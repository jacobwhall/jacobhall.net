//! RSS/Atom feeds, hand-built to match the splitflap output captured in the
//! golden corpus byte-for-byte (structure, indentation, quirks and all):
//! - both feeds are served as application/rss+xml; charset=utf-8
//! - the Atom self-URL is the historical .../feeds/atom/v1/atom (no ".atom")
//! - RSS pubDate days are not zero-padded ("Wed, 3 Aug 2022 …")
//! - tag URIs slugify the RAW DB title; fallback title is the unpadded post_id
//! - the generator string is kept for parity until cutover

use chrono::NaiveDateTime;
use sqlx::PgPool;

use crate::db::{self, PostRow};

pub const FEED_CONTENT_TYPE: &str = "application/rss+xml; charset=utf-8";
const GENERATOR: &str = "Custom (Rust)";
const TAG_BASE: &str = "tag:jacobhall.net,2022:blog";
const AUTHOR_NAME: &str = "Jacob Hall";
const AUTHOR_EMAIL: &str = "email@jacobhall.net";

#[derive(Clone, Copy, PartialEq)]
pub enum Dialect {
    Rss,
    Atom,
}

fn xml_escape(s: &str) -> String {
    s.replace('&', "&amp;")
        .replace('<', "&lt;")
        .replace('>', "&gt;")
}

/// Racket: (string-downcase (string-normalize-spaces title #px"[^\\w]+" "-"))
/// — collapse runs of non-word chars to "-", trimming the ends first.
fn slugify(title: &str) -> String {
    let mut out = String::new();
    let mut pending_dash = false;
    for c in title.chars() {
        if c.is_ascii_alphanumeric() || c == '_' {
            if pending_dash && !out.is_empty() {
                out.push('-');
            }
            pending_dash = false;
            out.push(c.to_ascii_lowercase());
        } else {
            pending_dash = true;
        }
    }
    out
}

struct FeedItem {
    title: String,
    link: String,
    published: NaiveDateTime,
    updated: NaiveDateTime,
    tag_uri: String,
    content: String,
}

fn to_item(row: &PostRow) -> FeedItem {
    let raw_title = row.post_title.clone().unwrap_or_default();
    let title = if raw_title.is_empty() {
        row.post_id.to_string()
    } else {
        raw_title
    };
    let content = match row.content.as_deref() {
        None | Some("") => format!(
            "{}<p><a href=\"{}\">read full article &gt;&gt;</a></p>",
            row.content_summary.clone().unwrap_or_default(),
            row.permalink
        ),
        Some(c) => c.to_string(),
    };
    let fallback = chrono::NaiveDate::from_ymd_opt(2022, 1, 1)
        .unwrap()
        .and_hms_opt(0, 0, 0)
        .unwrap();
    let published = row.published_date.unwrap_or(fallback);
    let updated = row.updated_date.unwrap_or(published);
    FeedItem {
        tag_uri: format!("{}.{}", TAG_BASE, slugify(&title)),
        title,
        link: row.permalink.clone(),
        published,
        updated,
        content,
    }
}

fn rss_date(t: &NaiveDateTime) -> String {
    t.format("%a, %-d %b %Y %H:%M:%S +0000").to_string()
}

fn atom_date(t: &NaiveDateTime) -> String {
    t.format("%Y-%m-%dT%H:%M:%SZ").to_string()
}

fn build_rss(base_url: &str, items: &[FeedItem], newest: &NaiveDateTime) -> String {
    let mut out = String::new();
    out.push_str("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\n");
    out.push_str("<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n");
    out.push_str("  <channel>\n");
    out.push_str(&format!("    <title>{}</title>\n", AUTHOR_NAME));
    out.push_str(&format!(
        "    <atom:link rel=\"self\" href=\"{}/feeds/rss/v1.rss\" type=\"application/rss+xml\" />\n",
        base_url
    ));
    out.push_str(&format!("    <link>{}</link>\n", base_url));
    out.push_str(&format!("    <pubDate>{}</pubDate>\n", rss_date(newest)));
    out.push_str(&format!(
        "    <lastBuildDate>{}</lastBuildDate>\n",
        rss_date(newest)
    ));
    out.push_str(&format!(
        "    <generator>{} (https://racket-lang.org)</generator>\n",
        GENERATOR
    ));
    out.push_str(&format!("    <description>{}</description>\n", AUTHOR_NAME));
    out.push_str("    <language>en</language>\n");
    for item in items {
        out.push_str("    <item>\n");
        out.push_str(&format!(
            "      <title>{}</title>\n",
            xml_escape(&item.title)
        ));
        out.push_str(&format!("      <link>{}</link>\n", item.link));
        out.push_str(&format!(
            "      <pubDate>{}</pubDate>\n",
            rss_date(&item.published)
        ));
        out.push_str(&format!(
            "      <author>{} ({})</author>\n",
            AUTHOR_EMAIL, AUTHOR_NAME
        ));
        out.push_str(&format!(
            "      <guid isPermaLink=\"false\">{}</guid>\n",
            item.tag_uri
        ));
        out.push_str("      <description>\n");
        out.push_str(&format!("        <![CDATA[{}]]>\n", item.content));
        out.push_str("      </description>\n");
        out.push_str("    </item>\n");
    }
    out.push_str("  </channel>\n");
    out.push_str("</rss>");
    out
}

