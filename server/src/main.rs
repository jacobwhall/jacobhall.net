mod config;
mod db;
#[cfg(feature = "dev-reload")]
mod dev_reload;
mod feeds;
mod jobs;
mod mf2;
mod render;
mod routes;

use std::sync::Arc;

use axum::routing::{any, get, post};
use axum::Router;
use axum_extra::extract::cookie::Key;
use clap::{Parser, Subcommand};

use routes::AppState;

#[cfg(target_env = "musl")]
#[global_allocator]
static GLOBAL: mimalloc::MiMalloc = mimalloc::MiMalloc;

#[derive(Parser)]
#[command(name = "jacobhall-net", about = "jacobhall.net web server")]
struct Cli {
    #[command(subcommand)]
    command: Option<Command>,
}

#[derive(Subcommand)]
enum Command {
    /// Run the web server (default)
    Serve,
    /// Apply pending database migrations, then exit
    Migrate,
    /// Enqueue and send webmentions for every external link in a page's
    /// e-content, then exit
    SendWebmentions {
        /// Public URL of the page to send webmentions for
        source_url: String,
    },
}

fn build_router(state: Arc<AppState>) -> Router {
    Router::new()
        .route("/", get(routes::pages::homepage))
        .route("/all", get(routes::pages::all_posts))
        .route("/few", get(routes::pages::few_posts))
        .route("/many", get(routes::pages::many_posts))
        .route("/kind/", get(routes::pages::kind_empty))
        .route("/kind/{kind}", get(routes::pages::kind_page))
        .route("/webmention", any(routes::webmention::webmention))
        .route("/feeds/rss/v1.rss", get(routes::pages::rss_feed))
        .route("/feeds/atom/v1.atom", get(routes::pages::atom_feed))
        .route("/auth/{action}", get(routes::auth::auth_action))
        .route("/admin", get(routes::admin::index))
        .route("/admin/login", post(routes::admin::login))
        .route("/admin/mentions", get(routes::admin::mentions))
        .route("/admin/mentions/{id}/approve", post(routes::admin::approve))
        .route("/admin/mentions/{id}/reject", post(routes::admin::reject))
        .route("/admin/webmentions", get(routes::admin::outbox))
        .route("/admin/webmentions/send", post(routes::admin::outbox_send))
        .fallback(routes::fallback::fallback)
        .layer(tower_http::trace::TraceLayer::new_for_http())
        .with_state(state)
}

fn make_state(cfg: config::Config) -> Arc<AppState> {
    let pool = match sqlx::postgres::PgPoolOptions::new()
        .max_connections(5)
        .connect_lazy(&cfg.database_url)
    {
        Ok(p) => p,
        Err(e) => {
            eprintln!("invalid DATABASE_URL: {e}");
            std::process::exit(1);
        }
    };
    let key = match &cfg.cookie_key {
        Some(k) => {
            // Expand the configured secret to the 64 bytes Key::from expects.
            use sha2::{Digest, Sha512};
            Key::from(&Sha512::digest(k.as_bytes()))
        }
        None => {
            tracing::warn!("COOKIE_KEY not set — admin sessions won't survive restarts");
            Key::generate()
        }
    };
    let tmpl = render::Templates::new(cfg.templates_dir.clone());
    Arc::new(AppState {
        pool,
        cfg,
        tmpl,
        notify: tokio::sync::Notify::new(),
        send_notify: tokio::sync::Notify::new(),
        key,
    })
}

async fn serve() {
    let cfg = match config::Config::from_env() {
        Ok(c) => c,
        Err(e) => {
            eprintln!("config error: {e}");
            std::process::exit(1);
        }
    };
    let bind_addr = cfg.bind_addr.clone();
    let state = make_state(cfg);
    jobs::spawn(state.clone());
    #[cfg(feature = "dev-reload")]
    let app = dev_reload::wrap(build_router(state.clone()), &state.cfg);
    #[cfg(not(feature = "dev-reload"))]
    let app = build_router(state);

    let listener = tokio::net::TcpListener::bind(&bind_addr)
        .await
        .unwrap_or_else(|e| {
            eprintln!("could not bind {bind_addr}: {e}");
            std::process::exit(1);
        });
    tracing::info!("listening on {bind_addr}");
    let server = axum::serve(listener, app);
    // Live-reload event streams never end on their own, so graceful shutdown
    // would wait on them forever. Just stop; browsers reconnect to the next
    // server and reload.
    #[cfg(feature = "dev-reload")]
    tokio::select! {
        res = std::future::IntoFuture::into_future(server) => res.expect("server error"),
        _ = shutdown_signal() => {}
    }
    #[cfg(not(feature = "dev-reload"))]
    server
        .with_graceful_shutdown(shutdown_signal())
        .await
        .expect("server error");
}

