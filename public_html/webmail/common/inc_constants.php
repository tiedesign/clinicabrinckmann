<?php

/*
 * AfterLogic WebMail Pro PHP by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in COPYING
 * 
 */

	defined('WM_ROOTPATH') || define('WM_ROOTPATH', (dirname(__FILE__).'/../'));

	define('ErrorDesc', 'ErrorDesc');
	define('MEMORYLIMIT', ((int) CApi::GetConf('webmail.memory-limit', 200)).'M');
	define('TIMELIMIT', CApi::GetConf('webmail.time-limit', 3000));
	define('RESET_TIME_LIMIT', 60);
	define('RESET_TIME_LIMIT_RUN', (int) (RESET_TIME_LIMIT / 2));

	define('DEFAULT_SKIN', 'AfterLogic');
	define('SESSION_LANG', 'session_lang');
	define('ATTACH_DIR', 'attachtempdir');

	define('USE_DB', 'db' === CApi::GetManager()->GetStorageByType('webmail'));

	define('USE_LDAP_LOGIN', false);
		define('LDAP_LOGIN_HOST', '127.0.0.1');
		define('LDAP_LOGIN_PORT', '389');
		define('LDAP_LOGIN_BIND_DN', 'cn=Directory Manager');
		define('LDAP_LOGIN_PASSWORD', 'password');
		define('LDAP_LOGIN_DN', 'ou=People,o=subdomain,o=domain');

	define('USE_LDAP_SETTINGS_STORAGE', false);
		define('LDAP_SETTINGS_FIELD', 'nswmExtendedUserPrefs');
		define('LDAP_SETTINGS_HOST', LDAP_LOGIN_HOST);
		define('LDAP_SETTINGS_PORT', LDAP_LOGIN_PORT);
		define('LDAP_SETTINGS_BIND_DN', 'cn=Directory Manager');
		define('LDAP_SETTINGS_PASSWORD', 'password');

	define('USE_LDAP_CONTACT', false);
		define('LDAP_CONTACT_HOST', LDAP_LOGIN_HOST);
		define('LDAP_CONTACT_PORT', LDAP_LOGIN_PORT);
		define('LDAP_CONTACT_BIND_DN', LDAP_LOGIN_BIND_DN);
		define('LDAP_CONTACT_PASSWORD', LDAP_LOGIN_PASSWORD);
		define('LDAP_CONTACT_DN', 'o=pab');
		
	define('WMVERSION', CApi::Version());
	define('IS_SUPPORT_ICONV', api_Utils::IsIconvSupport());
	
	define('SESSION_RESET_STEP', 'sessionresetstep');
	define('SESSION_RESET_ACCT_ID', 'sessionresetacctid');

	define('MOBILE_SYNC_LOGIN_PREFIX', 'wm_');

	define('ACCOUNT_OBJ', 'account_obj');
	define('ACCOUNT_FOLDERS', 'account_folders');
	define('ACCOUNT_IDS', 'all_accounts_ids');
	define('CALENDAR_ID', 'PubCalendarId');
	define('ACCESS_LEVEL', 'PubCalendarAccess');
	define('SEPARATED', 'separated_apl');
	
	define('DUMMYPASSWORD', '1111111111111111111111');
	define('MAX_INT', 1023998976);

	defined('INFORMATION') || define('INFORMATION', 'information');
	defined('ISINFOERROR') || define('ISINFOERROR', 'infoErr');
	
	define('DEMO_SES', 'demoses');
		define('DEMO_S_ContactsPerPage', 'contactsperpage');
		define('DEMO_S_MessagesPerPage', 'messagesperpage');
		define('DEMO_S_AllowDhtmlEditor', 'allowdhtmleditor');
		define('DEMO_S_DefaultSkin', 'defaultskin');
		define('DEMO_S_DefaultOutCharset', 'defaultoutcharset');
		define('DEMO_S_DefaultTimeZone', 'defaulttimezone');
		define('DEMO_S_DefaultLanguage', 'defaultlanguage');
		define('DEMO_S_DefaultDateFormat', 'defaultdateformat');
		define('DEMO_S_DefaultTimeFormat', 'defaulttimeformat');
		define('DEMO_S_ViewMode', 'viewmode');
		define('DEMO_S_AutoCheckMailInterval', 'autocheckmailinterval');

	define('USER_FLASH_VERSION', 'userflashversion');

	define('MAX_ENVELOPES_PER_SESSION', 20);

	define('IMAP_BS_ENCODETYPE_BASE64', 0);
	define('IMAP_BS_ENCODETYPE_QPRINTABLE', 1);
	define('IMAP_BS_ENCODETYPE_XUUE', 2);
	define('IMAP_BS_ENCODETYPE_NONE', 5);

	define('MESSAGE_VIEW_TYPE_PRINT', '0');
	define('MESSAGE_VIEW_TYPE_FULL', '1');
	define('MESSAGE_VIEW_TYPE_ATTACH', '2');

	define('SYNC_TYPE_FUNAMBOL', 1);

	defined('CRLF') || define('CRLF', "\r\n");

	/* ---------- */
	
	$defaultTimeZone = function_exists('date_default_timezone_get')
		? @date_default_timezone_get() : 'US/Pacific';
	
	define('SERVER_TIME_ZONE', ($defaultTimeZone && strlen($defaultTimeZone) > 0)
		? $defaultTimeZone : 'US/Pacific');
	
	include WM_ROOTPATH.'common/inc_arrays.php';
	include WM_ROOTPATH.'common/inc_functions.php';
