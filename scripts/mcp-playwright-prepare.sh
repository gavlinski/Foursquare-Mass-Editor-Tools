#!/bin/bash
###############################################################################
# mcp-playwright-prepare.sh
# Prepara cache persistente do Playwright MCP (Chromium + diretórios de artefato)
###############################################################################

set -e

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

cd /var/www/html

BROWSERS_DIR="/var/www/html/data/mcp/playwright/browsers"
ARTIFACTS_DIR="/var/www/html/debug/mcp-artifacts"

mkdir -p "$BROWSERS_DIR" "$ARTIFACTS_DIR"
chmod 700 /var/www/html/data/mcp/playwright || true
chmod 775 "$BROWSERS_DIR" "$ARTIFACTS_DIR" || true

export PLAYWRIGHT_BROWSERS_PATH="$BROWSERS_DIR"

echo -e "${CYAN}🎭 Playwright MCP prepare${NC}"
echo -e "${CYAN}   Cache path: $PLAYWRIGHT_BROWSERS_PATH${NC}"

if find "$PLAYWRIGHT_BROWSERS_PATH" -maxdepth 1 -type d -name 'chromium-*' | grep -q .; then
    echo -e "${GREEN}✅ Chromium já instalado (cache persistente)${NC}"
    exit 0
fi

echo -e "${YELLOW}⬇️  Instalando Chromium (primeira execução)...${NC}"
if npx -y playwright@latest install chromium; then
    echo -e "${GREEN}✅ Chromium instalado com sucesso${NC}"
else
    echo -e "${RED}❌ Falha na instalação do Chromium${NC}"
    exit 1
fi
