<?php

/**
 * AfterLogic Api by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in LICENSE.txt
 *
 * @package Api
 */

CApi::Run();
CApi::PostRun();

/**
 * @package Api
 */
class CApi
{
	/**
	 * @var CApiGlobalManager
	 */
	static $oManager;

	/**
	 * @var CApiPluginManager
	 */
	static $oPlugin;
	
	/**
	 * @var array
	 */
	static $aConfig;

	/**
	 * @var bool
	 */
	static $bIsValid;
	
	static public function Run()
	{
		if (!is_object(CApi::$oManager))
		{
			CApi::Inc('common.constants');
			CApi::Inc('common.enum');
			CApi::Inc('common.exception');
			CApi::Inc('common.utils');
			CApi::Inc('common.container');
			CApi::Inc('common.manager');
			CApi::Inc('common.xml');
			CApi::Inc('common.plugin');
			
			CApi::Inc('common.utils.get');
			CApi::Inc('common.utils.post');
			CApi::Inc('common.utils.session');
			
			CApi::Inc('common.db.storage');

			CApi::$oManager = new CApiGlobalManager();
			CApi::$aConfig = include CApi::RootPath().'/common/config.php';

			$sSettingsFile = CApi::DataPath().'/settings/config.php';
			if (@file_exists($sSettingsFile))
			{
				$aAppConfig = include $sSettingsFile;
				if (is_array($aAppConfig))
				{
					CApi::$aConfig = array_merge(CApi::$aConfig, $aAppConfig);
				}
			}

			CApi::$oPlugin = new CApiPluginManager(CApi::$oManager);
			CApi::$bIsValid = CApi::validateApi();

			CApi::$oManager->PrepareStorageMap();
		}
	}

	static public function PostRun()
	{
		CApi::Manager('users');
		CApi::Manager('domains');
	}

	/**
	 * @return CApiPluginManager
	 */
	static public function Plugin()
	{
		return CApi::$oPlugin;
	}

	/**
	 * @param string $sManagerType
	 */
	static public function Manager($sManagerType)
	{
		return CApi::$oManager->GetByType($sManagerType);
	}

	/**
	 * @return CApiGlobalManager
	 */
	static public function GetManager()
	{
		return CApi::$oManager;
	}

	/**
	 * @return api_Settings
	 */
	static public function &GetSettings()
	{
		return CApi::$oManager->GetSettings();
	}

	/**
	 * @param string $sKey
	 * @param mixed $mDefault = null
	 * @return mixed
	 */
	static public function GetConf($sKey, $mDefault = null)
	{
		return (isset(CApi::$aConfig[$sKey])) ? CApi::$aConfig[$sKey] : $mDefault;
	}
	
	/**
	 * @param string $sKey
	 * @param mixed $mValue
	 * @return void
	 */
	static public function SetConf($sKey, $mValue)
	{
		CApi::$aConfig[$sKey] = $mValue;
	}

	/**
	 * @return bool
	 */
	static public function ManagerInc($sManagerName, $sFileName)
	{
		$sManagerName = preg_replace('/[^a-z]/', '', strtolower($sManagerName));
		return CApi::Inc('common.managers.'.$sManagerName.'.'.$sFileName);
	}

	/**
	 * @return bool
	 */
	static public function ManagerPath($sManagerName, $sFileName)
	{
		$sManagerName = preg_replace('/[^a-z]/', '', strtolower($sManagerName));
		return CApi::IncPath('common.managers.'.$sManagerName.'.'.$sFileName);
	}
	
	/**
	 * @return bool
	 */
	static public function StorageInc($sManagerName, $sStorageName, $sFileName)
	{
		$sManagerName = preg_replace('/[^a-z]/', '', strtolower($sManagerName));
		$sStorageName = preg_replace('/[^a-z]/', '', strtolower($sStorageName));
		return CApi::Inc('common.managers.'.$sManagerName.'.storages.'.$sStorageName.'.'.$sFileName);
	}

	/**
	 * @return bool
	 */
	static public function IncPath($sFileName)
	{
		$sFileName = preg_replace('/[^a-z0-9\._\-]/', '', strtolower($sFileName));
		$sFileName = preg_replace('/[\.]+/', '.', $sFileName);
		$sFileName = str_replace('.', '/', $sFileName);
		
		return CApi::RootPath().'/'.$sFileName.'.php';
	}
	/**
	 * @return bool
	 */
	static public function Inc($sFileName)
	{
		static $aCache = array();
		
		$sFileFullPath = '';
		$sFileName = preg_replace('/[^a-z0-9\._\-]/', '', strtolower($sFileName));
		$sFileName = preg_replace('/[\.]+/', '.', $sFileName);
		$sFileName = str_replace('.', '/', $sFileName);
		if (isset($aCache[$sFileName]))
		{
			return true;
		}
		else
		{
			$sFileFullPath = CApi::RootPath().'/'.$sFileName.'.php';
			if (@file_exists($sFileFullPath))
			{
				$aCache[$sFileName] = true;
				include_once $sFileFullPath;
				return true;
			}
		}
		
		exit('FILE NOT EXITS = '.$sFileFullPath);
		return false;
	}

