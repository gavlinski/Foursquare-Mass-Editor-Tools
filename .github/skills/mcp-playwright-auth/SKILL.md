# MCP Playwright Auth Skill

## Name
Playwright MCP — Autenticação e Injeção de Token Foursquare

## Description
Procedimentos para autenticar o agente MCP no Foursquare via CDP (Chrome DevTools Protocol), extrair tokens salvos e injetá-los no browser Playwright para navegar em páginas autenticadas. **Sem senhas armazenadas — o usuário autentica visualmente no Brave.**

## When to use
Carregue esta skill quando:
- O agente MCP precisar acessar páginas protegidas da aplicação (main.php, edit.php, load.php)
- Diagnosticar falhas de autenticação do Playwright MCP
- Renovar o storage state expirado (token expira em ~6 meses)
- Configurar o fluxo de auth em um novo dev container
- Escrever testes ou automações que requerem sessão autenticada

---

## Arquitetura do Fluxo

```
Host macOS (Brave)                   Dev Container (Playwright MCP)
─────────────────────────────────────────────────────────────────
Brave --remote-debugging-port=9222
       ↑                                Bootstrap script:
Usuário faz login no Foursquare         1. curl /json/version → ws:// URL
       ↓                                2. chromium.connectOverCDP(wsUrl)
Sessão salva no perfil Brave            3. context.storageState() → arquivo JSON
                                        4. chmod 600 no arquivo
                                   ↓
                    data/mcp/playwright/foursquare.storage-state.json
                                   ↓
                    Agente lê token via get-foursquare-token.js
                                   ↓
                    document.cookie injection em cada navegação
```

---

## Arquivos Chave

| Arquivo | Função |
|---------|--------|
| `scripts/mcp-foursquare-auth-bootstrap.sh` | Captura storage state via CDP do Brave |
| `scripts/mcp-foursquare-auth-clear.sh` | Remove storage state (forçar re-auth) |
| `scripts/mcp-playwright-prepare.sh` | Instala Chromium no caminho persistente |
| `scripts/get-foursquare-token.js` | Extrai token para uso programático |
| `scripts/playwright-inject-cookies.js` | Util para injeção de cookies |
| `data/mcp/playwright/foursquare.storage-state.json` | Tokens salvos (git-ignored, chmod 600) |
| `data/mcp/playwright/browsers/` | Chromium instalado (persistente, git-ignored) |
| `.vscode/mcp.json` | Configuração do servidor Playwright MCP |

## Headless vs Headed — Quando usar cada modo

### Regra de decisão rápida

| Situação | Modo a usar |
|----------|-------------|
| Debugging visual, ver o agente interagir | **`playwright-headed`** |
| Gerar scripts de teste (observar fluxos) | **`playwright-headed`** |
| Automação silenciosa, CI/CD | **`playwright`** (headless) |
| Brave offline / sem acesso ao host | **`playwright`** (headless) |
| Extração de dados, screenshots automáticos | **`playwright`** (headless) |

> **O agente NÃO pode iniciar ou reiniciar servidores MCP de forma autônoma.**
> Se o servidor `playwright-headed` não estiver ativo ou precisar ser reiniciado, o agente deve:
> 1. Pedir ao usuário: _"Por favor reinicie o servidor MCP `playwright-headed` via Ctrl+Shift+P → MCP: Restart Server"_
> 2. Aguardar confirmação antes de prosseguir
> 3. Se Brave não estiver rodando, pedir que o usuário execute o comando de inicialização

### Modo Headed (Visual) via CDP

**O que é:** o servidor `playwright-headed` conecta o agente ao **Brave já aberto no host**, em vez de lançar um Chromium headless. O usuário vê na tela do Brave **cada clique, digitação e navegação** que o agente faz.

| Aspecto | `playwright` (headless) | `playwright-headed` |
|---------|------------------------|---------------------|
| Brave precisa estar aberto | Não | **Sim** |
| Usuário vê as interações | Não | **Sim** |
| Injeção manual de token | Necessária | **Não** — Brave já tem os cookies do bootstrap |
| Disponível sem pré-requisitos | Sempre | Somente com Brave rodando |

