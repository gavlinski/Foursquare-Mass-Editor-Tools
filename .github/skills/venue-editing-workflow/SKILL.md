---
name: venue-editing-workflow
description: Documenta o fluxo central do produto — Import → Load → Edit → Save de venues do Foursquare (load.php, edit.php, js/4sq.js) — incluindo a lista EDITABLE_FIELDS, as funções core de js/4sq.js, e o padrão de tratamento de status HTTP da API. Use ao adicionar/alterar um campo editável, modificar o fluxo de carregamento/salvamento em massa, depurar erros de API durante edição, ou entender como main.php/load.php/edit.php se conectam.
---

# Venue Editing Workflow

## Process Flow

```
1. Import  → CSV upload (load_csv.php), busca por coordenadas (search.php), IDs/URLs (load.php) ou locais-filhos (children.php)
2. Load    → Busca dados da venue na API (load.php)
3. Edit    → Interface de edição em massa (edit.php, widgets Dojo)
4. Save    → Chamadas em lote à API (js/4sq.js → xmlhttpRequest())
```

Também suportado: `load.php?venues=id1,id2,id3` para carregar venues diretamente por ID via URL.

A aba "Importar locais dentro de outro local" usa `GET /v2/venues/{id}/children`. O campo de local pai aceita busca por nome via `search/autocomplete`, além de ID ou link; informe também uma região como coordenadas ou cidade para restringir/ordenar resultados conforme suportado pela API. O helper compartilhado `js/fsq-autocomplete.js` fornece autocomplete e geocodificação ao frontend.

## Editable Fields

```javascript
const EDITABLE_FIELDS = [
    'name', 'address', 'crossStreet', 'neighborhood',
    'city', 'state', 'zip', 'phone', 'url',
    'twitter', 'facebook', 'instagram', 'categoryId',
    'description', 'menu', 'venuell', 'redes' // lat/lng and venue chains
];
```

Ao adicionar um campo editável, mantenha alinhados a seleção/carregamento, a renderização em `edit.php`, a serialização CSV e o payload enviado ao salvar. Campos com modal e controles próprios, como Redes, não usam um input simples de `renderizarCampo()`.

## Chains (Redes)

O campo **Redes** é opcional e acompanha a seleção `editChains` publicada por `edit.php` em `window.appBootstrap`. Quando desmarcado, os dados de `venueChains` não são processados nem exibidos, as consultas adicionais de chains não são iniciadas e as colunas de Redes não são inseridas no CSV — inclusive durante a recarga de dados parciais como venues completas. O fluxo direto de venues continua selecionando todos os campos conforme definido em `load.php`.

Com Redes selecionado, `processarRedesVenue()` lê `venueChains` da resposta da venue. `GET /v2/chains/search?query=...` fornece sugestões e `GET /v2/chains/{chainId}` pode completar nome/logo ausentes. A interface permite pesquisar por nome, informar o ID diretamente, adicionar/remover redes e promover uma rede principal. Logos e nomes são compartilhados com o CSV e tooltips.

No salvamento, `salvarVenues()` envia pelo endpoint `proposeedit` os parâmetros `primaryVenueChainId`, `addVenueChainIds` e `removeVenueChainIds`, somente quando há mudanças. O comportamento desses parâmetros foi testado na API v2, embora não esteja documentado na referência pública da API. Não inferir sucesso apenas pela presença dos parâmetros no payload: verificar status e resultado da API ao validar alterações.

O CSV inclui `venueChainId` apenas quando Redes foi selecionado. Quando disponível, os campos de proposta são `primaryVenueChainId`, `addVenueChainIds` e `removeVenueChainIds`. `normalizarCsv()` deve manter cabeçalho e linhas alinhados nos modos parcial e completo, inclusive após recarga.

## Category Search and Facebook

A busca de categorias da página principal e da modal de edição usa `search/autocomplete` através de `js/fsq-autocomplete.js`, em vez de depender da correspondência textual do widget Dijit. A modal mantém feedback de adição, duplicata e limite de três categorias; preserve os textos de status específicos da página principal ao alterar o helper compartilhado.

Na edição, o campo Facebook exibe `contact.facebookUsername` quando presente e usa `contact.facebook` como fallback. Antes de salvar, prefixos como `facebook.com/` e `https://` são removidos; o payload atual continua usando `facebookUrl`.

As regressões de UI ficam em `tests/e2e/02-mass-editing.spec.ts`; os cenários de alinhamento, recarga e seleção condicional estão em `tests/e2e/05-csv-consistency.spec.ts`.

## Core Functions (js/4sq.js)

- **`carregarDadosVenues()`** — Busca os dados das venues na API e popula a interface de edição.
- **`salvarVenues()`** — Envia as edições em lote para a API.
- **`sinalizarVenues()`** — Aplica flags/reports em venues problemáticas.
- **`xmlhttpRequest(metodo, endpoint, acao, dados, i)`** — Camada de comunicação AJAX com a Foursquare API; centraliza o tratamento de erros por status code.
- **`retryRequest()`** — Reexecuta chamadas que falharam (ex.: após 429/504).

## API Error Handling Pattern

Toda chamada via `xmlhttpRequest()` deve tratar os códigos de status da Foursquare API:

```javascript
function xmlhttpRequest(metodo, endpoint, acao, dados, i) {
    switch (xmlhttp.status) {
        case 400: // Bad Request — payload inválido
        case 401: // Unauthorized — token expirado, redirecionar para login
        case 403: // Forbidden — permissão insuficiente na venue
        case 404: // Not Found — venue removida/ID inválido
        case 429: // Rate Limited — usar retryRequest() com backoff
        case 500: // Server Error — erro do lado da Foursquare
        case 504: // Gateway Timeout — usar retryRequest()
    }
}
```

Ao adicionar uma nova chamada de API, sempre passe por `xmlhttpRequest()` em vez de duplicar lógica de `fetch`/`XMLHttpRequest` — isso mantém o tratamento de erros consistente em toda a edição em massa.

## Related

- Convenções de widgets Dojo (renderizarCampo, estilos inline): `.github/instructions/dojo-widgets.instructions.md`
- Autenticação/expiração de token durante a edição: skill `session-management`
