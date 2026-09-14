---
applyTo: "edit.php,main.php,load.php"
---

# Dojo Toolkit widget conventions

These pages render Dojo Toolkit v1.8 widgets (`dijit.form.TextBox`, etc.). Dojo sanitizes/strips external CSS applied to its widgets, so the usual "put it in a stylesheet" instinct does not work here.

```php
// ❌ NEVER — external CSS is ignored/stripped by Dojo
.dijitTextBox { width: 200px !important; }

// ✅ ALWAYS — inline styles on the widget markup itself
echo '<input dojoType="dijit.form.TextBox" style="width: 8em; margin-left: 5px;">';
```

## Use the renderizarCampo() helper

Field rendering is centralized in `edit.php` through `renderizarCampo()` — extend it instead of hand-rolling new `<input dojoType=...>` blocks:

```php
function renderizarCampo(string $tipo, string $name, array $config, int $ajusteInput, int $indice): string {
    $width = $config['width'] + $ajusteInput;
    return '<input type="text" dojoType="dijit.form.TextBox" name="' . htmlspecialchars($name) . '" ' .
           'maxlength="' . $config['maxlength'] . '" placeHolder="' . $config['placeholder'] . '" ' .
           'style="width: ' . $width . 'em; margin-left: 5px;" ' .
           'onchange="verificarAlteracao(this, ' . $indice . ')">' . chr(10);
}
```

## Dojo loading strategy

Current strategy is Google CDN primary with a local fallback bundled in `js/dojo`, `js/dijit`, `js/dojox` (legacy fallback CDNs were removed). Toggle for testing via env vars:

```bash
DOJO_SOURCE="cdn"
DOJO_FORCE_FALLBACK="false"
```

Use `debug/test_dojo_cdn.php` to validate CDN vs. fallback behavior — never write a one-off script for this.

## Forbidden

- Do not style Dojo widgets via external `.css` files.
- Do not use JavaScript to dynamically patch Dojo widget styles at runtime.
- Do not attempt a CSS/JS hybrid styling system for widgets — inline styles only.

If a new editable field is added, it also needs an entry in `EDITABLE_FIELDS` in `js/4sq.js` — see the `venue-editing-workflow` skill.
