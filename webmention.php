<?php

# Licensed under a CC0 1.0 Universal (CC0 1.0) Public Domain Dedication
# http://creativecommons.org/publicdomain/zero/1.0/

if (!isset($_POST['source']) || !isset($_POST['target'])) {
  header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
	echo "hi there, to send me a webmention you'll have to post a source and target url to your request.\n
		if that makes no sense, shoot me an email at email@jacobhall.net and i'd be happy to explain it to you :)\n
		i hope you have a great day!";
  exit;
}

ob_start();
$ch = curl_init($_POST['source']);
curl_setopt($ch,CURLOPT_USERAGENT,'mydomain (webmention.org)');
curl_setopt($ch,CURLOPT_HEADER,0);
$ok = curl_exec($ch);
curl_close($ch);
$source = ob_get_contents();
ob_end_clean();
	
header($_SERVER['SERVER_PROTOCOL'] . ' 202 Accepted');

if (stristr($source, $_POST['target'])) {
	echo "ATTN: sorry, the webmention you sent appears valid BUT i'm not quite ready to receive it. could you send it again soon? thank you";
	/*
	echo "thanks for the webmention! i'll attend to it shortly.\n";
	$file_location = "/home/jhall/webmentions/" . uniqid();
	file_put_contents($file_location, $source);
	echo "saving file to " . $file_location . "\n";
	*/
} else {
	echo "thanks, but the source does not seem to refer to the target! rejecting your webmention :(\n";
}

?>
