/**
 * Gerenciador de Sessão para Foursquare Mass Editor Tools
 * 
 * Monitora o status da sessão e fornece funcionalidades de autenticação
 * Integrado com a barra de status unificada
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

    showStatus(message, type = 'info', duration = 3000) {
        this.updateStatus(message, type);
        
        // Auto-hide após duração especificada apenas para mensagens temporárias
        if (duration > 0 && type !== 'error') {
            setTimeout(() => {
                this.updateStatus('Sessão monitorada', 'success');
            }, duration);
        }
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
            this.updateStatus('Verificando sessão...', 'loading');
        }

        try {
            // Usa sempre XMLHttpRequest para maior compatibilidade
            const data = await this.makeXhrRequest('session_status.php');
            
            if (data.status === 'valid' && data.authenticated) {
                this.userData = data.user || null;
                this.updateStatus(`Conectado como ${data.user?.name || 'usuário'}`, 'success');
                
                // Atualiza informações do usuário na barra
                if (window.sessionStatusBarAPI && data.user) {
                    window.sessionStatusBarAPI.updateUserInfo(data.user);
                }
                
                if (manual) {
                    this.showStatus('✅ Sessão válida!', 'success', 3000);
                }
                
                return true;
                
            } else if (data.status === 'expired' || data.status === 'error') {
                this.updateStatus('Token expirado - necessário fazer login', 'error');
                
                if (manual) {
                    this.showTokenExpiredDialog();
                } else {
                    // Redireciona automaticamente se for verificação em background
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 5000);
                }
                
                return false;
            }
            
        } catch (error) {
            console.error('Erro ao verificar sessão:', error);
            this.updateStatus('Erro na verificação de sessão', 'error');
            
            if (manual) {
                this.showStatus('❌ Erro na verificação', 'error', 5000);
            }
            
            return false;
        } finally {
            this.isChecking = false;
        }
    }

    async makeXhrRequest(url, options = {}) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const method = options.method || 'GET';
            
            xhr.open(method, url);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.withCredentials = true;
            
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        resolve(data);
                    } catch (e) {
                        reject(new Error('Resposta inválida do servidor'));
                    }
                } else {
                    reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                }
            };
            
            xhr.onerror = function() {
                reject(new Error('Erro de conexão'));
            };
            
            xhr.send(options.body || null);
        });
    }

    showTokenExpiredDialog() {
        const clearCacheChoice = confirm(
            '🔒 Sua sessão expirou.\n\n' +
            'Isso pode ser causado por dados antigos armazenados no cache.\n\n' +
            'Clique "OK" para limpar o cache e tentar novamente, ou "Cancelar" para ir direto ao login.'
        );
        
        if (clearCacheChoice) {
            // Tenta limpar cache primeiro
            this.forceClearCache();
        } else {
            // Vai direto para o login
            window.location.href = 'index.php';
        }
    }

    showSystemInfo() {
        // Coleta informações do sistema e ambiente
        const systemInfo = this.getSystemInfo();
        const info = [
            `🖥️ Navegador: ${systemInfo.browser}`,
            `📱 Plataforma: ${systemInfo.platform}`,
            `🌐 User Agent: ${systemInfo.userAgent}`,
            `📍 URL Atual: ${systemInfo.currentUrl}`,
            `🕐 Timestamp: ${systemInfo.timestamp}`,
            ``,
            `⚙️ Tecnologias do Sistema:`,
            `• PHP 8.1 (Backend modernizado)`,
            `• Dojo Toolkit v1.8.14`,
            `• JavaScript ES6`,
            `• Docker + Apache 2.4`,
            `• Composer PSR-4`,
            `• Foursquare API v2`,
            ``,
            `🔧 Funcionalidades:`,
            `• OAuth2 Authentication`,
            `• Session Management`,
            `• Bulk Venue Editing`,
            `• CSV Import/Export`,
            `• Google Maps Integration`,
            `• Real-time Status Monitoring`
        ].join('\n');
        
        alert(`ℹ️ Informações do Sistema:\n\n${info}`);
    }

    getSystemInfo() {
        const nav = navigator;
        const now = new Date();
        
        return {
            browser: this.detectBrowser(),
            platform: nav.platform || 'Unknown',
            userAgent: nav.userAgent,
            currentUrl: window.location.href,
            timestamp: now.toLocaleString('pt-BR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            }),
            screen: `${screen.width}x${screen.height}`,
            viewport: `${window.innerWidth}x${window.innerHeight}`,
            cookiesEnabled: nav.cookieEnabled,
            onlineStatus: nav.onLine ? 'Online' : 'Offline'
        };
    }

    detectBrowser() {
        const ua = navigator.userAgent;
        if (ua.includes('Chrome')) return 'Chrome';
        if (ua.includes('Firefox')) return 'Firefox';
        if (ua.includes('Safari')) return 'Safari';
        if (ua.includes('Edge')) return 'Edge';
        if (ua.includes('Opera')) return 'Opera';
        return 'Unknown';
    }

    logout() {
        const confirmLogout = confirm('🚪 Tem certeza que deseja fazer logout?');
        if (confirmLogout) {
            window.location.href = 'index.php?logout=1';
        }
    }

    startPeriodicCheck() {
        // Limpa intervalo anterior se existir
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
        
        // Inicia verificação periódica
        this.intervalId = setInterval(() => {
            this.checkSessionStatus(false);
        }, this.checkInterval);
        
        console.log('🔧 SessionManager: Verificação periódica iniciada');
    }

    stopPeriodicCheck() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
            console.log('🔧 SessionManager: Verificação periódica parada');
        }
    }

    bindEvents() {
        // Verifica sessão quando a página ganha foco
        window.addEventListener('focus', () => {
            // Só verifica se passou mais de 1 minuto desde a última verificação
            if (Date.now() - this.lastCheck > 60000) {
                this.checkSessionStatus(false);
            }
        });

        // Para a verificação quando a página perde foco (otimização)
        window.addEventListener('blur', () => {
            // Não para completamente, mas reduz a frequência
        });

        // Limpa recursos quando a página é descarregada
        window.addEventListener('beforeunload', () => {
            this.stopPeriodicCheck();
        });
    }

    // Método para forçar atualização do status
    forceRefresh() {
        this.checkSessionStatus(true);
    }

    // Getter para verificar se está autenticado
    get isAuthenticated() {
        return this.userData !== null;
    }

    // Getter para dados do usuário
    get userInfo() {
        return this.userData;
    }

    async forceClearCache() {
        console.log('🧹 SessionManager: Forçando limpeza de cache...');
        
        try {
            // Chama endpoint de limpeza de cache
            const response = await fetch('clear_cache.php', {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-cache'
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                console.log('✅ Cache limpo com sucesso');
                
                // Limpa localStorage
                try {
                    localStorage.clear();
                } catch (e) {
                    console.warn('⚠️ Não foi possível limpar localStorage:', e);
                }
                
                // Limpa sessionStorage
                try {
                    sessionStorage.clear();
                } catch (e) {
                    console.warn('⚠️ Não foi possível limpar sessionStorage:', e);
                }
                
                this.updateStatus('Cache limpo - recarregando página...', 'success');
                
                // Recarrega a página após 1 segundo
                setTimeout(() => {
                    window.location.reload(true);
                }, 1000);
                
                return true;
            } else {
                console.error('❌ Erro ao limpar cache:', data.message);
                return false;
            }
            
        } catch (error) {
            console.error('❌ Erro na limpeza de cache:', error);
            return false;
        }
    }
}

// Inicialização automática quando o DOM estiver pronto
if (typeof window !== 'undefined') {
    // Aguarda o DOM estar pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.sessionManager = new SessionManager();
        });
    } else {
        // DOM já está pronto
        window.sessionManager = new SessionManager();
    }
}

// Exporta para uso em outros contextos se necessário
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SessionManager;
}
