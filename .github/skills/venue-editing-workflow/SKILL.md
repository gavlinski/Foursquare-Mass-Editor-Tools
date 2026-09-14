---
name: venue-editing-workflow
description: Documenta o fluxo central do produto — Import → Load → Edit → Save de venues do Foursquare (load.php, edit.php, js/4sq.js) — incluindo a lista EDITABLE_FIELDS, as funções core de js/4sq.js, e o padrão de tratamento de status HTTP da API. Use ao adicionar/alterar um campo editável, modificar o fluxo de carregamento/salvamento em massa, depurar erros de API durante edição, ou entender como main.php/load.php/edit.php se conectam.
---

# Venue Editing Workflow

## Process Flow

```
1. Import  → CSV upload (load_csv.php) ou busca por coordenadas (search.php)
2. Load    → Busca dados da venue na API (load.php)
3. Edit    → Interface de edição em massa (edit.php, widgets Dojo)
4. Save    → Chamadas em lote à API (js/4sq.js → xmlhttpRequest())
```

Também suportado: `load.php?venues=id1,id2,id3` para carregar venues diretamente por ID via URL.

## Editable Fields

```javascript
const EDITABLE_FIELDS = [
    'name', 'address', 'crossStreet', 'neighborhood',
    'city', 'state', 'zip', 'phone', 'url',
    'twitter', 'facebook', 'instagram', 'categoryId',
    'description', 'menu', 'venuell' // lat/lng
];
```

Ao adicionar um novo campo editável, ele precisa aparecer em três lugares:
1. Esta lista em `js/4sq.js`.
2. Um `renderizarCampo()` correspondente em `edit.php` (ver skill/instructions de Dojo widgets).
3. O payload enviado em `xmlhttpRequest()` ao salvar.

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
