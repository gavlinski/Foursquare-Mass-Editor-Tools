dojo.require("dijit.Dialog");
dojo.require("dijit.form.Form");
dojo.require("dijit.form.Button");
dojo.require("dijit.form.CheckBox");
dojo.require("dijit.form.SimpleTextarea");
dojo.require("dijit.form.TextBox");
dojo.require("dijit.form.ValidationTextBox");
dojo.require("dijit.form.Select");
dojo.require("dijit.layout.AccordionContainer");
dojo.require("dojox.form.Uploader");
dojo.require("dojo.cookie");
dojo.require("dojox.image");

// Utilitários de logging (isLocalhost, debugLog, debugInfo) são providos por session-manager.js

function obterTokenPagina() {
	var campo = dojo.byId("oauth_token_scr");
	return campo ? campo.value : "";
}

var camposImportarDentro = ["nome", "endereco", "ruatransversal", "bairro", "cidade", "estado", "codigopostal", "dentro", "telefone", "sitedaweb", "twitter", "facebook", "instagram", "latlng", "descricao", "menu", "redes", "horas"];

function posicionarResultadosAutocomplete(input, results, anchor, matchAnchorWidth) {
	document.body.appendChild(results);
	results.style.position = "fixed";
	results.style.zIndex = "10000";
	results.style.margin = "2px 0 0 0";
	results.style.boxSizing = "border-box";

	function atualizarPosicao() {
		var rect = (anchor || input).getBoundingClientRect();
		var larguraViewport = document.documentElement.clientWidth;
		var alturaViewport = window.innerHeight || document.documentElement.clientHeight;
		var largura = matchAnchorWidth ? rect.width : Math.min(340, larguraViewport - 16);
		var abaixo = alturaViewport - rect.bottom - 8;
		var acima = rect.top - 8;
		var abrirAcima = abaixo < 120 && acima > abaixo;
		var disponivel = Math.max(0, (abrirAcima ? acima : abaixo) - 2);
		var alturaConteudo = results.style.display !== "none" ? results.scrollHeight : 266;
		var altura = Math.min(266, disponivel, alturaConteudo || 266);
		var esquerda = Math.max(8, Math.min(rect.left, larguraViewport - largura - 8));

		results.style.width = largura + "px";
		results.style.maxHeight = altura + "px";
		results.style.left = esquerda + "px";
		results.style.top = (abrirAcima ? Math.max(4, rect.top - altura - 4) : rect.bottom) + "px";
	}

	input.addEventListener("focus", atualizarPosicao);
	input.addEventListener("input", atualizarPosicao);
	window.addEventListener("resize", atualizarPosicao);
	window.addEventListener("scroll", atualizarPosicao, true);
	new MutationObserver(atualizarPosicao).observe(results, { childList: true });
	atualizarPosicao();
}

function configurarBuscaCategoriaPrincipal() {
	var widget = dijit.byId("categorySearchMain");
	var input = widget ? widget.focusNode : dojo.byId("categorySearchMain");
	if (!input || input._fsqBound) return;
	input._fsqBound = true;
	var results = dojo.byId("categorySearchMainResults");
	posicionarResultadosAutocomplete(input, results, widget ? widget.domNode : input, true);
	var hidden = dojo.byId("categoryId");
	var ctrl = fsqAnexarAutocomplete({
		input: input,
		results: results,
		status: dojo.byId("categorySearchMainStatus"),
		statusClass: "",
		group: "categories",
		getToken: obterTokenPagina,
		extraParams: function() {
			return fsqObterLl(dijit.byId("ll").get("value") || dojo.cookie("coordinates"), obterTokenPagina()).then(function(ll) {
				return { ll: ll, includePrivateCategories: "true", filterTopLevelCats: "false" };
			});
		},
		parse: fsqNormalizarCategorias,
		emptyMessage: "Nenhuma categoria encontrada.",
		onSelect: function(item) {
			hidden.value = item.id;
			if (widget) widget.set("value", item.name);
			else input.value = item.name;
		}
	});
	// Editar o texto invalida a categoria selecionada
	input.addEventListener("input", function() { hidden.value = ""; });
	document.addEventListener("click", function(event) {
		if (event.target !== input && !results.contains(event.target)) ctrl.limpar();
	});
}

