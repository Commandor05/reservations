FROM php:8-fpm-alpine

ENV PHPGROUP=laravel
ENV PHPUSER=laravel

RUN adduser -g ${PHPGROUP} -s /bin/sh -D ${PHPUSER}

RUN sed -i "s/user = www-data/user = ${PHPUSER}/g" /usr/local/etc/php-fpm.d/www.conf

RUN sed -i "s/group = www-data/group = ${PHPGROUP}/g" /usr/local/etc/php-fpm.d/www.conf

RUN mkdir -p /var/www/html/public

RUN set -ex && apk --no-cache add postgresql-dev

RUN apk add --no-cache zip libzip-dev libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev
RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip
RUN docker-php-ext-configure -j$(nproc) gd --with-freetype --with-jpeg

RUN docker-php-ext-install gd pdo pdo_pgsql opcache

ADD opcache.ini /usr/local/etc/php/conf.d/opcache.ini

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]