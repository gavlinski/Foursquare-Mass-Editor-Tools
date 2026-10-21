import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { skipIfUnauthenticated } from './auth-session';

/**
 * Regressão 6: Facebook, títulos da modal de comentários e autocompletes
 * (categorias, local pai "Dentro" e aba "locais dentro de outro local").
 *
 * Os testes de unidade extraem funções puras de js/*.js; os testes de UI mockam
 * as chamadas à API do Foursquare com page.route.
 */

const jsDir = path.join(process.cwd(), 'js');

function extrairFuncao(arquivo: string, nome: string): string {
  const fonte = fs.readFileSync(path.join(jsDir, arquivo), 'utf8');
  const inicio = fonte.indexOf(`function ${nome}(`);
  if (inicio < 0) throw new Error(`Função ${nome} não encontrada em ${arquivo}`);
  const fim = fonte.indexOf('\n}\n', inicio);
  return fonte.slice(inicio, fim + 2);
}

function carregarFuncao<T>(arquivo: string, nome: string, contexto: string[] = [], valores: unknown[] = []): T {
  const corpo = extrairFuncao(arquivo, nome);
  return new Function(...contexto, `${corpo}\nreturn ${nome};`)(...valores) as T;
}

const categoriasAutocomplete = {
  meta: { code: 200 },
  response: {
    groups: [
      { type: 'venues', items: [] },
      {
        type: 'categories',
        items: [{
          id: '5294c7523cf9994f4e043a62',
          name: 'Casa de Açaí',
          icon: {
            prefix: 'https://ss3.4sqi.net/img/categories_v2/food/argentinian_',
            suffix: '.png'
          }
        }]
      }
    ]
  }
};

const locaisAutocomplete = {
  meta: { code: 200 },
  response: {
    groups: [{
      type: 'unified',
      items: [
        {
          type: 'minivenue',
          object: {
            id: '5a177d2ce1f0aa75261b40b6',
            name: 'ParkShoppingCanoas',
            location: { address: 'Av. Farroupilha, 4545', city: 'Canoas', state: 'RS' },
            categories: [{ icon: { prefix: 'https://ss3.4sqi.net/img/categories_v2/shops/mall_', suffix: '.png' } }]
          }
        },
        { type: 'query', text: 'shopping' }
      ]
    }]
  }
};

const geocodeAutocomplete = {
  meta: { code: 200 },
  response: {
    geocode: {
      interpretations: {
        count: 1,
        items: [{
          feature: {
            name: 'Porto Alegre',
            displayName: 'Porto Alegre, RS, Brasil',
            geometry: { center: { lat: -30.03306, lng: -51.23 } }
          }
        }]
      }
    }
  }
};

const canoasGeocode = {
  meta: { code: 200 },
  response: {
    geocode: {
      interpretations: {
        count: 1,
        items: [{
          feature: {
            name: 'Canoas',
            displayName: 'Canoas, RS, Brasil',
            geometry: { center: { lat: -29.91, lng: -51.16 } }
          }
        }]
      }
    }
  }
};

const serproAutocomplete = {
  meta: { code: 200 },
  response: {
    groups: [{
      type: 'unified',
      items: [
        {
          type: 'minivenue',
          object: {
            id: '4b8bdc0ff964a52010af32e3',
            name: 'Serpro',
            location: { address: 'Av. Borges de Medeiros', city: 'Porto Alegre', state: 'RS' }
          }
        },
        {
          type: 'minivenue',
          object: {
            id: '4d8b803dff45a09031361bae',
            name: 'Serpro',
            location: { address: 'Av. Luiz Vianna Filho', city: 'Salvador', state: 'BA' }
          }
        }
      ]
    }]
  }
};

