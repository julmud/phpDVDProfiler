<?php
$rssver = '0.6.4';  // Versionnumber

# Version 0.6.x created by John Bartlett (jbartlett@strangejourney.net)

# version 0.6.4
#		- Added Upcoming Releases type (type=upcomingreleases)
#		- Reworked how the passed in URL flags were being passed around on the URL
#		- Fixed PHP script warnings
#		- Used CDATA for all URL and title fields to standardize URL's in the script and prevent against any RSS
#		  forbidden characters showing up in the title breaking the feed
#		- Added "noimage" URL flag to override the $rss_thumbnail setting to not return thumbnails
#		- Changed "type" default to "all" instead of "watched"
#		- filter=all was returning ordered and wishlist profiles, updated to only return owned profiles.
#		- Added description for the "type" being displayed
#		- Added Released Date to profile information
#		- Suppress display of Purchase Date if it's set to December 31, 1969 or November 30, 1999 - or if the
#		  Release Date is in the future
#		- iPhone: If the noimage URL flag is used, the media type image is displayed instead of a thumbnail.


# version 0.6.3
#		- Added filters.  Passing in "filter=list" on the URL will display a list
#		- Added new type support for filters: rating, genre, cast - takes in the "caid" value, crew - takes in the
#		  "caid" value, feature, audiocontent, audioformat, subtitle, studio, loaned
#		- Optimized SQL so that the NoRSS tag is utilized in the SQL to return a list of DVDs instead of performing
#		  a single query for each DVD in the display loop to see if it has the NoRSS tag assigned
#		- Added "adult" URL flag.
#		- iPhone: If "&filter=" exists on the URL, "Filters" will be displayed at the bottom of the main page

# version 0.6.2
#		- Type "all" now returns all profiles
#		- Added types "owned", "ordered", and "wishlist"
#		- iPhone: If there are any profiles set to ordered or wish list, links to view them will be listed under the
#		  alpha breakdown

# version 0.6.1
#		- Added "NoRSS" tag to exclude profiles with it set
#		- iPhone: On initial view, you are presented with list of profiles broken down into groups of no more than
#		  150 (configurable via $pagebreak)
#		- iPhone: The front cover thumbnail image is used in the RSS for optimal viewing
#		- iPhone: The grouping is based on the sort title


# version 0.6
#		- Added URL parameter "iphone" - if it exists on the URL, the full overview is returned & the "pubdate" is
#		  omitted to prevent the MAC RSS Reader from sorting by purchase date
#		- Added URL parameter "direct" - if it exists in the URL, all links point directly to the detailed view and
#		  will not display the left-side menu frame.  Automatically set if URL parameter "iphone" is used.
#		- Added an "all" type to display all DVDs sorted by the sort title

# version 0.5.x
#		- Optimized NoRSS SQL
#		- Added filters
#		- Added adult filter toggle

# Version 0.4.1 16th September 2007
#		- Version for DVD Profiler 3.1 (mutiple Boxsets)
# Version 0.4, 3rd August 2007
#		- added thumbnail (that was an idea of David)
# Version 0.3, 28th July 2007:
#		- Fixed broken special characters.
#		- added feed logo
# Version 0.2, 27th May 2007:
#		- title link opens the whole frameset now.
#		- added shortened Overview.
#		- added Hybrid DVD (thanks to Fred)

define('IN_SCRIPT', 1);

################ RSS DEFAULT VALUES ################
$rss_allow_rss = 1;
$rss_logo_url = "";
$rss_feeddescription = "DVDs I've bought recently...";
$rss_overview_length = 100;
$rss_items = 10;
$rss_thumbnail = 1;
$rss_thumbnail_width = 60;
$rss_report_leafs = true;
################ RSS DEFAULT VALUES ################

include_once('global.php');

if ($rss_allow_rss != 1) exit("Sorry, the RSS feed is not activated.");

$PersistURL="";
$PersistURL2="";

$iPhone="";
$iPhone2="";
if (isset($_GET['iphone'])) {
	$iPhone="&amp;iphone=";
	$iPhone2="&iphone=";
	$PersistURL.="&iphone=";
}

$AdultFilter="";
if (isset($_GET['adult'])) {
	$AdultFilter="AND isadulttitle=0";
	$PersistURL.="&adult=";
}

$filter="NA";
if (isset($_GET['filter'])) $filter = $_GET['filter'];

$noimage="N";
if (isset($_GET['noimage'])) {
	$noimage="Y";
	$PersistURL.="&noimage=";
}

$logo = "";
if (isset($rss_logo_url) && !empty ($rss_logo_url)) {
	$logo = 	"	<image>\n";
	if (isset($collectionurl) && !empty($collectionurl))
		$logo .="		<url><![CDATA[".$rss_logo_url."]]></url>\n";
	$logo .= 	"		<title><![CDATA[".$CurrentSiteTitle."]]></title>\n";
	$logo .= 	"		<link><![CDATA[".$collectionurl."]]></link>\n";
	$logo .= 	"	</image>\n";
}

