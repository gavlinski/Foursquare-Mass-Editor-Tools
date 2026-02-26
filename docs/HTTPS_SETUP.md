# Setup HTTPS - Guia Completo
## Foursquare Mass Editor Tools

> **Status**: ✅ Implementado  
> **Data de Implementação**: 25 de fevereiro de 2026

---

## 🎯 Visão Geral

Este guia documenta a implementação completa de HTTPS para os ambientes de desenvolvimento (localhost) e produção (4sq.eliotools.site).

**Benefícios da Implementação:**
- ✅ Cookies `Secure` funcionando corretamente
- ✅ OAuth2 com redirect URIs seguros
- ✅ Headers HSTS (HTTP Strict Transport Security)
- ✅ Eliminação de avisos "Não seguro" nos navegadores
- ✅ Conformidade com boas práticas de segurança

---

## 🔧 Desenvolvimento (localhost)

### Pré-requisitos

- **mkcert** instalado (`brew install mkcert` no macOS)
- **Docker** rodando
- **Git** para versionamento

### Instalação Completa

#### 1. Instalar e Configurar mkcert

```bash
# macOS
brew install mkcert nss

# Instalar CA local (executa uma vez)
mkcert -install
```

**O que faz**: Cria uma Certificate Authority (CA) local confiável no sistema operacional.

#### 2. Gerar Certificados SSL

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

#### 3. Configurar Variáveis de Ambiente

Edite o arquivo `.env` e atualize as URLs para HTTPS:

```dotenv
# ANTES:
APP_URL=http://localhost/4sqmet
FOURSQUARE_REDIRECT_URI=http://localhost/4sqmet/index.php
SESSION_SECURE=false
COOKIE_SECURE=false

# DEPOIS:
APP_URL=https://localhost/4sqmet
FOURSQUARE_REDIRECT_URI=https://localhost/4sqmet/index.php
SESSION_SECURE=true
COOKIE_SECURE=true
```

#### 4. Rebuild da Imagem Docker

```bash
# Parar container existente
./dev.sh stop

# Reconstruir imagem com HTTPS
./dev.sh build

# Iniciar container
./dev.sh start
```

#### 5. Validar HTTPS

```bash
# Testar conexão HTTPS
curl -Ik https://localhost/4sqmet/

# Validar certificado
openssl s_client -connect localhost:443 -servername localhost < /dev/null
```

**Output esperado**:
```
HTTP/1.1 200 OK
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

#### 6. Atualizar Foursquare Developer Console

1. Acesse: https://foursquare.com/developers/apps
2. Selecione sua aplicação
3. **Redirect URIs**: Altere para `https://localhost/4sqmet/index.php`
4. Salve

### URLs de Acesso

- **HTTPS**: https://localhost/4sqmet/ (primário)
- **HTTP**: http://localhost/4sqmet/ (redireciona para HTTPS)
- **Debug**: https://localhost/4sqmet/debug/

### Estrutura de Arquivos

```
ssl/
├── localhost.pem           # Certificado SSL (não commitado)
└── localhost-key.pem       # Chave privada (não commitada)

Dockerfile                  # Configurado para copiar certificados
apache-config.conf          # VirtualHost 443 + redirect 80→443
dev.sh                      # Mapeia portas 80 e 443
.env                        # URLs HTTPS configuradas
.gitignore                  # Ignora ssl/*.pem
```

### Troubleshooting

#### Erro: "Your connection is not private"

**Causa**: Certificado não é confiável no navegador

**Solução**:
```bash
# Verificar se mkcert-install foi executado
mkcert -install

# Reinstalar CA local se necessário
mkcert -uninstall
mkcert -install

# Reinicie o navegador
```

#### Erro: "SSL: CERTIFICATE_VERIFY_FAILED"

**Causa**: Certificados não foram copiados corretamente no Docker

