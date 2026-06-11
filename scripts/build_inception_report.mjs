/** AUCTC CAERT — Inception Report (15 sections) — EN */
import {
  Document, Packer, Paragraph, TextRun, HeadingLevel, Table, TableRow,
  TableCell, WidthType, BorderStyle, AlignmentType, ShadingType,
  PageBreak, Header, Footer, PageNumber, VerticalAlign
} from "docx";
import fs from "fs";
import path from "path";

const BLUE = "00529B"; const DARK = "1A1A1A"; const GRAY = "555555";
const LIGHT = "F4F7FA"; const RED = "B71C1C"; const RED_BG = "FFEBEE";
const ORG = "E65100"; const ORG_BG = "FFF3E0"; const WHITE = "FFFFFF";
const HDR = "00529B"; const ALT = "E8F0F8";
const pt = (n) => n * 2;

const body = (t, o = {}) => new Paragraph({
  children: [new TextRun({ text: t, size: pt(11), color: DARK, font: "Calibri", ...o })],
  spacing: { before: 80, after: 80, line: 276 }
});
const h1 = (t) => new Paragraph({
  heading: HeadingLevel.HEADING_1, spacing: { before: 360, after: 160 },
  children: [new TextRun({ text: t, size: pt(16), bold: true, color: BLUE, font: "Calibri" })]
});
const h2 = (t) => new Paragraph({
  spacing: { before: 280, after: 120 },
  children: [new TextRun({ text: t, size: pt(13), bold: true, color: BLUE, font: "Calibri" })]
});
const h3 = (t) => new Paragraph({
  spacing: { before: 200, after: 80 },
  children: [new TextRun({ text: t, size: pt(12), bold: true, color: DARK, font: "Calibri" })]
});
const bullet = (t) => new Paragraph({
  bullet: {}, spacing: { before: 60, after: 60 },
  children: [new TextRun({ text: t, size: pt(11), color: DARK, font: "Calibri" })]
});
const pb = () => new Paragraph({ children: [new PageBreak()] });
const sp = () => new Paragraph({ spacing: { before: 120, after: 120 } });

function box(title, lines, type = "info") {
  const bg = type === "alert" ? RED_BG : type === "warn" ? ORG_BG : LIGHT;
  const bd = type === "alert" ? RED : type === "warn" ? ORG : BLUE;
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [new TableRow({ children: [new TableCell({
      shading: { fill: bg },
      borders: { left: { style: BorderStyle.SINGLE, size: 16, color: bd } },
      margins: { top: 100, bottom: 100, left: 160, right: 160 },
      children: [
        new Paragraph({ children: [new TextRun({ text: title.toUpperCase(), bold: true, size: pt(10), color: bd })] }),
        ...lines.map(l => new Paragraph({
          indent: { left: 120 }, spacing: { before: 40, after: 40 },
          children: [new TextRun({ text: `• ${l}`, size: pt(10), color: DARK })]
        }))
      ]
    })] })]
  });
}

function tbl(headers, rows, widths) {
  const tot = widths.reduce((a, b) => a + b, 0);
  const mk = (c, hdr, alt) => {
    const txt = typeof c === "string" ? c : c.text;
    const bold = hdr || (typeof c === "object" && c.bold);
    const col = hdr ? WHITE : ((typeof c === "object" && c.color) || DARK);
    return new TableCell({
      shading: { fill: hdr ? HDR : (alt ? ALT : WHITE) },
      margins: { top: 80, bottom: 80, left: 140, right: 140 },
      verticalAlign: VerticalAlign.CENTER,
      children: [new Paragraph({ children: [new TextRun({ text: txt, size: pt(10), bold, color: col, font: "Calibri" })] })]
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

const cover = [
  new Paragraph({ spacing: { before: pt(55) } }),
  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: "AFRICAN UNION COUNTER-TERRORISM CENTRE", size: pt(14), color: GRAY, font: "Calibri" })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: "AUCTC — CAERT Information System", size: pt(18), bold: true, color: BLUE, font: "Calibri" })] }),
  sp(),
  new Paragraph({ alignment: AlignmentType.CENTER, shading: { fill: BLUE }, spacing: { before: 200, after: 200 },
    children: [new TextRun({ text: "DATABASE MODERNIZATION PROJECT", size: pt(20), bold: true, color: WHITE, font: "Calibri" })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 200 },
    children: [new TextRun({ text: "Inception & Technical Audit Report", size: pt(16), color: BLUE, font: "Calibri" })] }),
  new Paragraph({ alignment: AlignmentType.CENTER,
    children: [new TextRun({ text: "Phase: Analysis & Documentation Only — No Implementation", size: pt(11), italics: true, color: GRAY, font: "Calibri" })] }),
  sp(), sp(),
  tbl([], [
    ["Document ID", "AUCTC-CAERT-INCEPT-2026-003"],
    ["Version", "3.0 — Full 15-Section Enterprise Edition"],
    ["Date", "26 May 2026"],
    ["Classification", "Sensitive — Internal Use Only"],
    ["Production baseline audited", "PHP 7.x • Symfony 5.4 • MySQL 8"],
    ["Repository state (observed)", "Symfony 7.4 / PHP 8.2+ dependencies (upgrade in progress)"],
  ], [38, 62]),
  pb()
];

