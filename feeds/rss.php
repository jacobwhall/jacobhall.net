<?php
include '../../jhsite.config.php';
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

function getXMLfromHTML($html) {
	$dom = new DOMDocument;
	$dom->loadHTML($html);
	return $dom->saveXML($dom);
}
$querystring = "SELECT * FROM entries WHERE published = TRUE AND (author = 'Jacob Hall' OR author IS NULL) ORDER BY published_date DESC";
if (isset($querylimit)) $querystring = $querystring . " LIMIT " . $querylimit;
$sth = $conn->prepare($querystring);
$sth->execute();
$result = $sth->fetchAll();
header('Content-type: application/rss+xml');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n
<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n
<channel>\n
<title>Jacob Hall</title>\n
<atom:link href=\"https://jacobhall.net/feed/rss/v1.rss\" rel=\"self\" type=\"application/rss+xml\" />
<link>https://jacobhall.net</link>\n
<description>RSS feed of Jacob Hall's blog</description>\n
<docs>https://validator.w3.org/feed/docs/rss2.html</docs>\n
<managingEditor>email@jacobhall.net (Jacob Hall)</managingEditor>\n
<webMaster>email@jacobhall.net (Jacob Hall)</webMaster>\n
<ttl>120</ttl>\n";
// For each returned row from query
foreach($result as $row) {
	echo "\t<item>\n";
	if (isset($row["post_title"])) {
		echo "\t\t<title>" . $row["post_title"] . "</title>\n";
		if (isset($row["content_summary"])) {
			echo "\t\t<description>" . strip_tags($row["content_summary"]) . "</description>\n";
		} elseif (isset($row["content"])) {
			echo "\t\t<description>" . strip_tags($row["content"]) . "</description>\n";
		}
	} else {
		if (isset($row["content"])) {
			echo "\t\t<description>" . strip_tags($row["content"]) . "</description>\n";
		} elseif (isset($row["content_summary"])) {
			echo "\t\t<description>" . strip_tags($row["content_summary"]) . "</description>\n";
		} else {
			echo "\t\t<description>(no title or description)</description>\n";
		}
	}
	echo "\t\t<link>" . $row["permalink"] . "</link>\n";
	echo "\t\t<author>email@jacobhall.net (Jacob Hall)</author>\n";
	// get tags for this post
	$tagquery = "SELECT tag FROM tags WHERE post_id = " . $row['post_id'];
	$gettags = $conn->prepare($tagquery);
	$gettags->execute();
	$tagresult = $gettags->fetchAll();
	// For each returned row from query
	if (count($tagresult) > 0) {
		foreach($tagresult as $tag) {
			echo "\t\t<category>" . $tag . "</category>\n";
		}
	}
	// Now let's see if this baby has some comments
	// TODO: better handle situations where there is not a $row['post_id']
	$commentquery = "SELECT published_date, updated_date, permalink, content, content_summary, author, author_h_card, whostyle FROM entries WHERE reply_to_id = " . $row['post_id'] . " AND published = true ORDER BY published_date DESC";
	$getcomments = $conn->prepare($commentquery);
	$getcomments->execute();
	$commentresult = $getcomments->fetchAll();
	if (count($commentresult) > 0) {
		echo "\t\t<comments>" . $row["permalink"] . "</comments>\n";
	}
	// TODO: output <enclosure> tags if there is multimedia in post
	echo "\t\t<pubDate>" . date(DateTimeInterface::RSS, strtotime($row['published_date'])) . "</pubDate>\n";
	echo "\t\t<guid isPermaLink=\"true\">" . $row["permalink"] . "</guid>\n";
	echo "\t</item>\n";
}
echo "</channel>\n</rss>";
?>
