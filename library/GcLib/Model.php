<?php

/**
 * This file is part of the GcLib library.
 */

namespace GcLib;


/**
 * Model
 *
 * @author T4lus
 * @version $Revision: 73 $
 * @date $Date: 2013-08-02 09:22:14 +0200 (Fri, 02 Aug 2013) $
 * @package GcLib
 */
abstract class Model
{

	/**	Types */
	const	TYPE_INTEGER	= 0,
			TYPE_BOOLEAN	= 1,
			TYPE_STRING		= 2,
			TYPE_FLOAT		= 3,
			TYPE_DATE		= 4,
			TYPE_HTML		= 5;


	/** @const string Date format */
	const DATE_FORMAT	= 'Y-m-d H:i:s';

	/** @var int Current ID */
	public $id;

	/** @var array Definition of model */
	public static $definition = array(
		'primary'		=> 'id',
		'table'			=> '',
		'fields'		=> array(),
	);

	/** @var array Current definition of model */
	protected $_currentDefinition = array();

	/** @var mixed Database instance */
	protected $_database;


	/**
	 * Constructor
	 *
	 * @param null|integer $id Identifier
	 */
	public function __construct($id = NULL) {
		// Set current definition
		$this->_currentDefinition = self::getDefinition($this);

		// Set database instance
		$this->_database = Database::getInstance();

		// Check ID
		if ($this->isValidId($id)) {

			// Read row data
			$row = $this->read((int)$id);

			// Hydrate properties
			if (!empty($row)) {

				$this->id = (int)$id;

				foreach ($row as $field => $value) {
					if (\property_exists($this, $field)) {
						if(!empty($value)) {
							$val = @unserialize($value);
							if($val!==false)
								$value = $val;
						}
						$this->{$field} = $value;
					}
				}
			}
		}
	}


	/**
	 * Create current object in database
	 *
	 * @return bool Success of add
	 */
	public function create() {

		// Auto set create date
		$this->autoSetDate('date_create');

		// Auto set update date
		$this->autoSetDate('date_update');

		// Insertion
		$result = $this->_database->insert($this->_currentDefinition['table'], $this->getRow());

		// Hydrate current ID
		$this->id = $this->_database->getLastID();

		if (!$result) {
			return FALSE;
		}

		return $result;
	}


	/**
	 * Read row
	 *
	 * @param int $id
	 *
	 * @return array Row values
	 */
	public function read($id) {
		// Query
		$fields	= '`'.\implode(\array_keys($this->_currentDefinition['fields']), '`, `').'`';
		$query  = 'SELECT `'.$this->_currentDefinition['primary'].'`, '.$fields.' ';
		$query .= 'FROM `'.$this->_currentDefinition['table'].'` ';
		$query .= 'WHERE `'.$this->_currentDefinition['primary'].'` = :primary';

		// Retrieve row data
		return $this->_database->getRow($query, array('primary' => (int)$id));
	}



	/**
	 * Update row
	 *
	 * @return bool Success
	 */
	public function update() {
		// Auto set update date
		$this->autoSetDate('date_update');

		$result = $this->_database->update($this->_currentDefinition['table'], $this->getRow(), '`'.$this->_currentDefinition['primary'].'` = :id', array('id' => (int)$this->id), 0);

		return $result;
	}


	/**
	 * Delete row
	 *
	 * @param boolean $force Force deletion (if model use virtual deletion)
	 *
	 * @return bool Success
	 */
	public function delete($force = FALSE) {
		// Auto set update date
		$this->autoSetDate('date_delete');

		if ($this->isValidId($this->id)) {
			// Virtual deletion
			if (isset($this->_currentDefinition['fields']['is_delete']) && !$force) {
				// Check property existence
				if (\property_exists($this, 'is_delete')) {
					$this->{'is_delete'} = 1;
					return $this->save();
				}
				else {
					return FALSE;
				}
			}
			// Real deletion
			else {
				// Delete row
				return $this->_database->delete($this->_currentDefinition['table'], '`'.$this->_currentDefinition['primary'].'` = :id', array('id' => (int)$this->id), 1);
			}
		}
		return FALSE;
	}


	/**
	 * Save row
	 *
	 * @return bool Success
	 */
	public function save() {
		return $this->isValidId($this->id) ? $this->update() : $this->create();
	}


	/**
	 * Get current row
	 * (used save)
	 *
	 * @return array Row (fields)
	 */
	public function getRow() {
		$row = array();

		foreach ($this->_currentDefinition['fields'] as $field => $data) {
			if (property_exists($this, $field)) {
				$row[$field] = $this->formatValue($this->{$field}, $data['type']);
			}
		}
		return $row;
	}


