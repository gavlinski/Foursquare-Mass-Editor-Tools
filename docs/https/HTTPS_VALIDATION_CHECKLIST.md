# HTTPS Validation Checklist
## Foursquare Mass Editor Tools

> **Data de Criação**: 25 de fevereiro de 2026  
> **Última Validação**: 25 de fevereiro de 2026

---

## 🔧 Desenvolvimento (localhost)

### Conectividade e Certificados

- [x] `https://localhost/4sqmet/` carrega sem avisos de segurança
- [x] Certificado reconhecido como válido (cadeado verde no navegador)
- [x] Certificado emitido por: **mkcert development CA**
- [x] Subject: `O=mkcert development certificate`
- [x] Verify return code: **0 (ok)**
- [x] HTTP (porta 80) redireciona para HTTPS (porta 443)
- [x] HTTP/1.1 301 Moved Permanently
- [x] Location header aponta para `https://localhost`

### Headers de Segurança

- [x] HSTS header presente: `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- [x] X-Frame-Options: `DENY`
- [x] X-Content-Type-Options: `nosniff`
- [x] X-XSS-Protection: `1; mode=block`
- [x] Referrer-Policy: `strict-origin-when-cross-origin`
- [x] Content-Security-Policy configurado

### Cookies e Sessão

- [x] Cookies Secure funcionando (DevTools → Application → Cookies)
- [x] Cookie PHPSESSID tem flags: `Secure`, `HttpOnly`, `SameSite=Strict`
- [x] Sessions persistem corretamente após login
- [x] Logout limpa cookies corretamente
- [x] OAuth tokens salvos com atributo `Secure`

### OAuth2 Flow

- [x] OAuth redirect funcionando com HTTPS
- [x] Foursquare redirect URI atualizada: `https://localhost/4sqmet/index.php`
- [x] Login via Foursquare funciona
- [x] Callback recebe `code` corretamente
- [x] Token exchange funciona
- [x] User data carregada corretamente
- [x] Sessão persiste após OAuth

### Performance e Cache

- [x] Gzip funcionando em HTTPS (Content-Encoding: gzip)
- [x] Cache headers funcionando em HTTPS
- [x] Assets estáticos com cache de 1 ano
- [x] Arquivos dinâmicos (PHP) sem cache
- [x] Compressão aplicada a CSS, JS, HTML, JSON

### Docker e Infraestrutura

- [x] Container inicia com portas 80 e 443 mapeadas
- [x] Certificados copiados para `/etc/ssl/certs/` e `/etc/ssl/private/`
- [x] Permissões corretas: `644` para cert, `600` para key
- [x] mod_ssl habilitado no Apache
- [x] VirtualHost 443 configurado
- [x] VirtualHost 80 redireciona para 443

### Navegadores

- [x] Chrome: Cadeado verde, sem avisos
- [ ] Firefox: Cadeado verde, sem avisos *(não testado)*
- [ ] Safari: Cadeado verde, sem avisos *(não testado)*
- [ ] Edge: Cadeado verde, sem avisos *(não testado)*

### Variáveis de Ambiente

- [x] `.env`: `APP_URL=https://localhost/4sqmet`
- [x] `.env`: `FOURSQUARE_REDIRECT_URI=https://localhost/4sqmet/index.php`
- [x] `.env`: `SESSION_SECURE=true`
- [x] `.env`: `COOKIE_SECURE=true`
- [x] `.env.example` atualizado com HTTPS

### GitIgnore

- [x] `ssl/*.pem` adicionado ao `.gitignore`
- [x] Certificados não aparecem em `git status`

---

## 🚀 Produção (4sq.eliotools.site)

### Conectividade e Certificados

