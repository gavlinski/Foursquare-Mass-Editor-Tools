<?php
/**
 * Asset Helper - Carrega versões minificadas em produção
 * 
 * Detecta automaticamente o ambiente e carrega a versão apropriada:
 * - Produção: .min.js (minificado) + CDN Google com fallback local para Dojo/Dijit/Dojox
 * - Desenvolvimento: .js (original para debug) + Local OU CDN baseado em DOJO_SOURCE
 * 
 * IMPORTANTE: Configuração do Dojo Toolkit
 * 
 * PRODUÇÃO (eliotools.site):
 *   - Prioriza CDN do Google (ajax.googleapis.com)
 *   - Usa fallback automático para arquivos locais quando necessário
 *   - Ignora DOJO_SOURCE do .env
 *   - Garante performance e cache global
 * 
 * DESENVOLVIMENTO (localhost):
 *   - Respeita variável DOJO_SOURCE do arquivo .env:
 *     • DOJO_SOURCE=local  → Usa arquivos locais (js/dojo/, js/dijit/, js/dojox/)
 *     • DOJO_SOURCE=cdn    → Usa CDN (Google)
 *   - Configure via: ./dev.sh config
 *   - Fallback automático para CDN se arquivos locais não existirem
 * 
 * USO 100% CONSISTENTE:
 *   - Se configurado para local: TODOS os recursos vêm de arquivos locais
 *   - Se configurado para CDN: usa Google
 *   - CSS do ProgressBar gerado dinamicamente para manter consistência
 * 
 * @package ElioTools
 * @version 3.0.0
 */

// ============================================================
// Configuração de CDN para Bibliotecas Externas
// ============================================================

/**
 * URLs de CDN para Dojo Toolkit 1.8.14
 * 
 * Decisão de Design:
 * - Em PRODUÇÃO: SEMPRE CDN (performance, cache global, HTTP/2)
 * - Em DESENVOLVIMENTO: Configurável via DOJO_SOURCE no .env
 *   • local: Arquivos locais (js/dojo/, js/dijit/, js/dojox/)
 *   • cdn: Google CDN (padrão)
 * - Arquivos locais NÃO estão no Git (3000+ arquivos, 15MB)
 * - Use ./dev.sh config para escolher a fonte
 */
define('DOJO_VERSION', '1.8.14');
define('DOJO_CDN_BASE', 'https://ajax.googleapis.com/ajax/libs/dojo/' . DOJO_VERSION);
define('DOJO_APP_LOCALE', 'pt-br');

// Paths locais (usados apenas se DOJO_SOURCE=local em desenvolvimento)
define('DOJO_LOCAL_BASE', '/js/dojo');
define('DIJIT_LOCAL_BASE', '/js/dijit');
define('DOJOX_LOCAL_BASE', '/js/dojox');

/**
 * Resolve o DOCUMENT_ROOT com fallback seguro para execução em CLI.
 *
 * @return string
 */
function getDocumentRootPath() {
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if (!empty($docRoot)) {
        return rtrim($docRoot, '/');
    }

    return dirname(__DIR__);
}

/**
 * Verifica se o fallback local completo do Dojo está disponível.
 *
 * @return bool
 */
function hasLocalDojoAssets() {
    $docRoot = getDocumentRootPath();

    $requiredFiles = [
        $docRoot . DOJO_LOCAL_BASE . '/dojo.js',
        $docRoot . DIJIT_LOCAL_BASE . '/themes/tundra/tundra.css',
        $docRoot . DOJOX_LOCAL_BASE . '/form/Uploader.js',
    ];

    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            return false;
        }
    }

    return true;
}

// Detecta ambiente
function isProduction() {
    // Método 1: Variável de ambiente
    $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
    if ($env === 'production') {
        return true;
    }
    
    // Método 2: Hostname de produção
    $hostname = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    if (strpos($hostname, 'eliotools.site') !== false) {
        return true;
    }
    
    // Método 3: Detecta desenvolvimento (localhost, 127.0.0.1, ::1, IPs Docker)
    // Se qualquer uma dessas condições for TRUE, é desenvolvimento (não produção)
    if (strpos($hostname, 'localhost') !== false) {
        return false; // É localhost, definitivamente desenvolvimento
    }
    
    $server_addr = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
    if ($server_addr === '127.0.0.1' || $server_addr === '::1') {
        return false; // É IP loopback, desenvolvimento
    }
    
    // IPs privados (Docker, redes internas)
    if (preg_match('/^(127\.|10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.)/', $server_addr)) {
        return false; // É rede privada, desenvolvimento
    }
    
    // Se chegou aqui e não é nenhum dos casos acima, é produção
    return true;
}

