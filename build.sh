#!/bin/bash
###############################################################################
# Foursquare Mass Editor Tools - Build Script
# Minifica e otimiza assets JavaScript para produção
###############################################################################

set -e  # Exit on error

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Banner
echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║   Foursquare Mass Editor Tools - Build System v3.0        ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Detecta se npm está disponível, senão tenta usar Docker
if ! command -v npm &> /dev/null; then
    # Verifica se já está dentro de Docker (evita recursão)
    if [ -f /.dockerenv ] || grep -q docker /proc/1/cgroup 2>/dev/null; then
        echo -e "${RED}❌ npm não encontrado dentro do container Docker!${NC}"
        exit 1
    fi
    
    # Verifica se Docker está disponível
    if ! command -v docker &> /dev/null; then
        echo -e "${RED}❌ npm/npx não encontrado e Docker não está disponível.${NC}"
        echo -e "${YELLOW}💡 Solução: Instale Node.js ou Docker${NC}"
        exit 1
    fi
    
    echo -e "${YELLOW}🐳 npm não encontrado localmente. Usando Docker...${NC}"
    echo -e "${BLUE}   Imagem: node:22-alpine (~50MB)${NC}\n"
    
    # Executa build dentro de container Docker
    if docker run --rm \
        -v "$(pwd):/workspace" \
        -w /workspace \
        node:22-alpine \
        sh build-docker.sh; then
        
        echo -e "\n${GREEN}✅ Build concluído com sucesso via Docker!${NC}"
        echo -e "${BLUE}💡 Para builds mais rápidos, considere instalar Node.js localmente${NC}\n"
        exit 0
    else
        echo -e "\n${RED}❌ Erro no build via Docker${NC}"
        exit 1
    fi
fi

# NPM disponível localmente - continua fluxo normal
# Verifica se node_modules existe
if [ ! -d "node_modules" ]; then
    echo -e "${YELLOW}📦 Instalando dependências...${NC}"
    npm install
fi

# Verifica se terser está instalado
if ! command -v npx &> /dev/null; then
    echo -e "${RED}❌ npm/npx não encontrado. Instale Node.js primeiro.${NC}"
    exit 1
fi

echo -e "${BLUE}🔧 Iniciando minificação de scripts JavaScript...${NC}\n"

# Array de arquivos para minificar
declare -a FILES=(
    "4sq"
    "4sq_csv"
    "main"
    "session-manager"
    "google-maps"
)

# Contador de sucesso
SUCCESS=0
FAILED=0

# Minifica cada arquivo
for FILE in "${FILES[@]}"; do
    SOURCE="js/${FILE}.js"
    OUTPUT="js/${FILE}.min.js"
    
    if [ -f "$SOURCE" ]; then
        echo -e "${YELLOW}⚙️  Minificando: ${FILE}.js${NC}"
        
        # Calcula tamanho original
        ORIGINAL_SIZE=$(wc -c < "$SOURCE" | tr -d ' ')
        
        # Minifica com terser
        if npx terser "$SOURCE" \
            --compress warnings=false,drop_console=false,drop_debugger=true \
            --mangle \
            --output "$OUTPUT" \
            --source-map "filename='${OUTPUT}.map',url='${FILE}.min.js.map'" 2>/dev/null; then
            
            # Calcula tamanho minificado
            MINIFIED_SIZE=$(wc -c < "$OUTPUT" | tr -d ' ')
            
            # Calcula redução percentual
            REDUCTION=$(echo "scale=1; 100 - ($MINIFIED_SIZE * 100 / $ORIGINAL_SIZE)" | bc)
            
            # Formata tamanhos
            ORIGINAL_KB=$(echo "scale=1; $ORIGINAL_SIZE / 1024" | bc)
            MINIFIED_KB=$(echo "scale=1; $MINIFIED_SIZE / 1024" | bc)
            
            echo -e "${GREEN}   ✅ ${FILE}.min.js criado${NC}"
            echo -e "      Original: ${ORIGINAL_KB}KB → Minificado: ${MINIFIED_KB}KB (${REDUCTION}% redução)"
            echo ""
            
            SUCCESS=$((SUCCESS + 1))
        else
            echo -e "${RED}   ❌ Erro ao minificar ${FILE}.js${NC}\n"
            FAILED=$((FAILED + 1))
        fi
    else
        echo -e "${RED}⚠️  Arquivo não encontrado: $SOURCE${NC}\n"
        FAILED=$((FAILED + 1))
    fi
