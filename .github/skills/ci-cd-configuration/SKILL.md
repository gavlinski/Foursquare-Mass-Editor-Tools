# CI/CD Configuration Skill

## Name
GitHub Actions Configuration & Variable Management

## Description
Gerencia a configuração adequada de secrets e repository variables para o pipeline de CI/CD, com foco em centralização, segurança e clareza de responsabilidades.

## When to use
Carregue esta skill quando:
- Configurar secrets ou repository variables
- Revisar ou atualizar pipeline configuration
- Trabalhar com credenciais e configurações sensíveis
- Debugar problemas de acesso/autenticação no CI/CD
- Auditar boas práticas de gestão de credenciais

## Key Files
- `.github/workflows/deploy.yml` - Pipeline CI/CD que usa secrets/vars
- `.github/CICD_SETUP.md` - Documentação de configuração
- `.env.example` - Exemplo de variáveis locais

## Configuration Hierarchy

### Secrets vs Repository Variables

**🔒 SECRETS** (Encrypted - Sensitive Data)
- Criptografadas AES-256 pelo GitHub
- Nunca expostas em logs ou outputs
- Usadas para credenciais e tokens

```yaml
env:
  # ✅ Correto - Secrets para dados sensíveis
  DEPLOY_SSH_KEY: ${{ secrets.DEPLOY_SSH_KEY }}
  APP_URL: ${{ secrets.APP_URL }}
  FOURSQUARE_CLIENT_KEY: ${{ secrets.FOURSQUARE_CLIENT_KEY }}
```

### Secrets Necessários (9 total)

```
1. DEPLOY_SSH_KEY        - Chave privada SSH (536 bytes)
2. DEPLOY_USER           - Usuário SSH (4 bytes)
3. FOURSQUARE_CLIENT_KEY - API credentials (50 bytes)
4. FOURSQUARE_CLIENT_SECRET - API secret (48 bytes)
5. FOURSQUARE_REDIRECT_URI - callback URL (50 bytes)
6. GOOGLE_MAPS_API_KEY   - Maps API (39 bytes)
7. GOOGLE_MAPS_MAP_ID    - Map ID (24 bytes)
8. GOOGLE_MAPS_GEOCODING_KEY - Geocoding API
9. APP_URL               - Production URL (30 bytes)
```

**📋 REPOSITORY VARIABLES** (Public - Non-Sensitive)
- Visíveis no repositório
- Usadas para configuração não-sensível (IPs, domínios, paths)
- Fácil auditoria e mudanças

```yaml
env:
  # ✅ Correto - Variables para config pública
  DEPLOY_HOST: ${{ vars.DEPLOY_HOST }}
  PHP_VERSION: '8.1'
  NODE_VERSION: '18'
```

### Variables Necessários (1 total)

```
1. DEPLOY_HOST - IP ou domínio do servidor (22 bytes)
                 Exemplo: 134.209.163.143 ou 4sq.eliotools.site
```

## Centralização Pattern

### ❌ ANTES (Duplicação/Confusão)
```yaml
env:
  DEPLOY_HOST: '134.209.163.143'        # Hardcoded
  PRODUCTION_URL: 'https://4sq...'      # Hardcoded
  APP_URL: ${{ secrets.APP_URL }}       # Secret

jobs:
  deploy:
    env:
      APP_URL: ${{ secrets.APP_URL }}    # ❌ Duplicado
      DEPLOY_HOST: '134.209.163.143'    # ❌ Duplicado
```

### ✅ DEPOIS (Single Source of Truth)
```yaml
env:
  DEPLOY_HOST: ${{ vars.DEPLOY_HOST }}       # De vars
  PRODUCTION_URL: ${{ secrets.APP_URL }}    # De secret
  PHP_VERSION: '8.1'                        # Hardcoded (versionado)
  NODE_VERSION: '18'                        # Hardcoded (versionado)

jobs:
  deploy:
    env:
      DEPLOY_HOST: ${{ env.DEPLOY_HOST }}    # ✅ Reutiliza workflow env
      APP_URL: ${{ env.PRODUCTION_URL }}     # ✅ Alias do secret
      # Sem duplicação
```

## Setup Checklist

### 1. Criar Repository Variable

```
GitHub → Settings → Secrets and variables → Actions → Variables
```

**Preencher:**
```
Name: DEPLOY_HOST
Value: 134.209.163.143 (seu IP)
```

### 2. Verificar/Atualizar Secrets

Deve existir (ou criar):
```
- DEPLOY_SSH_KEY
- DEPLOY_USER
- APP_URL
- Credenciais Foursquare (3)
- Credenciais Google Maps (2)
```

### 3. Remover Redundâncias

Se houver secret `DEPLOY_HOST` deixado de versão anterior:
```
GitHub → Settings → Secrets → Delete DEPLOY_HOST
```

### 4. Validar Workflow

