//! Template rendering. minijinja with auto-escaping off everywhere
//!
//! In debug builds the environment is rebuilt per render so hand-edited
//! templates reload without recompiling. Release builds cache it.

use std::path::PathBuf;
use std::sync::OnceLock;

use minijinja::{context, path_loader, AutoEscape, Environment, Value};
use sqlx::PgPool;

use crate::db::{self, PostRow};

pub struct Templates {
    dir: PathBuf,
    #[allow(dead_code)]
    cached: OnceLock<Environment<'static>>,
}

impl Templates {
    pub fn new(dir: PathBuf) -> Self {
        Templates {
            dir,
            cached: OnceLock::new(),
        }
    }

    fn build_env(&self) -> Environment<'static> {
        let mut env = Environment::new();
        env.set_loader(path_loader(&self.dir));
        env.set_auto_escape_callback(|_| AutoEscape::None);
        env
    }

    pub fn render(&self, name: &str, ctx: Value) -> Result<String, minijinja::Error> {
        #[cfg(debug_assertions)]
        {
            self.build_env().get_template(name)?.render(ctx)
        }
        #[cfg(not(debug_assertions))]
        {
            self.cached
                .get_or_init(|| self.build_env())
                .get_template(name)?
                .render(ctx)
        }
    }
}

/// Mirror of Racket's row->ass: SQL NULL becomes "" for every string column.
fn post_ctx(row: &PostRow) -> Value {
    fn s(v: &Option<String>) -> String {
        v.clone().unwrap_or_default()
    }
    context! {
        type => row.type_,
        type_slug => row.type_slug.clone(),
        type_name => row.type_name.clone(),
        post_id => row.post_id,
        author => s(&row.author),
        post_title => s(&row.post_title),
        content_location => s(&row.content_location),
        bookmark_of => s(&row.bookmark_of),
        published_date_iso => s(&row.published_date_iso),
        published_date_disp => s(&row.published_date_disp),
        permalink => row.permalink.clone(),
        location => s(&row.location),
        display_location => row.display_location,
        author_h_card => row.author_h_card.clone(),
        author_photo => s(&row.author_photo),
        whostyle => row.whostyle.clone(),
        original_url => s(&row.original_url),
        reply_to_author => s(&row.reply_to_author),
        reply_to_author_h_card => s(&row.reply_to_author_h_card),
        reply_to_title => s(&row.reply_to_title),
        reply_to_url => s(&row.reply_to_url),
        content => s(&row.content),
        content_summary => s(&row.content_summary),
    }
}

pub fn render_post(tmpl: &Templates, row: &PostRow) -> Result<String, minijinja::Error> {
    tmpl.render("post.html", post_ctx(row))
}

/// Racket `posts`: fetch and concatenate rendered posts in document order.
pub async fn posts_html(
    pool: &PgPool,
    tmpl: &Templates,
    limit: i64,
    types: &[i32],
) -> Result<String, PageError> {
    let rows = db::posts_query(pool, limit, "Jacob Hall", types).await?;
    let mut out = String::new();
    for row in &rows {
        out.push_str(&render_post(tmpl, row)?);
    }
    Ok(out)
}

/// Racket `likes-query`'s HTML tail: facepile, or "" when no likes exist.
pub async fn likes_facepile(pool: &PgPool, post_id: i32) -> Result<String, PageError> {
    let likes = db::likes_query(pool, post_id).await?;
    let mut facepile = String::new();
    for like in &likes {
        facepile.push_str(&format!(
            "<a href=\"{}\"><img src=\"{}\" alt=\"Photo of {}\"></a>",
            like.original_url.clone().unwrap_or_default(),
            like.author_photo.clone().unwrap_or_default(),
            like.author.clone().unwrap_or_default(),
        ));
    }
    if facepile.is_empty() {
        Ok(String::new())
    } else {
        Ok(format!(
            "<h2>Likes</h2><div class=\"facepile\">{}</div>",
            facepile
        ))
    }
}

/// Racket `build-comments`: depth-first threaded replies. Recursion is boxed
/// (async) and depth-capped defensively — Racket had no cycle guard.
pub fn comments_thread<'a>(
    pool: &'a PgPool,
    tmpl: &'a Templates,
    post_id: i32,
    depth: u32,
) -> std::pin::Pin<Box<dyn std::future::Future<Output = Result<String, PageError>> + Send + 'a>> {
    Box::pin(async move {
        if depth > 50 {
            return Ok(String::new());
        }
        let replies = db::replies_query(pool, post_id).await?;
        let mut out = String::new();
        for reply in &replies {
            out.push_str(&render_post(tmpl, reply)?);
            out.push_str(&comments_thread(pool, tmpl, reply.post_id as i32, depth + 1).await?);
        }
        Ok(out)
    })
}

pub struct ArticlePage<'a> {
    pub title: &'a str,
    pub content: &'a str,
    pub article_tags: bool,
    pub insert_title: bool,
    /// When present, the likes facepile + comments block render (always,
    /// even with zero comments — Racket behavior).
    pub post_id: Option<i32>,
}

pub async fn article_page(
    pool: &PgPool,
    tmpl: &Templates,
    page: ArticlePage<'_>,
) -> Result<String, PageError> {
    let (likes_html, comments_html) = match page.post_id {
        Some(id) => (
            likes_facepile(pool, id).await?,
            comments_thread(pool, tmpl, id, 0).await?,
        ),
        None => (String::new(), String::new()),
    };
    Ok(tmpl.render(
        "article.html",
        context! {
            title => page.title,
            content => page.content,
            article_tags => page.article_tags,
            insert_title => page.insert_title,
            has_post_id => page.post_id.is_some(),
            likes_html => likes_html,
            comments_html => comments_html,
        },
    )?)
}

pub async fn homepage_html(pool: &PgPool, tmpl: &Templates) -> Result<String, PageError> {
    let recent_posts = posts_html(pool, tmpl, 3, &[1, 2, 3, 7, 8, 9, 10]).await?;
    let article_rows = db::posts_query(pool, 3, "Jacob Hall", &[1]).await?;
    let articles: Vec<Value> = article_rows
        .iter()
        .map(|r| {
            context! {
                permalink => r.permalink.clone(),
                post_title => r.post_title.clone().unwrap_or_default(),
            }
        })
        .collect();
    Ok(tmpl.render(
        "index.html",
        context! {
            recent_posts => recent_posts,
            articles => articles,
        },
    )?)
}

/// Unified error for page building; handlers convert it to a 500.
#[derive(Debug)]
pub enum PageError {
    Db(sqlx::Error),
    Template(minijinja::Error),
}

impl From<sqlx::Error> for PageError {
    fn from(e: sqlx::Error) -> Self {
        PageError::Db(e)
    }
}

impl From<minijinja::Error> for PageError {
    fn from(e: minijinja::Error) -> Self {
        PageError::Template(e)
    }
}

impl std::fmt::Display for PageError {
    fn fmt(&self, f: &mut std::fmt::Formatter<'_>) -> std::fmt::Result {
        match self {
            PageError::Db(e) => write!(f, "database error: {e}"),
            PageError::Template(e) => write!(f, "template error: {e}"),
        }
    }
}
