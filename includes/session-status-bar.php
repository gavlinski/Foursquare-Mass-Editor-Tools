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

<!-- Barra de Status da Sessão Unificada -->
<div id="session-status-bar" style="
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 10000;
    margin: 0;
    padding: 8px 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    backdrop-filter: blur(10px);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    display: none;
    transition: all 0.3s ease;
">
    <div style="
        width: 100%;
        max-width: 100vw;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 0 20px;
        box-sizing: border-box;
        overflow: visible;
        min-height: 36px;
    ">
        <!-- Status e Informações -->
        <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
            <div id="session-status-indicator" style="
                width: 12px;
                height: 12px;
                border-radius: 50%;
                background: #6c757d;
                transition: background-color 0.3s ease;
                box-shadow: 0 0 0 2px rgba(108, 117, 125, 0.2);
            "></div>
            
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <strong style="color: #495057; font-size: 14px;">Status:</strong>
                    <span id="session-status-text" style="color: #6c757d; font-size: 14px;">Verificando...</span>
                </div>
                
                <div id="session-user-info" style="
                    font-size: 12px;
                    color: #868e96;
                    margin-top: 2px;
                    display: none;
                "></div>
            </div>
        </div>

        <!-- Controles -->
        <div style="
            display: flex !important; 
            align-items: center !important; 
            gap: 10px !important;
            flex-wrap: nowrap !important;
            min-width: 0 !important;
            flex-shrink: 0 !important;
        ">
            <button id="session-refresh-btn" onclick="window.sessionManager?.checkSessionStatus(true)" style="
                padding: 6px 12px;
                background: #28a745;
                color: #ffffff;
                border: 1px solid #1e7e34;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
                font-weight: 500;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 6px;
                white-space: nowrap;
                box-shadow: 0 1px 2px rgba(0,0,0,0.2);
                height: 28px;
                box-sizing: border-box;
            " onmouseover="this.style.background='#34ce57'; this.style.borderColor='#155724'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.25)'" 
               onmouseout="this.style.background='#28a745'; this.style.borderColor='#1e7e34'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.2)'">
                ↻ Verificar
            </button>
            
            <button id="session-info-btn" onclick="window.sessionManager?.showSystemInfo()" style="
                padding: 6px 12px;
                background: #0d6efd;
                color: #ffffff;
                border: 1px solid #0a58ca;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
                font-weight: 500;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 6px;
                white-space: nowrap;
                box-shadow: 0 1px 2px rgba(0,0,0,0.2);
                height: 28px;
                box-sizing: border-box;
            " onmouseover="this.style.background='#3d81ff'; this.style.borderColor='#084298'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.25)'" 
               onmouseout="this.style.background='#0d6efd'; this.style.borderColor='#0a58ca'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.2)'">
                ⓘ Sistema
            </button>
            
            <button id="session-logout-btn" onclick="window.sessionManager?.logout()" style="
                padding: 6px 12px;
                background: #dc3545;
                color: #ffffff;
                border: 1px solid #b02a37;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
                font-weight: 500;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 6px;
                white-space: nowrap;
                box-shadow: 0 1px 2px rgba(0,0,0,0.2);
                height: 28px;
                box-sizing: border-box;
            " onmouseover="this.style.background='#e85663'; this.style.borderColor='#a21e2e'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.25)'" 
               onmouseout="this.style.background='#dc3545'; this.style.borderColor='#b02a37'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.2)'">
                ⎋ Logout
            </button>
            
            <!-- Botão de fechar discreto e elegante -->
            <button id="session-close-btn" onclick="hideSessionStatusBar()" title="Ocultar barra de status" style="
                /* Botão discreto translúcido com tooltip */
                padding: 6px !important;
                background: transparent !important;
                color: #6c757d !important;
                border: 1px solid transparent !important;
                border-radius: 3px !important;
                cursor: pointer !important;
                font-size: 14px !important;
                font-weight: normal !important;
                line-height: 1 !important;
                width: 24px !important;
                height: 24px !important;
                min-width: 24px !important;
                min-height: 24px !important;
                max-width: 24px !important;
                max-height: 24px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                text-align: center !important;
                box-sizing: border-box !important;
                position: relative !important;
                z-index: 999999 !important;
                visibility: visible !important;
                opacity: 0.7 !important;
                flex-shrink: 0 !important;
                flex-grow: 0 !important;
                margin-left: 8px !important;
                transition: all 0.2s ease !important;
                -webkit-appearance: none !important;
                -moz-appearance: none !important;
                appearance: none !important;
                overflow: visible !important;
                user-select: none !important;
                -webkit-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                outline: none !important;
                white-space: nowrap !important;
                text-shadow: none !important;
            " onmouseover="this.style.background='rgba(108, 117, 125, 0.1)'; this.style.borderColor='rgba(108, 117, 125, 0.3)'; this.style.opacity='1'; this.style.color='#495057'" 
               onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'; this.style.opacity='0.7'; this.style.color='#6c757d'">
                ×
            </button>
        </div>
    </div>
