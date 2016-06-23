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
class api_Utils
{
	/**
	 * @return float
	 */
	static public function Microtime()
	{
		return microtime(true);
	}

	/**
	 * @param array $aArray
	 * @param string $sKey
	 * @param mixed $mDefault
	 * @return mixed
	 */
	static public function ArrayValue($aArray, $sKey, $mDefault)
	{
		return (isset($aArray[$sKey])) ? $aArray[$sKey] : $mDefault;
	}

	/**
	 * @param string $sValue
	 * @return string
	 */
	static public function EncodeSpecialXmlChars($sValue)
	{
		return str_replace('>', '&gt;', str_replace('<', '&lt;', str_replace('&', '&amp;', $sValue)));
	}
	
	/**
	 * @param string $sValue
	 * @return string
	 */
	static public function DecodeSpecialXmlChars($sValue)
	{
		return str_replace('&amp;', '&', str_replace('&lt;', '<', str_replace('&gt;', '>', $sValue)));
	}

	/**
	 * @param string $sValue
	 * @return string
	 */
	static public function EncodeSimpleSpecialXmlChars($sValue)
	{
		return str_replace(']]>','&#93;&#93;&gt;', $sValue);
	}

	/**
	 * @param string $sValue
	 * @return string
	 */
	static public function DecodeSimpleSpecialXmlChars($sValue)
	{
		return str_replace('&#93;&#93;&gt;', ']]>', $sValue);
	}
	
	/**
	 * @param string $sValue
	 * @return string
	 */
	static public function ShowCRLF($sValue)
	{
		return str_replace(array("\r", "\n", "\t"), array('\r', '\n', '\t'), $sValue);
	}

	/**
	 * @param string $sPath
	 * @param string $sPrefix = null
	 * @return string
	 */
	static public function GetFullPath($sPath, $sPrefix = null)
	{
		if ($sPrefix !== null && !self::IsFullPath($sPath))
		{
			$sPath = rtrim($sPrefix, '\\/').'/'.trim($sPath, '\\/');
		}

		if (@is_dir($sPath))
		{
			$sPath = rtrim(str_replace('\\', '/', realpath($sPath)), '/');
		}

		return $sPath;
	}

	/**
	 * @param string $sPpath
	 * @return bool
	 */
	static public function IsFullPath($sPpath)
	{
		if (strlen($sPpath) > 0)
		{
			return (($sPpath{0} == '/' || $sPpath{0} == '\\') || (strlen($sPpath) > 1 && self::IsWin() && $sPpath{1} == ':'));
		}
		return false;
	}

	/**
	 * @return bool
	 */
	static public function IsWin()
	{
		return (defined('PHP_OS') && 'WIN' === strtoupper(substr(PHP_OS, 0, 3)));
	}

	/**
	 * @param array $aArray
	 * @param string $sType
	 * @return array
	 */
	static public function SetTypeArrayValue($aArray, $sType)
	{
		$aResult = array();
		foreach ($aArray as $mValue)
		{
			settype($mValue, $sType);
			$aResult[] = $mValue;
		}
		return $aResult;
	}

	/**
	 * @param string $sPrefix
	 * @return string
	 */
	static public function ClearPrefix($sPrefix)
	{
		$sNewPrefix = preg_replace('/[^a-z0-9_]/i', '_', $sPrefix);
		if ($sNewPrefix !== $sPrefix)
		{
			$sNewPrefix = preg_replace('/[_]+/', '_', $sNewPrefix);
		}
		return $sNewPrefix;
	}
	
	/**
	 * @param string $sPassword
	 * @return string
	 */
	static public function EncodePassword($sPassword)
	{
		if (empty($sPassword))
		{
			return '';
		}

		$sPlainBytes = $sPassword;
		$sEncodeByte = $sPlainBytes{0};
		$sResult = bin2hex($sEncodeByte);

		for ($iIndex = 1, $iLen = strlen($sPlainBytes); $iIndex < $iLen; $iIndex++)
		{
			$sPlainBytes{$iIndex} = ($sPlainBytes{$iIndex} ^ $sEncodeByte);
			$sResult .= bin2hex($sPlainBytes{$iIndex});
		}

		return $sResult;
	}

