import { test, type Page } from '@playwright/test';

export async function skipIfUnauthenticated(page: Page): Promise<void> {
  const response = await page.request.get('/4sqmet/session_status.php');
  if (!response.ok()) {
    throw new Error(`session_status.php retornou HTTP ${response.status()}`);
  }

  const session = await response.json();
  test.skip(session.authenticated !== true, 'Requer token Foursquare válido; gere/renove o storage state para executar este teste.');
}