# 📋 Checklist de Migração - Novo Droplet Docker

## ✅ Fase 0: Pré-requisitos (15 min)

### 0.1 Gerar Chave SSH

```bash
# No seu Mac
ssh-keygen -t ed25519 -C "4sqmet-prod-droplet" -f ~/.ssh/4sqmet_prod

# Copiar chave pública
cat ~/.ssh/4sqmet_prod.pub | pbcopy
```

- [ ] Chave gerada: `~/.ssh/4sqmet_prod`
- [ ] Chave pública copiada

### 0.2 Adicionar SSH Key ao DigitalOcean

```
1. DigitalOcean Console → Settings → Security → SSH Keys
2. Click "Add SSH Key"
3. Name: 4sqmet-prod-key
4. Public Key: [Colar do clipboard]
5. Add SSH Key
```

- [ ] SSH Key adicionada ao DigitalOcean

---

## ✅ Fase 1: Criar Droplet (5 min)

### Especificações Detalhadas:

```yaml
Region: New York - NYC3
Image: Marketplace → "Docker on Ubuntu 22.04"
Plan: Shared CPU - Basic - 1GB RAM - $6/mo
  - 1 vCPU
  - 1 GB RAM
  - 25 GB SSD
  - 1 TB Transfer

Authentication: SSH Key → 4sqmet-prod-key
Monitoring: ✅ Enable free metrics
IPv6: ⬜ Disabled
Backups: ⬜ Disabled (pode habilitar depois)

Hostname: 4sqmet-prod-v3
Tags: production, 4sqmet, foursquare-tools, docker, web-app
Project: Default ou criar "Foursquare Tools"
```

### Passos no Console:

1. [ ] Acessar: https://cloud.digitalocean.com/droplets
2. [ ] Click: **Create → Droplets**
3. [ ] Região: **New York - NYC3**
4. [ ] Imagem: **Marketplace → Docker on Ubuntu 22.04**
5. [ ] Plano: **Basic - 1GB - $6/mo**
6. [ ] SSH: Selecionar **4sqmet-prod-key**
7. [ ] Monitoring: ✅ **Enable**
8. [ ] Hostname: **4sqmet-prod-v3**
9. [ ] Tags: `production`, `4sqmet`, `foursquare-tools`, `docker`, `web-app`
10. [ ] Click: **Create Droplet**
11. [ ] Aguardar ~60 segundos (provisionamento)

### Anotar Informações:

```bash
# Após criação, anotar:
DROPLET_IP=___.___.___.___ # (exemplo: 165.227.xxx.xxx)
DROPLET_ID=________________ # (exemplo: 47290481)
```

- [ ] IP anotado: `DROPLET_IP=_______________`

---

## ✅ Fase 2: Configurar Servidor (10 min)

### 2.1 Testar Conexão SSH

```bash
# No seu Mac
ssh -i ~/.ssh/4sqmet_prod root@${DROPLET_IP}
# Confirmar fingerprint: yes
```

- [ ] SSH conectado com sucesso

### 2.2 Executar Setup Automatizado

```bash
# No seu Mac
cd /Users/elio/Projetos/Foursquare-Mass-Editor-Tools

# Copiar script para servidor
scp -i ~/.ssh/4sqmet_prod scripts/setup-droplet.sh root@${DROPLET_IP}:/tmp/

# Conectar ao servidor
ssh -i ~/.ssh/4sqmet_prod root@${DROPLET_IP}

# No servidor
bash /tmp/setup-droplet.sh
```

**O que o script faz:**
- ✅ Atualiza sistema Ubuntu
- ✅ Verifica/instala Docker
- ✅ Configura firewall (UFW)
- ✅ Instala Certbot
- ✅ Cria diretórios (/var/www/4sqmet, /var/backups, /var/log)
- ✅ Clona repositório GitHub
- ✅ Cria .env placeholder (será atualizado no primeiro deploy)
- ✅ Build imagem Docker
- ✅ Inicia container

**Tempo estimado**: 5-10 minutos

- [ ] Script executado com sucesso
- [ ] Container `4sqmet` rodando

> **💡 Nota**: Não precisa configurar `.env` manualmente! Ele será criado/atualizado automaticamente durante o deploy via secrets do GitHub Actions.

### 2.3 Verificar Aplicação

```bash
# No servidor
docker ps
docker logs -f 4sqmet

# Testar HTTP
curl -I http://localhost/4sqmet/

# Pode retornar erro 500 ou falha de OAuth - é esperado!
# As credenciais reais serão injetadas no primeiro deploy
```

