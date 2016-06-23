<?php

/*
 * AfterLogic Admin Panel by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in COPYING
 *
 */

	include 'core/top.php';
	include 'core/cadminpanel.php';

	$oAdminPanel = new CAdminPanel(__FILE__);
	$oAdminPanel->Run()->End();