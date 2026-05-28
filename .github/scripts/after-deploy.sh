#!/usr/bin/env bash
set -euo pipefail

PREFIX="${CONTAINER_PREFIX:-digipulse}"

# Post-deploy on the app server (restarts backend stack after release activation).
if docker ps -a --format '{{.Names}}' | grep -q "${PREFIX}-app"; then
  docker pull ghcr.io/yurij2015/digipulse-backend
  docker restart "${PREFIX}-app" "${PREFIX}-worker" "${PREFIX}-scheduler" "${PREFIX}-results-consumer"
  sleep 10
  docker exec "${PREFIX}-app" php artisan config:cache
  docker exec "${PREFIX}-app" php artisan route:cache
  docker exec "${PREFIX}-app" php artisan migrate --force
  docker exec "${PREFIX}-app" php artisan telescope:publish
else
  echo "Containers with prefix '${PREFIX}' not found. Run terraform apply first."
  exit 1
fi
