# Documentation base de données — CAERT

**SGBD :** MySQL 8.0  
**Encodage :** utf8mb4 / utf8mb4_unicode_ci  
**ORM :** Doctrine 3 (naming strategy underscore)

---

## 1. Vue d’ensemble

La base **`caert_db`** (nom configurable via `DATABASE_URL`) contient :

- **1 table centrale** : `all_data` (incidents)
- **Géographie** : `region`, `pays`
- **8 référentiels** paramétrables
- **Utilisateurs** : `users`
- **Audit** : `audit_log`
- **Installation** : `app_param`
- **Rôles legacy** : `roles` (non liée au RBAC Symfony)

Schéma visuel : [../diagrams/CAERT_Annexe_Schema_BDD.drawio](../diagrams/CAERT_Annexe_Schema_BDD.drawio)

---

## 2. Table centrale : `all_data`

Stocke chaque incident terroristes enregistré.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT PK | Identifiant |
| `details` | VARCHAR(255) | Description |
| `date_attaque` | DATETIME | **Date opérationnelle** (analytics) |
| `localite` | VARCHAR(255) | Localité (texte, pas de GPS) |
| `mort_civil`, `mort_securite_militaire`, `mort_terroriste` | INT | Décès par catégorie |
| `disparu_*`, `blesse_*` | INT | Disparus / blessés |
| `total_deces`, `total_disparus`, `total_blesses` | INT | Totaux calculés |
| `otages`, `liberes`, `terroriste_arretes` | INT | Autres indicateurs |
| `autres`, `remarque` | VARCHAR(255) | Champs libres |
| `is_published` | TINYINT NULL | `NULL`= en attente, `1`= publié, `0`= rejeté |
| `objet_rejet` | VARCHAR(255) | Motif de rejet |
| `created_at` | DATETIME | Date de saisie |
| `pays_id`, `user_id`, `attaque_id`, … | FK | Références |

### Workflow publication

| État | `is_published` | `objet_rejet` |
|------|----------------|---------------|
| En attente | NULL | NULL ou vide |
| Publié | true (1) | NULL |
| Rejeté | false (0) | Motif renseigné |

Seuls les incidents **publiés** alimentent les KPIs, graphiques et carte.

---

## 3. Référentiels

Structure commune : `id`, `libelle` (UNIQUE), `created_at`, `user_id`.

| Table | Usage |
|-------|-------|
| `attaque` | Type d’attaque |
| `cible` | Type de cible |
| `moyen_attaque` | Moyen utilisé |
| `materiel_attaque` | Matériel d’attaque |
| `materiaux` | Matériaux récupérés |
| `perpetrateurs` | Groupe terroriste |
| `espace` | Contexte (urbain, rural, etc.) |

Les libellés sont comparés **sans casse** à l’import Excel.

---

## 4. Géographie

```
region (1) ──< pays (N) ──< all_data (N)
users.pays_id ──> pays (point focal)
```

---

## 5. Utilisateurs : `users`

| Colonne notable | Description |
|-----------------|-------------|
| `email` | Identifiant de connexion (unique) |
| `roles` | JSON Symfony (`ROLE_USER`, `ROLE_ADMIN`, …) |
| `enable` | Compte actif |
| `is_verified` | E-mail vérifié |
| `pays_id` | Pays assigné (restriction incidents) |
| `locale` | Préférence UI (`fr`, `en`) |

---

## 6. Audit : `audit_log`

| Colonne | Description |
|---------|-------------|
| `action` | Ex. `INCIDENT_CREATE`, `INCIDENT_PUBLISH`, `IMPORT_COMPLETE` |
| `entity_type` | Ex. `all_data` |
| `entity_id` | ID entité concernée |
| `actor_id` | Utilisateur (FK `users`) |
| `ip_address` | IP client |
| `payload` | JSON contexte |
| `created_at` | Horodatage |

---

## 7. Index de performance

| Migration | Index |
|-----------|-------|
| `Version20260526120000` | `audit_log` ; `(is_published, date_attaque)` ; `(pays_id, created_at)` ; `(user_id)` |
| `Version20260601120000` | `date_attaque` ; workflow ; FK analytics ; `users.enable` |
| `Version20260608120000` | `users.locale` |

---

## 8. Commandes DBA

```bash
# État des migrations
php bin/console doctrine:migrations:status

# Appliquer migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Valider schéma
php bin/console doctrine:schema:validate --skip-sync

# Sauvegarde
bash scripts/deploy/backup-database.sh
```

---

## 9. Requêtes utiles (conformité)

```sql
-- Dernières actions audit
SELECT action, entity_type, entity_id, created_at
FROM audit_log ORDER BY created_at DESC LIMIT 50;

-- Incidents en attente
SELECT COUNT(*) FROM all_data
WHERE is_published IS NULL AND (objet_rejet IS NULL OR objet_rejet = '');

-- Volume publié par pays
SELECT p.libelle, COUNT(*) AS n
FROM all_data a JOIN pays p ON a.pays_id = p.id
WHERE a.is_published = 1
GROUP BY p.libelle ORDER BY n DESC;
```

---

## 10. Évolution cible

Migration PostgreSQL + PostGIS pour géocodage précis des localités (phase post-livraison).
