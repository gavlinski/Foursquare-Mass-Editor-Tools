#!/usr/bin/env node

/**
 * Helper para extrair e usar tokens do Foursquare do storage state salvo
 * Uso: node get-foursquare-token.js
 */

const fs = require('fs');
const path = require('path');

const STORAGE_STATE_FILE = '/var/www/html/data/mcp/playwright/foursquare.storage-state.json';

try {
  if (!fs.existsSync(STORAGE_STATE_FILE)) {
    console.error('Storage state not found. Run: ./scripts/mcp-foursquare-auth-bootstrap.sh');
    process.exit(1);
  }

  const data = JSON.parse(fs.readFileSync(STORAGE_STATE_FILE, 'utf8'));
  const cookies = data.cookies || [];

  // Procura pelo oauth_token (pode ser localhost ou .foursquare.com)
  const oauthToken = cookies.find(c => c.name === 'oauth_token' && c.value.length > 20);
  const phpsessid = cookies.find(c => c.domain === 'localhost' && c.name === 'PHPSESSID');

  if (!oauthToken) {
    console.error('OAuth token not found in storage state');
    process.exit(1);
  }

  // Retorna um objeto JSON que pode ser processado pelo agent
  console.log(JSON.stringify({
    oauth_token: oauthToken.value,
    phpsessid: phpsessid?.value || '',
    foursquare_domain: oauthToken.domain,
    expires: new Date(oauthToken.expires * 1000).toISOString(),
    authenticated: true
  }, null, 2));

} catch (error) {
  console.error('Error:', error.message);
  process.exit(1);
}
