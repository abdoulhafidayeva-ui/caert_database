/** AUCTC CAERT — Technical Design Document v1.0 */
import {
  Document, Packer, Paragraph, TextRun, HeadingLevel, Table, TableRow,
  TableCell, WidthType, BorderStyle, AlignmentType, ShadingType,
  PageBreak, Header, Footer, PageNumber, VerticalAlign
} from "docx";
import fs from "fs";
import path from "path";

const BLUE = "00529B"; const DARK = "1A1A1A"; const GRAY = "555555";
const LIGHT = "F4F7FA"; const RED = "B71C1C"; const ORG = "E65100";
const GRN = "1B5E20"; const WHITE = "FFFFFF"; const HDR = "00529B"; const ALT = "E8F0F8";
const pt = (n) => n * 2;

const body = (t, o = {}) => new Paragraph({
  children: [new TextRun({ text: t, size: pt(11), color: DARK, font: "Calibri", ...o })],
  spacing: { before: 80, after: 80, line: 276 }
});
const h1 = (t) => new Paragraph({ heading: HeadingLevel.HEADING_1, spacing: { before: 360, after: 160 },
  children: [new TextRun({ text: t, size: pt(16), bold: true, color: BLUE, font: "Calibri" })] });
const h2 = (t) => new Paragraph({ spacing: { before: 280, after: 120 },
  children: [new TextRun({ text: t, size: pt(13), bold: true, color: BLUE, font: "Calibri" })] });
const h3 = (t) => new Paragraph({ spacing: { before: 200, after: 80 },
  children: [new TextRun({ text: t, size: pt(12), bold: true, color: DARK, font: "Calibri" })] });
const bullet = (t) => new Paragraph({ bullet: {}, spacing: { before: 60, after: 60 },
  children: [new TextRun({ text: t, size: pt(11), color: DARK, font: "Calibri" })] });
const pb = () => new Paragraph({ children: [new PageBreak()] });
const sp = () => new Paragraph({ spacing: { before: 120, after: 120 } });
const mono = (t) => new Paragraph({ shading: { fill: LIGHT }, indent: { left: 360 },
  children: [new TextRun({ text: t, font: "Consolas", size: pt(9), color: DARK })] });

function box(title, lines, type = "info") {
  const bg = type === "alert" ? "FFEBEE" : type === "ok" ? "E8F5E9" : LIGHT;
  const bd = type === "alert" ? RED : type === "ok" ? GRN : BLUE;
  return new Table({ width: { size: 100, type: WidthType.PERCENTAGE }, rows: [new TableRow({ children: [new TableCell({
    shading: { fill: bg }, borders: { left: { style: BorderStyle.SINGLE, size: 16, color: bd } },
    margins: { top: 100, bottom: 100, left: 160, right: 160 },
    children: [
      new Paragraph({ children: [new TextRun({ text: title.toUpperCase(), bold: true, size: pt(10), color: bd })] }),
      ...lines.map(l => new Paragraph({ indent: { left: 120 }, spacing: { before: 40, after: 40 },
        children: [new TextRun({ text: `• ${l}`, size: pt(10), color: DARK })] }))
    ]
  })] })] });
}

function tbl(headers, rows, widths) {
  const tot = widths.reduce((a, b) => a + b, 0);
  const mk = (c, hdr, alt) => {
    const txt = typeof c === "string" ? c : c.text;
    const bold = hdr || (typeof c === "object" && c.bold);
    const col = hdr ? WHITE : ((typeof c === "object" && c.color) || DARK);
    return new TableCell({ shading: { fill: hdr ? HDR : (alt ? ALT : WHITE) },
      margins: { top: 80, bottom: 80, left: 140, right: 140 }, verticalAlign: VerticalAlign.CENTER,
      children: [new Paragraph({ children: [new TextRun({ text: txt, size: pt(10), bold, color: col, font: "Calibri" })] })] });
  };
  return new Table({ width: { size: 100, type: WidthType.PERCENTAGE },
    columnWidths: widths.map(w => Math.round((w / tot) * 9360)),
    rows: [new TableRow({ tableHeader: true, children: headers.map(h => mk(h, true, false)) }),
      ...rows.map((r, i) => new TableRow({ children: r.map(c => mk(c, false, i % 2 === 1)) }))] });
}

