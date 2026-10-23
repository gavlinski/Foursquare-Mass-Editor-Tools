<?php
/**
 * Teste Detalhado de Geocodificação
 * 
 * Testa a geocodificação Foursquare com fallback Google e expõe diagnóstico opcional.
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

echo "<!DOCTYPE html>\n";
echo "<html>\n<head>\n";
echo "<meta charset='utf-8'>\n";
echo "<link rel='stylesheet' href='../fonts/inter.css'>\n";
echo "<title>Debug Geocodificação</title>\n";
echo "<script>window.debugLog=window.debugLog||function(){};window.debugInfo=window.debugInfo||function(){};window.isLocalhost=function(){return true;};</script>\n";
echo "<script src='../js/google-maps-config.php?v=5.0.1'></script>\n";
echo "<script src='../js/google-maps.js'></script>\n";
echo "<script src='../js/fsq-autocomplete.js'></script>\n";
echo "<style>
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, Helvetica, Arial, sans-serif; padding: 20px; background: #f5f5f5; }
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

echo "<h1>🔍 Debug Detalhado - Geocodificação</h1>\n";

// Carrega configurações
$mapsConfig = include __DIR__ . '/../includes/google_maps_config.php';

// O teste normal replica o caminho da aplicação, usando Geocoding via JavaScript API.
$browserApiKey = $mapsConfig['google_maps_api_key'] ?? null;
$browserKeyConfigured = $browserApiKey && $browserApiKey !== 'YOUR_GOOGLE_MAPS_API_KEY' && $browserApiKey !== 'SUA_GOOGLE_MAPS_API_KEY_AQUI';
$oauthToken = $_SESSION['oauth_token'] ?? ($_COOKIE['oauth_token'] ?? '');
$canGeocode = $oauthToken !== '' || $browserKeyConfigured;
$apiKey = $mapsConfig['google_maps_geocoding_key'] ?? null;
$keyType = $apiKey ? 'Geocoding (Server-Side)' : 'não configurada';

echo "<div class='section'>\n";
echo "<h2>1. Configuração</h2>\n";
echo "<strong>Foursquare OAuth:</strong> " . ($oauthToken ? "<span class='status-ok'>sessão autenticada</span>" : "<span class='status-error'>sem sessão autenticada</span>") . "<br>\n";
echo "<strong>Google Maps (fallback):</strong> " . ($browserKeyConfigured ? "<span class='status-ok'>configurada</span>" : "<span class='status-error'>não configurada</span>") . "<br>\n";
echo "<strong>Chave server-side (teste opcional):</strong> " . $keyType . "<br>\n";
echo "<strong>Language:</strong> " . ($mapsConfig['geocoding']['language'] ?? 'pt-BR') . "<br>\n";
echo "<strong>Region:</strong> " . ($mapsConfig['geocoding']['region'] ?? 'BR') . "<br>\n";
if ($apiKey) {
    echo "<a href='?server_test=1'>Executar também o teste server-side (cURL)</a><br>\n";
}
echo "</div>\n";

if (!$canGeocode) {
    echo "<div class='warning' style='margin-top: 15px;'>\n";
    echo "⚠️ Autentique-se para usar a geocodificação do Foursquare ou configure <strong>GOOGLE_MAPS_API_KEY</strong> para o fallback Google.<br>\n";
    echo "Veja <a href='../docs/GEOCODING_SETUP.md' target='_blank'>docs/GEOCODING_SETUP.md</a>.\n";
    echo "</div>\n";
}

echo "<div class='section'>\n";
echo "<h2>2. Teste de geocodificação no navegador</h2>\n";
echo "<p>Este teste usa primeiro <code>geo/geocode</code> do Foursquare com a sessão autenticada e o Google Maps JavaScript Geocoder como fallback.</p>\n";
echo "<label for='geocodeAddress'>Cidade ou endereço:</label> ";
echo "<input id='geocodeAddress' type='text' value='Canoas, RS' style='width: min(420px, 70%); padding: 8px;'> ";
echo "<button id='geocodeButton' type='button' style='padding: 8px 14px;'>Geocodificar</button>\n";
echo "<p id='geocodeStatus' role='status' aria-live='polite'></p>\n";
echo "<strong>Resultado:</strong><pre id='geocodeResult'>Aguardando requisição.</pre>\n";
echo "</div>\n";
echo "<script>\n";
echo "document.getElementById('geocodeButton').addEventListener('click', function() {\n";
echo "  var address = document.getElementById('geocodeAddress').value.trim();\n";
echo "  var status = document.getElementById('geocodeStatus');\n";
echo "  var result = document.getElementById('geocodeResult');\n";
echo "  if (!address) { status.textContent = 'Informe uma cidade ou endereço.'; return; }\n";
echo "  this.disabled = true; status.textContent = 'Consultando geocodificação...'; result.textContent = '';\n";
echo "  fsqObterLl(address, " . json_encode($oauthToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ").then(function(ll) {\n";
echo "    status.textContent = 'Geocodificação concluída.';\n";
echo "    result.textContent = JSON.stringify({status: 'OK', address: address, ll: ll}, null, 2);\n";
echo "  }).catch(function(error) {\n";
echo "    status.textContent = 'A requisição falhou.';\n";
echo "    result.textContent = JSON.stringify({status: 'ERROR', address: address, error: error.message}, null, 2);\n";
echo "  }).finally(function() { document.getElementById('geocodeButton').disabled = false; });\n";
echo "});\n";
echo "</script>\n";

// O teste cURL é mantido apenas como diagnóstico separado da chave server-side.
if ($apiKey && isset($_GET['server_test']) && $_GET['server_test'] === '1') {
$endereco = "Canoas, RS";
$enderecoEncoded = str_replace(" ", "+", $endereco);

echo "<div class='section'>\n";
echo "<h2>3. Teste server-side opcional (cURL)</h2>\n";
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
            if (strpos($json->error_message, 'empty referer') !== false) {
                echo "<div class='warning'>\n";
                echo "<strong>Diagnóstico:</strong> esta chamada foi feita pelo servidor e não envia HTTP Referer. Inclua o IP público de saída mostrado pelo Google nas restrições de IP da chave server-side, e confirme que a chave não está restrita por HTTP referrers.<br>\n";
                echo "A busca pela interface principal geocodifica no navegador com a chave JavaScript autorizada para o domínio.\n";
                echo "</div>\n";
            }
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
                    echo "&nbsp;&nbsp;- Para esta chamada server-side, use restrição por IP e autorize o IP público de saída (não HTTP referrers)<br>\n";
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
}

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
echo "        Voltar para Debug\n";
echo "    </a>\n";
echo "</footer>\n";

echo "</body>\n</html>";
