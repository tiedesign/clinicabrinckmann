<?php

/**
 * AfterLogic Api by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in LICENSE.txt
 *
 * @package Api
 * @subpackage Net
 */

CApi::Inc('common.net.abstract');

/**
 * @package Api
 * @subpackage Net
 */
class CApiImap4MailProtocol extends CApiNetAbstract
{
	/**
	 * @return bool
	 */
	public function Connect()
	{
		$bResult = false;
		if (parent::Connect())
		{
			$bResult = $this->CheckResponse('*', $this->GetResponse('*'));
		}
		return $bResult;
	}
	
	/**
	 * @param string $sLogin
	 * @param string $sPassword
	 * @return bool
	 */
	public function Login($sLogin, $sPassword)
	{
		return $this->SendCommand('LOGIN '.
			$this->escapeString($sLogin, true).' '.$this->escapeString($sPassword, true),
			array($this->escapeString($sPassword)));
	}
	
	/**
	 * @param string $sLogin
	 * @param string $sPassword
	 * @return bool
	 */
	public function ConnectAndLogin($sLogin, $sPassword)
	{
		return $this->Connect() && $this->Login($sLogin, $sPassword);
	}
	
	/**
	 * @return bool
	 */
	public function Disconnect()
	{
		return parent::Disconnect();
	}
	
	/**
	 * @return bool
	 */
	public function Logout()
	{
		return $this->SendCommand('LOGOUT');
	}
	
	/**
	 * @return bool
	 */
	public function LogoutAndDisconnect()
	{
		return $this->Logout() && $this->Disconnect();
	}

	/**
	 * @return bool
	 */
	public function GetNamespace()
	{
		$sNamespace = '';
		$sTag = $this->getNextTag();
		if ($this->WriteLine($sTag.' NAMESPACE'))
		{
			$sResponse = $this->GetResponse($sTag);
			if ($this->CheckResponse($sTag, $sResponse))
			{
				$a = array();
				if (false !== preg_match_all('/NAMESPACE \(\(".*?"\)\)/', $sResponse, $a)
					&& isset($a[0][0]) && is_string($a[0][0]))
				{
					$b = array();
					if (false !== preg_match('/\(\("([^"]*)" "/', $a[0][0], $b) && isset($b[1]))
					{
						$sNamespace = trim($b[1]);
					}
				}
			}
		}
		return $sNamespace;
	}
	
	
	/**
	 * @param string $sCmd
	 * @return bool
	 */
	public function SendLine($sCmd)
	{
		$sTag = $this->getNextTag();
		return $this->WriteLine($sTag.' '.$sCmd);
	}
	
	/**
	 * @param string $sCmd
	 * @param array $aHideValues = array()
	 * @return bool
	 */
	public function SendCommand($sCmd, $aHideValues = array())
	{
		$sTag = $this->getNextTag();
		if ($this->WriteLine($sTag.' '.$sCmd, $aHideValues))
		{
			return $this->CheckResponse($sTag, $this->GetResponse($sTag));
		}
		
		return false;
	}
	
	/**
	 * @param string $sTag
	 * @return string
	 */
	public function GetResponse($sTag)
	{
		$aResponse = array();
		$iLen = strlen($sTag);
		while(true)
		{
			$sLine = $this->ReadLine();
			if ($sLine == false)
			{
				break;
			}

			if (substr($sLine, 0, $iLen) === $sTag)
			{
				$aResponse[] = $sLine;
				break;
			}

			$aResponse[] = $sLine;
		}
		
		return trim(implode('', $aResponse));
	}
	
	/**
	 * @param string $sTag
	 * @param string $sResponse
	 * @return bool
	 */
	public function CheckResponse($sTag, $sResponse)
	{
		return ('OK' === substr($sResponse, strpos($sResponse, $sTag.' ') + strlen($sTag) + 1, 2));
	}
	
	/**
	 * @staticvar int $sTag
	 * @return string
	 */
	protected function getNextTag()
	{
		static $sTag = 1;
		return 'TAG'.($sTag++);
	}
	
	/**
	 * @param string $sLineForEscape
	 * @param bool $bAddQuot = false
	 * @return string
	 */
	protected function escapeString($sLineForEscape, $bAddQuot = false)
	{
		$sReturn = strtr($sLineForEscape, array('"' => '\\"', '\\' => '\\\\'));
		return ($bAddQuot) ? '"'.$sReturn.'"' : $sReturn;
	}
}

