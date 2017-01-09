<?php
/**
 * This file is part of the GcLib library.
 */

namespace GcLib\Export;

/**
 * Export\XML
 *
 * @author T4lus
 * @version $Revision: 3 $
 * @date $Date: 2012-08-01 11:45:19 +0200 (Wed, 01 Aug 2012) $
 * @package GcLib
 */
class XML
{
	/** @var string Path */
	protected $_path = './';

	/** @var string File */
	protected $_filename = 'export.xml';

	/** @var \DOMDocument Document */
	protected $_document;

	/** @var array Data */
	protected $_data = array();

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
	 * @param array $data
	 */
	public function setData($data)
	{
		$this->_data = $data;
	}

	public function parseElements($nodeName, $nodeData)
	{
		$node = $this->_document->createElement($nodeName);

		// NOT RECURSIVE PART
		// Set text of node
		if (\is_array($nodeData))
		{
			// Set attributes
			if (!empty($nodeData['@attributes']) && \is_array($nodeData['@attributes']))
			{
				foreach ($nodeData['@attributes'] as $attribute => $value)
				{
					$node->setAttribute($attribute, $value);
				}

				unset($nodeData['@attributes']);
			}
			// Set text
			if (!empty($nodeData['@value']))
			{
				$text = $this->_document->createTextNode($nodeData['@value']);
				$node->appendChild($text);

				return $node;
			}
			// Set text (with CDATA)
			else if (!empty($nodeData['@cdata']))
			{
				$text = $this->_document->createCDATASection($nodeData['@cdata']);
				$node->appendChild($text);

				return $node;
			}
		}
		else
		{
			$text = $this->_document->createTextNode($nodeData);
			$node->appendChild($text);

			return $node;
		}

		// RECURSIVE PART
		if (\is_array($nodeData))
		{
			foreach ($nodeData as $subNodeName => $subNodeData)
			{
				if (\substr($subNodeName, 0, 1) == '@')
				{
					continue;
				}
				if(\is_array($subNodeData) && \is_numeric(\key($subNodeData)))
				{
					foreach ($subNodeData as $index => $subSubNodeData)
					{
						$node->appendChild(self::parseElements($subNodeName, $subSubNodeData));
					}
				}
				else
				{
					$node->appendChild(self::parseElements($subNodeName, $subNodeData));
				}

				unset($nodeData[$subNodeName]);
			}
		}

		return $node;
	}

	/**
	 * Parse
	 *
	 * @return \DOMDocument
	 */
	public function parse()
	{
		$this->_document = new \DOMDocument();

		foreach ($this->_data as $nodeName => $nodeData)
		{
			$node = $this->parseElements($nodeName, $nodeData);
			$this->_document->appendChild($node);
		}

		return $this->_document->saveXML();
	}

	/**
	 * Write
	 *
	 * @return bool
	 */
	public function write()
	{
		if (empty($this->_document))
		{
			$this->parse();
		}

		$file = \realpath($this->_path).DIRECTORY_SEPARATOR.$this->_filename;
		if (\is_writable(\realpath($this->_path).DIRECTORY_SEPARATOR))
		{
			return (bool)\file_put_contents($file, $this->_document->saveXML());
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

		$export->setData($data);

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