<?php
define('IN_SCRIPT', 1);

include_once('global.php');
if ($TryToChangeMemoryAndTimeLimits) @ini_set('memory_limit', -1);
include_once($jpgraphlocation.'jpgraph.php');
include_once($jpgraphlocation.'jpgraph_pie.php');
include_once($jpgraphlocation.'jpgraph_pie3d.php');

if (!isset($graphx) || !$graphx)
	$graphx = 800 - 40;
if (!isset($graphy) || !$graphy)
	$graphy = 'auto';
if ($graphy == 'auto')
	$graphy = ($graphx*3)/4;

$numslices = $genremax;
if ($numslices < 5)
	$numslices = 5;

$genrespecialcondition = str_replace('genre', 'primegenre', $genrespecialcondition);
$sql = $db->sql_query("SELECT primegenre,COUNT(*) AS total FROM $DVD_TABLE WHERE collectiontype='owned' $genrespecialcondition GROUP BY primegenre ORDER BY total DESC") or die($db->sql_error());

$data = array();
$name = array();
$order = array();
$other = 0;
$i = 0;
while ($row = $db->sql_fetchrow($sql)) {
	if ($i == 0)
		$most = $row['total'];
	if ($i > $numslices-1) {
		$other += $row['total'];
	}
	else {
		$data[$i] = $row['total'];
		$name[$i] = html_entity_decode(GenreTranslation($row['primegenre'])."\n%.1f%%");
		$order[$i] = strlen($name[$i]);
	}
	$i++;
}
$data[] = $other;
$name[] = $ttt = html_entity_decode("$lang[OTHER]\n%.1f%%");
$order[] = strlen($ttt);

array_multisort($order, SORT_DESC, $data, SORT_DESC, $name, SORT_ASC);

$graph = new PieGraph($graphx, $graphy, 'auto');
$graph->img->SetMargin(50, 30, 50, 60);
$graph->title->Set(html_entity_decode($lang['GRAPHS']['PRIMARYGENREPIE']));

$bplot = new PiePlot3D($data);
$bplot->SetLabels($name, 1);
$bplot->SetLabelPos(0.6);
$bplot->SetStartAngle(45);
$bplot->ExplodeSlice(array_search($most, $data), 40);
$bplot->SetEdge('black', 1);
$bplot->value->SetColor('black');

$graph->Add($bplot);
$graph->Stroke();
