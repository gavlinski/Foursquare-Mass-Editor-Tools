<?php
/**
 * Teste Detalhado de Geocodificação
 * 
 * Mostra a resposta completa da API do Google Maps para diagnóstico
 */

echo "<!DOCTYPE html>\n";
echo "<html>\n<head>\n";
echo "<meta charset='utf-8'>\n";
echo "<title>Debug Geocodificação</title>\n";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .section { 
        margin: 20px 0; 
        padding: 20px; 
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    h1 { color: #667eea; }
    h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
    pre { 
        background: #f9f9f9; 
        padding: 15px; 
        border-left: 3px solid #667eea;
        overflow-x: auto;
        font-size: 12px;
    }
    .status-ok { color: #28a745; font-weight: bold; }
    .status-error { color: #dc3545; font-weight: bold; }
    .warning { 
        background: #fff3cd; 
        border: 1px solid #ffc107;
        padding: 15px;
        border-radius: 5px;
        margin: 10px 0;
    }
</style>\n";
echo "</head>\n<body>\n";

echo "<h1>🔍 Debug Detalhado - Geocodificação Google Maps</h1>\n";

// Carrega configurações
$mapsConfig = include __DIR__ . '/../includes/google_maps_config.php';
$apiKey = $mapsConfig['google_maps_api_key'] ?? 'SUA_GOOGLE_MAPS_API_KEY_AQUI';

echo "<div class='section'>\n";
echo "<h2>1. Configuração</h2>\n";
echo "<strong>API Key:</strong> " . substr($apiKey, 0, 15) . "..." . substr($apiKey, -10) . "<br>\n";
echo "<strong>Language:</strong> " . ($mapsConfig['geocoding']['language'] ?? 'pt-BR') . "<br>\n";
echo "<strong>Region:</strong> " . ($mapsConfig['geocoding']['region'] ?? 'BR') . "<br>\n";
echo "</div>\n";

// Teste direto com cURL
$endereco = "Canoas, RS";
$enderecoEncoded = str_replace(" ", "+", $endereco);

echo "<div class='section'>\n";
echo "<h2>2. Teste Direto com a API</h2>\n";
echo "<strong>Endereço:</strong> $endereco<br>\n";

$geoapi = "https://maps.googleapis.com/maps/api/geocode/json";
$params = [
    "address" => $enderecoEncoded,
    "key" => $apiKey,
    "language" => "pt-BR",
    "region" => "BR"
];

$url = $geoapi . '?' . http_build_query($params);
$urlSafe = preg_replace('/key=[^&]+/', 'key=***HIDDEN***', $url);

echo "<strong>URL:</strong><br>\n";
echo "<pre>$urlSafe</pre>\n";

// Faz requisição
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<strong>HTTP Status:</strong> $httpCode<br>\n";

if ($curlError) {
    echo "<div class='warning'>⚠️ <strong>Erro cURL:</strong> $curlError</div>\n";
}

if ($response) {
    $json = json_decode($response);
    
    echo "<strong>Status da API:</strong> ";
    if ($json && isset($json->status)) {
        if ($json->status === "OK") {
            echo "<span class='status-ok'>✅ $json->status</span><br>\n";
        } else {
            echo "<span class='status-error'>❌ $json->status</span><br>\n";
        }
        
        // Mostra mensagem de erro se houver
        if (isset($json->error_message)) {
            echo "<div class='warning'>\n";
            echo "<strong>Mensagem de Erro:</strong><br>\n";
            echo htmlspecialchars($json->error_message);
            echo "</div>\n";
        }
        
        // Explica os status codes
        if ($json->status !== "OK") {
            echo "<div class='warning'>\n";
            echo "<strong>Significado:</strong><br>\n";
            switch ($json->status) {
                case "ZERO_RESULTS":
                    echo "• Nenhum resultado encontrado para este endereço.<br>\n";
                    break;
                case "OVER_QUERY_LIMIT":
                    echo "• Você excedeu a cota de requisições da API.<br>\n";
                    echo "• Verifique em: <a href='https://console.cloud.google.com/google/maps-apis/quotas' target='_blank'>Google Cloud Console - Quotas</a><br>\n";
                    break;
                case "REQUEST_DENIED":
                    echo "• Requisição negada. Verifique:<br>\n";
                    echo "&nbsp;&nbsp;- Se a API de Geocoding está habilitada no projeto<br>\n";
                    echo "&nbsp;&nbsp;- Se a API Key tem permissão para usar Geocoding API<br>\n";
                    echo "&nbsp;&nbsp;- Restrições de domínio/IP/aplicativo na API Key<br>\n";
                    echo "• Configure em: <a href='https://console.cloud.google.com/google/maps-apis/credentials' target='_blank'>Google Cloud Console - Credentials</a><br>\n";
                    break;
                case "INVALID_REQUEST":
                    echo "• Requisição inválida (falta parâmetro ou formato incorreto).<br>\n";
                    break;
                case "UNKNOWN_ERROR":
                    echo "• Erro desconhecido no servidor do Google. Tente novamente.<br>\n";
                    break;
            }
            echo "</div>\n";
        }
    } else {
        echo "<span class='status-error'>❌ Resposta inválida</span><br>\n";
    }
    
    echo "<strong>Resposta Completa:</strong><br>\n";
    echo "<pre>" . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>\n";
    
    // Se tiver resultados, mostra as coordenadas
    if ($json && $json->status === "OK" && isset($json->results[0])) {
        echo "<div class='section'>\n";
        echo "<h2>3. Coordenadas Obtidas</h2>\n";
        $result = $json->results[0];
        echo "<strong>Endereço Formatado:</strong> " . htmlspecialchars($result->formatted_address) . "<br>\n";
        echo "<strong>Latitude:</strong> " . $result->geometry->location->lat . "<br>\n";
        echo "<strong>Longitude:</strong> " . $result->geometry->location->lng . "<br>\n";
        
        if (isset($result->geometry->viewport)) {
            echo "<br><strong>Viewport (quadrantes):</strong><br>\n";
            echo "• Southwest: " . $result->geometry->viewport->southwest->lat . "," . $result->geometry->viewport->southwest->lng . "<br>\n";
            echo "• Northeast: " . $result->geometry->viewport->northeast->lat . "," . $result->geometry->viewport->northeast->lng . "<br>\n";
        }
        echo "</div>\n";
    }
    
} else {
    echo "<span class='status-error'>❌ Sem resposta</span><br>\n";
}

echo "</div>\n";

// Links úteis
echo "<div class='section'>\n";
echo "<h2>📚 Links Úteis</h2>\n";
echo "<ul>\n";
echo "<li><a href='https://console.cloud.google.com/google/maps-apis/credentials' target='_blank'>Google Cloud Console - API Credentials</a></li>\n";
echo "<li><a href='https://console.cloud.google.com/google/maps-apis/apis/geocoding-backend.googleapis.com' target='_blank'>Geocoding API - Status</a></li>\n";
echo "<li><a href='https://console.cloud.google.com/google/maps-apis/quotas' target='_blank'>API Quotas & Usage</a></li>\n";
echo "<li><a href='https://developers.google.com/maps/documentation/geocoding/requests-geocoding' target='_blank'>Documentação - Geocoding API</a></li>\n";
echo "</ul>\n";
echo "</div>\n";

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
echo "        ← Voltar para Debug\n";
echo "    </a>\n";
echo "</footer>\n";

echo "</body>\n</html>";
