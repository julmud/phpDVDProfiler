<?php

error_reporting(E_ALL);

if (!defined('IN_SCRIPT')) {
	die('This script should not be manually executed ... Possible Hacking attempt');
}

function UpdateUpdateLast($str='0||0|0|0|0|0') {
	@list(
		$x['Offset'],
		$x['Filename'],
		$x['Total'],
		$x['Added'],
		$x['Changed'],
		$x['NewCollNum'],
		$x['ConnectionId']
	) = explode('|', $str);
	@list(
		$x['Filename'],
		$x['Filesize']
	) = explode('!', $x['Filename']);
	if (!isset($x['ConnectionId']))
		$x['ConnectionId'] = '-1';
	return($x);
}

function MySQLVersion() {
global $db, $dbtype, $MyMySQLVersion;

	if (!isset($MyMySQLVersion)) {
		if ($dbtype != 'mysql' && $dbtype != 'mysqli')
			return(false);		// This fails if it isn't mysql
		$sql = "SELECT VERSION() AS ver";
		$result = $db->sql_query($sql) or die($db->sql_error());
		$answer = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		$MyMySQLVersion = $answer['ver'];
		unset($answer);
	}
	return($MyMySQLVersion);
}

function MySQLHasSubQueries() {
	$ret = false;
	if (($ver=MySQLVersion()) !== false) {
		list($major, $minor, $patch) = explode('.', $ver);
		if ($major > 4 || ($major == 4 && $minor >= 1)) {
			$ret = true;
		}
	}
	return($ret);
}

function DebugLog($str) {
global $DebugFilename;

	if (($handle=@fopen($DebugFilename, 'a')) !== false) {
		fwrite($handle, "$str\n");
		fclose($handle);
	}
}

function SendNoCacheHeaders($endheader='') {
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache');
	if ($endheader != '')
		header($endheader);
}

function DiscourageAbuse($RefuseBots) {
// This routine rely on the (current) fact that the img= parameter should only be called from
// within phpdvdprofiler, and indeed, only from index.php
// it is explicitly checking that the referring URL was from phpdvdprofiler
// A more relaxed check (which would allow the many robots that poke through the internet)
// could check that img=$img_webpath.* and just disallow hackers who want to use this to
// anonymously grab web content ...
global $PHP_SELF;

	if (!$RefuseBots)
		return;
	$str = '~://[^/]*'.$PHP_SELF.'~i';
	$referer = '';
	if (isset($_SERVER['HTTP_REFERER']))
		$referer = @$_SERVER['HTTP_REFERER'];
	if (($num=preg_match($str, $referer)) != 1) {
//		DebugLog("$_SERVER[REMOTE_ADDR]: $_SERVER[REQUEST_URI] FROM >$referer<");
//maybe send a "get knotted" image back to the bot/scammer
		exit;
	}
}

function Hex($int) {
	return(sprintf("%08x", $int));
}

function CustomTranslation($ind, $str) {
global $lang;

	if (isset($lang[$ind]))
		$str = $lang[$ind];
	return($str);
}

function CountryToLang($country, &$langname, &$localenum) {
global $lang, $CountryToLocality;

	$langname = $country;
	$localenum = '';
	if (isset($CountryToLocality[$country])) {
		$localenum = $CountryToLocality[$country];
		$langname = $lang['LOCALE'.$localenum];
	}
	return;
}

function GenreTranslation($gen) {
global $genre_translation;
	if (isset($genre_translation[$gen]))
		$gen = $genre_translation[$gen];
	return($gen);
}

function HideName($str) {
global $HideNames, $IsPrivate;
	if (!$HideNames || $IsPrivate)
		return($str);
	return(substr($str, 0, 1).'.');
}

function PhyspathToWebpath($string) {
global $img_physpath, $img_webpath;
	return(str_replace($img_physpath, $img_webpath, $string));
}

function WebpathToPhyspath($string) {
global $img_physpath, $img_webpath;
	return(str_replace($img_webpath, $img_physpath, $string));
}

function FormatIcon(&$dvd) {
global $AddFormatIcons, $MediaTypes;

	$formaticon = '';
	if ($AddFormatIcons != 2) {
		if ($dvd['custommediatype'] != '' && @$MediaTypes[$dvd['custommediatype']]['FormatIcon'] != '')
			$formaticon .= '<img src="' . $MediaTypes[$dvd['custommediatype']]['FormatIcon'] . '" alt="" border=0/>';
		switch ($dvd['builtinmediatype']) {
		case MEDIA_TYPE_DVD:
			if ($AddFormatIcons == 0 || $formaticon != '')	// force the DVD icon if there is already a custom icon
				if ($MediaTypes[MEDIA_TYPE_DVD]['FormatIcon'] != '')
					$formaticon .= '<img src="' . $MediaTypes[MEDIA_TYPE_DVD]['FormatIcon'] . '" alt="" border=0/>';
			break;
		case MEDIA_TYPE_HDDVD:
		case MEDIA_TYPE_BLURAY:
		case MEDIA_TYPE_ULTRAHD:
			if ($MediaTypes[$dvd['builtinmediatype']]['FormatIcon'] != '')
				$formaticon .= '<img src="' . $MediaTypes[$dvd['builtinmediatype']]['FormatIcon'] . '" alt="" border=0/>';
			break;
		case MEDIA_TYPE_HDDVD_DVD:
		case MEDIA_TYPE_BLURAY_DVD:
			if ($MediaTypes[$dvd['builtinmediatype']]['FormatIcon'] != '')
				$formaticon .= '<img src="' . $MediaTypes[$dvd['builtinmediatype']]['FormatIcon'] . '" alt="" border=0/>';
			if ($MediaTypes[MEDIA_TYPE_DVD]['FormatIcon'] != '')
				$formaticon .= '<img src="' . $MediaTypes[MEDIA_TYPE_DVD]['FormatIcon'] . '" alt="" border=0/>';
			break;
		// Just display the 4k and BR icons for now to avoid clutter.
		case MEDIA_TYPE_ULTRAHD_BLURAY:
		case MEDIA_TYPE_ULTRAHD_BLURAY_DVD:
			if ($MediaTypes[$dvd['builtinmediatype']]['FormatIcon'] != '')
				$formaticon .= '<img src="' . $MediaTypes[$dvd['builtinmediatype']]['FormatIcon'] . '" alt="" border=0/>';
			if ($MediaTypes[MEDIA_TYPE_BLURAY]['FormatIcon'] != '')
				$formaticon .= '<img src="' . $MediaTypes[MEDIA_TYPE_BLURAY]['FormatIcon'] . '" alt="" border=0/>';
			break;
		break;
		}
		if ($formaticon != '')
			$formaticon .= '&nbsp;';
	}
	return($formaticon);
}

