<?php
/**
 * Test Dojo CDN Loading
 * Diagnóstico para verificar carregamento correto do Dojo via CDN
 */
require_once __DIR__ . '/../includes/asset_helper.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Teste Dojo CDN</title>
    <?php dojo_script(['parseOnLoad' => true, 'isDebug' => true]); ?>
    <?php dojo_theme('tundra'); ?>
</head>
<body class="tundra">
    <h1>Diagnóstico Dojo CDN</h1>
    
    <div id="status">Verificando...</div>
    
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
                status.innerHTML = '✅ <strong>Sucesso!</strong> dojo.cookie carregou corretamente do CDN';
                status.style.color = 'green';
                
                // Testa o cookie
                dojo.cookie("test_cookie", "test_value");
                var testValue = dojo.cookie("test_cookie");
                console.log('Teste de cookie:', testValue);
            } else {
                status.innerHTML = '❌ <strong>Erro!</strong> dojo.cookie não está disponível';
                status.style.color = 'red';
                console.error('Módulo dojo.cookie não carregou. Verifique:');
                console.error('- Rede (DevTools → Network)');
                console.error('- Console (erros de carregamento)');
                console.error('- CSP (Content-Security-Policy)');
            }
        }, 1000);
    </script>
    
    <h2>Informações do Sistema</h2>
    <pre><?php
        echo "isProduction(): " . (isProduction() ? 'true' : 'false') . "\n";
        echo "dojo_url(): " . dojo_url() . "\n";
        echo "DOJO_CDN_BASE: " . DOJO_CDN_BASE . "\n";
        echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
        echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'não definido') . "\n";
    ?></pre>
    
    <h2>Instruções</h2>
    <ol>
        <li>Abra o <strong>DevTools Console</strong> (F12)</li>
        <li>Verifique os logs de diagnóstico acima</li>
        <li>Vá em <strong>Network</strong> e filtre por "dojo"</li>
        <li>Verifique se <code>cookie.js</code> foi carregado com status 200</li>
        <li>Se houver erro 404 ou CSP, veja os detalhes no console</li>
    </ol>
</body>
</html>