- [ ] Container rodando
- [ ] Aplicação iniciou (mesmo com erro de credenciais)

---

## ✅ Fase 3: Testes Via IP (15 min)

### 3.1 Adicionar Override DNS Local (seu Mac)

```bash
# No seu Mac
sudo nano /etc/hosts

# Adicionar linha:
${DROPLET_IP} 4sq.eliotools.site

# Salvar: Ctrl+O, Enter, Ctrl+X
```

- [ ] Override DNS configurado

### 3.2 Testar Fluxos Principais

Abrir no navegador: `http://4sq.eliotools.site/4sqmet/`

**Checklist de Testes:**
- [ ] Página principal carrega
- [ ] Login OAuth Foursquare funciona
- [ ] Busca de venues por coordenadas
- [ ] Upload CSV funciona
- [ ] Edição em massa abre
- [ ] Google Maps renderiza
- [ ] Salvar edições funciona
- [ ] Session persiste (refresh página)

**Se algum teste falhar:**
```bash
# Ver logs
ssh -i ~/.ssh/4sqmet_prod root@${DROPLET_IP}
docker logs --tail 100 -f 4sqmet
```

---

## ✅ Fase 4: Configurar CI/CD (15 min)

### 4.1 Configurar GitHub Secrets

```
1. GitHub → Repositório
2. Settings → Secrets and variables → Actions
3. New repository secret
```

**Secrets para adicionar (10 total):**

**A. Deploy SSH (3 secrets):**
```yaml
DEPLOY_SSH_KEY:
  # Conteúdo de ~/.ssh/4sqmet_prod (chave PRIVADA)
  # Copiar: cat ~/.ssh/4sqmet_prod | pbcopy
  
DEPLOY_USER:
  # Valor: root
  
DEPLOY_HOST:
  # Valor: ${DROPLET_IP} ou 4sq.eliotools.site (após DNS)
```

**B. Foursquare API (3 secrets):**
```yaml
FOURSQUARE_CLIENT_KEY:
  # Seu Client ID (50 caracteres)
  # Obter em: https://pt.foursquare.com/developers/home
  
FOURSQUARE_CLIENT_SECRET:
  # Seu Client Secret (48 caracteres)
  
FOURSQUARE_REDIRECT_URI:
  # Valor: https://4sq.eliotools.site/4sqmet/index.php
```

**C. Google Maps API (2 secrets):**
```yaml
GOOGLE_MAPS_API_KEY:
  # Sua API Key (começa com AIzaSy)
  # Obter em: https://console.cloud.google.com/google/maps-apis/credentials
  
GOOGLE_MAPS_MAP_ID:
  # Seu Map ID (24 caracteres hexadecimais)
```

**D. Application Config (1 secret):**
```yaml
APP_URL:
  # Valor: https://4sq.eliotools.site
```

**E. Total:**
- [ ] `DEPLOY_SSH_KEY` configurado
- [ ] `DEPLOY_USER` configurado  
- [ ] `DEPLOY_HOST` configurado
- [ ] `FOURSQUARE_CLIENT_KEY` configurado
- [ ] `FOURSQUARE_CLIENT_SECRET` configurado
- [ ] `FOURSQUARE_REDIRECT_URI` configurado
- [ ] `GOOGLE_MAPS_API_KEY` configurado
- [ ] `GOOGLE_MAPS_MAP_ID` configurado
- [ ] `APP_URL` configurado

> **💡 Benefício**: O deploy agora cria/atualiza `.env` automaticamente no servidor com esses valores. Zero configuração manual!

### 4.2 Testar Deploy Manual

```bash
# No seu Mac
cd /Users/elio/Projetos/Foursquare-Mass-Editor-Tools

# Configurar variáveis
export DEPLOY_USER=root
export DEPLOY_HOST=${DROPLET_IP}
export SSH_KEY_PATH=~/.ssh/4sqmet_prod

# Executar deploy
bash deploy.sh
```

- [ ] Deploy manual bem-sucedido

### 4.3 Testar GitHub Actions

```
1. GitHub → Actions
2. Selecionar "CI/CD Pipeline - Build and Deploy"
3. Run workflow → Branch: refactor-ia
4. Aguardar execução (~5 min)
```

- [ ] GitHub Actions executado com sucesso
- [ ] Deploy automático funcionou

---

## ✅ Fase 5: Migração DNS (20 min)

### 5.1 Remover Override Local

