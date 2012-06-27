<?php

/**
 * SilverStripe oEmbed
 * 
 * Provides oEmbed querying and shortcode embedding.
 * 
 * @category  module
 * @package   SilverStripe
 * @author    Justin Martin <frozenfire@thefrozenfire.com>
 * @copyright 2012 Justin Martin
 * @version   2.0
 * @link      https://github.com/MediaDevils/silverstripe-oembed
 */

/**
 * oEmbed Result
 * 
 * Handles the processing of URLs into oEmbed result objects.
 * 
 * @category  module
 * @package   SilverStripe
 * @author    Justin Martin <frozenfire@thefrozenfire.com>
 * @copyright 2012 Justin Martin
 * @version   Release: @package_version@
 * @link      https://github.com/MediaDevils/silverstripe-oembed
 */
class oEmbed_Result {

	/**
	 * Load a URL
	 * 
	 * Loads a URL's oEmbed data into an oEmbed result object
	 * 
	 * @param string $url URL of oEmbed endpoint to poll
	 * @return oEmbed_Result_Type oEmbed result object, or false on failure
	 * @access public 
	 */
	public function load($url) {
		$cache = SS_Cache::factory('oembed');
		if($result = $cache->load(md5($url))) return unserialize($result);
		$httpContext = stream_context_create(array(
			'http' => array(
				'ignore_errors' => true
			)
		));
		if($response = file_get_contents($url, false, $httpContext)) {
			switch($this->getContentType($http_response_header)) {
				default:
				case "application/json":
					$oembed = $this->fromJSON($response);
					break;
				case "text/xml":
					$oembed = $this->fromXML($response);
					break;
			}
			if(!$oembed) return false;
		} else return false;
		
		if($result = $this->toResult($oembed)) {
			$cache->save(serialize($result));
			return $result;
		} else return false;
	}
	
	/**
	 * Loads oEmbed data
	 * 
	 * Loads oEmbed data in JSON or XML format into an oEmbed result object
	 * 
	 * @param string $data   JSON or XML representation of an oEmbed object
	 * @param string $format 'json' or 'xml'
	 * @return oEmbed_Result_Type oEmbed result object, or false on failure
	 * @access public 
	 */
	public function loadData($data, $format) {
		switch($format) {
			case 'xml':
				return $this->toResult($this->fromXML($data));
			case 'json':
				return $this->toResult($this->fromJSON($data));
		}
		return false;
	}
	
	/**
	 * Convert JSON into an oEmbed array
	 * 
	 * Convert JSON into an array, representing an oEmbed object
	 * 
	 * @param string   $json JSON to convert
	 * @return array   Decoded array
	 * @access protected
	 */
	protected function fromJSON($json) {
		return json_decode($json, true);
	}
	
	/**
	 * Convert XML into an oEmbed array
	 * 
	 * Convert XML into an array, representing an oEmbed object
	 * 
	 * @param string   $xml XML document to parse
	 * @return array   Decoded array
	 * @access protected
	 */
	protected function fromXML($xml) {
		$document = DOMDocument::loadXML($xml);
		if(!$document) return false;
		
		$xpath = new DOMXPath($document);
		
		$oembed = $xpath->evaluate("//oembed/*");
		$result = array();
		
		foreach($oembed as $node) {
			$result[$node->tagName] = $node->textContent;
		}
		
		return $result;
	}
	
	/**
	 * Build an oEmbed result
	 * 
	 * Build an oEmbed result from an array of properties and values
	 * 
	 * @param array  $oembed Array representation of the oEmbed result
	 * @return oEmbed_Result_Type  oEmbed result object
	 * @access public
	 */
	public function toResult($oembed) {
		switch($oembed["type"]) {
			case 'photo':
				return new oEmbed_Result_Photo($oembed);
			case 'video':
				return new oEmbed_Result_Video($oembed);
			case 'link':
				return new oEmbed_Result_Link($oembed);
			case 'rich':
				return new oEmbed_Result_Rich($oembed);
		}
	}
	
	/**
	 * Gets the content type
	 * 
	 * Gets the content type for an oEmbed result document from its response
	 * headers.
	 * 
	 * @param array  $headers HTTP response headers from $http_response_header
	 * @return string The content type
	 * @access public
	 */
	public function getContentType($headers) {
		foreach($headers as $header) {
			if(strtolower(substr($header, 0, 13)) == "content-type:") {
				switch(true) {
					case stristr($header, 'application/json'):
						return 'application/json';
					case stristr($header, 'text/xml'):
						return 'text/xml';
				}
			}
		}
	}
}

/**
 * Abstract class for oEmbed result objects
 * 
 * Base class for all oEmbed result objects. Represents the common properties.
 * 
 * @category  module
 * @package   SilverStripe
 * @author	Justin Martin <frozenfire@thefrozenfire.com>
 * @copyright 2012 Justin Martin
 * @version   Release: @package_version@
 * @link	  https://github.com/MediaDevils/silverstripe-oembed
 */
abstract class oEmbed_Result_Type extends ViewableData {

	/**
	 * Result type
	 * @var string
	 * @access public 
	 */
	public $type;

	/**
	 * Description for public
	 * @var unknown
	 * @access public 
	 */
	public $version;

	/**
	 * Description for public
	 * @var unknown
	 * @access public 
	 */
	public $title;

	/**
	 * Description for public
	 * @var unknown
	 * @access public 
	 */
	public $author_name;

	/**
	 * Description for public
	 * @var unknown
	 * @access public 
	 */
	public $author_url;

