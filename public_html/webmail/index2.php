<?php

/*
 * AfterLogic WebMail Pro PHP by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in COPYING
 *
 */

	defined('WM_ROOTPATH') || define('WM_ROOTPATH', dirname(__FILE__).'/');
	
	require_once WM_ROOTPATH.'application/include.php';
	require_once WM_ROOTPATH.'common/inc_constants.php';
	require_once WM_ROOTPATH.'common/class_convertutils.php';

	if (CApi::IsValid())
	{
		$oInput = new CAppBaseHttp();
		CApi::Plugin()->RunHook('webmail-index2', array($oInput));
	}
	else
	{
		exit('Api Error');
	}