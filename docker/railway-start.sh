#!/bin/sh
set -eu

data_dir=/app/bd
key_file="$data_dir/app-key"
if [ -z "${APP_KEY:-}" ]; then
    if [ ! -s "$key_file" ]; then
        umask 077
        php -r 'echo "base64:", base64_encode(random_bytes(32));' > "$key_file"
    fi
    APP_KEY=$(cat "$key_file")
    export APP_KEY
fi

touch "$data_dir/database.sqlite"
mkdir -p "$data_dir/files" "$data_dir/backups"
chown -R www-data:www-data "$data_dir" /app/backend/storage /app/backend/bootstrap/cache
php artisan migrate --force
php artisan config:cache
chown -R www-data:www-data /app/backend/bootstrap/cache
a2dismod -f mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod -f mpm_prefork >/dev/null
if ! apache2ctl -t; then
    ls -l /etc/apache2/mods-enabled/*mpm* >&2 || true
    grep -R 'LoadModule mpm_' /etc/apache2 >&2 || true
    exit 1
fi
php artisan app:bootstrap-admin
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/multitena.conf
