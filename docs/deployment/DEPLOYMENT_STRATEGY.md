# 🚀 Estratégia de Deployment e Migração

## 📋 Contexto do Ambiente Atual

### Infraestrutura Existente

**Droplet Ativo (v2.3.5):**
```yaml
ID: ubuntu-s-1vcpu-1gb-nyc1-01
Region: NYC1 (New York)
Specs: 1 vCPU / 1 GB RAM / 25 GB Disk
IP: 206.189.180.222
Gateway: 206.189.176.1
SO: Ubuntu 18.04.1 LTS (kernel 4.15.0-213)
PHP: 7.2.24 (EOL desde 30/11/2020) ⚠️
Apache: 2.4.29 (2018)
```

**Domínio:**
- `eliotools.site` (Namecheap, 1 ano)
- DNS atualmente apontando para: 206.189.180.222

### ⚠️ Problemas Identificados

1. **PHP 7.2.24**: Sem suporte há 5+ anos, vulnerabilidades conhecidas
2. **Ubuntu 18.04**: EOL Standard em abril/2023 (apenas ESM até 2028)
3. **Incompatibilidade**: Projeto v3.x requer PHP 8.1+
4. **Gap Dev/Prod**: Desenvolvimento em Docker (PHP 8.1) vs Produção LAMP (PHP 7.2)

---

## 🎯 Análise de Opções de Deployment

### Opção 1: DigitalOcean App Platform (PaaS) ⭐⭐⭐⭐

**Descrição:**
Deploy gerenciado a partir do GitHub, com HTTPS automático e escalabilidade.

**Prós:**
- ✅ **Zero configuração de servidor**: DigitalOcean gerencia tudo
- ✅ **HTTPS automático**: Let's Encrypt incluído
- ✅ **CD nativo**: Deploy automático no git push
- ✅ **Suporte Docker**: Lê `Dockerfile` automaticamente
- ✅ **Rollback fácil**: 1 clique para versão anterior
- ✅ **Logs centralizados**: Interface web unificada
- ✅ **Ambiente staging**: Cria preview de PR automaticamente

**Contras:**
- ❌ **Custo maior**: $5-12/mês (Basic) vs $6/mês (Droplet)
- ❌ **Menos controle**: Limitado ao que a plataforma oferece
- ❌ **Vendor lock-in**: Dependência do App Platform
- ❌ **Sem acesso SSH**: Debugging limitado

**Custo Estimado:**
- **Basic Plan**: $5/mês (512MB RAM) - pode ser insuficiente
- **Professional Plan**: $12/mês (1GB RAM) - recomendado

**Complexidade:** 🟢 Baixa

**Recomendação:** ⭐⭐⭐⭐ (85/100)
Excelente para projetos que priorizam simplicidade e não precisam de customizações avançadas.

---

### Opção 2: Novo Droplet com Docker 🏆 ⭐⭐⭐⭐⭐

**Descrição:**
Criar droplet Ubuntu 24.04 LTS + Docker + GitHub Actions para deploy automatizado.

**Prós:**
- ✅ **Ambiente idêntico**: Dev/Prod usam mesmo Dockerfile
- ✅ **Controle total**: SSH, logs, customizações
- ✅ **Zero downtime**: Testa antes de migrar DNS
- ✅ **Isola v2.3.5**: Mantém droplet antigo rodando durante transição
- ✅ **CI/CD pronto**: Workflow já criado (modify deploy.sh)
- ✅ **Custo previsível**: $6-12/mês (1-2GB RAM)
- ✅ **Portabilidade**: Migra para outro provedor facilmente
- ✅ **Let's Encrypt**: Certbot configurável

**Contras:**
- ❌ **Setup inicial**: 1-2h de configuração
- ❌ **Manutenção**: Atualizações de segurança manuais
- ❌ **Mais complexo**: Requer conhecimento Docker + Linux

