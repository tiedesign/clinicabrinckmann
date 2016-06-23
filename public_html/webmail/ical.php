<?php
/* 
 * AfterLogic WebMail Pro PHP by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in COPYING
 * 
 */

defined('WM_ROOTPATH') || define('WM_ROOTPATH', (dirname(__FILE__).'/'));

require_once WM_ROOTPATH.'application/include.php';
require_once WM_ROOTPATH.'common/inc_constants.php';
require_once WM_ROOTPATH.'common/class_convertutils.php';
require_once WM_ROOTPATH.'calendar/class_settings.php';
require_once WM_ROOTPATH.'api/calendar/calendar_manager.php';

$calendarHash = isset($_GET['ical']) ? $_GET['ical'] : null;
if (null !== $calendarHash && !empty($calendarHash))
{
	$wm_settings =& CApi::GetSettings();
	$settings = new CalSettings($wm_settings);

	AppIncludeLanguage($settings->DefaultLanguage);

	$calendarContainer = null;
	$errorMsg = $response = '';
	$calendarManager = new CalendarManager();
	if (is_object($calendarManager) && $calendarManager->InitManager())
	{
		$calendarContainer = $calendarManager->GetCalendarByHash($calendarHash);

		if ($calendarContainer)
		{
			$response = $calendarManager->ExportIcs($calendarContainer);
		}
		else
		{
			$errorMsg = 'Can\'t load calendar. Check the link.';
		}
	}
	else
	{
		$errorMsg = 'Can\'t get load calendar.';
	}
}
else
{
	$errorMsg = 'Can\'t load calendar. Check the link.';
}

if (!empty($errorMsg))
{
    echo $errorMsg;
}
else
{
	echo SetHeaders($response);
	echo $response;
}

function SetHeaders($str)
{
	$l = strlen($str);
	if ($l > 0)
	{
		header('Content-Type: text/calendar; charset=UTF-8;');
		header('Content-Length: '.$l);
		header('Expires: Fri, 01 Jan 1990 00:00:00 GMT');
		header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
		header('Pragma: no-cache');
		header('Content-Disposition: attachment; filename="webmail.ics"');
	}
}
