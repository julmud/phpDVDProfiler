<?php
defined('IN_SCRIPT') || define('IN_SCRIPT', 1);
include_once('version.php');
include_once('global.php');

function UpdateDataRow(&$dvd) {
    $dvd['primegenre'] = GenreTranslation($dvd['primegenre']);
    FormatTheTitle($dvd);
}

if (isset($img)) $action = 'image';
if (!isset($action)) $action = 'main';
CheckOutOfDateSchema($action);

$iconPath = dirname($_SERVER['SCRIPT_NAME']) . '/gfx';

if (!isset($collection)) $collection = $DefaultCollection;

$colno = array_search($collection, $collectiontypelist);
if ($colno !== false)
    $collection = "FJW-$colno";

if ($collection != 'owned' &&
    $collection != 'ordered' &&
    $collection != 'wishlist' &&
    $collection != 'loaned' &&
    $collection != 'all' &&
    substr($collection, 0, strlen('FJW-')) != 'FJW-' && // handle user-defined collections
    !is_numeric($collection)) { // handle tag-based urls
    foreach ($masterauxcolltype as $key => $aux) {
        if (strtolower(substr($aux, 0, strlen($collection))) == strtolower($collection)) {
            $collection = "$key";
            break;
        }
    }
    if (!is_numeric($collection))
        $collection = 'owned';
}

if ($collection != 'owned' && isset($Columns[$collection])) {
    if (isset($Columns[$collection]['secondcol']))
        $secondcol = $Columns[$collection]['secondcol'];
    if (isset($Columns[$collection]['thirdcol']))
        $thirdcol = $Columns[$collection]['thirdcol'];
}

if ($action == 'GimmeAFrontThumb') {
    if (!isset($mediaid)) $mediaid = '';
    if (!isset($bannertype)) $bannertype = '';
    if (!isset($side)) $side = 'f';
    if ($side != 'b') $side = 'f';
    GimmeAThumb($mediaid, $side, $bannertype);
    DebugSQL($db, "$action");
    exit;
}

if ($action == 'phpinfo') {
    phpinfo();
    DebugSQL($db, "$action");
    exit;
}

if ($action == 'HealthCheck') {
    $badlogin = !isset($_GET['auth_login']) ||
              ($_GET['auth_login'] != $update_login) ||
              ($_GET['auth_pass'] != $update_pass);
    if ($inbrowser && $badlogin) {
        header("HTTP/1.0 401 Bad Username/Password", true, 401);
        $str = '<div id="phpdvd_notice" style="display:none">401</div>Bad Username/Password.';
        echo "$str$eoln";
        exit;
    }
    $res = $db->sql_query("SELECT CONNECTION_ID() AS Id") or die($db->sql_error());
    $row = $db->sql_fetchrow($res);
    $db->sql_freeresult($res);
    $db->sql_query("UPDATE $DVD_PROPERTIES_TABLE SET value='0||0|0|0|0|$row[Id]' WHERE property='CurrentPosition'") or die($db->sql_error());
    unset($row);
    ModifyTables('ENABLE');
    DebugSQL($db, "$action");
    $action = 'UpdateStatus';   // while this looks hackish, it prevents having to duplicate code
}

if ($action == 'UpdateStatus') {
    if (!isset($QueryNumber))
        $QueryNumber = 'NoQueryNumber';
    $result = $db->sql_query("SELECT value FROM $DVD_PROPERTIES_TABLE WHERE property='CurrentPosition'", 0, true);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    $UpdateStatus = $row !== false ? '*' . str_replace('|', '*', $row['value']) . '*' : '';
    $found = 'false';
    if (preg_match('/\*([^\*]*)\*$/', $UpdateStatus, $matches) == 1) {
        $RunningID = $matches[1];
        if ($RunningID < 0)
            $RunningID *= -1;
        $result = $db->sql_query("SHOW PROCESSLIST", 0, true);
        while ($row = $db->sql_fetchrow($result)) {
            if ($row['Id'] == $RunningID) {
                $found = 'true';
                break;
            }
        }
        $db->sql_freeresult($result);
    }
    SendNoCacheHeaders();
    echo "$QueryNumber-UpdateStatus:Status=$UpdateStatus:$found\n\n";
    DebugSQL($db, "$action");
    exit;
}

if ($action == 'info') {
    $thedatetime = date('Y-m-d-H:i:s', GetLastUpdateTime('LastUpdate'));
    SendNoCacheHeaders();
    echo "phpDVDProfiler:Version=$VersionNum|XML:Version=$thedatetime|MySQL:Version=".MySQLVersion()."|PHP:Version=".phpversion()."\n\n";
    DebugSQL($db, "$action");
    exit;
}

if ($action == 'notes') {
    $result = $db->sql_query("SELECT notes FROM $DVD_TABLE WHERE id='".$db->sql_escape($mediaid)."'") or die($db->sql_error());
    $data = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    header('Content-Type: text/html; charset="windows-1252";');
    echo $data['notes'];
    DebugSQL($db, "$action");
    exit;
}

if ($action == 'image') {
    DiscourageAbuse($RefuseBots);
    $TheTitle = $lang['DVDCOVER'];
    $res = $db->sql_query("SELECT title, originaltitle, description, custommediatype FROM $DVD_TABLE WHERE id='".$db->sql_escape($mediaid)."'") or die($db->sql_error());
    $dat = $db->sql_fetchrow($res);
    $db->sql_freeresult($res);
    if ($dat['title'] != '') {
        FormatTheTitle($dat);
        $TheTitle = fix1252(htmlspecialchars($dat['title'], ENT_COMPAT, 'ISO-8859-1'));
    }

    if (!isset($mtype))
        $mtype = 0;
    $hdlogo = '';
    if ($AddHDLogos && $getimages != 3) {
        if ($mtype >= 0)
            $ban = $MediaTypes[$mtype]['Banner'];
        else
            $ban = @$MediaTypes[$dat['custommediatype']]['Banner'];
        if ($ban != '') {
            $imagedata = getimagesize(WebpathToPhyspath($img));
            $width = $imagedata[0];
            $hdlogo = "<img width=\"$width\" src=\"$ban\" border=0 alt=\"$TheTitle\" title=\"$TheTitle\"/><br>";
        }
    }

    $whattodo = "$mobilepage?action=show&amp;mediaid=$mediaid";
    if ($popupimages)
        $whattodo = "javascript:window.close()";
    header('Content-Type: text/html; charset="windows-1252";');
    echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
<title>CoverScan</title>
<link rel=stylesheet type="text/css" href="format.css.php">
<link rel="SHORTCUT ICON" href="$iconPath/favicon.ico">
<link rel="icon" type="image/png" href="$iconPath/favicon-192x192.png" sizes="192x192">
<link rel="apple-touch-icon" sizes="180x180" href="$iconPath/apple-touch-icon-180x180.png">
</head>
<body onLoad="self.focus();">
<table width="95%" style="vertical-align:middle; height:100%;" cellpadding=0 cellspacing=0>
<tr><td class=bgd>
    <div align=center>
    <a href="$whattodo">$hdlogo<img src="$img" title="$TheTitle" alt="$TheTitle" border=0></a>
    </div>
</td>
</tr>
</table>
$endbody
</html>

EOT;
    DebugSQL($db, "$action");
    exit;
}

if ($action == 'upload' || $action == 'uploadxml') {
    if (is_readable('upload.php')) {
        include_once('upload.php');
    }
}

if ($action == 'smallupdate') {
    $LeaveMissing = true;
    $action = 'update';
}

if ($action == 'update') {
    if (isset($complete) && $complete) {
        $action = "CompleteUpdate";
    }
}

if ($action == 'update' || $action == 'CompleteUpdate' || $action == 'UpdateStats') {
    if (isset($NoImage))
        $forumuser = '';
    if ($action == 'CompleteUpdate') {
        $delete = 1;
        $UpdateLast = UpdateUpdateLast();
    }
    $remove_missing = true;     // default to not doing partial updates
// This allows this to be used from the command-line if desired
    if ($inbrowser) {
        $authorized = false;
        if ($force_formlogin==0 &&
            isset($_SERVER["SERVER_SOFTWARE"]) &&
            preg_match('/Apache/i', $_SERVER['SERVER_SOFTWARE'])) {
// Do Basic HTTP authentification on Apache servers
            if (!isset($PHP_AUTH_USER) ||
                (($PHP_AUTH_USER != $update_login) ||
                 ($PHP_AUTH_PW != $update_pass))) {
// If empty or incorrect, send header causing dialog box to appear
                header('WWW-Authenticate: Basic realm="phpDVDprofiler Update"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'Authorization Required.';
                exit;
            }
            else {
                $authorized = true;
            }
        }
        else {
// Do form based authentification on all other servers
            $badlogin = !isset($_POST['auth_login']) ||
                     (($_POST['auth_login'] != $update_login) ||
                      ($_POST['auth_pass'] != $update_pass));
            $showdb = ($db_fast_update)? 'checked': '';
            $showcheck = ($RemoveChecked)? 'checked': '';
            $allowremove = '';
            if ($LeaveMissing) {
                $allowremove = 'disabled';
                $showcheck = '';
            }
            if ($badlogin) {
// If empty or incorrect, send login form
                if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                    $OnloadStyle = 'UpdateLoaded();';
                    $JavascriptOnerr = '<script type="text/javascript" src="JavascriptOnerr.js"></script>'."\n";
                    $Javascript = '<script type="text/javascript" src="SmartUpdater.js.php"></script>'."\n";
                    $SubmitButton = '<input type="button" id="SubmitLogin" name="SubmitLogin" value="' . $lang['UPDATE'] . '" onClick="SubmitClicked()">';
                    if ($UpdateDebug)
                        $SubmitButton = '<input type="button" value="Force Halt" onClick="ForceHalt();"><input type="button" id="DebugButton" name="DebugButton" value="Debug" onClick="DebugEntry();">'.$SubmitButton;
                    $Action = 'phpaction';
                    $OutputAreas = '<div id="statushere"></div>'."\n"
                        .'<div style="display:none"><iframe id="writehere" src="#"></iframe></div>'."\n"
                        .'<div id="outputhere" style="border:1px solid black; overflow:auto; width:99%; height:75%"></div>'."\n";
                    if ($SubmitOldStyle) {
                        $OnloadStyle = 'document.LoginForm.auth_login.focus();';
                        $JavascriptOnerr = '';
                        $Javascript = '';
                        $SubmitButton = '<input type="submit" id="SubmitLogin" name="SubmitLogin" value="' . $lang['UPDATE'] . '">';
                        $Action = 'action';
                        $OutputAreas = '';
                    }
                    @header('Content-Type: text/html; charset="windows-1252";');
                    echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
<title>$lang[IMPORTTITLE]</title>
<link rel="stylesheet" type="text/css" href="format.css.php">
<link rel="SHORTCUT ICON" href="$iconPath/favicon.ico">
<link rel="icon" type="image/png" href="$iconPath/favicon-192x192.png" sizes="192x192">
<link rel="apple-touch-icon" sizes="180x180" href="$iconPath/apple-touch-icon-180x180.png">
$JavascriptOnerr$Javascript</head>
<body class=f6 onLoad="$OnloadStyle">
<form class=f1 name="LoginForm" id="LoginForm" action="$PHP_SELF" method="POST">
<input type=hidden name="$Action" value="$action">
$lang[LOGIN]<input type=text id=auth_login name=auth_login size=10>
$lang[PASSWORD]<input type=password id=auth_pass name=auth_pass size=10>
$lang[COMPLETE]<input type=checkbox id=complete name=complete><br>
<smallest>$lang[REMOVE]<input type=checkbox $allowremove $showcheck id=remove_missing_fromui name=remove_missing_fromui></smallest><br>
<input style="display:none" type="checkbox" id="db_fast_update_fromui" name="db_fast_update_fromui" $showdb>
$SubmitButton
</form>
$OutputAreas$endbody
EOT;
                }
                else {
                    header("HTTP/1.0 401 Bad Username/Password", true, 401);
                    $str = '<div id="phpdvd_notice" style="display:none">401</div>';
                    $str .= 'Bad Username/Password.';
                    echo "$str$eoln";
                }
                exit;
            }
            else {
                $authorized = true;
            }
        }
        if (!isset($remove_missing_fromui))
            $remove_missing = false;
// $db_fast_update is set from localsiteconfig. $db_fast_update_fromui is initially set by $db_fast_update
// If _fromui is the same as _update then this does nothing. If it is different, it is an override from
// SmartUpdater, and this overrides.
        if (isset($db_fast_update_fromui))
            $db_fast_update = true;
        else
            $db_fast_update = false;
    }
    if ($LeaveMissing)
        $remove_missing = false;

    include_once('incupdate.php');
    HandleOutOfDateSchema($outputtext);
    if ($action == 'UpdateStats') {
        $UpdateLast['Offset'] = -2;
    }
    ProcessXMLCollection($outputtext);
    DebugSQL($db, "$action");
    exit;
}

function CleanTheHTMLIn(&$str) {
global $playsounds;

    $str = fix1252(ReplaceSlashes($str));

    if (!DisplayIfIsPrivateOrAlways($playsounds)) {
        $str = str_replace('<embed src="" hidden="">', '', $str);
        $str = preg_replace('/<bgsound src=".*">/i', '', $str);
    }
    if (($j=stripos($str, '<body')) !== false) {
        $front = substr($str, 0, $j);
        preg_match('/<body([^>]*)>(.*)/is', $str, $matches);
        $back = $matches[2];
        $body = str_replace('\'', '"',  "<body $matches[1]>");
        unset($matches);

        $bgcolor = '';
        $textcolor = '';
        $bgimage = '';
        $style = '';

        if (($j=stripos($body, 'bgcolor')) !== false) {
            $t1 = substr($body, $j);
            $t1 = substr($t1, strpos($t1, '"')+1);
            $t1 = substr($t1, 0, strpos($t1, '"'));
            $bgcolor = "background-color:$t1;";
            $body = preg_replace('/bgcolor\s*=".*"/Ui', '', $body);
        }
        if (($j=stripos($body, 'text')) !== false) {
            $t1 = substr($body, $j);
            $t1 = substr($t1, strpos($t1, '"')+1);
            $t1 = substr($t1, 0, strpos($t1, '"'));
            $textcolor = "color:$t1;";
            $body = preg_replace('/text\s*=".*"/Ui', '', $body);
        }
        if (($j=stripos($body, 'background')) !== false) {
            $t1 = substr($body, $j);
            $t1 = substr($t1, strpos($t1, '"')+1);
            $t1 = substr($t1, 0, strpos($t1, '"'));
            $bgimage = "background-image:url($t1);";
            $body = preg_replace('/background\s*=".*"/Ui', '', $body);
        }
        if (($j=stripos($body, 'style')) !== false) {
            $t1 = substr($body, $j);
            $t1 = substr($t1, strpos($t1, '"')+1);
            $t1 = substr($t1, 0, strpos($t1, '"'));
            $style = "$t1;";
            $body = preg_replace('/style\s*=".*"/Ui', '', $body);
        }
        $str = "<table cellpadding=0 border=0 width=\"100%\" style=\"$style $bgcolor\">"
                ."<td border=0 style=\"$style $textcolor $bgcolor $bgimage\">$front$body$back</td></table>";
    }
}

function FormatLabel($val, $lab, $short) {
    if (!$short) {
        if ($val == '0')
            $val = 'N/R';
        else if (strlen($val) == 1)
            $val .= '.0';
        $title = "$lab:$val ";
    }
    else {
        $title = substr($lab, 0, 1) .":$val ";
    }
    return($title);
}

function IsValidReviewString($review) {
    if (strlen($review) > 4)
        return(false);
    for ($i=0; $i<strlen($review); $i++) {
        if (strpos('FVAE', $review[$i]) === false)
            return(false);
    }
    return(true);
}

function DeriveSort($review, $order) {
global $ReviewLabels;

    $sort = '';
    if (IsValidReviewString($review)) {
        for ($i=0; $i<strlen($review); $i++) {
            $sort .= $ReviewLabels[$review[$i]] . " $order,";
        }
    }
    $sort .= 'sorttitle ASC';
    return($sort);
}

function LabelAReviewGraph(&$dvd, $review, $short=false) {
global $lang, $ReviewLabels;

    if (!IsValidReviewString($review))
        return($lang['BADREVIEWSTRING'].$review);
    $title = '';
    for ($i=0; $i<strlen($review); $i++)
        $title .= FormatLabel(FixAReviewValue($dvd[$ReviewLabels[$review[$i]]])/2, $lang['REVIEWNAMES'][$review[$i]], $short);

    if (substr($title, -1, 1) == ' ')
        $title = substr($title, 0, -1);
    return($title);
}

function DrawAReviewGraph(&$dvd, $review, $width, $height, $bgcolor) {
global $lang, $ReviewLabels;

    if (!IsValidReviewString($review))
        return($lang['BADREVIEWSTRING'].$review);
    $bincolors = array(
        'F' => '#0080FF',
        'V' => '#00FF80',
        'A' => '#FF8080',
        'E' => '#FFFF80'
    );
    if ($bgcolor != '') $bgcolor = " BGCOLOR=\"$bgcolor\"";

// The style causes correct HTML, but is different from IVS's implementation, which uses the invalid HEIGHT parameter
    $pref = "<TABLE CELLPADDING=0 CELLSPACING=0 WIDTH=$width style=\"height:$height\" BORDER=1$bgcolor><TR>";

    $ret = '';
    $numreviews = strlen($review);
    $WidthOfOne = 1;
    if ($numreviews > 0)
        $WidthOfOne = floor($width/($numreviews*10));

    $rest = $width;
    for ($j=0; $j<strlen($review); $j++) {
        $m = FixAReviewValue($dvd[$ReviewLabels[$review[$j]]]);
        $rest -= $WidthOfOne*$m;
        $nm = floor($m/2); $rm = $m - $nm*2;
        for ($i=0; $i<$nm; $i++)
            $ret .= '<TD WIDTH='. (2*$WidthOfOne-1) .' BGCOLOR="'.$bincolors[$review[$j]].'"></TD>';
        if ($rm != 0) $ret .= '<TD WIDTH='. ($WidthOfOne-1) .' BGCOLOR="'.$bincolors[$review[$j]].'"></TD>';
    }

    $ret .= "<TD WIDTH=$rest>";
    $post = '</TD></TR></TABLE>';
    return($pref.$ret.$post);
}

function ProjectAColumn($colname, &$dvd, $align) {
global $lang, $reviewsort;
    switch ($colname) {
    case 'loandue':
        $colvalue = ($dvd['loandue'] === null? '': fix88595(ucwords(strftimeReplacement($lang['SHORTDATEFORMAT'], $dvd['loandue']))));
        break;
    case 'loaninfo':
        $colvalue = $dvd['loaninfo'];
        break;
    case 'productionyear':
        $colvalue = $dvd['productionyear'];
        break;
    case 'released':
        $colvalue = ($dvd['released'] === null? '': fix88595(ucwords(strftimeReplacement($lang['SHORTDATEFORMAT'], $dvd['released']))));
        break;
    case 'runningtime':
        $colvalue = "$dvd[runningtime] $lang[MINUTES]";
        break;
    case 'rating':
        $colvalue = $dvd['rating'];
        break;
    case 'reviews':
        $colvalue = LabelAReviewGraph($dvd, $reviewsort, true);
        break;
    case 'genres':
        $colvalue = $dvd['primegenre'];
        break;
    case 'purchasedate':
        $colvalue = ($dvd['wishpriority']!='0') ? $lang['SHORTWISHNAME'.$dvd['wishpriority']]: fix88595(ucwords(strftimeReplacement($lang['SHORTDATEFORMAT'], $dvd['purchasedate'])));
        break;
    case 'director':
        $colvalue = $dvd['primedirector'];
        break;
    case 'collectionnumber':
        $colvalue = ($dvd['wishpriority']!='0') ? $lang['SHORTWISHNAME'.$dvd['wishpriority']]: $dvd['collectionnumber'];
        break;
    default:
        $colvalue = '';
        break;
    }
    if ($colvalue == '0') $colvalue = '';
    if ($colname != 'none') $colvalue = "<td style=\"padding-left:10px\" align=$align nowrap>$colvalue</td>";
    return($colvalue);
}

