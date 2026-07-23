# Diagrammes de synthèse — AUCTC

**Objet :** vision claire de l’architecture, des profils d’accès et des parcours opérationnels livrés  

---

## 1. Architecture de déploiement (réseau local)

```
Collaborateurs
  ├── Postes câblés ──┐
  └── Postes Wi‑Fi ───┼──► Switch / VLAN
                      │
                      ▼
              Serveur Windows (IP fixe)
              ├── IIS — site « AUCTC »
              ├── PHP 8.2
              ├── Application Symfony (C:\caert)
              └── MySQL (caert_db)
```

**Lecture :** les utilisateurs accèdent à AUCTC sur le réseau institutionnel ; le serveur héberge l’application et la base. Contrôle : `http://<IP>/health`.

---

## 2. Profils et responsabilités

```
Point focal pays ─────► Région (lecture) · Pays (écriture)
Staff AUCTC ──────────► Région (lecture / validation / écriture)
Administrateur ───────► Accès métier global
Super administrateur ─► Utilisateurs + paramètres système
                              │
                              ▼
                     Données d’incidents
                     (tableau de bord, graphiques, carte)
```

| Profil | Vue par défaut | Écriture |
|--------|----------------|----------|
| Point focal pays | Région assignée (+ 12 mois) | Son pays |
| Staff AUCTC | Région assignée (+ 12 mois) | Sa région |
| Administrateur | Ensemble des données | Sans limite géographique |
| Super administrateur | Ensemble + administration | Comptes et configuration |

---

## 3. Cycle de vie d’un compte

```
Création (admin) ou auto-inscription
              │
              ▼
         Compte créé
              │
    ┌─────────┴─────────┐
    ▼                   ▼
 Actif               Inactif / suspendu
 (connexion OK)      (connexion bloquée)
    │                   │
    └──── activation / suspension ────┘
         (super administrateur)
```

**Lecture :** le super administrateur contrôle qui peut se connecter. Un utilisateur auto-inscrit reste inactif jusqu’à validation.

---

## 4. Parcours d’un incident

```
Saisie manuelle ──┐
                  ├──► En attente ──► Publié ──► Tableau de bord / Graphiques / Carte
Import Excel ─────┘              └──► Rejeté
```

**Lecture :** les données publiées alimentent les outils d’analyse et de visualisation livrés pour l’aide à la décision.

---

## 5. Synthèse des flux livrés

| Flux | Résultat |
|------|----------|
| Accès réseau | Collaborateurs → LAN → serveur AUCTC |
| Droits | Profil → périmètre région/pays → actions autorisées |
| Comptes | Création → activation → utilisation → suspension éventuelle |
| Incidents | Saisie / import → validation → publication → analyse |

---

*Sources Mermaid éditables : dossier `diagrammes/` (fichiers `.mmd`).*  
*Schéma de tables final (modifiable) : `diagrammes/05_schema_tables_AUCTC.drawio` (draw.io / diagrams.net).*
