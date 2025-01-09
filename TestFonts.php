<?php

$fontnames['Default Font Test #1'] = 'arial.ttf';

if (!defined('IN_SCRIPT')) define('IN_SCRIPT', 1);

include_once('global.php');

error_reporting(-1);	// Give me everything
$IAmReady = false;
// Here are some values for various fonts I found
$arial = array(0 => -1, 1 => 3, 2 => 123, 3 => 3, 4 => 123, 5 => -11, 6 => -1, 7 => -11);
$cour  = array(0 => 0,  1 => 2, 2 => 174, 3 => 2, 4 => 174, 5 => -9,  6 => 0,  7 => -9);
$comic = array(0 => 0,  1 => 4, 2 => 128, 3 => 4, 4 => 128, 5 => -12, 6 => 0,  7 => -12);
$times = array(0 => -1, 1 => 3, 2 => 111, 3 => 3, 4 => 111, 5 => -10, 6 => -1, 7 => -10);

function DisplayError($heading) {
global $eoln, $g_errno, $g_errstr, $g_errfile, $g_errline;

	if ($eoln !== "\n") {
		$bold = '<b>'; $unbold = '</b>';
		$space = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	}
	else {
		$bold = $unbold = '****';
		$space = "\t";
	}
	if ($heading != '')
		echo "$bold$heading$unbold$eoln";
	echo "$space Error Number: [$g_errno]$eoln";
	echo "$space Error String: $g_errstr$eoln";
	if ($heading != '')
		echo "$space Error Location: Line $g_errline of $g_errfile$eoln";
}

function myErrorHandler($errno, $errstr, $errfile, $errline) {
global $IAmReady, $g_errno, $g_errstr, $g_errfile, $g_errline, $ItWorked;

	$ItWorked = false;
	$g_errno = $errno;
	$g_errstr = $errstr;
	$g_errfile = $errfile;
	$g_errline = $errline;
	if (!$IAmReady) {
		DisplayError("Error Handler: Unexpected error");
	}

	/* Don't execute PHP internal error handler */
	return(true);
}

function DisplayResult($result) {
global $eoln;
	if ($eoln !== "\n") echo "<pre>";
	$one = str_pad("($result[6],$result[7])", 16);
	echo "$one\t\t($result[4],$result[5])\n";
	$one = str_pad("($result[0],$result[1])", 16);
	echo "$one\t\t($result[2],$result[3])\n";
	if ($eoln !== "\n") echo "</pre>";
//print_r($result);
}

function DisplayFilesInDir($dir, $extfil='', $typefil='') {
global $eoln;

	if (!is_dir($dir)) {
		echo "$dir is not a valid path to a directory.$eoln";
		return;
	}
	if (($dh=opendir($dir)) === false) {
		echo "Unable to open directory $dir$eoln";
		return;
	}
	$count = 0;
	if ($eoln !== "\n") echo "<pre>";
	while (($file=readdir($dh)) !== false) {
		if ($file == '.' || $file == '..')
			continue;		// ignore sub-directory linkages
		//echo "filename: $file : filetype: " . filetype($dir . $file) . "\n";
		if ($extfil != '' && pathinfo($file, PATHINFO_EXTENSION) != $extfil)
			continue;
		$ActualFile = $dir.$file;
		if (filetype($dir.$file) == 'link')
			$ActualFile = readlink($dir.$file);
		if ($typefil != '' && filetype($ActualFile) == $typefil) {
			$count++;
			if ($ActualFile != $dir.$file)
				echo "\t$dir$file => $ActualFile ($typefil)\n";
			else
				echo "\t$dir$file ($typefil)\n";
		}
		if ($typefil == '') {
			$count++;
			echo "\t$dir$file\n";
		}
	}
	closedir($dh);
	if ($eoln !== "\n") echo "</pre>";
	return($count);
}

function TestAFont($fontname, $MyResult='') {
global $eoln, $IAmReady, $g_errno, $g_errstr, $g_errfile, $g_errline, $ItWorked;
global $$MyResult;

	$ItWorked = true;
	$IAmReady = true;
	$result = ImageTTFBBox(10, 0, $fontname, 'This is just some text');
	$IAmReady = false;
	if ($ItWorked) {
		echo "The ImageTTFBBox() call with font \"$fontname\" succeeded.$eoln";
		echo "Font \"$fontname\" gives a bounding box of:$eoln";
		DisplayResult($result);
		if ($result[6] != $result[0] || $result[4] != $result[2] || $result[7] != $result[5] || $result[1] != $result[3]) {
			echo "****The result isn't a rectangle!?!?!$eoln";
		}
		echo "which means a height of ", $result[3] - $result[5], " and a width of ", $result[4] - $result[6], $eoln;
		if (isset($$MyResult)) {
			if ($result != $$MyResult) {
				echo "This is different than my result of:$eoln";
				DisplayResult($$MyResult);
			}
		}
		else {
			echo "I have no external data on font \"$fontname\"$eoln";
		}
		return(true);
	}
	else {
		echo "The ImageTTFBBox() call with font \"$fontname\" FAILED.$eoln";
		echo "The Error Handler reported:$eoln";
		DisplayError('');
		return(false);
	}
}

