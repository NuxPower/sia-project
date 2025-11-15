#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

export NODE_ENV="${NODE_ENV:-production}"

log() {
  printf '[%(%Y-%m-%dT%H:%M:%S%z)T] %s\n' -1 "$*" >&2
}

log "Bootstrapping KLEMA container"

if [ -z "${APP_KEY:-}" ]; then
  log "APP_KEY is not set. Please provide APP_KEY via Render environment variables."
  exit 1
fi

if [ ! -d "vendor" ]; then
  log "Vendor directory missing; installing composer dependencies"
  composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction
fi

if [ ! -d "node_modules" ]; then
  log "node_modules missing; installing frontend dependencies"
  npm ci
fi

if [ "${SKIP_VITE_BUILD:-0}" != "1" ]; then
  if [ ! -f "public/build/manifest.json" ] || [ "${FORCE_VITE_BUILD:-0}" = "1" ]; then
    log "Building frontend assets with Vite"
    npm run build
  else
    log "Existing Vite build detected; skipping rebuild"
  fi
else
  log "SKIP_VITE_BUILD=1 detected; skipping asset build"
fi

log "Ensuring storage symlink exists"
php artisan storage:link --force >/dev/null 2>&1 || true

log "Clearing and caching Laravel configuration"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-1}" != "0" ]; then
  log "Running database migrations (RUN_MIGRATIONS=${RUN_MIGRATIONS})"
  until php artisan migrate --force --no-interaction; do
    log "Migration failed (likely DB not ready). Retrying in 5 seconds..."
    sleep 5
  done
else
  log "RUN_MIGRATIONS=0, skipping php artisan migrate"
fi

log "Starting Laravel HTTP server on port ${PORT:-8000}"
exec php artisan serve --host 0.0.0.0 --port "${PORT:-8000}"

