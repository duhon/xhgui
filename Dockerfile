FROM php:7-apache
MAINTAINER Duhon <duhon@rambler.ru>

RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    openssh-server \
    libssl-dev \
    libzip-dev \
    --no-install-recommends && rm -r /var/lib/apt/lists/*

RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip
RUN pecl install mongodb
RUN docker-php-ext-enable mongodb
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ADD . /var/xhgui
WORKDIR /var/xhgui
RUN chmod 777 cache
#RUN composer install --no-dev
COPY php.ini /usr/local/etc/php/conf.d/custom_php.ini
COPY apache.conf /etc/apache2/sites-enabled/000-default.conf

EXPOSE 80
CMD ["apache2-foreground"]
