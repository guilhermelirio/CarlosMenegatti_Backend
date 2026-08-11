FROM node:24-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM php:8.5-fpm-alpine AS php
COPY --from=ghcr.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN apk add --no-cache postgresql-client \
    && install-php-extensions bcmath exif gd intl opcache pcntl pdo_pgsql redis zip
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --optimize-autoloader
COPY . .
COPY --from=frontend /app/public/build ./public/build
COPY docker/production/php.ini /usr/local/etc/php/conf.d/99-app.ini
RUN mkdir -p storage/app/backups storage/app/private storage/app/public \
    storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
EXPOSE 9000
CMD ["php-fpm", "-F"]

FROM nginx:1.29-alpine AS nginx
WORKDIR /var/www/html
COPY public ./public
COPY --from=frontend /app/public/build ./public/build
COPY docker/production/nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
