FROM php:8.2-cli-bullseye

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    NODE_MAJOR=20 \
    PATH="/var/www/html/vendor/bin:/var/www/html/node_modules/.bin:${PATH}"

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        gnupg \
        unzip \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        ca-certificates \
        build-essential \
        tini \
    && curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && docker-php-ext-install pdo_pgsql pdo_mysql zip bcmath \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY klema/composer.json klema/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --no-scripts

COPY klema/package.json klema/package-lock.json ./
RUN npm ci --omit=dev=false --legacy-peer-deps=false

COPY klema/ .

RUN php artisan package:discover --ansi

RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY render/entrypoint.sh /usr/local/bin/render-entrypoint.sh
RUN chmod +x /usr/local/bin/render-entrypoint.sh

# Queue worker will be started automatically by entrypoint.sh
# To run queue worker separately, use:
# php artisan queue:work --queue=weather --tries=3 --timeout=300

EXPOSE 8000

ENTRYPOINT ["/usr/bin/tini", "--"]
CMD ["render-entrypoint.sh"]

