# Rapport de consultance

**Projet :** Mise à jour et amélioration de la base de données AUCTC  
**Statut :** Système déployé et opérationnel — données historiques importées — formation à planifier  

---

## 1. Résumé exécutif

Ce rapport clôture les travaux de **mise à jour et d’amélioration de la base de données centrale de l’AUCTC**.

Au cours d’un engagement de trois mois, le Consultant a examiné la plateforme existante, renforcé son architecture, amélioré les capacités d’analyse et de visualisation, consolidé la sécurité et la gestion des accès, déployé le système sur le serveur local sécurisé de l’AUCTC, et préparé la documentation de prise en main.

**La base de données AUCTC améliorée est déployée, testée et prête à l’usage opérationnel.** Un jeu de données historiques a été intégré dans la base destinée à la production locale. **La session de formation des utilisateurs reste à organiser.**

Le présent document résume les réalisations au regard des termes de référence, du plan de travail convenu et des livrables contractuels.

---

## 2. Contexte et mandat

L’AUCTC (African Union Counter Terrorism Centre), basé à Alger, est une institution spécialisée de l’Union africaine chargée de renforcer les capacités des États membres à prévenir et combattre le terrorisme. Le Centre dispose d’une base de données centrale pour collecter, stocker et analyser les données relatives aux incidents, tendances et menaces liés au terrorisme en Afrique.

Depuis sa mise en place, l’évolution technologique, les besoins opérationnels et les retours d’utilisateurs ont justifié une modernisation structurée afin d’améliorer la fonctionnalité, l’intégrité des données, les capacités analytiques et l’usage durable du système.

La consultance a été mandatée pour **mettre à jour, renforcer et optimiser** cette base de données.

---

## 3. Objectifs de la mission — matrice de réalisation

| # | Objectif (termes de référence) | Résultat |
|---|-------------------------------|----------|
| 1 | Examiner l’architecture, la structure des données et les fonctionnalités existantes | **Réalisé** — évaluation technique complète |
| 2 | Identifier les lacunes, redondances et goulots d’étranglement | **Réalisé** — traités dans la refonte (index, accès, UX, déploiement) |
| 3 | Concevoir et mettre en œuvre un système de base de données amélioré et évolutif | **Réalisé** — plateforme MySQL 8 / Symfony 7.4 modernisée |
| 4 | Intégrer analytique avancée, visualisation et reporting | **Réalisé** — tableau de bord, graphiques, carte SIG, workflow de validation |
| 5 | Renforcer la sécurité des données, les sauvegardes et la gestion des accès | **Réalisé** — profils RBAC, activation des comptes, journal d’audit, procédures de sauvegarde |
| 6 | Assurer le renforcement des capacités et la documentation pour la pérennité | **Partiellement réalisé** — documentation et supports prêts ; **formation utilisateurs à planifier** |

---

## 4. Périmètre des travaux — phases livrées

### Phase 1 — Démarrage et évaluation (T1)

- Revue approfondie de la base existante (modèles de données, code, interface).
- Évaluation des sources de données, mécanismes de collecte et flux analytiques.
- Consultations avec les parties prenantes pour préciser les besoins fonctionnels et techniques.
- **Livrable :** rapport d’inception (constats, méthodologie, plan de travail actualisé).

### Phase 2 — Conception et développement (T2–T4)

- Refonte de l’architecture sur une stack moderne et sécurisée (**MySQL 8**, PHP 8.2, Symfony 7.4).
- Modèle de données optimisé, index de performance et contrôles d’intégrité.
- Voies d’ingestion : formulaires structurés et **import Excel (ETL)** avec validation.
- **Analyse géospatiale :** carte interactive Leaflet et agrégation des incidents publiés.
- **Tableaux de bord et reporting :** tableau de bord opérationnel, synthèse exécutive, graphiques comparatifs.
- Modèle d’accès aligné sur les rôles AUCTC (Point focal, Staff, Administrateur, Super administrateur).
- **Livrables :** document de conception technique ; prototype ; système final.

### Phase 3 — Tests et déploiement (T4–T5)

