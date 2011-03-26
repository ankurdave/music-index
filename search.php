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
<input type="search" name="q" value="<?=htmlentities($_GET['q'])?>" />
<input type="checkbox" name="boolean" id="boolean" <?php if ($boolean) { ?>checked="checked"<?php } ?> /> <label for="boolean">Boolean</label>
<input type="submit" value="Search" />
</form>
</div>

<?php
songsHeader();
while ($query->fetch()) {
    if (is_null($title) || $title === '') {
	$title = basename($path);
    }
    listSong($path, $title, $album, $artist, $genre, $year, $length, $bitrate);
}
$query->free_result();
$query->close();
songsFooter();
?>

</div>
</body>
</html>
