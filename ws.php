<?php
$ws_version = "2.1.2f";
#
# Version 2.1.2, 8th September 2007,	Removed resize_jpg as Fred moved it to functions.php
# Version 2.1.1, 27th May 2007,		Added 2 new parameters for sig strips.
#					'start' which controls where to start from
#					So, instead of listing the first 10 for example
#					if start is set to 20, it will display from the 20th
#					which is useful it you want to position multiple things
#					on your web page. Also, 'type' which defaults to 'watched'
#					which is the normal sig strip. But if 'type' is set to 'new'
#					it will show the most recent additions to your owned collection
#					(by purchase date) as we don't know when you added it.
#					Put back SetShadow as that was removed in error. But
#					removed AdjBackgroundImage as that's what is not GD2 compatible.
#					Fixed the fact I was including jpgraph stuff even if $usejpgraph
#					was set to false.
# Version 2.1.0, 26th May 2007,		Fixed ws.php not using the format.css.php
#					Fixed resetting rows for sig strips
#					Fixed exiting correctly when doing static images
#					Fixed handling if nothing is watched
#					Removed SetShadow from fonts as not compatible with GD2.0
#					Added code to clean imagecachedir if its being used
# Version 2.0.9, 13th April 2007,	Added ability to have multiple rows in signature strips
#					Fixed scaling of images if they aren't normal sizes
# Version 2.0.8, 6th April 2007,	Some fixes Fred put in for image handling.
#					Added some code for calling ws.php action=update
#					when called from incupdate.php.
#					Added some code to better handle when the putenv fails.
#					Fixed review discs but calling FixAReviewValue
# Version 2.0.7, 25th February 2007,	Replaced $img_physpath with $img_webpath
# Version 2.0.6, 6th December 2006,	Added date formating to sig config.
# 					Fixed problem with 'Watched' & 'watched' instead of '$watched'
#					Added #MOTD_RAND# and #MOTD_X# for top/bot message to handle
#					message of the day.
#					Make sig strips get built at update time
#					Added WS_SELF to url names.
#					Added action=phpinfo to report phpinfo
#					Fixed the fact I wasn't making $user web safe (so it could
#					have spaces in.
#					Added $ws_borrowed_is_watched so people that borrow, count as watched
# Version 2.0.5, 9th September 2006,	Added option to expand a user automatically. And year and month.
#					Added 'info' option to report version.
# Version 2.0.4, 8th September 2006, 	Has extra code for configuring all the 'me' stuff.
# Version 2.0.3, 7th August 2006.       Yet again fixed problems with ' and ". Hopefully for
#                                       the last time.
# Version 2.0.2, 6th August 2006.	Fixed problem with ' in title in the detail list.
#					Changed graph scaling.
# Version 2.0.1, 11th July 2006.	Fixed multiple thumbnails bug in 'best' and 'worst'
#					like the 'last' thumbnails had.
# 					Fixed problem with ' getting changed to \\' in the T_TITLE.
# Version 2.0.0, 4rd July 2006.		Added the DISTINICT back to fix the problem.
#					Added sort to mouseover events.
#					Fixed last thumbnail list. Removed 'DISTINICT' and 'GROUP BY'.
# 					See all the stuff below.
# Version 1.9.9, 1st July 2006.		Fixed handling of missing image in my_watched().
#					Don't display months where nothing was watched.
#					Fixed colspan when changing $maxthumbs.
#					Fixed various link building issues.
#					Added some scaling to the graphs.
# Version 1.9.8, 30th June 2006.	Added reviewfilm to mouseover if it's present.
#					Fixed line2 spacing.
#					Fixed bug with resetting last/most/best/worst when selecting a
#					different person/year/month.
#					Fixed duplicate thumb on last if watched by multiple users.
#					Changed resize_jpg to check if image exists and return unknown.jpg
#					and also returns thumb of main image if requested size is larger than
#					the width of the thumbnail.
# Version 1.9.7, 29th June 2006.	Fixed tooltop on images displaying the 'alt' text in buggy browsers.
#					Added js navigation to select last/most/best/worst.
# 					Fixed rouge <table> tag
# Version 1.9.6, 28th June 2006.	Added best/worst list.
#					Added toggles to last/most/best/worst and setup defaults.
#					Moved $me handling to its own function so I can hack it later.
# Version 1.9.5, 27th June 2006.	Fixed image resizing, so it didn't change the
#					image height and therefore just let the browser
#					scale it correctly.
#					Removed the </img> and put just / before closing >
#					Changed title to use $lang['WATCHED'].
#					Moved label to be a subheadings for last/most watched.
#					Added mouseover to last/most watched to popup info about watched
#					but without the image as we already have it.
#					Added tiny thumbnail caching. Thanks Cal for sample code
#					although my version is done via returning the filename rather
#					than returning the actual image.
# Version 1.9.4, 27th June 2006.	Added last and most watched thumbnails
# Version 1.9.3. 26th June 2006.	More enhancements. Mouseover graphs for
#					watcher, year and month.
#					Added globals to control mouseovers.
#					$ws_watcher, $ws_year, $ws_month & $ws_title
#					Added sorting by title, running time & timestamp
# Version 1.9.2. 25th June 2006.	Yet another fix for no last name.
# Version 1.9.1. 25th June 2006.	Fix for no last name and double quotes in titles
#					in the mouseover.
# Version 1.9.0. 24th June 2006.	Total Re-write for multiuser support.
#

# To Do
# -----
# 1. Multiple expanded people/years/months for ya_shin
# 2. *DONE* Well sort of. Obey's handlewatched now at least.
#           Remove IN_SCRIPT but still have my_watched() working without it.
# 3. DONE. Change array handling from $row[0] to $row['xxxx']
# 4. DONE. Check the globals for graphs
# 5. DONE. Enhance thumbcache to also check for no image and also use full image if thumb is missing
# 6. DONE. Remove double line2.
# 7. DONE. Add indicator to nav window for enable/disable.
# 8. DONE. Add code to delete mysql return info
# 9. Change watched() function to not do all 4 in the code, but take an argument of
#    what line to do.
#10. Tidy $sql variables.
#11. DONE. Fix bug where if you have changed what last/most/best/worst is displayed and then click
#    a new person/year/month, it goes back to defaults for last/most/best/worst.
#12. DONE. Check what happens with images don't exist for all the mouseovers.
#13. DONE. Test default sort orders.
#14. Change link building code so it's just a function. Saves changing it everywhere.
#15. Change the limit for $maxthumbs so that it works out the max based upon window width (for ws not me routine).


if (!defined('IN_SCRIPT'))
	define('IN_SCRIPT', 1);

include_once('global.php');
$WS_SELF = 'ws.php';

global $ClassColors;
$ColScheme = "this.T_BGCOLOR='$ClassColor[2]';this.T_TITLECOLOR='$ClassColor[17]';this.T_BORDERCOLOR='$ClassColor[5]'";

if (!isset($me)) $me='';
if (!isset($action)) $action = '';
if (!isset($watched)) $watched = 'Watched';
if (!isset($borrowed)) $borrowed = 'Borrowed';

if (!isset($searchby)) $searchby = '';
if (!isset($searchtext)) $searchtext = '';
if ($searchtext == '') $searchby = '';

$ws_wb = "(eventtype='$watched'";
if (isset($ws_borrowed_is_watched) && $ws_borrowed_is_watched)
	$ws_wb .= "OR eventtype='$borrowed'";

$ws_wb .= ')';

if (($handlewatched == 2) || (($handlewatched == 1) && !$IsPrivate)) {
	if (!$me && !$action) {
		 die('This script should not be manually executed ... Possible Hacking attempt');
	}
}

if ($action == 'phpinfo') {
	phpinfo();
	exit;
}

if ($action == 'info') {
	echo "ws:Version=$ws_version";
	exit;
}
if ($action == 'update') {
	if (isset($profiles) && isset($imagecachedir) && is_dir($imagecachedir) && is_writeable($imagecachedir)) {
		echo "{$lang['WS']['STATIC']}:";
		foreach($profiles as $profilename => $profiledata) {
			my_watched($profilename);
			echo " $profilename";
		}

		echo ". {$lang['WS']['DONE']}$eoln";

# Tidy the imagecache

		$ct = time();
		$minus30 = $ct - (30 * 24*60*60);
		$removed = 0;

		$dir = @opendir($imagecachedir);
		if ($dir) {
			echo "{$lang['WS']['CLEAN']}:";
			while ($entry = readdir($dir)) {
				if ($entry != '..' && $entry != '.' && $entry != 'index.html' && $entry != 'index.htm') {
//					$lm = filemtime($imagecachedir . $entry);
//	I think we want to cache most frequently used images, and I think that using filemtime()
//	causes us to cache most frequently *changed* files. fileatime() should tell us the most
//	frequently *accessed* files ... now, if atime updates are turned off (UNiX) or not supported (FAT)
//	then I expect that the ctime() is returned.
					$lm = fileatime($imagecachedir . $entry);
					if ($lm < $minus30) {
						$ret = @unlink($imagecachedir . $entry);
					}
				}
			}
			echo " {$lang['WS']['DONE']}$eoln";
		}
# end of tidy
	}
}
if ($action == 'profiles' && isset($profiles)) {
	echo 'Profile info<br>';
	foreach($profiles as $profilename => $profiledata) {
		echo "profile name is $profilename<br>";
		foreach($profiles[$profilename] as $key => $value) {
			printf('&nbsp;&nbsp;Key %-10s - Value %s<br>', $key, $value);
		}
	}
	echo 'End of profiles.<br>';
	exit;
}
if ($me) {
	my_watched($me);
	exit;
}

# Oh this is cheap, but it should work for now!

