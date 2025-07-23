<?php

declare(strict_types=1);

/**
 * List Venues Loader
 *
 * Carrega venues a partir de um endereço web, arquivo de texto ou IDs
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2011-2025
 * @version    3.0.0
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/load.php
 * @since      File available since Release 1.1
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */
 
/**
 * Variáveis de Sessão:
 *
 * $_SESSION["file"] = Array com as URLs completas, caso informadas
 * $_SESSION["venuesIds"] = Array com os IDs parseados das venues
 * $_SESSION["venues"] = String com os IDs das venues (v1,v2,...)
 * $_SESSION["campos"] = Array com os campos editáveis
 */

// Headers anti-cache para desenvolvimento
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Inicialização da sessão
if (!isset($_SESSION)) {
    session_start();
}

if (isset($_GET["venues"])) {
    $_SESSION["venues"] = $_GET["venues"];
}

if (!isset($_SESSION["oauth_token"])) {
    header('Location: index.php');
    exit();
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<title>Carregando...</title>
<meta charset="utf-8">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="pragma" content="no-cache">
<?php
define("VERSION", "Venues Loader 3.0.0");
define("LINKS", '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" type="text/css" href="js/dijit/themes/tundra/tundra.css">
<link rel="stylesheet" type="text/css" href="estilo.css?v=<?php echo time(); ?>">
<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
');
define("HBODY", '</head>
<body class="tundra">
');
define("CARREGANDO", LINKS . HBODY . '<div id="carregando">Carregando venues&hellip;</div>
');
define("TEMPLATE1", '<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script>dojo.require("dijit.form.Button");</script>
');
define("TEMPLATE2", '<p><button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" style="margin-left: 0px;">Voltar</button></p>
</body>
</html>');
define("ERRO01", TEMPLATE1 . '<p>O limite da API &eacute; de 500 requisi&ccedil;&otilde;es por hora por conjunto de endpoints por OAuth.</p>
<p>Reduza a quantidade de linhas e tente novamente.</p>
' . TEMPLATE2);
//define("ERRO02", TEMPLATE1 . '<p>Erro na leitura do ID ou URL de uma das venues.</p>
//<p>Verifique o arquivo ou a lista e tente novamente.</p>
//' . TEMPLATE2);
define("ERRO03", TEMPLATE1 . '<p>Nenhuma venue encontrada no endere&ccedil;o informado.</p>
<p>Verifique a p&aacute;gina e tente novamente.</p>
' . TEMPLATE2);
define("ERRO99", '<meta http-equiv="refresh" content="5; url=index.php">
' . LINKS . '</head>
<body>
<p>Erro na leitura dos dados.</p>
</body>
</html>');
define("EDIT", '<script>
	window.location = "edit.php"
</script>;');

/*** Importação de dados de um arquivo texto ***/
if (isset($_FILES['txt']['tmp_name'])) {
    $arquivo = $_FILES['txt']['tmp_name'];
    if (is_uploaded_file($arquivo)) {
        $file_content = file($arquivo);
        if ($file_content !== false) {
            $_SESSION["file"] = validarVenues($file_content);
            $_SESSION["campos"] = $_POST["campos1"] ?? [];
            setLocalCache("txt", implode('%0A,', $_SESSION["file"]), "venues");
            echo EDIT;
        }
    }

/*** Importação de lista de uma página web ***/
} else if (isset($_POST["pagina"])) {
    $pagina = trim((string)$_POST["pagina"]);
    if ($pagina !== "") {
        $_SESSION["file"] = parseVenues($pagina);
        if ($_SESSION["file"] === false) {
            echo ERRO03;
            exit;
        } else {
            $_SESSION["campos"] = $_POST["campos2"] ?? [];
            setLocalCache("txt", implode('%0A,', $_SESSION["file"]), "venues");
            echo EDIT;
        }
    }

/*** IDs ou URLs informados manualmente ***/
} else if ((isset($_POST["textarea"])) && ($_POST["textarea"] !== "")) {
    $lista = explode("\n", (string)$_POST["textarea"]);
    $_SESSION["file"] = validarVenues($lista);
    if ($_SESSION["file"] === false) {
        echo ERRO03;
        exit;
    } else {
        $_SESSION["campos"] = $_POST["campos3"] ?? null;
        setLocalCache("txt", implode('%0A,', $_SESSION["file"]), "venues");
        echo EDIT;
    }
} else if (isset($_SESSION["venues"])) {
    $lista = explode(",", (string)$_SESSION["venues"]);
    $_SESSION["file"] = validarVenues($lista);
    if ($_SESSION["file"] === false) {
        echo ERRO03;
        exit;
    } else {
        $_SESSION["campos"] = ["nome", "endereco", "ruatransversal", "bairro",
            "cidade", "estado", "codigopostal", "dentro", "telefone", "sitedaweb",
            "twitter", "facebook", "instagram", "latlng", "descricao", "menu", "horas"];
        setLocalCache("txt", implode('%0A,', $_SESSION["file"]), "venues");
        echo EDIT;
    }
} else {
    echo ERRO99;
}

/**
 * Filtra array removendo entradas inválidas
 */
function filtrarArray(array $array): array 
{
    foreach ($array as $i => &$value) {
        $value = trim((string)$value);
        if (((strlen($value) < 24) || (strlen(basename($value)) != 24) || (!ctype_xdigit(basename($value))))) {
            unset($array[$i]);
        }
    }
    unset($value);
    return array_values(array_unique($array));
}

/**
 * Filtra array por ID válido
 */
function filtrarArrayPorId(array $array): array 
{
    $new_array = [];
    foreach ($array as $i => &$value) {
        $vid = basename((string)$value);
        if ((strlen($vid) == 24) && ((!in_array($vid, $new_array)) && (ctype_xdigit($vid)))) {
            $new_array[] = $vid;
        } else {
            unset($array[$i]);
        }
    }
    unset($value);
    return array_values($array);
}

/**
 * Valida e processa venues de diferentes fontes
 */
function validarVenues(array $lines): array|false 
{
    $ret = [];
    global $venuesIds;
    $venuesIds = [];
    $i = 0;
    
    require_once 'ProgressBar.Class.php';

    echo CARREGANDO;
    $p = new ProgressBar();
    echo '<div style="width: 400px;">' . "\r\n";
    $p->render();
    echo '</div>' . "\r\n";
    
    foreach ($lines as $line_num => $line) {
        /*** Tidysquare e 4sqmap - Foursquare Maps and Statistics ***/
        if (stripos((string)$line, 'foursquare.com/venue/') !== false) {
            $ret = array_merge($ret, array_slice(explode('://foursquare.com/venue/', (string)$line), 1));
        }
    }

    if (count($ret) > 0) {
        $size = count($ret);
        foreach ($ret as &$r) {
            $vid = substr((string)$r, 0, 24);
            if ((stripos($vid, ' ') === false) && (!in_array($vid, $venuesIds))) {
                $venuesIds[] = $vid;
                $r = "https://foursquare.com/v/" . $vid;
            } else {
                $r = "";
            }
            $i++;
            $p->setProgressBarProgress($i*100/$size);
            usleep((int)(50000*0.1));
        }
        /*** break the reference with the last element ***/
        unset($r);

        $_SESSION["venuesIds"] = $venuesIds;
        return filtrarArray($ret);
    } else if (count($lines) > 500) {
        $p->hide();
        echo ERRO01;
        exit;
    } else {
        $size = count($lines);
        foreach ($lines as &$line) {
            $line = trim((string)$line);
            $length = strlen($line);
            
            if ($length > 24) {
                $l = $length - 2;
                if ($line[$l] === "/") {
                    $line = substr($line, 0, $l);
                }
                $line = str_replace("/edit_history", "", $line);
                $line = str_replace("/edit", "", $line);
                $line = str_replace("/history", "", $line);
                $venuesIds[$i] = basename($line);
            } else if ($length == 24) {
                $venuesIds[$i] = $line;
                $line = "https://foursquare.com/v/" . $line;
            }
            $i++;
            $p->setProgressBarProgress($i*100/$size);
            usleep((int)(50000*0.1));
        }
        /*** break the reference with the last element ***/
        unset($line);
        $lines = filtrarArray($lines);
        if (count($lines) == 0) {
            $p->hide();
            echo ERRO03;
            exit;
        }

        $_SESSION["venuesIds"] = filtrarArray($venuesIds);
        if (count($_SESSION["venuesIds"]) != count($lines)) {
            $lines = filtrarArrayPorId($lines);
        }
        return $lines;
    }
}

/**
 * Parse venues de uma página HTML
 */
function parseVenues(string $html): array|false 
{
    $lines = @file($html);
    $ret = [];
    global $venuesIds;
    $venuesIds = [];
    $i = 0;

    if ($lines) {
        require_once 'ProgressBar.Class.php';

        echo CARREGANDO;
        $p = new ProgressBar();
        echo '<div style="width: 400px;">' . "\r\n";
        $p->render();
        echo '</div>' . "\r\n";

        foreach ($lines as $line_num => $line) {
            /*** Listas do usuario do foursquare ***/
            if (stripos((string)$line, 'listJson') !== false) {
                $ret = array_slice(explode('"venue":{"id":"', (string)$line), 1);
                break;
            /*** Resultados da pesquisa ***/
            } else if (stripos((string)$line, 'fourSq.tiplists.setupSearchPageListControls([{"id":"v') !== false) {
                $ret = array_slice(explode('"id":"v', (string)$line), 1);
                break;
            }
        }

        /*** Paginas normais com a tag <a href="https://foursquare.com/venue/..."></a> ou <a href="https://foursquare.com/v/..."></a> ***/
        if (empty($ret)) {
            /*** a new dom object ***/
            $dom = new DOMDocument();

            /*** get the HTML (suppress errors) ***/
            @$dom->loadHTML(file_get_contents($html));

            /*** remove silly white space ***/
            $dom->preserveWhiteSpace = false;

            /*** get the links from the HTML ***/
            $links = $dom->getElementsByTagName('a');

            /*** loop over the links ***/
            $size = $links->length;
            $j = 0;
            foreach ($links as $tag) {
                $href = $tag->getAttribute('href');
                if ((stripos($href, "/venue/") !== false) || (stripos($href, "/v/") !== false)) {
                    if (stripos($href, "?referralId=a-") !== false) {
                        $vid = substr($href, -62, 24);
                    } else {
                        $vid = substr($href, -24);
                    }
                    if (!in_array($vid, $venuesIds)) { 
                        $venuesIds[$i] = $vid;
                        if (stripos($href, "foursquare.com") !== false) {
                            $ret[$i] = $href;
                        } else {
                            $ret[$i] = "https://foursquare.com" . $href;
                        }
                        $i++;
                    }
                }
                $j++;
                $p->setProgressBarProgress($j*100/$size);
                usleep((int)(50000*0.1));
            }
        } else {
            $size = count($ret);
            foreach ($ret as &$r) {
                $venuesIds[$i] = substr((string)$r, 0, 24);
                $r = "https://foursquare.com/v/" . $venuesIds[$i];
                $i++;
                $p->setProgressBarProgress($i*100/$size);
                usleep((int)(50000*0.1));
            }
            /*** break the reference with the last element ***/
            unset($r);
        }
        
        if (empty($ret)) {
            $p->hide();
        } else if (count($ret) > 500) {
            $ret = array_slice($ret, 0, 500);
        }
        
        $_SESSION["venuesIds"] = $venuesIds;
        return $ret;
    } else {
        echo LINKS . HBODY;
        print('<script>document.title = "Erro";</script>'."\r");
        return false;
    }
}

/**
 * Define cache local no localStorage
 */
function setLocalCache(string $key, string $data, string $key2): void 
{
    print('<script>'."\n\r".'	localStorage.setItem(\''.$key.'\', \''.$data.'\');'."\n\r".'	localStorage.removeItem(\''.$key2.'\');'."\n\r".'</script>');
    print str_pad('', intval(ini_get('output_buffering') ?: '0')) . "\n\r";
    flush();
}

?>