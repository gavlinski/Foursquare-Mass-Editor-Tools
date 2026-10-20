<?php

/**
 * Children Venues
 *
 * Importa os sublocais (children) de um local usando a API
 * https://api.foursquare.com/v2/venues/{VENUE_ID}/children
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION))
	session_start();
if (!isset($_SESSION["oauth_token"])) {
	header('Location: index.php');
	exit;
}

require_once __DIR__ . '/includes/asset_helper.php';
require_once __DIR__ . '/FoursquareAPI.Class.php';
include __DIR__ . '/includes/app_credentials.php';

function renderizarMensagem($mensagemHtml) {
	ob_start();
	dojo_theme('tundra');
	$tema = ob_get_clean();
	ob_start();
	dojo_script(["parseOnLoad" => true]);
	$dojo = ob_get_clean();
	echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Locais dentro de outro local</title>'
		. '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">' . $tema
		. '<link rel="stylesheet" type="text/css" href="estilo.css">' . $dojo
		. '<script>dojo.require("dijit.form.Button");</script></head><body class="tundra">'
		. $mensagemHtml
		. '<p><button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" style="margin-left: 0px;">Voltar</button></p>'
		. '</body></html>';
	exit;
}

$parentId = isset($_POST["parentId"]) ? trim((string)$_POST["parentId"]) : "";
if (!preg_match('/^[0-9a-f]{24}$/i', $parentId)) {
	renderizarMensagem('<p>Selecione o local onde os demais est&atilde;o localizados (nome, ID ou link).</p>');
}

$foursquare = new FoursquareAPI($client_key, $client_secret);
$foursquare->SetAccessToken($_SESSION["oauth_token"]);
$json = json_decode($foursquare->GetPrivate("venues/" . $parentId . "/children"));

if (!isset($json->meta->code)) {
	renderizarMensagem('<p>Ocorreu um erro desconhecido. Por favor, tente novamente.</p>');
}
if ($json->meta->code != 200) {
	renderizarMensagem('<p><b>Erro ' . (int)$json->meta->code . ':</b> '
		. htmlspecialchars($json->meta->errorType ?? '') . '</p><p><b>Detalhe:</b> '
		. htmlspecialchars($json->meta->errorDetail ?? '') . '</p>');
}

// A API pode devolver os locais em children.groups[].items[] ou children.items[]
$venues = [];
$children = $json->response->children ?? null;
if ($children) {
	foreach (($children->groups ?? []) as $grupo) {
		foreach (($grupo->items ?? []) as $item)
			$venues[] = $item;
	}
	foreach (($children->items ?? []) as $item)
		$venues[] = $item;
}

$venuesIds = [];
$file = [];
$unicos = [];
$venuesDados = [];
foreach ($venues as $venue) {
	if (isset($venue->id) && preg_match('/^[0-9a-f]{24}$/i', $venue->id) && !isset($unicos[$venue->id])) {
		$unicos[$venue->id] = true;
		$venuesIds[] = $venue->id;
		$file[] = "https://app.foursquare.com/v/" . $venue->id;
		$venuesDados[] = $venue;
	}
}

if (count($venuesIds) == 0) {
	renderizarMensagem('<p>Nenhum local encontrado dentro do local informado.</p>');
}

$campos = isset($_POST["campos5"]) ? $_POST["campos5"] : [];
$_SESSION["file"] = $file;
$_SESSION["venuesIds"] = $venuesIds;
$_SESSION["campos"] = $campos;
$data = json_encode(["meta" => ["code" => 200], "response" => ["venues" => $venuesDados]]);

echo '<!doctype html><html><head><meta charset="utf-8"><title>Importando...</title></head><body>';
echo '<script>localStorage.setItem("txt", ' . json_encode(implode('%0A,', $file)) . ');'
	. 'localStorage.setItem("venues", ' . json_encode($data) . ');'
	. 'window.location = "edit.php";</script></body></html>';
