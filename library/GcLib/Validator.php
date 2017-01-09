<?php

/**
 * This file is part of the GcLib library.
 */

namespace GcLib;

/**
 * Validator
 *
 * @author T4lus
 * @version $Revision: 73 $
 * @date $Date: 2013-08-02 09:22:14 +0200 (Fri, 02 Aug 2013) $
 * @package GcLib
 */
class Validator
{

	/**
	 * Valid: E-Mail
	 * E.g: t4lus@global-chaos.fr
	 *
	 * @param string $email EMail
	 *
	 * @return bool Valid
	 */
	public static function email($email) {
		return !empty($email) && (bool)\preg_match('/^[a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+[._a-z\p{L}0-9-]*\.[a-z0-9]+$/ui', $email);
	}


	/**
	 * Valid: MD5
	 * E.g: 80093791606455f2ba63e5c434b4f19c
	 *
	 * @param string $md5 MD5
	 *
	 * @return bool Valid
	 */
	public static function MD5($md5) {
		return (bool)\preg_match('/^[a-f0-9A-F]{32}$/', $md5);
	}


	/**
	 * Valid: SHA1
	 * E.g: AFBA34A2A11AB13EEBA5D0A7AA22BBB6120E177B
	 *
	 * @param string $sha1 SHA1
	 *
	 * @return bool Valid
	 */
	public static function SHA1($sha1) {
		return (bool)\preg_match('/^[a-fA-F0-9]{40}$/', $sha1);
	}


	/**
	 * Valid: Name
	 * E.g: John
	 *
	 * @param string $name Name
	 *
	 * @return bool Valid
	 */
	public static function name($name)  {
		return (bool)\preg_match('/^[^0-9!<>,;?=+()@#"Â°{}_$%:]*$/u', stripslashes($name));
	}


	/**
	 * Valid: Text
	 * E.g: Lorem ipsum dolor...
	 *
	 * @param string $text Text
	 *
	 * @return bool Valid
	 */
	public static function text($text) {
		if(is_array($text)) {
			$flag = true;

			foreach($text as $t)
				if(!(bool)\preg_match('/[<>{}]/i', $text)===false)
					$flag = false;

			return $flag;
		}
		else {
			return !(bool)\preg_match('/[<>{}]/i', $text);
		}
	}


	/**
	 * Valid: Integer
	 * E.g: -123, 123
	 *
	 * @param string $integer Integer
	 *
	 * @return bool Valid
	 */
	public static function integer($integer) {
		if(is_array($integer)) {

			$flag = true;

			foreach($integer as $int)
				if ((string)(int)$integer === (string)$integer || $integer === false)
					$flag = false;

			return $flag;
		}
		else {
			return ((string)(int)$integer === (string)$integer || $integer === false);
		}
	}


	/**
	 * Valid: Unsigned integer
	 * E.g: 123
	 *
	 * @param string $integer Integer
	 *
	 * @return bool Valid
	 */
	public static function unsignedInteger($integer) {
		return (\preg_match('#^[0-9]+$#', (string)$integer) && $integer < 4294967296 && $integer >= 0);
	}


	/**
	 * Valid: Float
	 * E.g: 99.9, -99.9
	 *
	 * @param string $float Float
	 *
	 * @return bool Valid
	 */
	public static function float($float) {
		return \strval((float)$float) == \strval($float);
	}


	/**
	 * Valid: Unsigned float
	 * E.g: 99.9
	 *
	 * @param string $float Float
	 *
	 * @return bool Valid
	 */
	public static function unsignedFloat($float) {
		return \strval((float)$float) == \strval($float) && $float >= 0;
	}


	/**
	 * Valid: Bool
	 * E.g: 0, false, true, 1, NULL
	 *
	 * @param string $boolean Bool
	 *
	 * @return bool Valid
	 */
	public static function boolean($boolean) {
		if(is_array($boolean)) {

			$flag = true;

			foreach($boolean as $bool)
				if (\is_null($bool) || \is_bool($bool) || (bool)\preg_match('/^0|1$/', $bool) === false)
					$flag = false;

			return $flag;
		}
		else {
			return \is_null($boolean) || \is_bool($boolean) || (bool)\preg_match('/^0|1$/', $boolean);
		}
	}


	/**
	 * Valid: Float
	 * E.g: 2012-06-05 15:57:32, 2012-06-05
	 *
	 * @param string $date Date
	 *
	 * @return bool Valid
	 */
	public static function date($date) {
		if (!\preg_match('/^([0-9]{4})-((0?[0-9])|(1[0-2]))-((0?[0-9])|([1-2][0-9])|(3[01]))( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/', $date, $matches))
			return false;
		return \checkdate((int)$matches[2], (int)$matches[5], (int)$matches[0]);
	}


	/**
	 * Valid: Ip
	 * E.g: 127.0.0.1 - ::1
	 *
	 * @param string $ip IP Address
	 *
	 * @return bool Valid
	 */
	public static function ip($ip) {
		return (\filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false);
	}


	/**
	 * Valid: HTML
	 * E.g: <strong>Hello</strong> ...
	 * (c) PrestaShop
	 *
	 * @param string $html HTML code
	 *
	 * @return bool Valid
	 */
	public static function html($html) {
		$events = 'onmousedown|onmousemove|onmmouseup|onmouseover|onmouseout|onload|onunload|onfocus|onblur|onchange';
		$events .= '|onsubmit|ondblclick|onclick|onkeydown|onkeyup|onkeypress|onmouseenter|onmouseleave|onerror|onselect|onreset|onabort|ondragdrop|onresize|onactivate|onafterprint|onmoveend';
		$events .= '|onafterupdate|onbeforeactivate|onbeforecopy|onbeforecut|onbeforedeactivate|onbeforeeditfocus|onbeforepaste|onbeforeprint|onbeforeunload|onbeforeupdate|onmove';
		$events .= '|onbounce|oncellchange|oncontextmenu|oncontrolselect|oncopy|oncut|ondataavailable|ondatasetchanged|ondatasetcomplete|ondeactivate|ondrag|ondragend|ondragenter|onmousewheel';
		$events .= '|ondragleave|ondragover|ondragstart|ondrop|onerrorupdate|onfilterchange|onfinish|onfocusin|onfocusout|onhashchange|onhelp|oninput|onlosecapture|onmessage|onmouseup|onmovestart';
		$events .= '|onoffline|ononline|onpaste|onpropertychange|onreadystatechange|onresizeend|onresizestart|onrowenter|onrowexit|onrowsdelete|onrowsinserted|onscroll|onsearch|onselectionchange';
		$events .= '|onselectstart|onstart|onstop';

		return !(bool)\preg_match('/<[ \t\n]*script/ims', $html) &&
			!(bool)\preg_match('/<?.*('.$events.')[ \t\n]*=/ims', $html) &&
			!(bool)\preg_match('/.*script\:/ims', $html) &&
			!(bool)\preg_match('/<[ \t\n]*i?frame/ims', $html);
	}
}
