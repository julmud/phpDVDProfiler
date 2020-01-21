<?php
/*	$Id$	*/

if (!$inbrowser)
	error_reporting(E_ALL);

function ExitSpecial() {
global $inbrowser;

	if ($inbrowser)
		echo '<div id="phpdvd_ErrorExit"></div>';
	exit;
}

# Function to upgrade the database schema
function schema_update($schema_file) {
global $db, $lang, $inbrowser, $eoln, $table_prefix, $UpdateLast;

	if (($sfh=fopen($schema_file, 'r')) === false) {
		printf($lang['IMPORTMISSINGSCHEMA'].$eoln, $schema_file);
		ExitSpecial();
	}

// This code assumes that comments are only designated by #, and that # doesn't appear in quotes in a line ...
	$dosub = ($table_prefix != 'DVDPROFILER_');
	$cmd = '';
	while ($line=fgets($sfh)) {
		$temp = explode('#', $line);
		if ($temp[0] != '') {
			$tmp = trim($temp[0]);
			$cmd .= $tmp;
			if (substr($tmp, strlen($tmp)-1, 1) == ';') {
				if ($dosub) $cmd = str_replace('DVDPROFILER_', $table_prefix, $cmd);
				$res = $db->sql_query($cmd) or die($db->sql_error());
				if (is_resource($res)) $db->sql_freeresult($res);
				$cmd = '';
			}
		}
	}
	fclose($sfh);
	$UpdateLast = UpdateUpdateLast();	// There is no data in the db, so let everyone know
}

function interpretEscapedXml($subject) {
  $result = preg_replace_callback('/&amp;/', function($matches) {
	  return '&';
	}, $subject);
  return preg_replace_callback('/&#(\d+);/', function($matches) {
	  return chr($matches[1]);
	}, $result);
}

function ProcessLocalitiesCallback($matches) {
global $Locale, $RatingSystem, $RatingCallbackResult;

	if (strncmp($matches[1], 'Locality ', strlen('Locality ')) == 0) {
		preg_match('/ID="([^"]*)"/', $matches[1], $theid);
		$Locale = $theid[1];
	}
	else if (strncmp($matches[1], 'Ratings ', strlen('Ratings ')) == 0) {
		preg_match('/Description="([^"]*)"/', $matches[1], $theid);
		$RatingSystem = interpretEscapedXml($theid[1]);
	}
	else if (strncmp($matches[1], 'Rating ', strlen('Rating ')) == 0) {
		preg_match('/Name="([^"]*)".*Description="([^"]*)"/', $matches[1], $theid);
		$theid[1] = interpretEscapedXml($theid[1]);
		$theid[2] = interpretEscapedXml($theid[2]);
		if ($RatingCallbackResult != '') $RatingCallbackResult .= ',';
		$RatingCallbackResult .= "('Rating~$Locale~$RatingSystem~$theid[1]','$theid[2]')";
	}
	return;
}

function UpdateRatingDescriptions() {
global $db, $RatingCallbackResult, $DVD_PROPERTIES_TABLE;
	$now = @filemtime('localities.xod');
	if ($now === false)
		return;
	$result = $db->sql_query("SELECT value FROM $DVD_PROPERTIES_TABLE WHERE property='Rating~LastLocalitiesUpdateTime'") or die($db->sql_error());
	$lastmtime = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	if ($lastmtime === false || ($lastmtime['value'] < $now)) {
		$data = file_get_contents('localities.xod');
		$RatingCallbackResult = '';
		preg_replace_callback('/<([^>]*)>/U', "ProcessLocalitiesCallback", $data);

		if ($lastmtime !== false) $db->sql_query("DELETE FROM $DVD_PROPERTIES_TABLE WHERE property LIKE 'Rating%'") or die($db->sql_error());
		$db->sql_query("INSERT INTO $DVD_PROPERTIES_TABLE (property,value) VALUES $RatingCallbackResult") or die($db->sql_error());
		$db->sql_query("INSERT INTO $DVD_PROPERTIES_TABLE (property,value) VALUES ('Rating~LastLocalitiesUpdateTime',$now)") or die($db->sql_error());
	}
	return;
}

function CheckForCompleteXML($data) {
	$retval = '';
	$Sections['Locks'] = isset($data['LOCKS'][0]);
	$Sections['Cast'] = isset($data['ACTORS'][0]);
	$Sections['Crew'] = isset($data['CREDITS'][0]);
	$Sections['Overview'] = isset($data['OVERVIEW'][0]);
	$Sections['Notes'] = isset($data['NOTES'][0]);
	$Sections['Tags'] = isset($data['TAGS'][0]);
	$Sections['Easter Eggs'] = isset($data['EASTEREGGS'][0]);
	foreach ($Sections as $key => $val)
		if (!$val)
			$retval .= "$key ";
	return($retval);
}

function DoSomeStats($NAME, $NeedDistinct, $WHATWHEREFROM, $WHERE, $GROUPORDER, $noadulttitles, &$ProfileName, &$Profile, &$numtimings, &$t0) {
global $db, $TryToChangeMemoryAndTimeLimits, $DVD_STATS_TABLE, $IgnoreCount0Profiles;

	$Distinct = '';
	if ($NeedDistinct) $Distinct = 'DISTINCT';
	if ($IgnoreCount0Profiles) $WHERE .= 'AND countas!=0 ';
	$sql = "INSERT INTO $DVD_STATS_TABLE SELECT $Distinct '{$NAME}Adult',$WHATWHEREFROM $WHERE $GROUPORDER";
	if ($TryToChangeMemoryAndTimeLimits) set_time_limit(0);
	$db->sql_query($sql) or die($db->sql_error());
	$ProfileName[$numtimings] = $NAME.'Adult'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

	if ($noadulttitles)
		$sql = "INSERT INTO $DVD_STATS_TABLE SELECT '{$NAME}NoAdult',namestring1,namestring2,id,counts FROM $DVD_STATS_TABLE WHERE stattype='{$NAME}Adult'";
	else
		$sql = "INSERT INTO $DVD_STATS_TABLE SELECT $Distinct '{$NAME}NoAdult',$WHATWHEREFROM $WHERE AND isadulttitle=0 $GROUPORDER";

	if ($TryToChangeMemoryAndTimeLimits) set_time_limit(0);
	$db->sql_query($sql) or die($db->sql_error());
	$ProfileName[$numtimings] = $NAME.'NoAdult'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();
	if ($noadulttitles) $numtimings--;
	return;
}

function HandleOutOfDateSchema(&$outputbuffer) {
global $lang, $WeCannotContinue, $inbrowser;

	$outputbuffer = '';
	if (!$WeCannotContinue)
		return;
	$schema_file = 'schema.sql';
	if ($inbrowser) {
		$outputbuffer = "$lang[IMPORTBADSCHEMA6]";
		schema_update($schema_file);
		$outputbuffer .= " $lang[IMPORTBADSCHEMA7]";
	}
	else {
		$outputbuffer = html_entity_decode("$lang[IMPORTBADSCHEMA6]\n");
		schema_update($schema_file);
		$outputbuffer .= html_entity_decode("$lang[IMPORTBADSCHEMA7]\n");
	}
}

function TranslateDateTime($string) {
// On entry, $string looks like: 1997-02-24T23:45:52.000Z
// MySQL seems to like: 1997-02-24 23:45:52

	$halves = explode('T', $string);
	if (!isset($halves[1])) $halves[1] = '00:00:00';
	return($halves[0] . ' ' . substr($halves[1], 0, 8));
}

class BufferedInsert {
var $db;
var $max_packet;
var $table;
var $sql;
var $room_left;
var $col_names;

	function __construct(&$db, $max_packet, $table, $col_names) {
		$this->db = $db;
		$this->room_left = $this->max_packet = $max_packet;
		$this->table = $table;
		$this->col_names = $col_names;
		$this->sql = '';
	}

	function add_element($values) {
		$retval = 0;
		$templen = strlen($values);
		if ($this->room_left <= $templen) {
			$this->db->sql_query($this->sql) or die($this->db->sql_error());
			$this->sql = '';
			$this->room_left = $this->max_packet;
			$retval = 1;
		}
		if ($this->sql == '') {
			$this->sql = "INSERT INTO $this->table $this->col_names VALUES $values";
			$this->room_left -= strlen($this->sql);
		}
		else {
			$this->sql .= ',' . $values;
			$this->room_left -= 1 + $templen;
		}
		return($retval);
	}

	function flush() {
		if ($this->sql != '') {
			$this->db->sql_query($this->sql) or die($this->db->sql_error());
			return(1);
		}
		return(0);
	}
}

// For memory stats, $ReportOnMemory=true and $pscommand='ps -p %%pid%% -o%mem= -orss='
// %%pid%% is replaced with the pid of the current process. This command gets the percentage
// of real memory used as well as the actual real size
$pscommand = str_replace('%%pid%%', getmypid(), $pscommand);
$MadeAChange = false;
$max_packet = 1024;	// Initialise global value

// Displays shortened information about an array
// From http://www.devdump.com/phpxml.php
function print_a($obj) {
global $__level_deep;

	if (!isset($__level_deep)) $__level_deep = array();
	if (is_object($obj))
		print '[obj]';
	elseif (is_array($obj)) {
		foreach(array_keys($obj) as $keys) {
			array_push($__level_deep, "[$keys]");
			print_a($obj[$keys]);
			array_pop($__level_deep);
		}
	}
	else
		print implode(' ', $__level_deep)." = $obj\n";
}

// Modified from http://www.devdump.com/phpxml.php
$lineno=1; $addlineno=false;
function GetChildren($vals, &$i) {
global $lineno, $addlineno;

	$children = array();		 // Contains node data

	/* Node has CDATA before it's children */
	if (isset($vals[$i]['value'])) {
		$children['VALUE'] = $vals[$i]['value'];
	}

	/* Loop through children */
	while (++$i < count($vals)) {
		switch ($vals[$i]['type']) {
			/* Node has CDATA after one of it's children
				(Add to cdata found before if this is the case) */
			case 'cdata':
				if (isset($children['VALUE']))
					$children['VALUE'] .= $vals[$i]['value'];
				else
					$children['VALUE'] = $vals[$i]['value'];
				break;
			/* At end of current branch */
			case 'complete':
				if (isset($vals[$i]['attributes'])) {
					$children[$vals[$i]['tag']][]['ATTRIBUTES'] = $vals[$i]['attributes'];
					$index = count($children[$vals[$i]['tag']])-1;
					if ($addlineno) {
						$children[$vals[$i]['tag']][$index]['LINENO'] = $lineno;
						$lineno++;
					}
					if (isset($vals[$i]['value']))
						$children[$vals[$i]['tag']][$index]['VALUE'] = $vals[$i]['value'];
					else
						$children[$vals[$i]['tag']][$index]['VALUE'] = '';
				}
				else {
					if (isset($vals[$i]['value']))
						$children[$vals[$i]['tag']][]['VALUE'] = $vals[$i]['value'];
					else
						$children[$vals[$i]['tag']][]['VALUE'] = '';
					if ($addlineno) {
						$index = count($children[$vals[$i]['tag']])-1;
						$children[$vals[$i]['tag']][$index]['LINENO'] = $lineno;
						$lineno++;
					}
				}
				break;
			/* Node has more children */
			case 'open':
				if (($vals[$i]['tag'] == 'ACTORS') || ($vals[$i]['tag'] == 'CREDITS')) {
					$addlineno = true;
					$lineno = 1;
				}
				if (isset($vals[$i]['attributes'])) {
					$children[$vals[$i]['tag']][]['ATTRIBUTES'] = $vals[$i]['attributes'];
					$index = count($children[$vals[$i]['tag']])-1;
					$children[$vals[$i]['tag']][$index] = array_merge($children[$vals[$i]['tag']][$index],GetChildren($vals, $i));
				}
				else {
					$children[$vals[$i]['tag']][] = GetChildren($vals, $i);
				}
				break;
			/* End of node, return collected data */
			case 'close':
				if (($vals[$i]['tag'] == 'ACTORS') || ($vals[$i]['tag'] == 'CREDITS')) {
					$addlineno = false;
				}
				return $children;
		}
	}
}

function GetXMLTreeFromString($data, $inputencoding='ISO-8859-1') {
global $inbrowser, $lang, $WorkAroundLibxmlBug, $HTMLEntSearch, $HTMLEntReplace;

	$data = '<?xml version="1.0" encoding="'.$inputencoding.'"?>' . "\n" . $data; // for php5
	$parser = xml_parser_create($inputencoding);
	xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'ISO-8859-1');
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, false);
	if ($WorkAroundLibxmlBug) {
		if (!isset($HTMLEntSearch)) {
			$HTMLEntSearch = array('~ampers~', '&amp;', '&lt;', '&gt;', '&quot;', '&apos;');
			$HTMLEntReplace = array('&',       '&',     '<',    '>',    '"',      "'");
		}
		$retval = xml_parse_into_struct($parser, str_replace('&', '~ampers~', $data), $vals, $index);
		if ($retval != 0) {
			$NumValues = count($vals);
			for ($i=0; $i<$NumValues; $i++) {
				if (isset($vals[$i]['value']))
					$vals[$i]['value'] = str_replace($HTMLEntSearch, $HTMLEntReplace, $vals[$i]['value']);
				if (isset($vals[$i]['attributes'])) {
					foreach ($vals[$i]['attributes'] as $key => $attval)
						$vals[$i]['attributes'][$key] = str_replace($HTMLEntSearch, $HTMLEntReplace, $vals[$i]['attributes'][$key]);
				}
			}
		}
	}
	else
		$retval = xml_parse_into_struct($parser, $data, $vals, $index);
	if ($retval == 0) { // value returned is integer not boolean
	global $amt_on_either_side;
		$err = xml_get_error_code($parser);
		$where_exactly = xml_get_current_byte_index($parser) - 1;	// It appears that the byte may be one-based, so subtract 1
		preg_match('|<Title>([^<]*)</Title>|', $data, $matches);
		$title = $matches[1];
		$tag = 'Unknown';
		if (preg_match('|<([^>]*)>|', strrchr(substr($data, 0, $where_exactly-1), '<'), $matches) != 0)
			$tag = $matches[1];
		if ($inbrowser)
			echo "<pre>\n";
		printf($lang['IMPORTBADXML1'], $title, $tag);
		printf($lang['IMPORTBADXML2'], $err, xml_error_string($err));
		printf($lang['IMPORTBADXML3'], $where_exactly, xml_get_current_line_number($parser));
		printf($lang['IMPORTBADXML4'], xml_get_current_column_number($parser), $amt_on_either_side);
		$start = $where_exactly - $amt_on_either_side;
		if ($start < 0) $start = 0;
		echo hexdump(substr($data, $start, $where_exactly-$start), $where_exactly-$start, '     ');
		echo hexdump($data{$where_exactly}, 1, '===> ');
		echo hexdump(substr($data, $where_exactly+1, $amt_on_either_side), $amt_on_either_side, '     ');
		echo $lang['IMPORTBADXML5'];
		if ($inbrowser) {
			print_r(htmlspecialchars($data, ENT_COMPAT, 'ISO-8859-1'));
			echo "</pre>\n\n";
		}
		else
			print_r($data);
		ExitSpecial();
	}
	xml_parser_free($parser);

	$tree = array();
	$i = 0;
	if (isset($vals[$i]['attributes']))
		$tree[$vals[$i]['tag']]['ATTRIBUTES'] = $vals[$i]['attributes'];
	$tree[$vals[$i]['tag']][] = GetChildren($vals, $i);

	unset($vals);
	unset($index);
	return($tree);
}

