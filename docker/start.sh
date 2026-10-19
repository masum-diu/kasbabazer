#!/bin/sh
# Container entrypoint: prepare Laravel, then run Apache on the port the host provides.
set -e

cd /var/www/html

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generate one with: php artisan key:generate --show" >&2
    exit 1
fi

# Render sets PORT (default 10000); Apache must listen on it.
PORT="${PORT:-10000}"
sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# A mounted persistent disk starts empty and root-owned.
mkdir -p storage/app/public/listings storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link --force || true
php artisan migrate --force

# Reference data the site needs (idempotent). Demo accounts are NOT created here.
php artisan db:seed --class=Database\\Seeders\\LocationSeeder --force
php artisan db:seed --class=Database\\Seeders\\CategorySeeder --force
php artisan db:seed --class=Database\\Seeders\\PlanSeeder --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Daily jobs (plan expiry) without a separate cron service.
php artisan schedule:work >> storage/logs/schedule.log 2>&1 &

exec apache2-foreground
