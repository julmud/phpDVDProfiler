FROM php:7.2-fpm-alpine

RUN apk update && apk upgrade \
    && apk add gnu-libiconv --no-cache \
        --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ \
        --allow-untrusted \
    && apk add --no-cache \
        freetype \
        libpng \
        libjpeg-turbo \
        freetype-dev \
        libpng-dev \
        jpeg-dev \
        libjpeg \
        libjpeg-turbo-dev \
        git \
     && docker-php-ext-configure gd \
        --with-freetype-dir=/usr/include/ \
        --with-jpeg-dir=/usr/include/ \
        --with-png-dir=/usr/include/ \
     && docker-php-ext-install -j$(getconf _NPROCESSORS_ONLN) gd \
     && docker-php-ext-install mysqli iconv mbstring \
     && rm -rf /tmp/* /var/cache/apk/*
RUN curl -sS https://getcomposer.org/installer | php \
        && mv composer.phar /usr/local/bin/ \
        && ln -s /usr/local/bin/composer.phar /usr/local/bin/composer

COPY . /var/www/

WORKDIR /var/www/
RUN cp contrib/docker/php.ini /usr/local/etc/php/conf.d/phpdvdprofiler.ini

ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php
ENV PATH="~/.composer/vendor/bin:./vendor/bin:${PATH}"

CMD exec php-fpm
