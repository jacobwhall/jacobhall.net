# Schema notes (from schema-baseline.sql, pg 13.7)

Answers to the Phase 0 open questions, from the live schema dump.

## vposts ordering — RESOLVED, low risk

The `vposts` view ends in `ORDER BY entries.published_date DESC`. The Racket
`posts-query` (no ORDER BY of its own) therefore gets deterministic order from
the view. The Rust port keeps the SQL verbatim and inherits the same order.
Caveat: rows sharing the same `published_date` (second precision) have no
tiebreaker; if golden diffs ever flake on adjacent posts, add
`ORDER BY published_date DESC, post_id DESC` to the *view* (not the queries)
as a coordinated fix.

## post_id / permalink allocation — RESOLVED (matters for Micropub, Phase 5)

- `entries.post_id smallint NOT NULL DEFAULT nextval('newentries_post_id_seq')`
- `entries.permalink text NOT NULL` has a DB-side DEFAULT that concatenates
  `https://jacobhall.net/` + current UTC `YYYY/MM/DD/` + `lpad(currval('newentries_post_id_seq'), 6, '0')`
- So Micropub creates can `INSERT INTO entries (post_type, content, …) VALUES (…) RETURNING post_id, permalink`
  and let the DB allocate both. The permalink default depends on `currval` of the
  post_id sequence being set in the same session — true for a plain INSERT that
  uses the post_id default. Do NOT supply post_id explicitly or currval breaks.
- `post_id` is smallint (max 32767) — fine for 6-digit zero-padded IDs up to 032767.

## wm_log — richer than site.rkt suggests

```
source    varchar(1000)
target    varchar(1000)
time_sent timestamp without time zone DEFAULT CURRENT_TIMESTAMP
whostyle  varchar
source_mf jsonb
```

The Racket receiver only inserts (source, target). `whostyle` and `source_mf`
are filled in by the current *manual* moderation workflow. Phase 2's
`webmentions` table supersedes this pipeline; the legacy insert stays
byte-compatible (source, target only).

## Computed vposts columns — come free with the view

- `published_date_iso` = `to_char(published_date, 'YYYY-MM-DD"T"HH24:MI:SS"Z"')`
- `published_date_disp` = `to_char(published_date, 'Month DD, YYYY HH24:MI')`
  — note `Month` is blank-padded to 9 chars by Postgres, so values contain
  interior runs of spaces (e.g. `May      12, 2022 14:03`). Harmless in HTML,
  but the golden diff must be whitespace-insensitive OR exact — we query the
  same view, so we emit the same bytes either way.
- `updated_date` = COALESCE(updated_date, published_date)
- `author_h_card` COALESCEs to `https://jacobhall.net`; `whostyle` COALESCEs to
  `jacobhall-net`; `display_location` COALESCEs to `false`.

## display_location — simpler than the plan assumed

Because the view COALESCEs it, `display_location` is always a real boolean by
the time Racket sees it (never NULL/""). Render the 📍 line iff `true`.
Model as `bool` in Rust, not `Option<bool>`.

## Timestamps

`published_date` is `timestamp without time zone`, default
`date_trunc('second', timezone('utc', now()))` — i.e. stored as naive UTC.
The Racket `sql-timestamp->moment` treats missing tz as UTC (offset 0) for
feeds. Rust: read as `chrono::NaiveDateTime`, interpret as UTC.

## Other observations

- View is `vposts` (lowercase); Racket writes `vPosts` unquoted — identical
  identifier after case folding. Keep either spelling.
- `entries.published boolean DEFAULT true` — vposts filters `published = true`,
  so drafts are representable. Micropub post-status could map here later.
- `tags (post_id, tag)` table exists but nothing in site.rkt reads it.
- `oldentries` + its sequence are legacy; ignore.
- `entries.display_date`, `reply_to_author_photo`, `reply_to_content` exist
  (latter two even in the view) but no template consumes them today.
- No PK/unique on `entries.post_id` (!) — only oldentries had the unique
  constraint. The sequence keeps IDs unique in practice. Candidate cleanup in
  the (deferred) model-updates phase, NOT now.

## Live-capture findings (golden corpus, 2026-07-28)

- **Slash-less post URLs 301**: `/2021/03/13/000000` → 301 → `/2021/03/13/000000/`
  (dir-redirect dispatcher fires before the post-slug handler). Posts only render
  at trailing-slash URLs. DB note permalinks are slash-less, so every post link
  incurs a 301. Reproduce exactly.
- **Existing directories redirect 302** (Racket files dispatcher), everything
  else slash-less gets **301** (dir-redirect dispatcher).
