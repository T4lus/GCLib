<?php

namespace Models;

class User extends \GcLib\Model
{

	/** @var string first name */
	public $first_name;

	/** @var string last name */
	public $last_name;

	/** @var string Login */
	public $login;

	/** @var string Password */
	public $password;

	/** @var string Email */
	public $email;

	public $date_create;
	public $date_update;

	public static $definition = array(
		'table'     => TABLE_USERS,
		'primary'   => 'id',
		'fields'    => array(
			'first_name'    => array('type' => self::TYPE_STRING,	'require' => true,	'validator' => 'text'),
			'last_name'     => array('type' => self::TYPE_STRING,	'require' => true,	'validator' => 'text'),
			'login'         => array('type' => self::TYPE_STRING,	'require' => true,	'validator' => 'text'),
			'password'      => array('type' => self::TYPE_STRING,	'require' => true),
			'email'         => array('type' => self::TYPE_STRING,	'require' => true,	'validator' => 'email'),
			'date_create'   => array('type' => self::TYPE_DATE),
			'date_update'   => array('type' => self::TYPE_DATE),
		),
	);
	
	public static function findByID($uid)
	{
		if(!empty($uid))
		{
			$query  = 'SELECT * FROM `'.TABLE_USERS.'` WHERE id=:uid';
			$params = array('uid' => $uid);
			$datas = \GcLib\Database::getInstance()->getRow($query, $params);
		}
		return (!empty($datas)) ? $datas : false;
	}

	public static function findByLogin($login)
	{
		if(!empty($login))
		{
			$query  = 'SELECT * FROM `'.TABLE_USERS.'` WHERE login=:login';
			$params = array('login' => $login);
			$datas = \GcLib\Database::getInstance()->getRow($query, $params);
		}
		return (!empty($datas)) ? $datas : false;
	}
	
	public static function findByEmail($email)
	{
		if(!empty($email))
		{
			$query  = 'SELECT * FROM `'.TABLE_USERS.'` WHERE email=:email';
			$params = array('email' => $email);
			$datas = \GcLib\Database::getInstance()->getRow($query, $params);
		}
		return (!empty($datas)) ? $datas : false;
	}	
}
 
 