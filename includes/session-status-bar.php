<?php
/**
 * Barra de Status da Sessão Unificada
 * 
 * Componente reutilizável para exibir o status da sessão em todas as páginas
 */

// Evita inclusão múltipla
if (defined('SESSION_STATUS_BAR_INCLUDED')) {
    return;
}
define('SESSION_STATUS_BAR_INCLUDED', true);
?>

<!-- Barra de Status da Sessão - Container Dinâmico -->
<!-- O conteúdo é gerado via JavaScript baseado na variante selecionada -->
<div id="session-status-bar" class="variant-v8" data-button-style="solid">
    <!-- Conteúdo será renderizado dinamicamente por renderVariantContent() -->
</div>

<!-- Botão discreto para reabrir a barra -->
<div id="session-status-reopen-btn" onclick="showSessionStatusBar()">
    📊 Status
</div>

<script>
// Utilitários de logging (isLocalhost, debugLog, debugInfo) são providos por session-manager.js
// que já foi carregado antes deste componente ser incluído

// Funções globais para controlar a barra de status
window.sessionStatusBarAPI = {
    sessionStartTime: null,
    sessionTimerInterval: null,
    
    show: function() {
        const bar = document.getElementById('session-status-bar');
        const reopenBtn = document.getElementById('session-status-reopen-btn');
        if (bar) {
            bar.style.display = 'block';
            // Ajusta o padding do body para compensar a barra fixa (altura reduzida)
            document.body.style.paddingTop = '44px';
            document.body.style.transition = 'padding-top 0.3s ease';
        }
        if (reopenBtn) {
            // Safari-compatible hiding
            reopenBtn.style.display = 'none';
            reopenBtn.style.visibility = 'hidden';
            reopenBtn.style.opacity = '0';
            reopenBtn.style.pointerEvents = 'none';
        }
    },
    
    hide: function() {
        const bar = document.getElementById('session-status-bar');
        const reopenBtn = document.getElementById('session-status-reopen-btn');
        if (bar) {
            bar.style.display = 'none';
            document.body.style.paddingTop = '0';
        }
        if (reopenBtn) {
            // Safari-compatible visibility
            reopenBtn.style.display = 'block';
            reopenBtn.style.visibility = 'visible';
            reopenBtn.style.opacity = '1';
            reopenBtn.style.transform = 'translateY(0) scale(1)';
            
            // Force re-render para Safari
            setTimeout(() => {
                reopenBtn.style.pointerEvents = 'auto';
                reopenBtn.offsetHeight; // Force reflow
            }, 50);
        }
    },
    
    updateStatus: function(message, type = 'info', showTimer = true) {
        const textElement = document.getElementById('session-status-text');
        const indicator = document.getElementById('session-status-indicator');
        
        // Detecta variante atual
        const statusBar = document.getElementById('session-status-bar');
        const currentVariant = statusBar?.className.match(/variant-(v\d+)/)?.[1] || 'v8';
        
        // PRIMEIRO: Para o timer se showTimer for false (antes de atualizar conteúdo)
        if (!showTimer && this.sessionTimerInterval) {
            clearInterval(this.sessionTimerInterval);
            this.sessionTimerInterval = null;
            debugLog('🔧 Timer pausado - showTimer = false');
        }
        
        // SEGUNDO: Atualiza conteúdo específico da variante V2 (tooltip independente)
        if (currentVariant === 'v2') {
            const tooltip = document.querySelector('.v2-tooltip');
            if (tooltip) {
                if (this.sessionStartTime && showTimer) {
                    // Com timer ativo
                    const elapsed = Date.now() - this.sessionStartTime;
                    const minutes = Math.floor(elapsed / 60000);
                    const seconds = Math.floor((elapsed % 60000) / 1000);
                    const formatted = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    tooltip.innerHTML = `${message} - Sessão: <span class="session-timer">${formatted}</span>`;
                } else {
                    // Sem timer (erro, loading, etc)
                    tooltip.textContent = message;
                    debugLog('📝 Tooltip V2 atualizada para:', message);
                }
            }
        }
        
        if (textElement) {
            // Armazena mensagem base para uso com timer
            textElement.dataset.baseMessage = message;
            textElement.dataset.showTimer = showTimer ? 'true' : 'false';
            
            // Outras variantes (V2 já foi tratado acima)
            if (currentVariant !== 'v2') {
                if (this.sessionStartTime && showTimer) {
                    const elapsed = Date.now() - this.sessionStartTime;
                    const minutes = Math.floor(elapsed / 60000);
                    const seconds = Math.floor((elapsed % 60000) / 1000);
                    const formatted = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    
                    // Usa innerHTML para preservar estrutura de timer
                    textElement.innerHTML = `${message} <span class="session-timer-container">(<span class="session-timer">${formatted}</span>)</span>`;
                } else {
                    textElement.textContent = message;
                }
            }
        }
        
        if (indicator) {
            const colors = {
                'success': '#28a745',
                'error': '#dc3545', 
                'warning': '#ffc107',
                'info': '#17a2b8',
                'loading': '#6c757d'
            };
            
            indicator.style.backgroundColor = colors[type] || colors.info;
            indicator.style.boxShadow = `0 0 0 2px ${colors[type] || colors.info}20`;
        }
        
        // Inicia o timer se o tipo for success (sessão conectada) e showTimer for true
        if (type === 'success' && !this.sessionStartTime && showTimer) {
            this.startSessionTimer();
        } else if (type === 'success' && showTimer && this.sessionStartTime && !this.sessionTimerInterval) {
            // Reinicia o timer se ele foi parado mas deveria estar ativo
            this.startSessionTimer();
        }
    },
    
    updateUserInfo: function(userInfo) {
        const userInfoElement = document.getElementById('session-user-info');
        if (!userInfoElement || !userInfo) return;
        
        // Detecta a variante atual
        const statusBar = document.getElementById('session-status-bar');
        const currentVariant = statusBar?.className.match(/variant-(v\d+)/)?.[1] || 'v8';
        
        debugLog(`👤 Atualizando info do usuário para variante ${currentVariant}`);
        
        let infoHTML = '';
        
        // V4: Inclui foto do usuário
        if (currentVariant === 'v4' && userInfo.photo) {
            let photoUrl = '';
            
            // Verifica se photo é string (URL completa) ou objeto {prefix, suffix}
            if (typeof userInfo.photo === 'string') {
                photoUrl = userInfo.photo;
                // Se não tem o tamanho, adiciona
                if (photoUrl && !photoUrl.includes('64x64') && photoUrl.endsWith('/')) {
                    photoUrl = photoUrl + '64x64.jpg';
                }
            } else if (typeof userInfo.photo === 'object') {
                // Photo vem como objeto {prefix, suffix} da API
                const photoPrefix = userInfo.photo.prefix || '';
                const photoSuffix = userInfo.photo.suffix || '';
                
                if (photoPrefix && photoSuffix) {
                    photoUrl = `${photoPrefix}64x64${photoSuffix}`;
                }
            }
            
            if (photoUrl) {
                infoHTML += `• <img src="${photoUrl}" alt="${userInfo.name}" class="user-photo">`;
            }
        }
        
        // Nome com link para perfil usando canonicalUrl
        // Define classe específica para cada variante
        const userLinkClass = `${currentVariant}-user-link`;
        
        // V2, V4 e V8 têm formatação diferente
        const userPrefix = currentVariant === 'v2' ? '' : 
                          currentVariant === 'v4' ? '' : 
                          currentVariant === 'v5' ? '' : 
                          currentVariant === 'v6' ? '<span style="margin-right: 3px;">👤</span> ' :
                          currentVariant === 'v8' ? '<strong style="color: #495057;">Usuário:</strong> ' : 
                          '👤 ';
        
        if (userInfo.name) {
            if (userInfo.canonicalUrl) {
                infoHTML += `${userPrefix}<a href="${userInfo.canonicalUrl}" target="_blank" class="${userLinkClass}">${userInfo.name}</a>`;
            } else if (userInfo.id) {
                // Fallback para ID se canonicalUrl não estiver disponível
                const profileUrl = `https://app.foursquare.com/user/${userInfo.id}`;
                infoHTML += `${userPrefix}<a href="${profileUrl}" target="_blank" class="${userLinkClass}">${userInfo.name}</a>`;
            } else {
                infoHTML += `${userPrefix}<span>${userInfo.name}</span>`;
            }
        }
        
        // Placemaker Level (superuser) - mostra ícone especial em v4
        if (userInfo.superuser_level && userInfo.superuser_level > 0) {
            if (currentVariant === 'v4') {
                // V4 tem formato especial: • <ícone> <texto>
                infoHTML += ` <span style="color: #868e96;">•</span> <img src="/img/superUserIcon@2x-4601df23d46b00829d34bbb47914e2e6.png" class="v4-superuser-icon" alt="Superuser" title="Placemaker Level ${userInfo.superuser_level}"> <span style="font-size: 12px;">Placemaker Level ${userInfo.superuser_level}</span>`;
            } else {
                infoHTML += ` • 🏅 Placemaker Level ${userInfo.superuser_level}`;
            }
        }
        
        if (infoHTML) {
            userInfoElement.innerHTML = infoHTML;
            // V4 usa display: flex (definido no CSS), outras variantes usam block
            if (currentVariant === 'v4') {
                userInfoElement.removeAttribute('style');
            } else {
                userInfoElement.style.display = 'block';
            }
        }
        
        // Mostra botão Debug se superuser >= 8
        const debugBtn = document.getElementById('session-debug-btn');
        if (debugBtn && userInfo.superuser_level >= 8) {
            debugBtn.style.display = 'inline-block';
        } else if (debugBtn) {
            debugBtn.style.display = 'none';
        }
    },
    
    startSessionTimer: function() {
        if (!this.sessionStartTime) {
            this.sessionStartTime = Date.now();
        }
        
        // Atualiza o contador a cada segundo
        if (!this.sessionTimerInterval) {
            this.sessionTimerInterval = setInterval(() => {
                this.updateSessionTimer();
            }, 1000);
        }
        
        this.updateSessionTimer();
    },
    
    updateSessionTimer: function() {
        if (!this.sessionStartTime) return;
        
        // Verifica se deve mostrar timer
        const statusText = document.getElementById('session-status-text');
        const showTimer = statusText?.dataset.showTimer !== 'false';
        
        if (!showTimer) {
            return; // Não atualiza timer se showTimer for false
        }
        
        const elapsed = Date.now() - this.sessionStartTime;
        const minutes = Math.floor(elapsed / 60000);
        const seconds = Math.floor((elapsed % 60000) / 1000);
        const formatted = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        // Detecta variante atual
        const statusBar = document.getElementById('session-status-bar');
        const currentVariant = statusBar?.className.match(/variant-(v\d+)/)?.[1] || 'v8';
        
        // V2 atualiza no tooltip
        if (currentVariant === 'v2') {
            const timerElement = document.querySelector('.v2-tooltip .session-timer');
            if (timerElement) {
                timerElement.textContent = formatted;
            } else {
                const tooltip = document.querySelector('.v2-tooltip');
                if (tooltip && statusText?.dataset.baseMessage) {
                    tooltip.innerHTML = `${statusText.dataset.baseMessage} - Sessão: <span class="session-timer">${formatted}</span>`;
                }
            }
        }
        // Outras variantes atualizam no session-timer
        else {
            const timerElement = document.querySelector('.session-timer');
            if (timerElement) {
                timerElement.textContent = formatted;
            } else {
                // Se timer não existe, atualiza texto completo
                if (statusText && statusText.dataset.baseMessage) {
                    statusText.innerHTML = `${statusText.dataset.baseMessage} <span class="session-timer-container">(<span class="session-timer">${formatted}</span>)</span>`;
                }
            }
        }
    },
    
    clearCache: async function() {
        if (window.sessionManager && typeof window.sessionManager.forceClearCache === 'function') {
            this.updateStatus('🧹 Limpando cache...', 'loading');
            const result = await window.sessionManager.forceClearCache();
            if (!result) {
                this.updateStatus('❌ Erro ao limpar cache', 'error');
            }
        } else {
            console.warn('⚠️ SessionManager não disponível para limpeza de cache');
            this.updateStatus('⚠️ Funcionalidade não disponível', 'warning');
        }
    }
};