function FormatTheTitle(&$dvd) {
global $titleorig, $titledesc;

	if ($dvd['originaltitle'] != '') {
		switch ($titleorig) {
		case 0:
			break;
		case 1:
			$tmp = $dvd['title'];
			$dvd['title'] = $dvd['originaltitle'];
			$dvd['originaltitle'] = $tmp;
			break;
		case 2:
			$dvd['title'] .= " ($dvd[originaltitle])";
			break;
		}
	}
	if ($dvd['description'] != '') {
		switch ($titledesc) {
		case 1:
			$dvd['title'] .= " ($dvd[description])";
			break;
		case 2:
			$dvd['title'] .= ": $dvd[description]";
			break;
		case 3:
			$dvd['title'] .= " - $dvd[description]";
			break;
		}
	}
}

function CheckOutOfDateSchema(&$action) {
global $lang, $WeCannotContinue, $db_Errors, $inbrowser, $DontNeedDatabase, $dbname, $dbhost, $dbuser, $db_schema_version, $code_schema_version;

	if (!$WeCannotContinue)
		return;
	if (in_array($action, $DontNeedDatabase))
		return;
	if ($db_Errors['code'] != 0) {
		switch ($db_Errors['code']) {
		case 1044:
			$output_string = sprintf($lang['IMPORTDBERR1'], $dbname, $dbhost);
			$output_string .= sprintf($lang['IMPORTDBERR2'], $dbuser);
			$output_string .= sprintf($lang['IMPORTDBERR3'], $dbuser);
			break;
		case 1049:
			$output_string = sprintf($lang['IMPORTDBERR4'], $dbname, $dbhost);
			$output_string .= sprintf($lang['IMPORTDBERR5'], $dbuser);
			$output_string .= sprintf($lang['IMPORTDBERR6'], $dbuser);
			break;
		default:
			$output_string = sprintf($lang['IMPORTDBERR7'], $dbname, $dbhost, $db_Errors['code'], $db_Errors['message']);
			break;
		}
	}
	else {
		$output_string = "$lang[IMPORTBADSCHEMA1]\n"
				."$lang[IMPORTBADSCHEMA2]\n\n"
				."$lang[IMPORTBADSCHEMA3]\n\n"
				."$lang[IMPORTBADSCHEMA4]$db_schema_version\n"
				."$lang[IMPORTBADSCHEMA5]$code_schema_version\n\n";
	}
	if ($inbrowser) {
		echo "<html><body><center><pre>\n\n\n\n";
		echo $output_string;
		echo "</pre></center>\n";
	}
	else {
		echo html_entity_decode($output_string);
	}
	$action = 'update';
	if ($db_Errors['code'] != 0)
		exit;
	return;
}

function ModifyTables($onoff) {
global $TryToFiddleIndices, $db;

	if (!$TryToFiddleIndices)
		return;
	$result = $db->sql_query("SHOW TABLES") or die($db->sql_error());
	while ($table = $db->sql_fetchrow($result)) {
		$db->sql_query("ALTER TABLE ".array_shift($table)." $onoff KEYS") or die($db->sql_error());
	}
	$db->sql_freeresult($result);
	return;
}

