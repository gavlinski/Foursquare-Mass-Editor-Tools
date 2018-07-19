<?php

/**
 * Main Page
 *
 * Página principal de acesso às ferramentas de importação e pesquisa
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2012-2018
 * @version    2.3.2
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/main.php
 * @since      File available since Release 1.5
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */
 
$VERSAO = "2.3.2";

if (!isset($_SESSION))
	session_start();
if (isset($_SESSION["oauth_token"])) {
	$oauth_token = $_SESSION["oauth_token"];
} else {
	header('Location: index.php');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<title>Elio Tools</title>
<meta charset="utf-8">
<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script src="js/main.js"></script>
<?php
$cache_file = "/tmp/cache-" . md5($_SERVER['REQUEST_URI']);
if (file_exists($cache_file) && (filemtime($cache_file) > (time() - 3600 * 12))) {
	// Cache file is less than 12 hours old. 
	// Don't bother refreshing, just use the file as-is.
	$response = file_get_contents($cache_file);
	$categories = json_decode($response);
	$categories_loaded = true;
} else {
	// Our cache is out-of-date, so load the data from our remote server.
	$response = carregarListaCategorias();
	if (empty($response)) {
		$categories = new stdClass();
		$categories->meta = "";
	} else {
		$categories = json_decode($response);
	}
	if (property_exists($categories->meta, "code") && ($categories->meta->code == "200")) {
		// JSON data is valid so save it over our cache for next time.
		$categories_loaded = true;
		file_put_contents($cache_file, $response, LOCK_EX);
		setLocalCache("categorias", $response);
	} else {
		// JSON data is invalid so delete it and clear cache.
		$categories_loaded = false;
		if (file_exists($cache_file))
			unlink($cache_file);
		removeLocalCache("categorias");
	}
}

function carregarListaCategorias() {
	require_once("FoursquareAPI.Class.php");

	/*** Set client key and secret ***/
	include 'includes/app_credentials.php';

	/*** Load the Foursquare API library ***/
	$foursquare = new FoursquareAPI($client_key, $client_secret);
	$foursquare -> SetAccessToken($_SESSION["oauth_token"]);
	
	return $foursquare->GetPrivate("venues/categories");
}

function setLocalCache($key, $data) {
	print('<script>localStorage.setItem(\''.$key.'\', \''.str_replace("'", "\'", $data).'\');</script>');
	print str_pad('', intval(ini_get('output_buffering')));
	flush();
}

function removeLocalCache($key) {
	print('<script>if (localStorage && localStorage.getItem(\''.$key.'\')) localStorage.removeItem(\''.$key.'\');</script>');
	print str_pad('', intval(ini_get('output_buffering')));
	flush();
}
?>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" type="text/css" href="js/dijit/themes/tundra/tundra.css">
<link rel="stylesheet" type="text/css" href="estilo.css">
</head>
<body class="tundra">
<header>
	<h2>Edi&ccedil;&atilde;o de venues em massa via API <span>(v<?php echo $VERSAO; ?>)</span></h2>
</header>
<article id="intro">
<?php
if ((isset($_COOKIE['name'])) && (strlen($_COOKIE['name']) > 0))
	//echo "<p>Ol&aacute;, <span id=\"name\">" . $_COOKIE['name'] . "</span>!</p>";
	echo "<p>Ol&aacute;, " . $_COOKIE['name'] . "!</p>";
?>
	<p>Este aplicativo usa a API do Foursquare&reg;, mas n&atilde;o &eacute; endossado ou certificado pelo Foursquare Labs, Inc. Todos os logos do Foursquare&reg; e marcas registradas exibidas neste aplicativo s&atilde;o de propriedade do Foursquare Labs, Inc.</p>
</article>
<article id="options">
	<div id="accordion" dojoType="dijit.layout.AccordionContainer" doLayout="false">
		<div dojoType="dijit.layout.ContentPane" title="Importar dados de um arquivo CSV">
			<div dojoType="dijit.form.Form" enctype="multipart/form-data" accept-charset="utf-8" id="f_csv" jsId="f_csv" action="load_csv.php" method="post">
				<section class="toolcontainer">
					<div class="row">
						<div class="filelabel"><label for="uploader_csv"><a id="dlg_csv" href="javascript:showDialogCsv();">Arquivo</a>:</label></div>
						<div class="uploadbutton">
						  <input type="hidden" name="MAX_FILE_SIZE" value="500000" dojoType="dijit.form.TextBox">
						  <input name="csv" multiple="false" type="file" data-dojo-type="dojox.form.Uploader" label="Escolher arquivo" id="uploader_csv">
						</div>
						<div class="selectedfile" id="arquivo_csv">Nenhum arquivo selecionado</div>
					</div>
				</section>
				<!--
				<button dojoType="dijit.form.Button" type=button onClick="console.log(f.getValues())">
					Get Values from form!
				</button>
				-->
				<button dojoType="dijit.form.Button" type="submit" class="continue">
					Continuar
				</button>
			</div>
		</div>
		<div dojoType="dijit.layout.ContentPane" title="Importar lista de um arquivo de texto simples">
			<div dojoType="dijit.form.Form" enctype="multipart/form-data" accept-charset="utf-8" id="f_txt" jsId="f_txt" action="load.php" method="post">
				<section class="toolcontainer">
					<div class="row">
						<div class="filelabel"><label for="txt"><a id="dlg_txt" href="javascript:showDialogTxt();">Arquivo</a>:</label></div>
						<div class="uploadbutton">
							<input type="hidden" name="MAX_FILE_SIZE" value="5000000" dojoType="dijit.form.TextBox">
							<input name="txt" multiple="false" type="file" data-dojo-type="dojox.form.Uploader" label="Escolher arquivo" id="uploader_txt">
							</div>
						<div class="selectedfile" id="arquivo_txt">Nenhum arquivo selecionado</div>
					</div>
					<div class="row">
						<div class="fieldslabel"><label for="campos1">Campos:</label></div>
						<div class="checkboxes">
							<div class="checkbox" style="width: 6.8em;">
								<input id="nome1" name="campos1[]" dojoType="dijit.form.CheckBox" value="nome">
								<label for="nome1">Nome</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="endereco1" name="campos1[]" dojoType="dijit.form.CheckBox" value="endereco">
								<label for="endereco1">Endere&ccedil;o</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="ruatransversal1" name="campos1[]" dojoType="dijit.form.CheckBox" value="ruatransversal">
								<label for="ruatransversal1">Rua transversal</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="bairro1" name="campos1[]" dojoType="dijit.form.CheckBox" value="bairro">
								<label for="bairro1">Bairro</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="cidade1" name="campos1[]" dojoType="dijit.form.CheckBox" value="cidade">
								<label for="cidade1">Cidade</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="estado1" name="campos1[]" dojoType="dijit.form.CheckBox" value="estado">
								<label for="estado1">Estado</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="codigopostal1" name="campos1[]" dojoType="dijit.form.CheckBox" value="codigopostal">
								<label for="codigopostal1">C&oacute;digo postal</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="dentro1" name="campos1[]" dojoType="dijit.form.CheckBox" value="dentro">
								<label for="dentro1">Dentro</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="telefone1" name="campos1[]" dojoType="dijit.form.CheckBox" value="telefone">
								<label for="telefone1">Telefone</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="sitedaweb1" name="campos1[]" dojoType="dijit.form.CheckBox" value="sitedaweb">
								<label for="sitedaweb1">Site da web</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="twitter1" name="campos1[]" dojoType="dijit.form.CheckBox" value="twitter">
								<label for="twitter1">Twitter</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="facebook1" name="campos1[]" dojoType="dijit.form.CheckBox" value="facebook">
								<label for="facebook1">Facebook</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="instagram1" name="campos1[]" dojoType="dijit.form.CheckBox" value="instagram">
								<label for="instagram1">Instagram</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="latlng1" name="campos1[]" dojoType="dijit.form.CheckBox" value="latlng">
								<label for="latlng1">Lat/Lng</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="descricao1" name="campos1[]" dojoType="dijit.form.CheckBox" value="descricao">
								<label for="descricao1">Descri&ccedil;&atilde;o</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="menu1" name="campos1[]" dojoType="dijit.form.CheckBox" value="menu" disabled>
								<label for="menu1">Menu</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="horas1" name="campos1[]" dojoType="dijit.form.CheckBox" value="horas" disabled>
								<label for="horas1">Horas</label>
							</div>
						</div>
					</div>
				</section>
				<!--
				<button dojoType="dijit.form.Button" type=button onClick="console.log(f_txt.getValues())">
					Get Values from form!
				</button>
				-->
				<button dojoType="dijit.form.Button" type="submit" class="continue">
					Continuar
				</button>
			</div>
		</div>
		<div dojoType="dijit.layout.ContentPane" title="Importar lista de uma p&aacute;gina web">
			<div dojoType="dijit.form.Form" enctype="multipart/form-data" accodigopostalt-charset="utf-8" id="f_lks" jsId="f_lks" action="load.php" method="post">
				<section class="toolcontainer">
					<div class="row">
						<div class="urlinputlabel"><label for="pagina"><a id="dlg_lks" href="javascript:showDialogLks();">Endere&ccedil;o</a>:</label></div>
						<div class="urlinput"><input type="text" id="pagina" name="pagina" required="true" dojoType="dijit.form.ValidationTextBox" trim="true" style="width: 50.2em; margin-bottom: 3px"/></div>
					</div>
					<div class="row">
						<div class="fieldslabel"><label for="campos2">Campos:</label></div>
						<div class="checkboxes">
							<div class="checkbox" style="width: 6.8em;">
								<input id="nome2" name="campos2[]" dojoType="dijit.form.CheckBox" value="nome">
								<label for="nome2">Nome</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="endereco2" name="campos2[]" dojoType="dijit.form.CheckBox" value="endereco">
								<label for="endereco2">Endere&ccedil;o</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="ruatransversal2" name="campos2[]" dojoType="dijit.form.CheckBox" value="ruatransversal">
								<label for="ruatransversal2">Rua transversal</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="bairro2" name="campos2[]" dojoType="dijit.form.CheckBox" value="bairro">
								<label for="bairro2">Bairro</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="cidade2" name="campos2[]" dojoType="dijit.form.CheckBox" value="cidade">
								<label for="cidade2">Cidade</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="estado2" name="campos2[]" dojoType="dijit.form.CheckBox" value="estado">
								<label for="estado2">Estado</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="codigopostal2" name="campos2[]" dojoType="dijit.form.CheckBox" value="codigopostal">
								<label for="codigopostal2">C&oacute;digo postal</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="dentro2" name="campos2[]" dojoType="dijit.form.CheckBox" value="dentro">
								<label for="dentro2">Dentro</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="telefone2" name="campos2[]" dojoType="dijit.form.CheckBox" value="telefone">
								<label for="telefone2">Telefone</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="sitedaweb2" name="campos2[]" dojoType="dijit.form.CheckBox" value="sitedaweb">
								<label for="sitedaweb2">Site da web</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="twitter2" name="campos2[]" dojoType="dijit.form.CheckBox" value="twitter">
								<label for="twitter2">Twitter</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="facebook2" name="campos2[]" dojoType="dijit.form.CheckBox" value="facebook">
								<label for="facebook2">Facebook</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="instagram2" name="campos2[]" dojoType="dijit.form.CheckBox" value="instagram">
								<label for="instagram2">Instagram</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="latlng2" name="campos2[]" dojoType="dijit.form.CheckBox" value="latlng">
								<label for="latlng2">Lat/Lng</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="descricao2" name="campos2[]" dojoType="dijit.form.CheckBox" value="descricao">
								<label for="descricao2">Descri&ccedil;&atilde;o</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="menu2" name="campos2[]" dojoType="dijit.form.CheckBox" value="menu" disabled>
								<label for="menu2">Menu</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="horas2" name="campos2[]" dojoType="dijit.form.CheckBox" value="horas" disabled>
								<label for="horas2">Horas</label>
							</div>
						</div>
					</div>
				</section>
				<!--
				<button dojoType="dijit.form.Button" type=button onClick="console.log(f_lks.getValues())">
					Get Values from form!
				</button>
				-->
				<button dojoType="dijit.form.Button" type="submit" class="continue">
					Continuar
				</button>
			</div>
		</div>
		<div dojoType="dijit.layout.ContentPane" title="Informar IDs ou URLs das venues">
			<div dojoType="dijit.form.Form" enctype="multipart/form-data" accodigopostalt-charset="utf-8" id="f_ids" jsId="f_ids" action="load.php" method="post">
				<section class="toolcontainer">
					<div class="row">
						<div class="urlstextarealabel"><label for="textarea_ids"><a id="dlg_ids" href="javascript:showDialogIds();">IDs ou URLs</a>:</label></div>
						<div class="urlstextarea"><textarea id="textarea_ids" name="textarea" dojoType="dijit.form.SimpleTextarea" maxLength="4000" trim="true" style="font-family: Arial, Helvetica, Verdana, sans-serif; font-size: 13px; resize: none; width: 647px; height: 59px;"></textarea></div>
					</div>
					<div class="row">
						<div class="fieldslabel"><label for="campos3">Campos:</label></div>
						<div class="checkboxes">
							<div class="checkbox" style="width: 6.8em;">
								<input id="nome3" name="campos3[]" dojoType="dijit.form.CheckBox" value="nome">
								<label for="nome3">Nome</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="endereco3" name="campos3[]" dojoType="dijit.form.CheckBox" value="endereco">
								<label for="endereco3">Endere&ccedil;o</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="ruatransversal3" name="campos3[]" dojoType="dijit.form.CheckBox" value="ruatransversal">
								<label for="ruatransversal3">Rua transversal</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="bairro3" name="campos3[]" dojoType="dijit.form.CheckBox" value="bairro">
								<label for="bairro3">Bairro</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="cidade3" name="campos3[]" dojoType="dijit.form.CheckBox" value="cidade">
								<label for="cidade3">Cidade</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="estado3" name="campos3[]" dojoType="dijit.form.CheckBox" value="estado">
								<label for="estado3">Estado</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="codigopostal3" name="campos3[]" dojoType="dijit.form.CheckBox" value="codigopostal">
								<label for="codigopostal3">C&oacute;digo postal</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="dentro3" name="campos3[]" dojoType="dijit.form.CheckBox" value="dentro">
								<label for="dentro3">Dentro</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="telefone3" name="campos3[]" dojoType="dijit.form.CheckBox" value="telefone">
								<label for="telefone3">Telefone</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="sitedaweb3" name="campos3[]" dojoType="dijit.form.CheckBox" value="sitedaweb">
								<label for="sitedaweb3">Site da web</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="twitter3" name="campos3[]" dojoType="dijit.form.CheckBox" value="twitter">
								<label for="twitter3">Twitter</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="facebook3" name="campos3[]" dojoType="dijit.form.CheckBox" value="facebook">
								<label for="facebook3">Facebook</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="instagram3" name="campos3[]" dojoType="dijit.form.CheckBox" value="instagram">
								<label for="instagram3">Instagram</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="latlng3" name="campos3[]" dojoType="dijit.form.CheckBox" value="latlng">
								<label for="latlng3">Lat/Lng</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="descricao3" name="campos3[]" dojoType="dijit.form.CheckBox" value="descricao">
								<label for="descricao3">Descri&ccedil;&atilde;o</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="menu3" name="campos3[]" dojoType="dijit.form.CheckBox" value="menu" disabled>
								<label for="menu3">Menu</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="horas3" name="campos3[]" dojoType="dijit.form.CheckBox" value="horas" disabled>
								<label for="horas3">Horas</label>
							</div>
						</div>
					</div>
				</section>
				<!--
				<button dojoType="dijit.form.Button" type=button onClick="console.log(f_ids.getValues())">
					Get Values from form!
				</button>
				-->
				<button dojoType="dijit.form.Button" type="submit" class="continue">
					Continuar
				</button>
			</div>
		</div>
		<div dojoType="dijit.layout.ContentPane" title="Pesquisar venues" selected="true">
			<div dojoType="dijit.form.Form" enctype="multipart/form-data" accodigopostalt-charset="utf-8" id="f_src" jsId="f_src" action="search.php" method="post">
				<input type="hidden" id="oauth_token_scr" name="oauth_token" value="<?= $oauth_token ?>"/>
				<section class="toolcontainer">
					<div class="row">
						<div class="queryinputlabel"><label for="query"><a href="https://developer.foursquare.com/docs/venues/search" target="_blank">Consulta</a>:</label></div>
						<div class="queryinput"><input type="text" id="query" name="query" required="false" dojoType="dijit.form.ValidationTextBox" trim="true" style="width: 299px"></div>
						<div class="llinputlabel"><label for="ll"><a href="https://developer.foursquare.com/docs/venues/search" target="_blank">Local</a>:</label></div>
						<div class="llinput"><input type="text" id="ll" name="ll" required="false" dojoType="dijit.form.ValidationTextBox" trim="true" style="width: 296px"/></div>
					</div>
					<div class="row">
						<div class="categoryidcomboboxlabel"><label for="categoryId"><a href="https://developer.foursquare.com/docs/venues/search" target="_blank">Categoria</a>:</label></div>
						<div class="categoryidcombobox">
							<div class="combobox">
								<select data-dojo-id="categoryId" name="categoryId" id="categoryId" data-dojo-type="dijit/form/FilteringSelect">
<?php
	$options = '									<option value=""></option>
	';
	if ($categories_loaded) {
		$categories_names = array();
	
		foreach ($categories->response->categories as $category):
			if (property_exists($category, "categories"))
				foreach ($category->categories as $category2):
					if (property_exists($category2, "name")) {
						$categories_names[$category2->id] = $category2->name;
					}
					if (property_exists($category2, "categories"))
						foreach ($category2->categories as $category3):
							if (property_exists($category3, "name")) {
								$categories_names[$category3->id] = $category3->name;
							}
							if (property_exists($category3, "categories"))
								foreach ($category3->categories as $category4):
									if (property_exists($category4, "name")) {
										$categories_names[$category4->id] = $category4->name;
									}
								endforeach;
						endforeach;
				endforeach;
		endforeach;

		asort($categories_names);
		foreach ($categories_names as $key => $val) {
			$options .=
	'									<option value="' . $key . '">' . $val . '</option>
	';
		}
	}

	echo $options;
?>
								</select>
							</div>
						</div>

						<div class="radiuscomboboxlabel"><label for="radius"><a href="https://developer.foursquare.com/docs/venues/search" target="_blank">Raio</a>:</label></div>
						<div class="radiuscombobox">
							<div class="comboboxes">
								<select data-dojo-id="radius" name="radius" id="radius" data-dojo-type="dijit/form/Select">
									<option value="">Padr&atilde;o</option>
									<option value="50">50 m</option>
									<option value="100">100 m</option>
									<option value="250">250 m</option>
									<option value="500">500 m</option>
									<option value="1000">1 km</option>
									<option value="5000">5 km</option>
									<option value="10000">10 km</option>
									<option value="50000">50 km</option>
									<!--<option value="100000">100 km</option>-->
								</select>
							</div>
						</div>
						<div class="intentcomboboxlabel"><label for="intent"><a href="https://developer.foursquare.com/docs/venues/search" target="_blank">Inten&ccedil;&atilde;o</a>:</label></div>
						<div class="intentcombobox">
							<div class="comboboxes">
								<select data-dojo-id="intent" name="intent" id="intent" data-dojo-type="dijit/form/Select">
									<option value="checkin">checkin</option>
									<option value="browse">browse</option>
									<option value="global">global</option>
									<option value="match">match</option>
								</select>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="limitcomboboxlabel"><label for="limit"><a href="https://developer.foursquare.com/docs/venues/search" target="_blank">Limite</a>:</label></div>
						<div class="limitcombobox">
							<div class="comboboxes">
								<select data-dojo-id="limit" name="limit" id="limit" id="intent" style="margin-bottom: 3px" data-dojo-type="dijit/form/Select">
									<option value="10">10</option>
									<option value="25">25</option>
									<option value="50" selected="selected">50</option>
									<option value="100">100</option>
									<option value="150">150</option>
									<option value="200">200</option>
									<option value="250">250</option>
								</select>
							</div>
						</div>

					</div>
					<div class="row">
						<div class="fieldslabel"><label for="campos4">Campos:</label></div>
						<div class="checkboxes">
							<div class="checkbox" style="width: 6.8em;">
								<input id="nome4" name="campos4[]" dojoType="dijit.form.CheckBox" value="nome">
								<label for="nome4">Nome</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="endereco4" name="campos4[]" dojoType="dijit.form.CheckBox" value="endereco">
								<label for="endereco4">Endere&ccedil;o</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="ruatransversal4" name="campos4[]" dojoType="dijit.form.CheckBox" value="ruatransversal">
								<label for="ruatransversal4">Rua transversal</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="bairro4" name="campos4[]" dojoType="dijit.form.CheckBox" value="bairro" disabled>
								<label for="bairro4">Bairro</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="cidade4" name="campos4[]" dojoType="dijit.form.CheckBox" value="cidade">
								<label for="cidade4">Cidade</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="estado4" name="campos4[]" dojoType="dijit.form.CheckBox" value="estado">
								<label for="estado4">Estado</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="codigopostal4" name="campos4[]" dojoType="dijit.form.CheckBox" value="codigopostal">
								<label for="codigopostal4">C&oacute;digo postal</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="dentro4" name="campos4[]" dojoType="dijit.form.CheckBox" value="dentro" disabled>
								<label for="dentro4">Dentro</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="telefone4" name="campos4[]" dojoType="dijit.form.CheckBox" value="telefone">
								<label for="telefone4">Telefone</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="sitedaweb4" name="campos4[]" dojoType="dijit.form.CheckBox" value="sitedaweb">
								<label for="sitedaweb4">Site da web</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="twitter4" name="campos4[]" dojoType="dijit.form.CheckBox" value="twitter">
								<label for="twitter4">Twitter</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="facebook4" name="campos4[]" dojoType="dijit.form.CheckBox" value="facebook">
								<label for="facebook4">Facebook</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="instagram4" name="campos4[]" dojoType="dijit.form.CheckBox" value="instagram" disabled>
								<label for="instagram4">Instagram</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="latlng4" name="campos4[]" dojoType="dijit.form.CheckBox" value="latlng">
								<label for="latlng4">Lat/Lng</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="descricao4" name="campos4[]" dojoType="dijit.form.CheckBox" value="descricao" disabled>
								<label for="descricao4">Descri&ccedil;&atilde;o</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="menu4" name="campos4[]" dojoType="dijit.form.CheckBox" value="menu" disabled>
								<label for="menu4">Menu</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="horas4" name="campos4[]" dojoType="dijit.form.CheckBox" value="horas" disabled>
								<label for="horas4">Horas</label>
							</div>
						</div>
					</div>
				</section>
				<!--
					https://api.foursquare.com/v2/multi?v=20120321&requests=
					%2Fvenues%2Fsearch%3Fll%3D-16.01670379538501%252C-48.06514263153076,
					%2Fvenues%2Fsearch%3Fll%3D-16.11670379538501%252C-48.06514263153076 -->
				<div>
					<button dojoType="dijit.form.Button" type="submit" class="continue">
						Continuar
					</button>
					<!--<button dojoType="dijit.form.Button" type=button onClick="console.log(f_src.getValues())">
						Get Values from form!
					</button>-->
				</div>
			</div>
		</div>
	</div>
</article>
<footer id="links">
	<div id="fixedlinks">
		<div class="social">
			<a href="https://github.com"><img src="img/GitHub-Mark-16px.png"></a><a href="https://github.com/gavlinski">gavlinski</a> / <a href="https://github.com/gavlinski/Foursquare-Mass-Editor-Tools">Foursquare-Mass-Editor-Tools</a> / <a href="https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/releases">releases</a>
		</div>
		<div class="social">
			<a href="https://groups.google.com/d/overview"><img src="img/groups-16.png"></a><a href="https://groups.google.com/group/brazilian-4sq-superusers-forum">Brazilian 4SQ Superusers Forum</a>
		</div>
		<div class="social">
			<a href="https://twitter.com"><img src="img/twitter-bird-16x16.png"></a><a href="https://twitter.com/gavlinski">@gavlinski</a>
		</div>
	</div>
</footer>
<!-- Start of StatCounter Code for Default Guide -->
<script type="text/javascript">
var sc_project=7288306;
var sc_invisible=1;
var sc_security="a38fdf67";
</script>
<script type="text/javascript"
src="https://www.statcounter.com/counter/counter.js"
async></script>
<noscript><div class="statcounter"><a title="Web Analytics"
href="http://statcounter.com/" target="_blank"><img
class="statcounter"
src="//c.statcounter.com/7288306/0/a38fdf67/1/" alt="Web
Analytics"></a></div></noscript>
<!-- End of Statcounter Code -->

<!-- End of StatCounter Code for Default Guide -->
</body>
</html>
