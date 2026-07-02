# Documentation API — CAERT

**Version :** 2.0.0-prod  
**Format :** JSON (Symfony JsonResponse)  
**Authentification :** Session Symfony (cookie) sauf endpoints publics listés

> **Note :** CAERT n’expose pas encore d’API REST v1 publique documentée OpenAPI. Les endpoints ci-dessous servent l’application web. Une API institutionnelle est prévue en phase ultérieure.

---

## 1. Endpoints publics (sans authentification)

### `GET /health`

Sonde opérationnelle pour load balancer et monitoring.

**Réponse 200 (healthy) :**

```json
{
  "status": "healthy",
  "service": "caert",
  "checks": {
    "app": "ok",
    "version": "2.0.0-prod",
    "database": "ok",
    "uploads_writable": "ok",
    "logs_writable": "ok"
  },
  "timestamp": "2026-06-11T12:00:00+00:00"
}
```

**Réponse 503 (degraded)** si un check = `error`.

---

### `GET /api/pays-by-region`

Liste des pays filtrés par région(s). Utilisé par les formulaires incident (Select2).

**Paramètres query :**

| Param | Type | Description |
|-------|------|-------------|
| `region` | string ou array | Libellé(s) de région |

**Exemple :** `/api/pays-by-region?region=Afrique de l'Ouest`

**Réponse :**

```json
{
  "pays": [
    { "id": 1, "libelle": "MALI", "region": "..." }
  ]
}
```

---

## 2. Endpoints authentifiés (`ROLE_USER`)

Tous les autres endpoints `/api/*` et routes métier requièrent une session active.

### `GET /api/map/incidents`

GeoJSON — points incidents publiés (centroïde pays).

**Réponse :**

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "geometry": { "type": "Point", "coordinates": [lng, lat] },
      "properties": {
        "id": 42,
        "label": "MALI",
        "deaths": 5,
        "localite": "Gao"
      }
    }
  ]
}
```

---

### `GET /api/map/countries`

GeoJSON — agrégation par pays (volume incidents, décès).

**Properties :** `country`, `count`, `deaths`

---

### `GET /objet_rejet_msg/{id}`

Motif de rejet d’un incident (DataTables AJAX).

**Réponse :**

```json
{ "msg": "Doublon avec incident #12" }
```

**Erreurs :** 404 si incident inconnu ; 403 si accès refusé (voter pays).

---

## 3. Endpoints analyses (Chart.js)

| Route | Méthode | Description |
|-------|---------|-------------|
| `/graphique/nb_Terrorist_Incidents` | GET | Totaux attaques / décès / blessés (publiés) |
| `/graphique/pr_Targets_Attacks` | GET | Répartition cibles |
| `/search_for_graphique` | POST | Comparaison régions / périodes |

### `POST /search_for_graphique`

**Corps (form-urlencoded) :**

| Champ | Obligatoire | Description |
|-------|-------------|-------------|
| `start` | Oui | Période 1 (`YYYY-MM`) |
| `end` | Non | Période 2 (défaut = start) |
| `type` | Oui | `attaque`, `perpetrateurs`, `civil` |
| `region[]` | Oui | IDs région |

**Réponse succès :**

```json
{
  "type": "ATTAQUE",
  "typeLabel": "Nombre d'attaques",
  "regions": ["Afrique de l'Ouest"],
  "countMonth": [
    { "label": "juin 2026", "donnees": [12, 8] }
  ]
}
```

**Erreurs 400 :** `{ "error": "..." }` (période manquante, indicateur invalide, etc.)

---

## 4. Actions POST sensibles (CSRF)

| Route | Token CSRF |
|-------|------------|
| `POST /import/data` | `import_data` |
| `POST /alldata/delete/{id}` | `delete{id}` |
| `POST /alldata/publish_or_not/{id}` | (formulaire workflow) |
| `POST /locale/{locale}` | Symfony CSRF |

---

## 5. Codes HTTP usuels

| Code | Signification |
|------|---------------|
| 200 | Succès |
| 302 | Redirection (login requis) |
| 400 | Paramètres invalides (API graphique) |
| 403 | Accès refusé (rôle ou pays) |
| 404 | Ressource introuvable |
| 503 | Health degraded |

---

## 6. Évolution API REST v1 (roadmap)

Endpoints prévus :

- `GET /api/v1/incidents` (pagination, filtres)
- `GET /api/v1/incidents/{id}`
- Authentification : token OAuth2 / clé API institutionnelle
- Documentation OpenAPI 3.x