</div>

<!-- Botão discreto para reabrir a barra - Safari compatible -->
<div id="session-status-reopen-btn" style="
    position: fixed !important;
    top: 20px !important;
    right: 20px !important;
    z-index: 999999 !important;
    display: none !important;
    padding: 10px 16px !important;
    background: linear-gradient(135deg, #007acc, #0056b3) !important;
    color: white !important;
    border: 2px solid #ffffff !important;
    border-radius: 25px !important;
    cursor: pointer !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    box-shadow: 0 4px 12px rgba(0,122,204,0.4) !important;
    transition: all 0.3s ease !important;
    backdrop-filter: blur(10px) !important;
    -webkit-backdrop-filter: blur(10px) !important;
    user-select: none !important;
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    box-sizing: border-box !important;
    min-width: 80px !important;
    max-width: 200px !important;
    text-align: center !important;
    white-space: nowrap !important;
    overflow: visible !important;
    line-height: 1.2 !important;
    visibility: hidden !important;
    opacity: 0 !important;
    transform: translateY(-20px) scale(0.8) !important;
" onclick="showSessionStatusBar()" 
   onmouseover="this.style.transform='scale(1.1) translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(0,122,204,0.5)'" 
   onmouseout="this.style.transform='scale(1) translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,122,204,0.4)'">
    📊 Status
</div>

<!-- Adicionando media queries para responsividade e visibilidade do botão -->
<style>
/* Estilo colorido vibrante para os botões principais com fundos sólidos */
#session-refresh-btn {
    background: #28a745 !important;
    color: #ffffff !important;
    border: 1px solid #198754 !important;
    border-radius: 3px !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.2) !important;
    text-shadow: 0 1px 0 rgba(0,0,0,0.2) !important;
    height: 28px !important;
    padding: 6px 12px !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    box-sizing: border-box !important;
    transition: all 0.2s ease !important;
}

#session-refresh-btn:hover {
    background: #34ce57 !important;
    border-color: #146c43 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.25) !important;
}

#session-info-btn {
    background: #0d6efd !important;
    color: #ffffff !important;
    border: 1px solid #0d6efd !important;
    border-radius: 3px !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.2) !important;
    text-shadow: 0 1px 0 rgba(0,0,0,0.2) !important;
    height: 28px !important;
    padding: 6px 12px !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    box-sizing: border-box !important;
    transition: all 0.2s ease !important;
}

#session-info-btn:hover {
    background: #3d81ff !important;
    border-color: #084298 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.25) !important;
}

#session-logout-btn {
    background: #dc3545 !important;
    color: #ffffff !important;
    border: 1px solid #c82333 !important;
    border-radius: 3px !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.2) !important;
    text-shadow: 0 1px 0 rgba(0,0,0,0.2) !important;
    height: 28px !important;
    padding: 6px 12px !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    box-sizing: border-box !important;
    transition: all 0.2s ease !important;
}

#session-logout-btn:hover {
    background: #e85663 !important;
    border-color: #a21e2e !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.25) !important;
}

/* Botão de fechar discreto */
#session-close-btn {
    background: transparent !important;
    color: #6c757d !important;
    border: 1px solid transparent !important;
    border-radius: 3px !important;
    width: 24px !important;
    height: 24px !important;
    min-width: 24px !important;
    min-height: 24px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
    flex-grow: 0 !important;
    position: relative !important;
    z-index: 999999 !important;
    visibility: visible !important;
    opacity: 0.7 !important;
    margin-left: 8px !important;
    cursor: pointer !important;
    font-size: 14px !important;
    font-weight: normal !important;
    line-height: 1 !important;
    box-sizing: border-box !important;
    outline: none !important;
    transition: all 0.2s ease !important;
}

#session-close-btn:hover {
    background: rgba(108, 117, 125, 0.1) !important;
    border-color: rgba(108, 117, 125, 0.3) !important;
    opacity: 1 !important;
    color: #495057 !important;
}

/* Container dos controles - alinhamento vertical perfeito */
#session-status-bar > div > div:last-child {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    flex-wrap: nowrap !important;
    flex-shrink: 0 !important;
    min-width: auto !important;
    overflow: visible !important;
    height: 36px !important;
    box-sizing: border-box !important;
}

/* Responsividade para telas menores */
@media (max-width: 1200px) {
    #session-status-bar > div {
        padding: 0 15px !important;
        gap: 10px !important;
    }
    
    #session-refresh-btn, #session-info-btn, #session-logout-btn {
        padding: 5px 10px !important;
        font-size: 11px !important;
        height: 26px !important;
    }
    
    #session-close-btn {
        width: 22px !important;
        height: 22px !important;
        min-width: 22px !important;
        min-height: 22px !important;
        font-size: 13px !important;
    }
}

