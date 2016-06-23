<?php

/*
 * AfterLogic WebMail Pro PHP by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in COPYING
 */

	if (isset($_GET['calendar']) && strlen($_GET['calendar']) > 0 && preg_match('/^[a-zA-Z0-9_\-]+$/', $_GET['calendar']))
	{
		header('Location: published_calendar.php?calendar='.$_GET['calendar']);
	}
	
	if (isset($_GET['ical']) && strlen($_GET['ical']) > 0 && preg_match('/^[a-zA-Z0-9_\-]+$/', $_GET['ical']))
	{
		header('Location: ical.php?ical='.$_GET['ical']);
	}
	