import { test, expect } from '@playwright/test';
import path from 'node:path';

type FlagRequest = {
  method: string;
  endpoint: string;
  action: string;
  data: string;
  index: number;
};

type FlagHarnessWindow = Window & {
  appBootstrap: { oauthToken: string };
  dojo: {
    require: (moduleName: string) => void;
    ready: (callback: () => void) => void;
    addOnLoad: (callback: () => void) => void;
    query: (selector: string) => Element[];
    byId: (id: string) => HTMLElement | null;
    cookie: () => undefined;
  };
  dijit: {
    byId: (id: string) => {
      value?: string;
      update?: () => void;
      show?: () => void;
      setChecked?: (checked: boolean) => void;
      get?: (name: string) => boolean;
      set: () => void;
      setAttribute: () => void;
    };
  };
  totalSelecionadas: number;
  debugInfo: (...values: unknown[]) => void;
  debugLog: (...values: unknown[]) => void;
  sinalizarVenues: (problema: string) => void;
  atualizarSinalizadas: (index: number, timeout: boolean) => void;
  xmlhttpRequest: (method: string, endpoint: string, action: string, data: string, index: number) => void;
  desabilitarLinha: (index: number) => void;
  __flagRequests: FlagRequest[];
  __flagCallbacks: number;
};

async function prepararHarness(page: import('@playwright/test').Page, checkins: number[]) {
  const rows = checkins.map((value, index) => `
    <section id="linha${index}">
      <form>
        <input id="selecao${index}" type="checkbox" name="selecao" value="${index}" checked>
        <input type="hidden" name="venue" value="venue-${index}">
        <input id="vcc${index}" value="${value}">
        <span id="result${index}"></span>
      </form>
    </section>
  `).join('');

  await page.setContent(rows);
  await page.evaluate(() => {
    const editor = window as unknown as FlagHarnessWindow;
    const controls: Record<string, {
      value?: string;
      update?: () => void;
      show?: () => void;
      set: () => void;
      setAttribute: () => void;
    }> = {};

    editor.appBootstrap = { oauthToken: 'mock-token' };
    editor.dojo = {
      require: () => undefined,
      ready: () => undefined,
      addOnLoad: () => undefined,
      query: selector => Array.from(document.querySelectorAll(selector)),
      byId: id => document.getElementById(id),
      cookie: () => undefined
    };
    editor.dijit = {
      byId: id => {
        const input = document.getElementById(id) as HTMLInputElement | null;
        if (input && input.type === 'checkbox') {
          return {
            get: () => input.checked,
            setChecked: checked => { input.checked = checked; },
            set: () => undefined,
            setAttribute: () => undefined
          };
        }
        if (!controls[id]) {
          controls[id] = {
            update: () => undefined,
            show: () => undefined,
            set: () => undefined,
            setAttribute: () => undefined
          };
        }
        return controls[id];
      }
    };
    editor.debugInfo = () => undefined;
    editor.debugLog = () => undefined;
  });
  await page.addScriptTag({ path: path.resolve(process.cwd(), 'js/4sq.js') });
  await page.evaluate(() => {
    const editor = window as unknown as FlagHarnessWindow;
    editor.__flagRequests = [];
    editor.__flagCallbacks = 0;
    editor.totalSelecionadas = document.querySelectorAll('input[name="selecao"]:checked').length;
    editor.desabilitarLinha = index => {
      const checkbox = document.getElementById(`selecao${index}`) as HTMLInputElement;
      checkbox.checked = false;
    };
    editor.xmlhttpRequest = (method, endpoint, action, data, index) => {
      editor.__flagRequests.push({ method, endpoint, action, data, index });
      window.setTimeout(() => {
        const problem = new URLSearchParams(data).get('problem');
        if (!['public', 'private', 'not_closed', 'un_delete'].includes(problem || '')) {
          editor.desabilitarLinha(index);
        }
        editor.atualizarSinalizadas(index, false);
        editor.__flagCallbacks++;
      }, 0);
    };
  });
}

test.describe('Seleção após sinalização mockada', () => {
  test('mantém a seleção em sinalizações comuns que não desabilitam as linhas', async ({ page }) => {
    await prepararHarness(page, [30, 20, 10]);

    await page.evaluate(() => (window as unknown as FlagHarnessWindow).sinalizarVenues('public'));
    await page.waitForFunction(() => {
      const editor = window as unknown as FlagHarnessWindow;
      return editor.__flagCallbacks === 3;
    });

    const result = await page.evaluate(() => {
      const editor = window as unknown as FlagHarnessWindow;
      return {
        requests: editor.__flagRequests,
        checked: Array.from(document.querySelectorAll<HTMLInputElement>('input[name="selecao"]'))
          .map(checkbox => checkbox.checked)
      };
    });

    expect(result.requests).toHaveLength(3);
    expect(result.requests.map(request => request.endpoint)).toEqual([
      'https://api.foursquare.com/v2/venues/venue-0/flag',
      'https://api.foursquare.com/v2/venues/venue-1/flag',
      'https://api.foursquare.com/v2/venues/venue-2/flag'
    ]);
    expect(result.requests.every(request => request.method === 'POST' && request.action === 'flag')).toBe(true);
    expect(result.checked).toEqual([true, true, true]);
  });

  test('marca todas as venues para event_over sem chamar a API real', async ({ page }) => {
    await prepararHarness(page, [30, 20, 10]);

    await page.evaluate(() => (window as unknown as FlagHarnessWindow).sinalizarVenues('event_over'));
    await page.waitForFunction(() => {
      const editor = window as unknown as FlagHarnessWindow;
      return editor.__flagCallbacks === 3;
    });

    const result = await page.evaluate(() => {
      const editor = window as unknown as FlagHarnessWindow;
      return {
        venues: editor.__flagRequests.map(request => request.endpoint.split('/').at(-2)),
        checked: Array.from(document.querySelectorAll<HTMLInputElement>('input[name="selecao"]'))
          .map(checkbox => checkbox.checked)
      };
    });

    expect(result.venues).toEqual(['venue-0', 'venue-1', 'venue-2']);
    expect(result.checked).toEqual([false, false, false]);
  });

  test('reserva a venue com mais check-ins para a sinalização duplicate', async ({ page }) => {
    await prepararHarness(page, [30, 20]);

    await page.evaluate(() => (window as unknown as FlagHarnessWindow).sinalizarVenues('duplicate'));
    await page.waitForFunction(() => {
      const editor = window as unknown as FlagHarnessWindow;
      return editor.__flagCallbacks === 1;
    });

    const result = await page.evaluate(() => {
      const editor = window as unknown as FlagHarnessWindow;
      return {
        requests: editor.__flagRequests,
        checked: Array.from(document.querySelectorAll<HTMLInputElement>('input[name="selecao"]'))
          .map(checkbox => checkbox.checked)
      };
    });

    expect(result.requests).toHaveLength(1);
    expect(result.requests[0].endpoint).toBe('https://api.foursquare.com/v2/venues/venue-1/flag');
    expect(new URLSearchParams(result.requests[0].data).get('venueId')).toBe('venue-0');
    expect(result.checked).toEqual([false, false]);
  });
});