- Validation fonctionnelle et intégrée des parcours clés (connexion, saisie, import, validation, analytique, carte, gestion des utilisateurs).
- Alignement du schéma de production sans perte d’intégrité structurelle.
- **Déploiement sur le serveur Windows local sécurisé de l’AUCTC** (IIS + MySQL), accessible sur le réseau institutionnel (câble et Wi‑Fi).
- Préparation et chargement d’une **base de production** comprenant référentiels, compte super administrateur et **données historiques d’incidents**.
- Sonde de santé et contrôles opérationnels.
- **Livrables :** système final testé et déployé ; base prête pour exploitation locale.

### Phase 4 — Documentation et passation (T6–T7)

- Documentation technique : architecture, déploiement, exploitation, maintenance et dépannage.
- Manuels utilisateur et administrateur ; guides d’onboarding et d’analyste ; supports de formation **préparés**.
- Rapport de consultance (présent document).
- **À planifier :** session(s) de formation des utilisateurs et administrateurs sur site ou à distance.
- **Livrables :** pack documentation (livré) ; formation (supports prêts — session non encore réalisée).

---

## 5. Réalisations principales

### 5.1 Plateforme modernisée et opérationnelle

- Application présentée sous l’identité **AUCTC** (les chemins techniques conservent l’identifiant historique CAERT lorsque nécessaire pour la continuité IT).
- Interface bilingue (français / anglais).
- Cycle de vie complet des incidents : saisie → validation → publication → analyse.
- Sonde de santé pour le suivi opérationnel.

### 5.2 Analytique, visualisation et reporting

- **Tableau de bord** adapté aux profils, avec fenêtre opérationnelle par défaut (région et 12 mois).
- **Graphiques et indicateurs** centrés sur les **totaux** (nombre d’attaques, total décès, total blessés), adaptés aux données historiques et aux KPI.
- **Carte SIG** pour la visualisation géographique, avec filtre d’année.
- **Synthèse opérationnelle** pour le pilotage, avec filtre d’année.

### 5.3 Intégrité des données, ingestion et import historique

- Formulaires structurés avec contrôle région / pays.
- Import Excel sécurisé (modèle CAERT), avec validation et traçabilité.
- Référentiels (régions, pays, types d’attaque, cibles, etc.) prêts pour la production, y compris valeurs neutres (« Non renseigné ») pour l’historique.
- Indexation orientée performance.

#### Import du fichier historique `Data.xlsx`

Un fichier d’incidents historiques (feuille **Data**, environ **9 800** lignes, période **2021–2024**) a été traité pour alimenter la base locale destinée à la production.

Travaux réalisés :

- analyse du format source (colonnes en anglais, structure simplifiée) par rapport au modèle métier AUCTC ;
- développement d’une **commande d’import dédiée** (hors interface) pour traiter le volume sans timeout navigateur ;
- **table d’alias** anglais → français pour pays, cibles et moyens d’attaque (ex. Cameroon → CAMEROUN, SALW → Armes légères, Civilians → Civils) ;
- création contrôlée des groupes perpétrateurs absents et des libellés manquants (« Non renseigné », etc.) ;
- conservation des **totaux** décès / blessés du fichier (le détail civil / militaire / terroriste n’étant pas disponible dans la source) ;
- import en statut **publié**, avec identifiants **auto-incrémentés** (indépendants de l’ID Excel) ;
- contrôles d’intégrité (dates, pays, régions) ; en cas d’écart région fichier / pays, **le pays en base fait foi**.

Résultat : base prête à l’exploitation locale, avec historique disponible pour la synthèse, la carte et les analyses sur totaux.

### 5.4 Sécurité et gestion des accès

| Profil | Périmètre opérationnel |
|--------|------------------------|
| Point focal pays | Vue par défaut : région assignée ; écriture : son pays |
| Staff AUCTC | Suivi et validation au niveau régional |
| Administrateur | Accès métier élargi |
| Super administrateur | Gestion des utilisateurs et paramètres système |

Contrôles complémentaires livrés :

- **activation / suspension** des comptes par le super administrateur (y compris auto-inscription en attente) ;
- annuaire utilisateurs avec recherche, filtres et pagination ;
- réinitialisation de mot de passe (e-mail optionnel) ;
- journal d’audit des actions sensibles ;
- approche de sauvegarde documentée (base et fichiers uploadés).

### 5.5 Déploiement et continuité institutionnelle

- Hébergement sur le **serveur Windows local** de l’AUCTC (IIS, PHP, MySQL).
- Accès LAN pour le personnel (câble et Wi‑Fi), IP fixe et procédures de redémarrage documentées.
- Guides de déploiement, lancement et reprise pour la prise en main IT.

