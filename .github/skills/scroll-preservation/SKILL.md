# Scroll Preservation Skill

## Name
Debug Index Scroll Preservation System

## Description
Sistema que preserva a posição de scroll no debug/index.php quando o usuário navega para páginas de teste e retorna. Usa sessionStorage e estratégia multi-retry para restauração confiável.

## When to use
Carregue esta skill quando:
- Modificar navegação em debug/index.php
- Adicionar novos links para páginas de teste
- Diagnosticar problemas de scroll preservation
- Implementar sistemas similares de navegação com preservação de estado

## Key Files
- `debug/index.php` - Página principal com onclick handlers e IIFE de restauração
- Todas as páginas de teste em `debug/` - Footer navigation sem onclick

## Architecture

### Flow Correto
```
1. Usuário em debug/index.php (scroll position: 500px)
2. Clica em link para test_session_debug.html
   └─> onclick salva 500px em sessionStorage
3. Navega para test_session_debug.html
4. Clica em "Voltar para Debug" (footer)
   └─> Volta para debug/index.php (SEM salvar posição)
5. debug/index.php carrega
   └─> IIFE lê 500px do sessionStorage
   └─> Restaura scroll para 500px
   └─> Remove item do sessionStorage
```

### ❌ Flow Errado (Bug Anterior)
```
1. Usuário em debug/index.php (scroll: 500px)
2. Clica em link → Salva 500px ✅
3. Página de teste carrega
4. Clica em "Voltar" → Salva 0px ❌ (overwrite!)
5. debug/index.php restaura → 0px (errado)
```

## Implementation

### Save Pattern (debug/index.php links)
```html
<!-- 14 links que SAEM do index -->
<a href="test_session_debug.html" 
   onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
    Test Session Debug
</a>

<a href="version.php" 
   onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
    Version Info
</a>

<!-- Total: 14 links com onclick para salvar -->
```

### Restore Pattern (IIFE at bottom of debug/index.php)
```javascript
(function() {
    const savedScrollPos = sessionStorage.getItem('debugIndexScrollPos');
    
    if (savedScrollPos) {
        const scrollPos = parseInt(savedScrollPos);
        
        function tryRestoreScroll() {
            window.scrollTo(0, scrollPos);
            const currentPos = window.scrollY || document.documentElement.scrollTop;
            
            // Verifica se falhou (página ainda não tem altura)
            if (currentPos === 0 && scrollPos > 0) {
                return false;  // Falhou
            }
            return true;  // Sucesso
        }
        
        // Tentativa imediata
        tryRestoreScroll();
        
        // Multi-retry strategy
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    if (tryRestoreScroll()) {
                        sessionStorage.removeItem('debugIndexScrollPos');
                    }
                }, 50);
            });
        } else {
            setTimeout(function() {
                if (tryRestoreScroll()) {
                    sessionStorage.removeItem('debugIndexScrollPos');
                } else {
                    // Fallback final após 500ms
                    setTimeout(function() {
                        tryRestoreScroll();
                        sessionStorage.removeItem('debugIndexScrollPos');
                    }, 500);
                }
            }, 100);
        }
    }
})();
```

### No Save Pattern (back links in test pages)
```html
<!-- Footer em TODAS as páginas de teste -->
<footer style="text-align: center; padding: 30px 20px; margin-top: 40px;">
    <a href="index.php">
        ← Voltar para Debug
    </a>
</footer>

<!-- SEM onclick! Apenas navegação simples -->
```

## Multi-Retry Strategy

### Timing
1. **Immediate** (0ms): Tenta logo que IIFE executa
2. **DOMContentLoaded + 50ms**: Se página ainda está carregando
3. **100ms**: Primeira tentativa extra
4. **500ms**: Fallback final (garante que página carregou)

### Rationale
- Página pode não ter altura suficiente para fazer scroll imediatamente
- Conteúdo pode estar carregando dinamicamente
- Diferentes browsers têm timing diferente
- Múltiplas tentativas garantem sucesso em ~99% dos casos

## Standard Footer Pattern

Todas as 11 páginas de teste devem usar footer consistente:

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

## Common Tasks

