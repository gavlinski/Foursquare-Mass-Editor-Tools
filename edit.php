<?php

declare(strict_types=1);

/**
 * List Venues Editor
 *
 * Edição de venues a partir dos campos e dados recebidos do load.php
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2011-2026
 * @version    3.1.0
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/edit.php
 * @since      File available since Release 0.5
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */

// Headers anti-cache para desenvolvimento
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Analytics interno (privacy-friendly)
require_once __DIR__ . '/analytics.php';

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

use ElioTools\Security\SessionManager;

$sessionManager = new SessionManager();
$sessionManager->start();
$oauthToken = $sessionManager->getAccessToken();

if ($oauthToken && ($sessionManager->get('file') != null)) {
    $file = $sessionManager->get('file');
    $venuesIds = $sessionManager->get('venuesIds');
    $campos = $sessionManager->get('campos');
    if ($sessionManager->has('venues')) {
        $sessionManager->remove('venues');
    }
} else {
    header('Location: index.php');
    exit();
}

// Set client key and secret
include 'includes/app_credentials.php';
include_once 'includes/asset_helper.php';
?>
<!doctype html>
<html lang="pt-BR">
<head>
<title>Elio Tools - Editor de Venues</title>
<meta charset="utf-8">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<?php dojo_theme('tundra'); ?>
<link rel="stylesheet" type="text/css" href="estilo.css?v=<?php echo time(); ?>">
<link rel="stylesheet" type="text/css" href="includes/session-status-bar-variants.css?v=<?php echo time(); ?>">
<style>
/* Loading Overlay - Inline para carregamento imediato */
#app-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    opacity: 1;
    transition: opacity 0.5s ease-out;
}

#app-loading-overlay.fade-out {
    opacity: 0;
    pointer-events: none;
}

.loading-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    color: white;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 18px;
    font-weight: 500;
    margin-top: 20px;
    text-align: center;
}

.loading-subtext {
    color: rgba(255, 255, 255, 0.8);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 14px;
    margin-top: 8px;
}

.loading-progress {
    width: 200px;
    height: 4px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
    margin-top: 16px;
    overflow: hidden;
}

.loading-progress-bar {
    height: 100%;
    background: white;
    width: 0%;
    animation: progress 2s ease-in-out infinite;
}

@keyframes progress {
    0% { width: 0%; }
    50% { width: 70%; }
    100% { width: 100%; }
}

