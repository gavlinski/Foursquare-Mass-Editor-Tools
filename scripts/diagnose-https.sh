#!/bin/bash

#############################################
# Diagnóstico HTTPS - Foursquare Mass Editor
#############################################

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_header() {
    echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

DOMAIN="4sq.eliotools.site"

print_header "🔍 Diagnóstico HTTPS - ${DOMAIN}"

#############################################
# 1. Testar HTTPS Externo
#############################################
print_header "1️⃣ Teste HTTPS Externo"

print_info "Testando conexão HTTPS externa..."
HTTPS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://${DOMAIN}/ --connect-timeout 10 2>&1)
CURL_EXIT_CODE=$?

if [ "$HTTPS_STATUS" = "200" ] || [ "$HTTPS_STATUS" = "302" ]; then
    print_success "HTTPS funcionando! Status: ${HTTPS_STATUS}"
    HTTPS_WORKING=true
elif [ "$HTTPS_STATUS" = "000" ] || [ "$HTTPS_STATUS" = "000000" ]; then
    print_error "HTTPS não respondeu (status: ${HTTPS_STATUS}, exit code: ${CURL_EXIT_CODE})"
    print_info "Isso pode indicar que Apache não está escutando na porta 443"
    HTTPS_WORKING=false
else
    print_warning "HTTPS respondeu mas com status não esperado: ${HTTPS_STATUS}"
    HTTPS_WORKING=false
fi

#############################################
# 2. Verificar Container e Portas
#############################################
print_header "2️⃣ Container e Portas"

print_info "Status do container:"
docker ps --filter name=4sqmet --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

print_info "Portas mapeadas no container:"
docker port 4sqmet

print_info "Processos escutando na porta 443 (host):"
netstat -tlnp | grep :443 || echo "Nenhum processo na porta 443 (host)"

print_info "Processos escutando na porta 443 (dentro do container):"
# Tentar ss primeiro (mais comum), depois netstat
if docker exec 4sqmet which ss >/dev/null 2>&1; then
    APACHE_LISTEN=$(docker exec 4sqmet ss -tlnp 2>/dev/null | grep :443 || echo "")
elif docker exec 4sqmet which netstat >/dev/null 2>&1; then
    APACHE_LISTEN=$(docker exec 4sqmet netstat -tlnp 2>/dev/null | grep :443 || echo "")
else
    APACHE_LISTEN="N/A"
fi

if [ -n "$APACHE_LISTEN" ] && [ "$APACHE_LISTEN" != "N/A" ]; then
    echo "$APACHE_LISTEN"
else
    # Apache pode estar escutando mas comando não disponível - verificar com outro método
    if docker exec 4sqmet apachectl -S 2>&1 | grep -q ":443"; then
        print_success "Apache configurado para porta 443 (VirtualHost ativo)"
    else
        print_warning "Não foi possível verificar (ss/netstat não disponível)"
    fi
fi

#############################################
# 3. Verificar Configuração Apache
#############################################
print_header "3️⃣ Configuração Apache"

print_info "Testando sintaxe da configuração Apache:"
docker exec 4sqmet apachectl configtest 2>&1 | grep -v "Could not reliably determine"

print_info "VirtualHosts ativos:"
docker exec 4sqmet apachectl -S 2>&1 | grep -A 2 "443"

print_info "Módulos SSL carregados:"
if docker exec 4sqmet apachectl -M 2>/dev/null | grep -q ssl_module; then
    print_success "Módulo SSL carregado"
else
    print_error "Módulo SSL NÃO está carregado!"
fi

#############################################
# 4. Verificar Certificados
#############################################
print_header "4️⃣ Certificados SSL"

print_info "Symlinks em /etc/ssl/4sqmet/ (dentro do container):"
docker exec 4sqmet ls -lh /etc/ssl/4sqmet/ 2>/dev/null || print_error "Diretório não existe dentro do container!"

print_info "Certificados em /etc/letsencrypt/live/:"
ls -lh /etc/letsencrypt/live/${DOMAIN}/ 2>/dev/null | grep -E "fullchain|privkey"

print_info "Verificando se certificados são válidos:"
if [ -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]; then
    openssl x509 -in /etc/letsencrypt/live/${DOMAIN}/fullchain.pem -noout -dates
else
    print_error "Certificado fullchain.pem não encontrado!"
fi

#############################################
# 5. Logs do Container
#############################################
print_header "5️⃣ Logs do Container (últimas 10 linhas)"

docker logs --tail 10 4sqmet 2>&1

#############################################
# 6. Logs de Erro do Apache
#############################################
print_header "6️⃣ Logs de Erro do Apache"

print_info "Verificando logs de erro do Apache..."
# Usar timeout para evitar travamento e limitar a 5 linhas
ERROR_LOG=$(timeout 3 docker exec 4sqmet sh -c 'if [ -f /var/log/apache2/error.log ]; then tail -5 /var/log/apache2/error.log; fi' 2>/dev/null)

if [ -n "$ERROR_LOG" ]; then
    # Mostrar apenas erros críticos (não avisos)
    CRITICAL_ERRORS=$(echo "$ERROR_LOG" | grep -i "error\|fatal\|critical" || echo "")
    if [ -n "$CRITICAL_ERRORS" ]; then
        echo "$CRITICAL_ERRORS"
    else
        print_success "Nenhum erro crítico encontrado"
    fi
else
    print_success "Nenhum erro registrado ou log não disponível"
fi

#############################################
# 7. Teste HTTPS Local (dentro do container)
#############################################
print_header "7️⃣ Teste HTTPS Local (localhost)"

print_info "Testando HTTPS de dentro do servidor (localhost)..."
HTTPS_LOCAL=$(curl -s -o /dev/null -w "%{http_code}" -k https://localhost:443/ --connect-timeout 5 2>&1)

if [ "$HTTPS_LOCAL" = "200" ] || [ "$HTTPS_LOCAL" = "302" ]; then
    print_success "HTTPS local funcionando (status: ${HTTPS_LOCAL})"
    if [ "$HTTPS_WORKING" = false ]; then
        print_warning "HTTPS funciona localmente mas não externamente"
        print_info "Possíveis causas:"
        echo "  • Firewall bloqueando porta 443"
        echo "  • Apache ouvindo em 127.0.0.1:443 ao invés de 0.0.0.0:443"
        echo "  • Problema com Docker port mapping"
    fi
else
    print_error "HTTPS local também não funciona (status: ${HTTPS_LOCAL})"
    print_info "Apache pode não estar configurado corretamente para HTTPS"
fi

#############################################
# 8. Verificar Arquivos de Configuração
#############################################
print_header "8️⃣ Configuração Apache (VirtualHost :443)"

print_info "Verificando VirtualHost :443 na configuração ativa:"
VHOST_443=$(docker exec 4sqmet grep -A 3 "VirtualHost \*:443" /etc/apache2/sites-enabled/000-default.conf 2>/dev/null | head -5)
if [ -n "$VHOST_443" ]; then
    echo "$VHOST_443"
    print_success "VirtualHost :443 encontrado e ativo"
else
    print_error "VirtualHost :443 NÃO encontrado!"
fi

print_info "Verificando caminhos dos certificados SSL:"
docker exec 4sqmet grep "SSLCertificate" /etc/apache2/sites-enabled/000-default.conf 2>/dev/null | head -2

#############################################
# 9. Resumo e Recomendações
#############################################
print_header "📊 Resumo do Diagnóstico"

echo -e "${BLUE}Status atual:${NC}"
echo "  • Container: $(docker ps --filter name=4sqmet --format '{{.Status}}')"
echo "  • HTTPS Externo: ${HTTPS_STATUS}"
echo "  • HTTPS Local: ${HTTPS_LOCAL}"
echo ""

if [ "$HTTPS_WORKING" = true ]; then
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ HTTPS está funcionando corretamente!${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo "Teste no navegador: https://${DOMAIN}/"
else
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}⚠️  HTTPS precisa de atenção${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo "🔧 Ações recomendadas:"
    echo ""
    echo "1. Verificar se Apache está realmente rodando:"
    echo "   docker exec 4sqmet ps aux | grep apache"
    echo ""
    echo "2. Reiniciar container completamente:"
    echo "   docker restart 4sqmet"
    echo "   sleep 5"
    echo "   curl -I https://${DOMAIN}/"
    echo ""
    echo "3. Se ainda não funcionar, verificar configuração SSL:"
    echo "   docker exec 4sqmet cat /var/www/html/apache-config.conf | grep -A 30 'VirtualHost \*:443'"
    echo ""
    echo "4. Habilitar módulo SSL (se não estiver carregado):"
    echo "   docker exec 4sqmet a2enmod ssl"
    echo "   docker exec 4sqmet apachectl graceful"
    echo ""
    echo "5. Verificar se Listen 443 está presente:"
    echo "   docker exec 4sqmet grep -r 'Listen 443' /etc/apache2/"
    echo ""
fi

print_header "🎯 Fim do Diagnóstico"