$MySQLHasSubQueries = false;
if (($ver=MySQLVersion()) !== false) {
	list($major, $minor, $patch) = explode('.', $ver);
	if ($major > 4 || ($major == 4 && $minor >= 1)) {
		$MySQLHasSubQueries = true;
	}
}
$RemoveNoRSSTagged = "(SELECT COUNT(*) FROM $DVD_TAGS_TABLE WHERE id = dvd.id AND fullyqualifiedname='NoRSS') = 0 AND ";
if (!$MySQLHasSubQueries) {
	$RemoveNoRSSTagged = '';
	$result = $db->sql_query("SELECT DISTINCT id FROM $DVD_TAGS_TABLE WHERE fullyqualifiedname='NoRSS'");
	$tmp = '(';
	while ($zzz = $db->sql_fetch_array($result)) {
		if ($tmp != '(') $tmp .= ',';
		$tmp .= "'$zzz[id]'";
	}
	$db->sql_freeresult($result);
	if ($tmp != '(') {
		$RemoveNoRSSTagged = "dvd.id NOT IN $tmp) AND ";
	}
}

// Display Filters
if ($filter <> "NA" && $filter <> "") {

	header('Content-Type: application/rss+xml');
	header('Content-Encoding: ISO-8859-1');
	echo '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n";
	?>
	<rss version="2.0">
	<!-- phpDVDProfiler RSS Version <?php echo $rssver; ?> -->
	<channel>
		<title><![CDATA[<?php echo $CurrentSiteTitle; ?>]]></title>
	<?php if (isset($collectionurl) && !empty($collectionurl)) {?>
		<link><![CDATA[<?php echo $collectionurl; ?>]]></link>
	<?php }
	echo $logo;

	switch ($filter) {
	case 'list':
		$tmp=array('Rating','Genre','Cast','Crew','Features','Audio Content','Audio Format','Subtitles','Studios','Loaned');
		$count=count($tmp);
		echo "<description>Filters</description>";
		for ($loop = 0; $loop < $count; $loop++) {
			echo "<item>";
				echo "<title><![CDATA[" . $tmp[$loop] . "]]></title>";
				echo "<link><![CDATA[" . $collectionurl . "rss.php?filter=" . $tmp[$loop] . $PersistURL . "]]></link>";
			echo "</item>";
		}
		break;

	case 'Rating':
		echo "<description>Ratings</description>";
		$sql="SELECT DISTINCT rating, count(*) AS cnt FROM $DVD_TABLE dvd $RemoveNoRSSTagged collectiontype='owned' $AdultFilter GROUP BY rating ORDER BY rating";
		$result=$db->sql_query($sql);
		while ($tmp = $db->sql_fetch_array($result)) {
			echo "<item>";
				echo "<title><![CDATA[" . $tmp['rating'] . " (" . $tmp['cnt'] . ")]]></title>";
				echo "<link><![CDATA[" . $collectionurl . "rss.php?type=rating&rating=" . $tmp['rating'] . $PersistURL . "]]></link>";
			echo "</item>";
		}
		break;

	case 'Genre':
		echo "<description>Genre</description>";
// now there can be custom genres ...
//		$tmp=array('Accessories','Action','Adult','Adventure','Animation','Classic','Comedy','Documentary','Drama','Family','Fantasy','Horror','Martial Arts','Music','Musical','Romance','Science-Fiction','Special Interest','Sports','Suspense/Thriller','Television','War','Western');
		$sql="SELECT DISTINCT genre FROM $DVD_GENRE_TABLE";
		$result=$db->sql_query($sql);
		$tmp=array();
		while ($zzz = $db->sql_fetch_array($result)) {
			$tmp[] = $zzz['genre'];
		}
		$db->sql_freeresult($result);
		$count=count($tmp);
		for ($loop = 0; $loop < $count; $loop++) {
			if (($tmp[$loop] == 'Adult' && $AdultFilter != '') || $tmp[$loop] != 'Adult') {
				$sql="SELECT count(*) AS cnt FROM $DVD_TABLE dvd JOIN $DVD_GENRE_TABLE gen ON dvd.id=gen.id WHERE $RemoveNoRSSTagged genre='" . $tmp[$loop] . "' AND collectiontype='owned' " . $AdultFilter;
				$result=$db->sql_fetch_array($db->sql_query($sql));
				if ($result['cnt'] > 0) {
					echo "<item>";
						echo "<title><![CDATA[" . $tmp[$loop] . " (" . $result['cnt'] . ")]]></title>";
						echo "<link><![CDATA[" . $collectionurl . "rss.php?type=genre&genre=" . $tmp[$loop] . $PersistURL . "]]></link>";
					echo "</item>";
				}
			}
		}
		break;

	case 'Cast':
		echo "<description>Cast</description>";
		for ($loop = 65; $loop <= 90; $loop++) {
			$sql="select count(*) as cnt from $DVD_COMMON_ACTOR_TABLE where lastname like '" . chr($loop) . "%' OR (lastname='' AND firstname like '" . chr($loop) . "%') " . $AdultFilter;
			$result=$db->sql_fetch_array($db->sql_query($sql));
			if ($result['cnt'] > 0) {
				echo "<item>";
					echo "<title><![CDATA[" . chr($loop) . "]]></title>";
					echo "<link><![CDATA[" . $collectionurl . "rss.php?filter=castlist&alpha=" . chr($loop) . $PersistURL . "]]></link>";
				echo "</item>";
			}
		}
		break;

	case 'castlist':
		$sql="SELECT fullname, birthyear FROM $DVD_COMMON_ACTOR_TABLE WHERE lastname like '" . $db->sql_escape($_GET['alpha']) . "%' OR (lastname='' AND firstname like '" . $db->sql_escape($_GET['alpha']) . "%') " . $AdultFilter . " ORDER BY fullname";
		$result=$db->sql_query($sql);
		echo "<description>Cast List - " . $_GET['alpha'] . "</description>";
		while ($tmp = $db->sql_fetch_array($result)) {
			$by="";
			if ($tmp['birthyear'] != 0) $by=" (" . $tmp['birthyear'] . ")";
			echo "<item>";
				echo "<title><![CDATA[" . $tmp['fullname'] . $by . "]]></title>";
				echo "<link><![CDATA[" . $collectionurl . "rss.php?type=cast&fullname=" . rawurlencode($tmp['fullname']) . "&birthyear=" . $tmp['birthyear'] . "]]></link>";
			echo "</item>";
		}
		break;

	case 'Crew':
		echo "<description>Crew</description>";
		for ($loop = 65; $loop <= 90; $loop++) {
			$sql="select count(*) as cnt from $DVD_COMMON_CREDITS_TABLE where lastname like '" . chr($loop) . "%' OR (lastname='' AND firstname like '" . chr($loop) . "%') " . $AdultFilter;
			$result=$db->sql_fetch_array($db->sql_query($sql));
			if ($result['cnt'] > 0) {
				echo "<item>";
					echo "<title><![CDATA[" . chr($loop) . "]]></title>";
					echo "<link><![CDATA[" . $collectionurl . "rss.php?filter=crewlist&alpha=" . chr($loop) . $PersistURL . "]]></link>";
				echo "</item>";
			}
		}
		break;

	case 'crewlist':
		$sql="SELECT fullname, birthyear FROM $DVD_COMMON_CREDITS_TABLE WHERE lastname like '" . $db->sql_escape($_GET['alpha']) . "%' OR (lastname='' AND firstname like '" . $db->sql_escape($_GET['alpha']) . "%') " . $AdultFilter . " ORDER BY fullname";
		$result=$db->sql_query($sql);
		echo "<description>Crew List - " . $_GET['alpha'] . "</description>";
		while ($tmp = $db->sql_fetch_array($result)) {
			$by="";
			if ($tmp['birthyear'] != 0) $by=" (" . $tmp['birthyear'] . ")";
			echo "<item>";
				echo "<title><![CDATA[" . $tmp['fullname'] . $by . "]]></title>";
				echo "<link><![CDATA[" . $collectionurl . "rss.php?type=crew&fullname=" . rawurlencode($tmp['fullname']) . "&birthyear=" . $tmp['birthyear'] . $PersistURL . "]]></link>";
			echo "</item>";
		}
		break;

	case 'Features':
		$tmp1=array('Scene Access','Play All','Trailers','Featurettes','Commentary','Deleted Scenes','Interviews','Outtakes/Bloopers','Storyboard Comparisons','Gallery','Production Notes/Bios','DVD-ROM Content','Interactive Game','Multi-Angle','Music Videos','THX Certified','Closed Captioned');
		$tmp2=array('sceneaccess','playall','trailer','makingof','commentary','deletedscenes', 'interviews','outtakes','storyboardcomparisons','photogallery','productionnotes','dvdromcontent','game','multiangle','musicvideos','thxcertified','closedcaptioned');
		$count=count($tmp1);
		echo "<description>Features</description>";
		for ($loop = 0; $loop < $count; $loop++) {
			echo "<item>";
				echo "<title><![CDATA[" . $tmp1[$loop] . "]]></title>";
				echo "<link><![CDATA[" . $collectionurl . "rss.php?type=feature&feature=" . $tmp2[$loop] . $PersistURL . "]]></link>";
			echo "</item>";
		}
		break;

	case 'Audio Content':
// subselect seems to be simply removing non-owned and no-audiocontent profiles - Replace with inner join
//		$sql="SELECT DISTINCT audiocontent FROM $DVD_AUDIO_TABLE aud WHERE (select count(*) FROM $DVD_TABLE WHERE id=aud.id AND collectiontype='owned') > 0 ORDER BY audiocontent";
		$sql="SELECT DISTINCT audiocontent FROM $DVD_TABLE d,$DVD_AUDIO_TABLE aud WHERE d.id=aud.id AND collectiontype='owned' ORDER BY audiocontent";
		$result=$db->sql_query($sql);
		$otherflag=0;
		echo "<description>Audio Content</description>";
		while ($tmp = $db->sql_fetch_array($result)) {
			if ($tmp['audiocontent'] == 'Other') {
				$otherflag=1;
			} else {
				echo "<item>";
					echo "<title><![CDATA[" . $tmp['audiocontent'] . "]]></title>";
					echo "<link><![CDATA[" . $collectionurl . "rss.php?type=audiocontent&audiocontent=" . $tmp['audiocontent'] . $PersistURL . "]]></link>";
				echo "</item>";
			}
		}
		if ($otherflag == 1) {
			echo "<item>";
				echo "<title>Other</title>";
				echo "<link><![CDATA[" . $collectionurl . "rss.php?type=audiocontent&audiocontent=Other" . $PersistURL . "]]></link>";
			echo "</item>";
		}
		break;

	case 'Audio Format':
// subselect seems to be simply removing non-owned and no-audioformat profiles - Replace with inner join
//		$sql="SELECT DISTINCT audioformat FROM $DVD_AUDIO_TABLE aud WHERE (select count(*) FROM $DVD_TABLE WHERE id=aud.id AND collectiontype='owned') > 0 ORDER BY audioformat";
		$sql="SELECT DISTINCT audioformat FROM $DVD_TABLE d,$DVD_AUDIO_TABLE aud WHERE d.id=aud.id AND collectiontype='owned' ORDER BY audioformat";
		$result=$db->sql_query($sql);
		$otherflag=0;
		echo "<description>Audio Format</description>";
		while ($tmp = $db->sql_fetch_array($result)) {
			if ($tmp['audioformat'] == 'Other') {
				$otherflag=1;
			} else {
				echo "<item>";
					echo "<title><![CDATA[" . $tmp['audioformat'] . "]]></title>";
					echo "<link><![CDATA[" . $collectionurl . "rss.php?type=audioformat&audioformat=" . $tmp['audioformat'] . $PersistURL . "]]></link>";
				echo "</item>";
			}
		}
		if ($otherflag == 1) {
			echo "<item>";
				echo "<title>Other</title>";
				echo "<link><![CDATA[" . $collectionurl . "rss.php?type=audioformat&audioformat=Other" . $PersistURL . "]]></link>";
			echo "</item>";
		}
		break;

	case 'Subtitles':
// subselect seems to be simply removing non-owned and no-subtitle profiles - Replace with inner join
//		$sql="SELECT DISTINCT subtitle FROM $DVD_SUBTITLE_TABLE sub WHERE (select count(*) FROM $DVD_TABLE WHERE id=sub.id AND collectiontype='owned') > 0 ORDER BY subtitle";
		$sql="SELECT DISTINCT subtitle FROM $DVD_TABLE d,$DVD_SUBTITLE_TABLE sub WHERE d.id=sub.id AND collectiontype='owned' ORDER BY subtitle";
		echo $sql;
		$result=$db->sql_query($sql);
		$otherflag=0;
		echo "<description>Subtitles</description>";
		while ($tmp = $db->sql_fetch_array($result)) {
			if ($tmp['subtitle'] == 'Other') {
				$otherflag=1;
			} else {
				echo "<item>";
					echo "<title><![CDATA[" . $tmp['subtitle'] . "]]></title>";
					echo "<link><![CDATA[" . $collectionurl . "rss.php?type=subtitle&subtitle=" . $tmp['subtitle'] . $PersistURL . "]]></link>";
				echo "</item>";
			}
		}
		if ($otherflag == 1) {
			echo "<item>";
				echo "<title>Other</title>";
				echo "<link><![CDATA[" . $collectionurl . "rss.php?type=subtitle&subtitle=Other" . $PersistURL . "]]></link>";
			echo "</item>";
		}
		break;

	case 'Studios':
// subselect seems to be simply removing non-owned and no-studio profiles - Replace with inner join
//		$sql="SELECT DISTINCT studio FROM $DVD_STUDIO_TABLE stud WHERE (select count(*) FROM $DVD_TABLE WHERE id=stud.id AND collectiontype='owned') > 0 ORDER BY studio";
		$sql="SELECT DISTINCT studio FROM $DVD_TABLE d,$DVD_STUDIO_TABLE stud d.id=stud.id AND collectiontype='owned' ORDER BY studio";
		$result=$db->sql_query($sql);
		echo "<description>Studios</description>";
		while ($tmp = $db->sql_fetch_array($result)) {
			echo "<item>";
				echo "<title><![CDATA[" . $tmp['studio'] . "]]></title>";
				echo "<link><![CDATA[" . $collectionurl . "rss.php?type=studio&studio=" . rawurlencode(trim($tmp['studio'])) . $PersistURL . "]]></link>";
			echo "</item>";
		}
		break;

	case 'Loaned':
		$sql="SELECT DISTINCT loaninfo, count(*) AS cnt FROM $DVD_TABLE dvd WHERE loaninfo<>'' AND $RemoveNoRSSTagged collectiontype='owned' $AdultFilter GROUP BY loaninfo ORDER BY loaninfo";
		$result=$db->sql_query($sql);
		echo "<description>Loaned</description>";
		while ($tmp = $db->sql_fetch_array($result)) {
			echo "<item>";
				echo "<title><![CDATA[" . $tmp['loaninfo'] . " (" . $tmp['cnt'] . ")]]></title>";
				echo "<link><![CDATA[" . $collectionurl . "rss.php?type=loaned&loaned=" . rawurlencode(trim($tmp['loaninfo'])) . $PersistURL . "]]></link>";
			echo "</item>";
		}
		break;

	} // end switch

	echo "</channel></rss>";

	exit();
}











