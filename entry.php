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
$querystring = "SELECT * from entries";
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
				echo "\t<h2 class=\"p-name\">" . $row['post_title'] . "</h2>\n";
			$content = $row['content_summary'] . " <a href=\"" . $row["permalink"] . "\">Read article &gt;&gt;</a>\n";
		}
		if ($row['post_type'] == 2) {
			echo "<p><a href=\"/kind/note\" class=\"kind\">📝 NOTE</a></p>\n";
			if (isset($row['post_title']))
				echo "<h2 class=\"p-name\">" . $row['post_title'] . "</h2>\n";
		}
		// TODO: Add plural if there are multiple photos
		if ($row['post_type'] == 3) echo "📷 PHOTO ";
			// Photos and videos will probably be under any caption/note/other content
		if ($row['post_type'] == 4) echo "🎥 VIDEO ";
		if ($row['post_type'] == 5) {
			echo "<a href=\"/kind/bookmark\" class=\"kind\">🔗 BOOKMARK</a>\n";
			$content = "<a class=\"u-bookmark-of\" href=\"" . $row['content'] . "\"><h2 class=\"p-name\">" . $row['post_title'] . "</h2></a>\n";
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

		
		echo "\t\t<a class=\"u-url\" href=\"" . $row['permalink'] . "\"> Posted <date class=\"dt-published\" datetime=\"" . $row['published_date'] . "\">" . date('F j, Y \a\t H:i', strtotime($row['published_date'])) . "</date>";
		if (isset($row['updated_date'])) {
			echo ", updated <date class=\"dt-updated\" datetime=\"" . $row['updated_date'] . "\">" . date('F j, Y \a\t H:i', strtotime($row['updated_date'])) . "</date>";
		}
		echo "\n\t\t</a>\n\t</p>\n</article>";
	}
echo "</div>\n</body>\n</html>";
?>
