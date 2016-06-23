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
class CApiMailsuiteCommandCreator extends api_CommandCreator
{
	/**
	 * @param CMailingList $oMailingList
	 * @return string
	 */
	public function CreateMailingList(CMailingList $oMailingList)
	{
		$aResults = api_AContainer::DbInsertArrays($oMailingList, $this->oHelper);
		
		if ($aResults[0] && $aResults[1])
		{
			$sSql = 'INSERT INTO %sawm_accounts ( %s ) VALUES ( %s )';
			return sprintf($sSql, $this->Prefix(),
				implode(', ', $aResults[0]), 
				implode(', ', $aResults[1])
			);
		}
		
		return '';
	}

	/**
	 * @param int $iMailingListId
	 * @return string
	 */
	public function GetMailingListById($iMailingListId)
	{
		$aMap = api_AContainer::DbReadKeys(CAccount::GetStaticMap());
		$aMap = array_map(array($this, 'escapeColumn'), $aMap);

		$sSql = 'SELECT %s FROM %sawm_accounts WHERE %s = %d';

		return sprintf($sSql, implode(', ', $aMap), $this->Prefix(),
			$this->escapeColumn('id_acct'), $iMailingListId);
	}
	
	/**
	 * @param int $iMailingListId
	 * @return string
	 */
	public function GetMailingListMembersById($iMailingListId)
	{
		$sSql = 'SELECT %s FROM %sawm_mailinglists WHERE %s = %d';
		
		return sprintf($sSql, $this->escapeColumn('list_to'), $this->Prefix(),
			$this->escapeColumn('id_acct'), $iMailingListId);
	}

	/**
	 * @param CMailingList $oMailingList
	 * @return string
	 */
	public function AddMailingListMembers(CMailingList $oMailingList)
	{
		$aListSql = array();
		foreach ($oMailingList->Members as $sEmail)
		{
			$aListSql[] = '('.$oMailingList->IdMailingList.', '.$this->escapeString($oMailingList->Email).', '.$this->escapeString($sEmail).')';
		}

		if (0 < count($aListSql))
		{
			$sSql = 'INSERT INTO %sawm_mailinglists (id_acct, list_name, list_to) VALUES ';
			return sprintf($sSql, $this->Prefix()).implode(', ', $aListSql);
		}
		
		return '';
	}

	/**
	 * @param int $iMailingListId
	 * @return string
	 */
	public function ClearMailingListMembers($iMailingListId)
	{
		$sSql = 'DELETE FROM %sawm_mailinglists WHERE %s = %d';

		return sprintf($sSql, $this->Prefix(), $this->escapeColumn('id_acct'), $iMailingListId);
	}
}

/**
 * @package Mailsuite
 */
class CApiMailsuiteCommandCreatorMySQL extends CApiMailsuiteCommandCreator
{
}