**Custo Estimado:**
- **Basic Droplet**: $6/mês (1GB RAM, 25GB disk)
- **Premium Intel**: $12/mês (2GB RAM, 50GB disk) - recomendado para produção

**Complexidade:** 🟡 Média

**Recomendação:** ⭐⭐⭐⭐⭐ (95/100) **← ESCOLHA RECOMENDADA**
Melhor custo-benefício, máximo controle, ambiente dev/prod idênticos.

---

### Opção 3: Atualizar Droplet Atual (In-Place Upgrade)

**Descrição:**
Atualizar Ubuntu 18.04 → 22.04/24.04 e PHP 7.2 → 8.1 no droplet existente.

**Prós:**
- ✅ **Aproveita infraestrutura**: Mesmo IP e configuração
- ✅ **Custo zero adicional**: Mantém $6/mês
- ✅ **Menos trabalho DNS**: IP não muda

**Contras:**
- ❌ **MUITO ARRISCADO**: Upgrade pode quebrar sistema
- ❌ **Downtime inevitável**: 30min-2h de indisponibilidade
- ❌ **Breaking changes**: PHP 7.2 → 8.1 quebrará v2.3.5
- ❌ **Sem rollback fácil**: Precisa restaurar backup completo
- ❌ **Gap Dev/Prod persiste**: Ainda diferente do Docker local
- ❌ **Ubuntu 18.04 → 24.04**: 2 major upgrades (18→20→22→24)

**Custo Estimado:**
- Mantém $6/mês

**Complexidade:** 🔴 Alta + Risco elevado

**Recomendação:** ⭐⭐ (40/100) **← NÃO RECOMENDADO**
Arriscado demais, pode quebrar v2.3.5 em produção e não resolve gap dev/prod.

---

### Opção 4: Novo Droplet Tradicional (LAMP)

**Descrição:**
Novo droplet com Ubuntu 24.04 + Apache + PHP 8.1 (sem Docker).

**Prós:**
- ✅ **Familiar**: Stack LAMP tradicional
- ✅ **Controle total**: SSH e customizações
- ✅ **Zero downtime**: Testa antes de migrar DNS

**Contras:**
- ❌ **Gap Dev/Prod**: Local usa Docker, Prod usa LAMP
- ❌ **Configuração manual**: Apache, PHP, extensões, etc
- ❌ **Desperdiça investimento**: Já tem Dockerfile pronto
- ❌ **Menos reprodutível**: "Funciona na minha máquina"

**Custo Estimado:**
- $6-12/mês

**Complexidade:** 🟡 Média

**Recomendação:** ⭐⭐⭐ (65/100)
Funcional mas perde benefícios do Docker já implementado.

---

## 🏆 Estratégia Recomendada: Opção 2 (Novo Droplet com Docker)

### Por que esta é a melhor escolha?

1. **Paridade Dev/Prod**: Mesmo ambiente (Dockerfile) em todos os lugares
2. **Zero Risco**: Droplet antigo continua rodando v2.3.5
3. **Transição gradual**: Testa tudo antes de trocar DNS
4. **CI/CD pronto**: Workflow GitHub Actions já configurado
5. **Custo-benefício**: $6-12/mês com controle total
6. **Futuro**: Fácil migrar para Kubernetes/outro provedor

---

## 📅 Plano de Migração (3 Fases)

### Fase 1: Preparação (1-2 horas)

**1.1 Criar novo droplet:**
```bash
# DigitalOcean Console > Create > Droplets
- Distribution: Ubuntu 24.04 LTS
- Plan: Basic ($6) ou Premium Intel ($12)
- Region: NYC1 (mesmo do atual)
- Size: 1GB RAM (mínimo) ou 2GB (recomendado)
- Authentication: SSH Key (gerar nova)
- Hostname: 4sqmet-prod-v3
```

