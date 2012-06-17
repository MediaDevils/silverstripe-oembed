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
 * @license   none
 * @version   2.0
 * @link      https://github.com/MediaDevils/silverstripe-oembed
 */


/**
 * oEmbed
 * 
 * Provides oEmbed querying and shortcode embedding.
 * 
 * @category  module
 * @package   SilverStripe
 * @author    Justin Martin <frozenfire@thefrozenfire.com>
 * @copyright 2012 Justin Martin
 * @version   Release: @package_version@
 * @link      https://github.com/MediaDevils/silverstripe-oembed
 */
class oEmbed {

	/**
	 * Array of providers in the format array([<pattern> => <endpoint url>[, ...]])
	 * @var array	
	 * @access protected
	 * @static
	 */
	protected static $providers = array();
	
	/**
	 * Whether to fall back to autodiscovery if the provided URL does not match
	 * @var boolean  
	 * @access protected
	 * @static
	 */
	protected static $autodiscover_fallback = false;
	
	/**
	 * Set whether to autodiscover
	 * 
	 * Set whether to autodiscover when no provider exists for the given URL
	 * 
	 * @param boolean $val Whether to autodiscover
	 * @return void   
	 * @access public 
	 * @static
	 */
	public static function set_autodiscover_fallback($val) {
		self::$autodiscover_fallback = (bool)$val;
	}
	
	/**
	 * Add oEmbed providers
	 * 
	 * Add one or more oEmbed providers in the format 
	 * array([<pattern> => <endpoint url>[, ...]])
	 * 
	 * @return void  
	 * @access public
	 * @static
	 */
	public static function add_providers() {
		$args = func_get_args();
		if($args) {
			if(is_array($args[0])) {
				self::$providers += $args[0];
			} elseif(count($args) == 2) {
				self::$providers[$args[0]] = $args[1];
			} else {
				user_error(__METHOD__ . ' expects either an array of providers or a URL scheme and JSON API endpoint');
			}
		} else {
			user_error(__METHOD__ . ' expects either an array of providers or a URL scheme and JSON API endpoint');
		}
	}
	
	/**
	 * Remove a provider by scheme
	 * 
	 * Remove a provider from the list of providers, by specifying the matching
	 * scheme
	 * 
	 * @param string $scheme The scheme of the provider to remove
	 * @return void   
	 * @access public 
	 * @static
	 */
	public static function remove_provider($scheme) {
		unset(self::$providers[$scheme]);
	}
	
	/**
	 * Remove all providers
	 * 
	 * @return void  
	 * @access public
	 * @static
	 */
	public static function empty_providers() {
		self::$providers = array();
	}
	
	/**
	 * Get the list of providers
	 * 
	 * Get the list of providers in the format
	 * array([<pattern> => <endpoint url>[, ...]])
	 * 
	 * @return array  The array of providers
	 * @access public
	 * @static
	 */
	public static function get_providers() {
		return self::$providers;
	}
	
	/**
	 * Matches a URL to a provider
	 * 
	 * Matches a URL to a provider by comparing each provider pattern against
	 * the URL, returning the endpoint URL of the first matched provider
	 * 
	 * @param string $url URL to match
	 * @return string Provider endpoint, or false if no match
	 * @access public 
	 * @static
	 */
	public static function match_url($url) {
		foreach(self::$providers as $scheme=>$endpoint) {
			if(self::match_scheme($url, $scheme)) {
				return $endpoint;
			}
		}
		return false;
	}
	
	/**
	 * Match a URL to a scheme
	 * 
	 * Determines whether the provided $url is matched by $scheme
	 * 
	 * @param string $url	URL to match
	 * @param string $scheme Scheme to attempt to match $url with
	 * @return boolean Whether $url matches $scheme
	 * @access public 
	 * @static
	 */
	public static function match_scheme($url, $scheme) {
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
	
	/**
	 * Try to autodiscover the endpoint for a URL
	 * 
	 * Try to autodiscover the endpoint for a URL by scraping the URL's HTML
	 * for an endpoint link.
	 * 
	 * @param string $url URL to autodiscover an endpoint from
	 * @return string   Endpoint URL for the specified resource
	 * @access public 
	 * @static
	 */
	public static function autodiscover_from_url($url) {
		$cache = SS_Cache::factory('oembed_autodiscover');
		if($result = $cache->load(md5($url))) return $result;
	
		$body = file_get_contents($url);
		
		if(preg_match_all('#<link[^>]+?(?:href=[\'"](.+?)[\'"][^>]+?)?type=["\']application/json\+oembed["\'](?:[^>]+?href=[\'"](.+?)[\'"])?#', $body, $matches, PREG_SET_ORDER)) {
			$match = $matches[0];
			if(!empty($match[1])) {
				$result = html_entity_decode($match[1]);
				$cache->save($result);
				return $result;
			}
			if(!empty($match[2])) {
				$result = html_entity_decode($match[2]);
				$cache->save($result);
				return $result;
			}
		}
		return false;
	}
	
	/**
	 * Get an oEmbed result object from a URL
	 * 
	 * Get an oEmbed result object from a URL if a provider matches, and
	 * optionally to autodiscover the oEmbed provider if no match is found.
	 * 
	 * @param string $url	 URL to get an oEmbed result for
	 * @param boolean $type	@deprecated
	 * @param array   $options Custom query parameters to poll the oEmbed service with
	 * @return oEmbed_Result_Type   An oEmbed result object, or false on failure
	 * @access public 
	 * @static
	 */
	public static function get_oembed_from_url($url, $type = false, Array $options = array()) {
		$endpoint = self::match_url($url);
		$ourl = false;
		if(!$endpoint) {
			if(self::$autodiscover_fallback) {
				$ourl = self::autodiscover_from_url($url);
			}
		} elseif($endpoint === true) {
			$ourl = self::autodiscover_from_url($url);
		} else {
			$ourl = Controller::join_links($endpoint, '?url=' . rawurlencode($url));
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
			$result = new oEmbed_Result();
			$oembed = $result->load($ourl);
			if(is_a($oembed, 'oEmbed_Result_Link')) $oembed->url = $url;
			return $oembed;
		}
		return false;
	}
	
	/**
	 * Return the embed HTML for a given [embed <url>] tag
	 * 
	 * This method handles the shortcode embedding via the WYSIWYG editor
	 * 
	 * @param array   $arguments Custom query parameters to poll the oEmbed service with
	 * @param string  $url	   URL to get the oEmbed result for
	 * @param unknown $parser	Shortcode parser handle
	 * @param unknown $shortcode Shortcode being handled
	 * @return string   HTML markup for the embed
	 * @access public 
	 * @static
	 */
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
