#!/bin/sh
# Script executado dentro do container Docker para build
# Usa sh puro para compatibilidade com Alpine

# Instala git se precisar de informações de commit
if ! command -v git >/dev/null 2>&1; then
    echo "📦 Instalando git para informações de commit..."
    apk add --no-cache git 2>/dev/null || echo "⚠️  Git não disponível"
fi

echo "📦 Instalando dependências..."
npm install --silent

echo "🔧 Minificando JavaScript..."

# Array de arquivos
FILES="4sq 4sq_csv main session-manager google-maps"

for FILE in $FILES; do
    printf "⚙️  Minificando: %s.js\n" "$FILE"
    npx terser "js/${FILE}.js" \
        --compress warnings=false,drop_console=false,drop_debugger=true \
        --mangle \
        --output "js/${FILE}.min.js" \
        --source-map "filename='js/${FILE}.min.js.map',url='${FILE}.min.js.map'" \
        2>/dev/null
    
    if [ $? -eq 0 ]; then
        printf "   ✅ %s.min.js criado\n" "$FILE"
    else
        printf "   ❌ Erro ao minificar %s.js\n" "$FILE"
    fi
done

echo "📋 Gerando informações de build..."

# Gera informações de build (compatível com Alpine sh)
BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
BUILD_TIMESTAMP=$(date +%s)
COMMIT_HASH=$(git rev-parse HEAD 2>/dev/null || echo "unknown")
COMMIT_SHORT=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")
VERSION=$(git describe --tags --always 2>/dev/null || echo "dev-$(date +%Y%m%d)")

# Cria o arquivo build-info.json
cat > build-info.json <<EOF
{
  "build_date": "${BUILD_DATE}",
  "build_timestamp": ${BUILD_TIMESTAMP},
  "commit_hash": "${COMMIT_HASH}",
  "commit_short": "${COMMIT_SHORT}",
  "branch": "${BRANCH}",
  "version": "${VERSION}",
  "built_with": "docker",
  "environment": "production"
}
EOF

if [ -f build-info.json ]; then
    printf "   ✅ build-info.json criado (versão: %s)\n" "$VERSION"
else
    printf "   ❌ Erro ao criar build-info.json\n"
fi

echo "✅ Build Docker concluído!"