- **Article-dir permalinks in the DB END WITH `/`** (e.g.
  `https://jacobhall.net/2022/08/mapping-william-mary/`) — the Racket
  `(string-trim … ".")` removes the trailing `/.` path component artifact,
  leaving the slash. The Rust permalink lookup must use `{BASE_URL}/{rel}/`.
- **Date parts are ignored** in post URLs: `/1999/01/02/000001/` renders post
  000001. Confirmed live.
- **`/1999/01/` returns 500** — the latent Racket crash (explode-path with <4
  segments on a nonexistent dir). Rust serves the 404 page instead (divergence).
- **`/.git/config` is served by the live site** (!). Rust blocks dot-segments
  (divergence, deliberate).
- `/kind` → 301 `/kind/`; `GET /webmention` → 301 `/webmention/`.
- Racket omits Content-Type for `.rkt` files and for directory `index.html`
  responses; tower-http will send proper types (accepted divergence).
- Feeds: both `application/rss+xml; charset=utf-8`. RSS pubDate day is
  NOT zero-padded (`Wed, 3 Aug 2022 …`). Tag URI: `tag:jacobhall.net,2022:blog.`
  + slug of the RAW DB title (lowercase, `[^\w]+` runs → `-`, ends trimmed);
  title fallback = unpadded post_id (`87`). Feed titles XML-escape the raw DB
  value (`w-amp-m` slug proves one DB title literally contains `&amp;`).
- `/auth/metadata` exact bytes (alphabetical keys):
  `{"authorization_endpoint":"https://indieauth.com/auth","code_challenge_methods_supported":["S256"],"issuer":"https://jacobhall.net","token_endpoint":"https://tokens.indieauth.com/token"}`
- **post_id binding**: 999999 in a URL exceeds smallint; bind SQL params as
  i32/i64 (Postgres promotes smallint = integer fine); never bind i16.
- 8 posts store `author_h_card = 'https://jacobhall.net/'` (trailing slash) —
  they intentionally(?) miss the exact-match `https://jacobhall.net` checks in
  post.txt. Exact string compare preserves this.

## types — full list (real export received 2026-07-28)

1=🖋️ ARTICLE/article, 2=📝 NOTE/note, 3=📷 PHOTO/photo, 4=🎥 VIDEO/video,
5=🔖 BOOKMARK/bookmark, 6=❤️ LIKE/like, 7=↩️ REPLY/reply, 8=🔄 REPOST/repost,
9=✉️ RSVP/rsvp, 10=📎 FILE/file, 11=📺 WATCHED/watch. **Type 12 does not
exist** despite appearing in site.rkt's /all type list (harmless: ANY() just
never matches it).

## Parity verification result (2026-07-28)

With the full data dump loaded (server/db/data.sql): **95/95 comparable golden
URLs pass; both feeds are BYTE-IDENTICAL to production** (beyond the c14n
target). Accepted divergences only: dot-segment guard, /about/extra,
/1999/01/, and the newer repo CSS. See server/tests/golden/compare.sh.
(After the Phase 2 404-status fix: 90 pass / 8 accepted.)

## Phase 2 verification (2026-07-28)

E2E on the scratch DB: receiver 202 → worker verified an mf2 fixture (reply,
author h-card, dt-published) → admin approve → entries row allocated by DB
defaults (post_id via sequence, permalink via the date+currval default) →
comment rendered threaded on the target page. Unit tests cover the mf2
extractor (6 fixtures).

**The production wm_log has a real backlog** (queued 2022–2024): the worker
verified mentions from tracydurnell.com (Kening Zhu h-card), and
iwebthings.joejenett.com; jacky.wtf sources now 404 (retry then fail);
dailywebthing/brid.gy/tracydurnell-blogroll sources no longer link to the
site → verify_failed (correct per spec — verification reflects current source
state). These await moderation at /admin/mentions after deploy.

Verification is scheme-tolerant (a target declared http:// matches an
https:// link) and trailing-slash-tolerant.

## Phase 3 verification (2026-07-28)

Endpoint discovery passes **all 22 webmention.rocks discovery tests**
(`cargo test discovery_webmention_rocks -- --ignored`), including Link-header
precedence, comma-in-URL headers, rel token lists, empty-href (endpoint = page
itself), and redirect resolution. Local E2E: admin form → e-content link
extraction → endpoint discovery → POST → failure recorded with backoff and
endpoint. Triggers: `jacobhall-net send-webmentions <url>` CLI (enqueues and
sends in one pass) and /admin/webmentions. Micropub (Phase 5) will enqueue
automatically on publish. The webmention.rocks Update/Delete tests need the
live deployment (their verifier must fetch the source page).
