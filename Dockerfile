# =============================================================================
# Image de base : FrankenPHP (Caddy + PHP embarqué)
# =============================================================================
FROM dunglas/frankenphp:1-php8.4 AS base

RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    intl \
    opcache \
    zip

# =============================================================================
# STAGE 1: Builder - dépendances et compilation des assets
# =============================================================================
FROM base AS builder

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV APP_ENV=prod

# Copié en premier pour que le cache Docker survive aux changements de code
COPY composer.json composer.lock symfony.lock ./

RUN composer install \
    --no-scripts \
    --no-dev \
    --optimize-autoloader \
    --classmap-authoritative \
    && composer clear-cache

COPY . .

COPY .env.prod .env

RUN composer dump-autoload --optimize --classmap-authoritative \
    && php bin/console importmap:install \
    && php bin/console asset-map:compile \
    && php bin/console cache:warmup \
    && rm -rf var/log/* /tmp/* /root/.composer/cache

# =============================================================================
# STAGE 2: Runtime
# =============================================================================
FROM base AS runtime

WORKDIR /app

ENV APP_ENV=prod \
    APP_DEBUG=0

COPY docker/php-prod.ini /usr/local/etc/php/conf.d/zzz-prod.ini
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

COPY --from=builder /app /app

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
