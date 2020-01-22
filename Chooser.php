<?php

error_reporting(E_ALL);
if (!defined('IN_SCRIPT'))
	define('IN_SCRIPT', 1);
include_once('global.php');

SendNoCacheHeaders('Content-Type: text/html; charset="windows-1252";');

	if (!isset($ChangedColor))
		$ChangedColor = 'yellow';
	$limit = 5;
	$thelimit = "<select onChange=\"HandleSelectChange(this)\" style=\"vertical-align:middle\" name=\"limit\">";
	$thelimit .= "<option value=\"1\">1</option>";
	for ($i=5; $i<55; $i+=5) {
		$sel = ''; if ($i == $limit) $sel = 'selected';
		$thelimit .= "<option value=\"$i\" $sel>$i</option>";
	}
	$thelimit .= "<option value=\"100\" $sel>100</option>";
	$thelimit .= "</select>\n";
	$thelimit = sprintf($lang['CHOOSERHEADER'], $thelimit);

	$collections = "<b>$lang[COLLECTIONTYPE]</b>:<br><select onChange=\"HandleSelectChange(this)\" name=\"collectiontype\"><option value=\"?\" selected>$lang[ALL]</option>";
 	$res = $db->sql_query("SELECT DISTINCT collectiontype FROM $DVD_TABLE ORDER BY collectiontype") or die($db->sql_error());
	while ($val = $db->sql_fetchrow($res)) {
		$collections .= "<option value=\"$val[collectiontype]\">".$lang[strtoupper($val['collectiontype'])]."</option>";
	}
	$db->sql_freeresult($res);

	$res = $db->sql_query("SELECT COUNT(*) AS itemcount FROM $DVD_TABLE WHERE loaninfo != ''") or die($db->sql_error());
	$val = $db->sql_fetchrow($res);
	$db->sql_freeresult($res);
	if ($val['itemcount'] != 0) {
		$collections .= "<option value=loaned>$lang[LOANED]</option>";
	}

	foreach ($masterauxcolltype as $num => $auxcoltype) {
		if ($auxcoltype != '') {
			$collections .= "<option value=$num>$auxcoltype</option>";
		}
	}
	$collections .= "</select>\n";

	$mediatable = <<<EOT
<table cellpadding=3 border=1><tr><th>$lang[MEDIATYPE]</th></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('mediatypedvd')" src="gfx/dontcare.jpg" id="mediatypedvd_img"><input type="hidden" name="mediatypedvd" id="mediatypedvd_input" value="?">&nbsp;$lang[DVD]</td></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('mediatypehddvd')" src="gfx/dontcare.jpg" id="mediatypehddvd_img"><input type="hidden" name="mediatypehddvd" id="mediatypehddvd_input" value="?">&nbsp;$lang[HDDVD]</td></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('mediatypebluray')" src="gfx/dontcare.jpg" id="mediatypebluray_img"><input type="hidden" name="mediatypebluray" id="mediatypebluray_input" value="?">&nbsp;$lang[BLURAY]</td></tr>
</table>
EOT;

	$formattable = <<<EOT
<table cellpadding=3 border=1><tr><th colspan=2>$lang[FORMAT]</th></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('formatletterbox')" src="gfx/dontcare.jpg" id="formatletterbox_img"><input type="hidden" name="formatletterbox" id="formatletterbox_input" value="?">&nbsp;$lang[WIDESCREEN]</td>
  <td><img style="vertical-align:middle" onClick="SwitchState('formatpanandscan')" src="gfx/dontcare.jpg" id="formatpanandscan_img"><input type="hidden" name="formatpanandscan" id="formatpanandscan_input" value="?">&nbsp;$lang[PANANDSCAN]</td></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('formatfullframe')" src="gfx/dontcare.jpg" id="formatfullframe_img"><input type="hidden" name="formatfullframe" id="formatfullframe_input" value="?">&nbsp;$lang[FULLFRAME]</td>
  <td><img style="vertical-align:middle" onClick="SwitchState('format16x9')" src="gfx/dontcare.jpg" id="format16x9_img"><input type="hidden" name="format16x9" id="format16x9_input" value="?">&nbsp;{$lang['16X9']}</td></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('formatdualsided')" src="gfx/dontcare.jpg" id="formatdualsided_img"><input type="hidden" name="formatdualsided" id="formatdualsided_input" value="?">&nbsp;$lang[DUALSIDED]</td>
  <td><img style="vertical-align:middle" onClick="SwitchState('formatduallayered')" src="gfx/dontcare.jpg" id="formatduallayered_img"><input type="hidden" name="formatduallayered" id="formatduallayered_input" value="?">&nbsp;$lang[DUALLAYERED]</td></tr>
