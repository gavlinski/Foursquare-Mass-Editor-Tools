#!/usr/bin/env node

/**
 * Injeta cookies do storage state salvo diretamente no contexto do Playwright MCP
 * Uso: NODE_PATH=... node playwright-inject-cookies.js <storage-state-file>
 */

const fs = require('fs');
const path = require('path');

const storageStateFile = process.argv[2] || '/var/www/html/data/mcp/playwright/foursquare.storage-state.json';

if (!fs.existsSync(storageStateFile)) {
  console.error(`❌ Storage state file not found: ${storageStateFile}`);
  process.exit(1);
}

try {
  const storageState = JSON.parse(fs.readFileSync(storageStateFile, 'utf8'));
  
  // Exporta objeto JavaScript que pode ser injetado no MCP
  console.log(JSON.stringify({
    cookies: storageState.cookies || [],
    origins: storageState.origins || []
  }, null, 2));
  
} catch (error) {
  console.error(`❌ Failed to parse storage state: ${error.message}`);
  process.exit(1);
}