if ($action <> 'update') {

if ($usejpgraph) {
	include($jpgraphlocation.'jpgraph.php');
	include($jpgraphlocation.'jpgraph_bar.php');
}

if (isset($graph)) {
	do_graph();
	exit;
}

if (!isset($ws_watcher)) $ws_watcher = 1;
if (!isset($ws_year)) $ws_year = 1;
if (!isset($ws_month)) $ws_month = 1;
if (!isset($ws_title)) $ws_title = 1;
 $sort = '';
 $order = '';

$yeartext = '';
$monthtext = '';
$numcols=6;
$uclass = '';
$yclass = '';
$mclass = '';

if (isset($user) && $user)
	$yeartext = $lang['WS']['YEAR'];

if (isset($year) && $year)
	$monthtext = $lang['WS']['MONTH'];


if (!isset($user)) $user = '';
if (!isset($year)) $year = '';
if (!isset($month)) $month = '';
$lasturl =  $mosturl = $besturl = $worsturl = '';
if (!isset($lastlist)) $lastlist=1;
if (!isset($mostlist)) $mostlist=1;
if (!isset($bestlist)) $bestlist=1;
if (!isset($worstlist)) $worstlist=1;
if (!isset($dolast)) $dolast=$lastlist;
if (!isset($domost)) $domost=$mostlist;
if (!isset($dobest)) $dobest=$bestlist;
if (!isset($doworst)) $doworst=$worstlist;

$uuser = rawurlencode($user);
# This code requires changing once I allow multiple expanded items.

if (isset($expand_user[0]) && $user == '') {
	$user = $expand_user[0];
}

if (isset($expand_year[0]) && $year == '') {
#	Don't care what user, if you've asked for a year to be expanded.
	if ($expand_year[0] > 0)
		$year = $expand_year[0];
	elseif ($expand_year[0] < 0)
		$year = date('Y',time());
}

if (isset($expand_month[0]) && $month == '') {
#	Only expand months, if we've asked for years to be expanded.
#	and it's the year requested.
	if (isset($expand_year[0]) && $expand_year[0] <> 0) {
		if ($expand_year[0] == $year || ($expand_year[0] == -1 && $year == date('Y', time()))) {
			if ($expand_month[0] > 0)
				$month = $expand_month[0];
			elseif ($expand_month[0] < 0)
				$month = date('m',time());
		}
	}
}

# Control whether the 'menu' item gets displayed on nav panel
# Only do it, if there is something to display

$dooptions = 0;
if ($lastlist<>2 || $mostlist<>2 || $bestlist<>2 || $worstlist<>2) $dooptions=1;

# Setup urls for the links
$nextlast=($dolast==1)?0:1;
$lasturl="$WS_SELF?user=$uuser&amp;year=$year&amp;month=$month&amp;dolast=$nextlast&amp;domost=$domost&amp;dobest=$dobest&amp;doworst=$doworst&amp;sort=$sort&amp;order=$order";
$nextmost=($domost==1)?0:1;
$mosturl="$WS_SELF?user=$uuser&amp;year=$year&amp;month=$month&amp;dolast=$dolast&amp;domost=$nextmost&amp;dobest=$dobest&amp;doworst=$doworst&amp;sort=$sort&amp;order=$order";
$nextbest=($dobest==1)?0:1;
$besturl="$WS_SELF?user=$uuser&amp;year=$year&amp;month=$month&amp;dolast=$dolast&amp;domost=$domost&amp;dobest=$nextbest&amp;doworst=$doworst&amp;sort=$sort&amp;order=$order";
$nextworst=($doworst==1)?0:1;
$worsturl="$WS_SELF?user=$uuser&amp;year=$year&amp;month=$month&amp;dolast=$dolast&amp;domost=$domost&amp;dobest=$dobest&amp;doworst=$nextworst&amp;sort=$sort&amp;order=$order";

header('Content-Type: text/html; charset="windows-1252";');
echo<<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<link rel="stylesheet" type="text/css" href="./format.css.php"></link>
<title>{$lang['WS']['TITLE']}</title>
<SCRIPT type="text/javascript" SRC="mom.js" language="JavaScript1.2"></SCRIPT>
<SCRIPT type="text/javascript" SRC="momItems.js.php" language="JavaScript1.2"></SCRIPT>
<script type="text/javascript">
<!--
	var num=0;

EOT;

if ($dooptions) {
	echo<<<EOT
	momItems[num++]=["$lang[MENU]"]

EOT;
}

if ($lastlist<>2) {
	$sh = ($dolast==1)? $lang['WS']['HIDE']: $lang['WS']['SHOW'];
	$sh .= ' ' . $lang['WS']['LAST'];
	echo<<<EOT
	momItems[num++]=["$sh", "$lasturl", "entry"]

EOT;
}

if ($mostlist<>2) {
	$sh = ($domost==1)? $lang['WS']['HIDE']: $lang['WS']['SHOW'];
	$sh .= ' ' . $lang['WS']['MOST'];
	echo<<<EOT
	momItems[num++]=["$sh", "$mosturl", "entry"]

EOT;
}

if ($bestlist<>2) {
	$sh = ($dobest==1)? $lang['WS']['HIDE']: $lang['WS']['SHOW'];
	$sh .= ' ' . $lang['WS']['BEST'];
	echo<<<EOT
	momItems[num++]=["$sh", "$besturl", "entry"]

EOT;
}

if ($worstlist<>2) {
	$sh = ($doworst==1)? $lang['WS']['HIDE']: $lang['WS']['SHOW'];
	$sh .= ' ' . $lang['WS']['WORST'];
	echo<<<EOT
	momItems[num++]=["$sh", "$worsturl", "entry"]

EOT;
}

// I've created a landing page on bws.com for the about. it links back to the pages on dvdaholic.
$vver = preg_replace('/\./', '_', $ws_version);
$vlink = "http://www.bws.com/phpdvdprofiler/ws_version_$vver.html";
#$vlink = "http://www.dvdaholic.me.uk/phpdvdprofiler/ws_version_$vver.html";
#$vlink = "http://didi/dvd/ws_version_$vver.html";
echo<<<EOT
	momItems[num++]=["{$lang['WS']['INFO']}"]
	momItems[num++]=["DVD Profiler", "http://www.invelos.com", "_blank"]
	momItems[num++]=["$lang[ANDYFORUM]", "http://www.dvdaholic.me.uk/forums/", "_blank"]
	momItems[num++]=["{$lang['WS']['ABOUT']}", "javascript:; \" onClick=\" window.open('{$vlink}','About','toolbar=no, width=670, height=400, resizable=yes, scrollbars=yes, status=yes'); return true;", ""]
	MOMbilden();

//-->
</SCRIPT>
</head>
<body>
<center>
<table width="100%" cellpadding=0 cellspacing=0>
<tr class=f1>
<td colspan=$numcols>{$lang['WS']['HEADING']}</td>
</tr>
EOT;

watched();

echo<<<EOT
<tr><td class=line2 colspan=$numcols></td></tr>
<tr class=t align=center>
<td width="15%"></td>
<td width="5%"></td>
<td width="5%"></td>
<td width="25%" align=right>{$lang['WS']['TOTRUN']}</td>
<td width="25%"></td>
<td width="25%">{$lang['WS']['AVERUNT']}</td>
</tr>
<tr class=t align=center>
<td width="15%" align=center>{$lang['WS']['VIEWER']}</td>
<td width="5">$yeartext</td>
<td width="5%">$monthtext</td>
<td width="25%" align=right>{$lang['WS']['DHM']}</td>
<td width="25%">{$lang['WS']['NOTITLES']}</td>
<td width="25%">{$lang['WS']['AVERUNB']}</td>
</tr>
EOT;

$line = 0;

#find watchers

get_watchers();

# Check numusers. If its 0 nothing has been watched

$cmd = "SELECT SUM(1) AS count, SUM(runningtime) AS runningtime FROM $DVD_EVENTS_TABLE LEFT JOIN $DVD_TABLE ON $DVD_EVENTS_TABLE.id=$DVD_TABLE.id WHERE $ws_wb";
if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
	$cmd .= " AND isadulttitle=0";

$cmd .= " GROUP BY eventtype";
$sql = $db->sql_query($cmd) or die($db->sql_error());
$rt = 0;
$cnt = 0;
$row = $db->sql_fetch_array($sql);
$cnt = $row['count'];
$rt = $row['runningtime'];
$avg = 0;
if ($cnt <> 0) $avg = intval($rt / $cnt);

$db->sql_freeresult($sql);

$days = floor($rt / 1440);
$hours = floor(($rt - ($days * 1440)) / 60);
$mins = $rt - (($hours * 60) + ($days * 1440));
$dhm = sprintf('%d : %02d : %02d', $days, $hours, $mins);

if ($cnt == 0) {
	echo<<<EOT
<tr class=line><td colspan=$numcols></td></tr>
<tr class=f1><td align=center colspan=$numcols>You may own lots of DVDs, but have you ever thought of watching them!</td></tr>
EOT;
}

echo<<<EOT
<tr class=line><td colspan=$numcols></td></tr>
<tr class=t align=center>
<td colspan=3 width="25%">{$lang['WS']['TOTAL']}</td>
<td width="25%" align=right>$dhm</td>
<td width="25%">$cnt</td>
<td width="25%">$avg</td>
</tr>
EOT;

$cmd = "SELECT DISTINCT $DVD_EVENTS_TABLE.id, runningtime FROM $DVD_EVENTS_TABLE LEFT JOIN $DVD_TABLE ON $DVD_EVENTS_TABLE.id=$DVD_TABLE.id WHERE $ws_wb";
if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
	$cmd .= " AND isadulttitle=0";

$sql = $db->sql_query($cmd) or die($db->sql_error());

$rt = 0;
$cnt = 0;
while ($row = $db->sql_fetch_array($sql)) {
	$rt += $row['runningtime'];
	$cnt++;
}

$avg = 0;
if ($cnt <> 0) $avg = intval($rt / $cnt);

$db->sql_freeresult($sql);

$days = floor($rt / 1440);
$hours = floor(($rt - ($days * 1440)) / 60);
$mins = $rt - (($hours * 60) + ($days * 1440));
$dhm = sprintf('%d : %02d : %02d', $days, $hours, $mins);

echo<<<EOT
<tr class=t align=center>
<td colspan=3 width="25%">{$lang['WS']['UNIQUEW']}</td>
<td width="25%" align=right>$dhm</td>
<td width="25%">$cnt</td>
<td width="25%">$avg</td>
</tr>
</table>
</center>
<script language="JavaScript" type="text/javascript" src="wz_tooltip.js"></script>
</body>
EOT;

$db->sql_freeresult($sql);

#exit;
}

