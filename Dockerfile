# syntax=docker/dockerfile:1
#
# Production image for Absensi MTsN 2 Kota Malang (Laravel 12, PHP 8.4).
# One container runs nginx + php-fpm (via supervisord) on port 80.
# Made for Coolify (Build Pack: Dockerfile), works with any Docker host.
# See DEPLOY_COOLIFY.md.

# Exact PHP release, so a redeploy always gets this version instead of an
# older image cached on the server. 8.4.27 (21 Oct 2026) fixes the critical
# PHP2Shell RCE bugs CVE-2026-109618 / CVE-2026-109619. Raise this (and
# PHP_MIN_SAFE below) when a newer 8.4.x security release comes out.
ARG PHP_VERSION=8.4.27
ARG NODE_VERSION=22

############################################
# 1. PHP runtime with the needed extensions
############################################
FROM php:${PHP_VERSION}-fpm-alpine AS base

# pg_dump / psql are used by Admin > Backup. Their major version must be
# the same as or newer than the PostgreSQL server (Coolify default: 16/17).
ARG PG_CLIENT_VERSION=17
# Oldest PHP release the build accepts (stops a build on an unpatched image)
ARG PHP_MIN_SAFE=8.4.27

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN set -eux; \
    php -r 'exit(version_compare(PHP_VERSION, getenv("PHP_MIN_SAFE"), ">=") ? 0 : 1);' \
        || { echo "PHP $(php -r 'echo PHP_VERSION;') is older than ${PHP_MIN_SAFE} and has known security holes" >&2; exit 1; }; \
    apk add --no-cache \
        nginx \
        supervisor \
        curl \
        su-exec \
        tzdata \
        "postgresql${PG_CLIENT_VERSION}-client"; \
    install-php-extensions \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pdo_pgsql \
        pgsql \
        zip; \
    # Use our own php-fpm pool config only
    rm -f /usr/local/etc/php-fpm.d/*.conf; \
    mkdir -p /run/nginx /var/lib/nginx/tmp /var/log/supervisor

ENV TZ=Asia/Jakarta

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf

############################################
# 2. PHP dependencies (Composer)
############################################
FROM base AS deps
WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Install PHP dependencies first so this layer is cached between deploys
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
    && composer clear-cache

############################################
# 3. Front-end assets (Vite + Tailwind)
############################################
FROM node:${NODE_VERSION}-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

# Tailwind scans the Blade views and JS for class names, including
# Laravel's pagination views (see resources/css/app.css)
COPY vite.config.js ./
COPY resources ./resources
COPY --from=deps /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

############################################
# 4. Application
############################################
FROM deps AS app

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN set -eux; \
    # Folders the app needs (empty ones are not part of the build context)
    mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        public/storage \
        public/uploads/logos; \
    rm -f .env bootstrap/cache/*.php; \
    # Also runs `php artisan package:discover`
    composer dump-autoload --no-dev --optimize --no-interaction; \
    # Keep a copy of the files that ship with the app, so the entrypoint can
    # put them into a new, empty volume (the default school logos)
    mkdir -p /opt/app-defaults; \
    cp -a public/uploads /opt/app-defaults/uploads; \
    # Only these folders are writable by the web server; the code is read-only
    chown -R www-data:www-data storage bootstrap/cache public/storage public/uploads

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=120s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up > /dev/null || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
