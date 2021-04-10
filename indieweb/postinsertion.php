<?php
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
    return $props;
}
function connectToDB() {

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
		return $conn;
	} catch (PDOException $e) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
		echo "Error connecting to database!";
		exit;
	}

}
function insertPost($insertionData) {
	$querystring = "INSERT INTO entries (post_type, post_title, published, content, bookmark_of) VALUES (:posttype, :posttitle, :published, :content, :bookmarkof) RETURNING permalink";

	$sth = connectToDB()->prepare($querystring);
	if ($sth->execute($insertionData)) {
		$result = $sth->fetch(PDO::FETCH_ASSOC);

		// Report back the success!
		header($_SERVER['SERVER_PROTOCOL'] . ' 201 Created');
		header('Location: ' . $result['permalink']);

	} else {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
		echo "Database insertion unsuccessful! There might be a permission issue.";
		 echo "\n";
		 $arr = $sth->errorInfo();
		 print_r($arr);
		exit;
	}
}

// Much of this code borrowed from https://github.com/skpy/micropub/blob/master/inc/content.php
// this probably belongs in the micropub file tbh
function create($request, $photos = []) {

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
		"posttype" => $properties['posttype'],
		"posttitle" => NULL,
		"content" => $content,
		"published" => $properties['published'],
		"bookmarkof" => NULL,
	];

	if (isset($properties["bookmark-of"])) {
		$insertionData["bookmarkof"] = $properties["bookmark-of"];
	}
	if (isset($properties["name"])) {
		$insertionData["posttitle"] = $properties["name"];
	}
	
	insertPost($insertionData);
}
?>
