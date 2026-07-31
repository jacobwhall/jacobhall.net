//! Outgoing webmentions: enqueue targets linked from a page's e-content,
//! discover each target's webmention endpoint (Link header first, then the
//! first <link>/<a> rel=webmention in document order, per the spec), POST
//! the notification, and retry transient failures with backoff.

use std::sync::Arc;
use std::time::Duration;

use scraper::{Html, Selector};
use sqlx::{PgPool, Row};
use url::Url;

use crate::routes::AppState;

const MAX_ATTEMPTS: i32 = 5;
const BACKOFF_SECS: [i64; 4] = [60, 900, 7200, 43200];
const POLL_INTERVAL: Duration = Duration::from_secs(60);
const SNIPPET_LEN: usize = 500;

pub fn client() -> reqwest::Result<reqwest::Client> {
    reqwest::Client::builder()
        .user_agent("jacobhall.net-webmention (+https://jacobhall.net/webmention)")
        .timeout(Duration::from_secs(30))
        .connect_timeout(Duration::from_secs(10))
        .redirect(reqwest::redirect::Policy::limited(5))
        .build()
}

pub async fn run(state: Arc<AppState>) {
    let client = match client() {
        Ok(c) => c,
        Err(e) => {
            tracing::error!("webmention send worker: could not build HTTP client: {e}");
            return;
        }
    };
    tracing::info!("webmention send worker started");
    loop {
        if let Err(e) = process_due(&state.pool, &client).await {
            tracing::error!("webmention send tick failed: {e}");
        }
        tokio::select! {
            _ = state.send_notify.notified() => {},
            _ = tokio::time::sleep(POLL_INTERVAL) => {},
        }
    }
}

/// Fetch one of our own pages and enqueue an outbox row per external link in
/// its e-content. Returns the number of rows enqueued (or re-enqueued).
pub async fn enqueue_links_for(
    pool: &PgPool,
    client: &reqwest::Client,
    source_url: &str,
) -> Result<usize, String> {
    let source = Url::parse(source_url).map_err(|e| format!("bad source URL: {e}"))?;
    let resp = client
        .get(source.clone())
        .send()
        .await
        .map_err(|e| format!("could not fetch source: {e}"))?;
    if !resp.status().is_success() {
        return Err(format!("source returned HTTP {}", resp.status().as_u16()));
    }
    let final_url = resp.url().clone();
    let html = resp
        .text()
        .await
        .map_err(|e| format!("could not read source body: {e}"))?;

    let targets = extract_targets(&html, &final_url);
    let mut enqueued = 0;
    for target in targets {
        sqlx::query(
            "INSERT INTO wm_outbox (source_url, target_url, next_attempt_at)
             VALUES ($1, $2, now())
             ON CONFLICT (source_url, target_url) DO UPDATE
             SET status = 'pending', attempts = 0, next_attempt_at = now(),
                 updated_at = now()",
        )
        .bind(source_url)
        .bind(&target)
        .execute(pool)
        .await
        .map_err(|e| format!("enqueue failed: {e}"))?;
        enqueued += 1;
    }
    Ok(enqueued)
}

/// External links inside the page's e-content blocks, deduplicated,
/// same-host links skipped.
fn extract_targets(html: &str, page_url: &Url) -> Vec<String> {
    let doc = Html::parse_document(html);
    let sel = Selector::parse(".e-content a[href]").expect("static selector");
    let mut seen = Vec::new();
    for a in doc.select(&sel) {
        let Some(href) = a.value().attr("href") else {
            continue;
        };
        let Ok(abs) = page_url.join(href) else {
            continue;
        };
        if !matches!(abs.scheme(), "http" | "https") {
            continue;
        }
        if abs.host_str() == page_url.host_str() {
            continue;
        }
        let s = abs.to_string();
        if !seen.contains(&s) {
            seen.push(s);
        }
    }
    seen
}

pub async fn process_due(pool: &PgPool, client: &reqwest::Client) -> sqlx::Result<()> {
    let due = sqlx::query(
        "SELECT id, source_url, target_url, attempts FROM wm_outbox
         WHERE status = 'pending'
         AND (next_attempt_at IS NULL OR next_attempt_at <= now())
         ORDER BY id
         LIMIT 10",
    )
    .fetch_all(pool)
    .await?;
    for row in due {
        let id: i64 = row.get("id");
        let source: String = row.get("source_url");
        let target: String = row.get("target_url");
        let attempts: i32 = row.get("attempts");
        send_one(pool, client, id, &source, &target, attempts).await?;
    }
    Ok(())
}

