<?php
class oEmbed_Result {
	public function load($url) {
		$cache = SS_Cache::factory('oembed');
		if($result = $cache->load(md5($url))) return unserialize($result);
		if($response = file_get_contents($url)) {
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
	
	public function loadData($data, $format) {
		switch($format) {
			case 'xml':
				return $this->toResult($this->fromXML($data));
			case 'json':
				return $this->toResult($this->fromJSON($data));
		}
		return false;
	}
	
	protected function fromJSON($json) {
		return json_decode($json, true);
	}
	
	protected function fromXML($xml) {
		$document = DOMDocument::loadXML($xml);
		if(!$document) return false;
		
		$xpath = new DOMXPath($document);
		
		$oembed = array(
			"type" => $xpath->evaluate("//oembed/type"),
			"version" => $xpath->evaluate("//oembed/version"),
			"title" => $xpath->evaluate("//oembed/title"),
			"author_name" => $xpath->evaluate("//oembed/author_name"),
			"author_url" => $xpath->evaluate("//oembed/author_url"),
			"provider_name" => $xpath->evaluate("//oembed/provider_name"),
			"provider_url" => $xpath->evaluate("//oembed/provider_url"),
			"cache_age" => $xpath->evaluate("//oembed/cache_age"),
			"thumbnail_url" => $xpath->evaluate("//oembed/thumbnail_url"),
			"thumbnail_width" => $xpath->evaluate("//oemebed/thumbnail_width"),
			"thumbnail_height" => $xpath->evaluate("//oembed/thumbnail_height"),
			// photo type
			"url" => $xpath->evaluate("//oembed/url"),
			// video and rich type
			"html" => $xpath->evaluate("//oembed/html"),
			// photo, video and rich type
			"width" => $xpath->evaluate("//oembed/width"),
			"height" => $xpath->evaluate("//oembed/height")
		);
		
		foreach($oembed as $key => $value)
			if(is_a($value, 'DOMNodeList') && $value->length > 0)
				$oembed[$key] = $value->item(0)->textContent;
			else unset($oembed[$key]);
		
		return $oembed;
	}
	
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

abstract class oEmbed_Result_Type extends ViewableData {
	public $type;
	public $version;
	public $title;
	public $author_name;
	public $author_url;
	public $provider_name;
	public $provider_url;
	public $cache_age;
	public $thumbnail_url;
	public $thumbnail_width;
	public $thumbnail_height;

	public function __construct($oembed) {
		parent::__construct();
		foreach($oembed as $key => $value) $this->$key = $value;
	}
	
	public function Thumbnail($width = null, $height = null) {
		return new oEmbed_Result_Photo(array(
			"url" => $this->thumbnail_url,
			"width" => $width?:$this->thumbnail_width,
			"height" => $height?:$this->thumbnail_height
		));
	}
}

class oEmbed_Result_Photo extends oEmbed_Result_Type {
	public $url;
	public $width;
	public $height;
	
	public function forTemplate() {
		$url = htmlentities($this->url);
		return "<img src=\"{$url}\" width=\"{$this->width}\" height=\"{$this->height}\" />";
	}
}

class oEmbed_Result_Video extends oEmbed_Result_Type {
	public $html;
	public $width;
	public $height;
	
	public function forTemplate() {
		return $this->html;
	}
}

class oEmbed_Result_Link extends oEmbed_Result_Type {
	public $url;

	public function forTemplate() {
		$url = htmlentities($this->url);
		$title = htmlspecialchars($this->title);
		return "<a href=\"{$url}\">{$title}</a>";
	}
}

class oEmbed_Result_Rich extends oEmbed_Result_Type {
	public $html;
	public $width;
	public $height;
	
	public function forTemplate() {
		return $this->html;
	}
}
