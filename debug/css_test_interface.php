<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste CSS - Botões Session Status Bar (Versão Corrigida)</title>
    <link rel="stylesheet" type="text/css" href="estilo.css?v=<?php echo time(); ?>">
    <style>
        body {
            padding: 80px 20px 20px 20px;
            font-family: Arial, sans-serif;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .test-section {
            margin-bottom: 30px;
        }
        .test-section h3 {
            margin-bottom: 15px;
            color: #495057;
        }
        .button-test-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .test-original {
            background: #fff;
            padding: 15px;
            border: 2px solid #28a745;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .test-comparison {
            background: #fff3cd;
            padding: 15px;
            border: 2px solid #ffc107;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Teste dos Estilos CSS - Botões Session Status Bar (CORRIGIDO)</h1>
        
        <div class="test-section">
            <h3>✅ Botões com CSS Corrigido (!important aplicado):</h3>
            <div class="test-original">
                <div class="button-test-row">
                    <button id="session-refresh-btn" class="session-btn">↻ Verificar</button>
                    <button id="session-info-btn" class="session-btn">ⓘ Sistema</button>
                    <button id="session-logout-btn" class="session-btn">⎋ Logout</button>
                    <button id="session-close-btn">×</button>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h3>🔄 Comparação - Botões sem classe (como estavam antes):</h3>
            <div class="test-comparison">
                <div class="button-test-row">
                    <button>↻ Verificar (sem classe)</button>
                    <button>ⓘ Sistema (sem classe)</button>
                    <button>⎋ Logout (sem classe)</button>
                    <button>× (sem classe)</button>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h3>📊 Botão de Reabrir:</h3>
            <div class="button-test-row">
                <div id="session-status-reopen-btn" style="position: relative; display: block; visibility: visible; opacity: 1;">📊 Status</div>
            </div>
        </div>
        
        <div class="test-section">
            <h3>🔍 Diagnóstico Automático:</h3>
            <div id="debug-info">
                <p><strong>Arquivo CSS:</strong> estilo.css?v=<?php echo time(); ?></p>
                <p><strong>Classes testadas:</strong> .session-btn, #session-refresh-btn, #session-info-btn, #session-logout-btn, #session-close-btn</p>
                <p><strong>Correções aplicadas:</strong> !important em todos os estilos, múltiplos seletores para maior especificidade</p>
            </div>
        </div>
        
        <div class="test-section">
            <h3>🎯 Resultado Esperado:</h3>
            <ul>
                <li><strong>Verificar:</strong> Fundo verde (#28a745), bordas arredondadas (8px)</li>
                <li><strong>Sistema:</strong> Fundo azul (#0d6efd), bordas arredondadas (8px)</li>
                <li><strong>Logout:</strong> Fundo vermelho (#dc3545), bordas arredondadas (8px)</li>
                <li><strong>Fechar:</strong> Transparente, menor (28x28px), cinza discreto</li>
            </ul>
        </div>
    </div>

    <!-- Inclui a barra de status real para teste -->
    <?php include 'includes/session-status-bar.php'; ?>

    <script>
        // Verifica se o CSS foi carregado
        window.addEventListener('load', function() {
            const refreshBtn = document.getElementById('session-refresh-btn');
            const infoBtn = document.getElementById('session-info-btn');
            const logoutBtn = document.getElementById('session-logout-btn');
            const closeBtn = document.getElementById('session-close-btn');
            
            const debugInfo = document.getElementById('debug-info');
            
            function analyzeButton(btn, expectedColor, name) {
                if (!btn) return `❌ ${name}: Botão não encontrado`;
                
                const computedStyle = window.getComputedStyle(btn);
                const backgroundColor = computedStyle.backgroundColor;
                const borderRadius = computedStyle.borderRadius;
                const display = computedStyle.display;
                
                let status = '✅';
                if (!backgroundColor.includes('rgb') && !backgroundColor.includes('#')) {
                    status = '❌';
                }
                
                return `${status} ${name}: bg=${backgroundColor}, radius=${borderRadius}, display=${display}`;
            }
            
            setTimeout(() => {
                debugInfo.innerHTML += `
                    <h4>🔍 Análise Detalhada:</h4>
                    <p>${analyzeButton(refreshBtn, '#28a745', 'Verificar')}</p>
                    <p>${analyzeButton(infoBtn, '#0d6efd', 'Sistema')}</p>
                    <p>${analyzeButton(logoutBtn, '#dc3545', 'Logout')}</p>
                    <p>${analyzeButton(closeBtn, 'transparent', 'Fechar')}</p>
                    <p><strong>Timestamp CSS:</strong> <?php echo time(); ?></p>
                `;
            }, 100);
        });
        
        // Força mostrar a barra de status para teste
        if (window.sessionStatusBarAPI) {
            window.sessionStatusBarAPI.show();
        }
    </script>
</body>
</html>
