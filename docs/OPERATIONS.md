# CAERT — Opérations & continuité

## Monitoring

| Endpoint | Usage |
|----------|--------|
| `GET /health` | Sonde load balancer (DB + app) |
| `var/log/prod.log` | Erreurs applicatives |
| `var/log/audit.log` | Piste d'audit (complément table `audit_log`) |

## Journalisation

- Canal Monolog `audit` → fichier dédié.
- Table `audit_log` : requêtes de conformité via SQL ou futur écran admin.

Actions journalisées : `INCIDENT_CREATE`, `UPDATE`, `DELETE`, `PUBLISH`, `REJECT`, imports.

## Sauvegarde & reprise

### Base de données

```bash
# Linux / cron quotidien
bash scripts/deploy/backup-database.sh
```

Variables : `DATABASE_URL`, optionnel `BACKUP_DIR` (défaut `var/backups`). Rétention 14 jours.

### Fichiers

- `var/uploads/` — pièces jointes incidents (hors `public/`).
- Synchroniser vers stockage objet (S3-compatible) en production.

### Plan de reprise (RPO / RTO indicatifs)

| Composant | RPO cible | RTO cible |
|-----------|-----------|-----------|
| MySQL | 24 h (dump quotidien) | 4 h |
| Uploads | 24 h | 4 h |
| Application | Redéploiement image | 1 h |

## Déploiement

Voir [DEPLOYMENT.md](DEPLOYMENT.md). Script automatisé :

```bash
bash scripts/deploy/deploy-prod.sh
RUN_SMOKE=1 APP_URL=https://caert.example.org bash scripts/deploy/deploy-prod.sh
```

Validation post-déploiement :

```bash
bash scripts/deploy/smoke-test.sh https://caert.example.org
# Windows
powershell -File scripts/deploy/smoke-test.ps1 -BaseUrl https://caert.example.org
```

## Docker

```bash
docker compose up -d --build
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app bash scripts/deploy/smoke-test.sh http://localhost
```

## Sécurité opérationnelle

- Désactiver comptes : `users.enable = false` (rejet à la connexion).
- Rotation `APP_SECRET` et mots de passe DB selon politique UA.
- Ne pas exposer `var/uploads` via le serveur web.
- TLS obligatoire en production ; `remember_me` limité à 7 jours.

## CI

Pipeline GitHub Actions : `.github/workflows/ci.yml` (lint container, migrations, PHPUnit).