/**
 * Detecta se deve usar Dojo local ou CDN
 * 
 * Lógica:
 * - Produção: SEMPRE CDN
 * - Desenvolvimento: Respeita DOJO_SOURCE do .env (local ou cdn)
 * 
 * @return bool True se deve usar arquivos locais, False se deve usar CDN
 */
function useLocalDojo() {
    // Produção SEMPRE usa CDN
    if (isProduction()) {
        return false;
    }
    
    // Desenvolvimento: lê preferência do .env
    $dojoSource = getenv('DOJO_SOURCE') ?: ($_ENV['DOJO_SOURCE'] ?? 'cdn');
    
    // Se configurado para local, verifica se arquivos existem
    if ($dojoSource === 'local') {
        // Só usa local se arquivos essenciais existirem
        if (hasLocalDojoAssets()) {
            return true;
        }
        
        // Fallback para CDN se arquivos não existirem
        error_log('AVISO: DOJO_SOURCE=local mas arquivos locais do Dojo não estão completos.');
        return false;
    }
    
    // Padrão: CDN
    return false;
}

/**
 * Retorna o caminho correto do asset (minificado ou não)
 * 
 * @param string $path Caminho do arquivo JS (sem .min)
 * @return string Caminho do arquivo apropriado
 */
function asset($path) {
    if (isProduction()) {
        // Em produção, usa versão minificada
        $minified = str_replace('.js', '.min.js', $path);
        
        // Verifica se arquivo minificado existe
        $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($minified, '/');
        if (file_exists($file_path)) {
            return $minified;
        }
        
        // Fallback para versão não minificada
        return $path;
    }
    
    // Em desenvolvimento, usa versão original
    return $path;
}

/**
 * Imprime tag <script> com asset apropriado
 * 
 * @param string $path Caminho do arquivo JS
 * @param array $attributes Atributos adicionais
 */
function script($path, $attributes = []) {
    $src = asset($path);
    $attrs = '';
    
    foreach ($attributes as $key => $value) {
        $attrs .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
    }
    
    echo sprintf('<script src="%s"%s></script>' . PHP_EOL, $src, $attrs);
}

/**
 * Retorna array de scripts com versões apropriadas
 * 
 * @param array $scripts Lista de caminhos de scripts
 * @return array Scripts com caminhos corretos
 */
function assets($scripts) {
    return array_map('asset', $scripts);
}

/**
 * Versão do asset para cache busting
 * 
 * @param string $path Caminho do arquivo
 * @return string Caminho com versão
 */
function asset_version($path) {
    $asset_path = asset($path);
    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($asset_path, '/');
    
    if (file_exists($file_path)) {
        $version = filemtime($file_path);
        return $asset_path . '?v=' . $version;
    }
    
    return $asset_path;
}

/**
 * Imprime tag <script> com versão para cache busting
 */
function script_versioned($path, $attributes = []) {
    $src = asset_version($path);
    $attrs = '';
    
    foreach ($attributes as $key => $value) {
        $attrs .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
    }
    
    echo sprintf('<script src="%s"%s></script>' . PHP_EOL, $src, $attrs);
}

// ============================================================
// Dojo Toolkit CDN Management
// ============================================================

/**
 * Retorna URL do Dojo principal (dojo.js)
 * 
 * Lógica de decisão:
 * - Produção: SEMPRE CDN
 * - Desenvolvimento: Respeita DOJO_SOURCE do .env
 * 
 * @param array $djConfig Configuração do Dojo
 * @return string URL completa do dojo.js
 */
function dojo_url($djConfig = []) {
    // Verifica se deve usar arquivos locais
    if (useLocalDojo()) {
        return DOJO_LOCAL_BASE . '/dojo.js';
    }
    
    // Usa CDN (padrão e produção)
    return DOJO_CDN_BASE . '/dojo/dojo.js';
}

/**
 * Imprime tag <script> do Dojo com configuração
 * 
 * Lógica:
 * - Arquivos locais: Usa data-dojo-config inline
 * - CDN: Usa dojoConfig global + baseUrl configurado
 * - Em produção: se CDN falhar, usa fallback local quando disponível
 * - DOJO_FORCE_FALLBACK: Força uso de fallback (para testes)
 * 
 * @param array $djConfig Configuração do djConfig
 */
