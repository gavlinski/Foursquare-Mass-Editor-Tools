<?php
/**
 * Teste Dojo (Google + fallback local)
 * Diagnóstico para validar carregamento primário via Google CDN
 * e fallback local em caso de falha.
 */
require_once __DIR__ . '/../includes/asset_helper.php';

function httpStatusForCandidates(array $urls): array
{
    foreach ($urls as $url) {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 8,
                'follow_location' => true,
                'max_redirects' => 3,
                'header' => "User-Agent: FMET-Dojo-Diag/1.0\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);

        $headers = @get_headers($url, true, $context);
        if ($headers !== false) {
            $statusLine = $headers[0] ?? 'UNKNOWN';
            if (preg_match('/\s(\d{3})\s/', (string)$statusLine, $m)) {
                $code = (int)$m[1];
                if ($code >= 200 && $code < 400) {
                    return [
                        'ok' => true,
                        'code' => $code,
                        'url' => $url,
                    ];
                }
                $last = [
                    'ok' => false,
                    'code' => $code,
                    'url' => $url,
                ];
            } else {
                $last = [
                    'ok' => false,
                    'code' => 0,
                    'url' => $url,
                ];
            }
        } else {
            $last = [
                'ok' => false,
                'code' => 0,
                'url' => $url,
            ];
        }
    }

    return $last ?? [
        'ok' => false,
        'code' => 0,
        'url' => '',
    ];
}

$isLocalMode = useLocalDojo();
$hasLocalAssets = function_exists('hasLocalDojoAssets') ? hasLocalDojoAssets() : false;
$forceFallback = getenv('DOJO_FORCE_FALLBACK') ?: ($_ENV['DOJO_FORCE_FALLBACK'] ?? false);
$googleProbeUrl = rtrim(DOJO_CDN_BASE, '/') . '/dojo/dojo.js';

$googleStaticChecks = [
    'Loader dojo.js' => [rtrim(DOJO_CDN_BASE, '/') . '/dojo/dojo.js'],
    'dojo/parser' => [rtrim(DOJO_CDN_BASE, '/') . '/dojo/parser.js'],
    'dojo/cookie' => [rtrim(DOJO_CDN_BASE, '/') . '/dojo/cookie.js'],
    'dojo/data/ItemFileReadStore' => [rtrim(DOJO_CDN_BASE, '/') . '/dojo/data/ItemFileReadStore.js'],
    'dijit/form/TextBox' => [rtrim(DOJO_CDN_BASE, '/') . '/dijit/form/TextBox.js'],
    'dojox/form/Uploader' => [rtrim(DOJO_CDN_BASE, '/') . '/dojox/form/Uploader.js'],
    'Tema tundra.css' => [rtrim(DOJO_CDN_BASE, '/') . '/dijit/themes/tundra/tundra.css'],
];

$googleStaticResults = [];
$googleStaticOkCount = 0;
foreach ($googleStaticChecks as $label => $candidates) {
    $res = httpStatusForCandidates($candidates);
    if ($res['ok']) {
        $googleStaticOkCount++;
    }
    $googleStaticResults[$label] = $res;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Dojo (Google + Local) - Foursquare Mass Editor</title>
    <?php dojo_script(['parseOnLoad' => true, 'isDebug' => true]); ?>
    <?php dojo_theme('tundra'); ?>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 30px;
            background: #f5f7fa;
            color: #2c3e50;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
            margin-top: -5px;
        }
        h2 {
            color: #34495e;
            margin-top: 25px;
            margin-bottom: 10px;
        }
        #status {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 1.1rem;
            border-left: 4px solid #95a5a6;
            background: #ecf0f1;
        }
        #status.success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        #status.error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 12px;
            margin: 15px 0 25px;
        }
        .status-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 0.95rem;
        }
        .status-card strong {
            display: block;
            margin-bottom: 6px;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .badge.google {
            background: #e8f0fe;
            color: #174ea6;
        }
        .badge.local {
            background: #e8f7ef;
            color: #166534;
        }
        .badge.warn {
            background: #fff3cd;
            color: #664d03;
        }
        .badge.unknown {
            background: #f1f3f5;
            color: #495057;
        }
        .probe-ok {
            color: #166534;
            font-weight: 600;
        }
        .probe-fail {
            color: #b91c1c;
            font-weight: 600;
        }
        .probe-unknown {
            color: #475569;
            font-weight: 600;
        }
        .module-list {
            margin-top: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        .module-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 10px 12px;
            border-bottom: 1px solid #eef2f7;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            background: #fff;
        }
        .module-row:last-child {
            border-bottom: 0;
        }
        .cdn-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 13px;
        }
        .cdn-table th,
        .cdn-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        .cdn-table th {
            background: #f8fafc;
        }
        .badge.ok {
            background: #d4edda;
            color: #155724;
        }
        .badge.fail {
            background: #f8d7da;
            color: #721c24;
        }
        pre {
            background: #2d3436;
            color: #dfe6e9;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .info-box {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            color: #0c5460;
        }
        ol {
            margin: 10px 0;
            padding-left: 25px;
        }
        ol li {
            margin: 8px 0;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e74c3c;
        }
    </style>
