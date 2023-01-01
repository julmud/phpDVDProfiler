<?php
################################################################################
# Gallery Script 2 for phpDVDProfiler  - Version 1 (24.09.2007)
# written by Thomas Fintzel (http://tfintzel.de/dvdprofiler),
#
#
# Description: 	This script provides a nice gallery of front or back covers.
#
################################################################################
#
#		Used libraries:
#		Lightbox v2.02	by Lokesh Dhakar - http://www.huddletogether.com
#
################################################################################
#   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
#	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
#	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
#	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
#	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
#	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
#	THE SOFTWARE.
################################################################################

$galleryver = "2 v1.5";

header('Content-Type: text/html; charset="windows-1252";');

function get_children($id,$bs){
	global $sql, $DVD_TABLE, $db, $plusclass, $FnExt, $FnWebPath, $img_physpath, $PHP_SELF;

	$bsql = str_replace( "boxparent = ''", "boxparent = '".$db->sql_escape($id)."'", $sql);
	$bsql = preg_replace('/(.*)(LIMIT)(.*)(,)(.*)/i', '\1', $bsql);

	$boxresult = $db->sql_query($bsql);

	while($dvd = $db->sql_fetch_array($boxresult)) {
		show_profile($dvd, $bs);
		if ($dvd['boxchild'] != 0){
			$nbs = "bs".str_replace('.', '_', $dvd['id']);
			echo "   <div class=\"".$plusclass."\" id=\"".$nbs."\">\n";
			get_children($dvd['id'],$nbs);
			echo "   </div>\n";
		}
	}
	return true;
}

function format_addinfo (&$dvd){
	GLOBAL $lang, $reviewsort, $sortby, $ReviewLabels;

	if (!isset($dvd['addinfo']) || empty($dvd['addinfo'])) {
		$dvd['addinfo'] = '';
		return(false);
	}

	switch ($sortby) {
	case 'purchasedate':
		if ($dvd['addinfo'] == '0' || empty ($dvd['addinfo']) ) {
			switch ($dvd['collectiontype']) {
			case 'wishlist':
				$dvd['addinfo'] = $lang["WISHNAME$dvd[wishpriority]"];
				break;
			case 'ordered':
				$dvd['addinfo'] = $lang['ORDERED'];
				break;
			default:
				$dvd['addinfo'] = $lang['UNKNOWN'];	# No number
				break;
			}
		}
		else {
			$dvd['addinfo'] = fix88595(ucwords(strftimeReplacement($lang['DATEFORMAT'], $dvd['addinfo'])));
		}
		break;
	case 'released':
		$dvd['addinfo'] = fix88595(ucwords(strftimeReplacement($lang['DATEFORMAT'], $dvd['addinfo'])));
		break;
	case 'collectionnumber':
		if ($dvd['addinfo'] == '0' ) {
			switch ($dvd['collectiontype']) {
			case 'wishlist':
				$dvd['addinfo'] = $lang["WISHNAME$dvd[addinfo2]"];
				break;
			case 'ordered':
				$dvd['addinfo'] = $lang['ORDERED'];
				break;
			default:
				$dvd['addinfo'] = 'keine Nummer';
				break;
			}
		}
		break;
	case 'genres':
		$trans = str_replace(array(' ', '/', '-'), '', strtoupper($dvd['addinfo']));

		if (empty($dvd['addinfo']))
			$trans = 'UNCATEGORIZED';
		$dvd['addinfo'] = $lang['GENRELIST'][$trans];
		break;
	case 'runningtime':
		$dvd['addinfo'] .= " $lang[MINUTES]";
		break;
	case 'reviews':
		for ($i=0; $i<strlen($reviewsort); $i++)
			$dvd['addinfo'] .= FormatLabel(FixAReviewValue($dvd[$ReviewLabels[$reviewsort[$i]]])/2, $lang['REVIEWNAMES'][$reviewsort[$i]], false);
		break;
	}
	return(true);
}

