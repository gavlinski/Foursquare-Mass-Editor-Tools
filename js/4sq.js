dojo.require("dijit.ProgressBar");
dojo.require("dijit.Dialog");
dojo.require("dijit.form.Button");
dojo.require("dijit.form.CheckBox");
dojo.require("dijit.form.TextBox");
dojo.require("dijit.form.ToggleButton");
dojo.require("dijit.Tooltip");
dojo.require("dijit.form.FilteringSelect");
dojo.require("dojo.data.ItemFileReadStore");
dojo.require("dijit.Tree");
dojo.require("dijit.Menu");
dojo.require("dojo.cookie");
dojo.require("dijit.form.Select");

var DATA_VERSIONAMENTO = "20250401";
var MESES = new Array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");

// Utilitários de logging (isLocalhost, debugLog, debugInfo) são providos por session-manager.js

var modo;
var DADOS_COMPLETOS = 0;
var DADOS_PARCIAIS = 1;

var CATEGORIA_HOME = "4bf58dd8d48988d103941735";
/*** States & Municipalities: City, County, Country, Neighborhood, State, Town, Village ***/
var CATEGORIAS_STATES_MUNICIPALITIES = [
	"530e33ccbcbc57f1066bbfe4", "50aa9e094b90af0d42d5de0d", "5345731ebcbc57f1066c39b2",
	"530e33ccbcbc57f1066bbff7", "4f2a25ac4b909258e854f55f", "530e33ccbcbc57f1066bbff8",
	"530e33ccbcbc57f1066bbff3", "530e33ccbcbc57f1066bbff9"
];
var CATEGORIA_CITY = "50aa9e094b90af0d42d5de0d";

var oauth_token; // Inicializado via dojo.ready() após módulos carregarem
var txt = "";
var json = "";

var csv = [];
var relatorio = [];

// Inicializa variáveis que dependem de módulos Dojo
dojo.ready(function() {
	oauth_token = getOauthToken();
});

function getOauthToken() {
	if (oauth_token && oauth_token !== "undefined" && oauth_token !== "null")
		return oauth_token;

	if (window.appBootstrap && window.appBootstrap.oauthToken) {
		oauth_token = window.appBootstrap.oauthToken;
		return oauth_token;
	}

	var cookieToken = dojo.cookie("oauth_token");
	if (cookieToken && cookieToken !== "undefined" && cookieToken !== "null") {
		oauth_token = cookieToken;
		return oauth_token;
	}

	return "";
}

function handleMissingOauthToken(contexto) {
	var mensagem = "Sessão indisponível para " + contexto + ". Faça login novamente.";
	debugLog("⚠️ " + mensagem);

	if (window.sessionStatusBarAPI)
		window.sessionStatusBarAPI.updateStatus(mensagem, "error", false);

	if (window.sessionManager && typeof window.sessionManager.handleCriticalSessionFailure === "function") {
		window.sessionManager.handleCriticalSessionFailure(mensagem, {
			title: "Sessão indisponível",
			redirectUrl: "index.php"
		});
	} else {
		setTimeout(function() {
			window.location.href = "index.php";
		}, 800);
	}

	return false;
}

var categorias = [];
var redes = [];
var store = {};
var categorySearchWidget = null;
var categorySearchStore = null;
var categorySearchItems = [];
var categoryPaths = {};

var timer;
var timerRede;
var timerBuscaRede;
var xhrBuscaRede;
var sequenciaBuscaRede = 0;

var redesInfoCache = {};
try {
	var savedRedesCache = localStorage.getItem('redes_cache');
	if (savedRedesCache) redesInfoCache = JSON.parse(savedRedesCache);
} catch(e) {}

var pendingChainRequests = {};

function formatarLogoRede(logoObj) {
	var defaultLogo = "https://app.foursquare.com/img/categories_v2/none_bg_32.png";
	if (!logoObj || typeof logoObj !== "object") return defaultLogo;
	var prefix = logoObj.prefix || "";
	var suffix = logoObj.suffix || "";
	if (!prefix || !suffix) return defaultLogo;
	if (prefix.indexOf("img/general") > -1) {
		var cleanPrefix = prefix.replace(/\/+$/, "");
		var cleanSuffix = suffix.replace(/^\/+/, "");
		return cleanPrefix + "/32x32/" + cleanSuffix;
	}
	return prefix + "32" + suffix;
}

function formatarLogoRedeUrl(url) {
	var defaultLogo = "https://app.foursquare.com/img/categories_v2/none_bg_32.png";
	if (!url || typeof url !== "string") return defaultLogo;
	if (url.indexOf("32x32") > -1 || url.indexOf("none_bg_32.png") > -1) return url;
	if (url.indexOf("img/general") > -1) {
		return url.replace(/\/img\/general\/+/, "/img/general/32x32/");
	}
	return url;
}

function validarIdRede(cId) {
	return /^[a-f\d]{24}$/i.test(cId || "");
}

function obterItensRede(rede) {
	if (!rede) return [];
	if (Array.isArray(rede.items)) {
		return rede.items.map(function(item) {
			return { id: String(item.id || ""), name: String(item.name || ""), logo: String(item.logo || "") };
		});
	}
	function lerLista(valor) {
		if (Array.isArray(valor)) return valor;
		if (typeof valor !== "string" || valor === "") return [];
		try {
			var parsed = JSON.parse(valor);
			if (Array.isArray(parsed)) return parsed;
		} catch(e) {}
		return valor.split(",");
	}
	var ids = lerLista(rede.ids);
	var nomes = lerLista(rede.nomes);
	var logos = lerLista(rede.logos);
	return ids.filter(Boolean).map(function(id, index) {
		return { id: String(id), name: String(nomes[index] || ""), logo: String(logos[index] || "") };
	});
}

function atualizarCamposRedes(i) {
	redes[i] = new Rede(obterItensRede(redes[i]));
	var items = redes[i].items;
	if (dojo.byId("chid" + i)) dojo.byId("chid" + i).value = items.map(function(item) { return item.id; }).join(",");
	if (dojo.byId("chna" + i)) dojo.byId("chna" + i).value = JSON.stringify(items.map(function(item) { return item.name; }));
	if (dojo.byId("chic" + i)) dojo.byId("chic" + i).value = JSON.stringify(items.map(function(item) { return item.logo; }));
}

function urlLogoRedeSegura(url) {
	var defaultLogo = "https://app.foursquare.com/img/categories_v2/none_bg_32.png";
	if (typeof url !== "string" || url === "") return defaultLogo;
	var link = document.createElement("a");
	link.href = url;
	return link.protocol === "https:" ? link.href : defaultLogo;
}

function renderizarIconeRede(elemento, i, item, permitirEdicao, opacity) {
	while (elemento.firstChild) elemento.removeChild(elemento.firstChild);
	var target = elemento;
	var targetId = "chainImg" + i;
	if (permitirEdicao) {
		target = document.createElement("a");
		target.id = "chainLnk" + i;
		target.href = "#";
		target.addEventListener("click", function(event) {
			event.preventDefault();
			editarRedes(i);
		});
		elemento.appendChild(target);
		targetId = target.id;
	}
	var image = document.createElement("img");
	image.id = "chainImg" + i;
	image.alt = "Rede";
	image.style.height = "22px";
	image.style.width = "22px";
	image.style.marginLeft = "0px";
	image.style.opacity = String(opacity);
	image.onerror = function() {
		this.onerror = null;
		this.src = "https://app.foursquare.com/img/categories_v2/none_bg_32.png";
	};
	image.src = urlLogoRedeSegura(item && item.logo ? formatarLogoRedeUrl(item.logo) : "");
	target.appendChild(image);
	var tooltip = item ? item.name : (permitirEdicao ? "Sem rede definida (clique para adicionar)" : "Sem rede definida");
	createTooltip(targetId, "<span style=\"font-size: 12px\">" + escapeTooltipHtml(tooltip || "Sem rede definida") + "</span>");
}

function isRedesSelecionada() {
	if (window.appBootstrap && typeof window.appBootstrap.editChains === "boolean") {
		return window.appBootstrap.editChains;
	}
	var span = dojo.byId("chainIcone0");
	return span && span.style.display !== "none";
}

function buscarInfoRede(cId, i) {
	if (!validarIdRede(cId)) return;
	if (redesInfoCache[cId]) {
		aplicarInfoRedeNaLinha(cId, redesInfoCache[cId].nome, redesInfoCache[cId].logo, i);
		return;
	}
	if (pendingChainRequests[cId]) {
		pendingChainRequests[cId].push(i);
		return;
	}
	pendingChainRequests[cId] = [i];
	var token = getOauthToken();
	if (!token) return;

	var xhr = new XMLHttpRequest();
	xhr.open("GET", "https://api.foursquare.com/v2/chains/" + cId + "?oauth_token=" + token + "&v=" + DATA_VERSIONAMENTO, true);
	xhr.onreadystatechange = function() {
		if (xhr.readyState === 4) {
			var nome = "Rede (" + cId.substr(0, 8) + "...)";
			var logo = "https://app.foursquare.com/img/categories_v2/none_bg_32.png";
			if (xhr.status === 200) {
				try {
					var resp = JSON.parse(xhr.responseText);
					if (resp.response && resp.response.venueChain) {
						var vc = resp.response.venueChain;
						if (vc.bestName && vc.bestName.name) {
							nome = vc.bestName.name;
						}
						logo = formatarLogoRede(vc.logo);
					}
				} catch(e) {}
			}
			redesInfoCache[cId] = { nome: nome, logo: logo };
			try {
				localStorage.setItem('redes_cache', JSON.stringify(redesInfoCache));
			} catch(e) {}
			var linhas = pendingChainRequests[cId] || [];
			delete pendingChainRequests[cId];
			for (var idx = 0; idx < linhas.length; idx++) {
				aplicarInfoRedeNaLinha(cId, nome, logo, linhas[idx]);
			}
		}
	};
	xhr.send();
}

function atualizarTooltipRedes(i, targetId) {
	var nomes = obterItensRede(redes[i]).map(function(item) { return item.name; }).join(", ");
	createTooltip(targetId || "chainLnk" + i, "<span style=\"font-size: 12px\">" + escapeTooltipHtml(nomes || "Sem rede definida") + "</span>");
}

function processarRedesVenue(venue, i) {
	redes[i] = new Rede([]);
	if (!isRedesSelecionada()) {
		if (dojo.byId("chid" + i)) dojo.byId("chid" + i).value = "";
		if (dojo.byId("chna" + i)) dojo.byId("chna" + i).value = "";
		if (dojo.byId("chic" + i)) dojo.byId("chic" + i).value = "";
		return;
	}

	if (venue.venueChains && venue.venueChains.length > 0) {
		for (var c = 0; c < venue.venueChains.length; c++) {
			var vChain = venue.venueChains[c];
			var cId = vChain.id || "";
			var cNome = "";
			var cLogo = "";

			if (vChain.bestName && vChain.bestName.name) cNome = vChain.bestName.name;
			if (vChain.logo) cLogo = formatarLogoRede(vChain.logo);

			if (cId && (!cNome || !cLogo || cLogo.indexOf("none_bg_32.png") > -1)) {
				if (redesInfoCache[cId]) {
					cNome = redesInfoCache[cId].nome;
					cLogo = redesInfoCache[cId].logo;
				} else {
					cNome = cNome || "Rede (" + cId.substr(0, 8) + "...)";
					cLogo = cLogo || "https://app.foursquare.com/img/categories_v2/none_bg_32.png";
					buscarInfoRede(cId, i);
				}
			} else if (cId && cNome && cLogo) {
				redesInfoCache[cId] = { nome: cNome, logo: cLogo };
				try { localStorage.setItem('redes_cache', JSON.stringify(redesInfoCache)); } catch(e) {}
			}

			redes[i].items.push({ id: cId, name: cNome, logo: cLogo });
		}
	}
	atualizarCamposRedes(i);
}

function atualizarIconeRede(i, permitirEdicao) {
	var chainIconeElem = dojo.byId("chainIcone" + i);
	if (!chainIconeElem) return;
	if (!isRedesSelecionada()) {
		chainIconeElem.style.display = "none";
		chainIconeElem.innerHTML = "";
		return;
	}

	chainIconeElem.style.display = "inline";
	var items = obterItensRede(redes[i]);
	renderizarIconeRede(chainIconeElem, i, items[0] || null, permitirEdicao, items.length ? 1 : (permitirEdicao ? 0.4 : 0.2));
}

function aplicarInfoRedeNaLinha(cId, nome, logo, i) {
	if (!document.forms[i]) return;
	var chainImg = dojo.byId("chainImg" + i);
	if (!chainImg) {
		chainImg = dojo.byId("chainLnk" + i);
	}
	var items = obterItensRede(redes[i]);
	var chainIndex = items.findIndex(function(item) { return item.id === cId; });
	if (chainIndex !== -1) {
		items[chainIndex].name = nome;
		items[chainIndex].logo = logo;
		redes[i] = new Rede(items);
		atualizarCamposRedes(i);
	}
	if (items.length && items[0].id === cId && chainImg && logo && logo.indexOf("none_bg_32.png") === -1) {
		chainImg.src = urlLogoRedeSegura(logo);
		chainImg.style.opacity = "1";
	}
	atualizarTooltipRedes(i);
}

var totalCarregadas = 0;
var totalNaoCarregadas = 0;

var linhasEditadas = [];
var totalParaSalvar = 0;
var totalEditadas = 0;

var linhasSelecionadas = [];
var totalSelecionadas = 0;

var failedRequests = {};
var totalParaSinalizar = 0;
var totalSinalizadas = 0;
var linhaVenueComMaisCheckins = 0;

var totalProgresso = 0;
var totalTimeout = 0;

var actionButton = "";

// Array para rastrear quais venues têm dados completos (modo DADOS_COMPLETOS ou recarregadas)
var venuesComDadosCompletos = [];

// Torna locais global para integração com Google Maps
window.locais = [];
// Variáveis legacas removidas - usando apenas window.googleMaps

var columnsStartIndex = 2;
//var totalInputsHidden = 14;
/*** Visible/editable input fields ***/
//var colunas = 0;

var venuellOriginais = [];

function addZero(i) {
	if (i < 10)
		i = "0" + i;
	return i;
}

function getDataHora() {
	var d = new Date();
	var dia = addZero(d.getDate());
	var horas = addZero(d.getHours());
	var minutos = addZero(d.getMinutes());
	var segundos = addZero(d.getSeconds());
	return dia + "/" + MESES[d.getMonth()] + "/" + d.getFullYear() + " " + horas + ":" + minutos + ":" + segundos;
}

function atualizarDicaResultado(item, imagem, dica) {
	try {
		dojo.byId(item).innerHTML = imagem;
		createTooltip(item, dica);
	} catch(e) {
		//console.log(e);
	}
}

function atualizarEditadas(i, timeout) {
	if (timeout == false) {
		linhasEditadas.splice(linhasEditadas.indexOf(i), 1);

		// Remove indicador visual de edição e erro após salvar com sucesso
		var linhaElement = dojo.byId("linha" + i);
		if (linhaElement) {
			dojo.removeClass(linhaElement, "edited");
			dojo.removeClass(linhaElement, "error"); // Remove erro se existir
		}
	}
	totalEditadas++;
	var editadas = linhasEditadas.length - totalTimeout;
	dijit.byId("saveProgress").update({maximum: totalParaSalvar, progress: totalEditadas});
	if (editadas > 1)
		dijit.byId("dlg_save").set("title", "Salvando " + editadas + " venues...");
	else if (editadas == 1)
		dijit.byId("dlg_save").set("title", "Salvando 1 venue...");
	else if (editadas == 0) {
		dijit.byId("saveButton").setAttribute('disabled', false);
		dijit.byId("reloadButton").setAttribute('disabled', false);
		var title = totalProgresso + " de " + totalEditadas + " venue";
		if (totalEditadas > 1)
			title += "s";
		if (totalProgresso == 0)
			title += " editadas";
		else if (totalProgresso == 1)
			title += " editada com sucesso";
		else
			title += " editadas com sucesso";
		dijit.byId("dlg_save").set("title", title);
		timer = setTimeout(function fecharPbSalvar() {
			dijit.byId("dlg_save").hide();
		}, 3000);
	}
}

function atualizarSinalizadas(i, timeout) {
	if (timeout == false)
		linhasSelecionadas.splice(linhasSelecionadas.indexOf(parseInt(i)), 1);
	totalSinalizadas++;
	var selecionadas = linhasSelecionadas.length - totalTimeout;
	dijit.byId("saveProgress").update({maximum: totalParaSinalizar, progress: totalSinalizadas});
	if (selecionadas > 1)
		dijit.byId("dlg_save").set("title", "Sinalizando " + selecionadas + " venues...");
	else if (selecionadas == 1)
		dijit.byId("dlg_save").set("title", "Sinalizando 1 venue...");
	else if (selecionadas == 0) {
		dijit.byId("menuSinalizar").setAttribute('disabled', false);
		var title = totalProgresso + " de " + totalSinalizadas + " venue";
		if (totalSinalizadas > 1)
			title += "s";
		if (totalProgresso == 0)
			title += " sinalizadas";
		else if (totalProgresso == 1)
			title += " sinalizada com sucesso";
		else
			title += " sinalizadas com sucesso";
		dijit.byId("dlg_save").set("title", title);
		if (timeout == false)
			dijit.byId(dojo.query("input[name=selecao]")[linhaVenueComMaisCheckins].id).setChecked(false);
		if (dojo.query("input[name=selecao]:enabled").length == 0)
			dijit.byId("menuSelecionar").setAttribute('disabled', true);
		timer = setTimeout(function fecharPbSalvar() {
			dijit.byId("dlg_save").hide();
		}, 3000);
	}
}

function atualizarFalhas(metodo, i, acao, timeout) {
	if (metodo == "GET") {
		desabilitarLinha(i);
		totalNaoCarregadas++;

		// Adiciona indicador visual de erro
		var linhaElement = dojo.byId("linha" + i);
		if (linhaElement) {
			dojo.removeClass(linhaElement, "edited"); // Remove estado editado se existir
			dojo.addClass(linhaElement, "error");
		}

		/*** Se tiver sido a última venue a ser carregada ***/
		if (totalCarregadas == (document.forms.length - totalNaoCarregadas)) {
			limparLinhasEditadas();
			// Atualiza marcadores se o mapa estiver carregado
			if (window.googleMaps && window.googleMaps.map && window.locais.length > 0) {
				window.googleMaps.updateMarkers(window.locais);
			}
			/*** Se nenhuma venue tiver sido carregada ***/
			if (totalNaoCarregadas == document.forms.length) {
				dijit.byId("menuSelecionar").setAttribute('disabled', true);
				dijit.byId("menuEditar").setAttribute('disabled', true);
				dijit.byId("menuExportar").setAttribute('disabled', true);
			}
		}
	} else if (metodo == "POST") {
		if (acao == "edit")
			atualizarEditadas(i, timeout);
		else if (acao == "flag")
			atualizarSinalizadas(i, timeout);
	}

	// Adiciona indicador visual de erro para POST (save/flag)
	if (metodo == "POST") {
		var linhaElement = dojo.byId("linha" + i);
		if (linhaElement) {
			dojo.removeClass(linhaElement, "edited"); // Remove estado editado
			dojo.addClass(linhaElement, "error");
		}
	}
}

/**
 * Limpa o array de linhas editadas e remove indicadores visuais
 * Chamada após salvar com sucesso ou ao recarregar dados
 */
function limparLinhasEditadas() {
	// Remove classe visual de todas as linhas editadas
	for (var i = 0; i < linhasEditadas.length; i++) {
		var linhaElement = dojo.byId("linha" + linhasEditadas[i]);
		if (linhaElement) {
			dojo.removeClass(linhaElement, "edited");
			dojo.removeClass(linhaElement, "error"); // Remove erro também
		}
	}
	linhasEditadas = [];
	dijit.byId("saveButton").setAttribute('disabled', false);
	dijit.byId("reloadButton").setAttribute('disabled', false);
}

function marcarLinhaEditada(i) {
	var indice = parseInt(i);
	if (linhasEditadas.indexOf(indice) === -1) {
		linhasEditadas.push(indice);
	}
	var linhaElement = dojo.byId("linha" + indice);
	if (linhaElement && !dojo.hasClass(linhaElement, "edited")) {
		dojo.addClass(linhaElement, "edited");
	}
	var saveButton = dijit.byId("saveButton");
	if (saveButton) saveButton.set("disabled", false);
}

function desabilitarLinha(i) {
	dojo.query('#linha' + i + ' input').forEach(
		function(inputElem) {
			//console.log(inputElem);
			if ((inputElem.type == 'checkbox') && (dijit.byId(inputElem.id).get("checked") == true))
				dijit.byId(inputElem.id).setChecked(false);
			if (inputElem.type == 'text') //|| (inputElem.type == 'checkbox')) //&& ((inputElem.value == ' ') || (inputElem.value == '')))
				dijit.byId(inputElem.id).setDisabled(true);
			if (dojo.byId("cid" + i).value == "")
				dojo.byId("icone" + i).innerHTML = "<img id=catImg" + i + " src='https://foursquare.com/img/categories_v2/none_bg_32.png' style='height: 22px; width: 22px; margin-left: 0px'>";
			else {
				dojo.byId("icone" + i).innerHTML = "<img id=catImg" + i + " src='" + dojo.byId("cic" + i).value.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'>";
				createTooltip("catImg" + i, "<span style=\"font-size: 12px\">" + dojo.byId("cna" + i).value.replace(/,/gi, ", ") + "</span>");
			}
			if (dojo.byId("chainIcone" + i)) {
				var chainItems = obterItensRede(redes[i]);
				renderizarIconeRede(dojo.byId("chainIcone" + i), i, chainItems[0] || null, false, chainItems.length ? 1 : 0.2);
			}
		}
	);
	var linhaEditada = linhasEditadas.indexOf(parseInt(i));
	if (linhaEditada != -1) {
		linhasEditadas.splice(linhaEditada, 1);
		// Remove classe visual
		var linhaElement = dojo.byId("linha" + i);
		if (linhaElement) {
			dojo.removeClass(linhaElement, "edited");
			dojo.removeClass(linhaElement, "error"); // Remove erro também
		}
	}
}

/**
 * Habilita todos os campos de texto de uma linha
 * Chamada antes de verificar se deve desabilitar baseado em categorias
 */
function habilitarCamposLinha(i) {
	dojo.query('#linha' + i + ' input').forEach(
		function(inputElem) {
			if (inputElem.type == 'text') {
				var widget = dijit.byId(inputElem.id);
				if (widget) {
					widget.setDisabled(false);
					// Remove readOnly também (usado em phone para categorias de estado/município)
					widget.attr("readOnly", false);
				}
			}
		}
	);
}

function compare(el1, el2, index) {
  return el1[index] == el2[index] ? 0 : (el1[index] < el2[index] ? -1 : 1);
}

function retryRequest(i) {
	if (failedRequests[i]) {
		var req = failedRequests[i];
		dojo.byId("result" + i).innerHTML = "<img src='img/loading.gif' alt='Retrying...'>";
		xmlhttpRequest(req.metodo, req.endpoint, req.acao, req.dados, i);
	}
}

