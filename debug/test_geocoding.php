<?php
/**
 * Teste de Geocodificação
 * 
 * Testa a conversão de endereços em coordenadas geográficas
 */

require_once(__DIR__ . '/../FoursquareAPI.Class.php');

// Carrega credenciais
include __DIR__ . '/../includes/app_credentials.php';

// Cria instância da API
$foursquare = new FoursquareAPI($client_key, $client_secret);

// Endereços para testar
$enderecos = [
    'Canoas, RS',
    'Porto Alegre, RS, Brasil',
    'Av. Paulista, São Paulo',
    'Rio de Janeiro, RJ'
];

echo "<!DOCTYPE html>\n";
echo "<html>\n<head>\n";
echo "<meta charset='utf-8'>\n";
echo "<title>Teste de Geocodificação</title>\n";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .teste { 
        margin: 20px 0; 
        padding: 15px; 
        border: 1px solid #ddd; 
        border-radius: 5px;
        background: #f9f9f9;
    }
    .endereco { font-weight: bold; color: #333; }
    .sucesso { color: #28a745; }
    .erro { color: #dc3545; }
    .detalhes { 
        margin-top: 10px; 
        padding: 10px;
        background: #fff;
        border-left: 3px solid #007bff;
        font-family: monospace;
        font-size: 12px;
    }
    h1 { color: #667eea; }
</style>\n";
echo "</head>\n<body>\n";

echo "<h1>🗺️ Teste de Geocodificação</h1>\n";

// Verifica se há API key configurada
$mapsConfig = include __DIR__ . '/../includes/google_maps_config.php';
$apiKey = $mapsConfig['google_maps_geocoding_key'] ?? $mapsConfig['google_maps_api_key'] ?? 'SUA_GOOGLE_MAPS_API_KEY_AQUI';
$keyType = isset($mapsConfig['google_maps_geocoding_key']) ? 'Geocoding (Server-Side)' : 'JavaScript (Fallback)';

echo "<div class='teste'>\n";
echo "<strong>Tipo de Key:</strong> " . $keyType . "<br>\n";
echo "<strong>API Key configurada:</strong> ";
if ($apiKey === 'YOUR_GOOGLE_MAPS_API_KEY' || $apiKey === 'SUA_GOOGLE_MAPS_API_KEY_AQUI') {
    echo "<span class='erro'>❌ NÃO (usando placeholder)</span><br>\n";
    echo "<small>Configure GOOGLE_MAPS_GEOCODING_KEY no arquivo .env ou em includes/google_maps_credentials.php</small>\n";
} else {
    echo "<span class='sucesso'>✅ SIM</span> (";
    echo substr($apiKey, 0, 10) . "..." . substr($apiKey, -5);
    echo ")<br>\n";
    
    // Aviso se estiver usando chave JavaScript como fallback
    if (!isset($mapsConfig['google_maps_geocoding_key'])) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 5px; margin-top: 10px;'>\n";
        echo "⚠️ <strong>Aviso:</strong> Usando chave JavaScript como fallback.<br>\n";
        echo "<small>Esta chave tem restrições de HTTP referrers e não funcionará para geocodificação server-side.</small><br>\n";
        echo "<small>Configure <code>GOOGLE_MAPS_GEOCODING_KEY</code> no .env com uma chave de restrição de IP.</small>\n";
        echo "</div>\n";
    }
}
echo "</div>\n";

foreach ($enderecos as $endereco) {
    echo "<div class='teste'>\n";
    echo "<div class='endereco'>📍 Endereço: $endereco</div>\n";
    
    try {
        $coordinates = $foursquare->GeoLocate($endereco);
        
        if ($coordinates === null) {
            echo "<div class='erro'>❌ Falha na geocodificação</div>\n";
            echo "<div class='detalhes'>Possíveis causas:\n";
            echo "- API Key inválida ou não configurada\n";
            echo "- Restrições de domínio/IP na Google Cloud Console\n";
            echo "- Cota da API excedida\n";
            echo "- Endereço não encontrado pelo Google Maps\n";
            echo "</div>\n";
        } else {
            echo "<div class='sucesso'>✅ Sucesso!</div>\n";
            echo "<div class='detalhes'>\n";
            echo "Latitude: " . $coordinates['latitude'] . "\n";
            echo "Longitude: " . $coordinates['longitude'] . "\n";
            echo "Southwest: " . $coordinates['southwest'] . "\n";
            echo "Northeast: " . $coordinates['northeast'] . "\n";
            echo "Southeast: " . $coordinates['southeast'] . "\n";
            echo "Northwest: " . $coordinates['northwest'] . "\n";
            echo "</div>\n";
        }
    } catch (Exception $e) {
        echo "<div class='erro'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>\n";
    }
    
    echo "</div>\n";
}

echo "<footer style='text-align: center; padding: 30px 20px; margin-top: 40px; border-top: 2px solid #e0e0e0; background: white;'>\n";
echo "    <a href='index.php' \n";
echo "       style='display: inline-flex; align-items: center; gap: 10px; padding: 12px 24px; \n";
echo "              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); \n";
echo "              color: white; text-decoration: none; border-radius: 8px; \n";
echo "              font-weight: 600; transition: transform 0.2s, box-shadow 0.2s; \n";
echo "              box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>\n";
echo "        <svg width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>\n";
echo "            <path d='M19 12H5M12 19l-7-7 7-7'/>\n";
echo "        </svg>\n";
echo "        Voltar para Debug\n";
echo "    </a>\n";
echo "</footer>\n";

echo "</body>\n</html>";
