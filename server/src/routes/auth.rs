//! /auth/{action} — Phase 1 keeps the indieauth.com delegation verbatim.
//! Phase 4 replaces this module with self-hosted endpoints.

use std::sync::Arc;

use axum::body::Body;
use axum::extract::{Path, State};
use axum::http::{header, HeaderValue, Response, StatusCode};

use super::{plain, AppState};

/// Exact bytes served by the Racket server (jsexpr->string key order).
const METADATA_JSON: &str = concat!(
    "{\"authorization_endpoint\":\"https://indieauth.com/auth\",",
    "\"code_challenge_methods_supported\":[\"S256\"],",
    "\"issuer\":\"https://jacobhall.net\",",
    "\"token_endpoint\":\"https://tokens.indieauth.com/token\"}"
);

pub async fn auth_action(
    State(_state): State<Arc<AppState>>,
    Path(action): Path<String>,
) -> Response<Body> {
    match action.as_str() {
        "metadata" => Response::builder()
            .status(StatusCode::OK)
            .header(
                header::CONTENT_TYPE,
                HeaderValue::from_static("application/json; charset=utf-8"),
            )
            .body(Body::from(METADATA_JSON))
            .unwrap(),
        // Parity quirk: unknown auth actions are a 500, not a 404.
        _ => plain(StatusCode::INTERNAL_SERVER_ERROR, "auth action not found"),
    }
}
