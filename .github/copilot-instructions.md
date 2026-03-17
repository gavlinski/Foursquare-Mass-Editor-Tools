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

## 🏗️ Build System & Version Tracking

### Build Info System
The project uses `build-info.json` to track build metadata. This file is:
- **Generated** by build scripts (not committed to git)
- **Environment-specific** (different in local vs CI/CD)
- **Read by** `version.php` and `js/session-manager.js`

```json
{
  "build_date": "2026-03-05T01:28:36Z",
  "build_timestamp": 1772674116,
  "commit_hash": "full_commit_hash",
  "commit_short": "short_hash",
  "branch": "refactor-ia",
  "version": "4SQMET-02_03_00-117-gddcf206",
  "build_source": "local|deploy|ci",
  "environment": "production"
}
```

### Build Source Values
The `build_source` field indicates WHO triggered the build:

- **`"local"`** - Developer ran `./build.sh` manually in their machine
- **`"deploy"`** - Developer ran `./deploy.sh` for manual production deploy
- **`"ci"`** - GitHub Actions automatic build (CI/CD pipeline)

**Detection Logic** (in build.sh and build-docker.sh):
```bash
if [ -z "$BUILD_SOURCE" ]; then
    if [ "$CI" = "true" ] || [ "$GITHUB_ACTIONS" = "true" ] || [ -n "$CI_COMMIT_SHA" ]; then
        BUILD_SOURCE="ci"
    else
        BUILD_SOURCE="local"
    fi
fi
```

### Build Scripts
- **`build.sh`**: Main orchestrator, detects npm availability
  - If npm absent → Uses Docker container (node:22-alpine)
  - If npm present → Local build with Terser
  - Detects CI environment automatically
  - Sets BUILD_SOURCE before generating build-info.json

- **`build-docker.sh`**: Runs inside Docker container
  - Receives BUILD_SOURCE via environment variable
  - Falls back to CI detection if not set
  - Generates build-info.json inside container

- **`deploy.sh`**: Manual production deployment
  - Sets `export BUILD_SOURCE="deploy"` before build
  - Runs build.sh to generate artifacts
  - SSH to server, git pull, composer install
  - Restarts Apache service

### Critical: build-info.json in CI/CD
**IMPORTANT**: `build-info.json` is in `.gitignore` and must be transferred as artifact:

```yaml
# .github/workflows/deploy.yml
# Job: build
- name: Upload build artifacts
  path: |
    js/*.min.js
    js/*.min.js.map
    build-info.json  # ← CRITICAL: Must include this

# Job: deploy
- name: Download build artifacts
  path: .  # ← CRITICAL: Root path keeps structure

- name: Deploy to server
  run: |
    # CRITICAL: SCP artifacts BEFORE deploy.sh
    scp build-info.json ${DEPLOY_USER}@${DEPLOY_HOST}:/var/www/4sqmet/
    scp js/*.min.js ${DEPLOY_USER}@${DEPLOY_HOST}:/var/www/4sqmet/js/
    bash deploy.sh
```

**Why this is critical:**
1. `build-info.json` is generated during CI/CD build with correct Git metadata
2. File is NOT in Git repository (ignored)
3. `git pull` on server won't bring the file
4. Must be copied explicitly via SCP before Docker build
5. Without it, `version.php` uses fallback values ("Development", "dev", etc.)

### Environment Field
The `"environment": "production"` field indicates:
- **Build type** (minified/production build)
- **NOT runtime environment** (local vs production server)
- Always "production" because builds are always minified
- To check runtime environment, use `$_SERVER['SERVER_NAME']` in PHP

## 📊 Version Endpoint & System Info

### version.php - Content Negotiation
The `version.php` file serves both HTML and JSON based on request headers:

```php
// Detect format
$format = $_GET['format'] ?? '';
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';

if ($format === 'json' || strpos($acceptHeader, 'application/json') !== false) {
    // Return JSON
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Return HTML
    header('Content-Type: text/html; charset=utf-8');
    // ... HTML page ...
}
```

### API Usage
```bash
# HTML (browser or default)
https://localhost/version.php

# JSON (with Accept header)
curl -H "Accept: application/json" https://localhost/version.php

# JSON (with query parameter)
curl https://localhost/version.php?format=json
```

### System Info Modal (Session Manager)
The session manager displays system info by fetching version.php:

