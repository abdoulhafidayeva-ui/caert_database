# Manuel administrateur — CAERT

**Public :** Administrateurs métier (`ROLE_ADMIN`), super-administrateurs (`ROLE_SUPER_ADMIN`)  
**Classification :** Interne — sensible

---

## 1. Rôles et responsabilités

| Rôle | Capacités |
|------|-----------|
| **ROLE_USER** | Saisie/consultation périmètre pays |
| **ROLE_ADMIN** | Tous pays + validation + synthèse + analyses |
| **ROLE_SUPER_ADMIN** | + gestion utilisateurs + référentiels + paramétrage |

---

## 2. Validation des incidents

### File de validation

Menu **File de validation** (`/workflow/inbox`)

- Liste incidents `is_published IS NULL`
- Tri par date de saisie
- Accès fiche → vérifier cohérence → publier ou rejeter

### Publier

Depuis le tableau de bord ou la fiche :

- Action **Publier** → `is_published = true`
- Incident visible dans KPIs, carte, analyses
- Audit : `INCIDENT_PUBLISH`

### Rejeter

- Action **Rejeter** → saisir **motif obligatoire**
- `is_published = false`, `objet_rejet` renseigné
- Le contributeur peut consulter le motif et resoumettre (modification → repasse en attente)

---

## 3. Synthèse direction

Menu **Synthèse** (`/executive`)

| KPI | Source |
|-----|--------|
| Publiés | COUNT `is_published = true` |
| En attente | COUNT pending |
| Rejetés | COUNT rejetés avec motif |
| Décès | SUM `total_deces` publiés |

**Top pays :** 10 pays avec le plus d'incidents publiés (pas tous les pays).

---

## 4. Gestion des utilisateurs

Accès : **Utilisateurs** (super-admin)

### Créer un compte

1. **Ajouter** → formulaire (identité, e-mail, rôle, pays, profil)
2. E-mail automatique → lien définition mot de passe (24 h)

### Modifier

- Rôles, pays assigné, profil, organisation
- Locale UI (FR/EN)

### Suspendre / activer

- **Suspendre** : `enable = false` — connexion refusée
- **Activer** : inverse

### Réinitialiser mot de passe

- Modale depuis liste utilisateurs
- Option envoi e-mail au utilisateur
- Audit tracé

### Supprimer

- Confirmation requise
- Impossible de supprimer son propre compte

---

## 5. Référentiels

Menu **Paramètres** → sous-menus :

- Types d'attaques, Cibles, Moyens, Matériels, Groupes, Espaces, Matériaux récupérés

Pour chaque entrée : libellé unique, créateur tracé.

**Attention :** renommer un libellé casse les imports Excel utilisant l'ancien nom.

---

## 6. Paramétrage application

Routes installation (première mise en service) :

- `/install` — nom application
- `/first-user` — premier super-admin

Ensuite : modifications via BDD ou évolution future écran paramètres.

---

## 7. Audit et conformité

Consulter la piste d'audit :

```sql
SELECT al.created_at, u.email, al.action, al.entity_type, al.entity_id, al.payload
FROM audit_log al
LEFT JOIN users u ON al.actor_id = u.id
ORDER BY al.created_at DESC
LIMIT 100;
```

Actions sensibles : publication, rejet, suppression, import, création compte.

Documentation : [../security/SECURITY_DOCUMENTATION.md](../security/SECURITY_DOCUMENTATION.md)

---

## 8. Import Excel (vue admin)

- Vérifier file validation après import massif d'un point focal
- Traiter les rejets avec feedback constructif
- S'assurer que les référentiels sont à jour **avant** campagnes d'import

---

## 9. Checklist admin hebdomadaire

- [ ] Traiter incidents en attente (> 48 h)
- [ ] Vérifier KPI pending dans synthèse
- [ ] Répondre demandes accès / reset MDP
- [ ] Contrôler nouveaux comptes inactifs non vérifiés

---

## 10. Escalade technique

| Sujet | Document |
|-------|----------|
| Déploiement | [../DEPLOYMENT.md](../DEPLOYMENT.md) |
| Maintenance | [../maintenance/MAINTENANCE_GUIDE.md](../maintenance/MAINTENANCE_GUIDE.md) |
| Dépannage | [../troubleshooting/TROUBLESHOOTING_GUIDE.md](../troubleshooting/TROUBLESHOOTING_GUIDE.md) |
| Sécurité | [../security/SECURITY_DOCUMENTATION.md](../security/SECURITY_DOCUMENTATION.md) |

Formation workflows : [../training/ANALYST_GUIDE.md](../training/ANALYST_GUIDE.md)
