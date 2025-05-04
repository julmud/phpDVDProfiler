<?php

function MakeAPercentage($value, $total, $decimals=0) {
    if ($total == 0)
        return(0);
    $frac = $value*100/$total;
    $j = log10($frac);
    $round = ($j>0)? $decimals: -1*floor($j);
    $frac = round($frac, $round);
    return($frac);
}

    SendNoCacheHeaders('Content-Type: text/html; charset="windows-1252";');

    if (!DisplayIfIsPrivateOrAlways($handleadult)) {
        $noadult = "AND isadulttitle=0";
        $ADULT = "NoAdult";
    }
    else {
        $noadult = "";
        $ADULT = "Adult";
    }

    $hdr = '';
    $onres = '';

    $hdr .= <<<EOT
<script type="text/javascript">
var RadioButtons = new Array();

function RegisterRadioButtons() {
var whichtable=arguments[0];

    RadioButtons[whichtable] = new Array();
    for (var i=1; i<arguments.length; i++)
        RadioButtons[whichtable][arguments[i]] = document.getElementById(arguments[i]);
}
function RadioAlternate(whichtable, whichdata){
    RadioButtons[whichtable][whichdata].style.display="";
    for (var i in RadioButtons[whichtable]) {
        if (i != whichdata) {
            RadioButtons[whichtable][i].style.display="none";
        }
    }
}
</script>

EOT;
    if ($usejpgraph) {
        $hdr .= <<<EOT
<script type="text/javascript">
var ResizeTimer;
function GetWidth() {
    if (self.innerWidth)
        frameWidth = self.innerWidth;
    else if (document.documentElement && document.documentElement.clientWidth)
        frameWidth = document.documentElement.clientWidth;
    else if (document.body)
        frameWidth = document.body.clientWidth;
    return(frameWidth);
}
function OnRes() {
    clearInterval(ResizeTimer);
    ResizeTimer = setInterval('UpdateImage()', 1000);
}
function UpdateImage() {
    clearInterval(ResizeTimer);
    j = GetWidth()-40;
    k = document.getElementsByName('jpgraph');
    for (i=0; i<k.length; i++)
        k[i].src = k[i].src.replace(/graphx=(\d+)/, 'graphx='+j);
}

function FiddleProd(low,high) {
var temp, obj=document.getElementById("byprodyeargraph");

    obj.src = obj.src.replace(/low=(\d{4})&high=(\d{4})/, 'low='+low+'&high='+high);
}
function FiddleMonth(str) {
var temp, obj=document.getElementById("bymonthgraph");

    obj.src = obj.src.replace(/year=(all|last|year|\d{4})/, 'year='+str);
}

</script>
EOT;
        $onres = 'onResize="OnRes()"';
    }

// ***************** Page Heading
    echo<<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
<title>$lang[STATISTICS]</title>
<link rel="stylesheet" type="text/css" href="format.css.php">$hdr
</head>
<body class=f6 $onres>
<center><table width="100%" class=f1><tr><td>$lang[STATISTICS]</td></tr></table></center>
<BR>
EOT;
    $t0 = microtime_float(); $numtimings = 0;

    $centertable = "<table class=f2np cellspacing=0 cellpadding=0><tr><td width=\"50%%\" align=right class=f2np>%d&nbsp;(</td>"
            ."<td align=left class=f2np>%s%%)</td></tr></table>";
// ***************** General Statistics
    echo <<<EOT
<center><table width="80%" class=f1><tr><td>$lang[GENSTATS]</td></tr></table></center>
<center><table width="75%" class=bgl>
EOT;

// Total number of profiles
    $sql = "SELECT COUNT(*) AS total FROM $DVD_TABLE WHERE collectiontype='owned' $noadult";
    $result = $db->sql_query($sql) or die($db->sql_error());
    $dvd = $db->sql_fetchrow($result);
    $total = $dvd['total'];
    echo "<tr><td class=f3np>$lang[TOTPROFS]</td><td class=f2np>$total</td><td class=f2np></td></tr>\n";
    $db->sql_freeresult($result);

