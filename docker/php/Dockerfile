FROM php:8.2-cli-alpine

RUN apk add --no-cache unzip git

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
