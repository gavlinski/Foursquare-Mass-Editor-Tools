<?php
/**
 * Endpoint de Informações de Versão e Build
 * 
 * Retorna informações sobre a build atual do sistema,
 * incluindo data, commit hash, e ambiente de execução.
 * 
 * Este arquivo é atualizado automaticamente durante o processo de build
 * pelos scripts build.sh e build-docker.sh
 * 
 * Retorna JSON quando solicitado por API (Accept: application/json)
 * Retorna HTML quando acessado pelo navegador
 */

// Caminho para o arquivo de build info gerado durante a build
$buildInfoFile = __DIR__ . '/build-info.json';

// Informações padrão caso o arquivo não exista
$defaultBuildInfo = [
    'build_date' => 'Development',
    'build_timestamp' => time(),
    'commit_hash' => 'dev',
    'commit_short' => 'dev',
    'branch' => 'unknown',
    'version' => 'dev',
    'build_source' => 'local',
    'environment' => 'development'
];

// Tenta carregar o arquivo de build info
$buildInfo = $defaultBuildInfo;
if (file_exists($buildInfoFile)) {
    $jsonContent = @file_get_contents($buildInfoFile);
    if ($jsonContent !== false) {
        $decodedInfo = @json_decode($jsonContent, true);
        if (is_array($decodedInfo)) {
            $buildInfo = array_merge($defaultBuildInfo, $decodedInfo);
        }
    }
}

