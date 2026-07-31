//! The Racket dispatcher chain, steps 2–6 (step 1 is the axum router):
//!
//!   top-level pages → static files → slash redirects → article dirs /
//!   post slugs → 404 page
//!
//! Captured live behavior this must reproduce:
//! - existing dirs without trailing slash: 302 → path/  (files dispatcher)
//! - everything else without trailing slash: 301 → path/ (dir-redirect)
//! - posts render ONLY at trailing-slash URLs (/2021/03/13/000000/)
//! - date parts in post slugs are ignored; lookup is by post id alone
//! - unknown paths get the 404 page with HTTP 200
//!
//! Deliberate divergences (documented in compare.sh):
//! - dot-prefixed segments are never served (live serves /.git/config!)
//! - top-level pages match single-segment paths only
//! - post slugs require exactly 4 segments (Racket 500s on /1999/01/)

use std::sync::Arc;

use axum::body::Body;
use axum::extract::State;
use axum::http::{Request, Response, StatusCode};
use percent_encoding::percent_decode_str;
use tower::ServiceExt;
use tower_http::services::ServeDir;

use super::{page_404, redirect, respond_article, server_error, AppState};
use crate::db;
use crate::render::{render_post, ArticlePage};

fn is_digits(s: &str, len: usize) -> bool {
    s.len() == len && s.bytes().all(|b| b.is_ascii_digit())
}

pub async fn fallback(State(state): State<Arc<AppState>>, req: Request<Body>) -> Response<Body> {
    let raw_path = req.uri().path().to_string();
    let decoded = percent_decode_str(&raw_path)
        .decode_utf8_lossy()
        .to_string();
    let has_trailing = decoded.ends_with('/');
    let segs: Vec<String> = decoded
        .split('/')
        .filter(|s| !s.is_empty())
        .map(str::to_string)
        .collect();

    if segs
        .iter()
        .any(|s| s.starts_with('.') && s != ".well-known")
    {
        return page_404(&state).await;
    }

    // 1. Top-level pages: /{name} or /{name}.html → top-level/{name}.txt
    if segs.len() == 1 {
        let seg = segs[0]
            .strip_suffix(".html")
            .unwrap_or(&segs[0])
            .to_string();
        if !seg.is_empty() {
            let path = state
                .cfg
                .docroot
                .join("top-level")
                .join(format!("{seg}.txt"));
            if let Ok(content) = tokio::fs::read_to_string(&path).await {
                return respond_article(
                    &state,
                    ArticlePage {
                        title: &seg,
                        content: &content,
                        article_tags: true,
                        insert_title: false,
                        post_id: None,
                    },
                )
                .await;
            }
        }
    }

    let rel = segs.join("/");
    let fs_path = state.cfg.docroot.join(&rel);

    // 2. Existing directory without trailing slash → 302 (files dispatcher).
    if !has_trailing && !rel.is_empty() && fs_path.is_dir() {
        return redirect(StatusCode::FOUND, &format!("{raw_path}/"));
    }

    // 3. Static files, including dir index.html. ServeDir is infallible.
    let res = match ServeDir::new(&state.cfg.docroot)
        .append_index_html_on_directories(true)
        .oneshot(req)
        .await
    {
        Ok(res) => res,
        Err(never) => match never {},
    };
    if res.status() != StatusCode::NOT_FOUND {
        let mut res = res.map(Body::new);
        // Racket's mime table sends text/plain and text/html with an explicit
        // charset; mime_guess omits it. Match the live headers.
        let ct = res
            .headers()
            .get(axum::http::header::CONTENT_TYPE)
            .and_then(|v| v.to_str().ok())
            .unwrap_or("");
        let with_charset = match ct {
            "text/plain" => Some("text/plain; charset=utf-8"),
            "text/html" => Some("text/html; charset=utf-8"),
            _ => None,
        };
        if let Some(ct) = with_charset {
            res.headers_mut().insert(
                axum::http::header::CONTENT_TYPE,
                axum::http::HeaderValue::from_static(ct),
            );
        }
        return res;
    }

    // 4. Everything else without a trailing slash → 301 (dir-redirect).
    if !has_trailing {
        return redirect(StatusCode::MOVED_PERMANENTLY, &format!("{raw_path}/"));
    }

    // 5. Article directories (article.txt + title.txt).
    if fs_path.is_dir() {
        let article_path = fs_path.join("article.txt");
        let title_path = fs_path.join("title.txt");
        if article_path.is_file() && title_path.is_file() {
            let (title, content) = match (
                tokio::fs::read_to_string(&title_path).await,
                tokio::fs::read_to_string(&article_path).await,
            ) {
                (Ok(t), Ok(c)) => (t, c),
                _ => return page_404(&state).await,
            };
            // Article-dir permalinks in the DB end with "/" (NOTES.md).
            let permalink = format!("{}/{}/", state.cfg.base_url, rel);
            let post_id = match db::post_by_permalink(&state.pool, &permalink).await {
                Ok(row) => row.map(|r| r.post_id as i32),
                Err(e) => return server_error(e.into()),
            };
            return respond_article(
                &state,
                ArticlePage {
                    title: &title,
                    content: &content,
                    article_tags: true,
                    insert_title: true,
                    post_id,
                },
            )
            .await;
        }
        return page_404(&state).await;
    }

    // 6. Post slugs: /YYYY/MM/DD/NNNNNN/ — id-only lookup.
    if segs.len() == 4
        && is_digits(&segs[0], 4)
        && is_digits(&segs[1], 2)
        && is_digits(&segs[2], 2)
        && is_digits(&segs[3], 6)
    {
        let id: i32 = segs[3].parse().unwrap_or(-1);
        return match db::post_from_id(&state.pool, id).await {
            Ok(Some(row)) => {
                let content = match render_post(&state.tmpl, &row) {
                    Ok(c) => c,
                    Err(e) => return server_error(e.into()),
                };
                respond_article(
                    &state,
                    ArticlePage {
                        title: "post",
                        content: &content,
                        article_tags: false,
                        insert_title: false,
                        post_id: Some(row.post_id as i32),
                    },
                )
                .await
            }
            Ok(None) => page_404(&state).await,
            Err(e) => server_error(e.into()),
        };
    }

    page_404(&state).await
}
