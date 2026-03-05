# Deploy Workflows Skill

## Name
Deployment and CI/CD Workflows

## Description
Gerencia os 3 métodos de deploy do projeto: desenvolvimento local (dev.sh), deploy manual (deploy.sh), e CI/CD automático (GitHub Actions).

## When to use
Carregue esta skill quando:
- Modificar scripts de deploy
- Trabalhar com CI/CD pipeline
- Configurar ambientes de deploy
- Diagnosticar problemas de deployment
- Implementar novos modos de deploy

## Key Files
- `dev.sh` - Desenvolvimento local (sem build)
- `deploy.sh` - Deploy manual para produção
- `.github/workflows/deploy.yml` - Pipeline CI/CD
- `docs/BUILD_AND_DEPLOY.md` - Documentação completa
- `docs/TODO_DEPLOY_ADVANCED.md` - Modos avançados (futuro)

## Deployment Methods

### 1. Development (dev.sh)
**Quando usar**: Desenvolvimento diário, mudanças pequenas

```bash
./dev.sh run
```

**O que faz**:
- ✅ Inicia container Docker
- ✅ Monta volume com código fonte
- ✅ Usa arquivos `.js` originais (não minificados)
- ❌ **NÃO gera** build-info.json
- ❌ **NÃO minifica** JavaScript

**Características**:
- F5 no browser já mostra mudanças
- Ideal para debug (código não minificado)
- ~10 segundos para iniciar

### 2. Manual Deploy (deploy.sh)
**Quando usar**: Apenas em 5 cenários específicos

```bash
./deploy.sh
```

**Cenários legítimos**:
1. **CI/CD indisponível** - GitHub Actions fora do ar
2. **Hotfix urgente** - Não quer esperar 10-15min do CI/CD (deploy em 2-3min)
3. **Primeiro deploy** - Servidor novo, CI/CD não configurado
4. **Ambiente sem CI/CD** - Staging server sem integração GitHub
5. **Secret expirado** - DEPLOY_SSH_KEY expirou no GitHub

**Fluxo completo**:
```
./deploy.sh
├─> Etapa 1: Build Local
│   └─> export BUILD_SOURCE="deploy"
│   └─> bash build.sh
├─> Etapa 2: Testes Locais
│   └─> PHPUnit (se configurado)
├─> Etapa 3: Backup Remoto
│   └─> SSH + tar backup no servidor
├─> Etapa 4: Git Pull
│   └─> cd /var/www/4sqmet
│   └─> git pull origin refactor-ia
├─> Etapa 5: Composer & Permissions
│   └─> composer install --no-dev
│   └─> chown www-data:www-data
└─> Etapa 6: Restart Services
    └─> systemctl restart apache2
```

**⚠️ IMPORTANTE**: 
Deploy **NÃO envia arquivos locais**, faz `git pull` no servidor. **Requer push antes**:
```bash
git commit -m "fix: bug crítico"
git push origin refactor-ia  # ← OBRIGATÓRIO
./deploy.sh
```

### 3. CI/CD Automático (GitHub Actions)
**Quando usar**: Workflow normal (99% dos casos)

```bash
git push origin refactor-ia
# Aguarda ~10-15 min (deploy automático)
```

**Pipeline**:
```
GitHub Actions (deploy.yml)
├─> Job 1: Lint & Validation (2-3 min)
│   ├─> PHP syntax check
│   └─> Composer validation
├─> Job 2: Tests (2-3 min)
│   └─> PHPUnit (quando implementado)
├─> Job 3: Build & Minify (3-4 min)
│   ├─> BUILD_SOURCE=ci bash build.sh
│   └─> Upload artifacts (7 dias retenção)
└─> Job 4: Deploy (2-3 min)
    ├─> Download artifacts
    ├─> SSH com DEPLOY_SSH_KEY secret
    └─> Executa deploy.sh (modo não-interativo)
```

**Triggers**:
- Push para `refactor-ia` ou `main`
- Tags de versão (`v*`)
- Workflow dispatch (manual via GitHub UI)

## Deploy Configuration

### Environment Variables
```bash
# deploy.sh
export DEPLOY_HOST="${DEPLOY_HOST:-4sq.eliotools.site}"
export DEPLOY_USER="${DEPLOY_USER:-root}"
export DEPLOY_BRANCH="${DEPLOY_BRANCH:-refactor-ia}"

# CI/CD
export CI="true"
export GITHUB_ACTIONS="true"
```

### SSH Setup
```bash
# Opção 1: ssh-agent (recomendado)
eval $(ssh-agent)
ssh-add ~/.ssh/id_rsa

# Opção 2: Variável de ambiente
export SSH_KEY_PATH=~/.ssh/id_rsa

# Teste conexão
ssh root@4sq.eliotools.site "echo 'OK'"
```