function TestConfiguration(&$fontnames) {
global $eoln;

	$FontsTested = array();
	foreach($fontnames as $key => $fontname) {
		echo "$eoln$key: Testing font \"$fontname\":";
		if (in_array($fontname, $FontsTested)) {
			echo " Already tested$eoln";
		}
		else {
			echo "$eoln";
			$success = TestAFont($fontname, basename($fontname, '.ttf'));
			if ($success)
				$FontsTested[] = $fontname;
			if ($fontname != basename($fontname)) {
				echo "Testing font \"$fontname\" without the path...$eoln";
				$success = TestAFont(basename($fontname), basename($fontname, '.ttf'));
				if ($success)
					$FontsTested[] = basename($fontname);
			}
		}
	}
	return($FontsTested);
}

	set_error_handler('myErrorHandler');
	echo "This code is running on PHP " . PHP_VERSION . " (" . PHP_OS . ")$eoln";
	echo "in directory ", getcwd(), " [", realpath(getcwd()),"]$eoln";
	if (!function_exists('ImageTTFBBox')) {
		echo "The function ImageTTFBBox() does not exist.$eoln";
		echo "None of the image code will work.$eoln";
		echo "Likely this instance of php was not compiled with the GD option.$eoln";
		echo "Aborting.$eoln";
		exit;
	}

	$GDFPOGood = true;
	$GDFONTPATH = @getenv('GDFONTPATH');
	if ($GDFONTPATH !== false)  {
		echo "Environment variable GDFONTPATH is \"$GDFONTPATH\"$eoln";
		echo "TrueType fonts available in that directory are:$eoln";
		$num = DisplayFilesInDir($GDFONTPATH, 'ttf');
		echo "for a total of $num fonts$eoln";
	}
	else {
		echo "Environment variable GDFONTPATH is not set.$eoln";
		$GDFONTPATH = '';
	}
	if (isset($GDFontPathOverride)) {
		echo "phpdvdprofiler variable \$GDFontPathOverride is set to \"$GDFontPathOverride\"$eoln";
		echo "TrueType fonts available in that directory are:$eoln";
		$num = DisplayFilesInDir($GDFontPathOverride, 'ttf');
		echo "for a total of $num fonts$eoln";
		if ($num == 0) {
			echo "It look like this is a bad setting for \$GDFontPathOverride; it should likely be$eoln";
			echo "removed, or possibly changed. More information later...$eoln";
			$GDFPOGood = false;
		}
	}

	$TryJpGraph = false;
	if ($usejpgraph) {
		echo "It says here that jpgraph is to be used";
		if (!isset($jpgraphlocation)) {
			echo " but \$jpgraphlocation isn't set$eoln";
		}
		else {
			echo " and it's located at \"$jpgraphlocation\"$eoln";
			if (!is_dir($jpgraphlocation)) {
				echo "Sadly, that is not a directory, so no help there (and likely your graphs are broken, also)$eoln";
			}
			else {
				echo "The directories in that location are:$eoln";
				DisplayFilesInDir($jpgraphlocation, '', 'dir');
				if (!is_dir($jpgraphlocation.'fonts')) {
					echo "Sadly, there is no {$jpgraphlocation}fonts directory, so likely no fonts.$eoln";
				}
				else {
					echo "The directories in {$jpgraphlocation}fonts are:$eoln";
					DisplayFilesInDir($jpgraphlocation.'fonts/', '', 'dir');
					if (!is_dir($jpgraphlocation.'fonts/truetype')) {
						echo "Sadly, there is no {$jpgraphlocation}fonts/truetype directory, so likely no fonts.$eoln";
					}
					else {
						echo "TrueType fonts available in {$jpgraphlocation}fonts/truetype are:$eoln";
						$num = DisplayFilesInDir($jpgraphlocation.'fonts/truetype/', 'ttf');
						echo "for a total of $num fonts$eoln";
						$TryJpGraph = true;
					}
				}
			}
		}
	}

	if (isset($profiles)) {
		foreach ($profiles as $key => $profile) {
			if (isset($profile['font']))
				$fontnames[$key] = $profile['font'];
			else
				$fontnames[$key] = 'arial.ttf';
		}
	}

	echo "{$eoln}/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\/\\$eoln";
	echo "Testing ImageTTFBBox() calls with the current configuration$eoln";
	$FontsSucceeded = TestConfiguration($fontnames);
	$numsystem = count($FontsSucceeded);
	if ($TryJpGraph) {
		echo "{$eoln}/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\/\\$eoln";
		echo "Testing ImageTTFBBox() calls using {$jpgraphlocation}fonts/truetype in the fontpath$eoln";
        	$gdfp = @putenv("GDFONTPATH={$jpgraphlocation}fonts/truetype");
		if ($gdfp === false)
			echo "The putenv() call to set the path indicates that it FAILED$eoln";
		$FontsSucceeded = TestConfiguration($fontnames);
		$numjpgraph = count($FontsSucceeded);
		if ($numsystem != $numjpgraph)
			echo "Current configuration: $numsystem fonts available{$eoln}JpGraph configuration: $numjpgraph fonts available$eoln";
		if ($numjpgraph > $numsystem)
			echo "{$eoln}Consider setting \$GDFontPathOverride = '{$jpgraphlocation}fonts/truetype'; in your $localsiteconfig$eoln";
	}
	if (isset($GDFontPathOverride) && $GDFPOGood) {
		echo "{$eoln}/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\\/\/\\$eoln";
		echo "Testing ImageTTFBBox() calls using $GDFontPathOverride in the fontpath$eoln";
        	$gdfp = @putenv("GDFONTPATH=$GDFontPathOverride");
		if ($gdfp === false)
			echo "The putenv() call to set the path indicates that it FAILED$eoln";
		$FontsSucceeded = TestConfiguration($fontnames);
		$numgdfpo = count($FontsSucceeded);
		if ($numsystem != $gdfpo)
			echo "Current configuration: $numsystem fonts available{$eoln}\$GDFontPathOverride configuration: $gdfpo fonts available$eoln";
		if ($numjpgraph > $numsystem)
			echo "{$eoln}Consider setting \$GDFontPathOverride = '{$jpgraphlocation}fonts/truetype'; in your $localsiteconfig$eoln";
	}
	exit;
