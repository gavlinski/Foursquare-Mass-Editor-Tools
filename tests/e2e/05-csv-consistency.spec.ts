import { test, expect, type Page } from '@playwright/test';
import path from 'node:path';

type CsvHarnessWindow = Window & {
  dojo: {
    require: (moduleName: string) => void;
    ready: (callback: () => void) => void;
    addOnLoad: (callback: () => void) => void;
    query: (selector: string) => Element[];
    byId: (id: string) => HTMLElement | null;
    hasClass: (element: Element, className: string) => boolean;
    addClass: (element: Element, className: string) => void;
  };
  dijit: {
    byId: (id: string) => {
      checked?: boolean;
      hide?: () => void;
      set: (name: string, value: unknown) => void;
      setAttribute: (name: string, value: unknown) => void;
    } | null;
  };
  appBootstrap: { editChains: boolean };
  debugLog: (...values: unknown[]) => void;
  createTooltip: (...values: unknown[]) => void;
  csv: string[][];
  modo: number;
  DADOS_COMPLETOS: number;
  DADOS_PARCIAIS: number;
  categorias: Array<{ ids?: string }>;
  redes: Array<{ ids: string; nomes?: string; logos?: string }>;
  venuesComDadosCompletos: number[];
  linhasEditadas: number[];
  obterEstadoEdicaoDaNavegacao: () => {
    version: number;
    venueIds: string[];
    editChains: boolean;
  } | null;
  restaurarEstadoEdicao: (state: {
    modo: number;
    csv: string[][];
    categorias: Array<{ ids?: string }>;
    redes: Array<{ ids: string; nomes?: string; logos?: string }>;
    venuesComDadosCompletos: number[];
    linhasEditadas: number[];
    venuellOriginais: string[];
    locais: Array<[string, number, number]>;
    relatorio: unknown[];
    failedRequests: Record<string, unknown>;
    rows: Array<{
      fields: Array<{ value: string; checked: boolean; disabled: boolean; readOnly: boolean }>;
      className: string;
      categoryHtml: string;
      chainHtml: string | null;
      resultHtml: string;
    }>;
  }) => void;
  setupVerticalNavigation: () => void;
  montarArvore: (categories: unknown) => void;
  debugInfo: (...values: unknown[]) => void;
  googleMaps: { map: object; updateMarkers: (locations: Array<[string, number, number]>) => void };
  __testWidgets: Record<string, { checked?: boolean; disabled?: boolean; hide?: () => void; set: (name: string, value: unknown) => void; setAttribute: (name: string, value: unknown) => void }>;
  __restoredMarkers?: Array<[string, number, number]>;
  montarPrefixoLinhaCsv: (
    venueId: string,
    categoryId: string,
    chainId: string,
    header: string[],
    incluirCategoria: boolean
  ) => string;
  anexarSufixoCsv: (venueIndex: number, completa: boolean) => void;
  processarRedesVenue: (venue: { venueChains?: Array<Record<string, unknown>> }, venueIndex: number) => void;
  escapeTooltipHtml: (value: string) => string;
  atualizarIconeRede: (venueIndex: number, permitirEdicao: boolean) => void;
  buscarInfoRede: (chainId: string, venueIndex: number) => void;
  normalizarCsv: () => void;
  processarEdicaoRedes: () => void;
};

const chainId = '556e092ea7c82e6b724ee2ef';
const partialVenueId = '4df0f0181838e429ae526d1c';
const completeVenueId = '64c426d64fc0f44ede091f20';

