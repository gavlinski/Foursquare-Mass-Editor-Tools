# 🚀 Guia Rápido de Deploy

> **📖 Para análise completa das opções, leia**: [DEPLOYMENT_STRATEGY.md](DEPLOYMENT_STRATEGY.md)

## 🎯 Recomendação: Novo Droplet com Docker

### Por quê?
- ✅ Ambiente dev/prod idênticos
- ✅ Zero downtime na migração
- ✅ CI/CD já configurado
- ✅ Custo-benefício ($6-12/mês)
- ✅ Controle total

---

## ⚡ Quick Start (30 minutos)

### 1. Criar Droplet

```yaml
# DigitalOcean Console > Create > Droplets
Distribution: Ubuntu 24.04 LTS
Plan: Basic $12/mês (2GB RAM) ou $6/mês (1GB)
Region: NYC1
SSH Key: Configure sua chave
Hostname: 4sqmet-prod-v3
```

### 2. Setup Inicial

```bash
# SSH no servidor
ssh root@<NOVO_IP>

# Instalar Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh
apt install docker-compose-plugin -y

# Configurar firewall
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable

# Criar estrutura
mkdir -p /var/www/4sqmet /var/backups/4sqmet
cd /var/www/4sqmet

# Clonar projeto
git clone -b refactor-ia https://github.com/gavlinski/Foursquare-Mass-Editor-Tools.git .
```

### 3. Configurar Aplicação

```bash
# Criar .env
cp .env.example .env
nano .env  # Adicionar credenciais reais

# Build Docker
docker build -t 4sqmet:latest .

# Rodar container
docker run -d --name 4sqmet \
  -p 80:80 -p 443:443 \
  -v $(pwd):/var/www/html \
  --restart unless-stopped \
  4sqmet:latest

# Verificar
docker logs -f 4sqmet
curl http://<NOVO_IP>/4sqmet/
```

### 4. Configurar HTTPS

```bash
# Instalar Certbot
apt install certbot python3-certbot-apache -y

# Obter certificado (após apontar DNS)
certbot --apache -d 4sq.eliotools.site

# Auto-renewal
systemctl enable certbot.timer
```

### 5. Configurar CI/CD

```bash
# GitHub: Settings → Secrets → Actions
DEPLOY_SSH_KEY: [Chave privada]
DEPLOY_USER: root
DEPLOY_HOST: <NOVO_IP>
```

### 6. Migrar DNS

```
Namecheap → eliotools.site → Advanced DNS
A Record: @ → <NOVO_IP>
TTL: Automatic

Aguardar 5-60 minutos para propagação
```

---

## 🔄 Deploy Workflow

### Automático (git push)
```bash
git add .
git commit -m "feat: Nova funcionalidade"
git push origin refactor-ia
# GitHub Actions faz deploy automaticamente
```

### Manual (GitHub UI)
```
Actions → CI/CD Pipeline → Run workflow → Select branch
```

---

## 📊 Arquitetura de Produção

```
┌────────────────────────────────────────────┐
│          Cliente (Browser)                 │
└───────────────┬────────────────────────────┘
                │
                │ HTTPS (Let's Encrypt)
                │
┌───────────────▼────────────────────────────┐
│    eliotools.site (Namecheap DNS)          │
│    → 206.189.180.xxx (DigitalOcean)        │
└───────────────┬────────────────────────────┘
                │
┌───────────────▼────────────────────────────┐
│   Ubuntu 24.04 LTS Droplet                 │
│   ┌────────────────────────────────┐       │
│   │  Docker Container              │       │
│   │  ┌──────────────────────────┐  │       │
│   │  │  Apache 2.4 + PHP 8.1   │  │       │
│   │  │  • mod_rewrite          │  │       │
│   │  │  • mod_ssl              │  │       │
│   │  │  • mod_deflate          │  │       │
│   │  └──────────────────────────┘  │       │
│   │                                │       │
│   │  /var/www/html → App Code      │       │
│   └────────────────────────────────┘       │
│                                            │
│   /etc/letsencrypt → SSL Certs             │
│   /var/backups/4sqmet → Backups            │
└────────────────────────────────────────────┘
                │
        ┌───────┴───────┐
        │               │
┌───────▼──────┐ ┌──────▼────────┐
│ Foursquare   │ │ Google Maps   │
│ API          │ │ API           │
└──────────────┘ └───────────────┘
```

