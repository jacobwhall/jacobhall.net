//! /admin — webmention moderation. Auth is a single owner password
//! (ADMIN_PASSWORD) checked by SHA-256 comparison, establishing a signed
//! session cookie (COOKIE_KEY). When ADMIN_PASSWORD is unset the whole
//! area serves the 404 page.

use std::sync::Arc;

use axum::body::Body;
use axum::extract::{Form, Path, State};
use axum::http::{HeaderMap, Response, StatusCode};
use axum_extra::extract::cookie::{Cookie, SameSite, SignedCookieJar};
use minijinja::context;
use serde::Deserialize;
use sha2::{Digest, Sha256};
use sqlx::Row;

use super::{html_200, page_404, redirect, server_error, AppState};
use crate::db::PostRow;
use crate::mf2::Mf2Kind;
use crate::render::render_post;

const COOKIE_NAME: &str = "jh_admin";

fn password_matches(expected: &str, given: &str) -> bool {
    // Hash both sides; comparing digests is constant-time in effect and
    // avoids leaking length information.
    Sha256::digest(expected.as_bytes()) == Sha256::digest(given.as_bytes())
}

fn jar(state: &AppState, headers: &HeaderMap) -> SignedCookieJar {
    SignedCookieJar::from_headers(headers, state.key.clone())
}

fn authed(state: &AppState, headers: &HeaderMap) -> bool {
    jar(state, headers)
        .get(COOKIE_NAME)
        .map(|c| c.value() == "ok")
        .unwrap_or(false)
}

fn render_admin(state: &AppState, name: &str, ctx: minijinja::Value) -> Response<Body> {
    match state.tmpl.render(name, ctx) {
        Ok(body) => html_200(body),
        Err(e) => server_error(e.into()),
    }
}

pub async fn index(State(state): State<Arc<AppState>>, headers: HeaderMap) -> Response<Body> {
    if state.cfg.admin_password.is_none() {
        return page_404(&state).await;
    }
    if authed(&state, &headers) {
        return redirect(StatusCode::FOUND, "/admin/mentions");
    }
    render_admin(&state, "admin/login.html", context! { error => false })
}

#[derive(Deserialize)]
pub struct LoginForm {
    password: String,
}

pub async fn login(
    State(state): State<Arc<AppState>>,
    headers: HeaderMap,
    Form(form): Form<LoginForm>,
) -> Response<Body> {
    let Some(expected) = state.cfg.admin_password.as_deref() else {
        return page_404(&state).await;
    };
    if !password_matches(expected, &form.password) {
        return render_admin(&state, "admin/login.html", context! { error => true });
    }
    let cookie = Cookie::build((COOKIE_NAME, "ok"))
        .path("/")
        .http_only(true)
        .same_site(SameSite::Lax)
        .secure(state.cfg.base_url.starts_with("https://"))
        .build();
    let jar = jar(&state, &headers).add(cookie);
    use axum::response::IntoResponse;
    (jar, redirect(StatusCode::FOUND, "/admin/mentions")).into_response()
}

/// A verified mention awaiting moderation, prepared for the template.
struct PendingMention {
    id: i64,
    source: String,
    target: String,
    kind: String,
    default_type: i16,
    has_target_post: bool,
    preview: String,
}

fn kind_from_str(s: &str) -> Mf2Kind {
    match s {
        "like" => Mf2Kind::Like,
        "repost" => Mf2Kind::Repost,
        "bookmark" => Mf2Kind::Bookmark,
        "mention" => Mf2Kind::Mention,
        _ => Mf2Kind::Reply,
    }
}

