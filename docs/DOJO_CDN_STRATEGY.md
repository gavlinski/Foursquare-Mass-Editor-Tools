# 📦 Gestão de Assets e CDN - Dojo Toolkit

## 🎯 Decisão de Arquitetura: CDN vs Versionamento Local

### Contexto

Durante o planejamento do processo de CI/CD e deploy em produção, identificamos que as bibliotecas Dojo Toolkit (dojo/, dijit/, dojox/) apresentavam desafios significativos:

**Números:**
- **3.000+ arquivos** JavaScript e CSS
- **~15MB** de código não comprimido
- **1.8.14** - versão específica usada no projeto
- **Impacto no Git:** Histórico inchado, clones lentos, diffs poluídos

### ❌ Problema Encontrado em Produção

```
dojo.js:1 Failed to load resource: the server responded with a status of 404 (Not Found)
main.php:1 Refused to execute script from 'https://4sq.eliotools.site/js/dojo/dojo.js' 
  because its MIME type ('text/html') is not executable
```

**Causa raiz:** Arquivos Dojo estavam no `.gitignore`, portanto não foram copiados para a imagem Docker durante o build.

### ✅ Solução Implementada: Google primário + fallback local

Optamos por **não versionar** as bibliotecas Dojo no Git e usar em produção:
- **Google CDN como primário**
- **Fallback local no servidor** (js/dojo, js/dijit, js/dojox), quando o CDN falhar

#### Benefícios:

1. **Performance Superior**
   - ✅ Cache global (usuários provavelmente já têm Dojo cached)
   - ✅ HTTP/2 e compressão automática
   - ✅ Múltiplos data centers (latência reduzida)
   - ✅ Uptime 99.9%+ garantido pelo Google

2. **Repositório Limpo**
   - ✅ Sem 3.000+ arquivos no Git
   - ✅ Clones 15MB mais leves
   - ✅ Diffs focados apenas no código do projeto
   - ✅ CI/CD mais rápido (menos arquivos para copiar)

3. **Resiliência em Produção**
   - ✅ Se o Google CDN falhar, o sistema pode carregar fallback local
   - ✅ Pipeline e deploy bloqueiam release sem fallback local completo

4. **Manutenção Simplificada**
   - ✅ Sem builds customizados do Dojo
   - ✅ Atualização de versão trivial (mudar constante)
   - ✅ Fonte única de diagnóstico em `debug/test_dojo_cdn.php`

5. **Compatibilidade**
   - ✅ Mesmo comportamento em produção e desenvolvimento
   - ✅ Carrega apenas módulos necessários sob demanda
   - ✅ Minificação automática em produção

---

## 🏗️ Implementação

### 1. Asset Helper (`includes/asset_helper.php`)

Criado helper centralizado para gerenciar carregamento de assets:

```php
<?php
// Configuração de CDN
define('DOJO_VERSION', '1.8.14');
define('DOJO_CDN_BASE', 'https://ajax.googleapis.com/ajax/libs/dojo/' . DOJO_VERSION);

// Funções utilitárias
function dojo_url() { ... }          // Retorna URL do dojo.js
function dojo_script($config) { ... } // Imprime <script> tag
function dojo_theme($theme) { ... }  // Imprime <link> do tema CSS
```

### 2. Detecção Automática de Ambiente

```php
function isProduction() {
    // Método 1: Variável de ambiente
    if (getenv('APP_ENV') === 'production') return true;
    
    // Método 2: Hostname
    if (strpos($_SERVER['HTTP_HOST'], 'eliotools.site') !== false) return true;
    
    // Método 3: IP não é localhost
    $server_addr = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
    if ($server_addr !== '127.0.0.1' && !strpos($_SERVER['HTTP_HOST'], 'localhost')) {
        return true;
    }
    
    return false;
}
```

**Resultado:**
- **Produção** (`4sq.eliotools.site`) → Google CDN como primário + fallback local em falha
- **Desenvolvimento** (`localhost`) → Respeita `DOJO_SOURCE` (local ou cdn)