### Como funciona

Quando o Brave é iniciado com `--remote-debugging-port=9222 --user-data-dir=/tmp/brave-mcp-profile` e o bootstrap já foi executado nesse perfil, o cookie `oauth_token` já está presente no browser. O agente conecta diretamente e já navega autenticado, sem injeção de cookie.

```
Host macOS (Brave headed)
  └─ --remote-debugging-port=9222
  └─ --user-data-dir=/tmp/brave-mcp-profile  ← já tem oauth_token
         ↑ CDP WebSocket
Dev Container (@playwright/mcp --cdp-endpoint)
         ↓ ações do agente aparecem visualmente no Brave
```

### Pré-requisito: Brave com o perfil de bootstrap

```bash
# macOS host — iniciar Brave com o MESMO perfil usado no bootstrap
/Applications/Brave\ Browser.app/Contents/MacOS/Brave\ Browser \
  --remote-debugging-port=9222 \
  --user-data-dir=/tmp/brave-mcp-profile
```

> ⚠️ Use **sempre** `--user-data-dir=/tmp/brave-mcp-profile` — o mesmo perfil do bootstrap.
> Sem esse flag, o Brave abre no perfil padrão, sem os cookies capturados.

### Ativar no VS Code

Após iniciar o Brave:
1. `Ctrl+Shift+P` → **MCP: Restart Server** → selecionar `playwright-headed`
2. No Chat, habilitar as tools do servidor `playwright-headed`
3. Navegar normalmente — o agente controlará o Brave visualmente

### Configuração no mcp.json

```json
"playwright-headed": {
  "type": "stdio",
  "command": "npx",
  "args": [
    "@playwright/mcp@latest",
    "--cdp-endpoint", "http://host.docker.internal:9222",
    "--cdp-header", "Host: localhost",
    "--ignore-https-errors",
    "--output-dir", "/var/www/html/debug/mcp-artifacts",
    "--output-mode", "file",
    "--viewport-size", "1440x900"
  ],
  "env": {
    "PLAYWRIGHT_MCP_CONSOLE_LEVEL": "error"
  }
}
```

> Nota: `--cdp-header "Host: localhost"` é obrigatório — sem ele o Brave rejeita a conexão com erro "Host header is not an IP address or localhost".

---



Antes de qualquer uso, verificar se o token ainda é válido:

```bash
node -e "
const fs = require('fs');
const data = JSON.parse(fs.readFileSync('/var/www/html/data/mcp/playwright/foursquare.storage-state.json', 'utf8'));
const cookies = data.cookies || [];
const token = cookies.find(c => c.domain === 'localhost' && c.name === 'oauth_token');
if (token) {
  const exp = new Date(token.expires * 1000);
  console.log('Token válido até:', exp.toISOString());
  console.log('Expirado:', exp < new Date());
} else {
  console.log('Token não encontrado — execute o bootstrap');
}
"
```

Ou via curl:
```bash
TOKEN=$(node /var/www/html/scripts/get-foursquare-token.js | grep oauth_token | cut -d'"' -f4)
curl -k -s -H "Cookie: oauth_token=$TOKEN" "https://localhost/4sqmet/session_status.php" | grep authenticated
```

---

## Fluxo Completo: Primeiro Bootstrap

### Pré-requisitos
- Brave Browser instalado no macOS host
- Dev Container em execução
- `scripts/mcp-playwright-prepare.sh` já executado (Chromium instalado)

### Passo 1 — Iniciar Brave com remote debugging (macOS host)

```bash
# Terminal do macOS (fora do container)
/Applications/Brave\ Browser.app/Contents/MacOS/Brave\ Browser \
  --remote-debugging-port=9222 \
  --user-data-dir=/tmp/brave-mcp-profile
```

