<?php

error_reporting(E_ALL);
//error_reporting(7);
//ini_set('mysql.trace_mode','on');
ini_set('error_reporting', E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'ERROR_LOG');

if (!defined('IN_SCRIPT')) {
	die('This script should not be manually executed ... Possible Hacking attempt');
}

include_once('functions.php');
include_once('version.php');

// Force GET/POST,etc. arguments to _not_ be quoted, and push GET and POST into the namespace
// Ensure that we can find superglobals
if (!isset($_SERVER)) {
	$_ENV = &$HTTP_ENV_VARS;
	$_SERVER = &$HTTP_SERVER_VARS;
	$_FILES = &$HTTP_POST_FILES;
	$_COOKIE = &$HTTP_COOKIE_VARS;
	$_POST = &$HTTP_POST_VARS;
	$_GET = &$HTTP_GET_VARS;
}
$EGPCS = array(
		'_ENV',
		'_SERVER',
		'_FILES',
		'_COOKIE',
		'_POST',
		'_GET'
		);

@extract($_POST);
@extract($_GET);

include_once('globalinits.php');
if (is_readable('multisite.php'))
	include_once('multisite.php');

$DontNeedDatabase = array(
	'phpinfo',
	'info',
	'image',
	'smallupdate',
	'update',
	'CompleteUpdate',
	'upload',
	'uploadxml',
	'GimmeAFrontThumb',
	'HealthCheck',
	'UpdateStatus',
	'UpdateStats'
);

if (isset($_SERVER['HTTP_CLIENT_IP']))
	$remote_ip = $_SERVER['HTTP_CLIENT_IP'];
else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	$remote_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
else if (isset($_SERVER['REMOTE_ADDR']))
	$remote_ip = $_SERVER['REMOTE_ADDR'];
else
	$remote_ip = '';

include_once('siteconfig.php');
if (is_readable($localsiteconfig))
	include_once($localsiteconfig);

if ($InitialRightFrame == '')
	if (isset($StatisticsOnFrameInit) && $StatisticsOnFrameInit)
		$InitialRightFrame = 'Statistics';

if (!$AllowProfileLabelsToWrap) {
	$plwrapf = '<NOBR>';
	$plwrapb = '</NOBR>';
}
$reviewgraph = strtoupper($reviewgraph);
$reviewsort = strtoupper($reviewsort);
$genrecombo = true;
$tagcombo = true;
$lockcombo = true;
$purchasecombo = true;
$localecombo = true;
$mediatypecombo = true;

if (!isset($img_webpathf))
	$img_webpathf = $img_webpath;
if (!isset($img_webpathb))
	$img_webpathb = $img_webpath;

if (!is_readable($img_physpath.$thumbnails))
	if (is_readable($img_physpath.'Thumbnails'))
		$thumbnails = 'Thumbnails';

if ($TopX <= 0) $TopX = 1;
if ($TopX > $MaxX) $TopX = $MaxX;

if ($colnorange < 0) $colnorange = 0;
if ($colnorange > 200000) $colnorange = 200000;

$IsPrivate = false;
$arrlen = count($local_lan);
for ($i=0; $i<$arrlen; $i++) {
	if (strncmp($remote_ip, $local_lan[$i], strlen($local_lan[$i]))==0) {
		$IsPrivate = true;
		break;
	}
}
if (@$_SERVER['REMOTE_ADDR'] == @$_SERVER['SERVER_ADDR'])
	$IsPrivate = true;
unset($local_lan);
////$IsPrivate = false;

if (isset($_COOKIE['debugskin']))
	$debugskin = true;

$siteactorsort		= $actorsort;
$sitesecondcol		= $secondcol;
$sitethirdcol		= $thirdcol;
$sitedefaultsorttype	= $defaultsorttype;
$sitetitledesc		= $titledesc;
$sitelocale		= $locale;
$sitestickyboxsets	= $stickyboxsets;
$sitepopupimages	= $popupimages;
$siteskinfile		= $skinfile;

$defaultorder		= array(
	'none'			=> 'asc',
	'sorttitle'		=> 'asc',
	'loaninfo'		=> 'asc',
	'loandue'		=> 'asc',
	'productionyear'	=> 'asc',
	'released'		=> 'asc',
	'collectionnumber'	=> 'desc',
	'purchasedate'		=> 'desc',
	'runningtime'		=> 'asc',
	'rating'		=> 'asc',
	'reviews'		=> 'desc',
	'timestamp'		=> 'desc',
	'director'		=> 'asc',
	'genres'		=> 'asc'
);
$ReviewLabels		= array(
	'F'			=> 'reviewfilm',
	'V'			=> 'reviewvideo',
	'A'			=> 'reviewaudio',
	'E'			=> 'reviewextras'
);

if ($allowtitlesperpage && isset($_COOKIE['titlesperpage'])) {
	$tmp = $_COOKIE['titlesperpage'];
	if (preg_match('/\d+/', $tmp) != 0)
		$TitlesPerPage = $tmp;
}

if ($allowactorsort && isset($_COOKIE['actorsort'])) {
	$tmp = $_COOKIE['actorsort'];
	if (($tmp == 0) || ($tmp == 1) || ($tmp == 2))
		$actorsort = $tmp;
}

if ($allowsecondcol && isset($_COOKIE['secondcol'])) {
	$tmp = $_COOKIE['secondcol'];
	if (($tmp == 'released') ||
	    ($tmp == 'productionyear') ||
	    ($tmp == 'purchasedate') ||
	    ($tmp == 'collectionnumber') ||
	    ($tmp == 'runningtime') ||
	    ($tmp == 'rating') ||
	    ($tmp == 'genres') ||
	    ($tmp == 'reviews') ||
	    ($tmp == 'director') ||
	    ($tmp == 'none'))
		$secondcol = $tmp;
}

if ($allowthirdcol && isset($_COOKIE['thirdcol'])) {
	$tmp = $_COOKIE['thirdcol'];
	if (($tmp == 'released') ||
	    ($tmp == 'productionyear') ||
	    ($tmp == 'purchasedate') ||
	    ($tmp == 'collectionnumber') ||
	    ($tmp == 'runningtime') ||
	    ($tmp == 'rating') ||
	    ($tmp == 'genres') ||
	    ($tmp == 'reviews') ||
	    ($tmp == 'director') ||
	    ($tmp == 'none'))
		$thirdcol = $tmp;
}

if ($allowdefaultsorttype && isset($_COOKIE['defaultsorttype'])) {
	$tmp = $_COOKIE['defaultsorttype'];
	if (($tmp == 'firstcol') || ($tmp == 'secondcol') || ($tmp == 'thirdcol'))
		$defaultsorttype = $tmp;
}
if (($defaultsorttype == 'secondcol') && ($secondcol == 'none'))
	$defaultsorttype = 'firstcol';