function hideSessionStatusBar() {
    window.sessionStatusBarAPI.hide();
    // Salva preferência do usuário APENAS para a sessão atual
    sessionStorage.setItem('session-status-bar-hidden', 'true');
}

function showSessionStatusBar() {
    window.sessionStatusBarAPI.show();
    sessionStorage.removeItem('session-status-bar-hidden');
}

/**
 * Renderiza o conteúdo específico de cada variante
 * @param {string} variant - Variante (v1-v8)
 * @param {string} buttonStyle - Estilo dos botões (solid, outline, thick, inverted, gradient)
 */
function renderVariantContent(variant, buttonStyle) {
    const statusBar = document.getElementById('session-status-bar');
    if (!statusBar) {
        console.error('❌ #session-status-bar não encontrado');
        return;
    }
    
    // Limpa conteúdo existente
    statusBar.innerHTML = '';
    
    // Cada variante cria seu próprio HTML completo
    let contentHTML = '';
    
    switch(variant) {
        case 'v1': // Horizontal Compacto
            contentHTML = `
                <div id="session-status-container">
                    <div class="status-content">
                        <div class="v1-status">
                            <div id="session-status-indicator" class="status-indicator"></div>
                            <strong>Status:</strong>
                            <span id="session-status-text">Verificando...</span>
                            <span>•</span>
                            <span id="session-user-info" style="display: none;"></span>
                        </div>
                    </div>
                    <div id="session-controls" class="status-controls"></div>
                </div>
            `;
            break;
            
        case 'v2': // Vertical com Separador
            contentHTML = `
                <div id="session-status-container">
                    <div class="v2-container">
                        <div class="v2-icon">
                            <div id="session-status-indicator" class="status-indicator"></div>
                            <div class="v2-tooltip">Conectado - Sessão: <span class="session-timer">0:00</span></div>
                        </div>
                        <div id="session-user-info" class="v2-user-info" style="display: none;"></div>
                    </div>
                    <div id="session-controls" class="status-controls"></div>
                </div>
            `;
            break;
            
        case 'v3': // Minimalista
            contentHTML = `
                <div id="session-status-container">
                    <div class="status-content">
                        <div class="v3-status">
                            <div id="session-status-indicator" class="status-indicator"></div>
                            <strong style="color: #495057;">Status:</strong>
                            <span id="session-status-text" style="color: #6c757d;">Verificando...</span>
                            <span style="color: #868e96;">•</span>
                            <span id="session-user-info" style="display: none;"></span>
                        </div>
                    </div>
                    <div id="session-controls" class="status-controls"></div>
                </div>
            `;
            break;
            
        case 'v4': // Com Foto do Usuário
            contentHTML = `
                <div id="session-status-container">
                    <div class="v4-container">
                        <div class="v4-status">
                            <div id="session-status-indicator" class="status-indicator"></div>
                            <strong>Status:</strong>
                            <span id="session-status-text">Verificando...</span>
                        </div>
                        <div id="session-user-info" class="v4-user-info"></div>
                    </div>
                    <div id="session-controls" class="status-controls"></div>
                </div>
            `;
            break;
            
        case 'v5': // Com Badge de Status
            contentHTML = `
                <div id="session-status-container">
                    <div class="status-content">
                        <div class="v5-status">
                            <div id="session-status-indicator" class="status-indicator"></div>
                            <strong style="color: #f8f9fa;">Status:</strong>
                            <span id="session-status-text" style="color: #adb5bd;">Verificando...</span>
                            <span style="color: #6c757d;">•</span>
                            <span id="session-user-info" style="display: none;"></span>
                        </div>
                    </div>
                    <div id="session-controls" class="status-controls"></div>
                </div>
            `;
            break;
            
        case 'v6': // Centralizado
            contentHTML = `
                <div id="session-status-container">
                    <div class="v6-container">
                        <div class="v6-content">
                            <div class="v6-status-row">
                                <div id="session-status-indicator" class="status-indicator"></div>
                                <strong style="color: #495057;">Status:</strong>
                                <span id="session-status-text" style="color: #6c757d;">Verificando...</span>
                            </div>
                            <div id="session-user-info" class="v6-user-row" style="display: none;"></div>
                        </div>
                    </div>
                    <div id="session-controls" class="status-controls"></div>
                </div>
            `;
            break;
            
        case 'v7': // Estilo Card
            contentHTML = `
                <div id="session-status-container">
                    <div class="status-content">
                        <div class="v7-status">
                            <div id="session-status-indicator" class="status-indicator"></div>
                            <strong style="color: #495057;">Status:</strong>
                            <span id="session-status-text" style="color: #6c757d;">Verificando...</span>
                            <span style="color: #868e96;">•</span>
                            <span id="session-user-info" style="display: none;"></span>
                        </div>
                    </div>
                    <div id="session-controls" class="status-controls"></div>
                </div>
            `;
            break;
            
        case 'v8': // Padrão Horizontal (fallback)
        default:
            contentHTML = `
                <div id="session-status-container">
                    <div class="v8-container">
                        <div id="session-status-indicator" class="status-indicator"></div>
                        <div class="v8-content">
                            <div class="v8-status-row">
                                <strong style="color: #495057;">Status:</strong>
                                <span id="session-status-text" style="color: #6c757d;">Verificando...</span>
                            </div>
                            <div id="session-user-info" class="v8-user-row" style="display: none;"></div>
                        </div>
                    </div>
                    <div id="session-controls" class="status-controls"></div>
                </div>
            `;
            break;
    }
    
    // Renderiza o conteúdo diretamente
    statusBar.innerHTML = contentHTML;
    
    // NÃO renderiza botões automaticamente - aguarda estilo carregar
    // Os botões serão renderizados após SessionManager carregar os dados
    const controlsDiv = document.getElementById('session-controls');
    if (controlsDiv) {
        // Adiciona apenas o botão X inicialmente
        const closeBtn = document.createElement('button');
        closeBtn.id = 'session-close-btn';
        closeBtn.className = `btn-style-${buttonStyle}`;
        closeBtn.setAttribute('onclick', 'hideSessionStatusBar()');
        closeBtn.setAttribute('title', 'Ocultar barra de status');
        closeBtn.innerHTML = '×';
        controlsDiv.appendChild(closeBtn);
    }
    
    debugLog(`✅ Variante ${variant} renderizada (botões serão adicionados após dados carregarem)`);
}

