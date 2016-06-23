<?php

/**
 * AfterLogic Api by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in LICENSE.txt
 *
 * @package Contacts
 */

/**
 * @package Contacts
 */
class CApiContactsLdapHelper
{
	const CONTACT_OBJECT_CLASS = 'pabperson';
	const GROUP_OBJECT_CLASS = 'pabgroup';
	
	/**
	 * @param CContact $oContact
	 * @return string
	 */
	static public function CreateNewContactUn($oContact)
	{
		return 'contact_'.md5($oContact->FullName.$oContact->ViewEmail.time());
	}

	/**
	 * @param CGroup $oGroup
	 * @return string
	 */
	static public function CreateNewGroupUn($oGroup)
	{
		return 'group_'.md5($oGroup->Name.time());
	}

	/**
	 * @param int $iUserId
	 * @return string
	 */
	static public function GetPabUrlFromId($iUserId)
	{
		return 'ldap://192.168.0.230:1389/ou=000000061506b6c76c834fa84,o=pab';
		return 'ldap://192.168.0.230:1389/ou=010105061506b6c76c834fa84,o=pab'; // TODO hc
		return CSession::Get('UserPabUrl', '');
	}

	/**
	 * @param CContact $oContact
	 */
	static public function ContactObjReparse(&$oContact)
	{
		$oContact->ViewEmail = '';
		$oContact->PrimaryEmail = EPrimaryEmailType::Home;

		if (CApi::GetConf('contacts.default-primary-email', EPrimaryEmailType::Home) === EPrimaryEmailType::Home)
		{
			if (0 < strlen($oContact->HomeEmail))
			{
				$oContact->ViewEmail = $oContact->HomeEmail;
				$oContact->PrimaryEmail = EPrimaryEmailType::Home;
			}
			else if (0 < strlen($oContact->BusinessEmail))
			{
				$oContact->ViewEmail = $oContact->BusinessEmail;
				$oContact->PrimaryEmail = EPrimaryEmailType::Business;
			}
		}
		else
		{
			if (0 < strlen($oContact->BusinessEmail))
			{
				$oContact->ViewEmail = $oContact->BusinessEmail;
				$oContact->PrimaryEmail = EPrimaryEmailType::Business;
			}
			else if (0 < strlen($oContact->HomeEmail))
			{
				$oContact->ViewEmail = $oContact->HomeEmail;
				$oContact->PrimaryEmail = EPrimaryEmailType::Home;
			}
		}

		if (0 === strlen($oContact->ViewEmail) && 0 < strlen($oContact->OtherEmail))
		{
			$oContact->ViewEmail = $oContact->OtherEmail;
			$oContact->PrimaryEmail = EPrimaryEmailType::Other;
		}
	}
	
	/**
	 * @param CContact $oGroup
	 */
	static public function GroupObjReparse(&$oGroup)
	{
	}

	/**
	 * @return array
	 */
	static public function GetLdapObjectMap()
	{
		return array(
			'sn' => 'SurName',
			'cn' => 'FullName',

			'mail' => 'HomeEmail',
			'homeemail' => 'HomeEmail',

			'street' => 'HomeStreet',
			'l' => 'HomeCity',
			'st' => 'HomeState',
			'postalcode' => 'HomeZip',
			'co' => 'HomeCountry',
			'facsimileTelephoneNumber' => 'HomeFax',
			'mobile' => 'HomeMobile',
			'homePhone' => 'HomePhone',
			'labeledUri' => 'HomeWeb',

			'description' => 'Notes',
		);
	}

	/**
	 * @return array
	 */
	static public function GetLdapContactObjectEntry()
	{
		return array(
			'un' => '',
//			'sn' => '', // TODO
			'cn' => '',
			'objectClass' => array(
				'top',
				'person',
				'pabperson',
				'organizationalPerson',
				'inetOrgPerson'
			)
		);
	}

	/**
	 * @return array
	 */
	static public function GetLdapGroupObjectEntry()
	{
		return array(
			'un' => '',
			'cn' => '',
			'objectClass' => array('top', 'pabGroup')
		);
	}
	