function resize_jpg(&$idstring, $side, $RequestedWidth, $qual, $RequestedHeight=0, $bgcolor='', $center=true) {
global $lang, $img_physpath, $imagecachedir, $thumbnails, $AddHDLogos, $DVD_TABLE, $db, $MediaTypes;
global $DontBreakOnBadPNGGDRoutines, $me_updating;
// we wish to put high-def banners at the top, if necessary.
// allow plain string id (causing possible SQL lookup) or array with pre-looked-up banner data
// possible array is passed by reference, so don't muck with the variable (hence the $idstring)

	$id = $idstring;
	if (is_array($idstring)) {
		$dvd = $idstring;
		$id = $dvd['id'];
	}
	if ($bgcolor != '' && $bgcolor[0] == '#')
		$bgcolor = substr($bgcolor, 1);
	$RequestedHeight = round($RequestedHeight);

	$filename = "$thumbnails/$id$side.jpg";
	if (!isset($imagecachedir) || !is_dir($imagecachedir) || !is_writeable($imagecachedir)) {
		if (!file_exists($img_physpath.$filename)) {
			$filename = "$id$side.jpg";
			if (!file_exists($img_physpath.$filename)) {
				return('gfx/unknown.jpg');
			}
		}
		return($img_physpath.$filename);
	}

#	Now work out what file to use. Try thumb, then full, then unknown.
	$usethumb = true;
	if (!is_readable($img_physpath.$filename)) {
#	Ok, check the main image
		$filename = "$id$side.jpg";
		if (!is_readable($img_physpath.$filename)) {
			return('gfx/unknown.jpg');
		}
		$usethumb = false;
	}
#	Time to check if the width we've requested is bigger than the thumbnail
#	If it is, we should use the true image.
#	We don't check the Requested height the same way
	list($OriginalImageWidth, $OriginalImageHeight) = getimagesize($img_physpath.$filename);
	if ($usethumb) {
		$fullfilename = "$id$side.jpg";
		if ($RequestedWidth > $OriginalImageWidth && is_readable($img_physpath.$fullfilename)) {
			$filename = $fullfilename;
			list($OriginalImageWidth, $OriginalImageHeight) = getimagesize($img_physpath.$filename);
		}
	}
	if ($RequestedWidth == 0)
		$RequestedWidth = $OriginalImageWidth;

#	Ok now we know the name of the source file we're using.

	$newfilename = "$imagecachedir$id$side-$RequestedWidth-$qual-$RequestedHeight-$bgcolor-$center-imagecache.jpg";

	if (!is_readable($newfilename) || filemtime($img_physpath.$filename) > filemtime($newfilename)) {
#
#	Code here to check if we need to add a Blu-ray/HD-DVD Banner or not.
#
		$hdbanner = '';
		if ($AddHDLogos) {
			if (!isset($dvd)) {
				$result = $db->sql_query("SELECT title,mediabannerfront,mediabannerback,custommediatype FROM $DVD_TABLE WHERE id='".$db->sql_escape($id)."' LIMIT 1") or die($db->sql_error());
				$dvd = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
			}
			$z = ($side=='f')? $dvd['mediabannerfront']: $dvd['mediabannerback'];
			if ($z < 0)
				$z = $dvd['custommediatype'];	// positive values are the builtins and -1 means the string custommediatype
			$hdbanner = @$MediaTypes[$z]['Banner'];	// if it doesn't exist, it'll get set with an empty string
		}

		if ($hdbanner != '') {
			list($OriginalBannerWidth, $OriginalBannerHeight) = getimagesize($hdbanner);
			$BannerHeightForActualImageWidth = round(($OriginalImageWidth * $OriginalBannerHeight) / $OriginalBannerWidth);
			$BannerHeightPercent = $BannerHeightForActualImageWidth / $OriginalImageHeight;
			$BannerHeight = round(($RequestedWidth * $OriginalBannerHeight) / $OriginalBannerWidth);
		}
		else {
			$BannerHeight = 0;
			$BannerHeightForActualImageWidth = 0;
			$BannerHeightPercent = 0;
		}
		$ThumbImageWidth = $ImageWidth = $RequestedWidth;
		if ($RequestedHeight == 0) {
			$ImageWidth = $RequestedWidth;
			$ImageHeight = round(($ImageWidth * $OriginalImageHeight) / $OriginalImageWidth);
			$ThumbImageWidth = $RequestedWidth;
			$ThumbImageHeight = $ImageHeight + $BannerHeight;
		}
		else {
			$ThumbHtoWRatio = $RequestedHeight / $RequestedWidth;
			$ThumbImageWidth = $RequestedWidth;
			$ThumbImageHeight = $RequestedHeight;
			$WhichIsTooLarge = ($OriginalImageHeight + $BannerHeightForActualImageWidth) / $OriginalImageWidth;
			if ($WhichIsTooLarge > $ThumbHtoWRatio) {
// height must be scaled
				$ImageHeight = round($ThumbImageHeight / (1 + $BannerHeightPercent));
				$ImageWidth = round(($ImageHeight * $OriginalImageWidth) / $OriginalImageHeight);
				$BannerHeight = $ThumbImageHeight - $ImageHeight;		// handle rounding/truncation artifacts
			}
			else {
// width must be scaled same as just correct ratio
				$ImageWidth = $RequestedWidth;
				$ImageHeight = round(($ImageWidth * $OriginalImageHeight) / $OriginalImageWidth);
			}
		}

		$im2 = ImageCreateTrueColor($ThumbImageWidth, $ThumbImageHeight);
// figure out the offsets within the thumbnail of the images
		$offx = $offy = 0;
		if ($ImageWidth != $ThumbImageWidth || ($ImageHeight + $BannerHeight) != $ThumbImageHeight) {
			if ($bgcolor != '') {
				$col = ImageColorAllocate($im2, hexdec('0x'.$bgcolor[0].$bgcolor[1]), hexdec('0x'.$bgcolor[2].$bgcolor[3]), hexdec('0x'.$bgcolor[4].$bgcolor[5]));
				ImageFill($im2, 0, 0, $col);
			}
			if ($center) {
				$offx = round(($ThumbImageWidth - $ImageWidth) / 2);
				$offy = round(($ThumbImageHeight - $ImageHeight - $BannerHeight) / 2);
			}
		}

#		Copy banner into place
		if ($hdbanner != '') {
			if ($DontBreakOnBadPNGGDRoutines) {
////////////////DebugLog("PNG:$me_updating: $dvd[title] -- $newfilename:$z");
//				if (is_readable($newfilename)) {
//					DebugLog("$img_physpath$filename=".date("F d Y H:i:s.", filemtime($img_physpath.$filename)));
//					DebugLog("$newfilename=".date("F d Y H:i:s.", filemtime($newfilename)));
//				}
				return('gfx/unknown.jpg');
			}
			$banner = ImageCreateFromPNG($hdbanner);
			ImageCopyResampled ($im2, $banner, $offx, $offy, 0, 0, $ImageWidth, $BannerHeight, $OriginalBannerWidth, $OriginalBannerHeight);
			ImageDestroy($banner);
			$offy += $BannerHeight;
		}
#		Copy thumbnail into place
		$image = ImageCreateFromJpeg($img_physpath.$filename);
		if ($OriginalImageWidth == $ImageWidth)
			ImageCopy($im2, $image, $offx, $offy, 0, 0, $OriginalImageWidth, $OriginalImageHeight);
		else
			ImageCopyResampled ($im2, $image, $offx, $offy, 0, 0, $ImageWidth, $ImageHeight, $OriginalImageWidth, $OriginalImageHeight);
		ImageDestroy($image);
		if (file_exists($newfilename))
			unlink($newfilename);	// get rid of any old file
		ImageJPEG($im2, $newfilename, $qual);
		ImageDestroy($im2);
		touch($newfilename);
	}
	return($newfilename);
}

if (!function_exists('stripos')) {
	function stripos($haystack,$needle,$offset = 0) {
		return(strpos(strtolower($haystack),strtolower($needle),$offset));
	}
}

if (!function_exists('file_get_contents')) {
	function file_get_contents($filename) {
		return implode('', file($filename));
	}
}

