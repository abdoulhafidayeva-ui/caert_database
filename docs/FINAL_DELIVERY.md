# CAERT — Final Production Delivery (AUCTC)

**Version:** 2.0.0-prod  
**Date:** 2026-06-01  
**Statut:** Production-ready (Wave 2 — finalisation)

---

## 1. Résumé exécutif

La plateforme CAERT (AUCTC) est une application Symfony 7.4 / PHP 8.2 / MySQL 8 de reporting d'incidents sécuritaires, modernisée pour un déploiement enterprise en production.

Cette livraison finalise l'architecture, la performance, la sécurité, les pipelines ops et l'expérience analyste.

---

## 2. Composants livrés

| Domaine | Livrable | Emplacement |
|---------|----------|-------------|
| **Base de données** | Indexes performance + audit_log | `migrations/Version20260526120000.php`, `Version20260601120000.php` |
| **Requêtes** | Synthèse exécutive en 1 requête | `src/Repository/AllDataRepository.php` |
| **ETL** | Import Excel sécurisé + audit | `src/Service/Import/ExcelImportService.php` |
| **GIS** | Carte Leaflet + agrégation pays | `src/Controller/MapController.php`, `templates/map/` |
| **BI** | Synthèse opérationnelle + top pays | `src/Controller/DashboardController.php` |
| **Workflow** | File de validation | `src/Controller/WorkflowController.php` |
| **RBAC** | Voters + garde pays | `src/Security/Voter/AllDataVoter.php`, `IncidentCountryGuard` |
| **Audit** | Journal applicatif | `src/Service/Audit/AuditLogger.php`, `audit_log` |
| **Sécurité** | Headers HTTP, auth Symfony 7 | `src/EventSubscriber/SecurityHeadersSubscriber.php` |
| **Monitoring** | Health probe enrichi | `GET /health` |
| **Backup / DR** | Scripts DB + uploads + restore | `scripts/deploy/backup-*.sh`, `disaster-recovery-restore.sh` |
| **Docker** | Multi-stage build (PHP + assets) | `Dockerfile`, `docker-compose.yml` |
| **CI** | Lint, migrations, PHPUnit, Docker build | `.github/workflows/ci.yml` |
| **UX** | Couche enterprise CSS, KPIs dashboard | `public/css/caert-enterprise.css` |
| **Docs** | Architecture, déploiement, ops | `docs/` |
| **Documentation & formation** | Corpus institutionnel complet | `docs/README.md`, `docs/TRAINING_DOCUMENTATION_PHASE.md` |

---

## 3. Déploiement production

### VM / bare metal (recommandé)

```bash
cp .env.example .env
# Éditer APP_SECRET, DATABASE_URL, MAILER_DSN

bash scripts/deploy/deploy-prod.sh
RUN_SMOKE=1 APP_URL=https://votre-domaine bash scripts/deploy/validate-deployment.sh
```

### Docker

```bash
cp .env.example .env
docker compose up -d --build
curl -f http://localhost:8080/health
```

### Cron (backup)

```cron
0 2 * * * cd /var/www/caert && bash scripts/deploy/backup-database.sh
15 2 * * * cd /var/www/caert && bash scripts/deploy/backup-uploads.sh
```

---

## 4. Comptes de test (fixtures)

Mot de passe commun de démonstration : `n4n86fgh`

| Profil | E-mail | Rôle Symfony |
|--------|--------|--------------|
| Super administrateur | abdoulhafidayeva@gmail.com | `ROLE_SUPER_ADMIN` |
| Staff AUCTC | staff.caert@caert.test | `ROLE_STAFF` |
| Administrateur métier | admin.caert@caert.test | `ROLE_ADMIN` |
| Point focal Mali | focal.mali@caert.test | `ROLE_USER` (pays : Mali) |

---

## 5. Validation

Exécuter :

```bash
bash scripts/deploy/validate-deployment.sh http://127.0.0.1:8000
php bin/console doctrine:migrations:migrate --no-interaction
vendor/bin/phpunit
```

Rapport détaillé : `docs/VALIDATION_REPORT.md`

---

## 6. Roadmap post-livraison

- PostgreSQL + PostGIS (géocodage précis)
- Messenger async (imports lourds)
- MFA / SSO institutionnel
- Superset ou Metabase BI externe
- Bootstrap 5 migration complète

---

## 7. Sign-off

| Rôle | Nom | Date | Statut |
|------|-----|------|--------|
| Tech lead | | | ☐ |
| Ops | | | ☐ |
| Métier AUCTC | | | ☐ |
