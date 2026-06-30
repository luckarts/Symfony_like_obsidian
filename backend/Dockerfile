FROM php:8.3-fpm-alpine AS php

WORKDIR /app

RUN apk add --no-cache \
    nginx supervisor \
    git curl unzip \
    postgresql-dev \
    libxslt-dev \
    yaml-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install yaml \
    && docker-php-ext-enable yaml \
    && apk del .build-deps \
    && docker-php-ext-install pdo pdo_pgsql xsl \
    && docker-php-ext-enable opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock symfony.lock ./
ENV APP_ENV=prod
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY . .
ENV DATABASE_URL=sqlite:///tmp/test.db
ENV MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
RUN composer run-script post-install-cmd \
    && php bin/console cache:clear \
    && chown -R www-data:www-data /app/var

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod 644 /app/config/jwt/private.pem

COPY docker/php/entrypoint.sh /app/docker/php/entrypoint.sh
RUN chmod +x /app/docker/php/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/app/docker/php/entrypoint.sh"]