test.describe('Utilitários (sem sessão)', () => {
  test('normalizarFacebook remove prefixos aceitos erroneamente', () => {
    const normalizar = carregarFuncao<(v: unknown) => string>('4sq.js', 'normalizarFacebook');
    const casos: Array<[unknown, string]> = [
      ['https://facebook.com/minhapagina', 'minhapagina'],
      ['http://www.facebook.com/minhapagina/', 'minhapagina'],
      ['facebook.com/minhapagina', 'minhapagina'],
      ['m.facebook.com/minhapagina?ref=x', 'minhapagina'],
      ['https://fb.com/minhapagina#topo', 'minhapagina'],
      ['@minhapagina', 'minhapagina'],
      ['  minhapagina  ', 'minhapagina'],
      ['123456789012345', '123456789012345'],
      ['', ''],
      [null, '']
    ];
    for (const [entrada, esperado] of casos) {
      expect(normalizar(entrada)).toBe(esperado);
    }
  });

  test('valor Facebook para edição prefere username e usa ID como fallback', () => {
    const obterFacebook = carregarFuncao<
      (contact: { facebook?: string; facebookUsername?: string } | null) => string
    >('4sq.js', 'obterFacebookDaVenue');
    expect(obterFacebook({
      facebook: '309938642387067',
      facebookUsername: 'minikalzone'
    })).toBe('minikalzone');
    expect(obterFacebook({ facebook: '309938642387067' })).toBe('309938642387067');
    expect(obterFacebook(null)).toBe('');
  });

  test('fsqExtrairIdLocal extrai ID de texto ou URL', () => {
    const extrair = carregarFuncao<(v: string) => string>('fsq-autocomplete.js', 'fsqExtrairIdLocal');
    expect(extrair('5a177d2ce1f0aa75261b40b6')).toBe('5a177d2ce1f0aa75261b40b6');
    expect(extrair('https://pt.foursquare.com/v/parkshopping/5a177d2ce1f0aa75261b40b6?x=1')).toBe('5a177d2ce1f0aa75261b40b6');
    expect(extrair('shopping')).toBe('');
  });

  test('normalização das respostas do autocomplete', () => {
    const contexto = ['FSQ_DEFAULT_ICON'];
    const valores = ['default.png'];
    const obterGrupo = extrairFuncao('fsq-autocomplete.js', 'fsqObterGrupo');
    const cats = extrairFuncao('fsq-autocomplete.js', 'fsqNormalizarCategorias');
    const locais = extrairFuncao('fsq-autocomplete.js', 'fsqNormalizarLocais');
    const api = new Function(...contexto, `${obterGrupo}\n${cats}\n${locais}\nreturn { cats: fsqNormalizarCategorias, locais: fsqNormalizarLocais };`)(...valores);

    expect(api.cats(categoriasAutocomplete.response)).toEqual([{
      id: '5294c7523cf9994f4e043a62',
      name: 'Casa de Açaí',
      icon: 'https://ss3.4sqi.net/img/categories_v2/food/argentinian_bg_32.png'
    }]);
    const encontrados = api.locais(locaisAutocomplete.response);
    expect(encontrados).toHaveLength(1);
    expect(encontrados[0]).toMatchObject({ id: '5a177d2ce1f0aa75261b40b6', name: 'ParkShoppingCanoas', detail: 'Av. Farroupilha, 4545, Canoas, RS' });
  });

  test('fsqObterLl usa geo/geocode do Foursquare como principal', async () => {
    let urlChamada = '';
    class MockXhr {
      readyState = 0;
      status = 0;
      responseText = '';
      onreadystatechange?: () => void;
      onerror?: () => void;
      open(_method: string, url: string) { urlChamada = url; }
      send() {
        this.status = 200;
        this.responseText = JSON.stringify(geocodeAutocomplete);
        this.readyState = 4;
        this.onreadystatechange?.();
      }
    }
    const obterLl = carregarFuncao< (texto: string, token?: string) => Promise<string> >(
      'fsq-autocomplete.js',
      'fsqObterLl',
      ['fsqLlCache', 'window', 'fsqGeocodificarPorApi'],
      [{}, {}, () => Promise.resolve('-30.03306,-51.23')]
    );
    const obterLocale = extrairFuncao('fsq-autocomplete.js', 'fsqObterLocale');
    const montarGeocodeUrl = extrairFuncao('fsq-autocomplete.js', 'fsqMontarUrlGeocode');
    const geocodificar = extrairFuncao('fsq-autocomplete.js', 'fsqGeocodificarPorApi');
    const chamadaApi = new Function(
      'navigator',
      'FSQ_AUTOCOMPLETE_VERSION',
      'fsqObterWsid',
      'XMLHttpRequest',
      `${obterLocale}\n${montarGeocodeUrl}\n${geocodificar}\nreturn fsqGeocodificarPorApi;`
    )({ languages: ['pt-BR'] }, '20261016', () => 'TESTWSID', MockXhr);
    await expect(chamadaApi('Porto Alegre, RS', 'test-token')).resolves.toBe('-30.03306,-51.23');
    const parametros = new URL(urlChamada).searchParams;
    expect(new URL(urlChamada).pathname).toBe('/v2/geo/geocode');
    expect(parametros.get('query')).toBe('Porto Alegre, RS');
    expect(parametros.get('autocomplete')).toBe('true');
    expect(parametros.get('allowCountry')).toBe('false');
    expect(parametros.get('oauth_token')).toBe('test-token');
    await expect(obterLl('Canoas, RS', 'test-token')).resolves.toBe('-30.03306,-51.23');
    await expect(obterLl('-29.914254, -51.165327')).resolves.toBe('-29.914254,-51.165327');
  });

  test('fsqObterLl usa Google Maps como fallback quando geo/geocode falha', async () => {
    let chamadasGoogle = 0;
    const obterLl = carregarFuncao< (texto: string, token: string) => Promise<string> >(
      'fsq-autocomplete.js',
      'fsqObterLl',
      ['fsqLlCache', 'window', 'fsqGeocodificarPorApi'],
      [{}, {
        googleMaps: { loadMapsAPI: () => Promise.resolve() },
        google: {
          maps: {
            Geocoder: function(this: any) {
              this.geocode = (_request: unknown, callback: (results: any[], status: string) => void) => {
                chamadasGoogle++;
                callback([{ geometry: { location: { lat: () => -29.91, lng: () => -51.16 } } }], 'OK');
              };
            }
          }
        }
      }, () => Promise.reject(new Error('API indisponível'))]
    );
    await expect(obterLl('Canoas, RS', 'test-token')).resolves.toBe('-29.91,-51.16');
    expect(chamadasGoogle).toBe(1);
  });

  test('diagnóstico de autocomplete HTTP 400 inclui tipo e detalhe da API', () => {
    const detalhar = carregarFuncao<(status: number, body: string) => string>(
      'fsq-autocomplete.js',
      'fsqDetalharErroAutocomplete'
    );
    expect(detalhar(400, JSON.stringify({
      meta: { code: 400, errorType: 'param_error', errorDetail: 'll is required' }
    }))).toContain('A API recusou a busca (param_error) porque a região (ll) está ausente ou inválida');
  });
});

