FROM php:7.0-apache

RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    openssh-server \
    libssl-dev \
    --no-install-recommends && rm -r /var/lib/apt/lists/*

RUN docker-php-ext-install zip && pecl install mongodb && docker-php-ext-enable mongodb
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY . /var/xhgui
WORKDIR /var/xhgui
RUN chmod 777 cache
RUN composer install --no-dev
COPY php.ini /usr/local/etc/php/conf.d/custom_php.ini
COPY apache.conf /etc/apache2/sites-enabled/000-default.conf

EXPOSE 80
CMD ["apache2-foreground"]