</head>
<body class="tundra">
    <div class="container">
        <h1>🔍 Diagnóstico Dojo (Google + fallback local)</h1>
        
        <div id="status"><?php echo $isLocalMode
            ? '🧭 Modo local detectado: validando Dojo local completo...'
            : '🔄 Verificando Google CDN (primário) e fallback local...'; ?></div>

        <div class="status-grid">
            <div class="status-card">
                <strong>Origem efetivamente carregada</strong>
                <div id="cdn-used"><span class="badge unknown">Detectando...</span></div>
            </div>
            <div class="status-card">
                <strong>Google CDN (disponibilidade)</strong>
                <div id="probe-google"><span class="probe-unknown">Testando...</span></div>
            </div>
            <div class="status-card">
                <strong>Fallback local (assets no servidor)</strong>
                <div id="probe-local-assets"><span class="probe-unknown">Validando...</span></div>
            </div>
        </div>

        <div class="status-card">
            <strong>Resultado de módulos essenciais (runtime)</strong>
            <div id="module-summary"><span class="probe-unknown">Aguardando dojo.addOnLoad...</span></div>
            <div class="module-list" id="module-list"></div>
        </div>

        <div class="status-card" style="margin-top: 12px;">
            <strong>Verificação estática do Google CDN</strong>
            <div>
                <span class="badge <?php echo $googleStaticOkCount === count($googleStaticChecks) ? 'ok' : 'fail'; ?>">
                    Static: <?php echo $googleStaticOkCount; ?>/<?php echo count($googleStaticChecks); ?>
                </span>
            </div>
            <table class="cdn-table">
                <thead>
                    <tr>
                        <th>Arquivo essencial</th>
                        <th>Status</th>
                        <th>URL efetiva</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($googleStaticResults as $label => $res): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($label); ?></td>
                            <td>
                                <span class="badge <?php echo $res['ok'] ? 'ok' : 'fail'; ?>">
                                    <?php echo (int)$res['code'] > 0 ? (int)$res['code'] : 'ERR'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars((string)$res['url']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="info-box">
            <h3>📋 O que este teste faz</h3>
            <p>Valida o fluxo atual do projeto: Google CDN como primário e fallback local como contingência. Também testa carregamento real de módulos Dojo usados na aplicação.</p>
        </div>
        
        <h2>Informações do Sistema</h2>
        <pre><?php
            echo "isProduction(): " . (isProduction() ? 'true' : 'false') . "\n";
            echo "useLocalDojo(): " . (useLocalDojo() ? 'true' : 'false') . "\n";
            echo "hasLocalDojoAssets(): " . ($hasLocalAssets ? 'true' : 'false') . "\n";
            echo "DOJO_FORCE_FALLBACK: " . ($forceFallback ? 'true' : 'false') . "\n";
            echo "dojo_url(): " . dojo_url() . "\n";
            echo "DOJO_CDN_BASE: " . DOJO_CDN_BASE . "\n";
            echo "DOJO_GOOGLE_PROBE_URL: " . $googleProbeUrl . "\n";
            echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
            echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'não definido') . "\n";
        ?></pre>
        
        <h2>📝 Instruções de Debug</h2>

        <h3>Desktop</h3>
        <ol>
            <li>Aguarde 3-8 segundos e confira os blocos de status.</li>
            <li>Verifique se a origem carregada foi Google ou Local.</li>
            <li>Confirme se os módulos essenciais ficaram em OK.</li>
            <li>No DevTools, filtre por <code>dojo</code> e confirme erros de rede/CSP.</li>
        </ol>

        <h3>Mobile</h3>
        <ol>
            <li>Aguarde 3-8 segundos e confirme status de origem/módulos.</li>
            <li>Faça uma captura de tela completa com os resultados visíveis.</li>
            <li>Repita em Safari e Chrome.</li>
            <li>Se falhar, recarregue uma vez e informe se o resultado mudou.</li>
        </ol>
    </div>
    
    <script>
        var FMET_DIAG = {
            localMode: <?php echo $isLocalMode ? 'true' : 'false'; ?>,
            hasLocalAssets: <?php echo $hasLocalAssets ? 'true' : 'false'; ?>,
            forceFallback: <?php echo $forceFallback ? 'true' : 'false'; ?>,
            primaryProbeUrl: <?php echo json_encode($googleProbeUrl); ?>,
            requiredModules: [
                'dojo.cookie',
                'dojo.parser',
                'dojo.data.ItemFileReadStore',
                'dijit.form.TextBox',
                'dijit.form.Button',
                'dijit.Dialog',
                'dojox.form.Uploader'
            ]
        };

        function detectCdnSource() {
            if (typeof dojo === 'undefined' || !dojo.baseUrl) {
                return { label: 'Indisponível', kind: 'unknown' };
            }

            var baseUrl = String(dojo.baseUrl);
            if (baseUrl.indexOf('ajax.googleapis.com') !== -1) {
                return { label: 'Google CDN (primário)', kind: 'google' };
            }
            if (baseUrl.indexOf('/js/dojo') !== -1) {
                return { label: 'Arquivos locais', kind: 'local' };
            }

            return { label: 'Origem não identificada', kind: 'unknown' };
        }

        function updateCdnBadge() {
            var target = document.getElementById('cdn-used');
            if (!target) return;

            var source = detectCdnSource();
            target.innerHTML = '<span class="badge ' + source.kind + '">' + source.label + '</span>';
        }

        function probeCdn(fileUrl, targetId) {
            var target = document.getElementById(targetId);
            if (!target) return;

            var testUrl = fileUrl + '?probe=' + Date.now();

            fetch(testUrl, { method: 'GET', mode: 'no-cors', cache: 'no-store' })
                .then(function() {
                    target.innerHTML = '<span class="probe-ok">Reachable</span>';
                })
                .catch(function() {
                    target.innerHTML = '<span class="probe-fail">Unavailable</span>';
                });
        }

        function renderModuleResult(moduleName, ok, details) {
            var list = document.getElementById('module-list');
            if (!list) return;

            var row = document.createElement('div');
            row.className = 'module-row';

            var statusHtml = ok
                ? '<span class="probe-ok">OK</span>'
                : '<span class="probe-fail">FALHA' + (details ? ' - ' + details : '') + '</span>';

            row.innerHTML = '<span>' + moduleName + '</span><span>' + statusHtml + '</span>';
            list.appendChild(row);
        }

        function testRequiredModules() {
            var summary = document.getElementById('module-summary');
            if (!summary) return;

            if (typeof dojo === 'undefined') {
                summary.innerHTML = '<span class="probe-fail">Dojo não carregado, teste de módulos não executado.</span>';
                return;
            }

            var modules = FMET_DIAG.requiredModules || [];
            var loaded = 0;
            var failed = 0;

            modules.forEach(function(moduleName) {
                try {
                    dojo.require(moduleName);
                    loaded += 1;
                    renderModuleResult(moduleName, true, '');
                } catch (e) {
                    failed += 1;
                    renderModuleResult(moduleName, false, (e && e.message) ? e.message : String(e));
                }
            });

            dojo.addOnLoad(function() {
                if (failed === 0) {
                    summary.innerHTML = '<span class="probe-ok">Todos os módulos essenciais foram requisitados com sucesso (' + loaded + ').</span>';
                } else {
                    summary.innerHTML = '<span class="probe-fail">Falhas ao requisitar módulos: ' + failed + ' de ' + modules.length + '.</span>';
                }
            });
        }

        console.log('🔍 Diagnóstico Dojo (Google + local)');
        if (typeof window.dojoConfig !== 'undefined') {
            console.log('dojoConfig (global):', window.dojoConfig);
        } else if (typeof dojo !== 'undefined' && dojo && dojo.config) {
            console.log('dojoConfig (dojo.config):', dojo.config);
        } else {
            console.warn('dojoConfig não disponível no escopo global.');
        }

        if (typeof dojo !== 'undefined') {
            console.log('dojo version:', dojo.version);
            console.log('dojo.baseUrl:', dojo.baseUrl);
        } else {
            console.warn('Dojo não foi carregado.');
        }

        updateCdnBadge();
        probeCdn(FMET_DIAG.primaryProbeUrl, 'probe-google');

        var localAssetsTarget = document.getElementById('probe-local-assets');
        if (localAssetsTarget) {
            localAssetsTarget.innerHTML = FMET_DIAG.hasLocalAssets
                ? '<span class="probe-ok">Completo no servidor</span>'
                : '<span class="probe-fail">Incompleto no servidor</span>';
        }

        if (FMET_DIAG.forceFallback) {
            var sourceTarget = document.getElementById('cdn-used');
            if (sourceTarget) {
                sourceTarget.innerHTML += ' <span class="badge warn">DOJO_FORCE_FALLBACK=true</span>';
            }
        }

        if (window.__FMET_DOJO_FALLBACK_FAILED__ === true) {
            var sourceTarget2 = document.getElementById('cdn-used');
            if (sourceTarget2) {
                sourceTarget2.innerHTML += ' <span class="badge warn">fallback falhou</span>';
            }
        }
        
        // Testa se dojo.cookie está disponível ANTES do require
        if (typeof dojo === 'undefined') {
            var fatalStatus = document.getElementById('status');
            if (fatalStatus) {
                fatalStatus.className = 'error';
                fatalStatus.innerHTML = '❌ <strong>Erro!</strong> Dojo não foi carregado. Verifique Google CDN e fallback local.';
            }
        }

        if (typeof dojo !== 'undefined') {
            console.log('dojo.cookie (antes do require):', typeof dojo.cookie);
        
        // Agora faz o require
            dojo.require("dojo.cookie");
        
        // Aguarda o módulo carregar
            setTimeout(function() {
                console.log('dojo.cookie (depois do require):', typeof dojo.cookie);
            
                var status = document.getElementById('status');
                var source = detectCdnSource();
            
                if (typeof dojo.cookie === 'function') {
                    status.className = 'success';
                    if (FMET_DIAG.localMode) {
                        status.innerHTML = '✅ <strong>Sucesso!</strong> dojo.cookie carregou em modo local. Origem detectada: <strong>' + source.label + '</strong>';
                    } else {
                        status.innerHTML = '✅ <strong>Sucesso!</strong> dojo.cookie carregou corretamente. Origem detectada: <strong>' + source.label + '</strong>';
                    }
                
                    // Testa o cookie
                    dojo.cookie("test_cookie", "test_value");
                    var testValue = dojo.cookie("test_cookie");
                    console.log('✅ Teste de cookie realizado:', testValue);
                    console.log('✅ dojo.cookie está funcionando corretamente');
                } else {
                    status.className = 'error';
                    status.innerHTML = '❌ <strong>Erro!</strong> dojo.cookie não está disponível';
                    console.error('❌ Módulo dojo.cookie não carregou. Verifique:');
                    console.error('- Rede (DevTools → Network)');
                    console.error('- Console (erros de carregamento)');
                    console.error('- CSP (Content-Security-Policy)');
                }

                testRequiredModules();
            }, 1000);
        }
    </script>
    
    <footer style="text-align: center; padding: 30px 20px; margin-top: 40px; border-top: 2px solid #e0e0e0;">
        <a href="index.php" style="display: inline-flex; align-items: center; gap: 8px; color: #3498db; text-decoration: none; padding: 12px 24px; border-radius: 8px; background: #f8f9fa; font-weight: 500; font-size: 14px; transition: all 0.2s; border: 1px solid #dee2e6;" onmouseover="this.style.color='#2980b9'; this.style.background='#e9ecef'; this.style.borderColor='#ced4da';" onmouseout="this.style.color='#3498db'; this.style.background='#f8f9fa'; this.style.borderColor='#dee2e6';">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
            </svg>
            Voltar para Debug
        </a>
    </footer>
</body>
</html>
