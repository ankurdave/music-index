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

	<title><?=htmlentities($title)?></title>
</head>

<body>
<div id="container">

<h1><?=htmlentities($title)?></h1>

<div id="search">
<h2>Search</h2>
<form name="search" action="<?=$Config['ScriptRelDir']?>/search" method="GET">
<input type="text" name="q" value="<?=htmlentities($_GET['q'])?>" />
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
<form name="stream" action="<?=$Config['ScriptRelDir']?>/stream" method="GET">
<p><input type="submit" value="Stream selected" /></p>
<table class="songs">
	<tr><th></th><th>Title</th><th>Album</th><th>Artist</th><th>Genre</th><th>Year</th><th>Length</th><th>Bitrate</th></tr>
<?php
	foreach ($files as $elem) {
		$elemAbs = "$absDir/$elem";
		$elemReq = "$reqDir/$elem";
		
		$info = getSongInfo($elemAbs);
		if (is_null($info['title']) || $info['title'] === '') {
			$info['title'] = basename($info['path']);
		}
?>
		<tr class="file">
			<td><input type="checkbox" name="<?=htmlentities($elemReq)?>" /></td>
			<td><a href="<?=pathurlencode($Config['ScriptRelDir'] . '/download' . $elemReq)?>" title="<?=htmlentities(absToReq($info['path']))?>"><?=htmlentities($info['title'])?></a></td>
			<td><?=htmlentities($info['album'])?></td>
			<td><?=htmlentities($info['artist'])?></td>
			<td><?=htmlentities($info['genre'])?></td>
			<td><?=htmlentities($info['year'])?></td>
			<td><?=htmlentities(sprintf('%u:%02u', floor($info['length'] / 60), $info['length'] % 60))?></td>
			<td><?=htmlentities($info['bitrate'] / 1000)?></td>
		</tr>
<?php
	}
?>
</table>
<p><input type="submit" value="Stream selected" /></p>
</form>
<?php } ?>

</div>
</body>
</html>
