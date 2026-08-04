FROM dunglas/frankenphp:php8.3

RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    bcmath \
    opcache \
    intl \
    zip

# frankenphp ships with cap_net_bind_service set via setcap so it can bind :80
# as non-root. Restricted-sandbox hosts (e.g. Render) reject exec of binaries
# with that capability ("Operation not permitted"). We bind a high port via
# $PORT anyway, so the capability is unnecessary — strip it.
RUN setcap -r /usr/local/bin/frankenphp

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENV APP_ENV=production
EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