// Total running time
    $sql = "SELECT SUM(runningtime) AS total, COUNT(runningtime) AS numfound  FROM $DVD_TABLE WHERE collectiontype='owned' $noadult $runtimespecialcondition";
    $result = $db->sql_query($sql) or die($db->sql_error());
    $dvd = $db->sql_fetchrow($result);
    $totaltime = $dvd['total'];
    $totalnum = $dvd['numfound'];
    if ($totalnum == 0)
        $totalnum = 1;
    printf("<tr><td class=f3np>$lang[TOTRUNTIME]</td>"
        ."<td class=f2np>%s $lang[LITTLEMINUTES] (%s:%02d)</td>"
        ."<td class=f2np>$lang[AVERAGING]</td></tr>\n",
        number_format($totaltime, 0, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']), number_format(floor($totaltime/60), 0, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']),
        $totaltime%60, round($totaltime/$totalnum));
    $db->sql_freeresult($result);

    if ($IsPrivate) {
// Total purchase price (with different currencies possible)
        $sql = "SELECT SUM(paid) AS paid,COUNT(*) AS num,purchasepricecurrencyid AS ppci FROM $DVD_TABLE "
            ."WHERE collectiontype='owned' $currencyspecialcondition $noadult GROUP BY ppci";
        $result = $db->sql_query($sql) or die($db->sql_error());
        while ($dvd = $db->sql_fetchrow($result)) {
            $paid = $dvd['paid'];
            printf("<tr><td class=f3np>$lang[TOTALPAID]</td>"
                ."<td class=f2np>$lang[THETOTAL]</td>"
                ."<td class=f2np>$lang[AVGPERPROF]</td></tr>\n",
                $dvd['ppci'],
                my_money_format($dvd['ppci'], $paid), $dvd['ppci'], $dvd['num'],
                my_money_format($dvd['ppci'], round($paid/$dvd['num'], money_digits($dvd['ppci']))), $dvd['ppci']);
        }
        $db->sql_freeresult($result);
    }

    if (DisplayIfIsPrivateOrAlways($displaySRP)) {
// Total MSRP (with different currencies possible)
        $sql = "SELECT SUM(srpdec) AS srp,COUNT(*) AS num,srpcurrencyid AS sci FROM $DVD_TABLE "
            ."WHERE collectiontype='owned' $srpspecialcondition $noadult GROUP BY sci";
        $result = $db->sql_query($sql) or die($db->sql_error());
        while ($dvd = $db->sql_fetchrow($result)) {
            $srp = $dvd['srp'];
            printf("<tr><td class=f3np>$lang[TOTALSRP]</td>"
                ."<td class=f2np>$lang[THETOTAL]</td>"
                ."<td class=f2np>$lang[AVGPERPROF]</td></tr>\n",
                $dvd['sci'],
                my_money_format($dvd['sci'], $srp), $dvd['sci'], $dvd['num'],
                my_money_format($dvd['sci'], round($srp/$dvd['num'], money_digits($dvd['sci']))), $dvd['sci']);
        }
        $db->sql_freeresult($result);
    }
    echo "</table></center><BR>\n";

    if ($usejpgraph) {
        if (DisplayIfIsPrivateOrAlways($displaymonth)) {
            if (!isset($monthspecialprecondition))
                $monthspecialprecondition = '';

            $years = array();
            $numyears = 3;
            $years[] = $lang['GRAPHS']['LAST12'];
            $years[] = $lang['GRAPHS']['ALL'];
            $years[] = $lang['GRAPHS']['YEARS'];

            $sql = "SELECT date_format(from_unixtime(purchasedate), '%Y') AS year, COUNT(title) AS count FROM $DVD_TABLE $monthspecialprecondition WHERE collectiontype='owned' $monthspecialcondition GROUP BY year DESC";
            $result = $db->sql_query($sql) or die($db->sql_error());
            while ($dvd = $db->sql_fetchrow($result)) {
                if ($dvd['year'] != '') {
                    $numyears++;
                    $years[] = $dvd['year'];
                }
            }
            $db->sql_freeresult($result);
            $odd = $numyears % 2;
            $spacing = 1;
            if ($odd)
                $spacing = 2;

            $cols = intval(($numyears+1)/2);
            $width = intval(100/$cols);
            $width2 = $width;
            if ($odd)
                $width2 = $width * 2;
            $cols = $cols - 2;
            echo<<<EOT
<table border width="100%" cellspacing=2 cellpadding=0>
<tr>
<td width="$width%" align=center><a style="cursor:pointer" onClick="FiddleMonth('last')">$years[0]</a></td>
<td width="$width%" align=center><a style="cursor:pointer" onClick="FiddleMonth('year')">$years[2]</a></td>
EOT;
            $cols = intval(($numyears+1)/2) - 2;
            for ($act=1; $act<=$cols; $act++) {
                $tmp=$act+2;
                echo "<td width='$width%' align=center><a style=\"cursor:pointer\" onClick=\"FiddleMonth('$years[$tmp]')\">$years[$tmp]</a></td>\n";
            }

            echo<<<EOT
</tr>
<tr>
<td width="$width2%" colspan=$spacing align=center><a style="cursor:pointer" onClick="FiddleMonth('all')">$years[1]</a></td>
EOT;
            for ($act=$cols; $act<=$numyears-4; $act++) {
                $tmp=$act+3;
                echo "<td width='$width%' align=center><a style=\"cursor:pointer\" onClick=\"FiddleMonth('$years[$tmp]')\">$years[$tmp]</a></td>\n";
            }

            echo<<<EOT
</tr>
</table>
<center>
<script type="text/javascript">
j = GetWidth()-40;
document.write('<img style="border-width:0px" id=bymonthgraph name=jpgraph src="gr_bymonth.php?year=last&graphx='+j+'&graphy=auto">');
</script>
</center><br>
EOT;

        }
        if (DisplayIfIsPrivateOrAlways($displaycurrency)) {
            $temp = array();
            $available = array();
            $sql = "SELECT DISTINCT(purchasepricecurrencyid) FROM $DVD_TABLE "
                ."WHERE collectiontype='owned' $noadult ORDER BY purchasepricecurrencyid";
            $result = $db->sql_query($sql) or die($db->sql_error());
            while ($dvd = $db->sql_fetchrow($result))
                if ($dvd['purchasepricecurrencyid'] != '')
                    $temp[] = $dvd['purchasepricecurrencyid'];
            $db->sql_freeresult($result);
            foreach ($currencypriority as $wantkey => $wantvalue) {
                foreach ($temp as $havekey =>$havevalue) {
                    if ($havevalue == $wantvalue) {
                        $available[] = $wantvalue;
                        unset($temp[$havekey]);
                        break;
                    }
                }
            }
            if (!$onlycurrencypriority)
                foreach ($temp as $havekey =>$havevalue)
                    $available[] = $havevalue;
            unset($temp);
            if (count($available) > 0) {
                if (count($available) > 1) {
                    $curlist = '';
                    foreach ($available as $key => $avail)
                        $curlist .= "\"$avail\", ";
                    $curlist = substr($curlist, 0, strlen($curlist)-2);
                    echo <<<EOT
<center>
<script type="text/javascript">
j = GetWidth()-40;

function SwitchCurrency(obj) {
var currencies=new Array($curlist);
var curr, next;
    curr = obj.src.replace(/.*&next=([^&]*)/, '$1');
    for (var i=0; i<currencies.length; i++) {
        if (currencies[i] == curr) {
            if (i != currencies.length-1)
                next = currencies[i+1];
            else
                next = currencies[0];
        }
    }
    obj.src = obj.src.replace(/&currency=.*&next=.*/, '&currency='+curr+'&next='+next);
}

document.write('<a style="cursor:pointer" title="{$lang['GRAPHS']['CLICK']}"><img style="border-width:0px" onClick="SwitchCurrency(this)" name=jpgraph src="gr_bycurrency.php?graphx='+j+'&graphy=auto&currency={$available[0]}&next={$available[1]}"></a>');
</script>
</center><br>

EOT;
                }
                else {
                    echo<<<EOT
<center>
<script type="text/javascript">
j = GetWidth()-40;
document.write('<img style="border-width:0px" name=jpgraph src="gr_bycurrency.php?graphx='+j+'&graphy=auto&currency={$available[0]}">');
</script>
</center><br>

EOT;
                }
            }
        }
        if (DisplayIfIsPrivateOrAlways($displayplace)) {
            echo<<<EOT
<center>
<script type="text/javascript">
j = GetWidth()-40;
document.write('<img name=jpgraph src="gr_byplace.php?graphx='+j+'&graphy=auto">');

</script>
</center><br>
EOT;
        }
        $numyears = 1;
        $allyears = $lang['GRAPHS']['PRODALL'];

        $sql = "SELECT MIN(productionyear) AS minyear, MAX(productionyear) AS maxyear FROM $DVD_TABLE WHERE collectiontype='owned' $productionyearspecialcondition";
        $result = $db->sql_query($sql) or die($db->sql_error());
        $dvd = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        $maxyear = $dvd['maxyear'];
        $minyear = 10*(int)($dvd['minyear']/10);
        $numyears += 1+(int)(($maxyear-$minyear)/10);

        $cols = intval(($numyears+1)/2);
        $width = intval(100/$cols);
        echo<<<EOT
<table border width="100%" cellspacing=2 cellpadding=0>
<tr>
<td width="$width%" align=center><a style="cursor:pointer" onClick="FiddleProd('$minyear','$maxyear')">$allyears</a></td>

EOT;
        for ($i=1,$tmp=$minyear; $i<$numyears; $i++,$tmp+=10) {
            $tmp1 = $tmp + 9;
            if ($i == $cols) echo "</tr><tr>\n";
            echo "<td width='$width%' align=center><a style=\"cursor:pointer\" onClick=\"FiddleProd('$tmp','$tmp1')\">$tmp</a></td>\n";
        }

        echo<<<EOT
</tr></table>
<center>
<script type="text/javascript">
j = GetWidth()-40;
document.write('<img id=byprodyeargraph name=jpgraph src="gr_byproduction.php?low=$minyear&high=$maxyear&graphx='+j+'&graphy=auto">');
</script>
</center><br>

EOT;
    }


    $ProfileName[$numtimings] = 'GeneralStats'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// ***************** Region Statistics
    echo "<center><table width=\"75%\" class=f1><tr><td>$lang[NUMPROFREG]</td></tr></table></center><BR>\n";
    echo "<center><table width=\"50%\" class=bgl>\n";

