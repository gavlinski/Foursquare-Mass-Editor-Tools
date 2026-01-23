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
<div id="session-status-bar">
    <div id="session-status-container">
        <!-- Status e Informações -->
        <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
            <div id="session-status-indicator"></div>
            
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <strong style="color: #495057; font-size: 12px;">Status:</strong>
                    <span id="session-status-text" style="color: #6c757d; font-size: 12px;">Verificando...</span>
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
            
            <button id="session-debug-btn" class="session-btn" style="display: none;" onclick="window.location.href='/debug/'">
                ⚙ Debug
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
    
    updateStatus: function(message, type = 'info') {
        const textElement = document.getElementById('session-status-text');
        const indicator = document.getElementById('session-status-indicator');
        
        if (textElement) {
            // Armazena mensagem base para uso com timer
            textElement.dataset.baseMessage = message;
            
            // Se o timer estiver ativo, adiciona o tempo
            if (this.sessionStartTime) {
                const elapsed = Date.now() - this.sessionStartTime;
                const minutes = Math.floor(elapsed / 60000);
                const seconds = Math.floor((elapsed % 60000) / 1000);
                const formatted = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                textElement.textContent = `${message} (Sessão: ${formatted})`;
            } else {
                textElement.textContent = message;
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
        
        // Inicia o timer se o tipo for success (sessão conectada)
        if (type === 'success' && !this.sessionStartTime) {
            this.startSessionTimer();
        }
    },
    
    updateUserInfo: function(userInfo) {
        const userInfoElement = document.getElementById('session-user-info');
        if (userInfoElement && userInfo) {
            let infoHTML = '';
            
            // Nome com link para perfil usando canonicalUrl
            if (userInfo.name) {
                if (userInfo.canonicalUrl) {
                    infoHTML += `👤 <a href="${userInfo.canonicalUrl}" target="_blank" style="color: #6c757d; text-decoration: none; font-size: 12px;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${userInfo.name}</a>`;
                } else if (userInfo.id) {
                    // Fallback para ID se canonicalUrl não estiver disponível
                    const profileUrl = `https://app.foursquare.com/user/${userInfo.id}`;
                    infoHTML += `👤 <a href="${profileUrl}" target="_blank" style="color: #6c757d; text-decoration: none; font-size: 12px;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${userInfo.name}</a>`;
                } else {
                    infoHTML += `👤 <span style="font-size: 12px;">${userInfo.name}</span>`;
                }
            }
            
            // Placemaker Level (superuser)
            if (userInfo.superuser_level && userInfo.superuser_level > 0) {
                infoHTML += ` • <span style="font-size: 12px;">🏅 Placemaker Level ${userInfo.superuser_level}</span>`;
            }
            
            if (infoHTML) {
                userInfoElement.innerHTML = infoHTML;
                userInfoElement.style.display = 'block';
            }
            
            // Mostra botão Debug se superuser >= 8
            const debugBtn = document.getElementById('session-debug-btn');
            if (debugBtn && userInfo.superuser_level >= 8) {
                debugBtn.style.display = 'inline-block';
            } else if (debugBtn) {
                debugBtn.style.display = 'none';
            }
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
        
        const elapsed = Date.now() - this.sessionStartTime;
        const minutes = Math.floor(elapsed / 60000);
        const seconds = Math.floor((elapsed % 60000) / 1000);
        const formatted = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        // Atualiza o texto de status se existir
        const statusText = document.getElementById('session-status-text');
        if (statusText && statusText.dataset.baseMessage) {
            statusText.textContent = `${statusText.dataset.baseMessage} (Sessão: ${formatted})`;
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
