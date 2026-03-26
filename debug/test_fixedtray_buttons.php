<?php

declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

include_once __DIR__ . '/../includes/asset_helper.php';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Debug - Fixed Tray Buttons</title>
    <?php dojo_theme('tundra'); ?>
    <link rel="stylesheet" type="text/css" href="../estilo.css?v=<?php echo time(); ?>">
    <?php dojo_script(['parseOnLoad' => true]); ?>
    <style>
        body {
            font-family: Arial, Helvetica, Verdana, sans-serif;
            background: #f3f5f9;
            color: #2f3a45;
            margin: 0;
            padding: 24px;
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
        }

        .card {
            background: #fff;
            border: 1px solid #d6dde8;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 24px;
            color: #26313d;
        }

        h2 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #364452;
        }

        p {
            margin: 0 0 10px;
            color: #5a6571;
        }

        .mock-table {
            min-height: 220px;
            border: 1px dashed #b8c2d1;
            border-radius: 8px;
            background: linear-gradient(180deg, #fafbfd 0%, #f2f5fa 100%);
            margin-bottom: 10px;
            padding: 12px;
        }

        .mock-row {
            background: #fff;
            border: 1px solid #dde3ee;
            border-left: 4px solid #2d5be3;
            border-radius: 4px;
            padding: 8px 10px;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .note {
            font-size: 12px;
            color: #6f7b88;
        }
    </style>
</head>
<body class="tundra">
    <div class="container">
        <div class="card">
            <h1>Teste da Barra de Botoes do Rodape</h1>
            <p>Valida icones, hierarquia visual do botao principal e comportamento no mobile.</p>
        </div>

        <div class="card">
            <h2>Preview: barra logo apos a tabela</h2>
            <div class="mock-table" aria-hidden="true">
                <div class="mock-row">Linha de local 1</div>
                <div class="mock-row">Linha de local 2</div>
                <div class="mock-row">Linha de local 3</div>
            </div>

            <div id="fixedtray">
                <button id="saveButton" dojoType="dijit.form.Button" type="button" name="saveButton" iconClass="fixedtraySaveIcon" class="fixedtray-btn fixedtray-save-btn" style="float: left; padding-right: 3px;">Salvar</button>
                <button id="reloadButton" dojoType="dijit.form.Button" type="button" name="reloadButton" iconClass="fixedtrayReloadIcon" class="fixedtray-btn" style="float: left; padding-right: 3px;">Recarregar</button>
                <button id="backButton" dojoType="dijit.form.Button" type="button" name="backButton" iconClass="fixedtrayBackIcon" class="fixedtray-btn" style="float: left; padding-right: 3px;">Voltar</button>
                <button id="moreButton" dojoType="dijit.form.DropDownButton" type="button" style="float: left;">
                    Mais
                    <span dojoType="dijit.Menu" id="testMenu">
                        <span dojoType="dijit.MenuItem" iconClass="editIcon">Editar</span>
                        <span dojoType="dijit.MenuItem" iconClass="exportIcon">Exportar</span>
                    </span>
                </button>
            </div>
            <p class="note">Use o DevTools responsivo para validar iPhone/Android e confirmar contraste dos botoes.</p>
        </div>

        <footer style="text-align: center; padding: 30px 20px; margin-top: 40px; border-top: 2px solid #e0e0e0; background: white; border-radius: 10px;">
            <a href="index.php" style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Voltar para Debug
            </a>
        </footer>
    </div>

    <script>
        // Forca carregamento explicito do parser e widgets para a pagina de debug.
        if (typeof require === 'function') {
            require([
                'dojo/parser',
                'dijit/form/Button',
                'dijit/form/DropDownButton',
                'dijit/Menu',
                'dijit/MenuItem',
                'dojo/domReady!'
            ], function(parser) {
                parser.parse();
            });
        }
    </script>
</body>
</html>
