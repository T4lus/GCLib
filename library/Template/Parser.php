<?php

	/* Classe Parser.
	** Auteur : Talus
	** Dernière modification : 22/02/12 - 11h11
	** Description : 
	** Notes d'auteur :
	** Journal :
	**		[USER] JJ/MM/AA à HHhMM - DESCRIPTION
	*/

namespace TemplateEngine;

use TemplateEngine\Template;
use TemplateEngine\Functions;

class Parser{
	
	private static $instance = null;		/// instance du parseur (pour éviter qu'il n'y en aie plusieur)
	protected $tpl;							/// référence vers l'objet de la classe Template
	
	/// les balises à remplacer
	protected $bal= array(
		'var'           => array('{', '}', '[', ']'),                                   		// vars
		'varAdd'        => array('var', 'name'),                                                // ajout de var
		'include'       => array('include', 'file', 'cache'),                   				// include
		'cond'          => array('if', 'elseif', 'else', 'cond'),               				// condition
		'foreach'       => array('foreach', 'var', 'as', 'foreachelse', 'infos'),				// boucle
		'func'          => array('function', 'name'),                                   		// fonction
		'com'           => array('/#', '#/'));                                                  // commentaire

	/**
	* Permet de récupéré l'instance Parser
	* @param Template $tpl référence vers l'objet de la classe Template
	* @return Parser l'instance du parseur
	*/
	public static function getInstance(Template &$tpl){
		if(is_null(self::$instance)) self::$instance = new Parser();
		self::$instance->setRefTpl($tpl);
		return self::$instance;
	}

	/**
	* Parse un fichier en remplaçant toutes les balises par leur équivalent et l'écrit dans le dossier des fichiers compilés
	* @param string $f fichier template à parser
	*/
	public function parse($f){
		$c = $this->openFile($f);
		foreach($this->bal as $k=>$v){
			$k = 'parse'.ucfirst($k);
			$this->$k($c);
		}
		$this->parseSup($c);
		$this->writeFile($f, $c);
	}

	/**
	* Renvoie le lien vers le fichier compilé
	* @param string $f le fichier templates
	* @return string le lien vers le fichier compilé
	*/
	public function getFileLink($f){
		return $this->getCompileDir().Functions::name($f).'.Tpl.php';
	}

	/**
	* Ouvre le fichier template à compiler
	* @param string $f le fichier template
	* @return string le contenu du fichier
	*/
	protected function openFile($f){
		$file = $this->getTplDir().$f;
		if(is_file($file)) return file_get_contents($file);
		else Functions::error('Fichier template introuvable : <i>'.$file.'</i>');
	}

	/**
	* Ecrit le fichier compilé dans le dossier des fichiers compilés
	* @param string $f le fichier template
	* @param string $c le contenu du fichier compilé
	*/
	protected function writeFile($f, $c){
		if(is_bool(file_put_contents($this->getFileLink($f), $c)))
			Functions::error('Le dossier des fichiers compil&eacute;s n\'est pas ouvert en &eacute;criture (<i>'.$this->tpl->compileDir.'</i>)', true);
	}

	/**
	* Fonction qui pourra être utilisée par les extentions
	* @param string $c référence du contenu du fichier en cour de compilation
	*/
	protected function parseSup(&$c){}

	/**
	* Parse les variables
	* @param string $c référence du contenu du fichier en cour de compilation
	*/
	protected function parseVar(&$c){
		$c = preg_replace('`'.preg_quote($this->bal['var'][2]).'(\S+)'.preg_quote($this->bal['var'][3]).'`U', '[\'\1\']', $c);
		$c = preg_replace('`'.preg_quote($this->bal['var'][0]).'(\S+)'.preg_quote($this->bal['var'][1]).'`isU', '<?php echo $\1; ?>', $c);
	}

	/**
	* Parse les balises d'ajout de variable
	* @param string $c référence du contenu du fichier en cour de compilation
	*/
	protected function parseVarAdd(&$c){
		$c = preg_replace('`\<'.preg_quote($this->bal['varAdd'][0]).' '.preg_quote($this->bal['varAdd'][1]).'\="(.+)"\>(.+)\</'.preg_quote($this->bal['varAdd'][0]).'\>`U', '<?php $\1 = \'\2\'; ?>', $c);
	}

	/**
	* Parse les inclusion
	* @param string $c référence du contenu du fichier en cour de compilation
	*/
	protected function parseInclude(&$c){
		$c = preg_replace_callback('`<'.preg_quote($this->bal['include'][0]).' '.preg_quote($this->bal['include'][1]).'="(.+)"\s?('.preg_quote($this->bal['include'][2]).'="on")?\s?/?>`isU', array('TemplateEngine\Parser', 'parseIncludeCallback'), $c);
	}

	/**
	* Callback de la méthode parseInclude()
	* @param array $m les entrées trouvées par la fonction parseInclude()
	* @return string le texte de remplacement
	*/
	private static function parseIncludeCallback($m){
		return isset($m[2]) ? '<?php $tpl_cache=$this->cache; $this->cache=true; $this->parse("'.str_replace('\\','\\\\',$m[1]).'"); $this->cache=$tpl_cache; ?>' : '<?php $this->parse("'.str_replace('\\','\\\\',$m[1]).'"); ?>';
	}

