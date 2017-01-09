<?php
/**
 * This file is part of the GcLib library.
 */

namespace GcLib\Request;
use GcLib\String;

/**
 * Request\File
 *
 * @author T4lus
 * @version $Revision: 73 $
 * @date $Date: 2013-08-02 09:22:14 +0200 (Fri, 02 Aug 2013) $
 * @package GcLib
 */
class File extends FileTypes
{
	/** Upload errors */
	const	NO_ERROR			= 0,
			ERROR_SIZE			= 1,
			ERROR_UPLOAD		= 2,
			ERROR_READ			= 3,
			ERROR_WRITE			= 4,
			ERROR_TYPE			= 5,
			ERROR_EXTENSION		= 6,
			ERROR_NO_FILE		= 7;

	/** @var string Filename */
	protected $_filenamePattern = '{origin.name}.{origin.extension}';

	/** @var string Path */
	protected $_path = './';

	/** @var array File */
	protected $_inputFile = array();

	/** @var array Allowed types (e.g. image/jpg, image/gif, image/*) */
	protected $_allowedTypes = array();

	/** @var int|string Allowed size (default: 3Mb) */
	protected $_allowedSize = 3145728;

	/** @var array File extension without point (e.g. mp3, jpg, avi, etc.) */
	protected $_allowedExtensions= array();

	/** @var array Errors */
	protected $_errors = array();

	/** @var array Upload errors (Just for information) */
	protected $_uploadErrors = array(
		UPLOAD_ERR_INI_SIZE		=> self::ERROR_SIZE,
		UPLOAD_ERR_FORM_SIZE	=> self::ERROR_SIZE,
		UPLOAD_ERR_PARTIAL		=> self::ERROR_UPLOAD,
		UPLOAD_ERR_NO_FILE		=> self::ERROR_NO_FILE,
		UPLOAD_ERR_NO_TMP_DIR	=> self::ERROR_WRITE,
		UPLOAD_ERR_CANT_WRITE	=> self::ERROR_WRITE,
		UPLOAD_ERR_EXTENSION	=> self::ERROR_UPLOAD,
	);

	/**
	 * Constructor
	 *
	 * @param array $inputFile Input file (from Request::getFile('file'))
	 */
	public function __construct(array $inputFile)
	{
		$this->_inputFile = $inputFile;
	}

	/**
	 * Set filename
	 *
	 * @param string $filenamePattern Filename pattern
	 *
	 * You can use:
	 * * {origin.name}		For origin name
	 * * {origin.extension}	For origin extension
	 * * {index}			For index of files
	 *
	 * @return File
	 */
	public function setFilenamePattern($filenamePattern)
	{
		$this->_filenamePattern = $filenamePattern;

		return $this;
	}

	/**
	 * Get file names (after format)
	 *
	 * @return array FileNames
	 */
	public function getFileNames()
	{
		$names = array();

		$files = $this->_inputFile;

		for ($indexFile = 0, $maxFiles = \count($files); $indexFile < $maxFiles; $indexFile++)
		{
			if (!empty($this->_inputFile[$indexFile]['name']))
			{
				$names[$indexFile] = $this->formatName($this->_inputFile[$indexFile]['name'], $indexFile);
			}
		}

		return $names;
	}

	/**
	 * Format name
	 *
	 * @param string $name File name
	 * @param int $fileIndex Index of file
	 *
	 * @return string
	 */
	public function formatName($name, $fileIndex = 0)
	{
		$extension		= \explode('.', $name);
		$extension		= \end($extension);
		$filename		= \substr($name, 0, \strlen($name) - \strlen($extension) - 1);
		$cleanname		= String::clean($filename);
		
		$replacements 	= array(
			'{clean.name}'			=> $cleanname,
			'{origin.name}'			=> $filename,
			'{origin.extension}'	=> $extension,
			'{index}'				=> $fileIndex,
		);

		return \strtr($this->_filenamePattern, $replacements);
	}

	/**
	 * Set path
	 *
	 * @param string $path Path
	 *
	 * @return File
	 */
	public function setPath($path)
	{
		$this->_path = $path;

		return $this;
	}

	/**
	 * Add allowed type
	 *
	 * @param array $type Type (@see \GcLib\Request\FileTypes)
	 *
	 * @return File
	 */
	public function addAllowedType(array $type)
	{
		$this->_allowedTypes += $type;

		return $this;
	}

	/**
	 * Set allowed size
	 *
	 * @param string|integer $allowedSize Size
	 *
	 * You can use:
	 * * 123K(b|o) or 123k(b|o) for Kilo
	 * * 123M(b|o) or 123m(b|o) for Mega
	 *
	 * @return File
	 */

