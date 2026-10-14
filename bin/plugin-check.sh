#!/usr/bin/env bash
# Plugin Check against the wordpress.org zip surface (not the git tree).
# Usage: bin/plugin-check.sh [extra wp plugin check args]
#
# Ignored PHPCS/check codes (documented, not silent skips of real errors):
# - ExceptionNotEscaped: exception messages are not HTML output.
# - NonPrefixedHooknameFound: WordPress core hooks plus locked sc_* public API.
# - load_plugin_textdomainFound: Domain Path is resources/languages; needed off wordpress.org.
set -euo pipefail
PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
WP_ROOT="$(cd "$PLUGIN_DIR/../../.." && pwd)"
cd "$WP_ROOT"
wp plugin check signclave-sso \
  --exclude-directories=tests,bin,.github \
  --exclude-files=phpcs.xml.dist,phpunit.xml.dist,phpunit.integration.xml.dist,rector.php,phpstan.neon,phpstan-bootstrap.php,infection.json5,.distignore,.gitignore,.DS_Store,roadmap.md \
  --ignore-codes=WordPress.Security.EscapeOutput.ExceptionNotEscaped,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound \
  "$@"