if (($defaultsorttype == 'thirdcol') && ($thirdcol == 'none'))
	$defaultsorttype = 'firstcol';

if ($allowtitledesc && isset($_COOKIE['titledesc'])) {
	$tmp = $_COOKIE['titledesc'];
	if (($tmp == 0) || ($tmp == 1) || ($tmp == 2) || ($tmp == 3))
		$titledesc = $tmp;
}

if ($allowstickyboxsets && isset($_COOKIE['stickyboxsets'])) {
	$tmp = $_COOKIE['stickyboxsets'];
	if ($tmp == '0')
		$stickyboxsets = false;
	else
		$stickyboxsets = true;
}

if ($allowpopupimages && isset($_COOKIE['popupimages'])) {
	$tmp = $_COOKIE['popupimages'];
	if ($tmp == '0')
		$popupimages = false;
	else
		$popupimages = true;
}
if ($allowskins && isset($_COOKIE['skinfile'])) {
	$tmp = rawurldecode($_COOKIE['skinfile']);
	if ($tmp != 'internal') {
		list($skinloc, $skinfile) = explode('/', $tmp);
		$skinloc = "skins/$skinloc";
	}
	else {
		$skinfile = 'internal';
		$skinloc = '';
	}
}
if ($skinfile != 'internal') {
	if (!is_readable("$skinloc/$skinfile")) {
		$skinfile = 'internal';
		$skinloc = '';
	}
}

include_once('locale.php');

$CurrentSiteTitle = $sitetitle;
if (isset($sitetitle_translation[$locale]))
	$CurrentSiteTitle = $sitetitle_translation[$locale];

$genre_translation = array(
	'Uncategorized' => $lang['GENRELIST']['UNCATEGORIZED'],
	'Accessories' => $lang['GENRELIST']['ACCESSORIES'],
	'Action' => $lang['GENRELIST']['ACTION'],
	'Adult' => $lang['GENRELIST']['ADULT'],
	'Adventure' => $lang['GENRELIST']['ADVENTURE'],
	'Animation' => $lang['GENRELIST']['ANIMATION'],
	'Anime' => $lang['GENRELIST']['ANIME'],
   	"Children's" => $lang['GENRELIST']['CHILDRENS'],
	'Classic' => $lang['GENRELIST']['CLASSIC'],
	'Comedy' => $lang['GENRELIST']['COMEDY'],
   	'Crime' => $lang['GENRELIST']['CRIME'],
   	'Disaster' => $lang['GENRELIST']['DISASTER'],
	'Documentary' => $lang['GENRELIST']['DOCUMENTARY'],
	'Drama' => $lang['GENRELIST']['DRAMA'],
	'Family' => $lang['GENRELIST']['FAMILY'],
	'Fantasy' => $lang['GENRELIST']['FANTASY'],
   	'Film Noir' => $lang['GENRELIST']['FILMNOIR'],
//	'Foreign' => $lang['GENRELIST']['FOREIGN'],
	'Horror' => $lang['GENRELIST']['HORROR'],
	'Martial Arts' => $lang['GENRELIST']['MARTIALARTS'],
	'Music' => $lang['GENRELIST']['MUSIC'],
	'Musical' => $lang['GENRELIST']['MUSICAL'],
	'Romance' => $lang['GENRELIST']['ROMANCE'],
	'Science-Fiction' => $lang['GENRELIST']['SCIENCEFICTION'],
	'Special Interest' => $lang['GENRELIST']['SPECIALINTEREST'],
	'Sports' => $lang['GENRELIST']['SPORTS'],
	'Suspense/Thriller' => $lang['GENRELIST']['SUSPENSETHRILLER'],
	'Television' => $lang['GENRELIST']['TELEVISION'],
	'War' => $lang['GENRELIST']['WAR'],
	'Western' => $lang['GENRELIST']['WESTERN']
);

$lock_translation = array(
	'Entire' => $lang['LOCKS']['ENTIRE'],
	'Covers' => $lang['LOCKS']['COVERS'],
	'Title' => $lang['LOCKS']['TITLE'],
	'MediaType' => $lang['LOCKS']['MEDIATYPE'],
	'Overview' => $lang['LOCKS']['OVERVIEW'],
	'Regions' => $lang['LOCKS']['REGIONS'],
	'Genres' => $lang['LOCKS']['GENRES'],
	'SRP' => $lang['LOCKS']['SRP'],
	'Studios' => $lang['LOCKS']['STUDIOS'],
	'Discinfo' => $lang['LOCKS']['DISCINFO'],
	'Cast' => $lang['LOCKS']['CAST'],
	'Crew' => $lang['LOCKS']['CREW'],
	'Features' => $lang['LOCKS']['FEATURES'],
	'Audio' => $lang['LOCKS']['AUDIO'],
	'Subtitles' => $lang['LOCKS']['SUBTITLES'],
	'EasterEggs' => $lang['LOCKS']['EASTEREGGS'],
	'RunningTime' => $lang['LOCKS']['RUNNINGTIME'],
	'ReleaseDate' => $lang['LOCKS']['RELEASEDATE'],
	'ProductionYear' => $lang['LOCKS']['PRODUCTIONYEAR'],
	'CaseType' => $lang['LOCKS']['CASETYPE'],
	'VideoFormats' => $lang['LOCKS']['VIDEOFORMATS'],
	'Rating' => $lang['LOCKS']['RATING']
);

$CountryToLocality = array(
	'United States'		=> '0',
	'New Zealand'		=> '1',
	'Australia'		=> '2',
	'Canada'		=> '3',
	'United Kingdom'	=> '4',
	'Germany'		=> '5',
	'China'			=> '6',
	'Former Soviet Union'	=> '7',
	'France'		=> '8',
	'Netherlands'		=> '9',
	'Spain'			=> '10',
	'Sweden'		=> '11',
	'Norway'		=> '12',
	'Italy'			=> '13',
	'Denmark'		=> '14',
	'Portugal'		=> '15',
	'Finland'		=> '16',
	'Japan'			=> '17',
	'South Korea'		=> '18',
	'Canada (Quebec)'	=> '19',
	'South Africa'		=> '20',
	'Hong Kong'		=> '21',
	'Switzerland'		=> '22',
	'Brazil'		=> '23',
	'Israel'		=> '24',
	'Mexico'		=> '25',
	'Iceland'		=> '26',
	'Indonesia'		=> '27',
	'Taiwan'		=> '28',
	'Poland'		=> '29',
	'Belgium'		=> '30',
	'Turkey'		=> '31',
	'Argentina'		=> '32',
	'Slovakia'		=> '33',
	'Hungary'		=> '34',
	'Singapore'		=> '35',
	'Czech Republic'	=> '36',
	'Malaysia'		=> '37',
	'Thailand'		=> '38',
	'India'			=> '39',
	'Austria'		=> '40',
	'Greece'		=> '41',
	'Vietnam'		=> '42',
	'Philippines'		=> '43',
	'Ireland'		=> '44',
	'Estonia'		=> '45',
	'Romania'		=> '46',
	'Iran'			=> '47',
	'Russia'		=> '48',
	'Chile'			=> '49',
	'Columbia'		=> '50',
	'Peru'			=> '51'
);

