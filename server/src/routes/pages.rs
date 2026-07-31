//! Primary routes: homepage, /all, /few, /many, /kind/…, feeds.

use std::sync::Arc;

use axum::body::Body;
use axum::extract::{Path, State};
use axum::http::{header, HeaderValue, Response, StatusCode};

use super::{html_200, page_404, respond_article, server_error, AppState};
use crate::db;
use crate::feeds::{self, Dialect, FEED_CONTENT_TYPE};
use crate::render::{posts_html, ArticlePage};

pub async fn homepage(State(state): State<Arc<AppState>>) -> Response<Body> {
    match crate::render::homepage_html(&state.pool, &state.tmpl).await {
        Ok(body) => html_200(body),
        Err(e) => server_error(e),
    }
}

async fn posts_page(state: &AppState, types: &[i32]) -> Response<Body> {
    let content = match posts_html(&state.pool, &state.tmpl, 25, types).await {
        Ok(c) => c,
        Err(e) => return server_error(e),
    };
    respond_article(
        state,
        ArticlePage {
            title: "all posts",
            content: &content,
            article_tags: false,
            insert_title: false,
            post_id: None,
        },
    )
    .await
}

pub async fn all_posts(State(state): State<Arc<AppState>>) -> Response<Body> {
    posts_page(&state, &[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]).await
}

pub async fn few_posts(State(state): State<Arc<AppState>>) -> Response<Body> {
    // "What I imagine you'd like to follow" — articles, notes, photos, etc.
    posts_page(&state, &[1, 2, 3, 4]).await
}

pub async fn many_posts(State(state): State<Arc<AppState>>) -> Response<Body> {
    // "What I imagine you might want to follow" — + bookmarks, watches, etc.
    posts_page(&state, &[1, 2, 3, 4, 5, 6, 7, 8, 9, 10]).await
}

/// GET /kind/ — empty kind renders the "all posts" page.
pub async fn kind_empty(State(state): State<Arc<AppState>>) -> Response<Body> {
    posts_page(&state, &[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]).await
}

/// GET /kind/{kind} — posts of one type, titled with the type name;
/// unknown slug gets the 404 page (HTTP 200, Racket parity).
pub async fn kind_page(
    State(state): State<Arc<AppState>>,
    Path(kind): Path<String>,
) -> Response<Body> {
    let type_row = match db::type_by_slug(&state.pool, &kind).await {
        Ok(t) => t,
        Err(e) => return server_error(e.into()),
    };
    let Some((type_id, type_name)) = type_row else {
        return page_404(&state).await;
    };
    let content = match posts_html(&state.pool, &state.tmpl, 25, &[type_id as i32]).await {
        Ok(c) => c,
        Err(e) => return server_error(e),
    };
    respond_article(
        &state,
        ArticlePage {
            title: &type_name,
            content: &content,
            article_tags: false,
            insert_title: false,
            post_id: None,
        },
    )
    .await
}

async fn feed_response(state: &AppState, dialect: Dialect) -> Response<Body> {
    match feeds::build_feed(&state.pool, &state.cfg.base_url, dialect).await {
        Ok(xml) => Response::builder()
            .status(StatusCode::OK)
            .header(
                header::CONTENT_TYPE,
                HeaderValue::from_static(FEED_CONTENT_TYPE),
            )
            .body(Body::from(xml))
            .unwrap(),
        Err(e) => server_error(e.into()),
    }
}

pub async fn rss_feed(State(state): State<Arc<AppState>>) -> Response<Body> {
    feed_response(&state, Dialect::Rss).await
}

pub async fn atom_feed(State(state): State<Arc<AppState>>) -> Response<Body> {
    feed_response(&state, Dialect::Atom).await
}
