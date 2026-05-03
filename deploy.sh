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
PRODUCTION_SERVER="${DEPLOY_HOST:-4sq.eliotools.site}"  # Permite override via DEPLOY_HOST
PRODUCTION_USER="${DEPLOY_USER:-root}"
PRODUCTION_PATH="/var/www/4sqmet"
BRANCH="${DEPLOY_BRANCH:-refactor-ia}"
BACKUP_DIR="/var/backups/4sqmet"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Detecta ambiente CI (GitHub Actions, GitLab CI, etc.)
IS_CI_ENVIRONMENT="${CI:-false}"

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

# Verifica se SSH está configurado (ssh-agent ou chave direta)
if [ -z "$SSH_AUTH_SOCK" ] && [ -z "$DEPLOY_KEY" ] && [ -z "$SSH_KEY_PATH" ]; then
    echo -e "${YELLOW}⚠️  Nenhuma chave SSH especificada.${NC}"
    echo -e "   ${CYAN}Configure: export SSH_KEY_PATH=/path/to/key${NC}"
    echo -e "   ${CYAN}Ou adicione ao ssh-agent: ssh-add ~/.ssh/id_rsa${NC}\n"
elif [ -n "$SSH_AUTH_SOCK" ]; then
    echo -e "${GREEN}✅ SSH Agent detectado (${SSH_AUTH_SOCK})${NC}\n"
fi

# Verifica se está na branch correta
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
    echo -e "${RED}❌ Você está na branch '${CURRENT_BRANCH}', mas o deploy é da '${BRANCH}'.${NC}"
    if [ "$IS_CI_ENVIRONMENT" = "true" ]; then
        echo -e "${YELLOW}🤖 Ambiente CI: fazendo checkout automaticamente para '${BRANCH}'${NC}"
        git checkout "$BRANCH"
    else
        echo -e "${YELLOW}Deseja fazer checkout para '${BRANCH}'? (s/n)${NC}"
        read -r response
        if [[ "$response" =~ ^[Ss]$ ]]; then
            git checkout "$BRANCH"
        else
            echo -e "${RED}Deploy cancelado.${NC}"
            exit 1
        fi
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
    if [ "$IS_CI_ENVIRONMENT" = "true" ]; then
        echo -e "${YELLOW}🤖 Ambiente CI: fazendo pull automaticamente${NC}"
        git pull origin "$BRANCH"
    else
        echo -e "${YELLOW}Deseja fazer pull antes do deploy? (s/n)${NC}"
        read -r response
        if [[ "$response" =~ ^[Ss]$ ]]; then
            git pull origin "$BRANCH"
        fi
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

if [ "$IS_CI_ENVIRONMENT" = "true" ]; then
    echo -e "${GREEN}🤖 Ambiente CI detectado: prosseguindo automaticamente${NC}"
else
    echo -e "${YELLOW}Deseja continuar? (s/n)${NC}"
    read -r response
    
    if [[ ! "$response" =~ ^[Ss]$ ]]; then
        echo -e "${YELLOW}Deploy cancelado pelo usuário.${NC}"
        exit 0
    fi
fi

# Etapa 1: Build local
echo -e "\n${BLUE}━━━ Etapa 1/6: Build local ━━━${NC}"

if [ "$IS_CI_ENVIRONMENT" = "true" ]; then
    echo -e "${GREEN}🤖 Ambiente CI: build já foi realizado no job anterior${NC}"
    echo -e "${YELLOW}⏭️  Pulando build local...${NC}"
else
    echo -e "${YELLOW}📦 Executando build de produção...${NC}"
    
    if [ ! -f "build.sh" ]; then
        echo -e "${RED}❌ build.sh não encontrado!${NC}"
        exit 1
    fi
    
    # Define origem da build como "deploy" (manual via script)
    export BUILD_SOURCE="deploy"
    
    if bash build.sh; then
        echo -e "${GREEN}✅ Build concluído com sucesso${NC}"
    else
        echo -e "${RED}❌ Erro no build. Deploy abortado.${NC}"
        exit 1
    fi
