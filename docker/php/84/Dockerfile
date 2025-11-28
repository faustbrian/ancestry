FROM php:8.4-cli
WORKDIR /usr/src/myapp

RUN apt-get update && apt-get install zip unzip git curl libpq-dev -y \
    && docker-php-ext-install pdo_mysql pdo_pgsql \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer \
    && mkdir /.composer && chmod 777 /.composer
