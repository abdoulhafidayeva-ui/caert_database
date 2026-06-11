#!/usr/bin/env bash
# CAERT — restore database from gzip backup (disaster recovery).
# Usage: ./disaster-recovery-restore.sh /path/to/caert_db_YYYYMMDD.sql.gz
set -euo pipefail

if [ $# -lt 1 ]; then
  echo "Usage: $0 <backup.sql.gz>" >&2
  exit 1
fi

BACKUP_FILE="$1"
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"

if [ ! -f "$BACKUP_FILE" ]; then
  echo "Backup file not found: $BACKUP_FILE" >&2
  exit 1
fi

if [ -z "${DATABASE_URL:-}" ] && [ -f "$ROOT/.env" ]; then
  set -a && source "$ROOT/.env" && set +a
fi

if [ -z "${DATABASE_URL:-}" ]; then
  echo "DATABASE_URL is required" >&2
  exit 1
fi

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

echo "WARNING: This will REPLACE database '$DB' on $HOST:$PORT"
read -r -p "Type RESTORE to continue: " CONFIRM
if [ "$CONFIRM" != "RESTORE" ]; then
  echo "Aborted."
  exit 1
fi

echo "Restoring from $BACKUP_FILE ..."
gunzip -c "$BACKUP_FILE" | mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" "$DB"
echo "Restore complete. Run: php bin/console cache:clear --env=prod"
