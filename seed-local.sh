#!/bin/bash
set -e

DOCKER=docker
if ! command -v "$DOCKER" >/dev/null 2>&1; then
    echo "Docker est introuvable dans le PATH." >&2
    exit 1
fi

services=$("$DOCKER" compose -f docker-compose.dev.yml ps --services --filter status=running)

if ! echo "$services" | grep -qx 'app'; then
    echo "Le service Docker app n'est pas démarré. Lancez d'abord la stack avec docker compose -f docker-compose.dev.yml up -d." >&2
    exit 1
fi

"$DOCKER" compose -f docker-compose.dev.yml exec app php artisan db:seed --force
