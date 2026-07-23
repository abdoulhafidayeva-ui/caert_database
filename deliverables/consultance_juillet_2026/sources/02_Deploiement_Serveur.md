# Guide de déploiement serveur — AUCTC

**Public :** administrateur IT / tiers chargé d’installer ou de **réinstaller** le système  
**Objectif :** suivre ce document **écran par écran**, sans connaissance préalable du projet  
**Serveur :** Windows Server ou Windows 10/11 Pro  
**Application :** **AUCTC** (chemins techniques : `C:\caert`, base `caert_db`)  
**Exemple d’IP dans tout le guide :** `192.168.10.20` — **remplacez par l’IP réelle du serveur**

**Durée estimée :** 2 à 4 heures (première installation).

---

## Comment lire ce guide

| Symbole | Signification |
|---------|----------------|
| **À l’écran** | Actions clic par clic dans Windows |
| **PowerShell** | Commandes à coller (ouvrir PowerShell **en administrateur** si indiqué) |
| Capture | Image de référence — votre écran doit ressembler à ça |

Deux façons d’amener le code sur le serveur :

| Méthode | Quand | Sur le serveur |
|---------|--------|----------------|
| **A — ZIP** (recommandée pour un tiers) | Vous recevez `caert-deploy.zip` | Pas besoin de Git / Composer / Node |
| **B — Git** | Accès dépôt privé + Deploy Key | Git + Composer + Node requis |

---

## Vue d’ensemble

```
PARTIE A — Préparer l’archive (PC de build)     ← sautez si on vous a déjà donné le ZIP
PARTIE B — Serveur Windows
  B1  IP fixe
  B2  PHP 8.2
  B3  MySQL 8
  B4  IIS + URL Rewrite
  B5  Extraire le projet dans C:\caert
  B6  Fichier .env
  B7  Base + migrations + fixtures + super admin
  B8  Site IIS « AUCTC »
  B9  Tests /health et login
  B10 Accès LAN / Wi‑Fi
  B11 Démarrage automatique
```

---

# PARTIE A — Préparer `caert-deploy.zip` (PC de build)

> Si vous avez **déjà** le fichier `caert-deploy.zip` sur une clé USB, **passez directement à la PARTIE B**.

### À l’écran

1. Sur un PC où le code source AUCTC est disponible, ouvrez **PowerShell**.  
2. Allez dans le dossier du projet, par exemple :

```powershell
cd C:\dev_land\office\caert
```

3. Lancez le script d’empaquetage :

```powershell
powershell -ExecutionPolicy Bypass -File scripts\deploy\package-for-windows-server.ps1
```

4. À la fin, vous devez voir un message du type : **`OK. Copiez C:\caert-deploy.zip sur le serveur...`**

5. Copiez `C:\caert-deploy.zip` sur une **clé USB** (ou partage réseau) vers le serveur.

---

# PARTIE B — Installation sur le serveur Windows

> Toutes les étapes suivantes se font **sur le serveur**, de préférence branché en **câble Ethernet**.

---

## B1. Configurer l’adresse IP fixe

### Ce que vous allez faire

Donner au serveur une adresse qui **ne change jamais** (ex. `192.168.10.20`), **sans perdre Internet**.

### Avant de changer — noter la config actuelle

1. Branchez le **câble Ethernet**.  
2. Ouvrez **PowerShell** et tapez :

```powershell
ipconfig /all
```

3. Notez pour la **carte Ethernet** :

| Champ | Votre valeur (à noter) |
|-------|-------------------------|
| Adresse IPv4 | |
| Masque | |
| **Passerelle par défaut** | |
| **Serveurs DNS** | |

> La **passerelle** et le **DNS** doivent rester **identiques** après le passage en IP fixe. Sinon : plus d’Internet.

### À l’écran — appliquer l’IP fixe

1. **Démarrer** → tapez **`ncpa.cpl`** → **Entrée**.  
2. **Clic droit** sur **Ethernet** → **Propriétés**.  
3. Double-clic sur **Protocole Internet version 4 (TCP/IPv4)**.  
4. Cochez **Utiliser l’adresse IP suivante**.  
5. Saisissez (adaptez à votre réseau) :