const cover = [
  new Paragraph({ spacing: { before: pt(50) } }),
  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: "AFRICAN UNION COUNTER-TERRORISM CENTRE", size: pt(14), color: GRAY })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: "AUCTC — CAERT Platform Modernization", size: pt(18), bold: true, color: BLUE })] }),
  sp(),
  new Paragraph({ alignment: AlignmentType.CENTER, shading: { fill: BLUE }, spacing: { before: 200, after: 200 },
    children: [new TextRun({ text: "TECHNICAL DESIGN DOCUMENT", size: pt(20), bold: true, color: WHITE })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 160 },
    children: [new TextRun({ text: "Architecture & Future-State Design", size: pt(14), color: BLUE })] }),
  new Paragraph({ alignment: AlignmentType.CENTER,
    children: [new TextRun({ text: "Phase: Technical Design — No Full Implementation", size: pt(11), italics: true, color: GRAY })] }),
  sp(), sp(),
  tbl([], [
    ["Document ID", "AUCTC-CAERT-TDD-2026-001"],
    ["Version", "1.0"],
    ["Date", "26 May 2026"],
    ["Classification", "Sensitive — Internal Use Only"],
    ["Prerequisite", "Inception Report AUCTC-CAERT-INCEPT-2026-003"],
    ["Target platform", "PHP 8.2+ • Symfony 7.x • PostgreSQL 16 + PostGIS 3"],
  ], [38, 62]),
  pb()
];

const toc = [
  h1("Table of Contents"),
  ...["Executive Summary","Future-State Architecture","Database Schema Design","ETL Architecture",
    "Analytics Architecture","GIS Architecture","Security Architecture","Infrastructure Architecture",
    "UX/UI Redesign Strategy","Deployment Strategy","Migration Strategy","Testing Strategy",
    "Scalability Strategy","Sustainability Strategy","Appendix — ADRs"].map((t,i) => body(`${i+1}. ${t}`)),
  pb()
];

const s1 = [
  h1("1. Executive Summary"),
  body("This Technical Design Document (TDD) defines the future-state enterprise architecture for the CAERT platform following the Inception & Audit phase. It translates audit findings into an actionable target design covering database, ETL, analytics, GIS, security, infrastructure, and UX."),
  h2("1.1 Design Objectives"),
  bullet("Replace EOL stack (PHP 7 / Symfony 5.4) with supported, patchable platform"),
  bullet("Establish data integrity, auditability, and institutional compliance"),
  bullet("Enable spatial analysis (GIS) and governed analytics for analysts and decision-makers"),
  bullet("Modernize operational workflows without disruptive service interruption"),
  h2("1.2 Key Architectural Decisions"),
  tbl(["Decision", "Choice", "Rationale"], [
    ["Primary database", "PostgreSQL 16 + PostGIS 3", "Native spatial types; ACID; mature GIS; read replicas"],
    ["Application pattern", "Modular monolith → API-first Symfony 7", "Continuity with existing team skills; lower ops complexity than microservices"],
    ["Analytics layer", "Read models + materialized views + Superset (self-hosted)", "Governed BI; no public Tableau"],
    ["GIS frontend", "MapLibre GL JS + institutional tile server", "Open source; offline-capable; no vendor lock-in"],
    ["Async processing", "Symfony Messenger + Redis/RabbitMQ", "Reliable ETL/import without HTTP timeouts"],
    ["AuthN/AuthZ", "Symfony Security + MFA + RBAC/ABAC voters", "Country-scoped access; object-level control"],
    ["Audit", "Append-only audit_log + domain events", "Immutable institutional traceability"],
  ], [28, 32, 40]),
  h2("1.3 Programme Phasing (Design → Build)"),
  tbl(["Wave", "Focus", "Duration"], [
    ["Wave 1", "Platform, security, audit, import fix, schema baseline", "3–4 months"],
    ["Wave 2", "API, workflow UX, analytics read models", "3–4 months"],
    ["Wave 3", "GIS module, advanced BI, export governance", "4–6 months"],
    ["Wave 4", "Optimization, DR drills, institutional handover", "Ongoing"],
  ], [15, 55, 30]),
  pb()
];

