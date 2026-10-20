/**
 * Autocomplete compartilhado (main.php e edit.php) baseado em
 * https://api.foursquare.com/v2/search/autocomplete
 */
var FSQ_AUTOCOMPLETE_VERSION = "20261016";
var FSQ_DEFAULT_ICON = "https://app.foursquare.com/img/categories_v2/none_bg_32.png";
var fsqWsid = "";

function fsqObterWsid() {
	if (fsqWsid) return fsqWsid;
	var caracteres = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	var bytes = new Uint8Array(30);
	if (window.crypto && window.crypto.getRandomValues) {
		window.crypto.getRandomValues(bytes);
	} else {
		for (var i = 0; i < bytes.length; i++) bytes[i] = Math.floor(Math.random() * 256);
	}
	for (var j = 0; j < bytes.length; j++) fsqWsid += caracteres.charAt(bytes[j] % caracteres.length);
	return fsqWsid;
}

function fsqExtrairIdLocal(texto) {
	var match = String(texto || "").match(/(?:^|[\/\s])([0-9a-f]{24})(?:[\/?#\s]|$)/i);
	return match ? match[1].toLowerCase() : "";
}

function fsqMontarUrlAutocomplete(query, token, params) {
	var url = "https://api.foursquare.com/v2/search/autocomplete?locale=pt&explicit-lang=false&v=" + FSQ_AUTOCOMPLETE_VERSION +
		"&query=" + encodeURIComponent(query);
	Object.keys(params || {}).forEach(function(chave) {
		if (params[chave] !== undefined && params[chave] !== null && params[chave] !== "")
			url += "&" + encodeURIComponent(chave) + "=" + encodeURIComponent(params[chave]);
	});
	return url + "&wsid=" + fsqObterWsid() + "&oauth_token=" + encodeURIComponent(token);
}

function fsqMontarUrlGeocode(query, token) {
	var params = {
		locale: "pt",
		"explicit-lang": "false",
		v: FSQ_AUTOCOMPLETE_VERSION,
		query: query,
		autocomplete: "true",
		allowCountry: "false",
		maxInterpretations: 3
	};
	var url = "https://api.foursquare.com/v2/geo/geocode?" + Object.keys(params).map(function(chave) {
		return encodeURIComponent(chave) + "=" + encodeURIComponent(params[chave]);
	}).join("&");
	return url + "&wsid=" + fsqObterWsid() + "&oauth_token=" + encodeURIComponent(token);
}

function fsqBuscarLocalPorId(id, token) {
	return new Promise(function(resolve, reject) {
		var req = new XMLHttpRequest();
		var url = "https://api.foursquare.com/v2/venues/" + encodeURIComponent(id) +
			"?v=" + FSQ_AUTOCOMPLETE_VERSION + "&oauth_token=" + encodeURIComponent(token);
		req.open("GET", url, true);
		req.onreadystatechange = function() {
			if (req.readyState !== 4) return;
			var dados;
			try {
				dados = JSON.parse(req.responseText || "");
			} catch (error) {
				reject(new Error("A API retornou uma resposta inválida ao validar o local."));
				return;
			}
			if (req.status !== 200 || !dados.meta || dados.meta.code !== 200) {
				reject(new Error(fsqDetalharErroAutocomplete(req.status, req.responseText)));
				return;
			}
			var venue = dados.response && dados.response.venue;
			if (!venue || !venue.id) {
				reject(new Error("A API não encontrou um local para o ID informado."));
				return;
			}
			resolve({ id: venue.id, name: venue.name || venue.id });
		};
		req.onerror = function() {
			reject(new Error("Não foi possível conectar à API para validar o local informado."));
		};
		req.send();
	});
}

function fsqGeocodificarPorApi(texto, token) {
	return new Promise(function(resolve, reject) {
		var req = new XMLHttpRequest();
		req.open("GET", fsqMontarUrlGeocode(texto, token), true);
		req.onreadystatechange = function() {
			if (req.readyState !== 4) return;
			var dados;
			try {
				dados = JSON.parse(req.responseText || "");
			} catch (error) {
				reject(new Error("A API de geocodificação do Foursquare retornou uma resposta inválida."));
				return;
			}
			if (req.status !== 200 || !dados.meta || dados.meta.code !== 200) {
				var meta = dados.meta || {};
				reject(new Error("A API de geocodificação do Foursquare recusou a consulta (HTTP " + req.status +
					(meta.errorDetail ? ": " + meta.errorDetail : meta.errorType ? ": " + meta.errorType : "") + ")."));
				return;
			}
			var interpretacoes = dados.response && dados.response.geocode && dados.response.geocode.interpretations;
			var itens = interpretacoes && Array.isArray(interpretacoes.items) ? interpretacoes.items : [];
			var centro = itens[0] && itens[0].feature && itens[0].feature.geometry && itens[0].feature.geometry.center;
			if (!centro || !Number.isFinite(Number(centro.lat)) || !Number.isFinite(Number(centro.lng))) {
				reject(new Error("A API de geocodificação do Foursquare não encontrou coordenadas para o endereço informado."));
				return;
			}
			resolve(Number(centro.lat) + "," + Number(centro.lng));
		};
		req.onerror = function() {
			reject(new Error("Não foi possível conectar à API de geocodificação do Foursquare."));
		};
		req.send();
	});
}

function fsqObterGrupo(response, tipo) {
	var grupos = response && Array.isArray(response.groups) ? response.groups : [];
	for (var i = 0; i < grupos.length; i++) {
		if (grupos[i].type === tipo) return Array.isArray(grupos[i].items) ? grupos[i].items : [];
	}
	return [];
}

function fsqNormalizarCategorias(response) {
	return fsqObterGrupo(response, "categories").filter(function(c) { return c && c.id; }).map(function(c) {
		var icone = c.icon && c.icon.prefix && c.icon.suffix ? c.icon.prefix + "bg_32" + c.icon.suffix : FSQ_DEFAULT_ICON;
		return { id: c.id, name: c.name || c.id, icon: icone };
	});
}

function fsqNormalizarLocais(response) {
	var itens = [];
	var grupos = response && Array.isArray(response.groups) ? response.groups : [];
	grupos.forEach(function(grupo) {
		(Array.isArray(grupo.items) ? grupo.items : []).forEach(function(item) {
			var obj = item && item.type === "minivenue" ? item.object : null;
			if (!obj || !obj.id) return;
			var loc = obj.location || {};
			var cat = obj.categories && obj.categories[0] && obj.categories[0].icon;
			var detalhes = [loc.address, loc.city, loc.state].filter(Boolean).join(", ");
			itens.push({
				id: obj.id,
				name: obj.name || obj.id,
				detail: detalhes,
				icon: cat && cat.prefix && cat.suffix ? cat.prefix + "bg_32" + cat.suffix : FSQ_DEFAULT_ICON
			});
		});
	});
	return itens;
}

function fsqDetalharErroAutocomplete(status, responseText) {
	var resposta;
	try {
		resposta = JSON.parse(responseText || "");
	} catch (error) {
		resposta = null;
	}
	var meta = resposta && resposta.meta ? resposta.meta : {};
	var tipo = meta.errorType || meta.error_type || "";
	var detalhe = meta.errorDetail || meta.error_message || meta.message || "";
	var codigo = meta.code || status;

	if (status === 400) {
		if (/\bll\b/i.test(detalhe) && /\b(required|missing|invalid|specified|provided)\b/i.test(detalhe)) {
			return "A API recusou a busca" + (tipo ? " (" + tipo + ")" : "") + " porque a região (ll) está ausente ou inválida. Informe coordenadas no formato latitude,longitude ou uma cidade no campo Região." +
				" Detalhe da API: " + detalhe;
		}
		var mensagem = "A API recusou a busca (HTTP 400";
		if (tipo) mensagem += ", " + tipo;
		mensagem += ").";
		if (detalhe) mensagem += " Detalhe: " + detalhe;
		mensagem += " Confira se a região informada é válida e se todos os parâmetros da busca foram enviados.";
		return mensagem;
	}
	if (status === 401 || status === 403) {
		return "A API recusou a autenticação da busca (HTTP " + status + (detalhe ? ": " + detalhe : ".") + ")";
	}
	if (status === 429) {
		return "A API limitou temporariamente as buscas (HTTP 429). Aguarde e tente novamente.";
	}
	return "Não foi possível buscar agora (HTTP " + status + (codigo && codigo !== status ? ", API " + codigo : "") +
		")." + (detalhe ? " Detalhe: " + detalhe : " Verifique os parâmetros e tente novamente.");
}

/**
 * opts: input, results (elementos DOM), status (opcional), getToken(), group, extraParams()
 * (retorna objeto ou null para impedir a busca), parse(response), onSelect(item),
 * preserveStatusOnSelect (opcional, onSelect gerencia o status),
 * onIdOrUrl(id) (opcional, chamado quando o texto contém um ID/URL), emptyMessage
 */
function fsqAnexarAutocomplete(opts) {
	var timer = null;
	var xhr = null;
	var sequencia = 0;

	function mensagem(texto, tipo) {
		if (opts.status) {
			opts.status.textContent = texto || "";
			opts.status.className = (opts.statusClass || "chain-search-status") + (tipo ? " is-" + tipo : "");
			opts.status.style.display = texto ? "" : "none";
		}
	}

	function falha(error) {
		if (opts.onError) {
			opts.onError(error);
			return;
		}
		mensagem(error.message || "Não foi possível concluir a busca.", "error");
	}

	function limpar() {
		opts.results.innerHTML = "";
		opts.results.style.display = "none";
	}

	function renderizar(itens) {
		opts.results.innerHTML = "";
		if (!itens.length) {
			limpar();
			mensagem(opts.emptyMessage || "Nenhum resultado encontrado.", "muted");
			return;
		}
		mensagem("", "");
		itens.forEach(function(item) {
			var botao = document.createElement("button");
			botao.type = "button";
			botao.className = "chain-search-result-item visual-result-item";
			var esquerda = document.createElement("div");
			esquerda.className = "chain-search-result-main visual-item-left";
			var img = document.createElement("img");
			img.src = item.icon || FSQ_DEFAULT_ICON;
			img.alt = "";
			img.onerror = function() { this.src = FSQ_DEFAULT_ICON; };
			var textos = document.createElement("div");
			var nome = document.createElement("span");
			nome.className = "chain-search-result-name visual-item-title";
			nome.textContent = item.name;
			var sub = document.createElement("div");
			sub.className = "chain-search-result-id visual-item-subtitle";
			sub.textContent = item.detail || ("ID: " + item.id);
			textos.appendChild(nome);
			textos.appendChild(sub);
			esquerda.appendChild(img);
			esquerda.appendChild(textos);
			botao.appendChild(esquerda);
			botao.addEventListener("click", function() {
				limpar();
				opts.onSelect(item);
				if (!opts.preserveStatusOnSelect)
					mensagem(opts.selectionMessage ? opts.selectionMessage(item) : "", "success");
			});
			opts.results.appendChild(botao);
		});
		opts.results.style.display = "block";
	}

	function buscar() {
		var query = opts.input.value.trim();
		if (xhr) { xhr.abort(); xhr = null; }
		var minLength = opts.minLength || 2;
		if (query.length < minLength) { limpar(); mensagem("", ""); return; }
		var idLocal = opts.onIdOrUrl ? fsqExtrairIdLocal(query) : "";
		if (idLocal) {
			limpar();
			var minhaId = ++sequencia;
			Promise.resolve(opts.onIdOrUrl(idLocal, function() { return minhaId === sequencia; })).catch(function(error) {
				if (minhaId !== sequencia) return;
				console.error("❌ Erro ao validar local informado:", error);
				falha(error);
			});
			return;
		}
		var token = opts.getToken();
		if (!token) { mensagem("Sessão sem token de acesso.", "error"); return; }
		var minha = ++sequencia;
		mensagem("Buscando...", "muted");
		Promise.resolve(opts.extraParams ? opts.extraParams() : {}).then(function(extras) {
			if (minha !== sequencia) return;
			if (extras === null) { limpar(); return; }
			var params = Object.assign({ group: opts.group }, extras);
			if (!params.group) delete params.group;
			var req = new XMLHttpRequest();
			xhr = req;
			req.open("GET", fsqMontarUrlAutocomplete(query, token, params), true);
			req.onreadystatechange = function() {
				if (req.readyState !== 4 || minha !== sequencia) return;
				xhr = null;
				if (req.status !== 200) {
					limpar();
					mensagem(fsqDetalharErroAutocomplete(req.status, req.responseText), "error");
					return;
				}
				try {
					renderizar(opts.parse(JSON.parse(req.responseText).response || {}));
				} catch (e) {
					console.error("❌ Erro ao processar autocomplete:", e);
					limpar();
					mensagem("Resposta inesperada da API.", "error");
				}
			};
			req.send();
		}).catch(function(error) {
			if (minha !== sequencia) return;
			console.error("❌ Erro ao preparar busca do autocomplete:", error);
			limpar();
			falha(error);
		});
	}

	function agendar() {
		if (timer) clearTimeout(timer);
		sequencia++;
		timer = setTimeout(buscar, 300);
	}

	function cancelar() {
		if (timer) clearTimeout(timer);
		timer = null;
		sequencia++;
		if (xhr) {
			xhr.abort();
			xhr = null;
		}
	}

	// opts.input pode ser trocado em tempo de execução (ex.: um campo por linha)
	function definirInput(input) { opts.input = input; }

	if (opts.input) {
		opts.input.addEventListener("input", agendar);
		opts.input.addEventListener("keydown", function(event) {
			if (event.key === "Enter") {
				event.preventDefault();
				if (timer) clearTimeout(timer);
				buscar();
			}
		});
	}
	return { buscar: buscar, agendar: agendar, cancelar: cancelar, limpar: limpar, mensagem: mensagem, definirInput: definirInput };
}

var fsqLlCache = {};

function fsqObterLl(texto, token) {
	texto = String(texto || "").trim();
	if (!texto) return Promise.resolve("");
	var m = texto.match(/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/);
	if (m) return Promise.resolve(m[1] + "," + m[2]);
	if (fsqLlCache.hasOwnProperty(texto)) return fsqLlCache[texto];
	function geocodificarComGoogle() {
		if (!window.googleMaps || typeof window.googleMaps.loadMapsAPI !== "function") {
			return Promise.reject(new Error("A API do Google Maps não está disponível nesta página."));
		}
		return window.googleMaps.loadMapsAPI().then(function() {
			if (!window.google || !window.google.maps || !window.google.maps.Geocoder) {
				throw new Error("O serviço de geocodificação do Google Maps não está disponível.");
			}
			return new Promise(function(resolve, reject) {
				var geocoder = new window.google.maps.Geocoder();
				geocoder.geocode({ address: texto, region: "BR" }, function(results, status) {
					if (status === "OK" && results && results.length && results[0].geometry && results[0].geometry.location) {
						var location = results[0].geometry.location;
						resolve(location.lat() + "," + location.lng());
						return;
					}
					reject(new Error("Não foi possível converter o endereço em coordenadas (Google Maps: " + status + ")."));
				});
			});
		});
	}
	var geocodificacao = token
		? fsqGeocodificarPorApi(texto, token).catch(function(erroFoursquare) {
			return geocodificarComGoogle().catch(function(erroGoogle) {
				throw new Error("Foursquare: " + erroFoursquare.message + " Google Maps (fallback): " + erroGoogle.message);
			});
		})
		: geocodificarComGoogle();
	fsqLlCache[texto] = geocodificacao.catch(function(error) {
		delete fsqLlCache[texto];
		throw error;
	});
	return fsqLlCache[texto];
}
