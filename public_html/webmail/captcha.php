<?php

	defined('WM_ROOTPATH') || define('WM_ROOTPATH', (dirname(__FILE__).'/'));
	include_once WM_ROOTPATH.'application/include.php';

	require WM_ROOTPATH.'libs/kcaptcha/kcaptcha.php';

	$captcha = new KCAPTCHA();
	if (isset($_GET['PHPWEBMAILSESSID']))
	{
		$_SESSION['captcha_keystring'] = $captcha->getKeyString();
	}
