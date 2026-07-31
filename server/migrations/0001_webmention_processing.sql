-- Phase 2: webmention processing pipeline.
-- Invariant: only ADDS tables/columns — never alters anything site.rkt reads,
-- so the Racket server remains runnable for rollback.

-- The legacy queue keeps receiving inserts (source, target); the worker marks
-- rows it has picked up.
ALTER TABLE wm_log ADD COLUMN IF NOT EXISTS processed_at timestamptz;

-- One row per (source, target) pair; re-sent webmentions update in place
-- (per the Webmention spec, a re-notification supersedes the previous one).
CREATE TABLE IF NOT EXISTS webmentions (
    id bigserial PRIMARY KEY,
    source text NOT NULL,
    target text NOT NULL,
    -- pending → verified | verify_failed | approved | rejected | spam
    status text NOT NULL DEFAULT 'pending',
    -- like | reply | repost | bookmark | mention (from mf2 classification)
    mf2_kind text,
    author_name text,
    author_url text,
    author_photo text,
    content_html text,
    content_text text,
    published_at timestamp without time zone,
    http_status int,
    attempts int NOT NULL DEFAULT 0,
    next_attempt_at timestamptz,
    error text,
    -- vposts.post_id of the target page, when the target maps to a DB post
    target_post_id int,
    -- entries.post_id created on approval
    created_post_id int,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    UNIQUE (source, target)
);

CREATE INDEX IF NOT EXISTS webmentions_due_idx
    ON webmentions (status, next_attempt_at);