	/**
	* Parse les conditions
	* @param string $c référence du contenu du fichier en cour de compilation
	*/
	protected function parseCond(&$c){
		$c = preg_replace(array(
				'`<'.preg_quote($this->bal['cond'][0]).' '.preg_quote($this->bal['cond'][3]).'="(.+)">`sU',
				'`</'.preg_quote($this->bal['cond'][0]).'>`sU',
				'`<'.preg_quote($this->bal['cond'][1]).' '.preg_quote($this->bal['cond'][3]).'="(.+)"\s?/?>`sU',
				'`<'.preg_quote($this->bal['cond'][2]).'\s?/?>`sU',
			),array(
				'<?php if(\1) { ?>',
				'<?php } ?>',
				'<?php }elseif(\1){ ?>',
				'<?php }else{ ?>'
			),
			$c);
	}

	/**
	* Parse les foreach (les boucles)
	* @param string $c référence du contenu du fichier en cour de compilation
	*/
	protected function parseForeach(&$c){
		for($i=0; $i<10 && strpos($c, '</'.preg_quote($this->bal['foreach'][0])) !== FALSE; $i++){
			$c = preg_replace_callback(
				'`<('.preg_quote($this->bal['foreach'][0]).')\s*'.preg_quote($this->bal['foreach'][1]).'="(.+)"\s*'.preg_quote($this->bal['foreach'][2]).'="(.+)"\s*('.preg_quote($this->bal['foreach'][4]).'=".+")?\s*>((?:.(?!<'.preg_quote($this->bal['foreach'][0]).'>))*)</'.preg_quote($this->bal['foreach'][0]).'>`sU',
				array('TemplateEngine\Parser', 'parseForeachCallback'), $c);
		}
		$c = str_replace('<'.$this->bal['foreach'][3].' />', '<?php }} else { if (true) {?>', $c);
	}

	static private function parseForeachCallback($m){
		if(!empty($m[4])){
			return $c = '<?php if(!empty('.$m[2].')){
	$'.$m[3].'gg[\'foreachCount\'] = 0;
	$'.$m[3].'gg[\'foreachTotal\'] = count('.$m[2].');
	foreach('.$m[2].' as $'.$m[3].') {
		$'.$m[3].'gg[\'foreachCount\']++;
		$'.$m[3].'[\'foreachCount\'] = &$'.$m[3].'gg[\'foreachCount\'];
		$'.$m[3].'[\'foreachTotal\'] = &$'.$m[3].'gg[\'foreachTotal\'];
		$'.$m[3].'[\'foreachFirst\'] = $'.$m[3].'gg[\'foreachCount\']==1;
		$'.$m[3].'[\'foreachLast\'] = $'.$m[3].'gg[\'foreachCount\']==$'.$m[3].'gg[\'foreachTotal\'];?>
			'.$m[5].'
		<?php 
	}} ?>';
		}else{
			return $c = '<?php if(!empty('.$m[2].')){
	foreach('.$m[2].' as $'.$m[3].') {?>
		'.$m[5].'
	<?php }} ?>';
		}
	}

	/**
	* Parse les fonctions
	* @param string $c référence du contenu du fichier en cour de compilation
	*/
	protected function parseFunc(&$c){
		$c = preg_replace_callback('`<'.preg_quote($this->bal['func'][0]).' '.preg_quote($this->bal['func'][1]).'="(\w+)"\s?(.*)?/?>`isU', array('TemplateEngine\Parser', 'parseFuncCallback'), $c);
	}

	/**
	* Callback de la fonction parseFunc()
	* @param array $m les entrées trouvées par parseFunc()
	* @return string le texte de remplacement
	*/
	private static function parseFuncCallback($m){
		$args = '';
		if($nb = preg_match_all('`(string|int|var)="(.+)"`U', $m[2], $arr)){
			for($i=0; $i<$nb; $i++){
				$args .= $arr[1][$i] == 'string' ? '"'.$arr[2][$i].'", ' : $arr[2][$i].', ';
			}
		}
		$args = substr($args, 0, strlen($args)-2);
		if(!function_exists($m[1])) Functions::error('La fonction <b>'.$m[1].'()</b> n\'existe pas !!!', true);
		return '<?php echo '.$m[1].'('.$args.'); ?>';
	}

	/**
	* Efface les commentaires du contenu
	* @param string $c référence du contenu du fichier en cour de compilation
	*/
	protected function parseCom(&$c){
		$c = preg_replace('`'.preg_quote($this->bal['com'][0]).'.+'.preg_quote($this->bal['com'][1]).'`isU', null, $c);
	}

	/**
	* Mutateur de la propriété $tpl (référence au moteur de template)
	* @param Template $tpl référence vers le moteur de template
	*/
	protected function setRefTpl(Template &$tpl){
		$this->tpl = $tpl;
	}

	/**
	* Renvoie le lien vers le fichier compilé directement depuis la classe mère.
	* @return string le lien vers le fichier compilé
	*/
	private function getCompileDir(){
		return $this->tpl->root.$this->tpl->compileDir;
	}

	/**
	* Renvoie le lien vers le fichier cache directement depuis la classe mère.
	* @return string le lien vers le fichier cache
	*/
	private function getTplDir(){
		return $this->tpl->root.$this->tpl->tplDir;
	}
}
?>