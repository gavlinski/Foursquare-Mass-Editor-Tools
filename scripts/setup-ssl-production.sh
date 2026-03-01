#!/bin/bash

#############################################
# Setup SSL Let's Encrypt - Produ\u00e7\u00e3o
# Projeto: Foursquare Mass Editor Tools v3
#############################################

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Functions
print_header() {
    echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Verificar se \u00e9 root
if [ "$EUID" -ne 0 ]; then 
    print_error "Este script deve ser executado como root"
    echo "Use: sudo bash setup-ssl-production.sh"
    exit 1
fi

print_header "🔐 Setup SSL Let's Encrypt - Produção"

# Par\u00e2metros
DOMAIN="${1:-4sq.eliotools.site}"
EMAIL="${2}"
WEBROOT_PATH="/var/www/4sqmet"

# Valida\u00e7\u00f5es
if [ -z "$EMAIL" ]; then
    print_warning "Email n\u00e3o fornecido. Ser\u00e1 solicitado."
    read -p "📧 Digite seu email para notifica\u00e7\u00f5es SSL: " EMAIL
    
    if [ -z "$EMAIL" ]; then
        print_error "Email \u00e9 obrigat\u00f3rio para Let's Encrypt"
        exit 1
    fi
fi

echo -e "\n${CYAN}Par\u00e2metros:${NC}"
echo "  • Dom\u00ednio: ${DOMAIN}"
echo "  • Email: ${EMAIL}"
echo "  • Webroot: ${WEBROOT_PATH}"
echo ""
read -p "Continuar com esses valores? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_warning "Setup cancelado pelo usu\u00e1rio"
    exit 0
fi

#############################################
# Verificar Pr\u00e9-requisitos
#############################################
print_header "📋 Verificando Pr\u00e9-requisitos"

# 1. Verificar DNS
print_info "Verificando resolu\u00e7\u00e3o DNS para ${DOMAIN}..."
DOMAIN_IP=$(dig +short ${DOMAIN} | tail -1)
SERVER_IP=$(curl -s ifconfig.me)

if [ -z "$DOMAIN_IP" ]; then
    print_error "Dom\u00ednio ${DOMAIN} n\u00e3o resolve para nenhum IP"
    echo "Configure o DNS antes de continuar."
    exit 1
fi

if [ "$DOMAIN_IP" != "$SERVER_IP" ]; then
    print_warning "Dom\u00ednio aponta para: ${DOMAIN_IP}"
    print_warning "Servidor IP atual: ${SERVER_IP}"
    echo ""
    echo "O DNS n\u00e3o aponta para este servidor. Certbot poder\u00e1 falhar."
    read -p "Continuar mesmo assim? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 0
    fi
else
    print_success "DNS configurado corretamente: ${DOMAIN} → ${SERVER_IP}"
fi

# 2. Verificar porta 80 acess\u00edvel
print_info "Verificando porta 80 (HTTP)..."
if netstat -tuln | grep -q ':80 '; then
    print_success "Porta 80 est\u00e1 em uso (Apache/Docker rodando)"
else
    print_warning "Porta 80 n\u00e3o est\u00e1 em uso. Apache pode n\u00e3o estar rodando."
fi

# 3. Verificar firewall
print_info "Verificando firewall..."
if ufw status | grep -q 'Status: active'; then
    if ufw status | grep -q '80/tcp'; then
        print_success "Firewall: Porta 80 aberta"
    else
        print_warning "Firewall ativo mas porta 80 n\u00e3o est\u00e1 aberta"
        print_info "Abrindo porta 80..."
        ufw allow 80/tcp
    fi
    
    if ufw status | grep -q '443/tcp'; then
        print_success "Firewall: Porta 443 aberta"
    else
        print_warning "Abrindo porta 443..."
        ufw allow 443/tcp
    fi
else
    print_info "Firewall n\u00e3o est\u00e1 ativo"
fi

#############################################
# Instalar Certbot
#############################################
print_header "📦 Instalando Certbot"

if command -v certbot &> /dev/null; then
    print_success "Certbot j\u00e1 instalado: $(certbot --version | head -1)"
else
    print_info "Instalando Certbot..."
    apt update -qq
    apt install -y certbot
    print_success "Certbot instalado!"
fi

#############################################
# Parar Container Docker (liberar porta 80)
#############################################
print_header "🐳 Preparando Container Docker"

print_info "Verificando se container est\u00e1 rodando..."
if docker ps | grep -q 4sqmet; then
    print_warning "Container 4sqmet est\u00e1 rodando. Ser\u00e1 parado temporariamente."
    docker stop 4sqmet
    print_success "Container parado"
    CONTAINER_WAS_RUNNING=true
else
    print_info "Container n\u00e3o est\u00e1 rodando"
    CONTAINER_WAS_RUNNING=false
fi

#############################################
# Obter Certificado Let's Encrypt
#############################################
print_header "🔐 Obtendo Certificado SSL"

print_info "Solicitando certificado para ${DOMAIN}..."
echo ""
echo -e "${YELLOW}⚠️  O Certbot ir\u00e1 validar o dom\u00ednio via HTTP (porta 80).${NC}"
echo -e "${YELLOW}   Certifique-se de que o DNS est\u00e1 apontando corretamente.${NC}"
echo ""

certbot certonly \
    --standalone \
    --preferred-challenges http \
    --agree-tos \
    --no-eff-email \
    --email "${EMAIL}" \
    -d "${DOMAIN}" \
    --non-interactive || {
        print_error "Falha ao obter certificado SSL"
        echo ""
        echo "Poss\u00edveis causas:"
        echo "  • DNS n\u00e3o aponta para este servidor"
        echo "  • Porta 80 bloqueada por firewall"
        echo "  • Rate limit do Let's Encrypt (5 cert/semana por dom\u00ednio)"
        echo ""
        echo "Para testar sem obter certificado real, use:"
        echo "  certbot certonly --standalone --dry-run -d ${DOMAIN}"
        exit 1
    }

print_success "Certificado SSL obtido com sucesso!"

# Verificar certificados
CERT_PATH="/etc/letsencrypt/live/${DOMAIN}"
if [ -f "${CERT_PATH}/fullchain.pem" ] && [ -f "${CERT_PATH}/privkey.pem" ]; then
    print_success "Certificados encontrados:"
    echo "  • Cert: ${CERT_PATH}/fullchain.pem"
    echo "  • Key:  ${CERT_PATH}/privkey.pem"
    
    # Exibir informa\u00e7\u00f5es do certificado
    print_info "Informa\u00e7\u00f5es do certificado:"
    openssl x509 -in "${CERT_PATH}/fullchain.pem" -noout -dates
else
    print_error "Certificados n\u00e3o encontrados em ${CERT_PATH}"
    exit 1
fi

#############################################
# Criar Symlink para Docker
#############################################
print_header "🔗 Configurando Certificados para Docker"

SSL_LINK_PATH="/var/www/4sqmet/ssl/production"

print_info "Criando symlink: ${SSL_LINK_PATH} → ${CERT_PATH}"
mkdir -p /var/www/4sqmet/ssl
ln -sf "${CERT_PATH}" "${SSL_LINK_PATH}"

if [ -L "${SSL_LINK_PATH}" ]; then
    print_success "Symlink criado com sucesso"
    ls -lh "${SSL_LINK_PATH}"
else
    print_error "Falha ao criar symlink"
    exit 1
fi

#############################################
# Atualizar Apache Config (se necess\u00e1rio)
#############################################
print_header "⚙️  Verificando Configura\u00e7\u00e3o Apache"

APACHE_CONFIG="/var/www/4sqmet/apache-config.conf"

if [ -f "$APACHE_CONFIG" ]; then
    print_info "Verificando caminhos dos certificados em apache-config.conf..."
    
    if grep -q "SSLCertificateFile" "$APACHE_CONFIG"; then
        print_success "Configura\u00e7\u00e3o SSL j\u00e1 presente em Apache"
        
        # Verificar se aponta para o local correto
        if grep -q "/etc/ssl/4sqmet/fullchain.pem" "$APACHE_CONFIG"; then
            print_success "Caminhos dos certificados est\u00e3o corretos"
        else
            print_warning "Caminhos podem precisar de ajuste manual"
        fi
    else
        print_warning "Configura\u00e7\u00e3o SSL n\u00e3o encontrada em apache-config.conf"
        echo "Adicione manualmente as linhas de SSL no VirtualHost :443"
    fi
else
    print_warning "Arquivo apache-config.conf n\u00e3o encontrado"
fi

#############################################
# Configurar Auto-Renewal
#############################################
print_header "🔄 Configurando Renova\u00e7\u00e3o Autom\u00e1tica"

# Habilitar timer do certbot
systemctl enable certbot.timer
systemctl start certbot.timer

print_success "Timer de renova\u00e7\u00e3o habilitado"
systemctl status certbot.timer --no-pager | grep -E "(Active|Trigger)"

# Criar hook de renova\u00e7\u00e3o para reiniciar container
RENEWAL_HOOK="/etc/letsencrypt/renewal-hooks/deploy/restart-docker.sh"
mkdir -p /etc/letsencrypt/renewal-hooks/deploy

cat > "$RENEWAL_HOOK" <<'EOF'
#!/bin/bash
# Hook executado ap\u00f3s renova\u00e7\u00e3o bem-sucedida do certificado
echo "🔄 Certificado renovado. Reiniciando container Docker..."
docker restart 4sqmet 2>/dev/null || echo "Container n\u00e3o estava rodando"
echo "✅ Container reiniciado"
EOF

chmod +x "$RENEWAL_HOOK"
print_success "Hook de renova\u00e7\u00e3o criado: ${RENEWAL_HOOK}"

# Testar renova\u00e7\u00e3o (dry-run)
print_info "Testando processo de renova\u00e7\u00e3o (dry-run)..."
if certbot renew --dry-run --quiet; then
    print_success "Teste de renova\u00e7\u00e3o passou!"
else
    print_warning "Teste de renova\u00e7\u00e3o falhou. Verificar logs."
fi

#############################################
# Reiniciar Container com SSL
#############################################
print_header "🚀 Reiniciando Container com HTTPS"

if [ "$CONTAINER_WAS_RUNNING" = true ]; then
    print_info "Reiniciando container 4sqmet com certificados SSL..."
    
    cd /var/www/4sqmet
    
    docker start 4sqmet
    
    # Aguardar container iniciar
    sleep 3
    
    if docker ps | grep -q 4sqmet; then
        print_success "Container reiniciado com sucesso!"
        docker ps --filter name=4sqmet --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
    else
        print_error "Falha ao reiniciar container. Verificar logs:"
        docker logs --tail 50 4sqmet
        exit 1
    fi
else
    print_info "Container n\u00e3o estava rodando anteriormente. Iniciando..."
    cd /var/www/4sqmet
    
    docker start 4sqmet 2>/dev/null || {
        print_warning "Container n\u00e3o existe. Criando novo..."
        docker run -d \
            --name 4sqmet \
            --restart unless-stopped \
            -p 80:80 \
            -p 443:443 \
            -v $(pwd):/var/www/html \
            -v "${CERT_PATH}:/etc/ssl/4sqmet:ro" \
            4sqmet:latest
    }
    
    sleep 3
    
    if docker ps | grep -q 4sqmet; then
        print_success "Container iniciado com sucesso!"
    else
        print_error "Falha ao iniciar container"
        exit 1
    fi
fi

#############################################
# Validar HTTPS
#############################################
print_header "✅ Validando HTTPS"

print_info "Testando HTTPS..."
sleep 2

# Teste HTTP
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://${DOMAIN}/ || echo "000")
print_info "HTTP Status: ${HTTP_STATUS}"

# Teste HTTPS
HTTPS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://${DOMAIN}/ || echo "000")
print_info "HTTPS Status: ${HTTPS_STATUS}"

if [ "$HTTPS_STATUS" = "200" ] || [ "$HTTPS_STATUS" = "302" ]; then
    print_success "HTTPS funcionando!"
else
    print_warning "HTTPS pode n\u00e3o estar funcionando corretamente (status: ${HTTPS_STATUS})"
fi

# Verificar certificado
print_info "Verificando validade do certificado..."
if echo | openssl s_client -connect ${DOMAIN}:443 -servername ${DOMAIN} 2>/dev/null | grep -q "Verify return code: 0"; then
    print_success "Certificado SSL v\u00e1lido!"
else
    print_warning "Certificado pode ter problemas de valida\u00e7\u00e3o"
fi

#############################################
# FINALIZA\u00c7\u00c3O
#############################################
print_header "🎉 Setup SSL Conclu\u00eddo!"

echo -e "\n${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ HTTPS configurado com sucesso!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "📊 Resumo:"
echo "   • Dom\u00ednio: ${DOMAIN}"
echo "   • Certificado: ${CERT_PATH}"
echo "   • Validade: 90 dias (renova automaticamente a cada 60 dias)"
echo "   • Container: 4sqmet (rodando)"
echo ""
echo "🌐 URLs:"
echo "   • HTTPS: https://${DOMAIN}/"
echo "   • HTTP:  http://${DOMAIN}/ (redireciona para HTTPS)"
echo ""
echo "🔍 Verificar:"
echo "   • SSL Labs: https://www.ssllabs.com/ssltest/analyze.html?d=${DOMAIN}"
echo "   • Security Headers: https://securityheaders.com/?q=${DOMAIN}"
echo ""
echo "🔄 Gerenciar Certificados:"
echo "   • Listar: certbot certificates"
echo "   • Renovar: certbot renew"
echo "   • Status auto-renewal: systemctl status certbot.timer"
echo "   • Pr\u00f3xima renova\u00e7\u00e3o: systemctl list-timers certbot.timer"
echo ""
echo "📝 Logs:"
echo "   • Certbot: /var/log/letsencrypt/"
echo "   • Container: docker logs 4sqmet"
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

# Salvar informa\u00e7\u00f5es
cat > /root/4sqmet-ssl-info.txt <<EOF
================================================
Foursquare Mass Editor Tools - SSL Info
================================================

Data Setup: $(date)
Dom\u00ednio: ${DOMAIN}
Email: ${EMAIL}

Certificados:
- Localiza\u00e7\u00e3o: ${CERT_PATH}
- Symlink Docker: ${SSL_LINK_PATH}
- Validade: $(openssl x509 -in "${CERT_PATH}/fullchain.pem" -noout -enddate)

Renova\u00e7\u00e3o:
- Auto-renewal: Habilitado (certbot.timer)
- Frequência: A cada 60 dias
- Hook: ${RENEWAL_HOOK}

Testes:
- HTTP Status: ${HTTP_STATUS}
- HTTPS Status: ${HTTPS_STATUS}

URLs:
- HTTPS: https://${DOMAIN}/
- HTTP:  http://${DOMAIN}/ (redireciona para HTTPS)
- SSL Labs: https://www.ssllabs.com/ssltest/analyze.html?d=${DOMAIN}

================================================
EOF

print_success "Informa\u00e7\u00f5es salvas em: /root/4sqmet-ssl-info.txt"

exit 0
