<?php
define('IN_SCRIPT', 1);

include_once('global.php');
if ($TryToChangeMemoryAndTimeLimits) @ini_set('memory_limit', -1);
include_once($jpgraphlocation.'jpgraph.php');
include_once($jpgraphlocation.'jpgraph_bar.php');

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

$sql = "SELECT suppliername,COUNT(*) AS count FROM $DVD_TABLE LEFT JOIN $DVD_SUPPLIER_TABLE ON purchaseplace=sid "
	."WHERE collectiontype='owned' AND suppliername!='Unknown' $placespecialcondition GROUP BY suppliername";
$result = $db->sql_query($sql) or die($db->sql_error());

$places = array();

$maxcount = 0;
while ($row = $db->sql_fetch_array($result)) {
	$place = $row['suppliername'];
	$places[$place] = $row['count'];
	if ($row['count'] > $maxcount)
		$maxcount = $row['count'];
}
$threshold = $maxcount*$placesmin;

// Remove suppliers with < $threshold and add into others

$others = 0;
$name = '';
foreach ($places as $key => $val) {
	if ($places[$key] < $threshold) {
		$others += $places[$key];
		if ($name != '') $name .= "\n";
		$name .= "$key ($places[$key])";
	}
}

$places[' '.$lang['OTHER']] = $others;

uksort($places, 'cmp');

$data = array();
$leg = array();
foreach ($places as $key => $val) {
	if ($places[$key] >= $threshold) {
		$data[] = $places[$key];
		$leg[] = "$key ";
	}
}

$graph = new Graph($graphx, $graphy, 'auto');
$graph->SetScale('textint');
$graph->img->SetMargin(50, 30, 50, 120);
$graph->title->Set(html_entity_decode($lang['GRAPHS']['PP']));

$graph->xaxis->SetTickLabels($leg);
$graph->xaxis->SetFont(FF_ARIAL);
$graph->xaxis->SetLabelAngle(45);
$graph->xaxis->HideTicks();

$graph->yaxis->scale->SetGrace($jpgrace);

$bplot = new BarPlot($data);
$bplot->SetFillColor('lightgreen'); // Fill color
$bplot->value->Show();
$bplot->value->SetFormat('%d');
$bplot->value->SetFont(FF_ARIAL, FS_BOLD);
$bplot->value->SetColor('black', 'navy');
$bplot->SetValuePos('center');
$bplot->SetShadow();

$graph->Add($bplot);
$graph->Stroke();