/**
 * Renderiza os botões de controle
 * @param {HTMLElement} container - Container onde os botões serão adicionados
 * @param {string} variant - Variante atual (para ajustar tamanho de SVG)
 * @param {string} buttonStyle - Estilo dos botões
 */
function renderButtons(container, variant, buttonStyle) {
    // Define função SVG helper - SVGS ORIGINAIS DO DEBUG
    const getSVG = (name) => {
        const svgs = {
            refresh: `<svg viewBox="0 0 16 16" fill="currentColor" style="margin: -1px 3px 0 -2px;"><path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>`,
            info: `<svg viewBox="0 0 16 16" fill="currentColor" style="margin: -1px 4px 1px 0;"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>`,
            debug: `<svg viewBox="0 0 12 12" fill="currentColor" style="margin: -1px 4px 1px 0;"><path d="m9.72227,1.8561c-0.03275,-0.03275 -0.06648,-0.05636 -0.10121,-0.07105c-0.03255,-0.01379 -0.07055,-0.02054 -0.11411,-0.02054c-0.04376,0 -0.08256,0.00695 -0.1155,0.02094c-0.03414,0.01439 -0.06708,0.03761 -0.09863,0.06966l-0.00208,0.00208l-0.61592,0.61404c-0.09228,0.09179 -0.23488,0.10201 -0.33837,0.03106c-0.07244,-0.04684 -0.14964,-0.09199 -0.23061,-0.13575c-0.08583,-0.04644 -0.16998,-0.08812 -0.25214,-0.12582c-0.08564,-0.0391 -0.17306,-0.07591 -0.26207,-0.11044c-0.08067,-0.03136 -0.17058,-0.06331 -0.26862,-0.09566c-0.11223,-0.03701 -0.18358,-0.1414 -0.18358,-0.25363l0,0l0,-0.94447c0,-0.04525 -0.00714,-0.08524 -0.02133,-0.12007c-0.01419,-0.03443 -0.03582,-0.06648 -0.06569,-0.09625c-0.02907,-0.02907 -0.06063,-0.05061 -0.09516,-0.0646c-0.03473,-0.01419 -0.07482,-0.02124 -0.12017,-0.02124l-1.1853,0c-0.04197,0 -0.07919,0.00675 -0.11173,0.02024c-0.03453,0.01419 -0.06837,0.03741 -0.10181,0.06976l-0.0004,0.0003c-0.02937,0.02937 -0.051,0.06063 -0.0649,0.09387c-0.01429,0.03404 -0.02143,0.07333 -0.02143,0.11798l0,0.86469c0,0.13148 -0.09496,0.24073 -0.21999,0.26306c-0.09496,0.02262 -0.18268,0.04604 -0.26256,0.07055c-0.08752,0.02679 -0.17554,0.05726 -0.26375,0.09169c-0.00437,0.00169 -0.00873,0.00327 -0.0131,0.00466c-0.0771,0.02997 -0.15837,0.0647 -0.24371,0.10409c-0.08673,0.03999 -0.1675,0.08057 -0.24232,0.12136c-0.10548,0.05745 -0.23269,0.03612 -0.31406,-0.04396l-0.0001,0.0001l-0.68211,-0.67228c-0.00208,-0.00208 -0.00417,-0.00417 -0.00625,-0.00625l0,0.0001c-0.02907,-0.03076 -0.05964,-0.05329 -0.09169,-0.06728c-0.03076,-0.0134 -0.06718,-0.02024 -0.10965,-0.02024c-0.04217,0 -0.07988,0.00714 -0.11302,0.02133c-0.03632,0.01548 -0.07105,0.03969 -0.10439,0.07234l-0.0002,0.0002l-0.82748,0.82937l-0.0005,0.0006l0.0005,0.0005c-0.03394,0.03394 -0.05795,0.06767 -0.07214,0.10121c-0.01379,0.03255 -0.02064,0.07055 -0.02064,0.11402c0,0.04386 0.00695,0.08256 0.02094,0.1157c0.01439,0.03404 0.03751,0.06698 0.06966,0.09854l0.00208,0.00208l0.61404,0.61602c0.09179,0.09228 0.10201,0.23488 0.03106,0.33837c-0.04684,0.07254 -0.09199,0.14964 -0.13565,0.23061c-0.04644,0.08593 -0.08812,0.16988 -0.12582,0.25224c-0.0392,0.08554 -0.07601,0.17306 -0.11064,0.26207c-0.03136,0.08057 -0.06321,0.17048 -0.09556,0.26852c-0.03701,0.11233 -0.1415,0.18358 -0.25363,0.18358l0,0.0001l-0.94417,0c-0.04545,0 -0.08534,0.00705 -0.12017,0.02114c-0.03433,0.01409 -0.06609,0.03552 -0.09506,0.0646l-0.00109,0.00119l0,0c-0.02907,0.02907 -0.05061,0.06073 -0.0647,0.09506c-0.01419,0.03483 -0.02114,0.07482 -0.02114,0.12017l0,1.1854c0,0.04188 0.00665,0.07919 0.02014,0.11163c0.01429,0.03453 0.03751,0.06837 0.06986,0.10191l0.0003,0.0002c0.02927,0.02937 0.06063,0.0511 0.09377,0.0649c0.03414,0.01429 0.07343,0.02143 0.11808,0.02143l0.86459,0c0.13148,0 0.24073,0.09496 0.26306,0.22009c0.02262,0.09486 0.04604,0.18268 0.07055,0.26256c0.02689,0.08752 0.05726,0.17544 0.09169,0.26365c0.03235,0.08405 0.06906,0.17137 0.10995,0.26187c0.04178,0.09258 0.08266,0.17693 0.12235,0.25324c0.05527,0.10578 0.03235,0.2318 -0.04823,0.31188l0.0002,0.0002l-0.67238,0.6704l-0.00427,0.00417l0.0001,0c-0.03076,0.02917 -0.05329,0.05974 -0.06718,0.09169c-0.0135,0.03076 -0.02024,0.06728 -0.02024,0.10975c0,0.04217 0.00714,0.07978 0.02124,0.11292c0.01548,0.03632 0.03969,0.07115 0.07244,0.10439l0.8245,0.83403c0.03225,0.02987 0.06589,0.0517 0.10112,0.06549c0.03533,0.01399 0.07541,0.02084 0.12027,0.02084c0.04525,0 0.08603,-0.00705 0.12195,-0.02124c0.03453,-0.01359 0.06698,-0.03443 0.09705,-0.06261l0.61116,-0.62088c0.09218,-0.09367 0.23656,-0.10489 0.34115,-0.03265c0.07234,0.04664 0.14924,0.09159 0.23002,0.13515c0.08593,0.04654 0.16988,0.08822 0.25224,0.12582c0.08554,0.0393 0.17306,0.07611 0.26207,0.11064c0.08057,0.03126 0.17038,0.06321 0.26852,0.09556c0.11233,0.03701 0.18358,0.1415 0.18358,0.25363l0.0001,0l0,0.94447c0,0.04525 0.00705,0.08524 0.02124,0.12007c0.01409,0.03443 0.03552,0.06609 0.0646,0.09516l0.0005,0.0006l0.0005,-0.0006c0.05825,0.05825 0.12969,0.08693 0.21523,0.08693l1.18521,0c0.04207,0 0.07928,-0.00665 0.11183,-0.02014c0.03453,-0.01429 0.06837,-0.03751 0.10171,-0.06986l0.0004,-0.0003c0.02927,-0.02937 0.051,-0.06063 0.0649,-0.09377c0.01439,-0.03414 0.02153,-0.07333 0.02153,-0.11798l0,-0.86479c0,-0.13138 0.09476,-0.24073 0.21999,-0.26296c0.09476,-0.02262 0.18258,-0.04614 0.26256,-0.07055c0.08742,-0.02689 0.17544,-0.05726 0.26356,-0.09159c0.08415,-0.03245 0.17157,-0.06916 0.26227,-0.11005c0.09228,-0.04178 0.17673,-0.08256 0.25274,-0.12225c0.10598,-0.05527 0.232,-0.03225 0.31208,0.04823l0.0002,-0.0002l0.6704,0.67228l0.00407,0.00427l0,-0.0001c0.02947,0.03096 0.06003,0.05339 0.09189,0.06728c0.03116,0.0134 0.06877,0.02024 0.11362,0.02024c0.04396,0 0.08216,-0.00714 0.11511,-0.02133c0.03314,-0.01439 0.064,-0.03652 0.09209,-0.06619c0.00308,-0.00337 0.00635,-0.00665 0.00992,-0.00992l0.83046,-0.82083c0.02967,-0.03215 0.0516,-0.06589 0.06529,-0.10112c0.01399,-0.03533 0.02094,-0.07551 0.02094,-0.12037c0,-0.04525 -0.00695,-0.08603 -0.02114,-0.12195c-0.01379,-0.03453 -0.03453,-0.06688 -0.06281,-0.09695l-0.62078,-0.61126c-0.09377,-0.09218 -0.10489,-0.23647 -0.03275,-0.34115c0.04674,-0.07234 0.09169,-0.14924 0.13525,-0.22992c0.04654,-0.08583 0.08822,-0.16988 0.12582,-0.25224c0.0392,-0.08554 0.07601,-0.17306 0.11064,-0.26207c0.03116,-0.08057 0.06321,-0.17048 0.09556,-0.26862c0.03701,-0.11233 0.1415,-0.18358 0.25373,-0.18358l0,-0.0001l0.94427,0c0.04535,0 0.08544,-0.00705 0.12017,-0.02124c0.03433,-0.01409 0.06609,-0.03552 0.09516,-0.0645l0.00099,-0.00109l0,0c0.02907,-0.02917 0.05081,-0.06083 0.0647,-0.09526c0.01419,-0.03473 0.02114,-0.07472 0.02114,-0.12007l0,-1.1854c0,-0.04188 -0.00655,-0.07919 -0.02014,-0.11173c-0.01419,-0.03443 -0.03741,-0.06837 -0.06976,-0.10191l-0.0004,-0.0003c-0.02927,-0.02937 -0.06063,-0.051 -0.09377,-0.0649c-0.03394,-0.01429 -0.07323,-0.02143 -0.11798,-0.02143l-0.86459,0c-0.13317,0 -0.24341,-0.09715 -0.26375,-0.22446c-0.02094,-0.08365 -0.04455,-0.16849 -0.07135,-0.25462c-0.0258,-0.08266 -0.05597,-0.17008 -0.09129,-0.26246c-0.00179,-0.00447 -0.00327,-0.00903 -0.00466,-0.01359c-0.03354,-0.08782 -0.06787,-0.17038 -0.1031,-0.24768c-0.0382,-0.08365 -0.07869,-0.16452 -0.12156,-0.24272c-0.05736,-0.10548 -0.03602,-0.23269 0.04396,-0.31406l0,-0.0001l0.67228,-0.68211c0.00208,-0.00208 0.00407,-0.00417 0.00625,-0.00625l0,0c0.03086,-0.02907 0.05329,-0.05964 0.06728,-0.09159c0.0134,-0.03076 0.02034,-0.06718 0.02034,-0.10965c0,-0.04217 -0.00714,-0.07988 -0.02133,-0.11302c-0.01548,-0.03632 -0.03969,-0.07105 -0.07244,-0.10439l-0.0002,-0.0002l-0.82946,-0.82748l-0.00099,-0.00089l0,0l0,0zm0.10538,-0.56174c0.09943,0.04197 0.18973,0.1034 0.2711,0.18477l0.0002,0.0001l0.83165,0.82976l0.00208,0.00208c0.08008,0.08137 0.1413,0.17097 0.18318,0.26921c0.04326,0.10131 0.06509,0.20858 0.06509,0.3218c0,0.11491 -0.02233,0.22228 -0.06629,0.3226c-0.04287,0.09844 -0.10548,0.18596 -0.18645,0.26326l-0.53257,0.54031c0.01568,0.03265 0.03136,0.06569 0.04654,0.09913c0.04267,0.09308 0.08177,0.18635 0.11729,0.27973c0.00198,0.00417 0.00367,0.00843 0.00526,0.0128c0.03543,0.09298 0.06936,0.19181 0.10171,0.29581c0.01042,0.03354 0.02054,0.06728 0.03027,0.10131l0.66008,0c0.11471,0 0.22208,0.02104 0.3227,0.06311c0.09883,0.04138 0.18814,0.10241 0.26782,0.18268l0.00566,0.00566c0.07849,0.08117 0.13803,0.17068 0.17852,0.26891c0.04148,0.09992 0.06202,0.20461 0.06202,0.31426l0,1.1853c0,0.11402 -0.02014,0.22079 -0.06083,0.32051c-0.04068,0.09953 -0.10131,0.18993 -0.18239,0.2712l-0.0004,0.0003l-0.0006,0.00069l-0.0004,0.0004c-0.08137,0.08107 -0.17167,0.1419 -0.27149,0.18258c-0.09983,0.04059 -0.2066,0.06083 -0.32051,0.06083l-0.75504,0c-0.0132,0.03552 -0.02689,0.07224 -0.04168,0.10975c-0.03781,0.09715 -0.07869,0.19429 -0.12305,0.29134c-0.04594,0.10032 -0.09268,0.19509 -0.14031,0.2835c-0.01568,0.02898 -0.03175,0.05785 -0.04823,0.08673l0.47134,0.464c0.00347,0.00327 0.00675,0.00655 0.01032,0.01012c0.0773,0.08177 0.13545,0.17256 0.17465,0.27179c0.0393,0.09963 0.05874,0.20521 0.05874,0.31615c0,0.11054 -0.01935,0.21523 -0.05835,0.31446c-0.039,0.09893 -0.09655,0.18933 -0.17306,0.2713l0,-0.0001c-0.00268,0.00278 -0.00546,0.00576 -0.00834,0.00853l-0.83651,0.82688c-0.07849,0.08226 -0.16691,0.14517 -0.26614,0.18784c-0.10022,0.04326 -0.20858,0.06519 -0.32577,0.06519c-0.11471,0 -0.22287,-0.02243 -0.32448,-0.06619c-0.10062,-0.04356 -0.18953,-0.10657 -0.26703,-0.18834l-0.53029,-0.53168c-0.03543,0.01707 -0.07016,0.03324 -0.10389,0.04852c-0.0903,0.04068 -0.18685,0.08087 -0.28936,0.12047c-0.09635,0.03751 -0.19687,0.07214 -0.30116,0.10409c-0.03473,0.01062 -0.06896,0.02074 -0.1027,0.03036l0,0.65869c0,0.11461 -0.02114,0.22208 -0.06321,0.3226c-0.04148,0.09893 -0.10231,0.18814 -0.18278,0.26792l-0.00566,0.00576c-0.08117,0.07829 -0.17078,0.13773 -0.26881,0.17842c-0.10002,0.04138 -0.20471,0.06192 -0.31436,0.06192l-1.1854,0c-0.23309,0 -0.42996,-0.08038 -0.5931,-0.24351l0.0005,-0.0005c-0.08137,-0.08147 -0.1424,-0.17207 -0.18308,-0.27199c-0.04068,-0.09983 -0.06093,-0.2065 -0.06093,-0.32051l0,-0.75504c-0.03562,-0.0132 -0.07224,-0.02699 -0.10985,-0.04168c-0.09705,-0.03781 -0.19419,-0.07869 -0.29124,-0.12314c-0.10032,-0.04584 -0.19509,-0.09268 -0.2836,-0.14041c-0.02888,-0.01558 -0.05775,-0.03165 -0.08663,-0.04813l-0.464,0.47134c-0.00327,0.00347 -0.00665,0.00685 -0.01012,0.01022c-0.08177,0.0775 -0.17256,0.13565 -0.27179,0.17474c-0.09963,0.0393 -0.20521,0.05884 -0.31615,0.05884c-0.11054,0 -0.21533,-0.01935 -0.31446,-0.05845c-0.09893,-0.039 -0.18923,-0.09655 -0.2713,-0.17296l0.0001,-0.0002c-0.00288,-0.00258 -0.00576,-0.00536 -0.00843,-0.00824l-0.83165,-0.84127c-0.07998,-0.08137 -0.1412,-0.17107 -0.18308,-0.26931c-0.04317,-0.10141 -0.06509,-0.20858 -0.06509,-0.3217c0,-0.11491 0.02233,-0.22228 0.06619,-0.3227c0.04336,-0.09913 0.10628,-0.18715 0.18824,-0.26494l0.53177,-0.53029c-0.01707,-0.03543 -0.03334,-0.07016 -0.04862,-0.10419c-0.04078,-0.0902 -0.08087,-0.18655 -0.12037,-0.28896c-0.03751,-0.09635 -0.07224,-0.19687 -0.10419,-0.30126c-0.01062,-0.03483 -0.02074,-0.06896 -0.03036,-0.1027l-0.65849,0c-0.11471,0 -0.22218,-0.02114 -0.3226,-0.06321c-0.09903,-0.04138 -0.18824,-0.10231 -0.26792,-0.18268l-0.00576,-0.00595c-0.07839,-0.08107 -0.13783,-0.17068 -0.17842,-0.26881c-0.04138,-0.10002 -0.06192,-0.20471 -0.06192,-0.31416l0,-1.1854c0,-0.11402 0.02024,-0.22079 0.06093,-0.32051c0.04068,-0.09973 0.10131,-0.19012 0.18248,-0.27139l0.0001,-0.0001l0.0006,-0.0005l0.0005,-0.0005c0.08137,-0.08117 0.17167,-0.1419 0.27149,-0.18258c0.09973,-0.04068 0.2065,-0.06083 0.32051,-0.06083l0.75504,0c0.0131,-0.03562 0.02699,-0.07224 0.04168,-0.10985c0.03771,-0.09705 0.07869,-0.19419 0.12314,-0.29134c0.04584,-0.10032 0.09258,-0.19509 0.14041,-0.2835c0.01588,-0.02947 0.03235,-0.05894 0.04922,-0.08841l-0.4645,-0.46598l-0.0002,-0.0001c-0.08206,-0.08067 -0.14418,-0.17147 -0.18655,-0.27179c-0.04336,-0.10241 -0.0648,-0.21017 -0.0648,-0.3224c0,-0.11203 0.02133,-0.2188 0.0644,-0.32071c0.04267,-0.10092 0.10419,-0.19171 0.18487,-0.27239l0.0005,0.0005l0.82917,-0.83115l0.00208,-0.00208c0.08137,-0.07998 0.17097,-0.1412 0.26921,-0.18308c0.10141,-0.04317 0.20858,-0.06519 0.3218,-0.06519c0.11481,0 0.22218,0.02233 0.3226,0.06619c0.09844,0.04297 0.18596,0.10548 0.26326,0.18655l0.53991,0.53207c0.03245,-0.01608 0.0648,-0.03146 0.09705,-0.04634c0.08306,-0.0383 0.17474,-0.0769 0.27507,-0.1158c0.00427,-0.00198 0.00863,-0.00377 0.013,-0.00546c0.09635,-0.03751 0.19697,-0.07224 0.30136,-0.10419c0.03473,-0.01062 0.06896,-0.02074 0.1027,-0.03036l0,-0.65859c0,-0.11461 0.02114,-0.22208 0.06321,-0.3225c0.04138,-0.09903 0.10241,-0.18824 0.18268,-0.26802l0.00566,-0.00566c0.08117,-0.07839 0.17068,-0.13773 0.26881,-0.17832c0.10002,-0.04148 0.20471,-0.06202 0.31436,-0.06202l1.18521,0c0.11392,0 0.22079,0.02014 0.32051,0.06083c0.10022,0.04088 0.19092,0.10191 0.27268,0.18358c0.08077,0.08097 0.1417,0.17147 0.18258,0.27159c0.04068,0.09973 0.06083,0.2065 0.06083,0.32041l0,0.75514c0.03562,0.0132 0.07214,0.02709 0.10985,0.04168c0.09695,0.03771 0.19399,0.07869 0.29114,0.12305c0.10042,0.04584 0.19519,0.09268 0.2835,0.14051c0.02967,0.01598 0.05914,0.03235 0.08861,0.04922l0.46589,-0.4645l0.0002,-0.0001c0.08067,-0.08206 0.17157,-0.14418 0.27169,-0.18655c0.1025,-0.04336 0.21007,-0.0648 0.3223,-0.0648c0.11223,0 0.219,0.02124 0.32091,0.0643l0,0zm-3.73095,2.21303c0.17673,0 0.34919,0.01687 0.51739,0.05041c0.16532,0.03304 0.32925,0.08375 0.49149,0.15222l0.00179,0.00079l0.0004,-0.00079c0.1549,0.06688 0.30176,0.14676 0.44068,0.23984c0.13763,0.09218 0.26514,0.19628 0.38204,0.31208l0.00099,0.00109l0,0c0.1159,0.11699 0.21999,0.2444 0.31218,0.38214c0.09308,0.13892 0.17286,0.28578 0.23974,0.44068c0.00308,0.00734 0.00585,0.01469 0.00834,0.02213c0.0641,0.15569 0.11223,0.31277 0.14378,0.47144c0.03374,0.16829 0.05041,0.34066 0.05041,0.51729c0,0.17673 -0.01667,0.34919 -0.05041,0.51739c-0.03294,0.16542 -0.08375,0.32915 -0.15212,0.49149l-0.00079,0.00179l0.00079,0.0004c-0.06688,0.1549 -0.14676,0.30166 -0.23974,0.44058c-0.09228,0.13773 -0.19638,0.26514 -0.31218,0.38214l-0.00099,0.00109l0,0c-0.11709,0.1159 -0.2444,0.21999 -0.38223,0.31218c-0.13872,0.09308 -0.28568,0.17286 -0.44048,0.23974c-0.00734,0.00308 -0.01469,0.00595 -0.02233,0.00834c-0.15559,0.0642 -0.31277,0.11223 -0.47134,0.14388c-0.1682,0.03364 -0.34076,0.05041 -0.51739,0.05041c-0.17673,0 -0.34909,-0.01677 -0.51729,-0.05041c-0.16542,-0.03304 -0.32925,-0.08385 -0.49159,-0.15222l-0.00179,-0.00079l-0.0003,0.00079c-0.1549,-0.06688 -0.30186,-0.14676 -0.44068,-0.23974c-0.13773,-0.09218 -0.26514,-0.19628 -0.38204,-0.31218l-0.00099,-0.00109l0,0c-0.1159,-0.11709 -0.22009,-0.2444 -0.31218,-0.38223c-0.09298,-0.13882 -0.17286,-0.28578 -0.23964,-0.44048c-0.00318,-0.00734 -0.00595,-0.01469 -0.00843,-0.02223c-0.0642,-0.15569 -0.11223,-0.31277 -0.14388,-0.47144c-0.03354,-0.1682 -0.05041,-0.34066 -0.05041,-0.51739c0,-0.17673 0.01677,-0.34909 0.05041,-0.51729c0.03304,-0.16542 0.08385,-0.32925 0.15232,-0.49149l0.00069,-0.00179l-0.00069,-0.0003c0.06678,-0.1549 0.14676,-0.30176 0.23964,-0.44058c0.09218,-0.13783 0.19628,-0.26514 0.31218,-0.38223l0.00109,-0.00099l0,0c0.11689,-0.1158 0.2443,-0.21989 0.38194,-0.31208c0.13892,-0.09298 0.28588,-0.17296 0.44078,-0.23984c0.00734,-0.00318 0.01469,-0.00595 0.02223,-0.00834c0.15569,-0.0642 0.31267,-0.11213 0.47134,-0.14378c0.1682,-0.03374 0.34056,-0.05061 0.51729,-0.05061l0,0zm0.413,0.57434c-0.13337,-0.02669 -0.2709,-0.03999 -0.413,-0.03999c-0.142,0 -0.27953,0.0133 -0.413,0.03999c-0.12959,0.0259 -0.25333,0.06301 -0.37122,0.11134c-0.00516,0.00258 -0.01042,0.00516 -0.01588,0.00744c-0.1288,0.05567 -0.24728,0.11957 -0.35524,0.19181c-0.10876,0.07274 -0.20938,0.1551 -0.30206,0.24679c-0.09179,0.09278 -0.17415,0.1936 -0.24698,0.30245c-0.07224,0.10786 -0.13614,0.22624 -0.19171,0.35505l-0.00069,-0.0003c-0.0517,0.12275 -0.09099,0.25195 -0.11818,0.38749c-0.02659,0.13337 -0.03989,0.2709 -0.03989,0.4129s0.0133,0.27953 0.03989,0.413c0.0259,0.12959 0.06311,0.25333 0.11144,0.37122c0.00258,0.00526 0.00516,0.01042 0.00744,0.01588c0.05557,0.1288 0.11947,0.24708 0.19161,0.35495c0.07283,0.10886 0.1552,0.20967 0.24708,0.30245c0.09268,0.09179 0.1934,0.17405 0.30216,0.24679c0.10786,0.07224 0.22624,0.13614 0.35514,0.19181l-0.0003,0.00069c0.12285,0.0517 0.25195,0.09109 0.38749,0.11808c0.13337,0.02669 0.271,0.03989 0.413,0.03989c0.142,0 0.27963,-0.0133 0.413,-0.03989c0.12969,-0.0259 0.25333,-0.06301 0.37132,-0.11134c0.00506,-0.00258 0.01042,-0.00516 0.01588,-0.00744c0.1287,-0.05567 0.24708,-0.11957 0.35505,-0.19181c0.10876,-0.07274 0.20947,-0.1551 0.30216,-0.24679c0.09189,-0.09278 0.17425,-0.1935 0.24708,-0.30235c0.07214,-0.10786 0.13604,-0.22624 0.19151,-0.35505l0.00079,0.0003c0.0517,-0.12275 0.09109,-0.25195 0.11818,-0.38739c0.02659,-0.13346 0.03989,-0.271 0.03989,-0.413s-0.0134,-0.27953 -0.03989,-0.4129c-0.026,-0.12969 -0.06301,-0.25333 -0.11144,-0.37132c-0.00268,-0.00516 -0.00506,-0.01042 -0.00754,-0.01588c-0.05547,-0.1288 -0.11937,-0.24718 -0.19171,-0.35505c-0.07264,-0.10886 -0.1551,-0.20967 -0.24688,-0.30235c-0.09268,-0.09169 -0.1934,-0.17405 -0.30216,-0.24679c-0.10796,-0.07224 -0.22634,-0.13614 -0.35505,-0.19181l0.0002,-0.00069c-0.12275,-0.0517 -0.25185,-0.09109 -0.38749,-0.11818l0,0z"/></svg>`,
            logout: `<svg viewBox="0 0 16 16" fill="currentColor" style="margin: -1px 3px 1px 0;"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/></svg>`
        };
        return svgs[name] || '';
    };
    
    // Aplica estilo ao container (como no debug)
    container.className = `btn-style-${buttonStyle}`;
    
    // Cria botões com estrutura do debug
    const buttons = [
        { id: 'session-refresh-btn', className: 'btn refresh', icon: 'refresh', label: 'Verificar', onclick: "window.sessionManager?.checkSessionStatus(true)" },
        { id: 'session-info-btn', className: 'btn info', icon: 'info', label: 'Sistema', onclick: "window.sessionManager?.showSystemInfo()" },
        { id: 'session-debug-btn', className: 'btn debug', icon: 'debug', label: 'Debug', onclick: "window.location.href='/debug/'", hidden: true },
        { id: 'session-logout-btn', className: 'btn logout', icon: 'logout', label: 'Logout', onclick: "window.sessionManager?.logout()" }
    ];
    
    buttons.forEach(btn => {
        const button = document.createElement('button');
        button.id = btn.id;
        button.className = btn.className;
        button.setAttribute('onclick', btn.onclick);
        if (btn.hidden) button.style.display = 'none';
        
        button.innerHTML = `${getSVG(btn.icon)}${btn.label}`;
        container.appendChild(button);
    });
    
    // Botão fechar (sem estilo específico)
    const closeBtn = document.createElement('button');
    closeBtn.id = 'session-close-btn';
    closeBtn.setAttribute('onclick', 'hideSessionStatusBar()');
    closeBtn.setAttribute('title', 'Ocultar barra de status');
    closeBtn.innerHTML = '×';
    container.appendChild(closeBtn);
}

