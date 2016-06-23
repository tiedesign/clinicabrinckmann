<?php

/**
 * AfterLogic Api by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in LICENSE.txt
 *
 * @package SubAdmins
 * @subpackage Classes
 */

/**
 * @property int $IdSubAdmin
 * @property string $Login
 * @property string $Password
 * @property string $Description
 * @property array $DomainIds
 *
 * @package SubAdmins
 * @subpackage Classes
 */
class CSubAdmin extends api_AContainer
{
	public function __construct()
	{
		parent::__construct(get_class($this), 'IdSubAdmin');

		$this->SetDefaults(array(
			'IdSubAdmin'	=> 0,
			'Login'			=> '',
			'Password'		=> '',
			'Description'	=> '',
			'DomainIds'		=> array()
		));
	}

	/**
	 * @return bool
	 */
	public function Validate()
	{
		switch (true)
		{
			case api_Validate::IsEmpty($this->Login):
				throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CSubAdmin', '{{ClassField}}' => 'Login'));
				
			case api_Validate::IsEmpty($this->Password):
				throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CSubAdmin', '{{ClassField}}' => 'Password'));
				
			case (!is_array($this->DomainIds) || count($this->DomainIds) < 1):
				throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'CSubAdmin', '{{ClassField}}' => 'DomainIds'));
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
			'IdSubAdmin'	=> array('int', 'id_admin', false),
			'Login'			=> array('string', 'login'),
			'Password'		=> array('string', 'password'),
			'Description'	=> array('string', 'description'),
			'DomainIds'		=> array('array')
		);
	}
}