function show_profile(&$dvd, $bs = 'DVDs') {
	global $FnExt, $FnWebPath, $img_physpath, $getimages, $PHP_SELF, $plusgif, $DoBack, $name, $vname, $thumbnails, $imagewidth, $thumbqual;
	FormatTheTitle($dvd);
	format_addinfo ($dvd);
	if (file_exists($img_physpath.$dvd['id'].$FnExt)){
		$img = $FnWebPath.$dvd['id'].$FnExt;
	}
	else {
		$img = "gfx/unknown.jpg";
	}

	if ($getimages == 3) {
		$tn = $FnWebPath.$thumbnails."/".$dvd['id'].$FnExt;
	}
// The previous code looks to see if there is an image file (looking in the thumbnails directory) and
// failing that it uses the unknown-image jpg. The resize_jpg() code does much the same thing, but
// checks places like the imagecache and tries to use the main image if the thumb is missing.
//	elseif (($name=find_a_file($dvd['id'], !$DoBack)) != '') {
//		$tn = PhyspathToWebpath(resize_jpg($dvd, ($DoBack?'b':'f'), $imagewidth, $thumbqual));
//	}
//	else {
//		$tn = "gfx/unknown.jpg";
//	}
	else {
		$tn = PhyspathToWebpath(resize_jpg($dvd, ($DoBack?'b':'f'), $imagewidth, $thumbqual));
	}

	$boximg = $boxcode = '';

	if ($dvd['boxchild'] != 0 ){
		$nbs = "bs". str_replace('.', '_', $dvd['id']);
		$boximg = '<img src="'.$plusgif.'" onclick="dh(\''.$nbs.'\',this)" alt = "">';
		$boxcode = "   <div class = \"plusbox\">\n"
	 		    ."   ".$boximg."\n"
	 		    ."    <a href=\"".$img."\" rel=\"lightbox[".$nbs."]\" dvdlink=\"&lt;a href=&quot;$PHP_SELF?mediaid={$dvd['id']}&amp;action=show&quot;&gt;{$dvd['title']}{$dvd['addinfo']}&lt;/a&gt;\"></a>\n"
		 	    ."   </div>\n";
	}


	echo "   <div class = \"gallery\">\n".
		 "    <div class = \"picbox\">\n".
		 "     <div class = \"bigbox\">\n".
		 "      <a href=\"".$img."\" rel=\"lightbox[".$bs."]\" dvdlink=\"&lt;a href=&quot;$PHP_SELF?mediaid={$dvd['id']}&amp;action=show&quot;&gt;{$dvd['title']}{$dvd['addinfo']}&lt;/a&gt;\">\n".
		 "       <img src=\"gfx/big.gif\" style=\"border:0 none;width:13px;height:13px\" alt = \"\">\n".
		 "      </a>\n".
		 "     </div>\n".
		 "     <a href=\"".$PHP_SELF."?mediaid=".$dvd['id']."&amp;action=show\" title=\"".$dvd['title']."\">\n".
		 "     <img src=\"".$tn."\" alt = \"".$dvd['title']."\"><br />\n".
		 "     <span class=\"print\">".$dvd['title'].$dvd['addinfo']."</span></a>\n";
	echo $boxcode;
	echo "    </div>\n".
		 "   </div>\n";


}