	/**
	 * @param string $sPassword
	 * @return string
	 */
	static public function DecodePassword($sPassword)
	{
		$sResult = '';
		$iPasswordLen = strlen($sPassword);

		if (0 < $iPasswordLen && strlen($sPassword) % 2 == 0)
		{
			$sDecodeByte = chr(hexdec(substr($sPassword, 0, 2)));
			$sPlainBytes = $sDecodeByte;
			$iStartIndex = 2;
			$iCurrentByte = 1;

			do
			{
				$sHexByte = substr($sPassword, $iStartIndex, 2);
				$sPlainBytes .= (chr(hexdec($sHexByte)) ^ $sDecodeByte);

				$iStartIndex += 2;
				$iCurrentByte++;
			}
			while ($iStartIndex < $iPasswordLen);

			$sResult = $sPlainBytes;
		}
		return $sResult;
	}

	/**
	 * @param string $sEmail
	 * @return string
	 */
	static public function GetAccountNameFromEmail($sEmail)
	{
		$sResult = '';
		if (!empty($sEmail))
		{
			$iPos = strpos($sEmail, '@');
			$sResult = (false === $iPos) ? $sEmail : substr($sEmail, 0, $iPos);
		}

		return $sResult;
	}

	/**
	 * @param string $sEmail
	 * @return string
	 */
	static public function GetDomainFromEmail($sEmail)
	{
		$sResult = '';
		if (!empty($sEmail))
		{
			$iPos = strpos($sEmail, '@');
			if (false !== $iPos)
			{
				$sResult = substr($sEmail, $iPos + 1);
			}
		}
		return $sResult;
	}

	/**
	 * @param string $iSizeInBytes
	 * @return string
	 */
	static public function GetFriendlySize($iSizeInBytes)
	{
		$iSizeInKB = ceil($iSizeInBytes / 1024);
		$iSizeInMB = $iSizeInKB / 1024;
		if ($iSizeInMB >= 100)
		{
			$iSizeInKB = ceil($iSizeInMB * 10 / 10).'MB';
		}
		else if ($iSizeInMB > 1)
		{
			$iSizeInKB = (ceil($iSizeInMB * 10) / 10).'MB';
		}
		else
		{
			$iSizeInKB = $iSizeInKB.'KB';
		}

		return $iSizeInKB;
	}

	/**
	 * @staticvar array $aMapping
	 * @param int $iCodePage
	 * @return string
	 */
	static public function GetCodePageName($iCodePage)
	{
		static $aMapping = array(
			0 => 'default',
			51936 => 'euc-cn',
			936 => 'gb2312',
			950 => 'big5',
			946 => 'euc-kr',
			50225 => 'iso-2022-kr',
			50220 => 'iso-2022-jp',
			932 => 'shift-jis',
			65000 => 'utf-7',
			65001 => 'utf-8',
			1250 => 'windows-1250',
			1251 => 'windows-1251',
			1252 => 'windows-1252',
			1253 => 'windows-1253',
			1254 => 'windows-1254',
			1255 => 'windows-1255',
			1256 => 'windows-1256',
			1257 => 'windows-1257',
			1258 => 'windows-1258',
			20866 => 'koi8-r',
			28591 => 'iso-8859-1',
			28592 => 'iso-8859-2',
			28593 => 'iso-8859-3',
			28594 => 'iso-8859-4',
			28595 => 'iso-8859-5',
			28596 => 'iso-8859-6',
			28597 => 'iso-8859-7',
			28598 => 'iso-8859-8'
		);

		return (isset($aMapping[$iCodePage])) ? $aMapping[$iCodePage] : '';
	}