function get_watchers() {
global $db, $DVD_TABLE, $DVD_EVENTS_TABLE, $DVD_USERS_TABLE, $lang, $watched, $user, $year, $line, $handleadult, $IsPrivate, $numcols;
global $uclass, $ws_watcher, $usejpgraph, $dolast, $domost, $dobest, $doworst, $ignore_list, $WS_SELF, $ws_wb, $ColScheme;

	$uline = 0;
	$cmd = "SELECT u.uid,firstname,lastname FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e, $DVD_USERS_TABLE u WHERE e.id=d.id AND e.uid=u.uid AND $ws_wb";
	if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
		$cmd .= " AND isadulttitle=0";

	$cmd .= " GROUP BY lastname, firstname";
	$sql = $db->sql_query($cmd) or die($db->sql_error());
	$numusers = $db->sql_numrows($sql);
	while ($row = $db->sql_fetch_array($sql)) {
		$ignore = false;
		$firstname = $row['firstname'];
		$lastname = $row['lastname'];
		$uid = $row['uid'];

		$name = $firstname . ' ' . $lastname;
		$n1 = preg_replace("/ /", "", $name);

		if (isset($ignore_list)) {
			foreach($ignore_list as $iuser) {
				$u1 = preg_replace("/ /", "", $iuser);
				if ($u1 == $n1)
					$ignore = true;
			}
		}

		if (!$ignore) {
			$cmd = "SELECT SUBSTRING(timestamp,1,4) as year, SUM(1) AS count FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e WHERE e.id=d.id AND e.uid=$uid AND $ws_wb";
			if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
				$cmd .= " AND isadulttitle=0";

			$cmd .= " GROUP BY year";
			$sql1a = $db->sql_query($cmd) or die($db->sql_error());
			$numyears = $db->sql_numrows($sql1a);
			$row = $db->sql_fetch_array($sql1a);

			$cmd = "SELECT SUM(1) AS count, SUM(runningtime) AS runningtime FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e WHERE e.id = d.id AND e.uid=$uid AND $ws_wb";
			if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
				$cmd .= " AND isadulttitle=0";

			$cmd .= " GROUP BY eventtype";
			$sql1 = $db->sql_query($cmd) or die($db->sql_error());
			$rt = 0;
			$cnt = 0;
			$row = $db->sql_fetch_array($sql1);
			$tcnt = $row['count'];
			$trt = $row['runningtime'];
			$tavg = 0;
			if ($tcnt <> 0) $tavg = intval($trt / $tcnt);

			$db->sql_freeresult($sql1);

			$cmd = "SELECT DISTINCT e.id, runningtime FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e WHERE e.id=d.id AND e.uid=$uid AND $ws_wb";
			if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
				$cmd .= " AND isadulttitle=0";

			$sql1 = $db->sql_query($cmd) or die($db->sql_error());

			$urt = 0;
			$ucnt = 0;
			while ($row = $db->sql_fetch_array($sql1)) {
				$urt += $row['runningtime'];
				$ucnt++;
			}

			$uavg = 0;
			if ($ucnt <> 0) $uavg = intval($urt / $ucnt);
			$db->sql_freeresult($sql1);

			$days = floor($trt / 1440);
			$hours = floor(($trt - ($days * 1440)) / 60);
			$mins = $trt - (($hours * 60) + ($days * 1440));
			$dhm = sprintf('%d : %02d : %02d', $days, $hours, $mins);

			$name = $firstname . ' ' . $lastname;
			$u1 = preg_replace("/ /", "", $user);
			$n1 = preg_replace("/ /", "", $name);
			$link = "$WS_SELF?user=";
			if ($u1 <> $n1)
				$link = "$WS_SELF?user=" . $n1;

			$link .= "&amp;dolast=$dolast&amp;domost=$domost&amp;dobest=$dobest&amp;doworst=$doworst";
			$line++;
			if ($line % 2)
                		$thisclass = 'class=o';
			else
                		$thisclass = 'class=l';

			$uline++;
			if ($uline % 2)
				$uclass = 'class=z1';
			else
				$uclass = 'class=z2';

			$width = 45 + ($numyears * 20);
			$mouse = "<a href=\"$link\"";
			if ($usejpgraph && $ws_watcher) {
				$mouse .= " onmouseover=\"";
				$mouse .= " this.T_WIDTH=$width;";
				$mouse .= "$ColScheme;return escape('";
				$mouse .= "<img src=\'$WS_SELF?graph=1&amp;uid=$uid\'>";
				$mouse .= "')\"";
			}
			$mouse .= ">";
			$mouse .= $firstname . " ". HideName($lastname) . "</a>";
			echo<<<EOT
<tr class=line><td colspan=$numcols></td></tr>
<tr $thisclass valign=middle align=center>
<td $uclass width="15%" rowspan=2 align=center>
$mouse
</td>
<td $uclass width="5%" rowspan=2></td>
<td $uclass width="5%" rowspan=2></td>
<td width="25%" align=right>$dhm</td>
<td width="25%" >$tcnt</td>
<td width="25%" >$tavg</td>
</tr>
EOT;

			$days = floor($urt / 1440);
			$hours = floor(($urt - ($days * 1440)) / 60);
			$mins = $urt - (($hours * 60) + ($days * 1440));
			$dhm = sprintf('%d : %02d : %02d', $days, $hours, $mins);

			$line++;
			if ($line % 2)
                		$thisclass = 'class=o';
			else
                		$thisclass = 'class=l';

			echo<<<EOT
<tr $thisclass align=center>
<td width="25%" align=right>$dhm</td>
<td width="25%">$ucnt</td>
<td width="25%">$uavg</td>
</tr>
<tr class=line><td colspan=$numcols></td></tr>
EOT;

# Get Years per watcher if user is set

			$u1 = preg_replace("/ /", "", $user);
			$n1 = preg_replace("/ /", "", $name);
			if ($u1 == $n1 || $numusers == 1) {
				get_years($uid, $firstname, $lastname);
			}
		}
	}
}

function get_years($uid, $firstname, $lastname) {

# Get Years per watcher

global $db, $DVD_TABLE, $DVD_EVENTS_TABLE, $lang, $name, $watched, $user, $year, $line, $handleadult, $IsPrivate, $numcols;
global $uclass, $yclass, $ws_year, $usejpgraph, $dolast, $domost, $dobest, $doworst, $WS_SELF, $ws_wb, $ColScheme;

	$uuser = rawurlencode($user);
	$yline = 0;
	$cmd = "SELECT SUBSTRING(timestamp,1,4) AS year, SUM(runningtime) AS runningtime, SUM(1) AS count FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e WHERE e.id=d.id AND e.uid=$uid AND $ws_wb";
	if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
		$cmd .= " AND isadulttitle=0";

	$cmd .= " GROUP BY year ORDER BY year DESC";
	$sql2 = $db->sql_query($cmd) or die($db->sql_error());

	$urt = 0;
	$ucnt = 0;
	while ($row = $db->sql_fetch_array($sql2)) {
		$yr = $row['year'];
		$yrt = $row['runningtime'];
		$ycnt = $row['count'];
		$yavg = 0;
		if ($ycnt <> 0) $yavg = intval($yrt / $ycnt);

		$days = floor($yrt / 1440);
		$hours = floor(($yrt - ($days * 1440)) / 60);
		$mins = $yrt - (($hours * 60) + ($days * 1440));
		$dhm = sprintf('%d : %02d : %02d', $days, $hours, $mins);

# Display total year figures

		$yline++;
		if ($yline % 2)
			$yclass = 'class=y';
		else
			$yclass = 'class=x';

		$link = "$WS_SELF?user=" . $uuser . "&amp;year=";
		if ($year <> $yr)
			$link = "$WS_SELF?user=" . $uuser . "&amp;year=" . $yr;

		$link .= "&amp;dolast=$dolast&amp;domost=$domost&amp;dobest=$dobest&amp;doworst=$doworst";
		$line++;
		if ($line % 2)
                	$thisclass = 'class=o';
		else
                	$thisclass = 'class=l';

		$width = 45 + (12 * 20);
		$mouse = "<a href=\"$link\"";
		if ($usejpgraph && $ws_year) {
			$mouse .= " onmouseover=\"";
			$mouse .= " this.T_WIDTH=$width;";
			$mouse .= "$ColScheme;return escape('";
			$mouse .= "<img src=\'$WS_SELF?graph=1&amp;uid=$uid&amp;year=$yr\'>";
			$mouse .= "')\"";
		}
		$mouse .= ">";
		$mouse .= $yr . "</a>";

echo<<<EOT
<tr valign=middle align=center>
<td $uclass width="15%" rowspan=2></td>
<td $yclass width="5%" rowspan=2 valign=middle>
$mouse
</td>
<td $yclass width="5%" rowspan=2></td>
<td $thisclass width="25%" align=right>$dhm</td>
<td $thisclass width="25%" >$ycnt</td>
<td $thisclass width="25%" >$yavg</td>
</tr>
EOT;

		$cmd = "SELECT DISTINCT e.id, runningtime FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e WHERE e.id=d.id AND e.uid=$uid "
			."AND $ws_wb AND SUBSTRING(timestamp,1,4)='$yr'";
		if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
			$cmd .= " AND isadulttitle=0";

		$sql3 = $db->sql_query($cmd) or die($db->sql_error());

		$yurt = 0;
		$yucnt = 0;
		$yuavg = 0;
		while ($row = $db->sql_fetch_array($sql3)) {
			$yurt += $row['runningtime'];
			$yucnt++;
		}

		if ($yucnt <> 0) $yuavg = intval($yurt / $yucnt);
		$db->sql_freeresult($sql3);

		$days = floor($yurt / 1440);
		$hours = floor(($yurt - ($days * 1440)) / 60);
		$mins = $yurt - (($hours * 60) + ($days * 1440));
		$dhm = sprintf('%d : %02d : %02d', $days, $hours, $mins);
		$line++;
		if ($line % 2)
                	$thisclass = 'class=o';
		else
                	$thisclass = 'class=l';

echo<<<EOT
<tr $thisclass valign=middle align=center>
<td width="25%" align=right>$dhm</td>
<td width="25%" >$yucnt</td>
<td width="25%" >$yuavg</td>
</tr>
<tr class=line><td colspan=$numcols></td></tr>
EOT;

		if ($year == $yr) {
			get_months($uid, $firstname, $lastname);
		}

	}
	$db->sql_freeresult($sql3);
}

