<!DOCTYPE html>
<html lang="en">
<head>
	<title>Jacob Hall</title>
	<meta charset="utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#FFFFFF"/>
	<meta name="Description" content="Jacob Hall's personal website">
	<link href="webmention.php" rel="webmention" />
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
		/* not used? might remove. i don't make errors ;) */
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
			article .feed {
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
		#announcement {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			background-color: red;
			color: white;
			line-height: 1.5em;
		}

		#announcement a {
			color: white;
		}

		#firstbox {
			text-align: center;
			padding-top: 2em;
			width: 100%;
		}

		.profilepic {
			border-radius:50%;
		}

		#titleprofilepic {
			height: 10em;
			max-height: 50vh;
		}

		#bottom h1 {
			font-family: 'Montserrat', sans-serif;
			font-size: 2em;
			line-height: 0.5em;
			font-weight: bold;
		}

		#bottom #socials a {
			font-size: 1.5em;
			text-decoration: none;
		}

		#subsequentbox {
			text-align: center;
			width: 100%;
			display: flex;
			flex-flow: row wrap;
			justify-content: space-between;
		}

		.list {
			padding: 1em;
		}

		.list ul {
			text-align: left;
		}

		#blog {
			text-align: justify;
		}

		.centeredtext {
			text-align: center;
		}

		.video {
			width: 100%;
			max-width: 50em;
			min-width: 10%
		}
		</style>
</head>
<body>
<!-- <div id="announcement">Looking for a resource I sent you here for? <a href="/">Click here</a></div> -->
<div class="h-card" id="firstbox">
	<a class="u-url" href="https://jacobhall.net"></a>
	<div id="top">
		<img class="profilepic u-photo" id="titleprofilepic" src="/profile.jpg" alt=""/>
	</div>
	<div id="bottom">
		<h1 class="p-name">Jacob Hall</h1>
		<data class="p-note">Student at William &amp; Mary studying geology and coding in his free time</data>
		<p><i>howdy! this is my weblog.<br>please leave your shoes by the door</i></p>
		<p id="socials">
			<a class="u-email" title="email me" href="mailto:email@jacobhall.net">📧</a>
			&nbsp;&nbsp;
			<a rel="me" title="my Instagram profile" href="https://www.instagram.com/jacobwhall/">📷</a>
			&nbsp;&nbsp;
			<a rel="me" title="my GitHub profile" href="https://github.com/jacobwhall">👨‍💻</a>
			&nbsp;&nbsp;
			<a rel="me" title="my Couchsurfing profile" href="https://www.couchsurfing.com/people/jacob-hall-10">🛋️</a>
			&nbsp;&nbsp;
			<a rel="me" title="my Mastodon profile" href="https://fosstodon.org/@jacobhall">🐘</a>
		</p>
		<p><a href="about.html">about</a> - <a href="links.html">linkroll</a> - <a href="dreams.html">ideas</a></p>
	</div>
</div>
<div id="contentbox">
<?php
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
			reply_to_title,
			reply_to_content,
			reply_to_url,
			content,
			content_summary from entries";
$querylimit = 5;
include("feed.php");
?>
<h2><a href="/kind">view more posts ➡️</a></h2>
<div id="subsequentbox">
	<div class="list">
		<h2>articles i've written</h2>
		<ul>
<?php
$querystring = "SELECT post_title, permalink FROM entries WHERE post_type=1 AND published=true ORDER BY published_date DESC LIMIT 3";
$articlequery = $conn->prepare($querystring);
$articlequery->execute();
$result = $articlequery->fetchAll();
foreach ($result as $row) {
	echo "\t\t\t<li>\n\t\t\t\t<a href=\"" . $row['permalink'] . "\">" . $row['post_title'] . "</a>\n\t\t\t</li>\n";
}
?>
		</ul>
		<!-- TODO: Add all articles to entries database, and then change this link to /kind/article -->
		<a href="articles.html">view all articles ➡️</a>
	</div>
	<div class="list">
		<h2>things i've made</h2>
		<ul>
			<li>
				<a href="2019/printkey">3D Print KW1, SC1, SC4 Keys</a>
			</li>
			<li>
				<a href="https://wordways.us">wordways.us</a>
			</li>
			<li>
				<a href="/nhoh">Named Houses of Harrisonburg</a>
			</li>
			<li>
				<a href="https://thehbarchive.org">The H-B Archive</a>
			</li>
			<li>
				<a href="blurred-fonts-project.html">Blurred Fonts Project</a>
			</li>
		</ul>
	</div>
</div>
</div>
<footer>
<div id="indiewebring">
	<a href="https://xn--sr8hvo.ws/%F0%9F%9A%84%F0%9F%8F%8B%F0%9F%94%A7/previous">←</a>
	An IndieWeb Webring 🕸💍
	<a href="https://xn--sr8hvo.ws/%F0%9F%9A%84%F0%9F%8F%8B%F0%9F%94%A7/next">→</a>
