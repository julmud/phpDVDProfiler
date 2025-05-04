<?php
defined('IN_SCRIPT') || define('IN_SCRIPT', 1);
include_once('global.php');
include_once('emailmessageconfig.php');

if (!isset($LOAN_REMINDER_FROM) || $LOAN_REMINDER_FROM == 'fred@bws.com') {
    echo "\$LOAN_REMINDER_FROM ***MUST*** be set in the file emailmessageconfig.php\n";
    exit;
}

// These two things can be changed in localsiteconfig.php
if (!isset($LOAN_GRACE)) $LOAN_GRACE = 5;
if (!isset($LOAN_WARNING_INTERVAL)) $LOAN_WARNING_INTERVAL = 3;
if ($LOAN_WARNING_INTERVAL <= 0) $LOAN_WARNING_INTERVAL = 1;

$sql = "SELECT id,title,loandue,FROM_UNIXTIME(loandue,\"%M %D, %Y\") AS ld FROM $DVD_TABLE WHERE loaninfo != ''";
$result = $db->sql_query($sql) or die($db->sql_error());
while ($borrowed = $db->sql_fetchrow($result)) {
    if (($borrowed['loandue'] + $LOAN_GRACE*24*60*60) < mktime()) {
        $numdayspastgrace = (int)((mktime()-$borrowed['loandue']-$LOAN_GRACE*24*60*60)/(24*60*60));
        $warningnumber = ((int)($numdayspastgrace/$LOAN_WARNING_INTERVAL))+1;
        if (($warningnumber-1)*$LOAN_WARNING_INTERVAL == $numdayspastgrace) {
            $sql = "SELECT firstname,lastname,emailaddress,DATE_FORMAT(timestamp,\"%M %e, %Y\") AS ts FROM $DVD_EVENTS_TABLE e,$DVD_USERS_TABLE u WHERE id='$borrowed[id]' AND eventtype='Borrowed' AND e.uid=u.uid ORDER BY timestamp DESC LIMIT 1";
            $result1 = $db->sql_query($sql) or die($db->sql_error());
            $event = $db->sql_fetchrow($result1);
            $db->sql_freeresult($result1);
            $ema = $event['emailaddress'];
            EmailReminder($borrowed['title'], $event['ts'], $borrowed['ld'], $ema, "$event[firstname] $event[lastname]", $warningnumber);
            if ($ema != '')
                echo "Sent reminder #$warningnumber to $event[firstname] $event[lastname] ($ema) about $borrowed[title]\n";
            else
                echo "Sent reminder #$warningnumber to the administrator about $event[firstname] $event[lastname] borrowing $borrowed[title]\n";
        }
    }
}
$db->sql_freeresult($result);
exit;

function EmailReminder($LOAN_TITLE, $LOAN_DATE_BORROWED, $LOAN_DATE_DUE, $LOAN_REMINDER_TO, $LOAN_TO, $LOAN_WARNING_NUMBER) {
global $LOAN_GRACE, $LOAN_WARNING_INTERVAL, $subject, $message, $LOAN_REMINDER_FROM;

echo "$LOAN_TITLE, $LOAN_DATE_BORROWED, $LOAN_DATE_DUE, $LOAN_REMINDER_TO, $LOAN_TO, $LOAN_WARNING_NUMBER\n";
    $LOAN_QUOTED_TITLE = "\"$LOAN_TITLE\"";
    $d = getdate();
    $NOW = "$d[month] $d[mday], $d[year]";

    $WhichSubject = $LOAN_WARNING_NUMBER;
    if ($WhichSubject > count($subject))
        $WhichSubject = count($subject);
    $WhichMessage = $LOAN_WARNING_NUMBER;
    if ($WhichMessage > count($message))
        $WhichMessage = count($message);
    $headers = "From: $LOAN_REMINDER_FROM\r\nCc: $LOAN_REMINDER_FROM\r\nX-phpDVDProfiler: Warning #$LOAN_WARNING_NUMBER";
    $parameters = '';

    $themessage = wordwrap($message[$WhichMessage], 76);
    mail($LOAN_REMINDER_TO, $subject[$WhichSubject], $themessage, $headers, $parameters);
}
