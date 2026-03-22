<?php
/**
 * Política de Privacidade
 * Foursquare Mass Editor Tools
 *
 * @category   Legal
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2012-2026
 * @version    1.0.0
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */

// Anti-cache headers
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Analytics interno (privacy-friendly)
require_once __DIR__ . '/analytics.php';
?>
<!doctype html>
<html lang="pt-BR">
<head>
<title>Política de Privacidade - Elio Tools</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Política de Privacidade do Foursquare Mass Editor Tools - Como coletamos, usamos e protegemos seus dados.">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<style>
:root {
    /* Dark theme (default) */
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
    --section-bg: rgba(255, 255, 255, 0.02);
    --footer-border: rgba(255, 255, 255, 0.08);
    --link-color: #667eea;
    --link-hover: #a991ff;
}

@media (prefers-color-scheme: light) {
    :root {
        /* Light theme */
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
        --section-bg: #fafbfc;
        --footer-border: #e0e6ed;
        --link-color: #5851db;
        --link-hover: #764ba2;
    }
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    min-height: 100vh;
    background: var(--bg-primary);
    color: var(--text-primary);
    position: relative;
    overflow-x: hidden;
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
    max-width: 900px;
    margin: 0 auto;
    padding: 60px 40px 80px;
    position: relative;
    z-index: 1;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-tertiary);
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 30px;
    transition: color 0.3s ease;
}

.back-link:hover {
    color: var(--link-color);
}

.back-link svg {
    width: 16px;
    height: 16px;
}

.header {
    margin-bottom: 50px;
}

