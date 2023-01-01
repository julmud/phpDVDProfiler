<?php

define('IN_SCRIPT', 1);
include('global.php');
error_reporting(E_ALL);

// Force the global.php AcquireAThumbnail routine to try to download
function GetAThumbnail($file) {
	$temp = $getimages;
	$getimages = 2;
	AcquireAThumbnail($file);
	$getimages = $temp;
}

$gi = '0.1.1';


$sql = "SELECT id, title FROM $DVD_TABLE WHERE 1 ORDER BY sorttitle ASC";

$result = $db->sql_query($sql) or die($db->sql_error());

echo "<html>\n
<head>\n
<title>GET THE IMAGES!</title>\n
<link rel=\"stylesheet\" type=\"text/css\" href=\"./format.css\">\n
</head>\n
<body class=\"f6\">\n
<!-- Version: $gi -->\n
<center>\n
<table width=\"80%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n
  <tr class=\"f1\">\n
    <td rowspan=\"2\" align=\"center\" valign=\"middle\">Profile Name </td>\n
    <td colspan=\"2\" align=\"center\" valign=\"middle\"><div align=\"center\">Hires</div></td>\n
    <td colspan=\"2\" align=\"center\" valign=\"middle\"><div align=\"center\">Lowres</div></td>\n
    <td colspan=\"2\" align=\"center\" valign=\"middle\"><div align=\"center\">Got new Lowres from IVS </div></td>\n
  </tr>\n
  <tr>\n
    <td width=\"50\" align=\"center\" valign=\"middle\">front</td>\n
    <td width=\"50\" align=\"center\" valign=\"middle\">back</td>\n
    <td width=\"50\" align=\"center\" valign=\"middle\">front</td>\n
    <td width=\"50\" align=\"center\" valign=\"middle\">back</td>\n
    <td width=\"75\" align=\"center\" valign=\"middle\">front</td>\n
    <td width=\"75\" align=\"center\" valign=\"middle\">back</td>\n
  </tr>\n";

while($dvd = mysql_fetch_array($result)) {

	$getfront = false;
	$getback = false;

	echo "<tr>\n
    <td>$dvd[title]</td>\n";

	$front = $dvd['id'].'f.jpg';
	if (!file_exists($img_physpath.$thumbnails.'/'.$front)) {
		if (($tmp=findfilecase($img_physpath.$thumbnails, $front)) != '')
			$front = $tmp;
	}

	$back  = $dvd['id'].'b.jpg';
	if (!file_exists($img_physpath.$thumbnails.'/'.$back)) {
		if (($tmp=findfilecase($img_physpath.$thumbnails, $back)) != '')
			$back = $tmp;
	}

	if (file_exists($img_physpath.$front)) {
		echo "    <td align=\"center\"><a target=\"_blank\" href=\"{$img_webpath}$front\"><img border=0 src=\"gfx/yes.png\"></a></td>\n";
	}
	else {
		echo "    <td align=\"center\"><img border=0 src=\"gfx/no.png\"></td>\n";
	}
	if (file_exists($img_physpath.$back)) {
		echo "    <td align=\"center\"><a target=\"_blank\" href=\"{$img_webpath}$back\"><img border=0 src=\"gfx/yes.png\"></a></td>\n";
	}
	else {
		echo "    <td align=\"center\"><img border=0 src=\"gfx/no.png\"></td>\n";
	}
	if (file_exists($img_physpath.$thumbnails.'/'.$front)) {
		echo "    <td align=\"center\"><a target=\"_blank\" href=\"{$img_webpath}$thumbnails/$front\"><img border=0 src=\"gfx/yes.png\"></a></td>\n";
	}
	else {
		echo "    <td align=\"center\"><img border=0 src=\"gfx/no.png\"></td>\n";

		$getfront = true;

		GetAThumbnail($front);
	}
	if (file_exists($img_physpath.$thumbnails.'/'.$back)) {
		echo "    <td align=\"center\"><a target=\"_blank\" href=\"{$img_webpath}$thumbnails/$back\"><img border=0 src=\"gfx/yes.png\"></a></td>\n";
	}
	else {
		echo "    <td align=\"center\"><img border=0 src=\"gfx/no.png\"></td>\n";
		$getback = true;

		GetAThumbnail($back);
	}

	if ($getfront == true && file_exists($img_physpath.$thumbnails.'/'.$front)) {
		echo "    <td align=\"center\"><a target=\"_blank\" href=\"{$img_webpath}$thumbnails/$front\"><img border=0 src=\"gfx/yes.png\"></a></td>\n";
	}
	elseif ($getfront == true && !file_exists($img_physpath.$thumbnails.'/'.$front)){
		echo "    <td align=\"center\"><img border=0 src=\"gfx/no.png\"></td>\n";
	}
	else { echo "<td>&nbsp;</td>\n";}

	if ($getback == true && file_exists($img_physpath.$thumbnails.'/'.$back)) {
		echo "    <td align=\"center\"><a target=\"_blank\" href=\"{$img_webpath}$thumbnails/$back\"><img border=0 src=\"gfx/yes.png\"></a></td>\n";
	}
	elseif ($getback == true && !file_exists($img_physpath.$thumbnails.'/'.$back)){
		echo "    <td align=\"center\"><img border=0 src=\"gfx/no.png\"></td>\n";
	}
	else { echo "<td>&nbsp;</td>\n";}

	echo " </tr>\n";
}
echo "</table>\n";