	public function setAllowedSize($allowedSize)
	{
		if (\is_string($allowedSize))
		{
			\preg_match('{^(?P<size>[0-9]+)(?P<unit>[k|m])?(?:[o|b]{1})?$}i', \strtolower($allowedSize), $matches);

			$size = isset($matches['size']) ? (int)$matches['size'] : 0;
			$unit = isset($matches['unit']) ? $matches['unit'] : '';

			// @todo Refactor
			switch ($unit)
			{
				default:
					$this->_allowedSize = $size;
					break;
				case 'k':
					$this->_allowedSize = $size * 1024;
					break;
				case 'm':
					$this->_allowedSize = $size * 1024 * 1024;
					break;
			}
		}
		else
		{
			$this->_allowedSize = (int)$allowedSize;
		}

		return $this;
	}

	/**
	 * Upload process
	 *
	 * @return bool Success
	 */
	public function upload()
	{
		$errors = $this->getErrors();

		$files = $this->_inputFile;

		$result = true;

		for ($indexFile = 0, $maxFiles = \count($files); $indexFile < $maxFiles; $indexFile++)
		{
			// Process upload if alright for current file!
			if (!\array_key_exists($indexFile, $errors))
			{
				$file = $this->_inputFile[$indexFile];

				if (!\move_uploaded_file($file['tmp_name'], $this->_path.$this->formatName($file['name'], $indexFile)))
				{
					$result = false;
				}
			}
			else
			{
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Has errors
	 *
	 * @return bool Has errors...
	 */
	public function hasErrors()
	{
		$errors = $this->getErrors();
		return !empty($errors);
	}

	/**
	 * Get errors
	 *
	 * @return array Errors list
	 */
	public function getErrors()
	{
		$errors = array();

		$files = $this->_inputFile;

		for ($indexFile = 0, $maxFiles = \count($files); $indexFile < $maxFiles; $indexFile++)
		{
			$properties	= $files[$indexFile];

			// Check upload errors
			if ($properties['error'] != UPLOAD_ERR_OK)
			{
				$errors[$indexFile] = $this->_uploadErrors[$properties['error']];
			}
			// Check work errors
			else
			{
				// Check size
				if (!$this->checkSize($indexFile))
				{
					$errors[$indexFile] = self::ERROR_SIZE;
				}
				// Check extensions
				else if (!$this->checkExtension($indexFile))
				{
					$errors[$indexFile] = self::ERROR_EXTENSION;
				}
				// Check types
				else if (!$this->checkType($indexFile))
				{
					$errors[$indexFile] = self::ERROR_TYPE;
				}
				// Check writable destination
				else if (!$this->checkWritable($indexFile))
				{
					$errors[$indexFile] = self::ERROR_WRITE;
				}
			}
		}

		return $errors;
	}

	/**
	 * Check size
	 *
	 * @param int $fileIndex Index of file
	 * @return bool Success
	 */
	public function checkSize($fileIndex = 0)
	{
		if (!isset($this->_inputFile[$fileIndex]))
		{
			return false;
		}

		$file = $this->_inputFile[$fileIndex];

		return $file['size'] <= $this->_allowedSize;
	}

	/**
	 * Check extension
	 *
	 * @param int $fileIndex Index of file
	 * @return bool Success
	 */
	public function checkExtension($fileIndex = 0)
	{
		if (!isset($this->_inputFile[$fileIndex]))
		{
			return false;
		}

		$file = $this->_inputFile[$fileIndex];

		if (!isset($file['name']))
		{
			return false;
		}

		$name		= \explode('.', $file['name']);
		$extension	= \end($name);

		$result = false;

		foreach ($this->_allowedTypes as $type)
		{
			$typeValues = $this->_types[$type];

			if (\in_array($extension, $typeValues['extensions']))
			{
				return true;
			}
		}

		return $result;
	}

	/**
	 * Check type
	 *
	 * @param int $fileIndex Index of file
	 * @return bool Success
	 */
	public function checkType($fileIndex = 0)
	{
		if (!isset($this->_inputFile[$fileIndex]))
		{
			return false;
		}

		$file = $this->_inputFile[$fileIndex];

		if (!isset($file['name']))
		{
			return false;
		}

		$result = false;

		foreach ($this->_allowedTypes as $type)
		{
			$typeValues = $this->_types[$type];

			if (\in_array($file['type'], $typeValues['types']))
			{
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Check writable destination
	 *
	 * @param int $fileIndex Index of file
	 * @return bool Success
	 */
	public function checkWritable($fileIndex = 0)
	{
		if (!isset($this->_inputFile[$fileIndex]))
		{
			return false;
		}

		return \is_dir($this->_path) && \is_writeable($this->_path);
	}
}