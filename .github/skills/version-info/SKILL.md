# Version Information Display Skill

## Name
Version Endpoint and Build Information System

## Description
Sistema dual de exibição de informações de versão: página HTML user-friendly e API JSON para integração. Mostra build_source, commit info, tempo desde build, versões de PHP/servidor, e badges personalizados.

## When to use
Carregue esta skill quando:
- Modificar version.php (HTML ou JSON response)
- Trabalhar com build-info.json structure
- Customizar badges (cores, labels)
- Diagnosticar inconsistências de versão
- Adicionar novos campos de informação

## Key Files
- `version.php` - Endpoint dual (HTML + JSON API)
- `build-info.json` - Gerado por build.sh (não commitado)
- `build.sh` - Gera build-info.json
- `js/session-manager.js` - Consome JSON API

## Architecture

### Dual Response System

#### Content Negotiation
```php
// Detecta formato desejado
$format = $_GET['format'] ?? '';
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';

if ($format === 'json' || strpos($acceptHeader, 'application/json') !== false) {
    // Retorna JSON
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Retorna HTML
    header('Content-Type: text/html; charset=utf-8');
    // ... render HTML page ...
}
```

#### Usage Examples
```bash
# Browser ou curl normal → HTML
https://localhost/version.php

# Com query parameter → JSON
curl -k https://localhost/version.php?format=json

# Com Accept header → JSON
curl -k -H "Accept: application/json" https://localhost/version.php

# JavaScript fetch → JSON
fetch('version.php', {
    headers: { 'Accept': 'application/json' }
})
```

## build-info.json Structure

### Complete Format
```json
{
  "build_date": "2026-03-05T01:28:36Z",
  "build_timestamp": 1772674116,
  "commit_hash": "ddcf2069c7890a1b2c3d4e5f6a7b8c9d0e1f2a3b",
  "commit_short": "ddcf206",
  "branch": "refactor-ia",
  "version": "4SQMET-02_03_00-117-gddcf206",
  "build_source": "local",
  "environment": "production"
}
```

### Field Meanings

| Field | Type | Description | Example Values |
|-------|------|-------------|----------------|
| `build_date` | ISO 8601 | Timestamp UTC do build | "2026-03-05T01:28:36Z" |
| `build_timestamp` | Unix time | Timestamp numérico | 1772674116 |
| `commit_hash` | String (40) | Hash completo do commit | "ddcf206..." |
| `commit_short` | String (7) | Hash curto (git log --oneline) | "ddcf206" |
| `branch` | String | Nome do branch | "refactor-ia", "main" |
| `version` | String | git describe output | "4SQMET-02_03_00-117-gddcf206" |
| `build_source` | Enum | Quem disparou o build | "local", "deploy", "ci" |
| `environment` | String | Tipo de build | "production" (sempre) |

### build_source Values

| Value | Quando Aparece | Triggered By |
|-------|----------------|--------------|
| `"local"` | Developer roda `./build.sh` manualmente | build.sh com auto-detect (não é CI) |
| `"deploy"` | Deploy script com build integrado | deploy.sh com `export BUILD_SOURCE="deploy"` |
| `"ci"` | Pipeline CI/CD automático | GitHub Actions com `env: BUILD_SOURCE: ci` |

### environment Field

⚠️ **CRITICAL**: Sempre "production" porque indica tipo de build (minified), NÃO ambiente de runtime.

```php
// Para checar ambiente de runtime, use:
$is_production = $_SERVER['SERVER_NAME'] === 'tools.eliovieira.com.br';
$is_localhost = $_SERVER['SERVER_NAME'] === 'localhost';
```

## Default Values (Fallback)

Quando build-info.json não existe (desenvolvimento sem build):

```php
$defaultBuildInfo = [
    'build_date' => 'Development',
    'build_timestamp' => time(),
    'commit_hash' => 'dev',
    'commit_short' => 'dev',
    'branch' => 'unknown',
    'version' => 'dev',
    'build_source' => 'local',
    'environment' => 'development'
];
```

## Badge System

### HTML Badges (version.php)

#### Environment Badge
```php
<?php if ($buildInfo['environment'] === 'production'): ?>
    <span class="badge badge-production">Production Build</span>
<?php else: ?>
    <span class="badge badge-development">Development Build</span>
<?php endif; ?>
```

```css
.badge-production {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.badge-development {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}
```

#### Build Source Badge
```php
<?php 
$buildSource = $buildInfo['build_source'] ?? 'unknown';
switch($buildSource) {
    case 'ci':
        echo '<span class="badge badge-ci">Automático (CI/CD)</span>';
        break;
    case 'deploy':
        echo '<span class="badge badge-deploy">Manual (Deploy Script)</span>';
        break;
    case 'local':
        echo '<span class="badge badge-local">Manual (Desenvolvedor)</span>';
        break;
}
?>
```

```css
.badge-ci {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.badge-deploy {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
}

.badge-local {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
}
```

### Modal Labels (session-manager.js)

Labels devem SEMPRE match com version.php:

```javascript
let buildSourceLabel = 'Desconhecido';

switch(buildInfo.build_source) {
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

// Display
alert(`• Origem: ${buildSourceLabel}`);
```

## Build Age Calculation