	/**
	 * Name of the provider
	 * @var string
	 * @access public 
	 */
	public $provider_name;

	/**
	 * URL of the provider
	 * @var string
	 * @access public 
	 */
	public $provider_url;

	/**
	 * Seconds to cache result
	 * @var int
	 * @access public 
	 */
	public $cache_age;

	/**
	 * URL of a thumbnail
	 * @var string
	 * @access public 
	 */
	public $thumbnail_url;

	/**
	 * Width in pixels of the thumbnail
	 * @var int
	 * @access public 
	 */
	public $thumbnail_width;

	/**
	 * Height in pixels of the thumbnail
	 * @var int
	 * @access public 
	 */
	public $thumbnail_height;

	/**
	 * Constructor for the result object
	 * 
	 * @param array  $oembed Array of properties and values
	 * @return oEmbed_Result_Type  
	 * @access public
	 */
	public function __construct($oembed) {
		parent::__construct();
		foreach($oembed as $key => $value) $this->$key = $value;
	}
	
	/**
	 * Gets the thumbnail for the result
	 * 
	 * Gets the thumbnail for the result, as a photo type
	 * 
	 * @param unknown $width  Maximum width of the image
	 * @param unknown $height Maximum height of the image
	 * @return oEmbed_Result_Photo The thumbnail
	 * @access public 
	 */
	public function Thumbnail($width = null, $height = null) {
		return new oEmbed_Result_Photo(array(
			"url" => $this->thumbnail_url,
			"width" => $width?:$this->thumbnail_width,
			"height" => $height?:$this->thumbnail_height
		));
	}
}

/**
 * oEmbed Photo Result
 * 
 * An oEmbed photo result, and the thumbnail type
 * 
 * @category  module
 * @package   SilverStripe
 * @author	Justin Martin <frozenfire@thefrozenfire.com>
 * @copyright 2012 Justin Martin
 * @version   Release: @package_version@
 * @link	  https://github.com/MediaDevils/silverstripe-oembed
 */
class oEmbed_Result_Photo extends oEmbed_Result_Type {

	/**
	 * Source URL for the image
	 * @var string
	 * @access public 
	 */
	public $url;

	/**
	 * Width of the image in pixels
	 * @var int
	 * @access public 
	 */
	public $width;

	/**
	 * Height of the image in pixels
	 * @var int
	 * @access public 
	 */
	public $height;
	
	/**
	 * HTML for displaying in a template
	 * 
	 * @return string HTML for displaying in a template
	 * @access public
	 */
	public function forTemplate() {
		$url = htmlentities($this->url);
		return "<img src=\"{$url}\" width=\"{$this->width}\" height=\"{$this->height}\" />";
	}
}

/**
 * oEmbed Video Result
 * 
 * An oEmbed video result
 * 
 * @category  module
 * @package   SilverStripe
 * @author	Justin Martin <frozenfire@thefrozenfire.com>
 * @copyright 2012 Justin Martin
 * @version   Release: @package_version@
 * @link	  https://github.com/MediaDevils/silverstripe-oembed
 */
class oEmbed_Result_Video extends oEmbed_Result_Type {

	/**
	 * HTML for embedding the video
	 * @var string
	 * @access public 
	 */
	public $html;

	/**
	 * Width of the video in pixels
	 * @var int
	 * @access public 
	 */
	public $width;

	/**
	 * Height of the video in pixels
	 * @var int
	 * @access public 
	 */
	public $height;
	
	/**
	 * HTML for displaying in a template
	 * 
	 * @return string HTML for displaying in a template
	 * @access public
	 */
	public function forTemplate() {
		return $this->html;
	}
}

/**
 * oEmbed Link Result
 * 
 * An oEmbed link result
 * 
 * @category  module
 * @package   SilverStripe
 * @author	Justin Martin <frozenfire@thefrozenfire.com>
 * @copyright 2012 Justin Martin
 * @version   Release: @package_version@
 * @link	  https://github.com/MediaDevils/silverstripe-oembed
 */
class oEmbed_Result_Link extends oEmbed_Result_Type {

	/**
	 * URL for the link
	 * @var string
	 * @access public 
	 */
	public $url;

	/**
	 * HTML for displaying in a template
	 * 
	 * @return string HTML for displaying in a template
	 * @access public
	 */
	public function forTemplate() {
		$url = htmlentities($this->url);
		$title = htmlspecialchars($this->title);
		return "<a href=\"{$url}\">{$title}</a>";
	}
}

/**
 * oEmbed Rich Result
 * 
 * An oEmbed rich result
 * 
 * @category  module
 * @package   SilverStripe
 * @author	Justin Martin <frozenfire@thefrozenfire.com>
 * @copyright 2012 Justin Martin
 * @version   Release: @package_version@
 * @link	  https://github.com/MediaDevils/silverstripe-oembed
 */
class oEmbed_Result_Rich extends oEmbed_Result_Type {

	/**
	 * HTML for embedding the rich content
	 * @var string
	 * @access public 
	 */
	public $html;

	/**
	 * Width of the rich content in pixels
	 * @var int
	 * @access public 
	 */
	public $width;

	/**
	 * Height of the rich content in pixels
	 * @var int
	 * @access public 
	 */
	public $height;
	
	/**
	 * HTML for displaying in a template
	 * 
	 * @return string HTML for displaying in a template
	 * @access public
	 */
	public function forTemplate() {
		return $this->html;
	}
}
