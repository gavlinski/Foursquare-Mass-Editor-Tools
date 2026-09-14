---
applyTo: "js/**/*.js"
---

# JavaScript conventions (js/)

This folder mixes modern ES6 modules (`session-manager.js`) with legacy Dojo-integrated scripts (`4sq.js`, `main.js`). Never edit the generated `*.min.js` / `*.min.js.map` files directly — they're build output (see the `build-system` skill); edit the source file and rebuild.

## ES6 classes integrate with Dojo via addOnLoad

```javascript
// Modern ES6 class
class SessionManager {
    async checkSessionStatus() {
        const response = await fetch('session_status.php', {
            credentials: 'same-origin',
            cache: 'no-cache'
        });
        return response.json();
    }
}

// Bridge into the legacy Dojo page lifecycle
dojo.addOnLoad(function inicializar() {
    if (window.sessionManager) {
        window.sessionManager.checkSessionStatus();
    }
});
```

## Console logging convention

Use emoji prefixes consistently so logs are scannable — this convention is relied on across the codebase (session-manager, 4sq.js):

```javascript
console.log('🔧 SessionManager: Inicializando...');
console.warn('⚠️ Token expirado');
console.error('❌ Erro na autenticação:', error);
```

## API calls go through the centralized error-handling pattern

See the `venue-editing-workflow` skill for the full `xmlhttpRequest()` status-code switch (400/401/403/404/429/500/504) — new API calls should reuse it rather than duplicating a bespoke `fetch`/`XMLHttpRequest` handler.
