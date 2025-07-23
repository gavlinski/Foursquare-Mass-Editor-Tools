<?php
/**
 * Configuração da Google Maps API
 * 
 * Este arquivo contém as configurações não-sensíveis para integração com a Google Maps API
 * As credenciais sensíveis estão em google_maps_credentials.php
 */

// Carrega credenciais sensíveis
$credentials = include __DIR__ . '/google_maps_credentials.php';

return array_merge($credentials, [
    
    // Versão da API a ser utilizada (v3 é a mais recente)
    'maps_api_version' => 'v3',
    
    // Bibliotecas adicionais da Google Maps API
    'maps_libraries' => [
        'marker', // Para AdvancedMarkerElement (nova API de marcadores)
        'places'  // Para autocomplete de endereços (opcional)
    ],
    
    // Configurações do mapa
    'default_zoom' => 15,
    'default_center' => [
        'lat' => -23.5505, // São Paulo como padrão
        'lng' => -46.6333
    ],

    // Configurações de estilo
    'map_styles' => [
        'default' => 'roadmap',
        'satellite' => 'satellite',
        'hybrid' => 'hybrid',
        'terrain' => 'terrain'
    ],
    
    // Configurações de segurança
    'allowed_domains' => [
        'localhost',
        '127.0.0.1',
        // Adicione seus domínios de produção aqui
    ],
    
    // Configurações de geocoding
    'geocoding' => [
        'enabled' => true,
        'language' => 'pt-BR',
        'region' => 'BR'
    ]
]);