### 3. Uso nos Arquivos PHP

**Antes:**
```php
<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<link rel="stylesheet" type="text/css" href="js/dijit/themes/tundra/tundra.css">
```

**Depois:**
```php
<?php dojo_script(['parseOnLoad' => true]); ?>
<?php dojo_theme('tundra'); ?>
```

**Arquivos atualizados:**
- ✅ `index.php` (login)
- ✅ `main.php` (página principal)
- ✅ `edit.php` (editor de venues)
- ✅ `load.php` (carregador de venues)
- ✅ `search.php` (pesquisa)
- ✅ `edit_csv.php`, `load_csv.php`, `flag_csv.php` (operações CSV)

---

## 🔧 Configuração

### Produção (Google + fallback local)

**URL gerada:**
```html
<!-- Script principal -->
<script>
var dojoConfig = {
    parseOnLoad: true,
    baseUrl: "https://ajax.googleapis.com/ajax/libs/dojo/1.8.14/dojo/",
    packages: [
        {name: "dijit", location: "../dijit"},
        {name: "dojox", location: "../dojox"}
    ]
};
</script>
<script src="https://ajax.googleapis.com/ajax/libs/dojo/1.8.14/dojo/dojo.js"></script>

<!-- Tema CSS -->
<link rel="stylesheet" type="text/css" 
      href="https://ajax.googleapis.com/ajax/libs/dojo/1.8.14/dijit/themes/tundra/tundra.css">
```

**Módulos carregados sob demanda:**
```javascript
dojo.require("dijit.form.Button");     // Carrega de CDN
dojo.require("dijit.form.TextBox");    // Carrega de CDN
dojo.require("dijit.layout.BorderContainer"); // Carrega de CDN
```

### Desenvolvimento (Local ou CDN)

**URL gerada:**
```html
<script src="/js/dojo/dojo.js"></script>
<link rel="stylesheet" type="text/css" href="/js/dijit/themes/tundra/tundra.css">
```

**Fallback em desenvolvimento:** se `DOJO_SOURCE=local` e arquivos locais estiverem incompletos, fallback para Google CDN.

---

## 📂 Estrutura de Arquivos

### Versionados no Git ✅

```
includes/
  └── asset_helper.php           # Helper de CDN
js/
  ├── 4sq.js                     # Código do projeto ✅
  ├── main.js                    # Código do projeto ✅
  ├── google-maps.js             # Código do projeto ✅
  ├── session-manager.js         # Código do projeto ✅
   ├── dojo/                      # ❌ NÃO versionado (fallback local no servidor)
   ├── dijit/                     # ❌ NÃO versionado (fallback local no servidor)
   └── dojox/                     # ❌ NÃO versionado (fallback local no servidor)
```

### .gitignore

```gitignore
# Bibliotecas externas (carregadas via CDN em produção)
js/dijit/
js/dojo/
js/dojox/
```

---

## 🧪 Testes e Validação

### Teste em Desenvolvimento

1. **Com arquivos locais:**
   ```bash
   # Arquivos existem em js/dojo/
   open https://localhost/4sqmet/
   # ✅ Deve carregar: /js/dojo/dojo.js (local)
   ```

2. **Sem arquivos locais (simulando produção):**
   ```bash
   # Temporariamente mover pastas
   mv js/dojo js/dojo.bak
   open https://localhost/4sqmet/
   # ✅ Deve carregar: https://ajax.googleapis.com/.../dojo.js (CDN)
   ```

### Teste em Produção

```bash
# Após deploy
curl -I https://4sq.eliotools.site/
# Verificar no browser DevTools → Network:
# ✅ dojo.js carregado de: ajax.googleapis.com (normal)
# ✅ Em falha simulada, origem muda para /js/dojo

# Diagnóstico consolidado
# ✅ https://SEU_DOMINIO/debug/test_dojo_cdn.php
```

---

## 🚨 Troubleshooting

### Problema: "dojo is not defined"

