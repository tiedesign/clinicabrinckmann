<?php

/**
 * AfterLogic Api by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in LICENSE.txt
 *
 * @package Api
 */

/**
 * @package Api
 */
class CSession
{
	/**
	 * @var bool
	 */
	static $bIsMagicQuotesOn = false;

	private function __construct() {}

	/**
	 * @param string $sKey
	 * @return bool
	 */
	static public function Has($sKey)
	{
		return (isset($_SESSION[$sKey]));
	}

	/**
	 * @param string $sKey
	 * @param mixed $nmDefault = null
	 * @return mixed
	 */
	static public function Get($sKey, $nmDefault = null)
	{
		return (isset($_SESSION[$sKey])) ? self::_stripSlashesValue($_SESSION[$sKey]) : $nmDefault;
	}

	/**
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	static public function Set($sKey, $mValue)
	{
		$_SESSION[$sKey] = $mValue;
	}

	/**
	 * @param mixed $mValue
	 * @return mixed
	 */
	static private function _stripSlashesValue($mValue)
	{
		if (!self::$bIsMagicQuotesOn)
		{
			return $mValue;
		}

		$sType = gettype($mValue);
		if ($sType === 'string')
		{
			return stripslashes($mValue);
		}
		else if ($sType === 'array')
		{
			$aReturnValue = array();
			$mValueKeys = array_keys($mValue);
			foreach($mValueKeys as $sKey)
			{
				$aReturnValue[$sKey] = self::_stripSlashesValue($mValue[$sKey]);
			}
			return $aReturnValue;
		}
		else
		{
			return $mValue;
		}
	}

	/**
	 * @param string $sName
	 * @return bool
	 */
	static public function Start($sName)
	{
		if (@session_name() !== $sName)
		{
			if (@session_name())
			{
				@session_write_close();
				if (isset($GLOBALS['PROD_NAME']) && false !== strpos($GLOBALS['PROD_NAME'], 'Plesk')) // Plesk
				{
					@session_module_name('files');
				}
			}

			@session_set_cookie_params(0);
			@session_name($sName);
			return @session_start();
		}
		
		return true;
	}
}

CSession::$bIsMagicQuotesOn = (bool) ini_get('magic_quotes_gpc');
