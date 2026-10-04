//! Development-only browser live reload (cargo feature `dev-reload`).
//!
//! tower-livereload appends a small script to HTML pages; it holds an
//! event stream open and reloads the page when told to, or when it
//! reconnects after the server restarts. A file watcher on the docroot and
//! templates dir sends the reload, so edits to content, styles, and
//! templates show up immediately (all are read from disk per request when
//! this feature is on). Rust changes restart the server instead.
//!
//! Only requests whose Accept header mentions text/html get the script, so
//! curl (golden captures) and webmention fetchers see unmodified pages.

use std::path::{Path, PathBuf};
use std::time::Duration;

use axum::body::Body;
use axum::http::{header, Request};
use axum::Router;
use notify::{EventKind, RecursiveMode, Watcher};
use tower_livereload::{LiveReloadLayer, Reloader};

use crate::config::Config;

/// Top-level docroot directories never worth watching: the server crate (its
/// target/ is huge; templates are watched separately) and build output.
/// Dot-directories are skipped too — .git, and .dev-data, the compose
/// Postgres data dir (unreadable to us, and constantly written).
const SKIP: &[&str] = &["server", "target", "node_modules"];

fn wants_html(req: &Request<Body>) -> bool {
    req.headers()
        .get(header::ACCEPT)
        .and_then(|v| v.to_str().ok())
        .is_some_and(|v| v.contains("text/html"))
}

pub fn wrap(app: Router, cfg: &Config) -> Router {
    tracing::warn!("dev-reload enabled: injecting live-reload script into HTML pages");
    let layer = LiveReloadLayer::new()
        .request_predicate::<Body, fn(&Request<Body>) -> bool>(wants_html);
    spawn_watcher(layer.reloader(), cfg);
    app.layer(layer)
}

fn skipped(name: &str) -> bool {
    name.starts_with('.') || SKIP.contains(&name)
}

/// Editor scratch files (vim swap/backup/probe files, emacs lockfiles).
fn is_scratch(path: &Path) -> bool {
    let name = path.file_name().and_then(|n| n.to_str()).unwrap_or("");
    name.ends_with('~')
        || name.ends_with(".swp")
        || name.ends_with(".swx")
        || name.starts_with(".#")
        || name == "4913"
}

fn spawn_watcher(reloader: Reloader, cfg: &Config) {
    let docroot = std::fs::canonicalize(&cfg.docroot).unwrap_or_else(|_| cfg.docroot.clone());
    let templates = cfg.templates_dir.clone();
    let (tx, mut rx) = tokio::sync::mpsc::unbounded_channel::<PathBuf>();

    let mut watcher = match notify::recommended_watcher(move |res: notify::Result<notify::Event>| {
        let Ok(ev) = res else { return };
        if matches!(
            ev.kind,
            EventKind::Create(_) | EventKind::Modify(_) | EventKind::Remove(_)
        ) {
            for p in ev.paths {
                let _ = tx.send(p);
            }
        }
    }) {
        Ok(w) => w,
        Err(e) => {
            tracing::warn!("dev-reload: file watcher unavailable: {e}");
            return;
        }
    };

    // The docroot itself non-recursively (top-level files), then each
    // top-level directory recursively, minus the skipped ones.
    let mut watch = |path: &Path, mode| {
        if let Err(e) = watcher.watch(path, mode) {
            tracing::warn!("dev-reload: cannot watch {}: {e}", path.display());
        }
    };
    watch(&docroot, RecursiveMode::NonRecursive);
    if let Ok(entries) = std::fs::read_dir(&docroot) {
        for entry in entries.flatten() {
            let name = entry.file_name();
            if entry.path().is_dir() && !skipped(&name.to_string_lossy()) {
                watch(&entry.path(), RecursiveMode::Recursive);
            }
        }
    }
    watch(&templates, RecursiveMode::Recursive);

    tokio::spawn(async move {
        // Owning the watcher here keeps it alive for the life of the server.
        let mut watcher = watcher;
        while let Some(path) = rx.recv().await {
            // Editors save in bursts (temp file, rename, chmod); settle first.
            tokio::time::sleep(Duration::from_millis(100)).await;
            let mut paths = vec![path];
            while let Ok(p) = rx.try_recv() {
                paths.push(p);
            }
            for p in &paths {
                // A new top-level directory: start watching it too.
                if p.parent() == Some(docroot.as_path())
                    && p.is_dir()
                    && !p.file_name().is_some_and(|n| skipped(&n.to_string_lossy()))
                {
                    let _ = watcher.watch(p, RecursiveMode::Recursive);
                }
            }
            if let Some(p) = paths.iter().find(|p| !is_scratch(p)) {
                tracing::info!("dev-reload: {} changed, reloading browsers", p.display());
                reloader.reload();
            }
        }
    });
}
