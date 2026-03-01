#!/bin/bash

#############################################
# Setup Automatizado - Droplet DigitalOcean
# Projeto: Foursquare Mass Editor Tools v3
#############################################

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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
    echo "Use: sudo bash setup-droplet.sh"
    exit 1
fi

print_header "🚀 Setup Automatizado - Foursquare Mass Editor Tools"

# Confirmar início
echo -e "Este script irá:"
echo "  1. Atualizar sistema Ubuntu"
echo "  2. Configurar firewall (UFW)"
echo "  3. Instalar Certbot para SSL"
echo "  4. Criar estrutura de diretórios"
echo "  5. Clonar repositório do GitHub"
echo "  6. Configurar ambiente (.env)"
echo "  7. Build imagem Docker"
echo "  8. Iniciar aplicação"
echo ""
read -p "Continuar? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_warning "Setup cancelado pelo usuário"
    exit 0
fi

#############################################
# FASE 1: Atualizar Sistema
#############################################
print_header "📦 Fase 1/8: Atualizando Sistema"

print_info "Atualizando lista de pacotes..."
apt update -qq

print_info "Instalando atualizações de segurança..."
apt upgrade -y -qq

print_info "Instalando pacotes essenciais..."
apt install -y -qq \
    curl \
    wget \
    git \
    unzip \
    vim \
    htop \
    net-tools \
    ca-certificates \
    gnupg \
    lsb-release

print_success "Sistema atualizado!"

# Verificar se reboot é necessário (atualização de kernel)
if [ -f /var/run/reboot-required ]; then
    print_warning "⚠️  REBOOT NECESSÁRIO!"
    echo ""
    echo -e "${YELLOW}O kernel do sistema foi atualizado e requer reinicialização.${NC}"
    echo -e "${YELLOW}Continuar sem reboot pode causar avisos de dessincronia de versões.${NC}"
    echo ""
    echo "Opções:"
    echo "  1) Reboot agora e re-executar este script após reiniciar"
    echo "  2) Continuar sem reboot (não recomendado)"
    echo ""
    read -p "Escolha (1/2): " -n 1 -r REBOOT_CHOICE
    echo ""
    
    if [[ $REBOOT_CHOICE == "1" ]]; then
        print_info "Salvando progresso e agendando reboot..."
        echo "#!/bin/bash" > /root/continue-4sqmet-setup.sh
        echo "cd /tmp" >> /root/continue-4sqmet-setup.sh
        echo "bash setup-droplet.sh" >> /root/continue-4sqmet-setup.sh
        chmod +x /root/continue-4sqmet-setup.sh
        
        print_success "Script de continuação criado em: /root/continue-4sqmet-setup.sh"
        echo ""
        echo -e "${BLUE}Após o reboot, execute:${NC}"
        echo -e "  ${GREEN}bash /root/continue-4sqmet-setup.sh${NC}"
        echo ""
        print_info "Reiniciando em 5 segundos..."
        sleep 5
        reboot
        exit 0
    else
        print_warning "Continuando sem reboot. Monitorar avisos de kernel."
    fi
fi

#############################################
# FASE 2: Verificar Docker
#############################################
print_header "🐳 Fase 2/8: Verificando Docker"

if command -v docker &> /dev/null; then
    DOCKER_VERSION=$(docker --version | awk '{print $3}' | sed 's/,//')
    print_success "Docker já instalado: ${DOCKER_VERSION}"
else
    print_warning "Docker não encontrado. Instalando..."
    
    # Instalar Docker
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    rm get-docker.sh
    
    # Instalar Docker Compose Plugin
    apt install -y docker-compose-plugin
    
    print_success "Docker instalado: $(docker --version)"
fi

# Verificar Docker Compose
if docker compose version &> /dev/null; then
    COMPOSE_VERSION=$(docker compose version --short)
    print_success "Docker Compose disponível: ${COMPOSE_VERSION}"
else
    print_error "Docker Compose não disponível"
    exit 1
fi

# Iniciar e habilitar Docker
systemctl start docker
systemctl enable docker
print_success "Docker service habilitado"

#############################################
# FASE 3: Configurar Firewall
#############################################
print_header "🔥 Fase 3/8: Configurando Firewall (UFW)"