fi

# Etapa 2: Testes locais (se existirem arquivos PHP de teste)
echo -e "\n${BLUE}━━━ Etapa 2/6: Testes locais ━━━${NC}"
PHP_TESTS_EXIST=$(find tests -name "*Test.php" -o -name "*.phpt" 2>/dev/null | head -1)
if [ -f "composer.json" ] && grep -q "phpunit" composer.json && ( [ -f "phpunit.xml" ] || [ -n "$PHP_TESTS_EXIST" ] ); then
    echo -e "${YELLOW}🧪 Executando testes...${NC}"
    composer test || {
        echo -e "${RED}❌ Testes falharam. Deploy abortado.${NC}"
        exit 1
    }
    echo -e "${GREEN}✅ Testes passaram${NC}"
else
    echo -e "${YELLOW}⚠️  Nenhum teste configurado. Pulando...${NC}"
fi

# Etapas 3-6: Executar remotamente (uma única conexão SSH)
echo -e "\n${BLUE}━━━ Etapas 3-6: Deploy Remoto ━━━${NC}"
echo -e "${YELLOW}🚀 Conectando ao servidor e executando deploy completo...${NC}"

# Configurar comando SSH com opções para CI/CD
SSH_OPTS="-T -o BatchMode=yes -o ConnectTimeout=10 -o LogLevel=ERROR"
if [ -n "$SSH_KEY_PATH" ]; then
    SSH_CMD="ssh $SSH_OPTS -i $SSH_KEY_PATH"
else
    SSH_CMD="ssh $SSH_OPTS"
fi

# Executar todas as etapas em uma única sessão SSH
$SSH_CMD "${PRODUCTION_USER}@${PRODUCTION_SERVER}" << EOF
    set -e
    
    # ━━━ ETAPA 3: BACKUP ━━━
    echo ""
    echo "━━━ Etapa 3/6: Backup em produção ━━━"
    echo "💾 Criando backup do código atual..."
    mkdir -p ${BACKUP_DIR}
    
    if [ -d "${PRODUCTION_PATH}" ]; then
        cd ${PRODUCTION_PATH}
        tar -czf ${BACKUP_DIR}/backup_${TIMESTAMP}.tar.gz \
            --exclude='vendor' \
            --exclude='node_modules' \
            --exclude='.git' \
            . 2>/dev/null
        echo "✅ Backup criado: backup_${TIMESTAMP}.tar.gz"
        
        # Mantém apenas os 5 backups mais recentes
        cd ${BACKUP_DIR}
        ls -t backup_*.tar.gz 2>/dev/null | tail -n +6 | xargs -r rm
        echo "🧹 Backups antigos removidos (mantidos 5 mais recentes)"
    else
        echo "⚠️  Diretório de produção não existe. Pulando backup."
    fi
    
    # ━━━ ETAPA 4: DEPLOY CÓDIGO ━━━
    echo ""
    echo "━━━ Etapa 4/6: Deploy para servidor ━━━"
    echo "🚀 Fazendo deploy do código..."
    cd ${PRODUCTION_PATH}

    echo "🔍 Verificando estado do repositório remoto..."
    REMOTE_STATUS=\$(git status --short)
    if [ -n "\$REMOTE_STATUS" ]; then
        echo "⚠️  Repositório remoto com mudanças locais detectadas"
        echo "\$REMOTE_STATUS"

        REMOTE_SYNC_DIR="${BACKUP_DIR}/pre_git_sync_${TIMESTAMP}"
        mkdir -p "\$REMOTE_SYNC_DIR"

        printf "%s\n" "\$REMOTE_STATUS" > "\$REMOTE_SYNC_DIR/git-status.txt"
        git --no-pager diff > "\$REMOTE_SYNC_DIR/git-diff.patch" || true
        git ls-files --others --exclude-standard > "\$REMOTE_SYNC_DIR/untracked-files.txt" || true

        git stash push -u -m "pre-deploy-sync-${TIMESTAMP}" >/dev/null

        echo "✅ Mudanças locais preservadas antes do pull"
        echo "   Backup textual: \$REMOTE_SYNC_DIR"
        echo "   Stash: pre-deploy-sync-${TIMESTAMP}"
    fi
    
    echo "📥 Atualizando código via Git..."
    git fetch origin
    git checkout ${BRANCH}
    git pull origin ${BRANCH}

    echo "🔎 Validando fallback local do Dojo (js/dojo, js/dijit, js/dojox)..."
    if [ ! -f "js/dojo/dojo.js" ] || [ ! -f "js/dijit/themes/tundra/tundra.css" ] || [ ! -f "js/dojox/form/Uploader.js" ]; then
        echo "❌ Fallback local do Dojo incompleto no servidor."
        echo "   Esperado: js/dojo/dojo.js, js/dijit/themes/tundra/tundra.css, js/dojox/form/Uploader.js"
        echo "   Execute scripts/setup-droplet.sh (fase Dojo) antes de continuar o deploy."
        exit 1
    fi
    echo "✅ Fallback local do Dojo validado"
    
    echo "⚙️  Atualizando .env com secrets..."
    if [ -n "${FOURSQUARE_CLIENT_KEY}" ]; then
        cat > .env << ENVEOF
