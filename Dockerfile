FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app/backend
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-progress --prefer-dist --no-interaction

COPY backend/ ./
COPY frontend/ /app/frontend/
COPY docker/start.sh /usr/local/bin/multitena-start

RUN cp .env.example .env \
    && sed -i 's/\r$//' /usr/local/bin/multitena-start \
    && composer dump-autoload --no-dev --optimize \
    && chmod +x /usr/local/bin/multitena-start \
    && mkdir -p /app/bd \
    && chown -R www-data:www-data /app/bd /app/backend/storage /app/backend/bootstrap/cache /app/backend/.env

USER www-data
EXPOSE 8000
ENTRYPOINT ["/usr/local/bin/multitena-start"]
