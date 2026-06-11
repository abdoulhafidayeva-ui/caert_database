#!/usr/bin/env bash
# CAERT — production deployment helper (VM or container host).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

export APP_ENV=prod

echo "==> Composer (prod)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Frontend assets"
if command -v npm >/dev/null 2>&1; then
  npm ci
  npm run build
fi

echo "==> Database migrations"
php bin/console doctrine:migrations:migrate --no-interaction

echo "==> Cache warmup"
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

echo "==> Permissions"
mkdir -p var/uploads var/log var/backups
chmod -R ug+rwx var || true

echo "==> Smoke test (optional)"
if [ "${RUN_SMOKE:-0}" = "1" ]; then
  bash scripts/deploy/smoke-test.sh "${APP_URL:-http://127.0.0.1:8080}"
fi

echo "==> Validation (optional)"
if [ "${RUN_VALIDATE:-1}" = "1" ] && [ "${RUN_SMOKE:-0}" != "1" ]; then
  bash scripts/deploy/validate-deployment.sh "${APP_URL:-http://127.0.0.1:8080}" || true
fi

echo "Deployment steps completed."
