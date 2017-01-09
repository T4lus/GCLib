<?php

/**
 * This file is part of the GcLib library.
 */

namespace GcLib;

use GcLib\Exception\RouterException;


/**
 * @author T4lus
 * @version $Revision: 73 $
 * @date $Date: 2013-08-02 09:22:14 +0200 (Fri, 02 Aug 2013) $
 * @package GcLib
 */
class Router
{

	/** Easy pattern™ */
	const	EP_ALL				= ':all:',
			EP_ALPHA			= ':alpha:',
			EP_NUM				= ':num:',
			EP_ALPHA_NUM		= ':alpha-num:',
			EP_ALPHA_NUM_EXT	= ':alpha-num-ext:';

	/** @var array Routes collection */
	protected $_routes = array();

	/** @var string URL */
	protected $_url = '';

	/** @var string Current route */
	protected $_current = '';

	/** @var string Current route line */
	protected $_routeline = array();

	/** @var string Current breadcrumb */
	protected $_breadcrumb = array();

	/** @var string Index display name roote */
	protected $_breadcrumbHome = 'Accueil';

	/** @var string Index name roote */
	protected $_routeIndex = 'homepage';

	/** @var array Easy patterns */
	protected $_easyPatterns = array(
		self::EP_ALL			=> '.*',
		self::EP_ALPHA_NUM_EXT	=> '[a-zA-Z0-9_-]+',
		self::EP_ALPHA_NUM		=> '[a-zA-Z0-9]+',
		self::EP_NUM			=> '[0-9]+',
		self::EP_ALPHA			=> '[a-zA-Z]+',
	);



	/**
	 * Constructor
	 *
	 * @param string|NULL $url URL
	 */
	public function __construct($url = NULL)
	{
		if (!\is_null($url))
		{
			$this->_url = $url;
		}
	}

	/**
	 * Add route
	 *
	 * @param string $name Route name
	 * @param string $rule Rule
	 * @param array $keywords Keywords values
	 * @param array $parameters Parameters values
	 *
	 * @return Router
	 */
	public function addRoute($name, $rule, array $keywords = array(), array $parameters = array(), $menu = array())
	{
		$route = new \stdClass();
		$route->rule		= $rule;
		$route->keywords	= $keywords;
		$route->parameters	= $parameters;
		$route->menu		= $menu;
		$route->regex		= '';
		$this->_routes[$name] = $route;
		return $this;
	}

	/**
	 * Add routes
	 *
	 * @param array $routes Routes
	 *
	 * @throws Exception\RouterException
	 */
	public function addRoutes(array $routes)
	{
		if (!empty($routes))
		{
			foreach ($routes as $name => $route)
			{
				if (isset($route['rule']))
				{
					$rule		= $route['rule'];
					$keywords	= isset($route['keywords']) ? $route['keywords'] : array();
					$parameters	= isset($route['parameters']) ? $route['parameters'] : array();
					$menu		= isset($route['menu']) ? $route['menu'] : array();

					$this->addRoute($name, $rule, $keywords, $parameters, $menu);
				}
				else
				{
					throw new RouterException('Invalid arguments for route: '.$name);
				}
			}
		}
	}

	/**
	 * Delete route
	 *
	 * @param string $name Route name
	 *
	 * @return Router
	 */
	public function deleteRoute($name)
	{
		unset($this->_routes[$name]);
		return $this;
	}

	/**
	 * Get routes
	 *
	 * @return array
	 */
	public function getRoutes()
	{
		return $this->_routes;
	}

	/**
	 * Get clean current url
	 *
	 * @return string
	 */
	public function	getCleanUrl()
	{
		$url = explode('?', $this->_url);
		return $url[0];	
	}

	/**
	 * Get an url with route
	 *
	 * @return array
	 */
	public function	getUrlByRoute($route, $params = array())
	{
		if (($myRoute = $this->existsRoute($route)) === false):
			throw new RouterException('Unknown route : '.$route);
		else:
			if (!empty($params)):
				/** manage dynamic url /blog/(:num:) */
			else:
				return $myRoute->rule;
			endif;
		endif;
	}