```javascript
async showSystemInfo() {
    try {
        // CRITICAL: Must include Accept header
        const response = await fetch('version.php', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'  // ← Without this, returns HTML
            },
            credentials: 'same-origin',
            cache: 'no-cache'
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        // Display modal with version + client info
    } catch (error) {
        console.error('❌ Erro ao buscar informações do sistema:', error);
        // Fallback: show only client info
    }
}
```

**Common Bug**: Forgetting `Accept: application/json` header causes:
- Server returns HTML instead of JSON
- JSON.parse() fails
- Modal shows only client info (missing version section)

### Build Source Labels & Badges
Display labels in both version.php (HTML) and session-manager.js (modal):

| build_source | Label (HTML & Modal) | Badge Color |
|--------------|---------------------|-------------|
| `"ci"` | Automático (CI/CD) | Green (#d4edda) |
| `"deploy"` | Manual (Deploy Script) | Yellow (#fff3cd) |
| `"local"` | Manual (Desenvolvedor) | Gray (#e9ecef) |

**Implementation**:
```javascript
// session-manager.js
let buildSourceLabel = 'Desconhecido';
const buildSource = buildInfo.build_source || 'unknown';

switch(buildSource) {
    case 'ci':
        buildSourceLabel = 'Automático (CI/CD)';
        break;
    case 'deploy':
        buildSourceLabel = 'Manual (Deploy Script)';
        break;
    case 'local':
        buildSourceLabel = 'Manual (Desenvolvedor)';
        break;
}
```

## 🐛 Debug Tools & Scroll Preservation

### Debug Directory Structure
**ALWAYS use consolidated debug tools** in `debug/` folder:
- `debug/index.php` - Main debug tools index with scroll preservation
- `debug/session_test_manager.php` - Session creation/validation
- `debug/debug_session.php` - Session debugging endpoint
- `debug/test_session_debug.html` - Session UI testing
- `debug/css_test_interface.php` - CSS testing tool
- `debug/test_integration_markers.html` - Google Maps integration testing

**NEVER create temporary debug files in root directory.**

### Scroll Preservation System
The debug tools index preserves scroll position when navigating away and back:

**Problem**: User scrolls to bottom of debug index → clicks link → returns → scroll resets to top

**Solution**: sessionStorage + multi-retry restoration strategy

#### Implementation Pattern

**Step 1: Save scroll position on links LEAVING debug/index.php**
```html
<a href="test_session_debug.html" 
   onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
    Test Session Debug
</a>
```

**Step 2: Restore scroll position when RETURNING to debug/index.php**
```javascript
// IIFE at bottom of debug/index.php (after DOM fully loaded)
(function() {
    const savedScrollPos = sessionStorage.getItem('debugIndexScrollPos');
    
    if (savedScrollPos) {
        const scrollPos = parseInt(savedScrollPos);
        
        // Multi-retry strategy for reliability
        window.scrollTo(0, scrollPos);  // Immediate
        
        setTimeout(() => window.scrollTo(0, scrollPos), 50);   // After 50ms
        setTimeout(() => window.scrollTo(0, scrollPos), 100);  // After 100ms
        setTimeout(() => window.scrollTo(0, scrollPos), 500);  // After 500ms
        
        // Cleanup
        sessionStorage.removeItem('debugIndexScrollPos');
    }
})();
```

#### Standardized Footer Navigation
All debug test pages must include standardized footer:

```html
<footer style="text-align: center; padding: 30px 20px; margin-top: 40px; border-top: 2px solid #e0e0e0; background: white;">
    <a href="index.php" 
       style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 24px; 
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
              color: white; text-decoration: none; border-radius: 8px; 
              font-weight: 600; transition: transform 0.2s, box-shadow 0.2s; 
              box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        ← Voltar para Debug
    </a>
</footer>
```

#### Critical Rules
- ✅ **Save scroll** on links LEAVING debug/index.php
- ❌ **DO NOT save** on back links (would overwrite with scroll=0)
- ✅ **Multi-retry** restoration (50ms, 100ms, 500ms delays)
- ✅ **Cleanup** sessionStorage after restoration
- ✅ **Standardize** all test pages with footer navigation
- ❌ **DO NOT duplicate** back links at top (creates confusion)

## 🚀 Deployment Workflows

### When to Use deploy.sh vs CI/CD

**Prefer CI/CD (GitHub Actions)** for normal workflow:
```bash
git add .
git commit -m "feat: new feature"
git push origin refactor-ia
# Wait ~10-15 min for automatic deployment
```

**Use deploy.sh for these specific scenarios**:

1. **CI/CD Down/Broken** - GitHub Actions failing, need immediate deploy
2. **Hotfix Urgent** - Critical fix needs deploy in 2-3min (CI/CD queue ~10-15min)
3. **New Server Setup** - First deploy before CI/CD configured
4. **Staging Environment** - Testing server without CI/CD integration
5. **Expired GitHub Secrets** - SSH keys expired, use local credentials temporarily

### Deploy Script Limitations
The current `deploy.sh`:
- ✅ Always does `git pull origin refactor-ia` (fixed branch)
- ❌ Cannot choose different branch
- ❌ Cannot rollback to specific commit
- ❌ Cannot deploy uncommitted local files
- ❌ Cannot send package/zip file
- ❌ Requires push before deploy (doesn't transfer local files)

**For advanced deploy scenarios**, see `docs/TODO_DEPLOY_ADVANCED.md`:
- Branch-specific deploys
- Commit-specific rollbacks
- Package local uncommitted files
- Hotfix specific files only
- Emergency rollback procedures

### Correct Manual Deploy Workflow
```bash
# 1. Make changes locally
git add .
git commit -m "fix: critical bug"

# 2. Push to repository (REQUIRED)
git push origin refactor-ia

# 3. Run deploy script (pulls from repository)
./deploy.sh

# Script does:
# - export BUILD_SOURCE="deploy"
# - ./build.sh (generates minified files)
# - SSH to server
# - git pull origin refactor-ia
# - composer install --no-dev
# - Set permissions (www-data:www-data)
# - Restart Apache
```

## 🔐 CI/CD Configuration & Variable Management

### Secrets vs Repository Variables
The workflow centralizes configuration for maximum security and maintainability:

**Secrets** (sensitive - encrypted):
- `DEPLOY_SSH_KEY` - SSH private key (uses `ssh-agent` action)
- `DEPLOY_USER` - SSH user (usually `root`)
- `FOURSQUARE_CLIENT_KEY`, `FOURSQUARE_CLIENT_SECRET`, `FOURSQUARE_REDIRECT_URI` - API credentials
- `GOOGLE_MAPS_API_KEY`, `GOOGLE_MAPS_MAP_ID`, `GOOGLE_MAPS_GEOCODING_KEY` - Maps credentials
- `APP_URL` - Application URL derived from `PRODUCTION_URL` in workflow

**Repository Variables** (non-sensitive - visible):
- `DEPLOY_HOST` - Server IP (e.g., `134.209.163.143`) - can be IP or domain

### Configuration Centralization Pattern
```yaml
env:
  DEPLOY_HOST: ${{ vars.DEPLOY_HOST }}        # From Repository Variable
  PRODUCTION_URL: ${{ secrets.APP_URL }}      # From Secret
  PRODUCTION_DOMAIN: '4sq.eliotools.site'     # Fallback domain only

jobs:
  deploy:
    steps:
      - name: Deploy
        env:
          DEPLOY_HOST: ${{ env.DEPLOY_HOST }}  # Reuse from workflow env
          APP_URL: ${{ env.PRODUCTION_URL }}   # Alias to avoid duplication
```

**Benefits:**
1. No duplicate values stored in multiple places
2. Single source of truth for each config item
3. Automatic propagation of changes
4. Clear separation: vars = config, secrets = credentials

## 🛡️ Security Hardening Practices

### Application-Level Filtering
The application filters malicious traffic at multiple layers:

**`analytics.php` - Incoming request filtering:**
```php
// 1. Bot/Scanner UA detection
function isIgnoredUserAgent(string $ua): bool {
    return preg_match('/googlebot|bingbot|curl|wget/i', $ua);
}

// 2. Suspicious request patterns
function isSuspiciousRequest(string $path, string $query): bool {
    return preg_match('/_ignition|xdebug|pearcmd|invokefunction|\.\.%2f/i', $path.$query);
}

// 3. Tracked path whitelist
const TRACKED_PATHS = ['/', '/main.php', '/index.php', /* ... */];

// 4. Only GET/HEAD methods
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'])) {
    return;
}
```

**`migrate_analytics_data.php` - Historical cleanup:**
- Same filtering logic retroactively applied
- Removes noisy bot/scanner entries
- Uses same whitelist and suspicious detection

### Apache Edge Blocking
Rewrite rules in both `apache-config.conf` and `apache-config-production.conf`:

```apache
# Block common exploit patterns
RewriteRule "(_ignition|xdebug|pearcmd|invokefunction)" - [F,L]

# Block path traversal variations
RewriteRule "\.\./|\.\.%2f" - [F,L]

# Returns HTTP 403 Forbidden for blocked requests
```

**Deployment persistence:**
- Rules synchronized in both vhosts
- Must be reapplied after server restart
- Covered in `deploy.sh` pre-check and `docker-entrypoint.sh`

### Server Safety Mechanisms
**`deploy.sh` - Dirty state handling:**
```bash
# Pre-deploy checks
git status --porcelain > /tmp/pre_sync_status.txt
git diff > /tmp/pre_sync_diff.txt
git diff --cached > /tmp/pre_sync_staged.txt

# Automatic stash before pull
git stash push -m "pre-sync-backup-$(date +%s)"

# Prevents deploy failure due to uncommitted changes
```

**`docker-entrypoint.sh` - Persistent configuration:**
```bash
# Suppress Apache AH00558 warning (ServerName required)
SERVER_NAME=$(printf '%s\n' "$APP_URL" | sed 's#^[a-zA-Z]*://\([^/:]*\).*#\1#')
# Write to Apache config with global ServerName directive
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

## 📊 Version Endpoint & System Info

### version.php - Content Negotiation
The `version.php` file serves both HTML and JSON based on request headers:

```php
// Detect format
$format = $_GET['format'] ?? '';
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';

if ($format === 'json' || strpos($acceptHeader, 'application/json') !== false) {
    // Return JSON
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Return HTML
    header('Content-Type: text/html; charset=utf-8');
    // ... HTML page ...
}
```

### API Usage
```bash
# HTML (browser or default)
https://localhost/version.php

# JSON (with Accept header)
curl -H "Accept: application/json" https://localhost/version.php

# JSON (with query parameter)
curl https://localhost/version.php?format=json
```

### System Info Modal (Session Manager)
The session manager displays system info by fetching version.php:

```javascript
async showSystemInfo() {
    try {
        // CRITICAL: Must include Accept header
        const response = await fetch('version.php', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'  // ← Without this, returns HTML
            },
            credentials: 'same-origin',
            cache: 'no-cache'
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        // Display modal with version + client info
    } catch (error) {
        console.error('❌ Erro ao buscar informações do sistema:', error);
        // Fallback: show only client info
    }
}
```

**Common Bug**: Forgetting `Accept: application/json` header causes:
- Server returns HTML instead of JSON
- JSON.parse() fails
- Modal shows only client info (missing version section)

### Build Source Labels & Badges
Display labels in both version.php (HTML) and session-manager.js (modal):

| build_source | Label (HTML & Modal) | Badge Color |
|--------------|---------------------|-------------|
| `"ci"` | Automático (CI/CD) | Green (#d4edda) |
| `"deploy"` | Manual (Deploy Script) | Yellow (#fff3cd) |
| `"local"` | Manual (Desenvolvedor) | Gray (#e9ecef) |

**Implementation**:
```javascript
// session-manager.js
let buildSourceLabel = 'Desconhecido';
const buildSource = buildInfo.build_source || 'unknown';

switch(buildSource) {
    case 'ci':
        buildSourceLabel = 'Automático (CI/CD)';
        break;
    case 'deploy':
        buildSourceLabel = 'Manual (Deploy Script)';
        break;
    case 'local':
        buildSourceLabel = 'Manual (Desenvolvedor)';
        break;
}
```

## 🐛 Debug Tools & Scroll Preservation

### Debug Directory Structure
**ALWAYS use consolidated debug tools** in `debug/` folder:
- `debug/index.php` - Main debug tools index with scroll preservation
- `debug/session_test_manager.php` - Session creation/validation
- `debug/debug_session.php` - Session debugging endpoint
- `debug/test_session_debug.html` - Session UI testing
- `debug/css_test_interface.php` - CSS testing tool
- `debug/test_integration_markers.html` - Google Maps integration testing

**NEVER create temporary debug files in root directory.**

### Scroll Preservation System
The debug tools index preserves scroll position when navigating away and back:

**Problem**: User scrolls to bottom of debug index → clicks link → returns → scroll resets to top

**Solution**: sessionStorage + multi-retry restoration strategy

#### Implementation Pattern

**Step 1: Save scroll position on links LEAVING debug/index.php**
```html
<a href="test_session_debug.html" 
   onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
    Test Session Debug
</a>
```

**Step 2: Restore scroll position when RETURNING to debug/index.php**
```javascript
// IIFE at bottom of debug/index.php (after DOM fully loaded)
(function() {
    const savedScrollPos = sessionStorage.getItem('debugIndexScrollPos');
    
    if (savedScrollPos) {
        const scrollPos = parseInt(savedScrollPos);
        
        // Multi-retry strategy for reliability
        window.scrollTo(0, scrollPos);  // Immediate
        
        setTimeout(() => window.scrollTo(0, scrollPos), 50);   // After 50ms
        setTimeout(() => window.scrollTo(0, scrollPos), 100);  // After 100ms
        setTimeout(() => window.scrollTo(0, scrollPos), 500);  // After 500ms
        
        // Cleanup
        sessionStorage.removeItem('debugIndexScrollPos');
    }
})();
```

#### Standardized Footer Navigation
All debug test pages must include standardized footer:

```html
<footer style="text-align: center; padding: 30px 20px; margin-top: 40px; border-top: 2px solid #e0e0e0; background: white;">
    <a href="index.php" 
       style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 24px; 
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
              color: white; text-decoration: none; border-radius: 8px; 
              font-weight: 600; transition: transform 0.2s, box-shadow 0.2s; 
              box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        ← Voltar para Debug
    </a>
</footer>
```

#### Critical Rules
- ✅ **Save scroll** on links LEAVING debug/index.php
- ❌ **DO NOT save** on back links (would overwrite with scroll=0)
- ✅ **Multi-retry** restoration (50ms, 100ms, 500ms delays)
- ✅ **Cleanup** sessionStorage after restoration
- ✅ **Standardize** all test pages with footer navigation
- ❌ **DO NOT duplicate** back links at top (creates confusion)

## 🚀 Deployment Workflows

### When to Use deploy.sh vs CI/CD

**Prefer CI/CD (GitHub Actions)** for normal workflow:
```bash
git add .
git commit -m "feat: new feature"
git push origin refactor-ia
# Wait ~10-15 min for automatic deployment
```

**Use deploy.sh for these specific scenarios**:

1. **CI/CD Down/Broken** - GitHub Actions failing, need immediate deploy
2. **Hotfix Urgent** - Critical fix needs deploy in 2-3min (CI/CD queue ~10-15min)
3. **New Server Setup** - First deploy before CI/CD configured
4. **Staging Environment** - Testing server without CI/CD integration
5. **Expired GitHub Secrets** - SSH keys expired, use local credentials temporarily

### Deploy Script Limitations
The current `deploy.sh`:
- ✅ Always does `git pull origin refactor-ia` (fixed branch)
- ❌ Cannot choose different branch
- ❌ Cannot rollback to specific commit
- ❌ Cannot deploy uncommitted local files
- ❌ Cannot send package/zip file
- ❌ Requires push before deploy (doesn't transfer local files)

**For advanced deploy scenarios**, see `docs/TODO_DEPLOY_ADVANCED.md`:
- Branch-specific deploys
- Commit-specific rollbacks
- Package local uncommitted files
- Hotfix specific files only
- Emergency rollback procedures

### Correct Manual Deploy Workflow
```bash
# 1. Make changes locally
git add .
git commit -m "fix: critical bug"

# 2. Push to repository (REQUIRED)
git push origin refactor-ia

# 3. Run deploy script (pulls from repository)
./deploy.sh

# Script does:
# - export BUILD_SOURCE="deploy"
# - ./build.sh (generates minified files)
# - SSH to server
# - git pull origin refactor-ia
# - composer install --no-dev
# - Set permissions (www-data:www-data)
# - Restart Apache
```

## 📚 Additional Resources

### Documentation Files
- **Migration Guide**: `docs/MIGRATION.md`
- **Google Maps Migration**: `docs/GOOGLE_MAPS_MIGRATION.md`
- **API Keys Documentation**: `docs/API_KEYS_DOCUMENTATION.md`
- **Build & Deploy Guide**: `docs/BUILD_AND_DEPLOY.md`
- **Advanced Deploy Modes (TODO)**: `docs/TODO_DEPLOY_ADVANCED.md`

### GitHub Copilot Agent Skills
For topic-specific deep knowledge, see `.github/skills/`:
- **build-system**: Build scripts, minification, build_source field
- **deploy-workflows**: dev/manual/CI-CD deployment methods
- **scroll-preservation**: sessionStorage, onclick handlers, restoration
- **session-management**: OAuth2, session monitoring, system info modal
- **version-info**: version.php API, build-info.json, badges
- **debug-tools**: Debug interface, consolidated test tools

---

**Generated by AI as guidance**  
**Last Updated**: January 2026
