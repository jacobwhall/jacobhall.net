#!/usr/bin/perl
use File::Slurp;

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

		# Write the concatenated $beginning, $article, and end tags to index.php
		overwrite_file($year.'/'.$name.'/index.html', $thisbeginning.$article."</body>\n</html>");
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
