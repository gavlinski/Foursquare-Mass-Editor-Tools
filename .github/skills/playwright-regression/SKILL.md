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

# Gerar relatório HTML após a execução
npm run test:report
# ou
npx playwright show-report

# Executar com headed chromium local (debug visual — NÃO é o Brave via CDP)
npm run test:headed
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

**Variável necessária para testes com venue real:**
```bash
# Definir ID de uma venue que pode ser editada
export TEST_VENUE_ID="4b2d7d5cf964a520d1d724e3"
npx playwright test tests/e2e/02-mass-editing.spec.ts
```

**Sem `TEST_VENUE_ID`:** os testes que dependem de `edit.php` são automaticamente skipped.

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

Para rodar regressão no GitHub Actions, o token de auth não estará disponível.
Opções:

1. **Testes de API apenas** (sem storageState) — omitir o `storageState` do config e testar apenas endpoints públicos
2. **Token como secret** — salvar o `oauth_token` como GitHub Secret e reconstruir o storage state no CI:
   ```yaml
   - name: Criar storage state para testes
     run: |
       node -e "
       const data = { cookies: [{ name: 'oauth_token', value: process.env.FOURSQUARE_TOKEN,
         domain: 'localhost', path: '/', expires: 9999999999,
         httpOnly: false, secure: true, sameSite: 'Lax' }], origins: [] };
       require('fs').writeFileSync('data/mcp/playwright/foursquare.storage-state.json', JSON.stringify(data));
       "
     env:
       FOURSQUARE_TOKEN: ${{ secrets.FOURSQUARE_TEST_TOKEN }}
   
   - name: Executar testes de regressão
     run: npm test
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
- O `PLAYWRIGHT_BROWSERS_PATH` pode não estar configurado no CI
- Adicionar `PLAYWRIGHT_BROWSERS_PATH: /root/.cache/ms-playwright` no env do workflow para usar o cache padrão do CI
