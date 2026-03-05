# 🚀 Deploy Avançado - Modos Flexíveis (TODO)

## 📋 Visão Geral

Implementar suporte para deploy flexível no `deploy.sh` com 5 modos de operação:

1. **Git Pull** (atual) - Padrão
2. **Branch Específica** - Deploy de feature branches
3. **Commit Específico** - Rollback ou teste de versão
4. **Pacote Local** - Upload de arquivos sem commit
5. **Arquivos Específicos** - Hotfix de arquivos individuais

---

## 🎯 Motivação

### Limitações Atuais:
- ❌ Deploy sempre fixo na branch `refactor-ia`
- ❌ Não permite rollback rápido para commit anterior
- ❌ Não permite testar mudanças antes de commit
- ❌ Não permite deploy de feature branches
- ❌ Não permite hotfix de arquivo único

### Casos de Uso:
- **Rollback Urgente**: Versão em produção com bug crítico
- **Teste Pré-Commit**: Validar mudanças antes de commit
- **Feature Deploy**: Testar branch em staging antes de merge
- **Hotfix Rápido**: Atualizar apenas 1 arquivo crítico

---

## 🛠️ Especificação dos Modos

### Modo 1: Git Pull (Padrão - já implementado)

**Uso:**
```bash
./deploy.sh
# ou
./deploy.sh --pull
```

**Comportamento:**
1. Valida repositório local (sem mudanças pendentes)
2. Executa build local (`./build.sh`)
3. Faz backup no servidor (`/var/backups/4sqmet/`)
4. Executa `git pull origin refactor-ia` no servidor
5. Roda `composer install --no-dev`
6. Ajusta permissões e reinicia Apache

**Quando usar:**
- CI/CD indisponível
- Deploy urgente (bypass fila)
- Primeiro deploy de servidor novo

---

### Modo 2: Branch Específica

**Uso:**
```bash
./deploy.sh --branch feature/nova-funcionalidade
./deploy.sh --branch main
```

**Comportamento:**
1. Valida que branch existe localmente
2. Executa build local da branch
3. No servidor: `git checkout <branch> && git pull origin <branch>`
4. Continua fluxo normal (composer, permissions, restart)

**Validações:**
- ⚠️ Avisa se não é `refactor-ia` ou `main`
- ⚠️ Confirma deploy de feature branch em produção
- ✅ Permite deploy em staging sem avisos

**Casos de Uso:**
- Testar feature em staging antes de merge
- Deploy de hotfix em branch específica
- Validar mudança grande antes de merge para main

**Implementação:**
```bash
BRANCH="${2:-refactor-ia}"

# Validação
if [[ "$BRANCH" != "refactor-ia" && "$BRANCH" != "main" ]]; then
    echo "⚠️  ATENÇÃO: Deploy de feature branch '$BRANCH'"
    echo "Recomendado apenas para staging/dev"
    read -p "Continuar? (s/n) " -r
    [[ ! $REPLY =~ ^[Ss]$ ]] && exit 1
fi

# Deploy
ssh "$USER@$HOST" << EOF
    cd $PATH
    git checkout $BRANCH
    git pull origin $BRANCH
EOF
```

---

### Modo 3: Commit Específico (Rollback)

**Uso:**
```bash
./deploy.sh --commit abc123f
./deploy.sh --rollback  # Automaticamente usa backup mais recente
```

**Comportamento:**
1. Valida que commit existe
2. **NÃO executa build local** (usa estado do commit)
3. No servidor: `git checkout <commit>`
4. Cria tag temporária: `rollback-$(date +%Y%m%d-%H%M%S)`
5. Continua fluxo normal

**Validações:**
- ⚠️ Avisa sobre detached HEAD
- ⚠️ Confirma rollback com informações do commit
- ✅ Mostra diff entre versão atual e commit

**Casos de Uso:**
- **Rollback urgente**: Bug crítico em produção
- **Teste de versão**: Validar se bug existia em commit anterior
- **Restauração**: Voltar para versão estável conhecida

**Implementação:**
```bash
COMMIT="$2"

# Validação
if ! git rev-parse --verify "$COMMIT" >/dev/null 2>&1; then
    echo "❌ Commit '$COMMIT' não existe"
    exit 1
fi

# Info do commit
echo "📋 Commit: $(git log -1 --oneline $COMMIT)"
echo "📅 Data: $(git log -1 --format=%cd $COMMIT)"
echo "👤 Autor: $(git log -1 --format=%an $COMMIT)"
echo ""
read -p "⚠️  Fazer rollback para esta versão? (s/n) " -r
[[ ! $REPLY =~ ^[Ss]$ ]] && exit 1

# Deploy
ROLLBACK_TAG="rollback-$(date +%Y%m%d-%H%M%S)"
ssh "$USER@$HOST" << EOF
    cd $PATH
    git checkout $COMMIT
    git tag $ROLLBACK_TAG
    echo "Tag criada: $ROLLBACK_TAG"
EOF
```

