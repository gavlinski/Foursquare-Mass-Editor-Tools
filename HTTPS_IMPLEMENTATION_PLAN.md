# Plano de Implementação HTTPS
## Foursquare Mass Editor Tools

> **Status**: 📋 Planejado (aguardando correção de bugs prioritários)  
> **Custo Total**: R$ 0,00  
> **Tempo Estimado**: 1h30 (3 sprints)  
> **Data de Criação**: 23 de fevereiro de 2026

---

## 🎯 Objetivo

Implementar HTTPS em ambos ambientes (desenvolvimento e produção) para:

1. **Resolver conflito técnico**: Cookies configurados como `Secure` mas URLs são HTTP
2. **Segurança OAuth**: Foursquare recomenda HTTPS para redirect URIs
3. **Experiência do usuário**: Eliminar avisos "Não seguro" no navegador
4. **Conformidade**: Cookies `Secure` **só funcionam em HTTPS**

---

## 🚨 Problema Atual

### Conflito Identificado

```dotenv
# .env.example (configuração atual)
SESSION_SECURE=true              # ⚠️ Requer HTTPS
COOKIE_SECURE=true               # ⚠️ Requer HTTPS
FOURSQUARE_REDIRECT_URI=http://  # ❌ HTTP (conflito)
APP_URL=http://                  # ❌ HTTP (conflito)
```

### Impacto

- ⚠️ Sessões podem não persistir corretamente
- ⚠️ OAuth tokens em cookies podem ser rejeitados silenciosamente
- ⚠️ Navegadores exibem aviso "Não seguro" na barra de endereços
- ⚠️ Cookies `Secure` são bloqueados em HTTP por navegadores modernos

---

## 📋 Plano de Implementação

### **Sprint 1: Setup HTTPS Local com mkcert** (~30 minutos)

#### 1.1. Instalar mkcert

```bash
# macOS
brew install mkcert nss

# Instalar CA local (executa uma vez)
mkcert -install
```

**O que faz**: Cria uma Certificate Authority (CA) local confiável no sistema.

#### 1.2. Gerar Certificados SSL

```bash
cd /Users/elio/Projetos/Foursquare-Mass-Editor-Tools

# Criar diretório para certificados
mkdir -p ssl

# Gerar certificados para localhost
mkcert -cert-file ssl/localhost.pem \
       -key-file ssl/localhost-key.pem \
       localhost 127.0.0.1 ::1
```

**Output esperado**:
```
Created a new certificate valid for the following names 📜
 - "localhost"
 - "127.0.0.1"
 - "::1"

The certificate is at "ssl/localhost.pem" and the key at "ssl/localhost-key.pem" ✅
```

#### 1.3. Atualizar `.gitignore`

```bash
echo "ssl/*.pem" >> .gitignore
```

**Razão**: Certificados são específicos por máquina, não devem ser commitados.

#### 1.4. Modificar `Dockerfile`

**Arquivo**: `Dockerfile`

```dockerfile
# ANTES (linha ~4):
RUN a2enmod rewrite headers deflate expires

# DEPOIS:
RUN a2enmod rewrite headers deflate expires ssl
```

**Adicionar após linha de módulos**:

```dockerfile
# Copiar certificados SSL para desenvolvimento
COPY ssl/localhost.pem /etc/ssl/certs/localhost.pem
COPY ssl/localhost-key.pem /etc/ssl/private/localhost-key.pem
RUN chmod 644 /etc/ssl/certs/localhost.pem && \
    chmod 600 /etc/ssl/private/localhost-key.pem
```

#### 1.5. Modificar `apache-config.conf`

**Arquivo**: `apache-config.conf`

**Adicionar ANTES do `<VirtualHost *:80>`**:

```apache
# ====================================
# HTTPS Configuration (Port 443)
# ====================================
<VirtualHost *:443>
    ServerName localhost
    DocumentRoot /var/www/html
    
    # SSL Engine
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/localhost.pem
    SSLCertificateKeyFile /etc/ssl/private/localhost-key.pem
    
    # SSL Protocol and Cipher Configuration
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5
    SSLHonorCipherOrder on
    
    # HSTS (HTTP Strict Transport Security)
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    
    # Alias e Diretórios (mesmo do VirtualHost 80)
    Alias /4sqmet /var/www/html
    
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex main.php index.php index.html
    </Directory>
    
    # Cache e Compression (mesmo do VirtualHost 80)
    <FilesMatch "\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
    
    # Gzip (mesmo do VirtualHost 80)
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
    </IfModule>
    
    # CSP (mesmo do VirtualHost 80)
    Header set Content-Security-Policy "default-src 'self' 'unsafe-inline' 'unsafe-eval' https://ajax.googleapis.com https://maps.googleapis.com https://api.foursquare.com https://www.statcounter.com https://*.statcounter.com; img-src 'self' data: https://*.foursquare.com https://*.4sqi.net https://*.googleapis.com https://*.gstatic.com https://c.statcounter.com; frame-src 'self' https://*.foursquare.com;"
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName localhost
    Redirect permanent / https://localhost/
</VirtualHost>
```

