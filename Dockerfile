FROM php:8.1-apache

# Build argument para diferenciar desenvolvimento e produção
ARG BUILD_ENV=production

# Instala extensões e utilitários recomendados
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        unzip \
        git \
        libonig-dev \
        libpng-dev \
        libxml2-dev \
        libcurl4-openssl-dev \
        xvfb \
        xauth \
    && docker-php-ext-install pdo pdo_mysql zip mbstring gd xml curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Ativa módulos do Apache necessários
RUN a2enmod rewrite headers deflate expires ssl

# Copia arquivos do projeto para o container
COPY . /var/www/html/

# Copia configuração do Apache apropriada para o ambiente
# Desenvolvimento: /4sqmet/ (com Alias)
# Produção: raiz / (sem Alias)
RUN if [ "$BUILD_ENV" = "development" ]; then \
        cp /var/www/html/apache-config.conf /etc/apache2/sites-available/000-default.conf && \
        echo "✅ Apache configurado para desenvolvimento (/4sqmet/)"; \
    else \
        cp /var/www/html/apache-config-production.conf /etc/apache2/sites-available/000-default.conf && \
        echo "✅ Apache configurado para produção (raiz /)"; \
    fi

# Configurar certificados SSL APENAS para desenvolvimento
# Em produção, usar Let's Encrypt via volume mount
RUN if [ "$BUILD_ENV" = "development" ] && [ -f /var/www/html/ssl/localhost.pem ]; then \
        cp /var/www/html/ssl/localhost.pem /etc/ssl/certs/localhost.pem && \
        cp /var/www/html/ssl/localhost-key.pem /etc/ssl/private/localhost-key.pem && \
        chmod 644 /etc/ssl/certs/localhost.pem && \
        chmod 600 /etc/ssl/private/localhost-key.pem && \
        echo "✅ Certificados SSL de desenvolvimento instalados"; \
    else \
        echo "ℹ️  Ambiente de produção - certificados SSL via Let's Encrypt"; \
    fi

# Define diretório de trabalho
WORKDIR /var/www/html

# Instala dependências do Composer (sem interação para CI/CD)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Define permissões corretas
RUN chown -R www-data:www-data /var/www/html

# Copia e configura entrypoint (cria symlinks SSL antes de iniciar Apache)
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expondo as portas HTTP e HTTPS
EXPOSE 80 443

# Comando padrão - entrypoint cria symlinks SSL e inicia Apache
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]