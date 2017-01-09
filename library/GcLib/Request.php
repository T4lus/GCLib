<?php

/**
 * This file is part of the GcLib library.
 */

namespace GcLib;

/**
 * Request
 *
 * @author T4lus
 * @version $Revision: 73 $
 * @date $Date: 2013-08-02 09:22:14 +0200 (Fri, 02 Aug 2013) $
 * @package GcLib
 */

class Request
{

	/** HTTP Methods */

	const	METHOD_HEAD		= 'head',
			METHOD_GET		= 'get',
			METHOD_POST		= 'post',
			METHOD_PUT		= 'put',
			METHOD_DELETE	= 'delete';



	/**
	 * Get all params
	 *
	 * @param array $params List of params (ignored if $whiteList is false, allowed if $whiteList is true)
	 * @param bool $whiteList Allow params
	 *
	 * @return array Couple of keys/values params
	 */

	public static function getParams($params = array(), $whiteList = false)
	{
		$paramsList = array_merge($_GET, $_POST);
		$function = $whiteList ? '\array_intersect_key' : '\array_diff_key';

		return $function($paramsList, \array_flip($params));
	}

	public static function getPostParams($params = array(), $whiteList = false)
	{
		$paramsList = $_POST;
		$function = $whiteList ? '\array_intersect_key' : '\array_diff_key';

		return $function($paramsList, \array_flip($params));
	}

	public static function getGetParams($params = array(), $whiteList = false)
	{
		$paramsList = $_GET;
		$function = $whiteList ? '\array_intersect_key' : '\array_diff_key';

		return $function($paramsList, \array_flip($params));
	}

	public static function getJsonParams($params = array(), $whiteList = false)
	{
		$paramsList = array();
		$paramsList = json_decode(file_get_contents("php://input"), true);
		if (gettype($paramsList) != 'array')
			$paramsList = array();
		$function = $whiteList ? '\array_intersect_key' : '\array_diff_key';

		return $function($paramsList, \array_flip($params));
	}

	public static function getHeaderParams($params = array(), $whiteList = false)
	{
		$paramsList = \getallheaders();
		$function = $whiteList ? '\array_intersect_key' : '\array_diff_key';

		return $function($paramsList, \array_flip($params));
	}

	/**
	 * Get request has param
	 *
	 * @param string $key Param Key
	 * @param mixed $defaultValue Default value
	 *
	 * @return bool Success
	 */

	public static function getParam($key, $defaultValue = false)
	{
		// Check bad key
		if (!\is_string($key))
		{
			return false;
		}

		// Get value of key in GET and POST array
		return (isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $defaultValue));
	}

	/**
	 * Get file
	 *
	 * @param string $key File key
	 *
	 * @return array File info
	 */
	public static function getFile($key)
	{
		$files = array();

		if (isset($_FILES[$key]))
		{
			// Single file
			if (isset($_FILES[$key]['name']) && !is_array($_FILES[$key]['name']))
			{
				$files = array($_FILES[$key]);
			}
			// Multi files
			else
			{
				$numFiles = \count($_FILES[$key]['name']);

				for ($file = 0; $file < $numFiles; $file++)
				{
					$properties = array();
					foreach ($_FILES[$key] as $property => $data)
					{
						$properties[$property] = $_FILES[$key][$property][$file];
					}
					$files[] = $properties;
				}
			}
		}
		return $files;
	}

	/**
	 * Get files
	 *
	 * @return array Files info
	 */
	public static function getFiles()
	{
		$files = array();

		if (!empty($_FILES))
		{
			foreach ($_FILES as $file => $data)
			{
				$files[$file] = self::getFile($file);
			}
		}
		return $files;
	}

	/**
	 * Check if request has param
	 *
	 * @param string $key Param Key
	 *
	 * @return bool Success
	 */
	public static function hasParam($key)
	{
		return isset($_POST[$key]) || isset($_GET[$key]);
	}

	/**
	 * Check if request has file
	 *
	 * @param string $key File Key
	 *
	 * @return bool Success
	 */
	public static function hasFile($key)
	{
		return isset($_FILES[$key]);
	}

	/**
	 * Check if request is provided by XHR (AJAX)
	 *
	 * @return bool Success
	 */
	public static function isXHR()
	{
		return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}

	/**
	 * Get HTTP method
	 *
	 * @return string|boolean Method (POST|GET|PUT|DELETE|HEAD|...)
	 */
	public static function getMethod()
	{
		return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false;
	}



	/**
	 * Get IP address
	 *
	 * @return bool|string IP address or failed
	 */
	public static function getIPAddress()
	{
		$serverKeys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
		foreach ($serverKeys as $key)
		{
			if (\array_key_exists($key, $_SERVER) === true)
			{
				foreach (\explode(',', $_SERVER[$key]) as $ip)
				{
					$ip = \trim($ip);
					// Valid IP address (with Validator)
					if (\class_exists('\GcLib\Validator'))
					{
						if (\GcLib\Validator::ip($ip))
						{
							return $ip;
						}
					}
					else
					{
						return $ip;
					}
				}
			}
		}
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
	}



	/**
	 * Get Url
	 *
	 * @param bool $withDomain With domain
	 * @param string $base Base
	 *
	 * @return string
	 */
	public static function getUrl($withDomain = true, $base = '')
	{
		$url = '';

		if ($withDomain)
		{
			$url .= 'http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on').'://';
			$url .= $_SERVER['SERVER_NAME'];
			if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80')
			{
				$url .= ':'.$_SERVER['SERVER_PORT'];
			}
		}
		$url .= \substr($_SERVER['REQUEST_URI'], \strlen($base) + 1);
		return $url;
	}
}