function GetSeparators($sort, &$dvd, &$sepa, &$separator) {
global $colnorange, $lang, $order, $reviewsort;
    switch ($sort) {
    case 'sorttitle':
        if ($dvd['sorttitle'] != '')
            $sepa = strtolower($dvd['sorttitle'][0]);
        else
            $sepa = '';
        if (preg_match("/\d/", $sepa)) {
            $sepa = '0';
            $separator = '0-9';
        }
        else {
            $separator = strtoupper($sepa);
        }
        $separator = "<a name=\"$separator\">$separator</a>";
        break;
    case 'productionyear':
        $sepa = intval($dvd['productionyear']);
        $separator = $sepa;
        break;
    case 'runningtime':
        $sepa = "$dvd[runningtime] $lang[FULLMINUTES]";
        $separator = $sepa;
        break;
    case 'loaninfo':
        $sepa = $dvd['loaninfo'];
        $separator = $sepa;
        break;
    case 'rating':
        $sepa = $dvd['rating'];
        $separator = $sepa;
        break;
    case 'reviews':
        $sepa = "$lang[REVIEWS]: " . LabelAReviewGraph($dvd, $reviewsort, false);
        $separator = $sepa;
        break;
    case 'genres':
        $sepa = $dvd['primegenre'];
        $separator = $sepa;
        break;
    case 'loandue':
        $sepa = fix88595(ucwords(strftimeReplacement($lang['DATEFORMAT'], $dvd['loandue'])));
        $separator = $sepa;
        break;
    case 'released':
        if ($dvd['released'] === null) {
            $sepa = '';
            $separator = $lang['NULL'];
        }
        else {
            $sepa = fix88595(ucwords(strftimeReplacement("%B %Y", $dvd['released'])));
            $separator = $sepa;
        }
        break;
    case 'purchasedate':
        if ($dvd['wishpriority'] != '0') {
            $sepa = $lang['WISHNAME'.$dvd['wishpriority']];
            $separator = $sepa;
        }
        else {
            $sepa = fix88595(ucwords(strftimeReplacement("%B %Y", $dvd['purchasedate'])));
            $separator = $sepa;
        }
        break;
    case 'collectionnumber':
        if ($dvd['wishpriority'] != '0') {
            $sepa = $lang['WISHNAME'.$dvd['wishpriority']];
            $separator = $sepa;
        }
        else {
            $sepa = '';
            $separator = '';
            if ($colnorange > 1) {
                $sepa = (int)($dvd['collectionnumber']/$colnorange);
                if ($order == 'asc')
                    $separator = ($sepa*$colnorange).' - '.((($sepa+1)*$colnorange)-1);
                else
                    $separator = ((($sepa+1)*$colnorange)-1).' - '.$sepa*$colnorange;
            }
        }
        break;
    default:
        $sepa = '';
        $separator = '';
        break;
    }
    return;
}

if ($collection == 'loaned') {
    $secondcol = 'loaninfo';
    $thirdcol = 'loandue';
}

if (!isset($sort) || ($sort != $firstcol && $sort != $secondcol && $sort != $thirdcol)) {
    $sort = $$defaultsorttype;  // two $ takes the value of the variable named in the variable
}
if (!isset($order) || ($order != 'asc' && $order != 'desc')) {
    $order = $defaultorder[$sort];
}

function SetColumnTitles($colname, &$colvalue, &$colhover) {
global $lang, $collection;

    switch ($colname) {
    case 'loandue':
        $colvalue = $lang['LOANDUE'];
        $colhover = $lang['SORTLOANDUE'];
        break;
    case 'loaninfo':
        $colvalue = $lang['LOANEE'];
        $colhover = $lang['SORTLOANEE'];
        break;
    case 'productionyear':
        $colvalue = $lang['YEAR'];
        $colhover = $lang['SORTYEAR'];
        break;
    case 'released':
        $colvalue = $lang['RELEASED'];
        $colhover = $lang['SORTRELEASED'];
        break;
    case 'runningtime':
        $colvalue = $lang['RUNTIME'];
        $colhover = $lang['SORTRUNTIME'];
        break;
    case 'rating':
        $colvalue = $lang['RATING'];
        $colhover = $lang['SORTRATING'];
        break;
    case 'reviews':
        $colvalue = $lang['REVIEWS'];
        $colhover = $lang['SORTREVIEWS'];
        break;
    case 'director':
        $colvalue = $lang['DIRECTOR'];
        $colhover = $lang['SORTDIRECTOR'];
        break;
    case 'genres':
        $colvalue = $lang['GENRE'];
        $colhover = $lang['SORTGENRE'];
        break;
    case 'purchasedate':
        $colvalue = ($collection=='wishlist') ? $lang['PRIORITY']: $lang['PURCHDATE'];
        $colhover = ($collection=='wishlist') ? $lang['SORTPRIORITY']: $lang['SORTPURCHDATE'];
        break;
    case 'collectionnumber':
        $colvalue = ($collection=='wishlist') ? $lang['PRIORITY']: $lang['NUMBER'];
        $colhover = ($collection=='wishlist') ? $lang['SORTPRIORITY']: $lang['SORTNUMBER'];
        break;
    default:
        $colvalue = '';
        $colhover = '';
        break;
    }
}

function DisplayDecoration($str, &$dvd) {
global $Highlight, $loanlength;

    if ($dvd['loaninfo'] != '') {
        $str = $Highlight['loaned']['open'] . $str . $Highlight['loaned']['close'];
        if (date('U') > $dvd['loandue'])
            $str = $Highlight['overdue']['open'] . $str . $Highlight['overdue']['close'];
    }
    if ($dvd['last_n_days'] == 1) {
        $str = $Highlight['last_n_days']['open'] . $str . $Highlight['last_n_days']['close'];
    }
    if ($dvd['last_x_purchasedates'] == 1) {
        $str = $Highlight['last_x_purchasedates']['open'] . $str . $Highlight['last_x_purchasedates']['close'];
    }
    return($str);
}

if (!isset($searchtext)) $searchtext = '';
$searchtext = rawurldecode($searchtext);
$searchurl = rawurlencode($searchtext);
$searchdisp = htmlentities($searchtext, ENT_COMPAT, 'ISO-8859-1');

if ($action == 'nav') {
    if (!isset($searchby)) $searchby = '';
    switch ($searchby) {
    case '':
        $thetitle = '';
        break;
    case 'title':
        $thetitle = "  ($lang[SEARCHED] $lang[TITLES] $lang[FOR] \"$searchdisp\")";
        break;
    case 'director':
        $thetitle = "  ($lang[SEARCHED] $lang[DIRECTORS] $lang[FOR] \"$searchdisp\")";
        break;
    case 'actor':
        $thetitle = "  ($lang[SEARCHED] $lang[CAST] $lang[FOR] \"$searchdisp\")";
        break;
    case 'credits':
        $thetitle = "  ($lang[SEARCHED] $lang[CREDITS] $lang[FOR] \"$searchdisp\")";
        break;
    case 'rating':
        list($tmploc, $tmpsys, $tmprat) = explode('.', $searchtext);
        $thetitle = "  ($lang[SEARCHED] $lang[RATINGS] $lang[FOR] \"".$lang['LOCALE'.$tmploc] . ": $tmpsys: $tmprat\")";
        break;
    case 'genre':
        $thetitle = "  ($lang[SEARCHED] $lang[GENRES] $lang[FOR] \"" . GenreTranslation($searchtext) . '")';
        break;
    case 'purchase':
        $result = $db->sql_query("SELECT suppliername from $DVD_SUPPLIER_TABLE WHERE sid=".$db->sql_escape($searchtext)) or die($db->sql_error());
        $items = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        $thetitle = "  ($lang[SEARCHED] $lang[PURCHASEPLACE] $lang[FOR] \"$items[suppliername]\")";
        unset($items);
        break;
    case 'mediatype':
        $thetitle = "  ($lang[SEARCHED] $lang[MEDIATYPE] $lang[FOR] \"$searchdisp\")";
        break;
    case 'locale':
        $thetitle = "  ($lang[SEARCHED] $lang[LOCALE] $lang[FOR] \"".$lang['LOCALE'.$searchtext].'")';
        break;
    case 'coo':
        CountryToLang($searchtext, $countryname, $localenum);
        $thetitle = "  ($lang[SEARCHED] $lang[COUNTRYOFORIGIN] $lang[FOR] \"$countryname\")";
        break;
    case 'tag':
        $thetitle = "  ($lang[SEARCHED] $lang[TAGS] $lang[FOR] \"$searchdisp\")";
        break;
    case 'lock':
        $thetitle = "  ($lang[SEARCHED] $lang[LOCK] $lang[FOR] \"$lock_translation[$searchtext]\")";
        break;
    case 'medialanguages':                                      // JMM: Added 11 Jun 2011
        $thetitle = "  ($lang[SEARCHED] $lang[MEDIALANGUAGES] $lang[FOR]  \"$searchdisp\")";    // JMM: Added 11 Jun 2011
        break;                                          // JMM: Added 11 Jun 2011
    case 'mediasubtitles':                                      // JMM: Added 11 Jun 2011
        $thetitle = "  ($lang[SEARCHED] $lang[MEDIASUBTITLES] $lang[FOR]  \"$searchdisp\")";    // JMM: Added 11 Jun 2011
        break;                                          // JMM: Added 11 Jun 2011
    }

    SetColumnTitles($secondcol, $secondcoltitle, $secondcolhover);
    SetColumnTitles($thirdcol, $thirdcoltitle, $thirdcolhover);

    $sortimg_title = ($sort=='sorttitle') ? "<img src=\"gfx/$order.gif\" width=13 height=13 border=0 alt=\"\">&nbsp;$lang[TITLE]": $lang['TITLE'];
    $sortimg_year = ($sort==$secondcol) ? "<img src=\"gfx/$order.gif\" width=13 height=13 border=0 alt=\"\">&nbsp;$secondcoltitle": $secondcoltitle;
    $sortimg_num = ($sort==$thirdcol) ? "<img src=\"gfx/$order.gif\" width=13 height=13 border=0 alt=\"\">&nbsp;$thirdcoltitle": $thirdcoltitle;

    $sorthdr_title = ($sort=='sorttitle') ? ($order=='asc')?'desc':'asc': $defaultorder['sorttitle'];
    $sorthdr_year = ($sort==$secondcol) ? ($order=='asc')?'desc':'asc': $defaultorder[$secondcol];
    $sorthdr_num = ($sort==$thirdcol) ? ($order=='asc')?'desc':'asc': $defaultorder[$thirdcol];
    $s1 = addslashes($lang['SORTTITLE']);
    $s2 = addslashes($secondcolhover);
    $s3 = addslashes($thirdcolhover);

    $infoline = '';
    $numincollection = array(
        'owned'     => 0,
        'ordered'   => 0,
        'wishlist'  => 0,
        'loaned'    => 0,
        'all'       => 0
    );
    $noadult = '';
    if (!DisplayIfIsPrivateOrAlways($handleadult))
        $noadult .= ' AND isadulttitle=0';
    $result = $db->sql_query("SELECT collectiontype, SUM(countas) AS itemcount FROM $DVD_TABLE WHERE 1 $noadult GROUP BY collectiontype") or die($db->sql_error());
    while ($items = $db->sql_fetchrow($result)) {
        $numincollection[$items['collectiontype']] = $items['itemcount'];
        $colltype = CustomTranslation(strtoupper(str_replace(' ', '', $items['collectiontype'])), $items['collectiontype']);
        $infoline .= "$items[itemcount] $colltype, ";
        $numincollection['all'] += $items['itemcount'];
    }
    if (DisplayIfIsPrivateOrAlways($displayloaned)) {
        $db->sql_freeresult($result);
        $result = $db->sql_query("SELECT SUM(countas) AS itemcount FROM $DVD_TABLE WHERE loaninfo != '' $noadult") or die($db->sql_error());
        $items = $db->sql_fetchrow($result);
        if ($items['itemcount'] != 0) {
            $infoline .= "$items[itemcount] $lang[LOANED], ";
            $numincollection['loaned'] = $items['itemcount'];
        }
    }
    $db->sql_freeresult($result);
    $infoline .= "$numincollection[all] $lang[TOTAL]";
    $thedatetime = GetLastUpdateTime('LastUpdate');
    $thedatetime = ($thedatetime!=0)? fix88595(ucwords(strftimeReplacement($lang['SHORTDATEFORMAT'], $thedatetime))): $lang['UNKNOWN'];
    $infoline .= " ($lang[UPDATED] $thedatetime)";

    $sel_title = $sel_credits = $sel_genre = $sel_director = $sel_tag = $sel_actor = $sel_lock = $sel_rating =
             $sel_purchase = $sel_locale = $sel_coo = $sel_medialanguages = $sel_mediasubtitles = $sel_mediatype = '';  // JMM: Added language
    switch ($searchby) {
        case 'rating':
            $sel_rating = 'selected';
            break;
        case 'lock':
            $sel_lock = 'selected';
            break;
        case 'tag':
            $sel_tag = 'selected';
            break;
        case 'actor':
            $sel_actor = 'selected';
            break;
        case 'director':
            $sel_director = 'selected';
            break;
        case 'credits':
            $sel_credits = 'selected';
            break;
        case 'genre':
            $sel_genre = 'selected';
            break;
        case 'purchase':
            $sel_purchase = 'selected';
            break;
        case 'mediatype':
            $sel_mediatype = 'selected';
            break;
        case 'locale':
            $sel_locale = 'selected';
            break;
        case 'coo':
            $sel_coo = 'selected';
            break;
        case 'medialanguages':                  // JMM: Added 11 June 2011
            $sel_medialanguages = 'selected';       // JMM: Added 11 June 2011
            break;                      // JMM: Added 11 June 2011
        case 'mediasubtitles':                  // JMM: Added 11 June 2011
            $sel_mediasubtitles = 'selected';       // JMM: Added 11 June 2011
            break;                      // JMM: Added 11 June 2011
        default:
            $sel_title = 'selected';
    }

    $sel_owned = $sel_ordered = $sel_loaned = $sel_wishlist = $sel_all = '';
    switch ($collection) {
        case 'ordered':
            $sel_ordered = ' selected';
            break;
        case 'wishlist':
            $sel_wishlist = ' selected';
            break;
        case 'loaned':
            $sel_loaned = ' selected';
            break;
        case 'all':
            $sel_all = ' selected';
            break;
        case 'owned':
            $sel_owned = ' selected';
            break;
    }

    $tagoption = '';
    if (DisplayIfIsPrivateOrAlways($searchtags))
        $tagoption = "<option value=tag $sel_tag>$lang[TAGS]</option>";
    $lockoption = '';
    if (DisplayIfIsPrivateOrAlways($searchlocks))
        $lockoption = "<option value=lock $sel_lock>$lang[LOCK]</option>";
    $placeoption = '';
    if (DisplayIfIsPrivateOrAlways($displayplace))
        $placeoption = "<option value=purchase $sel_purchase>$lang[PURCHASEPLACE]</option>";

    $FOR = "<span title=\"$lang[SKINNAME]: $skindisplayname\">$lang[FOR]</span>";
    if ($debugon)
        $FOR = "<a style=\"color:$ClassColor[33]\" href=\"javascript:;\" onClick=\"DebugFn($TitlesPerPage)\">$FOR</a>";

    $inputwidth = '130px';
    $maxinputwidth = '330px';
    $onres = '';
    if ($allowwidths)
        $onres = 'onResize="OnRes()"';

    $clearbutton = "<input type=button class=input value=\"$lang[CLEAR]\" onclick=\"ClearSearch('".addslashes($searchurl)."')\">";
    $searchbutton = "<input type=button class=input value=\"$lang[SEARCH]\" onClick=\"navform.submit()\">";
    header('Content-Type: text/html; charset="windows-1252";');
    echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
  <title>Nav Frame</title>
  <link rel=stylesheet type="text/css" href="format.css.php">
  <link rel="SHORTCUT ICON" href="$iconPath/favicon.ico">
  <link rel="icon" type="image/png" href="$iconPath/favicon-192x192.png" sizes="192x192">
  <link rel="apple-touch-icon" sizes="180x180" href="$iconPath/apple-touch-icon-180x180.png">
  <base target="menu">
  <script type="text/javascript" src="navframe.js"></script>
</head>
<body onLoad="NavInit('$searchby')" $onres>$ULHSTitle
<form method=post action="$PHP_SELF" id=navform name=navform target="_parent">
<table>
<tr class=s1>
<td align=right valign=middle>$lang[SEARCHIN]</td>
<td align=left valign=middle style="font-size:8pt"><nobr>
<input type=hidden name=sort value="$sort">
<input type=hidden name=order value="$order">
<input type=hidden name=searchtext value="$searchurl">
<select name=searchby onChange="SwitchField(this.value)">
<option value=title $sel_title>$lang[TITLES]</option>
<option value=director $sel_director>$lang[DIRECTORS]</option>
<option value=actor $sel_actor>$lang[ACTORS]/$lang[ROLES]</option>
<option value=credits $sel_credits>$lang[CREDITS]</option>
<option value=rating $sel_rating>$lang[RATINGS]</option>
<option value=genre $sel_genre>$lang[GENRES]</option>
$placeoption
<option value=locale $sel_locale>$lang[LOCALE]</option>
<option value=coo $sel_coo>$lang[COUNTRYOFORIGIN]</option>
<option value=mediatype $sel_mediatype>$lang[MEDIATYPE]</option>
$tagoption
$lockoption
<option value=medialanguages $sel_medialanguages>$lang[MEDIALANGUAGES]</option>
<option value=mediasubtitles $sel_mediasubtitles>$lang[MEDIASUBTITLES]</option>

EOT;
    echo "</select> $FOR&nbsp;<input type=text id=\"Textbox\" onKeyup=\"SetVal(this.value)\" value=\"".htmlentities(rawurldecode($searchurl))."\" style=\"width:$inputwidth\">\n";
    echo "<select name=\"Combobox\" id=genre style=\"position:absolute; left:auto; top:auto; width:$inputwidth; visibility:hidden\" onBlur=\"this.style.width='$inputwidth'\" onMouseDown=\"this.style.width='$maxinputwidth'\" onChange=\"this.style.width='$inputwidth';SetVal(this.value)\">\n";
    $sql = "SELECT DISTINCT(genre) FROM $DVD_GENRES_TABLE ORDER BY genre";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($row = $db->sql_fetchrow($result)) {
        $displaygenre = GenreTranslation($row['genre']);
        if ($searchtext == $displaygenre)
            echo "<option value=\"$row[genre]\" selected>$displaygenre</option>\n";
        else
            echo "<option value=\"$row[genre]\">$displaygenre</option>\n";
    }
    $db->sql_freeresult($result);
    echo "</select>\n";

    echo "<select name=\"Combobox\" id=rating style=\"position:absolute; left:auto; top:auto; width:$inputwidth; visibility:hidden\" onBlur=\"this.style.width='$inputwidth'\" onMouseDown=\"this.style.width='$maxinputwidth'\" onChange=\"this.style.width='$inputwidth';SetVal(this.value)\">\n";
    $sql = "SELECT IF (LOCATE('.',id) = '0',0,SUBSTRING(id,locate('.',id)+1,LENGTH(id)-LOCATE('.',id)))+0 AS locality,ratingsystem,rating FROM $DVD_TABLE GROUP BY locality,ratingsystem,rating ORDER BY locality,ratingsystem,rating";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($rating = $db->sql_fetchrow($result)) {
        if ($searchtext == rawurlencode("$rating[locality].$rating[ratingsystem].$rating[rating]"))
            echo "<option value=\"".rawurlencode("$rating[locality].$rating[ratingsystem].$rating[rating]")."\" selected>".$lang['LOCALE'.$rating['locality']].": $rating[ratingsystem]: $rating[rating]</option>\n";
        else
            echo "<option value=\"".rawurlencode("$rating[locality].$rating[ratingsystem].$rating[rating]")."\">".$lang['LOCALE'.$rating['locality']].": $rating[ratingsystem]: $rating[rating]</option>\n";
    }
    $db->sql_freeresult($result);
    echo "</select>\n";

    echo "<select name=\"Combobox\" id=coo onChange=\"SetVal(this.value)\" style=\"position:absolute; left:auto; top:auto; width:$inputwidth; visibility:hidden\">\n";
    $remembercoo = array();
    $result = $db->sql_query("SELECT DISTINCT countryoforigin,countryoforigin2,countryoforigin3 from $DVD_TABLE ORDER BY countryoforigin,countryoforigin2,countryoforigin3") or die($db->sql_error());
    while ($coo = $db->sql_fetchrow($result)) {
        if ($coo['countryoforigin'] == '' && $coo['countryoforigin2'] == '' && $coo['countryoforigin3'] == '')
            continue;
        if ($coo['countryoforigin'] != '' && (array_search($coo['countryoforigin'], $remembercoo) === false)) {
            $remembercoo[] = $coo['countryoforigin'];
        }
        if ($coo['countryoforigin2'] != '' && (array_search($coo['countryoforigin2'], $remembercoo) === false)) {
            $remembercoo[] = $coo['countryoforigin2'];
        }
        if ($coo['countryoforigin3'] != '' && (array_search($coo['countryoforigin3'], $remembercoo) === false)) {
            $remembercoo[] = $coo['countryoforigin3'];
        }
    }
    $db->sql_freeresult($result);
    unset($coo);
    sort($remembercoo);
    foreach ($remembercoo as $coo) {
        CountryToLang($coo, $countryname, $localenum);
        if ($searchtext == $countryname)
            echo "<option value=\"$countryname\" selected>$countryname</option>\n";
        else
            echo "<option value=\"$countryname\">$countryname</option>\n";
    }
    unset($remembercoo);
    echo "</select>\n";

    echo "<select name=\"Combobox\" id=medialanguages onChange=\"SetVal(this.value)\" style=\"position:absolute; left:auto; top:auto; width:$inputwidth; visibility:hidden\">\n";
    $result = $db->sql_query("SELECT DISTINCT audiocontent from $DVD_AUDIO_TABLE ORDER BY audiocontent") or die($db->sql_error());
    while ($ml = $db->sql_fetchrow($result)) {
        if ($ml['audiocontent'] == '')
            continue;   // ***FIXME
        $loc = $ml['audiocontent'];
        $loclang = substr($alang_translation[$loc], strpos($alang_translation[$loc], '/>')+3);
        if ($searchtext == $loc)
            echo "<option value=\"$loc\" selected>$loclang</option>\n";
        else
            echo "<option value=\"$loc\">$loclang</option>\n";
    }
    $db->sql_freeresult($result);
    echo "</select>\n";

    echo "<select name=\"Combobox\" id=mediasubtitles onChange=\"SetVal(this.value)\" style=\"position:absolute; left:auto; top:auto; width:$inputwidth; visibility:hidden\">\n";
    $result = $db->sql_query("SELECT DISTINCT subtitle from $DVD_SUBTITLE_TABLE ORDER BY subtitle") or die($db->sql_error());
    while ($ms = $db->sql_fetchrow($result)) {
        if ($ms['subtitle'] == '')
            continue;   // ***FIXME
        $loc = $ms['subtitle'];
        $loclang = substr($alang_translation[$loc], strpos($alang_translation[$loc], '/>')+3);
        if ($searchtext == $loc)
            echo "<option value=\"$loc\" selected>$loclang</option>\n";
        else
            echo "<option value=\"$loc\">$loclang</option>\n";
    }
    $db->sql_freeresult($result);
    echo "</select>\n";

    if ($localecombo) {
        echo "<select name=\"Combobox\" id=locale onChange=\"SetVal(this.value)\" style=\"position:absolute; left:auto; top:auto; width:$inputwidth; visibility:hidden\">\n";
        $sql = "SELECT IF (LOCATE('.',id) = '0',0,SUBSTRING(id,LOCATE('.',id)+1,LENGTH(id)-LOCATE('.',id)))+0 AS locality FROM $DVD_TABLE WHERE 1 $localespecialcondition GROUP BY locality ORDER BY locality";
        $result = $db->sql_query($sql) or die($db->sql_error());
        while ($locale = $db->sql_fetchrow($result)) {
            $loc = $locale['locality'];
            $loclang = $lang['LOCALE' . $loc];
            if ($searchtext == $loc)
                echo "<option value=\"$loc\" selected>$loclang</option>\n";
            else
                echo "<option value=\"$loc\">$loclang</option>\n";
        }
        $db->sql_freeresult($result);
        echo "</select>\n";
    }

    if ($purchasecombo) {
        echo "<select name=\"Combobox\" id=purchase style=\"position:absolute; left:auto; top:auto; width:$inputwidth; visibility:hidden\" onBlur=\"this.style.width='$inputwidth'\" onMouseDown=\"this.style.width='$maxinputwidth'\" onChange=\"this.style.width='$inputwidth';SetVal(this.value)\">\n";
        $result = $db->sql_query("SELECT sid, suppliername from $DVD_SUPPLIER_TABLE ORDER BY suppliername") or die($db->sql_error());
        while ($supplier = $db->sql_fetchrow($result)) {
            if ($searchtext == $supplier['suppliername'])
                echo "<option value=\"$supplier[sid]\" selected>$supplier[suppliername]</option>\n";
            else
                echo "<option value=\"$supplier[sid]\">$supplier[suppliername]</option>\n";
        }
        $db->sql_freeresult($result);
        echo "</select>\n";
    }

    if ($mediatypecombo) {
        $totdvd = $totbluray = $tothddvd = $totultrahd = 0;
        $result = $db->sql_query("SELECT builtinmediatype,COUNT(*) AS count FROM $DVD_TABLE GROUP BY builtinmediatype") or die($db->sql_error());
        while ($mtype = $db->sql_fetchrow($result)) {
            switch ($mtype['builtinmediatype']) {
            case MEDIA_TYPE_DVD:
                $totdvd += $mtype['count'];
                break;
            case MEDIA_TYPE_HDDVD:
                $tothddvd += $mtype['count'];
                break;
            case MEDIA_TYPE_HDDVD_DVD:
                $tothddvd += $mtype['count'];
                $totdvd += $mtype['count'];
                break;
            case MEDIA_TYPE_BLURAY:
                $totbluray += $mtype['count'];
                break;
            case MEDIA_TYPE_BLURAY_DVD:
                $totbluray += $mtype['count'];
                $totdvd += $mtype['count'];
                break;
            case MEDIA_TYPE_ULTRAHD:
                $totultrahd += $mtype['count'];
                break;
            case MEDIA_TYPE_ULTRAHD_BLURAY:
                $totultrahd += $mtype['count'];
                $totbluray += $mtype['count'];
                break;
            case MEDIA_TYPE_ULTRAHD_BLURAY_DVD:
                $totultrahd += $mtype['count'];
                $totbluray += $mtype['count'];
                $totdvd += $mtype['count'];
                break;
            }
        }
        $db->sql_freeresult($result);
        echo "<select name=\"Combobox\" id=mediatype onChange=\"SetVal(this.value)\" style=\"position:absolute; left:auto; top:auto; width:$inputwidth; visibility:hidden\">\n";
        if ($totdvd > 0) {
            $msel = ($searchtext == $lang['DVD']) ? ' selected' : '';
            echo "<option value=\"$lang[DVD]\"$msel>$lang[DVD]</option>\n";
        }
        if ($totbluray > 0) {
            $msel = ($searchtext == $lang['BLURAY']) ? ' selected' : '';
            echo "<option value=\"$lang[BLURAY]\"$msel>$lang[BLURAY]</option>\n";
        }
        if ($tothddvd > 0) {
            $msel = ($searchtext == $lang['HDDVD']) ? ' selected' : '';
            echo "<option value=\"$lang[HDDVD]\"$msel>$lang[HDDVD]</option>\n";
        }
        if ($totultrahd > 0) {
            $msel = ($searchtext == $lang['ULTRAHD']) ? ' selected' : '';
            echo "<option value=\"$lang[ULTRAHD]\"$msel>$lang[ULTRAHD]</option>\n";
        }
        $result = $db->sql_query("SELECT custommediatype,COUNT(*) AS count FROM $DVD_TABLE WHERE custommediatype != '' GROUP BY custommediatype") or die($db->sql_error());
        while ($mtype = $db->sql_fetchrow($result)) {
            if ($mtype['count'] > 0) {
                $msel = ($searchtext == $mtype['custommediatype']) ? ' selected' : '';
                echo "<option value=\"$mtype[custommediatype]\"$msel>$mtype[custommediatype]</option>\n";
            }
        }
        $db->sql_freeresult($result);
        echo "</select>\n";
    }

    if ($tagcombo) {
        echo "<select name=\"Combobox\" id=tag style=\"position:absolute; left:auto; top:auto; width:$inputwidth; visibility:hidden\" onBlur=\"this.style.width='$inputwidth'\" onMouseDown=\"this.style.width='$maxinputwidth'\" onChange=\"this.style.width='$inputwidth';SetVal(this.value)\">\n";
        $result = $db->sql_query("SELECT DISTINCT fullyqualifiedname from $DVD_TAGS_TABLE ORDER BY fullyqualifiedname") or die($db->sql_error());
        while ($tag = $db->sql_fetchrow($result)) {
            $tagval = rawurlencode($tag['fullyqualifiedname']);
            if ($searchtext == $tag['fullyqualifiedname'])
                echo "<option value=\"$tagval\" selected>$tag[fullyqualifiedname]</option>\n";
            else
                echo "<option value=\"$tagval\">$tag[fullyqualifiedname]</option>\n";
        }
        $db->sql_freeresult($result);
        echo "</select>\n";
    }

    if ($lockcombo) {
        echo "<select name=\"Combobox\" id=lock onChange=\"SetVal(this.value)\" style=\"position:absolute; left:auto; top:auto; width:$inputwidth; visibility:hidden\">\n";
        foreach ($lock_translation as $lockname => $thelockname)
            if ($searchtext == $lockname)
                echo "<option value=\"$lockname\" selected>$thelockname</option>\n";
            else
                echo "<option value=\"$lockname\">$thelockname</option>\n";
        echo "</select>\n";
    }

    $optionordered = $optionwishlist = $optionowned = $optionloaned = $optionother = $optionaux = '';
    if (!$hideordered && $numincollection['ordered'] != 0)
        $optionordered = "<option value=ordered$sel_ordered>$lang[ORDERED]</option>";
    if (!$hidewishlist && $numincollection['wishlist'] != 0)
        $optionwishlist = "<option value=wishlist$sel_wishlist>$lang[WISHLIST]</option>";
    if (!$hideowned && $numincollection['owned'] != 0)
        $optionowned = "<option value=owned$sel_owned>$lang[OWNED]</option>";
    if (!$hideloaned && $numincollection['loaned'] != 0)
        if (DisplayIfIsPrivateOrAlways($displayloaned))
            $optionloaned = "<option value=loaned$sel_loaned>$lang[LOANED]</option>";
    unset($numincollection);

    $coltnum = (substr($collection, 0, strlen('FJW-'))=='FJW-')?(int)substr($collection, strlen('FJW-')): -1;
    foreach ($collectiontypelist as $num => $ctype) {
        $thissel = ($coltnum==$num)? ' selected': '';
        $optionother .= "<option value=\"FJW-$num\"$thissel>$ctype</option>";
    }

    foreach ($masterauxcolltype as $num => $auxcoltype) {
        if ($auxcoltype != '') {
            $thissel = (is_numeric($collection) && $collection==$num)? ' selected': '';
            $optionaux .= "<option value=$num$thissel>$auxcoltype</option>";
        }
    }

    $TheHref = "$PHP_SELF?sort=sorttitle&amp;order=asc&amp;collection=$collection&amp;searchby=$searchby&amp;searchtext=$searchurl&amp;action=menu";
    $oc = 'class=n onClick="PopulateMenuFrame(0,\'asc\')"';
    if ($lettermeaning == 1 || $TitlesPerPage != 0) {
        $TheHref .= "&amp;letter";
        $navigationletters = <<<EOT
<a $oc href="$TheHref=0#0-9">0-9</a> <a $oc href="$TheHref=A#A">A</a> <a $oc href="$TheHref=B#B">B</a> <a $oc href="$TheHref=C#C">C</a>
<a $oc href="$TheHref=D#D">D</a> <a $oc href="$TheHref=E#E">E</a> <a $oc href="$TheHref=F#F">F</a> <a $oc href="$TheHref=G#G">G</a>
<a $oc href="$TheHref=H#H">H</a> <a $oc href="$TheHref=I#I">I</a> <a $oc href="$TheHref=J#J">J</a> <a $oc href="$TheHref=K#K">K</a>
<a $oc href="$TheHref=L#L">L</a> <a $oc href="$TheHref=M#M">M</a> <a $oc href="$TheHref=N#N">N</a> <a $oc href="$TheHref=O#O">O</a>
<a $oc href="$TheHref=P#P">P</a> <a $oc href="$TheHref=Q#Q">Q</a> <a $oc href="$TheHref=R#R">R</a> <a $oc href="$TheHref=S#S">S</a>
<a $oc href="$TheHref=T#T">T</a> <a $oc href="$TheHref=U#U">U</a> <a $oc href="$TheHref=V#V">V</a> <a $oc href="$TheHref=W#W">W</a>
<a $oc href="$TheHref=X#X">X</a> <a $oc href="$TheHref=Y#Y">Y</a> <a $oc href="$TheHref=Z#Z">Z</a>

EOT;
    }
    else {
        $navigationletters = <<<EOT
<a $oc href="$TheHref#0-9">0-9</a> <a $oc href="$TheHref#A">A</a> <a $oc href="$TheHref#B">B</a> <a $oc href="$TheHref#C">C</a>
<a $oc href="$TheHref#D">D</a> <a $oc href="$TheHref#E">E</a> <a $oc href="$TheHref#F">F</a> <a $oc href="$TheHref#G">G</a>
<a $oc href="$TheHref#H">H</a> <a $oc href="$TheHref#I">I</a> <a $oc href="$TheHref#J">J</a> <a $oc href="$TheHref#K">K</a>
<a $oc href="$TheHref#L">L</a> <a $oc href="$TheHref#M">M</a> <a $oc href="$TheHref#N">N</a> <a $oc href="$TheHref#O">O</a>
<a $oc href="$TheHref#P">P</a> <a $oc href="$TheHref#Q">Q</a> <a $oc href="$TheHref#R">R</a> <a $oc href="$TheHref#S">S</a>
<a $oc href="$TheHref#T">T</a> <a $oc href="$TheHref#U">U</a> <a $oc href="$TheHref#V">V</a> <a $oc href="$TheHref#W">W</a>
<a $oc href="$TheHref#X">X</a> <a $oc href="$TheHref#Y">Y</a> <a $oc href="$TheHref#Z">Z</a>

EOT;
    }

    echo <<<EOT
$searchbutton&nbsp;$clearbutton</nobr>
</td>
</tr>
<tr>
<td>
<select name=collection onchange="this.form.submit()" style="font-weight:Bold">
<option value=all$sel_all>$lang[ALL]</option> $optionowned $optionordered $optionwishlist $optionloaned $optionother $optionaux
</select>
</td>
<td class=nav2><nobr>
$navigationletters
</nobr></td>
</tr>
<tr><td class=s style="text-align:right">$lang[DVDCOUNTS]:</td><td class=s><nobr>$infoline</nobr></td></tr>
</table>
<table id="navheader" width="100%" cellpadding=0 cellspacing=0><tr class=t><td>&nbsp;
<div style="position:absolute;left:25;white-space:nowrap"><img src="gfx/none.gif" alt=""/><a onClick="PopulateMenuFrame(0,'$sorthdr_title')" class=n1 href="$PHP_SELF?sort=sorttitle&amp;order=$sorthdr_title&amp;collection=$collection&amp;searchby=$searchby&amp;searchtext=$searchurl&amp;action=menu" target="menu" onmouseover="status='$s1'; return true" title="$lang[SORTTITLE]">$sortimg_title</a>$thetitle</div>

EOT;
    if ($secondcol != 'none') {
        echo <<<EOT
<div style="position:absolute;left:300;white-space:nowrap" align=center><a onClick="PopulateMenuFrame(1,'$sorthdr_year')" class=n1 href="$PHP_SELF?sort=$secondcol&amp;order=$sorthdr_year&amp;collection=$collection&amp;searchby=$searchby&amp;searchtext=$searchurl&amp;action=menu" target="menu" onmouseover="status='$s2'; return true" title="$secondcolhover">$sortimg_year</a></div>

EOT;
    }
    if ($thirdcol != 'none') {
        echo <<<EOT
<div style="position:absolute;left:400;white-space:nowrap" align=right><a onClick="PopulateMenuFrame(2,'$sorthdr_num')" class=n1 href="$PHP_SELF?sort=$thirdcol&amp;order=$sorthdr_num&amp;collection=$collection&amp;searchby=$searchby&amp;searchtext=$searchurl&amp;action=menu" target="menu" onmouseover="status='$s3'; return true" title="$thirdcolhover">$sortimg_num</a></div>

EOT;
    }
    echo <<<EOT
</td></tr></table></form>
<div id=loading style="display:block"><br><br><br><br><center><h1>$lang[LOADING]</h1></center></div>
$endbody
</html>

EOT;
    DebugSQL($db, "$action");
    exit;
}

