-- Phase 3: outgoing webmention queue.
CREATE TABLE IF NOT EXISTS wm_outbox (
    id bigserial PRIMARY KEY,
    source_url text NOT NULL,
    target_url text NOT NULL,
    endpoint text,
    -- pending → sent | no_endpoint | failed
    status text NOT NULL DEFAULT 'pending',
    attempts int NOT NULL DEFAULT 0,
    next_attempt_at timestamptz,
    response_status int,
    response_body_snippet text,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    -- re-enqueueing a pair re-sends it (the spec encourages re-notifying
    -- after updates)
    UNIQUE (source_url, target_url)
);

CREATE INDEX IF NOT EXISTS wm_outbox_due_idx
    ON wm_outbox (status, next_attempt_at);
