FROM php:7-apache

RUN apt-get update && \
 DEBIAN_FRONTEND=noninteractive apt-get -y install imagemagick && \
 apt-get clean

COPY / /var/www/html