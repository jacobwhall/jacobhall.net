<?php

# Licensed under a CC0 1.0 Universal (CC0 1.0) Public Domain Dedication
# http://creativecommons.org/publicdomain/zero/1.0/

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
if (!isset($_POST['h'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    echo 'Missing "h" value.';
    exit;
}
$options = array(
	CURLOPT_URL => $token_endpoint,
	CURLOPT_HTTPGET => TRUE,
	CURLOPT_USERAGENT => $mysite,
	CURLOPT_TIMEOUT => 5,
	CURLOPT_RETURNTRANSFER => TRUE,
	CURLOPT_HEADER => FALSE,
	CURLOPT_HTTPHEADER => array(
		'Content-type: application/x-www-form-urlencoded'
		'Authorization: '.$_HEADERS['Authorization']
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
if (!isset($_POST['content'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    echo 'Missing "content" value.';
    exit;
}

/* Everything's cool. Do something with the $_POST variables
   (such as $_POST['content'], $_POST['category'], $_POST['location'], etc.)
   e.g. create a new entry, store it in a database, whatever. */

// Connect to database
include '../jhsite.config.php';
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

$insertionData = [
	"content" => $_POST['content'],
];

$querystring = "INSERT INTO entries (post_type, content) VALUES (2, :content) RETURNING permalink";

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
?>
