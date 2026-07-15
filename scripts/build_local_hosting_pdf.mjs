/**

 * Generate Word + PDF for local network hosting guide (with screenshots).

 */

import { Document, Packer, Header, Footer, PageNumber, AlignmentType, TextRun, Paragraph, ImageRun } from 'docx';

import { spawnSync } from 'child_process';

import fs from 'fs';

import path from 'path';

import { coverPage, h1, h2, h3, body, bullet, tbl, markdownToParagraphs } from './lib/docx_helpers.mjs';



const ROOT = process.cwd();

const DOCS = path.join(ROOT, 'docs');

const DOC_INFRA = path.join(DOCS, 'infrastructure');

const OUT = path.join(ROOT, 'deliverables');

const OUT_WORD = path.join(OUT, 'word');

const SRC = 'infrastructure/LOCAL_NETWORK_HOSTING.md';

const BASENAME = 'AUCTC_Hebergement_Reseau_Local_2026';



function readMd() {

  return fs.readFileSync(path.join(DOCS, SRC), 'utf8');

}



function resolveImage(rel) {

  const abs = path.join(DOC_INFRA, rel.replace(/^\//, ''));

  if (!fs.existsSync(abs)) {

    console.warn('Image manquante:', abs);

    return null;

  }

  return fs.readFileSync(abs);

}



function imageParagraph(rel, alt) {

  const data = resolveImage(rel);

  if (!data) {

    return body(`[Capture : ${alt}]`, { italics: true });

  }

  return new Paragraph({

    spacing: { before: 120, after: 120 },

    children: [

      new ImageRun({

        data,

        transformation: { width: 520, height: 320 },

      }),

    ],

  });

}



/** Markdown → docx blocks, with ![alt](path) images */

function hostingMarkdownToParagraphs(md) {

  const lines = md.replace(/\r\n/g, '\n').split('\n');

  const chunks = [];

  let buf = [];

  let i = 0;



  const flushText = () => {

    if (buf.length) {

      chunks.push(...markdownToParagraphs(buf.join('\n')));

      buf = [];

    }

  };



  while (i < lines.length) {

    const line = lines[i];

    const img = line.trim().match(/^!\[([^\]]*)\]\(([^)]+)\)$/);

    if (img) {

      flushText();

      chunks.push(imageParagraph(img[2], img[1]));

      i++;

      continue;

    }

    buf.push(line);

    i++;

  }

  flushText();

  return chunks;

}



