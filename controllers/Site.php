<?php

namespace Controllers;
use GcLib\Session;
use GcLib\Request;
use GcLib\Response;

class Site extends Common {

	public function init() {
		parent::init();
	}

	public function homeAction() {
		$this->_template->parse('header.tpl.html');
		$this->_template->parse('navbar.tpl.html');
		$this->_template->parse('home.tpl.html');
		$this->_template->parse('footer.tpl.html');
	}
}