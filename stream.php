<?php
	include('lib.php');
	
	$files = parseQueryString($_SERVER['QUERY_STRING']);
	
	header('Content-Type: audio/x-mpegurl');
	header('Content-Disposition: filename = "playlist.m3u"');

	foreach ($files as $key => $val) {
		echo 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . pathurlencode($Config['ScriptRelDir'] . $key), "\n";
	}
?>
