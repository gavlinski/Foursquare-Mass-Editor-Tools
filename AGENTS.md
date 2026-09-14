# AGENTS.md

This file is the canonical, tool-agnostic entry point for AI coding agents working in this repository (Claude Code, Cursor, Codex, Copilot, etc. — see [agents.md](https://agents.md)). `.github/copilot-instructions.md` is a thin, VS Code/Copilot-specific wrapper that points back here.

## Project overview

Foursquare Mass Editor Tools ("Elio Tools") is a PHP/Dojo web app for bulk editing Foursquare venues via the Foursquare API v2.

- **Backend**: PHP 8.1, hybrid architecture — modern PSR-4 code in `src/` (`ElioTools\Config`, `ElioTools\Security`, `ElioTools\Api`) + legacy procedural PHP 5.4+ in the repo root (`edit.php`, `load.php`, `main.php`, `index.php`).
- **Frontend**: Dojo Toolkit v1.8 (legacy widgets) + ES6 JavaScript (`js/session-manager.js` and friends).
- **API**: Foursquare API v2, OAuth2.
- **Infra**: Docker + Apache 2.4 + Composer.

```
src/            # Modern PSR-4: Api/, Config/, Security/
js/             # Frontend: legacy Dojo-integrated + modern ES6
debug/          # Consolidated debug/test tools (never add new test files elsewhere)
.github/skills/       # Task-triggered domain knowledge (Agent Skills spec)
.github/instructions/ # Path-scoped coding conventions (applyTo globs)
Root *.php      # Legacy, functional — keep working, don't gratuitously refactor
```

## Dev environment

Two supported workflows. **Never run both at once** — both bind ports 80/443.

- **VS Code Dev Container** (recommended): reopen the folder in the container; then use `./scripts/dev-internal.sh {status|reload|logs|build}`. Details: `.github/skills/devcontainer-setup/SKILL.md`, `docs/DEV_CONTAINER.md`.
- **External Docker**: `./dev.sh run` / `./dev.sh status` from the host, outside the container. Details: `.github/skills/deploy-workflows/SKILL.md`.

App is served at `https://localhost/4sqmet/`.

## Build & test commands

```bash
./build.sh                 # Minify JS, generate build-info.json (see build-system skill)
composer install           # PHP dependencies
npx playwright test        # E2E regression suite (see playwright-regression skill)
npx playwright test tests/e2e/01-session-auth.spec.ts   # single flow
```

There is no PHP unit test suite yet — validate PHP changes with `php -l <file>` and by exercising the relevant `debug/` tool.

## Code style

Coding conventions are enforced through path-scoped instruction files (VS Code `applyTo` glob instructions, but the rules apply regardless of tool):

- `.github/instructions/php-legacy.instructions.md` — root-level PHP (anti-cache headers, `FoursquareAPI` PascalCase calls, cookie/session hardening).
- `.github/instructions/php-modern.instructions.md` — `src/**/*.php` (PSR-4, `strict_types`, type hints).
- `.github/instructions/dojo-widgets.instructions.md` — `edit.php`, `main.php`, `load.php` (Dojo widgets only take inline styles, never external CSS).
- `.github/instructions/javascript-conventions.instructions.md` — `js/**/*.js` (ES6 + Dojo bridging, logging convention).

## Domain knowledge (skills)

Deeper, task-triggered knowledge lives in `.github/skills/*/SKILL.md`. Load the relevant one before touching its area: `build-system`, `deploy-workflows`, `deploy-safety`, `ci-cd-configuration`, `devcontainer-setup`, `debug-tools`, `scroll-preservation`, `session-management`, `version-info`, `analytics-hardening`, `venue-editing-workflow`, `mcp-playwright-auth`, `playwright-regression`.

## Security considerations

- Never commit `.env`, credentials, or secrets — configuration is read via `getenv()` with a documented fallback for local dev only.
- Never modify the vendored `FoursquareAPI.Class.php`.
- Always guard `setcookie()` calls with `headers_sent()` and set `secure`/`httponly`/`samesite=Strict`.
- Never create ad-hoc debug/test scripts in the repo root — use and extend the consolidated tools in `debug/` (`debug-tools` skill).
- Treat any OWASP Top 10 class of issue (injection, broken auth, sensitive data exposure, etc.) as a blocker, not a follow-up.

## Deployment

Three methods exist — CI/CD (GitHub Actions, the default for normal work), `deploy.sh` (manual, only for the 5 documented emergency scenarios), and `dev.sh`/Dev Container (local only, never deploys). Always `git push` before running `deploy.sh` — it deploys from the remote branch, not local files. Full detail: `deploy-workflows` and `deploy-safety` skills, `docs/BUILD_AND_DEPLOY.md`.

## For AI agents — index

| Need | Where |
|------|-------|
| High-level architecture, forbidden actions | `.github/copilot-instructions.md` |
| Path-scoped coding conventions | `.github/instructions/*.instructions.md` |
| Task-specific domain knowledge | `.github/skills/*/SKILL.md` |
| Human-facing docs (setup, features) | `README.md`, `docs/` |
