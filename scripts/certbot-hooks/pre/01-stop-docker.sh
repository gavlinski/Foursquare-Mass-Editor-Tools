#!/bin/bash
# Certbot pre-hook: para o container Docker antes do ACME HTTP-01 challenge.
# Necessário porque o certbot standalone precisa bindar a porta 80,
# que está ocupada pelo docker-proxy.
echo "⏸️  Parando container Docker para renovação SSL..."
docker stop 4sqmet
echo "✅ Container parado"
