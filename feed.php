<?php
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
$querystring = $querystring . " ORDER BY published_date DESC";
if (isset($querylimit)) $querystring = $querystring . " LIMIT " . $querylimit;
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
			$content = "<p>" . $row['content_summary'] . " <a href=\"" . $row["permalink"] . "\">Read article &gt;&gt;</a></p>\n";
		}
		if ($row['post_type'] == 2) {
			echo "\t<a href=\"/kind/note\" class=\"kind\">📝 NOTE</a>\n";
			if (isset($row['post_title']))
				echo "<h2 class=\"p-name\">" . $row['post_title'] . "</h2>";
		}
		// TODO: Add plural if there are multiple photos
		if ($row['post_type'] == 3) echo "📷 PHOTO ";
			// echo post_title if there is one
			// echo content (or content_summary)
			// $content = <img> tags
		if ($row['post_type'] == 4) echo "🎥 VIDEO ";
			// echo post_title if there is one
			// echo content (or content_summary)
			// $content = embedded html video
		if ($row['post_type'] == 5) {
			echo "<a href=\"/kind/bookmark\" class=\"kind\">🔗 BOOKMARK</a>\n";
			echo "\t\t<h2 class=\"p-name\">\n";
				echo "\t\t\t<a class=\"u-bookmark-of\" href=\"" . $row['content'] . "\">" . $row['post_title'] . "</a>\n";
			echo "\t\t</h2>";
		}
		if ($row['post_type'] == 6) echo "❤️ LIKE \n";
		// TODO: properly link to original author's h-card and the original post
		if ($row['post_type'] == 7) {
			echo "<a href=\"/kind/reply\" class=\"kind\">↩️ REPLY</a> ";
			echo "<span class=\"u-in-reply-to h-cite\">";
			echo "to <a class=\"p-author h-card\" href=\"". $row['reply_to_author_h_card'] . "\">" . $row['reply_to_author'] . "</a>'s";
				if (isset($row['reply_to_title'])) {
					echo " post <a class=\"u-url\" href=\"" . $row['reply_to_url'] . "\">";
						echo "<span class=\"p-name\">" . $row['reply_to_title'] . "</span>";
					echo "</a>";
				} else {
					echo " <a class=\"u-url\" href=\"" . $row['reply_to_url'] . "\">post</a>:";
				}
			echo "</span>";
		}
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
		echo "\t<span class=\"entry-data\">\n";
		// Ok now for the cute lil bottom text, with location/timestamps/such
		if ($row['display_location'] == 1) echo "\t\t📍 " . $row['location'] . "<br>\n";

		echo "\t\tPosted <time class=\"dt-published\" datetime=\"" . $row['published_date'] . "\"><a class=\"u-url\" href=\"" . $row['permalink'] . "\">" . date('F j, Y \a\t H:i', strtotime($row['published_date'])) . "</a></time>";
		if (isset($row['updated_date'])) {
			echo ", updated <date class=\"dt-updated\" datetime=\"" . $row['updated_date'] . "\">" . date('F j, Y \a\t H:i', strtotime($row['updated_date'])) . "</date>";
		}
		echo " by <img class=\"u-photo hidden-u-photo\" src=\"https://jacobhall.net/images/toothbrush_profile_small.jpg\" /><a class=\"p-author h-card\" href=\"https://jacobhall.net\">Jacob Hall</a>\n\t</span>\n</article>\n";
	}
?>