	/**
	 * Get values of an existing route
	 *
	 * @return array|bool
	 */
	public function	existsRoute($route)
	{
		$find = false;
		if (empty($route)) return false;
		if (empty($this->_routes)): return false;
		else:
			foreach ($this->_routes as $key => $value):
				if ($key == $route) $find = $value;
			endforeach;
		endif;
		return $find;
	}

	/**
	 * Matcher
	 *
	 * @return array|bool
	 */
	public function matcher()
	{
		$routeFound = false;
		if (!empty($this->_routes)):
			foreach ($this->_routes as $name => $route):
				if(!$routeFound): 
					$theroute = str_replace('/', '\/', $route->rule);
					$theroute = str_replace(array_keys($this->_easyPatterns), array_values($this->_easyPatterns), $route->rule);
					if ((bool)preg_match('#^'.$theroute.'$#i', $this->getCleanUrl(), $matches)):
						$routeFound = true;
						$this->_current = $name;
						foreach ($route->keywords as $key => $value): $_GET[$key] = $value; endforeach;
						foreach ($matches as $key => $value): $_GET[$key] = $value; endforeach;
					endif;
				endif;
			endforeach;
		endif;
		$this->setRouteline();
		if (count($this->_routeline > 1))
			$this->_routeline = array_reverse($this->_routeline);
	}

	

	/**
	 * Set path current route
	 *
	 */
	public function setRouteline($level=0, $parent='')
	{
		if ($this->_current == $this->_routeIndex):
			return '';
		elseif($level==0):
			$route = $this->_current;
		else: 
			$route = $parent;
		endif;

		if (!empty($this->_routes)):
			if (array_key_exists($route, $this->_routes)):
				$myRoute = $this->_routes[$route];
				if (isset($myRoute->menu['name'])):	
					$menu = $myRoute->menu['name'];
					if(is_array($menu)):
						if(class_exists('\\Models\\'.$menu['model'])):
							$param = \GcLib\Request::getParam($menu['param']);
							$models = call_user_func_array(array('\\Models\\'.$menu['model'], 'findAll'), array($menu['field'].'='.$param));
							if(is_array($models)): foreach($models as $model):
								$attribute = $menu['attribute'];
								$name = $model->$attribute;
							endforeach; else: $name = ''; endif;
							$this->_routeline[$route] = $name;
						endif;

					else:
						$this->_routeline[$route] = $myRoute->menu['name'];
					endif;
				else:
					$this->_routeline[$route] = $route;
				endif;
				

				if(!empty($myRoute->menu['parent'])):
					$this->setRouteline($level+1, $myRoute->menu['parent']);
				elseif($route != $this->_routeIndex):
					if (!empty($this->_breadcrumbHome))
						$this->_routeline[$this->_routeIndex] = $this->_breadcrumbHome;
				endif;
			endif;
		endif;
	}

	/**
	 * Get current page breadcrumb
	 *
	 */
	public function	getBreadcrumb()
	{
		return $this->_breadcrumb;
	}

	/**
	 * Set breadcrumb
	 *
	 */
	public function	setBreadcrumb($container='breadcrumb', $link=true, $before='', $after='')
	{
		$bc = '';
		$arrayPath = $this->_routeline;
		if (!empty($arrayPath) && !empty($this->_breadcrumbHome)):
			$bc .= '<ul class="'.$container.'">';
			$i = 1;
			$size = count($arrayPath);
			foreach($arrayPath as $key => $elt):
				if($link && $i != count($arrayPath)):
					$routelink = $this->getUrlByRoute($key);
					$path = '<a href="/'.$routelink.'">'.$elt.'</a>';
				else:
					$path = $elt;
				endif;
				$class = ($i==1)?' class="first"':(($i==count($arrayPath))?' class="last"':'');
				if($i==$size)
					$after = '';
				$bc .= '<li'.$class.'>'.$before.$path.$after.'</li>';
				$i++; 
			endforeach;
			$bc .= '</ul>';
		endif;
		$this->_breadcrumb = $bc;
	}

	/**
	 * Set route index display name
	 *
	 */
	public function setBreadcrumbHome($name)
	{
		$this->_breadcrumbHome = $name;	
	}

	/**
	 * Set route index name
	 *
	 */
	public function setRouteIndex($name)
	{
		if (!empty($name))
			$this->_routeIndex = $name;
	}
}