<?php
include('lib.php');

if ($argc < 1)
    exit;

$file = $argv[1];

if (!okayToDownload($file)) {
    exit;
}

// Check if it already exists in the DB; if so, do nothing
if (songExistsInDB($file)) {
    echo $file, "\n";
    exit;
}

// Otherwise, get its info and store it
$info = getSongInfo($file);

echo "$info[artist] / $info[album] / $info[title] ";
foreach ($info as $k => $v) {
    if ($v === NULL)
	echo "[$k]";
}
echo "\n";

?>
