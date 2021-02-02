FROM composer:2.0

COPY composer.* /app/
RUN composer install
COPY . /app
RUN composer test