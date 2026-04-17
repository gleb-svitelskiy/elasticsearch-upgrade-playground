# syntax=docker/dockerfile:1

FROM composer:lts AS prod-dependencies
RUN --mount=type=bind,source=composer.json,target=composer.json \
    --mount=type=bind,source=composer.lock,target=composer.lock \
    --mount=type=bind,source=src,target=src \
    --mount=type=cache,target=/tmp/cache \
    composer install --no-interaction --no-dev --classmap-authoritative

FROM composer:lts AS dev-dependencies
RUN --mount=type=bind,source=composer.json,target=composer.json \
    --mount=type=bind,source=composer.lock,target=composer.lock \
    --mount=type=cache,target=/tmp/cache \
    composer install --no-interaction

FROM php:8.5-cli AS base
ENV PHP_INI_DIR=$PHP_INI_DIR
WORKDIR /srv/app
COPY ./bin bin
COPY ./src src

FROM base AS development
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
ARG PHPUNIT_VERSION=13.1.8
ARG PHPUNIT_SHA256=0d38c8e5f89fa64d2602b026b6339ac100c50abba2d0c4fef13d229e9a7c16df
ADD --checksum=sha256:${PHPUNIT_SHA256} --chmod=+x \
    https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar tools/phpunit.phar
COPY --from=dev-dependencies /app/vendor vendor
COPY ./tests tests
COPY ./phpunit.xml phpunit.xml

FROM development AS test
RUN ./tools/phpunit.phar

FROM base AS final
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=prod-dependencies /app/vendor vendor
COPY --from=test /srv/app/tools/test-results tools/test-results