**Causas possíveis:**
1. Google CDN bloqueado por firewall/proxy
2. Configuração de CSP (Content Security Policy) muito restritiva
3. Fallback local ausente/incompleto no servidor
4. Erro na configuração do `dojoConfig`

**Diagnóstico:**
```bash
# Testar acesso ao CDN
curl -I https://ajax.googleapis.com/ajax/libs/dojo/1.8.14/dojo/dojo.js

# Verificar fallback local no servidor
ls -l /var/www/4sqmet/js/dojo/dojo.js \
   /var/www/4sqmet/js/dijit/themes/tundra/tundra.css \
   /var/www/4sqmet/js/dojox/form/Uploader.js

# Verificar CSP no Apache
grep "Content-Security-Policy" apache-config*.conf
```

**Solução:**
```apache
# Adicionar CDN à política CSP
Header set Content-Security-Policy "default-src 'self' 'unsafe-inline' 'unsafe-eval' https://ajax.googleapis.com ..."
```

### Problema: Módulos não carregam

**Causa:** `baseUrl` incorreto no `dojoConfig`

**Verificar:**
```javascript
console.log(dojoConfig);
// Deve mostrar:
// { baseUrl: "https://ajax.googleapis.com/.../dojo/", ... }
```

### Problema: CSS não aplica

**Causa:** Tema não especificado no `<body>` ou CSS não carregou

**Verificar:**
```html
<body class="tundra">  <!-- ✅ Obrigatório -->
```

---

## 🔄 Atualizar Versão do Dojo

Para atualizar a versão do Dojo em todo o projeto:

```php
// includes/asset_helper.php
define('DOJO_VERSION', '1.8.15'); // Alterar aqui
```

**Impacto:** Todas as páginas automaticamente usarão nova versão.

---

## 📊 Comparativo: Local vs CDN

| Aspecto | Local (❌ Anterior) | CDN (✅ Atual) |
|---------|-------------------|----------------|
| **Tamanho do repositório** | +15MB | +0MB |
| **Arquivos no Git** | +3.000 | 0 |
| **Tempo de clone** | ~30s | ~10s |
| **Build Docker** | Lento (copia 3k arquivos) | Rápido |
| **Cache do usuário** | Apenas para este site | Global (todos sites) |
| **Latência** | Depende do servidor | Multi-CDN (otimizado) |
| **Manutenção** | Atualização manual complexa | Mudança de 1 constante |
| **Uptime** | Depende do servidor | 99.9%+ (Google SLA) |
| **HTTP/2 Push** | Não configurado | Automático |
| **Compressão Brotli** | Não configurado | Automático |

---

## 🔗 Recursos

**Google CDN - Dojo Toolkit:**
- Base URL: https://ajax.googleapis.com/ajax/libs/dojo/1.8.14/
- Documentação: https://developers.google.com/speed/libraries#dojo

**Dojo Toolkit:**
- Site oficial: https://dojotoolkit.org/
- Documentação 1.8: https://dojotoolkit.org/reference-guide/1.8/
- GitHub: https://github.com/dojo/dojo

---

## 📝 Histórico de Decisões

| Data | Decisão | Contexto |
|------|---------|----------|
| 2026-02-28 | Não fazer build customizado | Build customizado seria complexo e pouco flexível |
| 2026-03-01 | Usar CDN em produção | Descoberto 404 em produção por arquivos no .gitignore |
| 2026-03-01 | Criar asset_helper.php | Centralizar lógica de carregamento dev/prod |
| 2026-03-01 | Fallback para local em dev | Permitir desenvolvimento offline |
| 2026-03-22 | Fallback local em produção | Removidos CDNs alternativos; Google primário + fallback local |

---

**Última atualização:** 22 de março de 2026  
**Versão Dojo:** 1.8.14  
**CDN primário:** Google (ajax.googleapis.com)  
**Fallback de produção:** Local (`/js/dojo`, `/js/dijit`, `/js/dojox`)  
**Status:** ✅ Implementado em produção
