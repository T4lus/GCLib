<?php
/**
 * This file is part of the GcLib library.
 */

namespace GcLib;

use GcLib\Exception\DatabaseException;

/**
 * Database
 * Drive all databases
 *
 * @author T4lus
 * @version $Revision: 73 $
 * @date $Date: 2013-08-02 09:22:14 +0200 (Fri, 02 Aug 2013) $
 * @package GcLib
 */
class Database
{
	/** @const string Default instance key */
	const DEFAULT_INSTANCE = 'default';

	/** @const string Option host */
	const OPTION_HOST = 'host';

	/** @const string Option user */
	const OPTION_USER = 'user';

	/** @const string Option password */
	const OPTION_PASSWORD = 'password';

	/** @const string Option database */
	const OPTION_DATABASE = 'database';

	/** @const string Option adapter */
	const OPTION_ADAPTER = 'adapter';

	/** @const string Option port */
	const OPTION_PORT = 'port';

	/** @const string Option DSN */
	const OPTION_DSN = 'dsn';

	/** @const string Option Debug */
	const OPTION_DEBUG = 'debug';

	/** @var string DSN of connection */
	protected $_dsn = '';

	/** @var string User */
	protected $_user = '';

	/** @var string Password */
	protected $_password = '';

	/** @var Database instances */
	protected static $_instances = array();

	/** @var string Current instance key */
	protected $_currentInstance = self::DEFAULT_INSTANCE;

	/** @var resource Connection resource */
	protected $_resource;

	/** @var resource Result */
	protected $_result;

	/** @var int Last insert ID */
	protected $_lastID;

	/** @var array Queries */
	protected $_queries = array();
	
	/** @var array Allowed functions */
	protected $_allowedFunc = array('NOW()');

	/** @var bool Debug */
	protected $_debug = false;

	/** @var array Options */
	protected static $_options = array();

	/** @var array Default options */
	protected static $_defaultOptions = array(
		self::OPTION_HOST		=> '127.0.0.1',
		self::OPTION_USER		=> 'root',
		self::OPTION_PASSWORD	=> '',
		self::OPTION_DATABASE	=> 'test',
		self::OPTION_ADAPTER	=> 'mysql',
		self::OPTION_PORT		=> 3306,
		self::OPTION_DSN		=> null,
		self::OPTION_DEBUG		=> false,
	);

	/**
	 * Get Instance
	 *
	 * @param string $instance Instance name
	 *
	 * @return Database instance
	 */
	public static function getInstance($instance = self::DEFAULT_INSTANCE)
	{
		if (!isset(self::$_instances[$instance]) || !(self::$_instances[$instance] instanceof self))
		{
			self::$_instances[$instance] = new self($instance);
		}

		$object = self::$_instances[$instance];

		$object->_currentInstance = $instance;

		return $object;
	}

	/**
	 * Set options
	 *
	 * @param array $options Options
	 * @param string $instance Instance name
	 */
	public static function setOptions(array $options, $instance = self::DEFAULT_INSTANCE)
	{
		if (!isset(self::$_options[$instance]))
		{
			self::$_options[$instance] = self::$_defaultOptions;
		}

		self::$_options[$instance] = \array_merge(self::$_options[$instance], $options);
	}

	/**
	 * Get options
	 *
	 * @param string $instance Instance name
	 *
	 * @return array Options
	 */
	public static function getOptions($instance = self::DEFAULT_INSTANCE)
	{
		return isset(self::$_options[$instance])
			? self::$_options[$instance]
			: array();
	}

	/**
	 * Connect
	 *
	 * @return bool
	 * @throws Exception\DatabaseException
	 */
	public function connect()
	{
		// Compatibility reasons
		if (ini_get('date.timezone') == '')
		{
			\date_default_timezone_set('Europe/Paris');
		}

		// Try to connect
		try
		{
			$this->_resource = new \PDO($this->_dsn, $this->_user, $this->_password);
		}
		catch (\PDOException $e)
		{
			$message = 'Database Error: Unable to connect to the database.'.($this->_debug ? PHP_EOL.$e->getMessage() : '');
			throw new DatabaseException($message);
		}

		// Try to enable UTF8
		if (!$this->enableUTF8() && $this->_debug)
		{
			throw new DatabaseException('Database Error: Unable to enable UTF8.');
		}
		else
		{
			return true;
		}
	}