// http://www.kiddiealert.com/index.php/account/images/flags/
$alang_translation = array(
	''			=> '<img src="gfx/ukn.png" alt=""/> '.$lang['AUDIO'][''],
	'Afrikaans'		=> '<img src="gfx/za.png" alt=""/> '.$lang['AUDIO']['AFRIKAANS'],
	'Arabic'		=> '<img src="gfx/sa.png" alt=""/> '.$lang['AUDIO']['ARABIC'],
	'Audio Descriptive'	=> '<img src="gfx/ukn.png" alt=""/> '.$lang['AUDIO']['AUDIODESCRIPTIVE'],
	'Bahasa'		=> '<img src="gfx/id.png" alt=""/> '.$lang['AUDIO']['BAHASA'],			// Indonesia/Malay
	'Bambara'		=> '<img src="gfx/ml.png" alt=""/> '.$lang['AUDIO']['BAMBARA'],			// Mali
	'Basque'		=> '<img src="gfx/es.png" alt=""/> '.$lang['AUDIO']['BASQUE'],			// Spain
	'Bulgarian'		=> '<img src="gfx/hu.png" alt=""/> '.$lang['AUDIO']['BULGARIAN'],
	'Cantonese'		=> '<img src="gfx/cn.png" alt=""/> '.$lang['AUDIO']['CANTONESE'],
	'Catalonian'		=> '<img src="gfx/es.png" alt=""/> '.$lang['AUDIO']['CATALONIAN'],		// Spain
	'Chinese'		=> '<img src="gfx/cn.png" alt=""/> '.$lang['AUDIO']['CHINESE'],
	'Commentary'		=> '<img src="gfx/com.png" alt=""/> '.$lang['AUDIO']['COMMENTARY'],
	'Croatian'		=> '<img src="gfx/hr.png" alt=""/> '.$lang['AUDIO']['CROATIAN'],
	'Czech'			=> '<img src="gfx/cz.png" alt=""/> '.$lang['AUDIO']['CZECH'],
	'Danish'		=> '<img src="gfx/dk.png" alt=""/> '.$lang['AUDIO']['DANISH'],
	'Dutch'			=> '<img src="gfx/nl.png" alt=""/> '.$lang['AUDIO']['DUTCH'],
	'English'		=> '<img src="gfx/en.png" alt=""/> '.$lang['AUDIO']['ENGLISH'],
	'Estonian'		=> '<img src="gfx/ee.png" alt=""/> '.$lang['AUDIO']['ESTONIAN'],
	'Farsi'			=> '<img src="gfx/ir.png" alt=""/> '.$lang['AUDIO']['FARSI'],
	'Finnish'		=> '<img src="gfx/fi.png" alt=""/> '.$lang['AUDIO']['FINNISH'],
	'Flemish'		=> '<img src="gfx/be.png" alt=""/> '.$lang['AUDIO']['FLEMISH'],			// Belgium
	'French'		=> '<img src="gfx/fr.png" alt=""/> '.$lang['AUDIO']['FRENCH'],
	'Galician'		=> '<img src="gfx/es.png" alt=""/> '.$lang['AUDIO']['GALICIAN'],		// Spain
	'Georgian'		=> '<img src="gfx/de.png" alt=""/> '.$lang['AUDIO']['GEORGIAN'],
	'German'		=> '<img src="gfx/de.png" alt=""/> '.$lang['AUDIO']['GERMAN'],
	'Greek'			=> '<img src="gfx/gr.png" alt=""/> '.$lang['AUDIO']['GREEK'],
	'Hebrew'		=> '<img src="gfx/il.png" alt=""/> '.$lang['AUDIO']['HEBREW'],
	'Hindi'			=> '<img src="gfx/in.png" alt=""/> '.$lang['AUDIO']['HINDI'],
	'Hungarian'		=> '<img src="gfx/hu.png" alt=""/> '.$lang['AUDIO']['HUNGARIAN'],
	'Icelandic'		=> '<img src="gfx/is.png" alt=""/> '.$lang['AUDIO']['ICELANDIC'],
	'Italian'		=> '<img src="gfx/it.png" alt=""/> '.$lang['AUDIO']['ITALIAN'],
	'Japanese'		=> '<img src="gfx/jp.png" alt=""/> '.$lang['AUDIO']['JAPANESE'],
	'Korean'		=> '<img src="gfx/kr.png" alt=""/> '.$lang['AUDIO']['KOREAN'],
	'Latvian'		=> '<img src="gfx/lv.png" alt=""/> '.$lang['AUDIO']['LATVIAN'],
	'Lithuanian'		=> '<img src="gfx/lt.png" alt=""/> '.$lang['AUDIO']['LITHUANIAN'],
	'Mandarin'		=> '<img src="gfx/cn.png" alt=""/> '.$lang['AUDIO']['MANDARIN'],
	'Mongolian'		=> '<img src="gfx/mn.png" alt=""/> '.$lang['AUDIO']['MONGOLIAN'],
	'Music Only'		=> '<img src="gfx/musik.png" alt=""/> '.$lang['AUDIO']['MUSICONLY'],
	'Norwegian'		=> '<img src="gfx/no.png" alt=""/> '.$lang['AUDIO']['NORWEGIAN'],
	'Other'			=> '<img src="gfx/ukn.png" alt=""/> '.$lang['AUDIO']['OTHER'],
	'Pashtu'		=> '<img src="gfx/af.png" alt=""/> '.$lang['AUDIO']['PASHTU'],			// Afghanistan
	'Polish'		=> '<img src="gfx/pl.png" alt=""/> '.$lang['AUDIO']['POLISH'],
	'Portuguese'		=> '<img src="gfx/pt.png" alt=""/> '.$lang['AUDIO']['PORTUGUESE'],
	'Romanian'		=> '<img src="gfx/ro.png" alt=""/> '.$lang['AUDIO']['ROMANIAN'],
	'Rumantsch'		=> '<img src="gfx/ch.png" alt=""/> '.$lang['AUDIO']['RUMANTSCH'],		// Switzerland
	'Russian'		=> '<img src="gfx/ru.png" alt=""/> '.$lang['AUDIO']['RUSSIAN'],
	'Serbian'		=> '<img src="gfx/rs.png" alt=""/> '.$lang['AUDIO']['SERBIAN'],
	'Slovak'		=> '<img src="gfx/sk.png" alt=""/> '.$lang['AUDIO']['SLOVAK'],
	'Slovakian'		=> '<img src="gfx/sk.png" alt=""/> '.$lang['AUDIO']['SLOVAKIAN'],
	'Slovenian'		=> '<img src="gfx/si.png" alt=""/> '.$lang['AUDIO']['SLOVENIAN'],
	'Spanish'		=> '<img src="gfx/es.png" alt=""/> '.$lang['AUDIO']['SPANISH'],
	'Special Effects'	=> '<img src="gfx/ukn.png" alt=""/> '.$lang['AUDIO']['SPECIALEFFECTS'],
	'Swedish'		=> '<img src="gfx/se.png" alt=""/> '.$lang['AUDIO']['SWEDISH'],
	'Swiss German'		=> '<img src="gfx/ch.png" alt=""/> '.$lang['AUDIO']['SWISSGERMAN'],		// Switzerland
	'Tagalog'		=> '<img src="gfx/ph.png" alt=""/> '.$lang['AUDIO']['TAGALOG'],
	'Thai'			=> '<img src="gfx/th.png" alt=""/> '.$lang['AUDIO']['THAI'],
	'Tibetan'		=> '<img src="gfx/cn.png" alt=""/> '.$lang['AUDIO']['TIBETAN'],			// China
	'Tjeckish'		=> '<img src="gfx/cz.png" alt=""/> '.$lang['AUDIO']['TJECKISH'],
	'Trivia'		=> '<img src="gfx/com.png" alt=""/> '.$lang['AUDIO']['TRIVIA'],
	'Turkish'		=> '<img src="gfx/tr.png" alt=""/> '.$lang['AUDIO']['TURKISH'],
	'Valencian'		=> '<img src="gfx/es.png" alt=""/> '.$lang['AUDIO']['VALENCIAN'],		// Spain
	'Vietnamese'		=> '<img src="gfx/vn.png" alt=""/> '.$lang['AUDIO']['VIETNAMESE'],
	'Xhosa'			=> '<img src="gfx/za.png" alt=""/> '.$lang['AUDIO']['XHOSA'],
	'Zulu'			=> '<img src="gfx/za.png" alt=""/> '.$lang['AUDIO']['ZULU']
);