| Champ | Exemple |
|-------|---------|
| Adresse IP | `192.168.10.20` |
| Masque de sous-réseau | `255.255.255.0` |
| Passerelle par défaut | `192.168.10.1` *(identique au DHCP)* |
| DNS préféré | `192.168.10.1` *(identique au DHCP)* |

6. Cliquez **OK** → **OK**.

![Configurer l'adresse IP fixe (IPv4)](images/hosting-step-ip-fixe.png)

*Capture de référence — fenêtre « Propriétés TCP/IPv4 » avec IP fixe renseignée.*

### Vérification

```powershell
ipconfig
ping 8.8.8.8
ping www.google.com
```

| Test | Résultat attendu |
|------|------------------|
| `ipconfig` | Votre nouvelle IP fixe |
| `ping 8.8.8.8` | Réponses (route Internet OK) |
| `ping www.google.com` | Réponses (DNS OK) |

---

## B2. Installer PHP 8.2

### À l’écran

1. Ouvrez **Edge** → https://windows.php.net/download/  
2. Section **PHP 8.2** → ligne **VS16 x64 Thread Safe** → téléchargez le **Zip**.  
3. **Clic droit** sur le zip → **Extraire tout…** → destination : **`C:\PHP`**.  
4. Dans `C:\PHP`, copiez `php.ini-production` → renommez la copie en **`php.ini`**.  
5. Ouvrez `php.ini` avec le **Bloc-notes**.  
6. Vérifiez / décommentez (enlevez le `;` en début de ligne) :

```ini
extension_dir = "ext"
extension=curl
extension=fileinfo
extension=intl
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=zip
```

7. **Enregistrer** le fichier.

### Ajouter PHP au PATH

1. **Démarrer** → tapez **`variables d'environnement`** → **Modifier les variables d'environnement système**.  
2. **Variables d'environnement…** → **Path** (système) → **Modifier** → **Nouveau** → `C:\PHP` → **OK**.  
3. **Fermez et rouvrez** PowerShell.

### Vérification

```powershell
php -v
php -m | findstr "intl pdo_mysql"
```

Vous devez voir **PHP 8.2.x** et les modules `intl` / `pdo_mysql`.

---

## B3. Installer MySQL 8

### À l’écran

1. Edge → https://dev.mysql.com/downloads/installer/  
2. Téléchargez et lancez l’installeur **MSI**.  
3. Type : **Server only** → **Next** → **Execute**.  
4. Configuration :
   - Port **3306**
   - Mot de passe **root** → **notez-le**
   - Cochez **Start MySQL Server at System Startup**
5. **Execute** → **Finish**.

### Vérification

```powershell
Get-Service MySQL*
```

Statut attendu : **Running**. Si arrêté :

```powershell
Start-Service MySQL80
```

*(Le nom peut être `MySQL` ou `MySQL80` — regardez le résultat de `Get-Service MySQL*`.)*

---

## B4. Installer IIS et URL Rewrite

### B4.1 Activer IIS

1. **Démarrer** → tapez **`optionalfeatures`** → **Entrée**.  
2. Cochez **Services Internet (IIS)** avec au minimum :
   - Console de gestion IIS  
   - Contenu statique  
   - Redirection HTTP  
3. **OK** → attendez la fin.

### B4.2 Installer URL Rewrite (obligatoire)

1. Edge → https://www.iis.net/downloads/microsoft/url-rewrite  
2. **Install this extension** → installez.  
3. Fermez / rouvrez IIS si besoin.

### B4.3 Lier PHP à IIS

**PowerShell administrateur :**

```powershell
New-WebHandler -Name "PHP_via_FastCGI" -Path "*.php" -Verb "*" -Modules "FastCgiModule" -ScriptProcessor "C:\PHP\php-cgi.exe" -ResourceType Either
```

Si le gestionnaire existe déjà, Windows affichera une erreur « exists » : c’est normal, passez à la suite.

---

## B5. Extraire le projet dans `C:\caert`

### À l’écran

1. Copiez `caert-deploy.zip` sur le serveur.  
2. **Clic droit** → **Extraire tout…**.  
3. Destination : **`C:\caert`**.  
4. Vérifiez la présence de :

| Élément | Obligatoire |
|---------|-------------|
| `C:\caert\vendor\` | **Oui** |
| `C:\caert\public\` | **Oui** |
| `C:\caert\public\build\` | **Oui** |
| `C:\caert\bin\console` | **Oui** |
| `C:\caert\composer.json` | **Oui** |

![Dossier C:\caert après extraction](images/hosting-step-dossier-caert.png)

*Capture de référence — le dossier `C:\caert` doit contenir au minimum `vendor`, `public`, `src`, `var`, `bin`, `.env` (à créer si absent).*

> Si `vendor` est absent : l’archive est incomplète. Refaites la **PARTIE A** sur le PC de build.

---

## B6. Créer le fichier `.env`

### À l’écran

1. Explorateur → `C:\caert`.  
2. Si `.env.example` existe : **copiez** → renommez la copie en **`.env`**.  
3. Sinon : créez un fichier texte nommé **`.env`** (attention : pas `.env.txt`).  
4. Ouvrez `.env` avec le **Bloc-notes** et collez (adaptez) :

```dotenv
APP_ENV=prod
APP_SECRET=collez_ici_une_chaine_aleatoire
DATABASE_URL="mysql://caert:MOT_DE_PASSE_CAERT@127.0.0.1:3306/caert_db?serverVersion=8.0&charset=utf8mb4"
APP_URL=http://192.168.10.20
MAILER_DSN=null://null
```

5. Générez `APP_SECRET` :

```powershell
php -r "echo bin2hex(random_bytes(16));"
```

6. Collez le résultat à la place de `collez_ici_une_chaine_aleatoire`.  
7. Remplacez `MOT_DE_PASSE_CAERT` par le mot de passe choisi pour l’utilisateur MySQL (étape B7).  
8. **Enregistrez**.

---

## B7. Base de données, schéma, référentiels, super admin

### B7.1 Créer la base MySQL

1. **Démarrer** → **MySQL 8.0 Command Line Client**.  
2. Mot de passe **root**.  
3. Exécutez **ligne par ligne** (même mot de passe que dans `.env`) :

```sql
CREATE DATABASE caert_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'caert'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_CAERT';
GRANT ALL PRIVILEGES ON caert_db.* TO 'caert'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### B7.2 Migrations + fixtures + cache

```powershell
cd C:\caert
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --group=prod --no-interaction
php bin/console cache:clear --env=prod
```

### B7.3 Créer le super administrateur

```powershell
cd C:\caert
$env:CAERT_SUPER_ADMIN_PASSWORD = 'ChoisissezUnMotDePasseFort!'
php bin/console app:create-super-admin "admin@exemple.org" --name="Administrateur" --first-name="AUCTC"
Remove-Item Env:CAERT_SUPER_ADMIN_PASSWORD
```

> Remplacez l’e-mail et le mot de passe. Notez-les : c’est le compte de première connexion.

### B7.4 Droits d’écriture

```powershell
icacls C:\caert\var /grant "IIS_IUSRS:(OI)(CI)M" /T
icacls C:\caert\var /grant "IUSR:(OI)(CI)M" /T
```

### B7.5 (Optionnel) Import historique `Data.xlsx`

```powershell
php bin/console app:import-data-xlsx "C:\chemin\Data.xlsx" --user="admin@exemple.org" --dry-run
php bin/console app:import-data-xlsx "C:\chemin\Data.xlsx" --user="admin@exemple.org"
```

Voir aussi `03_Import_Donnees_Historiques.docx`.

---

## B8. Créer le site IIS « AUCTC »

### À l’écran

1. **Démarrer** → tapez **`inetmgr`** → **Entrée** (Gestionnaire IIS).  
2. À gauche : nom du serveur → **Sites**.  
3. À droite : **Ajouter un site Web…**.  
4. Remplissez **exactement** :

| Champ | Valeur |
|-------|--------|
| Nom du site | `AUCTC` |
| Chemin physique | `C:\caert\public` |
| Type | `http` |
| Adresse IP | `192.168.10.20` (ou « Toutes non attribuées ») |
| Port | `80` |
| Nom d’hôte | *(vide)* |

5. Cliquez **OK**.  
6. Sélectionnez le site **AUCTC** → **Démarrer** si besoin.

![Ajouter un site Web dans IIS](images/hosting-step-iis-site.png)

*Capture de référence — nom du site et pool d’applications : **AUCTC** (chemin technique : `C:\caert\public`).*

### Ouvrir le pare-feu (port 80)

**PowerShell administrateur :**

```powershell
New-NetFirewallRule -DisplayName "AUCTC HTTP LAN" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow
```

---

## B9. Vérifier que ça marche

### Sur le serveur

1. Ouvrez **Edge**.  
2. Allez sur : `http://127.0.0.1/health`  
3. Vous devez voir un JSON du type : `{"status":"healthy"}`

![Test /health OK dans le navigateur](images/hosting-step-health-ok.png)

*Capture de référence — réponse healthy sur `/health`.*

4. Allez sur : `http://127.0.0.1/`  
5. Page de **login AUCTC** → connectez-vous avec le super admin créé en B7.3.

---

## B10. Accès depuis les postes du LAN / Wi‑Fi

### URL à communiquer

```
http://192.168.10.20
```

### Tests

| Test | Action |
|------|--------|
| Poste en **câble** | Ouvrir l’URL → login |
| Poste en **Wi‑Fi** | Même réseau (pas invité) → même URL |

Si le câble marche et pas le Wi‑Fi : vérifier avec l’IT que le Wi‑Fi est sur le **même sous-réseau** et que l’**isolation client** est désactivée.

Détails complémentaires : `08_Lancement_Acces_Redemarrage.docx`.

---

## B11. Démarrage automatique après reboot

```powershell
Set-Service W3SVC -StartupType Automatic
Set-Service MySQL80 -StartupType Automatic
```

Après un redémarrage du serveur : retester `http://127.0.0.1/health` puis le login.

---

## Checklist finale (cochez)

| # | Contrôle | ☐ |
|---|----------|---|
| 1 | `http://127.0.0.1/health` → healthy | |
| 2 | Page de login sur `http://127.0.0.1/` | |
| 3 | Connexion super admin | |
| 4 | Menu Utilisateurs visible | |
| 5 | Accès depuis un autre PC du LAN | |
| 6 | Analyses / Carte / Synthèse accessibles | |

---

## Mise à jour ultérieure (sans tout réinstaller)

1. Sauvegarder la base :

```powershell
mysqldump -u caert -p caert_db > C:\backups\caert_db_backup.sql
```

2. Remplacer le code (nouveau ZIP extrait dans `C:\caert`, en conservant `.env` et `var\uploads`).  
3. Puis :

```powershell
cd C:\caert
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
```

4. Revérifier `/health` et une connexion.

---

## Dépannage rapide

| Symptôme | Que faire |
|----------|-----------|
| Page blanche | Racine IIS = `C:\caert\public` ; URL Rewrite installé ; `web.config` présent |
| Erreur 500 | `php -m` (intl, pdo_mysql) ; logs dans `C:\caert\var\log` |
| `/health` KO | MySQL démarré ; `.env` / `DATABASE_URL` ; droits sur `var\` |
| Pas d’accès LAN | Pare-feu port 80 ; même réseau ; `ping` vers l’IP serveur |
| Connexion refusée | Compte **actif** + mot de passe défini |
| Pas de CSS/JS | Archive sans `public\build` → refaire la PARTIE A |

---

## Chemins récapitulatifs

| Élément | Valeur |
|---------|--------|
| Application | `C:\caert` |
| Racine web IIS | `C:\caert\public` |
| PHP | `C:\PHP` |
| Base / user | `caert_db` / `caert` |
| Site IIS | `AUCTC` |
| Santé | `http://<IP>/health` |
| Login | `http://<IP>/` |

---

*Guide pas à pas illustré — (re)déploiement AUCTC sur serveur Windows local.*
