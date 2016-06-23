<?php

defined('WM_ROOTPATH') || define('WM_ROOTPATH', (dirname(__FILE__).'/../'));

include_once WM_ROOTPATH.'application/xml-builder.php';
include_once WM_ROOTPATH.'application/xml-helper.php';

class CAppServer
{
	/**
	 * @var CAppBaseHttp
	 */
	protected $oInput;
	
	/**
	 * @var CXmlDocument
	 */
	protected $oResultXml;
	
	/**
	 * @var CXmlDocument
	 */
	protected $oRequestXml;
	
	/**
	 * @var CApiUsersManager
	 */
	protected $oUsersApi;
	
	/**
	 * @var CApiDomainsManager
	 */
	protected $oDomainsApi;
	
	/**
	 * @var CApiWebmailManager
	 */
	protected $oWebMailApi;
	
	/**
	 * @param CAppBaseHttp $oInput
	 * @param CXmlDocument $oResultXml
	 * @param CXmlDocument $RequestXml
	 */
	public function __construct(CAppBaseHttp $oInput, CXmlDocument $RequestXml, CXmlDocument $oResultXml)
	{
		$this->oInput = $oInput;
		$this->oResultXml = $oResultXml;
		$this->oRequestXml = $RequestXml;
		
		$this->oUsersApi = CApi::Manager('users');
		$this->oDomainsApi = CApi::Manager('domains');
		$this->oWebMailApi = CApi::Manager('webmail');
	}
	
	/**
	 * @param int $iAccountId
	 * @return CAccount
	 */
	public function getAccountById($iAccountId)
	{
		$oAccount = null;
		if (null !== $iAccountId)
		{
			$oAccount = AppGetAccount($iAccountId);
		}
		
		return $oAccount;
	}
	
	/**
	 * @param int $iAccountId = null
	 * @return CAccount
	 */
	public function getAccount($iAccountId = null)
	{
		$iSessAccountId = $this->oInput->GetSession(APP_SESSION_ACCOUNT_ID, null);
		$iAccountId = (null === $iAccountId)  ? $iSessAccountId : $iAccountId;
		
		if (null !== $iSessAccountId && $iSessAccountId !== $iAccountId)
		{
			$aAccountsIds = $this->oUsersApi->GetUserIdList($this->oInput->GetSession(APP_SESSION_USER_ID, null));
			if (!in_array($iAccountId, $aAccountsIds))
			{
				$iAccountId = null;
			}
		}

		$oAccount = null;
		if (null !== $iAccountId)
		{
			$oAccount = $this->getAccountById($iAccountId);
			if ($oAccount)
			{
				$oAccount->FlushObsolete();
			}
		}
		
		return $oAccount;
	}

	/**
	 * @param CAccount $oAccount = null
	 * @return array [$oAccount]
	 */
	public function getAccounts($oAccount = null)
	{
		return AppGetAccounts($oAccount, $this->oInput->GetSession(APP_SESSION_USER_ID, null));
	}

	/**
	 * @param CAccount $oAccount
	 * @return CDomain
	 */
	public function getDefaultAccountDomain(CAccount $oAccount)
	{
		$oDomain = ($oAccount->IsDefaultAccount) ? $oAccount->Domain : null;
		if (null === $oDomain)
		{
			$iDomainId = $this->oUsersApi->GetDefaultAccountDomainId($oAccount->IdUser);
			if (0 < $iDomainId)
			{
				$oDomain = $this->oDomainsApi->GetDomainById($iDomainId);
			}
			else
			{
				$oDomain = $this->oDomainsApi->GetDefaultDomain();
			}

			if (IsAdminLogin())
			{
				$oDomain->AllowUsersAddNewAccounts = true;
				$oDomain->AllowUsersChangeEmailSettings = true;
				$oDomain->AllowUsersChangeInterfaceSettings = true;

				if (!$oDomain->IsDefaultDomain)
				{
					$oDefaultDomain = $this->oDomainsApi->GetDefaultDomain();
					$oDefaultDomain->AllowUsersAddNewAccounts = true;
					$oDefaultDomain->AllowUsersChangeEmailSettings = true;
					$oDefaultDomain->AllowUsersChangeInterfaceSettings = true;
				}
			}
		}
		
		return $oDomain;
	}

	/**
	 * @param CAccount $oAccount
	 * @param object $oMailProcessor
	 * @return array
	 */
	protected function getAccountQuota(CAccount $oAccount, $oMailProcessor)
	{
		$aResult = array(0, 0);
		$oSettings =& CApi::GetSettings();
		if ($oSettings->GetConf('WebMail/TakeImapQuota', false))
		{
			if (EMailProtocol::IMAP4 === $oAccount->IncomingMailProtocol)
			{
				if ($oMailProcessor && $oMailProcessor->MailStorage->Connect())
				{
					$iQuota = $oMailProcessor->GetQuota();
					if (false !== $iQuota)
					{
						$aResult[0] = $iQuota;
					}
					$iUsedQuota = $oMailProcessor->GetUsedQuota();
					if (false !== $iUsedQuota)
					{
						$aResult[1] = $iUsedQuota;
					}
				}
			}
		}

		return $aResult;
	}
	
// LOGIN

	protected function validateLoginCaptcha($oSettings, &$sError, $oRequestXml)
	{
		if ($oSettings->GetConf('WebMail/UseReCaptcha'))
		{
			require_once WM_ROOTPATH.'libs/recaptcha/recaptchalib.php';

			$oResp = recaptcha_check_answer(CApi::GetConf('captcha.recaptcha-private-key', ''),
				$_SERVER['REMOTE_ADDR'],
				$oRequestXml->GetParamValueByName('recaptcha_challenge_field'),
				$oRequestXml->GetParamValueByName('recaptcha_response_field'));
			if (!$oResp)
			{
				$sError = CaptchaError;
			}
			else if (!$oResp->is_valid)
			{
				$sError = CaptchaError;
				if ('incorrect-captcha-sol' !== $oResp->error)
				{
					$sError = 'ReCaptcha: '.$oResp->error;
				}
			}
		}
		else if ($oSettings->GetConf('WebMail/UseCaptcha'))
		{
			$sCaptcha = $oRequestXml->GetParamValueByName('captcha');
			if (!isset($_SESSION['captcha_keystring']) || $sCaptcha != $_SESSION['captcha_keystring'])
			{
				$sError = CaptchaError;
			}
		}
	}
	
	public function DoLogin()
	{
		$sJsTimeOffset = $this->oRequestXml->GetParamValueByName('js_timeoffset');
		if (0 < strlen($sJsTimeOffset))
		{
			$this->oInput->SetSession(API_JS_TIMEOFFSET, $sJsTimeOffset);
		}

		$oSettings =& CApi::GetSettings();

		$sCaptchaError = '';
		if ($oSettings->GetConf('WebMail/UseReCaptcha'))
		{
			$this->validateLoginCaptcha($oSettings, $sCaptchaError, $this->oRequestXml);
		}
		else if ($oSettings->GetConf('WebMail/UseCaptcha'))
		{
			$iCaptchaLimit = CApi::GetConf('captcha.limit-count', 3);
			if (isset($_SESSION['captcha_count']) && (int) $_SESSION['captcha_count'] >= $iCaptchaLimit)
			{
				$this->validateLoginCaptcha($oSettings, $sCaptchaError, $this->oRequestXml);
			}

			$_SESSION['captcha_count'] = isset($_SESSION['captcha_count']) ?
				(int) $_SESSION['captcha_count'] + 1 : 1;

			if ((int) $_SESSION['captcha_count'] >= $iCaptchaLimit)
			{
				$oCaptchaOnNode = new CXmlDomNode('captcha', '1');
				$this->oResultXml->XmlRoot->AppendChild($oCaptchaOnNode);
			}
		}

		if (!empty($sCaptchaError))
		{
			$this->SetErrorResponse($sCaptchaError);
		}
		else
		{
			$bAdvanced = (bool) $this->oRequestXml->GetParamValueByName('advanced');
			$iLoginFormType = $oSettings->GetConf('WebMail/LoginFormType');

			$sEmail = $sLogin = '';
			if (ELoginFormType::Login === $iLoginFormType && !$bAdvanced)
			{
				$sLogin = $this->oRequestXml->GetParamTagValueByName('mail_inc_login');
			}
			else
			{
				$sEmail = $this->oRequestXml->GetParamTagValueByName('email');
			}
			
			$sPassword = $this->oRequestXml->GetParamTagValueByName('mail_inc_pass');
			$sLang = $this->oRequestXml->GetParamTagValueByName('language');

			/* @var $oAccount CAccount */
			$oAccount = null;
			if ($bAdvanced)
			{
				$sLogin = $this->oRequestXml->GetParamTagValueByName('mail_inc_login');
				$sMailIncProtocol = (int) $this->oRequestXml->GetParamValueByName('mail_protocol');
				$sMailIncHost = $this->oRequestXml->GetParamTagValueByName('mail_inc_host');
				$sMailIncPort = $this->oRequestXml->GetParamValueByName('mail_inc_port');
				$sMailOutHost = $this->oRequestXml->GetParamTagValueByName('mail_out_host');
				$sMailOutPort = $this->oRequestXml->GetParamValueByName('mail_out_port');
				$sMailOutAuth = (bool) $this->oRequestXml->GetParamValueByName('mail_out_auth');

				$oAccount = $this->oWebMailApi->LoginToAccountEx
					($sEmail, $sLogin, $sPassword,
						$sMailIncProtocol, $sMailIncHost, $sMailIncPort,
						$sMailOutHost, $sMailOutPort, $sMailOutAuth, $sLang);
			}
			else if (ELoginFormType::Login === $iLoginFormType && !empty($sLogin))
			{
				$oDomain = null;
				$sDefaultDomain = $oSettings->GetConf('WebMail/DefaultDomainValue');
				if (empty($sDefaultDomain))
				{
					$oDomain = $this->oDomainsApi->GetDomainByUrl($this->oInput->GetHost());
				}

				if (!empty($sDefaultDomain) || ($oDomain && 0 < strlen($oDomain->Name)))
				{
					$sEmail = $sLogin;
					if (false === strpos($sLogin, '@'))
					{
						if ($oDomain && 0 < strlen($oDomain->Name))
						{
							$sEmail .= '@'.$oDomain->Name;
						}
						else if (!empty($sDefaultDomain))
						{
							$sEmail .= '@'.$sDefaultDomain;
						}
					}

					if (!$oDomain)
					{
						$oDomain = $this->oDomainsApi->GetDefaultDomain();
					}

					$oAccount = $this->oWebMailApi->LoginToAccountEx
						($sEmail, $sLogin, $sPassword,
							$oDomain->IncomingMailProtocol, $oDomain->IncomingMailServer, $oDomain->IncomingMailPort,
							$oDomain->OutgoingMailServer, $oDomain->OutgoingMailPort, $oDomain->OutgoingMailAuth, $sLang);
				}
			}
			else
			{
				$oAccount = $this->oWebMailApi->LoginToAccount($sEmail, $sPassword, $sLang);
			}

			if ($oAccount)
			{
				$oAccount->FillSession();
				@setcookie('awm_cookie_sess_check', '1', 0, null, null, false, true);

				$oLoginNode = new CXmlDomNode('login');

				if ($this->oRequestXml->GetParamValueByName('sign_me'))
				{
					$oLoginNode->AppendAttribute('id_acct', $oAccount->IdAccount);
					$oLoginNode->AppendChild(new CXmlDomNode('hash',
						md5(api_Utils::EncodePassword($oAccount->IncomingMailPassword)), true));
				}

				$this->oResultXml->XmlRoot->AppendChild($oLoginNode);
			}
			else
			{
				$niCode = null;
				$naParams = null;

				$sError = WebMailException;
				$oException = $this->oWebMailApi->GetLastException();
				switch ($oException->getCode())
				{
					case Errs::UserManager_AccountAuthenticationFailed:
					case Errs::WebMailManager_AccountAuthentication:
					case Errs::WebMailManager_NewUserRegistrationDisabled:
					case Errs::WebMailManager_AccountWebmailDisabled:
						$sError = ErrorPOP3IMAP4Auth;
						break;
					case Errs::UserManager_AccountConnectToMailServerFailed:
					case Errs::WebMailManager_AccountConnectToMailServerFailed:
						$sError = CantConnectToMailServer;
						break;
					case Errs::UserManager_LicenseKeyInvalid:
					case Errs::UserManager_AccountCreateUserLimitReached:
					case Errs::UserManager_LicenseKeyIsOutdated:
						$sError = LicenseProblem;
						break;
					case Errs::Db_ExceptionError:
						$sError = PROC_CANT_LOAD_DB;
						break;
					case Errs::Main_CustomError:
						$aObjectParams = $oException->GetObjectParams();
						if (isset($aObjectParams['custom-error']))
						{
							$sError = $aObjectParams['custom-error'];
						}
						if (isset($aObjectParams['custom-error-code']))
						{
							$niCode = $aObjectParams['custom-error-code'];
						}
						if (isset($aObjectParams['redirect']))
						{
							$naParams = (is_array($naParams)) ? $naParams : array();
							$naParams['redirect'] = $aObjectParams['redirect'];
						}
						break;

				}

				$this->SetErrorResponse($sError, $niCode, $naParams);
			}
		}
	}

// GET

