<?php

/**
 * This file is part of the GcLib library.
 */

namespace GcLib;

/**
 * String
 *
 * @author T4lus
 * @version $Revision: 59 $
 * @date $Date: 2012-09-10 13:10:43 +0200 (Mon, 10 Sep 2012) $
 * @package GcLib
 */
class String
{

	/** Modes */
	const	MODE_UTF8		= 0,
			MODE_URL		= 1,
			MODE_BASE_64	= 2,
			MODE_JSON		= 3,
			MODE_MD5		= 4,
			MODE_SHA1		= 5;

	/** @const string Default charset */
	const DEFAULT_CHARSET = 'UTF-8';

	/**
	 * Truncate
	 *
	 * @param string $string String
	 * @param int $length Length (with end chars)
	 * @param string $endChars En characters
	 * @param bool $strict Strict truncating
	 * @param bool $truncateHTML Truncate HTML tags
	 *
	 * @return string
	 */
	public static function truncate($string, $length = 100, $endChars = '...', $strict = FALSE, $truncateHTML = TRUE) {
		$stringTemporary = $string;
		$string = '';
		$openTags = array();

		if ($truncateHTML) {
			if (self::length(\preg_replace('/<.*?>/', '', $stringTemporary)) <= $length) {
				return $stringTemporary;
			}

			\preg_match_all('/(<.+?>)?([^<>]*)/s', $stringTemporary, $lines, PREG_SET_ORDER);
			$totalLength = self::length($endChars);

			foreach ($lines as $lineMatching) {
				if (!empty($lineMatching[1])) {
					if (\preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $lineMatching[1]))
					{

					}

					else if (\preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $lineMatching[1], $tagMatching)) {
						$position = \array_search($tagMatching[1], $openTags);

						if ($position !== false) {
							unset($openTags[$position]);
						}
					}
					else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $lineMatching[1], $tagMatching)) {
						\array_unshift($openTags, self::lower($tagMatching[1]));
					}
					$string .= $lineMatching[1];
				}

				$contentLength = self::length(\preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $lineMatching[2]));

				if ($totalLength + $contentLength> $length) {
					$left = $length - $totalLength;
					$entitiesLength = 0;

					if (\preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $lineMatching[2], $entities, PREG_OFFSET_CAPTURE)) {
						foreach ($entities[0] as $entity) {
							if ($entity[1] + 1 - $entitiesLength <= $left) {
								$left--;
								$entitiesLength += self::length($entity[0]);
							}
							else {
								break;
							}
						}
					}
					$string .= self::sub($lineMatching[2], 0, $left+$entitiesLength);
					break;
				}
				else {
					$string .= $lineMatching[2];
					$totalLength += $contentLength;
				}

				if($totalLength>= $length) {
					break;
				}
			}
		}
		else {
			if (self::length($stringTemporary) <= $length) {
				return $stringTemporary;
			}
			else {
				$string = self::sub($stringTemporary, 0, $length - self::length($endChars));
			}
		}

		if (!$strict) {
			$spaces = \strrpos($string, ' ');

			if (isset($spaces)) {
				$string = self::sub($string, 0, $spaces);
			}
		}

		$string .= $endChars;

		if ($truncateHTML) {
			foreach ($openTags as $tag) {
				$string .= '</'.$tag.'>';
			}
		}

		return $string;
	}


	/**
	 * Check UTF8 string
	 *
	 * @param string $string String
	 *
	 * @return boolean
	 */
	public static function isUTF8($string) {
		return (bool)\preg_match('%(?:
		[\xC2-\xDF][\x80-\xBF]              # ASCII
		|\xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
		|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
		|\xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
		|\xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
		|[\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
		|\xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
		)+%xs', $string);
	}


	/**
	 * Strip accents
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function stripAccents($string) {
		$accents = array(
			'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
			'ç' => 'c',
			'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
			'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
			'ñ' => 'n',
			'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
			'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
			'ý' => 'y',	'ÿ' => 'y',
			'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
			'Ç' => 'C',
			'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
			'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
			'Ñ' => 'N',
			'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
			'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
			'Ý' => 'Y',
		);
		return \str_replace(\array_keys($accents), $accents, $string);
	}


	/**
	 * Length
	 *
	 * @param string $string
	 * @return int
	 */
	public static function length($string) {
		return \function_exists('mb_strlen') ? \mb_strlen($string, self::DEFAULT_CHARSET) : \strlen($string);
	}



	/**
	 * Upper
	 *
	 * @param $string
	 *
	 * @return string
	 */
	public static function upper($string) {
		return \function_exists('mb_strtoupper') ? \mb_strtoupper($string, self::DEFAULT_CHARSET) : \strtoupper($string);
	}

	/**
	 * Lower
	 *
	 * @param string $string
	 * @return string
	 */
	public static function lower($string) {
		return \function_exists('mb_strtolower') ? \mb_strtolower($string, self::DEFAULT_CHARSET) : \strtolower($string);
	}


	/**
	 * Capitalize
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function capitalize($string) {
		if (\function_exists('mb_convert_case')) {
			return \mb_convert_case($string, MB_CASE_TITLE, self::DEFAULT_CHARSET);
		}
		return \ucwords($string);
	}


	/**
	 * Sub
	 *
	 * @param string $string String
	 * @param int $start Start char
	 * @param NULL|int $length String length
	 * @return string
	 */
	public static function sub($string, $start, $length = NULL) {
		if (\is_null($length)) {
			$length = self::length($string);
		}
		return \function_exists('mb_substr') ? \mb_substr($string, $start, $length, self::DEFAULT_CHARSET): \substr($string, $start, $length);
	}


	/**
	 * Clean
	 *
	 * @param string $string
	 * @param string $separator
	 *
	 * @return string
	 */
	public static function clean($string, $separator = '-') {
		$string = \iconv(self::DEFAULT_CHARSET, 'ASCII//TRANSLIT', $string);
		$string = \preg_replace('/[^a-zA-Z0-9\/_|+ -]/', '', $string);
		$string = self::lower(\trim($string, '-'));
		$string = \preg_replace('/[\/_|+ -]+/', $separator, $string);

		return $string;
	}


	/**
	 * Generate string
	 *
	 * @param int $length Length
	 * @param boolean $complex Complex string
	 *
	 * @return string
	 */
	public static function generate($length = 8, $complex = FALSE) {
		$chars = 'abcdefghijklmopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$chars = $complex ? $chars.'!$%^&*+#~/|' : $chars;

		$string = '';

		if ($length <= 0) {
			return $string;
		}

		\srand((float)\microtime() * 1000000);

		for ($i = 0; $i < $length; $i++) {
			$string .= self::sub($chars, \rand() % self::length($chars), 1);
		}
		return $string;
	}



	/**
	 * Encode
	 *
	 * @param string $string String
	 * @param string|NULL $mode Mode (using const MODE_*)
	 *
	 * @return string
	 */
	public static function encode($string, $mode = NULL) {
		switch ($mode) {
			default:
				return $string;

			case self::MODE_JSON:
				return \json_encode($string);

			case self::MODE_BASE_64:
				return \base64_encode($string);

			case self::MODE_URL:
				return \urlencode($string);

			case self::MODE_UTF8:
				return \utf8_encode($string);

			case self::MODE_MD5:
				return \md5($string);

			case self::MODE_SHA1:
				return \sha1($string);
		}
	}


	/**
	 * Decode
	 *
	 * @param string $string String
	 * @param string|NULL $mode Mode (using const MODE_*)
	 *
	 * @return string
	 */
	public static function decode($string, $mode = NULL) {
		switch ($mode) {
			default:
				return $string;

			case self::MODE_JSON:
				return \json_decode($string);

			case self::MODE_BASE_64:
				return \base64_decode($string);

			case self::MODE_URL:
				return \urldecode($string);

			case self::MODE_UTF8:
				return \utf8_decode($string);
		}
	}


	/**
	 * Entities conversion
	 *
	 * @param $string String
	 * @param string $charset Charset
	 *
	 * @return string
	 */
	public static function entities($string, $charset = self::DEFAULT_CHARSET) {
		return \htmlentities($string, \ENT_QUOTES, $charset);
	}
}