# Playwright Regression Tests Skill

## Name
Playwright — Geração e Execução de Testes de Regressão

## Description
Guia completo para criar, executar e manter os testes de regressão automatizados com `@playwright/test`.
Cobre os 4 fluxos de regressão do runbook, configuração de autenticação, e o fluxo de
"usar MCP para gerar uma vez, executar sem MCP no futuro".

## When to use
Carregue esta skill quando:
- Precisar criar ou expandir testes de regressão em `tests/e2e/`
- Debugar falhas nos testes existentes
- Adicionar um novo fluxo funcional aos testes
- Configurar execução de regressão no CI/CD
- Usar o MCP (headed) para explorar e depois codificar novos testes

---

## Filosofia: MCP para Explorar → @playwright/test para Automatizar

```
Primeira vez (com MCP headed):           Execuções futuras (sem MCP):
──────────────────────────────           ──────────────────────────────
1. Usar playwright-headed para           1. npx playwright test
   navegar fluxo visualmente             2. Resultado em playwright-report/
2. Observar seletores e comportamento    3. Em CI: npm test (script no package.json)
3. Codificar em tests/e2e/*.spec.ts
4. Validar com npx playwright test
```

**Nunca é necessário o MCP para executar os testes existentes.** O MCP é útil apenas na fase
de exploração para entender novos fluxos antes de codificá-los.

---

## Estrutura de Arquivos

```
playwright.config.ts          ← Configuração central (baseURL, storageState, browsers)
tests/
  e2e/
    01-session-auth.spec.ts   ← Regressão 1: Sessão e Autenticação
    02-mass-editing.spec.ts   ← Regressão 2: Edição em Massa
    03-google-maps.spec.ts    ← Regressão 3: Google Maps
    04-errors-retry.spec.ts   ← Regressão 4: Erros e Retry
test-results/                 ← Artefatos de falha (gitignored)
playwright-report/            ← Relatório HTML (gitignored)
```

---

## Comandos Essenciais

```bash
# Executar todos os testes de regressão
npm test
# ou
npx playwright test

# Executar apenas 1 arquivo (smoke check rápido)
npm run test:smoke
# ou
npx playwright test tests/e2e/01-session-auth.spec.ts

# Gerar relatório HTML após a execução (inclui vídeos de falhas)
npm run test:report
# ou
npx playwright show-report

# Feedback visual em tempo real — RECOMENDADO para debug
# Inicia web UI na porta 41428; VS Code detecta e abre no host browser
npm run test:ui

# Executar com headed chromium (browser no display virtual Xvfb)
# Browser é invisível, mas testes passam e vídeos de falhas são gravados
npm run test:headed
```

### Modos de feedback visual no dev container

O dev container não tem display físico. Há dois modos de obter feedback visual:

| Modo | Comando | Quando usar |
|------|---------|------------|
| **UI mode** (recomendado) | `npm run test:ui` | Debug interativo — abre web UI no host browser via port forwarding |
| **Headed + vídeo** | `npm run test:headed` | Confirmar que testes passam headed; vídeos de falhas gravados automaticamente |
| **Headless** | `npm test` | CI, execução rápida sem feedback visual |

**VS Code "Show browser"**: Funciona após rebuild do container (Xvfb inicia automaticamente
via `postStartCommand`). Na sessão atual, inicie manualmente se necessário:
```bash
pgrep Xvfb >/dev/null || (nohup Xvfb :99 -screen 0 1280x720x24 >/dev/null 2>&1 &)
export DISPLAY=:99
```

---

## Configuração de Autenticação nos Testes

Em `@playwright/test`, o `storageState` **SIM** carrega cookies automaticamente — ao contrário do
Playwright MCP. Isso está configurado em `playwright.config.ts`:

```typescript
use: {
  storageState: 'data/mcp/playwright/foursquare.storage-state.json',
}
```

O arquivo de storage state é gerado por `./scripts/mcp-foursquare-auth-bootstrap.sh`.

### Pré-requisito antes de rodar os testes

