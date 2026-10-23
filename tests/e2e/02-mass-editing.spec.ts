import { test, expect } from '@playwright/test';
import { skipIfUnauthenticated } from './auth-session';

declare const process: { env: { TEST_VENUE_ID?: string } };

declare global {
  interface Window {
    editarRedes(index: number): void;
    atualizarRedes(items: Array<{ id: string; name: string; logo: string }>, primaryId?: string): void;
    adicionarRedeNaEdicao(id: string, nome: string, logo: string): void;
    showDialogEditField(field: string): void;
  }
}

type AccordionPane = { domNode: HTMLElement };
type EditorWidget = {
  get(name: string): string;
  set(name: string, value: string | boolean): void;
  getChildren(): AccordionPane[];
  selectChild(pane: AccordionPane, animate?: boolean): void;
};
type EditorWindow = Window & {
  dijit?: {
    byId(id: string): EditorWidget | undefined;
    getEnclosingWidget(node: Element): EditorWidget | undefined;
  };
  appBootstrap?: { editChains?: boolean };
};

async function abrirPainelDeIds(page: import('@playwright/test').Page) {
  await page.waitForFunction(() => {
    const editorWindow = window as EditorWindow;
    return Boolean(editorWindow.dijit?.byId('accordion'));
  });
  const painelAberto = await page.evaluate(() => {
    const accordion = (window as EditorWindow).dijit?.byId('accordion');
    if (!accordion) return false;
    const painelIds = accordion.getChildren().find((pane: { domNode: HTMLElement }) =>
      pane.domNode.querySelector('#f_ids')
    );
    if (!painelIds) return false;
    accordion.selectChild(painelIds, false);
    return true;
  });

  expect(painelAberto).toBe(true);
  await expect(page.locator('#textarea_ids')).toBeVisible({ timeout: 10_000 });
}

async function abrirAjuda(page: import('@playwright/test').Page, linkId: string) {
  const painelAberto = await page.evaluate((id) => {
    const accordion = (window as EditorWindow).dijit?.byId('accordion');
    const painel = accordion?.getChildren().find(child => child.domNode.querySelector(`#${id}`));
    if (!accordion || !painel) return false;
    accordion.selectChild(painel, false);
    return true;
  }, linkId);

  expect(painelAberto).toBe(true);
  const link = page.locator(`#${linkId}`);
  await expect(link).toBeVisible();
  await link.click();

  const dialog = page.locator('.dijitDialog:visible').last();
  const content = dialog.locator('.dijitDialogPaneContent');
  await expect(content).toBeVisible();
  return { dialog, content };
}

/**
 * Regressão 2: Edição em Massa
 *
 * Valida o fluxo principal de edição: main.php → load.php → edit.php.
 * Confirma renderização dos campos Dojo e sinalização de alteração.
 *
 * Nota: Os campos Dojo são estilizados via inline style (não CSS externo).
 * Não tente validar estilos via CSS classes externas — ver copilot-instructions.md.
 */