$dynamicrange_translation = array(
	'' => $lang[''],
	'HDR10' => '<acronym title="High-Dynamic Range 10 Media Profile" style="text-decoration:underline">' . $lang['HDR10'] . '</acronym>',
	'DOLBYVISION' => $lang['DOLBYVISION']
);

$aformat_translation = array(
	'' => $lang[''],
/***/	'Auro-3D' => '<acronym title="'.$lang['AUDIO']['AURO-3D'].'" style="text-decoration:underline"><img src="gfx/Auro3d.png"></acronym>',
/***/	'Dolby Atmos' => '<acronym title="'.$lang['AUDIO']['DA'].'" style="text-decoration:underline"><img src="gfx/DolbyAtmos.jpg"></acronym>',
    'Dolby Digital' => '<acronym title="'.$lang['AUDIO']['DD'].'" style="text-decoration:underline"><img src="gfx/DolbyDigital.jpg"></acronym>',
	'Dolby Digital Mono' => '<acronym title="'.$lang['AUDIO']['DD'].'" style="text-decoration:underline"><img src="gfx/DolbyDigital.jpg"></acronym>',
	'Dolby Digital Stereo' => '<acronym title="'.$lang['AUDIO']['DD'].'" style="text-decoration:underline"><img src="gfx/DolbyDigital.jpg"></acronym>',
	'Dolby Digital Surround' => '<acronym title="'.$lang['AUDIO']['DD'].'" style="text-decoration:underline"><img src="gfx/DolbyDigital.jpg"></acronym>',
	'Dolby Digital 4.0' => '<acronym title="'.$lang['AUDIO']['DD'].'" style="text-decoration:underline"><img src="gfx/DolbyDigital.jpg"></acronym>',
	'Dolby Digital 5.0' => '<acronym title="'.$lang['AUDIO']['DD'].'" style="text-decoration:underline"><img src="gfx/DolbyDigital.jpg"></acronym>',
	'Dolby Digital 5.1' => '<acronym title="'.$lang['AUDIO']['DD'].'" style="text-decoration:underline"><img src="gfx/DolbyDigital.jpg"></acronym>',
/***/	'Dolby Digital EX' => '<acronym title="'.$lang['AUDIO']['DD'].'" style="text-decoration:underline"><img src="gfx/ddex_logo.jpg"></acronym>',
	'Dolby Digital Surround EX' => '<acronym title="'.$lang['AUDIO']['DD'].'" style="text-decoration:underline"><img src="gfx/DolbyDigital.jpg"></acronym>',
	'Dolby Digital Plus' => '<acronym title="'.$lang['AUDIO']['DD'].'" style="text-decoration:underline"><img src="gfx/dolby-digital-plus.jpg"></acronym>',
/***/	'Dolby TrueHD' => '<acronym title="'.$lang['AUDIO']['DD'].'" style="text-decoration:underline"><img src="gfx/DolbyDigitalTrueHD.jpg"></acronym>',
	'Dolby Digital TrueHD' => '<acronym title="'.$lang['AUDIO']['DD'].'" style="text-decoration:underline"><img src="gfx/DolbyDigitalTrueHD.jpg"></acronym>',
/***/	'DTS' => '<acronym title="'.$lang['AUDIO']['DTS'].'" style="text-decoration:underline"><img src="gfx/newdts.jpg"></acronym>',
/**/	'DTS 5.0' => '<acronym title="'.$lang['AUDIO']['DTS'].'" style="text-decoration:underline"><img src="gfx/newdts.jpg"></acronym>',
	'DTS 5.1' => '<acronym title="'.$lang['AUDIO']['DTS'].'" style="text-decoration:underline"><img src="gfx/newdts.jpg"></acronym>',
/***/	'DTS ES' => '<acronym title="'.$lang['AUDIO']['DTS'].'" style="text-decoration:underline"><img src="gfx/dts-es.jpg"></acronym>',
	'DTS ES (Matrixed)' => '<acronym title="'.$lang['AUDIO']['DTS'].'" style="text-decoration:underline"><img src="gfx/dts-es.jpg"></acronym>',
	'DTS ES (Discrete)' => '<acronym title="'.$lang['AUDIO']['DTS'].'" style="text-decoration:underline"><img src="gfx/dts-es.jpg"></acronym>',
	'DTS HD' => '<acronym title="'.$lang['AUDIO']['DTS'].'" style="text-decoration:underline">DTS</acronym>',
/***/	'DTS-HD High Resolution' => '<acronym title="'.$lang['AUDIO']['DTS'].'" style="text-decoration:underline"><img src="gfx/DTS-HD_HR.jpg"></acronym>',
/***/	'DTS-HD Master Audio' => '<acronym title="'.$lang['AUDIO']['DTS'].'" style="text-decoration:underline"><img src="gfx/DTS-HDMasterAudio.jpg"></acronym>',
/**/	'DTS HD HR' => '<acronym title="'.$lang['AUDIO']['DTS'].'" style="text-decoration:underline"><img src="gfx/DTS-HD_HR.jpg"></acronym>',
/**/	'DTS HD Master Audio' => '<acronym title="'.$lang['AUDIO']['DTS'].'" style="text-decoration:underline"><img src="gfx/DTS-HDMasterAudio.jpg"></acronym>',
    'DTS-X' => '<acronym title="'.$lang['AUDIO']['DTS'].'" style="text-decoration:underline"><img src="gfx/DTS-X.gif"></acronym>',
	'PCM Stereo' => '<acronym title="'.$lang['AUDIO']['PCM'].'" style="text-decoration:underline"><img src="gfx/pcm_logo.jpg"></acronym>',
/***/	'PCM' => '<acronym title="'.$lang['AUDIO']['PCM'].'" style="text-decoration:underline"><img src="gfx/pcm_logo.jpg"></acronym>',
/**/	'PCM 5.0' => '<acronym title="'.$lang['AUDIO']['PCM'].'" style="text-decoration:underline"><img src="gfx/pcm_logo.jpg"></acronym>',
	'PCM 5.1' => '<acronym title="'.$lang['AUDIO']['PCM'].'" style="text-decoration:underline"><img src="gfx/pcm_logo.jpg"></acronym>',
/**/	'PCM 6.1' => '<acronym title="'.$lang['AUDIO']['PCM'].'" style="text-decoration:underline"><img src="gfx/pcm_logo.jpg"></acronym>',
/**/	'PCM 7.1' => '<acronym title="'.$lang['AUDIO']['PCM'].'" style="text-decoration:underline"><img src="gfx/pcm_logo.jpg"></acronym>',
	'MPEG-1 Audio Layer II (MP2)'	=>  '<acronym title="'.$lang['AUDIO']['MPEG'].'" style="text-decoration:underline"><img src="gfx/mpeg2_logo.jpg"></acronym>',
	'MPEG-2' => '<acronym title="'.$lang['AUDIO']['MPEG'].'" style="text-decoration:underline"><img src="gfx/mpeg2_logo.jpg"></acronym>',
/**/	'MPEG-2 Mono' => '<acronym title="'.$lang['AUDIO']['MPEG'].'" style="text-decoration:underline"><img src="gfx/mpeg2_logo.jpg"></acronym>',
/**/	'MPEG-2 2.0' => '<acronym title="'.$lang['AUDIO']['MPEG'].'" style="text-decoration:underline"><img src="gfx/mpeg2_logo.jpg"></acronym>',
/**/	'MPEG-2 Surround' => '<acronym title="'.$lang['AUDIO']['MPEG'].'" style="text-decoration:underline"><img src="gfx/mpeg2_logo.jpg"></acronym>',
	'Other' => $lang['OTHER']
);
$aformat_name = array (
	''				=> $lang[''],
/***/	'Auro-3D'			=> $lang['AUDIO']['AURO-3D'],
/***/	'Dolby Atmos'			=> $lang['AUDIO']['DA'],
    'Dolby Digital'			=> $lang['AUDIO']['DD'],
	'Dolby Digital Mono'		=> $lang['AUDIO']['DD'].' '.$lang['AUDIO']['MONO'],
	'Dolby Digital Stereo'		=> $lang['AUDIO']['DD'].' '.$lang['AUDIO']['STEREO'],
	'Dolby Digital Surround'	=> $lang['AUDIO']['DD'].' '.$lang['AUDIO']['SURROUND'],
	'Dolby Digital 4.0'		=> $lang['AUDIO']['DD'].' 4.0',
	'Dolby Digital 5.0'		=> $lang['AUDIO']['DD'].' 5.0',
	'Dolby Digital 5.1'		=> $lang['AUDIO']['DD'].' 5.1',
/***/	'Dolby Digital EX'		=> $lang['AUDIO']['DD'].' '.$lang['AUDIO']['EX'],
	'Dolby Digital Surround EX'	=> $lang['AUDIO']['DD'].' '.$lang['AUDIO']['SURROUNDEX'],
	'Dolby Digital Plus'		=> $lang['AUDIO']['DD'].' '.$lang['AUDIO']['PLUS'],
/***/	'Dolby TrueHD'			=> $lang['AUDIO']['DD'].' '.$lang['AUDIO']['TRUEHD'],
	'Dolby Digital TrueHD'		=> $lang['AUDIO']['DD'].' '.$lang['AUDIO']['TRUEHD'],
/***/	'DTS'				=> 'DTS',
/**/	'DTS 5.0'			=> 'DTS 5.0',
	'DTS 5.1'			=> 'DTS 5.1',
/***/	'DTS ES'			=> 'DTS '.$lang['AUDIO']['ES'],
	'DTS ES (Matrixed)'		=> 'DTS '.$lang['AUDIO']['ESMATRIX'],
	'DTS ES (Discrete)'		=> 'DTS '.$lang['AUDIO']['ESDISCRETE'],
	'DTS HD'			=> 'DTS '.$lang['AUDIO']['HD'],
/***/	'DTS-HD High Resolution'	=> 'DTS '.$lang['AUDIO']['HDHR'],
/**/	'DTS HD HR'			=> 'DTS '.$lang['AUDIO']['HDHR'],
/***/	'DTS-HD Master Audio'		=> 'DTS '.$lang['AUDIO']['HDMASTERAUDIO'],
/**/	'DTS HD Master Audio'		=> 'DTS '.$lang['AUDIO']['HDMASTERAUDIO'],
	'DTS-X'					=> 'DTS-X',
/***/	'PCM'				=> 'PCM',
	'PCM Stereo'			=> 'PCM '.$lang['AUDIO']['STEREO'],
/**/	'PCM 5.0'			=> 'PCM 5.0',
	'PCM 5.1'			=> 'PCM 5.1',
/**/	'PCM 6.1'			=> 'PCM '.$lang['AUDIO']['6.1'],
/**/	'PCM 7.1'			=> 'PCM 7.1',
	'MPEG-1 Audio Layer II (MP2)'	=> 'MPEG-1 '.$lang['AUDIO']['MP2'],
	'MPEG-2'			=> 'MPEG-2',
/**/	'MPEG-2 Mono'			=> 'MPEG-2 '.$lang['AUDIO']['MONO'],
/**/	'MPEG-2 2.0'			=> 'MPEG-2 2.0',
/**/	'MPEG-2 Surround'		=> 'MPEG-2 '.$lang['AUDIO']['SURROUND'],
	'Other'				=> $lang['OTHER']
);
/*
3.6 - has
fmt = Dolby Digital, DTS, Dolby Digital EX, DTS ES, Dolby Digital Plus, Dolby TrueHD, DTS-HD High Resolution, DTS-HD Master Audio, PCM, MPEG-2
chn = Mono, 2-Channel Stereo, Dolby Surround, 4.0, 4.1, 5.0, 5.1, 5.1 (Matrixed 6.1), 6.1 (Discrete), 7.1
*/
$newachan_name = array(
	''			=> $lang[''],
	'Mono'			=> $lang['AUDIO']['MONO'],
	'2-Channel Stereo'	=> $lang['AUDIO']['2CHANNELSTEREO'],
	'Dolby Surround'	=> $lang['AUDIO']['SURROUND'],
	'3.0'			=> '3.0',
	'3.1'			=> '3.1',
	'4.0'			=> '4.0',
	'4.1'			=> '4.1',
	'5.0'			=> '5.0',
	'5.1'			=> '5.1',
	'5.1 (Matrixed 6.1)'	=> $lang['AUDIO']['5.1M'],
	'6.1 (Discrete)'	=> $lang['AUDIO']['6.1'],
	'7.1'			=> '7.1',
	'3D'			=> $lang['AUDIO']['3D']
);
$newaformat_image = array(
	''			=> '<img src="gfx/dd00.gif" class="audioimage" title="'.$lang[''].'" alt=""/>&nbsp;',
	'Mono'			=> '<img src="gfx/dd10.gif" class="audioimage" title="'.$lang['AUDIO']['MONO'].'" alt=""/>&nbsp;',
	'2-Channel Stereo'	=> '<img src="gfx/dd20.gif" class="audioimage" title="'.$lang['AUDIO']['2CHANNELSTEREO'].'" alt=""/>&nbsp;',
	'Dolby Surround'	=> '<img src="gfx/dd21.gif" class="audioimage" title="'.$lang['AUDIO']['SURROUND'].'" alt=""/>&nbsp;',
	'3.0'			=> '<img src="gfx/dd40.gif" class="audioimage" title="3.0" alt=""/>&nbsp;',
	'3.1'			=> '<img src="gfx/dd40.gif" class="audioimage" title="3.1" alt=""/>&nbsp;',
	'4.0'			=> '<img src="gfx/dd40.gif" class="audioimage" title="4.0" alt=""/>&nbsp;',
	'4.1'			=> '<img src="gfx/dd41.gif" class="audioimage" title="4.1" alt=""/>&nbsp;',
	'5.0'			=> '<img src="gfx/dd50.gif" class="audioimage" title="5.0" alt=""/>&nbsp;',
	'5.1'			=> '<img src="gfx/dd51.gif" class="audioimage" title="5.1" alt=""/>&nbsp;',
	'5.1 (Matrixed 6.1)'	=> '<img src="gfx/dd52.gif" class="audioimage" title="'.$lang['AUDIO']['5.1M'].'" alt=""/>&nbsp;',
	'6.1 (Discrete)'	=> '<img src="gfx/dd61.gif" class="audioimage" title="'.$lang['AUDIO']['6.1'].'" alt=""/>&nbsp;',
	'7.1'			=> '<img src="gfx/dd71.gif" class="audioimage" title="7.1" alt=""/>&nbsp;',
	'3D'			=> '<img src="gfx/dd3d.gif" class="audioimage" title="'.$lang['AUDIO']['3D'].'" alt=""/>&nbsp;'
);
$aformat_image = array(
	''				=> $lang['AUDIO'][''],
	'Auro-3D'		=> '<img src="gfx/dd3d.gif" class="audioimage" title="'.$lang['AUDIO']['AURO-3D'].' '.$lang['AUDIO']['3D'].'" alt=""/>&nbsp;'.$lang['AUDIO']['AURO-3D'].' '.$lang['AUDIO']['3D'],
	'Dolby Atmos'		=> '<img src="gfx/dd3d.gif" class="audioimage" title="'.$lang['AUDIO']['DA'].' '.$lang['AUDIO']['3D'].'" alt=""/>&nbsp;'.$lang['AUDIO']['DA'].' '.$lang['AUDIO']['3D'],
	'Dolby Digital Mono'		=> '<img src="gfx/dd10.gif" class="audioimage" title="'.$lang['AUDIO']['DD'].' '.$lang['AUDIO']['MONO'].'" alt=""/>&nbsp;'.$lang['AUDIO']['DD'].' '.$lang['AUDIO']['MONO'],
	'Dolby Digital Stereo'		=> '<img src="gfx/dd20.gif" class="audioimage" title="'.$lang['AUDIO']['DD'].' '.$lang['AUDIO']['STEREO'].'" alt=""/>&nbsp;'.$lang['AUDIO']['DD'].' '.$lang['AUDIO']['STEREO'],
	'Dolby Digital Surround'	=> '<img src="gfx/dd30.gif" class="audioimage" title="'.$lang['AUDIO']['DD'].' '.$lang['AUDIO']['SURROUND'].'" alt=""/>&nbsp;'.$lang['AUDIO']['DD'].' '.$lang['AUDIO']['SURROUND'],
	'Dolby Digital 4.0'		=> '<img src="gfx/dd40.gif" class="audioimage" title="'.$lang['AUDIO']['DD'].' 4.0" alt=""/>&nbsp;'.$lang['AUDIO']['DD'].' 4.0',
	'Dolby Digital 5.0'		=> '<img src="gfx/dd50.gif" class="audioimage" title="'.$lang['AUDIO']['DD'].' 5.0" alt=""/>&nbsp;'.$lang['AUDIO']['DD'].' 5.0',
	'Dolby Digital 5.1'		=> '<img src="gfx/dd51.gif" class="audioimage" title="'.$lang['AUDIO']['DD'].' 5.1" alt=""/>&nbsp;'.$lang['AUDIO']['DD'].' 5.1',
	'Dolby Digital Surround EX'	=> '<img src="gfx/dd61.gif" class="audioimage" title="'.$lang['AUDIO']['DD'].' '.$lang['AUDIO']['SURROUNDEX'].'" alt=""/>&nbsp;'.$lang['AUDIO']['DD'].' '.$lang['AUDIO']['SURROUNDEX'],
	'Dolby Digital Plus'		=> '<img src="gfx/dd71.gif" class="audioimage" title="'.$lang['AUDIO']['DD'].' '.$lang['AUDIO']['PLUS'].'" alt=""/>&nbsp;'.$lang['AUDIO']['DD'].' '.$lang['AUDIO']['PLUS'],
	'Dolby Digital TrueHD'		=> '<img src="gfx/dd71.gif" class="audioimage" title="'.$lang['AUDIO']['DD'].' '.$lang['AUDIO']['TRUEHD'].'" alt=""/>&nbsp;'.$lang['AUDIO']['DD'].' '.$lang['AUDIO']['TRUEHD'],
/**/	'DTS 5.0'			=> '<img src="gfx/dd50.gif" class="audioimage" title="DTS 5.0" alt=""/>&nbsp;DTS 5.0',
	'DTS 5.1'			=> '<img src="gfx/dd51.gif" class="audioimage" title="DTS 5.1" alt=""/>&nbsp;DTS 5.1',
	'DTS ES (Matrixed)'		=> '<img src="gfx/dd61.gif" class="audioimage" title="DTS '.$lang['AUDIO']['ESMATRIX'].'" alt=""/>&nbsp;DTS '.$lang['AUDIO']['ESMATRIX'],
	'DTS ES (Discrete)'		=> '<img src="gfx/dd71.gif" class="audioimage" title="DTS '.$lang['AUDIO']['ESDISCRETE'].'" alt=""/>&nbsp;DTS '.$lang['AUDIO']['ESDISCRETE'],
	'DTS HD'			=> '<img src="gfx/dd71.gif" class="audioimage" title="DTS '.$lang['AUDIO']['HD'].'" alt=""/>&nbsp;DTS '.$lang['AUDIO']['HD'],
/**/	'DTS HD HR'			=> '<img src="gfx/dd71.gif" class="audioimage" title="DTS '.$lang['AUDIO']['HDHR'].'" alt=""/>&nbsp;DTS '.$lang['AUDIO']['HDHR'],
/**/	'DTS HD Master Audio'		=> '<img src="gfx/dd71.gif" class="audioimage" title="DTS '.$lang['AUDIO']['HDMASTERAUDIO'].'" alt=""/>&nbsp;DTS '.$lang['AUDIO']['HDMASTERAUDIO'],
	'DTS-X'					=> '<img src="gfx/dd3d.gif" class="audioimage" title="'.$lang['AUDIO']['DTS'].' '.$lang['AUDIO']['3D'].'" alt=""/>&nbsp;'.$lang['AUDIO']['DTS'].' '.$lang['AUDIO']['3D'],
	'PCM Stereo'			=> '<img src="gfx/dd20.gif" class="audioimage" title="PCM '.$lang['AUDIO']['STEREO'].'" alt=""/>&nbsp;PCM '.$lang['AUDIO']['STEREO'],
/**/	'PCM 5.0'			=> '<img src="gfx/dd50.gif" class="audioimage" title="PCM 5.0" alt=""/>&nbsp;PCM 5.0',
	'PCM 5.1'			=> '<img src="gfx/dd51.gif" class="audioimage" title="PCM 5.1" alt=""/>&nbsp;PCM 5.1',
/**/	'PCM 6.1'			=> '<img src="gfx/dd61.gif" class="audioimage" title="PCM '.$lang['AUDIO']['6.1'].'" alt=""/>&nbsp;PCM '.$lang['AUDIO']['6.1'],
/**/	'PCM 7.1'			=> '<img src="gfx/dd71.gif" class="audioimage" title="PCM 7.1" alt=""/>&nbsp;PCM 7.1',
	'MPEG-1 Audio Layer II (MP2)'	=> '<img src="gfx/dd10.gif" class="audioimage" title="MPEG-1 '.$lang['AUDIO']['MP2'].'" alt=""/>&nbsp;MPEG-1 '.$lang['AUDIO']['MP2'],
	'MPEG-2'			=> '<img src="gfx/dd20.gif" class="audioimage" title="MPEG-2" alt=""/>&nbsp;MPEG-2',
/**/	'MPEG-2 Mono'			=> '<img src="gfx/dd10.gif" class="audioimage" title="MPEG-2 '.$lang['AUDIO']['MONO'].'" alt=""/>&nbsp;MPEG-2 '.$lang['AUDIO']['MONO'],
/**/	'MPEG-2 2.0'			=> '<img src="gfx/dd20.gif" class="audioimage" title="MPEG-2 2.0" alt=""/>&nbsp;MPEG-2 2.0',
/**/	'MPEG-2 Surround'		=> '<img src="gfx/dd30.gif" class="audioimage" title="MPEG-2 Surround'.$lang['AUDIO']['SURROUND'].'" alt=""/>&nbsp;MPEG-2 Surround'.$lang['AUDIO']['SURROUND'],
	'Other'				=> ''
);
$acomp_translation = array(
	'' => $lang['AUDIO'][''],
	'DD (Dolby Digital)' => '<acronym title="'.$lang['AUDIO']['DD'].'" style="text-decoration:underline">DD</acronym>',
	'DTS (Digital Theater Systems)' => '<acronym title="'.$lang['AUDIO']['DTS'].'" style="text-decoration:underline">DTS</acronym>',
	'PCM (Pulse Code Modulation)' => '<acronym title="'.$lang['AUDIO']['PCM'].'" style="text-decoration:underline">PCM</acronym>',
	'MPEG Audio Stream' => '<acronym title="'.$lang['AUDIO']['MPEG'].'" style="text-decoration:underline">MPEG</acronym>'
);

