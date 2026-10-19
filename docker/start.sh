#!/bin/sh
# Container entrypoint: prepare Laravel, then run Apache on the port the host provides.
set -e

cd /var/www/html

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generate one with: php artisan key:generate --show" >&2
    exit 1
fi

# Data that must survive redeploys lives on the persistent disk (DATA_DIR): the SQLite file and uploaded photos.
DATA_DIR="${DATA_DIR:-/var/data}"

if [ ! -d "$DATA_DIR" ]; then
    echo "WARNING: $DATA_DIR does not exist. Attach a persistent disk mounted at $DATA_DIR," >&2
    echo "         otherwise the database and uploaded photos are lost on every deploy." >&2
    mkdir -p "$DATA_DIR"
fi

mkdir -p "$DATA_DIR/uploads"
export DB_CONNECTION="${DB_CONNECTION:-sqlite}"
export DB_DATABASE="${DB_DATABASE:-$DATA_DIR/database.sqlite}"
touch "$DB_DATABASE"

# Uploaded photos: storage/app/public -> persistent disk.
rm -rf storage/app/public
ln -s "$DATA_DIR/uploads" storage/app/public

chown -R www-data:www-data "$DATA_DIR"

# Render sets PORT (default 10000); Apache must listen on it.
PORT="${PORT:-10000}"
sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p "$DATA_DIR/uploads/listings" storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
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

# artisan ran as root: hand every file it created (SQLite -wal/-shm, caches) back to the web user.
chown -R www-data:www-data "$DATA_DIR" storage bootstrap/cache

# Daily jobs (plan expiry) without a separate cron service.
php artisan schedule:work >> storage/logs/schedule.log 2>&1 &

exec apache2-foreground
