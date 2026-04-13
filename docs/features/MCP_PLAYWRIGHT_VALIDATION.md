# MCP Playwright - Validation Runbook

## Objetivo

Padronizar validações de UI/UX e debug funcional via MCP no Dev Container.

## Pré-requisitos

1. Projeto aberto no Dev Container
2. App disponível em `https://localhost/4sqmet/`
3. Configuração MCP no workspace: `.vscode/mcp.json`
4. Trust do servidor MCP aprovado no VS Code
5. Chromium preparado em cache persistente:
   - `./scripts/mcp-playwright-prepare.sh`
6. Sessão autenticada (quando precisar fluxos logados):
   - `./scripts/mcp-foursquare-auth-bootstrap.sh`

## Smoke Check (obrigatório)

1. Abrir `https://localhost/4sqmet/`
2. Confirmar carregamento sem erro SSL
3. Navegar para `https://localhost/4sqmet/debug/`
4. Abrir Session Test Manager
5. Capturar screenshot em cada etapa

## Autenticação segura para fluxos logados

### Como funciona

- O login é feito manualmente pelo usuário no **Brave** (host macOS), conectado via CDP.
- Nenhuma senha é gravada no repositório.
- O script `mcp-foursquare-auth-bootstrap.sh` extrai os cookies via `chromium.connectOverCDP()` e salva em:
  - `data/mcp/playwright/foursquare.storage-state.json`
- O arquivo é local, ignorado no git e salvo com permissão `600`.
- O agente extrai o `oauth_token` via `scripts/get-foursquare-token.js` e o injeta via `document.cookie` após cada `browser_navigate`.

> ⚠️ O `--storage-state` do Playwright MCP **não** carrega cookies automaticamente — a injeção via JavaScript é necessária.

### Criar sessão autenticada

```bash
./scripts/mcp-foursquare-auth-bootstrap.sh
```

#### macOS + Dev Container (obrigatório para modo visual)

Como o container não tem X server, o login visual precisa ocorrer no browser do host via CDP.

1. Inicie o Brave no macOS com remote debugging e perfil dedicado:

```bash
/Applications/Brave\ Browser.app/Contents/MacOS/Brave\ Browser \
   --remote-debugging-port=9222 \
   --user-data-dir=/tmp/brave-mcp-profile
```

Alternativa com Google Chrome:

```bash
/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome \
   --remote-debugging-port=9222 \
   --user-data-dir=/tmp/chrome-mcp-profile
```

2. No terminal do Dev Container, execute:

```bash
./scripts/mcp-foursquare-auth-bootstrap.sh
```

3. Faça login manualmente no Foursquare no Chrome do host e pressione ENTER no terminal.

Opcional: se necessário, altere endpoint CDP:

```bash
BROWSER_CDP_ENDPOINT="http://host.docker.internal:9222" ./scripts/mcp-foursquare-auth-bootstrap.sh
```

### Revogar sessão autenticada

```bash
./scripts/mcp-foursquare-auth-clear.sh
```

### Observação de segurança

Use esse mecanismo apenas no seu ambiente local de desenvolvimento. Se suspeitar de comprometimento da sessão, remova o storage state e refaça login.

## Fluxos de regressão recomendados

### 1) Sessão/Auth

- Criar sessão de teste em `debug/session_test_manager.php?action=create`
- Validar sessão em `debug/debug_session.php?mode=validate`
- Verificar comportamento visual e mensagens da interface

### 2) Edição em massa

- Acessar fluxo principal até `edit.php`
- Confirmar renderização dos campos Dojo
- Alterar alguns campos e validar sinalização de alteração

### 3) Google Maps

- Validar presença do mapa e marcadores
- Validar redimensionamento da interface
- Validar estabilidade ao navegar entre telas

### 4) Erros e retry

- Exercitar cenário com erro de API (quando possível)
- Verificar feedback visual e tentativa de retry

## Evidências

Salvar artefatos em `debug/mcp-artifacts/`:

- screenshots
- snapshots
- logs de console/network (quando aplicável)

## Critério de aprovação

A execução é aprovada quando:

1. Todos os smoke checks passam
2. Fluxos críticos (sessão, edição, mapas) estão estáveis
3. Não há regressão visual evidente
4. Evidências foram geradas e anexadas ao PR/revisão

## Nota sobre Chrome DevTools MCP

Para diagnóstico avançado (trace, Lighthouse, memória), usar Chrome DevTools MCP em fase complementar, preferencialmente após upgrade do Node do Dev Container para 20.19+.