# Desabilitar temporariamente para reconfigurar
ufw --force disable

# Configurar regras
ufw default deny incoming
ufw default allow outgoing
ufw allow OpenSSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS

# Habilitar UFW
ufw --force enable

print_success "Firewall configurado!"
ufw status verbose

#############################################
# FASE 4: Instalar Certbot
#############################################
print_header "🔐 Fase 4/8: Instalando Certbot"

if command -v certbot &> /dev/null; then
    print_success "Certbot já instalado: $(certbot --version | head -1)"
else
    apt install -y certbot python3-certbot-apache
    print_success "Certbot instalado!"
fi

# Configurar auto-renewal
systemctl enable certbot.timer
print_success "Auto-renewal habilitado"

#############################################
# FASE 5: Criar Estrutura de Diretórios
#############################################
print_header "📁 Fase 5/8: Criando Estrutura de Diretórios"

# Criar diretórios
mkdir -p /var/www/4sqmet
mkdir -p /var/backups/4sqmet
mkdir -p /var/log/4sqmet
mkdir -p /etc/4sqmet

# Definir permissões
chmod 755 /var/www/4sqmet
chmod 755 /var/backups/4sqmet
chmod 755 /var/log/4sqmet

print_success "Estrutura de diretórios criada"
ls -la /var/www/

#############################################
# FASE 6: Clonar Repositório
#############################################
print_header "🔄 Fase 6/8: Clonando Repositório"

cd /var/www/4sqmet

if [ -d ".git" ]; then
    print_warning "Repositório já existe. Atualizando..."
    git fetch origin
    git reset --hard origin/refactor-ia
    git pull origin refactor-ia
else
    print_info "Clonando repositório..."
    
    # Perguntar branch
    read -p "Branch para clonar [refactor-ia]: " BRANCH
    BRANCH=${BRANCH:-refactor-ia}
    
    git clone -b ${BRANCH} https://github.com/gavlinski/Foursquare-Mass-Editor-Tools.git .
fi

print_success "Repositório clonado/atualizado!"
git log -1 --oneline

#############################################
# FASE 7: Configurar Ambiente (.env)
#############################################
print_header "⚙️  Fase 7/8: Configurando Ambiente"

if [ ! -f ".env" ]; then
    print_info "Criando arquivo .env placeholder..."
    cp .env.example .env
    
    echo -e "\n${YELLOW}✨ O arquivo .env foi criado com placeholders.${NC}"
    echo -e "${BLUE}ℹ️  As credenciais reais serão injetadas automaticamente via GitHub Actions${NC}"
    echo -e "${BLUE}   no primeiro deploy, a partir dos secrets configurados.${NC}"
    echo ""
    echo -e "${CYAN}Para testar manualmente antes do deploy automático:${NC}"
    echo "  1. Configurar secrets no GitHub (ver docs/deployment/CICD_SETUP.md)"
    echo "  2. Ou editar .env manualmente: vim /var/www/4sqmet/.env"
    echo ""
    
    print_success ".env placeholder criado"
else
    print_success "Arquivo .env já existe"
fi

#############################################
# FASE 8: Build e Deploy Docker
#############################################
print_header "🐳 Fase 8/8: Build e Deploy Docker"

print_info "Verificando conflitos de porta..."

# Parar containers existentes
docker stop 4sqmet 2>/dev/null || true
docker rm 4sqmet 2>/dev/null || true

# Verificar se algo está usando porta 80/443
if netstat -tuln | grep -q ':80 '; then
    print_warning "Porta 80 já está em uso. Verificando serviço..."
    
    # Verificar se é Apache do host
    if systemctl is-active --quiet apache2; then
        print_info "Apache2 do sistema detectado. Parando e desabilitando..."
        systemctl stop apache2
        systemctl disable apache2
        print_success "Apache2 do sistema desabilitado"
    fi
    
    # Verificar se ainda está em uso
    if netstat -tuln | grep -q ':80 '; then
        print_error "Porta 80 ainda está em uso por outro serviço"
        echo "Serviços usando porta 80:"
        netstat -tulnp | grep ':80 '
        echo ""
        echo "Execute: sudo lsof -i :80 para identificar o processo"
        exit 1
    fi
fi

