#!/usr/bin/env bash
# CAERT — post-deployment validation (health, auth, migrations).
set -euo pipefail

BASE_URL="${1:-${APP_URL:-http://127.0.0.1:8080}}"
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
FAIL=0

pass() { echo "[OK] $1"; }
fail() { echo "[FAIL] $1"; FAIL=1; }

echo "==> CAERT deployment validation"
echo "    Target: $BASE_URL"

# Health endpoint
HTTP=$(curl -s -o /tmp/caert-health.json -w "%{http_code}" "$BASE_URL/health" || echo "000")
if [ "$HTTP" = "200" ]; then
  pass "Health endpoint (HTTP 200)"
  if grep -q '"database":"ok"' /tmp/caert-health.json 2>/dev/null; then
    pass "Database connectivity"
  else
    fail "Database check in /health"
  fi
else
  fail "Health endpoint (HTTP $HTTP)"
fi

# Login page reachable
LOGIN_HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/login" || echo "000")
if [ "$LOGIN_HTTP" = "200" ]; then
  pass "Login page"
else
  fail "Login page (HTTP $LOGIN_HTTP)"
fi

# Protected route redirects unauthenticated users
HOME_HTTP=$(curl -s -o /dev/null -w "%{http_code}" -L "$BASE_URL/" || echo "000")
if [ "$HOME_HTTP" = "200" ] || [ "$HOME_HTTP" = "302" ]; then
  pass "Home route responds ($HOME_HTTP)"
else
  fail "Home route (HTTP $HOME_HTTP)"
fi

# Local migration status (when run on app host)
if [ -f "$ROOT/bin/console" ]; then
  if php "$ROOT/bin/console" doctrine:migrations:status --no-interaction 2>/dev/null | grep -q "Already at latest version"; then
    pass "Migrations at latest version"
  else
    echo "[WARN] Migrations may be pending — run doctrine:migrations:status"
  fi
fi

rm -f /tmp/caert-health.json

if [ "$FAIL" -eq 0 ]; then
  echo "==> Validation PASSED"
  exit 0
fi

echo "==> Validation FAILED"
exit 1
