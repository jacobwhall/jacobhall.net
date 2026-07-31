pub mod admin;
pub mod auth;
pub mod fallback;
pub mod pages;
pub mod webmention;

use axum::body::Body;
use axum::http::{header, HeaderValue, Response, StatusCode};
use axum_extra::extract::cookie::Key;

use crate::config::Config;
use crate::render::{ArticlePage, PageError, Templates};
use sqlx::PgPool;

pub struct AppState {
    pub pool: PgPool,
    pub cfg: Config,
    pub tmpl: Templates,
    /// Pinged by the webmention receiver so the worker runs immediately.
    pub notify: tokio::sync::Notify,
    /// Pinged when outgoing webmentions are enqueued.
    pub send_notify: tokio::sync::Notify,
    /// Signing key for admin session cookies.
    pub key: Key,
}

/// Racket http-200: 200 OK, text/html; charset=utf-8.
pub fn html_200(body: String) -> Response<Body> {
    Response::builder()
        .status(StatusCode::OK)
        .header(
            header::CONTENT_TYPE,
            HeaderValue::from_static("text/html; charset=utf-8"),
        )
        .body(Body::from(body))
        .unwrap()
}

pub fn plain(status: StatusCode, msg: &str) -> Response<Body> {
    Response::builder()
        .status(status)
        .header(
            header::CONTENT_TYPE,
            HeaderValue::from_static("text/plain; charset=utf-8"),
        )
        .body(Body::from(msg.to_string()))
        .unwrap()
}

pub fn redirect(status: StatusCode, location: &str) -> Response<Body> {
    Response::builder()
        .status(status)
        .header(header::LOCATION, location)
        .body(Body::empty())
        .unwrap()
}

/// Racket server-error: 500 with a plain-text message.
pub fn server_error(e: PageError) -> Response<Body> {
    tracing::error!("page error: {e}");
    plain(StatusCode::INTERNAL_SERVER_ERROR, &e.to_string())
}

/// The 404 page: the Racket article shell with title "404", now served with
/// a real 404 status (post-parity fix; Racket returned 200).
pub async fn page_404(state: &AppState) -> Response<Body> {
    match crate::render::article_page(
        &state.pool,
        &state.tmpl,
        ArticlePage {
            title: "404",
            content: "<h1>Not Found</h1>",
            article_tags: true,
            insert_title: false,
            post_id: None,
        },
    )
    .await
    {
        Ok(body) => Response::builder()
            .status(StatusCode::NOT_FOUND)
            .header(
                header::CONTENT_TYPE,
                HeaderValue::from_static("text/html; charset=utf-8"),
            )
            .body(Body::from(body))
            .unwrap(),
        Err(e) => server_error(e),
    }
}

/// Article page around pre-rendered content, converted to a response.
pub async fn respond_article(state: &AppState, page: ArticlePage<'_>) -> Response<Body> {
    match crate::render::article_page(&state.pool, &state.tmpl, page).await {
        Ok(body) => html_200(body),
        Err(e) => server_error(e),
    }
}
