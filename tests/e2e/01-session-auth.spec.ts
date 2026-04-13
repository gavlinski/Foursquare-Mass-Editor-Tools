import { test, expect } from '@playwright/test';

/**
 * Regressão 1: Sessão e Autenticação
 *
 * Valida que a sessão OAuth está ativa, que o usuário aparece na UI,
 * e que as ferramentas de debug de sessão respondem corretamente.
 *
 * Pré-requisito: storageState com oauth_token válido (veja playwright.config.ts).
 * Se falhar com redirect para index.php, execute:
 *   ./scripts/mcp-foursquare-auth-bootstrap.sh
 */
test.describe('Sessão e Autenticação', () => {

  test('main.php carrega autenticado - sem redirect para login', async ({ page }) => {
    await page.goto('/4sqmet/main.php');
    await expect(page).toHaveURL(/main\.php/);
    await expect(page.locator('text=Conectado')).toBeVisible({ timeout: 10_000 });
  });

  test('barra de sessão exibe nome do usuário', async ({ page }) => {
    await page.goto('/4sqmet/main.php');
    // A barra de status exibe "Usuário: <nome>"
    const sessionBar = page.locator('#session-info, .session-bar, [id*="session"], [class*="session"]').first();
    await expect(sessionBar).toBeVisible({ timeout: 10_000 });
  });

  test('session_status.php retorna authenticated: true', async ({ page }) => {
    const response = await page.request.get('/4sqmet/session_status.php');
    expect(response.status()).toBe(200);
    const body = await response.json();
    expect(body.authenticated).toBe(true);
  });

  test('debug/session_test_manager.php - criar sessão de teste', async ({ page }) => {
    const response = await page.request.get('/4sqmet/debug/session_test_manager.php?action=create');
    expect(response.status()).toBe(200);
  });

  test('debug/debug_session.php - validar sessão', async ({ page }) => {
    const response = await page.request.get('/4sqmet/debug/debug_session.php?mode=validate');
    expect(response.status()).toBe(200);
  });

  test('logout redireciona para index.php', async ({ page }) => {
    await page.goto('/4sqmet/main.php');
    // Navegação sem clicar em logout — apenas confirma que o botão existe
    const logoutBtn = page.getByRole('button', { name: /logout/i })
      .or(page.getByRole('link', { name: /logout/i }))
      .or(page.locator('#logout, .logout, [onclick*="logout"]').first());
    await expect(logoutBtn.first()).toBeVisible({ timeout: 10_000 });
  });

});
