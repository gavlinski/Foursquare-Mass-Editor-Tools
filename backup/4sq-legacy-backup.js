/**
 * BACKUP DO CÓDIGO LEGADO DO GOOGLE MAPS
 * 
 * Este arquivo contém todo o código legado relacionado ao Google Maps que foi removido
 * do 4sq.js para garantir que não seja executado acidentalmente.
 * 
 * Data do backup: 2025-07-19
 * Migrado para: modern-google-maps.js
 * 
 * ⚠️ IMPORTANTE: Este código NÃO deve ser usado! Foi substituído pelo sistema moderno.
 */

// ===== FUNÇÕES LEGADAS DE CARREGAMENTO DO MAPA =====

function carregarMapaLegado() {
	var script = document.createElement("script");
	script.type = "text/javascript";
	script.src = "http://maps.googleapis.com/maps/api/js?key=AIzaSyD9ZfpJz_ZlwOo7crLhiYhxcpJdBPpBVi8&callback=inicializarMapa";
	document.body.appendChild(script);
}

function inicializarMapaLegado() {
	var lat;
	var lng;
	var myZoom;
	if ((dojo.cookie("coordinates") != null) && (dojo.cookie("coordinates") != "undefined")) {
		coordinates = dojo.cookie("coordinates").split(",");
		lat = parseFloat(coordinates[0]);
		lng = parseFloat(coordinates[1]);
		myZoom = 15;
	} else {
		lat = -12.726084;
		lng = -55.425781;
		myZoom = 4;
	}
	
	// Exibir mapa;
	var myLatlng = new google.maps.LatLng(lat, lng);
	var mapOptions = {
		zoom: myZoom,
		center: myLatlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		mapTypeControl: true,
		mapTypeControlOptions: {
			style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
			position: google.maps.ControlPosition.RIGHT_TOP
		}
	}
	var controlDiv;
	require(["dojo/dom-construct"], function(domConstruct) {
  	controlDiv = domConstruct.create("div", { style: { padding: "0px 5px 2px 0px" } });
  	var image = '<img src="img/poweredByFoursquare.png" width="230" height="25">'; 
  	var link = domConstruct.create("a", {
  		href: "https://foursquare.com",
  		title: "Foursquare",
  		innerHTML: image
  	}, controlDiv);
  });

	// Exibir o mapa na div #mapa;
	map = new google.maps.Map(dojo.byId('mapa'), mapOptions);
	map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(controlDiv);
	bounds = new google.maps.LatLngBounds();
	mapaCarregado = true;
	console.info("Mapa carregado!");
}

// ===== FUNÇÕES LEGADAS DE ATUALIZAÇÃO DE MARCADORES =====

function atualizarMarcadoresMapaLegado() {
	if (!mapaCarregado) {
		setTimeout(atualizarMarcadoresMapaLegado, 1000);
		return;
	}
	for (i = 0; i < locais.length; i++) {
		if ((locais[i] != undefined) && (locais[i][1] != undefined)) {
			marcadores[i] = new google.maps.Marker({
				position: new google.maps.LatLng(locais[i][1], locais[i][2]),
				map: map,
				title: locais[i][0],
				draggable: true,
				animation: google.maps.Animation.DROP
			});
			google.maps.event.addListener(marcadores[i], 'dragend', function(evt) {
				var marcador = this.title.split(".", 1)[0];
				var j = parseInt(marcador) - 1;
				var novaPosicao = evt.latLng.lat() + ', ' + evt.latLng.lng();
				if (dojo.query("input[name=selecao]")[j].disabled != true) {
					inputId = dojo.query("input[name=venuell]")[j].id;
					if ((inputId == "") || ((dijit.byId(inputId).textbox.value != novaPosicao) && (dijit.byId(inputId).readOnly == false) && (dijit.byId(inputId).disabled == false))) {
						(inputId == "") ? dojo.query("input[name=venuell]")[j].value = novaPosicao : dijit.byId(inputId).set("value", novaPosicao);
						index = csv[0].indexOf("venuell");
						csv[parseInt(j) + 1][index] = novaPosicao;
						dojo.byId("result" + j).innerHTML = "";
						if (linhasEditadas.indexOf(parseInt(j)) == -1)
							linhasEditadas.push(parseInt(j));
					}
				}				
			});
			bounds.extend(marcadores[i].position);
		}
	}
	map.fitBounds(bounds);
	console.info("Marcadores posicionados!");
}

// ===== FIM DO BACKUP LEGADO =====