function css($divheight, $divwidth){
	global $Title, $imagewidth, $imageheight,$galleryver, $gallery_bgpic;

	if (!empty ($gallery_bgpic)) {
		$bgpic = "background-image: url(".$gallery_bgpic.");";
	}
	else {
		$bgpic = "";
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
    "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
 <title><?php echo $Title; ?></title>
 <link rel="stylesheet" type="text/css" href="./format.css.php">
 <meta http-equiv="content-type" content="text/html; charset=windows-1252">
 <script type="text/javascript">
  <!--
   function dh(theitems, obj) {
	 var item=document.getElementById(theitems);
	 if (item.className == 'hide') {
		item.className = 'show';
		obj.src = 'gfx/minus.gif';
	 }
	 else {
		item.className = 'hide';
		obj.src = 'gfx/plus.gif';
	 }
	}
  //-->
 </script>
 <script type="text/javascript" src="lb/prototype.js"></script>
 <script type="text/javascript" src="lb/scriptaculous.js?load=effects,builder"></script>
 <script type="text/javascript" src="lb/lightbox.js"></script>

 <link rel="stylesheet" href="lb/lightbox.css" type="text/css" media="screen">
 <style type="text/css">
  <!--
  body {
	margin: 0px;
	<?php echo $bgpic; ?>
  }
  .hide {
  	display:none;
  }
  .show {
  	display:block;
  }
  .gallery {
  	font-size: x-small;
  	text-align: center;
  	vertical-align: top;
  	float: left;
  	height: <?php echo $divheight; ?>px;
  	width: <?php echo $divwidth; ?>px;
  	overflow: hidden;
  	padding: 3px;
  }
  .picbox {
  	position: relative;
  	left: 0px;
  	top: 0px;
  	padding: 0px;
  }
  .picbox img {
  	width: <?php echo $imagewidth; ?>px;
  	<?php echo $imageheight; ?>
  }
  .plusbox {
  	padding: 3px;
  	height: 20px;
  	width: 20px;
  	position: absolute;
  	left: 5px;
  	top: 5px;
  	right: auto;
  	bottom: auto;
  }
  .plusbox img {
  	width: 13px;
	height: 13px;
  }
  .bigbox {
  	padding: 3px;
  	height: 20px;
  	width: 20px;
  	position: absolute;
  	right: 5px;
  	top: 5px;
  	left: auto;
  	bottom: auto;
  }
  .boxbox {
  	padding: 3px;
  	height: 20px;
  	width: 20px;
  	position: absolute;
  	right: 5px;
  	bottom: 5px;
  	left: auto;
  	top: auto;
  }
  .noprint {
  	display: block;
  }
  .print {
  	display:none;
  }
  #printbox {
  	height: 25px;
  	width: 25px;
  	position: absolute;
  	right: 25px;
  	top: 10px;
  	display:block;
  }
  #printbox[id] {
  	position: fixed;
  	right: 15px;
  	top: 25px;
  }
  @media print {
  	.print {
  		display:inline;
  	}
  	.noprint {
  		display:none;
  	}
  	.plusbox {
  		display:none;
  	}
  	.bigbox {
  		display:none;
  	}
  	.boxbox {
  		display:none;
  	}
  	.hide {
  		display:block;
  	}
  	#printbox {
  		display:none;
  	}
  }
  -->
 </style>
 <!-- Version: <?php echo $galleryver;?> -->
</head>
<?php
}

function site_header (){
	GLOBAL $Title, $lang, $CurrentSiteTitle, $xmlfile, $sql, $dpp, $sortby, $order, $searchby, $searchtext, $letter, $ct, $page;
	switch ($ct){
		case 'owned':
		case 'ordered':
		case 'wishlist':
		case 'loaned':
		case 'all':
			$tmp = $lang[strtoupper($ct)];
			break;
		default:
			$tmp = ""; # TODO: Translate the auxcolltype...
			//$tmp = $ct;
			break;
	}
	$stxt = stripslashes(stripslashes($searchtext));
	switch ($searchby) {
		case '':
			$search = '';
			break;
		case 'title':
			$search = ' ('.$lang['SEARCHED'].' '.$lang['TITLES'].' '.$lang['FOR'].' "'.$stxt.'")';
			break;
		case 'actor':
			$search = ' ('.$lang['SEARCHED'].' '.$lang['ACTORS'].' '.$lang['FOR'].' "'.$stxt.'")';
			break;
		case 'credits':
			$search = ' ('.$lang['SEARCHED'].' '.$lang['CREDITS'].' '.$lang['FOR'].' "'.$stxt.'")';
			break;
		case 'genre':
			$search = ' ('.$lang['SEARCHED'].' '.$lang['GENRES'].' '.$lang['FOR'].' "'.GenreTranslation($stxt).'")';
			break;
		case 'director':
			$search = ' ('.$lang['SEARCHED'].' '.$lang['DIRECTORS'].' '.$lang['FOR'].' "'.$stxt.'")';
			break;
		case 'tag':
			$search = ' ('.$lang['SEARCHED'].' '.$lang['TAGS'].' '.$lang['FOR'].' "'.$stxt.'")';
			break;
		case 'coo':
			$search = "  (".$lang['SEARCHED']." ".$lang['COUNTRYOFORIGIN']." ".$lang['FOR']." \"".$lang['LOCALE'.$stxt]."\")'";
			break;
		case 'mediatype':
			$search = " (".$lang['SEARCHED']." ".$lang['MEDIATYPE']." ".$lang['FOR']." \"".$stxt."\")";
			break;
		case 'rating':
			$search = ' ('.$lang['SEARCHED'].' '.$lang['RATING'].' '.$lang['FOR'].' "'.rawurldecode($stxt).'")';
			break;
		}
	switch ($sortby){
		case "productionyear":
		     	$sort = $lang['SORTYEAR'];
				break;
		case "rating":
				$sort =  $lang['SORTRATING'];
				break;
		case "released":
				$sort =  $lang['SORTRELEASED'];
				break;
		case "purchasedate":
				$sort =  $lang['SORTPURCHDATE'];
				break;
		case "collectionnumber":
				$sort = $lang['SORTNUMBER'];
				break;
		case "genres":
				$sort = $lang['SORTGENRE'];
				break;
		case "runningtime":
				$sort = $lang['SORTRUNTIME'];
				break;
		case "sorttitle":
				$sort = $lang['SORTTITLE'];
				break;
		case "reviews":
				$sort = $lang['SORTREVIEWS'];
				break;
		}
		$thedatetime = file_exists($xmlfile)? fix88595(ucwords(strftimeReplacement($lang['DATEFORMAT'], filemtime($xmlfile)))): $lang['UNKNOWN'];
?>
 <div style="width:95%;">
  <div id="printbox" class="f4" style="background-color:transparent"><a href="javascript:self.print()"><img src="./gfx/printer.gif" style = "border: 0;"  alt="Print" title="Print this page."></a></div>
   <p class="f1" style="background-color:transparent">
    <?php echo $Title;?><br />
   <span class="f4" style="background-color:transparent;text-align:center;clear:right">
   <?php
   echo generate_pagination(get_total_profiles($sql), $dpp, $dpp*($page-1), TRUE);
   ?>
   <br>
   <span class="print">
     <?php echo $CurrentSiteTitle; ?><br>
     <?php echo $tmp. " ". $search; ?><br>
     <?php echo isset($sort) ? $sort : ''; ?><br>
     <?php echo $lang['UPDATED']." ".$thedatetime ?>
   </span>
   </span>
   </p>
  </div>
 <div style="text-align:center">
<?php
}