function get_months($uid, $firstname, $lastname) {
global $db, $DVD_TABLE, $DVD_EVENTS_TABLE, $lang, $line, $user, $year, $month, $handleadult, $IsPrivate, $numcols;
global $uclass, $yclass, $ws_month, $usejpgraph, $dolast, $domost, $dobest, $doworst, $watched, $WS_SELF, $ws_wb, $ColScheme;
# Now get the months

	$uuser = rawurlencode($user);
	$mline = 0;
	$maxmonths = 12;
	if ($year == date("Y",time()))
		$maxmonths = date("m", time());

        for($mth=$maxmonths;$mth>0;$mth--){
                $yearmonth = sprintf('%04d-%02d', $year, $mth);
                $cmd = "SELECT SUM(runningtime) AS runningtime, SUM(1) AS count, e.id FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e ";
		$cmd .= "WHERE e.id=d.id AND e.uid=$uid AND SUBSTRING(timestamp,1,7)='$yearmonth' AND $ws_wb";
		if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
			$cmd .= " AND isadulttitle=0";

		$cmd .= " GROUP BY SUBSTRING(timestamp,1,7)";
                $sql4 = $db->sql_query($cmd) or die($db->sql_error());

                $murt = 0;
                $mucnt = 0;
                $row = $db->sql_fetch_array($sql4);
                $db->sql_freeresult($sql4);
                $mrt = $row['runningtime'];
                $mcnt = $row['count'];
		$mavg = 0;
		if ($mcnt <> 0) $mavg = intval($mrt / $mcnt);

                $days = floor($mrt / 1440);
                $hours = floor(($mrt - ($days * 1440)) / 60);
                $mins = $mrt - (($hours * 60) + ($days * 1440));
		$dhm = sprintf('%d : %02d : %02d', $days, $hours, $mins);

# Display total month figures

		if ($mcnt) {
                	$mline++;
                	if ($mline % 2)
                        	$mclass = 'class=u';
                	else
                        	$mclass = 'class=v';

                	$line++;
                	if ($line % 2)
                        	$thisclass = 'class=o';
                	else
                        	$thisclass = 'class=l';

                	$monthp = sprintf('%02d', $mth);

			$link = "$WS_SELF?user=" . $uuser . "&amp;year=" . $year . "&amp;month=";
			if ($month <> $monthp)
				$link = "$WS_SELF?user=" . $uuser . "&amp;year=" . $year . "&amp;month=" . $monthp;

			$link .= "&amp;dolast=$dolast&amp;domost=$domost&amp;dobest=$dobest&amp;doworst=$doworst";
			# This gets the month name in the current locale
			$amth = fix88595(ucwords(strftime("%B", mktime(0,0,0,$mth,1,0))));
				# $width = 45 + (cal_days_in_month(CAL_GREGORIAN, $mth, $year) * 10);
				$width = 45 + (date("j", mktime(0,0,0,intval($month)+1,0, $year)) * 10);
				$mouse = "<a href=\"$link\"";
				if ($usejpgraph && $ws_month) {
					$mouse .= " onmouseover=\"";
					$mouse .= " this.T_WIDTH=$width;";
					$mouse .= "$ColScheme;return escape('";
					$mouse .= "<img src=\'$WS_SELF?graph=1&amp;uid=$uid&amp;year=$year&amp;month=$monthp\'>";
					$mouse .= "')\"";
				}
				$mouse .= ">";
				$mouse .= $amth . "</a>";
				$html = $mouse;

				echo<<<EOT
<tr valign=middle align=center>
<td $uclass width="15%"></td>
<td $yclass width="5%"></td>
<td $mclass align=right width="5%" valign=middle>$html</td>
<td $thisclass width="25%" align=right>$dhm</td>
<td $thisclass width="25%" >$mcnt</td>
<td $thisclass width="25%" >$mavg</td>
</tr>
<tr class=line><td colspan=$numcols></td></tr>
EOT;

			if ($month == $monthp) {
				get_titles($uid, $firstname, $lastname, $mclass);
			}
		}

	}
}

function get_titles($uid, $firstname, $lastname, $mclass) {
global $db, $DVD_TABLE, $DVD_EVENTS_TABLE, $defaultorder, $lang, $watched, $line, $user, $year, $handleadult, $month;
global $IsPrivate, $wssortorder, $numcols, $uclass, $yclass, $ws_title, $sort, $order, $dolast, $domost, $dobest, $doworst, $WS_SELF, $ws_wb;

	$uuser = rawurlencode($user);

	if (!isset($wssortorder))
		$wssortorder = "sorttitle";

	if (!isset($sort) || !$sort)
		$sort = $wssortorder;

	if (!isset($order) || ($order != 'asc' && $order != 'desc')) {
		$order = $defaultorder[$sort];
	}

        $sortimg_title = ($sort=='sorttitle') ? "<img src=\"gfx/$order.gif\" width=13 height=13 border=0 alt=\"\"/>&nbsp;{$lang['WS']['MOVIE']}": $lang['WS']['MOVIE'] ;
        $sortimg_runtime = ($sort=='runningtime') ? "<img src=\"gfx/$order.gif\" width=13 height=13 border=0 alt=\"\"/>&nbsp;{$lang['WS']['RUNTIME']}": $lang['WS']['RUNTIME'];
        $sortimg_date = ($sort=='timestamp') ? "<img src=\"gfx/$order.gif\" width=13 height=13 border=0 alt=\"\"/>&nbsp;{$lang['WS']['DATE']}": $lang['WS']['DATE'];

        $sorthdr_title = ($sort=='sorttitle') ? ($order=='asc')?'desc':'asc': $defaultorder['sorttitle'];
        $sorthdr_runtime = ($sort=='runningtime') ? ($order=='asc')?'desc':'asc': $defaultorder['runningtime'];
        $sorthdr_date = ($sort=='timestamp') ? ($order=='asc')?'desc':'asc': $defaultorder['timestamp'];

	$s1 = addslashes($lang['SORTTITLE']);
	$s2 = addslashes($lang['SORTRUNTIME']);
	$s3 = addslashes($lang['SORTTIMESTAMP']);

	$dolink="&amp;dolast=$dolast&amp;domost=$domost&amp;dobest=$dobest&amp;doworst=$doworst";
	$link_title = "<a class=n href=\"$WS_SELF?user=$firstname$lastname&amp;year=$year&amp;month=$month&amp;sort=sorttitle&amp;order=$sorthdr_title$dolink\" title=\"$s1\">$sortimg_title</a>";
	$link_runtime = "<a class=n href=\"$WS_SELF?user=$firstname$lastname&amp;year=$year&amp;month=$month&amp;sort=runningtime&amp;order=$sorthdr_runtime$dolink\" title=\"$s2\">$sortimg_runtime</a>";
	$link_date = "<a class=n href=\"$WS_SELF?user=$firstname$lastname&amp;year=$year&amp;month=$month&amp;sort=timestamp&amp;order=$sorthdr_date$dolink\" title=\"$s3\">$sortimg_date</a>";
	echo<<<EOT
<tr class=t align=center>
<td $uclass width="15%"></td>
<td $yclass width="5%"></td>
<td $mclass width="5%"></td>
<td class=t width="40%" align=left>$link_title</td>
<td class=t width="15%">$link_runtime</td>
<td class=t width="20%">$link_date</td>
</tr>
EOT;

	$ym = $year . "-" . $month;
	$cmd = "SELECT e.id,title,runningtime,timestamp,reviewfilm FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e WHERE e.id=d.id AND e.uid=$uid "
		."AND $ws_wb AND SUBSTRING(timestamp,1,7)='$ym'";
	if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
		$cmd .= " AND isadulttitle=0";

	$cmd .= " ORDER BY $sort $order";
	$sql5 = $db->sql_query($cmd) or die($db->sql_error());
	$line++;
	while ($row = $db->sql_fetch_array($sql5)) {
		$id = $row['id'];
		$title = $row['title'];
		$runningtime = $row['runningtime'];
		$timestamp = $row['timestamp'];
		$reviewfilm = FixAReviewValue($row['reviewfilm']);

                $line++;
                if ($line % 2)
                        $thisclass = 'class=l';
                else
                        $thisclass = 'class=o';

		list($tdate, $ttime) = explode(' ',$timestamp);
		list($tyear, $tmonth, $tday) = explode('-', $tdate);
		list($thour, $tmin, $tsec) = explode(':',$ttime);
		$tm = mktime($thour, $tmin, $tsec, $tmonth, $tday, $tyear);
		$dt = fix88595(ucwords(strftime($lang['SHORTDATEFORMAT'], $tm)));

		if ($ws_title) {
			$title = addslashes($title);
			$mouse = mouseover($id, $title, 1, $reviewfilm);
		} else
			$mouse = $title;

		echo<<<EOT
<tr valign=middle align=center>
<td $uclass width="15%"></td>
<td $yclass width="5%"></td>
<td $mclass width="5%"></td>
<td $thisclass width="40%" align=left>$mouse</td>
<td $thisclass width="15%">$runningtime</td>
<td $thisclass width="20%">$dt</td>
</tr>
EOT;
	}
	$db->sql_freeresult($sql5);
	echo<<<EOT
<tr>
<td $uclass></td>
<td $yclass></td>
<td $mclass></td>
<td class=t colspan=3>&nbsp;</td>
</tr>
<tr class=line><td colspan=$numcols></td></tr>
EOT;

	$line++;
	return;
}

