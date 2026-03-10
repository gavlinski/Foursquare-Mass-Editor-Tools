# Sistema de Configuração do Dojo Toolkit

## 📋 Visão Geral

O Foursquare Mass Editor Tools agora suporta **duas fontes para o Dojo Toolkit**:

1. **Arquivos Locais** (`js/dojo/`, `js/dijit/`, `js/dojox/`)
2. **Google CDN** (`ajax.googleapis.com`)

A escolha é **100% consistente** - todos os recursos vêm da mesma fonte.

## 🎯 Comportamento por Ambiente

### 🏢 Produção (eliotools.site)
- ✅ **SEMPRE usa Google CDN**
- ❌ Ignora configuração `DOJO_SOURCE`
- 🎯 Performance otimizada
- 🌐 Cache global compartilhado

### 💻 Desenvolvimento (localhost)
- ⚙️ **Configurável via `.env`**
- 🔄 Escolha entre local ou CDN
- 🧪 Melhor para debug (local) ou prototipagem rápida (CDN)

## 🚀 Como Configurar

### Primeira Execução

```bash
./dev.sh run
```

O script perguntará qual fonte usar:

```
📦 Configuração do Dojo Toolkit
================================

Escolha a fonte dos arquivos Dojo para DESENVOLVIMENTO:

  1️⃣  Arquivos LOCAIS (js/dojo/, js/dijit/, js/dojox/)
     ✅ Melhor para debug detalhado
     ✅ Funciona offline
     ❌ Download inicial ~15MB

  2️⃣  Google CDN (ajax.googleapis.com)
     ✅ Sem download inicial
     ✅ Cache compartilhado
     ✅ Performance otimizada
     ❌ Requer internet

Sua escolha (1=Local, 2=CDN):
```

### Reconfigurar Depois

```bash
./dev.sh config
```

### Baixar Arquivos Locais Manualmente

```bash
./dev.sh dojo
```

## 📝 Configuração Manual (.env)

Edite o arquivo `.env`:

```bash
# Usar arquivos locais
DOJO_SOURCE=local

# OU usar Google CDN (padrão)
DOJO_SOURCE=cdn
```

Depois reinicie o container:

```bash
./dev.sh restart
```

## 🔍 Verificação da Configuração

### Via Script

```bash
./dev.sh status
```

### Via Navegador

Abra: `https://localhost/debug/test_dojo_cdn.php`

Console mostrará:
```javascript
🔍 Diagnóstico Dojo CDN
dojo.baseUrl: "http://localhost/js/dojo/"     // Local
// OU
dojo.baseUrl: "https://ajax.googleapis.com/ajax/libs/dojo/1.8.14/"  // CDN
```

## 🎨 CSS do ProgressBar

O CSS do ProgressBar é gerado **dinamicamente** para garantir consistência:

### Arquivos Locais
```css
.pb_bar {
    background: url("/js/dijit/themes/tundra/images/progressBarEmpty.png");
}
```

### Google CDN
```css
.pb_bar {
    background: url("https://ajax.googleapis.com/ajax/libs/dojo/1.8.14/dijit/themes/tundra/images/progressBarEmpty.png");
}
```

A função `dojo_theme()` já inclui o CSS dinâmico automaticamente.

## ⚠️ Importante

### ✅ Vantagens de Arquivos Locais
- Debug completo com arquivos originais
- Breakpoints no código fonte do Dojo
- Funciona sem internet
- Controle total sobre versão

### ✅ Vantagens do CDN
- Zero configuração
- Sem download de 15MB
- Cache compartilhado entre sites
- Atualizações automáticas
- Melhor performance (HTTP/2, compressão)

### ❌ Desvantagens de Arquivos Locais
- Download inicial de ~15MB
- 3000+ arquivos ocupam espaço
- Requer configuração manual

### ❌ Desvantagens do CDN
- Requer conexão com internet
- Depende de disponibilidade do Google
- Dificuldade em debug profundo do Dojo

## 🧪 Testes

### Teste Completo do Sistema

```bash
# 1. Configure para LOCAL
./dev.sh config
# Escolha opção 1

# 2. Reinicie container
./dev.sh restart

# 3. Verifique status
./dev.sh status

# 4. Teste no navegador
open https://localhost/debug/test_progressbar.php

# 5. Verifique console DevTools
# Todas as imagens devem vir de localhost/js/dijit/...

# 6. Reconfigure para CDN
./dev.sh config
# Escolha opção 2

# 7. Reinicie e teste novamente
./dev.sh restart
open https://localhost/debug/test_progressbar.php

# Agora as imagens devem vir de ajax.googleapis.com
```

## 🔧 Troubleshooting

### Problema: Configurado para local mas usa CDN

**Causa:** Arquivos locais não existem

**Solução:**
```bash
./dev.sh dojo  # Baixa arquivos
./dev.sh restart
```

### Problema: Imagens transparentes no ProgressBar

**Causa:** Conflito entre estilo.css (CDN) e arquivos locais

**Solução:** O CSS dinâmico já corrige isso automaticamente. Se persistir:
```bash
# Limpe cache do navegador
# Cmd+Shift+R (Mac) ou Ctrl+Shift+R (Windows/Linux)
```

### Problema: Erro 404 em imagens do Dojo

**Causa:** Configuração inconsistente

**Solução:**
```bash
./dev.sh config  # Reconfigure
./dev.sh restart # Reinicie
```

## 📚 Arquivos Relacionados

- **`dev.sh`**: Script de configuração e controle
- **`includes/asset_helper.php`**: Lógica de detecção e carregamento
- **`.env`**: Configuração do projeto (DOJO_SOURCE)
- **`.env.example`**: Template com documentação
- **`estilo.css`**: CSS base (mantém URLs do CDN como fallback)

## 🎓 Referências

- [Dojo Toolkit 1.8.14](https://dojotoolkit.org/reference-guide/1.8/)
- [Google Hosted Libraries - Dojo](https://developers.google.com/speed/libraries#dojo)
- [Asset Helper Documentation](../includes/asset_helper.php)

---

**Última Atualização:** 9 de março de 2026  
**Versão:** 3.0.0
