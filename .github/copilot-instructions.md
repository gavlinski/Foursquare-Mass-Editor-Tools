# Instruções do GitHub Copilot para o Projeto Foursquare Mass Editor Tools

## Visão Geral do Sistema

Este aplicativo é uma **coleção de ferramentas de place makers** para edição em massa e pesquisa em locais (venues) do Foursquare, construído com tecnologia da API do Foursquare v2. O sistema foi modernizado de PHP 5.4 para PHP 8.1, mantendo compatibilidade com componentes legados.

### Tecnologias e Arquitetura

**Stack Tecnológico Principal:**
- **Backend**: PHP 8.1 com PSR-4 autoloading (Composer) + Legacy PHP
- **Frontend**: Dojo Toolkit v1.8.14 + HTML5/CSS3/JavaScript ES6
- **API**: Foursquare API v2 com OAuth2 authentication
- **Containerização**: Docker com Apache 2.4
- **Gerenciamento de Dependências**: Composer + npm (se aplicável)

**Arquitetura Híbrida:**
```
src/
├── Api/           # Classes PSR-4 para integração com APIs
├── Config/        # Configurações modernizadas
└── Security/      # Gerenciamento de sessão moderno

FoursquareAPI.Class.php  # Classe legacy mantida para compatibilidade
js/
├── session-manager.js   # SessionManager ES6 moderno
├── 4sq.js              # Lógica principal de venues (legacy)
└── main.js             # Interface principal com Dojo
```

## Convenções de Código e Padrões

### PHP Moderno (PSR-4)
```php
<?php
declare(strict_types=1);

namespace ElioTools\Security;

class SessionManager {
    public function start(array $options = []): void {
        // Implementação com type hints
    }
}
```

### Integração Legacy
```php
// SEMPRE use os métodos corretos da FoursquareAPI.Class.php:
$foursquare->SetAccessToken($token);     // NÃO setAccessToken
$foursquare->GetPrivate("users/self");  // NÃO getPrivate
$foursquare->AuthenticationLink($uri);  // NÃO authenticationLink
```

### JavaScript ES6 + Dojo Integration
```javascript
// SessionManager moderno
class SessionManager {
    async checkSessionStatus() {
        const response = await fetch('session_status.php', {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-cache'
        });
        return response.json();
    }
}

// Integração com Dojo (legacy)
dojo.addOnLoad(function() {
    // Código Dojo aqui
});
```

## Arquitetura de Sessão e Autenticação

### Fluxo de Autenticação OAuth2
1. **index.php**: Gerencia login/logout e redirecionamento OAuth
2. **session_status.php**: Endpoint AJAX para validação de sessão
3. **session-manager.js**: Monitoramento client-side contínuo
4. **SessionManager.php**: Gerenciamento seguro server-side

### Estrutura de Sessão
```php
$_SESSION = [
    'oauth_token' => 'user_access_token',
    'user_data' => [
        'firstName' => 'Nome',
        'lastName' => 'Sobrenome',
        'id' => 'user_id',
        'checkins' => ['count' => 1234]
    ]
];
```

## Componentes-Chave do Sistema

### 1. Gerenciamento de Venues

**Arquivos Principais:**
- `main.php`: Interface de busca e configuração
- `load.php`: Carregamento de dados de venues
- `edit.php`: Interface de edição em massa
- `js/4sq.js`: Lógica de manipulação de venues

**Operações Principais:**
```javascript
// Funções principais em 4sq.js
function carregarDadosVenues()    // Carrega dados da API
function salvarVenues()           // Salva edições via API  
function sinalizarVenues()        // Flag venues com problemas
function xmlhttpRequest()         // Comunicação AJAX com API
```

### 2. Interface Dojo Toolkit

**Componentes UI:**
```javascript
dojo.require("dijit.Dialog");
dojo.require("dijit.form.Form");
dojo.require("dijit.layout.AccordionContainer");

// Exemplo de uso
var dialog = new dijit.Dialog({
    title: "Editar Campo",
    style: "width: 570px"
});
```

### 3. Sistema de Categorias
```php
function carregarListaCategorias() {
    $foursquare = new FoursquareApi($clientKey, $clientSecret);
    $foursquare->SetAccessToken($token);
    $response = $foursquare->GetPrivate("venues/categories");
    // Cache local para performance
}
```

## Padrões de Desenvolvimento

### Headers Anti-Cache (Crítico)
```php
// SEMPRE inclua em páginas dinâmicas:
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
```

### Tratamento de Erros API
```javascript
// Padrão para requests da API Foursquare
function xmlhttpRequest(metodo, endpoint, acao, dados, i) {
    // Tratamento por status code:
    switch (xmlhttp.status) {
        case 400: // Bad Request
        case 401: // Unauthorized  
        case 403: // Forbidden
        case 404: // Not Found
        case 429: // Rate Limited
        case 500: // Server Error
    }
}
```

