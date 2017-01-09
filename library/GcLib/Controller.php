<?php

/**
 * This file is part of the GcLib library.
 */

namespace GcLib;

use GcLib\Request;
use GcLib\Response;
use GcLib\Controller\Front;
use GcLib\Exception\ControllerException;

/**
 * Controller
 *
 * @author T4lus
 * @version $Revision: 25 $
 * @date $Date: 2012-08-20 16:54:38 +0200 (Mon, 20 Aug 2012) $
 * @package GcLib
 */
abstract class Controller
{

	/** @var \GcLib\Request Request component */
	protected $_request;

	/** @var \GcLib\Response Response component */
	protected $_response;

	/** @var \GcLib\Controller\Front */
	protected $_frontController;

	/**
	 * Constructor
	 *
	 * @param \GcLib\Controller\Front $frontController Front controller component
	 * @param \GcLib\Request $request Request component
	 * @param \GcLib\Response $response Response component
	 */
	public function __construct(Front $frontController, Request $request, Response $response)
	{

		$this->_frontController	= $frontController;
		$this->_request			= $request;
		$this->_response		= $response;

		$this->init();
	}

	/**
	 * Forward
	 *
	 * @param string $actionName Action name
	 * @param null|string $controllerName Controller name
	 */
	public function forward($actionName, $controllerName = NULL)
	{
		$this->_frontController->forward($actionName, $controllerName);
	}

	/**
	 * Redirection
	 *
	 * @param string $uri Relative URI or Absolute URL
	 * @param boolean $permanent
	 */
	public function redirect($uri, $permanent = false)
	{
		$this->_response->redirect($uri, $permanent);
	}

	/**
	 * Initialisation of controller
	 */
	public function init()
	{

	}

	/**
	 * Pre dispatch
	 */
	public function preDispatch()
	{

	}

	/**
	 * Post dispatch
	 */
	public function postDispatch()
	{

	}

	/**
	 * @return \GcLib\Request
	 */
	public function getRequest()
	{
		return $this->_request;
	}

	/**
	 * @return \GcLib\Response
	 */
	public function getResponse()
	{
		return $this->_response;
	}

	/**
	 * @return \GcLib\Controller\Front
	 */
	public function getFrontController()
	{
		return $this->_frontController;
	}

}