function site_footer(){
	GLOBAL $sql, $dpp, $PHP_SELF,$_SERVER, $page;
	?>
 </div>
 <div class="f4 noprint" style="background-color: transparent; text-align:center; width: 90%; clear:left;">
  <p>
	<?php
	echo generate_pagination(get_total_profiles($sql), $dpp, $dpp*($page-1), TRUE);
	?>
  </p>
  <p>
    <?php
	$link = $PHP_SELF."?".str_replace ( '&', '&amp;',$_SERVER['QUERY_STRING'] )."&amp;showall=true";
	echo "     <a href=\"".$link."\" class=\"f4\" style=\"background-color:transparent\">Show all</a>\n";
	?>
  </p>
 </div>
</body>
</html>
<?php
}

function get_total_profiles($sql){
	GLOBAL $db, $TitlesPerPage;

	$i = preg_replace('/(.*)(LIMIT)(.*)(,)(.*)/i', '\1', $sql);

	$result = $db->sql_query($i);
	$total = $db->sql_numrows($result);

	return $total;
}

function generate_pagination($num_items, $per_page, $start_item, $add_prevnext_text = TRUE){
	GLOBAL $PHP_SELF, $sortby, $order, $searchby, $searchtext, $letter, $ct, $mediaid, $page;
	$total_pages = ceil($num_items/$per_page);
	if ( $total_pages == 1 )
	{
		return '';
	}
	$on_page = floor($start_item / $per_page) + 1;

	$page_string = '';
	if ( $total_pages > 10 )
	{
		$init_page_max = ( $total_pages > 3 ) ? 3 : $total_pages;

		for($i = 1; $i < $init_page_max + 1; $i++)
		{
			$page_string .= ( $i == $on_page ) ? "<b>" . $i . "</b>" : "<a href=\"".$PHP_SELF."?mediaid=".$mediaid."&amp;action=show&amp;page=".$i."&amp;ct=".$ct."&amp;searchby=".$searchby."&amp;searchtext=".$searchtext."&amp;sort=".$sortby."&amp;order=".$order."\">" . $i . "</a>";
			if ( $i <  $init_page_max )
			{
				$page_string .= " |\n ";
			}
		}

		if ( $total_pages > 3 )
		{
			if ( $on_page > 1  && $on_page < $total_pages )
			{
				$page_string .= ( $on_page > 5 ) ? " ... \n" : " |\n ";

				$init_page_min = ( $on_page > 4 ) ? $on_page : 5;
				$init_page_max = ( $on_page < $total_pages - 4 ) ? $on_page : $total_pages - 4;

				for($i = $init_page_min - 1; $i < $init_page_max + 2; $i++)
				{
					$page_string .= ($i == $on_page) ? "<b>" . $i . "</b>" : "<a href=\"".$PHP_SELF."?mediaid=".$mediaid."&amp;action=show&amp;page=".$i."&amp;ct=".$ct."&amp;searchby=".$searchby."&amp;searchtext=".$searchtext."&amp;sort=".$sortby."&amp;order=".$order."\">" . $i . "</a>";
					if ( $i <  $init_page_max + 1 )
					{
						$page_string .= " |\n ";
					}
				}

				$page_string .= ( $on_page < $total_pages - 4 ) ? " ...\n " : " |\n ";
			}
			else
			{
				$page_string .= " ...\n ";
			}

			for($i = $total_pages - 2; $i < $total_pages + 1; $i++)
			{
				$page_string .= ( $i == $on_page ) ? "<b>" . $i . "</b>" : "<a href=\"".$PHP_SELF."?mediaid=".$mediaid."&amp;action=show&amp;page=".$i."&amp;ct=".$ct."&amp;searchby=".$searchby."&amp;searchtext=".$searchtext."&amp;sort=".$sortby."&amp;order=".$order."\">" . $i . "</a>";
				if( $i <  $total_pages )
				{
					$page_string .= " |\n ";
				}
			}
		}
	}
	else
	{
		for($i = 1; $i < $total_pages + 1; $i++)
		{
			$page_string .= ( $i == $on_page ) ? "<b>" . $i . "</b>" : "<a href=\"".$PHP_SELF."?mediaid=".$mediaid."&amp;action=show&amp;page=".$i."&amp;ct=".$ct."&amp;searchby=".$searchby."&amp;searchtext=".$searchtext."&amp;sort=".$sortby."&amp;order=".$order."\">" . $i . "</a>";
			if ( $i <  $total_pages )
			{
				$page_string .= " |\n ";
			}
		}
	}

	if ( $add_prevnext_text )
	{
		if ( $on_page > 1 )
		{
			$page_string = "<a href=\"".$PHP_SELF."?mediaid=".$mediaid."&amp;action=show&amp;page=". ($on_page-1) ."&amp;ct=".$ct."&amp;searchby=".$searchby."&amp;searchtext=".$searchtext."&amp;sort=".$sortby."&amp;order=".$order."\">&lt;</a>\n" . $page_string;
		}

		if ( $on_page < $total_pages )
		{
			$page_string .= "\n <a href=\"".$PHP_SELF."?mediaid=".$mediaid."&amp;action=show&amp;page=". ($on_page+1) ."&amp;ct=".$ct."&amp;searchby=".$searchby."&amp;searchtext=".$searchtext."&amp;sort=".$sortby."&amp;order=".$order."\">&gt;</a>";
		}

	}

	return $page_string;
}

