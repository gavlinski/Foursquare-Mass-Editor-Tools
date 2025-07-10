#!/bin/bash

# Script de Deploy para Produção - Foursquare Mass Editor Tools
# Este script automatiza o deploy em ambiente de produção

set -e

echo "🚀 Deploy para Produção - Foursquare Mass Editor Tools"
echo "====================================================="

# Verificações pré-deploy
echo "🔍 Executando verificações pré-deploy..."

# Verifica se está na branch correta
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "main" ] && [ "$CURRENT_BRANCH" != "master" ]; then
    echo "⚠️  Atenção: Você não está na branch principal ($CURRENT_BRANCH)"
    read -p "Deseja continuar? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Deploy cancelado"
        exit 1
    fi
fi

# Verifica se há mudanças não commitadas
if ! git diff-index --quiet HEAD --; then
    echo "❌ Há mudanças não commitadas. Faça commit antes do deploy."
    exit 1
fi

# Configuração do ambiente
echo "⚙️  Configurando ambiente de produção..."

# Copia configurações de produção
if [ -f ".env.production" ]; then
    cp .env.production .env
    echo "✅ Configurações de produção aplicadas"
else
    echo "❌ Arquivo .env.production não encontrado!"
    exit 1
fi

# Instala dependências de produção
echo "📦 Instalando dependências de produção..."
composer install --no-dev --optimize-autoloader --no-interaction

# Configurações de segurança
echo "🔒 Aplicando configurações de segurança..."

# Verifica permissões de arquivos
chmod 644 .env
chmod 755 . -R
chmod 644 includes/app_credentials.php

# Remove arquivos de desenvolvimento se existirem
rm -f .env.example 2>/dev/null || true
rm -f dev.sh 2>/dev/null || true
rm -f Dockerfile 2>/dev/null || true
rm -f docker-compose.yml 2>/dev/null || true

# Verificações finais
echo "🔍 Executando verificações finais..."

# Verifica se arquivos essenciais existem
REQUIRED_FILES=("index.php" "main.php" "src/Api/FoursquareApi.php" "vendor/autoload.php")
for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        echo "❌ Arquivo obrigatório não encontrado: $file"
        exit 1
    fi
done

# Verifica configurações críticas
if ! grep -q "production" .env; then
    echo "❌ Ambiente não está configurado como produção!"
    exit 1
fi

# Limpeza de cache e arquivos temporários
echo "🧹 Limpando cache e arquivos temporários..."
rm -rf /tmp/cache-* 2>/dev/null || true
rm -rf cache/* 2>/dev/null || true

echo "✅ Deploy concluído com sucesso!"
echo "🌐 Aplicação disponível em: http://4sq.eliotools.site"
echo ""
echo "📋 Checklist pós-deploy:"
echo "  □ Verificar se a aplicação está acessível"
echo "  □ Testar autenticação com Foursquare"
echo "  □ Verificar logs de erro"
echo "  □ Confirmar funcionalidades principais"
echo ""
echo "📝 Para rollback, execute: git checkout HEAD~1 && ./deploy.sh"
