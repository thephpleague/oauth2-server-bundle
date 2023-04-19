ARG PHP_VERSION=7.4

FROM php:${PHP_VERSION}-cli-alpine
LABEL maintainer="Petar Obradović <petar.obradovic@trikoder.net>"

# This is where we're going to store all of our non-project specific binaries
RUN mkdir -p /app/bin
ENV PATH /app/bin:$PATH

ARG XDEBUG_VERSION=3.1.6

# Install needed core and PECL extensions
RUN apk add --update --no-cache --virtual .build-deps \
        ${PHPIZE_DEPS} \
        libxml2-dev \
        libzip-dev \
        linux-headers \
        zlib-dev \
    && docker-php-ext-install -j $(getconf _NPROCESSORS_ONLN) \
        xml \
        zip \
    && pecl install \
        xdebug-${XDEBUG_VERSION} \
    && docker-php-ext-enable \
        xdebug \
    && apk del --purge .build-deps

RUN mv ${PHP_INI_DIR}/php.ini-development ${PHP_INI_DIR}/php.ini

ENV XDEBUG_START_WITH_REQUEST 0

RUN echo '[xdebug]' >> ${PHP_INI_DIR}/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.start_with_request = ${XDEBUG_START_WITH_REQUEST}' >> ${PHP_INI_DIR}/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.mode = debug' >> ${PHP_INI_DIR}/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.discover_client_host = 0' >> ${PHP_INI_DIR}/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.client_host = %XDEBUG_CLIENT_HOST%' >> ${PHP_INI_DIR}/conf.d/docker-php-ext-xdebug.ini

# Utilities needed to run this image
RUN apk add --update --no-cache \
        git \
        libzip \
        unzip \
        su-exec \
        shadow

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/app/bin --filename=composer \
    && chmod a+x /app/bin/composer

# Create the user that's going to run our application
RUN useradd -ms /bin/sh app

# Composer bin plugin
RUN su-exec app composer global config --no-interaction allow-plugins.bamarni/composer-bin-plugin true \
    && su-exec app composer global config --no-interaction allow-plugins.symfony/flex true \
    && su-exec app composer global require --dev bamarni/composer-bin-plugin

# PHP-CS-Fixer
RUN su-exec app composer global bin php-cs-fixer require --dev friendsofphp/php-cs-fixer \
    && ln -s /home/app/.composer/vendor/bin/php-cs-fixer /app/bin/php-cs-fixer

# Psalm
RUN su-exec app composer global bin psalm require --dev vimeo/psalm psalm/plugin-symfony \
    && ln -s /home/app/.composer/vendor/bin/psalm /app/bin/psalm

# Enable parallel package installation for Composer
RUN su-exec app composer global require symfony/flex

COPY entrypoint.sh /usr/local/bin/docker-entrypoint

VOLUME /app/src
WORKDIR /app/src

ENTRYPOINT ["docker-entrypoint"]
CMD ["php", "-a"]
