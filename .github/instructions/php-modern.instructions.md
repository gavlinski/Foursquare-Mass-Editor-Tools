---
applyTo: "src/**/*.php"
---

# Modern PHP conventions (src/, PSR-4)

Code under `src/` is the modern, PSR-4-autoloaded half of the hybrid architecture (`ElioTools\*` namespaces: `Config`, `Security`, `Api`). Unlike the legacy root files, this code should use current PHP 8.1 idioms.

```php
<?php
declare(strict_types=1);

namespace ElioTools\Security;

class SessionManager {
    public function start(array $options = []): void {
        // Type hints are mandatory on new modern-namespace code
    }
}
```

Rules:
- Always start files with `declare(strict_types=1);`.
- Always type-hint parameters and return types.
- Keep this code framework-agnostic PSR-4 — don't reach into legacy root-file globals (`$_SESSION` handling, superglobal-heavy patterns) from here; expose a clean API instead and let the legacy files call into it.