const toc = [
  h1("Table of Contents"),
  ...[
    "1. Executive Summary", "2. Current System Overview", "3. Architecture Assessment",
    "4. Database Assessment", "5. Data Flow Assessment", "6. Security Assessment",
    "7. Analytics & GIS Assessment", "8. Infrastructure Assessment",
    "9. UX/UI & Operational Workflow Assessment", "10. Technical Debt & Risks",
    "11. Bottlenecks & Scalability Issues", "12. Recommendations",
    "13. Proposed Work Plan", "14. Priority Matrix", "15. Risk Matrix",
    "Appendix A — Evidence & Audit Limitations"
  ].map((t, i) => body(`${i < 15 ? i + 1 : ""}${i < 15 ? ". " : ""}${t.replace(/^\d+\.\s*/, "")}`)),
  pb()
];

const s1 = [
  h1("1. Executive Summary"),
  body("The CAERT platform is the African Union Counter-Terrorism Centre's operational system for recording, validating, and analysing terrorism-related incidents across member states. This inception report documents the outcome of a full technical audit conducted through static analysis of the application codebase and configuration. No code, database, or infrastructure changes were made."),
  h2("1.1 Audit Scope"),
  bullet("Architecture, database, APIs, ETL, analytics, GIS, infrastructure, security, scalability, maintainability, workflows, and UX"),
  bullet("Production technology baseline: PHP 7.x and Symfony 5.4 (end-of-life, unpatched security posture)"),
  bullet("Repository under review may reflect an in-progress upgrade (Symfony 7.4, PHP ≥ 8.2 in composer.json) — production must be verified independently"),
  h2("1.2 Overall Verdict"),
  box("Strategic conclusion", [
    "CAERT delivers essential operational value but is not sustainable on its current legacy platform.",
    "Critical security exposures exist at both platform (EOL PHP/Symfony) and application layers (public analytics endpoints, no audit trail).",
    "Data integrity defects affect official statistics (import dates, analytics date dimension).",
    "GIS capability is absent; analyst and decision-maker workflows require modernization.",
  ], "alert"),
  h2("1.3 Maturity Snapshot"),
  tbl(["Domain", "Score", "Risk"], [
    ["Platform (PHP 7 / Symfony 5.4)", { text: "1 / 5", color: RED, bold: true }, { text: "Critical", color: RED }],
    ["Security & auditability", { text: "1.5 / 5", color: RED }, { text: "Critical", color: RED }],
    ["Data integrity", { text: "2 / 5", color: ORG }, { text: "High", color: ORG }],
    ["Database governance", { text: "2 / 5", color: ORG }, { text: "High", color: ORG }],
    ["Analytics & reporting", { text: "2 / 5", color: ORG }, { text: "Medium–High", color: ORG }],
    ["GIS", { text: "0 / 5", color: RED }, { text: "High (capability gap)", color: ORG }],
    ["UX & workflows", { text: "2.5 / 5", color: ORG }, { text: "Medium", color: ORG }],
    ["Infrastructure / DevOps", { text: "1 / 5", color: RED }, { text: "High", color: RED }],
  ], [40, 18, 42]),
  h2("1.4 Top Five Findings Requiring Executive Attention"),
  tbl(["#", "Finding", "Severity"], [
    ["1", "PHP 7 and Symfony 5.4 no longer receive security patches", { text: "Critical", color: RED, bold: true }],
    ["2", "/search_for_graphique exposes aggregated terrorism statistics without login", { text: "Critical", color: RED, bold: true }],
    ["3", "No immutable audit log for CRUD, publish, import, or authentication events", { text: "Critical", color: RED, bold: true }],
    ["4", "Excel import ignores attack date column; analytics filter on wrong date field", { text: "High", color: ORG, bold: true }],
    ["5", "No GIS — locality is free text only; spatial analysis impossible", { text: "High", color: ORG, bold: true }],
  ], [8, 72, 20]),
  h2("1.5 Recommended Programme Direction"),
  bullet("Phase 0 (immediate): Close public endpoints, implement audit logging, fix import/analytics dates, CSRF and delete-method hardening"),
  bullet("Phase 1 (0–6 months): Migrate to PHP 8.2+ and Symfony 6.4 LTS or 7.x; complete schema-as-code; automated tests"),
  bullet("Phase 2 (6–12 months): Governed BI, API layer, workflow UX, controlled export"),
  bullet("Phase 3 (12–24 months): GIS module, optional data warehouse"),
  pb()
];

