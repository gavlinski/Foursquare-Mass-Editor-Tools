#!/bin/bash
###############################################################################
# DevContainer Setup Script
# Rodado automaticamente após criação/rebuild do container (postCreateCommand)
# Configura o ambiente de desenvolvimento dentro do container
###############################################################################

set -e

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║   Foursquare Mass Editor Tools - DevContainer Setup       ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

cd /var/www/html

# ── 1. .env ──────────────────────────────────────────────────────────────────
if [ ! -f .env ]; then
    echo -e "${YELLOW}📝 Criando .env a partir do .env.example...${NC}"
    cp .env.example .env
    echo -e "${YELLOW}   ⚠️  Edite .env com suas credenciais antes de usar o app.${NC}"
else
    echo -e "${GREEN}✅ .env já existe${NC}"
fi

# ── 2. Composer ──────────────────────────────────────────────────────────────
echo -e "\n${CYAN}📦 Instalando dependências do Composer (incluindo dev)...${NC}"
composer install --optimize-autoloader --no-interaction
echo -e "${GREEN}✅ Composer: OK${NC}"

# ── 3. Permissões para pastas graváveis ──────────────────────────────────────
# Apache (www-data) precisa escrever em data/ e no diretório de sessões PHP
echo -e "\n${CYAN}🔐 Ajustando permissões de pastas graváveis...${NC}"

for DIR in data; do
    if [ -d "$DIR" ]; then
        chmod -R 775 "$DIR"
        chown -R root:www-data "$DIR"
        echo -e "   ✅ $DIR/ → root:www-data 775"
    fi
done

# Diretório de sessões PHP
PHP_SESSION_DIR=$(php -r "echo ini_get('session.save_path');" 2>/dev/null || echo "/tmp")
if [ -n "$PHP_SESSION_DIR" ] && [ "$PHP_SESSION_DIR" != "/tmp" ] && [ ! -d "$PHP_SESSION_DIR" ]; then
    mkdir -p "$PHP_SESSION_DIR"
    chmod 770 "$PHP_SESSION_DIR"
    chown root:www-data "$PHP_SESSION_DIR"
    echo -e "   ✅ session dir: $PHP_SESSION_DIR → root:www-data 770"
fi

# ── 4. Garante que o Apache está rodando ─────────────────────────────────────
echo -e "\n${CYAN}🌐 Verificando Apache...${NC}"
if apache2ctl status > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Apache: rodando${NC}"
else
    echo -e "${YELLOW}⚠️  Apache aparenta estar parado. Iniciando...${NC}"
    apache2ctl start 2>/dev/null || true
fi

# ── Resumo ───────────────────────────────────────────────────────────────────
echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}🚀 DevContainer pronto!${NC}"
echo -e ""
echo -e "   🌐 App:   ${CYAN}https://localhost/4sqmet/${NC}"
echo -e "   🐛 Debug: ${CYAN}https://localhost/4sqmet/debug/${NC}"
echo -e "   🔧 Build: ${CYAN}./build.sh${NC}  (npm disponível, sem Docker necessário)"
echo -e "   🛠  Mgmt:  ${CYAN}./scripts/dev-internal.sh <status|reload|logs|build>${NC}"
echo -e ""
echo -e "   ⚠️  Edite ${CYAN}.env${NC} com suas credenciais API se ainda não o fez."
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
