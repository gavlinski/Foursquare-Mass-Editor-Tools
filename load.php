<?php

/**
 * List Venues Loader
 *
 * Carrega venues a partir de um endereco web, arquivo de texto ou IDs
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2011-2012
 * @version    1.2
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/load.php
 * @since      File available since Release 1.1
 */

session_start();
$_SESSION["oauth_token"] = $_POST["oauth_token"];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>Carregando...</title>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1"/>
<meta http-equiv="cache-control" content="no-cache"/>
<meta http-equiv="pragma" content="no-cache">
<?php
define("VERSION", "Venues Loader 1.1");
define("LINKS", '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
<link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css"/>
<link rel="stylesheet" type="text/css" href="estilo.css"/>
');
define("HBODY", '</head>
<body class="claro">
');
define("CARREGANDO", LINKS . HBODY . '<div id="carregando">Carregando venues&hellip;</div>
');
define("TEMPLATE1", '<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script type="text/javascript">dojo.require("dijit.form.Button");</script>
');
define("TEMPLATE2", '<p><button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" style="margin-left: 0px;">Voltar</button></p>
</body>
</html>');
define("ERRO01", TEMPLATE1 . '<p>O limite da API &eacute; de 500 requisi&ccedil;&otilde;es por hora por conjunto de endpoints por OAuth.</p>
<p>Reduza a quantidade de linhas e tente novamente.</p>
' . TEMPLATE2);
define("ERRO02", TEMPLATE1 . '<p>Erro na leitura do ID ou URL de uma das venues.</p>
<p>Verifique o arquivo ou a lista e tente novamente.</p>
' . TEMPLATE2);
define("ERRO03", TEMPLATE1 . '<p>Nenhuma venue encontrada no endere&ccedil;o informado.</p>
<p>Verifique a p&aacute;gina e tente novamente.</p>
' . TEMPLATE2);
define("ERRO99", '<meta http-equiv="refresh" content="5; url=index.html">
' . LINKS . '</head>
<body>
<p>Erro na leitura dos dados.</p>
</body>
</html>');
define("EDIT", '<script type="text/javascript">
	window.location = "edit.php"
</script>;');

if (isset($_FILES['txts']['tmp_name'][0])) {
  $arquivo = $_FILES['txts']['tmp_name'][0];
  if (is_uploaded_file($arquivo)) {
  	$_SESSION["file"] = validarVenues(filtrarArray(file($arquivo)));
  	$_SESSION["campos"] = $_POST["campos"];
  	echo EDIT;
  }
} else if (isset($_POST["pagina"])) {
  $pagina = $_POST["pagina"];
  if ($pagina != "") {
  	$_SESSION["file"] = parseVenues(trim($pagina));
  	if ($_SESSION["file"] == false) {
    	echo ERRO03;
    	exit;
    } else {
    	$_SESSION["campos"] = $_POST["campos2"];
    	echo EDIT;
		}
  }
} else if ((isset($_POST["textarea"])) && ($_POST["textarea"] != "")) {
  $lista = explode("\n", $_POST["textarea"]);
  $_SESSION["file"] = validarVenues(filtrarArray($lista));
  $_SESSION["campos"] = $_POST["campos3"];
  echo EDIT;
}
echo ERRO99;

function filtrarArray($array) {
  foreach ($array as $i => &$value) {
    $value = trim($value);
    if (strlen($value) < 24)
      unset($array[$i]);
  }
  unset($value);
  return array_values(array_unique($array));
}

function validarVenues($lines) {
  $ret = array();
  global $venues;
  $venues = array();
  $i = 0;
  
  require_once 'ProgressBar.Class.php';

	echo CARREGANDO;
	$p = new ProgressBar();
	echo '<div style="width: 400px;">' . "\r\n";
	$p->render();
	echo '</div>' . "\r\n";
  
  foreach ($lines as $line_num => $line) {
    /*** Places ***/
    //$pos = stripos($line, ' "id": "');
    //if ($pos !== false) {
      //echo "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) . " at position <b>" . $pos . "</b><br />\n";
      //$ret[] = substr($line, $pos + 8);

    /*** Tidysquare ***/
    //} else if (stripos($line, 'venuesArray.push(venue') !== false) {
    if (stripos($line, 'venuesArray.push(venue') !== false) {
      $l = strlen($line);
      //echo "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) . "l : " . $l . "<br />\n";
      //if (($l == 53) or ($l == 54))
      if ($l == 48)
        //$ret[] = substr($line, -$l + 26);
        $ret[] = substr($line, -26);
      else {
        $ret = explode('venuesArray.push(venue', $line);
        $ret = array_slice($ret, 1);
      }
      
    /*** 4sqmap - Foursquare Maps and Statistics ***/
    } else if (stripos($line, 'https://foursquare.com/venue/') !== false) {
    	//echo "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) . " at position <b>" . stripos($line, 'https://foursquare.com/venue/') . "</b><br />\n";
    	$ret = array_merge($ret, array_slice(explode('https://foursquare.com/venue/', $line), 1));
    }
  }

  if (count($ret) > 0) {
    $size = count($ret);
    foreach ($ret as &$r) {
      $vid = substr($r, 0, 24);
      if (!in_array($vid, $venues)) { 
        $venues[$i] = $vid;
        $r = "https://foursquare.com/v/" . $venues[$i];
        $i++;
      }
      $p->setProgressBarProgress($i*100/$size);
			usleep(100000*0.1);
    }
    /*** break the reference with the last element ***/
    unset($r);
    //print_r($ret);
    //echo '<br><br>';
    //print_r($venues);
    //exit;
    $_SESSION["venues"] = $venues;
    return $ret;
  } else if (count($lines) > 500) {
    $p->hide();
    echo ERRO01;
    exit;
  } else {
    $size = count($lines);
    foreach ($lines as &$line) {
      $line = trim($line);
      if ((stripos($line, "foursquare.com/v") === false) && (strlen($line) > 25)) {
      	$p->hide();
        echo ERRO02;
        exit;
      }
      if (strlen($line) > 25) {
        $l = strlen($line) - 2;
        if ($line[$l] === "/")
          $line = substr($line, 0, $l);
        $line = str_replace("/edit", "", $line);
        $venues[$i] = substr($line, strrpos($line, "/") + 1, 24);
      } else {
        $venues[$i] = $line;
        $line = "https://foursquare.com/v/" . $line;
        //$line = "" + $venues[$i];
      }
      $i++;
      $p->setProgressBarProgress($i*100/$size);
			usleep(100000*0.1);
    }
    /*** break the reference with the last element ***/
    unset($line);
    //print_r($lines);
    //echo '<br><br>';
    //print_r($venues);
    //exit;
    $_SESSION["venues"] = $venues;
    return $lines;
  }
}

function parseVenues($html) {
	$lines = @file($html);
  $ret = array();
  global $venues;
  $venues = array();
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
    	//if (stripos($line, 'ITEMS_JSON') !== false) {
    	if (stripos($line, 'itemsJson') !== false) {
				//$ret = array_slice(explode('\"venue\":{\"id\":\"', $line), 1);
    	  $ret = array_slice(explode('"venue":{"id":"', $line), 1);
      	break;
    	/*** Resultados da pesquisa ***/
    	} else if (stripos($line, 'fourSq.tiplists.setupSearchPageListControls([{"id":"v') !== false) {
      	$ret = array_slice(explode('"id":"v', $line), 1);
      	break;
    	}
  	}

  	/*** Paginas normais com a tag <a href="https://foursquare.com/venue/..."></a> ou <a href="https://foursquare.com/v/..."></a> ***/
  	if ($ret == null) {
  		/*** a new dom object ***/
			$dom = new domDocument;

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
    	  if ((stripos($tag->getAttribute('href'), "/venue/") !== false) || (stripos($tag->getAttribute('href'), "/v/") !== false)) {
    	    $vid = substr($tag->getAttribute('href'), -24);
    	    if (!in_array($vid, $venues)) { 
            $venues[$i] = $vid;
       		  $ret[$i] = "https://foursquare.com" . $tag->getAttribute('href');
        	  $i++;
          }
      	}
      	$j++;
      	$p->setProgressBarProgress($j*100/$size);
				usleep(100000*0.1);
    	}
  	} else {
  	  $size = count($ret);
    	foreach ($ret as &$r) {
      	$venues[$i] = substr($r, 0, 24);
      	$r = "https://foursquare.com/v/" . $venues[$i];
      	$i++;
				$p->setProgressBarProgress($i*100/$size);
				usleep(100000*0.1);
    	}
    	/*** break the reference with the last element ***/
    	unset($r);
  	}
  	if ($ret == null)
  	  $p->hide();
  	else if (count($ret) > 500)
  	  $ret = array_slice($ret, 0, 500);
  	$_SESSION["venues"] = $venues;
  	return $ret;
	} else {
    echo LINKS . HBODY;
    print('<script type="text/javascript">document.title = "Erro";</script>'."\r");
    return false;
	}
}
?>