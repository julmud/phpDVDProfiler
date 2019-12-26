<?php
define('IN_SCRIPT', 1);

include_once('global.php');
if ($TryToChangeMemoryAndTimeLimits) @ini_set('memory_limit', -1);
include($jpgraphlocation.'jpgraph.php');
include($jpgraphlocation.'jpgraph_bar.php');

if (!isset($graphx) || !$graphx)
	$graphx = 800 - 40;
if (!isset($graphy) || !$graphy)
	$graphy = 'auto';
if ($graphy == 'auto')
	$graphy = ($graphx*3)/4;

$sql = $db->sql_query("SELECT productionyear,COUNT(title) AS count "
			."FROM $DVD_TABLE WHERE collectiontype='owned' "
			."AND productionyear>0 "
			."$productionyearspecialcondition GROUP BY productionyear "
			."ORDER BY productionyear") or die($db->sql_error());

if (!isset($high)) $high = 9999;
if (isset($low)) $current = $low - 1;

$data = array();
$leg = array();
$totalcount = 0;
while ($row = $db->sql_fetch_array($sql)) {
	$pdate = $row[0];
	$cnt = $row[1];
	if (!isset($low)) {
		$low = 10*(int)($pdate/10);
		$current = $low - 1;
	}
	if ($pdate > $high) {
		while (++$current <= $high) {
			$data[] = 0;
			$leg[] = $current;
		}
		break;
	}
	if ($pdate < $low)
		continue;
	while (++$current < $pdate) {
		$data[] = 0;
		$leg[] = $current;
	}
	$totalcount += $cnt;
	$data[] = $cnt;
	$leg[] = $pdate;
}
$db->sql_freeresult($sql);

$FixedTitle = html_entity_decode($lang['GRAPHS']['PRODYEAR']);
$FixedTitle .= "\n$low - $high ($totalcount)";

$graph = new Graph($graphx, $graphy, 'auto');
$graph->SetScale('textint');
$graph->img->SetMargin(50, 30, 50, 60);
$graph->title->Set($FixedTitle);

$graph->xaxis->SetTickLabels($leg);
$graph->xaxis->SetTextLabelInterval(($high-$low<=20)?1:5);
$graph->xaxis->SetFont(FF_COURIER);
$graph->xaxis->SetLabelAngle(45);
$graph->xaxis->HideTicks();

$graph->yaxis->scale->SetGrace($jpgrace);

$bplot = new BarPlot($data);
$bplot->SetFillColor('lightgreen'); // Fill color
$bplot->value->SetColor('black', 'navy');
if ($high-$low <= 20) {
	$bplot->value->Show();
	$bplot->value->SetFormat('%d');
	$bplot->value->SetFont(FF_ARIAL, FS_BOLD);
	$bplot->value->HideZero();
	$bplot->SetValuePos('center');
	$bplot->SetShadow();
}

$graph->Add($bplot);
$graph->Stroke();
?>
