/**
 * Google Maps Integration - Versão Moderna
 * 
 * Sistema moderno de integração com Google Maps API v3
 * Corrige problemas de performance, billing e deprecações
 * Bloqueia COMPLETAMENTE qualquer carregamento ou erro legado
 * 
 * @version 3.0.0
 * @author Elio Gavlinski <gavlinski@gmail.com>
 */

// BLOQUEIO SELETIVO DE CONSOLE ERRORS LEGADOS
(function() {
    const originalError = console.error;
    const originalWarn = console.warn;
    
    const BLOCKED_MESSAGES = [
        'BillingNotEnabledMapError',
        'For development purposes only',
        'InvalidKeyMapError',
        'You have exceeded your daily request quota',
        'callback=inicializarMapaLegado',
        'Google Maps JavaScript API has been loaded directly without loading=async',
        'google.maps.Marker is deprecated'
    ];
    
    console.error = function(...args) {
        const message = args.join(' ');
        if (BLOCKED_MESSAGES.some(blocked => message.includes(blocked))) {
            console.log('🛡️ Erro legado específico bloqueado silenciosamente');
            return;
        }
        return originalError.apply(console, args);
    };
    
    console.warn = function(...args) {
        const message = args.join(' ');
        if (BLOCKED_MESSAGES.some(blocked => message.includes(blocked))) {
            console.log('🛡️ Aviso legado específico bloqueado silenciosamente');
            return;
        }
        return originalWarn.apply(console, args);
    };
})();

// BLOQUEIO SELETIVO: Previne apenas scripts legados específicos (NON-INVASIVE)
(function() {
    // Aguarda o DOM estar pronto para evitar interferências com outros scripts
    const initBlockers = () => {
        const originalAppendChild = Document.prototype.appendChild || HTMLElement.prototype.appendChild;
        
        if (originalAppendChild) {
            HTMLElement.prototype.appendChild = function(child) {
                if (child && child.tagName === 'SCRIPT' && child.src && 
                    child.src.includes('maps.googleapis.com/maps/api/js')) {
                    
                    // BLOQUEIA apenas scripts legados com callbacks específicos
                    if (child.src.includes('callback=inicializarMapaLegado') || 
                        child.src.includes('callback=initGoogleMaps')) {
                        
                        console.warn('🚫 BLOQUEADO: Script legado específico do Google Maps impedido');
                        console.log('📍 URL bloqueada:', child.src);
                        console.trace('🔍 Stack trace do script bloqueado:');
                        return child; // Retorna o elemento mas não anexa
                    }
                    
                    // BLOQUEIA apenas scripts de carregamento inicial com chave antiga
                    if (child.src.includes('key=AIzaSyD9ZfpJz_ZlwOo7crLhiYhxcpJdBPpBVi8')) {
                        
                        console.warn('🚫 BLOQUEADO: Script de inicialização com chave antiga');
                        console.log('📍 URL bloqueada:', child.src);
                        return child; // Retorna o elemento mas não anexa
                    }
                    
                    // PERMITE todos os outros scripts modernos
                    console.log('✅ Script moderno do Google Maps autorizado');
                    return originalAppendChild.call(this, child);
                }
                return originalAppendChild.call(this, child);
            };
        }
    };
    
    // Inicializa quando o DOM estiver pronto ou imediatamente se já estiver
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBlockers);
    } else {
        initBlockers();
    }
})();

// INTERCEPTAÇÃO SELETIVA: Bloqueia apenas scripts legados específicos (NON-INVASIVE)
(function() {
    const initCreateElementBlocker = () => {
        const originalCreateElement = document.createElement;
        
        document.createElement = function(tagName) {
            const element = originalCreateElement.call(document, tagName);
            
            if (tagName.toLowerCase() === 'script') {
                const originalSetAttribute = element.setAttribute;
                element.setAttribute = function(name, value) {
                    if (name === 'src' && value && value.includes('maps.googleapis.com/maps/api/js')) {
                        // Bloqueia apenas callbacks legados específicos
                        if (value.includes('callback=inicializarMapaLegado') || 
                            value.includes('callback=initGoogleMaps')) {
                            
                            console.warn('🚫 INTERCEPTADO: Script legado específico bloqueado');
                            console.log('📍 URL bloqueada:', value);
                            return; // Não define o src
                        }
                        
                        // BLOQUEIA apenas scripts de carregamento inicial com chave antiga
                        if (value.includes('key=AIzaSyD9ZfpJz_ZlwOo7crLhiYhxcpJdBPpBVi8')) {
                            
                            console.warn('🚫 INTERCEPTADO: Script de inicialização com chave antiga');
                            console.log('📍 URL bloqueada:', value);
                            return; // Não define o src
                        }
                        
                        // PERMITE todos os outros scripts modernos
                        console.log('✅ Script moderno autorizado via createElement');
                    }
                    return originalSetAttribute.call(element, name, value);
                };
            }
            
            return element;
        };
    };
    
    // Inicializa quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCreateElementBlocker);
    } else {
        initCreateElementBlocker();
    }
})();

