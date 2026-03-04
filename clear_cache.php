<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpeza de Cache - Foursquare Mass Editor</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 30px;
            background: linear-gradient(135deg, #f39c12 0%, #e74c3c 100%);
            color: #2c3e50;
            line-height: 1.6;
            min-height: 100vh;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 25px;
            color: #e67e22;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 0;
        }
        .back-link:hover {
            color: #d35400;
        }
        .back-link svg {
            width: 16px;
            height: 16px;
        }
        h1 {
            color: #2c3e50;
            margin-top: -5px;
            margin-bottom: 10px;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .warning-box h3 {
            margin: 0 0 10px 0;
            color: #856404;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .warning-box p {
            margin: 5px 0;
            color: #856404;
        }
        .info-box {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            color: #0c5460;
        }
        .info-box ul {
            margin: 10px 0;
            padding-left: 25px;
        }
        .info-box li {
            margin: 5px 0;
            color: #0c5460;
        }
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        .btn-json {
            background: #3498db;
            color: white;
            font-size: 0.9rem;
            padding: 10px 20px;
        }
        .btn-json:hover {
            background: #2980b9;
        }
        .technical-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 25px;
        }
        .technical-section h3 {
            margin: 0 0 15px 0;
            color: #34495e;
            font-size: 1.1rem;
        }
        .technical-section code {
            background: #2d3436;
            color: #dfe6e9;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .technical-section ul {
            margin: 10px 0;
            padding-left: 25px;
        }
        .technical-section li {
            margin: 8px 0;
            color: #555;
        }
        #result {
            margin-top: 20px;
            padding: 20px;
            border-radius: 8px;
            display: none;
        }
        #result.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
            display: block;
        }
        #result.error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
            display: block;
        }
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            .container {
                padding: 25px;
            }
            .button-group {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Limpeza de Cache</h1>

        <div class="warning-box">
            <h3>⚠️ Atenção</h3>
            <p><strong>Esta ação irá:</strong></p>
            <ul style="margin: 10px 0; padding-left: 25px;">
                <li>Destruir sua sessão atual</li>
                <li>Limpar todos os cookies da aplicação</li>
                <li>Remover dados armazenados no cache do navegador</li>
                <li>Você precisará fazer login novamente</li>
            </ul>
        </div>

        <div class="info-box">
            <h3>📋 Quando usar esta ferramenta</h3>
            <ul>
                <li>Quando receber erros de "Token Expirado" persistentes</li>
                <li>Se a aplicação estiver carregando dados antigos de cache</li>
                <li>Após fazer logout mas os dados ainda aparecem</li>
                <li>Para resolver problemas de autenticação</li>
                <li>Quando mudanças no código não estão refletindo no navegador</li>
            </ul>
        </div>

        <div class="technical-section">
            <h3>🔧 Como Funciona</h3>
            <p>O processo de limpeza executa as seguintes ações técnicas:</p>
            <ul>
                <li><code>$_SESSION = array()</code> - Limpa todas as variáveis de sessão</li>
                <li><code>session_destroy()</code> - Destrói a sessão PHP completamente</li>
                <li><code>setcookie(..., time() - 3600)</code> - Expira todos os cookies relevantes:
                    <ul style="margin-top: 5px;">
                        <li>oauth_token, name, PHPSESSID</li>
                        <li>foursquare_token, user_data, session_data</li>
                    </ul>
                </li>
                <li><code>Clear-Site-Data</code> header - Limpa cache, cookies e storage do navegador</li>
            </ul>
        </div>

        <div class="button-group">
            <button class="btn btn-danger" onclick="clearCache()">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                </svg>
                Limpar Cache Agora
            </button>
            
            <a href="debug/" class="btn btn-secondary">
                Cancelar
            </a>
            
            <a href="clear_cache_action.php" class="btn btn-json" target="_blank">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M5 4a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H5zm-.5 2.5A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zM5 8a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H5zm0 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1H5z"/>
                    <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                </svg>
                Ver Resposta JSON
            </a>
        </div>

        <div id="result"></div>
    </div>

    <script>
        async function clearCache() {
            const resultEl = document.getElementById('result');
            const btn = document.querySelector('.btn-danger');
            
            // Desabilita o botão
            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.style.cursor = 'not-allowed';
            btn.innerHTML = '<span>🔄 Limpando...</span>';
            
            try {
                const response = await fetch('clear_cache_action.php', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    resultEl.className = 'success';
                    resultEl.innerHTML = `
                        <strong>✅ Sucesso!</strong><br>
                        ${data.message}<br>
                        <br>
                        <strong>Ações executadas:</strong><br>
                        • Cookies limpos: ${data.actions.cookies_cleared}<br>
                        • Sessão destruída: ${data.actions.session_destroyed ? 'Sim' : 'Não'}<br>
                        • Cache limpo: ${data.actions.cache_cleared ? 'Sim' : 'Não'}<br>
                        <br>
                        <em>Redirecionando para login em 3 segundos...</em>
                    `;
                    
                    // Redireciona para o login após 3 segundos
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 3000);
                } else {
                    throw new Error(data.message || 'Erro desconhecido');
                }
                
            } catch (error) {
                resultEl.className = 'error';
                resultEl.innerHTML = `
                    <strong>❌ Erro ao limpar cache:</strong><br>
                    ${error.message}
                `;
                
                // Reabilita o botão
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
                btn.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                    </svg>
                    Limpar Cache Agora
                `;
            }
        }
    </script>
</body>
</html>