pub async fn mentions(State(state): State<Arc<AppState>>, headers: HeaderMap) -> Response<Body> {
    if state.cfg.admin_password.is_none() {
        return page_404(&state).await;
    }
    if !authed(&state, &headers) {
        return redirect(StatusCode::FOUND, "/admin");
    }

    let rows = match sqlx::query(
        "SELECT id, source, target, status, mf2_kind, author_name, author_url,
                author_photo, content_html, published_at, target_post_id,
                created_post_id, attempts, error,
                to_char(published_at, 'Month DD, YYYY HH24:MI') AS published_disp
         FROM webmentions
         ORDER BY updated_at DESC
         LIMIT 200",
    )
    .fetch_all(&state.pool)
    .await
    {
        Ok(r) => r,
        Err(e) => return server_error(e.into()),
    };

    let types: Vec<(i16, String)> =
        match sqlx::query_as::<_, (i16, String)>("SELECT type, type_name FROM types ORDER BY type")
            .fetch_all(&state.pool)
            .await
        {
            Ok(t) => t,
            Err(e) => return server_error(e.into()),
        };
    let type_lookup = |id: i16| -> (String, String) {
        types
            .iter()
            .find(|(t, _)| *t == id)
            .map(|(_, name)| (name.clone(), String::new()))
            .unwrap_or_default()
    };

    let mut whostyles = vec!["jacobhall-net".to_string()];
    if let Ok(mut dir) = tokio::fs::read_dir(state.cfg.docroot.join("styles/whostyles")).await {
        while let Ok(Some(entry)) = dir.next_entry().await {
            if entry.path().is_dir() {
                if let Some(name) = entry.file_name().to_str() {
                    if !whostyles.iter().any(|w| w == name) {
                        whostyles.push(name.to_string());
                    }
                }
            }
        }
    }

    let mut verified = Vec::new();
    let mut failed = Vec::new();
    let mut decided = Vec::new();
    let mut pending_count = 0i64;

    for row in &rows {
        let status: String = row.get("status");
        let source: String = row.get("source");
        let target: String = row.get("target");
        match status.as_str() {
            "pending" => pending_count += 1,
            "verified" => {
                let kind = kind_from_str(
                    row.get::<Option<String>, _>("mf2_kind")
                        .as_deref()
                        .unwrap_or("reply"),
                );
                let default_type = kind.default_post_type();
                let (type_name, _) = type_lookup(default_type);
                let preview_row = PostRow {
                    type_: default_type,
                    type_slug: String::new(),
                    type_name,
                    post_id: 0,
                    author: row.get("author_name"),
                    post_title: None,
                    content_location: None,
                    bookmark_of: None,
                    published_date: row.get("published_at"),
                    published_date_iso: None,
                    published_date_disp: row.get("published_disp"),
                    updated_date: None,
                    permalink: target.clone(),
                    location: None,
                    display_location: false,
                    author_h_card: row
                        .get::<Option<String>, _>("author_url")
                        .unwrap_or_else(|| source.clone()),
                    author_photo: row.get("author_photo"),
                    whostyle: "jacobhall-net".into(),
                    original_url: Some(source.clone()),
                    reply_to_author: Some("Jacob Hall".into()),
                    reply_to_id: row.get("target_post_id"),
                    reply_to_author_h_card: Some("https://jacobhall.net".into()),
                    reply_to_author_photo: None,
                    reply_to_title: None,
                    reply_to_content: None,
                    reply_to_url: Some(target.clone()),
                    content: row.get("content_html"),
                    content_summary: None,
                };
                let preview = render_post(&state.tmpl, &preview_row)
                    .unwrap_or_else(|e| format!("preview failed: {e}"));
                verified.push(PendingMention {
                    id: row.get("id"),
                    source,
                    target,
                    kind: kind.as_str().to_string(),
                    default_type,
                    has_target_post: row.get::<Option<i32>, _>("target_post_id").is_some(),
                    preview,
                });
            }
            "verify_failed" => failed.push((
                source,
                target,
                row.get::<Option<String>, _>("error").unwrap_or_default(),
                row.get::<i32, _>("attempts"),
            )),
            _ => decided.push((
                status,
                source,
                target,
                row.get::<Option<i32>, _>("created_post_id"),
            )),
        }
    }

    let ctx = context! {
        pending_count => pending_count,
        verified => verified.iter().map(|m| context! {
            id => m.id,
            source => m.source.clone(),
            target => m.target.clone(),
            kind => m.kind.clone(),
            default_type => m.default_type,
            has_target_post => m.has_target_post,
            preview => m.preview.clone(),
        }).collect::<Vec<_>>(),
        failed => failed.iter().map(|(s, t, e, a)| context! {
            source => s.clone(), target => t.clone(), error => e.clone(), attempts => a,
        }).collect::<Vec<_>>(),
        decided => decided.iter().map(|(st, s, t, p)| context! {
            status => st.clone(), source => s.clone(), target => t.clone(), created_post_id => p,
        }).collect::<Vec<_>>(),
        types => types.iter().map(|(id, name)| context! { id => id, name => name.clone() }).collect::<Vec<_>>(),
        whostyles => whostyles,
    };
    render_admin(&state, "admin/mentions.html", ctx)
}

#[derive(Deserialize)]
pub struct ApproveForm {
    post_type: i16,
    whostyle: String,
}

