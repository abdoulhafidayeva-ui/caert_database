# Guide de dépannage — CAERT

**Public :** Support, ops, admins  
**Format :** Symptôme → cause → résolution

---

## 1. Connexion et accès

### « Identifiants incorrects »

| Cause | Résolution |
|-------|------------|
| E-mail ou MDP erroné | Vérifier saisie ; reset via « Mot de passe oublié » |
| Compte non vérifié | Cliquer lien e-mail vérification ou contacter admin |
| Compte suspendu | Admin : réactiver (`enable = true`) |

### Redirection infinie login

| Cause | Résolution |
|-------|------------|
| Cookie/session corrompu | Vider cookies site ; navigation privée |
| `APP_SECRET` changé | Reconnecter tous les utilisateurs |

### « Vous n'avez pas accès… »

| Cause | Résolution |
|-------|------------|
| Rôle insuffisant | Admin : attribuer rôle approprié |
| Pays non assigné (point focal) | Admin : renseigner `pays_id` sur compte |

---

## 2. Tableau de bord / DataTables

### Tableau vide ou « Processing… » infini

| Cause | Résolution |
|-------|------------|
| Erreur JS (CDN DataTables) | Vérifier console navigateur ; réseau |
| Session expirée | Reconnecter |
| Erreur serveur 500 | Consulter `var/log/prod.log` |

### Filtres ne fonctionnent pas

| Cause | Résolution |
|-------|------------|
| Select2 non initialisé | Recharger page ; vérifier jQuery |
| Colonne statut : codes internes | Utiliser pending/published/rejected (UI traduite) |

---

## 3. Import Excel

Voir [../etl/ETL_DOCUMENTATION.md](../etl/ETL_DOCUMENTATION.md)

| Symptôme | Action |
|----------|--------|
| « Jeton de sécurité invalide » | Recharger page, relancer import |
| Erreurs « inconnu » massives | Aligner libellés référentiels |
| Import partiel | Corriger lignes en erreur (session `import_errors`) |

---

## 4. Validation / publication

### Incident bloqué « déjà publié ou rejeté »

Normal : workflow terminal. Pour corriger : admin modifie via processus métier (resoumission si rejet).

### Publication sans effet sur KPIs

| Cause | Résolution |
|-------|------------|
| Cache navigateur | Ctrl+F5 |
| Filtre dashboard actif | Réinitialiser filtres |
| `is_published` non à true | Vérifier en BDD |

---

## 5. Analyses / graphiques

### « Chart.js n'est pas chargé »

| Cause | Résolution |
|-------|------------|
| Asset manquant | Vérifier `public/vendor/chart.js/` |
| Bloqueur script | Désactiver extension bloquante |

### Graphique comparaison vide

| Cause | Résolution |
|-------|------------|
| Période sans données publiées | Élargir période ou régions |
| Paramètres manquants | Renseigner période, indicateur, ≥1 région |

### Erreur 403 sur `/search_for_graphique`

Session expirée → reconnecter.

---

## 6. Carte SIG

### Carte blanche

| Cause | Résolution |
|-------|------------|
| Leaflet / OSM bloqué | Vérifier accès Internet tuiles OSM |
| Erreur JS | Console navigateur |

### Pays absent de la carte

Centroïde non configuré pour ce pays → ticket tech lead.

---

## 7. E-mails (vérification, reset MDP)

| Symptôme | Vérification |
|----------|--------------|
| E-mail non reçu | `MAILER_DSN` ; logs Symfony ; spam |
| Lien expiré | Demander renvoi ou reset admin |

Templates : `templates/notification/`

---

## 8. Santé infrastructure

### `/health` → `degraded`

| Check en erreur | Action |
|-----------------|--------|
| `database` | Vérifier MySQL, `DATABASE_URL` |
| `uploads_writable` | Permissions `var/uploads` |
| `logs_writable` | Permissions `var/log` |

### CI PHPUnit échoue

```bash
php bin/phpunit
# Corriger tests ou fixtures avant merge
```

---

## 9. Logs à consulter

| Fichier | Contenu |
|---------|---------|
| `var/log/prod.log` | Erreurs applicatives |
| `var/log/audit.log` | Piste audit Monolog |
| Apache error.log | Erreurs PHP/Apache |

Requête audit SQL :

```sql
SELECT * FROM audit_log WHERE action LIKE 'INCIDENT%' ORDER BY created_at DESC LIMIT 20;
```

---

## 10. Escalade

Si non résolu en 30 min (L2) :

1. Capturer : URL, utilisateur, rôle, message erreur, extrait log
2. Transmettre tech lead avec classification **sensible**
3. Incident sécurité : procédure RSSI UA (hors scope ce guide)