function configurarBuscaLocalPai() {
	var widget = dijit.byId("parentSearchMain");
	var input = widget ? widget.focusNode : dojo.byId("parentSearchMain");
	if (!input || input._fsqBound) return;
	input._fsqBound = true;
	var results = dojo.byId("parentSearchMainResults");
	posicionarResultadosAutocomplete(input, results, widget ? widget.domNode : input, !!widget);
	var hidden = dojo.byId("parentIdMain");
	var ctrl = fsqAnexarAutocomplete({
		input: input,
		results: results,
		status: dojo.byId("parentSearchMainStatus"),
		statusClass: "",
		group: "",
		minLength: 3,
		getToken: obterTokenPagina,
		extraParams: function() {
			var regiao = (dijit.byId("ll5").get("value") || dojo.cookie("coordinates") || "").trim();
			if (!regiao) throw new Error("Para buscar um local pai, informe coordenadas (latitude,longitude) ou uma cidade no campo Região.");
			var parametros = { m: "foursquare", group: "unified", isMobile: "false", limit: 20 };
			if (/^-?\d+(?:\.\d+)?\s*,\s*-?\d+(?:\.\d+)?$/.test(regiao)) {
				return fsqObterLl(regiao, obterTokenPagina()).then(function(ll) {
					parametros.ll = ll;
					return parametros;
				});
			}
			parametros.near = regiao;
			return parametros;
		},
		parse: fsqNormalizarLocais,
		emptyMessage: "Nenhum local encontrado.",
		onIdOrUrl: function(id, isCurrent) {
			var textoBusca = input.value.trim();
			ctrl.mensagem("Validando o local informado...", "muted");
			return fsqBuscarLocalPorId(id, obterTokenPagina()).then(function(local) {
				if (!isCurrent() || input.value.trim() !== textoBusca) return;
				hidden.value = local.id;
				if (widget) widget.set("value", local.id);
				else input.value = local.id;
				ctrl.mensagem('Local "' + local.name + '" selecionado.', "success");
			});
		},
		onSelect: function(item) {
			hidden.value = item.id;
			if (widget) widget.set("value", item.name);
			else input.value = item.name;
		},
		selectionMessage: function(item) {
			return 'Local com ID "' + item.id + '" selecionado.';
		},
		onError: function(error) {
			ctrl.mensagem("Não foi possível validar o local informado.", "error");
			window.alert(error.message || "Não foi possível validar o local informado.");
		}
	});
	input.addEventListener("input", function() {
		hidden.value = "";
		ctrl.mensagem("", "");
	});
	document.addEventListener("click", function(event) {
		if (event.target !== input && !results.contains(event.target)) {
			ctrl.limpar();
			if (!hidden.value) {
				ctrl.cancelar();
				ctrl.mensagem("", "");
			}
		}
	});
}

