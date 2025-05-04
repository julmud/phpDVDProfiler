<?php

if (!defined('IN_SCRIPT')) {
    die('This script should not be manually executed ... Possible Hacking attempt');
}

/*
// Config
*/

// Database connection data
// $dbtype specifies the name of the file containing the sql_db class. Currently, the only legal
// values are 'mysql' (which should work almost everywhere) and 'mysqli' which should only be
// required on systems with a version of PHP5 that does not have the mysql_* extension built in.
// note that $table_prefix must match the values in schema.sql
// $force_cleanup set to true makes updates always rejig boxsets and re-populate the statistics
// tables, even if there were no profile changes
// $usetemptable will speed up statistics calculation on updates. It requires that the mysql user
// that the update runs as be allowed to create temporary tables (and that the db support that).
// This may require the database administrator to GRANT CREATE TEMPORARY TABLES on db.* to dbuser
$dbtype         = 'mysqli';
$dbhost         = 'localhost';
$dbuser         = 'admin';
$dbpasswd       = 'admin';
$dbname         = 'phpdvdprofiler';
$dbport         = '';   // leave blank if on default port
$table_prefix       = 'DVDPROFILER_';
$force_cleanup      = false;
$usetemptable       = false;

// New (3.7.2.2) update mechanism
//
// New in this release, the update code uses a bunch of JavaScript to try to automate the
// update process. If your update normally completes without incident, then this should
// be completely transparent to you.
// The new mechanism tries to address the problem of updates dying before the update is
// complete. The previous mechanism required the user to notice that the update had died,
// and then re-run the update, possibly changing some parameters. The new update mechanism
// automates that process, watching to see if the update died, and automatically running
// another with the parameters set correctly. This may run for a long time, but as long
// as you don't close your browser it should eventually finish (it shouldn't take very
// much longer than the previous mechanism, in fact).

// There are a number of tunable variables introduced, which should be fine with their
// default values.
//
// $SubmitOldStyle - defaults to false. If set to true, then the old mechanism is used to update
// $UpdateDebug - defaults to false. If set to true, it puts a debug button on the UI and makes
//                the JavaScript code instrument the update run. When the debug button is pressed,
//                the output of the code is written into a window where it can be copy 'n pasted
//                into an email to me for debugging.
// $UpdateStatusFrequency - defaults to 3 (seconds). This is the frequency with which the code
//                checks to see how the update is progressing. It causes a db call on the server,
//                so the smaller you make this the harder the code presses your server.
// $UpdateMillisecondPauseBetweenPosts - defaults to 100 (milliseconds). You should not have to
//                change this. It is the amount of time that the program waits before issuing a
//                new update request. It may cause no preformance change with your server. Setting
//                it low may annoy your ISP.
// $UpdateMillisecondSettlingTime - default is 2000 (milliseconds - 2 seconds) This should not need
//                to be changed. It is the amount of time that the code waits for possibly in-flight
//                data to be returned from the server. Setting it too low could result in some very
//                odd effects on the display. This time is most noticable as the brief pause once
//                the program has determined that the current update has stopped running. If the value
//                is too low, then results could arrive unexpectedly, making the program think that
//                an error has occurred when it has not. This would not be a fatal problem.

// This variable changes the mechanism used to update actors names in the db when importing
// the XML data. This operation is quite slow. Setting this variable attempts to make it
// faster, by reading the entire table into memory at the beginning, working out of the
// memory table while processing the XML, and then writing the changes back into the DB
// after it is finished. Depending on the number of unique actors/crew, this can consume
// rather a lot of memory for the table. Possibly enough to blow the program up. But it
// much faster when putting in many actors, and it doesn't beat the database up nearly as
// much as the alternative. If there are only a small number of profiles changing, this
// mechanism can make the update slower. Changable in localsiteconfig.php, as always, and
// it might be something to change depending on the character of the update ...
// NOTE: the new automatic update ignores this value: it always starts out with this set
// to true, and if it determines that the update is failing consistantly, then it resets
// it to false for further attempts.
$db_fast_update     = true;

// $displayfreq controls how frequently the "Updated xxx profiles" message appears. It defaults
// to 100, although it may be more interesting for it to be smaller on slow machines. Set it
// to a small prime number like 23 for hours of enjoyment ...
$displayfreq = 100;

