#!/bin/bash
###############################################################################
# mcp-foursquare-auth-clear.sh
# Remove storage state autenticado usado pelo MCP Playwright.
###############################################################################

set -e

STORAGE_STATE="/var/www/html/data/mcp/playwright/foursquare.storage-state.json"

if [ -f "$STORAGE_STATE" ]; then
    rm -f "$STORAGE_STATE"
    echo "✅ Storage state removido: $STORAGE_STATE"
else
    echo "ℹ️ Nenhum storage state encontrado em: $STORAGE_STATE"
fi