dojo.addOnLoad(function() {
	configurarBuscaCategoriaPrincipal();
	configurarBuscaLocalPai();
	// Preload de imagens do tema (Dojo carregará automaticamente quando necessário)
	// dojox.image.preload(["js/dijit/themes/tundra/images/progressBarFull.png", "js/dijit/themes/tundra/images/progressBarAnim.gif"]);
	
	dlg_csv = new dijit.Dialog({
		title: "Arquivo CSV",
		style: "width: 570px"
	});
	var arquivo_csv = dojo.byId("arquivo_csv");
	var arquivo_csv_data = null;
	var uploader_csv = dijit.byId("uploader_csv");
	var form_csv = dijit.byId("f_csv");
	dojo.connect(uploader_csv, "onChange", function (data) {
		arquivo_csv.textContent = data[0].name + " (" + Math.ceil(data[0].size * .001) + " kB)";
		arquivo_csv_data = data[0];
	});
	dojo.connect(form_csv, "onSubmit", function(e) {
		if (form_csv.validate()) {
			if (!arquivo_csv_data) {
				e.preventDefault();
				alert("O arquivo precisa ser escolhido");
				uploader_csv.inputNode.focus();
			} else if (arquivo_csv_data.name.split('.').pop() != "csv") {
				e.preventDefault();
				alert("O arquivo deve ser do tipo CSV");
				uploader_csv.inputNode.focus();
			//} else {
				//alert("Ready to submit data: " + dojo.toJson(form_csv.attr("value")));
			}
		} else {
			e.preventDefault();
		}
		//dojo.cookie("pagina", "", { expires: 15 });
		//dojo.cookie("textarea", "", { expires: 15 });
		dojo.cookie("accordion", dijit.byId("accordion").selectedChildWidget.id, { expires: 15 });
	});
	
	dlg_txt = new dijit.Dialog({
		title: "Arquivo de texto",
		style: "width: 590px"
	});
	var arquivo_txt = dojo.byId("arquivo_txt");
	var arquivo_txt_data = null;
	var uploader_txt = dijit.byId("uploader_txt");
	var form_txt = dijit.byId("f_txt");
	dojo.connect(uploader_txt, "onChange", function (data) {
		arquivo_txt.textContent = data[0].name + " (" + Math.ceil(data[0].size * .001) + " kB)";
		arquivo_txt_data = data[0];
	});
	dojo.connect(form_txt, "onSubmit", function(e) {
		if (form_txt.validate()) {
			if (!arquivo_txt_data) {
				e.preventDefault();
				alert("O arquivo precisa ser escolhido");
				uploader_txt.inputNode.focus();
			//} else if (dojo.query('input:checked', 'f_txt').length == 0) {
				//e.preventDefault();
				//alert("Selecione pelo menos um dos campos");
				//dijit.byId("nome1").focus();
			//} else {
				//alert("Ready to submit data: " + dojo.toJson(form_txt.attr("value")));
			}
		} else {
			e.preventDefault();
		}
		//dojo.cookie("pagina", "", { expires: 15 });
		//dojo.cookie("textarea", "", { expires: 15 });
		var campos1 = dijit.byId("nome1").checked + "." + dijit.byId("endereco1").checked + "." + dijit.byId("ruatransversal1").checked + "." + dijit.byId("bairro1").checked + "." + dijit.byId("cidade1").checked + "." + dijit.byId("estado1").checked + "." + dijit.byId("codigopostal1").checked + "." + dijit.byId("dentro1").checked + "." + dijit.byId("telefone1").checked + "." + dijit.byId("sitedaweb1").checked + "." + dijit.byId("twitter1").checked + "." + dijit.byId("facebook1").checked + "." + dijit.byId("instagram1").checked + "." + dijit.byId("latlng1").checked + "." + dijit.byId("descricao1").checked + "." + dijit.byId("menu1").checked + "." + dijit.byId("redes1").checked + "." + dijit.byId("horas1").checked;
		dojo.cookie("campos", campos1, { expires: 15 });
		dojo.cookie("accordion", dijit.byId("accordion").selectedChildWidget.id, { expires: 15 });
	});
	
	dlg_lks = new dijit.Dialog({
		title: "Endere&ccedil;o de uma p&aacute;gina web",
		style: "width: 650px"
	});
	var form_lks = dijit.byId("f_lks");
	var pagina = dijit.byId("pagina");
	dojo.connect(form_lks, "onSubmit", function(e) {
		if (!form_lks.validate()) {
			//if (dojo.query('input:checked', 'f_lks').length == 0) {
				//e.preventDefault();
				//alert("Selecione pelo menos um dos campos");
				//dijit.byId("nome2").focus();
			//} else {
				//alert("Ready to submit data: " + dojo.toJson(form_lks.attr("value")));
			//}
		//} else {
			e.preventDefault();
		}
		dojo.cookie("pagina", dijit.byId("pagina").value, { expires: 15 });
		//dojo.cookie("textarea", "", { expires: 15 });
		var campos2 = dijit.byId("nome2").checked + "." + dijit.byId("endereco2").checked + "." + dijit.byId("ruatransversal2").checked + "." + dijit.byId("bairro2").checked + "." + dijit.byId("cidade2").checked + "." + dijit.byId("estado2").checked + "." + dijit.byId("codigopostal2").checked + "." + dijit.byId("dentro2").checked + "." + dijit.byId("telefone2").checked + "." + dijit.byId("sitedaweb2").checked + "." + dijit.byId("twitter2").checked + "." + dijit.byId("facebook2").checked + "." + dijit.byId("instagram2").checked + "." + dijit.byId("latlng2").checked + "." + dijit.byId("descricao2").checked + "." + dijit.byId("menu2").checked + "." + dijit.byId("redes2").checked + "." + dijit.byId("horas2").checked;
		dojo.cookie("campos", campos2, { expires: 15 });
		dojo.cookie("accordion", dijit.byId("accordion").selectedChildWidget.id, { expires: 15 });
	});
	
	dlg_ids = new dijit.Dialog({
		title: "IDs ou URLs dos locais",
		style: "width: 590px"
	});
	var form_ids = dijit.byId("f_ids");
	var textarea_ids = dijit.byId("textarea_ids");
	dojo.connect(form_ids, "onSubmit", function(e) {
		if (form_ids.validate()) {
			if (textarea_ids.value == "") {
				e.preventDefault();
				alert("Informe pelo menos um local");
				textarea_ids.focus();
			//} else if (dojo.query('input:checked', 'f_ids').length == 0) {
				//e.preventDefault();
				//alert("Selecione pelo menos um dos campos");
				//dijit.byId("nome3").focus();
			//} else {
				//alert("Ready to submit data: " + dojo.toJson(form_ids.attr("value")));
			}
		} else {
			e.preventDefault();
		}
		//dojo.cookie("pagina", "", { expires: 15 });
		dojo.cookie("textarea", dijit.byId("textarea_ids").value, { expires: 15 });
		var campos3 = dijit.byId("nome3").checked + "." + dijit.byId("endereco3").checked + "." + dijit.byId("ruatransversal3").checked + "." + dijit.byId("bairro3").checked + "." + dijit.byId("cidade3").checked + "." + dijit.byId("estado3").checked + "." + dijit.byId("codigopostal3").checked + "." + dijit.byId("dentro3").checked + "." + dijit.byId("telefone3").checked + "." + dijit.byId("sitedaweb3").checked + "." + dijit.byId("twitter3").checked + "." + dijit.byId("facebook3").checked + "." + dijit.byId("instagram3").checked + "." + dijit.byId("latlng3").checked + "." + dijit.byId("descricao3").checked + "." + dijit.byId("menu3").checked + "." + dijit.byId("redes3").checked + "." + dijit.byId("horas3").checked;
		dojo.cookie("campos", campos3, { expires: 15 });
		dojo.cookie("accordion", dijit.byId("accordion").selectedChildWidget.id, { expires: 15 });
	});
	
	var form_src = dijit.byId("f_src");
	var ll_src = dijit.byId("ll");
	function prepararCookiesBusca() {
		var campos4 = dijit.byId("nome4").checked + "." + dijit.byId("endereco4").checked + "." + dijit.byId("ruatransversal4").checked + ".";
		(dijit.byId("bairro4").disabled) ? campos4 += "false." : campos4 += dijit.byId("bairro4").checked + ".";
		campos4 += dijit.byId("cidade4").checked + "." + dijit.byId("estado4").checked + "." + dijit.byId("codigopostal4").checked + ".";
		(dijit.byId("dentro4").disabled) ? campos4 += "false." : campos4 += dijit.byId("dentro4").checked + ".";
		campos4 += dijit.byId("telefone4").checked + "." + dijit.byId("sitedaweb4").checked + "." + dijit.byId("twitter4").checked + "." + dijit.byId("facebook4").checked + ".";
		(dijit.byId("instagram4").disabled) ? campos4 += "false." : campos4 += dijit.byId("instagram4").checked + ".";
		campos4 += dijit.byId("latlng4").checked + ".";
		(dijit.byId("descricao4").disabled) ? campos4 += "false." : campos4 += dijit.byId("descricao4").checked + ".";
		(dijit.byId("menu4").disabled) ? campos4 += "false." : campos4 += dijit.byId("menu4").checked + ".";
		campos4 += dijit.byId("redes4").checked + ".";
		(dijit.byId("horas4").disabled) ? campos4 += "false." : campos4 += dijit.byId("horas4").checked;

		var categoryWidget = dijit.byId("categorySearchMain");
		var search = [dijit.byId("query").value, dijit.byId("ll").value, dojo.byId("categoryId").value, dijit.byId("radius").value, dijit.byId("intent").value, dijit.byId("limit").value, categoryWidget ? categoryWidget.get("value") : ""];
		dojo.cookie("search", JSON.stringify(search), { expires: 15 });
		dojo.cookie("campos", campos4, { expires: 15 });
		dojo.cookie("accordion", dijit.byId("accordion").selectedChildWidget.id, { expires: 15 });
	}
	dojo.connect(form_src, "onSubmit", function(e) {
		// Se campo ll estiver vazio, preenche com coordenadas do último check-in
		if (ll_src.value == "" || ll_src.value.trim() == "") {
			var lastCheckCoords = dojo.cookie("coordinates");
			
			if (lastCheckCoords && lastCheckCoords !== "undefined" && lastCheckCoords.trim() !== "") {
				debugLog("✅ Preenchendo campo Local com coordenadas: " + lastCheckCoords);
				ll_src.value = lastCheckCoords;
				dijit.byId("ll").attr("value", lastCheckCoords);
			} else {
				debugLog("⚠️ Campo Local é obrigatório");
				e.preventDefault();
				alert("O campo Local é obrigatório. Informe as coordenadas (ex: -29.93,-51.16) ou um endereço.");
				ll_src.focus();
				return;
			}
		}

		if (!form_src.validate()) {
			e.preventDefault();
			return;
		}

		var coordenadas = ll_src.value.trim().match(/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/);
		if (!coordenadas) {
			e.preventDefault();
			dijit.byId("radius").set("value", dijit.byId("radius").value || "5000");
			var botaoPesquisa = form_src.domNode.querySelector('button[type="submit"]');
			if (botaoPesquisa) botaoPesquisa.disabled = true;
			fsqObterLl(ll_src.value, obterTokenPagina()).then(function(llConvertido) {
				if (!llConvertido) throw new Error("O endereço não foi convertido em coordenadas.");
				ll_src.set("value", llConvertido);
				prepararCookiesBusca();
				form_src.domNode.submit();
			}).catch(function(error) {
				console.error("❌ Erro ao converter Local em coordenadas:", error);
				alert(error.message || "Não foi possível converter o Local em coordenadas.");
				if (botaoPesquisa) botaoPesquisa.disabled = false;
			});
			return;
		}
		
		// Preenche radius com valor padrão se estiver vazio
		if (!dijit.byId("radius").value || dijit.byId("radius").value == "") {
			dijit.byId("radius").attr("value", "5000");
		}
		prepararCookiesBusca();
	});

		dlg_lks = new dijit.Dialog({
		title: "Endere&ccedil;o de uma p&aacute;gina web",
		style: "width: 650px"
	});
	dlg_dentro = new dijit.Dialog({
		title: "Nome, ID ou link do local pai",
		style: "width: 650px"
	});
	var form_dentro = dijit.byId("f_dentro");
	var ll_dentro = dijit.byId("ll5");

	function salvarEstadoImportarDentro() {
		var campos = {};
		camposImportarDentro.forEach(function(nome) {
			var widget = dijit.byId(nome + "5");
			campos[nome] = Boolean(widget && !widget.disabled && widget.checked);
		});
		var estado = {
			parentId: dojo.byId("parentIdMain").value,
			parentText: dijit.byId("parentSearchMain").get("value"),
			ll: ll_dentro.get("value"),
			campos: campos
		};
		dojo.cookie("importar_dentro", JSON.stringify(estado), { expires: 15 });
	}

	dojo.connect(form_dentro, "onSubmit", function(e) {
		if (!dojo.byId("parentIdMain").value) {
			e.preventDefault();
			alert("Selecione na lista o local onde os demais est\u00e3o localizados (ou informe o ID/link).");
			dojo.byId("parentSearchMain").focus();
			return;
		}
		if (ll_dentro.get("value").trim() == "") {
			var coordenadas = dojo.cookie("coordinates");
			if (coordenadas && coordenadas !== "undefined" && coordenadas.trim() !== "") {
				ll_dentro.set("value", coordenadas);
			} else {
				e.preventDefault();
				alert("O campo Coordenadas \u00e9 obrigat\u00f3rio. Informe lat,lng (ex: -29.93,-51.16) ou uma cidade.");
				ll_dentro.focus();
				return;
			}
		}
		var campos5 = camposImportarDentro.map(function(nome) {
			var widget = dijit.byId(nome + "5");
			return widget.disabled ? "false" : String(widget.checked);
		}).join(".");
		salvarEstadoImportarDentro();
		dojo.cookie("campos", campos5, { expires: 15 });
		dojo.cookie("accordion", dijit.byId("accordion").selectedChildWidget.id, { expires: 15 });
	});

	function limparCookiesDeUpload() {
		dojo.cookie("arquivo_csv_nome", null, { expires: -1 });
		dojo.cookie("arquivo_csv_size", null, { expires: -1 });
		dojo.cookie("arquivo_txt_nome", null, { expires: -1 });
		dojo.cookie("arquivo_txt_size", null, { expires: -1 });
	}

	limparCookiesDeUpload();
	window.addEventListener("pageshow", function(event) {
		if (!event.persisted) return;

		arquivo_csv_data = null;
		arquivo_txt_data = null;
		uploader_csv.reset();
		uploader_txt.reset();
		arquivo_csv.textContent = "Nenhum arquivo selecionado";
		arquivo_txt.textContent = "Nenhum arquivo selecionado";
		limparCookiesDeUpload();
	});
});

