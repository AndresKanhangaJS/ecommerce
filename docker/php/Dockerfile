FROM composer:2 AS composer

FROM php:8.2-fpm-alpine

# docker-php-extension-installer resolves prebuilt binaries for these extensions
# instead of compiling from source, which is far faster and more reliable than
# apk + docker-php-ext-install/pecl. Installed in separate RUN layers (rather than
# one long chained command) so a rebuild after an interruption resumes from the
# last cached layer instead of reinstalling everything from scratch.
RUN apk add --no-cache bash curl mysql-client supervisor \
    && curl -sSLf -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    && chmod +x /usr/local/bin/install-php-extensions

RUN install-php-extensions bcmath exif pcntl pdo_mysql
RUN install-php-extensions redis
RUN install-php-extensions zip
RUN install-php-extensions intl
RUN install-php-extensions gd

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
