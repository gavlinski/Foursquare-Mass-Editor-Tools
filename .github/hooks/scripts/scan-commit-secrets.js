#!/usr/bin/env node
'use strict';

const { spawnSync } = require('node:child_process');
const fs = require('node:fs');

const credentialPatterns = [
  /-----BEGIN (?:RSA |EC |OPENSSH |DSA |PGP )?PRIVATE KEY-----/,
  /\bgh[pousr]_[A-Za-z0-9_]{20,}\b/,
  /\bgithub_pat_[A-Za-z0-9_]{20,}\b/,
  /\bAIza[0-9A-Za-z_-]{35}\b/,
  /\bAKIA[0-9A-Z]{16}\b/,
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

const payload = readPayload();
if (!payload || typeof payload !== 'object') process.exit(0);

const toolName = payload.toolName ?? payload.tool_name ?? '';
if (!['bash', 'powershell', 'Bash'].includes(toolName)) process.exit(0);

const command = getCommand(payload.toolArgs ?? payload.tool_input);
if (!/\bgit\s+commit\b/i.test(command)) process.exit(0);

const diff = spawnSync('git', [
  'diff',
  '--no-ext-diff',
  '--no-textconv',
  '--no-color',
  '--unified=0',
  'HEAD',
  '--',
  '.',
  ':!data/mcp/playwright/foursquare.storage-state.json',
], {
  cwd: process.cwd(),
  encoding: 'utf8',
  maxBuffer: 8 * 1024 * 1024,
});

if (diff.error || diff.status !== 0) process.exit(0);

const content = `${command}\n${diff.stdout}`;
if (!credentialPatterns.some((pattern) => pattern.test(content))) process.exit(0);

process.stdout.write(`${JSON.stringify({
  type: 'progress',
  message: 'Secret scanner found a high-confidence credential pattern in the commit command or tracked working-tree diff. Review before committing; matching values and paths are omitted.',
  temporary: true,
})}\n`);