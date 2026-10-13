#!/usr/bin/env node
'use strict';

const fs = require('node:fs');

const threats = [
  {
    id: 'remove-critical-path',
    pattern: /\brm\s+(?:-[a-z]*r[a-z]*f[a-z]*|-[a-z]*f[a-z]*r[a-z]*)\s+(?:--\s+)?(?:\/(?:\s|$)|~(?:\/|\s|$)|\.{1,2}(?:\/|\s|$)|(?:\.\/)?\.git(?:\/|\s|$)|(?:\.\/)?\.env(?:\s|$))/i,
    reason: 'Recursive removal of a critical path',
  },
  {
    id: 'git-reset-hard',
    pattern: /\bgit\s+reset\s+[^\n;&|]*--hard\b/i,
    reason: 'Hard git reset can discard uncommitted work',
  },
  {
    id: 'git-clean-force',
    pattern: /\bgit\s+clean\s+-[a-z]*(?=[a-z]*f)(?=[a-z]*d)[a-z]*\b/i,
    reason: 'Forced git clean can delete untracked work',
  },
  {
    id: 'git-force-push',
    pattern: /\bgit\s+push\b[^\n;&|]*\s(?:--force(?!-with-lease)\b|-f\b)/i,
    reason: 'Force push can rewrite remote history',
  },
];

function parseToolArgs(value) {
  if (typeof value !== 'string') return value;

  try {
    return JSON.parse(value);
  } catch {
    return { command: value };
  }
}

function getCommand(value) {
  const args = parseToolArgs(value);
  if (typeof args === 'string') return args;
  if (!args || typeof args !== 'object') return '';

  for (const key of ['command', 'script']) {
    if (typeof args[key] === 'string') return args[key];
  }

  return '';
}

function readPayload() {
  try {
    return JSON.parse(fs.readFileSync(0, 'utf8'));
  } catch {
    return null;
  }
}

function writeResult(result) {
  process.stdout.write(`${JSON.stringify(result)}\n`);
}

const payload = readPayload();
if (!payload || typeof payload !== 'object') process.exit(0);

const toolName = payload.toolName ?? payload.tool_name ?? '';
if (!['bash', 'powershell', 'Bash'].includes(toolName)) process.exit(0);

const command = getCommand(payload.toolArgs ?? payload.tool_input);
if (!command) process.exit(0);

const finding = threats.find((threat) => threat.pattern.test(command));
if (!finding) process.exit(0);

const context = `Tool Guardian: ${finding.id} detected. ${finding.reason}. Review the command and choose a safer alternative.`;
if (process.env.TOOL_GUARD_MODE === 'block') {
  writeResult({
    permissionDecision: 'deny',
    permissionDecisionReason: context,
  });
} else {
  writeResult({ type: 'progress', message: context, temporary: true });
}