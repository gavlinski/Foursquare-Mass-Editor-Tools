#!/bin/bash

#############################################
# Setup SSL Let's Encrypt - Produção
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

# Verificar se é root
if [ "$EUID" -ne 0 ]; then 
    print_error "Este script deve ser executado como root"
    echo "Use: sudo bash setup-ssl-production.sh"
    exit 1
fi

print_header "🔐 Setup SSL Let's Encrypt - Produção"

# Parâmetros
DOMAIN="${1:-4sq.eliotools.site}"
EMAIL="${2}"
WEBROOT_PATH="/var/www/4sqmet"

# Validações
if [ -z "$EMAIL" ]; then
    print_warning "Email não fornecido. Será solicitado."
    read -p "📧 Digite seu email para notificações SSL: " EMAIL
    
    if [ -z "$EMAIL" ]; then
        print_error "Email é obrigatório para Let's Encrypt"
        exit 1
    fi
fi

echo -e "\n${CYAN}Parâmetros:${NC}"
echo "  • Domínio: ${DOMAIN}"
echo "  • Email: ${EMAIL}"
echo "  • Webroot: ${WEBROOT_PATH}"
echo ""
read -p "Continuar com esses valores? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_warning "Setup cancelado pelo usuário"
    exit 0
fi

#############################################
# Verificar Pré-requisitos
#############################################
print_header "📋 Verificando Pré-requisitos"

# 1. Verificar DNS
print_info "Verificando resolução DNS para ${DOMAIN}..."
DOMAIN_IP=$(dig +short ${DOMAIN} | tail -1)
SERVER_IP=$(curl -s ifconfig.me)

if [ -z "$DOMAIN_IP" ]; then
    print_error "Domínio ${DOMAIN} não resolve para nenhum IP"
    echo "Configure o DNS antes de continuar."
    exit 1
fi

if [ "$DOMAIN_IP" != "$SERVER_IP" ]; then
    print_warning "Domínio aponta para: ${DOMAIN_IP}"
    print_warning "Servidor IP atual: ${SERVER_IP}"
    echo ""
    echo "O DNS não aponta para este servidor. Certbot poderá falhar."
    read -p "Continuar mesmo assim? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 0
    fi
else
    print_success "DNS configurado corretamente: ${DOMAIN} → ${SERVER_IP}"
fi

# 2. Verificar porta 80 acessível
print_info "Verificando porta 80 (HTTP)..."
if netstat -tuln | grep -q ':80 '; then
    print_success "Porta 80 está em uso (Apache/Docker rodando)"
else
    print_warning "Porta 80 não está em uso. Apache pode não estar rodando."
fi

# 3. Verificar firewall
print_info "Verificando firewall..."
if ufw status | grep -q 'Status: active'; then
    if ufw status | grep -q '80/tcp'; then
        print_success "Firewall: Porta 80 aberta"
    else
        print_warning "Firewall ativo mas porta 80 não está aberta"
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
    print_info "Firewall não está ativo"
fi

#############################################
# Instalar Certbot
#############################################
print_header "📦 Instalando Certbot"

if command -v certbot &> /dev/null; then
    print_success "Certbot já instalado: $(certbot --version | head -1)"
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

print_info "Verificando se container está rodando..."
if docker ps | grep -q 4sqmet; then
    print_warning "Container 4sqmet está rodando. Será parado temporariamente."
    docker stop 4sqmet
    print_success "Container parado"
    CONTAINER_WAS_RUNNING=true
else
    print_info "Container não está rodando"
    CONTAINER_WAS_RUNNING=false
fi

#############################################
# Obter Certificado Let's Encrypt
#############################################
print_header "🔐 Obtendo Certificado SSL"

print_info "Solicitando certificado para ${DOMAIN}..."
echo ""
echo -e "${YELLOW}⚠️  O Certbot irá validar o domínio via HTTP (porta 80).${NC}"
echo -e "${YELLOW}   Certifique-se de que o DNS está apontando corretamente.${NC}"
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
        echo "Possíveis causas:"
        echo "  • DNS não aponta para este servidor"
        echo "  • Porta 80 bloqueada por firewall"
        echo "  • Rate limit do Let's Encrypt (5 cert/semana por domínio)"
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
    
    # Exibir informações do certificado
    print_info "Informações do certificado:"
    openssl x509 -in "${CERT_PATH}/fullchain.pem" -noout -dates
else
    print_error "Certificados n\u00e3o encontrados em ${CERT_PATH}"
    exit 1
fi

#############################################
# Criar Symlinks para Apache (dentro do Docker)
#############################################
print_header "🔗 Configurando Certificados para Apache"

# Path que o Apache espera dentro do container
SSL_DIR="/etc/ssl/4sqmet"

print_info "Criando diretório de certificados: ${SSL_DIR}"
mkdir -p "${SSL_DIR}"

