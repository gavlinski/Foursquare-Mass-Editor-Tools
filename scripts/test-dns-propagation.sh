#!/bin/bash

#############################################
# Testar Propagação DNS - 4sq.eliotools.site
#############################################

DOMAIN="4sq.eliotools.site"
EXPECTED_IP="134.209.163.143"

echo "🔍 Testando propagação DNS para ${DOMAIN}..."
echo ""

# Teste 1: dig
echo "1️⃣ Teste com dig:"
RESOLVED_IP=$(dig +short ${DOMAIN} | tail -1)
echo "   IP Resolvido: ${RESOLVED_IP}"

if [ "$RESOLVED_IP" = "$EXPECTED_IP" ]; then
    echo "   ✅ DNS correto!"
else
    echo "   ❌ DNS ainda não propagou (esperado: ${EXPECTED_IP})"
fi
echo ""

# Teste 2: nslookup
echo "2️⃣ Teste com nslookup:"
nslookup ${DOMAIN} | grep -A1 "Name:" | tail -1
echo ""

# Teste 3: HTTP
echo "3️⃣ Teste de conectividade HTTP:"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://${DOMAIN}/ --connect-timeout 5)
if [ "$HTTP_STATUS" = "200" ]; then
    echo "   ✅ HTTP respondendo (status: ${HTTP_STATUS})"
else
    echo "   ⚠️  HTTP status: ${HTTP_STATUS}"
fi
echo ""

# Teste 4: DNS em múltiplos servidores
echo "4️⃣ Verificando em DNS públicos:"
echo "   Google DNS (8.8.8.8):"
dig @8.8.8.8 +short ${DOMAIN} | tail -1
echo "   Cloudflare DNS (1.1.1.1):"
dig @1.1.1.1 +short ${DOMAIN} | tail -1
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ "$RESOLVED_IP" = "$EXPECTED_IP" ] && [ "$HTTP_STATUS" = "200" ]; then
    echo "✅ DNS propagado com sucesso!"
    echo "   Você pode prosseguir com o teste OAuth"
else
    echo "⏳ DNS ainda propagando..."
    echo "   Aguarde 5-10 minutos e execute novamente:"
    echo "   bash scripts/test-dns-propagation.sh"
fi
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
