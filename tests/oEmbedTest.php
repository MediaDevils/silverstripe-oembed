<?php
class oEmbedTest extends SapphireTest {
	public static $fixture_file = "oembed/tests/data/fixture.yml";
	
	public static $providers = array(
		'http://*.youtube.com/watch*' => 'http://www.youtube.com/oembed/',
		'http://*.flickr.com/*' => 'http://www.flickr.com/services/oembed/',
		'http://*.viddler.com/*' => 'http://lab.viddler.com/services/oembed/',
		'http://*.revision3.com/*' => 'http://revision3.com/api/oembed/',
		'http://*.hulu.com/watch/*' => 'http://www.hulu.com/api/oembed.json',
		'http://*.vimeo.com/*' => 'http://www.vimeo.com/api/oembed.json',
		'https://twitter.com/*' => 'https://api.twitter.com/1/statuses/oembed.json',
		'http://twitter.com/*' => 'https://api.twitter.com/1/statuses/oembed.json',
	);
	
	public static $examplesMatch = array(
		'http://*.youtube.com/watch*' => 'http://www.youtube.com/watch?v=BNZCZsHJIR8',
		'http://*.flickr.com/*' => 'http://www.flickr.com/photos/leadnow/7323397318/',
		'http://*.vimeo.com/*' => 'http://vimeo.com/35311255'
	);
	
	public static $examplesNotMatch = array(
		'http://*.youtube.com/watch*' => 'http://www.youtube.com',
		'http://*.flickr.com/*' => 'http://www.flickr.com'
	);
	
	public static $exampleLinks = array(
		'http://*.youtube.com/watch*' => 'http://www.youtube.com/oembed?url=http%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3DBNZCZsHJIR8&format=json',
		'http://*.vimeo.com/*' => 'http://vimeo.com/api/oembed.json?url=http%3A%2F%2Fvimeo.com%2F35311255'
	);
	
	public function testadd_providers() {
		oEmbed::empty_providers();
		$this->assertEmpty(oEmbed::get_providers());
		oEmbed::add_providers(self::$providers);
		$this->assertNotEmpty(oEmbed::get_providers());
		oEmbed::empty_providers();
	}
	
	public function testremove_provider() {
		oEmbed::empty_providers();
		$provider = array_slice(self::$providers, 0, 1);
		$scheme = key($provider);
		oEmbed::add_providers($provider);
		
		$providers = oEmbed::get_providers();
		$this->assertTrue(array_slice(self::$providers, 0, 1) == $provider);
		
		oEmbed::remove_provider($scheme);
		
		$providers = oEmbed::get_providers();
		$this->assertEmpty($providers);
	}
	
	public function testempty_providers() {
		oEmbed::add_providers(self::$providers);
	
		oEmbed::empty_providers();
		$this->assertEmpty(oEmbed::get_providers());
	}
	
	public function testget_providers() {
		oEmbed::add_providers(self::$providers);
	
		$this->assertNotEmpty(oEmbed::get_providers());
	}
	
	public function testmatch_url() {
		oEmbed::add_providers(self::$providers);
		
		foreach(self::$examplesMatch as $scheme => $url)
			$this->assertEquals(oEmbed::match_url($url), self::$providers[$scheme]);
			
		foreach(self::$examplesNotMatch as $scheme => $url)
			$this->assertFalse(oEmbed::match_url($url));
	}
	
	public function testmatch_scheme() {
		oEmbed::add_providers(self::$providers);
		
		foreach(self::$examplesMatch as $scheme => $url)
			$this->assertTrue(oEmbed::match_scheme($url, $scheme));
			
		foreach(self::$examplesNotMatch as $scheme => $url)
			$this->assertFalse(oEmbed::match_scheme($url, $scheme));
	}
	
	public function testautodiscover_from_url() {
		oEmbed::add_providers(self::$providers);
		
		foreach(self::$exampleLinks as $scheme => $url) {
			$result = oEmbed::autodiscover_from_url(self::$examplesMatch[$scheme]);
			$this->assertEquals($url, $result);
		}
	}
	
	public function testget_oembed_from_url() {
		oEmbed::add_providers(self::$providers);
		
		foreach(self::$examplesMatch as $scheme => $url) {
			$result = oEmbed::get_oembed_from_url($url);
			$this->assertInstanceOf('oEmbed_Result_Type', $result);
		}
	}
	
	public function testhandle_shortcode() {
		oEmbed::add_providers(self::$providers);
		
		foreach(self::$examplesMatch as $scheme => $url) {
			$result = oEmbed::handle_shortcode(array(), $url, null, null);
			$this->assertTrue(is_string($result));
		}
	}
}
