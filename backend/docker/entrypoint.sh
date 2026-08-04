#!/bin/sh
set -e

php artisan config:cache
php artisan route:cache

# Render's free plan doesn't run pre-deploy commands, so migrations run here
# instead. Safe while numInstances=1; move to a proper release step before
# scaling past a single instance to avoid concurrent migration races.
php artisan migrate --force

exec frankenphp php-server --listen ":${PORT:-8080}" --root /app/public