const s2 = [
  h1("2. Current System Overview"),
  h2("2.1 Business Purpose"),
  body("CAERT supports institutional counter-terrorism information management: incident capture (casualties by actor category, attack classification, perpetrator groups, geography), editorial publication workflow, and limited analytics for analysts, technical staff, administrators, and coordination teams."),
  h2("2.2 Technology Stack"),
  tbl(["Layer", "Production baseline", "Repository (observed)", "Notes"], [
    ["Runtime", { text: "PHP 7.x (EOL Nov 2022)", color: RED }, "PHP ≥ 8.2", "Upgrade path required for production"],
    ["Framework", { text: "Symfony 5.4 (EOL Nov 2024)", color: RED }, "Symfony 7.4.*", "Confirm deployment vs. dev branch"],
    ["ORM", "Doctrine 2.x (typical)", "Doctrine ORM 3.3", "Major version jump with PHP 8"],
    ["Database", "MySQL 8.0", "MySQL 8.0", "Retain; add indexes and constraints"],
    ["UI", "Twig, Bootstrap 4, jQuery", "Same + Webpack Encore", "Legacy vendor assets coexist"],
    ["Tables", "Omines DataTables", "Omines DataTables 0.10", "Server-side ORM adapter"],
    ["Charts", "Chart.js", "Chart.js 3", "Three KPI charts + trend search"],
    ["Import", "PhpSpreadsheet", "PhpSpreadsheet 2/3", "Excel .xlsx bulk load"],
    ["External BI", "Tableau Public link", "Unchanged in templates", "Data sovereignty risk"],
  ], [22, 28, 28, 22]),
  h2("2.3 Application Structure"),
  body("Monolithic Symfony MVC: 5 controllers, 13 entities, 13 repositories, custom LoginFormAuthenticator, Omines DataTable types, Twig templates, assets via Webpack Encore. No REST API, no message bus, no custom voters, no console commands for ETL."),
  h2("2.4 User Roles & Access Model"),
  tbl(["Role", "Persona", "Capabilities"], [
    ["ROLE_USER", "Country focal point", "Create/edit incidents; country-scoped dashboard (DataTable filter)"],
    ["ROLE_ADMIN", "CAERT reviewer", "Publish/reject; view all countries"],
    ["ROLE_SUPER_ADMIN", "System administrator", "Users, reference data (attack types, targets, groups, etc.)"],
  ], [22, 28, 50]),
  body("User.profil labels (Point focal Pays, Staff_Caert, Administrateur) exist but are not enforced via Symfony voters — authorization relies on JSON roles and query filters only.", { italics: true }),
  h2("2.5 Core Data Entities"),
  body("Central fact: AllData (incident). Reference: Region, Pays, Attaque, Cible, MoyenAttaque, MaterielAttaque, Materiaux, Perpetrateurs, Espace. Users, AppParam (branding/install), Roles (unused in security layer)."),
  pb()
];

const s3 = [
  h1("3. Architecture Assessment"),
  h2("3.1 Pattern"),
  body("Layered monolith: HTTP Controllers → Symfony Forms / DataTables → Repositories → Doctrine Entities → MySQL. Synchronous request/response; session-based authentication; server-rendered HTML with AJAX for charts and DataTable pagination."),
  h3("Architecture diagram (logical)"),
  new Paragraph({ alignment: AlignmentType.CENTER, shading: { fill: LIGHT },
    children: [new TextRun({ text: "Browser → Symfony Firewall → Controllers → Services/Repos → Doctrine → MySQL\n                              ↘ public/uploads (Excel)    ↘ Mailer (SMTP)\nExternal: Tableau Public (uncontrolled)", font: "Consolas", size: pt(9), color: BLUE })] }),
  h2("3.2 Strengths"),
  bullet("Clear domain model aligned to incident reporting and publication governance"),
  bullet("Server-side DataTables reduce full-dataset client load"),
  bullet("Country isolation for non-admin users in AllDataDataTableType query builder"),
  bullet("Modern Symfony upgrade path is feasible once PHP 8 is in place"),
  h2("3.3 Weaknesses"),
  tbl(["Issue", "Impact"], [
    ["EOL platform (PHP 7 / Symfony 5.4)", "Structural CVE exposure; blocks modern security features"],
    ["Fat controllers (import logic in HomeController)", "Untestable; high regression risk on migration"],
    ["No domain service layer", "Duplicated business rules (totals, publication state)"],
    ["No API / integration layer", "Cannot support mobile, inter-agency feeds, or ETL pipelines"],
    ["No event sourcing or audit subsystem", "Institutional non-compliance for sensitive data"],
    ["Single incomplete migration file", "Schema drift between environments"],
    ["Dual login config (form_login + custom authenticator)", "Authentication edge-case risk"],
  ], [45, 55]),
  h2("3.4 Architecture Maturity"),
  tbl(["Dimension", "Score", "Comment"], [
    ["Modularity", "2/5", "Monolith OK at current scale; needs boundaries before growth"],
    ["Scalability", "2/5", "Vertical only; findAll() on dashboard; no caching"],
    ["Integrability", "1/5", "No formal API contracts"],
    ["Observability", "2/5", "Monolog present; no security audit domain"],
    ["Evolvability", "3/5", "Symfony foundation sound post-upgrade"],
  ], [30, 15, 55]),
  pb()
];