	/**
	 * Format value
	 *
	 * @param mixed $value Value
	 * @param int $type Type
	 *
	 * @return mixed Value formatted
	 */
	public function formatValue($value, $type = self::TYPE_STRING) {
		$allowHTML = FALSE;
		if(is_array($value)) {
			foreach($value as $val) {
				switch ($type) {
					case self::TYPE_INTEGER:
						$val = (int)$val;
						break;

					case self::TYPE_BOOLEAN:
						$val = (int)$val;
						break;

					case self::TYPE_FLOAT:
						$val = (float)$val;
						break;

					case self::TYPE_DATE:
						if (empty($val) || !$val) {
							$val = '0000-00-00';
						}
						break;

					case self::TYPE_HTML:
						$allowHTML = TRUE;
						break;

					case self::TYPE_STRING:
					default :
						// No action, leave escape to work
				}
			}
		}
		else {
			switch ($type) {
				case self::TYPE_INTEGER:
					$value = (int)$value;
					break;

				case self::TYPE_BOOLEAN:
					$value = (int)$value;
					break;

				case self::TYPE_FLOAT:
					$value = (float)$value;
					break;

				case self::TYPE_DATE:
					if (empty($value) || !$value) {
						$value = '0000-00-00';
					}
					break;

				case self::TYPE_HTML:
					$allowHTML = TRUE;
					break;

				case self::TYPE_STRING:
				default :
					// No action, leave escape to work
			}
		}
		return $this->_database->escape($value, $allowHTML);
	}


	/**
	 * Auto set date
	 *
	 * @param string $field Field name
	 */
	public function autoSetDate($field) {
		if (\property_exists($this, $field)) {
			$date = new \DateTime();
			$this->{$field} = $date->format(self::DATE_FORMAT);
		}
	}


	/**
	 * Check validity of ID
	 *
	 * @param int $id ID
	 *
	 * @return bool
	 */
	public function isValidId($id = NULL) {
		return (int)$id > 0;
	}


	/**
	 * Hydrate object
	 *
	 * @param array $row Row
	 * @param bool $forceHydration Force hydration
	 */
	public function hydrate($row = array(), $forceHydration = FALSE) {
		// Remove not secure fields
		if (!$forceHydration) {
			unset($row['id']);
			unset($row['is_delete']);
			unset($row['date_create']);
			unset($row['date_update']);
			unset($row['date_delete']);
		}

		// Set ID
		if ($forceHydration && isset($row['id'])) {
			$this->id = (int)$row['id'];
		}

		if ($forceHydration && isset($row[$this->_currentDefinition['primary']])) {
			$this->id = (int)$row[$this->_currentDefinition['primary']];
		}

		// Hydrate
		foreach ($this->_currentDefinition['fields'] as $field => $data) {
			if (isset($row[$field])) {
				if(is_array($row[$field])) {
					$array = array();
					foreach($row[$field] as $key => $value)
						$array[] = $this->formatValue($value, $data['type']);
					$this->{$field} = $array;
				}
				else {
					$this->{$field} = $this->formatValue($row[$field], $data['type']);
				}
			}
		}
	}


	/**
	 * Has errors
	 *
	 * @return bool Has errors...
	 */
	public function hasErrors() {
		$errors = $this->getErrors(FALSE);
		return !empty($errors);
	}


	/**

	 * Get errors

	 *

	 * @todo Check UTF-8 (\strlen, etc.)

	 * @todo Split method

	 *

	 * @param bool $full Return mode

	 *

	 * @return array List of errors

	 */

	public function getErrors($full = TRUE)

	{

		// Set default arrays

		$errorsRequire = $errorsSize = $errorsValid = array();



		// Check field by field

		foreach ($this->_currentDefinition['fields'] as $field => $data)

		{

			// Check require

			if ((isset($data['require']) && $data['require']) && empty($this->{$field}))

			{

				// @TODO : check GcLib version for retro compatibility

				if (GcLib_VERSION >= 2500 && $data['type'] == self::TYPE_BOOLEAN)

				{

					if(!isset($this->{$field}) || !Validator::boolean($this->{$field}))

						$errorsRequire[] = $field;

				}

				else

				{

					$errorsRequire[] = $field;

				}

			}

			

			// Check size

			if ((isset($data['size']) && $data['size']) && \is_string($this->{$field}))

			{

				// Check max length

				if (\is_numeric($data['size']))

				{

					$data['size'] = array('max' => (int)$data['size']);

				}

				// Check min / max length

				if (\is_array($data['size']) && \count($data['size']) <= 2)

				{

					$fieldSize	= \strlen($this->{$field});



					$min	= isset($data['size']['min']) ? (int)$data['size']['min'] : 0;

					$max	= isset($data['size']['max']) ? (int)$data['size']['max'] : \strlen($this->{$field}) + 1;



					if ($fieldSize < $min || $fieldSize > $max)

					{

						$errorsSize[] = $field;

					}

				}

			}



			// Check validity

			if ((isset($data['validator']) && $data['validator']))

			{

				// Custom validator (with callback)

				if (\is_array($data['validator']) && \count($data['validator']) === 2 && \method_exists($data['validator'][0], $data['validator'][1]))

				{

					if (!(bool)\call_user_func($data['validator'], $this->{$field}))

					{

						$errorsValid[] = $field;

					}

				}

				// Fixed validator

				elseif (\method_exists('GcLib\Validator', $data['validator']))

				{

					if (!\call_user_func(array('GcLib\Validator', $data['validator']), $this->{$field}))

					{

						$errorsValid[] = $field;

					}

				}

				// Function validator

				elseif (\function_exists($data['validator']))

				{

					if (!(bool)\call_user_func($data['validator'], $this->{$field}))

					{

						$errorsValid[] = $field;

					}

				}

			}

		}



		// Build errors array(s)

		if ($full)

		{

			$errors = array(

				'require'	=> $errorsRequire,

				'size'		=> $errorsSize,

				'valid'		=> $errorsValid,

			);

		}

		else

		{

			$errors = \array_merge($errorsRequire, $errorsSize, $errorsValid);

		}



		return $errors;

	}



