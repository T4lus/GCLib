<?php
/**
 * This file is part of the GcLib library.
 */

namespace GcLib\Export;

/**
 * Export\CSV
 *
 * @author T4lus
 * @version $Revision: 3 $
 * @date $Date: 2012-08-01 11:45:19 +0200 (Wed, 01 Aug 2012) $
 * @package GcLib
 */
class CSV
{
	/** @var string Path */
	protected $_path = './';

	/** @var string File */
	protected $_filename = 'export.csv';

	/** @var array Lines */
	protected $_lines = array();

	/** @var string Columns enclosure */
	protected $_enclosure = '"';

	/** @var string Columns delimiter */
	protected $_delimiter = ';';

	/** @var string End of lines */
	protected $_endOfLine = \PHP_EOL;

	/** @var string Raw data */
	protected $_rawData = '';

	/**
	 * Constructor
	 */
	public function __construct($path = NULL, $filename = NULL)
	{
		if (!\is_null($path))
		{
			$this->_path = $path;
		}
		if (!\is_null($filename))
		{
			$this->_filename = $filename;
		}
	}

	/**
	 * Add line
	 *
	 * @param array $columns Columns
	 */
	public function addLine(array $columns)
	{
		$this->_lines[] = $columns;
	}

	/**
	 * Set path
	 *
	 * @param string $path Path
	 */
	public function setPath($path)
	{
		$this->_path = $path;
	}

	/**
	 * Set filename
	 *
	 * @param string $filename Filename
	 */
	public function setFilename($filename)
	{
		$this->_filename = $filename;
	}

	/**
	 * Parse data
	 *
	 * @return string
	 */
	public function parse()
	{
		$this->_rawData = '';

		if (!empty($this->_lines))
		{
			foreach ($this->_lines as $fields)
			{
				foreach ($fields as $field)
				{
					$this->_rawData .= $this->_enclosure.$field.$this->_enclosure.$this->_delimiter;
				}
				$this->_rawData = \rtrim($this->_rawData, $this->_delimiter);
				$this->_rawData .= $this->_endOfLine;
			}
			$this->_rawData = \rtrim($this->_rawData, $this->_endOfLine);
		}

		return $this->_rawData;
	}

	/**
	 * Write
	 *
	 * @return bool
	 */
	public function write()
	{
		if (empty($this->_rawData))
		{
			$this->parse();
		}

		$file = \realpath($this->_path).DIRECTORY_SEPARATOR.$this->_filename;
		if (\is_writable(\realpath($this->_path).DIRECTORY_SEPARATOR))
		{
			return (bool)\file_put_contents($file, $this->_rawData);
		}

		return false;
	}

	/**
	 * Fast Export
	 *
	 * @param array|object $data Input data
	 * @param NULL|string $output Filename (use NULL to get raw data)
	 *
	 * @return bool|string
	 */
	public static function fastExport($data, $output = NULL)
	{
		$export = new self();

		if (!empty($data))
		{
			foreach ($data as $line)
			{
				$export->addLine($line);
			}
		}

		// Output raw
		if (\is_null($output))
		{
			return $export->parse();
		}

		// Output file
		$export->setPath(\dirname($output));
		$export->setFilename(\basename($output));
		return $export->write();
	}
}