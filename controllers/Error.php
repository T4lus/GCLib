<?php

namespace Controllers;



class Error extends Common
{
	public function indexAction() {
		if(!\headers_sent())
			\header('HTTP/1.1 404 Not Found');
		$this->_template->parse('header.tpl.html');
		$this->_template->parse('errors/404.tpl.html');
		$this->_template->parse('footer.tpl.html');
	}
}