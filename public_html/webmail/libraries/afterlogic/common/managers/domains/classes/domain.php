<?php

/**
 * AfterLogic Api by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in LICENSE.txt
 *
 * @package Domains
 * @subpackage Classes
 */

/**
 * @property int $IdDomain
 * @property string $Name
 * @property string $Url
 * @property bool $OverrideSettings
 * @property bool $SiteIsInternalName
 * @property bool $IsDefaultDomain
 * @property string $SiteName
 * @property string $DefaultLanguage
 * @property int $DefaultTimeZone
 * @property int $DefaultTimeFormat
 * @property bool $AllowRegistration
 * @property bool $AllowPasswordReset
 * @property bool $AllowWebMail
 * @property int $IncomingMailProtocol
 * @property string $IncomingMailServer
 * @property int $IncomingMailPort
 * @property bool $IncomingMailUseSSL
 * @property string $OutgoingMailServer
 * @property int $OutgoingMailPort
 * @property int $OutgoingMailAuth
 * @property string $OutgoingMailLogin
 * @property string $OutgoingMailPassword
 * @property bool $OutgoingMailUseSSL
 * @property int $OutgoingSendingMethod
 * @property int $UserQuota
 * @property int $AutoCheckMailInterval
 * @property string $DefaultSkin
 * @property int $MailsPerPage
 * @property bool $AllowUsersChangeInterfaceSettings
 * @property bool $AllowUsersChangeEmailSettings
 * @property bool $AllowUsersAddNewAccounts
 * @property int $Layout
 * @property int $SaveMail
 * @property bool $AllowContacts
 * @property int $ContactsPerPage
 * @property int $GlobalAddressBook
 * @property bool $AllowCalendar
 * @property bool $CalendarShowWeekends
 * @property int $CalendarWorkdayStarts
 * @property int $CalendarWorkdayEnds
 * @property bool $CalendarShowWorkDay
 * @property int $CalendarWeekStartsOn
 * @property int $CalendarDefaultTab
 * @property bool $DetectSpecialFoldersWithXList
 *
 * @package Domains
 * @subpackage Classes
 */
class CDomain extends api_AContainer
{
	/**
	 * @var array
	 */
	protected $aFolders;

	/**
	 * @param string $sName = ''
	 * @param string $sUrl = null
	 * @return CDomain
	 */
	public function __construct($sName = '', $sUrl = null)
	{
		parent::__construct(get_class($this), 'IdDomain');
		
		$oSettings =& CApi::GetSettings();

		$aDefaults = array(
			'IdDomain'	=> 0,
			'Name'		=> trim($sName),
			'Url'		=> (null === $sUrl) ? '' : trim($sUrl),
			
			'IsDefaultDomain'	=> true,
			'IsInternal'		=> false,
			'OverrideSettings'	=> true
		);

		$aSettingsMap = $this->GetSettingsMap();
		foreach ($aSettingsMap as $sProperty => $sSettingsName)
		{
			$aDefaults[$sProperty] = $oSettings->GetConf($sSettingsName);
		}
		
		$this->SetDefaults($aDefaults);

		$this->aFolders = array(
			EFolderType::Inbox => 'INBOX',
			EFolderType::Drafts => 'Drafts',
			EFolderType::Sent => 'Sent Items',
			EFolderType::Spam => 'Spam',
			EFolderType::Trash => 'Trash',
		);

		CApi::Plugin()->RunHook('api-domain-construct', array(&$this));
	}

	/**
	 * @return bool
	 */
	public function Validate()
	{
		switch (true)
		{
			case !api_Validate::Port($this->IncomingMailPort):
				throw new CApiValidationException(Errs::Validation_InvalidPort, null, array(
					'{{ClassName}}' => 'CAccount', '{{ClassField}}' => 'IncomingMailPort'));
				
			case !api_Validate::Port($this->OutgoingMailPort):
				throw new CApiValidationException(Errs::Validation_InvalidPort, null, array(
					'{{ClassName}}' => 'CAccount', '{{ClassField}}' => 'OutgoingMailPort'));
				
			case (!$this->IsDefaultDomain && api_Validate::IsEmpty($this->Name)):
				throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CDomain', '{{ClassField}}' => 'Name'));
				
			case api_Validate::IsEmpty($this->IncomingMailServer):
				throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CDomain', '{{ClassField}}' => 'IncomingMailServer'));
				
			case api_Validate::IsEmpty($this->OutgoingMailServer):
				throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CDomain', '{{ClassField}}' => 'OutgoingMailServer'));
		}
		
		return true;
	}
	
