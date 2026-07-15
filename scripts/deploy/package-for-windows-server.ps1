# Prépare un dossier CAERT prêt à copier sur le serveur Windows (vendor + assets inclus).
# À exécuter sur le PC de développement — PAS sur le serveur.
param(
    [string]$OutputZip = "C:\caert-deploy.zip"
)

$ErrorActionPreference = "Stop"
$Root = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
Set-Location $Root

Write-Host "==> Preparation AUCTC pour serveur Windows"
Write-Host "    Dossier source : $Root"

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    throw "PHP introuvable. Installez PHP sur ce PC ou ajoutez-le au PATH."
}

if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
    throw "Composer introuvable sur ce PC. Installez-le ici (pas sur le serveur)."
}

if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    throw "Node.js/npm introuvable sur ce PC."
}

Write-Host "==> composer install (prod)"
composer install --no-dev --optimize-autoloader --no-interaction

Write-Host "==> npm build"
npm ci
npm run build

$staging = Join-Path $env:TEMP "caert-deploy-staging"
if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
New-Item -ItemType Directory -Path $staging | Out-Null

Write-Host "==> Copie des fichiers vers staging"
robocopy $Root $staging /MIR /XD node_modules .git .idea tests /XF .env .env.local .env.*.local | Out-Null

if (-not (Test-Path (Join-Path $staging "vendor"))) {
    throw "Dossier vendor manquant apres copie."
}
if (-not (Test-Path (Join-Path $staging "public\build"))) {
    throw "Dossier public\build manquant. Verifiez npm run build."
}

Copy-Item (Join-Path $Root ".env.example") (Join-Path $staging ".env.example") -Force

if (Test-Path $OutputZip) { Remove-Item $OutputZip -Force }
Write-Host "==> Creation archive $OutputZip"
Compress-Archive -Path (Join-Path $staging "*") -DestinationPath $OutputZip -CompressionLevel Optimal

Write-Host ""
Write-Host "OK. Copiez $OutputZip sur le serveur et extrayez dans C:\caert"
Write-Host "Puis sur le serveur : copiez .env.example vers .env et configurez-le."
