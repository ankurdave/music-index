<?php
	include('lib.php');
	
	$q = $_GET['q'];
	$boolean = ($_GET['boolean'] == 'on');
	
	$db = connectToDB();
	
	// Query the DB
	if ($boolean) {
		$query = $db->prepare('SELECT path, title, album, artist, genre, bitrate, length, year FROM song WHERE MATCH(path, title, album, artist, genre) AGAINST (? IN BOOLEAN MODE) ORDER BY MATCH(path, title, album, artist, genre) AGAINST (?) DESC LIMIT 100');
		$query->bind_param('ss', $q, $q);
		
		$countQuery = $db->prepare('SELECT COUNT(*) FROM song WHERE MATCH(path, title, album, artist, genre) AGAINST (? IN BOOLEAN MODE)');
		$countQuery->bind_param('s', $q);
		$countQuery->execute();
		$countQuery->store_result();
		$countQuery->bind_result($numRows);
		$countQuery->fetch();
	} else {
		$query = $db->prepare('SELECT path, title, album, artist, genre, bitrate, length, year FROM song WHERE MATCH(path, title, album, artist, genre) AGAINST (?) LIMIT 100');
		$query->bind_param('s', $q);
		
		$countQuery = $db->prepare('SELECT COUNT(*) FROM song WHERE MATCH(path, title, album, artist, genre) AGAINST (?)');
		$countQuery->bind_param('s', $q);
		$countQuery->execute();
		$countQuery->store_result();
		$countQuery->bind_result($numRows);
		$countQuery->fetch();
	}

	$query->execute();
	$query->store_result();
	$query->bind_result($path, $title, $album, $artist, $genre, $bitrate, $length, $year);

	$title = $query->num_rows . ($query->num_rows == $numRows ? '' : " of $numRows") . " results for \"$q\"" . ($boolean ? ' in boolean mode' : '');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<link rel="stylesheet" href="style.css">

	<title><?=htmlentities($title)?></title>
</head>

<body>
<div id="container">

<h1><?=htmlentities($title)?></h1>

<div id="search">
<h2>Search</h2>
<form name="search" action="<?=$Config['ScriptRelDir']?>/search" method="GET">
<input type="text" name="q" value="<?=htmlentities($_GET['q'])?>" />
<input type="checkbox" name="boolean" id="boolean" <?php if ($boolean) { ?>checked="checked"<?php } ?> /> <label for="boolean">Boolean</label>
<input type="submit" value="Search" />
</form>
</div>

<form name="stream" action="<?=$Config['ScriptRelDir']?>/stream" method="GET">
<p><input type="submit" value="Stream selected" /></p>
<table class="songs">
	<tr><th></th><th>Title</th><th>Album</th><th>Artist</th><th>Genre</th><th>Year</th><th>Length</th><th>Bitrate</th></tr>

<?php
	while ($query->fetch()) {
		if (is_null($title) || $title === '') {
			$title = basename($path);
		}
?>
		<tr class="file">
			<td><input type="checkbox" name="<?=htmlentities(absToReq($path))?>" /></td>
			<td><a href="<?=pathurlencode($Config['ScriptRelDir'] . '/download' . absToReq($path))?>" title="<?=htmlentities(absToReq($path))?>"><?=htmlentities($title)?></a></td>
			<td><?=htmlentities($album)?></td>
			<td><?=htmlentities($artist)?></td>
			<td><?=htmlentities($genre)?></td>
			<td><?=htmlentities($year)?></td>
			<td><?=htmlentities(sprintf('%u:%02u', floor($length / 60), $length % 60))?></td>
			<td><?=htmlentities($bitrate / 1000)?></td>
		</tr>
<?php
	}
	$query->free_result();
	$query->close();
?>
</table>
<p><input type="submit" value="Stream selected" /></p>
</form>

</div>
</body>
</html>
