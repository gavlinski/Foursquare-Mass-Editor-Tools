---
description: "Use when writing or testing repository Copilot hooks, hook payloads, security warnings, or commit secret scanning."
applyTo: ".github/hooks/**, tests/hooks/**"
---

# Copilot Hook Conventions

- Keep hooks deterministic, local, synchronous, and narrowly scoped. Do not add network access, implicit installs, index mutation, or production-deploy actions.
- Emit only valid JSON on stdout. Use a temporary `progress` message for non-blocking warnings; use `permissionDecision: "deny"` only for explicitly approved blocking rules.
- Never print matched credentials, full commands, or sensitive file paths. Never inspect or log `data/mcp/playwright/foursquare.storage-state.json`.
- The commit scanner compares the tracked working tree with `HEAD` as a conservative superset, which can include changes outside a path-limited commit. It does not cover every credential format or every Git invocation; keep it advisory until exact commit selection is validated.
- Preserve normal deployment operations and test changes with `npm run test:hooks`.
- Document runtime support separately for Copilot CLI and VS Code Chat. A passing script test does not prove a host loads the hook configuration.