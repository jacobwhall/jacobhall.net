//! In-process background jobs — one tokio task per pipeline, no external
//! queue. The webmention receiver pings `AppState::notify` (verification)
//! and enqueue paths ping `AppState::send_notify` (sending) so work starts
//! immediately; otherwise the workers poll.

pub mod wm_send;
pub mod wm_verify;

use std::sync::Arc;

use crate::routes::AppState;

pub fn spawn(state: Arc<AppState>) {
    tokio::spawn(wm_verify::run(state.clone()));
    tokio::spawn(wm_send::run(state));
}
