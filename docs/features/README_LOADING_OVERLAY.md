# Loading Overlay System - Quick Reference

## 🎯 O Que Foi Implementado

Sistema de loading overlay para prevenir FOUC (Flash of Unstyled Content) durante carregamento do Dojo/Dijit.

## 📍 Arquivos Modificados

| Arquivo | Mudanças |
|---------|----------|
| `main.php` | + Loading overlay + Scripts de detecção |
| `edit.php` | + Loading overlay + Scripts de detecção + Progress bar |

## 🎨 Visual

### main.php
```
┌─────────────────────────────────┐
│                                 │
│         [◐ Spinner]            │
│                                 │
│    Carregando Elio Tools       │
│   Preparando interface...      │
│                                 │
└─────────────────────────────────┘
```

### edit.php
```
┌─────────────────────────────────┐
│                                 │
│         [◐ Spinner]            │
│                                 │
│     Carregando Editor          │
│   Preparando X venues...       │
│                                 │
│     ████████░░░░░░░            │
│                                 │
└─────────────────────────────────┘
```

## ⚙️ Como Funciona

### 1. Ao Carregar HTML
```html
<body class="tundra loading">
    <div id="app-loading-overlay">...</div>
    <!-- Conteúdo oculto via CSS -->
</body>
```

### 2. Durante Carregamento
- ✅ Overlay visível (z-index: 99999)
- ✅ Spinner animando
- ✅ Conteúdo oculto (visibility: hidden)
- ✅ 3 métodos de detecção paralelos:
  - `dojo.ready()` (preferencial)
  - `window.onload` (backup)
  - Timeout 10s (segurança)

### 3. Após Dojo Ready
```javascript
removeLoadingOverlay()
  → Fade out (500ms)
  → Remove do DOM
  → Remove class 'loading'
  → Conteúdo visível ✅
```

## 🧪 Teste Local

### Arquivo de Teste
```bash
# Abrir no navegador
open debug/test_loading_overlay.html

# Ou via servidor local
cd /Users/elio/Projetos/Foursquare-Mass-Editor-Tools
php -S localhost:8000
# Navegar para: http://localhost:8000/debug/test_loading_overlay.html
```

### Cenários de Teste
1. **Normal**: Overlay por 2s → Fade out
2. **Slow 3G**: DevTools > Network > Slow 3G
3. **Timeout**: Bloquear Dojo CDN → Overlay remove após 10s
4. **Cache**: Refresh múltiplos → Overlay mais rápido

## 🐛 Debug

### Console Logs
```javascript
✅ Dojo carregado em 1200ms
🎨 Interface pronta

⚠️ Timeout: Removendo overlay após 10 segundos
📦 window.onload: Removendo overlay
```

### Verificação Manual
```javascript
// Chrome DevTools Console

// Verificar se overlay foi removido
document.getElementById('app-loading-overlay'); // null = ✅

// Verificar estado do body
document.body.classList.contains('loading'); // false = ✅
```

## 🔧 Customização Rápida

### Alterar Timeout (30s padrão)
```javascript
// Buscar em main.php ou edit.php:
var fallbackTimeout = setTimeout(function() {
    removeLoadingOverlay();
}, 30000); // ← Alterar aqui (milissegundos)
```

### Alterar Cores
```css
/* Buscar em <style> da página: */
#app-loading-overlay {
    background: linear-gradient(135deg, 
        #667eea 0%,  /* ← Cor inicial */
        #764ba2 100% /* ← Cor final */
    );
}
```

### Alterar Velocidade de Fade
```css
#app-loading-overlay {
    transition: opacity 0.5s ease-out; /* ← Alterar duração */
}
```

## 📊 Performance Esperada

| Cenário | Tempo Overlay |
|---------|---------------|
| Cache completo (local) | 100-300ms |
| Sem cache (local) | 500-1500ms |
| Produção (rede rápida) | 1000-2000ms |
| Slow 3G | 3000-5000ms |
| Timeout máximo | 30000ms |

## ✅ Checklist de Validação

- [ ] Overlay aparece imediatamente ao carregar
- [ ] Spinner anima suavemente (não trava)
- [ ] Conteúdo não aparece sem estilo
- [ ] Fade out ocorre após Dojo carregar
- [ ] Overlay removido do DOM após fade
- [ ] Console mostra logs de sucesso
- [ ] Timeout funciona se Dojo falhar
- [ ] Funciona em mobile/tablet

## 📚 Documentação Completa

| Documento | Descrição |
|-----------|-----------|
| [LOADING_OVERLAY.md](LOADING_OVERLAY.md) | Documentação técnica completa |
| [LOADING_OVERLAY_VISUAL.md](LOADING_OVERLAY_VISUAL.md) | Guia visual e animações |
| [test_loading_overlay.html](../../debug/test_loading_overlay.html) | Arquivo de teste standalone |

## 🚀 Deploy

### Zero Configuração Adicional
- ✅ CSS inline no `<head>`
- ✅ JavaScript inline antes de `</body>`
- ✅ Sem arquivos externos necessários
- ✅ Funciona mesmo se CDN falhar

### Rollback (Se Necessário)
```bash
git log --oneline -5  # Ver commits recentes
git revert <commit-hash>  # Reverter mudanças
```

## 💡 Dicas

1. **Em Desenvolvimento**: Overlay dura menos (cache local rápido)
2. **Em Produção**: Overlay mais visível (CDN + sem cache)
3. **Conexão Lenta**: Overlay essencial para UX
4. **JavaScript Desabilitado**: Overlay permanece (edge case aceitável)

## 🔗 Links Úteis

- [Dojo 1.8 dojo.ready()](https://dojotoolkit.org/reference-guide/1.8/dojo/ready.html)
- [CSS Animations Performance](https://web.dev/animations/)
- [FOUC Prevention](https://webkit.org/blog/66/the-fouc-problem/)

---

**Implementado**: 11 de março de 2026  
**Status**: ✅ Produção  
**Compatibilidade**: Chrome 60+, Firefox 55+, Safari 11+, Edge 79+
