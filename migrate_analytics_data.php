<?php
/**
 * Script de Migração - Analytics Data Cleanup
 * 
 * Sanitiza URLs antigas no banco de dados:
 * - Remove parâmetros sensíveis (code, oauth_token, etc)
 * - Normaliza prefixos de diretório (/4sqmet/ → /)
 * - Remove registros inválidos
 * 
 * Uso: php migrate_analytics_data.php
 */

declare(strict_types=1);

const ANALYTICS_TRACKED_PATHS = [
    '/',
    '/index.php',
    '/main.php',
    '/edit.php',
    '/edit_csv.php',
    '/flag_csv.php',
    '/privacy.php'
];

// Função sanitizeUrl standalone (não depende de $_SERVER no contexto CLI)
function sanitizeUrlStandalone(string $url, string $baseDir = '/4sqmet'): string {
    // Parse URL
    $parsed = parse_url($url);
    $path = $parsed['path'] ?? '/';
    
    // Remove prefixo de diretório base (ex: /4sqmet/main.php → /main.php)
    if ($baseDir !== '/' && strpos($path, $baseDir) === 0) {
        $path = substr($path, strlen($baseDir));
        if ($path === '' || $path === false) {
            $path = '/';
        }
        // Garante que começa com /
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
    }
    
    // Se há query string, filtra parâmetros sensíveis
    if (isset($parsed['query']) && $parsed['query'] !== '') {
        parse_str($parsed['query'], $params);
        
        // Lista de parâmetros sensíveis que não devem ser gravados
        $sensitiveParams = [
            'code',           // OAuth code
            'oauth_token',    // OAuth token
            'access_token',   // Access token
            'token',          // Generic token
            'auth',           // Auth parameter
            'key',            // API key
            'secret',         // Secret
            'password',       // Password
            'pwd',            // Password abbreviation
            'pass',           // Password alternative
            'logout',         // Logout flag (não é sensível mas desnecessário)
            'error'           // Error parameter (não precisa trackear)
        ];
        
        // Remove parâmetros sensíveis
        foreach ($sensitiveParams as $param) {
            unset($params[$param]);
        }
        
        // Reconstrói query string sem parâmetros sensíveis
        if (!empty($params)) {
            $path .= '?' . http_build_query($params);
        }
    }
    
    return $path;
}

function isTrackedPagePathStandalone(string $path): bool {
    return in_array($path, ANALYTICS_TRACKED_PATHS, true);
}

function isIgnoredUserAgentStandalone(string $userAgent): bool {
    $userAgent = trim($userAgent);
    if ($userAgent === '') {
        return true;
    }

    $ignoredSignatures = [
        'DigitalOcean Uptime Probe',
        'Qualys',
        'SSL Labs',
        'ssllabs',
        'Go-http-client/',
        'curl/',
        'compatible; Odin;',
        'Palo Alto Networks',
        'Cortex-Xpanse'
    ];

    foreach ($ignoredSignatures as $signature) {
        if (stripos($userAgent, $signature) !== false) {
            return true;
        }
    }

    return false;
}

function shouldDeleteRecord(string $pageUrl, string $userAgent): bool {
    if (isIgnoredUserAgentStandalone($userAgent)) {
        return true;
    }

    $path = parse_url($pageUrl, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        $path = '/';
    }

    return !isTrackedPagePathStandalone($path);
}

define('ANALYTICS_DB_PATH', __DIR__ . '/data/analytics.db');

echo "🔧 Migrando dados do analytics...\n\n";

try {
    $pdo = new PDO('sqlite:' . ANALYTICS_DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Lista URLs únicas ANTES da migração
    echo "📊 URLs únicas ANTES da migração:\n";
    $stmt = $pdo->query('SELECT DISTINCT page_url, COUNT(*) as count FROM pageviews GROUP BY page_url ORDER BY count DESC');
    $urlsBefore = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($urlsBefore as $row) {
        echo "  - {$row['page_url']} ({$row['count']} registros)\n";
    }
    echo "\n";
    
    // 2. Busca TODOS os registros
    echo "🔍 Processando registros...\n";
    $stmt = $pdo->query('SELECT id, page_url, user_agent FROM pageviews');
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    $deleted = 0;
    $unchanged = 0;
    
    foreach ($records as $record) {
        $oldUrl = $record['page_url'];
        $userAgent = $record['user_agent'] ?? '';
        
        // Remove backslash inválido
        if ($oldUrl === '\\') {
            $stmt = $pdo->prepare('DELETE FROM pageviews WHERE id = ?');
            $stmt->execute([$record['id']]);
            $deleted++;
            echo "  ❌ Removido registro inválido: \\ (ID: {$record['id']})\n";
            continue;
        }
        
        // Aplica sanitização
        $newUrl = sanitizeUrlStandalone($oldUrl);
        
        if (shouldDeleteRecord($newUrl, $userAgent)) {
            $stmt = $pdo->prepare('DELETE FROM pageviews WHERE id = ?');
            $stmt->execute([$record['id']]);
            $deleted++;
            echo "  ❌ Removido registro de ruído: {$oldUrl} (ID: {$record['id']})\n";
        } elseif ($newUrl !== $oldUrl) {
            $stmt = $pdo->prepare('UPDATE pageviews SET page_url = ? WHERE id = ?');
            $stmt->execute([$newUrl, $record['id']]);
            $updated++;
            echo "  ✅ Atualizado: {$oldUrl} → {$newUrl}\n";
        } else {
            $unchanged++;
        }
    }
    
    echo "\n";
    echo "📈 Estatísticas:\n";
    echo "  - Total de registros: " . count($records) . "\n";
    echo "  - Atualizados: {$updated}\n";
    echo "  - Removidos: {$deleted}\n";
    echo "  - Inalterados: {$unchanged}\n";
    echo "\n";
    
    // 3. Lista URLs únicas DEPOIS da migração
    echo "📊 URLs únicas DEPOIS da migração:\n";
    $stmt = $pdo->query('SELECT DISTINCT page_url, COUNT(*) as count FROM pageviews GROUP BY page_url ORDER BY count DESC');
    $urlsAfter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($urlsAfter as $row) {
        echo "  - {$row['page_url']} ({$row['count']} registros)\n";
    }
    echo "\n";
    
    // 4. Vacuum para otimizar o banco
    echo "🗜️  Otimizando banco de dados...\n";
    $pdo->exec('VACUUM');
    
    echo "✨ Migração concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
