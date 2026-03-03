<?php
/**
 * Debug: Teste de configuração do Google Maps
 * 
 * Este arquivo verifica se as credenciais do Google Maps estão sendo carregadas corretamente
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔧 Debug: Google Maps Configuration</h2>";

echo "<h3>1. Variáveis de Ambiente (.env)</h3>";
echo "<pre>";
echo "GOOGLE_MAPS_API_KEY: " . (getenv('GOOGLE_MAPS_API_KEY') ?: 'NÃO DEFINIDA') . "\n";
echo "GOOGLE_MAPS_MAP_ID: " . (getenv('GOOGLE_MAPS_MAP_ID') ?: 'NÃO DEFINIDA') . "\n";
echo "</pre>";

echo "<h3>2. Arquivo google_maps_credentials.php</h3>";
echo "<pre>";
if (file_exists(__DIR__ . '/../includes/google_maps_credentials.php')) {
    $credentials = include __DIR__ . '/../includes/google_maps_credentials.php';
    echo "✅ Arquivo existe\n";
    echo "API Key: " . ($credentials['google_maps_api_key'] ?? 'NÃO DEFINIDA') . "\n";
    echo "Map ID: " . ($credentials['google_maps_map_id'] ?? 'NÃO DEFINIDA') . "\n";
    
    // Verifica se são valores placeholder
    if (strpos($credentials['google_maps_api_key'], 'YOUR_') !== false || 
        strpos($credentials['google_maps_api_key'], 'SUA_') !== false) {
        echo "\n⚠️ PROBLEMA: API Key está com valor placeholder!\n";
        echo "   O arquivo não está lendo do .env corretamente.\n";
    }
} else {
    echo "❌ Arquivo NÃO existe\n";
}
echo "</pre>";

echo "<h3>3. Arquivo google_maps_config.php</h3>";
echo "<pre>";
if (file_exists(__DIR__ . '/../includes/google_maps_config.php')) {
    $config = include __DIR__ . '/../includes/google_maps_config.php';
    echo "✅ Arquivo existe\n";
    echo "API Key: " . ($config['google_maps_api_key'] ?? 'NÃO DEFINIDA') . "\n";
    echo "Map ID: " . ($config['google_maps_map_id'] ?? 'NÃO DEFINIDA') . "\n";
    echo "Libraries: " . implode(', ', $config['maps_libraries'] ?? []) . "\n";
} else {
    echo "❌ Arquivo NÃO existe\n";
}
echo "</pre>";

echo "<h3>4. JavaScript Config (google-maps-config.php)</h3>";
echo "<pre>";
echo "URL: /js/google-maps-config.php\n";
echo "Este arquivo gera o objeto window.googleMapsConfig para o JavaScript\n";
echo "</pre>";

echo "<h3>5. Validação</h3>";
echo "<pre>";
$apiKey = getenv('GOOGLE_MAPS_API_KEY') ?: '';
$mapId = getenv('GOOGLE_MAPS_MAP_ID') ?: '';

$errors = [];

if (empty($apiKey) || strpos($apiKey, 'YOUR_') !== false) {
    $errors[] = "❌ API Key inválida ou não definida";
} else {
    echo "✅ API Key definida (" . strlen($apiKey) . " caracteres)\n";
}

if (empty($mapId) || strpos($mapId, 'YOUR_') !== false) {
    $errors[] = "❌ Map ID inválido ou não definido";
} else {
    echo "✅ Map ID definido (" . strlen($mapId) . " caracteres)\n";
}

if (count($errors) > 0) {
    echo "\n🚨 PROBLEMAS ENCONTRADOS:\n";
    foreach ($errors as $error) {
        echo "   $error\n";
    }
} else {
    echo "\n🎉 Todas as credenciais estão configuradas corretamente!\n";
}
echo "</pre>";

echo "<hr>";
echo "<p><a href='/'>← Voltar</a> | <a href='/debug/test_session_debug.html'>Debug Session</a></p>";
?>