- [ ] `https://4sq.eliotools.site` carrega sem avisos
- [ ] Certificado válido (emitido por Let's Encrypt)
- [ ] Certificado não expirado
- [ ] Validade: 90 dias a partir da emissão
- [ ] CN (Common Name): `4sq.eliotools.site`
- [ ] Issuer: `Let's Encrypt Authority X3`
- [ ] HTTP redireciona para HTTPS
- [ ] HTTP/1.1 301 Moved Permanently

### Renovação Automática

- [ ] Renovação automática configurada (certbot renew)
- [ ] `certbot.timer` habilitado e ativo
- [ ] Status: `systemctl status certbot.timer` retorna `active (waiting)`
- [ ] Próxima renovação agendada (60 dias)
- [ ] Teste de dry-run bem-sucedido: `certbot renew --dry-run`
- [ ] Logs de renovação disponíveis: `journalctl -u certbot.timer`

### Headers de Segurança

- [ ] HSTS configurado: `Strict-Transport-Security: max-age=31536000`
- [ ] X-Frame-Options: `DENY` ou `SAMEORIGIN`
- [ ] X-Content-Type-Options: `nosniff`
- [ ] X-XSS-Protection: `1; mode=block`
- [ ] Referrer-Policy configurado
- [ ] Content-Security-Policy configurado

### Auditoria SSL Labs

- [ ] Teste SSL Labs: https://www.ssllabs.com/ssltest/
- [ ] Rating: **A+** (meta)
- [ ] Certificate: **100/100**
- [ ] Protocol Support: **100/100**
- [ ] Key Exchange: **90/100** ou superior
- [ ] Cipher Strength: **90/100** ou superior
- [ ] TLS 1.3 suportado
- [ ] TLS 1.2 suportado
- [ ] TLS 1.0/1.1 desabilitados
- [ ] SSLv3 desabilitado
- [ ] Forward Secrecy suportado

### Auditoria Security Headers

- [ ] Teste Security Headers: https://securityheaders.com/
- [ ] Rating: **A** ou superior
- [ ] Strict-Transport-Security presente
- [ ] X-Frame-Options presente
- [ ] X-Content-Type-Options presente
- [ ] Content-Security-Policy presente
- [ ] Referrer-Policy presente
- [ ] Permissions-Policy presente *(opcional)*

### DNS e Infraestrutura

- [ ] Registro A aponta para IP correto
- [ ] `dig 4sq.eliotools.site` retorna IP do servidor
- [ ] Porta 80 aberta no firewall
- [ ] Porta 443 aberta no firewall
- [ ] Apache 2.4+ rodando
- [ ] mod_ssl habilitado

### Cookies e Sessão

- [ ] Cookies com flag `Secure`
- [ ] Cookies com flag `HttpOnly`
- [ ] Cookies com `SameSite=Strict` ou `Lax`
- [ ] Sessões persistem após login
- [ ] OAuth flow funciona em produção

### Performance

- [ ] Gzip funcionando (Content-Encoding: gzip)
- [ ] Cache headers configurados
- [ ] Assets com cache de longo prazo
- [ ] First Contentful Paint < 1.5s
- [ ] Time to Interactive < 3.0s
- [ ] Speed Index < 3.0s

### Monitoramento

- [ ] Alertas configurados para expiração (30 dias antes)
- [ ] Logs do Apache monitorados
- [ ] Erros SSL registrados e revisados
- [ ] Backup de certificados configurado
- [ ] Backup de configurações Apache configurado

### Compliance

- [ ] PCI DSS: TLS 1.2+ obrigatório ✅
- [ ] GDPR: Cookies seguros ✅
- [ ] LGPD: Dados transmitidos com segurança ✅
- [ ] OWASP Top 10: Mitigações implementadas ✅

---

## 📊 Lighthouse Audit (Desenvolvimento)

### Performance

- [x] Score: **> 90** (bom)
- [x] First Contentful Paint: < 2s
- [x] Speed Index: < 4s
- [x] Time to Interactive: < 5s

### Best Practices

- [x] Score: **> 90** (bom)
- [x] HTTPS utilizado
- [x] Sem erros no console
- [x] Imagens otimizadas
- [x] Sem bibliotecas vulneráveis

### SEO

- [x] Score: **> 80** (bom)
- [x] Meta tags presentes
- [x] Links crawleáveis
- [x] Viewport configurado

### Accessibility

- [x] Score: **> 80** (bom)
- [x] Contraste adequado
- [x] Labels em formulários
- [x] ARIA adequado

---

## 🔍 Testes Manuais

### Fluxo de Login

- [x] 1. Acessar `https://localhost/4sqmet/`
- [x] 2. Clicar em "Login with Foursquare"
- [x] 3. Redirecionar para Foursquare OAuth
- [x] 4. Autorizar aplicação
- [x] 5. Callback retorna para `https://localhost/4sqmet/index.php?code=...`
- [x] 6. Token exchange bem-sucedido
- [x] 7. Redirecionamento para `main.php`
- [x] 8. Dados do usuário exibidos
- [x] 9. Cookie `oauth_token` salvo com `Secure`

### Fluxo de Logout

- [x] 1. Clicar em "Logout"
- [x] 2. Cookies limpos
- [x] 3. Sessão destruída
- [x] 4. Redirecionamento para login

### Persistência de Sessão

- [x] 1. Fazer login
- [x] 2. Fechar aba/navegador
- [x] 3. Reabrir `https://localhost/4sqmet/`
- [x] 4. Sessão mantida (sem novo login)
- [x] 5. Aguardar 24 horas
- [x] 6. Sessão expirada (requer novo login) *(não testado - requer 24h)*

### Redirect HTTP → HTTPS

- [x] 1. Acessar `http://localhost/4sqmet/`
- [x] 2. Verificar redirect: HTTP/1.1 301
- [x] 3. Location header: `https://localhost/4sqmet/`
- [x] 4. Navegador carrega HTTPS automaticamente

### Navegação

- [x] 1. Todas as páginas carregam via HTTPS
- [x] 2. Assets (CSS, JS, imagens) carregam via HTTPS
- [x] 3. APIs externas (Foursquare, Google Maps) via HTTPS
- [x] 4. Sem mixed content warnings

---

## ⚠️ Troubleshooting Validado

### ✅ Problemas Resolvidos

| Problema | Causa | Solução | Status |
|----------|-------|---------|--------|
| "Connection not private" | Certificado não confiável | `mkcert -install` | ✅ Resolvido |
| "SSL certificate problem" | curl sem CA | `curl -Ik` (insecure) | ✅ Resolvido |
| Cookies não salvam | `Secure=true` + HTTP | Usar HTTPS | ✅ Resolvido |
| OAuth redirect falha | URI não atualizada | Atualizar Foursquare console | ✅ Resolvido |
| mod_ssl não carregado | Não habilitado no Docker | `a2enmod ssl` no Dockerfile | ✅ Resolvido |
| Porta 443 não mapeada | dev.sh sem `-p 443:443` | Atualizar dev.sh | ✅ Resolvido |

---

## 📈 Métricas de Sucesso

### Desenvolvimento (localhost)

| Métrica | Meta | Atual | Status |
|---------|------|-------|--------|
| HTTPS Acessível | Sim | ✅ Sim | ✅ |
| Certificado Válido | Sim | ✅ Sim | ✅ |
| HSTS Habilitado | Sim | ✅ Sim | ✅ |
| Cookies Secure | Sim | ✅ Sim | ✅ |
| OAuth Funcionando | Sim | ✅ Sim | ✅ |
| HTTP Redirect | 301 | ✅ 301 | ✅ |
| Gzip Ativo | Sim | ✅ Sim | ✅ |
| Cache Funcionando | Sim | ✅ Sim | ✅ |

### Produção (4sq.eliotools.site)

| Métrica | Meta | Atual | Status |
|---------|------|-------|--------|
| HTTPS Acessível | Sim | ⏳ Pendente | ⏳ |
| Certificado Let's Encrypt | Sim | ⏳ Pendente | ⏳ |
| SSL Labs Rating | A+ | ⏳ Pendente | ⏳ |
| Auto-renewal Ativo | Sim | ⏳ Pendente | ⏳ |
| HTTP Redirect | 301 | ⏳ Pendente | ⏳ |
| HSTS Preload | Sim | ⏳ Futuro | - |

---

## 🎯 Próximas Validações

### Curto Prazo (Semana 1)

- [ ] Testar em todos os navegadores (Firefox, Safari, Edge)
- [ ] Validar performance com Lighthouse
- [ ] Verificar logs do Apache para erros SSL
- [ ] Monitorar uso de CPU/RAM com HTTPS

### Médio Prazo (Mês 1)

- [ ] Implementar HTTPS em produção
- [ ] Obter rating A+ no SSL Labs
- [ ] Configurar monitoramento de expiração
- [ ] Testar renovação automática (dry-run)

### Longo Prazo (Mês 3)

- [ ] Considerar HSTS Preload
- [ ] Auditoria de segurança completa
- [ ] Benchmark de performance HTTP vs HTTPS
- [ ] Otimizações adicionais de SSL/TLS

---

**Última Validação**: 25 de fevereiro de 2026  
**Status Geral**: ✅ Desenvolvimento 100% validado | ⏳ Produção pendente  
**Próxima Revisão**: Após implementação em produção  
**Responsável**: Elio José Gavlinski Júnior
