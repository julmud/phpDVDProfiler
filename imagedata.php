<?php

#define('IN_SCRIPT', 1);

# History
# 0.1,	1st line had user/url/lastupdate/version
# 0.2,	1st line has user/url/lastupdate/version/img_webpath
#	2nd line may include 'delete' record. Format 'Delete||id||id etc'
#	data lines now include image filename, if the filename is different to the id
#	fx||$fy||$bx||$by||$fsize||$bsize||$id||$title||$sorttitle||$desc||$collection||$imagefname||$fthumb||$bthumb
# 0.3,	Can't remember what I changed. Opps
# 0.4,  Added handling of front/back images on different webpaths. Also pass version when sending data
# 	1st line has user/url/lastupdate/version/img_webpathf/img_webpathb
# 0.5,  As we now have an 'CompleteUpdate' option I decided that if we're doing a complete update
#	then we might as well check all the images as well
# 0.6,  Added some performance improvements. Instead of getting the timestamp, then filesize
#       and image size and then testing if the file was newer than 'last', I do that test first
# 0.7,  Fixed a bug introduced in 0.6. Always need to check images if you've updated the profile
#	which I wasn't doing.
# 0.8,	Added error code to getimagesize()
# 0.9,  Fixed code that causes image sizes of 0. Assuming its due to back cover changing
#	when the front doesn't.
# 1.0,	Decided to ignore stuff passed to this routine and just check the images.
# 1.1, 	Fixed problem with things being displayed as unlocked when they were in fact locked
#	Changed code to check if posix_uname() call exists before calling it.
# 1.2.	Added summary counts for dvd/hd/br for some more fun stats. So first line is now :-
#	1st line has user/url/lastupdate/version/img_webpath/version/vphp/vmysql/vxmldvd/hd/br
# 1.3,	Added some more code to validate the collectionurl so people get feedback if they have set it up wrong
# 1.4	Added status info while gathering image data
# 1.5	Added new variable $ii_verbose, which automatically enables if last is 00000000
#	Changed call to posix_uname to php_uname
# 1.6	Added code to my_file_get_contents() to fake being firefox (user agent)
#	Added back the check for a changed profile $IDsChanged so that when dvd's are moved from
#	ordered to owned that the stats get changed as well as that's in the image table.

#include_once('global.php');

$version = '1.6';

if (!$inbrowser) {
	$lang['IMAGEDATA']['VERIFY'] = html_entity_decode($lang['IMAGEDATA']['VERIFY']);
	$lang['IMAGEDATA']['UPGRADE'] = html_entity_decode($lang['IMAGEDATA']['UPGRADE']);
	$lang['IMAGEDATA']['OK'] = html_entity_decode($lang['IMAGEDATA']['OK']);
	$lang['IMAGEDATA']['LASTUPDATE'] = html_entity_decode($lang['IMAGEDATA']['LASTUPDATE']);
	$lang['IMAGEDATA']['NOTADULT'] = html_entity_decode($lang['IMAGEDATA']['NOTADULT']);
	$lang['IMAGEDATA']['ADULT'] = html_entity_decode($lang['IMAGEDATA']['ADULT']);
	$lang['IMAGEDATA']['COLLECT'] = html_entity_decode($lang['IMAGEDATA']['COLLECT']);
	$lang['IMAGEDATA']['DONE'] = html_entity_decode($lang['IMAGEDATA']['DONE']);
	$lang['IMAGEDATA']['TOOK'] = html_entity_decode($lang['IMAGEDATA']['TOOK']);
	$lang['IMAGEDATA']['SECONDS'] = html_entity_decode($lang['IMAGEDATA']['SECONDS']);
}

// Function to send and receive our data
function senddata($data, $remote_page, $boundary)
{
	$msg = "POST $remote_page HTTP/1.0\n".
       	"Content-Type: multipart/form-data; boundary=$boundary\n".
       	"Content-Length: ".strlen($data)."\r\n\r\n";

	// Open socket connection ...
	$f = fsockopen(servername($remote_page),80);

	if ($f)
	{
		// Send the data
		fputs($f,$msg.$data);

		// retrieve the response
		$result='';
		while (!feof($f)) $result.=fread($f,1024);
		fclose($f);

		// write the response (if needed)
		$pos1 = strpos($result, '<body>');
		$pos2 = strpos($result, '</body>');
		$ret = substr($result, $pos1+6, $pos2 - $pos1 - 6);
		return ($ret);

	} else {
		 die ('Cannot connect !!!');
	}
}

