<?php

require __DIR__ . '/vendor/autoload.php';
include_once './postinsertion.php';

if (!isset($_POST['source']) || !isset($_POST['target'])) {
	header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
	echo "hi there, to send me a webmention you'll have to post a source and target url to your request. \nif that makes no sense, shoot me an email at email@jacobhall.net and i'd be happy to explain it to you :) \ni hope you have a great day!\n";
  exit;
} elseif (parse_url($_POST['target'])['host'] != "jacobhall.net") {
	header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
	echo "it doesn't look like your target URL is to my website, jacobhall.net!\n";
}
/*
ob_start();
$ch = curl_init($_POST['source']);
curl_setopt($ch,CURLOPT_USERAGENT,'mydomain (webmention.org)');
curl_setopt($ch,CURLOPT_HEADER,0);
$ok = curl_exec($ch);
curl_close($ch);
$source = ob_get_contents();
ob_end_clean();
*/

function insertCommentProperties($propertyarray) {
	echo "comment property array passed!\n";
	
	// THIS FUNCTION IS TODO !!!!
	// THIS FUNCTION WILL CONVERT THE PASSED $propertyarray
	// into the $insertionData array insertPost() needs to
	// insert the post into the database!!
	
	// echo ("published: ". $properties['published']);
	$insertionData = [
		//"posttype" => $properties['posttype'],
		"posttitle" => NULL,
		//"content" => $content,
		//"published" => $properties['published'],
		"bookmarkof" => NULL,
	];

	//insertPost($insertionData);
}

function processHCard($url) {
	$cleanURL = trim($url, "/");
	echo "I'd like to process this h-card: " . $cleanURL . "\n\n";
}


function sniffForHCards($propertyarray) {
	if(isset($propertyarray['author'])) {
		foreach ($propertyarray['author'] as $author) {
			//echo "I'd also like to parse this h-card: " . $author['properties']['url'][0] . "\n";
			processHCard($author['properties']['url'][0]);
		}
	} elseif (isset($propertyarray['url'])) {
		processHCard($propertyarray['url'][0]);
	}
}

// Get microformats array of the source
$sourcemf = mf2\fetch($_POST['source']);
foreach ($sourcemf[items] as $item) {
	if ($item[type][0] == "h-card") {
		// This entry is an h-card
		sniffForHCards($item['properties']);
	} elseif ($item['type'][0] == "h-entry") {
		if (isset($item['properties']['in-reply-to'])) {
		// This h-entry is a reply
			if ($item['properties']['url'][0] == $_POST['source']) {
			// This reply is the one we're looking for!
				$replytothis = False;
				foreach ($item['properties']['in-reply-to'] as $replyitem) {
				// For every post this reply might be replying to
					if ($replyitem['properties']['url'][0] == $_POST['target']) {
					// Is this in-reply-to post the target (my post)?
						$replytothis = True;
						// It's true! This reply is to my post, the target.
					}
					sniffForHCards($replyitem['properties']);
					// Sniff for h-cards to add to my contacts in the other posts this reply is to
				}
				if ($replytothis) {
					insertCommentProperties($item['properties']);
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
}
//print_r($sourcemf);

// Is the target URL even in the source?
if (stristr($source, $_POST['target'])) {
//	$sourcemf = mf2\parse($source, $_POST['source']);
//	print_r($sourcemf);

	header($_SERVER['SERVER_PROTOCOL'] . ' 202 Accepted');
	
	echo "ATTN: sorry, the webmention you sent appears valid BUT i'm not quite ready to receive it. could you send it again soon? thank you\n";
	/*
	echo "thanks for the webmention! i'll attend to it shortly.\n";
	$file_location = "/home/jhall/webmentions/" . uniqid();
	file_put_contents($file_location, $source);
	echo "saving file to " . $file_location . "\n";
	*/
} else {
	header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
	echo "thanks, but the source does not seem to refer to the target! rejecting your webmention :(\n";
}

?>
