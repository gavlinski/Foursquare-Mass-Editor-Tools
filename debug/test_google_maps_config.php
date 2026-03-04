<?php
/**
 * Debug: Teste de configuração do Google Maps
 * 
 * Este arquivo verifica se as credenciais do Google Maps estão sendo carregadas corretamente
 * 
 * NOTA: Com --env-file no docker run, getenv() funciona nativamente sem precisar de Dotenv
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug: Google Maps Configuration</title>
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
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 0;
        }
        .back-link:hover {
            color: #2980b9;
        }
        .back-link svg {
            width: 16px;
            height: 16px;
        }
        h2 { 
            color: #2c3e50; 
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-top: 0;
            margin-bottom: 20px;
        }
        h3 { 
            color: #34495e; 
            margin-top: 25px;
            margin-bottom: 10px;
        }
        pre { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 5px;
            border-left: 4px solid #3498db;
            overflow-x: auto;
            font-size: 0.9rem;
        }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .warning { color: #f39c12; }
        .nav-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .nav-footer a {
            color: #3498db;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            background: #ecf0f1;
            font-size: 0.9rem;
        }
        .nav-footer a:hover {
            background: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔧 Debug: Google Maps Configuration</h2>

        <h3>1. Variáveis de Ambiente (.env)</h3>
        <pre><?php
echo "GOOGLE_MAPS_API_KEY: " . (getenv('GOOGLE_MAPS_API_KEY') ?: '<span class="error">NÃO DEFINIDA</span>') . "\n";
echo "GOOGLE_MAPS_MAP_ID: " . (getenv('GOOGLE_MAPS_MAP_ID') ?: '<span class="error">NÃO DEFINIDA</span>') . "\n";
?></pre>

        <h3>2. Arquivo google_maps_credentials.php</h3>
        <pre><?php
if (file_exists(__DIR__ . '/../includes/google_maps_credentials.php')) {
    $credentials = include __DIR__ . '/../includes/google_maps_credentials.php';
    echo "<span class='success'>✅ Arquivo existe</span>\n";
    echo "API Key: " . ($credentials['google_maps_api_key'] ?? '<span class="error">NÃO DEFINIDA</span>') . "\n";
    echo "Map ID: " . ($credentials['google_maps_map_id'] ?? '<span class="error">NÃO DEFINIDA</span>') . "\n";
    
    // Verifica se são valores placeholder
    if (isset($credentials['google_maps_api_key']) && 
        (strpos($credentials['google_maps_api_key'], 'YOUR_') !== false || 
         strpos($credentials['google_maps_api_key'], 'SUA_') !== false)) {
        echo "\n<span class='warning'>⚠️ PROBLEMA: API Key está com valor placeholder!</span>\n";
        echo "   O arquivo não está lendo do .env corretamente.\n";
    }
} else {
    echo "<span class='error'>❌ Arquivo NÃO existe</span>\n";
}
?></pre>

        <h3>3. Arquivo google_maps_config.php</h3>
        <pre><?php
if (file_exists(__DIR__ . '/../includes/google_maps_config.php')) {
    $config = include __DIR__ . '/../includes/google_maps_config.php';
    echo "<span class='success'>✅ Arquivo existe</span>\n";
    echo "API Key: " . ($config['google_maps_api_key'] ?? '<span class="error">NÃO DEFINIDA</span>') . "\n";
    echo "Map ID: " . ($config['google_maps_map_id'] ?? '<span class="error">NÃO DEFINIDA</span>') . "\n";
    echo "Libraries: " . implode(', ', $config['maps_libraries'] ?? []) . "\n";
} else {
    echo "<span class='error'>❌ Arquivo NÃO existe</span>\n";
}
?></pre>

        <h3>4. JavaScript Config (google-maps-config.php)</h3>
        <pre>URL: /js/google-maps-config.php
Este arquivo gera o objeto window.googleMapsConfig para o JavaScript</pre>

        <h3>5. Validação</h3>
        <pre><?php
$apiKey = getenv('GOOGLE_MAPS_API_KEY') ?: '';
$mapId = getenv('GOOGLE_MAPS_MAP_ID') ?: '';

$errors = [];

if (empty($apiKey) || strpos($apiKey, 'YOUR_') !== false) {
    $errors[] = "<span class='error'>❌ API Key inválida ou não definida</span>";
} else {
    echo "<span class='success'>✅ API Key definida (" . strlen($apiKey) . " caracteres)</span>\n";
}

if (empty($mapId) || strpos($mapId, 'YOUR_') !== false) {
    $errors[] = "<span class='error'>❌ Map ID inválido ou não definido</span>";
} else {
    echo "<span class='success'>✅ Map ID definido (" . strlen($mapId) . " caracteres)</span>\n";
}

if (count($errors) > 0) {
    echo "\n<span class='warning'>🚨 PROBLEMAS ENCONTRADOS:</span>\n";
    foreach ($errors as $error) {
        echo "   $error\n";
    }
} else {
    echo "\n<span class='success'>🎉 Todas as credenciais estão configuradas corretamente!</span>\n";
}
?></pre>
    </div>
    
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