const s4 = [
  h1("4. Database Assessment"),
  h2("4.1 Schema Overview"),
  body("Star-like schema around all_data with FKs to classification tables and pays → region hierarchy. Stored derived fields: totalDeces, totalBlesses, totalDisparus. Publication: isPublished (boolean, tri-state in practice), objetRejet. Timestamps: dateAttaque, createdAt. Geography: localite (varchar) — no lat/lon."),
  h2("4.2 Schema-as-Code Gap"),
  body("Only migration Version20260519160612 exists: creates users table and adds FK constraints to pre-existing tables. Full baseline schema is not version-controlled — greenfield deployments depend on undocumented SQL dumps."),
  h2("4.3 Data Integrity Issues"),
  tbl(["ID", "Issue", "Severity", "Evidence"], [
    ["DI-01", "Import sets dateAttaque to now(), ignores Excel column A", { text: "Critical", color: RED }, "HomeController::dataInport L127-129"],
    ["DI-02", "newData() forces totalDisparus = 0", { text: "High", color: ORG }, "HomeController::newData L160"],
    ["DI-03", "updateData() sets isPublished to empty string", { text: "High", color: ORG }, "HomeController::updateData L199"],
    ["DI-04", "Analytics trend uses createdAt not dateAttaque", { text: "High", color: ORG }, "AllDataRepository::getCountTotalForSearch"],
    ["DI-05", "Silent null FK on import if libelle not found", { text: "High", color: ORG }, "findOneBy libelle in import loop"],
    ["DI-06", "Redundant stored totals vs. component fields", { text: "Medium", color: GRAY }, "AllData entity"],
    ["DI-07", "KPI 'attack' count sums totalDisparus", { text: "High", color: ORG }, "getCountTotalAttackInjuredDeath"],
  ], [10, 38, 14, 38]),
  h2("4.4 Recommended Indexes (design phase)"),
  tbl(["Table", "Columns", "Rationale"], [
    ["all_data", "is_published, date_attaque", "Dashboard and analytics filters"],
    ["all_data", "pays_id, created_at", "Country scope and trend windows"],
    ["all_data", "user_id", "Attribution reporting"],
    ["lookup tables", "libelle", "Import resolution (partially unique already)"],
  ], [25, 30, 45]),
  h3("Database maturity: 2 / 5"),
  pb()
];

const s5 = [
  h1("5. Data Flow Assessment"),
  h2("5.1 Operational Flows"),
  tbl(["Flow", "Entry", "Processing", "Output"], [
    ["A — Manual capture", "Form /new/data", "Validate → compute totals → persist (unpublished)", "Pending record"],
    ["B — Excel import", "POST /inport/data", "PhpSpreadsheet row loop → persist all → flush", "Batch inserts, no rollback"],
    ["C — Publication", "publish_or_not", "Set isPublished or objetRejet", "Published / rejected"],
    ["D — Analytics", "GraphiqueController JSON", "SUM on isPublished=1", "Charts / trend comparison"],
  ], [18, 22, 40, 20]),
  h3("Publication state machine"),
  new Paragraph({ alignment: AlignmentType.CENTER, shading: { fill: LIGHT },
    children: [new TextRun({ text: "[Capture/Import] → Pending → Published (analytics eligible)\n                      ↘ Rejected (objetRejet set)", font: "Consolas", size: pt(10) })] }),
  h2("5.2 ETL / Ingestion"),
  tbl(["Capability", "Status"], [
    ["Scheduled ETL / cron", { text: "Absent", color: RED }],
    ["API ingestion", { text: "Absent", color: RED }],
    ["Excel import", { text: "Present — no CSRF, no validation report, no deduplication", color: ORG }],
    ["Server-side export", { text: "Absent", color: RED }],
    ["Data quality dashboard", { text: "Absent", color: RED }],
  ], [40, 60]),
  h2("5.3 Data Lineage Defect"),
  body("Operational attack date (dateAttaque) is the correct dimension for terrorism trend analysis. Current trend search filters on createdAt (submission time), producing systematically wrong results when reporting lags incidents."),
  pb()
];