	/**

	 * Get current definition

	 *

	 * @param string $class Current model

	 *

	 * @return array Current definition

	 */

	public static function getDefinition($class)

	{

		$reflection = new \ReflectionClass($class);

		return $reflection->getStaticPropertyValue('definition');

	}



	/**
	 * Find all rows
	 *
	 * @param null|string $where Where
	 * @param array $params List of bind params (for preparation query)
	 * @param null|string $order Order
	 * @param null|string|integer $limit Limit
	 * @param boolean $useVirtualDeletion Use virtual deletion
	 *
	 * @return array Collection
	 */
	public static function findAll($where = NULL, array $params = array(), $order = NULL, $limit = NULL, $useVirtualDeletion = TRUE) {

		$class = \get_called_class();
		$definition	= self::getDefinition($class);

		// Fields
		$fields  = '`'.$definition['primary'].'`, ';
		$fields .= '`'.\implode('`, `', \array_keys($definition['fields'])).'`';

		// Build query
		$query = 'SELECT '.$fields.' FROM `'.$definition['table'].'` ';

		// Complete query with condition
		if (!\is_null($where) && \is_string($where)) {
			$where = \str_replace('id ', $definition['primary'].' ', $where);
			$query .= 'WHERE '.$where.' ';
		}

		// Use virtual deletion
		if ($useVirtualDeletion && isset($definition['fields']['is_delete'])) {
			$query .= (is_null($where) ? 'WHERE' : 'AND').' `is_delete` = 0 ';
		}

		// Complete query with ordering
		if (!\is_null($order) && \is_string($order)) {
			$order = \str_replace('id ', $definition['primary'].' ', $order);
			$query .= 'ORDER BY '.$order.' ';
		}

		// Complete query with ordering
		if (!\is_null($limit) && (\is_string($limit) || \is_integer($limit))) {
			$query .= 'LIMIT '.$limit.' ';
		}

		// Get rows
		$rows = Database::getInstance()->getRows($query, $params);

		// Create collection
		$collection = array();
		foreach ($rows as $row) {
			$id	= (int)$row[$definition['primary']];

			$object = new $class();
			$object->hydrate($row, TRUE);

			$collection[$id] = $object;

			unset($object);
		}

		return $collection;
	}



	/**

	 * Count

	 *

	 * @param null|string $where Where

	 * @param array $params List of bind params (for preparation query)

	 * @param null|string $order Order

	 * @param boolean $useVirtualDeletion Use virtual deletion

	 *

	 * @return int

	 */

	public static function count($where = NULL, array $params = array(), $order = NULL, $useVirtualDeletion = TRUE)

	{

		$class = \get_called_class();

		$definition	= self::getDefinition($class);



		// Build query

		$query = 'SELECT COUNT(`'.$definition['primary'].'`) FROM `'.$definition['table'].'` ';



		// Complete query with condition

		if (!\is_null($where) && \is_string($where))

		{

			$where = \str_replace('id ', $definition['primary'].' ', $where);

			$query .= 'WHERE '.$where.' ';

		}



		// Use virtual deletion

		if ($useVirtualDeletion && isset($definition['fields']['is_delete']))

		{

			$query .= (is_null($where) ? 'WHERE' : 'AND').' is_delete = 0 ';

		}



		// Complete query with ordering

		if (!\is_null($order) && \is_string($order))

		{

			$order = \str_replace('id ', $definition['primary'].' ', $order);

			$query .= 'ORDER BY '.$order.' ';

		}



		// Get value

		return (int)Database::getInstance()->getValue($query, $params);

	}

}