O workflow deve usar:
```yaml
env:
  DEPLOY_HOST: ${{ vars.DEPLOY_HOST }}      # From vars
  PRODUCTION_URL: ${{ secrets.APP_URL }}    # From secret
```

## Best Practices

### ✅ DO (Boas Práticas)

1. **Centralizar em um único lugar**
   ```yaml
   # Definir no topo do workflow
   env:
     DEPLOY_HOST: ${{ vars.DEPLOY_HOST }}
     APP_URL: ${{ env.PRODUCTION_URL }}  # Alias
   
   # Reutilizar em jobs/steps
   jobs:
     deploy:
       env:
         DEPLOY_HOST: ${{ env.DEPLOY_HOST }}  # Sempre referencia global
   ```

2. **Separar por tipo**
   - Secrets = credenciais, tokens, senhas
   - Variables = IPs, domínios, versões, paths

3. **Usar nomes descritivos**
   ```yaml
   ✅ DEPLOY_HOST (claro o propósito)
   ✅ FOURSQUARE_CLIENT_KEY (qual API)
   ❌ HOST (ambíguo)
   ❌ KEY (qual chave?)
   ```

4. **Documentar em CICD_SETUP.md**
   - Manter lista sincronizada com workflow
   - Incluir tamanho/formato esperado
   - Versionar mudanças significativas

### ❌ DON'T (Evitar)

1. **NÃO hardcode IPs/portas**
   ```yaml
   ❌ DEPLOY_HOST: '134.209.163.143'  # Vai desatualizar
   ✅ DEPLOY_HOST: ${{ vars.DEPLOY_HOST }}
   ```

2. **NÃO colocar credenciais em vars**
   ```yaml
   ❌ APP_URL: 'https://user:pass@server'  # Inseguro em var pública
   ✅ APP_URL: ${{ secrets.APP_URL }}  # Em secret
   ```

3. **NÃO duplicar valores**
   ```yaml
   ❌ env:
        APP_URL: ${{ secrets.APP_URL }}
      jobs:
        deploy:
          env:
            APP_URL: ${{ secrets.APP_URL }}  # Duplicado

   ✅ env:
        APP_URL: ${{ secrets.APP_URL }}
      jobs:
        deploy:
          env:
            ALIASED_URL: ${{ env.APP_URL }}  # Reutilizado
   ```

4. **NÃO commitar valores sensíveis**
   ```bash
   ❌ git add .env.production
   ✅ git add .env.example  # Apenas template
   ```

## Configuration Drift Prevention

### Problema
Se o IP do servidor muda, você precisa atualizar:
1. GitHub secret/var (1 lugar)
2. Mas o workflow tem valor hardcoded (agora desatualizado)
3. Deploy falha porque IP está errado

### Solução
```yaml
# ✅ Tudo centralizado em vars
env:
  DEPLOY_HOST: ${{ vars.DEPLOY_HOST }}  # Único lugar
  
# Quando mudar server:
# GitHub → Variables → DEPLOY_HOST → Editar
# Próximo deploy usa novo IP automaticamente
```

## Auditoria & Rotação

### Rastrear Mudanças
```
GitHub → Settings → Secrets and variables → Actions
```

Cada item mostra:
- Criado em: data
- Atualizado em: data
- Quem modificou: username (no histórico de eventos)

### Rotacionar Credenciais
```bash
# 1. Gerar nova chave SSH
ssh-keygen -t ed25519 -f ~/.ssh/4sqmet_deploy_new

# 2. Atualizar no servidor
ssh root@server
echo "$(cat ~/.ssh/4sqmet_deploy_new.pub)" >> ~/.ssh/authorized_keys

# 3. Atualizar no GitHub
GitHub → Settings → Secrets → DEPLOY_SSH_KEY → Update

# 4. Remover chave antiga do servidor
nano ~/.ssh/authorized_keys  # remover linha antiga
```

## Troubleshooting

### Erro: "Variable not found" no workflow

**Causa**: Variable `DEPLOY_HOST` criada em Secrets em vez de Variables

**Solução**:
```
GitHub → Settings → Secrets and variables → Variables
# Verificar se DEPLOY_HOST existe na aba "Variables"
# Se estiver em Secrets, mover para Variables
```

### Erro: "Can't find secret" ao fazer merge

**Causa**: Secret não existe em todos os ambientes (branches)

**Solução**:
```
Secrets são por repositório (não por branch)
Mas pode ter diferentes valores por environment
GitHub → Settings → Environments
```

### IP antigo sendo usado

**Causa**: Workflow referencia hardcoded em vez de `vars.DEPLOY_HOST`

**Solução**:
```yaml
# Checar workflow
cat .github/workflows/deploy.yml | grep DEPLOY_HOST
# Deve retornar: ${{ vars.DEPLOY_HOST }}
# Se retornar IP literal, atualizar para vars
```

---

**Última atualização**: 17 de Março de 2026  
**Versão**: 1.1.0  
**Referências**: `.github/CICD_SETUP.md`, `.github/workflows/deploy.yml`
