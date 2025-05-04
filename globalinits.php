<?php

if (!defined('IN_SCRIPT')) {
    die('This script should not be manually executed ... Possible Hacking attempt');
}

$DontBreakOnBadPNGGDRoutines = false;

// Set default defaults: values that will be used if the admin comments out variables
// in siteconfig.php rather than setting them appropriately. This is to prevent having
// to have butt-loads of "if (isset($var))""-type code ...

$ClassColor[0]  = 'blue';   // #0000FF URL link to main page color
$ClassColor[1]  = '#ADA9A9';    //
$ClassColor[2]  = '#BDD9A7';    //
$ClassColor[3]  = '#D9E1FF';    //
$ClassColor[4]  = '#ADC0D3';    //
$ClassColor[5]  = '#0000A0';    //
$ClassColor[6]  = '#CCFFCC';    //
$ClassColor[7]  = '#CCFFDD';    //
$ClassColor[8]  = '#99CC99';    //
$ClassColor[9]  = '#FFFFBB';    //
$ClassColor[10] = '#FFFFCC';    //
$ClassColor[11] = 'black';  // #000000 link color
$ClassColor[12] = 'white';  // #FFFFFF link hover color
$ClassColor[13] = 'white';  // #FFFFFF class=n link color
$ClassColor[14] = 'red';    // #FF0000 class=n link hover color
$ClassColor[15] = 'white';  // #FFFFFF class=Divider text color
$ClassColor[16] = 'navy';   // #000080 class=Divider border color
$ClassColor[17] = 'white';  // #FFFFFF class=f1 text color
$ClassColor[18] = 'black';  // #000000 class=l,o,d,a,u,v,x,y,z1,f2,f2np,f7 text color
$ClassColor[19] = 'white';  // #FFFFFF class=nav text color
$ClassColor[20] = '#BDD9A7';    //         class=a,f6,f8,bgl bgcolor
$ClassColor[21] = '#BDD9A7';    //         class=z2,f3,f3np,f5 bgcolor
$ClassColor[22] = 'black';  // #000000 class=z2 text color
$ClassColor[23] = '#0000A0';    //         class=f3,f3np text color
$ClassColor[24] = '#0000A0';    //         class=f5,f6 text color
$ClassColor[25] = 'white';  // #FFFFFF mom_linkBGColor
$ClassColor[26] = '#CCCCCC';    //         mom_linkOverBGColor
$ClassColor[27] = 'black';  // #000000 mom_menuBGColor ... was #CCCCCC
$ClassColor[28] = 'black';  // #000000 mom_hdrFontColor
$ClassColor[29] = '#BDD9A7';    //         mom_hdrBGColor
$ClassColor[30] = '#0000A0';    //         mom_barBGColor
$ClassColor[31] = 'white';  // #FFFFFF mom_barFontColor
$ClassColor[32] = 'black';  // #000000 class=s text color
$ClassColor[33] = 'black';  // #000000 class=s1 text color
$ClassColor[34] = 'black';  // #000000 class=input text color
$ClassColor[35] = 'white';  // #FFFFFF class=f1sm,f4 text color
$ClassColor[36] = 'white';  // #FFFFFF class=t text color
$ClassColor[37] = 'white';  // #FFFFFF class=n1 link color
$ClassColor[38] = 'red';    // #FF0000 class=n1 link hover color

define('MEDIA_TYPE_DVD',    1);
define('MEDIA_TYPE_HDDVD',  2);
define('MEDIA_TYPE_HDDVD_DVD',  3);
define('MEDIA_TYPE_BLURAY', 4);
define('MEDIA_TYPE_BLURAY_DVD', 5);
define('MEDIA_TYPE_ULTRAHD',    6);
define('MEDIA_TYPE_ULTRAHD_BLURAY', 7);
define('MEDIA_TYPE_ULTRAHD_BLURAY_DVD', 8);

