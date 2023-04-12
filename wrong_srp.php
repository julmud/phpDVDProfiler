<?php

error_reporting(E_ALL);
defined('IN_SCRIPT') || define('IN_SCRIPT', 1);
include_once('global.php');

if ($inbrowser)
	echo "<pre>";

#Currency's for each locality. Can be more than one (eg, Euro and Franc as dvd's existed before France changed to the Euro)
$curr['LOCALE0'] = "USD";
$curr['LOCALE1'] = "NZD";
$curr['LOCALE2'] = "AUD";
$curr['LOCALE3'] = "CAD";
$curr['LOCALE4'] = "GBP";
$curr['LOCALE5'] = "EUR";
$curr['LOCALE6'] = "CNY";
$curr['LOCALE7'] = "RUB";
$curr['LOCALE8'] = "EUR";
$curr['LOCALE9'] = "EUR";
$curr['LOCALE10'] = "EUR";
$curr['LOCALE11'] = "SEK";
$curr['LOCALE12'] = "NOK";
$curr['LOCALE13'] = "EUR";
$curr['LOCALE14'] = "DKK";
$curr['LOCALE15'] = "EUR";
$curr['LOCALE16'] = "EUR";
$curr['LOCALE17'] = "JPY";
$curr['LOCALE18'] = "KRW";
$curr['LOCALE19'] = "CAN";
$curr['LOCALE20'] = "ZAR";
$curr['LOCALE21'] = "HKD";
$curr['LOCALE22'] = "CHF";
$curr['LOCALE23'] = "BRL";
$curr['LOCALE24'] = "ILS";
$curr['LOCALE25'] = "MXN";
$curr['LOCALE26'] = "ISK";
$curr['LOCALE27'] = "IDR";
$curr['LOCALE28'] = "TWD";
$curr['LOCALE29'] = "PLN";
$curr['LOCALE30'] = "EUR";
$curr['LOCALE31'] = "TRY";
$curr['LOCALE32'] = "ARS";
$curr['LOCALE33'] = "SKK";
$curr['LOCALE34'] = "HUF";
$curr['LOCALE35'] = "SGD";
$curr['LOCALE36'] = "CSK";
$curr['LOCALE37'] = "MYR";
$curr['LOCALE38'] = "THB";
$curr['LOCALE39'] = "INR";
$curr['LOCALE40'] = "EUR";
$curr['LOCALE41'] = "EUR";
$curr['LOCALE42'] = "VND";

$sql = "SELECT id,title, srpcurrencyid FROM $DVD_TABLE";
if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
        $sql .= " WHERE isadulttitle=0";

$sql .= " ORDER BY sorttitle ASC";

$result = $db->sql_query($sql);
$count = $db->sql_numrows($result);
echo "List of profiles, where the SRP currency doesn't match the locality\n";
printf("%20s - %-15s - %-10s - %s\n", "id", "locality", "currency", "title");

$cnt = 0;

while ($dvd = $db->sql_fetchrow($result)) {
	$id = $dvd['id'];
	$locality =  substr(strstr($dvd['id'], '.'), 1, 2);
	if (!$locality)
		$locality = 0;

	$pcurr = $dvd['srpcurrencyid'];

	if ($pcurr != $curr['LOCALE' . $locality]) {
		$cnt++;
		$title = $dvd['title'];
		printf("%20s - %-15s - %-10s - %s\n", $id, $lang['LOCALE' . $locality], $pcurr, $title);
	}
}
$db->sql_freeresult($result);
echo "There are $cnt profiles with the wrong SRP currency. Done\n";
if ($inbrowser)
	echo "</pre>";
