/**
 * Pack consultance AUCTC
 * - word/       : rapport seul
 * - essentiels/ : pièces jointes demandées (noms uniques, numérotés)
 * - annexes/    : reste optionnel (ex. hébergement détaillé)
 */
import { Document, Packer, Header, Footer, PageNumber, AlignmentType, TextRun, Paragraph, ImageRun } from 'docx';
import fs from 'fs';
import path from 'path';
import { coverPage, markdownToParagraphs, body } from './lib/docx_helpers.mjs';

const ROOT = process.cwd();
const PACK = path.join(ROOT, 'deliverables', 'consultance_juillet_2026');
const SRC = path.join(PACK, 'sources');
const OUT_WORD = path.join(PACK, 'word');
const OUT_ESSENTIELS = path.join(PACK, 'essentiels');
const OUT_ANNEX = path.join(PACK, 'annexes');

const RAPPORT = {
  file: '01_Rapport_Consultance.md',
  out: 'AUCTC_Rapport_Consultance_2026.docx',
  title: 'Rapport de consultance',
  subtitle: 'Mise à jour et amélioration de la base de données AUCTC',
  meta: [
    ['Document', 'AUCTC — Rapport de consultance'],
    ['Statut', 'Système déployé — données historiques importées — formation à planifier'],
  ],
  header: 'AUCTC | Rapport de consultance',
};

/** Documents générés depuis sources Markdown → essentiels/ */
const ESSENTIELS_MD = [
  {
    file: '02_Deploiement_Serveur.md',
    out: '01_Guide_Deploiement_Serveur.docx',
    title: 'Guide de déploiement serveur',
    subtitle: 'Windows · IIS · MySQL — guide pas à pas illustré',
    meta: [
      ['Document', 'AUCTC — Déploiement serveur'],
      ['Public', 'IT / Exploitation — (re)installation complète'],
    ],
    header: 'AUCTC | Déploiement serveur',
    withImages: true,
  },
  {
    file: '03_Diagrammes.md',
    out: '02_Diagrammes_Synthese.docx',
    title: 'Diagrammes de synthèse',
    subtitle: 'Architecture, profils d’accès et parcours opérationnels',
    meta: [
      ['Document', 'AUCTC — Diagrammes'],
      ['Usage', 'Annexe au rapport de consultance'],
    ],
    header: 'AUCTC | Diagrammes',
  },
  {
    file: '04_Import_Donnees_Historiques.md',
    out: '03_Import_Donnees_Historiques.docx',
    title: 'Import des données historiques',
    subtitle: 'Data.xlsx — méthode et périmètre',
    meta: [
      ['Document', 'AUCTC — Import historique'],
      ['Public', 'IT / Super administrateur'],
    ],
    header: 'AUCTC | Import données historiques',
  },
];

/** Documents déjà générés ailleurs → essentiels/ (noms uniques). */
const ESSENTIELS_COPIES = [
  {
    from: path.join(ROOT, 'deliverables', 'word', 'CAERT_Manuel_Utilisateur_2026.docx'),
    to: '04_Manuel_Utilisateur.docx',
  },
  {
    from: path.join(ROOT, 'deliverables', 'word', 'CAERT_Manuel_Administrateur_2026.docx'),
    to: '05_Manuel_Administrateur.docx',
  },
  {
    from: path.join(ROOT, 'deliverables', 'word', 'CAERT_Guide_Analyste_2026.docx'),
    to: '06_Guide_Analyste.docx',
  },
  {
    from: path.join(ROOT, 'deliverables', 'AUCTC_CAERT_Training_Slides_2026.docx'),
    to: '07_Supports_Formation.docx',
  },
  {
    from: path.join(ROOT, 'deliverables', 'word', 'AUCTC_Lancement_Acces_Redemarrage_2026.docx'),
    to: '08_Lancement_Acces_Redemarrage.docx',
  },
];

/** Archive optionnelle — hors liste essentiels. */
const ANNEX_COPIES = [
  {
    from: path.join(ROOT, 'deliverables', 'word', 'AUCTC_Hebergement_Reseau_Local_2026.docx'),
    to: 'Hebergement_Reseau_Local.docx',
  },
  {
    from: path.join(ROOT, 'deliverables', 'word', 'CAERT_Formation_Onboarding_2026.docx'),
    to: 'Formation_Onboarding.docx',
  },
];

