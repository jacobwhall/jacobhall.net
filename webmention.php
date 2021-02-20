<?php

# Licensed under a CC0 1.0 Universal (CC0 1.0) Public Domain Dedication
# http://creativecommons.org/publicdomain/zero/1.0/

if (!isset($_POST['source']) || !isset($_POST['target'])) {
  header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
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
	file_put_contents("webmentions.log","Hello World, testing!");
}

?>
