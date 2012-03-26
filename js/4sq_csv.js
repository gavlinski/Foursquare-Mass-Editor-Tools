dojo.require("dijit.Dialog");
dojo.require("dijit.form.Button");
dojo.require("dijit.form.ComboBox");
dojo.require("dijit.form.TextBox");
dojo.require("dijit.Tooltip");
var total = 0;
var timer;
function atualizarResultado(linha, imagem, dica) {
  document.getElementById(linha).innerHTML = imagem;
  if (dica != "")
    createTooltip(linha, dica);
  total++;
  if (total == document.forms.length) {
    dijit.byId("submitButton").setAttribute('disabled', false);
    if (timer)
    	clearTimeout(timer);
  }
}
function xmlhttpPost(venue, dados, i) {
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
      try {
        var resposta = JSON.parse(xmlhttp.responseText);
      } catch(err) {
        return false;
      }
      if (xmlhttp.status == 200)
        atualizarResultado("result" + i, "<img src='img/ok.png' alt='" + xmlhttp.responseText + "' style='vertical-align: middle;'>", "");
      else if (xmlhttp.status == 400)
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>", "<span style=\"font-size: 12px\">Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
      else if (xmlhttp.status == 401)
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>", "<span style=\"font-size: 12px\">Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
      else if (xmlhttp.status == 403)
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 403: Forbidden'>", "<span style=\"font-size: 12px\">Erro 403: Forbidden, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
      else if (xmlhttp.status == 404)
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 404: Not Found'>", "<span style=\"font-size: 12px\">Erro 404: Not Found</span>");
      else if (xmlhttp.status == 405)
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 405: Method Not Allowed'>", "<span style=\"font-size: 12px\">Erro 405: Method Not Allowed</span>");
      else if (xmlhttp.status == 409)
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 409: Conflict'>", "<span style=\"font-size: 12px\">Erro 409: Conflict</span>");
      else if (xmlhttp.status == 500)
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 500: Internal Server Error'>", "<span style=\"font-size: 12px\">Erro 500: Internal Server Error</span>");
      else
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro desconhecido: " + xmlhttp.status + "'>", "<span style=\"font-size: 12px\">Erro desconhecido: " + xmlhttp.status + "</span>");
    }
  }
  xmlhttp.open("POST", "https://api.foursquare.com/v2/venues/" + venue + "/edit", true);
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
function salvarVenues() {
  total = 0;
  dijit.byId("submitButton").setAttribute("disabled", true);
  var venue, dados, categoryId, ll;
  for (i = 0; i < document.forms.length; i++) {
    venue = document.forms[i]["venue"].value;
    dados = "oauth_token=" + oauth_token;
    //var form = dojo.formToObject("form" + (i + 1));
    //console.info(dojo.toJson(form, true));
    for (j = 1; j < document.forms[i].elements.length; j++) {
      if ((document.forms[i].elements[j].name != "ll") &&
          (document.forms[i].elements[j].name != "categoryId") &&
          ((document.forms[i].elements[j].name == "name")
           || (document.forms[i].elements[j].name == "address")
           || (document.forms[i].elements[j].name == "crossStreet")
           || (document.forms[i].elements[j].name == "city")
           || (document.forms[i].elements[j].name == "state")
           || (document.forms[i].elements[j].name == "zip")
           || (document.forms[i].elements[j].name == "twitter")
           || (document.forms[i].elements[j].name == "phone")
           || (document.forms[i].elements[j].name == "url")
           || (document.forms[i].elements[j].name == "description")))
        dados += "&" + document.forms[i].elements[j].name + "=" + document.forms[i].elements[j].value.replace(/&/g, "%26");
      else if (document.forms[i].elements[j].name == "categoryId") {
        categoryId = document.forms[i]["categoryId"].value;
        if (categoryId != null && categoryId != "")
          dados += "&categoryId=" + document.forms[i]["categoryId"].value;
      } else if (document.forms[i].elements[j].name == "ll") {
        ll = document.forms[i]["ll"].value;
        if (ll != null && ll != "")
          dados += "&ll=" + document.forms[i]["ll"].value;
      }
    }
    dados += "&v=20120325";
    //console.group("venue=" + venue + " (" + i + ")");
    //console.log(dados);
    //console.groupEnd();
		xmlhttpPost(venue, dados, i);
		document.getElementById("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";
  }
  //console.info("Dados enviados!");
  timer = setTimeout(function() {
  	dijit.byId("submitButton").setAttribute('disabled', false);
  }, 120000);
}
var dlg_guia;
dojo.addOnLoad(function() {
  // create the dialog:
  dlg_guia = new dijit.Dialog({
    title: "Guia de estilo",
    style: "width: 435px"
  });
});
function showDialog_guia() {
  // set the content of the dialog:
  dlg_guia.attr("content", "<ul><li><p>Use sempre a ortografia e as letras mai&uacute;sculas corretas.</p></li><li><p>Em redes ou venues com v&aacute;rios locais, n&atilde;o &eacute; preciso adicionar um sufixo de local. Portanto, pode deixar &quot;Starbucks&quot; ou &quot;Apple Store&quot; (em vez de &quot;Starbucks - Queen Anne&quot; ou &quot;Apple Store - Cidade Alta&quot;).</p></li><li><p>Sempre que poss&iacute;vel, use abrevia&ccedil;&otilde;es: &quot;Av.&quot; em vez de &quot;Avenida&quot;, &quot;R.&quot; em vez de &quot;Rua&quot;, etc.</p></li><li>A Rua Cross deve ser preenchida da seguinte forma:<ul><li>R. Bela Cintra (para venues em uma esquina)</li><li>R. Bela Cintra x R. Haddock Lobo (para venues entre duas quadras)</li></ul><br></li><li>Na Rua Cross tamb&eacute;m podem ser inclu&iacute;dos:<ul><li>Bairro, complemento, ponto de refer&ecirc;ncia ou via de acesso (quando relevante)</li><li>Bloco, piso, loja ou setor (para subvenues)</li></ul></li><li><p>Os nomes de Estados e prov&iacute;ncias devem ser abreviados.</p></li><li><p>Em caso de d&uacute;vida, formate os endere&ccedil;os das venues de acordo com as diretrizes postais locais.</p></li><li><p>Se tiver mais perguntas sobre a cria&ccedil;&atilde;o e edi&ccedil;&atilde;o de venues no foursquare, consulte nossas <a href='https://pt.foursquare.com/info/houserules' target='_blank'>regras da casa</a> e as <a href='http://support.foursquare.com/forums/191151-venue-help' target='_blank'>perguntas frequentes sobre venues</a>.</p></li></ul>");
  dlg_guia.show();
}
function verificarAlteracao(textbox, i) {
  if (textbox.oldvalue != " ") {
  	dojo.byId("result" + i).innerHTML = "";
  }
}