```bash
# No seu Mac
sudo nano /etc/hosts

# Remover linha: ${DROPLET_IP} 4sq.eliotools.site
# Salvar: Ctrl+O, Enter, Ctrl+X
```

- [ ] Override DNS removido

### 5.2 Atualizar DNS no Namecheap

```
1. Namecheap Dashboard → Domain List
2. eliotools.site → Manage
3. Advanced DNS → Host Records
4. Editar A Record:
   - Type: A Record
   - Host: 4sq (ou @ para root domain)
   - Value: ${DROPLET_IP}
   - TTL: Automatic (ou 300 para 5 minutos)
5. Save All Changes
```

- [ ] DNS atualizado para novo IP
- [ ] TTL configurado (recomendado: 300 para facilitar testes)

### 5.3 Verificar Propagação DNS

```bash
# No seu Mac
# Aguardar 5-60 minutos para propagação

# Verificar:
dig 4sq.eliotools.site
nslookup 4sq.eliotools.site

# Deve mostrar: ${DROPLET_IP}
```

- [ ] DNS propagado (retorna novo IP)

### 5.4 Testar URL Pública

```bash
curl -I http://4sq.eliotools.site/4sqmet/
# Deve retornar: HTTP/1.1 200 OK
```

- [ ] URL pública funcionando

---

## ✅ Fase 6: Configurar HTTPS (15 min)

### 6.1 Obter Certificado Let's Encrypt

```bash
# SSH no servidor
ssh -i ~/.ssh/4sqmet_prod root@${DROPLET_IP}

# Parar container temporariamente (Certbot precisa porta 80)
docker stop 4sqmet

# Obter certificado
certbot certonly --standalone -d 4sq.eliotools.site \
  --email seu-email@example.com --agree-tos --non-interactive

# Certificados salvos em:
# /etc/letsencrypt/live/4sq.eliotools.site/fullchain.pem
# /etc/letsencrypt/live/4sq.eliotools.site/privkey.pem
```

- [ ] Certificado SSL obtido

### 6.2 Configurar SSL no Container

```bash
# No servidor
cd /var/www/4sqmet

# Criar link simbólico (se não existir)
ln -sf /etc/letsencrypt/live/4sq.eliotools.site ssl/production

# Atualizar apache-config.conf (se necessário)
# Certificados já configurados em:
# SSLCertificateFile /etc/ssl/4sqmet/fullchain.pem
# SSLCertificateKeyFile /etc/ssl/4sqmet/privkey.pem

# Reiniciar container com SSL
docker start 4sqmet
```

- [ ] SSL configurado
- [ ] Container reiniciado

### 6.3 Testar HTTPS

```bash
# No seu Mac
curl -I https://4sq.eliotools.site/4sqmet/
# Deve retornar: HTTP/1.1 200 OK

# Verificar certificado
openssl s_client -connect 4sq.eliotools.site:443 -servername 4sq.eliotools.site | grep "Verify return code"
# Deve retornar: Verify return code: 0 (ok)
```

- [ ] HTTPS funcionando
- [ ] Certificado válido
- [ ] HTTP redireciona para HTTPS

### 6.4 Configurar Auto-Renewal

```bash
# No servidor
systemctl enable certbot.timer
systemctl start certbot.timer

# Verificar status
systemctl status certbot.timer

# Testar renovação (dry-run)
certbot renew --dry-run
```

- [ ] Auto-renewal habilitado
- [ ] Dry-run bem-sucedido

---

## ✅ Fase 7: Validação Final (15 min)

### 7.1 Testes Completos em HTTPS

Acessar: `https://4sq.eliotools.site/4sqmet/`

**Checklist de Validação:**
- [ ] ✅ HTTPS funcionando (cadeado verde)
- [ ] ✅ Login OAuth Foursquare
- [ ] ✅ Busca de venues
- [ ] ✅ Upload CSV
- [ ] ✅ Edição em massa
- [ ] ✅ Google Maps renderiza
- [ ] ✅ Salvar alterações
- [ ] ✅ Session persiste
- [ ] ✅ Cookies Secure funcionando
- [ ] ✅ Performance adequada

### 7.2 Configurar Monitoramento

```bash
# DigitalOcean Console
Droplets → 4sqmet-prod-v3 → Monitoring

# Criar alertas:
1. CPU > 80% por 5 min → Warning
2. CPU > 95% por 5 min → Critical
3. Memory > 85% por 5 min → Warning
4. Memory > 95% por 5 min → Critical
5. Disk > 80% → Warning
```

