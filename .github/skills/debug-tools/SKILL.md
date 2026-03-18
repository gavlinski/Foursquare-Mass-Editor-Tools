# Debug Tools Interface Skill

## Name
Consolidated Debug Tools Interface

## Description
Interface centralizada de ferramentas de debug em `debug/index.php` com 14 ferramentas consolidadas. Sistema de card-based layout, scroll preservation, e navegação padronizada. Jamais criar novos arquivos de teste na raiz.

## When to use
Carregue esta skill quando:
- Adicionar nova ferramenta de debug
- Modificar interface de debug/index.php
- Criar página de teste em debug/
- Diagnosticar problemas de desenvolvimento
- Padronizar navegação de debug tools

## Key Files
- `debug/index.php` - Interface principal consolidada
- `debug/session_test_manager.php` - Criar/validar sessões
- `debug/debug_session.php` - Debug de session data
- `debug/test_session_debug.html` - UI para teste de sessão
- `debug/css_test_interface.php` - Teste de estilos CSS
- `debug/test_integration_markers.html` - Teste Google Maps markers
- `debug/test_draggable_markers.html` - Teste drag & drop markers
- `debug/test_custom_markers.html` - Teste custom markers
- E mais 6 ferramentas...

## Architecture

### Card-Based Layout

```php
<div class="tool-grid">
    <!-- Card Template -->
    <div class="tool-card">
        <div class="tool-icon">🔧</div>
        <h3>Nome da Ferramenta</h3>
        <p>Descrição breve do que a ferramenta faz</p>
        <div class="tool-links">
            <a href="ferramenta.php" onclick="sessionStorage.setItem('debugIndexScrollPos', ...);">
                Abrir
            </a>
        </div>
    </div>
</div>
```

### Categories

#### 🔐 Session & Auth (4 tools)
1. **Session Test Manager** - Criar/validar sessões
2. **Debug Session** - Visualizar $_SESSION data
3. **Test Session Debug** - UI de teste de autenticação
4. **Clear Cache** - Limpar cache do navegador

#### 🗺️ Google Maps (4 tools)
5. **Integration Markers** - Testar integração de markers
6. **Draggable Markers** - Testar drag & drop
7. **Custom Markers** - Testar custom icons
8. **Maps Config** - Testar configuração de mapas

#### 🎨 Interface & CSS (3 tools)
9. **CSS Test Interface** - Testar estilos personalizados
10. **Dojo CDN Test** - Testar Dojo via CDN
11. **API Comparison** - Comparar versões de API

#### 📊 System Info (3 tools)
12. **Version Info** - Ver versão completa (version.php)
13. **PHP Info** - Ver phpinfo()
14. **Environment** - Ver variáveis de ambiente

## Consolidated Tools (NEVER Create New Files)

### ✅ Use Existing Tools

#### Session Testing
```bash
# Criar sessão de teste
curl -k "https://localhost/debug/session_test_manager.php?action=create"

# Validar sessão
curl -k "https://localhost/debug/debug_session.php?mode=validate"

# Destruir sessão
curl -k "https://localhost/debug/session_test_manager.php?action=destroy"
```

#### CSS Testing
```php
// debug/css_test_interface.php
// Interface visual para testar CSS em tempo real
// NÃO criar novo arquivo, usar este
```

#### Maps Testing
```html
<!-- debug/test_integration_markers.html -->
<!-- Testa markers, eventos, e integração
<!-- NÃO criar test_maps.html, usar este -->
```

### ❌ NEVER Do This

```bash
# ❌ ERRADO - Criar arquivo temporário na raiz
echo "<?php session_start(); var_dump(\$_SESSION); ?>" > test.php

# ❌ ERRADO - Criar novo arquivo de teste
touch debug/test_new_feature.php

# ✅ CORRETO - Adicionar à ferramenta existente
# Modificar debug/session_test_manager.php
```

## Standard Card Template

### CSS Classes
```css
.tool-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.tool-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 12px rgba(0,0,0,0.15);
}

.tool-icon {
    font-size: 2.5em;
    margin-bottom: 16px;
}

.tool-links a {
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 6px;
    display: inline-block;
    transition: transform 0.2s;
}
```

### Icon Guide
- 🔐 Session/Auth
- 🗺️ Maps
- 🎨 UI/CSS
- 📊 System Info
- 🔧 Tools
- ⚙️ Config
- 🐛 Debug
- 📝 Logs

## Adding New Tool

### Step 1: Create Tool File
```bash
# Em debug/ directory
cat > debug/my_new_tool.php <<'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>My New Tool - Debug</title>
    <meta charset="utf-8">
    <style>
        body { font-family: system-ui; padding: 40px; }
        /* ... styles ... */
    </style>
</head>
<body>
    <h1>🆕 My New Tool</h1>
    
    <!-- Tool implementation -->
    
    <!-- Footer padrão -->
    <footer style="text-align: center; padding: 30px 20px; margin-top: 40px; border-top: 2px solid #e0e0e0;">
        <a href="index.php" style="...">
            ← Voltar para Debug
        </a>
    </footer>
</body>
</html>
EOF
```

### Step 2: Add Card to index.php
```php
<!-- Em debug/index.php -->
<div class="tool-card">
    <div class="tool-icon">🆕</div>
    <h3>My New Tool</h3>
    <p>Descrição clara do que a ferramenta faz e quando usar</p>
    <div class="tool-links">
        <a href="my_new_tool.php" 
           onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
            Abrir Ferramenta
        </a>
    </div>
</div>
```

### Step 3: Document in copilot-instructions.md
```markdown
- **debug/my_new_tool.php**: Purpose and usage
```