function ReplaceSlashes($text, $searchfor='src') {
	$replaces = array();
	$regex = '@\w*' . $searchfor . '\s*=\s*"[^\\\\\"]*\\\\[^"]*"@iU';
	preg_match_all($regex, $text, $matches);
	foreach ($matches[0] as $key => $val)
		$replaces[$key] = str_replace('\\', '/', $val);
	$text = str_replace($matches[0], $replaces, $text);
	unset($matches);
	unset($replaces);
	$replaces = array();
	$regex = str_replace('"', "'", $regex);
	preg_match_all($regex, $text, $matches);
	foreach ($matches[0] as $key => $val)
		$replaces[$key] = str_replace('\\', '/', $val);
	$text = str_replace($matches[0], $replaces, $text);
	return($text);
}

// Converts Windows-1252 characters as decimal entities into their HTML equivalents
function fix1252($s) {
$replacement = array(
	"\x80" => '&euro;',   "\x81" => ' ',        "\x82" => '&sbquo;',  "\x83" => '&#x192;',
	"\x84" => '&bdquo;',  "\x85" => '&hellip;', "\x86" => '&dagger;', "\x87" => '&Dagger;',
	"\x88" => '&circ;',   "\x89" => '&permil;', "\x8a" => '&Scaron;', "\x8b" => '&lsaquo;',
	"\x8c" => '&OElig;',  "\x8d" => ' ',        "\x8e" => '&#x17D;',  "\x8f" => ' ',
	"\x90" => ' ',        "\x91" => '&lsquo;',  "\x92" => '&rsquo;',  "\x93" => '&ldquo;',
	"\x94" => '&rdquo;',  "\x95" => '&bull;',   "\x96" => '&ndash;',  "\x97" => '&mdash;',
	"\x98" => '&tilde;',  "\x99" => '&trade;',  "\x9a" => '&scaron;', "\x9b" => '&rsaquo;',
	"\x9c" => '&oelig;',  "\x9d" => ' ',        "\x9e" => '&#x17E;',  "\x9f" => '&Yuml;',
	"\xa0" => '&nbsp;',   "\xa1" => '&iexcl;',  "\xa2" => '&cent;',   "\xa3" => '&pound;',
	"\xa4" => '&curren;', "\xa5" => '&yen;',    "\xa6" => '&brvbar;', "\xa7" => '&sect;',
	"\xa8" => '&uml;',    "\xa9" => '&copy;',   "\xaa" => '&ordf;',   "\xab" => '&laquo;',
	"\xac" => '&not;',    "\xad" => '&shy;',    "\xae" => '&reg;',    "\xaf" => '&macr;',
	"\xb0" => '&deg;',    "\xb1" => '&plusmn;', "\xb2" => '&sup2;',   "\xb3" => '&sup3;',
	"\xb4" => '&acute;',  "\xb5" => '&micro;',  "\xb6" => '&para;',   "\xb7" => '&middot;',
	"\xb8" => '&ccedil;', "\xb9" => '&sup1;',   "\xba" => '&ordm;',   "\xbb" => '&raquo;',
	"\xbc" => '&frac14;', "\xbd" => '&frac12;', "\xbe" => '&frac34;', "\xbf" => '&iquest;',
	"\xc0" => '&Agrave;', "\xc1" => '&Aacute;', "\xc2" => '&Acirc;',  "\xc3" => '&Atilde;',
	"\xc4" => '&Auml;',   "\xc5" => '&Aring;',  "\xc6" => '&AElig;',  "\xc7" => '&Ccedil;',
	"\xc8" => '&Egrave;', "\xc9" => '&Eacute;', "\xca" => '&Ecirc;',  "\xcb" => '&Euml;',
	"\xcc" => '&Igrave;', "\xcd" => '&Iacute;', "\xce" => '&Icirc;',  "\xcf" => '&Iuml;',
	"\xd0" => '&ETH;',    "\xd1" => '&Ntilde;', "\xd2" => '&Ograve;', "\xd3" => '&Oacute;',
	"\xd4" => '&Ocirc;',  "\xd5" => '&Otilde;', "\xd6" => '&Ouml;',   "\xd7" => '&times;',
	"\xd8" => '&Oslash;', "\xd9" => '&Ugrave;', "\xda" => '&Uacute;', "\xdb" => '&Ucirc;',
	"\xdc" => '&Uuml;',   "\xdd" => '&Yacute;', "\xde" => '&THORN;',  "\xdf" => '&szlig;',
	"\xe0" => '&agrave;', "\xe1" => '&aacute;', "\xe2" => '&acirc;',  "\xe3" => '&atilde;',
	"\xe4" => '&auml;',   "\xe5" => '&aring;',  "\xe6" => '&aelig;',  "\xe7" => '&ccedil;',
	"\xe8" => '&egrave;', "\xe9" => '&eacute;', "\xea" => '&ecirc;',  "\xeb" => '&euml;',
	"\xec" => '&igrave;', "\xed" => '&iacute;', "\xee" => '&icirc;',  "\xef" => '&iuml;',
	"\xf0" => '&eth;',    "\xf1" => '&ntilde;', "\xf2" => '&ograve;', "\xf3" => '&oacute;',
	"\xf4" => '&ocirc;',  "\xf5" => '&otilde;', "\xf6" => '&ouml;',   "\xf7" => '&divide;',
	"\xf8" => '&oslash;', "\xf9" => '&ugrave;', "\xfa" => '&uacute;', "\xfb" => '&ucirc;',
	"\xfc" => '&uuml;',   "\xfd" => '&yacute;', "\xfe" => '&thorn;',  "\xff" => '&yuml;'
	);
	return(str_replace(array_keys($replacement), array_values($replacement), $s));
}

