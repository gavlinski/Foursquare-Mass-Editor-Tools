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

var total = 0;
var csv = new Array();
var txt = new Array();
var categorias = new Array();
var store = {};
var timer;
var linhasEditadas = new Array();
var totalLinhasEditadas = 0;
var totalLinhasParaSalvar = 0;
var totalLinhasSalvas = 0;

function atualizarResultado(linha, imagem, item, dica) {
  dojo.byId(linha).innerHTML = imagem;
  createTooltip(item, dica);
  linhasEditadas.splice(linha, 1);
  totalLinhasEditadas++;
  dijit.byId("saveProgress").update({maximum: totalLinhasParaSalvar, progress: totalLinhasEditadas});
  if (linhasEditadas.length == 0) {
    dijit.byId("saveButton").setAttribute('disabled', false);
    if (timer) {
    	clearTimeout(timer);
    	var title = totalLinhasSalvas + " de " + totalLinhasEditadas + " venue";
    	if (totalLinhasEditadas > 1)
        title += "s";
      if (totalLinhasSalvas == 0)
        title += " editadas";
    	else if (totalLinhasSalvas == 1)
    	  title += " editada com sucesso";
      else
        title += " editadas com sucesso";
      dijit.byId("dlg_save").set("title", title);
      timer = setTimeout(function fecharPbSalvar() {
  	    dijit.byId("dlg_save").hide();
      }, 5000);
    } 
  }
}

function desabilitarLinha(i, categoria) {
  dojo.query('#linha' + i + ' input').forEach(
    function(inputElem) {
      if (inputElem.type == 'text') //&& ((inputElem.value == ' ') || (inputElem.value == '')))
        //console.log(inputElem);
        dijit.byId(inputElem.id).setDisabled(true);
      if (categoria == 0)
        dojo.byId("icone" + i).innerHTML = "<img id=catImg" + i + " src='https://foursquare.com/img/categories_v2/none_bg_32.png' style='height: 22px; width: 22px; margin-left: 0px'>";
      else {
        dojo.byId("icone" + i).innerHTML = "<img id=catImg" + i + " src='" + dojo.byId("cic" + i).value.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'>";
        createTooltip("catImg" + i, "<span style=\"font-size: 12px\">" + dojo.byId("cna" + i).value.replace(/,/gi, ", ") + "</span>");
      } 
    }
  )
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
          dojo.byId("info" + i).innerHTML = dojo.byId("info" + i).innerHTML.replace(/%0A/gi, "");
          var dicaVenue = atualizarDicaVenue(i);
          totalLinhasSalvas++;
          atualizarResultado("result" + i, "<img src='img/ok.png' alt='" + xmlhttp.responseText + "' style='vertical-align: middle;'>", "venLnk" + i, dicaVenue);
        } else if ((metodo == "GET") && (resposta.response.categories == undefined)) {
          atualizarTabela(resposta, i);
        } else if (resposta.response.categories != undefined) {
          //console.info("Categorias recuperadas!");
          montarArvore(resposta);
        }
      } else if (xmlhttp.status == 400) {
      	if (metodo == "GET") {
          desabilitarLinha(i, 0);
      	}
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>", "result" + i, "<span style=\"font-size: 12px\">Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
      } else if (xmlhttp.status == 401) {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>", "result" + i, "<span style=\"font-size: 12px\">Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
      } else if (xmlhttp.status == 403) {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 403: Forbidden, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>", "result" + i, "<span style=\"font-size: 12px\">Erro 403: Forbidden, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
      } else if (xmlhttp.status == 404) {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 404: Not Found'>", "result" + i, "<span style=\"font-size: 12px\">Erro 404: Not Found</span>");
      } else if (xmlhttp.status == 405) {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 405: Method Not Allowed'>", "result" + i, "<span style=\"font-size: 12px\">Erro 405: Method Not Allowed</span>");
      } else if (xmlhttp.status == 409) {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 409: Conflict'>", "result" + i, "<span style=\"font-size: 12px\">Erro 409: Conflict</span>");
      } else if (xmlhttp.status == 500) {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro 500: Internal Server Error'>", "result" + i, "<span style=\"font-size: 12px\">Erro 500: Internal Server Error</span>");
      } else {
        atualizarResultado("result" + i, "<img src='img/erro.png' alt='Erro desconhecido: " + xmlhttp.status + "'>", "result" + i, "<span style=\"font-size: 12px\">Erro desconhecido: " + xmlhttp.status + "</span>");
      }
    }
  }
  xmlhttp.open(metodo, endpoint, true);
  xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xmlhttp.send(dados);
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
  var ids =  "";
  var icones = "";
  if (dojo.byId("cid" + i).value != "") {
    nomes = dojo.byId("cna" + i).value.split(",", 3);
    ids = dojo.byId("cid" + i).value;
    icones = dojo.byId("cic" + i).value;
  }
  atualizarCategorias(nomes, ids, icones);
  dojo.byId("venueIndex").innerHTML = i;
  dijit.byId("dlg_cats").show();
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
  var i = dojo.byId("venueIndex").innerHTML;
  var nomes = "";
  if (dojo.byId("catsIds").innerHTML != "") {
    nomes = dojo.byId("categoria1").innerHTML;
    if (dojo.byId("categoria2") !== null)
      nomes += dojo.byId("categoria2").innerHTML;
    if (dojo.byId("categoria3") !== null)
      nomes += dojo.byId("categoria3").innerHTML;
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
    dijit.byId('dlg_cats').hide();
    createTooltip("catLnk" + i, "<span style=\"font-size: 12px\">" + nomes.replace(/,/gi, ", ") + "</span>");
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
  var dia = date.getDate();
  if (dia < 10)
    dia = "0" + dia;
  var mes = new Array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");
  return dia + "/" + mes[date.getMonth()] + "/" + date.getFullYear();
}

