# Foursquare Mass Editor Tools - AI Agent Instructions

## 🧠 Project Architecture & Context

### Technology Stack
- **Backend**: PHP 8.1 (PSR-4 in `src/`) + Legacy PHP 5.4+ (root files)
- **Frontend**: Dojo Toolkit v1.8 + ES6 JavaScript
- **API**: Foursquare API v2 with OAuth2
- **Infrastructure**: Docker + Apache 2.4 + Composer

### Hybrid Architecture
```
src/
├── Api/        # Modern PSR-4 API integrations
├── Config/     # Centralized configuration
└── Security/   # Session management (modern)

Root files:     # Legacy procedural code (functional)
├── edit.php    # Main mass editor
├── load.php    # Venue data loader
├── main.php    # Main interface
└── js/4sq.js   # Core venue logic (legacy)
```

## 🛠 Critical Workflows

### Development Environment
```bash
# Start development (builds + installs + runs)
./dev.sh run

# Check status
./dev.sh status

# View logs
docker logs foursquare-mass-editor
```

### Debugging - Use Consolidated Tools
**NEVER create new test files.** Always use `debug/`:
- **Session/Auth**: `debug/session_test_manager.php`, `debug/debug_session.php`
- **UI Testing**: `debug/test_session_debug.html`
- **CSS Testing**: `debug/css_test_interface.php`
- **Maps Integration**: `debug/test_integration_markers.html`

```bash
# Test session
curl "http://localhost/debug/session_test_manager.php?action=create"

# Validate session
curl "http://localhost/debug/debug_session.php?mode=validate"
```

## 📝 Coding Conventions & Critical Patterns

### PHP - Modern (PSR-4)
```php
<?php
declare(strict_types=1);

namespace ElioTools\Security;

class SessionManager {
    public function start(array $options = []): void {
        // Type hints obrigatórios
    }
}
```

### PHP - Legacy (Root Files)
```php
// ALWAYS include anti-cache headers in dynamic pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Use FoursquareAPI methods EXACTLY (PascalCase)
$foursquare->SetAccessToken($token);    // ✅ Correto
$foursquare->GetPrivate("users/self");  // ✅ Correto
$foursquare->setAccessToken($token);    // ❌ ERRADO
```

### Frontend - Dojo Widgets (CRITICAL)

**🚨 Dojo sanitizes external CSS - styling via .css files DOES NOT WORK**

```php
// ❌ NEVER - External CSS is ignored by Dojo
.dijitTextBox { width: 200px !important; }

// ✅ ALWAYS - Inline styles or renderizarCampo()
echo '<input dojoType="dijit.form.TextBox" style="width: 8em; margin-left: 5px;">';

// ✅ ALWAYS - Use helper function
function renderizarCampo(string $tipo, string $name, array $config, int $ajusteInput, int $indice): string {
    $width = $config['width'] + $ajusteInput;
    return '<input type="text" dojoType="dijit.form.TextBox" name="' . htmlspecialchars($name) . '" ' .
           'maxlength="' . $config['maxlength'] . '" placeHolder="' . $config['placeholder'] . '" ' .
           'style="width: ' . $width . 'em; margin-left: 5px;" ' .
           'onchange="verificarAlteracao(this, ' . $indice . ')">' . chr(10);
}
```

### Frontend - Session Management
```javascript
// Modern ES6 SessionManager
class SessionManager {
    async checkSessionStatus() {
        const response = await fetch('session_status.php', {
            credentials: 'same-origin',
            cache: 'no-cache'
        });
        return response.json();
    }
}

// Integration with Dojo (legacy)
dojo.addOnLoad(function inicializar() {
    if (window.sessionManager) {
        window.sessionManager.checkSessionStatus();
    }
});
```

## 🗺️ Key Files Reference

### Core Application
- **`main.php`**: Main interface, search and configuration
- **`edit.php`**: Mass editing interface (uses `renderizarCampo()`)
- **`load.php`**: Venue data loader (CSV, URLs, web pages)
- **`search.php`**: Foursquare API search

### JavaScript Core
- **`js/4sq.js`**: Legacy venue manipulation logic
- **`js/session-manager.js`**: Modern session monitoring (ES6)
- **`js/google-maps.js`**: Google Maps integration (v5.0.1+)
- **`js/main.js`**: Main UI with Dojo

### Configuration
- **`includes/app_credentials.php`**: API keys (reads from `.env`)
- **`includes/google_maps_config.php`**: Google Maps configuration
- **`.env`**: Environment variables (never commit)

### Modern Classes (PSR-4)
- **`src/Config/AppConfig.php`**: Centralized app configuration
- **`src/Security/SessionManager.php`**: Modern session management
- **`src/Api/`**: Modern API integrations

