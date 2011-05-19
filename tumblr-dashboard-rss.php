<?php
/**
 * Tumblr Dashboard RSS Feed
 *
 * features: valid rss, cache-control, conditional get, easy config
 * requires: curl, dom, simplexml
 * @package Feeds
 * @author PJ Kix <pj@pjkix.com>
 * @copyright (cc) 2010 pjkix
 * @license http://creativecommons.org/licenses/by-nc-nd/3.0/
 * @see http://www.tumblr.com/docs/en/api
 * @version 1.2.0 $Id:$
 * @todo post types, make it more secure, multi-user friendly, compression, cacheing
 */

//* debug
ini_set('display_errors', TRUE) ;
error_reporting (E_ALL | E_STRICT) ;
//*/

/** Authorization info */
$tumblr['email']    = 'email@example.com';
$tumblr['password'] = 'password';

/** other settings*/
$_DEBUG = TRUE;
$cache_request = TRUE;
$cache_output = TRUE;
$cache_ttl = '300'; // 5m in seconds
$cache_dir = './cache'; // make sure this is writeable for www server
$request_file = 'dashboard.raw.xml';
$output_file = 'dashboard.rss.xml';
$img_size = 5; // 0-5 (0 is original or large, 5 is small)
//$title_format = '[%1$s] %4$s (%2$s) - %3$s'; // [type] longname (shortname) - entry
$title_format = '%3$s (%2$s) - %4$s [%1$s]'; // longname (shortname) - entry [type]

/** read config ... if available */
if ( file_exists('config.ini') ) {
	$config = parse_ini_file('config.ini', TRUE);
	extract($config);
}
// set GMT/UTC required for proper cache dates & feed validation
date_default_timezone_set('GMT');

// and away we go ...
if ($cache_request && check_cache() )
{
		$result = file_get_contents($cache_dir . DIRECTORY_SEPARATOR . $request_file);
		$posts = read_xml($result);
		output_rss($posts);
}
else
{
	fetch_tumblr_dashboard_xml($tumblr['email'], $tumblr['password']);
}


/** Functions
 ------------------------------------- */

/**
 * Tumbler Dashboard API Read
 *
 * @param string $email tumblr account email address
 * @param string $password tumblr account password
 * @return void
 */
function fetch_tumblr_dashboard_xml($email, $password)
{
	// Prepare POST request
	$request_data = http_build_query(
	    array(
	        'email'     => $email,
	        'password'  => $password,
	        'generator' => 'tumblr Dashboard Feed 1.1',
	        'num' => '50'
	    )
	);

	// Send the POST request (with cURL)
	$ch = curl_init('http://www.tumblr.com/api/dashboard');
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_USERAGENT, 'tumblr-dashboard-rss 1.1');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	// Check for success
	if ($status == 200) {
		// echo "Success! The output is $result.\n";
		// do process/output
		$posts = read_xml($result);
		output_rss($posts);
		// cache xml file ... check last mod & serve static
		cache_xml($result);
	} else if ($status == 403) {
		echo 'Bad email or password';
	} else if ($status == 503) {
		echo 'Rate Limit Exceded or Service Down';
	} else {
		echo "Error: $result\n";
	}
}

/**
 * write xml to local file
 * @param type $result
 * @todo chose a cache method - raw or formatted
 */
function cache_xml($result)
{
	if (is_writable('./cache'))
	{
		$fp = fopen('./cache/dashboard.raw.xml', 'w');
		fwrite($fp, $result);
		fclose($fp);
	}
	else
	{
		error_log('ERROR: xml cache not writeable');
		return FALSE;
	}
	//TMP
	// import to simple xml to clean up
	$sxml = new SimpleXMLElement($result);
	$dom = new DOMDocument('1.0');
	// convert to dom
	$dom_sxe = dom_import_simplexml($sxml);
	$dom_sxe = $dom->importNode($dom_sxe, TRUE);
	$dom_sxe = $dom->appendChild($dom_sxe);
	// format & output
	$dom->formatOutput = TRUE;
	//echo $dom->saveXML();
	$dom->save('./cache/dashboard.clean.xml');

}

/**
 * check if the cache is fresh
 * @param type $filename cache file path
 * @param type $ttl time in seconds
 * @return type bool true if file is fresh false if stale
 */
