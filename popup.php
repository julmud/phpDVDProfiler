<?php

define('IN_SCRIPT', 1);
include_once('version.php');
include_once('global.php');

$IMDBNameSearch = "$lang[IMDBURL]?s=nm&q=";

function OutputRoles($id, $acttype, $role) {
global $DVD_ACTOR_TABLE, $DVD_CREDITS_TABLE, $db, $lang, $ApplyDividerContinuations;

	$none  = '<img src="gfx/none.gif">&nbsp;';
	$plus  = '<img src="gfx/plus.gif" onClick="sum_detail(\'sum\',\'det\',\'999\')" style="vertical-align:middle">&nbsp;';
	$minus = '<img src="gfx/minus.gif" onClick="sum_detail(\'det\',\'sum\',\'999\')" style="vertical-align:middle">&nbsp;';
	$front   = "<tr><td align=left colspan=7 class=f5 style=\"font-size:8pt\"><b>";
	$frontd  = "<tr><td align=left colspan=7 class=\"DividerPopup\"><b>";
	$frontg  = "<tr><td align=left colspan=7 class=\"GroupDividerPopup\"><b>";
	$frontgn = "<tr><td align=left colspan=7 class=f5 style=\"font-size:8pt;padding-left:20px\"><b>";
	$back = "</b></td><td width=\"100%\"></td></tr>\n";

	if ($acttype == 'REGION' || $acttype == 'STUDIO') {
		echo $front . $role . $back;
		return;
	}

	$table = $DVD_CREDITS_TABLE;
	if ($acttype == 'ACTOR')
		$table = $DVD_ACTOR_TABLE;

	$result = $db->sql_query("SELECT * FROM $table WHERE id='".$db->sql_escape($id)."' AND (caid=".$db->sql_escape($role)." OR caid<0) ORDER BY lineno") or die($db->sql_error());
	$onlyone = 0;

	$thedetail = '';
	$thesummary = array();
	$image = str_replace('999', $id, $minus);
	$currentep = '';
	$currenteplineno = -10;
	$currentgr = '';
	$currentgrtype = '';
	while ($row = $db->sql_fetchrow($result)) {
		if (!isset($row['credittype']))
			$row['credittype'] = '';
		if ($row['caid'] == -1) {
			if ($acttype != 'ACTOR') {
// Currently, an episode divider in a cast list does not terminate a group divider; although it does in a crew list
				$currentgr = '';
				$currentgrtype = '';
			}
			$NeedToAddLine = true;
			if ($row['lineno'] == ($currenteplineno + 1)) {
				if ($ApplyDividerContinuations && (($FirstBlank=strpos($currentep, ' ')) !== false)) {
					if (substr($row['creditedas'], 0, $FirstBlank+1) == substr($currentep, 0, $FirstBlank+1)) {
						$currentep .= substr($row['creditedas'], $FirstBlank);
						$NeedToAddLine = false;
					}
				}
			}
			if ($NeedToAddLine) {
				$currentep = $row['creditedas'];
			}
			$currenteplineno = $row['lineno'];
		}
		else if ($row['caid'] == -2) {
			$currentgr = $row['creditedas'];
			$currentgrtype = $row['credittype'];
		}
		else if ($row['caid'] == -3) {
			$currentgr = '';
			$currentgrtype = '';
		}
		else {
			if ($row['credittype'] != $currentgrtype) {
				$currentgr = '';
				$currentgrtype = '';
			}
			$extra = '';
			if (isset($row['uncredited']) && $row['uncredited'] == 1)
				$extra .= $lang['UNCREDITED'];
			if (isset($row['voice']) && $row['voice'] == 1) {
				if ($extra != '')
					$extra .= ', ';
				$extra .= $lang['VOICE'];
			}
			if ($extra != '') $extra = " ($extra)";

			if (isset($row['customrole']) && $row['customrole'] != '')
				$row['role'] = $row['customrole'];
			else if (isset($row['creditsubtype']))
				$row['role'] = $lang[strtoupper(str_replace(' ', '', $row['creditsubtype']))];
			if (isset($row['creditedas']) && $row['creditedas'] != '')
				$row['role'] .= " ($row[creditedas])";
			if ($row['role'] == '')
				$row['role'] = '&nbsp;';

			if ($currentep != '') {
				$thedetail .= "$frontd$image$currentep$back";
				$image = $none;
				$onlyone++;
				$currentep = '';		// Only do the divider once
			}
			if ($currentgr != '') {
				$thedetail .= "$frontg$image$currentgr$back";
				$image = $none;
				$thedetail .= "$frontgn$image$row[role]$extra$back";
				$onlyone++;
				if ($row['role'] == '&nbsp;')
					$row['role'] = $currentgr;
				else
					$row['role'] = "$currentgr: $row[role]";
			}
			else {
				$thedetail .= "$front$image$row[role]$extra$back";
				$image = $none;
			}
			$onlyone++;
				
			foreach ($thesummary as $k => $name)
				if ($name == $row['role'])
					break;
			if (!isset($name) || $name != $row['role'])
				$thesummary[] = $row['role'];
		}
	}
	$db->sql_freeresult($result);

	if ($onlyone == 1) {
		echo $front . $thesummary[0] . $extra . $back;
	}
	else {
		$sum = '';
		foreach ($thesummary as $k => $name) {
			if ($sum != '') $sum .= ', ';
			$sum .= $name;
		}
		echo "<tbody id=\"sum_$id\">$front" . str_replace('999', $id, $plus) . "$sum$back</tbody>";
		echo "<tbody id=\"det_$id\" style=\"display:none\">$thedetail</tbody>";
	}
	unset($thesummary);
}

	header('Content-Type: text/html; charset="windows-1252";');
	echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