function mouseover($id, $title, $doimage, $topline) {
#
# $doimage controls whether we do the image in the mouseover, or as the thing we're mousing over
# 1 = do the image in the mouseover, 0 = this is what we are mousing over
#
global $db, $DVD_TABLE, $DVD_EVENTS_TABLE, $DVD_USERS_TABLE, $lang, $watched, $handleadult, $IsPrivate, $ws_wb, $ColScheme, $getimages, $img_webpath;

	$mouseline = 1;
	$cmd = "SELECT DISTINCT * FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e WHERE e.id=d.id AND e.id='$id' AND $ws_wb";
	if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
		$cmd .= " AND isadulttitle=0";

	$cmd .= " GROUP BY e.uid";
	$sql6 = $db->sql_query($cmd) or die($db->sql_error());
	$unique = $db->sql_numrows($sql6);
	$db->sql_freeresult($sql6);

	$cmd = "SELECT firstname, lastname, timestamp FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e, $DVD_USERS_TABLE u WHERE e.id=d.id AND e.uid=u.uid AND e.id='$id' AND $ws_wb";
	if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
		$cmd .= " AND isadulttitle=0";

	$cmd .= " ORDER BY CONCAT(lastname,' ',firstname),timestamp DESC";
	$sql7 = $db->sql_query($cmd) or die($db->sql_error());
	$times = $db->sql_numrows($sql7);

	$rs = $times+1;
	if ($getimages == 3)
		$thumbname = "$img_webpath[$id]f.jpg";
	else
		$thumbname = PhyspathToWebpath(resize_jpg($id, "f", 60, 100));

	$mouse = "<a href=\"index.php?mediaid=$id&amp;action=show\"";
	$mouse .= " onmouseover=\"";
	$title = preg_replace("/&/", "&amp;", $title);
	$mousetitle = preg_replace("/\'/", "'", $title);
	$mousetitle = preg_replace('/\\\"/', "&quot;", $mousetitle);
	$who = addslashes($lang['WS']['WHO']);
	$mouse .= " this.T_TITLE='$who \'$mousetitle\'';";
	$mouse .= "$ColScheme;return escape('";
	$mouse .= "<table width=\'100%\' border cellpadding=0 cellspacing=0>";

	if ($topline > 0) {
		$thisclass = 'class=o';
		$cpan = 2 + $doimage;
		$tl = sprintf("%02d", $topline);
		$mouse .= "<tr $thisclass><td align=center valign=middle colspan=3><img src=\'gfx/{$tl}.gif\' alt=\'$topline / 10\'></td></tr>";
	}

	$mouse .= "<tr class=a>";
	if ($doimage)
		$mouse .= "<td align=center valign=middle rowspan=$rs><img width=60 src=\'$thumbname\' alt=\'{$lang['WS']['NOIMAGE']}\'></td>";

	$mouse .= "<td width=\'60%\' align=center valign=middle >" . $lang['WS']['WATCHED'] .": $times</td>";
	$mouse .= "<td width=\'40%\' align=center valign=middle >" . $lang['WS']['UNIQUE'] .": $unique</td>";
	$mouse .= "</tr>";
	while ($row = $db->sql_fetch_array($sql7)) {
		$firstname = $row['firstname'];
		$lastname = $row['lastname'];
		$timestamp = $row['timestamp'];
                $mouseline++;
                if ($mouseline % 2)
                        $thisclass = 'class=l';
                else
                        $thisclass = 'class=o';
		$mouse .= "<tr $thisclass>";
		$mouse .= "<td style=\'padding-left:5px; padding-right=5px\' valign=middle align=center>$firstname " . HideName($lastname) . "</td>";
		list($tdate, $ttime) = explode(' ',$timestamp);
		list($tyear, $tmonth, $tday) = explode('-', $tdate);
		list($thour, $tmin, $tsec) = explode(':',$ttime);
		$tm = mktime($thour, $tmin, $tsec, $tmonth, $tday, $tyear);
		$dt = fix88595(ucwords(strftime($lang['SHORTDATEFORMAT'], $tm)));
		$mouse .= "<td style=\'padding-left:5px; padding-right=5px\'valign=middle align=center>$dt</td>";
		$mouse .= "</tr>";
	}
	$db->sql_freeresult($sql7);
	$mouse .= "</table>";
	$mouse .= "')\"";
	$mouse .= ">";
	if ($doimage) {
		$title = preg_replace("/\\\'/", "&rsquo;", $title);
		$mouse .= preg_replace('/\\\"/', "&quot;", $title);
	} else
		$mouse .= "<img width=60 title=\"\" src=\"$thumbname\" alt=\"{$lang['WS']['NOIMAGE']}\"/>";

	$mouse .= "</a>";

	return $mouse;
}

function do_graph() {
global $db, $DVD_TABLE, $DVD_EVENTS_TABLE, $handleadult, $IsPrivate, $uid, $year, $month, $watched, $ws_wb;

	if (!isset($year) || !$year) {
		$cmd = "SELECT SUBSTRING(timestamp,1,4) AS year, SUM(1) AS count FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e WHERE e.id=d.id AND e.uid=$uid AND $ws_wb";
		if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
			$cmd .= " AND isadulttitle=0";

		$cmd .= " GROUP BY year ORDER BY year DESC";
	} else {
		if (!isset($month) || !$month) {
			$cmd = "SELECT SUBSTRING(timestamp,1,7) AS year, SUM(1) AS count FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e WHERE e.id=d.id AND e.uid=$uid AND SUBSTRING(timestamp,1,4)='$year' AND $ws_wb";
			if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
				$cmd .= " AND isadulttitle=0";

			$cmd .= " GROUP BY year ORDER BY year";
		} else {
			$cmd = "SELECT SUBSTRING(timestamp,1,10) AS year, SUM(1) AS count FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e WHERE e.id=d.id AND e.uid=$uid AND SUBSTRING(timestamp,1,7)='$year-$month' AND $ws_wb";
			if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
				$cmd .= " AND isadulttitle=0";

			$cmd .= " GROUP BY year ORDER BY year";
		}
	}

	$sql = $db->sql_query($cmd) or die($db->sql_error());

	$dates = array();

	$cnt=0;
	while ($row = $db->sql_fetch_array($sql)) {
		$cnt++;
		$yr = $row['year'];
		$wth = $row['count'];
		if (isset($month) && $month) {
			$yr = substr($yr,8,2) + 0;
		} else {
			if (isset($year) && $year) {
				$yr = substr($yr,5,2) + 0;
			}
		}
		$dates[$yr] = $wth;
	}
	$db->sql_freeresult($sql);

	if (isset($year) && $year) {
		if (isset($month) && $month) {
			#$cnt = cal_days_in_month(CAL_GREGORIAN, $month, $year);
			$cnt = date("j", mktime(0,0,0,$month+1,0, $year));
			for($mth=1;$mth<=$cnt;$mth++) {
       			if (!array_key_exists($mth, $dates))
               			$dates[$mth] = 0;
			}
		} else {
			$cnt = 12;
			for($mth=1;$mth<=$cnt;$mth++) {
       			if (!array_key_exists($mth, $dates))
               			$dates[$mth] = 0;
			}
		}
	}

	$width = 20;
	$intval = 1;
	if (isset($month) && $month) {
		$width=10;
		$intval=3;
	}

	$graphx = 45 + ($cnt*$width);
	$graphy=100;

	ksort($dates);

	$ymax = 0;
	foreach ($dates as $key => $val) {
		$data[] = $dates[$key];
		if ($val > $ymax)
			$ymax = $val;

		$yr = $key;
		if (!isset($year) || !$year)
			$yr = substr($yr,2,2);

		$leg[] = $yr;
	}

	if ($ymax > 100)
		$ymax = round($ymax+21,-1);
	elseif ($ymax > 10)
		$ymax = round($ymax+11,-1);
	elseif ($ymax >= 5)
		$ymax = 10;
	elseif ($ymax < 5)
		$ymax = 5;

	$graph = new Graph($graphx, $graphy, 'auto');
	$graph->SetScale('textint', 0, $ymax);
	$graph->img->SetMargin(35, 10, 5, 30);
	#$graph->AdjBackgroundImage(0.4, 0.7, -1); //Removed for 2.0.10 as its not compatible with GD2
	$graph->SetShadow();

	$graph->xaxis->SetTickLabels($leg);
	$graph->xaxis->SetTextLabelInterval($intval);
	$graph->xaxis->SetFont(FF_COURIER);
	$graph->xaxis->HideTicks();

	$bplot = new BarPlot($data);
	$bplot->SetFillColor('lightgreen'); // Fill color
	$bplot->value->SetColor('black', 'navy');

	$graph->Add($bplot);
	$graph->Stroke();
	return;
}

