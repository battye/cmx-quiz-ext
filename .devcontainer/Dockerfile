# Debian version
ARG VARIANT="buster"
FROM mcr.microsoft.com/vscode/devcontainers/base:0-${VARIANT}

# Install PHP
RUN apt-get -y update
RUN apt-get -y install php php-xml php-mbstring php-curl php-zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install MySQL
RUN apt-get -y install mysql-server php-mysql

# Xdebug
RUN pecl install xdebug-3.1.2
ADD resources/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Configure Apache
RUN echo "Listen 8080" >> /etc/apache2/ports.conf && \
    a2enmod rewrite