test.describe('Main: autocompletes e aba "dentro"', () => {
  test('submete a pesquisa com cidade convertida em coordenadas no navegador', async ({ page }) => {
    await skipIfUnauthenticated(page);
    let corpoPost = '';
    let urlGeocode = '';
    await page.route('**/v2/geo/geocode**', async route => {
      urlGeocode = route.request().url();
      await route.fulfill({ json: canoasGeocode });
    });
    await page.route('**/4sqmet/search.php', async route => {
      corpoPost = route.request().postData() || '';
      await route.fulfill({ status: 200, contentType: 'text/html', body: 'ok' });
    });
    await page.goto('/4sqmet/main.php');
    await page.waitForFunction(() => Boolean((window as any).dijit?.byId('accordion')));
    await page.evaluate(() => {
      const w = window as any;
      const pane = w.dijit.byId('accordion').getChildren().find((c: any) => c.domNode.querySelector('#f_src'));
      w.dijit.byId('accordion').selectChild(pane, false);
    });
    await page.locator('#query').fill('café');
    await page.locator('#ll').fill('Canoas, RS');
    await page.getByRole('button', { name: /Continuar/i }).filter({ visible: true }).click();
    await expect.poll(() => corpoPost).toContain('-29.91,-51.16');
    expect(new URL(urlGeocode).pathname).toBe('/v2/geo/geocode');
    expect(new URL(urlGeocode).searchParams.get('query')).toBe('Canoas, RS');
    expect(corpoPost).not.toContain('Canoas');
  });

  test('busca de categorias usa search/autocomplete e preenche o categoryId', async ({ page }) => {
    await skipIfUnauthenticated(page);
    let urlChamada = '';
    await page.route('**/v2/search/autocomplete**', async route => {
      urlChamada = route.request().url();
      await route.fulfill({ json: categoriasAutocomplete });
    });
    await page.goto('/4sqmet/main.php');
    await page.locator('#categorySearchMain').fill('acai');
    const resultado = page.locator('#categorySearchMainResults button').first();
    await expect(resultado).toContainText('Casa de Açaí');
    expect(urlChamada).toContain('group=categories');
    expect(urlChamada).toContain('includePrivateCategories=true');
    await resultado.click();
    await expect(page.locator('#categoryId')).toHaveValue('5294c7523cf9994f4e043a62');
    await expect(page.locator('#categorySearchMain')).toHaveValue('Casa de Açaí');
  });

  test('aba "Importar locais dentro de outro local" exibe dica, busca e Coordenadas', async ({ page }) => {
    await skipIfUnauthenticated(page);
    let urlChamada = '';
    await page.route('**/v2/search/autocomplete**', async route => {
      urlChamada = route.request().url();
      await route.fulfill({ json: locaisAutocomplete });
    });
    await page.goto('/4sqmet/main.php');
    await page.waitForFunction(() => Boolean((window as any).dijit?.byId('accordion')));
    await page.evaluate(() => {
      const w = window as any;
      w.dijit.byId('accordion').selectChild(w.dijit.byId('paneDentro'), false);
    });
    await expect(page.getByRole('tab', { name: 'Importar locais dentro de outro local' })).toBeVisible();
    await expect(page.locator('#ll5')).toBeVisible();
    await page.locator('#paneDentro a[href="javascript:showDialogDentro();"]').click();
    await expect(page.locator('.dijitDialog:visible').last()).toContainText('Informe o local pai');
    await page.locator('.dijitDialog:visible').last().locator('.dijitDialogCloseIcon').click();

    await page.locator('#ll5').fill('-29.914254,-51.165327');
    await page.locator('#parentSearchMain').fill('shop');
    const resultado = page.locator('#parentSearchMainResults button').first();
    await expect(resultado).toContainText('ParkShoppingCanoas');
    expect(new URL(urlChamada).searchParams.get('supervenueOnly')).toBeNull();
    expect(new URL(urlChamada).searchParams.get('m')).toBe('foursquare');
    expect(new URL(urlChamada).searchParams.get('group')).toBe('unified');
    expect(urlChamada).toContain('ll=-29.914254%2C-51.165327');
    await resultado.click();
    await expect(page.locator('#parentIdMain')).toHaveValue('5a177d2ce1f0aa75261b40b6');
    await expect(page.locator('#parentSearchMainStatus')).toHaveText('Local com ID "5a177d2ce1f0aa75261b40b6" selecionado.');
    await expect(page.locator('#parentSearchMainStatus')).toBeVisible();
  });

  test('valida ID ou link do local pai e mostra nome selecionado', async ({ page }) => {
    await skipIfUnauthenticated(page);
    let requisicaoLocal = '';
    await page.route('**/v2/search/autocomplete**', async route => {
      await route.fulfill({ json: locaisAutocomplete });
    });
    await page.route('**/v2/venues/4b8bdc0ff964a52010af32e3**', async route => {
      requisicaoLocal = route.request().url();
      await route.fulfill({
        json: {
          meta: { code: 200 },
          response: { venue: { id: '4b8bdc0ff964a52010af32e3', name: 'SERPRO Porto Alegre' } }
        }
      });
    });
    await page.goto('/4sqmet/main.php');
    await page.waitForFunction(() => Boolean((window as any).dijit?.byId('accordion')));
    await page.evaluate(() => {
      const w = window as any;
      w.dijit.byId('accordion').selectChild(w.dijit.byId('paneDentro'), false);
    });

    const status = page.locator('#parentSearchMainStatus');
    await expect(status).toBeHidden();
    await page.locator('#parentSearchMain').fill('shop');
    await expect(page.locator('#parentSearchMainResults button').first()).toContainText('ParkShoppingCanoas');
    await page.locator('#ll5').click();
    await expect(status).toBeHidden();
    await expect(page.locator('#parentIdMain')).toHaveValue('');

    await page.locator('#parentSearchMain').fill('https://foursquare.com/v/serpro/4b8bdc0ff964a52010af32e3');
    await expect(status).toHaveText('Local "SERPRO Porto Alegre" selecionado.');
    await expect(page.locator('#parentSearchMain')).toHaveValue('4b8bdc0ff964a52010af32e3');
    await expect(page.locator('#parentIdMain')).toHaveValue('4b8bdc0ff964a52010af32e3');
    expect(new URL(requisicaoLocal).pathname).toBe('/v2/venues/4b8bdc0ff964a52010af32e3');

    await page.locator('#parentSearchMain').fill('4b8bdc0ff964a52010af32e3');
    await expect(status).toHaveText('Local "SERPRO Porto Alegre" selecionado.');
    await expect(page.locator('#parentSearchMain')).toHaveValue('4b8bdc0ff964a52010af32e3');

    await page.locator('#parentSearchMain').fill('SERPRO');
    await expect(status).toBeHidden();
    await page.locator('#parentSearchMain').click();
    await expect(status).toBeHidden();
  });

  test('ID de local inexistente mostra erro detalhado em alerta sem expandir a barra', async ({ page }) => {
    await skipIfUnauthenticated(page);
    await page.route('**/v2/venues/4b8bdc0ff964a52010af32ea**', async route => {
      await route.fulfill({
        status: 400,
        contentType: 'application/json',
        body: JSON.stringify({
          meta: {
            code: 400,
            errorType: 'param_error',
            errorDetail: 'Value 4b8bdc0ff964a52010af32ea is invalid for venue id'
          }
        })
      });
    });
    await page.goto('/4sqmet/main.php');
    await page.waitForFunction(() => Boolean((window as any).dijit?.byId('accordion')));
    await page.evaluate(() => {
      const w = window as any;
      w.dijit.byId('accordion').selectChild(w.dijit.byId('paneDentro'), false);
    });
    const dialogPromise = page.waitForEvent('dialog');
    await page.locator('#parentSearchMain').fill('4b8bdc0ff964a52010af32ea');
    const dialog = await dialogPromise;
    expect(dialog.message()).toContain('HTTP 400');
    expect(dialog.message()).toContain('param_error');
    await dialog.accept();
    const status = page.locator('#parentSearchMainStatus');
    await expect(status).toHaveText('Não foi possível validar o local informado.');
    await expect(status).toBeVisible();
    await expect(status).toHaveJSProperty('offsetHeight', 18);
  });

  test('busca Dentro usa near com o texto da Região e prioriza local da cidade', async ({ page }) => {
    await skipIfUnauthenticated(page);
    let urlChamada = '';
    await page.route('**/v2/search/autocomplete**', async route => {
      urlChamada = route.request().url();
      await route.fulfill({ json: serproAutocomplete });
    });
    await page.goto('/4sqmet/main.php');
    await page.waitForFunction(() => Boolean((window as any).dijit?.byId('accordion')));
    await page.evaluate(() => {
      const w = window as any;
      w.dijit.byId('accordion').selectChild(w.dijit.byId('paneDentro'), false);
    });
    await page.locator('#ll5').fill('Porto Alegre, RS');
    await page.locator('#parentSearchMain').fill('serpro');
    const resultado = page.locator('#parentSearchMainResults button').first();
    await expect(resultado).toContainText('Serpro');
    await expect(resultado).toContainText('Porto Alegre, RS');
    const parametros = new URL(urlChamada).searchParams;
    expect(parametros.get('near')).toBe('Porto Alegre, RS');
    expect(parametros.get('ll')).toBeNull();
    expect(parametros.get('m')).toBe('foursquare');
    expect(parametros.get('group')).toBe('unified');
    expect(parametros.get('supervenueOnly')).toBeNull();
  });

  test('restaura os dados do painel Dentro ao voltar da edição', async ({ page }) => {
    await skipIfUnauthenticated(page);
    await page.route('**/4sqmet/children.php', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'text/html',
        body: '<!doctype html><html><body><script>window.location.replace("edit.php");</script></body></html>'
      });
    });
    await page.route('**/4sqmet/edit.php', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'text/html',
        body: '<!doctype html><html><body><button id="backButton" onclick="location.href=\'main.php\'">Voltar</button></body></html>'
      });
    });

    await page.goto('/4sqmet/main.php');
    await page.waitForFunction(() => Boolean((window as any).dijit?.byId('f_dentro')));
    await page.evaluate(() => {
      const w = window as any;
      w.dijit.byId('accordion').selectChild(w.dijit.byId('paneDentro'), false);
      w.dijit.byId('parentSearchMain').set('value', 'ParkShoppingCanoas');
      w.dijit.byId('ll5').set('value', '-29.914254,-51.165327');
      w.dijit.byId('nome5').set('checked', false);
      w.dijit.byId('telefone5').set('checked', true);
      w.dijit.byId('facebook5').set('checked', true);
      w.dojo.byId('parentIdMain').value = '5a177d2ce1f0aa75261b40b6';
    });
    await page.getByRole('button', { name: /Continuar/i }).filter({ visible: true }).click();
    await page.waitForURL('**/edit.php');
    await page.locator('#backButton').click();
    await page.waitForURL('**/main.php');

    await expect(page.locator('#parentSearchMain')).toHaveValue('ParkShoppingCanoas');
    await expect(page.locator('#parentIdMain')).toHaveValue('5a177d2ce1f0aa75261b40b6');
    await expect(page.locator('#ll5')).toHaveValue('-29.914254,-51.165327');
    await expect(page.locator('#nome5')).not.toBeChecked();
    await expect(page.locator('#telefone5')).toBeChecked();
    await expect(page.locator('#facebook5')).toBeChecked();
  });

  test('campo Dentro explica erro 400 retornado pela API', async ({ page }) => {
    await skipIfUnauthenticated(page);
    await page.route('**/v2/search/autocomplete**', async route => {
      await route.fulfill({
        status: 400,
        contentType: 'application/json',
        body: JSON.stringify({ meta: { code: 400, errorType: 'param_error', errorDetail: 'll is required' } })
      });
    });
    await page.goto('/4sqmet/main.php');
    await page.waitForFunction(() => Boolean((window as any).dijit?.byId('accordion')));
    await page.evaluate(() => {
      const w = window as any;
      w.dijit.byId('accordion').selectChild(w.dijit.byId('paneDentro'), false);
    });
    await page.locator('#parentSearchMain').fill('shopping');
    await expect(page.locator('#parentSearchMainStatus')).toContainText('região (ll) está ausente ou inválida');
    await expect(page.locator('#parentSearchMainStatus')).toContainText('param_error');
    await expect(page.locator('#parentSearchMainStatus')).toContainText('ll is required');
  });

  test('diagnóstico de geocodificação conclui no navegador com o mesmo helper da aplicação', async ({ page }) => {
    await page.route('**/js/google-maps.js', async route => {
      await route.fulfill({
        contentType: 'application/javascript',
        body: 'window.googleMaps={loadMapsAPI:function(){return Promise.resolve();}};'
      });
    });
    await page.route('**/js/fsq-autocomplete.js', async route => {
      await route.fulfill({
        contentType: 'application/javascript',
        body: `window.fsqObterLl=function(address){return Promise.resolve(address==='Canoas, RS'?'-29.91,-51.16':Promise.reject(new Error('ZERO_RESULTS')));};`
      });
    });
    await page.goto('/4sqmet/debug/test_geocoding_detailed.php');
    await expect(page.locator('#geocodeAddress')).toHaveValue('Canoas, RS');
    await page.locator('#geocodeButton').click();
    await expect(page.locator('#geocodeStatus')).toHaveText('Geocodificação concluída.');
    await expect(page.locator('#geocodeResult')).toContainText('"ll": "-29.91,-51.16"');
    await expect(page.getByText('Teste Direto com a API')).toHaveCount(0);
  });

  test('teste de geocodificação executa todos os endereços pelo helper do navegador', async ({ page }) => {
    await page.route('**/js/google-maps.js**', async route => {
      await route.fulfill({
        contentType: 'application/javascript',
        body: 'window.googleMaps={loadMapsAPI:function(){return Promise.resolve();}};'
      });
    });
    await page.route('**/js/fsq-autocomplete.js**', async route => {
      await route.fulfill({
        contentType: 'application/javascript',
        body: `window.fsqObterLl=function(address){return Promise.resolve(address==='Canoas, RS'?'-29.91,-51.16':'-30,-51');};`
      });
    });
    await page.goto('/4sqmet/debug/test_geocoding.php');
    await expect(page.locator('.test-case')).toHaveCount(4);
    await page.locator('#testAll').click();
    await expect(page.locator('#overallStatus')).toHaveText('4 de 4 endereços convertidos.');
    await expect(page.locator('.caseResult').first()).toContainText('"ll": "-29.91,-51.16"');
    await expect(page.getByText('Configure GOOGLE_MAPS_GEOCODING_KEY')).toHaveCount(0);
  });
});

