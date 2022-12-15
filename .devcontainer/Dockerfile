# Debian version
ARG VARIANT="buster"
FROM mcr.microsoft.com/vscode/devcontainers/base:0-${VARIANT}

# Install PHP
RUN apt-get -y update
RUN apt-get -y install php php-xml

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set up the web root in the new public directory
RUN sudo rm -rf /var/www/html \
    && sudo mkdir -p /workspaces/phpbb \
    && sudo ln -s /workspaces/phpbb /var/www/html

# Install MySQL
RUN apt-get -y install mysql-server php-mysql

# Configure Apache
RUN echo "Listen 8080" >> /etc/apache2/ports.conf && \
    a2enmod rewrite
