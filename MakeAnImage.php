<?php

error_reporting(E_ALL);
defined('IN_SCRIPT') || define('IN_SCRIPT', 1);

include_once('global.php');

if (!isset($mediaid)) $mediaid = '';
if (!isset($x)) $x = 0;
if (!isset($y)) $y = 0;
if (!isset($side)) $side = 'f';
if ($side != 'b') $side = 'f';
if (substr($mediaid, 0, strlen('Disc ID: ')) == 'Disc ID: ') {
	$mediaid = 'I' . str_replace('-', '', substr($mediaid, strlen('Disc ID: ')));
}
if (($n=strpos($mediaid, '-')) !== false) {
	if ($n > 4)
		$mediaid = str_replace('-', '', $mediaid);
	else
		$mediaid = 'I' . str_replace('-', '', $mediaid);
}
if ($TryToChangeMemoryAndTimeLimits) @ini_set('memory_limit', -1);
SendNoCacheHeaders('Content-Type: image/jpeg');
readfile(resize_jpg($mediaid, $side, $x, 100, $y, 'FFFFFF', false));
exit;
