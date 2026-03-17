# Configuração do CI/CD - GitHub Actions

## 📋 Visão Geral

Este documento descreve como configurar o pipeline de CI/CD automatizado para o projeto Foursquare Mass Editor Tools usando GitHub Actions.

## 🔐 Secrets Necessários

Configure os seguintes secrets no GitHub:

### Acessar: `Settings` → `Secrets and variables` → `Actions` → `New repository secret`

| Secret Name | Descrição | Exemplo/Valor |
|------------|-----------|---------------|
| **Deploy SSH** | | |
| `DEPLOY_SSH_KEY` | Chave SSH privada para acesso ao servidor | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `DEPLOY_USER` | Usuário SSH do servidor de produção | `root` |
| **Foursquare API** | | |
| `FOURSQUARE_CLIENT_KEY` | Client ID da aplicação Foursquare | `AKR40GT...` (50 chars) |
| `FOURSQUARE_CLIENT_SECRET` | Client Secret da aplicação | `WGUZCMW...` (48 chars) |
| `FOURSQUARE_REDIRECT_URI` | URL de callback OAuth | `https://4sq.eliotools.site/4sqmet/index.php` |
| **Google Maps API** | | |
| `GOOGLE_MAPS_API_KEY` | API Key do Google Maps | `AIzaSy...` (39 chars) |
| `GOOGLE_MAPS_MAP_ID` | Map ID para customização | `522d4feb...` (24 chars hex) |
| **Application Config** | | |
| `APP_URL` | URL principal da aplicação | `https://4sq.eliotools.site` |

**Total:** 9 secrets

## 🔧 Repository Variables

Configure as seguintes variáveis (não-sensíveis) no GitHub:

### Acessar: `Settings` → `Secrets and variables` → `Actions` → `Variables` → `New repository variable`

| Variable Name | Descrição | Exemplo/Valor |
|---------------|-----------|---------------|
| `DEPLOY_HOST` | IP ou domínio do servidor de produção | `134.209.163.143` ou `4sq.eliotools.site` |

**Vantagens de usar Repository Variables:**
- ✅ **Não são sensíveis**: IP/domínio não precisa ser secreto
- ✅ **Mais eficiente**: Menos itens na seção Secrets
- ✅ **Clara separação de concerns**: Secrets = credenciais, Variables = config
- ✅ **Auditoria**: Histórico de mudanças visível

**Total:** 1 variable

### Por que usar secrets para credenciais?

✅ **Segurança**: Criptografados no GitHub (AES-256), nunca expostos em logs  
✅ **Rotação fácil**: Atualiza no GitHub → próximo deploy aplica automaticamente  
✅ **Auditoria**: GitHub registra quando/quem modificou  
✅ **Zero manual**: Deploy cria/atualiza `.env` automaticamente no servidor  
✅ **Sem commits sensíveis**: Credenciais nunca entram no repositório

## 🔑 Gerando e Configurando SSH Key

### 1. No seu computador local:

```bash
# Gerar chave SSH específica para deploy (sem senha)
ssh-keygen -t ed25519 -C "deploy@4sq.eliotools.site" -f ~/.ssh/4sqmet_deploy

# Exibir a chave privada (copiar todo o conteúdo)
cat ~/.ssh/4sqmet_deploy

# Exibir a chave pública (copiar todo o conteúdo)
cat ~/.ssh/4sqmet_deploy.pub
```

### 2. No servidor de produção (4sq.eliotools.site):

```bash
# Logar no servidor
ssh root@4sq.eliotools.site

# Adicionar a chave pública ao authorized_keys
echo "SUA_CHAVE_PUBLICA_AQUI" >> ~/.ssh/authorized_keys

# Verificar permissões corretas
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
```

### 3. No GitHub - Configurar Todos os Secrets:

```
Settings → Secrets and variables → Actions → New repository secret
```

**A. Deploy SSH (2 secrets):**
```
Nome: DEPLOY_SSH_KEY
Valor: [Cole o conteúdo completo de ~/.ssh/4sqmet_deploy]

Nome: DEPLOY_USER  
Valor: root
```

