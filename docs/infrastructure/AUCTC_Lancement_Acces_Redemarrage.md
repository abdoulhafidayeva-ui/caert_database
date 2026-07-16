# AUCTC — Lancement, accès Wi‑Fi et redémarrage automatique

**Public :** administrateur IT sur le serveur Windows local  
**Prérequis :** projet déjà installé (`C:\caert`), base MySQL créée, migrations OK  
**Protocole :** HTTP (réseau local)

Ce document ne couvre **que** :
1. IP fixe **sans perdre Internet**
2. Lancement du site (IIS + test)
3. Accès depuis les machines Wi‑Fi du réseau
4. Redémarrage automatique après coupure / reboot

---

## 1. IP fixe sans perdre Internet

Si vous passez en IP fixe et que **Internet disparaît**, c’est presque toujours la **passerelle** ou le **DNS** qui sont incorrects (ou oubliés).

### 1.1 Avant de changer : noter la config DHCP actuelle

1. Branchez le serveur en **câble Ethernet**
2. Laissez temporairement **Obtenir une adresse IP automatiquement**
3. Ouvrez **PowerShell** et tapez :

```powershell
ipconfig /all
```

Repérez la **Carte Ethernet** et notez :

| Champ | Exemple | Votre valeur |
|-------|---------|--------------|
| Adresse IPv4 | `192.168.1.45` | |
| Masque | `255.255.255.0` | |
| Passerelle par défaut | `192.168.1.1` | |
| Serveurs DNS | `192.168.1.1` | |

La **passerelle** et le **DNS** doivent être **les mêmes** une fois l’IP fixe appliquée. Sinon : plus d’Internet.

### 1.2 Choisir une IP fixe libre

Prenez une adresse **dans le même réseau**, hors plage DHCP si possible.

Exemples :

| Si votre IP actuelle (DHCP) était… | IP fixe possible |
|------------------------------------|------------------|
| `192.168.1.45` | `192.168.1.20` |
| `192.168.10.100` | `192.168.10.20` |

**Règle :** les 3 premiers nombres = ceux de la passerelle.  
Exemple passerelle `192.168.1.1` → IP du type `192.168.1.XX`.

### 1.3 Appliquer l’IP fixe (clic par clic)

1. **Démarrer** → tapez `ncpa.cpl` → **Entrée**
2. **Clic droit** sur **Ethernet** → **Propriétés**
3. Double-clic sur **Protocole Internet version 4 (TCP/IPv4)**
4. Cochez **Utiliser l’adresse IP suivante**
5. Saisissez (**adaptez** à vos notes de l’étape 1.1) :

| Champ | Que saisir |
|-------|------------|
| Adresse IP | l’IP fixe choisie (ex. `192.168.1.20`) |
| Masque de sous-réseau | **identique** au DHCP (souvent `255.255.255.0`) |
| Passerelle par défaut | **identique** au DHCP (ex. `192.168.1.1`) |
| DNS préféré | **identique** au DHCP (souvent la même que la passerelle) |
| DNS auxiliaire | optionnel : `8.8.8.8` |

6. Cliquez **OK** → **OK**

### 1.4 Vérifier Internet + réseau local

```powershell
ipconfig
ping 8.8.8.8
ping www.google.com
```

| Test | Résultat attendu |
|------|------------------|
| `ipconfig` | Votre nouvelle IP fixe |
| `ping 8.8.8.8` | Réponses (Internet / route OK) |
| `ping www.google.com` | Réponses (DNS OK) |

Si `8.8.8.8` marche mais pas `google.com` → DNS faux.  
Si rien ne marche → passerelle fausse.

PowerShell (alternative) — **remplacez** les valeurs :

```powershell
# Remplacez "Ethernet" par le nom exact de votre carte (Get-NetAdapter)
New-NetIPAddress -InterfaceAlias "Ethernet" -IPAddress 192.168.1.20 -PrefixLength 24 -DefaultGateway 192.168.1.1
Set-DnsClientServerAddress -InterfaceAlias "Ethernet" -ServerAddresses 192.168.1.1,8.8.8.8
```

> Dans la suite de ce guide, on utilise l’exemple `192.168.1.20`. **Remplacez par l’IP réelle de votre serveur.**

---

## 2. Lancement du projet (IIS)

### 2.1 Vérifier PHP et MySQL

```powershell
C:\PHP\php.exe -v
Get-Service MySQL*
Get-Service W3SVC
```

MySQL et **W3SVC** (IIS) doivent être **Running**.

Si arrêtés :

```powershell
Start-Service MySQL80
Start-Service W3SVC
```

*(Le nom MySQL peut être `MySQL` ou `MySQL80` — vérifier avec `Get-Service MySQL*`.)*

### 2.2 Permissions du dossier `var`

```powershell
icacls C:\caert\var /grant "IIS_IUSRS:(OI)(CI)M" /T
icacls C:\caert\var /grant "IUSR:(OI)(CI)M" /T
```

### 2.3 Créer le site IIS (si pas encore fait)

1. **Démarrer** → `inetmgr` → **Entrée**
2. Clic droit **Sites** → **Ajouter un site Web…**
3. Remplir :

| Champ | Valeur |
|-------|--------|
| Nom du site | `AUCTC` |
| Chemin physique | `C:\caert\public` |
| Type | `http` |
| Adresse IP | l’IP fixe du serveur **ou** « Toutes non attribuées » |
| Port | `80` |
| Nom d’hôte | *(vide)* |

4. **OK** → démarrer le site **AUCTC**

### 2.4 Pare-feu Windows (port 80)

PowerShell **administrateur** :

