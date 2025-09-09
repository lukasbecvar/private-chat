# define base php-apache image for development server
FROM php:8.4-apache

# set container working directory
WORKDIR /var/www

# install required packages
RUN apt-get update && apt-get install -y \
    libicu-dev \
    rpm
    
# install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo intl

# install OPCache extension
RUN docker-php-ext-install opcache

# set global server name (domain)
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# enable rewrite module
RUN a2enmod rewrite