	/**
	 * Disconnection
	 */
	public function disconnect()
	{
		$this->_resource = NULL;
	}

	/**
	 * Enable UTF-8
	 *
	 * @return bool Success
	 */
	public function enableUTF8()
	{
		return (bool)$this->_resource->query("SET NAMES 'utf8'");
	}

	/**
	 * Escape value
	 * (for security reasons)
	 *
	 * @param string $value
	 * @param bool $allowHTML
	 *
	 * @return string Value escaped
	 */
	public function escape($value, $allowHTML = false)
	{
		if(is_array($value))
		{
			$value = serialize($value);
		}
		else
		{
			// Check magic quotes
			if (\get_magic_quotes_gpc())
			{
				$value = \stripslashes($value);
			}
	
			// Not numeric
			if (!\is_numeric($value))
			{
				// Not allow HTML
				if (!$allowHTML)
				{
					$value = \strip_tags($value);
				}
			}
		}

		return $value;
	}

	/**
	 * Get Error Number
	 *
	 * @return int Error Number
	 */
	public function getErrorNumber()
	{
		$error = $this->_resource->errorInfo();
		return isset($error[1]) ? $error[1] : 0;
	}

	/**
	 * Get Value
	 *
	 * @param string $query SQL query (E.g. SELECT...)
	 * @param array $params List of bind params (for preparation query)
	 *
	 * @return bool|mixed Value
	 */
	public function getValue($query, array $params = array())
	{
		$row = $this->getRow($query, $params);

		return $row ? \array_shift($row) : false;
	}

	/**
	 * Get row
	 *
	 * @param string $query SQL query (E.g. SELECT...)
	 * @param array $params List of bind params (for preparation query)
	 *
	 * @return array|bool Row or error
	 */
	public function getRow($query, array $params = array())
	{
		// Add limit to get just first row
		$query .= ' LIMIT 1';

		// Send query
		$result = $this->query($query, $params);

		return $result ? $result->fetch(\PDO::FETCH_ASSOC) : false;
	}