.brand {
    font-size: 14px;
    font-weight: 600;
    color: var(--brand-color);
    margin-bottom: 15px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

h1 {
    font-size: 42px;
    font-weight: 800;
    margin-bottom: 15px;
    background: linear-gradient(135deg, #667eea 0%, #a991ff 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1.2;
}

.last-updated {
    font-size: 14px;
    color: var(--text-muted);
    margin-top: 10px;
    margin-bottom: 30px;
}

.content {
    line-height: 1.8;
}

.intro {
    font-size: 18px;
    color: var(--text-secondary);
    margin-bottom: 40px;
    padding: 25px 25px 15px 25px;
    background: var(--section-bg);
    border-radius: 12px;
    border: 1px solid var(--card-border);
}

.section {
    margin-bottom: 40px;
}

h2 {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 20px;
    margin-top: 10px;
}

h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 15px;
    margin-top: 25px;
}

p {
    font-size: 16px;
    color: var(--text-secondary);
    margin-bottom: 15px;
    line-height: 1.4;
}

ul, ol {
    margin-left: 25px;
    margin-bottom: 20px;
}

li {
    font-size: 16px;
    color: var(--text-secondary);
    margin-bottom: 10px;
    line-height: 1.7;
}

strong {
    color: var(--text-primary);
    font-weight: 600;
}

a {
    color: var(--link-color);
    text-decoration: none;
    border-bottom: 1px solid transparent;
    transition: all 0.3s ease;
}

a:hover {
    color: var(--link-hover);
    border-bottom-color: var(--link-hover);
}

.highlight-box {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 8px;
    padding: 25px 25px 15px 25px;
    margin: 20px 0;
}

.contact-box {
    background: var(--section-bg);
    border: 2px solid var(--card-border);
    border-radius: 12px;
    padding: 30px 30px 25px 30px;
    margin: 40px 0;
    text-align: center;
}

.contact-box h3 {
    margin-top: 0;
    margin-bottom: 20px;
}

.contact-box p {
    margin-bottom: 10px;
}

.footer {
    text-align: center;
    padding-top: 40px;
    margin-top: 60px;
    border-top: 1px solid var(--footer-border);
}

.footer-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.footer-links a {
    color: var(--text-tertiary);
    font-size: 14px;
}

.footer-text {
    font-size: 13px;
    color: var(--text-muted);
}

@media (max-width: 768px) {
    .container {
        padding: 40px 20px;
    }
    
    h1 {
        font-size: 32px;
    }
    
    h2 {
        font-size: 24px;
    }
    
    h3 {
        font-size: 18px;
    }
    
    p, li {
        font-size: 15px;
    }
}
</style>
</head>
<body>
<div class="container">
    <a href="index.php" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Voltar para o início
    </a>

    <div class="header">
        <div class="brand">Política de Privacidade</div>
        <h1>Como Protegemos Seus Dados</h1>
            <div class="last-updated">Última atualização: 17 de março de 2026</div>
        <div class="intro">
            <p><strong>Compromisso com sua privacidade:</strong> O Foursquare Mass Editor Tools ("Elio Tools") respeita sua privacidade e está comprometido em proteger seus dados pessoais. Esta política explica de forma transparente como coletamos, usamos, armazenamos e protegemos suas informações.</p>
        </div>

        <div class="section">
            <h2>1. Responsável pelo Tratamento de Dados</h2>
            <p>O responsável pela coleta e tratamento dos dados pessoais é:</p>
            <div class="highlight-box">
                <p><strong>Nome:</strong> Elio Gavlinski<br>
                <strong>Email:</strong> <a href="mailto:gavlinski@gmail.com">gavlinski@gmail.com</a><br>
                <strong>Aplicação:</strong> Foursquare Mass Editor Tools (Elio Tools)<br>
                <strong>Website:</strong> <a href="https://4sq.eliotools.site" target="_blank">https://4sq.eliotools.site</a></p>
            </div>
            <p style="margin-top: 15px; font-size: 15px; color: var(--text-muted);"><em>Nota: Como este é um projeto pessoal sem fins lucrativos, não há endereço comercial. Para questões legais, utilize o email de contato acima.</em></p>
        </div>

        <div class="section">
            <h2>2. Dados Coletados</h2>
            
            <h3>2.1 Dados de Autenticação (OAuth2)</h3>
            <p>Quando você se conecta usando sua conta Foursquare, coletamos através da API oficial do Foursquare:</p>
            <ul>
                <li><strong>Nome completo</strong> (primeiro e último nome)</li>
                <li><strong>ID de usuário do Foursquare</strong></li>
                <li><strong>Token de acesso OAuth2</strong> (para autenticação segura)</li>
                <li><strong>Coordenadas do último check-in</strong> (latitude e longitude, quando disponível)</li>
                <li><strong>Contagem de check-ins</strong> (estatística pública do perfil)</li>
            </ul>

            <h3>2.2 Dados de Uso da Aplicação</h3>
            <ul>
                <li><strong>Dados de locais editados:</strong> informações dos venues que você visualiza ou edita (endereços, coordenadas, categorias, etc.)</li>
                <li><strong>Preferências de sessão:</strong> coordenadas de busca, raio de busca, filtros aplicados</li>
                <li><strong>Logs de acesso:</strong> endereço IP, navegador, sistema operacional (apenas para segurança e diagnóstico)</li>
            </ul>

            <h3>2.3 Cookies e Armazenamento Local</h3>
            <ul>
                <li><strong>oauth_token:</strong> token de autenticação do Foursquare (essencial)</li>
                <li><strong>name:</strong> seu nome para exibição (essencial)</li>
                <li><strong>coordinates:</strong> última localização conhecida (funcional)</li>
                <li><strong>sessionStorage:</strong> dados temporários da sessão (navegação)</li>
                <li><strong>PHPSESSID:</strong> identificador de sessão PHP (essencial)</li>
            </ul>

            <h3>2.4 Analytics Interno (Privacy-Friendly)</h3>
            <p>Para entender como a aplicação é utilizada e melhorar a experiência do usuário, coletamos métricas básicas de uso através de um sistema de analytics próprio, hospedado no nosso servidor:</p>
            <ul>
                <li><strong>Páginas visitadas:</strong> URLs das páginas acessadas (sem query strings)</li>
                <li><strong>Identificador anônimo:</strong> hash criptográfico (SHA-256) do seu IP, alterado diariamente (não permite identificação pessoal)</li>
                <li><strong>Origem do acesso:</strong> de qual site você veio (referrer)</li>
                <li><strong>Informações técnicas:</strong> navegador, sistema operacional, idioma do navegador</li>
                <li><strong>Timestamp:</strong> data e hora do acesso</li>
            </ul>
            <div class="highlight-box">
                <p><strong>✅ Privacy-by-Design:</strong></p>
                <ul style="margin-top: 10px;">
                    <li><strong>Sem cookies de tracking:</strong> não utilizamos cookies para analytics</li>
                    <li><strong>IP anonimizado:</strong> seu IP real nunca é armazenado, apenas um hash irreversível</li>
                    <li><strong>Query strings removidas:</strong> URLs são sanitizadas para remover dados potencialmente sensíveis</li>
                    <li><strong>Self-hosted:</strong> todos os dados ficam no nosso servidor, não compartilhados com terceiros</li>
                    <li><strong>Retenção limitada:</strong> dados são automaticamente excluídos após 90 dias</li>
                    <li><strong>Não bloqueado:</strong> ao contrário de ferramentas de terceiros, não é bloqueado por adblockers</li>
                    <li><strong>Filtro de bots:</strong> scanners, ferramentas de teste e bots maliciosos são automaticamente filtrados</li>
                    <li><strong>Transparente:</strong> você pode visualizar as estatísticas agregadas no dashboard de analytics</li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h2>3. Finalidade do Tratamento</h2>
            <p>Utilizamos seus dados exclusivamente para as seguintes finalidades:</p>
            
            <div class="highlight-box">
                <ul>
                    <li><strong>Autenticação:</strong> Validar sua identidade via OAuth2 do Foursquare</li>
                    <li><strong>Funcionalidade da ferramenta:</strong> Permitir visualização, importação, edição e sinalização de locais</li>
                    <li><strong>Integração com APIs:</strong> Comunicação com Foursquare API e Google Maps API</li>
                    <li><strong>Experiência do usuário:</strong> Lembrar preferências de busca e localização</li>
                    <li><strong>Segurança:</strong> Prevenir uso não autorizado e monitorar atividades suspeitas</li>
                    <li><strong>Manutenção:</strong> Diagnosticar problemas técnicos e melhorar a aplicação</li>
                </ul>
            </div>

            <p><strong>Não utilizamos seus dados para:</strong></p>
            <ul>
                <li>Marketing ou publicidade</li>
                <li>Venda ou compartilhamento com terceiros comerciais</li>
                <li>Análise de comportamento para fins não relacionados à aplicação</li>
                <li>Criação de perfis automatizados ou decisões automatizadas</li>
            </ul>
        </div>

        <div class="section">
            <h2>4. Base Legal (LGPD)</h2>
            <p>O tratamento de dados é realizado com base nas seguintes hipóteses legais previstas na Lei Geral de Proteção de Dados (Lei nº 13.709/2018):</p>
            <ul>
                <li><strong>Consentimento (Art. 7º, I):</strong> Ao conectar sua conta Foursquare, você consente expressamente com a coleta e uso dos dados</li>
                <li><strong>Execução de contrato (Art. 7º, V):</strong> Os dados são necessários para fornecer o serviço solicitado</li>
                <li><strong>Legítimo interesse (Art. 7º, IX):</strong> Segurança da aplicação e prevenção de fraudes</li>
            </ul>
        </div>

        <div class="section">
            <h2>5. Compartilhamento de Dados</h2>
            <p>Seus dados são compartilhados apenas com os seguintes serviços terceiros, estritamente necessários para o funcionamento da aplicação:</p>

            <h3>5.1 Foursquare (necessário)</h3>
            <ul>
                <li><strong>Finalidade:</strong> Autenticação OAuth2 e operações CRUD nos locais</li>
                <li><strong>Dados compartilhados:</strong> Token de acesso, requisições de API</li>
                <li><strong>Política de privacidade:</strong> <a href="https://foursquare.com/legal/privacy-center/" target="_blank">https://foursquare.com/legal/privacy-center/</a></li>
            </ul>

            <h3>5.2 Google Maps (funcional)</h3>
            <ul>
                <li><strong>Finalidade:</strong> Visualização de mapas e geocodificação</li>
                <li><strong>Dados compartilhados:</strong> Coordenadas geográficas, endereços</li>
                <li><strong>Política de privacidade:</strong> <a href="https://policies.google.com/privacy" target="_blank">https://policies.google.com/privacy</a></li>
            </ul>

            <p><strong>Importante:</strong> Não vendemos, alugamos ou compartilhamos seus dados pessoais com terceiros para fins comerciais ou publicitários.</p>
        </div>

        <div class="section">
            <h2>6. Armazenamento e Segurança</h2>
            
            <h3>6.1 Onde os dados são armazenados</h3>
            <ul>
                <li><strong>Sessões PHP:</strong> Armazenadas temporariamente no servidor web durante o uso</li>
                <li><strong>Cookies:</strong> Armazenados localmente no seu navegador</li>
                <li><strong>Logs do servidor:</strong> Mantidos por até 30 dias para fins de segurança</li>
            </ul>

            <h3>6.2 Medidas de segurança implementadas</h3>
            <ul>
                <li>✓ Comunicação HTTPS/TLS para todas as conexões</li>
                <li>✓ OAuth2 com tokens seguros (sem armazenamento de senhas)</li>
                <li>✓ Sessões com cookies HttpOnly e SameSite</li>
                <li>✓ Regeneração de IDs de sessão após autenticação</li>
                <li>✓ Validação de entrada e sanitização de dados</li>
                <li>✓ Headers de segurança (Cache-Control, X-Frame-Options, etc.)</li>
                <li>✓ Monitoramento de loops de autenticação e tentativas suspeitas</li>
            </ul>

            <h3>6.3 Retenção de dados</h3>
            <ul>
                <li><strong>Dados de sessão:</strong> Excluídos automaticamente após logout ou expiração (24 horas de inatividade)</li>
                <li><strong>Cookies:</strong> Expiram após 24 horas ou são excluídos ao fazer logout</li>
                <li><strong>Logs de servidor:</strong> Mantidos por 30 dias e então excluídos automaticamente</li>
            </ul>
        </div>

        <div class="section">
            <h2>7. Direitos do Titular (LGPD/GDPR)</h2>
            <p>Em conformidade com a LGPD (Lei nº 13.709/2018) e GDPR (quando aplicável), você tem os seguintes direitos:</p>

            <div class="highlight-box">
                <ul>
                    <li><strong>Acesso:</strong> Solicitar cópia dos dados pessoais que mantemos sobre você</li>
                    <li><strong>Correção:</strong> Solicitar correção de dados incompletos, inexatos ou desatualizados</li>
                    <li><strong>Exclusão:</strong> Solicitar a exclusão de seus dados pessoais (direito ao esquecimento)</li>
                    <li><strong>Portabilidade:</strong> Solicitar os dados em formato estruturado e legível</li>
                    <li><strong>Revogação do consentimento:</strong> Retirar o consentimento a qualquer momento (fazendo logout)</li>
                    <li><strong>Oposição:</strong> Opor-se ao tratamento de dados em determinadas situações</li>
                    <li><strong>Informação:</strong> Obter informações sobre o tratamento de seus dados</li>
                    <li><strong>Anonimização/bloqueio:</strong> Solicitar anonimização ou bloqueio de dados</li>
                </ul>
            </div>

            <p><strong>Como exercer seus direitos:</strong></p>
            <ul>
                <li><strong>Exclusão de dados:</strong> Faça logout da aplicação (exclui automaticamente sessão e cookies)</li>
                <li><strong>Outras solicitações:</strong> Entre em contato através do email <a href="mailto:gavlinski@gmail.com">gavlinski@gmail.com</a></li>
            </ul>
            <p>Responderemos sua solicitação em até 15 dias úteis, conforme estabelecido pela LGPD.</p>
        </div>

        <div class="section">
            <h2>8. Consentimento e Revogação</h2>
            <p>Ao clicar em "Conectar com Foursquare" e autorizar a aplicação através do OAuth2, você:</p>
            <ul>
                <li>Consente expressamente com a coleta e tratamento de dados conforme descrito nesta política</li>
                <li>Concorda em compartilhar os dados necessários para o funcionamento da aplicação</li>
                <li>Compreende que alguns dados são essenciais e a recusa impossibilita o uso do serviço</li>
            </ul>

            <p><strong>Para revogar o consentimento:</strong></p>
            <ol>
                <li>Faça logout da aplicação (botão "Sair" no menu superior)</li>
                <li>Revogue o acesso da aplicação no Foursquare: <a href="https://foursquare.com/settings/connections" target="_blank">https://foursquare.com/settings/connections</a></li>
                <li>Limpe os cookies do seu navegador relacionados ao domínio <code>4sq.eliotools.site</code></li>
            </ol>
        </div>

        <div class="section">
            <h2>9. Cookies Detalhados</h2>
            <p>Esta aplicação utiliza os seguintes cookies:</p>

            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr style="background: var(--section-bg); text-align: left;">
                        <th style="padding: 12px; border: 1px solid var(--card-border);">Cookie</th>
                        <th style="padding: 12px; border: 1px solid var(--card-border);">Tipo</th>
                        <th style="padding: 12px; border: 1px solid var(--card-border);">Duração</th>
                        <th style="padding: 12px; border: 1px solid var(--card-border);">Finalidade</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 12px; border: 1px solid var(--card-border);"><code>PHPSESSID</code></td>
                        <td style="padding: 12px; border: 1px solid var(--card-border);">Essencial</td>
                        <td style="padding: 12px; border: 1px solid var(--card-border);">Sessão</td>
                        <td style="padding: 12px; border: 1px solid var(--card-border);">Identificador de sessão PHP</td>
                    </tr>
                    <tr style="background: var(--section-bg);">
                        <td style="padding: 12px; border: 1px solid var(--card-border);"><code>oauth_token</code></td>
                        <td style="padding: 12px; border: 1px solid var(--card-border);">Essencial</td>
                        <td style="padding: 12px; border: 1px solid var(--card-border);">24 horas</td>
                        <td style="padding: 12px; border: 1px solid var(--card-border);">Token de autenticação Foursquare</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid var(--card-border);"><code>name</code></td>
                        <td style="padding: 12px; border: 1px solid var(--card-border);">Funcional</td>
                        <td style="padding: 12px; border: 1px solid var(--card-border);">24 horas</td>
                        <td style="padding: 12px; border: 1px solid var(--card-border);">Exibição do nome do usuário</td>
                    </tr>
                    <tr style="background: var(--section-bg);">
                        <td style="padding: 12px; border: 1px solid var(--card-border);"><code>coordinates</code></td>
                        <td style="padding: 12px; border: 1px solid var(--card-border);">Funcional</td>
                        <td style="padding: 12px; border: 1px solid var(--card-border);">24 horas</td>
                        <td style="padding: 12px; border: 1px solid var(--card-border);">Lembrar última localização de busca</td>
                    </tr>
                </tbody>
            </table>

            <p style="margin-top: 20px;"><strong>Gerenciamento de cookies:</strong> Você pode bloquear ou excluir cookies através das configurações do seu navegador. Note que cookies essenciais são necessários para o funcionamento da aplicação.</p>
        </div>

        <div class="section">
            <h2>10. Transferência Internacional de Dados</h2>
            <p>Alguns dados podem ser transferidos internacionalmente ao utilizar serviços de terceiros:</p>
            <ul>
                <li><strong>Foursquare:</strong> Dados processados nos Estados Unidos (EUA)</li>
                <li><strong>Google Maps:</strong> Dados processados globalmente conforme política do Google</li>
            </ul>
            <p>Estas transferências são realizadas com base em adequadas garantias legais, incluindo cláusulas contratuais padrão e certificações de proteção de dados dos provedores.</p>
        </div>

        <div class="section">
            <h2>11. Menores de Idade</h2>
            <p>Esta aplicação não é destinada a menores de 13 anos. Não coletamos intencionalmente dados de crianças. Se você é responsável por um menor e acredita que ele forneceu dados pessoais, entre em contato para que possamos excluir essas informações.</p>
        </div>

        <div class="section">
            <h2>12. Alterações nesta Política</h2>
            <p>Esta Política de Privacidade pode ser atualizada periodicamente para refletir mudanças em nossas práticas ou requisitos legais. Notificaremos sobre alterações significativas através de:</p>
            <ul>
                <li>Atualização da data "Última atualização" no topo desta página</li>
                <li>Aviso destacado na aplicação quando você fizer login</li>
                <li>Email para usuários ativos (quando aplicável)</li>
            </ul>
            <p>Recomendamos que você revise esta política periodicamente.</p>
        </div>

        <div class="section">
            <h2>13. Legislação Aplicável</h2>
            <p>Esta Política de Privacidade é regida pelas seguintes legislações:</p>
            <ul>
                <li><strong>LGPD:</strong> Lei Geral de Proteção de Dados (Lei nº 13.709/2018) - Brasil</li>
                <li><strong>Marco Civil da Internet:</strong> Lei nº 12.965/2014 - Brasil</li>
                <li><strong>GDPR:</strong> Regulamento Geral de Proteção de Dados da UE (quando aplicável)</li>
            </ul>
        </div>

        <div class="contact-box">
            <h3>📧 Entre em Contato</h3>
            <p>Para exercer seus direitos, esclarecer dúvidas ou reportar problemas relacionados à privacidade:</p>
            <p><strong>Email:</strong> <a href="mailto:gavlinski@gmail.com">gavlinski@gmail.com</a></p>
            <p><strong>Responsável:</strong> Elio Gavlinski</p>
            <p style="margin-top: 20px; font-size: 14px; color: var(--text-muted);">
                Responderemos sua solicitação em até 15 dias úteis conforme estabelecido pela LGPD.
            </p>
        </div>

        <div class="section">
            <h2>14. Autoridade Nacional de Proteção de Dados (ANPD)</h2>
            <p>Se você não estiver satisfeito com a resposta às suas solicitações ou acredita que seus direitos foram violados, você pode registrar uma reclamação junto à Autoridade Nacional de Proteção de Dados (ANPD):</p>
            <p>
                <strong>Website:</strong> <a href="https://www.gov.br/anpd" target="_blank">https://www.gov.br/anpd</a><br>
                <strong>Email:</strong> <a href="mailto:comunicacao@anpd.gov.br">comunicacao@anpd.gov.br</a>
            </p>
        </div>
    </div>

    <div class="footer">
        <div class="footer-links">
            <a href="index.php">Início</a>
            <a href="https://foursquare.com/legal/privacy-center/" target="_blank">Privacidade do Foursquare</a>
            <a href="https://policies.google.com/privacy" target="_blank">Privacidade do Google</a>
            <a href="https://github.com/gavlinski/Foursquare-Mass-Editor-Tools" target="_blank">GitHub</a>
            <a href="mailto:gavlinski@gmail.com">Contato</a>
        </div>
        <p class="footer-text">
            © 2012-2026 Elio Gavlinski. Licenciado sob GPLv3.<br><br>
            Esta aplicação não é afiliada, associada, autorizada, endossada por, ou de qualquer forma oficialmente conectada com Foursquare Labs, Inc.
        </p>
    </div>
</div>
</body>
</html>