function atualizarDicaVenue(i) {
  var dica = "<span style=\"font-size: 12px\"><b>" + document.forms[i]["name"].value + "</b>";
  try {
    if (document.forms[i]["address"].value != "")
      dica += "<br>" + document.forms[i]["address"].value;
  } catch(err) { }
  try {
    if (document.forms[i]["crossStreet"].value != "")
      dica += " (" + document.forms[i]["crossStreet"].value + ")";
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
      dica += document.forms[i]["state"].value;
      if (document.forms[i]["zip"].value != "")
        dica += " " + document.forms[i]["zip"].value;
    } else if (document.forms[i]["zip"].value != "") {
      dica += document.forms[i]["zip"].value;
    }
  } catch(err) { }
  dica += "<br><span style=\"color: #999999;\"><img src=\"img/maps.png\" width=\"8\" height=\"10\" style=\"opacity: 0.4\"> " + document.forms[i]["checkinsCount"].value + "<img src=\"img/person.png\" width=\"10\" height=\"10\" style=\"opacity: 0.4; margin-left: 7px\"> " + document.forms[i]["usersCount"].value + "<img src=\"img/comment.png\" width=\"10\" height=\"10\" style=\"opacity: 0.4; margin-left: 7px; margin-right: 1px\"> " + document.forms[i]["tipCount"].value + "<img src=\"img/camera.png\" width=\"10\" height=\"9\" style=\"opacity: 0.4; margin-left: 7px\"> " + document.forms[i]["photosCount"].value;
  dica += "<br>Criada em " + document.forms[i]["createdAt"].value + "</span></span>";
  return dica;
}

