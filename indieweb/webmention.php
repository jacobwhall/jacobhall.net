<?php

require __DIR__ . '/vendor/autoload.php';
include_once './postinsertion.php';

$commentInserted = False;

if (!isset($_POST['source']) || !isset($_POST['target'])) {
	header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
	echo "Hi there, to send me a webmention you'll have to post a source and target url to your request. \nif that makes no sense, shoot me an email at email@jacobhall.net and i'd be happy to explain it to you :) \ni hope you have a great day!\n";
  exit;
} elseif (parse_url($_POST['target'])['host'] != "jacobhall.net") {
	header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
	echo "It doesn't look like your target URL is to my website, jacobhall.net!\n";
}


function insertComment($source, $target, $whostyle = Null, $item = Null) {
	$item = json_encode($item);
	global $commentInserted;
	$commentInserted = True;
	// Log this webmention request
	$conn = connectToDB();
	$logquery = "INSERT INTO wm_log (source, target, whostyle, source_mf) VALUES (:source, :target, :whostyle, :source_mf)";
	$lqp = $conn->prepare($logquery);
	$lqp->bindParam(":source", $source);
	$lqp->bindParam(":target", $target);
	$lqp->bindParam(":whostyle", $whostyle);
	$lqp->bindParam(":source_mf", $item);
	if (!$lqp->execute($logData)) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
		echo "Request logging unsuccessful! Please contact the webmaster.";
		echo "\n";
		$arr = $sth->errorInfo();
		print_r($arr);
		exit;
	}
}

function processHCard($url) {
	global $contactsToSave;
	$cleanURL = trim($url, "/");
	if (!in_array($cleanURL, $contactsToSave)) {
		// Add $cleanURL to $contactsToSave array
		$contactsToSave[] = $cleanURL;
	}
}


function sniffForHCards($propertyarray) {
	if(isset($propertyarray['author'])) {
		foreach ($propertyarray['author'] as $author) {
			processHCard($author['properties']['url'][0]);
		}
	} elseif (isset($propertyarray['url'])) {
		processHCard($propertyarray['url'][0]);
	}
}

$isReply = False;
// Get microformats array of the source
$sourcemf = mf2\fetch($_POST['source']);
foreach ($sourcemf[items] as $item) {
	// if entry is an h-card
	if ($item[type][0] == "h-card") {
		sniffForHCards($item['properties']);
	// elseif entry is an h-entry and is a reply
	} elseif ($item['type'][0] == "h-entry" && isset($item['properties']['in-reply-to'])) {
		// Is this reply the one we're looking for?
		if ($item['properties']['url'][0] == $_POST['source']) {
			// For every post this reply might be replying to
			foreach ($item['properties']['in-reply-to'] as $replyitem) {
				// If this post replies to the target (my post)
				if ($replyitem['properties']['url'][0] == $_POST['target']) {
					$isReply = True;
				}
				// Sniff for h-cards to add to my contacts
				sniffForHCards($replyitem['properties']);
			}
			if ($isReply) {
				// TODO: support whostyle sniffing
				insertComment($_POST["source"], $_POST["target"], Null, $item);
			}
		}
	} else {
		// This h-entry is not a reply
		// Let's sniff it anyways lol
		if (isset($item['properties'])) {
			sniffForHCards($item['properties']);
		}
	}
}

// Is the target URL even in the source?
if ($commentInserted) {
	header($_SERVER['SERVER_PROTOCOL'] . ' 202 Accepted');
	
	echo "Successfully received and validated your webmention!\nJust a heads up that I am moderating these manually, so if you want your comment to appear quickly do be in touch!";
	// print_r($contactsToSave);
} else {
	insertComment($_POST["source"], $_POST["target"]);
	header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
	echo "Thanks for the webmention!\nMy system reports that your post might not link to mine, though I will check that manually.\n";
}

?>
