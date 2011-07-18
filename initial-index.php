<?php
include('lib.php');

$dir = $Config['MusicDir'];
if ($argc > 1) {
	$dir = $argv[1];
}

$it = new RecursiveDirectoryIterator($dir);
$filter = new FilenameFilter($it, '/\.mp3$/i');

echo "Beginning scan of $dir ...\n\n";

foreach (new RecursiveIteratorIterator($filter) as $file) {
	if (!okayToDownload($file->getPathname())) {
		echo "Skipping ", $file->getPathname(), "\n";
		continue;
	}
	
	// Check if it already exists in the DB; if so, do nothing
	if (songExistsInDB($file->getPathname())) {
		echo $file->getPathname(), "\n";
		continue;
	}

	// Otherwise, get its info and store it
	$info = getSongInfo($file->getPathname());

	echo "$info[artist] / $info[album] / $info[title] ";
	foreach ($info as $k => $v) {
	    if ($v === NULL)
		echo "[$k]";
	}
	echo "\n";
}

echo "\nDone scanning $dir.\n";

?>