**Modo Alternativo: --rollback**
```bash
./deploy.sh --rollback

# Automaticamente:
# 1. Lista últimos 5 backups
# 2. Sugere backup mais recente antes do problema
# 3. Extrai commit hash do backup
# 4. Executa checkout para aquele commit
```

---

### Modo 4: Pacote Local (Deploy sem Commit)

**Uso:**
```bash
./deploy.sh --package
./deploy.sh --package --exclude "node_modules,vendor,.git"
```

**Comportamento:**
1. Valida mudanças locais (avisa se há arquivos não commitados)
2. Executa build local (`./build.sh`)
3. Compacta arquivos locais: `tar -czf deploy-$(date).tar.gz`
4. Envia via rsync/scp para servidor
5. No servidor: Extrai pacote, ajusta permissões, restart

**Validações:**
- ⚠️ **CRÍTICO**: Avisa que produção ficará diferente do git
- ⚠️ Lista arquivos não commitados que serão enviados
- ⚠️ Requer confirmação explícita: `CONFIRM_PACKAGE_DEPLOY=yes`

**Casos de Uso:**
- **Teste pré-commit**: Validar mudanças em staging
- **Debug urgente**: Testar fix sem commit
- **Validação de performance**: Testar otimização antes de commit

**⚠️ PERIGOS:**
- Produção fica inconsistente com repositório
- Difícil rastrear qual versão está em produção
- Próximo deploy via CI/CD pode sobrescrever mudanças

**Implementação:**
```bash
# Validação
CHANGED_FILES=$(git status --porcelain | wc -l)
if [ "$CHANGED_FILES" -gt 0 ]; then
    echo "⚠️  ATENÇÃO: $CHANGED_FILES arquivos não commitados"
    git status --short
    echo ""
    echo "🔴 PERIGO: Produção ficará diferente do repositório!"
    echo "   Próximo deploy via CI/CD pode sobrescrever estas mudanças"
    echo ""
    read -p "Realmente deseja continuar? Digite 'CONFIRMO': " -r
    [[ "$REPLY" != "CONFIRMO" ]] && exit 1
fi

# Build
./build.sh
PACKAGE="deploy-$(date +%Y%m%d-%H%M%S).tar.gz"

# Compacta (exclui git, node_modules, vendor)
tar -czf "$PACKAGE" \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='*.tar.gz' \
    .

# Envia e extrai
rsync -avz "$PACKAGE" "$USER@$HOST:$PATH/"
ssh "$USER@$HOST" << EOF
    cd $PATH
    tar -xzf $PACKAGE
    rm $PACKAGE
    echo "⚠️  PACOTE LOCAL IMPLANTADO - NÃO VIA GIT"
    echo "   Registre esta operação no changelog"
EOF

# Cleanup
rm "$PACKAGE"
```

---

### Modo 5: Arquivos Específicos (Hotfix)

**Uso:**
```bash
./deploy.sh --files "version.php"
./deploy.sh --files "version.php,js/session-manager.min.js,build-info.json"
./deploy.sh --files "*.php" --pattern  # Usa padrão glob
```

**Comportamento:**
1. Valida que arquivos existem localmente
2. **NÃO executa build** (assume que arquivos já estão prontos)
3. Faz backup apenas dos arquivos afetados
4. Envia via rsync com `--checksum` (só atualiza se diferente)
5. Ajusta permissões, **NÃO** reinicia Apache (optional flag)

**Validações:**
- ⚠️ Avisa sobre deploy parcial
- ⚠️ Lista arquivos que serão atualizados
- ⚠️ Mostra diff de cada arquivo
- ✅ Opção `--dry-run` para simular

**Casos de Uso:**
- **Hotfix CSS/JS**: Atualizar apenas arquivo minificado
- **Config urgente**: Atualizar apenas credentials.php
- **Correção única**: Fix em arquivo específico sem full deploy

**Implementação:**
```bash
FILES="$2"
IFS=',' read -ra FILE_ARRAY <<< "$FILES"

# Validação
echo "📋 Arquivos a serem atualizados:"
for FILE in "${FILE_ARRAY[@]}"; do
    if [ ! -f "$FILE" ]; then
        echo "❌ Arquivo não existe: $FILE"
        exit 1
    fi
    echo "  • $FILE ($(stat -f%z "$FILE") bytes)"
done

echo ""
echo "⚠️  HOTFIX PARCIAL: Apenas arquivos listados serão atualizados"
read -p "Continuar? (s/n) " -r
[[ ! $REPLY =~ ^[Ss]$ ]] && exit 1

# Backup e deploy
for FILE in "${FILE_ARRAY[@]}"; do
    # Backup no servidor
    ssh "$USER@$HOST" "cp $PATH/$FILE $PATH/${FILE}.backup-$(date +%s)"
    
    # Mostra diff
    echo "📊 Diff de $FILE:"
    diff <(ssh "$USER@$HOST" "cat $PATH/$FILE") "$FILE" || true
    
    # Envia
    rsync -av --checksum "$FILE" "$USER@$HOST:$PATH/$FILE"
done

echo "✅ Hotfix concluído"
echo "💡 Lembre de commitar estas mudanças depois"
```