// This variable, if set to true, will cause the program to try to find old-style-named image
// files if the new-style-named images don't exist. This could make it a little easier to
// migrate from V2 to V3
$try_prev3_images   = false;

// Change these next two lines to your Invelos ID, and the URL of your phpdvdprofiler website
// It ignores is_private and goes directly off of handleadult. Only if you always allow adult does
// it collect image info on adult titles.
// It doesn't send info on manually added titles. Therefore any counts might be different.
//
// **NOTE** Set $forumuser to your dvdprofiler forums user name
// **NOTE** Set $collectionurl to the public url for your collection. However, if your collection won't
// **NOTE** be publicly available, still set it, but set it to 'http://localhost/phpdvdprofiler/'
//
//$forumuser        = 'FredLooks';              // Change this to your username on the Invelos Website
//$forumuser        = 'UsernameMustBeCustomizedHere';   // Change this to your username on the Invelos Website
$forumuser      = '';
//$collectionurl    = 'http://localhost/phpdvdprofiler/';   // with trailing '/' without terminal 'index.php'
$collectionurl      = '';
// Set $forceimageupdate to true (best in localsiteconfig.php) to force a complete image update to ajm's site
//$forceimageupdate = true;

// This variable makes the word "for" in the nav pane into a link which can be used to debug
// Javascript, or to check the setting for the screen width.
$debugon        = true;

// Here (or more appropriately in localsiteconfig.php) you could configure awstats support. The
// PHP variable $endbody is defined as '</body>'. To make awstats apply you could define it to be:
// $endbody = '<script type="text/javascript" src="/js/awstats_misc_tracker.js"></script>'
//           .'<noscript><img src="/js/awstats_misc_tracker.js?nojs=y" alt="" height=0 width=0 border=0 style="display:none"></noscript>'
//           .'</body>';
// You should customise the url to suite your site, of course.

// This section allows for using the jpgraph software to make statistical plots of information.
// The jpgraphlocation variable indicates where the jpgraph files are located. On my machine, they are
// symlinked to appear as a sibling to the directory containing the phpdvdprofiler software.
// $localemin is used to set the threshold for the "Others" category
// in the profiles-by-locality graph: default set here is 5% of the most populous locale. $placesmin
// serves the same purpose in the profiles-by-purchaseplace graph.
// genremax controls how many segments are in the genre pie chart.

//$usejpgraph       = true;
$usejpgraph     = false;
$jpgraphlocation    = '../jpgraph/';
$localemin      ='0.05';
$placesmin      ='0.05';
$genremax       = 10;
// Although jpgraph does try to pad scales, sometimes its not perfect.
// So set this to add some padding to the scales. It's a percentage.
$jpgrace        = 3;

// $display[name] controls when potentially private graphs are displayed
// month, place, currency
// Set to 1 they obey IsPrivate. Set to 0 they always display.
$displaymonth       = 1;
$displayplace       = 1;
$displaycurrency    = 1;

// The $xxxspecialcondition variables reflects oddities in the data. AJM doesn't want to include any profiles that
// don't have a purchase place. FredLooks doesn't want to include any profiles with a purchasedate
// before 01 Jan 2000.
// Some of the special conditions are used at runtime: when the statistics page is displayed. These include
//  $runtimespecialcondition
//  $monthspecialprecondition
//  $monthspecialcondition
//  $srpspecialcondition
//  $productionyearspecialcondition
// Others of the special conditions are used at update time: when the database is updated with changes to the collection.
// These include
//  $audiospecialcondition
//  $currencyspecialcondition
//  $genrespecialcondition
//  $localespecialcondition
//  $originspecialcondition
//  $placespecialcondition
//  $regionspecialcondition
//  $shortestspecialcondition

// $monthspecialcondition is used for purchases by month.
// Andy's example : - (it was much easier before we moves the supplier to there own table!)
//$monthspecialcondition          = " AND $DVD_TABLE.purchaseplace=$DVD_SUPPLIER_TABLE.sid AND suppliertype <> 'U'";
//$monthspecialprecondition       = ", $DVD_SUPPLIER_TABLE ";

// Fred's example :-
//$monthspecialcondition    = "AND purchasedate > ".my_mktime(0,0,0,1,1,2000);

$monthspecialcondition      = '';
$monthspecialprecondition   = '';

