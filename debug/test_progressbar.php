<?php
/**
 * Test Progress Bar CSS
 * Verifica se as imagens do ProgressBar carregam corretamente do CDN
 */
require_once __DIR__ . '/../includes/asset_helper.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Teste Progress Bar - Foursquare Mass Editor</title>
    <?php dojo_script(['parseOnLoad' => true]); ?>
    <?php dojo_theme('tundra'); ?>
    <link rel="stylesheet" type="text/css" href="../estilo.css">
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
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        h2 {
            color: #34495e;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        .info-box {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 15px 0;
        }
        code {
            background: #2d3436;
            color: #dfe6e9;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .progress-demo {
            margin: 20px 0;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            margin: 5px;
        }
        button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body class="tundra">
    <div class="container">
        <h1>🔍 Teste Progress Bar</h1>
        
        <div class="info-box">
            <h3>📋 O que este teste faz</h3>
            <p>Verifica se as imagens do <code>ProgressBar.Class.php</code> carregam corretamente do CDN do Google sem gerar erros 404.</p>
        </div>
        
        <h2>1. Progress Bar Personalizado (estilo.css)</h2>
        <p>Este componente usa imagens do tema Dojo hospedadas no CDN:</p>
        <ul>
            <li><code>progressBarEmpty.png</code> - Barra vazia</li>
            <li><code>progressBarFull.png</code> - Barra cheia</li>
            <li><code>progressBarAnim.gif</code> - Animação indeterminada</li>
        </ul>
        
        <div class="test-section">
            <h3>Estado Indeterminado (Carregando...)</h3>
            <div class="progress-demo">
                <div class="pb_container">
                    <div class="pb_text" style="display: inline;">Carregando...</div>
                    <div class="pb_bar">
                        <div class="pb_indeterminate" style="width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Progresso 0%</h3>
            <div class="progress-demo">
                <div class="pb_container">
                    <div class="pb_text" style="display: inline;">0%</div>
                    <div class="pb_bar">
                        <div class="pb_before" style="width: 0%;"></div>
                        <div id="transparent-bar" style="float: left; width: 100%; height: 1.3em;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Progresso 50%</h3>
            <div class="progress-demo">
                <div class="pb_container">
                    <div class="pb_text" style="display: inline;">50%</div>
                    <div class="pb_bar">
                        <div class="pb_before" style="width: 50%;"></div>
                        <div id="transparent-bar2" style="float: left; width: 50%; height: 1.3em;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Progresso 100%</h3>
            <div class="progress-demo">
                <div class="pb_container">
                    <div class="pb_text" style="display: inline;">100%</div>
                    <div class="pb_bar">
                        <div class="pb_before" style="width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <h2>2. Progress Bar Dijit (dijit.ProgressBar)</h2>
        <p>Componente nativo do Dojo Toolkit (usado em edit.php):</p>
        
        <div class="test-section">
            <div id="dijitProgress" dojoType="dijit.ProgressBar" style="width:400px; margin: 20px 0;" maximum="100" value="75">
                75% Complete
            </div>
        </div>
        
        <h2>4. Instruções de Verificação no DevTools</h2>
        <div class="info-box">
            <ol>
                <li>Abra o <strong>DevTools Console</strong> (F12 ou Cmd+Option+I)</li>
                <li>Vá na aba <strong>Network</strong></li>
                <li>Filtre por "progressBar" ou "images"</li>
                <li><strong>✅ Sucesso:</strong> Todas as imagens carregam com status 200 do CDN do Google</li>
                <li><strong>❌ Erro:</strong> Erros 404 indicam que ainda há referências locais</li>
            </ol>
        </div>
        
        <h2>3. Configuração Atual do Sistema</h2>
        <div class="test-section">
            <div style="display: grid; grid-template-columns: 200px 1fr; gap: 10px; margin: 15px 0;">
                <div style="font-weight: bold; color: #555;">Ambiente:</div>
                <div style="font-family: 'Courier New', monospace; background: #f8f9fa; padding: 5px 10px; border-radius: 4px;">
                    <?php echo isProduction() ? '🚀 PRODUÇÃO (sempre CDN)' : '💻 DESENVOLVIMENTO'; ?>
                </div>
                
                <div style="font-weight: bold; color: #555;">DOJO_SOURCE:</div>
                <div style="font-family: 'Courier New', monospace; background: #f8f9fa; padding: 5px 10px; border-radius: 4px;">
                    <?php echo getenv('DOJO_SOURCE') ?: 'cdn (padrão)'; ?>
                </div>
                
                <div style="font-weight: bold; color: #555;">Modo Ativo:</div>
                <div style="font-family: 'Courier New', monospace; background: #f8f9fa; padding: 5px 10px; border-radius: 4px; color: <?php echo useLocalDojo() ? '#27ae60' : '#e67e22'; ?>; font-weight: bold;">
                    <?php echo useLocalDojo() ? '✅ ARQUIVOS LOCAIS' : '🌐 CDN do Google'; ?>
                </div>
                
                <div style="font-weight: bold; color: #555;">Base URL Dojo:</div>
                <div style="font-family: 'Courier New', monospace; background: #f8f9fa; padding: 5px 10px; border-radius: 4px; word-break: break-all;">
                    <?php echo dojo_url(); ?>
                </div>
                
                <div style="font-weight: bold; color: #555;">Tema URL:</div>
                <div style="font-family: 'Courier New', monospace; background: #f8f9fa; padding: 5px 10px; border-radius: 4px; word-break: break-all;">
                    <?php echo dojo_theme_url('tundra'); ?>
                </div>
                
                <div style="font-weight: bold; color: #555;">Imagens Base:</div>
                <div style="font-family: 'Courier New', monospace; background: #f8f9fa; padding: 5px 10px; border-radius: 4px; word-break: break-all;">
                    <?php echo dojo_theme_images_base('tundra'); ?>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <h4>Teste de Carregamento das Imagens:</h4>
                <p style="margin: 10px 0;">Se as imagens abaixo aparecerem (não ficarem em branco ou com X vermelho), significa que estão acessíveis:</p>
                <?php $imagesBase = dojo_theme_images_base('tundra'); ?>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <div style="text-align: center;">
                        <img src="<?php echo $imagesBase . 'progressBarEmpty.png'; ?>" 
                             style="width: 100px; height: 20px; border: 1px solid #ddd;" 
                             alt="Empty" 
                             onerror="this.style.border='2px solid red'; this.alt='❌ ERRO 404'">
                        <div style="font-size: 0.8rem; margin-top: 5px; color: #666;">Empty</div>
                    </div>
                    <div style="text-align: center;">
                        <img src="<?php echo $imagesBase . 'progressBarFull.png'; ?>" 
                             style="width: 100px; height: 20px; border: 1px solid #ddd;" 
                             alt="Full"
                             onerror="this.style.border='2px solid red'; this.alt='❌ ERRO 404'">
                        <div style="font-size: 0.8rem; margin-top: 5px; color: #666;">Full</div>
                    </div>
                    <div style="text-align: center;">
                        <img src="<?php echo $imagesBase . 'progressBarAnim.gif'; ?>" 
                             style="width: 100px; height: 20px; border: 1px solid #ddd;" 
                             alt="Anim"
                             onerror="this.style.border='2px solid red'; this.alt='❌ ERRO 404'">
                        <div style="font-size: 0.8rem; margin-top: 5px; color: #666;">Animated</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="info-box" style="background: #e7f3ff; border-left-color: #2196F3;">
            <h3>🔍 Comportamento nas Páginas Reais</h3>
            <p><strong>search.php, load.php e load_csv.php:</strong></p>
            <ul style="margin: 10px 0;">
                <li><strong>Sucesso:</strong> Redirecionamento para edit.php (nova página)</li>
                <li><strong>Erro:</strong> ProgressBar é escondido, mostra mensagem de erro</li>
                <li><strong>Sem reuso:</strong> Cada execução é uma nova requisição HTTP</li>
                <li><strong>Sem problema de recarregamento:</strong> F5 reinicia toda a página</li>
            </ul>
            <p style="margin: 10px 0 0 0; font-size: 0.9rem; color: #555;">
                Diferente deste teste, as páginas reais nunca tentam reusar o ProgressBar na mesma página,
                então o bug de display:none não afeta o fluxo normal da aplicação.
            </p>
        </div>
        
        <h2>5. Teste Interativo de Simulação</h2>
        <div class="test-section">
            <p>Simule o comportamento do ProgressBar usado em search.php e load.php:</p>
            <div class="info-box" style="margin-bottom: 15px; background: #fff3cd; border-left-color: #ffc107;">
                <p style="margin: 0; font-size: 0.95rem; color: #856404;">
                    <strong>ℹ️ Nota sobre reuso:</strong> Nas páginas reais (search.php, load.php), após o processamento sempre há redirecionamento para edit.php. 
                    Portanto, o ProgressBar nunca é reutilizado na mesma página. Este teste permite múltiplas execuções para fins de validação.
                </p>
            </div>
            <button onclick="testProgressBar()">▶️ Iniciar Simulação</button>
            <button onclick="resetProgressBar()">🔄 Resetar</button>
            
            <div id="testProgressContainer" style="margin-top: 20px; display: none;">
                <div class="pb_container">
                    <div id="testCarregando">Carregando locais...</div>
                    <div id="testPbText" class="pb_text" style="display: none;">0%</div>
                    <div class="pb_bar">
                        <div id="testProgressBar" class="pb_indeterminate" style="width: 100%;"></div>
                        <div id="testTransparentBar" style="float: left; width: 0%; height: 1.3em;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        dojo.require("dijit.ProgressBar");
        
        console.log('🔍 Teste Progress Bar');
        console.log('Dojo version:', dojo.version);
        console.log('Dojo baseUrl:', dojo.baseUrl);
        console.log('Tema URL:', '<?php echo dojo_theme_url("tundra"); ?>');
        
        function testProgressBar() {
            // Garante que o container está visível
            document.getElementById('testProgressContainer').style.display = 'block';
            
            // Reseta estado inicial
            document.getElementById('testCarregando').innerHTML = 'Carregando locais...';
            document.getElementById('testProgressBar').style.display = 'block'; // Garante visibilidade
            document.getElementById('testProgressBar').setAttribute('class', 'pb_indeterminate');
            document.getElementById('testProgressBar').style.width = '100%';
            document.getElementById('testPbText').style.display = 'none';
            document.getElementById('testTransparentBar').style.width = '0%';
            
            setTimeout(function() {
                // Muda para progresso determinado
                document.getElementById('testProgressBar').setAttribute('class', 'pb_before');
                document.getElementById('testPbText').style.display = 'inline';
                
                var progress = 0;
                var interval = setInterval(function() {
                    progress += 10;
                    document.getElementById('testProgressBar').style.width = progress + '%';
                    document.getElementById('testTransparentBar').style.width = (100 - progress) + '%';
                    document.getElementById('testPbText').innerHTML = progress + '%';
                    
                    if (progress >= 100) {
                        clearInterval(interval);
                        document.getElementById('testCarregando').innerHTML = 'Redirecionando...';
                        document.getElementById('testProgressBar').setAttribute('class', 'pb_indeterminate');
                        document.getElementById('testPbText').style.display = 'none';
                        
                        setTimeout(function() {
                            document.getElementById('testCarregando').innerHTML = '✅ Concluído!';
                            document.getElementById('testProgressBar').style.display = 'none';
                        }, 1000);
                    }
                }, 300);
            }, 2000);
        }
        
        function resetProgressBar() {
            // Reseta completamente o estado do progress bar
            document.getElementById('testProgressContainer').style.display = 'none';
            document.getElementById('testCarregando').innerHTML = 'Carregando locais...';
            document.getElementById('testProgressBar').style.display = 'block';
            document.getElementById('testProgressBar').setAttribute('class', 'pb_indeterminate');
            document.getElementById('testProgressBar').style.width = '100%';
            document.getElementById('testPbText').style.display = 'none';
            document.getElementById('testPbText').innerHTML = '0%';
            document.getElementById('testTransparentBar').style.width = '0%';
        }
        
        // Verifica erros de carregamento de imagens
        window.addEventListener('error', function(e) {
            if (e.target.tagName === 'IMG' || (e.target.style && e.target.style.backgroundImage)) {
                console.error('❌ Erro ao carregar imagem:', e.target);
            }
        }, true);
        
        // Monitora Network para verificar requests
        if (window.performance && window.performance.getEntriesByType) {
            setTimeout(function() {
                var resources = window.performance.getEntriesByType('resource');
                var progressBarImages = resources.filter(function(r) {
                    return r.name.indexOf('progressBar') !== -1;
                });
                
                console.log('=== Diagnóstico de Imagens do ProgressBar ===');
                if (progressBarImages.length > 0) {
                    console.log('✅ Imagens do ProgressBar carregadas:', progressBarImages.length);
                    progressBarImages.forEach(function(img) {
                        console.log('  📦 ' + img.name);
                        console.log('     Status: ' + (img.responseStatus || 'N/A'));
                        console.log('     Tamanho: ' + Math.round(img.transferSize / 1024) + ' KB');
                        console.log('     Tempo: ' + Math.round(img.duration) + ' ms');
                    });
                    
                    // Verifica origem das imagens (local vs CDN)
                    var fromCDN = progressBarImages.every(function(img) {
                        return img.name.indexOf('ajax.googleapis.com') !== -1;
                    });
                    var fromLocal = progressBarImages.every(function(img) {
                        return img.name.indexOf('/js/dijit/') !== -1;
                    });
                    
                    console.log(''); // Linha em branco
                    if (fromCDN) {
                        console.log('✅ Todas as imagens do CDN do Google (ajax.googleapis.com)');
                        console.info('ℹ️ Modo: Produção ou DOJO_SOURCE=cdn');
                    } else if (fromLocal) {
                        console.log('✅ Todas as imagens dos ARQUIVOS LOCAIS (/js/dijit/)');
                        console.info('ℹ️ Modo: Desenvolvimento com DOJO_SOURCE=local');
                    } else {
                        console.warn('⚠️ Origem mista ou desconhecida!');
                        console.info('ℹ️ Verifique a configuração DOJO_SOURCE no .env');
                    }
                } else {
                    console.warn('⚠️ Nenhuma imagem do ProgressBar detectada');
                    console.info('ℹ️ Possíveis causas:');
                    console.info('  1. Imagens já estão no cache do navegador');
                    console.info('  2. CSS ainda não foi aplicado');
                    console.info('  3. Limpe o cache e recarregue (Cmd+Shift+R ou Ctrl+Shift+R)');
                }
                console.log('============================================');
            }, 1500);
        }
    </script>
    
    <footer style="text-align: center; padding: 30px 20px; margin-top: 40px; border-top: 2px solid #e0e0e0;">
        <a href="index.php" 
           onclick="sessionStorage.setItem('debugIndexScrollPos', window.scrollY || document.documentElement.scrollTop);"
           style="display: inline-flex; align-items: center; gap: 8px; color: #3498db; text-decoration: none; padding: 12px 24px; border-radius: 8px; background: #f8f9fa; font-weight: 500; font-size: 14px; transition: all 0.2s; border: 1px solid #dee2e6;" 
           onmouseover="this.style.color='#2980b9'; this.style.background='#e9ecef'; this.style.borderColor='#ced4da';" 
           onmouseout="this.style.color='#3498db'; this.style.background='#f8f9fa'; this.style.borderColor='#dee2e6';">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
            </svg>
            Voltar para Debug
        </a>
    </footer>
</body>
</html>