</div>
<div id="license">
<p>
<p xmlns:dct="http://purl.org/dc/terms/" xmlns:vcard="http://www.w3.org/2001/vcard-rdf/3.0#">
  <a rel="license"
     href="http://creativecommons.org/publicdomain/zero/1.0/">
<svg width="4.258em" height="1.5em" enable-background="new -0.5 -0.101 88 31" version="1.1" viewBox="-.5 -.101 88 31" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
	<title>🄍 (CC0)</title>
	<path d="m1.803 0.482 83.127 0.149c1.161 0 2.198-0.173 2.198 2.333l-0.103 27.556h-87.32v-27.658c0-1.236 0.118-2.38 2.098-2.38z" fill="#fff"/>
	
		<ellipse cx="13.887" cy="15.502" rx="11.101" ry="11.174" fill="#fff"/>
	
	<path d="M23.271,4.061c3.484,2.592,5.754,6.744,5.755,11.44c-0.001,4.272-1.88,8.095-4.842,10.705h62.853V4.061H23.271z"/>
	<g fill="#fff">
		<path d="m35.739 7.559c0.392 0 0.728 0.059 1.002 0.173 0.276 0.116 0.5 0.268 0.674 0.456 0.173 0.189 0.299 0.405 0.379 0.647 0.079 0.242 0.118 0.494 0.118 0.753 0 0.253-0.039 0.503-0.118 0.749-0.08 0.244-0.206 0.462-0.379 0.65-0.174 0.189-0.397 0.341-0.674 0.456-0.274 0.114-0.61 0.173-1.002 0.173h-1.452v2.267h-1.382v-6.324h2.834zm-0.379 2.976c0.158 0 0.312-0.012 0.457-0.035 0.147-0.023 0.276-0.069 0.388-0.137s0.201-0.164 0.269-0.288 0.101-0.287 0.101-0.487-0.033-0.362-0.101-0.487c-0.067-0.124-0.157-0.221-0.269-0.287-0.111-0.068-0.24-0.114-0.388-0.138-0.146-0.024-0.299-0.036-0.457-0.036h-1.073v1.896l1.073-1e-3z"/>
		<path d="m43.751 13.4c-0.476 0.417-1.133 0.625-1.972 0.625-0.851 0-1.509-0.207-1.976-0.62-0.466-0.412-0.699-1.052-0.699-1.913v-3.933h1.381v3.934c0 0.171 0.016 0.338 0.045 0.505 0.029 0.165 0.091 0.311 0.185 0.439 0.094 0.126 0.225 0.229 0.392 0.309 0.167 0.081 0.392 0.12 0.673 0.12 0.493 0 0.833-0.11 1.021-0.332s0.282-0.568 0.282-1.04v-3.935h1.382v3.934c-1e-3 0.855-0.238 1.49-0.714 1.907z"/>
		<path d="m49.07 7.559c0.3 0 0.572 0.027 0.818 0.081 0.244 0.054 0.457 0.14 0.633 0.261 0.177 0.121 0.312 0.282 0.41 0.482 0.096 0.201 0.146 0.45 0.146 0.745 0 0.318-0.072 0.584-0.216 0.796-0.146 0.212-0.357 0.388-0.639 0.523 0.387 0.112 0.676 0.31 0.865 0.589 0.189 0.281 0.286 0.62 0.286 1.015 0 0.319-0.062 0.595-0.187 0.828-0.123 0.232-0.289 0.423-0.496 0.571-0.209 0.148-0.445 0.257-0.713 0.327-0.269 0.07-0.541 0.105-0.822 0.105h-3.047v-6.323h2.962zm-0.175 2.56c0.246 0 0.448-0.059 0.607-0.178 0.158-0.118 0.236-0.309 0.236-0.576 0-0.147-0.025-0.269-0.078-0.363-0.053-0.093-0.123-0.168-0.211-0.221-0.09-0.053-0.189-0.091-0.305-0.109-0.115-0.022-0.232-0.032-0.355-0.032h-1.294v1.48l1.4-1e-3zm0.08 2.685c0.135 0 0.264-0.014 0.387-0.04s0.23-0.072 0.326-0.133c0.092-0.062 0.168-0.147 0.226-0.254 0.056-0.104 0.083-0.241 0.083-0.406 0-0.324-0.092-0.557-0.271-0.695-0.182-0.138-0.424-0.208-0.723-0.208h-1.505v1.738h1.479v-2e-3h-2e-3z"/>
		<path d="M54.143,7.559v5.156h3.062v1.168H52.76V7.559H54.143z"/>
		<path d="m59.748 7.559v6.324h-1.382v-6.324h1.382z"/>
		<path d="m65.451 9.247c-0.082-0.132-0.186-0.249-0.309-0.349-0.123-0.102-0.263-0.18-0.418-0.236-0.156-0.057-0.316-0.084-0.488-0.084-0.312 0-0.574 0.062-0.793 0.183-0.217 0.12-0.394 0.283-0.525 0.486-0.136 0.204-0.232 0.436-0.296 0.695-0.062 0.259-0.093 0.528-0.093 0.806 0 0.267 0.031 0.524 0.093 0.776 0.062 0.251 0.16 0.477 0.296 0.678 0.134 0.201 0.312 0.361 0.525 0.483 0.219 0.12 0.481 0.181 0.793 0.181 0.424 0 0.752-0.13 0.99-0.389 0.236-0.26 0.383-0.602 0.437-1.028h1.337c-0.034 0.396-0.126 0.753-0.271 1.072-0.146 0.318-0.342 0.591-0.582 0.815-0.238 0.225-0.521 0.396-0.845 0.513-0.323 0.119-0.678 0.178-1.065 0.178-0.479 0-0.914-0.084-1.297-0.252-0.385-0.169-0.709-0.398-0.973-0.695-0.265-0.295-0.468-0.642-0.607-1.04-0.142-0.399-0.211-0.829-0.211-1.289 0-0.473 0.069-0.911 0.211-1.316 0.141-0.404 0.344-0.758 0.607-1.059 0.264-0.302 0.588-0.536 0.973-0.708 0.384-0.172 0.815-0.258 1.297-0.258 0.348 0 0.676 0.051 0.981 0.15 0.308 0.102 0.583 0.248 0.827 0.44 0.243 0.191 0.443 0.43 0.604 0.712 0.158 0.283 0.259 0.608 0.301 0.975h-1.34c-0.024-0.163-0.077-0.31-0.159-0.44z"/>
		<path d="m35.615 16.418c0.405 0 0.782 0.062 1.131 0.192 0.35 0.13 0.651 0.324 0.906 0.585 0.255 0.26 0.455 0.586 0.599 0.975 0.144 0.391 0.216 0.849 0.216 1.371 0 0.463-0.059 0.888-0.176 1.277-0.118 0.391-0.295 0.727-0.532 1.012-0.238 0.281-0.534 0.504-0.89 0.668-0.354 0.16-0.772 0.242-1.254 0.242h-2.71v-6.322h2.71zm-0.096 5.154c0.199 0 0.393-0.031 0.581-0.098 0.188-0.062 0.354-0.173 0.502-0.323 0.146-0.151 0.264-0.347 0.352-0.59 0.088-0.241 0.132-0.536 0.132-0.886 0-0.317-0.031-0.606-0.093-0.863-0.062-0.256-0.162-0.479-0.304-0.659-0.141-0.183-0.326-0.323-0.559-0.421-0.231-0.098-0.517-0.146-0.858-0.146h-0.984v3.986h1.231z"/>
		<path d="m39.8 18.289c0.141-0.403 0.344-0.756 0.606-1.059 0.265-0.303 0.589-0.538 0.973-0.709 0.385-0.171 0.816-0.257 1.298-0.257 0.487 0 0.921 0.086 1.303 0.257 0.381 0.171 0.704 0.406 0.969 0.709 0.264 0.303 0.466 0.652 0.605 1.059 0.143 0.404 0.213 0.845 0.213 1.316 0 0.46-0.07 0.891-0.213 1.288-0.142 0.397-0.344 0.744-0.605 1.04-0.266 0.295-0.588 0.525-0.969 0.695-0.382 0.166-0.815 0.252-1.303 0.252-0.481 0-0.913-0.086-1.298-0.252-0.384-0.17-0.708-0.4-0.973-0.695-0.263-0.296-0.466-0.645-0.606-1.04-0.14-0.397-0.211-0.828-0.211-1.288 0-0.471 0.07-0.911 0.211-1.316zm1.262 2.09c0.062 0.252 0.16 0.479 0.295 0.68 0.135 0.2 0.312 0.359 0.527 0.482 0.218 0.121 0.481 0.183 0.792 0.183 0.312 0 0.576-0.062 0.792-0.183 0.218-0.121 0.394-0.281 0.529-0.482 0.134-0.2 0.231-0.428 0.295-0.68 0.062-0.25 0.092-0.508 0.092-0.774 0-0.276-0.03-0.547-0.092-0.806-0.062-0.262-0.161-0.492-0.295-0.696-0.136-0.201-0.312-0.365-0.529-0.485-0.216-0.121-0.48-0.184-0.792-0.184-0.311 0-0.574 0.062-0.792 0.184-0.216 0.12-0.393 0.284-0.527 0.485-0.135 0.204-0.233 0.437-0.295 0.696s-0.093 0.527-0.093 0.806c1e-3 0.266 0.032 0.524 0.093 0.774z"/>
		<path d="m49.092 16.418 1.471 4.348h0.02l1.393-4.348h1.942v6.322h-1.294v-4.48h-0.02l-1.539 4.48h-1.065l-1.54-4.437h-0.019v4.437h-1.293v-6.322h1.944z"/>
		<path d="m58.764 16.418 2.35 6.322h-1.434l-0.476-1.408h-2.351l-0.492 1.408h-1.391l2.377-6.322h1.417zm0.08 3.879-0.793-2.322h-0.018l-0.817 2.322h1.628z"/>
		<path d="m63.547 16.418v6.322h-1.382v-6.322h1.382z"/>
		<path d="m66.604 16.418 2.623 4.242h0.018v-4.242h1.294v6.322h-1.384l-2.611-4.234h-0.02v4.234h-1.294v-6.322h1.374z"/>
	</g>
	<path d="m85.852 0h-84.705c-0.908 0-1.647 0.744-1.647 1.658v28.969c0 0.207 0.167 0.373 0.372 0.373h87.256c0.205 0 0.372-0.166 0.372-0.373v-28.969c0-0.914-0.738-1.658-1.648-1.658zm-84.705 0.75h84.705c0.498 0 0.902 0.406 0.902 0.908v28.557h-86.509v-8.426-20.131c0-0.501 0.405-0.908 0.902-0.908z"/>
		<ellipse cx="14.156" cy="15.661" rx="11.004" ry="11.076" fill="#fff"/>
		<path d="m14.22 8.746c-3.862 0-4.834 3.669-4.834 6.779 0 3.111 0.971 6.779 4.834 6.779s4.834-3.67 4.834-6.779c0-3.111-0.971-6.779-4.834-6.779zm0 2.555c0.157 0 0.3 0.024 0.435 0.06 0.278 0.24 0.414 0.573 0.147 1.038l-2.572 4.76c-0.079-0.603-0.091-1.195-0.091-1.634 0-1.37 0.094-4.224 2.081-4.224zm1.926 2.193c0.137 0.731 0.155 1.493 0.155 2.03 0 1.37-0.094 4.223-2.08 4.223-0.156 0-0.301-0.017-0.435-0.049-0.025-0.01-0.049-0.019-0.074-0.025-0.04-0.012-0.084-0.024-0.122-0.041-0.442-0.188-0.721-0.531-0.319-1.139l2.875-4.999z"/>
		<path d="m14.195 3.748c-3.245 0-5.98 1.137-8.21 3.422-1.128 1.135-1.99 2.431-2.589 3.876-0.585 1.43-0.876 2.921-0.876 4.478 0 1.57 0.291 3.062 0.876 4.479s1.434 2.69 2.548 3.826c1.128 1.121 2.395 1.985 3.802 2.588 1.421 0.59 2.903 0.884 4.449 0.884 1.547 0 3.05-0.304 4.499-0.907 1.448-0.604 2.74-1.471 3.883-2.605 1.101-1.078 1.934-2.317 2.49-3.719 0.571-1.415 0.853-2.932 0.853-4.544 0-1.598-0.281-3.112-0.852-4.528-0.571-1.429-1.407-2.693-2.507-3.801-2.298-2.302-5.092-3.449-8.366-3.449zm0.049 2.119c2.646 0 4.904 0.944 6.784 2.836 0.906 0.912 1.6 1.954 2.073 3.119 0.473 1.164 0.713 2.398 0.713 3.703 0 2.707-0.92 4.952-2.744 6.746-0.948 0.927-2.012 1.638-3.196 2.128-1.17 0.489-2.375 0.732-3.63 0.732-1.268 0-2.481-0.239-3.638-0.717-1.156-0.489-2.193-1.191-3.113-2.104-0.92-0.925-1.629-1.97-2.13-3.135-0.487-1.178-0.738-2.391-0.738-3.653 0-1.276 0.251-2.497 0.738-3.662 0.501-1.178 1.211-2.235 2.13-3.175 1.824-1.876 4.077-2.818 6.751-2.818z"/>
</svg>
  </a>
  <br />
  To the extent possible under law,
  <a rel="dct:publisher"
     href="https://jacobhall.net">
    <span property="dct:title">Jacob Hall</span></a>
  has waived all copyright and related or neighboring rights to his work on
  <span property="dct:title">jacobhall.net</span>. Content adapted from other sources may be © its creators as noted.
<br>
This work is published from:
<span property="vcard:Country" datatype="dct:ISO3166"
      content="US" about="https://jacobhall.net">
  United States</span>.
</p>
</div>
</footer>
</body>
</html>
