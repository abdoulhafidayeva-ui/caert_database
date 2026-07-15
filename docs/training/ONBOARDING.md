# Onboarding — CAERT / AUCTC

**Durée estimée :** 2 heures (utilisateur) / 4 heures (admin)  
**Public :** Tous les nouveaux utilisateurs

---

## Objectifs pédagogiques

À l'issue de l'onboarding, le participant sait :

1. Se connecter et choisir sa langue
2. Comprendre son rôle et son périmètre pays
3. Naviguer dans les modules principaux
4. Saisir ou importer un incident
5. Connaître le workflow de validation

---

## Module 1 — Contexte institutionnel (30 min)

### Présentation

- **CAERT** : plateforme continentale de reporting d'incidents sécuritaires
- **AUCTC** : Centre africain de lutte antiterroriste (Union africaine)
- Données **sensibles** — classification interne

### Principes

- Un incident = un enregistrement validé avant publication analytique
- Traçabilité complète (audit)
- Restriction par pays pour les points focaux

---

## Module 2 — Accès et interface (20 min)

### Exercice pratique

1. Connexion avec compte de test
2. Changer la langue FR ↔ EN
3. Parcourir le menu latéral
4. Identifier son pays assigné (profil / admin)

### Ressources

- [../user/USER_MANUAL.md](../user/USER_MANUAL.md) § 2–4

---

## Module 3 — Saisie incident (45 min)

### Démonstration

1. **Nouvel enregistrement** — remplir champs obligatoires
2. Vérifier statut **En attente**
3. (Admin) Publier l'incident demo
4. Constater apparition dans Analyses

### Exercice participant

Chaque point focal saisit **1 incident test** pour son pays (données fictives clairement marquées « TEST »).

---

## Module 4 — Import Excel (25 min, optionnel)

1. Présenter le mapping colonnes (colonne A = date)
2. Montrer un échec volontaire (libellé inconnu)
3. Corriger et réimporter

Ressource : [../etl/ETL_DOCUMENTATION.md](../etl/ETL_DOCUMENTATION.md)

---

## Module 5 — Workflow et statuts (20 min)

| Statut | Qui agit | Suite |
|--------|----------|-------|
| En attente | Admin valide | Publié ou Rejeté |
| Publié | — | Visible analytics |
| Rejeté | Contributeur corrige | Resoumission |

---

## Module 6 — Q&R et évaluation

### Quiz rapide (oral)

1. Quelle est la différence entre « en attente » et « publié » ?
2. Un point focal peut-il saisir un incident pour un autre pays ?
3. Où voir le motif de rejet ?

### Critères de réussite

- Connexion autonome
- 1 saisie ou import sans assistance
- Compréhension du cycle validation

---

## Suite de parcours

| Profil | Document suivant |
|--------|------------------|
| Analyste / point focal | [ANALYST_GUIDE.md](ANALYST_GUIDE.md) |
| Admin métier | [../admin/ADMINISTRATOR_MANUAL.md](../admin/ADMINISTRATOR_MANUAL.md) |
| Admin IT | [SYSADMIN_PROCEDURES.md](SYSADMIN_PROCEDURES.md) |

---

## Comptes de démonstration (fixtures)

Mot de passe commun : voir [../FINAL_DELIVERY.md](../FINAL_DELIVERY.md) (`n4n86fgh`).

| Profil | E-mail | Usage formation |
|--------|--------|-----------------|
| Super administrateur | abdoulhafidayeva@gmail.com | Gestion utilisateurs, référentiels, config |
| Staff AUCTC | staff.caert@caert.test | Validation des saisies, boîte de réception |
| Administrateur métier | admin.caert@caert.test | Données tous pays, tableau exécutif |
| Point focal Mali | focal.mali@caert.test | Saisie Mali, lecture autres pays |

> Ne pas utiliser en production. Changer les mots de passe après formation.
