//! Minimal microformats2 extraction — exactly what the webmention pipeline
//! needs: find the h-entry that references the target, classify the mention,
//! and pull author h-card / e-content / dt-published.
//!
//! Decision (documented in the plan): this purpose-built extractor ships
//! behind the `Mf2Source` interface instead of the community `microformats`
//! crate. If the crate matures, it can be slotted in behind the same
//! interface and compared on the fixture corpus in `tests`.

use scraper::{ElementRef, Html, Selector};
use url::Url;

#[derive(Debug, Clone, Copy, PartialEq, Eq)]
pub enum Mf2Kind {
    Like,
    Reply,
    Repost,
    Bookmark,
    Mention,
}

impl Mf2Kind {
    pub fn as_str(&self) -> &'static str {
        match self {
            Mf2Kind::Like => "like",
            Mf2Kind::Reply => "reply",
            Mf2Kind::Repost => "repost",
            Mf2Kind::Bookmark => "bookmark",
            Mf2Kind::Mention => "mention",
        }
    }

    /// Default entries.post_type for an approved mention of this kind.
    /// Plain mentions default to reply — the type the manual workflow used.
    pub fn default_post_type(&self) -> i16 {
        match self {
            Mf2Kind::Like => 6,
            Mf2Kind::Reply => 7,
            Mf2Kind::Repost => 8,
            Mf2Kind::Bookmark => 5,
            Mf2Kind::Mention => 7,
        }
    }
}

#[derive(Debug, Clone, Default)]
pub struct Mf2Mention {
    pub kind: Option<Mf2Kind>,
    pub author_name: Option<String>,
    pub author_url: Option<String>,
    pub author_photo: Option<String>,
    pub content_html: Option<String>,
    pub content_text: Option<String>,
    pub published: Option<String>,
}

fn sel(s: &str) -> Selector {
    Selector::parse(s).expect("static selector")
}

/// Resolve a possibly-relative href against the source page URL.
fn resolve(base: &Url, href: &str) -> String {
    base.join(href)
        .map(|u| u.to_string())
        .unwrap_or_else(|_| href.to_string())
}

/// Two URLs refer to the same page if they differ at most by a trailing slash.
fn url_matches(candidate: &str, target: &str) -> bool {
    let c = candidate.trim_end_matches('/');
    let t = target.trim_end_matches('/');
    c == t
}

fn subtree_links_to(el: ElementRef, base: &Url, target: &str) -> bool {
    let a = sel("a[href]");
    el.select(&a).any(|link| {
        url_matches(
            &resolve(base, link.value().attr("href").unwrap_or("")),
            target,
        )
    })
}

fn classify(entry: ElementRef, base: &Url, target: &str) -> Mf2Kind {
    let checks: [(&str, Mf2Kind); 4] = [
        ("a.u-like-of[href], .u-like-of a[href]", Mf2Kind::Like),
        (
            "a.u-in-reply-to[href], .u-in-reply-to a[href], a.in-reply-to[href]",
            Mf2Kind::Reply,
        ),
        ("a.u-repost-of[href], .u-repost-of a[href]", Mf2Kind::Repost),
        (
            "a.u-bookmark-of[href], .u-bookmark-of a[href]",
            Mf2Kind::Bookmark,
        ),
    ];
    for (selector, kind) in checks {
        let s = sel(selector);
        if entry
            .select(&s)
            .any(|a| url_matches(&resolve(base, a.value().attr("href").unwrap_or("")), target))
        {
            return kind;
        }
    }
    Mf2Kind::Mention
}

