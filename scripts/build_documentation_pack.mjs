/**
 * Generate consolidated Word documentation pack from Markdown sources.
 * Output: deliverables/AUCTC_CAERT_Documentation_Pack_2026.docx
 *         deliverables/word/*.docx (individual manuals)
 */
import { Document, Packer, Header, Footer, PageNumber, AlignmentType, TextRun, Paragraph } from 'docx';
import fs from 'fs';
import path from 'path';
import { coverPage, h1, pb, markdownToParagraphs } from './lib/docx_helpers.mjs';

const ROOT = process.cwd();
const DOCS = path.join(ROOT, 'docs');
const OUT = path.join(ROOT, 'deliverables');
const OUT_WORD = path.join(OUT, 'word');

const PACK_SECTIONS = [
  { file: 'README.md', title: 'Index documentation' },
  { file: 'TRAINING_DOCUMENTATION_PHASE.md', title: 'Livraison phase documentation' },
  { file: 'technical/TECHNICAL_DOCUMENTATION.md', title: 'Documentation technique' },
  { file: 'database/DATABASE_DOCUMENTATION.md', title: 'Documentation base de données' },
  { file: 'infrastructure/INFRASTRUCTURE_DOCUMENTATION.md', title: 'Documentation infrastructure' },
  { file: 'api/API_DOCUMENTATION.md', title: 'Documentation API' },
  { file: 'security/SECURITY_DOCUMENTATION.md', title: 'Documentation sécurité' },
  { file: 'etl/ETL_DOCUMENTATION.md', title: 'Documentation ETL' },
  { file: 'gis/GIS_DOCUMENTATION.md', title: 'Documentation SIG' },
  { file: 'DEPLOYMENT.md', title: 'Guide de déploiement' },
  { file: 'OPERATIONS.md', title: 'Opérations et continuité' },
  { file: 'maintenance/MAINTENANCE_GUIDE.md', title: 'Guide de maintenance' },
  { file: 'troubleshooting/TROUBLESHOOTING_GUIDE.md', title: 'Guide de dépannage' },
  { file: 'user/USER_MANUAL.md', title: 'Manuel utilisateur' },
  { file: 'admin/ADMINISTRATOR_MANUAL.md', title: 'Manuel administrateur' },
  { file: 'training/ONBOARDING.md', title: 'Formation — Onboarding' },
  { file: 'training/ANALYST_GUIDE.md', title: 'Formation — Guide analyste' },
  { file: 'training/SYSADMIN_PROCEDURES.md', title: 'Formation — Procédures IT' },
];

const STANDALONE = [
  { src: 'user/USER_MANUAL.md', out: 'CAERT_Manuel_Utilisateur_2026.docx' },
  { src: 'admin/ADMINISTRATOR_MANUAL.md', out: 'CAERT_Manuel_Administrateur_2026.docx' },
  { src: 'training/ONBOARDING.md', out: 'CAERT_Formation_Onboarding_2026.docx' },
  { src: 'training/ANALYST_GUIDE.md', out: 'CAERT_Guide_Analyste_2026.docx' },
];

function readMd(rel) {
  const p = path.join(DOCS, rel);
  if (!fs.existsSync(p)) {
    console.warn('Missing:', p);
    return '';
  }
  return fs.readFileSync(p, 'utf8');
}

function buildDocument(children, title) {
  return new Document({
    title,
    creator: 'AUCTC CAERT',
    sections: [{
      properties: {
        page: { margin: { top: 1260, bottom: 1260, left: 1260, right: 1260 } },
      },
      headers: {
        default: new Header({
          children: [new Paragraph({
            alignment: AlignmentType.RIGHT,
            children: [new TextRun({
              text: 'AUCTC — CAERT | ' + title,
              size: 16,
              color: '555555',
              italics: true,
              font: 'Calibri',
            })],
          })],
        }),
      },
      footers: {
        default: new Footer({
          children: [new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [
              new TextRun({ text: 'Page ', size: 16, color: '555555' }),
              new TextRun({ children: [PageNumber.CURRENT], size: 16, color: '555555' }),
            ],
          })],
        }),
      },
      children,
    }],
  });
}

async function writeDoc(doc, filepath) {
  fs.mkdirSync(path.dirname(filepath), { recursive: true });
  const buf = await Packer.toBuffer(doc);
  fs.writeFileSync(filepath, buf);
  console.log('OK:', filepath);
}

async function main() {
  fs.mkdirSync(OUT_WORD, { recursive: true });

  const packChildren = coverPage(
    'Pack documentation institutionnelle',
    'Training & Documentation Phase — juin 2026',
    [
      ['Document', 'AUCTC-CAERT-DOC-PACK-2026'],
      ['Version plateforme', '2.0.0-prod'],
      ['Classification', 'Interne — sensible'],
      ['Format source', 'Markdown (docs/)'],
      ['Export PDF', 'Ouvrir dans Word → Enregistrer sous PDF'],
    ],
  );

  packChildren.push(h1('Sommaire du pack'));
  PACK_SECTIONS.forEach((s, idx) => {
    packChildren.push(new Paragraph({
      children: [new TextRun({ text: `${idx + 1}. ${s.title}`, size: 22, font: 'Calibri' })],
      spacing: { before: 60, after: 60 },
    }));
  });
  packChildren.push(pb());

  for (const section of PACK_SECTIONS) {
    const md = readMd(section.file);
    if (!md) continue;
    packChildren.push(h1(section.title));
    packChildren.push(...markdownToParagraphs(md));
    packChildren.push(pb());
  }

  await writeDoc(
    buildDocument(packChildren, 'Documentation Pack'),
    path.join(OUT, 'AUCTC_CAERT_Documentation_Pack_2026.docx'),
  );

  for (const item of STANDALONE) {
    const md = readMd(item.src);
    const title = item.out.replace('.docx', '').replace(/_/g, ' ');
    const children = [
      ...coverPage(title, 'CAERT / AUCTC', [
        ['Source', 'docs/' + item.src],
        ['Classification', 'Interne — sensible'],
      ]),
      ...markdownToParagraphs(md),
    ];
    await writeDoc(buildDocument(children, title), path.join(OUT_WORD, item.out));
  }

  console.log('\nDone. PDF: open .docx in Microsoft Word → Save as PDF');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
