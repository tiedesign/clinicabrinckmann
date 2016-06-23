<?php

$iSortIndex = 10;
$sCurrentModule = 'CCommonModule';
class CCommonModule extends ap_Module
{
	/**
	* @var CApiWebmailManager
	*/
	protected $oWebmailApi;
	
	/**
	 * @var CApiDomainsManager
	 */
	protected $oDomainsApi;
	
	/**
	 * @var CApiUsersManager
	 */
	protected $oUsersApi;
	
	/**
	 * @var CApiCapabilityManager
	 */
	protected $oCapabilityApi;
	
	/**
	 * @param CAdminPanel $oAdminPanel
	 * @param string $sPath
	 * @return CCommonModule
	 */
	public function __construct(CAdminPanel &$oAdminPanel, $sPath)
	{
		parent::__construct($oAdminPanel, $sPath);

		$this->oDomainsApi = CApi::Manager('domains');
		$this->oUsersApi = CApi::Manager('users');
		$this->oCapabilityApi = CApi::Manager('capability');
		$this->oWebmailApi = CApi::Manager('webmail');
		
		$this->aTabs[] = AP_TAB_DOMAINS;
		$this->aTabs[] = AP_TAB_SYSTEM;

		//$this->aQueryActions[] = 'new';
		$this->aQueryActions[] = 'edit';
		$this->aQueryActions[] = 'list';

		$this->oPopulateData = new CCommonPopulateData($this);
		$this->oStandardPostAction = new CCommonPostAction($this);
		$this->oStandardPopAction = new CCommonPopAction($this);
		$this->oTableAjaxAction = new CCommonAjaxAction($this);

		$aTabs =& $oAdminPanel->GetTabs();

		//$this->_bHasWebmail = $oAdminPanel->IsModuleInit('CWebMailModule');
		
		array_unshift($aTabs,
			array('Services', AP_TAB_SERVICES),
			array('Domains', AP_TAB_DOMAINS)
		);
	}
	
	/**
	 * @param int $iDomainId
	 * @return CDomain
	 */
	public function GetDomain($iDomainId)
	{
		if (0 === $iDomainId)
		{
			return $this->oDomainsApi->GetDefaultDomain();
		}
		return $this->oDomainsApi->GetDomainById($iDomainId);
	}
	
	/**
	 * @param CDomain $oDomain
	 * @return bool
	 */
	public function UpdateDomain(CDomain $oDomain)
	{
		if (!$this->oDomainsApi->UpdateDomain($oDomain))
		{
			$this->lastErrorCode = $this->oDomainsApi->GetLastErrorCode();
			$this->lastErrorMessage = $this->oDomainsApi->GetLastErrorMessage();
			return false;
		}
		
		if (isset($_SESSION[AP_SESS_DOMAIN_NEXT_EDIT_ID]) && $oDomain->IdDomain === $_SESSION[AP_SESS_DOMAIN_NEXT_EDIT_ID])
		{
			unset($_SESSION[AP_SESS_DOMAIN_NEXT_EDIT_ID]);
		}
		return true;
	}

	/**
	 * @param string $sDomainName
	 * @return bool
	 */
	public function DomainExists($sDomainName)
	{
		return $this->oDomainsApi->DomainExists($sDomainName);
	}
	
	/**
	 * @return bool
	 */
	public function HasSslSupport()
	{
		return $this->oCapabilityApi->HasSslSupport();
	}
	
	/**
	 * @param string $sTab
	 * @param ap_Screen $oScreen
	 */
	protected function initStandardMenuByTab($sTab, ap_Screen &$oScreen)
	{
		switch ($sTab)
		{
			case AP_TAB_SYSTEM:
				$oScreen->AddMenuItem(CM_MODE_DB, CM_MODE_DB_NAME, $this->sPath.'/templates/db.php');
				$oScreen->AddMenuItem(CM_MODE_SECURITY, CM_MODE_SECURITY_NAME, $this->sPath.'/templates/security.php');
				$oScreen->SetDefaultMode(CM_MODE_DB);
				break;
		}
	}

	/**
	 * @param string $sTab
	 * @param ap_Screen $oScreen
	 */
	protected function initTableTopMenu($sTab, ap_Screen &$oScreen)
	{

	}

