<?php
/**
 * Analytics Interno - Privacy-Friendly
 * 
 * Coleta métricas básicas de uso sem cookies ou tracking invasivo
 * Totalmente aderente à LGPD/GDPR
 *
 * @category   Analytics
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2012-2026
 * @version    1.0.0
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */

declare(strict_types=1);

// Não exibir output (chamado como include)
if (php_sapi_name() === 'cli') {
    return; // Não executar em CLI
}

// Configurações
define('ANALYTICS_DB_PATH', __DIR__ . '/data/analytics.db');
define('ANALYTICS_ENABLED', true);
define('ANALYTICS_RETENTION_DAYS', 90); // Manter dados por 90 dias

// Verifica se analytics está habilitado
if (!ANALYTICS_ENABLED) {
    return;
}

/**
 * Inicializa o banco de dados SQLite
 */
function initAnalyticsDatabase(): ?PDO {
    try {
        // Cria diretório data/ se não existir
        $dataDir = dirname(ANALYTICS_DB_PATH);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        $pdo = new PDO('sqlite:' . ANALYTICS_DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Cria tabela se não existir
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS pageviews (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                page_url TEXT NOT NULL,
                page_title TEXT,
                referrer TEXT,
                user_hash TEXT NOT NULL,
                user_agent TEXT,
                browser TEXT,
                os TEXT,
                language TEXT,
                timestamp INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        // Índices para performance
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_timestamp ON pageviews(timestamp)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_page_url ON pageviews(page_url)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_user_hash ON pageviews(user_hash)');
        
        return $pdo;
    } catch (Exception $e) {
        error_log('Analytics DB Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Anonimiza IP usando SHA-256 (irreversível)
 */
function anonymizeIP(string $ip): string {
    // Adiciona salt diário para evitar correlação entre dias
    $salt = date('Y-m-d') . 'analytics_salt_4sqmet';
    return hash('sha256', $ip . $salt);
}

/**
 * Detecta informações do navegador
 */
function detectBrowser(string $userAgent): array {
    $browser = 'Unknown';
    $os = 'Unknown';
    
    // Detectar navegador
    if (preg_match('/Firefox\/([\d.]+)/', $userAgent, $m)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Chrome\/([\d.]+)/', $userAgent, $m)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Safari\/([\d.]+)/', $userAgent, $m) && !preg_match('/Chrome/', $userAgent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Edge\/([\d.]+)/', $userAgent, $m)) {
        $browser = 'Edge';
    } elseif (preg_match('/Edg\/([\d.]+)/', $userAgent, $m)) {
        $browser = 'Edge';
    } elseif (preg_match('/OPR\/([\d.]+)/', $userAgent, $m)) {
        $browser = 'Opera';
    }
    
    // Detectar sistema operacional
    if (preg_match('/Windows NT/', $userAgent)) {
        $os = 'Windows';
    } elseif (preg_match('/Mac OS X/', $userAgent)) {
        $os = 'macOS';
    } elseif (preg_match('/Linux/', $userAgent)) {
        $os = 'Linux';
    } elseif (preg_match('/Android/', $userAgent)) {
        $os = 'Android';
    } elseif (preg_match('/iPhone|iPad|iPod/', $userAgent)) {
        $os = 'iOS';
    }
    
    return ['browser' => $browser, 'os' => $os];
}

/**
 * Sanitiza URL removendo parâmetros sensíveis e normalizando o caminho
 */
function sanitizeUrl(string $url): string {
    // Parse URL
    $parsed = parse_url($url);
    $path = $parsed['path'] ?? '/';
    
    // Remove prefixo de diretório base (ex: /4sqmet/main.php → /main.php)
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = dirname($scriptName);
    if ($basePath !== '/' && $basePath !== '.' && strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
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
            'pass'            // Password alternative
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

/**
 * Registra pageview
 */
function trackPageview(): void {
    $pdo = initAnalyticsDatabase();
    if (!$pdo) {
        return; // Falha silenciosa
    }
    
    // Coleta dados
    $rawUrl = $_SERVER['REQUEST_URI'] ?? '/';
    $pageUrl = sanitizeUrl($rawUrl);
    $pageTitle = ''; // Pode ser preenchido via JavaScript se necessário
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    
    // Anonimiza IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userHash = anonymizeIP($ip);
    
    // Detecta browser/OS
    $browserInfo = detectBrowser($userAgent);
    
    // Filtra referrer (remove domínio próprio)
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    if (!empty($referrer) && strpos($referrer, $currentHost) !== false) {
        $referrer = ''; // Tráfego interno
    }
    
    // Limita tamanhos
    $pageUrl = substr($pageUrl, 0, 500);
    $referrer = substr($referrer, 0, 500);
    $userAgent = substr($userAgent, 0, 500);
    $language = substr(explode(',', $language)[0], 0, 10); // Primeira língua apenas
    
    try {
        $stmt = $pdo->prepare('
            INSERT INTO pageviews 
            (page_url, page_title, referrer, user_hash, user_agent, browser, os, language, timestamp)
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $pageUrl,
            $pageTitle,
            $referrer,
            $userHash,
            $userAgent,
            $browserInfo['browser'],
            $browserInfo['os'],
            $language,
            time()
        ]);
        
        // Limpeza periódica (1% de chance a cada pageview)
        if (random_int(1, 100) === 1) {
            cleanupOldData($pdo);
        }
    } catch (Exception $e) {
        error_log('Analytics tracking error: ' . $e->getMessage());
    }
}

/**
 * Remove dados antigos conforme política de retenção
 */
function cleanupOldData(PDO $pdo): void {
    try {
        $cutoffTime = time() - (ANALYTICS_RETENTION_DAYS * 86400);
        $stmt = $pdo->prepare('DELETE FROM pageviews WHERE timestamp < ?');
        $stmt->execute([$cutoffTime]);
        
        $deleted = $stmt->rowCount();
        if ($deleted > 0) {
            error_log("Analytics cleanup: {$deleted} registros antigos removidos");
        }
    } catch (Exception $e) {
        error_log('Analytics cleanup error: ' . $e->getMessage());
    }
}

// Executa tracking automaticamente quando incluído
trackPageview();