	/**
	 * @return array
	 */
	public function GetMap()
	{
		return self::GetStaticMap();
	}
	
	/**
	 * @return array
	 */
	static public function GetStaticMap()
	{
		return array(
			'IdDomain'	=> array('int', 'id_domain', false, false),
			'Name'		=> array('string', 'name'),
			'Url'		=> array('string', 'url'),

			'OverrideSettings'	=> array('bool', 'override_settings'),
			'IsInternal'		=> array('bool', 'is_internal'),
			'IsDefaultDomain'	=> array('bool'),

			// Common
			'SiteName'				=> array('string', 'site_name'),
			'DefaultLanguage'		=> array('string', 'lang'),
			'DefaultTimeZone'		=> array('int', 'def_user_timezone'),
			'DefaultTimeFormat'		=> array('int', 'def_user_timeformat'),
			'AllowRegistration'		=> array('bool', 'allow_registration'),
			'AllowPasswordReset'	=> array('bool', 'allow_pass_reset'),

			// WebMail
			'AllowWebMail'			=> array('bool', 'allow_webmail'),
			'IncomingMailProtocol'	=> array('int', 'mail_protocol'),
			'IncomingMailServer'	=> array('string', 'mail_inc_host'),
			'IncomingMailPort'		=> array('int', 'mail_inc_port'),
			'IncomingMailUseSSL'	=> array('bool', 'mail_inc_ssl'),

			'OutgoingMailServer'	=> array('string', 'mail_out_host'),
			'OutgoingMailPort'		=> array('int', 'mail_out_port'),
			'OutgoingMailAuth'		=> array('int', 'mail_out_auth'),
			'OutgoingMailLogin'		=> array('string', 'mail_out_login'),
			'OutgoingMailPassword'	=> array('password', 'mail_out_pass'),
			'OutgoingMailUseSSL'	=> array('bool', 'mail_out_ssl'),
			'OutgoingSendingMethod'	=> array('int', 'mail_out_method'),

			'UserQuota'				=> array('int', 'user_quota'),
			'AutoCheckMailInterval'	=> array('int', 'check_interval'),

			'DefaultSkin'	=> array('string', 'skin'),
			'MailsPerPage'	=> array('int', 'msgs_per_page'),

			'AllowUsersChangeInterfaceSettings'	=> array('bool', 'allow_change_interface_settings'),
			'AllowUsersChangeEmailSettings'		=> array('bool', 'allow_change_account_settings'),
			'AllowUsersAddNewAccounts'			=> array('bool', 'allow_users_add_acounts'),
			'AllowNewUsersRegister'				=> array('bool', 'allow_new_users_register'),

			'Layout'	=> array('int', 'layout'),
			'DetectSpecialFoldersWithXList'	=> array('int', 'xlist'),

			// Contacts
			'AllowContacts'			=> array('bool', 'allow_contacts'),
			'ContactsPerPage'		=> array('int', 'contacts_per_page'),
			'GlobalAddressBook'		=> array('int', 'global_addr_book'),

			// Calendar
			'AllowCalendar'			=> array('bool', 'allow_calendar'),
			'CalendarShowWeekends'	=> array('bool', 'cal_show_weekends'),
			'CalendarWorkdayStarts'	=> array('int', 'cal_workday_starts'),
			'CalendarWorkdayEnds'	=> array('int', 'cal_workday_ends'),
			'CalendarShowWorkDay'	=> array('bool', 'cal_show_workday'),
			'CalendarWeekStartsOn'	=> array('int', 'cal_week_starts_on'),
			'CalendarDefaultTab'	=> array('int', 'cal_default_tab')
		);
	}

	/**
	 * @return bool
	 */
	public function InitBeforeChange()
	{
		parent::InitBeforeChange();
		if (!$this->OverrideSettings && !$this->IsDefaultDomain)
		{
			/* @var $oApiDomainsManager CApiDomainsManager */
			$oApiDomainsManager = CApi::Manager('domains');

			$oDefDomain = $oApiDomainsManager->GetDefaultDomain();
			$aOverridenSettingsMap = $this->GetOverridenSettingsMap();

			foreach ($aOverridenSettingsMap as $sName)
			{
				$this->{$sName} = $oDefDomain->{$sName};
			}
		}
		
		return true;
	}

	/**
	 * @param stdClass $oRow
	 */
	public function InitByDbRow($oRow)
	{
		parent::InitByDbRow($oRow);

		$this->InitBeforeChange();
		$this->FlushObsolete();
	}

