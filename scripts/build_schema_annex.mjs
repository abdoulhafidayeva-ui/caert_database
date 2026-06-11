/** Annexe — Schéma base de données CAERT (état actuel) */
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
                           │                       │ 1 (créateur)
                           │ N                     │
                           │         ┌─────────────▼──────────────────────────────┐
                           │         │              all_data                       │
                           └────────►│──────────────────────────────────────────────│
                                     │ id (PK)                                      │
                                     │ details, localite, date_attaque              │
                                     │ victimes (morts/blessés/disparus par catégorie)│
                                     │ totaux, otages, libérés, terroriste_arretes  │
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

    Tables de référence (listes paramétrables) — chacune liée à users (créateur)
    et référencée par all_data :

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

    Tables isolées (sans relation FK vers le reste) :

    ┌──────────┐  ┌────────────┐
    │  roles   │  │ app_param  │
    │  label   │  │ name, logo │
    └──────────┘  │ (install)  │
                  └────────────┘
`;

const doc = new Document({
  title: "Annexe — Schéma BDD CAERT",
  sections: [{
    properties: { page: { margin: { top: 1260, bottom: 1260, left: 1260, right: 1260 } } },
    headers: { default: new Header({ children: [new Paragraph({
      alignment: AlignmentType.RIGHT,
      border: { bottom: { color: BLUE, size: 4, style: BorderStyle.SINGLE } },
      children: [new TextRun({ text: "AUCTC — Annexe schéma BDD CAERT | Complément rapport d'inception", size: pt(8), color: GRAY, italics: true })]
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
        children: [new TextRun({ text: "UNION AFRICAINE — AUCTC", size: pt(12), color: GRAY })] }),
      new Paragraph({ alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: "ANNEXE AU RAPPORT D'INCEPTION", size: pt(14), bold: true, color: BLUE })] }),
      new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 80 },
        children: [new TextRun({ text: "Schéma de la base de données — État actuel (CAERT)", size: pt(13), color: DARK })] }),
      new Paragraph({ alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: "Document complémentaire · 26 mai 2026 · v1.0", size: pt(10), italics: true, color: GRAY })] }),
      new Paragraph({ spacing: { before: 200 } }),
      body("Ce document complète le rapport d'inception (AUCTC_CAERT_Inception_Report_2026.docx). Il décrit exclusivement le modèle de données tel qu'il existe aujourd'hui dans le code Symfony / Doctrine, sans proposition de modernisation."),
      body("Source : entités src/Entity/, migration Version20260519160612, stratégie de nommage underscore (MySQL 8)."),

      h1("1. Vue d'ensemble"),
      body("Le système compte 13 tables métier. La table centrale all_data concentre chaque incident terroriste enregistré. Huit tables de référence (paramètres) alimentent les classifications. La géographie est limitée à region → pays + un champ texte localite (pas de coordonnées GPS)."),

      tbl(["Rôle", "Tables"], [
        ["Fait central (incidents)", "all_data"],
        ["Géographie", "region, pays"],
        ["Référentiels / paramètres", "attaque, cible, moyen_attaque, materiel_attaque, materiaux, perpetrateurs, espace"],
        ["Utilisateurs & sécurité", "users"],
        ["Autres (isolées)", "roles, app_param"],
      ], [35, 65]),

      h1("2. Diagramme des relations"),
      body("Légende : PK = clé primaire · FK = clé étrangère · (UQ) = unique · 1/N = cardinalité", { italics: true }),
      ...ER_DIAGRAM.trim().split("\n").map(line => mono(line)),

      pb(),

      h1("3. Matrice des relations (clés étrangères)"),
      tbl(["Table source", "Colonne FK", "Table cible", "Relation"], [
        ["pays", "region_id", "region", "N pays → 1 région"],
        ["users", "pays_id", "pays", "0..1 user → 1 pays (point focal)"],
        ["all_data", "pays_id", "pays", "N incidents → 1 pays"],
        ["all_data", "user_id", "users", "N incidents → 1 saisisseur"],
        ["all_data", "attaque_id", "attaque", "N → 1 type d'attaque"],
        ["all_data", "cible_id", "cible", "N → 1 type de cible"],
        ["all_data", "moyen_attaque_id", "moyen_attaque", "N → 1 moyen"],
        ["all_data", "materiel_attaque_id", "materiel_attaque", "N → 1 matériel d'attaque"],
        ["all_data", "materieaux_id", "materiaux", "N → 1 matériel récupéré"],
        ["all_data", "perpetrateur_id", "perpetrateurs", "N → 1 groupe"],
        ["all_data", "espace_id", "espace", "N → 1 espace / terrain"],
        ["attaque", "user_id", "users", "N → 1 créateur"],
        ["cible", "user_id", "users", "N → 1 créateur"],
        ["moyen_attaque", "user_id", "users", "N → 1 créateur"],
        ["materiel_attaque", "user_id", "users", "N → 1 créateur"],
        ["materiaux", "user_id", "users", "N → 1 créateur"],
        ["perpetrateurs", "user_id", "users", "N → 1 créateur"],
        ["espace", "user_id", "users", "N → 1 créateur"],
      ], [22, 22, 22, 34]),

      h1("4. Détail des tables"),

      h2("4.1 all_data — Table centrale (incidents)"),
      tbl(["Colonne", "Type", "Description"], [
        ["id", "INT PK", "Identifiant"],
        ["details", "VARCHAR(255)", "Description de l'incident"],
        ["date_attaque", "DATETIME", "Date de l'attaque"],
        ["localite", "VARCHAR(255)", "Localité (texte libre, pas de GPS)"],
        ["mort_civil / mort_securite_militaire / mort_terroriste", "INT", "Décès par catégorie"],
        ["disparu_* (3 colonnes)", "INT", "Disparus par catégorie"],
        ["blesse_* (3 colonnes)", "INT", "Blessés par catégorie"],
        ["total_deces / total_disparus / total_blesses", "INT", "Totaux calculés"],
        ["otages / liberes / terroriste_arretes", "INT", "Autres indicateurs"],
        ["autres / remarque", "VARCHAR(255)", "Champs libres"],
        ["is_published", "TINYINT", "Publié (analytics) ou non"],
        ["objet_rejet", "VARCHAR(255)", "Motif de rejet"],
        ["created_at", "DATETIME", "Date de saisie"],
        ["8 × *_id", "INT FK", "Voir matrice §3"],
      ], [32, 18, 50]),

      h2("4.2 region & pays — Géographie"),
      tbl(["Table", "Colonnes principales"], [
        ["region", "id, libelle, code"],
        ["pays", "id, libelle, code, capitale, region_id → region"],
      ], [25, 75]),

      h2("4.3 Tables de référence (paramètres)"),
      body("Structure commune : id, libelle (UNIQUE), created_at, user_id → users. Utilisées comme listes déroulantes à la saisie et à l'import Excel."),
      tbl(["Table SQL", "Entité PHP", "Libellé métier"], [
        ["attaque", "Attaque", "Types d'attaque"],
        ["cible", "Cible", "Types de cible"],
        ["moyen_attaque", "MoyenAttaque", "Moyens d'attaque"],
        ["materiel_attaque", "MaterielAttaque", "Matériels d'attaque"],
        ["materiaux", "Materiaux", "Matériaux récupérés"],
        ["perpetrateurs", "Perpetrateurs", "Groupes terroristes"],
        ["espace", "Espace", "Espace / terrain"],
      ], [28, 28, 44]),

      h2("4.4 users — Utilisateurs"),
      tbl(["Colonne", "Description"], [
        ["id, name, prenoms, email (unique)", "Identité"],
        ["password, roles (JSON)", "Authentification Symfony"],
        ["profil, fonction, organisation", "Profil métier"],
        ["enable, is_verified, token*", "Activation compte"],
        ["pays_id → pays", "Pays du point focal (relation 1-1)"],
      ], [35, 65]),

      h2("4.5 Tables sans relation FK"),
      tbl(["Table", "Rôle"], [
        ["roles", "Libellés de rôles en base — non reliée aux rôles Symfony (users.roles JSON)"],
        ["app_param", "Paramètres d'installation (nom, logo, URL, email)"],
      ], [25, 75]),

      h1("5. Notes pour la lecture du schéma"),
      body("• all_data est une table « large » : ~25 colonnes + 8 FK — modèle dénormalisé adapté à la saisie Excel mais peu optimal pour l'analyse et l'intégrité.", { italics: false }),
      body("• Aucune table d'audit, d'historique ou de versionnement n'existe."),
      body("• Aucune colonne géospatiale (latitude/longitude) — la localisation repose sur localite (texte) et pays."),
      body("• La migration Version20260519160612 ne crée que users ; les autres tables préexistaient (schéma historique non entièrement versionné)."),
      new Paragraph({ spacing: { before: 200 } }),
      new Paragraph({ alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: "— Fin de l'annexe — À joindre au rapport d'inception CAERT", size: pt(10), color: GRAY, italics: true })] }),
    ]
  }]
});

const out = path.join(process.cwd(), "AUCTC_CAERT_Annexe_Schema_BDD.docx");
Packer.toBuffer(doc).then(buf => { fs.writeFileSync(out, buf); console.log("OK:", out); });
