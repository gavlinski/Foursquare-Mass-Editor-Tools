import { test, type Page } from '@playwright/test';

export async function skipIfUnauthenticated(page: Page): Promise<void> {
  const response = await page.request.get('/4sqmet/session_status.php');
  if (!response.ok()) {
    throw new Error(`session_status.php retornou HTTP ${response.status()}`);
  }

  const contentType = response.headers()['content-type'] || '';
  const responseBody = await response.text();
  if (!contentType.toLowerCase().includes('application/json')) {
    throw new Error(
      `session_status.php deveria retornar application/json, recebeu "${contentType}": ${responseBody.slice(0, 160)}`
    );
  }

  let session: { authenticated?: boolean };
  try {
    session = JSON.parse(responseBody);
  } catch {
    throw new Error(`session_status.php retornou JSON inválido: ${responseBody.slice(0, 160)}`);
  }

  if (session.authenticated !== true) {
    const message = 'Requer token Foursquare válido; gere/renove o storage state para executar este teste.';
    if (process.env.CI) {
      throw new Error(`CI exige uma sessão Foursquare válida. ${message}`);
    }
    test.skip(true, message);
  }
}