	/**
	 * @param string $sTab
	 * @param ap_Screen $oScreen
	 */
	protected function initTableListHeaders($sTab, ap_Screen &$oScreen)
	{
		$oScreen->SetEmptySearch(AP_LANG_RESULTEMPTY);
		switch ($sTab)
		{
			case AP_TAB_DOMAINS:
				$oScreen->ClearHeaders();
				$oScreen->AddHeader('Name', 138, true);
				$oScreen->SetEmptyList(CM_LANG_NODOMAINS);
				$oScreen->SetEmptySearch(CM_LANG_NODOMAINS_RESULTEMPTY);
				break;
		}
	}

	/**
	 * @param string $sTab
	 * @param ap_Screen $oScreen
	 */
	protected function initTableList($sTab, ap_Screen &$oScreen)
	{
		if (AP_TAB_DOMAINS === $sTab)
		{
			$searchDesc = $oScreen->GetSearchDesc() ;
			$iAllCount = $this->oDomainsApi->GetDomainCount($searchDesc);
			$oScreen->EnableSearch( ($iAllCount > 1) || $searchDesc ) ;
			
			$bAddDefaultDomain = false;
			if ($this->oAdminPanel->HasAccessDomain(0))
			{
				$iAllCount++;
				$bAddDefaultDomain = true;
				$oScreen->AddListItem(0, array(
					'Name' => 'Default domain settings'
				), true);
			}
			
			$oScreen->SetAllListCount($iAllCount);
			
			$aDomainsList = $this->oDomainsApi->GetDomainsList($oScreen->GetPage(),
				$bAddDefaultDomain ? $oScreen->GetLinesPerPage() - 1 : $oScreen->GetLinesPerPage(),
				$oScreen->GetOrderBy(), $oScreen->GetOrderType(), $searchDesc );
				
			if (is_array($aDomainsList) && 0 < count($aDomainsList))
			{
				foreach ($aDomainsList as $iDomainId => $aDomainArray)
				{
					if ($this->oAdminPanel->HasAccessDomain($iDomainId))
					{
						$oScreen->AddListItem($iDomainId, array(
							'Type' => ($aDomainArray[0])
								? '<img src="static/images/mailsuite-domain-icon-big.png">'
								: '<img src="static/images/wm-domain-icon-big.png">',
							'Name' => $aDomainArray[1]
						));
					}
				}
			}
		}

	}

	/**
	 * @param string $sTab
	 * @param ap_Screen $oScreen
	 */
	protected function initTableMainSwitchers($sTab, ap_Screen &$oScreen)
	{
		$sMainAction = $this->getQueryAction();
		if (AP_TAB_DOMAINS === $sTab)
		{
			switch ($sMainAction)
			{
				case 'edit':
					$iDomainId = isset($_GET['uid']) ? (int) $_GET['uid'] : null;

					$oDomain = null;
					if ($this->oAdminPanel->HasAccessDomain($iDomainId))
					{
						$oDomain =& $this->oAdminPanel->GetMainObject('domain_edit');
						if (!$oDomain && null !== $iDomainId)
						{
							$oDomain = $this->GetDomain($iDomainId);
							if ($oDomain)
							{
								$this->oAdminPanel->SetMainObject('domain_edit', $oDomain);
							}
						}
					}

					if ($oDomain)
					{
						$oScreen->Data->SetValue('strDomainName', $oDomain->Name);
						if (0 === $oDomain->IdDomain)
						{
							$oScreen->Data->SetValue('strDomainName', 'Default domain settings');
						}
						
						$oScreen->Main->AddTopSwitcher($this->sPath.'/templates/main-top-edit-domain-name.php');
						$oScreen->Main->AddTopSwitcher($this->sPath.'/templates/main-top-edit-domain.php');
					}
					break;
			}
		}

	}

	/**
	* @param string $sTab
	* @param ap_Screen $oScreen
	*/
	protected function initTableMainSwitchersPost($sTab, ap_Screen &$oScreen)
	{
		$sMainAction = $this->getQueryAction();
		if (AP_TAB_DOMAINS === $sTab)
		{
			switch ($sMainAction)
			{
				case 'edit':
					$oDomain =& $this->oAdminPanel->GetMainObject('domain_edit');
					if ($oDomain)
					{
						$oScreen->Main->AddSwitcher(
						WM_SWITCHER_MODE_EDIT_DOMAIN_GENERAL, WM_SWITCHER_MODE_EDIT_DOMAIN_GENERAL_NAME,
						$this->sPath.'/templates/main-edit-domain-general-webmail.php');
						$oScreen->Main->AddSwitcher(
						WM_SWITCHER_MODE_EDIT_DOMAIN_GENERAL, WM_SWITCHER_MODE_EDIT_DOMAIN_GENERAL_NAME,
						$this->sPath.'/templates/main-edit-domain-general-regional.php');
					}
					break;
			}
		}
	}
	
