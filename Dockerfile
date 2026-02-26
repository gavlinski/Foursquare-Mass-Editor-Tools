FROM php:8.1-apache

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
    && docker-php-ext-install pdo pdo_mysql zip mbstring gd xml curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Ativa módulos do Apache necessários
RUN a2enmod rewrite headers deflate expires ssl

# Copiar certificados SSL para desenvolvimento
COPY ssl/localhost.pem /etc/ssl/certs/localhost.pem
COPY ssl/localhost-key.pem /etc/ssl/private/localhost-key.pem
RUN chmod 644 /etc/ssl/certs/localhost.pem && \
    chmod 600 /etc/ssl/private/localhost-key.pem

# Copia arquivos do projeto para o container
COPY . /var/www/html/

# Copia configuração do Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Define diretório de trabalho
WORKDIR /var/www/html

# Instala dependências do Composer
RUN composer install --no-dev --optimize-autoloader

# Define permissões corretas
RUN chown -R www-data:www-data /var/www/html

# Expondo as portas HTTP e HTTPS
EXPOSE 80 443

# Comando padrão
CMD ["apache2-foreground"]