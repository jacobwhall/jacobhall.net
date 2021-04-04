<?php
include $_SERVER['DOCUMENT_ROOT'] . '/beforeentry.html';
$querystring = "SELECT post_type,
			post_id,
			post_title,
			bookmark_of,
			content_location,
			to_char(published_date, 'YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"') as published_date,
			updated_date,
			permalink,
			location,
			display_location,
			author_h_card,
			author_photo,
			original_url,
			reply_to_author,
			reply_to_id,
			reply_to_author_h_card,
			reply_to_author_photo,
			reply_to_title,
			reply_to_content,
			reply_to_url,
			content,
			content_summary from entries";
$needsand = false;
if (isset($_GET['id'])) {
	if (is_numeric($_GET["id"])) {
		if ($_GET["id"] >= 0 && $_GET["id"] <= 999999) {
			$querystring = $querystring . " where post_id=" . $_GET["id"];
			$needsand = true;
		}
	}
}
if (isset($_GET['type'])) {
	if (is_numeric($_GET["type"])) {
		if ($_GET["type"] > 0) {
			if ($needsand) {
				$querystring = $querystring . " and";
			} else {
				$querystring = $querystring . " where";
			}
			$querystring = $querystring . " post_type=" . $_GET["type"];
		}
	}
}
include 'feed.php';
echo "</div>\n</body>\n</html>";
?>
