<?php
/**
 * Analytics Dashboard - Visualização de Métricas
 * 
 * Dashboard simples para visualizar estatísticas de uso
 * Requer autenticação
 *
 * @category   Analytics
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2012-2026
 * @version    1.0.0
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */

declare(strict_types=1);

// Anti-cache headers
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

use ElioTools\Security\SessionManager;

// Verifica autenticação
$sessionManager = new SessionManager();
$sessionManager->start();

$oauth_token = $sessionManager->get('oauth_token');
if (!$oauth_token) {
    header('Location: index.php');
    exit;
}

// Configurações
define('ANALYTICS_DB_PATH', __DIR__ . '/data/analytics.db');

/**
 * Conecta ao banco de dados
 */
function getAnalyticsDB(): ?PDO {
    try {
        if (!file_exists(ANALYTICS_DB_PATH)) {
            return null;
        }
        
        $pdo = new PDO('sqlite:' . ANALYTICS_DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (Exception $e) {
        error_log('Analytics Dashboard DB Error: ' . $e->getMessage());
        return null;
    }
}

// Coleta estatísticas
$pdo = getAnalyticsDB();
$stats = [
    'total_pageviews' => 0,
    'unique_visitors' => 0,
    'top_pages' => [],
    'top_referrers' => [],
    'browsers' => [],
    'operating_systems' => [],
    'daily_views' => []
];

if ($pdo) {
    try {
        // Período selecionado
        $period = $_GET['period'] ?? '30';
        $periodDays = (int)$period;
        $cutoffTime = time() - ($periodDays * 86400);
        
        // Total de pageviews
        $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM pageviews WHERE timestamp >= ?');
        $stmt->execute([$cutoffTime]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_pageviews'] = (int)($result['total'] ?? 0);
        
        // Visitantes únicos
        $stmt = $pdo->prepare('SELECT COUNT(DISTINCT user_hash) as unique_visitors FROM pageviews WHERE timestamp >= ?');
        $stmt->execute([$cutoffTime]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['unique_visitors'] = (int)($result['unique_visitors'] ?? 0);
        
        // Páginas mais visitadas
        $stmt = $pdo->prepare('
            SELECT page_url, COUNT(*) as views 
            FROM pageviews 
            WHERE timestamp >= ?
            GROUP BY page_url 
            ORDER BY views DESC 
            LIMIT 10
        ');
        $stmt->execute([$cutoffTime]);
        $stats['top_pages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Principais referrers
        $stmt = $pdo->prepare('
            SELECT referrer, COUNT(*) as count 
            FROM pageviews 
            WHERE timestamp >= ? AND referrer != ""
            GROUP BY referrer 
            ORDER BY count DESC 
            LIMIT 10
        ');
        $stmt->execute([$cutoffTime]);
        $stats['top_referrers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Navegadores
        $stmt = $pdo->prepare('
            SELECT browser, COUNT(*) as count 
            FROM pageviews 
            WHERE timestamp >= ?
            GROUP BY browser 
            ORDER BY count DESC
        ');
        $stmt->execute([$cutoffTime]);
        $stats['browsers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Sistemas operacionais
        $stmt = $pdo->prepare('
            SELECT os, COUNT(*) as count 
            FROM pageviews 
            WHERE timestamp >= ?
            GROUP BY os 
            ORDER BY count DESC
        ');
        $stmt->execute([$cutoffTime]);
        $stats['operating_systems'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Pageviews por dia (últimos 30 dias)
        $stmt = $pdo->prepare('
            SELECT 
                date(timestamp, "unixepoch", "localtime") as day,
                COUNT(*) as views
            FROM pageviews 
            WHERE timestamp >= ?
            GROUP BY day
            ORDER BY day ASC
        ');
        $stmt->execute([$cutoffTime]);
        $stats['daily_views'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log('Analytics query error: ' . $e->getMessage());
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<title>Analytics Dashboard - Elio Tools</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<style>
:root {
    --bg-primary: #0f0f1e;
    --bg-gradient-1: rgba(102, 126, 234, 0.15);
    --bg-gradient-2: rgba(118, 75, 162, 0.15);
    --text-primary: #ffffff;
    --text-secondary: #b0b0c0;
    --text-tertiary: #a0a0b0;
    --text-muted: #707080;
    --brand-color: #667eea;
    --card-bg: rgba(255, 255, 255, 0.03);
    --card-border: rgba(255, 255, 255, 0.08);
    --card-hover-bg: rgba(255, 255, 255, 0.05);
    --footer-border: rgba(255, 255, 255, 0.08);
}

@media (prefers-color-scheme: light) {
    :root {
        --bg-primary: #f5f8fa;
        --bg-gradient-1: rgba(102, 126, 234, 0.05);
        --bg-gradient-2: rgba(118, 75, 162, 0.05);
        --text-primary: #2c3e50;
        --text-secondary: #5a6c7d;
        --text-tertiary: #7f8c8d;
        --text-muted: #95a5a6;
        --brand-color: #5851db;
        --card-bg: #ffffff;
        --card-border: #e0e6ed;
        --card-hover-bg: #f8f9fa;
        --footer-border: #e0e6ed;
    }
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    min-height: 100vh;
    background: var(--bg-primary);
    color: var(--text-primary);
    position: relative;
}

body:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 30%, var(--bg-gradient-1) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, var(--bg-gradient-2) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
    position: relative;
    z-index: 1;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    flex-wrap: wrap;
    gap: 20px;
}

h1 {
    font-size: 32px;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea 0%, #a991ff 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.period-selector {
    display: flex;
    gap: 10px;
    align-items: center;
}

.period-selector select {
    padding: 10px 15px;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 14px;
    cursor: pointer;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 8px;
    color: var(--text-primary);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s ease;
}

.back-link:hover {
    background: var(--card-hover-bg);
    border-color: var(--brand-color);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    padding: 25px;
}

.stat-value {
    font-size: 36px;
    font-weight: 700;
    color: var(--brand-color);
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.chart-section {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
}

.chart-section h2 {
    font-size: 20px;
    margin-bottom: 20px;
    color: var(--text-primary);
}

.chart-bar {
    margin-bottom: 15px;
}

.chart-bar-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    font-size: 14px;
    color: var(--text-secondary);
}

.chart-bar-track {
    height: 8px;
    background: var(--card-border);
    border-radius: 4px;
    overflow: hidden;
}

.chart-bar-fill {
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #a991ff 100%);
    border-radius: 4px;
    transition: width 0.3s ease;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th {
    text-align: left;
    padding: 12px;
    border-bottom: 2px solid var(--card-border);
    color: var(--text-primary);
    font-weight: 600;
    font-size: 14px;
}

table td {
    padding: 12px;
    border-bottom: 1px solid var(--card-border);
    color: var(--text-secondary);
    font-size: 14px;
}

table tr:hover {
    background: var(--card-hover-bg);
}

.no-data {
    text-align: center;
    padding: 40px;
    color: var(--text-muted);
    font-size: 16px;
}

@media (max-width: 768px) {
    h1 { font-size: 24px; }
    .stats-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px; margin-bottom: 6px;"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>Analytics Dashboard</h1>
        <div style="display: flex; gap: 15px; align-items: center;">
            <div class="period-selector">
                <label for="period" style="color: var(--text-secondary); font-size: 14px;">Período:</label>
                <select id="period" onchange="window.location.href='?period='+this.value">
                    <option value="7" <?= $period === '7' ? 'selected' : '' ?>>Últimos 7 dias</option>
                    <option value="30" <?= $period === '30' ? 'selected' : '' ?>>Últimos 30 dias</option>
                    <option value="90" <?= $period === '90' ? 'selected' : '' ?>>Últimos 90 dias</option>
                </select>
            </div>
            <a href="main.php" class="back-link">
                ← Voltar
            </a>
        </div>
    </div>

    <?php if (!$pdo): ?>
        <div class="no-data">
            ⚠️ Banco de dados de analytics não encontrado.<br>
            Os dados serão coletados automaticamente conforme o uso.
        </div>
    <?php elseif (empty($stats['total_pageviews'])): ?>
        <div class="no-data">
            📭 Nenhum dado coletado no período selecionado.
        </div>
    <?php else: ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= number_format($stats['total_pageviews'], 0, ',', '.') ?></div>
            <div class="stat-label">Pageviews</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($stats['unique_visitors'], 0, ',', '.') ?></div>
            <div class="stat-label">Visitantes Únicos</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['unique_visitors'] > 0 ? number_format($stats['total_pageviews'] / $stats['unique_visitors'], 1, ',', '.') : '0' ?></div>
            <div class="stat-label">Páginas / Visitante</div>
        </div>
    </div>

    <?php if (!empty($stats['daily_views'])): ?>
    <div class="chart-section">
        <h2><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px; margin-bottom: 2px;"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>Pageviews por Dia</h2>
        <?php 
        $maxViews = max(array_column($stats['daily_views'], 'views'));
        foreach ($stats['daily_views'] as $day): 
            $percentage = $maxViews > 0 ? ($day['views'] / $maxViews) * 100 : 0;
        ?>
        <div class="chart-bar">
            <div class="chart-bar-label">
                <span><?= date('d/m/Y', strtotime($day['day'])) ?></span>
                <span><strong><?= $day['views'] ?></strong> views</span>
            </div>
            <div class="chart-bar-track">
                <div class="chart-bar-fill" style="width: <?= $percentage ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($stats['top_pages'])): ?>
    <div class="chart-section">
        <h2><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px; margin-bottom: 4px;"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>Páginas Mais Visitadas</h2>
        <table>
            <thead>
                <tr>
                    <th>Página</th>
                    <th style="text-align: right;">Visualizações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['top_pages'] as $page): ?>
                <tr>
                    <td><code><?= htmlspecialchars($page['page_url']) ?></code></td>
                    <td style="text-align: right;"><strong><?= $page['views'] ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($stats['top_referrers'])): ?>
    <div class="chart-section">
        <h2><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px; margin-bottom: 5px;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>Principais Referrers</h2>
        <table>
            <thead>
                <tr>
                    <th>Origem</th>
                    <th style="text-align: right;">Visitas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['top_referrers'] as $ref): ?>
                <tr>
                    <td><?= htmlspecialchars(parse_url($ref['referrer'], PHP_URL_HOST) ?: $ref['referrer']) ?></td>
                    <td style="text-align: right;"><strong><?= $ref['count'] ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
        <?php if (!empty($stats['browsers'])): ?>
        <div class="chart-section">
            <h2><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px; margin-bottom: 3px;"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>Navegadores</h2>
            <?php 
            $totalBrowsers = array_sum(array_column($stats['browsers'], 'count'));
            foreach ($stats['browsers'] as $browser): 
                $percentage = $totalBrowsers > 0 ? ($browser['count'] / $totalBrowsers) * 100 : 0;
            ?>
            <div class="chart-bar">
                <div class="chart-bar-label">
                    <span><?= htmlspecialchars($browser['browser']) ?></span>
                    <span><strong><?= number_format($percentage, 1) ?>%</strong></span>
                </div>
                <div class="chart-bar-track">
                    <div class="chart-bar-fill" style="width: <?= $percentage ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($stats['operating_systems'])): ?>
        <div class="chart-section">
            <h2><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px; margin-bottom: 3px;"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>Sistemas Operacionais</h2>
            <?php 
            $totalOS = array_sum(array_column($stats['operating_systems'], 'count'));
            foreach ($stats['operating_systems'] as $os): 
                $percentage = $totalOS > 0 ? ($os['count'] / $totalOS) * 100 : 0;
            ?>
            <div class="chart-bar">
                <div class="chart-bar-label">
                    <span><?= htmlspecialchars($os['os']) ?></span>
                    <span><strong><?= number_format($percentage, 1) ?>%</strong></span>
                </div>
                <div class="chart-bar-track">
                    <div class="chart-bar-fill" style="width: <?= $percentage ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>

    <div style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 1px solid var(--footer-border);">
        <p style="color: var(--text-muted); font-size: 14px;">
            🔒 Analytics privacy-friendly • Dados anonimizados • Retenção: 90 dias • 
            <a href="privacy.php" style="color: var(--brand-color); text-decoration: none;">Política de Privacidade</a>
        </p>
    </div>
</div>
</body>
</html>
