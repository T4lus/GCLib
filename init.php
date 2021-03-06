<?php
ini_set('display_errors', true);
error_reporting(E_ALL);

require_once('library/php_utils.php');

require_once('config.php');

// Inclusions section
require_once PATH_ROOT.'library/GcLib/Loader.php';

// Loader section
$loader = new \GcLib\Loader();

$loader->setRootPath(dirname(__FILE__));
$loader->registerNamespaces(array(
	'GcLib'				=> 'library/GcLib/',
	'TemplateEngine'	=> 'library/Template/',
	'Classes'			=> 'classes/',
	'Models'			=> 'models/',
	'Controllers'		=> 'controllers/',
));
$loader->register();

// Debug section
$allowedIPAddresses = array('127.0.0.1');
if (\in_array(\GcLib\Request::getIPAddress(), $allowedIPAddresses) && DEBUG)
{
	\error_reporting(E_ALL);
	\ini_set('display_errors','on');
	
}

// Database section
\GcLib\Database::setOptions(array(
	\GcLib\Database::OPTION_HOST			=> DB_HOST,
	\GcLib\Database::OPTION_USER			=> DB_USER,
	\GcLib\Database::OPTION_PASSWORD		=> DB_PASS,
	\GcLib\Database::OPTION_DATABASE		=> DB_BASE,
	\GcLib\Database::OPTION_DEBUG			=> DEBUG,
));
try {
	\GcLib\Database::getInstance();
}
catch (\GcLib\Exception\DatabaseException $e) {
	exit($e->getMessage());
}

// Routes section
include_once PATH_ROOT.'routing.php';

// Session section
\GcLib\Session::getInstance();

// Specific settings section
date_default_timezone_set('Europe/Paris');
setlocale(LC_ALL, 'fr_FR');