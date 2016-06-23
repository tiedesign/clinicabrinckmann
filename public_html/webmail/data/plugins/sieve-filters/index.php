<?php

class CSieveFilters extends AApiPlugin
{
	/**
	 * @var CApiSieveProtocol
	 */
	protected $oSieve;
	
	/**
	 * @var string
	 */
	protected $sSieveFileName;

	/**
	 * @param CApiPluginManager $oPluginManager
	 */
	public function __construct(CApiPluginManager $oPluginManager)
	{
		parent::__construct('1.0', $oPluginManager);

		$this->oSieve = null;
		$this->sSieveFileName = 'sieve';

		$this->AddHook('api-change-account-by-id', 'PluginChangeAccountById');
		$this->AddHook('get-sieve-filters', 'GetSieveFilters');
		$this->AddHook('update-sieve-filters', 'UpdateSieveFilters');
	}

	public function Init()
	{
		parent::Init();

		CApi::Inc('common.net.protocols.sieve');

		$sConfigPrefix = 'plugins.sieve-filters.config.';
		$this->sSieveFileName = CApi::GetConf($sConfigPrefix.'file', $this->sSieveFileName);
		$this->oSieve = new CApiSieveProtocol(
			CApi::GetConf($sConfigPrefix.'host', '127.0.0.1'),
			(int) CApi::GetConf($sConfigPrefix.'port', 2000));
	}

	/**
	 * @param CAccount $oAccount
	 */
	public function PluginChangeAccountById(&$oAccount)
	{
		if ($oAccount && $oAccount->IsDefaultAccount)
		{
			$oAccount->EnableExtension(CAccount::SieveFiltersExtension);
		}
	}

	/**
	 * @param CAcount $oAccount
	 * @return bool
	 */
	protected function connectSieve($oAccount)
	{
		$bResult = false;
		if (!$this->oSieve->IsConnected())
		{
			$bResult = $this->oSieve->ConnectAndLogin(
				$oAccount->IncomingMailLogin, $oAccount->IncomingMailPassword);
		}
		else
		{
			$bResult = true;
		}
		
		return $bResult;
	}

	/**
	 * @param CAcount $oAccount
	 * @return string | bool
	 */
	protected function GetSieveFiltersText(&$oAccount)
	{
		$sResult = false;
		if ($this->connectSieve($oAccount))
		{
			$sResult = $this->oSieve->GetScript($this->sSieveFileName);
		}
		return $sResult;
	}
	
	/**
	 * @param CAcount $oAccount
	 * @param string $sFilters
	 * @return string
	 */
	protected function SetSieveFiltersText(&$oAccount, $sFilters)
	{
		$iResult = 0;
		if ($this->connectSieve($oAccount))
		{
			$iResult = (int) $this->oSieve->SendScript($this->sSieveFileName, $sFilters);
			$iResult &= $this->oSieve->SetActiveScript($this->sSieveFileName);
		}
		return (bool) $iResult;
	}

	protected function getMailProcessorFolders($oAccount)
	{
		static $oFolders = null;
		if (null === $oFolders)
		{
			$oMailProcessor = new MailProcessor($oAccount);
			$oFolders = $oMailProcessor->GetFolders();
		}
		return $oFolders;
	}

	/**
	 * @param CAcount $oAccount
	 * @param CFilterCollection $oFilters
	 */
	public function GetSieveFilters(&$oAccount, &$oFilters)
	{
		if (!$oAccount->IsEnabledExtension(CAccount::SieveFiltersExtension))
		{
			return false;
		}

		$sScript = $this->GetSieveFiltersText($oAccount);
		if (false === $sScript)
		{
			return false;
		}

		$oFilters = null;
		$oFilters = new FilterCollection();
		
		$aFilters = explode("\n", $sScript);
		
		$aFoldersCache = array();
		foreach ($aFilters as $sFilter)
		{
			$pattern = '#sieve_filter:';
			if (strpos($sFilter, $pattern) !== false)
			{
				$sFilter = substr($sFilter, strlen($pattern));

				$aFilter = array();
				$aFilter = explode(";", $sFilter);

				$oFilter = new Filter();
				$oFilter->Id = -1;
				$oFilter->IdAcct = $oAccount->IdAccount;
				$oFilter->IsSystem = false;
				$oFilter->Applied = trim($aFilter[0]);
				$oFilter->Condition = trim($aFilter[1]);
				$oFilter->Field = trim($aFilter[2]);
				$oFilter->Filter = trim($aFilter[3]);
				$oFilter->Action = trim($aFilter[4]);

				if (FILTERACTION_MoveToFolder === (int) $oFilter->Action
					&& !empty($aFilter[5]))
				{
					$oFolders = $this->getMailProcessorFolders($oAccount);
					if ($oFolders)
					{
						if (!isset($aFoldersCache[$aFilter[5]]))
						{
							$aFoldersCache[$aFilter[5]] = $oFolders->GetFolderByFullName($aFilter[5]);
						}

						$oFolder = $aFoldersCache[$aFilter[5]];
						if ($oFolder)
						{
							$oFilter->IdFolder = $oFolder->IdDb;
							$oFilter->FolderFullName = $oFolder->FullName;
						}
						else
						{
							$oFilter = null;
						}
					}
					else
					{
						$oFilter = null;
					}
				}

				if ($oFilter)
				{
					$oFilters->Add($oFilter);
				}

				unset($oFilter);
			}
		}

		$oFilters->List->_list = array_reverse($oFilters->List->_list);
	}

