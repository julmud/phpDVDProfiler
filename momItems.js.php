<?php

define('IN_SCRIPT', 1);

include_once('version.php');
include_once('functions.php');
include_once('globalinits.php');
if (is_readable('multisite.php'))
	include('multisite.php');

include('siteconfig.php');
if (is_readable($localsiteconfig))
	include($localsiteconfig);

header('Content-Type: text/javascript; charset="windows-1252";');
?>
/* Vision Slidemenü 1.0                        */
/* Copyright (C) 2002 Matthias Mohr            */
/* E-mail: webmaster@mamo-net.de               */
/* Homepage: http://www.mamo-net.de            */
/* ------------------------------------------- */
/* Sie dürfen dieses Script frei benutzen wenn */
/* dieser Corpright Hinweis bestehen bleibt.   */

<!--
YOffset=50;
XOffset=0;
staticYOffset=30;
slideSpeed=25
waitTime=250;
<?php echo "menuBGColor=\"$ClassColor[27]\";\n"; ?>
menuIsStatic="yes";
menuWidth=190;	// this value should be a multiple of 10 to avoid drawing bugs
menuCols=2;
hdrFontFamily="verdana";
hdrFontSize="2";
<?php echo "hdrFontColor=\"$ClassColor[28]\";\n"; ?>
<?php echo "hdrBGColor=\"$ClassColor[29]\";\n"; ?>
hdrAlign="left";
hdrVAlign="center";
hdrHeight="15";
linkFontFamily="Verdana";
linkFontSize="2";
<?php echo "linkBGColor=\"$ClassColor[25]\";\n"; ?>
<?php echo "linkOverBGColor=\"$ClassColor[26]\";\n"; ?>
linkTarget="_self";
linkAlign="Left";
<?php echo "barBGColor=\"$ClassColor[30]\";\n"; ?>
barFontFamily="Verdana";
barFontSize="1";
<?php echo "barFontColor=\"$ClassColor[31]\";\n"; ?>
barVAlign="center";
barWidth=10;
barText="Navigation";	// no $lang vaiable here, and non us-ascii won't work anyway
//-->

/* Links in die PHP-Datei verlegt, da die dynamisch sind. */
