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
        <div>
            <button id="session-refresh-btn" class="session-btn" onclick="window.sessionManager?.checkSessionStatus(true)">
                ↻ Verificar
            </button>
            
            <button id="session-info-btn" class="session-btn" onclick="window.sessionManager?.showSystemInfo()">
                ⓘ Sistema
            </button>
            
            <button id="session-logout-btn" class="session-btn" onclick="window.sessionManager?.logout()">
                ⎋ Logout
            </button>

            <button id="session-close-btn" onclick="hideSessionStatusBar()" title="Ocultar barra de status">
                ×
            </button>
        </div>
    </div>
</div>

<!-- Botão discreto para reabrir a barra -->
<div id="session-status-reopen-btn" onclick="showSessionStatusBar()">
    📊 Status
</div>

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
</script>
