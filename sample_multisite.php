<?php
    if (php_sapi_name() == 'cli') {
        return;
    }
    if (!isset($_SERVER['REQUEST_URI']))
        return;
    if (substr($_SERVER['REQUEST_URI'], 0, strlen('/phpdvdprofiler.test/')) == '/phpdvdprofiler.test/') {
        $localsiteconfig = 'test_localsiteconfig.php';
    }
    if (substr($_SERVER['REQUEST_URI'], 0, strlen('/phpdvdprofiler.pastel/')) == '/phpdvdprofiler.pastel/') {
        $localsiteconfig = 'pastelsiteconfig.php';
    }

//
// The purpose of this piece of code is to set the variable $localsiteconfig to
// the name of a particular "user" config-file, based on the URL that the viewer
// has used. This can be used to allow a single set of phpdvdprofiler files serve
// up multiple websites. If this code does not change the value of $localsiteconfig
// then the default value ('localsiteconfig.php') will be used.
//
// I use the example above to allow me to have a completely separate database for
// testing and debugging other people's setups (the first url) and for showing how
// some simple manipulation can change the color scheme of the site (the second url)
//
// So it you come to my site as 'www.bws.com/phpdvdprofiler', then you get my regular
// site. If you come to 'www.bws.com/phpdvdprofiler.test' then you get the test database
// and if you ome to 'www.bws.com/phpdvdprofiler.pastel' then you get my regular site
// but in pastel colors.
//
// To accomplish this, I have arranged for all 3 of those urls to reference a single
// directory. I have done this through symlinks on my server, although there are other
// ways to do it (web-server configuration, etc.)
//
// We could set this variable ($localsiteconfig) based on URL, or in some other way ...
//

// The web server should (will) set up some variables that can be used to figure
//    out what request it is answering. Be careful; this coed will get called a number
//    times with different URIs within the phpdvdprofiler directory.

// $_SERVER['HTTP_HOST'] should be set to the hostname that was used for the request
//    so i think if you host multiple domains on the same server this could be used
//    to disambiguate them

// $_SERVER['REQUEST_URI'] should be set to the entire URI (like "/phpdvdprofiler/index.php?action=info"
//    for example) This could be used to set up a multi-site scenario where you create
//    different symlinks pointing to the actual phpdvdprofiler installation directory.

// Then you only need to create a "localsiteconfig.php" file that has the details of
//    the particular installation (the names of each file should be different :)). Each
//    file can be used to set any of the tunable knobs for a configuration, but should
//    have a at least few things defined:
//    $xmlfile  - each site needs an xml file from which to update
//    $dbname   - each site needs its own database or alternatively, you could set
//    $table_prefix - to use different tables in a single database
//    $dbuser   - for security, each database should use a different access username/password
//    $dbpasswd - although technically, they could all use the same one. these are real MySQL
//                users, and so may be restricted by your hosting provider.
//    $images   - could be all set to the same directory, although then differences between
//                cover images will be removed (for people who have dpecial covers).
//    $update_login - used to update collections; should be different for security.
//    $update_pass  - used to update collections; should be different for security.
//    $sitetitle    - used in headers and page titles, etc.
//    $forumuser    - set for image comparisons using ajm's site, if used
//    $collectionurl - set for image comparisons using ajm's site, if used