**Nota**: Substituir todo o `<VirtualHost *:80>` existente pelo código acima.

#### 1.6. Modificar `dev.sh`

**Arquivo**: `dev.sh`

**Encontrar linha com `-p 80:80` e modificar**:

```bash
# ANTES:
docker run -d --name foursquare-mass-editor \
    -p 80:80 \
    -v "$PWD":/var/www/html \
    foursquare-mass-editor:latest

# DEPOIS:
docker run -d --name foursquare-mass-editor \
    -p 80:80 \
    -p 443:443 \
    -v "$PWD":/var/www/html \
    foursquare-mass-editor:latest
```

#### 1.7. Atualizar `.env`

**Arquivo**: `.env` (ou `.env.example`)

```dotenv
# ANTES:
APP_URL=http://localhost/4sqmet
FOURSQUARE_REDIRECT_URI=http://localhost/4sqmet/index.php

# DEPOIS:
APP_URL=https://localhost/4sqmet
FOURSQUARE_REDIRECT_URI=https://localhost/4sqmet/index.php
```

#### 1.8. Rebuild e Testar

```bash
# Parar container
./dev.sh stop

# Reconstruir imagem
./dev.sh build

# Iniciar container
./dev.sh start

# Testar HTTPS
curl -I https://localhost/4sqmet/

# Validar certificado
openssl s_client -connect localhost:443 -servername localhost < /dev/null
```

**Output esperado**:
```
HTTP/1.1 200 OK
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

#### 1.9. Atualizar Foursquare Developer Console

1. Acessar: https://foursquare.com/developers/apps
2. Selecionar sua aplicação
3. **Redirect URIs**: Mudar de `http://localhost/4sqmet/index.php` para `https://localhost/4sqmet/index.php`
4. Salvar

---

### **Sprint 2: Documentação HTTPS** (~30 minutos)

#### 2.1. Criar Guia de Setup

**Arquivo**: `docs/HTTPS_SETUP.md` (novo)

**Conteúdo**:

```markdown
# Setup HTTPS - Guia Completo

## Desenvolvimento (localhost)

### Pré-requisitos
- mkcert instalado (`brew install mkcert`)
- Docker rodando

### Instalação
[Copiar passos da Sprint 1]

## Produção (4sq.eliotools.site)

### Via Let's Encrypt (Recomendado)
[Copiar passos da Sprint 3]

## Troubleshooting

### Erro: "Your connection is not private"
- Verifique se `mkcert -install` foi executado
- Reinicie o navegador

### Erro: "SSL: CERTIFICATE_VERIFY_FAILED"
- Certificados não copiados corretamente no Docker
- Execute: `./dev.sh build` novamente
```

#### 2.2. Atualizar BUILD_DEPLOY_README.md

**Adicionar seção**:

```markdown
## 🔒 HTTPS Configuration

### Development
Uses mkcert for trusted local certificates.
See [HTTPS_SETUP.md](docs/HTTPS_SETUP.md) for details.

### Production
Uses Let's Encrypt for free, auto-renewing certificates.
Configured via certbot (see Sprint 3 script).
```

#### 2.3. Criar Checklist de Validação

**Arquivo**: `HTTPS_VALIDATION_CHECKLIST.md` (novo)

```markdown
# HTTPS Validation Checklist

## Desenvolvimento (localhost)

- [ ] `https://localhost/4sqmet/` carrega sem avisos
- [ ] Certificado reconhecido como válido (cadeado verde)
- [ ] Cookies Secure funcionando (DevTools → Application → Cookies)
- [ ] OAuth redirect funcionando com HTTPS
- [ ] HTTP (porta 80) redireciona para HTTPS (porta 443)
- [ ] HSTS header presente (`Strict-Transport-Security`)
- [ ] Gzip funcionando em HTTPS (Content-Encoding: gzip)
- [ ] Cache headers funcionando em HTTPS

## Produção (4sq.eliotools.site)