// $localespecialcondition is used for the purchases-by-locale graph
//$localespecialcondition   = "AND purchaseplace <> ''";
$localespecialcondition     = '';

//$runtimespecialcondition  = "AND boxparent = ''";     // ignore child profiles
//$runtimespecialcondition  = "AND boxchild = 0";       // ignore parent profiles
$runtimespecialcondition    = '';

//$shortestspecialcondition = "AND runningtime <> 0";
$shortestspecialcondition   = '';

//$productionyearspecialcondition = "AND productionyear <> '0'";
$productionyearspecialcondition = "AND productionyear <> '0'";

//$currencyspecialcondition = "AND purchaseplace <> ''";
$currencyspecialcondition   = '';
// $currencypriority is for controlling the cost-by-month graph. $onlycurrencypriority says don't
// show currencies I don't list here. Otherwise, the graphs cycle through all of the available currencies
//$onlycurrencypriority     = false;
//$currencypriority[0]      = 'USD';
//$currencypriority[0]      = 'CAD';

// Force form based login on Apache servers instead of Basic HTTP authentification when updating
$force_formlogin    = 1;
// Login name and password for maintenance
$update_login       = 'admin';
$update_pass        = 'admin';

// Variables for gallery.php integration
// $rows is the maximum number of rows of images per page; default = 20
// $cols is the maximum number of columns of images per row; default = 3
// $imagewidth is the width of the images in the gallery
// $constantratio if true, all of the gallery images should be forced to the
//    same height. If false, then the image heights will depend on the actual images
// If $BackGallery is set to true, then a menu item to display a gallery of back covers is added.
// $thumbqual is the "quality" of the jpeg image created when re-sizing images
$rows           = 20;
$cols           = 3;
$imagewidth     = 150;
$constantratio      = true;
$BackGallery        = false;
$thumbqual      = 80;

// Title for webpages
//$sitetitle        = "Fred's DVD Collection";
$sitetitle      = "My DVD Collection";
// If you want different text for your site's title depending on the translation you are using, you
// can create additional variables, eg.
// $sitetitle_translation['ru'] = "My Russian title string";
// $sitetitle_translation['fr'] = "My French title string";
// $sitetitle_translation['no'] = "My Norwegian title string";

// Maximum number of Titles to display on a page
// Set to 0 to display all titles as normal. Setting this value to something other than 0 means your pages
// will load faster. Note that the actual number of titles that will appear on a page may vary. This variable
// only really controls the number of top-level titles that appear on a page; child profiles are not counted
// for this purpose.
//$TitlesPerPage    = 50;
$TitlesPerPage      = 0;

// Meaning of the letter URLs.
// $lettermeaning = 1 means that when you click on a letter, the current display (either a
// search, or the current collection) will be displayed ordered by the sorttitle, starting
// at the selected letter and limited to titles whose sorttitle first letter matches the
// selected letter.
// $lettermeaning = 0 means that when you click on a letter, the current display (either a
// search, or the current collection) will be displayed ordered by the sorttitle, starting
// at the selected letter and continuing to the end, or to $TitlesPerPage titles.
$lettermeaning      = 0;

// Colornames
// set $colornames=true to switch on, and set $colorfirst, $colormiddle, $colorlast to customize colors
//$colornames       = true;
//$colorfirst       = '#000070';
//$colormiddle      = '#700000';
//$colorlast        = '#007000';

// Deprecated. Use variable $InitialRightFrame, below.
// Display statistics page when initially loading site
// Choices are:
//   true - Display statistics page every time main frame is refreshed
//  false - Display a selected DVD on main frame refresh
//$StatisticsOnFrameInit    = false;

// What to display in right-hand frame when initially loading site
// Currently legal values are:
//$InitialRightFrame = 'Statistics';    // Causes the Statistics page to be displayed
//$InitialRightFrame = 'Front Gallery'; // Causes the Front Cover gallery to be displayed
//$InitialRightFrame = 'Back Gallery';  // Causes the Back Cover gallery to be displayed
$InitialRightFrame = '';        // Default. Causes the first profile in the menu to be displayed.
// All other values are equivalent to the default.
// In index.php, one can select the initial DVD to be displayed. There are several examples.
// Look for the string ' $sql = "SELECT ' to locate the spot ...

