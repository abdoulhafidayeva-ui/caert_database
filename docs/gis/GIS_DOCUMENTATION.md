# Documentation SIG / Cartographie — CAERT

**Module :** Carte SIG  
**Route UI :** `/map`  
**Technologie :** Leaflet 1.9 + tuiles OpenStreetMap  
**Contrôleur :** `MapController`

---

## 1. Objectif

Visualiser géographiquement la répartition des **incidents publiés** pour faciliter l’analyse spatiale au niveau continental.

> **Périmètre MVP actuel :** agrégation au **centroïde national** (pas de coordonnées GPS par localité).

---

## 2. Données affichées

| Critère | Valeur |
|---------|--------|
| Statut incident | `is_published = true` uniquement |
| Position | Centroïde du pays (`CountryCentroidProvider`) |
| Taille cercle (carte pays) | Proportionnelle au volume d’incidents |
| Infobulle | Pays, nombre incidents, décès |

Les incidents **en attente** ou **rejetés** n’apparaissent pas.

---

## 3. APIs GeoJSON

### `GET /api/map/incidents`

Un point par incident publié, positionné au centroïde du pays.

**Properties :** `id`, `label` (pays), `deaths`, `localite`

### `GET /api/map/countries`

Un point par pays avec agrégation.

**Properties :** `country`, `count`, `deaths`

Format standard GeoJSON `FeatureCollection` — compatible QGIS, scripts externes (avec session auth).

---

## 4. Interface utilisateur

Accès : menu **Carte SIG** (`nav.map`)

Éléments affichés :

- Carte Leaflet plein écran (65vh)
- KPI : nombre de pays concernés, total incidents publiés
- Légende : taille des cercles = volume par pays
- Note : localité disponible dans le détail incident, pas en coordonnées précises

Langue : libellés UI traduits FR/EN.

---

## 5. Pays supportés (centroïdes)

Coordonnées codées dans `CountryCentroidProvider` / repository pour les pays les plus représentés (ex. MALI, NIGERIA, BURKINA FASO, KENYA, SENEGAL, GHANA, CAMEROUN, NIGER, SOMALIA, TCHAD…).

**Pays sans centroïde configuré :** incident publié mais point absent de la carte.

**Action admin :** demander ajout coordonnées au tech lead.

---

## 6. Limites connues

| Limite | Impact | Évolution |
|--------|--------|-----------|
| Pas de GPS localité | Précision nationale seulement | PostGIS + géocodage |
| Centroïde fixe | Plusieurs incidents = points superposés | Agrégation par pays (API countries) |
| Tuiles OSM externes | Dépendance réseau | Tuiles institutionnelles |
| Auth session requise | Pas d’accès carte anonyme | API token (roadmap) |

---

## 7. Usage analyste (résumé)

1. Se connecter → **Carte SIG**
2. Zoomer sur la région d’intérêt
3. Cliquer sur un cercle / point pour infobulle
4. Croiser avec **Synthèse direction** et **Analyses** pour tendances temporelles
5. Pour le détail d’un incident : retour **Tableau de bord** → fiche incident

Guide complet : [../training/ANALYST_GUIDE.md](../training/ANALYST_GUIDE.md)

---

## 8. Export et réutilisation

Actuellement : pas d’export GeoJSON depuis l’UI. Options :

- Appeler `/api/map/countries` (authentifié) pour intégration scriptée
- Export SQL + jointure pays pour outils SIG externes

---

## 9. Roadmap SIG

1. Géocodage `localite` + pays → lat/lng
2. Migration PostgreSQL / PostGIS
3. Couches régionales, heatmaps
4. Filtres carte (période, type attaque, groupe)
