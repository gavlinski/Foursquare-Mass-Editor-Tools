<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferramentas de Debug - Foursquare Mass Editor</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 40px; background: #f0f2f5; color: #1a1a1a; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; transition: transform 0.2s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
        h2 { margin-top: 0; font-size: 1.2rem; color: #34495e; }
        p { color: #666; margin-bottom: 15px; }
        a { text-decoration: none; color: inherit; display: block; }
        .tag { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; margin-right: 5px; }
        .tag-ui { background: #e1f5fe; color: #0288d1; }
        .tag-api { background: #e8f5e9; color: #2e7d32; }
        .tag-legacy { background: #fff3e0; color: #ef6c00; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #3498db; text-decoration: none; font-weight: 500; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="../" class="back-link">← Voltar para Aplicação</a>
        
        <h1>🛠️ Ferramentas de Debug</h1>
        <p>Coleção de utilitários para desenvolvimento, teste e diagnóstico.</p>

        <a href="api_comparison_tool.html">
            <div class="card">
                <h2><span class="tag tag-api">API</span> Comparação de APIs Foursquare</h2>
                <p>Ferramenta interativa para comparar v2, v3 e Places API. Auxilia na auditoria de funcionalidades e análise de migração.</p>
            </div>
        </a>

        <a href="test_session_debug.html">
            <div class="card">
                <h2><span class="tag tag-ui">UI</span> Painel de Debug de Sessão</h2>
                <p>Interface principal consolidada para testar criação, validação e destruição de sessões. Use esta ferramenta primeiro.</p>
            </div>
        </a>

        <a href="test_integration_markers.html">
            <div class="card">
                <h2><span class="tag tag-ui">UI</span> Teste de Marcadores (Google Maps)</h2>
                <p>Verifica a integração com a API do Google Maps e renderização de marcadores.</p>
            </div>
        </a>

        <a href="test_draggable_markers.html">
            <div class="card">
                <h2><span class="tag tag-ui">UI</span> Teste de Marcadores Arrastáveis</h2>
                <p>Valida a funcionalidade de drag-and-drop dos marcadores no mapa.</p>
            </div>
        </a>

        <a href="test_custom_markers.html">
            <div class="card">
                <h2><span class="tag tag-ui">UI</span> Teste de Ícones Personalizados</h2>
                <p>Verifica se os ícones personalizados das categorias estão sendo carregados corretamente.</p>
            </div>
        </a>

        <a href="css_test_interface.php">
            <div class="card">
                <h2><span class="tag tag-ui">UI</span> Teste de Interface CSS</h2>
                <p>Ambiente isolado para validar estilos e componentes visuais.</p>
            </div>
        </a>

        <a href="test_dojo_cdn.php">
            <div class="card">
                <h2><span class="tag tag-ui">UI</span> Diagnóstico de CDN do Dojo</h2>
                <p>Valida carregamento de módulos via CDN, testa dojo.cookie e dojoConfig. Útil para troubleshooting de timing assíncrono.</p>
            </div>
        </a>

        <div class="card">
            <h2><span class="tag tag-api">API</span> Endpoints de Backend</h2>
            <p>APIs diretas para automação e testes via curl/Postman:</p>
            <ul>
                <li><a href="session_test_manager.php?action=status" target="_blank" style="display:inline; color:#3498db">session_test_manager.php</a> - Gerenciador de sessão</li>
                <li><a href="debug_session.php?mode=validate" target="_blank" style="display:inline; color:#3498db">debug_session.php</a> - Validador de sessão</li>
            </ul>
        </div>

        <div class="card" style="opacity: 0.7;">
            <h2><span class="tag tag-legacy">LEGACY</span> Ferramentas Legadas</h2>
            <p>Mantidas para compatibilidade, prefira usar o Painel de Debug de Sessão.</p>
            <ul>
                <li>legacy_session_creator.php</li>
            </ul>
        </div>
    </div>
</body>
</html>