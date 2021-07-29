<?php
# From https://github.com/skpy/micropub/blob/master/inc/content.php
# this takes an MF2 array of arrays and converts single-element arrays
# into non-arrays.
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
function insertPost($table, $insertionData, $tags) {

	$insertionDataTypes = [
		"table" => $table,
		"posttype",
		"posttitle",
		"content",
		"published",
		"bookmarkof"
	];

	foreach ($insertionDataTypes as $dataType) {
		if(!isset($insertionData[$dataType]))
			$insertionData[$dataType] = Null;
	}

	$conn = connectToDB();

	$querystring = "INSERT INTO :table (post_type, post_title, published, content, bookmark_of) VALUES " .
			"(:posttype, :posttitle, :published, :content, :bookmarkof) RETURNING permalink, post_id";

	$sth = $conn->prepare($querystring);
	if ($sth->execute($insertionData)) {
		$result = $sth->fetch(PDO::FETCH_ASSOC);
	
		if (count($tags) > 0) {
			foreach ($tags as $tag) {
				$tagData = [
					"postid" => $result['post_id'],
					"tag" => $tag
				];
				$tagquery = "INSERT INTO tags (post_id, tag) VALUES (:postid, :tag)";
				$taginsertion = $conn->prepare($tagquery);
				if (!$taginsertion->execute($tagData)) {
					header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
					echo "There was an error inserting a tag into the tags table. This shouldn't happen :-/";
					exit;
				}
			}
		}

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

function setPublishedPost($postURL, $published) {
	$conn = connectToDB();
	
	if ($published) {
		$published = "true";
	} else {
		$published = "false";
	}

	$querystring = "UPDATE entries SET published = " . $published . " WHERE permalink = '" . $postURL . "'";

	$sth = $conn->prepare($querystring);
	if ($sth->execute()) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
	}
}

?>
