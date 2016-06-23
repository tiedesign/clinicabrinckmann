<?php

	/**
	 * Original data taken from:
	 * ftp://ftp.unicode.org/Public/MAPPINGS/ISO8859/8859-2.TXT
	 * @param string $string
	 * @return string
	 */
	function charset_encode_iso_8859_2($string)
	{
		$mapping = array(
					"\xC2\x80" => "\x80",
					"\xC2\x81" => "\x81",
					"\xC2\x82" => "\x82",
					"\xC2\x83" => "\x83",
					"\xC2\x84" => "\x84",
					"\xC2\x85" => "\x85",
					"\xC2\x86" => "\x86",
					"\xC2\x87" => "\x87",
					"\xC2\x88" => "\x88",
					"\xC2\x89" => "\x89",
					"\xC2\x8A" => "\x8A",
					"\xC2\x8B" => "\x8B",
					"\xC2\x8C" => "\x8C",
					"\xC2\x8D" => "\x8D",
					"\xC2\x8E" => "\x8E",
					"\xC2\x8F" => "\x8F",
					"\xC2\x90" => "\x90",
					"\xC2\x91" => "\x91",
					"\xC2\x92" => "\x92",
					"\xC2\x93" => "\x93",
					"\xC2\x94" => "\x94",
					"\xC2\x95" => "\x95",
					"\xC2\x96" => "\x96",
					"\xC2\x97" => "\x97",
					"\xC2\x98" => "\x98",
					"\xC2\x99" => "\x99",
					"\xC2\x9A" => "\x9A",
					"\xC2\x9B" => "\x9B",
					"\xC2\x9C" => "\x9C",
					"\xC2\x9D" => "\x9D",
					"\xC2\x9E" => "\x9E",
					"\xC2\x9F" => "\x9F",
					"\xC2\xA0" => "\xA0",
					"\xC4\x84" => "\xA1",
					"\xCB\x98" => "\xA2",
					"\xC5\x81" => "\xA3",
					"\xC2\xA4" => "\xA4",
					"\xC4\xBD" => "\xA5",
					"\xC5\x9A" => "\xA6",
					"\xC2\xA7" => "\xA7",
					"\xC2\xA8" => "\xA8",
					"\xC5\xA0" => "\xA9",
					"\xC5\x9E" => "\xAA",
					"\xC5\xA4" => "\xAB",
					"\xC5\xB9" => "\xAC",
					"\xC2\xAD" => "\xAD",
					"\xC5\xBD" => "\xAE",
					"\xC5\xBB" => "\xAF",
					"\xC2\xB0" => "\xB0",
					"\xC4\x85" => "\xB1",
					"\xCB\x9B" => "\xB2",
					"\xC5\x82" => "\xB3",
					"\xC2\xB4" => "\xB4",
					"\xC4\xBE" => "\xB5",
					"\xC5\x9B" => "\xB6",
					"\xCB\x87" => "\xB7",
					"\xC2\xB8" => "\xB8",
					"\xC5\xA1" => "\xB9",
					"\xC5\x9F" => "\xBA",
					"\xC5\xA5" => "\xBB",
					"\xC5\xBA" => "\xBC",
					"\xCB\x9D" => "\xBD",
					"\xC5\xBE" => "\xBE",
					"\xC5\xBC" => "\xBF",
					"\xC5\x94" => "\xC0",
					"\xC3\x81" => "\xC1",
					"\xC3\x82" => "\xC2",
					"\xC4\x82" => "\xC3",
					"\xC3\x84" => "\xC4",
					"\xC4\xB9" => "\xC5",
					"\xC4\x86" => "\xC6",
					"\xC3\x87" => "\xC7",
					"\xC4\x8C" => "\xC8",
					"\xC3\x89" => "\xC9",
					"\xC4\x98" => "\xCA",
					"\xC3\x8B" => "\xCB",
					"\xC4\x9A" => "\xCC",
					"\xC3\x8D" => "\xCD",
					"\xC3\x8E" => "\xCE",
					"\xC4\x8E" => "\xCF",
					"\xC4\x90" => "\xD0",
					"\xC5\x83" => "\xD1",
					"\xC5\x87" => "\xD2",
					"\xC3\x93" => "\xD3",
					"\xC3\x94" => "\xD4",
					"\xC5\x90" => "\xD5",
					"\xC3\x96" => "\xD6",
					"\xC3\x97" => "\xD7",
					"\xC5\x98" => "\xD8",
					"\xC5\xAE" => "\xD9",
					"\xC3\x9A" => "\xDA",
					"\xC5\xB0" => "\xDB",
					"\xC3\x9C" => "\xDC",
					"\xC3\x9D" => "\xDD",
					"\xC5\xA2" => "\xDE",
					"\xC3\x9F" => "\xDF",
					"\xC5\x95" => "\xE0",
					"\xC3\xA1" => "\xE1",
					"\xC3\xA2" => "\xE2",
					"\xC4\x83" => "\xE3",
					"\xC3\xA4" => "\xE4",
					"\xC4\xBA" => "\xE5",
					"\xC4\x87" => "\xE6",
					"\xC3\xA7" => "\xE7",
					"\xC4\x8D" => "\xE8",
					"\xC3\xA9" => "\xE9",
					"\xC4\x99" => "\xEA",
					"\xC3\xAB" => "\xEB",
					"\xC4\x9B" => "\xEC",
					"\xC3\xAD" => "\xED",
					"\xC3\xAE" => "\xEE",
					"\xC4\x8F" => "\xEF",
					"\xC4\x91" => "\xF0",
					"\xC5\x84" => "\xF1",
					"\xC5\x88" => "\xF2",
					"\xC3\xB3" => "\xF3",
					"\xC3\xB4" => "\xF4",
					"\xC5\x91" => "\xF5",
					"\xC3\xB6" => "\xF6",
					"\xC3\xB7" => "\xF7",
					"\xC5\x99" => "\xF8",
					"\xC5\xAF" => "\xF9",
					"\xC3\xBA" => "\xFA",
					"\xC5\xB1" => "\xFB",
					"\xC3\xBC" => "\xFC",
					"\xC3\xBD" => "\xFD",
					"\xC5\xA3" => "\xFE",
					"\xCB\x99" => "\xFF");

		return str_replace(array_keys($mapping), array_values($mapping), $string);
	}