const s2 = [
  h1("2. Future-State Architecture"),
  h2("2.1 Logical Architecture"),
  mono("┌─────────────────────────────────────────────────────────────────┐\n│  Presentation Layer                                              │\n│  Web App (Twig/Stimulus) │ Analyst SPA modules │ Executive Dash  │\n└───────────────────────────────┬─────────────────────────────────┘\n                                │ HTTPS / JSON\n┌───────────────────────────────▼─────────────────────────────────┐\n│  Application Layer (Symfony 7 Modular Monolith)                  │\n│  ┌─────────────┐ ┌──────────────┐ ┌───────────┐ ┌─────────────┐ │\n│  │ Incident BC │ │ Workflow BC  │ │ GIS BC    │ │ Identity BC │ │\n│  └──────┬──────┘ └──────┬───────┘ └─────┬─────┘ └──────┬──────┘ │\n│         └────────────────┴─────────────┴──────────────┘         │\n│                    Domain Services + Events                      │\n│  REST API (/api/v1) │ OpenAPI │ Rate limiting │ Audit interceptor│\n└───────────────────────────────┬─────────────────────────────────┘\n                                │\n┌───────────────────────────────▼─────────────────────────────────┐\n│  Data Layer                                                      │\n│  PostgreSQL+PostGIS (OLTP) │ Read Replica │ Staging (ETL)        │\n│  Redis (cache/sessions)    │ Object Storage (imports/exports)    │\n└───────────────────────────────┬─────────────────────────────────┘\n                                │\n┌───────────────────────────────▼─────────────────────────────────┐\n│  Async / Integration                                             │\n│  Messenger Workers: Import │ Geocode │ Report │ Notification     │\n└─────────────────────────────────────────────────────────────────┘"),
  h2("2.2 Bounded Contexts"),
  tbl(["Context", "Responsibility", "Key aggregates"], [
    ["Incident Management", "CRUD incidents, casualties, classification", "Incident, CasualtySummary"],
    ["Publication Workflow", "Submit, review, publish, reject", "PublicationRequest, ReviewDecision"],
    ["Reference Data (MDM)", "Controlled vocabularies", "AttackType, TargetType, PerpetratorGroup"],
    ["Geography & GIS", "Admin boundaries, geocoding, spatial queries", "Locality, GeoPoint, AdminArea"],
    ["Analytics", "Aggregates, dashboards, exports", "IncidentFact (read model)"],
    ["Identity & Access", "Users, roles, country scope, MFA", "UserAccount, RoleAssignment"],
    ["Audit & Compliance", "Immutable event log", "AuditEntry"],
  ], [28, 38, 34]),
  h2("2.3 Integration Points"),
  bullet("REST API v1 for future mobile apps and inter-agency feeds (OAuth2 client credentials)"),
  bullet("Webhook notifications on publication events (optional Phase 2)"),
  bullet("Export to institutional document formats (PDF/XLSX) via async jobs"),
  pb()
];