```bash
# Verificar se o token ainda é válido
node /var/www/html/scripts/get-foursquare-token.js
# → Se "authenticated: true", pode rodar os testes

# Se token ausente ou expirado:
./scripts/mcp-foursquare-auth-bootstrap.sh
```

### Teste sem autenticação (contexto isolado)

Para testar comportamento sem sessão, crie um contexto novo sem storageState:

```typescript
test('redireciona para login sem token', async ({ browser }) => {
  const context = await browser.newContext({ ignoreHTTPSErrors: true }); // sem storageState
  const page = await context.newPage();
  await page.goto('https://localhost/4sqmet/main.php');
  await expect(page).toHaveURL(/index\.php/);
  await context.close();
});
```

---

## Os 4 Fluxos de Regressão

### Fluxo 1: Sessão e Autenticação (`01-session-auth.spec.ts`)

**O que valida:**
- `main.php` carrega autenticado (sem redirect)
- Barra de sessão exibe nome do usuário
- `session_status.php` retorna `authenticated: true`
- Ferramentas de debug de sessão respondem 200
- Botão Logout está visível

**Como rodar:**
```bash
npx playwright test tests/e2e/01-session-auth.spec.ts
```

**Se falhar com redirect para `index.php`:**
```bash
# Token expirado — refazer bootstrap
./scripts/mcp-foursquare-auth-bootstrap.sh
```

---

### Fluxo 2: Edição em Massa (`02-mass-editing.spec.ts`)

**O que valida:**
- `main.php` exibe formulário de entrada de IDs/URLs
- Checkboxes de seleção de campos presentes
- `edit.php` renderiza campos Dojo (requer `TEST_VENUE_ID`)
- Sinalização visual de campo alterado

**ID de venue:** os testes usam `4ec42a0a9a522f580b42dbeb` como fallback fixo.
Para substituir: `export TEST_VENUE_ID="outro-id"` antes de rodar.

> ⚠️ Os testes de `edit.php` fazem `fill()` no campo nome mas **não salvam** (sem submit).
> É seguro usar uma venue real de produção.

---

### Fluxo 3: Google Maps (`03-google-maps.spec.ts`)

**O que valida:**
- Nenhum erro crítico de Maps API no console (InvalidKeyMapError, MissingKeyMapError)
- Container do mapa presente no DOM
- `debug/test_google_maps_config.php` responde 200
- `debug/test_integration_markers.html` carrega sem TypeError/ReferenceError
- Estabilidade ao navegar entre páginas

**Se falhar com erros de API Key:**
- Verificar `includes/google_maps_config.php`
- Verificar variável `GOOGLE_MAPS_API_KEY` no `.env`

---

### Fluxo 4: Erros e Retry (`04-errors-retry.spec.ts`)

**O que valida:**
- `session_status.php` com cookie falso retorna `authenticated: false`
- `main.php` sem token redireciona para `index.php`
- `load.php` com ID inválido não gera erro 500
- `version.php` retorna JSON válido com `Accept: application/json`
- (Skipped sem `TEST_VENUE_ID`) Interceptação de 401 da API

---

## Adicionando um Novo Teste

### Padrão de arquivo

```typescript
import { test, expect } from '@playwright/test';

test.describe('Nome do Fluxo', () => {

  test('descrição do que é validado', async ({ page }) => {
    await page.goto('/4sqmet/pagina.php');
    await expect(page.locator('seletor')).toBeVisible({ timeout: 10_000 });
  });

});
```

### Regras de seletor

```typescript
// ✅ Preferir: role-based (mais robusto)
page.getByRole('button', { name: /salvar/i })
page.getByRole('link', { name: /continuar/i })

// ✅ Aceitável: text-based
page.locator('text=Conectado')

// ✅ Para campos Dojo
page.locator('[dojoType], [data-dojo-type], .dijitTextBox')

// ❌ Evitar: CSS classes específicas de estilo (mudam com refactoring)
page.locator('.btn-primary-blue-special')
```