const s6 = [
  h1("6. Security Assessment"),
  h2("6.1 Platform Security (PHP 7 / Symfony 5.4)"),
  box("Platform risk", [
    "PHP 7.4 EOL: no security fixes since November 2022.",
    "Symfony 5.4 EOL: no free security support since November 2024.",
    "Symfony 6+ requires PHP 8.1+ — migration is mandatory for sustained patching.",
    "Cumulative CVE exposure across runtime, framework, and Composer dependencies.",
  ], "alert"),
  h2("6.2 Application Security Findings"),
  tbl(["ID", "Finding", "Severity", "Remediation"], [
    ["SEC-01", "/search_for_graphique — PUBLIC_ACCESS", { text: "Critical", color: RED }, "Require ROLE_USER + role checks"],
    ["SEC-02", "/objet_rejet_msg/{id} — PUBLIC_ACCESS", { text: "High", color: RED }, "Require authentication"],
    ["SEC-03", "No security audit log", { text: "Critical", color: RED }, "Immutable audit table + events"],
    ["SEC-04", "Excel import without CSRF", { text: "High", color: ORG }, "CSRF token on form"],
    ["SEC-05", "delete_all_data accepts GET", { text: "High", color: ORG }, "POST only + confirmation"],
    ["SEC-06", "Uploads in public/uploads/", { text: "Medium", color: ORG }, "Move outside web root"],
    ["SEC-07", "Public Tableau for all data", { text: "High", color: ORG }, "Internal governed BI"],
    ["SEC-08", "Dual form_login + custom authenticator", { text: "Medium", color: GRAY }, "Consolidate auth config"],
    ["SEC-09", "Remember-me 7 days on sensitive system", { text: "Medium", color: GRAY }, "Shorten / require re-auth"],
    ["SEC-10", "User.enable not verified in authenticator", { text: "Medium", color: GRAY }, "Block disabled accounts at login"],
  ], [10, 38, 14, 38]),
  h2("6.3 Positive Controls"),
  bullet("Password hasher: Symfony auto (bcrypt/argon as available)"),
  bullet("CSRF on login form (LoginFormAuthenticator)"),
  bullet("Role hierarchy: SUPER_ADMIN → ADMIN → USER"),
  bullet("IsGranted on super-admin parameter routes"),
  bullet("Country filter for non-ROLE_ADMIN in DataTable"),
  h3("Security maturity: 1.5 / 5 (platform EOL compounds application gaps)"),
  pb()
];

const s7 = [
  h1("7. Analytics & GIS Assessment"),
  h2("7.1 Analytics (current state)"),
  tbl(["Feature", "Implementation", "Limitation"], [
    ["Dashboard grid", "Omines DataTable ~30 columns", "High cognitive load; findAll() on index"],
    ["KPI charts", "Chart.js — deaths, injuries, targets", "Mislabelled attack KPI; static aggregates"],
    ["Trend comparison", "POST search_for_graphique", "Public endpoint; wrong date field; type mapping bug"],
    ["Executive view", "None", "No situational summary"],
    ["Export", "Client DataTables buttons only", "No logged institutional export"],
    ["External BI", "Tableau Public URL", "Uncontrolled data exposure"],
  ], [22, 38, 40]),
  h2("7.2 GIS Assessment"),
  tbl(["Capability", "Status"], [
    ["Map visualization", { text: "Not implemented", color: RED }],
    ["Coordinates / GeoJSON in schema", { text: "Not implemented", color: RED }],
    ["Geocoding pipeline", { text: "Not implemented", color: RED }],
    ["Spatial queries (hotspots, corridors)", { text: "Not implemented", color: RED }],
    ["Map libraries (Leaflet, Mapbox, etc.)", { text: "Not in dependencies", color: RED }],
  ], [45, 55]),
  body("GIS is a greenfield workstream: geospatial schema, gazetteer, map UI (Leaflet/MapLibre), geocoding ETL, and policy for coordinate precision/classification."),
  h3("Analytics maturity: 2/5 | GIS maturity: 0/5"),
  pb()
];