function buildDocument(children, title) {

  return new Document({

    title,

    creator: 'AUCTC',

    sections: [{

      properties: { page: { margin: { top: 1260, bottom: 1260, left: 1260, right: 1260 } } },

      headers: {

        default: new Header({

          children: [new Paragraph({

            alignment: AlignmentType.RIGHT,

            children: [new TextRun({

              text: 'AUCTC | Hébergement réseau local',

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



function mdToPrintHtml(md) {

  const lines = md.replace(/\r\n/g, '\n').split('\n');

  const out = [];

  let inCode = false;

  let codeBuf = [];



  const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');



  const flushCode = () => {

    if (codeBuf.length) {

      out.push(`<pre><code>${esc(codeBuf.join('\n'))}</code></pre>`);

      codeBuf = [];

    }

  };



  let ti = 0;

  while (ti < lines.length) {

    const line = lines[ti];

    const trimmed = line.trim();



    if (trimmed.startsWith('```')) {

      if (inCode) flushCode();

      inCode = !inCode;

      ti++;

      continue;

    }

    if (inCode) {

      codeBuf.push(line);

      ti++;

      continue;

    }



    const img = trimmed.match(/^!\[([^\]]*)\]\(([^)]+)\)$/);

    if (img) {

      const data = resolveImage(img[2]);

      if (data) {

        const b64 = data.toString('base64');

        out.push(`<figure><img src="data:image/png;base64,${b64}" alt="${esc(img[1])}" style="max-width:100%;border:1px solid #ccc;margin:12px 0"/><figcaption>${esc(img[1])}</figcaption></figure>`);

      }

      ti++;

      continue;

    }



    if (trimmed.startsWith('|') && trimmed.endsWith('|')) {

      const rows = [];

      while (ti < lines.length && lines[ti].trim().startsWith('|')) {

        rows.push(lines[ti].trim());

        ti++;

      }

      if (rows.length >= 2) {

        const parse = (r) => r.split('|').slice(1, -1).map((c) => esc(c.trim()));

        const headers = parse(rows[0]);

        const bodyRows = rows.slice(2).map(parse);

        out.push('<table><tr>' + headers.map((h) => `<th>${h}</th>`).join('') + '</tr>'

          + bodyRows.map((r) => '<tr>' + r.map((c) => `<td>${c}</td>`).join('') + '</tr>').join('') + '</table>');

      }

      continue;

    }



    if (trimmed.startsWith('# ')) out.push(`<h1>${esc(trimmed.slice(2))}</h1>`);

    else if (trimmed.startsWith('## ')) out.push(`<h2>${esc(trimmed.slice(3))}</h2>`);

    else if (trimmed.startsWith('### ')) out.push(`<h3>${esc(trimmed.slice(4))}</h3>`);

    else if (trimmed.startsWith('- ')) out.push(`<li>${esc(trimmed.slice(2).replace(/\*\*/g, ''))}</li>`);

    else if (trimmed === '---') out.push('<hr/>');

    else if (trimmed) out.push(`<p>${esc(trimmed).replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>').replace(/`([^`]+)`/g, '<code>$1</code>')}</p>`);



    ti++;

  }

  flushCode();

  return out.join('\n');

}



function writePrintHtml(md, htmlPath) {

  const bodyHtml = mdToPrintHtml(md);

  const html = `<!DOCTYPE html>

<html lang="fr"><head><meta charset="utf-8">

<title>AUCTC — Hébergement réseau local</title>

<style>

@page{margin:15mm}body{font-family:Calibri,'Segoe UI',Arial,sans-serif;font-size:11pt;color:#1a1a1a;line-height:1.45;max-width:210mm;margin:0 auto;padding:10mm}

h1{color:#00529b;font-size:18pt;border-bottom:2px solid #00529b;page-break-before:always}h1:first-of-type{page-break-before:avoid}

h2{color:#00529b;font-size:14pt;margin-top:16px}h3{font-size:12pt}

pre{background:#f4f7fa;border:1px solid #d0d7de;padding:8px;font-size:9pt;white-space:pre-wrap}

table{border-collapse:collapse;width:100%;margin:8px 0;font-size:10pt}th,td{border:1px solid #ccc;padding:5px 8px}th{background:#e8f0f8}

figure{margin:14px 0;text-align:center}figcaption{font-size:9pt;color:#555;font-style:italic}

.cover{text-align:center;padding:30px 0;page-break-after:always}.cover h1{border:none;page-break-before:avoid}

</style></head><body>

<div class="cover"><p>UNION AFRICAINE — AUCTC</p><h1>Hébergement réseau local</h1>
<p>Plateforme AUCTC — IIS + PHP + MySQL — HTTP</p></div>

${bodyHtml}

</body></html>`;

  fs.writeFileSync(htmlPath, html, 'utf8');

  console.log('OK:', htmlPath);

}



async function writeDocx(filepath) {

  const md = readMd();

  const children = [

    ...coverPage('Hébergement réseau local AUCTC', 'Windows — guide pas à pas — juillet 2026', [

      ['Document', 'AUCTC-LOCAL-HOST-2026'],

      ['Application', 'AUCTC'],

      ['Préparation', 'Sur PC développeur (Composer)'],

      ['Serveur', 'PHP + MySQL + IIS uniquement'],

      ['Dépôt GitHub', 'abdoulhafidayeva-ui/caert_database'],

    ]),

    ...hostingMarkdownToParagraphs(md),

  ];

  fs.mkdirSync(path.dirname(filepath), { recursive: true });

  fs.writeFileSync(filepath, await Packer.toBuffer(buildDocument(children, 'Hébergement réseau local')));

  console.log('OK:', filepath);

}



function findEdge() {

  for (const p of [

    process.env['PROGRAMFILES(X86)'] + '\\Microsoft\\Edge\\Application\\msedge.exe',

    process.env.PROGRAMFILES + '\\Microsoft\\Edge\\Application\\msedge.exe',

  ].filter(Boolean)) {

    if (fs.existsSync(p)) return p;

  }

  return null;

}



function tryPdfFromBrowser(htmlPath, pdfPath) {

  const browser = findEdge();

  if (!browser) return false;

  const uri = 'file:///' + htmlPath.replace(/\\/g, '/');

  const r = spawnSync(browser, ['--headless=new', '--disable-gpu', `--print-to-pdf=${pdfPath}`, uri], { timeout: 90000 });

  if (r.status === 0 && fs.existsSync(pdfPath)) {

    console.log('OK:', pdfPath);

    return true;

  }

  return false;

}



async function main() {

  fs.mkdirSync(OUT_WORD, { recursive: true });

  const docxPath = path.join(OUT_WORD, `${BASENAME}.docx`);

  const pdfPath = path.join(OUT, `${BASENAME}.pdf`);

  const htmlPath = path.join(OUT, `${BASENAME}.html`);

  const md = readMd();



  await writeDocx(docxPath);

  writePrintHtml(md, htmlPath);



  if (!tryPdfFromBrowser(htmlPath, pdfPath)) {

    console.log('PDF : ouvrir le .docx dans Word → Enregistrer sous PDF');

  }

  console.log('Done.');

}



main().catch((e) => { console.error(e); process.exit(1); });