function UpdateCommonTableFromMemory(&$common_memory, &$common_stats, $table, $hints=false) {
global $db, $max_packet, $lang;

	$t0 = microtime_float();
	$db->sql_query("SET autocommit=0;") or die($db->sql_error());
	$db->sql_transaction('begin') or die($db->sql_error());
	if ($common_memory != '') {
		$col_names = '(caid,firstname,middlename,lastname,birthyear,fullname)';
		$bi = new BufferedInsert($db, $max_packet, $table, $col_names);
		if (!is_array($hints)) {
			foreach ($common_memory as $key => $adata) {
				list($caid, $add) = $adata;
				if ($add == 1) {
					$common_memory[$key] = array($caid, 0);
					$common_stats['numadded']++;

					list($fn, $mn, $ln, $by) = explode('|', $key);
					$toadd =  '('
						.       $caid                                                         . ','
						. "'" . $db->sql_escape($fn)                                          . "',"
						. "'" . $db->sql_escape($mn)                                          . "',"
						. "'" . $db->sql_escape($ln)                                          . "',"
						.       $by                                                           . ','
						. "'" . $db->sql_escape(preg_replace('/\s\s+/', ' ', trim("$fn $mn $ln"))) . "'"
						. ')';

					$common_stats['numinserted'] += $bi->add_element($toadd);
				}
			}
		}
		else {
			foreach ($hints as $key => $whichone) {
				list($caid, $add) = $common_memory[$whichone];
				$common_memory[$whichone] = array($caid, 0);
				$common_stats['numadded']++;

				list($fn, $mn, $ln, $by) = explode('|', $whichone);
				$toadd =  '('
					.       $caid                                                         . ','
					. "'" . $db->sql_escape($fn)                                          . "',"
					. "'" . $db->sql_escape($mn)                                          . "',"
					. "'" . $db->sql_escape($ln)                                          . "',"
					.       $by                                                           . ','
					. "'" . $db->sql_escape(preg_replace('/\s\s+/', ' ', trim("$fn $mn $ln"))) . "'"
					. ')';

				$common_stats['numinserted'] += $bi->add_element($toadd);
			}
		}

		$common_stats['numinserted'] += $bi->flush();
		unset($bi);
		unset($credit);
	}
	$db->sql_transaction('commit') or die($db->sql_error());
	$db->sql_query("SET autocommit=1;") or die($db->sql_error());
	$common_stats['amttime'] += microtime_float() - $t0;
}

function InitialiseCommonTable($which) {
global $db_fast_update, $common_actor, $common_actor_stats, $common_credit, $common_credit_stats, $db;
global $DVD_COMMON_ACTOR_TABLE, $DVD_COMMON_CREDITS_TABLE;

	if ($db_fast_update) {
		if ($which == 'common_actor' && empty($common_actor)) {
			$res = $db->sql_query("SELECT * FROM $DVD_COMMON_ACTOR_TABLE ORDER BY caid") or die($db->sql_error());
			$key = '';
			while ($row = $db->sql_fetch_array($res)) {
				$key = implode('|', array($row['firstname'], $row['middlename'], $row['lastname'], $row['birthyear']));
				$common_actor[$key] = array($row['caid'], 0);
			}
			$db->sql_freeresult($res);
			if ($key != '') {
				if ($common_actor[$key][0] < 0)
					$common_actor_stats['maxid'] = 0;
				else
					$common_actor_stats['maxid'] = intval($common_actor[$key][0]);
			}
		}

		if ($which == 'common_credit' && empty($common_credit)) {
			$res = $db->sql_query("SELECT * FROM $DVD_COMMON_CREDITS_TABLE ORDER BY caid") or die($db->sql_error());
			$key = '';
			while ($row = $db->sql_fetch_array($res)) {
				$key = implode('|', array($row['firstname'], $row['middlename'], $row['lastname'], $row['birthyear']));
				$common_credit[$key] = array($row['caid'], 0);
			}
			$db->sql_freeresult($res);
			if ($key != '') {
				if ($common_credit[$key][0] < 0)
					$common_credit_stats['maxid'] = 0;
				else
					$common_credit_stats['maxid'] = intval($common_credit[$key][0]);
			}
		}
	}
}

function FigureOutBuiltinMediaType($mediatypedvd, $mediatypehddvd, $mediatypebluray, $mediatypeultrahd) {
	if ($mediatypehddvd) {
		if ($mediatypedvd)
			return(MEDIA_TYPE_HDDVD_DVD);
		return(MEDIA_TYPE_HDDVD);
	}
	if ($mediatypeultrahd) {
		if ($mediatypebluray)
			return(MEDIA_TYPE_ULTRAHD_BLURAY);
		// I don't think there's any 4k releases that have Just a DVD and no BD?
		if ($mediatypedvd)
			return(MEDIA_TYPE_ULTRAHD_BLURAY_DVD);
		return(MEDIA_TYPE_ULTRAHD);
	}
	if ($mediatypebluray) {
		if ($mediatypedvd)
			return(MEDIA_TYPE_BLURAY_DVD);
		return(MEDIA_TYPE_BLURAY);
	}
	if ($mediatypedvd)
		return(MEDIA_TYPE_DVD);
	return(0);
}

function OnOffAuto(&$str, $side, $caseslipcover, $casetype, $builtinmediatype, $custommediatype) {
// TODO: This is the place that determines whether the image needs a banner on it. The rules for this
// in the windows program are opaque due to bugs (settings do not cause reproducable results).
// There is currently no way to change the behavior for the Builtin mediatypes, but here would be
// the place to do it ...
// Note that in windows 3.7.2, 'automatic' will put a banner on the back covers - it didn't used
// to and it is likely wrong, but that is the observed behaiour, which we track

	$defaulthasbanner = ($caseslipcover == 0 && ($casetype == 'HD Keep Case' || $casetype == 'HD Slim'));
	if (isset($str)) {
		$tmp = strtolower($str);
		if ($tmp == 'off')
			return(0);
		if ($custommediatype != '')
			return(-1);
		if ($tmp == 'on' || ($tmp == 'automatic' && $defaulthasbanner))
			return($builtinmediatype);
	}
	else {
		if ($defaulthasbanner) {
			if ($builtinmediatype != MEDIA_TYPE_DVD)
				return($builtinmediatype);
		}
	}
	return(0);
}

function TrueFalse(&$str) {
	return((strtolower($str) == 'true') ? 1 : 0);
}

function GetHashs(&$oldhashs) {
global $db, $DVD_TABLE;

	$result = $db->sql_query("SELECT id,hashprofile,hashnocolid,hashcast,hashcrew FROM $DVD_TABLE") or die($db->sql_error());
	while ($row = $db->sql_fetchrow($result)) {
		$id = array_shift($row);
		$oldhashs[$id] = $row;
	}
	$db->sql_freeresult($result);
	unset($row);
}

function HashData(&$data) {

	$colid = '';
	$hashprofile = Hex(crc32($data));
	$hashnocolid = $hashcast = $hashcrew = $hashprofile;

	if (($back = strpos($data, '</CollectionNumber>')) !== false) {
		$front = strpos($data, '<CollectionNumber>') + strlen('<CollectionNumber>');
		$colid = substr($data, $front, $back-$front);
		$hashnocolid = Hex(crc32(substr($data, 0, $front).substr($data, $back)));
	}

	if (($back = strpos($data, '</Actors>')) !== false) {
		$front = strpos($data, '<Actors>') + strlen('<Actors>');
		$hashcast = Hex(crc32(substr($data, $front, $back-$front)));
	}

	if (($back = strpos($data, '</Credits>')) !== false) {
		$front = strpos($data, '<Credits>') + strlen('<Credits>');
		$hashcrew = Hex(crc32(substr($data, $front, $back-$front)));
	}

	return(array('hashprofile' => $hashprofile, 'hashnocolid' => $hashnocolid, 'hashcast' => $hashcast, 'hashcrew' => $hashcrew, 'colid' => $colid));
}

function MemoryUsage($str, $addeoln = false) {
global $pscommand, $eoln, $ReportOnMemory;

	if (!$ReportOnMemory)
		return($addeoln? $eoln: '');
	if ($pscommand != '') {
		exec($pscommand, $out);
		list($percent, $kb) = explode(' ', trim($out[0]));
		return("$str: Resident Set Size is " . number_format($kb/1024, 1) . "MB ($percent% of real memory) currently using " . number_format(memory_get_usage()/1024/1024, 1) . "MB$eoln");
	}
	return("$str: Currently using " . number_format(memory_get_usage()/1024/1024, 1) . "MB$eoln");
}

function PrepareBrowserOutput() {
global $lang;

	SendNoCacheHeaders('Content-Type: text/html; charset="windows-1252";');
	echo<<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
<title>$lang[IMPORTTITLE]</title>
<link rel="stylesheet" type="text/css" href="format.css.php">
</head>
<body class=f6>
EOT;
}

function ExtractFromZip($filename) {
global $DeleteTemporaryFile, $imagecachedir, $eoln, $lang;

	$success = false;
	$x = zip_open($filename);
	if (!is_resource($x)) {
		printf($lang['IMPORTZIPOPENFAIL'].$eoln, $x, $filename);
		return($success);
	}
	$entry = zip_read($x);
	if (is_resource($entry)) {
		if (zip_entry_open($x, $entry, "r") !== false) {
// here we extract the zipfile
			$name = $imagecachedir . zip_entry_name($entry);
			if (file_exists($name)) {
				printf($lang['IMPORTZIPDELETEPREVIOUS'].$eoln, $name);
				@unlink($name);
			}
			if (($handle=fopen($name, 'w')) === false) {
				printf($lang['IMPORTZIPFOPENFAIL'].$eoln, $name, $filename);
				zip_entry_close($entry);
				zip_close($x);
				return($success);
			}
			@chmod($name, 0666);	// just for my convenience
			$entry_content = zip_entry_read($entry, 8192);
			while ($entry_content !== false && strlen($entry_content) > 0) {
				if (fwrite($handle, $entry_content) === false) {
					printf($lang['IMPORTZIPFWRITEFAIL'].$eoln, $name, $filename);
					zip_entry_close($entry);
					zip_close($x);
					return($success);
				}
				$entry_content = zip_entry_read($entry, 8192);
			}
			fclose($handle);
			zip_entry_close($entry);
			$DeleteTemporaryFile = true;
			$success = $name;
		}
		else {
			echo $lang['IMPORTZIPINTERNAL1'], $eoln;
		}
	}
	else if ($entry === false) {
		echo $lang['IMPORTZIPINTERNAL2'], $eoln;
	}
	else {
		printf($lang['IMPORTZIPINTERNAL3'].$eoln, $filename);
		printf($lang['IMPORTZIPINTERNAL4'].$eoln, $entry);
	}
	zip_close($x);
	return($success);
}

function GetCompressionList() {
// These mechanisms are characterised by using the regular fgets() function to read data
	$KnownCompressions = array(
		0 => array(
			'Supported'	=> true,
			'Compression'	=> 'DVD Profiler XML',
			'Magic'		=> "<?xml vers",
			'Extension'	=> "",
			'Open'		=> "fopen",
			'Close'		=> "fclose"
		),
		1 => array(
			'Supported'	=> function_exists('lzf_compress'),
			'Compression'	=> 'LZ Compress',
			'Magic'		=> "\037\235",
			'Extension'	=> "Z",
			'Open'		=> "",
			'Close'		=> ""
		),
		2 => array(
			'Supported'	=> function_exists('gzopen'),
			'Compression'	=> 'gzip',
			'Magic'		=> "\037\213",
			'Extension'	=> "gz",
			'Open'		=> "gzopen",
			'Close'		=> "gzclose"
		),
		3 => array(
			'Supported'	=> function_exists('bzopen'),
			'Compression'	=> 'bzip2',
			'Magic'		=> "BZ",
			'Extension'	=> "bz2",
			'Open'		=> "bzopen",
			'Close'		=> "bzclose"
		),
		4 => array(
			'Supported'	=> function_exists('zip_open'),
			'Compression'	=> 'zip',
			'Magic'		=> "PK\003\004",
			'Extension'	=> "zip",
			'Open'		=> "fopen",	// we'll extract to ASCII first
			'Close'		=> "fclose"
		)
	);

	return($KnownCompressions);
}

function PrintSupportedCompressions(&$KnownCompressions) {
global $lang, $inbrowser, $eoln;

	echo $lang['IMPORTFROMXMLNOFILES2'], $eoln;
	if ($inbrowser) echo "<pre>";
	foreach ($KnownCompressions as $Compression) {
		if ($Compression['Supported'])
			echo "\t$Compression[Compression]\n";
	}
	if ($inbrowser) echo "</pre>";
	return;
}

function GetListOfXMLFiles(&$my_fopen, &$my_fclose) {
global $xmlfile, $xmldir, $lang, $eoln, $imagecachedir, $TryToChangeMemoryAndTimeLimits;
//
// This will return with an array of files. It will ensure that the IO routines are appropriate for the
// files. Note that the IO routines will only change to match the single file in $xmlfile, while this
// routine will only process files ending .xml in $xmldir, and assume that they are plain-text ASCII.

	$allxmlfiles = array();

// If $xmldir points to a directory, then we grab only XML files from that directory.
// In this case, we ignore $xmlfile.
	if (is_dir($xmldir) && is_readable($xmldir)) {
		echo $lang['IMPORTFROMXMLDIR'], $eoln;
		$handle = opendir($xmldir);
		while (($file=readdir($handle)) !== false) {
			if (strcasecmp(pathinfo($file, PATHINFO_EXTENSION), 'xml') != 0)
				continue;
			if (is_readable("$xmldir/$file"))
				$allxmlfiles[] = "$xmldir/$file";
		}
		closedir($handle);
		return($allxmlfiles);
	}

// $xmldir did not point to a directory, so we will process $xmlfile.

	echo $lang['IMPORTFROMXMLFILE'], $eoln;
	$KnownCompressions = GetCompressionList();
	$Trying = $xmlfile;
	if (!is_readable($xmlfile)) {
// print $xmlfile not found - searching for $KnownCompressions, perhaps with stripped extension?
		$dir = dirname($xmlfile);
		if (!is_readable($dir)) {
			printf($lang['IMPORTFROMXMLBADDIR'].$eoln, $xmlfile, $dir);
			ExitSpecial();
		}
		$base = basename($xmlfile);
		$noext = substr($base, 0, -1*strlen(pathinfo($base, PATHINFO_EXTENSION))-1);
		$found = false;
		$handle = opendir($dir);
		while (($file=readdir($handle)) !== false) {
			if ($file == '.' || $file == '..')
				continue;
// check for simple case-sensitivity
			if (strcasecmp($file, $base) == 0) {
				if (is_readable("$dir/$file")) {
					$found = true;
					break;
				}
			}
// Check for each usable extension. Also check for replacement of the extension (.xml) with the compressed (.zip)
			foreach ($KnownCompressions as $Compression) {
				if (!$Compression['Supported'])
					continue;
				$ext = $Compression['Extension'];
				if (strcasecmp($file, "$base.$ext") == 0 && is_readable("$dir/$file")) {
					$found = true;
					break;
				}
				if (strcasecmp($file, "$noext.$ext") == 0 && is_readable("$dir/$file")) {
					$found = true;
					break;
				}
			}
			if ($found)
				break;
		}
		closedir($handle);
		$Trying = '';
		if ($found) {
			$Trying = $file;
			if ($dir != '.') $Trying = "$dir/$file";
			printf($lang['IMPORTFROMXMLFILEMISSING'].$eoln, $Trying);
		}
	}
	if ($Trying == '') {
		echo $lang['IMPORTFROMXMLNOFILES1'], $eoln;
		PrintSupportedCompressions($KnownCompressions);
		ExitSpecial();
	}
// Figure out what type of file this is, so that we can diddle the IO routines.
	if (($x=fopen($Trying, 'r')) === false) {
		printf($lang['IMPORTFROMXMLCANTTYPEOPEN'].$eoln, $Trying);
		ExitSpecial();
	}
	if (($buf=fread($x, 12)) === false) {
		fclose($x);
		printf($lang['IMPORTFROMXMLCANTTYPEREAD'].$eoln, $Trying);
		ExitSpecial();
	}
	fclose($x);

	$my_fopen = $my_fclose = '';
	$filetype = '* Unknown *';
	foreach ($KnownCompressions as $Compression) {
		if (substr($buf, 0, strlen($Compression['Magic'])) == $Compression['Magic']) {
			$my_fopen = $Compression['Open'];
			$my_fclose = $Compression['Close'];
			$filetype = $Compression['Compression'];
			break;
		}
	}
	printf($lang['IMPORTFROMXMLFILETYPE'].$eoln, $Trying, $filetype);
	if ($my_fopen == '') {
		printf($lang['IMPORTFROMXMLNOSUPPORT'].$eoln, $filetype);
		PrintSupportedCompressions($KnownCompressions);
		ExitSpecial();
	}

// At this point we have determined the (single) file, its type, and set the IO routines to handle it.
// we should have printed any informative messages regarding failures, and should put the
// filename into the array. Failure should have already resulted in an exit.
	if ($filetype == 'zip') {
		if (!isset($imagecachedir) || !is_dir($imagecachedir) || !is_writeable($imagecachedir)) {
			printf($lang['IMPORTZIPCANTWRITE'].$eoln, $Trying);
			ExitSpecial();
		}
		printf($lang['IMPORTZIPEXTRACTHEAD'].$eoln, $Trying, $imagecachedir);
		if ($TryToChangeMemoryAndTimeLimits) set_time_limit(0);
		$Trying = ExtractFromZip($Trying);
		if ($Trying === false)
			ExitSpecial();
	}
	$allxmlfiles[] = $Trying;
	return($allxmlfiles);
}

