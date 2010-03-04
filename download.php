<?php
	include('lib.php');
	
	$absPath = reqToAbs($_SERVER['PATH_INFO']);
	$reqPath = absToReq($absPath);
	
	if ($absPath === false) {
		// The requested file doesn't exist
		header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");
		exit();
	}
	
	if (!okayToDownload($absPath)) {
		header($_SERVER['SERVER_PROTOCOL'] . " 403 Forbidden");
		exit();
	}
	
	// Use mod_xsendfile (http://tn123.ath.cx/mod_xsendfile/beta/) to send it
	header('X-Sendfile:' . $absPath);
	header('Content-Type: ' . urlencode(mime_content_type($absPath)));
	header('Content-Disposition: attachment; filename=' . safeFilename(basename($absPath)));
?>