// Number of items in the Top "10" list
$TopX           = 10;

// Width of the nav/menu frames, in pixels. One for screen resolutions of 800 or less and
// one for resolutions greater than 800
// **** Note that widthle800 has been deprecated. The code no longer distinguishes screens
// **** less than 800 pixels
// The $allow variable (true or false) lets visitors configure the width of the columns for their own view
//$widthle800       = 275;
$widthgt800     = 474;
$allowwidths        = true;

// Allowing display and search of locks on DVDs.
// Choices are:
//   0 - Allow lock icons to be displayed and searched for
//   1 - Allow lock icons to be displayed and searched for if IsPrivate
//   2 - Do NOT allow lock icons to be displayed or searched for
$searchlocks        = 0;

// Allowing searches of tags in the collection
// Choices are:
//   0 - Allow tags to be searched
//   1 - Allow tags to be searched if IsPrivate
//   2 - Do NOT allow tags to be searched
$searchtags     = 0;

// Allowing display of tags in the collection
// Choices are:
//   0 - Allow tags to be displayed
//   1 - Allow tags to be displayed if IsPrivate
//   2 - Do NOT allow tags to be displayed
$displaytags        = 0;

// Displaying loaned DVDs as a selection in the collection-type drop-down list
// Choices are:
//   0 - Display loaned as selection
//   1 - Display loaned as selection if IsPrivate
//   2 - Never Display loaned as selection
$displayloaned      = 1;

// Display of Notes field.
// Choices are:
//   true - Display only if IsPrivate
//  false - Always display
$PrivateNotes       = false;

// Handling of watched statistics
// Choices are:
//   0 - Display Normally
//   1 - Display if IsPrivate
//   2 - Never Display
$handlewatched      = 1;

// Default sort order for the watched stats
// sorttitle, runningtime and timestamp are the real choices.
// For exmaple 'timestamp'
$wssortorder = "timestamp";

// Maximum thumbnails to display in below lists
// the max is 10

$maxthumbs = 10;

// Handling of watched statistics
// Choices are:
//   0 - Display but default to hidden
//   1 - Display but default to displayed
//   2 - Never Display
$lastlist       = 1;
$mostlist       = 0;
$bestlist       = 0;
$worstlist      = 0;

// Handling of titles containing the genre "Adult"
// Choices are:
//   0 - Display Normally
//   1 - Display if IsPrivate
//   2 - Never Display
$handleadult        = 0;

// Handling of Notes containing sound files
// Choices are:
//   0 - Play Sounds
//   1 - Play Sounds if IsPrivate
//   2 - Never Play Sounds
$playsounds     = 1;

// What to do with TABS. If 'removetabs' is set to 1, anything with a 'TAB' is removed
// from the standard collection types 'owned', 'ordered' & 'wishlist'.
$removetabbed       = 0;

// XML import into phpDVDProfiler
//
// $xmlfile is the name of the XML file that was exported from DVD Profiler
// $watched is the text of the string put into the XML file by a "Watched DVD" event in DVDProfiler.
// The text is localised to the language selected at the time that the XML is exported.
// $FixBadXML handles a problem where the <Notes> field is truncated in the XML file. It slows the
// import process by about a factor of 3, so if you don't have this problem, leave this false.
//$xmlfile      = 'DVDAll.xml';
$xmlfile        = 'collection.xml';
$watched        = 'Watched';
$FixBadXML      = false;

// This controls whether the names of profiles which have boxset contents that are not present in
// your collection are displayed during the import process. Set to false if you don't care.
$showbadboxsetnames = true;

// How cover images are handled
//   0 = no cover thumbs
//   1 = display if locally available
//   2 = try to get from IVS server and cache locally
//   3 = display URL
// Getting from the server may stop working at any time if Invelos decides to stop serving in this manner
// Displaying URL requires that $img_webpath be set to a URL that points to the images. An example
// would be http://my.website.com/some/path/ending/with/slash/
// Additionally, if there is a requirement to separate covers onto two servers, one can define $img_webpathf
// and $img_webpathb differently for front and rear covers.
// The purpose of value 3 is for someone who is using a web-space provider for this program that cannot serve
// images from that server.
$getimages      = 1;

