<?php

error_reporting(E_ALL);
define('IN_SCRIPT', 1);
include_once('global.php');

	$result = $db->sql_query("SELECT * FROM $DVD_TABLE WHERE id='".$db->sql_escape($mediaid)."' LIMIT 1") or die($db->sql_error());

	$dvd = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	if ($dvd) {
		$dvd['p_released'] = ($dvd['released'] === NULL? '': fix88595(ucwords(strftime($lang['DATEFORMAT'], $dvd['released']))));
		if ($dvd['builtinmediatype'] == MEDIA_TYPE_HDDVD || $dvd['builtinmediatype'] == MEDIA_TYPE_HDDVD_DVD ) $dvd['upc'] .= ' HDDVD';
		if ($dvd['builtinmediatype'] == MEDIA_TYPE_BLURAY || $dvd['builtinmediatype'] == MEDIA_TYPE_BLURAY_DVD ) $dvd['upc'] .= ' Blu-ray';
		if ($dvd['builtinmediatype'] == MEDIA_TYPE_ULTRAHD || $dvd['builtinmediatype'] == MEDIA_TYPE_ULTRAHD_BLURAY || $dvd['builtinmediatype'] == MEDIA_TYPE_ULTRAHD_BLURAY_DVD ) $dvd['upc'] .= ' ULTRAHD';
		if (substr($dvd['upc'], 0, strlen('Disc ID: ')) == 'Disc ID: ')
			$dvd['upc'] = 'I' . substr($dvd['upc'], strlen('Disc ID: '));

		$dvd['movieformat'] = '';
		if ($dvd['format16x9']==1) $dvd['movieformat'] .= $lang['16X9'] . ' ';
		if ($dvd['formataspectratio'] != '') $dvd['movieformat'] .= "$dvd[formataspectratio]:1";

		$dvd['discformat'] = (($dvd['formatdualsided']==1)  ? $lang['DS']: $lang['SS'])
			. (($dvd['formatduallayered']==1)      ? "/$lang[DL]": "/$lang[SL]");
		$dvd['discformat'] = preg_replace('/^, /', '', $dvd['discformat']);

		$dvd['p_overview'] = $dvd['overview'];
		if (!$AllowHTMLInOverview)
			$dvd['p_overview'] = htmlspecialchars($dvd['p_overview'], ENT_COMPAT, 'ISO-8859-1');
		$dvd['p_overview'] = nl2br(fix1252($dvd['p_overview']));
		$dvd['p_casetype'] = $lang[strtoupper(str_replace(' ', '', $dvd['casetype']))];
		$runtime = "$dvd[runningtime] $lang[MINUTES]";

		$dvd['format'] = '';
		if ($dvd['builtinmediatype'] == MEDIA_TYPE_DVD || $dvd['builtinmediatype'] == MEDIA_TYPE_HDDVD_DVD || $dvd['builtinmediatype'] == MEDIA_TYPE_BLURAY_DVD)
			$dvd['format'] = $dvd['formatvideostandard'].',';
		$dvd['format'] .=
			  (($dvd['format16x9']==1)             ? " {$lang['16X9']}":       '')
			. (($dvd['formatletterbox']==1)        ? " $lang[WIDESCREEN]": '');
		if ($dvd['formataspectratio'] != '')
			$dvd['format'] .= " $dvd[formataspectratio]:1";
		$dvd['format'] .=
			  (($dvd['formatpanandscan']==1)       ? ", $lang[PANANDSCAN]": '')
			. (($dvd['formatfullframe']==1)        ? ", $lang[FULLFRAME]": '');
		$dvd['format'] = preg_replace('/^,\s+/', '', $dvd['format']);

		$locale = substr(strstr($dvd['id'], '.'), 1, 2);
		if (!$locale)
			$locale = '0';

		$regions = '';
		if (strstr($dvd['region'], '0') !== false) { 
			$regions .= $lang['ALLREGIONS'];
		}
		else if (strstr($dvd['region'], '@') !== false) {
			$regions .= $lang['ALLREGIONS'];
		}
		else {
			for ($i=0; $i<strlen($dvd['region']); $i++) {
				if ($regions == '') $regions = "$lang[REGION] ";
 				$regions .= substr($dvd['region'], $i, 1) . ',';
			}
			if ($regions != '') $regions = substr($regions, 0, -1);
		}

		$dvd['title']         = fix1252(htmlspecialchars($dvd['title'], ENT_COMPAT, 'ISO-8859-1'));

		header('Content-Type: text/html; charset="windows-1252";');
		echo <<<EOT
<html>
	<head>
		<title>$dvd[title]</title>
    		<meta name="viewport" content="width=device-width,user-scalable=yes" />
		<link rel="stylesheet" type="text/css" href="smallshow.css">
	</head>
	<body>
		<div class="title">$dvd[title]</div>
		<div class="name" style="top:39px">UPC:</div> <div class="data" style="top:39px">$dvd[upc]</div>
		<div class="name" style="top:62px">$lang[REGION]:</div> <div class="data" style="top:62px">$regions</div>
		<div class="name" style="top:85px">$lang[YEAR]</div> <div class="data" style="top:85px">$dvd[productionyear]</div>
		<div class="name" style="top:108px">$lang[RATING]</div> <div class="data" style="top:108px">$dvd[rating]</div>
		<div class="name" style="top:131px">$lang[RUNTIME]</div> <div class="data" style="top:131px">$runtime</div>
		<div class="name" style="top:154px">$lang[RELEASEDATE]</div> <div class="data" style="top:154px">$dvd[p_released]</div>
		<div class="name" style="top:177px">$lang[CASETYPE]</div> <div class="data" style="top:177px">$dvd[p_casetype]</div>
		<div class="name" style="top:200px">$lang[FORMAT]</div> <div class="data" style="top:200px">$dvd[format]</div>
		<div class="name" style="top:223px">Movie Fmts</div> <div class="data" style="top:223px">$dvd[movieformat]</div>
		<div class="name" style="top:246px">Disc Fmts</div> <div class="data" style="top:246px">$dvd[discformat]</div>
		<div class="name" style="top:269px">$lang[SRP]</div> <div class="data" style="top:269px">$dvd[srp]</div>
		<div class="upperpic"><img src="MakeAnImage.php?x=93&y=130&mediaid=$dvd[id]"></div>
		<div class="lowerpic"><img src="MakeAnImage.php?side=b&x=93&y=130&mediaid=$dvd[id]"></div>
		<div class="overview">$dvd[p_overview]</div>
	</body>
</html>

EOT;
	}
	exit;
?>