function get_SQL($page, $dpp = 60){
	global $_GET, $handleadult, $stickyboxsets, $lettermeaning, $removetabbed, $IsPrivate, $masterauxcolltype, $CountryToLocality, $defaultsorttype,
		   $sortby, $order, $searchby, $searchtext, $letter, $ct, $SecondarySortFollowPrimary, $collectiontypelist, $lang, $reviewsort,
		   $hideowned, $hideordered, $hidewishlist,
		   $DVD_TABLE, $DVD_COMMON_ACTOR_TABLE, $DVD_ACTOR_TABLE, $DVD_EVENTS_TABLE, $DVD_DISCS_TABLE, $DVD_LOCKS_TABLE,
		   $DVD_AUDIO_TABLE, $DVD_COMMON_CREDITS_TABLE, $DVD_CREDITS_TABLE, $DVD_BOXSET_TABLE, $DVD_STUDIO_TABLE,
		   $DVD_TAGS_TABLE, $DVD_SUPPLIER_TABLE, $DVD_GENRES_TABLE, $db;

	if ($stickyboxsets) {
		$nobox = " AND boxparent = '' ";
	}
	else {
		$nobox = "";
	}

	$start = ($page-1)*$dpp;

	$limits = "";
	if (!$_GET['showall']) $limits = "LIMIT $start, $dpp";

	$noadult = "";

	if ($handleadult == 2 || ($handleadult == 1 && !$IsPrivate)) {
		$noadult = "AND isadulttitle=0";
	}




	switch ($sortby)	{		// sort options for the sql-query
		case 'productionyear':
						$add = ', productionyear as addinfo ';
						break;
		case 'rating':	$add = ', rating as addinfo ';
						break;
		case 'released':$add = ', released as addinfo ';
						break;
		case 'purchasedate':
						$add = ', collectiontype, wishpriority, purchasedate as addinfo ';
						break;
		case 'collectionnumber':
						$add = ', collectiontype, collectionnumber as addinfo, wishpriority as addinfo2 ';
						break;
		case 'director':$add = ', primedirector as addinfo ';
						$sort = 'addinfo';
						$sortby = 'primedirector';
						break;
		case 'genres':	$add = ', primegenre as addinfo ';
						$sort = 'addinfo';
						$sortby = 'primegenre';
						break;
		case 'runningtime':
						$add = ', runningtime as addinfo ';
						break;
		case 'reviews':	$add = ', reviewfilm, reviewvideo, reviewaudio, reviewextras ';
						$mysort = DeriveSort($reviewsort, $order);
						break;
		}

	$add = '';



	$secsort = 'ASC';
	if ($SecondarySortFollowPrimary)
		$secsort = $order;
	switch ($ct) {
		case 'owned':
		case 'ordered':
		case 'wishlist':
						$where = "collectiontype = '$ct' $noadult";
						$orderby = "ORDER BY $sortby $order,wishpriority $order, sorttitle $secsort";
						if (isset($removetabbed) && $removetabbed)
							$where .= " AND auxcolltype=''";
						break;
		case 'loaned':
						$add = ', loaninfo as addinfo';
						$where = "loaninfo != '' $noadult";
						$orderby = "ORDER BY $sortby $order, sorttitle $secsort";
						break;
		case 'all':
						$where = "1";
						if ($hideowned) $where .= " AND collectiontype!='owned'";
						if ($hideordered) $where .= " AND collectiontype!='ordered'";
						if ($hidewishlist) $where .= " AND collectiontype!='wishlist'";
						$where .= " $noadult";
						$orderby = "ORDER BY $sortby $order,wishpriority $order, sorttitle $secsort";
						break;
		default:
					if (is_numeric($ct)) {
						$where = "auxcolltype LIKE '%/".addslashes($masterauxcolltype[$ct])."/%'";
					}
					else if (substr($ct, 0, strlen('FJW-')) == 'FJW-') {
						$where = "realcollectiontype = '".addslashes($collectiontypelist[(int)substr($ct, strlen('FJW-'))])."' $noadult";
						if (isset($removetabbed) && $removetabbed)
							$where .= " AND auxcolltype=''";
					}
						$orderby = "ORDER BY $sortby $order,wishpriority $order, sorttitle $secsort";
						break;
	}

	if ($sortby == "reviews") $orderby =  "ORDER BY ".$mysort;

	if ($lettermeaning == 1 && !empty($letter)) {
		if ( $_GET['letter'] == "0")
			$where .= " AND sorttitle < 'A'";
		else
			$where .= " AND sorttitle LIKE '".$db->sql_escape($_GET['letter'])."%'";
	}

	$base = 'dvd.id, dvd.title, dvd.originaltitle, dvd.sorttitle, dvd.description, dvd.boxchild, dvd.mediabannerfront, dvd.mediabannerback, dvd.custommediatype';

	switch ($searchby) {
	case "title":
		if ($searchtext[0] == '^')
			$searchsql="AND (title LIKE '" . substr($searchtext, 1) ."%' OR originaltitle LIKE '".substr($searchtext,1)."%' OR description LIKE '".substr($searchtext,1)."%')";
		else
			$searchsql="AND (title LIKE '%".$searchtext."%' OR originaltitle LIKE '%".$searchtext."%' OR description LIKE '%".$searchtext."%')";
		$nobox = "";
		$sql  = "SELECT $base $add FROM $DVD_TABLE dvd WHERE $where $searchsql $orderby $limits";
		break;
	case "genre":
		$searchsql="AND dvd.id=gens.id AND genre LIKE '%".$searchtext."%'";
		$sql = "SELECT $base $add FROM $DVD_TABLE dvd, $DVD_GENRES_TABLE gens WHERE $where $searchsql $orderby $limits";
		break;
	case "tag":
		$searchsql="AND dvd.id = tag.id AND (tag.fullyqualifiedname LIKE '%".$searchtext."%')";
		$sql = "SELECT distinct $base $add FROM $DVD_TABLE dvd, $DVD_TAGS_TABLE tag WHERE $where $searchsql $orderby $limits";
		break;
	case "director":
		$searchsql="AND dvd.id = cre.id  AND (cc.fullname LIKE '%$searchtext%') AND (cre.credittype LIKE 'Direction')";
		$sql = "SELECT $base $add FROM $DVD_TABLE dvd, $DVD_CREDITS_TABLE cre INNER JOIN $DVD_COMMON_CREDITS_TABLE cc ON cc.caid = cre.caid  WHERE $where $searchsql GROUP BY dvd.id $orderby $limits";
		break;
	case "credits":
		$searchsql="AND dvd.id = cre.id AND (cc.fullname LIKE '%".$searchtext."%')";
		$sql = "SELECT distinct $base $add FROM $DVD_TABLE dvd, $DVD_CREDITS_TABLE cre INNER JOIN $DVD_COMMON_CREDITS_TABLE cc ON cc.caid = cre.caid  WHERE $where $searchsql $orderby $limits";
		break;
	case "actor":
		$searchsql="AND dvd.id = act.id AND ((ca.fullname LIKE '%".$searchtext."%') OR (act.role LIKE '%".$searchtext."%'))";
		$sql = "SELECT $base, ca.caid $add FROM $DVD_TABLE dvd, $DVD_ACTOR_TABLE act INNER JOIN $DVD_COMMON_ACTOR_TABLE ca ON ca.caid=act.caid WHERE $where $searchsql GROUP BY dvd.id $orderby $limits";
		break;
	case "lock":
		$searchsql="AND dvd.id = locks.id AND locks.".strtolower($searchtext)." = 1";
		$sql = "SELECT $base $add FROM $DVD_TABLE dvd, $DVD_LOCKS_TABLE locks WHERE $where $searchsql GROUP BY dvd.id $orderby $limits";
		break;
	case "rating":
		$lookfor = rawurldecode($searchtext);
		$searchsql="AND dvd.rating LIKE '$lookfor'";
		$sql = "SELECT $base, dvd.rating $add FROM $DVD_TABLE dvd WHERE $where $searchsql $orderby $limits";
		break;
	case "locale":
		$searchsql = "AND IF (LOCATE('.',dvd.id) = '0',0,SUBSTRING(dvd.id,locate('.',dvd.id)+1,LENGTH(dvd.id)-LOCATE('.',dvd.id)))+0 = '".$searchtext."'";
		$sql = "SELECT $base, dvd.rating $add FROM $DVD_TABLE dvd WHERE $where $searchsql $orderby $limits";
		break;
	case 'purchase':
		$searchsql="AND purchaseplace = '".$searchtext."'";
		$sql = "SELECT $base, dvd.rating $add FROM $DVD_TABLE dvd WHERE $where $searchsql $orderby $limits";
		break;
	case 'coo':
		$tmp = array_search($searchtext, $CountryToLocality);
		$searchsql = "AND countryoforigin = '".$tmp."'";
		$sql = "SELECT $base, dvd.countryoforigin $add FROM $DVD_TABLE dvd WHERE $where $searchsql $orderby $limits";
		break;
	case 'mediatype':
		switch ($searchtext) {
		case $lang['DVD']:
			$sfield = '(builtinmediatype='.MEDIA_TYPE_DVD.' or builtinmediatype='.MEDIA_TYPE_HDDVD_DVD.' or builtinmediatype='.MEDIA_TYPE_BLURAY_DVD.')';
			break;
		case $lang['BLURAY']:
			$sfield = '(builtinmediatype='.MEDIA_TYPE_BLURAY.' or builtinmediatype='.MEDIA_TYPE_BLURAY_DVD.')';
			break;
		case $lang['HDDVD']:
			$sfield = '(builtinmediatype='.MEDIA_TYPE_HDDVD_DVD.' or builtinmediatype='.MEDIA_TYPE_HDDVD.')';
			break;
		case $lang['ULTRAHD']:
			$sfield = '(builtinmediatype='.MEDIA_TYPE_ULTRAHD.'  or builtinmediatype='.MEDIA_TYPE_ULTRAHD_BLURAY.' or builtinmediatype='.MEDIA_TYPE_ULTRAHD_BLURAY_DVD.')';
			break;
		default:
			$sfield = "(custommediatype='$searchtext')";
			break;
		}
		$searchsql = "AND ".$sfield;
		$sql = "SELECT $base $add FROM $DVD_TABLE dvd WHERE $where $searchsql $orderby $limits";
		break;
	default:
		$sql = "SELECT $base $add FROM $DVD_TABLE dvd WHERE $where $nobox $orderby $limits";
		break;
	}
	return ($sql);
}

