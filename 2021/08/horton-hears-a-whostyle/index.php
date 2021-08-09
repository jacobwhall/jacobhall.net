<!DOCTYPE html>
<html lang="en">
<head>
		<title>🖌️ Horton Hears a Whostyle</title>
		<meta charset="utf-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="theme-color" content="#FFFFFF"/>
		<link rel="alternate" type="application/rss+xml" title="RSS Feed for jacobhall.net" href="/feeds/rss/v1.rss">
		<link href="/styles/whostyles/whostyle-v1.css" rel="whostyle">
		<link href="/webmention" rel="webmention">
		<style>
		/* sitewide stuff like fonts and title sizes */
		body {
			text-align: center;
			font-family: 'Work Sans', sans-serif;
			font-size: 100%;
			font-size: max(100%, .4cm);
		}

		@media (max-aspect-ratio: 1/1) {
			body {
				font-size: 4vw;
				font-size: min(4vw, .4cm);
			}
		}
		body, h1, h2, h3, h4, h5, h6 {
			font-size-adjust: 0.5;
		}

		ul {
			list-style-position: inside;
			padding-left: 0.5em;
		}
		#contentbox {
			text-align: left;
			display: inline-block;
			width: 100%;
			max-width: 50em;
			min-width: 10%
		}
		article {
			display: inline-block;
			text-align: left;
			padding-bottom: 3em;
			width: 100%;
			max-width: 50em;
			min-width: 10%;
		}
		article img {
			width: 100%;
		}
		.feed img {
			width: 100%;
		}
		.facepile img {
			width: 4em;
			height:4em;
			border-radius: 50%;
		}
			
		/* code highlighting rules */
		.code {
			overflow-x:auto;
			/* white-space: pre-wrap; */
			font-family: monospace;
		}
		pre.code {
			border: 1em solid #2c2e34;
			border-radius: 1em;	
			color: #c5cdd9;
			background-color: #2c2e34;
		}
		.code a {
			color: inherit;
		}
		.code .Comment {
			color: #828a98;
			font-style: italic;
		}
		.code .Constant {
			color: #a0c980;
		}
		/* not used? might remove. */
		.code .Error {
			color: #ffffff;
			background-color: #ff6060;
			padding-bottom: 1px;
		}
		.code .Identifier {
			color: #6cb6eb;
		}
		.code .PreProc {
			color: #ec7279;
		}
		.code .Special {
			color: #c5cdd9;
		}
		.code .Statement {
			color: #d38aea;
		}	
		.code .vimCommand {
			color: #d38aea;
		}	
		.code .texStatement {
			color: #6cb6eb;
		}
		.code .texSpecial {
			color: #deb974;
		}
		.code .Type {
			color: #d38aea;
		}	
		/* h-card, h-entry rules */
		.p-note {
			display: none;
		}
		.kind {
			font-family: monospace;
			color: black;
			text-decoration: none;
		}
		.kind:hover {
			text-decoration: underline;
		}
		.entry-data {
			font-family: monospace;
		}
		.hidden-u-photo {
			display: none;
		}
		@media (min-width: 80em) {
			article {
				margin-top: 2em;
				padding: 1em;
			}
			article.feed {
				border-radius: 25px;
				border: solid .2em lightgrey;
			}
		}
		#license #indiewebring {
			display: inline-block;
			width: 50em;
			min-width: 50%;
			max-width: 100%;
		}
		u:-webkit-scrollbar {
			display: none;
		}
		.warning {
			color:red;
		}
		#backprofilepic {
			border-radius: 50%;
			height: 4em;
		}
		#sidebar {
			padding: 1em;
			text-align: left;
			line-height: 2em;
		}
		.date {
			color: gray;
			font-style: italic;
		}
		.nostyle {
			color: black;
			text-decoration: none;
		}
		table {
			margin: 0 auto;
			border-collapse: collapse;
			margin-bottom: 1em;
		}
		table, th, td {
			border: 1px solid black;
		}
		th, td {
			padding: .5em;
		}
		#scifairimg {
			object-fit: scale-down;
			width: 100%;
			max-width: 40em;
		}
		.graph {
			object-fit: contain;
			width: 100%;
			max-width: 30em;
		}
		.graphboxwrapper {
			text-align: center;
		}
		.graphbox {
			display:inline-block;
			padding: .75em;
			border: 1px solid lightgrey;
		}
		.slider {
			object-fit: contain;
			width: 100%;
			max-width: 34.5em;
		}
		#backprofilepic {
			border-radius: 50%;
			height: 4em;
		}
		#sidebar {
			padding: 1em;
			text-align: left;
			line-height: 2em;
		}
		.nostyle {
			color: black;
			text-decoration: none;
		}

		/* entire container, keeps perspective */
		.flip-container {
			float: left;
			padding-right: 1em;
			padding-bottom: 1em;
			display: inline-block;	
			perspective: 1000px;
		}
		.flip-container, .front, .back {
			border-radius: 50%;
			width: 4em;
			height: 4em;
		}
		/* flip speed goes here */
		.flipper {
			transition: 0.4s;
			transform-style: preserve-3d;
			position: relative;
		}
		/* hide back of pane during swap */
		.front, .back {
			text-align: center;
			backface-visibility: hidden;
			position: absolute;
			top: 0;
			left: 0;	
		}
		/* front pane, placed above back */
		.front {
			z-index: 2;
			/* for firefox 31 */
			transform: rotateY(0deg);
		}
		/* back, initially hidden pane */
		.back {
			line-height:4em;
			transform: rotateY(180deg);
			background-color: lightgrey;
		}
		.back span {
			font-size: 3em;
		}
		@media (min-width: 80em) {
			#contentbox {
				padding-top: 3.5em;
			}
			#backprofilepic {
				height: 8em;
				width: 8em;
			}
			#sidebar {
				position: absolute;
				left: 5em;
				/* distance from left side should = distance from left side of article to the left side of the window divided by two, and then subtract 5em */
				left: calc(calc(calc(50% - 25em) / 2) - 5em);
				top: 5em;
				text-align: center;
				line-height: 0px;
			}
			.flip-container {
				float: none;
				padding: 0px;
			}
			/* flip the pane when hovered */
			.flip-container:hover .flipper, .flip-container.hover .flipper {
				transform: rotateY(180deg);
			}
			.flip-container, .front, .back {
				width: 8em;
				height: 8em;
			}
			.back {
				line-height: 8em;
			}
			.back span {
				font-size: 6em;
			}
		}
		article h1:first-of-type {
			line-height: 0px;
		}
		.centeredtext {
			text-align: center;
		}
		</style>
