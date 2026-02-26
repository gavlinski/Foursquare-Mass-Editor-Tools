# Comparação de APIs Foursquare - Análise de Migração

## 📋 Índice
- [Visão Geral](#visão-geral)
- [Metodologia de Teste](#metodologia-de-teste)
- [Comparação de Busca (Search)](#comparação-de-busca-search)
- [Comparação de Detalhes (Get)](#comparação-de-detalhes-get)
- [Comparação de Edição (Post)](#comparação-de-edição-post)
- [Análise de Campos](#análise-de-campos)
- [Recomendações](#recomendações)

---

## Visão Geral

Este documento apresenta uma análise comparativa entre as três versões de APIs do Foursquare:

1. **API v2** (depreciada) - Implementação atual do projeto
2. **API v3** (depreciada) - Versão intermediária descontinuada
3. **Places API** (atual) - Versão mais moderna e recomendada

### Mudanças Estratégicas

A Foursquare implementou mudanças significativas na nomenclatura:
- **"venues"** → **"places"** (locais)
- **"venue_id"** → **"fsq_place_id"** (identificador único)

### Objetivo da Análise

Responder à questão crítica: **A busca (search) retorna campos adicionais que não eram retornados na API v2?**

Campos investigados:
- ✅ `neighborhood` (bairro)
- ✅ `parentId` (dentro de)
- ✅ `instagram` (Instagram handle)
- ✅ `description` (descrição)
- ✅ `menu` (URL do menu)
- ✅ `hours` (horário de funcionamento)

---

## Metodologia de Teste

### Ferramenta Utilizada

Foi criada uma interface web interativa (`debug/api_comparison_tool.html`) que permite:
- Executar requisições paralelas para as 3 APIs
- Comparar respostas lado a lado
- Analisar automaticamente disponibilidade de campos
- Exportar resultados para documentação

### Parâmetros de Teste Padrão

**Busca:**
```
Query: "pizza"
Localização: -15.7801,-47.9292 (Brasília, DF)
Raio: 1000 metros
Limite: 5 resultados
```

**Detalhes:**
```
Venue ID de teste: 4b058f63f964a520c3a422e3
```

---

## Comparação de Busca (Search)

### API v2 (Depreciada)

**Endpoint:**
```
GET https://api.foursquare.com/v2/venues/search
```

**Parâmetros:**
```javascript
{
  oauth_token: "OAUTH_TOKEN",
  v: "20230404",
  ll: "lat,lng",
  query: "string",
  radius: "meters",
  limit: "number"
}
```

**Estrutura de Resposta:**
```javascript
{
  meta: {
    code: 200,
    requestId: "..."
  },
  response: {
    venues: [
      {
        id: "venue_id",
        name: "Nome do Local",
        location: {
          address: "Endereço",
          crossStreet: "Rua transversal",
          lat: -15.7801,
          lng: -47.9292,
          distance: 123,
          city: "Cidade",
          state: "UF",
          country: "País",
          formattedAddress: [...]
        },
        categories: [
          {
            id: "category_id",
            name: "Categoria",
            pluralName: "Categorias",
            shortName: "Cat",
            icon: {...},
            primary: true
          }
        ],
        verified: false,
        stats: {
          checkinsCount: 100,
          usersCount: 50,
          tipCount: 10
        }
      }
    ]
  }
}
```

**Campos Disponíveis:**
- ✅ Nome, endereço, cidade, estado, CEP
- ✅ Coordenadas (lat/lng)
- ✅ Categoria principal (máximo 1 na busca)
- ✅ Estatísticas básicas (checkins, users, tips)
- ❌ **Bairro (neighborhood)** - NÃO retornado na busca
- ❌ **Dentro de (parentId)** - NÃO retornado
- ❌ **Instagram** - NÃO retornado
- ❌ **Descrição** - NÃO retornado
- ❌ **Menu** - NÃO retornado
- ❌ **Horas** - NÃO retornado

---

### API v3 (Depreciada)

**Endpoint:**
```
GET https://api.foursquare.com/v3/places/search
```

**Autenticação:**
```
Header: Authorization: API_KEY
```

**Parâmetros:**
```javascript
{
  ll: "lat,lng",
  query: "string",
  radius: "meters",
  limit: "number"
}
```

**Estrutura de Resposta:**
```javascript
{
  results: [
    {
      fsq_id: "fsq_place_id",
      name: "Nome do Local",
      geocodes: {
        main: {
          latitude: -15.7801,
          longitude: -47.9292
        }
      },
      location: {
        address: "Endereço",
        locality: "Cidade",
        region: "Estado",
        postcode: "CEP",
        country: "BR",
        formatted_address: "..."
      },
      categories: [
        {
          id: 13065,
          name: "Pizzaria",
          icon: {...}
        }
      ],
      distance: 123
    }
  ]
}
```

**Campos Disponíveis:**
- ✅ Nome, endereço estruturado, coordenadas
- ✅ Categorias (pode retornar múltiplas)
- ✅ Distance do ponto de busca
- ⚠️ **Bairro (neighborhood)** - Depende do local
- ❌ **Instagram** - NÃO retornado na busca
- ❌ **Descrição** - NÃO retornado
- ❌ **Menu** - NÃO retornado
- ❌ **Horas** - NÃO retornado

---

### Places API (Atual)

**Endpoint:**
```
GET https://places-api.foursquare.com/places/search
```

**Autenticação:**
```
Header: Authorization: API_KEY
```

**Parâmetros:**
```javascript
{
  ll: "lat,lng",
  query: "string",
  radius: "meters",
  limit: "number",
  fields: "fsq_id,name,location,categories,distance,hours,description,social_media,menu"
}
```

**Estrutura de Resposta:**
```javascript
{
  results: [
    {
      fsq_id: "fsq_place_id",
      name: "Nome do Local",
      location: {
        address: "Rua Example, 123",
        address_extended: "Sala 456",
        locality: "São Paulo",
        region: "SP",
        postcode: "01234-567",
        country: "BR",
        formatted_address: "...",
        neighborhood: ["Bairro Nome"],  // ✅ DISPONÍVEL
        po_box: null,
        post_town: null
      },
      geocodes: {
        main: {
          latitude: -23.5505,
          longitude: -46.6333
        },
        roof: {...}
      },
      categories: [
        {
          id: 13065,
          name: "Pizzaria",
          icon: {...}
        }
      ],
      distance: 123,
      hours: {  // ✅ DISPONÍVEL (se solicitado via fields)
        display: "Mon-Fri 11:00 AM-10:00 PM",
        is_local_holiday: false,
        open_now: true,
        regular: [...]
      },
      description: "Melhor pizza da cidade...",  // ✅ DISPONÍVEL
      social_media: {  // ✅ DISPONÍVEL
        instagram: "@pizzariaexample",
        twitter: "@pizzariaexample",
        facebook_id: "123456789"
      },
      menu: "https://example.com/menu",  // ✅ DISPONÍVEL
      related_places: {
        parent: {  // ✅ EQUIVALENTE A "parentId"
          fsq_id: "parent_place_id",
          name: "Shopping Center"
        }
      }
    }
  ],
  context: {
    geo_bounds: {...}
  }
}
```

**Campos Disponíveis:**
- ✅ **Tudo da v2 e v3**
- ✅ **Bairro (neighborhood)** - Array de bairros
- ✅ **Dentro de (parent)** - Via `related_places.parent`
- ✅ **Instagram** - Via `social_media.instagram`
- ✅ **Descrição** - Campo `description`
- ✅ **Menu** - URL do menu
- ✅ **Horas** - Objeto `hours` completo

**Campo `fields` Especial:**
- A Places API permite solicitar campos específicos
- Reduz payload e melhora performance
- **IMPORTANTE:** Campos extras (hours, description, social_media, menu) precisam ser explicitamente solicitados

---

## Comparação de Detalhes (Get)

### API v2

**Endpoint:**
```
GET https://api.foursquare.com/v2/venues/{venue_id}
```

**Resposta:**
```javascript
{
  meta: { code: 200 },
  response: {
    venue: {
      id: "venue_id",
      name: "Nome",
      contact: {
        phone: "(11) 1234-5678",
        formattedPhone: "(11) 1234-5678",
        twitter: "handle",
        instagram: "handle",  // ✅ DISPONÍVEL
        facebook: "page_id"
      },
      location: {
        address: "...",
        crossStreet: "...",
        lat: -23.5505,
        lng: -46.6333,
        neighborhood: "Bairro",  // ✅ DISPONÍVEL
        city: "Cidade",
        state: "UF",
        postalCode: "CEP",
        country: "BR"
      },
      categories: [...],  // Até 3 categorias
      verified: true,
      stats: {
        checkinsCount: 5000,
        usersCount: 1234,
        tipCount: 567
      },
      url: "https://website.com",
      likes: { count: 100 },
      menu: {
        url: "https://menu.com"  // ✅ DISPONÍVEL
      },
      description: "Descrição do local...",  // ✅ DISPONÍVEL
      hours: {  // ✅ DISPONÍVEL
        status: "Open",
        isOpen: true,
        timeframes: [...]
      },
      parent: {  // ✅ DISPONÍVEL
        id: "parent_venue_id",
        name: "Shopping"
      }
    }
  }
}
```

**Conclusão:** API v2 retorna **TODOS** os campos quando consultando detalhes de um local específico. A limitação estava apenas na busca (search).

---

### API v3

**Endpoint:**
```
GET https://api.foursquare.com/v3/places/{fsq_id}
```

**Resposta:**
```javascript
{
  fsq_id: "fsq_place_id",
  name: "Nome",
  location: {...},
  geocodes: {...},
  categories: [...],
  hours: {...},  // ✅ DISPONÍVEL
  description: "...",  // ✅ DISPONÍVEL
  tel: "(11) 1234-5678",
  website: "https://...",
  social_media: {  // ✅ DISPONÍVEL
    instagram: "@handle",
    twitter: "@handle"
  },
  menu: "https://...",  // ✅ DISPONÍVEL
  related_places: {
    parent: {...}  // ✅ DISPONÍVEL
  }
}
```

---

### Places API

**Endpoint:**
```
GET https://places-api.foursquare.com/places/{fsq_place_id}
```

**Parâmetro fields:**
```
?fields=fsq_id,name,location,geocodes,categories,hours,description,tel,website,social_media,menu,related_places,photos,rating,stats
```

**Resposta:** Estrutura similar à busca, mas com todos os campos solicitados.

---

## Comparação de Edição (Post)

### API v2

**Endpoint:**
```
POST https://api.foursquare.com/v2/venues/{venue_id}/proposeedit
```

**Autenticação:** OAuth Token via query string

**Parâmetros (form-urlencoded):**
```
oauth_token=TOKEN
v=20230404
name=Nome Novo
address=Rua Example, 123
crossStreet=perto da Av. Principal
city=São Paulo
state=SP
zip=01234-567
phone=(11) 1234-5678
twitter=@handle
instagram=@handle
categoryId=category_id
removeCategoryIds=old_category_id
addCategoryIds=new_category_id
venuell=lat,lng
comment=Comentário sobre a edição
```

**Características:**
- ✅ Aceita todos os campos editáveis
- ✅ Instagram suportado
- ✅ Múltiplas categorias (add/remove)
- ✅ Comentário obrigatório
- ⚠️ Usa `venue_id`
- ⚠️ OAuth via query string (menos seguro)

---

### API v3

**Endpoint:**
```
POST https://api.foursquare.com/v3/places/{fsq_id}/proposeedit
```

**Autenticação:** Authorization header

**Body (JSON):**
```json
{
  "name": "Nome Novo",
  "address": "Rua Example, 123",
  "locality": "São Paulo",
  "region": "SP",
  "postcode": "01234-567",
  "tel": "(11) 1234-5678",
  "twitter": "@handle",
  "instagram": "@handle",
  "category": "category_id",
  "geocode": {
    "latitude": -23.5505,
    "longitude": -46.6333
  },
  "comment": "Comentário"
}
```

**Características:**
- ✅ JSON body (mais moderno)
- ✅ Authorization header (mais seguro)
- ✅ Instagram suportado
- ⚠️ Usa `fsq_id`
- ❌ API depreciada

---

### Places API

**Endpoint:**
```
POST https://places-api.foursquare.com/places/{fsq_place_id}/suggest/edit
```

**Autenticação:** Authorization header

**Body (JSON):**
```json
{
  "name": "Nome Novo",
  "location": {
    "address": "Rua Example, 123",
    "locality": "São Paulo",
    "region": "SP",
    "postcode": "01234-567"
  },
  "contact": {
    "phone": "(11) 1234-5678"
  },
  "social_media": {
    "twitter": "@handle",
    "instagram": "@handle"
  },
  "categories": [
    { "id": 13065 }
  ],
  "hours": {
    "regular": [...]
  },
  "description": "Nova descrição",
  "menu": "https://menu.com",
  "comment": "Comentário sobre edição"
}
```

**Características:**
- ✅ Estrutura aninhada e organizada
- ✅ Instagram via `social_media.instagram`
- ✅ Suporte completo a horários
- ✅ Endpoint `/suggest/edit` (mais descritivo)
- ✅ API atual e mantida
- ⚠️ Usa `fsq_place_id`

---

## Análise de Campos

### Tabela Comparativa - Busca (Search)

| Campo | API v2 | API v3 | Places API | Notas |
|-------|--------|--------|------------|-------|
| **Nome** | ✅ | ✅ | ✅ | Disponível em todas |
| **Endereço** | ✅ | ✅ | ✅ | Estruturas diferentes |
| **Cidade** | ✅ | ✅ | ✅ | v3/Places: `locality` |
| **Estado** | ✅ | ✅ | ✅ | v3/Places: `region` |
| **CEP** | ✅ | ✅ | ✅ | v3/Places: `postcode` |
| **Bairro** | ❌ | ⚠️ | ✅ | v2: NÃO. v3: às vezes. Places: sim (array) |
| **Coordenadas** | ✅ | ✅ | ✅ | Estruturas diferentes |
| **Categoria** | ✅ (1) | ✅ (múltiplas) | ✅ (múltiplas) | v2: apenas principal |
| **Instagram** | ❌ | ❌ | ✅ | Apenas Places (via fields) |
| **Descrição** | ❌ | ❌ | ✅ | Apenas Places (via fields) |
| **Menu** | ❌ | ❌ | ✅ | Apenas Places (via fields) |
| **Horas** | ❌ | ❌ | ✅ | Apenas Places (via fields) |
| **Dentro de** | ❌ | ❌ | ✅ | Places: `related_places.parent` (via fields) |

### Tabela Comparativa - Detalhes (Get)

| Campo | API v2 | API v3 | Places API |
|-------|--------|--------|------------|
| **Todos os campos básicos** | ✅ | ✅ | ✅ |
| **Bairro** | ✅ | ✅ | ✅ |
| **Instagram** | ✅ | ✅ | ✅ |
| **Descrição** | ✅ | ✅ | ✅ |
| **Menu** | ✅ | ✅ | ✅ |
| **Horas** | ✅ | ✅ | ✅ |
| **Dentro de** | ✅ | ✅ | ✅ |

**Conclusão:** A limitação da API v2 estava APENAS na busca (search), não nos detalhes (get).

---

## Recomendações

### 🎯 Resposta à Pergunta Principal

**"Os campos (bairro, dentro, instagram, descrição, menu, horas) são retornados pela busca?"**

**Resposta:**
- ❌ **API v2 (atual):** NÃO retorna esses campos na busca
- ⚠️ **API v3 (depreciada):** Retorna parcialmente (bairro às vezes)
- ✅ **Places API (atual):** Retorna TODOS os campos, mas precisa especificar via parâmetro `fields`

### 📊 Análise de Custo-Benefício

#### Opção 1: Manter API v2 (Status Quo)
**Prós:**
- ✅ Sistema funcionando e testado
- ✅ Sem necessidade de migração imediata
- ✅ OAuth2 já configurado

**Contras:**
- ❌ API oficialmente depreciada
- ❌ Busca retorna dados parciais (requer GET adicional)
- ❌ Pode ser descontinuada a qualquer momento
- ❌ Sem suporte a novos recursos

**Impacto:** Médio risco. Funciona, mas está em tecnologia descontinuada.

---

#### Opção 2: Migrar para API v3 (NÃO RECOMENDADO)
**Prós:**
- ✅ Estrutura JSON mais limpa
- ✅ Authorization header (mais seguro)

**Contras:**
- ❌ **API TAMBÉM DEPRECIADA**
- ❌ Busca ainda não retorna todos os campos
- ❌ Requer mudanças em IDs (`venue_id` → `fsq_id`)
- ❌ Sem benefício real vs v2

**Impacto:** NÃO VALE A PENA. Mesma situação da v2 mas com trabalho de migração.

---

#### Opção 3: Migrar para Places API (RECOMENDADO) ⭐
**Prós:**
- ✅ API atual e mantida pela Foursquare
- ✅ **Busca retorna TODOS os campos** (via `fields`)
- ✅ Elimina necessidade de GET adicional após busca
- ✅ Reduz chamadas à API (economia de quota)
- ✅ Performance melhorada
- ✅ Suporte garantido a longo prazo
- ✅ Estrutura moderna e bem documentada

**Contras:**
- ⚠️ Requer migração de código
- ⚠️ Mudança de IDs (`venue_id` → `fsq_place_id`)
- ⚠️ Autenticação diferente (API Key vs OAuth)
- ⚠️ Estrutura JSON diferente

**Impacto:** Alto trabalho inicial, mas benefício permanente.

---

### 🛣️ Roadmap de Migração Sugerido

#### Fase 1: Preparação (Q1 2026)
1. ✅ Documentar diferenças (este documento)
2. ✅ Criar ferramenta de teste (api_comparison_tool.html)
3. ⏳ Obter API Key da Places API
4. ⏳ Testar autenticação e quotas
5. ⏳ Mapear campos v2 → Places API

#### Fase 2: Implementação Híbrida (Q2 2026)
1. Criar camada de abstração para API
2. Implementar busca com Places API (paralelo à v2)
3. Feature flag para alternar entre v2 e Places
4. Testes extensivos com dados reais
5. Comparar performance e resultados

#### Fase 3: Migração Gradual (Q2-Q3 2026)
1. Migrar busca (search) primeiro
2. Migrar detalhes (get) depois
3. Migrar edição (post) por último
4. Manter fallback para v2 em caso de falhas

#### Fase 4: Finalização (Q4 2026)
1. Remover código v2
2. Otimizar chamadas com `fields`
3. Documentar nova estrutura
4. Treinar usuários em novas features

---

### 💡 Habilitação Imediata de Campos (Solução Temporária)

**Para API v2 (enquanto não migra):**

Não é possível habilitar os campos ausentes na busca sem fazer um GET adicional. A estratégia atual de "busca parcial + GET completo" é a única viável com v2.

**Recomendação:** Manter implementação atual até migração para Places API.

---

### 🔑 Mudanças Necessárias no Código

#### Busca (search.php / js/4sq.js)
```javascript
// ANTES (v2)
const url = `https://api.foursquare.com/v2/venues/search?oauth_token=${token}&ll=${ll}`;

// DEPOIS (Places API)
const url = `https://places-api.foursquare.com/places/search?ll=${ll}&fields=fsq_id,name,location,geocodes,categories,hours,description,social_media,menu,related_places`;
// Headers: { 'Authorization': apiKey }
```

#### Detalhes (load.php / js/4sq.js)
```javascript
// ANTES (v2)
const url = `https://api.foursquare.com/v2/venues/${venueId}?oauth_token=${token}`;

// DEPOIS (Places API)
const url = `https://places-api.foursquare.com/places/${fsqPlaceId}?fields=...`;
```

#### Edição (edit.php / salvarVenues())
```javascript
// ANTES (v2)
POST https://api.foursquare.com/v2/venues/${venueId}/proposeedit
Body: oauth_token=...&name=...

// DEPOIS (Places API)
POST https://places-api.foursquare.com/places/${fsqPlaceId}/suggest/edit
Headers: { 'Authorization': apiKey, 'Content-Type': 'application/json' }
Body: { "name": "...", "location": {...}, "social_media": {...} }
```

---

## 📖 Referências

### Documentação Oficial

**API v2 (Depreciada):**
- Search: https://developer.foursquare.com/docs/api/venues/search
- Details: https://developer.foursquare.com/docs/api/venues/details
- Edit: https://developer.foursquare.com/docs/api/venues/proposeedit

**API v3 (Depreciada):**
- Search: https://docs.foursquare.com/developer/reference/place-search
- Details: https://docs.foursquare.com/developer/reference/address-details
- Edit: https://docs.foursquare.com/developer/reference/place-propose-edit

**Places API (Atual):**
- Search: https://docs.foursquare.com/fsq-developers-places/reference/place-search
- Details: https://docs.foursquare.com/fsq-developers-places/reference/place-details
- Edit: https://docs.foursquare.com/fsq-developers-places/reference/place-suggest-edit

---

## 🎯 Conclusões Finais

1. **Busca na API v2 NÃO retorna campos completos** (bairro, instagram, descrição, menu, horas, dentro)
2. **Places API retorna TODOS os campos na busca** (se solicitados via `fields`)
3. **Migração para Places API eliminará necessidade de GET adicional após busca**
4. **Economia estimada: ~50% menos chamadas à API** (busca + get → apenas busca)
5. **Recomendação: Migrar para Places API em fases ao longo de 2026**

---

**Documento gerado em:** 1 de Janeiro de 2026  
**Versão:** 1.0.0  
**Ferramenta de teste:** `debug/api_comparison_tool.html`  
**Status:** 🟡 Aguardando testes reais com API Key

