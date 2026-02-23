#!/bin/bash
###############################################################################
# Foursquare Mass Editor Tools - Deploy Script
# Deploy automatizado para produção (Digital Ocean)
###############################################################################

set -e  # Exit on error

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configurações
PRODUCTION_SERVER="4sq.eliotools.site"
PRODUCTION_USER="${DEPLOY_USER:-root}"
PRODUCTION_PATH="/var/www/html"
BRANCH="${DEPLOY_BRANCH:-refactor-ia}"
BACKUP_DIR="/var/backups/4sqmet"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Banner
echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║   Foursquare Mass Editor Tools - Deploy System v3.0       ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Verifica variáveis de ambiente necessárias
if [ -z "$DEPLOY_USER" ]; then
    echo -e "${YELLOW}⚠️  DEPLOY_USER não definido. Usando 'root' como padrão.${NC}"
    echo -e "   ${CYAN}Configure: export DEPLOY_USER=seu_usuario${NC}\n"
fi

if [ -z "$DEPLOY_KEY" ] && [ -z "$SSH_KEY_PATH" ]; then
    echo -e "${YELLOW}⚠️  Nenhuma chave SSH especificada.${NC}"
    echo -e "   ${CYAN}Configure: export SSH_KEY_PATH=/path/to/key${NC}"
    echo -e "   ${CYAN}Ou adicione ao ssh-agent: ssh-add ~/.ssh/id_rsa${NC}\n"
fi

# Verifica se está na branch correta
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
    echo -e "${RED}❌ Você está na branch '${CURRENT_BRANCH}', mas o deploy é da '${BRANCH}'.${NC}"
    echo -e "${YELLOW}Deseja fazer checkout para '${BRANCH}'? (s/n)${NC}"
    read -r response
    if [[ "$response" =~ ^[Ss]$ ]]; then
        git checkout "$BRANCH"
    else
        echo -e "${RED}Deploy cancelado.${NC}"
        exit 1
    fi
fi

# Verifica se há mudanças não commitadas
if ! git diff-index --quiet HEAD --; then
    echo -e "${RED}❌ Há mudanças não commitadas no repositório.${NC}"
    echo -e "${YELLOW}Commit ou stash suas mudanças antes do deploy.${NC}"
    exit 1
fi

echo -e "${BLUE}🔍 Verificando status do repositório...${NC}"
git fetch origin

# Verifica se está atualizado com origin
LOCAL=$(git rev-parse @)
REMOTE=$(git rev-parse @{u})

if [ "$LOCAL" != "$REMOTE" ]; then
    echo -e "${YELLOW}⚠️  Branch local não está sincronizada com origin.${NC}"
    echo -e "${YELLOW}Deseja fazer pull antes do deploy? (s/n)${NC}"
    read -r response
    if [[ "$response" =~ ^[Ss]$ ]]; then
        git pull origin "$BRANCH"
    fi
fi