// Profile totals organised by on-disc regions (ie. number of discs with regions 1,3,4)
    echo "<tr><th align=left class=f3np style=\"color: black;\">$lang[REGIONSONDVD]</th>"
        ."<th class=f2np>$lang[NUMPROF]</th></tr>\n";
    $sql = "SELECT region,COUNT(*) AS total FROM $DVD_TABLE WHERE collectiontype='owned' $noadult GROUP BY region ORDER BY region";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        $regioncommas = AddCommas($dvd['region']);
        if ($regioncommas == '0') $regioncommas = $lang['ALLREGIONSDVD'];
        if ($regioncommas == '@') $regioncommas = $lang['ALLREGIONSBLURAY'];
        $str = "<a target=\"_blank\" href=\"javascript:;\" onClick=\"window.open("
            ."'popup.php?acttype=REGION&amp;fullname=$dvd[region]','Actors',$ActorWindowSettings); "
            ."return false;\">$regioncommas</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td align=center class=f2np>$centertable</td></tr>\n",
            $str, $dvd['total'], MakeAPercentage($dvd['total'], $total));
    }
    $db->sql_freeresult($result);
    echo "</table></center><BR>\n";

    $ProfileName[$numtimings] = 'RegionStats1'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// Profile totals per region (ie. number of discs playable in region 2)
    echo "<center><table width=\"50%\" class=bgl>\n";
    echo "<tr><th align=left class=f3np style=\"color:black;\">$lang[REGION]</th>"
        ."<th class=f2np>$lang[NUMPROF]</th></tr>\n";
    $sql = "SELECT namestring1 AS region,counts AS total from $DVD_STATS_TABLE "
        ."WHERE stattype='Region$ADULT'";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        if ($dvd['total'] != 0) {
            $disp = $lang['REGION'] . " " . $dvd['region'];
            if ($dvd['region'] == '0') $disp = $lang['ALLREGIONSDVD'];
            if ($dvd['region'] == '@') $disp = $lang['ALLREGIONSBLURAY'];
            printf("<tr><td class=f3np>%s</td>"
                ."<td align=center class=f2np>$centertable</td></tr>\n",
                $disp, $dvd['total'], MakeAPercentage($dvd['total'], $total));
        }
    }
    $db->sql_freeresult($result);
    echo "</table></center><BR>\n";

    if ($usejpgraph) {
        echo<<<EOT
<center>
<script type="text/javascript">
j = GetWidth()-40;
document.write('<img name=jpgraph src="gr_bylocality.php?graphx='+j+'&graphy=auto">');
</script>
</center><br>
<center>
<script type="text/javascript">
j = GetWidth()-40;
document.write('<img name=jpgraph src="gr_byorigin.php?graphx='+j+'&graphy=auto">');
</script>
</center><br>

EOT;
    }
    $ProfileName[$numtimings] = 'RegionStats2'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// ***************** Genre Statistics
    echo "<center><table width=\"75%\" class=f1><tr><td>$lang[NUMPROFGEN]</td></tr></table></center><BR>\n";

// Total number of profiles per primary (ie. first) genre
    $genre = array();
    $sql = "SELECT primegenre,COUNT(*) AS total FROM $DVD_TABLE "
        ."WHERE collectiontype='owned' $noadult GROUP BY primegenre "
        ."ORDER BY total DESC";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        $genre[$dvd['primegenre']]['primetotal'] = $dvd['total'];
        $genre[$dvd['primegenre']]['primefrac'] = MakeAPercentage($dvd['total'], $total, 1);
        $genre[$dvd['primegenre']]['alltotal'] = 0;
        $genre[$dvd['primegenre']]['allfrac'] = 0;
    }
    $db->sql_freeresult($result);
    $ProfileName[$numtimings] = 'GenreStats1'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// Total number of profiles containing a particular genre (ie. not just first genre)
    $sql = "SELECT genre,COUNT(genre) AS total FROM $DVD_TABLE d,$DVD_GENRES_TABLE g "
        ."WHERE d.id=g.id AND collectiontype='owned' $noadult $genrespecialcondition "
        ."GROUP BY genre";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