function ProcessXMLCollection($prelim_output) {
global $xmldir, $xmlfile, $inbrowser, $lang, $eoln, $PHP_SELF, $db, $DVD_TABLE, $delete, $FixBadXML, $UpdateLast, $users, $maxuserid;
global $forumuser, $handleadult, $collectionurl, $debugimageuploads, $endbody, $remove_missing, $TryToChangeMemoryAndTimeLimits, $MyConnectionId;
global $getimages, $DVD_PROPERTIES_TABLE, $DVD_COMMON_ACTOR_TABLE, $DVD_COMMON_CREDITS_TABLE, $CustomPostUpdate, $CollectionsNotInOwned;
global $common_actor, $common_actor_stats, $common_credit, $common_credit_stats, $db_fast_update, $max_packet, $displayfreq, $force_cleanup;

	$T0 = microtime_float();
// Get the current connection ID and stuff it into the DB so that everyone knows we're running
	$res = $db->sql_query("SELECT CONNECTION_ID() AS Id") or die($db->sql_error());
	$row = $db->sql_fetchrow($res);
	$MyConnectionId = $row['Id'];
	$res = $db->sql_query("SELECT value FROM $DVD_PROPERTIES_TABLE WHERE property='CurrentPosition'", 0, true);
	$row = $db->sql_fetchrow($res);
	$db->sql_freeresult($res);
	$val = substr($row['value'], 0, strrpos($row['value'], '|'));
	unset($row);
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='$val|-$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

	if ($inbrowser)
		$tmp = "<div id=\"phpdvd_notice\" style=\"display:none\">200 - $MyConnectionId</div>";
	else
		$tmp = "$lang[UPDATECONNECTIONID]: $MyConnectionId$eoln";
	if ($prelim_output != '')
		$prelim_output = $tmp . $prelim_output . $eoln;
	else
		$prelim_output = $tmp;

	$users = '';
	$maxuserid = 0;
	$common_actor = $common_credit = [];
	$common_actor_stats = array('maxid' => 1, 'numadded' => 0, 'numinserted' => 0, 'amttime' => (float)0);
	$common_credit_stats = array('maxid' => 1, 'numadded' => 0, 'numinserted' => 0, 'amttime' => (float)0);
	if (!isset($db_fast_update))
		$db_fast_update = false;

	$safe_mode = (bool)@ini_get('safe_mode');
	$TryToChangeMemoryAndTimeLimits	= $TryToChangeMemoryAndTimeLimits && !$safe_mode;
	if ($TryToChangeMemoryAndTimeLimits) @ini_set('memory_limit', -1);	// the import script can use boatloads of memory ...
	if ($inbrowser) {
		PrepareBrowserOutput();
	}
	else {
		foreach ($lang as $key => $val) {
			if (substr($key, 0, 6) == 'IMPORT')
				$lang[$key] = html_entity_decode(str_replace('&mdash;', '--', $lang[$key]));
		}
	}
	$my_fopen = 'fopen';
	$my_fclose = 'fclose';

	$total = $UpdateLast['Total'];
	$added = $UpdateLast['Added'];
	$removed = 0;
	$changed = $UpdateLast['Changed'];
	$newcollnum = $UpdateLast['NewCollNum'];
	$ppdelete = 0;
	$start = microtime_float();	// This will be overwritten if we actually do any work

	echo $prelim_output;
	if ($UpdateLast['Offset'] >= 0) {
		$allxmlfiles = GetListOfXMLFiles($my_fopen, $my_fclose);
		$numxmlfiles = count($allxmlfiles);

		$tmp = ($db_fast_update)? $lang['ON']: $lang['OFF'];
		echo $lang['DBFAST'] . $tmp . $eoln;
		flush();
		@ob_flush();

// Find out how long a query we can send to the server ...
		$result = $db->sql_query("SHOW VARIABLES like 'max_allowed_packet'") or die($db->sql_error());
		$row = $db->sql_fetch_array($result);
		$db->sql_freeresult($result);
		$max_packet = $row['Value'];
		$max_packet -= 2;	// Experimentation has shown that the largest string we can send is max_allowed_packet-2 ...
		unset($row);

		$start = microtime_float();

// Delete contents of tables
		if ($delete == 1) {
			echo $lang['COMPLETE'] . $eoln;
			flush();
			@ob_flush();
			DeleteFromTables('clean out all of the tables');
		}

		if ($UpdateLast['Offset'] != 0 && $remove_missing) {
			$remove_missing = false;
			echo $lang['UPDATEREMOVAL1'] . $eoln;
			echo $lang['UPDATEREMOVAL2'] . $eoln;
			echo $lang['UPDATEREMOVAL3'] . $eoln;
			flush();
			@ob_flush();
		}
		if (!$remove_missing) {
			echo $lang['NOTREMOVING'] . $eoln;
			flush();
			@ob_flush();
		}

		$oldhashs = array();
		GetHashs($oldhashs);

		ModifyTables('DISABLE');
		echo $lang['IMPORTUPDATING'] . $eoln;	// database name
		if ($numxmlfiles != 1)
			printf($lang['IMPORTXMLDIR'].$eoln, $numxmlfiles, $xmldir);

		$LotsaFiles = count($allxmlfiles) > 5;
		$TT = sprintf("%9s", number_format(microtime_float() - $T0, 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']));
		echo MemoryUsage("$TT: Before Processing");
		if ($UpdateLast['Offset'] > 0) {
			printf($lang['UPDATERESUMING'].$eoln, $UpdateLast['Total'],
				number_format($UpdateLast['Offset'], 0, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']), $UpdateLast['Filename']);
		}
		foreach ($allxmlfiles as $key => $currentxmlfile) {
			if ($UpdateLast['Offset'] > 0 && $currentxmlfile != $UpdateLast['Filename'])
				continue;
			if (($fh=$my_fopen($currentxmlfile, 'r')) === false) {
				echo $lang['IMPORTBADOPEN'] . $currentxmlfile . $eoln;
				if ($inbrowser)
					echo "$endbody</html>\n";
				ExitSpecial();	// Yes, the end html tag will precede the error div. none will care
			}
			$booya = fstat($fh);
			$currentxmlfilesize = $booya['size'];
			unset($booya);
			if ($UpdateLast['Offset'] > 0 && isset($UpdateLast['Filesize']) && $UpdateLast['Filesize'] != $currentxmlfilesize) {
				printf($lang['UPDATEFILECHANGED1'].$eoln, $currentxmlfile);
				printf($lang['UPDATEFILECHANGED2'].$eoln, $UpdateLast['Filesize'], $currentxmlfilesize);
				echo "$lang[UPDATEFILECHANGED3]$eoln";
			}
			if (!$LotsaFiles) {
				if (substr($currentxmlfile, -3) == '.gz')
					echo "$lang[IMPORTUSINGCOMPRESSED]$currentxmlfile$eoln";
				else
					echo "$lang[IMPORTPROCESSING]$currentxmlfile$eoln";
			}

			$inputencoding = '';
			while (true) {
				do {
					if (($temp=fgets($fh)) === false)
						break 2;	// escape the while (true)
					if ($inputencoding == '') {
						if (preg_match('/<\?xml version="1.0" encoding="([^"]*)"\?>/i', $temp, $matches)) {
							$inputencoding = $matches[1];
							unset($matches);
							if (strtolower($inputencoding) == 'windows-1252')
								$inputencoding = 'ISO-8859-1';
						}
					}
				} while (strpos($temp, '<DVD') === false);
				if ($inputencoding == '')
					$inputencoding = 'ISO-8859-1';
				if ($UpdateLast['Offset'] > 0) {
					fseek($fh, $UpdateLast['Offset']);
					$UpdateLast['Offset'] = 0;
					$UpdateLast['Filename'] = '';
					$data = '';
					do {	// Ensure that we're at the beginning of a DVD section ... This could get messed up with MediaTypes ...
						if (($temp=fgets($fh)) === false)
							break 2;	// escape the while (true)
					} while (strpos($temp, '<DVD') === false);
				}
				$data = $temp;
				do {
					if (($temp=fgets($fh)) === false)
						break 2;	// escape the while (true)
					$data .= $temp;
				} while (!((!(strpos($data, '</MediaTypes>') === false)) && !(strpos($temp, '</DVD>') === false)));
				$total++;

				$idfront = strpos($data, '<ID>') + strlen('<ID>');
				$idend = strpos($data, '</ID>');
				$id = substr($data, $idfront, $idend-$idfront);
				$hashs = HashData($data);

				if (!isset($oldhashs[$id]) || $hashs['hashprofile'] != $oldhashs[$id]['hashprofile']) {
					if (!isset($oldhashs[$id])) {
						$added++;
						$oldhashs[$id] = array('hashprofile' => '', 'hashnocolid' => '', 'hashcast' => '', 'hashcrew' => '');
					}
					else {
						$changed++;
					}
					if ($oldhashs[$id]['hashnocolid'] == $hashs['hashnocolid']) {
// Only the collection number changed. Update to new id, and walk away.
						$db->sql_query("UPDATE $DVD_TABLE SET collectionnumber='$hashs[colid]',hashprofile='$hashs[hashprofile]' WHERE id='$id'") or die($db->sql_error());
						$newcollnum++;
					}
					else {
// This *resets* the time limit to infinity. that is why it is in the loop
						if ($TryToChangeMemoryAndTimeLimits) set_time_limit(0);

						$tree = GetXMLTreeFromString($data, $inputencoding);
						unset($data);		// trying to reduce memory footprint
						if ($total == 1) {
							$missing = CheckForCompleteXML($tree['DVD'][0]);
							if ($missing != '') {
								echo "$eoln*******************************************************************************$eoln";
								echo $currentxmlfile . $lang['IMPORTMISSING1'] . $eoln;
								echo $lang['IMPORTMISSING2'] . $eoln;
								echo $lang['IMPORTMISSING3'] . $missing . $eoln;
								echo "*******************************************************************************$eoln";
								flush();
								@ob_flush();
							}
						}
						$IDsChanged[$tree['DVD'][0]['ID'][0]['VALUE']] = 1;

						$retval = AddADVD($tree['DVD'][0], $hashs, $oldhashs[$id]);
						unset($tree);
						if ($retval != 0) {
							echo "$currentxmlfile: " . $lang['ADDERRORS'][$retval] . $total . $eoln;
						}
					}
				}
				if (isset($data)) unset($data);
				if (isset($oldhashs[$id])) unset($oldhashs[$id]);
				if (($total % $displayfreq) == 0) {
					$TT = sprintf("%9s", number_format(microtime_float() - $T0, 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']));
					echo "$TT: $lang[IMPORTPROCESSED]" . $total . MemoryUsage('', true);
					flush();
					@ob_flush();
				}
				$WhereInFile = ftell($fh);
				$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='$WhereInFile|$currentxmlfile!$currentxmlfilesize|$total|$added|$changed|$newcollnum|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());
			}
			$my_fclose($fh);
		}
		if (($total % $displayfreq) != 0) {
			$TT = sprintf("%9s", number_format(microtime_float() - $T0, 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']));
			echo "$TT: $lang[IMPORTPROCESSED]" . $total . MemoryUsage('', true);
		}

		if ($db_fast_update) {
# Now complete the update of cast and crew (only does anything if $all_in_one_go, but free()s memory and prints message)
			UpdateCommonTableFromMemory($common_actor, $common_actor_stats, $DVD_COMMON_ACTOR_TABLE);
			printf($lang['ADDCAST'].$eoln,
				number_format($common_actor_stats['numadded'], 0, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']),
				number_format($common_actor_stats['numinserted'], 0, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']),
				number_format($common_actor_stats['amttime'], 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']));
			unset($common_actor);

			UpdateCommonTableFromMemory($common_credit, $common_credit_stats, $DVD_COMMON_CREDITS_TABLE);
			printf($lang['ADDCREW'].$eoln,
				number_format($common_credit_stats['numadded'], 0, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']),
				number_format($common_credit_stats['numinserted'], 0, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']),
				number_format($common_credit_stats['amttime'], 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']));
			unset($common_credit);
		}

		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-1||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());
	}

	if ($UpdateLast['Offset'] < 0) {
		echo $lang['UPDATEFINISHING'] . $eoln;
		flush();
		@ob_flush();
	}
	if ($UpdateLast['Offset'] >= -1) {
		echo $lang['IMPORTMEDIAANDCOLLECTIONS'] . $eoln;
		flush();
		@ob_flush();

		if ($remove_missing && isset($oldhashs)) {
			echo $lang['IMPORTDELETING'] . $eoln;
			flush();
			@ob_flush();
			$del = 'DELETE';
			foreach ($oldhashs as $id => $theoldhashs) {
				$removed++;
				$del .= "||$id";
				DeleteFromTables($id);
			}
			unset($theoldhashs);
			unset($oldhashs);
		}
		UpdateRatingDescriptions();

		echo $lang['IMPORTBOXSETS'] . $eoln;
		flush();
		@ob_flush();
		UpdateBoxSets();
		ModifyTables('ENABLE');
		$ppdelete = TidyPurchasePlace();
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());
	}

	if ($UpdateLast['Offset'] > -3) {
		$DatabaseTime = microtime_float() - $start;
		echo $lang['IMPORTUPDATESTATS'] . $eoln;
		flush();
		@ob_flush();
		if ($UpdateLast['Offset'] < 0)
			$force_cleanup = true;
		UpdateStats($TryToChangeMemoryAndTimeLimits);

		$totaltime = microtime_float() - $start;

		$TT = sprintf("%9s", number_format(microtime_float() - $T0, 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']));
		echo MemoryUsage("$TT: After processing");
		echo $lang['IMPORTDONE1'] . $total . $eoln;
		echo $lang['IMPORTNUMADDED'] . $added . $eoln;
		printf("$lang[IMPORTNUMCHANGED]$eoln", $changed, $newcollnum);
		echo $lang['IMPORTNUMREMOVED'] . $removed . $eoln;
		echo $lang['SUPPLIERREMOVED'] . $ppdelete . $eoln;
		echo $lang['IMPORTDONE2'] . number_format($totaltime, 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']) .
			' (' .  number_format($DatabaseTime, 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']) .
			' + ' . number_format($totaltime-$DatabaseTime, 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']) .
			')' . $eoln;

		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-3||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());
	}

	if ($UpdateLast['Offset'] >= -3) {

		if (is_readable('ws.php')) {
        	global $handlewatched, $jpgraphlocation, $profiles, $imagecachedir, $ws_wb, $IsPrivate, $ClassColor;
        		$action = 'update';
        		include ('ws.php');
		}

 		if (is_readable('imagedata.php') && $forumuser && $collectionurl && $getimages>0) {
		global $img_physpath, $img_webpath, $img_webpathf, $img_webpathb, $VersionNum, $DVD_LOCKS_TABLE, $thumbnails;
 			include ('imagedata.php');
 		}
		unset($IDsChanged);

		$db->sql_query("REPLACE INTO $DVD_PROPERTIES_TABLE SET value='".time()."', property='LastUpdate'") or die($db->sql_error());

		if (is_readable($CustomPostUpdate)) {
			echo "$lang[EXECUTING] $CustomPostUpdate$eoln";
			include($CustomPostUpdate);
		}
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='0||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

		if ($inbrowser)
			echo "<br><br><center><a class=\"URLref\" href=\"$PHP_SELF\" target=\"_top\">$lang[IMPORTCLICK]</a><br></center><div id=\"theend\"></div>$endbody</html>\n";
	}

//	$values = array();
//	$xyz = dbg_get_profiler_results($values);
//	var_dump($xyz);
//	print_r($values);
}

function TidyPurchasePlace() {
global $db, $DVD_TABLE, $DVD_SUPPLIER_TABLE;
	$ppdelete = 0;
	$suppliers = $db->sql_query("SELECT sid FROM $DVD_SUPPLIER_TABLE") or die($db->sql_error());
 	while ($supplierid = $db->sql_fetchrow($suppliers)) {
		$result = $db->sql_query("SELECT id FROM $DVD_TABLE WHERE purchaseplace='$supplierid[sid]'") or die($db->sql_error());
		if (!$db->sql_numrows($result)) {
			$ppdelete++;
			$db->sql_query("DELETE FROM $DVD_SUPPLIER_TABLE where sid = '$supplierid[sid]'") or die($db->sql_error());
		}
	}
	return($ppdelete);
}

function DeleteFromTables($id, $castisbad = true, $crewisbad = true) {
global $db, $DVD_TABLE, $DVD_ACTOR_TABLE, $DVD_EVENTS_TABLE, $DVD_DISCS_TABLE, $DVD_AUDIO_TABLE, $DVD_CREDITS_TABLE, $DVD_LOCKS_TABLE;
global $DVD_BOXSET_TABLE, $DVD_STUDIO_TABLE, $DVD_SUBTITLE_TABLE, $DVD_TAGS_TABLE, $DVD_SUPPLIER_TABLE, $DVD_USERS_TABLE, $MadeAChange;
global $DVD_GENRES_TABLE, $DVD_COMMON_ACTOR_TABLE, $DVD_COMMON_CREDITS_TABLE, $DVD_PROPERTIES_TABLE, $DVD_EXCLUSIONS_TABLE, $DVD_LINKS_TABLE;

	$MadeAChange = true;
	$where = '';
	if ($id != 'clean out all of the tables')
		$where = "WHERE id='$id'";
	$db->sql_query("DELETE FROM $DVD_TABLE $where") or die($db->sql_error());
	if ($castisbad) $db->sql_query("DELETE FROM $DVD_ACTOR_TABLE $where") or die($db->sql_error());
	$db->sql_query("DELETE FROM $DVD_EVENTS_TABLE $where") or die($db->sql_error());
	$db->sql_query("DELETE FROM $DVD_LOCKS_TABLE $where") or die($db->sql_error());
	$db->sql_query("DELETE FROM $DVD_DISCS_TABLE $where") or die($db->sql_error());
	$db->sql_query("DELETE FROM $DVD_AUDIO_TABLE $where") or die($db->sql_error());
	$db->sql_query("DELETE FROM $DVD_GENRES_TABLE $where") or die($db->sql_error());
	$db->sql_query("DELETE FROM $DVD_EXCLUSIONS_TABLE $where") or die($db->sql_error());
	if ($crewisbad) $db->sql_query("DELETE FROM $DVD_CREDITS_TABLE $where") or die($db->sql_error());
	$db->sql_query("DELETE FROM $DVD_BOXSET_TABLE $where") or die($db->sql_error());
	$db->sql_query("DELETE FROM $DVD_STUDIO_TABLE $where") or die($db->sql_error());
	$db->sql_query("DELETE FROM $DVD_LINKS_TABLE $where") or die($db->sql_error());
	$db->sql_query("DELETE FROM $DVD_SUBTITLE_TABLE $where") or die($db->sql_error());
	$db->sql_query("DELETE FROM $DVD_TAGS_TABLE $where") or die($db->sql_error());
	if ($where != '') {
		$db->sql_query("UPDATE $DVD_TABLE SET boxparent='' WHERE boxparent='$id'") or die($db->sql_error());
	}
	else {
		$db->sql_query("DELETE FROM $DVD_SUPPLIER_TABLE") or die($db->sql_error());
		$db->sql_query("DELETE FROM $DVD_USERS_TABLE") or die($db->sql_error());
		$db->sql_query("DELETE FROM $DVD_COMMON_ACTOR_TABLE WHERE caid>0") or die($db->sql_error());
		$db->sql_query("DELETE FROM $DVD_COMMON_CREDITS_TABLE WHERE caid>0") or die($db->sql_error());
		$db->sql_query("DELETE FROM $DVD_PROPERTIES_TABLE WHERE property LIKE 'Rating%'") or die($db->sql_error());
	}
}

function GetAUserIDFor($fname, $lname, $emailaddress, $phonenumber) {
global $users, $maxuserid, $db, $DVD_USERS_TABLE;

	if ($users == '') {
		$users = array();
		$res = $db->sql_query("SELECT * FROM $DVD_USERS_TABLE ORDER BY uid ASC") or die($db->sql_error());
		while ($row = $db->sql_fetch_array($res)) {
			$full = "$row[firstname]|$row[lastname]";
			$users[$full] = $row;
			$maxuserid = $row['uid'];
		}
		$db->sql_freeresult($res);
	}

	$fullname = "$fname|$lname";
	if ($fullname == '|')
		return(0);
	if (isset($users[$fullname])) {
		$uid = $users[$fullname]['uid'];
		if ($emailaddress != $users[$fullname]['emailaddress'] || $phonenumber != $users[$fullname]['phonenumber']) {
			$users[$fullname]['emailaddress'] = $emailaddress;
			$users[$fullname]['phonenumber'] = $phonenumber;
			$sql = "UPDATE $DVD_USERS_TABLE SET emailaddress='"
				. $db->sql_escape($emailaddress) . "',phonenumber='"
				. $db->sql_escape($phonenumber) . "' WHERE uid=$uid";
			$db->sql_query($sql) or die($db->sql_error());
		}
	}
	else {
		$uid = ++$maxuserid;
		$users[$fullname] = array('uid' => $maxuserid, 'firstname' => $fname, 'lastname' => $lname, 'emailaddress' => $emailaddress, 'phonenumber' => $phonenumber);
		$sql = "INSERT INTO $DVD_USERS_TABLE (uid,firstname,lastname,emailaddress,phonenumber) VALUES ("
			.$maxuserid . ','
			."'" . $db->sql_escape($fname) ."',"
			."'" . $db->sql_escape($lname) ."',"
			."'" . $db->sql_escape($emailaddress) ."',"
			."'" . $db->sql_escape($phonenumber) . "')";
		$db->sql_query($sql) or die($db->sql_error());
	}
	return($uid);
}

function GetAnIDFor($fname, $mname, $lname, $birth, $table, &$hints) {
global $db, $db_fast_update, $DVD_COMMON_ACTOR_TABLE;
global $common_actor, $common_actor_stats, $common_credit, $common_credit_stats;

	if ($db_fast_update) {
		$fullname = "$fname|$mname|$lname|$birth";

		if ($table == $DVD_COMMON_ACTOR_TABLE) {
			$common_name = &$common_actor;
			$common_stats = &$common_actor_stats;
		} else {
			$common_name = &$common_credit;
			$common_stats = &$common_credit_stats;
		}

		if (isset($common_name[$fullname])) {
			return $common_name[$fullname][0];
		} else {
			$common_stats['maxid']++;
			$common_name[$fullname] = array($common_stats['maxid'], 1);
			$hints[] = $fullname;
			return($common_stats['maxid']);
		}
	}

	$sql = "SELECT * FROM $table WHERE firstname='" . $db->sql_escape($fname)
		."' AND middlename='" . $db->sql_escape($mname)
		."' AND lastname='" . $db->sql_escape($lname)
		."' AND birthyear='" . $db->sql_escape($birth) . "'";
	$res = $db->sql_query($sql) or die($db->sql_error());
	$foundaid = false;
	while ($aid = $db->sql_fetchrow($res)) {
		if ($aid['firstname'] == $fname &&
		    $aid['middlename'] == $mname &&
		    $aid['lastname'] == $lname &&
		    $aid['birthyear'] == $birth) {
			$foundaid = true;
			break;
		}
	}
	$db->sql_freeresult($res);

	if ($foundaid === false) {
		$sql = "INSERT INTO $table (caid,firstname,middlename,lastname,birthyear,fullname) VALUES ("
			.'0,'
			."'" . $db->sql_escape($fname) ."',"
			."'" . $db->sql_escape($mname) ."',"
			."'" . $db->sql_escape($lname) ."',"
			."'" . $db->sql_escape($birth) ."',"
			."'" . $db->sql_escape(preg_replace('/\s\s+/', ' ', trim("$fname $mname $lname"))) . "')";
		$db->sql_query($sql) or die($db->sql_error());
		$aid['caid'] = $db->sql_nextid();
	}
	return($aid['caid']);
}

function StringIfThere(&$str, $default='') {
global $db;
	if (isset($str))
		return($db->sql_escape($str));
	return($default);
}

function region_cmp($a, $b) {
	return(strcmp($a['VALUE'], $b['VALUE']));
}

function AddADVD(&$dvd_info, $hashs, $oldhashs) {
global $DVD_TABLE, $DVD_COMMON_ACTOR_TABLE, $DVD_ACTOR_TABLE, $DVD_EVENTS_TABLE, $DVD_DISCS_TABLE, $DVD_AUDIO_TABLE, $DVD_LOCKS_TABLE;
global $DVD_COMMON_CREDITS_TABLE, $DVD_CREDITS_TABLE, $DVD_BOXSET_TABLE, $DVD_STUDIO_TABLE, $DVD_SUBTITLE_TABLE, $DVD_TAGS_TABLE;
global $DVD_SUPPLIER_TABLE, $DVD_GENRES_TABLE, $DVD_EXCLUSIONS_TABLE, $DVD_LINKS_TABLE, $AddWatchedEventWhenReturned;
global $db, $img_episode, $episode_replacements, $pcre_episode_replacements, $delete, $all_in_one_go, $CollectionsNotInOwned;
global $common_actor, $common_actor_stats, $common_credit, $common_credit_stats, $max_packet, $db_fast_update, $lang;
global $db_schema_version;

	$TheProfileID = $dvd_info['ID'][0]['VALUE'];

	if ($TheProfileID == '') {
// Profile IDs may not be blank
		return(1);
	}
	$db->sql_query("SET autocommit=0;") or die($db->sql_error());
	$db->sql_transaction('begin') or die($db->sql_error());
	if ($delete != 1)	// Don't bother if we've already nuked the entire db ...
		DeleteFromTables($TheProfileID, $hashs['hashcast']!=$oldhashs['hashcast'], $hashs['hashcrew']!=$oldhashs['hashcrew']);

	$primedir = '';
	$purchase_day = $purchase_month = $purchase_year = 0;
	if (isset($dvd_info['PURCHASEINFO'][0]['PURCHASEDATE'][0]['VALUE'])) {
		if (strpos($dvd_info['PURCHASEINFO'][0]['PURCHASEDATE'][0]['VALUE'], '/') !== false) {
			list($purchase_day, $purchase_month, $purchase_year) = explode('/', $dvd_info['PURCHASEINFO'][0]['PURCHASEDATE'][0]['VALUE']);
		} else {
			list($purchase_year, $purchase_month, $purchase_day) = explode('-', $dvd_info['PURCHASEINFO'][0]['PURCHASEDATE'][0]['VALUE']);
		}
	}

	$lastedited_day = $lastedited_month = $lastedited_year = 0;
	if (isset($dvd_info['LASTEDITED'][0]['VALUE'])) {
		if (strstr($dvd_info['LASTEDITED'][0]['VALUE'], '/'))
			list($lastedited_day, $lastedited_month, $lastedited_year) = explode('/', $dvd_info['LASTEDITED'][0]['VALUE']);
		else
			list($lastedited_year, $lastedited_month, $lastedited_day) = explode('-', $dvd_info['LASTEDITED'][0]['VALUE']);
	}

	$loan_day = $loan_month = $loan_year = 0;
	if (isset($dvd_info['LOANINFO'][0]['DUE'][0]['VALUE'])) {
		if (strstr($dvd_info['LOANINFO'][0]['DUE'][0]['VALUE'], '/'))
			list($loan_day, $loan_month, $loan_year) = explode('/', $dvd_info['LOANINFO'][0]['DUE'][0]['VALUE']);
		else
			list($loan_year, $loan_month, $loan_day) = explode('-', $dvd_info['LOANINFO'][0]['DUE'][0]['VALUE']);
	}

	$purchaseprice = (isset($dvd_info['PURCHASEINFO'][0]['PURCHASEPRICE'][0]['ATTRIBUTES']['FORMATTEDVALUE'])) ? $dvd_info['PURCHASEINFO'][0]['PURCHASEPRICE'][0]['ATTRIBUTES']['FORMATTEDVALUE'] : '0';
	$paid = (isset($dvd_info['PURCHASEINFO'][0]['PURCHASEPRICE'][0]['VALUE'])) ? $dvd_info['PURCHASEINFO'][0]['PURCHASEPRICE'][0]['VALUE'] : 0;
	$srp = (isset($dvd_info['SRP'][0]['ATTRIBUTES']['FORMATTEDVALUE'])) ? $dvd_info['SRP'][0]['ATTRIBUTES']['FORMATTEDVALUE'] : '0';
	$srpdec = (isset($dvd_info['SRP'][0]['VALUE'])) ? $dvd_info['SRP'][0]['VALUE'] : 0;

	$realcollectiontype = (isset($dvd_info['COLLECTIONTYPE'][0]['VALUE'])) ? $dvd_info['COLLECTIONTYPE'][0]['VALUE'] : '';
	$collectiontype = strtolower($realcollectiontype);
	if ($collectiontype == 'wish list') $collectiontype = 'wishlist';	// 3.0 changed the name ...
	if (isset($dvd_info['COLLECTIONTYPE'][0]['ATTRIBUTES']['ISPARTOFOWNEDCOLLECTION'])) {
		if (TrueFalse($dvd_info['COLLECTIONTYPE'][0]['ATTRIBUTES']['ISPARTOFOWNEDCOLLECTION']) == 1)
			$collectiontype = 'owned';
	}
	else {
		if ($collectiontype != 'ordered' && $collectiontype != 'wishlist') {
			$collectiontype = 'owned';
			if (in_array($realcollectiontype, $CollectionsNotInOwned))
				$collectiontype = $realcollectiontype;
		}
	}

// in V3, it's guaranteed that a wishpriority will exist. Sadly, it may not be zero for non-wishlist items. Better fix that ...
	$wishpriority = ($collectiontype=='wishlist') ? intval($dvd_info['WISHPRIORITY'][0]['VALUE']) : 0;

	$region = '';
	if (isset($dvd_info['REGIONS'][0]['REGION'])) {
		$xxx = $dvd_info['REGIONS'][0]['REGION'];
// This sort ensures that there are no combinatorial differences, but is expensive and currently unnecessary
//		usort($xxx, 'region_cmp');
		foreach ($xxx as $key => $val)
			$region .= $val['VALUE'];
	}
	if ($region == '')
		$region = (TrueFalse($dvd_info['MEDIATYPES'][0]['BLURAY'][0]['VALUE']) == 1)? '@': '0';

	$caseslipcover = 0;
	if (isset($dvd_info['CASESLIPCOVER'][0]['VALUE']) && TrueFalse($dvd_info['CASESLIPCOVER'][0]['VALUE']))
		$caseslipcover = 1;
	$formataspectratio =  (isset($dvd_info['FORMAT'][0]['FORMATASPECTRATIO'][0]['VALUE'])) ? $dvd_info['FORMAT'][0]['FORMATASPECTRATIO'][0]['VALUE'] : '';

	$format16x9 = TrueFalse($dvd_info['FORMAT'][0]['FORMAT16X9'][0]['VALUE']);
	$formatletterbox = TrueFalse($dvd_info['FORMAT'][0]['FORMATLETTERBOX'][0]['VALUE']);
	$fmcc = $fmcb = $fmccz = $fmcm = 0;
	if (isset($dvd_info['FORMAT'][0]['FORMATCOLOR'][0]['VALUE'])) {
		$fmc = $dvd_info['FORMAT'][0]['FORMATCOLOR'][0]['VALUE'];
		if ($fmc == 'Color')
			$fmcc = 1;
		else if ($fmc == 'Black & White')
			$fmcb = 1;
		else if ($fmc == 'Colorized')
			$fmccz = 1;
		else if ($fmc == 'Mixed')
			$fmcm = 1;
		else if ($fmc == 'Multiple')
			$fmcc = $fmcb = 1;
	}
	if (isset($dvd_info['FORMAT'][0]['COLORFORMAT'][0])) {
		$fmcc = TrueFalse($dvd_info['FORMAT'][0]['COLORFORMAT'][0]['CLRCOLOR'][0]['VALUE']);
		$fmcb = TrueFalse($dvd_info['FORMAT'][0]['COLORFORMAT'][0]['CLRBLACKANDWHITE'][0]['VALUE']);
		$fmccz = TrueFalse($dvd_info['FORMAT'][0]['COLORFORMAT'][0]['CLRCOLORIZED'][0]['VALUE']);
		$fmcm = TrueFalse($dvd_info['FORMAT'][0]['COLORFORMAT'][0]['CLRMIXED'][0]['VALUE']);
	}

// Process the notes field for episode guides. This assumes that the images will be referenced by the strings in the $episode_replacements array
	$notes = (isset($dvd_info['NOTES'][0]['VALUE'])) ? $dvd_info['NOTES'][0]['VALUE'] : '';
	foreach ($pcre_episode_replacements as $val => $repl)
		$notes = preg_replace($repl, $img_episode, $notes);
	foreach ($episode_replacements as $val => $repl)
		$notes = str_replace($repl, $img_episode, $notes);

// Put purchase place/url/type in its only table and store just the purchaseplace id in the dvd table.
        $sid = '';
	$suppliername = '';
	if ($collectiontype != 'wishlist')
		$suppliername = (isset($dvd_info['PURCHASEINFO'][0]['PURCHASEPLACE'][0]['VALUE'])) ? $dvd_info['PURCHASEINFO'][0]['PURCHASEPLACE'][0]['VALUE'] : '';
	if ($suppliername) {
		$supplier = array(
			'suppliername' =>  $suppliername,
			'suppliertype' => (isset($dvd_info['PURCHASEINFO'][0]['PURCHASEPLACETYPE'][0]['VALUE'])) ? $dvd_info['PURCHASEINFO'][0]['PURCHASEPLACETYPE'][0]['VALUE'] : '',
			'supplierurl' => (isset($dvd_info['PURCHASEINFO'][0]['PURCHASEPLACEWEBSITE'][0]['VALUE'])) ? $dvd_info['PURCHASEINFO'][0]['PURCHASEPLACEWEBSITE'][0]['VALUE'] : ''
		);
	}
	else {
		$suppliername = 'Unknown';
		$supplier = array(
			'suppliername' => 'Unknown',
			'suppliertype' => 'U',
			'supplierurl' => ''
		);
	}

       	while (!$sid) {
               	$xxx = "SELECT sid FROM $DVD_SUPPLIER_TABLE WHERE suppliername='" . $db->sql_escape($suppliername) . "'";
               	$result = $db->sql_query($xxx) or die($db->sql_error());
               	$row = $db->sql_fetchrow($result);
               	$sid = $row['sid'];
               	if (!$sid) {
                       	$sql = "INSERT INTO $DVD_SUPPLIER_TABLE " . $db->sql_build_array('INSERT', $supplier);
                       	$db->sql_query($sql) or die($db->sql_error());
               	}
       	}
       	unset ($supplier);

	$auxcolltype = '';
	if (isset($dvd_info['TAGS'][0]['TAG'])) {
		$sql = '';
		foreach ($dvd_info['TAGS'][0]['TAG'] as $num_s => $tag_info) {
			$n = (isset($tag_info['ATTRIBUTES']['NAME'])) ? $db->sql_escape($tag_info['ATTRIBUTES']['NAME']) : '';
			$f = (isset($tag_info['ATTRIBUTES']['FULLNAME'])) ? $tag_info['ATTRIBUTES']['FULLNAME'] : '';
			if (strncasecmp($f, 'TABS/', strlen('TABS/')) == 0) {
				$z = substr($f, strlen('TABS'));
				$auxcolltype .= $z;
			}
			if ($f != '') $f = $db->sql_escape($f);
			if ($sql != '') $sql .= ',';
			$sql .= "('$TheProfileID','$n','$f')";
		}
		if ($sql != '')
			$db->sql_query("INSERT INTO $DVD_TAGS_TABLE (id,name,fullyqualifiedname) VALUES $sql") or die($db->sql_error());
		if ($auxcolltype != '')
			$auxcolltype .= '/';
	}

	if (isset($dvd_info['BOXSET'][0]['CONTENTS'][0]['CONTENT'])) {
		$sql = '';
		foreach ($dvd_info['BOXSET'][0]['CONTENTS'][0]['CONTENT'] as $num_s => $boxset_info) {
			$c = (isset($boxset_info['VALUE'])) ? $boxset_info['VALUE'] : '';
			if ($sql != '') $sql .= ',';
			$sql .= "('$TheProfileID','$c')";
		}
		if ($sql != '')
			$db->sql_query("INSERT INTO $DVD_BOXSET_TABLE (id,child) VALUES $sql") or die($db->sql_error());
	}

	$isadulttitle = 0;
	$primegenre = '';
	if (isset($dvd_info['GENRES'][0]['GENRE'])) {
		$sql = '';
		foreach ($dvd_info['GENRES'][0]['GENRE'] as $num_g => $genre_info) {
			if (!empty($genre_info['VALUE'])) {
				if ($primegenre == '')
					$primegenre = $genre_info['VALUE'];
				if ($sql != '') $sql .= ',';
				$sql .= "('$TheProfileID',$num_g,'" . $db->sql_escape($genre_info['VALUE']) . "')";
				if ($genre_info['VALUE'] == 'Adult')
					$isadulttitle = 1;
			}
		}
		if ($sql != '')
			$db->sql_query("INSERT INTO $DVD_GENRES_TABLE (id,dborder,genre) VALUES $sql") or die($db->sql_error());
	}

// Put any exclusions into a table.
	if (isset($dvd_info['EXCLUSIONS'][0])) {
		$f  = 'id';			$v  =       "'$TheProfileID'";
		$f .= ',moviepick';		$v .= ',' . TrueFalse($dvd_info['EXCLUSIONS'][0]['MOVIEPICK'][0]['VALUE']);
		$f .= ',mobile';		$v .= ',' . TrueFalse($dvd_info['EXCLUSIONS'][0]['MOBILE'][0]['VALUE']);
		$f .= ',iphone';		$v .= ',' . TrueFalse($dvd_info['EXCLUSIONS'][0]['IPHONE'][0]['VALUE']);
		$f .= ',remoteconnections';	$v .= ',' . TrueFalse($dvd_info['EXCLUSIONS'][0]['REMOTECONNECTIONS'][0]['VALUE']);
		$f .= ',dpopublic';		$v .= ',' . TrueFalse($dvd_info['EXCLUSIONS'][0]['DPOPUBLIC'][0]['VALUE']);
		$f .= ',dpoprivate';		$v .= ',' . TrueFalse($dvd_info['EXCLUSIONS'][0]['DPOPRIVATE'][0]['VALUE']);
		$db->sql_query("INSERT INTO $DVD_EXCLUSIONS_TABLE ($f) VALUES ($v)") or die($db->sql_error());
	}

// Put any MyLinks entries into a table.
	if (isset($dvd_info['MYLINKS'][0]['CUSTOMLINKS'][0]['USERLINK'])) {
		$dborder = 0;
		$sql = '';
		foreach ($dvd_info['MYLINKS'][0]['CUSTOMLINKS'][0]['USERLINK'] as $num_s => $link_info) {
			$dborder++;
			$u = (isset($link_info['ATTRIBUTES']['URL'])) ? $db->sql_escape($link_info['ATTRIBUTES']['URL']) : '';
			$d = (isset($link_info['ATTRIBUTES']['DESCRIPTION'])) ? $db->sql_escape($link_info['ATTRIBUTES']['DESCRIPTION']) : '';
			$c = (isset($link_info['ATTRIBUTES']['CATEGORY'])) ? $db->sql_escape($link_info['ATTRIBUTES']['CATEGORY']) : '';
			if ($c == '') $c = 'Other';
			if ($c == 'Official Websites') $sortorder = 1000 + $dborder;
			else if ($c == 'Fan Sites') $sortorder = 2000 + $dborder;
			else if ($c == 'Trailers and Clips') $sortorder = 3000 + $dborder;
			else if ($c == 'Reviews') $sortorder = 4000 + $dborder;
			else if ($c == 'Ratings') $sortorder = 5000 + $dborder;
			else if ($c == 'General Information') $sortorder = 6000 + $dborder;
			else if ($c == 'Games') $sortorder = 7000 + $dborder;
			else
				$sortorder = 8000 + $dborder;
			if ($d == '') {
				$d = $u;
				if (strncasecmp($d, 'http://', 7) == 0 || strncasecmp($d, 'file://', 7) == 0)
					$d = substr($d, 7);
				if (strncasecmp($d, 'www.', 4) == 0)
					$d = substr($d, 4);
				if (($EOHost=strpos($d, '/')) !== false)
					$d = substr($d, 0, $EOHost);
			}
			if ($sql != '') $sql .= ',';
			$sql .= "('$TheProfileID',$sortorder,0,'$u','$d','$c')";
		}
		if ($sql != '')
			$db->sql_query("INSERT INTO $DVD_LINKS_TABLE (id,dborder,linktype,url,description,category) VALUES $sql") or die($db->sql_error());
	}

	if (isset($dvd_info['STUDIOS'][0]['STUDIO'])) {
		$dborder = 0;
		$sql = '';
		foreach ($dvd_info['STUDIOS'][0]['STUDIO'] as $num_s => $studio_info) {
			$dborder++;
			$s = (isset($studio_info['VALUE'])) ? $db->sql_escape($studio_info['VALUE']) : '';
			if ($sql != '') $sql .= ',';
			$sql .= "('$TheProfileID',0,$dborder,'$s')";
		}
		if ($sql != '')
			$db->sql_query("INSERT INTO $DVD_STUDIO_TABLE (id,ismediacompany,dborder,studio) VALUES $sql") or die($db->sql_error());
	}
	if (isset($dvd_info['MEDIACOMPANIES'][0]['MEDIACOMPANY'])) {
		$dborder = 0;
		$sql = '';
		foreach ($dvd_info['MEDIACOMPANIES'][0]['MEDIACOMPANY'] as $num_s => $studio_info) {
			$dborder++;
			$s = (isset($studio_info['VALUE'])) ? $db->sql_escape($studio_info['VALUE']) : '';
			if ($sql != '') $sql .= ',';
			$sql .= "('$TheProfileID',1,$dborder,'$s')";
		}
		if ($sql != '')
			$db->sql_query("INSERT INTO $DVD_STUDIO_TABLE (id,ismediacompany,dborder,studio) VALUES $sql") or die($db->sql_error());
	}
	if (!empty($dvd_info['MEDIAPUBLISHER'][0]['VALUE'])) {
		$db->sql_query("INSERT INTO $DVD_STUDIO_TABLE (id,ismediacompany,dborder,studio) VALUES ('$TheProfileID',1,1,'".$dvd_info['MEDIAPUBLISHER'][0]['VALUE']."')") or die($db->sql_error());
	}

	if (isset($dvd_info['SUBTITLES'][0]['SUBTITLE'])) {
		$sql = '';
		foreach ($dvd_info['SUBTITLES'][0]['SUBTITLE'] as $num_s => $subtitle_info) {
			$s = (isset($subtitle_info['VALUE'])) ? $db->sql_escape($subtitle_info['VALUE']) : '';
			if ($sql != '') $sql .= ',';
			$sql .= "('$TheProfileID','$s')";
		}
		if ($sql != '')
			$db->sql_query("INSERT INTO $DVD_SUBTITLE_TABLE (id,subtitle) VALUES $sql") or die($db->sql_error());
	}

 	if (isset($dvd_info['EVENTS'][0]['EVENT'])) {
		$sql = '';
 		foreach ($dvd_info['EVENTS'][0]['EVENT'] as $num_s => $event_info) {
 			$f = (isset($event_info['USER'][0]['ATTRIBUTES']['FIRSTNAME'])) ? $db->sql_escape($event_info['USER'][0]['ATTRIBUTES']['FIRSTNAME']) : '';
 			$l = (isset($event_info['USER'][0]['ATTRIBUTES']['LASTNAME'])) ? $db->sql_escape($event_info['USER'][0]['ATTRIBUTES']['LASTNAME']) : '';
 			$e = (isset($event_info['USER'][0]['ATTRIBUTES']['EMAILADDRESS'])) ? $db->sql_escape($event_info['USER'][0]['ATTRIBUTES']['EMAILADDRESS']) : '';
 			$p = (isset($event_info['USER'][0]['ATTRIBUTES']['PHONENUMBER'])) ? $db->sql_escape($event_info['USER'][0]['ATTRIBUTES']['PHONENUMBER']) : '';
 			$n = (isset($event_info['NOTE'][0]['VALUE'])) ? $db->sql_escape($event_info['NOTE'][0]['VALUE']) : '';
 			$t = (isset($event_info['EVENTTYPE'][0]['VALUE'])) ? $event_info['EVENTTYPE'][0]['VALUE'] : '';
 			$d = (isset($event_info['TIMESTAMP'][0]['VALUE'])) ? TranslateDateTime($event_info['TIMESTAMP'][0]['VALUE']) : '';
			$u = GetAUserIDFor($f, $l, $e, $p);
			if ($sql != '') $sql .= ',';
			$sql .= "('$TheProfileID','$u','$t','$n','$d')";
			if ($AddWatchedEventWhenReturned && $t=='Returned') {
				$sql .= ",('$TheProfileID','$u','Watched','$n','$d')";
			}
 		}
		if ($sql != '')
 			$db->sql_query("INSERT INTO $DVD_EVENTS_TABLE (id,uid,eventtype,note,timestamp) VALUES $sql") or die($db->sql_error());
 		unset($event);
 	}

	if (isset($dvd_info['AUDIO'][0]['AUDIOTRACK'])) {
		$dborder = 0;
		$sql = '';
		foreach ($dvd_info['AUDIO'][0]['AUDIOTRACK'] as $num_b => $audio_info) {
			$dborder++;
			$c = (isset($audio_info['AUDIOCONTENT'][0]['VALUE'])) ? $db->sql_escape($audio_info['AUDIOCONTENT'][0]['VALUE']) : '';
			$f = (isset($audio_info['AUDIOFORMAT'][0]['VALUE'])) ? $db->sql_escape($audio_info['AUDIOFORMAT'][0]['VALUE']) : '';
			$h = (isset($audio_info['AUDIOCHANNELS'][0]['VALUE'])) ? $db->sql_escape($audio_info['AUDIOCHANNELS'][0]['VALUE']) : '';
			if ($sql != '') $sql .= ',';
			if (isset($audio_info['AUDIOCHANNELS']))
				$sql .= "('$TheProfileID',$dborder,'$c','$f','$h')";
			else
				$sql .= "('$TheProfileID',$dborder,'$c','$f',NULL)";
		}
		if ($sql != '')
			$db->sql_query("INSERT INTO $DVD_AUDIO_TABLE (id,dborder,audiocontent,audioformat,audiochannels) VALUES $sql") or die($db->sql_error());
	}

	if (isset($dvd_info['DISCS'][0]['DISC'])) {
		$discno = 0;
		$fields  = '(id,discno,discdescsidea,discdescsideb,discidsidea,discidsideb,labelsidea,labelsideb,duallayeredsidea,duallayeredsideb,dualsided,location,slot)';
		$sql = '';
		foreach ($dvd_info['DISCS'][0]['DISC'] as $num_s => $disc_info) {
			if ($sql != '') $sql .= ',';
			$discno++;
			$sql .= "('$TheProfileID'";
			$sql .= ",$discno";
			$sql .= ",'" . StringIfThere($disc_info['DESCRIPTIONSIDEA'][0]['VALUE']) . "'";
			$sql .= ",'" . StringIfThere($disc_info['DESCRIPTIONSIDEB'][0]['VALUE']) . "'";
			$sql .= ",'" . StringIfThere($disc_info['DISCIDSIDEA'][0]['VALUE']) . "'";
			$sql .= ",'" . StringIfThere($disc_info['DISCIDSIDEB'][0]['VALUE']) . "'";
			$sql .= ",'" . StringIfThere($disc_info['LABELSIDEA'][0]['VALUE']) . "'";
			$sql .= ",'" . StringIfThere($disc_info['LABELSIDEB'][0]['VALUE']) . "'";
			$sql .= ','; $sql .= (isset($disc_info['DUALLAYEREDSIDEA'][0]['VALUE'])) ? TrueFalse($disc_info['DUALLAYEREDSIDEA'][0]['VALUE']) : "''";
			$sql .= ','; $sql .= (isset($disc_info['DUALLAYEREDSIDEB'][0]['VALUE'])) ? TrueFalse($disc_info['DUALLAYEREDSIDEB'][0]['VALUE']) : "''";
			$sql .= ','; $sql .= (isset($disc_info['DUALSIDED'][0]['VALUE'])) ? TrueFalse($disc_info['DUALSIDED'][0]['VALUE']) : "''";
			$sql .= ",'" . StringIfThere($disc_info['LOCATION'][0]['VALUE']) . "'";
			$sql .= ",'" . StringIfThere($disc_info['SLOT'][0]['VALUE']) . "'";
			$sql .= ')';

		}
		$db->sql_query("INSERT INTO $DVD_DISCS_TABLE $fields VALUES $sql") or die($db->sql_error());
	}

	if (isset($dvd_info['LOCKS'][0])) {
		$f  = 'id';			$v  =       "'$TheProfileID'";
		$f .= ',entire';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['ENTIRE'][0]['VALUE']);
		$f .= ',covers';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['COVERS'][0]['VALUE']);
		$f .= ',title';			$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['TITLE'][0]['VALUE']);
		$f .= ',mediatype';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['MEDIATYPE'][0]['VALUE']);
		$f .= ',overview';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['OVERVIEW'][0]['VALUE']);
		$f .= ',regions';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['REGIONS'][0]['VALUE']);
		$f .= ',genres';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['GENRES'][0]['VALUE']);
		$f .= ',srp';			$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['SRP'][0]['VALUE']);
		$f .= ',studios';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['STUDIOS'][0]['VALUE']);
		$f .= ',discinfo';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['DISCINFORMATION'][0]['VALUE']);
		$f .= ',cast';			$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['CAST'][0]['VALUE']);
		$f .= ',crew';			$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['CREW'][0]['VALUE']);
		$f .= ',features';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['FEATURES'][0]['VALUE']);
		$f .= ',audio';			$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['AUDIOTRACKS'][0]['VALUE']);
		$f .= ',subtitles';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['SUBTITLES'][0]['VALUE']);
		$f .= ',eastereggs';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['EASTEREGGS'][0]['VALUE']);
		$f .= ',runningtime';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['RUNNINGTIME'][0]['VALUE']);
		$f .= ',releasedate';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['RELEASEDATE'][0]['VALUE']);
		$f .= ',productionyear';	$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['PRODUCTIONYEAR'][0]['VALUE']);
		$f .= ',casetype';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['CASETYPE'][0]['VALUE']);
		$f .= ',videoformats';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['VIDEOFORMATS'][0]['VALUE']);
		$f .= ',rating';		$v .= ',' . TrueFalse($dvd_info['LOCKS'][0]['RATING'][0]['VALUE']);
		$db->sql_query("INSERT INTO $DVD_LOCKS_TABLE ($f) VALUES ($v)") or die($db->sql_error());
	}

	if ($hashs['hashcast'] != $oldhashs['hashcast']) {
// Process Actors into Common name table and create cast list
		InitialiseCommonTable('common_actor');
		$hints = false;
		if (!$all_in_one_go)
			$hints = array();
		$bi = new BufferedInsert($db, $max_packet, $DVD_ACTOR_TABLE, '(id,lineno,caid,creditedas,role,voice,uncredited)');
		if (isset($dvd_info['ACTORS'][0]['DIVIDER'])) {
			foreach ($dvd_info['ACTORS'][0]['DIVIDER'] as $num_a => $actor_info) {
				if (!isset($actor_info['ATTRIBUTES']['TYPE'])) {
					$caid = -1;
				}
				else if ($actor_info['ATTRIBUTES']['TYPE'] == 'Episode') {
					$caid = -1;
				}
				else if ($actor_info['ATTRIBUTES']['TYPE'] == 'Group') {
					$caid = -2;
				}
				else if ($actor_info['ATTRIBUTES']['TYPE'] == 'EndDiv') {
					$caid = -3;
				}
				$toadd =  '('
					. "'$TheProfileID',"
					. "$actor_info[LINENO],"
					. "$caid,"
					. "'" . $db->sql_escape($actor_info['ATTRIBUTES']['CAPTION']) . "',"
					. "'',"
					. "0,"
					. "0"
					. ')';
				$bi->add_element($toadd);
			}
		}
		if (isset($dvd_info['ACTORS'][0]['ACTOR'])) {
			foreach ($dvd_info['ACTORS'][0]['ACTOR'] as $num_a => $actor_info) {
				$aid = GetAnIDFor(
					$actor_info['ATTRIBUTES']['FIRSTNAME'],
					$actor_info['ATTRIBUTES']['MIDDLENAME'],
					$actor_info['ATTRIBUTES']['LASTNAME'],
					$actor_info['ATTRIBUTES']['BIRTHYEAR'],
					$DVD_COMMON_ACTOR_TABLE,
					$hints
				);
				$toadd =  '('
					. "'$TheProfileID',"
					. "$actor_info[LINENO],"
					. "$aid,"
					. "'" . $db->sql_escape($actor_info['ATTRIBUTES']['CREDITEDAS']) . "',"
					. "'" . $db->sql_escape($actor_info['ATTRIBUTES']['ROLE']) . "',"
					. TrueFalse($actor_info['ATTRIBUTES']['VOICE']) . ","
					. TrueFalse($actor_info['ATTRIBUTES']['UNCREDITED'])
					. ')';
				$bi->add_element($toadd);
			}
		}
		$bi->flush();
		unset($bi);
		if ($db_fast_update && !$all_in_one_go && count($hints) != 0) {
			UpdateCommonTableFromMemory($common_actor, $common_actor_stats, $DVD_COMMON_ACTOR_TABLE, $hints);
		}
		unset($hints);
	}	// if hashs don't match

	if ($hashs['hashcrew'] != $oldhashs['hashcrew']) {
// Process Crew into Common name table and create crew list
		InitialiseCommonTable('common_credit');
		$hints = false;
		if (!$all_in_one_go)
			$hints = array();
		$bi = new BufferedInsert($db, $max_packet, $DVD_CREDITS_TABLE, '(id,lineno,caid,creditedas,credittype,creditsubtype,customrole)');
		if (isset($dvd_info['CREDITS'][0]['DIVIDER'])) {
			foreach ($dvd_info['CREDITS'][0]['DIVIDER'] as $num_a => $credit_info) {
				$divtype = '';
				if (!isset($credit_info['ATTRIBUTES']['TYPE'])) {
					$caid = -1;
				}
				else if ($credit_info['ATTRIBUTES']['TYPE'] == 'Episode') {
					$caid = -1;
				}
				else if ($credit_info['ATTRIBUTES']['TYPE'] == 'Group') {
					$divtyp = @$credit_info['ATTRIBUTES']['CREDITTYPE'];	// if there is no credittype info, we treat it like an enddiv...
					$caid = -2;
				}
				else if ($credit_info['ATTRIBUTES']['TYPE'] == 'EndDiv') {
					$caid = -3;
				}
				$toadd =  '('
					. "'$TheProfileID',"
					. "$credit_info[LINENO],"
					. "$caid,"
					. "'" . $db->sql_escape($credit_info['ATTRIBUTES']['CAPTION']) . "',"
					. "'$divtype',"
					. "'',"
					. "''"
					. ')';
				$bi->add_element($toadd);
			}
		}
		if (isset($dvd_info['CREDITS'][0]['CREDIT'])) {
			$prdir = array();
			foreach ($dvd_info['CREDITS'][0]['CREDIT'] as $num_d => $credit_info) {
				$cid = GetAnIDFor(
					$credit_info['ATTRIBUTES']['FIRSTNAME'],
					$credit_info['ATTRIBUTES']['MIDDLENAME'],
					$credit_info['ATTRIBUTES']['LASTNAME'],
					$credit_info['ATTRIBUTES']['BIRTHYEAR'],
					$DVD_COMMON_CREDITS_TABLE,
					$hints
				);
				if ($credit_info['ATTRIBUTES']['CREDITTYPE'] == 'Direction') {
					$fulnam = preg_replace('/\s\s+/', ' ', trim($credit_info['ATTRIBUTES']['FIRSTNAME'] .' '.
										    $credit_info['ATTRIBUTES']['MIDDLENAME'] .' '.
										    $credit_info['ATTRIBUTES']['LASTNAME']));
					if (!isset($prdir[$fulnam]))
						$prdir[$fulnam] = 0;
					$prdir[$fulnam]++;
				}
				$custrole = '';
				if (isset($credit_info['ATTRIBUTES']['CUSTOMROLE']))
					$custrole = $credit_info['ATTRIBUTES']['CUSTOMROLE'];
				$toadd =  '('
					. "'$TheProfileID',"
					. "$credit_info[LINENO],"
					. "$cid,"
					. "'" . $db->sql_escape($credit_info['ATTRIBUTES']['CREDITEDAS']) . "',"
					. "'" . $db->sql_escape($credit_info['ATTRIBUTES']['CREDITTYPE']) . "',"
					. "'" . $db->sql_escape($credit_info['ATTRIBUTES']['CREDITSUBTYPE']) . "',"
					. "'" . $db->sql_escape($custrole) . "'"
					. ')';
				$bi->add_element($toadd);
			}
			if (count($prdir) > 0) {
				arsort($prdir);
				$primedir = key($prdir);
			}
			unset($prdir);
		}
		$bi->flush();
		unset($bi);

		if ($db_fast_update && !$all_in_one_go && count($hints) != 0) {
			UpdateCommonTableFromMemory($common_credit, $common_credit_stats, $DVD_COMMON_CREDITS_TABLE, $hints);
		}
		unset($hints);
	}	// if hashs don't match

	$giftuid = 0;
	$gift = 0;
	if (TrueFalse($dvd_info['PURCHASEINFO'][0]['RECEIVEDASGIFT'][0]['VALUE'])) {
		$gift = 1;
		$giftuid = GetAUserIDFor($dvd_info['PURCHASEINFO'][0]['GIFTFROM'][0]['ATTRIBUTES']['FIRSTNAME'],
					 $dvd_info['PURCHASEINFO'][0]['GIFTFROM'][0]['ATTRIBUTES']['LASTNAME'],
					 $dvd_info['PURCHASEINFO'][0]['GIFTFROM'][0]['ATTRIBUTES']['EMAILADDRESS'],
					 $dvd_info['PURCHASEINFO'][0]['GIFTFROM'][0]['ATTRIBUTES']['PHONENUMBER']);
	}

