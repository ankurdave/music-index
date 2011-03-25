<?php

include('config.php');


$Config['ScriptRelDir'] = dirname($_SERVER['SCRIPT_NAME']);


// takes two absolute paths and determines if one is a subdirectory of the other
// it doesn't care if it is an immediate child or 10 subdirectories deep...
// use absolute paths for both for best results
function is_child($parent, $child) {
	if(false !== ($parent = realpath($parent))) {
	    $parent = fix_path($parent);
	    if(false !== ($child = realpath($child))) {
	        $child = fix_path($child);
	        if(substr($child, 0, strlen($parent)) == $parent)
	            return true;
	    }
	}
   
	return false;
}

// fixes windows paths...
// (windows accepts forward slashes and backwards slashes, so why does PHP use backwards?
function fix_path($path) {
    return str_replace('\\','/',$path);
}

// Process the requested path into an absolute path
function reqToAbs($req) {
	global $Config;
	
	$absUnprocessed = $Config['MusicDir'] . "/$req";
	$abs = realpath($absUnprocessed);
	
	if ($abs === false || !file_exists($abs)) {
		// The requested path does not exist
		return false;
	}
	
	if (!is_child($Config['MusicDir'], $abs)) {
		// The requested path is outside of the allowed music dir
		return $Config['MusicDir'];
	}
	
	return $abs;
}

// Convert an absolute path into a request one
function absToReq($abs) {
	global $Config;
	
	if (is_child($Config['MusicDir'], $abs)) {
		// Chop off the musicdir part of the string at the beginning
		$req = substr($abs, strlen($Config['MusicDir']));
	} else {
		$req = '/';
	}
	
	return $req;
}

// Serve up a file using chunks
function readfile_chunked($filename) {
	global $Config;
	
	$buffer = '';
	$handle = fopen($filename, 'rb');
	if ($handle === false) {
		return false;
	}
	while (!feof($handle)) {
		$buffer = fread($handle, $Config['DownloadChunkSize']);
		print $buffer;
	}
	return fclose($handle);
}

function okayToDownload($absPath) {
	global $Config;
	
	return is_readable($absPath) && is_child($Config['MusicDir'], $absPath) && in_array(mime_content_type($absPath), $Config['Types']) && extensionOkay($absPath);
}

function extensionOkay($absPath) {
    global $Config;
    foreach ($Config['Extensions'] as $ext) {
	if (strripos($absPath, $ext) == strlen($absPath) - strlen($ext))
	    return true;
    }
    return false;
}

function safeFilename($str) {
	$str = str_replace(' ', '_', $str);
	return preg_replace('/[^a-zA-Z0-9_.-]/', '', $str);
}

function connectToDB() {
	global $Config;
	return new mysqli($Config['DB']['Host'], $Config['DB']['User'], $Config['DB']['Password'], $Config['DB']['Database']);
}

// Returns whether or not the given song exists in the DB.
function songExistsInDB($absPath) {
	static $db = null;
	if ($db == null) {
		$db = connectToDB();
		fwrite(STDERR, "Connecting to DB.\n");
	}
	
	static $query = null;
	if ($query == null) {
		$query = $db->prepare('SELECT path FROM song WHERE path=?');
		fwrite(STDERR, "Preparing query.\n");
	}
	
	$query->bind_param('s', $absPath);
	$query->execute();
	$query->store_result();
	
	$numRows = $query->num_rows;
	
	$query->free_result();
	
	return ($numRows > 0);
}

