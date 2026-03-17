#!/bin/bash
# Certbot post-hook: garante que o container esteja rodando após qualquer
# tentativa de renovação (seja ela bem-sucedida ou não).
# Safety net: mesmo se o deploy-hook não executou (renovação falhou),
# o container é reiniciado para não ficar parado.
echo "▶️  Iniciando container Docker após renovação SSL..."
docker start 4sqmet 2>/dev/null || true
echo "✅ Container iniciado"
