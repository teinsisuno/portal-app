# ==========================================
# Stage 1: Build PHP dependencies
# ==========================================
FROM composer:2.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# --no-scripts: post-autoload-dump manggil artisan yang belum ada di stage ini
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs --no-scripts

# ==========================================
# Stage 2: Build frontend assets (Vite)
# ==========================================
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ==========================================
# Stage 3: Production Image (Apache + PHP)
# ==========================================
FROM php:8.4-apache

WORKDIR /var/www/html

# System deps & PHP extensions — MySQL (pdo_mysql), bukan SQLite
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip bcmath intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite untuk Laravel routing
RUN a2enmod rewrite

# DocumentRoot menunjuk ke public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy application code
COPY . .

# Copy vendor dari composer stage
COPY --from=vendor /app/vendor/ ./vendor/

# Copy built frontend assets dari node stage
COPY --from=frontend /app/public/build/ ./public/build/

# Entrypoint: siapin storage + migrate otomatis tiap container start
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Permissions Laravel storage & cache (folder dibuat dulu — .dockerignore exclude storage)
RUN mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/framework/cache/data \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
