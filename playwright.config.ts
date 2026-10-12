import { defineConfig, devices } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

/**
 * Playwright configuration for regression tests.
 * Run: npx playwright test
 * Report: npx playwright show-report
 *
 * Auth: storageState reuses the oauth_token captured by mcp-foursquare-auth-bootstrap.sh.
 * Unlike Playwright MCP, @playwright/test DOES auto-load storageState cookies.
 */
// Dev container: reutilizar Chromium persistente no volume do workspace
// CI (GitHub Actions): CI=true é definido automaticamente — usar path padrão do runner
if (!process.env.CI) {
  process.env.PLAYWRIGHT_BROWSERS_PATH = '/var/www/html/data/mcp/playwright/browsers';
}

const projectRoot = process.cwd();
const configuredStorageState = process.env.PLAYWRIGHT_STORAGE_STATE;
const storageStatePath = configuredStorageState
  ? path.resolve(process.cwd(), configuredStorageState)
  : path.join(projectRoot, 'data/mcp/playwright/foursquare.storage-state.json');

function getUsableStorageState() {
  try {
    const state = JSON.parse(fs.readFileSync(storageStatePath, 'utf8'));
    const token = (state.cookies || []).find(cookie =>
      cookie.name === 'oauth_token' && cookie.domain === 'localhost' && cookie.value
    );

    if (!token) return undefined;
    if (Number.isFinite(token.expires) && token.expires !== -1 && token.expires <= Date.now() / 1000) {
      return undefined;
    }

    return storageStatePath;
  } catch {
    return undefined;
  }
}

const authStorageState = getUsableStorageState();
if (!authStorageState) {
  console.warn('Playwright auth state ausente ou expirado; testes que exigem sessão serão ignorados.');
}

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false,   // Sequential — API rate limits + single-user session
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [
    ['list'],
    ['html', { outputFolder: 'playwright-report', open: 'never' }],
  ],
  use: {
    baseURL: 'https://localhost',
    ignoreHTTPSErrors: true,    // Self-signed cert in dev container
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',  // Salva vídeo para testes que falham (útil para debug)
    trace: 'on-first-retry',
    // Auth: oauth_token cookie captured by mcp-foursquare-auth-bootstrap.sh
    // Run bootstrap first if this file doesn't exist:
    //   ./scripts/mcp-foursquare-auth-bootstrap.sh
    storageState: authStorageState,
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],

  outputDir: 'test-results',
});