**Solução**:
```bash
# Verificar se certificados existem
ls -la ssl/

# Rebuild completo
./dev.sh stop
./dev.sh build
./dev.sh start
```

#### Erro: "curl: (60) SSL certificate problem"

**Causa**: curl não confia no certificado mkcert

**Solução**:
```bash
# Usar -k para ignorar verificação (apenas testes)
curl -Ik https://localhost/4sqmet/

# Ou instalar CA do mkcert no sistema
mkcert -install
```

#### Erro: "Unable to negotiate with localhost port 443"

**Causa**: Módulo SSL não habilitado no Apache

**Solução**:
```bash
# Verificar se mod_ssl está habilitado no Dockerfile
grep "a2enmod ssl" Dockerfile

# Rebuild se necessário
./dev.sh build
```

#### Cookies não estão sendo salvos

**Causa**: `COOKIE_SECURE=true` mas acessando via HTTP

**Solução**:
- ✅ Sempre acesse via HTTPS: `https://localhost/4sqmet/`
- ✅ HTTP redireciona automaticamente para HTTPS

---

## 🚀 Produção (4sq.eliotools.site)

### Visão Geral

A produção usa **Let's Encrypt** para certificados SSL gratuitos e auto-renováveis.

### Pré-requisitos

- **Domínio apontando para servidor** (DNS configurado)
- **Apache 2.4+** instalado
- **Certbot** (instalado automaticamente pelo script)
- **Porta 80 e 443 abertas** no firewall

### Instalação Automática via Script

#### 1. Configurar DNS

```
Tipo: A
Nome: 4sq (ou @)
Valor: [IP do servidor]
TTL: 3600
```

**Aguarde propagação** (~5-30 minutos):
```bash
dig 4sq.eliotools.site
```

#### 2. Executar Script de Setup

```bash
# Copiar script para servidor
scp scripts/setup-ssl-production.sh root@4sq.eliotools.site:/tmp/

# Conectar ao servidor
ssh root@4sq.eliotools.site

# Executar script
cd /tmp
sudo bash setup-ssl-production.sh
```

**O script faz**:
1. Instala certbot
2. Habilita mod_ssl
3. Obtém certificado Let's Encrypt
4. Configura renovação automática
5. Configura VirtualHost Apache

#### 3. Validar Instalação

```bash
# Testar HTTPS
curl -I https://4sq.eliotools.site

# Verificar certificado
certbot certificates

# Status de renovação automática
systemctl status certbot.timer
```

### Renovação Automática

- **Validade**: 90 dias
- **Renovação**: A cada 60 dias (automática via `certbot.timer`)
- **Verificar status**: `systemctl status certbot.timer`
- **Forçar renovação**: `certbot renew --force-renewal`
- **Testar renovação**: `certbot renew --dry-run`

### Monitoramento

```bash
# Expiração do certificado
certbot certificates

# Logs de renovação
journalctl -u certbot.timer

# Próxima renovação
systemctl list-timers certbot.timer
```

### Troubleshooting

#### Erro: "Certbot failed to authenticate"

**Causa**: Domínio não aponta para servidor ou porta 80 bloqueada

**Solução**:
```bash
# Verificar DNS
dig 4sq.eliotools.site

# Verificar firewall
ufw status
ufw allow 80/tcp
ufw allow 443/tcp

# Verificar Apache
systemctl status apache2
```

#### Erro: "Too many requests"

**Causa**: Rate limit do Let's Encrypt (5 certificados/semana por domínio)

**Solução**:
- Use `--dry-run` para testar
- Aguarde 1 semana para resetar o limite

#### Renovação falhou

**Causa**: Servidor inacessível ou domínio mudou de IP

**Solução**:
```bash
# Verificar logs
journalctl -u certbot.timer

# Renovar manualmente
certbot renew --force-renewal

# Verificar configuração Apache
apache2ctl configtest
```

---

## 🔐 Segurança: Best Practices Implementadas

### HSTS (HTTP Strict Transport Security)

```apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

**O que faz**: Força navegador a sempre usar HTTPS, mesmo se usuário digitar `http://`

### Cookies Secure

```php
setcookie("oauth_token", $value, [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => true,      // ✅ Apenas HTTPS
    'httponly' => true,    // ✅ Protege contra XSS
    'samesite' => 'Strict' // ✅ Protege contra CSRF
]);
```

### Content Security Policy (CSP)

```apache
Header set Content-Security-Policy "default-src 'self' 'unsafe-inline' 'unsafe-eval' https://ajax.googleapis.com https://maps.googleapis.com https://api.foursquare.com ..."
```

### SSL/TLS Configuration

```apache
SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
SSLCipherSuite HIGH:!aNULL:!MD5
SSLHonorCipherOrder on
```

**Desabilita**: SSLv3, TLSv1, TLSv1.1 (vulneráveis)  
**Habilita**: TLSv1.2 e TLSv1.3 (seguros)

---

## 📊 Comparação: mkcert vs Let's Encrypt

| Aspecto | mkcert (Dev) | Let's Encrypt (Prod) |
|---------|--------------|----------------------|
| **Custo** | Gratuito | Gratuito |
| **Validade** | Indefinida (local) | 90 dias (auto-renova) |
| **Instalação** | 5 comandos | 1 script |
| **Renovação** | Não necessária | Automática |
| **Confiança** | Local apenas | Mundial |
| **Setup Time** | ~10 minutos | ~5 minutos |
| **Requisitos** | mkcert instalado | Domínio apontando |
| **Uso** | Desenvolvimento | Produção |

---

## 📈 Próximos Passos

### Auditoria de Segurança

1. **Lighthouse Audit**:
   ```bash
   # Chrome DevTools > Lighthouse > Security
   ```

2. **SSL Labs Test**: https://www.ssllabs.com/ssltest/
   - Meta: Rating **A+**

3. **Security Headers**: https://securityheaders.com/
   - Verificar todos os headers de segurança

### Monitoramento

1. **Certificados**:
   - Configurar alerta 30 dias antes da expiração
   - Verificar semanalmente: `certbot certificates`

2. **Logs**:
   - Monitorar logs do Apache para erros SSL
   - Verificar logs de renovação do certbot

3. **Performance**:
   - Comparar tempos de resposta HTTP vs HTTPS
   - Validar cache funcionando em HTTPS

### Backup

1. **Certificados Produção**:
   ```bash
   # Backup de certificados Let's Encrypt
   tar -czf letsencrypt-backup.tar.gz /etc/letsencrypt/
   ```

2. **Configurações Apache**:
   ```bash
   # Backup de VirtualHosts
   cp /etc/apache2/sites-available/* /backup/apache/
   ```

---

## 📚 Referências

### mkcert

- Repositório: https://github.com/FiloSottile/mkcert
- Documentação: https://github.com/FiloSottile/mkcert#readme
- Issues comuns: Problemas com CA local, navegadores

### Let's Encrypt

- Website: https://letsencrypt.org/
- Documentação: https://letsencrypt.org/docs/
- Certbot: https://certbot.eff.org/
- Rate Limits: https://letsencrypt.org/docs/rate-limits/

### Apache SSL

- mod_ssl: https://httpd.apache.org/docs/2.4/mod/mod_ssl.html
- SSL/TLS Strong Encryption: https://httpd.apache.org/docs/2.4/ssl/ssl_howto.html

### Segurança

- HSTS Preload: https://hstspreload.org/
- SSL Labs: https://www.ssllabs.com/ssltest/
- Security Headers: https://securityheaders.com/
- Mozilla SSL Configuration: https://ssl-config.mozilla.org/

---

**Última Atualização**: 25 de fevereiro de 2026  
**Status**: ✅ HTTPS implementado e testado  
**Autor**: Elio José Gavlinski Júnior  
**Versão**: 1.0
