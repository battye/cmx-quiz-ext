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
echo "Dependencies"
composer install --no-interaction

# Install phpBB
echo "phpBB project"
composer create-project --no-interaction phpbb/phpbb /workspaces/phpbb
cp /workspaces/cmx-quiz-ext/resources/phpbb-config.yml /workspaces/phpbb/install/install-config.yml
ln -s /workspaces/cmx-quiz-ext /workspaces/phpbb/ext/battye/cmx-quiz-ext
php /workspaces/phpbb/install/phpbbcli.php install /workspaces/phpbb/install/install-config.yml