/* Oculta o conteúdo principal até o Dojo terminar */
body.loading header,
body.loading article,
body.loading #listContainer {
    visibility: hidden;
}
</style>
<?php dojo_script(['parseOnLoad' => true]); ?>
<?php script_versioned('js/session-manager.js'); ?>
<script>
window.appBootstrap = window.appBootstrap || {};
window.appBootstrap.oauthToken = <?php echo json_encode($oauthToken, JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="js/google-maps-config.php?v=5.0.1" defer></script>
<?php script_versioned('js/google-maps.js', ['defer' => 'defer']); ?>
<?php script_versioned('js/4sq.js', ['defer' => 'defer']); ?>
</head>
<body class="tundra loading">

<!-- Loading Overlay -->
<div id="app-loading-overlay">
    <div class="loading-spinner"></div>
    <div class="loading-text">Carregando Editor</div>
    <div class="loading-subtext">Preparando <?php echo count($file); ?> locais...</div>
    <div class="loading-progress">
        <div class="loading-progress-bar"></div>
    </div>
</div>

<?php include 'includes/session-status-bar.php'; ?>

<header style="margin-top: 5px">
	<h2>Editar locais</h2>
</header>

<article>
	<p>Antes de salvar suas propostas de altera&ccedil;&otilde;es, certifique-se de ler nosso <a id="guia" href="javascript:showDialogGuia()">guia de estilo</a> e as <a id="regras" href="https://support.foursquare.com/hc/en-us/articles/14884050907164-House-Rules" target="_blank">regras da casa</a>.</p>
</article>
<article>
	<div id="mapa"></div>
</article>
<article>
<div id="listContainer">
<?php
$totalCampos = 0;

// Campos disponíveis para edição
$camposDisponiveis = [
    'nome' => 'editName',
    'endereco' => 'editAddress', 
    'ruatransversal' => 'editCross',
    'bairro' => 'editNeighborhood',
    'cidade' => 'editCity',
    'estado' => 'editState',
    'codigopostal' => 'editZip',
    'dentro' => 'editParentId',
    'telefone' => 'editPhone',
    'sitedaweb' => 'editUrl',
    'twitter' => 'editTwitter',
    'facebook' => 'editFacebook',
    'instagram' => 'editInstagram',
    'latlng' => 'editLatLng',
    'descricao' => 'editDescription',
    'menu' => 'editMenu',
    'horas' => 'editHours'
];

// Inicializa todas as variáveis como false
foreach ($camposDisponiveis as $variavel) {
    $$variavel = false;
}

// Compatibilidade com nomes antigos das variáveis
$editVenuell = false;  // Alias para editLatLng
$editDesc = false;     // Alias para editDescription

// Define quais campos serão editados baseado na seleção
if ($campos !== null) {
    foreach ($camposDisponiveis as $campo => $variavel) {
        if (in_array($campo, $campos)) {
            $$variavel = true;
            $totalCampos++;
            
            // Atualiza aliases para compatibilidade
            if ($campo === 'latlng') {
                $editVenuell = true;
            }
            if ($campo === 'descricao') {
                $editDesc = true;
            }
        }
    }
} else {
    // Se não há campos específicos definidos, todos ficam false (já inicializados acima)
}

$ajusteInput = 13 - $totalCampos;

/**
 * Renderiza um campo de input baseado no tipo
 * Compatível 100% com o código inline original
 */
function renderizarCampo(string $tipo, string $name, array $config, int $ajusteInput, int $indice): string 
{
    if ($tipo === 'hidden') {
        return '<input type="hidden" name="' . htmlspecialchars($name) . '">' . chr(10);
    }
    
    $width = $config['width'] + $ajusteInput;
    $maxlength = $config['maxlength'] ?? 256;
    $placeholder = htmlspecialchars($config['placeholder']);
    $namePtbr = htmlspecialchars($config['name_ptbr']);
    
    // Usa exatamente o mesmo formato do código inline original
    return '<input type="text" dojoType="dijit.form.TextBox" name="' . htmlspecialchars($name) . '" ' .
           'maxlength="' . $maxlength . '" value=" " placeHolder="' . $placeholder . '" ' .
           'style="width: ' . $width . 'em; margin-left: 5px;" ' .
           'onchange="verificarAlteracao(this, ' . $indice . ')" ' .
           'data-name-ptbr="' . $namePtbr . '">' . chr(10);
}

// Configuração dos campos
$configCampos = [
    'name' => ['width' => 11, 'maxlength' => 256, 'placeholder' => 'Nome', 'name_ptbr' => 'Nome'],
    'address' => ['width' => 11, 'maxlength' => 128, 'placeholder' => 'Endereço', 'name_ptbr' => 'Endereço'],
    'crossStreet' => ['width' => 9, 'maxlength' => 128, 'placeholder' => 'Rua transversal', 'name_ptbr' => 'Rua transversal'],
    'neighborhood' => ['width' => 9, 'maxlength' => 128, 'placeholder' => 'Bairro', 'name_ptbr' => 'Bairro'],
    'city' => ['width' => 7, 'maxlength' => 31, 'placeholder' => 'Cidade', 'name_ptbr' => 'Cidade'],
    'state' => ['width' => 2.5, 'maxlength' => 30, 'placeholder' => 'UF', 'name_ptbr' => 'UF'],
    'zip' => ['width' => 6, 'maxlength' => 13, 'placeholder' => 'Código postal', 'name_ptbr' => 'Código postal'],
    'parentId' => ['width' => 14, 'maxlength' => 24, 'placeholder' => 'Dentro', 'name_ptbr' => 'Dentro'],
    'phone' => ['width' => 7, 'maxlength' => 21, 'placeholder' => 'Telefone', 'name_ptbr' => 'Telefone'],
    'url' => ['width' => 8, 'maxlength' => 256, 'placeholder' => 'Website', 'name_ptbr' => 'Website'],
    'twitter' => ['width' => 7, 'maxlength' => 51, 'placeholder' => 'Twitter', 'name_ptbr' => 'Twitter'],
    'facebook' => ['width' => 7, 'maxlength' => 51, 'placeholder' => 'Facebook', 'name_ptbr' => 'Facebook'],
    'instagram' => ['width' => 7, 'maxlength' => 51, 'placeholder' => 'Instagram', 'name_ptbr' => 'Instagram'],
    'venuell' => ['width' => 7, 'maxlength' => 402, 'placeholder' => 'Lat/Long', 'name_ptbr' => 'Lat/Long'],
    'description' => ['width' => 7, 'maxlength' => 300, 'placeholder' => 'Descrição', 'name_ptbr' => 'Descrição'],
    'menu' => ['width' => 7, 'maxlength' => 256, 'placeholder' => 'Menu', 'name_ptbr' => 'Menu']
];

$i = 0;

foreach ($file as $f) {
    $i++;

    if (isset($venuesIds[$i - 1])) {
        echo '<section id="linha' . ($i - 1) . '" class="row">' . "\n";
        echo '<form name="form' . $i . '" accept-charset="utf-8" encType="multipart/form-data" method="post">' . "\n";

        $venue = $venuesIds[$i - 1];
        echo '<div class="selectbox"><input name="selecao" data-dojo-type="dijit/form/CheckBox" value="' . ($i - 1) . '" onChange="atualizarItensMenuMais(this.value)"></div>' . "\n";

        $venueLink = $f . '?ref=' . $client_key;
        echo '<input type="hidden" name="venue" value="' . htmlspecialchars($venue) . '">';
        echo '<span id="info' . ($i - 1) . '"><a id="venLnk' . ($i - 1) . '" href="' . htmlspecialchars($venueLink) . '" target="_blank" style="margin-left: 5px; margin-right: 5px; vertical-align: -1px;">';
        
        // Formatação do número baseado na quantidade total
        if (count($file) < 10) {
            echo $i;
        } else if (count($file) < 100) {
            echo str_pad((string)$i, 2, "0", STR_PAD_LEFT);
        } else {
            echo str_pad((string)$i, 3, "0", STR_PAD_LEFT);
        }
        echo '</a></span>' . "\n";

        echo '<span id="icone' . ($i - 1) . '"><img id="catImg' . $i . '" src="https://app.foursquare.com/img/categories_v2/none_bg_32.png" style="height: 22px; width: 22px; margin-left: 0px"></span>' . "\n";

        // Renderização dos campos usando a nova abordagem
        echo $editName ? renderizarCampo('text', 'name', $configCampos['name'], $ajusteInput, $i - 1) : renderizarCampo('hidden', 'name', [], 0, $i - 1);

        if ($editAddress) {
            echo renderizarCampo('text', 'address', $configCampos['address'], $ajusteInput, $i - 1);
        }

        if ($editCross) {
            echo renderizarCampo('text', 'crossStreet', $configCampos['crossStreet'], $ajusteInput, $i - 1);
        }

        if ($editNeighborhood) {
            echo renderizarCampo('text', 'neighborhood', $configCampos['neighborhood'], $ajusteInput, $i - 1);
        }

        if ($editCity) {
            echo renderizarCampo('text', 'city', $configCampos['city'], $ajusteInput, $i - 1);
        }

        if ($editState) {
            echo renderizarCampo('text', 'state', $configCampos['state'], 0, $i - 1); // Sem ajuste para estado
        }

        if ($editZip) {
            echo renderizarCampo('text', 'zip', $configCampos['zip'], 0, $i - 1);
        }

        if ($editParentId) {
            echo renderizarCampo('text', 'parentId', $configCampos['parentId'], 0, $i - 1);
        }

        if ($editPhone) {
            echo renderizarCampo('text', 'phone', $configCampos['phone'], 0, $i - 1);
        }

        if ($editUrl) {
            echo renderizarCampo('text', 'url', $configCampos['url'], $ajusteInput, $i - 1);
        }

        if ($editTwitter) {
            echo renderizarCampo('text', 'twitter', $configCampos['twitter'], $ajusteInput, $i - 1);
        }

        if ($editFacebook) {
            echo renderizarCampo('text', 'facebook', $configCampos['facebook'], $ajusteInput, $i - 1);
        }

        if ($editInstagram) {
            echo renderizarCampo('text', 'instagram', $configCampos['instagram'], $ajusteInput, $i - 1);
        }

        if ($editVenuell) {
            echo renderizarCampo('text', 'venuell', $configCampos['venuell'], $ajusteInput, $i - 1);
        } else {
            echo renderizarCampo('hidden', 'venuell', [], 0, $i - 1);
        }

        if ($editDesc) {
            echo renderizarCampo('text', 'description', $configCampos['description'], $ajusteInput, $i - 1);
        }

        if ($editMenu) {
            echo renderizarCampo('text', 'menu', $configCampos['menu'], $ajusteInput, $i - 1);
        }
		
		//if ($editHours) {
			//echo '<input type="text" dojoType="dijit.form.TextBox" name="hours" maxlength="256" value=" " placeHolder="Horas" style="width: ', 8 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="Horas">', chr(10);
		//}
	
		echo '<input type="hidden" id="cid', $i - 1, '" name="categoryId"><input type="hidden" id="cna', $i - 1, '" name="categoryName"><input type="hidden" id="cic', $i - 1, '" name="categoryIcon"><input type="hidden" id="vdt', $i - 1, '" name="createdAt"><input type="hidden" id="vcc', $i - 1, '" name="checkinsCount"><input type="hidden" id="vuc', $i - 1, '" name="usersCount"><input type="hidden" id="vtc', $i - 1, '" name="tipCount"><input type="hidden" id="vpc', $i - 1, '" name="photosCount"><input type="hidden" id="vhc', $i - 1, '" name="likesCount"><input type="hidden" id="vlc', $i - 1, '" name="listedCount"><input type="hidden" id="vic', $i - 1, '" name="isClosed"><input type="hidden" id="vip', $i - 1, '" name="isPrivate"><input type="hidden" id="vid', $i - 1, '" name="isDeleted"><input type="hidden" id="vrf', $i - 1, '" name="verified">', chr(10);
		echo '<span id="result', $i - 1, '"></span>', chr(10), '</form>', chr(10), '</section>', chr(10);
	}
}
?>
</div>
</article>
<!-- Botoes Salvar, Voltar e Mais -->
<article>
	<div id="fixedtray">
		<button id="saveButton" dojoType="dijit.form.Button" type="submit" name="saveButton" onclick="javascript:showDialogComment(this.name)" style="float: left; padding-right: 3px;" disabled>Salvar</button>
		<button id="reloadButton" dojoType="dijit.form.Button" type="button" onclick="recarregarDadosVenues()" name="reloadButton" style="float: left; padding-right: 3px;" disabled>Recarregar</button>
		<button id="backButton" dojoType="dijit.form.Button" type="button" onclick="location.href='main.php'" name="backButton" style="float: left; padding-right: 3px;">Voltar</button>
		<div id="dropdownButtonContainer" style="float: left"></div>
	</div>
</article>
<!-- Janela de Edicao das Categorias -->
<div data-dojo-type="dijit.Dialog" id="dlg_cats" data-dojo-props='title:"Categorias"'>
	<div id="catsContainer"></div>
	<div id="treeContainer"></div>
	<span class="checkbox">
		<div class="editAllCheckbox" style="width: 20em;">
			<input id="editAllCheckbox" name="editAllCheckbox" dojoType="dijit.form.CheckBox" value="editAllCategories">
				<label for="editAllCheckbox">
					Aplicar categoria(s) a todos os locais
				</label>
		</div>
	</span>
	<span style="clear: both; display: block;">
		<button id="saveCatsButton" dojoType="dijit.form.Button" type="button" onclick="salvarCategorias()" name="saveCatsButton">OK</button>
		<button data-dojo-type="dijit.form.Button" type="button" data-dojo-props="onClick:function(){ dijit.byId('dlg_cats').hide(); }">Cancelar</button>
	</span>
	<div id="venueIndex" style="display: none"></div><div id="catsIds" style="display: none"></div><div id="catsIcones" style="display: none"></div>
</div>
<!-- Barra de Progresso ao Salvar -->
<div data-dojo-type="dijit.Dialog" id="dlg_save" data-dojo-props='title:"Salvando locais..."'>
	<div dojoType="dijit.ProgressBar" style="width:300px" jsId="jsProgress"
id="saveProgress">
	</div>
</div>
<!-- Modal de Confirmacao ao Recarregar -->
<div data-dojo-type="dijit.Dialog" id="dlg_reload" data-dojo-props="title:'Confirmar Recarregamento'" style="display:none; width: 400px;">
	<div class="dijitDialogPaneContentArea">
		<p id="reloadMessage" style="margin: 10px 0;">Você tem edições não salvas. Recarregar os dados descartará todas as alterações. Deseja realmente continuar?</p>
	</div>
	<div class="dijitDialogPaneActionBar">
		<button data-dojo-type="dijit.form.Button" type="button" id="confirmReloadButton">Recarregar Mesmo Assim</button>
		<button data-dojo-type="dijit.form.Button" type="button" data-dojo-props="onClick:function(){ dijit.byId('dlg_reload').hide(); }">Cancelar</button>
	</div>
</div>
<!-- Modal de Confirmacao ao Editar Categorias com Dados Parciais -->
<div data-dojo-type="dijit.Dialog" id="dlg_confirmEditAllCategories" data-dojo-props="title:'Edição Múltipla de Categorias'" style="display:none; width: 450px;">
	<div class="dijitDialogPaneContentArea">
		<div id="confirmEditAllCategoriesMessage"></div>
	</div>
	<div class="dijitDialogPaneActionBar">
		<button data-dojo-type="dijit.form.Button" type="button" data-dojo-props="onClick:function(){ dijit.byId('dlg_confirmEditAllCategories').hide(); processarEdicaoCategorias(); dijit.byId('dlg_cats').hide(); }">Continuar Mesmo Assim</button>
		<button data-dojo-type="dijit.form.Button" type="button" data-dojo-props="onClick:function(){ dijit.byId('dlg_confirmEditAllCategories').hide(); dijit.byId('dlg_cats').hide(); var venueOrigemIndex = parseInt(dojo.byId('venueIndex').innerHTML); recarregarDadosVenues(venueOrigemIndex); }">Recarregar Todas</button>
		<button data-dojo-type="dijit.form.Button" type="button" data-dojo-props="onClick:function(){ dijit.byId('dlg_confirmEditAllCategories').hide(); }">Cancelar</button>
	</div>
</div>
<!-- Janela de Digitacao de Comentario -->
<div id="dlg_comment" data-dojo-type="dijit.Dialog" data-dojo-props="title:'Coment&aacute;rios'" style="display:none; width: 356px;">
	<div class="dijitDialogPaneContentArea">
		<textarea id="textareaComment" name="textareaComment" data-dojo-type="dijit/form/Textarea" maxLength="200" trim="true"></textarea>
	</div>
	<div class="dijitDialogPaneActionBar">
		<button data-dojo-type="dijit.form.Button" type="submit" id="saveCommentButton" data-dojo-props="onClick:function(){ (actionButton == 'saveButton') ? salvarVenues() : sinalizarVenues(actionButton); }">OK</button>
		<button data-dojo-type="dijit.form.Button" type="button" data-dojo-props="onClick:function(){ dijit.byId('dlg_comment').onCancel(); }">Cancelar</button>
	</div>
</div>
<!-- Janela de Edicao Multipla -->
<div id="dlg_editField" data-dojo-type="dijit.Dialog" data-dojo-props="title:'Edi&ccedil;&atilde;o M&uacute;ltipla'" style="display:none; width: 275px;">
	<div id="editFieldContainer" class="dijitDialogPaneContentArea">
		<div class="row">
			<span class="label"><label for="selectEditField">Campo:</label></span>
			<select data-dojo-id="selectEditField" name="selectEditField" id="selectEditField" data-dojo-type="dijit/form/Select" style="width: 15.1em; margin-bottom: 9px" data-dojo-props="onChange:function(){ dijit.byId('inputEditField').attr('value', ''); dijit.byId('inputEditField').attr('placeHolder', this.attr('displayedValue')); }"></select>
		</div>
		<div class="row">
			<span class="label"><label for="selectEditField">Valor:</label></span>
			<input type="text" id="inputEditField" name="inputEditField1" dojoType="dijit.form.TextBox" trim="true" style="width: 15.1em"/>
		</div>
	</div>
	<div class="dijitDialogPaneActionBar">
		<button data-dojo-type="dijit.form.Button" type="submit" id="editFieldOkButton" data-dojo-props="onClick:function(){ editField(dijit.byId('selectEditField').value, dijit.byId('inputEditField').value); }">OK</button>
		<button data-dojo-type="dijit.form.Button" type="button" id="editFieldApplyButton" data-dojo-props="onClick:function(){ editField(dijit.byId('selectEditField').value, dijit.byId('inputEditField').value); }">Aplicar</button>
		<button data-dojo-type="dijit.form.Button" type="button" data-dojo-props="onClick:function(){ dijit.byId('dlg_editField').onCancel(); }">Cancelar</button>
	</div>
</div>

<script>
// Inicialização do SessionManager para a página de edição
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Inicializando página de edição...');
    
    // Aguarda o SessionManager estar disponível (já integrado com a barra unificada)
    function waitForSessionManager() {
        if (window.sessionManager) {
            console.log('✅ SessionManager encontrado e carregado');
            // O SessionManager já vai se integrar automaticamente com a barra unificada
            setTimeout(() => {
                window.sessionManager.checkSessionStatus();
            }, 500);
        } else {
            console.log('⏳ Aguardando SessionManager...');
            setTimeout(waitForSessionManager, 200);
        }
    }
    
    waitForSessionManager();
    
    // Remove loading overlay após Dojo e venue data carregados
    function removeLoadingOverlay() {
        var overlay = document.getElementById('app-loading-overlay');
        var body = document.body;
        
        if (overlay && !overlay.classList.contains('fade-out')) {
            console.log('✅ Editor pronto - removendo overlay');
            overlay.classList.add('fade-out');
            
            setTimeout(function() {
                if (overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
                body.classList.remove('loading');
                console.log('🎨 Interface de edição pronta');
            }, 500);
        }
    }
    
    // Aguarda Dojo + carregamento inicial dos venues
    if (typeof dojo !== 'undefined' && dojo.ready) {
        dojo.ready(function() {
            // Aguarda carregamento dos dados dos venues (função do 4sq.js)
            var checkInterval = setInterval(function() {
                // Verifica se os dados foram carregados (ao menos o primeiro venue)
                var firstVenueForm = document.querySelector('form[name="form1"]');
                if (firstVenueForm) {
                    clearInterval(checkInterval);
                    setTimeout(removeLoadingOverlay, 200);
                }
            }, 100);
            
            // Fallback: remove após 15 segundos mesmo sem dados
            setTimeout(function() {
                clearInterval(checkInterval);
                removeLoadingOverlay();
            }, 15000);
        });
    } else {
        // Fallback se Dojo não disponível
        setTimeout(removeLoadingOverlay, 10000);
    }
    
    // Google Maps v3.32+ gerencia redimensionamento automaticamente
    // CSS resize: both permite redimensionar a div e o mapa se ajusta sozinho
    console.log('✅ Google Maps com redimensionamento automático ativado');
    
    // Debug: Log global para verificar se há erros relacionados à sessão
    window.addEventListener('error', function(e) {
        console.error('❌ Erro na página:', e.error);
        
        // Apenas envia para barra de status se for erro relacionado à sessão
        const sessionRelatedErrors = ['session', 'token', 'auth', 'login', 'logout', 'unauthorized', '401'];
        const isSessionError = sessionRelatedErrors.some(function(keyword) {
            return e.message && e.message.toLowerCase().includes(keyword);
        });
        
        if (isSessionError && window.sessionStatusBarAPI) {
            window.sessionStatusBarAPI.updateStatus('Erro de sessão: ' + e.message, 'error');
        }
    });
});
</script>

</body>
</html>