<script type="application/ld+json">
	{
	"@context": "https://schema.org",
	"@type": "BlogPosting",
	"headline": "🖌️ Horton Hears a Whostyle",
	"author": {
		"@type": "Person",
		"name": "Jacob Hall"
	},
	"dateModified": ""
	}
	</script></head>
<body>
        <div id="sidebar">
                <a class="nostyle p-author h-card" rel="author" href="https://jacobhall.net/">
                <div class="flip-container" ontouchstart="this.classList.toggle('hover');">
                        <div class="flipper">
                                <div class="front">
                                        <img class="profilepic" id="backprofilepic" src="/profile.jpg" alt="sexy mirror selfie"/>
                                </div>
                                <div class="back">
                                        <span>🏠</span>
                                </div>
                        </div>
                </div>
                        <h5>⬅️ Jacob Hall's weblog</h5>
                </a>
        </div>
<div id="contentbox">
<article class="h-entry">
<h2 class="p-name">🖌️ Horton Hears a Whostyle</h2>
<span class="date">Published on August 6, 2021</span>
<div class="e-content">
<a class="u-category" href="https://news.indieweb.org/en"></a>
<link rel="stylesheet" href="styles/whostyles/kickscondor-com/whostyle.css">
<link rel="stylesheet" href="whostyles/philosopher-life.css">
<p>
Back in 2018, <a class="u-category h-card" href="https://www.kickscondor.com/">Kicks Condor</a>, <a href="https://sphygm.us/">sphygmus</a>, and others came up with "<a href="https://www.kickscondor.com/whostyles/">whostyles</a>."
	The idea is that when a website displays a post reply that was written by someone else, the reply is displayed using the style (i.e. CSS rules) of that person's website.
	A couple weeks ago, I was chatting with <a class="u-category h-card" href="https://angelogladding.com/">Angelo</a> and <a class="u-category h-card" href="https://www.maxwelljoslyn.com/">Maxwell</a> at a <a href="https://events.indieweb.org/">Homebrew Website Club</a> meeting, and we came across this idea.
	I feel like it would fit right in to our webmention dreams at the IndieWeb, and I thought it would be fun to implement whostyles on my own site.
	This is my attempt to do so!
