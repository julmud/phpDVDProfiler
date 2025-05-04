<?php

error_reporting(E_ALL);
defined('IN_SCRIPT') || define('IN_SCRIPT', 1);
include_once('global.php');

if ($inbrowser)
    echo "<pre>";

$coltype = '';
if (isset($collection))
    $coltype = "collectiontype='$collection' AND ";

// List all of the profiles empty of COO
$sql = "SELECT id,title,collectiontype FROM $DVD_TABLE WHERE $coltype (countryoforigin='' and countryoforigin2='' and countryoforigin3='')";
if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
        $sql .= " AND isadulttitle=0";

$sql .= " ORDER BY sorttitle ASC";

$result = $db->sql_query($sql);
$count = $db->sql_numrows($result);
echo "You have $count profiles with no country of origin\n";
if ($count > 0) {
    printf("%20s - %-10s - %s\n", "id", "collection", "title");
    while ($dvd = $db->sql_fetchrow($result)) {
        $id = $dvd['id'];
        $collection = $dvd['collectiontype'];
        $title = $dvd['title'];
        printf("%20s - %-10s - %s\n", $id, $collection, $title);
    }
}
$db->sql_freeresult($result);

// List all of the profiles with an empty slot before a non-empty slot
$sql = "SELECT id,title,collectiontype,countryoforigin,countryoforigin2,countryoforigin3 FROM $DVD_TABLE WHERE $coltype "
    ."((countryoforigin='' AND (countryoforigin2!='' OR countryoforigin3!='')) OR "
    ."(countryoforigin!='' AND countryoforigin2='' AND countryoforigin3!=''))";
if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
        $sql .= " AND isadulttitle=0";

$sql .= " ORDER BY sorttitle ASC";

$result = $db->sql_query($sql);
$count = $db->sql_numrows($result);
echo "You have $count profiles with a missing country of origin before a real country of origin\n";
if ($count > 0) {
    printf("%20s - %-10s - %s: %s\n", "id", "collection", "title", "| coo1 | coo2 | coo3 |");
    while ($dvd = $db->sql_fetchrow($result)) {
        $id = $dvd['id'];
        $collection = $dvd['collectiontype'];
        $title = $dvd['title'];
        printf("%20s - %-10s - %s: | %s | %s | %s |\n", $id, $collection, $title, $dvd['countryoforigin'], $dvd['countryoforigin2'], $dvd['countryoforigin3']);
    }
}
$db->sql_freeresult($result);

// List all of the profiles with a duplicate slot
$sql = "SELECT id,title,collectiontype,countryoforigin,countryoforigin2,countryoforigin3 FROM $DVD_TABLE WHERE $coltype "
    ."(countryoforigin!='' AND (countryoforigin=countryoforigin2 OR countryoforigin=countryoforigin3)) OR (countryoforigin2!='' AND countryoforigin2=countryoforigin3)";
if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
        $sql .= " AND isadulttitle=0";

$sql .= " ORDER BY sorttitle ASC";

$result = $db->sql_query($sql);
$count = $db->sql_numrows($result);
echo "You have $count profiles with a duplicate country of origin\n";
if ($count > 0) {
    printf("%20s - %-10s - %s: %s\n", "id", "collection", "title", "| coo1 | coo2 | coo3 |");
    while ($dvd = $db->sql_fetchrow($result)) {
        $id = $dvd['id'];
        $collection = $dvd['collectiontype'];
        $title = $dvd['title'];
        printf("%20s - %-10s - %s: | %s | %s | %s |\n", $id, $collection, $title, $dvd['countryoforigin'], $dvd['countryoforigin2'], $dvd['countryoforigin3']);
    }
}
$db->sql_freeresult($result);

// Find which COOs are not locales
$sql = "SELECT id,title,collectiontype,countryoforigin,countryoforigin2,countryoforigin3 FROM $DVD_TABLE WHERE $coltype "
    ."(countryoforigin!='' AND (countryoforigin=countryoforigin2 OR countryoforigin=countryoforigin3)) OR (countryoforigin2!='' AND countryoforigin2=countryoforigin3)";
if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
        $sql .= " AND isadulttitle=0";

$sql .= " ORDER BY sorttitle ASC";

$result = $db->sql_query($sql);
$count = $db->sql_numrows($result);
echo "You have $count profiles with a duplicate country of origin\n";
if ($count > 0) {
    printf("%20s - %-10s - %s: %s\n", "id", "collection", "title", "| coo1 | coo2 | coo3 |");
    while ($dvd = $db->sql_fetchrow($result)) {
        $id = $dvd['id'];
        $collection = $dvd['collectiontype'];
        $title = $dvd['title'];
        printf("%20s - %-10s - %s: | %s | %s | %s |\n", $id, $collection, $title, $dvd['countryoforigin'], $dvd['countryoforigin2'], $dvd['countryoforigin3']);
    }
}
$db->sql_freeresult($result);

if ($inbrowser)
    echo "</pre>";
