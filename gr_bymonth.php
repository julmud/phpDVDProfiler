<?php
defined('IN_SCRIPT') || define('IN_SCRIPT', 1);

include_once('global.php');
if ($TryToChangeMemoryAndTimeLimits) @ini_set('memory_limit', -1);
include_once($jpgraphlocation.'jpgraph.php');
include_once($jpgraphlocation.'jpgraph_bar.php');

if (!isset($graphx) || !$graphx)
	$graphx = 800 - 40;
if (!isset($graphy) || !$graphy)
	$graphy = 'auto';
if ($graphy == 'auto')
	$graphy = ($graphx*3)/4;

// take this to mean that combos should count as the hi-def part or as a dvd
if (!isset($combo_as_hddvd))
	$combo_as_hddvd = true;

$tickint = 4;
$lowest = '9999/99';
$highest = date('Y/m');
$filter = '%Y/%m';

$title = $lang['GRAPHS']['MONTHMONTH'];
if (isset($year) && $year == 'year') {
	$title = $lang['GRAPHS']['MONTHYEARS'];
	$filter = '%Y';
	$lowest = '9999';
	$highest = date('Y');
	$tickint = 1;
}

if (!isset($monthspecialprecondition))
	$monthspecialprecondition = '';

if (isset($year) && is_numeric($year) && $year >= 1997 and $year <= date("Y")) {
	$title = $lang['GRAPHS']['MONTHYEAR'] . $year;
	$monthspecialcondition = "AND date_format(from_unixtime(purchasedate), '%Y') = '$year' " . $monthspecialcondition;
	$highest = "$year/12";
	$lowest = "$year/01";
	$tickint = 1;
}
if (isset($year) && $year == 'last') {
	$title = $lang['GRAPHS']['MONTHLAST'];
	$oneyear = (date('Y') - 1) . '/' . date('m');
	$monthspecialcondition = "AND date_format(from_unixtime(purchasedate), '%Y/%m') > '$oneyear' " . $monthspecialcondition;
	$tickint = 1;
}

$sql = $db->sql_query("SELECT date_format(from_unixtime(purchasedate), '$filter') AS month,"
			."builtinmediatype AS bi,custommediatype AS custom "
                        ."FROM $DVD_TABLE $monthspecialprecondition WHERE collectiontype='owned' "
                        ."$monthspecialcondition ORDER BY month") or die($db->sql_error());

$dvds = array();
$brs = array();
$hddvds = array();

$thetotal = 0;
$totdvd = 0;
$totbr = 0;
$tothddvd = 0;
while ($row = $db->sql_fetch_array($sql)) {
	$month = $row['month'];
	if ($combo_as_hddvd) {
		$dvd   = ($row['bi'] == MEDIA_TYPE_DVD);
		$br    = ($row['bi'] == MEDIA_TYPE_BLURAY || $row['bi'] == MEDIA_TYPE_BLURAY_DVD);
		$hddvd = ($row['bi'] == MEDIA_TYPE_HDDVD || $row['bi'] == MEDIA_TYPE_HDDVD_DVD);
	}
	else {
		$dvd   = ($row['bi'] == MEDIA_TYPE_DVD || $row['bi'] == MEDIA_TYPE_BLURAY_DVD || $row['bi'] == MEDIA_TYPE_HDDVD_DVD);
		$br    = ($row['bi'] == MEDIA_TYPE_BLURAY);
		$hddvd = ($row['bi'] == MEDIA_TYPE_HDDVD);
	}
	if ($month < $lowest)
		$lowest = $month;

	if (!array_key_exists($month, $dvds))
		$dvds[$month] = 0;
	if (!array_key_exists($month, $brs))
		$brs[$month] = 0;
	if (!array_key_exists($month, $hddvds))
		$hddvds[$month] = 0;

	$dvds[$month] += $dvd;
	$brs[$month] += $br;
	$hddvds[$month] += $hddvd;
	$totdvd += $dvd;
	$totbr  += $br;
	$tothddvd += $hddvd;
}

$thetotal = $totdvd + $totbr + $tothddvd;
// Pad empty months

$current = $lowest;
while($current <= $highest) {
	if (!array_key_exists($current, $dvds))
		$dvds[$current] = 0;
	if (!array_key_exists($current, $brs))
		$brs[$current] = 0;
	if (!array_key_exists($current, $hddvds))
		$hddvds[$current] = 0;

	if (!isset($year) || $year <> 'year') {
		list($year, $month) = explode('/', $current);
		$month++;
		if ($month > 12) {
			$month = 1;
			$year++;
		}

		$current = sprintf('%04d/%02d', $year, $month);
	}
	else {
		$current++;
	}

}

ksort($dvds);
ksort($brs);
ksort($hddvds);

$data = array();
$leg = array();
foreach ($dvds as $key => $val) {
	$data1[] = $dvds[$key];
	$data2[] = $brs[$key];
	$data3[] = $hddvds[$key];
	$leg[] = $key;
}

$title = "$thetotal $title";
$graph = new Graph($graphx, $graphy, 'auto');
$graph->SetScale('textint');
$graph->img->SetMargin(50, 30, 30, 80);
$graph->title->Set(html_entity_decode($title));

$graph->xaxis->SetTickLabels($leg);
$graph->xaxis->SetTextLabelInterval($tickint);
$graph->xaxis->SetFont(FF_COURIER);
$graph->xaxis->SetLabelAngle(45);
$graph->xaxis->SetLabelMargin(1);
$graph->xaxis->HideTicks();

$graph->yaxis->scale->SetGrace($jpgrace);

#$bplot = new BarPlot($data);
#$bplot->SetFillColor('lightgreen'); // Fill color
#$bplot->value->SetColor('black', 'navy');

$b1plot = new BarPlot($data1);
$b1plot->SetFillColor('lightgreen'); // Fill color

$b2plot = new BarPlot($data2);
$b2plot->SetFillColor('lightblue'); // Fill color

$b3plot = new BarPlot($data3);
$b3plot->SetFillColor('lightred'); // Fill color

$gbplot = new AccBarPlot(array($b1plot, $b2plot, $b3plot));

$b1plot->SetLegend($lang['DVD']    . ' (' . $totdvd   . ')');
$b2plot->SetLegend($lang['BLURAY'] . ' (' . $totbr    . ')');
$b3plot->SetLegend($lang['HDDVD']  . ' (' . $tothddvd . ')');

$graph->legend->Pos(0.5,0.995, 'center', 'bottom');
$graph->legend->SetColumns(3);

if (count($leg) <= 20) {
	$gbplot->value->Show();
	$gbplot->value->SetFormat('%d');
	$gbplot->value->SetFont(FF_ARIAL, FS_BOLD);
	$gbplot->value->HideZero();
	$gbplot->SetValuePos('center');
	$gbplot->SetShadow();
}

$graph->Add($gbplot);
$graph->Stroke();