// if the determinant for adult is more than containing the adult genre, then this is incorrect
        if (($dvd['genre'] == 'Adult') && !DisplayIfIsPrivateOrAlways($handleadult))
            continue;
        if (!isset($genre[$dvd['genre']]['primetotal'])) {
            $genre[$dvd['genre']]['primetotal'] = 0;
            $genre[$dvd['genre']]['primefrac'] = 0;
        }
        $genre[$dvd['genre']]['alltotal'] = $dvd['total'];
        $genre[$dvd['genre']]['allfrac'] = MakeAPercentage($dvd['total'], $total, 1);
    }
    $db->sql_freeresult($result);
    $ProfileName[$numtimings] = 'GenreStats2'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

    echo "<center><table width=\"50%\" class=bgl>\n";
    echo "<tr><th align=left class=f3np style=\"color: black;\">$lang[GENRE]</th>"
        ."<th class=f2np>$lang[PRIMGEN]</th>"
        ."<th class=f2np>$lang[ANYGEN]</th></tr>\n";
    foreach ($genre as $key => $value) {
        printf("<tr><td class=f3np>%s</td>"
            ."<td align=center class=f2np>$centertable</td>"
            ."<td align=center class=f2np>$centertable</td></tr>\n",
            GenreTranslation($key),
            $genre[$key]['primetotal'], $genre[$key]['primefrac'],
            $genre[$key]['alltotal'], $genre[$key]['allfrac']);
    }
    echo "</table></center><BR>\n";
    if ($usejpgraph) {
        echo<<<EOT
<center>
<script type="text/javascript">
j = GetWidth()-40;
document.write('<img name=jpgraph src="gr_byprimarygenre.php?graphx='+j+'&graphy=auto">');
</script>
</center><br>
<center>
<script type="text/javascript">
j = GetWidth()-40;
document.write('<img name=jpgraph src="gr_bygenre.php?graphx='+j+'&graphy=auto">');
</script>
</center><br>

EOT;
    }

    $ProfileName[$numtimings] = 'GenreStats3'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// ***************** Aspect Ratio Statistics
    echo "<center><table width=\"75%\" class=f1><tr><td>$lang[NUMPROFASPECT]</td></tr></table></center><BR>\n";
    echo "<center><table width=\"50%\" class=bgl>\n";
    echo "<tr><th class=f3np style=\"color: black;\">$lang[ASPECTRATIO]</th>"
        ."<th class=f2np>$lang[NUMPROF]</th></tr>\n";

// Total number of profiles per specific Aspect Ratio
    $sql = "SELECT formataspectratio AS fma,COUNT(*) AS total FROM $DVD_TABLE "
        ."WHERE collectiontype='owned' $noadult GROUP BY fma "
        ."ORDER BY total DESC,fma";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        printf("<tr><td align=center class=f3np>%s:1</td>"
            ."<td align=center class=f2np>$centertable</td></tr>\n",
            $dvd['fma'], $dvd['total'], MakeAPercentage($dvd['total'], $total));
    }
    $db->sql_freeresult($result);
    echo "</table></center><BR>\n";
    $ProfileName[$numtimings] = 'AspectStats'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// ***************** Audio Statistics
    echo "<center><table width=\"75%\" class=f1><tr><td>$lang[NUMPROFAUD]</td></tr></table></center><BR>\n";
    echo "<center><table width=\"50%\" class=bgl>\n";

// Total number of THX DVDs
    $sql = "SELECT COUNT(distinct(id)) AS total FROM $DVD_TABLE "
        ."WHERE featurethxcertified=1 AND collectiontype='owned' $noadult";
    $result = $db->sql_query($sql) or die($db->sql_error());
    $dvd = $db->sql_fetchrow($result);
    printf("<tr><td class=f3np>%s</td>"
        ."<td align=center class=f2np>$centertable</td></tr>\n",
        $lang['THXCERTIFIED'], $dvd['total'], MakeAPercentage($dvd['total'], $total));
    $db->sql_freeresult($result);

// Total number of DTS DVDs
    $sql = "SELECT COUNT(distinct(d.id)) AS total FROM $DVD_TABLE d, $DVD_AUDIO_TABLE a "
        ."WHERE audioformat LIKE 'DTS%' AND d.id=a.id AND collectiontype='owned' $noadult";
    $result = $db->sql_query($sql) or die($db->sql_error());
    $dvd = $db->sql_fetchrow($result);
    printf("<tr><td class=f3np>%s</td>"
        ."<td align=center class=f2np>$centertable</td></tr>\n",
        $lang['AUDIO']['DTS'], $dvd['total'], MakeAPercentage($dvd['total'], $total));
    $db->sql_freeresult($result);

// Total number of DVDs with 5.1 audio channels
    $sql = "SELECT COUNT(distinct(d.id)) AS total FROM $DVD_TABLE d, $DVD_AUDIO_TABLE a "
        ."WHERE audiochannels LIKE '%5.1%' AND d.id=a.id AND collectiontype='owned' $noadult";
    $result = $db->sql_query($sql) or die($db->sql_error());
    $dvd = $db->sql_fetchrow($result);
    printf("<tr><td class=f3np>%s</td>"
        ."<td align=center class=f2np>$centertable</td></tr>\n",
        $lang['5.1CHANNEL'], $dvd['total'], MakeAPercentage($dvd['total'], $total));
    $db->sql_freeresult($result);

// Total number of DVDs with 6.1 audio channels
    $sql = "SELECT COUNT(distinct(d.id)) AS total FROM $DVD_TABLE d, $DVD_AUDIO_TABLE a "
        ."WHERE audiochannels LIKE '%6.1%' AND d.id=a.id AND collectiontype='owned' $noadult";
    $result = $db->sql_query($sql) or die($db->sql_error());
    $dvd = $db->sql_fetchrow($result);
    printf("<tr><td class=f3np>%s</td>"
        ."<td align=center class=f2np>$centertable</td></tr>\n",
        $lang['6.1CHANNEL'], $dvd['total'], MakeAPercentage($dvd['total'], $total));
    $db->sql_freeresult($result);

// Total number of DVDs with 7.1 audio channels
    $sql = "SELECT COUNT(distinct(d.id)) AS total FROM $DVD_TABLE d, $DVD_AUDIO_TABLE a "
        ."WHERE audiochannels LIKE '%7.1%' AND d.id=a.id AND collectiontype='owned' $noadult";
    $result = $db->sql_query($sql) or die($db->sql_error());
    $dvd = $db->sql_fetchrow($result);
    printf("<tr><td class=f3np>%s</td>"
        ."<td align=center class=f2np>$centertable</td></tr>\n",
        $lang['7.1CHANNEL'], $dvd['total'], MakeAPercentage($dvd['total'], $total));
    $db->sql_freeresult($result);