pub async fn approve(
    State(state): State<Arc<AppState>>,
    headers: HeaderMap,
    Path(id): Path<i64>,
    Form(form): Form<ApproveForm>,
) -> Response<Body> {
    if state.cfg.admin_password.is_none() || !authed(&state, &headers) {
        return page_404(&state).await;
    }
    let row = match sqlx::query(
        "SELECT source, target, author_name, author_url, author_photo,
                content_html, published_at, target_post_id
         FROM webmentions WHERE id = $1 AND status = 'verified'",
    )
    .bind(id)
    .fetch_optional(&state.pool)
    .await
    {
        Ok(Some(r)) => r,
        Ok(None) => return page_404(&state).await,
        Err(e) => return server_error(e.into()),
    };

    let source: String = row.get("source");
    let target: String = row.get("target");
    let source_host = url::Url::parse(&source)
        .ok()
        .and_then(|u| u.host_str().map(str::to_owned))
        .unwrap_or_else(|| source.clone());

    // post_id and permalink come from the DB defaults — supplying post_id
    // would break the currval() chain the permalink default depends on.
    let inserted = sqlx::query_scalar::<_, i16>(
        "INSERT INTO entries
           (post_type, published, published_date, reply_to_id, reply_to_url,
            reply_to_author, reply_to_author_h_card, author, author_h_card,
            author_photo, original_url, content, whostyle)
         VALUES
           ($1, true, COALESCE($2, date_trunc('second', timezone('utc', now()))),
            $3, $4, 'Jacob Hall', 'https://jacobhall.net',
            COALESCE($5, $6), COALESCE($7, $8), $9, $10, $11,
            NULLIF($12, 'jacobhall-net'))
         RETURNING post_id",
    )
    .bind(form.post_type)
    .bind(row.get::<Option<chrono::NaiveDateTime>, _>("published_at"))
    .bind(row.get::<Option<i32>, _>("target_post_id"))
    .bind(&target)
    .bind(row.get::<Option<String>, _>("author_name"))
    .bind(&source_host)
    .bind(row.get::<Option<String>, _>("author_url"))
    .bind(&source)
    .bind(row.get::<Option<String>, _>("author_photo"))
    .bind(&source)
    .bind(row.get::<Option<String>, _>("content_html"))
    .bind(&form.whostyle)
    .fetch_one(&state.pool)
    .await;

    let post_id = match inserted {
        Ok(id) => id,
        Err(e) => return server_error(e.into()),
    };
    if let Err(e) = sqlx::query(
        "UPDATE webmentions
         SET status = 'approved', created_post_id = $2, updated_at = now()
         WHERE id = $1",
    )
    .bind(id)
    .bind(post_id as i32)
    .execute(&state.pool)
    .await
    {
        return server_error(e.into());
    }
    redirect(StatusCode::FOUND, "/admin/mentions")
}

pub async fn outbox(State(state): State<Arc<AppState>>, headers: HeaderMap) -> Response<Body> {
    if state.cfg.admin_password.is_none() {
        return page_404(&state).await;
    }
    if !authed(&state, &headers) {
        return redirect(StatusCode::FOUND, "/admin");
    }
    let rows = match sqlx::query(
        "SELECT id, source_url, target_url, status, endpoint, attempts,
                response_status, response_body_snippet, updated_at::text AS updated
         FROM wm_outbox ORDER BY updated_at DESC LIMIT 100",
    )
    .fetch_all(&state.pool)
    .await
    {
        Ok(r) => r,
        Err(e) => return server_error(e.into()),
    };
    let ctx = context! {
        rows => rows.iter().map(|r| context! {
            id => r.get::<i64, _>("id"),
            source => r.get::<String, _>("source_url"),
            target => r.get::<String, _>("target_url"),
            status => r.get::<String, _>("status"),
            endpoint => r.get::<Option<String>, _>("endpoint"),
            attempts => r.get::<i32, _>("attempts"),
            response_status => r.get::<Option<i32>, _>("response_status"),
            snippet => r.get::<Option<String>, _>("response_body_snippet"),
            updated => r.get::<Option<String>, _>("updated"),
        }).collect::<Vec<_>>(),
    };
    render_admin(&state, "admin/outbox.html", ctx)
}

#[derive(Deserialize)]
pub struct SendForm {
    source_url: String,
}

pub async fn outbox_send(
    State(state): State<Arc<AppState>>,
    headers: HeaderMap,
    Form(form): Form<SendForm>,
) -> Response<Body> {
    if state.cfg.admin_password.is_none() || !authed(&state, &headers) {
        return page_404(&state).await;
    }
    let client = match crate::jobs::wm_send::client() {
        Ok(c) => c,
        Err(e) => return super::plain(StatusCode::INTERNAL_SERVER_ERROR, &e.to_string()),
    };
    match crate::jobs::wm_send::enqueue_links_for(&state.pool, &client, &form.source_url).await {
        Ok(_) => {
            state.send_notify.notify_one();
            redirect(StatusCode::FOUND, "/admin/webmentions")
        }
        Err(e) => super::plain(StatusCode::BAD_REQUEST, &e),
    }
}

pub async fn reject(
    State(state): State<Arc<AppState>>,
    headers: HeaderMap,
    Path(id): Path<i64>,
) -> Response<Body> {
    if state.cfg.admin_password.is_none() || !authed(&state, &headers) {
        return page_404(&state).await;
    }
    if let Err(e) = sqlx::query(
        "UPDATE webmentions SET status = 'rejected', updated_at = now()
         WHERE id = $1 AND status IN ('pending', 'verified', 'verify_failed')",
    )
    .bind(id)
    .execute(&state.pool)
    .await
    {
        return server_error(e.into());
    }
    redirect(StatusCode::FOUND, "/admin/mentions")
}