const s3 = [
  h1("3. Database Schema Design"),
  h2("3.1 Design Principles"),
  bullet("Normalize reference data; incident facts in star schema friendly to analytics"),
  bullet("Computed casualty totals via DB generated columns or application service — single source of truth"),
  bullet("Soft-delete + row versioning for incidents (incident_version table)"),
  bullet("All timestamps UTC; date_attaque as canonical analytical date"),
  bullet("PostGIS geometry column (POINT, SRID 4326) with privacy tier metadata"),
  h2("3.2 Core Tables (optimized from legacy all_data)"),
  tbl(["Table", "Purpose", "Key columns"], [
    ["incident", "Primary fact (replaces all_data)", "id, uuid, date_attaque, details, status, country_id, locality_id, created_by, published_at"],
    ["incident_casualty", "Normalized casualties", "incident_id, category (enum), deaths, injured, missing"],
    ["incident_classification", "FKs to reference tables", "incident_id, attack_type_id, target_id, perpetrator_id, means_id, space_id"],
    ["locality", "Gazetteer entry", "id, name, country_id, admin_area_id, geom (POINT), precision_level"],
    ["admin_area", "Region/country/admin hierarchy", "id, type, name, parent_id, geom (MULTIPOLYGON)"],
    ["publication_review", "Workflow state", "incident_id, status, reviewer_id, rejection_reason, reviewed_at"],
    ["audit_log", "Append-only", "id, actor_id, action, entity, entity_id, old_json, new_json, ip, at"],
    ["import_batch", "ETL tracking", "id, filename, status, row_count, error_count, started_at"],
    ["import_batch_row", "Row-level validation", "batch_id, row_num, status, errors_json, incident_id"],
  ], [24, 32, 44]),
  h2("3.3 Reference Data (retain & rename legacy lookups)"),
  body("Legacy tables (attaque, cible, perpetrateurs, etc.) migrate to: ref_attack_type, ref_target_type, ref_perpetrator_group, ref_attack_means, ref_attack_material, ref_recovered_material, ref_terrain_space — all with code, label_en, label_fr, active, valid_from/to."),
  h2("3.4 Indexing Strategy"),
  tbl(["Index", "Columns", "Use case"], [
    ["idx_incident_analytics", "status, date_attaque", "Published incident queries"],
    ["idx_incident_country", "country_id, date_attaque DESC", "Country focal dashboard"],
    ["idx_incident_geom", "GIST(geom)", "Spatial queries"],
    ["idx_audit_entity", "entity, entity_id, at DESC", "Audit lookup"],
    ["idx_import_batch_status", "status, started_at", "ETL monitoring"],
  ], [28, 32, 40]),
  h2("3.5 Integrity Constraints"),
  bullet("CHECK: all casualty counts >= 0"),
  bullet("CHECK: date_attaque <= now() + interval '1 day' (configurable tolerance)"),
  bullet("ENUM incident_status: draft | pending_review | published | rejected | archived"),
  bullet("FK RESTRICT on reference data; CASCADE only on import staging"),
  pb()
];

const s4 = [
  h1("4. ETL Architecture"),
  h2("4.1 Pipeline Overview"),
  mono("Upload (.xlsx) → Validate (schema) → Stage (import_batch_row)\n    → Transform (map libelle→FK, parse dates) → Load (incident + casualties)\n    → Publish report (success/errors) → Archive file (object storage)"),
  h2("4.2 Components"),
  tbl(["Component", "Technology", "Role"], [
    ["Upload endpoint", "Symfony controller + Flysystem", "Secure upload outside web root"],
    ["Validation", "PhpSpreadsheet + Symfony Validator", "Column contract, types, required fields"],
    ["Staging", "PostgreSQL import_batch_* tables", "Idempotent re-runs; error isolation"],
    ["Worker", "Messenger handler ImportBatchHandler", "Async processing; no HTTP timeout"],
    ["Idempotency", "SHA-256 row hash per batch", "Prevent duplicate imports"],
    ["Scheduler", "Symfony Scheduler (cron)", "Nightly reconciliation, geocoding backlog"],
  ], [28, 32, 40]),
  h2("4.2 Excel Column Contract (corrected)"),
  tbl(["Col", "Field", "Validation"], [
    ["A", "date_attaque", "Required; parse Excel date → UTC"],
    ["C", "terrain_space", "FK lookup ref_terrain_space"],
    ["D", "country", "FK lookup admin_area (country)"],
    ["F", "locality_name", "Gazetteer match or create draft locality"],
    ["G–AD", "Classification + casualties", "FK lookups; non-negative integers"],
  ], [10, 30, 60]),
  h2("4.3 Error Handling"),
  bullet("Partial success: valid rows committed; invalid rows in import_batch_row with errors_json"),
  bullet("User receives downloadable error report (XLSX with row numbers and messages)"),
  bullet("No silent null FK — fail row if reference not found (or queue for MDM review)"),
  pb()
];