// Converts ISO8859-5 characters as decimal entities into their HTML equivalents
function fix88595($s) {
global $ISO88595;

	if (!$ISO88595)
		return($s);
$replacement = array(
	"\xa0" => '&nbsp;',   "\xa1" => '&#x401;', "\xa2" => '&#x402;', "\xa3" => '&#x403;',
	"\xa4" => '&#x404;',  "\xa5" => '&#x405;', "\xa6" => '&#x406;', "\xa7" => '&#x407;',
	"\xa8" => '&#x408;',  "\xa9" => '&#x409;', "\xaa" => '&#x40a;', "\xab" => '&#x40b;',
	"\xac" => '&#x40c;',  "\xad" => '&#x0ad;', "\xae" => '&#x40e;', "\xaf" => '&#x40f;',
	"\xb0" => '&#x410;',  "\xb1" => '&#x411;', "\xb2" => '&#x412;', "\xb3" => '&#x413;',
	"\xb4" => '&#x414;',  "\xb5" => '&#x415;', "\xb6" => '&#x416;', "\xb7" => '&#x417;',
	"\xb8" => '&#x418;',  "\xb9" => '&#x419;', "\xba" => '&#x41a;', "\xbb" => '&#x41b;',
	"\xbc" => '&#x41c;',  "\xbd" => '&#x41d;', "\xbe" => '&#x41e;', "\xbf" => '&#x41f;',
	"\xc0" => '&#x420;',  "\xc1" => '&#x421;', "\xc2" => '&#x422;', "\xc3" => '&#x423;',
	"\xc4" => '&#x424;',  "\xc5" => '&#x425;', "\xc6" => '&#x426;', "\xc7" => '&#x427;',
	"\xc8" => '&#x428;',  "\xc9" => '&#x429;', "\xca" => '&#x42a;', "\xcb" => '&#x42b;',
	"\xcc" => '&#x42c;',  "\xcd" => '&#x42d;', "\xce" => '&#x42e;', "\xcf" => '&#x42f;',
	"\xd0" => '&#x430;',  "\xd1" => '&#x431;', "\xd2" => '&#x432;', "\xd3" => '&#x433;',
	"\xd4" => '&#x434;',  "\xd5" => '&#x435;', "\xd6" => '&#x436;', "\xd7" => '&#x437;',
	"\xd8" => '&#x438;',  "\xd9" => '&#x439;', "\xda" => '&#x43a;', "\xdb" => '&#x43b;',
	"\xdc" => '&#x43c;',  "\xdd" => '&#x43d;', "\xde" => '&#x43e;', "\xdf" => '&#x43f;',
	"\xe0" => '&#x440;',  "\xe1" => '&#x441;', "\xe2" => '&#x442;', "\xe3" => '&#x443;',
	"\xe4" => '&#x444;',  "\xe5" => '&#x445;', "\xe6" => '&#x446;', "\xe7" => '&#x447;',
	"\xe8" => '&#x448;',  "\xe9" => '&#x449;', "\xea" => '&#x44a;', "\xeb" => '&#x44b;',
	"\xec" => '&#x44c;',  "\xed" => '&#x44d;', "\xee" => '&#x44e;', "\xef" => '&#x44f;',
	"\xf0" => '&#x2116;', "\xf1" => '&#x451;', "\xf2" => '&#x452;', "\xf3" => '&#x453;',
	"\xf4" => '&#x454;',  "\xf5" => '&#x455;', "\xf6" => '&#x456;', "\xf7" => '&#x457;',
	"\xf8" => '&#x458;',  "\xf9" => '&#x459;', "\xfa" => '&#x45a;', "\xfb" => '&#x45b;',
	"\xfc" => '&#x45c;',  "\xfd" => '&#x0a7;', "\xfe" => '&#x45e;', "\xff" => '&#x45f;'
	);
	return(str_replace(array_keys($replacement), array_values($replacement), $s));
}

function my_mktime() {
	@list($hours, $minutes, $seconds, $mon, $mday, $year, $isdst) = func_get_args();
//echo "$hours,$minutes,$seconds,$mon,$mday,$year,$isdst\n";
	$temp = @getdate();
//if (is_int($hours)) echo "IsInt is true\n";
//if (is_numeric($hours)) echo "Isnumeric is true\n";
	if (!is_numeric($hours)) $hours = $temp['hours'];
	if (!is_numeric($minutes)) $minutes = $temp['minutes'];
	if (!is_numeric($seconds)) $seconds = $temp['seconds'];
	if (!is_numeric($mon)) $mon = $temp['mon'];
	if (!is_numeric($mday)) $mday = $temp['mday'];
	if (!is_numeric($year)) $year = $temp['year'];
	if (!is_numeric($isdst)) $isdst = -1;
	unset($temp);
//echo "$hours,$minutes,$seconds,$mon,$mday,$year,$isdst\n\n";
	$ret = @mktime($hours, $minutes, $seconds, $mon, $mday, $year);
	if ($ret === false || $ret < 0) $ret = 0;
	return($ret);
}

function DecToIPv4($number) {
	$a = ($number>>24) & 255;
	$b = ($number>>16) & 255;
	$c = ($number>>8) & 255;
	$d = $number & 255;
	return("$a.$b.$c.$d");
}

function IPv4ToDec($addr) {
	@list($a, $b, $c, $d) = explode('.', $addr);
	return(((((($a<<8) + $b)<<8) + $c)<<8) + $d);
}

function CheckSubnet($addr, $cidr) {

	if (strpos($cidr, '/') === false) $cidr .= '/32';
	list($subnetbase, $subnetmaskbits) = explode('/', $cidr);
	$subnetmask = bindec(str_pad(str_pad('', $subnetmaskbits, '1'), 32, '0'));
	return((IPv4ToDec($addr) & $subnetmask) == (IPv4ToDec($subnetbase) & $subnetmask));
}

function DisplayIfIsPrivateOrAlways($var) {
global $IsPrivate;
	return(($var==0) || ($IsPrivate&&$var==1));
}

function GetFirstToken($str) {
	$tmp = strpos($str, ',');
	if ($tmp === false) $tmp = strlen($str);
	return(substr($str, 0, $tmp));
}

function AddCommas($str) {
	if (strlen($str) < 2)
		return($str);
	for ($out='',$i=0; $i<strlen($str)-1; $i++)
		$out .= $str[$i].', ';
	return($out.$str[strlen($str)-1]);
}

