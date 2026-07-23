# Note — Import des données historiques (`Data.xlsx`)

**Public :** IT / Super administrateur  
**Objet :** rappel de la méthode utilisée pour charger l’historique AUCTC dans la base locale  

---

## 1. Contexte

Le fichier source `Data.xlsx` (feuille **Data**) contient un historique d’incidents en anglais, structure simplifiée (environ **9 800** lignes, **2021–2024**).

Il ne correspond **pas** au modèle d’import Excel de l’interface CAERT/AUCTC (colonnes détaillées, libellés français, ventilation des victimes).

Une **commande console dédiée** a donc été utilisée pour l’import vers la base destinée à la production locale.

---

## 2. Prérequis

1. Migrations Doctrine appliquées.  
2. Fixtures groupe **prod** chargées (régions, pays, référentiels, y compris « Non renseigné »).  
3. Compte **super administrateur** créé (auteur des lignes importées).  
4. Sauvegarde MySQL avant import.

---

## 3. Commande

```bash
php bin/console app:import-data-xlsx "C:\chemin\Data.xlsx" --user="email@exemple.org"
```

Options utiles :

| Option | Effet |
|--------|--------|
| `--dry-run` | Valide sans écrire |
| `--pending` | Import en attente de validation (défaut : publié) |
| `--limit=N` | Test sur N lignes |
| `--deaths-mode=total` | Totaux décès/blessés du fichier (défaut) |

Les **ID Excel ne sont pas repris** : les identifiants incidents sont auto-incrémentés en base.

---

## 4. Règles de mapping

| Source Excel | Traitement AUCTC |
|--------------|------------------|
| Date / Year / Month | `dateAttaque` |
| Country | Pays (alias EN → FR) |
| City | Localité |
| Perpetors | Groupe (créé si absent) |
| Means of attack | Moyen d’attaque (alias SALW, IED…) |
| Primary target | Cible (alias Civilians → Civils…) |
| Total Deaths / Injured | `totalDeces` / `totalBlesses` |
| Champs absents (espace, type d’attaque, matériel…) | « Non renseigné » / valeurs neutres |

Si la région du fichier diverge du pays en base, **le pays (et sa région de référence) font foi**.

---

## 5. Résultat attendu

- ~9 800 incidents **publiés** ;  
- totaux décès / blessés disponibles pour synthèse, carte et analyses ;  
- indicateurs d’analyse basés sur **totaux** (pas de ventilation civil/terroriste inventée).

---

## 6. Annulation (si besoin)

```sql
DELETE FROM all_data WHERE remarque LIKE 'Import Data.xlsx%';
```

---

*Confidentiel — AUCTC*