---

## 🔒 Safeguards e Validações

### Validações Globais (Todos os Modos):

1. **Pre-flight checks:**
   - ✅ Servidor acessível (ping + SSH teste)
   - ✅ Permissões de escrita no diretório
   - ✅ Espaço em disco suficiente
   - ✅ Git repository válido (exceto modo --package)

2. **Backup automático:**
   - ✅ Backup criado antes de qualquer operação
   - ✅ Mantém últimos 10 backups
   - ✅ Tag de backup com timestamp + modo usado

3. **Rollback automático:**
   - ✅ Se deploy falhar, restaura backup automaticamente
   - ✅ Valida serviços após deploy (HTTP 200, Apache OK)
   - ✅ Timeout de 30s para validação

4. **Logging detalhado:**
   - ✅ Log em `/var/log/4sqmet-deploy.log`
   - ✅ Registra: modo, usuário, timestamp, arquivos mudados
   - ✅ Inclui git commit hash atual antes e depois

### Validações Específicas por Modo:

**--branch:**
- ⚠️ Confirma se não é branch main/refactor-ia
- ⚠️ Avisa sobre deploy em produção vs staging

**--commit:**
- ⚠️ Confirma detached HEAD
- ⚠️ Mostra diff entre versão atual e commit alvo
- ✅ Cria tag para facilitar voltar

**--package:**
- 🔴 Requer confirmação dupla
- 🔴 Lista arquivos não commitados
- 🔴 Avisa sobre inconsistência git

**--files:**
- ⚠️ Mostra diff de cada arquivo
- ⚠️ Confirma que não precisa restart (default: no restart)
- ✅ Opção `--dry-run` disponível

---

## 📊 Comparação dos Modos

| Modo | Velocidade | Risco | Rastreabilidade | Uso Recomendado |
|------|-----------|-------|-----------------|-----------------|
| **--pull** | ⚡⚡ 2-3min | 🟢 Baixo | ✅ Total | Padrão (99% casos) |
| **--branch** | ⚡⚡ 2-3min | 🟡 Médio | ✅ Total | Feature deploy, staging |
| **--commit** | ⚡⚡⚡ 1-2min | 🟡 Médio | ✅ Total | Rollback, teste versão |
| **--package** | ⚡ 3-5min | 🔴 Alto | ❌ Parcial | Teste pré-commit (staging) |
| **--files** | ⚡⚡⚡⚡ <1min | 🔴 Alto | ❌ Parcial | Hotfix único arquivo |

---

## 🚀 Implementação

### Fase 1: Estrutura Base
- [ ] Criar parser de argumentos (getopts)
- [ ] Implementar validações globais
- [ ] Criar sistema de backup robusto
- [ ] Implementar logging detalhado

### Fase 2: Modos Simples
- [ ] Implementar `--branch`
- [ ] Implementar `--commit`
- [ ] Criar testes para ambos

### Fase 3: Modos Avançados
- [ ] Implementar `--package` com safeguards
- [ ] Implementar `--files` com diff
- [ ] Implementar `--rollback` automático

### Fase 4: Melhorias
- [ ] Adicionar `--dry-run` global
- [ ] Implementar validação pós-deploy
- [ ] Criar rollback automático em falhas
- [ ] Adicionar suporte para múltiplos servidores

---

## 📖 Exemplos de Uso Futuros

```bash
# Rollback urgente
./deploy.sh --commit abc123f

# Teste de feature
./deploy.sh --branch feature/nova-funcionalidade

# Hotfix de arquivo único
./deploy.sh --files "version.php" --no-restart

# Teste antes de commit
./deploy.sh --package --exclude "tests,docs"

# Deploy com validação
./deploy.sh --pull --validate --notify-slack

# Dry run para ver o que mudaria
./deploy.sh --branch main --dry-run
```

---

## ⚠️ Notas Importantes

1. **Modo --package NUNCA deve ser usado em produção** (apenas staging/dev)
2. **Modo --files requer restart manual do Apache** se mudar PHP
3. **Sempre criar backup antes** de qualquer operação
4. **Log todas as operações** para auditoria
5. **Validar serviços** após deploy (HTTP 200, Apache status)

---

## 📚 Referências

- Deploy atual: `deploy.sh`
- Build atual: `build.sh`
- Documentação: `docs/BUILD_AND_DEPLOY.md`
- CI/CD: `.github/workflows/deploy.yml`

---

**Status**: 📝 TODO - Aguardando implementação  
**Prioridade**: Média (quando houver necessidade real dos casos de uso)  
**Estimativa**: 2-3 dias de desenvolvimento + testes  
**Última atualização**: 4 de março de 2026
