<?php

/**
 * AfterLogic Api by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in LICENSE.txt
 *
 * @package Mailsuite
 */

/**
 * @package Mailsuite
 */
class CApiMailsuiteManager extends AApiManagerWithStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('mailsuite', $oManager);
		
		$this->inc('classes.mailing-list');
	}

	/**
	 * @param int $iMailingListId
	 * @return CMailingList
	 */
	public function GetMailingListById($iMailingListId)
	{
		$oMailingList = null;
		try
		{
			$oMailingList = $this->oStorage->GetMailingListById($iMailingListId);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $oMailingList;
	}

	/**
	 * @param CMailingList &$oMailingList
	 * @return bool
	 */
	public function CreateMailingList(CMailingList &$oMailingList)
	{
		$bResult = false;
		try
		{
			if ($oMailingList->Validate())
			{
				if (!$this->MailingListExists($oMailingList))
				{
					if (!$this->oStorage->CreateMailingList($oMailingList))
					{
						throw new CApiManagerException(Errs::MailSuiteManager_MailingListCreateFailed);
					}
				}
				else
				{
					throw new CApiManagerException(Errs::MailSuiteManager_MailingListAlreadyExists);
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
	 * @param CMailingList &$oMailingList
	 * @return bool
	 */
	public function UpdateMailingList(CMailingList &$oMailingList)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->UpdateMailingList($oMailingList);
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}
		return $bResult;
	}

	/**
	 * @param CMailingList $oMailingList
	 * @return bool
	 */
	public function MailingListExists(CMailingList $oMailingList)
	{
		/* @var $oApiUsersManager CApiUsersManager */
		$oApiUsersManager = CApi::Manager('users');
		return $oApiUsersManager->AccountExists($oMailingList->GenerateAccount());
	}
}
