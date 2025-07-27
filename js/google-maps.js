/**
 * Google Maps Integration
 * 
 * Sistema integração com Google Maps API v3 usando Dynamic Library Import
 * 
 * @version 5.0.0
 * @author Elio Gavlinski <gavlinski@gmail.com>
 */

class GoogleMaps {
    constructor(config = {}) {
        this.config = {
            apiKey: config.apiKey || '',
            defaultZoom: config.defaultZoom || 15,
            defaultCenter: config.defaultCenter || { lat: -23.5505, lng: -46.6333 },
            mapElementId: config.mapElementId || 'mapa-interno',
            libraries: config.libraries || ['maps', 'marker'],
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
     * Carrega a Google Maps API usando Dynamic Library Import
     */
    async loadMapsAPI() {
        if (this.loadPromise) {
            return this.loadPromise;
        }

        this.loadPromise = new Promise(async (resolve, reject) => {
            try {
                // Verifica se já está carregada
                if (window.google && window.google.maps && window.google.maps.importLibrary) {
                    this.isLoaded = true;
                    resolve();
                    return;
                }

                // Instala o carregador bootstrap inline conforme documentação oficial
                if (!window.google || !window.google.maps || !window.google.maps.importLibrary) {
                    const script = document.createElement('script');
                    script.async = true;
                    script.innerHTML = `
                        (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=\`https://maps.\${c}apis.com/maps/api/js?\`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
                            key: "${this.config.apiKey}",
                            v: "weekly",
                            loading: "async"
                        });
                    `;
                    document.head.appendChild(script);
                    
                    // Aguarda um momento para o bootstrap ser executado
                    await new Promise(resolve => setTimeout(resolve, 200));
                }

                // Carrega bibliotecas usando importLibrary
                await window.google.maps.importLibrary("maps");
                
                for (const library of this.config.libraries) {
                    if (library !== 'maps') {
                        await window.google.maps.importLibrary(library);
                    }
                }

                this.isLoaded = true;
                resolve();
                
            } catch (error) {
                reject(new Error(`Falha ao carregar Google Maps API: ${error.message}`));
            }
        });

        return this.loadPromise;
    }

    /**
     * Inicializa o mapa
     */
    async initialize(lat = null, lng = null, zoom = null) {
        return new Promise(async (resolve, reject) => {
            try {
                await this.loadMapsAPI();

                const center = (lat && lng) ? { lat: parseFloat(lat), lng: parseFloat(lng) } : this.config.defaultCenter;
                const mapZoom = zoom || this.config.defaultZoom;

                const mapOptions = {
                    center: center,
                    zoom: mapZoom,
                    disableDefaultUI: true,
                    mapId: this.config.mapId,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    mapTypeControl: false,
                    // mapTypeControlOptions: {
                    //     position: google.maps.ControlPosition.TOP_RIGHT
                    // },
                    zoomControl: false,
                    // zoomControlOptions: {
                    //     position: google.maps.ControlPosition.RIGHT_CENTER
                    // }
                };

                const mapElement = document.getElementById(this.config.mapElementId);
                if (!mapElement) {
                    return reject(new Error(`Elemento do mapa não encontrado: ${this.config.mapElementId}`));
                }

                this.map = new google.maps.Map(mapElement, mapOptions);
                this.bounds = new google.maps.LatLngBounds();

                google.maps.event.addListenerOnce(this.map, 'idle', () => {
                    this.onMapReady(this.map);
                    // Resolve a promessa com a instância do mapa quando estiver pronto
                    resolve(this.map);
                });

                // Adiciona controle customizado do Foursquare
                this.addFoursquareAttribution();
                
                // Adiciona listener para redimensionamento da div
                this.addResizeObserver();

            } catch (error) {
                reject(error);
            }
        });
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
        this.map.controls[google.maps.ControlPosition.TOP_RIGHT].push(controlDiv);

        console.log('✅ Controle Foursquare adicionado ao mapa');
    }

    /**
     * Adiciona marcadores
     */
    async addMarker(position, options = {}) {
        if (!this.isLoaded) {
            throw new Error('Google Maps API não carregada');
        }

        try {
            const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");
            
            const marker = new AdvancedMarkerElement({
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

        } catch (error) {
            console.error("Falha ao carregar AdvancedMarkerElement, usando fallback para Marker.", error);
            // Fallback para Marker tradicional em caso de erro
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
    }

    /**
     * Atualiza marcadores no mapa
     */
    async updateMarkers(locations = []) {
        if (!this.map) {
            return;
        }

        this.clearMarkers();

        for (let i = 0; i < locations.length; i++) {
            const location = locations[i];
            if (location && location[1] !== undefined && location[2] !== undefined) {
                const position = { lat: parseFloat(location[1]), lng: parseFloat(location[2]) };
                
                try {
                    await this.addMarker(position, {
                        title: `${i + 1}. ${location[0] || 'Local'}`,
                        draggable: true,
                        index: i
                    });
                } catch (error) {
                    console.error(`Erro ao adicionar marcador ${i}:`, error);
                }
            }
        }

        if (this.markers.length > 0) {
            this.map.fitBounds(this.bounds);
            
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
     * Adiciona observer para redimensionamento da div do mapa
     */
    addResizeObserver() {
        if (!this.map) return;

        const mapElement = document.getElementById(this.config.mapElementId);
        if (!mapElement) return;

        // Usa ResizeObserver se disponível (moderno)
        if (window.ResizeObserver) {
            const resizeObserver = new ResizeObserver(entries => {
                for (let entry of entries) {
                    // Trigga resize do Google Maps quando a div muda de tamanho
                    google.maps.event.trigger(this.map, 'resize');
                    
                    // Log para debug
                    console.log('🔄 Mapa redimensionado:', {
                        width: entry.contentRect.width,
                        height: entry.contentRect.height
                    });
                }
            });
            
            resizeObserver.observe(mapElement);
            console.log('✅ ResizeObserver ativo para o mapa');
            
        } else {
            // Fallback usando MutationObserver + polling (navegadores antigos)
            let lastWidth = mapElement.offsetWidth;
            let lastHeight = mapElement.offsetHeight;
            
            const checkResize = () => {
                const currentWidth = mapElement.offsetWidth;
                const currentHeight = mapElement.offsetHeight;
                
                if (currentWidth !== lastWidth || currentHeight !== lastHeight) {
                    google.maps.event.trigger(this.map, 'resize');
                    console.log('🔄 Mapa redimensionado (fallback):', {
                        width: currentWidth,
                        height: currentHeight
                    });
                    
                    lastWidth = currentWidth;
                    lastHeight = currentHeight;
                }
            };
            
            // Verifica a cada 250ms
            setInterval(checkResize, 250);
            console.log('✅ Resize fallback ativo para o mapa');
        }
    }
}

// Disponibiliza globalmente
window.GoogleMaps = GoogleMaps;

// Inicialização
function initializeGoogleMaps() {
    if (!window.googleMapsConfig) {
        console.error('Configuração Google Maps não encontrada');
        return;
    }
    
    if (window.googleMaps) {
        return;
    }

    try {
        const config = {
            apiKey: window.googleMapsConfig.apiKey,
            mapId: window.googleMapsConfig.mapId,
            defaultZoom: window.googleMapsConfig.defaultZoom || 15,
            defaultCenter: window.googleMapsConfig.defaultCenter || { lat: -23.5505, lng: -46.6333 },
            libraries: window.googleMapsConfig.libraries || ['marker'],
            
            onMapReady: (map) => {
                if (window.mapReadyCallback) {
                    window.mapReadyCallback(map);
                }
            },
            
            onMarkerDragEnd: (marker, event, index) => {
                if (window.atualizarPosicaoMarcador) {
                    window.atualizarPosicaoMarcador(index, event);
                }
            }
        };

        window.googleMaps = new GoogleMaps(config);

        // Dispara um evento global para notificar que o Google Maps está pronto
        console.log('✅ Google Maps está pronto. Disparando evento "google-maps-ready".');
        document.dispatchEvent(new Event('google-maps-ready'));
        
    } catch (error) {
        console.error('Erro ao inicializar GoogleMaps:', error);
    }
}

// Aguarda configuração estar disponível
function waitForConfig(callback, timeout = 10000) {
    const startTime = Date.now();
    
    function check() {
        if (window.googleMapsConfig) {
            callback();
        } else if (Date.now() - startTime < timeout) {
            setTimeout(check, 100);
        } else {
            console.error('Timeout: Configuração do Google Maps não encontrada');
        }
    }
    
    check();
}

// Inicializa quando estiver pronto
waitForConfig(initializeGoogleMaps);