function MakeImageWindow($fn, $id, $mtype) {
global $PHP_SELF;

	list($imgwidth, $imgheight) = getimagesize($fn);
	$imgwidth += 40;
	$imgheight += 40;
	$mtype = "&mtype=$mtype";
	$NewWindow = "window.open('$PHP_SELF?img=$fn&mediaid=$id$mtype','Images',"
			."'toolbar=no,location=no,width=$imgwidth,height=$imgheight,resizable=yes,scrollbars=yes,status=yes'); return false;";
	return($NewWindow);
}

function FixAReviewValue($val) {
	return(($val==0)? $val: $val+1);
}

function ColorName($actor, $cn, $usedefaultcolors=false) {
global $colorfirst, $colormiddle, $colorlast;

	if ($actor['creditedas'] != '')
		return($actor['creditedas']);
	if (!$cn)
		return($actor['fullname']);
	$cf = $colorfirst;
	$cm = $colormiddle;
	$cl = $colorlast;
	if ($usedefaultcolors) {
		$cf = '#000080';
		$cm = '#800000';
		$cl = '#008000';
	}
	$name = '';
	if ($actor['firstname'] != '')
		$name = "<font color=\"$cf\">$actor[firstname]</font>";
	if ($actor['middlename'] != '') {
		if ($name != '') $name .= ' ';
		$name .= "<font color=\"$cm\">$actor[middlename]</font>";
	}
	if ($actor['lastname'] != '') {
		if ($name != '') $name .= ' ';
		$name .= "<font color=\"$cl\">$actor[lastname]</font>";
	}
	return($name);
}

function TableCopyingSupported() {
global $db, $dbtype;

	if ($dbtype != 'mysql' && $dbtype != 'mysqli')
		return(true);		// This assumes that databases other than mysql will like the syntax
	$ver = MySQLVersion();
	list($major, $minor, $patch) = explode('.', $ver);
	$patch = (int)$patch;
	if ($major>4 || ($major==4 && ($minor>0 || ($minor==0 && $patch>=14))))
		return(true);
	return(false);
}

function microtime_float() {
	list($usec, $sec) = explode(' ', microtime());
	return((float)$usec + (float)$sec);
}

function findfilecase($dir, $name) {
	$realname = '';
	if ($dir[strlen($dir)-1] == '/')
		$dir = substr($dir, 0, -1);
	if (is_dir($dir)) {
		if (file_exists($dir.'/'.$name))
			return($name);
		if (file_exists($dir.'/'.strtolower($name)))
			return(strtolower($name));
		if (file_exists($dir.'/'.strtoupper($name)))
			return(strtoupper($name));
		$handle = opendir($dir);
		while (($file=readdir($handle)) !== false) {
			if (strcasecmp($file, $name) == 0) {
				$realname = $file;
				break;
			}
		}
		closedir($handle);
	}
	return($realname);
}

/* Function checkTrueColor taken from DotClear (http://www.dotclear.net/)
   and written by Olivier Meunier. */
function checkTrueColor()
{
	if (function_exists('gd_info'))
	{
		$gdinfo = gd_info();
		$gdversion = $gdinfo['GD Version'];
		if (strpos($gdversion,'2.') !== false) {
			return true;
		}
	}
	return false;
}


