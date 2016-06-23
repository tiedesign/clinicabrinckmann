<?php

	defined('WM_ROOTPATH') || define('WM_ROOTPATH', (dirname(__FILE__).'/../'));

	/**
	 * @staticvar bool $bOnceRun
	 * @param string $sLanguageName
	 */
	function AppIncludeLanguage($sLanguageName)
	{
		static $bOnceRun = false;
		if (!$bOnceRun)
		{
			$bOnceRun = true;

			if (!@file_exists(WM_ROOTPATH.'lang/'.$sLanguageName.'.php'))
			{
				$sLanguageName = 'English';
			}

			include_once WM_ROOTPATH.'lang/'.$sLanguageName.'.php';
		}
	}

	/**
	 * @return bool
	 */
	function IsAdminLogin()
	{
		return isset($_SESSION[EAccountSessKey::AdminLogin]) && true === $_SESSION[EAccountSessKey::AdminLogin];
	}

	

	/**
	 * @param CAccount $oAccount
	 * @return CDomain
	 */
	function AppGetDomain($oAccount = null)
	{
		if ($oAccount)
		{
			return $oAccount->Domain;
		}

		/* @var $oApiDomainsManager CApiDomainsManager */
		$oApiDomainsManager = CApi::Manager('domains');
		
		$oInput = new CAppBaseHttp();
		
		return $oApiDomainsManager->GetDomainByUrl($oInput->GetHost());
	}

	/**
	 * @param int $iAccountId
	 * @return CAccount
	 */
	function AppGetAccount($iAccountId)
	{
		/* @var $oApiUsersManager CApiUsersManager */
		$oApiUsersManager = CApi::Manager('users');

		return $oApiUsersManager->GetAccountById($iAccountId);
	}

	/**
	 * @param CAccount $oAccount
	 * @param int $iSessionUserId = null
	 * @return array
	 */
	function AppGetAccounts($oAccount, $iSessionUserId = null)
	{
		$iUserId = (null !== $oAccount)
			? $oAccount->IdUser : $iSessionUserId;

		$aAccounts = array();

		if (null !== $iUserId)
		{
			/* @var $oApiUsersManager CApiUsersManager */
			$oApiUsersManager = CApi::Manager('users');
			$aAccountsIds = $oApiUsersManager->GetUserIdList($iUserId);

			if (is_array($aAccountsIds))
			{
				foreach ($aAccountsIds as $iAccountId)
				{
					if (null === $oAccount)
					{
						$aAccounts[$iAccountId] = AppGetAccount($iAccountId);
					}
					else
					{
						$aAccounts[$iAccountId] = ($iAccountId === $oAccount->IdAccount)
							? $oAccount : AppGetAccount($iAccountId);
					}

					if (!is_object($aAccounts[$iAccountId]))
					{
						unset($aAccounts[$iAccountId]);
					}
				}
			}
		}

		return $aAccounts;
	}