// Total number of DVDs in Dolby Digital
    $sql = "SELECT COUNT(distinct(d.id)) AS total FROM $DVD_TABLE d, $DVD_AUDIO_TABLE a "
        ."WHERE audioformat LIKE 'Dolby Digital%' AND d.id=a.id AND collectiontype='owned' $noadult";
    $result = $db->sql_query($sql) or die($db->sql_error());
    $dvd = $db->sql_fetchrow($result);
    printf("<tr><td class=f3np>%s</td>"
        ."<td align=center class=f2np>$centertable</td></tr>\n",
        $lang['AUDIO']['DD'], $dvd['total'], MakeAPercentage($dvd['total'], $total));
    $db->sql_freeresult($result);

    echo "</table></center><BR>\n";
    $ProfileName[$numtimings] = 'AudioStats'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// Audio FORMAT/COMPRESSION/CHANNELS SECTION

    echo "<center><table width=\"75%\" class=f1><tr><td>$lang[NUMPROFAUDFORM]</td></tr></table></center><BR>\n";
    echo "<center><table width=\"50%\" class=bgl>\n";

// Total number of Audio tracks
    $sql = "SELECT COUNT(*) AS totalaudio FROM $DVD_AUDIO_TABLE a, $DVD_TABLE d "
        ."WHERE d.id=a.id AND collectiontype='owned' $noadult";
    $result = $db->sql_query($sql) or die($db->sql_error());
    $dvd = $db->sql_fetchrow($result);
    $totalaudio = $dvd['totalaudio'];
    $db->sql_freeresult($result);

// Number of profiles per Audio Format
    echo "<tr><th align=left class=f3np style=\"color:black;\">$lang[AUDIOFORM]</th><th class=f2np>$lang[NUMTRACK]</th></tr>\n";
    $sql = "SELECT namestring1 AS aform,counts AS total FROM $DVD_STATS_TABLE "
        ."WHERE stattype='AudioFormat$ADULT'";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        printf("<tr><td class=f3np>%s</td>"
            ."<td align=center class=f2np>$centertable</td></tr>\n",
            $aformat_name[$dvd['aform']],
            $dvd['total'], MakeAPercentage($dvd['total'], $totalaudio));
    }
    $db->sql_freeresult($result);
    echo "</table></center><BR>\n";
    $ProfileName[$numtimings] = 'FormatStats'; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// ***************** Running Time Statistics
    echo "<center><table width=\"75%\" class=f1><tr><td>$lang[TOPTENRUNTIMES]</td></tr>";
    echo "<tr><td class=f1sm><form name=\"runtime\">";
    echo "<center><table class=f1sm>";
    echo "<tr><td align=right>$lang[LONGEST]<input type=radio name=1 onClick=\"RadioAlternate('runtime', 'long');\" checked></td>";
    echo "<td align=left><input type=radio name=1 onClick=\"RadioAlternate('runtime', 'longnotv');\">$lang[LONGESTNOTV]</td></tr>";
    echo "<tr><td align=right>$lang[SHORTEST]<input type=radio name=1 onClick=\"RadioAlternate('runtime', 'short');\"></td>";
    echo "<td align=left><input type=radio name=1 onClick=\"RadioAlternate('runtime', 'shortnotv');\">$lang[SHORTESTNOTV]</td></tr>";
    echo "</table></center></form></td></tr></table></center><br>\n";

    echo "<center>";
// $TopX profiles with longest running time
    echo "<table width=\"50%\" class=bgl id=\"long\">\n";
    $sql = "SELECT namestring1 AS title,namestring2 AS sorttitle,id,counts AS runningtime FROM $DVD_STATS_TABLE "
        ."WHERE stattype='LongTime$ADULT' LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        $dvd['title'] = "<a href=\"$PHP_SELF?mediaid=$dvd[id]&amp;action=show\" "
                ."title=\"$dvd[sorttitle]\">$dvd[title]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;$lang[MINUTES]</td></tr>\n",
            $dvd['title'], $dvd['runningtime']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "LongTime$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// $TopX profiles with shortest running time
    echo "<table width=\"50%\" class=bgl id=\"short\" style=\"display:none\">\n";
    $sql = "SELECT namestring1 AS title,namestring2 AS sorttitle,id,counts AS runningtime FROM $DVD_STATS_TABLE "
        ."WHERE stattype='ShortTime$ADULT' LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        $dvd['title'] = "<a href=\"$PHP_SELF?mediaid=$dvd[id]&amp;action=show\" "
                ."title=\"$dvd[sorttitle]\">$dvd[title]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;$lang[MINUTES]</td></tr>\n",
            $dvd['title'], $dvd['runningtime']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "ShortTime$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// $TopX profiles with longest running time NO TV
    echo "<table width=\"50%\" class=bgl id=\"longnotv\" style=\"display:none\">\n";
    $sql = "SELECT namestring1 AS title,namestring2 AS sorttitle,id,counts AS runningtime FROM $DVD_STATS_TABLE "
        ."WHERE stattype='LongTimeNOTV$ADULT' LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        $dvd['title'] = "<a href=\"$PHP_SELF?mediaid=$dvd[id]&amp;action=show\" "
                ."title=\"$dvd[sorttitle]\">$dvd[title]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;$lang[MINUTES]</td></tr>\n",
            $dvd['title'], $dvd['runningtime']);
    }
    $db->sql_freeresult($result);
    echo "</table>\n";
    $ProfileName[$numtimings] = "LongTimeNOTV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// $TopX profiles with shortest running time
    echo "<table width=\"50%\" class=bgl id=\"shortnotv\" style=\"display:none\">\n";
    $sql = "SELECT namestring1 AS title,namestring2 AS sorttitle,id,counts AS runningtime FROM $DVD_STATS_TABLE "
        ."WHERE stattype='ShortTimeNOTV$ADULT' LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        $dvd['title'] = "<a href=\"$PHP_SELF?mediaid=$dvd[id]&amp;action=show\" "
                ."title=\"$dvd[sorttitle]\">$dvd[title]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;$lang[MINUTES]</td></tr>\n",
            $dvd['title'], $dvd['runningtime']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "ShortTimeNOTV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

    echo "</center><BR>\n";
    echo "<script type=\"text/javascript\">RegisterRadioButtons('runtime', 'long', 'short', 'longnotv', 'shortnotv');</script>\n";