if (isset ($_GET['searchtext'])) $_GET['searchtext'] = addslashes($_GET['searchtext']);
if (!isset ($_GET['$printall'])) $_GET['$printall'] = false;
if (!isset ($_GET['showall'])) $_GET['showall'] = false;

if ($TitlesPerPage == 0)
	$dpp=60;
else
	$dpp=$TitlesPerPage;


	if (!isset($_GET['sort'])){
        switch($defaultsorttype){
            case ('secondcol'):
                $sortby = $secondcol;
                break;
            case ('thirdcol'):
                $sortby = $thirdcol;
                break;
            default:
                $sortby = 'sorttitle';
                break;
        }
    }
    else
        $sortby = $_GET['sort'];

    if (!isset($_GET['order']))
    	$order = 'asc';
    else
    	$order = $_GET['order'];

    if (!isset($_GET['searchby']))
    	$searchby = '';
    else
    	$searchby = $_GET['searchby'];

    if (isset($_GET['searchtext']))
    	$searchtext = $_GET['searchtext'];
    else
    	$searchtext = '';

    if (isset ($_GET['letter']))
    	$letter = $_GET['letter'];
    else
    	$letter = '';

	if (!isset ($_GET['ct']))
		$ct = 'owned';
	else
		$ct = $_GET['ct'];

	if (!isset ($_GET['mediaid'])){
		switch ($InitialRightFrame){
			case ('Back Gallery'):
				$mediaid = 'GalleryB';
				break;
			default:
				$mediaid = 'Gallery';
				break;
		}
	}

