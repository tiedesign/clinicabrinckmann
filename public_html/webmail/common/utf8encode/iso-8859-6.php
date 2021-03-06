<?php

	/**
	 * Original data taken from:
	 * ftp://ftp.unicode.org/Public/MAPPINGS/ISO8859/8859-6.TXT
	 * @param string $string
	 * @return string
	 */
	function charset_encode_iso_8859_6($string)
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
					"\xC2\xA4" => "\xA4",
					"\xD8\x8C" => "\xAC",
					"\xC2\xAD" => "\xAD",
					"\xD8\x9B" => "\xBB",
					"\xD8\x9F" => "\xBF",
					"\xD8\xA1" => "\xC1",
					"\xD8\xA2" => "\xC2",
					"\xD8\xA3" => "\xC3",
					"\xD8\xA4" => "\xC4",
					"\xD8\xA5" => "\xC5",
					"\xD8\xA6" => "\xC6",
					"\xD8\xA7" => "\xC7",
					"\xD8\xA8" => "\xC8",
					"\xD8\xA9" => "\xC9",
					"\xD8\xAA" => "\xCA",
					"\xD8\xAB" => "\xCB",
					"\xD8\xAC" => "\xCC",
					"\xD8\xAD" => "\xCD",
					"\xD8\xAE" => "\xCE",
					"\xD8\xAF" => "\xCF",
					"\xD8\xB0" => "\xD0",
					"\xD8\xB1" => "\xD1",
					"\xD8\xB2" => "\xD2",
					"\xD8\xB3" => "\xD3",
					"\xD8\xB4" => "\xD4",
					"\xD8\xB5" => "\xD5",
					"\xD8\xB6" => "\xD6",
					"\xD8\xB7" => "\xD7",
					"\xD8\xB8" => "\xD8",
					"\xD8\xB9" => "\xD9",
					"\xD8\xBA" => "\xDA",
					"\xD9\x80" => "\xE0",
					"\xD9\x81" => "\xE1",
					"\xD9\x82" => "\xE2",
					"\xD9\x83" => "\xE3",
					"\xD9\x84" => "\xE4",
					"\xD9\x85" => "\xE5",
					"\xD9\x86" => "\xE6",
					"\xD9\x87" => "\xE7",
					"\xD9\x88" => "\xE8",
					"\xD9\x89" => "\xE9",
					"\xD9\x8A" => "\xEA",
					"\xD9\x8B" => "\xEB",
					"\xD9\x8C" => "\xEC",
					"\xD9\x8D" => "\xED",
					"\xD9\x8E" => "\xEE",
					"\xD9\x8F" => "\xEF",
					"\xD9\x90" => "\xF0",
					"\xD9\x91" => "\xF1",
					"\xD9\x92" => "\xF2");

		return str_replace(array_keys($mapping), array_values($mapping), $string);
	}