done

# Relatório final
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Build concluído!${NC}"
echo -e "   Sucesso: ${SUCCESS} arquivos"
if [ $FAILED -gt 0 ]; then
    echo -e "   ${RED}Falhas: ${FAILED} arquivos${NC}"
fi

# Calcula redução total
if [ $SUCCESS -gt 0 ]; then
    TOTAL_ORIGINAL=0
    TOTAL_MINIFIED=0
    
    for FILE in "${FILES[@]}"; do
        SOURCE="js/${FILE}.js"
        OUTPUT="js/${FILE}.min.js"
        if [ -f "$SOURCE" ] && [ -f "$OUTPUT" ]; then
            TOTAL_ORIGINAL=$((TOTAL_ORIGINAL + $(wc -c < "$SOURCE" | tr -d ' ')))
            TOTAL_MINIFIED=$((TOTAL_MINIFIED + $(wc -c < "$OUTPUT" | tr -d ' ')))
        fi
    done
    
    TOTAL_REDUCTION=$(echo "scale=1; 100 - ($TOTAL_MINIFIED * 100 / $TOTAL_ORIGINAL)" | bc)
    TOTAL_ORIGINAL_KB=$(echo "scale=1; $TOTAL_ORIGINAL / 1024" | bc)
    TOTAL_MINIFIED_KB=$(echo "scale=1; $TOTAL_MINIFIED / 1024" | bc)
    SAVED_KB=$(echo "scale=1; ($TOTAL_ORIGINAL - $TOTAL_MINIFIED) / 1024" | bc)
    
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}📊 Estatísticas Totais:${NC}"
    echo -e "   Original total: ${TOTAL_ORIGINAL_KB}KB"
    echo -e "   Minificado total: ${TOTAL_MINIFIED_KB}KB"
    echo -e "   Economia: ${SAVED_KB}KB (${TOTAL_REDUCTION}% redução)"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
fi

# .gitignore para arquivos minificados
if ! grep -q "*.min.js" .gitignore 2>/dev/null; then
    echo -e "\n${YELLOW}📝 Adicionando *.min.js ao .gitignore${NC}"
    echo -e "\n# Build artifacts\n*.min.js\n*.min.js.map" >> .gitignore
fi

# Gera arquivo de informações de build
echo -e "\n${BLUE}📋 Gerando informações de build...${NC}"

BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
BUILD_TIMESTAMP=$(date +%s)
COMMIT_HASH=$(git rev-parse HEAD 2>/dev/null || echo "unknown")
COMMIT_SHORT=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")
VERSION=$(git describe --tags --always 2>/dev/null || echo "dev-$(date +%Y%m%d)")

# Detecta se está rodando em Docker ou localmente
if [ -f /.dockerenv ] || grep -q docker /proc/1/cgroup 2>/dev/null; then
    BUILT_WITH="docker"
else
    BUILT_WITH="local"
fi

# Cria o arquivo build-info.json
cat > build-info.json <<EOF
{
  "build_date": "${BUILD_DATE}",
  "build_timestamp": ${BUILD_TIMESTAMP},
  "commit_hash": "${COMMIT_HASH}",
  "commit_short": "${COMMIT_SHORT}",
  "branch": "${BRANCH}",
  "version": "${VERSION}",
  "built_with": "${BUILT_WITH}",
  "environment": "production"
}
EOF

if [ -f build-info.json ]; then
    echo -e "${GREEN}   ✅ build-info.json criado${NC}"
    echo -e "      Versão: ${VERSION}"
    echo -e "      Commit: ${COMMIT_SHORT}"
    echo -e "      Branch: ${BRANCH}"
    echo -e "      Build: ${BUILT_WITH}"
else
    echo -e "${RED}   ❌ Erro ao criar build-info.json${NC}"
fi

echo -e "\n${GREEN}🚀 Build pronto para deploy!${NC}\n"

exit 0
