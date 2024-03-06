# syntax=docker/dockerfile:1
FROM composer:2.4.2 as deps
WORKDIR /
COPY ["composer.json","composer.lock", "./"]
RUN composer install --no-dev \
        --no-interaction \
        --prefer-dist \
        --ignore-platform-reqs \
        --optimize-autoloader \
        --apcu-autoloader \
        --no-scripts

FROM tuanvu2504/laravel-opentelemetry:latest as build
USER www-data
WORKDIR /var/www/html

COPY --chown=www-data:www-data --from=deps vendor .
COPY --chown=www-data:www-data . .
RUN chmod +x entrypoint.sh

EXPOSE 9000
ENTRYPOINT [ "/var/www/html/entrypoint.sh" ]