// ***************** Actor Statistics
    echo "<center><table width=\"75%\" class=f1><tr><td>$lang[TOPTENACTORS]</td></tr>\n";
    echo "<tr><td class=f1sm><form name=\"actors\">";
    echo "<center><table class=f1sm>";
    echo "<tr><td align=right>$lang[ACTORS]<input type=radio name=1 onClick=\"RadioAlternate('actors', 'mostactor');\" checked></td>";
    echo "<td align=left><input type=radio name=1 onClick=\"RadioAlternate('actors', 'actornv');\">$lang[ACTORSNV]</td></tr>";
//  echo "<tr><td align=right>$lang[NORMACTORS]<input type=radio name=1 onClick=\"RadioAlternate('actors', 'normactor');\"></td>";
//  echo "<td align=left><input type=radio name=1 onClick=\"RadioAlternate('actors', 'normactornv');\">$lang[NORMACTORSNV]</td></tr>";
    echo "<tr><td align=right>$lang[ACTORSNOTV]<input type=radio name=1 onClick=\"RadioAlternate('actors', 'actornotv');\"></td>";
    echo "<td align=left><input type=radio name=1 onClick=\"RadioAlternate('actors', 'actornvnotv');\">$lang[ACTORSNVNOTV]</td></tr>";
//  echo "<tr><td align=right>$lang[NORMACTORSNOTV]<input type=radio name=1 onClick=\"RadioAlternate('actors', 'normactornotv');\">"</td>;
//  echo "<td align=left><input type=radio name=1 onClick=\"RadioAlternate('actors', 'normactornvnotv');\">$lang[NORMACTORSNVNOTV]</td></tr>";
    echo "<tr><td align=right>$lang[ACTORSORIG]<input type=radio name=1 onClick=\"RadioAlternate('actors', 'actoror');\"></td>";
    echo "<td align=left><input type=radio name=1 onClick=\"RadioAlternate('actors', 'actorornotv');\">$lang[ACTORSORIGNOTV]</td></tr>";
    echo "</table></center></form></td></tr></table></center><br>\n";

    echo "<center>";
// $TopX most collected Actors
    echo "<table width=\"50%\" class=bgl id=\"mostactor\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_ACTOR_TABLE ca "
        ."WHERE stattype='Actors$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcast, $castsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=ACTOR&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "Actors$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// $TopX most collected Actors excluding voice-only parts
    echo "<table width=\"50%\" class=bgl id=\"actornv\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_ACTOR_TABLE ca "
        ."WHERE stattype='ActorsNV$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcast, $castsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=ACTOR&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "ActorsNV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

/**********************************
// $TopX most collected Actors counting BoxSets as 1
    echo "<table width=\"50%\" class=bgl id=\"normactor\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_ACTOR_TABLE ca "
        ."WHERE stattype='NormActors$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcast, $castsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=ACTOR&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "NormActors$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// $TopX most collected Actors (non-voice) counting BoxSets as 1
    echo "<table width=\"50%\" class=bgl id=\"normactornv\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_ACTOR_TABLE ca "
        ."WHERE stattype='NormActorsNV$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcast, $castsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=ACTOR&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "NormActorsNV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();
*********************************/

// $TopX most collected Actors NOTV
    echo "<table width=\"50%\" class=bgl id=\"actornotv\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_ACTOR_TABLE ca "
        ."WHERE stattype='ActorsNOTV$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcast, $castsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=ACTOR&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "ActorsNOTV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// $TopX most collected Actors excluding voice-only parts NOTV
    echo "<table width=\"50%\" class=bgl id=\"actornvnotv\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_ACTOR_TABLE ca "
        ."WHERE stattype='ActorsNVNOTV$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcast, $castsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=ACTOR&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "ActorsNVNOTV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

/**********************************
// $TopX most collected Actors counting BoxSets as 1 NO TV
    echo "<table width=\"50%\" class=bgl id=\"normactornotv\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_ACTOR_TABLE ca "
        ."WHERE stattype='NormActorsNOTV$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcast, $castsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=ACTOR&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "NormActorsNOTV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// $TopX most collected Actors (non-voice) counting BoxSets as 1 NO TV
    echo "<table width=\"50%\" class=bgl id=\"normactornvnotv\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_ACTOR_TABLE ca "
        ."WHERE stattype='NormActorsNVNOTV$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcast, $castsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=ACTOR&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "NormActorsNVNOTV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();
*********************************/

// $TopX most collected Actors OriginalTitle
    echo "<table width=\"50%\" class=bgl id=\"actoror\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_ACTOR_TABLE ca "
        ."WHERE stattype='ActorsOR$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcast, $castsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=ACTOR&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "ActorsOR$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// $TopX most collected Actors NOTV
    echo "<table width=\"50%\" class=bgl id=\"actorornotv\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_ACTOR_TABLE ca "
        ."WHERE stattype='ActorsORNOTV$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcast, $castsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=ACTOR&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "ActorsNOTV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

    echo "</center><br>\n";
    echo "<script type=\"text/javascript\">RegisterRadioButtons('actors', 'mostactor', 'actornv'";
//  echo ", 'normactor', 'normactornv'";
    echo ", 'actornotv', 'actornvnotv'";
//  echo ", 'normactornotv', 'normactornvnotv'";
    echo ", 'actoror', 'actorornotv'";
    echo ");</script>\n";

// ***************** Director Statistics
    echo "<center><table width=\"75%\" class=f1><tr><td>$lang[TOPTENDIRS]</td></tr>\n";
    echo "<tr><td class=f1sm><form name=\"dirs\">";
    echo "<center><table class=f1sm>";
    echo "<tr><td align=right>$lang[DIRECTORS]<input type=radio name=1 onClick=\"RadioAlternate('dirs', 'mostdir');\" checked></td>";
    echo "<td align=left><input type=radio name=1 onClick=\"RadioAlternate('dirs', 'dirnotv');\">$lang[DIRSNOTV]</td></tr>";
//  echo "<tr><td align=right>$lang[NORMDIRS]<input type=radio name=1 onClick=\"RadioAlternate('dirs', 'normdir');\"></td>";
//  echo "<td align=left><input type=radio name=1 onClick=\"RadioAlternate('dirs', 'normdirnotv');\">$lang[NORMDIRSNOTV]</td></tr>";
    echo "</table></center></form></td></tr></table></center><br>\n";

    echo "<center>";