function xmlhttpRequest(metodo, endpoint, acao, dados, i) {
	var xmlhttp = new XMLHttpRequest();
	var item = "result" + i;
	var imagem, dica;
	//var acao = /[^/]*$/.exec(endpoint)[0];
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			try {
				var resposta = JSON.parse(xmlhttp.responseText);
			} catch(e) {
				return false;
			}
			/*** O erro {"meta":{"code":400,"errorType":"param_error","errorDetail":"Must start with http:\/\/"}} é um bug da API que ocorre quando o campo url é enviado em branco. Mas mesmo dando erro, a venue é corretamente editada. ***/
			if ((xmlhttp.status == 200) || ((xmlhttp.status == 400) && (resposta.meta.errorType == "param_error") && (resposta.meta.errorDetail == "Must start with http:\/\/"))) {
				clearTimeout(xmlhttpTimeout);

				// Remove indicador visual de erro em caso de sucesso
				var linhaElement = dojo.byId("linha" + i);
				if (linhaElement) {
					dojo.removeClass(linhaElement, "error");
				}

				if (metodo == "POST") {
					imagem = "<img src='img/ok.png' alt='" + xmlhttp.responseText + "' style='vertical-align: middle;'>";
					totalProgresso++;
					if (acao == "edit") {
						dojo.byId("info" + i).innerHTML = dojo.byId("info" + i).innerHTML.replace(/%0A/gi, "");
						var dicaVenue = atualizarDicaVenue(i);
						createTooltip("venLnk" + i, dicaVenue);
						categorias[i].ids = document.forms[i]["categoryId"].value;
						categorias[i].nomes = document.forms[i]["categoryName"].value;
						categorias[i].icones = document.forms[i]["categoryIcon"].value;
						if (redes[i] && document.forms[i]["venueChainId"]) atualizarCamposRedes(i);
						if (venuellOriginais[i] != document.forms[i]["venuell"].value)
							venuellOriginais[i] = document.forms[i]["venuell"].value;
						dica = "<span style=\"font-size: 12px\">Editada com sucesso</span>";
						atualizarEditadas(i, false);
						var campoRedesRelatorio = /** @type {HTMLInputElement|null} */ (document.getElementById("chna" + i));
						var redesRelatorio = window.appBootstrap && window.appBootstrap.editChains && campoRedesRelatorio
							? obterItensRede(redes[i]).map(function(item) { return item.name; }).join(", ")
							: "";
						var campoNomeRelatorio = /** @type {HTMLInputElement|null} */ (document.forms[i].elements.namedItem("name"));
						relatorio.push(new Array(campoNomeRelatorio ? campoNomeRelatorio.value : "", "editada", getDataHora(), document.forms[i]["venue"].value, dojo.byId("cna" + i).value, dijit.byId("textareaComment").value, redesRelatorio));
						dijit.byId("menuItemExportarRelatorio").setAttribute("disabled", false);
					} else if (acao == "flag") {
						dica = "<span style=\"font-size: 12px\">Sinalizada com sucesso</span>";
						var problema = dojo.queryToObject(dados).problem;
						if ((problema != "public") && (problema != "private") && (problema != "not_closed") && (problema != "un_delete"))
							desabilitarLinha(i);
						atualizarSinalizadas(i, false);
						relatorio.push(new Array(document.forms[i]["name"].value, "sinalizada", getDataHora(), document.forms[i]["venue"].value, categorias[i].nomes, dijit.byId("textareaComment").value));
						dijit.byId("menuItemExportarRelatorio").setAttribute("disabled", false);
					}
					atualizarDicaResultado(item, imagem, dica);
				} else if ((metodo == "GET") && (acao == "reload") && (resposta.response.venue != undefined)) {
					// Ação de reload - atualiza venue individual
					atualizarTabela(resposta.response.venue, i);
					debugInfo("✅ Venue " + (i + 1) + " recarregada com sucesso!");
				} else if ((metodo == "GET") && (acao == "load") && (resposta.response.venue != undefined)) {
					// Ação de load normal
					atualizarTabela(resposta.response.venue, i);
				} else if ((metodo == "GET") && (resposta.response.categories == undefined) && (resposta.response.venue != undefined)) {
					// Fallback para carregamento sem especificar ação
					atualizarTabela(resposta.response.venue, i);
				} else if (resposta.response.categories != undefined) {
					montarArvore(resposta);
					debugInfo("Categorias recuperadas!");
					localStorage.setItem("categorias", JSON.stringify(resposta));
					var d = new Date();
					d.setHours(0, 0, 0, 0);
					dojo.cookie("data_categorias", d, { expires: 1 });
				}
			} else {
				clearTimeout(xmlhttpTimeout);
				atualizarFalhas(metodo, i, acao, false);
				failedRequests[i] = {metodo: metodo, endpoint: endpoint, acao: acao, dados: dados};
				var errorImg = "<img src='img/erro.png' onclick='retryRequest(" + i + ")' style='cursor: pointer;'";
				switch (xmlhttp.status) {
				case 400:
					imagem = errorImg + " alt='Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>";
					dica = "<span style=\"font-size: 12px\">Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>";
					break;
				case 401:
					imagem = errorImg + " alt='Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>";
					dica = "<span style=\"font-size: 12px\">Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>";
					break;
				case 403:
					imagem = errorImg + " alt='Erro 403: Forbidden, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>";
					dica = "<span style=\"font-size: 12px\">Erro 403: Forbidden, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>";
					break;
				case 404:
					imagem = errorImg + " alt='Erro 404: Not Found'>";
					dica = "<span style=\"font-size: 12px\">Erro 404: Not Found</span>";
					break;
				case 405:
					imagem = errorImg + " alt='Erro 405: Method Not Allowed'>";
					dica = "<span style=\"font-size: 12px\">Erro 405: Method Not Allowed</span>";
					break;
				case 409:
					imagem  = errorImg + " alt='Erro 409: Conflict'>";
					dica = "<span style=\"font-size: 12px\">Erro 409: Conflict</span>";
					break;
				case 500:
					imagem = errorImg + " alt='Erro 500: Internal Server Error'>";
					dica = "<span style=\"font-size: 12px\">Erro 500: Internal Server Error</span>";
					break;
				case 503:
					imagem = errorImg + " alt='Erro 503: backend read error'>";
					dica = "<span style=\"font-size: 12px\">Erro 503: backend read error</span>";
					break;
				case 504:
					imagem = errorImg + " alt='Erro 504: Gateway Time-out'>";
					dica = "<span style=\"font-size: 12px\">Erro 504: Gateway Time-out</span>";
					break;
				default:
					imagem = errorImg + " alt='Erro desconhecido: " + xmlhttp.status + "'>";
					dica = "<span style=\"font-size: 12px\">Erro desconhecido: " + xmlhttp.status + "</span>";
				} // switch
				atualizarDicaResultado(item, imagem, dica);
			}
		}
	}
	xmlhttp.open(metodo, endpoint, true);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.send(dados);
	var xmlhttpTimeout = setTimeout(function ajaxTimeout() {
		xmlhttp.abort();
		debugInfo(metodo + " (" + i + ")" + " abortado!");
		if (i != null) {
			totalTimeout++;
			atualizarFalhas(metodo, i, acao, true);
			failedRequests[i] = {metodo: metodo, endpoint: endpoint, acao: acao, dados: dados};
			imagem = "<img src='img/erro.png' onclick='retryRequest(" + i + ")' style='cursor: pointer;' alt='Erro: Request Timed Out'>";
			dica = "<span style=\"font-size: 12px\">Erro: Request Timed Out</span>";
			atualizarDicaResultado(item, imagem, dica);
		}
	}, 60000);
	return false;
}

function Categoria(ids, nomes, icones) {
	this.ids = ids;
	this.nomes = nomes;
	this.icones = icones;
}

function Rede(ids, nomes, logos) {
	this.items = Array.isArray(ids) ? ids.map(function(item) {
		return { id: String(item.id || ""), name: String(item.name || ""), logo: String(item.logo || "") };
	}) : obterItensRede({ ids: ids || "", nomes: nomes || "", logos: logos || "" });
	Object.defineProperty(this, "ids", {
		enumerable: false,
		get: function() { return this.items.map(function(item) { return item.id; }).join(","); }
	});
}

function nomeItemSelecionado(node) {
	if (!node) return "";
	var label = node.querySelector(".selected-item-name");
	return label ? label.textContent.trim() : node.textContent.replace(/,/g, "").trim();
}

function obterIdPrimarioSelecionado(container, ids) {
	if (!container || !ids) return "";
	var items = container.querySelectorAll(".selected-item");
	var idsLista = ids.split(",");
	for (var i = 0; i < items.length; i++) {
		if (items[i].getAttribute("data-primary") === "true") return items[i].getAttribute("data-item-id") || idsLista[i] || "";
	}
	return (items[0] && items[0].getAttribute("data-item-id")) || idsLista[0] || "";
}

function obterItensSelecionadosPrimariaPrimeiro(container, ids, icones) {
	var items = container.querySelectorAll(".selected-item");
	var idsLista = ids ? ids.split(",") : [];
	var iconesLista = icones ? icones.split(",") : [];
	var resultado = { nomes: [], ids: [], icones: [], items: [] };
	var indicePrimario = 0;
	for (var i = 0; i < items.length; i++) {
		var id = items[i].getAttribute("data-item-id") || idsLista[i] || "";
		var icone = items[i].getAttribute("data-item-icon") || iconesLista[i] || "";
		var nome = nomeItemSelecionado(items[i]);
		resultado.nomes.push(nome);
		resultado.ids.push(id);
		resultado.icones.push(icone);
		resultado.items.push({ id: id, name: nome, logo: icone });
		if (items[i].getAttribute("data-primary") === "true") indicePrimario = i;
	}
	if (indicePrimario > 0) {
		resultado.nomes.unshift(resultado.nomes.splice(indicePrimario, 1)[0]);
		resultado.ids.unshift(resultado.ids.splice(indicePrimario, 1)[0]);
		resultado.icones.unshift(resultado.icones.splice(indicePrimario, 1)[0]);
		resultado.items.unshift(resultado.items.splice(indicePrimario, 1)[0]);
	}
	return resultado;
}

function criarItemSelecionado(container, id, index, nome, icone, tipo, adminId, primary) {
	var item = document.createElement("div");
	item.id = id;
	item.className = "categoria selected-item";
	item.setAttribute("data-primary", primary ? "true" : "false");
	item.setAttribute("data-item-id", adminId || "");
	item.setAttribute("data-item-icon", icone || "");

	var image = document.createElement("img");
	image.className = "selected-item-icon";
	image.alt = "";
	image.src = urlLogoRedeSegura(icone);
	image.onerror = function() { this.src = "https://app.foursquare.com/img/categories_v2/none_bg_32.png"; };
	item.appendChild(image);

	var label = document.createElement(tipo === "rede" && adminId ? "a" : "span");
	label.className = "selected-item-name";
	label.textContent = nome;
	if (tipo === "rede" && adminId) {
		label.className += " selected-chain-admin-link";
		label.href = "https://app.foursquare.com/admin/chain/" + encodeURIComponent(adminId);
		label.target = "_blank";
		label.rel = "noopener noreferrer";
		label.addEventListener("click", function(event) { event.stopPropagation(); });
		label.addEventListener("dblclick", function(event) { event.stopPropagation(); });
	}
	item.appendChild(label);

	{
		var actions = document.createElement("div");
		actions.className = "selected-item-actions";
		if (primary) {
			var primaryLabel = document.createElement("span");
			primaryLabel.className = "selected-item-primary";
			primaryLabel.textContent = "Primária";
			actions.appendChild(primaryLabel);
		} else {
			var makePrimaryButton = document.createElement("button");
			makePrimaryButton.type = "button";
			makePrimaryButton.className = "selected-item-primary-action";
			makePrimaryButton.textContent = "Definir como principal";
			makePrimaryButton.addEventListener("click", function(event) {
				event.stopPropagation();
				if (tipo === "categoria") tornarCategoriaPrimaria(index);
				else tornarRedePrimaria(index);
			});
			actions.appendChild(makePrimaryButton);
		}

		var removeButton = document.createElement("button");
		removeButton.type = "button";
		removeButton.className = "selected-item-remove";
		removeButton.setAttribute("aria-label", "Remover " + (tipo === "categoria" ? "categoria " : "rede ") + nome);
		removeButton.title = tipo === "categoria" ? "Remover categoria" : "Remover rede";
		removeButton.innerHTML = "<span aria-hidden=\"true\">&times;</span>";
		removeButton.addEventListener("click", function(event) {
			event.stopPropagation();
			if (tipo === "categoria") removerCategoria(index);
			else removerRede(index);
		});
		actions.appendChild(removeButton);
		item.appendChild(actions);
	}
	container.appendChild(item);
	return item;
}

function atualizarCategorias(nomes, ids, icones, primaryId) {
	var container = dojo.byId("catsContainer");
	for (var tooltipIndex = 1; tooltipIndex <= 3; tooltipIndex++) {
		destroyTooltip("categoria" + tooltipIndex);
	}
	nomes = nomes || [];
	var idsLista = ids ? ids.split(",") : [];
	if (primaryId === undefined) primaryId = obterIdPrimarioSelecionado(container, dojo.byId("catsIds").innerHTML);
	if (idsLista.indexOf(primaryId) === -1) primaryId = idsLista[0] || "";
	var iconesLista = icones ? icones.split(",") : [];
	container.innerHTML = "";
	if (!nomes || nomes.length === 0) {
		container.innerHTML = "<span class='category-selection-empty'>Nenhuma categoria selecionada</span>";
	} else {
		for (var j = 0; j < nomes.length; j++) {
			var categoryIndex = j + 1;
			criarItemSelecionado(container, "categoria" + categoryIndex, categoryIndex, nomes[j], iconesLista[j], "categoria", "", idsLista[j] === primaryId);
		}
	}
	dojo.byId("catsIds").innerHTML = ids || "";
	dojo.byId("catsIcones").innerHTML = icones || "";
	var saveButton = dijit.byId("saveCatsButton");
	if (saveButton) {
		saveButton.set("disabled", !idsLista.some(function(id) { return id !== ""; }));
	}
	atualizarStatusBuscaCategoria("", "");
	//console.log(dojo.byId("catsIcones").innerHTML);
}

function atualizarBuscaCategorias(categoriasArvore) {
	var items = [];
	categoryPaths = {};

	function indexarCategoria(categoria, caminhoAnterior) {
		var caminho = caminhoAnterior.concat(categoria.id);
		categoryPaths[categoria.id] = caminho;
		if (caminhoAnterior.length > 0) {
			items.push({
				id: categoria.id,
				name: categoria.name,
				icon: categoria.icon
			});
		}
		(categoria.children || []).forEach(function(categoriaFilha) {
			indexarCategoria(categoriaFilha, caminho);
		});
	}

	(categoriasArvore || []).forEach(function(categoriaRaiz) {
		indexarCategoria(categoriaRaiz, []);
	});
	items.sort(function(a, b) {
		return a.name.localeCompare(b.name);
	});
	categorySearchItems = items;

	categorySearchStore = new dojo.data.ItemFileReadStore({
		data: {
			identifier: "id",
			label: "name",
			items: items
		}
	});
	if (categorySearchWidget) {
		categorySearchWidget.set("store", categorySearchStore);
		categorySearchWidget.reset();
	}
}

function inicializarBuscaCategorias() {
	if (categorySearchWidget) return;
	if (!categorySearchStore) {
		categorySearchStore = new dojo.data.ItemFileReadStore({
			data: { identifier: "id", label: "name", items: [] }
		});
	}

	categorySearchWidget = new dijit.form.FilteringSelect({
		id: "categorySearchWidget",
		name: "categorySearch",
		store: categorySearchStore,
		searchAttr: "name",
		queryExpr: "*${0}*",
		highlightMatch: "all",
		autoComplete: false,
		required: false,
		placeHolder: "Digite para buscar uma categoria...",
		style: "width: 100%;",
		onChange: function(value) {
			if (!value || !categorySearchStore) return;
			categorySearchStore.fetchItemByIdentity({
				identity: value,
				onItem: function(item) {
					if (!item) {
						atualizarStatusBuscaCategoria("Selecione uma categoria da lista.", "error");
						return;
					}
						processarCategoriaBusca(
							categorySearchStore.getValue(item, "id"),
							categorySearchStore.getLabel(item),
							categorySearchStore.getValue(item, "icon"),
							true
						);
					categorySearchWidget.set("value", "");
				}
			});
		}
	}, "categorySearchMountPoint");
	categorySearchWidget.startup();
	categorySearchWidget.textbox.addEventListener("blur", function(event) {
		var termo = event.target.value.trim().toLocaleLowerCase();
		if (!termo) return;
		var categoria = categorySearchItems.filter(function(item) {
			return item.name.trim().toLocaleLowerCase() === termo;
		})[0];
		if (!categoria) return;

		processarCategoriaBusca(categoria.id, categoria.name, categoria.icon);
		categorySearchWidget.set("value", "");
	});
}

function processarCategoriaBusca(id, nome, icone, focarArvore) {
	var result = adicionarCategoriaSelecionada(id, nome, icone);
	if (result === "added") {
		atualizarStatusBuscaCategoria("Categoria adicionada.", "success");
		if (focarArvore) selecionarCategoriaNaArvore(id);
	} else if (result === "duplicate") {
		atualizarStatusBuscaCategoria("Esta categoria já está selecionada.", "error");
		if (focarArvore) selecionarCategoriaNaArvore(id);
	} else {
		atualizarStatusBuscaCategoria("O limite de 3 categorias foi atingido.", "error");
	}
}

function atualizarStatusBuscaCategoria(message, status) {
	var statusNode = dojo.byId("categorySearchStatus");
	if (!statusNode) return;
	statusNode.textContent = message || "";
	statusNode.className = "category-search-status" + (status ? " is-" + status : "");
}

function selecionarCategoriaNaArvore(id) {
	var tree = dijit.byId("treeContainer");
	var path = categoryPaths[id];
	if (!tree || !path) return;

	function expandirCaminho(node, pathIndex) {
		var children = node.getChildren();
		var targetNode = null;
		for (var i = 0; i < children.length; i++) {
			if (children[i].getIdentity() === path[pathIndex]) {
				targetNode = children[i];
				break;
			}
		}
		if (!targetNode) return;

		if (pathIndex === path.length - 1) {
			tree.set("selectedNodes", [targetNode]);
			tree.focusNode(targetNode);
			return;
		}

		tree._expandNode(targetNode).then(function() {
			expandirCaminho(targetNode, pathIndex + 1);
		});
	}

	expandirCaminho(tree.rootNode, 0);
}

function adicionarCategoriaSelecionada(id, nome, icone) {
	var idsNode = dojo.byId("catsIds");
	var ids = idsNode && idsNode.innerHTML ? idsNode.innerHTML.split(",") : [];
	if (ids.indexOf(id) !== -1) return "duplicate";
	if (ids.length >= 3) return "limit";

	var nomes = dojo.query("#catsContainer .selected-item").map(function(node) {
		return nomeItemSelecionado(node);
	});
	var iconesNode = dojo.byId("catsIcones");
	var icones = iconesNode && iconesNode.innerHTML ? iconesNode.innerHTML.split(",") : [];
	ids.push(id);
	nomes.push(nome);
	icones.push(icone);
	atualizarCategorias(nomes, ids.join(","), icones.join(","));
	return "added";
}

/**
 * Conta quantas venues têm dados completos (podem ter categorias editadas)
 * @returns {number} Quantidade de venues com dados completos e não desabilitadas
 */
function contarVenuesComDadosCompletos() {
	var count = 0;
	for (var i = 0; i < document.forms.length; i++) {
		if (venuesComDadosCompletos.indexOf(i) !== -1 && dojo.query("input[name=selecao]")[i].disabled != true) {
			count++;
		}
	}
	return count;
}

/**
 * Verifica se há venues com dados parciais (sem todas as categorias)
 * @returns {boolean} True se houver pelo menos uma venue com dados parciais
 */
function temVenuesComDadosParciais() {
	for (var i = 0; i < document.forms.length; i++) {
		if (venuesComDadosCompletos.indexOf(i) === -1 && dojo.query("input[name=selecao]")[i].disabled != true) {
			return true;
		}
	}
	return false;
}

function editarCategorias(i) {
	var nomes = [];
	var ids =	 "";
	var icones = "";
	if (dojo.byId("cid" + i).value != "") {
		nomes = dojo.byId("cna" + i).value.split(",", 3);
		ids = dojo.byId("cid" + i).value;
		icones = dojo.byId("cic" + i).value;
	}
	atualizarCategorias(nomes, ids, icones, ids ? ids.split(",")[0] : "");
	dojo.byId("venueIndex").innerHTML = i;

	// Garante que a árvore de categorias está montada antes de abrir modal
	// Verifica se existe o widget Tree real (não apenas o container DOM)
	var containerElement = dojo.byId("treeContainer");
	var hasTreeWidget = containerElement && containerElement.children && containerElement.children.length > 0;

	if (!hasTreeWidget) {
		debugLog('🌳 Árvore de categorias não existe ou está vazia, verificando cache...');
		var cachedCategories = localStorage.getItem('categorias');
		if (cachedCategories) {
			try {
				var resposta = JSON.parse(cachedCategories);
				debugLog('✅ Reconstruindo árvore a partir do cache');
				montarArvore(resposta);
				debugLog('🌳 Árvore reconstruída com sucesso');
			} catch (e) {
				debugLog('❌ Erro ao reconstruir árvore, carregando da API:', e);
				carregarListaCategorias();
			}
		} else {
			debugLog('📡 Cache vazio, carregando categorias da API');
			carregarListaCategorias();
		}
	} else {
		debugLog('✅ Árvore de categorias já existe, reutilizando');
	}

	dijit.byId("dlg_cats").show();
	inicializarBuscaCategorias();
	if (categorySearchStore) categorySearchWidget.set("store", categorySearchStore);
	categorySearchWidget.set("value", "");
	atualizarStatusBuscaCategoria("", "");

	// Habilita checkbox apenas se houver 2+ venues com dados completos
	var totalComDadosCompletos = contarVenuesComDadosCompletos();
	if (totalComDadosCompletos >= 2) {
		dijit.byId("editAllCheckbox").attr("checked", false);
		dijit.byId("editAllCheckbox").attr("disabled", false);
	} else {
		dijit.byId("editAllCheckbox").attr("checked", false);
		dijit.byId("editAllCheckbox").attr("disabled", true);
	}
}

function removerCategoria(i) {
	if (timer)
		clearTimeout(timer);
	timer = setTimeout(function remover() {
		var container = dojo.byId("catsContainer");
		var items = container.querySelectorAll(".selected-item");
		var ids = dojo.byId("catsIds").innerHTML.split(",");
		var icones = dojo.byId("catsIcones").innerHTML.split(",");
		var nomes = [];
		var primaryId = obterIdPrimarioSelecionado(container, dojo.byId("catsIds").innerHTML);
		var removedName = "";
		for (var itemIndex = 0; itemIndex < items.length; itemIndex++) {
			nomes.push(nomeItemSelecionado(items[itemIndex]));
			if (itemIndex === parseInt(i, 10) - 1) removedName = nomes[itemIndex];
		}
		var targetIndex = parseInt(i, 10) - 1;
		ids.splice(targetIndex, 1);
		icones.splice(targetIndex, 1);
		nomes.splice(targetIndex, 1);
		if (ids.indexOf(primaryId) === -1) primaryId = ids[0] || "";
		atualizarCategorias(nomes, ids.join(","), icones.join(","), primaryId);
		atualizarStatusBuscaCategoria('Categoria "' + removedName + '" removida.', "success");
	}, 250);
}

function tornarCategoriaPrimaria(i) {
	var container = dojo.byId("catsContainer");
	var items = container.querySelectorAll(".selected-item");
	var targetIndex = parseInt(i, 10) - 1;
	var categoryName = nomeItemSelecionado(items[targetIndex]);
	var alreadyPrimary = items[targetIndex].getAttribute("data-primary") === "true";
	var ids = dojo.byId("catsIds").innerHTML;
	var icones = dojo.byId("catsIcones").innerHTML;
	var nomes = [];
	for (var itemIndex = 0; itemIndex < items.length; itemIndex++) {
		nomes.push(nomeItemSelecionado(items[itemIndex]));
	}
	var primaryId = ids ? ids.split(",")[targetIndex] : "";
	atualizarCategorias(nomes, ids, icones, primaryId);
	var message = alreadyPrimary
		? 'Categoria "' + categoryName + '" já é a primária.'
		: 'Categoria "' + categoryName + '" definida como primária.';
	atualizarStatusBuscaCategoria(message, "success");
}

