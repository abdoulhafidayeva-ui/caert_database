FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json yarn.lock ./

RUN yarn install --frozen-lockfile

COPY assets ./assets
COPY webpack.config.js ./

RUN yarn build

FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev libicu-dev \
    && docker-php-ext-install pdo_mysql intl zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

COPY --from=assets /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p var/uploads var/cache var/log var/backups \
    && chown -R www-data:www-data var \
    && php bin/console assets:install public --no-interaction || true

ENV APP_ENV=prod

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
