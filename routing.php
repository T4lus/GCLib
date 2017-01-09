<?php
$routes = array
(
	'home'      => array(
			'path'      		=> '/',
			'rule'      		=> '/?',
			'keywords'  	=> array('controller' => 'site', 'action' => 'home'),
	),

	//-----------------------------------------------------------------------------------	
	// default
	'default'	=> array(
		'rule'				=> URI_PATH.'/(?P<controller>:alpha:)/(?P<action>:alpha:)',
		'keywords'			=> array(
			'controller'	=> \GcLib\Router::EP_ALPHA,
			'action' 		=> \GcLib\Router::EP_ALPHA,
		),
		'parameters'		=> array(),
	)	
		 
);