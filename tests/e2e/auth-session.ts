import { test, type Page } from '@playwright/test';

export async function skipIfUnauthenticated(page: Page): Promise<void> {
  const response = await page.request.get('/4sqmet/session_status.php');
  if (!response.ok()) {
    throw new Error(`session_status.php retornou HTTP ${response.status()}`);
  }

  const session = await response.json();
  if (session.authenticated !== true) {
    const message = 'Requer token Foursquare válido; gere/renove o storage state para executar este teste.';
    if (process.env.CI) {
      throw new Error(`CI exige uma sessão Foursquare válida. ${message}`);
    }
    test.skip(true, message);
  }
}