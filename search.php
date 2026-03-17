<?php

/**
 * Search Venues
 *
 * Pesquisa venues utilizando a API https://api.foursquare.com/v2/venues/search
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2012-2026
 * @version    3.1.0
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/search.php
 * @since      File available since Release 1.5
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt
 */

if (!isset($_SESSION))
	session_start();
if (!isset($_SESSION["oauth_token"])) {
	header('Location: index.php');
}

// Inclui helper de assets
require_once __DIR__ . '/includes/asset_helper.php';
?>
<!doctype html>
<html lang="pt-BR">
<head>
<title>Pesquisando...</title>
<meta charset="utf-8">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="pragma" content="no-cache">
<?php
define("VERSION", "Venues Searcher 3.1.0");

// Captura tag <link> e CSS dinâmico do ProgressBar
ob_start();
dojo_theme('tundra');
$DOJO_THEME_LINK = ob_get_clean();

define("LINKS", '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
' . $DOJO_THEME_LINK . '
<link rel="stylesheet" type="text/css" href="estilo.css">
');
ob_start();
dojo_script(["parseOnLoad" => true]);
define("DOJO_INIT", ob_get_clean());
define("HBODY", '</head>
<body class="tundra">
');
define("PESQUISANDO", LINKS . DOJO_INIT . HBODY . '<div id="carregando">Pesquisando venues&hellip;</div>
');
define("TEMPLATE1", '<script>dojo.require("dijit.form.Button");</script>
');
define("TEMPLATE2", '<p><button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" style="margin-left: 0px;">Voltar</button></p>
</body>
</html>');
define("ERRO01", LINKS . DOJO_INIT . HBODY . TEMPLATE1 . '
<script>
console.warn("⚠️ Geocodificação não disponível: GOOGLE_MAPS_GEOCODING_KEY não configurada");
console.info("ℹ️ Para habilitar geocodificação de endereços, o administrador deve configurar GOOGLE_MAPS_GEOCODING_KEY no arquivo .env");
console.info("📚 Documentação: docs/GEOCODING_SETUP.md");
</script>
<div style="max-width: 610px; margin: 40px auto; padding: 35px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
	<h2 style="color: #e67e22; margin-top: 0;">🗺️ Geocodifica&ccedil;&atilde;o n&atilde;o dispon&iacute;vel</h2>
	<p style="font-size: 1rem; line-height: 1.6; color: #333; margin: 20px 0;">
		A convers&atilde;o de endere&ccedil;os em coordenadas geogr&aacute;ficas n&atilde;o est&aacute; habilitada.
	</p>
	<p style="font-size: 1rem; line-height: 0.6; color: #333; margin: 20px 0;">
		Por favor, informe as coordenadas diretamente no formato <strong>latitude,longitude</strong>:
	</p>
	<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #3498db; margin: 20px 0;">
		<code style="font-family: \'Courier New\', monospace; font-size: 1.1rem; color: #2c3e50;">-29.9178,-51.1794</code>
	</div>
	<p style="font-size: 1rem; line-height: 0.6; color: #666; margin: 20px 0;">
		<strong>Onde encontrar coordenadas:</strong>
	</p>
	<ul style="font-size: 1rem; line-height: 1.6; color: #666;">
		<li>Google Maps: <a href="https://support.google.com/maps/answer/18539" target="_blank" style="color: #3498db;">clique com bot&atilde;o direito no mapa</a></li>
		<li>Foursquare: copie do campo <strong>Lat/Lng</strong> do local</li>
	</ul>
	<hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0 30px;">
	<p style="font-size: 0.95rem; line-height: 0.6; color: #95a5a6; margin: 0 0 30px 0;">
		💡 Para habilitar a busca por endere&ccedil;os, entre em contato com o administrador ou 
		<a href="https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/issues/new?title=Solicita%C3%A7%C3%A3o:%20Habilitar%20Geocodifica%C3%A7%C3%A3o&labels=feature" 
		   target="_blank" 
		   style="color: #3498db; text-decoration: none;">
			abra uma issue no GitHub
		</a>
	</p>
	<button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" style="margin-left: 0px;">Voltar</button>
</div>
</body>
</html>');
define("ERRO02", LINKS . DOJO_INIT . HBODY . TEMPLATE1 . '<p>Nenhuma venue encontrada nas coordenadas geogr&aacute;ficas informadas.</p>
<p>Verifique a latitude e longitude e tente novamente.</p>
' . TEMPLATE2);
define("ERRO03", LINKS . DOJO_INIT . HBODY . TEMPLATE1 . '
<script>
console.error("❌ Falha na geocodificação: local não encontrado ou API indisponível");
</script>
<div style="max-width: 650px; margin: 40px auto; padding: 35px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
	<h2 style="color: #e74c3c; margin-top: 0;">🌍 Endere&ccedil;o n&atilde;o encontrado</h2>
	<p style="font-size: 1.1rem; line-height: 1.6; color: #333; margin: 20px 0;">
		N&atilde;o foi poss&iacute;vel converter o endere&ccedil;o em coordenadas geogr&aacute;ficas.
	</p>
	<div style="background: #fff3cd; padding: 20px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 20px 0;">
		<p style="margin: 0 0 15px 0; font-weight: 600; color: #856404;">📍 Dicas para melhorar a busca:</p>
		<ul style="margin: 0; padding-left: 20px; color: #856404;">
			<li style="margin: 8px 0;">Use nomes completos: <strong>"Porto Alegre, RS"</strong> em vez de <strong>"POA"</strong></li>
			<li style="margin: 8px 0;">Adicione o estado: <strong>"Canoas, RS"</strong></li>
			<li style="margin: 8px 0;">Para cidades pequenas, adicione o pa&iacute;s: <strong>"Gramado, RS, Brasil"</strong></li>
			<li style="margin: 8px 0;">Evite abrevia&ccedil;&otilde;es muito espec&iacute;ficas</li>
		</ul>
	</div>
	<div style="background: #e8f4f8; padding: 20px; border-radius: 5px; border-left: 4px solid #3498db; margin: 20px 0;">
		<p style="margin: 0 0 10px 0; font-weight: 600; color: #1e5f7e;">💡 Alternativa: Use coordenadas diretamente</p>
		<p style="margin: 0 0 10px 0; color: #1e5f7e;">Formato: <code style="background: #fff; padding: 3px 8px; border-radius: 3px;">latitude,longitude</code></p>
		<p style="margin: 0; color: #5a9ab8; font-size: 0.95rem;">
			Exemplo: <code style="background: #fff; padding: 3px 8px; border-radius: 3px; color: #2c3e50;">-29.9178,-51.1794</code>
		</p>
	</div>
	<div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px;">
		<p style="margin: 0 0 10px 0; font-size: 0.95rem; color: #666;"><strong>Onde encontrar coordenadas:</strong></p>
		<ul style="margin: 0; padding-left: 20px; font-size: 0.95rem; color: #666;">
			<li style="margin: 5px 0;">🗺️ <a href="https://www.google.com.br/maps" target="_blank" style="color: #3498db;">Google Maps</a>: clique com bot&atilde;o direito no mapa</li>
			<li style="margin: 5px 0;">📍 Foursquare: copie do campo <strong>Lat/Lng</strong> do local</li>
		</ul>
	</div>
	<hr style="border: none; border-top: 1px solid #e0e0e0; margin: 25px 0;">
	<p style="font-size: 0.9rem; color: #95a5a6; margin: 0 0 20px 0;">
		⚠️ Se o problema persistir, a API do Google Maps pode estar temporariamente indispon&iacute;vel. Tente novamente em alguns minutos.
	</p>
	<p style="text-align: center;">
		<button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" style="margin-left: 0px;">Voltar e Tentar Novamente</button>
	</p>
</div>
</body>
</html>');
define("ERRO99", '<meta http-equiv="refresh" content="5; url=index.php">
' . LINKS . DOJO_INIT . '</head>
<body>
<p>Erro ao fazer a pesquisa.</p>
</body>
</html>');
define("EDIT", '<script>
	window.location = "edit.php"
</script>;');

if (isset($_POST["ll"]) && trim($_POST["ll"]) !== "")
	$params["ll"] = stripAccents($_POST["ll"]);
else {
	// Se ll não foi fornecido, exibe erro
	echo ERRO99;
	exit;
}
if (isset($_POST["categoryId"]))
	$params["categoryId"] = $_POST["categoryId"];
if ((isset($_POST["query"])) && ($_POST["query"] != ""))
	$params["query"] = $_POST["query"];
if (isset($_POST["limit"]))
	$params["limit"] = $_POST["limit"];
if (isset($_POST["intent"]))
	$params["intent"] = $_POST["intent"];
if (isset($_POST["radius"]))
	$params["radius"] = $_POST["radius"];

if (isset($params))
	$data = pesquisarVenues($params);
else
	echo ERRO99;

$_SESSION["file"] = filtrarArray($file);
if ($_SESSION["file"] == null) {
	$pbar->hide();
	echo ERRO02;
	exit;
}
$_SESSION["venuesIds"] = filtrarArray($venuesIds);
$_SESSION["campos"] = $_POST["campos4"];
setLocalCache("txt", implode('%0A,', $_SESSION["file"]));
setLocalCache("venues", addslashes($data));
echo EDIT;

/**
 * Remove acentos de uma string
 * 
 * Converte caracteres acentuados para seus equivalentes ASCII.
 * Útil para normalização de endereços e queries de busca.
 * Compatível com PHP 8.1+
 * 
 * @param string $str String com acentos
 * @return string String sem acentos
 */
function stripAccents($str) {
    // Normaliza para NFD (Normalization Form Decomposed) e remove marcas diacríticas
    if (function_exists('normalizer_normalize')) {
        $normalized = normalizer_normalize($str, Normalizer::FORM_D);
        // Remove caracteres de combinação (marcas diacríticas)
        return preg_replace('/\p{Mn}/u', '', $normalized);
    }
    
    // Fallback: mapeamento manual de caracteres
    $unwanted_array = [
        'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A',
        'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I',
        'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U',
        'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a',
        'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i',
        'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u',
        'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'
    ];
    return strtr($str, $unwanted_array);
}

/**
 * Filtra array removendo duplicatas
 * 
 * @param array $array Array a ser filtrado
 * @return array Array sem duplicatas e com índices reordenados
 */
function filtrarArray($array) {
	return array_values(array_unique($array));
}

/**
 * Pesquisa venues usando a API do Foursquare
 * 
 * Suporta pesquisas simples (até 50 resultados) e múltiplas (até 250 resultados)
 * usando diferentes coordenadas geográficas.
 * 
 * @param array $params Parâmetros da busca:
 *                      - ll: latitude,longitude ou endereço
 *                      - categoryId: ID da categoria (opcional)
 *                      - query: termo de busca (opcional)
 *                      - limit: número de resultados (1-250)
 *                      - intent: tipo de busca (checkin, browse, etc)
 *                      - radius: raio em metros (opcional)
 * @return string JSON com os resultados da busca
 */
function pesquisarVenues($params) {
	global $venuesIds;
	$venuesIds = array();
	global $file;
	$file = array();
		
	require_once("ProgressBar.Class.php");

	echo PESQUISANDO;
	$pbar = new ProgressBar();
	echo '<div style="width: 400px;">' . "\r\n";
	$pbar->render();
	echo '</div>' . "\r\n";

	require_once("FoursquareAPI.Class.php");

	/*** Set client key and secret ***/
	include 'includes/app_credentials.php';

	/*** Load the Foursquare API library ***/
	$foursquare = new FoursquareAPI($client_key, $client_secret);
	$foursquare -> SetAccessToken($_SESSION["oauth_token"]);
	
	/*** Leverages the Google Maps API to generate a lat/lng pair for a given address ***/
	// Se ll estiver vazio ou não for coordenadas válidas, tenta geocodificar
	if (!empty($params["ll"]) && (!preg_match('/^(\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?)$/', $params["ll"]))) {
		// Remove acentos do endereço para melhor compatibilidade com Google Maps API
		$endereco = stripAccents($params["ll"]);
		$coordinates = $foursquare -> GeoLocate($endereco);
		
		// Verifica se geocodificação foi bem-sucedida antes de acessar array
		if ($coordinates == null || !isset($coordinates["latitude"]) || !isset($coordinates["longitude"])) {
			$pbar->hide();
			// Verifica se é porque a API Key não está configurada ou porque falhou a geocodificação
			$mapsConfig = include __DIR__ . '/includes/google_maps_config.php';
			$hasGeocodingKey = isset($mapsConfig['google_maps_geocoding_key']) && 
			                    $mapsConfig['google_maps_geocoding_key'] !== null &&
			                    $mapsConfig['google_maps_geocoding_key'] !== 'YOUR_GOOGLE_MAPS_API_KEY';
			
			if (!$hasGeocodingKey) {
				echo ERRO01; // API Key não configurada
			} else {
				echo ERRO03; // Geocodificação falhou (local não encontrado ou API indisponível)
			}
			exit;
		}
		$params["ll"] = $coordinates["latitude"] . "," . $coordinates["longitude"];
	}
	
	// Extrai coordenadas centrais do parâmetro ll
	list($centerLat, $centerLng) = explode(',', $params["ll"]);
	$centerLat = floatval(trim($centerLat));
	$centerLng = floatval(trim($centerLng));
	
	// Calcula quadrantes adjacentes para buscas múltiplas (quando limit > 50)
	// Usa o raio fornecido ou valor padrão de 1000m
	$radius = isset($params["radius"]) ? intval($params["radius"]) : 1000;
	
	// Constantes de conversão: 1 grau ≈ 111km
	$latOffset = ($radius * 0.7) / 111000; // 0.7 = offset para evitar sobreposição excessiva
	$lngOffset = ($radius * 0.7) / (111000 * cos(deg2rad($centerLat)));
	
	$coordinates = array(
		"center" => $centerLat . "," . $centerLng,
		"southwest" => ($centerLat - $latOffset) . "," . ($centerLng - $lngOffset),
		"northeast" => ($centerLat + $latOffset) . "," . ($centerLng + $lngOffset),
		"southeast" => ($centerLat - $latOffset) . "," . ($centerLng + $lngOffset),
		"northwest" => ($centerLat + $latOffset) . "," . ($centerLng - $lngOffset)
	);
	
	/*** Perform a request to a authenticated-only resource ***/
	if ($params["limit"] <= 50) {
		$response = $foursquare -> GetPrivate("venues/search", $params);
		$json = json_decode($response);
		
	/*** Perform multiple requests at once to a authenticated-only resource ***/
	} else {
		$limit = $params["limit"];
		$params["limit"] = 50;
		$requests[] = array("endpoint" => "venues/search") + $params;
		if ($limit >= 100) {
			$params["ll"] = $coordinates["southwest"];
			$requests[] = array("endpoint" => "venues/search") + $params;
			if ($limit >= 150) {
				$params["ll"] = $coordinates["northeast"];
				$requests[] = array("endpoint" => "venues/search") + $params;
				if ($limit >= 200) {
					$params["ll"] = $coordinates["southeast"];
					$requests[] = array("endpoint" => "venues/search") + $params;
					if ($limit == 250) {
						$params["ll"] = $coordinates["northwest"];
						$requests[] = array("endpoint" => "venues/search") + $params;
					}
				}
			}
		}
		$responses = $foursquare -> GetMulti($requests);
		$json = json_decode($responses);
	}
		
	/**
	 * Extrai IDs e URLs das venues do response JSON da API
	 * 
	 * @param array $json_response_venues Array de venues do response da API
	 * @param ProgressBar $pbar Instância da barra de progresso
	 * @param int $size Tamanho total para cálculo de progresso
	 * @param int $i Índice inicial
	 * @return array Array com chaves 'venues' (IDs) e 'file' (URLs)
	 */
	function extrairVenuesIdsUrls($json_response_venues, $pbar, $size, $i) {
		$array = array();
		$s = count($json_response_venues);
		if ($size < 50)
			$delta = 1;
		else
			$delta = ($size/($size/50))/$s;
		//echo("\$size = $size, \$s = $s, \$delta = $delta<br>");
		foreach ($json_response_venues as $venue) {
			if (property_exists($venue, "id")) {
				$array["venues"][] = $venue->id;
				// Usa canonicalUrl se disponível (formato: https://app.foursquare.com/v/name/id)
				if (property_exists($venue, "canonicalUrl"))
					$array["file"][] = $venue->canonicalUrl;
				else
					$array["file"][] = "https://app.foursquare.com/v/" . $venue->id;
			}
			$i += $delta;
			//echo("\$i = $i,");
			$pbar->setProgressBarProgress($i*100/$size);
			usleep(50000*0.1);
		}
		return $array;
	}
	
	if ((isset($json->meta->code)) && ($json->meta->code == 200)) {
	
		/*** Single request ***/
		if (isset($json->response->venues)) {
			$size = count($json->response->venues);
			if ($size > 0) {
				$array = extrairVenuesIdsUrls($json->response->venues, $pbar, $size, 0);
				$venuesIds = $array["venues"];
				$file = $array["file"];
			} else {
				$pbar->hide();
				echo ERRO02;
				exit;
			}
			
		/*** Multi requests ***/
		} else if (isset($json->response->responses)) {
			$size = count($json->response->responses)*50;
			$response = json_decode($responses, true);
			$i = 0;
			$response_venues = array();
			foreach ($json->response->responses as $resp) {
				// Verifica se a resposta tem a estrutura esperada antes de acessar
				if (isset($resp->response->venues) && is_array($resp->response->venues) && count($resp->response->venues) > 0) {
					$array = extrairVenuesIdsUrls($resp->response->venues, $pbar, $size, $i);
					
					// Verifica se o array response tem a estrutura esperada
					$responseIndex = intval($i/50);
					if (isset($response["response"]["responses"][$responseIndex]["response"]["venues"])) {
						$r = $response["response"]["responses"][$responseIndex]["response"]["venues"];
						if (count($venuesIds) > 0) {
							foreach ($r as $key => &$value)
								if (!in_array($value["id"], $venuesIds))
									$response_venues["response"]["venues"][] = $r[$key];
								else
									unset($r[$key]); // remove venue duplicada
							unset($value);
						} else {
							$response_venues["response"]["venues"] = $r;
						}
					}
					$venuesIds = array_merge($venuesIds, $array["venues"]);
					$file = array_merge($file, $array["file"]);
					$i += 50;
				} else {
					$i += 50;
					$pbar->setProgressBarProgress($i*100/$size);
					usleep(50000*0.1);
				}
			}
			if (!isset($response_venues["response"]["venues"])) {
				$pbar->hide();
				echo ERRO02;
				exit;
			}

			unset($response["response"]);
			$response = json_encode(array_merge($response, $response_venues));
		}

	} else if (isset($json->meta->code)) {
		$pbar->hide();
		echo TEMPLATE1 . '<p><b>Erro ' . $json->meta->code . ':</b> ' . $json->meta->errorType . '</p>
<p><b>Detalhe:</b> ' . $json->meta->errorDetail . '</p>
' . TEMPLATE2;
		exit;
	} else {
		$pbar->hide();
		echo TEMPLATE1 . '<p>Ocorreu um erro desconhecido. Por favor, tente novamente. Caso o problema persista, reinicie o seu navegador.</p>
' . TEMPLATE2;
		exit;
	}

	return $response;
}

/**
 * Define cache local no localStorage do navegador
 * 
 * @param string $key Nome da chave no localStorage
 * @param string $data Dados a serem armazenados
 * @return void
 */
function setLocalCache($key, $data) {
	print('<script>'."\n\r".'	localStorage.setItem(\''.$key.'\', \''.$data.'\');'."\n\r".'</script>');
	print str_pad('', intval(ini_get('output_buffering'))) . "\n\r";
	flush();
}

?>