</p>
<style>
	#horton-wrapper {
		text-align: center;
		font-style: italic;
	}
	#horton {
		width:50%;
	}
</style>
	
<div id="horton-wrapper">
	<img id="horton" src="horton.webp" alt="Drawing of Horton the elephant">
	<p>Illustration by my talented partner Alyssa</p>
</div>
<h2>some example whostyles</h2>
<p>
	Importing my own style is redundant, so let's try importing someone else's whostyle: Kicks!
	This is a reply to <a href="https://www.cyberneticforests.com/">Eryk Salvaggio</a> he posted <a href="https://twitter.com/kickscondor/status/1370092733390462976">on Twitter</a> a while back.
	Hopefully they won't mind me re-posting this for demonstration purposes:
</p>

<div class="whostyle-kickscondor-com">
	<p>Love that album soooo much. It’s a classic! Looking forward to everything else you do.</p>
</div>
<p>
	If you've visited Kicks' site, you'll immediately recognize that as their post.
	It's almost creepy to use his styling, as if I summoned him to be with us right now…and that's exactly the point of whostyles!
	I don't intend to use Kicks' style on my own work, both because doing so would be unoriginal and I like my own style.
	But if Kicks were to post a reply to something I've written, wouldn't it be awesome if their words were always styled how they would style them, even on my site?
</p>
<p>
	Let's try quoting someone else using their whostyle, this time <a href="https://philosopher.life">h0p3</a>.
	h0p3's style is one of the examples provided in Kicks' demonstration, and is unique to the ones we've tried so far because it has its own font!
	I'll quote a short remark h0p3 posted in <a href="https://www.kickscondor.com/HT2020/">this conversation</a> last year:
</p>
<div class="whostyle-philosopher-life">
	<p>This is fun. =)</p>
</div>
<p>
	Even a brief comment is made much more individual by its style!
</p>
<h2>making it happen</h2>
<p>
	My implementation of whostyles ended up veering pretty far from Kicks' original vision.
	I'd love to hear others' thoughts on the matter, and I'd be happy to adjust my own site's whostyle system to better work with yours.
</p>
<h3>scoping CSS styles</h3>
<p>
	On <a href="https://www.kickscondor.com/whostyles/">Kicks' whostyles page</a>, they discuss putting comments in iframes that load their own style sheets.
	There are good reasons for sandboxing comments like that, as CSS is a powerful tool that could be abused by commenters.
	But to be honest, iframes feel like too much infrastructure for my site, and I don't want to expect others who want to load my whostyle to feel obligated to use them either.
	I'd rather use raw CSS — I figure I'll have to moderate it anyways — which aligns with my values to keep things simple.
	I propose that at the beginning of each whostyle class selector (see below), "all: revert;" is specified to revert all styling for that element to the browser default.
	This effectively gives the whostyle writer a blank slate: they can inherit the host site's styling if they want, or revert everything and do things their own way.
</p>
<p>
	Kicks' proposed standard uses the CSS class .whostyle to confine someone's styling to the post element.
	For my own implementation, I plan to edit the CSS class name for each post to reflect the domain of the original poster.
	I propose that whostyle class names start with whostyle, and then followed with the URL of the person's h-card, with all slashes and periods replaces with hyphens.
	This makes whostyle classes easy to guess, and unique to the poster.
	For example, a post of mine would have the class .whostyle-jacobhall-net
</p>
<h3>style inheritance</h3>
<p>
	If I comment on your site, I can expect that you'll style my post whichever way you like.
	If I comment on your site <i>with a whostyle</i>, it's reasonable for me to expect that the element my comment appears in on your site will inherit the CSS rules of your site.
	Managing whostyles this way will make it easy to make minor changes to one's post, for example you could just add a font and allow the parent site to handle the rest.
