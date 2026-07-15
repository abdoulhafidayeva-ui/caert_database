# AUCTC — Installation sur serveur Windows local (guide pas à pas)

**Public :** administrateur IT, responsable du point focal  
**Application :** **AUCTC** (plateforme de reporting d'incidents — Union Africaine)  
**Serveur :** Windows Server ou Windows 10/11 Pro (comme votre machine actuelle)  
**Accès employés :** `http://192.168.10.20` (HTTP — réseau local)  
**Dépôt GitHub :** `https://github.com/abdoulhafidayeva-ui/caert_database.git`

> **Nom du projet :** l'application s'appelle **AUCTC** pour les utilisateurs. Le dépôt GitHub et les chemins techniques (`C:\caert`, `caert_db`, `caert-deploy.zip`) conservent l'ancien identifiant interne **caert** — ne pas les renommer sauf décision IT.

---

## Comment lire ce guide

Deux façons d’installer le code sur le serveur (choisissez **une**) :

| Méthode | Quand l’utiliser | Git sur le serveur ? | Composer / Node sur le serveur ? |
|---------|------------------|----------------------|----------------------------------|
| **A — Archive ZIP** (Partie A + B5a) | Serveur **sans Git** ; build fait sur votre PC | Non | Non |
| **C — Git + dépôt privé** (Partie C) | Mises à jour avec `git pull` ; dépôt GitHub **privé** | **Oui** | **Oui** (pour construire après chaque pull) |

| Où ? | Rôle |
|------|------|
| **Votre PC** | Développement, ou préparation du ZIP (méthode A) |
| **Serveur Windows** | PHP + MySQL + IIS ; éventuellement Git + Composer + Node (méthode C) |

> Le dépôt GitHub est **privé**. Un simple téléchargement ZIP public ne fonctionne pas. Il faut soit une archive préparée sur votre PC (méthode A), soit un accès sécurisé (Deploy Key ou token — méthode C).

**Branche Git du dépôt :** `master` (pas `main`).

**Exemple d’adresse utilisée partout dans ce guide :** `192.168.10.20` — **remplacez-la par l’IP réelle de votre serveur.**

**Durée estimée :** 2 à 4 heures la première fois.

---

## Vue d’ensemble des étapes

```
PARTIE A — Sur votre PC (méthode ZIP uniquement)
  A1. Vérifier PHP, Composer, Node
  A2. Récupérer le code depuis GitHub
  A3. Créer l’archive caert-deploy.zip
  A4. Transférer l’archive vers le serveur

PARTIE B — Sur le serveur Windows (commun)
  B1. IP fixe
  B2. Installer PHP 8.2
  B3. Installer MySQL 8
  B4. Installer IIS + URL Rewrite
  B5a. (méthode ZIP) Extraire caert-deploy.zip dans C:\caert
      — ou —
  PARTIE C puis revenir ici
  B6. Créer le fichier .env
  B7. Base de données + migrations
  B8. Site IIS
  B9. /install + /first-user
  B10. Tests employés (câble + Wi‑Fi)
  B11. Démarrage automatique

PARTIE C — Git sur le serveur (dépôt privé) — alternative à A + B5a
  C1. Installer Git for Windows
  C2. Créer une Deploy Key SSH
  C3. Ajouter la clé sur GitHub
  C4. Cloner dans C:\caert
  C5. Installer Composer + Node sur le serveur
  C6. composer install + npm build
  Puis enchaîner B6 → B11
```

---

# PARTIE A — Préparation sur votre PC (méthode ZIP)

## A1. Vérifier que votre PC est prêt

Ouvrez **PowerShell** et tapez :

```powershell
php -v
composer -V
node -v
```

| Résultat | Action |
|----------|--------|
| Les 3 commandes répondent | Passez à A2 |
| `php` inconnu | Installez PHP comme vous l’avez déjà fait sur ce PC |
| `composer` inconnu | Installez Composer sur ce PC uniquement : https://getcomposer.org/download/ |
| `node` inconnu | Installez Node.js 20 LTS : https://nodejs.org/ |

---

## A2. Récupérer le code depuis GitHub

Ouvrez **PowerShell** :

```powershell
cd C:\dev_land\office\caert
git pull origin master
```

> Si le projet n’est pas encore cloné :
> `git clone https://github.com/abdoulhafidayeva-ui/caert_database.git C:\dev_land\office\caert`  
> (dépôt privé : authentification GitHub demandée sur votre PC)

---

## A3. Préparer le dossier pour le serveur (script automatique)

Toujours sur **votre PC**, exécutez :

```powershell
cd C:\dev_land\office\caert
powershell -ExecutionPolicy Bypass -File scripts\deploy\package-for-windows-server.ps1
```

Ce script :
1. Lance `composer install` (génère le dossier `vendor/`)
2. Lance `npm run build` (génère `public/build/`)
3. Crée **`C:\caert-deploy.zip`** prêt à copier sur le serveur

**Vous devez voir à la fin :** `OK. Copiez C:\caert-deploy.zip sur le serveur...`

### Alternative manuelle (si le script échoue)

```powershell
cd C:\dev_land\office\caert
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Puis copiez **tout le dossier** `C:\dev_land\office\caert` sur le serveur (clé USB, partage réseau, etc.), **sauf** `node_modules` et `.git`.

---

## A4. Transférer l’archive vers le serveur

Choisissez **une** méthode :

| Méthode | Action |
|---------|--------|
| **Clé USB** | Copier `C:\caert-deploy.zip` sur la clé → brancher sur le serveur |
| **Partage réseau** | Sur le serveur, ouvrir `\\VOTRE-PC\C$\` ou partage dossier → copier le zip |
| **Bureau à distance** | Se connecter au serveur → copier-coller le fichier |

---

# PARTIE B — Installation sur le serveur Windows

> Toutes les étapes suivantes se font **sur le serveur**, connecté en **câble Ethernet**.

---

## B1. Configurer l’adresse IP fixe

### Ce que vous allez faire
Donner au serveur une adresse IP qui ne change jamais, par ex. `192.168.10.20`.

### Étapes à l’écran

1. Cliquez sur **Démarrer** (bouton Windows en bas à gauche)
2. Tapez **`ncpa.cpl`** → appuyez sur **Entrée**  
   → La fenêtre **Connexions réseau** s’ouvre
3. Repérez la carte **Ethernet** (pas Wi‑Fi)
4. **Clic droit** sur **Ethernet** → cliquez sur **Propriétés**
5. Dans la liste, cliquez une fois sur **Protocole Internet version 4 (TCP/IPv4)**
6. Cliquez sur le bouton **Propriétés**
7. Cochez **Utiliser l’adresse IP suivante**
8. Saisissez :

| Champ | Valeur à saisir |
|-------|-----------------|
| Adresse IP | `192.168.10.20` |
| Masque de sous-réseau | `255.255.255.0` |
| Passerelle par défaut | `192.168.10.1` |

9. Cochez **Utiliser l’adresse de serveur DNS suivante**
10. Serveur DNS préféré : `192.168.10.1`
11. Cliquez **OK** → **OK** → fermez la fenêtre

![Configurer l'adresse IP fixe](images/hosting/hosting-step-ip-fixe.png)

### Vérification

Ouvrez **PowerShell** et tapez :

```powershell
ipconfig
```

Sous **Carte Ethernet**, l’adresse doit afficher : `192.168.10.20`

---

## B2. Installer PHP 8.2 sur le serveur

### Ce que vous allez faire
Installer PHP pour que IIS puisse exécuter **AUCTC**. **Pas besoin de Composer ici.**

### Étapes à l’écran

1. Sur le serveur, ouvrez **Microsoft Edge**
2. Allez sur : **https://windows.php.net/download/**
3. Dans la section **PHP 8.2**, ligne **VS16 x64 Thread Safe**, cliquez sur le lien **Zip**
4. Le fichier `php-8.2.x-Win32-vs16-x64.zip` se télécharge
5. Ouvrez l’**Explorateur de fichiers** → dossier **Téléchargements**
6. **Clic droit** sur le zip → **Extraire tout…** → destination : `C:\PHP` → **Extraire**
7. Ouvrez `C:\PHP` — vous devez voir `php.exe`, `php.ini-development`, etc.
8. Copiez `php.ini-development` → renommez la copie en **`php.ini`**
9. Ouvrez `C:\PHP\php.ini` avec le **Bloc-notes**
10. Cherchez (Ctrl+F) `extension_dir` — vérifiez : `extension_dir = "ext"`
11. Décommentez (enlevez le `;` au début) ces lignes :

```ini
extension=curl
extension=fileinfo
extension=intl
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=zip
```

12. **Fichier** → **Enregistrer** → fermez le Bloc-notes

### Vérification

Ouvrez **PowerShell** :

```powershell
C:\PHP\php.exe -v
C:\PHP\php.exe -m | findstr intl
```

Vous devez voir `PHP 8.2.x` et `intl` dans la liste.

---

## B3. Installer MySQL 8 sur le serveur

### Étapes à l’écran

1. Ouvrez Edge → **https://dev.mysql.com/downloads/installer/**
2. Cliquez **Download** sur **Windows (x86, 32-bit), MSI Installer** (ou version complète)
3. Lancez le fichier téléchargé `mysql-installer-community-…msi`
4. Choisissez le type d’installation : **Server only** → **Next**
5. **Execute** pour installer MySQL Server 8.0
6. Configuration :
   - **Config Type** : Development Computer
   - **Port** : `3306` (laisser par défaut)
   - Définissez un mot de passe **root** → **notez-le** dans un endroit sûr
   - Cochez **Start MySQL Server at System Startup** (démarrage automatique)
7. Cliquez **Execute** → **Finish**

### Vérification

Ouvrez **PowerShell** :

```powershell
Get-Service MySQL*
```

Le statut doit être **Running** (En cours d’exécution).

---

## B4. Installer IIS et le module URL Rewrite

### B4.1 Activer IIS

1. **Démarrer** → tapez **`optionalfeatures`** → **Entrée**
2. Fenêtre **Fonctionnalités Windows** :
   - Cochez **Services Internet Information Server (IIS)**
   - Déployez l’arborescence → cochez au minimum :
     - **Outils de gestion Web** → **Console de gestion IIS**
     - **Services Web** → **Sécurité**, **Contenu statique**, **Redirection HTTP**
3. Cliquez **OK** → attendez l’installation → **Fermer**

### B4.2 Installer URL Rewrite (obligatoire)

1. Edge → **https://www.iis.net/downloads/microsoft/url-rewrite**
2. Cliquez **Install this extension** → téléchargez et installez
3. Redémarrez la console IIS si elle était ouverte

### B4.3 Lier PHP à IIS

Ouvrez **PowerShell en tant qu’administrateur** (clic droit Démarrer → Windows PowerShell (admin)) :

```powershell
New-WebHandler -Name "PHP_via_FastCGI" -Path "*.php" -Verb "*" -Modules "FastCgiModule" -ScriptProcessor "C:\PHP\php-cgi.exe" -ResourceType Either
```

---

## B5a. Méthode ZIP — copier le projet dans C:\caert

> **Si vous utilisez Git sur le serveur**, sautez B5a et faites d’abord la **Partie C**, puis revenez à **B6**.

### Étapes à l’écran

1. Copiez `caert-deploy.zip` sur le serveur (clé USB ou réseau)
2. **Clic droit** sur `caert-deploy.zip` → **Extraire tout…**
3. Destination : **`C:\caert`** → cochez **Afficher les fichiers extraits**
4. Vérifiez que vous voyez :

| Dossier / fichier | Présent ? |
|-------------------|-----------|
| `C:\caert\vendor` | Oui — **indispensable** |
| `C:\caert\public\build` | Oui — **indispensable** |
| `C:\caert\bin\console` | Oui |
| `C:\caert\.env.example` | Oui |

![Dossier C:\caert après extraction](images/hosting/hosting-step-dossier-caert.png)

> Si `vendor` est absent : retournez sur votre PC, relancez le script `package-for-windows-server.ps1`, et recopiez l’archive.

---

## B6. Créer et remplir le fichier .env

### Étapes à l’écran

1. Ouvrez l’**Explorateur** → `C:\caert`
2. Copiez le fichier **`.env.example`**
3. Collez dans le même dossier → renommez la copie en **`.env`**
4. **Clic droit** sur `.env` → **Ouvrir avec** → **Bloc-notes**
5. Modifiez ces lignes (exemple) :

```dotenv
APP_ENV=prod
APP_SECRET=collez_ici_une_chaine_aleatoire_de_32_caracteres
DATABASE_URL=mysql://caert:MOT_DE_PASSE_CAERT@127.0.0.1:3306/caert_db?serverVersion=8.0&charset=utf8mb4
APP_URL=http://192.168.10.20
MAILER_DSN=null://null
```

6. Pour générer `APP_SECRET`, dans PowerShell :

```powershell
C:\PHP\php.exe -r "echo bin2hex(random_bytes(16));"
```

7. Copiez le résultat → collez-le comme valeur de `APP_SECRET`
8. **Enregistrez** le fichier `.env`

---

## B7. Créer la base de données et lancer les migrations

### B7.1 Créer la base MySQL

1. **Démarrer** → tapez **`MySQL 8.0 Command Line Client`** → **Entrée**
2. Saisissez le mot de passe **root** défini à l’installation
3. Copiez-collez **ligne par ligne** (remplacez le mot de passe) :

```sql
CREATE DATABASE caert_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'caert'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_CAERT';
GRANT ALL PRIVILEGES ON caert_db.* TO 'caert'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> Utilisez le **même** `MOT_DE_PASSE_CAERT` dans le `.env` (ligne `DATABASE_URL`).

### B7.2 Lancer les migrations

> Les migrations sont **idempotentes** : elles fonctionnent sur une **base vide** (création des tables) et ne recréent pas ce qui existe déjà.

Ouvrez **PowerShell** :

```powershell
cd C:\caert
C:\PHP\php.exe bin\console doctrine:migrations:migrate --no-interaction
C:\PHP\php.exe bin\console cache:clear --env=prod
```

Message attendu : migrations exécutées sans erreur.

Si une tentative précédente a **partiellement échoué** sur une base presque vide :

```powershell
mysql -u caert -p -e "DROP DATABASE caert_db; CREATE DATABASE caert_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cd C:\caert
C:\PHP\php.exe bin\console doctrine:migrations:migrate --no-interaction
```

### B7.3 Permissions du dossier var

```powershell
icacls C:\caert\var /grant "IIS_IUSRS:(OI)(CI)M" /T
icacls C:\caert\var /grant "IUSR:(OI)(CI)M" /T
```

---

## B8. Créer le site dans IIS

### Étapes à l’écran

1. **Démarrer** → tapez **`inetmgr`** → **Entrée**  
   → La **Console de gestion des services Internet (IIS)** s’ouvre
2. Dans l’arborescence à gauche, déployez le nom du serveur → cliquez sur **Sites**
3. Dans le panneau de droite, cliquez sur **Ajouter un site Web…**
4. Remplissez **exactement** :

| Champ | Valeur à saisir |
|-------|-----------------|
| **Nom du site** | `AUCTC` |
| **Chemin d'accès physique** | `C:\caert\public` (bouton `…` pour parcourir) |
| **Liaison — Type** | `http` |
| **Adresse IP** | `192.168.10.20` (ou « Toutes non attribuées ») |
| **Port** | `80` |
| **Nom d'hôte** | *(laisser vide)* |

5. Cliquez **OK**
6. Dans la liste des sites, cliquez sur **AUCTC**
7. Panneau de droite → **Démarrer** (si le site n’est pas déjà démarré)

![Ajouter un site Web dans IIS](images/hosting/hosting-step-iis-site.png)

### Ouvrir le pare-feu pour le port 80

**PowerShell administrateur** :

```powershell
New-NetFirewallRule -DisplayName "AUCTC HTTP LAN" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow
```

### Vérification sur le serveur

1. Ouvrez **Edge** sur le serveur
2. Dans la barre d’adresse, tapez : **`http://127.0.0.1/health`**
3. Vous devez voir : `{"status":"healthy"}` ou équivalent

![Test health OK dans le navigateur](images/hosting/hosting-step-health-ok.png)

---

## B9. Premier accès — créer le super administrateur

### Sur le serveur ou un PC du réseau

1. Ouvrez le navigateur
2. Barre d’adresse : **`http://192.168.10.20`**
3. Si c’est la première installation :
   - Vous êtes redirigé vers **`/install`**
   - **Saisissez** le nom de l’application (ex. `AUCTC`) → validez
   - Page **`/first-user`** :
     - Nom, prénoms, e-mail, mot de passe (2 fois)
     - Cliquez **Créer** / **Enregistrer**
4. Page de **connexion** → connectez-vous avec le compte créé
5. Ce compte est le **super administrateur**

---

## B10. Accès des employés (réseau local + Wi‑Fi)

### URL à communiquer aux employés

```
http://192.168.10.20
```

Affichez cette adresse dans les bureaux ou envoyez-la par e-mail interne.

### Test 1 — Poste en câble

1. Branchez un PC sur le réseau (Ethernet)
2. Ouvrez Chrome ou Edge
3. Tapez `http://192.168.10.20`
4. Connectez-vous avec un compte de test

### Test 2 — Poste en Wi‑Fi

1. Connectez un laptop au **Wi‑Fi employés** (pas le réseau invité)
2. Même URL : `http://192.168.10.20`
3. Même test de connexion

### Si le Wi‑Fi ne fonctionne pas mais le câble oui

Demandez à l’IT réseau de vérifier :
- Le Wi‑Fi est sur le **même sous-réseau** que `192.168.10.20`
- L’**isolation client** est désactivée sur les points d’accès

---

## B11. Redémarrage automatique (délestage / reboot)

### BIOS — redémarrer quand le courant revient

Au démarrage du serveur, touche **F2** ou **Del** → BIOS :
- Cherchez **Power On after AC Loss** ou **Restore on AC Power**
- Mettez sur **On** / **Enabled**
- **Save & Exit**

### Services Windows — démarrage automatique

**PowerShell administrateur** :

```powershell
Set-Service -Name W3SVC -StartupType Automatic
Set-Service -Name MySQL80 -StartupType Automatic
```

> Si le service MySQL a un autre nom, vérifiez avec `Get-Service MySQL*`

### Test

```powershell
Restart-Computer
```

Après redémarrage, depuis un PC employé : `http://192.168.10.20` doit fonctionner sans intervention manuelle.

---

## B12. Mise à jour ultérieure (nouvelle version)

### B12.1 Méthode ZIP (depuis votre PC)

```powershell
# Sur votre PC
cd C:\dev_land\office\caert
git pull origin master
powershell -ExecutionPolicy Bypass -File scripts\deploy\package-for-windows-server.ps1
```

Sur le serveur :
1. Sauvegarder la base (voir B13)
2. Renommer `C:\caert` en `C:\caert_old`
3. Extraire la nouvelle archive dans `C:\caert`
4. **Recopier** l’ancien fichier `.env` vers le nouveau `C:\caert\.env`
5. PowerShell :

```powershell
cd C:\caert
C:\PHP\php.exe bin\console doctrine:migrations:migrate --no-interaction
C:\PHP\php.exe bin\console cache:clear --env=prod
icacls C:\caert\var /grant "IIS_IUSRS:(OI)(CI)M" /T
```

### B12.2 Méthode Git (sur le serveur)

Voir **C8** (Partie C) — `git pull` + `composer` + `npm` + migrations.

---

## B13. Sauvegarde de la base (recommandé)

**PowerShell** — à planifier dans le **Planificateur de tâches** (quotidien 2h00) :

```powershell
$date = Get-Date -Format "yyyyMMdd"
& "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe" -u caert -p caert_db > "C:\caert\var\backups\caert_$date.sql"
```

---

# PARTIE C — Git sur le serveur (dépôt GitHub privé)

Utilisez cette partie **à la place** de la Partie A + B5a si le serveur doit pouvoir se mettre à jour avec `git pull`.

**Prérequis serveur :** connexion Internet vers `github.com` (au moins pour le clone et les mises à jour).  
**Branche :** `master`.

---

## C1. Installer Git for Windows

### Étapes à l’écran

1. Sur le serveur, ouvrez **Edge**
2. Allez sur : **https://git-scm.com/download/win**
3. Téléchargez **Git for Windows** (64-bit)
4. Lancez l’installeur → **Next** jusqu’à la fin
5. Options recommandées :
   - **Git from the command line and also from 3rd-party software**
   - Éditeur de commit : laissez la valeur par défaut
6. Cliquez **Install** → **Finish**
7. **Fermez** et **rouvrez** PowerShell

### Vérification

```powershell
git --version
```

Vous devez voir une version (ex. `git version 2.x.x`).

---

## C2. Créer une Deploy Key SSH (sur le serveur)

La Deploy Key donne au **serveur uniquement** un accès **lecture** à votre dépôt privé — sans partager votre mot de passe GitHub.

### Étapes PowerShell

1. Ouvrez **PowerShell**
2. Créez le dossier `.ssh` s’il n’existe pas :

```powershell
New-Item -ItemType Directory -Force -Path $env:USERPROFILE\.ssh
```

3. Générez la clé (appuyez sur **Entrée** pour les questions — passphrase vide acceptable pour un serveur local) :

```powershell
ssh-keygen -t ed25519 -C "auctc-serveur-local" -f $env:USERPROFILE\.ssh\auctc_deploy
```

4. Affichez la **clé publique** (à coller dans GitHub) :

```powershell
Get-Content $env:USERPROFILE\.ssh\auctc_deploy.pub
```

5. **Sélectionnez** toute la ligne affichée → **Ctrl+C** (copier).  
   Elle commence par `ssh-ed25519` et se termine souvent par `auctc-serveur-local`.

6. Créez le fichier de config SSH :

```powershell
@"
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/auctc_deploy
  IdentitiesOnly yes
"@ | Set-Content -Path $env:USERPROFILE\.ssh\config -Encoding ASCII
```

---

## C3. Ajouter la Deploy Key sur GitHub

### Étapes à l’écran (depuis votre PC ou le serveur, navigateur)

1. Connectez-vous à **GitHub** avec le compte propriétaire du dépôt
2. Ouvrez le dépôt : **https://github.com/abdoulhafidayeva-ui/caert_database**
3. Cliquez **Settings** (Paramètres du dépôt)
4. Dans le menu de gauche : **Deploy keys**
5. Cliquez **Add deploy key**
6. Remplissez :

| Champ | Valeur |
|-------|--------|
| **Title** | `AUCTC serveur local` |
| **Key** | Collez le contenu de `auctc_deploy.pub` (étape C2) |
| **Allow write access** | **Décoché** (lecture seule) |

7. Cliquez **Add key**

### Test de connexion SSH (sur le serveur)

```powershell
ssh -T git@github.com
```

Réponse attendue (normale) :

```
Hi abdoulhafidayeva-ui/caert_database! You've successfully authenticated, but GitHub does not provide shell access.
```

Au premier appel, tapez **`yes`** si on vous demande d’accepter l’empreinte du serveur.

---

## C4. Cloner le dépôt dans C:\caert

```powershell
cd C:\
git clone git@github.com:abdoulhafidayeva-ui/caert_database.git caert
cd C:\caert
git checkout master
git status
```

Vérifiez que vous voyez les dossiers `src`, `public`, `bin`, etc.

> Après un clone **vide** de `vendor/` et `public/build/` : c’est normal. L’étape C6 les génère.

---

## C5. Installer Composer et Node.js **sur le serveur**

Avec Git, le serveur doit **construire** le projet après chaque mise à jour.

### Composer

1. Edge → **https://getcomposer.org/download/**
2. Téléchargez **Composer-Setup.exe**
3. Pendant l’installation, indiquez le chemin PHP : `C:\PHP\php.exe`
4. Terminez l’installation → rouvrez PowerShell
5. Vérifiez :

```powershell
composer --version
```

### Node.js 20 LTS

1. Edge → **https://nodejs.org/**
2. Téléchargez **LTS** → installez (options par défaut)
3. Rouvrez PowerShell
4. Vérifiez :

```powershell
node --version
npm --version
```

---

## C6. Premier build sur le serveur

```powershell
cd C:\caert
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
```

Vérifiez :

| Dossier | Présent ? |
|---------|-----------|
| `C:\caert\vendor` | Oui |
| `C:\caert\public\build` | Oui |

Ensuite, **enchaînez B6 → B11** (fichier `.env`, base MySQL, site IIS, bootstrap, tests, démarrage auto).

---

## C7. Alternative : Personal Access Token (HTTPS)

Si SSH est bloqué par le réseau, utilisez HTTPS + token :

1. Sur GitHub (votre compte) : **Settings → Developer settings → Personal access tokens**  
   - Créez un token (classic) avec scope **`repo`**, ou Fine-grained **lecture seule** sur `caert_database`
2. Copiez le token immédiatement (il n’est affiché qu’une fois)
3. Sur le serveur :

```powershell
cd C:\
git clone https://github.com/abdoulhafidayeva-ui/caert_database.git caert
```

Quand demandé :

| Invite | Saisir |
|--------|--------|
| Username | votre identifiant GitHub |
| Password | **le token** (pas le mot de passe du compte) |

Puis reprendre **C5** et **C6**.

> Préférez la **Deploy Key SSH** (C2–C4) : le token est lié à votre compte personnel ; la Deploy Key est liée au serveur uniquement.

---

## C8. Mise à jour avec Git (sur le serveur)

À chaque nouvelle version poussée sur GitHub (`master`) :

```powershell
cd C:\caert

# 1. Sauvegarde MySQL (recommandé) — voir B13

# 2. Récupérer le code
git pull origin master

# 3. Reconstruire
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

# 4. Base + cache
C:\PHP\php.exe bin\console doctrine:migrations:migrate --no-interaction
C:\PHP\php.exe bin\console cache:clear --env=prod
C:\PHP\php.exe bin\console cache:warmup --env=prod

# 5. Droits var
icacls C:\caert\var /grant "IIS_IUSRS:(OI)(CI)M" /T

# 6. Contrôle
Invoke-WebRequest -Uri "http://127.0.0.1/health" -UseBasicParsing
```

> Ne pas écraser le fichier `.env` : `git pull` ne le remplace en général pas (il n’est pas versionné). Vérifiez qu’il est toujours présent.

---

## Dépannage rapide

| Problème | Solution |
|----------|----------|
| Page blanche | Vérifier `C:\caert\var\log\prod.log` |
| Erreur 500 | `C:\PHP\php.exe bin\console cache:clear --env=prod` |
| 404 sur toutes les pages sauf accueil | Installer **URL Rewrite** (B4.2) |
| `vendor` introuvable | Méthode ZIP : Partie A ; méthode Git : C6 |
| `Permission denied (publickey)` | Vérifier Deploy Key (C2–C3) et `ssh -T git@github.com` |
| `Repository not found` | Dépôt privé : Deploy Key absente ou mauvais dépôt |
| Site inaccessible après reboot | `Start-Service W3SVC` et `Start-Service MySQL80` |
| Wi‑Fi seul en échec | Vérifier VLAN / isolation AP (B10) |

---

## Fiche à remplir sur site

| Champ | Valeur |
|-------|--------|
| IP serveur | |
| URL employés | `http://` |
| Méthode déploiement | ☐ ZIP &nbsp; ☐ Git + Deploy Key |
| Mot de passe MySQL `caert` | *(coffre-fort IT)* |
| Super admin AUCTC (e-mail) | |
| Date installation | |
| Responsable IT | |

---

*Guide pas à pas — plateforme **AUCTC** sur Windows Server, HTTP, méthode ZIP ou Git (dépôt privé).*
