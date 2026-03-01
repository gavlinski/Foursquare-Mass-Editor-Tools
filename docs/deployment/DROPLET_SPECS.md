# 🖥️ Especificações do Droplet - DigitalOcean

## 📋 Configuração Recomendada (Budget 1GB)

### 1. **Região e Datacenter**

**Escolha: New York - NYC3** 🏆

#### Análise de Latência (estimada do Brasil):

| Região | Datacenter | Latência Brasil | Latência EUA | Recomendação |
|--------|------------|-----------------|--------------|--------------|
| **New York** | **NYC3** | **~120ms** | **~10-40ms** | ⭐⭐⭐⭐⭐ MELHOR |
| New York | NYC1 | ~125ms | ~10-40ms | ⭐⭐⭐⭐ (mais antigo) |
| Atlanta | ATL1 | ~140ms | ~20-50ms | ⭐⭐⭐ |
| San Francisco | SFO3 | ~180ms | ~30-80ms | ⭐⭐ (muito oeste) |

**Por que NYC3?**
- ✅ **Menor latência para Brasil**: ~120ms (melhor rota transatlântica)
- ✅ **Próximo de usuários EUA**: Costa leste concentra grande parte dos usuários americanos
- ✅ **Infraestrutura moderna**: NYC3 é mais novo que NYC1
- ✅ **Redundância**: Fácil criar réplica em NYC1 se necessário
- ✅ **Custo igual**: Todas as regiões têm mesmo preço

**Conclusão**: `nyc3` é a melhor escolha para balancear Brasil + EUA.

---

### 2. **Sistema Operacional**

**Escolha: Docker on Ubuntu 22.04 (Marketplace)** 🏆

#### Comparação:

| Opção | Setup Time | Versões | Recomendação |
|-------|------------|---------|--------------|
| **Docker on Ubuntu 22.04** | **~5 min** | Docker CE 28.11 + Compose 2.36.0 | ⭐⭐⭐⭐⭐ MELHOR |
| Ubuntu 24.04 LTS | ~20 min | Instalar manualmente | ⭐⭐⭐⭐ |
| Ubuntu 22.04 LTS | ~20 min | Instalar manualmente | ⭐⭐⭐ |

**Por que Marketplace "Docker on Ubuntu 22.04"?**
- ✅ **Docker pré-instalado**: CE 28.11, Compose 2.36.0, BuildX 0.23.0
- ✅ **Configurado e testado**: DigitalOcean mantém a imagem
- ✅ **Economiza tempo**: 15 minutos de setup eliminados
- ✅ **Ubuntu 22.04 LTS**: Suporte até abril/2027 (5 anos)
- ✅ **Segurança**: Patches automáticos configurados

**Alternativa**: Se quiser Ubuntu 24.04 LTS (suporte até 2029), pode instalar Docker manualmente (script fornecido).

---

### 3. **Droplet Type e Plano**

**Escolha: Shared CPU - Basic - 1GB** 💰

#### Especificações:

```yaml
Plan: Basic (Shared CPU)
CPU: 1 vCPU (Intel ou AMD)
RAM: 1 GB
Storage: 25 GB SSD
Transfer: 1 TB/mês
Cost: $6/mês ($0.009/hora)
```

#### Por que 1GB é suficiente?

**Análise de Requisitos:**
```
Apache + PHP-FPM: ~150-200 MB
MySQL (se usar): ~100-150 MB (mas usamos APIs externas)
Docker overhead: ~50 MB
Sistema + cache: ~100 MB
-----------------------------------
Total estimado: ~400-500 MB
Buffer: ~500 MB disponível
```

**Cenários de Uso:**
- ✅ **1-10 usuários simultâneos**: Confortável
- ✅ **Até 50 req/min**: Sem problemas
- ⚠️ **Picos ocasionais**: Pode ter lentidão temporária
- ❌ **100+ usuários simultâneos**: Precisa upgrade

**Quando fazer upgrade para 2GB ($12/mês)?**
- Tráfego > 1000 req/dia consistente
- Mais de 20 usuários simultâneos
- Adicionar banco de dados local
- Incorporar cache Redis/Memcached

---

### 4. **Autenticação**

**Escolha: SSH Key** 🔐

**Por quê?**
- ✅ **Mais seguro**: Impossível brute force
- ✅ **Conveniente**: Sem digitação de senha
- ✅ **Requerido para CI/CD**: GitHub Actions precisa