</table>
EOT;

	$bstable = <<<EOT
<table cellpadding=3 border=1><tr><th>$lang[BOXSET]</th></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('boxchild')" src="gfx/dontcare.jpg" id="boxchild_img"><input type="hidden" name="boxchild" id="boxchild_input" value="?">&nbsp;$lang[CHOOSERBOXCHILD]</td></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('boxparent')" src="gfx/dontcare.jpg" id="boxparent_img"><input type="hidden" name="boxparent" id="boxparent_input" value="?">&nbsp;$lang[CHOOSERBOXPARENT]</td></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('boxtvparent')" src="gfx/dontcare.jpg" id="boxtvparent_img"><input type="hidden" name="boxtvparent" id="boxtvparent_input" value="?">&nbsp;$lang[CHOOSERBOXTVPARENT]</td></tr>
</table>
EOT;

	$extrastable = <<<EOT
<table cellpadding=3 border=1><tr><th colspan=3>$lang[EXTRAS]</th></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('featuresceneaccess')" src="gfx/dontcare.jpg" id="featuresceneaccess_img"><input type="hidden" name="featuresceneaccess" id="featuresceneaccess_input" value="?">&nbsp;$lang[SCENEACCESS]</td>
  <td><img style="vertical-align:middle" onClick="SwitchState('featurecommentary')" src="gfx/dontcare.jpg" id="featurecommentary_img"><input type="hidden" name="featurecommentary" id="featurecommentary_input" value="?">&nbsp;$lang[COMMENTARY]</td>
  <td><img style="vertical-align:middle" onClick="SwitchState('featuretrailer')" src="gfx/dontcare.jpg" id="featuretrailer_img"><input type="hidden" name="featuretrailer" id="featuretrailer_input" value="?">&nbsp;$lang[TRAILER]</td></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('featurephotogallery')" src="gfx/dontcare.jpg" id="featurephotogallery_img"><input type="hidden" name="featurephotogallery" id="featurephotogallery_input" value="?">&nbsp;$lang[PHOTOGALLERY]</td>
  <td><img style="vertical-align:middle" onClick="SwitchState('featuredeletedscenes')" src="gfx/dontcare.jpg" id="featuredeletedscenes_img"><input type="hidden" name="featuredeletedscenes" id="featuredeletedscenes_input" value="?">&nbsp;$lang[DELETEDSCENES]</td>
  <td><img style="vertical-align:middle" onClick="SwitchState('featuremakingof')" src="gfx/dontcare.jpg" id="featuremakingof_img"><input type="hidden" name="featuremakingof" id="featuremakingof_input" value="?">&nbsp;$lang[MAKINGOF]</td></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('featureproductionnotes')" src="gfx/dontcare.jpg" id="featureproductionnotes_img"><input type="hidden" name="featureproductionnotes" id="featureproductionnotes_input" value="?">&nbsp;$lang[PRODUCTIONNOTES]</td>
  <td><img style="vertical-align:middle" onClick="SwitchState('featuregame')" src="gfx/dontcare.jpg" id="featuregame_img"><input type="hidden" name="featuregame" id="featuregame_input" value="?">&nbsp;$lang[GAME]</td>
  <td><img style="vertical-align:middle" onClick="SwitchState('featuredvdromcontent')" src="gfx/dontcare.jpg" id="featuredvdromcontent_img"><input type="hidden" name="featuredvdromcontent" id="featuredvdromcontent_input" value="?">&nbsp;$lang[DVDROMCONTENT]</td></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('featuremultiangle')" src="gfx/dontcare.jpg" id="featuremultiangle_img"><input type="hidden" name="featuremultiangle" id="featuremultiangle_input" value="?">&nbsp;$lang[MULTIANGLE]</td>
  <td><img style="vertical-align:middle" onClick="SwitchState('featuremusicvideos')" src="gfx/dontcare.jpg" id="featuremusicvideos_img"><input type="hidden" name="featuremusicvideos" id="featuremusicvideos_input" value="?">&nbsp;$lang[MUSICVIDEOS]</td>
  <td><img style="vertical-align:middle" onClick="SwitchState('featureinterviews')" src="gfx/dontcare.jpg" id="featureinterviews_img"><input type="hidden" name="featureinterviews" id="featureinterviews_input" value="?">&nbsp;$lang[INTERVIEWS]</td></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('featurestoryboardcomparisons')" src="gfx/dontcare.jpg" id="featurestoryboardcomparisons_img"><input type="hidden" name="featurestoryboardcomparisons" id="featurestoryboardcomparisons_input" value="?">&nbsp;$lang[STORYBOARDCOMPARISONS]</td>
  <td><img style="vertical-align:middle" onClick="SwitchState('featureouttakes')" src="gfx/dontcare.jpg" id="featureouttakes_img"><input type="hidden" name="featureouttakes" id="featureouttakes_input" value="?">&nbsp;$lang[OUTTAKES]</td>
  <td><img style="vertical-align:middle" onClick="SwitchState('featureclosedcaptioned')" src="gfx/dontcare.jpg" id="featureclosedcaptioned_img"><input type="hidden" name="featureclosedcaptioned" id="featureclosedcaptioned_input" value="?">&nbsp;$lang[CLOSEDCAPTIONED]</td></tr>
  <tr><td><img style="vertical-align:middle" onClick="SwitchState('featurethxcertified')" src="gfx/dontcare.jpg" id="featurethxcertified_img"><input type="hidden" name="featurethxcertified" id="featurethxcertified_input" value="?">&nbsp;$lang[THXCERTIFIED]</td></tr>