fn extract_author(scope: ElementRef, base: &Url, out: &mut Mf2Mention) {
    let author_sel = sel(".p-author");
    let Some(author) = scope.select(&author_sel).next() else {
        return;
    };
    // h-card author: pull structured properties; else the element text is
    // the name and its href (if a link) the URL.
    let name_sel = sel(".p-name");
    let url_sel = sel("a.u-url[href]");
    let photo_sel = sel("img.u-photo[src], .u-photo img[src]");

    let is_h_card = author
        .value()
        .attr("class")
        .map(|c| c.split_whitespace().any(|c| c == "h-card"))
        .unwrap_or(false);

    if is_h_card {
        out.author_name = author
            .select(&name_sel)
            .next()
            .map(|e| e.text().collect::<String>().trim().to_string())
            .filter(|s| !s.is_empty());
        out.author_url = author
            .select(&url_sel)
            .next()
            .and_then(|e| e.value().attr("href"))
            .map(|h| resolve(base, h));
        out.author_photo = author
            .select(&photo_sel)
            .next()
            .and_then(|e| e.value().attr("src"))
            .map(|s| resolve(base, s));
    }
    if out.author_name.is_none() {
        let text = author.text().collect::<String>().trim().to_string();
        if !text.is_empty() {
            out.author_name = Some(text);
        }
    }
    if out.author_url.is_none() {
        if author.value().name() == "a" {
            out.author_url = author.value().attr("href").map(|h| resolve(base, h));
        }
    }
    if out.author_photo.is_none() {
        if let Some(img) = author.select(&sel("img[src]")).next() {
            out.author_photo = img.value().attr("src").map(|s| resolve(base, s));
        }
    }
}

/// Extract the mention data from a source page's HTML.
///
/// `source_url` is the page's URL (for resolving relative links);
/// `target_url` is the page on this site being mentioned.
pub fn extract(html: &str, source_url: &Url, target_url: &str) -> Mf2Mention {
    let doc = Html::parse_document(html);
    let entry_sel = sel(".h-entry");

    // Prefer the h-entry that links to the target; else the first h-entry.
    let entries: Vec<ElementRef> = doc.select(&entry_sel).collect();
    let entry = entries
        .iter()
        .copied()
        .find(|e| subtree_links_to(*e, source_url, target_url))
        .or_else(|| entries.first().copied());

    let mut out = Mf2Mention::default();
    let Some(entry) = entry else {
        // No microformats at all: a plain mention with no metadata.
        out.kind = Some(Mf2Kind::Mention);
        return out;
    };

    out.kind = Some(classify(entry, source_url, target_url));

    extract_author(entry, source_url, &mut out);
    if out.author_name.is_none() {
        // Fall back to a page-level h-card.
        if let Some(card) = doc.select(&sel(".h-card")).next() {
            extract_author_from_card(card, source_url, &mut out);
        }
    }

    if let Some(content) = entry.select(&sel(".e-content")).next() {
        out.content_html = Some(content.inner_html().trim().to_string());
        let text = content.text().collect::<String>().trim().to_string();
        out.content_text = Some(text);
    }

    out.published = entry
        .select(&sel(".dt-published"))
        .next()
        .map(|e| {
            e.value()
                .attr("datetime")
                .map(str::to_string)
                .unwrap_or_else(|| e.text().collect::<String>().trim().to_string())
        })
        .filter(|s| !s.is_empty());

    out
}

fn extract_author_from_card(card: ElementRef, base: &Url, out: &mut Mf2Mention) {
    out.author_name = card
        .select(&sel(".p-name"))
        .next()
        .map(|e| e.text().collect::<String>().trim().to_string())
        .filter(|s| !s.is_empty())
        .or_else(|| {
            let t = card.text().collect::<String>().trim().to_string();
            (!t.is_empty()).then_some(t)
        });
    out.author_url = card
        .select(&sel("a.u-url[href]"))
        .next()
        .and_then(|e| e.value().attr("href"))
        .map(|h| resolve(base, h))
        .or_else(|| {
            (card.value().name() == "a")
                .then(|| card.value().attr("href").map(|h| resolve(base, h)))
                .flatten()
        });
    out.author_photo = card
        .select(&sel("img.u-photo[src], .u-photo img[src], img[src]"))
        .next()
        .and_then(|e| e.value().attr("src"))
        .map(|s| resolve(base, s));
}

