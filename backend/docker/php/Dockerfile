FROM php:8.3-cli

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    wget \
    libicu-dev \
    libzip-dev \
    libyaml-dev \
    libonig-dev \
    libxslt1-dev \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    intl \
    zip \
    mbstring \
    xsl \
    opcache

RUN pecl install yaml && docker-php-ext-enable yaml

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