# Foursquare API Credentials
FOURSQUARE_CLIENT_KEY=${FOURSQUARE_CLIENT_KEY}
FOURSQUARE_CLIENT_SECRET=${FOURSQUARE_CLIENT_SECRET}
FOURSQUARE_REDIRECT_URI=${FOURSQUARE_REDIRECT_URI}

# Google Maps Credentials
GOOGLE_MAPS_API_KEY=${GOOGLE_MAPS_API_KEY}
GOOGLE_MAPS_MAP_ID=${GOOGLE_MAPS_MAP_ID}

# Google Maps Geocoding API Key (Server-Side)
GOOGLE_MAPS_GEOCODING_KEY=${GOOGLE_MAPS_GEOCODING_KEY}

# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=${APP_URL}

# Dojo Toolkit Source (produção sempre usa CDN)
DOJO_SOURCE=cdn

# Session Configuration
SESSION_LIFETIME=1440
SESSION_SECURE=true
SESSION_HTTPONLY=true

# Cookie Settings
COOKIE_SECURE=true
COOKIE_HTTPONLY=true
COOKIE_SAMESITE=Lax
ENVEOF
        echo "✅ .env atualizado com secrets do GitHub Actions"
    else
        echo "⚠️  Secrets não fornecidos, usando .env existente"
    fi
    
    echo "⚙️  Criando google_maps_credentials.php..."
    if [ -f "includes/google_maps_credentials.php.example" ]; then
        cp includes/google_maps_credentials.php.example includes/google_maps_credentials.php
        echo "✅ google_maps_credentials.php criado (lê do .env)"
    else
        echo "⚠️  Arquivo .example não encontrado"
    fi
    
    echo "✅ Código atualizado (dependências PHP serão instaladas no Docker build)"
    
    # ━━━ ETAPA 5: BUILD DOCKER ━━━
    echo ""
    echo "━━━ Etapa 5/6: Build Docker ━━━"
    echo "🐳 Rebuilding imagem Docker..."
    
    echo "🔧 Parando container atual..."
    docker stop 4sqmet 2>/dev/null || echo "   Container não estava rodando"
    docker rm 4sqmet 2>/dev/null || echo "   Container não existia"
    
    echo "🏗️  Building nova imagem (ambiente de produção)..."
    docker build --build-arg BUILD_ENV=production -t 4sqmet:latest . --quiet
    
    echo "🧹 Limpando imagens antigas..."
    docker image prune -f >/dev/null 2>&1
    
    echo "✅ Imagem Docker atualizada"
    
    # ━━━ ETAPA 6: INICIAR APLICAÇÃO ━━━
    echo ""
    echo "━━━ Etapa 6/6: Iniciar aplicação ━━━"
    echo "🚀 Iniciando container Docker..."
    
    echo "🐳 Iniciando container..."
    docker run -d \
        --name 4sqmet \
        --restart unless-stopped \
        -p 80:80 \
        -p 443:443 \
        --env-file .env \
        -v /var/www/4sqmet:/var/www/html \
        -v /etc/letsencrypt:/etc/letsencrypt:ro \
        4sqmet:latest >/dev/null
    
    echo "⏳ Aguardando container e Apache inicializarem..."
    sleep 10
    
    if docker ps | grep -q 4sqmet; then
        echo "✅ Container 4sqmet está rodando"
        docker ps --filter name=4sqmet --format "   {{.Names}}: {{.Status}}"
    else
        echo "❌ Container não está rodando!"
        echo "Últimas 20 linhas do log:"
        docker logs --tail 20 4sqmet
        exit 1
    fi
    
    echo "🔍 Verificando saúde do container..."
    sleep 3
    HEALTH=\$(docker inspect --format='{{.State.Health.Status}}' 4sqmet 2>/dev/null || echo "no-healthcheck")
    if [ "\$HEALTH" = "healthy" ] || [ "\$HEALTH" = "no-healthcheck" ]; then
        echo "✅ Container saudável"
    else
        echo "⚠️  Container status: \$HEALTH"
    fi
    
    echo "✅ Aplicação iniciada"
