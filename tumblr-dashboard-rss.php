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
 * @version 1.1.0 $Id:$
 * @todo make it more secure, multi-user friendly, compression
 */

//* debug
ini_set('display_errors', TRUE) ;
error_reporting (E_ALL | E_STRICT) ;
//*/

/** Authorization info */
$config['tumblr']['email']    = 'email@example.com';
$config['tumblr']['password'] = 'password';

/** other settings*/
$config['DEBUG'] = FALSE;
$config['cache']['request'] = TRUE;
$config['cache']['output'] = TRUE;
$config['cache']['ttl'] = '300'; // 5m in seconds
$config['cache']['dir'] = './cache'; // make sure this is writeable for www server
$config['cache']['request_file'] = 'dashboard.raw.xml';
$config['cache']['output_file'] = 'dashboard.rss.xml';
$config['feed']['img_size'] = 5; // 0-5 (0 is original or large, 5 is small)
//$config['feed']['post_format'] = '[%1$s] %4$s (%2$s) - %3$s'; // [type] longname (shortname) - entry
$config['feed']['post_format'] = '%3$s (%2$s) - %4$s [%1$s]'; // longname (shortname) - entry [type]

/** read config ... if available */
if ( file_exists('config.ini') ) {
	$config = parse_ini_file('config.ini', TRUE);
}
<<<<<<< HEAD

// default to GMT for dates
date_default_timezone_set('America/New_York');
=======
// set GMT/UTC required for proper cache dates & feed validation
date_default_timezone_set('GMT');
>>>>>>> f284b97a3c2ede101e02a65c4b00a10276e6d09a