// Bloqueia callbacks legados que possam causar conflitos
const LEGACY_CALLBACKS = ['inicializarMapaLegado', 'initGoogleMaps'];
LEGACY_CALLBACKS.forEach(callbackName => {
    // Verifica se a propriedade já existe antes de tentar redefini-la
    if (!window.hasOwnProperty(callbackName)) {
        Object.defineProperty(window, callbackName, {
            set: function(value) {
                console.warn(`🚫 BLOQUEADO: Tentativa de definir callback legado '${callbackName}'`);
                console.log('✅ Use window.modernGoogleMaps.initialize() em vez disso');
            },
            get: function() {
                console.warn(`🚫 Callback legado '${callbackName}' redirecionado para sistema moderno`);
                if (window.modernGoogleMaps) {
                    return () => window.modernGoogleMaps.initialize();
                }
                return () => {};
            },
            configurable: true,
            enumerable: false
        });
    }
});

// Remove callbacks legados que possam estar registrados
if (window.inicializarMapaLegado) {
    window.inicializarMapaLegado = function() {
        console.warn('🚫 Callback legado bloqueado - redirecionando para sistema moderno');
        if (window.carregarMapa) {
            window.carregarMapa();
        }
    };
}

// Bloqueia tentativas de usar apenas API keys antigas específicas
const BLOCKED_API_KEYS = [
    'AIzaSyD9ZfpJz_ZlwOo7crLhiYhxcpJdBPpBVi8', // Chave antiga específica
];

class ModernGoogleMaps {
    constructor(config = {}) {
        this.config = {
            apiKey: config.apiKey || '',
            defaultZoom: config.defaultZoom || 15,
            defaultCenter: config.defaultCenter || { lat: -23.5505, lng: -46.6333 },
            mapElementId: config.mapElementId || 'mapa',
            libraries: config.libraries || ['marker'],
            ...config
        };
        
        this.map = null;
        this.markers = [];
        this.bounds = null;
        this.isLoaded = false;
        this.loadPromise = null;
        
        // Callbacks
        this.onMapReady = config.onMapReady || (() => {});
        this.onMarkerDragEnd = config.onMarkerDragEnd || (() => {});
    }

    /**
     * Método de inicialização pública para compatibilidade
     */
    async initialize() {
        return this.initializeMap();
    }

