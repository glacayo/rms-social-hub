# ============================================================
# RMS Social Hub — Production Dockerfile
# Single stage: PHP 8.2 + Node 20
# Order: composer install → ziggy:generate → npm build
# ============================================================
FROM php:8.2-fpm-alpine

# Install system dependencies + Node 20
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    nodejs \
    npm \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    oniguruma-dev \
    icu-dev \
    git \
    unzip \
    autoconf \
    g++ \
    make \
    && npm install -g npm@latest

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        mbstring \
        bcmath \
        opcache \
        intl \
        pcntl

# Install Redis extension via PECL (requires autoconf + g++ + make)
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── Step 1: PHP dependencies (layer cache — only re-runs if composer files change)
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

# ── Step 2: Copy full application
COPY . .

# ── Step 3: Run composer post-install scripts (needs full app)
RUN composer run-script post-autoload-dump

# ── Step 4: Generate ziggy.js (needs vendor/ + routes/)
RUN php artisan ziggy:generate --env=production

# ── Step 5: JS dependencies + frontend build (ziggy.js now exists)
COPY package*.json ./
RUN npm ci

RUN npm run build

# ── Step 6: Clean up Node + dev artifacts (keep image lean)
RUN npm prune --omit=dev \
    && rm -rf node_modules \
    && apk del nodejs npm

# ── Step 7: Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# ── Step 8: Config files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