function check_cache($filename='./cache/dashboard.raw.xml', $ttl=300)
{
	// check cache ...
	if (file_exists($filename)  && filemtime($filename) >  time() - $ttl )
	{
		$GLOBALS['_DEBUG'] && error_log('[DEBUG] CACHE FOUND! AND NOT EXPIRED! :)');
		return TRUE;
	}
	else
	{
		return FALSE;
	}

}

/**
 * parse tumblr dashboard xml
 *
 * @param string $result curl result string
 * @return array $posts array of posts for rss
 */
function read_xml($result)
{
	$format = $GLOBALS['title_format'];
	$img_size = $GLOBALS['img_size'];

	// $xml = simplexml_load_string($result);
	$xml = new SimpleXMLElement($result);
//	print_r($xml);die;

	// create simple array
	$posts = array();
	foreach ($xml->posts->post as $post) {
		$item = array();

//		var_dump($post);die;
		$item['title'] = str_replace('-', ' ', $post['slug']); // default
		$item['link'] = $post['url-with-slug'];
		$item['date'] = date(DATE_RSS, strtotime($post['date']) );
		$item['description'] = $post['type'];

		// handle types [Text, Photo, Quote, Link, Chat, Audio, Video]
		switch($post['type'])
		{
			case 'regular':
				$item['title'] = sprintf($format, $post['type'], $post['tumblelog'], $post->tumblelog['title'], $post->{'regular-title'} ) ;
				$item['description'] = $post->{'regular-body'};
			break;

			case 'photo':
				$item['title'] = sprintf($format, $post['type'], $post['tumblelog'], $post->tumblelog['title'], $post['slug']) ;
				$item['description'] = sprintf('<img src="%s"/> %s', $post->{'photo-url'}[$img_size], $post->{'photo-caption'} );
				$item['enclosure']['url'] = (string)$post->{'photo-url'}[0];
				$item['enclosure']['type'] = 'image/jpg'; // FIXME: best guess ... whish i knew without checking extensions
//				var_dump($post);die;
			break;

			case 'quote':
				$item['title'] = sprintf($format, $post['type'], $post['tumblelog'], $post->tumblelog['title'], $post->source) ;
				$item['description'] = $post->quote;
			break;

			case 'answer':
				$item['title'] = sprintf($format, $post['type'], $post['tumblelog'], $post->tumblelog['title'], $post['slgu']) ;
				$item['description'] = $post->answer;
			break;

			case 'link':
				$item['title'] = sprintf($format, $post['type'], $post['tumblelog'], $post->tumblelog['title'], $post->link) ;
				$item['description'] = $post->link;
			break;

			case 'conversation':
				$item['title'] = sprintf($format, $post['type'], $post['tumblelog'], $post->tumblelog['title'], $post->{'conversation-title'} ) ;
				$item['description'] = $post->{'conversation'};
			break;

			case 'audio':
				$item['title'] = sprintf($format, $post['type'], $post['tumblelog'], $post->tumblelog['title'], strip_tags($post->{'audio-caption'} ) ) ;
				$item['description'] = $post->{'audio-player'} . $post->{'audio-caption'};
				$item['enclosure']['url'] = $post->{'audio-download'};
				$item['enclosure']['type'] = 'audio/mp3'; // FIXME: best guess without looking at extension
			break;

			case 'video':
				$item['title'] = sprintf($format, $post['type'], $post['tumblelog'], $post->tumblelog['title'], $post['slug']) ;
				$item['description'] = 'Video ... embed goes here ... ';
				$item['enclosure']['url'] = $post->{'video-download'};
				$item['enclosure']['type'] = 'video/mp4'; // FIXME: best guess without looking at extension
			break;

			default:
				$item['description'] = 'unknown post type: ' . $post['type'];
			break;
		}
		// append to array
		$posts[] = $item;
	}
//	var_dump($posts);die;
	return $posts;
}

/**
 * generate rss feed output
 *
 * @param array $items post item array
 * @return void
 */
