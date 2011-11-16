<?php

class oEmbed {
	protected static $providers = array();
	
	protected static $autodiscover_fallback = false;
	
	public static function set_autodiscover_fallback($val) {
		static::$autodiscover_fallback = (bool)$val;
	}
	
	public static function add_providers() {
		$args = func_get_args();
		if($args) {
			if(is_array($args[0])) {
				static::$providers += $args[0];
			} elseif(count($args) == 2) {
				static::$providers[$args[0]] = $args[1];
			} else {
				user_error(__METHOD__ . ' expects either an array of providers or a URL scheme and JSON API endpoint');
			}
		} else {
			user_error(__METHOD__ . ' expects either an array of providers or a URL scheme and JSON API endpoint');
		}
	}
	
	protected static function match_url($url) {
		foreach(static::$providers as $scheme=>$endpoint) {
			if(static::match_scheme($url, $scheme)) {
				return $endpoint;
			}
		}
		return false;
	}
	
	protected static function match_scheme($url, $scheme) {
		$urlInfo = parse_url($url);
		$schemeInfo = parse_url($scheme);
		foreach($schemeInfo as $k=>$v) {
			if(!array_key_exists($k, $urlInfo)) {
				return false;
			}
			if(strpos($v, '*') !== false) {
				$v = preg_quote($v, '/');
				$v = str_replace('\*', '.*', $v);
				if($k == 'host') {
					$v = str_replace('*\.', '*', $v);
				}
				if(!preg_match('/' . $v . '/', $urlInfo[$k])) {
					return false;
				}
			} elseif(strcasecmp($urlInfo[$k], $v)) {
				return false;
			}
		}
		return true;
	}
	
	protected static function autodiscover_from_url($url) {
		$service = new RestfulService($url);
		$body = $service->request();
		if(!$body || $body->isError()) {
			return false;
		}
		$body = $body->getBody();
		
		if(preg_match_all('#<link[^>]+?(?:href=[\'"](.+?)[\'"][^>]+?)?type=["\']application/json\+oembed["\'](?:[^>]+?href=[\'"](.+?)[\'"])?#', $body, $matches, PREG_SET_ORDER)) {
			$match = $matches[0];
			if(!empty($match[1])) {
				return html_entity_decode($match[1]);
			}
			if(!empty($match[2])) {
				return html_entity_decode($match[2]);
			}
		}
		return false;
	}
	
	public static function get_oembed_from_url($url, $type = false, Array $options = array()) {
		$endpoint = static::match_url($url);
		$ourl = false;
		if(!$endpoint) {
			if(static::$autodiscover_fallback) {
				$ourl = static::autodiscover_from_url($url);
			}
		} elseif($endpoint === true) {
			$ourl = static::autodiscover_from_url($url);
		} else {
			$ourl = Controller::join_links($endpoint, '?format=json&url=' . rawurlencode($url));
		}
		if($ourl) {
			if($options) {
				if(isset($options['width']) && !isset($options['maxwidth'])) {
					$options['maxwidth'] = $options['width'];
				}
				if(isset($options['height']) && !isset($options['maxheight'])) {
					$options['maxheight'] = $options['height'];
				}
				$ourl = Controller::join_links($ourl, '?' . http_build_query($options, '', '&'));
			}
			return new oEmbed_Result($ourl, $url, $type);
		}
		return false;
	}
	
	public static function handle_shortcode($arguments, $url, $parser, $shortcode) {
		if(isset($arguments['type'])) {
			$type = $arguments['type'];
			unset($arguments['type']);
		} else {
			$type = false;
		}
		$oembed = self::get_oembed_from_url($url, $type, $arguments);
		if($oembed && $oembed->exists()) {
			return $oembed->forTemplate();
		} else {
			return '<a href="' . $url . '">' . $url . '</a>';
		}
	}
}