dojo.ready(function() {
	setTimeout(function() {
		//if ((dojo.cookie("name") != null) && (dojo.cookie("name") != "undefined"))
		//dojo.byId("name").innerText = dojo.cookie("name");
		if (dojo.cookie("pagina") != "undefined")
			dijit.byId("pagina").attr("value", dojo.cookie("pagina"));
		dijit.byId("textarea_ids").attr("value", dojo.cookie("textarea"));
		dojo.attr(dijit.byId("pagina").textbox, "autocomplete", "on");
		dojo.attr(dijit.byId("ll").textbox, "autocomplete", "on");
		//dojo.attr(dijit.byId("near").textbox, "autocomplete", "on");
		dojo.attr(dijit.byId("query").textbox, "autocomplete", "on");
		if (dojo.cookie("campos") != undefined) {
			var campos = dojo.cookie("campos").split(".");
			//console.debug(campos);
			for (i = 1; i < 6; i++) {
				dijit.byId("nome" + i).attr("checked", (campos[0] === 'true'));
				dijit.byId("endereco" + i).attr("checked", (campos[1] === 'true'));
				dijit.byId("ruatransversal" + i).attr("checked", (campos[2] === 'true'));
				(dijit.byId("bairro" + i).disabled) ? dijit.byId("bairro" + i).attr("checked", false) : dijit.byId("bairro" + i).attr("checked", (campos[3] === 'true'));
				dijit.byId("cidade" + i).attr("checked", (campos[4] === 'true'));
				dijit.byId("estado" + i).attr("checked", (campos[5] === 'true'));
				dijit.byId("codigopostal" + i).attr("checked", (campos[6] === 'true'));
				(dijit.byId("dentro" + i).disabled) ? dijit.byId("dentro" + i).attr("checked", false) : dijit.byId("dentro" + i).attr("checked", (campos[7] === 'true'));
				dijit.byId("telefone" + i).attr("checked", (campos[8] === 'true'));
				dijit.byId("sitedaweb" + i).attr("checked", (campos[9] === 'true'));
				dijit.byId("twitter" + i).attr("checked", (campos[10] === 'true'));
				dijit.byId("facebook" + i).attr("checked", (campos[11] === 'true'));
				(dijit.byId("instagram" + i).disabled) ? dijit.byId("instagram" + i).attr("checked", false) : dijit.byId("instagram" + i).attr("checked", (campos[12] === 'true'));
				dijit.byId("latlng"	+ i).attr("checked", (campos[13] === 'true'));
				(dijit.byId("descricao" + i).disabled) ? dijit.byId("descricao" + i).attr("checked", false) : dijit.byId("descricao" + i).attr("checked", (campos[14] === 'true'));
				(dijit.byId("menu" + i).disabled) ? dijit.byId("menu" + i).attr("checked", false) : dijit.byId("menu" + i).attr("checked", (campos[15] === 'true'));
				if (campos.length > 17)
					dijit.byId("redes" + i).attr("checked", (campos[16] === 'true'));
				(dijit.byId("horas" + i).disabled) ? dijit.byId("horas" + i).attr("checked", false) : dijit.byId("horas" + i).attr("checked", (campos[campos.length > 17 ? 17 : 16] === 'true'));
			}
			dijit.byId("accordion").selectChild(dojo.cookie("accordion"), false);
		} else {
			for (i = 1; i < 6; i++) {
				dijit.byId("nome" + i).attr("checked", true);
				dijit.byId("endereco" + i).attr("checked", true);
				dijit.byId("ruatransversal" + i).attr("checked", true);
				dijit.byId("cidade" + i).attr("checked", true);
				dijit.byId("estado" + i).attr("checked", true);
				dijit.byId("codigopostal" + i).attr("checked", true);
				dijit.byId("telefone" + i).attr("checked", true);
				dijit.byId("sitedaweb" + i).attr("checked", true);
				dijit.byId("redes" + i).attr("checked", true);
			}
			dijit.byId("accordion").selectChild("dijit_layout_ContentPane_3", false);
		}
		if (dojo.cookie("search") != undefined) {
			var search = JSON.parse(dojo.cookie("search"));
			dijit.byId("query").attr("value", search[0]);
			dijit.byId("ll").attr("value", search[1]);
			dojo.byId("categoryId").value = search[2] || "";
			var categoryWidget = dijit.byId("categorySearchMain");
			if (categoryWidget) categoryWidget.set("value", search[2] ? (search[6] || "") : "");
			dijit.byId("radius").attr("value", search[3]);
			dijit.byId("intent").attr("value", search[4]);
			dijit.byId("limit").attr("value", search[5]);
		} else {
			dijit.byId("ll").attr("value", dojo.cookie("coordinates"));
		}
		var estadoDentroCookie = dojo.cookie("importar_dentro");
		if (estadoDentroCookie) {
			try {
				var estadoDentro = JSON.parse(estadoDentroCookie);
				var parentWidget = dijit.byId("parentSearchMain");
				var parentIdInput = dojo.byId("parentIdMain");
				if (parentWidget) parentWidget.set("value", estadoDentro.parentText || "");
				if (parentIdInput) parentIdInput.value = estadoDentro.parentId || "";
				if (estadoDentro.ll !== undefined) dijit.byId("ll5").set("value", estadoDentro.ll || "");
				if (estadoDentro.campos) {
					camposImportarDentro.forEach(function(nome) {
						var widget = dijit.byId(nome + "5");
						if (widget && !widget.disabled && Object.prototype.hasOwnProperty.call(estadoDentro.campos, nome)) {
							widget.set("checked", Boolean(estadoDentro.campos[nome]));
						}
					});
				}
			} catch (error) {
				console.error("❌ Não foi possível restaurar os dados do painel de importação de locais internos:", error);
				dojo.cookie("importar_dentro", null, { expires: -1 });
			}
		}
	});
}, 0);

