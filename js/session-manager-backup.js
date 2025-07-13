/**
 * Gerenciador de Sessão para Foursquare Mass Editor Tools
 * 
 * Monitora o status da sessão e fornece funcionalidades de autenticação
 */

class SessionManager {
    constructor() {
        this.checkInterval = 5 * 60 * 1000; // 5 minutos
        this.intervalId = null;
        this.isChecking = false;
        this.lastCheck = 0;
        this.userData = null;
        
        // Aguarda um pequeno delay para garantir que o DOM está pronto
        setTimeout(() => {
            this.init();
        }, 100);
    }

    init() {
        try {
            // Não cria barra própria, usa a barra unificada
            this.checkSessionStatus();
            this.startPeriodicCheck();
            this.bindEvents();
        } catch (error) {
            console.error('SessionManager: Erro na inicialização:', error);
        }
    }

    updateStatus(message, type = 'info') {
        console.log(`🔧 SessionManager: ${message}`);
        
        // Usa a barra de status unificada se disponível
        if (window.sessionStatusBarAPI) {
            window.sessionStatusBarAPI.updateStatus(message, type);
            return;
        }
        
        // Fallback para console se não houver barra
        const emoji = {
            'success': '✅',
            'error': '❌', 
            'warning': '⚠️',
            'info': 'ℹ️',
            'loading': '🔄'
        };
        
        console.log(`${emoji[type] || emoji.info} ${message}`);
    }
            display: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        `;
        document.body.insertBefore(statusBar, document.body.firstChild);

        // Adiciona estilos CSS dinamicamente
        const style = document.createElement('style');
        style.textContent = `
            .session-controls {
                position: fixed;
                top: 10px;
                right: 10px;
                z-index: 10000;
                display: flex;
                gap: 8px;
            }
            .session-btn {
                padding: 8px 12px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                transition: background-color 0.3s;
            }
            .session-btn:hover {
                opacity: 0.8;
            }
            .btn-check {
                background: #007cba;
                color: white;
            }
            .btn-logout {
                background: #dc3545;
                color: white;
            }
            .btn-info {
                background: #17a2b8;
                color: white;
            }
            body {
                padding-top: 50px;
            }
        `;
        document.head.appendChild(style);

        // Container para os controles
        if (document.querySelector('.session-controls')) {
            return;
        }
        
        const controlsContainer = document.createElement('div');
        controlsContainer.className = 'session-controls';

        // Botão de verificação manual
        const checkButton = document.createElement('button');
        checkButton.id = 'check-session-btn';
        checkButton.className = 'session-btn btn-check';
        checkButton.textContent = '� Verificar';
        checkButton.onclick = () => this.checkSessionStatus(true);
        controlsContainer.appendChild(checkButton);

        // Botão de informações do usuário
        const infoButton = document.createElement('button');
        infoButton.id = 'user-info-btn';
        infoButton.className = 'session-btn btn-info';
        infoButton.textContent = '👤 Info';
        infoButton.onclick = () => this.showUserInfo();
        controlsContainer.appendChild(infoButton);

        // Botão de logout
        const logoutButton = document.createElement('button');
        logoutButton.id = 'logout-btn';
        logoutButton.className = 'session-btn btn-logout';
        logoutButton.textContent = '🚪 Logout';
        logoutButton.onclick = () => this.logout();
        controlsContainer.appendChild(logoutButton);

        document.body.appendChild(controlsContainer);
    }

    async checkSessionStatus(manual = false) {
        if (this.isChecking && !manual) return;
        
        // Verifica se estamos em um contexto válido
        if (typeof window === 'undefined') {
            console.error('window não está disponível');
            return;
        }
        
        this.isChecking = true;
        this.lastCheck = Date.now();
        
        if (manual) {
            this.showStatus('🔄 Verificando sessão...', 'info', 0); // 0 = não remove automaticamente
        }

        try {
            // Usa sempre XMLHttpRequest para maior compatibilidade
            const data = await this.makeXhrRequest('session_status.php');
            this.userData = data.user || null;

            switch (data.status) {
                case 'valid':
                    if (manual) {
                        this.showStatus(`✅ Sessão válida - ${data.user.name} (${data.user.checkins_count} check-ins)`, 'success', 5000);
                    } else {
                        this.hideStatus();
                    }
                    this.updateLastCheckInfo();
                    break;

                case 'expired':
                    this.showStatus(
                        `⚠️ Sessão expirada! <a href="#" onclick="sessionManager.reAuthenticate()" style="color: white; text-decoration: underline; font-weight: bold;">Clique aqui para fazer login novamente</a>`,
                        'warning'
                    );
                    break;

                case 'error':
                default:
                    this.showStatus(`❌ Erro ao verificar sessão: ${data.message}`, 'error');
                    break;
            }

        } catch (error) {
            console.error('Erro ao verificar status da sessão:', error);
            console.error('Tipo do erro:', error.constructor.name);
            console.error('Mensagem do erro:', error.message);
            console.error('Stack trace:', error.stack);
            if (manual) {
                this.showStatus(`❌ Erro de conexão ao verificar sessão: ${error.message}`, 'error', 10000);
            }
        } finally {
            this.isChecking = false;
        }
    }

    showUserInfo() {
        if (!this.userData) {
            this.checkSessionStatus(true);
            return;
        }

        const info = `
            👤 Usuário: ${this.userData.name}
            🆔 ID: ${this.userData.id}
            📍 Check-ins: ${this.userData.checkins_count}
            ⏰ Última verificação: ${new Date(this.lastCheck).toLocaleTimeString()}
        `;

        alert(info);
    }

    updateLastCheckInfo() {
        const checkBtn = document.getElementById('check-session-btn');
        if (checkBtn) {
            const time = new Date().toLocaleTimeString();
            checkBtn.title = `Última verificação: ${time}`;
        }
    }

    showStatus(message, type, autoHide = 0) {
        const statusBar = document.getElementById('session-status-bar');
        const colors = {
            success: '#28a745',
            warning: '#ffc107',
            error: '#dc3545',
            info: '#17a2b8'
        };

        statusBar.innerHTML = message;
        statusBar.style.backgroundColor = colors[type] || colors.info;
        statusBar.style.color = type === 'warning' ? '#000' : '#fff';
        statusBar.style.display = 'block';

        if (autoHide > 0) {
            setTimeout(() => this.hideStatus(), autoHide);
        }
    }

    hideStatus() {
        const statusBar = document.getElementById('session-status-bar');
        statusBar.style.display = 'none';
    }

    startPeriodicCheck() {
        this.stopPeriodicCheck();
        this.intervalId = setInterval(() => {
            this.checkSessionStatus();
        }, this.checkInterval);
    }

    stopPeriodicCheck() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    reAuthenticate() {
        // Limpa os dados locais e redireciona para autenticação
        this.clearLocalData();
        window.location.href = 'index.php';
    }

    logout() {
        if (confirm('Tem certeza que deseja fazer logout?')) {
            this.clearLocalData();
            window.location.href = 'index.php?logout=1';
        }
    }

    clearLocalData() {
        // Limpa cookies e dados locais
        document.cookie = 'oauth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        document.cookie = 'name=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        document.cookie = 'coordinates=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        
        // Limpa localStorage se estiver sendo usado
        if (typeof(Storage) !== "undefined") {
            localStorage.removeItem('foursquare_token');
            localStorage.removeItem('user_data');
        }
    }

    makeXhrRequest(url) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', url);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.withCredentials = true;
            
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        resolve(data);
                    } catch (e) {
                        console.error('SessionManager: Erro ao fazer parse do JSON:', e);
                        console.error('SessionManager: Response text era:', xhr.responseText);
                        reject(new Error('Erro ao fazer parse do JSON: ' + e.message));
                    }
                } else {
                    console.error('SessionManager: HTTP Error:', xhr.status, xhr.statusText);
                    reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                }
            };
            
            xhr.onerror = function() {
                console.error('SessionManager: XHR onerror - Erro de rede');
                reject(new Error('Erro de rede'));
            };
            
            xhr.ontimeout = function() {
                console.error('SessionManager: XHR timeout');
                reject(new Error('Timeout da requisição'));
            };
            
            // Define timeout de 10 segundos
            xhr.timeout = 10000;
            
            xhr.send();
        });
    }

    bindEvents() {
        // Verifica sessão quando a página ganha foco
        window.addEventListener('focus', () => {
            this.checkSessionStatus();
        });

        // Verifica sessão antes de requisições AJAX importantes
        this.interceptAjaxRequests();
    }

    interceptAjaxRequests() {
        // Intercepta requisições fetch
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            try {
                const response = await originalFetch.apply(this, args);
                
                // Se receber 401, pode ser token expirado
                if (response.status === 401) {
                    this.checkSessionStatus(true);
                }
                
                return response;
            } catch (error) {
                console.error('Erro na requisição:', error);
                throw error;
            }
        };

        // Intercepta requisições XMLHttpRequest
        const originalXHR = window.XMLHttpRequest;
        window.XMLHttpRequest = function() {
            const xhr = new originalXHR();
            const originalSend = xhr.send;
            
            xhr.send = function(...args) {
                xhr.addEventListener('load', function() {
                    if (xhr.status === 401) {
                        window.sessionManager?.checkSessionStatus(true);
                    }
                });
                
                return originalSend.apply(this, args);
            };
            
            return xhr;
        };
    }
}

// Inicializa o gerenciador quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    // Evita múltiplas inicializações
    if (!window.sessionManager) {
        window.sessionManager = new SessionManager();
    }
});

// Fallback para casos onde DOMContentLoaded já foi disparado
if (document.readyState === 'loading') {
    // DOM ainda carregando, aguarda DOMContentLoaded
} else {
    // DOM já carregado, inicializa imediatamente
    if (!window.sessionManager) {
        window.sessionManager = new SessionManager();
    }
}

// Exporta para uso global
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SessionManager;
}
