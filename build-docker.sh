#!/bin/sh
# Script executado dentro do container Docker para build
# Usa sh puro para compatibilidade com Alpine

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

echo "✅ Build Docker concluído!"
