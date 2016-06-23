$(document).ready(function() {
	$('#tbMenu a').click(function(event) {
		event.preventDefault();
		var pg = this.href;
		$('#pgContent').fadeOut('fast', function() {
			$('#pgLoading').show();
			if (pg.indexOf('/clinica') != -1) {
				openPage(pg, createGallery);				
			} else if (pg.indexOf('/localizacao') != -1) {
				openPage(pg, initializeMap);
			} else {
				openPage(pg);
			}
		});
	});

	$("#tbMenu a").hover(function() {
           $(this).animate({ backgroundColor: "#ffffff", color: "#697C5A" }, 500);
       },function() {
           $(this).animate({ backgroundColor: "#ffffff", color: "#95A886" }, 500);
    });
    
    //$("#pgRight").makeFloat({x:"current",y:"current",speed:"fast"});
});

	function initializeMap() {
		var myOptions = {
		  zoom: 16,
		  center: new google.maps.LatLng(-30.029000,-51.202065),
		  mapTypeId: google.maps.MapTypeId.ROADMAP,
		  streetViewControl: true,
		  mapTypeControl: true,
		  panControl: false,
		  zoomControl: true,
		  zoomControlOptions: {
			  style: google.maps.ZoomControlStyle.LARGE
		  },
		  scaleControl: false
		}
		var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);                          

		var contentString = '<div id="content">'+
			'<h6 class="title">Cl&iacute;nica Brinckmann</h6>'+
			'<p class="noindent">Avenida Goethe, 71/508<br/>Bairro Moinhos de Vento<br/>Porto Alegre/RS</p>'+
			'</div>';
			
		var infowindow = new google.maps.InfoWindow({
			content: contentString
		});
		
	//	<iframe width="425" height="350" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://maps.google.com.br/maps?f=q&amp;source=s_q&amp;hl=pt-BR&amp;geocode=&amp;q=Avenida+Goethe,+71%2F508+Bairro+Moinhos+de+Vento+Porto+Alegre%2FRS+&amp;aq=&amp;sll=-14.239424,-53.186502&amp;sspn=44.245853,79.013672&amp;vpsrc=0&amp;ie=UTF8&amp;hq=&amp;hnear=Av.+Goethe,+71+-+Rio+Branco,+Porto+Alegre+-+Rio+Grande+do+Sul,+90430-100&amp;t=m&amp;z=14&amp;ll=-30.029981,-51.201733&amp;output=embed"></iframe><br /><small><a href="http://maps.google.com.br/maps?f=q&amp;source=embed&amp;hl=pt-BR&amp;geocode=&amp;q=Avenida+Goethe,+71%2F508+Bairro+Moinhos+de+Vento+Porto+Alegre%2FRS+&amp;aq=&amp;sll=-14.239424,-53.186502&amp;sspn=44.245853,79.013672&amp;vpsrc=0&amp;ie=UTF8&amp;hq=&amp;hnear=Av.+Goethe,+71+-+Rio+Branco,+Porto+Alegre+-+Rio+Grande+do+Sul,+90430-100&amp;t=m&amp;z=14&amp;ll=-30.029981,-51.201733" style="color:#0000FF;text-align:left">Exibir mapa ampliado</a></small>
		
		var image = 'images/marker.gif';
		var myLatLng = new google.maps.LatLng(-30.030368,-51.202065);
		
		var marker = new google.maps.Marker({
			position: myLatLng,
			map: map,
			icon: image
		});
		
		google.maps.event.addListener(marker, 'click', function() {
		  infowindow.open(map,marker);
		});
		
	}

var testItemCount = 58;
function calculateVoiceTest() {
	var total = 0;
	var preffix = "pontuation-";
	var suffix = "-score";
	for(var i = 1; i < testItemCount; i++) {
		var chk = $("#" + preffix + i + suffix);
		if (chk && chk.length > 0) {
			var pt = chk.val();
			if (pt.length > 0) {
				total += parseInt(pt);
			}
		}
	}
	
	voiceTestUpdateMessage(total);
	$("#testeVozResultado").dialog({
		modal: true,
		closeText: 'Fechar',
		width: 660,
		buttons: {
			Ok: function() {
				$( this ).dialog( "close" );
			}
		}
	});
}

