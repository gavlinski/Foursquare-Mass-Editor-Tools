# 🔨 Build e Deploy - Guia Completo

> **📖 Para setup inicial de servidor de produção, veja**: [deployment/DEPLOY.md](deployment/DEPLOY.md)

## 📋 Índice
- [Entendendo as Informações de Build](#entendendo-as-informações-de-build)
- [Badges e Tags Possíveis](#badges-e-tags-possíveis)
- [Cenários de Build](#cenários-de-build)
- [Fluxo de Deploy](#fluxo-de-deploy)

---

## 🎯 Entendendo as Informações de Build

### Por que `"environment": "production"` em Desenvolvimento?

O campo `environment` no `build-info.json` **sempre** é `"production"` porque se refere ao **tipo de build**, não ao ambiente onde está rodando.

```json
{
  "environment": "production"  // ← Build otimizada com minificação
}
```

**Razão:** O script `build.sh` gera uma **build de produção** (minificada, otimizada) independente de onde você vai usar. O ambiente de execução (dev/staging/prod) é definido por:
- Variáveis de ambiente (`.env`)
- Configuração do servidor
- Host/domínio

**Valores possíveis:**
- `"production"` - Build otimizada com minificação ✅
- `"development"` - **Não implementado** (seria código source sem minificação)

### Como funciona `build_source`?

Indica **quem/como disparou** a build (origem):

#### 🟢 `"ci"` (Automático via CI/CD)
```bash
git push origin refactor-ia
# └─> GitHub Actions detecta push
#     └─> Roda workflow deploy.yml
#         └─> Executa build.sh com BUILD_SOURCE=ci
```

**Quando aparece:**
- Push para branches `refactor-ia` ou `main`
- Trigger manual via GitHub UI
- Tags de versão (v3.0.0, etc)

**Badge:** 🟢 **Automático (CI/CD)** (verde)  
**Detalhe:** "GitHub Actions Pipeline"

#### 🟡 `"deploy"` (Manual via Deploy Script)
```bash
./deploy.sh
# └─> Valida repositório
#     └─> Executa build.sh com BUILD_SOURCE=deploy
#         └─> SSH para servidor
#             └─> git pull + restart
```

**Quando aparece:**
- Deploy manual urgente
- Desenvolvedor roda `./deploy.sh` localmente

**Badge:** 🟡 **Manual (Deploy Script)** (amarelo)  
**Detalhe:** "Via script deploy.sh"

#### ⚪ `"local"` (Manual pelo Desenvolvedor)
```bash
./build.sh
# └─> Desenvolvedor na máquina local
#     └─> Build para testar/validar
#         └─> Gera *.min.js + build-info.json
```

**Quando aparece:**
- Desenvolvedor roda `./build.sh` localmente
- Validação antes de commit
- Testes de minificação

**Badge:** ⚪ **Manual (Desenvolvedor)** (cinza)  
**Detalhe:** "Via script build.sh"

---

### ~~Como funciona `built_with`?~~ (REMOVIDO)

**Campo removido** na versão 3.1. Substituído por `build_source` que fornece informação mais relevante sobre a origem da build, não a ferramenta usada.

---

## 🏷️ Badges e Tags Possíveis

### 1️⃣ Versão (Card "VERSÃO")

**Badge de Ambiente:**

| Badge | Cor | Significado | Como Aparece |
|-------|-----|-------------|--------------|
| `PRODUCTION` | 🟢 Verde | Build otimizada | `environment: "production"` |
| `DEVELOPMENT` | 🟡 Amarelo | Build não otimizada | `environment: "development"` (não usado) |

**Código em `version.php`:**
```php
<?php if ($buildInfo['environment'] === 'production'): ?>
    <span class="badge badge-prod">Production</span>
<?php else: ?>
    <span class="badge badge-dev">Development</span>
<?php endif; ?>
```

### 2️⃣ Origem da Build (Card "ORIGEM DA BUILD")

**Badge de Origem:**

| Badge | Cor | Significado | Quando Aparece |
|-------|-----|-------------|----------------|
| `Automático (CI/CD)` | 🟢 Verde | GitHub Actions | Push para refactor-ia/main |
| `Manual (Deploy Script)` | 🟡 Amarelo | Via deploy.sh | Deploy manual pelo desenvolvedor |
| `Manual (Desenvolvedor)` | ⚪ Cinza | Via build.sh | Build local para testes |

**Valores no JSON:**
- `build_source: "ci"` → Automático (CI/CD)
- `build_source: "deploy"` → Manual (Deploy Script)
- `build_source: "local"` → Manual (Desenvolvedor)

**Código em `version.php`:**
```php
<?php 
$buildSource = $buildInfo['build_source'] ?? 'unknown';
switch($buildSource) {
    case 'ci':
        echo '<span class="badge badge-ci">Automático (CI/CD)</span>';
        break;
    case 'deploy':
        echo '<span class="badge badge-deploy">Manual (Deploy Script)</span>';
        break;
    case 'local':
        echo '<span class="badge badge-local">Manual (Desenvolvedor)</span>';
        break;
}
?>
```

### 3️⃣ Commit (Card "COMMIT")

Mostra o hash curto do commit Git (7 caracteres):
```
ddcf206
```

### 4️⃣ Branch (Dentro do card "COMMIT")

Mostra a branch atual do Git:
```
refactor-ia
main
feature/nova-funcionalidade
```

### 5️⃣ Idade da Build

Calcula tempo decorrido desde a build:
```
27 segundos atrás
5 minutos atrás
2 horas atrás
3 dias atrás
```

---

## 🛠️ Cenários de Build

### Proteção Contra Working Tree Sujo em Produção

O `deploy.sh` agora trata preventivamente o caso em que o checkout do servidor contém alterações locais não versionadas.

Durante a etapa remota de deploy, se houver `git status --short` não vazio:

- o status é salvo em `/var/backups/4sqmet/pre_git_sync_<timestamp>/git-status.txt`
- o diff textual é salvo em `/var/backups/4sqmet/pre_git_sync_<timestamp>/git-diff.patch`
- os arquivos não rastreados são listados em `/var/backups/4sqmet/pre_git_sync_<timestamp>/untracked-files.txt`
- as mudanças locais são preservadas em `git stash` com nome `pre-deploy-sync-<timestamp>`

Isso evita que o `git pull` falhe no CI/CD ou no deploy manual por causa de alterações locais residuais no servidor.

### ServerName Global do Apache

O container agora escreve um arquivo `servername.conf` no startup via `docker-entrypoint.sh` e habilita essa configuração automaticamente.

Prioridade usada para definir o valor:

- `APACHE_SERVER_NAME`, se definido
- host do certificado encontrado em `/etc/letsencrypt/live`
- host derivado de `APP_URL`
- fallback `localhost`

Isso elimina o alerta `AH00558: Could not reliably determine the server's fully qualified domain name` sem depender de alteração manual dentro do container.

### 1️⃣ Desenvolvimento - Mudanças Pequenas

**Quando usar:** Ajustes de CSS, pequenas correções em PHP, testes rápidos.

**Comando:**
```bash
./dev.sh run
```

**O que faz:**
```
1. Verifica Docker
2. Baixa Dojo Toolkit (se necessário)
3. Cria/inicia container Docker
4. Monta volume com código fonte
5. Apache carrega arquivos .js ORIGINAIS (não minificados)
```

**Características:**
- ✅ **SEM BUILD** - Usa arquivos `.js` direto (não `.min.js`)
- ✅ Mudanças refletem instantaneamente (F5 no browser)
- ✅ Ideal para debug (código não minificado)
- ❌ **NÃO gera** `build-info.json`
- ❌ **NÃO minifica** JavaScript

**Quando NÃO usar:**
- Mudanças em lógica JavaScript complexa
- Testes de performance
- Validação final antes de commit

---

### 2️⃣ Desenvolvimento - Mudanças Grandes

**Quando usar:** Refatoração de JS, novas features, testes de performance, validação pré-commit.

**Comando:**
```bash
./build.sh
```

**O que faz:**
```
1. Verifica se npm está instalado
   ├─ Se SIM → Build local (rápido)
   └─ Se NÃO → Build via Docker node:22-alpine

2. Instala dependências (terser)
     "build_source": "local",
   - 4sq.js → 4sq.min.js
   - main.js → main.min.js
   - session-manager.js → session-manager.min.js
   - google-maps.js → google-maps.min.js
   - 4sq_csv.js → 4sq_csv.min.js

4. Gera source maps (*.min.js.map)

5. Cria build-info.json:
   {
     "build_date": "2026-03-05T00:29:50Z",
     "commit_hash": "ddcf206...",
     "branch": "refactor-ia",
     "version": "4SQMET-02_03_00-117-gddcf206",
     "built_with": "docker" ou "local",
     "environment": "production"
   }
```

**Características:**
- ✅ **COM BUILD** - Gera arquivos `.min.js`
- ✅ Código otimizado e minificado
- ✅ Redução de ~40-60% no tamanho
- ✅ Gera `build-info.json` com dados reais
- ⚠️ Precisa **reiniciar container** para aplicar:
  ```bash
  docker restart foursquare-mass-editor
  ```

**Exemplo de redução:**
```
Original: 45.2KB → Minificado: 18.7KB (58.6% redução)
```

**Quando usar:**
- Antes de commit importante
- Após mudanças em lógica JavaScript
- Validação de performance
- Testes de build antes de push

---

### 3️⃣ Deploy Manual para Produção

**Quando usar:** Situações específicas onde CI/CD não é viável ou desejável.

#### 🎯 Cenários Legítimos de Uso:

**1. CI/CD Indisponível ou Quebrado**
```bash
# GitHub Actions está fora do ar ou com erro
./deploy.sh  # Fallback para deploy manual
```

**2. Hotfix Crítico (bypass da fila do CI/CD)**
```bash
git commit -m "fix: corrige bug crítico em produção"
git push origin refactor-ia

# CI/CD levaria ~10-15 min
# Deploy manual: ~2-3 min
./deploy.sh  # Deploy urgente
```

**3. Primeiro Deploy (servidor novo)**
```bash
# Configurando servidor pela primeira vez
# CI/CD ainda não configurado
./deploy.sh  # Setup inicial
```

**4. Ambiente Sem CI/CD (staging, dev-server)**
```bash
# Servidor de testes sem integração GitHub
DEPLOY_HOST=staging.eliotools.site ./deploy.sh
```

**5. Token/Secret Expirado no GitHub**
```bash
# DEPLOY_SSH_KEY expirou no GitHub Secrets
# Deploy urgente enquanto atualiza o secret
./deploy.sh  # Usa chave SSH local
```

#### ⚠️ Limitações Atuais:

O `deploy.sh` **sempre faz `git pull`** do repositório, portanto:

- ❌ **NÃO permite** escolher branch diferente (fixo em `refactor-ia`)
- ❌ **NÃO permite** escolher commit específico (rollback)
- ❌ **NÃO permite** enviar arquivos locais sem commit
- ❌ **NÃO permite** deploy de pacote compactado
- ❌ **NÃO permite** atualizar apenas arquivos específicos

**Importante:** Você **DEVE fazer push** antes de rodar `./deploy.sh`, pois ele não envia arquivos locais - apenas faz `git pull` no servidor.

#### 📋 Workflow Correto:

```bash
# 1. Build local (valida que funciona)
./build.sh

# 2. Commit e push (OBRIGATÓRIO)
git add .
git commit -m "feat: nova funcionalidade"
git push origin refactor-ia

# 3. Deploy manual
./deploy.sh  # Faz git pull no servidor
```

**Nota:** Para cenários avançados (rollback, deploy sem commit, branch específica), veja `docs/TODO_DEPLOY_ADVANCED.md`.

**Pré-requisitos:**
```bash
# Configurar SSH (uma vez)
export DEPLOY_USER="root"  # ou seu usuário
export SSH_KEY_PATH="~/.ssh/id_rsa"

# Ou adicionar chave ao ssh-agent (recomendado)
eval $(ssh-agent)
ssh-add ~/.ssh/id_rsa
```

**Comando:**
```bash
./deploy.sh
```

**O que faz:**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Etapa 1/6: Build Local
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. Verifica se branch está limpa (sem commits pendentes)
2. Executa ./build.sh localmente
3. Gera build-info.json ATUALIZADO

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Etapa 2/6: Testes Locais
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. Roda PHPUnit (se configurado)
2. Valida sintaxe PHP
3. Aborta se falhar

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Etapa 3/6: Backup Remoto
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. SSH para 4sq.eliotools.site
2. Cria backup do código atual:
   /var/backups/4sqmet/backup_20260305_003145.tar.gz
3. Mantém apenas 5 backups mais recentes

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Etapa 4/6: Pull from Git
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. cd /var/www/4sqmet
2. git pull origin refactor-ia
3. Atualiza código no servidor

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Etapa 5/6: Composer & Permissions
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. composer install --no-dev
2. Ajusta permissões (755/644)
3. Configura owner (www-data)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Etapa 6/6: Restart Services
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. systemctl restart apache2
2. php-fpm restart (se aplicável)
```

**Características:**
- ✅ Deploy completo em ~2-3 minutos
- ✅ Backup automático antes de deploy
- ✅ Rollback manual possível (via backup)
- ✅ Usa código **do repositório Git** (não arquivos locais)
- ⚠️ Requer SSH configurado
- ⚠️ Requer permissões sudo no servidor

**⚠️ IMPORTANTE:** 
O deploy.sh **NÃO envia arquivos locais**. Ele faz `git pull` no servidor, então você **DEVE** fazer commit e push antes:

```bash
# 1. Build local
./build.sh

# 2. Commit e push
git add .
git commit -m "feat: nova funcionalidade"
git push origin refactor-ia

# 3. Deploy
./deploy.sh
```

---

### 4️⃣ Deploy Automático (GitHub Actions)

**Quando ocorre:** Automaticamente ao fazer `git push` para:
- `refactor-ia` (staging/dev)
- `main` (produção)

**Também pode ser disparado:**
- Manualmente via UI do GitHub (workflow_dispatch)
- Em Pull Requests (apenas build, sem deploy)

**Comando:**
```bash
# Você só faz:
git push origin refactor-ia

# GitHub Actions faz o resto automaticamente
```

**O que o CI/CD faz:**

```yaml
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Job 1: Lint & Validation (2-3 min)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Checkout code
✓ Setup PHP 8.1
✓ Install Composer
✓ PHP Syntax Check (todos os .php)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Job 2: Tests (2-3 min)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Setup PHP 8.1
✓ Run PHPUnit (quando implementado)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Job 3: Build & Minify (3-4 min)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Checkout code (fetch-depth: 0 para tags)
✓ Setup Node.js 18
✓ npm install
✓ ./build.sh (gera *.min.js + build-info.json)
✓ Upload artifacts (*.min.js + build-info.json)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Job 4: Deploy (2-3 min)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Checkout code (fetch-depth: 0)
✓ Download build artifacts
✓ SCP artifacts to server (build-info.json + *.min.js)
✓ Setup SSH (usa secrets.DEPLOY_SSH_KEY)
✓ ./deploy.sh (modo não-interativo)
```

**Total:** ~10-15 minutos do push até site atualizado

**⚠️ CRÍTICO - fetch-depth: 0**

Todos os jobs que precisam de informações Git devem usar `fetch-depth: 0`:

```yaml
- name: 📥 Checkout code
  uses: actions/checkout@v4
  with:
    fetch-depth: 0  # Fetch all history and tags
```

**Por quê?**
- Sem isso, apenas o último commit é baixado (shallow checkout)
- `git describe --tags` não encontra tags → retorna apenas hash curto
- Versão fica "abc123" em vez de "4SQMET-02_03_00-117-gabc123"
- Build-info.json com dados incompletos

**Características:**
- ✅ **Totalmente automático** após push
- ✅ Build roda em ambiente limpo (Ubuntu latest)
- ✅ Artifacts preservados por 7 dias
- ✅ Rollback via re-run de commit anterior
- ✅ Notificação de falha via GitHub
- ❌ **NÃO precisa** rodar build.sh local antes do push
  - O CI/CD roda o build no servidor GitHub
  - Usa a versão do código no commit

**⚠️ IMPORTANTE:**
Você **NÃO precisa** rodar `./build.sh` antes de fazer push. O CI/CD faz isso automaticamente. Porém, é **recomendado** para:
- Testar se o build funciona antes de push
- Validar minificação localmente
- Evitar push quebrado que falha no CI

---

## 📊 Comparação dos Cenários

| Cenário | Comando | Build? | Minifica? | Deploy? | Tempo | Uso |
|---------|---------|--------|-----------|---------|-------|-----|
| **Dev - Pequeno** | `./dev.sh run` | ❌ | ❌ | ❌ | 10s | Mudanças CSS/PHP |
| **Dev - Grande** | `./build.sh` | ✅ | ✅ | ❌ | 1-2min | Validação JS pré-commit |
| **Deploy Manual** | `./deploy.sh` | ✅ | ✅ | ✅ | 2-3min | Deploy urgente |
| **CI/CD Auto** | `git push` | ✅ | ✅ | ✅ | 10-15min | Deploy normal |

---

## 🔄 Fluxo de Trabalho Recomendado

### Workflow Diário

```bash
# 1. Inicia ambiente dev (uma vez por sessão)
./dev.sh run

# 2. Desenvolve features (loop)
# - Edita código
# - Testa no browser (localhost)
# - F5 para ver mudanças

# 3. Antes de commit importante
./build.sh  # Valida que build funciona

# 4. Commit e push
git add .
git commit -m "feat: nova feature"
git push origin refactor-ia

# 5. GitHub Actions faz deploy automaticamente
# Aguarda ~10-15 min
# Monitora em: https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/actions
```

### Workflow de Produção

```bash
# 1. Merge refactor-ia → main
git checkout main
git merge refactor-ia
git push origin main

# 2. CI/CD faz deploy automaticamente para produção
# Monitora progresso no GitHub Actions

# 3. (Alternativa) Deploy manual urgente
./build.sh     # Build local
git push       # Push primeiro!
./deploy.sh    # Deploy direto
```

---

## 🐛 Troubleshooting

### Problema: Versão mostra apenas commit hash curto no CI/CD (ex: "7a693ca" em vez de "4SQMET-02_03_00-117-gddcf206")

**Causa:** Checkout shallow no CI/CD sem fetch de tags

O `git describe --tags --always` precisa do histórico completo para encontrar tags. Se o workflow faz checkout shallow (padrão), apenas o commit atual é baixado.

**Sintoma:**
```json
// ❌ INCORRETO (sem tags)
{
  "version": "7a693ca"
}

// ✅ CORRETO (com tags)
{
  "version": "4SQMET-02_03_00-117-gddcf206"
}
```

**Solução:** Adicionar `fetch-depth: 0` no workflow

```yaml
# .github/workflows/deploy.yml
jobs:
  build:
    steps:
      - name: 📥 Checkout code
        uses: actions/checkout@v4
        with:
          fetch-depth: 0  # ← CRÍTICO: Fetch all history and tags
```

**Verificação:**
```bash
# Deve retornar versão completa com tag
git describe --tags --always
# 4SQMET-02_03_00-117-gddcf206
```

### Problema: `build-info.json` mostra commit antigo

**Causa:** Não rodou build depois dos novos commits

**Solução:**
```bash
./build.sh  # Gera novo build-info.json com commit atual
docker restart foursquare-mass-editor  # Aplica mudanças
```

### Problema: Modal do Sistema não mostra dados de build

**Causa:** Fetch sem header `Accept: application/json`

**Solução:** Já corrigido em `js/session-manager.js`:
```javascript
fetch('version.php', {
    headers: { 'Accept': 'application/json' }  // ← IMPORTANTE
})
```

### Problema: Deploy manual falha com "Permission denied"

**Causa:** SSH não configurado corretamente

**Solução:**
```bash
# Opção 1: ssh-agent (recomendado)
eval $(ssh-agent)
ssh-add ~/.ssh/id_rsa

# Opção 2: variável de ambiente
export SSH_KEY_PATH=~/.ssh/id_rsa

# Testa conexão
ssh root@4sq.eliotools.site "echo 'Conexão OK'"
```

### Problema: CI/CD falha no build

**Causa:** Sintaxe PHP inválida ou dependências quebradas

**Solução:**
```bash
# Testa localmente antes do push
./build.sh  # Deve completar sem erros
composer install  # Verifica dependências
find . -name "*.php" -exec php -l {} \;  # Valida sintaxe
```

---

## 📚 Referências

### Documentação Relacionada
- **[deployment/DEPLOY.md](deployment/DEPLOY.md)**: Guia rápido de deploy e setup de produção
- **[deployment/DEPLOYMENT_STRATEGY.md](deployment/DEPLOYMENT_STRATEGY.md)**: Análise de estratégias de deploy
- **[TODO_DEPLOY_ADVANCED.md](TODO_DEPLOY_ADVANCED.md)**: Deploy avançado (rollback, branch específica)

### Arquivos do Sistema
- **Scripts:** `build.sh`, `build-docker.sh`, `deploy.sh`, `dev.sh`
- **CI/CD:** `.github/workflows/deploy.yml`
- **Version Endpoint:** `version.php`
- **Build Info:** `build-info.json` (gerado, não commitado)
- **Frontend:** `js/session-manager.js` (função `showSystemInfo()`)

---

**Última atualização:** 5 de março de 2026  
**Versão:** 3.0.0
