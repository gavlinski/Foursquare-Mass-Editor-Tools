#!/bin/bash
# Certbot deploy-hook: reinicia o container após renovação BEM-SUCEDIDA.
# Executado apenas quando um novo certificado é efetivamente obtido.
echo "🔄 Certificado renovado. Reiniciando container Docker..."
docker restart 4sqmet 2>/dev/null || echo "Container não estava rodando"
echo "✅ Container reiniciado"
