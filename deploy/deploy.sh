#!/usr/bin/env bash
# Run on the server as user 'deploy' from /var/www/delta after every update:
#   bash deploy/deploy.sh
set -euo pipefail
cd "$(dirname "$0")/.."

git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction

if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate --force
  echo ">> .env created — fill DB_PASSWORD and insurer credentials, then run this script again."
  exit 0
fi

php artisan down --retry=30 || true
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up

sudo -n supervisorctl restart 'delta-worker:*' 2>/dev/null || php artisan queue:restart
echo ">> Deployed. Check the insurers:  php artisan insurers:ping"