async function carregarLogicaCsv(page: Page) {
  await page.setContent(`
    <section id="linha0" class="row"><form><input type="hidden" name="venue" value="${partialVenueId}"><input type="checkbox" name="selecao"><input name="checkinsCount" value="107"><input name="usersCount" value="73"><input name="tipCount" value="1"><input name="createdAt" value="2020-01-01"><input name="likesCount" value="0"><input name="listedCount" value="0"><input name="photosCount" value="0"><input name="isClosed" value="false"><input name="isPrivate" value="false"><input name="isDeleted" value="false"><input id="cna0" value="Cacau Show"><input id="chna0" value="Cacau Show"><span id="result0"></span></form><span id="icone0"><img id="catImg0"></span><span id="chainIcone0"><img id="chainImg0"></span></section>
    <section id="linha1" class="row"><form><input type="hidden" name="venue" value="${completeVenueId}"><input type="checkbox" name="selecao"><input name="checkinsCount" value="4"><input name="usersCount" value="4"><input name="tipCount" value="0"><input name="createdAt" value="2020-01-01"><input name="likesCount" value="0"><input name="listedCount" value="0"><input name="photosCount" value="0"><input name="isClosed" value="false"><input name="isPrivate" value="false"><input name="isDeleted" value="false"><input id="cna1" value="Cacau Show"><input id="chna1" value="Cacau Show"><span id="result1"></span></form><span id="icone1"><img id="catImg1"></span><span id="chainIcone1"><img id="chainImg1"></span></section>
    <div id="venueChainIndex">1</div><div id="chainsContainer"></div><div id="chainsIds"></div><div id="chainsLogos"></div>
  `);
  await page.evaluate(() => {
    const editor = window as unknown as CsvHarnessWindow;
    editor.dojo = {
      require: () => undefined,
      ready: () => undefined,
      addOnLoad: () => undefined,
      query: selector => Array.from(document.querySelectorAll(selector)),
      byId: id => document.getElementById(id),
      hasClass: (element, className) => element.classList.contains(className),
      addClass: (element, className) => element.classList.add(className)
    };
    editor.__testWidgets = {
      saveButton: { disabled: true, set: (_name, value) => { editor.__testWidgets.saveButton.disabled = Boolean(value); }, setAttribute: (_name, value) => { editor.__testWidgets.saveButton.disabled = Boolean(value); } },
      reloadButton: { disabled: true, set: (_name, value) => { editor.__testWidgets.reloadButton.disabled = Boolean(value); }, setAttribute: (_name, value) => { editor.__testWidgets.reloadButton.disabled = Boolean(value); } },
      editAllChainsCheckbox: { checked: false, set: (_name, value) => { editor.__testWidgets.editAllChainsCheckbox.checked = Boolean(value); }, setAttribute: () => undefined },
      dlg_chains: { set: () => undefined, setAttribute: () => undefined, hide: () => undefined }
    };
    editor.dijit = { byId: id => editor.__testWidgets[id] || null };
    editor.appBootstrap = { editChains: true };
    editor.debugLog = () => undefined;
    editor.debugInfo = () => undefined;
    editor.createTooltip = () => undefined;
    editor.setupVerticalNavigation = () => undefined;
    editor.googleMaps = {
      map: {},
      updateMarkers: locations => { editor.__restoredMarkers = locations; }
    };
  });
  await page.addScriptTag({ path: path.resolve(process.cwd(), 'js/4sq.js') });
  await page.evaluate(() => {
    const editor = window as unknown as CsvHarnessWindow;
    editor.createTooltip = () => undefined;
    editor.setupVerticalNavigation = () => undefined;
  });
}

function colunasCompletas(): string[] {
  return [
    '""', '""', '""', '""', '""', '""',
    '"2026-10-11"', '4', '4', '0', '0', '0', '0', 'false', 'false', 'false'
  ];
}