	/**
	 * @return CContact | bool
	 */
	static public function LdapContactPopulate($aGetEntriesResult, $iUserId)
	{
		$oContact = false;
		if ($aGetEntriesResult)
		{
			$aContact = null;
			if (isset($aGetEntriesResult[0]) && isset($aGetEntriesResult[0]['un'][0]))
			{
				$aContact = $aGetEntriesResult[0];

				$oContact = new CContact();
				$oContact->IdUser = $iUserId;
				$oContact->UseFriendlyName = true;
				$oContact->IdContact = $aContact['un'][0];
				$oContact->IdContactStr = $oContact->IdContact;

				$aMap = self::GetLdapObjectMap();
				$aMap = array_change_key_case($aMap, CASE_LOWER);
				foreach ($aContact as $sKey => $aRow)
				{
					if (isset($aMap[strtolower($sKey)]) && 0 === strlen($oContact->{$aMap[strtolower($sKey)]}))
					{
						$oContact->{$aMap[strtolower($sKey)]} = isset($aRow[0]) ? $aRow[0] : '';
					}
				}

				$sDateOfBirth = isset($aContact['dateofbirth'][0]) ? $aContact['dateofbirth'][0] : '';
				if (strlen($sDateOfBirth) > 0)
				{
					$aDateOfBirth = explode('/', $sDateOfBirth, 3);
					if (3 === count($aDateOfBirth))
					{
						$oContact->BirthdayDay = (int) $aDateOfBirth[0];
						$oContact->BirthdayMonth = (int) $aDateOfBirth[1];
						$oContact->BirthdayYear = (int) $aDateOfBirth[2];
					}
				}

				if (isset($aContact['memberofpabgroup']))
				{
					unset($aContact['memberofpabgroup']['count']);
					
					if (is_array($aContact['memberofpabgroup']))
					{
						$aNemberOfPabGroup = array_values($aContact['memberofpabgroup']);
						$oContact->GroupsIds = is_array($aNemberOfPabGroup)	? $aNemberOfPabGroup : array();
					}
				}

				self::ContactObjReparse($oContact);
			}
		}

		return $oContact;
	}

	/**
	 * @return CGroup | bool
	 */
	static public function LdapGroupPopulate($aGetEntriesResult, $iUserId)
	{
		$oGroup = false;
		if ($aGetEntriesResult)
		{
			$aContact = null;
			if (isset($aGetEntriesResult[0]) && isset($aGetEntriesResult[0]['un'][0]))
			{
				$aContact = $aGetEntriesResult[0];

				$oGroup = new CGroup();
				$oGroup->IdUser = $iUserId;
				$oGroup->IdGroup = $aContact['un'][0];
				$oGroup->IdGroupStr = $oGroup->IdGroup;
				$oGroup->Name =  isset($aContact['cn'][0]) ? $aContact['cn'][0]: '';
				$oGroup->IsOrganization = false;
				
				self::GroupObjReparse($oGroup);
			}
		}

		return $oGroup;
	}

	/**
	* @param CContact $oContact
	* @param bool $bIsUpdate = false
	* @return array
	*/
	static public function GetEntryFromContact($oContact, $bIsUpdate = false)
	{
		self::ContactObjReparse($oContact);
		
		$aEntry = self::GetLdapContactObjectEntry();
		$aEntry['un'] = $oContact->IdContact;

		$aMap = self::GetLdapObjectMap();
		$aMapCache = array();
		foreach ($aMap as $sEntryKey => $sObjectKey)
		{
			if (strlen($oContact->{$sObjectKey}) > 0)
			{
				if (!isset($aMapCache[$sObjectKey]))
				{
					$aEntry[$sEntryKey] = $oContact->{$sObjectKey};
					$aMapCache[$sObjectKey] = true;
				}
			}
		}
		
		// TODO hc
//		if (empty($aEntry['sn']))
//		{
//			$aEntry['sn'] = $aEntry['cn'];
//		}

		if (0 < $oContact->BirthdayDay && 0 < $oContact->BirthdayMonth && 0 < $oContact->BirthdayYear)
		{
			$aEntry['dateOfBirth'] = $oContact->BirthdayDay.'/'.$oContact->BirthdayMonth.'/'.$oContact->BirthdayYear;
		}

		$aEntry['memberofpabgroup'] = array();
		$aGroupsIds = $oContact->GroupsIds;
		if (is_array($aGroupsIds) && 0 < count($aGroupsIds))
		{
			foreach($aGroupsIds as $mGroupId)
			{
				$aEntry['memberofpabgroup'][] = $mGroupId;
			}
		}

		if ($bIsUpdate)
		{
			unset($aEntry['objectClass']);
		}
		else
		{
			if (0 === count($aEntry['memberofpabgroup']))
			{
				unset($aEntry['memberofpabgroup']);
			}
		}

		return $aEntry;
	}

	/**
	* @param CGroup $oGroup
	* @param bool $bIsUpdate = false
	* @return array
	*/
	static public function GetEntryFromGroup($oGroup, $bIsUpdate = false)
	{
		self::GroupObjReparse($oGroup);

		$aEntry = self::GetLdapGroupObjectEntry();
		$aEntry['un'] = $oGroup->IdGroup;
		$aEntry['cn'] = $oGroup->Name;

		if ($bIsUpdate)
		{
			unset($aEntry['objectClass']);
		}
		
		return $aEntry;
	}
}