### GitHub Secrets Required
- `DEPLOY_SSH_KEY` - Chave privada SSH para acesso ao servidor
- `DEPLOY_HOST` (opcional) - Override do hostname
- `DEPLOY_USER` (opcional) - Override do usuário SSH

## Workflow Comparison

| Aspecto | dev.sh | deploy.sh | CI/CD |
|---------|--------|-----------|-------|
| **Tempo** | 10s | 2-3min | 10-15min |
| **Build?** | ❌ | ✅ | ✅ |
| **Minifica?** | ❌ | ✅ | ✅ |
| **Deploy?** | ❌ | ✅ | ✅ |
| **Testes?** | ❌ | ⚠️ Opcional | ✅ |
| **Requer push?** | ❌ | ✅ | ✅ |
| **Manual?** | ✅ | ✅ | ❌ |
| **build_source** | - | `"deploy"` | `"ci"` |

## Common Tasks

### Deploy manual urgente
```bash
# 1. Commit e push
git add .
git commit -m "fix: bug crítico"
git push origin refactor-ia

# 2. Deploy imediato (bypass CI/CD)
./deploy.sh
```

### Deploy para ambiente diferente
```bash
DEPLOY_HOST=staging.eliotools.site \
DEPLOY_USER=deploy \
DEPLOY_BRANCH=develop \
./deploy.sh
```

### Verificar status do deploy CI/CD
```bash
# Via CLI (gh cli)
gh workflow view deploy

# Ou acesse:
# https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/actions
```

### Rollback (usando backup)
```bash
# SSH para servidor
ssh root@4sq.eliotools.site

# Lista backups
ls -lh /var/backups/4sqmet/

# Restaura backup
cd /var/www/4sqmet
tar -xzf /var/backups/4sqmet/backup_20260305_013000.tar.gz
systemctl restart apache2
```

## Troubleshooting

### Deploy falha: "Permission denied (publickey)"
```bash
# Verifica chave SSH
ssh-add -l

# Adiciona chave
ssh-add ~/.ssh/id_rsa

# Testa conexão
ssh -v root@4sq.eliotools.site
```

### Deploy falha: "mudanças não commitadas"
```bash
# git diff-index detecta mudanças locais
git status
git add .
git commit -m "message"
git push
./deploy.sh
```

### CI/CD falha no build
```bash
# Testa localmente primeiro
./build.sh
composer install
find . -name "*.php" -exec php -l {} \;

# Se passar, commit e push
git push
```

### Deploy aplica versão antiga
```bash
# Causa: git pull não trouxe último commit
# Solução: Verifica no servidor
ssh root@4sq.eliotools.site "cd /var/www/4sqmet && git log -1"

# Se necessário, força pull
ssh root@4sq.eliotools.site "cd /var/www/4sqmet && git fetch origin && git reset --hard origin/refactor-ia"
```

## Critical Patterns

### NEVER deploy without push
```bash
# ❌ Errado
./build.sh
./deploy.sh  # Vai aplicar versão do repositório, não local

# ✅ Correto
./build.sh  # Valida build
git add .
git commit -m "message"
git push origin refactor-ia
./deploy.sh
```

### NEVER skip backup
```bash
# deploy.sh SEMPRE faz backup antes
tar -czf ${BACKUP_DIR}/backup_${TIMESTAMP}.tar.gz \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='.git' \
    .
```

### ALWAYS verify branch before deploy
```bash
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
    echo "❌ Branch errada!"
    exit 1
fi
```

## Limitations & Future Improvements

### Limitações Atuais (deploy.sh)
- ❌ Não permite escolher branch diferente
- ❌ Não permite escolher commit específico
- ❌ Não permite enviar arquivos locais sem commit
- ❌ Não permite rollback automático
- ❌ Não permite deploy de pacote compactado

### Melhorias Planejadas
Documentadas em `docs/TODO_DEPLOY_ADVANCED.md`:
- ✅ Modo `--branch <name>` para deploy de feature
- ✅ Modo `--commit <hash>` para rollback
- ✅ Modo `--package` para envio de arquivos locais
- ✅ Modo `--files <list>` para hotfix específico
- ✅ Modo `--rollback` para restaurar backup

## Integration Points

- **build.sh**: deploy.sh define BUILD_SOURCE="deploy" antes de chamar
- **GitHub Actions**: Orquestra pipeline completo
- **version.php**: Exibe build_source="deploy" ou "ci"
- **Backups**: Mantém 5 backups no servidor (/var/backups/4sqmet/)

## Documentation
- **Main guide**: `docs/BUILD_AND_DEPLOY.md`
- **Deploy scenarios**: `docs/BUILD_AND_DEPLOY.md` seção "Cenários de Build"
- **Advanced modes**: `docs/TODO_DEPLOY_ADVANCED.md`
- **CI/CD config**: `.github/workflows/deploy.yml`