	/**
	 * @staticvar array $aMapping
	 * @param string $sCodePageName
	 * @return int
	 */
	static public function GetCodePageNumber($sCodePageName)
	{
		static $aMapping = array(
			'default' => 0,
			'euc-cn' => 51936,
			'gb2312' => 936,
			'big5' => 950,
			'euc-kr' => 949,
			'iso-2022-kr' => 50225,
			'iso-2022-jp' => 50220,
			'shift-jis' => 932,
			'utf-7' => 65000,
			'utf-8' => 65001,
			'windows-1250' => 1250,
			'windows-1251' => 1251,
			'windows-1252' => 1252,
			'windows-1253' => 1253,
			'windows-1254' => 1254,
			'windows-1255' => 1255,
			'windows-1256' => 1256,
			'windows-1257' => 1257,
			'windows-1258' => 1258,
			'koi8-r' => 20866,
			'iso-8859-1' => 28591,
			'iso-8859-2' => 28592,
			'iso-8859-3' => 28593,
			'iso-8859-4' => 28594,
			'iso-8859-5' => 28595,
			'iso-8859-6' => 28596,
			'iso-8859-7' => 28597,
			'iso-8859-8' => 28598
		);

		return (isset($aMapping[$sCodePageName])) ? $aMapping[$sCodePageName] : 0;
	}

	/**
	 * @param string $sDateTime
	 * @return array | bool
	 */
	static public function DateParse($sDateTime)
	{
		if (function_exists('date_parse'))
		{
			return date_parse($sDateTime);
		}

		$mReturn = false;
		$aDateTime = explode(' ', $sDateTime, 2);
		if (count($aDateTime) == 2)
		{
			$aDate = explode('-', trim($aDateTime[0]), 3);
			$aTime = explode(':', trim($aDateTime[1]), 3);

			if (3 === count($aDate) && 3 === count($aTime))
			{
				$mReturn = array(
					'year' => $aDate[0],
					'day' => $aDate[2],
					'month' => $aDate[1],

					'hour' => $aTime[0],
					'minute' => $aTime[1],
					'second' => $aTime[2]
				);
			}
		}
		return $mReturn;
	}

	/**
	 * @param int $iDefaultTimeZone
	 * @return short
	 */
	static public function GetTimeOffset($iDefaultTimeZone)
	{
		if (isset($_SESSION[API_JS_TIMEOFFSET]))
		{
			return $_SESSION[API_JS_TIMEOFFSET];
		}

		$aLocalTimeArray = localtime(time(), true);

		$iDaylightSaveMinutes = isset($aLocalTimeArray['tm_isdst']) ? $aLocalTimeArray['tm_isdst'] * 60 : 0;

		$iTimeOffset = 0;

		switch ($iDefaultTimeZone)
		{
			default:
			case 0:
				return date('O') / 100 * 60;
				break;
			case 1:
				$iTimeOffset = -12 * 60;
				break;
			case 2:
				$iTimeOffset = -11 * 60;
				break;
			case 3:
				$iTimeOffset = -10 * 60;
				break;
			case 4:
				$iTimeOffset = -9 * 60;
				break;
			case 5:
				$iTimeOffset =  -8*60;
				break;
			case 6:
			case 7:
				$iTimeOffset = -7 * 60;
				break;
			case 8:
			case 9:
			case 10:
			case 11:
				$iTimeOffset = -6 * 60;
				break;
			case 12:
			case 13:
			case 14:
				$iTimeOffset = -5 * 60;
				break;
			case 15:
			case 16:
			case 17:
				$iTimeOffset = -4 * 60;
				break;
			case 18:
				$iTimeOffset = -3.5 * 60;
				break;
			case 19:
			case 20:
			case 21:
				$iTimeOffset = -3 * 60;
				break;
			case 22:
				$iTimeOffset = -2 * 60;
				break;
			case 23:
			case 24:
				$iTimeOffset = -60;
				break;
			case 25:
			case 26:
				$iTimeOffset = 0;
				break;
			case 27:
			case 28:
			case 29:
			case 30:
			case 31:
				$iTimeOffset = 60;
				break;
			case 32:
			case 33:
			case 34:
			case 35:
			case 36:
			case 37:
				$iTimeOffset = 2 * 60;
				break;
			case 38:
			case 39:
			case 40:
			case 41:
				$iTimeOffset = 3 * 60;
				break;
			case 42:
				$iTimeOffset = 3.5 * 60;
				break;
			case 43:
			case 44:
				$iTimeOffset = 4 * 60;
				break;
			case 45:
				$iTimeOffset = 4.5 * 60;
				break;
			case 46:
			case 47:
				$iTimeOffset = 5 * 60;
				break;
			case 48:
				$iTimeOffset = 5.5 * 60;
				break;
			case 49:
				$iTimeOffset = 5 * 60 + 45;
				break;
			case 50:
			case 51:
			case 52:
				$iTimeOffset = 6 * 60;
				break;
			case 53:
				$iTimeOffset = 6.5 * 60;
			case 54:
			case 55:
				$iTimeOffset = 7 * 60;
				break;
			case 56:
			case 57:
			case 58:
			case 59:
			case 60:
				$iTimeOffset = 8 * 60;
				break;
			case 61:
			case 62:
			case 63:
				$iTimeOffset = 9 * 60;
				break;
			case 64:
			case 65:
				$iTimeOffset = 9.5 * 60;
				break;
			case 66:
			case 67:
			case 68:
			case 69:
			case 70:
				$iTimeOffset = 10 * 60;
				break;
			case 71:
				$iTimeOffset = 11 * 60;
				break;
			case 72:
			case 73:
				$iTimeOffset = 12 * 60;
				break;
			case 74:
				$iTimeOffset = 13 * 60;
				break;
		}

		return $iTimeOffset + $iDaylightSaveMinutes;
	}
	
