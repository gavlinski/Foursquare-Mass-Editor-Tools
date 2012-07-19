dojo.require("dijit.Dialog");
dojo.require("dijit.form.Button");
dojo.require("dijit.form.TextBox");
dojo.require("dijit.Tooltip");
dojo.require("dijit.Menu");

var total = 0;
var timer;
var categorias = new Array();

function atualizarResultado(linha, imagem, dica) {
  document.getElementById(linha).innerHTML = imagem;
  if (dica != "")
    createTooltip(linha, dica);
  total++;
  if (total == document.forms.length) {
    if (dijit.byId("flagButton"))
      dijit.byId("flagButton").setAttribute('disabled', false);
    else 
      dijit.byId("saveButton").setAttribute('disabled', false);
    if (timer)
    	clearTimeout(timer);
  }
}

function xmlhttpRequest(metodo, endpoint, dados, i) {
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
      try {
        var resposta = JSON.parse(xmlhttp.responseText);
      } catch(err) {
        return false;
      }
      if (xmlhttp.status == 200) {
      	if (metodo == "POST") {
					atualizarResultado("result" + i, "<img src='img/ok.png' alt='" + xmlhttp.responseText + "' style='vertical-align: middle;'>", "");
				} else if (metodo == "GET") {
				  //console.info("Categorias recuperadas!");
				  montarTabela(resposta);
          atualizarCategorias();
        }
			} else if (xmlhttp.status == 400) {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>", "<span style=\"font-size: 12px\">Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
      } else if (xmlhttp.status == 401) {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>", "<span style=\"font-size: 12px\">Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
      } else if (xmlhttp.status == 403) {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 403: Forbidden'>", "<span style=\"font-size: 12px\">Erro 403: Forbidden, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
      } else if (xmlhttp.status == 404) {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 404: Not Found'>", "<span style=\"font-size: 12px\">Erro 404: Not Found</span>");
      } else if (xmlhttp.status == 405) {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 405: Method Not Allowed'>", "<span style=\"font-size: 12px\">Erro 405: Method Not Allowed</span>");
      } else if (xmlhttp.status == 409) {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 409: Conflict'>", "<span style=\"font-size: 12px\">Erro 409: Conflict</span>");
      } else if (xmlhttp.status == 500) {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 500: Internal Server Error'>", "<span style=\"font-size: 12px\">Erro 500: Internal Server Error</span>");
      } else {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro desconhecido: " + xmlhttp.status + "'>", "<span style=\"font-size: 12px\">Erro desconhecido: " + xmlhttp.status + "</span>");
      }
    }
  }
  //xmlhttp.open("POST", "https://api.foursquare.com/v2/venues/" + venue + "/edit", true);
  xmlhttp.open(metodo, endpoint, true);
  xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xmlhttp.send(dados);
  return false;
}

function createTooltip(target_id, content) {
  var obj = document.getElementById('tt_' + target_id);
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
    categorias[category1.id] = {"nome": category1.name, "icone": category1.icon.prefix + category1.icon.sizes[0] + category1.icon.name};
    dojo.map(category1.categories, dojo.hitch(this, function(category2) {
      categorias[category2.id] = {"nome": category2.name, "icone": category2.icon.prefix + category2.icon.sizes[0] + category2.icon.name};
      if (category2.categories != "") {
        dojo.map(category2.categories, dojo.hitch(this, function(category3) {
          categorias[category3.id] = {"nome": category3.name, "icone": category3.icon.prefix + category3.icon.sizes[0] + category3.icon.name};
        }));
      }
    }));
  }));
}

function salvarCategoria(i) {
  categoryId = document.forms[i]["categoryId"].value.split(",", 3);
  if ((categoryId[0] != "") && (categoryId[0] != " ") && (categoryId[0] in categorias)) {
    document.getElementById("icone" + i).innerHTML = "<img id=catImg" + i + " src='" + categorias[categoryId[0]].icone + "' style='height: 22px; width: 22px; margin-left: 0px'>";
    nomes = "";
    for (j = 0; j < categoryId.length; j++)
      if (categoryId[j] in categorias)
        nomes += categorias[categoryId[j]].nome + ", ";
    createTooltip("catImg" + i, "<span style=\"font-size: 12px\">" + nomes.slice(0, -2) + "</span>");
  } else {
    document.getElementById("icone" + i).innerHTML = "<img id=catImg" + i + " src='https://foursquare.com/img/categories/none.png' style='height: 22px; width: 22px; margin-left: 0px'>";
  }
}

function atualizarCategorias() {
  var totalLinhas = document.forms.length;
  var categoryId = new Array();
  var nomes;
  for (i = 0; i < totalLinhas; i++) {
   salvarCategoria(i);
  }
}

function salvarVenues() {
  total = 0;
  dijit.byId("saveButton").setAttribute("disabled", true);
  var venue, dados, categoryId, ll, elementName;
  //console.info("Enviando dados...");
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
    dados += "&v=20120416";
    //console.group("venue=" + venue + " (" + i + ")");
    //console.log(dados);
    //console.groupEnd();
		xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venue + "/edit", dados, i);
		document.getElementById("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";
  }
  //console.info("Dados enviados!");
  timer = setTimeout(function() {
  	dijit.byId("saveButton").setAttribute('disabled', false);
  }, 120000);
}

function sinalizarVenues(problema) {
  total = 0;
  dijit.byId("flagButton").setAttribute("disabled", true);
  var venue, dados;
  //console.info("Enviando dados...");
  var totalLinhas = document.forms.length;
  for (i = 0; i < totalLinhas; i++) {
    venue = document.forms[i]["venue"].value;
    dados = "oauth_token=" + oauth_token + "&problem=" + problema + "&v=20120501";
    //console.group("venue=" + venue + " (" + i + ")");
    //console.log(dados);
    //console.groupEnd();
		xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venue + "/flag", dados, i);
		document.getElementById("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";
  }
  //console.info("Dados enviados!");
  timer = setTimeout(function() {
  	dijit.byId("flagButton").setAttribute('disabled', false);
  }, 120000);
}

function carregarListaCategorias() {
  //console.info("Recuperando dados das categorias...");
  xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/categories" + "?oauth_token=" + oauth_token + "&v=20120416", null, null);
}

var dlg_guia;
dojo.addOnLoad(function() {
  // create the dialog:
  dlg_guia = new dijit.Dialog({
    title: "Guia de estilo",
    style: "width: 435px"
  });
  if (dojo.query('#dropdownButtonContainer').length != 0) {
    var menu = new dijit.Menu({
      style: "display: none;"
    });
    var menuItem1 = new dijit.MenuItem({
      label: "Inappropriate",
      onClick: function() {
        sinalizarVenues("inappropriate");
      }
    });
    menu.addChild(menuItem1);
    var menuItem2 = new dijit.MenuItem({
      label: "Doesn't exist",
      onClick: function() {
        sinalizarVenues("doesnt_exist");
      }
    });
    menu.addChild(menuItem2);
    var button = new dijit.form.DropDownButton({
      label: "Flag",
      name: "menuButton",
      dropDown: menu,
      id: "flagButton"
    });
    dojo.byId("dropdownButtonContainer").appendChild(button.domNode);
  }
  if (dojo.query('#icone0').length != 0)
    carregarListaCategorias();
});

function showDialog_guia() {
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