const s5 = [
  h1("5. Analytics Architecture"),
  h2("5.1 Layered Analytics Model"),
  tbl(["Layer", "Description", "Technology"], [
    ["Operational", "Real-time dashboards in app", "Symfony + Chart.js / custom widgets"],
    ["Semantic", "Defined KPIs and dimensions", "SQL views + documentation"],
    ["Aggregate", "Pre-computed rollups", "Materialized views (refresh hourly)"],
    ["Exploration", "Ad-hoc analysis for senior analysts", "Apache Superset (self-hosted)"],
    ["Executive", "High-level situational brief", "Dedicated executive dashboard route"],
  ], [22, 43, 35]),
  h2("5.2 Canonical KPIs (corrected definitions)"),
  tbl(["KPI", "Definition", "Date dimension"], [
    ["Total incidents", "COUNT published incidents", "date_attaque"],
    ["Total deaths", "SUM deaths all categories", "date_attaque"],
    ["Deaths by category", "SUM by casualty category", "date_attaque"],
    ["Incidents by region", "COUNT GROUP BY region", "date_attaque"],
    ["Trend MoM", "Compare periods on date_attaque", "date_attaque"],
  ], [28, 42, 30]),
  h2("5.3 Materialized Views (examples)"),
  mono("mv_incidents_monthly_region — GROUP BY year, month, region_id\nmv_casualties_by_category — JOIN incident_casualty, filter published\nmv_perpetrator_activity — GROUP BY perpetrator_id, month"),
  h2("5.4 Export Governance"),
  bullet("All exports require authentication + permission EXPORT_INCIDENTS"),
  bullet("Watermark: user, timestamp, classification label"),
  bullet("Audit log entry for every export"),
  bullet("No public BI links — Superset behind SSO"),
  pb()
];

const s6 = [
  h1("6. GIS Architecture"),
  h2("6.1 Spatial Data Model"),
  bullet("admin_area: MULTIPOLYGON for regions, countries, provinces (Natural Earth / AU official boundaries)"),
  bullet("locality: POINT with precision_level enum: exact | approximate | admin_centroid | country_centroid"),
  bullet("incident.geom: denormalized POINT copied from locality at publish time (or override with analyst pin)"),
  bullet("classification: coordinates above admin_centroid require ROLE_GIS_SENSITIVE"),
  h2("6.2 Geocoding Pipeline"),
  mono("locality text + country → GeocodeService (Nominatim self-hosted or commercial)\n    → validate within country bbox → store POINT + precision_level\n    → manual analyst correction via map UI"),
  h2("6.3 Map UI Architecture"),
  tbl(["Component", "Choice", "Notes"], [
    ["Map engine", "MapLibre GL JS 4", "WebGL; vector tiles"],
    ["Tile server", "Martin or pg_tileserv", "Serves PostGIS layers"],
    ["Base map", "Self-hosted OSM tiles or MapTiler (institutional license)", "No dependency on public CDNs in prod"],
    ["Incident layer", "Clustered points by zoom level", "Color by status/category"],
    ["Choropleth", "Deaths/incidents per admin_area", "Linked to analytics filters"],
    ["Draw tools", "Mapbox Draw (or MapLibre equivalent)", "Analyst pin adjustment with audit"],
  ], [25, 35, 40]),
  h2("6.4 Spatial Queries"),
  bullet("Hotspot analysis: ST_ClusterDBSCAN on published incidents"),
  bullet("Radius search: ST_DWithin for corridor/cross-border analysis"),
  bullet("Within country: ST_Contains(country.geom, incident.geom) validation"),
  pb()
];

