<?php

oEmbed::add_providers(array(
	'http://*.youtube.com/watch*' => 'http://www.youtube.com/oembed/',
	'http://*.flickr.com/*' => 'http://www.flickr.com/services/oembed/',
	'http://*.viddler.com/*' => 'http://lab.viddler.com/services/oembed/',
	'http://*.revision3.com/*' => 'http://revision3.com/api/oembed/',
	'http://*.hulu.com/watch/*' => 'http://www.hulu.com/api/oembed.json',
	'http://*.vimeo.com/*' => 'http://www.vimeo.com/api/oembed.json',
));

ShortcodeParser::get('default')->register('embed', array('oEmbed', 'handle_shortcode'));