// $TopX most collected Directors
    echo "<table width=\"50%\" class=bgl id=\"mostdir\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_CREDITS_TABLE ca "
        ."WHERE stattype='Directors$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcrew, $crewsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=CREDIT&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "Directors$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// $TopX most collected Directors NO TV
    echo "<table width=\"50%\" class=bgl id=\"dirnotv\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_CREDITS_TABLE ca "
        ."WHERE stattype='DirectorsNOTV$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcrew, $crewsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=CREDIT&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "DirectorsNOTV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

/***************************************
// $TopX most collected Directors counting BoxSets as 1
    echo "<table width=\"50%\" class=bgl id=\"normdir\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_CREDITS_TABLE ca "
        ."WHERE stattype='NormDirectors$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcrew, $crewsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=CREDIT&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "NormDirectors$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// $TopX most collected Directors counting BoxSets as 1 NO TV
    echo "<table width=\"50%\" class=bgl id=\"normdirnotv\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_CREDITS_TABLE ca "
        ."WHERE stattype='NormDirectorsNOTV$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcrew, $crewsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=CREDIT&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "NormDirectorsNOTV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();
**************************************/

    echo "</center><br>\n";
    echo "<script type=\"text/javascript\">RegisterRadioButtons('dirs', 'mostdir', 'dirnotv'";
//  echo ", 'normdir', 'normdirnotv'";
    echo ");</script>\n";

// ***************** Writer Statistics
    echo "<center><table width=\"75%\" class=f1><tr><td>$lang[TOPTENWRITERS]</td></tr>\n";
    echo "<tr><td class=f1sm><form name=\"writers\">";
    echo "<center><table class=f1sm>";
    echo "<tr><td align=right>$lang[WRITERS]<input type=radio name=1 onClick=\"RadioAlternate('writers', 'mostwriter');\" checked></td>";
    echo "<td align=left><input type=radio name=1 onClick=\"RadioAlternate('writers', 'writernotv');\">$lang[WRITERSNOTV]</td></tr>";
//  echo "<tr><td align=right>$lang[NORMWRITERS]<input type=radio name=1 onClick=\"RadioAlternate('writers', 'normwriters');\"></td>";
//  echo "<td align=left><input type=radio name=1 onClick=\"RadioAlternate('writers', 'normwritersnotv');\">$lang[NORMWRITERSNOTV]</td></tr>";
    echo "</table></center></form></td></tr></table></center><br>\n";

    echo "<center>";
// $TopX most collected Writers
    echo "<table width=\"50%\" class=bgl id=\"mostwriter\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_CREDITS_TABLE ca "
        ."WHERE stattype='Writers$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcrew, $crewsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=CREDIT&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "Writers$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// $TopX most collected Writers NO TV
    echo "<table width=\"50%\" class=bgl id=\"writernotv\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_CREDITS_TABLE ca "
        ."WHERE stattype='WritersNOTV$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcrew, $crewsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=CREDIT&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "WritersNOTV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

/*************************************
// $TopX most collected Writers counting BoxSets as 1
    echo "<table width=\"50%\" class=bgl id=\"normwriters\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_CREDITS_TABLE ca "
        ."WHERE stattype='NormWriters$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcrew, $crewsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=CREDIT&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "NormWritersNOTV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

// $TopX most collected Writers counting BoxSets as 1 NO TV
    echo "<table width=\"50%\" class=bgl id=\"normwritersnotv\" style=\"display:none\">\n";
    $sql = "SELECT namestring1,namestring2,firstname,middlename,lastname,fullname,birthyear,counts AS times FROM $DVD_STATS_TABLE s,$DVD_COMMON_CREDITS_TABLE ca "
        ."WHERE stattype='NormWritersNOTV$ADULT' AND namestring1=caid LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        GetHeadAndMouse($dvd, $headcrew, $crewsubs, $image, $mouse);
        $str = "<a target=\"_blank\" href=\"javascript:;\" $mouse onClick=\"window.open("
            ."'popup.php?acttype=CREDIT&amp;fullname=".urlencode($dvd['namestring1'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[fullname]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d&nbsp;(%d)</td></tr>\n",
            "$image&nbsp;$str", $dvd['times'], $dvd['namestring2']);
    }
    $db->sql_freeresult($result);
    echo "</table>";
    $ProfileName[$numtimings] = "NormWritersNOTV$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();
***************************************/

    echo "</center><br>\n";
    echo "<script type=\"text/javascript\">RegisterRadioButtons('writers', 'mostwriter', 'writernotv'";
//  echo ", 'normwriters', 'normwritersnotv'";
    echo ");</script>\n";

// ***************** Writer Statistics
    echo "<center><table width=\"75%\" class=f1><tr><td>$lang[TOPTENSTUDIOS]</td></tr></table></center><BR>\n";

// $TopX most collected studios
    echo "<center><table width=\"50%\" class=bgl>\n";
    $sql = "SELECT studio,count(*) AS times from $DVD_STUDIO_TABLE s,$DVD_TABLE d "
        ."WHERE d.id=s.id AND collectiontype='owned' and ismediacompany<>1 $noadult GROUP BY studio "
        ."ORDER BY times DESC LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        $str = "<a target=\"_blank\" href=\"javascript:;\" onClick=\"window.open("
            ."'popup.php?acttype=STUDIO&amp;fullname=".urlencode($dvd['studio'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[studio]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d</td></tr>\n",
            $str, $dvd['times']);
    }
    $db->sql_freeresult($result);
    echo "</table></center><BR>\n";
    $ProfileName[$numtimings] = "Studios$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

    echo "<center><table width=\"75%\" class=f1><tr><td>$lang[TOPTENMEDIACOMPANIES]</td></tr></table></center><BR>\n";

