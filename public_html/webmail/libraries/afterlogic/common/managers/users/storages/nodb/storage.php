<?php

/**
 * AfterLogic Api by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in LICENSE.txt
 *
 * @package Users
 */

/**
 * @package Users
 */
class CApiUsersNodbStorage extends CApiUsersStorage
{
	const SESS_ACCOUNT_STORAGE = 'sess-acct-storage';

	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
	}

	/**
	 * @param int $iAccountId
	 * @return CAccount
	 */
	public function GetAccountById($iAccountId)
	{
		$oAccount = CSession::Get(CApiUsersNodbStorage::SESS_ACCOUNT_STORAGE, null);
		return ($oAccount && $iAccountId === $oAccount->IdAccount) ? clone $oAccount : null;
	}

	/**
	 * @param CAccount &$oAccount
	 * @return bool
	 */
	public function CreateAccount(CAccount &$oAccount)
	{
		$oAccount->IdAccount = 1;
		$oAccount->IdUser = 1;
		$oAccount->User->IdUser = 1;
		
		CSession::Set(CApiUsersNodbStorage::SESS_ACCOUNT_STORAGE, $oAccount);
		
		return true;
	}

	/**
	 * @param int $iUserId
	 * @return array | false
	 */
	public function GetUserIdList($iUserId)
	{
		$oAccount = CSession::Get(CApiUsersNodbStorage::SESS_ACCOUNT_STORAGE, null);
		return ($oAccount && $iUserId === $oAccount->IdUser) ? array($iUserId) : false;
	}

	/**
	 * @param CAccount $oAccount
	 * @return bool
	 */
	public function AccountExists(CAccount $oAccount)
	{
		return false;
	}

	/**
	 * @param CAccount $oAccount
	 * @return bool
	 */
	public function UpdateAccount(CAccount $oAccount)
	{
		CSession::Set(CApiUsersNodbStorage::SESS_ACCOUNT_STORAGE, $oAccount);
		return true;
	}
}
