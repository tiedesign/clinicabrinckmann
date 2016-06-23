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
class CApiMailsuiteDbStorage extends CApiMailsuiteStorage
{
	/**
	 * @var CDbStorage $oConnection
	 */
	protected $oConnection;
	
	/**
	 * @var CApiMailsuiteCommandCreator
	 */
	protected $oCommandCreator;
	
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('db', $oManager);
				
		$this->oConnection =& $oManager->GetConnection();
		$this->oCommandCreator =& $oManager->GetCommandCreator(
			$this, array(EDbType::MySQL => 'CApiMailsuiteCommandCreatorMySQL')
		);
	}

	/**
	 * @param int $iMailingListId
	 * @return CMailingList
	 */
	public function GetMailingListById($iMailingListId)
	{
		$oMailingList = null;
		if ($this->oConnection->Execute($this->oCommandCreator->GetMailingListById($iMailingListId)))
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow)
			{
				$oMailingList = new CMailingList();
				$oMailingList->InitByDbRow($oRow);
				$this->initMailingListMembers($oMailingList);
			}
		}

		$this->throwDbExceptionIfExist();
		return $oMailingList;
	}

	/**
	 * @param CMailingList &$oMailingList
	 * @return bool
	 */
	public function CreateMailingList(CMailingList &$oMailingList)
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->CreateMailingList($oMailingList)))
		{
			$oMailingList->IdMailingList = $this->oConnection->GetLastInsertId();
			$this->updateMailingListMembers($oMailingList);
			$bResult = true;
		}

		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @param CMailingList $oMailingList
	 * @return bool
	 */
	public function UpdateMailingList(CMailingList $oMailingList)
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->UpdateMailingList($oMailingList)))
		{
			$this->updateMailingListMembers($oMailingList);
			$bResult = true;
		}

		$this->throwDbExceptionIfExist();
		return $bResult;
	}
	
	/**
	 * @param CMailingList &$oMailingList
	 */
	protected function initMailingListMembers(CMailingList &$oMailingList)
	{
		if ($oMailingList && $this->oConnection->Execute(
			$this->oCommandCreator->GetMailingListMembersById($oMailingList->IdMailingList)))
		{
			$oRow = null;
			$aMailingList = array();
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$aMailingList[] = $oRow->list_to;
			}

			$oMailingList->Members = $aMailingList;
		}

		$this->throwDbExceptionIfExist();
	}
	
	/**
	 * @param CMailingList $oMailingList
	 */
	protected function updateMailingListMembers(CMailingList $oMailingList)
	{
		if ($oMailingList && 0 < count($oMailingList->Members))
		{
			$this->oConnection->Execute(
				$this->oCommandCreator->ClearMailingListMembers($oMailingList->IdMailingList));

			$this->oConnection->Execute(
				$this->oCommandCreator->AddMailingListMembers($oMailingList));
		}

		$this->throwDbExceptionIfExist();
	}
}