const s8 = [
  h1("8. Infrastructure Assessment"),
  tbl(["Area", "Finding", "Risk"], [
    ["Containerization", "No Dockerfile / Compose in project", { text: "High", color: RED }],
    ["CI/CD", "No pipeline in repository", { text: "High", color: RED }],
    ["IaC", "None documented", { text: "High", color: RED }],
    ["Web server", "Apache (symfony/apache-pack), public/.htaccess", { text: "Low", color: GRAY }],
    ["Asset build", "Webpack Encore; built assets in public/build/", { text: "Medium", color: ORG }],
    ["Environment", ".env present; no .env.dist template", { text: "Medium", color: ORG }],
    ["Backups / DR", "Not documented in repo", { text: "High", color: RED }],
    ["Monitoring", "Not documented", { text: "High", color: RED }],
    ["Patch management", "EOL stack — no sustainable patch cadence", { text: "Critical", color: RED }],
  ], [28, 44, 28]),
  body("Deployment model (inferred): manual composer install, npm build, doctrine migrations, Apache vhost to public/. No runbook or rollback procedure in repository."),
  h3("Infrastructure maturity: 1 / 5"),
  pb()
];

const s9 = [
  h1("9. UX/UI & Operational Workflow Assessment"),
  h2("9.1 Navigation"),
  body("Sidebar: Dashboard (incident grid), Data Visualization (charts + Tableau), Users/Parameters (super-admin). Single landing page for all roles — no role-based home."),
  h2("9.2 Analyst Workflow Gaps"),
  tbl(["Gap", "Impact on productivity"], [
    ["No publication work queue", "Reviewers cannot see 'awaiting validation' items efficiently"],
    ["No saved filters / column presets", "Analysts rebuild complex views each session"],
    ["~30-column table without prioritization", "Excessive scrolling; critical fields buried"],
    ["No full-text global search", "Must know exact filter values"],
    ["Mixed FR UI / EN chart labels", "Inconsistent institutional presentation"],
    ["Import modal lacks template + error report", "Failed bulk loads give no row-level feedback"],
    ["No executive KPI snapshot", "Decision-makers depend on manual export or Tableau"],
  ], [40, 60]),
  h2("9.3 Publication Workflow"),
  body("States: Pending (badge warning) → Published (green, analytics included) or Rejected (red + objetRejet). Functional but not optimized as a task-oriented workflow."),
  h3("UX maturity: 2.5 / 5"),
  pb()
];

const s10 = [
  h1("10. Technical Debt & Risks"),
  h2("10.1 Technical Debt Register"),
  tbl(["ID", "Item", "Effort", "Risk"], [
    ["TD-00", { text: "EOL platform PHP 7 + Symfony 5.4", color: RED, bold: true }, "Large", { text: "Critical", color: RED }],
    ["TD-01", "Incomplete Doctrine migrations", "Large", { text: "High", color: RED }],
    ["TD-02", "Business logic in controllers", "Medium", { text: "Medium", color: ORG }],
    ["TD-03", "Zero automated tests", "Large", { text: "High", color: RED }],
    ["TD-04", "Legacy public/vendor assets", "Medium", { text: "Medium", color: ORG }],
    ["TD-05", "Route typo /inport/data", "Small", { text: "Low", color: GRAY }],
    ["TD-06", "Unused Roles entity vs Symfony roles", "Small", { text: "Medium", color: ORG }],
    ["TD-07", "Entity typo materieaux", "Small", { text: "Medium", color: ORG }],
    ["TD-08", "Mislabelled attack KPI query", "Small", { text: "High", color: ORG }],
    ["TD-09", "Dual authenticator configuration", "Small", { text: "Medium", color: ORG }],
    ["TD-10", "No API versioning strategy", "Large", { text: "High", color: RED }],
  ], [10, 42, 18, 30]),
  h2("10.2 Consolidated Risk Summary"),
  body("See Section 15 (Risk Matrix) for likelihood × impact mapping. Extreme risks: R-00 (EOL platform), R-01 (public analytics), R-05 (no audit trail)."),
  pb()
];

const s11 = [
  h1("11. Bottlenecks & Scalability Issues"),
  tbl(["Bottleneck", "Symptom", "Trigger estimate"], [
    ["findAll(AllData) on dashboard", "Memory and slow TTFB", "~10,000+ records"],
    ["Synchronous Excel import single flush", "HTTP timeout", "500+ rows"],
    ["DataTable 8-way LEFT JOIN", "Slow AJAX under concurrency", "Multiple simultaneous analysts"],
    ["No aggregate query caching", "Repeated SUM cost per chart load", "Active usage"],
    ["Session-bound monolith", "Sticky sessions for horizontal scale", "Multi-server deployment"],
    ["Public upload directory", "Disk I/O and attack surface", "Bulk import campaigns"],
  ], [32, 38, 30]),
  body("Comfortable ceiling without architectural change: low tens of thousands of incidents. Beyond that: indexes, remove findAll(), read replica, archival strategy, and optional caching layer."),
  pb()
];

