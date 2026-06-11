# CAERT — Guide de déploiement (production)

## Prérequis

- PHP 8.2+, extensions: intl, zip, pdo_mysql
- MySQL 8.0+
- Composer 2.x
- Node.js 18+ (build assets)

## Installation

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
```

## Variables d'environnement

| Variable | Description |
|----------|-------------|
| `DATABASE_URL` | Connexion MySQL |
| `APP_SECRET` | Clé Symfony |
| `MAILER_DSN` | SMTP institutionnel |

## Docker

```bash
docker compose up -d --build
docker compose exec app php bin/console doctrine:migrations:migrate
```

Application : http://localhost:8080

## Sauvegardes

- MySQL : dump quotidien + binlog si disponible
- `var/uploads` : synchroniser vers stockage objet
- `var/log/audit.log` : archivage conformité

## Script de déploiement

```bash
bash scripts/deploy/deploy-prod.sh
```

Variables optionnelles : `RUN_SMOKE=1`, `APP_URL=https://votre-domaine`.

## Post-déploiement (smoke test)

Automatisé :

```bash
bash scripts/deploy/smoke-test.sh https://votre-domaine
# Windows
powershell -File scripts/deploy/smoke-test.ps1 -BaseUrl https://votre-domaine
```

Manuel :

1. `GET /health` → `status: healthy`
2. Connexion utilisateur
3. `/workflow/inbox` (admin)
4. `/executive` KPIs
5. `/map` chargement GeoJSON
6. Import Excel test (1 ligne)
7. Vérifier `audit_log` et indexes

## Sauvegarde planifiée

```bash
# cron : 0 2 * * * cd /var/www/caert && bash scripts/deploy/backup-database.sh
```

Voir aussi [OPERATIONS.md](OPERATIONS.md) et [ARCHITECTURE.md](ARCHITECTURE.md).
