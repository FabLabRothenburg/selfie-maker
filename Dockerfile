FROM php:8-apache

RUN apt-get update && \
 DEBIAN_FRONTEND=noninteractive apt-get -y install imagemagick && \
 apt-get clean

RUN echo "upload_max_filesize = 8M" > $PHP_INI_DIR/conf.d/90-upload_max_filesize.ini && \
    echo "date.timezone = Europe/Berlin" > $PHP_INI_DIR/conf.d/20-timezone.ini && \
    cp $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/conf.d/00-base.ini

COPY / /var/www/html
