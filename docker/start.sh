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

if [ -w /app/backend/.env ]; then
    php -r '
        $env = file_get_contents("/app/backend/.env");
        $env = preg_replace("/^APP_KEY=.*$/m", "APP_KEY=" . getenv("APP_KEY"), $env);
        file_put_contents("/app/backend/.env", $env);
    '
fi

touch "$data_dir/database.sqlite"
php artisan migrate --force

exec php artisan serve --host=0.0.0.0 --port=8000