	/**
	 * @param CAccount $oAccount
	 * @param CAccount $oFiltersNode
	 * @param bool $bSuccess
	 */
	public function UpdateSieveFilters(&$oAccount, &$oFiltersNode, &$bSuccess)
	{
		if (!$oAccount->IsEnabledExtension(CAccount::SieveFiltersExtension))
		{
			return false;
		}

		$sFilters = '#sieve filter'."\n\n".'require "fileinto" ;'."\n";

		for ($mKey = count($oFiltersNode->Children) - 1; $mKey >= 0; $mKey--)
		{
			$oFilterNode =& $oFiltersNode->Children[$mKey];
			if (isset($oFilterNode->Attributes['status']))
			{
				$sStatus = $oFilterNode->Attributes['status'];
				switch ($sStatus)
				{
					case 'new':
					case 'updated':
					case 'unchanged':
						$oFilter = CAppXmlHelper::GetFilter($oFilterNode);
						if ($oFilter)
						{
							// field
							$field = 'From';
							switch($oFilter->Field)
							{
								case FILTERFIELD_From:
									$field = 'From';
									break;
								case FILTERFIELD_To:
									$field = 'To';
									break;
								case FILTERFIELD_Subject:
									$field = 'Subject';
									break;
							}

							// filter
							$filter = $oFilter->Filter;

							// condition
							$condition = '';
							switch ($oFilter->Condition)
							{
								case FILTERCONDITION_ContainSubstring:
									$condition = 'if header :contains "'.$field.'" "'.$filter.'" {';
									break;
								case FILTERCONDITION_ContainExactPhrase:
									$condition = 'if header :is "'.$field.'" "'.$filter.'" {';
									break;
								case FILTERCONDITION_NotContainSubstring:
									$condition = 'if not header :contains "'.$field.'" "'.$filter.'" {';
									break;
							}

							// folder
							$folderFullName = '';
							if ((int) $oFilter->Action === FILTERACTION_MoveToFolder && $oAccount)
							{
								$oFolders = $this->getMailProcessorFolders($oAccount);
								if ($oFolders)
								{
									$folder = $oFolders->GetFolderById($oFilter->IdFolder);
									if ($folder)
									{
										$folderFullName = $folder->FullName;
									}
								}
							}

							// action
							$action = "";
							switch($oFilter->Action)
							{
								case FILTERACTION_DeleteFromServerImmediately:
									$action = 'discard ;';
									break;
								case FILTERACTION_MoveToFolder:
									$action = 'fileinto "'.$folderFullName.'" ;';
									break;
							}

							$end = '}';

							if (!$oFilter->Applied)
							{
								$condition = '#'.$condition;
								$action = '#'.$action;
								$end = '#'.$end;
							}

							$sFilters .= "\n".'#sieve_filter:'.
								implode(';', array(
									$oFilter->Applied, $oFilter->Condition, $oFilter->Field, 
									$oFilter->Filter, $oFilter->Action, $folderFullName))."\n";
							
							$sFilters .= $condition."\n";
							$sFilters .= $action."\n";
							$sFilters .= $end."\n";
						}
						break;
				}
			}
		}
		$sFilters = $sFilters . "\n".'#end sieve filter'."\n";

		$this->SetSieveFiltersText($oAccount, $sFilters);
	}
}

return new CSieveFilters($this);
