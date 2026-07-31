# Builder and runtime must share a Debian release (glibc compatibility).
FROM rust:1-slim-trixie AS builder
# aws-lc-sys (rustls' crypto provider, via sqlx/reqwest) compiles C sources
# with cmake; perl is used by its assembly generation.
RUN apt-get update \
    && apt-get install -y --no-install-recommends cmake perl \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /build
COPY Cargo.toml Cargo.lock ./
COPY migrations migrations
COPY src src
# Cache mounts keep the registry and incremental build artifacts across
# image rebuilds; the binary is copied out because the cache is not a layer.
RUN --mount=type=cache,target=/usr/local/cargo/registry \
    --mount=type=cache,target=/build/target \
    cargo build --release \
    && cp target/release/jacobhall-net /usr/local/bin/jacobhall-net

FROM debian:trixie-slim
# CA roots so the webmention workers can fetch https sources/targets.
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && useradd --system --uid 10001 --create-home site
COPY --from=builder /usr/local/bin/jacobhall-net /usr/local/bin/jacobhall-net
# Templates are baked in as a fallback; deployments normally mount the site
# tree and point TEMPLATES_DIR/DOCROOT at it (see compose.yaml).
COPY templates /app/templates
ENV TEMPLATES_DIR=/app/templates
USER site
WORKDIR /home/site
EXPOSE 8000
CMD ["jacobhall-net", "serve"]
