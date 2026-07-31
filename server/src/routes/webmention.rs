//! POST /webmention — the parity queue receiver. Validation order, response
//! strings (typos included), and status codes match site.rkt exactly.

use std::collections::HashMap;
use std::sync::Arc;

use axum::body::{to_bytes, Body};
use axum::extract::State;
use axum::http::{Method, Request, Response, StatusCode};
use url::Url;

use super::{plain, redirect, AppState};
use crate::db;

pub async fn webmention(State(state): State<Arc<AppState>>, req: Request<Body>) -> Response<Body> {
    if req.method() != Method::POST {
        // Non-POST falls through the Racket dispatch chain to the
        // slash-redirect: GET /webmention → 301 /webmention/.
        return redirect(StatusCode::MOVED_PERMANENTLY, "/webmention/");
    }

    let query = req.uri().query().map(str::to_owned);
    let body = match to_bytes(req.into_body(), 1024 * 1024).await {
        Ok(b) => b,
        Err(_) => return plain(StatusCode::BAD_REQUEST, "could not read request body"),
    };

    // Racket's request-bindings/raw: form body bindings, with URL query as
    // a fallback source.
    let mut params: HashMap<String, String> =
        url::form_urlencoded::parse(&body).into_owned().collect();
    if let Some(q) = query {
        for (k, v) in url::form_urlencoded::parse(q.as_bytes()).into_owned() {
            params.entry(k).or_insert(v);
        }
    }

    let (Some(source), Some(target)) = (params.get("source"), params.get("target")) else {
        return plain(
            StatusCode::BAD_REQUEST,
            "Webmention request must include both source and target!",
        );
    };

    let scheme_ok = |u: &Url| matches!(u.scheme(), "http" | "https");
    let (source_url, target_url) = match (Url::parse(source), Url::parse(target)) {
        (Ok(s), Ok(t)) if scheme_ok(&s) && scheme_ok(&t) => (s, t),
        _ => {
            return plain(
                StatusCode::BAD_REQUEST,
                "Source and target URLs must have either HTTP or HTTPS schemes.",
            )
        }
    };

    let expected_host = Url::parse(&state.cfg.base_url)
        .ok()
        .and_then(|u| u.host_str().map(str::to_owned))
        .unwrap_or_else(|| "jacobhall.net".into());
    if target_url.host_str() != Some(expected_host.as_str()) {
        return plain(
            StatusCode::BAD_REQUEST,
            "Target URL must point to jacobhall.net",
        );
    }

    match db::insert_webmention(&state.pool, source_url.as_str(), target_url.as_str()).await {
        Ok(()) => plain(
            StatusCode::ACCEPTED,
            "Thanks for the webmention! I have queued it for processing.",
        ),
        // Parity: the original message, typo and all.
        Err(_) => plain(
            StatusCode::INTERNAL_SERVER_ERROR,
            "An unknown error occured when inserting your webmention into my database.",
        ),
    }
}
