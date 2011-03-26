<?php
	include('lib.php');
	
	// Read the dir
	$absDir = reqToAbs($_SERVER['PATH_INFO']);
	$reqDir = absToReq($absDir);
	
	if ($absDir === false) {
		// The requested file doesn't exist
		header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");
		exit();
	}
	
	// If it's actually a file, point to the file showing page
	if (is_file($absDir)) {
		header('Location: ' . $Config['ScriptRelDir'] . '/download' . pathurlencode($reqDir));
		exit();
	}
	
	$contents = array_diff(scandir($absDir), array('.', '..'));
	$dirs = array();
	$files = array();
	foreach ($contents as $elem) {
		if (is_dir("$absDir/$elem")) {
			array_push($dirs, $elem);
		} else if (okayToDownload("$absDir/$elem")) {
			array_push($files, $elem);
		}
	}
	
	$title = 'Music in ' . (empty($reqDir) ? '/' : $reqDir);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<link rel="stylesheet" href="<?=$Config['ScriptRelDir']?>/style.css">
    
    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; minimum-scale=1.0; user-scalable=no">
    <link type="text/css" rel="stylesheet" href="<?=$Config['ScriptRelDir']?>/skin/jplayer.blue.monday.css">
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
	<script type="text/javascript" src="<?=$Config['ScriptRelDir']?>/jquery.jplayer.min.js"></script>
	<script type="text/javascript" src="<?=$Config['ScriptRelDir']?>/followalong.js"></script>
    <script type="text/javascript" src="<?=$Config['ScriptRelDir']?>/player.js"></script>

	<title><?=htmlentities($title)?></title>
</head>

<body>
<?php include "player.inc.php" ?>

<div id="container">

<h1><?=htmlentities($title)?></h1>

<div id="search">
<h2>Search</h2>
<form name="search" action="<?=$Config['ScriptRelDir']?>/search" method="GET">
<input name="q" type="search" value="<?=htmlentities($_GET['q'])?>">
<input type="checkbox" name="boolean" id="boolean" /> <label for="boolean">Boolean</label>
<input type="submit" value="Search" />
</form>
</div>

<h2>Folders</h2>
<table>
	<tr class="dir special"><td><a href="<?=pathurlencode($Config['ScriptRelDir'] . $reqDir . '/..')?>">Parent folder</a></td></tr>
<?php
	foreach ($dirs as $elem) {
		$elemAbs = "$absDir/$elem";
		$elemReq = "$reqDir/$elem";
?>
		<tr class="dir"><td><a href="<?=pathurlencode($Config['ScriptRelDir'] . $elemReq)?>"><?=htmlentities($elem)?></a></td></tr>
<?php
	}
?>
</table>

<?php if (!empty($files)) { ?>
<h2>Songs</h2>
<?php
songsHeader();
foreach ($files as $elem) {
    $elemAbs = "$absDir/$elem";
    $elemReq = "$reqDir/$elem";
    
    $info = getSongInfo($elemAbs);
    if (is_null($info['title']) || $info['title'] === '') {
	$info['title'] = basename($info['path']);
    }

    listSong($info['path'], $info['title'], $info['album'], $info['artist'], $info['genre'], $info['year'], $info['length'], $info['bitrate']);
}
songsFooter();
}
?>

</div>
</body>
</html>