function buildDocument(headerLabel, children) {
  return new Document({
    title: headerLabel,
    creator: 'AUCTC / Abdoul-Hafid Ayeva',
    sections: [{
      properties: { page: { margin: { top: 1260, bottom: 1260, left: 1260, right: 1260 } } },
      headers: {
        default: new Header({
          children: [new Paragraph({
            alignment: AlignmentType.RIGHT,
            children: [new TextRun({
              text: headerLabel,
              size: 16, color: '555555', italics: true, font: 'Calibri',
            })],
          })],
        }),
      },
      footers: {
        default: new Footer({
          children: [new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [
              new TextRun({ text: 'Confidential — AUCTC | Page ', size: 16, color: '555555' }),
              new TextRun({ children: [PageNumber.CURRENT], size: 16, color: '555555' }),
            ],
          })],
        }),
      },
      children,
    }],
  });
}

function clearDocx(dir) {
  if (!fs.existsSync(dir)) return;
  for (const f of fs.readdirSync(dir)) {
    if (f.endsWith('.docx')) fs.unlinkSync(path.join(dir, f));
  }
}

function copyInto(dir, items, label) {
  let n = 0;
  for (const item of items) {
    if (!fs.existsSync(item.from)) {
      console.warn(label + ' manquant (ignoré):', item.from);
      continue;
    }
    const dest = path.join(dir, item.to);
    fs.copyFileSync(item.from, dest);
    console.log(label + ':', dest);
    n += 1;
  }
  return n;
}

function resolveSourceImage(relPath) {
  const cleaned = relPath.replace(/^\.\//, '').replace(/^\//, '');
  const candidates = [
    path.join(SRC, cleaned),
    path.join(SRC, 'images', path.basename(cleaned)),
    path.join(ROOT, 'docs', 'infrastructure', cleaned),
    path.join(ROOT, 'docs', 'infrastructure', 'images', 'hosting', path.basename(cleaned)),
  ];
  for (const abs of candidates) {
    if (fs.existsSync(abs)) {
      return fs.readFileSync(abs);
    }
  }
  console.warn('Image manquante:', relPath);
  return null;
}

function imageParagraph(relPath, alt) {
  const data = resolveSourceImage(relPath);
  if (!data) {
    return body(`[Capture manquante : ${alt}]`, { italics: true });
  }
  return new Paragraph({
    spacing: { before: 160, after: 80 },
    children: [
      new ImageRun({
        type: 'png',
        data,
        transformation: { width: 540, height: 330 },
        altText: { title: alt, description: alt, name: alt },
      }),
    ],
  });
}

/** Markdown → docx, avec support ![alt](chemin.png) */
function markdownWithImagesToParagraphs(md) {
  const lines = md.replace(/\r\n/g, '\n').split('\n');
  const chunks = [];
  let buf = [];

  const flushText = () => {
    if (buf.length) {
      chunks.push(...markdownToParagraphs(buf.join('\n')));
      buf = [];
    }
  };

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const img = line.trim().match(/^!\[([^\]]*)\]\(([^)]+)\)$/);
    if (img) {
      flushText();
      chunks.push(imageParagraph(img[2].trim(), img[1].trim() || 'Capture'));
      const next = lines[i + 1];
      if (next && /^\*[^*].*\*$/.test(next.trim())) {
        chunks.push(body(next.trim().replace(/^\*/, '').replace(/\*$/, ''), { italics: true }));
        i += 1;
      }
      continue;
    }
    buf.push(line);
  }
  flushText();
  return chunks;
}

async function buildOne(spec, outDir) {
  const mdPath = path.join(SRC, spec.file);
  if (!fs.existsSync(mdPath)) {
    throw new Error('Source manquante: ' + mdPath);
  }
  const md = fs.readFileSync(mdPath, 'utf8');
  const bodyParas = spec.withImages
    ? markdownWithImagesToParagraphs(md)
    : markdownToParagraphs(md);
  const children = [
    ...coverPage(spec.title, spec.subtitle, spec.meta),
    ...bodyParas,
  ];
  const outPath = path.join(outDir, spec.out);
  fs.writeFileSync(outPath, await Packer.toBuffer(buildDocument(spec.header, children)));
  console.log('OK:', outPath);
}

async function main() {
  fs.mkdirSync(OUT_WORD, { recursive: true });
  fs.mkdirSync(OUT_ESSENTIELS, { recursive: true });
  fs.mkdirSync(OUT_ANNEX, { recursive: true });

  clearDocx(OUT_WORD);
  clearDocx(OUT_ESSENTIELS);
  clearDocx(OUT_ANNEX);

  await buildOne(RAPPORT, OUT_WORD);

  for (const spec of ESSENTIELS_MD) {
    await buildOne(spec, OUT_ESSENTIELS);
  }
  const essCopies = copyInto(OUT_ESSENTIELS, ESSENTIELS_COPIES, 'Essentiel');
  const annexCount = copyInto(OUT_ANNEX, ANNEX_COPIES, 'Annex');

  console.log(
    'Pack consultance: rapport=1, essentiels=' + (ESSENTIELS_MD.length + essCopies) +
    ', annexes=' + annexCount
  );
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
