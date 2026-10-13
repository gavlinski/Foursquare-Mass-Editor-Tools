'use strict';

const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const path = require('node:path');
const test = require('node:test');

const repositoryRoot = path.resolve(__dirname, '../..');
const hookPath = path.join(repositoryRoot, '.github/hooks/scripts/guard-tools.js');

function invokeHook(payload, mode) {
  const result = spawnSync(process.execPath, [hookPath], {
    cwd: repositoryRoot,
    encoding: 'utf8',
    env: { ...process.env, TOOL_GUARD_MODE: mode },
    input: JSON.stringify(payload),
  });

  assert.equal(result.status, 0, result.stderr);
  return result.stdout.trim() ? JSON.parse(result.stdout) : {};
}

test('allows normal deploy and git commands without a guardian decision', () => {
  for (const command of [
    'git checkout refactor-ia && git pull origin refactor-ia',
    'docker rm 4sqmet && docker image prune -f',
    'git push --force-with-lease origin feature/test',
  ]) {
    assert.deepEqual(invokeHook({ toolName: 'bash', toolArgs: { command } }), {});
  }
});

test('warns on destructive shell commands without changing the permission decision', () => {
  const cases = [
    ['rm -rf .git/', /remove-critical-path/],
    ['git reset --hard', /git-reset-hard/],
    ['git clean -fd', /git-clean-force/],
    ['git push origin main --force', /git-force-push/],
  ];

  for (const [command, finding] of cases) {
    const result = invokeHook({
      toolName: 'bash',
      toolArgs: JSON.stringify({ command }),
    });

    assert.equal(result.type, 'progress');
    assert.equal(result.temporary, true);
    assert.match(result.message, finding);
    assert.equal(result.message.includes(command), false);
  }
});

test('supports VS Code compatible payloads and blocks only in explicit block mode', () => {
  const result = invokeHook({
    hook_event_name: 'PreToolUse',
    tool_name: 'Bash',
    tool_input: { command: 'git push origin main --force' },
  }, 'block');

  assert.equal(result.permissionDecision, 'deny');
  assert.match(result.permissionDecisionReason, /git-force-push/);
  assert.doesNotMatch(result.permissionDecisionReason, /git push origin main/);
});

test('ignores non-shell tools and malformed payloads', () => {
  assert.deepEqual(invokeHook({ toolName: 'view', toolArgs: { command: 'rm -rf /' } }), {});

  const result = spawnSync(process.execPath, [hookPath], {
    cwd: repositoryRoot,
    encoding: 'utf8',
    input: '{invalid',
  });

  assert.equal(result.status, 0);
  assert.equal(result.stdout, '');
});