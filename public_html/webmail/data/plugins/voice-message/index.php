<?php

class CVoiceMessagePlugin extends AApiPlugin
{
	/**
	 * @param CApiPluginManager $oPluginManager
	 */
	public function __construct(CApiPluginManager $oPluginManager)
	{
		parent::__construct('1.0', $oPluginManager);

		$this->AddHook('webmail.voice-message-detect', 'PluginVoiceMessageDetect');
		$this->AddHook('webmail.voice-attachment-detect', 'PluginVoiceAttachmentDetect');
	}

	/**
	 * @param WebMailMessage $oWebMailMessage
	 * @param bool $bIsVoice
	 */
	public function PluginVoiceMessageDetect($oWebMailMessage, &$bIsVoice)
	{
		$bIsVoice = false;

		if (is_a($oWebMailMessage, 'WebMailMessage'))
		{
			$sContentType = $oWebMailMessage->GetContentType();
			$bIsVoice = (false !== strpos(strtolower($sContentType), 'voice'));
		}
	}

	/**
	 * @param WebMailMessage $oWebMailMessage
	 * @param bool $bIsVoice
	 */
	public function PluginVoiceAttachmentDetect($oAttachment, $sFileName, &$bAttachmentVoice)
	{
		$bAttachmentVoice = false;

		if ($oAttachment && !empty($sFileName))
		{
			$aFileNameExplode = explode('.', $sFileName);
			$bAttachmentVoice = (bool) (is_array($aFileNameExplode) && 1 < count($aFileNameExplode) &&
				('amr' === strtolower($aFileNameExplode[count($aFileNameExplode) - 1]) ||
				 'wav' === strtolower($aFileNameExplode[count($aFileNameExplode) - 1]))
			);
		}
	}
}

return new CVoiceMessagePlugin($this);
