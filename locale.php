<?php
//  **** LOCALE Settings ****
//
// This code tries to set the appropriate locale for the chosen language. Sadly, this sort
// of thing is not well-specified. The code below tries to set the locale to a variety of
// different values depending on the chosen language. The existing list is based upon information
// received from users. The value 'en_US' should work according to the standard. The string
// 'en_US.ISO8859-1' is what will actually work on FreeBSD 5.x systems. According to the
// Microsoft web page, 'english' should work on Windows systems. If this code isn't working
// for you, you will get the default for your system (most likely the 'C' locale which is
// pretty much English). You should try to determine what strings your system wants, and then
// add those to the list. And send me an email, please, so I can update the master source.
// On many Unix derived systems, the command locale -a" will print out a list of supported
// locales, please consult your OS manual for details.
//
if ($allowlocale && isset($_COOKIE['locale'])) {
    $tmp = $_COOKIE['locale'];
    if (($tmp == 'en') ||
        ($tmp == 'dk') ||
        ($tmp == 'de') ||
        ($tmp == 'no') ||
        ($tmp == 'fr') ||
        ($tmp == 'nl') ||
        ($tmp == 'sv') ||
        ($tmp == 'fi') ||
        ($tmp == 'ru'))
        $locale = $tmp;
}

$localeset = 'C';
$ISO88595 = false;
switch ($locale) {
case 'de':
    $localeset = array(
        'de_DE',
        'de_DE.ISO8859-1',
        'de_DE.iso88591',
        'german',
        'deu_DE',
        'ger_DE',
        );
    break;
//case 'dk':            // There doesn't seem to be a locale defined for Danish
case 'en':
    $localeset = array(
        'en_US',
        'en_US.ISO8859-1',
        'en_US.iso88591',
        'english',
        'eng_US',
        );
    break;
case 'fi':
    $localeset = array(
        'fi_FI',
        'fi_FI.ISO8859-1',
        'fi_FI.iso88591',
        'finnish',
        'fin_FI',
        );
    break;
case 'fr':
    $localeset = array(
        'fr_FR',
        'fr_FR.ISO8859-1',
        'fr_FR.iso88591',
        'french',
        'fra_FR',
        );
    break;
case 'no':
    $localeset = array(
        'no_NO',
        'no_NO.ISO8859-1',
        'no_NO.iso88591',
        'norwegian',
        'nor_NO',
        );
    break;
case 'nl':
    $localeset = array(
        'nl_NL',
        'nl_NL.ISO8859-1',
        'nl_NL.iso88591',
        'dutch',
        'nla_NL',
        'dut_NL',
        );
    break;
case 'ru':
    $ISO88595 = true;
    $localeset = array(
        'ru_RU',
        'ru_RU.ISO8859-5',
        'ru_RU.iso88595',
        'Russian_Russia.28595',
        'russian',
        'rus_RU',
        );
    break;
case 'sv':
    $localeset = array(
        'sv_SE',
        'sv_SE.ISO8859-1',
        'sv_SE.iso88591',
        'swedish',
        'sve_SE',
        'swe_SE',
        );
    break;
}
// This requires that the system have the appropriate locale installed
// PHP pre-4.3.0 will throw a warning with the muliple strings in the array, hence the @
@setlocale(LC_ALL, $localeset);
require_once("lang_$locale.php");
if ($language_override != '') {
    if (($x = strrpos($language_override, '.')) !== false) {
        if (substr($language_override, $x) == '.php') {
            $language_override = substr($language_override, 0, $x);
        }
    }
    if (is_readable($language_override . "_$locale")) {
        include_once($language_override . "_$locale");
    }
    else if (is_readable($language_override . "_$locale.php")) {
        include_once($language_override . "_$locale.php");
    }
    else if (is_readable($language_override)) {
        include_once($language_override);
    }
    else if (is_readable($language_override . ".php")) {
        include_once($language_override . ".php");
    }
}
require_once("monetary.php");
