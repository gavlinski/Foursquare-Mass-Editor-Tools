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
    <title>Debug - Comparativo de Seletores de Redes (Chains)</title>
    <?php dojo_theme('tundra'); ?>
    <link rel="stylesheet" type="text/css" href="../estilo.css?v=<?php echo time(); ?>">
    <?php dojo_script(['parseOnLoad' => true]); ?>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f3f5f9;
            color: #2f3a45;
            margin: 0;
            padding: 24px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 28px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.25);
        }

        .header-card h1 {
            color: #fff;
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 700;
        }

        .header-card p {
            margin: 0;
            opacity: 0.92;
            font-size: 14px;
            line-height: 1.6;
        }

        .card {
            background: #fff;
            border: 1px solid #d6dde8;
            border-radius: 10px;
            padding: 22px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }

        .card h2 {
            margin: 0 0 6px;
            font-size: 17px;
            color: #26313d;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card p.desc {
            margin: 0 0 16px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }

        .tag-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .tag-dijit { background: #e0e7ff; color: #3730a3; }
        .tag-visual { background: #dcfce7; color: #166534; }
        .tag-combo { background: #fef3c7; color: #92400e; }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            margin-bottom: 14px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            min-width: 260px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }

        .form-input {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .btn-primary:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        /* Container de Redes Selecionadas */
        .selected-chains-area {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            min-height: 48px;
            padding: 12px;
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            margin-top: 10px;
            align-items: center;
        }

        .chain-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            border-radius: 24px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            font-size: 13px;
            font-weight: 500;
        }

        .chain-badge.is-primary {
            border-color: #6366f1;
            background: #eef2ff;
            color: #3730a3;
        }

        .chain-badge img {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            object-fit: cover;
            background: #eee;
        }

        .chain-badge .badge-remove {
            color: #ef4444;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            margin-left: 4px;
            line-height: 1;
        }

        .chain-badge .badge-remove:hover {
            color: #b91c1c;
        }

        .chain-badge .badge-primary-btn {
            background: none;
            border: none;
            color: #6366f1;
            font-size: 11px;
            cursor: pointer;
            text-decoration: underline;
            padding: 0;
            margin-left: 2px;
        }

        /* Lista de resultados visuais (Opção 2) */
        .visual-results-list {
            max-height: 260px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            margin-top: 10px;
            display: none;
        }

        .visual-result-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background 0.15s;
        }

        .visual-result-item:last-child {
            border-bottom: none;
        }

        .visual-result-item:hover {
            background: #f8fafc;
        }

        .visual-item-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .visual-item-left img {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .visual-item-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }

        .visual-item-subtitle {
            font-size: 12px;
            color: #64748b;
        }

        .visual-item-right {
            text-align: right;
            font-size: 12px;
            color: #94a3b8;
        }

        /* Box de Payload Simulado */
        .payload-box {
            background: #0f172a;
            color: #38bdf8;
            padding: 14px;
            border-radius: 8px;
            font-family: Consolas, Monaco, monospace;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
            margin-top: 10px;
        }

        .comparison-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 20px;
        }

        .active-option {
            border: 2px solid #6366f1 !important;
        }
    </style>
    <script>
        dojo.require("dijit.form.FilteringSelect");
        dojo.require("dijit.form.ComboBox");
        dojo.require("dojo.store.Memory");
        dojo.require("dijit.form.Button");
    </script>
</head>
<body class="tundra">
<div class="container">
    <div class="header-card">
        <h1>🧪 Comparativo de Seletores de Redes (Chains)</h1>
        <p>Esta página permite testar e comparar 3 opções de componentes para seleção e busca de redes com a <strong>API Foursquare v2</strong> (<code>/v2/chains/search</code>). Interaja com cada uma para escolher a que melhor se adapta ao fluxo de edição de locais.</p>
    </div>

    <!-- Parâmetros de Teste -->
    <div class="card">
        <h2>⚙️ Parâmetros de Autenticação e Local de Teste</h2>
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label for="tokenInput">OAuth Token:</label>
                <input type="password" id="tokenInput" class="form-input" value="" placeholder="Cole o token OAuth v2" autocomplete="off">
            </div>
            <div class="form-group" style="flex: 1.2;">
                <label for="venueIdInput">Venue ID de Teste:</label>
                <input type="text" id="venueIdInput" class="form-input" value="5a21c1b48173cb3fac7fc609" placeholder="Ex: 5a21c1b48173cb3fac7fc609">
            </div>
            <div style="margin-top: 22px;">
                <button type="button" class="btn btn-primary" onclick="carregarDadosLocal()">Carregar Dados do Local</button>
            </div>
        </div>
        <div id="localInfo" style="font-size: 13px; color: #475569; margin-top: 4px;"></div>
    </div>

    <!-- Estado Atual das Redes Selecionadas -->
    <div class="card">
        <h2>🏷️ Redes Atualmente Vinculadas ao Local</h2>
        <p class="desc">Redes que constam no local ou que foram selecionadas através de um dos componentes abaixo. A primeira rede é a primária.</p>
        <div id="selectedChainsContainer" class="selected-chains-area">
            <span style="color: #94a3b8; font-size: 13px;">Nenhuma rede selecionada ainda. Carregue o local ou busque abaixo.</span>
        </div>
        <div style="margin-top: 14px;">
            <button type="button" class="btn btn-secondary" onclick="atualizarSimulacaoPayload()">Gerar Simulação de Payload POST</button>
            <div id="payloadOutput" class="payload-box" style="display: none;"></div>
        </div>
    </div>

    <div class="comparison-grid">
        <!-- Opção 1: FilteringSelect -->
        <div class="card" id="cardOption1">
            <h2>
                <span class="tag-badge tag-dijit">Opção 1</span>
                FilteringSelect (Dojo Dijit)
            </h2>
            <p class="desc">Componente nativo do Dijit similar ao usado na busca de categorias em <code>main.php</code>. Busca redes na API e força seleção de item existente na lista.</p>

            <div class="form-group" style="margin-bottom: 12px;">
                <label>Pesquisar termo para carregar na lista:</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="opt1Query" class="form-input" value="Cacau" placeholder="Ex: Cacau, Starbucks, McDonald" style="flex: 1;">
                    <button type="button" class="btn btn-secondary" onclick="buscarParaFilteringSelect()">Buscar</button>
                </div>
            </div>

            <div class="form-group">
                <label for="filteringSelectWidget">Selecione na lista do Dijit:</label>
                <div id="filteringSelectMountPoint"></div>
            </div>

            <div style="margin-top: 14px; font-size: 12px; color: #64748b;">
                <strong>Vantagens:</strong> Totalmente integrado ao Dijit, visual consistente com a tela inicial.<br>
                <strong>Consideração:</strong> Requer carregamento prévio da busca ou botão de consulta para popular o store.
            </div>
        </div>

        <!-- Opção 2: Busca Reativa com Cards Visuais -->
        <div class="card" id="cardOption2">
            <h2>
                <span class="tag-badge tag-visual">Opção 2</span>
                Busca Reativa com Lista de Logos
            </h2>
            <p class="desc">Campo com busca automática (debounce) que exibe diretamente os logotipos (32x32px), nome e quantidade de unidades. Seleciona com 1 clique.</p>

            <div class="form-group">
                <label for="opt2SearchInput">Buscar rede pelo nome:</label>
                <input type="text" id="opt2SearchInput" class="form-input" placeholder="Digite para buscar (ex: Cacau, Starbucks)..." oninput="debounceBuscarOpt2(this.value)">
            </div>

            <div id="opt2ResultsList" class="visual-results-list"></div>
            <div id="opt2Loading" style="display: none; padding: 10px; font-size: 13px; color: #64748b;">⏳ Buscando redes na API...</div>

            <div style="margin-top: 14px; font-size: 12px; color: #64748b;">
                <strong>Vantagens:</strong> Experiência visual imediata com logotipo real da marca e contagem de lojas.<br>
                <strong>Consideração:</strong> Utiliza marcação HTML customizada com estilo limpo em vez de widget puro do Dojo.
            </div>
        </div>

        <!-- Opção 3: ComboBox -->
        <div class="card" id="cardOption3">
            <h2>
                <span class="tag-badge tag-combo">Opção 3</span>
                ComboBox (Texto Livre + Dropdown)
            </h2>
            <p class="desc">Permite selecionar de sugestões buscadas OU digitar/colar diretamente um ID de rede de 24 caracteres hexadecimais.</p>

            <div class="form-group" style="margin-bottom: 12px;">
                <label>Termo ou ID de Rede:</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="opt3Query" class="form-input" value="Starbucks" placeholder="Nome ou ID direto" style="flex: 1;">
                    <button type="button" class="btn btn-secondary" onclick="buscarParaComboBox()">Buscar</button>
                </div>
            </div>

            <div class="form-group">
                <label for="comboBoxWidget">ComboBox Dijit (digite ID ou selecione):</label>
                <div id="comboBoxMountPoint"></div>
            </div>

            <div style="margin-top: 10px;">
                <button type="button" class="btn btn-primary" onclick="adicionarDoComboBox()">Adicionar Seleção / ID</button>
            </div>

            <div style="margin-top: 14px; font-size: 12px; color: #64748b;">
                <strong>Vantagens:</strong> Permite colar IDs diretamente caso o usuário já os possua em planilha.<br>
                <strong>Consideração:</strong> Não valida se o ID digitado manualmente realmente existe sem fazer consulta extra.
            </div>
        </div>
    </div>

    <!-- Footer padrão de ferramentas de debug com preservação de scroll -->
    <footer style="text-align: center; padding: 30px 20px; margin-top: 40px; border-top: 2px solid #e0e0e0;">
        <a href="index.php" style="display: inline-flex; align-items: center; gap: 8px; color: #3498db; text-decoration: none; padding: 12px 24px; border-radius: 8px; background: #f8f9fa; font-weight: 500; font-size: 14px; transition: all 0.2s; border: 1px solid #dee2e6;" onmouseover="this.style.color='#2980b9'; this.style.background='#e9ecef'; this.style.borderColor='#ced4da';" onmouseout="this.style.color='#3498db'; this.style.background='#f8f9fa'; this.style.borderColor='#dee2e6';">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
            </svg>
            Voltar para Debug
        </a>
    </footer>
</div>

<script>
    var DATA_VERSIONAMENTO = "20250401";
    var defaultLogo = "https://app.foursquare.com/img/categories_v2/none_bg_32.png";

    function formatarLogoRede(logoObj) {
        if (!logoObj || typeof logoObj !== "object") return defaultLogo;
        var prefix = logoObj.prefix || "";
        var suffix = logoObj.suffix || "";
        if (!prefix || !suffix) return defaultLogo;
        var url;
        if (prefix.indexOf("img/general") > -1) {
            var cleanPrefix = prefix.replace(/\/+$/, "");
            var cleanSuffix = suffix.replace(/^\/+/, "");
            url = cleanPrefix + "/32x32/" + cleanSuffix;
        } else {
            url = prefix + "32" + suffix;
        }
        try {
            var parsedUrl = new URL(url, window.location.href);
            return parsedUrl.protocol === "https:" ? parsedUrl.href : defaultLogo;
        } catch (e) {
            return defaultLogo;
        }
    }

    function criarImagemRede(url, nome) {
        var imagem = document.createElement("img");
        imagem.src = url;
        imagem.alt = nome;
        imagem.onerror = function() {
            this.onerror = null;
            this.src = defaultLogo;
        };
        return imagem;
    }

    function exibirMensagem(container, mensagem, cor) {
        var elemento = document.createElement("div");
        elemento.style.padding = "12px";
        elemento.style.fontSize = "13px";
        elemento.style.color = cor;
        elemento.textContent = mensagem;
        container.replaceChildren(elemento);
    }

    // Estado local da tela de testes
    var redesOriginais = []; // [{ id, name, logo }]
    var redesAtuais = [];    // [{ id, name, logo }]
    var filteringSelectWidget = null;
    var comboBoxWidget = null;
    var storeOpt1 = null;
    var storeOpt3 = null;
    var timerOpt2 = null;

    function getToken() {
        return document.getElementById("tokenInput").value.trim();
    }

    // Inicialização do Dojo
    dojo.ready(function() {
        // Inicializa store vazio para Opção 1 (FilteringSelect)
        storeOpt1 = new dojo.store.Memory({
            data: [],
            idProperty: "id"
        });

        filteringSelectWidget = new dijit.form.FilteringSelect({
            id: "filteringSelectWidget",
            name: "chainSelect",
            store: storeOpt1,
            searchAttr: "name",
            placeHolder: "Selecione uma rede...",
            style: "width: 100%;",
            onChange: function(val) {
                if (val) {
                    var item = storeOpt1.get(val);
                    if (item) {
                        adicionarRede(item.id, item.name, item.logo);
                    }
                }
            }
        }, "filteringSelectMountPoint");
        filteringSelectWidget.startup();

        // Inicializa store para Opção 3 (ComboBox)
        storeOpt3 = new dojo.store.Memory({
            data: [],
            idProperty: "id"
        });

        comboBoxWidget = new dijit.form.ComboBox({
            id: "comboBoxWidget",
            name: "chainCombo",
            store: storeOpt3,
            searchAttr: "name",
            placeHolder: "Nome ou ID de 24 chars...",
            style: "width: 100%;"
        }, "comboBoxMountPoint");
        comboBoxWidget.startup();

        // Tenta preencher com cookie se existir
        if (typeof dojo.cookie === "function") {
            var cToken = dojo.cookie("oauth_token");
            if (cToken && cToken.length > 10) {
                document.getElementById("tokenInput").value = cToken;
            }
        }

        // Executa busca inicial padrão
        buscarParaFilteringSelect();
        buscarParaComboBox();
    });

    // Carrega dados da venue especificada
    async function carregarDadosLocal() {
        var token = getToken();
        var venueId = document.getElementById("venueIdInput").value.trim();
        var infoDiv = document.getElementById("localInfo");

        if (!token || !venueId) {
            alert("Informe o OAuth Token e o Venue ID.");
            return;
        }

        infoDiv.textContent = "⏳ Carregando dados da venue na API v2...";

        try {
            var url = "https://api.foursquare.com/v2/venues/" + encodeURIComponent(venueId) + "?oauth_token=" + encodeURIComponent(token) + "&v=" + DATA_VERSIONAMENTO;
            var resp = await fetch(url);
            var json = await resp.json();

            if (json.meta && json.meta.code === 200 && json.response && json.response.venue) {
                var v = json.response.venue;
                infoDiv.replaceChildren();
                infoDiv.appendChild(document.createTextNode("✅ Local encontrado: "));
                var venueName = document.createElement("strong");
                venueName.textContent = v.name || "";
                infoDiv.appendChild(venueName);
                var location = v.location || {};
                infoDiv.appendChild(document.createTextNode(" (" + (location.city || "") + "/" + (location.state || "") + ")"));

                redesOriginais = [];
                redesAtuais = [];

                if (v.venueChains && v.venueChains.length > 0) {
                    v.venueChains.forEach(function(ch) {
                        var logoUrl = formatarLogoRede(ch.logo);
                        var nome = (ch.bestName && ch.bestName.name) ? ch.bestName.name : ch.id;
                        var item = { id: ch.id, name: nome, logo: logoUrl };
                        redesOriginais.push(item);
                        redesAtuais.push(item);
                    });
                }

                renderizarRedesSelecionadas();
                atualizarSimulacaoPayload();
            } else {
                infoDiv.textContent = "❌ Erro ao buscar venue: " + (json.meta ? json.meta.errorDetail || json.meta.errorType : "Desconhecido");
            }
        } catch (e) {
            infoDiv.textContent = "❌ Erro de conexão: " + e.message;
        }
    }

    // Renderiza a lista de badges das redes selecionadas
    function renderizarRedesSelecionadas() {
        var container = document.getElementById("selectedChainsContainer");
        if (redesAtuais.length === 0) {
            container.replaceChildren();
            var emptyMessage = document.createElement("span");
            emptyMessage.style.color = "#94a3b8";
            emptyMessage.style.fontSize = "13px";
            emptyMessage.textContent = "Nenhuma rede selecionada para este local.";
            container.appendChild(emptyMessage);
            return;
        }

        container.replaceChildren();
        redesAtuais.forEach(function(ch, idx) {
            var isPrimary = (idx === 0);
            var badge = document.createElement("div");
            badge.className = "chain-badge" + (isPrimary ? " is-primary" : "");
            badge.appendChild(criarImagemRede(ch.logo, ch.name));

            var name = document.createElement("span");
            name.textContent = ch.name;
            badge.appendChild(name);

            var id = document.createElement("small");
            id.style.color = "#64748b";
            id.style.fontSize = "11px";
            id.textContent = "(" + String(ch.id).substr(0, 8) + "...)";
            badge.appendChild(id);

            if (isPrimary) {
                var primaryLabel = document.createElement("span");
                primaryLabel.style.cssText = "font-size: 10px; font-weight: 700; background: #6366f1; color: #fff; padding: 1px 6px; border-radius: 10px;";
                primaryLabel.textContent = "PRIMÁRIA";
                badge.appendChild(primaryLabel);
            } else {
                var primaryButton = document.createElement("button");
                primaryButton.type = "button";
                primaryButton.className = "badge-primary-btn";
                primaryButton.textContent = "tornar primária";
                primaryButton.addEventListener("click", function() { tornarPrimaria(idx); });
                badge.appendChild(primaryButton);
            }

            var removeButton = document.createElement("button");
            removeButton.type = "button";
            removeButton.className = "badge-remove";
            removeButton.title = "Remover rede";
            removeButton.style.background = "none";
            removeButton.style.border = "none";
            removeButton.style.padding = "0";
            removeButton.setAttribute("aria-label", "Remover " + ch.name);
            removeButton.textContent = "×";
            removeButton.addEventListener("click", function() { removerRede(idx); });
            badge.appendChild(removeButton);
            container.appendChild(badge);
        });

        atualizarSimulacaoPayload();
    }

    function adicionarRede(id, name, logo) {
        // Evita duplicatas
        for (var i = 0; i < redesAtuais.length; i++) {
            if (redesAtuais[i].id === id) {
                return;
            }
        }
        redesAtuais.push({
            id: id,
            name: name || id,
            logo: logo || defaultLogo
        });
        renderizarRedesSelecionadas();
    }

    function removerRede(index) {
        redesAtuais.splice(index, 1);
        renderizarRedesSelecionadas();
    }

    function tornarPrimaria(index) {
        if (index > 0 && index < redesAtuais.length) {
            var item = redesAtuais.splice(index, 1)[0];
            redesAtuais.unshift(item);
            renderizarRedesSelecionadas();
        }
    }

    // Calcula diferenças e simula o payload enviado ao proposeedit
    function atualizarSimulacaoPayload() {
        var idsOriginais = redesOriginais.map(function(r) { return r.id; });
        var idsAtuais = redesAtuais.map(function(r) { return r.id; });

        var addChainIds = [];
        var removeChainIds = [];
        var primaryChainId = "";

        // Adicionadas
        idsAtuais.forEach(function(id) {
            if (idsOriginais.indexOf(id) === -1) {
                addChainIds.push(id);
            }
        });

        // Removidas
        idsOriginais.forEach(function(id) {
            if (idsAtuais.indexOf(id) === -1) {
                removeChainIds.push(id);
            }
        });

        // Primária (se houve alteração)
        if (idsAtuais.length > 0) {
            primaryChainId = idsAtuais[0];
        }

        var payload = "POST https://api.foursquare.com/v2/venues/" + (document.getElementById("venueIdInput").value.trim() || "{venueId}") + "/proposeedit\n";
        payload += "Content-Type: application/x-www-form-urlencoded\n\n";
        payload += "oauth_token=" + getToken().substr(0, 10) + "...\n";
        payload += "&v=" + DATA_VERSIONAMENTO + "\n";

        if (primaryChainId && (idsOriginais.length === 0 || idsOriginais[0] !== primaryChainId)) {
            payload += "&primaryVenueChainId=" + primaryChainId + "  ← Rede Primária definida\n";
        }
        if (addChainIds.length > 0) {
            payload += "&addVenueChainIds=" + addChainIds.join(",") + "  ← Redes a adicionar\n";
        }
        if (removeChainIds.length > 0) {
            payload += "&removeVenueChainIds=" + removeChainIds.join(",") + "  ← Redes a remover\n";
        }
        if (!primaryChainId && addChainIds.length === 0 && removeChainIds.length === 0) {
            payload += "# (Nenhuma alteração de rede detectada)\n";
        }

        var box = document.getElementById("payloadOutput");
        box.style.display = "block";
        box.textContent = payload;
    }

    // OPÇÃO 1: Buscar e carregar no FilteringSelect
    async function buscarParaFilteringSelect() {
        var token = getToken();
        var q = document.getElementById("opt1Query").value.trim();
        if (!token || !q) return;

        try {
            var url = "https://api.foursquare.com/v2/chains/search?query=" + encodeURIComponent(q) + "&oauth_token=" + token + "&v=" + DATA_VERSIONAMENTO;
            var resp = await fetch(url);
            var json = await resp.json();

            if (json.response && json.response.results) {
                var items = json.response.results.map(function(r) {
                    var logoUrl = formatarLogoRede(r.logo);
                    var nome = (r.bestName && r.bestName.name) ? r.bestName.name : r.id;
                    return {
                        id: r.id,
                        name: nome + " (" + (r.count || 0) + " locais)",
                        logo: logoUrl
                    };
                });

                storeOpt1.setData(items);
                if (filteringSelectWidget) {
                    filteringSelectWidget.set("store", storeOpt1);
                    filteringSelectWidget.reset();
                }
            }
        } catch (e) {
            console.error("Erro na busca FilteringSelect:", e);
        }
    }

    // OPÇÃO 2: Busca Reativa com Cards e Debounce
    function debounceBuscarOpt2(valor) {
        clearTimeout(timerOpt2);
        timerOpt2 = setTimeout(function() {
            executarBuscaOpt2(valor);
        }, 300);
    }

    async function executarBuscaOpt2(query) {
        var token = getToken();
        var container = document.getElementById("opt2ResultsList");
        var loading = document.getElementById("opt2Loading");

        if (!query || query.length < 2) {
            container.style.display = "none";
            loading.style.display = "none";
            return;
        }

        loading.style.display = "block";
        container.style.display = "none";

        try {
            var url = "https://api.foursquare.com/v2/chains/search?query=" + encodeURIComponent(query) + "&oauth_token=" + token + "&v=" + DATA_VERSIONAMENTO;
            var resp = await fetch(url);
            var json = await resp.json();

            loading.style.display = "none";

            if (json.response && json.response.results && json.response.results.length > 0) {
                container.replaceChildren();
                json.response.results.forEach(function(r) {
                    var logoUrl = formatarLogoRede(r.logo);
                    var nome = (r.bestName && r.bestName.name) ? r.bestName.name : r.id;
                    var locaisTxt = (r.count || 0) + " locais";
                    var paisesTxt = (r.countries || 1) + " país(es)";

                    var result = document.createElement("div");
                    result.className = "visual-result-item";
                    result.addEventListener("click", function() { adicionarRede(r.id, nome, logoUrl); });

                    var left = document.createElement("div");
                    left.className = "visual-item-left";
                    left.appendChild(criarImagemRede(logoUrl, nome));
                    var details = document.createElement("div");
                    var title = document.createElement("div");
                    title.className = "visual-item-title";
                    title.textContent = nome;
                    var subtitle = document.createElement("div");
                    subtitle.className = "visual-item-subtitle";
                    subtitle.textContent = "ID: " + String(r.id || "");
                    details.appendChild(title);
                    details.appendChild(subtitle);
                    left.appendChild(details);
                    result.appendChild(left);

                    var right = document.createElement("div");
                    right.className = "visual-item-right";
                    var count = document.createElement("div");
                    count.style.fontWeight = "600";
                    count.style.color = "#475569";
                    count.textContent = locaisTxt;
                    var countries = document.createElement("div");
                    countries.textContent = paisesTxt;
                    right.appendChild(count);
                    right.appendChild(countries);
                    result.appendChild(right);
                    container.appendChild(result);
                });

                container.style.display = "block";
            } else {
                exibirMensagem(container, 'Nenhuma rede encontrada para "' + query + '"', "#94a3b8");
                container.firstChild.style.textAlign = "center";
                container.style.display = "block";
            }
        } catch (e) {
            loading.style.display = "none";
            exibirMensagem(container, "Erro ao buscar: " + e.message, "#ef4444");
            container.style.display = "block";
        }
    }

    // OPÇÃO 3: Buscar para ComboBox e Adicionar
    async function buscarParaComboBox() {
        var token = getToken();
        var q = document.getElementById("opt3Query").value.trim();
        if (!token || !q) return;

        try {
            var url = "https://api.foursquare.com/v2/chains/search?query=" + encodeURIComponent(q) + "&oauth_token=" + token + "&v=" + DATA_VERSIONAMENTO;
            var resp = await fetch(url);
            var json = await resp.json();

            if (json.response && json.response.results) {
                var items = json.response.results.map(function(r) {
                    var logoUrl = formatarLogoRede(r.logo);
                    var nome = (r.bestName && r.bestName.name) ? r.bestName.name : r.id;
                    return {
                        id: r.id,
                        name: nome,
                        logo: logoUrl
                    };
                });

                storeOpt3.setData(items);
                if (comboBoxWidget) {
                    comboBoxWidget.set("store", storeOpt3);
                }
            }
        } catch (e) {
            console.error("Erro na busca ComboBox:", e);
        }
    }

    function adicionarDoComboBox() {
        if (!comboBoxWidget) return;
        var valor = comboBoxWidget.get("value").trim();
        if (!valor) return;

        // Verifica se é item do store
        var items = storeOpt3.data;
        var matched = null;
        for (var i = 0; i < items.length; i++) {
            if (items[i].name === valor || items[i].id === valor) {
                matched = items[i];
                break;
            }
        }

        if (matched) {
            adicionarRede(matched.id, matched.name, matched.logo);
        } else {
            // Se digitou ID direto (24 chars hex)
            adicionarRede(valor, "Rede (" + valor.substr(0, 8) + "...) ", defaultLogo);
        }
    }
</script>
</body>
</html>