</table>
EOT;

	$genretable = "<table cellpadding=3 border=1><tr><th colspan=5>$lang[GENRES]</th></tr><tr>";
	$res = $db->sql_query("SELECT genre,COUNT(genre) AS counts FROM $DVD_GENRES_TABLE GROUP BY genre ORDER BY genre") or die($db->sql_error());
	$tmpcount = 1;
	$valgen = "\tgenrecount = 0;\n";
	while ($val = $db->sql_fetchrow($res)) {
		if ($val['counts'] != 0) {
			if ($tmpcount++ % 5 == 1) $genretable .= "</tr>\n<tr>";
			$gen = str_replace(' ', '_', $val['genre']);
			$valgen .= "\tif ($('genre_{$gen}_input').value == '1') genrecount++;\n";
			$genretable .= "<td><img style=\"vertical-align:middle\" onClick=\"SwitchState('genre_$gen')\" src=\"gfx/dontcare.jpg\""
					." id=\"genre_{$gen}_img\"><input type=\"hidden\" name=\"genre_$gen\" id=\"genre_{$gen}_input\""
					." value=\"?\">&nbsp;".GenreTranslation($val['genre'])."</td>";
		}
	}
	$db->sql_freeresult($res);
	$genretable .= "</tr></table>";

	$aspectcombo = '';
	$res = $db->sql_query("SELECT DISTINCT formataspectratio FROM $DVD_TABLE ORDER BY formataspectratio") or die($db->sql_error());
	if ($db->sql_numrows($res) < 2) {
		$val = $db->sql_fetchrow($res);
		$aspectcombo .= "<tr><td align=right>$lang[ASPECTRATIO]:</td><td>$val[formataspectratio]</td></tr>";
	}
	else {
		$aspectcombo .= "<tr><td align=right>$lang[ASPECTRATIO]:</td><td><select onChange=\"HandleSelectChange(this)\" name=\"formataspectratio\"><option value=\"?\" selected>$lang[CHOOSERDONTCARE]</option>";
		while ($val = $db->sql_fetchrow($res)) {
			$aspectcombo .= "<option value=\"$val[formataspectratio]\">$val[formataspectratio]</option>";
		}
		$aspectcombo .= "</select></td></tr>";
	}
	$db->sql_freeresult($res);

	$vidstdcombo = '';
	$res = $db->sql_query("SELECT DISTINCT formatvideostandard FROM $DVD_TABLE ORDER BY formatvideostandard") or die($db->sql_error());
	if ($db->sql_numrows($res) < 2) {
		$val = $db->sql_fetchrow($res);
		$vidstdcombo .= "<tr><td align=right>$lang[CHOOSERVIDEOSTANDARD]:</td><td>$val[formatvideostandard]</td></tr>";
	}
	else {
		$vidstdcombo .= "<tr><td align=right>$lang[CHOOSERVIDEOSTANDARD]:</td><td><select onChange=\"HandleSelectChange(this)\" name=\"formatvideostandard\"><option value=\"?\" selected>$lang[CHOOSERDONTCARE]</option>";
		while ($val = $db->sql_fetchrow($res)) {
			$vidstdcombo .= "<option value=\"$val[formatvideostandard]\">$val[formatvideostandard]</option>";
		}
		$vidstdcombo .= "</select></td></tr>";
	}
	$db->sql_freeresult($res);

	$coocombo = '';
	$res = $db->sql_query("SELECT DISTINCT countryoforigin FROM $DVD_TABLE ORDER BY countryoforigin") or die($db->sql_error());
	if ($db->sql_numrows($res) < 2) {
		$val = $db->sql_fetchrow($res);
		$coocombo .= "<tr><td align=right>$lang[COUNTRYOFORIGIN]:</td><td>$val[countryoforigin]</td></tr>";
	}
	else {
		$coocombo .= "<tr><td align=right>$lang[COUNTRYOFORIGIN]:</td><td><select onChange=\"HandleSelectChange(this)\" name=\"countryoforigin\"><option value=\"?\" selected>$lang[CHOOSERDONTCARE]</option>";
		while ($val = $db->sql_fetchrow($res)) {
			$vvv = $val['countryoforigin'];
			if ($vvv == '')
				$vvv = $lang['UNKNOWN'];
			$coocombo .= "<option value=\"$val[countryoforigin]\">$vvv</option>";
		}
		$coocombo .= "</select></td></tr>";
	}
	$db->sql_freeresult($res);

	$regioncombo = '';
	$res = $db->sql_query("SELECT DISTINCT region FROM $DVD_TABLE ORDER BY region") or die($db->sql_error());
	$rgnlist = '';
	while ($val = $db->sql_fetchrow($res)) {
		$rgnlist .= $val['region'];
	}
	$db->sql_freeresult($res);
	if (strlen($rgnlist) < 2) {
		$regioncombo .= "<tr><td align=right>$lang[REGION]:</td><td>$rgnlist</td></tr>";
	}
	else {
		$PossibleRegions = '0123456@ABC';
		$regioncombo .= "<tr><td align=right>$lang[REGION]:</td><td><select onChange=\"HandleSelectChange(this)\" name=\"region\"><option value=\"?\" selected>$lang[CHOOSERDONTCARE]</option>";
		for ($i=0; $i<=strlen($PossibleRegions); $i++) {
			if (strpos($rgnlist, substr($PossibleRegions, $i, 1)) !== false) {
				$val = $PossibleRegions{$i};
				$disp = $PossibleRegions{$i};
				if ($val == '0') $disp = $lang['ALLREGIONSDVD'];
				if ($val == '@') $disp = $lang['ALLREGIONSBLURAY'];
				$regioncombo .= "<option value=\"$val\">$disp</option>";
			}
		}
		$regioncombo .= "</select></td></tr>";
	}

	$casecombo = '';
	$res = $db->sql_query("SELECT DISTINCT casetype FROM $DVD_TABLE ORDER BY casetype") or die($db->sql_error());
	if ($db->sql_numrows($res) < 2) {
		$val = $db->sql_fetchrow($res);
		$casecombo .= "<tr><td align=right>$lang[CASETYPE]:</td><td>$val[casetype]</td></tr>";
	}
	else {
		$casecombo .= "<tr><td align=right>$lang[CASETYPE]:</td><td><select onChange=\"HandleSelectChange(this)\" name=\"casetype\"><option value=\"?\" selected>$lang[CHOOSERDONTCARE]</option>";
		while ($val = $db->sql_fetchrow($res)) {
			$casecombo .= "<option value=\"$val[casetype]\">$val[casetype]</option>";
		}
		$casecombo .= "</select>&nbsp;<span><img style=\"vertical-align:middle\" onClick=\"SwitchState('caseslipcover')\""
			." src=\"gfx/dontcare.jpg\" id=\"caseslipcover_img\"><input type=\"hidden\" name=\"caseslipcover\""
			." id=\"caseslipcover_input\" value=\"?\">&nbsp;$lang[SLIPCOVER]</span></td></tr>";
	}
	$db->sql_freeresult($res);

	$ratingcombo = '';
	$sql = "SELECT IF (LOCATE('.',id) = '0',0,SUBSTRING(id,locate('.',id)+1,LENGTH(id)-LOCATE('.',id)))+0 AS locality,rating"
		." FROM $DVD_TABLE GROUP BY locality,rating ORDER BY locality,rating";
	$res = $db->sql_query($sql) or die($db->sql_error());
	if ($db->sql_numrows($res) < 2) {
		$val = $db->sql_fetchrow($res);
		$ratingcombo .= "<tr><td align=right>$lang[RATING]:</td><td>$val[rating] (".$lang['LOCALE'.$val['locality']].")</td></tr>";
	}
	else {
		$ratingcombo .= "<tr><td align=right>$lang[RATING]:</td><td><select onChange=\"HandleSelectChange(this)\" name=\"rating\"><option value=\"?\" selected>$lang[CHOOSERDONTCARE]</option>";
		while ($val = $db->sql_fetchrow($res)) {
			$ratingcombo .= "<option value=\"$val[locality].$val[rating]\">$val[rating] (".$lang['LOCALE'.$val['locality']].")</option>";
		}
		$ratingcombo .= "</select></td></tr>";
	}
	$db->sql_freeresult($res);

	$suppliercombo = '';
	if (DisplayIfIsPrivateOrAlways($displayplace)) {
		$res = $db->sql_query("SELECT sid,suppliername FROM $DVD_SUPPLIER_TABLE ORDER BY suppliername") or die($db->sql_error());
		if ($db->sql_numrows($res) < 2) {
			$val = $db->sql_fetchrow($res);
			$suppliercombo .= "<tr><td align=right>$lang[PURCHASEPLACE]:</td><td>$val[suppliername]</td></tr>";
		}
		else {
			$suppliercombo .= "<tr><td align=right>$lang[PURCHASEPLACE]:</td><td><select onChange=\"HandleSelectChange(this)\" name=\"purchaseplace\"><option value=\"?\" selected>$lang[CHOOSERDONTCARE]</option>";
			while ($val = $db->sql_fetchrow($res)) {
				$suppliercombo .= "<option value=\"$val[sid]\">$val[suppliername]</option>";
			}
			$suppliercombo .= "</select></td></tr>";
		}
		$db->sql_freeresult($res);
	}

	$watchedby = '';
	if ($IsPrivate) {
		$res = $db->sql_query("SELECT u.uid,firstname,lastname,COUNT(id) AS numwatched FROM $DVD_EVENTS_TABLE e, $DVD_USERS_TABLE u WHERE e.uid=u.uid AND eventtype='watched' GROUP BY lastname,firstname") or die($db->sql_error());
		if ($db->sql_numrows($res) >= 1) {
			$watchedby .=  "<table cellpadding=3 border=1><tr><th colspan=3>$lang[CHOOSERWATCHEDBY]</th></tr><tr>";
			$count = 1;
			while ($val = $db->sql_fetchrow($res)) {
				if ($count++ % 3 == 1)
					$watchedby .=  "</tr>\n<tr>";
				$name = $val['uid'];
  				$watchedby .=  "<td><img style=\"vertical-align:middle\" onClick=\"SwitchState('watched_$name')\" src=\"gfx/dontcare.jpg\""
						." id=\"watched_{$name}_img\"><input type=\"hidden\" name=\"watched_$name\" id=\"watched_{$name}_input\""
						." value=\"?\">&nbsp;$val[firstname] " . HideName($val['lastname']) . "</td>";
			}
			$watchedby .=  "</tr></table>\n";
		}
		$db->sql_freeresult($res);
	}

	$tagtable = '';
	$res = $db->sql_query("SELECT DISTINCT fullyqualifiedname FROM $DVD_TAGS_TABLE WHERE fullyqualifiedname NOT LIKE 'tabs/%' AND fullyqualifiedname NOT LIKE 'tabs'") or die($db->sql_error());
	if ($db->sql_numrows($res) >= 1) {
		$tagtable .= "<table cellpadding=3 border=1><tr><th colspan=3>$lang[TAGS]</th></tr><tr>";
		$count = 1;
		while ($val = $db->sql_fetchrow($res)) {
			if ($count++ % 3 == 1)
				$tagtable .= "</tr>\n<tr>";
			$name = urlencode($val['fullyqualifiedname']);
  			$tagtable .= "<td><img style=\"vertical-align:middle\" onClick=\"SwitchState('tag_$name')\" src=\"gfx/dontcare.jpg\""
				." id=\"tag_{$name}_img\"><input type=\"hidden\" name=\"tag_$name\" id=\"tag_{$name}_input\""
				." value=\"?\">&nbsp;$val[fullyqualifiedname]</td>\n";
		}
		$tagtable .= "</tr></table>";
	}
	$db->sql_freeresult($res);

	echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