// these styles are an attempt to get the images to line up properly in different browsers
// the margin is to separate the lines a little ...
$achan_translation = array(
	'' => $lang['AUDIO'][''],
	'Mono' => '<img src="gfx/dd10.gif" style="vertical-align:-30%; margin-bottom:2px" title="'.$lang['AUDIO']['MONO'].'" alt=""/>',
	'Stereo' =>'<img src="gfx/dd20.gif" style="vertical-align:-30%; margin-bottom:2px" title="'.$lang['AUDIO']['STEREO'].'" alt=""/>',
	'Pro-logic' => '<img src="gfx/dd30.gif" style="vertical-align:-30%; margin-bottom:2px" title="'.$lang['AUDIO']['PROLOGIC'].'" alt=""/>',
	'Dolby Surround' => '<img src="gfx/dd40.gif" style="vertical-align:-30%; margin-bottom:2px" title="'.$lang['AUDIO']['DOLBYSUR'].'" alt=""/>',
	'5.0 Surround' => '<img src="gfx/dd30.gif" style="vertical-align:-30%; margin-bottom:2px" title="'.$lang['AUDIO']['5.0SUR'].'" alt=""/>',
	'5.1 Surround' => '<img src="gfx/dd51.gif" style="vertical-align:-30%; margin-bottom:2px" title="'.$lang['AUDIO']['5.1SUR'].'" alt=""/>',
	'6.1 Surround' => '<img src="gfx/dd61.gif" style="vertical-align:-30%; margin-bottom:2px" title="'.$lang['AUDIO']['6.1SUR'].'" alt=""/>',
	'7.1 Surround' => '<img src="gfx/dd61.gif" style="vertical-align:-30%; margin-bottom:2px" title="'.$lang['AUDIO']['7.1SUR'].'" alt=""/>',
	'3D' => '<img src="gfx/dd3d.gif" style="vertical-align:-30%; margin-bottom:2px" title="3D" alt=""/>'
);

