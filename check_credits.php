<?php

define('IN_SCRIPT', 1);
include_once('global.php');
SendNoCacheHeaders('Content-Type: text/html; charset="windows-1252";');

	$check1 = "Checking for truncated 'uncredited' indicators [searching roles for '(u']";
	$check2 = "Checking for truncated 'voice' indicators [searching roles for '(v']";
	if ($inbrowser) {
		$fmt = "<tr><td>%s</td><td>%s</td><td>%s</td></tr>";
		echo "<center><table border=1><th colspan=3><bold>$check1</bold></th>\n";
	}
	else {
		$fmt = "%s || %s || %s\n";
		echo "$check1\n";
	}

	$sql = "SELECT title,description,fullname,role FROM $DVD_TABLE d, $DVD_COMMON_ACTOR_TABLE ca, $DVD_ACTOR_TABLE a WHERE a.id=d.id AND ca.caid=a.caid AND role LIKE '%(u%' ORDER BY sorttitle";
	$res = $db->sql_query($sql) or die($db->sql_error());

	while ($row = $db->sql_fetchrow($res)) {
		if ($row['description'] != '') $row['title'] = "$row[title] ($row[description])";
		printf($fmt, $row['title'], $row['fullname'], $row['role']);
	}
	$db->sql_freeresult($res);
	if ($inbrowser) {
		echo "</table></center><br><br><center><table border=1><th colspan=3><bold>$check2</bold></th>\n";
	}
	else {
		echo "\n\n$check2\n";
	}

	$sql = "SELECT title,description,fullname,role FROM $DVD_TABLE d, $DVD_COMMON_ACTOR_TABLE ca, $DVD_ACTOR_TABLE a WHERE a.id=d.id AND ca.caid=a.caid AND role LIKE '%(v%' ORDER BY sorttitle";
	$res = $db->sql_query($sql) or die($db->sql_error());

	while ($row = $db->sql_fetchrow($res)) {
		if ($row['description'] != '') $row['title'] = "$row[title] ($row[description])";
		printf($fmt, $row['title'], $row['fullname'], $row['role']);
	}
	$db->sql_freeresult($res);
	echo "\n";

	if ($inbrowser) {
		echo "</table></center><br>\n";
	}
?>