<title>$lang[CHOOSERSHORT] - $lang[CHOOSERDESC]</title>
<link rel="stylesheet" type="text/css" href="format.css.php">
<script type="text/javascript">
function $(obj) {
	return(document.getElementById(obj));
}

function IsANumber(obj) {
	if (obj.value != '?') {
		if (/^\d+$/.test(obj.value) == false) {
			alert('"' + obj.value + '" is not a number. Please enter a number of minutes.');
			obj.focus();
			return(false);
		}
	}
	return(true);
}

function ValidateThenSubmit() {
var theform=$('search_form');

// Check number of genres
$valgen

	if (genrecount > 5) {
		alert('There can be no more than 5 genres selected');
		return(false);
	}

	if (IsANumber(document.getElementsByName('rtimelower')[0]) == false)
		return(false);
	if (IsANumber(document.getElementsByName('rtimehigher')[0]) == false)
		return(false);

// Made it!
	return(true);
}

function HandleTextChange(textobj) {
	if (textobj.value != '')
		textobj.style.backgroundColor = '$ChangedColor';
	else
		textobj.style.backgroundColor = '';
}

function HandleSelectChange(selectobj) {
	if (selectobj.selectedIndex != 0)
		selectobj.style.backgroundColor = '$ChangedColor';
	else
		selectobj.style.backgroundColor = '';
}

