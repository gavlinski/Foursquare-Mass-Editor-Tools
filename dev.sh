#!/bin/bash

# Script para desenvolvimento local do Foursquare Mass Editor Tools

set -e

echo "🚀 Foursquare Mass Editor Tools - Desenvolvimento Local"
echo "=================================================="

# Função para verificar se o Docker está rodando
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        echo "❌ Docker não está rodando. Por favor, inicie o Docker primeiro."
        exit 1
    fi
}

# Função para baixar e instalar o Dojo Toolkit
download_dojo() {
    if [ ! -d "js/dojo" ] || [ ! -d "js/dijit" ] || [ ! -d "js/dojox" ]; then
        echo "📦 Dojo Toolkit não encontrado. Baixando v1.8.14..."
        curl -L -o dojo.tar.gz http://download.dojotoolkit.org/release-1.8.14/dojo-release-1.8.14.tar.gz
        echo "📂 Extraindo Dojo Toolkit..."
        tar -xzf dojo.tar.gz
        echo "🚚 Movendo arquivos..."
        cp -r dojo-release-1.8.14/dojo js/
        cp -r dojo-release-1.8.14/dijit js/
        cp -r dojo-release-1.8.14/dojox js/
        echo "🧹 Limpando arquivos temporários..."
        rm -rf dojo-release-1.8.14 dojo.tar.gz
        echo "✅ Dojo Toolkit instalado com sucesso!"
    else
        echo "✅ Dojo Toolkit já instalado."
    fi
}

# Função para verificar dependências do projeto
check_dependencies() {
    echo "🔍 Verificando dependências do projeto..."
    
    # Verifica e instala Dojo Toolkit
    download_dojo
    
    # Verifica se existe arquivo .env
    if [ ! -f .env ]; then
        echo "⚠️  Arquivo .env não encontrado. Copiando do exemplo..."
        cp .env.example .env
        echo "📝 Edite o arquivo .env com suas configurações antes de continuar."
    fi
    
    # Verifica se existe composer.lock ou vendor
    if [ ! -f composer.lock ] || [ ! -d vendor ]; then
        echo "⚠️  Dependências do Composer não instaladas. Instalando..."
        install_deps
    fi
    
    echo "✅ Dependências verificadas!"
}

# Função para build da imagem
build_image() {
    echo "🔨 Construindo imagem Docker (ambiente de desenvolvimento)..."
    docker build --build-arg BUILD_ENV=development -t foursquare-mass-editor:latest .
    echo "✅ Imagem construída com sucesso!"
}

# Função para executar o container
run_container() {
    echo "🚀 Iniciando container..."
    
    # Para o container se estiver rodando
    docker stop foursquare-mass-editor 2>/dev/null || true
    docker rm foursquare-mass-editor 2>/dev/null || true
    
    # Executa o novo container
    docker run -d \
        --name foursquare-mass-editor \
        -p 80:80 \
        -p 443:443 \
        -v $(pwd):/var/www/html \
        --env-file .env \
        foursquare-mass-editor:latest
    
    echo "✅ Container iniciado com sucesso!"
    echo "🌐 Acesse: https://localhost/4sqmet/ (HTTPS)"
    echo "🔓 HTTP: http://localhost/4sqmet/ (redireciona para HTTPS)"
    echo "📂 Debug: https://localhost/4sqmet/debug/"
    echo "📊 Status: Use './dev.sh status' para verificar"
}

# Função para ver logs
show_logs() {
    echo "📄 Mostrando logs do container..."
    docker logs -f foursquare-mass-editor
}

# Função para parar o container
stop_container() {
    echo "⏹️  Parando container..."
    docker stop foursquare-mass-editor 2>/dev/null || true
    docker rm foursquare-mass-editor 2>/dev/null || true
    echo "✅ Container parado!"
}

