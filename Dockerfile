# syntax=docker/dockerfile:1
FROM tuanvu2504/laravel-opentelemetry:swoole as build
USER www-data
WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev \
        --no-interaction \
        --prefer-dist \
        --ignore-platform-reqs \
        --no-autoloader \
        --no-progress
RUN composer dump-autoload --optimize --apcu --no-dev
RUN chmod +x entrypoint.sh

RUN rm -fr /var/www/html/composer
EXPOSE 9000
ENTRYPOINT [ "/var/www/html/entrypoint.sh" ]