	/**
	 * @return void
	 */
	protected function initInclude()
	{
		include $this->sPath.'/inc/constants.php';
		include $this->sPath.'/inc/populate.php';
		include $this->sPath.'/inc/post.php';
		include $this->sPath.'/inc/pop.php';
		include $this->sPath.'/inc/ajax.php';
	}

	/**
	 * @return void
	 */
	public function AuthCheckSet()
	{
		if (isset($_SESSION[AP_SESS_AUTH]) && $_SESSION[AP_SESS_AUTH] == @session_id() && isset($_SESSION[AP_SESS_AUTH_TYPE]))
		{
			$this->setAdminAccessType((int) $_SESSION[AP_SESS_AUTH_TYPE]);
			$this->setAdminAccessDomains(isset($_SESSION[AP_SESS_AUTH_DOMAINS])
				? $_SESSION[AP_SESS_AUTH_DOMAINS] : null);
		}
	}

	/**
	 * @param string $sLogin
	 * @param string $sPassword
	 * @return bool
	 */
	public function AuthLogin($sLogin, $sPassword)
	{
		$oSettings = null;
		$oSettings =& CApi::GetSettings();
		$sDemoLogin = CApi::GetConf('demo.adminpanel.login', '');

		if ($oSettings->GetConf('Common/AdminLogin') === $sLogin && 
				($sPassword === $oSettings->GetConf('Common/AdminPassword') ||
				md5($sPassword) === $oSettings->GetConf('Common/AdminPassword')))
		{
			$this->setAdminAccessType(AP_SESS_AUTH_TYPE_SUPER_ADMIN);
			return true;
		}
		else if (CApi::GetConf('demo.adminpanel.enable', false) &&
			0 < strlen($sDemoLogin) && $sDemoLogin === CPost::Get('AdmloginInput'))
		{
			$this->setAdminAccessType(AP_SESS_AUTH_TYPE_SUPER_ADMIN_ONLYREAD);
			return true;
		}
		else if ($this->oAdminPanel->PType())
		{
			$aDomainsIds = $this->oAdminPanel->CallModuleFunction('CProModule',
				'GetSubAdminDomainsIdsByLoginPassword', array($sLogin, md5($sPassword)));

			if (is_array($aDomainsIds) && 0 < count($aDomainsIds))
			{
				$this->setAdminAccessType(AP_SESS_AUTH_TYPE_SUBADMIN);
				$this->setAdminAccessDomains($aDomainsIds);
				return true;
			}
		}

		return false;
	}

	/**
	 * @param int $iAccessType
	 */
	protected function setAdminAccessType($iAccessType = AP_SESS_AUTH_TYPE_NONE)
	{
		$this->oAdminPanel->SetAuthType((int) $iAccessType);
		if (in_array((int) $iAccessType, array(AP_SESS_AUTH_TYPE_SUBADMIN,
			AP_SESS_AUTH_TYPE_SUPER_ADMIN, AP_SESS_AUTH_TYPE_SUPER_ADMIN_ONLYREAD)))
		{
			$this->oAdminPanel->SetIsAuth(true);
			$_SESSION[AP_SESS_AUTH] = @session_id();
			$_SESSION[AP_SESS_AUTH_TYPE] = (int) $iAccessType;
		}
	}

	/**
	 * @param array $aDomainsIds
	 */
	protected function setAdminAccessDomains($aDomainsIds)
	{
		$_SESSION[AP_SESS_AUTH_DOMAINS] = is_array($aDomainsIds) ? $aDomainsIds : null;
		$this->oAdminPanel->SetAuthDomains($aDomainsIds);
	}
	