function salvarCategorias() {
	var venueIndex, venueLoop;
	var nomes = "";

	// Verifica se checkbox está marcada e há venues com dados parciais
	if (dijit.byId("editAllCheckbox").checked && temVenuesComDadosParciais()) {
		// Mostra modal de confirmação
		mostrarConfirmacaoEdicaoMultiplaCategorias();
		return;
	}

	// Processa a edição normalmente
	processarEdicaoCategorias();
}

/**
 * Mostra modal de confirmação quando há venues com dados parciais
 */
function mostrarConfirmacaoEdicaoMultiplaCategorias() {
	var totalComDadosCompletos = contarVenuesComDadosCompletos();
	var totalParciais = 0;
	for (var i = 0; i < document.forms.length; i++) {
		if (venuesComDadosCompletos.indexOf(i) === -1 && dojo.query("input[name=selecao]")[i].disabled != true) {
			totalParciais++;
		}
	}

	// Textos com singular e plural corretos
	var textoParciais = totalParciais === 1
		? "Há <strong>1 local</strong> carregado com dados parciais que não pode ter as categorias alteradas sem risco de perda de informações."
		: "Há <strong>" + totalParciais + " locais</strong> carregados com dados parciais que não podem ter as categorias alteradas sem risco de perda de informações.";

	var textoCompletos = totalComDadosCompletos === 1
		? "Apenas <strong>1 local</strong> carregado com dados completos será alterado."
		: "Apenas <strong>" + totalComDadosCompletos + " locais</strong> carregados com dados completos serão alterados.";

	// Atualiza apenas o conteúdo da mensagem, não o dialog inteiro
	dojo.byId("confirmEditAllCategoriesMessage").innerHTML =
		"<p style='margin: 10px 0;'><strong>Atenção:</strong> " + textoParciais + "</p>" +
		"<p style='margin: 10px 0;'>" + textoCompletos + "</p>" +
		"<p style='margin: 10px 0;'><strong>O que deseja fazer?</strong></p>";

	dijit.byId("dlg_confirmEditAllCategories").show();
}

/**
 * Processa a edição de categorias (aplicável apenas a venues com dados completos se editAllCheckbox estiver marcada)
 */
