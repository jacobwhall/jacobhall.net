use std::env;
use std::path::PathBuf;

#[derive(Debug, Clone)]
pub struct Config {
    pub database_url: String,
    pub bind_addr: String,
    pub docroot: PathBuf,
    pub base_url: String,
    pub templates_dir: PathBuf,
    /// Owner password for /admin; admin area is disabled when unset.
    pub admin_password: Option<String>,
    /// Key material for signed session cookies (>= 32 chars). When unset a
    /// random key is generated at boot (sessions won't survive restarts).
    pub cookie_key: Option<String>,
}

impl Config {
    pub fn from_env() -> Result<Self, String> {
        let database_url =
            env::var("DATABASE_URL").map_err(|_| "DATABASE_URL must be set".to_string())?;
        let bind_addr = env::var("BIND_ADDR").unwrap_or_else(|_| "127.0.0.1:8000".into());
        let docroot = PathBuf::from(env::var("DOCROOT").unwrap_or_else(|_| ".".into()));
        let base_url = env::var("BASE_URL")
            .unwrap_or_else(|_| "https://jacobhall.net".into())
            .trim_end_matches('/')
            .to_string();
        let templates_dir =
            PathBuf::from(env::var("TEMPLATES_DIR").unwrap_or_else(|_| "server/templates".into()));

        if !docroot.is_dir() {
            return Err(format!("DOCROOT {:?} is not a directory", docroot));
        }
        if !templates_dir.is_dir() {
            return Err(format!(
                "TEMPLATES_DIR {:?} is not a directory",
                templates_dir
            ));
        }
        let admin_password = env::var("ADMIN_PASSWORD").ok().filter(|s| !s.is_empty());
        let cookie_key = env::var("COOKIE_KEY").ok().filter(|s| !s.is_empty());
        if let Some(k) = &cookie_key {
            if k.len() < 32 {
                return Err("COOKIE_KEY must be at least 32 characters".into());
            }
        }
        Ok(Config {
            database_url,
            bind_addr,
            docroot,
            base_url,
            templates_dir,
            admin_password,
            cookie_key,
        })
    }
}
