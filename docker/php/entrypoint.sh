#!/bin/sh
set -e

wait_for_mysql() {
    echo "Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
    until nc -z "${DB_HOST:-mysql}" "${DB_PORT:-3306}" 2>/dev/null; do
        sleep 1
    done
}

wait_for_mysql

if [ "$1" = "php-fpm" ]; then
    # storage/ and bootstrap/cache are bind-mounted from the host, so they're
    # owned by the host user's uid rather than www-data (uid 82, the php-fpm
    # worker user) — without this, Blade view compilation and log writes fail.
    chown -R www-data:www-data storage bootstrap/cache

    if [ ! -f .env ]; then
        cp .env.example .env
    fi

    if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
        composer install --no-interaction --prefer-dist --optimize-autoloader
    fi

    if ! grep -q "^APP_KEY=base64" .env 2>/dev/null; then
        php artisan key:generate --ansi --force
    fi

    php artisan migrate --force

    if [ ! -L public/storage ]; then
        php artisan storage:link
    fi
else
    # Other roles (e.g. the queue worker) just wait for the app container to
    # finish installing dependencies before starting their process.
    until [ -f vendor/autoload.php ]; do
        echo "Waiting for vendor/autoload.php..."
        sleep 1
    done
fi

exec "$@"
