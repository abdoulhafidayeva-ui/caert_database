# Documentation infrastructure — CAERT

**Environnements cibles :** Production, pré-production, développement local  
**Dernière révision :** juin 2026

---

## 1. Architecture de déploiement

```
                    ┌─────────────────┐
                    │  Load balancer  │
                    │  (TLS termin.)  │
                    └────────┬────────┘
                             │ HTTPS
                    ┌────────▼────────┐
                    │  Apache + PHP   │
                    │  Symfony 7.4    │
                    │  public/        │
                    └────────┬────────┘
              ┌──────────────┼──────────────┐
              ▼              ▼              ▼
        ┌──────────┐  ┌────────────┐  ┌──────────┐
        │ MySQL 8  │  │ var/uploads│  │ var/log  │
        └──────────┘  └────────────┘  └──────────┘
```

---

## 2. Prérequis serveur

| Composant | Version minimale |
|-----------|------------------|
| PHP | 8.2+ |
| Extensions PHP | intl, zip, pdo_mysql, opcache |
| MySQL | 8.0+ |
| Node.js (build) | 18+ |
| Composer | 2.x |

---

## 3. Variables d’environnement

Fichier `.env` (ne jamais committer les secrets) :

| Variable | Obligatoire | Description |
|----------|-------------|-------------|
| `APP_ENV` | Oui | `prod`, `dev`, `test` |
| `APP_SECRET` | Oui | Clé Symfony (rotation annuelle) |
| `DATABASE_URL` | Oui | `mysql://user:pass@host:3306/caert_db?serverVersion=8.0` |
| `MAILER_DSN` | Oui (prod) | SMTP institutionnel |

Répertoires applicatifs :

| Chemin | Usage | Permissions |
|--------|-------|-------------|
| `var/cache/` | Cache Symfony | Écriture web server |
| `var/log/` | Logs | Écriture web server |
| `var/uploads/` | Fichiers import temporaires | Écriture, **hors public/** |

---

## 4. Docker

Fichiers : `Dockerfile`, `docker-compose.yml`

```bash
docker compose up -d --build
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
curl -f http://localhost:8080/health
```

Services :

| Service | Port | Image |
|---------|------|-------|
| `app` | 8080→80 | Build local (PHP 8.2 Apache) |
| `db` | 3307→3306 | mysql:8.0 |

Volumes persistants : `caert_mysql_data`, `./var/uploads`, `./var/log`

---

## 5. Déploiement bare metal / VM

```bash
bash scripts/deploy/deploy-prod.sh
RUN_SMOKE=1 APP_URL=https://caert.example.org bash scripts/deploy/deploy-prod.sh
```

Étapes du script (résumé) :

1. `composer install --no-dev`
2. `yarn build` (assets Encore)
3. Migrations Doctrine
4. Cache prod
5. Smoke test optionnel

Guide détaillé : [../DEPLOYMENT.md](../DEPLOYMENT.md)

---

## 6. Monitoring

| Sonde | URL | Réponse attendue |
|-------|-----|------------------|
| Health | `GET /health` | `{"status":"healthy",...}` HTTP 200 |
| Login | `GET /login` | HTTP 200 |
| Auth requise | `GET /` | HTTP 302 → login |

Checks `/health` :

- `database` : `SELECT 1`
- `uploads_writable`
- `logs_writable`

Scripts smoke :

```bash
bash scripts/deploy/smoke-test.sh https://votre-domaine
powershell -File scripts/deploy/smoke-test.ps1 -BaseUrl https://votre-domaine
```

---

## 7. Sauvegarde et reprise

| Ressource | Script | Fréquence recommandée |
|-----------|--------|------------------------|
| Base MySQL | `scripts/deploy/backup-database.sh` | Quotidien (cron 02:00) |
| Uploads | `scripts/deploy/backup-uploads.sh` | Quotidien |
| Restauration | `scripts/deploy/disaster-recovery-restore.sh` | Sur incident |

RPO/RTO indicatifs : voir [../OPERATIONS.md](../OPERATIONS.md)

---

## 8. CI/CD

Pipeline : `.github/workflows/ci.yml`

Déclencheurs : push/PR sur `main`, `master`, `develop`

Étapes :

1. Composer install
2. Yarn build
3. `lint:container`
4. Migrations sur MySQL de test
5. PHPUnit
6. Build image Docker

---

## 9. Réseau et sécurité infra

- TLS obligatoire en production (certificat institutionnel)
- Pare-feu : autoriser 443 uniquement depuis Internet
- MySQL : accès restreint au serveur applicatif
- `var/uploads` : jamais exposé via Apache/Nginx document root

Voir [../security/SECURITY_DOCUMENTATION.md](../security/SECURITY_DOCUMENTATION.md)
