<?php

/**
 * This file is part of the GcLib library.
 */

namespace GcLib;

/**
 * Webservice
 *
 * @author T4lus
 * @version $Revision: 69 $
 * @date $Date: 2012-09-20 15:08:42 +0200 (Thu, 20 Sep 2012) $
 * @package GcLib
 */

class Webservice
{

	/** Errors */
	const	ERROR_NONE			= 0,
			ERROR_UNKNOWN		= 1,
			ERROR_ID			= 2,
			ERROR_PAGE			= 3,
			ERROR_NO_DATA		= 4,
			ERROR_ENTITY		= 5,
			ERROR_ID_UNKNOWN	= 6,
			ERROR_DATA			= 7,
			ERROR_FIELDS		= 8,
			ERROR_ID_MISSING	= 9;

	/** Success */
	const	SUCCESS_UNKNOWN		= 0,
			SUCCESS_DELETE		= 1,
			SUCCESS_CREATE		= 2,
			SUCCESS_UPDATE		= 3;

	/** Modes */
	const	MODE_LIST			= 'list',
			MODE_LITE			= 'lite',
			MODE_FULL			= 'full',
			MODE_DETAIL			= 'detail',
			MODE_DEFINITION		= 'definition';

	/** @var array Errors messages */
	public $errorsMessages = array(
		self::ERROR_NONE		=> '',
		self::ERROR_UNKNOWN		=> 'Unknown error',
		self::ERROR_ID			=> 'ID not found',
		self::ERROR_PAGE		=> 'This page does not exists',
		self::ERROR_NO_DATA		=> 'There is no result',
		self::ERROR_ENTITY		=> 'Unknown entry',
		self::ERROR_ID_UNKNOWN	=> 'Unknown ID',
		self::ERROR_DATA		=> 'Bad data (use JSON)',
		self::ERROR_FIELDS		=> 'Some fields are corrupted (require, validate, size)',
		self::ERROR_ID_MISSING	=> 'ID is missing',
	);

	/** @var array Success messages */
	public $successMessages = array(
		self::SUCCESS_UNKNOWN	=> 'Success',
		self::SUCCESS_DELETE	=> 'Delete entry successful',
		self::SUCCESS_CREATE	=> 'Create entry successful',
	);

	/** @var string Current HTTP method */
	protected $_method = 'get';

	/** @var array Entities */
	protected $_entities = array();

	/** @var string Entity */
	protected $_entity;

	/** @var int Current ID */
	protected $_id;

	/** @var integer Mode */
	protected $_mode = self::MODE_LIST;

	/** @var integer Page */
	protected $_page = 1;

	/** @var integer Pages */
	protected $_pages = 1;

	/** @var bool Use pagination */
	protected $_usePagination = TRUE;

	/** @var string Request */
	protected $_request = '/';

	/** @var array Output */
	protected $_output = array(
		'pagination'	=> array(
			'pages'		=> 1,
			'page'		=> 1,
		),
	);

	/** @var int Maximum of item per page */
	protected $_maximumPerPage = 50;

	/**
	 * Add entity
	 *
	 * @param string $name Resource name
	 * @param string $description Resource description
	 * @param Model $model Resource model
	 */
	public function addEntity($name, $description = '', Model $model) {
		$name = \strtolower($name);

		$resource = new \stdClass();
		$resource->name			= $name;
		$resource->description	= $description;
		$resource->model		= $model;

		$this->_entities[$name] = $resource;
	}


	/**
	 * Set HTTP Method
	 *
	 * @param string $method HTTP Method
	 */
	public function setMethod($method) {
		$this->_method = $method;
	}


	/**
	 * Set request
	 *
	 * @param string $request Request URI
	 */
	public function setRequest($request) {
		$this->_request = $request;
	}


	/**
	 * Set maximum per page
	 *
	 * @param int $number Number (default: 50)
	 */
	public function setMaximumPerPage($number = 50) {
		$this->_maximumPerPage = (int)$number;
	}


	/**
	 * Get error message
	 *
	 * @param int $errorNumber Error number
	 */
	public function getErrorMessage($errorNumber = self::ERROR_NONE) {
		if (\array_key_exists($errorNumber, $this->errorsMessages)) {
			return $this->errorsMessages[$errorNumber];
		}

		return $this->errorsMessages[self::ERROR_UNKNOWN];
	}


