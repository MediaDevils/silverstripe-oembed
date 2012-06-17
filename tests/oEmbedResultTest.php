<?php
class oEmbedResultTest extends SapphireTest {
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
	
	public static $examples = array(
		'http://*.youtube.com/watch*' => 'http://www.youtube.com/watch?v=BNZCZsHJIR8',
		'http://*.flickr.com/*' => 'http://www.flickr.com/photos/leadnow/7323397318/',
		'http://*.vimeo.com/*' => 'http://vimeo.com/35311255'
	);
	
	public function provider() {
		$results = array();
		foreach(self::$examples as $scheme => $url) {
			$results[] = array(oEmbed::get_oembed_from_url($url), $scheme);
		}
		return $results;
	}
	
	public function testload() {
		$oembed = new oEmbed_Result();
		foreach(self::$examples as $scheme => $url) {
			$result = $oembed->load($url);
			$this->assertInstanceOf('oEmbed_Result_Type', $result);
		}
	}
	
	public function testloadUncached() {
		$cache = SS_Cache::factory('oembed');
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
	
		$oembed = new oEmbed_Result();
		foreach(self::$examples as $scheme => $url) {
			$result = $oembed->load($url);
			$this->assertInstanceOf('oEmbed_Result_Type', $result);
		}
	}
	
	public function testloadData() {
		$oembed = new oEmbed_Result();
		
		$result = $oembed->loadData(file_get_contents(__DIR__."/data/test.json"), 'json');
		$this->assertInstanceOf('oEmbed_Result_Type', $result);
		
		$result = $oembed->loadData(file_get_contents(__DIR__."/data/test.xml"), 'xml');
		$this->assertInstanceOf('oEmbed_Result_Type', $result);
	}
	
	/**
	* @dataProvider provider
	*/
	public function testResult($result, $scheme) {
		$this->assertTrue(in_array($result->type, array('photo', 'video', 'link', 'rich')));
		$this->assertEquals($result->version, '1.0');
	}
}
