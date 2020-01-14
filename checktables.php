<?php

define('IN_SCRIPT', 1);
include_once('global.php');
SendNoCacheHeaders('Content-Type: text/html; charset="windows-1252";');

function DisplayAResultSet(&$db, $sql) {
	$result = $db->sql_query($sql);
	$firstrow = true;
	while ($row = $db->sql_fetchrow($result)) {
		if ($firstrow) {
			$firstrow = false;
			echo "<table border=1><tr>\n";
			foreach ($row as $key => $value) {
				echo "<th>$key</th>";
			}
			echo "</tr>\n";
		}
		echo "<tr>";
		foreach ($row as $key => $value) {
			echo "<td>$value</td>\n";
		}
		echo "</tr>\n";
	}
	echo "</table>\n";
	$db->sql_freeresult($result);
	unset($row);
}

	$table = @$_GET['table'];
	if ($table{0} == '$')
		$table = $GLOBALS[substr($table, 1)];
	if ($table == '')
		$table = $DVD_STATS_TABLE;

	$request = "SELECT * FROM ".$db->sql_escape($table);
	echo "<html><head><title>Dump of table $table</title></head><body>\n";
	DisplayAResultSet($db, $request);
	echo "</body></html>\n";
?>