	/**
	 * Get rows
	 *
	 * @param string $query SQL query (E.g. SELECT...)
	 * @param array $params List of bind params (for preparation query)
	 *
	 * @return array|bool Rows or error
	 */
	public function getRows($query, array $params = array())
	{
		$rows = array();

		// Send query
		$result = $this->query($query, $params);

		if (!$result)
		{
			return false;
		}

		// Build rows array
		while ($row = $result->fetch(\PDO::FETCH_ASSOC))
		{
			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Insert
	 *
	 * @param string $table Table name
	 * @param array $fieldsList List of fields (E.g. array(array('email' => 't4lus@global-chaos.fr'), array('email' => 'plop@global-chaos.fr'))
	 *
	 * @return bool Success
	 */
	public function insert($table, array $fieldsList)
	{
		$params = array();

		// No fields, no query :)
		if (empty($fieldsList))
		{
			return false;
		}

		// Transform simple to multidimensional array
		if (!isset($fieldsList[0]))
		{
			$fieldsList = array($fieldsList);
		}

		// Build query
		$fieldsValues = '';
		foreach ($fieldsList as $index => $fields)
		{
			foreach ($fields as $field => $value) // array_walk not good working :(
			{
				$allowedFunc = array_map('strtolower', $this->_allowedFunc);
				if (!in_array(strtolower($value), $allowedFunc))
				{
					$fieldKey = 'field_'.$index.'_'.$field;
					$fields[$field] = ':'.$fieldKey;
					$params[$fieldKey] = $value;
				}
			}
			$fieldsValues .= '('.\implode(', ', $fields).'), ';
		}
		
		$fieldsValues	= \rtrim($fieldsValues, ', ');
		$fieldsKeys		= '`'.\implode('`, `', \array_keys($fieldsList[0])).'`';
		$query			= 'INSERT INTO `'.$table.'` ('.$fieldsKeys.') VALUES '.$fieldsValues;
		$this->query($query, $params);

		return (bool)$this->getNumberRows();
	}

	/**
	 * Update
	 *
	 * @param string $table Table name
	 * @param array $fields List of fields (E.g. array('email' => 't4lus@global-chaos.fr')
	 * @param string $where Condition
	 * @param array $params List of bind params (for preparation query)
	 * @param int $limit Limit of update
	 *
	 * @return bool Success
	 */
	public function update($table, array $fields, $where = '', array $params = array(), $limit = 0)
	{
		// No fields, no query :)
		if (empty($fields))
		{
			return false;
		}

		// Build query
		$query = 'UPDATE `'.$table.'` SET ';

		// Build fields in query
		foreach ($fields as $field => $value)
		{
			$allowedFunc = array_map('strtolower', $this->_allowedFunc);
			if (in_array(strtolower($value), $allowedFunc))
			{
				$query .= '`'.$field.'` = '.$value.', ';
			}
			else
			{
				$fieldKey = 'field_'.$field;
				$query .= '`'.$field.'` = :'.$fieldKey.', ';
				$params[$fieldKey] = $value;
			}
		}
		$query = \rtrim($query, ', ');

		// Build where in query
		if (!empty($where))
		{
			$query .= ' WHERE '.$where;
		}

		// Build limit in query
		if ($limit)
		{
			$query .= ' LIMIT '.(int)$limit;
		}

		$this->query($query, $params);

		return (bool)$this->getNumberRows();
	}

	/**
	 * Delete row(s)
	 *
	 * @param string $table Table name
	 * @param string $where Condition
	 * @param array $params List of bind params (for preparation query)
	 * @param int $limit Limit of deletion
	 *
	 * @return bool Success
	 */
	public function delete($table, $where = '', array $params = array(), $limit = 0)
	{
		// Build query
		$query = 'DELETE FROM `'.$table.'`';

		// Build where in query
		if (!empty($where))
		{
			$query .= ' WHERE '.$where;
		}

		// Build limit in query
		if ($limit)
		{
			$query .= ' LIMIT '.(int)$limit;
		}

		$this->query($query, $params);

		return (bool)$this->getNumberRows();
	}

	/**
	 * Send query
	 *
	 * @param string $query SQL query (E.g. SELECT...)
	 * @param array $params List of bind params (for preparation query)
	 *
	 * @return mixed Result
	 */
	public function query($query, array $params = array())
	{
		$debug = array();

		// Debug get start time
		if ($this->_debug)
		{
			$timeStart = $this->getMicrotime();
		}

		// Query execution
		$this->_result = $this->_resource->prepare($query);
		$this->_bindParams($this->_result, $params);
		$this->_result->execute();

			// Debug store query
		$debug['query'] = $query;
		$debug['interpolate'] = $this->interpolateQuery($query, $params);
		
		// Debug
		if ($this->_debug)
		{
			// Store time
			$debug['time'] = $this->getMicrotime() - $timeStart;

			// Store explain
			if ((bool)\preg_match('{^(SELECT)}u', $query))
			{
				$result = $this->_resource->prepare('EXPLAIN '.$query);
				$this->_bindParams($result, $params);
				$result->execute();
				$debug['explain'] = $result->fetch(\PDO::FETCH_ASSOC);
			}
		}

		$this->_queries[] = $debug;

		if ($this->_result)
		{
			$this->autoSetLastID();
		}

		return $this->_result;
	}

	/**
	 * Replaces any parameter placeholders in a query with the value of that
	 * parameter. Useful for debugging. Assumes anonymous parameters from
	 * $params are are in the same order as specified in $query
	 *
	 * @see http://stackoverflow.com/questions/210564/pdo-prepared-statements
	 *
	 * @param string $query The sql query with parameter placeholders
	 * @param array $params The array of substitution parameters
	 *
	 * @return string The interpolated query
	 */
	public function interpolateQuery($query, $params)
	{
		$keys = array();

		$formatValueByType = function($value)
		{
			$sqlKeywords = array('NULL', 'NOW');
			// Type string
			if (\is_string($value))
			{
				// Security for no SQL Keywords
				if (!\in_array($value, $sqlKeywords, false))
				{
					$value = "'".\addslashes($value)."'";
				}
			}
			// Type integer && bool
			elseif (\is_int($value) || is_bool($value))
			{
				$value = (int)$value;
			}
			// Type float
			elseif (\is_float($value))
			{
				$value = (float)$value;
			}
			// Type NULL
			elseif (\is_null($value))
			{
				$value = 'NULL';
			}

			return $value;
		};

		foreach ($params as $key => $value)
		{
			$keys[] = \is_string($key) ? '/:'.$key.'/' : '/[?]/';
			$params[$key] = $formatValueByType($value);
		}

		$query = \preg_replace($keys, $params, $query, 1, $count);

		return $query;
	}

	/**
	 * Get number of rows
	 *
	 * @return int
	 */
	public function getNumberRows()
	{
		return $this->_result ? $this->_result->rowCount() : 0;
	}

	/**
	 * Auto set last (inserted) ID
	 */
	public function autoSetLastID()
	{
		$this->_lastID = (int)$this->_resource->lastInsertId();
	}

	/**
	 * Get last ID (inserted)
	 *
	 * @return int ID
	 */
	public function getLastID()
	{
		return (int)$this->_lastID;
	}

	/**
	 * Get Error Message
	 *
	 * @return string Error
	 */
	public function getErrorMessage()
	{
		$error = $this->_resource->errorInfo();
		return $error[0] == '00000' ? '' : $error[2];
	}

	/**
	 * Get queries
	 *
	 * @return array Queries
	 */
	public function getQueries()
	{
		return $this->_queries;
	}

	/**
	 * Get last query
	 *
	 * @return string Query SQL
	 */
	public function getLastQuery()
	{
		if (!empty($this->_queries))
		{
			$lastQuery = \end($this->_queries);
			return $lastQuery;
		}
		return '';
	}

	/**
	 * Enable debug
	 */
	public function enableDebug()
	{
		$this->_debug = true;
	}

	/**
	 * Disable debug
	 */
	public function disableDebug()
	{
		$this->_debug = false;
	}

	/**
	 * Get table status
	 *
	 * @param string $table Table
	 * @param null|string $column Column (E.g. Auto_increment)
	 *
	 * @return mixed Value(s)
	 * @throws Exception\DatabaseException
	 */
	public function getTableStatus($table, $column = null)
	{
		// Check adapter
		$options = $this->getOptions($this->_currentInstance);
		if ($options[self::OPTION_ADAPTER] != 'mysql')
		{
			throw new DatabaseException('Database Error: Unable to use '.__METHOD__.' for non "mysql" adapter.');
		}

		$result = $this->query('SHOW TABLE STATUS LIKE :table', array('table' => $table));

		$results = $result->fetch(\PDO::FETCH_ASSOC);

		if (!\is_null($column) && isset($results[$column]))
		{
			return $results[$column];
		}

		return $results;
	}

	/**
	 * Get schema
	 *
	 * @return array
	 * @throws Exception\DatabaseException
	 */
	public function getSchema()
	{
		// Check adapter
		$options = $this->getOptions($this->_currentInstance);
		if ($options[self::OPTION_ADAPTER] != 'mysql')
		{
			throw new DatabaseException('Database Error: Unable to use '.__METHOD__.' for non "mysql" adapter.');
		}

		$schema = array();

		// Get tables
		$tables = $this->getRows('SHOW TABLES');

		if (!empty($tables))
		{
			foreach ($tables as $table)
			{
				$table = \current($table);

				$schema[$table] = array();

				// Get columns
				$columns = $this->getRows('SHOW FULL COLUMNS FROM `'.$table.'`');

				foreach ($columns as $index => $column)
				{
					\preg_match('{^(?P<type>[a-zA-z]+)(\((?P<size>[0-9]+)\))?}', $column['Type'], $matches);

					$schema[$table][$column['Field']] = array(
						// Default
						'default'			=> $column['Default'] != 'NULL' ? $column['Default'] : NULL,
						'isNull'			=> $column['Null'] == 'YES',
						// Comment
						'comment'			=> !empty($column['Comment']) ? $column['Comment'] : NULL,
						// Cast
						'type'				=> \strtolower($matches['type']),
						'size'				=> isset($matches['size']) ? (float)$matches['size'] : NULL,
						'isUnsigned'		=> (bool)\strpos($column['Type'], 'unsigned'),
						// Keys
						'isPrimary'			=> $column['Key'] == 'PRI',
						'isIndex'			=> $column['Key'] == 'MUL',
						'isUnique'			=> $column['Key'] == 'UNI' || $column['Key'] == 'PRI',
						'isAutoIncremented' => $column['Extra'] == 'auto_increment',
						// Raw (origin)
						'raw'				=> $column,
					);
				}
			}
		}

		return $schema;
	}

	/**
	 * Return PDO resource
	 * @return resource
	 */
	public function getResource()
	{
		return $this->_resource;
	}

	/**
	 * Constructor
	 *
	 * @param string $instance Instance name
	 */
	protected function __construct($instance = self::DEFAULT_INSTANCE)
	{
		$options = self::getOptions($instance);

		$this->_debug = (bool)$options[self::OPTION_DEBUG];

		if (!isset($options[self::OPTION_DSN]) || \is_null($options[self::OPTION_DSN]))
		{
			if ($options[self::OPTION_ADAPTER] != 'sqlite')
			{
				$this->_dsn	= $options[self::OPTION_ADAPTER].':dbname='.$options[self::OPTION_DATABASE].';host='.$options[self::OPTION_HOST];
				if (!\is_null($options[self::OPTION_PORT]))
				{
					$this->_dsn	.= ';port:'.$options[self::OPTION_PORT];
				}
			}
			else
			{
				$this->_dsn	= $options[self::OPTION_ADAPTER].':'.$options[self::OPTION_DATABASE];
			}
		}
		else
		{
			$this->_dsn	= isset($options[self::OPTION_DSN]) ? $options[self::OPTION_DSN] : '';
		}

		$this->_user		= isset($options[self::OPTION_USER]) ? $options[self::OPTION_USER] : null;
		$this->_password	= isset($options[self::OPTION_PASSWORD]) ? $options[self::OPTION_PASSWORD] : null;

		$this->connect();
	}

	/**
	 * Bind Params
	 *
	 * @param \PDOStatement $result PDO Statement
	 * @param array $params List of bind params (for preparation query)
	 */
	protected function _bindParams($result, $params)
	{
		if (!empty($params))
		{
			foreach ($params as $param => $value)
			{
				if(\is_int($value))
				{
					$type = \PDO::PARAM_INT;
				}
				elseif(\is_bool($value))
				{
					$type = \PDO::PARAM_BOOL;
				}
				elseif(\is_null($value))
				{
					$type = \PDO::PARAM_NULL;
				}
				elseif(\is_string($value))
				{
					$type = \PDO::PARAM_STR;
				}
				else
				{
					$type = FALSE;
				}
				$param	= \is_int($param) ? $param + 1 : ':'.$param;
				$result->bindValue($param, $value, $type);
			}
		}
	}

	/**
	 * Get PDO type of value
	 *
	 * @param $value
	 * @return bool|int
	 */
	protected function _getPDOTypeOfValue($value)
	{
		// Default type (same for float, decimal, double, etc.)
		$type = \PDO::PARAM_STR;

		if(\is_int($value))
		{
			$type = \PDO::PARAM_INT;
		}
		elseif(\is_bool($value))
		{
			$type = \PDO::PARAM_BOOL;
		}
		elseif(\is_null($value))
		{
			$type = \PDO::PARAM_NULL;
		}
		elseif(\is_string($value))
		{
			$type = \PDO::PARAM_STR;
		}

		return $type;
	}
	
	/**
	 * Get microtime
	 *
	 * @return string Microtime
	 */
	public function getMicrotime()
	{
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}

	/**
	 * Disable cloning
	 */
	protected function __clone() {}
}