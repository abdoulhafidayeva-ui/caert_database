/** Annex — CAERT Database Schema (current state) — English */
import {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  WidthType, BorderStyle, AlignmentType, ShadingType, PageBreak,
  Header, Footer, PageNumber, VerticalAlign
} from "docx";
import fs from "fs";
import path from "path";

const BLUE = "00529B"; const DARK = "1A1A1A"; const GRAY = "555555";
const LIGHT = "F4F7FA"; const WHITE = "FFFFFF"; const HDR = "00529B"; const ALT = "E8F0F8";
const pt = (n) => n * 2;

const body = (t, o = {}) => new Paragraph({
  children: [new TextRun({ text: t, size: pt(11), color: DARK, font: "Calibri", ...o })],
  spacing: { before: 80, after: 80 }
});
const h1 = (t) => new Paragraph({ spacing: { before: 280, after: 140 },
  children: [new TextRun({ text: t, size: pt(15), bold: true, color: BLUE, font: "Calibri" })] });
const h2 = (t) => new Paragraph({ spacing: { before: 200, after: 100 },
  children: [new TextRun({ text: t, size: pt(12), bold: true, color: BLUE, font: "Calibri" })] });
const mono = (t) => new Paragraph({ shading: { fill: LIGHT }, spacing: { before: 60, after: 60 },
  children: [new TextRun({ text: t, font: "Consolas", size: pt(8), color: DARK })] });
const pb = () => new Paragraph({ children: [new PageBreak()] });

function tbl(headers, rows, widths) {
  const tot = widths.reduce((a, b) => a + b, 0);
  const mk = (c, hdr, alt) => {
    const txt = typeof c === "string" ? c : c.text;
    const bold = hdr || (typeof c === "object" && c.bold);
    return new TableCell({
      shading: { fill: hdr ? HDR : (alt ? ALT : WHITE) },
      margins: { top: 60, bottom: 60, left: 120, right: 120 },
      verticalAlign: VerticalAlign.CENTER,
      children: [new Paragraph({ children: [new TextRun({
        text: txt, size: pt(9), bold, color: hdr ? WHITE : DARK, font: "Calibri"
      })] })]
    });
  };
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    columnWidths: widths.map(w => Math.round((w / tot) * 9360)),
    rows: [
      new TableRow({ tableHeader: true, children: headers.map(h => mk(h, true, false)) }),
      ...rows.map((r, i) => new TableRow({ children: r.map(c => mk(c, false, i % 2 === 1)) }))
    ]
  });
}

const ER_DIAGRAM = `
                    ┌─────────────┐
                    │   region    │
                    │─────────────│
                    │ id (PK)     │
                    │ libelle     │
                    │ code        │
                    └──────┬──────┘
                           │ 1
                           │
                           │ N
                    ┌──────▼──────┐         ┌─────────────┐
                    │    pays     │◄────────│    users    │
                    │─────────────│  0..1   │─────────────│
                    │ id (PK)     │ pays_id │ id (PK)     │
                    │ libelle     │ (UNIQUE)│ email (UQ)  │
                    │ code        │         │ roles (JSON)│
                    │ capitale    │         │ pays_id (FK)│
                    │ region_id   │         └──────┬──────┘
                    └──────┬──────┘                │
                           │                       │ 1 (creator)
                           │ N                     │
                           │         ┌─────────────▼──────────────────────────────┐
                           │         │              all_data                       │
                           └────────►│──────────────────────────────────────────────│
                                     │ id (PK)                                      │
                                     │ details, localite, date_attaque              │
                                     │ casualties (deaths/injured/missing by category)│
                                     │ totals, hostages, released, arrests          │
                                     │ is_published, objet_rejet                    │
                                     │ created_at                                   │
                                     │──────── FK ──────────────────────────────────│
                                     │ pays_id ──────────────► pays                 │
                                     │ user_id ──────────────► users                │
                                     │ attaque_id ───────────► attaque              │
                                     │ cible_id ─────────────► cible                │
                                     │ moyen_attaque_id ─────► moyen_attaque        │
                                     │ materiel_attaque_id ──► materiel_attaque     │
                                     │ materieaux_id ────────► materiaux            │
                                     │ perpetrateur_id ──────► perpetrateurs        │
                                     │ espace_id ────────────► espace               │
                                     └──────────────────────────────────────────────┘

    Reference tables (configurable lists) — each linked to users (creator)
    and referenced by all_data:

    ┌──────────┐ ┌──────────┐ ┌───────────────┐ ┌──────────────┐ ┌─────────────┐
    │ attaque  │ │  cible   │ │ moyen_attaque │ │materiel_attaque│ │ materiaux │
    │ libelle  │ │ libelle  │ │   libelle     │ │   libelle    │ │  libelle  │
    │ user_id  │ │ user_id  │ │   user_id     │ │   user_id    │ │  user_id  │
    └──────────┘ └──────────┘ └───────────────┘ └──────────────┘ └─────────────┘

    ┌──────────────┐ ┌──────────┐
    │ perpetrateurs│ │  espace  │
    │   libelle    │ │ libelle  │
    │   user_id    │ │ user_id  │
    └──────────────┘ └──────────┘

    Standalone tables (no FK relationship to the rest):

    ┌──────────┐  ┌────────────┐
    │  roles   │  │ app_param  │
    │  label   │  │ name, logo │
    └──────────┘  │ (install)  │
                  └────────────┘
`;