### Segurança e Validação
```php
// Validação de entrada
$code = filter_var($_GET['code'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Regeneração de session ID
if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}
```

## Desenvolvimento e Debug
- Utilize o script `.dev.sh` para iniciar o ambiente de desenvolvimento com Docker.

### Docker Development Environment
```bash
# Iniciar ambiente de desenvolvimento
docker build -t foursquare-tools .
docker run -p 8080:80 -v $(pwd):/var/www/html foursquare-tools

# Logs de debug
docker logs container_name
```

### Debug de Sessão
- **test_session_debug.html**: Interface de teste de sessão
- **session_status.php**: Endpoint de verificação
- **create_test_session.php**: Criação de sessões de teste

### Estrutura de Logs
```javascript
// Padrão de logging
console.log('🔧 SessionManager: Inicializando...');
console.warn('⚠️ Token expirado');
console.error('❌ Erro na autenticação:', error);
```

## Configuração e Credenciais

### Arquivo de Configuração
```php
// includes/app_credentials.php
return [
    'client_key' => 'YOUR_CLIENT_ID',
    'client_secret' => 'YOUR_CLIENT_SECRET', 
    'redirect_uri' => 'http://localhost:8080/index.php'
];
```

### Variáveis de Ambiente
```bash
FOURSQUARE_CLIENT_ID=your_client_id
FOURSQUARE_CLIENT_SECRET=your_client_secret
FOURSQUARE_REDIRECT_URI=http://localhost:8080/index.php
```

## Workflows de Edição

### Processo de Edição em Massa
1. **Upload CSV** → `load_csv.php`
2. **Busca por Coordenadas** → `search.php`
3. **Carregamento de Dados** → `load.php`
4. **Edição em Interface** → `edit.php`
5. **Salvamento via API** → `4sq.js`

### Campos Editáveis
```javascript
// Campos disponíveis para edição em massa
const EDITABLE_FIELDS = [
    'name', 'address', 'crossStreet', 'neighborhood',
    'city', 'state', 'zip', 'phone', 'url', 
    'twitter', 'facebook', 'instagram', 'categoryId'
];
```

## Boas Práticas

### Performance
- **Cache local**: Categorias e dados de usuário
- **Lazy loading**: Carregamento sob demanda de scripts
- **Batch operations**: Agrupamento de requisições API

### Manutenabilidade
- **Separação de responsabilidades**: PSR-4 vs Legacy
- **Documentação inline**: JSDoc e PHPDoc
- **Versionamento**: Semantic versioning

### Segurança
- **Input sanitization**: Filter_var em todas as entradas
- **CSRF protection**: Tokens de sessão
- **HTTPS enforcement**: Headers seguros

## Debugging e Troubleshooting

### Problemas Comuns

**1. Token Expirado:**
```javascript
// Verificar em 4sq.js
if (oauth_token == undefined) {
    console.warn("Token expirado");
    // Redirecionar para index.php
}
```

**2. Métodos API Incorretos:**
```php
// CORRETO:
$foursquare->GetPrivate("users/self");

// INCORRETO:
$foursquare->getPrivate("users/self");
```

**3. Headers já enviados:**
```php
// Sempre verificar antes de setcookie
if (!headers_sent()) {
    setcookie("name", $value, $expires);
}
```

## Extensibilidade

### Adicionando Novos Endpoints
```php
// Seguir padrão existente
class NewApiEndpoint {
    public function __construct(private FoursquareApi $api) {}
    
    public function processRequest(): array {
        return $this->api->GetPrivate("new/endpoint");
    }
}
```

### Customização de Interface
```javascript
// Extender SessionManager
class ExtendedSessionManager extends SessionManager {
    customMethod() {
        // Nova funcionalidade
    }
}
```

## Persona do Copilot
- Você é um programador experiente e prestativo que orienta os usuários e constrói ferramentas úteis de edição de múltiplos locais aproveitando todo o potencial da API do Foursquare.
- Fornece sugestões com base no contexto do repositório e nas informações do usuário.
- Garante que as instruções geradas sejam claras, concisas e acionáveis.
- Adapta-se ao feedback do usuário e refina as instruções iterativamente.
- SEMPRE pergunte ao usuário se há algo mais que ele gostaria de adicionar ou modificar antes de encerrar a interação.

## Diretrizes de Performance
- É fundamental manter o alto desempenho.
- Otimizar algoritmos e estruturas de dados para maior eficiência.
- Evitar cálculos e uso de memória desnecessários.

## Saúde da Aplicação
- Sistema completamente modernizado (PHP 8.1) e funcional.
- Espera-se que o código seja limpo, bem estruturado e sustentável.
- Seguir as melhores práticas de qualidade e manutenção do código.
- Manter compatibilidade entre componentes PSR-4 modernos e legacy.

---

**Gerado por AI como orientação por Elio Gavlinski em 2024-12-28**