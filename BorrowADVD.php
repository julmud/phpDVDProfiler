<?php

define('IN_SCRIPT', 1);
include_once('global.php');

	if (empty($MY_EMAIL_ADDRESS))
		die($lang['BORROW_NO_EMAIL']);
	if (!isset($mediaid))
		die($lang['BORROW_BAD_ARGS']);

	$result = $db->sql_query("SELECT title,description,location,slot from $DVD_TABLE d, $DVD_DISCS_TABLE f where d.id='".$db->sql_escape($mediaid)."' AND f.id=d.id and f.discno=1") or die($db->sql_error());
	$dvd = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	$name = "$dvd[title]";
	if ($dvd['description'] != '')
		$name .= " ($dvd[description])";

	SendNoCacheHeaders('Content-Type: text/html; charset="windows-1252";');

	if (isset($emailaddr)) {
		setcookie('emailaddr', $emailaddr);
		echo<<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
<title>$name</title>
<link rel="stylesheet" type="text/css" href="format.css.php">
</head>
<body class=f6>
<center><table width="100%" class=f1><tr><td>$lang[BORROW_PAGE_HEADING] $MY_EMAIL_ADDRESS</td></tr></table></center>
<BR>
<center><table width="75%" class=f1 cellpadding=5 border=1><tr><td align=right>$lang[BORROW_TITLE]</td><td align=left>$name</td></tr>
<tr><td align=right>$lang[BORROW_EMAIL]</td><td align=left>$emailaddr</td></tr></table><br>
</center>
EOT;

		$headers = "$lang[BORROW_FROM_TEXT] <$MY_EMAIL_ADDRESS>";
		$parameters = '';

		$themessage = "$lang[BORROW_BODY_1] $name\r\n";
		$themessage .= "$lang[BORROW_BODY_2] $emailaddr\r\n";
		$themessage .= "$lang[BORROW_BODY_3] $dvd[location], $dvd[slot]\r\n";
		$themessage .= "\r\n\r\n$lang[BORROW_BODY_4]\r\n\r\n";
		mail($MY_EMAIL_ADDRESS, $lang['BORROW_SUBJECT'], $themessage, $headers, $parameters);

		echo "<center>$lang[BORROW_DONE]</center>$endbody</html>\n";
		exit;
	}

	if (!isset($_COOKIE['emailaddr']))
		$_COOKIE['emailaddr'] = $lang['BORROW_PROMPT_STRING'];
	$onfocus = 'onFocus="this.value=\'\'"';
	if ($_COOKIE['emailaddr'] != $lang['BORROW_PROMPT_STRING'])
		$onfocus = '';
	echo<<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=windows-1252">
<title>$name</title>
<link rel="stylesheet" type="text/css" href="format.css.php">
<script type="text/javascript">
function isValidEmail(str) {
	return (str.indexOf(".") > 2) && (str.indexOf("@") > 0);
}

function ValidateForm(form) {
	if (!isValidEmail(form.emailaddr.value)) { 
		alert('$lang[BORROW_BAD_EMAIL]');
		form.emailaddr.focus(); 
		return(false);
	} 
	return(true);;
} 
</script>
</head>
<body class=f6>
<center><table width="100%" class=f1><tr><td>$lang[BORROW_PAGE_HEADING] $MY_EMAIL_ADDRESS</td></tr></table></center>
<BR>
<center><form action=$PHP_SELF method=post onSubmit="return ValidateForm(this);"><table width="75%" class=f1 cellpadding=5 border=1><tr><td align=right>$lang[BORROW_TITLE]</td><td align=left>$name</td></tr>
<tr><td align=right>$lang[BORROW_EMAIL]</td><td align=left><input id=emailaddr $onfocus type=text name=emailaddr value="$_COOKIE[emailaddr]"></td></tr></table><br>
<input type=hidden name="mediaid" value="$mediaid"><input type=submit value="$lang[BORROW_SUBMIT_TEXT]"></form></center>
$endbody</html>

EOT;
?>
