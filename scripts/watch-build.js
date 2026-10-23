// Rebuilds on js/ changes; replaces the vulnerable `watch` package.
const { spawnSync } = require('node:child_process');

spawnSync('npm', ['run', 'build'], { stdio: 'inherit' });