#[cfg(test)]
mod tests {
    use super::*;

    fn base() -> Url {
        Url::parse("https://example.com/posts/1").unwrap()
    }
    const TARGET: &str = "https://jacobhall.net/2021/08/horton-hears-a-whostyle";

    #[test]
    fn classifies_like() {
        let html = r#"<div class="h-entry">
            <a class="u-like-of" href="https://jacobhall.net/2021/08/horton-hears-a-whostyle/">nice</a>
            <a class="p-author h-card" href="/me"><img class="u-photo" src="/me.jpg"><span class="p-name">Ann Example</span></a>
        </div>"#;
        let m = extract(html, &base(), TARGET);
        assert_eq!(m.kind, Some(Mf2Kind::Like));
        assert_eq!(m.author_name.as_deref(), Some("Ann Example"));
        assert_eq!(m.author_url.as_deref(), Some("https://example.com/me"));
        assert_eq!(
            m.author_photo.as_deref(),
            Some("https://example.com/me.jpg")
        );
    }

    #[test]
    fn classifies_reply_with_content() {
        let html = r#"<article class="h-entry">
            <p>In reply to <a class="u-in-reply-to" href="https://jacobhall.net/2021/08/horton-hears-a-whostyle">this</a></p>
            <div class="e-content"><p>Great <b>post</b>!</p></div>
            <time class="dt-published" datetime="2021-08-12T16:09:44Z">August 12</time>
        </article>"#;
        let m = extract(html, &base(), TARGET);
        assert_eq!(m.kind, Some(Mf2Kind::Reply));
        assert_eq!(m.content_html.as_deref(), Some("<p>Great <b>post</b>!</p>"));
        assert_eq!(m.content_text.as_deref(), Some("Great post!"));
        assert_eq!(m.published.as_deref(), Some("2021-08-12T16:09:44Z"));
    }

    #[test]
    fn plain_link_is_mention() {
        let html = r#"<div class="h-entry"><div class="e-content">
            I read <a href="https://jacobhall.net/2021/08/horton-hears-a-whostyle">something</a> today.
        </div></div>"#;
        let m = extract(html, &base(), TARGET);
        assert_eq!(m.kind, Some(Mf2Kind::Mention));
    }

    #[test]
    fn no_mf2_at_all() {
        let html = r#"<p>A page with <a href="https://jacobhall.net/2021/08/horton-hears-a-whostyle">a link</a> only.</p>"#;
        let m = extract(html, &base(), TARGET);
        assert_eq!(m.kind, Some(Mf2Kind::Mention));
        assert!(m.author_name.is_none());
    }

    #[test]
    fn picks_the_entry_linking_to_target() {
        let html = r#"
        <div class="h-entry"><div class="e-content">unrelated</div></div>
        <div class="h-entry">
            <a class="u-repost-of" href="https://jacobhall.net/2021/08/horton-hears-a-whostyle">repost</a>
            <div class="e-content">the right one</div>
        </div>"#;
        let m = extract(html, &base(), TARGET);
        assert_eq!(m.kind, Some(Mf2Kind::Repost));
        assert_eq!(m.content_text.as_deref(), Some("the right one"));
    }

    #[test]
    fn bookmark_with_page_level_author() {
        let html = r#"
        <a class="h-card u-url" href="/about"><img src="/pic.png">Site Owner</a>
        <div class="h-entry">
            <a class="u-bookmark-of" href="https://jacobhall.net/2021/08/horton-hears-a-whostyle/">saved</a>
        </div>"#;
        let m = extract(html, &base(), TARGET);
        assert_eq!(m.kind, Some(Mf2Kind::Bookmark));
        assert_eq!(m.author_name.as_deref(), Some("Site Owner"));
    }
}
