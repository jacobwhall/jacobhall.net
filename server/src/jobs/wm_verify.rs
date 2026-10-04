//! Webmention verification worker.
//!
//! wm_log (legacy queue, still written by POST /webmention)
//!   → webmentions (state machine: pending → verified | verify_failed,
//!     then approved | rejected via the admin UI)
//!
//! Verification per the Webmention spec: fetch the source, confirm it links
//! to the target, then parse microformats to classify the mention and pull
//! author/content. Transient fetch failures back off at +1m/15m/2h/12h and
//! give up after 5 attempts.

use std::sync::Arc;
use std::time::Duration;

use chrono::NaiveDateTime;
use sqlx::{PgPool, Row};
use url::Url;

use crate::db;
use crate::mf2;
use crate::routes::AppState;

const MAX_BODY: usize = 1024 * 1024;
const MAX_ATTEMPTS: i32 = 5;
const BACKOFF_SECS: [i64; 4] = [60, 900, 7200, 43200];
const POLL_INTERVAL: Duration = Duration::from_secs(30);

pub async fn run(state: Arc<AppState>) {
    let client = match super::wm_send::client() {
        Ok(c) => c,
        Err(e) => {
            tracing::error!("webmention worker: could not build HTTP client: {e}");
            return;
        }
    };
    tracing::info!("webmention verification worker started");
    loop {
        if let Err(e) = tick(&state, &client).await {
            tracing::error!("webmention worker tick failed: {e}");
        }
        tokio::select! {
            _ = state.notify.notified() => {},
            _ = tokio::time::sleep(POLL_INTERVAL) => {},
        }
    }
}

async fn tick(state: &AppState, client: &reqwest::Client) -> sqlx::Result<()> {
    intake(&state.pool).await?;
    let due = sqlx::query(
        "SELECT id, source, target, attempts FROM webmentions
         WHERE status = 'pending'
         AND (next_attempt_at IS NULL OR next_attempt_at <= now())
         ORDER BY id
         LIMIT 10",
    )
    .fetch_all(&state.pool)
    .await?;
    for row in due {
        let id: i64 = row.get("id");
        let source: String = row.get("source");
        let target: String = row.get("target");
        let attempts: i32 = row.get("attempts");
        verify_one(state, client, id, &source, &target, attempts).await?;
    }
    Ok(())
}

/// Move new wm_log rows into the webmentions state machine. A re-sent
/// (source, target) pair resets the existing row to pending, per spec.
async fn intake(pool: &PgPool) -> sqlx::Result<()> {
    let rows = sqlx::query(
        "SELECT source, target FROM wm_log
         WHERE processed_at IS NULL AND source IS NOT NULL AND target IS NOT NULL",
    )
    .fetch_all(pool)
    .await?;
    for row in rows {
        let source: String = row.get("source");
        let target: String = row.get("target");
        sqlx::query(
            "INSERT INTO webmentions (source, target, next_attempt_at)
             VALUES ($1, $2, now())
             ON CONFLICT (source, target) DO UPDATE
             SET status = 'pending', attempts = 0, next_attempt_at = now(),
                 error = NULL, updated_at = now()",
        )
        .bind(&source)
        .bind(&target)
        .execute(pool)
        .await?;
        sqlx::query(
            "UPDATE wm_log SET processed_at = now()
             WHERE source = $1 AND target = $2 AND processed_at IS NULL",
        )
        .bind(&source)
        .bind(&target)
        .execute(pool)
        .await?;
        tracing::info!("webmention queued for verification: {source} -> {target}");
    }
    Ok(())
}

async fn fail_permanent(pool: &PgPool, id: i64, error: &str) -> sqlx::Result<()> {
    sqlx::query(
        "UPDATE webmentions SET status = 'verify_failed', error = $2, updated_at = now()
         WHERE id = $1",
    )
    .bind(id)
    .bind(error)
    .execute(pool)
    .await
    .map(|_| ())
}

async fn fail_transient(
    pool: &PgPool,
    id: i64,
    attempts: i32,
    error: &str,
    http_status: Option<i32>,
) -> sqlx::Result<()> {
    let attempts = attempts + 1;
    if attempts >= MAX_ATTEMPTS {
        return fail_permanent(pool, id, error).await;
    }
    let delay = BACKOFF_SECS[(attempts as usize - 1).min(BACKOFF_SECS.len() - 1)];
    sqlx::query(
        "UPDATE webmentions
         SET attempts = $2, next_attempt_at = now() + make_interval(secs => $3),
             error = $4, http_status = $5, updated_at = now()
         WHERE id = $1",
    )
    .bind(id)
    .bind(attempts)
    .bind(delay as f64)
    .bind(error)
    .bind(http_status)
    .execute(pool)
    .await
    .map(|_| ())
}