</p>
<p>
If you would like to style your comment from scratch, there is a CSS property called <a href="https://developer.mozilla.org/en-US/docs/Web/CSS/all">all</a> and keyword called <a href="https://developer.mozilla.org/en-US/docs/Web/CSS/revert">revert</a>, that have decent <a href="https://caniuse.com/css-all">browser</a> <a href="https://caniuse.com/css-revert-value">coverage</a>.
	To revert all CSS rules to the client browser's defaults, begin your whostyle with all: revert;
</p>
<pre class="code">
.whostyle-jacobhall-net {
	<span class="Type">all</span>: <span class="Constant">revert</span>;
	<span class="Comment">/* add styling here */</span>
}
</pre>
<h3>moderating whostyles</h3>
<p>
	My larger plan for this site involves a full comment moderation system, so I already intend to read everything that people send me.
	Given the scale of my site, and the relative geekiness of whostyles as a concept, I'm not too worried about how many CSS rules I'll have to manually review day-to-day.
	When my webmention endpoint receives a webmention, it will sniff the source site for a whostyle.
	If one is detected, it will be downloaded and presented to me as a part of the comment moderation process.
	I will review the rules within it, making sure that it a) doesn't do anything naughty and b) doesn't completely break my site.
	Perhaps if this becomes a burden, I'll invest more time writing a script to do the editing for me.
</p>
<h3>updating whostyles</h3>
<p>
	An I've often heard within the IndieWeb has to do with the historical "look" of one's site, and if it should be maintained in old posts even after a new style has been adopted.
	I plan to re-theme this site from time to time, and when I do so I will intentionally leave this article with the CSS it was written in, for example.
	I don't want to force people to support all of their previous comments with their current whostyle!
	Over years of reimagining ourselves online, it would be very complex to create styles that properly support everything we've written.
</p>
<p>
	If your whostyle is identical to the last one you sent me, my website will automatically use it without the need for moderation.
	If you update it, my moderation system will have me review the changes and then your new post will have the new style.
</p>
<h2>my proposal in a nutshell</h2>
<ul>
	<li>whostyles are defined using &lt;link rel="whostyle"&gt; tags in the &lt;head&gt; of a post page</li>
	<li>the class name of a whostyle element is "whostyle" and then the URL of the poster's h-card, with spaces and punctuation replaced with hyphens (e.g. whostyle-jacobhall-net)</li>
	<li>when writing a whostyle, assume that you inherit the CSS rules of the parent website</li>
	<li>whostyles should be stored and linked per-post rather than per-poster</li>
</ul>
<h2>remaining issues</h2>
<p>
	I really, really want to support fonts in whostyles.
	It feels like the single best way for a writer to style their work, and would breathe a whole lot of life into the comments section.
	The problem is, font licensing can be messy, and it's hard to know where a web font is coming from!
	Not only that, but I don't really want to be linking to external resources like Google Fonts from my site.
	Feel free to add comments to your whostyle with font you want to link, and how I could add it as an asset on my site for your use.
	If you have ideas for making font linking safer or more convenient, I'd love to hear your thoughts!
</p>
<h2>final notes</h2>
<p>
	There are so many people in the IndieWeb who are doing cool things right now, and I'm excited to grow an interoperability between my site and those it connects to.
	Building a more personalized social web requires this sort of innovation!
</p>
<p>
	I've finally flipped the switch so that my website will accept incoming webmentions.
	My IndieWeb implementations are a work in progress, so much so that you might want to <a href="mailto:email@jacobhall.net">email me</a> a notification of your reply as well 😂
	But with a little luck and some more tuning, I'll learn to trust my webmention system, and hopefully use it to conduct more of my interactions Out There.
	So, send me a webmention, perhaps with a whostyle!
	It might take me days to properly accept it, but that's the only way for my website to improve.
	Or just be in touch, and let's chat about how to make our computers talk to each other in cool ways.
</p>
<p>
	TTFN ❤️
</p>
</div>
</article>
<?php
$postID = 83;
include $_SERVER['DOCUMENT_ROOT'] . '/feeds/comments.php';
?>
</div>
</body>
</html>
