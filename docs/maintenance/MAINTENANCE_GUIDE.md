# Guide de maintenance — CAERT

**Public :** Équipe ops, support N2, DBA  
**Fréquence :** Opérations récurrentes et planifiées

---

## 1. Maintenance quotidienne

| Tâche | Commande / action | Responsable |
|-------|-------------------|-------------|
| Vérifier santé app | `curl -s https://domaine/health \| jq .status` | Ops |
| Consulter logs erreurs | `tail -f var/log/prod.log` | Ops |
| Vérifier espace disque | `df -h`, `du -sh var/log var/uploads` | Ops |

---

## 2. Maintenance hebdomadaire

| Tâche | Détail |
|-------|--------|
| Revue comptes admin | Lister `ROLE_SUPER_ADMIN` actifs |
| File validation | S’assurer que pendingCount ne stagne pas |
| Test smoke | `bash scripts/deploy/smoke-test.sh $APP_URL` |
| Vérifier backups | Présence dump < 24 h dans `var/backups/` |

---

## 3. Maintenance mensuelle

| Tâche | Détail |
|-------|--------|
| Mises à jour sécurité PHP/OS | Patch CVE, fenêtre maintenance |
| `composer audit` | Vulnérabilités dépendances PHP |
| Rotation logs | Archiver `prod.log`, `audit.log` > 90 jours |
| Revue index MySQL | `EXPLAIN` sur requêtes lentes |
| Test restauration backup | Restaurer sur env. test (DR drill) |

---

## 4. Mises à jour applicatives

### Déploiement version mineure

```bash
git pull origin master
composer install --no-dev --optimize-autoloader
yarn install --frozen-lockfile && yarn build
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
bash scripts/deploy/smoke-test.sh https://votre-domaine
```

### Rollback

1. Restaurer code (tag précédent)
2. `composer install --no-dev`
3. Si migration réversible : `doctrine:migrations:execute --down`
4. Sinon : restaurer dump BDD (voir DR)

---

## 5. Maintenance base de données

```bash
# Optimisation tables (fenêtre maintenance)
mysql -e "OPTIMIZE TABLE all_data, audit_log;"

# Statistiques
php bin/console doctrine:migrations:status
```

Index critiques : voir [../database/DATABASE_DOCUMENTATION.md](../database/DATABASE_DOCUMENTATION.md)

---

## 6. Maintenance référentiels

Les libellés référentiels sont utilisés par l’import Excel (matching exact).

**Avant de renommer un libellé :**

1. Vérifier incidents existants liés
2. Mettre à jour ou créer nouvelle entrée + migration données
3. Communiquer aux points focaux

Accès admin : menu **Référentiels** (super-admin).

---

## 7. Cache Symfony

```bash
# Prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Vérifier permissions var/cache
chown -R www-data:www-data var/
```

---

## 8. Assets frontend

```bash
yarn encore production
# ou dev local
yarn encore dev --watch
```

Fichiers statiques : `public/build/` (Encore) + `public/js/caert-*.js`

---

## 9. Calendrier de maintenance recommandé

| Période | Activité |
|---------|----------|
| Quotidien | Health, logs, backup |
| Hebdomadaire | Smoke, revue pending |
| Mensuel | DR test, patches, audit comptes |
| Trimestriel | Revue sécurité, perf BDD |
| Annuel | Rotation secrets, exercice DR complet |

---

## 10. Contacts escalation

| Niveau | Profil | Action |
|--------|--------|--------|
| L1 | Support utilisateur | Manuel utilisateur, FAQ |
| L2 | Admin CAERT | Manuel admin, troubleshooting |
| L3 | Dev / DBA | Documentation technique, infra |

Voir [../troubleshooting/TROUBLESHOOTING_GUIDE.md](../troubleshooting/TROUBLESHOOTING_GUIDE.md)
