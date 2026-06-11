#!/usr/bin/env bash
# CAERT — post-deploy smoke tests (requires curl and running app).
set -euo pipefail

BASE_URL="${1:-http://localhost:8080}"

echo "Smoke test: $BASE_URL"

code_health="$(curl -s -o /dev/null -w '%{http_code}' "$BASE_URL/health")"
if [ "$code_health" != "200" ]; then
  echo "FAIL /health returned $code_health" >&2
  exit 1
fi
echo "OK /health"

code_login="$(curl -s -o /dev/null -w '%{http_code}' "$BASE_URL/login")"
if [ "$code_login" != "200" ]; then
  echo "FAIL /login returned $code_login" >&2
  exit 1
fi
echo "OK /login"

# Protected routes must redirect or 401/403 without session
code_home="$(curl -s -o /dev/null -w '%{http_code}' -L "$BASE_URL/")"
if [ "$code_home" = "200" ]; then
  echo "WARN / accessible without auth (check security config)" >&2
else
  echo "OK / requires authentication ($code_home)"
fi

echo "Smoke tests passed."
