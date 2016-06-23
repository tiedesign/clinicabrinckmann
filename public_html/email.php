<?php
	$email_remetente = "brinckmann@clinicabrinckmann.com.br"; // deve ser um email do dominio
	$email_destinatario = "brinckmann@clinicabrinckmann.com.br"; // qualquer email pode receber os dados

	$nome = $_POST['nome'];
	$email = $_POST['email'];
	$telefone = $_POST['telefone'];
	$mensagem = $_POST['mensagem'];
 
	$email_conteudo = "<b>Nome:</b> $nome \n"; 
	$email_conteudo .= "<b>Email:</b> $email \n"; 
	$email_conteudo .= "<b>Telefone:</b> $telefone \n";
	$email_conteudo .= "\n<b>Mensagem:</b> \n\n $mensagem \n";

	$email_headers = implode("\n",array ( "From: $email_remetente", "Reply-To: $email", "Subject: Contato via site de: $nome", "Return-Path:  $email", "MIME-Version: 1.0","X-Priority: 3","Content-Type: text/html; charset=ISO-8859-1" ) );
 
	$assunto = "Contato via site de $nome";
	if (mail($email_destinatario, $assunto, nl2br($email_conteudo), $email_headers)) {
		echo "Sucesso";
	} else {
		echo "Erro";
	}
?>