function processarEdicaoCategorias() {
	var venueIndex, venueLoop;
	var nomes = "";
	if (dojo.byId("catsIds").innerHTML != "") {
		var selecionadas = obterItensSelecionadosPrimariaPrimeiro(
			dojo.byId("catsContainer"),
			dojo.byId("catsIds").innerHTML,
			dojo.byId("catsIcones").innerHTML
		);
		nomes = selecionadas.nomes.join(",");
		dojo.byId("catsIds").innerHTML = selecionadas.ids.join(",");
		dojo.byId("catsIcones").innerHTML = selecionadas.icones.join(",");
		if (dijit.byId("editAllCheckbox").checked) {
			venueIndex = 0;
			venueLoop = document.forms.length - 1;
		} else {
			venueIndex = dojo.byId("venueIndex").innerHTML;
			venueLoop = venueIndex;
		}
		for (i = venueIndex; i <= venueLoop; i++) {
			// Se editAllCheckbox estiver marcada, aplica apenas em venues com dados completos
			var podeEditar = !dijit.byId("editAllCheckbox").checked || venuesComDadosCompletos.indexOf(i) !== -1;

			if (dojo.query("input[name=selecao]")[i].disabled != true && podeEditar) {
				dojo.byId("cna" + i).value = nomes;
				dojo.byId("cid" + i).value = dojo.byId("catsIds").innerHTML;
				dojo.byId("cic" + i).value = dojo.byId("catsIcones").innerHTML;
				dojo.byId("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:editarCategorias(" + i + ")'><img id=catImg" + i + " src='" + dojo.byId("cic" + i).value.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'></a>";

				// SOLUÇÃO SIMPLES: Lê categoryId ORIGINAL do CSV (essa coluna NUNCA é modificada)
				// Ela sempre contém o estado original do Foursquare, servindo como fonte da verdade
				var indexCategoryId = csv[0].indexOf("categoryId");
				var categoryIdOriginalCSV = "";
				var categoriasOriginais = [];

				if (indexCategoryId !== -1 && csv[parseInt(i) + 1] && csv[parseInt(i) + 1][indexCategoryId]) {
					categoryIdOriginalCSV = csv[parseInt(i) + 1][indexCategoryId].replace(/"/g, "");
					if (categoryIdOriginalCSV) {
						categoriasOriginais = categoryIdOriginalCSV.split(",");
					}
				}

				// Pega categorias NOVAS selecionadas na árvore
				var categoryIds = dojo.byId("cid" + i).value;
				var categoriasNovas = categoryIds.split(",");

				debugLog('📋 CSV[categoryId] ORIGINAL (imutável) - Linha', i, ':', categoriasOriginais.join(', ') || '(vazio)');
				debugLog('📋 Categorias NOVAS (selecionadas) - Linha', i, ':', categoriasNovas.join(', '));

				// Calcula diferenças comparando SEMPRE com categorias ORIGINAIS do CSV
				// Categorias removidas: estavam no original mas não estão mais nas novas
				categoriasRemover = categoriasOriginais.filter(function(val) {
					return categoriasNovas.indexOf(val) == -1;
				});

				// Categorias adicionadas (secundárias): estão nas novas mas não no original
				// IMPORTANTE: Só adiciona secundárias (índices 1 e 2) porque primaryCategoryId já cuida da primária
				var categoriasSecundariasNovas = [];
				for (var j = 1; j < categoriasNovas.length && j <= 2; j++) {
					if (categoriasOriginais.indexOf(categoriasNovas[j]) == -1) {
						categoriasSecundariasNovas.push(categoriasNovas[j]);
					}
				}

				debugLog('🔍 Diferenças calculadas - Adicionar:', categoriasSecundariasNovas.join(',') || '(nenhuma)', '| Remover:', categoriasRemover.join(',') || '(nenhuma)');

				// Atualiza removeCategoryIds no CSV (SEMPRE, mesmo se vazio para limpar valores antigos)
				var indexRemove = csv[0].indexOf("removeCategoryIds");
				if (categoriasRemover.length > 0) {
					csv[parseInt(i) + 1][indexRemove] = '"' + categoriasRemover.toString() + '"';
					debugLog('🗑️ CSV[removeCategoryIds] atualizado - Linha', i, ':', categoriasRemover.toString());
				} else {
					csv[parseInt(i) + 1][indexRemove] = '""'; // Limpa coluna se não há categorias a remover
					debugLog('ℹ️ Nenhuma categoria removida na linha', i, '- CSV[removeCategoryIds] limpo');
				}

				// Atualiza primaryCategoryId se mudou em relação ao original
				if (categoriasOriginais.length > 0 && categoriasOriginais[0] != categoriasNovas[0]) {
					var index = csv[0].indexOf("primaryCategoryId");
					csv[parseInt(i) + 1][index] = '"' + categoriasNovas[0] + '"';
					debugLog('⭐ CSV[primaryCategoryId] atualizado - Linha', i, ': de', categoriasOriginais[0], 'para', categoriasNovas[0]);
				} else if (categoriasOriginais.length === 0) {
					// Venue com dados parciais sendo editada pela primeira vez
					var index = csv[0].indexOf("primaryCategoryId");
					csv[parseInt(i) + 1][index] = '"' + categoriasNovas[0] + '"';
					debugLog('⭐ CSV[primaryCategoryId] definido pela primeira vez - Linha', i, ':', categoriasNovas[0]);
				} else {
					debugLog('ℹ️ Categoria primária não mudou na linha', i, '(permanece:', categoriasOriginais[0] || 'vazio', ')');
				}

				// Atualiza addCategoryIds no CSV (SEMPRE, mesmo se vazio para limpar valores antigos)
				var indexAdd = csv[0].indexOf("addCategoryIds");
				if (categoriasSecundariasNovas.length > 0) {
					csv[parseInt(i) + 1][indexAdd] = '"' + categoriasSecundariasNovas.toString() + '"';
					debugLog('➕ CSV[addCategoryIds] atualizado - Linha', i, ':', categoriasSecundariasNovas.toString());
				} else {
					csv[parseInt(i) + 1][indexAdd] = '""'; // Limpa coluna se não há categorias a adicionar
					debugLog('ℹ️ Nenhuma categoria secundária nova na linha', i, '- CSV[addCategoryIds] limpo');
				}

				// AGORA SIM: Atualiza array global categorias[] após calcular diferenças
				if (categorias[i]) {
					categorias[i].ids = categoryIds;
					categorias[i].nomes = nomes;
					categorias[i].icones = dojo.byId("cic" + i).value;
					debugLog('📝 Array categorias[] atualizado - Índice', i);
				}
				dojo.byId("result" + i).innerHTML = "";
				marcarLinhaEditada(i);
				//console.log(dojo.byId("cna" + i).value);
				//console.log(dojo.byId("cid" + i).value);
				//console.log(dojo.byId("cic" + i).value);
				//console.log(csv[parseInt(i) + 1][2], csv[parseInt(i) + 1][index]);
				createTooltip("catLnk" + i, "<span style=\"font-size: 12px\">" + nomes.replace(/,/gi, ", ") + "</span>");
			}
		}
		dijit.byId('dlg_cats').hide();
		//dijit.byId("menuItemExportarCSV").setAttribute("disabled", true);
	}
}

function atualizarRedes(items, primaryId) {
	var container = dojo.byId("chainsContainer");
	if (!container) return;
	items = (items || []).map(function(item) {
		return { id: String(item.id || ""), name: String(item.name || ""), logo: String(item.logo || "") };
	});
	if (primaryId === undefined) primaryId = obterIdPrimarioSelecionado(container, "");
	if (!items.some(function(item) { return item.id === primaryId; })) primaryId = items.length ? items[0].id : "";
	container.innerHTML = "";
	if (items.length > 0) {
		for (var j = 0; j < items.length; j++) {
			criarItemSelecionado(container, "rede" + (j + 1), j + 1, items[j].name, items[j].logo, "rede", items[j].id, items[j].id === primaryId);
		}
	} else {
		container.innerHTML = "<span class='chain-selection-empty'>Nenhuma rede selecionada</span>";
	}
	if (dojo.byId("chainsIds")) dojo.byId("chainsIds").textContent = JSON.stringify(items);
	if (dojo.byId("chainsLogos")) dojo.byId("chainsLogos").textContent = "";
}

function configurarEdicaoMultiplaRedes(marcado) {
	var habilitada = contarVenuesComDadosCompletos() >= 2;
	dijit.byId("editAllChainsCheckbox").attr("checked", habilitada && marcado);
	dijit.byId("editAllChainsCheckbox").attr("disabled", !habilitada);
}

function configurarBuscaReativaRede() {
	if (timerBuscaRede) clearTimeout(timerBuscaRede);
	sequenciaBuscaRede++;
	if (xhrBuscaRede) {
		xhrBuscaRede.abort();
		xhrBuscaRede = null;
	}
	var inputWidget = dijit.byId("inputChainSearch");
	if (!inputWidget || !inputWidget.textbox || inputWidget.textbox.chainSearchListenersAdded) return;
	var input = inputWidget.textbox;
	input.chainSearchListenersAdded = true;
	input.addEventListener("input", function() {
		debounceBuscarRedePeloNome(inputWidget.get("value"));
	});
	input.addEventListener("keydown", function(event) {
		if (event.key === "Enter" || event.keyCode === 13) {
			event.preventDefault();
			buscarRedePeloNome(inputWidget.get("value"));
		}
	});
}

function editarRedes(i) {
	var items = obterItensRede(redes[i]);
	atualizarRedes(items, items.length ? items[0].id : "");
	dojo.byId("venueChainIndex").innerHTML = i;

	var resContainer = dojo.byId("chainsResultsContainer");
	if (resContainer) {
		resContainer.innerHTML = "";
		resContainer.style.display = "none";
	}
	atualizarStatusBuscaRede("", "");

	dijit.byId("dlg_chains").show();
	configurarBuscaReativaRede();
	if (dijit.byId("inputChainSearch")) dijit.byId("inputChainSearch").set("value", "");

	configurarEdicaoMultiplaRedes(false);
}

function removerRede(index) {
	if (timerRede)
		clearTimeout(timerRede);
	timerRede = setTimeout(function remover() {
		var chainElements = dojo.query("#chainsContainer .categoria");
		var selecionadas = obterItensSelecionadosPrimariaPrimeiro(dojo.byId("chainsContainer"));
		var currentItems = selecionadas.items;
		var primaryId = obterIdPrimarioSelecionado(dojo.byId("chainsContainer"), "");
		var targetIdx = parseInt(index) - 1;
		var removedName = currentItems[targetIdx] && currentItems[targetIdx].name;
		if (targetIdx >= 0 && targetIdx < currentItems.length) {
			currentItems.splice(targetIdx, 1);
		}
		if (!currentItems.some(function(item) { return item.id === primaryId; })) primaryId = currentItems.length ? currentItems[0].id : "";
		atualizarRedes(currentItems, primaryId);
		if (removedName) {
			atualizarStatusBuscaRede('Rede "' + removedName + '" removida.', "success");
		}
	}, 250);
}

function tornarRedePrimaria(index) {
	if (timerRede)
		clearTimeout(timerRede);
	var selecionadas = obterItensSelecionadosPrimariaPrimeiro(dojo.byId("chainsContainer"));
	var currentItems = selecionadas.items;
	var targetIdx = parseInt(index) - 1;
	if (targetIdx >= 0 && targetIdx < currentItems.length) {
		var primaryName = currentItems[targetIdx].name;
		var primaryId = currentItems[targetIdx].id;
		var alreadyPrimary = obterIdPrimarioSelecionado(dojo.byId("chainsContainer"), "") === primaryId;
		atualizarRedes(currentItems, primaryId);
		var message = alreadyPrimary
			? 'Rede "' + primaryName + '" já é a primária.'
			: 'Rede "' + primaryName + '" definida como primária.';
		atualizarStatusBuscaRede(message, "success");
	}
}

function atualizarStatusBuscaRede(message, status) {
	var statusNode = dojo.byId("chainSearchStatus");
	if (!statusNode) return;
	statusNode.textContent = message || "";
	statusNode.className = "chain-search-status" + (status ? " is-" + status : "");
}

function salvarRedes() {
	if (dijit.byId("editAllChainsCheckbox") && dijit.byId("editAllChainsCheckbox").checked && temVenuesComDadosParciais()) {
		mostrarConfirmacaoEdicaoMultiplaRedes();
		return;
	}
	processarEdicaoRedes();
}

function mostrarConfirmacaoEdicaoMultiplaRedes() {
	var totalComDadosCompletos = contarVenuesComDadosCompletos();
	var totalParciais = 0;
	for (var i = 0; i < document.forms.length; i++) {
		if (venuesComDadosCompletos.indexOf(i) === -1 && dojo.query("input[name=selecao]")[i].disabled != true) {
			totalParciais++;
		}
	}

	var textoParciais = totalParciais === 1
		? "Há <strong>1 local</strong> carregado com dados parciais que não pode ter as redes alteradas sem risco de perda de informações."
		: "Há <strong>" + totalParciais + " locais</strong> carregados com dados parciais que não podem ter as redes alteradas sem risco de perda de informações.";
	var textoCompletos = totalComDadosCompletos === 1
		? "Apenas <strong>1 local</strong> carregado com dados completos será alterado."
		: "Apenas <strong>" + totalComDadosCompletos + " locais</strong> carregados com dados completos serão alterados.";

	dojo.byId("confirmEditAllChainsMessage").innerHTML =
		"<p style='margin: 10px 0;'><strong>Atenção:</strong> " + textoParciais + "</p>" +
		"<p style='margin: 10px 0;'>" + textoCompletos + "</p>" +
		"<p style='margin: 10px 0;'><strong>O que deseja fazer?</strong></p>";

	dijit.byId("dlg_confirmEditAllChains").show();
}

function processarEdicaoRedes() {
	var venueIndex, venueLoop;
	var selecionadas = obterItensSelecionadosPrimariaPrimeiro(
		dojo.byId("chainsContainer"),
		"",
		""
	);
	var ids = selecionadas.ids.join(",");
	var items = selecionadas.items;

	if (dijit.byId("editAllChainsCheckbox") && dijit.byId("editAllChainsCheckbox").checked) {
		venueIndex = 0;
		venueLoop = document.forms.length - 1;
	} else {
		venueIndex = parseInt(dojo.byId("venueChainIndex").innerHTML);
		venueLoop = venueIndex;
	}

	for (var i = venueIndex; i <= venueLoop; i++) {
		var aplicarEmVenueCompleta = !dijit.byId("editAllChainsCheckbox").checked || venuesComDadosCompletos.indexOf(i) !== -1;
		if (document.forms[i] && dojo.query("input[name=selecao]")[i] && dojo.query("input[name=selecao]")[i].disabled != true && aplicarEmVenueCompleta) {
			redes[i] = new Rede(items);
			atualizarCamposRedes(i);

			var chainIconeElem = dojo.byId("chainIcone" + i);
			if (chainIconeElem) renderizarIconeRede(chainIconeElem, i, items[0] || null, true, items.length ? 1 : 0.4);

			// Compara com rede original do CSV
			var indexChainId = csv[0].indexOf("venueChainId");
			var chainIdOriginalCSV = "";
			var redesOriginais = [];

			if (indexChainId !== -1 && csv[parseInt(i) + 1] && csv[parseInt(i) + 1][indexChainId]) {
				chainIdOriginalCSV = csv[parseInt(i) + 1][indexChainId].replace(/"/g, "");
				if (chainIdOriginalCSV) {
					redesOriginais = chainIdOriginalCSV.split(",");
				}
			}

			var redesNovas = ids ? ids.split(",") : [];

			var redesRemover = redesOriginais.filter(function(val) {
				return redesNovas.indexOf(val) == -1;
			});

			var redesAdicionar = [];
			for (var j = 1; j < redesNovas.length; j++) {
				if (redesOriginais.indexOf(redesNovas[j]) == -1) {
					redesAdicionar.push(redesNovas[j]);
				}
			}

			var indexRemoveChain = csv[0].indexOf("removeVenueChainIds");
			if (indexRemoveChain !== -1) {
				if (redesRemover.length > 0) {
					csv[parseInt(i) + 1][indexRemoveChain] = '"' + redesRemover.toString() + '"';
				} else {
					csv[parseInt(i) + 1][indexRemoveChain] = '""';
				}
			}

			var indexAddChain = csv[0].indexOf("addVenueChainIds");
			if (indexAddChain !== -1) {
				if (redesAdicionar.length > 0) {
					csv[parseInt(i) + 1][indexAddChain] = '"' + redesAdicionar.toString() + '"';
				} else {
					csv[parseInt(i) + 1][indexAddChain] = '""';
				}
			}

			var indexPrimaryChain = csv[0].indexOf("primaryVenueChainId");
			if (indexPrimaryChain !== -1) {
				if (redesNovas.length > 0 && (redesOriginais.length === 0 || redesOriginais[0] !== redesNovas[0])) {
					csv[parseInt(i) + 1][indexPrimaryChain] = '"' + redesNovas[0] + '"';
				} else {
					csv[parseInt(i) + 1][indexPrimaryChain] = '""';
				}
			}

			dojo.byId("result" + i).innerHTML = "";
			marcarLinhaEditada(i);
		}
	}
	dijit.byId("dlg_chains").hide();
}

function debounceBuscarRedePeloNome(query) {
	if (timerBuscaRede) clearTimeout(timerBuscaRede);
	sequenciaBuscaRede++;
	var sequencia = sequenciaBuscaRede;
	if (xhrBuscaRede) {
		xhrBuscaRede.abort();
		xhrBuscaRede = null;
	}
	query = (query || "").trim();
	var resultsContainer = dojo.byId("chainsResultsContainer");
	if (query.length < 2) {
		resultsContainer.innerHTML = "";
		resultsContainer.style.display = "none";
		atualizarStatusBuscaRede("", "");
		return;
	}
	timerBuscaRede = setTimeout(function() {
		buscarRedePeloNome(query, sequencia);
	}, 300);
}

function buscarRedePeloNome(query, sequencia) {
	var inputWidget = dijit.byId("inputChainSearch");
	if (typeof query !== "string") query = inputWidget ? inputWidget.get("value") : "";
	query = query.trim();
	if (sequencia === undefined) {
		if (timerBuscaRede) clearTimeout(timerBuscaRede);
		sequencia = ++sequenciaBuscaRede;
		if (xhrBuscaRede) xhrBuscaRede.abort();
	}
	if (sequencia !== sequenciaBuscaRede) return;
	var resultsContainer = dojo.byId("chainsResultsContainer");
	var token = getOauthToken();
	if (!token) return handleMissingOauthToken("pesquisar redes");
	if (!query) return;

	if (query.length === 24 && /^[0-9a-f]{24}$/i.test(query)) {
		mostrarMensagemBuscaRede("Buscando detalhes da rede por ID...", "muted");
		var xhrId = new XMLHttpRequest();
		xhrBuscaRede = xhrId;
		xhrId.open("GET", "https://api.foursquare.com/v2/chains/" + query + "?oauth_token=" + token + "&v=" + DATA_VERSIONAMENTO, true);
		xhrId.onreadystatechange = function() {
			if (xhrId.readyState !== 4 || sequencia !== sequenciaBuscaRede) return;
			xhrBuscaRede = null;
			if (xhrId.status === 200) {
				try {
					var resp = JSON.parse(xhrId.responseText);
					if (resp.response && resp.response.venueChain) {
						var vc = resp.response.venueChain;
						var logo = formatarLogoRede(vc.logo);
						var nome = (vc.bestName && vc.bestName.name) ? vc.bestName.name : vc.id;
						adicionarRedeNaEdicao(vc.id, nome, logo);
						setTimeout(function() {
							if (sequencia === sequenciaBuscaRede) resultsContainer.style.display = "none";
						}, 1500);
						return;
					}
				} catch(e) {}
			}
			adicionarRedeNaEdicao(query, "Rede (" + query.substr(0, 8) + "...) ", "https://app.foursquare.com/img/categories_v2/none_bg_32.png");
			setTimeout(function() {
				if (sequencia === sequenciaBuscaRede) resultsContainer.style.display = "none";
			}, 1500);
		};
		xhrId.send();
		return;
	}

	mostrarMensagemBuscaRede("Buscando redes...", "muted");
	var xhr = new XMLHttpRequest();
	xhrBuscaRede = xhr;
	xhr.open("GET", "https://api.foursquare.com/v2/chains/search?query=" + encodeURIComponent(query) + "&oauth_token=" + token + "&v=" + DATA_VERSIONAMENTO, true);
	xhr.onreadystatechange = function() {
		if (xhr.readyState !== 4 || sequencia !== sequenciaBuscaRede) return;
		xhrBuscaRede = null;
		if (xhr.status === 200) {
			try {
				var resp = JSON.parse(xhr.responseText);
				if (resp.response && resp.response.results && resp.response.results.length > 0) {
					resultsContainer.innerHTML = "";
					resp.response.results.forEach(function(r) {
						var logo = formatarLogoRede(r.logo);
						var nome = (r.bestName && r.bestName.name) ? r.bestName.name : r.id;
						var item = document.createElement("button");
						item.type = "button";
						item.className = "chain-search-result-item visual-result-item";
						var main = document.createElement("div");
						main.className = "chain-search-result-main visual-item-left";
						var image = document.createElement("img");
						image.src = logo;
						image.alt = "";
						image.onerror = function() { this.src = "https://app.foursquare.com/img/categories_v2/none_bg_32.png"; };
						var details = document.createElement("div");
						var name = document.createElement("span");
						name.className = "chain-search-result-name visual-item-title";
						name.textContent = nome;
						var identity = document.createElement("div");
						identity.className = "chain-search-result-id visual-item-subtitle";
						identity.textContent = "ID: " + r.id;
						main.appendChild(image);
						details.appendChild(name);
						details.appendChild(identity);
						main.appendChild(details);
						var resultCount = document.createElement("div");
						resultCount.className = "chain-search-result-count visual-item-right";
						var count = r.count || 0;
						var countries = r.countries || 1;
						var countLabel = document.createElement("div");
						countLabel.textContent = count + (count === 1 ? " local" : " locais");
						var countriesLabel = document.createElement("div");
						countriesLabel.textContent = countries + (countries === 1 ? " país" : " países");
						resultCount.appendChild(countLabel);
						resultCount.appendChild(countriesLabel);
						item.appendChild(main);
						item.appendChild(resultCount);
						resultsContainer.appendChild(item);
						dojo.connect(item, "onclick", function() {
							adicionarRedeNaEdicao(r.id, nome, logo);
						});
					});
					resultsContainer.style.display = "block";
				} else {
					mostrarMensagemBuscaRede("Nenhuma rede encontrada para \"" + query + "\".", "muted");
				}
			} catch(e) {
				mostrarMensagemBuscaRede("Erro ao processar resposta.", "error");
			}
		} else {
			mostrarMensagemBuscaRede("Erro na consulta da API (" + xhr.status + ").", "error");
		}
	};
	xhr.send();
}

function mostrarMensagemBuscaRede(message, status) {
	var resultsContainer = dojo.byId("chainsResultsContainer");
	resultsContainer.innerHTML = "";
	var messageNode = document.createElement("div");
	messageNode.className = "chain-search-message is-" + status;
	messageNode.textContent = message;
	resultsContainer.appendChild(messageNode);
	resultsContainer.style.display = "block";
}

function adicionarRedeNaEdicao(id, nome, logo) {
	var currentItems = obterItensSelecionadosPrimariaPrimeiro(dojo.byId("chainsContainer")).items;

	if (currentItems.some(function(item) { return item.id === id; })) {
		atualizarStatusBuscaRede("Esta rede já está selecionada.", "error");
		return "duplicate";
	}
	if (currentItems.length >= 3) {
		atualizarStatusBuscaRede("O limite de 3 redes foi atingido.", "error");
		return "limit";
	}
	currentItems.push({ id: id, name: nome, logo: logo || "https://app.foursquare.com/img/categories_v2/none_bg_32.png" });
	atualizarRedes(currentItems);
	atualizarStatusBuscaRede("Rede adicionada.", "success");

	var resContainer = dojo.byId("chainsResultsContainer");
	if (resContainer) resContainer.style.display = "none";
	return "added";
}

function escapeTooltipHtml(value) {
	var replacements = {
		"&": "&amp;",
		"<": "&lt;",
		">": "&gt;",
		'"': "&quot;",
		"'": "&#39;"
	};
	return String(value).replace(/[&<>"']/g, function(character) {
		return replacements[character];
	});
}

function createTooltip(target_id, content) {
	destroyTooltip(target_id);
	var tooltip = new dijit.Tooltip({
		connectId: [target_id],
		label: content
	});
	tooltip.domNode.id = 'tt_' + target_id;
	document.body.appendChild(tooltip.domNode);
}

function destroyTooltip(target_id) {
	var obj = dojo.byId('tt_' + target_id);
	if (!obj) return;
	var tooltip = dijit.registry.byNode(obj);
	if (tooltip) {
		tooltip.destroyRecursive();
	} else if (obj.parentNode) {
		obj.parentNode.removeChild(obj);
	}
}

function formattedTime(unix_timestamp) {
	var date = new Date(unix_timestamp * 1000);
	var dia = addZero(date.getDate());
	return dia + "/" + MESES[date.getMonth()] + "/" + date.getFullYear();
}

function atualizarDicaVenue(i) {
	var dica = "<span style=\"font-size: 12px\"><b>" + document.forms[i]["name"].value + "</b>";
	if (document.forms[i]["verified"].value == "true")
		dica += "<img src=\"img/verified.png\" width=\"10\" height=\"10\" style=\"margin-left: 7px\">";
	if ((document.forms[i]["isClosed"].value == "true") || (document.forms[i]["isPrivate"].value == "true") || (document.forms[i]["isDeleted"].value == "true")) {
		dica += "<br><span style=\"color: #df7a77;\"><b>";
		var flags = "";
		if (document.forms[i]["isClosed"].value == "true")
			flags += "(Fechado) ";
		if (document.forms[i]["isPrivate"].value == "true")
			flags += "(Privado) ";
		if (document.forms[i]["isDeleted"].value == "true")
			flags += "(Exclu&iacute;do) ";
		dica += flags.trim() + "</b></span>";
	}
	try {
		if (document.forms[i]["address"].value != "") {
			dica += "<br>" + document.forms[i]["address"].value;
			if (document.forms[i]["crossStreet"].value != "")
				dica += " (" + document.forms[i]["crossStreet"].value + ")";
		} else if (document.forms[i]["crossStreet"].value != "") {
			dica += "<br>" + document.forms[i]["crossStreet"].value;
		}
	} catch(e) {
	}
	try {
		if (document.forms[i]["city"].value != "") {
			dica += "<br>" + document.forms[i]["city"].value;
			if (document.forms[i]["state"].value != "") {
				dica += ", " + document.forms[i]["state"].value;
				if (document.forms[i]["zip"].value != "")
					dica += " " + document.forms[i]["zip"].value;
			}
		} else if (document.forms[i]["state"].value != "") {
			dica += "<br>" + document.forms[i]["state"].value;
			if (document.forms[i]["zip"].value != "")
				dica += " " + document.forms[i]["zip"].value;
		} else if (document.forms[i]["zip"].value != "") {
			dica += "<br>" + document.forms[i]["zip"].value;
		}
	} catch(err) { }
	dica += "<br><span style=\"color: #999999;\"><img src=\"img/maps.png\" width=\"8\" height=\"10\" style=\"opacity: 0.4\"> " + document.forms[i]["checkinsCount"].value + "<img src=\"img/person.png\" width=\"10\" height=\"10\" style=\"opacity: 0.4; margin-left: 7px\"> " + document.forms[i]["usersCount"].value + "<img src=\"img/comment.png\" width=\"10\" height=\"10\" style=\"opacity: 0.4; margin-left: 7px; margin-right: 1px\"> " + document.forms[i]["tipCount"].value;
	if (modo == DADOS_COMPLETOS) {
		dica += "<img src=\"img/photo.png\" width=\"10\" height=\"10\" style=\"opacity: 0.4; margin-left: 7px\"> ";
		(document.forms[i]["photosCount"].value != "") ? dica += document.forms[i]["photosCount"].value : dica += "0";
		dica += "<img src=\"img/heart.png\" width=\"10\" height=\"10\" style=\"opacity: 0.4; margin-left: 7px\"> ";
		(document.forms[i]["likesCount"].value != "") ? dica += document.forms[i]["likesCount"].value : dica += "0";
		dica += "<img src=\"img/list.png\" width=\"10\" height=\"10\" style=\"opacity: 0.4; margin-left: 7px\"> ";
		(document.forms[i]["listedCount"].value != "") ? dica += document.forms[i]["listedCount"].value : dica += "0";
		if (document.forms[i]["createdAt"].value != "")
			dica += "<br>Criada em " + document.forms[i]["createdAt"].value;
	}
	dica += "</span></span>";
	return dica;
}

function montarPrefixoLinhaCsv(venueId, categoryId, chainId, header, incluirCategoria) {
	var campos = ['"' + venueId + '"'];
	if (incluirCategoria) campos.push('"' + (categoryId || "") + '"');
	if (header.indexOf("venueChainId") !== -1) campos.push('"' + (chainId || "") + '"');
	return campos.join("&&");
}

function deveIncluirColunasRedesCsv() {
	return isRedesSelecionada();
}

function removerColunasRedesDoCsv() {
	if (!csv.length || !csv[0]) return;
	var nomesColunasRedes = ["venueChainId", "primaryVenueChainId", "addVenueChainIds", "removeVenueChainIds"];
	for (var coluna = 0; coluna < nomesColunasRedes.length; coluna++) {
		var indice = csv[0].indexOf(nomesColunasRedes[coluna]);
		while (indice !== -1) {
			csv[0].splice(indice, 1);
			for (var linha = 1; linha < csv.length; linha++) {
				if (csv[linha] && csv[linha].length > indice) csv[linha].splice(indice, 1);
			}
			indice = csv[0].indexOf(nomesColunasRedes[coluna]);
		}
	}
}

function montarSufixoLinhaCsv(i, completa, incluirRedes) {
	if (!completa) {
		return [
			document.forms[i]["checkinsCount"].value,
			document.forms[i]["usersCount"].value,
			document.forms[i]["tipCount"].value
		];
	}

	var colunas = ["", "", ""];
	if (incluirRedes) colunas = colunas.concat("", "", "");
	return colunas.concat(
		document.forms[i]["createdAt"].value,
		document.forms[i]["checkinsCount"].value,
		document.forms[i]["usersCount"].value,
		document.forms[i]["tipCount"].value,
		document.forms[i]["likesCount"].value,
		document.forms[i]["listedCount"].value,
		document.forms[i]["photosCount"].value,
		document.forms[i]["isClosed"].value,
		document.forms[i]["isPrivate"].value,
		document.forms[i]["isDeleted"].value
	);
}

function anexarSufixoCsv(i, completa) {
	csv[i + 1] = csv[i + 1].concat(
		montarSufixoLinhaCsv(i, completa, deveIncluirColunasRedesCsv())
	);
}

function atualizarTabela(venue, i) {
	totalCarregadas++;
	var linha = "";
	categorias[i] = new Categoria();
	var sufixo;
	for (j = 0; j < venue.categories.length; j++) {
		categorias[i].ids += venue.categories[j].id + ",";
		categorias[i].nomes += venue.categories[j].name + ",";
		categorias[i].icones += venue.categories[j].icon.prefix + "bg_32" + venue.categories[j].icon.suffix + ",";
	}
	if (categorias[i].ids != undefined) {
		categorias[i].ids = categorias[i].ids.slice(0, -1).replace(/undefined/gi, "");
		dojo.byId("cid" + i).value = categorias[i].ids;
		categorias[i].nomes = categorias[i].nomes.slice(0, -1).replace(/undefined/gi, "");
		dojo.byId("cna" + i).value = categorias[i].nomes;
		categorias[i].icones = categorias[i].icones.slice(0, -1).replace(/undefined/gi, "");
		dojo.byId("cic" + i).value = categorias[i].icones;
		//console.log(dojo.byId("cna" + i).value + " (" + dojo.byId("cid" + i).value + ") [" + dojo.byId("cic" + i).value + "]");
		//console.log(categorias[i].nomes + " (" + categorias[i].ids + ") [" + categorias[i].icones + "]");
	}

	processarRedesVenue(venue, i);

	document.forms[i]["name"].value = venue.name;
	// Update Dijit widget to hide placeholder
	if (venue.name !== undefined) {
		var nameWidget = dijit.byId(dojo.query("input[name=name]")[i].id);
		if (nameWidget) nameWidget.set("value", venue.name || "");
	}
	document.forms[i]["venuell"].value = (venue.location.lat + ', ' + venue.location.lng).replace(/undefined/gi, "0.0");
	// Update venuell widget
	var venuellValue = (venue.location.lat + ', ' + venue.location.lng).replace(/undefined/gi, "0.0");
	var venuellWidget = dijit.byId(dojo.query("input[name=venuell]")[i].id);
	if (venuellWidget) venuellWidget.set("value", venuellValue);

	// Verifica se esta linha foi marcada para recarregamento (precisa estar aqui para usar depois)
	const isReloading = document.forms[i].getAttribute('data-reloading') === 'true';
	const permitirEdicao = (modo == DADOS_COMPLETOS) || isReloading;

	var chainIdOriginal = (redes[i] && redes[i].ids) ? redes[i].ids : "";
	linha = montarPrefixoLinhaCsv(venue.id, categorias[i].ids, chainIdOriginal, csv[0], permitirEdicao);
	var elementName;
	var length = document.forms[i].elements.length;
	for (j = columnsStartIndex; j < length; j++) {
		if (document.forms[i].elements[j].type == "text") {
			elementName = document.forms[i].elements[j].name;
			switch (elementName) {
			case "name":
				//document.forms[i]["name"].value = venue.name;
				linha += '&&"' + venue.name + '"';
				break;
			case "address":
				document.forms[i]["address"].value = venue.location.address;
				if (venue.location.address !== undefined) {
					var addressWidget = dijit.byId(dojo.query("input[name=address]")[i].id);
					if (addressWidget) addressWidget.set("value", venue.location.address || "");
				}
				linha += '&&"' + venue.location.address + '"';
				break;
			case "crossStreet":
				document.forms[i]["crossStreet"].value = venue.location.crossStreet;
				if (venue.location.crossStreet !== undefined) {
					var crossStreetWidget = dijit.byId(dojo.query("input[name=crossStreet]")[i].id);
					if (crossStreetWidget) crossStreetWidget.set("value", venue.location.crossStreet || "");
				}
				linha += '&&"' + venue.location.crossStreet + '"';
				break;
			case "neighborhood":
				document.forms[i]["neighborhood"].value = venue.location.neighborhood;
				if (venue.location.neighborhood !== undefined) {
					var neighborhoodWidget = dijit.byId(dojo.query("input[name=neighborhood]")[i].id);
					if (neighborhoodWidget) neighborhoodWidget.set("value", venue.location.neighborhood || "");
				}
				linha += '&&"' + venue.location.neighborhood + '"';
				break;
			case "city":
				document.forms[i]["city"].value = venue.location.city;
				if (venue.location.city !== undefined) {
					var cityWidget = dijit.byId(dojo.query("input[name=city]")[i].id);
					if (cityWidget) cityWidget.set("value", venue.location.city || "");
				}
				linha += '&&"' + venue.location.city + '"';
				break;
			case "state":
				document.forms[i]["state"].value = venue.location.state;
				if (venue.location.state !== undefined) {
					var stateWidget = dijit.byId(dojo.query("input[name=state]")[i].id);
					if (stateWidget) stateWidget.set("value", venue.location.state || "");
				}
				linha += '&&"' + venue.location.state + '"';
				break;
			case "zip":
				document.forms[i]["zip"].value = venue.location.postalCode;
				if (venue.location.postalCode !== undefined) {
					var zipWidget = dijit.byId(dojo.query("input[name=zip]")[i].id);
					if (zipWidget) zipWidget.set("value", venue.location.postalCode || "");
				}
				linha += '&&"' + venue.location.postalCode + '"';
				break;
			case "parentId":
				var parentId;
				try {
					if (venue.parent)
						(venue.parent.id) ? parentId = venue.parent.id : parentId = "";
				} catch(e) {
					debugLog(e);
				}
				document.forms[i]["parentId"].value = parentId;
				if (parentId !== undefined) {
					var parentIdWidget = dijit.byId(dojo.query("input[name=parentId]")[i].id);
					if (parentIdWidget) parentIdWidget.set("value", parentId || "");
				}
				linha += '&&"' + parentId + '"';
				break;
			case "phone":
				document.forms[i]["phone"].value = venue.contact.phone;
				if (venue.contact.phone !== undefined) {
					var phoneWidget = dijit.byId(dojo.query("input[name=phone]")[i].id);
					if (phoneWidget) phoneWidget.set("value", venue.contact.phone || "");
				}
				linha += '&&"' + venue.contact.phone + '"';
				break;
			case "url":
				document.forms[i]["url"].value = venue.url;
				if (venue.url !== undefined) {
					var urlWidget = dijit.byId(dojo.query("input[name=url]")[i].id);
					if (urlWidget) urlWidget.set("value", venue.url || "");
				}
				linha += '&&"' + venue.url + '"';
				break;
			case "twitter":
				document.forms[i]["twitter"].value = venue.contact.twitter;
				if (venue.contact.twitter !== undefined) {
					var twitterWidget = dijit.byId(dojo.query("input[name=twitter]")[i].id);
					if (twitterWidget) twitterWidget.set("value", venue.contact.twitter || "");
				}
				linha += '&&"' + venue.contact.twitter + '"';
				break;
			case "facebook":
				document.forms[i]["facebook"].value = venue.contact.facebook;
				if (venue.contact.facebook !== undefined) {
					var facebookWidget = dijit.byId(dojo.query("input[name=facebook]")[i].id);
					if (facebookWidget) facebookWidget.set("value", venue.contact.facebook || "");
				}
				linha += '&&"' + venue.contact.facebook + '"';
				break;
			case "instagram":
				document.forms[i]["instagram"].value = venue.contact.instagram;
				if (venue.contact.instagram !== undefined) {
					var instagramWidget = dijit.byId(dojo.query("input[name=instagram]")[i].id);
					if (instagramWidget) instagramWidget.set("value", venue.contact.instagram || "");
				}
				linha += '&&"' + venue.contact.instagram + '"';
				break;
			case "venuell":
				//document.forms[i]["venuell"].value = (venue.location.lat + ', ' + venue.location.lng).replace(/undefined/gi, "0.0");
				linha += '&&"' + venue.location.lat + ', ' + venue.location.lng + '"';
				break;
			case "description":
				document.forms[i]["description"].value = venue.description;
				if (venue.description !== undefined) {
					var descriptionWidget = dijit.byId(dojo.query("input[name=description]")[i].id);
					if (descriptionWidget) descriptionWidget.set("value", venue.description || "");
				}
				linha += '&&"' + venue.description + '"';
				//if (venue.verified == true)
					//dijit.byId(dojo.query("input[name=description]")[i].id).attr("readOnly", true);
				break;
			case "menu":
				var menuUrl;
				try {
					if (venue.menu)
						(venue.menu.url) ? menuUrl = venue.menu.url : menuUrl = "";
				} catch(e) {
					debugLog(e);
				}
				document.forms[i]["menu"].value = menuUrl;
				if (menuUrl !== undefined) {
					var menuWidget = dijit.byId(dojo.query("input[name=menu]")[i].id);
					if (menuWidget) menuWidget.set("value", menuUrl || "");
				}
				linha += '&&"' + menuUrl + '"';
				break;
			//case "hours":
			//	var hours;
			//	try {
			//		(venue.hours) ? hours = "horas existem" : hours = "";
			//		//(venue.hours) ? hours = venue.hours.timeframes.open.renderedTime : hours = "";
			//	} catch(e) {
			//		console.log(e);
			//	}
			//	document.forms[i]["hours"].value = hours;
			//	linha += '&&"' + hours + '"';
			//	break;
			default:
				break;
			}
			if (document.forms[i].elements[j].value == "undefined")
				dijit.byId(dojo.query("input[name=" + elementName + "]")[i].id).set("value", "");
			//console.log(j, elementName, "adicionado");
		} //else
			//console.log(j, document.forms[i].elements[j].name, "ignorado");
	}
	dojo.byId("result" + i).innerHTML = "";

	// Primeiro habilita todos os campos (remove restrições anteriores)
	habilitarCamposLinha(i);

	// Depois verifica se deve desabilitar baseado nas categorias atuais
	if (venue.categories[0] != undefined) {
		if (venue.categories[0].id == CATEGORIA_HOME)
			desabilitarLinha(i);
		else if ((CATEGORIAS_STATES_MUNICIPALITIES.indexOf(venue.categories[0].id) > -1) ||
			((venue.categories[1] != undefined) && (CATEGORIAS_STATES_MUNICIPALITIES.indexOf(venue.categories[1].id) > -1)) ||
			((venue.categories[2] != undefined) && (CATEGORIAS_STATES_MUNICIPALITIES.indexOf(venue.categories[2].id) > -1))) {
			//console.info("Venue " + i + " dentro de Estado ou Município");
			if (document.forms[i]["address"])
				dijit.byId(dojo.query("input[name=address]")[i].id).setAttribute("disabled", true);
			if (document.forms[i]["crossStreet"])
				dijit.byId(dojo.query("input[name=crossStreet]")[i].id).setAttribute("disabled", true);
			if (document.forms[i]["neighborhood"])
				dijit.byId(dojo.query("input[name=neighborhood]")[i].id).setAttribute("disabled", true);
			if ((document.forms[i]["city"]) && (venue.categories[0].id == CATEGORIA_CITY))
				dijit.byId(dojo.query("input[name=city]")[i].id).setAttribute("disabled", true);
			if (document.forms[i]["phone"])
				dijit.byId(dojo.query("input[name=phone]")[i].id).attr("readOnly", true);
		}
	}
	csv[i + 1] = linha.replace(/undefined/gi, "").split("&&");
	venuellOriginais[i] = document.forms[i]["venuell"].value;

	// Marca esta venue como tendo dados completos se aplicável
	if (permitirEdicao && venuesComDadosCompletos.indexOf(i) === -1) {
		venuesComDadosCompletos.push(i);
	}

	if (venue.categories[0] == undefined) {
		(permitirEdicao) ? dojo.byId("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:editarCategorias(" + i + ")'><img id=catImg" + i + " src='https://foursquare.com/img/categories_v2/none_bg_32.png' style='height: 22px; width: 22px; margin-left: 0px'></a>" : dojo.byId("icone" + i).innerHTML = "<img id=catImg" + i + " src='https://foursquare.com/img/categories_v2/none_bg_32.png' style='height: 22px; width: 22px; margin-left: 0px'>";
	} else if ((venue.categories[0].id == CATEGORIA_HOME) || (modo == DADOS_PARCIAIS)) {
		dojo.byId("icone" + i).innerHTML = "<img id=catLnk" + i + " src='" + categorias[i].icones.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'>";
		createTooltip("catLnk" + i, "<span style=\"font-size: 12px\">" + categorias[i].nomes.replace(/,/gi, ", ") + "</span>");
	} else if (venue.id != document.forms[i]["venue"].value) {
		desabilitarLinha(i);
	} else {
		if (permitirEdicao) {
			dojo.byId("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:editarCategorias(" + i + ")'><img id=catImg" + i + " src='" + categorias[i].icones.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'></a>";
			createTooltip("catLnk" + i, "<span style=\"font-size: 12px\">" + categorias[i].nomes.replace(/,/gi, ", ") + "</span>");
		} else {
			dojo.byId("icone" + i).innerHTML = "<img id=catLnk" + i + " src='" + categorias[i].icones.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'>";
			createTooltip("catLnk" + i, "<span style=\"font-size: 12px\">" + categorias[i].nomes.replace(/,/gi, ", ") + "</span>");
		}
	}
	atualizarIconeRede(i, permitirEdicao);
	document.forms[i]["verified"].value = venue.verified;
	document.forms[i]["checkinsCount"].value = venue.stats.checkinsCount;
	document.forms[i]["usersCount"].value = venue.stats.usersCount;
	document.forms[i]["tipCount"].value = venue.stats.tipCount;
	if (venue.likes != undefined)
		document.forms[i]["likesCount"].value = venue.likes.count;
	if ((venue.listed != undefined) && (venue.listed.count != undefined))
		document.forms[i]["listedCount"].value = venue.listed.count;
	if (venue.photos != undefined)
	  document.forms[i]["photosCount"].value = venue.photos.count;
	(venue.closed == undefined) ? document.forms[i]["isClosed"].value = false : document.forms[i]["isClosed"].value = true;
	(venue.private == undefined) ? document.forms[i]["isPrivate"].value = false : document.forms[i]["isPrivate"].value = true;
	(venue.deleted == undefined) ? document.forms[i]["isDeleted"].value = false : document.forms[i]["isDeleted"].value = true;
	if (venue.createdAt != undefined)
		document.forms[i]["createdAt"].value = formattedTime(venue.createdAt);
	var dicaVenue = atualizarDicaVenue(i);
	createTooltip("venLnk" + i, dicaVenue);

	// Sempre adiciona colunas ao CSV (determinaremos formato no final)
	// Temporariamente adiciona formato completo se aplicável, senão parcial
	var estaVenueTemDadosCompletos = (modo == DADOS_COMPLETOS) || (venuesComDadosCompletos.indexOf(i) !== -1);
	anexarSufixoCsv(i, estaVenueTemDadosCompletos);

	// Normaliza CSV apenas uma vez, quando todas venues foram carregadas (Bug #1 e #2)
	if (totalCarregadas == document.forms.length - totalNaoCarregadas) {
		normalizarCsv();

		if (dojo.query("input[name=selecao]:enabled").length == 0)
			dijit.byId("menuSelecionar").setAttribute('disabled', true);
	}

	window.locais[i] = [(i + 1) + ". " + venue.name, venue.location.lat, venue.location.lng];
	debugInfo("Venue " + i + " recuperada!");

	// Atualiza marcadores no mapa quando venue é carregada
	if (window.googleMaps && window.googleMaps.map) {
		window.googleMaps.updateMarkers(window.locais);
		debugInfo("Marcadores atualizados!");
	}

	// Só limpa linhas editadas se for carregamento inicial (não reload)
	// Durante reload, a limpeza é feita seletivamente em executarRecarregamento
	if (!isReloading && totalCarregadas == (document.forms.length - totalNaoCarregadas)) {
		limparLinhasEditadas();
		debugInfo("Marcadores posicionados!");

		// Ativa navegação vertical após todos os campos estarem prontos
		setupVerticalNavigation();
	}
}

function montarArvore(resposta) {
	function ordenarCategoriasRecursivamente(categoriasNoNivel) {
		if (!categoriasNoNivel || !categoriasNoNivel.length) return;
		categoriasNoNivel.sort(function(el1, el2) {
			return compare(el1, el2, "name");
		});
		categoriasNoNivel.forEach(function(categoria) {
			ordenarCategoriasRecursivamente(categoria.categories);
		});
	}
	function transformarCategoria(categoria) {
		var novaCategoria = {
			id: categoria.id,
			name: categoria.name,
			icon: categoria.icon.prefix + "bg_32" + categoria.icon.suffix
		};
		if (categoria.categories && categoria.categories.length) {
			novaCategoria.children = dojo.map(categoria.categories, transformarCategoria);
		}
		return novaCategoria;
	}
	resposta.response.categories.forEach(function(categoriaRaiz) {
		ordenarCategoriasRecursivamente(categoriaRaiz.categories);
	});
	var restructuredData = dojo.map(resposta.response.categories, transformarCategoria);
	JSONText = JSON.stringify(restructuredData);
	//console.log(JSONText);
	atualizarBuscaCategorias(restructuredData);

	// Verifica se widget já existe - se sim, REUTILIZA (não destrói!)
	var existingTree = dijit.byId("treeContainer");
	if (existingTree) {
		// Árvore já existe e está funcional - reutiliza sem destruir
		debugInfo("✅ Árvore de categorias já existe - reutilizando sem destruir.");
		return; // Não precisa recriar
	}

	// Garante que o container DOM existe
	var containerElement = dojo.byId("treeContainer");
	if (!containerElement) {
		// Se o container foi removido, recria ele APÓS o catsContainer (posição correta)
		var catsContainer = dojo.byId("catsContainer");
		if (catsContainer) {
			// Usa dojo.place com "after" para posicionar logo após o catsContainer
			containerElement = dojo.place('<div id="treeContainer"></div>', catsContainer, "after");
			debugInfo("Container DOM #treeContainer recriado após #catsContainer.");
		} else {
			debugInfo("⚠️ Container #catsContainer não encontrado, não é possível recriar árvore.");
			return;
		}
	} else {
		// Limpa o conteúdo HTML residual
		containerElement.innerHTML = "";
	}

	// Cria árvore (primeira vez ou recriação)
	store = new dojo.data.ItemFileReadStore({
		data: {
			"identifier": "id",
			"label": "name",
			"items": restructuredData
		}
	});
	var treeModel = new dijit.tree.ForestStoreModel({
		store: store,
		rootId: "root",
		rootLabel: "Categorias",
		childrenAttrs: ["children"]
	});

	// Cria a árvore usando o container existente (que já tem o ID e CSS corretos)
	var treeWidget = new dijit.Tree({
		model: treeModel,
		showRoot: false,
		onClick: treeOnClick,
		getIconClass: function(/*dojo.data.Item*/ item, /*Boolean*/ opened) {
			var style = document.createElement('style');
			style.type = 'text/css';
			style.innerHTML = '.icon' + item.id + ' { background-image: url(\'' + item.icon + '\'); background-size: 16px 16px; width: 16px; height: 16px; }';
			document.getElementsByTagName('head')[0].appendChild(style);
			return 'icon' + item.id;
		}
	}, "treeContainer");
	treeWidget.startup();
	debugInfo("✅ Árvore de categorias criada/recriada com sucesso.");
}

function treeOnClick(item) {
	if (item.root) return false;
	var result = adicionarCategoriaSelecionada(
		store.getValue(item, "id"),
		store.getLabel(item),
		store.getValue(item, "icon")
	);
	if (result === "limit") {
		atualizarStatusBuscaCategoria("O limite de 3 categorias foi atingido.", "error");
		return false;
	}
	if (result === "duplicate") return false;
	return result === "added";
}

var estadoEdicaoParaRestaurar = null;
var chaveEstadoEdicao = "fmet:editor-state";

function obterIdsDasVenues() {
	var ids = [];
	for (var i = 0; i < document.forms.length; i++) {
		ids.push(document.forms[i].elements.namedItem("venue").value);
	}
	return ids;
}

function obterEstadoEdicaoDaNavegacao() {
	var entradas = window.performance && window.performance.getEntriesByType
		? window.performance.getEntriesByType("navigation")
		: [];
	var navigationType = entradas.length > 0 ? entradas[0].type : "";
	var deveRestaurar = navigationType === "back_forward" || navigationType === "reload";
	if (!deveRestaurar && window.performance && window.performance.navigation) {
		deveRestaurar = window.performance.navigation.type === 1 || window.performance.navigation.type === 2;
	}

	try {
		var salvo = sessionStorage.getItem(chaveEstadoEdicao);
		if (!deveRestaurar || !salvo) {
			if (!deveRestaurar) sessionStorage.removeItem(chaveEstadoEdicao);
			return null;
		}
		var estado = JSON.parse(salvo);
		if (estado.version !== 1 || JSON.stringify(estado.venueIds) !== JSON.stringify(obterIdsDasVenues()) ||
			estado.editChains !== !!(window.appBootstrap && window.appBootstrap.editChains)) {
			sessionStorage.removeItem(chaveEstadoEdicao);
			return null;
		}
		return estado;
	} catch (error) {
		return null;
	}
}

function salvarEstadoEdicao() {
	if (!document.forms.length) return;
	for (var i = 0; i < document.forms.length; i++) {
		var resultadoLinha = dojo.byId("result" + i);
		if (resultadoLinha && resultadoLinha.querySelector("img[src*='loading.gif']")) return;
	}
	var estado = {
		version: 1,
		venueIds: obterIdsDasVenues(),
		editChains: !!(window.appBootstrap && window.appBootstrap.editChains),
		modo: modo,
		csv: csv,
		categorias: categorias,
		redes: isRedesSelecionada() ? redes : [],
		venuesComDadosCompletos: venuesComDadosCompletos,
		linhasEditadas: linhasEditadas,
		venuellOriginais: venuellOriginais,
		locais: window.locais,
		relatorio: relatorio,
		failedRequests: failedRequests,
		rows: []
	};
	for (var i = 0; i < document.forms.length; i++) {
		var form = document.forms[i];
		var fields = [];
		for (var j = 0; j < form.elements.length; j++) {
			var field = form.elements[j];
			fields.push({
				value: field.value,
				checked: field.checked,
				disabled: field.disabled,
				readOnly: field.readOnly
			});
		}
		estado.rows.push({
			fields: fields,
			className: dojo.byId("linha" + i).className,
			categoryHtml: dojo.byId("icone" + i).innerHTML,
			chainHtml: isRedesSelecionada() && dojo.byId("chainIcone" + i) ? dojo.byId("chainIcone" + i).innerHTML : null,
			resultHtml: dojo.byId("result" + i).innerHTML
		});
	}
	try {
		sessionStorage.setItem(chaveEstadoEdicao, JSON.stringify(estado));
	} catch (error) {
		debugLog("⚠️ Não foi possível salvar o estado do editor para o retorno do navegador.", error);
	}
}

function restaurarEstadoEdicao(estado) {
	var redesHabilitadas = isRedesSelecionada();
	modo = estado.modo;
	csv = estado.csv || [];
	if (!redesHabilitadas) removerColunasRedesDoCsv();
	categorias = estado.categorias || [];
	redes = redesHabilitadas ? (estado.redes || []).map(function(rede) { return new Rede(obterItensRede(rede)); }) : [];
	venuesComDadosCompletos = estado.venuesComDadosCompletos || [];
	linhasEditadas = estado.linhasEditadas || [];
	venuellOriginais = estado.venuellOriginais || [];
	window.locais = estado.locais || [];
	relatorio = estado.relatorio || [];
	failedRequests = estado.failedRequests || {};
	totalCarregadas = document.forms.length;
	totalNaoCarregadas = 0;

	for (var i = 0; i < estado.rows.length; i++) {
		var form = document.forms[i];
		var linha = dojo.byId("linha" + i);
		var row = estado.rows[i];
		for (var j = 0; j < row.fields.length && j < form.elements.length; j++) {
			var field = form.elements[j];
			var savedField = row.fields[j];
			var widget = field.id ? dijit.byId(field.id) : null;
			if (field.type === "checkbox" && widget) {
				widget.set("checked", savedField.checked, false);
			} else if (widget && typeof widget.set === "function") {
				widget.set("value", savedField.value, false);
			} else {
				field.value = savedField.value;
			}
			field.disabled = savedField.disabled;
			field.readOnly = savedField.readOnly;
		}
		linha.className = row.className;
		dojo.byId("icone" + i).innerHTML = row.categoryHtml;
		var chainIcone = dojo.byId("chainIcone" + i);
		if (chainIcone && !redesHabilitadas) {
			chainIcone.style.display = "none";
			chainIcone.innerHTML = "";
		} else if (chainIcone) {
			var chainItems = obterItensRede(redes[i]);
			renderizarIconeRede(chainIcone, i, chainItems[0] || null, true, chainItems.length ? 1 : 0.4);
		}
		dojo.byId("result" + i).innerHTML = row.resultHtml;

		var categoryTarget = dojo.byId("catLnk" + i) ? "catLnk" + i : (dojo.byId("catImg" + i) ? "catImg" + i : null);
		if (categoryTarget) {
			createTooltip(categoryTarget, "<span style=\"font-size: 12px\">" + (dojo.byId("cna" + i).value || "").replace(/,/gi, ", ") + "</span>");
		}
		if (dojo.byId("venLnk" + i)) createTooltip("venLnk" + i, atualizarDicaVenue(i));
		if (redesHabilitadas && redes[i]) {
			atualizarCamposRedes(i);
			if (dojo.byId("chainIcone" + i)) atualizarTooltipRedes(i);
		}
	}

	linhasSelecionadas = dojo.query("input[name=selecao]:checked");
	totalSelecionadas = linhasSelecionadas.length;
	if (dijit.byId("saveButton")) dijit.byId("saveButton").set("disabled", linhasEditadas.length === 0);
	if (dijit.byId("reloadButton")) dijit.byId("reloadButton").setAttribute("disabled", false);
	if (dijit.byId("menuSinalizar")) atualizarItensMenuMais();
	if (dijit.byId("menuItemExportarRelatorio")) {
		dijit.byId("menuItemExportarRelatorio").setAttribute("disabled", relatorio.length === 0);
	}
	if (modo === DADOS_COMPLETOS) {
		var categoriasSalvas = localStorage.getItem("categorias");
		if (categoriasSalvas) {
			try {
				montarArvore(JSON.parse(categoriasSalvas));
			} catch (error) {
				carregarListaCategorias();
			}
		} else {
			carregarListaCategorias();
		}
	}
	atualizarMarcadoresLocais();
	setupVerticalNavigation();
	debugInfo("Estado anterior do editor restaurado.");
}

function atualizarMarcadoresLocais() {
	if (window.googleMaps && window.googleMaps.map && window.locais && window.locais.length > 0) {
		window.googleMaps.updateMarkers(window.locais);
	}
}

window.addEventListener("pagehide", salvarEstadoEdicao);
window.addEventListener("pageshow", function(event) {
	if (!event.persisted) return;
	try {
		var estado = JSON.parse(sessionStorage.getItem(chaveEstadoEdicao) || "null");
		if (!estado || JSON.stringify(estado.venueIds) !== JSON.stringify(obterIdsDasVenues())) return;
		for (var i = 0; i < estado.rows.length; i++) {
			var checkbox = document.forms[i].elements.namedItem("selecao");
			var savedCheckbox = estado.rows[i].fields[0];
			var widget = checkbox && dijit.byId(checkbox.id);
			if (widget && savedCheckbox) widget.set("checked", savedCheckbox.checked, false);
		}
		linhasSelecionadas = dojo.query("input[name=selecao]:checked");
		if (dijit.byId("menuSinalizar")) atualizarItensMenuMais();
	} catch (error) {}
});

function carregarDadosVenues() {
	var venue;
	var linhas = document.forms.length;
	var token = getOauthToken();

	if (!token)
		return handleMissingOauthToken("carregar os locais");
	if (estadoEdicaoParaRestaurar) {
		var estado = estadoEdicaoParaRestaurar;
		estadoEdicaoParaRestaurar = null;
		restaurarEstadoEdicao(estado);
		try {
			sessionStorage.removeItem(chaveEstadoEdicao);
		} catch (error) {}
		return;
	}

	//if (localStorage && localStorage.getItem('venues'))
		//json = JSON.parse(localStorage.getItem('venues'));
	if (json == "") {
		debugInfo("Recuperando dados completos das venues...");
		modo = DADOS_COMPLETOS;
		for (i = 0; i < linhas; i++) {
			venue = document.forms[i]["venue"].value;
			xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/" + venue + "?oauth_token=" + token + "&v=" + DATA_VERSIONAMENTO, "load", null, i);
			dojo.byId("result" + i).innerHTML = "<img src='img/loading.gif' alt='Recuperando dados...'>";
		}
		var d = new Date();
		d.setHours(0, 0, 0, 0);
		if ((!localStorage.getItem('categorias')) || (d > dojo.cookie("data_categorias")))
			carregarListaCategorias();
		else {
			var resposta = JSON.parse(localStorage.getItem('categorias'));
			montarArvore(resposta);
			debugInfo("Categorias recuperadas do localStorage!");
		}
	} else {
		debugInfo("Recuperando dados parciais das venues...");
		modo = DADOS_PARCIAIS;
		for (i = 0; i < linhas; i++) {
			atualizarTabela(json.response.venues[i], i);
			//console.info("Venue " + i + " recuperada via JSON!");
		}
		debugInfo("Dados parciais das venues recuperados via JSON!");
	}
}

/**
 * Normaliza estrutura do CSV para garantir consistência entre venues com dados parciais e completos
 * - Insere coluna categoryId se necessário
 * - Normaliza todas as linhas para formato completo (24 colunas) ou parcial (14 colunas)
 * - Pode ser chamada após carregamento inicial ou após recarregamento de venues
 */
function normalizarCsv() {
	// Verifica se há pelo menos um venue com dados completos
	var temAlgumComDadosCompletos = (modo == DADOS_COMPLETOS) || (venuesComDadosCompletos.length > 0);
	var deveIncluirRedesNoCsv = deveIncluirColunasRedesCsv();

	debugLog('🔧 Normalização CSV iniciada:', {
		modo: modo,
		venuesComDadosCompletos: venuesComDadosCompletos.length,
		venuesComDadosCompletosArray: venuesComDadosCompletos,
		temAlgumComDadosCompletos: temAlgumComDadosCompletos,
		deveIncluirRedesNoCsv: deveIncluirRedesNoCsv,
		headerAntes: csv[0].slice(0, 5).join(', ') + '...',
		headerLength: csv[0].length
	});

	if (temAlgumComDadosCompletos) {
		// PASSO 1: Insere categoryId se necessário
		var headerTemCategoryId = (csv[0].length > 1 && csv[0][1] === "categoryId");
		debugLog('📋 Header tem categoryId?', headerTemCategoryId, '| Header:', csv[0].slice(0, 5));

		if (!headerTemCategoryId) {
			// Insere categoryId no header (posição 1, logo após "venue")
			csv[0].splice(1, 0, "categoryId");
			debugLog('✅ CategoryId inserido no header. Novo header:', csv[0].slice(0, 5));

			// Insere categoryId em cada linha conforme seu tipo
			var linhasAtualizadas = 0;
			for (var k = 0; k < document.forms.length; k++) {
				if (csv[k + 1]) {
					// Verifica se ESTA linha específica foi recarregada (tem dados completos)
					var foiRecarregada = venuesComDadosCompletos.indexOf(k) !== -1;

					if (foiRecarregada) {
						// Linha recarregada JÁ TEM categoryId (adicionado na linha 791), não insere
						debugLog('  → Linha', k, 'JÁ tem categoryId (recarregada), pulando');
					} else {
						// Linha parcial: insere categoria principal se disponível
						var categoryIdValue = "";
						if (categorias[k] && categorias[k].ids) {
							// Pega apenas a primeira categoria (principal)
							categoryIdValue = categorias[k].ids.split(",")[0] || "";
						}
						csv[k + 1].splice(1, 0, categoryIdValue);
						linhasAtualizadas++;
						debugLog('  → Linha', k, 'recebeu categoryId (parcial):', categoryIdValue || '(vazio)');
					}
				}
			}
			debugLog('✅ CategoryId inserido em', linhasAtualizadas, 'linhas parciais');
		}

		// Insere venueChainId apenas se deveIncluirRedesNoCsv
		if (deveIncluirRedesNoCsv) {
			var headerTemVenueChainId = (csv[0].indexOf("venueChainId") !== -1);
			if (!headerTemVenueChainId) {
				var catPos = csv[0].indexOf("categoryId");
				var chainInsertPos = (catPos !== -1) ? catPos + 1 : 1;
				csv[0].splice(chainInsertPos, 0, "venueChainId");
				for (var k = 0; k < document.forms.length; k++) {
					if (csv[k + 1]) {
						var chainIdValue = (redes[k] && redes[k].ids) ? redes[k].ids : "";
						csv[k + 1].splice(chainInsertPos, 0, chainIdValue ? '"' + chainIdValue + '"' : '""');
					}
				}
				debugLog('✅ venueChainId inserido no header e linhas do CSV');
			}
		} else {
			removerColunasRedesDoCsv();
			debugLog('ℹ️ Colunas de redes removidas do CSV pois redes não foi selecionada');
		}

		// PASSO 2: Adiciona colunas finais ao header (verifica se já foi feito)
		var jaTemColunasFinais = (csv[0].indexOf("createdAt") !== -1);
		if (!jaTemColunasFinais) {
			// Verifica se header já tem as 3 colunas parciais (checkins, users, tips)
			var temColunasParciais = (csv[0].indexOf("checkins") !== -1);

			if (temColunasParciais) {
				// Remove as 3 colunas parciais do final do header antes de adicionar as colunas completas
				debugLog('📋 Header tem colunas parciais, removendo para substituir por completas');
				// Encontra índice de "checkins" e remove dali até o final
				var indexCheckins = csv[0].indexOf("checkins");
				csv[0] = csv[0].slice(0, indexCheckins);
			}

			// Adiciona colunas completas ao HEADER (categorias e redes vêm ANTES das métricas)
			var colunasExtras = ["primaryCategoryId", "addCategoryIds", "removeCategoryIds"];
			if (deveIncluirRedesNoCsv) {
				colunasExtras.push("primaryVenueChainId", "addVenueChainIds", "removeVenueChainIds");
			}
			colunasExtras.push("createdAt", "checkins", "users", "tips", "likes", "listed", "photos", "closed", "private", "deleted");
			csv[0] = csv[0].concat(colunasExtras);

			// Normaliza APENAS as linhas parciais (que ainda não têm as colunas completas)
			var todasComDadosCompletos = (modo == DADOS_COMPLETOS && venuesComDadosCompletos.length === 0);
			var linhasNormalizadas = 0;

			if (!todasComDadosCompletos) {
				// Há mix de parciais e completas (ou apenas recarregadas)
				for (var k = 0; k < document.forms.length; k++) {
					var foiRecarregada = venuesComDadosCompletos.indexOf(k) !== -1;

					if (!foiRecarregada && csv[k + 1]) {
						// Esta linha tem apenas 3 colunas no final (checkins, users, tips)
						csv[k + 1] = csv[k + 1].slice(0, -3);
						var colunasLinha = ["", "", ""]; // primaryCategoryId, addCategoryIds, removeCategoryIds
						if (deveIncluirRedesNoCsv) {
							colunasLinha.push("", "", ""); // primaryVenueChainId, addVenueChainIds, removeVenueChainIds
						}
						colunasLinha.push(
							"",                   // createdAt (vazio)
							document.forms[k]["checkinsCount"].value,
							document.forms[k]["usersCount"].value,
							document.forms[k]["tipCount"].value,
							"", "", "", "", "", "" // likes, listed, photos, closed, private, deleted (vazios)
						);
						csv[k + 1] = csv[k + 1].concat(colunasLinha);
						linhasNormalizadas++;
					}
				}
			} else {
				debugLog('✅ Todas venues carregadas com dados completos desde início, linhas já têm colunas completas');
			}
			debugLog('✅ Colunas finais adicionadas:', {
				headerColunas: csv[0].length,
				headerFinal: csv[0].slice(-5).join(', ') + ' (últimas 5)',
				linhasNormalizadas: linhasNormalizadas,
				primeiraLinhaExemplo: csv[1] ? csv[1].length + ' colunas' : 'N/A'
			});
		} else {
			debugLog('⚠️ Colunas finais já adicionadas, pulando');
		}
	} else {
		// Todas têm apenas dados parciais
		// categoryId NÃO é adicionada no modo parcial sem reload.
		// venueChainId: adicionada APENAS se "Redes" estava selecionado na tela principal!
		if (isRedesSelecionada()) {
			var headerTemVenueChainId = (csv[0].indexOf("venueChainId") !== -1);
			if (!headerTemVenueChainId) {
				csv[0].splice(1, 0, "venueChainId");
				for (var k = 0; k < document.forms.length; k++) {
					if (csv[k + 1]) {
						var chainVal = (redes[k] && redes[k].ids) ? redes[k].ids : "";
						csv[k + 1].splice(1, 0, chainVal ? '"' + chainVal + '"' : '""');
					}
				}
				debugLog('✅ venueChainId inserido no CSV parcial pois "Redes" estava selecionado');
			}
		} else {
			removerColunasRedesDoCsv();
			debugLog('ℹ️ venueChainId NÃO incluído no CSV parcial pois "Redes" não foi selecionado');
		}

		var jaTemColunasParciais = (csv[0].indexOf("checkins") !== -1);
		if (!jaTemColunasParciais) {
			csv[0] = csv[0].concat("checkins", "users", "tips");
			debugLog('✅ CSV mantido em modo parcial (3 colunas)');
		}
	}
}

/**
 * Recarrega dados das venues da API Foursquare
 * - Se houver venues selecionadas, recarrega apenas as selecionadas
 * - Se não houver seleções, recarrega todas
 * - Verifica se há edições não salvas antes de proceder
 * - Sempre força carregamento completo (DADOS_COMPLETOS)
 * - Atualiza localStorage e marcadores do Google Maps
 * @param {number} [excluirIndice] - Índice da venue a ser excluída do recarregamento (opcional)
 */
function recarregarDadosVenues(excluirIndice) {
	// Verifica se há edições não salvas
	if (linhasEditadas.length > 0) {
		// Verifica se há seleções
		var checkboxesSelecionados = dojo.query("input[name=selecao]:checked");
		var totalSelecionadas = checkboxesSelecionados.length;

		var numEdicoes = linhasEditadas.length;

		// Se houver seleções, conta apenas as editadas que estão selecionadas
		if (totalSelecionadas > 0) {
			var edicoesNaSelecionadas = 0;
			checkboxesSelecionados.forEach(function(checkbox) {
				var venueIndex = parseInt(dijit.byId(checkbox.id).value);
				if (linhasEditadas.indexOf(venueIndex) !== -1) {
					edicoesNaSelecionadas++;
				}
			});
			numEdicoes = edicoesNaSelecionadas;
		}

		// Só exibe mensagem se houver edições pendentes nos locais selecionados
		if (numEdicoes > 0) {
			var mensagem = "Você tem " + numEdicoes + (numEdicoes > 1 ? " edições" : " edição") + " não " + (numEdicoes > 1 ? "salvas" : "salva") + " nos " + (totalSelecionadas > 0 ? "locais selecionados" : "locais") + ". Recarregar descartará todas as alterações. Deseja continuar?";
			dojo.byId("reloadMessage").innerHTML = mensagem;

			// Configura ação do botão de confirmação
			var confirmButton = dijit.byId("confirmReloadButton");
			if (confirmButton._connections) {
				dojo.forEach(confirmButton._connections, dojo.disconnect);
			}
			confirmButton.onClick = function() {
				dijit.byId("dlg_reload").hide();
				executarRecarregamento(excluirIndice);
			};

			dijit.byId("dlg_reload").show();
		} else {
			// Não há edições nos locais selecionados, recarrega diretamente
			executarRecarregamento(excluirIndice);
		}
	} else {
		executarRecarregamento(excluirIndice);
	}
}

/**
 * Executa o recarregamento efetivo dos dados
 * @param {number} [excluirIndice] - Índice da venue a ser excluída do recarregamento (opcional)
 */
function executarRecarregamento(excluirIndice) {
	var token = getOauthToken();

	if (!token)
		return handleMissingOauthToken("recarregar os locais");

	// Determina quais venues recarregar
	var venuesParaRecarregar = [];
	var checkboxesSelecionados = dojo.query("input[name=selecao]:checked");
	var totalSelecionadas = checkboxesSelecionados.length;

	if (totalSelecionadas > 0) {
		// Recarrega apenas as selecionadas
		checkboxesSelecionados.forEach(function(checkbox) {
			var venueIndex = parseInt(dijit.byId(checkbox.id).value);
			// Exclui o índice especificado se fornecido
			if (excluirIndice === undefined || venueIndex !== excluirIndice) {
				venuesParaRecarregar.push(venueIndex);
			}
		});
		debugInfo("Recarregando " + venuesParaRecarregar.length + " venue" + (venuesParaRecarregar.length > 1 ? "s" : "") + " selecionada" + (venuesParaRecarregar.length > 1 ? "s" : "") + "...");
	} else {
		// Recarrega todas
		for (var i = 0; i < document.forms.length; i++) {
			// Exclui o índice especificado se fornecido
			if (excluirIndice === undefined || i !== excluirIndice) {
				venuesParaRecarregar.push(i);
			}
		}
		var mensagemLog = excluirIndice !== undefined
			? "Recarregando " + venuesParaRecarregar.length + " venue" + (venuesParaRecarregar.length > 1 ? "s" : "") + " (exceto a venue de origem)..."
			: "Recarregando todas as " + venuesParaRecarregar.length + " venues...";
		debugInfo(mensagemLog);
	}

	// Salva estado dos checkboxes antes do reload
	var estadoCheckboxes = {};
	dojo.query("input[name=selecao]").forEach(function(checkbox) {
		estadoCheckboxes[checkbox.id] = dijit.byId(checkbox.id).checked;
	});

	// Salva posição do scroll
	var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

	// Configura progress bar
	totalCarregadas = 0;
	totalNaoCarregadas = 0;
	var totalParaRecarregar = venuesParaRecarregar.length;
	dijit.byId("saveProgress").update({maximum: totalParaRecarregar, progress: 0});

	var tituloModal = "Recarregando " + totalParaRecarregar + " venue" + (totalParaRecarregar > 1 ? "s" : "") + "...";
	dijit.byId("dlg_save").set("title", tituloModal);
	dijit.byId("dlg_save").show();

	// Desabilita botões durante recarregamento
	dijit.byId("saveButton").setAttribute("disabled", true);
	dijit.byId("reloadButton").setAttribute("disabled", true);

	// Remove indicadores visuais apenas das linhas que serão recarregadas
	for (var idx = 0; idx < venuesParaRecarregar.length; idx++) {
		var venueIdx = venuesParaRecarregar[idx];
		var linhaElement = dojo.byId("linha" + venueIdx);
		if (linhaElement) {
			dojo.removeClass(linhaElement, "edited");
			dojo.removeClass(linhaElement, "error"); // Remove erro também
		}
		// Remove do array de linhas editadas
		var posicaoEditada = linhasEditadas.indexOf(venueIdx);
		if (posicaoEditada !== -1) {
			linhasEditadas.splice(posicaoEditada, 1);
		}
	}

	// Limpa cache JSON para forçar dados completos
	json = "";
	if (localStorage) {
		localStorage.removeItem('venues');
		localStorage.removeItem('venuesComDadosCompletos');
	}

	// Força modo DADOS_COMPLETOS
	modo = DADOS_COMPLETOS;

	// Carrega categorias se necessário (habilita edição)
	var d = new Date();
	d.setHours(0, 0, 0, 0);
	if ((!localStorage.getItem('categorias')) || (d > dojo.cookie("data_categorias"))) {
		carregarListaCategorias();
	} else {
		var resposta = JSON.parse(localStorage.getItem('categorias'));
		montarArvore(resposta);
		debugInfo("Categorias recuperadas do localStorage!");
	}

	// Contador para progresso
	var carregadasNoReload = 0;

	// Recarrega cada venue
	venuesParaRecarregar.forEach(function(venueIndex) {
		var venue = document.forms[venueIndex]["venue"].value;
		dojo.byId("result" + venueIndex).innerHTML = "<img src='img/loading.gif' alt='Recarregando dados...'>";

		// Marca que esta linha está sendo recarregada (para habilitar categorias depois)
		document.forms[venueIndex].setAttribute('data-reloading', 'true');

		// Faz requisição à API
		xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/" + venue + "?oauth_token=" + token + "&v=" + DATA_VERSIONAMENTO, "reload", null, venueIndex);
	});

	// Monitora conclusão do reload (a atualização de progresso é feita via xmlhttpRequest callback)
	var checkReloadComplete = setInterval(function() {
		var totalProcessadas = totalCarregadas + totalNaoCarregadas;
		dijit.byId("saveProgress").update({progress: totalProcessadas});

		if (totalProcessadas >= totalParaRecarregar) {
			clearInterval(checkReloadComplete);

			// Restaura estado dos checkboxes
			setTimeout(function() {
				for (var checkboxId in estadoCheckboxes) {
					if (dijit.byId(checkboxId)) {
						dijit.byId(checkboxId).setChecked(estadoCheckboxes[checkboxId]);
					}
				}

				// Remove marcação de recarregamento das linhas
				venuesParaRecarregar.forEach(function(venueIndex) {
					document.forms[venueIndex].removeAttribute('data-reloading');
				});

				// Restaura posição do scroll
				window.scrollTo(0, scrollTop);
			}, 100);

			// Atualiza marcadores do Google Maps
			if (window.googleMaps && window.googleMaps.map && window.locais.length > 0) {
				debugInfo("Atualizando marcadores do mapa...");
				window.googleMaps.updateMarkers(window.locais);
			}

			// Normaliza CSV após recarregamento (para garantir consistência entre parciais e completos)
			debugLog('🔄 Executando normalização após reload...');
			normalizarCsv();

			// Exibe mensagem final
			var mensagemFinal = "Recarregamento concluído!";
			if (totalNaoCarregadas > 0) {
				mensagemFinal += " (" + totalNaoCarregadas + " falha" + (totalNaoCarregadas > 1 ? "s" : "") + ")";
			}
			dijit.byId("dlg_save").set("title", mensagemFinal);

			// Habilita botões após conclusão
			dijit.byId("saveButton").setAttribute("disabled", false);
			dijit.byId("reloadButton").setAttribute("disabled", false);

			debugInfo("✅ " + mensagemFinal);

			// Fecha modal após 2 segundos
			setTimeout(function() {
				dijit.byId("dlg_save").hide();
			}, 2000);
		}
	}, 100);
}

function salvarVenues() {
	var token = getOauthToken();

	if (!token)
		return handleMissingOauthToken("salvar as alterações");

	totalEditadas = 0;
	totalParaSalvar = linhasEditadas.length;
	totalProgresso = 0;
	totalTimeout = 0;
	dijit.byId("saveProgress").update({maximum: totalParaSalvar, progress: totalEditadas});
	(totalParaSalvar > 1) ? dijit.byId("dlg_save").set("title", "Salvando " + totalParaSalvar + " venues...") : dijit.byId("dlg_save").set("title", "Salvando 1 venue...");
	dijit.byId("dlg_save").show();
	dijit.byId("saveButton").setAttribute("disabled", true);
	dijit.byId("reloadButton").setAttribute("disabled", true);
	var i, venueId, dados, elementName;
	debugInfo("Enviando dados...");
	for (l = 0; l < totalParaSalvar; l++) {
		i = linhasEditadas[l];
		dados = "oauth_token=" + token;
		var length = document.forms[i].elements.length;
		for (j = columnsStartIndex; j < length; j++) {
			elementName = document.forms[i].elements[j].name;
			if (document.forms[i].elements[j].type == "text") {
				//console.log(elementName + ' adicionado');
				if ((['name', 'address', 'crossStreet', 'neighborhood', 'city', 'state', 'zip', 'parentId', 'phone', 'url', 'twitter', 'instagram'].indexOf(elementName) > -1) && (dojo.query("input[name=" + elementName + "]")[i].disabled != true) && (document.forms[i][elementName].readOnly == false))
					dados += "&" + elementName + "=" + encodeURIComponent(document.forms[i].elements[j].value);
				else if (elementName == "facebook") {
					var facebookUsername = document.forms[i].elements[j].value;
					if ((facebookUsername != null) && (facebookUsername != ""))
						dados += "&facebookUrl=" + encodeURIComponent("facebook.com/" + facebookUsername);
				} else if ((elementName == "description") && (document.forms[i]["description"].readOnly == false)) {
					var index = csv[0].indexOf("description");
					dados += "&description=" + encodeURIComponent(csv[i + 1][index].slice(1, -1));
				} else if (elementName == "menu") {
						dados += "&menuUrl=" + encodeURIComponent(document.forms[i].elements[j].value);
				}
			} //else
				//console.log(elementName + ' ignorado');
		}
		var ll = document.forms[i]["venuell"].value;
		if ((ll != null) && (ll != "") && (ll != venuellOriginais[i]))
			dados += "&venuell=" + encodeURIComponent(ll.replace(/ /g, ""));

		// Verifica se esta venue tem dados completos (modo global OU venue individual recarregada)
		var estaVenueTemDadosCompletos = (modo == DADOS_COMPLETOS) || (venuesComDadosCompletos.indexOf(i) !== -1);

		debugLog('🔍 salvarVenues - Venue', i, ':', {
			modo: modo,
			modoString: modo == DADOS_COMPLETOS ? 'DADOS_COMPLETOS' : 'DADOS_PARCIAIS',
			venuesComDadosCompletos: venuesComDadosCompletos,
			indiceDentroArray: venuesComDadosCompletos.indexOf(i),
			estaVenueTemDadosCompletos: estaVenueTemDadosCompletos,
			categoryIdValue: document.forms[i]["categoryId"].value
		});

		if (estaVenueTemDadosCompletos) {
			var categoryIds = document.forms[i]["categoryId"].value;
			debugLog('📋 Processando categorias para venue', i, '- categoryIds:', categoryIds);

			if ((categoryIds != null) && (categoryIds != "")) {
				// LÊ DO CSV as colunas já calculadas por processarEdicaoCategorias()
				// (não recalcula pois categorias[i].ids já foi sobrescrito com novos valores)
				var indexPrimaryCategoryId = csv[0].indexOf("primaryCategoryId");
				var indexRemoveCategoryIds = csv[0].indexOf("removeCategoryIds");
				var indexAddCategoryIds = csv[0].indexOf("addCategoryIds");

				debugLog('🔍 Índices CSV:', {
					primaryCategoryId: indexPrimaryCategoryId,
					removeCategoryIds: indexRemoveCategoryIds,
					addCategoryIds: indexAddCategoryIds
				});

				// Remove aspas dos valores do CSV
				var primaryFromCsv = (indexPrimaryCategoryId !== -1 && csv[i + 1][indexPrimaryCategoryId])
					? csv[i + 1][indexPrimaryCategoryId].replace(/"/g, '')
					: '';
				var removeFromCsv = (indexRemoveCategoryIds !== -1 && csv[i + 1][indexRemoveCategoryIds])
					? csv[i + 1][indexRemoveCategoryIds].replace(/"/g, '')
					: '';
				var addFromCsv = (indexAddCategoryIds !== -1 && csv[i + 1][indexAddCategoryIds])
					? csv[i + 1][indexAddCategoryIds].replace(/"/g, '')
					: '';

				debugLog('📊 Valores do CSV venue', i, ':', {
					primaryCategoryId: primaryFromCsv,
					removeCategoryIds: removeFromCsv,
					addCategoryIds: addFromCsv
				});

				// Adiciona parâmetros aos dados do POST
				if (removeFromCsv != '') {
					dados += "&removeCategoryIds=" + removeFromCsv;
					debugLog('✅ removeCategoryIds adicionado:', removeFromCsv);
				}
				if (primaryFromCsv != '') {
					dados += "&primaryCategoryId=" + primaryFromCsv;
					debugLog('✅ primaryCategoryId adicionado:', primaryFromCsv);
				}
				if (addFromCsv != '') {
					dados += "&addCategoryIds=" + addFromCsv;
					debugLog('✅ addCategoryIds adicionado:', addFromCsv);
				}
			} else {
				debugLog('⚠️ categoryIds vazio ou null para venue', i);
			}
		} else {
			debugLog('⚠️ Venue', i, 'NÃO tem dados completos - categorias NÃO serão processadas');
		}

		// Processa redes para esta venue
		var indexPrimaryChainId = csv[0].indexOf("primaryVenueChainId");
		var indexRemoveChainIds = csv[0].indexOf("removeVenueChainIds");
		var indexAddChainIds = csv[0].indexOf("addVenueChainIds");

		if (indexPrimaryChainId !== -1 || indexRemoveChainIds !== -1 || indexAddChainIds !== -1) {
			var primaryChainFromCsv = (indexPrimaryChainId !== -1 && csv[i + 1][indexPrimaryChainId])
				? csv[i + 1][indexPrimaryChainId].replace(/"/g, '') : '';
			var removeChainFromCsv = (indexRemoveChainIds !== -1 && csv[i + 1][indexRemoveChainIds])
				? csv[i + 1][indexRemoveChainIds].replace(/"/g, '') : '';
			var addChainFromCsv = (indexAddChainIds !== -1 && csv[i + 1][indexAddChainIds])
				? csv[i + 1][indexAddChainIds].replace(/"/g, '') : '';

			if (removeChainFromCsv != '') {
				dados += "&removeVenueChainIds=" + removeChainFromCsv;
				debugLog('✅ removeVenueChainIds adicionado:', removeChainFromCsv);
			}
			if (primaryChainFromCsv != '') {
				dados += "&primaryVenueChainId=" + primaryChainFromCsv;
				debugLog('✅ primaryVenueChainId adicionado:', primaryChainFromCsv);
			}
			if (addChainFromCsv != '') {
				dados += "&addVenueChainIds=" + addChainFromCsv;
				debugLog('✅ addVenueChainIds adicionado:', addChainFromCsv);
			}
		}

		var comentario = encodeURIComponent(dijit.byId("textareaComment").value);
		if (comentario != "")
			dados += "&comment=" + comentario;
		dados += "&v=" + DATA_VERSIONAMENTO;
		venueId = document.forms[i]["venue"].value;
		console.group("venue=" + venueId + " (" + i + ")");
		debugLog(dados);
		console.groupEnd();
		var acao = "proposeedit";
		xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venueId + "/" + acao, "edit", dados, i);
		dojo.byId("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";
	}
	debugInfo("Dados enviados!");
}

function sinalizarVenues(problema) {
	var token = getOauthToken();

	if (!token)
		return handleMissingOauthToken("sinalizar os locais");

	//dojo.query("input[name=selecao]:checked").forEach("console.log(dijit.byId(item.id).value)");
	linhasSelecionadas = dojo.query("input[name=selecao]:checked");
	totalSinalizadas = 0;
	if (problema == "duplicate") {
		/*** Verifica qual a venue que possui mais check-ins ***/
		var linhaComMaisCheckins = 0;
		var checkinsCount = parseInt(dojo.byId("vcc" + linhasSelecionadas[linhaComMaisCheckins].value).value);
		var venueId = document.forms[linhasSelecionadas[linhaComMaisCheckins].value]["venue"].value;
		var checkins = 0;
		for (l = 1; l < totalSelecionadas; l++) {
			checkins = parseInt(dojo.byId("vcc" + linhasSelecionadas[l].value).value);
			if (checkins > checkinsCount) {
				linhaComMaisCheckins = l;
				checkinsCount = checkins;
				venueId = document.forms[linhasSelecionadas[linhaComMaisCheckins].value]["venue"].value;
			}
		}
		linhaVenueComMaisCheckins = parseInt(linhasSelecionadas[linhaComMaisCheckins].value);
		linhasSelecionadas.splice(linhaComMaisCheckins, 1);
		totalParaSinalizar = totalSelecionadas - 1;
	} else {
		totalParaSinalizar = totalSelecionadas;
	}

	totalProgresso = 0;
	totalTimeout = 0;
	dijit.byId("saveProgress").update({maximum: totalParaSinalizar, progress: totalSinalizadas});
	(totalParaSinalizar > 1) ? dijit.byId("dlg_save").set("title", "Sinalizando " + totalParaSinalizar + " venues...") : dijit.byId("dlg_save").set("title", "Sinalizando 1 venue...");
	dijit.byId("dlg_save").show();
	dijit.byId("menuSinalizar").setAttribute("disabled", true);
	debugInfo("Enviando dados...");

	var l = 0;
	var linhas = linhasSelecionadas.slice(0);
	function sinalizar(delayTime) {
		setTimeout(function () {
			var venue, dados, acao, comentario;
			var i = linhas[l].value;
			venue = document.forms[i]["venue"].value;
			dados = "oauth_token=" + token;
			if (problema == "home_remove") {
				dados += "&removeCategoryIds=" + CATEGORIA_HOME;
				acao = "proposeedit";
			} else {
				dados += "&problem=" + problema;
				acao = "flag";
			}
			comentario = encodeURIComponent(dijit.byId("textareaComment").value);
			dados += "&comment=" + comentario + "&v=" + DATA_VERSIONAMENTO;
			if (problema == "duplicate") {
				dados += "&venueId=" + venueId;
				delayTime = 5000;
			}
			console.group("venue=" + venue + " (" + i + ")");
			debugLog(dados);
			console.groupEnd();
			xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venue + "/" + acao, "flag", dados, i);
			dojo.byId("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";

			l++;
			if (l < totalParaSinalizar) {
				sinalizar(delayTime);
			} else {
				debugInfo("Dados enviados!");
			}
		}, delayTime); // wait 0 or 5000 milliseconds (API duplicate bug) to flag
	}

	sinalizar(0);
}

function selecionarTodas(valor) {
	dojo.query("input[name=selecao]").forEach(
		function(inputElem) {
			if (inputElem.disabled != true)
				dijit.byId(inputElem.id).setChecked(valor);
		}
	);
}

function carregarListaCategorias() {
	var token = getOauthToken();

	if (!token)
		return handleMissingOauthToken("carregar as categorias");

	debugInfo("Recuperando dados das categorias...");
	xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/categories" + "?oauth_token=" + token + "&v=" + DATA_VERSIONAMENTO, "load", null, null);
}

var STR_PAD_LEFT = 1;
var STR_PAD_RIGHT = 2;
var STR_PAD_BOTH = 3;

function pad(str, len, pad, dir) {
	if (typeof(len) == "undefined") { var len = 0; }
	if (typeof(pad) == "undefined") { var pad = ' '; }
	if (typeof(dir) == "undefined") { var dir = STR_PAD_RIGHT; }
	if (len + 1 >= str.length) {
		switch (dir) {
		case STR_PAD_LEFT:
			str = Array(len + 1 - str.length).join(pad) + str;
			break;
		case STR_PAD_BOTH:
			var right = Math.ceil((padlen = len - str.length) / 2);
			var left = padlen - right;
			str = Array(left + 1).join(pad) + str + Array(right + 1).join(pad);
			break;
		default:
			str = str + Array(len + 1 - str.length).join(pad);
		} // switch
	}
	return str;
}

// ===== GOOGLE MAPS - INTEGRAÇÃO =====

// Callback para integração com o sistema de mapas
window.atualizarPosicaoMarcador = function(index, event) {
	debugLog('📍 Marcador movido:', index, event);

	if (!event || !event.latLng) return;

	const marcador = index + 1;
	const novaPosicao = event.latLng.lat() + ', ' + event.latLng.lng();

	debugInfo('Marcador ' + marcador + ' movido: ' + novaPosicao);

	// Integração com o sistema existente
	try {
		const inputElements = dojo.query("input[name=selecao]");
		if (inputElements[index] && !inputElements[index].disabled) {
			const inputId = dojo.query("input[name=venuell]")[index].id;

			if (inputId === "" ||
				(dijit.byId(inputId) &&
				 dijit.byId(inputId).textbox.value !== novaPosicao &&
				 !dijit.byId(inputId).readOnly &&
				 !dijit.byId(inputId).disabled)) {

				// Atualiza o campo
				if (inputId === "") {
					dojo.query("input[name=venuell]")[index].value = novaPosicao;
				} else {
					dijit.byId(inputId).set("value", novaPosicao);
				}

				// Marca a linha como editada
				debugLog('🔵 Marcando linha ' + (index + 1) + ' como editada após arrastar marcador');
				verificarAlteracao(dojo.query("input[name=venuell]")[index], index);

				// Atualiza CSV
				if (window.csv) {
					const csvIndex = window.csv[0].indexOf("venuell");
					if (csvIndex !== -1) {
						window.csv[parseInt(index) + 1][csvIndex] = novaPosicao;
					}
				}

				// Limpa resultado
				const resultElement = dojo.byId("result" + index);
				if (resultElement) {
					resultElement.innerHTML = "";
				}
			}
		}
	} catch (error) {
		console.error('❌ Erro ao atualizar posição do marcador:', error);
	}
};

function removerNaoSelecionadas(arq, startIndex) {
	var j = startIndex;
	var l = arq.length;
	linhasSelecionadas = [];
	dojo.query("input[name=selecao]:checked").forEach("linhasSelecionadas.push(dijit.byId(item.id).value)");
	for (i = 0; i < l; i++) {
		if (linhasSelecionadas.indexOf(i.toString()) == -1)
			arq.splice(j, 1);
		else
			j++;
	}
	return arq;
}

var dlgGuia;
dojo.addOnLoad(function inicializar() {
	estadoEdicaoParaRestaurar = obterEstadoEdicaoDaNavegacao();
	if (estadoEdicaoParaRestaurar) {
		modo = estadoEdicaoParaRestaurar.modo;
		json = modo === DADOS_PARCIAIS ? {} : "";
	} else if (localStorage && localStorage.getItem('venues')) {
		json = JSON.parse(localStorage.getItem('venues'));
	}

	/*** Valida OAuth Token via SessionManager moderno ***/
	if (window.sessionManager) {
		// Usa o SessionManager moderno
		window.sessionManager.checkSessionStatus(false).then(isValid => {
			if (!isValid) {
				const tokenFallback = getOauthToken();
				if (tokenFallback) {
					debugLog("⚠️ SessionStatus retornou inválido, mas token válido foi encontrado. Continuando inicialização.");
					initializeApplication();
					return;
				}

				handleMissingOauthToken("inicializar o editor");
				return;
			}
			// Continua com a inicialização normal
			initializeApplication();
		});
	} else {
		// Fallback para verificação legacy com cookie
		debugLog("🔄 Usando validação legacy de token");

		// Verifica se o token existe via cookie
		const legacyToken = getOauthToken();
		if (!legacyToken || legacyToken === "undefined" || legacyToken === "null") {
			console.warn("🔒 Token legacy não encontrado ou inválido");
			handleMissingOauthToken("inicializar o editor");
			return;
		}

		debugLog("✅ Token legacy válido - continuando inicialização");
		// Continua com a inicialização normal
		initializeApplication();
	}

	function initializeApplication() {

	/*** Inicializar Google Maps ***/
	if (document.getElementById('mapa')) {
		const initMap = () => {
			debugLog('🚀 Inicializando o mapa...');
			if (window.googleMaps) {
				window.googleMaps.initialize().then(() => {
					debugInfo("Mapa carregado!");
					// Se já há venues carregadas, posiciona os marcadores imediatamente
					if (window.locais && window.locais.length > 0) {
						atualizarMarcadoresLocais();
						debugInfo("Marcadores posicionados no mapa!");
					}
				}).catch(error => {
					console.error("Falha ao inicializar o mapa:", error);
				});
			} else {
				console.error("Erro crítico: googleMaps não está definido no momento da inicialização.");
			}
		};

		if (window.googleMaps) {
			debugLog('🚀 Google Maps já estava pronto. Inicializando imediatamente.');
			initMap();
		} else {
			// Espera o evento que sinaliza que a API do Google Maps está pronta
			document.addEventListener('google-maps-ready', () => {
				debugLog('🚀 Evento "google-maps-ready" recebido.');
				initMap();
			});
		}
	}

	/*** Autoajusta o tamanho inicial do mapa conforme largura da lista ***/
	// Só define largura inicial se não foi personalizada pelo usuário
	const mapaElement = dojo.byId('mapa');
	if (mapaElement && !mapaElement.style.width) {
		dojo.style("mapa", "width", dojo.byId('listContainer').offsetWidth.toString() + "px");
		debugLog('📏 Largura inicial do mapa definida automaticamente');
	}

	/*** Guia de Estilo ***/
	dlg_guia = new dijit.Dialog({
		title: "Guia de estilo",
		style: "width: 435px"
	});

	/*** Modal de Exportação de URLs ***/
	dlg_export_urls = new dijit.Dialog({
		title: "Exportar URLs",
		style: "width: 600px"
	});

	/*** Modal de Exportação de URL Direta ***/
	dlg_export_direct_url = new dijit.Dialog({
		title: "Exportar URL Direta",
		style: "width: 500px"
	});

	/*** Modal de Relatório ***/
	dlg_report = new dijit.Dialog({
		title: "Relatório de Edições",
		style: "width: 900px; max-width: 95%;"
	});

	/*** Modal de Exportação CSV ***/
	dlg_export_csv = new dijit.Dialog({
		title: "Exportar CSV",
		style: "width: 700px; max-width: 90%;"
	});

	/*** DropDown do menu Mais ***/
	var menu = new dijit.Menu({
		style: "display: none;"
	});

	/*** Submenu Selecionar ***/
	var subMenu1 = new dijit.Menu({
		style: "display: none;"
	});

	var subMenu1Item1 = new dijit.MenuItem({
		label: "Todas",
		id: "menuItemSelecionarTodas",
		onClick: function() {
			selecionarTodas(true);
		}
	});
	subMenu1.addChild(subMenu1Item1);

	var subMenu1Item2 = new dijit.MenuItem({
		label: "Nenhuma",
		id: "menuItemSelecionarNenhuma",
		onClick: function() {
			selecionarTodas(false);
		}
	});
	subMenu1.addChild(subMenu1Item2);

	var menuItem1 = new dijit.PopupMenuItem({
		label: "Selecionar",
		id: "menuSelecionar",
		popup: subMenu1
	});

	menu.addChild(menuItem1);

	/*** Separador ***/
	menu.addChild(new dijit.MenuSeparator);

	/*** Submenu Editar ***/
	var subMenu2 = new dijit.Menu({
		style: "display: none;"
	});

	var elements;
	var elementName;
	var nomeCampo;
	var options = [];
	var select = dijit.byId("selectEditField");
	var subMenu2Item;
	if (json == "") { // modo == DADOS_COMPLETOS
		subMenu2Item = new dijit.MenuItem({
			label: "Categorias",
			id: "menu2ItemCategories",
			onClick: function() {
				atualizarCategorias([], "", "");
				dijit.byId("dlg_cats").show();

				// Habilita checkbox apenas se houver 2+ venues com dados completos
				var totalComDadosCompletos = contarVenuesComDadosCompletos();
				if (totalComDadosCompletos >= 2) {
					dijit.byId("editAllCheckbox").attr("checked", true);
					dijit.byId("editAllCheckbox").attr("disabled", false);
				} else {
					dijit.byId("editAllCheckbox").attr("checked", false);
					dijit.byId("editAllCheckbox").attr("disabled", true);
				}
				//console.log(this.id + " clicado.");
			}
		});
		subMenu2.addChild(subMenu2Item);

		var subMenu2ItemChains = new dijit.MenuItem({
			label: "Redes",
			id: "menu2ItemChains",
			disabled: !(window.appBootstrap && window.appBootstrap.editChains),
			onClick: function() {
				atualizarRedes([]);
				if (dojo.byId("venueChainIndex")) dojo.byId("venueChainIndex").innerHTML = "0";
				if (dojo.byId("chainsResultsContainer")) {
					dojo.byId("chainsResultsContainer").innerHTML = "";
					dojo.byId("chainsResultsContainer").style.display = "none";
				}
				dijit.byId("dlg_chains").show();
				configurarBuscaReativaRede();
				if (dijit.byId("inputChainSearch")) dijit.byId("inputChainSearch").set("value", "");
				configurarEdicaoMultiplaRedes(true);
			}
		});
		subMenu2.addChild(subMenu2ItemChains);
	}
	if (document.form1.name.type == "hidden")
		columnsStartIndex++;
	//if (document.form1.venuell.type == "hidden")
		//totalInputsHidden++;
	csv[0] = ["venue"];
	if (json == "") // modo == DADOS_COMPLETOS
		csv[0] = csv[0].concat("categoryId");
	elements = document.form1.elements;
	//colunas = elements.length - totalInputsHidden;
	if (subMenu2Item != undefined)
		subMenu2.addChild(new dijit.MenuSeparator);
	for (c = columnsStartIndex; c < elements.length; c++) {
		if (elements[c].type == "text") {
			elementName = elements[c].name;
			nomeCampo = elements[c].getAttribute("data-name-ptbr");
			subMenu2Item = new dijit.MenuItem({
				label: nomeCampo,
				id: "menu2Item" + elementName,
				onClick: function() {
					showDialogEditField(this.id.substring(9));
					//console.log(this.id + " clicado.");
				}
			});
			subMenu2.addChild(subMenu2Item);
			options.push({ value: elementName, label: nomeCampo, selected: false });
			csv[0] = csv[0].concat(elementName);
			//console.info(c, elementName, nomeCampo, "adicionado");
		} //else
			//console.info(c, elements[c].name, "ignorado");
	}
	// REMOVIDO: primaryCategoryId, addCategoryIds, removeCategoryIds são adicionados por normalizarCsv()
	// para evitar duplicação quando modo == DADOS_COMPLETOS
	select.addOption(options);

	var menuItem2 = new dijit.PopupMenuItem({
		label: "Editar",
		id: "menuEditar",
		iconClass: "editIcon",
		popup: subMenu2
	});

	menu.addChild(menuItem2);

	/*** Submenu Sinalizar ***/
	var subMenu3 = new dijit.Menu({
		style: "display: none;"
	});

	var subMenu3Item1 = new dijit.MenuItem({
		label: "duplicate", // "Duplicadas",
		id: "menuItemSinalizarDuplicate",
		onClick: function() {
			showDialogComment("duplicate", "Duplicada");
		}
	});
	subMenu3.addChild(subMenu3Item1);

	/*** Separador (item) ***/
	subMenu3.addChild(new dijit.MenuSeparator);

	var subMenu3Item2 = new dijit.MenuItem({
		label: "doesnt_exist", // "N&atilde;o existe",
		id: "menuItemSinalizarDoesnt_exist",
		//iconClass: "doesnt_existIcon",
		onClick: function() {
			//dojo.query("input[name=selecao]:checked").forEach("desabilitarLinha(dijit.byId(item.id).value)");
			showDialogComment("doesnt_exist", deCode("N&#227;o existe"));
		}
	});
	subMenu3.addChild(subMenu3Item2);

	var subMenu3Item3 = new dijit.MenuItem({
		label: "event_over", // "J&aacute; terminou",
		id: "menuItemSinalizarEvent_over",
		//iconClass: "event_overIcon",
		onClick: function() {
			showDialogComment("event_over", deCode("J&#225; terminou"));
		}
	});
	subMenu3.addChild(subMenu3Item3);

	var subMenu3Item4 = new dijit.MenuItem({
		label: "inappropriate", // "Inadequada",
		id: "menuItemSinalizarInappropriate",
		//iconClass: "inappropriateIcon",
		onClick: function() {
			showDialogComment("inappropriate", "Inadequada");
		}
	});
	subMenu3.addChild(subMenu3Item4);

	var subMenu3Item5 = new dijit.MenuItem({
		label: "closed", // "Fechada",
		id: "menuItemSinalizarClosed",
		//iconClass: "closedIcon",
		onClick: function() {
			showDialogComment("closed", "Fechada");
		}
	});
	subMenu3.addChild(subMenu3Item5);

	/*** Separador (item) ***/
	subMenu3.addChild(new dijit.MenuSeparator);

	var subMenu3Item6 = new dijit.MenuItem({
		label: "home_remove", // "",
		id: "menuItemSinalizarHome_remove",
		//iconClass: "closedIcon",
		onClick: function() {
			showDialogComment("home_remove", "");
		}
	});
	subMenu3.addChild(subMenu3Item6);

	var subMenu3Item7 = new dijit.MenuItem({
		label: "home_recategorize", // "",
		id: "menuItemSinalizarHome_recategorize",
		//iconClass: "closedIcon",
		onClick: function() {
			showDialogComment("home_recategorize", "");
		}
	});
	subMenu3.addChild(subMenu3Item7);

	/*** Separador (item) ***/
	subMenu3.addChild(new dijit.MenuSeparator);

	var subMenu3Item8 = new dijit.MenuItem({
		label: "public", // "P&uacute;blica",
		id: "menuItemSinalizarPublic",
		//iconClass: "closedIcon",
		onClick: function() {
			showDialogComment("public", deCode("P&#250;blica"));
		}
	});
	subMenu3.addChild(subMenu3Item8);

	var subMenu3Item9 = new dijit.MenuItem({
		label: "private", // "Particular",
		id: "menuItemSinalizarPrivate",
		//iconClass: "privateIcon",
		onClick: function() {
			showDialogComment("private", "Particular");
		}
	});
	subMenu3.addChild(subMenu3Item9);

	/*** Separador (item) ***/
	subMenu3.addChild(new dijit.MenuSeparator);

	var subMenu3Item10 = new dijit.MenuItem({
		label: "not_closed", // "Reaberta",
		id: "menuItemSinalizarNot_closed",
		//iconClass: "closedIcon",
		onClick: function() {
			showDialogComment("not_closed", "Reaberta");
		}
	});
	subMenu3.addChild(subMenu3Item10);

	var subMenu3Item11 = new dijit.MenuItem({
		label: "un_delete", // "",
		id: "menuItemSinalizarUn_delete",
		//iconClass: "closedIcon",
		onClick: function() {
			showDialogComment("un_delete", "");
		}
	});
	subMenu3.addChild(subMenu3Item11);

	var menuItem3 = new dijit.PopupMenuItem({
		label: "Sinalizar",
		id: "menuSinalizar",
		iconClass: "flagIcon",
		disabled: true,
		popup: subMenu3
	});
	menu.addChild(menuItem3);

	/*** Separador (Submenu) ***/
	menu.addChild(new dijit.MenuSeparator);

	/*** Submenu Exportar ***/
	var subMenu4 = new dijit.Menu({
		style: "display: none;"
	});

	var subMenu4Item1 = new dijit.MenuItem({
		label: "Arquivo CSV",
		id: "menuItemExportarCSV",
		onClick: function() {
			showDialogExportCsv();
		}
	});
	subMenu4.addChild(subMenu4Item1);

	var subMenu4Item2 = new dijit.MenuItem({
		label: "Arquivo TXT",
		id: "menuItemExportarTXT",
		onClick: function() {
			showDialogExportUrls();
		}
	});
	subMenu4.addChild(subMenu4Item2);

	/**
	 * Menu item for exporting venue data as a direct URL.
	 *
	 * Creates a Dijit MenuItem that, when clicked, generates a URL containing venue IDs
	 * extracted from a text array. The venue IDs are obtained by:
	 * 1. Creating a copy of the 'txt' array
	 * 2. Extracting the last segment after the last "/" from each item
	 * 3. Trimming each ID to 24 characters maximum
	 * 4. If there are selected items (totalSelecionadas > 0), filtering out non-selected venues
	 * 5. Opening a new window with a data URL containing the venue IDs as a comma-separated list
	 *
	 * The generated URL format is:
	 * https://4sq.eliotools.site/load.php?venues=[venue_id1,venue_id2,...]
	 *
	 * @type {dijit.MenuItem}
	 * @property {string} label - Display text "URL direta" (Direct URL)
	 * @property {string} id - MenuItem identifier "menuItemExportarURL"
	 * @property {Function} onClick - Handler that processes and exports venue data as URL
	 */
	var subMenu4Item3 = new dijit.MenuItem({
		label: "URL direta",
		id: "menuItemExportarURL",
		onClick: function() {
			showDialogExportDirectUrl();
		}
	});
	subMenu4.addChild(subMenu4Item3);

	subMenu4.addChild(new dijit.MenuSeparator);

	var subMenu4Item4 = new dijit.MenuItem({
		label: "Relat&oacute;rio",
		id: "menuItemExportarRelatorio",
		disabled: true,
		onClick: function() {
			showDialogReport();
		}
	});
	subMenu4.addChild(subMenu4Item4);

	var menuItem4 = new dijit.PopupMenuItem({
		label: "Exportar",
		id: "menuExportar",
		iconClass: "exportIcon",
		popup: subMenu4
	});
	menu.addChild(menuItem4);

	/*** Menu Mais ***/
	var button = new dijit.form.DropDownButton({
		label: "Mais",
		name: "menuButton",
		dropDown: menu,
		id: "progButton"
	});
	dojo.byId("dropdownButtonContainer").appendChild(button.domNode);

	if (localStorage && localStorage.getItem('txt'))
		txt = localStorage.getItem("txt").split(',');
	carregarDadosVenues();
	} // Fecha initializeApplication()
});

function deCode(str) {
	var convertstr;
	convertstr = str.replace(/\&\#(\d+)\;/g, function(p1, p2) {
		return String.fromCharCode(p2)
	});
	return convertstr;
}

window.onbeforeunload = function() {
	if (linhasEditadas.length > 0)
		return deCode("As altera&#231;&#245;es ser&#227;o perdidas se voc&#234; sair desta p&#225;gina sem clicar no bot&#227;o Salvar.");
}

function showDialogGuia() {
	// set the content of the dialog:
	dlg_guia.attr("content", "<ul style='width: 415px; padding-left: 18px; margin-top: -10px'><li><p>Use sempre a ortografia, acentua&ccedil;&atilde;o e as letras mai&uacute;sculas e min&uacute;sculas corretas.</p></li><li><p>Em redes ou franquias, n&atilde;o &eacute; preciso adicionar um sufixo de local. Portanto, pode deixar &quot;Subway&quot; ou &quot;Loja Americanas&quot; (em vez de &quot;Subway - Ponta Verde&quot; ou &quot;Lojas Americanas - Iguatemi&quot;).</p></li><li><p>Os nomes dos locais devem respeitar a grafia original da marca sem abrevia&ccedil;&otilde;es (principalmente nomes de empresas).</p></li><li><p>Sempre use abrevia&ccedil;&otilde;es nos endere&ccedil;os: &quot;Av.&quot; em vez de &quot;Avenida&quot;, &quot;R.&quot; em vez de &quot;Rua&quot;, etc., observando as <a href='http://www.buscacep.correios.com.br' target='_blank'>diretrizes postais locais</a>.</p></li><li><p>O preenchimento da Rua transversal &eacute; opcional no Brasil.</p></li><li>Na Rua transversal tamb&eacute;m podem ser inclu&iacute;dos:<ul style='padding-left: 18px; margin-top: 4px'><li>Complemento, ponto de refer&ecirc;ncia ou via de acesso (quando relevante)</li><li>Bloco, piso, loja ou setor (para sublocais)</li><li>Regi&atilde;o Administrativa (RA) considerada bairro do Distrito Federal</li></ul></li><li><p>Os nomes de Estados devem ser abreviados: &quot;RJ&quot; em vez de &quot;Rio de Janeiro&quot;.</p></li><li><p>Em caso de d&uacute;vida sobre a cria&ccedil;&atilde;o e edi&ccedil;&atilde;o de locais no Foursquare&reg;, consulte as <a href='https://pt.foursquare.com/info/houserules' target='_blank'>regras da casa</a> e as <a href='https://support.foursquare.com/hc/en-us/sections/21181966510620-General-Swarm-Q-A' target='_blank'>perguntas frequentes sobre o Swarm</a>.</p></li></ul>");
	dlg_guia.show();
	dlg_guia.attr("style", "width: 455px;");
}

function showDialogComment(caller, comment) {
	for (i = 0; i < document.forms.length; i++)
		dojo.byId("result" + i).innerHTML = "";
	if (((caller == "saveButton") && (linhasEditadas.length > 0)) || (caller != "saveButton")) {
		dijit.byId('dlg_comment').show();
		if (typeof(comment) == "undefined")
			var comment = "";
		if ((dijit.byId("textareaComment").value) == "")
			dijit.byId("textareaComment").attr("value", comment);
	}
	actionButton = caller;
}

function showDialogEditField(field) {
	dijit.byId("selectEditField").attr("value", field);
	atualizarValorEdicaoMultipla(field);
	atualizarStatusEdicaoMultipla("");
	dijit.byId('dlg_editField').show();
}

function obterAlvosEdicaoMultipla(campo) {
	var checkboxes = dojo.query("input[name=selecao]");
	var campos = dojo.query("input[name=" + campo + "]");
	var haSelecionadas = dojo.query("input[name=selecao]:checked").length > 0;
	var alvos = [];

	for (var i = 0; i < document.forms.length; i++) {
		var checkbox = checkboxes[i];
		if (!checkbox || checkbox.disabled || (haSelecionadas && !checkbox.checked)) continue;

		var input = campos[i];
		if (!input) continue;

		var widget = dijit.getEnclosingWidget(input);
		if (!widget || widget.readOnly || widget.disabled) continue;
		alvos.push({ index: i, widget: widget });
	}

	return { alvos: alvos, haSelecionadas: haSelecionadas };
}

function atualizarValorEdicaoMultipla(campo) {
	var inputValor = dijit.byId("inputEditField");
	var selectCampo = dijit.byId("selectEditField");
	if (!inputValor || !selectCampo) return;

	var estado = obterAlvosEdicaoMultipla(campo);
	var valorComum = "";
	var valoresIguais = estado.alvos.length > 0;

	if (valoresIguais) {
		valorComum = estado.alvos[0].widget.textbox.value;
		for (var i = 1; i < estado.alvos.length; i++) {
			if (estado.alvos[i].widget.textbox.value !== valorComum) {
				valoresIguais = false;
				break;
			}
		}
	}

	inputValor.set("value", valoresIguais ? valorComum : "");
	inputValor.set("placeHolder", selectCampo.get("displayedValue"));
}

function atualizarStatusEdicaoMultipla(mensagem, status) {
	var statusNode = dojo.byId("editFieldStatus");
	if (!statusNode) return;
	statusNode.textContent = mensagem || "";
	statusNode.className = "bulk-edit-status" + (status ? " is-" + status : "");
}

/**
 * Exibe modal de exportação de URLs dos locais carregados
 * Permite escolher entre 3 formatos:
 * - URL Canônica (compartilhável)
 * - URL Padrão (curta, sempre disponível)
 * - Somente IDs (apenas os IDs de 24 caracteres)
 */
function showDialogExportUrls() {
	var indicesSelecionados = [];
	if (totalSelecionadas > 0) {
		// Se há seleção, considera apenas os selecionados
		for (var i = 0; i < document.forms.length; i++) {
			if (document.forms[i]["selecao"].checked) {
				indicesSelecionados.push(i);
			}
		}
	} else {
		// Se não há seleção, considera todos
		for (var i = 0; i < document.forms.length; i++) {
			indicesSelecionados.push(i);
		}
	}

	// Formatos disponíveis
	var FORMATO_CANONICA = 'canonica';
	var FORMATO_PADRAO = 'padrao';
	var FORMATO_IDS = 'ids';

	var formatoAtual = FORMATO_CANONICA;

	function obterIdsSelecionados() {
		return indicesSelecionados.map(function(i) {
			var campoVenue = document.forms[i] && document.forms[i].elements['venue'];
			return campoVenue ? campoVenue.value : '';
		}).filter(function(venueId) {
			return /^[a-f0-9]{24}$/i.test(venueId);
		});
	}

	/**
	 * Gera lista de URLs no formato escolhido
	 * @param {string} formato - 'canonica', 'padrao' ou 'ids'
	 * @returns {string} Lista formatada
	 */
	function gerarListaUrls(formato) {
		var lista = [];

		switch(formato) {
			case FORMATO_CANONICA:
				lista = obterIdsSelecionados().map(function(venueId) {
					return 'https://app.foursquare.com/share/venue/' + venueId;
				});
				break;

			case FORMATO_PADRAO:
				lista = obterIdsSelecionados().map(function(venueId) {
					return 'https://app.foursquare.com/v/' + venueId;
				});
				break;

			case FORMATO_IDS:
				lista = obterIdsSelecionados();
				break;
		}

		return lista.join('\n');
	}

	// Gera lista inicial
	var urlList = gerarListaUrls(formatoAtual);

	// Destroi widgets anteriores para evitar erros do Dojo
	if (dijit.byId('btnSaveUrls')) dijit.byId('btnSaveUrls').destroy();
	if (dijit.byId('btnCopyUrls')) dijit.byId('btnCopyUrls').destroy();
	if (dijit.byId('btnCancelUrls')) dijit.byId('btnCancelUrls').destroy();
	if (dijit.byId('tooltipCanonicaDisabled')) dijit.byId('tooltipCanonicaDisabled').destroy();

	// Contador de locais
	var numLocais = indicesSelecionados.length;
	var textoLocais = numLocais === 1 ? '1 local' : numLocais + ' locais';

	// Cria o conteúdo da modal
	var dialogContent = '<div style="padding: 10px;">' +
		'<p style="margin-top: 0;"><strong>Escolha o formato de exportação:</strong></p>' +
		'<div style="margin: 10px 0 20px 0;">' +
			'<div style="margin: 5px 0;">' +
				'<input type="radio" name="formatoExport" id="radioCanonica" value="' + FORMATO_CANONICA + '" ' +
					(formatoAtual === FORMATO_CANONICA ? 'checked' : '') + '> ' +
				'<label for="radioCanonica">' +
					'URL Canônica (compartilhável)' +
				'</label>' +
			'</div>' +
			'<div style="margin: 5px 0;">' +
				'<input type="radio" name="formatoExport" id="radioPadrao" value="' + FORMATO_PADRAO + '" ' +
					(formatoAtual === FORMATO_PADRAO ? 'checked' : '') + '> ' +
				'<label for="radioPadrao">URL Padrão (curta)</label>' +
			'</div>' +
			'<div style="margin: 5px 0;">' +
				'<input type="radio" name="formatoExport" id="radioIds" value="' + FORMATO_IDS + '"> ' +
				'<label for="radioIds">Somente IDs (24 caracteres)</label>' +
			'</div>' +
		'</div>' +
		'<p style="margin-bottom: 5px;">Lista de URLs dos ' + textoLocais + (totalSelecionadas > 0 ? ' selecionados' : '') + ':</p>' +
		'<textarea id="exportUrlsTextarea" readonly style="width: 100%; height: 300px; font-family: monospace; font-size: 12px; padding: 8px; box-sizing: border-box; resize: vertical;">' + urlList + '</textarea>' +
		'<div style="text-align: right; margin-top: 10px;">' +
		'<button id="btnSaveUrls" dojoType="dijit.form.Button">Salvar</button>' +
		'<button id="btnCopyUrls" dojoType="dijit.form.Button">Copiar</button>' +
		'<button id="btnCancelUrls" dojoType="dijit.form.Button">Cancelar</button>' +
		'</div>' +
		'</div>';

	dlg_export_urls.attr("content", dialogContent);
	dlg_export_urls.show();

	// Aguarda renderização do Dojo e conecta eventos
	setTimeout(function() {
		// Event listener para mudança de formato
		function atualizarFormatoExport() {
			var radioCanonica = dojo.byId("radioCanonica");
			var radioPadrao = dojo.byId("radioPadrao");
			var radioIds = dojo.byId("radioIds");

			if (radioCanonica && radioCanonica.checked) {
				formatoAtual = FORMATO_CANONICA;
			} else if (radioPadrao && radioPadrao.checked) {
				formatoAtual = FORMATO_PADRAO;
			} else if (radioIds && radioIds.checked) {
				formatoAtual = FORMATO_IDS;
			}

			// Atualiza textarea com novo formato
			var textarea = dojo.byId("exportUrlsTextarea");
			if (textarea) {
				urlList = gerarListaUrls(formatoAtual);
				textarea.value = urlList;
			}
		}

		// Conecta eventos de mudança nos radioboxes
		if (dojo.byId("radioCanonica")) {
			dojo.connect(dojo.byId("radioCanonica"), "onchange", atualizarFormatoExport);
		}
		if (dojo.byId("radioPadrao")) {
			dojo.connect(dojo.byId("radioPadrao"), "onchange", atualizarFormatoExport);
		}
		if (dojo.byId("radioIds")) {
			dojo.connect(dojo.byId("radioIds"), "onchange", atualizarFormatoExport);
		}

		// Event listeners para botões
		dojo.connect(dijit.byId("btnSaveUrls"), "onClick", function() {
			// Pega conteúdo atualizado do textarea
			var textoAtual = dojo.byId("exportUrlsTextarea").value;
			var blob = new Blob([textoAtual], {type: 'text/plain;charset=utf-8'});
			var link = document.createElement('a');
			link.href = window.URL.createObjectURL(blob);

			// Nome do arquivo baseado no formato
			var sufixo = '';
			if (formatoAtual === FORMATO_CANONICA) {
				sufixo = '_canonical';
			} else if (formatoAtual === FORMATO_IDS) {
				sufixo = '_ids';
			}

			link.download = 'venues_urls' + sufixo + '_' + new Date().getTime() + '.txt';
			link.click();
			window.URL.revokeObjectURL(link.href);
			debugInfo('📁 Arquivo exportado com sucesso!');

			// Fecha a modal após salvar
			dlg_export_urls.hide();
		});

		dojo.connect(dijit.byId("btnCopyUrls"), "onClick", function() {
			var textarea = document.getElementById('exportUrlsTextarea');
			textarea.select();
			document.execCommand('copy');
			alert('URLs copiadas para a área de transferência!');
			debugInfo('📋 URLs copiadas para o clipboard!');

			// Fecha a modal após copiar
			dlg_export_urls.hide();
		});

		dojo.connect(dijit.byId("btnCancelUrls"), "onClick", function() {
			dlg_export_urls.hide();
		});
	}, 100);
}

/**
 * Exibe modal com URL direta para carregar locais via load.php
 * URL pode incluir todos os locais ou apenas os selecionados
 * Detecta automaticamente ambiente (localhost vs produção)
 */
function showDialogExportDirectUrl() {
	var basePath = window.location.pathname.replace(/\/[^/]*$/, '');
	var baseUrl = window.location.origin + basePath;

	// Extrai IDs dos venues
	var arq = txt.slice(0);
	for (var i = 0; i < arq.length; i++) {
		arq[i] = arq[i].split("/").pop().slice(0, 24);
	}
	if (totalSelecionadas > 0)
		arq = removerNaoSelecionadas(arq, 0);

	// Gera a URL direta
	var directUrl = baseUrl + '/load.php?venues=' + arq.toString();

	// Destroi widgets anteriores para evitar erros do Dojo
	if (dijit.byId('btnCopyDirectUrl')) dijit.byId('btnCopyDirectUrl').destroy();
	if (dijit.byId('btnCancelDirectUrl')) dijit.byId('btnCancelDirectUrl').destroy();

	// Cria o conteúdo da modal
	var dialogContent = '<div style="padding: 10px;">' +
		'<p style="margin-top: 0;">URL direta para carregar ' + (totalSelecionadas > 0 ? 'os locais selecionados' : 'todos os locais') + ':</p>' +
		'<textarea id="exportDirectUrlTextarea" readonly style="width: 100%; height: 120px; font-family: monospace; font-size: 12px; padding: 8px; box-sizing: border-box; resize: vertical; word-wrap: break-word;">' + directUrl + '</textarea>' +
		'<div style="text-align: right; margin-top: 10px;">' +
		'<button id="btnCopyDirectUrl" dojoType="dijit.form.Button">Copiar</button>' +
		'<button id="btnCancelDirectUrl" dojoType="dijit.form.Button">Cancelar</button>' +
		'</div>' +
		'</div>';

	dlg_export_direct_url.attr("content", dialogContent);
	dlg_export_direct_url.show();

	// Aguarda renderização do Dojo e conecta eventos
	setTimeout(function() {
		dojo.connect(dijit.byId("btnCopyDirectUrl"), "onClick", function() {
			var textarea = document.getElementById('exportDirectUrlTextarea');
			textarea.select();
			document.execCommand('copy');
			alert('URL copiada para a área de transferência!');
			debugInfo('📋 URL direta copiada para o clipboard!');

			// Fecha a modal após copiar
			dlg_export_direct_url.hide();
		});

		dojo.connect(dijit.byId("btnCancelDirectUrl"), "onClick", function() {
			dlg_export_direct_url.hide();
		});
	}, 100);
}

/**
 * Função utilitária para criar tabela HTML estilizada
 * @param {Array} headers - Array de strings com os cabeçalhos das colunas
 * @param {Array} rows - Array de arrays com os dados das linhas
 * @param {string} tableId - ID único para a tabela
 * @returns {string} HTML da tabela formatada
 */
function createHtmlTable(headers, rows, tableId) {
	var tableHtml = '<table id="' + tableId + '" style="width: 100%; border-collapse: collapse; font-size: 12px; font-family: monospace;">' +
		'<thead>' +
		'<tr style="background-color: #f0f0f0; border-bottom: 2px solid #333;">';

	// Adiciona cabeçalhos
	for (var i = 0; i < headers.length; i++) {
		tableHtml += '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">' + headers[i] + '</th>';
	}

	tableHtml += '</tr></thead><tbody>';

	// Adiciona linhas de dados
	for (var i = 0; i < rows.length; i++) {
		tableHtml += '<tr style="border-bottom: 1px solid #ddd;">';
		for (var j = 0; j < rows[i].length; j++) {
			var cellValue = rows[i][j] || '';
			tableHtml += '<td style="padding: 8px; border: 1px solid #ddd;">' + cellValue + '</td>';
		}
		tableHtml += '</tr>';
	}

	tableHtml += '</tbody></table>';
	return tableHtml;
}

/**
 * Exibe modal com pré-visualização e opção de exportar dados em formato CSV
 * Permite exportar todos os locais ou apenas os selecionados
 * CSV usa separador ponto-e-vírgula (;) e inclui cabeçalhos
 */
function showDialogExportCsv() {
	// Prepara dados CSV
	var arq = [];
	var j = 0;
	for (var i = 0; i < csv.length; i++) {
		if (csv[i] != undefined)
			arq[j++] = csv[i].slice(0); // Copia o array
	}
	if (totalSelecionadas > 0)
		arq = removerNaoSelecionadas(arq, 1);

	if (arq.length === 0) {
		alert('Nenhum dado para exportar.');
		return;
	}

	// Destroi widgets anteriores para evitar erros do Dojo
	if (dijit.byId('btnSaveCsv')) dijit.byId('btnSaveCsv').destroy();
	if (dijit.byId('btnCancelCsv')) dijit.byId('btnCancelCsv').destroy();

	// Primeira linha são os cabeçalhos
	var headers = arq[0].map(function(header) {
		return header.replace(/"/g, ''); // Remove aspas dos cabeçalhos
	});

	// Restante são os dados
	var rows = [];
	for (var i = 1; i < arq.length; i++) {
		var row = arq[i].map(function(cell) {
			return cell.replace(/"/g, ''); // Remove aspas dos dados
		});
		rows.push(row);
	}

	// Cria a tabela HTML
	var tableHtml = createHtmlTable(headers, rows, 'csvPreviewTable');

	var dialogContent = '<div style="padding: 10px;">' +
		'<p style="margin-top: 0;">Pré-visualização do CSV' + (totalSelecionadas > 0 ? ' (apenas selecionados)' : '') + ':</p>' +
		'<div style="overflow-x: auto; max-height: 400px; overflow-y: auto; border: 1px solid #ddd;">' +
		tableHtml +
		'</div>' +
		'<div style="text-align: right; margin-top: 15px;">' +
		'<button id="btnSaveCsv" dojoType="dijit.form.Button">Salvar</button>' +
		'<button id="btnCancelCsv" dojoType="dijit.form.Button">Cancelar</button>' +
		'</div>' +
		'</div>';

	dlg_export_csv.attr("content", dialogContent);
	dlg_export_csv.show();

	// Aguarda renderização do Dojo e conecta eventos
	setTimeout(function() {
		dojo.connect(dijit.byId("btnSaveCsv"), "onClick", function() {
			// Gera o conteúdo CSV com separador de ponto-e-vírgula
			var csvContent = [];
			for (var i = 0; i < arq.length; i++) {
				csvContent.push(arq[i].join(";"));
			}

			var blob = new Blob([csvContent.join("\r\n")], {type: 'text/csv;charset=utf-8'});
			var link = document.createElement('a');
			link.href = window.URL.createObjectURL(blob);
			link.download = 'venues_export.csv';
			link.click();
			window.URL.revokeObjectURL(link.href);
			debugInfo('📁 Arquivo CSV exportado com sucesso!');

			// Fecha a modal após salvar
			dlg_export_csv.hide();
		});

		dojo.connect(dijit.byId("btnCancelCsv"), "onClick", function() {
			dlg_export_csv.hide();
		});
	}, 100);
}

/**
 * Exibe modal com relatório detalhado de edições realizadas
 * Inclui nome do local, ação, data/hora, ID e opcionalmente categorias e comentários
 * Permite salvar relatório em formato texto tabulado (.txt)
 */
function showDialogReport() {
	var COL_NAME = 0;
	var COL_ACTION = 1;
	var COL_DATETIME = 2;
	var COL_ID = 3;
	var COL_CATEGORIES = 4;
	var COL_COMMENTS = 5;
	var COL_CHAINS = 6;

	// Destroi widgets anteriores para evitar erros do Dojo
	if (dijit.byId('btnSaveReport')) dijit.byId('btnSaveReport').destroy();
	if (dijit.byId('btnCancelReport')) dijit.byId('btnCancelReport').destroy();

	// Verifica quais colunas devem ser exibidas
	var hasCategories = (json == "");
	var hasChains = !!(window.appBootstrap && window.appBootstrap.editChains);
	var hasComments = false;

	for (var i = 0; i < relatorio.length; i++) {
		if (relatorio[i][COL_NAME] == undefined)
			relatorio[i][COL_NAME] = "";
		if (relatorio[i][COL_CATEGORIES] == undefined)
			relatorio[i][COL_CATEGORIES] = "";
		if (relatorio[i][COL_CHAINS] == undefined)
			relatorio[i][COL_CHAINS] = "";
		if (relatorio[i][COL_COMMENTS] && relatorio[i][COL_COMMENTS].length > 0)
			hasComments = true;
	}

	// Prepara cabeçalhos
	var headers = ['Nome', 'Ação', 'Data', 'Hora', 'ID'];
	if (hasCategories)
		headers.push('Categorias');
	if (hasChains)
		headers.push('Redes');
	if (hasComments)
		headers.push('Comentários');

	// Prepara dados
	var rows = [];
	for (var i = 0; i < relatorio.length; i++) {
		var dateTime = relatorio[i][COL_DATETIME].split(' ');
		var date = dateTime[0] || '';
		var time = dateTime[1] || '';

		var row = [
			relatorio[i][COL_NAME] || '',
			relatorio[i][COL_ACTION],
			date,
			time,
			relatorio[i][COL_ID]
		];

		if (hasCategories)
			row.push(relatorio[i][COL_CATEGORIES] || '');
		if (hasChains)
			row.push(relatorio[i][COL_CHAINS] || '');
		if (hasComments) {
			var comment = relatorio[i][COL_COMMENTS] ? relatorio[i][COL_COMMENTS].replace(/(\r\n|\n|\r)/gm, " ").replace(/\s+/g, " ") : '';
			row.push(comment);
		}

		rows.push(row);
	}

	// Cria a tabela usando a função utilitária
	var tableHtml = createHtmlTable(headers, rows, 'reportTable');

	var dialogContent = '<div style="padding: 10px; overflow-x: auto;">' +
		tableHtml +
		'<div style="text-align: right; margin-top: 15px;">' +
		'<button id="btnSaveReport" dojoType="dijit.form.Button">Salvar</button>' +
		'<button id="btnCancelReport" dojoType="dijit.form.Button">Cancelar</button>' +
		'</div>' +
		'</div>';

	dlg_report.attr("content", dialogContent);
	dlg_report.show();

	// Aguarda renderização do Dojo e conecta eventos
	setTimeout(function() {
		dojo.connect(dijit.byId("btnSaveReport"), "onClick", function() {
			// Gera conteúdo em texto simples para download
			var textContent = 'Nome\tAção\tData\tHora\tID';
			if (hasCategories) textContent += '\tCategorias';
			if (hasChains) textContent += '\tRedes';
			if (hasComments) textContent += '\tComentários';
			textContent += '\n';

			for (var i = 0; i < relatorio.length; i++) {
				var dateTime = relatorio[i][COL_DATETIME].split(' ');
				var date = dateTime[0] || '';
				var time = dateTime[1] || '';

				textContent += (relatorio[i][COL_NAME] || '') + '\t' +
					relatorio[i][COL_ACTION] + '\t' +
					date + '\t' +
					time + '\t' +
					relatorio[i][COL_ID];

				if (hasCategories)
					textContent += '\t' + (relatorio[i][COL_CATEGORIES] || '');
				if (hasChains)
					textContent += '\t' + (relatorio[i][COL_CHAINS] || '');
				if (hasComments) {
					var comment = relatorio[i][COL_COMMENTS] ? relatorio[i][COL_COMMENTS].replace(/(\r\n|\n|\r)/gm, " ").replace(/\s+/g, " ") : '';
					textContent += '\t' + comment;
				}

				textContent += '\n';
			}

			var blob = new Blob([textContent], {type: 'text/plain;charset=utf-8'});
			var link = document.createElement('a');
			link.href = window.URL.createObjectURL(blob);
			link.download = 'relatorio_edicoes.txt';
			link.click();
			window.URL.revokeObjectURL(link.href);
			debugInfo('📁 Relatório exportado com sucesso!');

			// Fecha a modal após salvar
			dlg_report.hide();
		});

		dojo.connect(dijit.byId("btnCancelReport"), "onClick", function() {
			dlg_report.hide();
		});
	}, 100);
}

/**
 * Implementa navegação vertical com teclas UP/DOWN nos campos de input
 * Simula comportamento de planilha: DOWN vai para o mesmo campo na linha abaixo
 * Pula automaticamente campos desabilitados
 */
function setupVerticalNavigation() {
	const listContainer = dojo.byId("listContainer");
	if (!listContainer) return;

	/**
	 * Procura recursivamente o próximo campo habilitado na direção especificada
	 * @param {string} fieldName - Nome do campo a procurar
	 * @param {number} startIndex - Índice da linha inicial
	 * @param {number} direction - Direção da busca (1 para baixo, -1 para cima)
	 * @returns {HTMLElement|null} Próximo input habilitado ou null se não encontrar
	 */
	function findNextEnabledInput(fieldName, startIndex, direction) {
		const maxAttempts = 100; // Limite de segurança para evitar loops infinitos
		let attempts = 0;
		let currentIndex = startIndex + direction;

		while (attempts < maxAttempts) {
			const targetRow = dojo.byId("linha" + currentIndex);

			// Se não existe mais linha nesta direção, retorna null
			if (!targetRow) {
				return null;
			}

			// Procura o input com o mesmo name na linha
			const targetInput = dojo.query("input[name='" + fieldName + "'][type='text']", targetRow)[0];

			// Se encontrou o input e ele está habilitado, retorna
			if (targetInput && !targetInput.disabled) {
				return targetInput;
			}

			// Se o input está desabilitado, continua procurando na mesma direção
			currentIndex += direction;
			attempts++;
		}

		return null;
	}

	// Busca todos os inputs dentro das linhas (exceto hidden)
	dojo.query("section.row input[type='text']", listContainer).forEach(function(input) {
		// Conecta evento keydown no elemento DOM nativo
		dojo.connect(input, "keydown", function(e) {
			// Apenas processa se for UP_ARROW ou DOWN_ARROW
			if (e.keyCode !== dojo.keys.UP_ARROW && e.keyCode !== dojo.keys.DOWN_ARROW) {
				return;
			}

			// Previne o comportamento padrão (mover cursor dentro do input)
			dojo.stopEvent(e);

			// Identifica o campo atual
			const fieldName = input.name;

			// Encontra a linha (section) pai navegando pelo DOM
			let currentRow = input.parentNode;
			while (currentRow && currentRow.tagName !== 'SECTION') {
				currentRow = currentRow.parentNode;
			}
			if (!currentRow || !currentRow.id) return;

			// Extrai o índice da linha atual do ID (formato: "linha0", "linha1", etc)
			const currentIndex = parseInt(currentRow.id.replace("linha", ""), 10);

			// Determina a direção da navegação (1 = DOWN, -1 = UP)
			const direction = (e.keyCode === dojo.keys.DOWN_ARROW) ? 1 : -1;

			// Procura o próximo campo habilitado recursivamente
			const targetInput = findNextEnabledInput(fieldName, currentIndex, direction);

			if (targetInput) {
				// Move o foco para o campo de destino
				setTimeout(function() {
					targetInput.focus();
					targetInput.select();
				}, 10);
			}
		});
	});

	debugLog('⌨️ Navegação vertical (UP/DOWN arrows) ativada nos campos (pula desabilitados)');
}

function verificarAlteracao(textbox, i) {
	var index = csv[0].indexOf(textbox.name);
	// Obtém o valor original, removendo aspas se existirem
	var valorOriginal = csv[i + 1][index];
	if (typeof valorOriginal === 'string') {
		// Remove aspas do início e fim se existirem
		if (valorOriginal.startsWith('"') && valorOriginal.endsWith('"')) {
			valorOriginal = valorOriginal.slice(1, -1);
		}
	} else {
		valorOriginal = valorOriginal || '';
	}

	// Verifica se esta linha está sendo recarregada - se sim, ignora alterações
	var formElement = document.forms[i];
	if (formElement && formElement.getAttribute('data-reloading') === 'true') {
		return; // Ignora alterações durante reload
	}

	if (valorOriginal != textbox.value) {
		//console.info("changed (" + i + "): " + textbox.name + ", old value: " + valorOriginal + ", new value: " + textbox.value);
		csv[i + 1][index] = '"' + textbox.value + '"';
		dojo.byId("result" + i).innerHTML = "";
		var linhaElement = dojo.byId("linha" + i);
		if (linhaElement) dojo.removeClass(linhaElement, "error");
		marcarLinhaEditada(i);
		if (textbox.name == "venuell") {
			var latlng = textbox.value.replace(/ /g, "").split(",");

			// Atualiza marcador no mapa
			if (window.googleMaps && window.googleMaps.moveMarker) {
				const position = { lat: parseFloat(latlng[0]), lng: parseFloat(latlng[1]) };
				window.googleMaps.moveMarker(i, position);
			}
		}
		//console.debug(textbox.style);
		//var domNode = dijit.byId(textbox.id).domNode;
		//dojo.style(domNode, "background", "#FFFFE0");
	}
}

function atualizarItensMenuMais(i) {
	totalSelecionadas = dojo.query("input[name=selecao]:checked").length;
	(totalSelecionadas > 0) ? dijit.byId("menuSinalizar").setAttribute("disabled", false) : dijit.byId("menuSinalizar").setAttribute("disabled", true);
	(totalSelecionadas > 1) ? dijit.byId("menuItemSinalizarDuplicate").setAttribute("disabled", false) : dijit.byId("menuItemSinalizarDuplicate").setAttribute("disabled", true);
}

function editField(campo, valor) {
	var estado = obterAlvosEdicaoMultipla(campo);
	var alterados = 0;
	var index = csv[0].indexOf(campo);

	for (var alvoIndex = 0; alvoIndex < estado.alvos.length; alvoIndex++) {
		var alvo = estado.alvos[alvoIndex];
		if (alvo.widget.textbox.value === valor) continue;

		alvo.widget.set("value", valor);
		if (index !== -1 && csv[parseInt(alvo.index, 10) + 1]) {
			csv[parseInt(alvo.index, 10) + 1][index] = alvo.widget.get("value");
		}
		dojo.byId("result" + alvo.index).innerHTML = "";
		marcarLinhaEditada(alvo.index);
		alterados++;
	}

	var mensagem;
	if (alterados === 0) {
		mensagem = "Nenhum local precisou ser alterado.";
	} else {
		var campoLabel = dijit.byId("selectEditField").get("displayedValue");
		var escopo;
		if (estado.haSelecionadas) {
			escopo = alterados + (alterados === 1 ? " local selecionado" : " locais selecionados");
		} else {
			escopo = "todos os locais";
		}

		mensagem = valor === ""
			? campoLabel + " removido em " + escopo + "."
			: campoLabel + ' alterado para "' + valor + '" em ' + escopo + ".";
	}
	atualizarStatusEdicaoMultipla(mensagem, "success");
	} // Fecha initializeApplication()