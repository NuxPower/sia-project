# KLEMA - Climate-Smart Agriculture Monitoring System
# Dockerfile for containerized deployment
# 
# SETUP INSTRUCTIONS (for USB/local deployment):
# 1. Prerequisites:
#    - Docker installed on your system
#    - PostgreSQL database (can be external or in separate container)
#    - OpenWeatherMap API key
#
# 2. Build the Docker image:
#    docker build -t klema:latest .
#
# 3. Run the container:
#    docker run -d \
#      -p 8000:8000 \
#      -e DB_HOST=your_db_host \
#      -e DB_DATABASE=klema \
#      -e DB_USERNAME=your_db_user \
#      -e DB_PASSWORD=your_db_password \
#      -e OPENWEATHER_API_KEY=your_api_key \
#      --name klema-app \
#      klema:latest
#
# 4. For local development without Docker, see README.md setup guide

FROM php:8.2-cli-bullseye

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    NODE_MAJOR=20 \
    PATH="/var/www/html/vendor/bin:/var/www/html/node_modules/.bin:${PATH}"

# Install system dependencies and PHP extensions
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

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies (Laravel, Sanctum, DomPDF, etc.)
COPY klema/composer.json klema/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --no-scripts

# Install Node.js dependencies
# Includes: Vue.js, Vite, Capacitor, Leaflet, Three.js, Vanta.js, and other frontend libraries
COPY klema/package.json klema/package-lock.json ./
RUN npm ci --omit=dev=false --legacy-peer-deps=false

# Copy application files
COPY klema/ .

# Discover Laravel packages
RUN php artisan package:discover --ansi

# Set up storage and cache directories with proper permissions
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy and set up entrypoint script
COPY render/entrypoint.sh /usr/local/bin/render-entrypoint.sh
RUN chmod +x /usr/local/bin/render-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/bin/tini", "--"]
CMD ["render-entrypoint.sh"]