// $TopX most collected media companies
    echo "<center><table width=\"50%\" class=bgl>\n";
    $sql = "SELECT studio,count(*) AS times from $DVD_STUDIO_TABLE s,$DVD_TABLE d "
        ."WHERE d.id=s.id AND collectiontype='owned' and ismediacompany=1 $noadult GROUP BY studio "
        ."ORDER BY times DESC LIMIT $TopX";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        $str = "<a target=\"_blank\" href=\"javascript:;\" onClick=\"window.open("
            ."'popup.php?acttype=STUDIO&amp;fullname=".urlencode($dvd['studio'])."',"
            ."'Actors',$ActorWindowSettings); return false;\">$dvd[studio]</a>";
        printf("<tr><td class=f3np>%s</td>"
            ."<td class=f2np align=right>%d</td></tr>\n",
            $str, $dvd['times']);
    }
    $db->sql_freeresult($result);
    echo "</table></center><BR>\n";
    $ProfileName[$numtimings] = "MediaCompany$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();

    if ($IsPrivate) {
// $TopX most expensive DVDs
        $sql = "SELECT namestring1 AS title,namestring2 AS ppci,id,counts as paid FROM $DVD_STATS_TABLE "
            ."WHERE stattype LIKE '%PricePaid$ADULT' "
            ."ORDER BY ppci,paid DESC";
        $result = $db->sql_query($sql) or die($db->sql_error());
        $oldppci = '';
        $countppci = 0;
        while ($dvd = $db->sql_fetchrow($result)) {
            if ($oldppci != $dvd['ppci']) {
                if ($oldppci != '')
                    echo "</table></center><BR>\n";
                echo "<center><table width=\"75%\" class=f1><tr><td>$lang[TOPTENEXPENSIVE]<BR>($dvd[ppci])</td></tr></table></center><BR>\n";
                echo "<center><table width=\"50%\" class=bgl>\n";
                $oldppci = $dvd['ppci'];
                $countppci = 0;
            }
            $countppci++;
            if ($countppci <= $TopX) {
                $dvd['title'] = "<a href=\"$PHP_SELF?mediaid=$dvd[id]&action=show\">$dvd[title]</a>";
                printf("<tr><td class=f3np>%s</td>"
                    ."<td class=f2np align=right>%s&nbsp;%s</td></tr>\n",
                    $dvd['title'],
                    my_money_format($dvd['ppci'], round($dvd['paid']/1000, money_digits($dvd['ppci']))), $dvd['ppci']);
            }
        }
        $db->sql_freeresult($result);
        echo "</table></center><BR>\n";
        $ProfileName[$numtimings] = "PricePaid$ADULT"; $Profile[$numtimings++] = microtime_float()-$t0; $t0 = microtime_float();
    }

if (0) {  // ********** not being executed *********
// ***************** Lists of Profiles by Audio characteristics
// List of Profiles with true 6.1 Audio Channels
    echo "<center><table width=\"75%\" class=f1><tr><td>Titres encod�s en 6.1 canaux discrets</td></tr></table></center><BR>\n";
    echo "<center><table width=\"75%\" class=bgl>\n";
    $sql = "SELECT DISTINCT(d.id), title, sorttitle, featureother, audiolanguage, audiochannels, audiocompression AS compression FROM $DVD_TABLE d, $DVD_AUDIO_TABLE a "
        ."WHERE audiochannels LIKE '6.1%' AND d.id=a.id AND collectiontype='owned' $noadult "
        ."ORDER BY title";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        $dvd['title'] = "<a href=\"$PHP_SELF?mediaid=$dvd[id]&action=show\" "
                ."title=\"$dvd[sorttitle]\">$dvd[title]</a>";
        if ($dvd['compression'] == 'DTS (Digital Theater Systems)') {
            if (strpos('Matrix', $dvd['featureother']) !== false)
                $str = '';
            else
                $str = 'DTS-ES Discrete';   // would need to be translated
            printf("<tr><td class=f3np>%s</td>"
                ."<td class=f2np>%s</td>"
                ."<td class=f2np>%s</td></tr>\n",
                $dvd['title'], $str, $alang_translation[$dvd['audiolanguage']]);
        }
    }
    $db->sql_freeresult($result);
    echo "</table></center><BR>\n";

// List of Profiles with 5+1.1 Audio Channels (Matrix)
    echo "<center><table width=\"75%\" class=f1><tr><td>Titres encod�s en 5+1.1 canaux matric�s</td></tr></table></center><BR>\n";
    echo "<center><table width=\"75%\" class=bgl>\n";
    $sql = "SELECT DISTINCT(d.id), title, sorttitle, featureother, audiolanguage, audiochannels, audiocompression AS compression FROM $DVD_TABLE d, $DVD_AUDIO_TABLE a "
        ."WHERE audiochannels LIKE '6.1%' AND d.id=a.id AND collectiontype='owned' $noadult "
        ."ORDER BY title";
    $result = $db->sql_query($sql) or die($db->sql_error());
    while ($dvd = $db->sql_fetchrow($result)) {
        $dvd['title'] = "<a href=\"$PHP_SELF?mediaid=$dvd[id]&action=show\" "
                ."title=\"$dvd[sorttitle]\">$dvd[title]</a>";
        if ($dvd['compression'] == 'DTS (Digital Theater Systems)') {
            if (strpos('Matrix', $dvd['featureother']) !== false) {
                printf("<tr><td class=f3np>%s</td>"
                    ."<td class=f2np>%s</td>"
                    ."<td class=f2np>%s</td></tr>\n",
                    $dvd['title'], 'DTS-ES Matrix', // would need to be translated
                    $alang_translation[$dvd['audiolanguage']]);
            }
        }
        else  {
            printf("<tr><td class=f3np>%s</td>"
                ."<td class=f2np>%s</td>"
                ."<td class=f2np>%s</td></tr>\n",
                $dvd['title'], 'Dolby EX',  // would need to be translated
                $alang_translation[$dvd['audiolanguage']]);
        }
    }
    $db->sql_freeresult($result);
    echo "</table></center><BR>\n";
}   // end of if (0)   // ********** not being executed *********

    if ($IsPrivate && $ProfileStatistics) {
        echo "<h5><pre>";
        echo "Timings:\n";
        $totaltime = 0;
        for ($i=0; $i<$numtimings; $i++) {
            $totaltime += $Profile[$i];
            printf("  %-20s %7s\n", $ProfileName[$i], number_format($Profile[$i], 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']));
        }
        echo "Total Time: ",number_format($totaltime, 3, $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP']),"\n";
        echo "</pre></h5>";
    }
    unset($ProfileName);
    unset($Profile);
    echo '<script language="JavaScript" type="text/javascript" src="wz_tooltip.js"></script>';
    echo "$endbody</html>\n";
