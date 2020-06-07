#!/usr/bin/perl
use File::Slurp;

# Create list of all files
my @dirs = <*/*/title.txt>;

# Read template for beginning of article HTML
$beginning = read_file("beforearticle.html");

# Foreach file from glob
foreach (@dirs) {
	# If file path matches regex, assign parentheticals to $1 and $2
	if (/(\d{4})\/(.+)\/title\.txt$/) {
		# Write year and file to variables
		my $year = $1;
		my $name = $2;
		# Try to read article file. If fails, throw error
		print 'Skipping '.$year.'/'.$name.': no article file' unless $article = read_file($year.'/'.$name.'/'.$name.'.html');
		# Grab title of article
		$title = read_file($_);
		# Remove newline in title
		chomp $title;
		# Put the title between the <title> tags
		$beginning =~ s/(?<=<title>).*(?=<\/title>)/$title/;
		# If there is a file containing additions to <head>
#		if (-e $year.'/'.'$name'.'/addtohead.html') {
			# Read addtohead.html file and write it to $addtohead
#			$addtohead = $read_file($year.'/'.'$name'.'/addtohead.html');
			# Write $addtohead to $beginning before the closing </head> tag
#			$beginning =~ s/.*(?=<\/head>)/$addtohead/;
#		}
		# Write the concatenated $beginning, $article, and end tags to index.php
		overwrite_file($year.'/'.$name.'/index.html', $beginning.$article."</body>\n</html>");
	}	
}
