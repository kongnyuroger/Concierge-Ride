#!/bin/sh
set -e

php artisan config:cache
php artisan route:cache

exec frankenphp php-server --listen ":${PORT:-8080}" --root /app/public