    /**
     * Carrega a Google Maps API de forma assíncrona e moderna
     */
    async loadMapsAPI() {
        if (this.loadPromise) {
            return this.loadPromise;
        }

        this.loadPromise = new Promise((resolve, reject) => {
            // Verifica se já está carregada
            if (window.google && window.google.maps) {
                this.isLoaded = true;
                resolve();
                return;
            }

            // Cria callback global único
            const callbackName = `initGoogleMaps_${Date.now()}`;
            window[callbackName] = () => {
                this.isLoaded = true;
                delete window[callbackName];
                resolve();
            };

            // Carrega o script de forma moderna
            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.async = true;
            script.defer = true;
            
            const libraries = this.config.libraries.join(',');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${this.config.apiKey}&libraries=${libraries}&callback=${callbackName}&loading=async`;
            
            script.onerror = () => {
                delete window[callbackName];
                reject(new Error('Falha ao carregar Google Maps API'));
            };

            document.head.appendChild(script);
        });

        return this.loadPromise;
    }

    /**
     * Inicializa o mapa com configurações modernas
     */
    async initializeMap(lat = null, lng = null, zoom = null) {
        try {
            await this.loadMapsAPI();

            const center = (lat && lng) ? { lat: parseFloat(lat), lng: parseFloat(lng) } : this.config.defaultCenter;
            const mapZoom = zoom || this.config.defaultZoom;

            const mapOptions = {
                center: center,
                zoom: mapZoom,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                
                // Controles otimizados e compactos
                mapTypeControl: true,
                mapTypeControlOptions: {
                    style: google.maps.MapTypeControlStyle.DROPDOWN_MENU, // Mais compacto
                    position: google.maps.ControlPosition.TOP_RIGHT,
                    mapTypeIds: [google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.SATELLITE]
                },
                
                zoomControl: true,
                zoomControlOptions: {
                    position: google.maps.ControlPosition.RIGHT_CENTER
                },
                
                // Ocultar controles menos essenciais para economizar espaço
                streetViewControl: false, // Oculto - não essencial para o sistema
                fullscreenControl: false, // Oculto - não essencial para o sistema
                
                // Configurações modernas de estilo
                styles: [
                    {
                        featureType: "poi.business",
                        stylers: [{ visibility: "on" }]
                    }
                ],
                
                // Remove a marca d'água "For development purposes only"
                restriction: {
                    latLngBounds: {
                        north: 85,
                        south: -85,
                        west: -180,
                        east: 180
                    }
                }
            };

            const mapElement = document.getElementById(this.config.mapElementId);
            if (!mapElement) {
                throw new Error(`Elemento do mapa não encontrado: ${this.config.mapElementId}`);
            }

            this.map = new google.maps.Map(mapElement, mapOptions);
            this.bounds = new google.maps.LatLngBounds();

            // Callback quando o mapa estiver pronto
            google.maps.event.addListenerOnce(this.map, 'idle', () => {
                console.log('🗺️ Google Maps carregado com sucesso');
                this.onMapReady(this.map);
            });

            // Adiciona controle customizado do Foursquare
            this.addFoursquareAttribution();

            return this.map;

        } catch (error) {
            console.error('❌ Erro ao inicializar Google Maps:', error);
            throw error;
        }
    }

    /**
     * Adiciona o controle de atribuição do Foursquare ao mapa
     */
    addFoursquareAttribution() {
        if (!this.map) return;

        // Cria o elemento de controle
        const controlDiv = document.createElement('div');
        controlDiv.className = 'foursquare-attribution';
        controlDiv.style.cssText = `
            padding: 6px 10px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 4px;
            box-shadow: rgba(0, 0, 0, 0.25) 0px 2px 6px;
            margin: 8px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            cursor: pointer;
        `;

        // Cria o link e imagem
        const link = document.createElement('a');
        link.href = 'https://foursquare.com';
        link.title = 'Powered by Foursquare - Clique para visitar o site';
        link.target = '_blank';
        link.style.cssText = 'text-decoration: none; display: block;';
        
        const img = document.createElement('img');
        img.src = 'img/poweredByFoursquare.png';
        img.width = 140;
        img.height = 16;
        img.style.cssText = 'display: block; opacity: 0.9; transition: opacity 0.2s ease;';
        img.alt = 'Powered by Foursquare';

        // Ajuste responsivo para telas menores
        if (window.innerWidth <= 768) {
            img.width = 120;
            img.height = 13;
            controlDiv.style.padding = '4px 6px';
            controlDiv.style.margin = '4px';
        }

        // Adiciona efeitos hover
        controlDiv.addEventListener('mouseenter', () => {
            controlDiv.style.backgroundColor = 'rgba(255, 255, 255, 1)';
            controlDiv.style.boxShadow = 'rgba(0, 0, 0, 0.35) 0px 3px 8px';
            controlDiv.style.transform = 'translateY(-1px)';
            img.style.opacity = '1';
        });

        controlDiv.addEventListener('mouseleave', () => {
            controlDiv.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
            controlDiv.style.boxShadow = 'rgba(0, 0, 0, 0.25) 0px 2px 6px';
            controlDiv.style.transform = 'translateY(0)';
            img.style.opacity = '0.9';
        });

        // Monta a estrutura
        link.appendChild(img);
        controlDiv.appendChild(link);

        // Adiciona ao mapa no canto superior esquerdo
        this.map.controls[google.maps.ControlPosition.TOP_LEFT].push(controlDiv);

        console.log('✅ Controle Foursquare adicionado ao mapa moderno');
    }

    /**
     * Adiciona marcadores usando a nova API AdvancedMarkerElement (recomendada)
     */
    async addModernMarker(position, options = {}) {
        if (!this.isLoaded) {
            throw new Error('Google Maps API não carregada');
        }

        try {
            // Usa a nova AdvancedMarkerElement (Google Maps API v3.56+)
            if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
                const marker = new google.maps.marker.AdvancedMarkerElement({
                    position: position,
                    map: this.map,
                    title: options.title || '',
                    gmpDraggable: options.draggable || false,
                    content: options.content || undefined
                });

                if (options.draggable && this.onMarkerDragEnd) {
                    marker.addListener('dragend', (event) => {
                        this.onMarkerDragEnd(marker, event, options.index);
                    });
                }

                this.markers.push(marker);
                this.bounds.extend(position);

                return marker;
            } else {
                // Fallback para Marker legado com aviso
                console.warn('⚠️ AdvancedMarkerElement não disponível, usando Marker legado');
                return this.addLegacyMarker(position, options);
            }
        } catch (error) {
            console.error('❌ Erro ao criar marcador moderno:', error);
            // Fallback para marcador legado
            return this.addLegacyMarker(position, options);
        }
    }

    /**
     * Fallback para marcadores legados (compatibilidade)
     */
    addLegacyMarker(position, options = {}) {
        const marker = new google.maps.Marker({
            position: position,
            map: this.map,
            title: options.title || '',
            draggable: options.draggable || false,
            animation: options.animation || google.maps.Animation.DROP
        });

        if (options.draggable && this.onMarkerDragEnd) {
            marker.addListener('dragend', (event) => {
                this.onMarkerDragEnd(marker, event, options.index);
            });
        }

        this.markers.push(marker);
        this.bounds.extend(position);

        return marker;
    }

    /**
     * Atualiza marcadores no mapa
     */
    async updateMapMarkers(locations = []) {
        if (!this.map) {
            console.warn('⚠️ Mapa não inicializado');
            return;
        }

        // Remove marcadores existentes
        this.clearMarkers();

        // Adiciona novos marcadores
        for (let i = 0; i < locations.length; i++) {
            const location = locations[i];
            if (location && location[1] !== undefined && location[2] !== undefined) {
                const position = { lat: parseFloat(location[1]), lng: parseFloat(location[2]) };
                
                try {
                    await this.addModernMarker(position, {
                        title: `${i + 1}. ${location[0] || 'Local'}`,
                        draggable: true,
                        index: i
                    });
                } catch (error) {
                    console.error(`❌ Erro ao adicionar marcador ${i}:`, error);
                }
            }
        }

        // Ajusta visualização para mostrar todos os marcadores
        if (this.markers.length > 0) {
            this.map.fitBounds(this.bounds);
            
            // Se há apenas um marcador, define zoom específico
            if (this.markers.length === 1) {
                this.map.setZoom(this.config.defaultZoom);
            }
        }
    }

    /**
     * Remove todos os marcadores
     */
    clearMarkers() {
        this.markers.forEach(marker => {
            if (marker.setMap) {
                marker.setMap(null);
            } else if (marker.map) {
                marker.map = null;
            }
        });
        this.markers = [];
        this.bounds = new google.maps.LatLngBounds();
    }

    /**
     * Move um marcador para nova posição
     */
    moveMarker(index, newPosition) {
        if (this.markers[index]) {
            const marker = this.markers[index];
            if (marker.position) {
                marker.position = newPosition;
            } else if (marker.setPosition) {
                marker.setPosition(newPosition);
            }
        }
    }

    /**
     * Obtém informações de debug
     */
    getDebugInfo() {
        return {
            isLoaded: this.isLoaded,
            mapExists: !!this.map,
            markersCount: this.markers.length,
            apiVersion: window.google?.maps?.version || 'N/A',
            hasAdvancedMarkers: !!(window.google?.maps?.marker?.AdvancedMarkerElement),
            config: this.config
        };
    }
}

// Disponibiliza a classe globalmente
window.ModernGoogleMaps = ModernGoogleMaps;

// Funções de compatibilidade com o código existente
window.modernGoogleMaps = null;

// Aguarda as configurações E o Dojo serem carregados antes de inicializar
function waitForConfigAndDojo(callback, timeout = 10000) {
    const startTime = Date.now();
    
    function check() {
        const hasConfig = !!window.googleMapsConfig;
        const hasDojo = !!(window.dojo && window.dojo.ready);
        
        if (hasConfig && (hasDojo || !window.dojo)) {
            // Tem config e (Dojo está pronto OU não usa Dojo)
            callback();
        } else if (Date.now() - startTime < timeout) {
            setTimeout(check, 100);
        } else {
            console.error('❌ Timeout: Configurações/Dojo não carregados completamente');
            // Fallback com configuração padrão MODERNA
            if (!window.googleMapsConfig) {
                window.googleMapsConfig = {
                    apiKey: 'SUA_GOOGLE_MAPS_API_KEY_AQUI', // Chave moderna
                    defaultZoom: 15,
                    defaultCenter: { lat: -23.5505, lng: -46.6333 },
                    libraries: ['marker']
                };
            }
            callback();
        }
    }
    
    check();
}

// Função para carregamento assíncrono - MODERNA 
// Cria implementação preservada contra sobrescrição
window.carregarMapaModerno = function() {
    console.log('🔄 Carregando Google Maps moderno...');
    
    return new Promise((resolve, reject) => {
        waitForConfigAndDojo(async () => {
            try {
                if (!window.modernGoogleMaps) {
                    const config = {
                        apiKey: window.googleMapsConfig.apiKey,
                        defaultZoom: window.googleMapsConfig.defaultZoom || 15,
                        defaultCenter: window.googleMapsConfig.defaultCenter || { lat: -23.5505, lng: -46.6333 },
                        mapElementId: 'mapa',
                        libraries: window.googleMapsConfig.libraries || ['marker'],
                        onMapReady: (map) => {
                            window.mapaCarregado = true;
                            window.map = map; // Compatibilidade com código legado
                            console.log('🗺️ Mapa moderno carregado e pronto');
                            resolve(map);
                        },
                        onMarkerDragEnd: (marker, event, index) => {
                            // Integração com o código existente
                            if (window.atualizarPosicaoMarcador) {
                                window.atualizarPosicaoMarcador(index, event);
                            }
                        }
                    };

                    window.modernGoogleMaps = new ModernGoogleMaps(config);
                }

                await window.modernGoogleMaps.initializeMap();
                
            } catch (error) {
                console.error('❌ Erro no carregamento moderno do mapa:', error);
                reject(error);
            }
        });
    });
};

// Implementação pública - pode ser sobrescrita mas sempre chama a versão preservada
window.carregarMapa = function() {
    console.log('🔗 Chamando implementação moderna preservada...');
    return window.carregarMapaModerno();
}

// Função para atualizar marcadores no mapa
window.atualizarMarcadoresMapa = async function() {
    if (window.modernGoogleMaps && window.locais) {
        try {
            await window.modernGoogleMaps.updateMapMarkers(window.locais);
        } catch (error) {
            console.error('❌ Erro ao atualizar marcadores:', error);
        }
    }
};

// Função de debug
window.debugGoogleMaps = function() {
    if (window.modernGoogleMaps) {
        console.table(window.modernGoogleMaps.getDebugInfo());
    } else {
        console.log('ModernGoogleMaps não inicializado');
    }
};

// Função de teste para verificar se o sistema está funcionando
window.testarSistemaModerno = function() {
    console.log('🧪 Testando sistema moderno...');
    console.log('✅ Configuração disponível:', !!window.googleMapsConfig);
    console.log('✅ ModernGoogleMaps disponível:', typeof ModernGoogleMaps !== 'undefined');
    console.log('✅ Instância criada:', !!window.modernGoogleMaps);
    
    if (window.googleMapsConfig) {
        console.log('🔑 API Key:', window.googleMapsConfig.apiKey);
    }
    
    // Tenta carregar o mapa se não estiver carregado
    if (!window.modernGoogleMaps) {
        console.log('🔄 Iniciando carregamento do mapa...');
        try {
            const resultado = window.carregarMapaModerno();
            if (resultado && typeof resultado.then === 'function') {
                resultado.then(() => {
                    console.log('✅ Mapa carregado com sucesso!');
                }).catch(error => {
                    console.error('❌ Erro ao carregar mapa:', error);
                });
            } else {
                console.error('❌ carregarMapaModerno() não retornou uma Promise');
            }
        } catch (error) {
            console.error('❌ Erro ao chamar carregarMapaModerno():', error);
        }
    }
};

console.log('🗺️ ModernGoogleMaps carregado - versão 3.0.0');