#ajm Added 'removedtabbed'
switch ($collection) {
case 'owned':
    $where = "collectiontype='owned'";
    if ($removetabbed)
        $where .= " AND auxcolltype=''";
    break;
case 'ordered':
    $where = "collectiontype='ordered'";
    if ($removetabbed)
        $where .= " AND auxcolltype=''";
    break;
case 'wishlist':
    $where = "collectiontype='wishlist'";
    if ($removetabbed)
        $where .= " AND auxcolltype=''";
    break;
case 'loaned':
    $where = "loaninfo!=''";
    break;
case 'all':
    $where = "1";
    if ($hideowned) $where .= " AND collectiontype!='owned'";
    if ($hideordered) $where .= " AND collectiontype!='ordered'";
    if ($hidewishlist) $where .= " AND collectiontype!='wishlist'";
    break;
default:
    if (is_numeric($collection)) {
        $where = "auxcolltype LIKE '%/".addslashes($masterauxcolltype[$collection])."/%'";
    }
    else if (substr($collection, 0, strlen('FJW-')) == 'FJW-') {
        $collsel = $collectiontypelist[(int)(substr($collection, strlen('FJW-')))];
        $where = "realcollectiontype='" . addslashes($collsel) . "'";
        if ($removetabbed)
            $where .= " AND auxcolltype=''";
    }
    break;
}

if (!DisplayIfIsPrivateOrAlways($handleadult))
    $where .= ' AND isadulttitle=0';

if (!isset($searchby)) $searchby = '';
if ($searchtext == '') $searchby = '';

if ($stickyboxsets && $searchby == '')
    $where .= " AND boxparent='%%BOXPARENT%%'";

$Extra = 'dvd WHERE';
if (isset($letter) && $lettermeaning == 1) {
    if ($letter == "0")
        $where .= " AND dvd.sorttitle < 'A'";
    else
        $where .= " AND dvd.sorttitle LIKE '$letter%'";
}

$srchtext = preg_replace('/(\\\\)*\'/', '\\\\\'', $searchtext);
$srchtext = $db->sql_escape($srchtext);
switch ($searchby) {
case 'title':
// Add the ability to anchor search to the start of the field
    if ($srchtext[0] == '^')
        $where .= " AND (dvd.title LIKE '".substr($srchtext,1)."%' OR originaltitle LIKE '".substr($srchtext,1)."%' OR description LIKE '".substr($srchtext,1)."%')";
    else
        $where .= " AND (dvd.title LIKE '%$srchtext%' OR originaltitle LIKE '%$srchtext%' OR description LIKE '%$srchtext%')";
    break;
case 'genre':
    $Extra = "dvd,$DVD_GENRES_TABLE gens WHERE dvd.id=gens.id and gens.genre='$srchtext' AND";
    break;
case 'locale':
    $Extra = "dvd WHERE IF (LOCATE('.',id) = '0',0,SUBSTRING(id,locate('.',id)+1,LENGTH(id)-LOCATE('.',id)))+0 = '$srchtext' AND";
    break;
case 'coo':
    CountryToLang($searchtext, $countryname, $localenum);
    $countryname = $db->sql_escape($countryname);
    $Extra = "dvd WHERE (countryoforigin = '$countryname' OR countryoforigin2 = '$countryname' OR countryoforigin3 = '$countryname') AND";
    break;
case 'rating':
    list($tmploc, $tmpsys, $tmprat) = explode('.', rawurldecode($srchtext));
    $Extra = "dvd WHERE IF (LOCATE('.',id) = '0',0,SUBSTRING(id,locate('.',id)+1,LENGTH(id)-LOCATE('.',id)))+0 = '$tmploc' AND ratingsystem='$tmpsys' AND rating='$tmprat' AND";
    break;
case 'mediatype':
    $sfield = '1';
    if (is_numeric($srchtext)) {
        $sfield = "custommediatype=$srchtext";
    }
    else {
        switch ($srchtext) {
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
            $sfield = "(custommediatype='$srchtext')";
            break;
        }
    }
    $Extra = "dvd WHERE $sfield AND";
    break;
case 'purchase':
    $Extra = "dvd WHERE purchaseplace = '$srchtext' AND";
    break;
case 'lock':
    $Extra = "dvd,$DVD_LOCKS_TABLE locks WHERE dvd.id=locks.id AND locks.$srchtext=1 AND";
    break;
case 'tag':
    $Extra = "dvd,$DVD_TAGS_TABLE tag WHERE dvd.id=tag.id AND tag.fullyqualifiedname='$srchtext' AND";
    break;
case 'actor':
    $lookfor = "'%" . str_replace(' ', '%', $srchtext) . "%'";
    $Extra = "dvd,$DVD_ACTOR_TABLE act INNER JOIN $DVD_COMMON_ACTOR_TABLE com ON com.caid=act.caid WHERE dvd.id=act.id AND (com.fullname LIKE $lookfor OR act.creditedas LIKE $lookfor OR act.role LIKE $lookfor) AND";
    break;
case 'director':
    $lookfor = "'%" . str_replace(' ', '%', $srchtext) . "%'";
    $Extra = "dvd,$DVD_CREDITS_TABLE dir INNER JOIN $DVD_COMMON_CREDITS_TABLE com ON com.caid=dir.caid WHERE dvd.id=dir.id AND dir.credittype='Direction' AND (dir.creditedas LIKE $lookfor OR com.fullname LIKE $lookfor) AND";
    break;
case 'credits':
    $lookfor = "'%" . str_replace(' ', '%', $srchtext) . "%'";
    $Extra = "dvd,$DVD_CREDITS_TABLE dir INNER JOIN $DVD_COMMON_CREDITS_TABLE com ON com.caid=dir.caid WHERE dvd.id=dir.id AND (dir.creditedas LIKE $lookfor OR com.fullname LIKE $lookfor) AND";
    break;
case 'medialanguages':                                                  // JMM: Added 11 June 2011
    $Extra = "dvd,$DVD_AUDIO_TABLE audiocontent WHERE dvd.id=audiocontent.id AND audiocontent='$srchtext' AND"; // JMM: Added 11 June 2011
    break;                                                      // JMM: Added 11 June 2011
case 'mediasubtitles':                                                  // JMM: Added 11 June 2011
    $Extra = "dvd,$DVD_SUBTITLE_TABLE subtitle WHERE dvd.id=subtitle.id AND subtitle='$srchtext' AND";      // JMM: Added 11 June 2011
    break;                                                      // JMM: Added 11 June 2011
}
$nowhere = str_replace('%%BOXPARENT%%', '', $where);

