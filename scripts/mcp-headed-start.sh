#!/bin/bash
# mcp-headed-start.sh
# Wrapper para playwright-headed: busca a WS URL do Brave, transforma (localhost → host.docker.internal:9222)
# e inicia @playwright/mcp com a URL correta para evitar o erro "WebSocket was closed".
#
# Problema: Brave retorna "webSocketDebuggerUrl":"ws://localhost/devtools/..." (sem porta)
# O MCP tentaria conectar na porta 80 (Apache). Precisamos substituir por host.docker.internal:9222.

set -e

CDP_BASE="http://host.docker.internal:9222"
HOST_HEADER="Host: localhost"

# Busca /json/version do Brave com o Host header correto
JSON=$(curl -s --max-time 5 -H "$HOST_HEADER" "${CDP_BASE}/json/version" 2>/dev/null || true)

if [ -z "$JSON" ]; then
    echo "ERROR: Brave não está acessível em ${CDP_BASE}. Abra o Brave com --remote-debugging-port=9222" >&2
    exit 1
fi

# Extrai a WS URL e substitui localhost → host.docker.internal:9222
WS_URL=$(echo "$JSON" \
    | grep -o '"webSocketDebuggerUrl": *"[^"]*"' \
    | grep -o 'ws://[^"]*' \
    | sed 's|ws://localhost/|ws://host.docker.internal:9222/|g')

if [ -z "$WS_URL" ]; then
    echo "ERROR: Não foi possível extrair WebSocket URL da resposta do Brave" >&2
    echo "Resposta: $JSON" >&2
    exit 1
fi

exec npx @playwright/mcp@latest \
    --cdp-endpoint "$WS_URL" \
    --ignore-https-errors \
    --output-dir /var/www/html/debug/mcp-artifacts \
    --output-mode file \
    --viewport-size 1440x900
