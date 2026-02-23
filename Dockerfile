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
RUN a2enmod rewrite headers deflate expires

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

# Expondo a porta padrão do Apache
EXPOSE 80

# Comando padrão
CMD ["apache2-foreground"]