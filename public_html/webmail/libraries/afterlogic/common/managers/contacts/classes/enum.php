<?php

/**
 * AfterLogic Api by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in LICENSE.txt
 *
 * @package Users
 * @subpackage Enum
 */

/**
 * @package Users
 * @subpackage Enum
 */
class EContactSortField extends AEnumeration
{
	const Name = 1;
	const EMail = 2;
	const Frequency = 3;

	/**
	 * @param int $iValue
	 * @return string
	 */
	static public function GetContactDbField($iValue)
	{
		$sResult = 'view_email';
		switch ($iValue)
		{
			case self::Name:
				$sResult = 'fullname';
				break;
			case self::EMail:
				$sResult = 'view_email';
				break;
			case self::Frequency:
				$sResult = 'use_frequency';
				break;
		}
		return $sResult;
	}
	
	/**
	 * @param int $iValue
	 * @return string
	 */
	static public function GetGroupDbField($iValue)
	{
		$sResult = 'group_nm';
		switch ($iValue)
		{
			case self::Name:
				$sResult = 'group_nm';
				break;
			case self::Frequency:
				$sResult = 'use_frequency';
				break;
		}
		return $sResult;
	}
}
