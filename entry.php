<?php
include $_SERVER['DOCUMENT_ROOT'] . '/beforeentry.html';
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
//	echo "Error:".$e->getMessage();
	die("Error connecting to database!\n</body>\n</html>");
}
$querystring = "SELECT post_type,
			post_title,
			content_location,
			to_char(published_date at time zone 'UTC', 'YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"') as published_date,
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
$querystring = $querystring . " ORDER BY published_date DESC";
$sth = $conn->prepare($querystring);
$sth->execute();
$result = $sth->fetchAll();

	// For each returned row from query
	foreach($result as $row) {
		// Very important, reset the content for this entry!!
		unset($content);

		echo "<article class=\"h-entry\">\n";

		if ($row['post_type'] == 1) {
			echo "\t<a href=\"/kind/article\" class=\"kind\">🖋️ ARTICLE</a>\n";
			if (isset($row['post_title']))
				echo "\t<h2 class=\"p-name\">\n\t\t<a href=\"" . $row['permalink'] . "\">" . $row['post_title'] . "</a>\n\t</h2>\n";
			$content = $row['content_summary'] . " <a href=\"" . $row["permalink"] . "\">Read article &gt;&gt;</a>\n";
		}
		if ($row['post_type'] == 2) {
			echo "\t<a href=\"/kind/note\" class=\"kind\">📝 NOTE</a><br><br>\n";
			if (isset($row['post_title']))
				echo "<h2 class=\"p-name\">" . $row['post_title'] . "</h2>";
		}
		// TODO: Add plural if there are multiple photos
		if ($row['post_type'] == 3) echo "📷 PHOTO ";
			// Photos and videos will probably be under any caption/note/other content
		if ($row['post_type'] == 4) echo "🎥 VIDEO ";
		if ($row['post_type'] == 5) {
			echo "<a href=\"/kind/bookmark\" class=\"kind\">🔗 BOOKMARK</a>\n";
			$content = "\t\t<h2 class=\"p-name\">\n\t\t\t<a class=\"u-bookmark-of\" href=\"" . $row['content'] . "\">" . $row['post_title'] . "</a>\n\t\t</h2>";
		}
		if ($row['post_type'] == 6) echo "❤️ LIKE \n";
		// TODO: properly link to original author's h-card and the original post
		if ($row['post_type'] == 7) echo "↩️ REPLY \n";
		// TODO: properly link to original author's h-card and the original post
		if ($row['post_type'] == 8) echo "🔄 REPOST of \n";
		// TODO: properly link to the event h-entry
		if ($row['post_type'] == 9) echo "✉️ RSVP \n";
		// TODO: Add more post types. 🎧 jam, 📺 watch,📖 read, presentation? 📅 event?

		// Output content of post!
		echo "\t<span class=\"e-content\">\n";
		if (isset($content)) {
			echo $content;
		} elseif (isset($row['content_summary'])) {
			echo "\t\t<p class=\"p-summary\">\n" . $row['content_summary'] . "\n\t\t</p>";
		} elseif (isset($row['content_location'])) {
			include ($_SERVER['DOCUMENT_ROOT'] . $row['content_location']);
		} else {
			echo $row['content'];
		}
		echo "\n\t</span>\n";
		echo "\t<p class=\"entry-data\">\n";
		// Ok now for the cute lil bottom text, with location/timestamps/such
		if ($row['display_location'] == 1) echo "\t\t📍 " . $row['location'] . "<br>\n";

		echo "\t\tPosted <time class=\"dt-published\" datetime=\"" . $row['published_date'] . "\"><a class=\"u-url\" href=\"" . $row['permalink'] . "\">" . date('F j, Y \a\t H:i', strtotime($row['published_date'])) . "</a></time>";
		if (isset($row['updated_date'])) {
			echo ", updated <date class=\"dt-updated\" datetime=\"" . $row['updated_date'] . "\">" . date('F j, Y \a\t H:i', strtotime($row['updated_date'])) . "</date>";
		}
		echo " by <img class=\"u-photo hidden-u-photo\" src=\"https://jacobhall.net/images/toothbrush_profile_small.jpg\" /><a class=\"p-author h-card\" href=\"https://jacobhall.net\">Jacob Hall</a>\n\t</p>\n</article>\n";
	}
echo "</div>\n</body>\n</html>";
?>
