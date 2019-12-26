<?php
/*	$Id: incupdate.php,v 2.00 2004/08/25 14:21:00 fred Exp $	*/

error_reporting(E_ALL);
define('IN_SCRIPT', 1);
include_once('global.php');

function CheckCommonTable($t1, $t2) {
global $db;
	$sql = "CREATE TEMPORARY TABLE TEMP_COMMON (caid int unique NOT NULL, count int unsigned NOT NULL default 0) TYPE=MyISAM;";
	$db->sql_query($sql) or die($db->sql_error());

	$sql = "INSERT INTO TEMP_COMMON SELECT caid,COUNT(caid) AS count FROM $t1 WHERE caid>0 GROUP BY caid";
	$db->sql_query($sql) or die($db->sql_error());

	$sql = "INSERT IGNORE INTO TEMP_COMMON SELECT caid,0 FROM $t2 WHERE caid>0";
	$db->sql_query($sql) or die($db->sql_error());

	$sql = "SELECT c.*,a.* FROM TEMP_COMMON c,$t2 a WHERE a.caid=c.caid AND count=0";
	$res = $db->sql_query($sql) or die($db->sql_error());
	while ($row = $db->sql_fetchrow($res)) {
		echo "Deleting #$row[caid]: $row[fullname] ($row[firstname]/$row[middlename]/$row[lastname])\n";
		$sql = "DELETE FROM $t2 WHERE caid=$row[caid]";
		$db->sql_query($sql) or die($db->sql_error());
	}
	$db->sql_freeresult($res);
	$db->sql_query("DROP TEMPORARY TABLE TEMP_COMMON") or die($db->sql_error());
}

	echo "Checking Cast\n";
	CheckCommonTable($DVD_ACTOR_TABLE, $DVD_COMMON_ACTOR_TABLE);
	echo "Checking Crew\n";
	CheckCommonTable($DVD_CREDITS_TABLE, $DVD_COMMON_CREDITS_TABLE);
?>