function showDialogCsv() {
	// set the content of the dialog:
	dlg_csv.attr("content", `
		<p style="margin-top: 0.1em;">Planilhas eletr&ocirc;nicas, como Microsoft Excel, LibreOffice Calc e Planilhas Google, podem ser usadas para criar e editar arquivos CSV. O arquivo deve ter um cabe&ccedil;alho na primeira linha.</p>
		<p><b>Nomes de colunas reconhecidos:</b> venue, name, address, crossStreet, neighborhood, city, state, zip, parentId, phone, url, twitter, facebook, instagram, venuell, description, menu, categoryId, primaryCategoryId, addCategoryIds, removeCategoryIds, venueChainId, primaryVenueChainId, addVenueChainIds, removeVenueChainIds</p>
		<ul>
			<li><b>Conjunto de caracteres:</b> Unicode (UTF-8)</li>
			<li><b>Delimitador de campo:</b> ;</li>
			<li><b>Delimitador de texto:</b> &quot;</li>
		</ul>
		<p>O campo <b>venue</b> &eacute; obrigat&oacute;rio e deve conter o ID do local. <b>categoryId</b> e <b>venueChainId</b> s&atilde;o campos somente leitura. <b>primaryCategoryId</b> e <b>primaryVenueChainId</b> identificam a categoria e a rede principais. Os campos <b>addCategoryIds</b>, <b>removeCategoryIds</b>, <b>addVenueChainIds</b> e <b>removeVenueChainIds</b> aceitam IDs separados por v&iacute;rgulas.</p>
		<p><b>Exemplo de arquivo CSV:</b></p>
		<p style="font-family: courier, monospace;">venue;name;address;crossStreet;city;state;zip;phone;<br>&quot;4e666afcae60c9631d5e13c9&quot;;&quot;Voo Gol G3 1087&quot;;&quot;Aeroporto Salgado Filho&quot;;&quot;POA-GIG&quot;;&quot;Porto Alegre&quot;;&quot;RS&quot;;&quot;90200-310&quot;;&quot;08007040465&quot;;<br>&quot;4dda410efa76ad96d166f02c&quot;;&quot;Voo Gol G3 1241&quot;;&quot;Aeroporto Salgado Filho&quot;;&quot;POA-CGH&quot;;&quot;Porto Alegre&quot;;&quot;RS&quot;;&quot;90200-310&quot;;&quot;08007040465&quot;;</p>
	`);
	dlg_csv.show();
}