/// Best-effort published-date parsing: RFC 3339 (normalized to UTC), or a
/// bare "YYYY-MM-DDTHH:MM:SS".
fn parse_published(s: &str) -> Option<NaiveDateTime> {
    if let Ok(dt) = chrono::DateTime::parse_from_rfc3339(s) {
        return Some(dt.naive_utc());
    }
    NaiveDateTime::parse_from_str(s, "%Y-%m-%dT%H:%M:%S").ok()
}

/// Does the target URL exist on this site? DB permalink first (also yields
/// the post id for threading), then the content tree.
async fn resolve_target(state: &AppState, target: &str) -> sqlx::Result<(bool, Option<i32>)> {
    let trimmed = target.trim_end_matches('/');
    for candidate in [trimmed.to_string(), format!("{trimmed}/")] {
        if let Some(row) = db::post_by_permalink(&state.pool, &candidate).await? {
            return Ok((true, Some(row.post_id as i32)));
        }
    }
    let Ok(url) = Url::parse(target) else {
        return Ok((false, None));
    };
    let rel = url.path().trim_matches('/').to_string();
    if rel.is_empty() {
        return Ok((true, None)); // homepage
    }
    if rel.split('/').any(|s| s.starts_with('.')) {
        return Ok((false, None));
    }
    let fs_path = state.cfg.docroot.join(&rel);
    if fs_path.is_dir() || fs_path.is_file() {
        return Ok((true, None));
    }
    if !rel.contains('/') {
        let seg = rel.strip_suffix(".html").unwrap_or(&rel);
        if state
            .cfg
            .docroot
            .join("top-level")
            .join(format!("{seg}.txt"))
            .is_file()
        {
            return Ok((true, None));
        }
    }
    Ok((false, None))
}

async fn verify_one(
    state: &AppState,
    client: &reqwest::Client,
    id: i64,
    source: &str,
    target: &str,
    attempts: i32,
) -> sqlx::Result<()> {
    let Ok(source_url) = Url::parse(source) else {
        return fail_permanent(&state.pool, id, "source URL does not parse").await;
    };
    if source.trim_end_matches('/') == target.trim_end_matches('/') {
        return fail_permanent(&state.pool, id, "source and target are the same page").await;
    }
    let (target_exists, target_post_id) = resolve_target(state, target).await?;
    if !target_exists {
        return fail_permanent(&state.pool, id, "target page does not exist on this site").await;
    }

    let resp = match client.get(source_url.clone()).send().await {
        Ok(r) => r,
        Err(e) => {
            return fail_transient(
                &state.pool,
                id,
                attempts,
                &format!("fetch failed: {e}"),
                None,
            )
            .await
        }
    };
    let http_status = resp.status().as_u16() as i32;
    if !resp.status().is_success() {
        return fail_transient(
            &state.pool,
            id,
            attempts,
            &format!("source returned HTTP {http_status}"),
            Some(http_status),
        )
        .await;
    }
    let final_url = resp.url().clone();
    let mut resp = resp;
    let mut body: Vec<u8> = Vec::new();
    loop {
        match resp.chunk().await {
            Ok(Some(chunk)) => {
                body.extend_from_slice(&chunk);
                if body.len() > MAX_BODY {
                    body.truncate(MAX_BODY);
                    break;
                }
            }
            Ok(None) => break,
            Err(e) => {
                return fail_transient(
                    &state.pool,
                    id,
                    attempts,
                    &format!("body read failed: {e}"),
                    Some(http_status),
                )
                .await
            }
        }
    }
    let html = String::from_utf8_lossy(&body).to_string();

    // The source must actually link to the target. Trailing slash and
    // http/https scheme differences are tolerated (a target declared as
    // http:// is commonly linked as https://).
    let trimmed = target.trim_end_matches('/');
    let scheme_swapped = if let Some(rest) = trimmed.strip_prefix("https://") {
        format!("http://{rest}")
    } else if let Some(rest) = trimmed.strip_prefix("http://") {
        format!("https://{rest}")
    } else {
        trimmed.to_string()
    };
    if !html.contains(trimmed) && !html.contains(&scheme_swapped) {
        return fail_permanent(&state.pool, id, "source page does not link to the target").await;
    }

    let mention = mf2::extract(&html, &final_url, target);
    let published_at = mention.published.as_deref().and_then(parse_published);

    sqlx::query(
        "UPDATE webmentions
         SET status = 'verified', mf2_kind = $2, author_name = $3, author_url = $4,
             author_photo = $5, content_html = $6, content_text = $7,
             published_at = $8, http_status = $9, target_post_id = $10,
             error = NULL, updated_at = now()
         WHERE id = $1",
    )
    .bind(id)
    .bind(mention.kind.unwrap_or(mf2::Mf2Kind::Mention).as_str())
    .bind(&mention.author_name)
    .bind(&mention.author_url)
    .bind(&mention.author_photo)
    .bind(&mention.content_html)
    .bind(&mention.content_text)
    .bind(published_at)
    .bind(http_status)
    .bind(target_post_id)
    .execute(&state.pool)
    .await?;
    tracing::info!("webmention verified: {source} -> {target}");
    Ok(())
}
