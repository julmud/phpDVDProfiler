<?php

defined('IN_SCRIPT') || define('IN_SCRIPT', 1);
include_once('global.php');

$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='0.0' WHERE property='db_schema_version'");

if ($inbrowser) {
	echo <<<EOT
<html>
<body>
<center>Your database schema has been invalidated - you need to run an update now<br>
<br>
<a style="text-decoration:underline; color:blue" href="index.php" target="_top">$lang[IMPORTCLICK]</a></center></html>
EOT;
}
else {
	echo "Your database schema has been invalidated - you need to run an update now\n";
}