// Inicialização automática
document.addEventListener('DOMContentLoaded', function() {
    debugLog('🔧 SessionStatusBar: Inicializando renderização dinâmica...');
    
    // ETAPA 1: Determinar variante e estilo dos botões
    const temporaryVariant = localStorage.getItem('content_variant');
    const temporaryStyle = localStorage.getItem('button_style');
    const defaultVariant = localStorage.getItem('default_content_variant');
    const defaultStyle = localStorage.getItem('default_button_style');
    
    // Prioridade: temporary > default > fallback (v8/solid)
    const variant = temporaryVariant || defaultVariant || 'v8';
    const buttonStyle = temporaryStyle || defaultStyle || 'solid';
    
    debugLog(`📊 Variante selecionada: ${variant} | Estilo: ${buttonStyle}`);
    
    // ETAPA 2: Aplicar classes ao container principal
    const statusBar = document.getElementById('session-status-bar');
    if (statusBar) {
        // Remove classes antigas de variantes
        statusBar.className = statusBar.className.split(' ').filter(c => !c.startsWith('variant-')).join(' ');
        
        // Adiciona nova variante
        statusBar.classList.add(`variant-${variant}`);
        statusBar.dataset.buttonStyle = buttonStyle;
        
        debugLog(`✅ Classes aplicadas: ${statusBar.className}`);
    }
    
    // ETAPA 3: Renderizar conteúdo específico da variante
    if (typeof renderVariantContent === 'function') {
        renderVariantContent(variant, buttonStyle);
        debugLog('✅ Conteúdo da variante renderizado');
    } else {
        console.warn('⚠️ Função renderVariantContent não encontrada');
    }
    
    // ETAPA 4: Verificar se foi ocultada na sessão
    const hiddenInSession = sessionStorage.getItem('session-status-bar-hidden');
    
    if (!hiddenInSession) {
        window.sessionStatusBarAPI.show();
    } else {
        // Se foi ocultada na sessão, mostra o botão de reabrir
        const reopenBtn = document.getElementById('session-status-reopen-btn');
        if (reopenBtn) {
            reopenBtn.style.display = 'block';
        }
    }
    
    // ETAPA 5: Integração com SessionManager
    if (window.sessionManager) {
        // Override dos métodos do SessionManager para usar a barra unificada
        const originalUpdateStatus = window.sessionManager.updateStatus;
        window.sessionManager.updateStatus = function(message, type) {
            window.sessionStatusBarAPI.updateStatus(message, type);
            
            // Quando sessão conectar, renderiza os botões completos
            if (type === 'success') {
                const controlsDiv = document.getElementById('session-controls');
                if (controlsDiv && controlsDiv.children.length === 1) {
                    // Limpa apenas o botão X temporário
                    const closeBtn = controlsDiv.querySelector('#session-close-btn');
                    controlsDiv.innerHTML = '';
                    
                    // Renderiza botões completos
                    const statusBar = document.getElementById('session-status-bar');
                    const currentVariant = statusBar?.className.match(/variant-(v\d+)/)?.[1] || 'v8';
                    const buttonStyle = statusBar?.dataset.buttonStyle || 'solid';
                    renderButtons(controlsDiv, currentVariant, buttonStyle);
                    
                    debugLog('✅ Botões renderizados após conexão bem-sucedida');
                }
            }
            
            if (originalUpdateStatus) {
                originalUpdateStatus.call(this, message, type);
            }
        };
        
        const originalShowStatus = window.sessionManager.showStatus;
        window.sessionManager.showStatus = function(message, type, duration) {
            window.sessionStatusBarAPI.updateStatus(message, type);
            if (originalShowStatus) {
                originalShowStatus.call(this, message, type, duration);
            }
        };
        
        // Verifica sessão automaticamente
        setTimeout(() => {
            window.sessionManager.checkSessionStatus();
        }, 500);
    }
    
    debugLog('🎉 SessionStatusBar: Inicialização completa');
});
</script>
