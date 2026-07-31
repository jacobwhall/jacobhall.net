//! Queries against the vposts view, kept verbatim from site.rkt.
//! The view supplies ordering (ORDER BY published_date DESC) and the
//! computed columns (published_date_iso/_disp, COALESCEd defaults).
//! Params bind as i32/i64 — never i16 — so out-of-range URL ids (999999)
//! compare cleanly against the smallint columns instead of overflowing.

use chrono::NaiveDateTime;
use sqlx::PgPool;

// Mirrors every vposts column; some fields (reply_to_content, …) are mapped
// but not yet consumed by any template, same as in the Racket original.
#[allow(dead_code)]
#[derive(Debug, Clone, sqlx::FromRow)]
pub struct PostRow {
    #[sqlx(rename = "type")]
    pub type_: i16,
    pub type_slug: String,
    pub type_name: String,
    pub post_id: i16,
    pub author: Option<String>,
    pub post_title: Option<String>,
    pub content_location: Option<String>,
    pub bookmark_of: Option<String>,
    pub published_date: Option<NaiveDateTime>,
    pub published_date_iso: Option<String>,
    pub published_date_disp: Option<String>,
    pub updated_date: Option<NaiveDateTime>,
    pub permalink: String,
    pub location: Option<String>,
    pub display_location: bool,
    pub author_h_card: String,
    pub author_photo: Option<String>,
    pub whostyle: String,
    pub original_url: Option<String>,
    pub reply_to_author: Option<String>,
    pub reply_to_id: Option<i32>,
    pub reply_to_author_h_card: Option<String>,
    pub reply_to_author_photo: Option<String>,
    pub reply_to_title: Option<String>,
    pub reply_to_content: Option<String>,
    pub reply_to_url: Option<String>,
    pub content: Option<String>,
    pub content_summary: Option<String>,
}

pub async fn posts_query(
    pool: &PgPool,
    limit: i64,
    author: &str,
    types: &[i32],
) -> sqlx::Result<Vec<PostRow>> {
    sqlx::query_as::<_, PostRow>(
        "SELECT *
         FROM vPosts
         WHERE author = $1
         AND type=ANY($2::int[])
         LIMIT $3",
    )
    .bind(author)
    .bind(types)
    .bind(limit)
    .fetch_all(pool)
    .await
}

pub async fn replies_query(pool: &PgPool, reply_to_id: i32) -> sqlx::Result<Vec<PostRow>> {
    sqlx::query_as::<_, PostRow>(
        "SELECT *
         FROM vPosts
         WHERE type = 7
         AND reply_to_id = $1
         ORDER BY published_date ASC",
    )
    .bind(reply_to_id)
    .fetch_all(pool)
    .await
}

pub async fn likes_query(pool: &PgPool, reply_to_id: i32) -> sqlx::Result<Vec<PostRow>> {
    sqlx::query_as::<_, PostRow>(
        "SELECT *
         FROM vPosts
         WHERE type = 6
         AND reply_to_id = $1
         ORDER BY published_date DESC",
    )
    .bind(reply_to_id)
    .fetch_all(pool)
    .await
}

pub async fn post_from_id(pool: &PgPool, post_id: i32) -> sqlx::Result<Option<PostRow>> {
    sqlx::query_as::<_, PostRow>(
        "SELECT *
         FROM vPosts
         WHERE post_id = $1
         LIMIT 1",
    )
    .bind(post_id)
    .fetch_optional(pool)
    .await
}

pub async fn post_by_permalink(pool: &PgPool, permalink: &str) -> sqlx::Result<Option<PostRow>> {
    sqlx::query_as::<_, PostRow>(
        "SELECT *
         FROM vPosts
         WHERE permalink = $1
         LIMIT 1",
    )
    .bind(permalink)
    .fetch_optional(pool)
    .await
}

pub async fn type_by_slug(pool: &PgPool, slug: &str) -> sqlx::Result<Option<(i16, String)>> {
    sqlx::query_as::<_, (i16, String)>(
        "SELECT type, type_name
         FROM types
         WHERE slug = $1
         LIMIT 1",
    )
    .bind(slug)
    .fetch_optional(pool)
    .await
}

pub async fn insert_webmention(pool: &PgPool, source: &str, target: &str) -> sqlx::Result<()> {
    sqlx::query(
        "INSERT INTO wm_log
         (source, target)
         VALUES
         ($1, $2)",
    )
    .bind(source)
    .bind(target)
    .execute(pool)
    .await
    .map(|_| ())
}
