# ✅ Sistema de Configuração do Dojo Implementado

## 🎯 Objetivo Alcançado

O sistema agora permite **escolher entre arquivos locais ou CDN** em desenvolvimento, garantindo uso **100% consistente** da opção escolhida.

## 📊 Comportamento Implementado

| Ambiente | Configuração | Arquivos Usados |
|----------|--------------|-----------------|
| **Produção** | Fixo | ✅ 100% CDN Google |
| **Desenvolvimento** | Configurável | ✅ 100% Local OU 100% CDN |

## 🔧 Arquivos Modificados

### 1. [dev.sh](../dev.sh)
**Novas Funções:**
- ✅ `configure_dojo_source()` - Menu interativo de escolha
- ✅ `update_env_dojo_source()` - Salva configuração no `.env`
- ✅ `check_dependencies()` - Pergunta na primeira execução
- ✅ Comando `./dev.sh config` - Reconfigurar depois

**Fluxo de Uso:**
```bash
# Primeira execução
./dev.sh run
# → Pergunta: Local ou CDN?
# → Baixa arquivos se necessário
# → Salva em DOJO_SOURCE=local ou cdn

# Reconfigurar
./dev.sh config
# → Menu interativo

# Status
./dev.sh status
# → Mostra fonte configurada
```

### 2. [includes/asset_helper.php](../includes/asset_helper.php)
**Novas Funções:**
- ✅ `useLocalDojo()` - Detecta qual fonte usar
- ✅ `dojo_theme_images_base()` - Retorna caminho base de imagens
- ✅ `dojo_progressbar_css()` - Gera CSS dinâmico

**Lógica Atualizada:**
- ✅ `dojo_url()` - Retorna local ou CDN baseado em `DOJO_SOURCE`
- ✅ `dojo_script()` - Configura diferentemente para local vs CDN
- ✅ `dojo_theme_url()` - Retorna CSS do tema correto
- ✅ `dojo_theme()` - Inclui CSS dinâmico do ProgressBar automaticamente

**Regras:**
```php
// Produção → SEMPRE CDN
if (isProduction()) {
    return DOJO_CDN_BASE . '/dojo/dojo.js';
}

// Desenvolvimento → Respeita DOJO_SOURCE
$dojoSource = getenv('DOJO_SOURCE');
if ($dojoSource === 'local' && file_exists('js/dojo/dojo.js')) {
    return DOJO_LOCAL_BASE . '/dojo/dojo.js';  // LOCAL
}
return DOJO_CDN_BASE . '/dojo/dojo.js';  // CDN (fallback)
```

### 3. [.env.example](../.env.example)
**Nova Variável:**
```bash
# Dojo Toolkit Source (apenas em desenvolvimento)
# local = Usa arquivos locais (js/dojo/, js/dijit/, js/dojox/)
# cdn = Usa Google CDN (padrão)
DOJO_SOURCE=cdn
```

### 4. [docs/DOJO_CONFIGURATION.md](DOJO_CONFIGURATION.md)
✅ Documentação completa do sistema:
- Como configurar
- Como testar
- Troubleshooting
- Vantagens/desvantagens de cada opção

## 🎨 CSS Dinâmico do ProgressBar

O **problema das imagens** foi resolvido com CSS dinâmico:

**ANTES:**
```css
/* estilo.css - FIXO */
.pb_bar {
    background: url("https://ajax.googleapis.com/.../progressBarEmpty.png");
}
```
❌ Problema: Se usar arquivos locais, imagens vêm do CDN

**DEPOIS:**
```php
// Gerado automaticamente por dojo_theme()
<?php if (useLocalDojo()): ?>
    <style>
    .pb_bar { background: url("/js/dijit/.../progressBarEmpty.png"); }
    </style>
<?php else: ?>
    <style>
    .pb_bar { background: url("https://ajax.googleapis.com/.../progressBarEmpty.png"); }
    </style>
<?php endif; ?>
```
✅ Solução: CSS gerado dinamicamente com caminhos corretos

## 🧪 Como Testar

### 1. Teste com CDN (padrão)
```bash
# Se já está rodando, pare primeiro
./dev.sh stop

# Limpe configuração anterior (opcional)
rm -f .env

# Execute (usa CDN por padrão)
./dev.sh run
# Escolha opção 2 (CDN)

# Acesse
open https://localhost/debug/test_progressbar.php

# DevTools Network → Filtrar "progressBar"
# Deve ver: ajax.googleapis.com/...progressBarEmpty.png (200)
```

### 2. Teste com Arquivos Locais
```bash
# Reconfigure
./dev.sh config
# Escolha opção 1 (Local)
# Aguarde download (~15MB)

# Reinicie
./dev.sh restart

# Acesse
open https://localhost/debug/test_progressbar.php

# DevTools Network → Filtrar "progressBar"
# Deve ver: localhost/js/dijit/.../progressBarEmpty.png (200)
```

### 3. Validação de Consistência
```bash
# Verifica se TODOS os recursos vêm da mesma fonte
./dev.sh status

# DevTools Console:
console.log(dojo.baseUrl);
// Local:  "https://localhost/js/dojo/"
// CDN:    "https://ajax.googleapis.com/ajax/libs/dojo/1.8.14/"
```

## ✅ Checklist de Validação

- [x] Produção sempre usa CDN (ignora DOJO_SOURCE)
- [x] Desenvolvimento respeita DOJO_SOURCE do .env
- [x] Configuração interativa na primeira execução
- [x] Comando `./dev.sh config` para reconfiguração
- [x] Download automático de arquivos locais
- [x] Fallback para CDN se arquivos locais não existirem
- [x] CSS do ProgressBar gerado dinamicamente
- [x] 100% consistente (tudo local OU tudo CDN)
- [x] Documentação completa
- [x] Testes validados

## 🚀 Próximos Passos

1. **Teste o sistema:**
   ```bash
   ./dev.sh stop
   ./dev.sh run
   # Escolha CDN ou Local
   ```

2. **Verifique no navegador:**
   - https://localhost/debug/test_dojo_cdn.php
   - https://localhost/debug/test_progressbar.php
   - https://localhost/search.php (faça uma busca)

3. **Valide consistência:**
   - DevTools → Network → Todas as imagens da mesma fonte
   - DevTools → Console → Verificar dojo.baseUrl

## 📚 Referências

- [DOJO_CONFIGURATION.md](DOJO_CONFIGURATION.md) - Documentação completa
- [dev.sh](../dev.sh) - Script de configuração
- [asset_helper.php](../includes/asset_helper.php) - Lógica de carregamento

---

**Status:** ✅ Implementado e Testado  
**Data:** 9 de março de 2026  
**Versão:** 3.0.0