```powershell
New-NetFirewallRule -DisplayName "AUCTC HTTP LAN" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow
```

### 2.5 Tests sur le serveur

Dans Edge sur le serveur :

1. `http://127.0.0.1/health` → doit répondre `healthy`
2. `http://127.0.0.1/` → page AUCTC (login ou `/install`)
3. `http://VOTRE-IP-FIXE/` (ex. `http://192.168.1.20/`) → même résultat

Si `127.0.0.1` marche mais pas l’IP fixe : vérifier la liaison IIS (Adresse IP du site) et le pare-feu.

### 2.6 Premier compte (si base neuve)

1. Ouvrir `http://VOTRE-IP-FIXE/`
2. `/install` → nom **AUCTC**
3. `/first-user` → créer le super administrateur
4. Se connecter

### 2.7 `APP_URL` dans `.env`

Dans `C:\caert\.env` :

```dotenv
APP_URL=http://192.168.1.20
```

(adapter à votre IP) puis :

```powershell
cd C:\caert
C:\PHP\php.exe bin\console cache:clear --env=prod
```

---

## 3. Accès depuis les machines Wi‑Fi

### 3.1 URL à donner aux employés

```
http://192.168.1.20
```

(Remplacer par l’IP fixe réelle du serveur.)

Les employés doivent être sur le **Wi‑Fi interne** (même réseau que le serveur), **pas** le Wi‑Fi invité.

### 3.2 Test depuis un laptop Wi‑Fi

1. Connecter le laptop au SSID employés
2. Edge / Chrome → `http://192.168.1.20`
3. Page de connexion AUCTC attendue

Diagnostic sur le laptop :

```powershell
ping 192.168.1.20
```

| Résultat | Meaning |
|----------|---------|
| Ping OK + page OK | Terminé |
| Ping OK + page KO | Pare-feu / IIS (voir §2) |
| Ping KO | Pas le même réseau, ou isolation Wi‑Fi |

### 3.3 Si le Wi‑Fi ne voit pas le serveur

Demander à l’IT réseau de vérifier :

1. **Même sous-réseau** que le serveur (ex. tous en `192.168.1.x`)
2. **Isolation client** (AP isolation) **désactivée** sur le SSID employés
3. Pas de VLAN « invité » pour ce SSID
4. Pare-feu du routeur : autoriser TCP **80** vers l’IP du serveur

Test depuis un PC **en câble** sur le même switch : si le câble marche et le Wi‑Fi non → problème côté points d’accès / VLAN, pas côté AUCTC.

### 3.4 Affiche interne

Exemple à afficher dans les bureaux :

```
Application AUCTC
Adresse : http://192.168.1.20
Réseau : Wi‑Fi employés (pas invité)
```

---

## 4. Redémarrage automatique (délestage / reboot)

Objectif : après coupure de courant ou `Restart-Computer`, le site revient **sans intervention**.

### 4.1 BIOS — rallumer quand le courant revient

1. Redémarrer le serveur → Touche **Del**, **F2** ou **F10** (selon marque)
2. Chercher : **Restore on AC Power Loss** / **AC Power Recovery** / **After Power Loss**
3. Mettre : **Power On** / **Last State** / **Enabled**
4. **Save & Exit**

Sans cette option, le serveur reste éteint après un délestage.

### 4.2 Services Windows — démarrage Automatique

PowerShell **administrateur** :

```powershell
Set-Service -Name W3SVC -StartupType Automatic
Get-Service MySQL* | ForEach-Object { Set-Service -Name $_.Name -StartupType Automatic }

Start-Service W3SVC
Get-Service MySQL* | Start-Service

Get-Service W3SVC, MySQL* | Format-Table Name, Status, StartType
```

| Service | Rôle | StartType attendu |
|---------|------|-------------------|
| `W3SVC` | IIS (site web) | Automatic |
| `MySQL80` (ou `MySQL`) | Base de données | Automatic |

### 4.3 Test de validation

```powershell
Restart-Computer
```

Après redémarrage (2–3 minutes) :

**Sur le serveur :**

```powershell
Get-Service W3SVC, MySQL*
Invoke-WebRequest -Uri "http://127.0.0.1/health" -UseBasicParsing
```

**Depuis un laptop Wi‑Fi :** ouvrir `http://VOTRE-IP-FIXE/`

Si le site ne répond pas :

```powershell
Start-Service W3SVC
Get-Service MySQL* | Start-Service
iisreset /start
```

### 4.4 Onduleur (recommandé)

Un **UPS** évite une coupure brutale de MySQL pendant le délestage. Brancher le serveur (et si possible le switch) dessus.

---

## 5. Checklist finale

- [ ] IP fixe + passerelle + DNS → Internet OK (`ping 8.8.8.8`)
- [ ] `http://127.0.0.1/health` → healthy
- [ ] `http://IP-FIXE/` marche sur le serveur
- [ ] Même URL marche depuis un PC **câble**
- [ ] Même URL marche depuis un laptop **Wi‑Fi employés**
- [ ] `W3SVC` et MySQL en **Automatic**
- [ ] BIOS « Restore on AC Power » activé
- [ ] Test après `Restart-Computer` OK
- [ ] URL communiquée aux utilisateurs

---

## 6. Fiche site

| Champ | Valeur |
|-------|--------|
| IP fixe serveur | |
| Passerelle | |
| DNS | |
| URL employés | `http://` |
| SSID Wi‑Fi | |
| Date mise en service | |
| Responsable IT | |

---

*AUCTC — Guide ciblé : IP fixe + Internet, lancement IIS, accès LAN/Wi‑Fi, redémarrage auto.*
