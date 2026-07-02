# Documentation sécurité — CAERT

**Classification :** Interne — sensible  
**Public :** RSSI, administrateurs sécurité, ops  
**Dernière révision :** juin 2026

---

## 1. Modèle de menace

CAERT traite des **données sensibles** sur des incidents terroristes. Les risques principaux :

| Risque | Mesure |
|--------|--------|
| Accès non autorisé | Authentification + RBAC + restriction pays |
| Fuite de données | HTTPS, pas d’exposition uploads |
| Modification non tracée | Audit log + CSRF |
| Compte compromis | Remember me limité, suspension compte |
| Injection | Doctrine ORM, validation Symfony |

---

## 2. Authentification

| Mécanisme | Détail |
|-----------|--------|
| Provider | Entity `User` (email) |
| Authenticator | `LoginFormAuthenticator` |
| Mot de passe | Hash auto (bcrypt/argon selon PHP) |
| Remember me | Cookie 7 jours (`604800` s), secret `%kernel.secret%` |
| User checker | `UserChecker` — compte désactivé / non vérifié |

**Connexion :** `/login`  
**Déconnexion :** `/logout` — invalide session et cookie remember me

---

## 3. Hiérarchie des rôles

```
ROLE_USER
    └── ROLE_ADMIN (+ validation, synthèse, tous pays)
            └── ROLE_SUPER_ADMIN (+ ROLE_USERS_MANAGEMENT, gestion comptes)
```

Configuration : `config/packages/security.yaml`

---

## 4. Contrôle d’accès (routes)

| Pattern | Accès |
|---------|-------|
| `/login`, `/install`, `/health`, `/api/pays-by-region` | Public |
| `/users`, `/user/*`, `/register` | `ROLE_SUPER_ADMIN` |
| Toutes les autres routes | `ROLE_USER` minimum |

---

## 5. Autorisation objet (incidents)

Voter : `AllDataVoter`

| Attribut | Règle |
|----------|-------|
| `INCIDENT_VIEW`, `EDIT`, `DELETE` | Admin = tous ; User = pays assigné uniquement |
| `INCIDENT_PUBLISH` | `ROLE_ADMIN` uniquement |

Service complémentaire : `IncidentCountryGuard` (formulaires et import Excel).

---

## 6. Protection CSRF

Activée sur :

- Formulaire de connexion
- Import Excel (`_token` = `import_data`)
- Suppression incident (`delete{id}`)
- Actions utilisateurs (toggle, reset password, delete)

---

## 7. En-têtes HTTP de sécurité

Subscriber : `SecurityHeadersSubscriber`

| En-tête | Valeur |
|---------|--------|
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `SAMEORIGIN` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | geolocation/micro/camera désactivés |
| `X-XSS-Protection` | `0` (désactivé — CSP préférée en évolution) |

---

## 8. Audit et traçabilité

Double journalisation :

1. **Table `audit_log`** — requêtes SQL conformité
2. **Fichier `var/log/audit.log`** — canal Monolog `audit`

Actions tracées :

| Action | Contexte |
|--------|----------|
| `INCIDENT_CREATE` | Nouvel incident |
| `INCIDENT_UPDATE` | Modification |
| `INCIDENT_DELETE` | Suppression |
| `INCIDENT_PUBLISH` | Publication |
| `INCIDENT_REJECT` | Rejet + motif |
| `IMPORT_COMPLETE` | Import Excel (succès/erreurs) |

Chaque entrée : acteur, IP, horodatage, payload JSON optionnel.

---

## 9. Données sensibles

| Donnée | Stockage | Exposition |
|--------|----------|------------|
| Mots de passe | Hash en BDD | Jamais en clair |
| Tokens reset MDP | `users.token` | Usage unique, expiration |
| Fichiers import | `var/uploads/` temporaire | Supprimés après traitement |
| Incidents | MySQL | UI selon rôle/pays |

---

## 10. Recommandations opérationnelles

1. **TLS** obligatoire en production
2. Rotation **APP_SECRET** et mots de passe DB selon politique UA
3. Désactiver comptes : `users.enable = false` (immédiat au login)
4. Ne pas utiliser « Rester connecté » sur postes partagés
5. Sauvegardes chiffrées hors site
6. Accès admin limité au personnel AUCTC autorisé
7. Revue trimestrielle des comptes `ROLE_SUPER_ADMIN`

---

## 11. Évolutions sécurité (roadmap)

- MFA / SSO institutionnel (Azure AD, etc.)
- Content-Security-Policy stricte
- API tokens avec scope par pays
- Chiffrement at-rest MySQL (TDE)