	/**
	 * Get success message
	 *
	 * @param int $successNumber Success number
	 */
	public function getSuccessMessage($successNumber = self::SUCCESS_UNKNOWN) {
		if (\array_key_exists($successNumber, $this->successMessages)) {
			return $this->successMessages[$successNumber];
		}

		return $this->successMessages[self::SUCCESS_UNKNOWN];
	}


	/**
	 * Run webservice
	 *
	 * @return string JSON response
	 */
	public function run() {
		// Method or default method
		$methodPrefix	= 'process';
		$methodDefault	= $methodPrefix.'GET';
		$method			= $methodPrefix.\strtoupper($this->_method);

		// Parsing of request
		$this->parseRequest();

		// Call process method
		if (\method_exists($this, $method)) {
			$this->{$method}();
		}
		else {
			$this->{$methodDefault}();
		}

		// Return data
		return \json_encode($this->_output);
	}


	/**
	 * Parse request
	 */
	protected function parseRequest() {
		$requestParts = \explode('/', $this->_request);

		// First part
		if (!empty($requestParts[0]) && \array_key_exists($requestParts[0], $this->_entities)) {
			$this->_mode		= self::MODE_LITE;
			$this->_entity	= $this->_entities[$requestParts[0]];

			// Second part
			if (isset($requestParts[1])) {
				if ($requestParts[1] == self::MODE_DEFINITION) {
					$this->_mode = self::MODE_DEFINITION;
				}
				else if ($requestParts[1] == self::MODE_FULL) {
					$this->_mode = self::MODE_FULL;
				}
				else if ($requestParts[1] == self::MODE_LITE) {
					$this->_mode = self::MODE_LITE;
				}
				else if (\is_numeric($requestParts[1])) {
					$this->_mode = self::MODE_DETAIL;
					$this->_id = (int)$requestParts[1];
				}

				// Pagination
				if (!empty($requestParts[2]) && (int)$requestParts[2] && ($requestParts[1] == self::MODE_LITE || $requestParts[1] == self::MODE_FULL)) {
					$this->_page = (int)$requestParts[2];
					$this->_output['pagination']['page'] = $this->_page;
					$this->_usePagination = TRUE;
				}

				// No pagination
				if (!empty($requestParts[2]) && $requestParts[2] == 'all' && ($requestParts[1] == self::MODE_LITE || $requestParts[1] == self::MODE_FULL)) {
					$this->_usePagination = FALSE;
				}
			}
		}
	}


	/**
	 * Process GET
	 *
	 * /								List resources
	 * /<resource>/definition/			Definition of resource
	 * /<resource>/[lite/[<page>/]]		List rows of resources (lite)
	 * /<resource>/full/[<page>/]		List rows of resources (full)
	 * /<resource>/<id>/				List row of resource
	 */
	protected function processGET() {
		switch ($this->_mode) {

			// List of resource
			case self::MODE_LIST:
			default:
				foreach ($this->_entities as $resource) {
					$this->_output[] = array(
						'name'			=> $resource->name,
						'description'	=> $resource->description,
						'links'			=> array(
							'lite'			=> "/{$resource->name}/lite/",
							'full'			=> "/{$resource->name}/full/",
							'definition'	=> "/{$resource->name}/definition/",
						),
					);
				}
				unset($this->_output['pagination']);
				break;

			// Full or LITE resource
			case self::MODE_LITE:
			case self::MODE_FULL:
				$limit = NULL;

				// Use pagination
				if ($this->_usePagination) {
					$numberRows = $this->_entity->model->count();

					// Pagination
					$this->_pages = \round($numberRows / $this->_maximumPerPage);
					$this->_output['pagination']['pages'] = $this->_pages;
					if ($this->_page >= 1) {
						$limit = (($this->_page - 1) * $this->_maximumPerPage).','.$this->_maximumPerPage;
					}
				}
				// Not use pagination

				else {
					unset($this->_output['pagination']);
				}

				// Results
				$rows = $this->_entity->model->findAll(NULL, array(), NULL, $limit);

				// Data
				if ($this->_mode == self::MODE_LITE) {
					foreach ($rows as $row) {
						$this->_output['rows'][$row->id] = "/{$this->_entity->name}/{$row->id}/";
					}
				}
				else if ($this->_mode == self::MODE_FULL) {
					$this->_output['rows'] = $rows;
				}

				// Errors
				if (empty($rows)) {
					// No page (consequently no data)
					if ($this->_pages && $this->_page > $this->_pages) {
						$this->_output = array(
							'errors' => array(
								self::ERROR_PAGE => $this->getErrorMessage(self::ERROR_PAGE),
							),
						);
					}
					// No data
					else {
						$this->_output = array(
							'errors' => array(
								self::ERROR_NO_DATA => $this->getErrorMessage(self::ERROR_NO_DATA),
							),
						);
					}
				}
				break;
			// Detail
			case self::MODE_DETAIL:
				$row = $this->_entity->model->findAll('id = :id', array('id' => $this->_id));

				// Errors
				if (empty($row)) {
					$this->_output = array(
						'errors' => array(
							self::ERROR_ID => $this->getErrorMessage(self::ERROR_ID),
						),
					);
				}
				else {
					$this->_output = $row[$this->_id];
				}
				break;

			// Definition
			case self::MODE_DEFINITION:
				// Reflection of definition
				$reflexion	= new \ReflectionClass($this->_entity->model);
				$definition	= $reflexion->getStaticPropertyValue('definition');

				// Output
				$this->_output = $definition['fields'];
				break;
		}
	}


