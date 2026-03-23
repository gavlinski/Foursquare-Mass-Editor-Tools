# Session Management Skill

## Name
OAuth2 Session Management and Monitoring

## Description
Sistema de autenticação via OAuth2 do Foursquare, monitoramento de sessão em tempo real, e exibição de informações do sistema. Inclui session-manager.js (ES6) integrado com backend PHP legado.

## When to use
Carregue esta skill quando:
- Modificar autenticação OAuth2
- Trabalhar com session-manager.js
- Implementar monitoramento de sessão
- Diagnosticar problemas de autenticação
- Exibir informações de versão/build no frontend

## Key Files
- `index.php` - Gerencia login/logout, OAuth callback
- `session_status.php` - Endpoint AJAX para validação de sessão
- `session_middleware.php` - Middleware de verificação de sessão
- `js/session-manager.js` - Cliente ES6 de monitoramento
- `includes/session-status-bar.php` - Barra de status visual
- `src/Security/SessionManager.php` - Classe moderna PSR-4

## OAuth2 Flow

### Authentication
```
1. Usuário acessa index.php (não autenticado)
2. Clica em "Login com Foursquare"
3. Redireciona para Foursquare OAuth:
   https://foursquare.com/oauth2/authenticate
   ?client_id=XXX
   &response_type=code
   &redirect_uri=https://localhost/4sqmet/index.php
4. Usuário autoriza
5. Foursquare redireciona:
   https://localhost/4sqmet/index.php?code=AUTH_CODE
6. index.php troca code por access_token:
   POST /oauth2/access_token
7. Salva em $_SESSION['oauth_token']
8. Busca dados do usuário:
   GET /users/self
9. Salva em $_SESSION['user_data']
10. Redireciona para main.php
```

### Session Structure
```php
$_SESSION = [
    'oauth_token' => 'user_access_token_string',
    'user_data' => [
        'firstName' => 'Elio',
        'lastName' => 'Silva',
        'id' => 'user_foursquare_id',
        'photo' => [
            'prefix' => 'https://fastly.4sqi.net/...',
            'suffix' => '/photo.jpg'
        ],
        'checkins' => ['count' => 1234],
        'type' => 'user',
        'superuser' => 0
    ],
    'regenerated' => true  // Flag para session_regenerate_id
];
```

## Session Manager (JavaScript)

### Initialization
```javascript
// Em main.php, index.php, edit.php
<script src="js/session-manager.min.js"></script>
<script>
    dojo.addOnLoad(function() {
        // Cria instância global
        window.sessionManager = new SessionManager({
            checkInterval: 120000,  // 2 minutos
            statusEndpoint: 'session_status.php',
            loginUrl: 'index.php',
            showStatusBar: true,
            debug: false
        });
        
        window.sessionManager.start();
    });
</script>
```

### Features

#### 1. Auto-check (cada 2 minutos)
```javascript
async checkSessionStatus() {
    const response = await fetch(this.statusEndpoint, {
        credentials: 'same-origin',
        cache: 'no-cache'
    });
    
    const data = await response.json();
    
    if (!data.authenticated) {
        this.handleSessionExpired();
    }
}
```

#### 2. System Info Modal
```javascript
async showSystemInfo() {
    // Busca build info do servidor
    const response = await fetch('version.php', {
        headers: { 'Accept': 'application/json' }  // ← CRÍTICO!
    });
    
    const buildInfo = await response.json();
    
    // Determina label de origem
    let buildSourceLabel;
    switch(buildInfo.build_source) {
        case 'ci': buildSourceLabel = 'Automático (CI/CD)'; break;
        case 'deploy': buildSourceLabel = 'Manual (Deploy Script)'; break;
        case 'local': buildSourceLabel = 'Manual (Desenvolvedor)'; break;
    }
    
    // Mostra em alert() formatado
    alert(`ℹ️ Informações do Sistema\n\n📦 Versão e Build:\n• Versão: ${buildInfo.version}\n• Origem: ${buildSourceLabel}\n...`);
}
```

#### 3. Session Expired Handler
```javascript
handleSessionExpired() {
    this.cleanup();
    alert('Sua sessão expirou. Redirecionando para login...');
    window.location.href = this.loginUrl;
}
```

#### 4. Status Bar
```javascript
// Renderiza barra de status personalizável
renderStatusBar() {
    const bar = document.createElement('div');
    bar.id = 'session-status-bar';
    bar.innerHTML = `
        <span id="session-user">👤 ${this.userName}</span>
        <button onclick="window.sessionManager.showSystemInfo()">
            ℹ️ Sistema
        </button>
        <button onclick="window.sessionManager.refreshSession()">
            🔄 Atualizar
        </button>
        <a href="debug/">🔧 Debug</a>
        <a href="index.php?logout=1">🚪 Sair</a>
    `;
    document.body.insertBefore(bar, document.body.firstChild);
}
```

