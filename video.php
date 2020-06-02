<!DOCTYPE html>
<html lang="en">
<head>
		<title>watch a video - jacobhall.net</title>
		<meta charset="utf-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="theme-color" content="#FFFFFF"/>
		<meta name="Description" content="watch videos!">

	<!-- unpkg : use the latest version of Video.js -->
	<link href="https://unpkg.com/video.js/dist/video-js.min.css" rel="stylesheet">
	<script src="https://unpkg.com/video.js/dist/video.min.js"></script>
</head>
<body>
<video
    id="my-player"
    class="video-js"
    controls
    preload="auto"
    data-setup='{}'>
  <source src="https://elasticbeanstalk-us-west-2-264830222555.s3-us-west-2.amazonaws.com/<?php echo $_GET["url"]; ?>" type="video/mp4">
  <p class="vjs-no-js">
    To view this video please enable JavaScript, and consider upgrading to a
    web browser that
    <a href="https://videojs.com/html5-video-support/" target="_blank">
      supports HTML5 video
    </a>
  </p>
</video>
</body>
</html>