	/**
	 * @param string $sDir
	 */
	static public function RecRmdir($sDir)
	{ 
		if (is_dir($sDir))
		{ 
			$aObjects = scandir($sDir); 
			foreach ($aObjects as $sObject)
			{ 
				if ($sObject != '.' && $sObject != '..')
				{ 
					if (filetype($sDir.'/'.$sObject) == 'dir') 
					{
						self::RecRmdir($sDir.'/'.$sObject);
					}
					else
					{
						unlink($sDir.'/'.$sObject);
					}
				}
			}
			
			reset($aObjects); 
			rmdir($sDir); 
		} 
	}

	/**
	 * @return bool
	 */
	static public function HasSslSupport()
	{
		return (bool) @function_exists('openssl_open');
	}
	
	/**
	 * @return bool
	 */
	static public function IsMcryptSupport()
	{
		return (bool) @function_exists('mcrypt_encrypt');
	}
	
	/**
	 * @return bool
	 */
	static public function IsIconvSupport()
	{
		return (bool) @function_exists('iconv');
	}
	
	/**
	 * @return bool
	 */
	static public function IsGzipSupport()
	{
		return (bool) 
			((false !== strpos(isset($_SERVER['HTTP_ACCEPT_ENCODING'])
				? $_SERVER['HTTP_ACCEPT_ENCODING'] : '', 'gzip'))
			&& function_exists('gzencode'));
	}

	/**
	 * @param int $iBigInt
	 * @return int
	 */
	static public function GetGoodBigInt($iBigInt)
	{
		if (null === $iBigInt || false == $iBigInt)
		{
			return 0;
		}
		else if ($iBigInt > API_PHP_INT_MAX)
		{
			return API_PHP_INT_MAX;
		}
		else if ($iBigInt < API_PHP_INT_MIN)
		{
			return API_PHP_INT_MIN;
		}

		return (int) $iBigInt;
	}

	/**
	 * @param string $sPabUri
	 * @return array
	 */
	static public function LdapUriParse($sPabUri)
	{
		$aReturn = array(
			'host' => 'localhost',
			'port' => 389,
			'search_dn' => '',
		);

		$sPabUriLower = strtolower($sPabUri);
		if ('ldap://' === substr($sPabUriLower, 0, 7))
		{
			$sPabUriLower = substr($sPabUriLower, 7);
		}

		$aPabUriLowerExplode = explode('/', $sPabUriLower, 2);
		$aReturn['search_dn'] = isset($aPabUriLowerExplode[1]) ? $aPabUriLowerExplode[1] : '';

		if (isset($aPabUriLowerExplode[0]))
		{
			$aPabUriLowerHostPortExplode = explode(':', $aPabUriLowerExplode[0], 2);
			$aReturn['host'] = isset($aPabUriLowerHostPortExplode[0]) ? $aPabUriLowerHostPortExplode[0] : $aReturn['host'];
			$aReturn['port'] = isset($aPabUriLowerHostPortExplode[1]) ? (int) $aPabUriLowerHostPortExplode[1] : $aReturn['port'];
		}

		return $aReturn;
	}

