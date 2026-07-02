# Documentation technique — CAERT

**Version :** 2.0.0-prod  
**Dernière révision :** juin 2026  
**Classification :** Interne — sensible

---

## 1. Présentation

CAERT est une application web monolithique **Symfony 7.4** / **PHP 8.2** pour le reporting, la validation et l’analyse d’incidents sécuritaires au niveau continental (AUCTC).

| Composant | Technologie |
|-----------|-------------|
| Backend | PHP 8.2, Symfony 7.4 |
| ORM | Doctrine 3 |
| Base de données | MySQL 8 |
| Frontend | Twig, Bootstrap 4, jQuery, Webpack Encore |
| Tableaux | DataTables (Omines bundle) |
| Graphiques | Chart.js |
| Cartographie | Leaflet + OpenStreetMap |
| Import | PhpSpreadsheet (Excel .xlsx) |
| Conteneurisation | Docker (Apache + PHP) |

---

## 2. Structure du dépôt

```
caert/
├── config/           # Configuration Symfony (packages, routes, services)
├── migrations/       # Migrations Doctrine versionnées
├── public/           # Document root (assets, vendor JS/CSS)
├── src/
│   ├── Controller/   # Contrôleurs HTTP
│   ├── DataTable/    # Configuration DataTables
│   ├── Entity/       # Modèle Doctrine
│   ├── EventSubscriber/
│   ├── Form/         # Formulaires Symfony
│   ├── Repository/   # Requêtes métier
│   ├── Security/     # Auth, voters, user checker
│   └── Service/      # Logique métier (import, audit, totaux, locale)
├── templates/        # Vues Twig
├── translations/     # i18n FR/EN (messages.*.yaml)
├── tests/            # PHPUnit
├── scripts/deploy/   # Déploiement, backup, smoke tests
└── docs/             # Documentation institutionnelle
```

---

## 3. Modules fonctionnels

| Module | Contrôleur(s) | Description |
|--------|---------------|-------------|
| Tableau de bord | `HomeController` | Liste incidents, filtres, CRUD, import |
| Validation | `WorkflowController` | File d’attente admin |
| Synthèse direction | `DashboardController` | KPIs exécutifs |
| Analyses | `GraphiqueController` | Graphiques tendances |
| Carte SIG | `MapController` | Visualisation géographique |
| Utilisateurs | `SecurityController` | Comptes, rôles, reset MDP |
| Référentiels | `AllParamController` | Types d’attaque, cibles, etc. |
| Paramétrage app | `ParamAppController` | Installation initiale |
| Santé | `HealthController` | Sonde `/health` |
| Locale | `LocaleController` | Bascule FR/EN |

---

## 4. Services métier clés

| Service | Rôle |
|---------|------|
| `ExcelImportService` | Import Excel → entités `AllData` |
| `AllDataTotalsCalculator` | Calcul totaux décès / blessés / disparus |
| `AuditLogger` | Écriture `audit_log` + Monolog |
| `IncidentCountryGuard` | Restriction pays pour points focaux |
| `LocaleResolver` | Locale par défaut selon pays utilisateur |
| `CountryCentroidProvider` | Coordonnées pays pour la carte |

---

## 5. Cycle de vie d’un incident

```
Saisie (web ou Excel)
    → isPublished = NULL  (en attente)
    → Admin : publication (true) ou rejet (false + objetRejet)
    → Incidents publiés : analytics, carte, synthèse
```

Chaque étape sensible est journalisée (`AuditLogger`).

---

## 6. Internationalisation

- Locales actives : `fr`, `en` (structure prête pour `ar`)
- Fichiers : `translations/messages.fr.yaml`, `messages.en.yaml`
- Préférence : champ `users.locale` + sélecteur dans l’en-tête
- JS : objet `window.caertI18n` injecté via `_i18n.html.twig`

---

## 7. Tests

```bash
php bin/console lint:container
php bin/phpunit
```

Tests unitaires : calculateur de totaux, garde pays, health endpoint.

---

## 8. Évolutions planifiées (hors scope actuel)

- PostgreSQL + PostGIS
- Symfony Messenger (import asynchrone)
- API REST v1 documentée (OpenAPI)
- MFA / SSO institutionnel
- BI externe (Superset)

Voir [../ARCHITECTURE.md](../ARCHITECTURE.md) et [../database/DATABASE_DOCUMENTATION.md](../database/DATABASE_DOCUMENTATION.md).