test.describe('Consistência do CSV de venues', () => {
  test('mantém as colunas alinhadas no modo parcial', async ({ page }) => {
    await carregarLogicaCsv(page);

    const resultado = await page.evaluate(({ chainId, partialVenueId }) => {
      const editor = window as unknown as CsvHarnessWindow;
      const header = ['venue', 'name', 'checkins', 'users', 'tips'];
      const row = editor.montarPrefixoLinhaCsv(partialVenueId, '', chainId, header, false)
        .concat('&&"Cacau Show"&&107&&73&&1').split('&&');
      editor.csv = [header, row];
      editor.modo = editor.DADOS_PARCIAIS;
      editor.categorias = [];
      editor.redes = [{ ids: chainId }];
      editor.venuesComDadosCompletos = [];

      editor.normalizarCsv();
      const chainColumn = editor.csv[0].indexOf('venueChainId');
      return {
        header: editor.csv[0],
        rows: editor.csv.slice(1),
        chainIds: editor.csv.slice(1).map(rowValue => rowValue[chainColumn].replace(/"/g, ''))
      };
    }, { chainId, partialVenueId });

    expect(resultado.rows.every(row => row.length === resultado.header.length)).toBe(true);
    expect(resultado.chainIds).toEqual([chainId]);
  });

  test('mantém as colunas alinhadas no modo completo', async ({ page }) => {
    await carregarLogicaCsv(page);

    const resultado = await page.evaluate(({ chainId, completeVenueId, fullColumns }) => {
      const editor = window as unknown as CsvHarnessWindow;
      const header = ['venue', 'categoryId', 'name', 'address'];
      const prefix = editor.montarPrefixoLinhaCsv(
        completeVenueId,
        '52f2ab2ebcbc57f1066b8b31',
        chainId,
        header,
        true
      );
      const row = prefix.concat('&&"Cacau Show"&&"R. Quinze de Janeiro, 293"')
        .split('&&').concat(fullColumns);
      editor.csv = [header, row];
      editor.modo = editor.DADOS_COMPLETOS;
      editor.categorias = [];
      editor.redes = [{ ids: chainId }];
      editor.venuesComDadosCompletos = [];

      editor.normalizarCsv();
      const chainColumn = editor.csv[0].indexOf('venueChainId');
      return {
        header: editor.csv[0],
        rows: editor.csv.slice(1),
        chainId: editor.csv[1][chainColumn].replace(/"/g, ''),
        name: editor.csv[1][editor.csv[0].indexOf('name')].replace(/"/g, '')
      };
    }, { chainId, completeVenueId, fullColumns: colunasCompletas() });

    expect(resultado.rows.every(row => row.length === resultado.header.length)).toBe(true);
    expect(resultado.chainId).toBe(chainId);
    expect(resultado.name).toBe('Cacau Show');
  });

  test('anexa as colunas completas ao recarregar um local parcial', async ({ page }) => {
    await carregarLogicaCsv(page);

    const resultado = await page.evaluate(({ chainId, completeVenueId }) => {
      const editor = window as unknown as CsvHarnessWindow;
      const header = ['venue', 'categoryId', 'venueChainId', 'name', 'address', 'city', 'state'];
      const prefix = editor.montarPrefixoLinhaCsv(
        completeVenueId,
        '52f2ab2ebcbc57f1066b8b31',
        chainId,
        header,
        true
      );
      const row = prefix.concat('&&"Cacau Show"&&"R. Quinze de Janeiro, 293"&&"Canoas"&&"RS"')
        .split('&&');
      editor.csv = [header, row];
      editor.modo = editor.DADOS_COMPLETOS;
      editor.appBootstrap.editChains = true;
      editor.venuesComDadosCompletos = [0];

      editor.anexarSufixoCsv(0, true);
      editor.normalizarCsv();
      return { header: editor.csv[0], row: editor.csv[1] };
    }, { chainId, completeVenueId });

    expect(resultado.row.length).toBe(resultado.header.length);
    expect(resultado.row[resultado.header.indexOf('venueChainId')]).toBe(`"${chainId}"`);
    expect(resultado.row[resultado.header.indexOf('createdAt')]).toBe('2020-01-01');
    expect(resultado.row[resultado.header.indexOf('state')]).toBe('"RS"');
  });

  test('não processa nem exibe redes ao recarregar venue completo sem seleção', async ({ page }) => {
    await carregarLogicaCsv(page);

    const resultado = await page.evaluate(({ chainId, completeVenueId }) => {
      const editor = window as unknown as CsvHarnessWindow;
      let chamadasBuscaRede = 0;
      editor.appBootstrap.editChains = false;
      editor.buscarInfoRede = () => { chamadasBuscaRede++; };
      editor.modo = editor.DADOS_COMPLETOS;
      editor.categorias = [];
      editor.redes = [];
      editor.venuesComDadosCompletos = [0];
      editor.csv = [
        ['venue', 'categoryId', 'name', 'state'],
        [completeVenueId, '52f2ab2ebcbc57f1066b8b31', '"Cacau Show"', '"RS"']
      ];

      editor.processarRedesVenue({
        venueChains: [{ id: chainId, bestName: { name: 'Cacau Show' } }]
      }, 0);
      editor.atualizarIconeRede(0, true);
      editor.anexarSufixoCsv(0, true);
      editor.normalizarCsv();

      const chainIcon = document.getElementById('chainIcone0');
      return {
        header: editor.csv[0],
        row: editor.csv[1],
        chainIds: editor.redes[0].ids,
        chainDisplay: chainIcon?.getAttribute('style'),
        chainHtml: chainIcon?.innerHTML,
        chamadasBuscaRede
      };
    }, { chainId, completeVenueId });

    expect(resultado.header).not.toContain('venueChainId');
    expect(resultado.header).not.toContain('primaryVenueChainId');
    expect(resultado.row.length).toBe(resultado.header.length);
    expect(resultado.chainIds).toBe('');
    expect(resultado.chainDisplay).toContain('display: none');
    expect(resultado.chainHtml).toBe('');
    expect(resultado.chamadasBuscaRede).toBe(0);
  });

  test('mantém redes no fluxo de URL direta com todos os campos habilitados', async ({ page }) => {
    await carregarLogicaCsv(page);

    const resultado = await page.evaluate(({ chainId, completeVenueId }) => {
      const editor = window as unknown as CsvHarnessWindow;
      editor.appBootstrap.editChains = true;
      editor.modo = editor.DADOS_COMPLETOS;
      editor.categorias = [];
      editor.redes = [];
      editor.venuesComDadosCompletos = [0];
      editor.csv = [
        ['venue', 'categoryId', 'name', 'state'],
        [completeVenueId, '52f2ab2ebcbc57f1066b8b31', '"Cacau Show"', '"RS"']
      ];

      editor.processarRedesVenue({
        venueChains: [{
          id: chainId,
          bestName: { name: 'Cacau Show' },
          logo: { prefix: 'https://image.test/', suffix: 'logo.png' }
        }]
      }, 0);
      editor.atualizarIconeRede(0, true);
      editor.anexarSufixoCsv(0, true);
      editor.normalizarCsv();

      return {
        header: editor.csv[0],
        row: editor.csv[1],
        display: document.getElementById('chainIcone0')?.getAttribute('style')
      };
    }, { chainId, completeVenueId });

    expect(resultado.header).toContain('venueChainId');
    expect(resultado.row[resultado.header.indexOf('venueChainId')]).toBe(`"${chainId}"`);
    expect(resultado.header).toContain('primaryVenueChainId');
    expect(resultado.display).not.toContain('display: none');
  });

  test('remove redes de um estado antigo restaurado com a opção desmarcada', async ({ page }) => {
    await carregarLogicaCsv(page);

    const resultado = await page.evaluate(({ chainId, completeVenueId }) => {
      const editor = window as unknown as CsvHarnessWindow;
      editor.appBootstrap.editChains = false;
      editor.montarArvore = () => undefined;
      const armazenamentoLocal = new Map<string, string>([['categorias', '{}']]);
      Object.defineProperty(window, 'localStorage', {
        configurable: true,
        value: {
          getItem: (key: string) => armazenamentoLocal.get(key) || null,
          setItem: (key: string, value: string) => armazenamentoLocal.set(key, value),
          removeItem: (key: string) => armazenamentoLocal.delete(key)
        }
      });
      editor.restaurarEstadoEdicao({
        modo: editor.DADOS_COMPLETOS,
        csv: [[
          'venue', 'venueChainId', 'name', 'primaryVenueChainId',
          'addVenueChainIds', 'removeVenueChainIds', 'createdAt'
        ], [
          completeVenueId, chainId, '"Cacau Show"', chainId, '', '', '2020-01-01'
        ]],
        categorias: [],
        redes: [{ ids: chainId, nomes: 'Cacau Show', logos: 'https://image.test/logo.png' }],
        venuesComDadosCompletos: [0],
        linhasEditadas: [],
        venuellOriginais: [],
        locais: [],
        relatorio: [],
        failedRequests: {},
        rows: [{
          fields: [],
          className: 'row',
          categoryHtml: '<img id="catImg0">',
          chainHtml: '<img id="chainImg0">',
          resultHtml: ''
        }]
      });

      const chainIcon = document.getElementById('chainIcone0');
      return {
        header: editor.csv[0],
        row: editor.csv[1],
        chains: editor.redes,
        display: chainIcon?.getAttribute('style'),
        chainHtml: chainIcon?.innerHTML
      };
    }, { chainId, completeVenueId });

    expect(resultado.header).not.toContain('venueChainId');
    expect(resultado.header).not.toContain('primaryVenueChainId');
    expect(resultado.header).not.toContain('addVenueChainIds');
    expect(resultado.header).not.toContain('removeVenueChainIds');
    expect(resultado.row.length).toBe(resultado.header.length);
    expect(resultado.chains).toEqual([]);
    expect(resultado.display).toContain('display: none');
    expect(resultado.chainHtml).toBe('');
  });

  test('preserva IDs e registra a rede removida em uma lista mista', async ({ page }) => {
    await carregarLogicaCsv(page);

    const resultado = await page.evaluate(({ chainId, completeVenueId, partialVenueId, fullColumns }) => {
      const editor = window as unknown as CsvHarnessWindow;
      const header = ['venue', 'categoryId', 'venueChainId', 'name', 'address', 'checkins', 'users', 'tips'];
      const partialRow = [
        `"${partialVenueId}"`, '52f2ab2ebcbc57f1066b8b31', `"${chainId}"`,
        '"Cacau Show"', '"Av. Victor Barreto, 2570"', '107', '73', '1'
      ];
      const completePrefix = editor.montarPrefixoLinhaCsv(
        completeVenueId,
        '52f2ab2ebcbc57f1066b8b31',
        chainId,
        header,
        true
      );
      const completeRow = completePrefix.concat('&&"Cacau Show"&&"R. Quinze de Janeiro, 293"')
        .split('&&').concat(fullColumns);
      editor.csv = [header, partialRow, completeRow];
      editor.modo = editor.DADOS_PARCIAIS;
      editor.categorias = [];
      editor.redes = [{ ids: chainId }, { ids: chainId }];
      editor.venuesComDadosCompletos = [1];
      editor.linhasEditadas = [];

      editor.normalizarCsv();
      editor.processarEdicaoRedes();

      const chainColumn = editor.csv[0].indexOf('venueChainId');
      const removeColumn = editor.csv[0].indexOf('removeVenueChainIds');
      return {
        header: editor.csv[0],
        rows: editor.csv.slice(1),
        chainId: editor.csv[2][chainColumn].replace(/"/g, ''),
        removedChainId: editor.csv[2][removeColumn].replace(/"/g, ''),
        saveDisabled: editor.__testWidgets.saveButton.disabled
      };
    }, { chainId, completeVenueId, partialVenueId, fullColumns: colunasCompletas() });

    expect(resultado.rows.every(row => row.length === resultado.header.length)).toBe(true);
    expect(resultado.chainId).toBe(chainId);
    expect(resultado.removedChainId).toBe(chainId);
    expect(resultado.saveDisabled).toBe(false);
  });

  test('restaura o estado completo do editor após F5', async ({ page }) => {
    await carregarLogicaCsv(page);

    const resultado = await page.evaluate(({ chainId, partialVenueId, completeVenueId }) => {
      const editor = window as unknown as CsvHarnessWindow;
      const storage = new Map<string, string>();
      Object.defineProperty(window, 'sessionStorage', {
        configurable: true,
        value: {
          getItem: (key: string) => storage.get(key) || null,
          setItem: (key: string, value: string) => storage.set(key, value),
          removeItem: (key: string) => storage.delete(key)
        }
      });
      Object.defineProperty(window.performance, 'getEntriesByType', {
        configurable: true,
        value: () => [{ type: 'reload' }]
      });
      const state = {
        version: 1,
        venueIds: [partialVenueId, completeVenueId],
        editChains: true,
        modo: editor.DADOS_PARCIAIS,
        csv: [
          ['venue', 'venueChainId', 'name', 'checkins'],
          [partialVenueId, chainId, '"Cacau Show editado"', '107'],
          [completeVenueId, chainId, '"Cacau Show"', '4']
        ],
        categorias: [],
        redes: [{ ids: chainId, nomes: 'Cacau Show', logos: '' }],
        venuesComDadosCompletos: [1],
        linhasEditadas: [0],
        venuellOriginais: [],
        locais: [['1. Cacau Show', -29.9167, -51.1833], ['2. Cacau Show', -29.917, -51.184]] as [string, number, number][],
        relatorio: [],
        failedRequests: {},
        rows: [0, 1].map(() => ({
          fields: [],
          className: 'row edited',
          categoryHtml: '<img id="catImg0">',
          chainHtml: '<img id="chainImg0">',
          resultHtml: ''
        }))
      };
      sessionStorage.setItem('fmet:editor-state', JSON.stringify(state));

      const savedState = editor.obterEstadoEdicaoDaNavegacao();
      if (!savedState) throw new Error('Estado do editor não foi reconhecido após reload.');
      editor.restaurarEstadoEdicao(state);

      return {
        restored: true,
        csv: editor.csv,
        dirtyRows: editor.linhasEditadas,
        saveDisabled: editor.__testWidgets.saveButton.disabled,
        markers: editor.__restoredMarkers
      };
    }, { chainId, partialVenueId, completeVenueId });

    expect(resultado.restored).toBe(true);
    expect(resultado.csv[1][2]).toBe('"Cacau Show editado"');
    expect(resultado.dirtyRows).toEqual([0]);
    expect(resultado.saveDisabled).toBe(false);
    expect(resultado.markers).toEqual([
      ['1. Cacau Show', -29.9167, -51.1833],
      ['2. Cacau Show', -29.917, -51.184]
    ]);
  });

  test('escapa nomes externos antes de renderizá-los no HTML da tooltip', async ({ page }) => {
    await carregarLogicaCsv(page);

    const resultado = await page.evaluate(() => {
      const editor = window as unknown as CsvHarnessWindow;
      return editor.escapeTooltipHtml('<img src=x onerror=alert(1)> & "rede"');
    });

    expect(resultado).toBe('&lt;img src=x onerror=alert(1)&gt; &amp; &quot;rede&quot;');
  });
});