	/**
	* @return array
	*/
	public function GetTimeZoneList()
	{
		return array(
				'Default',
				'(GMT -12:00) Eniwetok, Kwajalein, Dateline Time',
				'(GMT -11:00) Midway Island, Samoa',
				'(GMT -10:00) Hawaii',
				'(GMT -09:00) Alaska',
				'(GMT -08:00) Pacific Time (US & Canada); Tijuana',
				'(GMT -07:00) Arizona',
				'(GMT -07:00) Mountain Time (US & Canada)',
				'(GMT -06:00) Central America',
				'(GMT -06:00) Central Time (US & Canada)',
				'(GMT -06:00) Mexico City, Tegucigalpa',
				'(GMT -06:00) Saskatchewan',
				'(GMT -05:00) Indiana (East)',
				'(GMT -05:00) Eastern Time (US & Canada)',
				'(GMT -05:00) Bogota, Lima, Quito',
				'(GMT -04:00) Santiago',
				'(GMT -04:00) Caracas, La Paz',
				'(GMT -04:00) Atlantic Time (Canada)',
				'(GMT -03:30) Newfoundland',
				'(GMT -03:00) Greenland',
				'(GMT -03:00) Buenos Aires, Georgetown',
				'(GMT -03:00) Brasilia',
				'(GMT -02:00) Mid-Atlantic',
				'(GMT -01:00) Cape Verde Is.',
				'(GMT -01:00) Azores',
				'(GMT) Casablanca, Monrovia',
				'(GMT) Dublin, Edinburgh, Lisbon, London',
				'(GMT +01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna',
				'(GMT +01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague',
				'(GMT +01:00) Brussels, Copenhagen, Madrid, Paris',
				'(GMT +01:00) Sarajevo, Skopje, Sofija, Warsaw, Zagreb',
				'(GMT +01:00) West Central Africa',
				'(GMT +02:00) Athens, Istanbul, Minsk',
				'(GMT +02:00) Bucharest',
				'(GMT +02:00) Cairo',
				'(GMT +02:00) Harare, Pretoria',
				'(GMT +02:00) Helsinki, Riga, Tallinn, Vilnius',
				'(GMT +02:00) Israel, Jerusalem Standard Time',
				'(GMT +03:00) Baghdad',
				'(GMT +03:00) Arab, Kuwait, Riyadh',
				'(GMT +03:00) Moscow, St. Petersburg, Volgograd',
				'(GMT +03:00) East Africa, Nairobi',
				'(GMT +03:30) Tehran',
				'(GMT +04:00) Abu Dhabi, Muscat',
				'(GMT +04:00) Baku, Tbilisi, Yerevan',
				'(GMT +04:30) Kabul',
				'(GMT +05:00) Ekaterinburg',
				'(GMT +05:00) Islamabad, Karachi, Sverdlovsk, Tashkent',
				'(GMT +05:30) Calcutta, Chennai, Mumbai, New Delhi, India Standard Time',
				'(GMT +05:45) Kathmandu, Nepal',
				'(GMT +06:00) Almaty, Novosibirsk, North Central Asia',
				'(GMT +06:00) Astana, Dhaka',
				'(GMT +06:00) Sri Jayawardenepura, Sri Lanka',
				'(GMT +06:30) Rangoon',
				'(GMT +07:00) Bangkok, Hanoi, Jakarta',
				'(GMT +07:00) Krasnoyarsk',
				'(GMT +08:00) Beijing, Chongqing, Hong Kong SAR, Urumqi',
				'(GMT +08:00) Irkutsk, Ulaan Bataar',
				'(GMT +08:00) Kuala Lumpur, Singapore',
				'(GMT +08:00) Perth, Western Australia',
				'(GMT +08:00) Taipei',
				'(GMT +09:00) Osaka, Sapporo, Tokyo',
				'(GMT +09:00) Seoul, Korea Standard time',
				'(GMT +09:00) Yakutsk',
				'(GMT +09:30) Adelaide, Central Australia',
				'(GMT +09:30) Darwin',
				'(GMT +10:00) Brisbane, East Australia',
				'(GMT +10:00) Canberra, Melbourne, Sydney, Hobart',
				'(GMT +10:00) Guam, Port Moresby',
				'(GMT +10:00) Hobart, Tasmania',
				'(GMT +10:00) Vladivostok',
				'(GMT +11:00) Magadan, Solomon Is., New Caledonia',
				'(GMT +12:00) Auckland, Wellington',
				'(GMT +12:00) Fiji Islands, Kamchatka, Marshall Is.',
				'(GMT +13:00) Nuku\'alofa, Tonga,'
		);
	}
	
	/**
	 * @return array
	 */
	public function GetSkinList()
	{
		return $this->oWebmailApi->GetSkinList();
	}
	
	
	/**
	 * @return array
	 */
	public function GetLangsList()
	{
		return $this->oWebmailApi->GetLanguageList();
	}
	
}

