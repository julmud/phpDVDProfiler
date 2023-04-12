<?php

defined('IN_SCRIPT') || define('IN_SCRIPT', 1);

include_once('version.php');
include_once('functions.php');
include_once('globalinits.php');
if (is_readable('multisite.php'))
	include_once('multisite.php');

include_once('siteconfig.php');
if (is_readable($localsiteconfig))
	include_once($localsiteconfig);

@header('Content-Type: text/css; charset="windows-1252";');
echo <<<EOT
body {	background-color: $ClassColor[1]; }

a {	color: $ClassColor[11];
	text-decoration: none;
}
a:hover { color: $ClassColor[12]; }
a.n {	color: $ClassColor[13];
	text-decoration: none;
}
a.n:hover {
	color: $ClassColor[14];
	text-decoration: none;
}
a.n1 {	color: $ClassColor[37];
	text-decoration: none;
}
a.n1:hover {
	color: $ClassColor[38];
	text-decoration: none;
}

.pl10 {	padding-left: 10px; }

.audioimage {
	vertical-align: -30%;
	margin-bottom: 2px;
}
.URLref {
	color: $ClassColor[0];
	text-decoration: underline;
}
.GroupDividerPopup {
	background-image: url(gfx/LtGradGrp.png);
	background-repeat: repeat-x;
	font-family: Arial,Helvetica,sans-serif;
	font-size: 8pt;
	color: $ClassColor[24];
	vertical-align: top;
	border-style: solid;
	border-width: 0px;
	padding: 0px;
	padding-left: 3px;
}
.GroupDivider {
	background-image: url(gfx/LtGradGrp.png);
	background-repeat: repeat-x;
	color: $ClassColor[15];
	font-weight: bold;
	padding: 1px 100px 1px 8px;
	border-top: solid 1px $ClassColor[16];
	border-left: solid 1px $ClassColor[16];
	border-right: solid 1px $ClassColor[16];
}
.DividerPopup {
	background-image: url(gfx/LtGrad.png);
	background-repeat: repeat-x;
	font-family: Arial,Helvetica,sans-serif;
	font-size: 8pt;
	color: $ClassColor[24];
	vertical-align: top;
	border-style: solid;
	border-width: 0px;
	padding: 0px;
	padding-left: 3px;
}
.Divider {
	background-image: url(gfx/LtGrad.png);
	background-repeat: repeat-x;
	color: $ClassColor[15];
	font-weight: bold;
	padding: 1px 100px 1px 8px;
	border-top: solid 1px $ClassColor[16];
	border-left: solid 1px $ClassColor[16];
	border-right: solid 1px $ClassColor[16];
}
.t {	font-family: Arial,Helvetica,sans-serif;
	font-size: 10pt;
	font-weight: Bold;
	background-color: $ClassColor[5];
	color: $ClassColor[36];
	padding: 1px;
}

.l {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	background-color: $ClassColor[3];
	color: $ClassColor[18];
	vertical-align: top;
	padding: 1px;
	padding-left: 3px;
}

.o {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	background-color: $ClassColor[4];
	color: $ClassColor[18];
	vertical-align: top;
	padding: 1px;
	padding-left: 3px;
}

.a {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	font-weight: Bold;
	background-color: $ClassColor[20];
	color: $ClassColor[18];
	vertical-align: top;
	padding-left: 15px;
}

.u {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	font-weight: Bold;
	background-color: $ClassColor[6];
	color: $ClassColor[18];
	vertical-align: middle;
	padding-left: 15px;
	padding-right: 15px;
}

.v {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	font-weight: Bold;
	background-color: $ClassColor[7];
	color: $ClassColor[18];
	vertical-align: middle;
	padding-left: 15px;
	padding-right: 15px;
}

.x {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	font-weight: Bold;
	background-color: $ClassColor[10];
	color: $ClassColor[18];
	vertical-align: middle;
	padding-left: 15px;
}

.y {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	font-weight: Bold;
	background-color: $ClassColor[9];
	color: $ClassColor[18];
	vertical-align: middle;
	padding-left: 15px;
}

.z1 {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	font-weight: Bold;
	background-color: $ClassColor[8];
	color: $ClassColor[18];
	vertical-align: middle;
	padding-left: 15px;
}