print_success "Portas 80/443 disponíveis"

print_info "Building imagem Docker (ambiente de produção)..."
docker build --build-arg BUILD_ENV=production -t 4sqmet:latest .

print_info "Iniciando container..."
docker run -d \
    --name 4sqmet \
    --restart unless-stopped \
    -p 80:80 \
    -p 443:443 \
    -v $(pwd):/var/www/html \
    -v $(pwd)/ssl:/etc/ssl/4sqmet \
    4sqmet:latest

# Aguardar container iniciar
sleep 5

# Verificar status
if docker ps | grep -q 4sqmet; then
    print_success "Container iniciado com sucesso!"
    docker ps --filter name=4sqmet --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
else
    print_error "Falha ao iniciar container"
    echo "Logs:"
    docker logs 4sqmet
    exit 1
fi

#############################################
# FINALIZAÇÃO
#############################################
print_header "✅ Setup Concluído!"

# Obter IP
SERVER_IP=$(curl -s ifconfig.me)

echo -e "\n${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}🎉 Servidor configurado com sucesso!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "📊 Informações do Servidor:"
echo "   • IP Público: ${SERVER_IP}"
echo "   • Aplicação: /var/www/4sqmet"
echo "   • Backups: /var/backups/4sqmet"
echo "   • Logs: /var/log/4sqmet"
echo ""
echo "🌐 URLs de Acesso:"
echo "   • HTTP:  http://${SERVER_IP}/"
echo "   • HTTPS: https://4sq.eliotools.site (após DNS + SSL)"
echo ""
echo "🔐 Próximos Passos:"
echo ""
echo "1️⃣  Testar aplicação:"
echo "   curl -I http://${SERVER_IP}/"
echo ""
echo "2️⃣  Configurar DNS (Namecheap):"
echo "   • Tipo: A Record"
echo "   • Host: 4sq (ou @)"
echo "   • Value: ${SERVER_IP}"
echo "   • TTL: Automatic"
echo ""
echo "3️⃣  Configurar SSL (após DNS propagar):"
echo "   bash /tmp/setup-ssl-production.sh 4sq.eliotools.site seu-email@example.com"
echo ""
echo "4️⃣  Configurar GitHub Actions:"
echo "   • Settings → Secrets → Actions"
echo "   • DEPLOY_SSH_KEY: [sua chave privada]"
echo "   • DEPLOY_USER: root"
echo "   • DEPLOY_HOST: ${SERVER_IP}"
echo ""
echo "5️⃣  Testar deploy automático:"
echo "   git push origin refactor-ia"
echo ""
echo "📝 Comandos Úteis:"
echo "   • Ver logs:      docker logs -f 4sqmet"
echo "   • Reiniciar:     docker restart 4sqmet"
echo "   • Status:        docker ps"
echo "   • Firewall:      ufw status"
echo ""
echo "📚 Documentação:"
echo "   • /var/www/4sqmet/docs/deployment/DEPLOY.md"
echo "   • /var/www/4sqmet/docs/deployment/DEPLOYMENT_STRATEGY.md"
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

# Salvar informações em arquivo
cat > /root/4sqmet-setup-info.txt <<EOF
================================================
Foursquare Mass Editor Tools - Setup Info
================================================

Data Setup: $(date)
Servidor IP: ${SERVER_IP}
Branch: $(cd /var/www/4sqmet && git branch --show-current)
Commit: $(cd /var/www/4sqmet && git log -1 --oneline)

Docker Version: $(docker --version)
Container: 4sqmet
Status: $(docker inspect -f '{{.State.Status}}' 4sqmet)

URLs:
- HTTP:  http://${SERVER_IP}/
- HTTPS: https://4sq.eliotools.site (após DNS + SSL)

Diretórios:
- Aplicação: /var/www/4sqmet
- Backups:   /var/backups/4sqmet
- Logs:      /var/log/4sqmet

Próximos Passos:
1. Configurar DNS apontando para ${SERVER_IP}
2. Configurar SSL com Certbot
3. Configurar GitHub Actions secrets
4. Testar deploy automático

================================================
EOF

print_success "Informações salvas em: /root/4sqmet-setup-info.txt"
print_info "Use: cat /root/4sqmet-setup-info.txt"

exit 0