// INSERT into array representing the DB structure
// Do this last, as the $uniqueness key that marks the profile as added is in this table. Thus allowing
// restarts, and preventing partial data corruption.
// The XML contains arbitrary strings (eg. Overview), numbers (eg. formatletterbox) and manifest strings (eg. genre)
// The manifest strings are all in English. They will be put into the database in English, and the translations will
// be done when displaying them.

	$casetype = !empty($dvd_info['CASETYPE'][0]['VALUE']) ? $dvd_info['CASETYPE'][0]['VALUE'] : 'Unknown';
	$mediatypedvd = TrueFalse($dvd_info['MEDIATYPES'][0]['DVD'][0]['VALUE']);
	$mediatypehddvd = TrueFalse($dvd_info['MEDIATYPES'][0]['HDDVD'][0]['VALUE']);
	$mediatypebluray = TrueFalse($dvd_info['MEDIATYPES'][0]['BLURAY'][0]['VALUE']);
	$mediatypeultrahd = TrueFalse($dvd_info['MEDIATYPES'][0]['ULTRAHD'][0]['VALUE']);
	$builtinmediatype = FigureOutBuiltinMediaType($mediatypedvd, $mediatypehddvd, $mediatypebluray, $mediatypeultrahd);
	$custommediatype = (isset($dvd_info['MEDIATYPES'][0]['CUSTOMMEDIATYPE'][0]['VALUE']) ? $db->sql_escape($dvd_info['MEDIATYPES'][0]['CUSTOMMEDIATYPE'][0]['VALUE']) : '');

	$TheDVDTitle = $db->sql_escape($dvd_info['TITLE'][0]['VALUE']);
	$f  = 'id';				$v  = "'$TheProfileID'";
	$f .= ',upc';				$v .= ",'" . $dvd_info['UPC'][0]['VALUE'] . "'";
	$f .= ',builtinmediatype';		$v .= ','  . $builtinmediatype;
	$f .= ',custommediatype';		$v .= ",'$custommediatype'";
	$f .= ',mediabannerfront';		$v .= ','  . OnOffAuto($dvd_info['MEDIABANNERS'][0]['ATTRIBUTES']['FRONT'], 'f', $caseslipcover, $casetype, $builtinmediatype, $custommediatype);
	$f .= ',mediabannerback';		$v .= ','  . OnOffAuto($dvd_info['MEDIABANNERS'][0]['ATTRIBUTES']['BACK'], 'b', $caseslipcover, $casetype, $builtinmediatype, $custommediatype);
	$f .= ',title';				$v .= ",'$TheDVDTitle'";
	$f .= ',sorttitle';			$v .= ",'" . StringIfThere($dvd_info['SORTTITLE'][0]['VALUE'], $TheDVDTitle) . "'";
	$f .= ',originaltitle';			$v .= ",'" . StringIfThere($dvd_info['ORIGINALTITLE'][0]['VALUE']) . "'";
	$f .= ',description';			$v .= ",'" . StringIfThere($dvd_info['DISTTRAIT'][0]['VALUE']) . "'";
	$f .= ',countryoforigin';		$v .= ",'" . StringIfThere($dvd_info['COUNTRYOFORIGIN'][0]['VALUE']) . "'";
	$f .= ',countryoforigin2';		$v .= ",'" . StringIfThere($dvd_info['COUNTRYOFORIGIN2'][0]['VALUE']) . "'";
	$f .= ',countryoforigin3';		$v .= ",'" . StringIfThere($dvd_info['COUNTRYOFORIGIN3'][0]['VALUE']) . "'";
	$f .= ',region';			$v .= ",'$region'";
	$f .= ',collectiontype';		$v .= ",'" . $db->sql_escape($collectiontype) . "'";
	$f .= ',realcollectiontype';		$v .= ",'" . $db->sql_escape($realcollectiontype) . "'";
	$f .= ',auxcolltype';			$v .= ",'" . $db->sql_escape($auxcolltype) . "'";
	$f .= ',collectionnumber';		$v .= ','  . (isset($dvd_info['COLLECTIONNUMBER'][0]['VALUE']) ? intval($dvd_info['COLLECTIONNUMBER'][0]['VALUE']) : 0);
	$f .= ',rating';			$v .= ",'" . (!empty($dvd_info['RATING'][0]['VALUE']) ? $db->sql_escape($dvd_info['RATING'][0]['VALUE']) : 'NR') . "'";
	$f .= ',ratingsystem';			$v .= ",'" . (!empty($dvd_info['RATINGSYSTEM'][0]['VALUE']) ? $db->sql_escape($dvd_info['RATINGSYSTEM'][0]['VALUE']) : '') . "'";
	$f .= ',ratingage';			$v .= ",'" . (isset($dvd_info['RATINGAGE'][0]['VALUE']) ? $db->sql_escape($dvd_info['RATINGAGE'][0]['VALUE']) : '') . "'";
	$f .= ',ratingvariant';			$v .= ",'" . (!empty($dvd_info['RATINGVARIANT'][0]['VALUE']) ? $db->sql_escape($dvd_info['RATINGVARIANT'][0]['VALUE']) : '') . "'";
	$f .= ',ratingdetails';			$v .= ",'" . (!empty($dvd_info['RATINGDETAILS'][0]['VALUE']) ? $db->sql_escape($dvd_info['RATINGDETAILS'][0]['VALUE']) : '') . "'";
	$f .= ',productionyear';		$v .= ",'" . StringIfThere($dvd_info['PRODUCTIONYEAR'][0]['VALUE']) . "'";
	$f .= ',released';			$v .= "," . MakeAUnixTime(@$dvd_info['RELEASED'][0]['VALUE'], 'NULL');
	$f .= ',runningtime';			$v .= ','  . (isset($dvd_info['RUNNINGTIME'][0]['VALUE']) ? $dvd_info['RUNNINGTIME'][0]['VALUE'] : 0);
	$f .= ',casetype';			$v .= ",'$casetype'";
	$f .= ',caseslipcover';			$v .= ','  . $caseslipcover;
	$f .= ',isadulttitle';			$v .= ','  . $isadulttitle;

	$fvs = StringIfThere($dvd_info['FORMAT'][0]['FORMATVIDEOSTANDARD'][0]['VALUE']);
	if ($builtinmediatype == MEDIA_TYPE_BLURAY || $builtinmediatype == MEDIA_TYPE_HDDVD || MEDIA_TYPE_ULTRAHD)	// DVDProfiler stuffs NTSC rather than blanks
		$fvs = '';
	$f .= ',formatvideostandard';	$v .= ",'$fvs'";
	$f .= ',formatletterbox';		$v .= ',' . $formatletterbox;
	$f .= ',format16x9';			$v .= ',' . $format16x9;
	$f .= ',formataspectratio';		$v .= ",'$formataspectratio'";
	$f .= ',formatcolorcolor';		$v .= ',' . $fmcc;
	$f .= ',formatcolorbw';			$v .= ',' . $fmcb;
	$f .= ',formatcolorcolorized';	$v .= ',' . $fmccz;
	$f .= ',formatcolormixed';		$v .= ',' . $fmcm;
	$f .= ',formatpanandscan';		$v .= ','  . TrueFalse($dvd_info['FORMAT'][0]['FORMATPANANDSCAN'][0]['VALUE']);
	$f .= ',formatfullframe';		$v .= ','  . TrueFalse($dvd_info['FORMAT'][0]['FORMATFULLFRAME'][0]['VALUE']);
	$f .= ',formatdualsided';		$v .= ','  . TrueFalse($dvd_info['FORMAT'][0]['FORMATDUALSIDED'][0]['VALUE']);
	$f .= ',formatduallayered';		$v .= ','  . TrueFalse($dvd_info['FORMAT'][0]['FORMATDUALLAYERED'][0]['VALUE']);
	$f .= ',dim2d';					$v .= ','  . @TrueFalse($dvd_info['FORMAT'][0]['DIMENSIONS'][0]['DIM2D'][0]['VALUE']);
	$f .= ',dim3danaglyph';			$v .= ','  . @TrueFalse($dvd_info['FORMAT'][0]['DIMENSIONS'][0]['DIM3DANAGLYPH'][0]['VALUE']);
	$f .= ',dim3dbluray';			$v .= ','  . @TrueFalse($dvd_info['FORMAT'][0]['DIMENSIONS'][0]['DIM3DBLURAY'][0]['VALUE']);
	$f .= ',drhdr10';				$v .= ','  . @TrueFalse($dvd_info['FORMAT'][0]['DYNAMICRANGE'][0]['DRHDR10'][0]['VALUE']);
	$f .= ',drdolbyvision';			$v .= ','  . @TrueFalse($dvd_info['FORMAT'][0]['DYNAMICRANGE'][0]['DRDOLBYVISION'][0]['VALUE']);

	$f .= ',featuresceneaccess';	$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATURESCENEACCESS'][0]['VALUE']);
	$f .= ',featuretrailer';		$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATURETRAILER'][0]['VALUE']);
	$f .= ',featurebonustrailers';	$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREBONUSTRAILERS'][0]['VALUE']);
	$f .= ',featuremakingof';		$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREMAKINGOF'][0]['VALUE']);
	$f .= ',featurecommentary';		$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATURECOMMENTARY'][0]['VALUE']);
	$f .= ',featuredeletedscenes';	$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREDELETEDSCENES'][0]['VALUE']);
	$f .= ',featureinterviews';		$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREINTERVIEWS'][0]['VALUE']);
	$f .= ',featureouttakes';		$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREOUTTAKES'][0]['VALUE']);
	$f .= ',featurestoryboardcomparisons';	$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATURESTORYBOARDCOMPARISONS'][0]['VALUE']);
	$f .= ',featurephotogallery';	$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREPHOTOGALLERY'][0]['VALUE']);
	$f .= ',featureproductionnotes';$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREPRODUCTIONNOTES'][0]['VALUE']);
	$f .= ',featuredvdromcontent';	$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREDVDROMCONTENT'][0]['VALUE']);
	$f .= ',featuregame';			$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREGAME'][0]['VALUE']);
	$f .= ',featuremultiangle';		$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREMULTIANGLE'][0]['VALUE']);
	$f .= ',featuremusicvideos';	$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREMUSICVIDEOS'][0]['VALUE']);
	$f .= ',featurethxcertified';	$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATURETHXCERTIFIED'][0]['VALUE']);
	$f .= ',featureclosedcaptioned';$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATURECLOSEDCAPTIONED'][0]['VALUE']);
	$f .= ',featuredigitalcopy';	$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREDIGITALCOPY'][0]['VALUE']);
	$f .= ',featurepip';			$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREPIP'][0]['VALUE']);
	$f .= ',featurebdlive';			$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREBDLIVE'][0]['VALUE']);
	$f .= ',featureother';			$v .= ",'" . StringIfThere($dvd_info['FEATURES'][0]['OTHERFEATURES'][0]['VALUE']) . "'";

	if (version_compare($db_schema_version, '2.8') >= 0) {
		$f .= ',featureplayall';		$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREPLAYALL'][0]['VALUE']);
		$f .= ',featuredbox';			$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREDBOX'][0]['VALUE']);
		$f .= ',featurecinechat';		$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATURECINECHAT'][0]['VALUE']);
		$f .= ',featuremovieiq';		$v .= ','  . TrueFalse($dvd_info['FEATURES'][0]['FEATUREMOVIEIQ'][0]['VALUE']);
	}

	$f .= ',reviewfilm';			$v .= ','  . $dvd_info['REVIEW'][0]['ATTRIBUTES']['FILM'];
	$f .= ',reviewvideo';			$v .= ','  . $dvd_info['REVIEW'][0]['ATTRIBUTES']['VIDEO'];
	$f .= ',reviewaudio';			$v .= ','  . $dvd_info['REVIEW'][0]['ATTRIBUTES']['AUDIO'];
	$f .= ',reviewextras';			$v .= ','  . $dvd_info['REVIEW'][0]['ATTRIBUTES']['EXTRAS'];

	$f .= ',srp';				$v .= ",'$srp'";
	$f .= ',srpcurrencyid';			$v .= ",'" . StringIfThere($dvd_info['SRP'][0]['ATTRIBUTES']['DENOMINATIONTYPE']) . "'";
	$f .= ',srpcurrencyname';		$v .= ",'" . StringIfThere($dvd_info['SRP'][0]['ATTRIBUTES']['DENOMINATIONDESC']) . "'";
	$f .= ',srpdec';			$v .= ",'$srpdec'";
	$f .= ',gift';				$v .= ','  . $gift;
	$f .= ',giftuid';			$v .= ','  . $giftuid;
	$f .= ',purchaseprice';			$v .= ",'$purchaseprice'";
	$f .= ',purchasepricecurrencyid';	$v .= ",'" . StringIfThere($dvd_info['PURCHASEINFO'][0]['PURCHASEPRICE'][0]['ATTRIBUTES']['DENOMINATIONTYPE']) . "'";
	$f .= ',purchasepricecurrencyname';	$v .= ",'" . StringIfThere($dvd_info['PURCHASEINFO'][0]['PURCHASEPRICE'][0]['ATTRIBUTES']['DENOMINATIONDESC']) . "'";
	$f .= ',paid';				$v .= ",'$paid'";
	$f .= ',purchasedate';			$v .= ','  . ($wishpriority==0 ? MakeAUnixTime(@$dvd_info['PURCHASEINFO'][0]['PURCHASEDATE'][0]['VALUE'], 0) : 0);
	$f .= ',purchaseplace';			$v .= ','  . $sid;

	$f .= ',notes';				$v .= ",'" . $db->sql_escape($notes) . "'";
	$f .= ',wishpriority';			$v .= ','  . $wishpriority;
	$f .= ',loaninfo';			$v .= ",'" . (TrueFalse($dvd_info['LOANINFO'][0]['LOANED'][0]['VALUE']) ? $db->sql_escape($dvd_info['LOANINFO'][0]['USER'][0]['ATTRIBUTES']['FIRSTNAME'] . ' ' . $dvd_info['LOANINFO'][0]['USER'][0]['ATTRIBUTES']['LASTNAME']) : '') . "'";
	$f .= ',loandue';			$v .= ','  . MakeAUnixTime(@$dvd_info['LOANINFO'][0]['DUE'][0]['VALUE'], 0);
	$f .= ',overview';			$v .= ",'" . StringIfThere($dvd_info['OVERVIEW'][0]['VALUE']) . "'";
	$f .= ',countas';			$v .= ','  . (isset($dvd_info['COUNTAS'][0]['VALUE']) ? intval($dvd_info['COUNTAS'][0]['VALUE']) : 1);
	$f .= ',eastereggs';			$v .= ",'" . StringIfThere($dvd_info['EASTEREGGS'][0]['VALUE']) . "'";
	$f .= ',primegenre';			$v .= ",'" . $db->sql_escape($primegenre) . "'";
	if ($hashs['hashcrew'] != $oldhashs['hashcrew']) {	// don't delete existing primedir if there was no change in crew
		$f .= ',primedirector';			$v .= ",'" . $db->sql_escape($primedir) . "'";
	}
	$f .= ',boxparent';			$v .= ",''";
	$f .= ',boxchild';			$v .= ','  . (isset($dvd_info['BOXSET'][0]['CONTENTS'][0]['CONTENT'][0]['VALUE']) ? 1 : 0);
	$f .= ',hashprofile';			$v .= ",'$hashs[hashprofile]'";
	$f .= ',hashnocolid';			$v .= ",'$hashs[hashnocolid]'";
	$f .= ',hashcast';			$v .= ",'$hashs[hashcast]'";
	$f .= ',hashcrew';			$v .= ",'$hashs[hashcrew]'";
	$f .= ',lastedited';			$v .=','   . MakeAUnixTime(@$dvd_info['LASTEDITED'][0]['VALUE'], 0);

	$db->sql_query("INSERT INTO $DVD_TABLE ($f) VALUES ($v)") or die($db->sql_error());
	$db->sql_transaction('commit') or die($db->sql_error());
	$db->sql_query("SET autocommit=1;") or die($db->sql_error());
	return(0);
}

