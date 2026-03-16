#!/bin/bash
set -e

echo "🚀 Iniciando Foursquare Mass Editor Tools..."

# Em produção, certificados SSL vêm via volume mount do Let's Encrypt
# Criar symlinks para o Apache encontrá-los no caminho esperado
if [ -d "/etc/letsencrypt/live" ]; then
    echo "🔐 Ambiente de produção detectado - configurando certificados SSL..."
    
    # Encontrar o diretório do certificado (pode ser 4sq.eliotools.site ou outro domínio)
    CERT_DIR=$(ls -1 /etc/letsencrypt/live/ | head -1)
    
    if [ -n "$CERT_DIR" ]; then
        echo "   Certificado encontrado: $CERT_DIR"
        
        # Criar diretório de destino
        mkdir -p /etc/ssl/4sqmet
        
        # Criar symlinks para os certificados
        ln -sf "/etc/letsencrypt/live/$CERT_DIR/fullchain.pem" /etc/ssl/4sqmet/fullchain.pem
        ln -sf "/etc/letsencrypt/live/$CERT_DIR/privkey.pem" /etc/ssl/4sqmet/privkey.pem
        ln -sf "/etc/letsencrypt/live/$CERT_DIR/chain.pem" /etc/ssl/4sqmet/chain.pem
        
        echo "   ✅ Symlinks SSL criados:"
        ls -lh /etc/ssl/4sqmet/
    else
        echo "   ⚠️  Nenhum certificado encontrado em /etc/letsencrypt/live/"
        echo "   Apache iniciará apenas com HTTP"
    fi
else
    echo "ℹ️  Ambiente de desenvolvimento - usando certificados locais"
fi

echo "🌐 Iniciando Apache..."

# Inicia Apache em foreground (necessário para Docker)
exec apache2-foreground
