<?php

	/* Classe Template.
	** Auteur : Talus
	** Dernière modification : 22/02/12 - 11h11
	** Description : 
	** Notes d'auteur :
	** Journal :
	**		[USER] JJ/MM/AA à HHhMM - DESCRIPTION
	*/

namespace TemplateEngine;

use TemplateEngine\Parser;
use TemplateEngine\Functions;
		
	class Template
	{
		
		protected $vars				= array();											/// variables envoyées au moteur de template
		protected $_root			= './';												/// [public] dossier racine vers le site
		protected $_display			= true;												/// [public] s'il faut afficher le rendu ou non
		protected $_cache			= false;											/// [public] active la mise en cache ou non
		protected $_forceCompile	= false;											/// [public] force la recompilation, même si elle n'est pas nécessaire
		protected $_cacheTime		= 0;												/// [public] temps de mise en cache, zéro pour infini
		protected $_tplDir			= 'templates/';										/// [public] dossier des fichiers templates
		protected $_cacheDir		= 'cache/';											/// [public] dossier des fichiers mis en cache
		protected $_compileDir		= 'compile/';										/// [public] dossier des fichiers compilés
		protected $infos			= array();											/// informations sur les fichiers déjà compilé/mis en cache
		protected $infosNew			= false;											/// si il y a de nouvelle information
		protected $debug			= array('cache' => false, 'temps' => array());		/// informations de débugages
		protected $refParser		= null;												/// référence vers le parseur

		protected $_template_dir;	/// @deprecated use $tplDIr
		protected $_compile_dir;	/// @deprecated use $compileDir
		protected $_cache_dir;		/// @deprecated use $cacheDIr
		protected $_compile;		/// @deprecated use $forceCompile
		protected $_cache_time;		/// @deprecated use $cacheTime

		/**
		* Le constructeur du moteur de templates. Définit le chemin racine, construit le parseur, récupère les infos.
		* @param string $root dossier racine du site, de préférence un lien absolut
		*/
		public function __construct($root = './'){
			$this->_root = $root;
			$this->getInfos();
			$this->createParser();

			//référence des propritétés dépréciées
			$this->_template_dir = &$this->_tplDir;
			$this->_cache_dir = &$this->_cacheDir;
			$this->_compile_dir = &$this->_compileDir;
			$this->_compile = &$this->_forceCompile;
			$this->_cache_time = &$this->_cacheTime;
		}

		/**
		* Construit le parseur. Utile pour les extentions.
		*/
		protected function createParser(){
			$this->refParser = Parser::getInstance($this);
		}

		/**
		* Permet de déclarer une variable pour le template
		* @param string|array $n nom de la variable ou tableau contenant plusieur variable
		* @param all $v valeur de la variable si $n n'est pas un Arrray
		* @see assignArray()
		*/
		public function assign($n, $v=null){
			if(is_array($n)) $this->vars = array_merge($this->vars, $n);
			else $this->vars[$n] = $v;
		}

		public function set($n, $v=null){
			if(is_array($n)) $this->vars = array_merge($this->vars, $n);
			else $this->vars[$n] = $v;
		}
		
		/**
		* permet d'assigner un tableau au moteur de template. Utilisé en liens avec un <foreach> dans le template.
		* @param string $n nom du tableau
		* @param array $arr valeurs du tableau
		* @see assign()
		*/
		public function assignArray($n, $arr){
			if(isset($this->vars[$n]) && !is_array($this->vars[$n]))
				Functions::error('Vous &eacute;crasez une variable par un array');
			krsort($arr);

			if(strpos($n, '.')) {
				$e = explode('.', $n);
				$b = '$this->vars';
				$c = count($e) -1;
				for ($i=0 ; $i<$c ; $i++) {
					$b .= '[\'' . $e[$i] . '\']';
					$c_b_p = 'count(' . $b . ') - 1';
					$b .= '[' . $c_b_p . ']';
				}
				$b .= '[\'' . $e[$c] . '\'][] = $arr;';
				eval($b);
			}else
				$this->vars[$n][] = $arr;
		}

		/**
		* Alias de assignArray()
		* @param string $n nom du tableau
		* @param array $arr valeurs du tableau
		* @deprecated use assignArray()
		*/
		public function assign_array($n, $arr){
			return $this->assignArray($n, $arr);
		}

		public function setArray($n, $arr){
			return $this->assignArray($n, $arr);
		}	
		
		/**
		* Fonction permettant de parser un fichier template
		* @param String $f   fichier à  parser
		* @param boolean $s  surnom à  lui donner en cas de cache
		*/
		public function parse($f, $s=false){
			$d = microtime(true);
			$tpl = $this->_root.$this->_tplDir.$f;
			if(!$this->isCompile($tpl)){
				$this->refParser->parse($f);
				$this->infos[$tpl]['compile'] = filemtime($tpl);
				$this->infosNew = true;
			}

			if($this->_cache && !$this->_forceCompile){
				$cacheFile = $this->_root.$this->_cacheDir.TemplateEngine\Functions::name($f, $s).'.tpl.txt';
				if(!$this->isCache($tpl, $s)){
					ob_start();
					$this->display($f, true);
					$c = ob_get_contents();
					ob_clean();
					if(is_bool(file_put_contents($cacheFile, $c)))
						Functions::error('Le dossier des fichiers mis en cache n\'est pas ouvert en &eacute;criture (<i>'.$this->root.$this->cacheDir.'</i>)', true);
					$this->infos[$tpl]['cache'][$s==false ? 'base' : $s] = time();
					$this->infosNew = true;
					if($this->_display) echo $c;
				}else{
					if($this->_display) echo file_get_contents($cacheFile);
					$this->debug['cache'] = true;
				}
			}else{
				$this->display($f);
			}
			$this->debug['temps'][$f] = microtime(true)-$d;
			$this->setInfos();
		}

		/**
		* Fonction affichant (ou non) la page.
		* @param string $f le fichier template
		* @param boolean $force force l'affichage (dans le cas ou la propriété $display est à false).
		* @see $display
		*/
		protected function display($f, $force=false){
			$file = $this->refParser->getFileLink($f);
			if($this->_display || $force){
				foreach($this->vars as $k=>$v)
					${$k} = $v;
				include($file);
			}
		}

		/**
		* Indique si le fichier compilé existe
		* @param string $tpl lien absolu vers le fichier template
		* @return booléen si le fichier compilé existe
		*/
		private function isCompile($tpl){
			if($this->_forceCompile) return false;
			if(!isset($this->infos[$tpl])) return false;
			if($this->infos[$tpl]['compile']<filemtime($tpl)) return false;
			return true;
		}

		/**
		* Indique si le fichier cache existe
		* @param string $tpl lien absolu vers le fichier template
		* @return booléen si le fichier cache existe
		*/
		private function isCache($tpl, $s){
			if(!$s) $s = 'base';
			if(isset($this->infos[$tpl]['cache'][$s])){
				if($this->_cacheTime <= 0) return true;
				if($this->infos[$tpl]['cache'][$s]+$this->_cacheTime > time()) return true;
			}
			return false;
		}

		/**
		* Verifie si le fichier est encore en cache. Permet d'éviter des assignations inutiles
		* @param string $f le fichier template
		* @param sring $s le surnom du fichier
		* @param boolean $active si false, regardera si la propriété $cache est activée, si true, $cache ne sera pas pris en compte
		* @return boolean si le fichier est encore en cache
		*/
		public function isntInCache($f, $s=false, $active=false){
			$tpl = $this->_root.$this->_tplDir.$f;
			if($this->_cache || $active) return !$this->isCache($tpl, $s);
			return true;
		}

		/**
		* Accesseur aux différentes propriétés (commence par $_)
		* @param String $n nom de la propriété
		* @return all la valeur de la propriété
		*/
		public function __get($n){
			$p = '_'.$n;
			if(isset($this->$p)) return $this->$p;
			else Functions::error('Propri&eacute;t&eacute; <b>'.$n.'</b> inexistante');
		}

		/**
		* Mutateur des différentes propriétés (commence par $_). Vérifie aussi si sont type est bon.
		* @param string $n le nomde de la propriété
		* @param all $v nouvelle valeur de la propriété
		*/
		public function __set($n, $v){
			$p = '_'.$n;
			if(!isset($this->$p))
				Functions::error('Propri&eacute;t&eacute; <b>'.$n.'</b> inexistante');
			elseif(gettype($v) != gettype($this->$p) && $this->$p)
				Functions::error('Propri&eacute;t&eacute; <b>'.$n.'</b> doit être <i>'.gettype($this->$p).'</i>');
			else
				$this->$p = $v;
		}

		/**
		* Récupère les informations sur les fichiers compilés et mis en cache.
		*/
		protected function getInfos(){
			$f = $this->root.$this->_cacheDir.'infos.tpl';
			if(is_file($f)) $this->infos = unserialize(file_get_contents($f));
		}

		/**
		* Sauve les informations sur les fichiers compilés et mis en cache.
		*/
		protected function setInfos(){
			if($this->infosNew){
				$f = $this->root.$this->_cacheDir.'infos.tpl';
				@unlink($f);
				if(!file_put_contents($f, serialize($this->infos)))
					Functions::error('Le dossier des fichiers mis en cache n\'est pas ouvert en &eacute;criture (<i>'.$this->root.$this->cacheDir.'</i>)', true);
			}
		}

		/**
		* Supprime un fichier mis en cache
		* @param string $f le fichier template
		* @param string|boolean $s le surnom du fichier ou true pour supprimer pour tous les surnoms
		* @see cleanCacheDir()
		*/
		public function delCache($f, $s='base') {
			$f_tpl = $this->_root.$this->_tplDir.$f;
			if($s === true)
				unset($this->infos[$f_tpl]['cache']);
			elseif(isset($this->infos[$f_tpl]['cache'][$s]))
				unset($this->infos[$f_tpl]['cache'][$s]);
			$this->setInfos();
		}

		/**
		* Supprime tous les fichiers d'un dossier pour une certaine extentions
		* @param string $dir le dossier
		* @param string $ext l'extention
		* @return array un tableau avec la liste de tous les fichiers supprimés.
		*/
		private function clean($dir,$ext=false) {
			$l = $this->_root.$dir;
			$a = array();
			$d = opendir($l);
			while ($f = readdir($d)) {
				if ($f != '.' && $f != '..') {
					if (($ext == false) || strpos($f, $ext)) {
						$a[] = $f;
						@unlink($l.$f);
					}
				}
			}
			closedir($d);
			return $a;
		}

		/**
		* Alias de cleanCacheDir()
		* @deprecated use cleanCacheDir()
		*/
		public function clean_cache_dir(){
			return $this->cleanCacheDir();
		}

		/**
		* Alias de cleanCompilDir()
		* @deprecated use cleanCompileDir()
		*/
		public function clean_compile_dir(){
			return $this->cleanCompileDir();
		}

		/**
		* Supprime tous les fichiers mis en cache
		* @return array la liste des fichiers mis en cache supprimés.
		* @see cleanCacheDir()
		*/
		public function cleanCompileDir() {
			$this->infos = array();
			$this->setInfos();
			return $this->clean($this->_compileDir, '.tpl.php');
		}

		/**
		* Supprime tous les fichiesr compilés
		* @return array la liste des fichiers compilés supprimés
		* @see cleanCompileDir()
		* @see delCache()
		*/
		public function cleanCacheDir() {
			foreach($this->infos as $k => $v){
			foreach($v as $sk=>$sv){
				unset($this->infos[$k]['cache']);
			}
		}
		$this->setInfos();
			return $this->clean($this->_cacheDir, '.tpl.txt');
		}

		/**
		* Permet d'afficher un popup de débugage du moteur de templates
		* @param string $url l'url vers la racine du site
		* @param int $dec nombre de décimal pour le temps
		*/
		public function debug($url, $dec=5){
			include dirname(__FILE__).'/Debug.php';
		}
	}
?>