if ($action == 'main') {
    $cookiesort = isset($_COOKIE['cookiesort']) ? $_COOKIE['cookiesort']: '';
    $cookieorder = isset($_COOKIE['cookieorder']) ? $_COOKIE['cookieorder']: '';
    if ($collection == 'loaned') {
        $secondcol = 'loaninfo';
        $thirdcol = 'loandue';
    }

// AGW start -- determine current sort order
    if ($cookiesort != '')
        $sort = $cookiesort;
    if (!isset($sort))
        $sort = $$defaultsorttype;  // two $ takes the value of the variable named in the variable
    if ($cookieorder != '')
        $order = $cookieorder;
    if (!isset($order) || ($order != 'asc' && $order != 'desc'))
        $order = $defaultorder[$sort];
    switch ($sort) {
    case 'runningtime':
    case 'loaninfo':
    case 'loandue':
    case 'productionyear':
    case 'released':
    case 'rating':
        $mysort = "$sort $order,sorttitle ASC";
        break;
    case 'reviews':
        $mysort = DeriveSort($reviewsort, $order);
        break;
    case 'director':
        $mysort = "primedirector $order,sorttitle ASC";
        break;
    case 'genres':
        $mysort = "primegenre $order,sorttitle ASC";
        break;
    case 'purchasedate':
    case 'collectionnumber':
        if ($collection == 'wishlist')
            $mysort = "wishpriority $order,sorttitle $order";
        else if ($collection == 'all')
            $mysort = "$sort $order,wishpriority $order,sorttitle $order";
        else
            $mysort = "$sort $order,sorttitle $order";
        break;
    case 'sorttitle':
    default:
        $mysort = "$sort $order";
        break;
    }
// AGW end

    if (!isset($lastmedia)) {
        $IRF = ($searchby!='')? '': $InitialRightFrame;
        switch ($IRF) {
        case 'Statistics':
            $lastmedia = 'Statistics';
            break;
        case 'Front Gallery':
            if (!isset($gallerysorttype)) $gallerysorttype = $sort;
            if (!isset($gallerysortorder)) $gallerysortorder = $order;
            $lastmedia = "Gallery&amp;ct=$collection&amp;searchby=$searchby&amp;searchtext=$searchurl&amp;sort=$gallerysorttype&amp;order=$gallerysortorder";
            break;
        case 'Back Gallery':
            $lastmedia = "GalleryB&amp;ct=$collection&amp;searchby=$searchby&amp;searchtext=$searchurl&amp;sort=$sort&amp;order=$order";
            break;
        case 'Chooser':
            $lastmedia = 'Chooser';
            break;
        default:
// Get last edited DVD in collection
//          $sql = "SELECT dvd.id AS lastmedia FROM $DVD_TABLE dvd WHERE $nowhere ORDER BY lastedited DESC LIMIT 1";
// Get last edited DVD in filtered collection
//          $sql = "SELECT dvd.id AS lastmedia FROM $DVD_TABLE $Extra $nowhere ORDER BY lastedited DESC LIMIT 1";
// Get first DVD in filtered collection alphabetically
//          $sql = "SELECT dvd.id AS lastmedia FROM $DVD_TABLE $Extra $nowhere ORDER BY sorttitle LIMIT 1";
// Get last purchased DVD in filtered collection
//          $sql = "SELECT dvd.id AS lastmedia FROM $DVD_TABLE $Extra $nowhere ORDER BY purchasedate DESC,sorttitle ASC LIMIT 1";

// Get first DVD that is displayed in filtered collection
            $sql = "SELECT dvd.id AS lastmedia FROM $DVD_TABLE $Extra $nowhere ORDER BY $mysort LIMIT 1";

            $result = $db->sql_query($sql) or die($db->sql_error());
            $media = $db->sql_fetchrow($result);
            $db->sql_freeresult($result);
            if ($media !== null) {
                $lastmedia = $media['lastmedia'];
            } else {
                $sanitizedSql = str_replace(array("\n", "\r", "\t"), '', $sql);
                error_log('lastmedia query returned no result! SQL: [' . $sanitizedSql . ']');
                $lastmedia = '';
            }
            break;
        }
    }

    if ($allowwidths) {
        if (isset($_COOKIE['widthgt800'])) $widthgt800 = $_COOKIE['widthgt800'];
    }
    $nomove = ($allowwidths) ? '': 'framespacing=0';

    header('Content-Type: text/html; charset="windows-1252";');
    $rssfeed = '';
    if (is_readable('rss.php')) $rssfeed = ' <link rel="alternate" type="application/rss+xml" title="'.$CurrentSiteTitle.'" href="rss.php" />';
    if ($mobileshow) {
        if ($lastmedia == 'Chooser' || $lastmedia == 'Statistics' || $lastmedia == 'WatchedStatistics' || substr($lastmedia, 0, 7) == 'Gallery')
            $mobilepage = $PHP_SELF;
    }
    echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
  <title>$CurrentSiteTitle</title>
  $rssfeed
  <script type="text/javascript" src="top.js"></script>
  <link rel="SHORTCUT ICON" href="$iconPath/favicon.ico">
  <link rel="icon" type="image/png" href="$iconPath/favicon-192x192.png" sizes="192x192">
  <link rel="apple-touch-icon" sizes="180x180" href="$iconPath/apple-touch-icon-180x180.png">
</head>
<frameset id="thecols" cols="$widthgt800,*" $nomove>
  <frameset id="therows" rows="*,1" border=0 frameborder=0 framespacing=0>
    <frame src="$PHP_SELF?sort=$sort&amp;order=$order&amp;collection=$collection&amp;searchby=$searchby&amp;searchtext=$searchurl&amp;action=nav" name="nav" scrolling=no framespacing=0 marginheight=2 marginwidth=2>
    <frame src="$PHP_SELF?sort=$sort&amp;order=$order&amp;collection=$collection&amp;searchby=$searchby&amp;searchtext=$searchurl&amp;action=menu" name="menu" scrolling=yes framespacing=0 marginheight=0 marginwidth=0>
  </frameset>
  <frame src="$mobilepage?mediaid=$lastmedia&amp;action=show" framespacing=0 marginheight=0 marginwidth=0 name="entry">
</frameset>
</html>

EOT;
    DebugSQL($db, "$action");
    exit;
}

if ($action == 'menu') {
    switch ($sort) {
    case 'runningtime':
    case 'loaninfo':
    case 'loandue':
    case 'productionyear':
    case 'released':
    case 'rating':
        $mysort = "$sort $order,sorttitle ASC";
        break;
    case 'reviews':
        $mysort = DeriveSort($reviewsort, $order);
        break;
    case 'director':
        $mysort = "primedirector $order,sorttitle ASC";
        break;
    case 'genres':
        $mysort = "primegenre $order,sorttitle ASC";
        break;
    case 'purchasedate':
    case 'collectionnumber':
        $secsort = 'ASC';
        if ($SecondarySortFollowPrimary)
            $secsort = $order;
        if ($collection == 'wishlist')
            $mysort = "wishpriority $order,sorttitle $secsort";
        else if ($collection == 'all')
            $mysort = "$sort $order,wishpriority $order,sorttitle $secsort";
        else
            $mysort = "$sort $order,sorttitle $secsort";
        break;
    case 'sorttitle':
    default:
        $mysort = "$sort $order";
        break;
    }

    $numincollection = array(
        'owned'     => 0,
        'ordered'   => 0,
        'wishlist'  => 0,
        'loaned'    => 0,
        'all'       => 0
    );
    $result = $db->sql_query("SELECT collectiontype, COUNT(*) AS itemcount FROM $DVD_TABLE GROUP BY collectiontype") or die($db->sql_error());
    while ($items = $db->sql_fetchrow($result)) {
        $numincollection[$items['collectiontype']] = $items['itemcount'];
        $numincollection['all'] += $items['itemcount'];
    }
    if (DisplayIfIsPrivateOrAlways($displayloaned)) {
        $db->sql_freeresult($result);
        $result = $db->sql_query("SELECT COUNT(*) AS itemcount FROM $DVD_TABLE WHERE loaninfo != ''") or die($db->sql_error());
        $items = $db->sql_fetchrow($result);
        if ($items['itemcount'] != 0)
            $numincollection['loaned'] = $items['itemcount'];
    }
    $db->sql_freeresult($result);

// Cookies are used to remember the current sort type and order when when the collection is changed
// If cookies are disabled, then the only effect is that changing the collection reverts to the
// default sort type and order.
    setcookie('cookiesort', $sort);
    setcookie('cookieorder', $order);

// removed DISTINCT to repair broken order by
// added back to support searches finding single entries per profile
    $stuff = 'dvd.id,dvd.title,originaltitle,sorttitle,description,dvd.productionyear,wishpriority,collectionnumber,released,gift,giftuid,purchasedate,boxchild,dvd.rating,primegenre,primedirector,dvd.runningtime,loaninfo,loandue,reviewfilm,reviewaudio,reviewvideo,reviewextras';
    if ($AddFormatIcons != 2)
        $stuff .= ',builtinmediatype,custommediatype';
# Find the start of a letter.
    if (!isset($startrow)) $startrow = 0;
    $sql = "SELECT DISTINCT $stuff,UNIX_TIMESTAMP()-purchasedate<$Highlight_Last_N_Days*24*60*60 AS last_n_days,purchasedate IN ($ListOfPurchaseDates) AS last_x_purchasedates FROM $DVD_TABLE $Extra $where ORDER BY $mysort";
    $sqllimit = '';
    if ($TitlesPerPage != 0) {
        if (isset($letter) && ($lettermeaning == 0)) {
            if ($letter != '0') {
                $result = $db->sql_query("SELECT COUNT(DISTINCT dvd.id) AS totrows FROM $DVD_TABLE $Extra $nowhere AND (dvd.sorttitle < '$letter')") or die($db->sql_error());
                $howmany = $db->sql_fetchrow($result);
                $db->sql_freeresult($result);
                $startrow = floor($howmany['totrows']/$TitlesPerPage)*$TitlesPerPage;
            }
        }
        $result = $db->sql_query("SELECT COUNT(DISTINCT dvd.id) AS totrows FROM $DVD_TABLE $Extra $nowhere") or die($db->sql_error());
        $howmany = $db->sql_fetchrow($result);
        $totrows = $howmany['totrows'];
        $db->sql_freeresult($result);
        $sqllimit = " LIMIT $startrow,$TitlesPerPage";
    }


    header('Content-Type: text/html; charset="windows-1252";');
    echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
<link rel=stylesheet type="text/css" href="format.css.php">
<base target="entry">
<title>Menu Frame</title>
<link rel="SHORTCUT ICON" href="$iconPath/favicon.ico">
<link rel="icon" type="image/png" href="$iconPath/favicon-192x192.png" sizes="192x192">
<link rel="apple-touch-icon" sizes="180x180" href="$iconPath/apple-touch-icon-180x180.png">
  <script type="text/javascript">
  <!--
     defaultStatus = "$lang[STATUSLINE] $VersionNum";

EOT;
    if ($stickyboxsets)
        echo <<<EOT

function dh(theitems, obj) {
var item=document.getElementById(theitems);
    if (item.style.display == 'none') {
        item.style.display = '';
        obj.src = 'gfx/minus.gif';
    }
    else {
        item.style.display = 'none';
        obj.src = 'gfx/plus.gif';
    }
}

EOT;
    $numcols = 1;   // 1 for image, one for title
    $page = 1;
    if ($TitlesPerPage != 0)
        $page = ceil($startrow / $TitlesPerPage)+1;
    if (($lettermeaning == 1 || $TitlesPerPage != 0) && isset ($letter)) {
        if ($letter != '0-9')
            $let = "&amp;letter=$letter#$letter";
        else
            $let = "&amp;letter=$letter#0-9";
    }
    else
        $let = '';
    echo <<<EOT
  //-->
  </script>
</head>
<body onLoad="top.MenuInit()" onResize="top.DoResizeHeader()">
  <script type="text/javascript" SRC="mom.js" language="JavaScript1.2"></script>
  <script type="text/javascript" SRC="momItems.js.php" language="JavaScript1.2"></script>
  <script type="text/javascript">
  <!--
    var num=0;

EOT;
    if ($allowlocale) echo <<<EOT
    momItems[num++]=["$lang[LANGUAGES]"]
    momItems[num++]=["English", "javascript:top.setcookie('locale','en',10*365);top.ChangeLang();", "_top", 1, "no"]
    momItems[num++]=["Fran&ccedil;ais", "javascript:top.setcookie('locale','fr',10*365);top.ChangeLang();", "_top", 1]
    momItems[num++]=["Deutsch", "javascript:top.setcookie('locale','de',10*365);top.ChangeLang();", "_top", 1, "no"]
    momItems[num++]=["Norsk", "javascript:top.setcookie('locale','no',10*365);top.ChangeLang();", "_top", 1]
    momItems[num++]=["Svenska", "javascript:top.setcookie('locale','sv',10*365);top.ChangeLang();", "_top", 1, "no"]
    momItems[num++]=["Dansk", "javascript:top.setcookie('locale','dk',10*365);top.ChangeLang();", "_top", 1]
    momItems[num++]=["Suomi", "javascript:top.setcookie('locale','fi',10*365);top.ChangeLang();", "_top", 1, "no"]
    momItems[num++]=["Nederlands", "javascript:top.setcookie('locale','nl',10*365);top.ChangeLang();", "_top", 1]
    momItems[num++]=["&#x420;&#x443;&#x441;&#x441;&#x43a;&#x438;&#x439;", "javascript:top.setcookie('locale','ru',10*365);top.ChangeLang();", "_top"]

EOT;
    echo <<<EOT
    momItems[num++]=["$lang[MENU]"]
    momItems[num++]=["$lang[STATISTICS]", "$PHP_SELF?mediaid=Statistics&amp;action=show", "entry"]

EOT;
    if (is_readable("ws.php") && DisplayIfIsPrivateOrAlways($handlewatched)) echo <<<EOT
    momItems[num++]=["$lang[WATCHED]", "ws.php?ct=$collection&amp;page=$page&amp;searchby=$searchby&amp;searchtext=$searchurl", "entry"]

EOT;

    if (is_readable('gallery.php')) {           // changed: Thomas 29.08.2005
        echo <<<EOT
    momItems[num++]=["$lang[FRONTGALLERY]", "$PHP_SELF?mediaid=Gallery&amp;action=show&amp;ct=$collection&amp;page=$page&amp;searchby=$searchby&amp;searchtext=$searchurl&amp;sort=$sort&amp;order=$order$let", "entry"]

EOT;
        if ($BackGallery) {
            echo <<<EOT
    momItems[num++]=["$lang[BACKGALLERY]", "$PHP_SELF?mediaid=GalleryB&amp;action=show&amp;ct=$collection&amp;page=$page&amp;searchby=$searchby&amp;searchtext=$searchurl&amp;sort=$sort&amp;order=$order$let", "entry"]

EOT;
        }
    }
    echo <<<EOT
    momItems[num++]=["{$lang['PREFS']['USERPREFS']}", "userpref.php", "_top"]
    momItems[num++]=["$lang[LISTS]"]

EOT;
    if (!$hideowned && $numincollection['owned'] != 0) echo <<<EOT
    momItems[num++]=["$lang[OWNED]", "$PHP_SELF?sort=sorttitle&amp;order=asc&amp;collection=owned&amp;searchby=$searchby&amp;searchtext=$searchurl", "_top"]

EOT;
    if (!$hideordered && $numincollection['ordered'] != 0) echo <<<EOT
    momItems[num++]=["$lang[ORDERED]", "$PHP_SELF?sort=sorttitle&amp;order=asc&amp;collection=ordered&amp;searchby=$searchby&amp;searchtext=$searchurl", "_top"]

EOT;
    if (!$hidewishlist && $numincollection['wishlist'] != 0) echo <<<EOT
    momItems[num++]=["$lang[WISHLIST]", "$PHP_SELF?sort=sorttitle&amp;order=asc&amp;collection=wishlist&amp;searchby=$searchby&amp;searchtext=$searchurl", "_top"]

EOT;
    if (!$hideloaned && $numincollection['loaned'] != 0) echo <<<EOT
    momItems[num++]=["$lang[LOANED]", "$PHP_SELF?sort=sorttitle&amp;order=asc&amp;collection=loaned&amp;searchby=$searchby&amp;searchtext=$searchurl", "_top"]

EOT;
    echo <<<EOT
    momItems[num++]=["$lang[ALL]", "$PHP_SELF?sort=sorttitle&amp;order=asc&amp;collection=all&amp;searchby=$searchby&amp;searchtext=$searchurl", "_top"]

EOT;

    if (is_readable('upload.php') || $allowupdate || (is_readable('imagedata.php') && $forumuser && $collectionurl)) echo<<<EOT
    momItems[num++]=["$lang[ADMIN]"]

EOT;

    if (!isset($ii_ignorelocked))
        $ii_ignorelocked = false;
    if (!isset($locale))
        $locale = "en";
//  if (is_readable('imagedata.php') && $forumuser && $collectionurl) echo <<<EOT
//  momItems[num++]=["$lang[IMAGE]", "http://dvdaholic.me.uk/ii/index.php?user=$forumuser&locked=$ii_ignorelocked&lang=$locale", "_new"]
//  momItems[num++]=["$lang[IMAGEUSERS]", "$ImageUserURL", "entry"]
//
//EOT;
    if (is_readable('upload.php')) {
        echo <<<EOT
    momItems[num++]=["$lang[UPLOADM]", "$PHP_SELF?action=upload", "_top", 1, "no"]
    momItems[num++]=["$lang[UPDATE]", "$PHP_SELF?action=update", "_top", 1]

EOT;
    }
    else {
        if ($allowupdate) echo <<<EOT
    momItems[num++]=["$lang[UPDATE]", "$PHP_SELF?action=update", "_top"]

EOT;
    }
    echo <<<EOT
    momItems[num++]=["$lang[MISCLINKS]"]
//  momItems[num++]=["$lang[ANDYFORUM]", "http://forums.dvdaholic.me.uk", "_blank"]
//  momItems[num++]=["DVD Profiler V2.x", "http://www.intervocative.com", "_blank"]
    momItems[num++]=["DVD Profiler V3.x", "http://www.invelos.com", "_blank"]
//  momItems[num++]=["$lang[FORUMS] V2.x", "http://www.intervocative.com/Forums.aspx?task=viewtopic&topicid=6404", "_blank"]
    momItems[num++]=["$lang[NEWRELEASES]", "https://www.joblo.com/blu-rays-dvds/release-dates/", "_blank"]

EOT;
    if ($AllowChooser) echo "\tmomItems[num++]=[\"$lang[CHOOSERSHORT]\", \"Chooser.php\", \"entry\"]\n";
    echo <<<EOT
//  momItems[num++]=["Get Firefox!", "http://www.getfirefox.com", "_blank"]
    MOMbilden();

    //-->
    </script>
<table width="100%" cellspacing=1>
<tr>
<td><a name=0></a>
<table id="menutable" width="100%" cellpadding=0 cellspacing=0>

EOT;
    if ($secondcol != 'none')
        $numcols++;
    if ($thirdcol != 'none')
        $numcols++;
    unset($numincollection);
function ProcessChildrenOf($boxparent, &$sql, &$numthispage, &$secondcol, &$thirdcol, &$boxchildren, &$plusdisplay, &$plusgif, &$numcols, &$thisclass, $depth) {
global $db, $PHP_SELF, $mobilepage;

    $result = $db->sql_query(str_replace('%%BOXPARENT%%', $boxparent, $sql)) or die($db->sql_error());
    $numthispage += $db->sql_numrows($result);
    while ($dvd = $db->sql_fetchrow($result)) {
        if ($boxchildren == '')
            $boxchildren = "<tr id=\"bs" . str_replace('.', '_', $boxparent) . "\" $plusdisplay><td colspan=$numcols><table width=\"100%\" cellpadding=0 cellspacing=0>\n";
        UpdateDataRow($dvd);
        $secnum = ProjectAColumn($secondcol, $dvd, 'center');
        $cnum = ProjectAColumn($thirdcol, $dvd, 'right');

        $stitle = fix1252(htmlspecialchars($dvd['sorttitle'], ENT_COMPAT, 'ISO-8859-1'));
        $ttitle = FormatIcon($dvd) . DisplayDecoration(fix1252(htmlspecialchars($dvd['title'], ENT_COMPAT, 'ISO-8859-1')), $dvd);
        $dd = 10 + $depth;
        if ($dvd['boxchild'] != 0) {
            $boximg = '<img src="'.$plusgif.'" onclick="dh(\'bs'. str_replace(".", "_", $dvd['id']) .'\',this)" style="vertical-align:middle" alt=""/><span style="width:2px"></span>';
            $dd -= 15;
            $boxchildren .= "<tr $thisclass><td style=\"padding-left:{$dd}px\">"
                ."$boximg<a href=\"$mobilepage?mediaid=$dvd[id]&amp;action=show\" "
                ."title=\"$stitle\">$ttitle</a></td>"
                ."$secnum$cnum</tr><tr class=line><td colspan=$numcols></td></tr>\n"
                ."<tr id=\"bs" . str_replace('.', '_', $dvd['id']) . "\" $plusdisplay><td colspan=$numcols><table width=\"100%\" cellpadding=0 cellspacing=0>\n";
            ProcessChildrenOf($dvd['id'], $sql, $numthispage, $secondcol, $thirdcol, $boxchildren, $plusdisplay, $plusgif, $numcols, $thisclass, $depth+20);
            $boxchildren .= "</table></td></tr>";
        }
        else {
            $boxchildren .= "<tr $thisclass><td style=\"padding-left:{$dd}px\">"
                ."<a href=\"$mobilepage?mediaid=$dvd[id]&amp;action=show\" "
                ."title=\"$stitle\">$ttitle</a></td>"
                ."$secnum$cnum</tr><tr class=line><td colspan=$numcols></td></tr>\n";
        }
    }
    $db->sql_freeresult($result);
}

    $result = $db->sql_query(str_replace('%%BOXPARENT%%', '', $sql.$sqllimit)) or die($db->sql_error());
    $numthispage = $db->sql_numrows($result);

    $sepa = $oldsepa = '*-*-*-*';
    $evenodd = 0;
    $plusgif = 'gfx/plus.gif';
    $plusdisplay = 'style="display:none"';
    if ($expandboxsets) {
        $plusgif = 'gfx/minus.gif';
        $plusdisplay = '';
    }
    while ($dvd = $db->sql_fetchrow($result)) {
        UpdateDataRow($dvd);

        $secnum = ProjectAColumn($secondcol, $dvd, 'center');
        $cnum = ProjectAColumn($thirdcol, $dvd, 'right');

        GetSeparators($sort, $dvd, $sepa, $separator);
        if (strcmp($sepa, $oldsepa) != 0) {
            echo "\n<tr class=line><td colspan=$numcols></td></tr>"
                ."<tr class=a><td style=\"padding-left:12px\" colspan=$numcols>$separator</td></tr>"
                ."<tr class=line><td colspan=$numcols></td></tr><tr class=line><td colspan=$numcols></td></tr>\n";
            $evenodd = 0;
        }

        if ($evenodd % 2)
            $thisclass = 'class=l';
        else
            $thisclass = 'class=o';

        $stitle = fix1252(htmlspecialchars($dvd['sorttitle'], ENT_COMPAT, 'ISO-8859-1'));
        $ttitle = FormatIcon($dvd) . DisplayDecoration(fix1252(htmlspecialchars($dvd['title'], ENT_COMPAT, 'ISO-8859-1')), $dvd);

        $boximg = '<img src="'.$plusgif.'" onclick="dh(\'bs'. str_replace(".", "_", $dvd['id']) .'\',this)" style="vertical-align:middle" alt=""/><span style="width:2px"></span>';
        echo "<tr $thisclass><td style=\"padding-left:10px\">";
        $theboxparent = "<a href=\"$mobilepage?mediaid=$dvd[id]&amp;action=show\" "
                ."title=\"$stitle\">$ttitle</a></td>$secnum"
                ."$cnum</tr><tr class=line><td colspan=$numcols></td></tr>\n";
        $oldsepa = $sepa;
        $boxchildren = '';
        if ($stickyboxsets&&$searchby==''&&$dvd['boxchild']!=0) {
            ProcessChildrenOf($dvd['id'], $sql, $numthispage, $secondcol, $thirdcol, $boxchildren, $plusdisplay, $plusgif, $numcols, $thisclass, 40);
        }
        if ($boxchildren != '') {
            echo "$boximg$theboxparent$boxchildren</table></td></tr>\n";
            $boxchildren = '';
        }
        else {
            echo '<img src="gfx/none.gif" alt=""/><span style="width:2px"></span>'."$theboxparent";
        }
        $evenodd++;
    }

    $prev = $next = $firstpage = $lastpage = "&nbsp;";
    $prowlink = $nrowlink = "";

    if ($TitlesPerPage != 0) { // Set TitlesPerPage to 0 to turn off paging
        $replace[0] = "/&startrow=(\d+)/";
        if (!$lettermeaning)
            $replace[1] = "/&letter=./";

        if (($startrow - $TitlesPerPage) < 0)
            $prow = -1;
        else
            $prow = $startrow - $TitlesPerPage;

        if ($prow >= 0) {
            $prowlink = preg_replace($replace, '', "$PHP_SELF?$_SERVER[QUERY_STRING]")."&startrow=$prow";
            $prowlink = preg_replace("/&/", "&amp;", $prowlink);
            $s1 = "$lang[PREV] $TitlesPerPage";
            $prev = "<a class=n1 href=\"$prowlink\" target=\"menu\" onmouseover=\"status='$s1'; return true\" title=\"$s1\">$lang[PREV]</a>";
        }

        if (($startrow + $TitlesPerPage) >= $totrows)       // Changed: 29.08.2005 Thomas '=' to '>=' to avoid blank last pages
            $nrow = $startrow;
        else
            $nrow = $startrow + $TitlesPerPage;

        if ($nrow <> $startrow) {
            $nrowlink = preg_replace($replace, '', "$PHP_SELF?$_SERVER[QUERY_STRING]")."&startrow=$nrow";
            $nrowlink = preg_replace("/&/", "&amp;", $nrowlink);
            $s1 = "$lang[NEXT] $TitlesPerPage";
            $next = "<a class=n1 href=\"$nrowlink\" target=\"menu\" onmouseover=\"status='$s1'; return true\" title=\"$s1\">$lang[NEXT]</a>";
        }

        if ($prowlink) {
            $frowlink = preg_replace($replace, '', "$PHP_SELF?$_SERVER[QUERY_STRING]")."&startrow=0";
            $frowlink = preg_replace("/&/", "&amp;", $frowlink);
            $firstpage = "<a class=n1 href=\"$frowlink\" target=\"menu\" onmouseover=\"status='$lang[FIRST]'; return true\" title=\"$lang[FIRST]\">$lang[FIRST]</a>";
        }

        if ($nrowlink) {
            if (($totrows % $TitlesPerPage) == 0)                         // Added: 29.08.2005 Thomas, to avoid blank last pages
                $lpcount = floor((($totrows) / $TitlesPerPage)-1) * $TitlesPerPage;
            else
                $lpcount = floor(($totrows) / $TitlesPerPage) * $TitlesPerPage;

            $lrowlink = preg_replace($replace, '', "$PHP_SELF?$_SERVER[QUERY_STRING]")."&startrow=$lpcount";
            $lrowlink = preg_replace("/&/", "&amp;", $lrowlink);
            $lastpage = "<a class=n1 href=\"$lrowlink\" target=\"menu\" onmouseover=\"status='$lang[LAST]'; return true\" title=\"$lang[LAST]\">$lang[LAST]</a>";
        }

    }

    echo <<<EOT
<tr class=s>
<td colspan=$numcols>
<table width="100%" cellspacing=0 cellpadding=0>
<tr class=s>
<td class=t width="8%" align=center>&nbsp;</td>
<td class=t width="12%" align=center>$firstpage</td>
<td class=t width="12%" align=center>$prev</td>
<td class=t width="12%" align=center>$next</td>
<td class=t width="12%" align=center>$lastpage</td>
<td class=t width="40%" align=right>$numthispage $lang[ITEMS]</td>
</tr>
</table>
</td>
</tr>
</table></td></tr></table>$endbody</html>

EOT;
    DebugSQL($db, "$action");
    exit;
}