const s7 = [
  h1("7. Security Architecture"),
  h2("7.1 Defence in Depth"),
  tbl(["Layer", "Control"], [
    ["Network", "TLS 1.3, WAF, IP allowlist for admin, private subnet for DB"],
    ["Application", "CSRF, rate limiting, input validation, security headers"],
    ["Authentication", "Email + password (Argon2id), MFA (TOTP) for ADMIN+"],
    ["Authorization", "RBAC + ABAC voters (country scope, object ownership)"],
    ["Data", "Encryption at rest (DB TDE), encrypted backups, field-level classification tags"],
    ["Audit", "Append-only audit_log; SIEM export optional"],
  ], [25, 75]),
  h2("7.2 RBAC Matrix (target)"),
  tbl(["Permission", "FOCAL", "REVIEWER", "ANALYST", "ADMIN", "SUPER"], [
    ["incident.create", "✓ (own country)", "✓", "—", "✓", "✓"],
    ["incident.view", "✓ (own country)", "✓ (all)", "✓ (published)", "✓", "✓"],
    ["incident.publish", "—", "✓", "—", "✓", "✓"],
    ["incident.export", "—", "✓", "✓", "✓", "✓"],
    ["analytics.executive", "—", "—", "✓", "✓", "✓"],
    ["gis.precise_coords", "—", "—", "✓", "✓", "✓"],
    ["ref_data.manage", "—", "—", "—", "—", "✓"],
    ["user.manage", "—", "—", "—", "—", "✓"],
  ], [22, 13, 13, 13, 13, 13]),
  h2("7.3 Audit Event Catalogue"),
  body("AUTH_LOGIN, AUTH_LOGOUT, AUTH_FAILED, INCIDENT_CREATE, INCIDENT_UPDATE, INCIDENT_DELETE, INCIDENT_SUBMIT, INCIDENT_PUBLISH, INCIDENT_REJECT, IMPORT_START, IMPORT_COMPLETE, EXPORT_REQUEST, USER_CREATE, USER_DISABLE, REF_DATA_CHANGE"),
  h2("7.4 Backup & Disaster Recovery"),
  tbl(["Tier", "RPO", "RTO", "Method"], [
    ["Database", "1 hour", "4 hours", "pg_dump continuous + WAL archiving to object storage"],
    ["Application", "24 hours", "2 hours", "Container images in registry; IaC redeploy"],
    ["Files (imports)", "24 hours", "4 hours", "Object storage replication"],
  ], [22, 15, 15, 48]),
  body("Quarterly restore drills mandatory. Runbook documented in ops repository."),
  pb()
];

const s8 = [
  h1("8. Infrastructure Architecture"),
  h2("8.1 Environment Topology"),
  tbl(["Environment", "Purpose", "Data"], [
    ["Development", "Developer workstations + Docker Compose", "Anonymized subset"],
    ["Staging", "Pre-production parity", "Masked copy of production"],
    ["Production", "Live AUCTC operations", "Full classified data"],
    ["DR", "Warm standby (optional Phase 2)", "Replicated from prod"],
  ], [22, 38, 40]),
  h2("8.2 Container Stack (recommended)"),
  mono("docker-compose / Kubernetes:\n  app (PHP-FPM 8.2 + Symfony)\n  nginx (reverse proxy)\n  postgres-postgis:16-3.4\n  redis:7\n  worker (messenger consume)\n  superset (analytics)\n  martin (tile server)"),
  h2("8.3 CI/CD Pipeline"),
  mono("Git push → GitHub Actions / GitLab CI:\n  composer install → phpstan → phpunit → security-check\n  → docker build → deploy staging → smoke tests → manual prod approval"),
  pb()
];

