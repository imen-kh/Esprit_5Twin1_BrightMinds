# syntax=docker/dockerfile:1
#
# Image de production NutriTrace (Laravel 12, PHP 8.2, Apache), construite en 3 étapes :
#   1. assets : compilation Vite (CSS/JS) avec Node 20
#   2. vendor : dépendances PHP de production avec Composer
#   3. image finale php:8.2-apache, sans Node ni Composer ni fichier .env
# Les secrets (APP_KEY, DB_*, GEMINI_API_KEY, MAIL_*) sont fournis au démarrage par variables d'environnement.

# ===== 1. Assets front (Vite) =====
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
# Réessais en cas de réseau instable ; npm peut s'arrêter sans code d'erreur (« Exit handler never called ») :
# on vérifie donc que Vite est bien installé pour échouer ici plutôt qu'à l'étape suivante
RUN npm config set fetch-retries 5 \
    && npm config set fetch-retry-maxtimeout 120000 \
    && npm ci --no-audit --no-fund \
    && test -x node_modules/.bin/vite
COPY resources ./resources
COPY public ./public
RUN npm run build

# ===== 2. Dépendances PHP (Composer) =====
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Les extensions PHP (gd, intl…) sont installées dans l'image finale : on ne les exige pas ici
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# ===== 3. Image finale : PHP 8.2 + Apache =====
FROM php:8.2-apache

# Extensions PHP nécessaires : PostgreSQL/MySQL, images (gd), archives (zip), calcul décimal (bcmath), i18n (intl)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev libicu-dev libpq-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_pgsql gd zip bcmath intl opcache \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

# Apache : réécriture d'URL (routes Laravel) et racine web sur /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

# Code de l'application (le .env et les fichiers sensibles sont exclus par .dockerignore)
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

# Script de démarrage : attente de MySQL, migrations, caches Laravel
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint \
    && chmod +x /usr/local/bin/entrypoint \
    && rm -f .env \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]
