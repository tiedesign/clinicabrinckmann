<?php

	/**
	 * Original data taken from:
	 * ftp://ftp.unicode.org/Public/MAPPINGS/ISO8859/8859-9.TXT
	 * @param string $string
	 * @return string
	 */
	function charset_encode_iso_8859_9($string)
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
					"\xC2\xA1" => "\xA1",
					"\xC2\xA2" => "\xA2",
					"\xC2\xA3" => "\xA3",
					"\xC2\xA4" => "\xA4",
					"\xC2\xA5" => "\xA5",
					"\xC2\xA6" => "\xA6",
					"\xC2\xA7" => "\xA7",
					"\xC2\xA8" => "\xA8",
					"\xC2\xA9" => "\xA9",
					"\xC2\xAA" => "\xAA",
					"\xC2\xAB" => "\xAB",
					"\xC2\xAC" => "\xAC",
					"\xC2\xAD" => "\xAD",
					"\xC2\xAE" => "\xAE",
					"\xC2\xAF" => "\xAF",
					"\xC2\xB0" => "\xB0",
					"\xC2\xB1" => "\xB1",
					"\xC2\xB2" => "\xB2",
					"\xC2\xB3" => "\xB3",
					"\xC2\xB4" => "\xB4",
					"\xC2\xB5" => "\xB5",
					"\xC2\xB6" => "\xB6",
					"\xC2\xB7" => "\xB7",
					"\xC2\xB8" => "\xB8",
					"\xC2\xB9" => "\xB9",
					"\xC2\xBA" => "\xBA",
					"\xC2\xBB" => "\xBB",
					"\xC2\xBC" => "\xBC",
					"\xC2\xBD" => "\xBD",
					"\xC2\xBE" => "\xBE",
					"\xC2\xBF" => "\xBF",
					"\xC3\x80" => "\xC0",
					"\xC3\x81" => "\xC1",
					"\xC3\x82" => "\xC2",
					"\xC3\x83" => "\xC3",
					"\xC3\x84" => "\xC4",
					"\xC3\x85" => "\xC5",
					"\xC3\x86" => "\xC6",
					"\xC3\x87" => "\xC7",
					"\xC3\x88" => "\xC8",
					"\xC3\x89" => "\xC9",
					"\xC3\x8A" => "\xCA",
					"\xC3\x8B" => "\xCB",
					"\xC3\x8C" => "\xCC",
					"\xC3\x8D" => "\xCD",
					"\xC3\x8E" => "\xCE",
					"\xC3\x8F" => "\xCF",
					"\xC4\x9E" => "\xD0",
					"\xC3\x91" => "\xD1",
					"\xC3\x92" => "\xD2",
					"\xC3\x93" => "\xD3",
					"\xC3\x94" => "\xD4",
					"\xC3\x95" => "\xD5",
					"\xC3\x96" => "\xD6",
					"\xC3\x97" => "\xD7",
					"\xC3\x98" => "\xD8",
					"\xC3\x99" => "\xD9",
					"\xC3\x9A" => "\xDA",
					"\xC3\x9B" => "\xDB",
					"\xC3\x9C" => "\xDC",
					"\xC4\xB0" => "\xDD",
					"\xC5\x9E" => "\xDE",
					"\xC3\x9F" => "\xDF",
					"\xC3\xA0" => "\xE0",
					"\xC3\xA1" => "\xE1",
					"\xC3\xA2" => "\xE2",
					"\xC3\xA3" => "\xE3",
					"\xC3\xA4" => "\xE4",
					"\xC3\xA5" => "\xE5",
					"\xC3\xA6" => "\xE6",
					"\xC3\xA7" => "\xE7",
					"\xC3\xA8" => "\xE8",
					"\xC3\xA9" => "\xE9",
					"\xC3\xAA" => "\xEA",
					"\xC3\xAB" => "\xEB",
					"\xC3\xAC" => "\xEC",
					"\xC3\xAD" => "\xED",
					"\xC3\xAE" => "\xEE",
					"\xC3\xAF" => "\xEF",
					"\xC4\x9F" => "\xF0",
					"\xC3\xB1" => "\xF1",
					"\xC3\xB2" => "\xF2",
					"\xC3\xB3" => "\xF3",
					"\xC3\xB4" => "\xF4",
					"\xC3\xB5" => "\xF5",
					"\xC3\xB6" => "\xF6",
					"\xC3\xB7" => "\xF7",
					"\xC3\xB8" => "\xF8",
					"\xC3\xB9" => "\xF9",
					"\xC3\xBA" => "\xFA",
					"\xC3\xBB" => "\xFB",
					"\xC3\xBC" => "\xFC",
					"\xC4\xB1" => "\xFD",
					"\xC5\x9F" => "\xFE",
					"\xC3\xBF" => "\xFF");

		return str_replace(array_keys($mapping), array_values($mapping), $string);
	}