### PHP (server-side)
```php
$buildTimestamp = $buildInfo['build_timestamp'] ?? time();
$buildAge = time() - $buildTimestamp;

if ($buildAge < 60) {
    $buildAgeFormatted = 'agora mesmo';
} elseif ($buildAge < 3600) {
    $minutes = floor($buildAge / 60);
    $buildAgeFormatted = "$minutes min atrás";
} elseif ($buildAge < 86400) {
    $hours = floor($buildAge / 3600);
    $buildAgeFormatted = "$hours hora(s) atrás";
} else {
    $days = floor($buildAge / 86400);
    $buildAgeFormatted = "$days dia(s) atrás";
}
```

### JavaScript (client-side)
```javascript
const buildTimestamp = buildInfo.build_timestamp;
const now = Math.floor(Date.now() / 1000);
const age = now - buildTimestamp;

let buildAge;
if (age < 60) {
    buildAge = 'agora';
} else if (age < 3600) {
    buildAge = `${Math.floor(age / 60)} min atrás`;
} else if (age < 86400) {
    buildAge = `${Math.floor(age / 3600)}h atrás`;
} else {
    buildAge = `${Math.floor(age / 86400)}d atrás`;
}
```

## System Information

### PHP Version & Server
```php
$response = array_merge($buildInfo, [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
    'build_age' => $buildAgeFormatted
]);
```

### Client Information (JavaScript)
```javascript
const clientInfo = {
    userAgent: navigator.userAgent || 'Desconhecido',
    platform: navigator.platform || 'Desconhecido',
    language: navigator.language || 'Desconhecido',
    cookiesEnabled: navigator.cookieEnabled || false,
    onLine: navigator.onLine || false,
    screenResolution: `${screen.width}x${screen.height}`,
    colorDepth: `${screen.colorDepth} bits`,
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
};
```

## Common Tasks

### Adicionar novo campo no build-info
```bash
# Em build.sh
cat > build-info.json <<EOF
{
  ...existing fields...
  "new_field": "${NEW_VALUE}"
}
EOF
```

```php
// Em version.php
$newField = $buildInfo['new_field'] ?? 'default';
```

### Criar novo badge
```php
// HTML
<span class="badge badge-custom"><?php echo $customValue; ?></span>
```

```css
.badge-custom {
    background: linear-gradient(135deg, #color1 0%, #color2 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
}
```

### Testar endpoint JSON
```bash
# Via curl
curl -k -H "Accept: application/json" https://localhost/version.php | jq

# Via JavaScript console
fetch('version.php', {
    headers: { 'Accept': 'application/json' }
})
.then(r => r.json())
.then(data => console.table(data))
```

## Troubleshooting

### Modal mostra apenas client info (sem build info)
**Causa**: Fetch sem header `Accept: application/json`

**Diagnóstico**:
```bash
# Testar o que está retornando
curl -k https://localhost/version.php
# Se retorna HTML (tags <html>), confirma problema

curl -k -H "Accept: application/json" https://localhost/version.php
# Deve retornar JSON
```

**Solução**: Ver [session-management skill](../session-management/SKILL.md)

### Commit hash desatualizado
**Causa**: build-info.json não regenerado após novos commits

**Solução**:
```bash
./build.sh  # Regenera build-info.json
docker restart foursquare-mass-editor  # Se usando Docker
```

### build_source incorreto no CI/CD
**Causa**: Ambiente CI não detectado corretamente

**Diagnóstico**:
```bash
# Checar variáveis de ambiente no CI log
echo "CI=$CI"
echo "GITHUB_ACTIONS=$GITHUB_ACTIONS"
echo "BUILD_SOURCE=$BUILD_SOURCE"
```

**Solução**:
```yaml
# Em .github/workflows/deploy.yml
- name: Build
  run: bash build.sh
  env:
    BUILD_SOURCE: ci  # ← Explicit override
```

### Badge não aparece
**Causa**: CSS não carregado ou valor desconhecido

**Diagnóstico**:
```php
// Adicionar debug
var_dump($buildInfo['build_source']);
```

**Solução**:
```php
// Sempre ter default no switch
default:
    echo '<span class="badge badge-unknown">Desconhecido</span>';
```

## Critical Patterns

### ALWAYS use Accept header for JSON
```javascript
// ❌ Errado
fetch('version.php')

// ✅ Correto
fetch('version.php', {
    headers: { 'Accept': 'application/json' }
})
```

### NEVER commit build-info.json
```bash
# Em .gitignore
build-info.json   # ✅ Sempre ignorado
*.min.js          # ✅ Minified também
```

### ALWAYS have fallback values
```php
// ❌ Errado - Falha se campo não existir
$version = $buildInfo['version'];

// ✅ Correto - Fallback
$version = $buildInfo['version'] ?? 'dev';
```

### ALWAYS match labels between HTML and JS
```php
// version.php
case 'ci': echo 'Automático (CI/CD)'; break;
```

```javascript
// session-manager.js
case 'ci': buildSourceLabel = 'Automático (CI/CD)'; break;
```

## Integration Points

- **build.sh**: Gera build-info.json com todas as informações
- **session-manager.js**: Consome JSON API para modal do Sistema
- **deploy.sh**: Define BUILD_SOURCE="deploy"
- **.github/workflows/deploy.yml**: Define BUILD_SOURCE="ci"

## Related Skills
- [Build System](../build-system/SKILL.md) - Como build-info.json é gerado
- [Deploy Workflows](../deploy-workflows/SKILL.md) - Como build_source é definido
- [Session Management](../session-management/SKILL.md) - Como modal consome a API

## Documentation
- **Build & Deploy Guide**: `docs/BUILD_AND_DEPLOY.md`
- **Field Explanations**: Section "Entendendo os Campos"
- **Badges Reference**: Section "Sistema de Badges"
