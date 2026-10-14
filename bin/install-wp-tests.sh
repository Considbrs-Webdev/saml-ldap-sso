#!/usr/bin/env bash
# Install WordPress phpunit test library for the Integration suite.
# Usage: bin/install-wp-tests.sh [db-name] [db-user] [db-pass] [db-host]
set -euo pipefail
DB_NAME="${1:-sc_tests}"
DB_USER="${2:-root}"
DB_PASS="${3:-}"
DB_HOST="${4:-127.0.0.1}"
WP_VERSION="${WP_VERSION:-latest}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
TMP="${PLUGIN_DIR}/tests/.wordpress"
mkdir -p "$TMP"
curl -sS https://raw.githubusercontent.com/wp-cli/scaffold-command/v2.0.1/templates/install-wp-tests.sh -o "$TMP/install-wp-tests.sh"
chmod +x "$TMP/install-wp-tests.sh"
WP_TESTS_DIR="$TMP/wordpress-tests-lib" WP_CORE_DIR="$TMP/wordpress" \
  bash "$TMP/install-wp-tests.sh" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST" "$WP_VERSION" true
echo "Set SC_WP_TESTS_DIR=$TMP/wordpress-tests-lib before running: composer test:integration"