> ⚠️ Usar `--user-data-dir` dedicado para não interferir no perfil principal do Brave.
> Firefox e Safari NÃO funcionam — CDP só é suportado por browsers Chromium-based.

### Passo 2 — Executar bootstrap no container

```bash
# Terminal do Dev Container
./scripts/mcp-foursquare-auth-bootstrap.sh
```

O script vai:
1. Verificar se o Brave está acessível em `http://host.docker.internal:9222`
2. Imprimir a URL de login para abrir no Brave
3. Aguardar o usuário fazer login e pressionar ENTER
4. Conectar ao Brave via WebSocket CDP
5. Extrair `storageState` (cookies + localStorage)
6. Salvar em `data/mcp/playwright/foursquare.storage-state.json` com `chmod 600`

### Passo 3 — Verificar

```bash
node /var/www/html/scripts/get-foursquare-token.js
```

Saída esperada:
```json
{
  "oauth_token": "A5H532HX...",
  "phpsessid": "ee545e72...",
  "authenticated": true,
  "expires": "2026-10-10T03:37:47.659Z"
}
```

---

## Como o Agente Deve se Autenticar

O `--storage-state` do Playwright MCP **não** carrega cookies automaticamente ao navegar.
O agente deve **injetar o token manualmente** via JavaScript após cada `browser_navigate`.

### Padrão de uso pelo agente

```javascript
// 1. Navegar para uma página qualquer do domínio primeiro
await page.goto('https://localhost/4sqmet/index.php');

// 2. Injetar o token (obtido via get-foursquare-token.js)
await page.evaluate(() => {
  document.cookie = 'oauth_token=<TOKEN>; path=/; secure; samesite=lax';
});

// 3. Navegar para a página autenticada
await page.goto('https://localhost/4sqmet/main.php');
// → Página carrega autenticada (Status: Conectado)
```

### Instrução para o agente solicitar token ao usuário

Se o arquivo `foursquare.storage-state.json` não existir ou o token estiver expirado, o agente deve:

1. **Verificar existência:**
   ```bash
   test -f /var/www/html/data/mcp/playwright/foursquare.storage-state.json && echo "existe" || echo "ausente"
   ```

2. **Se ausente — pedir ao usuário que execute o bootstrap:**
   > "O token de autenticação Foursquare não está disponível. Por favor execute:
   > ```bash
   > ./scripts/mcp-foursquare-auth-bootstrap.sh
   > ```
   > Você precisará ter o Brave aberto com `--remote-debugging-port=9222`. Após concluir, avise para continuar."

3. **Se o usuário fornecer o token diretamente** (ex: colar o valor), usar:
   ```bash
   # Criar storage state mínimo com token fornecido pelo usuário
   node -e "
   const token = 'TOKEN_FORNECIDO_PELO_USUARIO';
   const data = {
     cookies: [{
       name: 'oauth_token', value: token,
       domain: 'localhost', path: '/',
       expires: Math.floor(Date.now()/1000) + 15552000,
       httpOnly: false, secure: true, sameSite: 'Lax'
     }],
     origins: []
   };
   require('fs').writeFileSync(
     '/var/www/html/data/mcp/playwright/foursquare.storage-state.json',
     JSON.stringify(data, null, 2)
   );
   require('fs').chmodSync('/var/www/html/data/mcp/playwright/foursquare.storage-state.json', 0o600);
   console.log('Token salvo com sucesso');
   "
   ```

---

## Renovar Token Expirado

```bash
# Limpar token atual
./scripts/mcp-foursquare-auth-clear.sh

# Re-executar bootstrap (Brave precisa estar aberto com --remote-debugging-port=9222)
./scripts/mcp-foursquare-auth-bootstrap.sh
```

---

## Troubleshooting

### "Host header is specified and is not an IP address or localhost"
**Causa:** curl enviando `Host: host.docker.internal` para o Brave.
**Solução:** O bootstrap script já inclui `-H 'Host: localhost'` nos curls. Se aparecer novamente, verificar versão do script.