EOF

echo -e "${GREEN}✅ Deploy remoto concluído${NC}"

# Verificação final
echo -e "\n${BLUE}━━━ Verificação final ━━━${NC}"
echo -e "${YELLOW}🔍 Testando conectividade...${NC}"

# Aguardar mais tempo para Apache inicializar completamente
sleep 5

# Testar HTTPS (produção)
HTTPS_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "https://${PRODUCTION_SERVER}/" || echo "000")

if [ "$HTTPS_CODE" = "200" ] || [ "$HTTPS_CODE" = "302" ]; then
    echo -e "${GREEN}✅ Site está respondendo via HTTPS (HTTP ${HTTPS_CODE})${NC}"
elif [ "$HTTPS_CODE" = "000" ]; then
    # Fallback: testar HTTP
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "http://${PRODUCTION_SERVER}/" || echo "000")
    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
        echo -e "${YELLOW}⚠️  Site responde via HTTP mas HTTPS falhou${NC}"
        echo -e "${YELLOW}   Verifique certificados SSL${NC}"
    else
        echo -e "${RED}⚠️  Site não está respondendo (HTTPS: ${HTTPS_CODE}, HTTP: ${HTTP_CODE})${NC}"
        echo -e "${YELLOW}   Container pode estar inicializando. Aguarde 1-2 minutos e teste:${NC}"
        echo -e "${CYAN}   curl -I https://${PRODUCTION_SERVER}/${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Site retornou código inesperado: ${HTTPS_CODE}${NC}"
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
echo -e "   ${CYAN}cd ${PRODUCTION_PATH}${NC}"
echo -e "   ${CYAN}docker stop 4sqmet && docker rm 4sqmet${NC}"
echo -e "   ${CYAN}tar -xzf ${BACKUP_DIR}/backup_${TIMESTAMP}.tar.gz -C ${PRODUCTION_PATH}${NC}"
echo -e "   ${CYAN}docker build -t 4sqmet:latest .${NC}"
echo -e "   ${CYAN}docker run -d --name 4sqmet --restart unless-stopped -p 80:80 -p 443:443 -v \$(pwd):/var/www/html 4sqmet:latest${NC}"

echo -e "\n${YELLOW}📊 Monitorar logs:${NC}"
echo -e "   ${CYAN}docker logs -f 4sqmet${NC}"

echo -e "\n${GREEN}✅ Deploy finalizado com sucesso!${NC}\n"

exit 0