$ActorWindowSettings        = "'toolbar=no,location=no,width=670,height=255,resizable=yes,scrollbars=yes,status=yes'";
$MediaTypes         = array(
    0           => array('FormatIcon' => '',                      'Icon' => '',               'Banner' => ''),
    MEDIA_TYPE_DVD      => array('FormatIcon' => 'gfx/icondvdpng.png',    'Icon' => 'gfx/dvd.jpg',    'Banner' => 'gfx/Banner_DVD.png'),
    MEDIA_TYPE_HDDVD    => array('FormatIcon' => 'gfx/iconhddvdpng.png',  'Icon' => 'gfx/hddvd.jpg',  'Banner' => 'gfx/Banner_HDDVD.png'),
    MEDIA_TYPE_HDDVD_DVD    => array('FormatIcon' => 'gfx/iconhddvdpng.png',  'Icon' => 'gfx/hddvd.jpg',  'Banner' => 'gfx/Banner_HDDVD_DVD.png'),
    MEDIA_TYPE_BLURAY   => array('FormatIcon' => 'gfx/iconbluraypng.png', 'Icon' => 'gfx/bluray.jpg', 'Banner' => 'gfx/Banner_BluRay.png'),
    MEDIA_TYPE_BLURAY_DVD   => array('FormatIcon' => 'gfx/iconbluraypng.png', 'Icon' => 'gfx/bluray.jpg', 'Banner' => 'gfx/Banner_BluRayDVD.png'),
    MEDIA_TYPE_ULTRAHD  => array('FormatIcon' => 'gfx/iconultrahd.png', 'Icon' => 'gfx/ultrahd.jpg', 'Banner' => 'gfx/Banner_UltraHD.png'),
    MEDIA_TYPE_ULTRAHD_BLURAY   => array('FormatIcon' => 'gfx/iconultrahd.png', 'Icon' => 'gfx/ultrahd.jpg', 'Banner' => 'gfx/Banner_UltraHDBD.png'),
    MEDIA_TYPE_ULTRAHD_BLURAY_DVD => array('FormatIcon' => 'gfx/iconultrahd.png', 'Icon' => 'gfx/ultrahd.jpg', 'Banner' => 'gfx/Banner_UltraHDBDDVD.png'),

);

$IgnoreCount0Profiles       = false;
$audiospecialcondition      = '';
$currencyspecialcondition   = '';
$srpspecialcondition        = '';
$genrespecialcondition      = '';
$localespecialcondition     = '';
$monthspecialcondition      = '';
$monthspecialprecondition   = '';
$originspecialcondition     = '';
$placespecialcondition      = '';
$productionyearspecialcondition = '';
$regionspecialcondition     = '';
$runtimespecialcondition    = '';
$shortestspecialcondition   = '';

$Highlight['loaned']['open'] = '';
$Highlight['loaned']['close'] = '';
$Highlight['overdue']['open'] = '';
$Highlight['overdue']['close'] = '';
$Highlight_Last_N_Days = 0;
$Highlight['last_n_days']['open'] = '';
$Highlight['last_n_days']['close'] = '';
$Highlight_Last_X_PurchaseDates = 0;
$Highlight['last_x_purchasedates']['open'] = '';
$Highlight['last_x_purchasedates']['close'] = '';

