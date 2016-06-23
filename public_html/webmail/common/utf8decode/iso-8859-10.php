<?php

	/**
	 * Original data taken from:
	 * ftp://ftp.unicode.org/Public/MAPPINGS/ISO8859/8859-10.TXT
	 * @param string $string
	 * @return string
	 */
	function charset_decode_iso_8859_10($string)
	{
		$mapping = array(
					"\x80" => "\xC2\x80",
					"\x81" => "\xC2\x81",
					"\x82" => "\xC2\x82",
					"\x83" => "\xC2\x83",
					"\x84" => "\xC2\x84",
					"\x85" => "\xC2\x85",
					"\x86" => "\xC2\x86",
					"\x87" => "\xC2\x87",
					"\x88" => "\xC2\x88",
					"\x89" => "\xC2\x89",
					"\x8A" => "\xC2\x8A",
					"\x8B" => "\xC2\x8B",
					"\x8C" => "\xC2\x8C",
					"\x8D" => "\xC2\x8D",
					"\x8E" => "\xC2\x8E",
					"\x8F" => "\xC2\x8F",
					"\x90" => "\xC2\x90",
					"\x91" => "\xC2\x91",
					"\x92" => "\xC2\x92",
					"\x93" => "\xC2\x93",
					"\x94" => "\xC2\x94",
					"\x95" => "\xC2\x95",
					"\x96" => "\xC2\x96",
					"\x97" => "\xC2\x97",
					"\x98" => "\xC2\x98",
					"\x99" => "\xC2\x99",
					"\x9A" => "\xC2\x9A",
					"\x9B" => "\xC2\x9B",
					"\x9C" => "\xC2\x9C",
					"\x9D" => "\xC2\x9D",
					"\x9E" => "\xC2\x9E",
					"\x9F" => "\xC2\x9F",
					"\xA0" => "\xC2\xA0",
					"\xA1" => "\xC4\x84",
					"\xA2" => "\xC4\x92",
					"\xA3" => "\xC4\xA2",
					"\xA4" => "\xC4\xAA",
					"\xA5" => "\xC4\xA8",
					"\xA6" => "\xC4\xB6",
					"\xA7" => "\xC2\xA7",
					"\xA8" => "\xC4\xBB",
					"\xA9" => "\xC4\x90",
					"\xAA" => "\xC5\xA0",
					"\xAB" => "\xC5\xA6",
					"\xAC" => "\xC5\xBD",
					"\xAD" => "\xC2\xAD",
					"\xAE" => "\xC5\xAA",
					"\xAF" => "\xC5\x8A",
					"\xB0" => "\xC2\xB0",
					"\xB1" => "\xC4\x85",
					"\xB2" => "\xC4\x93",
					"\xB3" => "\xC4\xA3",
					"\xB4" => "\xC4\xAB",
					"\xB5" => "\xC4\xA9",
					"\xB6" => "\xC4\xB7",
					"\xB7" => "\xC2\xB7",
					"\xB8" => "\xC4\xBC",
					"\xB9" => "\xC4\x91",
					"\xBA" => "\xC5\xA1",
					"\xBB" => "\xC5\xA7",
					"\xBC" => "\xC5\xBE",
					"\xBD" => "\xE2\x80\x95",
					"\xBE" => "\xC5\xAB",
					"\xBF" => "\xC5\x8B",
					"\xC0" => "\xC4\x80",
					"\xC1" => "\xC3\x81",
					"\xC2" => "\xC3\x82",
					"\xC3" => "\xC3\x83",
					"\xC4" => "\xC3\x84",
					"\xC5" => "\xC3\x85",
					"\xC6" => "\xC3\x86",
					"\xC7" => "\xC4\xAE",
					"\xC8" => "\xC4\x8C",
					"\xC9" => "\xC3\x89",
					"\xCA" => "\xC4\x98",
					"\xCB" => "\xC3\x8B",
					"\xCC" => "\xC4\x96",
					"\xCD" => "\xC3\x8D",
					"\xCE" => "\xC3\x8E",
					"\xCF" => "\xC3\x8F",
					"\xD0" => "\xC3\x90",
					"\xD1" => "\xC5\x85",
					"\xD2" => "\xC5\x8C",
					"\xD3" => "\xC3\x93",
					"\xD4" => "\xC3\x94",
					"\xD5" => "\xC3\x95",
					"\xD6" => "\xC3\x96",
					"\xD7" => "\xC5\xA8",
					"\xD8" => "\xC3\x98",
					"\xD9" => "\xC5\xB2",
					"\xDA" => "\xC3\x9A",
					"\xDB" => "\xC3\x9B",
					"\xDC" => "\xC3\x9C",
					"\xDD" => "\xC3\x9D",
					"\xDE" => "\xC3\x9E",
					"\xDF" => "\xC3\x9F",
					"\xE0" => "\xC4\x81",
					"\xE1" => "\xC3\xA1",
					"\xE2" => "\xC3\xA2",
					"\xE3" => "\xC3\xA3",
					"\xE4" => "\xC3\xA4",
					"\xE5" => "\xC3\xA5",
					"\xE6" => "\xC3\xA6",
					"\xE7" => "\xC4\xAF",
					"\xE8" => "\xC4\x8D",
					"\xE9" => "\xC3\xA9",
					"\xEA" => "\xC4\x99",
					"\xEB" => "\xC3\xAB",
					"\xEC" => "\xC4\x97",
					"\xED" => "\xC3\xAD",
					"\xEE" => "\xC3\xAE",
					"\xEF" => "\xC3\xAF",
					"\xF0" => "\xC3\xB0",
					"\xF1" => "\xC5\x86",
					"\xF2" => "\xC5\x8D",
					"\xF3" => "\xC3\xB3",
					"\xF4" => "\xC3\xB4",
					"\xF5" => "\xC3\xB5",
					"\xF6" => "\xC3\xB6",
					"\xF7" => "\xC5\xA9",
					"\xF8" => "\xC3\xB8",
					"\xF9" => "\xC5\xB3",
					"\xFA" => "\xC3\xBA",
					"\xFB" => "\xC3\xBB",
					"\xFC" => "\xC3\xBC",
					"\xFD" => "\xC3\xBD",
					"\xFE" => "\xC3\xBE",
					"\xFF" => "\xC4\xB8");
		
		$outStr = '';
    	for ($i = 0, $len = strlen($string); $i < $len; $i++)
    	{
    		$outStr .= (array_key_exists($string{$i}, $mapping))?$mapping[$string{$i}]:$string{$i};
		}
		
		return $outStr;
	}

