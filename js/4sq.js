dojo.require("dijit.ProgressBar");
dojo.require("dijit.Dialog");
dojo.require("dijit.form.Button");
dojo.require("dijit.form.CheckBox");
dojo.require("dijit.form.TextBox");
dojo.require("dijit.form.ToggleButton");
dojo.require("dijit.Tooltip");
dojo.require("dojo.data.ItemFileReadStore");
dojo.require("dijit.Tree");
dojo.require("dijit.Menu");
dojo.require("dojo.cookie");
dojo.require("dijit.form.Select");

var DATA_VERSIONAMENTO = "20230404";
var MESES = new Array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");

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

var oauth_token = dojo.cookie("oauth_token");
var txt = "";
var json = "";

var csv = [];
var relatorio = [];

var categorias = [];
var store = {};

var timer;

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
	if (timeout == false)
		linhasEditadas.splice(linhasEditadas.indexOf(i), 1);
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
}

function limparLinhasEditadas() {
	linhasEditadas = [];
	dijit.byId("saveButton").setAttribute('disabled', false);
	dijit.byId("reloadButton").setAttribute('disabled', false);
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
		}
	);
	var linhaEditada = linhasEditadas.indexOf(parseInt(i));
	if (linhaEditada != -1)
		linhasEditadas.splice(linhaEditada, 1);
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
						if (venuellOriginais[i] != document.forms[i]["venuell"].value)
							venuellOriginais[i] = document.forms[i]["venuell"].value;
						dica = "<span style=\"font-size: 12px\">Editada com sucesso</span>";
						atualizarEditadas(i, false);
						relatorio.push(new Array(document.forms[i]["name"].value, "editada", getDataHora(), document.forms[i]["venue"].value, dojo.byId("cna" + i).value, dijit.byId("textareaComment").value));
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
					console.info("✅ Venue " + (i + 1) + " recarregada com sucesso!");
				} else if ((metodo == "GET") && (acao == "load") && (resposta.response.venue != undefined)) {
					// Ação de load normal
					atualizarTabela(resposta.response.venue, i);
				} else if ((metodo == "GET") && (resposta.response.categories == undefined) && (resposta.response.venue != undefined)) {
					// Fallback para carregamento sem especificar ação
					atualizarTabela(resposta.response.venue, i);
				} else if (resposta.response.categories != undefined) {
					montarArvore(resposta);
					console.info("Categorias recuperadas!");
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
		console.info(metodo + " (" + i + ")" + " abortado!");
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

function atualizarCategorias(nomes, ids, icones) {
	dojo.byId("catsContainer").innerHTML = "";
	for (j = 0; j < nomes.length; j++)
		dojo.byId("catsContainer").innerHTML += "<div id='categoria" + (j + 1) + "' class='categoria' ondblclick=\"tornarCategoriaPrimaria('" + (j + 1) + "')\" onclick=\"removerCategoria('" + (j + 1) + "')\">" + nomes[j] + ",</div>";
	dojo.byId("catsContainer").innerHTML = dojo.byId("catsContainer").innerHTML.slice(0, -7) + "</div>";
	dojo.byId("catsIds").innerHTML = ids;
	dojo.byId("catsIcones").innerHTML = icones;
	//console.log(dojo.byId("catsIcones").innerHTML);
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
	atualizarCategorias(nomes, ids, icones);
	dojo.byId("venueIndex").innerHTML = i;
	dijit.byId("dlg_cats").show();
	dijit.byId("editAllCheckbox").attr("checked", false);
	dijit.byId("editAllCheckbox").attr("disabled", false);
}

function removerCategoria(i) {
	if (timer)
		clearTimeout(timer);
	timer = setTimeout(function remover() {
		//console.info('Remover a categoria ' + i);
		var nomes = [];
		var ids = "";
		var icones = "";
		if ((dojo.byId("categoria1") !== null) && (i != 1)) {
			nomes.push(dojo.byId("categoria1").innerHTML.replace(/,/gi, ""));
			ids += dojo.byId("catsIds").innerHTML.substr(0, 24) + ",";
			icones += dojo.byId("catsIcones").innerHTML.split(",", 1)[0] + ",";
		}
		if ((dojo.byId("categoria2") !== null) && (i != 2)) {
			nomes.push(dojo.byId("categoria2").innerHTML.replace(/,/gi, ""));
			ids += dojo.byId("catsIds").innerHTML.substr(25, 24) + ",";
			icones += dojo.byId("catsIcones").innerHTML.split(",", 2)[1] + ",";
		}
		if ((dojo.byId("categoria3") !== null) && (i != 3)) {
			nomes.push(dojo.byId("categoria3").innerHTML);
			ids += dojo.byId("catsIds").innerHTML.substr(50, 24) + ",";
			icones += dojo.byId("catsIcones").innerHTML.split(",", 3)[2] + ",";
		}
		atualizarCategorias(nomes, ids.slice(0, -1), icones.slice(0, -1));
	}, 250);
}

function tornarCategoriaPrimaria(i) {
	clearTimeout(timer);
	//console.info("Tornar a categoria " + i + " primaria");
	var nomes = [];
	var ids = "";
	var icones = "";
	nomes.push(dojo.byId("categoria" + i).innerHTML.replace(/,/gi, ""));
	if (i == 1) {
		ids += dojo.byId("catsIds").innerHTML.substr(0, 24) + ",";
		icones += dojo.byId("catsIcones").innerHTML.split(",", 1)[0] + ",";
	} else if (i == 2) {
		ids += dojo.byId("catsIds").innerHTML.substr(25, 24) + ",";
		icones += dojo.byId("catsIcones").innerHTML.split(",", 2)[1] + ",";
	} else if (i == 3) {
		ids += dojo.byId("catsIds").innerHTML.substr(50, 24) + ",";
		icones += dojo.byId("catsIcones").innerHTML.split(",", 3)[2] + ",";
	}
	if ((dojo.byId("categoria1") !== null) && (i != 1)) {
		nomes.push(dojo.byId("categoria1").innerHTML.replace(/,/gi, ""));
		ids += dojo.byId("catsIds").innerHTML.substr(0, 24) + ",";
		icones += dojo.byId("catsIcones").innerHTML.split(",", 1)[0] + ",";
	}
	if ((dojo.byId("categoria2") !== null) && (i != 2)) {
		nomes.push(dojo.byId("categoria2").innerHTML.replace(/,/gi, ""));
		ids += dojo.byId("catsIds").innerHTML.substr(25, 24) + ",";
		icones += dojo.byId("catsIcones").innerHTML.split(",", 2)[1] + ",";
	}
	if ((dojo.byId("categoria3") !== null) && (i != 3)) {
		nomes.push(dojo.byId("categoria3").innerHTML);
		ids += dojo.byId("catsIds").innerHTML.substr(50, 24) + ",";
		icones += dojo.byId("catsIcones").innerHTML.split(",", 3)[2] + ",";
	}
	atualizarCategorias(nomes, ids.slice(0, -1), icones.slice(0, -1));
}

function salvarCategorias() {
	var venueIndex, venueLoop;
	var nomes = "";
	if (dojo.byId("catsIds").innerHTML != "") {
		nomes = dojo.byId("categoria1").innerHTML;
		if (dojo.byId("categoria2") !== null) {
			nomes += dojo.byId("categoria2").innerHTML;
			if (dojo.byId("categoria3") !== null)
				nomes += dojo.byId("categoria3").innerHTML;
		}
		if (dijit.byId("editAllCheckbox").checked) {
			venueIndex = 0;
			venueLoop = document.forms.length - 1;
		} else {
			venueIndex = dojo.byId("venueIndex").innerHTML;
			venueLoop = venueIndex;
		}
		for (i = venueIndex; i <= venueLoop; i++) {
			if (dojo.query("input[name=selecao]")[i].disabled != true) {
				dojo.byId("cna" + i).value = nomes;
				dojo.byId("cid" + i).value = dojo.byId("catsIds").innerHTML;
				dojo.byId("cic" + i).value = dojo.byId("catsIcones").innerHTML;
				dojo.byId("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:editarCategorias(" + i + ")'><img id=catImg" + i + " src='" + dojo.byId("cic" + i).value.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'></a>";
				//var index = csv[0].indexOf("categoryId");
				//csv[parseInt(i) + 1][index] = dojo.byId("cid" + i).value;
				var categoryIds = dojo.byId("cid" + i).value;
				var categoriasAtuais = [];
				if (categorias[i].ids != undefined)
					categoriasAtuais = categorias[i].ids.split(",");
				var categoriasNovas = categoryIds.split(",");
				categoriasRemover = categoriasAtuais.filter(function(val) {
					return categoriasNovas.indexOf(val) == -1;
				});
				if (categoriasRemover.length > 0) {
					var index = csv[0].indexOf("removeCategoryIds");
					csv[parseInt(i) + 1][index] = '"' + categoriasRemover.toString() + '"';
				}
				if (categoriasAtuais[0] != categoriasNovas[0]) {
					var index = csv[0].indexOf("primaryCategoryId");
					csv[parseInt(i) + 1][index] = '"' + categoriasNovas[0] + '"';
				}
				try {
					var index = csv[0].indexOf("addCategoryIds");
					var addCategoryIds;
					if ((typeof categoriasNovas[1].ids != undefined) && (categoriasAtuais.indexOf(categoriasNovas[1]) == -1))
						addCategoryIds = categoriasNovas[1];
					if ((typeof categoriasNovas[2].ids != undefined) && (categoriasAtuais.indexOf(categoriasNovas[2]) == -1))
						addCategoryIds += "," + categoriasNovas[2];
					if (addCategoryIds != "")
						csv[parseInt(i) + 1][index] = '"' + addCategoryIds + '"';
				} catch(e) {
				}
				dojo.byId("result" + i).innerHTML = "";
				if (linhasEditadas.indexOf(parseInt(i)) == -1)
					linhasEditadas.push(parseInt(i));
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

function createTooltip(target_id, content) {
	var obj = dojo.byId('tt_' + target_id);
	if (obj != null)
		obj.parentNode.removeChild(obj);
	var tooltip = new dijit.Tooltip({
		connectId: [target_id],
		label: content
	});
	tooltip.domNode.id = 'tt_' + target_id;
	document.body.appendChild(tooltip.domNode);
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
	document.forms[i]["name"].value = venue.name;
	document.forms[i]["venuell"].value = (venue.location.lat + ', ' + venue.location.lng).replace(/undefined/gi, "0.0");
	linha = '"' + venue.id + '"';
	if (modo == DADOS_COMPLETOS) 
		linha += '&&' + '"' + categorias[i].ids + '"';
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
				linha += '&&"' + venue.location.address + '"';
				break;
			case "crossStreet":
				document.forms[i]["crossStreet"].value = venue.location.crossStreet;
				linha += '&&"' + venue.location.crossStreet + '"';
				break;
			case "neighborhood":
				document.forms[i]["neighborhood"].value = venue.location.neighborhood;
				linha += '&&"' + venue.location.neighborhood + '"';
				break;
			case "city":
				document.forms[i]["city"].value = venue.location.city;
				linha += '&&"' + venue.location.city + '"';
				break;
			case "state":
				document.forms[i]["state"].value = venue.location.state;
				linha += '&&"' + venue.location.state + '"';
				break;
			case "zip":
				document.forms[i]["zip"].value = venue.location.postalCode;
				linha += '&&"' + venue.location.postalCode + '"';
				break;
			case "parentId":
				var parentId;
				try {
					if (venue.parent)
						(venue.parent.id) ? parentId = venue.parent.id : parentId = "";
				} catch(e) {
					console.log(e);
				}
				document.forms[i]["parentId"].value = parentId;
				linha += '&&"' + parentId + '"';
				break;
			case "phone":
				document.forms[i]["phone"].value = venue.contact.phone;
				linha += '&&"' + venue.contact.phone + '"';
				break;
			case "url":
				document.forms[i]["url"].value = venue.url;
				linha += '&&"' + venue.url + '"';
				break;
			case "twitter":
				document.forms[i]["twitter"].value = venue.contact.twitter;
				linha += '&&"' + venue.contact.twitter + '"';
				break;
			case "facebook":
				document.forms[i]["facebook"].value = venue.contact.facebook;
				linha += '&&"' + venue.contact.facebook + '"';
				break;
			case "instagram":
				document.forms[i]["instagram"].value = venue.contact.instagram;
				linha += '&&"' + venue.contact.instagram + '"';
				break;
			case "venuell":
				//document.forms[i]["venuell"].value = (venue.location.lat + ', ' + venue.location.lng).replace(/undefined/gi, "0.0");
				linha += '&&"' + venue.location.lat + ', ' + venue.location.lng + '"';
				break;
			case "description":
				document.forms[i]["description"].value = venue.description;
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
					console.log(e);
				}
				document.forms[i]["menu"].value = menuUrl;
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
	
	// Verifica se esta linha foi marcada para recarregamento
	const isReloading = document.forms[i].getAttribute('data-reloading') === 'true';
	const permitirEdicao = (modo == DADOS_COMPLETOS) || isReloading;
	
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
	(modo == DADOS_COMPLETOS) ? csv[i + 1] = csv[i + 1].concat("", "", "", document.forms[i]["createdAt"].value + ";" + document.forms[i]["checkinsCount"].value + ";" + document.forms[i]["usersCount"].value + ";" + document.forms[i]["tipCount"].value + ";" + document.forms[i]["likesCount"].value + ";" + document.forms[i]["listedCount"].value + ";" + document.forms[i]["photosCount"].value + ";" + document.forms[i]["isClosed"].value + ";" + document.forms[i]["isPrivate"].value + ";" + document.forms[i]["isDeleted"].value) : csv[i + 1] = csv[i + 1].concat(document.forms[i]["checkinsCount"].value + ";" + document.forms[i]["usersCount"].value + ";" + document.forms[i]["tipCount"].value);
	if (totalCarregadas == document.forms.length - totalNaoCarregadas) {
		(modo == DADOS_COMPLETOS) ? csv[0] = csv[0].concat("createdAt;checkins;users;tips;likes;listed;photos;closed;private;deleted") : csv[0] = csv[0].concat("checkins;users;tips");
		if (dojo.query("input[name=selecao]:enabled").length == 0)
			dijit.byId("menuSelecionar").setAttribute('disabled', true);
	}
	window.locais[i] = [(i + 1) + ". " + venue.name, venue.location.lat, venue.location.lng];
	console.info("Venue " + i + " recuperada!");
	
	// Atualiza marcadores no mapa quando venue é carregada
	if (window.googleMaps && window.googleMaps.map) {
		window.googleMaps.updateMarkers(window.locais);
		console.info("Marcadores atualizados!");
	}
	
	if (totalCarregadas == (document.forms.length - totalNaoCarregadas)) {
		limparLinhasEditadas();
		console.info("Marcadores posicionados!");
		
		// Ativa navegação vertical após todos os campos estarem prontos
		setupVerticalNavigation();
	}
}

function montarArvore(resposta) {
	/*** Organiza em ordem alfabética o segundo, terceiro e quarto níveis das categorias ***/
	for (j = 0; j < resposta.response.categories.length; j++) {
		resposta.response.categories[j].categories.sort(function(el1, el2) {
			return compare(el1, el2, "name")
		});
		for (k = 0; k < resposta.response.categories[j].categories.length; k++) {
			if (resposta.response.categories[j].categories[k].categories.length > 1)
				resposta.response.categories[j].categories[k].categories.sort(function(el1, el2) {
					return compare(el1, el2, "name")
				});
			for (l = 0; l < resposta.response.categories[j].categories[k].categories.length; l++)
				if (resposta.response.categories[j].categories[k].categories[l].categories.length > 1)
					resposta.response.categories[j].categories[k].categories[l].categories.sort(function(el1, el2) {
						return compare(el1, el2, "name")
					});
		}
	}
	var restructuredData = dojo.map(resposta.response.categories, dojo.hitch(this, function categoriasPrimarias(category1) {
		var newCategory1 = {};
		newCategory1.id = category1.id;
		newCategory1.name = category1.name;
		newCategory1.icon = category1.icon.prefix + "bg_32" + category1.icon.suffix;
		newCategory1.children = dojo.map(category1.categories, dojo.hitch(this, function categoriasSecundarias(idPrefix, category2) {
			var newCategory2 = {};
			//newCategory2.id = idPrefix + "_" + category2.id;
			newCategory2.id = category2.id;
			newCategory2.name = category2.name;
			newCategory2.icon = category2.icon.prefix + "bg_32" + category2.icon.suffix;
			if (category2.categories != "") {
				newCategory2.children = dojo.map(category2.categories, dojo.hitch(this, function categoriasTerciarias(idPrefix, category3) {
					var newCategory3 = {};
					//newCategory3.id = idPrefix + "_" + category3.id;
					newCategory3.id = category3.id;
					newCategory3.name = category3.name;
					newCategory3.icon = category3.icon.prefix + "bg_32" + category3.icon.suffix;
					if (category3.categories != "") {
						newCategory3.children = dojo.map(category3.categories, dojo.hitch(this, function categoriasQuaternarias(idPrefix, category4) {
							var newCategory4 = {};
							//newCategory4.id = idPrefix + "_" + category4.id;
							newCategory4.id = category4.id;
							newCategory4.name = category4.name;
							newCategory4.icon = category4.icon.prefix + "bg_32" + category4.icon.suffix;
							return newCategory4;
						}, newCategory3.id));
					}
					return newCategory3;
				}, newCategory2.id));
			}
		return newCategory2;
		}, newCategory1.id));
	return newCategory1;
	}));
	JSONText = JSON.stringify(restructuredData);
	//console.log(JSONText);
	
	// Verifica se widget já existe - se sim, apenas atualiza o modelo ao invés de destruir
	var existingTree = dijit.byId("treeContainer");
	if (existingTree) {
		// Árvore já existe, atualiza apenas o store
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
		existingTree.set("model", treeModel);
		console.info("Árvore de categorias atualizada!");
		return;
	}
	
	// Cria árvore pela primeira vez
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
	var treeContainer = new dijit.Tree({
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
	treeContainer.startup();
}

function treeOnClick(item) {
	if (!item.root) {
		//console.log("Execute of node " + store.getLabel(item) + ", id=" + store.getValue(item, "id") + ", icon=" + store.getValue(item, "icon"));
		var i = 1;
		if (dojo.byId("categoria3") !== null)
			//console.warn("Limite maximo de categorias");
			return false;
		else if (((dojo.byId("categoria2") !== null) && (dojo.byId("categoria2").innerHTML.replace(/,/gi, "") == store.getLabel(item))) || ((dojo.byId("categoria1") !== null) && (dojo.byId("categoria1").innerHTML.replace(/,/gi, "") == store.getLabel(item))))
			//console.warn("Categoria repetida");
			return false;
		else if (dojo.byId("categoria2") !== null)
			i = 3;
		else if (dojo.byId("categoria1") !== null)
			i = 2;
		// Adiciona categoria
		if (i != 1) {
			dojo.byId("catsContainer").innerHTML = dojo.byId("catsContainer").innerHTML.slice(0, -6) + ",</div>"
			dojo.byId("catsIds").innerHTML += ",";
			dojo.byId("catsIcones").innerHTML += ",";
		}
		dojo.byId("catsContainer").innerHTML += "<div id='categoria" + i + "' class='categoria' ondblclick=\"tornarCategoriaPrimaria('" + i + "')\" onclick=\"removerCategoria('" + i + "')\">" + store.getLabel(item) + "</div>";
		dojo.byId("catsIds").innerHTML += store.getValue(item, "id");
		dojo.byId("catsIcones").innerHTML += store.getValue(item, "icon");
		return true;
	}
}

function carregarDadosVenues() {
	var venue;
	var linhas = document.forms.length;
	//if (localStorage && localStorage.getItem('venues'))
		//json = JSON.parse(localStorage.getItem('venues'));
	if (json == "") {
		console.info("Recuperando dados completos das venues...");
		modo = DADOS_COMPLETOS;
		for (i = 0; i < linhas; i++) {
			venue = document.forms[i]["venue"].value;
			xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/" + venue + "?oauth_token=" + oauth_token + "&v=" + DATA_VERSIONAMENTO, "load", null, i);
			dojo.byId("result" + i).innerHTML = "<img src='img/loading.gif' alt='Recuperando dados...'>";
		}
		var d = new Date();
		d.setHours(0, 0, 0, 0);
		if ((!localStorage.getItem('categorias')) || (d > dojo.cookie("data_categorias")))
			carregarListaCategorias();
		else {
			var resposta = JSON.parse(localStorage.getItem('categorias'));
			montarArvore(resposta);
			console.info("Categorias recuperadas do localStorage!");
		}
	} else {
		console.info("Recuperando dados parciais das venues...");
		modo = DADOS_PARCIAIS;
		for (i = 0; i < linhas; i++) {
			atualizarTabela(json.response.venues[i], i);
			//console.info("Venue " + i + " recuperada via JSON!");
		}
		console.info("Dados parciais das venues recuperados via JSON!");
	}
}

/**
 * Recarrega dados das venues da API Foursquare
 * - Se houver venues selecionadas, recarrega apenas as selecionadas
 * - Se não houver seleções, recarrega todas
 * - Verifica se há edições não salvas antes de proceder
 * - Sempre força carregamento completo (DADOS_COMPLETOS)
 * - Atualiza localStorage e marcadores do Google Maps
 */
function recarregarDadosVenues() {
	// Verifica se há edições não salvas
	if (linhasEditadas.length > 0) {
		var numEdicoes = linhasEditadas.length;
		var mensagem = "Você tem " + numEdicoes + (numEdicoes > 1 ? " edições" : " edição") + " não " + (numEdicoes > 1 ? "salvas" : "salva") + ". Recarregar descartará todas as alterações. Deseja continuar?";
		dojo.byId("reloadMessage").innerHTML = mensagem;
		
		// Configura ação do botão de confirmação
		var confirmButton = dijit.byId("confirmReloadButton");
		if (confirmButton._connections) {
			dojo.forEach(confirmButton._connections, dojo.disconnect);
		}
		confirmButton.onClick = function() {
			dijit.byId("dlg_reload").hide();
			executarRecarregamento();
		};
		
		dijit.byId("dlg_reload").show();
	} else {
		executarRecarregamento();
	}
}

/**
 * Executa o recarregamento efetivo dos dados
 */
function executarRecarregamento() {
	// Determina quais venues recarregar
	var venuesParaRecarregar = [];
	var checkboxesSelecionados = dojo.query("input[name=selecao]:checked");
	var totalSelecionadas = checkboxesSelecionados.length;
	
	if (totalSelecionadas > 0) {
		// Recarrega apenas as selecionadas
		checkboxesSelecionados.forEach(function(checkbox) {
			var venueIndex = parseInt(dijit.byId(checkbox.id).value);
			venuesParaRecarregar.push(venueIndex);
		});
		console.info("Recarregando " + totalSelecionadas + " venue" + (totalSelecionadas > 1 ? "s" : "") + " selecionada" + (totalSelecionadas > 1 ? "s" : "") + "...");
	} else {
		// Recarrega todas
		for (var i = 0; i < document.forms.length; i++) {
			venuesParaRecarregar.push(i);
		}
		console.info("Recarregando todas as " + venuesParaRecarregar.length + " venues...");
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
	
	// Limpa apenas o array de edições pendentes (mantém estado do botão Salvar)
	linhasEditadas = [];
	
	// Limpa cache JSON para forçar dados completos
	json = "";
	if (localStorage) {
		localStorage.removeItem('venues');
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
		console.info("Categorias recuperadas do localStorage!");
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
		xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/" + venue + "?oauth_token=" + oauth_token + "&v=" + DATA_VERSIONAMENTO, "reload", null, venueIndex);
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
				console.info("Atualizando marcadores do mapa...");
				window.googleMaps.updateMarkers(window.locais);
			}
			
			// Exibe mensagem final
			var mensagemFinal = "Recarregamento concluído!";
			if (totalNaoCarregadas > 0) {
				mensagemFinal += " (" + totalNaoCarregadas + " falha" + (totalNaoCarregadas > 1 ? "s" : "") + ")";
			}
			dijit.byId("dlg_save").set("title", mensagemFinal);
			
			// Habilita botões após conclusão
			dijit.byId("saveButton").setAttribute("disabled", false);
			dijit.byId("reloadButton").setAttribute("disabled", false);
			
			console.info("✅ " + mensagemFinal);
			
			// Fecha modal após 2 segundos
			setTimeout(function() {
				dijit.byId("dlg_save").hide();
			}, 2000);
		}
	}, 100);
}

function salvarVenues() {
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
	console.info("Enviando dados...");
	for (l = 0; l < totalParaSalvar; l++) {
		i = linhasEditadas[l];
		dados = "oauth_token=" + oauth_token;
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
		if (modo == DADOS_COMPLETOS) {
			var categoryIds = document.forms[i]["categoryId"].value;
			if ((categoryIds != null) && (categoryIds != "")) {
				var categoriasAtuais = [];
				if (categorias[i].ids != undefined)
					categoriasAtuais = categorias[i].ids.split(",");
				var categoriasNovas = categoryIds.split(",");
				categoriasRemover = categoriasAtuais.filter(function(val) {
					return categoriasNovas.indexOf(val) == -1;
				});
				if (categoriasRemover.length > 0)
					dados += "&removeCategoryIds=" + categoriasRemover.toString();
				if (categoriasAtuais[0] != categoriasNovas[0])
					dados += "&primaryCategoryId=" + categoriasNovas[0];
				try {
					if ((typeof categoriasNovas[1].ids != undefined) && (categoriasAtuais.indexOf(categoriasNovas[1]) == -1)) {
						dados += "&addCategoryIds=" + categoriasNovas[1];
						if ((typeof categoriasNovas[2].ids != undefined) && (categoriasAtuais.indexOf(categoriasNovas[2]) == -1))
							dados += "," + categoriasNovas[2];
					} else if ((typeof categoriasNovas[2].ids != undefined) && (categoriasAtuais.indexOf(categoriasNovas[2]) == -1)) {
						dados += "&addCategoryIds=" + categoriasNovas[2];
					}
				} catch(e) {
				}
			}
		}
		var comentario = encodeURIComponent(dijit.byId("textareaComment").value);
		if (comentario != "")
			dados += "&comment=" + comentario;
		dados += "&v=" + DATA_VERSIONAMENTO;
		venueId = document.forms[i]["venue"].value;
		console.group("venue=" + venueId + " (" + i + ")");
		console.log(dados);
		console.groupEnd();
		var acao = "proposeedit";
		xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venueId + "/" + acao, "edit", dados, i);
		dojo.byId("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";
	}
	console.info("Dados enviados!");
}

function sinalizarVenues(problema) {
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
	console.info("Enviando dados...");
	
	var l = 0;
	var linhas = linhasSelecionadas.slice(0);
	function sinalizar(delayTime) {
		setTimeout(function () {
			var venue, dados, acao, comentario;
			var i = linhas[l].value;
			venue = document.forms[i]["venue"].value;
			dados = "oauth_token=" + oauth_token;
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
			console.log(dados);
			console.groupEnd();
			xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venue + "/" + acao, "flag", dados, i);
			dojo.byId("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";

			l++;
			if (l < totalParaSinalizar) {
				sinalizar(delayTime);
			} else {
				console.info("Dados enviados!");
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
	console.info("Recuperando dados das categorias...");
	xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/categories" + "?oauth_token=" + oauth_token + "&v=" + DATA_VERSIONAMENTO, "load", null, null);
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
	console.log('📍 Marcador movido:', index, event);
	
	if (!event || !event.latLng) return;
	
	const marcador = index + 1;
	const novaPosicao = event.latLng.lat() + ', ' + event.latLng.lng();
	
	console.info('Marcador ' + marcador + ' movido: ' + novaPosicao);
	
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
	if (localStorage && localStorage.getItem('venues'))
		json = JSON.parse(localStorage.getItem('venues'));

	/*** Valida OAuth Token via SessionManager moderno ***/
	if (window.sessionManager) {
		// Usa o SessionManager moderno
		window.sessionManager.checkSessionStatus(false).then(isValid => {
			if (!isValid) {
				console.warn("🔒 Sessão expirada - redirecionando para login");
				if (window.confirm('Sessão expirada. Por favor, autentique-se novamente no Foursquare®.')) {
					window.location.href = 'index.php';
				}
				return;
			}
			// Continua com a inicialização normal
			initializeApplication();
		});
	} else {
		// Fallback para verificação legacy com cookie
		console.log("🔄 Usando validação legacy de token");
		
		// Verifica se o token existe via cookie
		const legacyToken = dojo.cookie("oauth_token");
		if (!legacyToken || legacyToken === "undefined" || legacyToken === "null") {
			console.warn("🔒 Token legacy não encontrado ou inválido");
			if (window.confirm('Sessão expirada. Por favor, autentique-se novamente no Foursquare®.')) {
				window.location.href = 'index.php';
			}
			return;
		}
		
		console.log("✅ Token legacy válido - continuando inicialização");
		// Continua com a inicialização normal
		initializeApplication();
	}
	
	function initializeApplication() {
	
	/*** Inicializar Google Maps ***/
	if (document.getElementById('mapa')) {
		const initMap = () => {
			console.log('🚀 Inicializando o mapa...');
			if (window.googleMaps) {
				window.googleMaps.initialize().then(() => {
					console.info("Mapa carregado!");
					// Se já há venues carregadas, posiciona os marcadores imediatamente
					if (window.locais && window.locais.length > 0) {
						window.googleMaps.updateMarkers(window.locais);
						console.info("Marcadores posicionados no mapa!");
					}
				}).catch(error => {
					console.error("Falha ao inicializar o mapa:", error);
				});
			} else {
				console.error("Erro crítico: googleMaps não está definido no momento da inicialização.");
			}
		};

		if (window.googleMaps) {
			console.log('🚀 Google Maps já estava pronto. Inicializando imediatamente.');
			initMap();
		} else {
			// Espera o evento que sinaliza que a API do Google Maps está pronta
			document.addEventListener('google-maps-ready', () => {
				console.log('🚀 Evento "google-maps-ready" recebido.');
				initMap();
			});
		}
	}
	
	/*** Autoajusta o tamanho inicial do mapa conforme largura da lista ***/
	// Só define largura inicial se não foi personalizada pelo usuário
	const mapaElement = dojo.byId('mapa');
	if (mapaElement && !mapaElement.style.width) {
		dojo.style("mapa", "width", dojo.byId('listContainer').offsetWidth.toString() + "px");
		console.log('📏 Largura inicial do mapa definida automaticamente');
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
				dijit.byId("editAllCheckbox").attr("checked", true);
				dijit.byId("editAllCheckbox").attr("disabled", true);
				//console.log(this.id + " clicado.");
			}
		});
		subMenu2.addChild(subMenu2Item);
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
	if (json == "") // modo == DADOS_COMPLETOS
		csv[0] = csv[0].concat("primaryCategoryId", "addCategoryIds", "removeCategoryIds");
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
	 * http://4sq.eliotools.site/load.php?venues=[venue_id1,venue_id2,...]
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
	dlg_guia.attr("content", "<ul style='width: 415px; padding-left: 18px; margin-top: -10px'><li><p>Use sempre a ortografia, acentua&ccedil;&atilde;o e as letras mai&uacute;sculas e min&uacute;sculas corretas.</p></li><li><p>Em redes ou venues com v&aacute;rios locais, n&atilde;o &eacute; preciso adicionar um sufixo de local. Portanto, pode deixar &quot;Subway&quot; ou &quot;Loja Americanas&quot; (em vez de &quot;Subway - Ponta Verde&quot; ou &quot;Lojas Americanas - Iguatemi&quot;).</p></li><li><p>Os nomes das venues devem respeitar o grafia original do lugar sem abrevia&ccedil;&otilde;es (principalmente nomes de empresas).</p></li><li><p>Sempre use abrevia&ccedil;&otilde;es nos endere&ccedil;os: &quot;Av.&quot; em vez de &quot;Avenida&quot;, &quot;R.&quot; em vez de &quot;Rua&quot;, etc., observando as <a href='http://www.buscacep.correios.com.br' target='_blank'>diretrizes postais locais</a>.</p></li><li><p>O preenchimento da Rua transversal &eacute; opcional no Brasil.</p></li><li>Na Rua transversal tamb&eacute;m podem ser inclu&iacute;dos:<ul style='padding-left: 18px; margin-top: 4px'><li>Complemento, ponto de refer&ecirc;ncia ou via de acesso (quando relevante)</li><li>Bloco, piso, loja ou setor (para subvenues)</li><li>Regi&atilde;o Administrativa (RA) considerada bairro do Distrito Federal</li></ul></li><li><p>Os nomes de Estados devem ser abreviados: &quot;RJ&quot; em vez de &quot;Rio de Janeiro&quot;.</p></li><li><p>Em caso de d&uacute;vida sobre a cria&ccedil;&atilde;o e edi&ccedil;&atilde;o de venues no Foursquare&reg;, consulte nossas <a href='https://pt.foursquare.com/info/houserules' target='_blank'>regras da casa</a> e as <a href='http://support.foursquare.com/forums/191151-venue-help' target='_blank'>perguntas frequentes sobre venues</a>.</p></li></ul>");
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
	dijit.byId('dlg_editField').show();
}

function showDialogExportUrls() {
	var arq = txt.slice(0);
	if (totalSelecionadas > 0)
		arq = removerNaoSelecionadas(arq, 0);
	
	// Limpa %0A e formata como lista de URLs
	var urlList = arq.map(function(url) {
		return url.replace(/%0A/g, '');
	}).join('\n');
	
	// Destroi widgets anteriores para evitar erros do Dojo
	if (dijit.byId('btnSaveUrls')) dijit.byId('btnSaveUrls').destroy();
	if (dijit.byId('btnCopyUrls')) dijit.byId('btnCopyUrls').destroy();
	if (dijit.byId('btnCancelUrls')) dijit.byId('btnCancelUrls').destroy();
	
	// Cria o conteúdo da modal
	var dialogContent = '<div style="padding: 10px;">' +
		'<p style="margin-top: 0;">Lista de URLs dos locais' + (totalSelecionadas > 0 ? ' selecionados' : '') + ':</p>' +
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
		dojo.connect(dijit.byId("btnSaveUrls"), "onClick", function() {
			var blob = new Blob([urlList], {type: 'text/plain;charset=utf-8'});
			var link = document.createElement('a');
			link.href = window.URL.createObjectURL(blob);
			link.download = 'venues_urls.txt';
			link.click();
			window.URL.revokeObjectURL(link.href);
		});
		
		dojo.connect(dijit.byId("btnCopyUrls"), "onClick", function() {
			var textarea = document.getElementById('exportUrlsTextarea');
			textarea.select();
			document.execCommand('copy');
			alert('URLs copiadas para a área de transferência!');
		});
		
		dojo.connect(dijit.byId("btnCancelUrls"), "onClick", function() {
			dlg_export_urls.hide();
		});
	}, 100);
}

function showDialogExportDirectUrl() {
	// Detecta o ambiente (localhost vs produção)
	var host = window.location.hostname === 'localhost' ? 'localhost' : '4sq.eliotools.site';
	var protocol = window.location.hostname === 'localhost' ? window.location.protocol : 'http:';
	var port = window.location.hostname === 'localhost' && window.location.port ? ':' + window.location.port : '';
	var baseUrl = protocol + '//' + host + port;
	
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
			
			dlg_export_csv.hide();
		});
		
		dojo.connect(dijit.byId("btnCancelCsv"), "onClick", function() {
			dlg_export_csv.hide();
		});
	}, 100);
}

function showDialogReport() {
	var COL_NAME = 0;
	var COL_ACTION = 1;
	var COL_DATETIME = 2;
	var COL_ID = 3;
	var COL_CATEGORIES = 4;
	var COL_COMMENTS = 5;
	
	// Destroi widgets anteriores para evitar erros do Dojo
	if (dijit.byId('btnSaveReport')) dijit.byId('btnSaveReport').destroy();
	if (dijit.byId('btnCancelReport')) dijit.byId('btnCancelReport').destroy();
	
	// Verifica quais colunas devem ser exibidas
	var hasCategories = (json == "");
	var hasComments = false;
	
	for (var i = 0; i < relatorio.length; i++) {
		if (relatorio[i][COL_NAME] == undefined)
			relatorio[i][COL_NAME] = "";
		if (relatorio[i][COL_CATEGORIES] == undefined)
			relatorio[i][COL_CATEGORIES] = "";
		if (relatorio[i][COL_COMMENTS] && relatorio[i][COL_COMMENTS].length > 0)
			hasComments = true;
	}
	
	// Prepara cabeçalhos
	var headers = ['Nome', 'Ação', 'Data', 'Hora', 'ID'];
	if (hasCategories)
		headers.push('Categorias');
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
		});
		
		dojo.connect(dijit.byId("btnCancelReport"), "onClick", function() {
			dlg_report.hide();
		});
	}, 100);
}

