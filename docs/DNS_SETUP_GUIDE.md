# 🌐 Guia de Configuração DNS - 4sq.eliotools.site

## 📸 Situação Atual (Namecheap)

Baseado na captura de tela fornecida:

```
HOST RECORDS:
┌──────────────┬──────┬───────────────────┬─────────┐
│ Type         │ Host │ Value             │ TTL     │
├──────────────┼──────┼───────────────────┼─────────┤
│ A Record     │ @    │ 206.189.180.222   │ 5 min   │
│ CNAME Record │ 4sq  │ eliotools.site    │ 5 min   │ ⚠️  PROBLEMA
│ CNAME Record │ www  │ eliotools.site    │ 5 min   │
└──────────────┴──────┴───────────────────┴─────────┘
```

**Problema identificado:**  
O registro `4sq` está configurado como **CNAME** (aponta para outro domínio), mas precisamos de um **A Record** (aponta para IP).

---

## ✅ Configuração Necessária

### Objetivo:
Fazer `4sq.eliotools.site` apontar para o servidor DigitalOcean (`134.209.163.143`)

### Passos no Namecheap:

#### 1️⃣ Deletar CNAME Existente

1. Acesse: **Domains → eliotools.site → Advanced DNS**
2. Localize na tabela **HOST RECORDS**:
   ```
   CNAME Record | 4sq → eliotools.site
   ```
3. Clique no ícone da **lixeira** (🗑️) à direita
4. Confirme a exclusão

#### 2️⃣ Criar Novo A Record

1. Clique no botão vermelho **"ADD NEW RECORD"**
2. Selecione **"A Record"** no dropdown
3. Preencha os campos:
   ```
   Type:  A Record
   Host:  4sq
   Value: 134.209.163.143
   TTL:   5 min
   ```
4. Clique em **"Save Changes"** ou ícone de check (✓)

#### 3️⃣ Resultado Esperado

Após salvar, a tabela deve mostrar:

```
HOST RECORDS:
┌──────────────┬──────┬───────────────────┬─────────┐
│ Type         │ Host │ Value             │ TTL     │
├──────────────┼──────┼───────────────────┼─────────┤
│ A Record     │ @    │ 206.189.180.222   │ 5 min   │
│ A Record     │ 4sq  │ 134.209.163.143   │ 5 min   │ ✅ NOVO
│ CNAME Record │ www  │ eliotools.site    │ 5 min   │
└──────────────┴──────┴───────────────────┴─────────┘
```

---

## 🧪 Testar Propagação DNS

### Método 1: Script Automático

Aguarde **5-10 minutos** após salvar no Namecheap, então execute:

```bash
bash scripts/test-dns-propagation.sh
```

**Saída esperada (quando propagado):**
```
🔍 Testando propagação DNS para 4sq.eliotools.site...

1️⃣ Teste com dig:
   IP Resolvido: 134.209.163.143
   ✅ DNS correto!

2️⃣ Teste com nslookup:
   Address: 134.209.163.143

3️⃣ Teste de conectividade HTTP:
   ✅ HTTP respondendo (status: 200)

4️⃣ Verificando em DNS públicos:
   Google DNS (8.8.8.8):
   134.209.163.143
   Cloudflare DNS (1.1.1.1):
   134.209.163.143

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ DNS propagado com sucesso!
   Você pode prosseguir com o teste OAuth
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### Método 2: Testes Manuais

**Teste 1: dig**
```bash
dig +short 4sq.eliotools.site
# Esperado: 134.209.163.143
```

**Teste 2: curl**
```bash
curl -I http://4sq.eliotools.site/
# Esperado: HTTP/1.1 200 OK
```

**Teste 3: Browser**
```
http://4sq.eliotools.site/
```
Deve carregar a página de login do Foursquare Mass Editor Tools.

---

## 🔄 Reversão (Se Necessário)

Caso precise reverter para a configuração original:

### Passos para Reverter:

1. **Deletar** o A Record criado:
   ```
   A Record | 4sq → 134.209.163.143
   ```

2. **Criar** novamente o CNAME:
   ```
   Type:  CNAME Record
   Host:  4sq
   Value: eliotools.site
   TTL:   5 min
   ```

**Tempo de reversão:** 5-10 minutos (TTL de 5 minutos)

---

## ⏱️ Linha do Tempo Esperada

| Tempo | Ação |
|-------|------|
| T+0min | Salvar alteração no Namecheap |
| T+2min | Propagação para servidores Namecheap |
| T+5min | Propagação para DNS públicos (Google, Cloudflare) |
| T+10min | Propagação global completa |
| T+15min | Todos os ISPs propagados |

**Recomendação:** Aguardar **10 minutos** antes de testar OAuth.

---

## 🔐 Próximo Passo: Testar OAuth

Quando o DNS estiver propagado (script retorna ✅), você poderá:

1. Acessar: http://4sq.eliotools.site/
2. Clicar em **"Connect to this app via Foursquare"**
3. Autorizar no Foursquare
4. Verificar se o redirect funciona corretamente

**OAuth Redirect URI configurado:**
```
http://4sq.eliotools.site/index.php
```

---

## 🛠️ Troubleshooting

### Problema: DNS não propaga após 10 minutos

**Diagnóstico:**
```bash
# Verificar NS (nameservers) do domínio
dig NS eliotools.site

# Consultar diretamente nos nameservers Namecheap
dig @dns1.registrar-servers.com 4sq.eliotools.site
```

**Possíveis causas:**
- CloudFlare ou outro CDN ativo (verificar proxy laranja/cinza)
- Cache do navegador (testar em aba anônima)
- DNS local cached (executar: `sudo dscacheutil -flushcache` no Mac)

### Problema: HTTP 404 após DNS propagar

**Diagnóstico:**
```bash
# Verificar container Docker no servidor
ssh -i ~/.ssh/4sqmet_prod root@134.209.163.143 "docker ps"

# Ver logs do Apache
ssh -i ~/.ssh/4sqmet_prod root@134.209.163.143 "docker logs 4sqmet | tail -50"
```

---

## 📝 Checklist

Antes de prosseguir com SSL e HTTPS:

- [ ] DNS propagado (script retorna ✅)
- [ ] HTTP funcionando (http://4sq.eliotools.site/)
- [ ] OAuth testado com sucesso
- [ ] Redirect URI funcional (callback após login)

Após confirmar tudo funcionando, prosseguir com:
- [ ] Configurar SSL (Let's Encrypt)
- [ ] Descomentar HTTPS no apache-config-production.conf
- [ ] Testar HTTPS (https://4sq.eliotools.site/)

---

**Última atualização:** 1 de março de 2026  
**Servidor:** 134.209.163.143 (DigitalOcean NYC3)  
**Domínio:** 4sq.eliotools.site → eliotools.site (Namecheap)
