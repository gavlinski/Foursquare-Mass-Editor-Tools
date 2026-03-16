# Analytics Interno - Privacy-Friendly

Sistema de analytics próprio, self-hosted, totalmente aderente à LGPD/GDPR.

## 📊 Características

### ✅ Privacy-by-Design
- **Sem cookies de tracking**: Nenhum cookie é criado para analytics
- **IP anonimizado**: SHA-256 hash com salt diário (irreversível)
- **Self-hosted**: Todos os dados no próprio servidor
- **Zero terceiros**: Nenhum dado compartilhado externamente
- **Não bloqueado**: Não é bloqueado por adblockers (domínio próprio)

### 📈 Métricas Coletadas
- Pageviews (URLs acessadas)
- Visitantes únicos (hash anônimo)
- Referrers (origem do tráfego)
- Navegador e sistema operacional
- Idioma do navegador
- Timestamps

### 🗄️ Armazenamento
- **Banco de dados**: SQLite (`data/analytics.db`)
- **Retenção**: 90 dias (limpeza automática)
- **Performance**: Índices otimizados
- **Cleanup**: 1% de chance a cada pageview

## 🚀 Funcionamento

### Coleta Automática
O arquivo `analytics.php` é incluído no topo das páginas principais:

```php
// Analytics interno (privacy-friendly)
require_once __DIR__ . '/analytics.php';
```

### Páginas Monitoradas
- `index.php` - Landing page
- `main.php` - Página principal
- `edit.php` - Editor de venues
- `load.php` - Carregador de dados
- `search.php` - Busca de venues

### Dashboard
Acesse `analytics-dashboard.php` (requer autenticação) para visualizar:

- Total de pageviews
- Visitantes únicos
- Páginas mais visitadas
- Principais referrers
- Navegadores e sistemas operacionais
- Gráfico de pageviews por dia

## 🔒 Privacidade e Segurança

### Anonimização de IP
```php
// SHA-256 com salt diário
$salt = date('Y-m-d') . 'analytics_salt_4sqmet';
$userHash = hash('sha256', $ip . $salt);
```

**Resultado**: Hash diferente a cada dia, impossível correlacionar entre dias.

### Dados NÃO Coletados
- ❌ Nomes ou emails
- ❌ IPs reais
- ❌ Senhas ou tokens
- ❌ Dados pessoais de venues editados
- ❌ Conteúdo de formulários

### Conformidade Legal
- ✅ **LGPD** (Lei nº 13.709/2018)
- ✅ **GDPR** (quando aplicável)
- ✅ **Marco Civil da Internet** (Lei nº 12.965/2014)

**Base legal**: Legítimo interesse (Art. 7º, IX da LGPD) para:
- Segurança da aplicação
- Melhoria da experiência do usuário
- Estatísticas de uso

## 🛠️ Arquivos do Sistema

```
analytics.php                  # Coletor server-side
analytics-dashboard.php        # Dashboard de visualização
data/
  ├── analytics.db            # Banco SQLite (gerado automaticamente)
  └── .gitignore              # Ignora DBs do git
```

## 📝 Estrutura do Banco

```sql
CREATE TABLE pageviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_url TEXT NOT NULL,
    page_title TEXT,
    referrer TEXT,
    user_hash TEXT NOT NULL,      -- SHA-256 anônimo
    user_agent TEXT,
    browser TEXT,
    os TEXT,
    language TEXT,
    timestamp INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Índices para performance
CREATE INDEX idx_timestamp ON pageviews(timestamp);
CREATE INDEX idx_page_url ON pageviews(page_url);
CREATE INDEX idx_user_hash ON pageviews(user_hash);
```

## 🔧 Configuração

### Variáveis (em `analytics.php`)

```php
define('ANALYTICS_DB_PATH', __DIR__ . '/data/analytics.db');
define('ANALYTICS_ENABLED', true);
define('ANALYTICS_RETENTION_DAYS', 90);
```

### Desabilitar Analytics
Para desabilitar temporariamente:

```php
define('ANALYTICS_ENABLED', false);
```

## 📊 Exemplos de Consultas

### Total de pageviews (últimos 30 dias)
```sql
SELECT COUNT(*) FROM pageviews 
WHERE timestamp >= strftime('%s', 'now', '-30 days');
```

### Visitantes únicos (últimos 7 dias)
```sql
SELECT COUNT(DISTINCT user_hash) FROM pageviews 
WHERE timestamp >= strftime('%s', 'now', '-7 days');
```

### Top 10 páginas
```sql
SELECT page_url, COUNT(*) as views 
FROM pageviews 
GROUP BY page_url 
ORDER BY views DESC 
LIMIT 10;
```

## 🆚 Comparação com Alternativas

| Aspecto | Analytics Próprio | StatCounter | Google Analytics |
|---------|------------------|-------------|------------------|
| **Privacy** | ✅ 100% anônimo | ❌ Tracking | ❌ Tracking |
| **Bloqueado** | ✅ Não | ❌ Sim | ❌ Sim |
| **Self-hosted** | ✅ Sim | ❌ Não | ❌ Não |
| **LGPD/GDPR** | ✅ Compliant | ⚠️ Requer banner | ⚠️ Requer consentimento |
| **Cookies** | ✅ Sem cookies | ❌ Usa cookies | ❌ Usa cookies |
| **Dados no servidor** | ✅ Sim | ❌ EUA | ❌ EUA |
| **Setup** | 🟢 Simples | 🟠 Conta externa | 🟠 Conta externa |

## 🚀 Próximas Melhorias (Opcional)

- [ ] API REST para consumo externo
- [ ] Exportação CSV/JSON
- [ ] Alertas de picos de tráfego
- [ ] Gráficos interativos (Chart.js)
- [ ] Tracking de eventos customizados
- [ ] Funis de conversão
- [ ] Heatmaps de cliques

## 📚 Referências

- [LGPD - Lei nº 13.709/2018](http://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709.htm)
- [GDPR - Regulamento UE 2016/679](https://gdpr-info.eu/)
- [Privacy by Design - Ann Cavoukian](https://www.ipc.on.ca/wp-content/uploads/resources/7foundationalprinciples.pdf)

## 📄 Licença

Este sistema de analytics faz parte do Foursquare Mass Editor Tools.

**Licença**: GPLv3  
**Autor**: Elio Gavlinski  
**Copyright**: Copyleft (c) 2012-2026
