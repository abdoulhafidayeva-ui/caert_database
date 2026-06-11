#!/usr/bin/env bash
# CAERT — backup uploaded files (var/uploads). Schedule via cron after DB backup.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
UPLOAD_DIR="${UPLOAD_DIR:-$ROOT/var/uploads}"
BACKUP_DIR="${BACKUP_DIR:-$ROOT/var/backups}"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

if [ ! -d "$UPLOAD_DIR" ]; then
  echo "Upload directory not found: $UPLOAD_DIR" >&2
  exit 1
fi

OUT="$BACKUP_DIR/caert_uploads_${TIMESTAMP}.tar.gz"
echo "Archiving $UPLOAD_DIR to $OUT"
tar -czf "$OUT" -C "$ROOT/var" uploads

find "$BACKUP_DIR" -name 'caert_uploads_*.tar.gz' -mtime +14 -delete
echo "Upload backup complete: $OUT"