/**
 * Implementa navegação vertical com teclas UP/DOWN nos campos de input
 * Simula comportamento de planilha: DOWN vai para o mesmo campo na linha abaixo
 */
function setupVerticalNavigation() {
	const listContainer = dojo.byId("listContainer");
	if (!listContainer) return;
	
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
			
			// Calcula o índice da linha de destino
			const targetIndex = (e.keyCode === dojo.keys.DOWN_ARROW) ? currentIndex + 1 : currentIndex - 1;
			
			// Verifica se a linha de destino existe
			const targetRow = dojo.byId("linha" + targetIndex);
			if (!targetRow) return;
			
			// Encontra o input com o mesmo name na linha de destino
			const targetInput = dojo.query("input[name='" + fieldName + "'][type='text']", targetRow)[0];
			if (targetInput) {
				// Move o foco para o campo de destino
				setTimeout(function() {
					targetInput.focus();
					targetInput.select();
				}, 10);
			}
		});
	});
	
	console.log('⌨️ Navegação vertical (UP/DOWN arrows) ativada nos campos');
}

function verificarAlteracao(textbox, i) {
	var index = csv[0].indexOf(textbox.name);
	if (csv[i + 1][index].slice(1, -1) != textbox.value) {
		//console.info("changed (" + i + "): " + textbox.name + ", old value: " + csv[i + 1][index].slice(1, -1) + ", new value: " + textbox.value);
		csv[i + 1][index] = '"' + textbox.value + '"';
		dojo.byId("result" + i).innerHTML = "";
		if (linhasEditadas.indexOf(i) == -1)
			linhasEditadas.push(i);
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
	var inputId, index;
	for (i = 0; i <= document.forms.length - 1; i++) {
		if (dojo.query("input[name=selecao]")[i].disabled != true) {
			inputId = dojo.query("input[name=" + campo + "]")[i].id;
			if ((dijit.byId(inputId).textbox.value != valor) && (dijit.byId(inputId).readOnly == false) && (dijit.byId(inputId).disabled == false)) {
				dijit.byId(inputId).set("value", valor);
				//console.log(campo, dijit.byId(inputId).value);
				index = csv[0].indexOf(campo);
				csv[parseInt(i) + 1][index] = dijit.byId(inputId).value;
				dojo.byId("result" + i).innerHTML = "";
				if (linhasEditadas.indexOf(parseInt(i)) == -1)
					linhasEditadas.push(parseInt(i));
				//console.log(csv[parseInt(i) + 1][2], csv[parseInt(i) + 1][index]);
			}
		}
	}
	} // Fecha initializeApplication()