function watched() {
global $db, $DVD_TABLE, $DVD_EVENTS_TABLE, $lang, $watched, $user, $year, $month, $line, $handleadult, $IsPrivate, $numcols, $maxthumbs;
global $jpgraphlocation, $lastlist, $mostlist, $bestlist, $worstlist, $dolast, $domost, $dobest, $doworst, $WS_SELF, $ws_wb;

# Function to display last and most watched thumbnails
# Last watched (don't count multiples)
# Last watched. No idea why you wouldn't want to count the multiples. Stupid
# if you as me and I wrote the code and comment. Go figure!. Obviously not
# enough beer.
# Simple, cos that's not what it was supposed to do. It was meant to only count
# 1 user if they watched at the same time. That's the bug to fix. Now to do it by
# adding 'DISTINCT' back. Not the GROUP BY.

$uuser = rawurlencode($user);

if (!isset($maxthumbs) || $maxthumbs > 10)
	$maxthumbs = 10;

$nc = $maxthumbs;

echo<<<EOT
<tr class=o>
<td colspan=$numcols>
<table width="100%" cellspacing=0 cellpadding=0>
EOT;

#
# Last Watched
#
	if ($lastlist <> 2 && $dolast) {
		$nextlast=($dolast==1)?0:1;
		$link="$WS_SELF?user=$uuser&amp;year=$year&amp;month=$month&amp;dolast=$nextlast&amp;domost=$domost&amp;dobest=$dobest&amp;doworst=$doworst";
		echo<<<EOT
<tr><td class=line2 colspan=$nc></td></tr>
<tr class=t>
<td colspan=$nc align=center valign=middle><a class=n href="$link" title="{$lang['WS']['TOGGLE']}">{$lang['WS']['LAST']}</a></td>
</tr>
<tr class=line2><td colspan=$nc></td></tr>
EOT;
		if ($dolast) {
			echo "<tr>";
			$cmd = "SELECT DISTINCT $DVD_EVENTS_TABLE.id, title, timestamp, reviewfilm FROM $DVD_EVENTS_TABLE LEFT JOIN $DVD_TABLE ON $DVD_EVENTS_TABLE.id=$DVD_TABLE.id WHERE $ws_wb";

			if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
				$cmd .= " AND isadulttitle=0";

			$cmd .= " ORDER BY timestamp DESC LIMIT $maxthumbs";

			$sql8 = $db->sql_query($cmd) or die($db->sql_error());
			while ($row = $db->sql_fetch_array($sql8)) {
				$id = $row['id'];
				$timestamp = $row['timestamp'];
				$reviewfilm = FixAReviewValue($row['reviewfilm']);
				$title = addslashes($row['title']);
				$mouse = mouseover($id, $title, 0, $reviewfilm);
				echo<<<EOT
<td align=center valign=middle>
$mouse
</td>
EOT;
			}
			$db->sql_freeresult($sql8);
			echo "</tr>";
		}
	}

#
# Most Watched
#
	if ($mostlist <> 2 && $domost) {
		$nextmost=($domost==1)?0:1;
		$link="$WS_SELF?user=$user&amp;year=$year&amp;month=$month&amp;dolast=$dolast&amp;domost=$nextmost&amp;dobest=$dobest&amp;doworst=$doworst";
		echo<<<EOT
<tr><td class=line2 colspan=$nc></td></tr>
<tr class=t>
<td colspan=$nc align=center valign=middle><a class=n href="$link" title="{$lang['WS']['TOGGLE']}">{$lang['WS']['MOST']}</a></td>
</tr>
<tr class=line2><td colspan=$nc></td></tr>
EOT;

		if ($domost) {
			echo "<tr>";
			$cmd = "SELECT $DVD_EVENTS_TABLE.id, SUM(1) AS count, title, reviewfilm FROM $DVD_EVENTS_TABLE LEFT JOIN $DVD_TABLE ON $DVD_EVENTS_TABLE.id = $DVD_TABLE.id WHERE $ws_wb";
			if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
				$cmd .= " AND isadulttitle=0";

			$cmd .= " GROUP BY $DVD_EVENTS_TABLE.id ORDER BY count DESC, sorttitle LIMIT $maxthumbs";
			$sql9 = $db->sql_query($cmd) or die($db->sql_error());
			while ($row = $db->sql_fetch_array($sql9)) {
				$id = $row['id'];
				$count = $row['count'];
				$reviewfilm = FixAReviewValue($row['reviewfilm']);
				$title = addslashes($row['title']);
				$mouse = mouseover($id, $title, 0, $reviewfilm);
				echo<<<EOT
<td align=center valign=middle>
$mouse
</td>
EOT;
			}
			$db->sql_freeresult($sql9);
			echo "</tr>";
		}
	}

#
# Best Review
#
	if ($bestlist <> 2 && $dobest) {
		$nextbest=($dobest==1)?0:1;
		$link="$WS_SELF?user=$user&amp;year=$year&amp;month=$month&amp;dolast=$dolast&amp;domost=$domost&amp;dobest=$nextbest&amp;doworst=$doworst";
		echo<<<EOT
<tr><td class=line2 colspan=$nc></td></tr>
<tr class=t>
<td colspan=$nc align=center valign=middle><a class=n href="$link" title="{$lang['WS']['TOGGLE']}">{$lang['WS']['BEST']}</a></td>
</tr>
<tr class=line2><td colspan=$nc></td></tr>
EOT;

		if ($dobest) {
			echo "<tr>";
			$cmd = "SELECT DISTINCT $DVD_EVENTS_TABLE.id, title, reviewfilm FROM $DVD_EVENTS_TABLE LEFT JOIN $DVD_TABLE ON $DVD_EVENTS_TABLE.id=$DVD_TABLE.id WHERE $ws_wb AND reviewfilm > '0'";
			if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
				$cmd .= " AND isadulttitle=0";

			$cmd .= " ORDER BY reviewfilm DESC, timestamp DESC LIMIT $maxthumbs";
			$sql10 = $db->sql_query($cmd) or die($db->sql_error());
			while ($row = $db->sql_fetch_array($sql10)) {
				$id = $row['id'];
				$title = addslashes($row['title']);
				$reviewfilm = FixAReviewValue($row['reviewfilm']);
				$p_reviewfilm = '<img src=\'gfx/'.sprintf("%02d",$reviewfilm).'.gif\' alt=\''.$reviewfilm.' / 10\'/>';
				$mouse = mouseover($id, $title, 0, $reviewfilm);
				echo<<<EOT
<td align=center valign=middle>
$mouse
</td>
EOT;
			}
		$db->sql_freeresult($sql10);
		echo "</tr>";
		}
	}
#
# Worst Review
#
	if ($worstlist <> 2 && $doworst) {
		$nextworst=($doworst==1)?0:1;
		$link="$WS_SELF?user=$user&amp;year=$year&amp;month=$month&amp;dolast=$dolast&amp;domost=$domost&amp;dobest=$dobest&amp;doworst=$nextworst";
		echo<<<EOT
<tr><td class=line2 colspan=$nc></td></tr>
<tr class=t>
<td colspan=$nc align=center valign=middle><a class=n href="$link" title="{$lang['WS']['TOGGLE']}">{$lang['WS']['WORST']}</a></td>
</tr>
<tr class=line2><td colspan=$nc></td></tr>
EOT;
		if ($doworst) {
			echo "<tr>";
			$cmd = "SELECT DISTINCT $DVD_EVENTS_TABLE.id, title, reviewfilm FROM $DVD_EVENTS_TABLE LEFT JOIN $DVD_TABLE ON $DVD_EVENTS_TABLE.id=$DVD_TABLE.id WHERE $ws_wb AND reviewfilm > '0'";
			if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
				$cmd .= " AND isadulttitle=0";

			$cmd .= " ORDER BY reviewfilm, timestamp DESC LIMIT $maxthumbs";
			$sql11 = $db->sql_query($cmd) or die($db->sql_error());
			while ($row = $db->sql_fetch_array($sql11)) {
				$id = $row['id'];
				$title = addslashes($row['title']);
				$reviewfilm = FixAReviewValue($row['reviewfilm']);
				$p_reviewfilm = '<img src=\'gfx/'.sprintf("%02d",$reviewfilm).'.gif\' alt=\''.$reviewfilm.' / 10\'/>';
				$mouse = mouseover($id, $title, 0, $reviewfilm);
				echo<<<EOT
<td align=center valign=middle>
$mouse
</td>
EOT;
			}
		$db->sql_freeresult($sql11);
		echo "</tr>";
		}
	}

#
#end
#
	echo<<<EOT
</table>
</td>
</tr>
EOT;
	return;
}

