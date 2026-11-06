FROM debian:bookworm-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends g++ python3 coreutils \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /input
