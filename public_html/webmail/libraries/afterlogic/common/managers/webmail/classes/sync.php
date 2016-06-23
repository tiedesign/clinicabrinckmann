<?php

/**
 * AfterLogic Api by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in LICENSE.txt
 *
 * @package WebMail
 * @subpackage Classes
 */

/**
 * @property bool $EnableOutlookSync
 * @property bool $EnableMobileSync
 * @property string $MobileSyncUrl
 * @property string $MobileSyncContactDatabase
 * @property string $MobileSyncCalendarDatabase
 *
 * @package WebMail
 * @subpackage Classes
 */
class CSyncConfig extends api_AContainer
{
	public function __construct()
	{
		parent::__construct(get_class($this));
		
		$oSettings =& CApi::GetSettings();

		$aDefaults = array();
		$aSettingsMap = $this->GetSettingsMap();
		foreach ($aSettingsMap as $sProperty => $sSettingsName)
		{
			$aDefaults[$sProperty] = $oSettings->GetConf($sSettingsName);
		}
		
		$this->SetDefaults($aDefaults);
	}

	/**
	 * @return bool
	 */
	public function Validate()
	{
		if ($this->EnableMobileSync)
		{
			switch (true)
			{
				case api_Validate::IsEmpty($this->MobileSyncUrl):
					throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CSyncConfig', '{{ClassField}}' => 'MobileSyncUrl'));
					
				case api_Validate::IsEmpty($this->MobileSyncContactDatabase):
					throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CSyncConfig', '{{ClassField}}' => 'MobileSyncContactDatabase'));
					
				case api_Validate::IsEmpty($this->MobileSyncCalendarDatabase):
					throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CSyncConfig', '{{ClassField}}'=> 'MobileSyncCalendarDatabase'));
			}
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
			'EnableOutlookSync'	=> array('bool'),
			'EnableMobileSync'	=> array('bool'),
			
			'MobileSyncUrl'					=> array('string'),
			'MobileSyncContactDatabase'		=> array('string'),
			'MobileSyncCalendarDatabase'	=> array('string')
		);
	}
	
	/**
	 * @return array
	 */
	public function GetSettingsMap()
	{
		return array(
			'EnableOutlookSync'	=> 'Calendar/EnableOutlookSync',
			'EnableMobileSync'	=> 'Common/EnableMobileSync',
			'MobileSyncUrl'					=> 'Common/MobileSyncUrl',
			'MobileSyncContactDatabase'		=> 'Common/MobileSyncContactDataBase',
			'MobileSyncCalendarDatabase'	=> 'Common/MobileSyncCalendarDataBase'
		);
	}
}