$achan_rev_translation = array(
	'' => '',
	'5.1 Surround' => '5.1',
	'Stereo' => '2',
	'5.0 Surround' => '5',
	'Pro-logic' => '4',
	'Mono' => '1',
	'Dolby Surround' => '3',
	'6.1 Surround' => '6.1',
	'7.1 Surround' => '7.1',
	'3D' => '3D'
);

$skindisplayname = $lang['PREFS']['SKINS']['INTERNAL'];
if ($skinfile != 'internal')
	$skindisplayname = preg_replace('/\.htm[l]$/i', '', $skinfile);

/*
//	Defines
*/

// Table names
$DVD_TABLE = $table_prefix.'dvd';
$DVD_COMMON_ACTOR_TABLE = $table_prefix.'dvd_common_actor';
$DVD_ACTOR_TABLE = $table_prefix.'dvd_actor';
$DVD_EVENTS_TABLE = $table_prefix.'dvd_events';
$DVD_DISCS_TABLE = $table_prefix.'dvd_discs';
$DVD_LOCKS_TABLE = $table_prefix.'dvd_locks';
$DVD_AUDIO_TABLE = $table_prefix.'dvd_audio';
$DVD_COMMON_CREDITS_TABLE = $table_prefix.'dvd_common_credits';
$DVD_CREDITS_TABLE = $table_prefix.'dvd_credits';
$DVD_BOXSET_TABLE = $table_prefix.'dvd_boxset';
$DVD_GENRES_TABLE = $table_prefix.'dvd_genres';
$DVD_STUDIO_TABLE = $table_prefix.'dvd_studio';
$DVD_SUBTITLE_TABLE = $table_prefix.'dvd_subtitle';
$DVD_TAGS_TABLE = $table_prefix.'dvd_tags';
$DVD_STATS_TABLE = $table_prefix.'dvd_stats';
$DVD_SUPPLIER_TABLE = $table_prefix.'dvd_supplier';
$DVD_PROPERTIES_TABLE = $table_prefix.'dvd_properties';
$DVD_EXCLUSIONS_TABLE = $table_prefix.'dvd_exclusions';
$DVD_LINKS_TABLE = $table_prefix.'dvd_links';
$DVD_USERS_TABLE = $table_prefix.'dvd_users';

