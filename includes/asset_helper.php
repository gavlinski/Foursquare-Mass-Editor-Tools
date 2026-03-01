<?php
/**
 * Asset Helper - Carrega versões minificadas em produção
 * 
 * Detecta automaticamente o ambiente e carrega a versão apropriada:
 * - Produção: .min.js (minificado) + CDN para bibliotecas externas
 * - Desenvolvimento: .js (original para debug) + arquivos locais
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
 * - Dojo não é versionado no Git (3000+ arquivos, 15MB)
 * - CDN oferece melhor performance (cache global, HTTP/2, compressão)
 * - Fallback para arquivos locais em desenvolvimento
 */
define('DOJO_VERSION', '1.8.14');
define('DOJO_CDN_BASE', 'https://ajax.googleapis.com/ajax/libs/dojo/' . DOJO_VERSION);

// Fallback para arquivos locais se CDN estiver indisponível
define('DOJO_LOCAL_BASE', '/js/dojo');
define('DIJIT_LOCAL_BASE', '/js/dijit');
define('DOJOX_LOCAL_BASE', '/js/dojox');

// Detecta ambiente
function isProduction() {
    // Método 1: Variável de ambiente
    $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
    if ($env === 'production') {
        return true;
    }
    
    // Método 2: Hostname
    $hostname = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    if (strpos($hostname, 'eliotools.site') !== false) {
        return true;
    }
    
    // Método 3: IP não é localhost
    $server_addr = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
    if ($server_addr !== '127.0.0.1' && $server_addr !== '::1' && !strpos($hostname, 'localhost')) {
        return true;
    }
    
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
 * Em produção: Usa Google CDN (cache global, HTTP/2)
 * Em desenvolvimento: Usa arquivos locais (debug facilitado)
 * 
 * @param array $djConfig Configuração do Dojo
 * @return string URL completa do dojo.js
 */
function dojo_url($djConfig = []) {
    if (isProduction()) {
        // Produção: Google CDN
        return DOJO_CDN_BASE . '/dojo/dojo.js';
    }
    
    // Desenvolvimento: Arquivos locais
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . DOJO_LOCAL_BASE . '/dojo.js')) {
        return DOJO_LOCAL_BASE . '/dojo.js';
    }
    
    // Fallback para CDN se arquivos locais não existirem
    return DOJO_CDN_BASE . '/dojo/dojo.js';
}

/**
 * Imprime tag <script> do Dojo com configuração
 * 
 * @param array $djConfig Configuração do djConfig
 */
function dojo_script($djConfig = ['parseOnLoad' => true]) {
    $url = dojo_url($djConfig);
    
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
    
    // Em produção, adiciona baseUrl para CDN
    if (isProduction()) {
        echo sprintf(
            '<script>var dojoConfig = { %s, baseUrl: "%s/dojo/", packages: [{name: "dijit", location: "../dijit"}, {name: "dojox", location: "../dojox"}] };</script>' . PHP_EOL,
            $config_str,
            DOJO_CDN_BASE
        );
    }
    
    echo sprintf('<script src="%s"></script>' . PHP_EOL, $url);
}

/**
 * Retorna URL do tema Dojo (CSS)
 * 
 * @param string $theme Nome do tema (tundra, claro, nihilo, soria)
 * @return string URL do CSS do tema
 */
function dojo_theme_url($theme = 'tundra') {
    if (isProduction()) {
        return DOJO_CDN_BASE . '/dijit/themes/' . $theme . '/' . $theme . '.css';
    }
    
    // Desenvolvimento: Arquivos locais
    $local_path = DIJIT_LOCAL_BASE . '/themes/' . $theme . '/' . $theme . '.css';
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $local_path)) {
        return $local_path;
    }
    
    // Fallback para CDN
    return DOJO_CDN_BASE . '/dijit/themes/' . $theme . '/' . $theme . '.css';
}

/**
 * Imprime tag <link> do tema Dojo
 * 
 * @param string $theme Nome do tema
 */
function dojo_theme($theme = 'tundra') {
    $url = dojo_theme_url($theme);
    echo sprintf('<link rel="stylesheet" type="text/css" href="%s">' . PHP_EOL, $url);
}