- [ ] Alertas configurados
- [ ] Email de notificação validado

### 7.3 Criar Backup Manual

```bash
# No servidor
cd /var/www/4sqmet
tar -czf /var/backups/4sqmet/manual_backup_$(date +%Y%m%d_%H%M%S).tar.gz \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='.git' \
  .
  
# Listar backups
ls -lh /var/backups/4sqmet/
```

- [ ] Backup manual criado

---

## ✅ Fase 8: Desativar Droplet Antigo (após 7 dias)

⚠️ **Aguardar 1 semana** de validação em produção antes de desativar

### 8.1 Criar Snapshot do Droplet Antigo

```
1. DigitalOcean Console → Droplets
2. ubuntu-s-1vcpu-1gb-nyc1-01
3. Snapshots → Take Snapshot
4. Name: 4sqmet-v2.3.5-final-backup-2026-03-XX
5. Create Snapshot (aguardar ~10 min)
```

- [ ] Snapshot criado

### 8.2 Power Off Droplet Antigo

```
1. DigitalOcean Console → Droplets
2. ubuntu-s-1vcpu-1gb-nyc1-01
3. More → Power Off
4. Confirm
```

⚠️ **NÃO deletar ainda**. Manter por 30 dias antes de deletar definitivamente.

- [ ] Droplet antigo desligado
- [ ] Data para deletar: ________ (30 dias após power off)

---

## 📊 Resumo de Custos

### Migração (Temporário):
```
Droplet Antigo (1GB):  $6/mês (durante transição)
Droplet Novo (1GB):    $6/mês
------------------------
Total Temporário:      $12/mês (apenas durante migração)
```

### Produção (Após desativar antigo):
```
Droplet Novo (1GB):     $6/mês
Monitoring:             $0/mês (free)
Bandwidth 1TB:          $0/mês (incluído)
SSL Let's Encrypt:      $0/mês (free)
Snapshots (opcional):   $0.05/GB/mês
------------------------
Total Final:            $6/mês ($72/ano)
```

---

## 🆘 Rollback de Emergência

Se algo der errado após migração DNS:

### Opção 1: Voltar DNS para Droplet Antigo

```
1. Namecheap → eliotools.site → Advanced DNS
2. A Record: @ → 206.189.180.222 (IP antigo)
3. Save
4. Aguardar propagação (5-60 min)
```

### Opção 2: Restaurar Backup no Novo Droplet

```bash
ssh -i ~/.ssh/4sqmet_prod root@${DROPLET_IP}
cd /var/www/4sqmet
docker stop 4sqmet && docker rm 4sqmet
tar -xzf /var/backups/4sqmet/backup_TIMESTAMP.tar.gz
docker build -t 4sqmet:latest .
docker run -d --name 4sqmet --restart unless-stopped -p 80:80 -p 443:443 -v $(pwd):/var/www/html 4sqmet:latest
```

---

## ✅ Checklist Final

- [ ] Droplet criado (NYC3, Docker Ubuntu 22.04, 1GB)
- [ ] SSH Key configurada
- [ ] Setup automatizado executado
- [ ] Container Docker rodando
- [ ] Testes via IP bem-sucedidos
- [ ] GitHub Actions configurado
- [ ] DNS migrado para novo IP
- [ ] SSL Let's Encrypt configurado
- [ ] HTTPS funcionando
- [ ] Todos os fluxos validados em HTTPS
- [ ] Monitoramento configurado
- [ ] Backups criados
- [ ] Droplet antigo desligado (após 7 dias)
- [ ] Documentação atualizada

---

## 📚 Documentação Relacionada

- **[DROPLET_SPECS.md](DROPLET_SPECS.md)**: Especificações detalhadas
- **[DEPLOYMENT_STRATEGY.md](DEPLOYMENT_STRATEGY.md)**: Análise de opções
- **[DEPLOY.md](DEPLOY.md)**: Guia quick start
- **[../https/HTTPS_SETUP.md](../https/HTTPS_SETUP.md)**: Configuração SSL completa

---

**Tempo Total Estimado**: 2-3 horas (incluindo propagação DNS)  
**Complexidade**: 🟡 Média  
**Custo**: $6/mês ($72/ano)  
**Downtime**: Zero (testa antes de trocar DNS)

---

**Criado em**: 28 de Fevereiro de 2026  
**Para versão**: v3.0.0  
**Última atualização**: 28/02/2026
