<?php

# Licensed under a CC0 1.0 Universal (CC0 1.0) Public Domain Dedication
# http://creativecommons.org/publicdomain/zero/1.0/

require_once 'vendor/autoload.php';

$mysite = 'https://jacobhall.net/'; // Change this to your website.
$token_endpoint = 'https://tokens.indieauth.com/token';

$_HEADERS = array();
foreach(getallheaders() as $name => $value) {
    $_HEADERS[$name] = $value;
}

if (!isset($_HEADERS['Authorization'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
    echo 'Missing "Authorization" header.';
    exit;
}
/* 
if (!isset($_POST['h'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    echo 'Missing "h" value.';
    exit;
}
*/
$options = array(
	CURLOPT_URL => $token_endpoint,
	CURLOPT_HTTPGET => TRUE,
	CURLOPT_USERAGENT => $mysite,
	CURLOPT_TIMEOUT => 5,
	CURLOPT_RETURNTRANSFER => TRUE,
	CURLOPT_HEADER => FALSE,
	CURLOPT_HTTPHEADER => array(
		'Content-type: application/x-www-form-urlencoded',
		'Authorization: ' . $_HEADERS['Authorization']
	)
);

$curl = curl_init();
curl_setopt_array($curl, $options);
$source = curl_exec($curl);
curl_close($curl);

parse_str($source, $values);

if (!isset($values['me'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    echo 'Missing "me" value in authentication token.';
    exit;
}
if (!isset($values['scope'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    echo 'Missing "scope" value in authentication token.';
    exit;
}
if (substr($values['me'], -1) != '/') {
    $values['me'].= '/';
}
if (substr($mysite, -1) != '/') {
    $mysite.= '/';
}
if (strtolower($values['me']) != strtolower($mysite)) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
    echo 'Mismatching "me" value in authentication token.';
    exit;
}
if (!stristr($values['scope'], 'create')) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
    echo 'Missing "post" value in "scope".';
    exit;
}
/*
if (!isset($_POST['content'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    echo 'Missing "content" value.';
    exit;
}
*/
/* Everything's cool. Do something with the $_POST variables
   (such as $_POST['content'], $_POST['category'], $_POST['location'], etc.)
   e.g. create a new entry, store it in a database, whatever. */


if ( strtolower($_SERVER['CONTENT_TYPE']) == 'application/json' || strtolower($_SERVER['HTTP_CONTENT_TYPE']) == 'application/json' ) {
        $request = \p3k\Micropub\Request::createFromJSONObject(json_decode(file_get_contents('php://input'), true));
    } else {
        $request = \p3k\Micropub\Request::createFromPostArray($_POST);
    }
    if($request->error) {
        quit(400, $request->error_property, $request->error_description);
    }

function not_implemented($action) {
	header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
	die("Feature not yet implemented: ". $action);
}

# From https://github.com/skpy/micropub/blob/master/inc/content.php
# this takes an MF2 array of arrays and converts single-element arrays
# into non-arrays.
function normalize_properties($properties) {
    $props = [];
    foreach ($properties as $k => $v) {
        # we want the "photo" property to be an array, even if it's a
        # single element.  Our Hugo templates require this.
        if ($k == 'photo') {
            $props[$k] = $v;
        } elseif (is_array($v) && count($v) === 1) {
            $props[$k] = $v[0];
        } else {
            $props[$k] = $v;
        }
    }
    # MF2 defines "name" instead of title, but Hugo wants "title".
    # Only assign a title if the post has a name.
    if (isset($props['name'])) {
        $props['title'] = $props['name'];
    }
    return $props;
}

# From https://github.com/skpy/micropub/blob/master/inc/content.php
# this function accepts the properties of a post and
# tries to perform post type discovery according to
# https://indieweb.org/post-type-discovery
# returns the MF2 post type
function post_type_discovery($properties) {
    $vocab = array('rsvp',
                 'in-reply-to',
                 'repost-of',
                 'like-of',
                 'bookmark-of',
                 'photo');
    foreach ($vocab as $type) {
        if (isset($properties[$type])) {
            return $type;
        }
    }
    # articles have titles, which Micropub defines as "name"
    if (isset($properties['name'])) {
        return 'article';
    }
    # no other match?  Must be a note.
    return 'note';
}

function make_html($input) {
$output = trim($input);
$output = ("<p>\n" . $output . "\n</p>");
$output = preg_replace("/\n\n+/i", "\n</p>\n<p>\n", $output);
return $output;
}

// Much of this code borrowed from https://github.com/skpy/micropub/blob/master/inc/content.php
function create($request, $photos = []) {
	include '../../jhsite.config.php';
	// Connect to database
	try {
		$host=$config['DB_HOST'];
		$port=$config['DB_PORT'];
		$dbname=$config['DB_DATABASE'];
		$conn= new PDO("pgsql:host=$host;
			dbname=$dbname",
			$config['DB_USERNAME'],
			$config['DB_PASSWORD']);
	} catch (PDOException $e) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
		echo "Error connecting to database!";
		exit;
	}

	$mf2 = $request->toMf2();
	# make a more normal PHP array from the MF2 JSON array
	$properties = normalize_properties($mf2['properties']);

	# pull out just the content, so that $properties can be front matter
	# NOTE: content may be in ['content'] or ['content']['html'].
	# NOTE 2: there may be NO content!
	if (isset($properties['content'])) {
		if (is_array($properties['content']) && isset($properties['content']['html'])) {
			$content = $properties['content']['html'];
		} else {
			$content = make_html($properties['content']);
		}
	} else {
		$content = '';
	}
	# ensure that the properties array doesn't contain 'content'
	unset($properties['content']);

	if (isset($properties['bookmark-of'])) {
		not_implemented("bookmarks");
	}

	if (!empty($photos)) {
	# add uploaded photos to the front matter.
		if (!isset($properties['photo'])) {
			$properties['photo'] = $photos;
		} else {
			not_implemented("photo uploads");
			// $properties['photo'] = array_merge($properties['photo'], $photos);
		}
	}
	if (!empty($properties['photo'])) {
	//	$properties['thumbnail'] = preg_replace('#-' . $config['max_image_width'] . '\.#', '-200.', $properties['photo']);
	}

	# figure out what kind of post this is.
	$properties['posttype'] = post_type_discovery($properties);
	// echo ("post type is: ". $properties['posttype']);
	
	if (isset($properties['post-status'])) {
		if ($properties['post-status'] == 'draft') {
			$properties['published'] = false;
		} else {
			$properties['published'] = true;
		}
		unset($properties['post-status']);
	} else {
		# explicitly mark this item as published
		$properties['published'] = true;
	}
	// echo ("published: ". $properties['published']);
	$insertionData = [
		"content" => $content,
		"published" => $properties['published'],
	];
	$querystring = "INSERT INTO entries (post_type, published, content) VALUES (2, :published, :content) RETURNING permalink, content";
	$sth = $conn->prepare($querystring);

	if ($sth->execute($insertionData)) {
		$result = $sth->fetch(PDO::FETCH_ASSOC);

		// Report back the success!
		header($_SERVER['SERVER_PROTOCOL'] . ' 201 Created');
		header('Location: ' . $result['permalink']);

	} else {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
		echo "Database insertion unsuccessful! There might be a permission issue.";
		// echo "\n";
		// $arr = $sth->errorInfo();
		// print_r($arr);
		exit;
	}
}

// We'll be better about handling photos another day :)
$photo_urls = array();

switch($request->action):
	case 'delete':
		not_implemented($request->action);
		// delete($request);
		break;
	case 'undelete':
		not_implemented($request->action);
		// undelete($request);
		break;
	case 'update':
		not_implemented($request->action);
		// update($request, $photo_urls);
		break;
	default:
		//not_implemented($request->action);
		create($request, $photo_urls);
		break;
	endswitch;

?>