const s9 = [
  h1("9. UX/UI Redesign Strategy"),
  h2("9.1 Design Principles"),
  bullet("Role-first navigation: each persona lands on their primary task surface"),
  bullet("Progressive disclosure: summary → detail; never 30 columns by default"),
  bullet("Consistent FR/EN via Symfony Translation + institutional terminology glossary"),
  bullet("Mobile-responsive for field focal points (tablet minimum)"),
  bullet("Accessibility: WCAG 2.1 AA target for institutional compliance"),
  h2("9.2 Information Architecture"),
  mono("┌─ Focal Point ─────────────────────────────────────────┐\n│ Home: My submissions (status chips) + Quick capture    │\n│ Incidents | Import | Help                                │\n└─────────────────────────────────────────────────────────┘\n┌─ Reviewer ──────────────────────────────────────────────┐\n│ Home: Validation inbox (count badge) + Recently published│\n│ Inbox | All incidents | Analytics | Map                  │\n└─────────────────────────────────────────────────────────┘\n┌─ Executive / Analyst ───────────────────────────────────┐\n│ Home: KPI cards + regional map + trend sparklines        │\n│ Dashboard | Map | Reports | Explore (Superset)           │\n└─────────────────────────────────────────────────────────┘"),
  h2("9.3 Key Screen Designs"),
  tbl(["Screen", "Purpose", "UX improvements"], [
    ["Validation Inbox", "Review pending incidents", "Sortable queue; bulk actions; side-by-side diff on edit"],
    ["Incident Detail", "Single incident view", "Tabbed: Summary | Casualties | Map | History | Audit"],
    ["Analytics Hub", "Self-service filters", "Date range on date_attaque; save views; share link (auth)"],
    ["Map Explorer", "Spatial analysis", "Split view: map + filtered list; draw radius; export viewport"],
    ["Import Wizard", "Bulk load", "Template download; drag-drop; progress bar; error report"],
    ["Executive Brief", "Decision support", "3–5 KPIs; regional heatmap; period comparison; PDF export"],
  ], [22, 28, 50]),
  h2("9.4 Component Library"),
  body("Migrate from Bootstrap 4/jQuery to: Bootstrap 5.3 or institutional design system; Stimulus controllers for interactivity; replace DataTables with TanStack Table or keep Omines with column presets API."),
  pb()
];

const s10 = [
  h1("10. Deployment Strategy"),
  bullet("Blue/green or rolling deployment via container orchestration"),
  bullet("Database migrations via Doctrine — backward-compatible expand/contract pattern"),
  bullet("Feature flags (Symfony) for GIS and new analytics modules"),
  bullet("Maintenance window only for major DB schema cutovers"),
  bullet("Automated smoke tests post-deploy: login, list incidents, one chart endpoint"),
  pb()
];

const s11 = [
  h1("11. Migration Strategy"),
  h2("11.1 Data Migration (MySQL → PostgreSQL)"),
  mono("Phase A: Schema creation on PostgreSQL\nPhase B: ETL script all_data → incident + incident_casualty\nPhase C: Reference data migration with code mapping\nPhase D: Geocode localities (batch job)\nPhase E: Parallel run (read-only compare counts)\nPhase F: Cutover weekend (freeze writes, final sync, DNS switch)"),
  h2("11.2 Application Migration"),
  bullet("Wave 1: Symfony 7 on PostgreSQL with legacy-compatible views if needed"),
  bullet("Wave 2: Introduce new tables/API alongside; deprecate all_data direct access"),
  bullet("Wave 3: Remove legacy paths; archive MySQL"),
  h2("11.3 Rollback Plan"),
  body("Maintain MySQL read-only snapshot for 30 days post-cutover. DNS revert procedure documented. No destructive migration without verified backup."),
  pb()
];

const s12 = [
  h1("12. Testing Strategy"),
  tbl(["Level", "Scope", "Tools"], [
    ["Unit", "Domain services, validators, calculators", "PHPUnit"],
    ["Integration", "Repositories, migrations, import pipeline", "PHPUnit + test DB"],
    ["API", "REST contract", "PHPUnit + OpenAPI schema validation"],
    ["Security", "AuthZ matrix, public endpoint scan", "PHPUnit + OWASP ZAP (staging)"],
    ["E2E", "Critical workflows", "Panther or Playwright"],
    ["Performance", "DataTable, map cluster, import 1000 rows", "k6 or Apache Bench"],
    ["UAT", "Analyst/focal acceptance", "Institutional test scripts"],
  ], [18, 42, 40]),
  body("Coverage target: ≥80% on domain services; 100% on security voters and import validators."),
  pb()
];

const s13 = [
  h1("13. Scalability Strategy"),
  tbl(["Concern", "Threshold", "Mitigation"], [
    ["Incident volume", "100K+ records", "Partitioning by year; archival policy; read replica"],
    ["Concurrent analysts", "50+ simultaneous", "Redis session; DB connection pooling; CDN for static"],
    ["Import volume", "10K rows/file", "Async Messenger; batch inserts; staging tables"],
    ["Map tiles", "High zoom traffic", "Martin cache; CDN for tiles"],
    ["Analytics queries", "Heavy aggregations", "Materialized views; Superset async queries"],
  ], [28, 27, 45]),
  body("Horizontal scaling: stateless app containers behind load balancer; sticky sessions avoided via Redis session store."),
  pb()
];