const s12 = [
  h1("12. Recommendations"),
  h2("12.1 Priority 0 — Immediate (weeks 1–4)"),
  bullet("Confirm production versions (php -v, composer show symfony/*)"),
  bullet("Remove PUBLIC_ACCESS from /search_for_graphique and /objet_rejet_msg"),
  bullet("Implement immutable audit log (login, logout, CRUD, publish, import, delete)"),
  bullet("Fix Excel import dateAttaque from column A; fix analytics to use dateAttaque"),
  bullet("CSRF on import; POST-only deletes; secure upload directory"),
  h2("12.2 Priority 1 — Short term (months 2–6)"),
  bullet("Migrate production to PHP 8.2+ then Symfony 6.4 LTS or 7.x with full regression testing"),
  bullet("Complete schema migrations baseline; deploy indexes; normalize isPublished"),
  bullet("Introduce PHPUnit suite; CI with composer audit and static analysis"),
  bullet("Retire or replace public Tableau with governed internal BI"),
  h2("12.3 Priority 2 — Medium term (months 6–12)"),
  bullet("REST API (versioned, institutional IAM)"),
  bullet("Domain services extracted from controllers"),
  bullet("Role-based landing pages, publication inbox, controlled export"),
  bullet("Docker + documented backup/restore drills"),
  h2("12.4 Priority 3 — Long term (months 12–24)"),
  bullet("GIS module: coordinates, gazetteer, map UI, spatial analytics"),
  bullet("Optional data warehouse / read replica for heavy reporting"),
  pb()
];

const s13 = [
  h1("13. Proposed Work Plan"),
  tbl(["Phase", "Duration", "Deliverables", "Dependencies"], [
    ["0 — Inception", "2–3 wks", "This report approved; production stack confirmed", "—"],
    ["1 — Discovery", "4–6 wks", "Threat model, target ADR, migration readiness, data profiling", "Phase 0 sign-off"],
    ["2 — Secure & platform", "6–10 wks", "PHP 8 + supported Symfony in prod; P0 security fixes", "Phase 1"],
    ["3 — Database", "8–12 wks", "Full migrations, constraints, cleansing, indexes", "Phase 2"],
    ["4 — API & quality", "8–10 wks", "REST API, tests ≥70% core domain, CI/CD", "Phase 3"],
    ["5 — Analytics & UX", "6–8 wks", "Dashboards, workflows, export", "Phase 4"],
    ["6 — GIS", "10–14 wks", "Map, geocoding, spatial queries", "Phase 4"],
    ["7 — DevOps sustain", "Ongoing", "Monitoring, runbooks, training", "Parallel from Phase 2"],
  ], [14, 12, 44, 30]),
  body("Phases 2 and 7 run in parallel where possible. No big-bang cutover — maintain operational continuity throughout."),
  pb()
];

const s14 = [
  h1("14. Priority Matrix"),
  body("Impact vs. effort guide for programme sequencing:"),
  tbl(["Priority", "Initiative", "Impact", "Effort"], [
    [{ text: "P0", bold: true, color: RED }, "Migrate off PHP 7 + Symfony 5.4", "Critical", "High"],
    [{ text: "P0", bold: true, color: RED }, "Close public analytics & rejection endpoints", "Critical", "Low"],
    [{ text: "P0", bold: true, color: RED }, "Audit log MVP", "Critical", "Medium"],
    [{ text: "P0", bold: true, color: RED }, "Fix import date + analytics date dimension", "High", "Low"],
    [{ text: "P1", bold: true, color: ORG }, "Schema migrations + indexes", "High", "Medium"],
    [{ text: "P1", bold: true, color: ORG }, "CI/CD + automated tests", "High", "Medium"],
    [{ text: "P1", bold: true, color: ORG }, "Govern Tableau / internal BI", "High", "Low–Medium"],
    [{ text: "P2", bold: true, color: BLUE }, "REST API + domain services", "High", "High"],
    [{ text: "P2", bold: true, color: BLUE }, "UX publication inbox + export", "Medium", "Medium"],
    [{ text: "P3", bold: true, color: GRAY }, "GIS module", "Strategic", "Very High"],
    [{ text: "P3", bold: true, color: GRAY }, "Data warehouse", "Strategic", "High"],
  ], [10, 48, 22, 20]),
  pb()
];