// Images paths
$img_physpath       = 'images/';    // Path on filesystem
$img_webpath        = 'images/';    // Path on webserver
# Setting imagecachedir and ensuring the permission on it is 777 means that the webserver can create
# tiny thumbnail images and only send those rather than sending normal thumbnails and
# letting the browser resize them. Therefore less data is sent over the internet.
$imagecachedir      = "imagecache/";

// Episode replacements. These are to support the excellent work done putting episode guides into the database.
// The episode guides are HTML, and to make them look nice we need to map the paths for images and sound files
// from where they need to be on the PC with DVDPro, to where they need to be for phpdvdprofiler. The place on
// the PC is an array to allow for different ways of writing the path. The destination is a single path. Note
// that the strings are case-sensitive, so put one for each variant in case. The variable $pcre_episode_replacements
// behaves the same as the other, except regular expressions are used. If both variables are defined, then the
// $pcre_episode_replacements is done first.
$episode_replacements   = array(
    'C:\\Program Files\\Intervocative Software\\DVD Profiler\\epg\\',
    '..\\epg\\',
    '../epg/'
);
$pcre_episode_replacements  = array(
    '@C:[/\\\\]Program Files[/\\\\]Intervocative Software[/\\\\]DVD Profiler[/\\\\]epg[/\\\\]@i',
    '@\.\.[/\\\\]epg[/\\\\]@i',
    '@c:[/\\\\]DVDPro[/\\\\]epg[/\\\\]@i'
);
$img_episode        = 'epg/';   // Path to images, etc. for episode guides

$local_lan = array( // Set this to the IP nets that you want to consider to be local
//  "10.0.0.",
    "127.0.0.1",    // for people who are on this machine
    "192.168.1."
);

// Value for the range of collection numbers between "headers". A value of 0 means no headings.
$colnorange     = 25;

/*
// Potentially viewer-configurable settings
*/

// Handling of sorting of actors in cast list.
// Choices are:
//   0 - sort by actor surname
//   1 - Sort by role
//   2 - use order found in database (should be same as DVDProfiler shows)
// The $allow variable (true or false) lets visitors configure this field for their own view
$actorsort      = 2;
$allowactorsort     = true;

// Value for the second and third columns -- Choices are:
//  'productionyear'
//  'released'
//  'runningtime'
//  'rating'
//  'genres'
//  'purchasedate'
//  'reviews'
//  'collectionnumber'
//  'none'  -- disables the column entirely
// The $allow variable (true or false) lets visitors configure this field for their own view
//$secondcol        = 'released';
//$thirdcol     = 'collectionnumber';
$secondcol      = 'genres';
$allowsecondcol     = true;

$thirdcol       = 'purchasedate';
$allowthirdcol      = true;

// Default sort -- Choices are 'firstcol', 'secondcol', or 'thirdcol', meaning that the default
// sort will be on the values of the first, second or third column in the nav menu
// The $allow variable (true or false) lets visitors configure this field for their own view
//$defaultsorttype  = 'firstcol';
$defaultsorttype    = 'thirdcol';
$allowdefaultsorttype   = true;

// Like DVD Profiler, controls the display of title and originaltitle.
// Choices are:
//   0 - Title
//   1 - Original Title
//   2 - Title (Original Title)
$titleorig      = 0;

// Like DVD Profiler, controls the display of title and description.
// Choices are:
//   0 - Title
//   1 - Title (Desc)
//   2 - Title: Desc
//   3 - Title - Desc
// The $allow variable (true or false) lets visitors configure this field for their own view
$titledesc      = 1;
$allowtitledesc     = true;

// Whether Box Sets are to be "sticky" (have expandable entries via javascript) (true or false)
// The $allow variable (true or false) lets visitors configure this field for their own view
// $expandboxsets controls whether sticky boxsets are expanded by default or not.
//$stickyboxsets    = false;
$stickyboxsets      = true;
$allowstickyboxsets = true;
$expandboxsets      = false;

// Skins control the display of the right hand pane. The string 'internal' causes the internal
// skin to be used. Other skins should be located in the skins directory, in a separate directory
// for each skin. The path to the directory must be placed in the $skinloc variable (it is ignored for
// the internal skin). The skins themselves are the result of exporting an HTML file of the DVDProfiler
// skin of interest. Any images in the skin should be put into the directory with the HTML file.
// $allowskins controls whether the viewer is allowed to change skins.
//$skinloc      = 'skins/phpDVDProfiler_Skin';
//$skinfile     = 'phpDVDProfiler Skin.html';
$allowskins     = true;
$skinfile       = 'internal';

