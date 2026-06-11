#!/usr/bin/env bash
# CAERT — MySQL backup (production). Schedule via cron: 0 2 * * *
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
BACKUP_DIR="${BACKUP_DIR:-$ROOT/var/backups}"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

if [ -z "${DATABASE_URL:-}" ]; then
  if [ -f "$ROOT/.env" ]; then
    # shellcheck disable=SC1090
    set -a && source "$ROOT/.env" && set +a
  fi
fi

if [ -z "${DATABASE_URL:-}" ]; then
  echo "DATABASE_URL is required" >&2
  exit 1
fi

# mysql://user:pass@host:port/dbname
PARSED="${DATABASE_URL#mysql://}"
CREDS="${PARSED%%@*}"
HOST_DB="${PARSED#*@}"
USER="${CREDS%%:*}"
PASS="${CREDS#*:}"
HOST_PORT="${HOST_DB%%/*}"
DB="${HOST_DB#*/}"
DB="${DB%%\?*}"
HOST="${HOST_PORT%%:*}"
PORT="${HOST_PORT#*:}"
PORT="${PORT:-3306}"

OUT="$BACKUP_DIR/caert_${DB}_${TIMESTAMP}.sql.gz"
echo "Backing up $DB to $OUT"
mysqldump -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" \
  --single-transaction --routines --triggers "$DB" | gzip -9 > "$OUT"

find "$BACKUP_DIR" -name 'caert_*.sql.gz' -mtime +14 -delete
echo "Backup complete: $OUT"