// This routine will not catch the case where a boxset child has been added to a pre-existing boxset
function UpdateBoxSets() {
global $db, $lang, $eoln, $showbadboxsetnames, $DVD_TABLE, $DVD_BOXSET_TABLE, $MadeAChange, $force_cleanup;

	if (!$MadeAChange && !$force_cleanup)
		return;
	$numbadentries = 0;
	$numbadsets = 0;
	$lastbadparent = '';

// Find and remove BoxSet entries where the child id isn't in the main table
 	$result = $db->sql_query("SELECT * FROM $DVD_BOXSET_TABLE ORDER BY id") or die($db->sql_error());
 	while ($pair = $db->sql_fetchrow($result)) {
 		$result1 = $db->sql_query("SELECT COUNT(*) AS numentries FROM $DVD_TABLE WHERE id='$pair[child]'") or die($db->sql_error());
 		$answer = $db->sql_fetchrow($result1);
 		$db->sql_freeresult($result1);
 		if ($answer['numentries'] == 0) {
 			$db->sql_query("DELETE FROM $DVD_BOXSET_TABLE WHERE id='$pair[id]' AND child='$pair[child]'") or die($db->sql_error());
			$numbadentries++;
			if ($lastbadparent != $pair['id']) {
				$lastbadparent = $pair['id'];
				$numbadsets++;
				if ($showbadboxsetnames) {
 					$result1 = $db->sql_query("SELECT title FROM $DVD_TABLE WHERE id='$lastbadparent'") or die($db->sql_error());
 					$answer = $db->sql_fetchrow($result1);
 					$db->sql_freeresult($result1);
					if ($numbadsets == 1)
						echo $lang['IMPORTBADBOXDESC'] . $eoln;
					echo "$lang[IMPORTBADBOX]$answer[title] ($lastbadparent)$eoln";
				}
			}
 		}
 	}
	if ($numbadentries > 0) {
		echo $lang['IMPORTENTRIES'] . $numbadentries . $eoln;
		echo $lang['IMPORTCOLLECTIONS'] . $numbadsets . $eoln;
	}

// Update the main table to set the child's parentpointer
	$result = $db->sql_query("SELECT * FROM $DVD_BOXSET_TABLE") or die($db->sql_error());
	while ($pair = $db->sql_fetchrow($result)) {
		$db->sql_query("UPDATE $DVD_TABLE SET boxparent='$pair[id]' WHERE id='$pair[child]'") or die($db->sql_error());
	}
	$db->sql_freeresult($result);

// Update the main table so that the box child member reflects the number of children
	$result = $db->sql_query("SELECT DISTINCT id FROM $DVD_BOXSET_TABLE") or die($db->sql_error());
	while ($pair = $db->sql_fetchrow($result)) {
		$parent = $pair['id'];
		$numkids = 0;
		$result1 = $db->sql_query("SELECT COUNT(*) AS numkids FROM $DVD_TABLE d,$DVD_BOXSET_TABLE b WHERE d.id=b.child and b.id='$parent'") or die($db->sql_error());
		$answer = $db->sql_fetchrow($result1);
		$db->sql_freeresult($result1);

		$db->sql_query("UPDATE $DVD_TABLE SET boxchild=$answer[numkids] WHERE id='$parent'") or die($db->sql_error());
	}
	$db->sql_freeresult($result);
}