---

## 🛠️ Comandos Úteis

### Gerenciamento Docker
```bash
# Logs
docker logs -f 4sqmet

# Reiniciar
docker restart 4sqmet

# Rebuild
docker stop 4sqmet
docker rm 4sqmet
docker build -t 4sqmet:latest .
docker run -d --name 4sqmet -p 80:80 -p 443:443 -v $(pwd):/var/www/html --restart unless-stopped 4sqmet:latest

# Status
docker ps
docker stats 4sqmet
```

### Deploy Manual
```bash
cd /var/www/4sqmet
git pull origin refactor-ia
docker restart 4sqmet
```

### Backup
```bash
# Criar backup
tar -czf /var/backups/4sqmet/backup_$(date +%Y%m%d_%H%M%S).tar.gz -C /var/www/4sqmet .

# Restaurar backup
cd /var/www/4sqmet
tar -xzf /var/backups/4sqmet/backup_YYYYMMDD_HHMMSS.tar.gz
docker restart 4sqmet
```

### Logs
```bash
# Application logs
docker logs --tail 100 -f 4sqmet

# Apache access logs
docker exec 4sqmet tail -f /var/log/apache2/access.log

# Apache error logs
docker exec 4sqmet tail -f /var/log/apache2/error.log

# System logs
journalctl -u docker -f
```

---

## 🔍 Troubleshooting

### Site não carrega

```bash
# Verificar container
docker ps -a
docker logs 4sqmet

# Verificar porta
netstat -tlnp | grep :80
netstat -tlnp | grep :443

# Testar dentro do container
docker exec 4sqmet curl -I localhost/4sqmet/
```

### Erro de permissões

```bash
# Ajustar ownership
docker exec 4sqmet chown -R www-data:www-data /var/www/html

# Ajustar permissões
docker exec 4sqmet find /var/www/html -type d -exec chmod 755 {} \;
docker exec 4sqmet find /var/www/html -type f -exec chmod 644 {} \;
```

### SSL não funciona

```bash
# Verificar certificado
certbot certificates

# Renovar manualmente
certbot renew

# Testar configuração SSL
openssl s_client -connect 4sq.eliotools.site:443 -servername 4sq.eliotools.site
```

### Deploy falha

```bash
# Verificar GitHub Actions logs
# https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/actions

# Testar SSH manualmente
ssh -i ~/.ssh/4sqmet_deploy root@4sq.eliotools.site

# Verificar secrets configurados
# GitHub → Settings → Secrets → Actions
```

---

## 📚 Documentação Relacionada

- **[DEPLOYMENT_STRATEGY.md](DEPLOYMENT_STRATEGY.md)**: Análise completa das opções
- **[../BUILD_AND_DEPLOY.md](../BUILD_AND_DEPLOY.md)**: Sistema de build, cenários e troubleshooting
- **[../.github/CICD_SETUP.md](../.github/CICD_SETUP.md)**: Configuração do CI/CD
- **[../https/HTTPS_SETUP.md](../https/HTTPS_SETUP.md)**: Configuração HTTPS completa

---

## ✅ Checklist de Deploy

### Pré-requisitos
- [ ] Droplet criado (Ubuntu 24.04 LTS)
- [ ] Docker instalado
- [ ] Firewall configurado (80, 443, SSH)
- [ ] SSH keys configuradas

### Aplicação
- [ ] Repositório clonado
- [ ] `.env` configurado com credenciais
- [ ] Container Docker rodando
- [ ] Health check OK (HTTP 200)

### HTTPS
- [ ] DNS apontando para novo IP
- [ ] Certificado Let's Encrypt instalado
- [ ] HTTPS funcionando
- [ ] Auto-renewal configurado

### CI/CD
- [ ] Secrets do GitHub configurados
- [ ] Deploy workflow testado
- [ ] Deploy automático funcionando

### Validação Final
- [ ] Login OAuth OK
- [ ] Upload CSV OK
- [ ] Busca de venues OK
- [ ] Edição em massa OK
- [ ] Google Maps renderizando
- [ ] Session management OK
- [ ] Monitoramento configurado

---

**Última atualização**: 28 de Fevereiro de 2026  
**Versão**: 3.0.0  
**Suporte**: GitHub Issues