function voiceTestUpdateMessage(pts) {
	var html = "Você fez " + pts + " ponto";
	if (pts > 1) {
		html += "s";		
	}
	html += "!";
	html += "<br /><br />";
	if (pts < 16) {
		html += "Você não tem propensão para desenvolver um problema de voz. "; 
		html += "<br /><br />Parabéns pois você respeita os limites do organismo! ";
		html += "<br /><br />Siga assim que estará contribuindo para a sua longevidade vocal. "; 
		html += "<br /><br />Contudo, se apesar dessa classificação você estiver apresentando um problema de voz, tal como voz rouca ou esforço para falar, consulte rapidamente um especialista pois provavelmente trata-se de um quadro orgânico independente do comportamento vocal e necessita de uma avaliação detalhada. ";
	} else if (pts < 31) {
		html += "Você tem tendência para desenvolver um problema de voz e, talvez, já apresente alguns sinais e sintomas de alteração vocal: a chamada disfonia. "; 
		html += "<br /><br />Você está em uma situação onde um acontecimento estressante adicional ou um simples aumento do uso da voz na atividade profissional podem levá-lo a um sério risco vocal. "; 
		html += "<br /><br />Você precisa ser avaliado por um especialista! "; 
		html += "Procure verificar em seu ambiente de trabalho, familiar e social quais as modificações que podem ser introduzidas para reunir melhores condições de comunicação. "; 
		html += "<br /><br />Conscientize-se da importância de sua voz e reduza a prática dos comportamentos negativos. "; 		
	} else if (pts < 51) {
		html += "Você tem se arriscado demais e pode vir a perder um dos maiores bens que possui: sua voz! "; 
		html += "<br /><br />Talvez você já apresente uma disfonia e já tenha recorrido a um especialista. "; 
		html += "Siga corretamente a orientação e o tratamento indicados. "; 
		html += "<br /><br />Procure refletir sobre o modo com que você se comunica com as pessoas, em diferentes situações, caracterizando os principais focos de tensão e estresse de seu dia-a-dia, procurando reunir condições para reverter esse quadro. "; 
		html += "<br /><br />Pense o quanto sua vida irá tornar-se difícil se você tiver que viver com uma limitação vocal definitiva. "; 
		html += "Reaja! ";
	} else {
		html += "De duas uma: ou você sofre de um problema de voz crônico ou apresenta uma resistência vocal excepcional, acima do normal! "; 
		html += "<br /><br />Se você tem um problema de voz, sabe o quanto esta situação interfere negativamente em sua vida, e como este fato representa uma sobrecarga adicional em casa e no trabalho. "; 
		html += "Conscientize-se da necessidade imediata de desenvolver comportamentos vocais adequados e saudáveis. "; 
		html += "<br /><br />Melhore seu ambiente de comunicação! Se você ainda não consultou um especialista, é melhor não adiar. "; 
		html += "Bu	sque orientação!  ";
		html += "<br /><br />Por outro lado, se apesar dessa quantidade de desvios no uso da voz ela ainda se apresenta saudável, você pertence a esse raro tipo de indivíduo com resistência vocal a toda prova. "; 
		html += "<br /><br />Contudo, cuide-se, pois a voz não é eterna e os limites do organismo mudam constantemente com a idade e com as condições gerais de saúde. "; 
		html += "Além disso, seu comportamento vocal pode estar sendo invasivo para seus interlocutores, representando também um modelo vocal inadequado, principalmente para as crianças. "; 
		html += "Que tal mudar? ";		
	}
	
	$("#testeVozResultado").html(html);	
}

