# Foursquare Mass Editor Tools — Copilot Instructions

> **Source of truth**: [/AGENTS.md](../AGENTS.md) has the full project overview, dev environment, build/test commands, and security considerations for any AI agent. This file only adds VS Code/Copilot-specific wiring (skill and instruction discovery) and the critical guardrails that must never be skipped.

## Architecture at a glance

- **Backend**: PHP 8.1, hybrid — modern PSR-4 in `src/` (`ElioTools\Config`, `ElioTools\Security`, `ElioTools\Api`) + legacy procedural PHP in the repo root (`edit.php`, `load.php`, `main.php`, `index.php`).
- **Frontend**: Dojo Toolkit v1.8 widgets + ES6 JavaScript (`js/`).
- **API**: Foursquare API v2, OAuth2.
- **Infra**: Docker + Apache 2.4 + Composer.

```
src/            # Modern PSR-4: Api/, Config/, Security/
js/             # legacy Dojo-integrated + modern ES6
debug/          # consolidated debug/test tools
Root *.php      # legacy, functional — edit.php, load.php, main.php, js/4sq.js
```

Two dev workflows exist (Dev Container vs `./dev.sh`) — **never run both at once** (port 80/443 conflict). See the `devcontainer-setup` and `deploy-workflows` skills.

## Path-scoped coding conventions

VS Code loads these automatically for matching files — read them directly if you need the detail:

- `.github/instructions/php-legacy.instructions.md` — root-level `*.php`
- `.github/instructions/php-modern.instructions.md` — `src/**/*.php`
- `.github/instructions/dojo-widgets.instructions.md` — `edit.php`, `main.php`, `load.php`
- `.github/instructions/javascript-conventions.instructions.md` — `js/**/*.js`
- `.github/instructions/hooks.instructions.md` defines conventions for `.github/hooks/**` and `tests/hooks/**`.

## Local Copilot hooks

- `.github/hooks/tool-guardian.json` runs a shell-command guardian in warning mode by default; block mode is opt-in by changing `TOOL_GUARD_MODE` to `block`.
- The guardian checks destructive removal of critical paths, `git reset --hard`, forced `git clean`, and force-push. It intentionally permits normal deploy operations such as `git checkout`, `git pull`, `docker rm`, and image pruning.
- `.github/hooks/secrets-scanner.json` warns before `git commit` when high-confidence credential patterns appear in the commit command or tracked working-tree diff. It compares against `HEAD` without mutating the index and excludes the Playwright storage-state file; it does not cover every secret format or all Git invocation styles.
- Both hooks are advisory by default. The scanner is intentionally not a commit blocker because its tracked-diff superset may warn about files outside a path-limited commit. Neither hook replaces CI or GitHub-side controls.
- Run `npm run test:hooks` after changing hook behavior. Script tests validate the contract, not host discovery. Copilot CLI repository-hook discovery is documented; actual runtime execution still needs an authenticated smoke test, and VS Code Chat support must be confirmed separately.

## Skills index (`.github/skills/`)

Load the matching `SKILL.md` before working in its area:

- **build-system** — build.sh/build-docker.sh, minification, build_source field
- **deploy-workflows** — dev.sh vs deploy.sh vs CI/CD, when to use each
- **deploy-safety** — dirty-state handling, backups, ServerName config
- **ci-cd-configuration** — GitHub Actions secrets vs. repository variables
- **devcontainer-setup** — VS Code Dev Container config and troubleshooting
- **debug-tools** — consolidated debug/index.php interface
- **scroll-preservation** — sessionStorage scroll restore pattern in debug/index.php
- **session-management** — OAuth2 flow, session-manager.js, system info modal
- **version-info** — version.php dual HTML/JSON endpoint, build-info.json, badges
- **analytics-hardening** — bot/scanner filtering layers in analytics.php
- **venue-editing-workflow** — Import→Load→Edit→Save flow, Chains, CSV behavior, EDITABLE_FIELDS, API error handling
- **mcp-playwright-auth** — Playwright MCP CDP authentication and token injection
- **playwright-regression** — @playwright/test regression suite (tests/e2e/)

## Forbidden actions (always-on guardrails)

- ❌ Do NOT modify `FoursquareAPI.Class.php` (vendored legacy library).
- ❌ Do NOT use camelCase on FoursquareAPI methods (`getPrivate` → use `GetPrivate`).
- ❌ Do NOT create temporary debug/test scripts in the repo root or anywhere outside `debug/` — extend the consolidated tools instead (see `debug-tools` skill).
- ❌ Do NOT hardcode credentials — read from `.env` via `getenv()`.
- ❌ Do NOT style Dojo widgets via external CSS or JS — inline styles only (see `dojo-widgets.instructions.md`).
- ❌ Do NOT skip anti-cache headers in dynamic legacy PHP pages.
- ❌ Do NOT commit `.env`, secrets, or `build-info.json`.
- ❌ Do NOT run `./dev.sh` and the Dev Container workflow at the same time.

## MCP Playwright agent authentication

The Playwright MCP uses headless/headed Chromium to browse the app. For authenticated pages, the agent injects a token captured from the host browser via CDP — see the **mcp-playwright-auth** skill for the full bootstrap/injection procedure. Never store passwords; the user authenticates visually in the host browser.

## Additional docs

- **Migration Guide**: `docs/MIGRATION.md`
- **Google Maps Migration**: `docs/GOOGLE_MAPS_MIGRATION.md`
- **API Keys Documentation**: `docs/API_KEYS_DOCUMENTATION.md`
- **Build & Deploy Guide**: `docs/BUILD_AND_DEPLOY.md`
- **Advanced Deploy Modes (TODO)**: `docs/TODO_DEPLOY_ADVANCED.md`
- **Playwright MCP Validation Runbook**: `docs/features/MCP_PLAYWRIGHT_VALIDATION.md`
- **README.md** for human-facing setup and feature docs

---

**Generated by AI as guidance**  
**Last Updated**: October 2026