<title>$lang[$acttype] $lang[INFORMATION]</title>
<link rel="stylesheet" type="text/css" href="format.css.php">
<script type="text/javascript">
function sum_detail(hide, show, id) {
	document.getElementById(show+'_'+id).style.display = '';
	document.getElementById(hide+'_'+id).style.display = 'none';
}
</script>
</head>
<body onLoad="self.focus()">

EOT;
	if (!isset($sortby)) $sortby = 'sorttitle';
	$sel_title = $sel_released = $sel_prodyear = '';
	if ($sortby == 'released')
		$sel_released = 'selected';
	else if ($sortby == 'productionyear')
		$sel_prodyear = 'selected';
	else {
		$sortby = 'sorttitle';
		$sel_title = 'selected';
	}

	$noadult = '';
	if (!DisplayIfIsPrivateOrAlways($handleadult))
		$noadult .= ' AND isadulttitle=0';

	$slashedfullname = addslashes($fullname);
	$dispname = "<a target=\"_blank\" href=\"$IMDBNameSearch" . urlencode($fullname) . "\">$fullname</a>";
	$chsimg = '';
	$numresults = $numdistinctprofiles = '';
	if ($acttype == 'ACTOR') {
		$result = $db->sql_query("SELECT firstname,middlename,lastname,fullname AS realfullname,birthyear,COUNT(a.id) AS numresults,COUNT(DISTINCT a.id) AS numprofiles FROM $DVD_COMMON_ACTOR_TABLE ca, $DVD_ACTOR_TABLE a WHERE ca.caid=".$db->sql_escape($fullname)." AND ca.caid=a.caid GROUP BY a.caid") or die($db->sql_error());
		$actor = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		$numdistinctprofiles = $actor['numprofiles'];
		$numresults = $actor['numresults'];
		$dispname = "<a target=\"_blank\" href=\"$IMDBNameSearch" . urlencode($actor['realfullname']) . "\">$actor[realfullname]</a>";
		$castimgname = $actor['realfullname'];
		if ($actor['birthyear'])
			$castimgname .= " $actor[birthyear]";
		else
			$actor['birthyear'] = '';
		$castimgname .= '.jpg';

		if (is_readable($headcast . $castimgname)) {
			$isize = getimagesize($headcast . $castimgname);
			$hwidth = '';
			if ($maxheadshotwidthccw && $isize[0] > $maxheadshotwidthccw)
				$isize[0] = $maxheadshotwidthccw;
			if ($maxheadshotwidthccw)
				$hwidth = "width=$isize[0]";
        		$castimgname = $headcast . rawurlencode($castimgname);
        		$chsimg = "<img src=\"$castimgname\" $hwidth alt=\"\" />&nbsp;";
		}

		$sql = "SELECT mediabannerfront,runningtime,suppliername,purchasedate,purchaseprice,purchasepricecurrencyid,productionyear,released,rating,"
			."builtinmediatype,custommediatype,formatletterbox,formatpanandscan,formatfullframe,format16x9,upc,caid AS role,"
			."title,originaltitle,description,COUNT(caid) AS numthisprofile,a.id AS mediaid FROM $DVD_ACTOR_TABLE a,$DVD_TABLE d,$DVD_SUPPLIER_TABLE s "
			."WHERE caid='$fullname' AND a.id=d.id AND sid=purchaseplace $noadult GROUP BY a.id ORDER BY $sortby";
	}
	else if ($acttype == 'CREDIT') {
		$result = $db->sql_query("SELECT firstname,middlename,lastname,fullname AS realfullname,birthyear,COUNT(c.id) AS numresults,COUNT(DISTINCT c.id) AS numprofiles FROM $DVD_COMMON_CREDITS_TABLE cc, $DVD_CREDITS_TABLE c WHERE cc.caid=".$db->sql_escape($fullname)." AND cc.caid=c.caid GROUP BY c.caid") or die($db->sql_error());
		$actor = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		$numdistinctprofiles = $actor['numprofiles'];
		$numresults = $actor['numresults'];
		$dispname = "<a target=\"_blank\" href=\"$IMDBNameSearch" . urlencode($actor['realfullname']) . "\">$actor[realfullname]</a>";
		$castimgname = $actor['realfullname'];
		if ($actor['birthyear'])
			$castimgname .= " $actor[birthyear]";
		else
			$actor['birthyear'] = '';
		$castimgname .= '.jpg';

		if (is_readable($headcrew . $castimgname)) {
			$isize = getimagesize($headcrew . $castimgname);
			$hwidth = '';
			if ($maxheadshotwidthccw && $isize[0] > $maxheadshotwidthccw)
				$isize[0] = $maxheadshotwidthccw;
			if ($maxheadshotwidthccw)
				$hwidth = "width=$isize[0]";
        		$castimgname = $headcrew . rawurlencode($castimgname);
        		$chsimg = "<img src=\"$castimgname\" $hwidth alt=\"\" />&nbsp;";
		}

		$sql = "SELECT mediabannerfront,runningtime,suppliername,purchasedate,purchaseprice,purchasepricecurrencyid,productionyear,released,rating,"
			."builtinmediatype,custommediatype,formatletterbox,formatpanandscan,formatfullframe,format16x9,upc,caid AS role,"
			."title,originaltitle,description,COUNT(caid) AS numthisprofile,a.id AS mediaid FROM $DVD_CREDITS_TABLE a,$DVD_TABLE d,$DVD_SUPPLIER_TABLE s "
			."WHERE caid='".$db->sql_escape($fullname)."' AND a.id=d.id AND sid=purchaseplace $noadult GROUP BY a.id ORDER BY $sortby,mediaid,creditsubtype";
	}
	else if ($acttype == 'STUDIO') {
		$sql = "SELECT mediabannerfront,runningtime,suppliername,purchasedate,purchaseprice,purchasepricecurrencyid,productionyear,released,rating,a.studio AS role,"
			."builtinmediatype,custommediatype,formatletterbox,formatpanandscan,formatfullframe,format16x9,upc,"
			."title,originaltitle,description,a.id AS mediaid FROM $DVD_STUDIO_TABLE a,$DVD_TABLE d,$DVD_SUPPLIER_TABLE s "
			."WHERE studio='".$db->sql_escape($slashedfullname)."' AND a.id=d.id AND sid=purchaseplace $noadult ORDER BY $sortby,mediaid";
	}
	else if ($acttype == 'REGION') {
		$sql = "SELECT mediabannerfront,runningtime,suppliername,purchasedate,purchaseprice,purchasepricecurrencyid,productionyear,released,rating,region AS role,"
			."builtinmediatype,custommediatype,formatletterbox,formatpanandscan,formatfullframe,format16x9,upc,"
			."title,originaltitle,description,id AS mediaid FROM $DVD_TABLE d,$DVD_SUPPLIER_TABLE s "
			."WHERE sid=purchaseplace AND region='".$db->sql_escape($slashedfullname)."' $noadult ORDER BY $sortby,mediaid";
	}
	$result = $db->sql_query($sql) or die($db->sql_error());
	
	if ($acttype == 'REGION') {
		$dispname = "$lang[REGION] ".AddCommas($fullname);
		if ($fullname == '0') $dispname = $lang['ALLREGIONSDVD'];
		if ($fullname == '@') $dispname = $lang['ALLREGIONSBLURAY'];
	}
	if ($numresults == '')
		$numresults = $db->sql_numrows($result);
	if ($numdistinctprofiles == '')
		$numdistinctprofiles = $numresults;

	if ($numresults == 1) {
		if ($numdistinctprofiles == 1)
			$creditedin = "$numresults $lang[PROFILE]";
		else
			$creditedin = sprintf($lang['RESULTINPROFILE'], $numdistinctprofiles, $numresults);
	}
	else {
		if ($numdistinctprofiles == 1) {
			$creditedin = sprintf($lang['RESULTSINPROFILE'], $numdistinctprofiles, $numresults);
		}
		else {
			if ($numdistinctprofiles == $numresults)
				$creditedin = "$numresults $lang[PROFILES]";
			else
				$creditedin = sprintf($lang['RESULTSINPROFILES'], $numdistinctprofiles, $numresults);
		}
	}
	if ($acttype == 'ACTOR' || $acttype == 'CREDIT') {
		$dispname = "<table><tr><td colspan=2 style=\"font-size:14pt\" nowrap>$dispname</td></tr>"
			."<tr style=\"font-size:9pt\"><td nowrap>$lang[FIRSTNAME]:</td><td style=\"padding-left:15px\" nowrap>$actor[firstname]</td></tr>"
			."<tr style=\"font-size:9pt\"><td nowrap>$lang[LASTNAME]:</td><td style=\"padding-left:15px\" nowrap>$actor[lastname]</td></tr>"
			."<tr style=\"font-size:9pt\"><td nowrap>$lang[MIDDLENAME]:</td><td style=\"padding-left:15px\" nowrap>$actor[middlename]</td></tr>"
			."<tr style=\"font-size:9pt\"><td nowrap>$lang[BIRTHYEAR]:</td><td style=\"padding-left:15px\">$actor[birthyear]</td></tr>"
			."<tr style=\"font-size:9pt\"><td nowrap>$lang[CREDITEDIN]:</td><td style=\"padding-left:15px\" nowrap>$creditedin</td></tr>"
			."</table>";
	}
	else
		$dispname = "$dispname ($creditedin)";

	echo <<<EOT