function dojo_script($djConfig = ['parseOnLoad' => true]) {
    $url = dojo_url($djConfig);
    $usingLocal = useLocalDojo();
    $forceFallback = getenv('DOJO_FORCE_FALLBACK') ?: ($_ENV['DOJO_FORCE_FALLBACK'] ?? false);

    if (!isset($djConfig['locale'])) {
        $djConfig['locale'] = DOJO_APP_LOCALE;
    }
    
    // Converte array para string de configuração
    $config_pairs = [];
    foreach ($djConfig as $key => $value) {
        if (is_bool($value)) {
            $config_pairs[] = "{$key}: " . ($value ? 'true' : 'false');
        } elseif (is_numeric($value)) {
            $config_pairs[] = "{$key}: {$value}";
        } else {
            $config_pairs[] = "{$key}: '" . addslashes($value) . "'";
        }
    }
    $config_str = implode(', ', $config_pairs);
    
    if ($usingLocal) {
        // Arquivos locais: configuração inline no atributo data-dojo-config
        echo sprintf('<script data-dojo-config="%s" src="%s"></script>' . PHP_EOL, $config_str, $url);
    } else {
        $hasLocalFallback = hasLocalDojoAssets();
        $hasLocalFallbackJs = $hasLocalFallback ? 'true' : 'false';

        // Se DOJO_FORCE_FALLBACK=true, pula o CDN principal e testa fallback local diretamente.
        if ($forceFallback && $hasLocalFallback) {
            echo '<!-- DOJO_FORCE_FALLBACK=true: Carregando APENAS fallback local -->' . PHP_EOL;
            echo '<script>' . PHP_EOL;
            echo 'window.dojoConfig = {' . PHP_EOL;
            echo '    ' . str_replace(', ', ',' . PHP_EOL . '    ', $config_str) . ',' . PHP_EOL;
            echo '    baseUrl: "' . DOJO_LOCAL_BASE . '/",' . PHP_EOL;
            echo '    packages: [' . PHP_EOL;
            echo '        {name: "dojo", location: "."},' . PHP_EOL;
            echo '        {name: "dijit", location: "' . DIJIT_LOCAL_BASE . '"},' . PHP_EOL;
            echo '        {name: "dojox", location: "' . DOJOX_LOCAL_BASE . '"}' . PHP_EOL;
            echo '    ]' . PHP_EOL;
            echo '};' . PHP_EOL;
            echo '</script>' . PHP_EOL;
            echo sprintf('<script src="%s/dojo.js"></script>' . PHP_EOL, DOJO_LOCAL_BASE);
            echo '<!-- Fallback local carregado diretamente (sem tentativa de primário) -->' . PHP_EOL;
        } else {
            if ($forceFallback && !$hasLocalFallback) {
                echo '<!-- DOJO_FORCE_FALLBACK=true, mas fallback local indisponivel: mantendo CDN primario -->' . PHP_EOL;
            }
            echo sprintf(
                '<script>(function(){function applyDojoRoots(roots){window.dojoConfig={ %s, baseUrl: roots.dojo + "/", packages: [{name: "dojo", location: "."}, {name: "dijit", location: roots.dijit}, {name: "dojox", location: roots.dojox}] };}window.__FMET_DOJO_PRIMARY_ROOTS__={dojo:"%s/dojo",dijit:"%s/dijit",dojox:"%s/dojox"};window.__FMET_DOJO_LOCAL_ROOTS__={dojo:"%s",dijit:"%s",dojox:"%s"};window.__FMET_HAS_LOCAL_DOJO_FALLBACK__=%s;applyDojoRoots(window.__FMET_DOJO_PRIMARY_ROOTS__);window.__FMET_APPLY_DOJO_ROOTS__=applyDojoRoots;})();</script>' . PHP_EOL,
                $config_str,
                DOJO_CDN_BASE,
                DOJO_CDN_BASE,
                DOJO_CDN_BASE,
                DOJO_LOCAL_BASE,
                DIJIT_LOCAL_BASE,
                DOJOX_LOCAL_BASE,
                $hasLocalFallbackJs
            );
            echo sprintf('<script src="%s"></script>' . PHP_EOL, $url);
            echo '<script>if(typeof window.dojo==="undefined"){if(window.__FMET_HAS_LOCAL_DOJO_FALLBACK__){console.warn("FMET: Falha ao carregar Dojo do CDN principal, ativando fallback local.");window.__FMET_APPLY_DOJO_ROOTS__(window.__FMET_DOJO_LOCAL_ROOTS__);document.write(\'<script src="' . DOJO_LOCAL_BASE . '/dojo.js"><\\/script>\');}else{console.error("FMET: Falha no CDN principal e fallback local indisponivel.");window.__FMET_DOJO_FALLBACK_FAILED__=true;}}</script>' . PHP_EOL;
        }
    }
}

