#!/usr/bin/env bash
set -euo pipefail

PREFIX="${CONTAINER_PREFIX:-digipulse}"

# Post-deploy on the app server (restarts backend stack after release activation).
if docker ps -a --format '{{.Names}}' | grep -q "${PREFIX}-app"; then
  docker pull ghcr.io/yurij2015/digipulse-backend

  # Restart app first so config:cache runs with the fresh .env
  docker restart "${PREFIX}-app"
  sleep 10
  docker exec "${PREFIX}-app" php artisan config:cache
  docker exec "${PREFIX}-app" php artisan route:cache
  docker exec "${PREFIX}-app" php artisan migrate --force
  docker exec "${PREFIX}-app" php artisan telescope:publish

  # Restart remaining containers AFTER config cache is written so they
  # boot with the correct bootstrap/cache/config.php (not a stale one)
  docker restart "${PREFIX}-worker" "${PREFIX}-scheduler" "${PREFIX}-results-consumer"
else
  echo "Containers with prefix '${PREFIX}' not found. Run terraform apply first."
  exit 1
fi