// function to read server name
function servername($txt)
{
	if (substr(strtoupper($txt),0,4)=='WWW.')
		$txt='HTTP://'.$txt;
	if (substr(strtoupper($txt),0,7)!='HTTP://')
		return 0;
	preg_match('~^(http://([^/ ]+))~i',$txt,$arr);
	return $arr[2];
}

function my_file_get_contents($url) {
global $eoln;

	$timeout = 10;
//	echo "Parsing $url$eoln";
	$host = parse_url($url);
	if (!isset($host['port'])) $host['port'] = 80;

//	echo "\$fp = fsockopen(\"$host[host]\", $host[port], \$errno, \$errstr, $timeout);$eoln";
	$fp = @fsockopen($host['host'], $host['port'], $errno, $errstr, $timeout);

	if (!$fp) {
		echo "Got errno=$errno, errstr=$errstr$eoln";
		return(false);
	}

	fwrite($fp, "GET $host[path]?$host[query] HTTP/1.0\r\n");
	fwrite($fp, "User-Agent: Wget/1.10.2\r\n");
	fwrite($fp, "Host: $host[host]\r\n");
	fwrite($fp, "Connection: Close\r\n\r\n");

	stream_set_blocking($fp, TRUE);
	stream_set_timeout($fp, $timeout);
	$info = stream_get_meta_data($fp);

	$data = '';
	while ((!feof($fp)) && (!$info['timed_out'])) {
		$data .= fgets($fp, 4096);
		$info = stream_get_meta_data($fp);
		flush();
	}

	if ($info['timed_out']) {
		echo "Timed Out with $timeout seconds$eoln";
		return(false);
	}

	if ($data === '') {
		echo "Got an empty page$eoln";
	}
	return($data);
}
#function my_file_get_contents($url) {
#	$timeout = 10;
#	$host = parse_url($url);
#	if (!isset($host['port'])) $host['port'] = 80;
#
#	$fp = @fsockopen($host['host'], $host['port'], $errno, $errstr, $timeout);
#	$data = '';
#
#	if (!$fp)
#		return(false);
#
#	fwrite($fp, "GET $host[path]?$host[query] HTTP/1.0\r\n");
#	fwrite($fp, "User-Agent: Wget/1.10.2\r\n");
#	fwrite($fp, "Host: $host[host]\r\n");
#	fwrite($fp, "Connection: Close\r\n\r\n");
#
#	stream_set_blocking($fp, TRUE);
#	stream_set_timeout($fp,$timeout);
#	$info = stream_get_meta_data($fp);
#
#	while ((!feof($fp)) && (!$info['timed_out'])) {
#		$data .= fgets($fp, 4096);
#		$info = stream_get_meta_data($fp);
#		#ob_flush();
#		flush();
#	}
#
#	if ($info['timed_out'])
#		return(false);
#
#	return($data);
#}

$host = parse_url($collectionurl);
$ii = false;
if ($host['host'] == 'localhost') {
	$ii = true;
	echo $lang['IMAGEDATA']['PRIVATE'] . $eoln;
	flush();
	@ob_flush();
}
# remove trailing /
$patterns[] = '/index.php/';
$patterns[] = '!/$!';
$replacements[] = '';
$replacements[] = '';
$cu = preg_replace($patterns, $replacements, $collectionurl);
# Add a trailing / as the server side wants it.
$cu .= '/';
$URL = $cu . 'index.php?action=info';
$page = my_file_get_contents($URL);
preg_match('/phpDVDProfiler:Version=/', $page, $phpdvdprofiler);
if (isset($phpdvdprofiler[0])) {
	$ii = true;
	echo $lang['IMAGEDATA']['VALID1'] . $eoln;
} else {
	echo "{$lang['IMAGEDATA']['INVALID1']} \$collectionurl ($cu) {$lang['IMAGEDATA']['INVALID2']} $eoln";
	echo "Received page =<pre>", var_dump($page), "</pre>$eoln";
}
flush();
@ob_flush();

$ii = false;
if ($ii) {

    echo $lang['IMAGEDATA']['VERIFY'] . '....';
    flush();
    @ob_flush();

    // init ...
    srand((double)microtime()*1000000);
    #$remote_page = 'http://andy.snowhopers.com/ii/do_upload.php';
    $remote_page = 'http://dvdaholic.me.uk/ii/do_upload.php';
    $boundary = '---------------------------'.substr(md5(rand(0,32000)),0,10);

    // define HTTP POST DATA
    $data = "--$boundary\n".
            "Content-Disposition: form-data; name=\"version\"\n".
	        "\n$version\n".
            "--$boundary--\r\n\r\n";

    $result = senddata($data, $remote_page, $boundary);

    if ( $result ) {
	    echo $lang['IMAGEDATA']['UPGRADE'] . " $result$eoln";
	    $ii = false;
    } else {
	    echo $lang['IMAGEDATA']['OK'] . "$eoln";
    }
}

