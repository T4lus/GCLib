<?php

//-----------------------------------------------------------------------------------
// Constants section
define('GcLib_VERSION',                     2530);
define('DEBUG',                             TRUE);
define('TEST',                              TRUE);
define('DEV',                               TRUE);
define('SALT',                              'L&*xo6U62@J;[3O5HXDz,Wq:{>?$.KY#9WaPH+TY{Euy!9u)FkqhcS2B6wJ+mzh<)');

define('COOKIE_EXPIRE',                     365*24*3600);
define('SESSION_TIMEOUT', 					5*60);


//-----------------------------------------------------------------------------------
// Paths & URL constants
define('PATH_ROOT',                        	dirname(__FILE__).DIRECTORY_SEPARATOR);
define('PATH_CONTROLLERS',            		PATH_ROOT.'controllers'.DIRECTORY_SEPARATOR);
define('PATH_LIBRARY',            			PATH_ROOT.'library'.DIRECTORY_SEPARATOR);
define('PATH_MODELS',                     	PATH_ROOT.'models'.DIRECTORY_SEPARATOR);
define('PATH_VIEWS',                       	DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR);
define('PATH_IMG',                          PATH_ROOT.'assets'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR);

//-----------------------------------------------------------------------------------
// Tables
define('TABLE_PREFIX',                      '');
define('TABLE_USERS',                      	TABLE_PREFIX.'user');


//-----------------------------------------------------------------------------------
// Meta config
define('META_TITLE',                        'Global-Chaos Lib');
define('META_DESCRIPTION',            		'Global-chaos Lib');


// Dev config
if(DEV){
	define('DB_HOST',                       '');
	define('DB_USER',                       '');
	define('DB_PASS',                       '');
	define('DB_BASE',                       '');

	define('URI_PATH',                      '/');
}

// Prod config
else{
	define('DB_HOST',                       '');
	define('DB_USER',                       '');
	define('DB_PASS',                       '');
	define('DB_BASE',                       '');

	define('URI_PATH',                      '/');
}
