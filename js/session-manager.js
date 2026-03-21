/**
 * Gerenciador de Sessão para Foursquare Mass Editor Tools
 * 
 * Monitora o status da sessão e fornece funcionalidades de autenticação
 * Integrado com a barra de status unificada
 */

// Utilitários de logging condicional (apenas em localhost)
const isLocalhost = () => {
    return window.location.hostname === 'localhost' || 
           window.location.hostname === '127.0.0.1' ||
           window.location.hostname === '[::1]';
};

const debugLog = (...args) => {
    if (isLocalhost()) console.log(...args);
};

const debugInfo = (...args) => {
    if (isLocalhost()) console.info(...args);
};

class SessionManager {
    constructor() {
        this.checkInterval = 2 * 60 * 1000; // 2 minutos (reduzido para melhor detecção)
        this.intervalId = null;
        this.isChecking = false;
        this.lastCheck = 0;
        this.userData = null;
        this.serverInstanceId = null;
        this.restartDetected = false;
        this.restartInterceptionBound = false;

        // Suprime um único aviso de restart após navegação pós-login (index.php -> main.php).
        try {
            const suppressFromServer = window.__FMET_SUPPRESS_RESTART_WARNING_ONCE__ === true;
            const suppressFromReferrer = /\/index\.php(?:[?#]|$)/i.test(document.referrer || '');

            if (suppressFromServer || suppressFromReferrer) {
                sessionStorage.setItem('fmet:suppress-restart-warning-once', '1');
            }
        } catch (error) {
            console.warn('⚠️ SessionManager: Não foi possível configurar supressão de aviso de restart:', error);
        }
        
        // Calcula delay inicial baseado em quanto tempo a página está carregada
        // Se a página acabou de carregar (< 2s), aguarda mais tempo para sessão se estabelecer
        const pageLoadTime = performance.now();
        const initialDelay = pageLoadTime < 2000 ? 2000 : 100;
        
        debugLog(`🔧 SessionManager: Inicialização com delay de ${initialDelay}ms (página carregada há ${Math.round(pageLoadTime)}ms)`);
        
        // Aguarda para garantir que DOM está pronto e sessão estabelecida
        setTimeout(() => {
            this.init();
        }, initialDelay);
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
        debugLog(`🔧 SessionManager: ${message}`);
        
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
        
        debugLog(`${emoji[type] || emoji.info} ${message}`);
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

    ensureDialogRoot() {
        let root = document.getElementById('session-manager-dialog-root');

        if (root) {
            return root;
        }

        root = document.createElement('div');
        root.id = 'session-manager-dialog-root';
        root.innerHTML = [
            '<div id="session-manager-dialog-backdrop" style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 2147483646; display: none; align-items: center; justify-content: center; padding: 16px;">',
            '<div id="session-manager-dialog" role="dialog" aria-modal="true" aria-labelledby="session-manager-dialog-title" style="width: min(520px, 100%); background: #ffffff; color: #1f2937; border-radius: 14px; box-shadow: 0 24px 80px rgba(15, 23, 42, 0.35); overflow: hidden;">',
            '<div style="padding: 18px 20px 10px; border-bottom: 1px solid #e5e7eb;">',
            '<h3 id="session-manager-dialog-title" style="margin: 0; font-size: 18px; line-height: 1.3;"></h3>',
            '</div>',
            '<div id="session-manager-dialog-message" style="padding: 16px 20px; font-size: 14px; line-height: 1.6; white-space: pre-line;"></div>',
            '<div style="padding: 14px 20px 20px; display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">',
            '<button type="button" id="session-manager-dialog-cancel" style="display: none; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; border-radius: 10px; padding: 10px 14px; font: inherit; cursor: pointer;">Cancelar</button>',
            '<button type="button" id="session-manager-dialog-confirm" style="border: 0; background: #2563eb; color: #ffffff; border-radius: 10px; padding: 10px 14px; font: inherit; cursor: pointer;">OK</button>',
            '</div>',
            '</div>',
            '</div>'
        ].join('');

        document.body.appendChild(root);

        return root;
    }

    showDialog({
        title,
        message,
        confirmText = 'OK',
        cancelText = '',
        confirmVariant = 'primary',
        dismissible = false
    }) {
        const root = this.ensureDialogRoot();
        const backdrop = root.querySelector('#session-manager-dialog-backdrop');
        const titleElement = root.querySelector('#session-manager-dialog-title');
        const messageElement = root.querySelector('#session-manager-dialog-message');
        const confirmButton = root.querySelector('#session-manager-dialog-confirm');
        const cancelButton = root.querySelector('#session-manager-dialog-cancel');

        titleElement.textContent = title;
        messageElement.textContent = message;
        confirmButton.textContent = confirmText;
        cancelButton.textContent = cancelText;
        cancelButton.style.display = cancelText ? 'inline-flex' : 'none';

        const confirmStyles = {
            primary: { background: '#2563eb', color: '#ffffff' },
            danger: { background: '#dc2626', color: '#ffffff' },
            warning: { background: '#d97706', color: '#ffffff' }
        };
        const currentStyle = confirmStyles[confirmVariant] || confirmStyles.primary;
        confirmButton.style.background = currentStyle.background;
        confirmButton.style.color = currentStyle.color;

        backdrop.style.display = 'flex';

        return new Promise((resolve) => {
            const cleanup = () => {
                backdrop.style.display = 'none';
                confirmButton.removeEventListener('click', handleConfirm);
                cancelButton.removeEventListener('click', handleCancel);
                backdrop.removeEventListener('click', handleBackdropClick);
                document.removeEventListener('keydown', handleKeydown);
            };

            const handleConfirm = () => {
                cleanup();
                resolve(true);
            };

            const handleCancel = () => {
                cleanup();
                resolve(false);
            };

            const handleBackdropClick = (event) => {
                if (dismissible && event.target === backdrop) {
                    handleCancel();
                }
            };

            const handleKeydown = (event) => {
                if (event.key === 'Escape' && cancelText) {
                    handleCancel();
                }
            };

            confirmButton.addEventListener('click', handleConfirm, { once: true });
            cancelButton.addEventListener('click', handleCancel, { once: true });
            backdrop.addEventListener('click', handleBackdropClick);
            document.addEventListener('keydown', handleKeydown);

            setTimeout(() => confirmButton.focus(), 0);
        });
    }

    async showInfoDialog(title, message, confirmText = 'OK') {
        await this.showDialog({
            title,
            message,
            confirmText,
            dismissible: true
        });
    }

    async handleCriticalSessionFailure(message, options = {}) {
        const title = options.title || 'Sessão indisponível';
        const redirectUrl = options.redirectUrl || 'index.php';

        this.updateStatus(message, 'error');
        await this.showInfoDialog(title, message, 'Ir para login');
        window.location.href = redirectUrl;
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
            
            debugLog('🔍 SessionManager: Resposta recebida:', data);
            
            if (data.status === 'valid' && data.authenticated) {
                // Verifica se o servidor foi reiniciado
                if (data.server_instance_id) {
                    const storedInstanceId = localStorage.getItem('server_instance_id');
                    
                    if (storedInstanceId && storedInstanceId !== data.server_instance_id) {
                        const suppressRestartWarningOnce = sessionStorage.getItem('fmet:suppress-restart-warning-once') === '1';

                        if (suppressRestartWarningOnce) {
                            sessionStorage.removeItem('fmet:suppress-restart-warning-once');
                            console.info('ℹ️ Aviso de restart suprimido no primeiro carregamento pós-login');
                        } else {
                            // Servidor foi reiniciado!
                            console.warn('⚠️ Restart do servidor detectado!');
                            this.restartDetected = true;
                            this.showServerRestartWarning();
                        }
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
                    await this.handleCriticalSessionFailure(
                        'Erro ao verificar a sessão. Faça login novamente.',
                        { title: 'Erro de sessão', redirectUrl: 'index.php' }
                    );
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
                    await this.showInfoDialog(
                        'Sem conexão',
                        'Não foi possível falar com o servidor. Verifique sua conexão e tente novamente.',
                        'Fechar'
                    );
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
                        debugLog('📥 XHR Response:', {
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

    async showTokenExpiredDialog() {
        const clearCacheChoice = await this.showDialog({
            title: 'Sessão expirada',
            message: 'Sua sessão expirou.\n\nIsso pode ser causado por dados antigos armazenados no cache.\n\nDeseja limpar o cache e tentar novamente?',
            confirmText: 'Limpar cache',
            cancelText: 'Ir para login',
            confirmVariant: 'warning'
        });
        
        if (clearCacheChoice) {
            // Tenta limpar cache primeiro
            this.forceClearCache();
        } else {
            // Vai direto para o login
            window.location.href = 'index.php';
        }
    }

    async showSystemInfo() {
        // Coleta informações do sistema e ambiente
        const systemInfo = this.getSystemInfo();
        
        // Busca informações de build/versão do servidor
        let buildInfo = null;
        try {
            const response = await fetch('version.php', {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-cache',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                buildInfo = await response.json();
                debugLog('🔧 Build info carregada:', buildInfo);
            } else {
                debugLog('⚠️ Não foi possível carregar build info:', response.status);
            }
        } catch (error) {
            debugLog('⚠️ Erro ao buscar build info:', error);
        }
        
        // Monta as informações para exibição (apenas dados dinâmicos)
        const infoSections = [];
        
        // Seção de Versão/Build (se disponível)
        if (buildInfo) {
            // Determina label da origem da build
            let buildSourceLabel = 'Desconhecido';
            const buildSource = buildInfo.build_source || 'unknown';
            switch(buildSource) {
                case 'ci':
                    buildSourceLabel = 'Automático (CI/CD)';
                    break;
                case 'deploy':
                    buildSourceLabel = 'Manual (Deploy Script)';
                    break;
                case 'local':
                    buildSourceLabel = 'Manual (Desenvolvedor)';
                    break;
            }
            
            infoSections.push([
                `📦 Versão e Build:`,
                `• Versão: ${buildInfo.version}`,
                `• Commit: ${buildInfo.commit_short} (${buildInfo.branch})`,
                `• Build: ${buildInfo.build_date}`,
                `• Idade: ${buildInfo.build_age || 'agora'}`,
                `• Origem: ${buildSourceLabel}`,
                `• PHP: ${buildInfo.php_version}`,
                `• Servidor: ${buildInfo.server_software}`
            ].join('\n'));
        }
        
        // Seção de Ambiente do Cliente
        infoSections.push([
            `🖥️ Ambiente do Cliente:`,
            `• Navegador: ${systemInfo.browser} (${systemInfo.platform})`,
            `• Resolução: ${systemInfo.screen} (Viewport: ${systemInfo.viewport})`,
            `• Cookies: ${systemInfo.cookiesEnabled ? 'Habilitados' : 'Desabilitados'}`,
            `• Status: ${systemInfo.onlineStatus}`,
            `• Timestamp: ${systemInfo.timestamp}`
        ].join('\n'));
        
        const info = infoSections.join('\n\n');
        
        await this.showInfoDialog('Informações do sistema', info, 'Fechar');
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

    async logout() {
        const confirmLogout = await this.showDialog({
            title: 'Confirmar logout',
            message: 'Tem certeza que deseja encerrar a sessão agora?',
            confirmText: 'Sair',
            cancelText: 'Cancelar',
            confirmVariant: 'danger'
        });

        if (confirmLogout) {
            this.performLogout();
        }
    }

    async showServerRestartWarning() {
        // Mostra aviso visual persistente
        this.updateStatus('⚠️ Servidor reiniciado - recarregue a página!', 'warning');

        const shouldReload = await this.showDialog({
            title: 'Servidor reiniciado',
            message: 'O servidor foi reiniciado.\n\nRecarregue a página agora para evitar perda de dados.',
            confirmText: 'Recarregar agora',
            cancelText: 'Continuar mesmo assim',
            confirmVariant: 'warning'
        });
        
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
        if (this.restartInterceptionBound) {
            return;
        }

        this.restartInterceptionBound = true;

        // Intercepta submits de formulários
        document.addEventListener('submit', (e) => {
            if (this.restartDetected) {
                e.preventDefault();
                e.stopImmediatePropagation();

                this.showDialog({
                    title: 'Servidor reiniciado',
                    message: 'Continuar sem recarregar a página pode causar perda de dados.\n\nDeseja recarregar agora?',
                    confirmText: 'Recarregar agora',
                    cancelText: 'Continuar sem recarregar',
                    confirmVariant: 'warning'
                }).then((confirmAction) => {
                    if (confirmAction) {
                        window.location.reload();
                    }
                });
            }
        }, true);

        // Intercepta cliques em botões de ação
        document.addEventListener('click', (e) => {
            if (!this.restartDetected || !(e.target instanceof Element)) {
                return;
            }

            const actionButton = e.target.closest('button, .action-button');
            if (!actionButton) {
                return;
            }

            // Permite interação normal com os botões da própria modal.
            if (actionButton.closest('#session-manager-dialog-backdrop')) {
                return;
            }

            // Verifica se não é um botão de reload seguro
            if (!actionButton.classList.contains('reload-safe')) {
                e.preventDefault();
                e.stopImmediatePropagation();

                this.showDialog({
                    title: 'Servidor reiniciado',
                    message: 'Recarregue a página antes de executar novas ações.\n\nDeseja recarregar agora?',
                    confirmText: 'Recarregar agora',
                    cancelText: 'Fechar',
                    confirmVariant: 'warning'
                }).then((confirmAction) => {
                    if (confirmAction) {
                        window.location.reload();
                    }
                });
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
            debugLog('⏰ Verificação periódica automática iniciada');
            this.checkSessionStatus(false);
        }, this.checkInterval);
        
        debugLog(`🔧 SessionManager: Verificação periódica iniciada a cada ${this.checkInterval / 60000} minutos`);
        debugLog(`🔧 Próxima verificação em: ${new Date(Date.now() + this.checkInterval).toLocaleTimeString()}`);
    }

    stopPeriodicCheck() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
            debugLog('🔧 SessionManager: Verificação periódica parada');
        }
    }

    bindEvents() {
        // Verifica sessão quando a página ganha foco
        window.addEventListener('focus', () => {
            // Verifica imediatamente quando volta ao foco
            debugLog('🔍 Página ganhou foco - verificando sessão');
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
                debugLog('🔍 Página ficou visível - verificando sessão');
                this.checkSessionStatus(false);
            }
        });
        
        // Monitora mudanças em localStorage para detectar logout em outras abas
        window.addEventListener('storage', (e) => {
            if (e.key === 'session_logout' || e.key === 'server_instance_id') {
                debugLog('🔍 Mudança em storage detectada - verificando sessão');
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
        debugLog('🧹 SessionManager: Forçando limpeza de cache...');
        
        try {
            // Chama endpoint de limpeza de cache
            const response = await fetch('clear_cache.php', {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-cache'
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                debugLog('✅ Cache limpo com sucesso');
                
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

    performLogout() {
        debugLog('👋 SessionManager: Iniciando logout...');
        
        // Sinaliza logout para outras abas
        try {
            localStorage.setItem('session_logout', Date.now().toString());
        } catch (e) {
            console.warn('⚠️ Erro ao sinalizar logout:', e);
        }
        
        // Limpa localStorage
        try {
            localStorage.removeItem('server_instance_id');
            debugLog('✅ localStorage limpo');
        } catch (e) {
            console.warn('⚠️ Erro ao limpar localStorage:', e);
        }
        
        // Limpa sessionStorage
        try {
            sessionStorage.clear();
            debugLog('✅ sessionStorage limpo');
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