include_once($dbtype.'.php');

/*
// Common Code
*/

$bullet = '&nbsp;&bull;&nbsp;';
if (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
	$inbrowser = false;
	$eoln = "\n";
}
else {
	$inbrowser = true;
	$eoln = '<br>' . str_repeat(' ', 512) . "\n";
}

$delete = 0;

if (!$inbrowser) {
	if (isset($_SERVER['argv'][1])) {
		$args = explode('&', $_SERVER['argv'][1]);
		foreach ($args as $key => $value) {
			$temp = explode('=', $value);
			if (!isset($temp[1]))
				$temp[1] = '';
			$$temp[0] = $temp[1];
			unset($temp);
		}
		unset($args);
	}
}

$DeleteTemporaryFile = false;		// Flag to see if we need to delete a file created from a zip ...
if (!isset($PHP_SELF)) {
	if (isset($_SERVER['PHP_SELF']))
		$PHP_SELF = $_SERVER['PHP_SELF'];
	else
		$PHP_SELF = 'index.php';
}
$mobilepage = $PHP_SELF;
if ($mobileshow)
	$mobilepage = 'smallshow.php';

$db_schema_version = 'Unknown';

$db = new sql_db($dbhost, $dbuser, $dbpasswd, $dbname, $dbport, false, $debugSQL, true);
$db_Errors = $db->GetErrorState();

