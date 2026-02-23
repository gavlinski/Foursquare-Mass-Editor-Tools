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

echo -e "\n${GREEN}🚀 Build pronto para deploy!${NC}\n"

exit 0
