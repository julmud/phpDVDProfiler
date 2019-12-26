<?php
/*	$Id: userpref.php,v 1.0 2005/01/01 14:31:55 fred Exp $	*/

define('IN_SCRIPT', 1);
include_once('version.php');
include_once('global.php');

function GetSkins() {
global $debugskin;

	function cmpcase($a, $b) {
		return(strcasecmp($a['displayname'], $b['displayname']));
	}

	$TheSkins = array();
	if ($handle=@opendir('skins')) {
		while (($dn=readdir($handle)) !== false) {
			if ($dn == '.' || $dn == '..')
				continue;
			if (!is_dir("skins/$dn"))
				continue;
			if ($h2=opendir('skins/'.$dn)) {
				while (($fn=readdir($h2)) !== false) {
					if (preg_match('/.*\.htm[l]$/i', $fn)) {
						if (is_readable("skins/$dn/$fn"))
							$TheSkins[] = array('dirname' => $dn, 'filename' => $fn, 'displayname' => preg_replace('/\.htm[l]$/i', '', $fn));
					}
					if ($debugskin && preg_match('/.*\.htm[l].hidden$/i', $fn)) {
						if (is_readable("skins/$dn/$fn"))
							$TheSkins[] = array('dirname' => $dn, 'filename' => $fn, 'displayname' => preg_replace('/\.htm[l].hidden$/i', ' *** HIDDEN ***', $fn));
					}
				}
				closedir($h2);
			}
		}
		closedir($handle);
	}
	usort($TheSkins, "cmpcase");
	return($TheSkins);
}

	if ($allowskins) {
		$TheSkins = GetSkins();
		if (count($TheSkins) == 0)
			$allowskins = false;
	}

	if (!$allowactorsort && 
	    !$allowsecondcol &&
	    !$allowthirdcol &&
	    !$allowdefaultsorttype &&
	    !$allowtitledesc &&
	    !$allowlocale &&
	    !$allowstickyboxsets &&
	    !$allowskins &&
	    !$allowpopupimages &&
	    !$allowwidths) {
		header('Content-Type: text/html; charset="windows-1252";');
		echo<<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
<title>{$lang['PREFS']['USERPREFS']}</title>
<link rel="stylesheet" type="text/css" href="format.css.php">
</head>
<body>
<center><table width="100%" class=f1><tr><td>{$lang['PREFS']['USERPREFS']}</td></tr></table><br><br></center>
<center><table width="100%" class=bgl><tr><td align=center class=a>{$lang['PREFS']['NOPREFSETTABLE']}</td></tr></table><br><br></center>
<center><table width="100%" class=bgl><tr><td align=center class=a>
<center><a class="f3 URLref" href="index.php" target="_top">$lang[IMPORTCLICK]</a></center>
</td></tr></table></center><br><br>
$endbody</html>
EOT;
		exit;
	}

	SendNoCacheHeaders('Content-Type: text/html; charset="windows-1252";');

	$ddstyle = 'style="margin:3px 0 0 5px"';
	echo<<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
<title>{$lang['PREFS']['USERPREFS']}</title>
<link rel="stylesheet" type="text/css" href="format.css.php">
<script type="text/javascript">
<!--

function getexpirydate(numdays) {
	Today = new Date();
	Today.setTime(Date.parse(Today) + numdays*24*60*60*1000);
	return(Today.toUTCString());
}

function getcookie(cookiename) {
var cookiestring = "" + document.cookie;
var index1 = cookiestring.indexOf(cookiename);
var index2;

	if (index1 == -1 || cookiename == "")
		return(""); 
	index2 = cookiestring.indexOf(';', index1);
	if (index2 == -1)
		index2 = cookiestring.length; 
	return(unescape(cookiestring.substring(index1+cookiename.length+1, index2)));
}

function setcookie(name, value, durationindays) {
	cookiestring = name + "=" + escape(value) + ";EXPIRES=" + getexpirydate(durationindays);
	document.cookie = cookiestring;
	return(true);
}

function ProcessADropDown(groupobj, groupname, expiresindays)
{
	if (groupobj[groupobj.selectedIndex].value == 'sitedefault')
		setcookie(groupname, getcookie(groupname), -1);
	else
		setcookie(groupname, groupobj[groupobj.selectedIndex].value, expiresindays);
}

function ResetWidths(obj)
{
	if (obj.checked == true) {
		setcookie('widthgt800', getcookie('widthgt800'), -1);
	}
	return true;
}

