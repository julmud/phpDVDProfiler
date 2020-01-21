<?php

error_reporting(E_ALL);
define('IN_SCRIPT', 1);
include_once('global.php');

function GetThumbFromId($id) {
global $getimages, $img_webpathf, $thumbnails;

	if ($getimages > 0) {
		if ($getimages == 3) {
			$thumbs = "<img alt=\"\" width=60 height=84 src=\"{$img_webpathf}$thumbnails/{$id}f.jpg\">";
		}
		else {
			$thumbs = '<img alt="" width=60 height=84 src="' . resize_jpg($id, 'f', 60, 100) . '">';
		}
	}
	return($thumbs);
}

	$pre = '';
//	$pre .= '<pre>$_GET = '.print_r($_GET, true)."\n\$_POST = ".print_r($_POST, true).'</pre><br>';
	if (count($_POST) != 0) {
		$str = $_SERVER['REMOTE_ADDR'] . ' - ' . date("Y/m/d-H:i:s") . ' => ';
		foreach ($_POST as $key => $val) {
			switch ($key) {
			case 'actors':
			case 'roles':
			case 'credits':
			case 'studios':
			case 'title':
			case 'featureother':
			case 'overview':
			case 'eastereggs':
			case 'notes':
				if ($val != '')
					$str .= "$key=>$val< ";
				break;
			default:
				if ($val != '?')
					$str .= "$key=>$val< ";
				break;
			}
		}
		//DebugLog($str);
	}
	$InMenu = '0';
	if (isset($_GET['InMenu']))
		$InMenu = $_GET['InMenu'];
	$limit = 5;
	if (isset($_POST['limit']))
		$limit = $db->sql_escape($_POST['limit']);

	$where = '';
	$needtable = array('actor' => false, 'cactor' => false, 'credit' => false, 'ccredit' => false, 'genre' => false, 'studio' => false);
	$numgenretables = 0;

	foreach ($_POST as $key => $value) {
		if (substr($key, 0, 6) == 'genre_') {
			switch ($value) {
			case '?':
				break;
			case 1:
				$needtable['genre'] = true;
				$where .= " AND g$numgenretables.genre='".str_replace('_', ' ', $db->sql_escape(substr($key, 6)))."'";
				$numgenretables++;
				break;
			case -1:
				// for the use we are making, sub-queries are *much* slower
				$result = $db->sql_query("SELECT DISTINCT id FROM $DVD_GENRES_TABLE WHERE genre='".str_replace('_', ' ', $db->sql_escape(substr($key, 6)))."'");
				$tmp = '(';
				while ($zzz = $db->sql_fetch_array($result)) {
					if ($tmp != '(') $tmp .= ',';
					$tmp .= "'$zzz[id]'";
				}
				$db->sql_freeresult($result);
				if ($tmp != '(') {
					$where .= " AND d.id NOT IN $tmp)";
				}
				break;
			}
			continue;
		}
		if (substr($key, 0, 4) == 'tag_') {
			if ($value != '?') {
				$tagname = $db->sql_escape(urldecode(substr($key, 4)));
				$sql = "SELECT DISTINCT id FROM $DVD_TAGS_TABLE WHERE fullyqualifiedname='$tagname'";
				$res = $db->sql_query($sql) or die($db->sql_error());
				if ($db->sql_numrows($res) != 0) {
					$seen = ' AND (';
					while ($val = $db->sql_fetchrow($res)) {
						if ($value == '1')
							$seen .= " d.id='$val[id]' OR";
						else
							$seen .= " d.id!='$val[id]' AND";
					}
					$seen = str_replace('AND)', ')', str_replace('OR)', ')', $seen.')'));
					$where .= $seen;
				}
			}
			continue;
		}
		if (substr($key, 0, 8) == 'watched_') {
			if ($value != '?') {
				$uid = $db->sql_escape(urldecode(substr($key, 8)));
				$sql = "SELECT DISTINCT id FROM $DVD_EVENTS_TABLE WHERE uid=$uid AND eventtype='watched'";
				$res = $db->sql_query($sql) or die($db->sql_error());
				if ($db->sql_numrows($res) != 0) {
					$seen = ' AND (';
					while ($val = $db->sql_fetchrow($res)) {
						if ($value == '1')
							$seen .= " d.id='$val[id]' OR";
						else
							$seen .= " d.id!='$val[id]' AND";
					}
					$seen = str_replace('AND)', ')', str_replace('OR)', ')', $seen.')'));
					$where .= $seen;
				}
			}
			continue;
		}
		switch ($key) {
		case 'limit':
			break;
		case 'boxtvparent':
			switch ($value) {
			case '?':
				break;
			case 1:
				$needtable['genre'] = true;
				$where .= " AND boxparent!='' AND g$numgenretables.genre='Television'";
				$numgenretables++;
				break;
			case -1:
				$needtable['genre'] = true;
				$where .= " AND NOT (boxparent!='' AND g$numgenretables.genre='Television')";
				$numgenretables++;
				break;
			}
			break;
		case 'boxparent':
			switch ($value) {
			case '?':
				break;
			case 1:
				$where .= " AND boxparent!=''";
				break;
			case -1:
				$where .= " AND boxparent=''";
				break;
			}
			break;
		case 'boxchild':
			switch ($value) {
			case '?':
				break;
			case 1:
				$where .= " AND boxchild!='0'";
				break;
			case -1:
				$where .= " AND boxchild='0'";
				break;
			}
			break;
		case 'region':
			if ($value != '?')
				$where .= " AND LOCATE('".$db->sql_escape($value)."',region)>0";
			break;
		case 'rating':
			if ($value != '?') {
				list($loc, $rate) = explode('.', $value);
				$where .= " AND IF (LOCATE('.',d.id) = '0',0,SUBSTRING(d.id,LOCATE('.',d.id)+1,LENGTH(d.id)-LOCATE('.',d.id)))+0 = '".$db->sql_escape($loc)."' AND rating='".$db->sql_escape($rate)."'";
			}
			break;
		case 'rtimelower':
			if ($value != '?')
				$where .= " AND d.runningtime>=" . $db->sql_escape($value);
			break;
		case 'rtimehigher':
			if ($value != '?')
				$where .= " AND d.runningtime<=" . $db->sql_escape($value);
			break;
		case 'actors':
		case 'roles':
		case 'credits':
		case 'studios':
		case 'title':
		case 'featureother':
		case 'overview':
		case 'eastereggs':
		case 'notes':
			if (substr($value, 0, 1) == '^')
				$theval = substr($value, 1);
			else
				$theval = $value;
			preg_match_all('/(?(?=")"[^"]*"|[^ ]*)/', $theval, $matches);
			$lookfor = '';
			foreach ($matches[0] as $kkk => $vvv) {
				if ($vvv != '') {
					$lookfor .= '%' . trim($vvv, '"');
				}
			}
			if (substr($value, 0, 1) == '^')
				$lookfor = substr($lookfor, 1);
			$lookfor = $db->sql_escape($lookfor.'%');
			if ($lookfor != '%' && $lookfor != '') {
				switch ($key) {
				case 'actors':
					$needtable['actor'] = true;
					$needtable['cactor'] = true;
					$where .= " AND a.caid>0 AND (b.fullname LIKE '$lookfor' OR a.creditedas LIKE '$lookfor')";
					break;
				case 'roles':
					$needtable['actor'] = true;
					$where .= " AND a.caid>0 AND role LIKE '$lookfor'";
					break;
				case 'credits':
					$needtable['credit'] = true;
					$needtable['ccredit'] = true;
					$where .= " AND c.caid>0 AND (e.fullname LIKE '$lookfor' OR c.creditedas LIKE '$lookfor')";
					break;
				case 'studios':
					$needtable['studio'] = true;
					$where .= " AND studio LIKE '$lookfor'";
					break;
				case 'title':
					$where .= " AND (title LIKE '$lookfor' OR originaltitle LIKE '$lookfor' OR description LIKE '$lookfor')";
					break;
				default:
					$where .= " AND $key LIKE '$lookfor'";
					break;
				}
			}
			break;
		case 'mediatypedvd':
			switch ($value) {
			case '?':
				break;
			case 1:
				$where .= " AND (builtinmediatype=".MEDIA_TYPE_DVD." OR builtinmediatype=".MEDIA_TYPE_HDDVD_DVD." OR builtinmediatype=".MEDIA_TYPE_BLURAY_DVD.")";
				break;
			case -1:
//				$where .= " AND (builtinmediatype!=".MEDIA_TYPE_DVD." AND builtinmediatype!=".MEDIA_TYPE_HDDVD_DVD." AND builtinmediatype!=".MEDIA_TYPE_BLURAY_DVD.")";
				$where .= " AND (builtinmediatype!=".MEDIA_TYPE_DVD.")";
				break;
			}
			break;
		case 'mediatypehddvd':
			switch ($value) {
			case '?':
				break;
			case 1:
				$where .= " AND (builtinmediatype=".MEDIA_TYPE_HDDVD." OR builtinmediatype=".MEDIA_TYPE_HDDVD_DVD.")";
				break;
			case -1:
//				$where .= " AND (builtinmediatype!=".MEDIA_TYPE_HDDVD." AND builtinmediatype!=".MEDIA_TYPE_HDDVD_DVD.")";
				$where .= " AND (builtinmediatype!=".MEDIA_TYPE_HDDVD.")";
				break;
			}
			break;
		case 'mediatypebluray':
			switch ($value) {
			case '?':
				break;
			case 1:
				$where .= " AND (builtinmediatype=".MEDIA_TYPE_BLURAY." OR builtinmediatype=".MEDIA_TYPE_BLURAY_DVD.")";
				break;
			case -1:
//				$where .= " AND (builtinmediatype!=".MEDIA_TYPE_BLURAY." AND builtinmediatype!=".MEDIA_TYPE_BLURAY_DVD.")";
				$where .= " AND (builtinmediatype!=".MEDIA_TYPE_BLURAY.")";
				break;
			}
			break;
		case 'collectiontype':
			switch ($value) {
			case '?':
				break;
			case 'owned':
			case 'ordered':
			case 'wishlist':
				$where .= " AND $key='$value'";
				break;
			case 'loaned':
				$where .= " AND loaninfo!=''";
				break;
			default:
				$where .= " AND auxcolltype LIKE '%/".addslashes($masterauxcolltype[$value])."/%'";
				break;
			}
			break;
		default:
			switch ($value) {
			case '?':
				break;
			case 1:
				$where .= " AND ".$db->sql_escape($key)."=1";
				break;
			case -1:
				$where .= " AND ".$db->sql_escape($key)."=0";
				break;
			default:
				$where .= " AND ".$db->sql_escape($key)."='".$db->sql_escape($value)."'";
				break;
			}
		}
	}

	$MainQuery = "SELECT DISTINCT title,d.id FROM $DVD_TABLE d";
	if ($needtable['actor'])
		$MainQuery .= ",$DVD_ACTOR_TABLE a";
	if ($needtable['cactor'])
		$MainQuery .= ",$DVD_COMMON_ACTOR_TABLE b";
	if ($needtable['credit'])
		$MainQuery .= ",$DVD_CREDITS_TABLE c";
	if ($needtable['ccredit'])
		$MainQuery .= ",$DVD_COMMON_CREDITS_TABLE e";
	if ($needtable['genre']) {
		for ($zzz=0; $zzz<$numgenretables; $zzz++) {
			$MainQuery .= ",$DVD_GENRES_TABLE g$zzz";
		}
	}
	if ($needtable['studio'])
		$MainQuery .= ",$DVD_STUDIO_TABLE s";
	$MainQuery .= " WHERE";
	if ($needtable['actor'])
		$MainQuery .= " AND d.id=a.id";
	if ($needtable['cactor'])
		$MainQuery .= " AND b.caid=a.caid";
	if ($needtable['credit'])
		$MainQuery .= " AND d.id=c.id";
	if ($needtable['ccredit'])
		$MainQuery .= " AND c.caid=e.caid";
	if ($needtable['genre']) {
		for ($zzz=0; $zzz<$numgenretables; $zzz++) {
			$MainQuery .= " AND d.id=g$zzz.id";
		}
	}
	if ($needtable['studio'])
		$MainQuery .= " AND d.id=s.id";
	$MainQuery .= "$where ORDER BY RAND() LIMIT $limit";
	$MainQuery = str_replace('WHERE ORDER', 'ORDER', $MainQuery);
	$MainQuery = str_replace('WHERE AND', 'WHERE', $MainQuery);

	$t0 = microtime_float();
	$res = $db->sql_query($MainQuery) or die($db->sql_error());
	$querytime = number_format(microtime_float() - $t0, 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']);
	$count = 1;
	$results = '<table><tr>';
	while ($row = $db->sql_fetchrow($res)) {
		if (($count++ % 5) == 1)
			$results .= '</tr><tr>';
		$thumbs = GetThumbFromId($row['id']);
		if ($InMenu == '0')
			$results .= "<td valign=top align=center width=\"20%\"><a href='index.php?lastmedia=$row[id]' target='phpdvd'>$thumbs<br>$row[title]</a></td>";
		else
			$results .= "<td valign=top align=center width=\"20%\"><a href='index.php?mediaid=$row[id]&amp;action=show' target='entry'>$thumbs<br>$row[title]</a></td>";
	}
	$db->sql_freeresult($res);
	if ($results == '<table><tr>')
		$results = '<br><h1>No profiles matched search criteria.</h1>';
	else
		$results .= '</tr></table>';

	if (!$ShowSQLInPicker)
		$MainQuery = '';

	SendNoCacheHeaders('Content-Type: text/html; charset="windows-1252";');

	echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html><head>
<link rel="stylesheet" type="text/css" href="format.css.php">
</head>
<body class=f6>
$results<br>$pre$MainQuery
<br>$lang[CHOOSEREXECUTION]: $querytime
</body>
</html>
EOT;
?>
