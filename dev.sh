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

# Função para build da imagem
build_image() {
    echo "🔨 Construindo imagem Docker..."
    docker build -t foursquare-mass-editor:latest .
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
        -v $(pwd):/var/www/html \
        --env-file .env \
        foursquare-mass-editor:latest
    
    echo "✅ Container iniciado com sucesso!"
    echo "🌐 Acesse: http://localhost/4sqmet"
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

# Menu principal
case "${1:-}" in
    "build")
        check_docker
        build_image
        ;;
    "run")
        check_docker
        build_image
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
    *)
        echo "Uso: $0 {build|run|start|stop|logs|install|restart}"
        echo ""
        echo "Comandos disponíveis:"
        echo "  build    - Constrói a imagem Docker"
        echo "  run      - Constrói e executa o container"
        echo "  start    - Inicia o container"
        echo "  stop     - Para o container"
        echo "  logs     - Mostra logs do container"
        echo "  install  - Instala dependências do Composer"
        echo "  restart  - Reinicia o container"
        exit 1
        ;;
esac
