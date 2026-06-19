#!/usr/bin/env bash
set -e

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
  export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
fi

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  export DB_DATABASE="${DB_DATABASE:-/var/data/controlcash/database.sqlite}"
  mkdir -p storage/app storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs
  mkdir -p "$(dirname "${DB_DATABASE}")"
  touch "${DB_DATABASE}"
  chown -R www-data:www-data "$(dirname "${DB_DATABASE}")"
fi

chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link || true
php artisan migrate --force --no-interaction
php artisan config:cache
php artisan view:cache

exec apache2-foreground
