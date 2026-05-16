#!/usr/bin/env bash
set -euo pipefail

# Post-deploy on the app server (restarts backend stack after release activation).
if docker ps -a --format '{{.Names}}' | grep -q 'digipulse-app'; then
  docker pull ghcr.io/yurij2015/digipulse-backend
  docker restart digipulse-app digipulse-worker digipulse-scheduler digipulse-results-consumer
  sleep 10
  docker exec digipulse-app php artisan config:cache
  docker exec digipulse-app php artisan route:cache
  docker exec digipulse-app php artisan migrate --force
  docker exec digipulse-app php artisan telescope:publish
else
  echo 'Containers not found. Run terraform apply first.'
  exit 1
fi