### Usando MCP headed para explorar antes de codificar

1. Com `playwright-headed` ativo, navegar o fluxo que quer testar
2. Usar `browser_snapshot` para capturar a estrutura ARIA da página
3. Identificar os seletores corretos no snapshot
4. Codificar o teste em `tests/e2e/XX-nome.spec.ts`
5. Rodar `npx playwright test` para validar sem MCP

---

## Workflow: MCP → Script de Teste

```
Com MCP headed (exploração):                Sem MCP (execução):
────────────────────────────                ─────────────────────
mcp_playwright-he_browser_navigate          npx playwright test
mcp_playwright-he_browser_snapshot          # usa Chromium headless_shell
mcp_playwright-he_browser_take_screenshot   # storageState auto-carregado
  → Identificar seletores                   # 100% autônomo, sem Brave
  → Observar comportamento
  → Codificar spec.ts
```

---

## Chromium para @playwright/test

O `@playwright/test` usa `chromium_headless_shell` (diferente do Chrome completo usado pelo MCP).
Ambos estão no caminho persistente do workspace:

```
data/mcp/playwright/browsers/
  chromium-1217/            ← Chrome completo (usado pelo MCP headless)
  chromium_headless_shell-1217/  ← Headless shell (usado pelo @playwright/test)
  mcp-chrome-7cbeefd/      ← Chrome usado pelo playwright-headed via CDP
```

Configurado em `playwright.config.ts`:
```typescript
process.env.PLAYWRIGHT_BROWSERS_PATH = '/var/www/html/data/mcp/playwright/browsers';
```

**Se `chromium_headless_shell` não existir (após rebuild):**
```bash
PLAYWRIGHT_BROWSERS_PATH=/var/www/html/data/mcp/playwright/browsers npx playwright install chromium
```

---

## Integração CI/CD

Os testes rodam no job `e2e-tests` do workflow `.github/workflows/deploy.yml`.

### Arquitetura do job

```
GitHub Actions runner (ubuntu-latest)
  ├─ Docker container: aplicacão PHP/Apache (porta 443)
  └─ npx playwright test (conecta em https://localhost)
```

### Secrets necessárias

| Secret | Descrição | Onde configurar |
|--------|-----------|----------------|
| `FOURSQUARE_TEST_TOKEN` | OAuth token para autenticacão dos testes | GitHub → Settings → Secrets and variables → Actions |
| `FOURSQUARE_CLIENT_KEY` | Chave do app Foursquare | Já existe |
| `FOURSQUARE_CLIENT_SECRET` | Secret do app Foursquare | Já existe |
| `GOOGLE_MAPS_API_KEY` | API key do Maps | Já existe |

### `PLAYWRIGHT_BROWSERS_PATH` em CI vs Dev Container

O `playwright.config.ts` usa a seguinte lógica:
```typescript
// CI=true é definido automaticamente pelo GitHub Actions
if (!process.env.CI) {
  process.env.PLAYWRIGHT_BROWSERS_PATH = '/var/www/html/data/mcp/playwright/browsers';
}
// CI: usa path padrão do runner (~/.cache/ms-playwright)
// Dev container: usa volume persistente do workspace
```

### Criar a secret `FOURSQUARE_TEST_TOKEN`

```bash
# No dev container, obter o token atual
node /var/www/html/scripts/get-foursquare-token.js
# Copiar o valor de "oauth_token" e adicionar como secret no GitHub
```

O job cria o storage state automaticamente a partir da secret:
```yaml
- name: Criar storage state de autenticacão Playwright
  run: |
    mkdir -p data/mcp/playwright
    node -e "
    const token = process.env.FOURSQUARE_TEST_TOKEN;
    const data = { cookies: [{ name: 'oauth_token', value: token,
      domain: 'localhost', path: '/', expires: 9999999999,
      httpOnly: false, secure: true, sameSite: 'Lax' }], origins: [] };
    require('fs').writeFileSync('data/mcp/playwright/foursquare.storage-state.json', JSON.stringify(data));
    "
  env:
    FOURSQUARE_TEST_TOKEN: ${{ secrets.FOURSQUARE_TEST_TOKEN }}
```