test.describe('Edit: modal, Facebook, Dentro e categorias', () => {
  test('edit.php aplica títulos da modal, normaliza Facebook e usa autocomplete', async ({ page }) => {
    const venueId = '4ec42a0a9a522f580b42dbeb';
    let facebookId = '';
    let facebookUsername = '';
    await skipIfUnauthenticated(page);
    await page.route(`**/v2/venues/${venueId}**`, async route => {
      const response = await route.fetch();
      const body = await response.json();
      facebookId = body.response?.venue?.contact?.facebook || '';
      facebookUsername = body.response?.venue?.contact?.facebookUsername || '';
      await route.fulfill({ response, body: JSON.stringify(body) });
    });
    await page.route('**/v2/search/autocomplete**', async route => {
      const url = route.request().url();
      if (url.includes('group=categories')) {
        const respostaCategorias = JSON.parse(JSON.stringify(categoriasAutocomplete));
        const itemBase = respostaCategorias.response.groups.find((group: { type: string }) => group.type === 'categories').items[0];
        respostaCategorias.response.groups.find((group: { type: string }) => group.type === 'categories').items.push(
          { ...itemBase, id: 'category-result-2', name: 'Categoria de teste 2' },
          { ...itemBase, id: 'category-result-3', name: 'Categoria de teste 3' },
          { ...itemBase, id: 'category-result-4', name: 'Categoria de teste 4' }
        );
        await route.fulfill({ json: respostaCategorias });
        return;
      }
      await route.fulfill({ json: locaisAutocomplete });
    });
    await page.goto('/4sqmet/main.php');
    await page.waitForFunction(() => Boolean((window as any).dijit?.byId('accordion')));
    await page.evaluate(() => {
      const w = window as any;
      const pane = w.dijit.byId('accordion').getChildren().find((c: any) => c.domNode.querySelector('#f_ids'));
      w.dijit.byId('accordion').selectChild(pane, false);
    });
    await page.locator('#textarea_ids').fill(venueId);
    for (const campo of ['nome3', 'facebook3', 'dentro3']) {
      const checkbox = page.locator(`#${campo}`);
      if (!(await checkbox.isChecked()) && !(await checkbox.isDisabled())) await page.locator(`label[for="${campo}"]`).click();
    }
    await page.getByRole('button', { name: /Continuar/i }).filter({ visible: true }).click();
    await page.waitForURL('**/edit.php**', { timeout: 30_000 });

    // Facebook: prefixos são removidos ao alterar o campo
    const facebook = page.locator('input[name=facebook]').first();
    await expect(facebook).toBeVisible();
    // Aguarda o carregamento assíncrono dos dados do local
    await expect.poll(() => facebookId || facebookUsername).not.toBe('');
    expect(facebookUsername).not.toBe('');
    await expect(facebook).toHaveValue(facebookUsername);
    expect(facebookUsername).not.toBe(facebookId);
    await facebook.fill('https://facebook.com/minhapagina/');
    await facebook.press('Tab');
    await expect(facebook).toHaveValue('minhapagina');

    // Dentro: autocomplete grava o ID do local escolhido
    const dentro = page.locator('input[name=parentId]').first();
    await dentro.fill('shop');
    const resultadoPai = page.locator('#parentSearchResults button').first();
    await expect(resultadoPai).toContainText('ParkShoppingCanoas');
    await resultadoPai.click();
    await expect(dentro).toHaveValue('5a177d2ce1f0aa75261b40b6');

    // Edição múltipla: abrir pelo menu também inicializa a busca de categorias
    await page.evaluate(() => (window as any).dijit.byId('menu2ItemCategories').onClick());
    await page.locator('#categorySearchInput').fill('acai');
    await expect(page.locator('#categorySearchResults button').first()).toContainText('Casa de Açaí');
    await page.evaluate(() => (window as any).dijit.byId('dlg_cats').hide());

    // Categorias: busca da modal de edição por local usa o autocomplete da API
    await page.evaluate(() => (window as any).editarCategorias(0));
    await page.locator('#categorySearchInput').fill('acai');
    await expect(page.locator('#categorySearchResults button').first()).toContainText('Casa de Açaí');
    await expect(page.locator('#categorySearchResults button')).toHaveCount(4);
    const listaCategorias = page.locator('#categorySearchResults');
    await expect.poll(() => listaCategorias.evaluate(element => ({
      maxHeight: getComputedStyle(element).maxHeight,
      overflowY: getComputedStyle(element).overflowY,
      scrollable: element.scrollHeight > element.clientHeight
    }))).toEqual({ maxHeight: '160px', overflowY: 'auto', scrollable: true });
    await page.locator('#categorySearchResults button').first().click();
    await expect(page.locator('#catsIds')).toContainText('5294c7523cf9994f4e043a62');
    await expect(page.locator('#categorySearchStatus')).toHaveText('Categoria "Casa de Açaí" adicionada.');
    await page.waitForTimeout(500);
    await expect(page.locator('#categorySearchStatus')).toHaveText('Categoria "Casa de Açaí" adicionada.');

    await page.evaluate(() => {
      (window as any).atualizarCategorias(['Categoria 1', 'Categoria 2'], 'id1,id2', 'icone1,icone2');
    });
    await page.locator('#categorySearchInput').fill('acai');
    await expect(page.locator('#categorySearchResults button')).toHaveCount(4);
    await page.locator('#categorySearchResults button').nth(1).click();
    await expect(page.locator('#categorySearchStatus')).toHaveText('Categoria "Categoria de teste 2" adicionada.');
    await expect(page.locator('#catsIds')).toContainText('id1,id2,category-result-2');
    await page.waitForTimeout(500);
    await expect(page.locator('#categorySearchStatus')).toHaveText('Categoria "Categoria de teste 2" adicionada.');

    await page.locator('#categorySearchInput').fill('acai');
    await expect(page.locator('#categorySearchResults button')).toHaveCount(4);
    await page.locator('#categorySearchResults button').nth(1).click();
    await expect(page.locator('#categorySearchStatus')).toHaveText('Esta categoria já está selecionada.');
    await expect(page.locator('#categorySearchStatus')).toHaveClass(/is-error/);

    await page.locator('#categorySearchInput').fill('acai');
    await expect(page.locator('#categorySearchResults button')).toHaveCount(4);
    await page.locator('#categorySearchResults button').nth(2).click();
    await expect(page.locator('#categorySearchStatus')).toHaveText('O limite de 3 categorias foi atingido.');
    await page.waitForTimeout(500);
    await expect(page.locator('#categorySearchStatus')).toHaveText('O limite de 3 categorias foi atingido.');

    await page.evaluate(() => {
      const w = window as any;
      w.atualizarCategorias(['Categoria 1', 'Categoria 2'], 'id1,id2', 'icone1,icone2');
      w.store = {
        getValue: (_item: unknown, key: string) => key === 'id' ? 'tree-category-id' : 'tree-category-icon',
        getLabel: () => 'Categoria da árvore'
      };
      w.treeOnClick({ root: false });
    });
    await expect(page.locator('#categorySearchStatus')).toHaveText('Categoria "Categoria da árvore" adicionada.');
    await expect(page.locator('#categorySearchStatus')).toBeVisible();
    await page.evaluate(() => (window as any).treeOnClick({ root: false }));
    await expect(page.locator('#categorySearchStatus')).toHaveText('Esta categoria já está selecionada.');
    await expect(page.locator('#categorySearchStatus')).toHaveClass(/is-error/);
    await page.evaluate(() => {
      const w = window as any;
      w.atualizarCategorias(['Categoria 1', 'Categoria 2', 'Categoria 3'], 'id1,id2,id3', 'icone1,icone2,icone3');
      w.treeOnClick({ root: false });
    });
    await expect(page.locator('#categorySearchStatus')).toHaveText('O limite de 3 categorias foi atingido.');
    await expect(page.locator('#categorySearchStatus')).toBeVisible();
    await page.evaluate(() => (window as any).dijit.byId('dlg_cats').hide());

    // Modal de comentários: título dinâmico e label
    await page.evaluate(() => {
      const w = window as any;
      const nomeInput = document.querySelector('input[name=name]') as HTMLInputElement;
      const indiceNome = w.csv[0].indexOf('name');
      const valorOriginal = String(w.csv[1][indiceNome] || '').replace(/^"|"$/g, '');
      nomeInput.value = `${valorOriginal} alteração pendente`;
      w.linhasEditadas = [1, 2, 3, 4, 5, 6, 7];
      w.showDialogComment('saveButton');
    });
    const dialog = page.locator('#dlg_comment');
    await expect(page.locator('#dlg_comment_title')).toHaveText('Salvar alterações em 8 locais');
    await expect(dialog.getByText('Comentários')).toBeVisible();
    const titulos = await page.evaluate(() => {
      const w = window as any;
      return [w.obterTituloModalComentario('closed'), w.obterTituloModalComentario('duplicate')];
    });
    expect(titulos[0]).toMatch(/^Sinalizar \d+ locais? como "closed"$/);
    expect(titulos[1]).toMatch(/^Sinalizar local com \d+ duplicadas?$/);
  });
});
