dojo.require("dijit.Dialog");
dojo.require("dijit.form.Button");
dojo.require("dijit.form.TextBox");
dojo.require("dijit.Tooltip");
dojo.require("dijit.Menu");
dojo.require("dojo.cookie");

var DATA_VERSIONAMENTO = "20120924";
var MESES = new Array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");

var oauth_token = dojo.cookie("oauth_token");

var relatorio = new Array();

var total = 0;
var timer;
var categorias = new Array();

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

function atualizarResultado(i, imagem, dica) {
	var item = "result" + i;
	dojo.byId(item).innerHTML = imagem;
	if (dica != "")
		createTooltip(item, dica);
	total++;
	if (total == document.forms.length) {
		if (dijit.byId("flagButton"))
			dijit.byId("flagButton").setAttribute('disabled', false);
		else 
			dijit.byId("saveButton").setAttribute('disabled', false);
	}
}

function xmlhttpRequest(metodo, endpoint, dados, i) {
	var xmlhttp = new XMLHttpRequest();
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
					atualizarResultado(i, "<img src='img/ok.png' alt='" + xmlhttp.responseText + "' style='vertical-align: middle;'>", "<span style=\"font-size: 12px\">Editada com sucesso</span>");
					var name = "";
					if (document.forms[i]["name"] != undefined)
						name = document.forms[i]["name"].value;
					var categoryId = new Array();
					if (document.forms[i]["categoryId"] != undefined)
						categoryId = document.forms[i]["categoryId"].value.split(",", 3);
					if (acao == "edit")
						acao = "editada";
					else if (acao == "flag")
						acao = "sinalizada";
					var venueId = document.forms[i]["venue"].value;
					relatorio.push(new Array(name, acao, getDataHora(), venueId, recuperarNomesCategorias(categoryId, ","), (i + 1).toString()));
					dijit.byId("menuItemExportarRelatorio").setAttribute("disabled", false);
					dijit.byId("exportButton").setAttribute("disabled", false);
				} else if (metodo == "GET") {
					montarTabela(resposta);
					atualizarCategorias();
					console.info("Categorias recuperadas!");
					localStorage.setItem("categorias", JSON.stringify(resposta));
					var d = new Date();
					d.setHours(0, 0, 0, 0);
					dojo.cookie("data_categorias", d, { expires: 1 });
				}
			} else if (xmlhttp.status == 400) {
				clearTimeout(xmlhttpTimeout);
				atualizarResultado(i, "<img src='img/erro.png' alt='Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>", "<span style=\"font-size: 12px\">Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
			} else if (xmlhttp.status == 401) {
				clearTimeout(xmlhttpTimeout);
				atualizarResultado(i, "<img src='img/erro.png' alt='Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>", "<span style=\"font-size: 12px\">Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
			} else if (xmlhttp.status == 403) {
				clearTimeout(xmlhttpTimeout);
				atualizarResultado(i, "<img src='img/erro.png' alt='Erro 403: Forbidden'>", "<span style=\"font-size: 12px\">Erro 403: Forbidden, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
			} else if (xmlhttp.status == 404) {
				clearTimeout(xmlhttpTimeout);
				atualizarResultado(i, "<img src='img/erro.png' alt='Erro 404: Not Found'>", "<span style=\"font-size: 12px\">Erro 404: Not Found</span>");
			} else if (xmlhttp.status == 405) {
				clearTimeout(xmlhttpTimeout);
				atualizarResultado(i, "<img src='img/erro.png' alt='Erro 405: Method Not Allowed'>", "<span style=\"font-size: 12px\">Erro 405: Method Not Allowed</span>");
			} else if (xmlhttp.status == 409) {
				clearTimeout(xmlhttpTimeout);
				atualizarResultado(i, "<img src='img/erro.png' alt='Erro 409: Conflict'>", "<span style=\"font-size: 12px\">Erro 409: Conflict</span>");
			} else if (xmlhttp.status == 500) {
				clearTimeout(xmlhttpTimeout);
				atualizarResultado(i, "<img src='img/erro.png' alt='Erro 500: Internal Server Error'>", "<span style=\"font-size: 12px\">Erro 500: Internal Server Error</span>");
			} else {
				clearTimeout(xmlhttpTimeout);
				atualizarResultado(i, "<img src='img/erro.png' alt='Erro desconhecido: " + xmlhttp.status + "'>", "<span style=\"font-size: 12px\">Erro desconhecido: " + xmlhttp.status + "</span>");
			}
		}
	}
	//xmlhttp.open("POST", "https://api.foursquare.com/v2/venues/" + venue + "/edit", true);
	xmlhttp.open(metodo, endpoint, true);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.send(dados);
	var xmlhttpTimeout = setTimeout(function ajaxTimeout() {
		xmlhttp.abort();
		console.info(metodo + " abortado!");
		if (metodo == "POST")
			atualizarResultado(i, "<img src='img/erro.png' alt='Erro: Request Timed Out'>", "<span style=\"font-size: 12px\">Erro: Request Timed Out</span>");
	}, 60000);
	return false;
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

function montarTabela(resposta) {
	dojo.map(resposta.response.categories, dojo.hitch(this, function(category1) {
		categorias[category1.id] = {"nome": category1.name, "icone": category1.icon.prefix + "bg_32" + category1.icon.suffix};
		dojo.map(category1.categories, dojo.hitch(this, function(category2) {
			categorias[category2.id] = {"nome": category2.name, "icone": category2.icon.prefix + "bg_32" + category2.icon.suffix};
			if (category2.categories != "") {
				dojo.map(category2.categories, dojo.hitch(this, function(category3) {
					categorias[category3.id] = {"nome": category3.name, "icone": category3.icon.prefix + "bg_32" + category3.icon.suffix};
				}));
			}
		}));
	}));
}

function recuperarNomesCategorias(categoryId, separador) {
	var nomes = "";
	if (categoryId.length > 0) {
		for (j = 0; j < categoryId.length; j++)
			if (categoryId[j] in categorias)
				nomes += categorias[categoryId[j]].nome + separador;
		nomes = nomes.replace(/\s+$/, "").slice(0, -1);
	}
	return nomes;
}

function salvarCategoria(i) {
	categoryId = document.forms[i]["categoryId"].value.split(",", 3);
	if ((categoryId[0] != "") && (categoryId[0] != " ") && (categoryId[0] in categorias)) {
		dojo.byId("icone" + i).innerHTML = "<img id=catImg" + i + " src='" + categorias[categoryId[0]].icone + "' style='height: 22px; width: 22px; margin-left: 0px'>";
		createTooltip("catImg" + i, "<span style=\"font-size: 12px\">" + recuperarNomesCategorias(categoryId, ", ") + "</span>");
	} else {
		dojo.byId("icone" + i).innerHTML = "<img id=catImg" + i + " src='https://foursquare.com/img/categories_v2/none_bg_32.png' style='height: 22px; width: 22px; margin-left: 0px'>";
	}
}

function atualizarCategorias() {
	var totalLinhas = document.forms.length;
	var nomes;
	for (i = 0; i < totalLinhas; i++) {
		salvarCategoria(i);
	}
}

function salvarVenues() {
	total = 0;
	dijit.byId("saveButton").setAttribute("disabled", true);
	var venue, dados, categoryId, ll, elementName;
	console.info("Enviando dados...");
	var totalLinhas = document.forms.length;
	for (i = 0; i < totalLinhas; i++) {
		venue = document.forms[i]["venue"].value;
		dados = "oauth_token=" + oauth_token;
		//var form = dojo.formToObject("form" + (i + 1));
		//console.info(dojo.toJson(form, true));
		var totalColunas = document.forms[i].elements.length;
		for (j = 1; j < totalColunas; j++) {
			elementName = document.forms[i].elements[j].name;
			if ((elementName != "ll") && (elementName != "categoryId") &&
					((elementName == "name") || (elementName == "address") || (elementName == "crossStreet") || (elementName == "city") || (elementName == "state") || (elementName == "zip") || (elementName == "twitter") || (elementName == "phone") || (elementName == "url") || (elementName == "description")))
				dados += "&" + elementName + "=" + document.forms[i].elements[j].value.replace(/&/g, "%26");
			else if (elementName == "categoryId") {
				categoryId = document.forms[i]["categoryId"].value;
				if (categoryId != null && categoryId != "")
					dados += "&categoryId=" + document.forms[i]["categoryId"].value;
			} else if (elementName == "ll") {
				ll = document.forms[i]["ll"].value;
				if (ll != null && ll != "")
					dados += "&ll=" + document.forms[i]["ll"].value;
			}
		}
		dados += "&v=" + DATA_VERSIONAMENTO;
		//console.group("venue=" + venue + " (" + i + ")");
		//console.log(dados);
		//console.groupEnd();
		xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venue + "/edit", dados, i);
		dojo.byId("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";
	}
	console.info("Dados enviados!");
}

function sinalizarVenues(problema) {
	total = 0;
	dijit.byId("flagButton").setAttribute("disabled", true);
	var venue, dados;
	console.info("Enviando dados...");
	var totalLinhas = document.forms.length;
	for (i = 0; i < totalLinhas; i++) {
		venue = document.forms[i]["venue"].value;
		dados = "oauth_token=" + oauth_token + "&problem=" + problema + "&v=" + DATA_VERSIONAMENTO;
		//console.group("venue=" + venue + " (" + i + ")");
		//console.log(dados);
		//console.groupEnd();
		xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venue + "/flag", dados, i);
		dojo.byId("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";
	}
	console.info("Dados enviados!");
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

var dlg_guia;
dojo.addOnLoad(function() {
	// create the dialog:
	dlg_guia = new dijit.Dialog({
		title: "Guia de estilo",
		style: "width: 435px"
	});
	
	if (dojo.query('#dropdownButtonContainer1').length != 0) {
		var menu1 = new dijit.Menu({
			style: "display: none;"
		});
		var menuItem1 = new dijit.MenuItem({
			label: "Inappropriate",
			onClick: function() {
				sinalizarVenues("inappropriate");
			}
		});
		menu1.addChild(menuItem1);
		var menuItem2 = new dijit.MenuItem({
			label: "Doesn't exist",
			onClick: function() {
				sinalizarVenues("doesnt_exist");
			}
		});
		menu1.addChild(menuItem2);
		var button1 = new dijit.form.DropDownButton({
			label: "Flag",
			name: "menuButton",
			dropDown: menu1,
			id: "flagButton"
		});
		dojo.byId("dropdownButtonContainer1").appendChild(button1.domNode);
	}
	
	var menu2 = new dijit.Menu({
		style: "display: none;"
	});
	var menu2Item1 = new dijit.MenuItem({
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
			var COL_VENUE = 5;
			var hasName, hasCategory;
			var j = 0;
			for (i = 0; i < relatorio.length; i++) {
				if (relatorio[i][COL_NAME] == undefined)
				  relatorio[i][COL_NAME] = "";
				if (relatorio[i][COL_NAME].length > NAME_MAX_SIZE)
					NAME_MAX_SIZE = relatorio[i][COL_NAME].length;
				(relatorio[i][COL_NAME].length > 0) ? hasName = true : hasName = false;
				if (relatorio[i][COL_ACTION].length > ACTION_MAX_SIZE)
					ACTION_MAX_SIZE = relatorio[i][COL_ACTION].length;
				if (relatorio[i][COL_CATEGORIES] == undefined)
				  relatorio[i][COL_CATEGORIES] = "";
				if (relatorio[i][COL_CATEGORIES].length > CATEGORIES_MAX_SIZE)
					CATEGORIES_MAX_SIZE = relatorio[i][COL_CATEGORIES].length;
				(relatorio[i][COL_CATEGORIES].length > 0) ? hasCategory = true : hasCategory = false;
			}
			var html = new Array();
			html[0] = "<!DOCTYPE html><html><head><meta http-equiv=\"text/html; charset=iso-8859-1\"></head><body><pre>";
			if (hasName)
				html[1] = pad("name", NAME_MAX_SIZE + 1) + pad("action", ACTION_MAX_SIZE + 1) + pad("date", 11) + pad("time", 9) + pad("id", 25);
			else
				html[1] = "venue " + pad("action", ACTION_MAX_SIZE + 1) + pad("date", 11) + pad("time", 9) + pad("id", 25);
			if (hasCategory)
				html[1] += pad("categories", CATEGORIES_MAX_SIZE);
			var j = 2;
			for (i = 0; i < relatorio.length; i++) {
				if (hasName)
					html[j] = pad(relatorio[i][COL_NAME], NAME_MAX_SIZE + 1) + pad(relatorio[i][COL_ACTION], ACTION_MAX_SIZE + 1) + relatorio[i][COL_DATETIME] + " " + relatorio[i][COL_ID];
				else
					html[j] = pad(relatorio[i][COL_VENUE], 6) + pad(relatorio[i][COL_ACTION], ACTION_MAX_SIZE + 1) + relatorio[i][COL_DATETIME] + " " + relatorio[i][COL_ID];
				if (hasCategory)
					html[j] += " " + pad(relatorio[i][COL_CATEGORIES], CATEGORIES_MAX_SIZE + 1);
				j++;
			}
			html.push("</pre></body></html>");
			window.open("data:text/html;charset=iso-8859-1," + escape(html.join("\r\n")));
		}
	});
	menu2.addChild(menu2Item1);	 
	var button2 = new dijit.form.DropDownButton({
		label: "Exportar",
		name: "menuExportar",
		dropDown: menu2,
		disabled: true,
		id: "exportButton"
	});
	dojo.byId("dropdownButtonContainer2").appendChild(button2.domNode);
	
	if (dojo.query('#icone0').length != 0) {
		var d = new Date();
		d.setHours(0, 0, 0, 0);
		if ((!localStorage.getItem('categorias')) || (d > dojo.cookie("data_categorias")))
			carregarListaCategorias();
		else {
			var resposta = JSON.parse(localStorage.getItem('categorias'));
			montarTabela(resposta);
			atualizarCategorias();
			console.info("Categorias recuperadas do localStorage!");
		}
	}
});

function showDialogGuia() {
	// set the content of the dialog:
	dlg_guia.attr("content", "<ul><li><p>Use sempre a ortografia, acentua&ccedil;&atilde;o e as letras mai&uacute;sculas e min&uacute;sculas corretas.</p></li><li><p>Em redes ou venues com v&aacute;rios locais, n&atilde;o &eacute; preciso adicionar um sufixo de local. Portanto, pode deixar &quot;Subway&quot; ou &quot;Loja Americanas&quot; (em vez de &quot;Subway - Ponta Verde&quot; ou &quot;Lojas Americanas - Iguatemi&quot;).</p></li><li><p>Os nomes das venues devem respeitar o grafia original do lugar sem abrevia&ccedil;&otilde;es (principalmente nomes de empresas).</p></li><li><p>Sempre use abrevia&ccedil;&otilde;es nos endere&ccedil;os: &quot;Av.&quot; em vez de &quot;Avenida&quot;, &quot;R.&quot; em vez de &quot;Rua&quot;, etc., observando as <a href='http://www.buscacep.correios.com.br' target='_blank'>diretrizes postais locais</a>.</p></li><li>O preenchimento da Rua Cross &eacute; opcional e deve ser realizado da seguinte forma:<ul><li>R. Bela Cintra (para venues em uma esquina)</li><li>R. Bela Cintra x R. Haddock Lobo (para venues entre duas quadras)</li></ul><br></li><li>Na Rua Cross tamb&eacute;m podem ser inclu&iacute;dos:<ul><li>Bairro, complemento, ponto de refer&ecirc;ncia ou via de acesso (quando relevante)</li><li>Bloco, piso, loja ou setor (para subvenues)</li></ul></li><li><p>Os nomes de Estados devem ser abreviados: &quot;RJ&quot; em vez de &quot;Rio de Janeiro&quot;.</p></li><li><p>Em caso de d&uacute;vida sobre a cria&ccedil;&atilde;o e edi&ccedil;&atilde;o de venues no foursquare, consulte nossas <a href='https://pt.foursquare.com/info/houserules' target='_blank'>regras da casa</a> e as <a href='http://support.foursquare.com/forums/191151-venue-help' target='_blank'>perguntas frequentes sobre venues</a>.</p></li></ul>");
	dlg_guia.show();
}

function verificarAlteracao(textbox, i) {
	dojo.byId("result" + i).innerHTML = "";
	//console.info("changed: " + textbox.name + ", new value: " + textbox.value);
	if (textbox.name == "categoryId")
		salvarCategoria(i);
}