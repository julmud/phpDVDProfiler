<?php
define('IN_SCRIPT', 1);

$originsmin = 0;
$hideunknown = true;
include_once('global.php');
if ($TryToChangeMemoryAndTimeLimits) @ini_set('memory_limit', -1);
include($jpgraphlocation.'jpgraph.php');
include($jpgraphlocation.'jpgraph_pie.php');
include($jpgraphlocation.'jpgraph_pie3d.php');
//include($jpgraphlocation.'jpgraph_bar.php');

function cmp($a, $b) {
	if (strtolower($a) == strtolower($b))
		return(0);
	return((strtolower($a) < strtolower($b))? -1 : 1);
}

if (!isset($graphx) || !$graphx)
	$graphx = 800 - 40;
if (!isset($graphy) || !$graphy)
	$graphy = 'auto';
if ($graphy == 'auto')
	$graphy = ($graphx*3)/4;

$sql = "SELECT countryoforigin,countryoforigin2,countryoforigin3, sum(1) count FROM $DVD_TABLE WHERE collectiontype='owned' $originspecialcondition GROUP by countryoforigin,countryoforigin2,countryoforigin3";
$result = $db->sql_query($sql) or die($db->sql_error());

$origins = array();

$unknownkey = " $lang[UNKNOWN]";
$maxcount = 0;
while ($row = $db->sql_fetch_array($result)) {
        $origin = '';
        if ($row['countryoforigin'] != '') {
                $origin = $lang['LOCALE' . $CountryToLocality[$row['countryoforigin']]];
                if (!isset($origins[$origin]))
                        $origins[$origin] = 0;
                $origins[$origin] += $row['count'];
                if ($origins[$origin] > $maxcount)
                        $maxcount = $origins[$origin];
        }
        if ($row['countryoforigin2'] != '') {
                $origin = $lang['LOCALE' . $CountryToLocality[$row['countryoforigin2']]];
                if (!isset($origins[$origin]))
                        $origins[$origin] = 0;
                $origins[$origin] += $row['count'];
                if ($origins[$origin] > $maxcount)
                        $maxcount = $origins[$origin];
        }
        if ($row['countryoforigin3'] != '') {
                $origin = $lang['LOCALE' . $CountryToLocality[$row['countryoforigin3']]];
                if (!isset($origins[$origin]))
                        $origins[$origin] = 0;
                $origins[$origin] += $row['count'];
                if ($origins[$origin] > $maxcount)
                        $maxcount = $origins[$origin];
        }
        if ($origin == '') {
                $origin = $unknownkey;
                $origins[$origin] = $row['count'];
                if ($origins[$origin] > $maxcount)
                        $maxcount = $origins[$origin];
        }
}
$db->sql_freeresult($result);
$threshold = $maxcount*$originsmin;

uksort($origins, 'cmp');

foreach ($origins as $key => $val) {
	if (!(($key == $unknownkey) && $hideunknown)) {
		if ($origins[$key] >= $threshold) {
			$data[] = $origins[$key];
			$leg[] = "$key ";
		}
	}
}

//$graph = new Graph($graphx, $graphy, 'auto');
//$graph->SetScale('textint');
//$graph->img->SetMargin(50, 30, 50, 120);
//$unkncnt = $origins[$unknownkey];
//if ($hideunknown)
//	$graph->title->Set(html_entity_decode($lang['GRAPHS']['COO']."\n($lang[UNKNOWN] = $unkncnt)"));
//else
//	$graph->title->Set(html_entity_decode($lang['GRAPHS']['COO']));
//
//$graph->xaxis->SetTickLabels($leg);
//$graph->xaxis->SetFont(FF_ARIAL);
//$graph->xaxis->SetLabelAngle(45);
//$graph->xaxis->HideTicks();
//
//$graph->yaxis->scale->SetGrace($jpgrace);
//
//$bplot = new BarPlot($data);
//$bplot->SetFillColor('lightgreen'); // Fill color
//$bplot->value->Show();
//$bplot->value->SetFormat('%d');
//$bplot->value->SetFont(FF_ARIAL, FS_BOLD);
//$bplot->value->SetColor('black', 'navy');
//$bplot->SetValuePos('center');
//$bplot->SetShadow();
//
//$graph->Add($bplot);
//$graph->Stroke();
$graph = new PieGraph($graphx, $graphy, 'auto');
$graph->img->SetMargin(50, 30, 50, 60);
//if ($hideunknown)
//	$graph->title->Set(html_entity_decode($lang['GRAPHS']['COO']."\n($lang[UNKNOWN] = $unkncnt)"));
//else
	$graph->title->Set(html_entity_decode($lang['GRAPHS']['COO']));

//$bplot = new PiePlot3D($data);
$bplot = new PiePlot($data);
//$bplot->SetLabels($name, 1);
$bplot->SetGuideLines();
$bplot->SetLabelPos(0.6);
//$bplot->SetStartAngle(45);
//$bplot->ExplodeSlice(array_search($most, $data), 40);
//$bplot->SetEdge('black', 1);
$bplot->value->SetColor('black');

$graph->Add($bplot);
$graph->Stroke();
?>