async fn migrate() {
    let cfg = match config::Config::from_env() {
        Ok(c) => c,
        Err(e) => {
            eprintln!("config error: {e}");
            std::process::exit(1);
        }
    };
    let pool = sqlx::postgres::PgPoolOptions::new()
        .max_connections(1)
        .connect(&cfg.database_url)
        .await
        .unwrap_or_else(|e| {
            eprintln!("could not connect: {e}");
            std::process::exit(1);
        });
    match sqlx::migrate!("./migrations").run(&pool).await {
        Ok(()) => println!("migrations applied"),
        Err(e) => {
            eprintln!("migration failed: {e}");
            std::process::exit(1);
        }
    }
}

async fn shutdown_signal() {
    let ctrl_c = async {
        tokio::signal::ctrl_c().await.ok();
    };
    #[cfg(unix)]
    let terminate = async {
        tokio::signal::unix::signal(tokio::signal::unix::SignalKind::terminate())
            .expect("failed to install SIGTERM handler")
            .recv()
            .await;
    };
    #[cfg(not(unix))]
    let terminate = std::future::pending::<()>();
    tokio::select! {
        _ = ctrl_c => {},
        _ = terminate => {},
    }
}

#[tokio::main]
async fn main() {
    dotenvy::dotenv().ok();
    tracing_subscriber::fmt()
        .with_env_filter(
            tracing_subscriber::EnvFilter::try_from_default_env()
                .unwrap_or_else(|_| "info,tower_http=warn".into()),
        )
        .init();

    let cli = Cli::parse();
    match cli.command.unwrap_or(Command::Serve) {
        Command::Serve => serve().await,
        Command::Migrate => migrate().await,
        Command::SendWebmentions { source_url } => send_webmentions(source_url).await,
    }
}

async fn send_webmentions(source_url: String) {
    let cfg = match config::Config::from_env() {
        Ok(c) => c,
        Err(e) => {
            eprintln!("config error: {e}");
            std::process::exit(1);
        }
    };
    let pool = sqlx::postgres::PgPoolOptions::new()
        .max_connections(2)
        .connect(&cfg.database_url)
        .await
        .unwrap_or_else(|e| {
            eprintln!("could not connect: {e}");
            std::process::exit(1);
        });
    let client = jobs::wm_send::client().expect("HTTP client");
    match jobs::wm_send::enqueue_links_for(&pool, &client, &source_url).await {
        Ok(n) => println!("enqueued {n} target(s) from {source_url}"),
        Err(e) => {
            eprintln!("enqueue failed: {e}");
            std::process::exit(1);
        }
    }
    if let Err(e) = jobs::wm_send::process_due(&pool, &client).await {
        eprintln!("send pass failed: {e}");
        std::process::exit(1);
    }
    let rows = sqlx::query_as::<_, (String, String, Option<String>)>(
        "SELECT target_url, status, endpoint FROM wm_outbox WHERE source_url = $1 ORDER BY id",
    )
    .bind(&source_url)
    .fetch_all(&pool)
    .await
    .unwrap_or_default();
    for (target, status, endpoint) in rows {
        println!(
            "  {status}: {target}{}",
            endpoint
                .map(|e| format!(" (endpoint {e})"))
                .unwrap_or_default()
        );
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[tokio::test]
    async fn router_builds() {
        let cfg = config::Config {
            database_url: "postgres://localhost/none".into(),
            bind_addr: "127.0.0.1:0".into(),
            docroot: std::path::PathBuf::from(env!("CARGO_MANIFEST_DIR")).join(".."),
            base_url: "https://jacobhall.net".into(),
            templates_dir: std::path::PathBuf::from(env!("CARGO_MANIFEST_DIR")).join("templates"),
            admin_password: None,
            cookie_key: None,
        };
        let _ = build_router(make_state(cfg));
    }
}
