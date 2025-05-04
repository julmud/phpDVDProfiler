<?php
error_reporting(-1);

$goterror = false;
function myErrorHandler($errno, $errstr, $errfile, $errline) {
global $goterror;
    if ($errno != E_NOTICE)
        return(false);
    $goterror = true;
    return(true);
}

// set to the user defined error handler
$old_error_handler = set_error_handler("myErrorHandler");

    $dir = $_SERVER['argv'][0];

    if (($handle=opendir($dir)) === false) {
        echo "Unable to open directory '$dir' to read. Exiting.\n";
        exit;
    }
    echo "<pre>Processing '$dir'\n";;
    while (($old=readdir($handle)) !== false) {
        if (is_dir($old))
            continue;
        echo "Processing '$dir$old' ... ";
        $goterror = false;
        $new = iconv('UTF-8', 'CP1252', $old);
        if ($goterror) {
            echo "non-UTF-8 characters in string: ignoring this file";
        }
        else if ($new == '') {
            echo "new filename is empty: ignoring this file";
        }
        else if ($new != $old) {
            echo "Converting '$old' to '$new'";
            rename($dir.$old, $dir.$new);
        }
        echo "\n";
    }
    closedir($handle);
    echo "Done\n";
