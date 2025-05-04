<?php

error_reporting(E_ALL);
defined('IN_SCRIPT') || define('IN_SCRIPT', 1);
if (!isset($_SERVER['QUERY_STRING']))
    $_SERVER['QUERY_STRING'] = '(Null)';
include_once('global.php');
SendNoCacheHeaders('Content-Type: text/html; charset="windows-1252";');

    if (!isset($runtimeslack))
        $runtimeslack = 2;
    if ($inbrowser)
        echo "<pre>";
    $sql = "SELECT id,title,runningtime,boxchild FROM $DVD_TABLE WHERE collectiontype='owned' AND boxchild <> 0 ORDER BY sorttitle ASC";
    $result = $db->sql_query($sql) or die($db->sql_error());

    while ($dvd = $db->sql_fetchrow($result)) {
        $strout = "Parent: ($dvd[boxchild] children) $dvd[title]\t\t$dvd[runningtime] minutes\n";
        $sql = "SELECT title,runningtime FROM $DVD_TABLE WHERE boxparent='$dvd[id]' ORDER BY sorttitle ASC";
        $result1 = $db->sql_query($sql) or die($db->sql_error());
        $count = $db->sql_numrows($result1);
        if ($count != $dvd['boxchild']) {
            echo "$strout*** Problem: Parent says $dvd[boxchild] children, but $count are found!!!\n";
        }
        $childtot = 0;
        while ($child = $db->sql_fetchrow($result1)) {
            $strout .= "\t$child[title]\t\t$child[runningtime] minutes\n";
            $childtot += $child['runningtime'];
        }
        $db->sql_freeresult($result1);
        $less = 'less';
        $diff = $dvd['runningtime'] - $childtot;
        if ($diff < 0) {
            $diff *= -1;
            $less = 'more'; // I just like writing this :)
        }
        if ($diff > $runtimeslack) {
            echo "$strout*** Problem:\tSum of children is $childtot minutes ($diff minutes $less than the parent profile)\n\n";
        }
    }
    $db->sql_freeresult($result);
    echo "Done\n";
