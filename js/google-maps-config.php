<?php
/**
 * Google Maps Configuration Bridge
 * 
 * Converte configurações PHP para JavaScript
 */

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Carrega configurações
$mapsConfig = include __DIR__ . '/../includes/google_maps_config.php';

// Gera configuração JavaScript
echo "// Configuração Google Maps gerada automaticamente\n";
echo "window.googleMapsConfig = " . json_encode([
    'apiKey' => $mapsConfig['google_maps_api_key'],
    'mapId' => $mapsConfig['google_maps_map_id'],
    'defaultZoom' => $mapsConfig['default_zoom'],
    'defaultCenter' => $mapsConfig['default_center'],
    'libraries' => $mapsConfig['maps_libraries'],
    'mapStyles' => $mapsConfig['map_styles'],
    'geocoding' => $mapsConfig['geocoding']
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . ";\n\n";

echo "console.log('🔧 Configuração Google Maps carregada:', window.googleMapsConfig);\n";
?>
