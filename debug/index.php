<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferramentas de Debug - Foursquare Mass Editor</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            padding: 40px; 
            background: #f0f2f5; 
            color: #1a1a1a; 
            line-height: 1.6;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        .header-section { 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
            margin-bottom: 30px;
        }
        h1 { 
            color: #2c3e50; 
            margin-bottom: 10px;
            margin-top: -5px;
            font-size: 2rem;
        }
        .subtitle {
            color: #7f8c8d;
            font-size: 1rem;
            margin-bottom: 0;
        }
        .category {
            margin-bottom: 30px;
        }
        .category-title {
            font-size: 1.3rem;
            color: #34495e;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #3498db;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .category-icon {
            font-size: 1.5rem;
        }
        .card { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.08); 
            margin-bottom: 12px; 
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }
        .card:hover { 
            transform: translateX(4px); 
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            border-left-color: #3498db;
        }
        h2 { 
            margin: 0 0 8px 0; 
            font-size: 1.1rem; 
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        p { 
            color: #666; 
            margin: 0;
            font-size: 0.95rem;
        }
        a { 
            text-decoration: none; 
            color: inherit; 
            display: block; 
        }
        .tag { 
            display: inline-block; 
            padding: 3px 10px; 
            border-radius: 12px; 
            font-size: 0.75rem; 
            font-weight: 600; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .tag-system { background: #e3f2fd; color: #1976d2; }
        .tag-session { background: #f3e5f5; color: #7b1fa2; }
        .tag-maps { background: #e8f5e9; color: #388e3c; }
        .tag-ui { background: #fff3e0; color: #f57c00; }
        .tag-api { background: #fce4ec; color: #c2185b; }
        .tag-util { background: #e0f2f1; color: #00796b; }
        .tag-test { background: #f3e5f5; color: #8e24aa; }
        .tag-help { background: #e1f5fe; color: #0277bd; }
        .tag-legacy { background: #eeeeee; color: #616161; }
        .back-link { 
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px; 
            color: #3498db; 
            text-decoration: none; 
            font-weight: 500;
            padding: 8px 0;
            transition: color 0.2s;
        }
        .back-link:hover { 
            color: #2980b9;
        }
        .endpoint-list {
            margin-top: 10px;
            padding-left: 20px;
        }
        .endpoint-list li {
            margin-bottom: 6px;
        }
        .endpoint-list a {
            display: inline;
            color: #3498db;
            text-decoration: none;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .endpoint-list a:hover {
            text-decoration: underline;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 12px;
        }
        @media (max-width: 768px) {
            body { padding: 20px; }
            .grid-2 { grid-template-columns: 1fr; }
            h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../" class="back-link">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
            </svg>
            Voltar para Aplicação
        </a>

        <div class="header-section">
            <h1>🛠️ Ferramentas de Debug</h1>
            <p class="subtitle">Coleção completa de utilitários para desenvolvimento, teste, diagnóstico e monitoramento.</p>
        </div>

        <!-- Sistema e Versão -->
        <div class="category">
            <h3 class="category-title">
                <span class="category-icon">🎯</span>
                Sistema e Versão
            </h3>
            
            <a href="../version.php" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                <div class="card">
                    <h2><span class="tag tag-system">SYSTEM</span> Informações de Versão e Build</h2>
                    <p>Endpoint JSON com versão, commit hash, data de build, idade da build e informações do servidor. Essencial para rastreabilidade e rollback.</p>
                </div>
            </a>
            
            <a href="../clear_cache.php" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                <div class="card">
                    <h2><span class="tag tag-util">UTIL</span> Limpeza de Cache</h2>
                    <p>Interface para limpar cache do navegador, cookies e sessões. Remove dados antigos armazenados e resolve problemas de autenticação.</p>
                </div>
            </a>
        </div>

        <!-- Sessão e Autenticação -->
        <div class="category">
            <h3 class="category-title">
                <span class="category-icon">🔐</span>
                Sessão e Autenticação
            </h3>
            
            <a href="test_session_debug.html" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                <div class="card">
                    <h2><span class="tag tag-session">SESSION</span> Painel de Debug de Sessão</h2>
                    <p>Interface consolidada para testar criação, validação e destruição de sessões. Use esta ferramenta primeiro para troubleshooting de autenticação.</p>
                </div>
            </a>
            
            <div class="card">
                <h2><span class="tag tag-session">SESSION</span> Endpoints de Backend</h2>
                <p>APIs diretas para automação e testes via curl/Postman:</p>
                <ul class="endpoint-list">
                    <li><a href="session_test_manager.php?action=status">session_test_manager.php</a> - Gerenciador completo de sessão (create, validate, destroy)</li>
                    <li><a href="debug_session.php?mode=validate">debug_session.php</a> - Validador detalhado de sessão</li>
                    <li><a href="../session_status.php">session_status.php</a> - Status da sessão atual (usado pelo frontend)</li>
                </ul>
            </div>
        </div>

        <!-- Google Maps -->
        <div class="category">
            <h3 class="category-title">
                <span class="category-icon">🗺️</span>
                Google Maps
            </h3>
            
            <a href="test_google_maps_config.php" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                <div class="card">
                    <h2><span class="tag tag-maps">MAPS</span> Diagnóstico de Configuração</h2>
                    <p>Verifica se as credenciais do Google Maps estão sendo carregadas corretamente do .env. Valida API Key, Map ID e configurações.</p>
                </div>
            </a>
            
            <a href="test_integration_markers.html" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                <div class="card">
                    <h2><span class="tag tag-maps">MAPS</span> Teste de Marcadores</h2>
                    <p>Valida a integração com a API do Google Maps e renderização de marcadores no mapa.</p>
                </div>
            </a>
            
            <div class="grid-2">
                <a href="test_draggable_markers.html" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                    <div class="card">
                        <h2><span class="tag tag-maps">MAPS</span> Marcadores Arrastáveis</h2>
                        <p>Testa funcionalidade de drag-and-drop dos marcadores.</p>
                    </div>
                </a>
                
                <a href="test_custom_markers.html" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                    <div class="card">
                        <h2><span class="tag tag-maps">MAPS</span> Ícones Personalizados</h2>
                        <p>Verifica carregamento de ícones das categorias.</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Geocodificação -->
        <div class="category">
            <h3 class="category-title">
                <span class="category-icon">📍</span>
                Geocodificação (Google Maps)
            </h3>
            
            <a href="test_geocoding_detailed.php" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                <div class="card">
                    <h2><span class="tag tag-maps">MAPS</span> Debug Detalhado de Geocodificação</h2>
                    <p>Diagnóstico completo da conversão de endereços em coordenadas. Mostra resposta completa da API, status codes e mensagens de erro detalhadas.</p>
                </div>
            </a>
            
            <a href="test_geocoding.php" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                <div class="card">
                    <h2><span class="tag tag-maps">MAPS</span> Teste Rápido de Geocodificação</h2>
                    <p>Testa múltiplos endereços simultaneamente. Verifica se GOOGLE_MAPS_GEOCODING_KEY está configurada e funcional.</p>
                </div>
            </a>
            
            <div class="card" style="background: #e8f5e9; border-left-color: #4caf50;">
                <h2><span class="tag tag-help">HELP</span> Como Configurar Geocoding API</h2>
                <p>📚 Guia completo: <a href="../docs/GEOCODING_SETUP.md" target="_blank" style="display: inline; color: #2e7d32; font-weight: 600;">docs/GEOCODING_SETUP.md</a></p>
                <p style="margin-top: 8px; font-size: 0.9rem;">✅ Cota gratuita: 10.000 requisições/mês | 💰 Custo adicional: $5 por 1.000</p>
            </div>
        </div>

        <!-- Interface e UI -->
        <div class="category">
            <h3 class="category-title">
                <span class="category-icon">🎨</span>
                Interface e UI
            </h3>
            
            <a href="css_test_interface.php" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                <div class="card">
                    <h2><span class="tag tag-ui">UI</span> Teste de Interface CSS</h2>
                    <p>Ambiente isolado para validar estilos e componentes visuais da barra de status de sessão com 8 variantes diferentes.</p>
                </div>
            </a>
            
            <a href="test_visual_edited_rows.html" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                <div class="card">
                    <h2><span class="tag tag-ui">UI</span> Visualização de Linhas Editadas</h2>
                    <p>Teste visual de diferentes estados de linhas na tabela de edição: normal, editada, erro, sincronizando, etc.</p>
                </div>
            </a>
            
            <a href="test_dojo_cdn.php" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                <div class="card">
                    <h2><span class="tag tag-ui">UI</span> Diagnóstico de CDN do Dojo</h2>
                    <p>Valida carregamento de módulos via CDN, testa dojo.cookie e dojoConfig. Útil para troubleshooting de timing assíncrono.</p>
                </div>
            </a>
        </div>

        <!-- APIs Foursquare -->
        <div class="category">
            <h3 class="category-title">
                <span class="category-icon">🔌</span>
                APIs Foursquare
            </h3>
            
            <a href="api_comparison_tool.html" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                <div class="card">
                    <h2><span class="tag tag-api">API</span> Comparação de APIs Foursquare</h2>
                    <p>Ferramenta interativa para comparar v2, v3 e Places API. Auxilia na auditoria de funcionalidades e análise de migração entre versões.</p>
                </div>
            </a>
            
            <a href="test_api_version.html" onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);">
                <div class="card">
                    <h2><span class="tag tag-api">API</span> Teste de Versionamento da API</h2>
                    <p>Interface para testar diferentes versões da API Foursquare (v2 vs v3) e comparar respostas de endpoints.</p>
                </div>
            </a>
        </div>

        <!-- Ferramentas Legadas -->
        <div class="category">
            <h3 class="category-title">
                <span class="category-icon">📦</span>
                Ferramentas Legadas
            </h3>
            
            <div class="card" style="opacity: 0.7;">
                <h2><span class="tag tag-legacy">LEGACY</span> Criador de Sessão Legado</h2>
                <p>Mantido para compatibilidade com sistema antigo. Prefira usar o <strong>Painel de Debug de Sessão</strong> moderno.</p>
                <ul class="endpoint-list">
                    <li><a href="legacy_session_creator.php">legacy_session_creator.php</a> - Criador de sessão do sistema antigo</li>
                </ul>
            </div>
        </div>

        <div style="text-align: center; margin-top: 40px; color: #95a5a6; font-size: 0.85rem;">
            <p>💡 Dica: Pressione <kbd style="padding: 2px 6px; background: #ecf0f1; border-radius: 3px; font-family: monospace;">Ctrl+F</kbd> para buscar uma ferramenta específica</p>
        </div>
    </div>
    
    <script>
        // Restaurar posição de scroll ao voltar
        (function() {
            const savedScrollPos = sessionStorage.getItem('debugIndexScrollPos');
            
            if (savedScrollPos) {
                const scrollPos = parseInt(savedScrollPos);
                
                function tryRestoreScroll() {
                    window.scrollTo(0, scrollPos);
                    const currentPos = window.scrollY || document.documentElement.scrollTop;
                    
                    if (currentPos === 0 && scrollPos > 0) {
                        return false;
                    }
                    return true;
                }
                
                tryRestoreScroll();
                
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        setTimeout(function() {
                            if (tryRestoreScroll()) {
                                sessionStorage.removeItem('debugIndexScrollPos');
                            }
                        }, 50);
                    });
                } else {
                    setTimeout(function() {
                        if (tryRestoreScroll()) {
                            sessionStorage.removeItem('debugIndexScrollPos');
                        } else {
                            setTimeout(function() {
                                tryRestoreScroll();
                                sessionStorage.removeItem('debugIndexScrollPos');
                            }, 500);
                        }
                    }, 100);
                }
            }
        })();
    </script>
</body>
</html>