### Adicionar novo link no index
```html
<!-- Em debug/index.php -->
<a href="nova_pagina.html" 
   onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
    Nova Página de Teste
</a>
```

### Criar nova página de teste
```html
<!DOCTYPE html>
<html>
<head>
    <title>Nova Página - Debug</title>
</head>
<body>
    <h1>Conteúdo...</h1>
    
    <!-- Footer padrão SEM onclick -->
    <footer style="...">
        <a href="index.php">← Voltar para Debug</a>
    </footer>
</body>
</html>
```

### Diagnosticar falha de restauração
```javascript
// Adicionar console.log temporário no IIFE
console.log('🔍 Saved position:', savedScrollPos);
console.log('🎯 Trying to restore to:', scrollPos);

tryRestoreScroll();

setTimeout(() => {
    const atual = window.scrollY;
    console.log('📍 Current position:', atual);
    console.log(atual === scrollPos ? '✅ Success' : '❌ Failed');
}, 600);
```

## Troubleshooting

### Scroll sempre volta para topo
**Causas possíveis**:
1. ❌ Back button tem onclick (overwrite com 0)
2. ❌ sessionStorage bloqueado (browser privacy mode)
3. ❌ IIFE não está executando

**Diagnóstico**:
```javascript
// No console do browser
sessionStorage.getItem('debugIndexScrollPos')  // Deve retornar número ou null
```

**Solução**:
```bash
# Verifique que back links NÃO têm onclick
grep -n "onclick.*Voltar.*Debug" debug/*.html
# Não deve retornar resultados
```

### Restauração intermitente
**Causa**: Timing issue, página carrega em velocidades diferentes

**Solução**: Multi-retry já resolve isso. Se ainda falhar:
```javascript
// Aumentar timeout final de 500ms para 1000ms
setTimeout(function() {
    tryRestoreScroll();
    sessionStorage.removeItem('debugIndexScrollPos');
}, 1000);  // ← Era 500
```

### sessionStorage não limpa
**Causa**: Código nunca chama `removeItem`

**Solução**: Sempre limpar após restauração bem-sucedida:
```javascript
if (tryRestoreScroll()) {
    sessionStorage.removeItem('debugIndexScrollPos');  // ✅
}
```

## Critical Patterns

### NEVER save on back links
```html
<!-- ❌ Errado -->
<a href="index.php" 
   onclick="sessionStorage.setItem('debugIndexScrollPos', 0);">
    Voltar
</a>

<!-- ✅ Correto -->
<a href="index.php">
    Voltar
</a>
```

### ALWAYS cleanup after restore
```javascript
// ❌ Errado - Scroll position fica no storage indefinidamente
tryRestoreScroll();

// ✅ Correto - Limpa após usar
if (tryRestoreScroll()) {
    sessionStorage.removeItem('debugIndexScrollPos');
}
```

### ALWAYS use fallback values
```javascript
// ❌ Errado - Pode falhar em alguns browsers
const scrollPos = window.scrollY;

// ✅ Correto - Fallback para compatibilidade
const scrollPos = window.scrollY || document.documentElement.scrollTop;
```

## Integration Points

- **debug/index.php**: 14 links com onclick + IIFE de restauração
- **11 test pages**: Footer com link simples (sem onclick)
- **backup/ directory**: Páginas antigas de teste (não mais usadas)

## Files Modified (Historical)
Durante implementação deste sistema:
- `debug/index.php` - Adicionado onclick em 14 links + IIFE
- `debug/test_session_debug.html` - Footer padronizado
- `debug/test_integration_markers.html` - Footer padronizado
- `debug/test_draggable_markers.html` - Footer padronizado
- `debug/test_custom_markers.html` - Footer padronizado
- `debug/css_test_interface.php` - Footer padronizado
- `debug/test_dojo_cdn.php` - Footer padronizado
- `debug/test_google_maps_config.php` - Footer padronizado
- `debug/api_comparison_tool.html` - Footer padronizado
- `clear_cache.php` - Removido onclick dos back links
- Moved to backup: test_scroll_preservation.html, help_cache_favicon.html

## Documentation
Nenhuma documentação externa específica (sistema interno de debug).