const s15 = [
  h1("15. Risk Matrix"),
  tbl(["Likelihood \\ Impact", "High impact", "Critical impact"], [
    [{ text: "High", bold: true }, "R-07 GIS gap; R-10 stale front-end deps", { text: "R-00 EOL platform\nR-01 public analytics\nR-05 no audit", color: RED, bold: true }],
    [{ text: "Medium", bold: true }, "R-02 import integrity; R-04 Tableau; R-06 performance; R-09 exfiltration", "R-08 failed big-bang upgrade"],
    [{ text: "Low", bold: true }, "—", "—"],
  ], [22, 39, 39]),
  h2("15.1 Risk Treatment"),
  tbl(["Rating", "Risks", "Treatment"], [
    [{ text: "Extreme", color: RED, bold: true }, "R-00, R-01, R-05", "Immediate design + deploy controls before feature work"],
    [{ text: "High", color: RED }, "R-02–R-04, R-06–R-09", "Phases 2–4 with acceptance criteria and sign-off"],
    [{ text: "Medium", color: ORG }, "R-10, R-11", "Schedule in technology refresh; monitor CVEs interim"],
  ], [18, 32, 50]),
  pb()
];

const appendix = [
  h1("Appendix A — Evidence & Audit Limitations"),
  h2("A.1 Code Evidence"),
  h3("Public analytics endpoint"),
  new Paragraph({ indent: { left: 360 }, shading: { fill: LIGHT },
    children: [new TextRun({ text: "config/packages/security.yaml:\n  - { path: ^/search_for_graphique, roles: PUBLIC_ACCESS }", font: "Consolas", size: pt(9) })] }),
  h3("Import date defect"),
  new Paragraph({ indent: { left: 360 }, shading: { fill: LIGHT },
    children: [new TextRun({ text: "HomeController.php: setDateAttaque(new DateTime()); // Excel column A parsed but not used", font: "Consolas", size: pt(9) })] }),
  h3("Analytics wrong date field"),
  new Paragraph({ indent: { left: 360 }, shading: { fill: LIGHT },
    children: [new TextRun({ text: "AllDataRepository.php: where('a.createdAt >= :date_start')", font: "Consolas", size: pt(9) })] }),
  h2("A.2 Audit Limitations"),
  bullet("Static analysis only — no penetration test, no production DB metrics, no server config review"),
  bullet("Production PHP/Symfony version must be confirmed operationally"),
  bullet("Stakeholder interviews and institutional policy documents not included"),
  sp(),
  new Paragraph({ alignment: AlignmentType.CENTER,
    children: [new TextRun({ text: "— End of Report — AUCTC CAERT Inception v3.0 — 26 May 2026", size: pt(10), color: GRAY, italics: true })] }),
];

const doc = new Document({
  creator: "AUCTC Modernization Programme",
  title: "AUCTC CAERT Inception Report v3.0",
  sections: [{
    properties: { page: { margin: { top: 1440, bottom: 1440, left: 1440, right: 1440 } } },
    headers: { default: new Header({ children: [new Paragraph({
      alignment: AlignmentType.RIGHT,
      border: { bottom: { color: BLUE, size: 6, style: BorderStyle.SINGLE } },
      children: [new TextRun({ text: "AUCTC — CAERT Inception Report v3.0 | SENSITIVE", size: pt(8), color: GRAY, italics: true })]
    })] }) },
    footers: { default: new Footer({ children: [new Paragraph({
      alignment: AlignmentType.CENTER,
      border: { top: { color: BLUE, size: 6, style: BorderStyle.SINGLE } },
      children: [
        new TextRun({ text: "Page ", size: pt(8), color: GRAY }),
        new TextRun({ children: [PageNumber.CURRENT], size: pt(8), color: GRAY }),
        new TextRun({ text: " of ", size: pt(8), color: GRAY }),
        new TextRun({ children: [PageNumber.TOTAL_PAGES], size: pt(8), color: GRAY }),
        new TextRun({ text: "  |  26 May 2026  |  Analysis only", size: pt(8), color: GRAY }),
      ]
    })] }) },
    children: [...cover, ...toc, ...s1, ...s2, ...s3, ...s4, ...s5, ...s6, ...s7, ...s8, ...s9, ...s10, ...s11, ...s12, ...s13, ...s14, ...s15, ...appendix]
  }]
});

const out = path.join(process.cwd(), "AUCTC_CAERT_Inception_Report_2026.docx");
Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync(out, buf);
  console.log("Written:", out);
});