@media (max-width: 768px) {
    #session-status-bar > div {
        padding: 0 10px !important;
        gap: 8px !important;
    }
    
    #session-refresh-btn, #session-info-btn, #session-logout-btn {
        padding: 4px 8px !important;
        font-size: 10px !important;
        height: 22px !important;
        gap: 4px !important;
    }
    
    #session-close-btn {
        width: 20px !important;
        height: 20px !important;
        min-width: 20px !important;
        min-height: 20px !important;
        font-size: 12px !important;
        margin-left: 6px !important;
    }
}

@media (max-width: 480px) {
    #session-status-bar > div {
        padding: 0 8px !important;
        gap: 6px !important;
    }
    
    #session-refresh-btn, #session-info-btn, #session-logout-btn {
        padding: 3px 6px !important;
        font-size: 9px !important;
        height: 20px !important;
        gap: 3px !important;
    }
    
    #session-close-btn {
        width: 18px !important;
        height: 18px !important;
        min-width: 18px !important;
        min-height: 18px !important;
        font-size: 11px !important;
        margin-left: 4px !important;
    }
}

/* Para telas muito grandes - garante que o botão não seja empurrado para fora */
@media (min-width: 1600px) {
    #session-status-bar > div {
        max-width: 1400px !important;
        margin: 0 auto !important;
        padding: 0 20px !important;
    }
}
</style>

<script>
// Funções globais para controlar a barra de status
window.sessionStatusBarAPI = {
    show: function() {
        const bar = document.getElementById('session-status-bar');
        const reopenBtn = document.getElementById('session-status-reopen-btn');
        if (bar) {
            bar.style.display = 'block';
            // Ajusta o padding do body para compensar a barra fixa (altura reduzida)
            document.body.style.paddingTop = '48px';
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
    
    updateStatus: function(message, type = 'info') {
        const textElement = document.getElementById('session-status-text');
        const indicator = document.getElementById('session-status-indicator');
        
        if (textElement) {
            textElement.textContent = message;
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
    },
    
    updateUserInfo: function(userInfo) {
        const userInfoElement = document.getElementById('session-user-info');
        if (userInfoElement && userInfo) {
            let infoText = '';
            if (userInfo.name) {
                infoText += `👤 ${userInfo.name}`;
            }
            if (userInfo.checkins_count) {
                infoText += ` • 📍 ${userInfo.checkins_count} check-ins`;
            }
            
            if (infoText) {
                userInfoElement.textContent = infoText;
                userInfoElement.style.display = 'block';
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

// Inicialização automática
document.addEventListener('DOMContentLoaded', function() {
    // Sempre mostra a barra ao carregar/recarregar a página,
    // mas verifica se foi ocultada APENAS na sessão atual
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
    
    // Integração com SessionManager se disponível
    if (window.sessionManager) {
        // Override dos métodos do SessionManager para usar a barra unificada
        const originalUpdateStatus = window.sessionManager.updateStatus;
        window.sessionManager.updateStatus = function(message, type) {
            window.sessionStatusBarAPI.updateStatus(message, type);
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
});

// CSS adicional para responsividade
const style = document.createElement('style');
style.textContent = `
@media (max-width: 768px) {
    #session-status-bar > div {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px !important;
        padding: 8px 15px !important;
    }
    
    #session-status-bar > div > div:last-child {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
        justify-content: center !important;
    }
    
    #session-status-bar button {
        font-size: 11px !important;
        padding: 5px 10px !important;
        flex: 0 0 auto !important;
        min-width: 24px !important;
    }
    
    #session-close-btn {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    #session-user-info {
        text-align: center !important;
        margin-top: 5px !important;
    }
    
    #session-status-reopen-btn {
        top: 5px !important;
        right: 5px !important;
        font-size: 11px !important;
        padding: 6px 10px !important;
    }
}

@media (min-width: 1400px) {
    #session-status-bar > div {
        padding: 0 40px !important;
    }
}

@media (min-width: 1920px) {
    #session-status-bar > div {
        padding: 0 60px !important;
    }
}

#session-status-bar button:active {
    transform: translateY(1px) !important;
}

#session-status-reopen-btn:active {
    transform: scale(0.95) translateY(1px) !important;
}

#session-status-bar {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Garantir visibilidade do botão de fechar */
#session-close-btn {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    z-index: 10001 !important;
}

/* Animação suave para o botão de reabrir */
#session-status-reopen-btn {
    animation: slideInFromTop 0.5s ease-out;
}

@keyframes slideInFromTop {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

#session-status-reopen-btn {
    right: 24px !important;
    top: 18px !important;
    /* Evita corte em Safari, Chrome, Firefox */
    margin-right: 0 !important;
    margin-top: 0 !important;
    box-sizing: border-box !important;
    max-width: 160px;
    min-width: 60px;
    z-index: 10001 !important;
}
@media (max-width: 768px) {
    #session-status-reopen-btn {
        right: 12px !important;
        top: 12px !important;
        font-size: 11px !important;
        padding: 6px 10px !important;
        min-width: 48px !important;
        max-width: 120px !important;
    }
}
`;
document.head.appendChild(style);
</script>