function my_watched($me) {
global $db, $DVD_TABLE, $DVD_EVENTS_TABLE, $DVD_USERS_TABLE, $lang, $watched, $user, $year, $line, $handleadult, $IsPrivate, $numcols, $maxthumbs;
global $img_physpath, $img_webpath, $jpgraphlocation, $profiles, $ws_wb;
global $imagecachedir, $action, $gdfp, $DVD_TAGS_TABLE, $CullDupsFromWatched, $me_updating, $GDFontPathOverride;

#	Ok, here's what all the $me_* do :-
#		$me_nick = nickname to display (will use firstname if not set)
#		$me_first = firstname that dvdprofiler uses
#		$me_last = lastname that dvdprofiler uses
#		$me_width = width of image (pixels)
#		$me_height = height of image (pixels)
#		$me_num = number columns of thumbnails
#		$me_rows = number rows thumbnails
#		$me_start = number to start at (instead of 0)
#		$me_bgcol = background colour
#		$me_font = font name to use for text
#		$me_fontcol = font colour
#		$me_fontsize = font size
#		$me_hborder = border size (pixels) on top and bottom
#		$me_wborder = border size (pixels) on left and right
#		$me_padding = padding between thumbnails (pixels)
#		$me_space = padding on outside left of image
#		$me_topmsg = top message to display
#		$me_topalign = "l" (l, r, c)
#		$me_botmsg = botoom message to display
#		$me_botalign = "r" (l, r, c)
#		$me_quality = quality of thumbnail images (0 worst - 100 best)
#		$me_datefmt = format that dates should be displayed in
#		$me_type = 'watched' (watched or new)
#		$me_filter = '' (really only useful if type is 'watched' in which case it might be 'and collectionnumber'
#
#	Ok, some rules.
#		if none of this lot are set it defaults to :-
#			$me_width = 60, $me_height = $me_width*7/5, $me_num = 10, $me_rows = 1, $me_start = 0, $me_bgcol = (C8,C8,C8)
#			$me_font = arial, $me_fontcol = (0,0,0), $me_fontsize = 10, $me_hborder = 3, $me_wborder = 6, $me_padding = 6
#			$me_topmsg = "What #NAME#'s Watched Recently", $me_botmsg = "As of #DATE#", $me_quality = 80, $me_topalign = "c"
#			$me_botalign = "l", $me_datefmt = $lang[SHORTDATEFORMAT], $me_type = 'watched', $me_filter = '', $me_space = 0.
#

	if (!isset($profiles)) $profiles = array();
	if (!isset($me_first)) $me_first = "";
	if (!isset($me_last)) $me_last = "";
	if (!isset($me_width)) $me_width = 60;
	if (!isset($me_height)) $me_height = $me_width*7/5;
	if (!isset($me_num)) $me_num = 10;
	if (!isset($me_rows)) $me_rows = 1;
	if (!isset($me_start)) $me_start = 0;
	if (!isset($me_bgcol)) $me_bgcol = "C8C8C8";
	if (!isset($me_font)) $me_font = "arial.ttf";
	if (!isset($me_fontcol)) $me_fontcol = "000000";
	if (!isset($me_fontsize)) $me_fontsize = 10;
	if (!isset($me_hborder)) $me_hborder = 3;
	if (!isset($me_wborder)) $me_wborder = 6;
	if (!isset($me_padding)) $me_padding = 6;
	if (!isset($me_space)) $me_space = 0;
	if (!isset($me_topmsg)) $me_topmsg = "What #NAME#'s Watched Recently.";
	if (!isset($me_botmsg)) $me_botmsg = "As of #DATE#";
	if (!isset($me_topalign)) $me_topalign = "c";
	if (!isset($me_botalign)) $me_botalign = "l";
	if (!isset($me_quality)) $me_quality = 80;
	if (!isset($me_datefmt)) $me_datefmt = $lang['SHORTDATEFORMAT'];
	if (!isset($me_type)) $me_type = 'watched';
	if (!isset($me_filter)) $me_filter = '';
	//DJ Doena
	if (!isset($me_excludetag)) $me_excludetag = '';
	if (!isset($me_includetag)) $me_includetag = '';
	if (!isset($me_createmap)) $me_createmap = false;
	if (!isset($me_maptoprofilerpath)) $me_maptoprofilerpath = "";
	if (!isset($me_mapwithdate)) $me_mapwithdate = false;
	if (!isset($me_includetags)) $me_includetags = '';
	//DJ Doena end

	if (!isset($me_nick)) $me_nick = '';

#	Determine what to set based upon the profile.

	if (array_key_exists($me, $profiles)) {
		foreach($profiles[$me] as $prokey => $proval) {
			switch($prokey) {
				case "nick":
					$me_nick = $proval;
					break;
				case "first":
					$me_first = $proval;
					break;
				case "last":
					$me_last = $proval;
					break;
				case "width":
					$me_width = $proval;
					break;
				case "height":
					$me_height = $proval;
					break;
				case "num":
					$me_num = $proval;
					break;
				case "rows":
					$me_rows = $proval;
					break;
				case "start":
					$me_start = $proval;
					break;
				case "bgcol":
					$me_bgcol = $proval;
					break;
				case "font":
					$me_font = $proval;
					break;
				case "fontcol":
					$me_fontcol = $proval;
					break;
				case "fontsize":
					$me_fontsize = $proval;
					break;
				case "hborder":
					$me_hborder = $proval;
					break;
				case "wborder":
					$me_wborder = $proval;
					break;
				case "padding":
					$me_padding = $proval;
					break;
				case "space":
					$me_space = $proval;
					break;
				case "topmsg":
					$me_topmsg = $proval;
					break;
				case "botmsg":
					$me_botmsg = $proval;
					break;
				case "topalign":
					$me_topalign = $proval;
					break;
				case "botalign":
					$me_botalign = $proval;
					break;
				case "quality":
					$me_quality = $proval;
					break;
				case "datefmt":
					if ($proval <> "")
						$me_datefmt = $proval;
					break;
				case "type":
					$me_type = $proval;
					break;
				case "filter":
					$me_filter = $proval;
					break;
				//DJ Doena
				case "excludetag":
				    $me_excludetag = $proval;
				    break;
				case "includetag":
				    $me_includetag = $proval;
				    break;
				case "createmap":
				    $me_createmap = $proval;
				    break;
				case "maptoprofilerpath":
				    $me_maptoprofilerpath = $proval;
				    break;
				case "mapwithdate":
				    $me_mapwithdate = $proval;
				    break;
				case "includetags":
				    $me_includetags = $proval;
				    break;
				//DJ Doena end
			}
		}
	} else {
# didn't find the profile
		echo "I'm sorry, but profile ($me) doesn't exist";
	foreach ($profiles as $key => $val)
		if (isset($val['last']))
			$profiles[$key]['last'] = HideName($profiles[$key]['last']);
echo "<pre>\$me="; print_r($me);echo "\n\$profiles="; print_r($profiles);
		exit;
	}

	if ($GDFontPathOverride != '') {
       		@putenv('GDFONTPATH=' . $GDFontPathOverride);
	}

#	No nickname? use the firstname
	if ($me_nick == '')
		$me_nick = $me_first;

	if ( $me_type == 'watched' ) {

#	Check the namelist first
		$cmd = "SELECT DISTINCT e.id,title,mediabannerfront,custommediatype,originaltitle,timestamp FROM $DVD_TABLE d, $DVD_EVENTS_TABLE e, $DVD_USERS_TABLE u WHERE e.id=d.id AND e.uid=u.uid AND $ws_wb";
		$cmd .= " $me_filter AND firstname='$me_first' AND lastname='$me_last'";
	}

	else if ( $me_type == 'new' ) {
		$cmd = "SELECT id, title, mediabannerfront, custommediatype, originaltitle, purchasedate AS timestamp FROM $DVD_TABLE WHERE collectiontype='owned' $me_filter";
		//$cmd = "SELECT id, title, purchasedate AS timestamp FROM $DVD_TABLE WHERE collectiontype='owned' $me_filter";
	}
	else {
		$cmd = "SELECT d.id, title, mediabannerfront, custommediatype, originaltitle, purchasedate as timestamp FROM $DVD_TABLE d, $DVD_TAGS_TABLE t WHERE fullyqualifiedname='$me_type' AND d.id=t.id AND collectiontype='owned' $me_filter";
	}

#	Never display adult as we're going to enhance this bit to cache the image
	$cmd .= " AND isadulttitle=0";

	$mt = $me_num * $me_rows;
//DJ Doena
/* original code:
	if ( $me_type == 'watched' ) {
		$cmd .= " ORDER BY timestamp DESC LIMIT $me_start,$mt";
	}
	else if ( $me_type == 'new' ) {
		$cmd .= " ORDER BY timestamp DESC, title LIMIT $me_start,$mt";
	}
	else {
		$cmd .= " ORDER BY timestamp DESC, title LIMIT $me_start,$mt";
	}
*/
	$cmd .= " ORDER BY timestamp DESC, sorttitle DESC";
	if (!$CullDupsFromWatched && $me_excludetag == '' && $me_includetag == '' && !is_array($me_includetags)) {
		$cmd .= " LIMIT $me_start,$mt";
	}
	//DJ Doena end
//DebugLog($cmd);
#	Ok, get the time of the last watched movie. We'll use that if they want the
#	data, rather than the timestamp on the xml.
	$sql8 = $db->sql_query($cmd) or die($db->sql_error());
	$row = $db->sql_fetch_array($sql8);
	$timestamp = $row['timestamp'];
	if ( !$timestamp )
		return;

	$db->sql_freeresult($sql8);
	if ( $me_type == 'watched' ) {
		list($tdate, $ttime) = explode(' ',$timestamp);
		list($tyear, $tmonth, $tday) = explode('-', $tdate);
		list($thour, $tmin, $tsec) = explode(':',$ttime);
		$tm = mktime($thour, $tmin, $tsec, $tmonth, $tday, $tyear);

	}
	//DJ Doena
	/* original code:
	else if ( $me_type == 'new' ) {
		$tm = $timestamp;
	}
	*/
	//DJ Doena end
	else {
		$tm = $timestamp;
	}

	$thedatetime = fix88595(ucwords(strftime($me_datefmt, $tm)));

#	Ok, work out the size of the text to be displayed.
	$ttext = preg_replace('/#NAME#/', $me_nick, $me_topmsg);
	$ttext = preg_replace('/#DATE#/', $thedatetime , $ttext);

	if (preg_match('/#MOTD_RAND#/', $ttext, $matches) != 0) {
		srand((double)microtime()*1000000);
		$rnd = rand(0,count($ws_motd)-1);
		$ttext = str_replace($matches[0], $ws_motd[$rnd], $ttext);
	}

	if (preg_match('/#MOTD_\d+#/', $ttext, $matches) != 0 ) {
		if (preg_match('/\d+/', $matches[0], $newmatches) != 0) {
			$ttext = str_replace($matches[0], $ws_motd[$newmatches[0]], $ttext);
		}
	}

	$tbox = ImageTTFBBox($me_fontsize, 0, $me_font, $ttext);
	$th = $tbox[1] - $tbox[5];
	$tw = $tbox[4] - $tbox[0];
	$btext = preg_replace('/#NAME#/', $me_nick, $me_botmsg);
	$btext = preg_replace('/#DATE#/', $thedatetime , $btext);
	if (preg_match('/#MOTD_RAND#/', $btext, $matches) != 0) {
		srand((double)microtime()*1000000);
		$rnd = rand(0, count($ws_motd)-1);
		$btext = str_replace($matches[0], $ws_motd[$rnd], $btext);
	}

	if (preg_match('/#MOTD_\d+#/', $btext, $matches) != 0 ) {
		if (preg_match('/\d+/', $matches[0], $newmatches) != 0) {
			$btext = str_replace($matches[0], $ws_motd[$newmatches[0]], $btext);
		}
	}

	$bbox = ImageTTFBBox($me_fontsize, 0, $me_font, $btext);
	$bh = $bbox[1] - $bbox[5];
	$bw = $bbox[4] - $bbox[0];

#	Now work out the size of the box we need to put it all in
#	Height :- hborder + ((height of thumb + hborder) * rows) + height of ttext + height of btext)
	$new_h = $me_hborder + (($me_height + $me_hborder) * $me_rows) + $th + $bh + $me_hborder;
#echo "hborder is $me_hborder\n";
#echo "height h is $me_height\n";
#echo "th is $th\n";
#echo "bh is $bh\n";
#echo "new h is $new_h\n";
#exit;
	$new_h = intval(($new_h+1)/2)*2;
#	Width  :- (width of thumb * number of thumbs) + ((number of thumbs - 1) * padding) + (border * 2).
#		  Then check if it's smaller than the width we need for the text.
	$new_w = $me_width * $me_num + ($me_padding * ($me_num - 1)) + (2 * $me_wborder) + $me_space;
	$new_w = intval(($new_w+1)/2)*2;
	if ( ($tw + (2 * $me_wborder)) > $new_w) {
		$ob = $me_wborder;
		$me_wborder = intval(($tw + (2 * $ob) - $new_w) / 2);
		if ($ob > $me_wborder) $me_wborder = $ob;
		$new_w = $tw + (2 * $ob);
	}
	if ( ($bw + (2 * $me_wborder)) > $new_w) {
		$ob = $me_wborder;
		$me_wborder = intval(($bw + (2 * $ob) - $new_w) / 2);
		if ($ob > $me_wborder) $me_wborder = $ob;
		$new_w = $bw + (2 * $ob);
	}
	$im2 = ImageCreateTrueColor($new_w, $new_h);

#	Create some colours
	$int = hexdec($me_bgcol);
	$arr = array('red' => 0xFF & ($int >> 0x10), 'green' => 0xFF & ($int >> 0x8), 'blue' => 0xFF & $int);
	$bgcol = ImageColorAllocate($im2, $arr['red'], $arr['green'], $arr['blue']);
	$int = hexdec($me_fontcol);
	$arr = array('red' => 0xFF & ($int >> 0x10), 'green' => 0xFF & ($int >> 0x8), 'blue' => 0xFF & $int);
	$fontcol = ImageColorAllocate($im2, $arr['red'], $arr['green'], $arr['blue']);

#	Now create the empty box with our bgcol
	ImageFill($im2, 0, 0, $bgcol);

#	Now put the top text in
	$th2 = intval($th / 2) + 3;
	$xoff = $me_wborder + $me_space;
	$me_topalign = strtolower($me_topalign);
	if ($me_topalign == 'r')
		$xoff = $new_w - $me_wborder - $tw;
	if ($me_topalign == 'c')
		$xoff = $me_space + (($new_w - $me_space) / 2) - ($tw / 2);
	ImageTTFText($im2, $me_fontsize, 0, $xoff, $me_hborder + $th2, $fontcol, $me_font, $ttext);

#	Now put the bottom text in
	$bh2 = intval($bh / 2) + 3;
	$xoff = $me_wborder + $me_space;
	$me_botalign = strtolower($me_botalign);
	if ($me_botalign == 'r')
		$xoff = $new_w - $me_wborder - $bw;
	if ($me_botalign == 'c')
		$xoff = $me_space + (($new_w - $me_space) / 2) - ($bw / 2);

	$tvo = ($me_height + $me_hborder) * $me_rows + $me_hborder + $th + $bh2 + ($me_hborder / 2);
	#ImageTTFText($im2, $me_fontsize, 0, $xoff, $me_hborder + $th + $me_height + $bh2 + $me_hborder, $fontcol, $me_font, $btext);
	ImageTTFText($im2, $me_fontsize, 0, $xoff, $tvo, $fontcol, $me_font, $btext);

	//DJ Doena
	if ($me_createmap) {
		$imagemap ="<map id=\"$me\" name=\"$me\">";
	}
	//DJ Doena end

#	Now add the thumbnails
	$tcount = 0;
	$crow = 0;
	$sql8 = $db->sql_query($cmd) or die($db->sql_error());
	//DJ Doena
	$addedPictures = 0;
	//DJ Doena end
	$LastIDWatched = '';
	while ($row = $db->sql_fetch_array($sql8)) {
		//DJ Doena
		if ($me_excludetag != '') {
			$tagcmd = "SELECT COUNT(*) as cnt FROM $DVD_TAGS_TABLE WHERE id = '$row[id]' AND name = '$me_excludetag'";
			$tagsql = $db->sql_query($tagcmd) or die($db->sql_error());
			$tagrow = $db->sql_fetch_array($tagsql);
			if($tagrow['cnt'] != 0) {
				continue;
			}
		}
		if ($me_includetag != '') {
			$tagcmd = "SELECT COUNT(*) as cnt FROM $DVD_TAGS_TABLE WHERE id = '$row[id]' AND name = '$me_includetag'";
			$tagsql = $db->sql_query($tagcmd) or die($db->sql_error());
			$tagrow = $db->sql_fetch_array($tagsql);
			if($tagrow['cnt'] == 0) {
				continue;
			}
		}
		if(is_array($me_includetags)) {
			$tagcmd = "SELECT COUNT(*) as cnt FROM $DVD_TABLE ot WHERE id = '$row[id]'";
			foreach($me_includetags as $includetag) {
				$tagcmd .= "AND EXISTS (SELECT id FROM $DVD_TAGS_TABLE it WHERE it.id = ot.id AND it.name = '$includetag')";
			}
			//echo("<br>" . $tagcmd);
			$tagsql = $db->sql_query($tagcmd) or die($db->sql_error());
			$tagrow = $db->sql_fetch_array($tagsql);
			//echo(" ==> " . $tagrow['cnt']);
			if($tagrow['cnt'] == 0) {
				continue;
			}
		}
		//DJ Doena end

		if ($CullDupsFromWatched && ($LastIDWatched == $row['id']))
			continue;
		$LastIDWatched = $row['id'];

		if ($tcount % $me_num == 0) {
			$ic = 0;
			$voffset = $me_hborder + $th + ($crow * ($me_height + $me_hborder));
			$crow ++;
		}

		$tcount++;

# 		New code for caching of the thumbnails
		$me_updating = $me;
		$filename = resize_jpg($row, 'f', $me_width, $me_quality, $me_height, $me_bgcol);
#		Wow was that it! Much smaller than before

		$imagedata = getimagesize($filename);
		$w = $imagedata[0];
		$h = $imagedata[1];
		$image = ImageCreateFromJPEG($filename);
		$hoffset = $me_wborder+$me_space+($ic*$me_width)+($ic*$me_padding);
# this is where I need to add the code to position non standard images correctly.
		$ir = $h / $w;
		$tr = $me_height / $me_width;

		//DJ Doena
		if($me_createmap) {
			$imagemap .= "\n<area shape=\"rect\"";
			$imagemap .= " coords=\"$hoffset,$voffset,".($hoffset + $w).",".($voffset+$h)."\"";
			$imagemap .= " href=\"$me_maptoprofilerpath?lastmedia=$row[id]\"";
			if((strpos($me, "_en") !== false) && ($row['originaltitle'] != '')) {
				$formattedTitle = htmlentities($row['originaltitle']);
			}
			else {
				$formattedTitle = htmlentities($row['title']);
			}
			if($me_mapwithdate) {
				$formattedTitle .= ' (' . fix88595(ucwords(strftime($me_datefmt, strtotime($row['timestamp'])))) . ')';
			}
			$imagemap .= " alt=\"$formattedTitle\" title=\"$formattedTitle\" />";
		}
		//DJ Doena end

		$ih = $me_height;
		$iho = 0;
		if ( $ir < $tr ) {
			$ih = $me_width * $ir;
			$iho = ($me_height - $ih) / 2;
		}

		ImageCopyResampled ($im2, $image, $hoffset, $voffset + $iho, 0, 0, $me_width, $ih, $w, $h);
		ImageDestroy($image);	// fjw added - plug memory leak
		$ic++;
		//DJ Doena
		$addedPictures++;
		if($addedPictures == $mt) {
			break;
		}
		//DJ Doena end
	}
	$db->sql_freeresult($sql8);
	$filename = '';
	if ( $action ) {
		$filename = $imagecachedir . $me . '.jpg';
		@unlink($filename);
	}
	else {
		SendNoCacheHeaders('Content-Type: image/jpeg');
	}
	ImageJPEG($im2, $filename, $me_quality);
	ImageDestroy($im2);	// fjw added plug memory leak

  //DJ Doena
  # code for imagemap
  if($me_createmap) {
    $imagemap .= "\n</map>\n";
    $imagemapfile = $imagecachedir . $me . '.map';
    $fh = fopen($imagemapfile, 'w') or die("can't open file");
    fwrite($fh, $imagemap);
    fclose($fh);
  }
  # end code

	return;
}
