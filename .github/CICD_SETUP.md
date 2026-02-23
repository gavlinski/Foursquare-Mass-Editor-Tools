# Configuração do CI/CD - GitHub Actions

## 📋 Visão Geral

Este documento descreve como configurar o pipeline de CI/CD automatizado para o projeto Foursquare Mass Editor Tools usando GitHub Actions.

## 🔐 Secrets Necessários

Configure os seguintes secrets no GitHub:

### Acessar: `Settings` → `Secrets and variables` → `Actions` → `New repository secret`

| Secret Name | Descrição | Exemplo |
|------------|-----------|---------|
| `DEPLOY_SSH_KEY` | Chave SSH privada para acesso ao servidor | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `DEPLOY_USER` | Usuário SSH do servidor de produção | `root` ou `deploy` |

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

### 3. No GitHub:

```
Settings → Secrets and variables → Actions → New repository secret

Nome: DEPLOY_SSH_KEY
Valor: [Cole o conteúdo completo de ~/.ssh/4sqmet_deploy]

Nome: DEPLOY_USER  
Valor: root
```

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
- [ ] Configurar `DEPLOY_SSH_KEY` no GitHub
- [ ] Configurar `DEPLOY_USER` no GitHub
- [ ] Verificar módulos Apache habilitados (`mod_deflate`, `mod_expires`)
- [ ] Testar workflow manualmente
- [ ] Verificar health check após deploy
- [ ] Configurar badge no README.md

---

**Última atualização**: 22 de Fevereiro de 2026  
**Versão do Pipeline**: 1.0.0