function showDialogTxt() {
	// set the content of the dialog:
	dlg_txt.attr("content", "Um arquivo de texto &eacute; uma esp&eacute;cie de arquivo simples estruturado como uma sequ&ecirc;ncia de linhas. O arquivo deve conter os identificadores de cada local a ser editado.</p><p>Os principais tipos reconhecidos s&atilde;o os seguintes:<ul><li><b>IDs ou URLs dos locais:</b> arquivo TXT com os IDs ou URLs</li><li><b>Resultado de pesquisas:</b> arquivo HTML salvo a partir do <a href='https://www.4sqmap.com' target='_blank'>4sqmap</a> ou <a href='https://foursweep.com' target='_blank'>4sweep</a></li><li><b>Arquivo de texto exportado:</b> arquivo gerado pela op&ccedil;&atilde;o <b>&quot;Exportar Arquivo TXT&quot;</b></li></ul><p><b>Exemplos v&aacute;lidos:</b></p><p style='font-family: courier, monospace;'>https://app.foursquare.com/venue/banco-do-brasil/4c701c87f23c76b0c5abe685<br>https://pt.foursquare.com/v/banco-do-brasil/4d7a895be8b7a1cdb4e3991f/<br>https://app.foursquare.com/share/venue/4d5bec09524fa35d1cdd4335<br>https://foursquare.com/v/4eadbb7902d5cf33fa8cf19e/edit<br>https://foursquare.com/venue/94562<br>4c701c87f23c76b0c5abe685</p>");
	dlg_txt.show();
}