$collectiontypelist = array();
if ($db_Errors['code'] == 0) {
	$result = $db->sql_query("SELECT * FROM $DVD_PROPERTIES_TABLE WHERE property NOT LIKE 'Rating%' ORDER BY property", 0, true);
	$masterauxcolltype = array();
	$ListOfPurchaseDates = '-1';
	if (!$result) {
		$db_schema_version = 'Prior to schema versioning';
	}
	else {
		while ($item = $db->sql_fetchrow($result)) {
			switch ($item['property']) {
			case 'db_schema_version':
				$db_schema_version = $item['value'];
				break;
			case 'listofpurchasedates':
				$ListOfPurchaseDates = $item['value'];
				break;
			case 'masterauxcolltypeAdult':
				if (DisplayIfIsPrivateOrAlways($handleadult))
					$masterauxcolltype = explode('/', substr($item['value'], 1));
				break;
			case 'masterauxcolltypeNoAdult':
				if (!DisplayIfIsPrivateOrAlways($handleadult))
					$masterauxcolltype = explode('/', substr($item['value'], 1));
				break;
			case 'CurrentPosition':
				$UpdateLast = UpdateUpdateLast($item['value']);
				break;
			}
		}
		$db->sql_freeresult($result);
		if (!isset($UpdateLast)) {
			$UpdateLast = UpdateUpdateLast();
			$result = $db->sql_query("SELECT CONNECTION_ID() AS Id") or die($db->sql_error());
			$item = $db->sql_fetchrow($result);
			$db->sql_query("INSERT IGNORE INTO $DVD_PROPERTIES_TABLE (property,value) VALUES ('CurrentPosition','0||0|0|0|0|".$db->sql_escape($item['Id'])."')") or die($db->sql_error());
		}
		$result = $db->sql_query("SELECT DISTINCT realcollectiontype FROM $DVD_TABLE ORDER BY realcollectiontype", 0, true);
		while ($item = $db->sql_fetchrow($result)) {
			$rct = strtolower(str_replace(' ', '', $item['realcollectiontype']));
			if ($rct != 'owned' && $rct != 'ordered' && $rct != 'wishlist')
				$collectiontypelist[] = $item['realcollectiontype'];
		}
		$db->sql_freeresult($result);
	}
}

$WeCannotContinue = false;
if ($db_schema_version != $code_schema_version)
	$WeCannotContinue = true;

if (!$WeCannotContinue && DisplayIfIsPrivateOrAlways($displayloaned)) {
	$sql = "SELECT COUNT(*) AS num FROM $DVD_TABLE WHERE loaninfo!=''";
	$result = $db->sql_query($sql) or die($db->sql_error());
	$item = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	if ($item['num'] == 0)
		$displayloaned = 2;
}

if (!$WeCannotContinue && (DisplayIfIsPrivateOrAlways($searchtags) || DisplayIfIsPrivateOrAlways($displaytags))) {
	$sql = "SELECT COUNT(*) AS num FROM $DVD_TAGS_TABLE";
	$result = $db->sql_query($sql) or die($db->sql_error());
	$item = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	if ($item['num'] == 0) {
		$searchtags = 2;
		$displaytags = 2;
	}
}

if (!DisplayIfIsPrivateOrAlways($searchtags)) {
	$tagcombo = false;
}

if (!DisplayIfIsPrivateOrAlways($searchlocks)) {
	$lockcombo = false;
}
