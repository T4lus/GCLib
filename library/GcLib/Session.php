<?php

/**
 * This file is part of the GcLib library.
 */



namespace GcLib;

class Session
{
	/** @string Session ID */
	const SESSION_ID = 'GcLib';

	/** @string Authentication key */
	const AUTH_KEY = 'authentication';

	/** @string Flash message key */
	const FLASH_MESSAGE_KEY = 'flash.message';

	/** @var Session instances */
	protected static $_instance;



	/**
	 * Get Instance
	 *
	 * @return Session instance
	 */
	public static function getInstance() {
		if (!(self::$_instance instanceof self)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	/**
	 * Constructor
	 */
	private function __construct() {
		if (!\headers_sent()) {
			\session_name(self::SESSION_ID);
			\session_start();
		}
	}


	/**
	 * Stop session
	 */
	public function stop() {
		\session_unset();
		\session_destroy();
	}


	/**
	 * Get data
	 *
	 * @param string $key Key
	 * @return mixed
	 */
	public function getData($key) {
		return isset($_SESSION[$key]) ? $_SESSION[$key] : NULL;
	}


	/**
	 * Set data
	 *
	 * @param string $key Key
	 * @param mixed $data Data
	 */
	public function setData($key, $data) {
		$_SESSION[$key] = $data;
	}


	/**
	 * Get flash message
	 *
	 * @return mixed
	 */

	public function getFlashMessage() {
		$value = NULL;
		if (isset($_SESSION[self::FLASH_MESSAGE_KEY])) {
			$value = $_SESSION[self::FLASH_MESSAGE_KEY];
			unset($_SESSION[self::FLASH_MESSAGE_KEY]);
		}
		return $value;
	}


	/**
	 * Set flash message
	 *
	 * @param string|int|bool $message Message
	 */
	public function setFlashMessage($message) {
		$_SESSION[self::FLASH_MESSAGE_KEY] = $message;
	}


	/**
	 * Auth: Login user
	 */
	public function authLogin()	{
		$identity  = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''; // @todo Refactor in Request component
		//$identity .= Request::getIPAddress();
		$_SESSION[self::AUTH_KEY] = array(
			'data'		=> array(),
			'identity'	=> \md5($identity),
		);
	}


	/**
	 * Auth: Logout user
	 */
	public function authLogout() {
		unset($_SESSION[self::AUTH_KEY]);
	}


	/**
	 * Auth: Check user logged
	 *
	 * @return bool
	 */
	public function authIsLogged() {
		$identity = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''; // @todo Refactor in Request component
		//$identity .= Request::getIPAddress();
		return isset($_SESSION[self::AUTH_KEY]) &&
			isset($_SESSION[self::AUTH_KEY]['identity']) &&
			$_SESSION[self::AUTH_KEY]['identity'] == \md5($identity);
	}


	/**
	 * Auth: Set data
	 *
	 * @param mixed $data
	 */
	public function authSetData($data) {
		$_SESSION[self::AUTH_KEY]['data'] = $data;
	}


	/**
	 * Auth: Get data
	 *
	 * @return mixed
	 */
	public function authGetData() {
		if ($this->authIsLogged()) {
			return $_SESSION[self::AUTH_KEY]['data'];
		}
		return NULL;
	}
}