# Confirmação de deploy
echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}🚀 Pronto para fazer deploy para PRODUÇÃO${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "   Servidor: ${GREEN}${PRODUCTION_SERVER}${NC}"
echo -e "   Usuário: ${GREEN}${PRODUCTION_USER}${NC}"
echo -e "   Branch: ${GREEN}${BRANCH}${NC}"
echo -e "   Commit: ${GREEN}$(git log -1 --pretty=format:'%h - %s')${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${RED}⚠️  ATENÇÃO: Este deploy afetará o site em produção!${NC}"
echo -e "${YELLOW}Deseja continuar? (s/n)${NC}"
read -r response

if [[ ! "$response" =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}Deploy cancelado pelo usuário.${NC}"
    exit 0
fi

# Etapa 1: Build local
echo -e "\n${BLUE}━━━ Etapa 1/6: Build local ━━━${NC}"
echo -e "${YELLOW}📦 Executando build de produção...${NC}"

if [ ! -f "build.sh" ]; then
    echo -e "${RED}❌ build.sh não encontrado!${NC}"
    exit 1
fi

if bash build.sh; then
    echo -e "${GREEN}✅ Build concluído com sucesso${NC}"
else
    echo -e "${RED}❌ Erro no build. Deploy abortado.${NC}"
    exit 1
fi

# Etapa 2: Testes locais (se existirem)
echo -e "\n${BLUE}━━━ Etapa 2/6: Testes locais ━━━${NC}"
if [ -f "composer.json" ] && grep -q "phpunit" composer.json; then
    echo -e "${YELLOW}🧪 Executando testes...${NC}"
    composer test || {
        echo -e "${RED}❌ Testes falharam. Deploy abortado.${NC}"
        exit 1
    }
    echo -e "${GREEN}✅ Testes passaram${NC}"
else
    echo -e "${YELLOW}⚠️  Nenhum teste configurado. Pulando...${NC}"
fi

# Etapa 3: Backup remoto
echo -e "\n${BLUE}━━━ Etapa 3/6: Backup em produção ━━━${NC}"
echo -e "${YELLOW}💾 Criando backup do código atual...${NC}"

SSH_CMD="ssh"
if [ -n "$SSH_KEY_PATH" ]; then
    SSH_CMD="ssh -i $SSH_KEY_PATH"
fi

$SSH_CMD "${PRODUCTION_USER}@${PRODUCTION_SERVER}" << EOF
    set -e
    echo "📁 Criando diretório de backup..."
    mkdir -p ${BACKUP_DIR}
    
    echo "📦 Compactando código atual..."
    if [ -d "${PRODUCTION_PATH}" ]; then
        cd ${PRODUCTION_PATH}
        tar -czf ${BACKUP_DIR}/backup_${TIMESTAMP}.tar.gz \
            --exclude='vendor' \
            --exclude='node_modules' \
            --exclude='.git' \
            .
        echo "✅ Backup criado: backup_${TIMESTAMP}.tar.gz"
        
        # Mantém apenas os 5 backups mais recentes
        cd ${BACKUP_DIR}
        ls -t backup_*.tar.gz | tail -n +6 | xargs -r rm
        echo "🧹 Backups antigos removidos (mantidos 5 mais recentes)"
    else
        echo "⚠️  Diretório de produção não existe. Pulando backup."
    fi
EOF

echo -e "${GREEN}✅ Backup concluído${NC}"

# Etapa 4: Deploy para produção
echo -e "\n${BLUE}━━━ Etapa 4/6: Deploy para servidor ━━━${NC}"
echo -e "${YELLOW}🚀 Fazendo deploy do código...${NC}"

$SSH_CMD "${PRODUCTION_USER}@${PRODUCTION_SERVER}" << EOF
    set -e
    
    # Navega para o diretório de produção
    cd ${PRODUCTION_PATH}
    
    echo "📥 Atualizando código via Git..."
    git fetch origin
    git checkout ${BRANCH}
    git pull origin ${BRANCH}
    
    echo "📦 Instalando dependências do Composer..."
    composer install --no-dev --optimize-autoloader --no-interaction
    
    echo "🔧 Verificando permissões..."
    chown -R www-data:www-data ${PRODUCTION_PATH}
    find ${PRODUCTION_PATH} -type f -exec chmod 644 {} \;
    find ${PRODUCTION_PATH} -type d -exec chmod 755 {} \;
    
    echo "✅ Deploy concluído"
EOF

echo -e "${GREEN}✅ Código atualizado em produção${NC}"

# Etapa 5: Build e otimização em produção
echo -e "\n${BLUE}━━━ Etapa 5/6: Build em produção ━━━${NC}"
echo -e "${YELLOW}⚙️  Executando build no servidor...${NC}"

$SSH_CMD "${PRODUCTION_USER}@${PRODUCTION_SERVER}" << 'EOF'
    set -e
    cd /var/www/html
    
    # Verifica se Node.js está instalado
    if command -v node &> /dev/null; then
        echo "📦 Instalando dependências npm..."
        npm install --production
        
        echo "🔧 Executando build..."
        bash build.sh
        
        echo "✅ Build em produção concluído"
    else
        echo "⚠️  Node.js não instalado. Build de minificação pulado."
        echo "   Usando arquivos .min.js já commitados."
    fi
EOF

echo -e "${GREEN}✅ Build em produção concluído${NC}"

# Etapa 6: Restart do Apache
echo -e "\n${BLUE}━━━ Etapa 6/6: Restart de serviços ━━━${NC}"
echo -e "${YELLOW}🔄 Reiniciando Apache...${NC}"

$SSH_CMD "${PRODUCTION_USER}@${PRODUCTION_SERVER}" << EOF
    set -e
    
    echo "🔄 Reiniciando Apache..."
    systemctl restart apache2
    
    echo "✅ Apache reiniciado"
    
    # Verifica status
    if systemctl is-active --quiet apache2; then
        echo "✅ Apache está rodando corretamente"
    else
        echo "❌ Apache não está rodando!"
        exit 1
    fi
EOF

echo -e "${GREEN}✅ Serviços reiniciados${NC}"

# Verificação final
echo -e "\n${BLUE}━━━ Verificação final ━━━${NC}"
echo -e "${YELLOW}🔍 Testando conectividade...${NC}"

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://${PRODUCTION_SERVER}" || echo "000")

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    echo -e "${GREEN}✅ Site está respondendo (HTTP ${HTTP_CODE})${NC}"
else
    echo -e "${RED}⚠️  Site retornou HTTP ${HTTP_CODE}${NC}"
    echo -e "${YELLOW}Verifique os logs do Apache e considere fazer rollback.${NC}"
fi

# Resumo final
echo -e "\n${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                 🎉 DEPLOY CONCLUÍDO! 🎉                    ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo -e "\n${CYAN}📊 Informações do Deploy:${NC}"
echo -e "   URL: ${GREEN}http://${PRODUCTION_SERVER}${NC}"
echo -e "   Timestamp: ${GREEN}${TIMESTAMP}${NC}"
echo -e "   Branch: ${GREEN}${BRANCH}${NC}"
echo -e "   Commit: ${GREEN}$(git log -1 --pretty=format:'%h - %s')${NC}"
echo -e "   Backup: ${GREEN}${BACKUP_DIR}/backup_${TIMESTAMP}.tar.gz${NC}"

echo -e "\n${YELLOW}📝 Para fazer rollback:${NC}"
echo -e "   ${CYAN}ssh ${PRODUCTION_USER}@${PRODUCTION_SERVER}${NC}"
echo -e "   ${CYAN}cd ${BACKUP_DIR}${NC}"
echo -e "   ${CYAN}tar -xzf backup_${TIMESTAMP}.tar.gz -C ${PRODUCTION_PATH}${NC}"
echo -e "   ${CYAN}systemctl restart apache2${NC}"

echo -e "\n${GREEN}✅ Deploy finalizado com sucesso!${NC}\n"

exit 0
