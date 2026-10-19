# Production image for Render (or any Docker host): Apache + PHP 8.3, assets built at image build time.
FROM php:8.3-apache

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl ca-certificates libpq-dev libzip-dev libicu-dev \
    && docker-php-ext-install pdo_pgsql pgsql zip intl bcmath opcache pcntl \
    && a2enmod rewrite headers expires \
    && sed -ri "s#/var/www/html#${APACHE_DOCUMENT_ROOT}#g" /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# PHP dependencies first so this layer is cached until composer.lock changes.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist

# JS dependencies.
COPY package.json package-lock.json .npmrc ./
RUN npm ci

COPY . .

# Vite inlines this at build time; Render passes service env vars to the build as build args.
ARG VITE_CLERK_PUBLISHABLE_KEY
ARG VITE_APP_NAME="Kasba Bazer"
ENV VITE_CLERK_PUBLISHABLE_KEY=$VITE_CLERK_PUBLISHABLE_KEY \
    VITE_APP_NAME=$VITE_APP_NAME

# The build needs a throwaway key and SQLite only so artisan (wayfinder) can boot; nothing is kept.
RUN composer dump-autoload --optimize --no-dev --no-scripts \
    && cp .env.example .env \
    && php artisan key:generate --force \
    && touch database/database.sqlite \
    && DB_CONNECTION=sqlite npm run build \
    && rm -rf .env database/database.sqlite node_modules \
    && chmod +x docker/start.sh

EXPOSE 10000
CMD ["docker/start.sh"]