	/**
	 * @param string $sNewLocation
	 */
	static public function Location($sNewLocation)
	{
		CApi::Log('Location: '.$sNewLocation);
		@header('Location: '.$sNewLocation);
	}

	/**
	 * @param string $sDesc
	 * @param CAccount $oAccount
	 */
	static public function LogEvent($sDesc, CAccount $oAccount)
	{
		$oSettings =& CApi::GetSettings();
		
		if ($oSettings && $oSettings->GetConf('Common/EnableEventLogging'))
		{
			$sDate = @date('H:i:s');
			CApi::Log('Event: '.$oAccount->Email.' > '.$sDesc);
			CApi::LogOnly('['.$sDate.'] '.$oAccount->Email.' > '.$sDesc, CApi::GetConf('log.event-file', 'event.txt'));
		}
	}

	/**
	 * @param mixed $mObject
	 * @param int $iLogLevel = ELogLevel::Full
	 * @param string $sFilePrefix = ''
	 */
	static public function LogObject($mObject, $iLogLevel = ELogLevel::Full, $sFilePrefix = '')
	{
		CApi::Log(print_r($mObject, true), $iLogLevel, $sFilePrefix);
	}
	
	/**
	 * @param string $sDesc
	 * @param int $iLogLevel = ELogLevel::Full
	 * @param string $sFilePrefix = ''
	 */
	static public function Log($sDesc, $iLogLevel = ELogLevel::Full, $sFilePrefix = '')
	{
		static $bIsFirst = true;
		
		$oSettings =& CApi::GetSettings();
		$sLogFile = $sFilePrefix.CApi::GetConf('log.log-file', 'log.txt');
		
		if ($oSettings && $oSettings->GetConf('Common/EnableLogging')
			&& $iLogLevel <= $oSettings->GetConf('Common/LoggingLevel'))
		{
			$aMicro = explode('.', microtime(true));
			$sDate = @date('H:i:s.').str_pad((isset($aMicro[1]) ? substr($aMicro[1], 0, 2) : '0'), 2, '0');
			if ($bIsFirst)
			{
				$sUri = api_Utils::RequestUri();
				$bIsFirst = false;
				$sPost = (isset($_POST) && count($_POST) > 0) ? ' [POST('.count($_POST).')]' : '';

				CApi::LogOnly(API_CRLF.'['.$sDate.']'.$sPost.' '.$sUri, $sLogFile);
				if (!empty($sPost))
				{
					if (CApi::GetConf('labs.log.post-view', false))
					{
						CApi::LogOnly('['.$sDate.'] POST > '.print_r($_POST, true), $sLogFile);
					}
					else
					{
						CApi::LogOnly('['.$sDate.'] POST > ['.implode(', ', array_keys($_POST)).']', $sLogFile);
					}
				}
				CApi::LogOnly('['.$sDate.']', $sLogFile);

				@register_shutdown_function('CApi::LogEnd');
			}

			CApi::LogOnly('['.$sDate.'] '.$sDesc, $sLogFile);
		}
	}

	/**
	 * @param string $sDesc
	 * @param string $sLogFile
	 */
	static public function LogOnly($sDesc, $sLogFile)
	{
		@error_log($sDesc.API_CRLF, 3, CApi::DataPath().'/logs/'.$sLogFile);
	}

	static public function LogEnd()
	{
		CApi::Log('# script shutdown');
	}

	/**
	 * @return string
	 */
	static public function RootPath()
	{
		defined('API_ROOTPATH') || define('API_ROOTPATH', rtrim(dirname(__FILE__).'/', '/\\'));
		return API_ROOTPATH;
	}

	/**
	 * @return string
	 */
	static public function WebMailPath()
	{
		return CApi::RootPath().API_PATH_TO_WEBMAIL;
	}
	
	/**
	 * @return string
	 */
	static public function Version()
	{
		static $sVersion = null;
		if (null === $sVersion)
		{
			$sAppVersion = @file_get_contents(CApi::WebMailPath().'VERSION');
			$sVersion = (false === $sAppVersion) ? '0.0.0' : $sAppVersion;
		}
		return $sVersion;
	}
	
	/**
	 * @return string
	 */
	static public function VersionJs()
	{
		return preg_replace('/[^0-9a-z]/', '', CApi::Version());
	}

	/**
	 * @return string
	 */
	static public function DataPath()
	{
		if (!defined('API_DATA_FOLDER') && @file_exists(CApi::WebMailPath().'inc_settings_path.php'))
		{
			include CApi::WebMailPath().'inc_settings_path.php';
			if (isset($dataPath) && null !== $dataPath)
			{
				define('API_DATA_FOLDER', api_Utils::GetFullPath($dataPath, CApi::WebMailPath()));
			}
		}
		return defined('API_DATA_FOLDER') ? API_DATA_FOLDER : '';
	}

	/**
	 * @return bool
	 */
	static protected function validateApi()
	{
		$iResult = 1;

		$oSettings =& CApi::GetSettings();
		$iResult &= $oSettings && is_a($oSettings, 'api_Settings');
			
		return (bool) $iResult;
	}

	/**
	 * @return bool
	 */
	static public function IsValid()
	{
		return (bool) CApi::$bIsValid;
	}
}