if ($action == 'show') {
    if (!isset($mediaid)) $mediaid = $InitialRightFrame;
    if ($mediaid == 'Chooser' && $AllowChooser) {
        include_once('Chooser.php');
        DebugSQL($db, "$action: $mediaid");
        exit;
    }
    if ($mediaid == 'Statistics') {
        include_once('statistics.php');
        DebugSQL($db, "$action: $mediaid");
        exit;
    }
    if ($mediaid == 'WatchedStatistics' && is_readable('ws.php')) {
        include_once('ws.php');
        DebugSQL($db, "$action: $mediaid");
        exit;
    }
    if ($mediaid == 'Gallery' || $mediaid == 'GalleryB') {
        include_once('gallery.php');
        DebugSQL($db, "$action: $mediaid");
        exit;
    }
    $result = $db->sql_query("SELECT * FROM $DVD_TABLE WHERE id='".$db->sql_escape($mediaid)."' LIMIT 1") or die($db->sql_error());

    $dvd = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    if ($dvd) {
        $DividerCount = 0;
        $actorsortby = 'ORDER BY lineno ASC';

        $dispimg = 'gfx/plus.gif';
        $dispstyle = ' style="display:none"';
        if ($expanddividers) {
            $dispimg = 'gfx/minus.gif';
            $dispstyle = '';
        }
// Make list of actors
        $sql = "SELECT a.*,ca.* FROM $DVD_ACTOR_TABLE a,$DVD_COMMON_ACTOR_TABLE ca WHERE a.caid=ca.caid AND id='".$db->sql_escape($mediaid)."' $actorsortby";
        $result = $db->sql_query($sql) or die($db->sql_error());
        $dvd['p_actors'] = '';
        $dvd['actors'] = array();
        $LastDivider = '';
        $LastDividerLineno = -10;
        $GroupIndent = false;
        while ($actor = $db->sql_fetchrow($result)) {
            if ($actor['caid'] > 0) {
                $extra = '';
                if ($actor['uncredited'] == 1)
                    $extra .= $lang['UNCREDITED'];
                if ($actor['voice'] == 1) {
                    if ($extra != '')
                        $extra .= ', ';
                    $extra .= $lang['VOICE'];
                }
                if ($extra != '')
                    $actor['role'] .= " ($extra)";

                $actor['oc'] = "window.open('popup.php?acttype=ACTOR&amp;fullname=$actor[caid]',"
                    ."'Actors',$ActorWindowSettings); return false;";
                GetHeadAndMouse($actor, $headcast, $castsubs, $chsimg, $mouse, $mediaid);
                $fourdots = '....';
                if ($actor['role'] == '')
                    $fourdots = '';
                $ind = '';
                if ($GroupIndent)
                    $ind = ' style="padding-left:20px"';
                $dvd['p_actors'] .= "<tr><td>$chsimg</td><td$ind><a href=\"javascript:;\" $mouse onClick=\"$actor[oc]\">"
                        .ColorName($actor, $colornames)."</a></td><td>$fourdots</td><td>$actor[role]</td></tr>\n";
            }
            else {
                if ($actor['caid'] == -1) {
                    $NotAContinuation = true;
                    if ($actor['lineno'] == ($LastDividerLineno + 1)) {
                        if ($ApplyDividerContinuations && (($FirstBlank=strpos($LastDivider, ' ')) !== false)) {
                            if (substr($actor['creditedas'], 0, $FirstBlank+1) == substr($LastDivider, 0, $FirstBlank+1)) {
                                $LastDivider .= substr($actor['creditedas'], $FirstBlank);
                                preg_match("~(.*)&nbsp;([^<]*)</td></tr><tbody$dispstyle id=Divider$DividerCount>\n$~s", $dvd['p_actors'], $matches);
                                $dvd['p_actors'] = "$matches[1]&nbsp;$LastDivider</td></tr><tbody$dispstyle id=Divider$DividerCount>\n";
                                $NotAContinuation = false;
                            }
                        }
                        if ($NotAContinuation) {
                            preg_match("~(.*)"
                                ."<img style=\"vertical-align:middle;\" src='$dispimg' onClick=\"SwitchOutRows\\('Divider$DividerCount', this\\)\">"
                                ."&nbsp;([^<]*)</td></tr><tbody$dispstyle id=Divider$DividerCount>\n$~s", $dvd['p_actors'], $matches);
                            $dvd['p_actors'] = "$matches[1]&nbsp;$LastDivider</td></tr><tbody$dispstyle id=Divider$DividerCount>\n";
                        }
                    }
                    if ($NotAContinuation) {
                        $DividerCount++;
                        $LastDivider = $actor['creditedas'];
                        $dvd['p_actors'] .= "</tbody><tr><td colspan=4 class=\"Divider\">"
                                ."<img style=\"vertical-align:middle;\" src='$dispimg' onClick=\"SwitchOutRows('Divider$DividerCount', this)\">"
                                ."&nbsp;$LastDivider</td></tr><tbody$dispstyle id=Divider$DividerCount>\n";
                    }
                    $LastDividerLineno = $actor['lineno'];
                }
                else if ($actor['caid'] == -2) {
                    $dvd['p_actors'] .= "<tr><td colspan=4 class=\"GroupDivider\">$actor[creditedas]</td></tr>\n";
                    $GroupIndent = true;
                }
                else if ($actor['caid'] == -3) {
                    $GroupIndent = false;
                }
            }
            $dvd['actors'][] = $actor;
        }
        unset($actor);
        $db->sql_freeresult($result);

// Make list of ALL credits, remember directors separately
        $sql = "SELECT c.*,ca.* FROM $DVD_CREDITS_TABLE c,$DVD_COMMON_CREDITS_TABLE ca WHERE c.caid=ca.caid AND id='".$db->sql_escape($mediaid)."' $actorsortby";
        $result = $db->sql_query($sql) or die($db->sql_error());
        $dirname = $lang['DIRECTOR'];
        $dvd['p_directors'] = '';
        $dvd['directors'] = array();
        $dvd['p_credits'] = '';
        $dvd['credits'] = array();
        $oldtype = '';
        $LastDivider = '';
        $LastDividerLineno = -10;
        $GroupIndent = false;
        $outerind = 0;
        while ($credit = $db->sql_fetchrow($result)) {
            if ($credit['caid'] > 0) {
                if ($oldtype != $credit['credittype']) {
                    $GroupIndent = false;
                    $oldtype = $credit['credittype'];
                    $ind = ($outerind == 0)? '': " style=\"padding-left:{$outerind}px\"";
                    $dvd['p_credits'] .= '<tr style="font-weight:bold"><td colspan=3'.$ind.'>'
                        .$lang[strtoupper(str_replace(' ','',$oldtype))].'</td></tr>';
                }
                $xx = ($GroupIndent == 0)? $outerind: $outerind+20;
                $ind = ($xx == 0)? '': " style=\"padding-left:{$xx}px\"";

                $credit['oc'] = "window.open('popup.php?acttype=CREDIT&amp;fullname=$credit[caid]',"
                    ."'Actors',$ActorWindowSettings); return false;";
                GetHeadAndMouse($credit, $headcrew, $crewsubs, $chsimg, $mouse, $mediaid);
                $crrole = $lang[strtoupper(str_replace(' ','',$credit['creditsubtype']))];
                if ($credit['customrole'] != '')
                    $crrole = $credit['customrole'];
                $fourdots = '....';
                if ($crrole == '')
                    $fourdots = '';
                $dvd['p_credits'] .= "<tr><td style=\"padding-left:10px\">$chsimg</td><td$ind><a href=\"javascript:;\" $mouse onClick=\""
                    ."$credit[oc]\">".ColorName($credit, $colornames)."</a></td><td>$fourdots</td><td>$crrole</td></tr>\n";
                if ($credit['creditsubtype'] == 'Director') {
                    $alreadygotone = false;
                    foreach ($dvd['directors'] as $k => $adir) {
                        if ($adir['caid'] == $credit['caid']) {
                            $alreadygotone = true;
                            break;
                        }
                    }
                    if (!$alreadygotone) {
                        if (strlen($dvd['p_directors'])>0) {
                            $dvd['p_directors'] .= ', ';
                            $dirname = $lang['DIRECTORS'];
                        }
                        $dvd['directors'][] = $credit;
                        $dvd['p_directors'] .= "<a href=\"javascript:;\" onClick=\"$credit[oc]\">".ColorName($credit, $colornames).'</a>';
                    }
                }
            }
            else {
                if ($credit['caid'] == -1) {
                    $NotAContinuation = true;
                    if ($credit['lineno'] == ($LastDividerLineno + 1)) {
                        if ($ApplyDividerContinuations && (($FirstBlank=strpos($LastDivider, ' ')) !== false)) {
                            if (substr($credit['creditedas'], 0, $FirstBlank+1) == substr($LastDivider, 0, $FirstBlank+1)) {
                                $LastDivider .= substr($credit['creditedas'], $FirstBlank);
                                preg_match("~(.*)&nbsp;([^<]*)</td></tr><tbody$dispstyle id=Divider$DividerCount>\n$~s", $dvd['p_credits'], $matches);
                                $dvd['p_credits'] = "$matches[1]&nbsp;$LastDivider</td></tr><tbody$dispstyle id=Divider$DividerCount>\n";
                                $NotAContinuation = false;
                            }
                        }
                        if ($NotAContinuation) {
                            preg_match("~(.*)"
                                ."<img style=\"vertical-align:middle;\" src='$dispimg' onClick=\"SwitchOutRows\\('Divider$DividerCount', this\\)\">"
                                ."&nbsp;([^<]*)</td></tr><tbody$dispstyle id=Divider$DividerCount>\n$~s", $dvd['p_credits'], $matches);
                            $dvd['p_credits'] = "$matches[1]&nbsp;$LastDivider</td></tr><tbody$dispstyle id=Divider$DividerCount>\n";
                        }
                    }
                    if ($NotAContinuation) {
                        $DividerCount++;
                        $oldtype = '';
                        $LastDivider = $credit['creditedas'];
                        $dvd['p_credits'] .= "</tbody><tr><td colspan=4 class=\"Divider\">"
                                ."<img style=\"vertical-align:middle;\" src='$dispimg' onClick=\"SwitchOutRows('Divider$DividerCount', this)\">"
                                ."&nbsp;$LastDivider</td></tr><tbody$dispstyle id=Divider$DividerCount>\n";
                    }
                    $LastDividerLineno = $credit['lineno'];
                    $outerind = 20;
                }
                else if ($credit['caid'] == -2) {
                    $ind = ($outerind == 0)? '': " style=\"padding-left:{$outerind}px\"";
                    $dvd['p_credits'] .= "<tr><td$ind colspan=4 class=\"GroupDivider\">$credit[creditedas]</td></tr>\n";
                    $GroupIndent = true;
                }
                else if ($credit['caid'] == -3) {
                    $GroupIndent = false;
                }
            }
            $dvd['credits'][] = $credit;
        }
        unset($credit);
        $db->sql_freeresult($result);

        $IMDBNum = array();
// Make list of discs - ajm
        $result = $db->sql_query("SELECT * FROM $DVD_DISCS_TABLE WHERE id='".$db->sql_escape($mediaid)."' ORDER BY discno ASC") or die($db->sql_error());
        $dvd['p_discs'] = '';
        $dvd['discs'] = array();
        $locval = '';
        while ($discs = $db->sql_fetchrow($result)) {
            if ($IMDBNumFromSlot && $discs['slot'] != '')
                $IMDBNum[] = 'tt' . $discs['slot'];
            if ($discs['location'] == '')
                $discs['location'] = ' ';
            if ($discs['slot'] == '')
                $discs['slot'] = ' ';
            if (!$IsPrivate) {
                $discs['location'] = '';
                $discs['slot'] = '';
            }
            $dvd['discs'][] = $discs;
            if ($dvd['p_discs'] == '')
                $locval = "$discs[location], $discs[slot]";
            $dvd['p_discs'] .= "<tr>";
            $dvd['p_discs'] .= "<td class=f2np>$discs[discno]</td>";
            $z = $discs['discdescsidea'];
            if ($discs['discdescsideb'] != '')
                $z .= ", $discs[discdescsideb]";
            $dvd['p_discs'] .= "<td class=f2np>$z</td>";
            if ($discs['discidsidea'].$discs['labelsidea'] != '') {
                $t = "$discs[discidsidea]";
                if ($discs['labelsidea'] != '') $t .= " [$discs[labelsidea]]";
                $discs['discidsidea'] = "<img src=\"gfx/discid.gif\" alt=\"\" title=\"$lang[SIDEAID] = ".trim($t)."\"/>";
            }
            if ($discs['discidsideb'].$discs['labelsideb'] != '') {
                $t = "$discs[discidsideb]";
                if ($discs['labelsideb'] != '') $t .= " [$discs[labelsideb]]";
                $discs['discidsideb'] = "<img src=\"gfx/discid.gif\" alt=\"\" title=\"$lang[SIDEBID] = ".trim($t)."\"/>";
            }
            if ($discs['dualsided'] == '1')
                $discs['dualsided'] = "<img src=\"gfx/check.gif\" title=\"$lang[DUALSIDED]\" alt=\"\"/>";
            else
                $discs['dualsided'] = '';
            if ($discs['duallayeredsidea'] == '1')
                $discs['duallayeredsidea'] = "<img src=\"gfx/check.gif\" title=\"$lang[DUALLAYERED]\" alt=\"\"/>";
            else
                $discs['duallayeredsidea'] = '';
            if ($discs['duallayeredsideb'] == '1')
                $discs['duallayeredsideb'] = "<img src=\"gfx/check.gif\" title=\"$lang[DUALLAYERED]\" alt=\"\"/>";
            else
                $discs['duallayeredsideb'] = '';
            $dvd['p_discs'] .= "<td class=f2np align=center>$discs[discidsidea]</td>";
            $dvd['p_discs'] .= "<td class=f2np align=center>$discs[discidsideb]</td>";
            $dvd['p_discs'] .= "<td class=f2np align=center>$discs[dualsided]</td>";
            $dvd['p_discs'] .= "<td class=f2np align=center>$discs[duallayeredsidea]</td>";
            $dvd['p_discs'] .= "<td class=f2np align=center>$discs[duallayeredsideb]</td>";
            if ($IsPrivate) {
                $dvd['p_discs'] .= "<td class=f2np>$discs[location]</td>";
                $dvd['p_discs'] .= "<td class=f2np>$discs[slot]</td>";
            }
            $dvd['p_discs'] .= "</tr>";
        }
        unset($discs);
        $db->sql_freeresult($result);
        if ($locval == ', ')
            $locval = '';

// Make list of events - ajm
        $dvd['p_events'] = '';
        $dvd['events'] = array();
        $dvd['lastwatched'] = '';
        if ($IsPrivate) {
            $sql = "SELECT timestamp,eventtype,note,u.* FROM $DVD_EVENTS_TABLE e, $DVD_USERS_TABLE u WHERE e.uid=u.uid AND id='".$db->sql_escape($mediaid)."' ORDER BY timestamp DESC";
            $result = $db->sql_query($sql) or die($db->sql_error());
            while ($events = $db->sql_fetchrow($result)) {
                if (isset($events['timestamp']))
                    list($ts_year, $ts_month, $ts_day) = explode('-', substr($events['timestamp'], 0, 10));
                else
                    $ts_day = $ts_month = $ts_year = 0;
                if ($events['eventtype'] == 'Watched' && $dvd['lastwatched'] == '') {
                    $dvd['lastwatched'] = my_mktime(0, 0, 0, $ts_month, $ts_day, $ts_year);
                }
                $events['timestamp'] = fix88595(ucwords(strftimeReplacement($lang['DATEFORMAT'], my_mktime(0, 0, 0, $ts_month, $ts_day, $ts_year))));
                $dvd['p_events'] .= "<tr>";
                $dvd['p_events'] .= "<td class=f2>$events[firstname] $events[lastname]</td>";
                $dvd['p_events'] .= "<td class=f2 align=center>$events[phonenumber]</td>";
                $dvd['p_events'] .= "<td class=f2 align=center>$events[emailaddress]</td>";
                $dvd['p_events'] .= "<td class=f2 align=center>$events[eventtype]</td>";
                $dvd['p_events'] .= "<td class=f2 align=center>$events[timestamp]</td>";
                $dvd['p_events'] .= "<td class=f2 align=left>$events[note]</td>";
                $dvd['p_events'] .= "</tr>";
                $dvd['events'][] = $events;
            }
            unset($events);
        }

        $locks['entire'] = $locks['covers'] = $locks['title'] = $locks['cast'] = '';
        $locks['crew'] = $locks['discinfo'] = $locks['studios'] = $locks['srp'] = '';
        $locks['audio'] = $locks['regions'] = $locks['overview'] = $locks['genres'] = '';
        $locks['features'] = $locks['subtitles'] = $locks['eastereggs'] = $locks['runningtime'] = '';
        $locks['releasedate'] = $locks['productionyear'] = $locks['casetype'] = $locks['videoformats'] = '';
        $locks['rating'] = $locks['mediatype'] = '';

        if (DisplayIfIsPrivateOrAlways($searchlocks)) {
// Make list of locks - ajm
            $result = $db->sql_query("SELECT * FROM $DVD_LOCKS_TABLE WHERE id='".$db->sql_escape($mediaid)."'") or die($db->sql_error());
            $locks = $db->sql_fetchrow($result);
            $db->sql_freeresult($result);
            $locked = " <img src=\"gfx/locked.gif\" title=\"$lang[LOCKED]\" alt=\"\"/>";
            if ($locks['entire']) {
                $locks['entire'] = $locks['covers'] = $locks['title'] = $locks['cast'] = $locked;
                $locks['crew'] = $locks['discinfo'] = $locks['studios'] = $locks['srp'] = $locked;
                $locks['audio'] = $locks['regions'] = $locks['overview'] = $locks['genres'] = $locked;
                $locks['features'] = $locks['subtitles'] = $locks['eastereggs'] = $locks['runningtime'] = $locked;
                $locks['releasedate'] = $locks['productionyear'] = $locks['casetype'] = $locked;
                $locks['rating'] = $locks['videoformats'] = $locks['mediatype'] = $locked;
            }
            else {
                $locks['entire'] = '';
                $locks['covers'] = (empty($locks['covers'])) ? '' : $locked;
                $locks['title'] = (empty($locks['title'])) ? '' : $locked;
                $locks['mediatype'] = (empty($locks['mediatype'])) ? '' : $locked;
                $locks['overview'] = (empty($locks['overview'])) ? '' : $locked;
                $locks['regions'] = (empty($locks['regions'])) ? '' : $locked;
                $locks['genres'] = (empty($locks['genres'])) ? '' : $locked;
                $locks['srp'] = (empty($locks['srp'])) ? '' : $locked;
                $locks['studios'] = (empty($locks['studios'])) ? '' : $locked;
                $locks['discinfo'] = (empty($locks['discinfo'])) ? '' : $locked;
                $locks['cast'] = (empty($locks['cast'])) ? '' : $locked;
                $locks['crew'] = (empty($locks['crew'])) ? '' : $locked;
                $locks['features'] = (empty($locks['features'])) ? '' : $locked;
                $locks['audio'] = (empty($locks['audio'])) ? '' : $locked;
                $locks['subtitles'] = (empty($locks['subtitles'])) ? '' : $locked;
                $locks['eastereggs'] = (empty($locks['eastereggs'])) ? '' : $locked;
                $locks['runningtime'] = (empty($locks['runningtime'])) ? '' : $locked;
                $locks['releasedate'] = (empty($locks['releasedate'])) ? '' : $locked;
                $locks['productionyear'] = (empty($locks['productionyear'])) ? '' : $locked;
                $locks['casetype'] = (empty($locks['casetype'])) ? '' : $locked;
                $locks['videoformats'] = (empty($locks['videoformats'])) ? '' : $locked;
                $locks['rating'] = (empty($locks['rating'])) ? '' : $locked;
            }
        }

// Make list of audio specs
        $dvd['p_audio'] = '';
        $dvd['audio'] = array();
        $result = $db->sql_query("SELECT * FROM $DVD_AUDIO_TABLE WHERE id='".$db->sql_escape($mediaid)."' ORDER BY dborder ASC") or die($db->sql_error());
        while ($audio = $db->sql_fetchrow($result)) {
            $dvd['audio'][] = $audio;
            if (is_null($audio['audiochannels'])) {
                $dvd['p_audio'] .= "<tr>";
                $dvd['p_audio'] .= "<td class=f2np style=\"padding-left:5px\">".$alang_translation[$audio['audiocontent']]."</td>";
                $dvd['p_audio'] .= "<td class=f2np>".$aformat_translation[$audio['audioformat']]."</td>";
                $dvd['p_audio'] .= "<td class=f2np>".$aformat_image[$audio['audioformat']]."</td>";
                $dvd['p_audio'] .= "</tr>";
            }
            else {
                $dvd['p_audio'] .= "<tr>";
                $dvd['p_audio'] .= "<td class=f2np style=\"padding-left:5px\">".$alang_translation[$audio['audiocontent']]."</td>";
                $dvd['p_audio'] .= "<td class=f2np>".$aformat_translation[$audio['audioformat']]."</td>";
                $dvd['p_audio'] .= "<td class=f2np>".$newaformat_image[$audio['audiochannels']].' '.$aformat_name[$audio['audioformat']].' '.$newachan_name[$audio['audiochannels']]."</td>";
                $dvd['p_audio'] .= "</tr>";
            }
        }
        unset($audio);
        $db->sql_freeresult($result);
        if ($dvd['p_audio'] != '')
            $dvd['p_audio'] = '<table width="100%" cellpadding=0 cellspacing=0>'.$dvd['p_audio'].'</table>';
        else
            $dvd['p_audio'] = '&nbsp;';

// Make list of subtitles
        $dvd['p_subtitles'] = '';
        $dvd['subtitles'] = array();
        $result = $db->sql_query("SELECT * FROM $DVD_SUBTITLE_TABLE WHERE id='".$db->sql_escape($mediaid)."' ORDER BY subtitle ASC") or die($db->sql_error());
        while ($subtitle = $db->sql_fetchrow($result)) {
            if (strlen($dvd['p_subtitles']) > 0) {
                $dvd['p_subtitles'] .= "<br>";
            }
            $dvd['p_subtitles'] .= $alang_translation[$subtitle['subtitle']];
            $dvd['subtitles'][] = $subtitle['subtitle'];
        }
        unset($subtitle);
        $db->sql_freeresult($result);
        if ($dvd['p_subtitles'] == '') $dvd['p_subtitles'] = '&nbsp;';

// Make list of studios
        $dvd['p_studios'] = '';
        $ps = $mc = '';
        $dvd['studios'] = array();
        $dvd['mediacompanies'] = array();
        $result = $db->sql_query("SELECT * FROM $DVD_STUDIO_TABLE WHERE id='".$db->sql_escape($mediaid)."' ORDER BY ismediacompany ASC,dborder ASC") or die($db->sql_error());
        while ($studio = $db->sql_fetchrow($result)) {
            $NewWindow = "window.open('popup.php?acttype=STUDIO&amp;fullname="
                .urlencode($studio['studio'])."','Actors',$ActorWindowSettings); return false;";
            if ($studio['ismediacompany'] == 0) {
                if ($ps == '')
                    $ps = "<span style='font-weight:bold'>$lang[PRODUCTIONSTUDIOS]:</span>";
                $ps .= "<br>$bullet<a href=\"javascript:;\" onClick=\"$NewWindow\">$studio[studio]</a>";
                $dvd['studios'][] = array($studio['ismediacompany'], $studio['studio']);
            }
            if ($studio['ismediacompany'] == 1) {
                if ($mc == '')
                    $mc = "<span style='font-weight:bold'>$lang[MEDIACOMPANIES]:</span>";
                $mc .= "<br>$bullet<a href=\"javascript:;\" onClick=\"$NewWindow\">$studio[studio]</a>";
                $dvd['mediacompanies'][] = array($studio['ismediacompany'], $studio['studio']);
            }
            unset($NewWindow);
        }
        unset($studio);
        $db->sql_freeresult($result);
        $dvd['p_studios'] = "$ps<br>$mc";
        unset($ps);
        unset($mc);

        $dvd['p_genres'] = '';
        $dvd['genres'] = array();
        $result = $db->sql_query("SELECT genre FROM $DVD_GENRES_TABLE WHERE id='".$db->sql_escape($mediaid)."' ORDER BY dborder") or die($db->sql_error());
        while ($row = $db->sql_fetchrow($result)) {
            $dvd['p_genres'] .= '<br>' . GenreTranslation($row['genre']);
            $dvd['genres'][] = $row['genre'];
        }
        unset($row);
        $db->sql_freeresult($result);
        if ($dvd['p_genres'] != '')
            $dvd['p_genres'] = substr($dvd['p_genres'], 4);

        $dvd['p_released'] = ($dvd['released'] === null? '': fix88595(ucwords(strftimeReplacement($lang['DATEFORMAT'], $dvd['released']))));
        $dvd['p_purchasedate'] = fix88595(ucwords(strftimeReplacement($lang['DATEFORMAT'], $dvd['purchasedate'])));
        if ($dvd['collectiontype'] == 'wishlist')
            $dvd['p_purchasedate'] = '';

        $dvd['format'] = '';
        if ($dvd['builtinmediatype'] == MEDIA_TYPE_DVD || $dvd['builtinmediatype'] == MEDIA_TYPE_HDDVD_DVD || $dvd['builtinmediatype'] == MEDIA_TYPE_BLURAY_DVD)
            $dvd['format'] .= $dvd['formatvideostandard'];

        if ($dvd['format'] != '')
            $dvd['format'] .= ',';
        $colours = ''
            . (($dvd['formatcolorcolor']==1)    ? ", $lang[COLOR]": '')
            . (($dvd['formatcolorbw']==1)       ? ", ".$lang['BLACK&WHITE']: '')
            . (($dvd['formatcolorcolorized']==1)    ? ", $lang[COLORIZED]": '')
            . (($dvd['formatcolormixed']==1)    ? ", $lang[MIXED]": '');
        if ($colours == '') {
            $colours = $lang['COLORUNSET'];
        }
        else {
            $colours = substr($colours, 2);
            if (strpos($colours, ',') !== false) $colours = "($colours)";
        }
        $dvd['format'] .= ' ' . $colours;

        $dynamicRange = (($dvd['drhdr10']==1)   ? '(' . $dynamicrange_translation['HDR10'] . ')' : '')
            . (($dvd['drdolbyvision']==1) ? '(' . $dynamicrange_translation['DOLBYVISION'] . ')' : '');

        if ($dvd['drhdr10']==1 && $dvd['drdolbyvision']==1) {
            $dynamicRange = '(' . $dynamicrange_translation['HDR10']. ', ' . $dynamicrange_translation['DOLBYVISION'] . ')';
        }

        if ($dynamicRange != '') {
            $dvd['format'] .= ' ' . $dynamicRange;
        }

        if ($dvd['format'] != '')
            $dvd['format'] .= ',';
        $dims = ''
            . (($dvd['dim2d']==1)           ? ", $lang[DIM2D]": '')
            . (($dvd['dim3danaglyph']==1)       ? ", $lang[DIM3DANAGLYPH]": '')
            . (($dvd['dim3dbluray']==1)     ? ", $lang[DIM3DBLURAY]": '');

        if ($dims == '') {
            $dims = $lang['DIM2D'];
        }
        else {
            $dims = substr($dims, 2);
            if (strpos($dims, ',') !== false) $dims = "($dims)";
        }
// The following code says that if the dimensions are just 2D (most DVDs and Blu-rays) then omit the 2D designation
        if ($dims != $lang['DIM2D'])
            $dvd['format'] .= ' ' . $dims;

        if ($dvd['formataspectratio'] != '')
            $dvd['format'] .= " $dvd[formataspectratio]:1";
        $dvd['format'] .=
              (($dvd['format16x9']==1)             ? " {$lang['16X9']}":       '')
            . (($dvd['formatletterbox']==1)        ? " $lang[WIDESCREEN]": '');
        $dvd['format'] .=
              (($dvd['formatpanandscan']==1)       ? ", $lang[PANANDSCAN]": '')
            . (($dvd['formatfullframe']==1)        ? ", $lang[FULLFRAME]": '');
        $dvd['format'] = preg_replace('/^,\s+/', '', $dvd['format']);

        $dvd['media'] = (($dvd['formatdualsided']==1)  ? $lang['DUALSIDED']: $lang['SINGLESIDED'])
            . (($dvd['formatduallayered']==1)      ? ", $lang[DUALLAYERED]": ", $lang[SINGLELAYERED]");
        $dvd['media'] = preg_replace('/^, /', '', $dvd['media']);

        $dvd['p_extras'] = (($dvd['featuresceneaccess']==1) ? ", $lang[SCENEACCESS]": '')
            . ((isset($dvd['featureplayall']) && $dvd['featureplayall']==1)     ? ", $lang[PLAYALL]": '')
            . (($dvd['featuretrailer']==1)          ? ", $lang[TRAILER]": '')
            . (($dvd['featurebonustrailers']==1)        ? ", $lang[BONUSTRAILERS]": '')
            . (($dvd['featuremakingof']==1)         ? ", $lang[MAKINGOF]": '')
            . (($dvd['featurecommentary']==1)       ? ", $lang[COMMENTARY]": '')
            . (($dvd['featuredeletedscenes']==1)        ? ", $lang[DELETEDSCENES]": '')
            . (($dvd['featureinterviews']==1)       ? ", $lang[INTERVIEWS]": '')
            . (($dvd['featureouttakes']==1)         ? ", $lang[OUTTAKES]": '')
            . (($dvd['featurestoryboardcomparisons']==1)    ? ", $lang[STORYBOARDCOMPARISONS]": '')
            . (($dvd['featurephotogallery']==1)     ? ", $lang[PHOTOGALLERY]": '')
            . (($dvd['featureproductionnotes']==1)      ? ", $lang[PRODUCTIONNOTES]": '')
            . (($dvd['featuredvdromcontent']==1)        ? ", $lang[DVDROMCONTENT]": '')
            . (($dvd['featuregame']==1)         ? ", $lang[GAME]": '')
            . (($dvd['featuremultiangle']==1)       ? ", $lang[MULTIANGLE]": '')
            . (($dvd['featuremusicvideos']==1)      ? ", $lang[MUSICVIDEOS]": '')
            . (($dvd['featurethxcertified']==1)     ? ", $lang[THXCERTIFIED]": '')
            . (($dvd['featureclosedcaptioned']==1)      ? ", $lang[CLOSEDCAPTIONED]": '')
            . (($dvd['featuredigitalcopy']==1)      ? ", $lang[DIGITALCOPY]": '')
            . (($dvd['featurepip']==1)      ? ", $lang[PIP]": '')
            . (($dvd['featurebdlive']==1)           ? ", $lang[BDLIVE]": '')
            . ((isset($dvd['featuredbox']) && $dvd['featuredbox']==1)           ? ", $lang[DBOX]": '')
            . ((isset($dvd['featurecinechat']) && $dvd['featurecinechat']==1)           ? ", $lang[CINECHAT]": '')
            . ((isset($dvd['featuremovieiq']) && $dvd['featuremovieiq']==1)     ? ", $lang[MOVIEIQ]": '')
            . ((strlen($dvd['featureother'])>0)     ? ", $dvd[featureother]": '');

        $dvd['extras'] = explode("<br>$bullet", $dvd['p_extras']);
        unset($dvd['extras'][0]);   // first element is always blank
        if (strlen($dvd['p_extras']) > 0)
            $dvd['p_extras'] = trim(substr($dvd['p_extras'], strpos($dvd['p_extras'], ',') + 1));
        else
            $dvd['p_extras'] = '&nbsp;';

        $dvd['p_overview'] = $dvd['overview'];
        if (!$AllowHTMLInOverview)
            $dvd['p_overview'] = htmlspecialchars($dvd['p_overview'], ENT_COMPAT, 'ISO-8859-1');
        $dvd['p_overview'] = nl2br(fix1252($dvd['p_overview']));

        $dvd['thumbs'] = '&nbsp;';
        $dvd['frontimage'] = $dvd['frontthumb'] = $dvd['frontimageanchor'] = '';
        $dvd['backimage'] = $dvd['backthumb'] = $dvd['backimageanchor'] = '';

        FormatTheTitle($dvd);
        $TheTitle = fix1252(htmlspecialchars($dvd['title'], ENT_COMPAT, 'ISO-8859-1'));

        $ImageNotFound = '';
        if (is_readable('gfx/unknown.jpg'))
            $ImageNotFound = 'gfx/unknown.jpg';
        if ($getimages > 0) {
            $hdflogo = $hdblogo = $tfclass = $tbclass = '';
            if ($AddHDLogos) {
                if ($dvd['mediabannerfront'] >= 0)
                    $ban = $MediaTypes[$dvd['mediabannerfront']]['Banner'];
                else
                    $ban = @$MediaTypes[$dvd['custommediatype']]['Banner'];
                if ($ban != '') {
                    $hdflogo = "<img width=\"$thumbwidth\" src=\"$ban\" class=hdlogo alt=\"$TheTitle\" title=\"$TheTitle\"/><br>";
                    $tfclass = "class=fthumb ";
                }
                if ($dvd['mediabannerback'] >= 0)
                    $ban = $MediaTypes[$dvd['mediabannerback']]['Banner'];
                else
                    $ban = @$MediaTypes[$dvd['custommediatype']]['Banner'];
                if ($ban != '') {
                    $hdblogo = "<img width=\"$thumbwidth\" src=\"$ban\" class=hdlogo alt=\"$TheTitle\" title=\"$TheTitle\"/><br>";
                    $tbclass = "class=fthumb ";
                }
            }
            if ($getimages == 3) {
                $dvd['frontimage'] = "{$img_webpathf}$dvd[id]f.jpg";
                $dvd['frontthumb'] = "{$img_webpathf}$thumbnails/$dvd[id]f.jpg";
                $dvd['backimage']  = "{$img_webpathb}$dvd[id]b.jpg";
                $dvd['backthumb']  = "{$img_webpathb}$thumbnails/$dvd[id]b.jpg";
                if ($popupimages) {
                    $NewWindow = MakeImageWindow($dvd['frontimage'], $dvd['id'], $dvd['mediabannerfront']);
                    $dvd['frontimageanchor'] = "<a href=\"#\" onClick=\"$NewWindow\">";
                    $NewWindow = MakeImageWindow($dvd['backimage'], $dvd['id'], $dvd['mediabannerback']);
                    $dvd['backimageanchor'] = "<a href=\"#\" onClick=\"$NewWindow\">";
                }
                else {
                    $dvd['frontimageanchor'] = "<a href=\"$PHP_SELF?img=$dvd[frontimage]&amp;mediaid=$dvd[id]&amp;mtype=$dvd[mediabannerfront]\" target=\"_self\">";
                    $dvd['backimageanchor'] = "<a href=\"$PHP_SELF?img=$dvd[backimage]&amp;mediaid=$dvd[id]&amp;mtype=$dvd[mediabannerback]\" target=\"_self\">";
                }
                $dvd['thumbs']  = "$dvd[frontimageanchor]$hdflogo<img width=\"$thumbwidth\" src=\"$dvd[frontthumb]\" alt=\"$TheTitle\" title=\"$TheTitle\"/></a><br>";
                $dvd['thumbs'] .= "$dvd[backimageanchor]$hdblogo<img width=\"$thumbwidth\" src=\"$dvd[backthumb]\" alt=\"$TheTitle\" title=\"$TheTitle\"/></a><br>";
            }
            else {
// BTW, the logic and code in here is quite disgusting. I apologise.
                $finame = find_a_file($dvd['id'], true, false);
                $ftname = find_a_file($dvd['id'], true, true);
                $biname = find_a_file($dvd['id'], false, false);
                $btname = find_a_file($dvd['id'], false, true);

                if ($ftname != '') {
                    $dvd['frontthumb'] = "{$img_webpathf}$thumbnails/$ftname";
                }
                else {
                    if ($finame != '') {
                        $dvd['frontthumb'] = "{$img_webpathf}$finame";
                    }
                    else {
                        if ($dvd['boxparent'] != '') {
                            $finame = find_a_file($dvd['boxparent'], true, false);
                            $ftname = find_a_file($dvd['boxparent'], true, true);
                            if ($ftname != '') {
                                $dvd['frontthumb'] = "{$img_webpathf}$thumbnails/$ftname";
                            }
                            else {
                                if ($finame != '') {
                                    $dvd['frontthumb'] = "{$img_webpathf}$finame";
                                }
                                else {
                                    $dvd['frontthumb'] = $ImageNotFound;
                                }
                            }
                        }
                        else {
                            $dvd['frontthumb'] = $ImageNotFound;
                        }
                    }
                }
                if ($btname != '') {
                    $dvd['backthumb'] = "{$img_webpathb}$thumbnails/$btname";
                }
                else {
                    if ($biname != '') {
                        $dvd['backthumb'] = "{$img_webpathb}$biname";
                    }
                    else {
                        if ($dvd['boxparent'] != '') {
                            $biname = find_a_file($dvd['boxparent'], false, false);
                            $btname = find_a_file($dvd['boxparent'], false, true);
                            if ($btname != '') {
                                $dvd['backthumb'] = "{$img_webpathb}$thumbnails/$btname";
                            }
                            else {
                                if ($biname != '') {
                                    $dvd['backthumb'] = "{$img_webpathb}$biname";
                                }
                                else {
                                    $dvd['backthumb'] = $ImageNotFound;
                                }
                            }
                        }
                        else {
                            $dvd['backthumb'] = $ImageNotFound;
                            if ($NoBackImageNotFound)
                                $dvd['backthumb'] = '';
                        }
                    }
                }

                if ($finame != '') {
                    $dvd['frontimage'] = "{$img_webpathf}$finame";
                    if ($popupimages) {
                        $NewWindow = MakeImageWindow($img_physpath.$finame, $dvd['id'], $dvd['mediabannerfront']);
                        $dvd['frontimageanchor'] = "<a href=\"#\" onClick=\"$NewWindow\">";
                    }
                    else {
                        $dvd['frontimageanchor'] = "<a href=\"$PHP_SELF?img=$dvd[frontimage]&amp;mediaid=$dvd[id]&amp;mtype=$dvd[mediabannerfront]\" target=\"_self\">";
                    }
                    $dvd['thumbs'] = "$dvd[frontimageanchor]$hdflogo<img width=\"$thumbwidth\" src=\"$dvd[frontthumb]\" $tfclass alt=\"$TheTitle\" title=\"$TheTitle\"/></a><br>";
                }
                else {
                    if ($dvd['frontthumb'] != '')
                        $dvd['thumbs'] = "$hdflogo<img width=\"$thumbwidth\" src=\"$dvd[frontthumb]\" alt=\"$TheTitle\" title=\"$TheTitle\"/><br>";
                }
                if ($biname != '') {
                    $dvd['backimage'] = "{$img_webpathb}$biname";
                    if ($popupimages) {
                        $NewWindow = MakeImageWindow($img_physpath.$biname, $dvd['id'], $dvd['mediabannerback']);
                        $dvd['backimageanchor'] = "<a href=\"#\" onClick=\"$NewWindow\">";
                    }
                    else {
                        $dvd['backimageanchor'] = "<a href=\"$PHP_SELF?img=$dvd[backimage]&amp;mediaid=$dvd[id]&amp;mtype=$dvd[mediabannerback]\" target=\"_self\">";
                    }
                    $dvd['thumbs'] .= "$dvd[backimageanchor]$hdblogo<img width=\"$thumbwidth\" src=\"$dvd[backthumb]\" $tbclass alt=\"$TheTitle\" title=\"$TheTitle\"/></a><br>";
                }
                else {
                    if ($dvd['backthumb'] != '')
                        if ($finame != '') {    // special case for large image = front + back; make both point to it
                            $dvd['backimage'] = "{$img_webpathf}$finame";
                            if ($popupimages) {
                                $NewWindow = MakeImageWindow($img_physpath.$finame, $dvd['id'], $dvd['mediabannerfront']);
                                $dvd['backimageanchor'] = "<a href=\"#\" onClick=\"$NewWindow\">";
                            }
                            else {
                                $dvd['backimageanchor'] = "<a href=\"$PHP_SELF?img=$dvd[backimage]&amp;mediaid=$dvd[id]&amp;mtype=$dvd[mediabannerfront]\" target=\"_self\">";
                            }
                            $dvd['thumbs'] .= "$dvd[backimageanchor]$hdflogo<img width=\"$thumbwidth\" src=\"$dvd[backthumb]\" alt=\"$TheTitle\" title=\"$TheTitle\"/></a><br>";

                        }
                        else
                            $dvd['thumbs'] .= "<img width=\"$thumbwidth\" src=\"$dvd[backthumb]\" alt=\"$TheTitle\" title=\"$TheTitle\"/><br>";
                }

            }
        }
        $dvd['p_reviewfilm'] = $dvd['p_reviewvideo'] = $dvd['p_reviewaudio'] = $dvd['p_reviewextras'] = '';
        $temp = FixAReviewValue($dvd['reviewfilm']);
        if ($temp != 0 || $displayreviewsEQ0)
            $dvd['p_reviewfilm']   = '<img src="gfx/' . sprintf('%02d', $temp) . '.gif" alt="'.$temp.' / 10">';
        $temp = FixAReviewValue($dvd['reviewvideo']);
        if ($temp != 0 || $displayreviewsEQ0)
            $dvd['p_reviewvideo']  = '<img src="gfx/' . sprintf('%02d', $temp) . '.gif" alt="'.$temp.' / 10">';
        $temp = FixAReviewValue($dvd['reviewaudio']);
        if ($temp != 0 || $displayreviewsEQ0)
            $dvd['p_reviewaudio']  = '<img src="gfx/' . sprintf('%02d', $temp) . '.gif" alt="'.$temp.' / 10">';
        $temp = FixAReviewValue($dvd['reviewextras']);
        if ($temp != 0 || $displayreviewsEQ0)
            $dvd['p_reviewextras'] = '<img src="gfx/' . sprintf('%02d', $temp) . '.gif" alt="'.$temp.' / 10">';

        $locale = substr(strstr($dvd['id'], '.'), 1, 2);
        if (!$locale)
            $locale = '0';

        $regions = "<a target=\"_blank\" href=\"http://www.invelos.com/Forums.aspx?task=contributionnotes&amp;type=DVD&amp;ProfileUPC=$dvd[id]\">$dvd[upc]</a>";
        $regions .= " <img src=\"gfx/loc$locale.gif\" style=\"vertical-align:-30%; margin-bottom:2px\" title=\"" . $lang['LOCALE'.$locale] .'" alt=""/> ';

        if (strstr($dvd['region'], '0') !== false) {
            $regions .= "<img src=\"gfx/region_0.gif\" style=\"vertical-align:-30%; margin-bottom:2px\" title=\"$lang[ALLREGIONS]\" alt=\"\"/>\n";
        }
        else if (strstr($dvd['region'], '@') !== false) {
            $regions .= "<img height=\"17px\" src=\"gfx/region_ABC.gif\" style=\"vertical-align:-30%; margin-bottom:2px\" title=\"$lang[ALLREGIONS]\" alt=\"\"/>\n";
        }
        else {
// should handle AC/BC/AB differently
            for ($i=0; $i<strlen($dvd['region']); $i++) {
                $regions .= "<img height=\"17px\" src=\"gfx/region_".substr($dvd['region'], $i, 1).".gif\" style=\"vertical-align:-30%; margin-bottom:2px\" title=\"$lang[REGION] ".substr($dvd['region'], $i, 1)."\" alt=\"\"/>\n";
            }
        }

        $db->sql_freeresult($result);
        $dvd['p_casetype'] = $lang[strtoupper(str_replace(' ', '', $dvd['casetype']))];
        if ($dvd['caseslipcover'] != 0)
            $dvd['p_casetype'] .= ", $lang[SLIPCOVER]";
        if ($dvd['collectiontype'] == 'wishlist') {
            $cnum = '('.$lang['WISHNAME'.$dvd['wishpriority']].')';
        }
        else {
            $cnum = "(#$dvd[collectionnumber])";
            if ($cnum == '(#)' || $cnum == '(#0)')
                $cnum = '';
        }
        $ctype = CustomTranslation(strtoupper(str_replace(' ', '', $dvd['realcollectiontype'])), $dvd['realcollectiontype']);
        $j = $dvd['runningtime']%60;
        if ($j < 10) $j = '0'.$j;
        $runtime = floor($dvd['runningtime']/60) . ":$j ($dvd[runningtime] $lang[MINUTES])";

// V3 has pre-formatted field
//      $dvd['srp'] = my_money_format($dvd['srpcurrencyid'], $dvd['srpdec']);

        $dvd['p_tags'] = '';
        $dvd['tags'] = array();
        $HasEPG = false;

        $result = $db->sql_query("SELECT * FROM $DVD_TAGS_TABLE WHERE id='".$db->sql_escape($mediaid)."' ORDER BY fullyqualifiedname") or die($db->sql_error());

        while ($tags = $db->sql_fetchrow($result)) {
            if ($tags['fullyqualifiedname'] == $EPGTagname)
                $HasEPG = true;
            $dvd['tags'][] = $tags;
            if (strlen($dvd['p_tags']) > 0)
                $dvd['p_tags'] .= "<br>";
            $array = explode('/', $tags['fullyqualifiedname']);
            $tags['fullyqualifiedname'] = implode(': ', $array);
            $dvd['p_tags'] .= $tags['fullyqualifiedname'];
        }
        unset($tags);
        $db->sql_freeresult($result);
        if (!DisplayIfIsPrivateOrAlways($searchtags)) {
            unset($dvd['tags']);
            $dvd['tags'] = array();
        }
        if (!DisplayIfIsPrivateOrAlways($displaytags)) {
            $dvd['p_tags'] = '';
        }

// Get the supplier info
        $purchaseplace = '';
        $result = $db->sql_query("SELECT * FROM $DVD_SUPPLIER_TABLE WHERE sid='$dvd[purchaseplace]'") or die($db->sql_error());
        $supplier = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        $dvd['purchaseplace'] = '';
        if ($supplier !== false) {
            $dvd['purchaseplace'] = $supplier['suppliername'];
            $supplier['supplierurl'] = preg_replace('/http:\/\//i', '', $supplier['supplierurl']);
            $supbullet = ($supplier['suppliertype'] == "O")? $bullet: '';
            if ($supplier['supplierurl'] != '')
                $purchaseplace = "$supbullet<a target=\"_blank\" href=\"http://$supplier[supplierurl]\">$supplier[suppliername]</a>";
            else
                $purchaseplace = "$supbullet$supplier[suppliername]";
        }

        $dvd['links'] = "window.open('http://www.invelos.com/dvdpro/userinfo/ProfileContributors.aspx?UPC=$dvd[id]','Contributors','toolbar=no,width=702,height=499,resizable=yes,scrollbars=yes,status=yes'); return false;";
        $dvd['links'] = "$bullet<a href=\"javascript:;\" onClick=\"$dvd[links]\">$lang[CONTRIBUTORS]</a><br>\n";

        $tmp = "window.open('http://www.invelos.com/ProfileLinks.aspx?UPC=$dvd[id]','ProfileLinks','toolbar=no,width=1017,height=499,resizable=yes,scrollbars=yes,status=yes'); return false;";
        $dvd['links'] .= "$bullet<a href=\"javascript:;\" onClick=\"$tmp\">$lang[PROFILELINKS]</a>";

// Prepare to add MyLinks to the links display. Make this all temporary so that we can conditionally put an IMDB search in ...
        $lastlinkcat = '';
        $mylinks = '';
        $linkres = $db->sql_query("SELECT * FROM $DVD_LINKS_TABLE WHERE id='$dvd[id]' ORDER BY dborder") or die($db->sql_error());
        while ($linkrow = $db->sql_fetchrow($linkres)) {
            if ($lastlinkcat == '')
                $mylinks .= "<br><br>\n<font size=\"+1\"><b>$lang[MYLINKS]</b></font>";
            if ($lastlinkcat != $linkrow['category']) {
                $lastlinkcat = $linkrow['category'];
                if ($lastlinkcat == 'Official Websites') $z = 'Website.jpg';
                else if ($lastlinkcat == 'Fan Sites') $z = 'Fans.jpg';
                else if ($lastlinkcat == 'Trailers and Clips') $z = 'Trailers.jpg';
                else if ($lastlinkcat == 'Reviews') $z = 'Reviews.jpg';
                else if ($lastlinkcat == 'Ratings') $z = 'Rating.jpg';
                else if ($lastlinkcat == 'General Information') $z = 'Information.jpg';
                else if ($lastlinkcat == 'Games') $z = 'Games.jpg';
                else
                    $z = 'Other.jpg';
                $mylinks .= "<hr />\n<img src=\"gfx/$z\" height=24 width=24 style=\"vertical-align:middle\">&nbsp;<b>" . $lang['LINKS_'.strtoupper(str_replace(' ', '', $linkrow['category']))] . "</b>";
            }
            $tmp = "window.open('$linkrow[url]','$linkrow[description]','toolbar=no,width=1017,height=499,resizable=yes,scrollbars=yes,status=yes'); return false;";
            $mylinks .= "<br>\n<img height=1 width=24 src=\"gfx/transparent.gif\">$bullet<a href=\"$linkrow[url]\" onClick=\"$tmp\">$linkrow[description]</a>";
        }
        $db->sql_freeresult($linkres);

//
// This bit of code allows one to put text like "<IMDB number=1234567>" into the notes field. The number will
// be extracted and used to make the "Link-to-IMDB" feature useful. That part of the string will be removed
// from the notes field so that otherwise blank notes fields will not be displayed.
// The same will be done for strings like: <IMDB> tt1234567 </IMDB> to support the format used in Mithirandir's
// wonderful skin. Now also for <IMDB=tt1234567 />
//
        $IMDBfmt = array(
             '~\s*<\s*IMDB\s+number\s*=\s*["\']?([0-9]+)["\']?\s*/?>\s*~Ui',
             '~\s*<\s*IMDB\s*>([t0-9]*)</\s*IMDB\s*>\s*~Ui',
             '~\s*<\s*IMDB\s*=\s*([t0-9]*)\s*/>\s*~Ui'
        );
        if (($num_matches=preg_match_all($IMDBfmt[0], $dvd['notes'], $matches)) != 0) {
            for ($i=0; $i<$num_matches; $i++)
                $IMDBNum[] = 'tt'.$matches[1][$i];
        }
        if (($num_matches=preg_match_all($IMDBfmt[1], $dvd['notes'], $matches)) != 0) {
            for ($i=0; $i<$num_matches; $i++)
                $IMDBNum[] = $matches[1][$i];
        }
        if (($num_matches=preg_match_all($IMDBfmt[2], $dvd['notes'], $matches)) != 0) {
            for ($i=0; $i<$num_matches; $i++)
                $IMDBNum[] = $matches[1][$i];
        }
        if (isset($matches)) unset($matches);
        $num_matches = count($IMDBNum);
// Technically, we could cull IMDB duplicates, but it seems like a lot of work :)
        if ($num_matches == 0) {
// Only put in the IMDB _search_ if there is no IMDB link in the MyLinks section
            if (stristr($mylinks, '.imdb.') === false)
                $dvd['links'] .= "<br>\n$bullet<a target=\"_blank\" href=\"$lang[IMDBURL]?s=tt&q=" . urlencode($dvd['title']) . "\">$lang[IMDBNAME]</a>";
        }
        else if ($num_matches == 1) {
            $dvd['links'] .= "<br>\n$bullet<a target=\"_blank\" href=\"$lang[IMDBSITE]/title/$IMDBNum[0]/\">$lang[IMDBNAME]</a>";
        }
        else {
            for ($i=0; $i<$num_matches; $i++)
                $dvd['links'] .= "<br>\n$bullet<a target=\"_blank\" href=\"$lang[IMDBSITE]/title/$IMDBNum[$i]/\">$lang[IMDBNAME] (#".($i+1).")</a>";
        }
        unset($IMDBNum);

// Changed Dsig 23Oct12 - Added wikilink url to links to display
                // only do this if there is no wikipedia in the links from dvdprofiler
                if (stristr($mylinks, 'wikipedia') === false) {
                        // first break out the title string .. the convert a spaces to _
                        $searchTitle = $dvd['title'];
                        $searchTitle = preg_replace('/\\s/', '_', $searchTitle);
                        $dvd['links'] .= "<br>\n$bullet<a target=\"_blank\" href=\"$lang[WIKIURL]$searchTitle\">$lang[WIKINAME]</a>";
                }

                $dvd['links'] .= $mylinks;

        $dvd['o_notes'] = preg_replace('|</body>|i', '</ body>', $dvd['notes']);
        if (!$DisplayNotesAsHTML) {
            $dvd['notes'] = nl2br(str_replace('&#039;', '&apos;', htmlspecialchars($dvd['notes'], ENT_QUOTES, 'ISO-8859-1')));
        }

        $dvd['epg'] = $thefilename = '';
        if (preg_match('~<?\s*epg\s*=\s*1\s*(/?>)?~i', $dvd['notes'], $matches) != 0) {
            $dvd['notes'] = str_replace($matches[0], '', $dvd['notes']);
            $HasEPG = true;
        }
        if ($HasEPG) {
            $epg = $dvd['id'];
            if ($epg_RemoveLocale) {
                if (($period=strpos($dvd['id'], '.')) !== false)
                    $epg = substr($dvd['id'], 0, $period);
            }
            $thefilename = $img_epgpath.$epg.'.html';
            if (is_readable($thefilename) && !$UseIframeForEPGs)
                $dvd['epg'] = file_get_contents($thefilename);
        }
        if (preg_match('~<!\[epgfn=([^\]]*)\]>~Ui', $dvd['notes'], $matches) != 0) {
            $thefilename = $img_epgpath.$matches[1];
            if (is_readable($thefilename) && !$UseIframeForEPGs) {
                $dvd['epg'] = file_get_contents($thefilename);
                $dvd['notes'] = str_replace($matches[0], '', $dvd['notes']);
            }
// if the file isn't found, then the text is left in the notes field ...
            unset($matches);
        }
        if ($dvd['epg'] != '') {
            foreach ($pcre_episode_replacements as $val => $repl)
                $dvd['epg'] = preg_replace($repl, $img_episode, $dvd['epg']);
            foreach ($episode_replacements as $val => $repl)
                $dvd['epg'] = str_replace($repl, $img_episode, $dvd['epg']);
        }

        CleanTheHTMLIn($dvd['epg']);
        if ($UseIframeForEPGs && $thefilename != '')
            $dvd['epg'] = $thefilename;

        if (($dvd['notes'] != '') && ($PrivateNotes && !$IsPrivate))
            $dvd['notes'] = '';
        if (!$IsPrivate)
            $dvd['notes'] = preg_replace('~\s*<\s*Private\s*>.*</\s*Private\s*>\s*~i', '', $dvd['notes']);

        CleanTheHTMLIn($dvd['notes']);
//
// The physical location of the DVDs is un-interesting information for others, but it is the
// primary piece of info for family members. This code puts a bold location on the page for
// machines on the "local" lan. I also don't like people knowing what I paid for DVDs, so
// this code blanks the purchase price unless the request is from the local lan.
//
        if ($IsPrivate) {
            $locname = $lang['LOCATION'];
            if ($dvd['loaninfo'] == '')
                $locval = "<b>$locval</b>";
            else
                $locval = "<b>$lang[LOANEDTO] $dvd[loaninfo] - $locval</b>";
//          $dvd['purchprice'] = my_money_format($dvd['purchasepricecurrencyid'], $dvd['paid'])." $dvd[purchasepricecurrencyid]";
            $dvd['purchaseprice'] .= " $dvd[purchasepricecurrencyid]";
        }
        else {
            $locname = $lang['STATUS'];
            if ($dvd['loaninfo'] != '')
                $locval = $lang['LOANEDOUT'];
            else {
                $locval = $lang['NOTLOANEDOUT'];
                foreach ($borrowers as $key => $cidr) {
                    if (CheckSubnet($remote_ip, $cidr)) {
                        $locval = "$lang[BORROWME] <a style='text-decoration:underline' href='BorrowADVD.php?mediaid=$dvd[id]'>$lang[CLICKHERE]</a>";
                        break;
                    }
                }
            }
            $dvd['purchaseprice'] = $lang['HIDDEN'];
        }
        if ($dvd['collectiontype'] != 'owned')
            $locval = CustomTranslation(strtoupper(str_replace(' ', '', $dvd['collectiontype'])), $dvd['collectiontype']);

        if ($AlwaysRemoveFromNotes != '')
            $dvd['notes'] = str_replace($AlwaysRemoveFromNotes, '', $dvd['notes']);
        if ($skinfile != 'internal') {
            include_once('processskin.php');
// if we get here, we really want to do the internal skin
        }

// now remove the IMDB tags from the notes
        $dvd['notes'] = preg_replace($IMDBfmt, '', $dvd['notes']);
        unset($IMDBfmt);

        $dvd['originaltitle'] = fix1252(htmlspecialchars($dvd['originaltitle'], ENT_COMPAT, 'ISO-8859-1'));
        $dvd['sorttitle']     = fix1252(htmlspecialchars($dvd['sorttitle'], ENT_COMPAT, 'ISO-8859-1'));
        $dvd['title']         = fix1252(htmlspecialchars($dvd['title'], ENT_COMPAT, 'ISO-8859-1'));
        $xxx = '';
        if ($dvd['countryoforigin'] != '') {
            CountryToLang($dvd['countryoforigin'], $countryname, $countryloc);
            if ($countryloc != '')
                $xxx .= "<img src=\"gfx/loc$countryloc.gif\" style=\"vertical-align:-30%; margin-bottom:2px\" title=\"$countryname\" alt=\"\"/>";
            $xxx .= "&nbsp;$countryname";
        }
        if ($dvd['countryoforigin2'] != '') {
            if ($xxx != '') $xxx .= ', ';
            CountryToLang($dvd['countryoforigin2'], $countryname, $countryloc);
            if ($countryloc != '')
                $xxx .= "<img src=\"gfx/loc$countryloc.gif\" style=\"vertical-align:-30%; margin-bottom:2px\" title=\"$countryname\" alt=\"\"/>";
            $xxx .= "&nbsp;$countryname";
        }
        if ($dvd['countryoforigin3'] != '') {
            if ($xxx != '') $xxx .= ', ';
            CountryToLang($dvd['countryoforigin3'], $countryname, $countryloc);
            if ($countryloc != '')
                $xxx .= "<img src=\"gfx/loc$countryloc.gif\" style=\"vertical-align:-30%; margin-bottom:2px\" title=\"$countryname\" alt=\"\"/>";
            $xxx .= "&nbsp;$countryname";
        }
        $dvd['countryoforigin'] = $xxx;

        $rdesc = $dvd['rating'];
        if ($dvd['ratingdetails'] != '')
            $rdesc = sprintf($lang['RATINGDESC'], $dvd['rating'], $dvd['ratingdetails']);
        if ($dvd['ratingsystem'] != '')
            $rdesc = "$rdesc($dvd[ratingsystem])";
        $ratinglogo = $rdesc;
        $rfn = "rating_{$locale}_" . str_replace('/', '-', strtolower($dvd['ratingsystem'].'_'.$dvd['rating'])) . '.gif';
        $rfn = GetRatingLogo($locale, $dvd['ratingsystem'], $dvd['rating']);
        if (isset($rfn)) {
            $ratinglogo = '<img src="' . $rfn . "\" height=30 title=\"$rdesc\" alt=\"$lang[RATING]\"/>";
            if ($ExposeRatingDetails)
                $ratinglogo .= "&nbsp;$rdesc";
        }

        $r = trim(str_replace('img ', 'img align=left ', $locks['mediatype']));
        if ($dvd['builtinmediatype'] == MEDIA_TYPE_DVD || $dvd['builtinmediatype'] == MEDIA_TYPE_HDDVD_DVD || $dvd['builtinmediatype'] == MEDIA_TYPE_BLURAY_DVD)
            if ($MediaTypes[MEDIA_TYPE_DVD]['Icon'] != '')
                $r .= '<img alt="" align=left src="' . $MediaTypes[MEDIA_TYPE_DVD]['Icon'] . '">';
        if ($dvd['builtinmediatype'] != MEDIA_TYPE_DVD && $MediaTypes[$dvd['builtinmediatype']]['Icon'] != '')
            $r .= '<img alt="" align=left src="' . $MediaTypes[$dvd['builtinmediatype']]['Icon'] . '">';
        if ($dvd['custommediatype'] != '' && @$MediaTypes[$dvd['custommediatype']]['Icon'] != '')
            $r .= '<img alt="" align=left src="' . $MediaTypes[$dvd['custommediatype']]['Icon'] . '">';
        $J = '';
        if ($dvd['description'] != '')
            $J = "<br><span class=f1sm>$dvd[description]</span>";
        $origtitle = $lang['ORIGINALTITLE'];
        if ($titleorig == 1)
            $origtitle = $lang['TITLE'];
        header('Content-Type: text/html; charset="windows-1252";');
        echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
<title>$dvd[title]</title>
<link rel="stylesheet" type="text/css" href="format.css.php">
<link rel="SHORTCUT ICON" href="$iconPath/favicon.ico">
<link rel="icon" type="image/png" href="$iconPath/favicon-192x192.png" sizes="192x192">
<link rel="apple-touch-icon" sizes="180x180" href="$iconPath/apple-touch-icon-180x180.png">
<script type="text/javascript">
function SwitchOutRows(theitems, obj) {
var item=document.getElementById(theitems);
    if (item.style.display == 'none') {
        item.style.display = '';
        obj.src = 'gfx/minus.gif';
    }
    else {
        item.style.display = 'none';
        obj.src = 'gfx/plus.gif';
    }
}
</script>
</head>
<body>
<table width="100%">
<tr>
<td class=f1 rowspan=3>$r$dvd[title]$locks[title]$J</td>
<td class=nav width="70"><a class=n target="_self" href="#1">$lang[AUDIOFORMAT]</a><br>
<a class=n target="_self" href="#2">$lang[EXTRAS]</a><br>
<a class=n target="_self" href="#3">$lang[CAST]</a></td>
</tr>
</table>

<table class=bgl width="100%">
<tr>
<td valign=top class=bgd width="100%" rowspan=2>
<table width="100%" cellpadding=0>
<tr>
<td>
<table width="100%" cellspacing=0>

EOT;
        if ($dvd['originaltitle'] != '')
            echo<<<EOT
<tr>
<td class=f3>$plwrapf$origtitle:$plwrapb</td>
<td class=f2>$dvd[originaltitle]</td>
</tr>

EOT;

        if ($dvd['countryoforigin'] != '')
            echo<<<EOT
<tr>
<td class=f3>$plwrapf$lang[COUNTRYOFORIGIN]:$plwrapb</td>
<td class=f2>$dvd[countryoforigin]</td>
</tr>

EOT;
        if ($dvd['productionyear'] == '0')
            $dvd['productionyear'] = '&nbsp;';
        echo<<<EOT
<tr>
<td class=f3>$plwrapf$lang[PRODUCTIONYEAR]:$plwrapb</td>
<td class=f2>$dvd[productionyear]$locks[productionyear]</td>
</tr>

<tr>
<td class=f3>$plwrapf$dirname:$plwrapb</td>
<td class=f2>$dvd[p_directors]</td>
</tr>

<tr>
<td class=f3>$plwrapf$lang[RATING]:$plwrapb</td>
<td class=f2>$ratinglogo$locks[rating]</td>
</tr>

<tr>
<td colspan=2 class=bgd></td>
</tr>

<tr>
<td class=f3>$plwrapf$lang[REGIONCODE]:$plwrapb</td>
<td class=f2>$regions$locks[regions]</td>
</tr>

<tr>
<td class=f3>$plwrapf$lang[RUNNINGTIME]:$plwrapb</td>
<td class=f2>$runtime$locks[runningtime]</td>
</tr>

<tr>
<td class=f3>$plwrapf$lang[CASETYPE]:$plwrapb</td>
<td class=f2>$dvd[p_casetype]$locks[casetype]</td>
</tr>

<tr>
<td class=f3>$plwrapf$lang[FORMAT]:$plwrapb</td>
<td class=f2>$dvd[format]$locks[videoformats]</td>
</tr>

<tr>
<td class=f3>$plwrapf$lang[MEDIA]:$plwrapb</td>
<td class=f2>$dvd[media]</td>
</tr>

<tr>
<td class=f3>$plwrapf$lang[RELEASED]:$plwrapb</td>
<td class=f2>$dvd[p_released]$locks[releasedate]</td>
</tr>

<tr>
<td class=f3>$plwrapf$lang[COLLECTIONTYPE]:$plwrapb</td>
<td class=f2>$ctype $cnum</td>
</tr>

<tr>
<td class=f3>$plwrapf$locname:$plwrapb</td>
<td class=f2>$locval</td>
</tr>

<tr>
<td colspan=2 class=bgd></td>
</tr>
EOT;
        $giftfiddle1 = $lang['PURCHASEDATE'];
        $giftfiddle2 = $lang['PURCHASEPRICE'];
        $giftfiddle3 = $dvd['purchaseprice'];
        if ($dvd['gift']) {
            $giftfiddle1 = $lang['RECEIVED'];
            $giftfiddle2 = $lang['GIFTFROM'];
            $giftfiddle3 = '';
            if ($dvd['giftuid'] != 0) {
                $userres = $db->sql_query("SELECT firstname,lastname FROM $DVD_USERS_TABLE WHERE uid=$dvd[giftuid]") or die($db->sql_error());
                $r = $db->sql_fetchrow($userres);
                $db->sql_freeresult($userres);
                $giftfiddle3 = $r['firstname'] . ' ' . HideName($r['lastname']);
            }
        }
        echo <<<EOT
<tr>
<td class=f3>$plwrapf$giftfiddle1:$plwrapb</td>
<td class=f2>$dvd[p_purchasedate]</td>
</tr>

<tr>
<td class=f3>$plwrapf$giftfiddle2:$plwrapb</td>
<td class=f2>$plwrapf$giftfiddle3$plwrapb</td>
</tr>
EOT;

        if (DisplayIfIsPrivateOrAlways($displaySRP)) echo <<<EOT

<tr>
<td class=f3>$plwrapf$lang[SRP]:$plwrapb</td>
<td class=f2>$dvd[srp] $dvd[srpcurrencyid]$locks[srp]</td>
</tr>
EOT;

        if (DisplayIfIsPrivateOrAlways($displayplace)) echo <<<EOT
<tr>
<td class=f3>$plwrapf$lang[PURCHASEPLACE]:$plwrapb</td>
<td class=f2>$purchaseplace</td>
</tr>
EOT;
        if ($SeparateReviews) {
            if ($dvd['p_reviewfilm'] != '' && strpos($reviewgraph, 'F') !== false) echo <<<EOT
<tr>
<td class=f3>$lang[REVIEWFILM]:</td>
<td class=f2>$dvd[p_reviewfilm]</td>
</tr>
EOT;
            if ($dvd['p_reviewvideo'] != '' && strpos($reviewgraph, 'V') !== false) echo <<<EOT
<tr>
<td class=f3>$lang[REVIEWVIDEO]:</td>
<td class=f2>$dvd[p_reviewvideo]</td>
</tr>
EOT;
            if ($dvd['p_reviewaudio'] != '' && strpos($reviewgraph, 'A') !== false) echo <<<EOT

<tr>
<td class=f3>$lang[REVIEWAUDIO]:</td>
<td class=f2>$dvd[p_reviewaudio]</td>
</tr>
EOT;
            if ($dvd['p_reviewextras'] != '' && strpos($reviewgraph, 'E') !== false) echo <<<EOT

<tr>
<td class=f3>$lang[REVIEWEXTRA]:</td>
<td class=f2>$dvd[p_reviewextras]</td>
</tr>
EOT;
        }
        else {
            if ($dvd['reviewfilm'] != 0 ||
                $dvd['reviewvideo'] != 0 ||
                $dvd['reviewaudio'] != 0 ||
                $dvd['reviewextras'] != 0 ||
                $displayreviewsEQ0) {
                echo "<tr><td class=f3>$lang[REVIEWS]:</td><td class=f2>"
                    .'<div title="' . LabelAReviewGraph($dvd, $reviewgraph, false) .'">'
                    . DrawAReviewGraph($dvd, $reviewgraph, 240, 20, '') . '</div></td></tr>';
            }
        }
        echo <<<EOT
<tr>
<td colspan=2 class=bgd></td>
</tr>
</table>
</td>
</tr>
EOT;
        $dispimg = 'gfx/plus.gif';
        $dispstyle = ' style="display:none"';
        if ($expandoverview) {
            $dispimg = 'gfx/minus.gif';
            $dispstyle = '';
        }
        echo<<<EOT
<tr>
<td class=f4><img src="$dispimg" style="vertical-align:middle" onClick="SwitchOutRows('overviewrow', this)"> $lang[OVERVIEW]$locks[overview]</td>
</tr>
<tr>
<td id=overviewrow$dispstyle class=f2>$dvd[p_overview]</td>
</tr>
EOT;
        if ($dvd['notes'] != '') {
            $dispimg = 'gfx/plus.gif';
            $dispstyle = ' style="display:none"';
            if ($expandnotes) {
                $dispimg = 'gfx/minus.gif';
                $dispstyle = '';
            }
            if ($UseIframeForNotes) {
                $ifh = '';
                if ($IframeHeight != 0) $ifh = 'height="' . $IframeHeight . 'px"';
                $dispframe = "<td id=notesrow$dispstyle class=f2><iframe width='100%' $ifh src='$PHP_SELF?action=notes&amp;mediaid=$dvd[id]'></iframe></td>";
            }
            else
                $dispframe = "<td id=notesrow$dispstyle class=f2>$dvd[notes]</td>";
            echo<<<EOT
<tr>
<td class=f4><img src="$dispimg" style="vertical-align:middle" onClick="SwitchOutRows('notesrow', this)"> $lang[NOTES]</td>
</tr>
<tr>
$dispframe
</tr>
EOT;
        }
        if ($dvd['epg'] != '') {
            $dispimg = 'gfx/plus.gif';
            $dispstyle = ' style="display:none"';
            if ($expandepg) {
                $dispimg = 'gfx/minus.gif';
                $dispstyle = '';
            }
            if ($UseIframeForEPGs) {
                $ifh = '';
                if ($IframeHeight != 0) $ifh = 'height="' . $IframeHeight . 'px"';
                $dispframe = "<td id=epgrow$dispstyle class=f2><iframe width='100%' $ifh src='$dvd[epg]'></iframe></td>";
            }
            else
                $dispframe = "<td id=epgrow$dispstyle class=f2>$dvd[epg]</td>";
            echo<<<EOT
<tr>
<td class=f4><img src="$dispimg" style="vertical-align:middle" onClick="SwitchOutRows('epgrow', this)"> $lang[EPG]</td>
</tr>
<tr>
$dispframe
</tr>
EOT;
        }
        echo <<<EOT
</table>
</td>
<td class=bgd valign=top>
<table cellpadding=0 cellspacing=0>
<tr>
<td class=f4 valign=top>
$lang[COVERS]$locks[covers]
</td>
</tr>
<tr>
<td class=bgd valign=top>
$dvd[thumbs]
</td>
</tr>
</table>
</td>
</tr>

<tr>
<td class=bgd valign=top style="vertical-align:bottom">
<table width="100%">
<tr>
<td class=f4>$lang[MISCLINKS]</td>
</tr>

<tr>
<td class=f2>
$dvd[links]
</td>
</tr>
</table>
</td>
</tr>

<tr>
<td class=bgd colspan=2>
<table width="100%" cellpadding=0 cellspacing=0>
<tr>
<td width="50%" valign=top>
<table width="100%">
<tr>
<td class=f4>$lang[GENRES]$locks[genres]</td>
</tr>

<tr>
<td class=f2>$dvd[p_genres]</td>
</tr>
</table>
</td>
<td width="50%" valign=top>
<table width="100%">
<tr>
<td class=f4>$lang[STUDIOS]$locks[studios]</td>
</tr>

<tr>
<td class=f2>$dvd[p_studios]</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>

<tr>
<td class=bgd colspan=2><a name=1></a>
<table width="100%" cellpadding=0 cellspacing=0>
<tr>
<td width="50%" valign=top>
<table width="100%">
<tr>
<td class=f4>$lang[AUDIOFORMAT]$locks[audio]</td>
</tr>

<tr>
<td class=f2>
$dvd[p_audio]
</td>
</tr>
</table>
</td>
<td width="50%" valign=top>
<table width="100%">
<tr>
<td class=f4>$lang[SUBTITLES]$locks[subtitles]</td>
</tr>

<tr>
<td class=f2>$dvd[p_subtitles]</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
EOT;
        if (DisplayIfIsPrivateOrAlways($displaytags) && ($dvd['p_tags'] != '')) {
            echo <<<EOT
<tr>
<td class=bgd colspan=2><a name=2></a>
<table width="100%">
<tr>
<td class=f4>$lang[TAGS]</td>
</tr>
<tr>
<td class=f2>$dvd[p_tags]</td>
</tr>
</table>
</td>
</tr>
EOT;
}
        if ($dvd['p_discs'] != '') {
            echo <<<EOT
<tr>
<td class=bgd colspan=2><a name=2></a>
<table width="100%">
<tr>
<td class=f4>$lang[DISCS]$locks[discinfo]</td>
</tr>

<tr>
<td class=bgd colspan=2><a name=2></a>
<table width="100%">
<tr>
<td class=f4>$lang[DISCNO]</td>
<td class=f4>$lang[DESCRIPTION]</td>
<td class=f4 align=center title="$lang[SIDEAID]">$lang[SIDEA]</td>
<td class=f4 align=center title="$lang[SIDEBID]">$lang[SIDEB]</td>
<td class=f4 align=center title="$lang[DUALSIDED]">$lang[DS]</td>
<td class=f4 align=center title="$lang[DUALLAYERED] A">$lang[DL] A</td>
<td class=f4 align=center title="$lang[DUALLAYERED] B">$lang[DL] B</td>

EOT;
            if ($IsPrivate)
                echo <<<EOT
<td class=f4>$lang[LOCATION]</td>
<td class=f4>$lang[SLOT]</td>

EOT;
            echo <<<EOT
</tr>
$dvd[p_discs]
</table>
</td>
</tr>
</table>
</tr>
EOT;
        }

        if ($dvd['p_events'] != '') {
            echo <<<EOT
<tr>
<td class=bgd colspan=2><a name=2></a>
<table width="100%">
<tr>
<td class=f4>$lang[EVENTS]</td>
</tr>

<tr>
<td class=bgd colspan=2><a name=2></a>
<table width="100%">
<tr>
<td class=f4>$lang[USERNAME]</td>
<td class=f4 align=center>$lang[PHONE]</td>
<td class=f4 align=center>$lang[EMAIL]</td>
<td class=f4 align=center>$lang[EVENT]</td>
<td class=f4 align=center>$lang[TIMESTAMP]</td>
<td class=f4 align=center>$lang[EVENTNOTE]</td>

</tr>
$dvd[p_events]
</table>
</td>
</tr>
</table>
</tr>
EOT;
        }

        echo <<<EOT
<tr>
<td class=bgd colspan=2><a name=2></a>
<table width="100%">
<tr>
<td class=f4>$lang[EXTRAS]$locks[features]</td>
</tr>
<tr>
<td class=f2>
$dvd[p_extras]
</td>
</tr>
</table>
</td>
</tr>

EOT;
        if (strlen($dvd['eastereggs']) > 0) {
            $dvd['eastereggs'] = nl2br($dvd['eastereggs']);
            echo <<<EOT
<tr>
<td class=bgd colspan=2>
<table width="100%">
<tr>
<td class=f4>$lang[EASTEREGGS]$locks[eastereggs]</td>
</tr>

<tr>
<td class=f2>
$dvd[eastereggs]
</td>
</tr>
</table>
</td>
</tr>

EOT;
        }
        if (strlen($dvd['p_credits']) > 0) {
            $dispimg = 'gfx/plus.gif';
            $dispstyle = ' style="display:none"';
            if ($expandcrew) {
                $dispimg = 'gfx/minus.gif';
                $dispstyle = '';
            }
            echo <<<EOT
<tr>
<td class=bgd colspan=2>
<table width="100%">
<tr>
<td class=f4><img src="$dispimg" style="vertical-align:middle" onClick="SwitchOutRows('crewrow', this)"> $lang[CREDITHEAD]$locks[crew]</td>
</tr>

<tr>
<td id=crewrow$dispstyle class=f2>
<table cellpadding=1 cellspacing=1 border=0 class=f2><tbody>
$dvd[p_credits]
</tbody></table>
</td>
</tr>
</table>
</td>
</tr>

EOT;
        }

        $dispimg = 'gfx/plus.gif';
        $dispstyle = ' style="display:none"';
        if ($expandcast) {
            $dispimg = 'gfx/minus.gif';
            $dispstyle = '';
        }
        echo <<<EOT
<tr>
<td class=bgd colspan=2><a name=3></a>
<table width="100%">
<tr>
<td class=f4><img src="$dispimg" style="vertical-align:middle" onClick="SwitchOutRows('castrow', this)"> $lang[CAST]$locks[cast]</td>
</tr>

<tr>
<td id=castrow$dispstyle class=f2>
<table cellpadding=1 cellspacing=1 border=0 class=f2><tbody>
$dvd[p_actors]
</tbody></table>
</td>
</tr>
</table>
</td>
</tr>
</table>
<script type="text/javascript" src="wz_tooltip.js"></script>
$endbody
</html>

EOT;

    }
    else {
        header('Content-Type: text/html; charset="windows-1252";');
        echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
<title>$lang[EMPTYLIST]</title>
<link rel="stylesheet" type="text/css" href="format.css.php">
<link rel="SHORTCUT ICON" href="$iconPath/favicon.ico">
<link rel="icon" type="image/png" href="$iconPath/favicon-192x192.png" sizes="192x192">
<link rel="apple-touch-icon" sizes="180x180" href="$iconPath/apple-touch-icon-180x180.png">
</head>
<body>
<table width="100%">
<tr>
<td class=f1 rowspan=3>$lang[EMPTYLIST]<br><font size=2>0 $lang[ITEMS]</font></td>
<td class=nav width=70>$lang[NOMEDIA]</td>
</tr>
</table>
$endbody
</html>

EOT;
    }
    DebugSQL($db, "$action: $mediaid");
    exit;
}
