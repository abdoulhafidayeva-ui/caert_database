/**
 * CAERT training slide deck (Word format — print or export to PDF).
 * Output: deliverables/AUCTC_CAERT_Training_Slides_2026.docx
 */
import { Document, Packer, Header, Footer, PageNumber, AlignmentType, TextRun, Paragraph } from 'docx';
import fs from 'fs';
import path from 'path';
import { coverPage, slide, pb, body, BLUE, GRAY, pt } from './lib/docx_helpers.mjs';

const OUT = path.join(process.cwd(), 'deliverables', 'AUCTC_CAERT_Training_Slides_2026.docx');

const slides = [
  ['CAERT / AUCTC', [
    'Plateforme continentale de reporting d\'incidents sécuritaires',
    'Union Africaine — Counter-Terrorism Centre',
    'Classification : interne — sensible',
  ], 'Formation utilisateurs et administrateurs — juin 2026'],
  ['Objectifs de la formation', [
    'Comprendre le rôle de CAERT dans la chaîne AUCTC',
    'Maîtriser connexion, navigation et langue (FR/EN)',
    'Saisir et importer des incidents',
    'Connaître le workflow validation → publication',
    'Utiliser analyses, synthèse et carte SIG',
  ]],
  ['Qui utilise CAERT ?', [
    'Points focaux pays — saisie périmètre national',
    'Analystes — consultation et reporting',
    'Administrateurs — validation et synthèse',
    'Super-admins — comptes et référentiels',
  ]],
  ['Architecture fonctionnelle', [
    'Saisie web + import Excel',
    'File de validation (admin)',
    'Publication → analytics & carte',
    'Audit complet de chaque action sensible',
  ]],
  ['Connexion', [
    'URL institutionnelle + e-mail / mot de passe',
    '« Rester connecté » : poste personnel uniquement (7 jours)',
    'Mot de passe oublié : e-mail ou SMS',
    'Sélecteur FR / EN dans l\'en-tête',
  ]],
  ['Navigation principale', [
    'Tableau de bord — liste et filtres incidents',
    'File de validation — admin',
    'Synthèse direction — KPIs',
    'Analyses — graphiques tendances',
    'Carte SIG — visualisation pays',
  ]],
  ['Statuts incident', [
    'En attente (orange) — soumis, non validé',
    'Publié (vert) — visible KPIs, carte, analyses',
    'Rejeté (rouge) — motif obligatoire, correction possible',
  ], 'Seuls les incidents publiés alimentent les indicateurs officiels'],
  ['Workflow contributeur', [
    '1. Saisir ou importer un incident',
    '2. Statut = En attente',
    '3. Admin publie ou rejette',
    '4. Si rejeté : lire motif → corriger → resoumettre',
  ]],
  ['Saisie manuelle', [
    'Nouvel enregistrement → formulaire complet',
    'Date d\'attaque, localité, pays, référentiels',
    'Victimes par catégorie (civils, militaires, terroristes)',
    'Point focal : pays = pays assigné au compte',
  ]],
  ['Import Excel', [
    'Format .xlsx — colonne A = date d\'attaque',
    'Libellés référentiels = identiques à la base',
    'Import partiel possible avec rapport d\'erreurs',
    'Tous imports → statut En attente',
  ]],
  ['Workflow administrateur', [
    'Ouvrir File de validation',
    'Vérifier cohérence (date, chiffres, sources)',
    'Publier → analytics immédiat',
    'Rejeter → motif clair et actionnable',
  ]],
  ['Tableau de bord — filtres', [
    'Statut, région, pays, type attaque, dates…',
    'Multi-sélection Select2',
    'Export DataTables (Excel/PDF si activé)',
    'KPI « À valider » pour admins',
  ]],
  ['Synthèse direction', [
    'Publiés / En attente / Rejetés / Décès',
    'Top 10 pays (incidents publiés)',
    'Ne remplace pas l\'analyse détaillée par pays',
    'Accès : ROLE_ADMIN',
  ]],
  ['Analyses — graphiques', [
    'Récap : attaques, décès, blessés, cibles',
    'Comparatif : période + indicateur + régions',
    'Données publiées uniquement',
    'Générer après validation des filtres',
  ]],
  ['Carte SIG (MVP)', [
    'Incidents publiés agrégés au centroïde pays',
    'Taille cercle ∝ volume par pays',
    'Localité = texte dans fiche incident',
    'Évolution : géocodage précis (PostGIS)',
  ]],
  ['Reporting institutionnel', [
    'Synthèse + Analyses + Carte + export tableau',
    'Toujours préciser la période',
    'Uniquement données publiées',
    'Pas de diffusion hors circuits UA',
  ]],
  ['Sécurité — bonnes pratiques', [
    'Ne pas partager identifiants',
    'Se déconnecter sur poste partagé',
    'Données sensibles — classification interne',
    'Signaler incident sécurité au RSSI',
  ]],
  ['Administration système (aperçu)', [
    '/health — sonde monitoring',
    'Backups quotidiens BDD + uploads',
    'Migrations Doctrine versionnées',
    'Documentation : docs/ + scripts/deploy/',
  ]],
  ['Support et documentation', [
    'Manuel utilisateur : docs/user/',
    'Manuel admin : docs/admin/',
    'Dépannage : docs/troubleshooting/',
    'Index : docs/README.md',
  ]],
  ['Quiz — vérification', [
    'Différence En attente vs Publié ?',
    'Un point focal peut saisir pour un autre pays ?',
    'Où voir le motif de rejet ?',
    'Quelles données alimentent la carte ?',
  ]],
  ['Merci', [
    'Exercice pratique : 1 saisie test',
    'Questions / support AUCTC',
    'docs/training/ONBOARDING.md pour le détail',
  ], 'AUCTC — CAERT v2.0.0-prod'],
];

async function main() {
  const children = coverPage(
    'Support de formation',
    'Slides — onboarding & workflows CAERT',
    [
      ['Format', 'Word (export PDF via Word)'],
      ['Durée cible', '2–3 heures avec exercices'],
      ['Nombre de slides', String(slides.length)],
    ],
  );

  children.push(new Paragraph({
    spacing: { before: pt(20), after: pt(20) },
    children: [new TextRun({
      text: 'Instructions animateur : une page = une slide. Mode présentation Word ou export PDF plein écran.',
      size: pt(10),
      italics: true,
      color: GRAY,
      font: 'Calibri',
    })],
  }));
  children.push(pb());

  slides.forEach(([title, bullets, note]) => {
    children.push(...slide(title, bullets, note));
  });

  const doc = new Document({
    title: 'CAERT Training Slides',
    sections: [{
      properties: {
        page: {
          size: { orientation: 'landscape' },
          margin: { top: 720, bottom: 720, left: 1080, right: 1080 },
        },
      },
      headers: {
        default: new Header({
          children: [new Paragraph({
            alignment: AlignmentType.RIGHT,
            children: [new TextRun({ text: 'AUCTC — CAERT Formation', size: 14, color: GRAY, font: 'Calibri' })],
          })],
        }),
      },
      footers: {
        default: new Footer({
          children: [new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [
              new TextRun({ text: 'Slide ', size: 14, color: GRAY }),
              new TextRun({ children: [PageNumber.CURRENT], size: 14, color: GRAY }),
            ],
          })],
        }),
      },
      children,
    }],
  });

  fs.mkdirSync(path.dirname(OUT), { recursive: true });
  fs.writeFileSync(OUT, await Packer.toBuffer(doc));
  console.log('OK:', OUT);
  console.log('PDF: Word → Fichier → Enregistrer sous → PDF');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
