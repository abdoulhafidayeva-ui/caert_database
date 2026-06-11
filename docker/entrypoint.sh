#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [ -n "${DATABASE_URL:-}" ]; then
  echo "==> Running database migrations"
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
fi

echo "==> Warming production cache"
php bin/console cache:warmup --env=prod

chown -R www-data:www-data var || true

exec "$@"
