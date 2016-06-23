<?php

/**
 * AfterLogic Api by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in LICENSE.txt
 *
 * @package Contacts
 * @subpackage Classes
 */

/**
 * @property mixed $IdGroup
 * @property string $IdGroupStr
 * @property int $IdUser
 * @property string $Name
 * @property array $ContactsIds
 * @property bool $IsOrganization
 * @property string $Email
 * @property string $Company
 * @property string $Street
 * @property string $City
 * @property string $State
 * @property string $Zip
 * @property string $Country
 * @property string $Phone
 * @property string $Fax
 * @property string $Web
 *
 * @package Group
 * @subpackage Classes
 */
class CGroup extends api_AContainer
{
	const STR_PREFIX = '5765624D61696C50726F';
	
	public function __construct()
	{
		parent::__construct(get_class($this), 'IdGroup');

		$this->SetDefaults(array(
			'IdGroup'		=> '',
			'IdGroupStr'	=> '',
			'IdUser'		=> 0,

			'Name'			=> '',
			'ContactsIds'	=> array(),

			'IsOrganization'	=> false,

			'Email'		=> '',
			'Company'	=> '',
			'Street'	=> '',
			'City'		=> '',
			'State'		=> '',
			'Zip'		=> '',
			'Country'	=> '',
			'Phone'		=> '',
			'Fax'		=> '',
			'Web'		=> ''
		));
		
		CApi::Plugin()->RunHook('api-group-construct', array(&$this));
	}

	/**
	 * @return string
	 */
	public function GenerateStrId()
	{
		return self::STR_PREFIX.$this->IdGroup;
	}

	/**
	 * @return bool
	 */
	public function InitBeforeChange()
	{
		parent::InitBeforeChange();

		if (0 === strlen($this->IdGroupStr))
		{
			$this->IdGroupStr = $this->GenerateStrId();
		}
		
		return true;
	}

	/**
	 * @return bool
	 */
	public function Validate()
	{
		switch (true)
		{
			case api_Validate::IsEmpty($this->Name):
				throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CGroup', '{{ClassField}}' => 'Name'));
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function GetMap()
	{
		return self::GetStaticMap();
	}

	/**
	 * @return array
	 */
	static public function GetStaticMap()
	{
		return array(
			'IdGroup'		=> array('string', 'id_group', false, false),
			'IdGroupStr'	=> array('string', 'group_str_id', false),
			'IdUser'		=> array('int', 'id_user'),
			
			'Name'			=> array('string', 'group_nm'),
			'ContactsIds'	=> array('array'),

			'IsOrganization'	=> array('bool', 'organization'),

			'Email'		=> array('string', 'email'),
			'Company'	=> array('string', 'company'),
			'Street'	=> array('string', 'street'),
			'City'		=> array('string', 'city'),
			'State'		=> array('string', 'state'),
			'Zip'		=> array('string', 'zip'),
			'Country'	=> array('string', 'country'),
			'Phone'		=> array('string', 'phone'),
			'Fax'		=> array('string', 'fax'),
			'Web'		=> array('string', 'web')
		);
	}
}
