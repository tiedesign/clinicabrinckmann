<?php

/*
 * AfterLogic WebMail Pro PHP by AfterLogic Corp. <support@afterlogic.com>
 *
 * Copyright (C) 2002-2011  AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in COPYING
 * 
 */

defined('WM_ROOTPATH') || define('WM_ROOTPATH', (dirname(__FILE__).'/'));
require_once WM_ROOTPATH.'application/include.php';
$oInput = new CAppBaseHttp();

header('Content-type: text/xml');

define('MAX_SUGGEST_WORDS', 10);

require_once(WM_ROOTPATH.'common/spellchecker/class_spellchecker.php');
require_once(WM_ROOTPATH.'common/inc_constants.php');
require_once(WM_ROOTPATH.'common/class_convertutils.php');

ConvertUtils::SetLimits();

@ob_start('obLogResponse');

$spell_lang = '';
$spell_dictionary = '';

$spell_lang = isset($_SESSION[APP_SESSION_LANG]) ? $_SESSION[APP_SESSION_LANG] : 'English';

switch ($spell_lang) 
{
	default:
		$spell_dictionary = 'en-US.dic';
		break;
	case 'French':
		$spell_dictionary = 'fr-FR.dic';
		break;
	case 'Russian':
		$spell_dictionary = 'ru-RU.dic';
		break;
	case 'German':
		$spell_dictionary = 'de-DE.dic';
		break;
	case 'Portuguese-Brazil':
		// $spell_dictionary = 'pt-PT.dic';
		$spell_dictionary = 'pt-BR.dic';
		break;
}

$sp = new Spellchecker(WM_ROOTPATH.'common/spellchecker/dictionary/'.$spell_dictionary);
if ($sp->_error === '' || $sp->_error === null) 
{
	$xmlStr = $oInput->GetPost('xml', '<'.'?xml version="1.0" encoding="utf-8"?'.'><webmail><param name="action" value="spellcheck"/><param name="request" value="spell"/><text><![CDATA[]]></text></webmail>');
	CApi::Log('[spellchecker] <'."\r\n".$xmlStr);
	
	$cxml = new CXmlDocument();
	$cxml->ParseFromString($xmlStr);
	$response = new CXmlDocument();
	$response->CreateElement('webmail');
	if ($cxml->GetParamValueByName('action') == 'spellcheck')
	{
		$req = $cxml->GetParamValueByName('request');
		switch ($req)
		{
			case 'spell':
				$node = new CXmlDomNode('spellcheck');

				$text = str_replace('&#93;&#93;&gt;', ']]>', $cxml->XmlRoot->GetChildValueByTagName('text'));
				$sp->text = $text;
				$misspel = $sp->ParseText();
				foreach ($misspel as $misspelNode)
				{
					if (is_array($misspelNode) && count($misspelNode) > 1)
					{
						$misp = new CXmlDomNode('misp', '');
						$misp->AppendAttribute('pos',  $misspelNode[0]);
						$misp->AppendAttribute('len',  $misspelNode[1]);
						$node->AppendChild($misp);
						unset($misp);
					}
				}
				
				$node->AppendAttribute('action', 'spellcheck');
				$response->XmlRoot->AppendChild($node);
				break;
			case 'suggest':
				$suggest = array();
				$suggestTmp = array();
				$wordNode = $cxml->XmlRoot->GetChildNodeByTagName('word');
				$word = '';
				if ($wordNode)
				{
					$word = str_replace('&amp;', '&', str_replace('&lt;', '<', str_replace('&gt;', '>', $wordNode->Value)));
				}

				if (strlen(trim($word)) == 0)
				{
					$node = new CXmlDomNode('spellcheck');
					$node->AppendAttribute('action', 'suggest');
					$response->XmlRoot->AppendChild($node);
					break;
				}
				
				$sp->currentWord = $word;
				
				$sp->ReplaceChars($suggestTmp);
				$suggest = array_unique(array_merge($suggest, $suggestTmp));
				if (count($suggest) < MAX_SUGGEST_WORDS - 1)
				{
					$sp->SwapChar($suggestTmp);
					$suggest = array_unique(array_merge($suggest, $suggestTmp));
					if (count($suggest) < MAX_SUGGEST_WORDS - 1)
					{
						$sp->BadChar($suggestTmp);
						$suggest = array_unique(array_merge($suggest, $suggestTmp));
						if (count($suggest) < MAX_SUGGEST_WORDS - 1)
						{
							$sp->ForgotChar($suggestTmp);
							$suggest = array_unique(array_merge($suggest, $suggestTmp));
							if (count($suggest) < MAX_SUGGEST_WORDS - 1)
							{
								$sp->ExtraChar($suggestTmp);
								$suggest = array_unique(array_merge($suggest, $suggestTmp));
								if (count($suggest) < MAX_SUGGEST_WORDS - 1)
								{
									$sp->TwoWords($suggestTmp);
									$suggest = array_unique(array_merge($suggest, $suggestTmp));
								}
							}
						}
					}
				}
				$node = new CXmlDomNode('spellcheck');
				foreach ($suggest as $suggestWord) 
				{
					$node->AppendChild(new CXmlDomNode('word', $suggestWord, true));
				}
				
				$node->AppendAttribute('action', 'suggest');
				$response->XmlRoot->AppendChild($node);
				break;
		} 
	}
	
	echo $response->ToString();
} 
else
{
	/* if error ocured */
	$response = new CXmlDocument();
	$response->CreateElement('webmail');
	$node = new CXmlDomNode('spellcheck');
	$node->AppendAttribute('action', 'error');
	$node->AppendAttribute('errorstr',  $sp->_error);
	$response->XmlRoot->AppendChild($node);
	echo $response->ToString();
}

/**
 * @param string $string
 * @return string
 */
function obLogResponse($string)
{
	CApi::Log('[spellchecker] >'."\r\n".$string);
	return $string;
}
