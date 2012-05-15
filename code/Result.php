<?php
class oEmbed_Result extends RESTClient {
	public function load($url) {
		$this->Base = $url;
		
		if($response = $this->request(array(), array(CURLOPT_FOLLOWLOCATION => true))) {
			switch(curl_getinfo($this->curl, CURLINFO_CONTENT_TYPE)) {
				default:
				case "application/json":
					$oembed = json_decode($response, true);
					break;
				case "text/xml":
					$oembed = $this->fromXML($response);
					break;
			}
			if(!$oembed) return false;
		} else return false;
		
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
		return false;
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
		
		return $oembed;
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
}

class oEmbed_Result_Photo extends oEmbed_Result_Type {
	public $url;
	public $width;
	public $height;
	
	public function forTemplate() {
		return "<img src=\"{$this->url}\" width=\"{$this->width}\" height=\"{$this->height}\" />";
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
		return "<a href=\"{$this->url}\">{$this->title}</a>";
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
