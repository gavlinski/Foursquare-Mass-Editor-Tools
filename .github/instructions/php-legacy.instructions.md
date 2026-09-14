---
applyTo: "*.php"
---

# Legacy PHP conventions (root-level files)

These root-level files (`edit.php`, `load.php`, `main.php`, `index.php`, etc.) are legacy PHP 5.4+ procedural code kept intentionally functional — do not refactor them to PSR-4 without an explicit request. See `.github/instructions/php-modern.instructions.md` for the modern `src/` conventions instead.

## Anti-cache headers

Always include these in dynamic pages so browsers/proxies never serve stale state:

```php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
```

## FoursquareAPI method casing

`FoursquareAPI.Class.php` is a vendored legacy library — never modify it, and always call its methods with the exact PascalCase casing:

```php
$foursquare->SetAccessToken($token);    // ✅ correct
$foursquare->GetPrivate("users/self");  // ✅ correct
$foursquare->setAccessToken($token);    // ❌ wrong casing, will fail
```

## Configuration hierarchy

Environment variables are the source of truth; hardcoded values are only a development fallback:

```php
$client_key = getenv('FOURSQUARE_CLIENT_KEY') ?:
              "YOUR_FOURSQUARE_CLIENT_KEY"; // fallback for local dev only
```

## Input validation & session security

```php
// Sanitize any GET/POST value before use
$code = filter_var($_GET['code'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Regenerate the session id once per session (not on every request)
if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}
```

## Cookies must be guarded and hardened

Setting cookies after headers are already sent throws a warning and silently fails — always check first, and always harden the flags:

```php
if (!headers_sent()) {
    setcookie("oauth_token", $value, [
        'expires' => time() + 3600,
        'path' => '/',
        'secure' => true,      // HTTPS only
        'httponly' => true,    // not accessible via JS
        'samesite' => 'Strict' // CSRF protection
    ]);
}
```

## Forbidden

- Do not modify `FoursquareAPI.Class.php`.
- Do not create temporary debug/test scripts in the repo root — use `debug/` (see the `debug-tools` skill).
- Do not hardcode credentials — always read from `.env` via `getenv()`.
