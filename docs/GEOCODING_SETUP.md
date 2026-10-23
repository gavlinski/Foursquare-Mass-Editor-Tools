# Geocodificação e região das buscas

## Conversão de cidade/endereço em coordenadas

Quando a aplicação precisa transformar uma cidade ou endereço em `latitude,longitude`,
usa primeiro `GET /v2/geo/geocode` do Foursquare, autenticado com o token da sessão.
O primeiro centro geográfico válido da resposta é usado. Se a API falhar ou não
encontrar coordenadas, a aplicação tenta o Google Maps JavaScript Geocoder como
fallback. Coordenadas já informadas no formato `lat,lng` são usadas diretamente,
sem chamar nenhum geocoder.

O fallback Google requer `GOOGLE_MAPS_API_KEY` habilitada para Maps JavaScript API
e Geocoding, com o domínio da aplicação permitido nas restrições de HTTP referrer.
Não use essa chave em chamadas PHP/cURL. O fluxo principal é browser-side e não
requer uma `GOOGLE_MAPS_GEOCODING_KEY`.

As credenciais locais podem ser fornecidas por `includes/google_maps_credentials.php`.
Se esse arquivo estiver ausente (por exemplo, em um checkout limpo ou no CI), a
aplicação carrega `includes/google_maps_credentials.php.example`, que lê as
credenciais do ambiente e usa placeholders quando elas não estão configuradas.

O erro `REQUEST_DENIED` com `empty referer` ocorre em chamadas server-side, que não
enviam o Referer do navegador. A ferramenta opcional
`debug/test_geocoding_detailed.php?server_test=1` ainda pode testar esse fluxo
legado, mas não é usado pela geocodificação normal da aplicação. A página
`debug/test_geocoding.php` e o teste padrão de `debug/test_geocoding_detailed.php`
usam o helper compartilhado da aplicação.

## Região no autocomplete “Dentro”

Ao buscar um local pai na aba “Importar locais dentro de outro local”, o campo
Região é enviado à API de autocomplete como `near` quando contém uma cidade ou
endereço, por exemplo `near=Porto Alegre, RS`. Essa forma prioriza resultados
associados à região; converter o nome em `ll` e enviar apenas coordenadas não
produziu a mesma priorização nas buscas verificadas. Se o valor já estiver no
formato `lat,lng`, a chamada envia `ll` diretamente. Em ambos os casos a busca
usa `m=foursquare` e `group=unified`. Não envia `supervenueOnly=true`: esse filtro
remove locais que podem ser selecionáveis como local pai e, no caso verificado do
SERPRO, omitia a unidade de Porto Alegre apesar de `near` estar presente.

## Diagnóstico

1. Acesse `https://localhost/main.php` e abra “Importar locais dentro de outro local”.
2. Informe uma cidade no campo Região e um termo no campo Dentro.
3. Confirme que os resultados exibidos incluem a cidade/estado e que a chamada de
   autocomplete contém `near`, `m=foursquare` e `group=unified`, sem
   `supervenueOnly=true`.
4. Para testar conversão em coordenadas, use a pesquisa principal com uma cidade.
   Com uma sessão válida, a chamada `geo/geocode` do Foursquare deve ocorrer antes
   de qualquer fallback Google.

As requisições de autocomplete e geocodificação usam o token OAuth da sessão.
Não registre nem compartilhe URLs completas dessas requisições, pois elas incluem
o token.
