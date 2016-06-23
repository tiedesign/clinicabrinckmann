<?php

class CApiContactsCsvParser
{
	protected $aContainer;

	protected $aMap;

	public function __construct()
	{
		$this->aMap = array();

		$this->aMap = array(
			'tokens' => array(
				'Title' => 'Title',
				'First Name' => 'FirstName',
				'Middle Name' => '',
				'Last Name' => 'SurName',
				'EmailDisplayName' => 'FullName',
				'Company' => 'BusinessCompany',
				'Department' => 'BusinessDepartment',
				'Job Title' => 'BusinessJobTitle',
				'Business Street' => 'BusinessStreet',
				'Business City' => 'BusinessCity',
				'Business State' => 'BusinessState',
				'Business Postal Code' => 'BusinessZip',
				'Business Country' => 'BusinessCountry',
				'Home Street' => 'HomeStreet',
				'Home City' => 'HomeCity',
				'Home State' => 'HomeState',
				'Home Postal Code' => 'HomeZip',
				'Home Country' => 'HomeCountry',
				'Business Fax' => 'BusinessFax',
				'Business Phone' => 'BusinessPhone',
				'Home Fax' => 'HomeFax',
				'Home Phone' => 'HomePhone',
				'Mobile Phone' => 'HomeMobile',
				'E-mail Address' => 'HomeEmail',
				'Email Address' => 'HomeEmail',
				'EmailAddress' => 'HomeEmail',
				'Notes' => 'Notes',
				'Office Location' => 'BusinessOffice',
				'Web Page' => 'HomeWeb'
			),
					
			'tokensWithSpecialTreatmentImport' => array(
				'Birthday' => 'bdayImportForm'
			)
		);
	}

	public function Reset()
	{
		$this->aContainer = array();
	}

	/**
	 * @param array $aContainer
	 */
	public function SetContainer($aContainer)
	{
		$this->aContainer = $aContainer;
	}

	public function GetParameters()
	{
		$aResult = array();
		$aLowerTokensMap = array_change_key_case($this->aMap['tokens'], CASE_LOWER);
		$aLowerSpecialMap = array_change_key_case($this->aMap['tokensWithSpecialTreatmentImport'], CASE_LOWER);

		if ($this->aContainer && 0 < count($this->aContainer))
		{
			foreach ($this->aContainer as $sHeaderName => $sValue)
			{
				if (!empty($sValue) && isset($aLowerTokensMap[strtolower($sHeaderName)]))
				{
					$aResult[$aLowerTokensMap[strtolower($sHeaderName)]] = $sValue;
				}
				else if (!empty($sValue) && isset($aLowerSpecialMap[strtolower($sHeaderName)]))
				{
					$sFunctionName = $aLowerSpecialMap[strtolower($sHeaderName)];
					$aResult[$aLowerSpecialMap[strtolower($sHeaderName)]] =
						(string) call_user_func_array(
							array(&$this, $sFunctionName), array($sHeaderName, $sValue)
						);
				}

			}
		}
		
		return $aResult;
	}

	protected function bdayImportForm($sToken, $sTokenValue)
	{
		$aReturn = $aExplodeArray = array();
		if (false !== strpos($sTokenValue, '-'))
		{
			$aExplodeArray = explode('-', $sTokenValue, 3);
		}
		else if (false !== strpos($sTokenValue, '.'))
		{
			$aExplodeArray = explode('.', $sTokenValue, 3);
		}

		if (3 === count($aExplodeArray))
		{
			$iYear = (int) $aExplodeArray[0];
			$iMonth = (int) $aExplodeArray[1];
			$iDay = (int) $aExplodeArray[2];

			if (checkdate($iMonth, $iDay, $iYear))
			{
				$aReturn['BirthdayDay'] = $iDay;
				$aReturn['BirthdayMonth'] = $iMonth;
				$aReturn['BirthdayYear'] = $iYear;
			}
		}
		
		return $aReturn;
	}
}