// Define the max number of items per group when displayed on an iPhone/iTouch
$pagebreak=150;

$type="watched";
if (isset($_GET['iphone'])) $type='all';
if (isset($_GET['type'])) $type=$_GET['type'];

if ($iPhone <> "" && !isset($_GET['asql']) && $type == "all" && ($filter == "" || $filter == "NA")) {

	// Compute out the page breakdown
	$sqlA="SELECT count(*) AS cnt FROM $DVD_TABLE WHERE collectiontype='owned' $AdultFilter AND sorttitle LIKE '";
	$sqlB="SELECT count(*) AS cnt FROM $DVD_TABLE LEFT JOIN $DVD_TAGS_TABLE ON $DVD_TABLE.id = $DVD_TAGS_TABLE.id WHERE collectiontype='owned' $AdultFilter AND fullyqualifiedname='NoRSS' AND sorttitle LIKE '";
	$sqlC="SELECT count(*) AS cnt FROM $DVD_TABLE WHERE collectiontype='ordered' $AdultFilter";
	$sqlD="SELECT count(*) AS cnt FROM $DVD_TABLE WHERE collectiontype='wishlist' $AdultFilter";

	$orderedresult=$db->sql_fetch_array($db->sql_query($sqlC));
	$wishlistresult=$db->sql_fetch_array($db->sql_query($sqlD));
	$orderedcnt=$orderedresult['cnt'];
	$wishlistcnt=$wishlistresult['cnt'];

	// Query the database to get the number of profiles for each letter
	$pos=0;
	for ($loop = 65; $loop <= 90; $loop += 1) {
		$dvdcntA = $db->sql_fetch_array($db->sql_query("$sqlA" . chr($loop) . "%'"));
		$dvdcntB = $db->sql_fetch_array($db->sql_query("$sqlB" . chr($loop) . "%'"));
		$alpha[$pos] = $dvdcntA['cnt'] - $dvdcntB['cnt'];
		$pos=$pos + 1;
	}

	$pos=0;
	$cnt=$alpha[0];
	$page[0]="A-";
	$alphasql[0] = "A";
	$inc=1;
	// Determine sets
	for ($loop = 66; $loop <= 90; $loop += 1) {
		if ($cnt + $alpha[$inc] > $pagebreak) {
			$page[$pos].=chr($loop - 1);
			$pos=$pos + 1;
			$page[$pos]=chr($loop) . "-";
			$alphasql[$pos]="";
			$cnt=0;
		}
		$cnt=$cnt + $alpha[$inc];
		$alphasql[$pos].=chr($loop);
		$inc=$inc + 1;
	}
	$page[$pos]=$page[$pos] . "Z";


	header('Content-Type: application/rss+xml');
	header('Content-Encoding: ISO-8859-1');
	echo '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n";
	?>
	<rss version="2.0">
	<!-- phpDVDProfiler RSS Version <?php echo $rssver; ?> -->
	<channel>
		<title><?php echo $CurrentSiteTitle; ?></title>
	<?php if (isset($collectionurl) && !empty($collectionurl)) {?>
		<link><?php echo $collectionurl; ?></link>
	<?php } ?>
		<description></description>
	<?php
	echo $logo;

	echo "<item>\n";
		echo "<title>Profiles 0-9</title>\n";
		echo "<link><![CDATA[" . $collectionurl . "rss.php?asql=0123456789" . $PersistURL . "]]></link>\n";
	echo "</item>\n";

	for ($loop = 0; $loop <= $pos; $loop += 1) {
		echo "<item>\n";
			echo "<title>Profiles " . $page[$loop] . "</title>\n";
			echo "<link><![CDATA[" . $collectionurl . "rss.php?asql=" . $alphasql[$loop] . $PersistURL . "]]></link>\n";
		echo "</item>\n";
	}

	if ($orderedcnt > 0) {
		echo "<item>\n";
			echo "<title>Ordered</title>\n";
			echo "<link><![CDATA[" . $collectionurl . "rss.php?type=ordered" . $PersistURL . "]]></link>\n";
		echo "</item>\n";
	}

	if ($wishlistcnt > 0) {
		echo "<item>\n";
			echo "<title>Wishlist</title>\n";
			echo "<link><![CDATA[" . $collectionurl . "rss.php?type=wishlist" . $PersistURL . "]]></link>\n";
		echo "</item>\n";
	}

	echo "<item>";
		echo "<title>Upcoming Releases</title>\n";
		echo "<link><![CDATA[" . $collectionurl . "rss.php?type=upcomingreleases" . $PersistURL . "]]></link>\n";
	echo "</item>\n";

	if ($filter == "") {
		echo "<item>\n";
			echo "<title>Filters</title>\n";
			echo "<link><![CDATA[" . $collectionurl . "rss.php?filter=list" . $PersistURL . "]]></link>\n";
		echo "</item>\n";
	}

	echo "</channel></rss>\n";

	if (!isset($_GET['alphasql'])) exit();
}