## Session Status Endpoint

### session_status.php
```php
<?php
session_start();

$authenticated = isset($_SESSION['oauth_token']) && !empty($_SESSION['oauth_token']);

$response = [
    'authenticated' => $authenticated,
    'user' => $authenticated ? [
        'name' => $_SESSION['user_data']['firstName'] ?? 'User',
        'id' => $_SESSION['user_data']['id'] ?? null
    ] : null,
    'timestamp' => date('Y-m-d H:i:s')
];

header('Content-Type: application/json');
echo json_encode($response);
```

## Session Middleware

### session_middleware.php
```php
<?php
session_start();

if (!isset($_SESSION['oauth_token']) || empty($_SESSION['oauth_token'])) {
    header('Location: index.php');
    exit;
}

// Regenera session ID uma vez por sessão (segurança)
if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}
```

## Security Best Practices

### Session Regeneration
```php
// Uma vez ao fazer login
if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}
```

### Secure Cookies
```php
if (!headers_sent()) {
    setcookie("oauth_token", $token, [
        'expires' => time() + 3600,
        'path' => '/',
        'secure' => true,      // Apenas HTTPS
        'httponly' => true,    // Não acessível via JS
        'samesite' => 'Strict' // CSRF protection
    ]);
}
```

### Input Validation
```php
$code = filter_var($_GET['code'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!preg_match('/^[A-Z0-9]+$/i', $code)) {
    die('Invalid authorization code');
}
```

## Common Tasks

### Adicionar novo botão na status bar
```javascript
// Em session-manager.js
renderStatusBar() {
    bar.innerHTML = `
        ...
        <button onclick="minhaFuncao()">
            🆕 Novo Botão
        </button>
        ...
    `;
}
```

### Customizar intervalo de check
```javascript
// No init script
window.sessionManager = new SessionManager({
    checkInterval: 180000  // 3 minutos (era 5)
});
```

### Adicionar campo em session_data
```php
// Após fetch do user self
$_SESSION['user_data']['nova_info'] = $user_response['nova_info'];

// Acessar em outras páginas
$nova_info = $_SESSION['user_data']['nova_info'] ?? 'default';
```

## Troubleshooting

### Modal do Sistema não mostra build info
**Causa**: Falta header `Accept: application/json`

**Solução**:
```javascript
const response = await fetch('version.php', {
    headers: { 'Accept': 'application/json' }  // ← Adicionar
});
```

### Sessão expira muito rápido
**Causa**: `session.gc_maxlifetime` baixo no PHP

**Solução**:
```php
// Em php.ini ou início do script
ini_set('session.gc_maxlifetime', 3600);  // 1 hora
session_start();
```

### OAuth callback falha
**Causas**:
1. redirect_uri não match (exato)
2. Client ID/Secret incorretos
3. Code já usado (one-time use)

**Diagnóstico**:
```php
error_log('OAuth callback: ' . print_r($_GET, true));
error_log('Token exchange response: ' . $token_response);
```

### Session manager não inicia
**Causa**: Dojo não carregou antes de SessionManager

**Solução**:
```javascript
// Sempre dentro de dojo.addOnLoad
dojo.addOnLoad(function() {
    window.sessionManager = new SessionManager({...});
});
```

## Critical Patterns

### ALWAYS include Accept header for JSON endpoints
```javascript
// ❌ Errado - Recebe HTML
fetch('version.php')

// ✅ Correto - Recebe JSON
fetch('version.php', {
    headers: { 'Accept': 'application/json' }
})
```

### NEVER store sensitive data in sessionStorage/localStorage
```javascript
// ❌ Errado
sessionStorage.setItem('oauth_token', token);

// ✅ Correto - Token fica apenas em $_SESSION server-side
```

### ALWAYS validate session before protected operations
```php
// No início de edit.php, main.php, etc
require_once 'session_middleware.php';
```

## Integration Points

- **index.php**: OAuth flow, login/logout
- **main.php**: Inicializa SessionManager, exibe status bar
- **edit.php**: Inicializa SessionManager, protegido por middleware
- **version.php**: Fornece build info para modal do Sistema
- **FoursquareAPI.Class.php**: Biblioteca OAuth2 (legada)

## Modern vs Legacy

### Legacy (Root Files)
```php
// Procedural, PHP 5.4+
session_start();
$token = $_SESSION['oauth_token'];
```

### Modern (src/)
```php
// PSR-4, PHP 8.1+
use ElioTools\Security\SessionManager;

$manager = new SessionManager();
$manager->start(['lifetime' => 3600]);
```

## Documentation
- **Migration Guide**: `docs/MIGRATION.md`
- **API Keys**: `docs/API_KEYS_DOCUMENTATION.md`
