#!/bin/sh

mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache
# mkdir -p /etc/supervisor/conf.d \
#   /etc/supervisor.d \
#   /var/log/supervisor
# chown -R www-data:octane storage bootstrap/cache
# chmod -R ug+rwx storage bootstrap/cache

php artisan optimize:clear;
php artisan package:discover --ansi;
php artisan event:cache;
php artisan config:cache;
php artisan route:cache;

exec /usr/bin/supervisord -c docker/supervisor.conf