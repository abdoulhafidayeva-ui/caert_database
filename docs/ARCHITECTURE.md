# CAERT — Architecture de production

## Vue d'ensemble

CAERT (Centre africain de lutte antiterroriste) est une plateforme analytique Symfony 7 pour la saisie, la validation, l'analyse et la cartographie des incidents terroristes au niveau continental.

```
┌─────────────┐     HTTPS      ┌──────────────────┐
│  Analystes  │ ─────────────► │  Symfony (PHP)   │
│  / Admins   │                │  Twig + Encore   │
└─────────────┘                └────────┬─────────┘
                                        │
                    ┌───────────────────┼───────────────────┐
                    ▼                   ▼                   ▼
              ┌──────────┐      ┌────────────┐      ┌─────────────┐
              │ MySQL 8  │      │ var/uploads│      │ audit_log   │
              │ all_data │      │ (privé)    │      │ + Monolog   │
              └──────────┘      └────────────┘      └─────────────┘
```

## Couches applicatives

| Couche | Composants |
|--------|------------|
| Présentation | Twig, DataTables, Chart.js, Leaflet (`/map`) |
| Contrôleurs | `HomeController`, `WorkflowController`, `DashboardController`, `MapController`, `GraphiqueController` |
| Domaine | `AllData`, référentiels (Pays, Région, Attaque, …) |
| Services | `ExcelImportService`, `AllDataTotalsCalculator`, `AuditLogger` |
| Sécurité | `LoginFormAuthenticator`, `AllDataVoter`, CSRF, firewall `ROLE_USER` |
| Persistance | Doctrine ORM 3, migrations versionnées |

## Modèle de données central

- **`all_data`** — incident (date d'attaque, victimes, publication, pays, pièces jointes).
- **`audit_log`** — traçabilité (action, entité, acteur, IP, payload JSON).
- Référentiels : `pays`, `region`, `attaque`, `perpetrateurs`, `cible`, etc.

## Flux opérationnels

1. **Saisie** — formulaire web ou import Excel (`/import/data`).
2. **Validation** — admin : file `/workflow/inbox`, publication/rejet avec motif.
3. **Analyse** — graphiques (`/graphique`), synthèse `/executive`, carte `/map`.
4. **Audit** — chaque création/modification/suppression/publication journalisée.

## RBAC

| Rôle | Périmètre |
|------|-----------|
| `ROLE_USER` | Pays assigné à l'utilisateur |
| `ROLE_ADMIN` | Tous pays + validation + synthèse |
| `ROLE_SUPER_ADMIN` | Administration utilisateurs et paramètres |

Voter : `AllDataVoter` (`INCIDENT_VIEW`, `EDIT`, `DELETE`, `PUBLISH`).

## Performance

Index migration `Version20260526120000` :

- `(is_published, date_attaque)` — tendances et KPIs.
- `(pays_id, created_at)` — filtrage pays.
- `(user_id)` — workflows par contributeur.

Requêtes analytiques basées sur **`date_attaque`** (et non `created_at`).

## SIG (MVP)

Points agrégés par pays (centroïdes) via `AllDataRepository::findMapPointsByCountry()` — API GeoJSON `/api/map/incidents`.

Évolution cible (TDD) : PostgreSQL + PostGIS, géocodage `localite`.

## Évolutions planifiées (hors livrable actuel)

- PostgreSQL / PostGIS natif
- Symfony Messenger (import asynchrone)
- Apache Superset / API REST v1
- MFA institutionnel