**1.2 Configurar servidor:**
```bash
# SSH no novo droplet
ssh root@<NOVO_IP>

# Atualizar sistema
apt update && apt upgrade -y

# Instalar Docker + Docker Compose
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Instalar Docker Compose v2
apt install docker-compose-plugin -y

# Verificar instalação
docker --version
docker compose version

# Configurar firewall
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable

# Instalar Certbot para Let's Encrypt
apt install certbot python3-certbot-apache -y
```

**1.3 Configurar aplicação:**
```bash
# Criar diretórios
mkdir -p /var/www/4sqmet
mkdir -p /var/backups/4sqmet
mkdir -p /var/log/4sqmet

# Clonar repositório
cd /var/www/4sqmet
git clone -b refactor-ia https://github.com/gavlinski/Foursquare-Mass-Editor-Tools.git .

# Criar .env de produção
cp .env.example .env
nano .env  # Configurar credenciais
```

**1.4 Build e testes:**
```bash
# Build da imagem Docker
docker build -t 4sqmet:latest .

# Rodar container
docker run -d \
  --name 4sqmet \
  -p 80:80 -p 443:443 \
  -v $(pwd):/var/www/html \
  -v $(pwd)/ssl:/etc/ssl/4sqmet \
  --restart unless-stopped \
  4sqmet:latest

# Verificar logs
docker logs -f 4sqmet

# Testar
curl http://<NOVO_IP>/4sqmet/
```

**1.5 Configurar HTTPS:**
```bash
# Obter certificado Let's Encrypt (temporário com IP)
certbot certonly --standalone -d 4sq.eliotools.site \
  --email seu-email@example.com --agree-tos

# Certificados salvos em:
# /etc/letsencrypt/live/4sq.eliotools.site/fullchain.pem
# /etc/letsencrypt/live/4sq.eliotools.site/privkey.pem
```

---

### Fase 2: CI/CD e Automação (30 min)

**2.1 Atualizar GitHub Secrets:**
```
Settings → Secrets → Actions

DEPLOY_SSH_KEY: [Nova chave privada do droplet]
DEPLOY_USER: root
DEPLOY_HOST: <NOVO_IP>  # Ou 4sq.eliotools.site após DNS
```

**2.2 Modificar deploy.sh:**
```bash
# Ajustar script para Docker:
# - docker compose down
# - git pull
# - docker compose build
# - docker compose up -d
# - docker logs --tail 50 4sqmet
```

**2.3 Testar deploy automático:**
```bash
# Criar commit de teste
git commit --allow-empty -m "test: Trigger deploy automático"
git push origin refactor-ia

# Monitorar no GitHub Actions
# https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/actions
```

---

### Fase 3: Migração DNS e Go-Live (15 min + 1h propagação)

**3.1 Testes finais no novo servidor:**
```bash
# Via /etc/hosts local (seu computador)
sudo nano /etc/hosts
# Adicionar: <NOVO_IP> 4sq.eliotools.site

# Testar todos os fluxos:
- Login OAuth Foursquare
- Busca de venues
- Upload CSV
- Edição em massa
- Maps (Google Maps API)
```

**3.2 Atualizar DNS (Namecheap):**
```
1. Namecheap Dashboard → Domain List → eliotools.site → Manage
2. Advanced DNS → Host Records
3. Atualizar A Record:
   - Type: A Record
   - Host: 4sq (ou @)
   - Value: <NOVO_IP>
   - TTL: Automatic

4. Aguardar propagação (5min-1h)
```

**3.3 Validar migração:**
```bash
# Verificar propagação DNS
dig 4sq.eliotools.site
nslookup 4sq.eliotools.site

# Remover override /etc/hosts
sudo nano /etc/hosts  # Remover linha adicionada

# Testar URL pública
curl -I https://4sq.eliotools.site

# Monitorar logs
ssh root@<NOVO_IP>
docker logs -f 4sqmet
```