function UpdateStats($TryToChangeMemoryAndTimeLimits) {
global $DVD_TABLE, $DVD_STATS_TABLE, $DVD_ACTOR_TABLE, $DVD_GENRES_TABLE, $DVD_CREDITS_TABLE, $DVD_AUDIO_TABLE, $DVD_PROPERTIES_TABLE;
global $lang, $db, $MaxX, $MadeAChange, $inbrowser, $eoln, $ProfileStatistics, $force_cleanup;
global $usetemptable, $shortestspecialcondition, $runtimespecialcondition, $regionspecialcondition;
global $audiospecialcondition, $Highlight_Last_X_PurchaseDates, $UpdateLast, $MyConnectionId;

	if (!$MadeAChange && !$force_cleanup)
		return;

	$result = $db->sql_query("SELECT DISTINCT purchasedate FROM $DVD_TABLE ORDER BY purchasedate DESC LIMIT $Highlight_Last_X_PurchaseDates") or die($db->sql_error());
	$thelist = '';
	while ($row = $db->sql_fetchrow($result)) {
		if ($thelist != '') $thelist .= ',';
		$thelist .= $row['purchasedate'];
	}
	if ($thelist == '') $thelist = '-1';
	$db->sql_freeresult($result);
	$db->sql_query("DELETE FROM $DVD_PROPERTIES_TABLE WHERE property='listofpurchasedates'");
	$db->sql_query("INSERT INTO $DVD_PROPERTIES_TABLE (property,value) VALUES ('listofpurchasedates', '$thelist')");

	$db->sql_query("DELETE FROM $DVD_STATS_TABLE") or die($db->sql_error());
	$noadulttitles = false;
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.1||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

	if (TableCopyingSupported()) {
		if ($TryToChangeMemoryAndTimeLimits) set_time_limit(0);
		$result = $db->sql_query("SELECT COUNT(*) AS num FROM $DVD_TABLE WHERE isadulttitle=1") or die($db->sql_error());
		$answer = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		$noadulttitles = $answer['num'] == 0;
		unset($answer);
	}

//	$hsq = MySQLHasSubQueries();
	$hsq = false;	// subqueries are far slower in this application
	if ($hsq) {
		$NOTVTable = ",$DVD_GENRES_TABLE g";
		$NOTVQuery = " AND g.id=d.id AND (SELECT COUNT(*) FROM $DVD_GENRES_TABLE g WHERE g.id=d.id AND genre='Television')=0";
	}
	else {
		$NOTVTable = '';
		$NOTVQuery = '';
		$tmp = '(';
		$result = $db->sql_query("SELECT DISTINCT id FROM $DVD_GENRES_TABLE WHERE genre='Television'") or die($db->sql_error());
		while ($zzz = $db->sql_fetch_array($result)) {
			if ($tmp != '(') $tmp .= ',';
			$tmp .= "'$zzz[id]'";
		}
		$db->sql_freeresult($result);
		if ($tmp != '(') {
			$NOTVQuery = " AND d.id NOT IN $tmp)";
		}
	}
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.2||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

	$t0 = microtime_float(); $numtimings = 0;
// Create tables including and excluding Adult titles

	$result = $db->sql_query("SELECT DISTINCT auxcolltype FROM $DVD_TABLE") or die($db->sql_error());
	$masterauxcolltype = '';
	while ($row = $db->sql_fetchrow($result)) {
		$masterauxcolltype .= $row['auxcolltype'];
	}
	$db->sql_freeresult($result);
	$temparray = array_unique(explode('/', $masterauxcolltype));
	sort($temparray);
	$masterauxcolltype = implode('/', $temparray);
	unset($temparray);
	$result = $db->sql_query("SELECT value FROM $DVD_PROPERTIES_TABLE WHERE property='masterauxcolltypeNoAdult'") or die($db->sql_error());
	$answer = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	if ($answer === false) {
		$sql = "INSERT INTO $DVD_PROPERTIES_TABLE SET value='".addslashes($masterauxcolltype)."', property='masterauxcolltypeNoAdult'";
	}
	else {
		$sql = "UPDATE $DVD_PROPERTIES_TABLE SET value='".addslashes($masterauxcolltype)."' WHERE property='masterauxcolltypeNoAdult'";
	}
	$db->sql_query($sql) or die($db->sql_error());
	unset($answer);
	$ProfileName[$numtimings] = 'AuxilliaryCollectionsAdult'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

	if ($noadulttitles) {
		$result = $db->sql_query("SELECT value FROM $DVD_PROPERTIES_TABLE WHERE property='masterauxcolltypeAdult'") or die($db->sql_error());
		$answer = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		if ($answer === false) {
			$sql = "INSERT INTO $DVD_PROPERTIES_TABLE SET value='".addslashes($masterauxcolltype)."', property='masterauxcolltypeAdult'";
		}
		else {
			$sql = "UPDATE $DVD_PROPERTIES_TABLE SET value='".addslashes($masterauxcolltype)."' WHERE property='masterauxcolltypeAdult'";
		}
		$db->sql_query($sql) or die($db->sql_error());
		unset($answer);
	}
	else {
		$result = $db->sql_query("SELECT DISTINCT auxcolltype FROM $DVD_TABLE WHERE isadulttitle=0") or die($db->sql_error());
		$masterauxcolltype = '';
		while ($row = $db->sql_fetchrow($result)) {
			$masterauxcolltype .= $row['auxcolltype'];
		}
		$db->sql_freeresult($result);
		$temparray = array_unique(explode('/', $masterauxcolltype));
		sort($temparray);
		$masterauxcolltype = implode('/', $temparray);
		unset($temparray);
		$result = $db->sql_query("SELECT value FROM $DVD_PROPERTIES_TABLE WHERE property='masterauxcolltypeAdult'") or die($db->sql_error());
		$answer = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		if ($answer === false) {
			$sql = "INSERT INTO $DVD_PROPERTIES_TABLE SET value='".addslashes($masterauxcolltype)."', property='masterauxcolltypeAdult'";
		}
		else {
			$sql = "UPDATE $DVD_PROPERTIES_TABLE SET value='".addslashes($masterauxcolltype)."' WHERE property='masterauxcolltypeAdult'";
		}
		$db->sql_query($sql) or die($db->sql_error());
		unset($answer);
	}
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.3||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());
	$ProfileName[$numtimings] = 'AuxilliaryCollectionsNoAdult'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();
	if ($noadulttitles) $numtimings--;

	$PossibleRegions = '0123456@ABC';
