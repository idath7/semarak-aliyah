# ==============================================================================
# STAGE 1: ASSET BUILDER (NODE.JS 22 LATEST + VITE)
# ==============================================================================
FROM node:22-alpine AS asset-builder

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

ARG PHP_VERSION=8.4.26
ARG FRANKENPHP_VERSION=latest

# ==============================================================================
# STAGE 2: PRODUCTION RUNTIME (FRANKENPHP LATEST + PHP 8.4 ALPINE + LARAVEL OCTANE)
# ==============================================================================
FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php8.4-alpine

LABEL maintainer="SEMARAK ALIYAH XIV"
LABEL php.version="8.4.26"

ENV PHP_VERSION=8.4.26

WORKDIR /var/www/html

# 1. Install system utilities
RUN apk add --no-cache \
    curl \
    zip \
    unzip \
    bash \
    postgresql-client \
    linux-headers

# 2. Install PHP 8.4 Extensions via official helper:
#    - pdo_pgsql, pdo_mysql : Database drivers
#    - gd, imagick          : Image processing (ImageMagick)
#    - zip, xml, bcmath     : Data export, parsing, precision math
#    - pcntl, opcache       : Process control for Octane & JIT compilation
#    - redis                : High-performance in-memory cache, session & queue
#    - intl                 : Internationalization and locale formatting
RUN install-php-extensions \
    @composer \
    pdo_pgsql \
    pdo_mysql \
    gd \
    imagick \
    zip \
    xml \
    bcmath \
    pcntl \
    opcache \
    redis \
    intl

# 3. Optimization environment settings
#    - MAGICK_THREAD_LIMIT=1 prevents thread collision between ImageMagick OpenMP and FrankenPHP goroutines
#    - OCTANE_SERVER=frankenphp sets Laravel Octane default runtime
ENV MAGICK_THREAD_LIMIT=1 \
    OCTANE_SERVER=frankenphp \
    FRANKENPHP_CONFIG="worker /var/www/html/public/frankenphp-worker.php"

# 4. Copy custom PHP 8.4 Performance Optimization
COPY docker/php/custom-optimasi.ini /usr/local/etc/php/conf.d/custom-optimasi.ini

# 5. Copy custom FrankenPHP / Caddy configuration
COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

# 6. Copy application code
COPY . /var/www/html

# 7. Copy pre-compiled Vite assets from Stage 1
COPY --from=asset-builder /app/public/build /var/www/html/public/build

# 8. Install PHP dependencies via Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 9. Create necessary directories and set correct permissions
RUN mkdir -p /var/www/html/storage/framework/cache/data \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/app/public \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 10. Copy and configure entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose ports:
# 8000: Web Publik (Landing & Pendaftaran)
# 8001: Portal Admin & Operasional
# 8085: Laravel Reverb WebSocket Proxy
# 2019: FrankenPHP Admin/Metrics API
EXPOSE 8000 8001 8085 2019

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000", "--admin-port=2019", "--caddyfile=/etc/caddy/Caddyfile"]