function showDialogLks() {
	// set the content of the dialog:
	dlg_lks.attr("content", "Uma p&aacute;gina web &eacute; qualquer documento ou recurso de informa&ccedil;&atilde;o que pode ser acessado atrav&eacute;s de um navegador na Internet. Ela deve ser <b>p&uacute;blica</b> e conter os identificadores de cada local a ser editado.</p><p><b>Requisitos importantes:</b></p><ul><li>A p&aacute;gina deve ser <b>acess&iacute;vel sem login ou autentica&ccedil;&atilde;o</b></li><li>O site <b>n&atilde;o pode ter bloqueadores ou prote&ccedil;&otilde;es anti-bot muito restritivas</b></li><li>A p&aacute;gina deve conter links ou identificadores v&aacute;lidos de locais do Foursquare</li></ul><p>Os principais tipos de p&aacute;ginas reconhecidas s&atilde;o:<ul><li><b>P&aacute;ginas com links para locais:</b> contendo tags &lt;a href=&quot;/venue/...&quot;&gt; ou &lt;a href=&quot;/v/...&quot;&gt;</li><li><b>P&aacute;ginas com IDs dos locais:</b> formato de 24 caracteres ou 5-6 dígitos</li><li><b>Listas e estat&iacute;sticas de sites externos:</b> como <a href='https://www.4sqstat.com' target='_blank'>4sqstat.com</a></li></ul><p><b>Exemplos de endere&ccedil;os v&aacute;lidos:</b></p><p style='font-family: courier, monospace;'>https://www.4sqstat.com/newyork?category_id=4bf58dd8d48988d192941735<br>https://www.4sqstat.com/sao-paulo</p>");
	dlg_lks.show();
}

