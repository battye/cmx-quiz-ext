# Copy Apache configuration
# sudo rm /etc/apache2/sites-enabled/000-default.conf
# sudo cp .devcontainer/resources/phpbb-apache.conf /etc/apache2/sites-enabled/000-default.conf

# Start MySQL
sudo service mysql start

# Start Apache
sudo service apache2 start

# Add SSH key
echo "$SSH_KEY" > /home/vscode/.ssh/id_rsa && chmod 600 /home/vscode/.ssh/id_rsa

# Create a MySQL user to use
sudo mysql -u root<<EOFMYSQL
    CREATE USER 'phpbb'@'localhost' IDENTIFIED BY 'phpbb'; 
    GRANT ALL PRIVILEGES ON *.* TO 'phpbb'@'localhost' WITH GRANT OPTION;
    CREATE DATABASE IF NOT EXISTS phpbb;
EOFMYSQL

# Download dependencies
composer install
composer create-project phpbb/phpbb /workspaces/cmx-quiz-ext/public