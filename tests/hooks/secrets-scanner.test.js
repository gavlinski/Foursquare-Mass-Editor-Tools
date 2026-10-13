'use strict';

const assert = require('node:assert/strict');
const { execFileSync, spawnSync } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const test = require('node:test');

const scannerPath = path.resolve(__dirname, '../../.github/hooks/scripts/scan-commit-secrets.js');

function createRepository(t) {
  const repositoryRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'elio-hook-secret-test-'));
  t.after(() => fs.rmSync(repositoryRoot, { recursive: true, force: true }));

  execFileSync('git', ['init', '-q'], { cwd: repositoryRoot });
  execFileSync('git', ['config', 'user.name', 'Hook Test'], { cwd: repositoryRoot });
  execFileSync('git', ['config', 'user.email', 'hook-test@example.invalid'], { cwd: repositoryRoot });
  fs.writeFileSync(path.join(repositoryRoot, 'tracked.txt'), 'baseline\n');
  fs.mkdirSync(path.join(repositoryRoot, 'data/mcp/playwright'), { recursive: true });
  fs.writeFileSync(path.join(repositoryRoot, 'data/mcp/playwright/foursquare.storage-state.json'), '{}\n');
  execFileSync('git', ['add', '.'], { cwd: repositoryRoot });
  execFileSync('git', ['commit', '-qm', 'baseline'], { cwd: repositoryRoot });

  return repositoryRoot;
}

function invokeScanner(repositoryRoot, command) {
  const result = spawnSync(process.execPath, [scannerPath], {
    cwd: repositoryRoot,
    encoding: 'utf8',
    input: JSON.stringify({ toolName: 'bash', toolArgs: { command } }),
  });

  assert.equal(result.status, 0, result.stderr);
  return result.stdout.trim() ? JSON.parse(result.stdout) : {};
}

test('warns on high-confidence credentials in tracked changes without revealing values', (t) => {
  const repositoryRoot = createRepository(t);
  const fakeCredential = ['ghp_', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456'].join('');
  fs.writeFileSync(path.join(repositoryRoot, 'tracked.txt'), `GITHUB_TOKEN=${fakeCredential}\n`);

  const result = invokeScanner(repositoryRoot, 'git commit -am "update"');

  assert.equal(result.type, 'progress');
  assert.equal(result.temporary, true);
  assert.doesNotMatch(result.message, new RegExp(fakeCredential));
  assert.doesNotMatch(result.message, /tracked\.txt/);
});

test('ignores placeholders and the Playwright storage-state path', (t) => {
  const repositoryRoot = createRepository(t);
  fs.writeFileSync(path.join(repositoryRoot, 'tracked.txt'), 'GITHUB_TOKEN=YOUR_TOKEN_HERE\n');

  assert.deepEqual(invokeScanner(repositoryRoot, 'git commit -am "placeholder"'), {});

  fs.writeFileSync(
    path.join(repositoryRoot, 'data/mcp/playwright/foursquare.storage-state.json'),
    `{"token":"${['ghp_', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456'].join('')}"}\n`,
  );

  assert.deepEqual(invokeScanner(repositoryRoot, 'git commit -am "storage state"'), {});
});

test('does not scan unrelated shell commands or non-shell tools', (t) => {
  const repositoryRoot = createRepository(t);
  const fakeCredential = ['ghp_', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456'].join('');
  fs.writeFileSync(path.join(repositoryRoot, 'tracked.txt'), `${fakeCredential}\n`);

  assert.deepEqual(invokeScanner(repositoryRoot, 'git status --short'), {});

  const result = spawnSync(process.execPath, [scannerPath], {
    cwd: repositoryRoot,
    encoding: 'utf8',
    input: JSON.stringify({ toolName: 'view', toolArgs: { command: 'git commit' } }),
  });

  assert.equal(result.status, 0);
  assert.equal(result.stdout, '');
});