function showDialogDentro() {
	// set the content of the dialog:
	dlg_dentro.attr("content", "Informe o local pai (<i>parent</i>) para carregar todos os sublocais (<i>children</i>) que est&atilde;o dentro deste local. O local pode ser selecionado a partir de uma pesquisa ou colando diretamente a URL.</p><p>Preencha as coordenadas da regi&atilde;o ou nome da cidade correspondente caso a busca pelo local pai via geolocaliza&ccedil;&atilde;o n&atilde;o retorne os resultados desejados.</p><p><b>Exemplos v&aacute;lidos:</b></p><p style='font-family: courier, monospace;'>https://app.foursquare.com/v/parkshoppingcanoas/5a177d2ce1f0aa75261b40b6<br>5a177d2ce1f0aa75261b40b6<br>ParkShoppingCanoas</p>");
	dlg_dentro.show();
}

function showDialogIds() {
	// set the content of the dialog:
	dlg_ids.attr("content", "O conte&uacute;do copiado e colado deve conter os identificadores de cada local a ser editado. Informe um local por linha ou separe v&aacute;rios por espa&ccedil;os, v&iacute;rgulas ou ponto e v&iacute;rgula. Acima de 4000 caracteres, utilize a ferramenta de <b>&quot;Importar lista de um arquivo de texto simples&quot;</b>.</p><p>Os seguintes formatos s&atilde;o aceitos:</p><ul><li><b>IDs dos locais:</b> 24 caracteres hexadecimais ou 5-6 d&iacute;gitos num&eacute;ricos</li><li><b>URLs completas:</b> diversos formatos de URLs do Foursquare</li></ul><p><b>Exemplos v&aacute;lidos:</b></p><p style='font-family: courier, monospace;'>https://app.foursquare.com/venue/banco-do-brasil/4c701c87f23c76b0c5abe685<br>https://pt.foursquare.com/v/banco-do-brasil/4d7a895be8b7a1cdb4e3991f/<br>https://app.foursquare.com/share/venue/4d5bec09524fa35d1cdd4335<br>https://foursquare.com/v/4eadbb7902d5cf33fa8cf19e/edit<br>https://foursquare.com/venue/94562<br>4c701c87f23c76b0c5abe685</p>");
	dlg_ids.show();
}