print_info "Criando symlinks dos certificados..."
ln -sf "${CERT_PATH}/fullchain.pem" "${SSL_DIR}/fullchain.pem"
ln -sf "${CERT_PATH}/privkey.pem" "${SSL_DIR}/privkey.pem"
ln -sf "${CERT_PATH}/chain.pem" "${SSL_DIR}/chain.pem"

if [ -L "${SSL_DIR}/fullchain.pem" ] && [ -L "${SSL_DIR}/privkey.pem" ]; then
    print_success "Symlinks criados com sucesso"
    ls -lh "${SSL_DIR}/"
else
    print_error "Falha ao criar symlinks"
    exit 1
fi

# Também criar backup path (por compatibilidade)
SSL_BACKUP_PATH="/var/www/4sqmet/ssl/production"
print_info "Criando symlink de backup: ${SSL_BACKUP_PATH}"
mkdir -p /var/www/4sqmet/ssl
ln -sf "${CERT_PATH}" "${SSL_BACKUP_PATH}"

#############################################
# Atualizar Apache Config (se necessário)
#############################################
print_header "⚙️  Habilitando Configuração HTTPS no Apache"

APACHE_CONFIG_PROD="/var/www/4sqmet/apache-config-production.conf"
APACHE_CONFIG="/var/www/4sqmet/apache-config.conf"

if [ -f "$APACHE_CONFIG_PROD" ]; then
    print_info "Verificando configuração Apache de produção..."
    
    # Verificar se HTTPS está comentado (desabilitado)
    if grep -q "^# <VirtualHost \*:443>" "$APACHE_CONFIG_PROD"; then
        print_warning "HTTPS está comentado no apache-config-production.conf"
        print_info "Removendo comentários da seção HTTPS..."
        
        # Descomentar todas as linhas entre # <VirtualHost *:443> e # </VirtualHost>
        # Usando sed para remover '#' no início de linhas da seção VirtualHost :443
        sed -i.bak '/^# <VirtualHost \*:443>/,/^# <\/VirtualHost>/ s/^# //' "$APACHE_CONFIG_PROD"
        
        print_success "Seção HTTPS habilitada em apache-config-production.conf"
    else
        print_success "Configuração HTTPS já está habilitada"
    fi
    
    # Verificar se caminhos dos certificados estão corretos
    if grep -q "/etc/ssl/4sqmet/fullchain.pem" "$APACHE_CONFIG_PROD"; then
        print_success "Caminhos dos certificados estão corretos"
    else
        print_warning "Caminhos dos certificados podem precisar de ajuste manual"
        print_info "Esperado: /etc/ssl/4sqmet/fullchain.pem e /etc/ssl/4sqmet/privkey.pem"
    fi
    
    # Copiar configuração de produção para a configuração ativa
    print_info "Ativando configuração de produção..."
    cp "$APACHE_CONFIG_PROD" "$APACHE_CONFIG"
    print_success "apache-config.conf atualizado com configuração de produção"
else
    print_warning "Arquivo apache-config-production.conf não encontrado"
    echo "Verifique se a configuração SSL está presente em apache-config.conf"
fi

#############################################
# Configurar Auto-Renewal
#############################################
print_header "🔄 Configurando Renovação Automática"

# Habilitar timer do certbot
systemctl enable certbot.timer
systemctl start certbot.timer

print_success "Timer de renovação habilitado"
systemctl status certbot.timer --no-pager | grep -E "(Active|Trigger)"

# Criar hooks de renovação para gerenciar o container Docker
# (certbot standalone precisa da porta 80 livre — o Docker-proxy a ocupa)
HOOKS_SRC="/var/www/4sqmet/scripts/certbot-hooks"
HOOKS_DEST="/etc/letsencrypt/renewal-hooks"
mkdir -p "${HOOKS_DEST}/pre" "${HOOKS_DEST}/deploy" "${HOOKS_DEST}/post"

if [ -d "$HOOKS_SRC" ]; then
    # Copiar hooks versionados do repositório
    for dir in pre deploy post; do
        for script in "${HOOKS_SRC}/${dir}/"*.sh; do
            [ -f "$script" ] || continue
            cp "$script" "${HOOKS_DEST}/${dir}/"
            chmod +x "${HOOKS_DEST}/${dir}/$(basename "$script")"
        done
    done
    print_success "Hooks de renovação instalados de scripts/certbot-hooks/"
else
    # Fallback: criar inline (caso o repo não esteja disponível)
    print_warning "scripts/certbot-hooks/ não encontrado, criando hooks inline..."

    cat > "${HOOKS_DEST}/pre/01-stop-docker.sh" <<'HOOKEOF'
#!/bin/bash
echo "⏸️  Parando container Docker para renovação SSL..."
docker stop 4sqmet
echo "✅ Container parado"
HOOKEOF
    chmod +x "${HOOKS_DEST}/pre/01-stop-docker.sh"

    cat > "${HOOKS_DEST}/deploy/restart-docker.sh" <<'HOOKEOF'