---

## Troubleshooting

### `Error: browserType.launch: Executable doesn't exist`
```bash
PLAYWRIGHT_BROWSERS_PATH=/var/www/html/data/mcp/playwright/browsers npx playwright install chromium
```

### `storageState file not found`
```bash
./scripts/mcp-foursquare-auth-bootstrap.sh
```

### Teste falha com `authenticated: false` mas token parece válido
- Verificar se `data/mcp/playwright/foursquare.storage-state.json` tem `domain: "localhost"` (não `"127.0.0.1"`)
- Verificar se `baseURL` em `playwright.config.ts` bate com o domínio do cookie

### Testes passam localmente mas falham no CI
- Verificar se a secret `FOURSQUARE_TEST_TOKEN` está configurada no GitHub
- `playwright.config.ts` não sobrescreve `PLAYWRIGHT_BROWSERS_PATH` quando `CI=true` — o runner usa o path padrão instalado por `npx playwright install chromium --with-deps`

### Seletor Dojo não encontra elemento ou elemento está hidden
Ver seção **Armadilhas Dojo** abaixo. Resumo:
- `input[type=text]` → usar ID específico (`#textarea_ids`)
- `.dijitTextBox` para fill() → usar `input.dijitInputInner`
- `button:has-text()` → usar `getByRole('button', { name: /texto/i })`
- `input.dijitInputInner[name="nome"]` → **NUNCA funciona**: Dojo não preserva `name` em `dijitInputInner`

### `edit.php` redireciona para `index.php` / `main.php` no CI
`edit.php` exige `$_SESSION["file"] != null`, que só é definido pelo fluxo via `load.php`.
Navegação direta para `edit.php` falha em CI (sessão limpa). **Solução:** sempre usar o fluxo completo:
```typescript
// ❌ ERRO — falha em CI (sessão limpa, redirect para main.php)
await page.goto('/4sqmet/edit.php?ids=xxx');

// ✅ CORRETO — fluxo completo via load.php
await page.goto('/4sqmet/main.php');
await page.locator('#textarea_ids').fill(VENUE_ID);
await page.locator('label[for="nome3"]').click();
await page.getByRole('button', { name: /Continuar/i }).filter({ visible: true }).click();
await page.waitForURL('**/edit.php**', { timeout: 30_000 });
```

### AccordionContainer — múltiplos botões "Continuar" (um por pane)
`main.php` tem 5 panes de accordion, cada um com um botão Continuar.
- ❌ `.getByRole('button', { name: /Continuar/i }).first()` → pode pegar o de um pane colapsado
- ✅ `.getByRole('button', { name: /Continuar/i }).filter({ visible: true }).click()` → pega o do pane expandido

### `input.dijitInputInner.first()` — cuidado em qual página está
- Em **`main.php`**: o primeiro `dijitInputInner` pode ser de um pane accordion colapsado (ex.: campo `#pagina` do pane 3), resultando em timeout ou fill em campo hidden.
- Em **`edit.php`** (via fluxo correto): o primeiro `dijitInputInner` é sempre o campo `name` (primeiro campo renderizado).

### Dojo CheckBox — label click vs. direct check
Clicar na `<label>` associada é mais confiável que `.check()` em inputs Dojo escondidos:
```typescript
// ✅ Confiável — usa associação label→input do browser
await page.locator('label[for="nome3"]').click();

// ⚠️ Menos confiável — Dojo pode substituir o input original
await page.locator('#nome3').check();
```

### `libglib-2.0.so.0: cannot open shared object file` após rebuild
O binário do Chromium persiste no volume do workspace, mas as libs de SO são perdidas no rebuild.
```bash
PLAYWRIGHT_BROWSERS_PATH=/var/www/html/data/mcp/playwright/browsers npx playwright install-deps chromium
```
> Fix permanente: `scripts/devcontainer-setup.sh` já executa isso automaticamente.