const s14 = [
  h1("14. Sustainability Strategy"),
  h2("14.1 Institutional Ownership"),
  bullet("Runbooks for deploy, backup restore, import failure, security incident"),
  bullet("Architecture Decision Records (ADR) in docs/adr/"),
  bullet("Quarterly dependency audit (composer audit, npm audit)"),
  bullet("Annual penetration test by accredited provider"),
  h2("14.2 Team & Training"),
  bullet("Symfony/PostgreSQL/PostGIS training for maintainers"),
  bullet("GIS analyst training for map tools and coordinate classification policy"),
  bullet("Knowledge transfer sessions at end of each wave"),
  h2("14.3 Technology Lifecycle"),
  tbl(["Component", "Review cycle", "Action"], [
    ["PHP / Symfony", "Annual", "Stay on active LTS; plan upgrade 6 months before EOL"],
    ["PostgreSQL", "Annual", "Minor version upgrades in staging first"],
    ["PostGIS", "Annual", "Follow PostgreSQL compatibility matrix"],
    ["Front-end deps", "Quarterly", "npm audit fix; major upgrades in dedicated sprint"],
  ], [28, 22, 50]),
  pb()
];

const appendix = [
  h1("Appendix — Architecture Decision Records (Summary)"),
  tbl(["ADR", "Decision", "Status"], [
    ["ADR-001", "PostgreSQL + PostGIS over MySQL spatial", "Proposed"],
    ["ADR-002", "Modular monolith over microservices", "Accepted"],
    ["ADR-003", "date_attaque as canonical analytics dimension", "Accepted"],
    ["ADR-004", "Append-only audit_log over application logging only", "Accepted"],
    ["ADR-005", "Self-hosted Superset over Tableau Public", "Proposed"],
    ["ADR-006", "Symfony Messenger for async ETL", "Accepted"],
    ["ADR-007", "MapLibre + Martin over Google Maps", "Proposed"],
  ], [15, 55, 30]),
  sp(),
  new Paragraph({ alignment: AlignmentType.CENTER,
    children: [new TextRun({ text: "— End of Technical Design Document v1.0 — 26 May 2026", size: pt(10), color: GRAY, italics: true })] }),
];

const doc = new Document({
  creator: "AUCTC Architecture Team",
  title: "AUCTC CAERT Technical Design Document",
  sections: [{
    properties: { page: { margin: { top: 1440, bottom: 1440, left: 1440, right: 1440 } } },
    headers: { default: new Header({ children: [new Paragraph({
      alignment: AlignmentType.RIGHT,
      border: { bottom: { color: BLUE, size: 6, style: BorderStyle.SINGLE } },
      children: [new TextRun({ text: "AUCTC — CAERT Technical Design v1.0 | SENSITIVE", size: pt(8), color: GRAY, italics: true })]
    })] }) },
    footers: { default: new Footer({ children: [new Paragraph({
      alignment: AlignmentType.CENTER,
      border: { top: { color: BLUE, size: 6, style: BorderStyle.SINGLE } },
      children: [
        new TextRun({ text: "Page ", size: pt(8), color: GRAY }),
        new TextRun({ children: [PageNumber.CURRENT], size: pt(8), color: GRAY }),
        new TextRun({ text: " of ", size: pt(8), color: GRAY }),
        new TextRun({ children: [PageNumber.TOTAL_PAGES], size: pt(8), color: GRAY }),
      ]
    })] }) },
    children: [...cover, ...toc, ...s1, ...s2, ...s3, ...s4, ...s5, ...s6, ...s7, ...s8, ...s9, ...s10, ...s11, ...s12, ...s13, ...s14, ...appendix]
  }]
});

const out = path.join(process.cwd(), "AUCTC_CAERT_Technical_Design_2026.docx");
Packer.toBuffer(doc).then(buf => { fs.writeFileSync(out, buf); console.log("Written:", out); });
