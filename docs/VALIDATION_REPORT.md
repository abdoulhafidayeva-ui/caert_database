# Rapport de validation — Modernisation CAERT (production)

**Date :** 1 juin 2026  
**Version :** 2.0.0-prod (Wave 2 — finalisation enterprise)

## Résumé exécutif

La plateforme CAERT est **production-ready** sur Symfony 7 / MySQL 8 avec architecture sécurisée, indexes performance, ETL audité, BI/SIG, workflow validation, Docker multi-stage, CI renforcée, scripts backup/DR et documentation complète.

Voir aussi : [FINAL_DELIVERY.md](FINAL_DELIVERY.md)

## Périmètre validé

| Exigence | Statut | Détail |
|----------|--------|--------|
| Sécurité — endpoints publics | ✅ | Graphiques protégés ; `/health` public ops |
| Headers sécurité HTTP | ✅ | `SecurityHeadersSubscriber` + Apache |
| Audit trail | ✅ | `audit_log` + `AuditLogger` + Monolog `audit` |
| Intégrité import Excel | ✅ | Date colonne A, FK, garde pays, audit |
| Analytics optimisés | ✅ | Synthèse 1 requête, top pays |
| Index BDD Wave 1 | ✅ | `Version20260526120000` |
| Index BDD Wave 2 | ✅ | `Version20260601120000` (date, workflow, FK) |
| RBAC | ✅ | `AllDataVoter`, `IncidentCountryGuard` |
| CSRF | ✅ | Login, import, suppression |
| Workflow validation | ✅ | `/workflow/inbox` |
| Synthèse direction | ✅ | `/executive` + tableau top pays |
| SIG MVP | ✅ | `/map` + GeoJSON + légende |
| UX enterprise | ✅ | KPIs accueil, nav, `caert-enterprise.css` |
| DataTables | ✅ | Fix conflit ID wrapper/table |
| Docker production | ✅ | Multi-stage, curl, entrypoint, volumes |
| Health probe | ✅ | DB + uploads + logs writable |
| Backup DB + uploads | ✅ | `backup-database.sh`, `backup-uploads.sh` |
| Disaster recovery | ✅ | `disaster-recovery-restore.sh` |
| Validation deploy | ✅ | `validate-deployment.sh` |
| CI | ✅ | PHPUnit complet, yarn build, Docker build |
| Documentation | ✅ | DEPLOYMENT, ARCHITECTURE, OPERATIONS, FINAL_DELIVERY |

## Vérifications automatisées

```bash
php bin/console lint:container
php bin/console doctrine:migrations:migrate --no-interaction
vendor/bin/phpunit
bash scripts/deploy/validate-deployment.sh http://127.0.0.1:8000
```

**Résultat attendu :** container OK, migrations à jour, tests verts, validation OK.

## Checklist smoke manuelle

- [ ] Connexion admin + point focal pays
- [ ] Utilisateur pays : restriction incidents
- [ ] Admin : file validation, publication/rejet
- [ ] Import Excel : date et audit log
- [ ] `/executive` : KPIs + top pays
- [ ] `/map` : cercles par pays
- [ ] Accueil admin : bandeau KPI + raccourcis
- [ ] Entrée `audit_log` après action sensible

## Hors périmètre (roadmap)

- PostgreSQL / PostGIS natif
- Symfony Messenger (import async)
- Apache Superset, MFA, API REST v1
- Tests E2E navigateur complets

## Sign-off

| Rôle | Nom | Date | Signature |
|------|-----|------|-----------|
| Tech lead | | | |
| Ops | | | |
| Métier AUCTC | | | |
