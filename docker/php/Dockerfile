# ─── Stage 1: Composer dependencies ────────────────────────────────────────
FROM composer:2.7 AS composer-deps
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# ─── Stage 2: Shared PHP base ───────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS base

ARG UID=1000
ARG GID=1000

WORKDIR /var/www/html

RUN apk add --no-cache \
        linux-headers \
        librdkafka-dev \
        supervisor \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql pcntl \
    && pecl install rdkafka redis \
    && docker-php-ext-enable rdkafka redis \
    && apk del .build-deps

COPY --from=composer-deps /app /var/www/html
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

RUN addgroup -g ${GID} -S appgroup \
    && adduser -u ${UID} -S appuser -G appgroup

RUN chown -R appuser:appgroup /var/www/html

USER appuser

# ─── Stage 3: API service (PHP-FPM) ─────────────────────────────────────────
FROM base AS api
EXPOSE 9000
CMD ["php-fpm"]

# ─── Stage 4: WebSocket service (Reverb) ────────────────────────────────────
FROM base AS reverb
EXPOSE 8080
CMD ["php", "artisan", "reverb:start", "--host=0.0.0.0", "--port=8080", "--no-interaction"]

# ─── Stage 5: Kafka consumer ─────────────────────────────────────────────────
FROM base AS consumer
COPY docker/supervisor/kafka-consumer.conf /etc/supervisor/conf.d/
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]

# ─── Stage 6: Local dev (includes dev dependencies + Xdebug) ─────────────────
FROM base AS local
USER root

# Add build dependencies for xdebug, compile it, then clean up
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer
RUN composer install --no-scripts

USER appuser
CMD ["php-fpm"]
