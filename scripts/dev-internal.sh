#!/bin/bash
###############################################################################
# dev-internal.sh — Gerenciamento do ambiente DevContainer
#
# Substitui os comandos Docker do dev.sh quando executado DENTRO do container.
# Uso: ./scripts/dev-internal.sh <comando>
#
# Comandos disponíveis:
#   status   — Status do Apache e portas
#   reload   — Recarrega config do Apache (graceful restart)
#   restart  — Para e reinicia o Apache
#   logs     — Saída ao vivo dos logs do Apache
#   build    — Minifica JS e gera build-info.json
#   dojo     — Gerencia Dojo Toolkit local (download / status)
###############################################################################

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Garante execução dentro do container
if [ -z "$DEVCONTAINER" ] && [ ! -f /.dockerenv ]; then
    echo -e "${RED}❌ Este script é para uso DENTRO do DevContainer.${NC}"
    echo -e "   Para desenvolvimento externo (OrbStack/Docker), use ${CYAN}./dev.sh${NC}"
    exit 1
fi

# Garante que está no diretório raiz do projeto
cd /var/www/html

# ── Funções ───────────────────────────────────────────────────────────────────

cmd_status() {
    echo -e "${BLUE}📊 Status do DevContainer${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    # Apache
    if apache2ctl status > /dev/null 2>&1; then
        APACHE_PIDS=$(pgrep apache2 2>/dev/null | wc -l | tr -d ' ')
        echo -e "${GREEN}✅ Apache: rodando${NC} (${APACHE_PIDS} processos)"
    else
        echo -e "${RED}❌ Apache: parado${NC}"
    fi

    # PHP
    PHP_VERSION=$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo "não encontrado")
    echo -e "${GREEN}✅ PHP: ${PHP_VERSION}${NC}"

    # Node/npm
    NODE_VERSION=$(node --version 2>/dev/null || echo "não encontrado")
    NPM_VERSION=$(npm --version 2>/dev/null || echo "não encontrado")
    echo -e "${GREEN}✅ Node: ${NODE_VERSION} / npm: ${NPM_VERSION}${NC}"

    # Portas — usa /proc/net/tcp* pois ss/netstat podem não estar presentes
    # Porta 80 em hex = 0050, porta 443 = 01BB
    echo ""
    echo -e "${CYAN}🔗 Portas:${NC}"
    if grep -qE ':(0050|00050) ' /proc/net/tcp /proc/net/tcp6 2>/dev/null || \
       curl -s http://localhost/ --max-time 2 -o /dev/null 2>/dev/null; then
        echo -e "   ${GREEN}✅ :80  HTTP${NC}"
    else
        echo -e "   ${RED}❌ :80  HTTP — não está escutando${NC}"
    fi
    if grep -qE ':(01BB|001BB) ' /proc/net/tcp /proc/net/tcp6 2>/dev/null || \
       curl -sk https://localhost/ --max-time 2 -o /dev/null 2>/dev/null; then
        echo -e "   ${GREEN}✅ :443 HTTPS${NC}"
    else
        echo -e "   ${RED}❌ :443 HTTPS — não está escutando${NC}"
    fi

    # Dojo
    echo ""
    echo -e "${CYAN}📦 Dojo Toolkit:${NC}"
    DOJO_SOURCE=$(grep "^DOJO_SOURCE=" .env 2>/dev/null | cut -d'=' -f2 || echo "cdn")
    if [ "$DOJO_SOURCE" = "local" ]; then
        if [ -d "js/dojo" ] && [ -d "js/dijit" ] && [ -d "js/dojox" ]; then
            echo -e "   ${GREEN}✅ Local (js/dojo/, js/dijit/, js/dojox/)${NC}"
        else
            echo -e "   ${RED}❌ Configurado como local, mas arquivos ausentes${NC}"
            echo -e "      Execute: ${CYAN}./scripts/dev-internal.sh dojo${NC}"
        fi
    else
        echo -e "   ${CYAN}☁️  CDN (Google Ajax CDN)${NC}"
    fi

    # URLs
    echo ""
    echo -e "${CYAN}🌐 URLs:${NC}"
    echo -e "   • App:   https://localhost/4sqmet/"
    echo -e "   • Debug: https://localhost/4sqmet/debug/"

    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

cmd_reload() {
    echo -e "${CYAN}🔄 Recarregando Apache (graceful)...${NC}"
    if apache2ctl configtest 2>&1 | grep -q "Syntax OK"; then
        apache2ctl graceful
        echo -e "${GREEN}✅ Apache recarregado com sucesso!${NC}"
    else
        echo -e "${RED}❌ Erro de sintaxe na configuração do Apache:${NC}"
        apache2ctl configtest
        exit 1
    fi
}

