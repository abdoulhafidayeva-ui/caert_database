/**
 * Word + PDF : lancement IIS, accès Wi‑Fi, redémarrage auto (guide court).
 */
import { Document, Packer, Header, Footer, PageNumber, AlignmentType, TextRun, Paragraph } from 'docx';
import { spawnSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { coverPage, markdownToParagraphs } from './lib/docx_helpers.mjs';

const ROOT = process.cwd();
const DOCS = path.join(ROOT, 'docs');
const OUT = path.join(ROOT, 'deliverables');
const OUT_WORD = path.join(OUT, 'word');
const SRC = 'infrastructure/AUCTC_Lancement_Acces_Redemarrage.md';
const BASENAME = 'AUCTC_Lancement_Acces_Redemarrage_2026';

function readMd() {
  return fs.readFileSync(path.join(DOCS, SRC), 'utf8');
}

function buildDocument(children) {
  return new Document({
    title: 'AUCTC — Lancement, accès Wi‑Fi, redémarrage',
    creator: 'AUCTC',
    sections: [{
      properties: { page: { margin: { top: 1260, bottom: 1260, left: 1260, right: 1260 } } },
      headers: {
        default: new Header({
          children: [new Paragraph({
            alignment: AlignmentType.RIGHT,
            children: [new TextRun({
              text: 'AUCTC | Lancement & accès réseau',
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

function mdToHtml(md) {
  const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  const lines = md.replace(/\r\n/g, '\n').split('\n');
  const out = [];
  let inCode = false;
  let code = [];

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const t = line.trim();
    if (t.startsWith('```')) {
      if (inCode) {
        out.push(`<pre><code>${esc(code.join('\n'))}</code></pre>`);
        code = [];
      }
      inCode = !inCode;
      continue;
    }
    if (inCode) {
      code.push(line);
      continue;
    }
    if (t.startsWith('|') && t.endsWith('|')) {
      const rows = [];
      while (i < lines.length && lines[i].trim().startsWith('|')) {
        rows.push(lines[i].trim());
        i++;
      }
      i--;
      if (rows.length >= 2) {
        const parse = (r) => r.split('|').slice(1, -1).map((c) => esc(c.trim().replace(/\*\*/g, '')));
        const headers = parse(rows[0]);
        const body = rows.slice(2).filter((r) => !r.includes('---')).map(parse);
        out.push('<table><tr>' + headers.map((h) => `<th>${h}</th>`).join('') + '</tr>'
          + body.map((r) => '<tr>' + r.map((c) => `<td>${c}</td>`).join('') + '</tr>').join('') + '</table>');
      }
      continue;
    }
    if (t.startsWith('# ')) out.push(`<h1>${esc(t.slice(2))}</h1>`);
    else if (t.startsWith('## ')) out.push(`<h2>${esc(t.slice(3))}</h2>`);
    else if (t.startsWith('### ')) out.push(`<h3>${esc(t.slice(4))}</h3>`);
    else if (t.startsWith('- [ ] ')) out.push(`<p>☐ ${esc(t.slice(6))}</p>`);
    else if (t.startsWith('- ')) out.push(`<li>${esc(t.slice(2).replace(/\*\*/g, ''))}</li>`);
    else if (t === '---') out.push('<hr/>');
    else if (t.startsWith('> ')) out.push(`<p class="note"><em>${esc(t.slice(2))}</em></p>`);
    else if (t) {
      out.push(`<p>${esc(t).replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>').replace(/`([^`]+)`/g, '<code>$1</code>')}</p>`);
    }
  }
  return out.join('\n');
}

function writeHtml(md, htmlPath) {
  const html = `<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8">
<title>AUCTC — Lancement, accès, redémarrage</title>
<style>
@page{margin:15mm}body{font-family:Calibri,'Segoe UI',Arial,sans-serif;font-size:11pt;line-height:1.45;max-width:210mm;margin:0 auto;padding:10mm;color:#1a1a1a}
h1{color:#00529b;font-size:18pt;border-bottom:2px solid #00529b;page-break-before:always}h1:first-of-type{page-break-before:avoid}
h2{color:#00529b;font-size:14pt;margin-top:16px}h3{font-size:12pt}
pre{background:#f4f7fa;border:1px solid #d0d7de;padding:8px;font-size:9pt;white-space:pre-wrap}
table{border-collapse:collapse;width:100%;margin:8px 0;font-size:10pt}th,td{border:1px solid #ccc;padding:5px 8px}th{background:#e8f0f8;color:#00529b}
.note{color:#444}.cover{text-align:center;padding:40px 0;page-break-after:always}.cover h1{border:none;page-break-before:avoid}
</style></head><body>
<div class="cover"><p>UNION AFRICAINE — AUCTC</p>
<h1>Lancement, accès Wi‑Fi<br>et redémarrage automatique</h1>
<p>Guide ciblé — Windows Server / réseau local</p></div>
${mdToHtml(md)}
</body></html>`;
  fs.writeFileSync(htmlPath, html, 'utf8');
  console.log('OK:', htmlPath);
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

async function main() {
  fs.mkdirSync(OUT_WORD, { recursive: true });
  const md = readMd();
  const docxPath = path.join(OUT_WORD, `${BASENAME}.docx`);
  const pdfPath = path.join(OUT, `${BASENAME}.pdf`);
  const htmlPath = path.join(OUT, `${BASENAME}.html`);

  const children = [
    ...coverPage('Lancement, accès Wi‑Fi & redémarrage', 'Guide ciblé — juillet 2026', [
      ['Document', 'AUCTC-LANCER-ACCES-2026'],
      ['Périmètre', 'IP fixe + Internet, IIS, Wi‑Fi, reboot auto'],
      ['Hors périmètre', 'Installation PHP / MySQL / clone'],
    ]),
    ...markdownToParagraphs(md),
  ];
  fs.writeFileSync(docxPath, await Packer.toBuffer(buildDocument(children)));
  console.log('OK:', docxPath);

  writeHtml(md, htmlPath);
  const edge = findEdge();
  if (edge) {
    const uri = 'file:///' + htmlPath.replace(/\\/g, '/');
    const r = spawnSync(edge, ['--headless=new', '--disable-gpu', `--print-to-pdf=${pdfPath}`, uri], { timeout: 90000 });
    if (r.status === 0 && fs.existsSync(pdfPath)) {
      console.log('OK:', pdfPath);
    } else {
      console.warn('PDF : ouvrir le Word → Enregistrer sous PDF');
    }
  }

  console.log('Done.');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