// Adiciona informações do ambiente atual
$buildInfo['server_time'] = date('Y-m-d H:i:s T');
$buildInfo['server_timezone'] = date_default_timezone_get();
$buildInfo['php_version'] = PHP_VERSION;
$buildInfo['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';

// Informações de sessão (se disponível)
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$buildInfo['session_active'] = isset($_SESSION['oauth_token']);
$buildInfo['session_user'] = $_SESSION['user_data']['firstName'] ?? null;

// Calcula tempo desde a build
if (isset($buildInfo['build_timestamp']) && is_numeric($buildInfo['build_timestamp'])) {
    $buildTimestamp = (int)$buildInfo['build_timestamp'];
    $currentTimestamp = time();
    $diffSeconds = $currentTimestamp - $buildTimestamp;
    
    // Formata tempo decorrido
    if ($diffSeconds < 60) {
        $buildInfo['build_age'] = $diffSeconds . ' segundos atrás';
    } elseif ($diffSeconds < 3600) {
        $minutes = floor($diffSeconds / 60);
        $buildInfo['build_age'] = $minutes . ' minuto' . ($minutes > 1 ? 's' : '') . ' atrás';
    } elseif ($diffSeconds < 86400) {
        $hours = floor($diffSeconds / 3600);
        $buildInfo['build_age'] = $hours . ' hora' . ($hours > 1 ? 's' : '') . ' atrás';
    } else {
        $days = floor($diffSeconds / 86400);
        $buildInfo['build_age'] = $days . ' dia' . ($days > 1 ? 's' : '') . ' atrás';
    }
}

// Detecta se é requisição de API (JSON) ou navegador (HTML)
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$isJsonRequest = strpos($acceptHeader, 'application/json') !== false || 
                 isset($_GET['format']) && $_GET['format'] === 'json';

if ($isJsonRequest) {
    // Retorna JSON para requisições de API
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo json_encode($buildInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Retorna HTML para navegador
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Versão e Build - Foursquare Mass Editor</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #2c3e50;
            line-height: 1.6;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 25px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 0;
        }
        .back-link:hover {
            color: #764ba2;
        }
        .back-link svg {
            width: 16px;
            height: 16px;
        }
        h1 {
            color: #2c3e50;
            margin-top: -5px;
            margin-bottom: 10px;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .info-card h3 {
            margin: 0 0 10px 0;
            color: #34495e;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-card .value {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            word-break: break-all;
        }
        .info-card .detail {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 5px;
        }
        .json-section {
            background: #2d3436;
            color: #dfe6e9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            position: relative;
        }
        .json-section h3 {
            margin: 0 0 15px 0;
            color: #74b9ff;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .json-section pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            line-height: 1.6;
        }
        .copy-button {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .copy-button:hover {
            background: #764ba2;
        }
        .copy-button:active {
            transform: scale(0.95);
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-prod {
            background: #d4edda;
            color: #155724;
        }
        .badge-dev {
            background: #fff3cd;
            color: #856404;
        }
        .badge-docker {
            background: #cce5ff;
            color: #004085;
        }
        .badge-local {
            background: #e2e3e5;
            color: #383d41;
        }
        .badge-ci {
            background: #d4edda;
            color: #155724;
        }
        .badge-deploy {
            background: #fff3cd;
            color: #856404;
        }
        .nav-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .nav-footer a {
            color: #667eea;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            background: #f8f9fa;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .nav-footer a:hover {
            background: #667eea;
            color: white;
        }
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            .container {
                padding: 25px;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            📦 Versão e Build
        </h1>
        <p class="subtitle">Informações completas sobre a versão atual do sistema e configuração do ambiente.</p>

        <div class="info-grid">
            <div class="info-card">
                <h3>🏷️ Versão</h3>
                <div class="value"><?= htmlspecialchars($buildInfo['version']) ?></div>
                <div class="detail">
                    <?php if ($buildInfo['environment'] === 'production'): ?>
                        <span class="badge badge-prod">Production</span>
                    <?php else: ?>
                        <span class="badge badge-dev">Development</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="info-card">
                <h3>🔖 Commit</h3>
                <div class="value"><?= htmlspecialchars($buildInfo['commit_short']) ?></div>
                <div class="detail">Branch: <?= htmlspecialchars($buildInfo['branch']) ?></div>
            </div>

            <div class="info-card">
                <h3>📅 Data da Build</h3>
                <div class="value"><?= htmlspecialchars(date('d/m/Y H:i', $buildInfo['build_timestamp'])) ?></div>
                <div class="detail"><?= htmlspecialchars($buildInfo['build_age']) ?></div>
            </div>

            <div class="info-card">
                <h3>🛠️ Origem da Build</h3>
                <div class="value">
                    <?php 
                    $buildSource = $buildInfo['build_source'] ?? 'unknown';
                    switch($buildSource) {
                        case 'ci':
                            echo '<span class="badge badge-ci">Automático (CI/CD)</span>';
                            break;
                        case 'deploy':
                            echo '<span class="badge badge-deploy">Manual (Deploy Script)</span>';
                            break;
                        case 'local':
                            echo '<span class="badge badge-local">Manual (Desenvolvedor)</span>';
                            break;
                        default:
                            echo '<span class="badge badge-local">Desconhecido</span>';
                    }
                    ?>
                </div>
                <div class="detail">
                    <?php
                    switch($buildSource) {
                        case 'ci':
                            echo 'GitHub Actions Pipeline';
                            break;
                        case 'deploy':
                            echo 'Via script deploy.sh';
                            break;
                        case 'local':
                            echo 'Via script build.sh';
                            break;
                        default:
                            echo 'Origem não identificada';
                    }
                    ?>
                </div>
            </div>

            <div class="info-card">
                <h3>🐘 Versão PHP</h3>
                <div class="value"><?= htmlspecialchars($buildInfo['php_version']) ?></div>
                <div class="detail"><?= htmlspecialchars($buildInfo['server_software']) ?></div>
            </div>

            <div class="info-card">
                <h3>⏰ Horário do Servidor</h3>
                <div class="value"><?= htmlspecialchars(date('H:i:s')) ?></div>
                <div class="detail"><?= htmlspecialchars($buildInfo['server_timezone']) ?></div>
            </div>
        </div>

        <div class="json-section">
            <h3>📄 JSON Completo (API)</h3>
            <button class="copy-button" onclick="copyJSON()">Copiar JSON</button>
            <pre id="json-content"><?= json_encode($buildInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></pre>
        </div>

        <div class="nav-footer">
            <a href="debug/">← Ferramentas de Debug</a>
            <a href="main.php">Aplicação Principal</a>
            <a href="?format=json" target="_blank">Ver JSON Puro</a>
        </div>
    </div>

    <script>
        function copyJSON() {
            const jsonText = document.getElementById('json-content').textContent;
            navigator.clipboard.writeText(jsonText).then(() => {
                const button = document.querySelector('.copy-button');
                const originalText = button.textContent;
                button.textContent = '✓ Copiado!';
                button.style.background = '#27ae60';
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '#667eea';
                }, 2000);
            }).catch(err => {
                alert('Erro ao copiar: ' + err);
            });
        }
    </script>
</body>
</html>
