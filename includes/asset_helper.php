<?php
/**
 * Asset Helper - Carrega versões minificadas em produção
 * 
 * Detecta automaticamente o ambiente e carrega a versão apropriada:
 * - Produção: .min.js (minificado)
 * - Desenvolvimento: .js (original para debug)
 * 
 * @package ElioTools
 * @version 3.0.0
 */

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
