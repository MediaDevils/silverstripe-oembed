<?php

oEmbed::add_providers(array(
	'http://*.youtube.com/watch*' => 'http://www.youtube.com/oembed/',
	'http://*.flickr.com/*' => true,
	'http://*.viddler.com/*' => 'http://lab.viddler.com/services/oembed/',
	'http://*.revision3.com/*' => 'http://revision3.com/api/oembed/',
	'http://*.hulu.com/watch/*' => 'http://www.hulu.com/api/oembed.json',
	'http://*.vimeo.com/*' => 'http://www.vimeo.com/api/oembed.json',
));
