<?php
/**
 * Teste de Geocodificação
 *
 * Testa geo/geocode do Foursquare com Google Maps JavaScript como fallback.
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$mapsConfig = include __DIR__ . '/../includes/google_maps_config.php';
$apiKey = $mapsConfig['google_maps_api_key'] ?? null;
$apiKeyConfigured = $apiKey && $apiKey !== 'YOUR_GOOGLE_MAPS_API_KEY' && $apiKey !== 'SUA_GOOGLE_MAPS_API_KEY_AQUI';
$oauthToken = $_SESSION['oauth_token'] ?? ($_COOKIE['oauth_token'] ?? '');
$canGeocode = $oauthToken !== '' || $apiKeyConfigured;

require_once __DIR__ . '/../includes/asset_helper.php';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teste de Geocodificação</title>
    <script>
        window.debugLog = window.debugLog || function() {};
        window.debugInfo = window.debugInfo || function() {};
        window.isLocalhost = function() { return location.hostname === 'localhost' || location.hostname === '127.0.0.1'; };
    </script>
    <script src="../js/google-maps-config.php?v=5.0.1"></script>
    <?php script_versioned('../js/google-maps.js'); ?>
    <?php script_versioned('../js/fsq-autocomplete.js'); ?>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; color: #333; }
        main { max-width: 900px; margin: 0 auto; }
        h1 { color: #667eea; }
        .section, .test-case {
            margin: 20px 0;
            padding: 18px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-ok { color: #218838; font-weight: bold; }
        .status-error { color: #c82333; font-weight: bold; }
        .muted { color: #666; }
        .test-case h2 { margin: 0 0 12px; font-size: 1.1rem; }
        .test-case button, #testAll {
            border: 0;
            border-radius: 5px;
            padding: 9px 14px;
            background: #667eea;
            color: white;
            cursor: pointer;
        }
        button:disabled { opacity: .65; cursor: wait; }
        pre {
            margin: 12px 0 0;
            padding: 12px;
            background: #f9f9f9;
            border-left: 3px solid #667eea;
            overflow-x: auto;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            font-size: 12px;
        }
        footer { text-align: center; padding: 30px 20px; margin-top: 40px; border-top: 2px solid #e0e0e0; background: white; }
        footer a {
            display: inline-flex; align-items: center; gap: 10px; padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; text-decoration: none; border-radius: 8px; font-weight: 600;
        }
    </style>
</head>
<body>
<main>
    <h1>🗺️ Teste de Geocodificação</h1>
    <section class="section">
        <strong>Google Maps (fallback):</strong>
        <?php if ($apiKeyConfigured): ?>
            <span class="status-ok">configurada</span>
        <?php else: ?>
            <span class="status-error">não configurada</span>
        <?php endif; ?>
        <p>Este teste usa primeiro a API <code>geo/geocode</code> do Foursquare com a sessão autenticada e, se ela falhar, tenta o Google Maps JavaScript Geocoder no navegador.</p>
        <?php if (!$apiKeyConfigured && !$oauthToken): ?>
            <p class="status-error">Autentique-se para usar a API de geocodificação do Foursquare ou configure <code>GOOGLE_MAPS_API_KEY</code> para o fallback Google.</p>
            <p><a href="../docs/GEOCODING_SETUP.md" target="_blank" rel="noopener">Ver instruções de configuração</a></p>
        <?php endif; ?>
        <button id="testAll" type="button" <?php echo $canGeocode ? '' : 'disabled'; ?>>Testar todos os endereços</button>
        <p id="overallStatus" class="muted" role="status" aria-live="polite"></p>
    </section>

    <?php
    $enderecos = [
        'Canoas, RS',
        'Porto Alegre, RS, Brasil',
        'Av. Paulista, São Paulo',
        'Rio de Janeiro, RJ'
    ];
    foreach ($enderecos as $index => $endereco):
    ?>
        <section class="test-case" data-address="<?php echo htmlspecialchars($endereco, ENT_QUOTES, 'UTF-8'); ?>">
            <h2>📍 Endereço: <?php echo htmlspecialchars($endereco, ENT_QUOTES, 'UTF-8'); ?></h2>
            <button type="button" class="testAddress" <?php echo $canGeocode ? '' : 'disabled'; ?>>Geocodificar</button>
            <span class="caseStatus muted" role="status" aria-live="polite"></span>
            <pre class="caseResult">Aguardando teste.</pre>
        </section>
    <?php endforeach; ?>

    <footer>
        <a href="index.php">← Voltar para Debug</a>
    </footer>
</main>
<script>
    var oauthToken = <?php echo json_encode($oauthToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    (function() {
        var cases = Array.prototype.slice.call(document.querySelectorAll('.test-case'));
        var buttons = Array.prototype.slice.call(document.querySelectorAll('.testAddress'));
        var allButton = document.getElementById('testAll');
        var overallStatus = document.getElementById('overallStatus');
        var running = false;

        function testarCaso(testCase) {
            var address = testCase.getAttribute('data-address');
            var button = testCase.querySelector('.testAddress');
            var status = testCase.querySelector('.caseStatus');
            var result = testCase.querySelector('.caseResult');
            button.disabled = true;
            status.className = 'caseStatus muted';
            status.textContent = 'Consultando geocodificação...';
            result.textContent = '';

            return fsqObterLl(address, oauthToken).then(function(ll) {
                status.className = 'caseStatus status-ok';
                status.textContent = 'Sucesso';
                result.textContent = JSON.stringify({ status: 'OK', address: address, ll: ll }, null, 2);
                return true;
            }).catch(function(error) {
                status.className = 'caseStatus status-error';
                status.textContent = 'Falha';
                result.textContent = JSON.stringify({ status: 'ERROR', address: address, error: error.message }, null, 2);
                return false;
            }).finally(function() {
                button.disabled = false;
            });
        }

        buttons.forEach(function(button) {
            button.addEventListener('click', function() {
                testarCaso(button.closest('.test-case'));
            });
        });

        allButton.addEventListener('click', function() {
            if (running) return;
            running = true;
            allButton.disabled = true;
            overallStatus.textContent = 'Testando endereços...';
            Promise.all(cases.map(testarCaso)).then(function(results) {
                var sucesso = results.filter(Boolean).length;
                overallStatus.className = sucesso === results.length ? 'status-ok' : 'status-error';
                overallStatus.textContent = sucesso + ' de ' + results.length + ' endereços convertidos.';
            }).finally(function() {
                running = false;
                allButton.disabled = false;
            });
        });
    })();
</script>
</body>
</html>
