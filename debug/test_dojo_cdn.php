<?php
/**
 * Test Dojo CDN Loading
 * Diagnóstico para verificar carregamento correto do Dojo via CDN
 */
require_once __DIR__ . '/../includes/asset_helper.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Dojo CDN - Foursquare Mass Editor</title>
    <?php dojo_script(['parseOnLoad' => true, 'isDebug' => true]); ?>
    <?php dojo_theme('tundra'); ?>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 30px;
            background: #f5f7fa;
            color: #2c3e50;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 0;
        }
        .back-link:hover {
            color: #2980b9;
        }
        .back-link svg {
            width: 16px;
            height: 16px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
            margin-top: -5px;
        }
        h2 {
            color: #34495e;
            margin-top: 25px;
            margin-bottom: 10px;
        }
        #status {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 1.1rem;
            border-left: 4px solid #95a5a6;
            background: #ecf0f1;
        }
        #status.success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        #status.error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        pre {
            background: #2d3436;
            color: #dfe6e9;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .info-box {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            color: #0c5460;
        }
        ol {
            margin: 10px 0;
            padding-left: 25px;
        }
        ol li {
            margin: 8px 0;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e74c3c;
        }
        .nav-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .nav-footer a {
            color: #3498db;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            background: #ecf0f1;
            font-size: 0.9rem;
        }
        .nav-footer a:hover {
            background: #3498db;
            color: white;
        }
    </style>
</head>
<body class="tundra">
    <div class="container">
        <h1>🔍 Diagnóstico Dojo CDN</h1>
        
        <div id="status">🔄 Verificando carregamento do Dojo...</div>
        
        <div class="info-box">
            <h3>📋 O que este teste faz</h3>
            <p>Verifica se o Dojo Toolkit está sendo carregado corretamente via CDN e se o módulo <code>dojo.cookie</code> está disponível para uso.</p>
        </div>
        
        <h2>Informações do Sistema</h2>
        <pre><?php
            echo "isProduction(): " . (isProduction() ? 'true' : 'false') . "\n";
            echo "dojo_url(): " . dojo_url() . "\n";
            echo "DOJO_CDN_BASE: " . DOJO_CDN_BASE . "\n";
            echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
            echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'não definido') . "\n";
        ?></pre>
        
        <h2>📝 Instruções de Debug</h2>
        <ol>
            <li>Abra o <strong>DevTools Console</strong> (F12 ou Cmd+Option+I)</li>
            <li>Verifique os logs de diagnóstico abaixo no console</li>
            <li>Vá na aba <strong>Network</strong> e filtre por "dojo"</li>
            <li>Verifique se <code>cookie.js</code> foi carregado com status 200</li>
            <li>Se houver erro 404 ou CSP, veja os detalhes no console</li>
        </ol>
    </div>
    
    <script>
        console.log('🔍 Diagnóstico Dojo CDN');
        console.log('dojoConfig:', dojoConfig);
        console.log('dojo version:', dojo.version);
        console.log('dojo.baseUrl:', dojo.baseUrl);
        
        // Testa se dojo.cookie está disponível ANTES do require
        console.log('dojo.cookie (antes do require):', typeof dojo.cookie);
        
        // Agora faz o require
        dojo.require("dojo.cookie");
        
        // Aguarda o módulo carregar
        setTimeout(function() {
            console.log('dojo.cookie (depois do require):', typeof dojo.cookie);
            
            var status = document.getElementById('status');
            
            if (typeof dojo.cookie === 'function') {
                status.className = 'success';
                status.innerHTML = '✅ <strong>Sucesso!</strong> dojo.cookie carregou corretamente do CDN';
                
                // Testa o cookie
                dojo.cookie("test_cookie", "test_value");
                var testValue = dojo.cookie("test_cookie");
                console.log('✅ Teste de cookie realizado:', testValue);
                console.log('✅ dojo.cookie está funcionando corretamente');
            } else {
                status.className = 'error';
                status.innerHTML = '❌ <strong>Erro!</strong> dojo.cookie não está disponível';
                console.error('❌ Módulo dojo.cookie não carregou. Verifique:');
                console.error('- Rede (DevTools → Network)');
                console.error('- Console (erros de carregamento)');
                console.error('- CSP (Content-Security-Policy)');
            }
        }, 1000);
    </script>
    
    <footer style="text-align: center; padding: 30px 20px; margin-top: 40px; border-top: 2px solid #e0e0e0;">
        <a href="index.php" style="display: inline-flex; align-items: center; gap: 8px; color: #3498db; text-decoration: none; padding: 12px 24px; border-radius: 8px; background: #f8f9fa; font-weight: 500; font-size: 14px; transition: all 0.2s; border: 1px solid #dee2e6;" onmouseover="this.style.color='#2980b9'; this.style.background='#e9ecef'; this.style.borderColor='#ced4da';" onmouseout="this.style.color='#3498db'; this.style.background='#f8f9fa'; this.style.borderColor='#dee2e6';">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
            </svg>
            Voltar para Debug
        </a>
    </footer>
</body>
</html>
