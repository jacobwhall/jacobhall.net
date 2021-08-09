<?php
include __DIR__ . '/../../jhsite.config.php';
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
// Check this post for likes
// TODO: better handle situations where there is not a $row['post_id']
$likesquery = "SELECT author, author_photo, original_url FROM entries WHERE reply_to_id = " . $postID . " AND published = true AND post_type = 6 ORDER BY published_date DESC";
$getlikes = $conn->prepare($likesquery);
$getlikes->execute();
$likesresult = $getlikes->fetchAll();

if (!empty($likesresult)) {
	echo "<h2>Likes</h2>\n<div class=\"facepile\">\n";
	foreach ($likesresult as $like) {
		echo "<a href=\"" . $like["original_url"] . "\"><img src=\"" . $like["author_photo"] . "\" alt=\"Photo of " . $like["author"] . "\"></a>";
	}
	echo "</div>";
}

// Now let's see if this baby has some comments
// TODO: better handle situations where there is not a $row['post_id']
$commentquery = "SELECT published_date, updated_date, original_url, permalink, content, content_summary, author, author_h_card, whostyle FROM entries WHERE reply_to_id = " . $postID . " AND post_type != 6 AND published = true ORDER BY published_date DESC";
$getcomments = $conn->prepare($commentquery);
$getcomments->execute();
$commentresult = $getcomments->fetchAll();
// For each returned row from query


echo "<h2>Comments</h2>";
echo "<p>You can leave a comment here via webmention! Note that I moderate webmentions manually, so it may take a few days to appear.</p>";
foreach($commentresult as $comment) {
	if (isset($comment['whostyle'])) {
		echo "<link rel=\"stylesheet\" href=\"/styles/whostyles/" . $comment['whostyle'] . "/whostyle.css\">";
		echo "<article class=\"whostyle-" . $comment['whostyle'] . "\">";
	} else {
		echo "<article class=\"whostyle-jacobhall-net\">";
	}
	echo "<span class=\"kind\">↩️ REPLY</span> ";
	// echo "<a href=\"/kind/reply\" class=\"kind\">↩️ REPLY</a> ";
	
	echo "<span class=\"u-in-reply-to h-cite\">";
	echo "from <a class=\"p-author h-card\" href=\"" . $comment['author_h_card'] . "\">" . $comment['author'] . "</a>\n";
	
	echo "\t\ton <time class=\"dt-published\" datetime=\"" . $comment['published_date'] . "\"><a class=\"u-url\" href=\"";
	if (isset($comment['original_url'])) {
		echo $comment['original_url'];
	} else {
		echo $comment['permalink'];
	}
	echo "\">" . date('F j, Y \a\t H:i', strtotime($comment['published_date'])) . "</a></time>";
	if (isset($comment['updated_date'])) {
		echo ", updated <date class=\"dt-updated\" datetime=\"" . $comment['updated_date'] . "\">" . date('F j, Y \a\t H:i', strtotime($row['updated_date'])) . "</date>";
	}
	if (isset($row['content_summary'])) {
		echo "\t\t<p class=\"p-summary\">\n" . $comment['content_summary'] . "\n\t\t</p>";
		echo "<a href=\"" . $comment['permalink'] . "\">read full comment &gt;&gt;</a>";
	} else {
		echo $comment['content'];
	}
	echo "</span>";
	echo "</article>";
	// TODO: more gracefully pad comments
	echo "<br><br>";
}
?>