$FnExt = 'f.jpg';
$FnWebPath = $img_webpathf;
$Title = $lang['FRONTGALLERY'];
$DoBack = ($mediaid == 'GalleryB');
if ($DoBack) {
	$FnExt = 'b.jpg';
	$FnWebPath = $img_webpathb;
	$Title = $lang['BACKGALLERY'];
}
if (!isset($imagewidth) || empty($imagewidth)) $imagewidth = 150;
$imageheight = '';
if (isset($constantratio) && $constantratio) {
	$imageheight = 'height: '.($imagewidth*7/5).'px';
}
$divwidth = $imagewidth+20;
$divheight = ($imagewidth + 40) * 7/5;

$plusgif = 'gfx/plus.gif';
$plusdisplay = 'none';
$plusclass = 'hide';


if (!isset($_GET['page']))
	$page=1;
else
	$page = $_GET['page'];


$sql = get_SQL($page,$dpp);

$result = $db->sql_query($sql);

$name = "";
$vname = "";

css($divheight, $divwidth, $imagewidth);
site_header();

while($dvd = $db->sql_fetch_array($result)) {

	$name = strtoupper(substr($dvd['sorttitle'], 0 , 1));
	$name = preg_replace('/([0-9])/', '0-9', $name);
	if ($name == $vname)
			$name = "";
	else
			$vname = $name;

	if (!empty($name))
			echo "   <a name=\"".$name."\"></a>\n";

	if (isset ($_GET['searchby']) && !empty ($_GET['searchby'])) $dvd['boxchild'] = 0;
	show_profile($dvd);

	if ($dvd['boxchild'] != 0){
		$bs = "bs".str_replace('.', '_', $dvd['id']);
		echo " <div  class=\"".$plusclass."\" id=\"".$bs."\">\n";
		get_children($dvd['id'],$bs);
		echo " </div>\n";
	}

}
site_footer();
