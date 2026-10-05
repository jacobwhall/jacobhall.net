# Build from the repo root (the published image bakes in the site content):
#
#   docker build -f server/Dockerfile .
#
# Alpine's Rust targets musl and links statically by default, so the binary
# needs nothing from the runtime image (which is FROM scratch).
FROM rust:1-alpine AS builder
# aws-lc-sys (rustls' crypto provider, via reqwest) compiles C sources with
# cmake; perl is used by its assembly generation. musl-dev and gcc build it
# and mimalloc.
RUN apk add --no-cache cmake perl make gcc musl-dev
WORKDIR /build
COPY server/Cargo.toml server/Cargo.lock ./
COPY server/migrations migrations
COPY server/src src
# Production builds use the defaults. compose.yaml overrides both for local
# development (dev-container profile, dev-reload feature); never ship an
# image built with dev-reload.
ARG CARGO_PROFILE=release
ARG CARGO_FEATURES=""
# Cache mounts keep the registry and incremental build artifacts across
# image rebuilds; the binary is copied out because the cache is not a layer.
RUN --mount=type=cache,target=/usr/local/cargo/registry \
    --mount=type=cache,target=/build/target \
    cargo build --profile "$CARGO_PROFILE" --features "$CARGO_FEATURES" \
    && cp "target/$CARGO_PROFILE/jacobhall-net" /usr/local/bin/jacobhall-net

# The site content tree (year directories, images/, styles/, top-level/, …):
# the whole repo minus the server sources and dev tooling. .dockerignore
# keeps .git and local state out of the context in the first place.
FROM alpine AS content
COPY . /site
RUN rm -rf /site/server /site/compose.yaml

# Nothing but the static binary and the templates: no shell, libc, or CA
# store (TLS roots are compiled in via webpki-root-certs). Commands must use
# exec form. Runs as an unprivileged uid; scratch has no /etc/passwd, so it
# is numeric. compose.yaml builds this target and bind-mounts the working
# tree as the docroot instead of baking it in.
FROM scratch AS server
COPY --from=builder /usr/local/bin/jacobhall-net /usr/local/bin/jacobhall-net
COPY server/templates /app/templates
ENV TEMPLATES_DIR=/app/templates
USER 65532:65532
EXPOSE 8000
CMD ["/usr/local/bin/jacobhall-net", "serve"]

# Default target, and what CI publishes: the server plus the site content at
# DOCROOT, so a deployment needs no volume for it. Content is root-owned and
# read-only to the server's uid.
FROM server
COPY --from=content /site /site
ENV DOCROOT=/site