cmd_restart() {
    echo -e "${CYAN}🔁 Reiniciando Apache...${NC}"
    apache2ctl stop 2>/dev/null || true
    sleep 1
    apache2ctl start
    echo -e "${GREEN}✅ Apache reiniciado!${NC}"
}

cmd_logs() {
    echo -e "${CYAN}📄 Logs do Apache (Ctrl+C para sair)${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    # Segue ambos os logs em paralelo
    tail -f /var/log/apache2/error.log /var/log/apache2/access.log 2>/dev/null \
        || tail -f /proc/1/fd/2 2>/dev/null \
        || echo -e "${YELLOW}⚠️  Logs não encontrados em /var/log/apache2/${NC}"
}

cmd_build() {
    echo -e "${CYAN}🔨 Iniciando build (minificação JS)...${NC}"
    if command -v npm > /dev/null 2>&1; then
        bash build.sh
    else
        echo -e "${RED}❌ npm não encontrado — verifique a feature Node.js no devcontainer.json${NC}"
        exit 1
    fi
}

cmd_dojo() {
    DOJO_URL="https://download.dojotoolkit.org/release-1.8.14/dojo-release-1.8.14.tar.gz"

    echo -e "${BLUE}📦 Dojo Toolkit${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    if [ -d "js/dojo" ] && [ -d "js/dijit" ] && [ -d "js/dojox" ]; then
        echo -e "${GREEN}✅ Dojo Toolkit local já está instalado (js/dojo, js/dijit, js/dojox)${NC}"
        echo ""
        read -p "Deseja reinstalar? (s/N) " -n 1 -r
        echo
        [[ $REPLY =~ ^[Ss]$ ]] || exit 0
    fi

    echo -e "${CYAN}⬇️  Baixando Dojo Toolkit v1.8.14 (~15MB)...${NC}"
    curl -L -f -o /tmp/dojo.tar.gz "$DOJO_URL" || {
        echo -e "${RED}❌ Falha no download${NC}"
        exit 1
    }

    echo -e "${CYAN}📂 Extraindo...${NC}"
    tar -xzf /tmp/dojo.tar.gz -C /tmp/

    echo -e "${CYAN}🚚 Copiando para js/...${NC}"
    mkdir -p js
    cp -r /tmp/dojo-release-1.8.14/dojo js/
    cp -r /tmp/dojo-release-1.8.14/dijit js/
    cp -r /tmp/dojo-release-1.8.14/dojox js/
    rm -rf /tmp/dojo-release-1.8.14 /tmp/dojo.tar.gz

    # Atualiza .env
    sed -i.bak '/^DOJO_SOURCE=/d' .env && rm -f .env.bak
    echo "DOJO_SOURCE=local" >> .env

    echo -e "${GREEN}✅ Dojo Toolkit instalado! DOJO_SOURCE=local salvo em .env${NC}"
}

cmd_help() {
    echo -e "${BLUE}Foursquare Mass Editor Tools — DevContainer Manager${NC}"
    echo ""
    echo -e "  ${CYAN}./scripts/dev-internal.sh <comando>${NC}"
    echo ""
    echo -e "  ${GREEN}status${NC}   — Status do Apache, PHP, Node, portas e Dojo"
    echo -e "  ${GREEN}reload${NC}   — Recarrega config do Apache (sem derrubar conexões)"
    echo -e "  ${GREEN}restart${NC}  — Para e reinicia o Apache"
    echo -e "  ${GREEN}logs${NC}     — Segue os logs do Apache em tempo real"
    echo -e "  ${GREEN}build${NC}    — Minifica JS e gera build-info.json"
    echo -e "  ${GREEN}dojo${NC}     — Instala/verifica Dojo Toolkit local"
    echo ""
    echo -e "  ${YELLOW}💡 Para uso fora do DevContainer (OrbStack/Docker), use ${CYAN}./dev.sh${NC}"
}

# ── Dispatcher ────────────────────────────────────────────────────────────────
case "${1:-help}" in
    status)  cmd_status  ;;
    reload)  cmd_reload  ;;
    restart) cmd_restart ;;
    logs)    cmd_logs    ;;
    build)   cmd_build   ;;
    dojo)    cmd_dojo    ;;
    help|--help|-h) cmd_help ;;
    *)
        echo -e "${RED}❌ Comando desconhecido: ${1}${NC}"
        echo ""
        cmd_help
        exit 1
        ;;
esac