async fn defer(
    pool: &PgPool,
    id: i64,
    attempts: i32,
    snippet: &str,
    response_status: Option<i32>,
    endpoint: Option<&str>,
) -> sqlx::Result<()> {
    let attempts = attempts + 1;
    if attempts >= MAX_ATTEMPTS {
        sqlx::query(
            "UPDATE wm_outbox SET status = 'failed', response_body_snippet = $2,
                    response_status = $3, endpoint = COALESCE($4, endpoint),
                    updated_at = now() WHERE id = $1",
        )
        .bind(id)
        .bind(snippet)
        .bind(response_status)
        .bind(endpoint)
        .execute(pool)
        .await?;
        return Ok(());
    }
    let delay = BACKOFF_SECS[(attempts as usize - 1).min(BACKOFF_SECS.len() - 1)];
    sqlx::query(
        "UPDATE wm_outbox
         SET attempts = $2, next_attempt_at = now() + make_interval(secs => $3),
             response_body_snippet = $4, response_status = $5,
             endpoint = COALESCE($6, endpoint), updated_at = now()
         WHERE id = $1",
    )
    .bind(id)
    .bind(attempts)
    .bind(delay as f64)
    .bind(snippet)
    .bind(response_status)
    .bind(endpoint)
    .execute(pool)
    .await
    .map(|_| ())
}

async fn send_one(
    pool: &PgPool,
    client: &reqwest::Client,
    id: i64,
    source: &str,
    target: &str,
    attempts: i32,
) -> sqlx::Result<()> {
    let endpoint = match discover_endpoint(client, target).await {
        Ok(Some(e)) => e,
        Ok(None) => {
            sqlx::query(
                "UPDATE wm_outbox SET status = 'no_endpoint', updated_at = now()
                 WHERE id = $1",
            )
            .bind(id)
            .execute(pool)
            .await?;
            return Ok(());
        }
        Err(e) => {
            return defer(
                pool,
                id,
                attempts,
                &format!("discovery failed: {e}"),
                None,
                None,
            )
            .await
        }
    };

    let result = client
        .post(&endpoint)
        .form(&[("source", source), ("target", target)])
        .send()
        .await;
    match result {
        Ok(resp) => {
            let status = resp.status().as_u16() as i32;
            let snippet: String = resp
                .text()
                .await
                .unwrap_or_default()
                .chars()
                .take(SNIPPET_LEN)
                .collect();
            if (200..300).contains(&status) {
                sqlx::query(
                    "UPDATE wm_outbox
                     SET status = 'sent', endpoint = $2, response_status = $3,
                         response_body_snippet = $4, updated_at = now()
                     WHERE id = $1",
                )
                .bind(id)
                .bind(&endpoint)
                .bind(status)
                .bind(&snippet)
                .execute(pool)
                .await?;
                tracing::info!("webmention sent: {source} -> {target} via {endpoint}");
                Ok(())
            } else {
                defer(pool, id, attempts, &snippet, Some(status), Some(&endpoint)).await
            }
        }
        Err(e) => {
            defer(
                pool,
                id,
                attempts,
                &format!("post failed: {e}"),
                None,
                Some(&endpoint),
            )
            .await
        }
    }
}

/// Webmention endpoint discovery per the spec: the first rel=webmention in
/// the Link headers (in header order), else the first <link> or <a> with
/// rel=webmention in document order. Relative URLs (including "") resolve
/// against the final URL after redirects.
pub async fn discover_endpoint(
    client: &reqwest::Client,
    target: &str,
) -> Result<Option<String>, String> {
    let resp = client
        .get(target)
        .header("Accept", "text/html")
        .send()
        .await
        .map_err(|e| e.to_string())?;
    let final_url = resp.url().clone();

    for value in resp.headers().get_all(reqwest::header::LINK) {
        if let Ok(value) = value.to_str() {
            if let Some(href) = link_header_webmention(value) {
                return Ok(Some(resolve(&final_url, &href)));
            }
        }
    }

    let html = resp.text().await.map_err(|e| e.to_string())?;
    Ok(html_webmention_endpoint(&html).map(|href| resolve(&final_url, &href)))
}

fn resolve(base: &Url, href: &str) -> String {
    if href.is_empty() {
        return base.to_string();
    }
    base.join(href)
        .map(|u| u.to_string())
        .unwrap_or_else(|_| href.to_string())
}

/// Parse one Link header value; return the first target whose rel tokens
/// include "webmention". Commas inside <...> (allowed in URLs) are handled.
fn link_header_webmention(value: &str) -> Option<String> {
    let mut parts: Vec<String> = Vec::new();
    let mut depth_url = false;
    let mut cur = String::new();
    for c in value.chars() {
        match c {
            '<' => {
                depth_url = true;
                cur.push(c);
            }
            '>' => {
                depth_url = false;
                cur.push(c);
            }
            ',' if !depth_url => {
                parts.push(std::mem::take(&mut cur));
            }
            _ => cur.push(c),
        }
    }
    if !cur.trim().is_empty() {
        parts.push(cur);
    }

    for part in parts {
        let part = part.trim();
        let url = part
            .split(';')
            .next()
            .map(str::trim)
            .and_then(|u| u.strip_prefix('<'))
            .and_then(|u| u.strip_suffix('>'))?
            .to_string();
        let has_webmention_rel = part.split(';').skip(1).any(|param| {
            let param = param.trim();
            let Some((k, v)) = param.split_once('=') else {
                return false;
            };
            if !k.trim().eq_ignore_ascii_case("rel") {
                return false;
            }
            let v = v.trim().trim_matches('"');
            v.split_ascii_whitespace()
                .any(|token| token.eq_ignore_ascii_case("webmention"))
        });
        if has_webmention_rel {
            return Some(url);
        }
    }
    None
}

