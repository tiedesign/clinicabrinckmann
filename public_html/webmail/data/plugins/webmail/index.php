<?php

class CWebMailPlugin extends AApiPlugin
{
	/**
	 * @param CApiPluginManager $oPluginManager
	 */
	public function __construct(CApiPluginManager $oPluginManager)
	{
		parent::__construct('1.0', $oPluginManager);

		$this->AddHook('api-change-account-by-id', 'PluginApiChangeAccountById');
	}

	/**
	 * @param CAccount $oAccount
	 */
	public function PluginApiChangeAccountById(&$oAccount)
	{
		if (is_a($oAccount, 'CAccount') && EMailProtocol::IMAP4 === $oAccount->IncomingMailProtocol)
		{
			$oAccount->EnableExtension(CAccount::SpamFolderExtension);
			$oAccount->EnableExtension(CAccount::SpamLearningExtension);
		}
	}
}

return new CWebMailPlugin($this);
