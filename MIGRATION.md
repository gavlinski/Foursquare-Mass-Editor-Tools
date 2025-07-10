# Plano de Migração - Arquivos Legados

## Status da Migração

### ✅ Arquivos Modernizados (Concluído)
- `index.php` - Migrado para nova estrutura
- `main.php` - Migrado para nova estrutura
- `js/main.js` - Atualizado para nova API

### 🔄 Arquivos em Processo de Migração
- `src/Api/FoursquareApi.php` - Nova implementação da API
- `src/Config/AppConfig.php` - Nova gestão de configurações
- `src/Security/SessionManager.php` - Nova gestão de sessões

### ⚠️ Arquivos Pendentes de Migração
- `search.php` - Ainda usa `FoursquareAPI.Class.php`
- `edit.php` - Ainda usa `includes/app_credentials.php`
- `edit_csv.php` - Ainda usa classe legada
- `load.php` - Ainda usa classe legada
- `load_csv.php` - Ainda usa classe legada
- `flag_csv.php` - Ainda usa classe legada

### 🗑️ Arquivos para Exclusão (Após Migração Completa)
- `FoursquareAPI.Class.php` - Substituído por `src/Api/FoursquareApi.php`
- `includes/app_credentials.php` - Substituído por configuração `.env`
- `includes/config.php` - Redundante com nova estrutura

## Próximos Passos

1. **Migrar arquivos pendentes** para usar a nova estrutura
2. **Testar funcionalidades** após migração
3. **Remover arquivos legados** após confirmação
4. **Atualizar documentação**

## Comando para Remover Arquivos Legados (Execute após migração completa)

```bash
# Remover arquivos legados
git rm FoursquareAPI.Class.php
git rm includes/app_credentials.php
git rm includes/config.php

# Commit da remoção
git commit -m "Remove arquivos legados após migração completa"
```

## Verificação antes da Remoção

Antes de remover os arquivos legados, verificar se não há dependências:

```bash
# Verificar referências aos arquivos
grep -r "FoursquareAPI.Class.php" . --exclude-dir=vendor
grep -r "app_credentials.php" . --exclude-dir=vendor
grep -r "includes/config.php" . --exclude-dir=vendor
```
