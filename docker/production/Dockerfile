# syntax=docker/dockerfile:1
#
# Dockerfile produksi SILKABK (Laravel + Inertia + Vue).
# Satu file, dua target image:
#   - backend  : PHP-FPM yang menjalankan Laravel   (docker build --target backend)
#   - frontend : Nginx yang menyajikan hasil build Vue dan meneruskan request PHP
#                ke container backend                (docker build --target frontend)
# Build context = root repo:  docker build -f docker/production/Dockerfile --target backend .

############################
# 1. Dependensi PHP (Composer)
############################
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
        --no-interaction --no-progress --ignore-platform-reqs
COPY . .
# File dump database hanya untuk image db, jangan ikut ke image aplikasi
RUN rm -f ./*.sql ./*.backup ./*.dump     && composer dump-autoload --no-dev --optimize --no-scripts

############################
# 2. Build frontend (Vite + Vue)
############################
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
# Tailwind memindai view pagination di vendor
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

############################
# 3. Backend: PHP-FPM
############################
FROM php:8.2-fpm-alpine AS backend

RUN apk add --no-cache libpq icu-libs libzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS postgresql-dev icu-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip opcache \
    && apk del .build-deps

COPY docker/production/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/production/php-fpm-port.conf /usr/local/etc/php-fpm.d/zzz-port.conf
# Port PHP-FPM (backend). Diatur dari docker-compose (mis. 8817 / 8809)
ENV FPM_PORT=9000

WORKDIR /var/www/html

# Kode + vendor diambil dari tahap "vendor" (sudah tanpa file dump)
COPY --chown=www-data:www-data --from=vendor /app ./
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

COPY docker/production/entrypoint.sh /usr/local/bin/entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint \
    && chmod +x /usr/local/bin/entrypoint \
    && rm -f public/hot \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
                storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
EXPOSE ${FPM_PORT}
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

############################
# 4. Frontend: Nginx
############################
FROM nginx:1.27-alpine AS frontend
# Template diproses envsubst saat start (variabel ${PHP_FPM_HOST})
COPY docker/production/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY --from=backend /var/www/html/public /var/www/html/public
ENV PHP_FPM_HOST=backend:9000
EXPOSE 80