.z2 {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	font-weight: Bold;
	background-color: $ClassColor[21];
	color: $ClassColor[22];
	vertical-align: middle;
	padding-left: 15px;
}

.f1 {	font-family: Arial,Helvetica,sans-serif;
	font-size: 18pt;
	background-color: $ClassColor[5];
	color: $ClassColor[17];
	text-align: center;
}

.f1sm {	font-family: Arial,Helvetica,sans-serif;
	font-size: 10pt;
	background-color: $ClassColor[5];
	color: $ClassColor[35];
	text-align: center;
}

.f2 {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	background-color: $ClassColor[3];
	color: $ClassColor[18];
	vertical-align: top;
	padding: 3px;
	padding-left: 5px;
}

.f2np {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	background-color: $ClassColor[3];
	color: $ClassColor[18];
}

.f3 {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	font-weight: Bold;
	background-color: $ClassColor[21];
	color: $ClassColor[23];
	vertical-align: top;
	padding: 3px;
	padding-left: 5px;
}

.f3np {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	font-weight: Bold;
	background-color: $ClassColor[21];
	color: $ClassColor[23];
}

.f4 {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	font-weight: Bold;
	background-color: $ClassColor[5];
	color: $ClassColor[35];
	padding: 3px;
	padding-left: 5px;
}

.f5 {	font-family: Arial,Helvetica,sans-serif;
	font-size: 7pt;
	background-color: $ClassColor[21];
	color: $ClassColor[24];
	vertical-align: top;
	border-style: solid;
	border-width: 0px;
	padding: 0px;
	padding-left: 3px;
}

.f5b {	font-family: Arial,Helvetica,sans-serif;
	font-size: 7pt;
	background-color: $ClassColor[21];
	color: $ClassColor[18];
	vertical-align: top;
	border-style: solid;
	border-width: 0px;
	padding: 0px;
	padding-left: 3px;
}

.f6 {	font-family: Arial,Helvetica,sans-serif;
	font-size: 10pt;
	font-weight: Bold;
	background-color: $ClassColor[20];
	color: $ClassColor[24];
	vertical-align: top;
	border-style: solid;
	border-width: 0px;
/*	width: 100%;*/
	padding: 0px;
	padding-left: 3px;
}

.hdlogo {
	background-color: $ClassColor[1];
	border-bottom-width: 0px;
}
.fthumb {
	background-color: $ClassColor[1];
	border-top-width: 0px;
}

.f7 {	font-family: Arial,Helvetica,sans-serif;
	font-size: 11pt;
	font-weight: Bold;
	background-color: $ClassColor[3];
	color: $ClassColor[18];
	vertical-align: middle;
	text-align: left;
	margin: 0px;
	border-style: solid;
	border-width: 0px;
	padding: 0px;
}

.f8 {	background-color: $ClassColor[20];
	width: 65px
}

.f9 {	background-color: $ClassColor[3];
	margin: 0px;
	border-style: solid;
	border-width: 0px;
	padding: 0px;
}

.s {	font-family: Arial,Helvetica,sans-serif;
	font-size: 7pt;
	color: $ClassColor[32];
}

.s1 {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	color: $ClassColor[33];
}

.bgl {	background-color: $ClassColor[20]; }

.bgd {	background-color: $ClassColor[1];
	height: 2px;
}

.nav {	font-family: Arial,Helvetica,sans-serif;
	font-size: 7pt;
	background-color: $ClassColor[5];
	color: $ClassColor[19];
	text-align: right;
	padding: 4px;
}

.nav2 {	font-family: Arial,Helvetica,sans-serif;
	font-size: 9pt;
	font-weight: Bold;
	background-color: $ClassColor[5];
	color: $ClassColor[2];
	text-align: center;
	padding: 2px;
}

.line {	background-color: $ClassColor[1];
	height: 1px;
}

.line2 {
	background-color: $ClassColor[1];
	height: 2px;
}

.input { font-family: Arial,Helvetica,sans-serif;
	font-size: 8pt;
	color: $ClassColor[34];
}

span { font-family: Verdana,Arial,Helvetica,sans-serif; }

img.b {	border-style: solid;
	border-color: $ClassColor[1];
	border-width: 2px;
}

EOT;
