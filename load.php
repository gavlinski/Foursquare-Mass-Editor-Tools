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
 * @copyright  Copyleft (c) 2011-2026
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

// Inclui helper de assets
require_once __DIR__ . '/includes/asset_helper.php';

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
<?php dojo_script(['parseOnLoad' => true]); ?>
');
define("HBODY", '</head>
<body class="tundra">
');
define("CARREGANDO", LINKS . HBODY . '<div id="carregando">Carregando locais&hellip;</div>
');
define("TEMPLATE1", '<script>dojo.require("dijit.form.Button");</script>
');
define("TEMPLATE2", '<p><button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" style="margin-left: 0px;">Voltar</button></p>
</body>
</html>');
define("ERRO01", LINKS . HBODY . TEMPLATE1 . '<p>O limite da API &eacute; de 500 requisi&ccedil;&otilde;es por hora por conjunto de endpoints por OAuth.</p>
<p>Reduza a quantidade de linhas e tente novamente.</p>
' . TEMPLATE2);
//define("ERRO02", LINKS . HBODY . TEMPLATE1 . '<p>Erro na leitura do ID ou URL de um dos locais.</p>
//<p>Verifique o arquivo ou a lista e tente novamente.</p>
//' . TEMPLATE2);
define("ERRO03", LINKS . HBODY . TEMPLATE1 . '<p>Nenhum local encontrado no endere&ccedil;o informado.</p>
<p><b>Poss&iacute;veis causas:</b></p>
<ul>
<li>A p&aacute;gina exige autentica&ccedil;&atilde;o (login) para ser acessada</li>
<li>O site possui bloqueadores ou prote&ccedil;&otilde;es anti-bot</li>
<li>A p&aacute;gina n&atilde;o cont&eacute;m identificadores v&aacute;lidos de locais do Foursquare</li>
<li>O endere&ccedil;o informado est&aacute; incorreto ou inacess&iacute;vel</li>
</ul>
<p><b>Sugest&otilde;es:</b></p>
<ul>
<li>Verifique se a p&aacute;gina &eacute; p&uacute;blica e acess&iacute;vel sem login</li>
<li>Tente copiar o conte&uacute;do da p&aacute;gina e usar a op&ccedil;&atilde;o de <b>"IDs ou URLs dos locais"</b></li>
<li>Utilize sites que funcionam bem com esta ferramenta, como <a href="https://www.4sqstat.com" target="_blank">4sqstat.com</a></li>
</ul>
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
 * Extrai venue ID de uma string usando regex
 * 
 * Formatos suportados:
 * - ID moderno: 4c701c87f23c76b0c5abe685 (24 caracteres hexadecimais)
 * - ID antigo: 94562 (5-6 dígitos numéricos)
 * - https://foursquare.com/venue/name/4c701c87f23c76b0c5abe685
 * - https://foursquare.com/v/name/4d7a895be8b7a1cdb4e3991f/
 * - http://foursquare.com/venue/94562 (formato antigo)
 * - *.foursquare.com/v/4eadbb7902d5cf33fa8cf19e/*
 * 
 * @param string $input String contendo ID ou URL da venue
 * @return string|null Retorna o ID ou null se inválido
 */
function extrairVenueId(string $input): ?string 
{
    $input = trim($input);
    
    // Regex para IDs modernos (24 caracteres hexadecimais)
    // Usa negative lookahead para garantir que o "nome" não seja um ID hex de 24 chars
    // Formato: (domínio)/(venue|v)/(nome-não-hex)?/(ID de 24 chars hex)
    $patternModerno = '/(?:https?:\/\/)?(?:[^\/]*\.)?foursquare\.com\/(?:venue|v)\/(?:(?![0-9a-f]{24}(?:\/|$))[^\/]+\/)?([0-9a-f]{24})/i';
    
    if (preg_match($patternModerno, $input, $matches)) {
        // O ID estará sempre no grupo 1
        return $matches[1];
    }
    
    // Regex para IDs antigos (5-6 dígitos numéricos)
    // Formato: http://foursquare.com/venue/94562
    $patternAntigo = '/(?:https?:\/\/)?(?:[^\/]*\.)?foursquare\.com\/venue\/(\d{5,6})(?:\/|$)/i';
    
    if (preg_match($patternAntigo, $input, $matches)) {
        return $matches[1];
    }
    
    // Se não matched URL, verifica se é ID puro
    // ID moderno: 24 caracteres hexadecimais
    if (strlen($input) === 24 && ctype_xdigit($input)) {
        return $input;
    }
    
    // ID antigo: 5-6 dígitos numéricos
    if (strlen($input) >= 5 && strlen($input) <= 6 && ctype_digit($input)) {
        return $input;
    }
    
    return null;
}

/**
 * Filtra array removendo entradas inválidas e duplicadas
 * 
 * @param array $array Array de URLs ou IDs de venues
 * @return array Array filtrado e sem duplicatas
 */
function filtrarArray(array $array): array 
{
    $filtered = [];
    
    foreach ($array as $value) {
        $value = trim((string)$value);
        $venueId = extrairVenueId($value);
        
        if ($venueId !== null) {
            // Preserva URL completa se fornecida
            if (stripos($value, 'foursquare.com') !== false) {
                $filtered[] = $value;
            } else {
                // Cria URL adequada baseada no tipo de ID
                if (strlen($venueId) === 24) {
                    // ID moderno (24 chars hex)
                    $filtered[] = "https://app.foursquare.com/v/" . $venueId;
                } else {
                    // ID antigo (5-6 dígitos)
                    $filtered[] = "https://foursquare.com/venue/" . $venueId;
                }
            }
        }
    }
    
    return array_values(array_unique($filtered));
}

/**
 * Filtra array extraindo apenas IDs válidos
 * 
 * @param array $array Array de URLs ou strings contendo IDs
 * @return array Array de IDs únicos (24 caracteres hex)
 */
function filtrarArrayPorId(array $array): array 
{
    $uniqueIds = [];
    
    foreach ($array as $value) {
        $venueId = extrairVenueId((string)$value);
        
        if ($venueId !== null && !in_array($venueId, $uniqueIds)) {
            $uniqueIds[] = $venueId;
        }
    }
    
    return array_values($uniqueIds);
}

/**
 * Valida e processa venues de diferentes fontes
 * 
 * Processa arquivos ou listas contendo IDs/URLs de venues nos formatos:
 * - IDs puros (24 chars hex)
 * - URLs do Foursquare (*.foursquare.com/venue/* ou /v/*)
 * - Páginas HTML exportadas (4sqmap, 4sweep)
 * 
 * @param array $lines Array de linhas do arquivo/input
 * @return array|false Array de URLs processadas ou false em caso de erro
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
    
    // Primeira passagem: busca por HTML exportado (4sqmap, etc)
    // Deve conter múltiplas referências ao padrão para ser considerado HTML exportado
    $htmlExportadoCount = 0;
    foreach ($lines as $line_num => $line) {
        /*** 4sqmap - Foursquare Maps and Statistics ***/
        // Conta quantas vezes o padrão de URL do Foursquare aparece
        if (stripos((string)$line, 'foursquare.com/venue/') !== false || 
            stripos((string)$line, 'foursquare.com/v/') !== false) {
            $htmlExportadoCount++;
        }
    }
    
    // Só processa como HTML exportado se houver muitas referências (indica HTML, não lista simples)
    if ($htmlExportadoCount > 3) {
        foreach ($lines as $line_num => $line) {
            // Extrai IDs válidos de URLs na linha usando regex
            if (stripos((string)$line, 'foursquare.com') !== false) {
                if (preg_match_all('/(?:https?:\/\/)?(?:[^\/]*\.)?foursquare\.com\/(?:venue|v)\/(?:(?![0-9a-f]{24}(?:\/|$))[^\/]+\/)?([0-9a-f]{24})/i', (string)$line, $matches)) {
                    $ret = array_merge($ret, $matches[1]);
                }
            }
        }
    }

    // Se encontrou HTML exportado
    if (count($ret) > 0) {
        $size = count($ret);
        $processedUrls = [];
        
        foreach ($ret as $r) {
            $venueId = extrairVenueId((string)$r);
            
            if ($venueId !== null && !in_array($venueId, $venuesIds)) {
                $venuesIds[] = $venueId;
                $processedUrls[] = "https://app.foursquare.com/v/" . $venueId;
            }
            
            $i++;
            $p->setProgressBarProgress($i*100/$size);
            usleep((int)(50000*0.1));
        }

        $_SESSION["venuesIds"] = $venuesIds;
        return array_values(array_unique($processedUrls));
        
    } else if (count($lines) > 500) {
        // Limite da API
        $p->hide();
        echo ERRO01;
        exit;
        
    } else {
        // Processamento de IDs/URLs diretos
        $size = count($lines);
        $processedUrls = [];
        
        foreach ($lines as $line) {
            $line = trim((string)$line);
            
            if (empty($line)) {
                continue;
            }
            
            // Remove sufixos comuns de URLs do Foursquare
            $line = preg_replace('/\/(edit_history|edit|history)\/?$/', '', $line);
            
            $venueId = extrairVenueId($line);
            
            if ($venueId !== null && !in_array($venueId, $venuesIds)) {
                $venuesIds[] = $venueId;
                
                // Preserva URL original se válida (removendo ?ref= para evitar duplicação)
                if (strlen($line) > 6 && stripos($line, 'foursquare.com') !== false) {
                    // Remove TODOS os ?ref= da URL
                    $cleanUrl = preg_replace('/\?ref=[^&]*/', '', $line);
                    $processedUrls[] = $cleanUrl;
                } else {
                    // Cria URL adequada baseada no tipo de ID
                    if (strlen($venueId) === 24) {
                        // ID moderno (24 chars hex)
                        $processedUrls[] = "https://app.foursquare.com/v/" . $venueId;
                    } else {
                        // ID antigo (5-6 dígitos)
                        $processedUrls[] = "https://foursquare.com/venue/" . $venueId;
                    }
                }
            }
            
            $i++;
            $p->setProgressBarProgress($i*100/$size);
            usleep((int)(50000*0.1));
        }
        
        if (count($processedUrls) === 0) {
            $p->hide();
            echo ERRO03;
            exit;
        }

        $_SESSION["venuesIds"] = array_values(array_unique($venuesIds));
        return array_values(array_unique($processedUrls));
    }
}

/**
 * Parse venues de uma página HTML pública do Foursquare
 * 
 * Suporta os seguintes tipos de páginas:
 * - Listas de usuários (com JSON embedado listJson)
 * - Resultados de pesquisa (fourSq.tiplists)
 * - Páginas HTML genéricas com links para venues
 * 
 * @param string $html URL ou caminho da página HTML
 * @return array|false Array de URLs das venues ou false em caso de erro
 */
function parseVenues(string $html): array|false 
{
    // Inicia buffer de output para evitar HTML duplicado em caso de erro
    ob_start();
    
    // Cria contexto com user-agent para evitar bloqueio
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n",
            'timeout' => 30
        ]
    ]);
    
    $lines = @file($html, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES, $context);
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

        /*** Paginas normais com a tag <a href="https://app.foursquare.com/venue/..."></a> ou <a href="https://app.foursquare.com/v/..."></a> ***/
        if (empty($ret)) {
            /*** a new dom object ***/
            $dom = new DOMDocument();

            /*** get the HTML (suppress errors) ***/
            $htmlContent = file_get_contents($html, false, $context);
            @$dom->loadHTML($htmlContent);

            /*** remove silly white space ***/
            $dom->preserveWhiteSpace = false;

            /*** get the links from the HTML ***/
            $links = $dom->getElementsByTagName('a');

            /*** loop over the links ***/
            $size = $links->length;
            $j = 0;
            
            foreach ($links as $tag) {
                $href = $tag->getAttribute('href');
                
                // Extrai venue ID usando regex
                $venueId = extrairVenueId($href);
                
                if ($venueId !== null && !in_array($venueId, $venuesIds)) {
                    $venuesIds[$i] = $venueId;
                    
                    // Preserva URL completa se contém domínio (removendo ?ref= para evitar duplicação)
                    if (stripos($href, "foursquare.com") !== false) {
                        // Remove TODOS os ?ref= da URL
                        $ret[$i] = preg_replace('/\?ref=[^&]*/', '', $href);
                    } else if (strpos($href, "/venue/") === 0 || strpos($href, "/v/") === 0) {
                        // URL relativa, adiciona domínio
                        $ret[$i] = "https://foursquare.com" . $href;
                    } else {
                        // Cria URL adequada baseada no tipo de ID
                        if (strlen($venueId) === 24) {
                            $ret[$i] = "https://app.foursquare.com/v/" . $venueId;
                        } else {
                            $ret[$i] = "https://foursquare.com/venue/" . $venueId;
                        }
                    }
                    $i++;
                }
                
                $j++;
                $p->setProgressBarProgress($j*100/$size);
                usleep((int)(50000*0.1));
            }
        } else {
            // Processa resultados de JSON embedado
            $size = count($ret);
            foreach ($ret as &$r) {
                $venueId = extrairVenueId((string)$r);
                
                if ($venueId !== null) {
                    $venuesIds[$i] = $venueId;
                    
                    // Cria URL adequada baseada no tipo de ID
                    if (strlen($venueId) === 24) {
                        $r = "https://app.foursquare.com/v/" . $venueId;
                    } else {
                        $r = "https://foursquare.com/venue/" . $venueId;
                    }
                    $i++;
                }
                
                $p->setProgressBarProgress($i*100/$size);
                usleep((int)(50000*0.1));
            }
            /*** break the reference with the last element ***/
            unset($r);
        }
        
        if (empty($ret)) {
            $p->hide();
            // Limpa qualquer output anterior antes de retornar false
            ob_end_clean();
            return false;
        } else if (count($ret) > 500) {
            $ret = array_slice($ret, 0, 500);
        }
        
        $_SESSION["venuesIds"] = array_values(array_unique($venuesIds));
        return array_values(array_unique($ret));
    } else {
        // Não faz echo aqui - deixa o código principal tratar o erro
        
        // Envia o output acumulado e retorna resultado
        ob_end_flush();
        return false;
    }
}

/**
 * Define cache local no localStorage do navegador
 * 
 * @param string $key Nome da chave no localStorage
 * @param string $data Dados a serem armazenados
 * @param string $key2 Chave opcional a ser removida
 * @return void
 */
function setLocalCache(string $key, string $data, string $key2): void 
{
    print('<script>'."\n\r".'	localStorage.setItem(\''.$key.'\', \''.$data.'\');'."\n\r".'	localStorage.removeItem(\''.$key2.'\');'."\n\r".'</script>');
    print str_pad('', intval(ini_get('output_buffering') ?: '0')) . "\n\r";
    flush();
}

?>