	/**
	 * @return array
	 */
	public function &GetFoldersMap()
	{
		return $this->aFolders;
	}

	/**
	 * @return array
	 */
	public function GetOverridenSettingsMap()
	{
		return array(
			'SiteName',
			'DefaultLanguage',
			'DefaultTimeZone',
			'DefaultTimeFormat',
			'AllowRegistration',
			'AllowPasswordReset',

			'AllowWebMail',
			'UserQuota',
			'AutoCheckMailInterval',
			'DefaultSkin',
			'MailsPerPage',
			'AllowUsersChangeInterfaceSettings',
			'AllowUsersChangeEmailSettings',
			'AllowUsersAddNewAccounts',
			'AllowNewUsersRegister',

			'Layout',
			'DetectSpecialFoldersWithXList',

			'AllowContacts',
			'ContactsPerPage',
			'GlobalAddressBook',

			'AllowCalendar',
			'CalendarShowWeekends',
			'CalendarWorkdayStarts',
			'CalendarWorkdayEnds',
			'CalendarShowWorkDay',
			'CalendarWeekStartsOn',
			'CalendarDefaultTab'
		);
	}
	
	/**
	 * @return array
	 */
	public function GetSettingsMap()
	{
		return array(
			'SiteName'				=> 'Common/SiteName',
			'DefaultLanguage'		=> 'Common/DefaultLanguage',
			'DefaultTimeZone'		=> 'Common/DefaultTimeZone',
			'DefaultTimeFormat'		=> 'Common/DefaultTimeFormat',
			'AllowRegistration'		=> 'Common/AllowRegistration',
			'AllowPasswordReset'	=> 'Common/AllowPasswordReset',
			
			'AllowWebMail'			=> 'WebMail/AllowWebMail',
			'IncomingMailProtocol'	=> 'WebMail/IncomingMailProtocol',
			'IncomingMailServer'	=> 'WebMail/IncomingMailServer',
			'IncomingMailPort'		=> 'WebMail/IncomingMailPort',
			'IncomingMailUseSSL'	=> 'WebMail/IncomingMailUseSSL',
			
			'OutgoingMailServer'	=> 'WebMail/OutgoingMailServer',
			'OutgoingMailPort'		=> 'WebMail/OutgoingMailPort',
			'OutgoingMailAuth'		=> 'WebMail/OutgoingMailAuth',
			'OutgoingMailLogin'		=> 'WebMail/OutgoingMailLogin',
			'OutgoingMailPassword'	=> 'WebMail/OutgoingMailPassword',
			'OutgoingMailUseSSL'	=> 'WebMail/OutgoingMailUseSSL',
			'OutgoingSendingMethod'	=> 'WebMail/OutgoingSendingMethod',
			'UserQuota'				=> 'WebMail/UserQuota',
			'AutoCheckMailInterval'	=> 'WebMail/AutoCheckMailInterval',
			'DefaultSkin'			=> 'WebMail/DefaultSkin',
			'MailsPerPage'			=> 'WebMail/MailsPerPage',
			'AllowUsersChangeInterfaceSettings'		=> 'WebMail/AllowUsersChangeInterfaceSettings',
			'AllowUsersChangeEmailSettings'			=> 'WebMail/AllowUsersChangeEmailSettings',
			'AllowUsersAddNewAccounts'				=> 'WebMail/AllowUsersAddNewAccounts',
			'AllowNewUsersRegister'					=> 'WebMail/AllowNewUsersRegister',
			
			'Layout'	=> 'WebMail/Layout',
			'DetectSpecialFoldersWithXList'	=> 'WebMail/DetectSpecialFoldersWithXList',
			
			'AllowContacts'			=> 'Contacts/AllowContacts',
			'ContactsPerPage'		=> 'Contacts/ContactsPerPage',
			'GlobalAddressBook'		=> 'Contacts/GlobalAddressBook/Sql/Visibility',

			'AllowCalendar'			=> 'Calendar/AllowCalendar',
			'CalendarShowWeekends'	=> 'Calendar/ShowWeekends',
			'CalendarWorkdayStarts'	=> 'Calendar/WorkdayStarts',
			'CalendarWorkdayEnds'	=> 'Calendar/WorkdayEnds',
			'CalendarShowWorkDay'	=> 'Calendar/ShowWorkDay',
			'CalendarWeekStartsOn'	=> 'Calendar/WeekStartsOn',
			'CalendarDefaultTab'	=> 'Calendar/DefaultTab',
		);			
	}
}
