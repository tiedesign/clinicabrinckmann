<?php header('Content-Type: text/html; charset=ISO-8859-1'); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"> 
<html xmlns="http://www.w3.org/1999/xhtml" lang="pt" xml:lang="pt">

    <head>
    	<title>Clínica Brinckmann</title>
        <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"/>
        <meta name="description" content="" />
    	<meta name="keywords" content="" />
    	<meta name="robots" content="index,follow" />
    	<meta name="author" content="http://www.tiedesign.com.br" />
    
    	<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon"/>
        <link rel="stylesheet" href="css/style.css" type="text/css" media="screen"/>
        <link rel="stylesheet" href="css/fonts.css" type="text/css" media="screen"/>
        <link rel="stylesheet" href="css/jquery.ad-gallery.css" type="text/css" media="screen"/>
    	<link rel="stylesheet" href="css/smoothness/jquery-ui-1.8.16.custom.css" type="text/css" media="screen"/>
        
    	<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
    	<script type="text/javascript" src="js/jquery-ui-1.8.16.custom.min.js"></script>
    	<script type="text/javascript" src="js/jquery.ad-gallery.pack.js"></script>
    	<script type="text/javascript" src="js/jquery.tools.min.js"></script>
    	<script type="text/javascript" src="js/jquery.floatobject-1.0.js"></script>
    	<script type="text/javascript" src="js/jquery.raty.min.js"></script>
    	<script type="text/javascript" src="js/util.js"></script>
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false&language=pt_BR"></script>
    	<script type="text/javascript">
            $(document).ready(function() {
            });
    	</script>
    </head>

    <body>

        <div id="pg">
            <div id="pgLeft">
                <div class="top">
                    <img src="images/logo.jpg"/>
                </div>
                
                <div id="menuLeft" class="menu">
                    <table width="100%" id="tbMenu" cellspacing="0">
                        <tr>
                            <td><a href="inicial" class="menuitem">Página Inicial</a></td>
                            <td><a href="clinica" class="menuitem">Clínica</a></td>
                            <td><a href="profissionais" class="menuitem">Profissionais</a></td>
                            <td><a href="convenios" class="menuitem">Convênios</a></td>
                            <td><a href="saibamais" class="menuitem">Saiba Mais</a></td>
                            <td><a href="localizacao" class="menuitem">Localização</a></td>
                        </tr>
                    </table>
                </div>
            
                <div id="pgLoading" style="display:none;">
                    <img src="images/ajax-loader.gif"/>
                </div>
                <div id="pgContent" class="content">
                    <?php include("inicial.php"); ?>
                </div>
            </div>
            
            <div id="pgRight">
                <div class="top">
                    <h6 class="title">Contato</h6>
                    <img src="images/telefone.jpg"/>
                    <a href="mailto:brinckmann@clinicabrinckmann.com.br"><img src="images/email.jpg"/></a>
                </div>
                
                <div id="menuRight" class="menu">
                    <span class="menuitem">Fale Conosco</span>
                </div>
            
                <div class="content">
                    <form id="fEmail" name="fEmail" action="email.php" method="post" target="fHidden">
                        <label class="floatLeft" for="nome">Nome:</label>
                        <input type="text" name="nome" id="nome" class="tamanhoInputs" />
                        <br />
                        <label class="floatLeft" for="email">E-mail:</label>
                        <input type="text" name="email" id="email" class="tamanhoInputs" />
                        <br />
                        <label class="floatLeft" for="telefone">Telefone:</label>
                        <input type="text" name="telefone" id="telefone" class="tamanhoInputs" />
                        <br />
                        <label for="mensagem">Mensagem:</label>
                        <br />
                        <textarea name="mensagem" id="mensagem" class="tamanhoTextArea"></textarea>
                        <blockquote>
                            <button type="button" onclick="enviaEmail();">Enviar</button>
                        </blockquote>
                    </form>
					
					<iframe name="fHidden" src="" style="display:none;"></iframe>
					<div id="wMensagem" style="display:none;" title="Fale Conosco">
					</div>
					
                    <h6 class="menuitem">Endereço</h6>
                    
                    <a href="javascript: openPage('comochegar');"><img src="images/conducoes.jpg" class="floatRight" alt="Como chegar" title="Como chegar"></a>
                    <p>
                        Avenida Goethe, 71/508
                        <br />Bairro Moinhos de Vento
                        <br />Porto Alegre/RS
                        
                        <blockquote>
                            <button onclick="openPage('localizacao', initializeMap);">Ver no mapa</button>
                            <button onclick="openImage('wEstacionamento');">Estacionamento</button>
                            <button onclick="openPage('comochegar');">Como chegar</button>
                        </blockquote>
                    </p>
            
                        	<div id="wEstacionamento" class="fotoPredio" title="Estacionamento">
                        		<img src="images/mbparking.jpg" /><br/>
					<p><b>MB Parking</b></p>
					<p>
					Estacionamento pago no próprio prédio.
					</p>
					<p>
					Cond. Josué Guimarães
					</p>
					<p>
					Consulte a tarifa no local.
					</p>
				</div>	
<!--
                    <h6 class="menuitem">Convênios</h6>
                    
                    <img src="images/unimed.jpg"/>
                    <br /><br />
                    <img src="images/serpro.jpg"/>
                    <br /><br />
                    <img src="images/goldencross.jpg"/>
                    
                    <blockquote>
                        <button onclick="openPage('convenios');">Ver todos</button>
                    </blockquote>
-->
                </div>
            </div>

            <div id="footer">
                <div id="footerContent">
                	<span id="copyright" class="floatLeft">
                        &copy; Copyright 2011 Brinckmann &mdash; Todos os direitos reservados
                	</span>
                	<span id="tie" class="floatRight">
                        <a id="tie" title="Tie Design - &quot;A eleg&acirc;ncia do seu neg&oacute;cio na Internet&quot;" href="http://www.tiedesign.com.br" target="_blank"><img src="images/tie.jpg"/></a>
                	</span>
                </div>
            </div>

            <div class="clearBoth">&nbsp;</div>

        </div>

    </body>
</html>