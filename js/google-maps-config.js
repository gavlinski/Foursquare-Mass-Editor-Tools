// Configuração Google Maps - Versão Estática
// Arquivo gerado automaticamente para evitar dependência de PHP runtime

window.googleMapsConfig = {
    "apiKey": "SUA_GOOGLE_MAPS_API_KEY_AQUI",
    "mapId": "SEU_GOOGLE_MAPS_MAP_ID_AQUI",
    "defaultZoom": 15,
    "defaultCenter": {
        "lat": -23.5505,
        "lng": -46.6333
    },
    "libraries": [
        "marker",
        "places"
    ],
    "mapStyles": {
        "default": "roadmap",
        "satellite": "satellite",
        "hybrid": "hybrid",
        "terrain": "terrain"
    },
    "geocoding": {
        "enabled": true,
        "language": "pt-BR",
        "region": "BR"
    }
};

console.log('🔧 Configuração Google Maps carregada (estática):', window.googleMapsConfig);
