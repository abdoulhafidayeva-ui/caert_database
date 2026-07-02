# Guide analyste — Workflows opérationnels CAERT

**Public :** Analystes, points focaux, admins métier  
**Prérequis :** [ONBOARDING.md](ONBOARDING.md)

---

## 1. Workflow opérationnel global

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   SAISIE    │────►│  EN ATTENTE  │────►│   PUBLIÉ    │
│ web / Excel │     │  (validation)│     │  (analytics)│
└─────────────┘     └──────┬───────┘     └─────────────┘
                           │
                           ▼
                    ┌─────────────┐
                    │   REJETÉ    │──► correction ──► EN ATTENTE
                    └─────────────┘
```

---

## 2. Workflow saisie manuelle

### Étape 1 — Préparer les données

- Date d'attaque exacte
- Localité, pays (auto si point focal)
- Type attaque, cible, groupe, moyens
- Victimes par catégorie (civils, militaires, terroristes)

### Étape 2 — Enregistrer

Menu → **Nouvel enregistrement** → **Enregistrer**

### Étape 3 — Suivre le statut

Tableau de bord → colonne **Statut** ou filtre **En attente**

### Étape 4 — Traiter un rejet

1. Consulter motif (badge rouge / fiche détail)
2. Corriger les données
3. **Modifier** → enregistrer → repasse **En attente**

---

## 3. Workflow import Excel

| Étape | Action |
|-------|--------|
| 1 | Vérifier référentiels à jour |
| 2 | Préparer fichier .xlsx (modèle colonnes) |
| 3 | Importer via modal dashboard |
| 4 | Lire message succès / partiel / échec |
| 5 | Corriger lignes en erreur si partiel |
| 6 | Admin valide en masse via file validation |

Référence : [../etl/ETL_DOCUMENTATION.md](../etl/ETL_DOCUMENTATION.md)

---

## 4. Workflow validation (admin)

| Étape | Action |
|-------|--------|
| 1 | Ouvrir **File de validation** |
| 2 | Ouvrir fiche incident |
| 3 | Vérifier cohérence (date, pays, chiffres, sources) |
| 4 | **Publier** ou **Rejeter** avec motif clair |
| 5 | Vérifier KPI synthèse mis à jour |

**Motif de rejet** : factuel, actionnable (ex. « Doublon #42 — fusionner »).

---

## 5. Guide tableau de bord et filtres

### KPI bandeau (admin)

- Compteur incidents à valider → lien direct file validation

### Filtres multi-critères

1. Sélectionner statut(s), région, pays, types…
2. **Rechercher**
3. Exporter via boutons DataTables (Excel, PDF) si activés

### Colonnes exportées

Reflectent les filtres actifs — vérifier période avant reporting officiel.

---

## 6. Guide synthèse direction

Page **Synthèse** (`/executive`)

| Widget | Usage décisionnel |
|--------|-------------------|
| Publiés | Volume validé continent |
| En attente | Backlog validation |
| Rejetés | Qualité données / formation |
| Décès | Impact humanitaire publié |
| Top pays | Priorisation géographique (top 10) |

**Limitation :** top pays ≠ totalité des pays — voir section carte pour vision complète.

---

## 7. Guide analyses (graphiques)

### Graphiques automatiques (haut de page)

- Barres : attaques / décès / blessés (total publié)
- Camembert : répartition cibles

### Graphique comparatif (filtre)

1. **Période 1** (mois) — obligatoire
2. **Période 2** — optionnel (comparaison)
3. **Indicateur** : nombre d'attaques, morts terroristes, morts civils
4. **Régions** — multi-sélection
5. **Générer les graphiques**

Interprétation : barres par région pour chaque période sélectionnée.

---

## 8. Guide cartographie (SIG)

### Lecture

- Cercles par pays = volume incidents publiés
- Plus le cercle est grand → plus d'incidents
- Clic → infobulle (pays, décès, localité texte)

### Analyse spatiale recommandée

1. Carte → identifier hotspots
2. Synthèse → confirmer chiffres top pays
3. Tableau de bord filtré par pays → détail incidents
4. Analyses → tendance temporelle du pays

Limites MVP : pas de GPS localité — [../gis/GIS_DOCUMENTATION.md](../gis/GIS_DOCUMENTATION.md)

---

## 9. Workflow reporting institutionnel

### Rapport mensuel type

| Section | Source CAERT |
|---------|--------------|
| Synthèse chiffres | `/executive` |
| Évolution | `/graphique` |
| Cartographie | `/map` + capture écran |
| Détail pays X | Dashboard filtré + export DataTables |

### Checklist qualité avant diffusion

- [ ] Uniquement incidents **publiés**
- [ ] Période clairement indiquée
- [ ] Pas de données « TEST » en prod
- [ ] Validation admin documentée

---

## 10. Workflow analyse incident

### Investigation d'un incident spécifique

1. Dashboard → recherche ID ou filtres
2. **Voir** fiche complète
3. Vérifier statut, auteur, dates
4. Si rejet : lire `objet_rejet`
5. Croiser avec incidents même pays/période (filtres)

### Analyse tendance pays

1. Filtre pays + plage dates attaque
2. Export tableau
3. Graphique indicateur « attaque » sur région du pays
4. Carte SIG pour contexte spatial

---

## 11. Bonnes pratiques analyste

1. **Qualité > quantité** — mieux vaut rejeter et corriger
2. **Libellés cohérents** — alignés référentiels
3. **Dates d'attaque** — pas confondre avec date de saisie
4. **Classification** — ne pas exporter hors circuits UA
5. **Signaler** anomalies référentiels à l'admin

---

## 12. Ressources associées

| Sujet | Document |
|-------|----------|
| Utilisateur | [../user/USER_MANUAL.md](../user/USER_MANUAL.md) |
| Admin | [../admin/ADMINISTRATOR_MANUAL.md](../admin/ADMINISTRATOR_MANUAL.md) |
| Dépannage | [../troubleshooting/TROUBLESHOOTING_GUIDE.md](../troubleshooting/TROUBLESHOOTING_GUIDE.md) |