// Profile totals per region (ie. number of discs playable in region 2)
	for ($i=0; $i<strlen($PossibleRegions); $i++) {
		DoSomeStats('Region', false,
			"'".$PossibleRegions{$i}."','','',COUNT(*) AS total FROM $DVD_TABLE ",
			"WHERE region LIKE '%".$PossibleRegions{$i}."%' AND collectiontype='owned' $regionspecialcondition",
			'',
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
	}
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.4||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

// Number of profiles per Audio Format
	DoSomeStats('AudioFormat', false,
		"audioformat,'','',COUNT(*) AS total FROM $DVD_AUDIO_TABLE a, $DVD_TABLE d ",
		"WHERE d.id=a.id AND collectiontype='owned' $audiospecialcondition ",
		"GROUP BY audioformat ORDER BY total DESC",
		$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.5||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

// $MaxX profiles with longest running time
	DoSomeStats('LongTime', true,
		"title,sorttitle,id,runningtime FROM $DVD_TABLE ",
		"WHERE collectiontype='owned' $runtimespecialcondition ",
		"ORDER BY runningtime DESC LIMIT $MaxX",
		$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.6||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

// $MaxX profiles with shortest running time
	DoSomeStats('ShortTime', true,
		"title,sorttitle,id,runningtime FROM $DVD_TABLE ",
		"WHERE collectiontype='owned' $shortestspecialcondition ",
		"ORDER BY runningtime ASC LIMIT $MaxX",
		$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.7||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

// $MaxX profiles with longest running time NO TV
	DoSomeStats('LongTimeNOTV', true,
		"title,sorttitle,d.id,runningtime FROM $DVD_TABLE d$NOTVTable ",
		"WHERE collectiontype='owned' $runtimespecialcondition$NOTVQuery ",
		"ORDER BY runningtime DESC LIMIT $MaxX",
		$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.8||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

// $MaxX profiles with shortest running time NO TV
	DoSomeStats('ShortTimeNOTV', true,
		"title,sorttitle,d.id,runningtime FROM $DVD_TABLE d$NOTVTable ",
		"WHERE collectiontype='owned' $shortestspecialcondition$NOTVQuery ",
		"ORDER BY runningtime ASC LIMIT $MaxX",
		$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.9||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

	if ($usetemptable) {
		$sql = "CREATE TEMPORARY TABLE TEMP_ACTORS ("
			."id char(20) NOT NULL, "
			."caid int, "
            ."uniquetitle varchar(255), "
			."voice tinyint unsigned, "
			."uncredited tinyint unsigned, "
			."boxparent varchar(20), "
			."isadulttitle tinyint, "
			."countas smallint, "
			."KEY(id), KEY(caid)"
			.");";
		$db->sql_query($sql) or die($db->sql_error());
// this needs to be looked at
		$sql = "INSERT IGNORE INTO TEMP_ACTORS SELECT a.id,caid,if(originaltitle != '', originaltitle, title),voice,uncredited,boxparent,isadulttitle,countas FROM $DVD_ACTOR_TABLE a,$DVD_TABLE d "
			."WHERE a.id=d.id AND caid>0 AND collectiontype='owned'";
		$db->sql_query($sql) or die($db->sql_error());
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.10||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());
		$ProfileName[$numtimings] = 'Actors Setup'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// $MaxX most collected Actors
		DoSomeStats('Actors', false,
			"caid,COUNT(id),'',COUNT(DISTINCT(id)) AS times FROM TEMP_ACTORS ",
			"WHERE 1 ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.11||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

// $MaxX most collected Actors excluding voice-only parts
		DoSomeStats('ActorsNV', false,
			"caid,COUNT(id),'',COUNT(DISTINCT(id)) AS times FROM TEMP_ACTORS ",
			"WHERE voice=0 ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.12||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

/*********************************
// $MaxX most collected Actors counting BoxSets as 1
		DoSomeStats('NormActors', false,
			"caid,COUNT(id),'',COUNT(DISTINCT(id)) AS times FROM TEMP_ACTORS ",
			"WHERE boxparent='' ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);

// $MaxX most collected Actors counting BoxSets as 1 excluding voice-only parts
		DoSomeStats('NormActorsNV', false,
			"caid,COUNT(id),'',COUNT(DISTINCT(id)) AS times FROM TEMP_ACTORS ",
			"WHERE boxparent='' AND voice=0 ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
****************************/

// $MaxX most collected Actors NO TV
		DoSomeStats('ActorsNOTV', false,
			"caid,COUNT(d.id),'',COUNT(DISTINCT(d.id)) AS times FROM TEMP_ACTORS d$NOTVTable ",
			"WHERE 1$NOTVQuery ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.13||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

// $MaxX most collected Actors excluding voice-only parts
		DoSomeStats('ActorsNVNOTV', false,
			"caid,COUNT(d.id),'',COUNT(DISTINCT(d.id)) AS times FROM TEMP_ACTORS d$NOTVTable ",
			"WHERE voice=0$NOTVQuery ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.14||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

/*********************************
// $MaxX most collected Actors counting BoxSets as 1 NO TV
		DoSomeStats('NormActorsNOTV', false,
			"caid,COUNT(d.id),'',COUNT(DISTINCT(d.id)) AS times FROM TEMP_ACTORS d$NOTVTable ",
			"WHERE boxparent=''$NOTVQuery ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);

// $MaxX most collected Actors counting BoxSets as 1 excluding voice-only parts NO TV
		DoSomeStats('NormActorsNVNOTV', false,
			"caid,COUNT(d.id),'',COUNT(DISTINCT(d.id)) AS times FROM TEMP_ACTORS d$NOTVTable ",
			"WHERE boxparent='' AND voice=0$NOTVQuery ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
****************************/

// $MaxX most collected Actors OriginalTitle
		DoSomeStats('ActorsOR', false,
			"caid,COUNT(id),'',COUNT(DISTINCT(uniquetitle)) AS times FROM TEMP_ACTORS ",
			"WHERE 1 ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.15||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

// $MaxX most collected Actors OriginalTitle NO TV
		DoSomeStats('ActorsORNOTV', false,
			"caid,COUNT(d.id),'',COUNT(DISTINCT(uniquetitle)) AS times FROM TEMP_ACTORS d$NOTVTable ",
			"WHERE 1$NOTVQuery ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.16||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

	}
	else {
// $MaxX most collected Actors
		DoSomeStats('Actors', false,
			"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_ACTOR_TABLE a,$DVD_TABLE d ",
			"WHERE a.id=d.id AND caid>0 AND collectiontype='owned' ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.17||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

// $MaxX most collected Actors excluding voice-only parts
		DoSomeStats('ActorsNV', false,
			"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_ACTOR_TABLE a,$DVD_TABLE d ",
			"WHERE a.id=d.id AND caid>0 AND collectiontype='owned' AND voice=0 ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.18||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

/************************************
// $MaxX most collected Actors counting BoxSets as 1
		DoSomeStats('NormActors', false,
			"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_ACTOR_TABLE a,$DVD_TABLE d ",
			"WHERE boxparent='' AND a.id=d.id AND caid>0 AND collectiontype='owned' ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);

// $MaxX most collected Actors counting BoxSets as 1 excluding voice-only parts
		DoSomeStats('NormActorsNV', false,
			"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_ACTOR_TABLE a,$DVD_TABLE d ",
			"WHERE boxparent='' AND a.id=d.id AND caid>0 AND voice=0 AND collectiontype='owned' ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
********************************/

// $MaxX most collected Actors NO TV
		DoSomeStats('ActorsNOTV', false,
			"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_ACTOR_TABLE a,$DVD_TABLE d$NOTVTable ",
			"WHERE a.id=d.id AND caid>0 AND collectiontype='owned'$NOTVQuery ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.19||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

// $MaxX most collected Actors excluding voice-only parts NO TV
		DoSomeStats('ActorsNVNOTV', false,
			"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_ACTOR_TABLE a,$DVD_TABLE d$NOTVTable ",
			"WHERE a.id=d.id AND caid>0 AND collectiontype='owned' AND voice=0$NOTVQuery ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.20||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

/************************************
// $MaxX most collected Actors counting BoxSets as 1 NO TV
		DoSomeStats('NormActorsNOTV', false,
			"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_ACTOR_TABLE a,$DVD_TABLE d$NOTVTable ",
			"WHERE boxparent='' AND a.id=d.id AND caid>0 AND collectiontype='owned'$NOTVQuery ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);

// $MaxX most collected Actors counting BoxSets as 1 excluding voice-only parts NO TV
		DoSomeStats('NormActorsNVNOTV', false,
			"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_ACTOR_TABLE a,$DVD_TABLE d$NOTVTable ",
			"WHERE boxparent='' AND a.id=d.id AND caid>0 AND collectiontype='owned' AND voice=0$NOTVQuery ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
********************************/

// $MaxX most collected Actors OriginalTitle
		DoSomeStats('ActorsOR', false,
			"caid,COUNT(a.id),'',COUNT(DISTINCT(if(originaltitle != '', originaltitle, title))) AS times FROM $DVD_ACTOR_TABLE a,$DVD_TABLE d ",
			"WHERE a.id=d.id AND caid>0 AND collectiontype='owned' ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.21||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

// $MaxX most collected Actors OriginalTitle NO TV
		DoSomeStats('ActorsORNOTV', false,
			"caid,COUNT(a.id),'',COUNT(DISTINCT(if(originaltitle != '', originaltitle, title))) AS times FROM $DVD_ACTOR_TABLE a,$DVD_TABLE d$NOTVTable ",
			"WHERE a.id=d.id AND caid>0 AND collectiontype='owned'$NOTVQuery ",
			"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
		$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.22||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

	}

// $MaxX most collected Directors
	DoSomeStats('Directors', false,
		"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_CREDITS_TABLE a,$DVD_TABLE d ",
		"WHERE a.id=d.id AND collectiontype='owned' AND credittype='direction' ",
		"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
		$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.23||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

/*************************************
// $MaxX most collected Directors counting BoxSets as 1
	DoSomeStats('NormDirectors', false,
		"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_CREDITS_TABLE a,$DVD_TABLE d ",
		"WHERE boxparent='' AND a.id=d.id AND collectiontype='owned' AND credittype='direction' ",
		"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
		$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
**************************************/

// $MaxX most collected Writers
	DoSomeStats('Writers', false,
		"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_CREDITS_TABLE a,$DVD_TABLE d ",
		"WHERE a.id=d.id AND collectiontype='owned' AND credittype='writing' ",
		"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
		$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.24||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

/****************************************
// $MaxX most collected Writers counting BoxSets as 1
	DoSomeStats('NormWriters', false,
		"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_CREDITS_TABLE a,$DVD_TABLE d ",
		"WHERE boxparent='' AND a.id=d.id AND collectiontype='owned' AND credittype='writing' ",
		"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
		$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
*************************************/

// $MaxX most collected Directors NO TV
	DoSomeStats('DirectorsNOTV', false,
		"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_CREDITS_TABLE a,$DVD_TABLE d$NOTVTable ",
		"WHERE a.id=d.id AND collectiontype='owned' AND credittype='direction'$NOTVQuery ",
		"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
		$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.25||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

/*************************************
// $MaxX most collected Directors counting BoxSets as 1 NO TV
	DoSomeStats('NormDirectorsNOTV', false,
		"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_CREDITS_TABLE a,$DVD_TABLE d$NOTVTable ",
		"WHERE boxparent='' AND a.id=d.id AND collectiontype='owned' AND credittype='direction'$NOTVQuery ",
		"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
		$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
**************************************/

// $MaxX most collected Writers NO TV
	DoSomeStats('WritersNOTV', false,
		"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_CREDITS_TABLE a,$DVD_TABLE d$NOTVTable ",
		"WHERE a.id=d.id AND collectiontype='owned' AND credittype='writing'$NOTVQuery ",
		"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
		$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.26||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

/****************************************
// $MaxX most collected Writers counting BoxSets as 1 NO TV
	DoSomeStats('NormWritersNOTV', false,
		"caid,COUNT(a.id),'',COUNT(DISTINCT(a.id)) AS times FROM $DVD_CREDITS_TABLE a,$DVD_TABLE d$NOTVTable ",
		"WHERE boxparent='' AND a.id=d.id AND collectiontype='owned' AND credittype='writing'$NOTVQuery ",
		"GROUP BY caid ORDER BY times DESC LIMIT $MaxX",
		$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
*************************************/

// $MaxX most expensive DVDs
// *****NOTE*****
//   This is just selecting the largest values from the 'paid' column, which are not guaranteed to all be in the
//   same currency. If they are not, then these results are incorrect. A more correct way to do this is once for
//   each of the currencies paid ... a fair bit of work i'm not interested in doing at the moment
	if ($TryToChangeMemoryAndTimeLimits) set_time_limit(0);
	$result = $db->sql_query("SELECT DISTINCT purchasepricecurrencyid AS ppci FROM $DVD_TABLE WHERE collectiontype='owned'") or die($db->sql_error());
	while ($ppcis = $db->sql_fetchrow($result)) {
		DoSomeStats("$ppcis[ppci]PricePaid", false,
			"title,purchasepricecurrencyid,id,paid*1000 FROM $DVD_TABLE ",
			"WHERE collectiontype='owned' AND purchasepricecurrencyid='$ppcis[ppci]' ",
			"ORDER BY paid DESC,sorttitle LIMIT $MaxX",
			$noadulttitles, $ProfileName, $Profile, $numtimings, $t0);
	}
	$db->sql_freeresult($result);
	$db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='-2.27||0|0|0|0|$MyConnectionId' WHERE property='CurrentPosition'") or die($db->sql_error());

	if ($ProfileStatistics) {
		if ($inbrowser) echo "<pre>";
		echo "Timings:\n";
		$totaltime = 0;
		for ($i=0; $i<$numtimings; $i++) {
			$totaltime += $Profile[$i];
			printf("  %-30s %7s\n", $ProfileName[$i], number_format($Profile[$i], 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']));
		}
		echo "Total Time: ", number_format($totaltime, 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']), "\n";
		if ($inbrowser) echo "</pre>";
	}
	unset($ProfileName);
	unset($Profile);
}
?>