if ($rss_report_leafs)
	$rss_report_condition = "boxchild = 0";
else
	$rss_report_condition = "boxparent = ''";



	$alphasql="";
	$alpha="";
	if (isset($_GET['asql'])) $alpha=$_GET['asql'];
	if ($alpha != "") {
		$alphasql="(sorttitle LIKE '" . substr($alpha,0,1) . "%'";
		for ($loop=1; $loop < strlen($alpha); $loop += 1) {
			$alphasql.= " OR sorttitle LIKE '" . substr($alpha,$loop,1) . "%'";
		}
		$alphasql.=")";
	}

	$fieldlist="dvd.id, upc, builtinmediatype, custommediatype, title, description, overview, collectionnumber, rating, productionyear, purchasedate, purchaseplace, released";
	switch ($type) {
	case 'all':
		$rss_feeddescription = "Owned DVDs";
		$sql = "SELECT $fieldlist FROM $DVD_TABLE dvd WHERE $RemoveNoRSSTagged 1 $AdultFilter";
		if ($alphasql != "") $sql.=" AND " . $alphasql;
		$sql.=" ORDER BY sorttitle";
		break;

	case 'owned':
		$rss_feeddescription = "Owned DVDs";
		$sql = "SELECT $fieldlist FROM $DVD_TABLE dvd WHERE $RemoveNoRSSTagged collectiontype='owned' $AdultFilter";
		if ($alphasql != "") $sql.=" AND " . $alphasql;
		$sql.=" ORDER BY sorttitle";
		break;

	case 'ordered':
		$rss_feeddescription = "Ordered DVDs";
		$sql = "SELECT $fieldlist FROM $DVD_TABLE dvd WHERE $RemoveNoRSSTagged collectiontype='ordered' $AdultFilter";
		if ($alphasql != "") $sql.=" AND " . $alphasql;
		$sql.=" ORDER BY sorttitle";
		break;

	case 'wishlist':
		$rss_feeddescription = "DVD Wishlist";
		$sql = "SELECT $fieldlist FROM $DVD_TABLE dvd WHERE $RemoveNoRSSTagged collectiontype='wishlist' $AdultFilter";
		if ($alphasql != "") $sql.=" AND " . $alphasql;
		$sql.=" ORDER BY sorttitle";
		break;

	case 'rating':
		$rss_feeddescription = "DVDs filtered by Rating: ". $_GET['rating'];
		$sql = "SELECT $fieldlist FROM $DVD_TABLE dvd WHERE $RemoveNoRSSTagged rating='$_GET[rating]' $AdultFilter AND collectiontype='owned' ORDER BY sorttitle";
		break;

	case 'genre':
		$rss_feeddescription = "DVDs filtered by Genre: ". $_GET['genre'];
		$sql = "SELECT $fieldlist FROM $DVD_TABLE dvd JOIN $DVD_GENRE_TABLE gen ON dvd.id=gen.id WHERE $RemoveNoRSSTagged genre='$_GET[genre]' $AdultFilter AND collectiontype='owned' ORDER BY sorttitle";
		break;

	case 'cast':
		$rss_feeddescription = "DVDs filtered by Cast";
// subselect used to remove NoRSS tagged profiles **FIXME**
		$sql = "SELECT $fieldlist FROM $DVD_TABLE dvd WHERE $RemoveNoRSSTagged collectiontype='owned' $AdultFilter AND "
			."( "
			."SELECT count(*) FROM $DVD_ACTOR_TABLE a1, $DVD_COMMON_ACTOR_TABLE a2 "
			."WHERE id=dvd.id "
			."  AND a2.caid=" . $_GET['caid'] . ""
			."  AND a2.caid=a1.caid "
			.") > 0 "
			."ORDER BY sorttitle";
		break;

	case 'crew':
		$rss_feeddescription = "DVDs filtered by Crew";
// subselect used to remove NoRSS tagged profiles **FIXME**
		$sql = "SELECT $fieldlist FROM $DVD_TABLE dvd WHERE $RemoveNoRSSTagged collectiontype='owned' $AdultFilter AND "
			."( "
			."SELECT count(*) "
			."FROM $DVD_CREDITS_TABLE a1, $DVD_COMMON_CREDITS_TABLE a2 "
			."WHERE id=dvd.id "
			."  AND a2.caid=" . $_GET['caid'] . ""
			."  AND a2.caid=a1.caid "
			.") > 0 "
			."ORDER BY sorttitle";
		break;

	case 'feature':
		$rss_feeddescription = "DVDs filtered by Feature: ". $_GET['feature'];
		$sql = "SELECT $fieldlist FROM $DVD_TABLE dvd WHERE $RemoveNoRSSTagged collectiontype='owned' $AdultFilter AND feature$_GET[feature]=1 ORDER BY sorttitle";
		break;

	case 'audiocontent':
		$rss_feeddescription = "DVDs filtered by Audio Content: ". $_GET['audiocontent'];
		$sql = "SELECT DISTINCT $fieldlist FROM $DVD_TABLE dvd LEFT JOIN $DVD_AUDIO_TABLE audio ON dvd.id=audio.id WHERE $RemoveNoRSSTagged "
			."audio.audiocontent='$_GET[audiocontent]' $AdultFilter AND collectiontype='owned' ORDER BY sorttitle";
		break;

	case 'audioformat':
		$rss_feeddescription = "DVDs filtered by Audio Format: ". $_GET['audioformat'];
		$sql = "SELECT DISTINCT $fieldlist FROM $DVD_TABLE dvd LEFT JOIN $DVD_AUDIO_TABLE audio ON dvd.id=audio.id WHERE $RemoveNoRSSTagged "
			."audio.audioformat='$_GET[audioformat]' $AdultFilter AND collectiontype='owned' ORDER BY sorttitle";
		break;

	case 'subtitle':
		$rss_feeddescription = "DVDs filtered by Subtitle: ". $_GET['subtitle'];
		$sql = "SELECT DISTINCT $fieldlist FROM $DVD_TABLE dvd LEFT JOIN $DVD_SUBTITLE_TABLE sub ON dvd.id=sub.id WHERE $RemoveNoRSSTagged "
			."sub.subtitle='$_GET[subtitle]' $AdultFilter AND collectiontype='owned' ORDER BY sorttitle";
		break;

	case 'studio':
		$rss_feeddescription = "DVDs filtered by Studio: ". $_GET['studio'];
		$sql = "SELECT DISTINCT $fieldlist FROM $DVD_TABLE dvd LEFT JOIN $DVD_STUDIO_TABLE stud ON dvd.id=stud.id WHERE $RemoveNoRSSTagged "
			."stud.studio='$_GET[studio]' $AdultFilter AND collectiontype='owned' ORDER BY sorttitle";
		break;

	case 'loaned':
		$rss_feeddescription = "DVDs loaned to " . $_GET['loaned'];
		$sql = "SELECT $fieldlist FROM $DVD_TABLE dvd WHERE $RemoveNoRSSTagged loaninfo='$_GET[loaned]' $AdultFilter AND collectiontype='owned' ORDER BY sorttitle";
		break;

	case 'upcomingreleases':
		// Include releases from up to two weeks ago
		$rss_feeddescription = "Upcoming Releases";
		$sql = "SELECT $fieldlist FROM $DVD_TABLE dvd WHERE $RemoveNoRSSTagged released>=" . (time() - (14 * 24 * 60 * 60)) . $AdultFilter . " ORDER BY released, sorttitle";
		break;

	default:
		// watched
// this is not last watched; that needs to process the $DVD_EVENTS_TABLE for a particular user. This is most recent purchases
//		$rss_feeddescription = "Last watched";
		$rss_feeddescription = "Recent Purchases";
		$sql = "SELECT $fieldlist FROM $DVD_TABLE dvd WHERE $RemoveNoRSSTagged collectiontype='owned' AND $rss_report_condition $AdultFilter ORDER BY purchasedate DESC, sorttitle ASC LIMIT 0,$rss_items";
		break;
	}
	//echo "<!-- " . $sql . " -->";
	$result = $db->sql_query($sql);

	header('Content-Type: application/rss+xml');
	header('Content-Encoding: ISO-8859-1');
	echo '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n";