## Standard Footer (All Test Pages)

```html
<footer style="text-align: center; padding: 30px 20px; margin-top: 40px; border-top: 2px solid #e0e0e0; background: white;">
    <a href="index.php" 
       style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 24px; 
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
              color: white; text-decoration: none; border-radius: 8px; 
              font-weight: 600; transition: transform 0.2s, box-shadow 0.2s; 
              box-shadow: 0 4px 6px rgba(0,0,0,0.1);"
       onmouseover="this.style.transform='scale(1.05)'"
       onmouseout="this.style.transform='scale(1)'">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        ← Voltar para Debug
    </a>
</footer>
```

⚠️ **CRITICAL**: Footer NÃO tem onclick! Ver [scroll-preservation skill](../scroll-preservation/SKILL.md)

## Session Test Manager

### Endpoints
```bash
# Criar sessão
?action=create

# Validar sessão
?action=validate

# Destruir sessão
?action=destroy

# Ver dados
?action=view
```

### Usage Example
```php
<?php
// Em debug/session_test_manager.php

$action = $_GET['action'] ?? 'view';

switch($action) {
    case 'create':
        session_start();
        $_SESSION['oauth_token'] = 'test_token_' . uniqid();
        $_SESSION['user_data'] = [
            'firstName' => 'Test',
            'lastName' => 'User',
            'id' => 'test_user_id'
        ];
        echo json_encode(['success' => true, 'message' => 'Session created']);
        break;
    
    case 'validate':
        session_start();
        $valid = isset($_SESSION['oauth_token']);
        echo json_encode(['valid' => $valid]);
        break;
    
    case 'destroy':
        session_start();
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Session destroyed']);
        break;
    
    case 'view':
        session_start();
        echo json_encode($_SESSION);
        break;
}
```

## CSS Test Interface

### Live CSS Editor
```php
<!DOCTYPE html>
<html>
<head>
    <title>CSS Test Interface</title>
    <style id="dynamic-styles">
        /* CSS inserido via textarea é aplicado aqui */
    </style>
</head>
<body>
    <textarea id="css-input" rows="10" cols="50">
/* Cole ou escreva CSS aqui */
.test { color: red; }
    </textarea>
    
    <button onclick="applyCSS()">Aplicar CSS</button>
    
    <div class="test">Elemento de teste</div>
    
    <script>
    function applyCSS() {
        const css = document.getElementById('css-input').value;
        document.getElementById('dynamic-styles').textContent = css;
    }
    </script>
</body>
</html>
```

## Common Tasks

### Debug OAuth flow
```bash
# 1. Criar sessão de teste
curl -k "https://localhost/debug/session_test_manager.php?action=create"

# 2. Verificar dados
curl -k "https://localhost/debug/debug_session.php?mode=validate"

# 3. Testar protected page
curl -k -b cookies.txt https://localhost/4sqmet/main.php
```

### Test Google Maps integration
```bash
# 1. Abrir em browser
https://localhost/debug/test_integration_markers.html

# 2. Verificar console para erros de API key
# 3. Testar clicks, drags, custom markers
```

### Add new debugging category
```php
<!-- Em debug/index.php -->
<section>
    <h2>🆕 Nova Categoria</h2>
    <div class="tool-grid">
        <!-- Cards da nova categoria -->
    </div>
</section>
```

## Troubleshooting

### Tool não aparece no index
**Causa**: Card não adicionado ou CSS quebrado

**Solução**:
```php
// Verificar que card está dentro de .tool-grid
<div class="tool-grid">
    <div class="tool-card">...</div>  // ← Seu card aqui
</div>
```

### Link não preserva scroll
**Causa**: Falta onclick no link de saída

**Solução**: Ver [scroll-preservation skill](../scroll-preservation/SKILL.md)

### Session test não funciona
**Causa**: session_start() falhando

**Diagnóstico**:
```bash
# Checar logs do Apache/PHP
docker logs foursquare-mass-editor | grep session
```

**Solução**:
```php
// Adicionar error handling
if (!session_start()) {
    die('Failed to start session');
}
```

## Critical Patterns

### NEVER create temporary test files
```bash
# ❌ ERRADO
touch test.php
touch debug_something.php
touch temp.html

# ✅ CORRETO
# Use ferramentas existentes em debug/
```

### ALWAYS use consolidated tools
```php
// ❌ ERRADO - Criar novo arquivo
<?php
session_start();
var_dump($_SESSION);
?>

// ✅ CORRETO - Usar ferramenta existente
curl -k https://localhost/debug/debug_session.php?mode=dump
```

### ALWAYS add footer navigation
```html
<!-- ❌ ERRADO - Sem back link -->
</body>
</html>

<!-- ✅ CORRETO - Com footer padrão -->
    <footer>
        <a href="index.php">← Voltar para Debug</a>
    </footer>
</body>
</html>
```

## Related Skills
- [Scroll Preservation](../scroll-preservation/SKILL.md) - Navegação com preservação de posição
- [Session Management](../session-management/SKILL.md) - Debug de sessões
- [Version Info](../version-info/SKILL.md) - System info display

## Historical Files

### backup/ Directory
Arquivos antigos movidos para backup (não mais usados):
- `backup/test_scroll_preservation.html`
- `backup/help_cache_favicon.html`
- `backup/session-manager-backup.js`
- `backup/4sq-legacy-backup.js`

⚠️ **NEVER reference these files** - Apenas histórico

## Documentation
- **Migration Guide**: `docs/MIGRATION.md`
- **Copilot Instructions**: `.github/copilot-instructions.md`
