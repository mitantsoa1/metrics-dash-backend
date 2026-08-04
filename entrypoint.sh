#!/bin/sh
set -e

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan package:discover --ansi

php artisan config:clear
php artisan config:cache

# Pas de route:cache : routes/web.php declare des routes par closure, que Laravel
# refuse de serialiser.

php artisan migrate --force

exec "$@"
