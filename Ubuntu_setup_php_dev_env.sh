#!/bin/bash
# script for setting up dev enviroment on ubuntu and ubuntu derivates

# 1. install php repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update

# 2. install php and extensions
sudo apt-get install -y \
    php8.3 \
    php8.3-cli \
    php8.3-fpm \
    php8.3-mysql \
    php8.3-zip \
    php8.3-curl \
    php8.3-xml \
    php8.3-mbstring \
    php8.3-bcmath \
    php8.3-gd \
    php8.3-imagick \
    php8.3-intl \
    php8.3-soap \
    php8.3-redis \
    php8.3-pdo \
    php8.3-gd \
    php8.3-xdebug \
    php8.3-ldap \
    php8.3-openssl \    

# 3. install composer
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

# 4. install phpcs and phpcbf
composer global require --dev "squizlabs/php_codesniffer=*"

# 4. install phpcs-fixer
composer global require --dev friendsofphp/php-cs-fixer