if ($ii) {
    echo $lang['IMAGEDATA']['LASTUPDATE'] . "....";
    flush();
    @ob_flush();

    // if $delete is set, we're doing a complete update. So might as well include the images
    // this means not caring about the last update date.
    $last = '00000000';
    // this is a little convoluted. if $forceimageupdate is set and set to true, then we don't
    // want to do this. if we're doing a complete update, then we don't want to do this
    // else we want to do it ...
    if ( $delete )
	    $last = '99999999';

    // define HTTP POST DATA
    $data = "--$boundary\n".
       	    "Content-Disposition: form-data; name=\"lastupdate\"\n".
	        "\n$last\n".
       	    "--$boundary\n".
       	    "Content-Disposition: form-data; name=\"user\"\n".
	        "\n$forumuser\n".
       	    "--$boundary--\r\n\r\n";

    $last = senddata($data, $remote_page, $boundary);

    if ( !$last ) {
	    $last = '00000000';
    }

    echo "($last)$eoln";
    $now = date('Ymd', time());

    # Count dvd, bluray and hdvd

    $totdvd = $tothddvd = $totbluray = 0;
    $sql = "SELECT builtinmediatype AS bi,COUNT(*) AS count FROM $DVD_TABLE WHERE collectiontype='owned' GROUP BY builtinmediatype";
    $result = $db->sql_query($sql) or die(mysql_error());
    while ($mtype = $db->sql_fetchrow($result)) {
	    switch ($mtype['bi']) {
	    case MEDIA_TYPE_DVD:
		    $totdvd += $mtype['count'];
		    break;
	    case MEDIA_TYPE_HDDVD:
		    $tothddvd += $mtype['count'];
		    break;
	    case MEDIA_TYPE_HDDVD_DVD:
		    $tothddvd += $mtype['count'];
		    $totdvd += $mtype['count'];
		    break;
	    case MEDIA_TYPE_BLURAY:
		    $totbluray += $mtype['count'];
		    break;
	    case MEDIA_TYPE_BLURAY_DVD:
		    $totbluray += $mtype['count'];
		    $totdvd += $mtype['count'];
		    break;
	    }
    }
    $db->sql_freeresult($result);

    # exclude manually added titles
    $sql = "SELECT id, title, sorttitle, description, collectiontype FROM $DVD_TABLE where id not like \"M%\"";
    if ( $handleadult ) {
	    echo $lang['IMAGEDATA']['NOTADULT'] . "$eoln";
        $sql .= " and isadulttitle=0";
    } else {
	    echo $lang['IMAGEDATA']['ADULT'] . "$eoln";
    }

    echo $lang['IMAGEDATA']['COLLECT'] . "...$eoln";
    flush();
    @ob_flush();
    $time_start = microtime_float();

    if (isset($img_webpath) && (!isset($img_webpathf) && !isset($img_webpathb))) {
	    $img_webpathf = $img_webpath;
	    $img_webpathb = $img_webpath;
    }

    if (!isset($img_webpathf))
	    $img_webpathf = '';

    if (!isset($img_webpathb))
	    $img_webpathb = '';

    $vphp = phpversion();
    $vmysql = MySQLVersion();
    $vxml = date("Y-m-d-H:i:s", GetLastUpdateTime('LastUpdate'));
    $os = php_uname('s');

    $filedata = "$forumuser||$collectionurl||" . date("Ymd") . "||$version||$img_webpathf||$img_webpathb";
    $filedata .= "||$VersionNum||$vphp||$vmysql||$vxml||$os";
    $filedata .= "||$totdvd||$tothddvd||$totbluray\n";
    if ( isset($del) && $del ) {
	    $filedata .= "$del\n";
    }

    if (!isset($ii_verbose))
	    $ii_verbose = false;

    if ( $last == '00000000' )
	    $ii_verbose = true;

    $result = $db->sql_query($sql) or die(mysql_error());
    $cnt = 0;
    //$displayfreq = 100;	// now a global
    while ($row = $db->sql_fetchrow($result))
    {
	    $cnt++;
	    if ($ii_verbose && (($cnt % $displayfreq) == 0) )
		    echo $lang['IMAGEDATA']['CHECKED'], $cnt, $eoln;

	    $id = $row['id'];
	    $title = $row['title'];
	    $sorttitle = $row['sorttitle'];
	    $desc = $row['description'];
	    $collection = $row['collectiontype'];
	    $fx = 0;
	    $fy = 0;
	    $bx = 0;
	    $by = 0;
	    $fsize = 0;
	    $bsize = 0;
	    $fid = $id . 'f.jpg';
	    $bid = $id . 'b.jpg';

	    $tmp = findfilecase($img_physpath, $id . 'f.jpg');
	    if ( $tmp != '' && $tmp != $id . 'f.jpg') {
        	    $fid = $tmp;
	    }
	    $tmp = findfilecase($img_physpath, $id . 'b.jpg');
	    if ( $tmp != '' && $tmp != $id . 'b.jpg') {
        	    $bid = $tmp;
	    }

	    $fname = $img_physpath . $fid;
	    $bname = $img_physpath . $bid;

	    $fthumbname = $img_physpath . $thumbnails .'/' . $fid;
	    $bthumbname = $img_physpath . $thumbnails .'/' . $bid;

	    # I don't care what the filename id is, if its the same as the id.
	    if ( $fid == $id . 'f.jpg')
		    $fid = '';

	    if ( $bid == $id . 'b.jpg')
		    $bid = '';

	    $fx = '-1';
	    $fy = '-1';
	    $bx = '-1';
	    $by = '-1';
	    $flm = 0;
	    $blm = 0;
	    $ftlm = 0;
	    $btlm = 0;
	    $fthumb = 0;
	    $bthumb = 0;
	    $ftstamp = 0;
	    $btstamp = 0;
	    $update = 0;
	    $locked = 0;

	    if (file_exists($fname)) {
		    $flm =  date ('Ymd', filemtime($fname) );
	    }
	    if (file_exists($bname)) {
		    $blm =  date ('Ymd', filemtime($bname) );
	    }
	    if (file_exists($fthumbname)) {
		    $fthumb = 1;
		    $ftlm =  date ('Ymd', filemtime($fthumbname) );
	    }
	    if (file_exists($bthumbname)) {
		    $bthumb = 1;
		    $btlm =  date ('Ymd', filemtime($bthumbname) );
	    }

	    if ( isset($IDsChanged[$id]) || $flm >= $last || $blm >= $last || $ftlm >= $last || $btlm >= $last ) {
		    $update = 1;
	    }

	    if ( $update ) {
		    # Only check the lock if we're doing an update. Should stop things displaying as unlocked
		    $sql = "SELECT covers FROM $DVD_LOCKS_TABLE WHERE id = '".$db->sql_escape($id)."'";
		    $res = $db->sql_query($sql) or die($db->sql_error());
		    $row = $db->sql_fetchrow($res);
		    $locked = $row['covers'];

		    $fx = 0;
		    $fy = 0;
		    if ($flm) {
			    $fsize = @filesize($fname);
			    $fdim = @getimagesize($fname);
			    $ftstamp = $flm;
			    if ( $fdim ) {
				    $fx = $fdim[0];
				    $fy = $fdim[1];
			    }
		    }

		    $bx = 0;
		    $by = 0;
		    if ($blm) {
			    $bsize = @filesize($bname);
			    $bdim = @getimagesize($bname);
			    $btstamp = $blm;
			    if ( $bdim ) {
				    $bx = $bdim[0];
				    $by = $bdim[1];
			    }
		    }
	    }

	    if ( $update )
		    $filedata .= "$fx||$fy||$bx||$by||$fsize||$bsize||$id||$title||$sorttitle||$desc||$collection||$fid||$fthumb||$bthumb||$flm||$blm||$locked\n";

    }
    if ($ii_verbose)
	    echo $lang['IMAGEDATA']['CHECKED'], $cnt, $eoln;

    $db->sql_freeresult($result);

    $file = $forumuser . '_imageinfo.txt';
    //$debugimageuploads = 1;
    if ($debugimageuploads) { $iii=fopen($file,"w");fwrite($iii,$filedata);fclose($iii);}
    // define HTTP POST DATA
    $data = "--$boundary\n".
            "Content-Disposition: form-data; name=\"file\"; filename=\"$file\"\n".
            "Content-Type: application/octet-stream\n\n$filedata\n".
            "--$boundary\n".
            "Content-Disposition: form-data; name=\"user\"\n".
	        "\n$forumuser\n".
            "--$boundary--\r\n\r\n";

    $result = senddata($data, $remote_page, $boundary);
    if (!$inbrowser)
	    $result = preg_replace('/<br *\/?>/i', "\n", $result);
    echo "$result$eoln";

    $time = microtime_float() - $time_start;
    printf ($lang['IMAGEDATA']['DONE'] . " ... " . $lang['IMAGEDATA']['TOOK'] . " %2.2f " . $lang['IMAGEDATA']['SECONDS'] . "$eoln", $time);
    flush();
    @ob_flush();
}
?>