?>
<rss version="2.0">
<!-- phpDVDProfiler RSS Version <?php echo $rssver; ?> -->
<channel>
	<title><?php echo $CurrentSiteTitle; ?></title>
<?php if (isset($collectionurl) && !empty($collectionurl)) {?>
	<link><?php echo $collectionurl; ?></link>
<?php } ?>
	<description><?php echo $rss_feeddescription; ?></description>
<?php
	echo $logo;
	while ($dvd = $db->sql_fetch_array($result)) {

		if ($dvd['builtinmediatype'] == MEDIA_TYPE_DVD) $mediatype = 'DVD';
		if ($dvd['builtinmediatype'] == MEDIA_TYPE_HDDVD) $mediatype = 'HD-DVD';
		if ($dvd['builtinmediatype'] == MEDIA_TYPE_BLURAY) $mediatype = 'Blu-ray';
		if ($dvd['builtinmediatype'] == MEDIA_TYPE_HDDVD_DVD) $mediatype = 'DVD/HD-DVD Hybrid';
		if ($dvd['builtinmediatype'] == MEDIA_TYPE_BLURAY_DVD) $mediatype = 'DVD/Blu-ray Hybrid';
		$pupdate = date ('r', $dvd['purchasedate']);
		$releaseddate_int=intval($dvd['released']);
		$dvd['purchasedate'] = fix88595(ucwords(strftime($lang['DATEFORMAT'], $dvd['purchasedate'])));
		$dvd['released'] = fix88595(ucwords(strftime($lang['DATEFORMAT'], $dvd['released'])));
		$dvd['title'] = htmlspecialchars($dvd['title'], ENT_COMPAT, 'ISO-8859-1');

// Now overview can have html in it ... truncate if necessary, and append closing bold and italic tags just in case :)
		if (isset($rss_overview_length) && $rss_overview_length > -1) {
			if ($iPhone <> "") {
//				$dvd['overview'] = htmlspecialchars($dvd['overview'], ENT_COMPAT, 'ISO-8859-1');
			} else {
//				$dvd['overview'] = htmlspecialchars(substr($dvd['overview'], 0, $rss_overview_length), ENT_COMPAT, 'ISO-8859-1');
				$dvd['overview'] = substr($dvd['overview'], 0, $rss_overview_length).'</b></i>';
				if (isset($_GET['direct'])) {
					$dvd['overview'] .= "<a href = \"".$collectionurl."index.php?action=show&mediaid=".$dvd['id']."\">[...]</a>";
				} else {
					$dvd['overview'] .= "<a href = \"".$collectionurl."index.php?lastmedia=".$dvd['id']."\">[...]</a>";
				}
			}
		}
		else {
//			$dvd['overview'] = htmlspecialchars($dvd['overview'], ENT_COMPAT, 'ISO-8859-1');
		}

		if (!empty($dvd['description'])) {
			$dvd['description'] = htmlspecialchars($dvd['description'], ENT_COMPAT, 'ISO-8859-1');
			switch ($titledesc) {
			case 0:
				$dvd['description'] = '';
				break;
			case 1:
				$dvd['description'] = " ($dvd[description])";
				break;
			case 2:
				$dvd['description'] = ": $dvd[description]";
				break;
			case 3:
				$dvd['description'] = " - $dvd[description]";
				break;
			}
		}
		echo "	<item>\n";
		echo "		<title><![CDATA[".$dvd['title'].$dvd['description']." (".$mediatype.")]]></title>\n";
		if (isset($_GET['direct']) || $iPhone <> "") {
			echo "		<link><![CDATA[".$collectionurl."index.php?action=show&mediaid=".$dvd['id']."]]></link>\n";
		} else {
			echo "		<link>".$collectionurl."index.php?lastmedia=".$dvd['id']."</link>\n";
		}
		echo "		<category>".$mediatype."</category>\n";
		echo "		<description><![CDATA[\n";
		if ($rss_thumbnail == 1) {
			if ($noimage == "Y") {
				if ($iPhone != "") {
					if ($mediatype == 'DVD') echo "			<img src=\"".$collectionurl.$MediaTypes[MEDIA_TYPE_DVD]['Icon']."\"><br>\n";
					if ($mediatype == 'HD-DVD' || $mediatype == 'DVD/HD-DVD Hybrid') echo "			<img src=\"".$collectionurl.$MediaTypes[MEDIA_TYPE_HDDVD]['Icon']."\"><br>\n";
					if ($mediatype == 'Blu-ray' || $mediatype == 'DVD/Blu-ray Hybrid') echo "			<img src=\"".$collectionurl.$MediaTypes[MEDIA_TYPE_BLURAY]['Icon']."\"><br>\n";
				}
			} else {
				if ($iPhone<>""){
// This should use the symbols for locations
					echo "			<img src=\"$collectionurl$img_webpath/$thumbnails/$dvd[id]f.jpg\"><br>\n";
				} else {
					echo "			<img src=\"".$collectionurl.resize_jpg($dvd, 'f', $rss_thumbnail_width, 100)."\" vspace=\"2\" hspace=\"14\" border=\"1\" align=\"left\">\n";
				}
			}
		}
		echo "			<b>".$lang['RELEASEDATE'].":</b> ".$dvd['released']."<br>\n";
		$sup=0;
		if ($dvd['purchasedate'] == 'December 31, 1969') $sup=1;
		if ($dvd['purchasedate'] == 'November 30, 1999') $sup=1;
		if ($releaseddate_int > time()) $sup=1;
		if ($sup == 0) {
			echo "			<b>".$lang['PURCHASEDATE'].":</b> ".$dvd['purchasedate']."<br>\n";
		}
		echo "			<b>".$lang['PRODUCTIONYEAR'].":</b> ".$dvd['productionyear']."<br>\n";
		echo "			<b>".$lang['RATING'].":</b> ".$dvd['rating']."<br>\n";

		if ($dvd['overview'] != '' && $rss_overview_length != 0)
			echo "			<p>".$dvd['overview']."</p>\n";
		echo "		]]></description>\n";
		echo "		<guid isPermaLink=\"false\">".$dvd['id']."</guid>\n";
		if ($iPhone=="") echo "		<pubDate>".$pupdate."</pubDate>\n";
		echo "	</item>\n";
	}
DebugSQL($db, "rss");
?>
</channel>
</rss>
