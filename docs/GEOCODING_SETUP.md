# 🗺️ Setup da Geocoding API (Server-Side)

Guia completo para configurar a geocodificação de endereços em coordenadas geográficas.

## 📊 Informações de Pricing

### Cota Gratuita
✅ **10.000 requisições gratuitas por mês**
- Renovadas todo dia 1º do mês (meia-noite Pacific Time)
- Sem custo financeiro dentro deste limite

### Custos Adicionais
Após os primeiros 10.000 requisições/mês:
- **$5,00 USD por 1.000 requisições** ($0,005 por requisição)
- Descontos automáticos por volume:
  - 100.001-500.000: $4,00 por 1.000
  - 500.001-2.000.000: $3,00 por 1.000
  - Acima de 2M: até $0,38 por 1.000

### 📚 Documentação Oficial
- [Pricing & Billing](https://developers.google.com/maps/billing-and-pricing/pricing)
- [Geocoding API Usage](https://developers.google.com/maps/documentation/geocoding/usage-and-billing)

---

## 🚀 Como Configurar

### Pré-requisitos
- Conta no Google Cloud Platform
- Projeto com faturamento habilitado (aceita cartão de crédito)
- Geocoding API habilitada no projeto

---

## Passo 1: Criar API Key para Server-Side

### 1.1 Acesse o Google Cloud Console
🔗 [Console de Credenciais](https://console.cloud.google.com/google/maps-apis/credentials)

### 1.2 Criar Nova Credencial
1. Clique em **"+ CREATE CREDENTIALS"**
2. Selecione **"API key"**
3. Uma nova chave será gerada

### 1.3 Configurar Restrições da API Key

**⚠️ CRÍTICO**: API Keys para geocodificação server-side devem usar **restrição de IP**, não HTTP referrers.

#### 🔧 Configure a API Key:

1. Clique em **"EDIT API KEY"** (ou no ícone de lápis ao lado da chave criada)

2. **Nome sugerido**: `Server-Side Geocoding Key`

3. **Application restrictions** → Selecione **"IP addresses"**
   ```
   # Desenvolvimento (Docker/localhost)
   172.17.0.0/16
   127.0.0.1
   ::1
   
   # Produção (adicione o IP público do seu servidor)
   203.0.113.45     # Exemplo: substitua pelo IP real
   ```

4. **API restrictions** → Selecione **"Restrict key"**
   - ✅ Marque apenas: **Geocoding API**
   - ❌ Desmarque: Maps JavaScript API, Places API, etc.

5. Clique em **"SAVE"**

---

## Passo 2: Habilitar Geocoding API

### 2.1 Acesse a API Library
🔗 [API Library - Geocoding](https://console.cloud.google.com/marketplace/product/google/geocoding-backend.googleapis.com)

### 2.2 Habilitar API
1. Clique em **"ENABLE"**
2. Aguarde a ativação (pode levar alguns segundos)

### 2.3 Verificar Status
🔗 [Geocoding API Status](https://console.cloud.google.com/google/maps-apis/apis/geocoding-backend.googleapis.com)
- Status deve mostrar: **✅ Enabled**

---

## Passo 3: Configurar no Projeto

### 3.1 Editar arquivo `.env`

Adicione a nova API Key ao arquivo `.env` na raiz do projeto:

```bash
# Google Maps Credentials
GOOGLE_MAPS_API_KEY=AIzaSy...ABC123           # Chave JavaScript (já existente)
GOOGLE_MAPS_MAP_ID=abc123...xyz                # Map ID (já existente)

# Nova chave para Geocoding Server-Side
GOOGLE_MAPS_GEOCODING_KEY=AIzaSy...XYZ789     # ← ADICIONE ESTA LINHA
```

### 3.2 Reiniciar Container (para aplicar variável)

```bash
./dev.sh restart
# ou
docker-compose down && docker-compose up -d
```

---

## 🧪 Testar a Configuração

### Teste 1: Diagnóstico Detalhado
Acesse no navegador:
```
https://localhost/debug/test_geocoding_detailed.php
```

Verifique:
- ✅ **Status da API**: deve mostrar "OK" com fundo verde
- ✅ **Coordenadas**: deve exibir latitude/longitude
- ❌ **REQUEST_DENIED**: chave com restrição incorreta
- ❌ **OVER_QUERY_LIMIT**: cota excedida

### Teste 2: Busca de Venues
1. Acesse: `https://localhost/main.php`
2. Aba **"Pesquisar locais"**
3. Campo **"Local"**: Digite `Canoas, RS`
4. Clique em **"Pesquisar"**
5. Deve carregar venues da região de Canoas

---

## 🔍 Troubleshooting

### Erro: "REQUEST_DENIED"
**Causa**: Restrição de API Key incorreta

**Solução**:
1. Verifique se usou **IP addresses** (não HTTP referrers)
2. Confirme que o IP do servidor está na lista
3. Para Docker local, adicione: `172.17.0.0/16`

### Erro: "OVER_QUERY_LIMIT"
**Causa**: Cota de 10.000 requisições/mês excedida

**Soluções**:
1. Aguarde virada do mês para renovar cota
2. Habilite faturamento no projeto (será cobrado $5 por 1.000 adicionais)
3. Monitore uso em: [Quotas & Usage](https://console.cloud.google.com/google/maps-apis/quotas)

### Erro: "ZERO_RESULTS"
**Causa**: Endereço não encontrado

**Solução**:
1. Verifique se o endereço existe no Google Maps
2. Use coordenadas diretas: `-29.9178,-51.1794`

### Geocodificação Não Disponível
Se a variável `GOOGLE_MAPS_GEOCODING_KEY` não estiver configurada:
- Sistema exibe mensagem clara pedindo coordenadas diretas
- Não há erro fatal
- Usuário pode continuar usando formato lat,lng

---

## 📊 Monitorar Uso

### Console de Quotas
🔗 [Metrics & Usage](https://console.cloud.google.com/google/maps-apis/metrics)

Você pode:
- Ver requisições por dia/mês
- Configurar alertas de cota
- Analisar custos estimados

### Configurar Alertas
1. Acesse: [Billing Budgets](https://console.cloud.google.com/billing/budgets)
2. Crie orçamento: ex. $10/mês
3. Configure alertas em 50%, 80%, 100%

---

## 🔐 Boas Práticas

### Segurança
✅ **SEMPRE**:
- Use restrições de IP em API Keys server-side
- Mantenha `.env` no `.gitignore`
- Rotacione chaves periodicamente (a cada 6 meses)

❌ **NUNCA**:
- Commite API Keys no Git
- Use mesma key para JavaScript e server-side
- Deixe API Key sem restrições

### Monitoramento
- Configure alertas de uso
- Revise logs mensalmente
- Monitore requisições anômalas

### Economia
- Use coordenadas diretas quando possível
- Cache resultados de geocodificação (quando aplicável)
- 10k requisições/mês gratuitas é suficiente para maioria dos casos

---

## 💡 Alternativas

### Sem Geocoding API Key
Se não configurar `GOOGLE_MAPS_GEOCODING_KEY`:
- Sistema funciona normalmente
- Usuário deve fornecer coordenadas diretas: `-29.9178,-51.1794`
- Mensagem de erro é clara e informativa

### Outras APIs de Geocoding (futuro)
- OpenStreetMap Nominatim (gratuito, sem chave)
- HERE Geocoding API (25k transações/mês grátis)
- Mapbox Geocoding (100k requisições/mês grátis)

---

## 📞 Suporte

### Links Úteis
- [Google Cloud Console](https://console.cloud.google.com)
- [Maps Platform Support](https://developers.google.com/maps/support)
- [Stack Overflow - google-maps-api-3](https://stackoverflow.com/questions/tagged/google-maps-api-3)

### Contato
Para problemas específicos deste projeto:
- Abra issue no GitHub: [Foursquare-Mass-Editor-Tools/issues](https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/issues)

---

**Última atualização**: Março 2026  
**Versão do guia**: 1.0