// and away we go ...
if ($config['cache']['request'] && check_cache() )
{
		$result = file_get_contents($config['cache']['dir'] . DIRECTORY_SEPARATOR . $config['cache']['request_file']);
		$posts = read_xml($result);
		output_rss($posts);
}
else
{
	fetch_tumblr_dashboard_xml($config['tumblr']['email'], $config['tumblr']['password']);
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
	global $config;
	$config['DEBUG'] && error_log('[DEBUG] REQUESTING API READ!');
	// Prepare POST request
	$request_data = http_build_query(
	    array(
	        'email'     => $email,
	        'password'  => $password,
<<<<<<< HEAD
	        'generator' => 'tumblr Dashboard Feed 1.0',
	        'num' => '10'
=======
	        'generator' => 'tumblr Dashboard Feed 1.1',
	        'num' => '50'
>>>>>>> f284b97a3c2ede101e02a65c4b00a10276e6d09a
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
<<<<<<< HEAD
		// echo "Success!";// The output is $result.\n";
=======
		// echo "Success! The output is $result.\n";
		// do process/output
>>>>>>> f284b97a3c2ede101e02a65c4b00a10276e6d09a
		$posts = read_xml($result);
		output_rss($posts);
		// cache xml file 
		$config['cache']['request'] && cache_xml($result);
	} else if ($status == 403) {
		echo "Bad email or password\n";
		echo $result;
		echo "\n";
		$handle = fopen('authfail.txt','w');
		fwrite( $handle, var_export( $request_data, true ) );
		fclose($handle);
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
	global $config;
	if ( is_writable($config['cache']['dir']) )
	{
		$fp = fopen($config['cache']['dir'] . DIRECTORY_SEPARATOR . $config['cache']['request_file'], 'w');
		fwrite($fp, $result);
		fclose($fp);
	}
	else
	{
		error_log('ERROR: xml cache not writeable');
		return FALSE;
	}

}

/**
 * check if the cache is fresh
 * @param type $filename cache file path
 * @param type $ttl time in seconds
 * @return type bool true if file is fresh false if stale
 */
function check_cache()
{
	global $config;
	$filename = $config['cache']['dir'] . DIRECTORY_SEPARATOR . $config['cache']['request_file'];
	$ttl = (int) $config['cache']['ttl'];

//	var_dump($filename, $ttl, filemtime($filename), time(), time() - $ttl);die;

	if (file_exists($filename)  && filemtime($filename) + $ttl >  time() )
	{
		$config['DEBUG'] && error_log('[DEBUG] CACHE FOUND! AND NOT EXPIRED! :)');
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
<<<<<<< HEAD
	$data_output = fopen("data.xml",'w');
	fwrite($data_output,$result);
	fclose($data_output);
	
	// fix quality="best">
	$result = str_replace("quality=\"best\">","quality=\"best\"&gt;",$result);
	
	// fix </embed>
	$result = str_replace("</embed>","&lt;embed/&gt;",$result);

	$xml = simplexml_load_string($result);
	
=======
	global $config;
	$format = $config['feed']['post_format'];
	$img_size = (int)$config['feed']['img_size'];

	// $xml = simplexml_load_string($result);
>>>>>>> f284b97a3c2ede101e02a65c4b00a10276e6d09a
	$xml = new SimpleXMLElement($result);
//	print_r($xml);die;

	// create simple array
	$posts = array();
	foreach ($xml->posts->post as $post) {
<<<<<<< HEAD
		
		$log = $post->{'tumblelog'};
		$auth = (string)$log['title'];
		$log = strtoupper((string)$log['name']).' ('.(string)$log['title'].')';
		$posts[$i]['title'] = $post['slug'].' '.$log.' ['.$post['type'].']'; // wish there was a real title
		$posts[$i]['description'] = $post['type']; // maybe do somehting intelligent with type
		$posts[$i]['author'] = $auth;
		
		switch($post['type']) {
			case 'photo':
				// Pick the first photo in the set.
				$photo_links = $post->{'photo-url'};
				$posts[$i]['data'] = $photo_links[0];
				$posts[$i]['quote'] = $post->{'photo-caption'};
				break;
			case 'regular':
				$posts[$i]['data'] = $post->{'regular-title'};
				$posts[$i]['quote'] = $post->{'regular-body'};
				break;
			case 'answer':
				$posts[$i]['data'] = $post->{'question'};
				$posts[$i]['quote'] = $post->{'answer'};
				break;
			case 'video':
				$posts[$i]['data'] = $post->{'video-player'};
				$posts[$i]['quote'] = $post->{'video-caption'};
				break;
			case 'quote':
				$posts[$i]['quote'] = $post->{'quote'};
				break;
			case 'audio':
				//$posts[$i]['data'] = $post->{'audio-player'};
				$posts[$i]['quote'] = $post->{'audio-caption'};
				break;
			case 'conversation':
				$posts[$i]['data'] = $post->{'conversation-title'};
				
				$lines = array();
				foreach($post->{'conversation'}->{'line'} as $line){
					$lines[] = "&lt;dt&gt;".$line['label']."&lt;/dt&gt;&lt;dd&gt;".(string)$line."&lt;/dd&gt;";
				}
				
				if( is_array($lines) ){
					$convo = "&lt;dl&gt;".implode("\n",$lines)."&lt;/dl&gt;";
					$posts[$i]['quote'] = $convo;
				}
				break;
			default:
				//var_dump($post->asXML());
				//print "<!-- \n\n\t ".$post['type']."\n\n -->";
				//die();
				break;
		}
		
		$posts[$i]['link'] = $post['url-with-slug'];
		$posts[$i]['date'] = date(DATE_RSS, strtotime($post['date']) );
		
		$i++;
	}
=======
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
			break;

			case 'quote':
				$item['title'] = sprintf($format, $post['type'], $post['tumblelog'], $post->tumblelog['title'], $post['slug']) ;
				$item['description'] = $post->{'quote-text'};
				// var_dump($post);die;
			break;

			case 'answer':
				$item['title'] = sprintf($format, $post['type'], $post['tumblelog'], $post->tumblelog['title'], $post['slug']) ;
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
				$item['description'] = $post->{'video-player'}[0] . $post->{'video-caption'};
				if ( preg_match('/https?:\/\/[^"]+/i', $post->{'video-source'}, $matches) )
				{
					$item['enclosure']['url'] = $matches[0]; // must be full url
					$item['enclosure']['type'] = 'video/mp4'; // FIXME: best guess without looking at extension
				}
			break;

			default:
				$item['description'] = 'unknown post type: ' . $post['type'];
			break;
		}
		// append to array
		$posts[] = $item;
	}
//	var_dump($posts);die;
>>>>>>> f284b97a3c2ede101e02a65c4b00a10276e6d09a
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
	global $config;
	if (!is_array($posts)) die('no posts ...');
	$lastmod = strtotime($posts[0]['date']);

	// http headers
	header('Content-type: application/xml; charset=utf-8'); // set mime ... application/rss+xml
	header('Cache-Control: max-age=300, must-revalidate'); // cache control 5 mins
	header('Last-Modified: ' . gmdate('D, j M Y H:i:s T', $lastmod) ); //D, j M Y H:i:s T
<<<<<<< HEAD
	header('Expires: ' . gmdate('D, j M Y H:i:s T', time()));
	
	// conditional get ... 
	
	$ifmod = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] === gmdate('D, j M Y H:i:s T', $lastmod) : false; 
	if ( false !== $ifmod ) {
		header('HTTP/1.0 304 Not Modified'); 
		exit; 
=======
	header('Expires: ' . gmdate('D, j M Y H:i:s T', time() + 300));

	// conditional get ...
	$ifmod = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] === gmdate('D, j M Y H:i:s T', $lastmod) : FALSE;
	if ( FALSE !== $ifmod ) {
		$config['DEBUG'] && error_log('[DEBUG] 304 NOT MODIFIED :)');
		header('HTTP/1.0 304 Not Modified');
		exit;
>>>>>>> f284b97a3c2ede101e02a65c4b00a10276e6d09a
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
	$self->setAttribute('href', 'http://tumblr.com/dashboard');
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
<<<<<<< HEAD
		$author = $dom->createElement("author",$post['author']);
		$item->appendChild( $author );
		
		switch($post['description']){
			case 'photo':
				$description = $dom->createElement( "description", "&lt;img src=\"".$post['data']."\" /&gt;"."&lt;br /&gt;&lt;br /&gt;".$post['quote']."&lt;br /&gt;" );
				break;
			case 'regular':
				$description = $dom->createElement( "description");
				$cdata = $dom->createCDATASection($post['data']."<br />".$post['quote']."<br /><br />" );
				$description->appendChild($cdata);
				break;
			case 'answer':
				$description = $dom->createElement( "description", "&lt;strong&gt;".$post['data']."&lt;/strong&gt;&lt;br /&gt;&lt;blockquote&gt;".$post['quote']."&lt;/blockquote&gt;&lt;br /&gt;&lt;br /&gt;&lt;br /&gt;");
				break;
			case 'video':
				$description = $dom->createElement( "description", $post['data']."&lt;br /&gt;".$post['quote']."&lt;br /&gt;&lt;br /&gt;" );
				break;
			case 'audio':
				$description = $dom->createElement( "description", "&lt;br /&gt;".$post['quote']."&lt;br /&gt;&lt;br /&gt;" );
				break;
			case 'quote':
				$description = $dom->createElement( "description", "&lt;blockquote&gt;".$post['quote']."&lt;/blockquote;&gt;&lt;br /&gt;&lt;br /&gt;&lt;br /&gt;");
				break;
			case 'conversation':
				$description = $dom->createElement( "description", "&lt;strong&gt;".$post['data']."&lt;/strong&gt;&lt;br /&gt;".$post['quote']."&lt;br /&gt;&lt;br /&gt;&lt;br /&gt;");
				break;
			default:
				$description = $dom->createElement( "description", $post['description'] );
				break;
		}
		
		$item->appendChild( $description );

		$pubDate = $dom->createElement( "pubDate", $post['date'] );
=======
		$description = $dom->createElement('description' );
		// put description in cdata to avoid breakage
		$cdata = $dom->createCDATASection($post['description']);
		$description->appendChild($cdata);
		$item->appendChild( $description );
		$pubDate = $dom->createElement('pubDate', $post['date'] );
>>>>>>> f284b97a3c2ede101e02a65c4b00a10276e6d09a
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

			// media rss?
		}

		$channel->appendChild( $item );
	  }

	  // cache output
	  if ($config['cache']['output'] == TRUE && is_writable($config['cache']['dir']))
		  $dom->save($config['cache']['dir'] . DIRECTORY_SEPARATOR . $config['cache']['output_file']);
	  // send output to browser
	  echo $dom->saveXML();

}

/*EOF*/
