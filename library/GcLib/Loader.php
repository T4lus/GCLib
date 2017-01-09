<?php

/**
 * This file is part of the GcLib library.
 */

namespace GcLib;


/**
 * Loader
 *
 * @author T4lus
 * @version $Revision: 73 $
 * @date $Date: 2013-08-02 09:22:14 +0200 (Fri, 02 Aug 2013) $
 * @package GcLib
 */

class Loader
{
	/** @var string Root path */
	protected $_rootPath = '';

	/** @var array Namespaces */
	protected $_namespaces = array();

	/** @var string time */
	public $_time = '';

	/**
	 * Constructor
	 *
	 * @param string $rootPath Root path
	 */
	public function __construct($rootPath = '') {
		// Check set GcLib version
		if (!defined('GcLib_VERSION')) die('Error : please > defined your GcLib version !');

		// Active microtime debug
		if(defined('DEBUG') && constant('DEBUG') === true) {$this->_time = $this->getMicrotime();}

		
		if (!empty($rootPath))
		{
			$this->_rootPath = $rootPath;
		}
	}


	/**
	 * Set root path
	 *
	 * @param string $rootPath Root path
	 *
	 * @return Loader
	 */
	public function setRootPath($rootPath) {
		$this->_rootPath = $rootPath;

		return $this;
	}


	/**
	 * Register namespaces
	 *
	 * @param array $namespaces Namespaces
	 *
	 * @return Loader
	 */
	public function registerNamespaces(array $namespaces) {
		foreach ($namespaces as $namespace => $path)
		{
			$this->registerNamespace($namespace, $path);
		}

		return $this;
	}


	/**
	 * Register namespace
	 *
	 * @param string $namespace Namespace
	 * @param string $path Path to files
	 *
	 * @return Loader
	 */
	public function registerNamespace($namespace, $path) {
		$this->_namespaces[$namespace] = $path;

		return $this;
	}


	/**
	 * Register loader
	 *
	 * @param callback $callback Loader (E.g. class, function, object...)
	 *
	 * @return bool Success
	 */
	public function register($callback = NULL) {
		if (\is_null($callback))
		{
			$callback = array($this, 'defaultLoader');
		}

		return \spl_autoload_register($callback);
	}


	/**
	 * Default loader
	 *
	 * @param string $namespaceWithClass Name of class
	 *
	 * @return boolean Success
	 */
	public function defaultLoader($namespaceWithClass) {
		$namespaces	= self::getNamespace($namespaceWithClass);
		$namespaces = \explode('\\', $namespaces);

		$namespace	= current($namespaces);
		$class		= self::getClass($namespaceWithClass);

		if (\array_key_exists($namespace, $this->_namespaces))
		{

			$path  = $this->_rootPath.DIRECTORY_SEPARATOR;
			$path .= $this->_namespaces[$namespace];

			if (\count($namespaces) > 1)
			{
				\array_shift($namespaces);
				$path .= \implode(DIRECTORY_SEPARATOR, $namespaces);
			}
			$path  = \realpath($path).DIRECTORY_SEPARATOR;
			$file = $path.$class.'.php';

			if (\file_exists($file))
			{
				require $file;
				return true;
			}
		}
		return false;
	}


	/**
	 * Get namespace
	 *
	 * @param string $namespaceWithClass Class name
	 *
	 * @return string Namespace
	 */
	protected static function getNamespace($namespaceWithClass) {
		return \substr($namespaceWithClass, 0, \strrpos($namespaceWithClass, '\\'));
	}


	/**
	 * Get class
	 *
	 * @param string $namespaceWithClass Class name
	 *
	 * @return string Class
	 */
	protected static function getClass($namespaceWithClass) {
		return \substr($namespaceWithClass, \strrpos($namespaceWithClass, '\\') + 1, \strlen($namespaceWithClass));
	}


	/**
	 * Get microtime
	 *
	 * @return string Microtime
	 */
	public function getMicrotime() {
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}

}