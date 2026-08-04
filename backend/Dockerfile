FROM dunglas/frankenphp:php8.3

RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    bcmath \
    opcache \
    intl \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENV APP_ENV=production
EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
