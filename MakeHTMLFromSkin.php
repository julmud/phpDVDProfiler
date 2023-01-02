<?php

// usage: php MakeHTMLFromSkin.php lang skin.html >newskin.html
//
// eg: php MakeHTMLFromSkin.php sv 'phpDVDProfiler Skin.html' >skin.html
// to create the skin with Swedish strings
//
// This code puts the language strings into a skin by replacing the $lang[] elements
// for the purpose of creating an HTML file for import into DVDProfiler

include_once('lang_'.$_SERVER['argv'][1].'.php');

function Replace2Lang($matches) {
global $lang;
	$matches[1] = str_replace("'", '', $matches[1]);
	$matches[1] = str_replace('"', '', $matches[1]);
	$matches[2] = str_replace("'", '', $matches[2]);
	$matches[2] = str_replace('"', '', $matches[2]);
	return($lang[$matches[1]][$matches[2]]);
}
function ReplaceLang($matches) {
global $lang;
	$matches[1] = str_replace("'", '', $matches[1]);
	$matches[1] = str_replace('"', '', $matches[1]);
	return($lang[$matches[1]]);
}

	$j = file_get_contents($_SERVER['argv'][2]);

	$j = preg_replace_callback('/\\$lang\\[(.*)\\]\\[(.*)\\]/U', "Replace2Lang", $j);
	$j = preg_replace_callback('/\\$lang\\[(.*)\\]/U', "ReplaceLang", $j);

	echo $j;