### "webSocketDebuggerUrl não encontrada"
**Causa:** Brave iniciado sem `--remote-debugging-port=9222`, ou com o flag errado.
**Solução:** Fechar o Brave completamente e reiniciar com o comando do Passo 1.

### "Nenhum contexto disponível no browser remoto"
**Causa:** Brave aberto mas sem abas/janelas.
**Solução:** Abrir uma aba qualquer no Brave antes de pressionar ENTER.

### Token capturado mas `session_status.php` retorna `authenticated: false`
**Causa:** O `PHPSESSID` no storage state aponta para uma sessão PHP que já expirou no servidor.
**Solução:** Isso é esperado e normal — use apenas o `oauth_token` (não o PHPSESSID). O PHP recria a sessão e autentica via cookie `oauth_token` automaticamente.

### `Cannot find module 'playwright'`
**Causa:** `npx -p playwright node` não disponibiliza o módulo para `require()`.
**Solução:** O script instala playwright via `npm install` em diretório temporário. Verificar se o script está na versão correta (commit `3a40cd6`+).

### Brave abre mas `https://localhost/4sqmet/index.php` dá erro SSL
**Causa:** O certificado self-signed do Dev Container não é confiado pelo Brave.
**Solução:** No Brave, clicar em "Advanced" → "Proceed to localhost (unsafe)" na primeira vez.

---

## Configuração mcp.json (estado atual)

```json
{
  "servers": {
    "playwright": {
      "type": "stdio",
      "command": "npx",
      "args": [
        "@playwright/mcp@latest",
        "--headless",
        "--ignore-https-errors",
        "--output-dir", "/var/www/html/debug/mcp-artifacts",
        "--output-mode", "file",
        "--viewport-size", "1440x900"
      ],
      "env": {
        "PLAYWRIGHT_MCP_CONSOLE_LEVEL": "error",
        "PLAYWRIGHT_BROWSERS_PATH": "/var/www/html/data/mcp/playwright/browsers"
      }
    },
    "playwright-headed": {
      "type": "stdio",
      "command": "/bin/bash",
      "args": ["/var/www/html/scripts/mcp-headed-start.sh"],
      "env": {
        "PLAYWRIGHT_MCP_CONSOLE_LEVEL": "error"
      }
    }
  }
}
```

**Por que o wrapper `mcp-headed-start.sh`?**
O Brave retorna `ws://localhost/devtools/...` (sem porta) no CDP. O MCP tentaria conectar na porta 80 (Apache). O wrapper busca essa URL via `curl`, substitui `ws://localhost/` por `ws://host.docker.internal:9222/` e passa o valor correto ao `--cdp-endpoint`.

> ⚠️ **NÃO usar `--save-session` junto com `--storage-state`** — sobrescreve o storage state com sessão vazia ao iniciar.
> ⚠️ **Usar caminhos absolutos** — `${workspaceFolder}` não é resolvido pelo processo Node do MCP server.

---

## Persistência do Chromium

O Chromium é instalado em `data/mcp/playwright/browsers/` (no volume do workspace = persistente entre rebuilds).

Verificar se está instalado:
```bash
find /var/www/html/data/mcp/playwright/browsers -maxdepth 1 -type d -name 'chromium-*' | head -3
```

Reinstalar se necessário:
```bash
./scripts/mcp-playwright-prepare.sh
```
### Dependências de sistema perdidas após rebuild
O binário do Chromium persiste no workspace, mas as libs de SO (`libglib-2.0.so.0`, `libnss3`, etc.)
são parte da camada do container e são apagadas em cada rebuild.

```bash
PLAYWRIGHT_BROWSERS_PATH=/var/www/html/data/mcp/playwright/browsers npx playwright install-deps chromium
```

> **Fix permanente:** `scripts/devcontainer-setup.sh` executa isso automaticamente no `postCreateCommand`
> (adicionado no commit `cad7301`). Após qualquer rebuild, as deps são reinstaladas sem intervenção manual.