#!/bin/bash
echo "🔄 Certificado renovado. Reiniciando container Docker..."
docker restart 4sqmet 2>/dev/null || echo "Container não estava rodando"
echo "✅ Container reiniciado"
HOOKEOF
    chmod +x "${HOOKS_DEST}/deploy/restart-docker.sh"

    cat > "${HOOKS_DEST}/post/01-start-docker.sh" <<'HOOKEOF'
#!/bin/bash
echo "▶️  Iniciando container Docker após renovação SSL..."
docker start 4sqmet 2>/dev/null || true
echo "✅ Container iniciado"
HOOKEOF
    chmod +x "${HOOKS_DEST}/post/01-start-docker.sh"
fi

print_success "Hooks configurados em ${HOOKS_DEST}:"
ls -la "${HOOKS_DEST}/pre/" "${HOOKS_DEST}/deploy/" "${HOOKS_DEST}/post/"

# Testar renovação (dry-run)
print_info "Testando processo de renovação (dry-run)..."
if certbot renew --dry-run --quiet; then
    print_success "Teste de renovação passou!"
else
    print_warning "Teste de renovação falhou. Verificar logs."
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
        
        # Recarregar Apache dentro do container para ler nova configuração
        print_info "Recarregando Apache com configuração HTTPS..."
        docker exec 4sqmet apachectl graceful || docker exec 4sqmet apache2ctl graceful || print_warning "Tentativa de reload Apache falhou (pode não ser necessário)"
    else
        print_error "Falha ao reiniciar container. Verificar logs:"
        docker logs --tail 50 4sqmet
        exit 1
    fi
else
    print_info "Container não estava rodando anteriormente. Iniciando..."
    cd /var/www/4sqmet
    
    docker start 4sqmet 2>/dev/null || {
        print_warning "Container n\u00e3o existe. Criando novo..."
        docker run -d \
            --name 4sqmet \
            --restart unless-stopped \
            -p 80:80 \
            -p 443:443 \
            -v $(pwd):/var/www/html \
            -v "/etc/ssl/4sqmet:/etc/ssl/4sqmet:ro" \
            -v "${CERT_PATH}:/etc/letsencrypt/live/${DOMAIN}:ro" \
            4sqmet:latest
    }
    
    sleep 3
    
    if docker ps | grep -q 4sqmet; then
        print_success "Container iniciado com sucesso!"
        
        # Recarregar Apache dentro do container para ler nova configuração
        print_info "Recarregando Apache com configuração HTTPS..."
        docker exec 4sqmet apachectl graceful || docker exec 4sqmet apache2ctl graceful || print_warning "Tentativa de reload Apache falhou (pode não ser necessário)"
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
    print_warning "HTTPS pode não estar funcionando corretamente (status: ${HTTPS_STATUS})"
fi

# Verificar certificado
print_info "Verificando validade do certificado..."
if echo | openssl s_client -connect ${DOMAIN}:443 -servername ${DOMAIN} 2>/dev/null | grep -q "Verify return code: 0"; then
    print_success "Certificado SSL válido!"
else
    print_warning "Certificado pode ter problemas de validação"
fi

#############################################
# FINALIZAÇÃO
#############################################
print_header "🎉 Setup SSL Concluído!"

echo -e "\n${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ HTTPS configurado com sucesso!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "📊 Resumo:"
echo "   • Domínio: ${DOMAIN}"
echo "   • Certificado: ${CERT_PATH}"
echo "   • Symlinks Apache: ${SSL_DIR}/"
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
echo "   • Próxima renovação: systemctl list-timers certbot.timer"
echo ""
echo "📝 Logs:"
echo "   • Certbot: /var/log/letsencrypt/"
echo "   • Container: docker logs 4sqmet"
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

# Salvar informações
cat > /root/4sqmet-ssl-info.txt <<EOF
================================================
Foursquare Mass Editor Tools - SSL Info
================================================

Data Setup: $(date)
Domínio: ${DOMAIN}
Email: ${EMAIL}

Certificados:
- Localização: ${CERT_PATH}
- Symlinks Apache: ${SSL_DIR}/
  - fullchain.pem → ${CERT_PATH}/fullchain.pem
  - privkey.pem → ${CERT_PATH}/privkey.pem
  - chain.pem → ${CERT_PATH}/chain.pem
- Backup: ${SSL_BACKUP_PATH} → ${CERT_PATH}
- Validade: $(openssl x509 -in "${CERT_PATH}/fullchain.pem" -noout -enddate)

Renovação:
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

Configuração Apache:
- Config ativo: /var/www/4sqmet/apache-config.conf
- Config produção: /var/www/4sqmet/apache-config-production.conf

================================================
EOF

print_success "Informa\u00e7\u00f5es salvas em: /root/4sqmet-ssl-info.txt"

exit 0
