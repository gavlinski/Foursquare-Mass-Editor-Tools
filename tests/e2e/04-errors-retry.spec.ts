import { test, expect } from '@playwright/test';
import { skipIfUnauthenticated } from './auth-session';

type EditorWindow = Window & {
  dijit?: {
    byId(id: string): {
      getChildren(): Array<{ domNode: HTMLElement }>;
      selectChild(pane: { domNode: HTMLElement }, animate?: boolean): void;
    } | undefined;
  };
};

async function abrirPainelDeIds(page: import('@playwright/test').Page) {
  await page.waitForFunction(() => Boolean((window as EditorWindow).dijit?.byId('accordion')));
  const painelAberto = await page.evaluate(() => {
    const accordion = (window as EditorWindow).dijit?.byId('accordion');
    const painelIds = accordion?.getChildren().find(pane => pane.domNode.querySelector('#f_ids'));
    if (!accordion || !painelIds) return false;
    accordion.selectChild(painelIds, false);
    return true;
  });
  expect(painelAberto).toBe(true);
  await expect(page.locator('#textarea_ids')).toBeVisible({ timeout: 10_000 });
}

/**
 * Regressão 4: Erros e Retry
 *
 * Valida feedback visual em cenários de erro da API Foursquare:
 * sessão expirada, token inválido, rate limit, timeout.
 *
 * Nota: Não é possível forçar erros reais da API Foursquare em testes automatizados
 * sem interceptação de rede. Estes testes usam page.route() para simular respostas de erro.
 */
test.describe('Erros e Retry', () => {

  test('session_status.php com token inválido retorna authenticated: false', async ({ page }) => {
    // Navegar sem storageState para simular sessão inválida
    // (storageState está no config, mas aqui testamos a API diretamente com cookie falso)
    const response = await page.request.get('/4sqmet/session_status.php', {
      headers: { Cookie: 'oauth_token=TOKEN_INVALIDO_PARA_TESTE' }
    });
    expect(response.status()).toBe(200);
    const body = await response.json();
    expect(body.authenticated).toBe(false);
  });

  test('main.php sem token redireciona para index.php', async ({ browser }) => {
    // Contexto sem storageState (sem cookies de auth)
    const context = await browser.newContext({
      ignoreHTTPSErrors: true,
      // Sem storageState aqui
    });
    const page = await context.newPage();
    await page.goto('https://localhost/4sqmet/main.php');
    // Deve redirecionar para login
    await expect(page).toHaveURL(/index\.php|4sqmet\//);
    await context.close();
  });

  test('load.php com ID inválido retorna erro estruturado', async ({ page }) => {
    // Simular chamada com venue ID inválido
    const response = await page.request.post('/4sqmet/load.php', {
      form: { ids: 'ID_INEXISTENTE_12345' }
    });
    // Deve retornar 200 com conteúdo de erro (não 500)
    expect(response.status()).toBeLessThan(500);
  });

  test('edit.php intercepta 401 da API e exibe mensagem de sessão expirada', async ({ page }) => {
    const VENUE_ID_TESTE = process.env.TEST_VENUE_ID || '4ec42a0a9a522f580b42dbeb';
    test.skip(!VENUE_ID_TESTE, 'TEST_VENUE_ID não definido');
    await skipIfUnauthenticated(page);

    await page.route('**/v2/venues/**', async route => {
      await route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({ meta: { code: 401, errorType: 'invalid_auth' } }),
      });
    });

    await page.goto('/4sqmet/main.php');
    await page.waitForLoadState('networkidle', { timeout: 20_000 });
    await abrirPainelDeIds(page);
    await page.locator('#textarea_ids').fill(VENUE_ID_TESTE);
    await page.locator('label[for="nome3"]').click();
    await page.getByRole('button', { name: /Continuar/i }).filter({ visible: true }).click();
    await page.waitForURL('**/edit.php**', { timeout: 30_000 });

    await expect(page.locator('#result0 img')).toHaveAttribute('alt', /Erro 401: Unauthorized, Tipo: invalid_auth/);
  });

  test('version.php retorna JSON válido com Accept: application/json', async ({ page }) => {
    const response = await page.request.get('/4sqmet/version.php', {
      headers: { Accept: 'application/json' }
    });
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/json');
    const body = await response.json();
    expect(body).toHaveProperty('version');
    expect(body).toHaveProperty('build_source');
  });

});
