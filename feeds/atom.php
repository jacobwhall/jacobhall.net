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
header('Content-type: application/atom+xml');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<feed xmlns=\"http://www.w3.org/2005/Atom\">
\t<title>Jacob Hall</title>
\t<link href=\"https://jacobhall.net\">
\t<author>
\t\t<name>Jacob Hall</name>
\t</author>
\t<id>https://jacobhall.net</id>
\t<category term=\"blog\">
\t<icon>/profile.jpg</icon>\n";
foreach($result as $row) {
	if (isset($row["published_date"])) {
		echo "\t<updated>" . date(DateTimeInterface::ATOM, strtotime($row['published_date'])) . "</updated>\n";
		break;
	}
}
echo "\n";
// For each returned row from query
foreach($result as $row) {
	/*
	 * Currently supported post types:
	 * 1 - Articles
	 * 2 - Notes
	 * 3 - Photos (photo not included in feed yet)
	 * 4 - Videos (TBD, I haven't posted any yet)
	 * 10 - Files
	 * TODO: Replies and Reposts. I'm nervous this will be spammy though
	 */
	if (in_array($row['post_type'], [1,2,3,4,10])) {
		echo "\t<entry>\n";
		if (isset($row["post_title"])) {
			echo "\t\t<title>" . $row["post_title"] . "</title>\n";
			if (isset($row["content_summary"])) {
				echo "\t\t<summary>" . strip_tags($row["content_summary"]) . "</summary>\n";
			} elseif (isset($row["content"])) {
				echo "\t\t<content>" . strip_tags($row["content"]) . "</content>\n";
			}
		} else {
			if (isset($row["content"])) {
				echo "\t\t<content>" . strip_tags($row["content"]) . "</content>\n";
			} elseif (isset($row["content_summary"])) {
				echo "\t\t<summary>" . strip_tags($row["content_summary"]) . "</summary>\n";
			}
		}
		echo "\t\t<link rel=\"alternate\">" . $row["permalink"] . "</link>\n";
		echo "\t\t<author>\n\t\t\t<name>Jacob Hall</name>\n\t\t\t<email>email@jacobhall.net</email>\n\t\t</author>\n";
		// TODO: output <enclosure> tags if there is multimedia in post
		echo "\t\t<published>" . date(DateTimeInterface::ATOM, strtotime($row['published_date'])) . "</published>\n";
		echo "\t</entry>\n";
	}
}
echo "</feed>";
?>
