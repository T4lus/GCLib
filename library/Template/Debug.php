<?php

	/* Classe Debug.
	** Auteur : Talus
	** Dernière modification : 22/02/12 - 11h11
	** Description : 
	** Notes d'auteur :
	** Journal :
	**		[USER] JJ/MM/AA à HHhMM - DESCRIPTION
	*/

namespace TemplateEngine;

class Debug
{
	private $t = 0;
	private $e = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	private $l = 50;

	public function vars($v){
		if(is_array($v)){
			$r = '<br />';
			++$this->t;
			foreach($v as $k => $v){
				$r .= $this->tab().'[<b>'.$k.'</b>] => '.$this->vars($v)."<br />";
			}
			--$this->t;
			$r = substr($r, 0, strlen($r)-6);
			return $r;
		}else return $this->value($v);
	}

	private function value($v){
		if(is_bool($v)) return $v ? '<i>true</i>' : '<i>false</i>';
		if(is_numeric($v)) return '<span style="color:blue;">'.$v.'</span>';
		if(is_string($v)) return '"<span style="color:red;">'.$this->size($v).'</span>"';
		if(is_null($v)) return '<span style="color:green;">'.$v.'</span>';
		return $v;
	}

	private function size($v){
		if(strlen($v) <= $this->l) return $v;
		return '<i>'.substr($v, 0, $this->l).'...</i>';
	}

	private function tab(){
		$r = '';
		for($i=1; $i<$this->t; ++$i){
			$r .= '|'.$this->e;
		}
		return $r;
	}
}
ob_start();

$d = new Debug();
$c = 'color1';//on initialise la couleur de fond

	//------------------------------
	// Headers
	//------------------------------
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Debug</title>
	<style type="text/css">
	<!--
	body {
		background-color: #66CCCC;
	}
	h1
	{
		font-family:"Comic Sans MS", Arial, serif;
		text-align: center;
		margin: 0px;
	}
	td
	{
		border: 1px dotted black;
		padding: 1px;
	}
	.color1
	{
		background-color: #66CCAA;
	}
	.color2
	{
		background-color: #66CCBB;
	}
	-->
	</style></head>

	<body>
	<h1>Template - Debug</h1>';

	//------------------------------
	// Temps d'exécution
	//------------------------------
	echo '<h2>Temps d\'execution</h2>
	<table width="550" border="0" align="center" cellpadding="0" cellspacing="2">
		<tr>
			<th scope="col" width="30%">Fichier</th>
			<th scope="col">Temps en sec </th>
		</tr>';

	foreach($this->debug['temps'] as $n => $v){
		$c = $c == 'color1' ? 'color2' : 'color1';
		echo '	<tr>'
		.'		<td class="'.$c.'" align="center">'.$n.'</td>'
		.'		<td class="'.$c.'" align="left">'.round($v, $dec).'</td>'
		.'	</tr>';
	}

	$c = $c == 'color1' ? 'color2' : 'color1';
	echo '	<tr>'
	.'		<td class="'.$c.'" align="center"><b>Total</b></td>'
	.'		<td class="'.$c.'" align="left">'.round(array_sum($this->debug['temps']), $dec).'</td>'
	.'	</tr>';

	echo '</table>';


	//------------------------------
	// Variables
	//------------------------------

	if($this->debug['cache'] && !$this->_forceCompile)
		echo '<h3 style="text-align:center;"><span style="color:red;">Attention</span>, un ou plusieurs fichiers ont été repris depuis la mis en cache, il se peut que les variables ne correspondent pas !</h3>';

	echo '<h2>Variables</h2>';
	echo $d->vars($this->vars);

	echo '<h2>Fichier info</h2><pre>';
	echo $d->vars($this->infos);
	echo '</pre>

	</body>
	</html>';

$t = ob_get_contents();
ob_end_clean();
file_put_contents($this->_root.$this->_compileDir.'debug.html', $t);

echo '<script language="javascript" type="text/javascript">
<!--
	window.open(\''.$this->_compileDir.'debug.html\', \'nom fenetre\', \'width=800, height=800,scrollbars=yes\');
--></script>';
?>


