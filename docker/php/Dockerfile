FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
        git unzip libzip-dev icu-dev oniguruma-dev libsodium-dev \
        mysql-client bash \
    && docker-php-ext-install pdo pdo_mysql intl opcache zip \
    && docker-php-ext-enable sodium opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Symfony CLI (optional but handy)
RUN wget -O - https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

EXPOSE 9000

CMD ["php-fpm"]
