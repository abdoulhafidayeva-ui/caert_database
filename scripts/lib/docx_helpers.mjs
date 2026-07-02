/** Shared helpers for AUCTC / CAERT Word deliverables */
import {
  Paragraph, TextRun, HeadingLevel, Table, TableRow, TableCell,
  WidthType, BorderStyle, AlignmentType, ShadingType, PageBreak, VerticalAlign,
} from 'docx';

export const BLUE = '00529B';
export const DARK = '1A1A1A';
export const GRAY = '555555';
export const LIGHT = 'F4F7FA';
export const WHITE = 'FFFFFF';
export const HDR = '00529B';
export const ALT = 'E8F0F8';
export const pt = (n) => n * 2;

export const body = (t, o = {}) => new Paragraph({
  children: [new TextRun({ text: t, size: pt(11), color: DARK, font: 'Calibri', ...o })],
  spacing: { before: 80, after: 80, line: 276 },
});

export const h1 = (t) => new Paragraph({
  heading: HeadingLevel.HEADING_1,
  spacing: { before: 360, after: 160 },
  children: [new TextRun({ text: t, size: pt(16), bold: true, color: BLUE, font: 'Calibri' })],
});

export const h2 = (t) => new Paragraph({
  spacing: { before: 280, after: 120 },
  children: [new TextRun({ text: t, size: pt(13), bold: true, color: BLUE, font: 'Calibri' })],
});

export const h3 = (t) => new Paragraph({
  spacing: { before: 200, after: 80 },
  children: [new TextRun({ text: t, size: pt(12), bold: true, color: DARK, font: 'Calibri' })],
});

export const bullet = (t) => new Paragraph({
  bullet: {},
  spacing: { before: 60, after: 60 },
  children: [new TextRun({ text: t, size: pt(11), color: DARK, font: 'Calibri' })],
});

export const pb = () => new Paragraph({ children: [new PageBreak()] });
export const sp = () => new Paragraph({ spacing: { before: 120, after: 120 } });

export function coverPage(title, subtitle, metaRows) {
  return [
    new Paragraph({ spacing: { before: pt(50) } }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: 'UNION AFRICAINE — AUCTC', size: pt(14), color: GRAY, font: 'Calibri' })],
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: 'CAERT — Plateforme de reporting d\'incidents', size: pt(18), bold: true, color: BLUE, font: 'Calibri' })],
    }),
    sp(),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      shading: { fill: BLUE },
      spacing: { before: 200, after: 200 },
      children: [new TextRun({ text: title.toUpperCase(), size: pt(18), bold: true, color: WHITE, font: 'Calibri' })],
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { before: 160 },
      children: [new TextRun({ text: subtitle, size: pt(12), color: GRAY, font: 'Calibri' })],
    }),
    sp(),
    sp(),
    tbl(['Champ', 'Valeur'], metaRows, [35, 65]),
    pb(),
  ];
}

export function tbl(headers, rows, widths) {
  const tot = widths.reduce((a, b) => a + b, 0);
  const mk = (c, hdr, alt) => {
    const txt = typeof c === 'string' ? c : c.text;
    const bold = hdr || (typeof c === 'object' && c.bold);
    return new TableCell({
      shading: { fill: hdr ? HDR : (alt ? ALT : WHITE) },
      margins: { top: 80, bottom: 80, left: 140, right: 140 },
      verticalAlign: VerticalAlign.CENTER,
      children: [new Paragraph({
        children: [new TextRun({ text: txt, size: pt(10), bold, color: hdr ? WHITE : DARK, font: 'Calibri' })],
      })],
    });
  };
  const tableRows = [];
  if (headers.length) {
    tableRows.push(new TableRow({ tableHeader: true, children: headers.map((h) => mk(h, true, false)) }));
  }
  rows.forEach((r, i) => {
    tableRows.push(new TableRow({ children: r.map((c) => mk(c, false, i % 2 === 1)) }));
  });
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    columnWidths: widths.map((w) => Math.round((w / tot) * 9360)),
    rows: tableRows,
  });
}

/** Simple markdown → docx Paragraph[] (headings, bullets, tables, body) */
export function markdownToParagraphs(md) {
  const lines = md.replace(/\r\n/g, '\n').split('\n');
  const out = [];
  let i = 0;
  let inCode = false;

  while (i < lines.length) {
    const line = lines[i];
    const trimmed = line.trim();

    if (trimmed.startsWith('```')) {
      inCode = !inCode;
      i++;
      continue;
    }
    if (inCode) {
      out.push(body(trimmed, { font: 'Consolas', size: pt(9) }));
      i++;
      continue;
    }

    if (trimmed === '---' || trimmed === '***') {
      i++;
      continue;
    }

    if (trimmed.startsWith('|') && trimmed.endsWith('|')) {
      const tableLines = [];
      while (i < lines.length && lines[i].trim().startsWith('|')) {
        tableLines.push(lines[i].trim());
        i++;
      }
      if (tableLines.length >= 2 && tableLines[1].includes('---')) {
        const parseRow = (r) => r.split('|').slice(1, -1).map((c) => c.trim());
        const headers = parseRow(tableLines[0]);
        const rows = tableLines.slice(2).map(parseRow).filter((r) => r.some((c) => c));
        if (headers.length && rows.length) {
          const w = headers.map(() => Math.floor(100 / headers.length));
          out.push(tbl(headers, rows, w));
        }
      }
      continue;
    }

    if (trimmed.startsWith('# ')) {
      out.push(h1(stripMd(trimmed.slice(2))));
    } else if (trimmed.startsWith('## ')) {
      out.push(h2(stripMd(trimmed.slice(3))));
    } else if (trimmed.startsWith('### ')) {
      out.push(h3(stripMd(trimmed.slice(4))));
    } else if (trimmed.startsWith('- ') || trimmed.startsWith('* ')) {
      out.push(bullet(stripMd(trimmed.slice(2))));
    } else if (/^\d+\.\s/.test(trimmed)) {
      out.push(body(stripMd(trimmed)));
    } else if (trimmed.startsWith('> ')) {
      out.push(body(stripMd(trimmed.slice(2)), { italics: true, color: GRAY }));
    } else if (trimmed) {
      out.push(body(stripMd(trimmed)));
    }
    i++;
  }
  return out;
}

function stripMd(text) {
  return text
    .replace(/\*\*([^*]+)\*\*/g, '$1')
    .replace(/`([^`]+)`/g, '$1')
    .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1');
}

export function slide(title, bullets, note) {
  const items = [
    new Paragraph({
      spacing: { before: pt(40), after: pt(20) },
      children: [new TextRun({ text: title, size: pt(22), bold: true, color: BLUE, font: 'Calibri' })],
    }),
    new Paragraph({
      spacing: { after: pt(16) },
      border: { bottom: { style: BorderStyle.SINGLE, size: 8, color: BLUE } },
      children: [new TextRun({ text: ' ', size: pt(4) })],
    }),
  ];
  bullets.forEach((b) => {
    items.push(new Paragraph({
      bullet: { level: 0 },
      spacing: { before: 100, after: 100 },
      children: [new TextRun({ text: b, size: pt(14), color: DARK, font: 'Calibri' })],
    }));
  });
  if (note) {
    items.push(sp());
    items.push(new Paragraph({
      children: [new TextRun({ text: note, size: pt(10), italics: true, color: GRAY, font: 'Calibri' })],
    }));
  }
  items.push(pb());
  return items;
}
