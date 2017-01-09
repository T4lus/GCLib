<?php

namespace Classes;

/**
 * Encrypt
 */

class Encrypt
{

	/**
	 * Encrypt with a salt md5(sha1())
	 * 
	 * @param type $str, $salt
	 * @return boolean
	 */
	public static function encrypt($str, $salt) {
		if(isset($str) && isset($salt))
			return \md5(\sha1($str . $salt));
	}
}