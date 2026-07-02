# Documentation ETL / Import Excel — CAERT

**Service :** `App\Service\Import\ExcelImportService`  
**Route :** `POST /import/data`  
**Format accepté :** `.xlsx` (Excel Open XML)

---

## 1. Vue d’ensemble

L’import permet de charger en masse des incidents depuis un fichier Excel. Chaque ligne devient un enregistrement `all_data` en statut **en attente de validation** (`isPublished = NULL`).

Flux :

```
Upload .xlsx → validation CSRF → stockage temporaire var/uploads/
    → lecture PhpSpreadsheet → mapping colonnes → persist BDD
    → suppression fichier temporaire → audit IMPORT_COMPLETE
```

---

## 2. Accès et sécurité

| Règle | Détail |
|-------|--------|
| Rôle minimum | `ROLE_USER` |
| CSRF | Token `import_data` |
| Restriction pays | Point focal : pays colonne D = pays assigné |
| Audit | Action `IMPORT_COMPLETE` avec compteurs succès/erreurs |

---

## 3. Structure du fichier Excel

- **Ligne 1** : en-têtes (supprimée automatiquement)
- **Lignes 2+** : données
- Ligne ignorée si colonnes A et H vides

### Mapping colonnes → champs

| Colonne | Champ incident | Obligatoire |
|---------|----------------|-------------|
| **A** | `date_attaque` | **Oui** |
| C | `espace` (référentiel) | Oui |
| D | `pays` (référentiel) | Oui |
| F | `localite` | Recommandé |
| G | `perpetrateur` | Oui |
| H | `details` | Recommandé |
| I | `moyen_attaque` | Oui |
| J | `cible` | Oui |
| K | `mort_civil` | Numérique |
| L | `mort_securite_militaire` | Numérique |
| M | `mort_terroriste` | Numérique |
| N–S | disparus / blessés | Numérique |
| W | `liberes` | Numérique |
| X | `attaque` (type) | Oui |
| Y | `materiel_attaque` | Oui |
| Z | `otages` | Numérique |
| AA | `terroriste_arretes` | Numérique |
| AB | `materiaux` | Oui |
| AC | `autres` | Texte |
| AD | `remarque` | Texte |

---

## 4. Règles de validation

### Date (colonne A)

- Formats acceptés : numéro série Excel ou texte parseable (`strtotime`)
- Erreur si vide : `Date d'attaque (colonne A) manquante.`

### Référentiels

- Libellé comparé en **MAJUSCULES** sans casse (`UPPER(libelle)`)
- Erreur si inconnu : `{label} inconnu : VALEUR`
- Erreur si vide : `{label} manquant.`

### Pays (restriction point focal)

Si l’utilisateur est restreint à un pays :

```
Import refusé : le pays « X » ne correspond pas à votre pays assigné.
```

### Totaux

Calculés automatiquement par `AllDataTotalsCalculator` avant persist.

---

## 5. Résultat import

| Cas | Message flash | Session |
|-----|---------------|---------|
| 100 % succès | `N ligne(s) importée(s) avec succès` | — |
| Partiel | `Import partiel : X OK, Y erreur(s)` | `import_errors` (détail lignes) |
| Échec total | `Import échoué : {message}` | — |

Format erreurs partielles :

```php
['row' => 15, 'message' => 'pays inconnu : XYZ']
```

---

## 6. Bonnes pratiques

1. **Préparer les référentiels** avant import (types d’attaque, pays, etc.)
2. Utiliser les **libellés exacts** présents en base (vérifier casse)
3. Tester avec **1–2 lignes** avant import massif
4. Vérifier la **file de validation** après import (`/workflow/inbox`)
5. Conserver une copie du fichier source (hors plateforme, selon politique UA)

---

## 7. Dépannage import

| Symptôme | Cause probable | Action |
|----------|----------------|--------|
| Toutes lignes en erreur « inconnu » | Libellés référentiels absents | Créer entrées référentiel |
| Erreur pays assigné | Point focal importe autre pays | Corriger colonne D ou compte |
| Date invalide | Format date non reconnu | Utiliser format Excel date |
| Aucun fichier | Upload vide | Resélectionner .xlsx |

Voir [../troubleshooting/TROUBLESHOOTING_GUIDE.md](../troubleshooting/TROUBLESHOOTING_GUIDE.md)

---

## 8. Évolution (roadmap)

- Import asynchrone via Symfony Messenger (gros volumes)
- Template Excel téléchargeable depuis l’UI
- Rapport d’import PDF archivé
