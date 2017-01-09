<?php

/**
 * This file is part of the GcLib library.
 */

namespace GcLib;

use GcLib\Exception\ViewException;


/**
 * View
 *
 * @author T4lus
 * @version $Revision: 57 $
 * @date $Date: 2012-09-04 13:48:43 +0200 (Tue, 04 Sep 2012) $
 * @package GcLib
 */
class View 
{

	/** @var string Path to template */
	protected $_path = './';

	/** @var array Helpers */
	protected $_helpers = array();

	/** @var array Variables */
	protected $_variables = array();

	/**
	 * @param string $path View path
	 */
	public function __construct($path = NULL) {
		if (!\is_null($path)) {
			$this->_path = $path;
		}
		$this->addDefaultHelpers();
	}


	/**
	 * Set variables
	 *
	 * @param array $variables
	 */
	public function setVariables(array $variables = array()) {
		if (!empty($variables)) {
			foreach ($variables AS $key => $value) {
				$this->setVariable($key, $value);
			}
		}
	}


	/**
	 * Set variable
	 *
	 * @param string $key Key
	 * @param mixed $value
	 * @param bool $escape
	 */
	public function setVariable($key, $value, $escape = true) {
		if (\is_string($value) && $escape) {
			$value = \htmlentities($value, \ENT_QUOTES, 'UTF-8');
		}
		$this->_variables[$key] = $value;
	}


	/**
	 * Get variable
	 *
	 * @param string $key
	 *
	 * @return mixed|NULL
	 */
	public function getVariable($key) {
		return \array_key_exists($key, $this->_variables) ? $this->_variables[$key] : NULL;
	}


	/**
	 * Get route
	 *
	 * @param string $route
	 *
	 * @return string|NULL
	 */
	public function	getroute($route) {
		if(isset($GLOBALS['router'])){
			if(!empty($route)){
				return DIRECTORY_SEPARATOR.$GLOBALS['router']->getUrlByRoute($route);
			}
		}
		return NULL;
	}


	/**
	 * Add Helper
	 *
	 * @param string $pattern
	 * @param callback $callback
	 */
	public function addHelper($pattern, $callback) {
		$this->_helpers[$pattern] = $callback;
	}


	/**
	 * Add default helpers
	 */
	public function addDefaultHelpers() {
		$view = $this;

		// Variables
		$this->addHelper('/{([a-zA-Z0-9_]+)}/u', function($input) use ($view) {
			return $view->getVariable($input[1]);
		});

		// Includes
		$view->addHelper('/{include:([a-zA-Z0-9\/\._-]+)}/', function($input) use ($view) {
			return $view->parse($input[1]);
		});

		// Routes
		$view->addHelper('/{route:([a-zA-Z0-9\/\._-]+)}/', function($input) use ($view) {
			return $view->getRoute($input[1]);
		});
	}


	/**
	 * @param $filename
	 * @return mixed|string
	 * @throws Exception\ViewException
	 */
	public function parse($filename) {
		$raw = '';
		$file = $this->_path.$filename;

		if (\file_exists($file)) {
			\ob_start();
			include $file;
			$raw = \ob_get_contents();
			\ob_end_clean();

			if (!empty($this->_helpers)) {
				foreach ($this->_helpers as $pattern => $callback) {
					$raw = \preg_replace_callback($pattern, $callback, $raw);
				}
			}
		}
		else {
			throw new ViewException('View error: Unable to load template file "'.$file.'".');
		}

		return $raw;
	}

}