#!/usr/bin/env bash
# One-time setup for a fresh Ubuntu 24.04 server (run as root):
#   sudo bash server-setup.sh uat.deltains-brokers.com
set -euo pipefail

DOMAIN="${1:?Usage: server-setup.sh <domain>}"
APP_DIR=/var/www/delta
DB_NAME=delta
DB_USER=delta
DB_PASS="$(openssl rand -base64 24 | tr -d '=+/')"

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get -y upgrade
apt-get install -y software-properties-common curl unzip git ufw nginx mysql-server redis-server supervisor certbot python3-certbot-nginx
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-gd php8.3-redis

# Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Firewall: SSH + web only
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# Database
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

# App directory + deploy user
id -u deploy >/dev/null 2>&1 || adduser --disabled-password --gecos "" deploy
usermod -aG www-data deploy
mkdir -p "$APP_DIR"
chown -R deploy:www-data "$APP_DIR"

# Nginx
sed "s/__DOMAIN__/${DOMAIN}/g" "$(dirname "$0")/nginx.conf" > /etc/nginx/sites-available/delta
ln -sf /etc/nginx/sites-available/delta /etc/nginx/sites-enabled/delta
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# Queue worker + scheduler
cp "$(dirname "$0")/supervisor-worker.conf" /etc/supervisor/conf.d/delta-worker.conf
# "|| true": a new user has no crontab yet, and under set -e that would stop the script here.
( crontab -u deploy -l 2>/dev/null || true; echo "* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1" ) | sort -u | crontab -u deploy -
supervisorctl reread || true

echo
echo "=================================================================="
echo " Server ready. Public IP (send this to the insurers for whitelisting):"
curl -s https://api.ipify.org; echo
echo
echo " MySQL password for user '${DB_USER}':  ${DB_PASS}"
echo " -> put it in ${APP_DIR}/.env  (DB_PASSWORD=...)"
echo
echo " Next: as user 'deploy', clone the repository into ${APP_DIR} and run deploy/deploy.sh"
echo " HTTPS:  certbot --nginx -d ${DOMAIN}   (after DNS points to this server)"
echo "=================================================================="
