# Manuel utilisateur — CAERT

**Public :** Points focaux pays, contributeurs, analystes  
**Version plateforme :** 2.0.0-prod  
**Langues UI :** Français, English

---

## 1. Introduction

CAERT est la plateforme AUCTC de **reporting d’incidents sécuritaires**. En tant qu’utilisateur, vous pouvez :

- Saisir ou importer des incidents
- Consulter et filtrer les données de votre périmètre
- Suivre le statut de validation de vos enregistrements

---

## 2. Première connexion

1. Ouvrir l’URL institutionnelle CAERT
2. Saisir **e-mail** et **mot de passe**
3. Option **Rester connecté** : à cocher uniquement sur poste personnel (session 7 jours)
4. En cas de premier accès : suivre le lien e-mail pour définir le mot de passe

**Mot de passe oublié :** lien sur la page login → e-mail ou SMS selon configuration.

---

## 3. Langue de l’interface

- Sélecteur **FR / EN** dans l’en-tête
- La préférence est enregistrée sur votre compte
- Les **données** (noms de pays, types d’attaque en base) restent dans leur langue de saisie

---

## 4. Navigation

| Menu | Description | Accès |
|------|-------------|-------|
| Tableau de bord | Liste et filtres incidents | Tous |
| File de validation | Incidents à publier/rejeter | Admin |
| Synthèse | KPIs direction | Admin |
| Analyses | Graphiques tendances | Tous |
| Carte SIG | Carte incidents publiés | Tous |
| Référentiels | Listes paramétrables | Super-admin |
| Utilisateurs | Gestion comptes | Super-admin |

---

## 5. Tableau de bord

### Liste des incidents

Colonnes principales : date, pays, localité, type, statut, actions.

**Statuts :**

| Badge | Signification |
|-------|---------------|
| En attente (orange) | Soumis, en attente validation admin |
| Publié (vert) | Validé, visible dans analyses/carte |
| Rejeté (rouge) | Refusé avec motif |

### Filtres

- Statut, espace, région, pays, cible, type d’attaque, groupe, dates
- **Rechercher** applique les filtres au tableau
- **Réinitialiser** efface les critères

### Actions sur un incident

| Action | Condition |
|--------|-----------|
| Voir / Modifier | Votre pays ou admin |
| Supprimer | Votre enregistrement ou admin |
| Publier / Rejeter | Admin uniquement |

---

## 6. Nouvel enregistrement

1. Cliquer **Nouvel enregistrement**
2. Remplir le formulaire (champs obligatoires marqués)
3. **Enregistrer** → statut **En attente**
4. Un administrateur validera ou rejettera l’incident

**Point focal :** vous ne pouvez enregistrer que des incidents pour **votre pays assigné**.

---

## 7. Import Excel

1. Tableau de bord → **Importer**
2. Sélectionner fichier `.xlsx`
3. **Lancer l'import**

Prérequis :

- Colonne A = date d'attaque
- Libellés référentiels identiques à ceux en base

Guide détaillé : [../etl/ETL_DOCUMENTATION.md](../etl/ETL_DOCUMENTATION.md)

---

## 8. Fiche incident

Accès : clic **Voir** ou **Modifier**

Affiche : détail complet, victimes, référentiels, statut, motif de rejet si applicable.

---

## 9. Analyses (graphiques)

Page **Analyses** :

- Graphiques récapitulatifs (attaques, décès, blessés, cibles)
- Formulaire : période, indicateur, régions → **Générer les graphiques**

Seules les données **publiées** sont incluses.

---

## 10. Carte SIG

Visualisation des incidents publiés par pays. Voir [../gis/GIS_DOCUMENTATION.md](../gis/GIS_DOCUMENTATION.md).

---

## 11. Bonnes pratiques

1. Vérifier les données avant soumission
2. Utiliser des libellés référentiels **exactes** à l'import
3. Ne pas partager vos identifiants
4. Se déconnecter sur poste partagé
5. Signaler toute anomalie à votre administrateur AUCTC

---

## 12. Support

| Besoin | Contact |
|--------|---------|
| Accès compte | Administrateur AUCTC |
| Erreur technique | Support IT (voir troubleshooting) |
| Formation | [../training/ONBOARDING.md](../training/ONBOARDING.md) |

Dépannage : [../troubleshooting/TROUBLESHOOTING_GUIDE.md](../troubleshooting/TROUBLESHOOTING_GUIDE.md)