**3.4 Checklist pós-migração:**
- [ ] Site carrega sem erros (HTTP 200)
- [ ] HTTPS funciona (certificado válido)
- [ ] Login OAuth Foursquare OK
- [ ] Busca de venues OK
- [ ] Upload CSV OK
- [ ] Edição em massa OK
- [ ] Google Maps renderiza OK
- [ ] Session management OK
- [ ] Deploy automático funcionando

---

### Fase 4 (Opcional): Desativar droplet antigo (após 7 dias)

**4.1 Backup final do droplet antigo:**
```bash
# DigitalOcean Console
Droplets → ubuntu-s-1vcpu-1gb-nyc1-01 → Snapshots → Take Snapshot
Nome: "4sqmet-v2.3.5-final-backup-2026-03-01"
```

**4.2 Desligar droplet:**
```bash
# Power Off (não deletar ainda)
# Manter snapshot por 30 dias antes de deletar definitivamente
```

**4.3 Economia mensal:**
```
Custo atual:
- Droplet antigo: $6/mês
- Droplet novo: $6-12/mês

Após desativar antigo:
- Total: $6-12/mês (sem mudança significativa)
```

---

## 🔧 Alternativas e Ajustes

### Se preferir App Platform:

**Vantagens adicionais:**
- Preview de PR automático com URL temporária
- Escalabilidade horizontal automática
- Monitoramento integrado

**Passos:**
```
1. DigitalOcean Console → App Platform → Create App
2. Source: GitHub → gavlinski/Foursquare-Mass-Editor-Tools
3. Branch: refactor-ia
4. Detect Dockerfile: Yes
5. Environment Variables: Adicionar do .env
6. Domain: 4sq.eliotools.site
7. Deploy!
```

**Custo real:**
- $12/mês (Professional - 1GB RAM)
- + $0.15/GB tráfego excedente

### Se precisar economizar:

**Opção: Shared CPU Droplet 512MB ($4/mês)**
- Suficiente para low traffic (<1000 req/dia)
- Pode ter performance reduzida em picos
- Boa para começar, fazer upgrade depois

---

## 📊 Comparação de Custos (12 meses)

| Opção | Custo Mensal | Custo Anual | Setup | Manutenção |
|-------|--------------|-------------|-------|------------|
| **Droplet atual (manter)** | $6 | $72 | ⚠️ Arriscado | Alta |
| **Novo Droplet 1GB** | $6 | $72 | Média | Média |
| **Novo Droplet 2GB** | $12 | $144 | Média | Média |
| **App Platform Basic** | $5 | $60 | Baixa | Zero |
| **App Platform Pro** | $12 | $144 | Baixa | Zero |

**Considerando:**
- Domínio: $15-20/ano (já pago)
- SSL: Grátis (Let's Encrypt ou DO managed)
- GitHub: Grátis (Actions incluídas em plano free)

---

## 🎯 Recomendação Final

### Cenário 1: Máximo Controle + Ambiente Consistente
**→ Novo Droplet 2GB com Docker ($12/mês)**
- Performance estável
- Dev/Prod idênticos
- Futuro escalável

### Cenário 2: Máxima Simplicidade
**→ App Platform Professional ($12/mês)**
- Zero manutenção
- Deploy automático nativo
- Preview de PRs

### Cenário 3: Budget Limitado
**→ Novo Droplet 1GB com Docker ($6/mês)**
- Custo mínimo
- Controle total
- Performance aceitável para início

---

## ✅ Próximos Passos

Confirme qual estratégia prefere e posso:

1. **Criar scripts de setup automatizados** (`setup-droplet.sh`)
2. **Atualizar workflow GitHub Actions** para deployment Docker
3. **Gerar checklist detalhado** passo-a-passo
4. **Criar docker-compose.yml** para produção com Let's Encrypt
5. **Documentar rollback procedures** completos

**Qual opção você prefere explorar primeiro?**

---

**Criado em**: 28 de Fevereiro de 2026  
**Versão**: 1.0.0  
**Autor**: AI Agent + Elio
