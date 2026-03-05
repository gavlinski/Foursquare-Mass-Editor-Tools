# Build System Skill

## Name
Build System and Minification

## Description
Esta skill gerencia o sistema de build do projeto, incluindo minificação de JavaScript, geração de build-info.json, e controle de origem da build (build_source).

## When to use
Carregue esta skill quando:
- Modificar scripts de build (`build.sh`, `build-docker.sh`)
- Trabalhar com minificação de JavaScript
- Alterar estrutura de `build-info.json`
- Diagnosticar problemas de build
- Atualizar `build_source` ou informações de versão

## Key Files
- `build.sh` - Script principal de build (detecta npm local ou usa Docker)
- `build-docker.sh` - Script executado dentro do container Docker
- `build-info.json` - Arquivo gerado com metadados da build (não versionado)
- `.github/workflows/deploy.yml` - CI/CD pipeline

## Build Source Field

### Valores possíveis
| Valor | Significado | Quando aparece |
|-------|-------------|----------------|
| `"local"` | Build manual por desenvolvedor | `./build.sh` rodado localmente |
| `"deploy"` | Build via script de deploy | `./deploy.sh` executa build antes de deploy |
| `"ci"` | Build automática via CI/CD | GitHub Actions após push |

### Detecção automática
```bash
# Em build.sh e build-docker.sh
if [ -z "$BUILD_SOURCE" ]; then
    if [ "$CI" = "true" ] || [ "$GITHUB_ACTIONS" = "true" ]; then
        BUILD_SOURCE="ci"
    else
        BUILD_SOURCE="local"
    fi
fi
```

### Configuração externa
```bash
# deploy.sh define antes de chamar build.sh
export BUILD_SOURCE="deploy"
bash build.sh

# GitHub Actions define via env variable
env:
  BUILD_SOURCE: ci
```

## Build Process Flow

### Local Build (sem npm)
```
./build.sh
├─> Detecta npm ausente
├─> Usa Docker container (node:22-alpine)
├─> docker run ... sh build-docker.sh
│   ├─> npm install
│   ├─> Minifica 5 arquivos JS com terser
│   ├─> Gera build-info.json com BUILD_SOURCE
│   └─> Retorna *.min.js e *.min.js.map
└─> Exibe estatísticas de redução
```

### Local Build (com npm)
```
./build.sh
├─> Detecta npm disponível
├─> npm install (se necessário)
├─> Minifica cada arquivo JS
│   └─> terser --compress --mangle --source-map
├─> Gera build-info.json
└─> Exibe estatísticas
```

### CI/CD Build
```
GitHub Actions (deploy.yml)
├─> Checkout code
├─> Setup Node.js 18
├─> npm install
├─> BUILD_SOURCE=ci bash build.sh
├─> Upload artifacts
└─> Deploy (se push para refactor-ia/main)
```

## Build Info JSON Structure

```json
{
  "build_date": "2026-03-05T01:28:36Z",
  "build_timestamp": 1772674116,
  "commit_hash": "ddcf2069344788632b19934b404ea60f77cb4c78",
  "commit_short": "ddcf206",
  "branch": "refactor-ia",
  "version": "4SQMET-02_03_00-117-gddcf206",
  "build_source": "local",
  "environment": "production"
}
```

### Campo `environment`
**Sempre `"production"`** - Indica tipo de build (minificada), não ambiente de execução.

## Files Minified

1. `js/4sq.js` → `js/4sq.min.js`
2. `js/4sq_csv.js` → `js/4sq_csv.min.js`
3. `js/main.js` → `js/main.min.js`
4. `js/session-manager.js` → `js/session-manager.min.js`
5. `js/google-maps.js` → `js/google-maps.min.js`

## Common Tasks

### Adicionar novo arquivo JS para minificação
```bash
# Em build.sh (linha ~77)
declare -a FILES=(
    "4sq"
    "4sq_csv"
    "main"
    "session-manager"
    "google-maps"
    "novo-arquivo"  # ← Adicionar aqui (sem .js)
)

# Em build-docker.sh (linha ~14)
FILES="4sq 4sq_csv main session-manager google-maps novo-arquivo"
```

### Testar build localmente
```bash
./build.sh
# Verifica arquivos gerados
ls -lh js/*.min.js
cat build-info.json
```

### Aplicar build no container dev
```bash
./build.sh
docker restart foursquare-mass-editor
# Aguarda 3-5 segundos
curl -k https://localhost/version.php
```

## Troubleshooting

### Build falha: "npm não encontrado"
```bash
# Solução 1: Instalar Node.js localmente
brew install node  # macOS
apt install nodejs npm  # Linux

# Solução 2: Verificar Docker
docker info  # Deve estar rodando

# Solução 3: Forçar uso de Docker
# build.sh automaticamente usa Docker se npm ausente
```

### build-info.json com commit antigo
```bash
# Causa: Não rodou build depois de novos commits
# Solução: Rebuild
./build.sh
cat build-info.json  # Verifica commit_short atualizado
```

### Minificação quebra código
```bash
# Adicionar arquivo ao build.sh com flags especiais
npx terser "js/${FILE}.js" \
    --compress warnings=false,drop_console=false,keep_fnames=true \
    --mangle reserved=['specialFunction'] \
    --output "js/${FILE}.min.js"
```

### CI/CD usa build_source errado
```bash
# Verificar em .github/workflows/deploy.yml
- name: 🔧 Build and minify JavaScript
  run: bash build.sh
  env:
    BUILD_SOURCE: ci  # ← Deve estar definido
```

## Critical Patterns

### NEVER commit minified files
```gitignore
# .gitignore
*.min.js
*.min.js.map
build-info.json
```

### NEVER hardcode build_source
```bash
# ❌ Errado
"build_source": "local"

# ✅ Correto - Detecta automaticamente
BUILD_SOURCE="${BUILD_SOURCE:-local}"
if [ "$CI" = "true" ]; then
    BUILD_SOURCE="ci"
fi
```

### ALWAYS regenerate build-info.json on each build
```bash
# Sobrescreve arquivo existente
cat > build-info.json <<EOF
{
  "build_date": "${BUILD_DATE}",
  ...
}
EOF
```

## Integration Points

- **version.php**: Lê build-info.json, exibe badges baseado em build_source
- **session-manager.js**: Fetch version.php, mostra origem da build no modal
- **deploy.sh**: Define BUILD_SOURCE="deploy" antes de build
- **GitHub Actions**: Define BUILD_SOURCE="ci" via env var

## Documentation
- **Main guide**: `docs/BUILD_AND_DEPLOY.md`
- **Advanced deploy**: `docs/TODO_DEPLOY_ADVANCED.md`
- **Migration notes**: `docs/MIGRATION.md`
