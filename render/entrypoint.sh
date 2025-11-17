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

# Run migrations by default unless RUN_MIGRATIONS=0 is explicitly set
# Default behavior: migrations run automatically on deploy
if [ "${RUN_MIGRATIONS:-1}" != "0" ]; then
  log "Running database migrations (RUN_MIGRATIONS=${RUN_MIGRATIONS:-1})"
  until php artisan migrate --force --no-interaction; do
    log "Migration failed (likely DB not ready). Retrying in 5 seconds..."
    sleep 5
  done
  log "Database migrations completed successfully"
else
  log "RUN_MIGRATIONS=0, skipping php artisan migrate"
  log "WARNING: If this is first deploy or after adding new migrations, you must run migrations manually!"
fi

log "Starting queue worker in background (processing 'weather' and 'default' queues)"
# Process weather queue first (higher priority), then default queue
# Increased timeout to 300s for historical weather jobs that process 30 days of data
php artisan queue:work --queue=weather,default --tries=3 --timeout=300 --sleep=3 > /proc/1/fd/1 2>&1 &

log "Starting Laravel HTTP server on port ${PORT:-8000}"
exec php artisan serve --host 0.0.0.0 --port "${PORT:-8000}"

