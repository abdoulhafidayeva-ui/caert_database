# Phase Training & Documentation — Livraison

**Projet :** AUCTC Database Modernization  
**Phase :** Training & Documentation  
**Date :** juin 2026  
**Version plateforme :** 2.0.0-prod

---

## Résumé

Cette phase produit le **corpus documentaire institutionnel** et les **supports de formation** pour la plateforme CAERT, complémentaire aux livrables des phases Inception, Design et Implementation.

---

## Livrables générés

| # | Exigence | Statut | Fichier |
|---|----------|--------|---------|
| 1 | Documentation technique | ✅ | [technical/TECHNICAL_DOCUMENTATION.md](technical/TECHNICAL_DOCUMENTATION.md) |
| 2 | Documentation base de données | ✅ | [database/DATABASE_DOCUMENTATION.md](database/DATABASE_DOCUMENTATION.md) |
| 3 | Documentation infrastructure | ✅ | [infrastructure/INFRASTRUCTURE_DOCUMENTATION.md](infrastructure/INFRASTRUCTURE_DOCUMENTATION.md) |
| 4 | Documentation API | ✅ | [api/API_DOCUMENTATION.md](api/API_DOCUMENTATION.md) |
| 5 | Documentation sécurité | ✅ | [security/SECURITY_DOCUMENTATION.md](security/SECURITY_DOCUMENTATION.md) |
| 6 | Documentation ETL | ✅ | [etl/ETL_DOCUMENTATION.md](etl/ETL_DOCUMENTATION.md) |
| 7 | Documentation SIG | ✅ | [gis/GIS_DOCUMENTATION.md](gis/GIS_DOCUMENTATION.md) |
| 8 | Documentation déploiement | ✅ | [DEPLOYMENT.md](DEPLOYMENT.md) (existant, référencé) |
| 9 | Guide maintenance | ✅ | [maintenance/MAINTENANCE_GUIDE.md](maintenance/MAINTENANCE_GUIDE.md) |
| 10 | Guide dépannage | ✅ | [troubleshooting/TROUBLESHOOTING_GUIDE.md](troubleshooting/TROUBLESHOOTING_GUIDE.md) |
| 11 | Manuel utilisateur | ✅ | [user/USER_MANUAL.md](user/USER_MANUAL.md) |
| 12 | Manuel administrateur | ✅ | [admin/ADMINISTRATOR_MANUAL.md](admin/ADMINISTRATOR_MANUAL.md) |
| 13 | Guides formation analyste | ✅ | [training/](training/) |

---

## Supports de formation

| Support | Public | Durée |
|---------|--------|-------|
| [training/ONBOARDING.md](training/ONBOARDING.md) | Tous | ~2 h |
| [training/ANALYST_GUIDE.md](training/ANALYST_GUIDE.md) | Analystes, admins métier | ~3 h |
| [training/SYSADMIN_PROCEDURES.md](training/SYSADMIN_PROCEDURES.md) | IT / ops | Référence |

Contenu couvert :

- Onboarding institutionnel
- Workflows opérationnels (saisie, validation, rejet)
- Usage dashboards et synthèse direction
- Usage cartographie SIG
- Workflows reporting et analyse incident
- Procédures administration système

---

## Exports Word / PDF / slides

Génération depuis les sources Markdown :

```bash
npm run docs:all
```

| Fichier | Description |
|---------|-------------|
| `deliverables/AUCTC_CAERT_Documentation_Pack_2026.docx` | Pack documentation complet |
| `deliverables/AUCTC_CAERT_Training_Slides_2026.docx` | Slides formation (paysage) |
| `deliverables/training_slides.html` | Présentation navigateur |
| `deliverables/word/*.docx` | Manuels individuels |

**PDF :** ouvrir chaque `.docx` dans Word → Enregistrer sous → PDF.

Voir [../deliverables/README.md](../deliverables/README.md)

---

## Index central

Point d'entrée unique : **[README.md](README.md)**

---

## Maintenance documentaire

Mettre à jour la documentation lors de :

- Nouvelle migration BDD
- Nouvelle route ou API
- Changement RBAC
- Évolution workflow ou import Excel

---

## Sign-off

| Rôle | Nom | Date | Statut |
|------|-----|------|--------|
| Tech lead | | | ☐ |
| Formation AUCTC | | | ☐ |
| Métier | | | ☐ |
