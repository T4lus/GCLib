<?php
namespace Controllers;

// Components
use GcLib\Controller;
use GcLib\Database;
use GcLib\Session;
use GcLib\View;
use GcLib\Request;
use GcLib\Validator;
use Classes\Encrypt;
use Classes\Logger;

use TemplateEngine\Template;


use Models\User;

/**
 * Common controller
 *
 * Tools & Helper
 */
class Common extends Controller
{
	protected $_session;
	protected $_view;
	protected $_template;
	protected $_db;
	protected $_page;
	protected $_action;
	protected $_assign;
	protected $_errors;
	protected $_user = NULL;
	protected $_session_id = NULL;
	
    
	public function init()
	{
            $this->_session     = Session::getInstance();
            $this->_view        = new View(PATH_VIEWS);
            $this->_db          = Database::getInstance();
            $this->_page        = $this->_frontController->getControllerName();
            $this->_action      = $this->_frontController->getActionName();
            $this->_errors      = array();
            $this->_assign      = array();
            
            $this->_template	= new Template();
		  	$this->_template->tplDir = PATH_VIEWS;
			$this->_template->cacheDir = '/cache/';
			$this->_template->compileDir = '/compiled/';
          		
            // Helper : format date closure 
            $this->_view->helperFormatDate1 = function($date, $format = 'd/m/Y')
            {
                $date = new \DateTime($date);
                return $date->format($format);
            };
            
            // Helper : format date closure 
            $this->_view->helperFormatDate2 = function($date, $format = 'd/m/Y à H:i:s')
            {
                $date = new \DateTime($date);
                return $date->format($format);
            };
        
            // Helper path
            $this->_view->helperPath = function($route)
            {
                return call_user_func(array(__CLASS__, 'getRoute'), $route);
            };
        
            // set assign vars
            $this->_view->setVariables($this->_assign);
			
			$this->_session_id = $this->_session->getData('session');
			
			if($this->_session_id)
			{
				$user = User::findAll('id = :id', array('id' => $this->_session_id));
				
				$this->_user = array_shift($user);				
			}
	}
	
	/**
	 * getIPforwarded
	 * @return string
	 */
	public function getIPforwarded()
	{
		$ip = null;
		
		if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && is_array($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		
		return $ip;		
	}
    
	/*
	 * Helper GetRoute
	 */
	public function getRoute($route)
	{
		if(array_key_exists('routes', $GLOBALS))
		{
			if(!empty($GLOBALS['routes'][$route]))
			{
				$key = (array_key_exists('path', $GLOBALS['routes'][$route])) ? 'path' : 'rule';

				if(!empty($GLOBALS['routes'][$route][$key]))
					return SITE_URL.$GLOBALS['routes'][$route][$key];
			}
		}
		return SITE_URL;
    }
    
    //////////////////////////////////////////////////////////////////////////// DO NOT EDIT : Authentication management

	/**
	 * Parse HTTP Digest
	 *
	 * @param string $txt
	 * @return array|bool
	 */
	protected function _httpDigestParse($txt)
	{
		$needed_parts = array(
			'nonce'		=> 1,
			'nc'		=> 1,
			'cnonce'	=> 1,
			'qop'		=> 1,
			'username'	=> 1,
			'uri'		=> 1,
			'response'	=> 1,
		);
		$data = array();
		$keys = \implode('|', \array_keys($needed_parts));

		\preg_match_all('@('.$keys.')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, \PREG_SET_ORDER);

		foreach ($matches as $m)
		{
			$data[$m[1]] = $m[3] ? $m[3] : $m[4];
			unset($needed_parts[$m[1]]);
		}

		return $needed_parts ? false : $data;
	}

	/**
	 * HTTP Authentication
	 *
	 * @param string $realm Sentence of connection
 	 * @param array $users Users (e.g. array('admin' => 'admin'))
	 */
	protected function _httpAuthentication($realm, $users)
	{
		\header('Content-type: text/html; charset:utf-8');

		if (empty($_SERVER['PHP_AUTH_DIGEST']))
		{
			\header('HTTP/1.1 401 Unauthorized');
			\header('WWW-Authenticate: Digest realm="'.$realm.'",qop="auth",nonce="'.\uniqid().'",opaque="'.\md5($realm).'"');

			exit('Forbidden');
		}

		if (!($data = $this->_httpDigestParse($_SERVER['PHP_AUTH_DIGEST'])) || !isset($users[$data['username']]))
		{
			exit('Forbidden');
		}

		$A1 = \md5($data['username'].':'.$realm.':'.$users[$data['username']]);
		$A2 = \md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
		$valid_response = \md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);

		if ($data['response'] != $valid_response)
		{
			exit('Forbidden');
		}
	}
}