---

## 6. Livrables contractuels — état

| Livrable | Échéance | État |
|----------|----------|------|
| Rapport d’inception | Semaine 2 | **Livré** |
| Document de conception technique | Semaine 4 | **Livré** |
| Prototype de base de données | Semaine 8 | **Livré** |
| Système final de base de données | Semaine 12 | **Livré — déployé et opérationnel** (données historiques chargées) |
| Formation et documentation | Semaine 13 | **Documentation livrée** — **formation à planifier** |
| Rapport de consultance | Semaine 14 | **Livré (présent document)** |

### Alignement avec le plan de travail (T1–T7)

| Code | Tâche | Livrable | État |
|------|-------|----------|------|
| T1 | Inception et évaluation du système | Rapport d’inception | Terminé |
| T2 | Conception technique et architecture | Document de conception | Terminé |
| T3 | Développement du prototype | Prototype | Terminé |
| T4 | Mise en œuvre complète | Système final | Terminé |
| T5 | Tests et validation | Rapports / preuves de tests | Terminé |
| T6 | Formation et documentation | Manuels et formation | Documentation terminée — **formation en attente** |
| T7 | Rapport final et passation | Rapport de consultance | Terminé |

---

## 7. Documents d’accompagnement

Rapport (présent document) + pièces dans `essentiels/` :

| # | Document | Fichier |
|---|----------|---------|
| 1 | Guide de déploiement serveur | `essentiels/01_Guide_Deploiement_Serveur.docx` |
| 2 | Diagrammes de synthèse | `essentiels/02_Diagrammes_Synthese.docx` |
| 3 | Import des données historiques | `essentiels/03_Import_Donnees_Historiques.docx` |
| 4 | Manuel utilisateur | `essentiels/04_Manuel_Utilisateur.docx` |
| 5 | Manuel administrateur | `essentiels/05_Manuel_Administrateur.docx` |
| 6 | Guide analyste | `essentiels/06_Guide_Analyste.docx` |
| 7 | Supports de formation | `essentiels/07_Supports_Formation.docx` |
| 8 | Lancement / accès / redémarrage | `essentiels/08_Lancement_Acces_Redemarrage.docx` |

Dossier : `deliverables/consultance_juillet_2026/`  
— Rapport : `word/AUCTC_Rapport_Consultance_2026.docx`  
— Pièces jointes : `essentiels/`

---

## 8. Enseignements tirés

1. **La clarté des rôles améliore la qualité des données** — l’alignement des profils (focal / staff / admin) sur les périmètres région et pays réduit le bruit et renforce le focus opérationnel.  
2. **L’hébergement local convient au contexte AUCTC** — un déploiement Windows sur le LAN offre autonomie, maîtrise et accès prévisible pour le personnel du Centre.  
3. **La documentation fait partie du produit** — manuels, diagrammes et guides de déploiement sont essentiels à la pérennité au-delà de la consultance.  
4. **L’historique impose des indicateurs réalistes** — lorsque la source ne fournit que des totaux, les analyses doivent s’appuyer sur ces totaux plutôt que sur une ventilation inventée.

---

## 9. Recommandations pour la pérennité

1. Désigner des référents Super administrateur et IT.  
2. Exécuter des sauvegardes régulières (base et fichiers).  
3. Maintenir le SMTP pour les comptes et mots de passe.  
4. **Organiser la formation** des utilisateurs avec les supports déjà remis.  
5. Suivre la qualité des données via la file de validation et les tableaux de bord.

---

## 10. Conclusion

Conformément au contrat et à ses annexes, le Consultant a achevé la mise à jour et l’amélioration de la base de données AUCTC dans le délai de trois mois.

Le Centre dispose désormais d’une plateforme **modernisée, sécurisée et analytiquement capable**, déployée sur son serveur local, avec une gouvernance des accès claire, un historique d’incidents chargé, et un ensemble documentaire pour un usage durable. **Il reste à planifier et réaliser la formation des utilisateurs.**

Le Consultant reste disponible, conformément aux termes de référence, pour un appui post-déploiement de clarification durant la fenêtre convenue, si le Client le souhaite.

---

*Confidentiel — African Union Counter Terrorism Centre (AUCTC) / Union africaine.*