// Control display of reviews
// DVDProfiler has an options section that allows users to customise how reviews are displayed.
// This is accomplished here through the setting of the reviewxxx variables. There are 2 review
// settings: $reviewgraph, which controls the display of the graphs, and $reviewsort which controls
// how reviews are sorted, and displayed when reviews is made a column in the left window. The
// individual reviews tracked are: Film, Video, Audio and Extras. Each of these can individually
// be set to be displayed. The variables are set to a string containing the letters FVAE in the order
// in which the reviews are to be displayed/sorted. The full DVDProfiler graph would correspond to
// $reviewgraph='FVAE'. DVDProfiler has two additional settings: Default, which is equivalent
// to $reviewgraph='FV' and one called Simple, which is the same as $reviewgraph='F'.
//$reviewgraph      = 'FVAE';
$reviewgraph        = 'FV';
//$reviewsort       = 'VFA';
$reviewsort     = 'FV';
// $SeparateReviews controls whether reviews are shown as an image representing the score, or
// as a graph, the way that DVDProfiler does it in the windows application
$SeparateReviews    = true;

// Control display of large images
// Choices are:
//   false - large images appear in the right-hand frame
//   true  - large images appear in a separate browser window
// The $allow variable (true or false) lets visitors configure this field for their own view
$popupimages        = false;
$allowpopupimages   = true;

// This variable mimics the DVDProfiler option Display Notes as HTML. Default is true
// for backward compatibility
$DisplayNotesAsHTML = true;

// Setting font path for signature scripts in ws.php
// The host-based default value for font locations can be overridden by setting the
// variable $GDFontPathOverride to the host-based path to fonts. This can be an absolute
// path or a path relative to the phpdvdprofiler directory. It must terminate in a slash (/)
// eg.
// $GDFontPathOverride = '../jpgraph/fonts/truetype/';

// This feature controls the Highlighting of titles in the main menu. Different effects can
// be added to titles depending on certain conditions. The idea is that one specifies an
// HTML tag pair to surround the title if the condition is met. For example, if the title
// is currently loaned, then the title would be displayed surrounded by the values of the
// $Highlight['loaned'] entry.
//$Highlight['loaned']['open'] = '<i><del>';
//$Highlight['loaned']['close'] = '</del></i>';
//$Highlight['overdue']['open'] = '<b>';
//$Highlight['overdue']['close'] = '</b>';
//$Highlight_Last_N_Days = 14;      // Highlight profiles recent in the last 14 days
//$Highlight['last_n_days']['open'] = '<b>';
//$Highlight['last_n_days']['close'] = '</b>';
//$Highlight_Last_X_PurchaseDates = 5;      // Highlight profiles purchased in the last 5 acquisitions
// The system can store up to the last 22 purchasedates, so any value of $Highlight_Last_X_PurchaseDates
// larger than that will be the same as $Highlight_Last_X_PurchaseDates = 22;
//$Highlight['last_x_purchasedates']['open'] = '<span style="font-variant: small-caps">';
//$Highlight['last_x_purchasedates']['close'] = '</span>';

// Default language to use. This also controls date formats via the system's locale settings
// Currently supported languages include:
//  Dutch  == 'nl'
//  English  == 'en'
//  French == 'fr'
//  German == 'de'
//  Norwegian  == 'no'
//  Swedish  == 'sv'
//  Finnish == 'fi'
//  Danish == 'dk'
//  Russian == 'ru'
// This requires language files such as lang_de.php. In there there are DATEFORMAT and SHORTDATEFORMAT
// strings. The format of these strings follows the format of the strftime() functions, see www.php.net
// NOTE: if month names appear in English when you have chosen another language, please see the
// notes in global.php. Look for '***** LOCALE Settings ****' for details
// The $allow variable (true or false) lets visitors configure this field for their own view
$locale         = 'en';
$allowlocale        = true;

