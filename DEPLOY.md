# Guia de Deploy para Produção

## 🚀 Configurações de Produção

### Credenciais do Foursquare API

- **Client ID:** [Configurado via .env.production]
- **Client Secret:** [Configurado via .env.production]
- **Project URL:** <http://4sq.eliotools.site/>
- **Redirect URL:** <http://4sq.eliotools.site/index.php>

### URLs do Projeto
- **Desenvolvimento:** http://localhost/4sqmet
- **Produção:** http://4sq.eliotools.site

## 📋 Checklist de Deploy

### 1. Pré-requisitos
- [ ] Servidor web com PHP 8.1+
- [ ] Composer instalado
- [ ] Acesso ao repositório Git
- [ ] Permissões de escrita no diretório web

### 2. Deploy Automático
```bash
# Clone do repositório
git clone https://github.com/gavlinski/Foursquare-Mass-Editor-Tools.git
cd Foursquare-Mass-Editor-Tools

# Execute o script de deploy
./deploy.sh
```

### 3. Deploy Manual

#### 3.1 Configuração do Ambiente
```bash
# Copie as configurações de produção
cp .env.production .env

# Instale as dependências
composer install --no-dev --optimize-autoloader
```

#### 3.2 Configuração do Apache
```bash
# Copie a configuração do Apache
sudo cp apache-config.conf /etc/apache2/sites-available/4sqtools.conf
sudo a2ensite 4sqtools.conf
sudo a2enmod rewrite headers expires deflate
sudo systemctl reload apache2
```

#### 3.3 Permissões
```bash
# Configure as permissões corretas
chmod 755 . -R
chmod 644 .env
chmod 644 includes/app_credentials.php
```

### 4. Verificações Pós-Deploy
- [ ] Aplicação acessível em http://4sq.eliotools.site
- [ ] Autenticação com Foursquare funcionando
- [ ] JavaScript carregando corretamente
- [ ] Pesquisa de venues funcionando
- [ ] Logs de erro limpos

## 🔒 Configurações de Segurança

### Produção vs Desenvolvimento
| Configuração | Desenvolvimento | Produção |
|-------------|----------------|----------|
| APP_ENV | development | production |
| APP_DEBUG | true | false |
| COOKIE_SECURE | false | false* |
| SESSION_SECURE | false | false* |
| Apache Options | +Indexes | -Indexes |
| Error Display | On | Off |

*Nota: HTTPS não está configurado no ambiente atual

### Headers de Segurança
- X-Content-Type-Options: nosniff
- X-Frame-Options: DENY
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin

### Arquivos Bloqueados
- `.env*` - Arquivos de configuração
- `.git*` - Arquivos do Git
- `vendor/` - Dependências do Composer
- `*.md` - Arquivos de documentação

## 🔄 Rollback

Em caso de problemas, execute:
```bash
git checkout HEAD~1
./deploy.sh
```

## 📊 Monitoramento

### Logs Importantes
- Apache Error: `/var/log/apache2/4sqtools_error.log`
- Apache Access: `/var/log/apache2/4sqtools_access.log`
- PHP Errors: Configurado para log do sistema

### Verificações de Saúde
```bash
# Verificar se a aplicação responde
curl -I http://4sq.eliotools.site

# Verificar logs de erro
tail -f /var/log/apache2/4sqtools_error.log
```

## 🆘 Troubleshooting

### Problemas Comuns

1. **Erro 500 - Internal Server Error**
   - Verificar logs do Apache
   - Verificar permissões dos arquivos
   - Verificar se o Composer foi executado

2. **Erro de Autenticação Foursquare**
   - Verificar se as credenciais estão corretas no `.env`
   - Verificar se a URL de redirect está correta
   - Verificar se o domínio está registrado no Foursquare

3. **JavaScript não carregando**
   - Verificar se os arquivos Dojo estão presentes
   - Verificar configurações de cache do Apache
   - Verificar CSP headers

### Contatos
- **Desenvolvedor:** gavlinski@gmail.com
- **Repositório:** https://github.com/gavlinski/Foursquare-Mass-Editor-Tools