/**
 * Retorna URL do tema Dojo (CSS)
 * 
 * Lógica:
 * - Produção: SEMPRE CDN
 * - Desenvolvimento: Respeita DOJO_SOURCE do .env
 * 
 * @param string $theme Nome do tema (tundra, claro, nihilo, soria)
 * @return string URL do CSS do tema
 */
function dojo_theme_url($theme = 'tundra') {
    // Verifica se deve usar arquivos locais
    if (useLocalDojo()) {
        return DIJIT_LOCAL_BASE . '/themes/' . $theme . '/' . $theme . '.css';
    }
    
    // Usa CDN (padrão e produção)
    return DOJO_CDN_BASE . '/dijit/themes/' . $theme . '/' . $theme . '.css';
}

/**
 * Imprime tag <link> do tema Dojo
 * 
 * Automaticamente inclui CSS dinâmico do ProgressBar
 * para garantir caminhos corretos (local ou CDN)
 * 
 * @param string $theme Nome do tema
 */
function dojo_theme($theme = 'tundra') {
    $url = dojo_theme_url($theme);
    $usingLocal = useLocalDojo();
    $hasLocalFallback = hasLocalDojoAssets();

    if (!$usingLocal && $hasLocalFallback) {
        $localThemeUrl = DIJIT_LOCAL_BASE . '/themes/' . $theme . '/' . $theme . '.css';
        echo sprintf(
            '<link id="fmet-dojo-theme" rel="stylesheet" type="text/css" href="%s" onerror="this.onerror=null;this.href=\'%s\';">' . PHP_EOL,
            $url,
            $localThemeUrl
        );
    } else {
        echo sprintf('<link rel="stylesheet" type="text/css" href="%s">' . PHP_EOL, $url);
    }
    
    // Injeta CSS dinâmico do ProgressBar
    dojo_progressbar_css($theme);
}

/**
 * Retorna caminho base para imagens do Dojo theme
 * 
 * Usado para CSS dinâmico (ProgressBar)
 * 
 * @param string $theme Nome do tema
 * @return string URL base das imagens do tema
 */
function dojo_theme_images_base($theme = 'tundra') {
    if (useLocalDojo()) {
        return DIJIT_LOCAL_BASE . '/themes/' . $theme . '/images/';
    }

    if (isProduction() && hasLocalDojoAssets()) {
        return DIJIT_LOCAL_BASE . '/themes/' . $theme . '/images/';
    }

    return DOJO_CDN_BASE . '/dijit/themes/' . $theme . '/images/';
}

/**
 * Imprime CSS dinâmico para ProgressBar
 * 
 * Sobrescreve as regras do estilo.css com caminhos corretos
 * baseados na configuração (local ou CDN)
 * 
 * @param string $theme Nome do tema
 */
function dojo_progressbar_css($theme = 'tundra') {
    $imagesBase = dojo_theme_images_base($theme);
    $sourceLabel = useLocalDojo() ? 'LOCAL' : ((isProduction() && hasLocalDojoAssets()) ? 'LOCAL (resiliencia em producao)' : 'CDN');
    
    echo '<style>' . PHP_EOL;
    echo '/* ProgressBar - Imagens do Dojo (' . $sourceLabel . ') */' . PHP_EOL;
    echo '.pb_bar {' . PHP_EOL;
    echo '    background: #ffffff url("' . $imagesBase . 'progressBarEmpty.png") repeat-x center center;' . PHP_EOL;
    echo '}' . PHP_EOL;
    echo '.pb_before {' . PHP_EOL;
    echo '    background: #abd6ff url("' . $imagesBase . 'progressBarFull.png") repeat-x center center;' . PHP_EOL;
    echo '}' . PHP_EOL;
    echo '.pb_indeterminate {' . PHP_EOL;
    echo '    background: #fff url("' . $imagesBase . 'progressBarAnim.gif") repeat-x center center;' . PHP_EOL;
    echo '}' . PHP_EOL;
    echo '</style>' . PHP_EOL;
}