	/**
	 * Process POST
	 */
	protected function processPOST() {
		if ($this->checkEntity()) {
			$input	= \file_get_contents('php://input');
			$data	= (array)\json_decode($input);

			if ($data) {
				$model = $this->_entity->model;
				$model->hydrate($data);

				if ($model->hasErrors()) {
					$this->_output = array(
						'errors' => array(
							self::ERROR_FIELDS => $this->getErrorMessage(self::ERROR_FIELDS),
						),
						'fields' => $model->getErrors(),
					);
				}
				else {
					$model->save();
					$this->_output = array(
						'success' => array(
							self::SUCCESS_CREATE => $this->getSuccessMessage(self::SUCCESS_CREATE),
						),
						'id' => $model->id,
					);
				}
			}
			else {
				$this->_output = array(
					'errors' => array(
						self::ERROR_DATA => $this->getErrorMessage(self::ERROR_DATA),
					),
				);
			}
		}
	}


	/**
	 * Process PUT
	 *
	 * @todo Refactor with processDELETE
	 */
	protected function processPUT() {
		if ($this->checkEntity()) {
			$row = $this->_entity->model->findAll('id = :id', array('id' => (int)$this->_id));
			
			if (empty($row) || !(int)$this->_id) {
				$this->_output = array(
					'errors' => array(
						self::ERROR_ID_MISSING => $this->getErrorMessage(self::ERROR_ID_MISSING),
					),
				);
			}
			else {
				$input	= \file_get_contents('php://input');
				$data	= (array)\json_decode($input);
				
				if ($data) {
					$model = $this->_entity->model;
					$model->hydrate($model->read($this->_id), true);
					$model->hydrate($data);

					if ($model->hasErrors()) {
						$this->_output = array(
							'errors' => array(
								self::ERROR_FIELDS => $this->getErrorMessage(self::ERROR_FIELDS),
							),
							'fields' => $model->getErrors(),
						);
					}
					else {
						$model->save();
						$this->_output = array(
							'success' => array(
								self::SUCCESS_UPDATE => $this->getSuccessMessage(self::SUCCESS_UPDATE),
							),
							'id' => $model->id,
						);
					}
				}
				else {
					$this->_output = array(
						'errors' => array(
							self::ERROR_DATA => $this->getErrorMessage(self::ERROR_DATA),
						),
					);
				}
			}
		}
	}


	/**
	 * Process DELETE
	 *
	 * @todo Refactor with processPUT
	 */
	protected function processDELETE() {
		if ($this->checkEntity()) {
			$row = $this->_entity->model->findAll('id = :id', array('id' => (int)$this->_id));

			if (empty($row)) {
				$this->_output = array(
					'errors' => array(
						self::ERROR_ID_UNKNOWN => $this->getErrorMessage(self::ERROR_ID_UNKNOWN),
					),
				);
			}
			else {
				$row[$this->_id]->delete();
				$this->_output = array(
					'success' => array(
						self::SUCCESS_DELETE => $this->getSuccessMessage(self::SUCCESS_DELETE),
					),
				);
			}
		}
	}


	/**
	 * Check entity
	 *
	 * @return bool Success
	 */
	protected function checkEntity() {
		if (empty($this->_entity)) {
			$this->_output = array(
				'errors' => array(
					self::ERROR_ENTITY => $this->getErrorMessage(self::ERROR_ENTITY),
				),
			);
			return false;
		}
		return true;
	}


}