function HandleCookies()
{


EOT;
	if ($allowactorsort) echo "\tProcessADropDown(document.config.actorsort, 'actorsort', 10*365);\n";
	if ($allowsecondcol) echo "\tProcessADropDown(document.config.secondcol, 'secondcol', 10*365);\n";
	if ($allowthirdcol) echo "\tProcessADropDown(document.config.thirdcol, 'thirdcol', 10*365);\n";
	if ($allowdefaultsorttype) echo "\tProcessADropDown(document.config.defaultsorttype, 'defaultsorttype', 10*365);\n";
	if ($allowtitledesc) echo "\tProcessADropDown(document.config.titledesc, 'titledesc', 10*365);\n";
	if ($allowlocale) echo "\tProcessADropDown(document.config.locale, 'locale', 10*365);\n";
	if ($allowstickyboxsets) echo "\tProcessADropDown(document.config.stickyboxsets, 'stickyboxsets', 10*365);\n";
	if ($allowpopupimages) echo "\tProcessADropDown(document.config.popupimages, 'popupimages', 10*365);\n";
	if ($allowskins) echo "\tProcessADropDown(document.config.skins, 'skinfile', 10*365);\n";
	if ($allowwidths) echo "\tResetWidths(document.config.widths);\n";
// remove the temporary cookies to reduce user confusion when changing defaultsorttype
	echo<<<EOT
	setcookie('cookiesort', getcookie('cookiesort'), -1);
	setcookie('cookieorder', getcookie('cookieorder'), -1);
	document.location.reload();
}
// -->
</script>
</head>
<body>
<center><table width="100%" class=f1><tr><td>{$lang['PREFS']['USERPREFS']}</td></tr></table></center>
<center><a class="URLref" href="index.php" target="_top">$lang[IMPORTCLICK]</a></center>
<form name=config action="javascript:;" onSubmit="HandleCookies();return true">
<table class=bgl align=center width="75%" border=1>
<tr class=t><th>{$lang['PREFS']['PREFERENCE']}</th><th>{$lang['PREFS']['CURRENTVAL']}</th><th>{$lang['PREFS']['SELECTIONS']}</th></tr>
EOT;

	if ($allowactorsort) {
		$n1 = $n2 = $n3 = $n4 = $n5 = '';
		if (isset($_COOKIE['actorsort'])) {
			$a = $lang['PREFS']['ACTORSORT'][$actorsort];
			if ($_COOKIE['actorsort'] == '0')
				$n1 = 'selected';
			else if ($_COOKIE['actorsort'] == '1')
				$n2 = 'selected';
			else if ($_COOKIE['actorsort'] == '2')
				$n3 = 'selected';
		}
		else {
			$a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['ACTORSORT'][$siteactorsort]."]";
			$n4 = 'selected';
		}
		echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['ACTORSORT']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="actorsort">
<option value="0" $n1>{$lang['PREFS']['ACTORSORT'][0]}</option>
<option value="1" $n2>{$lang['PREFS']['ACTORSORT'][1]}</option>
<option value="2" $n3>{$lang['PREFS']['ACTORSORT'][2]}</option>
<option value="sitedefault" $n4>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['ACTORSORT'][$siteactorsort]}]</option>
</select></td></tr>
EOT;
	}

	if ($allowsecondcol) {
		$n1 = $n2 = $n3 = $n4 = $n5 = $n6 = $n7 = $n8 = $n9 = $n10 = $n11 = '';
		if (isset($_COOKIE['secondcol'])) {
			$a = $lang['PREFS']['COLUMNS'][$secondcol];
			if ($_COOKIE['secondcol'] == 'released')
				$n1 = 'selected';
			else if ($_COOKIE['secondcol'] == 'productionyear')
				$n2 = 'selected';
			else if ($_COOKIE['secondcol'] == 'purchasedate')
				$n3 = 'selected';
			else if ($_COOKIE['secondcol'] == 'collectionnumber')
				$n4 = 'selected';
			else if ($_COOKIE['secondcol'] == 'runningtime')
				$n5 = 'selected';
			else if ($_COOKIE['secondcol'] == 'rating')
				$n6 = 'selected';
			else if ($_COOKIE['secondcol'] == 'genres')
				$n7 = 'selected';
			else if ($_COOKIE['secondcol'] == 'reviews')
				$n10 = 'selected';
			else if ($_COOKIE['secondcol'] == 'director')
				$n11 = 'selected';
			else if ($_COOKIE['secondcol'] == 'none')
				$n9 = 'selected';
		}
		else {
			$a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['COLUMNS'][$sitesecondcol]."]";
			$n8 = 'selected';
		}
		echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['COLUMNS']['SECONDNAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="secondcol">
<option value="released" $n1>{$lang['PREFS']['COLUMNS']['released']}</option>
<option value="productionyear" $n2>{$lang['PREFS']['COLUMNS']['productionyear']}</option>
<option value="purchasedate" $n3>{$lang['PREFS']['COLUMNS']['purchasedate']}</option>
<option value="collectionnumber" $n4>{$lang['PREFS']['COLUMNS']['collectionnumber']}</option>
<option value="runningtime" $n5>{$lang['PREFS']['COLUMNS']['runningtime']}</option>
<option value="rating" $n6>{$lang['PREFS']['COLUMNS']['rating']}</option>
<option value="genres" $n7>{$lang['PREFS']['COLUMNS']['genres']}</option>
<option value="reviews" $n10>{$lang['PREFS']['COLUMNS']['reviews']}</option>
<option value="director" $n11>{$lang['PREFS']['COLUMNS']['director']}</option>
<option value="sitedefault" $n8>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['COLUMNS'][$sitesecondcol]}]</option>
<option value="none" $n9>{$lang['PREFS']['COLUMNS']['none']}</option>
</select></td></tr>
EOT;
	}

	if ($allowthirdcol) {
		$n1 = $n2 = $n3 = $n4 = $n5 = $n6 = $n7 = $n8 = $n9 = $n10 = $n11 = '';
		if (isset($_COOKIE['thirdcol'])) {
			$a = $lang['PREFS']['COLUMNS'][$thirdcol];
			if ($_COOKIE['thirdcol'] == 'released')
				$n1 = 'selected';
			else if ($_COOKIE['thirdcol'] == 'productionyear')
				$n2 = 'selected';
			else if ($_COOKIE['thirdcol'] == 'purchasedate')
				$n3 = 'selected';
			else if ($_COOKIE['thirdcol'] == 'collectionnumber')
				$n4 = 'selected';
			else if ($_COOKIE['thirdcol'] == 'runningtime')
				$n5 = 'selected';
			else if ($_COOKIE['thirdcol'] == 'rating')
				$n6 = 'selected';
			else if ($_COOKIE['thirdcol'] == 'genres')
				$n7 = 'selected';
			else if ($_COOKIE['thirdcol'] == 'reviews')
				$n10 = 'selected';
			else if ($_COOKIE['thirdcol'] == 'director')
				$n11 = 'selected';
			else if ($_COOKIE['thirdcol'] == 'none')
				$n9 = 'selected';
		}
		else {
			$a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['COLUMNS'][$sitethirdcol]."]";
			$n8 = 'selected';
		}
		echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['COLUMNS']['THIRDNAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="thirdcol">
<option value="released" $n1>{$lang['PREFS']['COLUMNS']['released']}</option>
<option value="productionyear" $n2>{$lang['PREFS']['COLUMNS']['productionyear']}</option>
<option value="purchasedate" $n3>{$lang['PREFS']['COLUMNS']['purchasedate']}</option>
<option value="collectionnumber" $n4>{$lang['PREFS']['COLUMNS']['collectionnumber']}</option>
<option value="runningtime" $n5>{$lang['PREFS']['COLUMNS']['runningtime']}</option>
<option value="rating" $n6>{$lang['PREFS']['COLUMNS']['rating']}</option>
<option value="genres" $n7>{$lang['PREFS']['COLUMNS']['genres']}</option>
<option value="reviews" $n10>{$lang['PREFS']['COLUMNS']['reviews']}</option>
<option value="director" $n11>{$lang['PREFS']['COLUMNS']['director']}</option>
<option value="sitedefault" $n8>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['COLUMNS'][$sitethirdcol]}]</option>
<option value="none" $n9>{$lang['PREFS']['COLUMNS']['none']}</option>
</select></td></tr>
EOT;
	}

	if ($allowdefaultsorttype) {
		$n1 = $n2 = $n3 = $n4 = $n5 = '';
		$z = $$sitedefaultsorttype;
		$displaysitedefaultsorttype = $lang['PREFS']['DEFAULTSORTTYPE'][$sitedefaultsorttype].$lang['PREFS']['COLUMNS'][$z];
		if (isset($_COOKIE['defaultsorttype'])) {
			$z = $$defaultsorttype;
			$a = $lang['PREFS']['DEFAULTSORTTYPE'][$defaultsorttype].$lang['PREFS']['COLUMNS'][$z];
			if ($_COOKIE['defaultsorttype'] == 'firstcol')
				$n1 = 'selected';
			else if ($_COOKIE['defaultsorttype'] == 'secondcol')
				$n2 = 'selected';
			else if ($_COOKIE['defaultsorttype'] == 'thirdcol')
				$n3 = 'selected';
		}
		else {
			$a = $lang['PREFS']['SITEDEFAULT']."<br>[$displaysitedefaultsorttype]";
			$n4 = 'selected';
		}
		echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['DEFAULTSORTTYPE']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="defaultsorttype">
<option value="firstcol" $n1>{$lang['PREFS']['DEFAULTSORTTYPE']['firstcol']}{$lang['PREFS']['COLUMNS'][$firstcol]}</option>
<option value="secondcol" $n2>{$lang['PREFS']['DEFAULTSORTTYPE']['secondcol']}{$lang['PREFS']['COLUMNS'][$secondcol]}</option>
<option value="thirdcol" $n3>{$lang['PREFS']['DEFAULTSORTTYPE']['thirdcol']}{$lang['PREFS']['COLUMNS'][$thirdcol]}</option>
<option value="sitedefault" $n4>{$lang['PREFS']['SITEDEFAULT']} [$displaysitedefaultsorttype]</option>
</select></td></tr>
EOT;
	}

	if ($allowtitledesc) {
		$n1 = $n2 = $n3 = $n4 = $n5 = '';
		if (isset($_COOKIE['titledesc'])) {
			$a = $lang['PREFS']['TITLEDESC'][$titledesc];
			if ($_COOKIE['titledesc'] == '0')
				$n1 = 'selected';
			else if ($_COOKIE['titledesc'] == '1')
				$n2 = 'selected';
			else if ($_COOKIE['titledesc'] == '2')
				$n3 = 'selected';
			else if ($_COOKIE['titledesc'] == '3')
				$n4 = 'selected';
		}
		else {
			$a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['TITLEDESC'][$sitetitledesc]."]";
			$n5 = 'selected';
		}
		echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['TITLEDESC']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="titledesc">
<option value="0" $n1>{$lang['PREFS']['TITLEDESC'][0]}</option>
<option value="1" $n2>{$lang['PREFS']['TITLEDESC'][1]}</option>
<option value="2" $n3>{$lang['PREFS']['TITLEDESC'][2]}</option>
<option value="3" $n4>{$lang['PREFS']['TITLEDESC'][3]}</option>
<option value="sitedefault" $n5>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['TITLEDESC'][$sitetitledesc]}]</option>
</select></td></tr>
EOT;
	}

	if ($allowlocale) {
		$n1 = $n2 = $n3 = $n4 = $n5 = $n6 = $n7 = $n8 = $n9 = $n10 = '';
		if (isset($_COOKIE['locale'])) {
			$a = $lang['PREFS']['LOCALE'][$locale];
			if ($_COOKIE['locale'] == 'en')
				$n1 = 'selected';
			else if ($_COOKIE['locale'] == 'de')
				$n2 = 'selected';
			else if ($_COOKIE['locale'] == 'no')
				$n3 = 'selected';
			else if ($_COOKIE['locale'] == 'fr')
				$n4 = 'selected';
			else if ($_COOKIE['locale'] == 'nl')
				$n5 = 'selected';
			else if ($_COOKIE['locale'] == 'sv')
				$n6 = 'selected';
			else if ($_COOKIE['locale'] == 'dk')
				$n7 = 'selected';
			else if ($_COOKIE['locale'] == 'fi')
				$n8 = 'selected';
			else if ($_COOKIE['locale'] == 'ru')
				$n9 = 'selected';
		}
		else {
			$a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['LOCALE'][$sitelocale]."]";
			$n10 = 'selected';
		}
		echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['LOCALE']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="locale">
<option value="en" $n1>{$lang['PREFS']['LOCALE']['en']}</option>
<option value="de" $n2>{$lang['PREFS']['LOCALE']['de']}</option>
<option value="no" $n3>{$lang['PREFS']['LOCALE']['no']}</option>
<option value="fr" $n4>{$lang['PREFS']['LOCALE']['fr']}</option>
<option value="nl" $n5>{$lang['PREFS']['LOCALE']['nl']}</option>
<option value="sv" $n6>{$lang['PREFS']['LOCALE']['sv']}</option>
<option value="dk" $n7>{$lang['PREFS']['LOCALE']['dk']}</option>
<option value="fi" $n8>{$lang['PREFS']['LOCALE']['fi']}</option>
<option value="ru" $n9>{$lang['PREFS']['LOCALE']['ru']}</option>
<option value="sitedefault" $n10>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['LOCALE'][$sitelocale]}]</option>
</select></td></tr>
EOT;
	}

	if ($allowstickyboxsets) {
		$n1 = $n2 = $n3 = $n4 = $n5 = '';
		if (isset($_COOKIE['stickyboxsets'])) {
			$a = $lang['PREFS']['STICKYBOXSETS'][$stickyboxsets];
			if ($_COOKIE['stickyboxsets'] == '1')
				$n1 = 'selected';
			else if ($_COOKIE['stickyboxsets'] == '0')
				$n2 = 'selected';
		}
		else {
			$a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['STICKYBOXSETS'][$sitestickyboxsets]."]";
			$n3 = 'selected';
		}
		echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['STICKYBOXSETS']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="stickyboxsets">
<option value="1" $n1>{$lang['PREFS']['STICKYBOXSETS'][1]}</option>
<option value="0" $n2>{$lang['PREFS']['STICKYBOXSETS'][0]}</option>
<option value="sitedefault" $n3>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['STICKYBOXSETS'][$sitestickyboxsets]}]</option>
</select></td></tr>
EOT;
	}

	if ($allowpopupimages) {
		$n1 = $n2 = $n3 = $n4 = $n5 = '';
		if (isset($_COOKIE['popupimages'])) {
			$a = $lang['PREFS']['POPUPIMAGES'][$popupimages];
			if ($_COOKIE['popupimages'] == '1')
				$n1 = 'selected';
			else if ($_COOKIE['popupimages'] == '0')
				$n2 = 'selected';
		}
		else {
			$a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['POPUPIMAGES'][$sitepopupimages]."]";
			$n3 = 'selected';
		}
		echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['POPUPIMAGES']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="popupimages">
<option value="1" $n1>{$lang['PREFS']['POPUPIMAGES'][1]}</option>
<option value="0" $n2>{$lang['PREFS']['POPUPIMAGES'][0]}</option>
<option value="sitedefault" $n3>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['POPUPIMAGES'][$sitepopupimages]}]</option>
</select></td></tr>
EOT;
	}

	if ($allowskins) {
		$n1 = $n2 = '';
		if ($skinfile == 'internal')
			$n1 = 'selected';
		$ss = $siteskinfile;
		if ($siteskinfile == 'internal')
			$ss = $lang['PREFS']['SKINS']['INTERNAL'];
		$ss = preg_replace('/\.htm[l]$/i', '', $ss);
		if (isset($_COOKIE['skinfile'])) {
			if ($skinfile == 'internal')
				$a = $lang['PREFS']['SKINS']['INTERNAL'];
			else
				$a = preg_replace('/\.htm[l]$/i', '', $skinfile);
		}
		else {
			$a = $lang['PREFS']['SITEDEFAULT']."<br>[$ss]";
			$n2 = 'selected';
		}
		echo <<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['SKINS']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="skins">
<option value="internal" $n1>{$lang['PREFS']['SKINS']['INTERNAL']}</option>

EOT;
		foreach ($TheSkins as $k => $SkinValue) {
			$t = '';
			if ($SkinValue['filename'] == $skinfile)
				$t = 'selected';
			echo '<option value="'.rawurlencode("$SkinValue[dirname]/$SkinValue[filename]")."\" $t>$SkinValue[displayname]</option>\n";
		}
		echo <<<EOT
<option value="sitedefault" $n2>{$lang['PREFS']['SITEDEFAULT']} [$ss]</option>
</select></td></tr>
EOT;
		unset($TheSkins);
	}

	if ($allowwidths) {
		echo<<<EOT
<tr class=a><td colspan=3 align=center>
<br><input type="checkbox" name="widths">{$lang['PREFS']['WIDTHS']['NAME']}<br></td></tr>
EOT;
	}

	unset($mapping);
	echo '</table><br><center><input type="submit" value="',$lang['PREFS']['UPDATEPREFS'],'"></center></form>';
	echo '<center><a class="URLref" href="index.php" target="_top">', $lang['IMPORTCLICK'], "</a></center>\n";
	echo "$endbody</html>\n";
?>