## 🔐 Authentication & Session Flow

### OAuth2 Flow
1. **`index.php`**: Handles login/logout, OAuth redirects
2. **`session_status.php`**: AJAX endpoint for validation
3. **`js/session-manager.js`**: Client-side monitoring (5min intervals)
4. **`src/Security/SessionManager.php`**: Server-side management

### Session Structure
```php
$_SESSION = [
    'oauth_token' => 'user_access_token',
    'user_data' => [
        'firstName' => 'Name',
        'lastName' => 'Last',
        'id' => 'user_id',
        'checkins' => ['count' => 1234]
    ]
];
```

### Security Best Practices
```php
// Input validation
$code = filter_var($_GET['code'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Session regeneration
if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}

// Secure cookies
if (!headers_sent()) {
    setcookie("oauth_token", $value, [
        'expires' => time() + 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}
```

## 🎯 Development Patterns

### API Error Handling
```javascript
function xmlhttpRequest(metodo, endpoint, acao, dados, i) {
    // Handle all Foursquare API status codes
    switch (xmlhttp.status) {
        case 400: // Bad Request
        case 401: // Unauthorized (token expired)
        case 403: // Forbidden
        case 404: // Not Found
        case 429: // Rate Limited
        case 500: // Server Error
        case 504: // Gateway Timeout
    }
}
```

### Logging Pattern
```javascript
console.log('🔧 SessionManager: Inicializando...');
console.warn('⚠️ Token expirado');
console.error('❌ Erro na autenticação:', error);
```

### Configuration Hierarchy
```php
// 1. Environment variables (production)
$client_key = getenv('FOURSQUARE_CLIENT_KEY') ?: 
// 2. Fallback to hardcoded (development)
              "YOUR_FOURSQUARE_CLIENT_KEY";
```

## 📊 Venue Editing Workflow

### Process Flow
1. **Import**: CSV upload (`load_csv.php`) or coordinate search (`search.php`)
2. **Load**: Fetch venue data from API (`load.php`)
3. **Edit**: Mass editing interface (`edit.php` with Dojo widgets)
4. **Save**: Batch API calls (`js/4sq.js` → `xmlhttpRequest()`)

### Editable Fields
```javascript
const EDITABLE_FIELDS = [
    'name', 'address', 'crossStreet', 'neighborhood',
    'city', 'state', 'zip', 'phone', 'url',
    'twitter', 'facebook', 'instagram', 'categoryId',
    'description', 'menu', 'venuell' // lat/lng
];
```

### Core Functions (js/4sq.js)
- **`carregarDadosVenues()`**: Load venue data from API
- **`salvarVenues()`**: Save batch edits
- **`sinalizarVenues()`**: Flag problematic venues
- **`xmlhttpRequest()`**: AJAX communication
- **`retryRequest()`**: Retry failed API calls

## 🚫 Forbidden Actions

### Code Modifications
- ❌ Do NOT modify `FoursquareAPI.Class.php` (vendored legacy library)
- ❌ Do NOT use camelCase API methods (`getPrivate` → use `GetPrivate`)
- ❌ Do NOT create temporary debug scripts in root (use `debug/` folder)
- ❌ Do NOT hardcode credentials (use `.env` variables)

### Styling Restrictions
- ❌ Do NOT try to style Dojo widgets via external CSS files
- ❌ Do NOT use JavaScript to dynamically modify Dojo widget styles
- ❌ Do NOT attempt CSS/JS hybrid systems for widgets

### Development Practices
- ❌ Do NOT skip anti-cache headers in dynamic pages
- ❌ Do NOT create new test files (use consolidated debug tools)
- ❌ Do NOT commit `.env` files or credentials

## 🔧 Troubleshooting Common Issues

### Token Expired
```javascript
// Check in 4sq.js
if (oauth_token == undefined) {
    window.location.href = 'index.php';
}
```

### Headers Already Sent
```php
// Always verify before setting cookies
if (!headers_sent()) {
    setcookie("name", $value, $expires);
}
```

### Dojo Widget Not Styling
```php
// Solution: Use inline styles only
echo '<input dojoType="dijit.form.TextBox" style="width: 10em;">';
```

### Google Maps Not Loading
```javascript
// Check console for Map ID requirement (v3.32+)
// Verify API key restrictions in Google Cloud Console
```

## 📚 Additional Resources

For detailed technical documentation, see:
- **Migration Guide**: `docs/MIGRATION.md`
- **Google Maps Migration**: `docs/GOOGLE_MAPS_MIGRATION.md`
- **API Keys Documentation**: `docs/API_KEYS_DOCUMENTATION.md`

---

**Generated by AI as guidance**  
**Last Updated**: December 2025