# Função para instalar dependências
install_deps() {
    echo "📦 Instalando dependências do Composer..."
    docker run --rm -v $(pwd):/app composer:latest install
    echo "✅ Dependências instaladas!"
}

# Função para verificar status e sincronização
status() {
    echo "📊 Status do Foursquare Mass Editor Tools"
    echo "======================================="
    
    # Verifica se o container está rodando
    if docker ps --filter name=foursquare-mass-editor --format "table {{.Names}}\t{{.Status}}" | grep -q foursquare-mass-editor; then
        echo "✅ Container: RODANDO"
        echo "🔗 URLs:"
        echo "   • Principal: http://localhost/4sqmet/"
        echo "   • Debug: http://localhost/4sqmet/debug/"
        
        # Teste de conectividade
        if curl -s -o /dev/null -w "%{http_code}" http://localhost/4sqmet/ | grep -q "200"; then
            echo "✅ Conectividade: OK"
        else
            echo "❌ Conectividade: FALHOU"
        fi
        
        # Verifica sincronização de arquivos críticos
        echo ""
        echo "🔄 Sincronização de arquivos:"
        
        # Google Maps
        local_lines=$(cat js/google-maps.js | wc -l | tr -d ' ')
        container_lines=$(docker exec foursquare-mass-editor cat /var/www/html/js/google-maps.js | wc -l | tr -d ' ')
        if [ "$local_lines" = "$container_lines" ]; then
            echo "   ✅ google-maps.js: Sincronizado ($local_lines linhas)"
        else
            echo "   ❌ google-maps.js: DESSINCRONIZADO (local: $local_lines, container: $container_lines)"
        fi
        
        # 4sq.js  
        local_lines=$(cat js/4sq.js | wc -l | tr -d ' ')
        container_lines=$(docker exec foursquare-mass-editor cat /var/www/html/js/4sq.js | wc -l | tr -d ' ')
        if [ "$local_lines" = "$container_lines" ]; then
            echo "   ✅ 4sq.js: Sincronizado ($local_lines linhas)"
        else
            echo "   ❌ 4sq.js: DESSINCRONIZADO (local: $local_lines, container: $container_lines)"
        fi
        
        # Debug folder
        local_files=$(ls debug/ | wc -l | tr -d ' ')
        container_files=$(docker exec foursquare-mass-editor ls /var/www/html/debug/ | wc -l | tr -d ' ')
        if [ "$local_files" = "$container_files" ]; then
            echo "   ✅ debug/: Sincronizado ($local_files arquivos)"
        else
            echo "   ❌ debug/: DESSINCRONIZADO (local: $local_files, container: $container_files)"
        fi
        
    else
        echo "❌ Container: PARADO"
        echo "💡 Use './dev.sh run' para iniciar"
    fi
}

# Menu principal
case "${1:-}" in
    "build")
        check_docker
        build_image
        ;;
    "run")
        check_docker
        check_dependencies
        if ! docker image inspect foursquare-mass-editor:latest > /dev/null 2>&1; then
             build_image
        fi
        run_container
        ;;
    "start")
        check_docker
        run_container
        ;;
    "stop")
        check_docker
        stop_container
        ;;
    "logs")
        check_docker
        show_logs
        ;;
    "install")
        check_docker
        install_deps
        ;;
    "restart")
        check_docker
        stop_container
        run_container
        ;;
    "status")
        check_docker
        status
        ;;
    *)
        echo "Uso: $0 {build|run|start|stop|logs|install|restart|status}"
        echo ""
        echo "Comandos disponíveis:"
        echo "  build    - Constrói a imagem Docker"
        echo "  run      - Constrói e executa o container"
        echo "  start    - Inicia o container"
        echo "  stop     - Para o container"
        echo "  logs     - Mostra logs do container"
        echo "  install  - Instala dependências do Composer"
        echo "  restart  - Reinicia o container"
        echo "  status   - Verifica status e sincronização de arquivos"
        exit 1
        ;;
esac
