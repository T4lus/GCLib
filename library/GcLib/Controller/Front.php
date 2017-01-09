<?php

/**
 * This file is part of the GcLib library.
 */

namespace GcLib\Controller;

use GcLib\Request;
use GcLib\Response;
use GcLib\Exception\FrontControllerException;


/**
 * Controller\Front
 *
 * @author T4lus
 * @version $Revision: 61 $
 * @date $Date: 2012-09-10 14:01:05 +0200 (Mon, 10 Sep 2012) $
 * @package GcLib
 */

class Front
{

	/** @const string Controllers namespace */
	const CONTROLLERS_NAMESPACE = '\\Controllers\\';

	/** @const string Action suffix */
	const ACTION_SUFFIX = 'Action';

	/** @const string XHR suffix */
	const XHR_SUFFIX = 'XHR';

	/** @var string Default controller */
	protected $_defaultControllerName = 'Index';

	/** @var string Default action */
	protected $_defaultActionName = 'index';

	/** @var string Default error controller */
	protected $_defaultErrorControllerName = 'Error';

	/** @var Request Request component */
	protected $_request;

	/** @var Response Response component */
	protected $_response;

	/** @var string Controller name */
	protected $_controllerName;

	/** @var string Action name */
	protected $_actionName;

	/** @var bool Is dispatched */
	protected $_isDispatched = false;

	/** @var array Dispatching history */
	protected $_dispatchingHistory = array();



	/**
	 * Constructor
	 *
	 * @param Request|null $request Request component
	 * @param Response|null $response Response component
	 */

	public function __construct(Request $request = NULL, Response $response = NULL)
	{
		$this->_request = \is_null($request) ? new Request : $request;
		$this->_response = \is_null($response) ? new Response : $response;
	}


	/**
	 * Dispatch
	 *
	 * @return mixed
	 * @throws \GcLib\Exception\FrontControllerException
	 */

	public function dispatch()
	{
		$output = '';

		if (empty($this->_controllerName))
		{
			$this->_controllerName = $this->_defaultControllerName;
		}

		if (empty($this->_actionName))
		{
			$this->_actionName = $this->_defaultActionName;
		}

		while(!$this->_isDispatched)
		{
			// Put to history
			$this->_addDispatchingHistory($this->_controllerName, $this->_actionName);

			// Check controller existence
			if (!$this->_controllerExists($this->_controllerName))
			{
				if ($this->_controllerName != $this->_defaultErrorControllerName)
				{
					$this->_controllerName	= $this->_defaultErrorControllerName;
					$this->_actionName		= $this->_defaultActionName;
				}
				else
				{
					throw new FrontControllerException('Front Controller Error: Unable to found error controller.');
				}
			}
			// Instance of controller
			else
			{
				$controllerNameOrigin = $this->_controllerName;
				$controllerName = self::CONTROLLERS_NAMESPACE.$this->_controllerName;
				$controller = new $controllerName($this, $this->_request, $this->_response);

				// Pre dispatch
				$controller->preDispatch();
				if ($controllerNameOrigin != $this->_controllerName)
				{
					continue;
				}
				$actionName = $this->_actionName.self::ACTION_SUFFIX;

				// Finish dispatch
				if (\method_exists($controller, $actionName.self::XHR_SUFFIX) && $this->_request->isXHR())
				{
					$this->_isDispatched = true;

					// Call XHR action
					$actionName .= self::XHR_SUFFIX;
					echo $controller->$actionName();
				}

				elseif (\method_exists($controller, $actionName))
				{
					$this->_isDispatched = true;

					// Call action
					$output = $controller->$actionName();
				}

				// Go to error controller
				else
				{
					$this->_controllerName	= $this->_defaultErrorControllerName;
					$this->_actionName		= $this->_defaultActionName;
				}
			}
		}

		// Send output
		echo $output;

		// Post dispatch
		$controller->postDispatch();
	}



	/**
	 * Forward
	 *
	 * @param string $actionName Action name
	 * @param null|string $controllerName Controller name
	 */

	public function forward($actionName, $controllerName = NULL)
	{
		$this->_isDispatched = false;
		$this->_actionName = $actionName;
		if (!\is_null($controllerName))
		{
			$this->_controllerName = $controllerName;
		}
	}

	/**
	 * Set action name
	 *
	 * @param string $actionName
	 */
	public function setActionName($actionName)
	{
		$this->_actionName = $actionName;
	}



	/**
	 * Get action name
	 *
	 * @return string
	 */

	public function getActionName()
	{
		return $this->_actionName;
	}



	/**
	 * Set controller name
	 *
	 * @param string $controllerName
	 */
	public function setControllerName($controllerName)
	{
		$this->_controllerName = $controllerName;
	}



	/**
	 * Get controller name
	 *
	 * @return string
	 */
	public function getControllerName()
	{
		return $this->_controllerName;
	}



	/**
	 * Set is dispatched
	 *
	 * @param boolean $isDispatched
	 */
	public function setIsDispatched($isDispatched)
	{
		$this->_isDispatched = (bool)$isDispatched;
	}



	/**
	 * Get is dispatched
	 *
	 * @return boolean
	 */
	public function getIsDispatched()
	{
		return $this->_isDispatched;
	}



	/**
	 * Get dispatching history
	 *
	 * @return array
	 */
	public function getDispatchingHistory()
	{
		return $this->_dispatchingHistory;
	}



	/**
	 * Controller Exists
	 *
	 * @param string $controllerName Controller name
	 *
	 * @return bool
	 */
	protected function _controllerExists($controllerName)
	{
		return \class_exists(self::CONTROLLERS_NAMESPACE.$controllerName);
	}


	/**
	 * Add dispatching history
	 *
	 * @param string $controllerName Controller name
	 * @param string $actionName Action name
	 */
	protected function _addDispatchingHistory($controllerName, $actionName)
	{
		$this->_dispatchingHistory[] = array(
			'controllerName'	=> $controllerName,
			'actionName'		=> $actionName,
		);
	}
}