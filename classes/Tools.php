<?php

namespace Classes;

/**
 * Tools
 */

class Tools
{	

	/**
	 * This function turns HTML into text... 
	 * strips tags, comments spanning multiple lines including CDATA, 
	 * and anything else that gets in it's way. 
	 * 
	 * @param type $string
	 * @return string
	 */
	public static function html2txt($string) {
		$search = array('@<script[^>]*?>.*?</script>@si',	// Strip out javascript
						'@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
						'@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
						'@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
		);
		$text = preg_replace($search, '', $string);
		return $text;
	} 
}