function ResetToDontCare() {
var theform=$('search_form');

	for (i=0; i<theform.elements.length; i++) {
		switch (theform.elements[i].type) {
		case 'select-one':
		case 'text':
			theform.elements[i].style.backgroundColor = '';
			break;
		case 'hidden':
			theform.elements[i].value = '?';
			$(theform.elements[i].id.replace('_input', '_img')).src = "gfx/dontcare.jpg";
			theform.elements[i].parentNode.style.backgroundColor = '';
			break;
		default:
			break;
		}
	}
}

function FixIFrame() {
var InMenu='0';
	if (window.name == 'entry')
		InMenu = '1';
	$('search_form').action = "Picker.php?InMenu="+InMenu;
	$('theiframe').src = "Picker.php?InMenu="+InMenu;
}

function SwitchState(obj) {
	switch ($(obj+"_input").value) {
	case "-1":
		$(obj+"_input").value = '?';
		$(obj+"_img").src = "gfx/dontcare.jpg";
		$(obj+"_img").parentNode.style.backgroundColor = '';
		break;
	case "?":
		$(obj+"_input").value = 1;
		$(obj+"_img").src = "gfx/wantit.jpg";
		$(obj+"_img").parentNode.style.backgroundColor = '$ChangedColor';
		break;
	case "1":
		$(obj+"_input").value = -1;
		$(obj+"_img").src = "gfx/dontwantit.jpg";
		$(obj+"_img").parentNode.style.backgroundColor = '$ChangedColor';
		break;
	}
}
function ManageRT(obj, inout) {
	if (inout == 'in') {
		if (obj.value == '?') {
			obj.value = '';
		}
	}
	else if (inout == 'out') {
		if (obj.value == '' || obj.value == '?') {
			obj.value = '?';
			obj.style.backgroundColor = '';
		}
		else
			obj.style.backgroundColor = '$ChangedColor';
	}
}
</script>
</head>
<body class=f6 onLoad="FixIFrame()">
<form method=post action="#" target=theiframe id=search_form onSubmit="return(ValidateThenSubmit())">
<h1>$thelimit</h1>
<input type="submit" value="$lang[CHOOSERPICK]">&nbsp;<input type="reset" value="$lang[CHOOSERRESET]" onClick="ResetToDontCare()">
<br><iframe style="border:medium double black" src=""" width=800 height="240" id="theiframe" name="theiframe"></iframe><br>
<table><tr>
<td align=center valign=top>
$collections
$mediatable
</td>
<td align=center valign=top>
$formattable
$bstable
</td>
<td valign=top>
$watchedby
</td></tr></table>
$genretable
<table cellpadding=3 border=1><tr><th>$lang[RUNNINGTIME]</th></tr>
<tr><td align="center">
Minimum runtime <input onFocus="ManageRT(this, 'in')" onBlur="ManageRT(this, 'out')" type="text" size="5" value="?" name="rtimelower">&nbsp;<input onFocus="ManageRT(this, 'in')" onBlur="ManageRT(this, 'out')" type="text" size="5" value="?" name="rtimehigher">Maximum runtime
</td></tr></table>
<table>
$aspectcombo
$vidstdcombo
$coocombo
$regioncombo
$casecombo
$ratingcombo
$suppliercombo
</table>
<br><table>
  <tr><td>$lang[CHOOSERFINDTITLE]:</td><td><input onChange="HandleTextChange(this)" type="text" name="title" width=40></td></tr>
  <tr><td>$lang[CHOOSERFINDOFEATURES]:</td><td><input onChange="HandleTextChange(this)" type="text" name="featureother" width=40></td></tr>
  <tr><td>$lang[CHOOSERFINDOVERVIEW]:</td><td><input onChange="HandleTextChange(this)" type="text" name="overview" width=40></td></tr>
  <tr><td>$lang[CHOOSERFINDEGGS]:</td><td><input onChange="HandleTextChange(this)" type="text" name="eastereggs" width=40></td></tr>
  <tr><td>$lang[CHOOSERFINDNOTES]:</td><td><input onChange="HandleTextChange(this)" type="text" name="notes" width=40></td></tr>
  <tr><td>$lang[CHOOSERFINDACTORS]:</td><td><input onChange="HandleTextChange(this)" type="text" name="actors" width=40></td></tr>
  <tr><td>$lang[CHOOSERFINDROLES]:</td><td><input onChange="HandleTextChange(this)" type="text" name="roles" width=40></td></tr>
  <tr><td>$lang[CHOOSERFINDCREW]:</td><td><input onChange="HandleTextChange(this)" type="text" name="credits" width=40></td></tr>
  <tr><td>$lang[CHOOSERFINDSTUDIOS]:</td><td><input onChange="HandleTextChange(this)" type="text" name="studios" width=40></td></tr>
</table>
$lang[CHOOSERFINDTITLEEXPLAIN]<br>
$extrastable
$tagtable
  <br><br><br><br><br><br><br><font size="-2">
<h2>Things that cannot yet be selected:</h2>
      collectionnumber
  <br>productionyear
  <br>released
  <br>reviewfilm
  <br>reviewvideo
  <br>reviewaudio
  <br>reviewextras
  <br>srp
  <br>purchaseprice
  <br>purchasedate
  <br>loaninfo
  <br>loandue
  <br>locks
  <br>Audio
  <br>Subtitles</font>
</form>
</body>
</html>
EOT;
?>