- [ ] `https://4sq.eliotools.site` carrega sem avisos
- [ ] Certificado válido (emitido por Let's Encrypt)
- [ ] Certificado não expirado (validade: 90 dias)
- [ ] Renovação automática configurada (certbot renew)
- [ ] HTTP redireciona para HTTPS
- [ ] HSTS configurado
- [ ] Teste SSL Labs: A+ rating (https://www.ssllabs.com/ssltest/)
```

---

### **Sprint 3: Script Let's Encrypt para Produção** (~30 minutos)

#### 3.1. Criar Script de Setup

**Arquivo**: `scripts/setup-ssl-production.sh` (novo)

```bash
#!/bin/bash
# Setup SSL com Let's Encrypt para Produção
# Foursquare Mass Editor Tools
# Data: 2026-02-23

set -e  # Exit on error

echo "🔒 Setup SSL/HTTPS com Let's Encrypt"
echo "===================================="
echo ""

# Variáveis
DOMAIN="4sq.eliotools.site"
EMAIL="your-email@example.com"  # SUBSTITUIR

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then 
   echo "❌ Este script precisa ser executado como root"
   echo "   Use: sudo bash setup-ssl-production.sh"
   exit 1
fi

# Verificar se domínio está apontando para este servidor
echo "🔍 Verificando DNS do domínio..."
CURRENT_IP=$(curl -s ifconfig.me)
DOMAIN_IP=$(dig +short $DOMAIN | tail -n1)

if [ "$CURRENT_IP" != "$DOMAIN_IP" ]; then
    echo "⚠️  AVISO: Domínio não aponta para este servidor"
    echo "   IP do servidor: $CURRENT_IP"
    echo "   IP do domínio:  $DOMAIN_IP"
    read -p "Deseja continuar mesmo assim? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Instalar certbot
echo ""
echo "📦 Instalando certbot..."
apt-get update
apt-get install -y certbot python3-certbot-apache

# Verificar se Apache está rodando
if ! systemctl is-active --quiet apache2; then
    echo "❌ Apache não está rodando"
    echo "   Execute: systemctl start apache2"
    exit 1
fi

# Habilitar mod_ssl
echo ""
echo "🔧 Habilitando mod_ssl..."
a2enmod ssl
systemctl reload apache2

# Obter certificado Let's Encrypt
echo ""
echo "🔐 Obtendo certificado Let's Encrypt..."
certbot --apache -d $DOMAIN --non-interactive --agree-tos --email $EMAIL

# Verificar se certificado foi criado
if [ ! -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
    echo "❌ Falha ao obter certificado"
    exit 1
fi

# Configurar renovação automática
echo ""
echo "🔄 Configurando renovação automática..."
systemctl enable certbot.timer
systemctl start certbot.timer

# Testar renovação
certbot renew --dry-run

echo ""
echo "✅ Setup SSL completo!"
echo ""
echo "📋 Informações do certificado:"
certbot certificates
echo ""
echo "🔄 Renovação automática:"
echo "   O certificado será renovado automaticamente a cada 90 dias"
echo "   Verificar status: systemctl status certbot.timer"
echo ""
echo "🌐 Acesse: https://$DOMAIN"
echo ""
```

**Tornar executável**:

```bash
chmod +x scripts/setup-ssl-production.sh
```

#### 3.2. Documentar no CICD_SETUP.md

**Adicionar seção**:

```markdown
## 🔒 SSL/HTTPS Setup (Produção)

### Primeira Configuração

1. **Apontar domínio para servidor**:
   ```
   Tipo: A
   Nome: 4sq (ou @)
   Valor: [IP do servidor]
   TTL: 3600
   ```

2. **Aguardar propagação DNS** (~5-30 minutos):
   ```bash
   dig 4sq.eliotools.site
   ```

3. **Executar script de setup**:
   ```bash
   scp scripts/setup-ssl-production.sh root@4sq.eliotools.site:/tmp/
   ssh root@4sq.eliotools.site
   cd /tmp
   sudo bash setup-ssl-production.sh
   ```

### Renovação Automática

- Certificado válido por: **90 dias**
- Renovação automática: **A cada 60 dias** (via certbot.timer)
- Verificar status: `systemctl status certbot.timer`
- Forçar renovação: `certbot renew --force-renewal`

### Troubleshooting

**Erro: "Certbot failed to authenticate"**
- Verificar se domínio aponta para servidor correto
- Verificar se porta 80 está aberta no firewall
- Verificar se Apache está servindo o domínio

**Erro: "Too many requests"**
- Let's Encrypt tem rate limit (5 certificados/semana por domínio)
- Use `--dry-run` para testar sem consumir limite
- Aguarde 1 semana para resetar o limite
```

#### 3.3. Atualizar `deploy.sh`

**Adicionar verificação de HTTPS**:

```bash
# Após deploy, verificar se HTTPS está funcionando
echo "🔒 Verificando HTTPS..."
if curl -I -s https://4sq.eliotools.site | grep -q "HTTP/2 200"; then
    echo "✅ HTTPS funcionando"
else
    echo "⚠️  HTTPS não configurado (execute setup-ssl-production.sh)"
fi
```

---

## 📊 Comparação de Soluções

| Aspecto | mkcert (Dev) | Let's Encrypt (Prod) |
|---------|--------------|----------------------|
| **Custo** | Gratuito | Gratuito |
| **Validade** | Indefinida (local) | 90 dias (auto-renova) |
| **Instalação** | 5 comandos | 1 comando (certbot) |
| **Renovação** | Não necessária | Automática |
| **Confiança** | Local apenas | Mundial |
| **Setup Time** | ~10 minutos | ~5 minutos |
| **Requsitos** | mkcert instalado | Domínio apontando |

---

## ✅ Checklist de Implementação

### Sprint 1: mkcert Local

- [ ] Instalar mkcert (`brew install mkcert`)
- [ ] Executar `mkcert -install`
- [ ] Gerar certificados em `ssl/`
- [ ] Adicionar `ssl/*.pem` ao `.gitignore`
- [ ] Modificar `Dockerfile` (adicionar mod_ssl + copiar certificados)
- [ ] Modificar `apache-config.conf` (VirtualHost 443 + redirect 80→443)
- [ ] Modificar `dev.sh` (mapear porta 443)
- [ ] Atualizar `.env` (URLs para HTTPS)
- [ ] Rebuild Docker (`./dev.sh build`)
- [ ] Testar `https://localhost/4sqmet/`
- [ ] Atualizar Foursquare redirect URIs

### Sprint 2: Documentação

- [ ] Criar `docs/HTTPS_SETUP.md`
- [ ] Atualizar `BUILD_DEPLOY_README.md`
- [ ] Criar `HTTPS_VALIDATION_CHECKLIST.md`
- [ ] Documentar troubleshooting
- [ ] Commit + push

### Sprint 3: Script Produção

- [ ] Criar `scripts/setup-ssl-production.sh`
- [ ] Tornar executável (`chmod +x`)
- [ ] Documentar no `CICD_SETUP.md`
- [ ] Atualizar `deploy.sh` (verificação HTTPS)
- [ ] Testar script em servidor de staging (se disponível)
- [ ] Commit + push

---

## 🔐 Segurança: Best Practices

### Cookies Secure

Após implementar HTTPS, validar em `index.php` e demais arquivos:

```php
// CORRETO após HTTPS
setcookie("oauth_token", $value, [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => true,      // ✅ Funciona com HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

### HSTS (HTTP Strict Transport Security)

```apache
# Já incluído no VirtualHost 443
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

**O que faz**: Força navegador a sempre usar HTTPS, mesmo se usuário digitar `http://`.

### CSP (Content Security Policy)

```apache
# Manter configuração existente, mas validar que funciona em HTTPS
Header set Content-Security-Policy "..."
```

---

## 📈 Próximos Passos (Após Implementação)

1. **Lighthouse Audit**: Verificar score de segurança
2. **SSL Labs Test**: https://www.ssllabs.com/ssltest/
   - Meta: Rating A+
3. **Monitoramento de Expiração**: Configurar alerta 30 dias antes (produção)
4. **Backup de Certificados**: Incluir em rotina de backup

---

## 🐛 Bugs Conhecidos a Corrigir Antes

Conforme discussão, existem bugs prioritários a corrigir antes da implementação de HTTPS:

- [ ] **Bug 1**: [Descrever aqui]
- [ ] **Bug 2**: [Descrever aqui]
- [ ] **Bug 3**: [Descrever aqui]

**Ação**: Corrigir bugs acima → Retomar este plano → Implementar HTTPS

---

## 📞 Suporte

### mkcert

- Documentação: https://github.com/FiloSottile/mkcert
- Issues: Problemas com CA local, navegadores

### Let's Encrypt

- Documentação: https://letsencrypt.org/docs/
- Certbot: https://certbot.eff.org/
- Issues: Rate limits, validação de domínio

---

**Última Atualização**: 23 de fevereiro de 2026  
**Status**: 📋 Aguardando correção de bugs prioritários  
**Responsável**: A definir após bugs corrigidos  
**Estimativa**: 1h30 total (após bugs corrigidos)
