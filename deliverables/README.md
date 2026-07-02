# Livrables documentation & formation — CAERT

Fichiers générés à partir des sources Markdown (`docs/`).

## Génération

```bash
node scripts/build_documentation_pack.mjs
node scripts/build_training_slides.mjs
```

Ou :

```bash
npm run docs:word
npm run docs:slides
npm run docs:all
```

## Fichiers produits

| Fichier | Description |
|---------|-------------|
| `AUCTC_CAERT_Documentation_Pack_2026.docx` | Pack complet (toute la doc) |
| `AUCTC_CAERT_Training_Slides_2026.docx` | Support de formation (~20 slides, paysage) |
| `training_slides.html` | Présentation navigateur (flèches / F plein écran) |
| `word/CAERT_Manuel_Utilisateur_2026.docx` | Manuel utilisateur seul |
| `word/CAERT_Manuel_Administrateur_2026.docx` | Manuel admin seul |
| `word/CAERT_Formation_Onboarding_2026.docx` | Guide onboarding |
| `word/CAERT_Guide_Analyste_2026.docx` | Guide analyste |

## Export PDF

1. Ouvrir le `.docx` dans **Microsoft Word** ou **LibreOffice Writer**
2. **Fichier → Enregistrer sous → PDF**
3. Pour les slides : mode présentation Word ou PDF paysage

## Présentation HTML (optionnel)

Ouvrir `training_slides.html` dans un navigateur (F pour plein écran, flèches pour naviguer).

## Source de vérité

Les fichiers Markdown dans `docs/` restent la référence maintenue. Regénérer les Word après toute mise à jour.