**A.1 Deploy Host (1 Repository Variable):**
```
Nome: DEPLOY_HOST
Valor: 134.209.163.143 (ou seu IP/domínio)
Tipo: Regular string (NÃO é secret)
```

Configuração em: `Settings → Secrets and variables → Actions → Variables`

**B. Foursquare API (3 secrets):**
```
Nome: FOURSQUARE_CLIENT_KEY
Valor: [Seu Client ID - 50 caracteres]

Nome: FOURSQUARE_CLIENT_SECRET
Valor: [Seu Client Secret - 48 caracteres]

Nome: FOURSQUARE_REDIRECT_URI
Valor: https://4sq.eliotools.site/4sqmet/index.php
```

> 📝 Obtenha suas credenciais em: https://pt.foursquare.com/developers/home

**C. Google Maps API (2 secrets):**
```
Nome: GOOGLE_MAPS_API_KEY
Valor: [Sua API Key - começa com AIzaSy]

Nome: GOOGLE_MAPS_MAP_ID
Valor: [Seu Map ID - 24 caracteres hexadecimais]
```

> 📝 Obtenha suas credenciais em: https://console.cloud.google.com/google/maps-apis/credentials

**D. Application Config (1 secret):**
```
Nome: APP_URL
Valor: https://4sq.eliotools.site
```

### ✅ Validação dos Secrets

Após configurar, verifique no GitHub:
```
Settings → Secrets and variables → Actions
```

**Secrets** - Deve listar **9 secrets**:
- ✅ DEPLOY_SSH_KEY (536 bytes~)
- ✅ DEPLOY_USER (4 bytes)
- ✅ FOURSQUARE_CLIENT_KEY (50 bytes)
- ✅ FOURSQUARE_CLIENT_SECRET (48 bytes)
- ✅ FOURSQUARE_REDIRECT_URI (50 bytes~)
- ✅ GOOGLE_MAPS_API_KEY (39 bytes)
- ✅ GOOGLE_MAPS_MAP_ID (24 bytes)
- ✅ APP_URL (30 bytes~)

**Variables** - Deve listar **1 variable**:
- ✅ DEPLOY_HOST (IP ou domínio do servidor)

## 🚀 Workflows Disponíveis

### 1. **CI/CD Pipeline Automático** (`deploy.yml`)

Executa automaticamente em:
- ✅ Push para `refactor-ia` ou `main`
- ✅ Pull requests
- ✅ Tags de versão (`v*`)
- ✅ Trigger manual

**Jobs executados:**
1. 🔍 **Lint** - Validação de sintaxe PHP
2. 🧪 **Test** - Testes automatizados (PHPUnit quando implementado)
3. 🔨 **Build** - Minificação de JavaScript com Terser
4. 🚀 **Deploy** - Deploy automático para produção
5. 📦 **Release** - Cria release no GitHub (apenas em tags)
6. ⏪ **Rollback** - Rollback manual via UI

### 2. **Trigger Manual de Deploy**

```
Actions → CI/CD Pipeline → Run workflow → Selecionar branch
```

## 📊 Status do Pipeline

Adicione badge ao README.md:

```markdown
![CI/CD Status](https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/actions/workflows/deploy.yml/badge.svg)
```

## 🔄 Processo de Deploy

### Deploy Automático (Push):

```bash
# 1. Fazer mudanças e commit
git add .
git commit -m "feat: Nova funcionalidade"

# 2. Push para branch
git push origin refactor-ia

# 3. GitHub Actions executa automaticamente:
#    - Valida código
#    - Roda testes
#    - Faz build
#    - Deploy para produção
#    - Health check
```

### Deploy Manual:

```
1. Ir em: Actions → CI/CD Pipeline
2. Clicar em: Run workflow
3. Selecionar branch: refactor-ia
4. Clicar em: Run workflow
```

### Criar Release:

```bash
# 1. Criar tag de versão
git tag -a v3.1.0 -m "Release v3.1.0"

# 2. Push da tag
git push origin v3.1.0

# 3. GitHub Actions cria release automaticamente
```

## 🏗️ Arquitetura do Build

```
┌─────────────────────────────────────────────────────────┐
│                    GitHub Actions                        │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  1. Lint & Validation                                    │
│     └─ Valida sintaxe PHP                               │
│                                                          │
│  2. Tests                                                │
│     └─ Executa PHPUnit (quando implementado)            │
│                                                          │
│  3. Build                                                │
│     ├─ npm install                                       │
│     ├─ bash build.sh                                     │
│     └─ Minifica: 4sq.js, main.js, session-manager.js   │
│                                                          │
│  4. Deploy                                               │
│     ├─ SSH para servidor                                │
│     ├─ Cria backup                                       │
│     ├─ Git pull                                          │
│     ├─ composer install                                  │
│     ├─ bash build.sh (no servidor)                      │
│     └─ Restart Apache                                    │
│                                                          │
│  5. Health Check                                         │
│     └─ curl http://4sq.eliotools.site                   │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

## 📝 Build Local vs Remoto

### Build Local (GitHub Actions):
- Minifica JavaScript antes do deploy
- Upload de artifacts
- Mais rápido (usa cache do GitHub)

### Build Remoto (no servidor):
- Fallback caso Node.js não esteja no workflow
- Garante versões minificadas mesmo sem Actions
- Backup adicional

## 🔄 Rollback

### Via GitHub Actions UI:

```
1. Actions → CI/CD Pipeline
2. Run workflow
3. Job: Rollback Deployment
4. Run workflow
```

### Manual via SSH:

```bash
ssh root@4sq.eliotools.site

cd /var/backups/4sqmet
ls -lht backup_*.tar.gz | head -5  # Ver backups disponíveis

# Restaurar último backup
LATEST=$(ls -t backup_*.tar.gz | head -n1)
cd /var/www/html
tar -xzf /var/backups/4sqmet/${LATEST}
systemctl restart apache2
```

## 📊 Monitoramento

### Logs do Apache:

```bash
# No servidor
tail -f /var/log/apache2/error.log
tail -f /var/log/apache2/access.log
```

### Status do Apache:

```bash
systemctl status apache2
```

### Verificar deploy:

```bash
curl -I http://4sq.eliotools.site
```

## 🐛 Troubleshooting

### Deploy falha com erro SSH:

```bash
# Verificar chave SSH no servidor
cat ~/.ssh/authorized_keys

# Testar conexão
ssh -i ~/.ssh/4sqmet_deploy root@4sq.eliotools.site
```

### Build falha:

```bash
# Rodar build localmente para debug
npm install
bash build.sh
```

### Apache não reinicia:

```bash
# Verificar configuração
apachectl configtest

# Verificar logs
journalctl -u apache2 -f
```

## 📚 Referências

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Deploy com SSH](https://github.com/marketplace/actions/ssh-agent)
- [Terser Documentation](https://terser.org/)

## ✅ Checklist de Setup

- [ ] Gerar par de chaves SSH
- [ ] Adicionar chave pública ao servidor
- [ ] Configurar `DEPLOY_SSH_KEY` no GitHub (Secrets)
- [ ] Configurar `DEPLOY_USER` no GitHub (Secrets)
- [ ] Configurar `DEPLOY_HOST` no GitHub (Repository Variables - NÃO Secrets)
- [ ] Verificar módulos Apache habilitados (`mod_deflate`, `mod_expires`)
- [ ] Testar workflow manualmente
- [ ] Verificar health check após deploy
- [ ] Configurar badge no README.md
- [ ] Remover secret `DEPLOY_HOST` antigo se existir

---

**Última atualização**: 17 de Março de 2026  
**Versão do Pipeline**: 1.1.0  
**Mudanças**: Centralização de config em vars (DEPLOY_HOST) + secrets (APP_URL)
