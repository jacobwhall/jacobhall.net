# Alpine's Rust targets musl and links statically by default, so the binary
# needs nothing from the runtime image (which is FROM scratch).
FROM rust:1-alpine AS builder
# aws-lc-sys (rustls' crypto provider, via reqwest) compiles C sources with
# cmake; perl is used by its assembly generation. musl-dev and gcc build it
# and mimalloc.
RUN apk add --no-cache cmake perl make gcc musl-dev
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

# Nothing but the static binary and the fallback templates: no shell, libc,
# or CA store (TLS roots are compiled in via webpki-root-certs). Commands must
# use exec form. Runs as an unprivileged uid; scratch has no /etc/passwd, so
# it is numeric.
FROM scratch
COPY --from=builder /usr/local/bin/jacobhall-net /usr/local/bin/jacobhall-net
# Templates are baked in as a fallback; deployments normally mount the site
# tree and point TEMPLATES_DIR/DOCROOT at it (see compose.yaml).
COPY templates /app/templates
ENV TEMPLATES_DIR=/app/templates
USER 65532:65532
EXPOSE 8000
CMD ["/usr/local/bin/jacobhall-net", "serve"]
