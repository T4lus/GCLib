<?php

/**
 * This file is part of the GcLib library.
 */

namespace GcLib;

/**
 * Response
 *
 * @author T4lus
 * @version $Revision: 3 $
 * @date $Date: 2012-08-01 11:45:19 +0200 (Wed, 01 Aug 2012) $
 * @package GcLib
 */
class Response
{

	/**
	 * Redirect
	 *
	 * @param string $URI URI
	 * @param bool $permanent Permanent or temporary
	 */
	public static function redirect($URI, $permanent = true) {
		if (!\headers_sent()) {

			// Permanent redirection (301)
			if ($permanent === true) {
				\header('Status: 301 Moved Permanently', false, 301);
			}
			// Temporary redirection (302)
			else if ($permanent === false) {
				\header('Status: 302 Moved Temporarily', false, 302);
			}

			// Redirection
			\header('Location: '.$URI);
			exit;
		}
	}


	/**
	 * Set content type
	 *
	 * @param string $type Content type (E.g. image/jpg, application/json, ...)
	 */
	public static function setContentType($type) {
		if (!\headers_sent()) {
			\header('Content-type: '.$type);
		}
	}


	/**
	 * Disable cache
	 */
	public static function disableCache() {
		if (!\headers_sent()) {
			\header('Cache-Control: no-cache, must-revalidate');
			\header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		}
	}
}