function voiceTestCallback() {
	$('.pontuation').raty({
		path:		'images/',
		starOff:	'point-off.jpg',
		starOn:		'point-on.jpg',
		number:		4,
		cancel:		true,
		cancelOff:	'point-reset.jpg',
		cancelOn:	'point-reset.jpg',
		cancelHint:	'Não se aplica',
		hintList:	['Rara ocorrência', 'Baixa ocorrência', 'Ocorrência elevada', 'Ocorrência constante']
	});
	$("#teste_voz img").tooltip({
		position: "top right"
	});
}

function calculateVoiceProblem() {
	var total = 0;
	var preffix = "opt";
	for(var i = 1; i < 22; i++) {
		var chk = $("#" + preffix + i);
		if (chk && chk.length > 0) {
			var ok = chk[0].checked;
			if (ok) {
				total++;
			}
		}
	}

	var html = "Você fez " + total + " ponto";
	if (total > 1) {
		html += "s";		
	}
	html += "!";		
	
	if (total < 5) {
		html += "<br /><br />Verifique o que pode ser feito para reduzir essa marca.";		
	} else {
		html += "<br /><br />Procure um especialista e peça orientação.";				
		html += "<br /><br />Sua voz é muito importante e sua saúde vocal pode estar correndo um sério risco!";				
	}
	 
	$("#problemaVozResultado").html(html);		
	$("#problemaVozResultado").dialog({
		modal: true,
		closeText: 'Fechar',
		width: 660,
		buttons: {
			Ok: function() {
				$( this ).dialog( "close" );
			}
		}
	});
}

function createGallery() {
	var galleries = $('.ad-gallery').adGallery({
		loader_image: 'images/ajax-loader.gif',
		display_next_and_prev: false,
		display_back_and_forward: false,
		slideshow: {
			enable: false,
		}
	});
}

function openPage(pg, cb) {
	$('#pgContent').fadeOut('fast', function() {
		$('#pgLoading').show();
		$.ajax({
			type: "GET",
			url: pg + ".php",
			dataType: "html",
			beforeSend: function(xhr) {
//	            xhr.overrideMimeType("text/html; charset=ISO-8859-1");
//				xhr.setRequestHeader("Content-type", "charset=ISO-8859-1");
			},
			success:function(response){
				$('#pgLoading').hide();
				$('#pgContent').html(response);
				$('#pgContent').fadeIn();
				if (cb) cb();
			}
		});
	});
}

function openImage(w) {
	//window.open(url,"_blank","width=330,height=430,left=200,top=100");
	
	$("#" + w).dialog({
		modal: true,
		closeText: 'Fechar',
		width: 350,
		buttons: {
			Ok: function() {
				$( this ).dialog( "close" );
			}
		}
	});	
}

var campoDeFoco;

function openMessage(m) {
	$("#wMensagem").html(m);
	$("#wMensagem").dialog({
		modal: true,
		closeText: 'Ok',
		width: 350,
		buttons: {
			Ok: function() {
				$(this).dialog("close");
			}
		},
		close: function(event, ui)
        {
			if (campoDeFoco != null) {
				campoDeFoco.focus();
			}
        }
	});	
}

function enviaEmail() {
	if ($("#nome").val().length == 0) {
		openMessage("<p>Por favor, preencha o seu nome.</p>");
		campoDeFoco = $("#nome");
		return false;
	} else if ($("#email").val().length == 0) {
		openMessage("<p>Por favor, preencha o seu e-mail.</p>");
		campoDeFoco = $("#email");
		return false;
	} else if ($("#telefone").val().length == 0) {
		openMessage("<p>Por favor, preencha o seu telefone.</p>");
		campoDeFoco = $("#telefone");
		return false;
	}
	
	$("#fEmail").submit();
	openMessage("Sua mensagem foi enviada com sucesso.<br/><br/>Por favor, aguarde o nosso retorno.");
	
	$("#nome").val("");
	$("#email").val("");
	$("#telefone").val("");	
	$("#mensagem").val("");	
}