	/**
	 * @return string
	 */
	static public function RequestUri()
	{
		$sUri = '';
		if (isset($_SERVER['REQUEST_URI']))
		{
			$sUri = $_SERVER['REQUEST_URI'];
		}
		else
		{
			if (isset($_SERVER['SCRIPT_NAME']))
			{
				if (isset($_SERVER['argv'], $_SERVER['argv'][0]))
				{
					$sUri = $_SERVER['SCRIPT_NAME'].'?'.$_SERVER['argv'][0];
				}
				else if (isset($_SERVER['QUERY_STRING']))
				{
					$sUri = $_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING'];
				}
				else
				{
					$sUri = $_SERVER['SCRIPT_NAME'];
				}
			}
		}

		$sUri = '/'. ltrim($sUri, '/');
		return $sUri;
	}
	
	/**
	 * @return string $sFileName
	 * @return string
	 */
	static public function CsvToArray($sFileName)
	{
		if (!file_exists($sFileName) || !is_readable($sFileName))
		{
			return false;
		}

		$aHeaders = null;
		$aData = array();

		@setlocale(LC_CTYPE, 'en_US.UTF-8');
		if (false !== ($rHandle = @fopen($sFileName, 'rb')))
		{
			$sDelimiterSearchString = @fread($rHandle, 20);
			rewind($rHandle);

			$sDelimiter = (
				(int) strpos($sDelimiterSearchString, ',') > (int) strpos($sDelimiterSearchString, ';'))
					? ',' : ';';

			while (false !== ($mRow = fgetcsv($rHandle, 5000, $sDelimiter, '"')))
			{
				if (null === $aHeaders)
				{
					$aHeaders = $mRow;
				}
				else
				{
					$aNewItem = array();
					foreach ($aHeaders as $iIndex => $sHeaderValue)
					{
						$aNewItem[@iconv('utf-8', 'utf-8//IGNORE', $sHeaderValue)] =
							isset($mRow[$iIndex]) ? $mRow[$iIndex] : '';
					}
					
					$aData[] = $aNewItem;
				}
			}

			fclose($rHandle);
		}

		return $aData;
	}
}

/**
 * @package Api
 */
class api_Validate
{
	/**
	 * @param string $sFuncName
	 * @return string
	 */
	static public function GetError($sFuncName)
	{
		switch ($sFuncName)
		{
			case 'Port':
				return 'Required valid port.';
			case 'IsEmpty':
				return 'Required fields cannot be empty.';
		}
		
		return 'Error';
	}

	/**
	 * @param mixed $mValue
	 * @return bool
	 */
	static public function IsEmpty($mValue)
	{
		return empty($mValue);
	}

	/**
	 * @param mixed $mValue
	 * @return bool
	 */
	static public function Port($mValue)
	{
		$bResult = false;
		if (0 < strlen((string) $mValue) && is_numeric($mValue))
		{
			$iPort = (int) $mValue;
			if (0 < $iPort && $iPort < 65355)
			{
				$bResult = true;
			}
		}
		return $bResult;
	}
}

/**
 * @package Api
 */
class CApiValidationException extends CApiBaseException  {}

/**
 * @package Api
 */
class api_Ints
{
	/**
	 * @return int
	 */
	function getIntMax()
	{
		$iMax = 0x7fff;
		$iProbe = 0x7fffffff;
		while ($iMax == ($iProbe >> 16))
		{
			$iMax = $iProbe;
			$iProbe = ($iProbe << 16) + 0xffff;
		}
		return $iMax;
	}
}

function fNullCallback() {}

defined('API_PHP_INT_MAX') || define('API_PHP_INT_MAX', (int) api_Ints::getIntMax());
defined('API_PHP_INT_MIN') || define('API_PHP_INT_MIN', (int) (API_PHP_INT_MAX + 1));
