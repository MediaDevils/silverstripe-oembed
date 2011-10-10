<?php

class oEmbed_Result extends ViewableData {
	protected $data = false;
	protected $origin = false;
	protected $type = false;
	protected $url;
	
	public static $casting = array(
		'html' => 'HTMLText',
	);
	
	public function __construct($url, $origin = false, $type = false) {
		$this->url = $url;
		$this->origin = $origin;
		$this->type = $type;
		
		parent::__construct();
	}
	
	protected function loadData() {
		if($this->data !== false) {
			return;
		}
		$service = new RestfulService($this->url);
		$body = $service->request();
		if(!$body || $body->isError()) {
			$this->data = array();
			return;
		}
		$body = $body->getBody();
		$data = json_decode($body, true);
		if(!$data) {
			$data = array();
		}
		foreach($data as $k=>$v) {
			unset($data[$k]);
			$data[strtolower($k)] = $v;
		}
		if($this->type && $this->type != $data['type']) {
			$data = array();
		}
		$this->data = $data;
	}
	
	public function hasField($field) {
		$this->loadData();
		return array_key_exists(strtolower($field), $this->data);
	}
	
	public function getField($field) {
		$field = strtolower($field);
		if($this->hasField($field)) {
			return $this->data[$field];
		}
	}
	
	public function forTemplate() {
		$this->loadData();
		switch($this->Type) {
			case 'video':
			case 'rich':
				return $this->HTML;
				break;
			case 'link':
				return '<a href="' . $this->origin . '">' . $this->Title . '</a>';
				break;
			case 'photo':
				return "<img src='$this->URL' width='$this->Width' height='$this->Height' />";
				break;
		}
	}
	
	public function exists() {
		$this->loadData();
		return count($this->data) > 0;
	}
}
