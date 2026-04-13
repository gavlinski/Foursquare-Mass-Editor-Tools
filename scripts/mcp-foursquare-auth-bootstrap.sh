#!/bin/bash
###############################################################################
# mcp-foursquare-auth-bootstrap.sh
# Captura storage state autenticado do Foursquare sem salvar senha no repositório.
# O usuário autentica manualmente no browser do host (Brave/Chrome) via CDP.
###############################################################################

set -e

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

cd /var/www/html

STORAGE_STATE="/var/www/html/data/mcp/playwright/foursquare.storage-state.json"
BROWSERS_DIR="/var/www/html/data/mcp/playwright/browsers"
LOGIN_URL="https://localhost/4sqmet/index.php"
CDP_ENDPOINT="${BROWSER_CDP_ENDPOINT:-${CHROME_CDP_ENDPOINT:-http://host.docker.internal:9222}}"

mkdir -p /var/www/html/data/mcp/playwright
chmod 700 /var/www/html/data/mcp/playwright || true

export PLAYWRIGHT_BROWSERS_PATH="$BROWSERS_DIR"

echo -e "${CYAN}🔐 Bootstrap de autenticação MCP (Foursquare)${NC}"
echo -e "${CYAN}   Login URL: $LOGIN_URL${NC}"
echo -e "${CYAN}   Storage state: $STORAGE_STATE${NC}"
echo -e "${CYAN}   CDP endpoint: $CDP_ENDPOINT${NC}"

if ! command -v curl >/dev/null 2>&1; then
        echo -e "${RED}❌ curl não encontrado no container.${NC}"
        exit 1
fi

if ! curl -sS "$CDP_ENDPOINT/json/version" >/dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  Não foi possível conectar ao browser via CDP.${NC}"
    echo -e "${YELLOW}➡️  Inicie o Brave no macOS com remote debugging e perfil dedicado:${NC}"
        echo ""
    echo "/Applications/Brave\\ Browser.app/Contents/MacOS/Brave\\ Browser --remote-debugging-port=9222 --user-data-dir=/tmp/brave-mcp-profile"
    echo ""
    echo -e "${YELLOW}Alternativa com Google Chrome:${NC}"
    echo "/Applications/Google\\ Chrome.app/Contents/MacOS/Google\\ Chrome --remote-debugging-port=9222 --user-data-dir=/tmp/chrome-mcp-profile"
        echo ""
        echo -e "${YELLOW}Depois execute novamente este script.${NC}"
        exit 1
fi

echo -e "${YELLOW}➡️  No Brave, abra manualmente a URL abaixo e faça login:${NC}"
echo -e "   ${LOGIN_URL}"
echo ""
echo -e "${YELLOW}➡️  Faça login manualmente no Foursquare no Brave.${NC}"
echo -e "${YELLOW}➡️  Depois de concluir, volte aqui e pressione ENTER para capturar a sessão.${NC}"
read -r

# Obtém a WebSocket URL diretamente do /json/version (sem barra final) para
# contornar o erro 500 que Brave retorna quando Playwright adiciona a barra final.
echo -e "${CYAN}ℹ️  Obtendo WebSocket URL do browser...${NC}"
BASE_URL="${CDP_ENDPOINT%/}"
VERSION_JSON=$(curl -s --max-time 5 -H 'Host: localhost' "${BASE_URL}/json/version")
echo -e "${CYAN}   /json/version: ${VERSION_JSON}${NC}"

CDP_WS_URL=$(echo "$VERSION_JSON" \
    | grep -o '"webSocketDebuggerUrl": *"[^"]*"' \
    | grep -o '"ws://[^"]*"' \
    | tr -d '"' \
    | sed 's|ws://localhost/|ws://host.docker.internal:9222/|g; s|ws://127\.0\.0\.1/|ws://host.docker.internal:9222/|g')

# Fallback: tentar /json/list (lista de targets)
if [ -z "$CDP_WS_URL" ]; then
    echo -e "${YELLOW}⚠️  webSocketDebuggerUrl não encontrada em /json/version, tentando /json/list...${NC}"
    LIST_JSON=$(curl -s --max-time 5 -H 'Host: localhost' "${BASE_URL}/json/list")
    echo -e "${CYAN}   /json/list: ${LIST_JSON}${NC}"
    CDP_WS_URL=$(echo "$LIST_JSON" \
        | grep -o '"webSocketDebuggerUrl": *"[^"]*"' \
        | head -1 \
        | grep -o '"ws://[^"]*"' \
        | tr -d '"' \
        | sed 's|ws://localhost/|ws://host.docker.internal:9222/|g; s|ws://127\.0\.0\.1/|ws://host.docker.internal:9222/|g')
fi

if [ -z "$CDP_WS_URL" ]; then
    echo -e "${RED}❌ Não foi possível obter WebSocket URL de ${BASE_URL}${NC}"
    echo -e "${YELLOW}   Verifique se o Brave está rodando com: --remote-debugging-port=9222${NC}"
    exit 1
fi
echo -e "${CYAN}   WS URL: $CDP_WS_URL${NC}"

TMP_DIR=$(mktemp -d)
trap "rm -rf '$TMP_DIR'" EXIT

cat > "$TMP_DIR/script.js" <<'EOF'
const fs = require('fs');
const { chromium } = require('playwright');

async function main() {
    const wsUrl = process.env.CDP_WS_URL;
    const output = process.env.MCP_STORAGE_STATE;

    const browser = await chromium.connectOverCDP(wsUrl);
    const contexts = browser.contexts();

    if (!contexts.length) {
        throw new Error('Nenhum contexto disponível no browser remoto.');
    }

    const context = contexts[0];
    await context.storageState({ path: output });
    await browser.close();

    if (!fs.existsSync(output)) {
        throw new Error('Storage state não foi criado.');
    }
}

main().catch((err) => {
    console.error(err.message || err);
    process.exit(1);
});
EOF

# Resolve playwright: prefere instalação existente no workspace, senão instala no tmp dir
PW_LOCAL="/var/www/html/node_modules/playwright"
if [ -d "$PW_LOCAL" ]; then
    mkdir -p "$TMP_DIR/node_modules"
    ln -s "$PW_LOCAL" "$TMP_DIR/node_modules/playwright"
    echo -e "${CYAN}ℹ️  Usando playwright do workspace.${NC}"
else
    echo -e "${CYAN}ℹ️  Instalando playwright (sem browsers)...${NC}"
    cd "$TMP_DIR"
    PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1 npm install playwright --quiet --no-fund --no-audit 2>&1 | tail -3
    cd /var/www/html
fi

if ! CDP_WS_URL="$CDP_WS_URL" MCP_STORAGE_STATE="$STORAGE_STATE" node "$TMP_DIR/script.js"; then
    echo -e "${RED}❌ Falha ao capturar storage state via CDP.${NC}"
    exit 1
fi

if [ -f "$STORAGE_STATE" ]; then
    chmod 600 "$STORAGE_STATE"
    echo -e "${GREEN}✅ Storage state salvo com permissão restrita (600).${NC}"
    echo -e "${GREEN}   O agente MCP já pode usar sua sessão autenticada.${NC}"
else
    echo -e "${RED}❌ Storage state não foi gerado.${NC}"
    exit 1
fi
