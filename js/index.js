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

dojo.addOnLoad(function() {
	dlg_csv = new dijit.Dialog({
		title: "Arquivo CSV",
		style: "width: 570px"
	});
	var arquivo_csv = dojo.byId("arquivo_csv");
	var arquivo_nome = "";
	var uploader_csv = dijit.byId("uploader_csv");
	var form_csv = dijit.byId("f_csv");
	var oauth_csv = dijit.byId("oauth_token_csv");
	dojo.connect(uploader_csv, "onChange", function (data) {
		arquivo_csv.innerHTML = data[0].name + " (" + Math.ceil(data[0].size * .001) + " kB)";
		arquivo_nome = data[0].name;
	});
	dojo.connect(form_csv, "onSubmit", function(e) {
		if (form_csv.validate()) {
			if (oauth_csv.get("value").length != 48) {
				e.preventDefault();
				alert("O OAuth token deve possuir 48 caracteres");
				oauth_csv.focus();
			} else if (arquivo_csv.innerHTML === "Nenhum arquivo selecionado") {
				e.preventDefault();
				alert("O arquivo precisa ser escolhido");
				uploader_csv.inputNode.focus();
			} else if (arquivo_nome.split('.').pop() != "csv") {
				e.preventDefault();
				alert("O arquivo deve ser do tipo CSV");
				uploader_csv.inputNode.focus();
			//} else {
				//alert("Ready to submit data: " + dojo.toJson(form_csv.attr("value")));
			}
		} else {
			e.preventDefault();
		}
		dojo.cookie("oauth_token", dijit.byId("oauth_token_csv").value, { expires: 15 });
		//dojo.cookie("pagina", "", { expires: 15 });
		//dojo.cookie("textarea", "", { expires: 15 });
		dojo.cookie("accordion", dijit.byId("accordion").selectedChildWidget.id, { expires: 15 });
	});
	
	dlg_txt = new dijit.Dialog({
		title: "Arquivo de texto",
		style: "width: 570px"
	});
	var arquivo_txt = dojo.byId("arquivo_txt");
	var uploader_txt = dijit.byId("uploader_txt");
	var form_txt = dijit.byId("f_txt");
	var oauth_txt = dijit.byId("oauth_token_txt");
	dojo.connect(uploader_txt, "onChange", function (data) {
		arquivo_txt.innerHTML = data[0].name + " (" + Math.ceil(data[0].size * .001) + " kB)";
	});
	dojo.connect(form_txt, "onSubmit", function(e) {
		if (form_txt.validate()) {
			if (oauth_txt.get("value").length != 48) {
				e.preventDefault();
				alert("O OAuth token deve possuir 48 caracteres");
				oauth_txt.focus();
			} else if (arquivo_txt.innerHTML === "Nenhum arquivo selecionado") {
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
		dojo.cookie("oauth_token", dijit.byId("oauth_token_txt").value, { expires: 15 });
		//dojo.cookie("pagina", "", { expires: 15 });
		//dojo.cookie("textarea", "", { expires: 15 });
		dojo.cookie("campos", dijit.byId("nome1").checked + "." + dijit.byId("endereco1").checked + "." + dijit.byId("ruacross1").checked + "." + dijit.byId("cidade1").checked + "." + dijit.byId("estado1").checked + "." + dijit.byId("cep1").checked + "." + dijit.byId("twitter1").checked + "." + dijit.byId("telefone1").checked + "." + dijit.byId("website1").checked + "." + dijit.byId("descricao1").checked + "." + dijit.byId("latlong1").checked, { expires: 15 });
		dojo.cookie("accordion", dijit.byId("accordion").selectedChildWidget.id, { expires: 15 });
	});
	
	dlg_lks = new dijit.Dialog({
		title: "Endere&ccedil;o de uma p&aacute;gina web",
		style: "width: 650px"
	});
	var form_lks = dijit.byId("f_lks");
	var oauth_lks = dijit.byId("oauth_token_lks");
	var pagina = dijit.byId("pagina");
	dojo.connect(form_lks, "onSubmit", function(e) {
		if (form_lks.validate()) {
			if (oauth_lks.get("value").length != 48) {
				e.preventDefault();
				alert("O OAuth token deve possuir 48 caracteres");
				oauth_lks.focus();
			//} else if (dojo.query('input:checked', 'f_lks').length == 0) {
				//e.preventDefault();
				//alert("Selecione pelo menos um dos campos");
				//dijit.byId("nome2").focus();
			//} else {
				//alert("Ready to submit data: " + dojo.toJson(form_lks.attr("value")));
			}
		} else {
			e.preventDefault();
		}
		dojo.cookie("oauth_token", dijit.byId("oauth_token_lks").value, { expires: 15 });
		dojo.cookie("pagina", dijit.byId("pagina").value, { expires: 15 });
		//dojo.cookie("textarea", "", { expires: 15 });
		dojo.cookie("campos", dijit.byId("nome2").checked + "." + dijit.byId("endereco2").checked + "." + dijit.byId("ruacross2").checked + "." + dijit.byId("cidade2").checked + "." + dijit.byId("estado2").checked + "." + dijit.byId("cep2").checked + "." + dijit.byId("twitter2").checked + "." + dijit.byId("telefone2").checked + "." + dijit.byId("website2").checked + "." + dijit.byId("descricao2").checked + "." + dijit.byId("latlong2").checked, { expires: 15 });
		dojo.cookie("accordion", dijit.byId("accordion").selectedChildWidget.id, { expires: 15 });
	});
	
	dlg_ids = new dijit.Dialog({
		title: "IDs ou URLs das venues",
		style: "width: 570px"
	});
	var form_ids = dijit.byId("f_ids");
	var oauth_ids = dijit.byId("oauth_token_ids");
	var textarea_ids = dijit.byId("textarea_ids");
	dojo.connect(form_ids, "onSubmit", function(e) {
		if (form_ids.validate()) {
			if (oauth_ids.get("value").length != 48) {
				e.preventDefault();
				alert("O OAuth token deve possuir 48 caracteres");
				oauth_ids.focus();
			} else if (textarea_ids.value == "") {
				e.preventDefault();
				alert("Informe pelo menos uma venue");
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
		dojo.cookie("oauth_token", dijit.byId("oauth_token_ids").value, { expires: 15 });
		//dojo.cookie("pagina", "", { expires: 15 });
		dojo.cookie("textarea", dijit.byId("textarea_ids").value, { expires: 15 });
		dojo.cookie("campos", dijit.byId("nome3").checked + "." + dijit.byId("endereco3").checked + "." + dijit.byId("ruacross3").checked + "." + dijit.byId("cidade3").checked + "." + dijit.byId("estado3").checked + "." + dijit.byId("cep3").checked + "." + dijit.byId("twitter3").checked + "." + dijit.byId("telefone3").checked + "." + dijit.byId("website3").checked + "." + dijit.byId("descricao3").checked + "." + dijit.byId("latlong3").checked, { expires: 15 });
		dojo.cookie("accordion", dijit.byId("accordion").selectedChildWidget.id, { expires: 15 });
	});
	
	var form_src = dijit.byId("f_src");
	var oauth_src = dijit.byId("oauth_token_src");
	var ll_src = dijit.byId("ll");
	dojo.connect(form_src, "onSubmit", function(e) {
		if (form_src.validate()) {
			if (oauth_src.get("value").length != 48) {
				e.preventDefault();
				alert("O OAuth token deve possuir 48 caracteres");
				oauth_src.focus();
			} else if (ll_src.value == "") {
				e.preventDefault();
				alert("Informe as coordenadas");
				ll_src.focus();
			//} else if (dojo.query('input:checked', 'f_ids').length == 0) {
				//e.preventDefault();
				//alert("Selecione pelo menos um dos campos");
				//dijit.byId("nome3").focus();
			} else {
				alert("Ready to submit data: " + dojo.toJson(form_src.attr("value")));
			}
		} else {
			e.preventDefault();
		}
		dojo.cookie("oauth_token", dijit.byId("oauth_token_src").value, { expires: 15 });
		//dojo.cookie("pagina", "", { expires: 15 });
		//dojo.cookie("textarea", "", { expires: 15 });
		//dojo.cookie("ll", dijit.byId("ll").value, { expires: 15 });
		dojo.cookie("campos", dijit.byId("nome4").checked + "." + dijit.byId("endereco4").checked + "." + dijit.byId("ruacross4").checked + "." + dijit.byId("cidade4").checked + "." + dijit.byId("estado4").checked + "." + dijit.byId("cep4").checked + "." + dijit.byId("twitter4").checked + "." + dijit.byId("telefone4").checked + "." + dijit.byId("website4").checked + "." + dijit.byId("descricao4").checked + "." + dijit.byId("latlong4").checked, { expires: 15 });
		dojo.cookie("accordion", dijit.byId("accordion").selectedChildWidget.id, { expires: 15 });
	});
});

dojo.ready(function() {
	setTimeout(function() {
		dijit.byId("oauth_token_csv").attr("value", dojo.cookie("oauth_token"));
		dijit.byId("oauth_token_txt").attr("value", dojo.cookie("oauth_token"));
		dijit.byId("oauth_token_lks").attr("value", dojo.cookie("oauth_token"));
		dijit.byId("oauth_token_src").attr("value", dojo.cookie("oauth_token"));
		dijit.byId("pagina").attr("value", dojo.cookie("pagina"));
		dijit.byId("textarea_ids").attr("value", dojo.cookie("textarea"));
		dijit.byId("oauth_token_ids").attr("value", dojo.cookie("oauth_token"));
		dojo.attr(dijit.byId("pagina").textbox, "autocomplete", "on");
		dojo.attr(dijit.byId("ll").textbox, "autocomplete", "on");
		if (dojo.cookie("campos") != undefined) {
			var campos = dojo.cookie("campos").split(".");
			//console.debug(campos);
			for (i = 1; i < 5; i++) {
				dijit.byId("nome" + i).attr("checked", (campos[0] === 'true'));
				dijit.byId("endereco" + i).attr("checked", (campos[1] === 'true'));
				dijit.byId("ruacross" + i).attr("checked", (campos[2] === 'true'));
				dijit.byId("cidade" + i).attr("checked", (campos[3] === 'true'));
				dijit.byId("estado" + i).attr("checked", (campos[4] === 'true'));
				dijit.byId("cep" + i).attr("checked", (campos[5] === 'true'));
				dijit.byId("twitter" + i).attr("checked", (campos[6] === 'true'));
				dijit.byId("telefone" + i).attr("checked", (campos[7] === 'true'));
				dijit.byId("website" + i).attr("checked", (campos[8] === 'true'));
				dijit.byId("descricao" + i).attr("checked", (campos[9] === 'true'));
				dijit.byId("latlong"	+ i).attr("checked", (campos[10] === 'true'));
			}
			dijit.byId("accordion").selectChild(dojo.cookie("accordion"), false);
		} else {
			for (i = 1; i < 5; i++) {
				dijit.byId("nome" + i).attr("checked", true);
				dijit.byId("endereco" + i).attr("checked", true);
				dijit.byId("ruacross" + i).attr("checked", true);
				dijit.byId("cidade" + i).attr("checked", true);
				dijit.byId("estado" + i).attr("checked", true);
				dijit.byId("cep" + i).attr("checked", true);
				dijit.byId("twitter" + i).attr("checked", true);
				dijit.byId("telefone" + i).attr("checked", true);
			}
			dijit.byId("accordion").selectChild("dijit_layout_ContentPane_3", false);
		} 
	});
}, 0);

function showDialogCsv() {
	// set the content of the dialog:
	dlg_csv.attr("content", "Os programas de planilha eletr&ocirc;nica, como o Microsoft Excel, o BrOffice Calc e o Google Spreadsheets, facilitam a cria&ccedil;&atilde;o e a edi&ccedil;&atilde;o de arquivos CSV. Ele deve ser formatado como uma tabela e deve incluir um cabe&ccedil;alho, ou primeira linha, que defina os campos nessa tabela.<p>Veja alguns detalhes importantes que devem ser considerados durante a cria&ccedil;&atilde;o do arquivo:<ul><li><b>Cabe&ccedil;alho:</b> venue, name, address, crossStreet, city, state, zip, twitter, phone, url, description, ll, categoryId</li><li><b>Conjunto de caracteres:</b> Europa ocidental (ISO-8859-1)</li><li><b>Delimitador de campo:</b> ;</li><li><b>Delimitador de texto:</b> &quot;</li></ul><p>O campo <b>venue</b> &eacute; o &uacute;nico obrigat&oacute;rio e deve conter o ID da venue. O campo <b>categoryId</b> deve conter os IDs das categorias da venue separados por v&iacute;rgula.</p><p><b>Exemplo de arquivo CSV:</b></p><p style='font-family: courier, monospace;'>venue;name;address;crossStreet;city;state;zip;phone;<br>&quot;4e666afcae60c9631d5e13c9&quot;;&quot;Voo Gol G3 1087&quot;;&quot;Aeroporto Salgado Filho&quot;;&quot;POA-GIG&quot;;&quot;Porto Alegre&quot;;&quot;RS&quot;;&quot;90200-310&quot;;&quot;08007040465&quot;;<br>&quot;4dda410efa76ad96d166f02c&quot;;&quot;Voo Gol G3 1241&quot;;&quot;Aeroporto Salgado Filho&quot;;&quot;POA-CGH&quot;;&quot;Porto Alegre&quot;;&quot;RS&quot;;&quot;90200-310&quot;;&quot;08007040465&quot;;</p>");
	dlg_csv.show();
}

function showDialogTxt() {
	// set the content of the dialog:
	dlg_txt.attr("content", "Um arquivo de texto &eacute; uma esp&eacute;cie de arquivo simples estruturado como uma sequ&ecirc;ncia de linhas. O arquivo deve conter os identificadores de cada venue a ser editada.</p><p>Os principais tipos reconhecidos s&atilde;o os seguintes:<ul><li><b>IDs ou URLs das venues:</b> arquivo TXT com os IDs ou URLs das venues</li><li><b>Resultado da pesquisa do 4sqmap:</b> arquivo HTML salvo a partir do <a href='http://www.4sqmap.com'>4sqmap</a></li><li><b>Resultado da pesquisa do Tidysquare:</b> arquivo HTML salvo a partir do <a href='http://www.tidysquare.com'>Tidysquare</a></li></ul><p><b>Exemplo de arquivo TXT:</b></p><p style='font-family: courier, monospace;'>https://foursquare.com/venue/banco-do-brasil/4c701c87f23c76b0c5abe685<br>https://pt.foursquare.com/v/banco-do-brasil/4d7a895be8b7a1cdb4e3991f/<br>https://foursquare.com/v/4eadbb7902d5cf33fa8cf19e/edit<br>4e764d9888775d593e595c62</p>");
	dlg_txt.show();
}

function showDialogLks() {
	// set the content of the dialog:
	dlg_lks.attr("content", "Uma p&aacute;gina web &eacute; qualquer documento ou recurso de informa&ccedil;&atilde;o que pode ser acessado atrav&eacute;s de um navegador na Internet. Ela deve ser p&uacute;blica e conter os identificadores de cada venue a ser editada.</p><p>Os principais endere&ccedil;os reconhecidos s&atilde;o os seguintes:<ul><li><b>P&aacute;ginas com links para as venues:</b> com a tag &lt;a href=&quot;/venue/...&quot;&gt; ou &lt;a href=&quot;/v/...&quot;&gt;</li><li><b>P&aacute;ginas de usu&aacute;rios com prefeituras:</b> do tipo <span style='color: #2398c9;'>https://foursquare.com/user</span></li><li><b>Listas p&uacute;blicas de usu&aacute;rios:</b> do tipo <span style='color: #2398c9;'>https://foursquare.com/user/list/list-name</span></li><li><b>Resultados da pesquisa:</b> do tipo <span style='color: #2398c9;'>https://foursquare.com/search?tab=venueResults&q=query&near=ll</span></li></ul><p><b>Exemplos de endere&ccedil;os:</b></p><p style='font-family: courier, monospace;'>https://foursquare.com/4sqcities/list/sampa-badge<br>https://pt.foursquare.com/search?tab=venueResults&q=via&near=Bras%C3%ADlia</p>");
	dlg_lks.show();
}

function showDialogIds() {
	// set the content of the dialog:
	dlg_ids.attr("content", "O conte&uacute;do copiado e colado deve conter os identificadores de cada venue a ser editada. Acima de 4000 caracteres, utilize a ferramenta de importar lista de um arquivo de texto.</p><p><b>Exemplo de ID:</b></p><p style='font-family: courier, monospace;'>4c701c87f23c76b0c5abe685</p><p><b>Exemplos de URLs:</b></p><p style='font-family: courier, monospace;'>https://foursquare.com/venue/banco-do-brasil/4c701c87f23c76b0c5abe685<br>https://pt.foursquare.com/v/banco-do-brasil/4d7a895be8b7a1cdb4e3991f/<br>https://foursquare.com/v/4eadbb7902d5cf33fa8cf19e/edit</p>");
	dlg_ids.show();
}
