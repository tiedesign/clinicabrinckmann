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
abstract class AApiChangePasswordPlugin extends AApiPlugin
{

	/**
	 * @param string $sVersion
	 * @param CApiPluginManager $oPluginManager
	 */
	public function __construct($sVersion, CApiPluginManager $oPluginManager)
	{
		parent::__construct($sVersion, $oPluginManager);

		$this->AddHook('api-change-account-by-id', 'PluginChangeAccountById');
		$this->AddHook('api-update-account', 'PluginUpdateAccount');
	}

	abstract public function ValidateIfAccountCanChangePassword($oAccount);
	
	abstract public function ChangePasswordProcess($oAccount);

	/**
	 * @param CAccount $oAccount
	 * @param bool $bUseOnlyHookUpdate
	 */
	public function PluginUpdateAccount(&$oAccount, &$bUseOnlyHookUpdate)
	{
		if ($this->ValidateIfAccountCanChangePassword($oAccount) && $oAccount->IsEnabledExtension(CAccount::ChangePasswordExtension))
		{
			$this->ChangePasswordProcess($oAccount);
		}
	}

	/**
	 * @param CAccount $oAccount
	 */
	public function PluginChangeAccountById(&$oAccount)
	{
		if ($this->ValidateIfAccountCanChangePassword($oAccount))
		{
			$oAccount->EnableExtension(CAccount::ChangePasswordExtension);
		}
	}
}