function create_thumb($img_path, $file_name) {
global $thumbnails;

	if (!extension_loaded('gd')) return false;

	list ($width_orig, $height_orig) = getimagesize($img_path.$file_name);
	$width = 180;
	$height = ($width / $width_orig) * $height_orig;
	$image = ImageCreateFromJPEG($img_path.$file_name);

	if (checkTrueColor()) {
		$image_p = ImageCreateTrueColor($width, $height);
		$function_resize = 'ImageCopyResampled';
		$success = true;
	}
	else {
// hack to create a true color image, even when we don't have 
// access to GD 2
		$image_p = ImageCreate($width, $height);
		$success = @ImageJPEG($image_p, $img_path.$thumbnails.'/'.$file_name, 75);
		$image_p = ImageCreateFromJPEG($img_path.$thumbnails.'/'.$file_name);
		$function_resize = 'ImageCopyResized';
	}  

	$success = $success && @$function_resize($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
	$success = $success && @ImageJPEG($image_p, $img_path.$thumbnails.'/'.$file_name, 75);

	ImageDestroy($image);
	ImageDestroy($image_p);

	return $success;
}

function AcquireAThumbnail($filename) {
global $getimages, $img_physpath, $createthumbs, $thumbnails;

	if (!file_exists($img_physpath.$thumbnails.'/'.$filename) && $getimages == 2) {	// if $getimages == 2, try to grab an image from the Invelos server
		if ($thumb=@file_get_contents('http://www.invelos.com/mpimages/'.$filename[0].$filename[1].'/'.$filename)) {
			if ($handle = fopen($img_physpath.$thumbnails.'/'.$filename, 'wb')) {
				fwrite($handle, $thumb);
				fclose($handle);
			}
			unset($thumb);
		}
	}
// If the thumbnail still doesn't exist, try to create it from an existing large image
	if ($createthumbs &&
		!file_exists($img_physpath.$thumbnails.'/'.$filename) &&
		file_exists($img_physpath.$filename) &&
		is_writable($img_physpath.$thumbnails)) {
			create_thumb($img_physpath, $filename);
	}
}


// This finds an existing file in the images/thumbnails directory, in a case-insensitive way
// If it can't, it tries to create one, using the original name. It returns the actual name
// that is either created, or found. If no file is created or found, then it returns an empty string.

function real_find_a_file($id, $isfront, $checkthumb=true) {
global $getimages, $img_physpath, $thumbnails;

	$name = $id . (($isfront)? 'f': 'b') . '.jpg';
	$dir = $img_physpath;
	if ($checkthumb)
		$dir .= $thumbnails.'/';
	if (!file_exists($dir.$name)) {
		if (($tmp=findfilecase($dir, $name)) != '')
			$name = $tmp;
	}
	if (!file_exists($dir.$name) && $checkthumb) {
		AcquireAThumbnail($name);
	}
	if (is_readable($dir.$name))
		return($name);
//DebugLog('$dir='.$dir.', $name='.$name);
	return('');
}

function find_a_file($id, $isfront, $checkthumb=true) {
global $try_prev3_images;

	$ret = real_find_a_file($id, $isfront, $checkthumb);
	if ($ret == '' && $try_prev3_images && $id[0] != 'I' && $id[0] != 'M') {
		$locale = '';
		$EANUPC = $id;
		if (strpos($id, '.') !== false)
			list($EANUPC, $locale) = explode('.', $id);
		if ($locale != '') $locale = ".$locale";
		if (strlen($EANUPC) == 13)
			$ret = real_find_a_file(substr($EANUPC, 1, 12).$locale, $isfront, $checkthumb);
		else if (strlen($EANUPC) == 12)
			$ret = real_find_a_file(substr($EANUPC, 1, 10).$locale, $isfront, $checkthumb);
	}
	return($ret);
}

function find_files_in_set(&$dvd, &$img, &$thmb, $isfront) {
global $img_webpath, $tuumbnails;

	$img = find_a_file($dvd['id'], $isfront, false);
	$thmb = find_a_file($dvd['id'], $isfront, true);
	if ($thmb != '') {
		$thmb = "{$img_webpathf}$thumbnails/$thmb";
	}
	else {
		if ($img != '')
			$thmb = "{$img_webpathf}$img";
		else
			$thmb = find_files_in_set($dvd['boxparent'], $dvd, $img, $thmb, $isfront);
	}
	if ($fn != '')
		return($fn);
}

function DebugSQL($db, $string) {
global $debugSQL;

	if (!$debugSQL)
		return;
	$num_queries = $db->sql_num_queries();
	$thequeries = $db->sql_ret_queries();
	DebugLog("$string: $num_queries$thequeries\n");
}

function GetLastUpdateTime($which) {
global $xmldir, $xmlfile, $db, $DVD_PROPERTIES_TABLE;

	$result = $db->sql_query("SELECT value FROM $DVD_PROPERTIES_TABLE WHERE property='".$db->sql_escape($which)."'") or die($db->sql_error());
	if ($dvd = $db->sql_fetchrow($result)) {
		$thedatetime = $dvd['value'];
	}
	else {
		$fn = $xmlfile;
		if ($xmldir != '') {
			$fn = $xmldir.'/.';
		}
		$thedatetime = @filemtime($fn);
		if (!$thedatetime)
			$thedatetime = 0;
	}
	$db->sql_freeresult($result);
	if (isset($dvd))
		unset($dvd);
	return($thedatetime);
}

function FindHeadImage($headdir, &$subdirs, &$filename, $UPC = '') {

	$imagename = '';
	if ($UPC == '') {
		$tried = $headdir . $filename;
		if (is_readable($headdir . $filename)) {
			$imagename = $headdir . $filename;
		}
	}
	else {
		$tried = "$headdir$UPC/$filename";
		if (is_readable("$headdir$UPC/$filename")) {
			$imagename = "$headdir$UPC/$filename";
		}
		else {
			$tried .= " \n$headdir$filename";
			if (is_readable($headdir . $filename)) {
				$imagename = $headdir . $filename;
			}
		}
	}
	if ($imagename == '' && count($subdirs) > 0) {
		foreach ($subdirs as $key => $sd) {
			$tried .= " \n$headdir$sd$filename";
			if (is_readable($headdir . $sd . $filename)) {
				$imagename = $headdir . $sd . $filename;
				break;
			}
		}
	}
	$filename = $tried;
	return($imagename);
}

function HeadImage(&$person, $headdir, &$subdirs, &$filename, $UPC = '') {
	$filename = $person['fullname'];
	if ($person['birthyear']) $filename .= " $person[birthyear]";
	$filename .= '.jpg';

	$imagename = FindHeadImage($headdir, $subdirs, $filename, $UPC);

	if ($imagename == '') {
		$filename2 = "$person[lastname]_$person[firstname]_$person[middlename]";
		if ($person['birthyear']) $filename2 .= "_$person[birthyear]";
		$filename2 .= ".jpg";

		$imagename = FindHeadImage($headdir, $subdirs, $filename2, $UPC);
		$filename .= " \n$filename2";	// add a space for browsers that don't honor newlines
	}
	return($imagename);
}

function GetHeadAndMouse(&$person, $headdir, &$subdirs, &$chsimg, &$mouse, $UPC = '') {
global $maxheadshotwidth, $ClassColor;

	$displayname = str_replace(array('&', "'", '"'), array('&amp;', "\\'", '&quot;'), $person['fullname']);
	$ColScheme = "this.T_BGCOLOR='$ClassColor[2]';this.T_TITLECOLOR='$ClassColor[17]';this.T_BORDERCOLOR='$ClassColor[5]'";

	$imagename = HeadImage($person, $headdir, $subdirs, $filename, $UPC);
	if ($imagename != '') {
		$isize = getimagesize($imagename);
		if ($isize[0] > $maxheadshotwidth)
			$isize[0] = $maxheadshotwidth;
		$filenameurl = str_replace("'", '%27', preg_replace('/[^\x20-\x7F]/e', '"%".dechex(ord("$0"))', htmlspecialchars($imagename, ENT_COMPAT, 'ISO-8859-1')));
		$mouse = "onmouseover=\"$ColScheme;this.T_TITLE='$displayname';this.T_WIDTH=$isize[0];return escape('<img src=\'$filenameurl\' alt=\'\' width=$isize[0] ')\"";
		unset($isize);
		$sanifilename = str_replace("'", '&#39;', $imagename);
		$chsimg = "<img src='gfx/head.gif' title='$sanifilename' alt=''/>";
	}
	else {
		$mouse = '';
		if (isset($person['creditedas']) && $person['creditedas'] != '')
			$mouse = "onmouseover=\" $ColScheme;this.T_TITLE='$displayname';this.T_WIDTH=100;this.T_BORDERWIDTH=0;this.T_PADDING=0;return escape('')\"";
		$sanifilename = str_replace("'", '&#39;', $filename);
		$chsimg = "<img src='gfx/no_head.gif' title='$sanifilename' alt=''/>";
	}
	return;
}

function myasciz($c) {
	if (ord($c) < 32 || ord($c) == 129)
		return('.');
	return($c);
}

function mydechex($c) {
	if (ord($c) < 16)
		return('0'.dechex(ord($c)));
	return(dechex(ord($c)));
}

function hexdump($string, $len, $leader='') {
	$max = $stringlen = strlen($string);
	if ($max > $len)
		$max = $len;
	$out = $leader;
	$hex = ''; $asc = '';
	for ($i=0; $i<$max; $i++) {
		$hex .= mydechex($string[$i]) . ' ';
		$asc .= myasciz($string[$i]) . ' ';
		if ($i%8 == 7) {
			$out .= "$hex  $asc\n$leader";
			$hex = $asc = '';
		}
		if ($i%8 == 3) {
			$hex .= ' ';
			$asc .= ' ';
		}
	}
	$lennow = strlen($hex);
	if ($lennow != 0) {
		$hex = str_pad($hex, 25);
		$out .= "$hex  $asc\n";
	}
	return($out);
}

function GimmeAThumb($id, $side='f', $addbanner='') {
global $img_physpath, $thumbnails;
global $DontBreakOnBadPNGGDRoutines;
// The intent of this function is to emit to output, an image with a named banner on it, at full quality
// It explicitly does not check to see if that is appropriate for the particular profile
// although it does only do it for front images

	$filename = $img_physpath . $thumbnails ."/$id$side.jpg";
	if (!is_readable($filename)) {
		echo "Can't find $id\n";
		return;
	}
	if ($side == 'f') {
		$banner = @$MediaTypes[$addbanner]['Banner'];	// if the type doesn't exist, $banner will be set to empty string
		if ($banner != '' && is_readable($banner)) {
			if ($DontBreakOnBadPNGGDRoutines) {
////////////////DebugLog("PNG2:GimmeAThumb -- $filename");
				$filename = 'gfx/unknown.jpg';
			}
			else {
				list($imagewidth, $imageheight) = getimagesize($filename);
				list($bannerwidth, $bannerheight) = getimagesize($banner);
				$newbannerheight = intval(($imagewidth / $bannerwidth) * $bannerheight);
				$outputheight = $imageheight + $newbannerheight;

				$newbitmap = ImageCreateTrueColor($imagewidth, $outputheight);
				$imbanner = ImageCreateFromPNG($banner);
				ImageCopyResampled ($newbitmap, $imbanner, 0, 0, 0, 0, $imagewidth, $newbannerheight, $bannerwidth, $bannerheight);
				ImageDestroy($imbanner);

				$imim = ImageCreateFromJPEG($filename);
				ImageCopy($newbitmap, $imim, 0, $newbannerheight, 0, 0, $imagewidth, $imageheight);
				ImageDestroy($imim);

				SendNoCacheHeaders('Content-Type: image/jpeg');
				ImageJPEG($newbitmap, '', 100);	// manual says to use NULL, but only '' seems to work
				ImageDestroy($newbitmap);
				return;
			}
		}
	}
	SendNoCacheHeaders('Content-Type: image/jpeg');
	readfile($filename);
	return;
}

function MakeAUnixTime($entry, $default) {
	$retval = $default;
	if (isset($entry)) {

// I don't think that DVDP3 actually generates dates with slashes anymore, so I'm removing the code. If I'm wrong, uncomment the next line
//		if (strpos($entry, '/') !== false) $entry = preg_replace('~(\d+)/(\d+)/(\d+)(.*)~', '$3-$2-$1$4', $entry);

		$thetime = ''; if (strpos($entry, 'T') === false) $thetime = 'T00:00:00.000Z';

// While the preg_replace is more elegant, it is also considerably slower ... I'm leaving it here because it's pretty :)
//		$retval = preg_replace('~(\d+)-0?(\d+)-0?(\d+)T0?(\d+):0?(\d+):0?(\d+).*Z~e', 'my_mktime($4, $5, $6, $2, $3, $1)', $entry.$thetime);

		sscanf($entry.$thetime, '%d-%d-%dT%d:%d:%d.%dZ', $matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6], $matches[7]);
		$retval = my_mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
		unset($matches);
	}
	return($retval);
}

