<?php
/**
 * This file is part of the GcLib library.
 */

namespace GcLib\Request;

/**
 * Request\FileTypes
 *
 * @author T4lus
 * @version $Revision: 65 $
 * @date $Date: 2012-09-10 17:00:59 +0200 (Mon, 10 Sep 2012) $
 * @package GcLib
 */
abstract class FileTypes
{
	/** Simple constants for different types */
	const	JPG		= 10,
			GIF		= 11,
			PNG		= 12,
			TIFF	= 13,
			BMP		= 14,
			ICO		= 15,

			AVI		= 20,
			FLV		= 21,
			MOV		= 22,
			MPEG	= 23,
			VOB		= 24,

			PDF		= 30,
			DOC		= 31,
			XLS		= 32,
			PPT		= 33,
			ODP		= 34,
			ODS		= 35,
			ODT		= 36,

			ZIP		= 40,
			RAR		= 41,

			HTML	= 50,
			XML		= 51,

			CSV		= 52;

	/** @var array File types */
	protected $_types = array(
		// Images
		self::JPG	=> array('types' => array('image/jpe', 'image/jpg', 'image/jpeg', 'image/pjpeg'), 'extensions' => array('jpe', 'jpg', 'jpeg')),
		self::GIF	=> array('types' => array('image/gif'), 'extensions' => array('gif')),
		self::PNG	=> array('types' => array('image/png', 'image/x-png'), 'extensions' => array('png')),
		self::TIFF	=> array('types' => array('image/tiff'), 'extensions' => array('tiff', 'tif')),
		self::BMP	=> array('types' => array('image/bmp'),	'extensions' => array('bmp')),
		self::ICO	=> array('types' => array('image/x-icon'), 'extensions' => array('ico')),

		// Videos
		self::AVI	=> array('types' => array('video/x-msvideo'), 'extensions' => array('avi')),
		self::FLV	=> array('types' => array('video/x-flv'), 'extensions' => array('flv')),
		self::MOV	=> array('types' => array('video/quicktime'), 'extensions' => array('mov', 'qt')),
		self::MPEG	=> array('types' => array('video/mpeg'), 'extensions' => array('mprh', 'mpg', 'mp2')),
		self::VOB	=> array('types' => array('video/x-mpg'), 'extensions' => array('vob')),

		// Documents
		self::PDF	=> array('types' => array('application/pdf'), 'extensions' => array('pdf')),
		self::DOC	=> array('types' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'), 'extensions' => array('doc', 'docx')),
		self::XLS	=> array('types' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'), 'extensions' => array('xls', 'xlsx')),
		self::PPT	=> array('types' => array('application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.ms-powerpoint'), 'extensions' => array('ppt', 'pptx')),
		self::ODP	=> array('types' => array('application/vnd.oasis.opendocument.presentation'), 'extensions' => array('odp')),
		self::ODS	=> array('types' => array('application/vnd.oasis.opendocument.spreadsheet'), 'extensions' => array('ods')),
		self::ODT	=> array('types' => array('application/vnd.oasis.opendocument.text'), 'extensions' => array('odt')),

		// Archives
		self::ZIP	=> array('types' => array('application/zip', 'application/x-zip-compressed'), 'extensions' => array('zip')),
		self::RAR	=> array('types' => array('application/rar', 'application/x-rar-compressed'), 'extensions' => array('rar')),

		// Text
		self::HTML	=> array('types' => array('text/html'),	'extensions' => array('html', 'htm')),
		self::XML	=> array('types' => array('text/xml'),	'extensions' => array('xml')),

		// CSV
		self::CSV	=> array('types' => array('text/csv', 'application/vnd.ms-excel', 'text/comma-separated-values'), 'extensions' => array('csv')),
	);
}