**Passo a passo:**

```bash
# 1. Gerar chave SSH no seu Mac
ssh-keygen -t ed25519 -C "4sqmet-prod-droplet" -f ~/.ssh/4sqmet_prod

# 2. Copiar chave pública
cat ~/.ssh/4sqmet_prod.pub | pbcopy

# 3. No DigitalOcean Console:
Settings → Security → SSH Keys → Add SSH Key
- Name: 4sqmet-prod-key
- Public Key: [Colar do clipboard]

# 4. Ao criar droplet, selecionar esta chave
```

**Backup de Emergência:**
- DigitalOcean Console tem acesso via navegador (Recovery Console)
- Não precisa configurar senha

---

### 5. **Monitoring e Alertas**

**Escolha: Habilitado (Free)** 📊

**Recursos incluídos:**
- ✅ CPU usage (%)
- ✅ Memory usage (%)
- ✅ Disk I/O
- ✅ Bandwidth (in/out)
- ✅ Alertas customizáveis

**Alertas Recomendados:**
```yaml
CPU Usage:
  - Warning: > 80% por 5 min
  - Critical: > 95% por 5 min

Memory Usage:
  - Warning: > 85% por 5 min
  - Critical: > 95% por 5 min

Disk Usage:
  - Warning: > 80%
  - Critical: > 90%
```

**Como configurar:**
1. Droplet criado → Monitoring tab
2. Create Alert Policy
3. Selecionar métricas e thresholds
4. Email de notificação

---

### 6. **Hostname e Tags**

**Hostname**: `4sqmet-prod-v3`

**Tags Recomendadas:**
```
production
4sqmet
foursquare-tools
docker
web-app
```

**Por que estas tags?**
- **production**: Identifica ambiente
- **4sqmet**: Identifica projeto
- **foursquare-tools**: Categoria funcional
- **docker**: Stack utilizada
- **web-app**: Tipo de aplicação

**Benefícios:**
- Filtrar droplets no dashboard
- Aplicar firewall rules por tag
- Agrupar métricas
- Facilitar automação (API)

---

## 📝 Resumo da Configuração

### Especificações Finais:

```yaml
Distribution: Docker on Ubuntu 22.04 (Marketplace)
Datacenter: NYC3 (New York 3)

Droplet Type: Shared CPU - Basic
Plan: $6/mês
CPU: 1 vCPU
RAM: 1 GB
Storage: 25 GB SSD
Bandwidth: 1 TB/mês

Authentication: SSH Key (4sqmet-prod-key)
Monitoring: Enabled (Free)

Hostname: 4sqmet-prod-v3
Tags: production, 4sqmet, foursquare-tools, docker, web-app

IPv4: Yes (público)
IPv6: Optional (não necessário)
Backups: Optional ($1.20/mês - recomendado após 1 mês)
```

---

## 🎯 Checklist de Criação

### Pré-requisitos:
- [ ] Chave SSH criada (`~/.ssh/4sqmet_prod.pub`)
- [ ] Chave SSH adicionada ao DigitalOcean (Settings → Security)
- [ ] Conta DigitalOcean com método de pagamento ativo

### Passos no Console:

**1. Create → Droplets**
- [ ] Region: **New York - NYC3**
- [ ] Image: **Marketplace → Docker on Ubuntu 22.04**
- [ ] Size: **Basic - Shared CPU - 1GB - $6/mo**

**2. Authentication**
- [ ] SSH Keys: Selecionar **4sqmet-prod-key**
- [ ] Password: Deixar desmarcado

**3. Options (Finalize and Create)**
- [ ] Monitoring: ✅ **Enable free metrics**
- [ ] IPv6: ⬜ Disabled (opcional)
- [ ] User data: ⬜ Blank (usaremos script depois)
- [ ] Backups: ⬜ Disabled (habilitar depois de validar)

**4. Finalize**
- [ ] Hostname: **4sqmet-prod-v3**
- [ ] Tags: `production`, `4sqmet`, `foursquare-tools`, `docker`, `web-app`
- [ ] Project: Default ou criar `Foursquare-Tools`
- [ ] Click: **Create Droplet**

**5. Aguardar Provisionamento** (~60 segundos)

---

## 🚀 Próximos Passos Após Criação

### 1. Anotar IP Público

```bash
# Será algo como:
DROPLET_IP=134.209.163.143
```

### 2. Testar Conexão SSH