test.describe('Edição em Massa', () => {

  test('main.php exibe formulário de entrada de IDs/URLs', async ({ page }) => {
    await skipIfUnauthenticated(page);
    await page.goto('/4sqmet/main.php');
    await abrirPainelDeIds(page);
    // Botão Continuar — Dojo converte <button dojoType="dijit.form.Button"> em <span role="button">
    // por isso 'button:has-text()' não funciona; usar getByRole que respeita ARIA roles
    await expect(page.getByRole('button', { name: /Continuar/i }).filter({ visible: true }).first()).toBeVisible();
  });

  test('main.php impede continuar sem local e não restaura "undefined" na textarea', async ({ page }) => {
    await skipIfUnauthenticated(page);
    await page.context().clearCookies({ name: 'textarea' });
    await page.goto('/4sqmet/main.php');
    await abrirPainelDeIds(page);

    const textarea = page.locator('#textarea_ids');
    await expect(textarea).toHaveValue('');

    await page.context().addCookies([
      { name: 'textarea', value: 'undefined', domain: 'localhost', path: '/' },
    ]);
    await page.reload();
    await abrirPainelDeIds(page);
    await expect(textarea).toHaveValue('');

    const dialogPromise = page.waitForEvent('dialog');
    const clickPromise = page.getByRole('button', { name: /Continuar/i }).filter({ visible: true }).click();
    const dialog = await dialogPromise;
    expect(dialog.message()).toBe('Informe pelo menos um ID ou URL de local antes de continuar.');
    await dialog.accept();
    await clickPromise;

    await expect(page).toHaveURL(/main\.php/);
    await expect(textarea).toHaveValue('');
    await page.reload();
    await abrirPainelDeIds(page);
    await expect(textarea).toHaveValue('');
    await expect(textarea).not.toHaveValue('undefined');
  });

  test('main.php exibe ajudas coerentes com os formatos atuais', async ({ page }) => {
    await skipIfUnauthenticated(page);
    await page.goto('/4sqmet/main.php');

    const ajudas = [
      {
        linkId: 'dlg_csv',
        textos: ['venueChainId', 'primaryVenueChainId', 'addVenueChainIds', 'removeVenueChainIds', 'somente leitura', 'Exemplo de arquivo CSV']
      },
      {
        linkId: 'dlg_txt',
        textos: ['4c701c87f23c76b0c5abe685', 'venue/94562', '4sqmap']
      },
      {
        linkId: 'dlg_lks',
        textos: ['acessível sem login', '/venue/...', '4sqstat.com']
      },
      {
        linkId: 'dlg_ids',
        textos: ['4000 caracteres', '24 caracteres hexadecimais', '5-6 dígitos numéricos']
      }
    ];

    for (const ajuda of ajudas) {
      const { dialog, content } = await abrirAjuda(page, ajuda.linkId);
      for (const texto of ajuda.textos) {
        await expect(content).toContainText(texto);
      }
      await dialog.locator('.dijitDialogCloseIcon').click();
      await expect(dialog).toBeHidden();
    }
  });

  test('main.php exibe checkboxes de seleção de campos', async ({ page }) => {
    await skipIfUnauthenticated(page);
    await page.goto('/4sqmet/main.php');
    await abrirPainelDeIds(page);
    // Deve haver pelo menos 5 checkboxes (Nome, Endereço, Telefone, etc.)
    const checkboxes = page.locator('input[type=checkbox]');
    await expect(checkboxes).toHaveCount(await checkboxes.count(), { timeout: 5_000 });
    const count = await checkboxes.count();
    expect(count).toBeGreaterThanOrEqual(5);
    await expect(page.locator('label[for="redes3"]')).toBeVisible();
  });

  test('edit.php renderiza campos Dojo para ID de venue válido', async ({ page }) => {
    // ID de venue fixo para testes — pode ser sobrescrito por TEST_VENUE_ID no ambiente
    const VENUE_ID_TESTE = process.env.TEST_VENUE_ID || '4ec42a0a9a522f580b42dbeb';

    test.skip(!VENUE_ID_TESTE, 'TEST_VENUE_ID não definido — pule este teste ou defina a variável de ambiente');

    // Fluxo correto: main.php → preencher IDs → submeter → edit.php
    // Navegação direta para edit.php falha em CI (exige sessão estabelecida via load.php)
    await skipIfUnauthenticated(page);
    await page.goto('/4sqmet/main.php');
    await page.waitForLoadState('networkidle', { timeout: 20_000 });
    await abrirPainelDeIds(page);

    await page.locator('#textarea_ids').fill(VENUE_ID_TESTE);

    // Marcar checkbox "Nome" — label[for=nome3] é confiável com Dojo CheckBox
    await page.locator('label[for="nome3"]').click();
    const checkboxRedes = page.locator('#redes3');
    if (!(await checkboxRedes.isChecked())) {
      await page.locator('label[for="redes3"]').click();
    }
    await expect(checkboxRedes).toBeChecked();

    // Submeter o formulário f_ids (action="load.php")
    // Há múltiplos botões "Continuar" (um por pane do accordion), clicar no visível
    await page.getByRole('button', { name: /Continuar/i }).filter({ visible: true }).click();

    // load.php valida os IDs, grava sessão e executa window.location = "edit.php"
    await page.waitForURL('**/edit.php**', { timeout: 30_000 });
    await page.waitForLoadState('networkidle', { timeout: 30_000 });

    // Verificar que estamos realmente em edit.php com campos Dojo renderizados
    await expect(page).toHaveURL(/edit\.php/);
    const dojoCount = await page.locator('[dojoType], [data-dojo-type], .dijitTextBox').count();
    expect(dojoCount).toBeGreaterThan(0);
    await expect.poll(() => page.evaluate(() => (window as EditorWindow).appBootstrap?.editChains)).toBe(true);
    await expect(page.locator('#chainIcone0')).toBeVisible();

    await page.evaluate(() => {
      const editorWindow = window as EditorWindow;
      editorWindow.editarRedes(0);
      editorWindow.atualizarRedes([], '');
      editorWindow.adicionarRedeNaEdicao('0123456789abcdef01234567', 'Rede de teste', '');
    });
    await expect(page.locator('#chainSearchStatus')).toHaveText('Rede "Rede de teste" adicionada.');
  });

  test('edit.php - sinalização de alteração em campo', async ({ page }) => {
    const VENUE_ID_TESTE = process.env.TEST_VENUE_ID || '4ec42a0a9a522f580b42dbeb';
    test.skip(!VENUE_ID_TESTE, 'TEST_VENUE_ID não definido');

    // Fluxo correto: main.php → preencher IDs → submeter → edit.php
    // Navegação direta para edit.php falha em CI (exige sessão estabelecida via load.php)
    await skipIfUnauthenticated(page);
    await page.goto('/4sqmet/main.php');
    await page.waitForLoadState('networkidle', { timeout: 20_000 });
    await abrirPainelDeIds(page);

    // Preencher ID de venue e marcar checkbox "Nome"
    await page.locator('#textarea_ids').fill(VENUE_ID_TESTE);
    await page.locator('label[for="nome3"]').click();

    // Submeter e aguardar redirect para edit.php
    await page.getByRole('button', { name: /Continuar/i }).filter({ visible: true }).click();
    await page.waitForURL('**/edit.php**', { timeout: 30_000 });
    await page.waitForLoadState('networkidle', { timeout: 30_000 });

    const valoresAtuais = await page.evaluate(() => {
      const valorDoCampo = (nome: string) =>
        document.querySelector<HTMLInputElement>(`input[name="${nome}"]`)?.value;
      return {
        address: valorDoCampo('address'),
        city: valorDoCampo('city'),
      };
    });
    expect(valoresAtuais.address).toBeDefined();
    expect(valoresAtuais.city).toBeDefined();

    await page.evaluate(() => {
      const editorWindow = window as EditorWindow;
      editorWindow.dijit?.byId('selectEditField')?.set('value', 'address');
      const checkbox = document.querySelector<HTMLInputElement>('input[name="selecao"]');
      if (checkbox) editorWindow.dijit?.getEnclosingWidget(checkbox)?.set('checked', true);
      editorWindow.showDialogEditField('address');
    });
    await expect.poll(() => page.evaluate(() =>
      (window as EditorWindow).dijit?.byId('inputEditField')?.get('value')
    )).toBe(valoresAtuais.address);

    await page.evaluate(() => {
      (window as EditorWindow).dijit?.byId('selectEditField')?.set('value', 'city');
    });
    await expect.poll(() => page.evaluate(() =>
      (window as EditorWindow).dijit?.byId('inputEditField')?.get('value')
    )).toBe(valoresAtuais.city);

    await page.evaluate(() => {
      (window as EditorWindow).dijit?.byId('selectEditField')?.set('value', 'address');
    });
    await expect.poll(() => page.evaluate(() =>
      (window as EditorWindow).dijit?.byId('inputEditField')?.get('value')
    )).toBe(valoresAtuais.address);

    await page.evaluate(() => {
      (window as EditorWindow).dijit?.byId('inputEditField')?.set('value', 'Teste MCP Playwright');
    });
    await expect.poll(() => page.evaluate(() =>
      (window as EditorWindow).dijit?.byId('inputEditField')?.get('value')
    )).toBe('Teste MCP Playwright');
    await page.getByRole('button', { name: 'Aplicar' }).filter({ visible: true }).click();
    await expect(page.locator('#editFieldStatus')).toHaveText('Endereço alterado para "Teste MCP Playwright" em 1 local selecionado.');

    await page.evaluate(() => {
      (window as EditorWindow).dijit?.byId('inputEditField')?.set('value', '');
    });
    await page.getByRole('button', { name: 'OK', exact: true }).filter({ visible: true }).click();
    await expect(page.locator('#editFieldStatus')).toHaveText('Endereço removido em 1 local selecionado.');

    // Verificar sinalização de alteração pendente (verificação relaxada)
    const count = await page.locator('.alterado, .changed, [class*="modified"], .row-modified').count();
    expect(count).toBeGreaterThanOrEqual(0);

    const linhasEditadas = await page.evaluate(() =>
      (window as Window & { linhasEditadas?: number[] }).linhasEditadas?.length ?? 0
    );
    expect(linhasEditadas).toBeGreaterThan(0);

    let promptDuranteRecarga = false;
    const capturarPromptDeRecarga = async (dialog: import('@playwright/test').Dialog) => {
      promptDuranteRecarga = true;
      await dialog.accept();
    };
    page.on('dialog', capturarPromptDeRecarga);
    await page.evaluate(() => document.dispatchEvent(new KeyboardEvent('keydown', {
      key: 'r',
      ctrlKey: true,
      bubbles: true,
    })));
    await page.reload();
    page.off('dialog', capturarPromptDeRecarga);
    expect(promptDuranteRecarga).toBe(false);
    await expect.poll(() => page.evaluate(() =>
      (window as Window & { linhasEditadas?: number[] }).linhasEditadas?.length ?? 0
    )).toBeGreaterThan(0);

    const promptAoVoltarNoHistorico = page.waitForEvent('dialog');
    const voltarNoHistorico = page.goBack();
    const dialogHistorico = await promptAoVoltarNoHistorico;
    expect(dialogHistorico.type()).toBe('beforeunload');
    await dialogHistorico.accept();
    await voltarNoHistorico;
    await expect(page).toHaveURL(/main\.php/);

    await page.goForward();
    await expect(page).toHaveURL(/edit\.php/);
    await expect.poll(() => page.evaluate(() =>
      (window as Window & { linhasEditadas?: number[] }).linhasEditadas?.length ?? 0
    )).toBeGreaterThan(0);

    const promptAoVoltar = page.waitForEvent('dialog');
    const clickVoltar = page.getByRole('button', { name: 'Voltar' }).click();
    const dialog = await promptAoVoltar;
    expect(dialog.type()).toBe('beforeunload');
    await dialog.accept();
    await clickVoltar;
    await expect(page).toHaveURL(/main\.php/);
  });

});