<table class=f9 style="position:absolute; top:0px; left:0px;" width="103%" cellspacing=0 cellpadding=0>
<tr class=f9><th class=f9 colspan=2>
<table class=f9 width="100%"><tr class=f9><td class=f7 style="padding-left:3px">$chsimg</td><td class=f7 style="padding-left:10px;" align=left valign=middle nowrap>$dispname</td>
<td width="100%" nowrap class=f7 style="text-align:right;padding-top:10px; padding-right:10px;" align=right ><form method="post" action="popup.php" name="acting"><input type=hidden name=acttype value="$acttype"><input type=hidden name=fullname value="$fullname"><select name=sortby onChange="this.form.submit()"><option value=sorttitle $sel_title>$lang[SORTTITLE]</option><option value=released $sel_released>$lang[SORTRELEASED]</option><option value=productionyear $sel_prodyear>$lang[SORTYEAR]</option></select><input type=button class=input id="Close" value="$lang[CLOSE]" onClick="window.close()"></form></td>
</tr></table></th></tr><tr><td colspan=2 class=bgd></td></tr>

EOT;
	while ($actor = $db->sql_fetchrow($result)) {
			if ($acttype == 'REGION') {
				if ($actor['role'] == '0' || $actor['role'] == '@')
					$actor['role'] = $lang['ALLREGIONS'];
				else
					$actor['role'] = $lang['REGION'].' '.AddCommas($actor['role']);
			}

			$actor['released'] = ($actor['released'] === NULL? '': fix88595(ucwords(strftime($lang['DATEFORMAT'], $actor['released']))));
			$actor['purchasedate'] = ($actor['purchasedate'] == 0? '': fix88595(ucwords(strftime($lang['DATEFORMAT'], $actor['purchasedate']))));
			$j = $actor['runningtime']%60;
			if ($j < 10) $j = '0'.$j;
			$runtime = floor($actor['runningtime']/60) . ":$j";
////////		$thisurl = "$mobilepage?mediaid=$actor[mediaid]&amp;action=show";
			$thisurl = "index.php?mediaid=$actor[mediaid]&amp;action=show";

			FormatTheTitle($actor);
			if ($getimages > 0) {
				if ($getimages == 3) {
					$thumbs = "<img alt=\"\" width=80 height=112 src=\"{$img_webpathf}$thumbnails/$actor[mediaid]f.jpg\">";
				}
				else {
					$actor['id'] = $actor['mediaid'];
					$thumbs = '<img alt="" width=80 height=112 src="' . resize_jpg($actor, 'f', 80, 100) . '">';
				}
			}

			$actor['genres'] = '';
			$genreres = $db->sql_query("SELECT genre FROM $DVD_GENRES_TABLE WHERE id='$actor[mediaid]' ORDER BY dborder") or die($db->sql_error());
			while ($genrow = $db->sql_fetchrow($genreres)) {
				if ($actor['genres'] != '') $actor['genres'] .= ', ';
				$actor['genres'] .= GenreTranslation($genrow['genre']);
			}
			$db->sql_freeresult($genreres);
			unset($genrow);

			$actor['featuring'] = '';
			$actres = $db->sql_query("SELECT fullname FROM $DVD_COMMON_ACTOR_TABLE ca,$DVD_ACTOR_TABLE a WHERE a.caid=ca.caid AND a.caid>0 AND id='$actor[mediaid]' ORDER BY lineno LIMIT 3") or die($db->sql_error());
			while ($actrow = $db->sql_fetchrow($actres)) {
				if ($actor['featuring'] != '') $actor['featuring'] .= ', ';
				$actor['featuring'] .= $actrow['fullname'];
			}
			$db->sql_freeresult($actres);
			unset($actrow);

			$actor['directors'] = '';
			$actres = $db->sql_query("SELECT fullname FROM $DVD_COMMON_CREDITS_TABLE cc,$DVD_CREDITS_TABLE c WHERE c.caid=cc.caid AND c.caid>0 AND id='$actor[mediaid]' AND creditsubtype='Director' ORDER BY lineno LIMIT 3") or die($db->sql_error());
			while ($actrow = $db->sql_fetchrow($actres)) {
				if ($actor['directors'] != '') $actor['directors'] .= ', ';
				$actor['directors'] .= $actrow['fullname'];
			}
			$db->sql_freeresult($actres);
			unset($actrow);

			$fmts = '';
			if ($actor['formatpanandscan'])
				$fmts .= "$lang[PANANDSCAN]";
			if ($actor['formatfullframe']) {
				if ($fmts != '') $fmts .= ', ';
				$fmts .= $lang['FULLFRAME'];
			}
			if ($actor['formatletterbox']) {
				if ($fmts != '') $fmts .= ', ';
				if ($actor['format16x9']) {
					$fmts .= $lang['16X9']." ";
				}
				$fmts .= $lang['WIDESCREEN'];
			}
			if ($fmts != '') $fmts .= ' ';

			$typs = '';
			if ($actor['builtinmediatype'] == MEDIA_TYPE_ULTRAHD || $actor['builtinmediatype'] == MEDIA_TYPE_ULTRAHD_BLURAY || $actor['builtinmediatype'] == MEDIA_TYPE_ULTRAHD_BLURAY_DVD) {
				$typs .= $lang['ULTRAHD'];
			}
			if ($actor['builtinmediatype'] == MEDIA_TYPE_BLURAY || $actor['builtinmediatype'] == MEDIA_TYPE_BLURAY_DVD) {
				$typs .= $lang['BLURAY'];
			}
			if ($actor['builtinmediatype'] == MEDIA_TYPE_HDDVD || $actor['builtinmediatype'] == MEDIA_TYPE_HDDVD_DVD) {
				$typs .= $lang['HDDVD'];
			}
			if ($actor['builtinmediatype'] == MEDIA_TYPE_DVD || $actor['builtinmediatype'] == MEDIA_TYPE_HDDVD_DVD || $actor['builtinmediatype'] == MEDIA_TYPE_BLURAY_DVD) {
				if ($typs != '') $typs .= ', ';
				$typs .= $lang['DVD'];
			}
			if ($actor['custommediatype'] != '') {
				if ($typs != '') $typs .= ', ';
				$typs .= $actor['custommediatype'];
			}

			$actor['purchdata'] = $actor['purchasedate'];
			if ($actor['suppliername'] != 'Unknown')
				$actor['purchdata'] .= " $lang[FROM] $actor[suppliername]";
			if ($IsPrivate && $actor['purchaseprice'] != '0')
				$actor['purchdata'] .= " $lang[FOR] $actor[purchaseprice] $actor[purchasepricecurrencyid]";
			$credtimes = '';
			if (!isset($actor['numthisprofile']))
				$actor['numthisprofile'] = 1;
			if ($actor['numthisprofile'] != 1)
				$credtimes = sprintf($lang['CREDTIMES'], $actor['numthisprofile']);

			
			$prefix = "\n<tr><td class=f8 style=\"padding-left:3px; vertical-align:top\"><a target=entry href=\"$thisurl\">$thumbs</a></td><td class=f5>\n"
				."<table cellspacing=0 cellpadding=0>\n"
				."<tr><td class=f6 align=left colspan=7 nowrap><a target=entry href=\"$thisurl\">$actor[title]</a></td><td width=\"100%\" nowrap>&nbsp;</td></tr>\n"
				."<tr><td class=bgd colspan=7></td></tr>\n";
			$postfix = "<tr><td class=f5b align=left>$lang[PRODUCED]:</td><td class=f5 align=left>$actor[productionyear]</td>"
				      ."<td class=f5 width=\"30px\"></td><td class=f5 align=left nowrap><span class=f5b>$lang[RATED]:</span>&nbsp;$actor[rating]</td>"
				      ."<td class=f5 width=\"30px\"></td><td class=f5 align=left nowrap><span class=f5b>$lang[RUNTIME]:</span>&nbsp;$runtime</td><td width=\"100%\"></td></tr>"
				."<tr><td class=f5b align=left>$lang[RELEASED]:</td><td colspan=5 class=f5 align=left nowrap>$actor[released]</td><td width=\"100%\"></td></tr>"
				."<tr><td class=f5b align=left>$lang[GENRES]:</td><td colspan=5 class=f5 align=left nowrap>$actor[genres]</td><td class=f5 style=\"padding-right:10px\" align=right width=\"100%\">$credtimes</td></tr>"
				."<tr><td class=f5b align=left nowrap>$lang[DIRECTEDBY]:</td><td colspan=5 class=f5 align=left nowrap>$actor[directors]</td><td width=\"100%\"></td></tr>"
				."<tr><td class=f5b align=left>$lang[FEATURING]:</td><td colspan=5 class=f5 align=left nowrap>$actor[featuring]</td><td width=\"100%\"></td></tr>"
				."<tr><td class=f5b align=left>$lang[PURCHASED]:</td><td colspan=5 class=f5 align=left nowrap>$actor[purchdata]</td><td width=\"100%\"></td></tr>"
				."<tr><td class=f5b align=left>$lang[FORMATS]:</td><td colspan=5 class=f5 align=left nowrap>$fmts$typs</td><td class=f5 align=right width=\"100%\" style=\"padding-right:10px;font-size:8pt\">$actor[upc]</td></tr>"
				."</table></td></tr>\n<tr><td colspan=2 class=bgd></td></tr>\n";
			echo $prefix;
			OutputRoles($actor['mediaid'], $acttype, $actor['role']);
			echo $postfix;
	}
	$db->sql_freeresult($result);
	echo "</table>$endbody</html>";
	DebugSQL($db, "popup: $acttype");
	exit;
?>