// Language Support
// The language of the user interface is controlled by the $locale variable mentioned above. This is
// done by reading a file named "lang_xx.php" where xx is the value of the $locale variable. In these
// lang_xx.php files are the text strings that are used in the user interface.
// The values of the strings so loaded can be overridden by providing setting a variable in localsiteconfig.php
// eg. $language_override = 'CoinCollection'; where $language_override is the name of the variable used to
// signal that the builtin values should be modified, and 'CoinCollection' is the filename part of the file
// which will contain the override strings. The main program will then look for files named:
//     CoinCollection_en
//     CoinCollection_en.php
//     CoinCollection
//     CoinCollection.php
// in that order (assuming that the locale is 'en'; it actually looks for "CoinCollection_$locale" so that
// you could provide localised overrides if desired)

// The Javascript menu must be customized in-line. The code is in index.php; search for the string "mom"

// Display of small mediatype icons in menu listing
// Choices are:
//   0 - Display Icons on every line
//   1 - Display only icons that are HiDef (Bluray, HD DVD and combos with those)
//   2 - Don't Display Icons
$AddFormatIcons     = 2;
// One can change the images used by adding an entry such as:
//$MediaTypes[MEDIA_TYPE_BLURAY]['FormatIcon'] = 'gfx/NewBlurayImage.png';
//*********** Note that this has changed recently. The above is the new way to do it.
// The images need to be quite small, so it's best to test

// In the Windows program, new user-defined collections can be set to show up in the owned list or not.
// That information is not available in the XML file. By default, we show user-defined collections in the
// owned list. For Collections that you wish to not appear there, add their names to the $CollectionsNotInOwned
// array. Like so:
// $CollectionsNotInOwned[] = 'My Collection Name';
// You can add as many as you like. Note that after changing this value you must update your collection.
//
// The program supports Custom media types without intervention. If you want to have custom banners,
// or icons, or a custom formaticon, just add a line for each Custom Media Type like this:
//$MediaTypes['ExactNameOfCustomMediaType'] = array('FormatIcon' => 'FormatIconImageFile', 'Icon' => 'IconImageFile', 'Banner' => 'BannerImagefile');
// so for example:
//$MediaTypes['MP4 File'] = array('FormatIcon' => 'mp4.png', 'Icon' => 'big_mp4.jpg', 'Banner' => 'mp4file.png');
// Note that Banners *must* be png files

// Add mediatype banner on generated thumbnails.
// If you change this value, you'll have to do a full update of your collection, and then remove every image in the imagecache folder.
// Choices are:
//   0 - Adhere to the DVDProfiler way (mostly: banner on HD formats)
//   1 - Always add a media banner to thumbnails
//   2 - Don't add a media banner to thumbnails
$AddBannerOnThumbnails = 0;

// The following variables need documentation. I hope to get around to it soon :)
//$AllowProfileLabelsToWrap - boolean that tries to control line-wrapping in profile labels
//$MaxX - number of items stored in stats table at update time. must be larger than $TopX
//$ProfileStatistics - causes timing profiling to be done on statistics
//$allowtitlesperpage
//$allowupdate - controls if the 'update' menu option is available. If you use rsync or update from the
//               command line, you may want to set this variable to false in localsiteconfig.php
//$createthumbs
//$debugimageuploads - tries to create a file with the image comparison upload data. Mostly for ajm
//$debugskin - emits skin debugging info. Mostly for skin authors.
//$debugSQL - emits SQL debug info. Unlikely to help anyone else
//$displayreviewsEQ0 - true/false; indicates whether or not to Display reviews that are == 0
//$EPGTagname - name of tag to look for to indicate that there is an EPG file for the profile
//$expandcast - true/false to indicate whether collapsible cast section should be open initially
//$expandcrew - true/false to indicate whether collapsible crew section should be open initially
//$expanddividers - true/false to indicate whether collapsible dividers in cast/crew should be open initially
//$expandepg - true/false to indicate whether collapsible epg section should be open initially
//$expandnotes - true/false to indicate whether collapsible notes section should be open initially
//$expandoverview - true/false to indicate whether collapsible overview section should be open initially
//$firstcol
//$img_epgpath - location of epg files
//$headcrew - directory for crew headshots
//$headcast - directory for cast headshots
//$localemin
//$maxthumbwidth
//$maxheadshotwidth - maximum width (in pixels) for headshots
//$maxheadshotwidthccw - maximum width (in pixels) for headshots in cast/crew popup window
//$thumbwidth
