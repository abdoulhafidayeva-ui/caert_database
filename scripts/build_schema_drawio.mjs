/**
 * Génère le schéma de tables AUCTC (draw.io / diagrams.net), modifiable.
 * Source de vérité : migrations Doctrine (état final).
 */
import fs from 'fs';
import path from 'path';

const ROOT = process.cwd();
const OUT = path.join(
  ROOT,
  'deliverables',
  'consultance_juillet_2026',
  'diagrammes',
  '05_schema_tables_AUCTC.drawio',
);

function esc(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

let nextId = 2;
const id = () => String(nextId++);

const ROW_H = 22;
const HEAD_H = 28;
const COL_W = 260;

function table(name, cols, x, y, fill) {
  const tid = id();
  const h = HEAD_H + cols.length * ROW_H;
  const cells = [];
  cells.push(`<mxCell id="${tid}" value="${esc(name)}" style="swimlane;fontStyle=1;align=center;verticalAlign=middle;childLayout=stackLayout;horizontal=1;startSize=${HEAD_H};horizontalStack=0;resizeParent=1;resizeParentMax=0;resizeLast=0;collapsible=0;marginBottom=0;whiteSpace=wrap;html=1;fillColor=${fill};strokeColor=#6c8ebf;fontColor=#000000;" vertex="1" parent="1">
        <mxGeometry x="${x}" y="${y}" width="${COL_W}" height="${h}" as="geometry" />
      </mxCell>`);
  const ports = {};
  cols.forEach((c, i) => {
    const cid = id();
    ports[c.key || c.label] = cid;
    const bold = c.pk || c.fk ? 'fontStyle=1;' : '';
    const color = c.pk ? 'fontColor=#0B5CAB;' : (c.fk ? 'fontColor=#B85450;' : '');
    cells.push(`<mxCell id="${cid}" value="${esc(c.label)}" style="text;strokeColor=none;fillColor=none;align=left;verticalAlign=middle;spacingLeft=6;spacingRight=4;overflow=hidden;rotatable=0;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;whiteSpace=wrap;html=1;${bold}${color}" vertex="1" parent="${tid}">
        <mxGeometry y="${HEAD_H + i * ROW_H}" width="${COL_W}" height="${ROW_H}" as="geometry" />
      </mxCell>`);
  });
  return { cells: cells.join('\n'), ports, tid };
}

function edge(fromPort, toPort, label) {
  const eid = id();
  const lab = label ? ` value="${esc(label)}"` : '';
  return `<mxCell id="${eid}"${lab} style="edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;endArrow=ERmany;startArrow=ERone;startFill=0;endFill=0;strokeColor=#666666;fontSize=10;" edge="1" parent="1" source="${fromPort}" target="${toPort}">
        <mxGeometry relative="1" as="geometry" />
      </mxCell>`;
}

const fillMain = '#dae8fc';
const fillRef = '#d5e8d4';
const fillGeo = '#fff2cc';
const fillSys = '#f5f5f5';
const fillUser = '#e1d5e7';

const region = table('region', [
  { label: 'PK  id  INT', pk: true, key: 'id' },
  { label: 'libelle  VARCHAR(255)', key: 'libelle' },
  { label: 'code  VARCHAR(255)', key: 'code' },
], 40, 40, fillGeo);

const pays = table('pays', [
  { label: 'PK  id  INT', pk: true, key: 'id' },
  { label: 'FK  region_id  INT', fk: true, key: 'region_id' },
  { label: 'libelle  VARCHAR(255)', key: 'libelle' },
  { label: 'code  VARCHAR(255)', key: 'code' },
  { label: 'capitale  VARCHAR(255)', key: 'capitale' },
], 40, 200, fillGeo);

const users = table('users', [
  { label: 'PK  id  INT', pk: true, key: 'id' },
  { label: 'FK  pays_id  INT NULL', fk: true, key: 'pays_id' },
  { label: 'FK  region_id  INT NULL', fk: true, key: 'region_id' },
  { label: 'name  VARCHAR(255)', key: 'name' },
  { label: 'prenoms  VARCHAR(255)', key: 'prenoms' },
  { label: 'email  VARCHAR(255) UNIQUE', key: 'email' },
  { label: 'password  VARCHAR(255) NULL', key: 'password' },
  { label: 'profil  VARCHAR(255) NULL', key: 'profil' },
  { label: 'roles  JSON NULL', key: 'roles' },
  { label: 'fonction  VARCHAR(255) NULL', key: 'fonction' },
  { label: 'organisation  VARCHAR(255) NULL', key: 'organisation' },
  { label: 'enable  TINYINT NULL', key: 'enable' },
  { label: 'is_verified  TINYINT NULL', key: 'is_verified' },
  { label: 'notify_by  INT NULL', key: 'notify_by' },
  { label: 'token  VARCHAR(255) UNIQUE NULL', key: 'token' },
  { label: 'token_created_at  DATETIME NULL', key: 'token_created_at' },
  { label: 'locale  VARCHAR(5) NULL', key: 'locale' },
  { label: 'created_at  DATETIME NULL', key: 'created_at' },
  { label: 'update_at  DATETIME NULL', key: 'update_at' },
], 360, 40, fillUser);

const refCols = [
  { label: 'PK  id  INT', pk: true, key: 'id' },
  { label: 'FK  user_id  INT NULL', fk: true, key: 'user_id' },
  { label: 'libelle  VARCHAR(255) UNIQUE', key: 'libelle' },
  { label: 'created_at  DATETIME', key: 'created_at' },
];

const attaque = table('attaque', refCols, 1000, 40, fillRef);
const cible = table('cible', refCols, 1000, 200, fillRef);
const moyen = table('moyen_attaque', refCols, 1000, 360, fillRef);
const matAtt = table('materiel_attaque', refCols, 1000, 520, fillRef);
const materiaux = table('materiaux', refCols, 1280, 40, fillRef);
const perpet = table('perpetrateurs', refCols, 1280, 200, fillRef);
const espace = table('espace', refCols, 1280, 360, fillRef);

const allData = table('all_data', [
  { label: 'PK  id  INT', pk: true, key: 'id' },
  { label: 'FK  attaque_id  INT NULL', fk: true, key: 'attaque_id' },
  { label: 'FK  materiel_attaque_id  INT NULL', fk: true, key: 'materiel_attaque_id' },
  { label: 'FK  cible_id  INT NULL', fk: true, key: 'cible_id' },
  { label: 'FK  materieaux_id  INT NULL', fk: true, key: 'materieaux_id' },
  { label: 'FK  moyen_attaque_id  INT NULL', fk: true, key: 'moyen_attaque_id' },
  { label: 'FK  perpetrateur_id  INT NULL', fk: true, key: 'perpetrateur_id' },
  { label: 'FK  pays_id  INT NULL', fk: true, key: 'pays_id' },
  { label: 'FK  user_id  INT NULL', fk: true, key: 'user_id' },
  { label: 'FK  espace_id  INT NULL', fk: true, key: 'espace_id' },
  { label: 'details  VARCHAR(255)', key: 'details' },
  { label: 'localite  VARCHAR(255) NULL', key: 'localite' },
  { label: 'date_attaque  DATETIME NULL', key: 'date_attaque' },
  { label: 'mort_securite_militaire  INT NULL', key: 'msm' },
  { label: 'mort_civil  INT', key: 'mc' },
  { label: 'mort_terroriste  INT', key: 'mt' },
  { label: 'disparu_securite_militaire  INT NULL', key: 'dsm' },
  { label: 'disparu_civil  INT NULL', key: 'dc' },
  { label: 'disparu_terroriste  INT NULL', key: 'dt' },
  { label: 'blesse_securite_militaire  INT', key: 'bsm' },
  { label: 'blesse_civil  INT', key: 'bc' },
  { label: 'blesse_terroriste  INT', key: 'bt' },
  { label: 'total_deces  INT', key: 'td' },
  { label: 'total_disparus  INT NULL', key: 'tdi' },
  { label: 'total_blesses  INT', key: 'tb' },
  { label: 'otages  INT', key: 'ot' },
  { label: 'liberes  INT', key: 'li' },
  { label: 'terroriste_arretes  INT', key: 'ta' },
  { label: 'autres  VARCHAR(255)', key: 'autres' },
  { label: 'remarque  VARCHAR(255)', key: 'remarque' },
  { label: 'is_published  TINYINT NULL', key: 'is_published' },
  { label: 'objet_rejet  VARCHAR(255) NULL', key: 'objet_rejet' },
  { label: 'created_at  DATETIME', key: 'created_at' },
], 680, 520, fillMain);

const audit = table('audit_log', [
  { label: 'PK  id  INT', pk: true, key: 'id' },
  { label: 'FK  actor_id  INT NULL  ON DELETE SET NULL', fk: true, key: 'actor_id' },
  { label: 'action  VARCHAR(64)', key: 'action' },
  { label: 'entity_type  VARCHAR(128)', key: 'entity_type' },
  { label: 'entity_id  INT NULL', key: 'entity_id' },
  { label: 'ip_address  VARCHAR(45) NULL', key: 'ip' },
  { label: 'payload  JSON NULL', key: 'payload' },
  { label: 'created_at  DATETIME', key: 'created_at' },
], 40, 480, fillSys);

const appParam = table('app_param', [
  { label: 'PK  id  INT', pk: true, key: 'id' },
  { label: 'name  VARCHAR(255)', key: 'name' },
  { label: 'email  VARCHAR(255) NULL', key: 'email' },
  { label: 'site_url  VARCHAR(255) NULL', key: 'site_url' },
  { label: 'logo  VARCHAR(255) NULL', key: 'logo' },
  { label: 'created_at  DATETIME', key: 'created_at' },
  { label: 'updated_at  DATETIME', key: 'updated_at' },
], 40, 760, fillSys);

const roles = table('roles', [
  { label: 'PK  id  INT', pk: true, key: 'id' },
  { label: 'label  VARCHAR(255)', key: 'label' },
  { label: 'created_at  DATETIME', key: 'created_at' },
  { label: 'updated_at  DATETIME NULL', key: 'updated_at' },
  { label: 'deleted_at  DATETIME NULL', key: 'deleted_at' },
  { label: 'deleted  TINYINT NULL', key: 'deleted' },
], 360, 560, fillSys);

const tables = [region, pays, users, attaque, cible, moyen, matAtt, materiaux, perpet, espace, allData, audit, appParam, roles];

const edges = [
  edge(pays.ports.region_id, region.ports.id, 'region_id'),
  edge(users.ports.pays_id, pays.ports.id, 'pays_id'),
  edge(users.ports.region_id, region.ports.id, 'region_id'),
  edge(allData.ports.attaque_id, attaque.ports.id, 'attaque_id'),
  edge(allData.ports.materiel_attaque_id, matAtt.ports.id, 'materiel_attaque_id'),
  edge(allData.ports.cible_id, cible.ports.id, 'cible_id'),
  edge(allData.ports.materieaux_id, materiaux.ports.id, 'materieaux_id'),
  edge(allData.ports.moyen_attaque_id, moyen.ports.id, 'moyen_attaque_id'),
  edge(allData.ports.perpetrateur_id, perpet.ports.id, 'perpetrateur_id'),
  edge(allData.ports.pays_id, pays.ports.id, 'pays_id'),
  edge(allData.ports.user_id, users.ports.id, 'user_id'),
  edge(allData.ports.espace_id, espace.ports.id, 'espace_id'),
  edge(attaque.ports.user_id, users.ports.id),
  edge(cible.ports.user_id, users.ports.id),
  edge(moyen.ports.user_id, users.ports.id),
  edge(matAtt.ports.user_id, users.ports.id),
  edge(materiaux.ports.user_id, users.ports.id),
  edge(perpet.ports.user_id, users.ports.id),
  edge(espace.ports.user_id, users.ports.id),
  edge(audit.ports.actor_id, users.ports.id, 'actor_id'),
];

const titleId = id();
const noteId = id();

const xml = `<?xml version="1.0" encoding="UTF-8"?>
<mxfile host="app.diagrams.net" agent="AUCTC" version="24.7.0" type="device">
  <diagram id="auctc-schema" name="Schéma tables AUCTC">
    <mxGraphModel dx="1400" dy="900" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="2000" pageHeight="1400" math="0" shadow="0">
      <root>
        <mxCell id="0" />
        <mxCell id="1" parent="0" />
        <mxCell id="${titleId}" value="AUCTC — Schéma de tables (état final)&#xa;Ouvrir avec diagrams.net / draw.io — entièrement modifiable" style="text;html=1;strokeColor=none;fillColor=none;align=left;verticalAlign=middle;whiteSpace=wrap;rounded=0;fontStyle=1;fontSize=16;fontColor=#00529B;" vertex="1" parent="1">
          <mxGeometry x="40" y="-10" width="620" height="40" as="geometry" />
        </mxCell>
        <mxCell id="${noteId}" value="Légende : PK = clé primaire (bleu) · FK = clé étrangère (rouge)&#xa;Couleurs : jaune = géo · violet = utilisateurs · vert = référentiels · bleu = incidents · gris = système&#xa;Note : roles (table) est historique — les rôles applicatifs sont dans users.roles (JSON). Colonne all_data.materieaux_id → table materiaux." style="text;html=1;strokeColor=#CCCCCC;fillColor=#FAFAFA;align=left;verticalAlign=top;whiteSpace=wrap;rounded=1;fontSize=11;spacing=6;" vertex="1" parent="1">
          <mxGeometry x="40" y="1020" width="900" height="70" as="geometry" />
        </mxCell>
${tables.map((t) => t.cells).join('\n')}
${edges.join('\n')}
      </root>
    </mxGraphModel>
  </diagram>
</mxfile>
`;

fs.mkdirSync(path.dirname(OUT), { recursive: true });
fs.writeFileSync(OUT, xml, 'utf8');
console.log('OK:', OUT);
