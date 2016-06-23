<?php

/**
 * AfterLogic Api by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in LICENSE.txt
 *
 * @package Domains
 */

/**
 * @package Domains
 */
class CApiDomainsManager extends AApiManagerWithStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('domains', $oManager);

		$this->inc('classes.domain');
	}

	/**
	 * @return CDomain
	 */
	public function GetDefaultDomain()
	{
		return new CDomain();
	}

	/**
	 * @param string $sDomainId
	 * @return CDomain
	 */
	public function GetDomainById($sDomainId)
	{
		$oDomain = null;
		try
		{
			$oDomain = $this->oStorage->GetDomainById($sDomainId);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $oDomain;
	}
	
	/**
	 * @param string $sDomainName
	 * @return CDomain
	 */
	public function GetDomainByName($sDomainName)
	{
		$oDomain = null;
		try
		{
			$oDomain = $this->oStorage->GetDomainByName($sDomainName);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $oDomain;
	}

	/**
	 * @param string $sDomainUrl
	 * @return CDomain
	 */
	public function GetDomainByUrl($sDomainUrl)
	{
		$oDomain = null;
		try
		{
			$oDomain = $this->oStorage->GetDomainByUrl($sDomainUrl);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		
		$oDomain = (null === $oDomain) ? $this->GetDefaultDomain() : $oDomain;
		return $oDomain;
	}

	/**
	 * @param CDomain &$oDomain
	 * @return bool
	 */
	public function CreateDomain(CDomain &$oDomain)
	{
		$bResult = false;
		try
		{
			if ($oDomain->Validate())
			{
				if (!$this->DomainExists($oDomain->Name))
				{
					if (!$this->oStorage->CreateDomain($oDomain))
					{
						throw new CApiManagerException(Errs::DomainsManager_DomainCreateFailed);
					}
				}
				else
				{
					throw new CApiManagerException(Errs::DomainsManager_DomainAlreadyExists);
				}
			}
			
			$bResult = true;
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}
		
		return $bResult;
	}

	/**
	 * @param CDomain $oDomain
	 * @return bool
	 */
	public function UpdateDomain(CDomain $oDomain)
	{
		$bResult = false;
		try
		{
			if ($oDomain->Validate())
			{
				if ($oDomain->IsDefaultDomain)
				{
					$oSettings =& CApi::GetSettings();
					$aSettingsMap = $oDomain->GetSettingsMap();

					foreach ($aSettingsMap as $sProperty => $sSettingsName)
					{
						$oSettings->SetConf($sSettingsName, $oDomain->{$sProperty});
					}

					$bResult = $oSettings->SaveToXml();
				}
				else
				{
					if (!$this->oStorage->UpdateDomain($oDomain))
					{
						throw new CApiManagerException(Errs::DomainsManager_DomainUpdateFailed);
					}
					
					$bResult = true;
				}
			}
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}
		
		return $bResult;
	}
	
	/**
	 * @param array $aDomainsIds
	 * @return bool
	 */
	public function AreDomainsEmpty($aDomainsIds)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->AreDomainsEmpty($aDomainsIds);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $bResult;
	}
	
	/**
	 * @param array $aDomainsIds
	 * @return bool
	 */
	public function DeleteDomains($aDomainsIds)
	{
		$bResult = false;
		try
		{
			if (!$this->AreDomainsEmpty($aDomainsIds))
			{
				throw new CApiManagerException(Errs::DomainsManager_DomainNotEmpty);
			}
			
			$bResult = $this->oStorage->DeleteDomains($aDomainsIds);
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}
		
		return $bResult;
	}
	
	/**
	 * @param int $iDomainId
	 * @return bool
	 */
	public function DeleteDomainById($iDomainId)
	{
		$bResult = false;
		try
		{
			$oDomain = $this->GetDomainById($iDomainId);
			if (!$oDomain)
			{
				throw new CApiManagerException(Errs::DomainsManager_DomainDoesNotExist);
			}

			if (!$this->AreDomainsEmpty(array($iDomainId)))
			{
				throw new CApiManagerException(Errs::DomainsManager_DomainNotEmpty);
			}
			
			$bResult = $this->oStorage->DeleteDomains(array($iDomainId));
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}
		
		return $bResult;
	}

	/**
	 * @param string $sDomainName
	 * @return bool
	 */
	public function DeleteDomainByName($sDomainName)
	{
		$bResult = false;
		$oDomain = $this->GetDomainByName($sDomainName);
		if ($oDomain)
		{
			$bResult = $this->DeleteDomainById($oDomain->IdDomain);
		}
		else
		{
			$this->setLastException(new CApiManagerException(Errs::DomainsManager_DomainDoesNotExist));
		}
		
		return $bResult;
	}

	/**
	 * @return array | false
	 */
	public function GetFullDomainsList()
	{
		return $this->GetDomainsList(1, 99999);
	}
	
	/**
	 * @return array | false
	 */
	public function GetFilterList()
	{
		return $this->GetFullDomainsList();
	}

	/**
	 * @param int $iPage
	 * @param int $iDomainsPerPage
	 * @param string $sOrderBy = 'name'
	 * @param bool $bOrderType = true
	 * @param string $sSearchDesc = ''
	 * @return array | false [IdDomain => [IsInternal, Name]]
	 */
	public function GetDomainsList($iPage, $iDomainsPerPage, $sOrderBy = 'name', $bOrderType = true, $sSearchDesc = '')
	{
		$aResult = false;
		try
		{
			$aResult = $this->oStorage->GetDomainsList($iPage, $iDomainsPerPage, $sOrderBy, $bOrderType, $sSearchDesc);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $aResult;
	}
	
	/**
	 * @param string $sDomainName
	 * @return bool
	 */
	public function DomainExists($sDomainName)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->DomainExists($sDomainName);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $bResult;
	}

	/**
	 * @param string $sSearchDesc = ''
	 * @return int | false
	 */
	public function GetDomainCount($sSearchDesc = '')
	{
		$iResult = false;
		try
		{
			$iResult = $this->oStorage->GetDomainCount($sSearchDesc);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $iResult;
	}
}