$AddWatchedEventWhenReturned = false;
$AddHDLogos     = true;
$AddFormatIcons     = 2;
$AllowProfileLabelsToWrap = true;
$AllowFlagging      = false;
$AllowHTMLInOverview    = true;
$AlwaysRemoveFromNotes  = '';
$ApplyDividerContinuations = false;
$BackGallery        = false;
$CustomPostUpdate   = '';
$CullDupsFromWatched    = false;
$DebugFilename      = '';
$DefaultCollection  = 'owned';
$DisplayNotesAsHTML = true;
$AllowChooser       = false;
$RefuseBots     = true;
$RemoveChecked      = true;
$LeaveMissing       = false;
$FixBadXML      = false;
$HideNames      = false;
$ExposeRatingDetails    = false;
$GDFontPathOverride = '';
$IMDBNumFromSlot    = false;
$IframeHeight       = 0;
$ImageUserURL       = 'http://dvdaholic.me.uk/ii/index.php';
$NoBackImageNotFound    = false;
$MaxX           = 20;
$CollectionsNotInOwned  = array();
$PrivateNotes       = false;
$ProfileStatistics  = false;
$ReportOnMemory     = false;
//$pscommand        = 'ps -p %%pid%% -o%mem= -orss=';
$pscommand      = '';
$SeparateReviews    = true;
$SecondarySortFollowPrimary = true;
//$StatisticsOnFrameInit    = false;
$ShowSQLInPicker    = false;
$SubmitOldStyle     = false;
$InitialRightFrame  = '';
$TitlesPerPage      = 0;
$TryToFiddleIndices = false;
$MY_EMAIL_ADDRESS   = '';
$TopX           = 10;
$UpdateDebug        = false;
$UpdateStatusFrequency  = 3;
$UpdateMillisecondPauseBetweenPosts = 100;
$UpdateMillisecondSettlingTime  = 1000;
$UseIframeForNotes  = false;
$UseIframeForEPGs   = false;
$TryToChangeMemoryAndTimeLimits = true;
$ULHSTitle      = '';
$WorkAroundLibxmlBug    = false;
$actorsort      = 2;
$all_in_one_go      = false;
$allowactorsort     = false;
$allowdefaultsorttype   = false;
$allowlocale        = false;
$allowpopupimages   = false;
$allowsecondcol     = false;
$allowskins     = true;
$allowstickyboxsets = false;
$allowthirdcol      = false;
$allowtitledesc     = false;
$allowtitlesperpage = true;
$allowupdate        = true;
$allowwidths        = false;
$amt_on_either_side = 24;
$borrowers      = array();
$collectionurl      = '';
$colnorange     = 25;
$colorfirst     = '#000080';
$colorlast      = '#008000';
$colormiddle        = '#800000';
$colornames     = false;
$cols           = 3;
$constantratio      = true;
$createthumbs       = true;
$currencypriority   = array();
$db_fast_update     = false;
$dbhost         = 'localhost';
$dbname         = 'phpdvdprofiler';
$dbpasswd       = 'admin';
$dbport         = '';
$dbtype         = 'mysql';
$dbuser         = 'admin';
$debugon        = true;
$debugimageuploads  = false;
$debugskin      = false;
$debugSQL       = false;
$defaultsorttype    = 'thirdcol';
$displaycurrency    = 1;
$displayfreq        = 100;
$displayloaned      = 1;
$displaymonth       = 1;
$displayplace       = 1;
$displaySRP     = 1;
$displaytags        = 2;
$displayreviewsEQ0  = true;
$endbody        = '</body>';
$EPGTagname     = 'Has EPG';
$epg_RemoveLocale   = true;
$episode_replacements   = array();
$expandboxsets      = false;
$expandcast     = true;
$expandcrew     = true;
$expanddividers     = true;
$expandepg      = true;
$expandnotes        = true;
$expandoverview     = true;
$firstcol       = 'sorttitle';
$force_cleanup      = false;
$force_formlogin    = 1;
$forceimageupdate   = false;
$forumuser      = '';
$gallery_bgpic      = 'gfx/wood.jpg';
$genrefmt       = 'percent';
$genremax       = 10;
$getimages      = 1;
$handleadult        = 0;
$handlewatched      = 1;
$hideordered        = false;
$hidewishlist       = false;
$hideowned      = false;
$hideloaned     = false;
$imagecachedir      = 'imagecache/';
$imagewidth     = 150;
$img_physpath       = 'images/';
$img_webpath        = 'images/';
$img_epgpath        = 'epg/';
$img_episode        = 'epg/';
$headcrew       = 'headshots/crew/';
$headcast       = 'headshots/cast/';
$castsubs       = array();
$crewsubs       = array();
$jpgrace        = 3;
$jpgraphlocation    = '../jpgraph/';
$language_override  = '';
$lettermeaning      = 0;
if (($localsiteconfig=@getenv('localsiteconfig')) == '')
    $localsiteconfig = 'localsiteconfig.php';
$local_lan      = array();
$locale         = 'en';
$localemin      ='0.05';
$mobileshow     = false;
$maxthumbwidth      = 180;
$maxheadshotwidth   = 180;
$maxheadshotwidthccw    = 60;
$onlycurrencypriority   = false;
$pcre_episode_replacements  = array();
$placesmin      ='0.05';
$playsounds     = 1;
$plwrapf        = '';
$plwrapb        = '';
$popupimages        = false;
$try_prev3_images   = false;
$removetabbed       = 0;
$reviewgraph        = 'FV';
$reviewsort     = $reviewgraph;
$rows           = 20;
$searchlocks        = 0;
$searchtags     = 0;
$secondcol      = 'genres';
$showbadboxsetnames = true;
$sitetitle      = "My DVD Collection";
$skinfile       = 'internal';
$skinloc        = '';
$stickyboxsets      = true;
$table_prefix       = 'DVDPROFILER_';
$thirdcol       = 'purchasedate';
$thumbnails     = 'thumbnails';
$thumbqual      = 80;
$thumbwidth     = 180;
$titledesc      = 1;
$titleorig      = 0;
$update_login       = 'admin';
$update_pass        = 'admin';
$usejpgraph     = false;
$usetemptable       = false;
$watched        = 'Watched';
$widthgt800     = 474;
$xmldir         = '';
$xmlfile        = 'collection.xml';
// End of default defaults
