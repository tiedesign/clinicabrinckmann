<?php

	if (isset($_GET['mode']) && $_GET['mode'] == 'logout')
	{
		header('Location: /webmaillogout.cgi');
		die();
	}

	defined('WM_ROOTPATH') || define('WM_ROOTPATH', (dirname(__FILE__).'/../'));

	$userLogin = $_SERVER['REMOTE_USER'];
	$userPassword = $_SERVER['REMOTE_PASSWORD'];
	$userHost = $_SERVER['DOMAIN'];
	
	$arr = explode('@', $userLogin);
	if( ! isset($arr[1]) )
		$userEmail = $arr[0].'@'.$userHost ;
	else
		$userEmail = $arr[0].'@'.$arr[1] ;
	
	/* --- */
	include WM_ROOTPATH.'libraries/afterlogic/api.php';

	$sEmail = $userEmail;
	$sPassword = $userPassword;

	/**
	 * @var CApiWebmailManager
	 */
	$oApiWebMailManager = CApi::Manager('webmail');

	$oAccount = $oApiWebMailManager->LoginToAccount($sEmail, $sPassword);
	if ($oAccount)
	{
		$oAccount->FillSession();
		$oApiWebMailManager->JumpToWebMail('../webmail.php?check=1');
	}
	else
	{
		$sError = $oApiWebMailManager->GetLastErrorMessage();
		exit(empty($sError) ? WebMailException : $sError);
	}

	exit();