fn build_atom(base_url: &str, items: &[FeedItem], newest: &NaiveDateTime) -> String {
    let mut out = String::new();
    out.push_str("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\n");
    out.push_str("<feed xmlns=\"http://www.w3.org/2005/Atom\" xml:lang=\"en\">\n");
    out.push_str(&format!("  <title type=\"text\">{}</title>\n", AUTHOR_NAME));
    // Historical quirk: /v1/atom, not /v1.atom — matches the live feed.
    out.push_str(&format!(
        "  <link rel=\"self\" href=\"{}/feeds/atom/v1/atom\" />\n",
        base_url
    ));
    out.push_str(&format!(
        "  <link rel=\"alternate\" href=\"{}\" />\n",
        base_url
    ));
    out.push_str(&format!("  <updated>{}</updated>\n", atom_date(newest)));
    out.push_str(&format!("  <id>{}</id>\n", TAG_BASE));
    out.push_str(&format!(
        "  <generator uri=\"https://racket-lang.org\" version=\"8.5\">{}</generator>\n",
        GENERATOR
    ));
    for item in items {
        out.push_str("  <entry>\n");
        out.push_str(&format!(
            "    <title type=\"text\">{}</title>\n",
            xml_escape(&item.title)
        ));
        out.push_str(&format!(
            "    <link rel=\"alternate\" href=\"{}\" />\n",
            item.link
        ));
        out.push_str(&format!(
            "    <updated>{}</updated>\n",
            atom_date(&item.updated)
        ));
        out.push_str(&format!(
            "    <published>{}</published>\n",
            atom_date(&item.published)
        ));
        out.push_str("    <author>\n");
        out.push_str(&format!("      <name>{}</name>\n", AUTHOR_NAME));
        out.push_str(&format!("      <email>{}</email>\n", AUTHOR_EMAIL));
        out.push_str("    </author>\n");
        out.push_str(&format!("    <id>{}</id>\n", item.tag_uri));
        out.push_str("    <content>\n");
        out.push_str(&format!("      <![CDATA[{}]]>\n", item.content));
        out.push_str("    </content>\n");
        out.push_str("  </entry>\n");
    }
    out.push_str("</feed>");
    out
}

pub async fn build_feed(pool: &PgPool, base_url: &str, dialect: Dialect) -> sqlx::Result<String> {
    let rows = db::posts_query(pool, 25, "Jacob Hall", &[1, 2, 3, 4, 10]).await?;
    let items: Vec<FeedItem> = rows.iter().map(to_item).collect();
    let newest = items
        .iter()
        .map(|i| i.updated)
        .max()
        .unwrap_or_else(|| chrono::NaiveDateTime::default());
    Ok(match dialect {
        Dialect::Rss => build_rss(base_url, &items, &newest),
        Dialect::Atom => build_atom(base_url, &items, &newest),
    })
}

#[cfg(test)]
mod tests {
    use super::*;

    // Expected slugs harvested from the live golden feed.
    #[test]
    fn slugify_matches_splitflap() {
        assert_eq!(
            slugify("🗺️ How I made the best map of William & Mary"),
            "how-i-made-the-best-map-of-william-mary"
        );
        assert_eq!(
            slugify("Google locked me out of my phone"),
            "google-locked-me-out-of-my-phone"
        );
        // A DB title containing literal "&amp;" produces the "-amp-" slug.
        assert_eq!(
            slugify("Connecting to W&amp;M eduroam WiFi on Linux"),
            "connecting-to-w-amp-m-eduroam-wifi-on-linux"
        );
        assert_eq!(slugify("87"), "87");
        assert_eq!(
            slugify("Horton Hears a Whostyle!"),
            "horton-hears-a-whostyle"
        );
    }

    #[test]
    fn rss_date_is_unpadded() {
        let t = chrono::NaiveDate::from_ymd_opt(2022, 8, 3)
            .unwrap()
            .and_hms_opt(6, 24, 51)
            .unwrap();
        assert_eq!(rss_date(&t), "Wed, 3 Aug 2022 06:24:51 +0000");
    }
}