const doc = new Document({
  title: "Annex — CAERT Database Schema",
  sections: [{
    properties: { page: { margin: { top: 1260, bottom: 1260, left: 1260, right: 1260 } } },
    headers: { default: new Header({ children: [new Paragraph({
      alignment: AlignmentType.RIGHT,
      border: { bottom: { color: BLUE, size: 4, style: BorderStyle.SINGLE } },
      children: [new TextRun({ text: "AUCTC — CAERT Database Schema Annex | Inception Report Supplement", size: pt(8), color: GRAY, italics: true })]
    })] }) },
    footers: { default: new Footer({ children: [new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [
        new TextRun({ text: "Page ", size: pt(8), color: GRAY }),
        new TextRun({ children: [PageNumber.CURRENT], size: pt(8), color: GRAY }),
        new TextRun({ text: " / ", size: pt(8), color: GRAY }),
        new TextRun({ children: [PageNumber.TOTAL_PAGES], size: pt(8), color: GRAY }),
      ]
    })] }) },
    children: [
      new Paragraph({ spacing: { before: pt(40) } }),
      new Paragraph({ alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: "AFRICAN UNION — AUCTC", size: pt(12), color: GRAY })] }),
      new Paragraph({ alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: "INCEPTION REPORT ANNEX", size: pt(14), bold: true, color: BLUE })] }),
      new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 80 },
        children: [new TextRun({ text: "Database Schema — Current State (CAERT)", size: pt(13), color: DARK })] }),
      new Paragraph({ alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: "Supplementary document · 26 May 2026 · v1.0 EN", size: pt(10), italics: true, color: GRAY })] }),
      new Paragraph({ spacing: { before: 200 } }),
      body("This document supplements the inception report (AUCTC_CAERT_Inception_Report_2026.docx). It describes the data model as it exists today in the Symfony / Doctrine codebase, without any modernization proposals."),
      body("Source: entities in src/Entity/, migration Version20260519160612, underscore naming strategy (MySQL 8)."),

      h1("1. Overview"),
      body("The system comprises 13 business tables. The central table all_data stores each recorded terrorist incident. Eight reference (lookup) tables supply classification values. Geography is limited to region → country plus a free-text localite field (no GPS coordinates)."),

      tbl(["Role", "Tables"], [
        ["Central fact (incidents)", "all_data"],
        ["Geography", "region, pays"],
        ["Reference / lookup data", "attaque, cible, moyen_attaque, materiel_attaque, materiaux, perpetrateurs, espace"],
        ["Users & security", "users"],
        ["Other (standalone)", "roles, app_param"],
      ], [35, 65]),

      h1("2. Relationship Diagram"),
      body("Legend: PK = primary key · FK = foreign key · (UQ) = unique · 1/N = cardinality", { italics: true }),
      ...ER_DIAGRAM.trim().split("\n").map(line => mono(line)),

      pb(),

      h1("3. Relationship Matrix (Foreign Keys)"),
      tbl(["Source table", "FK column", "Target table", "Relationship"], [
        ["pays", "region_id", "region", "N countries → 1 region"],
        ["users", "pays_id", "pays", "0..1 user → 1 country (focal point)"],
        ["all_data", "pays_id", "pays", "N incidents → 1 country"],
        ["all_data", "user_id", "users", "N incidents → 1 data entry user"],
        ["all_data", "attaque_id", "attaque", "N → 1 attack type"],
        ["all_data", "cible_id", "cible", "N → 1 target type"],
        ["all_data", "moyen_attaque_id", "moyen_attaque", "N → 1 means of attack"],
        ["all_data", "materiel_attaque_id", "materiel_attaque", "N → 1 attack equipment"],
        ["all_data", "materieaux_id", "materiaux", "N → 1 recovered material"],
        ["all_data", "perpetrateur_id", "perpetrateurs", "N → 1 perpetrator group"],
        ["all_data", "espace_id", "espace", "N → 1 space / terrain type"],
        ["attaque", "user_id", "users", "N → 1 creator"],
        ["cible", "user_id", "users", "N → 1 creator"],
        ["moyen_attaque", "user_id", "users", "N → 1 creator"],
        ["materiel_attaque", "user_id", "users", "N → 1 creator"],
        ["materiaux", "user_id", "users", "N → 1 creator"],
        ["perpetrateurs", "user_id", "users", "N → 1 creator"],
        ["espace", "user_id", "users", "N → 1 creator"],
      ], [22, 22, 22, 34]),

      h1("4. Table Details"),

      h2("4.1 all_data — Central table (incidents)"),
      tbl(["Column", "Type", "Description"], [
        ["id", "INT PK", "Identifier"],
        ["details", "VARCHAR(255)", "Incident description"],
        ["date_attaque", "DATETIME", "Attack date"],
        ["localite", "VARCHAR(255)", "Locality (free text, no GPS)"],
        ["mort_civil / mort_securite_militaire / mort_terroriste", "INT", "Deaths by category"],
        ["disparu_* (3 columns)", "INT", "Missing persons by category"],
        ["blesse_* (3 columns)", "INT", "Injured by category"],
        ["total_deces / total_disparus / total_blesses", "INT", "Computed totals"],
        ["otages / liberes / terroriste_arretes", "INT", "Other indicators"],
        ["autres / remarque", "VARCHAR(255)", "Free-text fields"],
        ["is_published", "TINYINT", "Published (analytics) or not"],
        ["objet_rejet", "VARCHAR(255)", "Rejection reason"],
        ["created_at", "DATETIME", "Entry date"],
        ["8 × *_id", "INT FK", "See matrix §3"],
      ], [32, 18, 50]),

      h2("4.2 region & pays — Geography"),
      tbl(["Table", "Main columns"], [
        ["region", "id, libelle, code"],
        ["pays", "id, libelle, code, capitale, region_id → region"],
      ], [25, 75]),

      h2("4.3 Reference tables (lookup data)"),
      body("Common structure: id, libelle (UNIQUE), created_at, user_id → users. Used as dropdown lists during data entry and Excel import."),
      tbl(["SQL table", "PHP entity", "Business label"], [
        ["attaque", "Attaque", "Attack types"],
        ["cible", "Cible", "Target types"],
        ["moyen_attaque", "MoyenAttaque", "Means of attack"],
        ["materiel_attaque", "MaterielAttaque", "Attack equipment"],
        ["materiaux", "Materiaux", "Recovered materials"],
        ["perpetrateurs", "Perpetrateurs", "Terrorist groups"],
        ["espace", "Espace", "Space / terrain type"],
      ], [28, 28, 44]),

      h2("4.4 users — Users"),
      tbl(["Column", "Description"], [
        ["id, name, prenoms, email (unique)", "Identity"],
        ["password, roles (JSON)", "Symfony authentication"],
        ["profil, fonction, organisation", "Business profile"],
        ["enable, is_verified, token*", "Account activation"],
        ["pays_id → pays", "Focal point country (1-1 relationship)"],
      ], [35, 65]),

      h2("4.5 Tables without FK relationships"),
      tbl(["Table", "Role"], [
        ["roles", "Role labels in database — not linked to Symfony roles (users.roles JSON)"],
        ["app_param", "Installation settings (name, logo, URL, email)"],
      ], [25, 75]),

      h1("5. Notes for Reading the Schema"),
      body("• all_data is a wide table: ~25 columns + 8 FKs — a denormalized model suited to Excel entry but suboptimal for analytics and integrity.", { italics: false }),
      body("• No audit, history, or versioning tables exist."),
      body("• No geospatial columns (latitude/longitude) — location relies on localite (text) and country."),
      body("• Migration Version20260519160612 only creates users; other tables pre-existed (historical schema not fully version-controlled)."),
      new Paragraph({ spacing: { before: 200 } }),
      new Paragraph({ alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: "— End of annex — To be attached to the CAERT inception report", size: pt(10), color: GRAY, italics: true })] }),
    ]
  }]
});

const out = path.join(process.cwd(), "AUCTC_CAERT_Annex_Database_Schema.docx");
Packer.toBuffer(doc).then(buf => { fs.writeFileSync(out, buf); console.log("OK:", out); });
