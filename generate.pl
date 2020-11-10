#!/usr/bin/perl
use File::Slurp;
use DateTime;

# Create list of all files
my @dirs = <*/*/title.txt>;

# Read template for beginning of article HTML
$beginning = read_file("beforearticle.html");

# Read the sitewide CSS style
$universalstyle = read_file("styles/universal.css");
$articlestyle = read_file("styles/article.css");
$homepagestyle = read_file("styles/homepage.css");

# Foreach file from glob
foreach (@dirs) {
	# If file path matches regex, assign parentheticals to $1 and $2
	if (/(\d{4})\/(.+)\/title\.txt$/) {

		# Write year and file to variables
		my $year = $1;
		my $name = $2;

		# Try to read article file. If fails, throw error
		if (-e $year.'/'.$name.'/'.$name.'.html') {
			$article = read_file($year.'/'.$name.'/'.$name.'.html');
		} else {
			print 'Skipping '.$year.'/'.$name.": no article file\n";
			next;
		}

		# Try to detect if there is accidentally HTML header code in the article
		print "WARNING: ".$year.'/'.$name." article file appears to contain unwanted HTML code\n" if ($article =~ /<head>/ || $article =~ /<\/body>/);

		# Grab title of article
		$title = read_file($_);

		# Remove newline in title
		chomp $title;

		# Reset $thisbeginning for this article
		$thisbeginning = $beginning;

		# Put the title between the <title> tags
		$thisbeginning =~ s/(?<=<title>).*(?=<\/title>)/$title/;
		
		# Write article last modified timestamp to variable
		my $epoch_timestamp = (stat($year.'/'.$name.'/'.$name.'.html'))[9];
		
		# Assume that we should not overwrite article title: it is already written in .html
		$writearticletitle = 0;

		# Was article written after I started keeping track of article modification times?
		if ($epoch_timestamp > 1604714100) {

			# For this article we should write the article title, as well as timestamp
			$writearticletitle = 1;
			my $timestamp = DateTime->from_epoch( epoch => $epoch_timestamp )->iso8601();
		}

		# Construct the style for this page
		$thisstyle = "\t\t<style>\n".$universalstyle.$articlestyle."\t\t</style>\n";

		# Add CSS style to end of <head>
		$thisbeginning =~ s/.*(?=<\/head>)/$thisstyle/;

		# If there is a file containing additions to <head>
		if (-e $year.'/'.$name.'/addtohead.html') {
			# Read addtohead.html file and write it to $addtohead
			$addtohead = read_file($year.'/'.$name.'/addtohead.html');
			# Write $addtohead to $beginning before the closing </head> tag
			$thisbeginning =~ s/.*(?=<\/head>)/$addtohead/;
		}

		if ($writearticletitle) {
	$schema = "<script type=\"application/ld+json\">\n\t{\n\t\"\@context\": \"https://schema.org\",\n\t\"\@type\": \"BlogPosting\",\n\t\"headline\": \"$title\",\n\t\"author\": {\n\t\t\"\@type\": \"Person\",\n\t\t\"name\": \"Jacob Hall\"\n\t},\n\t\"dateModified\": \"$timestamp\"\n\t}\n\t</script>";
			$thisbeginning =~ s/.*(?=<\/head>)/$schema/;
			# Add <article> and title to top of article
			$thisbeginning .= "\n<article>\n<h2>".$title."</h2>\n";
			# Write the concatenated $beginning, $article, and end tags to index.php, including title and <article> tags
			overwrite_file($year.'/'.$name.'/index.html', $thisbeginning.$article."</article>\n</body>\n</html>");

		} else {

			# Write the concatenated $beginning, $article, and end tags to index.php
			overwrite_file($year.'/'.$name.'/index.html', $thisbeginning.$article."</body>\n</html>");

		}
	}	
}

# Now let's generate the homepage
# For now, all this script does is insert the CSS. But someday...someday it'll move mountains

# Read homepage file
$homepage = read_file("homepage.php");

# Construct the style for this page
$thisstyle = "\t\t<style>\n".$universalstyle.$homepagestyle."\t\t</style>\n";

# Add CSS style to end of <head>
$homepage =~ s/.*(?=<\/head>)/$thisstyle/;

# Write homepage to index.php
overwrite_file('index.php', $homepage);

# delete generated recipes from last time

# Check for recipes in recipe folder

# make array for recipe names/links
 
# for each recipe
 	
# 	read title, filename, ingredients, directions
# 	create recipe page using templates
# 	write recipe page to /recipes/filename
#	save title, filename to array

# create /recipes/index.html using template

# write /recipes/index.html
