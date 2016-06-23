<?php

	// remove the following line for real use
	exit('remove this line');

	// determining main directory
	defined('WM_ROOTPATH') || define('WM_ROOTPATH', (dirname(__FILE__).'/../'));

	// utilizing WebMail Pro API
	include_once WM_ROOTPATH.'libraries/afterlogic/api.php';
	if (class_exists('CApi') && CApi::IsValid())
	{
		// Getting required API class
		$oApiDbManager = CApi::Manager('db');

//		$oSettings =& CApi::GetSettings();
//		$oSettings->SetConf('Common/DBPrefix', '');

		echo $oApiDbManager->GetSqlSchemaAsString(true);
	}
	else
	{
		echo 'WebMail API not allowed';
	}