	function DoGetContactsGroups()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			if ($oAccount->User->AllowContacts)
			{
				/* @var $oApiContactsManager CApiContactsManager */
				$oApiContactsManager = CApi::Manager('contacts');

				CAppXmlBuilder::BuildContactList($this->oRequestXml, $this->oResultXml, $oAccount, $oApiContactsManager);
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_GET_CONTS_FROM_DB);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoGetContact()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			if ($oAccount->User->AllowContacts)
			{
				$sAddressId = $this->oRequestXml->GetParamValueByName('id_addr');

				/* @var $oApiContactsManager CApiContactsManager */
				$oApiContactsManager = CApi::Manager('contacts');
				$oContact = $oApiContactsManager->GetContactById($oAccount->User->IdUser, $sAddressId);
				
				if ($oContact)
				{
					CAppXmlBuilder::BuildContact($this->oResultXml, $oContact, $oApiContactsManager);
				}
				else
				{
					$this->SetErrorResponse(PROC_CANT_GET_CONT_FROM_DB);
				}
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_GET_CONT_FROM_DB);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoGetGroup()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			if ($oAccount->User->AllowContacts)
			{
				$sGroupId = $this->oRequestXml->GetParamValueByName('id_group');

				/* @var $oApiContactsManager CApiContactsManager */
				$oApiContactsManager = CApi::Manager('contacts');
				
				$oGroup = $oApiContactsManager->GetGroupById($oAccount->IdUser, $sGroupId);
				if ($oGroup)
				{
					CAppXmlBuilder::BuildGroup($this->oResultXml, $oGroup, $oApiContactsManager);
				}
				else
				{
					$this->SetErrorResponse(PROC_CANT_GET_CONTS_FROM_DB);
				}
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_GET_CONTS_FROM_DB);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	function DoGetGroups()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			if ($oAccount->User->AllowContacts)
			{
				/* @var $oApiContactsManager CApiContactsManager */
				$oApiContactsManager = CApi::Manager('contacts');
				$aGroups = $oApiContactsManager->GetGroupItems($oAccount->IdUser);

				$oGroupsNode = new CXmlDomNode('groups');
				if (is_array($aGroups))
				{
					$oContactListItem = null;
					foreach ($aGroups as /* @var $oContactListItem CContactListItem */ $oContactListItem)
					{
						$oGroupNode = new CXmlDomNode('group');
						$oGroupNode->AppendAttribute('id', $oContactListItem->Id);
						$oGroupNode->AppendChild(new CXmlDomNode('name', $oContactListItem->Name, true));
						$oGroupsNode->AppendChild($oGroupNode);
						unset($oGroupNode);
					}
				}

				$this->oResultXml->XmlRoot->AppendChild($oGroupsNode);
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_GET_CONTS_FROM_DB);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoGetFilters()
	{
		$oMailProcessor = $oFilters = null;
		$iAccountId = (int) $this->oRequestXml->GetParamValueByName('id_acct');
		$oAccount = $this->getAccount($iAccountId);
		if ($oAccount)
		{
			if (USE_DB)
			{
				$oMailProcessor = new MailProcessor($oAccount);
				$oFilters =& $oMailProcessor->DbStorage->SelectFilters($oAccount->IdAccount);
			}
			
			CApi::Plugin()->RunHook('get-sieve-filters', array(&$oAccount, &$oFilters));

			if ($oFilters)
			{
				CAppXmlBuilder::BuildFilterList($this->oResultXml, $oFilters, $oAccount->IdAccount);
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_GET_FILTER_LIST);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoGetAccountBase()
	{
		$iAccountId = (int) $this->oRequestXml->GetParamValueByName('id_acct');
		$oAccount = $this->getAccount($iAccountId);
		if (!$oAccount)
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
		else
		{
			if (1 === (int) $this->oRequestXml->GetParamValueByName('change_acct'))
			{
				$this->oInput->SetSession(APP_SESSION_ACCOUNT_ID, $iAccountId);
			}

			$oMailProcessor = new MailProcessor($oAccount);
			$oFolders =& $oMailProcessor->GetFolders();
			
			if ($oFolders)
			{
				CAppXmlBuilder::BuildFolders($this->oResultXml, $oFolders, $oAccount, $oMailProcessor);
				
				$this->oResultXml->XmlRoot->AppendAttribute('complex', 'account_base');
				
				$oFolder =& $oFolders->GetFolderByType(FOLDERTYPE_Inbox);

				$iSortOrder = 0;
				$iSortField = $oAccount->DefaultOrder / 2;
				if (ceil($oAccount->DefaultOrder / 2) != $iSortField)
				{
					$iSortOrder = 1;
					$iSortField = ($oAccount->DefaultOrder - 1) / 2;
				}

				$oMessageCollection =& $oMailProcessor->GetMessageHeaders(1, $oFolder);

				$aQuotes = $this->getAccountQuota($oAccount, $oMailProcessor);
				CAppXmlBuilder::BuildAccountImapQuotaNode($this->oResultXml, $oAccount->IdAccount, $aQuotes[0], $aQuotes[1]);
				
				if (!CAppXmlBuilder::BuildMessagesList($this->oResultXml, $oMessageCollection, $oAccount,
					$oMailProcessor, $oFolder, '', 0, 1, $iSortField, $iSortOrder))
				{
					$this->SetErrorResponse(PROC_CANT_GET_MSG_LIST);
				}
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_GET_FLDS);
			}
		}
	}
	
	public function DoGetBase()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$sFlashVersion = $this->oRequestXml->GetParamValueByName('flash_version');
			if (!empty($sFlashVersion))
			{
				$this->oInput->SetSession(USER_FLASH_VERSION, (int) $sFlashVersion);
			}

			$this->oResultXml->XmlRoot->AppendAttribute('complex', 'base');

			$aAccounts = $this->getAccounts($oAccount);
			
			$oMailProcessor = new MailProcessor($oAccount);

			$oDomain = $this->getDefaultAccountDomain($oAccount);

			if ($oAccount && $aAccounts && $oDomain)
			{
				$aQuotes = $this->getAccountQuota($oAccount, $oMailProcessor);

				CAppXmlBuilder::BuildAccountImapQuotaNode($this->oResultXml, $oAccount->IdAccount, $aQuotes[0], $aQuotes[1]);
				CAppXmlBuilder::BuildSettingsList($this->oResultXml, $oAccount, $oDomain);
				CAppXmlBuilder::BuildAccountList($this->oResultXml, $aAccounts, $oAccount->IdAccount);
				CAppXmlBuilder::BuildIdentities($this->oResultXml, $aAccounts);

				$oFolders = null;
				$oFolders =& $oMailProcessor->GetFolders();

				if ($oFolders != null)
				{
					CAppXmlBuilder::BuildFolders($this->oResultXml, $oFolders, $oAccount, $oMailProcessor);

					$oInboxFolder =& $oFolders->GetFolderByType(FOLDERTYPE_Inbox);

					$iSortOrder = 0;
					$iSortField = $oAccount->DefaultOrder / 2;
					if (ceil($oAccount->DefaultOrder / 2) != $iSortField)
					{
						$iSortOrder = 1;
						$iSortField = ($oAccount->DefaultOrder - 1) / 2;
					}

					$oMessageCollection =& $oMailProcessor->GetMessageHeaders(1, $oInboxFolder);

					if (!CAppXmlBuilder::BuildMessagesList($this->oResultXml, $oMessageCollection,
						$oAccount, $oMailProcessor, $oInboxFolder, '', 0, 1, $iSortField, $iSortOrder))
					{
						$this->SetErrorResponse(PROC_CANT_GET_MSG_LIST);
					}
				}
				else
				{
					$this->SetErrorResponse(WebMailException);
				}
			}
			else
			{
				$this->SetErrorResponse(WebMailException);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoGetFoldersBase()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$oMailProcessor = new MailProcessor($oAccount);
			$oFolders =& $oMailProcessor->GetFolders();
			
			if ($oFolders)
			{
				$this->oResultXml->XmlRoot->AppendAttribute('complex', 'folders_base');

				$iSortField = $oAccount->DefaultOrder / 2;
				$iSortOrder = 0;
				if (ceil($oAccount->DefaultOrder / 2) != $iSortField)
				{
					$iSortField = ($oAccount->DefaultOrder - 1) / 2;
					$iSortOrder = 1;
				}
				
				$iGetCount = 0;
				$oFolderInbox = $oFolders->GetFolderByType(FOLDERTYPE_Inbox);

				$iFolderBaseLimit = CApi::GetConf('webmail.folder-base-limit', 5);
				$oFolderList = new FolderCollection();
				if (0 < strlen($oAccount->Namespace) && $oFolderInbox && $oFolderInbox->SubFolders && 0 < $oFolderInbox->SubFolders->Count())
				{
					for ($iIndex = 0, $iCount = $oFolderInbox->SubFolders->Count(); $iIndex < $iCount; $iIndex++)
					{
						if ($iGetCount >= $iFolderBaseLimit)
						{
							break;
						}
						
						$oFolder =& $oFolderInbox->SubFolders->Get($iIndex);
						if ($oFolder && !$oFolder->Hide)
						{
							$iGetCount++;
							$oFolderList->Add($oFolder);
						}
						unset($oFolder);
					}
				}
				
				for ($iIndex = 0, $iCount = $oFolders->Count(); $iIndex < $iCount; $iIndex++)
				{
					if ($iGetCount >= $iFolderBaseLimit)
					{
						break;
					}

					$oFolder =& $oFolders->Get($iIndex);
					if ($oFolder && FOLDERTYPE_Inbox !== $oFolder->Type && !$oFolder->Hide)
					{
						$iGetCount++;
						$oFolderList->Add($oFolder);
					}
					unset($oFolder);
				}
				
				$oFolder = null;
				for ($iIndex = 0, $iCount = $oFolderList->Count(); $iIndex < $iCount; $iIndex++)
				{
					$oFolder =& $oFolderList->Get($iIndex);
					if (FOLDERSYNC_DirectMode === $oFolder->SyncType)
					{
						$oMailProcessor->GetFolderMessageCount($oFolder);
					}

					$oMessageCollection =& $oMailProcessor->GetMessageHeaders(1, $oFolder);
					if (!CAppXmlBuilder::BuildMessagesList($this->oResultXml, $oMessageCollection, $oAccount,
							$oMailProcessor, $oFolder, '', 0, 1, $iSortField, $iSortOrder))
					{
						$this->SetErrorResponse(PROC_CANT_GET_MSG_LIST);
					}

					unset($oFolder, $oMessageCollection);
				}
			}
			else
			{
				$this->SetErrorResponse(getGlobalError());
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoGetMessagesBodies()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$oMailProcessor = new MailProcessor($oAccount);
			$sStartIncCharset = $oAccount->User->DefaultIncomingCharset;

			$this->oResultXml->XmlRoot->AppendAttribute('complex', 'messages_bodies');
			/* return true; */

			$aFolders = array();
			if (null != $this->oRequestXml->XmlRoot->Children)
			{
				$aXmlKeys = array_keys($this->oRequestXml->XmlRoot->Children);
				foreach ($aXmlKeys as $sKey)
				{
					$oXmlNode =& $this->oRequestXml->XmlRoot->Children[$sKey];
					if ($oXmlNode && 'folder' === $oXmlNode->TagName)
					{
						$iFolderId = (int) $oXmlNode->GetAttribute('id', -1);
						if (0 < $iFolderId && !isset($aFolders[$iFolderId]))
						{
							$aFolders[$iFolderId] = array('', array());
							if (null != $oXmlNode->Children)
							{
								$aFolderKeys = array_keys($oXmlNode->Children);
								foreach ($aFolderKeys as $sKey)
								{
									$oMsgNode =& $oXmlNode->Children[$sKey];
									if ($oMsgNode)
									{
										if ('message' === $oMsgNode->TagName)
										{
											$iMsgId = $oMsgNode->GetAttribute('id', -1);
											$bVoice = '1' === $oMsgNode->GetAttribute('voice', '0');
											$iMsgCharset = $oMsgNode->GetAttribute('charset', -1);
											$iMsgSize = $oMsgNode->GetAttribute('size', -1);
											$mMsgUid = $oMsgNode->GetChildValueByTagName('uid');
											$aFolders[$iFolderId][1][] = array($iMsgId, $mMsgUid, $iMsgCharset, $iMsgSize, $bVoice);
										}
										else if ($oMsgNode->TagName == 'full_name')
										{
											$aFolders[$iFolderId][0] = api_Utils::DecodeSpecialXmlChars($oMsgNode->Value);
										}
									}
									unset($oMsgNode);
								}
							}
						}
					}
					unset($oXmlNode);
				}
			}

			$iPreloadBodySize = CApi::GetConf('webmail.preload-body-size', 76800);
			$sErrorDesc = '';
			$aFolderArray = array();
			foreach ($aFolders as $iFolderId => $aItem)
			{
				if ($iFolderId < 1)
				{
					$sErrorDesc = WebMailException;
					break;
				}

				$oFolder = null;
				if (isset($aFolderArray[$iFolderId]))
				{
					$oFolder =& $aFolderArray[$iFolderId];
				}
				else
				{
					$oFolder = new Folder($oAccount->IdAccount, $iFolderId, $aItem[0]);
					$oMailProcessor->GetFolderInfo($oFolder);
					$aFolderArray[$iFolderId] =& $oFolder;
				}

				if (!$oFolder || (EMailProtocol::POP3 === $oAccount->IncomingMailProtocol && (
					 $oFolder->SyncType == FOLDERSYNC_AllHeadersOnly || $oFolder->SyncType == FOLDERSYNC_NewHeadersOnly)))
				{
					continue;
				}

				if (is_array($aItem[1]) && count($aItem[1]) > 0)
				{
					foreach ($aItem[1] as $aValues)
					{
						if (is_array($aValues) && 4 < count($aValues))
						{
							$iCharsetNum = $aValues[2];
							if ($iCharsetNum > 0)
							{
								$sCharsetName = ConvertUtils::GetCodePageName($iCharsetNum);
								if (empty($sCharsetName))
								{
									$sCharsetName = CApi::GetConf('webmail.default-inc-charset', 'iso-8859-1');
								}
								
								$oAccount->User->DefaultIncomingCharset = $sCharsetName;
								$oMailProcessor->_account->User->DefaultIncomingCharset = $oAccount->User->DefaultIncomingCharset;
								$GLOBALS[MailInputCharset] = $oAccount->User->DefaultIncomingCharset;
							}
							else
							{
								$oAccount->User->DefaultIncomingCharset = $sStartIncCharset;
								$oMailProcessor->_account->User->DefaultIncomingCharset = $oAccount->User->DefaultIncomingCharset;
								if (isset($GLOBALS[MailInputCharset]))
								{
									unset($GLOBALS[MailInputCharset]);
								}
							}

							$iMsgSize = $aValues[3];
							$iModeForGet = false;
							if ((int) $iMsgSize > 0 && (int) $iMsgSize < $iPreloadBodySize)
							{
								$iModeForGet = null;
								if (EMailProtocol::IMAP4 === $oAccount->IncomingMailProtocol && $oFolder->Type != FOLDERTYPE_Drafts)
								{
									$iModeForGet = 263;
								}
							}
							else if (EMailProtocol::IMAP4 === $oAccount->IncomingMailProtocol)
							{
								$iModeForGet = false;
								if ($oFolder->Type != FOLDERTYPE_Drafts)
								{
									$iModeForGet = 263;
								}
							}

							$bVoice = $aValues[4];
							if ($bVoice)
							{
								$iModeForGet = null;
							}

							$oMessage = null;
							if (false !== $iModeForGet)
							{
								$oMessage =& $oMailProcessor->GetMessage($aValues[0], $aValues[1], $oFolder, $iModeForGet, (EMailProtocol::POP3 === $oAccount->IncomingMailProtocol));
							}

							if (null != $oMessage && ($oMessage->Size < $iPreloadBodySize || EMailProtocol::IMAP4 === $oAccount->IncomingMailProtocol))
							{
								$oFromObj = new EmailAddress();
								$oFromObj->Parse($oMessage->GetFromAsString(true));

								$bShowImages = CApi::GetSettings()->GetConf('WebMail/AlwaysShowImagesInMessage');
								if (USE_DB && 0 < strlen($oFromObj->Email) && false === $bShowImages)
								{
									$bShowImages = $oMailProcessor->DbStorage->SelectSenderSafetyByEmail(
										$oFromObj->Email, $oAccount->IdUser);
								}

								$iModeForGet = ($oFolder->Type == FOLDERTYPE_Drafts) ? 263 + 512 : 263;

								CAppXmlBuilder::BuildMessage($this->oResultXml, $oAccount, $oMailProcessor,
									$oMessage, $oFolder, $iModeForGet, $iCharsetNum, $bShowImages);
							}

							unset($oMessage);
						}
					}
				}
				unset($oFolder);
			}

			if (!empty($sErrorDesc))
			{
				$this->SetErrorResponse($sErrorDesc);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoGetAccounts()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$aAccounts = $this->getAccounts($oAccount);
			CAppXmlBuilder::BuildAccountList($this->oResultXml, $aAccounts, $oAccount->IdAccount);
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoGetAccount()
	{
		$iAccountId = (int) $this->oRequestXml->GetParamValueByName('id_acct');
		$oAccount = $this->getAccount($iAccountId);
		if ($oAccount)
		{
			CAppXmlBuilder::BuildAccount($this->oResultXml->XmlRoot, $oAccount);
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoGetFoldersList()
	{
		$iAccountId = (int) $this->oRequestXml->GetParamValueByName('id_acct');
		$oAccount = $this->getAccount($iAccountId);
		if ($oAccount)
		{
			$iSyncType = (int) $this->oRequestXml->GetParamValueByName('sync');
			$bChangeAccount = false;

			if ($iSyncType != -1)
			{
				if ($iAccountId != $this->oInput->GetSession(APP_SESSION_ACCOUNT_ID))
				{
					if ($iSyncType != 2)
					{
						$bChangeAccount = true;
						$this->oInput->SetSession(APP_SESSION_ACCOUNT_ID, $iAccountId);
					}
				}
				else
				{
					$iAccountId = $this->oInput->GetSession(APP_SESSION_ACCOUNT_ID);
				}
			}

			if ($iAccountId !== $oAccount->IdAccount)
			{
				$oAccount = $this->getAccount($iAccountId);
			}
			
			$oMailProcessor = new MailProcessor($oAccount);

			$oFolders =& $oMailProcessor->GetFolders();
			if ($oFolders)
			{
				CAppXmlBuilder::BuildFolders($this->oResultXml, $oFolders, $oAccount, $oMailProcessor);
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_GET_FLDS);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoGetMessages()
	{
		$iAccountId = (int) $this->oRequestXml->GetParamValueByName('id_acct');
		$oAccount = $this->getAccount($iAccountId);
		$oFolderNode = $oSearchNode = null;
		if ($oAccount)
		{
			$iPage = 1;
			$oMailProcessor = new MailProcessor($oAccount);
			
			$oFolderNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('folder');
			$oSearchNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('look_for');
			
			$oFolders = $oFolder = null;
			$iFolderId = isset($oFolderNode->Attributes['id']) ? $oFolderNode->Attributes['id'] : null;
			$sFolderName = $oFolderNode->GetChildValueByTagName('full_name');
			$sSearchFields = isset($oSearchNode->Attributes['fields']) ? $oSearchNode->Attributes['fields'] : null;
			
			if (null !== $iFolderId && is_numeric($iFolderId) && null !== $sSearchFields)
			{
				$oFolder = new Folder($oAccount->IdAccount, (int) $iFolderId, $sFolderName);
				if (!USE_DB)
				{
					$oFolder->SyncType = FOLDERSYNC_DirectMode;
				}
				
				$iSortField = (int) $this->oRequestXml->GetParamValueByName('sort_field');
				$iSortOrder = (int) $this->oRequestXml->GetParamValueByName('sort_order');
				$iFilter = (int) $this->oRequestXml->GetParamValueByName('filter');
				
				if (!in_array($iFilter, array(APP_MESSAGE_LIST_FILTER_NONE,
					APP_MESSAGE_LIST_FILTER_UNSEEN, APP_MESSAGE_LIST_FILTER_WITH_ATTACHMENTS)))
				{
					$iFilter = APP_MESSAGE_LIST_FILTER_NONE;
				}

				if ($iSortField + $iSortOrder !== $oAccount->DefaultOrder)
				{
					$oAccount->DefaultOrder = $iSortField + $iSortOrder;
					$this->oUsersApi->UpdateAccount($oAccount);
				}
				
				$sSearchValue = trim(api_Utils::DecodeSpecialXmlChars($oSearchNode->Value));

				$oMessageCollection = null;
				if (empty($sSearchValue))
				{
					$oMailProcessor->GetFolderInfo($oFolder);
					$oMailProcessor->GetFolderMessageCount($oFolder);

					if (!$oFolder->IsNoSelect())
					{
						if (ceil($oFolder->MessageCount/$oAccount->User->MailsPerPage) < (int) $this->oRequestXml->GetParamValueByName('page'))
						{
							$iPage = (int) $this->oRequestXml->GetParamValueByName('page') - 1;
							$iPage = ($iPage < 1) ? 1 : $iPage;
						}
						else
						{
							$iPage = (int) $this->oRequestXml->GetParamValueByName('page');
						}

						$oMessageCollection =& $oMailProcessor->GetMessageHeaders($iPage, $oFolder, $iFilter);
						if ($oMessageCollection && null !== $oMessageCollection->FilteredCount)
						{
							$oFolder->MessageCount = $oMessageCollection->FilteredCount;
						}
					}
				}
				else
				{
					if ($oFolder->IdDb == -1)
					{
						if (USE_DB)
						{
							$oFolders =& $oMailProcessor->GetFolders();
						}
						else
						{
							$oTmpFolders =& $oMailProcessor->GetFolders();
							$oFolder = $oTmpFolders->GetFolderByType(FOLDERTYPE_Inbox);
						}
					}
					else
					{
						$oMailProcessor->GetFolderInfo($oFolder);

						$oFolders = new FolderCollection();
						$oFolders->Add($oFolder);
					}

					if (!$oFolder->IsNoSelect())
					{
						$iPage = (int) $this->oRequestXml->GetParamValueByName('page');

						if (EMailProtocol::IMAP4 === $oAccount->IncomingMailProtocol
								&& $oFolder->SyncType == FOLDERSYNC_DirectMode && $oFolder->IdDb > 0)
						{
							$oMailProcessor->GetFolderInfo($oFolder);

							$iMsgCount = 0;
							$oMessageCollection =& $oMailProcessor->DmImapSearchMessages(
								$iPage,	$sSearchValue, $oFolder, (bool) !$sSearchFields, $iMsgCount, $iFilter);

							$oFolder->MessageCount = $iMsgCount;
						}
						else if (EMailProtocol::IMAP4 === $oAccount->IncomingMailProtocol
								&& (bool) $sSearchFields && $oFolder->IdDb > 0
								&& ($oFolder->SyncType == FOLDERSYNC_AllHeadersOnly 
								|| $oFolder->SyncType == FOLDERSYNC_NewHeadersOnly))
						{
							$oMailProcessor->GetFolderInfo($oFolder);

							$iMsgCount = 0;
							$oMessageCollection =& $oMailProcessor->HeadersFullImapSearchMessages(
								$iPage,	$sSearchValue, $oFolder, $iMsgCount, $iFilter);

							$oFolder->MessageCount = $iMsgCount;
						}
						else
						{
							$oFolder->MessageCount = $oMailProcessor->SearchMessagesCount(
								$sSearchValue, $oFolders, (bool) !$sSearchFields);

							$oMessageCollection =& $oMailProcessor->SearchMessages($iPage,
								$sSearchValue, $oFolders, (bool) !$sSearchFields, $oFolder->MessageCount);
						}
					}
				}

				if (!$oFolder->IsNoSelect())
				{
					if (!CAppXmlBuilder::BuildMessagesList($this->oResultXml, $oMessageCollection, $oAccount,
						$oMailProcessor, $oFolder, $sSearchValue, $sSearchFields, $iPage, $iSortField, $iSortOrder,	$iFilter))
					{
						$this->SetErrorResponse(PROC_CANT_GET_MSG_LIST);
					}
				}
				else
				{
					$this->SetErrorResponse('NO-SELECT'); // TODO
				}
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_GET_MSG_LIST);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoGetMessage()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$iCharsetNum = (int) $this->oRequestXml->GetParamValueByName('charset');
			if ($iCharsetNum > 0)
			{
				$sCharsetName = ConvertUtils::GetCodePageName($iCharsetNum);
				if (empty($sCharsetName))
				{
					$sCharsetName = CApi::GetConf('webmail.default-inc-charset', 'iso-8859-1');
				}

				$oAccount->User->DefaultIncomingCharset = $sCharsetName;
				$GLOBALS[MailInputCharset] = $oAccount->User->DefaultIncomingCharset;
				$this->oUsersApi->UpdateAccount($oAccount);
			}

			$oFolderNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('folder');
			if (isset($oFolderNode->Attributes['id']))
			{
				$oMailProcessor = new MailProcessor($oAccount);
				
				$oFolder = new Folder($oAccount->IdAccount, $oFolderNode->Attributes['id'], 
					$oFolderNode->GetChildValueByTagName('full_name'));
				
				$oMailProcessor->GetFolderInfo($oFolder);

				if ($oFolder && $oFolder->IdDb > 0)
				{
					$iMsgId = $this->oRequestXml->GetParamValueByName('id');
					$bVoice = '1' === $this->oRequestXml->GetParamValueByName('voice');
					$sMsgUid = $this->oRequestXml->GetParamTagValueByName('uid');
					$iMsgSize = $this->oRequestXml->GetParamValueByName('size');
					
					$aMsgIdUid = array($iMsgId => $sMsgUid);

					$iMode = (int) $this->oRequestXml->GetParamValueByName('mode');
					$iModeForGet = $iMode;
					if ($bVoice || empty($iMsgSize) || (int) $iMsgSize < CApi::GetConf('webmail.bodystructure-message-size-limit', 20000) ||	// size
							($oFolder && FOLDERTYPE_Drafts == $oFolder->Type) ||	// draft
							(($iMode & 8) == 8 || ($iMode & 16) == 16 ||			// forward
								($iMode & 32) == 32 || ($iMode & 64) == 64))
					{
						$iModeForGet = null;
					}

					$oMessage = null;
					$oMessage =& $oMailProcessor->GetMessage($iMsgId, $sMsgUid, $oFolder, $iModeForGet);

					if (null != $oMessage)
					{
						$sSeen = $this->oRequestXml->GetParamValueByName('seen');
						if (($oMessage->Flags & MESSAGEFLAGS_Seen) != MESSAGEFLAGS_Seen && '0' !== $sSeen)
						{
							$oMailProcessor->SetFlag($aMsgIdUid, $oFolder, MESSAGEFLAGS_Seen, ACTION_Set);
						}

						$bShowImages = CApi::GetSettings()->GetConf('WebMail/AlwaysShowImagesInMessage');
						if (USE_DB && ($iModeForGet === null || (($iModeForGet & 1) == 1)))
						{
							$oFrom = new EmailAddress();
							$oFrom->Parse($oMessage->GetFromAsString(true));

							if (0 < strlen($oFrom->Email) && false === $bShowImages)
							{
								$bShowImages = $oMailProcessor->DbStorage->SelectSenderSafetyByEmail(
									$oFrom->Email, $oAccount->IdUser);
							}

							if ($oFolder->SyncType != FOLDERSYNC_DirectMode && $oMailProcessor->DbStorage->Connect())
							{
								$oMailProcessor->DbStorage->UpdateMessageCharset($iMsgId, $iCharsetNum, $oMessage);
							}
						}

						CAppXmlBuilder::BuildMessage($this->oResultXml, $oAccount, $oMailProcessor, 
							$oMessage, $oFolder, $iMode, $iCharsetNum, $bShowImages);
					}
					else
					{
						$this->SetErrorResponse(getGlobalError());
					}
				}
				else
				{
					$this->SetErrorResponse(WebMailException);
				}
			}
			else
			{
				$this->SetErrorResponse(WebMailException);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoGetSettings()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$oSettingsNode = new CXmlDomNode('settings');
			$oSettingsNode->AppendAttribute('msgs_per_page', (int) $oAccount->User->MailsPerPage);
			$oSettingsNode->AppendAttribute('contacts_per_page', (int) $oAccount->User->ContactsPerPage);
			$oSettingsNode->AppendAttribute('auto_checkmail_interval', (int) $oAccount->User->AutoCheckMailInterval);
			$oSettingsNode->AppendAttribute('def_editor', (int) $oAccount->User->DefaultEditor);
			$oSettingsNode->AppendAttribute('def_timezone', (int) $oAccount->User->DefaultTimeZone);
			$oSettingsNode->AppendAttribute('layout', (int) $oAccount->User->Layout);

			$aSkins = $this->oWebMailApi->GetSkinList();
			$sDefaultSkin = strtolower($oAccount->User->DefaultSkin);
			$oSkinsNode = new CXmlDomNode('skins');
			foreach ($aSkins as $sSkinName)
			{
				$oSkinNode = new CXmlDomNode('skin', $sSkinName, true);
				$oSkinNode->AppendAttribute('def', (int) ($sDefaultSkin === strtolower($sSkinName)));

				$oSkinsNode->AppendChild($oSkinNode);
				unset($oSkinNode);
			}
			$oSettingsNode->AppendChild($oSkinsNode);

			$aLangs = $this->oWebMailApi->GetLanguageList();
			$sDefaultLanguage = strtolower($oAccount->User->DefaultLanguage);
			$oLangsNode = new CXmlDomNode('langs');
			foreach ($aLangs as $sLangName)
			{
				$oLangNode = new CXmlDomNode('lang', $sLangName, true);
				$oLangNode->AppendAttribute('def', (int) ($sDefaultLanguage === strtolower($sLangName)));
				
				$oLangsNode->AppendChild($oLangNode);
				unset($oLangNode);
			}
			$oSettingsNode->AppendChild($oLangsNode);

			$oSettingsNode->AppendAttribute('time_format', $oAccount->User->DefaultTimeFormat);

			$this->oResultXml->XmlRoot->AppendChild($oSettingsNode);
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoGetMobileSync()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$oSettings =& CApi::GetSettings();
			$bEnableMobileSync = $oSettings->GetConf('Common/EnableMobileSync');
			
			$oMobileSyncNode = new CXmlDomNode('mobile_sync');
			$oMobileSyncNode->AppendAttribute('enable_system', (int) $bEnableMobileSync);

			if ($bEnableMobileSync)
			{
				$oMobileSyncNode->AppendChild(new CXmlDomNode('url',
					$oSettings->GetConf('Common/MobileSyncUrl'), true));
				$oMobileSyncNode->AppendChild(new CXmlDomNode('contact_db',
					$oSettings->GetConf('Common/MobileSyncContactDataBase'), true));
				$oMobileSyncNode->AppendChild(new CXmlDomNode('calendar_db',
					$oSettings->GetConf('Common/MobileSyncCalendarDataBase'), true));
				
				$oMobileSyncNode->AppendAttribute('enable_account', (int) $oAccount->User->EnableMobileSync);
				$oMobileSyncNode->AppendChild(new CXmlDomNode('login', $oAccount->Email, true));
			}
			else
			{
				$oMobileSyncNode->AppendAttribute('enable_account', '0');
				$oMobileSyncNode->AppendChild(new CXmlDomNode('url', '', true));
				$oMobileSyncNode->AppendChild(new CXmlDomNode('contact_db', '', true));
				$oMobileSyncNode->AppendChild(new CXmlDomNode('calendar_db', '', true));
				$oMobileSyncNode->AppendChild(new CXmlDomNode('login', '', true));
			}

			$this->oResultXml->XmlRoot->AppendChild($oMobileSyncNode);
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoGetSettingsList()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$oDomain = $this->getDefaultAccountDomain($oAccount);
			CAppXmlBuilder::BuildSettingsList($this->oResultXml, $oAccount, $oDomain);
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
// CREATE / NEW / ADD
	
	public function DoAddContacts()
	{
		$oContactsNode = null;
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			if ($oAccount->User->AllowContacts)
			{
				/* @var $oApiContactsManager CApiContactsManager */
				$oApiContactsManager = CApi::Manager('contacts');

				$iGroupId = $this->oRequestXml->GetParamValueByName('id_group');
				$oContactsNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('contacts');

				$oGroup = $oApiContactsManager->GetGroupById($oAccount->IdUser, $iGroupId);
				if ($oGroup)
				{
					$aContactIds = $oGroup->ContactsIds;
					$mKey = null;
					$oContactsKeys = array_keys($oContactsNode->Children);
					foreach ($oContactsKeys as $mKey)
					{
						$oContactNode =& $oContactsNode->Children[$mKey];
						if (isset($oContactNode->Attributes['id']))
						{
							$aContactIds[] = $oContactNode->Attributes['id'];
						}

						unset($oContactNode);
					}

					$oGroup->ContactsIds = array_unique($aContactIds);
				}
				
				if ($oApiContactsManager->UpdateGroup($oGroup))
				{
					$this->SetUpdateResponse('group');
				}
				else
				{
					$this->SetErrorResponse(PROC_CANT_ADD_NEW_CONT_TO_GRP);
				}
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_ADD_NEW_CONT_TO_GRP);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoNewGroup()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			if ($oAccount->User->AllowContacts)
			{
				/* @var $oApiContactsManager CApiContactsManager */
				$oApiContactsManager = CApi::Manager('contacts');
				
				$oGroupNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('group');

				$oGroup = new CGroup();

				$oGroup->IdUser = $oAccount->IdUser;
				$oGroup->Name = $oGroupNode->GetChildValueByTagName('name', true);

				$oGroup->IsOrganization = false;
				if (isset($oGroupNode->Attributes['organization']))
				{
					$oGroup->IsOrganization = (bool) $oGroupNode->Attributes['organization'];
				}

				if ($oGroup->IsOrganization)
				{
					$oGroup->Email = $oGroupNode->GetChildValueByTagName('email', true);
					$oGroup->Company = $oGroupNode->GetChildValueByTagName('company', true);
					$oGroup->Street = $oGroupNode->GetChildValueByTagName('street', true);
					$oGroup->City = $oGroupNode->GetChildValueByTagName('city', true);
					$oGroup->State = $oGroupNode->GetChildValueByTagName('state', true);
					$oGroup->Zip = $oGroupNode->GetChildValueByTagName('zip', true);
					$oGroup->Country = $oGroupNode->GetChildValueByTagName('country', true);
					$oGroup->Phone = $oGroupNode->GetChildValueByTagName('phone', true);
					$oGroup->Fax = $oGroupNode->GetChildValueByTagName('fax', true);
					$oGroup->Web = $oGroupNode->GetChildValueByTagName('web', true);
				}

				$oContactsNode =& $oGroupNode->GetChildNodeByTagName('contacts');
				if ($oContactsNode)
				{
					$mKey = null;
					$aContactIds = array();
					$oContactsKeys = array_keys($oContactsNode->Children);
					foreach ($oContactsKeys as $mKey)
					{
						$oContactNode =& $oContactsNode->Children[$mKey];
						if (isset($oContactNode->Attributes['id']))
						{
							$aContactIds[] = $oContactNode->Attributes['id'];
						}

						unset($oContactNode);
					}

					$oGroup->ContactsIds = array_unique($aContactIds);
				}

				if ($oApiContactsManager->CreateGroup($oGroup))
				{
					CAppXmlBuilder::BuildContactList($this->oRequestXml, $this->oResultXml, $oAccount, $oApiContactsManager);
					CApi::LogEvent('User add PAB (group "'.$oGroup->Name.'")', $oAccount);
				}
				else
				{
					$sError = PROC_CANT_INS_NEW_GROUP;
					switch ($oApiContactsManager->GetLastErrorCode())
					{
						// TODO
//						case Errs::UserManager_AccountCreateUserLimitReached:
//							$sError = ErrorMaximumUsersLicenseIsExceeded;
//							break;
					}

					$this->SetErrorResponse($sError);
				}
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_INS_NEW_CONTS);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoNewContact()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			if ($oAccount->User->AllowContacts)
			{
				/* @var $oApiContactsManager CApiContactsManager */
				$oApiContactsManager = CApi::Manager('contacts');
				
				$oContact = CAppXmlHelper::GetContact($this->oRequestXml, $oAccount);

				if ($oApiContactsManager->CreateContact($oContact))
				{
					CAppXmlBuilder::BuildContactList(
						$this->oRequestXml, $this->oResultXml, $oAccount, $oApiContactsManager, $oContact->IdContact);
					
					CApi::LogEvent('User add PAB (contact)', $oAccount);
				}
				else
				{
					$sError = PROC_CANT_INS_NEW_CONTS;
					switch ($oApiContactsManager->GetLastErrorCode())
					{
						// TODO
						case Errs::UserManager_AccountCreateUserLimitReached:
							$sError = ErrorMaximumUsersLicenseIsExceeded;
							break;
					}

					$this->SetErrorResponse($sError);
				}
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_INS_NEW_CONTS);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoNewAccount()
	{
		$oAccount = $this->getAccount();
		$oAccountNode = $this->oRequestXml->XmlRoot->GetChildNodeByTagName('account');
		
		if ($oAccount && $oAccountNode)
		{
			$oDomain = $this->oDomainsApi->GetDefaultDomain();
			if ($oDomain)
			{
				$oNewAccount = new CAccount($oDomain);
				$oNewAccount->IdUser = $oAccount->IdUser;
				$oNewAccount->IsDefaultAccount = false;

				$oDomain = $this->getDefaultAccountDomain($oAccount);
				if (!$oDomain)
				{
					$this->SetErrorResponse(WebMailException);
				}
				else
				{
					CAppXmlHelper::PopulateAccount($oAccountNode, $oNewAccount, $oDomain);

					// TODO
					$oNewAccount->IncomingMailUseSSL = in_array($oNewAccount->IncomingMailPort, array(993, 995));
					$oNewAccount->OutgoingMailUseSSL = in_array($oNewAccount->OutgoingMailPort, array(465));

					if ($this->oUsersApi->CreateAccount($oNewAccount))
					{
						$oMailProcessor = new MailProcessor($oNewAccount);
						$oMailProcessor->SynchronizeFolders();

						$aAccounts = $this->getAccounts($oAccount);
						CAppXmlBuilder::BuildAccountList($this->oResultXml, $aAccounts, $oAccount->IdAccount, $oNewAccount->IdAccount);
					}
					else
					{
						$this->SetErrorResponse(PROC_ERROR_ACCT_CREATE);
					}
				}
			}
		}
		else if ($oAccountNode)
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
		else
		{
			$this->SetErrorResponse(WebMailException);
		}
	}
	
	public function DoNewFolder()
	{
		$iAccountId = (int) $this->oRequestXml->GetParamValueByName('id_acct');
		$oAccount = $this->getAccount($iAccountId);
		if ($oAccount)
		{
			$iParentId = (int) $this->oRequestXml->GetParamValueByName('id_parent');

			$sFolderName = ConvertUtils::ConvertEncoding(
				$this->oRequestXml->GetParamTagValueByName('name'), CPAGE_UTF8, CPAGE_UTF7_Imap);

			$sParentPath = ($iParentId == -1)
				? '' : $this->oRequestXml->GetParamTagValueByName('full_name_parent').$oAccount->Delimiter;

			$oFolder = null;
			if (EMailProtocol::IMAP4 === $oAccount->IncomingMailProtocol)
			{
				$oFolder = new Folder($oAccount->IdAccount, -1, $sParentPath.$sFolderName, $sFolderName, FOLDERSYNC_DirectMode);
			}
			else if (EMailProtocol::POP3 === $oAccount->IncomingMailProtocol)
			{
				$oFolder = new Folder($oAccount->IdAccount, -1, $sParentPath.$sFolderName, $sFolderName, FOLDERSYNC_DontSync);
			}
			
			$oFolder->IdParent = $iParentId;
			$oFolder->Type = FOLDERTYPE_Custom;
			$oFolder->Hide = false;
			
			$sValidate = $oFolder->ValidateData();
			if (true !== $sValidate)
			{
				$this->SetErrorResponse($sValidate);
			}
			else
			{
				$oMailProcessor = new MailProcessor($oAccount);
				$oFolders =& $oMailProcessor->GetFolders();
				$oFolderList =& $oFolders->CreateFolderListFromTree();
				
				$bIsFolderExist = false;
				$aFolderListKeys = array_keys($oFolderList->Instance());
				foreach ($aFolderListKeys as $iKey)
				{
					$oListFolder = & $oFolderList->Get($iKey);
					if (strtolower($oListFolder->FullName) == strtolower($oFolder->FullName))
					{
						$bIsFolderExist = true;
						break;
					}

					unset($oListFolder);
				}
				
				if ($bIsFolderExist)
				{
					$this->SetErrorResponse(PROC_FOLDER_EXIST);
				}
				else if ($oMailProcessor->CreateFolder($oFolder, true))
				{
					$oFolders =& $oMailProcessor->GetFolders();
					
					if ($oFolders)
					{
						CAppXmlBuilder::BuildFolders($this->oResultXml, $oFolders, $oAccount, $oMailProcessor);
					}
					else
					{
						$this->SetErrorResponse(PROC_CANT_CREATE_FLD);
					}
				}
				else
				{
					$this->SetErrorResponse(PROC_CANT_CREATE_FLD);
				}
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoSetSender()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$oEmail = new EmailAddress();
			$oEmail->Parse(trim($this->oRequestXml->GetParamTagValueByName('sender')));
			if (!empty($oEmail->Email))
			{
				$oDbStorage =& DbStorageCreator::CreateDatabaseStorage($oAccount);
				$oDbStorage->SetSenders($oEmail->Email,
					(int) $this->oRequestXml->GetParamValueByName('safety'), $oAccount->IdUser);
			}

			$this->SetUpdateResponse('set_sender');
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
// UPDATE

	public function DoUpdateFolders()
	{
		$iAccountId = (int) $this->oRequestXml->GetParamValueByName('id_acct');
		$oAccount = $this->getAccount($iAccountId);
		if ($oAccount)
		{
			$oMailProcessor = new MailProcessor($oAccount);

			$oFoldersNode = null;
			if ($oMailProcessor->MailStorage->Connect())
			{
				$oFoldersNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('folders');

				$iResult = 1;

				$aServerFoldersName = array();
				if (EMailProtocol::IMAP4 === $oAccount->IncomingMailProtocol)
				{
					$aFolders = $oMailProcessor->MailStorage->GetFolders();
					$oServerFolders = $aFolders->CreateFolderListFromTree();
					$aServerFolders = $oServerFolders->Instance();

					foreach ($aServerFolders as $oFolder)
					{
						$aServerFoldersName[] = strtolower($oFolder->FullName);
					}

					unset($aFolders, $oServerFolders, $aServerFolders, $oFolder);
				}

				$aLSubList = null;
				$sError = '';
				for ($mKey = count($oFoldersNode->Children) - 1; $mKey >= 0; $mKey--)
				{
					$mFolderNode =& $oFoldersNode->Children[$mKey];

					if (!ConvertUtils::CheckDefaultWordsFileName($mFolderNode->GetChildValueByTagName('name')) ||
							!ConvertUtils::CheckFileName($mFolderNode->GetChildValueByTagName('name')))
					{
						$iResult = 0;
						$sError = PROC_CANT_UPD_FLD;
						break;
					}

					$sNewFolderName = ConvertUtils::ConvertEncoding(
						ConvertUtils::ClearFileName(
							ConvertUtils::WMBackHtmlSpecialChars($mFolderNode->GetChildValueByTagName('name'))),
						CPAGE_UTF8, CPAGE_UTF7_Imap);

					$bIsNewFolderHide = (bool) $mFolderNode->GetAttribute('hide', false);
					$iNewFolderType = (int) $mFolderNode->GetAttribute('type', 0);
					$iNewFolderType = ($iNewFolderType === 0) ? FOLDERTYPE_Custom : $iNewFolderType;

					$sFullFolderName = $mFolderNode->GetChildValueByTagName('full_name');

					$oFolder = new Folder($oAccount->IdAccount, $mFolderNode->GetAttribute('id', -1), $sFullFolderName);
					$oMailProcessor->GetFolderInfo($oFolder);

					$bIsRename = false;
					if ($oFolder->Name != $sNewFolderName)
					{
						CApi::Log('personal folder (rename "'.$oFolder->FullName.'" => "'.$sNewFolderName.'")');
						if (null === $aLSubList)
						{
							$aLSubList = $oMailProcessor->GetLsubFolders();
						}

						$sOldName = $oFolder->Name;
						$oFolder->Name = $sNewFolderName;
						$sValidate = $oFolder->ValidateData();
						if (true !== $sValidate)
						{
							$iResult = 0;
							$sError = $sValidate;
							break;
						}
						else
						{
							$oFolder->Name = $sOldName;
						}

						$iResult &= $oMailProcessor->RenameFolder(
							$oFolder, $sNewFolderName, $oAccount->Delimiter, $aLSubList);

						CApi::LogEvent('personal folder (rename "'.$oFolder->FullName.'" => "'.$sNewFolderName.'")', $oAccount);
						$bIsRename = true;
					}

					if ($oFolder->Hide != $bIsNewFolderHide)
					{
						$oFolder->Hide = $bIsNewFolderHide;
						$oMailProcessor->SetHide($oFolder, $bIsNewFolderHide);
					}

					if ($oFolder->Type != $iNewFolderType)
					{
						$oFolder->Type = $iNewFolderType;
					}

					$oFolder->Name = $sNewFolderName;
					$oFolder->SyncType = (int) $mFolderNode->GetAttribute('sync_type', FOLDERSYNC_DontSync);
					$oFolder->FolderOrder = (int) $mFolderNode->GetAttribute('fld_order', 0);

					if (!$bIsRename && $oAccount->IncomingMailProtocol != EMailProtocol::POP3 &&
							!in_array(strtolower($oFolder->FullName), $aServerFoldersName) &&
							$oFolder->SyncType != FOLDERSYNC_DontSync)
					{
						$iResult &= $oMailProcessor->MailStorage->CreateFolder($oFolder);
					}

					if ((bool) $iResult)
					{
						$iResult &= (!USE_DB) ? true : $oMailProcessor->DbStorage->UpdateFolder($oFolder);
					}
					else
					{
						$iResult = 0;
						$sError = PROC_CANT_UPD_FLD;
						break;
					}
				}

				if ($iResult)
				{
					$oFolders =& $oMailProcessor->GetFolders();
					CAppXmlBuilder::BuildFolders($this->oResultXml, $oFolders, $oAccount, $oMailProcessor);
				}
				else
				{
					$this->SetErrorResponse(PROC_CANT_UPD_FLD);
				}
			}
			else
			{
				$this->SetErrorResponse(getGlobalError());
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoUpdateContact()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			if ($oAccount->User->AllowContacts)
			{
				/* @var $oApiContactsManager CApiContactsManager */
				$oApiContactsManager = CApi::Manager('contacts');

				$oContact = CAppXmlHelper::GetContact($this->oRequestXml, $oAccount);

				if ($oApiContactsManager->UpdateContact($oContact))
				{
					CAppXmlBuilder::BuildContactList($this->oRequestXml, $this->oResultXml, $oAccount, $oApiContactsManager);
					CApi::LogEvent('User edit PAB (contact)', $oAccount);
				}
				else
				{
					$sError = PROC_CANT_UPDATE_CONT;
					switch ($oApiContactsManager->GetLastErrorCode())
					{
						// TODO
//						case Errs::UserManager_AccountCreateUserLimitReached:
//							$sError = $sError;
//							break;
					}
					$this->SetErrorResponse($sError);
				}
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_UPDATE_CONT);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoUpdateGroup()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			if ($oAccount->User->AllowContacts)
			{
				/* @var $oApiContactsManager CApiContactsManager */
				$oApiContactsManager = CApi::Manager('contacts');

				$oGroupNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('group');

				$oGroup = new CGroup();
				$oGroup->IdGroup = $oGroupNode->GetAttribute('id', -1);
				$oGroup->IdUser = $oAccount->IdUser;

				$oGroup->Name = $oGroupNode->GetChildValueByTagName('name', true);
				$oGroup->IsOrganization = (bool) $oGroupNode->GetAttribute('organization', false);

				$oGroup->Email = $oGroupNode->GetChildValueByTagName('email', true);
				$oGroup->Company = $oGroupNode->GetChildValueByTagName('company', true);
				$oGroup->Street = $oGroupNode->GetChildValueByTagName('street', true);
				$oGroup->City = $oGroupNode->GetChildValueByTagName('city', true);
				$oGroup->State = $oGroupNode->GetChildValueByTagName('state', true);
				$oGroup->Zip = $oGroupNode->GetChildValueByTagName('zip', true);
				$oGroup->Country = $oGroupNode->GetChildValueByTagName('country', true);
				$oGroup->Phone = $oGroupNode->GetChildValueByTagName('phone', true);
				$oGroup->Fax = $oGroupNode->GetChildValueByTagName('fax', true);
				$oGroup->Web = $oGroupNode->GetChildValueByTagName('web', true);

				$aContactsIds = array();
				$oContactsNode = $mKey = null;
				$oContactsNode =& $oGroupNode->GetChildNodeByTagName('contacts');
				$aContactsKeys = array_keys($oContactsNode->Children);
				foreach ($aContactsKeys as $mKey)
				{
					$oContactNode =& $oContactsNode->Children[$mKey];
					$aContactsIds[] = $oContactNode->GetAttribute('id', -1);
					unset($oContactNode);
				}

				$oGroup->ContactsIds = $aContactsIds;

				if ($oApiContactsManager->UpdateGroup($oGroup))
				{
					CAppXmlBuilder::BuildContactList($this->oRequestXml, $this->oResultXml, $oAccount, $oApiContactsManager);
					CApi::LogEvent('User edit PAB (group "'.$oGroup->Name.'")', $oAccount);
				}
				else
				{
					$sError = PROC_CANT_INS_NEW_CONTS;
					switch ($oApiContactsManager->GetLastErrorCode())
					{
						// TODO
//						case Errs::UserManager_AccountCreateUserLimitReached:
//							$sError = PROC_CANT_INS_NEW_CONTS;
//							break;
					}
					$this->SetErrorResponse($sError);
				}
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_UPDATE_CONT);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoUpdateFilters()
	{
		$oFiltersNode = $this->oRequestXml->XmlRoot->GetChildNodeByTagName('filters');
		$iAccountId = (int) $oFiltersNode->GetAttribute('id_acct', -1);

		$oMailProcessor = null;
		$oAccount = $this->getAccount($iAccountId);
		if ($oAccount)
		{
			$bSuccess = true;
			if (USE_DB)
			{
				$oMailProcessor = new MailProcessor($oAccount);
				for ($mKey = count($oFiltersNode->Children) - 1; $mKey >= 0; $mKey--)
				{
					$oFilterNode =& $oFiltersNode->Children[$mKey];
					if (isset($oFilterNode->Attributes['status']))
					{
						$sStatus = $oFilterNode->Attributes['status'];
						switch ($sStatus)
						{
							case 'new':
								$oFilter = CAppXmlHelper::GetFilter($oFilterNode);
								if ($oFilter)
								{
									$oFilter->IdAcct = $oAccount->IdAccount;
									$bSuccess &= $oMailProcessor->DbStorage->InsertFilter($oFilter);
								}
								else
								{
									$bSuccess = false;
								}
								break;

							case 'removed':
								if (isset($oFilterNode->Attributes['id']))
								{
									$iFilterId = (int) $oFilterNode->Attributes['id'];
									$bSuccess &= $oMailProcessor->DbStorage->DeleteFilter($iFilterId, $oAccount->IdAccount);
								}
								else
								{
									$bSuccess = false;
								}
								break;

							case 'updated':
								$oFilter = CAppXmlHelper::GetFilter($oFilterNode);
								if ($oFilter)
								{
									$oFilter->IdAcct = $oAccount->IdAccount;
									$bSuccess &= $oMailProcessor->DbStorage->UpdateFilter($oFilter);
								}
								else
								{
									$bSuccess = false;
								}
								break;
							case 'unchanged':
								$oFilter = CAppXmlHelper::GetFilter($oFilterNode);
								if ($oFilter)
								{
									$oFilter->IdAcct = $oAccount->IdAccount;
									$bSuccess &= true;
								}
								else
								{
									$bSuccess = false;
								}
								break;
						}
					}
				}
				CApi::Plugin()->RunHook('update-sieve-filters', array(&$oAccount, &$oFiltersNode, &$bSuccess));
			}

			if ($bSuccess)
			{
				$oFilters = null;
				if (USE_DB)
				{
					$oFilters =& $oMailProcessor->DbStorage->SelectFilters($oAccount->IdAccount);
				}
				
				CApi::Plugin()->RunHook('get-sieve-filters', array(&$oAccount, &$oFilters));

				if ($oFilters)
				{
					CAppXmlBuilder::BuildFilterList($this->oResultXml, $oFilters, $oAccount->IdAccount);
				}
				else
				{
					$this->SetErrorResponse(PROC_CANT_GET_FILTER_LIST);
				}
			}
			else
			{
				$this->SetErrorResponse(ErrorCantUpdateFilters);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoUpdateAccount()
	{
		$oAccountNode = $this->oRequestXml->XmlRoot->GetChildNodeByTagName('account');
		$iAccountId = (int) $oAccountNode->GetAttribute('id', -1);
		
		$oAccount = $this->getAccount($iAccountId);
		if ($oAccount)
		{
			$sOldPass = (string) $oAccount->IncomingMailPassword;
			
			$oDomain = $this->getDefaultAccountDomain($oAccount);
			if ($oDomain)
			{
				CAppXmlHelper::PopulateAccount($oAccountNode, $oAccount, $oDomain, true);

				$sCurrentPassword = (string) trim($oAccountNode->GetChildValueByTagName('cur_pass'));
				if (!empty($sCurrentPassword) && $sCurrentPassword !== $sOldPass)
				{
					$this->SetErrorResponse(AccountOldPasswordNotCorrect);
				}
				else
				{
					if ($this->oUsersApi->UpdateAccount($oAccount))
					{
						$this->SetUpdateResponse('account');
					}
					else
					{
						$sError = PROC_CANT_UPDATE_ACCT;
						switch ($this->oUsersApi->GetLastErrorCode())
						{
							case Errs::UserManager_AccountOldPasswordNotCorrect:
								$sError = AccountOldPasswordNotCorrect;
								break;
							case Errs::UserManager_AccountNewPasswordUpdateError:
								$sError = AccountNewPasswordUpdateError;
								break;
							case Errs::UserManager_AccountNewPasswordRejected:
								$sError = AccountNewPasswordRejected;
								break;
						}

						$this->SetErrorResponse($sError);
					}
				}
			}
			else
			{
				$this->SetErrorResponse(WebMailException);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoUpdateSettings()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$oSettingsNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('settings');

			$iMailsPerPage = (int) $oSettingsNode->GetAttribute('msgs_per_page', $oAccount->User->MailsPerPage);
			if ($iMailsPerPage < 1)
			{
				$iMailsPerPage = 1;
			}

			$iContactsPerPage = (int) $oSettingsNode->GetAttribute('contacts_per_page', $oAccount->User->ContactsPerPage);
			if ($iContactsPerPage < 1)
			{
				$iContactsPerPage = 1;
			}

			$iAutoCheckMailInterval = (int) $oSettingsNode->GetAttribute('auto_checkmail_interval', $oAccount->User->AutoCheckMailInterval);
			if (!in_array($iAutoCheckMailInterval, array(0, 1, 3, 5, 10, 15, 20, 30)))
			{
				$iAutoCheckMailInterval = 0;
			}

			$iDefaultTimeZone = $oSettingsNode->GetAttribute('def_timezone', $oAccount->User->DefaultTimeZone);
			$iLayout = (int) $oSettingsNode->GetAttribute('layout', $oAccount->User->Layout);
			$sDefaultSkin = $oSettingsNode->GetChildValueByTagName('def_skin');
			if (empty($sDefaultSkin))
			{
				$sDefaultSkin = $oAccount->User->DefaultSkin;
			}

			$iDefaultEditor = (int) $oSettingsNode->GetAttribute('def_editor', $oAccount->User->DefaultEditor);

			$sDefaultLanguage = $oSettingsNode->GetChildValueByTagName('def_lang');
			if (empty($sDefaultLanguage))
			{
				$sDefaultLanguage = $oAccount->User->DefaultLanguage;
			}

			$this->oInput->SetSession(APP_SESSION_LANG, $sDefaultLanguage);
			@setcookie('awm_defLang', $sDefaultLanguage, time() + 31104000);

			$sDateFormat = $oSettingsNode->GetChildValueByTagName('def_date_fmt');
			if (empty($sDateFormat))
			{
				$sDateFormat = $oAccount->User->DefaultDateFormat;
			}

			$sTimeFormat = $oSettingsNode->GetAttribute('time_format', $oAccount->User->DefaultTimeFormat);

			$oAccount->User->MailsPerPage = $iMailsPerPage;
			$oAccount->User->ContactsPerPage = $iContactsPerPage;
			$oAccount->User->DefaultTimeZone = $iDefaultTimeZone;
			$oAccount->User->Layout = $iLayout;
			$oAccount->User->DefaultSkin = $sDefaultSkin;
			$oAccount->User->DefaultEditor = $iDefaultEditor;
			$oAccount->User->DefaultLanguage = $sDefaultLanguage;
			$oAccount->User->DefaultDateFormat = $sDateFormat;
			$oAccount->User->DefaultTimeFormat = $sTimeFormat;
			$oAccount->User->AutoCheckMailInterval = $iAutoCheckMailInterval;

			if ($this->oUsersApi->UpdateAccount($oAccount))
			{
				$this->SetUpdateResponse('settings');
			}
			else
			{
				$this->SetErrorResponse(PROC_ERROR_ACCT_UPDATE);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoUpdateMobileSync()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$oSettings =& CApi::GetSettings();
			$oMobileSyncNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('mobile_sync');
			$oAccount->User->EnableMobileSync = 
				(bool) $oMobileSyncNode->GetAttribute('enable_account', $oAccount->User->EnableMobileSync);
				
			if ($oSettings->GetConf('Common/EnableMobileSync') && $this->oUsersApi->UpdateAccount($oAccount))
			{
				$this->SetUpdateResponse('mobile_sync');
			}
			else
			{
				$this->SetErrorResponse(PROC_ERROR_ACCT_UPDATE);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoUpdateIdAcct()
	{
		$iAcctountId = (int) $this->oRequestXml->GetParamValueByName('id_acct');
		$oAccount = $this->getAccount($iAcctountId);
		if ($oAccount)
		{
			$this->oInput->SetSession(APP_SESSION_ACCOUNT_ID, $iAcctountId);
			$this->SetUpdateResponse('id_acct');
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	function DoUpdateDefOrder()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$sDefOrder = $this->oRequestXml->GetParamValueByName('def_order');
			if (0 < strlen($sDefOrder) && (int) $sDefOrder !== $oAccount->DefaultOrder)
			{
				$oAccount->DefaultOrder = (int) $sDefOrder;
				$this->oUsersApi->UpdateAccount($oAccount);
			}
			
			$this->SetUpdateResponse('def_order');
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
// DELETE
	
	public function DoDeleteFolders()
	{
		$iAccountId = (int) $this->oRequestXml->GetParamValueByName('id_acct');
		$oAccount = $this->getAccount($iAccountId);
		if ($oAccount)
		{
			$oMailProcessor = new MailProcessor($oAccount);

			$oFoldersNode = null;
			$oFoldersNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('folders');

			$mKey = null;
			$iResult = 1;
			$aFoldersKeys = array_keys($oFoldersNode->Children);
			foreach ($aFoldersKeys as $mKey)
			{
				$oFolder = null;
				$oFolderNode =& $oFoldersNode->Children[$mKey];
				if (isset($oFolderNode->Attributes['id']))
				{
					$oFolder = new Folder($oAccount->IdAccount,
						$oFolderNode->Attributes['id'], $oFolderNode->GetChildValueByTagName('full_name'));
				}
				else
				{
					$iResult = 0;
					unset($oFolderNode);
					break;
				}

				$oMailProcessor->GetFolderInfo($oFolder);
				$oMailProcessor->GetFolderMessageCount($oFolder);

				$iChildCount = (USE_DB) ? $oMailProcessor->DbStorage->GetFolderChildCount($oFolder) : 0;

				if (EMailProtocol::IMAP4 === $oAccount->IncomingMailProtocol &&
					($oFolder->MessageCount > 0 || $iChildCount > 0))
				{
					$iResult = 0;
				}
				else
				{
					$iResult &= $oMailProcessor->DeleteFolder($oFolder);
				}
				
				unset($oFolderNode, $oFolder);
			}

			if ($iResult)
			{
				$oFolders = null;
				$oFolders =& $oMailProcessor->GetFolders();
				CAppXmlBuilder::BuildFolders($this->oResultXml, $oFolders, $oAccount, $oMailProcessor);
			}
			else
			{
				$this->SetErrorResponse(PROC_ERROR_DEL_FLD);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoDeleteContacts()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			if ($oAccount->User->AllowContacts)
			{
				/* @var $oApiContactsManager CApiContactsManager */
				$oApiContactsManager = CApi::Manager('contacts');
				
				$bResult = true;

				$aContactsIds = array();
				$oContactsNode = null;
				$oContactsNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('contacts');
				$aContactsKeys = array_keys($oContactsNode->Children);
				foreach ($aContactsKeys as $mKey)
				{
					$oContactNode =& $oContactsNode->Children[$mKey];
					if (isset($oContactNode->Attributes['id']))
					{
						$aContactsIds[] = $oContactNode->Attributes['id'];
					}
					else
					{
						$bResult = false;
					}
					unset($oContactNode);
				}
				unset($oContactsNode, $aContactsKeys);

				if (0 < count($aContactsIds))
				{
					$bResult = $oApiContactsManager->DeleteContacts($oAccount->IdUser, $aContactsIds);
					if ($bResult)
					{
						CApi::LogEvent('User delete PAB (contact ids="'.(implode(', ', $aContactsIds)).'")', $oAccount);
					}
				}

				$aGroupsIds = array();
				$oGroupsNode = null;
				$oGroupsNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('groups');
				$aGroupsKeys = array_keys($oGroupsNode->Children);
				foreach ($aGroupsKeys as $mKey)
				{
					$oGroupNode =& $oGroupsNode->Children[$mKey];
					if (isset($oGroupNode->Attributes['id']))
					{
						$aGroupsIds[] = $oGroupNode->Attributes['id'];
					}
					else
					{
						$bResult = false;
					}
					unset($oGroupNode);
				}
				unset($oGroupsNode, $aGroupsKeys);

				if (0 < count($aGroupsIds))
				{
					$bResult = $oApiContactsManager->DeleteGroups($oAccount->IdUser, $aGroupsIds);
					if ($bResult)
					{
						CApi::LogEvent('User delete PAB (group id="'.(implode(', ', $aGroupsIds)).'")', $oAccount);
					}
				}

				if ($bResult)
				{
					CAppXmlBuilder::BuildContactList($this->oRequestXml, $this->oResultXml, $oAccount, $oApiContactsManager);
				}
				else
				{
					$this->SetErrorResponse(PROC_CANT_DEL_CONT_GROUPS);
				}
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_DEL_CONT_GROUPS);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoDeleteAccount()
	{
		$iAccountId = (int) $this->oRequestXml->GetParamValueByName('id_acct');
		$oAccount = $this->getAccount($iAccountId);
		if ($oAccount)
		{
			$oDomain = $this->getDefaultAccountDomain($oAccount);
			if (!$oDomain)
			{
				$this->SetErrorResponse(WebMailException);
			}
			else if ($oDomain->AllowUsersChangeEmailSettings &&
				!$oAccount->IsEnabledExtension(CAccount::DisableAccountDeletion) &&
				!$oAccount->IsInternal)
			{
				if ($this->oUsersApi->DeleteAccountById($oAccount->IdAccount))
				{
					$iCurrentId = -1;
					$oListAccount = null;
					$aAccounts = $this->getAccounts();
					if (0 < count($aAccounts) && !$oAccount->IsDefaultAccount)
					{
						foreach ($aAccounts as /* @var $oListAccount CAccount */ $oListAccount)
						{
							if ($oListAccount->IsDefaultAccount)
							{
								$iCurrentId = $oListAccount->IdAccount;
							}
						}
					}

					if (0 < $iCurrentId)
					{
						$this->oInput->SetSession(APP_SESSION_ACCOUNT_ID, $iCurrentId);
					}

					CAppXmlBuilder::BuildAccountList($this->oResultXml, $aAccounts, $iCurrentId);
				}
				else
				{
					$this->SetErrorResponse(PROC_CANT_DEL_ACCT_BY_ID);
				}
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_DEL_ACCT_BY_ID);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	protected function actionIdentity($sActionType)
	{
		$oIdentityNode = $this->oRequestXml->XmlRoot->GetChildNodeByTagName('identity');

		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$bSuccess = true;
			
			/* @var $oApiUsersManager CApiUsersManager */
			$oApiUsersManager = CApi::Manager('users');

			$oIdentity = CAppXmlHelper::GetIdentity($oIdentityNode, 'new' === $sActionType);
			if ($oIdentity)
			{
				$oIdentity->IdUser = $oAccount->IdUser;
				if ('update' === $sActionType)
				{
					$bSuccess &= $oApiUsersManager->UpdateIdentity($oIdentity);
				}
				else if ('new' === $sActionType)
				{
					$bSuccess &= $oApiUsersManager->CreateIdentity($oIdentity);
				}
				else
				{
					$bSuccess = false;
				}
			}
			else
			{
				$bSuccess = false;
			}

			if ($bSuccess)
			{
				$aAccounts = $this->getAccounts();
				CAppXmlBuilder::BuildIdentities($this->oResultXml, $aAccounts);
			}
			else
			{
				$sError = WebMailException;
				if ('update' === $sActionType)
				{
					$sError = CantUpdateIdentity;
				}
				else if ('new' === $sActionType)
				{
					$sError = CantCreateIdentity;
				}
				
				$this->SetErrorResponse($sError);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

	public function DoNewIdentity()
	{
		$this->actionIdentity('new');
	}
	
	public function DoUpdateIdentity()
	{
		$this->actionIdentity('update');
	}

	public function DoDeleteIdentity()
	{
		$bSuccess = false;
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			/* @var $oApiUsersManager CApiUsersManager */
			$oApiUsersManager = CApi::Manager('users');

			$iIdentityId = (int) $this->oRequestXml->GetParamValueByName('id');
			$oIdentity = $oApiUsersManager->GetIdentity($iIdentityId);
			if ($oIdentity && $oIdentity->IdUser === $oAccount->IdUser)
			{
				$bSuccess = $oApiUsersManager->DeleteIdentity($iIdentityId);
			}

			if ($bSuccess)
			{
				$aAccounts = $this->getAccounts($oAccount);
				CAppXmlBuilder::BuildIdentities($this->oResultXml, $aAccounts);
			}
			else
			{
				$this->SetErrorResponse(CantDeleteIdentity);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
// OTHER
	
	function DoSendMessage()
	{
		$oAccount = $this->getAccount();
		$oSettings =& CApi::GetSettings();
		if ($oAccount)
		{
			$oMessageNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('message');
			
			$oFromAccount = $oFromIdentity = false;
			$iFromAccountId = (int) $oMessageNode->GetAttribute('from_acct_id', -1);
			$iFromIdentityId = (int) $oMessageNode->GetAttribute('from_identity_id', -1);
			if (0 < $iFromIdentityId)
			{
				/* @var $oApiUsersManager CApiUsersManager */
				$oApiUsersManager = CApi::Manager('users');
				$oFromIdentity = /* @var $oFromIdentity CIdentity */ $oApiUsersManager->GetIdentity($iFromIdentityId);
				if ($oAccount->IdUser === $oFromIdentity->IdUser)
				{
					if ($oFromIdentity->IdAccount === $oAccount->IdAccount)
					{
						$oFromAccount = $oAccount;
					}
					else
					{
						$oFromAccount = $this->getAccount($oFromIdentity->IdAccount);
						if (!$oFromAccount)
						{
							$oFromAccount = false;
						}
					}
				}
				else
				{
					$oFromIdentity = false;
				}
			}
			else if (0 < $iFromAccountId)
			{
				if ($iFromAccountId === $oAccount->IdAccount)
				{
					$oFromAccount = $oAccount;
				}
				else
				{
					$oFromAccount = $this->getAccount($iFromAccountId);
					if (!$oFromAccount)
					{
						$oFromAccount = false;
					}
				}
			}
			
			if (false !== $oFromAccount)
			{
				$oMessage = CAppXmlHelper::GetMessage($oMessageNode, $oAccount, $oFromAccount, $oFromIdentity);
				
				CApi::Plugin()->RunHook('webmail-change-message-before-send', array(&$oMessage, &$oFromAccount));

				$oMailProcessor = new MailProcessor($oFromAccount);
				$oFolders =& $oMailProcessor->GetFolders();
				$oSentFolder =& $oFolders->GetFolderByType(FOLDERTYPE_SentItems);

				$oMessage->OriginalMailMessage = $oMessage->ToMailString(true);
				$oMessage->Flags |= MESSAGEFLAGS_Seen;

				$bResult = true;
				$bNeedToDelete = ($oMessage->IdMsg != -1);
				$iIdToDelete = $oMessage->IdMsg;

				$oFromAccount = null === $oFromAccount ? $oAccount : $oFromAccount;
				
				$oAttachmentsNode = null;
				if (CSmtp::SendMail($oFromAccount, $oMessage, null, null))
				{
					$oAttachmentsNode =& $oMessageNode->GetChildNodeByTagName('attachments');
					$bSaveInSent = 
						ESaveMail::Always === $oSettings->GetConf('WebMail/SaveMail') ||
						(isset($oMessageNode->Attributes['save_mail']) && 1 === (int) $oMessageNode->Attributes['save_mail']);
					
					$oDraftsFolder = null;
					if ($bNeedToDelete)
					{
						$oDraftsFolder =& $oFolders->GetFolderByType(FOLDERTYPE_Drafts);
						if ($oDraftsFolder)
						{
							if (!$oMailProcessor->SaveMessage($oMessage, $oSentFolder, $oDraftsFolder, !$bSaveInSent))
							{
								$bNeedToDelete = false;
							}
						}
					}
					else
					{
						if ($bSaveInSent)
						{
							if (!$oMailProcessor->SaveMessage($oMessage, $oSentFolder))
							{
								$bNeedToDelete = false;
							}
						}
					}

					if (ESaveMail::Always !== $oSettings->GetConf('WebMail/SaveMail'))
					{
						if (($bSaveInSent ? ESaveMail::DefaultOff : ESaveMail::DefaultOn) === $oAccount->User->SaveMail)
						{
							$oAccount->User->SaveMail = $bSaveInSent ? ESaveMail::DefaultOn : ESaveMail::DefaultOff;
							$this->oUsersApi->UpdateAccount($oAccount);
						}
					}

					$oHeadersNode =& $oMessageNode->GetChildNodeByTagName('headers');
					$oGroupNode =& $oHeadersNode->GetChildNodeByTagName('groups');
					$oToNode =& $oHeadersNode->GetChildNodeByTagName('to');
					$oCcNode =& $oHeadersNode->GetChildNodeByTagName('cc');
					$oBccNode =& $oHeadersNode->GetChildNodeByTagName('bcc');
					
					$sEmailsString = '';
					$aGroupIds = array();

					if ($oGroupNode != null && $oGroupNode->Value != null)
					{
						if (count($oGroupNode->Children) > 0)
						{
							$sKey = null;
							$aGroupKeys = array_keys($oGroupNode->Children);
							foreach ($aGroupKeys as $sKey)
							{
								$oListFroupNode =& $oGroupNode->Children[$sKey];
								$aGroupIds[] = isset($oListFroupNode->Attributes['id']) ? (int) $oListFroupNode->Attributes['id'] : -1;
								unset($oListFroupNode);
							}
						}
					}

					if ($oToNode != null && $oToNode->Value != null)
					{
						$sEmailsString .= ConvertUtils::WMBackHtmlSpecialChars($oToNode->Value) . ', ';
					}

					if ($oCcNode != null && $oCcNode->Value != null)
					{
						$sEmailsString .= ConvertUtils::WMBackHtmlSpecialChars($oCcNode->Value) . ', ';
					}

					if ($oBccNode != null && $oBccNode->Value != null)
					{
						$sEmailsString .= ConvertUtils::WMBackHtmlSpecialChars($oBccNode->Value);
					}

					$sEmailsString = trim(trim($sEmailsString), ',');

					$oEmailsCollection = new EmailAddressCollection($sEmailsString);

					$aEmails = array();
					for($iIndex = 0, $iCount = $oEmailsCollection->Count(); $iIndex < $iCount; $iIndex++)
					{
						$oEmail =& $oEmailsCollection->Get($iIndex);
						if ($oEmail && trim($oEmail->Email))
						{
							$aEmails[strtolower($oEmail->Email)] = trim($oEmail->DisplayName);
						}
					}

					CAppXmlHelper::ReplySetFlag($oMessageNode, $oMailProcessor);

					/* @var $oApiContactsManager CApiContactsManager */
					$oApiContactsManager = CApi::Manager('contacts');
					$oApiContactsManager->UpdateSuggestTable($oAccount->IdUser, $aEmails);

					if ($bNeedToDelete && $oDraftsFolder)
					{
						$aMessageIdSet = array($iIdToDelete);
						if (EMailProtocol::IMAP4 === $oFromAccount->IncomingMailProtocol)
						{
							if ($oMailProcessor->PurgeFolder($oDraftsFolder) && USE_DB)
							{
								$oMailProcessor->DbStorage->DeleteMessages($aMessageIdSet, false, $oDraftsFolder);
							}
						}
						else if (USE_DB)
						{
							$oMailProcessor->DbStorage->DeleteMessages($aMessageIdSet, false, $oDraftsFolder);
						}
					}

					if (USE_DB)
					{
						$oMailProcessor->DbStorage->UpdateMailboxSize();
					}

					$bResult = true;
				}
				else
				{
					$bResult = false;
				}

				if ($bResult)
				{
					$sRecipients = $oMessage->GetAllRecipientsEmailsAsString();
					
					$oReplyNode =& $oMessageNode->GetChildNodeByTagName('reply_message');
					if ($oReplyNode && isset($oReplyNode->Attributes['action']))
					{
						switch ($oReplyNode->Attributes['action'])
						{
							case 'reply':
								// TODO: statistics ContentID
								CApi::Plugin()->RunHook('statistics.message-replied', array(&$oAccount, array('RECIPIENT' => $sRecipients, 'CONTENT_ID' => '' )));

								break;
							case 'forward':
								// TODO: statistics ContentID
								CApi::Plugin()->RunHook('statistics.message-forwarded', array(&$oAccount, array('RECIPIENT' => $sRecipients, 'CONTENT_ID' => '')));
								break;
						}
					}					
					else
					{
						// TODO: statistics ContentID
						CApi::Plugin()->RunHook('statistics.message-sent', array(&$oAccount, array('RECIPIENT' => $sRecipients, 'CONTENT_ID' => '')));
					}
					
					$this->SetUpdateResponse('send_message');

					$aQuotes = $this->getAccountQuota($oFromAccount, $oMailProcessor);
					CAppXmlBuilder::BuildAccountImapQuotaNode($this->oResultXml, $oFromAccount->IdAccount, $aQuotes[0], $aQuotes[1]);
				}
				else
				{
					$sGlobalError = getGlobalError();
					if ($oFromAccount && 0 === strpos($sGlobalError, 'For security reasons'))
					{
						$this->SetErrorResponse($sGlobalError);
					}
					else
					{
						$this->SetErrorResponse(PROC_CANT_SEND_MSG);
					}
				}
			}
			else
			{
				$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	public function DoSaveMessage()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$oMessageNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('message');
			
			$oFromAccount = false;
			$iFromAccountId = (int) $oMessageNode->GetAttribute('from_acct_id', -1);
			$iFromIdentityId = (int) $oMessageNode->GetAttribute('from_identity_id', -1);
			if (0 < $iFromIdentityId)
			{
				/* @var $oApiUsersManager CApiUsersManager */
				$oApiUsersManager = CApi::Manager('users');
				$oFromIdentity = /* @var $oFromIdentity CIdentity */ $oApiUsersManager->GetIdentity($iFromIdentityId);
				if ($oAccount->IdUser === $oFromIdentity->IdUser)
				{
					if ($oFromIdentity->IdAccount === $oAccount->IdAccount)
					{
						$oFromAccount = $oAccount;
					}
					else
					{
						$oFromAccount = $this->getAccount($oFromIdentity->IdAccount);
						if (!$oFromAccount)
						{
							$oFromAccount = false;
						}
					}
				}
				else
				{
					$oFromIdentity = false;
				}
			}
			else if (0 < $iFromAccountId)
			{
				if ($iFromAccountId === $oAccount->IdAccount)
				{
					$oFromAccount = $oAccount;
				}
				else
				{
					$oFromAccount = $this->getAccount($iFromAccountId);
					if (!$oFromAccount)
					{
						$oFromAccount = false;
					}
				}
			}
			
			if (false !== $oFromAccount)
			{
				$oFromAccount = null === $oFromAccount ? $oAccount : $oFromAccount;

				$oMessage = CAppXmlHelper::GetMessage($oMessageNode, $oAccount, $oFromAccount);
			
				/* suggestion */
				$oHeaderNode =& $oMessageNode->GetChildNodeByTagName('headers');
				$oGroupsNode =& $oHeaderNode->GetChildNodeByTagName('groups');
				
				$aGroupsIds = array();
				if ($oGroupsNode)
				{
					$mKey = null;
					$aGroupsKeys = array_keys($oGroupsNode->Children);
					foreach ($aGroupsKeys as $mKey)
					{
						$oListGroupNode =& $oGroupsNode->Children[$mKey];
						$aGroupsIds[] = isset($oListGroupNode->Attributes['id']) ? (int) $oListGroupNode->Attributes['id'] : -1;
						unset($oListGroupNode);
					}
				}

				$bResult = true;

				$oMailProcessor = new MailProcessor($oFromAccount);
				CAppXmlHelper::ReplySetFlag($oMessageNode, $oMailProcessor);

				$oFolders =& $oMailProcessor->GetFolders();
				$oDraftFolder =& $oFolders->GetFolderByType(FOLDERTYPE_Drafts);

				$oMessage->OriginalMailMessage = $oMessage->ToMailString();
				$oMessage->Flags |= MESSAGEFLAGS_Seen;

				$aMessageIdUidSet = array();
				$aMessageIdUidSet[$oMessage->IdMsg] = $oMessage->Uid;
				
				$aMessageIdSet = null;

				$bIsFromDrafts = ($oMessage->IdMsg != -1);

				if ($bIsFromDrafts)
				{
					$aMessageIdSet = array($oMessage->IdMsg);
				}

				if ($bResult)
				{
					$bResult = ($bIsFromDrafts)
						? $oMailProcessor->UpdateMessage($oMessage, $oDraftFolder)
						: $oMailProcessor->SaveMessage($oMessage, $oDraftFolder);

					$aMessageIdUidSet[$oMessage->IdMsg] = $oMessage->Uid;

					if ($bResult)
					{
						$oMailProcessor->SetFlags($aMessageIdUidSet, $oDraftFolder, MESSAGEFLAGS_Seen, ACTION_Set);
					}

					if (USE_DB)
					{
						$oMailProcessor->DbStorage->UpdateMailboxSize();
					}
				}
				else
				{
					$bResult = false;
				}

				if ($bResult)
				{
					if ($oMessage)
					{
						$this->SetUpdateResponse('save_message',
							array('id' => $oMessage->IdMsg), array('uid' => $oMessage->Uid));
					}
					else
					{
						$this->SetUpdateResponse('save_message');
					}

					$aQuotes = $this->getAccountQuota($oFromAccount, $oMailProcessor);
					CAppXmlBuilder::BuildAccountImapQuotaNode($this->oResultXml, $oFromAccount->IdAccount, $aQuotes[0], $aQuotes[1]);
				}
				else
				{
					$this->SetErrorResponse(PROC_CANT_SAVE_MSG);
				}
			}
			else
			{
				$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	function DoSendConfirmation()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$oMessage = CAppXmlHelper::GetConfirmationMessage($this->oRequestXml, $this->oResultXml,  $oAccount);
			
			$bResult = false;
			if ($oMessage)
			{
				$oMessage->OriginalMailMessage = $oMessage->ToMailString(true);
				$oMessage->Flags |= MESSAGEFLAGS_Seen;
				$bResult = CSmtp::SendMail($oAccount, $oMessage, null, null);
			}

			if ($bResult)
			{
				$this->SetUpdateResponse('send_confirmation');
			}
			else
			{
				$this->SetErrorResponse(PROC_CANT_SEND_MSG);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}
	
	function DoOperationMessagesFunction()
	{
		$oAccount = $this->getAccount();
		if ($oAccount)
		{
			$oMessagesNode =& $this->oRequestXml->XmlRoot->GetChildNodeByTagName('messages');
			if ($oMessagesNode)
			{
				$oMailProcessor = new MailProcessor($oAccount);
				
				$bGetMsg = (isset($oMessagesNode->Attributes['getmsg']) && $oMessagesNode->Attributes['getmsg'] == '1');
				
				$oFolderNode =& $oMessagesNode->GetChildNodeByTagName('folder');
				$oFolderToNode =& $oMessagesNode->GetChildNodeByTagName('to_folder');
				
				if ($oFolderNode && $oFolderToNode &&
					isset($oFolderNode->Attributes['id'], $oFolderToNode->Attributes['id']))
				{
					$oFolder = new Folder($oAccount->IdAccount, $oFolderNode->Attributes['id'],
						ConvertUtils::WMBackHtmlSpecialChars($oFolderNode->GetChildValueByTagName('full_name')));

					$oMailProcessor->GetFolderInfo($oFolder, true);

					$oFolderTo = new Folder($oAccount->IdAccount, $oFolderToNode->Attributes['id'],
						ConvertUtils::WMBackHtmlSpecialChars($oFolderToNode->GetChildValueByTagName('full_name')));

					$oMailProcessor->GetFolderInfo($oFolderTo, true);
					
					$oResultOperationNode = new CXmlDomNode('operation_messages');

					$oResultFolderToNode = new CXmlDomNode('to_folder', $oFolderTo->FullName, true);
					$oResultFolderToNode->AppendAttribute('id', $oFolderTo->IdDb);
					$oResultOperationNode->AppendChild($oResultFolderToNode);

					$oResultFolderNode = new CXmlDomNode('folder', $oFolder->FullName, true);
					$oResultFolderNode->AppendAttribute('id', $oFolder->IdDb);
					$oResultOperationNode->AppendChild($oResultFolderNode);

					$oResultMessagesNode = new CXmlDomNode('messages');
					$oResultMessagesNode->AppendAttribute('getmsg', $bGetMsg ? '1' : '0');

					$aMessageIdUidSet = array();

					$aFolders = array();

					$mNodeKey = null;
					$aMessagesKeys = array_keys($oMessagesNode->Children);
					foreach ($aMessagesKeys as $mNodeKey)
					{
						$oMessageNode =& $oMessagesNode->Children[$mNodeKey];

						if ('message' !== (string) $oMessageNode->TagName)
						{
							continue;
						}

						if (!isset($oMessageNode->Attributes['id'], $oMessageNode->Attributes['charset'], $oMessageNode->Attributes['size']))
						{
							continue;
						}

						$iMsgId = $oMessageNode->Attributes['id'];
						$iMsgCharset = $oMessageNode->Attributes['charset'];
						$iMsgSize = $oMessageNode->Attributes['size'];
						$mMsgUid = $oMessageNode->GetChildValueByTagName('uid', true);

						$oMsgFolderNode =& $oMessageNode->GetChildNodeByTagName('folder');
						if (!isset($oMsgFolderNode->Attributes['id']))
						{
							continue;
						}

						$mMsgFolderId = $oMsgFolderNode->Attributes['id'];
						$aFolders[$mMsgFolderId] = $oMsgFolderNode->GetChildValueByTagName('full_name', true);

						if (!isset($aMessageIdUidSet[$mMsgFolderId]))
						{
							$aMessageIdUidSet[$mMsgFolderId] = array();
						}
						$aMessageIdUidSet[$mMsgFolderId][$iMsgId] = $mMsgUid;

						$oResultMessageNode = new CXmlDomNode('message');
						$oResultMessageNode->AppendAttribute('id', $iMsgId);
						$oResultMessageNode->AppendAttribute('charset', $iMsgCharset);
						$oResultMessageNode->AppendAttribute('size', $iMsgSize);
						$oResultMessageNode->AppendChild(new CXmlDomNode('uid', $mMsgUid, true));

						$oResultMessageFolderNode = new CXmlDomNode('folder', $aFolders[$mMsgFolderId], true);
						$oResultMessageFolderNode->AppendAttribute('id', $mMsgFolderId);

						$oResultMessageNode->AppendChild($oResultMessageFolderNode);

						$oResultMessagesNode->AppendChild($oResultMessageNode);

						unset($oMessageNode, $oMsgFolderNode, $oResultMessageNode, $oResultMessageFolderNode);
					}

					$oResultOperationNode->AppendChild($oResultMessagesNode);

					$sError = $sRequestType = '';
					$sRequest = $this->oRequestXml->GetParamValueByName('request');
					
					switch ($sRequest)
					{
						case 'mark_all_read':
							$aMessageIdUidSet = null;
							if ($oMailProcessor->SetFlags($aMessageIdUidSet, $oFolder, MESSAGEFLAGS_Seen, ACTION_Set))
							{
								$sRequestType = 'mark_all_read';
							}
							else
							{
								$sError = PROC_CANT_MARK_ALL_MSG_READ;
							}
							break;
						case 'mark_all_unread':
							$aMessageIdUidSet = null;
							if ($oMailProcessor->SetFlags($aMessageIdUidSet, $oFolder, MESSAGEFLAGS_Seen, ACTION_Remove))
							{
								$sRequestType = 'mark_all_unread';
							}
							else
							{
								$sError = PROC_CANT_MARK_ALL_MSG_UNREAD;
							}
							break;
						case 'purge':
							if ($oMailProcessor->EmptyTrash())
							{
								CApi::LogEvent('User emptied trash', $oAccount);
								$sRequestType = 'purge';
							}
							else
							{
								$sError = PROC_CANT_PURGE_MSGS;
							}
							break;
						case 'clear_spam':
							if ($oMailProcessor->EmptySpam())
							{
								CApi::LogEvent('User cleared spam', $oAccount);
								$sRequestType = 'clear_spam';
							}
							else
							{
								$sError = PROC_CANT_PURGE_MSGS;
							}
							break;
					}

					$bIsDeleteFolderAppendError = false;
					$aFoldersArray = array();
					foreach ($aFolders as $iFolderId => $sFolderFullName)
					{
						if (isset($aFoldersArray[$iFolderId]))
						{
							$oFolder =& $aFoldersArray[$iFolderId];
						}
						else
						{
							$oFolder = new Folder($oAccount->IdAccount, $iFolderId, $sFolderFullName);
							$oMailProcessor->GetFolderInfo($oFolder, true);
							$aFoldersArray[$iFolderId] =& $oFolder;
						}

						switch ($sRequest)
						{
							case 'no_move_delete':
								if ($oMailProcessor->DeleteMessages($aMessageIdUidSet[$iFolderId], $oFolder, true))
								{
									CApi::LogEvent('User delete message', $oAccount);
									$sRequestType = 'no_move_delete';
								}
								else
								{
									$sError = PROC_CANT_DEL_MSGS;
								}
								break;
							case 'delete':
								if ($oMailProcessor->DeleteMessages($aMessageIdUidSet[$iFolderId], $oFolder))
								{
									CApi::LogEvent('User delete message', $oAccount);
									$sRequestType = 'delete';
								}
								else
								{
									if ($oMailProcessor->IsMoveError)
									{
										$sRequestType = 'delete';
										$bIsDeleteFolderAppendError = true;
									}
									$sError = PROC_CANT_DEL_MSGS;
								}
								break;
							case 'undelete':
								if ($oMailProcessor->SetFlags($aMessageIdUidSet[$iFolderId], $oFolder, MESSAGEFLAGS_Deleted, ACTION_Remove))
								{
									$sRequestType = 'undelete';
								}
								else
								{
									$sError = PROC_CANT_UNDEL_MSGS;
								}
								break;
							case 'mark_read':
								if ($oMailProcessor->SetFlags($aMessageIdUidSet[$iFolderId], $oFolder, MESSAGEFLAGS_Seen, ACTION_Set))
								{
									CApi::Plugin()->RunHook('statistics.message-received', array(&$oAccount, null));
									$sRequestType = 'mark_read';
								}
								else
								{
									$sError = PROC_CANT_MARK_MSGS_READ;
								}
								break;
							case 'mark_unread':
								if ($oMailProcessor->SetFlags($aMessageIdUidSet[$iFolderId], $oFolder, MESSAGEFLAGS_Seen, ACTION_Remove))
								{
									$sRequestType = 'mark_unread';
								}
								else
								{
									$sError = PROC_CANT_MARK_MSGS_UNREAD;
								}
								break;
							case 'flag':
								if ($oMailProcessor->SetFlags($aMessageIdUidSet[$iFolderId], $oFolder, MESSAGEFLAGS_Flagged, ACTION_Set))
								{
									$sRequestType = 'flag';
								}
								else
								{
									$sError = PROC_CANT_SET_MSG_FLAGS;
								}
								break;
							case 'unflag':
								if ($oMailProcessor->SetFlags($aMessageIdUidSet[$iFolderId], $oFolder, MESSAGEFLAGS_Flagged, ACTION_Remove))
								{
									$sRequestType = 'unflag';
								}
								else
								{
									$sError = PROC_CANT_REMOVE_MSG_FLAGS;
								}
								break;

							case 'move_to_folder':
								if ($oMailProcessor->MoveMessages($aMessageIdUidSet[$iFolderId], $oFolder, $oFolderTo))
								{
									$sRequestType = 'move_to_folder';
								}
								else
								{
									$sError = PROC_CANT_CHANGE_MSG_FLD;
								}
								break;

							case 'spam':
								if ($oAccount->IsEnabledExtension(CAccount::SpamLearningExtension) &&
									$oMailProcessor->SpamMessages($aMessageIdUidSet[$iFolderId], $oFolder, true))
								{
									$sRequestType = 'spam';
								}
								else
								{
									$sError = PROC_CANT_SET_MSG_AS_SPAM;
								}
								break;

							case 'not_spam':
								if ($oAccount->IsEnabledExtension(CAccount::SpamLearningExtension) &&
									$oMailProcessor->SpamMessages($aMessageIdUidSet[$iFolderId], $oFolder, false))
								{
									$sRequestType = 'not_spam';
								}
								else
								{
									$sError = PROC_CANT_SET_MSG_AS_NOTSPAM;
								}
								break;
						}

						unset($oFolder);

						if (strlen($sError) > 0)
						{
							break;
						}
					}

					if (empty($sError) && 0 < strlen($sRequestType))
					{
						$oResultOperationNode->AppendAttribute('type', $sRequestType);
						$this->oResultXml->XmlRoot->AppendChild($oResultOperationNode);

						if (in_array($sRequestType, array('undelete', 'delete', 'no_move_delete', 'clear_spam', 'purge')))
						{
							$aQuotes = $this->getAccountQuota($oAccount, $oMailProcessor);
							CAppXmlBuilder::BuildAccountImapQuotaNode($this->oResultXml, $oAccount->IdAccount, $aQuotes[0], $aQuotes[1]);
						}
					}
					else if ($bIsDeleteFolderAppendError)
					{
						$oResultOperationNode->AppendAttribute('type', $sRequestType);
						$oResultMessagesNode->AppendAttribute('no_move', '1');
						$this->oResultXml->XmlRoot->AppendChild($oResultOperationNode);
					}
					else
					{
						if (empty($sError))
						{
							$sError = WebMailException;
						}
						
						$this->oResultXml->XmlRoot->AppendChild(new CXmlDomNode('error', $sError, true));
					}
				}
				else
				{
					$this->SetErrorResponse(WebMailException);
				}
			}
			else
			{
				$this->SetErrorResponse(WebMailException);
			}
		}
		else
		{
			$this->SetErrorResponse(PROC_WRONG_ACCT_ACCESS);
		}
	}

// -------------------------------------------------------------------
	
	/**
	 * @param string $sAction
	 * @param string $sRequest
	 * @return void
	 */
	public function UseMethod($sAction, $sRequest)
	{
		$sName = $this->prepareMethodName($sAction, $sRequest);
		if ($this->methodExist($sName))
		{
			CApi::Log('CAppServer->'.$sName);
			CApi::Plugin()->RunHook('webmail-use-method-precall', array(&$this, &$sName));

			$bAllow = true;
			CApi::Plugin()->RunHook('webmail-use-method-disable', array(&$sName, &$bAllow));
			if ($bAllow)
			{
				call_user_func(array(&$this, $sName));
			}
			else
			{
				CApi::Log('CAppServer->'.$sName.' disabled');
			}
			
			CApi::Plugin()->RunHook('webmail-use-method-postcall', array(&$this, &$sName));
		}
		else if (CApi::Plugin()->XmlHookExist($sName))
		{
			CApi::Plugin()->RunXmlHook($this, $sName);
		}
		else
		{
			CApi::Log('Do[Error] : CAppServer->'.$sName.' not exist', ELogLevel::Error);
			$this->SetErrorResponse('CAppServer->'.$sName.' not exist');
		}
	}
	
	/**
	 * @param string $sError
	 * @param int $niCode = null
	 * @param array $naParams = null
	 */
	public function SetErrorResponse($sError, $niCode = null, $naParams = null)
	{
		$oErrorNote = new CXmlDomNode('error', $sError, true);
		if (null !== $niCode)
		{
			$oErrorNote->AppendAttribute('code', (int) $niCode);
		}

		if (is_array($naParams))
		{
			foreach ($naParams as $sKey => $sValue)
			{
				$oErrorNote->AppendAttribute($sKey, $sValue);
			}
		}

		$this->oResultXml->XmlRoot->AppendChild($oErrorNote);
	}

	/**
	 * @param string $sError
	 * @param int $niCode = null
	 * @return string
	 */
	public function GetErrorResponseAsString($sError, $niCode = null)
	{
		$oResultXml = new CXmlDocument();
		$oResultXml->CreateElement('webmail');

		$oErrorNote = new CXmlDomNode('error', $sError, true);
		if (null !== $niCode)
		{
			$oErrorNote->AppendAttribute('code', (int) $niCode);
		}

		$oResultXml->XmlRoot->AppendChild($oErrorNote);

		return $oResultXml->ToString();
	}
	
	/**
	 * @param string $sError
	 * @param array $aAttributs = array()
	 * @param array $aNodes = array()
	 */
	public function SetUpdateResponse($sUpdateDesc, $aAttributs = array(), $aNodes = array())
	{
		$oUpdateNode = new CXmlDomNode('update');
		$oUpdateNode->AppendAttribute('value', $sUpdateDesc);

		if (0 < count($aAttributs))
		{
			foreach ($aAttributs as $sName => $mValue)
			{
				$oUpdateNode->AppendAttribute($sName, $mValue);
			}
		}

		if (0 < count($aNodes))
		{
			foreach ($aNodes as $sName => $mValue)
			{
				$oUpdateSubNode = new CXmlDomNode($sName, $mValue, true);
				$oUpdateNode->AppendChild($oUpdateSubNode);
			}
		}
					
		$this->oResultXml->XmlRoot->AppendChild($oUpdateNode);
	}
	
	public function GetInput()
	{
		return $this->oInput;
	}

	public function GetResultXml()
	{
		return $this->oResultXml;
	}

	public function GetRequestXml()
	{
		return $this->oRequestXml;
	}

	/**
	 * @return string
	 */
	public function ResultXml()
	{
		if ((bool) $this->oRequestXml->GetParamValueByName('background'))
		{
			$this->oResultXml->XmlRoot->AppendAttribute('background', 1);
		}
		
		return $this->oResultXml->ToString();
	}
	
	/**
	 * @param string $sName
	 * @return bool
	 */
	protected function methodExist($sName)
	{
		$bHookExist = true;
		CApi::Plugin()->RunHook('webmail-method-exist-disabler', array(&$sName, &$bHookExist));
		return ($bHookExist && 'Do' === substr($sName, 0, 2) && method_exists($this, $sName));
	}

	/**
	 * @param string $sAction
	 * @param string $sRequest
	 */
	protected function prepareMethodName($sAction, $sRequest)
	{
		$sName = '';
		if ('operation_messages' == $sAction)
		{
			$sName = 'DoOperationMessagesFunction';
		}
		else
		{
			$sName = str_replace('_', ' ', strtolower('Do '.$sAction.' '.$sRequest));
			$aNamesArray = array_map('ucfirst', explode(' ', $sName));
			$sName = implode('', $aNamesArray);
		}
		
		return $sName;
	}
}
