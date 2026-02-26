FROM dunglas/frankenphp:php8.4-alpine

WORKDIR /app

RUN install-php-extensions zip


COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY . .

RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

ENV APP_ENV=prod
ENV SERVER_NAME=:80
ENV DOCUMENT_ROOT=/app/public

EXPOSE 80
