dojo.require("dijit.Dialog");
dojo.require("dijit.form.Button");
dojo.require("dijit.form.ComboBox");
dojo.require("dijit.form.TextBox");
dojo.require("dijit.Tooltip");
dojo.require("dojo.data.ItemFileReadStore");
dojo.require("dijit.Tree");
var total = 0;
var venues = "";
var categorias = new Array();
var store = {};
var timer = null;
function xmlhttpRequest(metodo, endpoint, dados, i) {
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
      var resposta = JSON.parse(xmlhttp.responseText);
      if (xmlhttp.status == 200) {
        if (metodo == "POST") {
          document.getElementById("info" + i).innerHTML = document.getElementById("info" + i).innerHTML.replace(/%0A/gi, "");
          var dicaVenue = atualizarDicaVenue(i);
          createTooltip("venLnk" + i, dicaVenue);
          document.getElementById("result" + i).innerHTML = "<img src='img/ok.png' alt='" + xmlhttp.responseText + "' style='vertical-align: middle;'>";
        } else if ((metodo == "GET") && (resposta.response.categories == undefined)) {
          atualizarTabela(resposta, i);
        } else if (resposta.response.categories != undefined) {
          montarArvore(resposta);
        }
      } else if (xmlhttp.status == 400) {
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>";
        createTooltip("result" + i, "<span style=\"font-size: 92%\">Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
      } else if (xmlhttp.status == 401) {
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>";
        createTooltip("result" + i, "<span style=\"font-size: 92%\">Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
      } else if (xmlhttp.status == 403) {
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='Erro 403: Forbidden, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>";
        createTooltip("result" + i, "<span style=\"font-size: 92%\">Erro 403: Forbidden, Tipo: " + resposta.meta.errorType + ",<br>Detalhe: " + resposta.meta.errorDetail + "</span>");
      } else if (xmlhttp.status == 404) {
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='Erro 404: Not Found'>";
        createTooltip("result" + i, "<span style=\"font-size: 92%\">Erro 404: Not Found</span>");
      } else if (xmlhttp.status == 405) {
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='Erro 405: Method Not Allowed'>";
        createTooltip("result" + i, "<span style=\"font-size: 92%\">Erro 405: Method Not Allowed</span>");
      } else if (xmlhttp.status == 409) {
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='Erro 409: Conflict'>";
        createTooltip("result" + i, "<span style=\"font-size: 92%\">Erro 409: Conflict</span>");
      } else if (xmlhttp.status == 500) {
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='Erro 500: Internal Server Error'>";
        createTooltip("result" + i, "<span style=\"font-size: 92%\">Erro 500: Internal Server Error</span>");
      } else {
        document.getElementById(result).innerHTML = "<img src='img/erro.png' alt='Erro desconhecido: " + xmlhttp.status + "'>";
        createTooltip("result" + i, "<span style=\"font-size: 92%\">Erro desconhecido: " + xmlhttp.status + "</span>");
      }
    }
  }
  xmlhttp.open(metodo, endpoint, true);
  xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
  xmlhttp.send(dados);
  return false;
}
function Categoria(ids, nomes, icones) {
   this.ids = ids;
   this.nomes = nomes;
   this.icones = icones;
}
function atualizarCategorias(nomes, ids, icones) {
  document.getElementById("catsContainer").innerHTML = "";
  for (j = 0; j < nomes.length; j++)
    document.getElementById("catsContainer").innerHTML += "<div id='categoria" + (j + 1) + "' class='categoria' ondblclick=\"tornarCategoriaPrimaria('" + (j + 1) + "')\" onclick=\"removerCategoria('" + (j + 1) + "')\">" + nomes[j] + ",</div>";
  document.getElementById("catsContainer").innerHTML = document.getElementById("catsContainer").innerHTML.slice(0, -7) + "</div>";
  document.getElementById("catsIds").innerHTML = ids;
  document.getElementById("catsIcones").innerHTML = icones;
  //console.log(document.getElementById("catsIcones").innerHTML);
}
function editarCategorias(i) {
  var nomes = new Array();
  var ids =  "";
  var icones = "";
  if (document.getElementById("cid" + i).value != "") {
    nomes = document.getElementById("cna" + i).value.split(",", 3);
    ids = document.getElementById("cid" + i).value;
    icones = document.getElementById("cic" + i).value;
  }
  atualizarCategorias(nomes, ids, icones);
  document.getElementById("venueIndex").innerHTML = i;
  dijit.byId("dlg_cats").show();
}
function removerCategoria(i) {
  if (timer)
    clearTimeout(timer);
  timer = setTimeout(function() {
    //console.info('Remover a categoria ' + i);
    var nomes = new Array();
    var ids = "";
    var icones = "";
    if ((document.getElementById("categoria1") !== null) && (i != 1)) {
      nomes.push(document.getElementById("categoria1").innerHTML.replace(/,/gi, ""));
      ids += document.getElementById("catsIds").innerHTML.substr(0, 24) + ",";
      icones += document.getElementById("catsIcones").innerHTML.split(",", 1)[0] + ",";
    }
    if ((document.getElementById("categoria2") !== null) && (i != 2)) {
      nomes.push(document.getElementById("categoria2").innerHTML.replace(/,/gi, ""));
      ids += document.getElementById("catsIds").innerHTML.substr(25, 24) + ",";
      icones += document.getElementById("catsIcones").innerHTML.split(",", 2)[1] + ",";
    }
    if ((document.getElementById("categoria3") !== null) && (i != 3)) {
      nomes.push(document.getElementById("categoria3").innerHTML);
      ids += document.getElementById("catsIds").innerHTML.substr(50, 24) + ",";
      icones += document.getElementById("catsIcones").innerHTML.split(",", 3)[2] + ",";
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
  nomes.push(document.getElementById("categoria" + i).innerHTML.replace(/,/gi, ""));
  if (i == 1) {
    ids += document.getElementById("catsIds").innerHTML.substr(0, 24) + ",";
    icones += document.getElementById("catsIcones").innerHTML.split(",", 1)[0] + ",";
  } else if (i == 2) {
    ids += document.getElementById("catsIds").innerHTML.substr(25, 24) + ",";
    icones += document.getElementById("catsIcones").innerHTML.split(",", 2)[1] + ",";
  } else if (i == 3) {
    ids += document.getElementById("catsIds").innerHTML.substr(50, 24) + ",";
    icones += document.getElementById("catsIcones").innerHTML.split(",", 3)[2] + ",";
  }
  if ((document.getElementById("categoria1") !== null) && (i != 1)) {
    nomes.push(document.getElementById("categoria1").innerHTML.replace(/,/gi, ""));
    ids += document.getElementById("catsIds").innerHTML.substr(0, 24) + ",";
    icones += document.getElementById("catsIcones").innerHTML.split(",", 1)[0] + ",";
  }
  if ((document.getElementById("categoria2") !== null) && (i != 2)) {
    nomes.push(document.getElementById("categoria2").innerHTML.replace(/,/gi, ""));
    ids += document.getElementById("catsIds").innerHTML.substr(25, 24) + ",";
    icones += document.getElementById("catsIcones").innerHTML.split(",", 2)[1] + ",";
  }
  if ((document.getElementById("categoria3") !== null) && (i != 3)) {
    nomes.push(document.getElementById("categoria3").innerHTML);
    ids += document.getElementById("catsIds").innerHTML.substr(50, 24) + ",";
    icones += document.getElementById("catsIcones").innerHTML.split(",", 3)[2] + ",";
  }
  atualizarCategorias(nomes, ids.slice(0, -1), icones.slice(0, -1));
}
function salvarCategorias() {
  var i = document.getElementById("venueIndex").innerHTML;
  var nomes = "";
  if (document.getElementById("catsIds").innerHTML != "") {
    nomes = document.getElementById("categoria1").innerHTML;
    if (document.getElementById("categoria2") !== null)
      nomes += document.getElementById("categoria2").innerHTML;
    if (document.getElementById("categoria3") !== null)
      nomes += document.getElementById("categoria3").innerHTML;
    document.getElementById("cna" + i).value = nomes;
    document.getElementById("cid" + i).value = document.getElementById("catsIds").innerHTML;
    document.getElementById("cic" + i).value = document.getElementById("catsIcones").innerHTML;
    document.getElementById("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:editarCategorias(" + i + ")'><img id=catImg" + i + " src='" + document.getElementById("cic" + i).value.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'></a>";
    //console.log(document.getElementById("cna" + i).value);
    //console.log(document.getElementById("cid" + i).value);
    //console.log(document.getElementById("cic" + i).value);
    dijit.byId('dlg_cats').hide();
    createTooltip("catLnk" + i, "<span style=\"font-size: 92%\">" + nomes.replace(/,/gi, ", ") + "</span>");
  }
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
function formattedTime(unix_timestamp) {
  var date = new Date(unix_timestamp * 1000);
  var dia = date.getDate();
  if (dia < 10)
    dia = "0" + dia;
  var mes = new Array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");
  return dia + "/" + mes[date.getMonth()] + "/" + date.getFullYear();
}
function atualizarDicaVenue(i) {
  var dica = "<span style=\"font-size: 92%\"><b>" + document.forms[i]["name"].value + "</b>";
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
  dica += "<br><span style=\"color: #999999;\">Criada em " + document.forms[i]["createdAt"].value + "</span></span>";
  return dica;
}
function atualizarTabela(resposta, i) {
  total++;
  var linha = "";
  categorias[i] = new Categoria();
  for (j = 0; j < resposta.response.venue.categories.length; j++) {
    categorias[i].ids += resposta.response.venue.categories[j].id + ",";
    categorias[i].nomes += resposta.response.venue.categories[j].name + ",";
    categorias[i].icones += resposta.response.venue.categories[j].icon.prefix + resposta.response.venue.categories[j].icon.sizes[0] + resposta.response.venue.categories[j].icon.name + ",";
  }
  if (categorias[i].ids != undefined) {
    categorias[i].ids = categorias[i].ids.slice(0, -1).replace(/undefined/gi, "");
    document.getElementById("cid" + i).value = categorias[i].ids;
    categorias[i].nomes = categorias[i].nomes.slice(0, -1).replace(/undefined/gi, "");
    document.getElementById("cna" + i).value = categorias[i].nomes;
    categorias[i].icones = categorias[i].icones.slice(0, -1).replace(/undefined/gi, "");
    document.getElementById("cic" + i).value = categorias[i].icones;
    //console.log(document.getElementById("cna" + i).value + " (" + document.getElementById("cid" + i).value + ") [" + document.getElementById("cic" + i).value + "]");
    //console.log(categorias[i].nomes + " (" + categorias[i].ids + ") [" + categorias[i].icones + "]");
  }
  document.forms[i]["name"].value = resposta.response.venue.name;
  for (j = 1; j < document.forms[i].elements.length - 2; j++) {
    switch (document.forms[i].elements[j].name) {
    case "name":
      //document.forms[i]["name"].value = resposta.response.venue.name;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'name;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.name + '";';
      break;
    case "address":
      document.forms[i]["address"].value = resposta.response.venue.location.address;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'address;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.location.address + '";';
      break;
    case "crossStreet":
      document.forms[i]["crossStreet"].value = resposta.response.venue.location.crossStreet;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'crossStreet;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.location.crossStreet + '";';
      break;
    case "city":
      document.forms[i]["city"].value = resposta.response.venue.location.city;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'city;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.location.city + '";';
      break;
    case "state":
      document.forms[i]["state"].value = resposta.response.venue.location.state;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'state;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.location.state + '";';
      break;
    case "zip":
      document.forms[i]["zip"].value = resposta.response.venue.location.postalCode;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'zip;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.location.postalCode + '";';
      break;
    case "twitter":
      document.forms[i]["twitter"].value = resposta.response.venue.contact.twitter;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'twitter;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.contact.twitter + '";';
      break;
    case "phone":
      document.forms[i]["phone"].value = resposta.response.venue.contact.phone;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'phone;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.contact.phone + '";';
      break;
    case "url":
      document.forms[i]["url"].value = resposta.response.venue.url;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'url;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.url + '";';
      break;
    case "description":
      document.forms[i]["description"].value = resposta.response.venue.description;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'description;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.description + '";';
      break;
    case "ll":
      document.forms[i]["ll"].value = resposta.response.venue.location.lat + ', ' + resposta.response.venue.location.lng;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'll;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.location.lat + ', ' + resposta.response.venue.location.lng + '";';
      break;
    default:
      break;
    }
    if (document.forms[i].elements[j].value == "undefined") {
      document.forms[i].elements[j].value = "";
      var x = window.scrollX, y = window.scrollY;
      document.forms[i].elements[j].focus();
      document.forms[i].elements[j].blur();
      window.scrollTo(x, y);
    }
    document.getElementById("result" + i).innerHTML = "";
    //if (total == document.forms.length) {
      //dojo.byId("regras").focus();
      //dojo.byId("regras").blur();
    //}
  }
  if (total == 1)
    venues = venues.slice(0, -1) + '\n';
  venues = venues + linha.replace(/undefined/gi, "") + '\n';
  if (resposta.response.venue.categories[0] == undefined) {
    document.getElementById("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:editarCategorias(" + i + ")'><img id=catImg" + i + " src='http://foursquare.com/img/categories/none.png' style='height: 22px; width: 22px; margin-left: 0px'></a>";
  } else {
    document.getElementById("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:editarCategorias(" + i + ")'><img id=catImg" + i + " src='" + categorias[i].icones.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'></a>";
    createTooltip("catLnk" + i, "<span style=\"font-size: 92%\">" + categorias[i].nomes.replace(/,/gi, ", ") + "</span>");
  }
  document.forms[i]["createdAt"].value = formattedTime(resposta.response.venue.createdAt);
  var dicaVenue = atualizarDicaVenue(i);
  createTooltip("venLnk" + i, dicaVenue);
}
function montarArvore(resposta) {
  var restructuredData = dojo.map(resposta.response.categories, dojo.hitch(this, function(category1) {
    var newCategory1 = {};
    newCategory1.id = category1.id;
    newCategory1.name = category1.name;
    newCategory1.icon = category1.icon.prefix + category1.icon.sizes[0] + category1.icon.name;
    newCategory1.children = dojo.map(category1.categories, dojo.hitch(this, function(idPrefix, category2) {
      var newCategory2 = {};
      //newCategory2.id = idPrefix + "_" + category2.id;
      newCategory2.id = category2.id;
      newCategory2.name = category2.name;
      newCategory2.icon = category2.icon.prefix + category2.icon.sizes[0] + category2.icon.name;
      if (category2.categories != "") {
        newCategory2.children = dojo.map(category2.categories, dojo.hitch(this, function(idPrefix, category3) {
          var newCategory3 = {};
          //newCategory3.id = idPrefix + "_" + category3.id;
          newCategory3.id = category3.id;
          newCategory3.name = category3.name;
          newCategory3.icon = category3.icon.prefix + category3.icon.sizes[0] + category3.icon.name;
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
  new dijit.Tree({
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
}
function treeOnClick(item) {
  if (!item.root) {
    //console.log("Execute of node " + store.getLabel(item) + ", id=" + store.getValue(item, "id") + ", icon=" + store.getValue(item, "icon"));
    var i = 1;
    if (document.getElementById("categoria3") !== null)
      //console.warn("Limite maximo de categorias");
      return false;
    else if (((document.getElementById("categoria2") !== null) && (document.getElementById("categoria2").innerHTML.replace(/,/gi, "") == store.getLabel(item))) || ((document.getElementById("categoria1") !== null) && (document.getElementById("categoria1").innerHTML.replace(/,/gi, "") == store.getLabel(item))))
      //console.warn("Categoria repetida");
      return false;
    else if (document.getElementById("categoria2") !== null)
      i = 3;
    else if (document.getElementById("categoria1") !== null)
      i = 2;
    // Adiciona categoria
    if (i != 1) {
      document.getElementById("catsContainer").innerHTML = document.getElementById("catsContainer").innerHTML.slice(0, -6) + ",</div>"
      document.getElementById("catsIds").innerHTML += ",";
      document.getElementById("catsIcones").innerHTML += ",";
    }
    document.getElementById("catsContainer").innerHTML += "<div id='categoria" + i + "' class='categoria' ondblclick=\"tornarCategoriaPrimaria('" + i + "')\" onclick=\"removerCategoria('" + i + "')\">" + store.getLabel(item) + "</div>";
    document.getElementById("catsIds").innerHTML += store.getValue(item, "id");
    document.getElementById("catsIcones").innerHTML += store.getValue(item, "icon");
    return true;
  }
}
function carregarVenues() {
  var venue;
  //console.info("Recuperando dados das venues...");
  for (i = 0; i < document.forms.length; i++) {
    venue = document.forms[i]["venue"].value;
    xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/" + venue + "?oauth_token=" + oauth_token + "&v=20120311", null, i);
    document.getElementById("result" + i).innerHTML = "<img src='img/loading.gif' alt='Recuperando dados...'>";
  }
  //console.info("Venues recuperadas!");
}
function salvarVenues() {
  var venue, dados, ll;
  //console.info("Enviando dados...");
  for (i = 0; i < document.forms.length; i++) {
    dados = "oauth_token=" + oauth_token;
    for (j = 1; j < document.forms[i].elements.length; j++) {
      venue = document.forms[i]["venue"].value;
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
    dados += "&v=20120311";
    //console.group("venue=" + venue + " (" + i + ")");
    //console.log(dados);
    //console.groupEnd();
    xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venue + "/edit", dados, i);
    document.getElementById("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";
  }
  //console.info("Dados enviados!");
}
function carregarListaCategorias() {
  //console.info("Recuperando dados das categorias...");
  xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/categories" + "?oauth_token=" + oauth_token + "&v=20120221", null, i);
  //console.info("Categorias recuperadas!");
}
var dlgGuia;
dojo.addOnLoad(function() {
  // create the dialog:
  dlg_guia = new dijit.Dialog({
    title: "Guia de estilo",
    style: "width: 435px"
  });
  carregarVenues();
  carregarListaCategorias();
});
function showDialog_guia() {
  // set the content of the dialog:
  dlg.attr("content", "<ul><li><p>Use sempre a ortografia e as letras mai&uacute;sculas corretas.</p></li><li><p>Em redes ou venues com v&aacute;rios locais, n&atilde;o &eacute; preciso adicionar um sufixo de local. Portanto, pode deixar &quot;Starbucks&quot; ou &quot;Apple Store&quot; (em vez de &quot;Starbucks - Queen Anne&quot; ou &quot;Apple Store - Cidade Alta&quot;).</p></li><li><p>Sempre que poss&iacute;vel, use abrevia&ccedil;&otilde;es: &quot;Av.&quot; em vez de &quot;Avenida&quot;, &quot;R.&quot; em vez de &quot;Rua&quot;, etc.</p></li><li>A Rua Cross deve ser preenchida da seguinte forma:<ul><li>R. Bela Cintra (para venues em uma esquina)</li><li>R. Bela Cintra x R. Haddock Lobo (para venues entre duas quadras)</li></ul><br></li><li>Na Rua Cross tamb&eacute;m podem ser inclu&iacute;dos:<ul><li>Bairro, complemento, ponto de refer&ecirc;ncia ou via de acesso (quando relevante)</li><li>Bloco, piso, loja ou setor (para subvenues)</li></ul></li><li><p>Os nomes de Estados e prov&iacute;ncias devem ser abreviados.</p></li><li><p>Em caso de d&uacute;vida, formate os endere&ccedil;os das venues de acordo com as diretrizes postais locais.</p></li><li><p>Se tiver mais perguntas sobre a cria&ccedil;&atilde;o e edi&ccedil;&atilde;o de venues no foursquare, consulte nossas <a href='https://pt.foursquare.com/info/houserules' target='_blank'>regras da casa</a> e as <a href='http://support.foursquare.com/forums/191151-venue-help' target='_blank'>perguntas frequentes sobre venues</a>.</p></li></ul>");
  dlg_guia.show();
}
//var node = dojo.byId("forms");
//dojo.connect(node, "onkeypress", function(e) {
  //if (e.keyCode == dojo.keys.DOWN_ARROW) {
    //document.forms[1].elements[1].focus();
    //dojo.stopEvent(e);
  //}
//});
function exportarVenues() {
  window.location.href = "data:application/csv;charset=iso-8859-1," + encodeURIComponent(venues);
}
