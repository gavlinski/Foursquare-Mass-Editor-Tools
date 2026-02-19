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

dojo.addOnLoad(function() {
	dojox.image.preload(["js/dijit/themes/claro/images/progressBarFull.png", "js/dijit/themes/claro/images/progressBarAnim.gif"]);
	dlg_csv = new dijit.Dialog({
		title: "Arquivo CSV",
		style: "width: 570px"
	});
	var arquivo_csv = dojo.byId("arquivo_csv");
	var arquivo_nome = dojo.cookie("arquivo_csv_nome") || ""; // Restaura do cookie se existir
	var arquivo_csv_data = null; // Guarda dados do arquivo selecionado
	var uploader_csv = dijit.byId("uploader_csv");
	var form_csv = dijit.byId("f_csv");
	dojo.connect(uploader_csv, "onChange", function (data) {
		arquivo_csv.innerHTML = data[0].name + " (" + Math.ceil(data[0].size * .001) + " kB)";
		arquivo_nome = data[0].name;
		arquivo_csv_data = data[0]; // Salva dados para restaurar depois
		// Salva em cookie para persistir após navegação
		dojo.cookie("arquivo_csv_nome", data[0].name, { expires: 1 });
		dojo.cookie("arquivo_csv_size", data[0].size, { expires: 1 });
		console.log("💾 Arquivo CSV salvo em cookie: " + data[0].name);
	});
	dojo.connect(form_csv, "onSubmit", function(e) {
		if (form_csv.validate()) {
			// Verifica se arquivo foi selecionado ou restaurado
			var nomeArquivo = arquivo_nome || window.arquivo_csv_nome_global || "";
			
			if (arquivo_csv.innerHTML === "Nenhum arquivo selecionado") {
				e.preventDefault();
				alert("O arquivo precisa ser escolhido");
				uploader_csv.inputNode.focus();
			} else if (nomeArquivo && nomeArquivo.split('.').pop() != "csv") {
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
	var arquivo_txt_data = null; // Guarda dados do arquivo selecionado
	var uploader_txt = dijit.byId("uploader_txt");
	var form_txt = dijit.byId("f_txt");
	dojo.connect(uploader_txt, "onChange", function (data) {
		arquivo_txt.innerHTML = data[0].name + " (" + Math.ceil(data[0].size * .001) + " kB)";
		arquivo_txt_data = data[0]; // Salva dados para restaurar depois
		// Salva em cookie para persistir após navegação
		dojo.cookie("arquivo_txt_nome", data[0].name, { expires: 1 });
		dojo.cookie("arquivo_txt_size", data[0].size, { expires: 1 });
		console.log("💾 Arquivo TXT salvo em cookie: " + data[0].name);
	});
	dojo.connect(form_txt, "onSubmit", function(e) {
		if (form_txt.validate()) {
			if (arquivo_txt.innerHTML === "Nenhum arquivo selecionado") {
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
		var campos1 = dijit.byId("nome1").checked + "." + dijit.byId("endereco1").checked + "." + dijit.byId("ruatransversal1").checked + "." + dijit.byId("bairro1").checked + "." + dijit.byId("cidade1").checked + "." + dijit.byId("estado1").checked + "." + dijit.byId("codigopostal1").checked + "." + dijit.byId("dentro1").checked + "." + dijit.byId("telefone1").checked + "." + dijit.byId("sitedaweb1").checked + "." + dijit.byId("twitter1").checked + "." + dijit.byId("facebook1").checked + "." + dijit.byId("instagram1").checked + "." + dijit.byId("latlng1").checked + "." + dijit.byId("descricao1").checked + "." + dijit.byId("menu1").checked + "." + dijit.byId("horas1").checked;
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
		var campos2 = dijit.byId("nome2").checked + "." + dijit.byId("endereco2").checked + "." + dijit.byId("ruatransversal2").checked + "." + dijit.byId("bairro2").checked + "." + dijit.byId("cidade2").checked + "." + dijit.byId("estado2").checked + "." + dijit.byId("codigopostal2").checked + "." + dijit.byId("dentro2").checked + "." + dijit.byId("telefone2").checked + "." + dijit.byId("sitedaweb2").checked + "." + dijit.byId("twitter2").checked + "." + dijit.byId("facebook2").checked + "." + dijit.byId("instagram2").checked + "." + dijit.byId("latlng2").checked + "." + dijit.byId("descricao2").checked + "." + dijit.byId("menu2").checked + "." + dijit.byId("horas2").checked;
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
		var campos3 = dijit.byId("nome3").checked + "." + dijit.byId("endereco3").checked + "." + dijit.byId("ruatransversal3").checked + "." + dijit.byId("bairro3").checked + "." + dijit.byId("cidade3").checked + "." + dijit.byId("estado3").checked + "." + dijit.byId("codigopostal3").checked + "." + dijit.byId("dentro3").checked + "." + dijit.byId("telefone3").checked + "." + dijit.byId("sitedaweb3").checked + "." + dijit.byId("twitter3").checked + "." + dijit.byId("facebook3").checked + "." + dijit.byId("instagram3").checked + "." + dijit.byId("latlng3").checked + "." + dijit.byId("descricao3").checked + "." + dijit.byId("menu3").checked + "." + dijit.byId("horas3").checked;
		dojo.cookie("campos", campos3, { expires: 15 });
		dojo.cookie("accordion", dijit.byId("accordion").selectedChildWidget.id, { expires: 15 });
	});
	
	var form_src = dijit.byId("f_src");
	var oauth_src = dojo.byId("oauth_token_src");
	var ll_src = dijit.byId("ll");
	dojo.connect(form_src, "onSubmit", function(e) {
		// Se campo ll estiver vazio, preenche com coordenadas do último check-in
		if (ll_src.value == "" || ll_src.value.trim() == "") {
			var lastCheckCoords = dojo.cookie("coordinates");
			
			if (lastCheckCoords && lastCheckCoords !== "undefined" && lastCheckCoords.trim() !== "") {
				console.log("✅ Preenchendo campo Local com coordenadas: " + lastCheckCoords);
				ll_src.value = lastCheckCoords;
				dijit.byId("ll").attr("value", lastCheckCoords);
			} else {
				console.log("⚠️ Campo Local é obrigatório");
				e.preventDefault();
				alert("O campo Local é obrigatório. Informe as coordenadas (ex: -29.93,-51.16) ou um endereço.");
				ll_src.focus();
				return;
			}
		}
		
		// Preenche radius com valor padrão se estiver vazio
		if (!dijit.byId("radius").value || dijit.byId("radius").value == "") {
			dijit.byId("radius").attr("value", "5000");
		}
		
		if (form_src.validate()) {
			//} else if (dojo.query('input:checked', 'f_src').length == 0) {
				//e.preventDefault();
				//alert("Selecione pelo menos um dos campos");
				//dijit.byId("nome4").focus();
			//} else {
				//alert("Ready to submit data: " + dojo.toJson(form_src.attr("value")));
			//}
		} else {
			e.preventDefault();
		}
		var campos4 = dijit.byId("nome4").checked + "." + dijit.byId("endereco4").checked + "." + dijit.byId("ruatransversal4").checked + ".";
		(dijit.byId("bairro4").disabled) ? campos4 += "false." : campos4 += dijit.byId("bairro4").checked + ".";
		campos4 += dijit.byId("cidade4").checked + "." + dijit.byId("estado4").checked + "." + dijit.byId("codigopostal4").checked + ".";
		(dijit.byId("dentro4").disabled) ? campos4 += "false." : campos4 += dijit.byId("dentro4").checked + ".";
		campos4 += dijit.byId("telefone4").checked + "." + dijit.byId("sitedaweb4").checked + "." + dijit.byId("twitter4").checked + "." + dijit.byId("facebook4").checked + ".";
		//(dijit.byId("facebook4").disabled) ? campos4 += "false." : campos4 += dijit.byId("facebook4").checked + ".";
		(dijit.byId("instagram4").disabled) ? campos4 += "false." : campos4 += dijit.byId("instagram4").checked + ".";
		campos4 += dijit.byId("latlng4").checked + ".";
		(dijit.byId("descricao4").disabled) ? campos4 += "false." : campos4 += dijit.byId("descricao4").checked + ".";
		(dijit.byId("menu4").disabled) ? campos4 += "false." : campos4 += dijit.byId("menu4").checked + ".";
		(dijit.byId("horas4").disabled) ? campos4 += "false." : campos4 += dijit.byId("horas4").checked;
		
		var search = [dijit.byId("query").value, dijit.byId("ll").value, dijit.byId("categoryId").value, dijit.byId("radius").value, dijit.byId("intent").value, dijit.byId("limit").value];
		dojo.cookie("search", JSON.stringify(search), { expires: 15 });
		dojo.cookie("campos", campos4, { expires: 15 });
		dojo.cookie("accordion", dijit.byId("accordion").selectedChildWidget.id, { expires: 15 });

	});
});

dojo.ready(function() {
	setTimeout(function() {
		// Detecta tipo de navegação usando API moderna (com fallback para deprecated)
		var isBackNavigation = false;
		var navEntries = performance.getEntriesByType('navigation');
		
		if (navEntries && navEntries.length > 0) {
			// API moderna: type pode ser "navigate", "reload", "back_forward", "prerender"
			isBackNavigation = (navEntries[0].type === 'back_forward');
			console.log("🔍 Tipo de navegação (moderna):", navEntries[0].type);
		} else if (performance.navigation) {
			// Fallback para API deprecated: 0=normal, 1=reload, 2=back/forward
			isBackNavigation = (performance.navigation.type === 2);
			console.log("🔍 Tipo de navegação (deprecated):", performance.navigation.type);
		}
		
		// Se for reload (F5) ou navegação normal, limpa os cookies de arquivo
		if (!isBackNavigation) {
			console.log("🔄 Refresh detectado - limpando cookies de arquivo");
			dojo.cookie("arquivo_csv_nome", null, { expires: -1 });
			dojo.cookie("arquivo_csv_size", null, { expires: -1 });
			dojo.cookie("arquivo_txt_nome", null, { expires: -1 });
			dojo.cookie("arquivo_txt_size", null, { expires: -1 });
		} else {
			console.log("⬅️ Navegação Voltar detectada - restaurando arquivos");
			
			// Restaura informação do arquivo CSV se existir cookie
			var csvNome = dojo.cookie("arquivo_csv_nome");
			var csvSize = dojo.cookie("arquivo_csv_size");
			if (csvNome && csvSize) {
				var arquivo_csv = dojo.byId("arquivo_csv");
				if (arquivo_csv) {
					arquivo_csv.innerHTML = csvNome + " (" + Math.ceil(csvSize * .001) + " kB)";
					// Atualiza também a variável arquivo_nome para permitir validação
					window.arquivo_csv_nome_global = csvNome;
					console.log("📋 Arquivo CSV restaurado do cookie: " + csvNome);
				}
			}
			
			// Restaura informação do arquivo TXT se existir cookie
			var txtNome = dojo.cookie("arquivo_txt_nome");
			var txtSize = dojo.cookie("arquivo_txt_size");
			if (txtNome && txtSize) {
				var arquivo_txt = dojo.byId("arquivo_txt");
				if (arquivo_txt) {
					arquivo_txt.innerHTML = txtNome + " (" + Math.ceil(txtSize * .001) + " kB)";
					// Atualiza também a variável arquivo_txt para permitir validação
					window.arquivo_txt_nome_global = txtNome;
					console.log("📋 Arquivo TXT restaurado do cookie: " + txtNome);
				}
			}
		}
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
			for (i = 1; i < 5; i++) {
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
				(dijit.byId("horas" + i).disabled) ? dijit.byId("horas" + i).attr("checked", false) : dijit.byId("horas" + i).attr("checked", (campos[16] === 'true'));
			}
			dijit.byId("accordion").selectChild(dojo.cookie("accordion"), false);
		} else {
			for (i = 1; i < 5; i++) {
				dijit.byId("nome" + i).attr("checked", true);
				dijit.byId("endereco" + i).attr("checked", true);
				dijit.byId("ruatransversal" + i).attr("checked", true);
				dijit.byId("cidade" + i).attr("checked", true);
				dijit.byId("estado" + i).attr("checked", true);
				dijit.byId("codigopostal" + i).attr("checked", true);
				dijit.byId("telefone" + i).attr("checked", true);
				dijit.byId("sitedaweb" + i).attr("checked", true);
			}
			dijit.byId("accordion").selectChild("dijit_layout_ContentPane_3", false);
		}
		if (dojo.cookie("search") != undefined) {
			var search = JSON.parse(dojo.cookie("search"));
			dijit.byId("query").attr("value", search[0]);
			dijit.byId("ll").attr("value", search[1]);
			dijit.byId("categoryId").attr("value", search[2]);
			dijit.byId("radius").attr("value", search[3]);
			dijit.byId("intent").attr("value", search[4]);
			dijit.byId("limit").attr("value", search[5]);
		} else {
			dijit.byId("ll").attr("value", dojo.cookie("coordinates"));
		}
	});
}, 0);

function showDialogCsv() {
	// set the content of the dialog:
	//dlg_csv.attr("content", "Os programas de planilha eletr&ocirc;nica, como o Microsoft Excel, o LibreOffice Calc e o Planilhas Google, facilitam a cria&ccedil;&atilde;o e a edi&ccedil;&atilde;o de arquivos CSV. Ele deve ser formatado como uma tabela e deve incluir um cabe&ccedil;alho, ou primeira linha, que defina os campos nessa tabela.<p>Veja alguns detalhes importantes que devem ser considerados durante a cria&ccedil;&atilde;o do arquivo:<ul><li><b>Cabe&ccedil;alho:</b> venue, name, address, crossStreet, neighborhood, city, state, zip, parentId, phone, url, twitter, facebook, instagram, venuell, description, menu, hours, categoryId, primaryCategoryId, addCategoryIds, removeCategoryIds</li><li><b>Conjunto de caracteres:</b> Unicode (UTF-8)</li><li><b>Delimitador de campo:</b> ;</li><li><b>Delimitador de texto:</b> &quot;</li></ul><p>O campo <b>venue</b> &eacute; o &uacute;nico obrigat&oacute;rio e deve conter o ID da venue. O campo <b>categoryId</b> é somente leitura. Os campos <b>primaryCategoryId</b>, <b>addCategoryIds</b> e <b>removeCategoryIds</b> devem conter os IDs das categorias da venue separados por v&iacute;rgulas.</p><p><b>Exemplo de arquivo CSV:</b></p><p style='font-family: courier, monospace;'>venue;name;address;crossStreet;city;state;zip;phone;<br>&quot;4e666afcae60c9631d5e13c9&quot;;&quot;Voo Gol G3 1087&quot;;&quot;Aeroporto Salgado Filho&quot;;&quot;POA-GIG&quot;;&quot;Porto Alegre&quot;;&quot;RS&quot;;&quot;90200-310&quot;;&quot;08007040465&quot;;<br>&quot;4dda410efa76ad96d166f02c&quot;;&quot;Voo Gol G3 1241&quot;;&quot;Aeroporto Salgado Filho&quot;;&quot;POA-CGH&quot;;&quot;Porto Alegre&quot;;&quot;RS&quot;;&quot;90200-310&quot;;&quot;08007040465&quot;;</p>");
	dlg_csv.attr("content", "Os programas de planilha eletr&ocirc;nica, como o Microsoft Excel, o LibreOffice Calc e o Planilhas Google, facilitam a cria&ccedil;&atilde;o e a edi&ccedil;&atilde;o de arquivos CSV. Ele deve ser formatado como uma tabela e deve incluir um cabe&ccedil;alho, ou primeira linha, que defina os campos nessa tabela.<p>Veja alguns detalhes importantes que devem ser considerados durante a cria&ccedil;&atilde;o do arquivo:<ul><li><b>Cabe&ccedil;alho:</b> venue, name, address, crossStreet, neighborhood, city, state, zip, parentId, phone, url, twitter, facebook, instagram, venuell, description, menu, categoryId, primaryCategoryId, addCategoryIds, removeCategoryIds</li><li><b>Conjunto de caracteres:</b> Unicode (UTF-8)</li><li><b>Delimitador de campo:</b> ;</li><li><b>Delimitador de texto:</b> &quot;</li></ul><p>O campo <b>venue</b> &eacute; o &uacute;nico obrigat&oacute;rio e deve conter o ID da venue. O campo <b>categoryId</b> é somente leitura. Os campos <b>primaryCategoryId</b>, <b>addCategoryIds</b> e <b>removeCategoryIds</b> devem conter os IDs das categorias da venue separados por v&iacute;rgulas.</p><p><b>Exemplo de arquivo CSV:</b></p><p style='font-family: courier, monospace;'>venue;name;address;crossStreet;city;state;zip;phone;<br>&quot;4e666afcae60c9631d5e13c9&quot;;&quot;Voo Gol G3 1087&quot;;&quot;Aeroporto Salgado Filho&quot;;&quot;POA-GIG&quot;;&quot;Porto Alegre&quot;;&quot;RS&quot;;&quot;90200-310&quot;;&quot;08007040465&quot;;<br>&quot;4dda410efa76ad96d166f02c&quot;;&quot;Voo Gol G3 1241&quot;;&quot;Aeroporto Salgado Filho&quot;;&quot;POA-CGH&quot;;&quot;Porto Alegre&quot;;&quot;RS&quot;;&quot;90200-310&quot;;&quot;08007040465&quot;;</p>");
	dlg_csv.show();
}

function showDialogTxt() {
	// set the content of the dialog:
	dlg_txt.attr("content", "Um arquivo de texto &eacute; uma esp&eacute;cie de arquivo simples estruturado como uma sequ&ecirc;ncia de linhas. O arquivo deve conter os identificadores de cada local a ser editado.</p><p>Os principais tipos reconhecidos s&atilde;o os seguintes:<ul><li><b>IDs ou URLs dos locais:</b> arquivo TXT com os IDs ou URLs</li><li><b>Resultado de pesquisas:</b> arquivo HTML salvo a partir do <a href='http://www.4sqmap.com' target='_blank'>4sqmap</a> ou <a href='https://foursweep.com' target='_blank'>4sweep</a></li><li><b>Arquivo de texto exportado:</b> arquivo gerado pela op&ccedil;&atilde;o <b>&quot;Exportar Arquivo TXT&quot;</b></li></ul><p><b>Exemplos v&aacute;lidos:</b></p><p style='font-family: courier, monospace;'>https://app.foursquare.com/venue/banco-do-brasil/4c701c87f23c76b0c5abe685<br>https://pt.foursquare.com/v/banco-do-brasil/4d7a895be8b7a1cdb4e3991f/<br>https://foursquare.com/v/4eadbb7902d5cf33fa8cf19e/edit<br>http://foursquare.com/venue/94562<br>4c701c87f23c76b0c5abe685</p>");
	dlg_txt.show();
}

function showDialogLks() {
	// set the content of the dialog:
	dlg_lks.attr("content", "Uma p&aacute;gina web &eacute; qualquer documento ou recurso de informa&ccedil;&atilde;o que pode ser acessado atrav&eacute;s de um navegador na Internet. Ela deve ser <b>p&uacute;blica</b> e conter os identificadores de cada local a ser editado.</p><p><b>Requisitos importantes:</b></p><ul><li>A p&aacute;gina deve ser <b>acess&iacute;vel sem login ou autentica&ccedil;&atilde;o</b></li><li>O site <b>n&atilde;o pode ter bloqueadores ou prote&ccedil;&otilde;es anti-bot muito restritivas</b></li><li>A p&aacute;gina deve conter links ou identificadores v&aacute;lidos de locais do Foursquare</li></ul><p>Os principais tipos de p&aacute;ginas reconhecidas s&atilde;o:<ul><li><b>P&aacute;ginas com links para locais:</b> contendo tags &lt;a href=&quot;/venue/...&quot;&gt; ou &lt;a href=&quot;/v/...&quot;&gt;</li><li><b>P&aacute;ginas com IDs dos locais:</b> formato de 24 caracteres ou 5-6 dígitos</li><li><b>Listas e estat&iacute;sticas de sites externos:</b> como <a href='https://www.4sqstat.com' target='_blank'>4sqstat.com</a></li></ul><p><b>Exemplos de endere&ccedil;os v&aacute;lidos:</b></p><p style='font-family: courier, monospace;'>https://www.4sqstat.com/newyork?category_id=4bf58dd8d48988d192941735<br>https://www.4sqstat.com/sao-paulo</p>");
	dlg_lks.show();
}

function showDialogIds() {
	// set the content of the dialog:
	dlg_ids.attr("content", "O conte&uacute;do copiado e colado deve conter os identificadores de cada local a ser editado. Acima de 4000 caracteres, utilize a ferramenta de <b>&quot;Importar lista de um arquivo de texto simples&quot;</b>.</p><p>Os seguintes formatos s&atilde;o aceitos:</p><ul><li><b>IDs dos locais:</b> 24 caracteres hexadecimais ou 5-6 d&iacute;gitos num&eacute;ricos</li><li><b>URLs completas:</b> diversos formatos de URLs do Foursquare</li></ul><p><b>Exemplos v&aacute;lidos:</b></p><p style='font-family: courier, monospace;'>https://app.foursquare.com/venue/banco-do-brasil/4c701c87f23c76b0c5abe685<br>https://pt.foursquare.com/v/banco-do-brasil/4d7a895be8b7a1cdb4e3991f/<br>https://foursquare.com/v/4eadbb7902d5cf33fa8cf19e/edit<br>http://foursquare.com/venue/94562<br>4c701c87f23c76b0c5abe685</p>");
	dlg_ids.show();
}
