# Procédures administration système — CAERT

**Public :** Administrateurs IT, DevOps, DBA  
**Prérequis :** [../infrastructure/INFRASTRUCTURE_DOCUMENTATION.md](../infrastructure/INFRASTRUCTURE_DOCUMENTATION.md)

---

## 1. Procédure — Déploiement production

### Pré-déploiement

- [ ] Backup BDD récent (< 24 h)
- [ ] Backup `var/uploads`
- [ ] Fenêtre maintenance communiquée
- [ ] Tag git / version identifiée

### Exécution

```bash
bash scripts/deploy/deploy-prod.sh
RUN_SMOKE=1 APP_URL=https://caert.example.org bash scripts/deploy/deploy-prod.sh
```

### Post-déploiement

- [ ] `/health` → healthy
- [ ] Login admin OK
- [ ] File validation accessible
- [ ] `doctrine:migrations:status` → latest
- [ ] Pas d'erreur dans `prod.log` (15 min)

Référence : [../DEPLOYMENT.md](../DEPLOYMENT.md)

---

## 2. Procédure — Sauvegarde

### Quotidienne (cron)

```cron
0 2 * * * cd /var/www/caert && bash scripts/deploy/backup-database.sh
15 2 * * * cd /var/www/caert && bash scripts/deploy/backup-uploads.sh
```

### Vérification

```bash
ls -la var/backups/
# Fichier daté du jour présent
```

### Rétention

14 jours par défaut (script backup) — ajuster selon politique UA.

---

## 3. Procédure — Restauration (DR)

```bash
bash scripts/deploy/disaster-recovery-restore.sh /chemin/vers/dump.sql
```

Post-restore :

1. `php bin/console doctrine:migrations:status`
2. Smoke test complet
3. Vérifier intégrité comptes admin
4. Documenter incident DR

RPO/RTO : [../OPERATIONS.md](../OPERATIONS.md)

---

## 4. Procédure — Création compte admin

Déléguée au **super-admin métier** via UI (`/register`).

Rôle IT :

1. Vérifier SMTP opérationnel (`MAILER_DSN`)
2. Confirmer réception e-mail activation
3. Ne **jamais** transmettre mot de passe en clair par e-mail non chiffré

---

## 5. Procédure — Suspension compte compromis

1. Super-admin : **Suspendre** compte (UI)
2. Vérifier `users.enable = 0` en BDD
3. Consulter `audit_log` actions récentes du compte
4. Rotation MDP admin si suspicion élévation privilèges
5. Rapport RSSI selon procédure UA

---

## 6. Procédure — Migration base de données

```bash
# Toujours sur backup récent
php bin/console doctrine:migrations:migrate --no-interaction --dry-run
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate --skip-sync
```

En cas d'échec : **ne pas** laisser migration partielle — restaurer backup.

---

## 7. Procédure — Monitoring alertes

| Alerte | Seuil | Action |
|--------|-------|--------|
| `/health` ≠ 200 | Immédiat | Vérifier MySQL, permissions var/ |
| Espace disque > 85 % | 1 h | Purger logs, agrandir volume |
| Erreurs 500 répétées | 15 min | Analyser `prod.log`, rollback si régression |
| pendingCount > 100 | 24 h | Informer admin métier |

---

## 8. Procédure — Mise à jour dépendances

### PHP (Composer)

```bash
composer update --with-dependencies
composer audit
php bin/phpunit
# Déployer en pré-prod d'abord
```

### Frontend

```bash
yarn upgrade
yarn build
# Tester pages critiques : login, dashboard, map, graphique
```

---

## 9. Procédure — Logs et conformité

### Export audit trimestriel

```sql
SELECT al.*, u.email
FROM audit_log al
LEFT JOIN users u ON al.actor_id = u.id
WHERE al.created_at >= '2026-01-01'
INTO OUTFILE '/secure/path/audit_export.csv'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

Adapter selon politique export UA (chiffrement, stockage).

### Archivage logs

```bash
gzip var/log/prod.log.$(date +%Y%m)
gzip var/log/audit.log.$(date +%Y%m)
```

---

## 10. Procédure — Support niveau 3

1. Reproduire en environnement test
2. Consulter [../troubleshooting/TROUBLESHOOTING_GUIDE.md](../troubleshooting/TROUBLESHOOTING_GUIDE.md)
3. Si code : ouvrir ticket dev avec logs + steps
4. Communication utilisateurs via admin métier

---

## 11. Checklist sécurité trimestrielle

- [ ] Revue comptes `ROLE_SUPER_ADMIN`
- [ ] Rotation `APP_SECRET` (si politique)
- [ ] Vérifier TLS certificat expiry
- [ ] `composer audit` sans critical
- [ ] Test restauration backup
- [ ] Revue accès SSH / DB

Documentation sécurité : [../security/SECURITY_DOCUMENTATION.md](../security/SECURITY_DOCUMENTATION.md)

---

## 12. Contacts

| Domaine | Rôle |
|---------|------|
| Application | Tech lead CAERT |
| Infrastructure | Ops UA / hébergeur |
| Sécurité | RSSI AUCTC |
| Métier | Admin plateforme AUCTC |
