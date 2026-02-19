/**
 * Gerenciador de Sessão para Foursquare Mass Editor Tools
 * 
 * Monitora o status da sessão e fornece funcionalidades de autenticação
 * Integrado com a barra de status unificada
 */

class SessionManager {
    constructor() {
        this.checkInterval = 2 * 60 * 1000; // 2 minutos (reduzido para melhor detecção)
        this.intervalId = null;
        this.isChecking = false;
        this.lastCheck = 0;
        this.userData = null;
        this.serverInstanceId = null;
        this.restartDetected = false;
        
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
        
        // Auto-hide apenas para mensagens não-permanentes (info/loading)
        if (duration > 0 && (type === 'info' || type === 'loading')) {
            setTimeout(() => {
                this.updateStatus('Conectado', 'success');
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
            // Usa sempre XMLHttpRequest com timestamp para evitar cache
            const cacheBuster = Date.now();
            const data = await this.makeXhrRequest(`session_status.php?_=${cacheBuster}`);
            
            console.log('🔍 SessionManager: Resposta recebida:', data);
            
            if (data.status === 'valid' && data.authenticated) {
                // Verifica se o servidor foi reiniciado
                if (data.server_instance_id) {
                    const storedInstanceId = localStorage.getItem('server_instance_id');
                    
                    if (storedInstanceId && storedInstanceId !== data.server_instance_id) {
                        // Servidor foi reiniciado!
                        console.warn('⚠️ Restart do servidor detectado!');
                        this.restartDetected = true;
                        this.showServerRestartWarning();
                    }
                    
                    // Atualiza o ID armazenado
                    localStorage.setItem('server_instance_id', data.server_instance_id);
                    this.serverInstanceId = data.server_instance_id;
                }
                
                this.userData = data.user || null;
                
                // Atualiza informações do usuário na barra
                if (window.sessionStatusBarAPI && data.user) {
                    window.sessionStatusBarAPI.updateUserInfo(data.user);
                }
                
                if (manual) {
                    // Verificação manual - mostra mensagem temporária
                    this.showStatus('✅ Sessão válida!', 'success', 3000);
                } else {
                    // Verificação automática - mantém status conectado
                    this.updateStatus('Conectado', 'success');
                }
                
                return true;
                
            } else if (data.status === 'expired' || !data.authenticated) {
                // Token expirado ou sessão inválida
                console.warn('⚠️ SessionManager: Sessão expirada ou inválida');
                console.warn('⚠️ Status:', data.status, '| Authenticated:', data.authenticated);
                
                // Limpa dados locais
                this.userData = null;
                localStorage.removeItem('server_instance_id');
                
                // Para a verificação periódica
                this.stopPeriodicCheck();
                
                // Atualiza barra de status
                this.updateStatus('Sessão expirada', 'error');
                
                if (manual) {
                    this.showTokenExpiredDialog();
                } else {
                    // Verificação automática - mostra aviso e redireciona
                    console.warn('⚠️ Sessão expirada - redirecionando para login em 5s');
                    
                    // Mostra alerta se houver barra de status
                    if (window.sessionStatusBarAPI) {
                        window.sessionStatusBarAPI.updateStatus('⚠️ Sessão expirada - redirecionando...', 'error');
                    }
                    
                    setTimeout(() => {
                        window.location.href = data.redirect_url || 'index.php';
                    }, 5000);
                }
                
                return false;
                
            } else if (data.status === 'error') {
                // Erro genérico
                console.error('❌ SessionManager: Erro no servidor:', data.message);
                this.updateStatus('Erro de sessão', 'error');
                
                if (manual) {
                    alert('❌ Erro ao verificar sessão.\n\nTente fazer login novamente.');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                }
                
                return false;
            } else {
                // Status desconhecido
                console.error('❌ SessionManager: Status desconhecido:', data);
                this.updateStatus('Status desconhecido', 'warning');
                return false;
            }
            
        } catch (error) {
            console.error('Erro ao verificar sessão:', error);
            
            // Diferencia erro de conexão de outros erros
            const isNetworkError = error.message && 
                (error.message.includes('conexão') || 
                 error.message.includes('Network') ||
                 error.message.includes('Failed to fetch'));
            
            if (isNetworkError) {
                // Erro de rede - não mostra timer
                if (window.sessionStatusBarAPI) {
                    window.sessionStatusBarAPI.updateStatus('Sem conexão', 'warning', false);
                } else {
                    console.warn('⚠️ Sem conexão - verificação pausada');
                }
                
                if (manual) {
                    alert('⚠️ Sem conexão com o servidor.\n\nVerifique sua conexão de rede.');
                }
            } else {
                // Erro desconhecido
                this.updateStatus('Erro na verificação', 'error');
                
                if (manual) {
                    this.showStatus('❌ Erro na verificação', 'error', 5000);
                }
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
            xhr.setRequestHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
            xhr.setRequestHeader('Pragma', 'no-cache');
            xhr.setRequestHeader('Expires', '0');
            xhr.withCredentials = true;
            
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        console.log('📥 XHR Response:', {
                            url: url,
                            status: xhr.status,
                            data: data
                        });
                        resolve(data);
                    } catch (e) {
                        console.error('❌ Erro ao parsear resposta:', e);
                        console.error('❌ Response text:', xhr.responseText);
                        reject(new Error('Resposta inválida do servidor'));
                    }
                } else {
                    console.error('❌ HTTP Error:', xhr.status, xhr.statusText);
                    reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                }
            };
            
            xhr.onerror = function() {
                console.error('❌ XHR Network Error');
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

    showServerRestartWarning() {
        // Mostra aviso visual persistente
        this.updateStatus('⚠️ Servidor reiniciado - recarregue a página!', 'warning');
        
        // Mostra dialog modal amigável
        const message = [
            '⚠️ Servidor Reiniciado',
            '',
            'O servidor foi reiniciado.',
            'Por favor, recarregue a página para evitar perda de dados.',
            '',
            'Recarregar agora?'
        ].join('\n');
        
        const shouldReload = confirm(message);
        
        if (shouldReload) {
            window.location.reload();
        } else {
            // Se o usuário não quiser recarregar, marca como detectado
            // para não mostrar o aviso novamente nesta sessão
            console.warn('⚠️ Usuário optou por não recarregar após restart do servidor');
            
            // Adiciona listener para interceptar ações perigosas
            this.addRestartInterceptionListeners();
        }
    }

    addRestartInterceptionListeners() {
        // Intercepta submits de formulários
        document.addEventListener('submit', (e) => {
            if (this.restartDetected) {
                e.preventDefault();
                const confirmAction = confirm(
                    '⚠️ ATENÇÃO: Servidor foi reiniciado!\n\n' +
                    'Continuar sem recarregar a página pode causar perda de dados.\n\n' +
                    'Deseja recarregar agora?'
                );
                if (confirmAction) {
                    window.location.reload();
                }
            }
        }, true);

        // Intercepta cliques em botões de ação
        document.addEventListener('click', (e) => {
            const target = e.target;
            if (this.restartDetected && 
                (target.tagName === 'BUTTON' || target.classList.contains('action-button'))) {
                
                // Verifica se não é um botão de reload
                if (!target.classList.contains('reload-safe')) {
                    const confirmAction = confirm(
                        '⚠️ Servidor reiniciado!\n\n' +
                        'Recarregue a página antes de executar ações.\n\n' +
                        'Recarregar agora?'
                    );
                    if (confirmAction) {
                        e.preventDefault();
                        window.location.reload();
                    }
                }
            }
        }, true);
    }

    startPeriodicCheck() {
        // Limpa intervalo anterior se existir
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
        
        // Inicia verificação periódica
        this.intervalId = setInterval(() => {
            console.log('⏰ Verificação periódica automática iniciada');
            this.checkSessionStatus(false);
        }, this.checkInterval);
        
        console.log(`🔧 SessionManager: Verificação periódica iniciada a cada ${this.checkInterval / 60000} minutos`);
        console.log(`🔧 Próxima verificação em: ${new Date(Date.now() + this.checkInterval).toLocaleTimeString()}`);
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
            // Verifica imediatamente quando volta ao foco
            console.log('🔍 Página ganhou foco - verificando sessão');
            this.checkSessionStatus(false);
        });

        // Para a verificação quando a página perde foco (otimização)
        window.addEventListener('blur', () => {
            // Não para completamente, mas reduz a frequência
        });

        // Limpa recursos quando a página é descarregada
        window.addEventListener('beforeunload', () => {
            this.stopPeriodicCheck();
        });
        
        // Detecta mudanças de visibilidade da página
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                console.log('🔍 Página ficou visível - verificando sessão');
                this.checkSessionStatus(false);
            }
        });
        
        // Monitora mudanças em localStorage para detectar logout em outras abas
        window.addEventListener('storage', (e) => {
            if (e.key === 'session_logout' || e.key === 'server_instance_id') {
                console.log('🔍 Mudança em storage detectada - verificando sessão');
                this.checkSessionStatus(false);
            }
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

    logout() {
        console.log('👋 SessionManager: Iniciando logout...');
        
        // Sinaliza logout para outras abas
        try {
            localStorage.setItem('session_logout', Date.now().toString());
        } catch (e) {
            console.warn('⚠️ Erro ao sinalizar logout:', e);
        }
        
        // Limpa localStorage
        try {
            localStorage.removeItem('server_instance_id');
            console.log('✅ localStorage limpo');
        } catch (e) {
            console.warn('⚠️ Erro ao limpar localStorage:', e);
        }
        
        // Limpa sessionStorage
        try {
            sessionStorage.clear();
            console.log('✅ sessionStorage limpo');
        } catch (e) {
            console.warn('⚠️ Erro ao limpar sessionStorage:', e);
        }
        
        // Limpa cookies acessíveis via JS (opcional, pois o servidor deve limpar os HttpOnly)
        document.cookie.split(";").forEach((c) => {
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
        });

        // Redireciona para o endpoint de logout
        window.location.href = 'index.php?logout=true';
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