/// First <link> or <a> with rel~=webmention in document order; returns the
/// raw href (possibly ""), unresolved.
fn html_webmention_endpoint(html: &str) -> Option<String> {
    let doc = Html::parse_document(html);
    let sel = Selector::parse("link[rel][href], a[rel][href]").expect("static selector");
    for el in doc.select(&sel) {
        let rel = el.value().attr("rel").unwrap_or("");
        if rel
            .split_ascii_whitespace()
            .any(|t| t.eq_ignore_ascii_case("webmention"))
        {
            return el.value().attr("href").map(str::to_string);
        }
    }
    None
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn link_header_simple() {
        assert_eq!(
            link_header_webmention(r#"<https://example.com/wm>; rel="webmention""#),
            Some("https://example.com/wm".into())
        );
    }

    #[test]
    fn link_header_multi_rel_and_unquoted() {
        assert_eq!(
            link_header_webmention(r#"<https://example.com/wm>; rel="something webmention""#),
            Some("https://example.com/wm".into())
        );
        assert_eq!(
            link_header_webmention("<https://example.com/wm>; rel=webmention"),
            Some("https://example.com/wm".into())
        );
    }

    #[test]
    fn link_header_comma_in_url_and_multiple_links() {
        assert_eq!(
            link_header_webmention(
                r#"<https://example.com/a,b>; rel="other", <https://example.com/wm?x=1,2>; rel="webmention""#
            ),
            Some("https://example.com/wm?x=1,2".into())
        );
    }

    #[test]
    fn link_header_no_match() {
        assert_eq!(
            link_header_webmention(r#"<https://example.com/x>; rel="stylesheet""#),
            None
        );
    }

    #[test]
    fn html_first_in_document_order() {
        let html = r#"<html><head>
            <link rel="stylesheet" href="/css">
            <link rel="webmention" href="/wm-link">
        </head><body>
            <a rel="webmention" href="/wm-a">endpoint</a>
        </body></html>"#;
        assert_eq!(html_webmention_endpoint(html), Some("/wm-link".into()));
    }

    #[test]
    fn html_empty_href_means_page_itself() {
        let html = r#"<link rel="webmention" href="">"#;
        assert_eq!(html_webmention_endpoint(html), Some(String::new()));
        let base = Url::parse("https://example.com/post?x=1").unwrap();
        assert_eq!(resolve(&base, ""), "https://example.com/post?x=1");
    }

    /// Read-only discovery against the webmention.rocks test suite.
    /// Network-dependent, so ignored by default:
    ///   cargo test discovery_webmention_rocks -- --ignored --nocapture
    #[tokio::test]
    #[ignore]
    async fn discovery_webmention_rocks() {
        let client = client().unwrap();
        let mut failures = Vec::new();
        for n in 1..=22 {
            let target = format!("https://webmention.rocks/test/{n}");
            // Test 15 advertises rel=webmention with href="" — the correct
            // endpoint is the page itself.
            let ok = |endpoint: &str| {
                if n == 15 {
                    endpoint.trim_end_matches('/') == target
                } else {
                    endpoint.contains(&format!("/test/{n}/webmention"))
                }
            };
            match discover_endpoint(&client, &target).await {
                Ok(Some(endpoint)) if ok(&endpoint) => {
                    println!("test {n}: OK {endpoint}");
                }
                other => {
                    println!("test {n}: FAIL {other:?}");
                    failures.push(n);
                }
            }
        }
        assert!(failures.is_empty(), "failed discovery tests: {failures:?}");
    }

    #[test]
    fn extract_targets_dedupes_and_skips_same_host() {
        let base = Url::parse("https://jacobhall.net/2022/01/29/000177").unwrap();
        let html = r#"<div class="e-content">
            <a href="https://other.example/a">a</a>
            <a href="https://other.example/a">dup</a>
            <a href="/2021/08/horton-hears-a-whostyle">internal</a>
            <a href="mailto:someone@example.com">mail</a>
            <a href="https://second.example/b">b</a>
        </div>"#;
        assert_eq!(
            extract_targets(html, &base),
            vec![
                "https://other.example/a".to_string(),
                "https://second.example/b".to_string()
            ]
        );
    }
}
