<?php

	/* Classe Error.
	** Auteur : Talus
	** Dernière modification : 22/02/12 - 11h11
	** Description : 
	** Notes d'auteur :
	** Journal :
	**		[USER] JJ/MM/AA à HHhMM - DESCRIPTION
	*/
	
namespace TemplateEngine;

abstract class Functions
{
	static protected $errorDisplay  		= true;											/// s'il faut afficher les erreurs
	static protected $errorMsg              = '<hr /><b>[Engine Error]:</b><br />';			/// le message d'erreur de base
	static protected $errors                = false;										/// la liste des erreurs qui sont survenues, sinon false

	/**
	* Affiche un message d'erreur
	* @param string $t le message d'erreur
	* @param boolean $e s'il faut arrêter le script
	*/
	public static function error($t, $e=false){
		if(self::$errorDisplay){
			echo self::$errorMsg.$t;
			self::$errors .= $t."\n";
			if($e) exit;
		}
	}

	/**
	* Fonction de nommage des fichiers templates
	* @param string $f le nom du fichier template
	* @param string $s le surnom du fichier, sinon false
	* @return string le nom du fichier compilé/cache
	*/
	public static function name($f, $s=false){
		$f = str_replace('/', '-', substr($f, 0, strrpos($f, '.')));
		if($s) $f .= '-'.$s;
		return $f;
	}

	/// Mutateur de la propriété errorDisplay
	public static function setErrorDisplay(bool $v){
		self::$errorDisplay = $v;
	}

	/// Mutateur de la propriété ErrorMsg
	public static function setErrorMsg(string $m){
		self::$errorMsg = $m;
	}

	/// Accesseur à la propriété error
	public static function getErrors(){
		return $errors;
	}
	
	public static function get_cookie($name, $validate = true) {
		if (isset($_COOKIE[$name]))
			return ($validate !== false) ? htmlspecialchars($_COOKIE[$name], ENT_QUOTES) : $_COOKIE[$name];
		else
			return null;
	}
}
?>