function output_rss ($posts, $cache=false, $file=NULL)
{
	if (!is_array($posts)) die('no posts ...');
	$lastmod = strtotime($posts[0]['date']);

	// http headers
	header('Content-type: text/xml'); // set mime ... application/rss+xml
	header('Cache-Control: max-age=300, must-revalidate'); // cache control 5 mins
	header('Last-Modified: ' . gmdate('D, j M Y H:i:s T', $lastmod) ); //D, j M Y H:i:s T
	header('Expires: ' . gmdate('D, j M Y H:i:s T', time() + 300));

	// conditional get ...
	$ifmod = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] === gmdate('D, j M Y H:i:s T', $lastmod) : FALSE;
	if ( FALSE !== $ifmod ) {
		$GLOBALS['_DEBUG'] && error_log('[DEBUG] 304 NOT MODIFIED :)');
		header('HTTP/1.0 304 Not Modified');
		exit;
	}

	// build rss using dom
	$dom = new DomDocument();
	$dom->formatOutput = TRUE;
	$dom->encoding = 'utf-8';

	$rss = $dom->createElement('rss');
	$rss->setAttribute('version', '2.0');
	$rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom'); // atom for self
	$rss->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/'); //xmlns:media="http://search.yahoo.com/mrss/"
	$dom->appendChild($rss);

	$channel = $dom->createElement('channel');
	$rss->appendChild($channel);

	// set up feed properties
	$title = $dom->createElement('title', 'My Tumblr Dashboard Feed');
	$channel->appendChild($title);
	$link = $dom->createElement('link', 'http://tumblr.com/dashboard');
	$channel->appendChild($link);
	$description = $dom->createElement('description', 'My tumblr dashboard feed');
	$channel->appendChild($description);
	$language = $dom->createElement('language', 'en-us');
	$channel->appendChild($language);
	$pubDate = $dom->createElement('pubDate', $posts[0]['date'] );
	$channel->appendChild($pubDate);
	$lastBuild = $dom->createElement('lastBuildDate', date(DATE_RSS) );
	$channel->appendChild($lastBuild);
	$docs = $dom->createElement('docs', 'http://blogs.law.harvard.edu/tech/rss' );
	$channel->appendChild($docs);
	$generator = $dom->createElement('generator', 'Tumbler API' );
	$channel->appendChild($generator);
	$managingEditor = $dom->createElement('managingEditor', 'editor@example.com (editor)' );
	$channel->appendChild($managingEditor);
	$webMaster = $dom->createElement('webMaster', 'webmaster@example.com (webmaster)' );
	$channel->appendChild($webMaster);
	$self = $dom->createElement('atom:link');
	$self->setAttribute('href', 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
	$self->setAttribute('rel', 'self');
	$self->setAttribute('type', 'application/rss+xml');
	$channel->appendChild($self);

	// add items
	foreach( $posts as $post )
	{
		$item = $dom->createElement('item');

		$link = $dom->createElement('link', $post['link'] );
		// $link->appendChild( $dom->createTextNode( $item['link'] ) );
		$item->appendChild( $link );
		$title = $dom->createElement('title', $post['title'] );
		$item->appendChild( $title );
		$description = $dom->createElement('description' );
		// put description in cdata to avoid breakage
		$cdata = $dom->createCDATASection($post['description']);
		$description->appendChild($cdata);
		$item->appendChild( $description );
		$pubDate = $dom->createElement('pubDate', $post['date'] );
		$item->appendChild( $pubDate );
		$guid = $dom->createElement('guid', $post['link'] );
		$item->appendChild( $guid );

		// if enclosure ...
		if ( isset($post['enclosure']) )
		{
			//<enclosure url="http://www.webmonkey.com/monkeyrock.mpg" length="2471632" type="video/mpeg"/>
			$enclosure = $dom->createElement('enclosure');
			$enclosure->setAttribute('url', $post['enclosure']['url']);
			$enclosure->setAttribute('length', '1024'); // this is required but doesn't need to be accurate
			$enclosure->setAttribute('type', $post['enclosure']['type']); // valid mime type
			$item->appendChild($enclosure);

			// media rss
		}

		$channel->appendChild( $item );
	  }

	  // cache output
	  if ($GLOBALS['cache_output'] === TRUE && is_writable($GLOBALS['cache_dir']))
		  $dom->save($GLOBALS['cache_dir'] . DIRECTORY_SEPARATOR . $GLOBALS['output_file']);
	  // send output to browser
	  echo $dom->saveXML();

}

/*EOF*/
