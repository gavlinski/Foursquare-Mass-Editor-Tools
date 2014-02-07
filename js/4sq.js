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

var DATA_VERSIONAMENTO = "20130113";
var MESES = new Array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");

var modo;
var DADOS_COMPLETOS = 0;
var DADOS_PARCIAIS = 1;

var oauth_token = dojo.cookie("oauth_token");
var txt = "";
var json = "";

var csv = new Array();
var relatorio = new Array();

var categorias = new Array();
var store = {};

var timer;

var totalCarregadas = 0;
var totalNaoCarregadas = 0;

var linhasEditadas = new Array();
var totalParaSalvar = 0;
var totalEditadas = 0;

var linhasSelecionadas = new Array();
var totalSelecionadas = 0;
var totalParaSinalizar = 0;
var totalSinalizadas = 0;
var linhaVenueComMaisCheckins = 0;

var totalProgresso = 0;

var actionButton = "";

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
	dojo.byId(item).innerHTML = imagem;
	createTooltip(item, dica);
}

function atualizarEditadas(i) {
	linhasEditadas.splice(linhasEditadas.indexOf(i), 1);
	totalEditadas++;
	dijit.byId("saveProgress").update({maximum: totalParaSalvar, progress: totalEditadas});
	if (linhasEditadas.length > 1)
		dijit.byId("dlg_save").set("title", "Salvando " + linhasEditadas.length + " venues...");
	else if (linhasEditadas.length == 1)
		dijit.byId("dlg_save").set("title", "Salvando 1 venue...");
	else if (linhasEditadas.length == 0) {
		dijit.byId("saveButton").setAttribute('disabled', false);
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

function atualizarSinalizadas(i) {
	linhasSelecionadas.splice(linhasSelecionadas.indexOf(parseInt(i)), 1);
	totalSinalizadas++;
	dijit.byId("saveProgress").update({maximum: totalParaSinalizar, progress: totalSinalizadas});
	if (linhasSelecionadas.length > 1)
		dijit.byId("dlg_save").set("title", "Sinalizando " + linhasSelecionadas.length + " venues...");
	else if (linhasSelecionadas.length == 1)
		dijit.byId("dlg_save").set("title", "Sinalizando 1 venue...");
	else if (linhasSelecionadas.length == 0) {
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
		dijit.byId(dojo.query("input[name=selecao]")[linhaVenueComMaisCheckins].id).setChecked(false);
		if (dojo.query("input[name=selecao]:enabled").length == 0)
			dijit.byId("menuSelecionar").setAttribute('disabled', true);
		timer = setTimeout(function fecharPbSalvar() {
			dijit.byId("dlg_save").hide();
		}, 3000);
	}
}

function atualizarFalhas(metodo, i, acao) {
	if (metodo == "GET") {
		desabilitarLinha(i);
		totalNaoCarregadas++;
		/*** Se tiver sido a última venue a ser carregada ***/
		if (totalCarregadas == (document.forms.length - totalNaoCarregadas)) {
			limparLinhasEditadas();
			/*** Se nenhuma venue tiver sido carregada ***/
			if (totalNaoCarregadas == document.forms.length) {
				dijit.byId("menuSelecionar").setAttribute('disabled', true);
				dijit.byId("menuExportar").setAttribute('disabled', true);
			}
		}
	} else if (metodo == "POST") {
		if (acao == "edit")
			atualizarEditadas(i);
		else if (acao == "flag")
			atualizarSinalizadas(i);
	}
}

function limparLinhasEditadas() {
	linhasEditadas = [];
	dijit.byId("saveButton").setAttribute('disabled', false);
}

function desabilitarLinha(i) {
	dojo.query('#linha' + i + ' input').forEach(
		function(inputElem) {
			//console.log(inputElem);
			if ((inputElem.type == 'checkbox') && (dijit.byId(inputElem.id).get("checked") == true))
				dijit.byId(inputElem.id).setChecked(false);
			if ((inputElem.type == 'text') || (inputElem.type == 'checkbox')) //&& ((inputElem.value == ' ') || (inputElem.value == '')))
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

function xmlhttpRequest(metodo, endpoint, dados, i) {
	var xmlhttp = new XMLHttpRequest();
	var item = "result" + i;
	var imagem, dica;
	var acao = endpoint.substr(-4);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			try {
				var resposta = JSON.parse(xmlhttp.responseText);
			} catch(err) {
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
						dica = "<span style=\"font-size: 12px\">Editada com sucesso</span>";
						atualizarEditadas(i);
						relatorio.push(new Array(document.forms[i]["name"].value, "editada", getDataHora(), document.forms[i]["venue"].value, dojo.byId("cna" + i).value, dijit.byId("textarea").value));
						dijit.byId("menuItemExportarRelatorio").setAttribute("disabled", false);
					} else if (acao == "flag") {
						dica = "<span style=\"font-size: 12px\">Sinalizada com sucesso</span>";
						desabilitarLinha(i);
						atualizarSinalizadas(i);
						relatorio.push(new Array(document.forms[i]["name"].value, "sinalizada", getDataHora(), document.forms[i]["venue"].value, categorias[i].nomes, dijit.byId("textarea").value));
						dijit.byId("menuItemExportarRelatorio").setAttribute("disabled", false);
					}
					atualizarDicaResultado(item, imagem, dica);
				} else if ((metodo == "GET") && (resposta.response.categories == undefined)) {
					atualizarTabela(resposta.response.venue, i);
					console.info("Venue " + i + " recuperada!");
				} else if (resposta.response.categories != undefined) {
					/*** Organiza em ordem alfabética o segundo e terceiro níveis das categorias ***/
					for (j = 0; j < resposta.response.categories.length; j++) {
						resposta.response.categories[j].categories.sort(function(el1, el2) {
							return compare(el1, el2, "name")
						});
						for (k = 0; k < resposta.response.categories[j].categories.length; k++)
							if (resposta.response.categories[j].categories[k].categories.length > 1)
								resposta.response.categories[j].categories[k].categories.sort(function(el1, el2) {
									return compare(el1, el2, "name")
								});
					}
					montarArvore(resposta);
					console.info("Categorias recuperadas!");
					localStorage.setItem("categorias", JSON.stringify(resposta));
					var d = new Date();
					d.setHours(0, 0, 0, 0);
					dojo.cookie("data_categorias", d, { expires: 1 });
				}
			} else {
				clearTimeout(xmlhttpTimeout);
				atualizarFalhas(metodo, i, acao);
				switch (xmlhttp.status) {
				case 400:
					imagem = "<img src='img/erro.png' alt='Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>";
					dica = "<span style=\"font-size: 12px\">Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>";
					break;
				case 401:
					imagem = "<img src='img/erro.png' alt='Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>";
					dica = "<span style=\"font-size: 12px\">Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>";
					break;
				case 403:
					imagem = "<img src='img/erro.png' alt='Erro 403: Forbidden, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>";
					dica = "<span style=\"font-size: 12px\">Erro 403: Forbidden, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>";
					break;
				case 404:
					imagem = "<img src='img/erro.png' alt='Erro 404: Not Found'>";
					dica = "<span style=\"font-size: 12px\">Erro 404: Not Found</span>";
					break;
				case 405:
					imagem = "<img src='img/erro.png' alt='Erro 405: Method Not Allowed'>";
					dica = "<span style=\"font-size: 12px\">Erro 405: Method Not Allowed</span>";
					break;
				case 409:
					imagem  = "<img src='img/erro.png' alt='Erro 409: Conflict'>";
					dica = "<span style=\"font-size: 12px\">Erro 409: Conflict</span>";
					break;
				case 500:
					imagem = "<img src='img/erro.png' alt='Erro 500: Internal Server Error'>";
					dica = "<span style=\"font-size: 12px\">Erro 500: Internal Server Error</span>";
					break;
				default:
					imagem = "<img src='img/erro.png' alt='Erro desconhecido: " + xmlhttp.status + "'>";
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
			atualizarFalhas(metodo, i, acao);
			imagem = "<img src='img/erro.png' alt='Erro: Request Timed Out'>";
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
	var nomes = new Array();
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
}

function removerCategoria(i) {
	if (timer)
		clearTimeout(timer);
	timer = setTimeout(function remover() {
		//console.info('Remover a categoria ' + i);
		var nomes = new Array();
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
	var nomes = new Array();
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
				var index = csv[0].indexOf("categoryId")
				csv[parseInt(i) + 1][index] = dojo.byId("cid" + i).value;
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
	if (document.forms[i]["isClosed"].value == "true")
		dica += "<br><span style=\"color: #999999;\"><b>Now Closed</b></span>";
	try {
		if (document.forms[i]["address"].value != "") {
			dica += "<br>" + document.forms[i]["address"].value;
			if (document.forms[i]["crossStreet"].value != "")
				dica += " (" + document.forms[i]["crossStreet"].value + ")";
		} else if (document.forms[i]["crossStreet"].value != "") {
			dica += "<br>" + document.forms[i]["crossStreet"].value;
		}
	} catch(err) { }
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
	if (document.forms[i]["photosCount"].value != "")
		dica += "<img src=\"img/camera.png\" width=\"10\" height=\"9\" style=\"opacity: 0.4; margin-left: 7px\"> " + document.forms[i]["photosCount"].value;
	if (document.forms[i]["createdAt"].value)
		dica += "<br>Criada em " + document.forms[i]["createdAt"].value;
	dica += "</span></span>";
	return dica;
}

function atualizarTabela(venue, i) {
	totalCarregadas++;
	if (totalCarregadas == (document.forms.length - totalNaoCarregadas)) {
		limparLinhasEditadas();
	}
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
	var colunas = document.forms[i].elements.length - 9;
	var elementName;
	for (j = 2; j < colunas; j++) {
		elementName = document.forms[i].elements[j].name;
		//console.log(elementName);
		switch (elementName) {
		case "name":
			//document.forms[i]["name"].value = venue.name;
			if (totalCarregadas == 1) {
				if (j == 2)
					csv[0] = ["venue", "categoryId"];
				csv[0] = csv[0].concat("name");
			}
			if (j == 2)
				linha = venue.id + '&&' + categorias[i].ids;
			linha += '&&"' + venue.name + '"';
			break;
		case "address":
			document.forms[i]["address"].value = venue.location.address;
			if (totalCarregadas == 1) {
				if (j == 2)
					csv[0] = ["venue", "categoryId"];
				csv[0] = csv[0].concat("address");
			}
			if (j == 2)
				linha = venue.id + '&&' + categorias[i].ids;
			linha += '&&"' + venue.location.address + '"';
			break;
		case "crossStreet":
			document.forms[i]["crossStreet"].value = venue.location.crossStreet;
			if (totalCarregadas == 1) {
				if (j == 2)
					csv[0] = ["venue", "categoryId"];
				csv[0] = csv[0].concat("crossStreet");
			}
			if (j == 2)
				linha = venue.id + '&&' + categorias[i].ids;
			linha += '&&"' + venue.location.crossStreet + '"';
			break;
		case "city":
			document.forms[i]["city"].value = venue.location.city;
			if (totalCarregadas == 1) {
				if (j == 2)
					csv[0] = ["venue", "categoryId"];
				csv[0] = csv[0].concat("city");
			}
			if (j == 2)
				linha = venue.id + '&&' + categorias[i].ids;
			linha += '&&"' + venue.location.city + '"';
			break;
		case "state":
			document.forms[i]["state"].value = venue.location.state;
			if (totalCarregadas == 1) {
				if (j == 2)
					csv[0] = ["venue", "categoryId"];
				csv[0] = csv[0].concat("state");
			}
			if (j == 2)
				linha = venue.id + '&&' + categorias[i].ids;
			linha += '&&"' + venue.location.state + '"';
			break;
		case "zip":
			document.forms[i]["zip"].value = venue.location.postalCode;
			if (totalCarregadas == 1) {
				if (j == 2)
					csv[0] = ["venue", "categoryId"];
				csv[0] = csv[0].concat("zip");
			}
			if (j == 2)
				linha = venue.id + '&&' + categorias[i].ids;
			linha += '&&"' + venue.location.postalCode + '"';
			break;
		case "twitter":
			document.forms[i]["twitter"].value = venue.contact.twitter;
			if (totalCarregadas == 1) {
				if (j == 2)
					csv[0] = ["venue", "categoryId"];
				csv[0] = csv[0].concat("twitter");
			}
			if (j == 2)
				linha = venue.id + '&&' + categorias[i].ids;
			linha += '&&"' + venue.contact.twitter + '"';
			break;
		case "phone":
			document.forms[i]["phone"].value = venue.contact.phone;
			if (totalCarregadas == 1) {
				if (j == 2)
					csv[0] = ["venue", "categoryId"];
				csv[0] = csv[0].concat("phone");
			}
			if (j == 2)
				linha = venue.id + '&&' + categorias[i].ids;
			linha += '&&"' + venue.contact.phone + '"';
			break;
		case "url":
			document.forms[i]["url"].value = venue.url;
			if (totalCarregadas == 1) {
				if (j == 2)
					csv[0] = ["venue", "categoryId"];
				csv[0] = csv[0].concat("url");
			}
			if (j == 2)
				linha = venue.id + '&&' + categorias[i].ids;
			linha += '&&"' + venue.url + '"';
			break;
		case "description":
			document.forms[i]["description"].value = venue.description;
			if (totalCarregadas == 1) {
				if (j == 2)
					csv[0] = ["venue", "categoryId"];
				csv[0] = csv[0].concat("description");
			}
			if (j == 2)
				linha = venue.id + '&&' + categorias[i].ids;
			linha += '&&"' + venue.description + '"';
			if (venue.verified == true)
				dijit.byId(dojo.query("input[name=description]")[i].id).attr("readOnly", true);
			break;
		case "ll":
			document.forms[i]["ll"].value = (venue.location.lat + ', ' + venue.location.lng).replace(/undefined/gi, "0.0");
			if (totalCarregadas == 1) {
				if (j == 2)
					csv[0] = ["venue", "categoryId"];
				csv[0] = csv[0].concat("ll");
			}
			if (j == 2)
				linha = venue.id + '&&' + categorias[i].ids;
			linha += '&&"' + venue.location.lat + ', ' + venue.location.lng + '"';
			break;
		default:
			break;
		}
		if (document.forms[i].elements[j].value == "undefined")
			dijit.byId(dojo.query("input[name=" + elementName + "]")[i].id).set("value", "");
	}
	dojo.byId("result" + i).innerHTML = "";
	if ((venue.categories[0] != undefined) && (venue.categories[0].id == "4bf58dd8d48988d103941735"))
		desabilitarLinha(i);
	csv[i + 1] = linha.replace(/undefined/gi, "").split("&&");
	if (venue.categories[0] == undefined) {
		(modo == DADOS_COMPLETOS) ? dojo.byId("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:editarCategorias(" + i + ")'><img id=catImg" + i + " src='https://foursquare.com/img/categories_v2/none_bg_32.png' style='height: 22px; width: 22px; margin-left: 0px'></a>" : dojo.byId("icone" + i).innerHTML = "<img id=catImg" + i + " src='https://foursquare.com/img/categories_v2/none_bg_32.png' style='height: 22px; width: 22px; margin-left: 0px'>";
	} else if ((venue.categories[0].id == "4bf58dd8d48988d103941735") || (modo == DADOS_PARCIAIS)) {
		dojo.byId("icone" + i).innerHTML = "<img id=catLnk" + i + " src='" + categorias[i].icones.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'>";
		createTooltip("catLnk" + i, "<span style=\"font-size: 12px\">" + categorias[i].nomes.replace(/,/gi, ", ") + "</span>");
	} else if (venue.id != document.forms[i]["venue"].value) {
		desabilitarLinha(i);
	} else {
		dojo.byId("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:editarCategorias(" + i + ")'><img id=catImg" + i + " src='" + categorias[i].icones.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'></a>";
		createTooltip("catLnk" + i, "<span style=\"font-size: 12px\">" + categorias[i].nomes.replace(/,/gi, ", ") + "</span>");
	}
	if (venue.createdAt != undefined)
		document.forms[i]["createdAt"].value = formattedTime(venue.createdAt);
	document.forms[i]["checkinsCount"].value = venue.stats.checkinsCount;
	document.forms[i]["usersCount"].value = venue.stats.usersCount;
	document.forms[i]["tipCount"].value = venue.stats.tipCount;
	if (venue.photos != undefined)
	  document.forms[i]["photosCount"].value = venue.photos.count;
	(venue.closed == undefined) ? document.forms[i]["isClosed"].value = false : document.forms[i]["isClosed"].value = true;
	var dicaVenue = atualizarDicaVenue(i);
	createTooltip("venLnk" + i, dicaVenue);
	(modo == DADOS_COMPLETOS) ? csv[i + 1] = csv[i + 1].concat(document.forms[i]["createdAt"].value + ";" + document.forms[i]["checkinsCount"].value + ";" + document.forms[i]["usersCount"].value + ";" + document.forms[i]["tipCount"].value + ";" + document.forms[i]["photosCount"].value + ";" + document.forms[i]["isClosed"].value) : csv[i + 1] = csv[i + 1].concat(document.forms[i]["checkinsCount"].value + ";" + document.forms[i]["usersCount"].value + ";" + document.forms[i]["tipCount"].value);
	if (totalCarregadas == document.forms.length - totalNaoCarregadas) {
		(modo == DADOS_COMPLETOS) ? csv[0] = csv[0].concat("createdAt;checkins;users;tips;photos;closed") : csv[0] = csv[0].concat("checkins;users;tips");
		if (dojo.query("input[name=selecao]:enabled").length == 0)
			dijit.byId("menuSelecionar").setAttribute('disabled', true);
	}
}

function montarArvore(resposta) {
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
					return newCategory3;
				}, newCategory2.id));
			}
		return newCategory2;
		}, newCategory1.id));
	return newCategory1;
	}));
	//JSONText = JSON.stringify(restructuredData);
	//console.log(JSONText);
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
	if (localStorage && localStorage.getItem('venues'))
		json = JSON.parse(localStorage.getItem('venues'));
	if (json == "") {
		console.info("Recuperando dados completos das venues...");
		modo = DADOS_COMPLETOS;
		for (i = 0; i < linhas; i++) {
			venue = document.forms[i]["venue"].value;
			xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/" + venue + "?oauth_token=" + oauth_token + "&v=" + DATA_VERSIONAMENTO, null, i);
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

function salvarVenues() {
	totalEditadas = 0;
	totalParaSalvar = linhasEditadas.length;
	totalProgresso = 0;
	dijit.byId("saveProgress").update({maximum: totalParaSalvar, progress: totalEditadas});
	(totalParaSalvar > 1) ? dijit.byId("dlg_save").set("title", "Salvando " + totalParaSalvar + " venues...") : dijit.byId("dlg_save").set("title", "Salvando 1 venue...");
	dijit.byId("dlg_save").show();
	dijit.byId("saveButton").setAttribute("disabled", true);
	var i, venue, dados, ll, elementName, categoryId;
	console.info("Enviando dados...");
	for (l = 0; l < totalParaSalvar; l++) {
		i = linhasEditadas[l];
		dados = "oauth_token=" + oauth_token;
		var colunas = document.forms[i].elements.length - 8;
		for (j = 2; j < colunas; j++) {
			venue = document.forms[i]["venue"].value;
			elementName = document.forms[i].elements[j].name;
			if ((elementName != "ll") && (elementName != "categoryId") &&
					((elementName == "name") || (elementName == "address") || (elementName == "crossStreet") || (elementName == "city") || (elementName == "state") || (elementName == "zip") || (elementName == "twitter") || (elementName == "phone") || (elementName == "url")))
				dados += "&" + elementName + "=" + document.forms[i].elements[j].value.replace(/&/g, "%26");
			else if ((elementName == "description") && (document.forms[i]["description"].readOnly == false)) {
				var index = csv[0].indexOf("description")
				dados += "&description=" + csv[i + 1][index].slice(1, -1).replace(/&/g, "%26");
			} else if ((elementName == "categoryId") && (modo == DADOS_COMPLETOS)){
				categoryId = document.forms[i]["categoryId"].value;
				if ((categoryId != null) && (categoryId != ""))
					dados += "&categoryId=" + categoryId;
			} else if (elementName == "ll") {
				ll = document.forms[i]["ll"].value;
				if (ll != null && ll != "")
					dados += "&ll=" + document.forms[i]["ll"].value;
			}
		}
		dados += "&v=" + DATA_VERSIONAMENTO;
		console.group("venue=" + venue + " (" + i + ")");
		console.log(dados);
		console.groupEnd();
		xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venue + "/edit", dados, i);
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
	dijit.byId("saveProgress").update({maximum: totalParaSinalizar, progress: totalSinalizadas});
	(totalParaSinalizar > 1) ? dijit.byId("dlg_save").set("title", "Sinalizando " + totalParaSinalizar + " venues...") : dijit.byId("dlg_save").set("title", "Sinalizando 1 venue...");
	dijit.byId("dlg_save").show();
	dijit.byId("menuSinalizar").setAttribute("disabled", true);
	var venue, dados;
	console.info("Enviando dados...");
	for (l = 0; l < totalParaSinalizar; l++) {
		i = linhasSelecionadas[l].value;
		venue = document.forms[i]["venue"].value;
		dados = "oauth_token=" + oauth_token + "&problem=" + problema + "&v=" + DATA_VERSIONAMENTO;
		if (problema == "duplicate")
			dados += "&venueId=" + venueId;
		//console.group("venue=" + venue + " (" + i + ")");
		//console.log(dados);
		//console.groupEnd();
		xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venue + "/flag", dados, i);
		dojo.byId("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";
		}
	console.info("Dados enviados!");
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
	xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/categories" + "?oauth_token=" + oauth_token + "&v=" + DATA_VERSIONAMENTO, null, null);
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

var dlgGuia;
dojo.addOnLoad(function inicializar() {
	// create the dialog:
	dlg_guia = new dijit.Dialog({
		title: "Guia de estilo",
		style: "width: 435px"
	});
	var menu = new dijit.Menu({
		style: "display: none;"
	});
	
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
	
	menu.addChild(new dijit.MenuSeparator);

	var subMenu2 = new dijit.Menu({
		style: "display: none;"
	});
	var subMenu2Item1 = new dijit.MenuItem({
		label: "Duplicadas",
		id: "menuItemSinalizarDuplicate",
		onClick: function() {
			showDialogComment("duplicate", "duplicada");
		}
	});
	subMenu2.addChild(subMenu2Item1);
	subMenu2.addChild(new dijit.MenuSeparator);
	var subMenu2Item2 = new dijit.MenuItem({
		label: "N&atilde;o existe",
		id: "menuItemSinalizarDoesnt_exist",
		iconClass: "doesnt_existIcon",
		onClick: function() {
			//dojo.query("input[name=selecao]:checked").forEach("desabilitarLinha(dijit.byId(item.id).value)");
			showDialogComment("doesnt_exist", deCode("n&#227;o existe"));
		}
	});
	subMenu2.addChild(subMenu2Item2);
	var subMenu2Item3 = new dijit.MenuItem({
		label: "Fechada",
		id: "menuItemSinalizarClosed",
		iconClass: "closedIcon",
		onClick: function() {
			showDialogComment("closed", "fechada");
		}
	});
	subMenu2.addChild(subMenu2Item3);
	var subMenu2Item4 = new dijit.MenuItem({
		label: "Inadequada",
		id: "menuItemSinalizarInappropriate",
		iconClass: "inappropriateIcon",
		onClick: function() {
			showDialogComment("inappropriate", "inadequada");
		}
	});
	subMenu2.addChild(subMenu2Item4);
	var subMenu2Item5 = new dijit.MenuItem({
		label: "J&aacute; terminou",
		id: "menuItemSinalizarEvent_over",
		iconClass: "event_overIcon",
		onClick: function() {
			showDialogComment("event_over", deCode("j&#225; terminou"));
		}
	});
	subMenu2.addChild(subMenu2Item5);
	var menuItem2 = new dijit.PopupMenuItem({
		label: "Sinalizar",
		id: "menuSinalizar",
		iconClass: "flagIcon",
		disabled: true,
		popup: subMenu2
	});
	menu.addChild(menuItem2);
	
	var subMenu3 = new dijit.Menu({
		style: "display: none;"
	});
	var subMenu3Item1 = new dijit.MenuItem({
		label: "Arquivo CSV",
		id: "menuItemExportarCSV",
		onClick: function() {
			var arq = [];
			var j = 0;
			for (i = 0; i < csv.length; i++) {
				if (csv[i] != undefined)
					arq[j++] = csv[i].join(";");
			}
			window.location.href = "data:text/csv;charset=iso-8859-1," + escape(arq.join("\r\n"));
		}
	});
	subMenu3.addChild(subMenu3Item1);
	var subMenu3Item2 = new dijit.MenuItem({
		label: "Arquivo TXT",
		id: "menuItemExportarTXT",
		onClick: function() {
			var arq = txt.slice(0);
			var j = 0;
			var l = arq.length;
			linhasSelecionadas = [];
			if (totalSelecionadas > 0) {
				dojo.query("input[name=selecao]:checked").forEach("linhasSelecionadas.push(dijit.byId(item.id).value)");
				for (i = 0; i < l; i++) {
					if (linhasSelecionadas.indexOf(i.toString()) == -1)
						arq.splice(j, 1);
					else
						j++;
				}
			}
			window.open("data:text/plain;charset=utf-8," + arq.join("\r\n"));
		}
	});
	subMenu3.addChild(subMenu3Item2);
	var subMenu3Item3 = new dijit.MenuItem({
		label: "Relat&oacute;rio",
		id: "menuItemExportarRelatorio",
		disabled: true,
		onClick: function() {
			var NAME_MAX_SIZE = 4;
			var ACTION_MAX_SIZE = 7;
			var CATEGORIES_MAX_SIZE = 10;
			var COL_NAME = 0;
			var COL_ACTION = 1;
			var COL_DATETIME = 2;
			var COL_ID = 3;
			var COL_CATEGORIES = 4;
			var COL_COMMENTS = 5;
			var comments = false;
			var j = 0;
			for (i = 0; i < relatorio.length; i++) {
				if (relatorio[i][COL_NAME] == undefined)
				  relatorio[i][COL_NAME] = "";
				if (relatorio[i][COL_NAME].length > NAME_MAX_SIZE)
					NAME_MAX_SIZE = relatorio[i][COL_NAME].length;
				if (relatorio[i][COL_ACTION].length > ACTION_MAX_SIZE)
					ACTION_MAX_SIZE = relatorio[i][COL_ACTION].length;
				if (relatorio[i][COL_CATEGORIES] == undefined)
				  relatorio[i][COL_CATEGORIES] = "";
				if (relatorio[i][COL_CATEGORIES].length > CATEGORIES_MAX_SIZE)
					CATEGORIES_MAX_SIZE = relatorio[i][COL_CATEGORIES].length;
				if (relatorio[i][COL_COMMENTS].length > 0)
					comments = true;
			}
			var html = new Array();
			html[0] = "<!DOCTYPE html><html><head><meta http-equiv=\"text/html; charset=iso-8859-1\"></head><body><pre>";
			html[1] = pad("name", NAME_MAX_SIZE + 1) + pad("action", ACTION_MAX_SIZE + 1) + pad("date", 11) + pad("time", 9) + pad("id", 24);
			if ((json == "") && (comments))
				html[1] += pad(" categories", CATEGORIES_MAX_SIZE + 2) + "comments";
			else if ((json == "") && (!comments))
				html[1] += pad(" categories", CATEGORIES_MAX_SIZE + 1);
			else if (comments)
				html[1] += " comments";
			var j = 2;
			for (i = 0; i < relatorio.length; i++) {
				html[j] = pad(relatorio[i][COL_NAME], NAME_MAX_SIZE + 1) + pad(relatorio[i][COL_ACTION], ACTION_MAX_SIZE + 1) + relatorio[i][COL_DATETIME] + " " + relatorio[i][COL_ID];
				if ((json == "") && (comments))
					html[j] += " " + pad(relatorio[i][COL_CATEGORIES], CATEGORIES_MAX_SIZE + 1) + relatorio[i][COL_COMMENTS].replace(/(\r\n|\n|\r)/gm, " ").replace(/\s+/g, " ");
				else if ((json == "") && (!comments))
					html[j] += " " + pad(relatorio[i][COL_CATEGORIES], CATEGORIES_MAX_SIZE);
				else if (comments)
					html[j] += " " + relatorio[i][COL_COMMENTS].replace(/(\r\n|\n|\r)/gm, " ").replace(/\s+/g, " ");
				j++;
			}
			html.push("</pre></body></html>");
			var rel = escape(html.join("\r\n"));
			while (rel.indexOf("%u") !== -1)
				rel = rel.substring(0, rel.indexOf("%u")) + " " + rel.substring(rel.indexOf("%u") + 6);
			window.open("data:text/html;charset=iso-8859-1," + rel);
		}
	});
	subMenu3.addChild(subMenu3Item3);
	var menuItem3 = new dijit.PopupMenuItem({
		label: "Exportar",
		id: "menuExportar",
		iconClass: "exportIcon",
		popup: subMenu3
	});
	menu.addChild(menuItem3);	 
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
	dlg_guia.attr("content", "<ul><li><p>Use sempre a ortografia, acentua&ccedil;&atilde;o e as letras mai&uacute;sculas e min&uacute;sculas corretas.</p></li><li><p>Em redes ou venues com v&aacute;rios locais, n&atilde;o &eacute; preciso adicionar um sufixo de local. Portanto, pode deixar &quot;Subway&quot; ou &quot;Loja Americanas&quot; (em vez de &quot;Subway - Ponta Verde&quot; ou &quot;Lojas Americanas - Iguatemi&quot;).</p></li><li><p>Os nomes das venues devem respeitar o grafia original do lugar sem abrevia&ccedil;&otilde;es (principalmente nomes de empresas).</p></li><li><p>Sempre use abrevia&ccedil;&otilde;es nos endere&ccedil;os: &quot;Av.&quot; em vez de &quot;Avenida&quot;, &quot;R.&quot; em vez de &quot;Rua&quot;, etc., observando as <a href='http://www.buscacep.correios.com.br' target='_blank'>diretrizes postais locais</a>.</p></li><li>O preenchimento da Rua Cross &eacute; opcional e deve ser realizado da seguinte forma:<ul><li>R. Bela Cintra (para venues em uma esquina)</li><li>R. Bela Cintra x R. Haddock Lobo (para venues entre duas quadras)</li></ul><br></li><li>Na Rua Cross tamb&eacute;m podem ser inclu&iacute;dos:<ul><li>Bairro, complemento, ponto de refer&ecirc;ncia ou via de acesso (quando relevante)</li><li>Bloco, piso, loja ou setor (para subvenues)</li></ul></li><li><p>Os nomes de Estados devem ser abreviados: &quot;RJ&quot; em vez de &quot;Rio de Janeiro&quot;.</p></li><li><p>Em caso de d&uacute;vida sobre a cria&ccedil;&atilde;o e edi&ccedil;&atilde;o de venues no foursquare, consulte nossas <a href='https://pt.foursquare.com/info/houserules' target='_blank'>regras da casa</a> e as <a href='http://support.foursquare.com/forums/191151-venue-help' target='_blank'>perguntas frequentes sobre venues</a>.</p></li></ul>");
	dlg_guia.show();
}

function showDialogComment(caller, comment) {
	for (i = 0; i < document.forms.length; i++)
		dojo.byId("result" + i).innerHTML = "";
	if (((caller == "saveButton") && (linhasEditadas.length > 0)) || (caller != "saveButton")) {
		dijit.byId('dlg_comment').show();
		if (typeof(comment) == "undefined")
			var comment = "";
		if ((dijit.byId("textarea").value) == "")
			dijit.byId("textarea").attr("value", comment);
	}
	actionButton = caller;
}
//var node = dojo.byId("forms");
//dojo.connect(node, "onkeypress", function(e) {
	//if (e.keyCode == dojo.keys.DOWN_ARROW) {
		//document.forms[1].elements[1].focus();
		//dojo.stopEvent(e);
	//}
//});

function verificarAlteracao(textbox, i) {
	var index = csv[0].indexOf(textbox.name)
	if (csv[i + 1][index].slice(1, -1) != textbox.value) {
		//console.info("changed: " + textbox.name + ", old value: " + csv[i + 1][index].slice(1, -1) + ", new value: " + textbox.value);
		csv[i + 1][index] = '"' + textbox.value + '"';
		dojo.byId("result" + i).innerHTML = "";
		if (linhasEditadas.indexOf(i) == -1)
			linhasEditadas.push(i);
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