function atualizarTabela(resposta, i) {
  total++;
  if (total == document.forms.length) {
    /*** Necessário adicionar 1 segundo de atraso após término do carregamento ***/
    timer = setTimeout(function limparLinhasEditadas() {
      linhasEditadas = [];
      dijit.byId("saveButton").setAttribute('disabled', false);
    }, 1000);
  }
  var linha = "";
  categorias[i] = new Categoria();
  for (j = 0; j < resposta.response.venue.categories.length; j++) {
    categorias[i].ids += resposta.response.venue.categories[j].id + ",";
    categorias[i].nomes += resposta.response.venue.categories[j].name + ",";
    categorias[i].icones += resposta.response.venue.categories[j].icon.prefix + "bg_32" + resposta.response.venue.categories[j].icon.suffix + ",";
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
  document.forms[i]["name"].value = resposta.response.venue.name;
  var colunas = document.forms[i].elements.length - 8;
  var elementName;
  for (j = 2; j < colunas; j++) {
    elementName = document.forms[i].elements[j].name;
    //console.log(elementName);
    switch (elementName) {
    case "name":
      //document.forms[i]["name"].value = resposta.response.venue.name;
      if (total == 1) {
        if (j == 2)
          csv[0] = ["venue", "categoryId"];
        csv[0] = csv[0].concat("name");
      }
      if (j == 2)
        linha = resposta.response.venue.id + '&&' + categorias[i].ids;
      linha += '&&"' + resposta.response.venue.name + '"';
      break;
    case "address":
      document.forms[i]["address"].value = resposta.response.venue.location.address;
      if (total == 1) {
        if (j == 2)
          csv[0] = ["venue", "categoryId"];
        csv[0] = csv[0].concat("address");
      }
      if (j == 2)
        linha = resposta.response.venue.id + '&&' + categorias[i].ids;
      linha += '&&"' + resposta.response.venue.location.address + '"';
      break;
    case "crossStreet":
      document.forms[i]["crossStreet"].value = resposta.response.venue.location.crossStreet;
      if (total == 1) {
        if (j == 2)
          csv[0] = ["venue", "categoryId"];
        csv[0] = csv[0].concat("crossStreet");
      }
      if (j == 2)
        linha = resposta.response.venue.id + '&&' + categorias[i].ids;
      linha += '&&"' + resposta.response.venue.location.crossStreet + '"';
      break;
    case "city":
      document.forms[i]["city"].value = resposta.response.venue.location.city;
      if (total == 1) {
        if (j == 2)
          csv[0] = ["venue", "categoryId"];
        csv[0] = csv[0].concat("city");
      }
      if (j == 2)
        linha = resposta.response.venue.id + '&&' + categorias[i].ids;
      linha += '&&"' + resposta.response.venue.location.city + '"';
      break;
    case "state":
      document.forms[i]["state"].value = resposta.response.venue.location.state;
      if (total == 1) {
        if (j == 2)
          csv[0] = ["venue", "categoryId"];
        csv[0] = csv[0].concat("state");
      }
      if (j == 2)
        linha = resposta.response.venue.id + '&&' + categorias[i].ids;
      linha += '&&"' + resposta.response.venue.location.state + '"';
      break;
    case "zip":
      document.forms[i]["zip"].value = resposta.response.venue.location.postalCode;
      if (total == 1) {
        if (j == 2)
          csv[0] = ["venue", "categoryId"];
        csv[0] = csv[0].concat("zip");
      }
      if (j == 2)
        linha = resposta.response.venue.id + '&&' + categorias[i].ids;
      linha += '&&"' + resposta.response.venue.location.postalCode + '"';
      break;
    case "twitter":
      document.forms[i]["twitter"].value = resposta.response.venue.contact.twitter;
      if (total == 1) {
        if (j == 2)
          csv[0] = ["venue", "categoryId"];
        csv[0] = csv[0].concat("twitter");
      }
      if (j == 2)
        linha = resposta.response.venue.id + '&&' + categorias[i].ids;
      linha += '&&"' + resposta.response.venue.contact.twitter + '"';
      break;
    case "phone":
      document.forms[i]["phone"].value = resposta.response.venue.contact.phone;
      if (total == 1) {
        if (j == 2)
          csv[0] = ["venue", "categoryId"];
        csv[0] = csv[0].concat("phone");
      }
      if (j == 2)
        linha = resposta.response.venue.id + '&&' + categorias[i].ids;
      linha += '&&"' + resposta.response.venue.contact.phone + '"';
      break;
    case "url":
      document.forms[i]["url"].value = resposta.response.venue.url;
      if (total == 1) {
        if (j == 2)
          csv[0] = ["venue", "categoryId"];
        csv[0] = csv[0].concat("url");
      }
      if (j == 2)
        linha = resposta.response.venue.id + '&&' + categorias[i].ids;
      linha += '&&"' + resposta.response.venue.url + '"';
      break;
    case "description":
      document.forms[i]["description"].value = resposta.response.venue.description;
      if (total == 1) {
        if (j == 2)
          csv[0] = ["venue", "categoryId"];
        csv[0] = csv[0].concat("description");
      }
      if (j == 2)
        linha = resposta.response.venue.id + '&&' + categorias[i].ids;
      linha += '&&"' + resposta.response.venue.description + '"';
      if (resposta.response.venue.verified == true)
        dijit.byId(dojo.query("input[name=description]")[i].id).attr("readOnly", true);
      break;
    case "ll":
      document.forms[i]["ll"].value = (resposta.response.venue.location.lat + ', ' + resposta.response.venue.location.lng).replace(/undefined/gi, "0.0");
      if (total == 1) {
        if (j == 2)
          csv[0] = ["venue", "categoryId"];
        csv[0] = csv[0].concat("ll");
      }
      if (j == 2)
        linha = resposta.response.venue.id + '&&' + categorias[i].ids;
      linha += '&&"' + resposta.response.venue.location.lat + ', ' + resposta.response.venue.location.lng + '"';
      break;
    default:
      break;
    }
    if (document.forms[i].elements[j].value == "undefined") {
      dijit.byId(dojo.query("input[name=" + elementName + "]")[i].id).set("value", "");
    }
    dojo.byId("result" + i).innerHTML = "";
    if ((resposta.response.venue.categories[0] != undefined) && (resposta.response.venue.categories[0].id == "4bf58dd8d48988d103941735"))
      dijit.byId(dojo.query("input[name=" + elementName + "]")[i].id).setDisabled(true);
  }
  csv[i + 1] = linha.replace(/undefined/gi, "").split("&&");
  txt[i + 1] = resposta.response.venue.id + '%0A';
  if (resposta.response.venue.categories[0] == undefined) {
    dojo.byId("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:editarCategorias(" + i + ")'><img id=catImg" + i + " src='https://foursquare.com/img/categories_v2/none_bg_32.png' style='height: 22px; width: 22px; margin-left: 0px'></a>";
  } else if (resposta.response.venue.categories[0].id == "4bf58dd8d48988d103941735") {
    dojo.byId("icone" + i).innerHTML = "<img id=catLnk" + i + " src='" + categorias[i].icones.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'>";
    createTooltip("catLnk" + i, "<span style=\"font-size: 12px\">" + categorias[i].nomes.replace(/,/gi, ", ") + "</span>");
  } else {
    dojo.byId("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:editarCategorias(" + i + ")'><img id=catImg" + i + " src='" + categorias[i].icones.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'></a>";
    createTooltip("catLnk" + i, "<span style=\"font-size: 12px\">" + categorias[i].nomes.replace(/,/gi, ", ") + "</span>");
  }
  document.forms[i]["createdAt"].value = formattedTime(resposta.response.venue.createdAt);
  document.forms[i]["checkinsCount"].value = resposta.response.venue.stats.checkinsCount;
  document.forms[i]["usersCount"].value = resposta.response.venue.stats.usersCount;
  document.forms[i]["tipCount"].value = resposta.response.venue.stats.tipCount;
  document.forms[i]["photosCount"].value = resposta.response.venue.photos.count;
  var dicaVenue = atualizarDicaVenue(i);
  createTooltip("venLnk" + i, dicaVenue);
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

function carregarVenues() {
  var venue;
  //console.info("Recuperando dados das venues...");
  var linhas = document.forms.length;
  for (i = 0; i < linhas; i++) {
    venue = document.forms[i]["venue"].value;
    xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/" + venue + "?oauth_token=" + oauth_token + "&v=20120722", null, i);
    dojo.byId("result" + i).innerHTML = "<img src='img/loading.gif' alt='Recuperando dados...'>";
  }
  //console.info("Venues recuperadas!");
}

function salvarVenues() {
  for (i = 0; i < document.forms.length; i++)
    dojo.byId("result" + i).innerHTML = "";
  if (linhasEditadas.length > 0) {
    totalLinhasEditadas = 0;
    totalLinhasSalvas = 0;
    dijit.byId("saveProgress").update({maximum: linhasEditadas.length, progress: totalLinhasEditadas});
    dijit.byId("dlg_save").set("title", "Salvando venues...");
    dijit.byId("dlg_save").show();
    dijit.byId("saveButton").setAttribute("disabled", true);
    var venue, dados, ll, elementName;
    //console.info("Enviando dados...");
    totalLinhasParaSalvar = linhasEditadas.length;
    for (l = 0; l < totalLinhasParaSalvar; l++) {
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
        } else if (elementName == "categoryId") {
          categoryId = document.forms[i]["categoryId"].value;
          if (categoryId != null && categoryId != "")
            dados += "&categoryId=" + document.forms[i]["categoryId"].value;
        } else if (elementName == "ll") {
          ll = document.forms[i]["ll"].value;
          if (ll != null && ll != "")
            dados += "&ll=" + document.forms[i]["ll"].value;
        }
      }
      dados += "&v=20120722";
      //console.group("venue=" + venue + " (" + i + ")");
      //console.log(dados);
      //console.groupEnd();
      xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venue + "/edit", dados, i);
      dojo.byId("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";
    }
    //console.info("Dados enviados!");
    timer = setTimeout(function reabilitarSalvar() {
  	  dijit.byId("saveButton").setAttribute('disabled', false);
    }, 60000);
  }
}

function carregarListaCategorias() {
  //console.info("Recuperando dados das categorias...");
  xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/categories" + "?oauth_token=" + oauth_token + "&v=20120722", null, null);
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
      dojo.query("input[name=selecao]").forEach("dijit.byId(item.id).setChecked(true)");
      //onClick: dijit.byId(dojo.query("input[name=selecao]")[1].id).setChecked(true);
    }
  });
  subMenu1.addChild(subMenu1Item1);
  var subMenu1Item2 = new dijit.MenuItem({
    label: "Nenhuma",
    id: "menuItemSelecionarNenhuma",
    onClick: function() {
      dojo.query("input[name=selecao]").forEach("dijit.byId(item.id).setChecked(false)");
    }
  });
  subMenu1.addChild(subMenu1Item2);
  var menuItem1 = new dijit.PopupMenuItem({
    label: "Selecionar",
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
      //sinalizarVenues("duplicate");
      dojo.query("input[name=selecao]:checked").forEach("console.log(dijit.byId(item.id).value)");
    }
  });
  subMenu2.addChild(subMenu2Item1);
  subMenu2.addChild(new dijit.MenuSeparator);
  var subMenu2Item2 = new dijit.MenuItem({
    label: "N&atilde;o existe",
    id: "menuItemSinalizarDoesnt_exist",
    onClick: function() {
      dojo.query("input[name=selecao]:checked").forEach("desabilitarLinha(dijit.byId(item.id).value, 1)");
    }
  });
  subMenu2.addChild(subMenu2Item2);
  var subMenu2Item3 = new dijit.MenuItem({
    label: "Fechada",
    id: "menuItemSinalizarClosed"
  });
  subMenu2.addChild(subMenu2Item3);
  var subMenu2Item4 = new dijit.MenuItem({
    label: "Inadequada",
    id: "menuItemSinalizarInappropriate"
  });
  subMenu2.addChild(subMenu2Item4);
  var subMenu2Item5 = new dijit.MenuItem({
    label: "J&aacute; terminou",
    id: "menuItemSinalizarEvent_over"
  });
  subMenu2.addChild(subMenu2Item5);
  var menuItem2 = new dijit.PopupMenuItem({
    label: "Sinalizar",
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
    	window.open("data:text/plain;charset=iso-8859-1," + txt.join("\r\n"));
		}
  });
  subMenu3.addChild(subMenu3Item2);
  var menuItem3 = new dijit.PopupMenuItem({
    label: "Exportar",
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
  carregarVenues();
  carregarListaCategorias();
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

function showDialog_guia() {
  // set the content of the dialog:
  dlg_guia.attr("content", "<ul><li><p>Use sempre a ortografia, acentua&ccedil;&atilde;o e as letras mai&uacute;sculas e min&uacute;sculas corretas.</p></li><li><p>Em redes ou venues com v&aacute;rios locais, n&atilde;o &eacute; preciso adicionar um sufixo de local. Portanto, pode deixar &quot;Subway&quot; ou &quot;Loja Americanas&quot; (em vez de &quot;Subway - Ponta Verde&quot; ou &quot;Lojas Americanas - Iguatemi&quot;).</p></li><li><p>Os nomes das venues devem respeitar o grafia original do lugar sem abrevia&ccedil;&otilde;es (principalmente nomes de empresas).</p></li><li><p>Sempre use abrevia&ccedil;&otilde;es nos endere&ccedil;os: &quot;Av.&quot; em vez de &quot;Avenida&quot;, &quot;R.&quot; em vez de &quot;Rua&quot;, etc., observando as <a href='http://www.buscacep.correios.com.br' target='_blank'>diretrizes postais locais</a>.</p></li><li>O preenchimento da Rua Cross &eacute; opcional e deve ser realizado da seguinte forma:<ul><li>R. Bela Cintra (para venues em uma esquina)</li><li>R. Bela Cintra x R. Haddock Lobo (para venues entre duas quadras)</li></ul><br></li><li>Na Rua Cross tamb&eacute;m podem ser inclu&iacute;dos:<ul><li>Bairro, complemento, ponto de refer&ecirc;ncia ou via de acesso (quando relevante)</li><li>Bloco, piso, loja ou setor (para subvenues)</li></ul></li><li><p>Os nomes de Estados devem ser abreviados: &quot;RJ&quot; em vez de &quot;Rio de Janeiro&quot;.</p></li><li><p>Em caso de d&uacute;vida sobre a cria&ccedil;&atilde;o e edi&ccedil;&atilde;o de venues no foursquare, consulte nossas <a href='https://pt.foursquare.com/info/houserules' target='_blank'>regras da casa</a> e as <a href='http://support.foursquare.com/forums/191151-venue-help' target='_blank'>perguntas frequentes sobre venues</a>.</p></li></ul>");
  dlg_guia.show();
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