// Returns the ID3 information of a song, using the database as a cache
function getSongInfo($absPath) {
	static $db = null;
	if ($db == null) $db = connectToDB();
	
	// If song already exists in DB, get it from there
	static $selectQuery = null;
	if ($selectQuery == null) $selectQuery = $db->prepare('SELECT path, title, album, artist, genre, bitrate, filesize, year, length FROM song WHERE path=?');
	$selectQuery->bind_param('s', $absPath);
	$selectQuery->execute();
	$selectQuery->store_result();
	if ($selectQuery->num_rows > 0) {
		$selectQuery->bind_result($path, $title, $album, $artist, $genre, $bitrate, $filesize, $year, $length);
		$selectQuery->fetch();
		$selectQuery->free_result();
		
		return array(
			'path' => $path,
			'title' => $title, 
			'album' => $album,
			'artist' => $artist,
			'genre' => $genre,
			'bitrate' => $bitrate,
			'filesize' => $filesize,
			'year' => $year,
			'length' => $length,
		);
	} else {
		$selectQuery->free_result();
	}

	// If not, get the metadata from the file and store in in the DB
	require_once('/usr/share/php-getid3/getid3.php');

	$getID3 = new getID3;
	if (!is_file($absPath)) {
		return false;
	}

	$infoObj = $getID3->analyze($absPath);
	if (!$infoObj) {
		return false;
	}
	getid3_lib::CopyTagsToComments($infoObj);

	$info = array(
		'title' => commaJoin($infoObj['comments']['title']),
		'album' => commaJoin($infoObj['comments']['album']),
		'artist' => commaJoin($infoObj['comments']['artist']),
		'genre' => commaJoin($infoObj['comments']['genre']),
		'bitrate' => orNull($infoObj['bitrate']),
		'filesize' => orNull($infoObj['filesize']),
		'year' => mean($infoObj['comments']['year']),
		'length' => orNull($infoObj['playtime_seconds']),
	);

	// Store the metadata in the DB
	static $query = null;
	if ($query == null) $query = $db->prepare('INSERT INTO song (path, title, album, artist, genre, bitrate, filesize, year, length) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
	$query->bind_param('sssssiiid', $absPath, $info['title'], $info['album'], $info['artist'], $info['genre'], $info['bitrate'], $info['filesize'], $info['year'], $info['length']);
	$query->execute();
	
	print "(fresh) "; // TODO: remove if not debugging
	return $info;
}

function findKeyInArray($needle, $haystack) {
	$results = array();
	foreach ($haystack as $key => $val) {
		if ($key === $needle) {
			if (is_array($val)) {
				$results = array_merge($results, $val);
			} else {
				array_push($results, $val);
			}
		} else if (is_array($val)) {
			$result = findKeyInArray($needle, $val);
			$results = array_merge($results, $result);
		}
	}
	
	return $results;
}

function findLongestStringInArray($array) {
	$maxLength = 0;
	$longest = null;
	foreach ($array as $val) {
		$length = strlen($val);
		if ($length > $maxLength) {
			$longest = $val;
			$maxLength = $length;
		}
	}
	
	return $longest;
}

function mean($array) {
	if (count($array) == 0)
		return NULL;
	else
		return array_sum($array) / count($array);
}

function commaJoin($stringOrArray) {
    if (empty($stringOrArray))
	return NULL;

    if (is_array($stringOrArray))
	return join(', ', $stringOrArray);
    
    return $stringOrArray;
}

function orNull($val) {
    if (empty($val))
	return NULL;
    else
	return $val;
}

function pathurlencode($path) {
	return implode("/", array_map("rawurlencode", explode("/", $path)));
}

// Parses query strings in the standard way, without the [] for arrays and without replacing stuff with _
// Based on http://www.php.net/manual/en/function.parse-str.php#76792
function parseQueryString($str) {
  # result array
  $arr = array();

  # split on outer delimiter
  $pairs = explode('&', $str);

  # loop through each pair
  foreach ($pairs as $i) {
    # split into name and value
    list($name,$value) = array_map('urldecode', explode('=', $i, 2));
   
    # if name already exists
    if( isset($arr[$name]) ) {
      # stick multiple values into an array
      if( is_array($arr[$name]) ) {
        $arr[$name][] = $value;
      }
      else {
        $arr[$name] = array($arr[$name], $value);
      }
    }
    # otherwise, simply stick it in a scalar
    else {
      $arr[$name] = $value;
    }
  }

  # return result array
  return $arr;
}

function songsHeader() {
?>
<form name="stream" action="<?=$Config['ScriptRelDir']?>/stream" method="GET">
<p><input type="submit" value="Stream selected" /></p>
<table class="songs">
	<tr><th></th><th>Title</th><th>Album</th><th>Artist</th><th>Genre</th><th>Year</th><th>Length</th><th>Bitrate</th></tr>
<?php
}

function listSong($title, $album, $artist, $genre, $year, $length, $bitrate) {
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

function songsFooter() {
?>
</table>
<p><input type="submit" value="Stream selected" /></p>
</form>
<?php
}

?>
