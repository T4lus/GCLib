<?php
/**
 * This file is part of the GcLib library.
 */
namespace GcLib;
/**
 * Feed
 *
 * @author T4lus
 * @version $Revision: 59 $
 * @date $Date: 2012-09-10 13:10:43 +0200 (Mon, 10 Sep 2012) $
 * @package GcLib
 */
class Feed {
	/** Format */
	const	FORMAT_RSS	= 0,
			FORMAT_RSS2	= 1,
			FORMAT_ATOM	= 2;

	/** @var string Title */
	protected $_title = '';

	/** @var string Description */
	protected $_description = '';

	/** @var string Link */
	protected $_link = '';

	/** @var string Language */
	protected $_language = 'fr';

	/** @var int Feed format */
	protected $_format = self::FORMAT_RSS2;

	/** @var array Image properties */
	protected $_image = array(
		'url'	=> '',
		'title'	=> '',
		'link'	=> '',
	);

	/** @var array Items */
	protected $_items = array();

	/**
	 * Constructor
	 */
	public function __construct($format = self::FORMAT_RSS2) {
		$this->_format = $format;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->_title = $title;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->_description = $description;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->_description;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->_title;
	}

	/**
	 * @param string $link
	 */
	public function setLink($link) {
		$this->_link = $link;
	}

	/**
	 * @return string
	 */
	public function getLink() {
		return $this->_link;
	}

	/**
	 * @param int $format
	 */
	public function setFormat($format) {
		$this->_format = $format;
	}

	/**
	 * @return int
	 */
	public function getFormat() {
		return $this->_format;
	}

	/**
	 * Set Image
	 *
	 * @param $url
	 * @param null $link
	 * @param null $title
	 */
	public function setImage($url, $link = NULL, $title = NULL) {
		$this->_image['url']	= $url;
		$this->_image['link']	= \is_null($link) ? $this->_link : $link;
		$this->_image['title']	= \is_null($title) ? $this->_title : $title;
	}

	/**
	 * @return array
	 */
	public function getImage() {
		return $this->_image;
	}

	/**
	 * @return array
	 */
	public function getItems() {
		return $this->_items;
	}

	/**
	 * Add item
	 *
	 * @param $title
	 * @param $link
	 * @param $description
	 * @param null $date
	 * @param null $author
	 */
	public function addItem($title, $link, $description, $date = NULL, $author = NULL) {
		$item = new \stdClass();

		$item->date = NULL;
		if (!\is_null($date)) {
			$date = new \DateTime($date);
			$item->date = $date->format(\DateTime::RSS);
		}

		$item->title		= $title;
		$item->link			= $link;
		$item->description	= $description;
		$item->author		= $author;

		$this->_items[] = $item;
	}

	/**
	 * Parse RSS2
	 *
	 * @return string
	 */
	public function parseRSS2() {
		$document = new \DOMDocument();

		// Node : Rss
		$nodeRss = $document->createElement('rss');
		$nodeRss->setAttribute('version', '2.0');
		$document->appendChild($nodeRss);

		// Node : Rss > Channel
		$nodeChannel = $document->createElement('channel');
		$nodeRss->appendChild($nodeChannel);

		// Node : Rss > Channel > Title
		$nodeTitle = $document->createElement('title', $this->_title);
		$nodeChannel->appendChild($nodeTitle);

		// Node : Rss > Channel > Link
		$nodeLink = $document->createElement('link', $this->_link);
		$nodeChannel->appendChild($nodeLink);

		// Node : Rss > Channel > Description
		$nodeDescription = $document->createElement('description', $this->_description);
		$nodeChannel->appendChild($nodeDescription);

		// Node : Rss > Channel > Language
		$nodeLanguage = $document->createElement('language', $this->_language);
		$nodeChannel->appendChild($nodeLanguage);

		// Node : Rss > Channel > Image
		if (!empty($this->_image['url'])) {
			$nodeImage = $document->createElement('image');
			$nodeChannel->appendChild($nodeImage);

			// Node : Rss > Channel > Image > url
			$nodeUrl = $document->createElement('url', $this->_image['url']);
			$nodeImage->appendChild($nodeUrl);

			// Node : Rss > Channel > Image > url
			$nodeTitle = $document->createElement('title', $this->_image['title']);
			$nodeImage->appendChild($nodeTitle);

			// Node : Rss > Channel > Image > url
			$nodeLink = $document->createElement('link', $this->_image['link']);
			$nodeImage->appendChild($nodeLink);
		}

		foreach ($this->_items as $item) {
			// Nodes : Rss > Channel > Item
			$nodeItem  = $document->createElement('item');

			// Nodes : Rss > Channel > Item > Title
			$nodeTitle = $document->createElement('title', $item->title);
			$nodeItem->appendChild($nodeTitle);

			// Nodes : Rss > Channel > Item > Link
			$nodeLink = $document->createElement('link', $item->link);
			$nodeItem->appendChild($nodeLink);

			// Nodes : Rss > Channel > Item > Description
			$nodeDescription = $document->createElement('description', $item->description);
			$nodeItem->appendChild($nodeDescription);

			// Nodes : Rss > Channel > Item > PubDate
			if (!\is_null($item->date)) {
				$nodePubDate = $document->createElement('pubDate', $item->date);
				$nodeItem->appendChild($nodePubDate);
			}

			// Nodes : Rss > Channel > Item > Author
			if (!\is_null($item->author)) {
				$nodeAuthor = $document->createElement('author', $item->author);
				$nodeItem->appendChild($nodeAuthor);
			}

			$nodeChannel->appendChild($nodeItem);
		}

		return $document->saveXML();
	}

	/**
	 * Parse
	 *
	 * @return string
	 */
	public function parse() {
		switch($this->_format) {
			case self::FORMAT_RSS:
				// @todo RSS 0.91 & 0.92
			case self::FORMAT_ATOM:
				// @todo ATOM
			case self::FORMAT_RSS2:
				return $this->parseRSS2();
		}
	}
}