# CAERT / AUCTC — Documentation institutionnelle

**Projet :** AUCTC Database Modernization  
**Phase :** Training & Documentation  
**Version plateforme :** 2.0.0-prod  
**Classification :** Usage interne — sensible

---

## Objectif de ce corpus

Ce répertoire regroupe l’ensemble de la documentation et des supports de formation pour la plateforme **CAERT** (reporting d’incidents sécuritaires, AUCTC). Il est conçu pour être **transférable institutionnellement** : onboarding, exploitation, administration et maintenance.

---

## Index des documents

### Documentation technique

| # | Document | Public | Fichier |
|---|----------|--------|---------|
| 1 | Documentation technique | Équipe IT, intégrateurs | [technical/TECHNICAL_DOCUMENTATION.md](technical/TECHNICAL_DOCUMENTATION.md) |
| 2 | Documentation base de données | DBA, architectes | [database/DATABASE_DOCUMENTATION.md](database/DATABASE_DOCUMENTATION.md) |
| 3 | Documentation infrastructure | DevOps, hébergeur | [infrastructure/INFRASTRUCTURE_DOCUMENTATION.md](infrastructure/INFRASTRUCTURE_DOCUMENTATION.md) |
| 3b | **Hébergement réseau local AUCTC (Windows)** | Admin IT, point focal | [infrastructure/LOCAL_NETWORK_HOSTING.md](infrastructure/LOCAL_NETWORK_HOSTING.md) |
| 4 | Documentation API | Développeurs, intégrateurs | [api/API_DOCUMENTATION.md](api/API_DOCUMENTATION.md) |
| 5 | Documentation sécurité | RSSI, admins sécurité | [security/SECURITY_DOCUMENTATION.md](security/SECURITY_DOCUMENTATION.md) |
| 6 | Documentation ETL / import | Analystes, admins données | [etl/ETL_DOCUMENTATION.md](etl/ETL_DOCUMENTATION.md) |
| 7 | Documentation SIG / cartographie | Analystes SIG, GIS | [gis/GIS_DOCUMENTATION.md](gis/GIS_DOCUMENTATION.md) |
| 8 | Guide de déploiement | Ops, intégrateurs | [DEPLOYMENT.md](DEPLOYMENT.md) |
| 9 | Guide de maintenance | Ops, support N2 | [maintenance/MAINTENANCE_GUIDE.md](maintenance/MAINTENANCE_GUIDE.md) |
| 10 | Guide de dépannage | Support, ops | [troubleshooting/TROUBLESHOOTING_GUIDE.md](troubleshooting/TROUBLESHOOTING_GUIDE.md) |

### Manuels utilisateurs

| # | Document | Public | Fichier |
|---|----------|--------|---------|
| 11 | Manuel utilisateur | Points focaux, contributeurs | [user/USER_MANUAL.md](user/USER_MANUAL.md) |
| 12 | Manuel administrateur | Admins, super-admins | [admin/ADMINISTRATOR_MANUAL.md](admin/ADMINISTRATOR_MANUAL.md) |

### Formation

| Document | Public | Fichier |
|----------|--------|---------|
| Onboarding | Tous les nouveaux utilisateurs | [training/ONBOARDING.md](training/ONBOARDING.md) |
| Guide analyste (workflows complets) | Analystes, points focaux | [training/ANALYST_GUIDE.md](training/ANALYST_GUIDE.md) |
| Procédures administration système | Admins IT | [training/SYSADMIN_PROCEDURES.md](training/SYSADMIN_PROCEDURES.md) |

### Documents de livraison (phases antérieures)

| Document | Contenu |
|----------|---------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Architecture de production (synthèse) |
| [OPERATIONS.md](OPERATIONS.md) | Monitoring, backup, DR |
| [PRODUCTION_MODERNIZATION.md](PRODUCTION_MODERNIZATION.md) | Synthèse modernisation |
| [VALIDATION_REPORT.md](VALIDATION_REPORT.md) | Rapport de validation |
| [FINAL_DELIVERY.md](FINAL_DELIVERY.md) | Livraison finale |

### Schémas

| Fichier | Description |
|---------|-------------|
| [diagrams/CAERT_Annexe_Schema_BDD.drawio](diagrams/CAERT_Annexe_Schema_BDD.drawio) | Schéma BDD (Draw.io) |

### Exports Word / formation

| Fichier | Description |
|---------|-------------|
| [../deliverables/README.md](../deliverables/README.md) | Génération Word, PDF, slides |
| `deliverables/training_slides.html` | Présentation HTML (navigateur) |

Commande : `npm run docs:all`

---

## Parcours recommandés

### Nouveau point focal pays
1. [training/ONBOARDING.md](training/ONBOARDING.md)
2. [user/USER_MANUAL.md](user/USER_MANUAL.md)
3. [training/ANALYST_GUIDE.md](training/ANALYST_GUIDE.md)

### Nouvel administrateur métier
1. [admin/ADMINISTRATOR_MANUAL.md](admin/ADMINISTRATOR_MANUAL.md)
2. [training/ANALYST_GUIDE.md](training/ANALYST_GUIDE.md) (sections validation et synthèse)

### Équipe technique / hébergement
1. [technical/TECHNICAL_DOCUMENTATION.md](technical/TECHNICAL_DOCUMENTATION.md)
2. [infrastructure/INFRASTRUCTURE_DOCUMENTATION.md](infrastructure/INFRASTRUCTURE_DOCUMENTATION.md)
3. [DEPLOYMENT.md](DEPLOYMENT.md)
4. [maintenance/MAINTENANCE_GUIDE.md](maintenance/MAINTENANCE_GUIDE.md)
5. [security/SECURITY_DOCUMENTATION.md](security/SECURITY_DOCUMENTATION.md)

---

## Langues de l’interface

L’application supporte **français** et **anglais** (interface uniquement). Les données métier en base restent dans la langue de saisie. Voir le manuel utilisateur, section « Langue ».

---

## Mise à jour de la documentation

Lors de toute évolution majeure (migration, nouvelle route, changement RBAC), mettre à jour :

1. Le document concerné dans ce répertoire
2. [VALIDATION_REPORT.md](VALIDATION_REPORT.md) si impact recette
3. La date de révision en en-tête du fichier modifié

**Contact documentation :** Tech lead AUCTC / équipe CAERT.