```bash
ssh -i ~/.ssh/4sqmet_prod root@${DROPLET_IP}

# Deve conectar sem pedir senha
# Primeira vez pedirá confirmação de fingerprint (yes)
```

### 3. Executar Setup Automatizado

```bash
# No seu Mac:
scp -i ~/.ssh/4sqmet_prod scripts/setup-droplet.sh root@${DROPLET_IP}:/tmp/

# No servidor:
ssh -i ~/.ssh/4sqmet_prod root@${DROPLET_IP}
bash /tmp/setup-droplet.sh
```

O script `setup-droplet.sh` fará:
- ✅ Atualizar sistema
- ✅ Configurar firewall (UFW)
- ✅ Instalar Certbot
- ✅ Criar estrutura de diretórios
- ✅ Clonar repositório
- ✅ Configurar .env
- ✅ Build da imagem Docker
- ✅ Rodar aplicação

**Tempo estimado**: ~5-10 minutos

---

## 💰 Custos Estimados

### Mensal:

```
Droplet 1GB:        $6.00/mês
Monitoring:         $0.00 (free)
Bandwidth 1TB:      $0.00 (incluído)
IPv4:               $0.00 (incluído)
-----------------------------------
TOTAL:              $6.00/mês
```

### Opcional (Adicionar depois):

```
Automated Backups:  $1.20/mês (20% do droplet)
Volume Storage 10GB: $1.00/mês (se precisar mais disk)
Load Balancer:      $12/mês (só se escalar)
-----------------------------------
Total com backups:  $7.20/mês
```

### Anual:

```
Sem backups:  $72/ano
Com backups:  $86.40/ano
```

---

## 🔄 Plano de Upgrade Futuro

### Quando o tráfego crescer:

**Opção 1: Resize do Droplet (Vertical Scaling)**
```
1GB → 2GB: +$6/mês ($12 total)
  - 2x RAM
  - 2x CPU
  - Downtime: ~1 minuto
```

**Opção 2: Multi-Droplet com Load Balancer (Horizontal Scaling)**
```
2x Droplet 1GB + Load Balancer:
  - $6 x 2 = $12
  - Load Balancer = $12
  - Total: $24/mês
  - Zero downtime
  - Alta disponibilidade
```

**Opção 3: Migrar para App Platform**
```
Professional Plan: $12/mês
  - Auto-scaling
  - Zero config
  - Preview de PRs
  - Monitoring incluído
```

---

## 📚 Referências Rápidas

### DigitalOcean Console:
- **Droplets**: https://cloud.digitalocean.com/droplets
- **Networking**: https://cloud.digitalocean.com/networking
- **Monitoring**: https://cloud.digitalocean.com/monitoring
- **Billing**: https://cloud.digitalocean.com/billing

### Documentação Relacionada:
- **[DEPLOYMENT_STRATEGY.md](DEPLOYMENT_STRATEGY.md)**: Análise completa das opções
- **[DEPLOY.md](DEPLOY.md)**: Guia quick start
- **[setup-droplet.sh](../../scripts/setup-droplet.sh)**: Script de automação
- **[docker-compose.yml](../../docker-compose.production.yml)**: Config Docker produção

### Suporte:
- **DigitalOcean Community**: https://www.digitalocean.com/community
- **Status Page**: https://status.digitalocean.com
- **Support Tickets**: https://cloud.digitalocean.com/support

---

## ✅ FAQ

### P: Por que NYC3 e não NYC1?
**R**: NYC3 é mais novo e geralmente tem hardware mais moderno. Latência similar.

### P: 1GB vai ser suficiente mesmo?
**R**: Sim, para low traffic (<1000 req/dia, <20 usuários simultâneos). Pode fazer resize depois.

### P: Por que não Ubuntu 24.04?
**R**: Marketplace só tem 22.04 com Docker pré-instalado. 22.04 tem suporte até 2027 (suficiente).

### P: E se o IP mudar?
**R**: IPs do DigitalOcean são permanentes enquanto droplet existe. Só muda se deletar e recriar.

### P: Backups são necessários?
**R**: Opcional. Recomendo habilitar após 1 mês quando aplicação estiver estável ($1.20/mês).

### P: Posso trocar de região depois?
**R**: Não diretamente. Precisa criar novo droplet e migrar via snapshot/backup.

---

**Criado em**: 28 de Fevereiro de 2026  
**Para versão**: v3.0.0  
**Custo total**: $6/mês ($72/ano)