function _testFile($filename) {
	if (!empty($filename) && file_exists("gfx/Ratings/$filename")) {
		return "gfx/Ratings/" . rawurlencode($filename);
	}
	return FALSE;
}

// Try to get a sanitized version of the rating logo's filename
function GetRatingLogo($locale, $ratingsystem, $rating) {
	$rfn = "rating_{$locale}_" . str_replace('/', '-', strtolower($ratingsystem.'_'.$rating)) . '.gif';
	if (extension_loaded('intl')) {
		$transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;', Transliterator::FORWARD);
		$normalized = $transliterator->transliterate(utf8_encode($rfn));
		$logo = _testFile($normalized);
		if ($logo) {
			return $logo;
		}
	}

	$normalized = iconv('ISO-8859-1', 'ascii//TRANSLIT', $rfn);
	$logo = _testFile($normalized);
	if ($logo) {
		return $logo;
	}

	// Filename string sanitizer, from https://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
	// Remove anything which isn't a word, whitespace, number
	// or any of the following caracters -_~,;[]()+.
	// If you don't need to handle multi-byte characters
	// you can use preg_replace rather than mb_ereg_replace
	// Thanks @Łukasz Rysiak!
	$normalized = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).\+])", '', $normalized);
	// Remove any runs of periods (thanks falstro!)
	$normalized = mb_ereg_replace("([\.]{2,})", '', $normalized);
	$logo = _testFile($normalized);
	if ($logo) {
		return $logo;
	}

	// Fallback, no sanitized version of the file was found, use the rating system and rating by itself
	$logo = _testFile($rfn);
	if ($logo) {
		return $logo;
	}
	return NULL;
}
?>
