<?php
/*  $Id$    */

header('Content-Type: text/html; charset="windows-1252";');

function print_param($param, $processed) {
global $debugskin;
    if (!$debugskin)
        return;
    $out = '';
    foreach ($param as $k => $v)
        if (!in_array($k, $processed)) $out .= "$param[NAME]: $k=\"$v\" ";
    if ($out != '')
        echo "$out<br>\n";
}

function ProcessAParameter($param, $whichone, $type, $default) {
    if (!isset($param[$whichone]))
        return($default);
    if ($type == 'bool') {
        $param[$whichone] = trim($param[$whichone]);
        $retval = false;
        if (strcasecmp($param[$whichone], 'yes') == 0 ||
            strcasecmp($param[$whichone], 'true') == 0 ||
            strcasecmp($param[$whichone], 'y') == 0 ||
            strcasecmp($param[$whichone], 't') == 0)
            $retval = true;
    }
    else if ($type == 'int') {
        $retval = trim($param[$whichone]);
        if (!is_numeric($retval))
            $retval = $default;
    }
    else {
        $retval = $param[$whichone];
    }
    return($retval);
}

function JSDate($val, $default, $timealso=false) {
    if ($val === NULL || $val == '')
        return($default);
    $extra = '';
    if ($timealso)
        $extra = ',G,i,s';
    $tmp = date('n', $val);
    return(date('Y,' . ($tmp-1) . ',j' . $extra, $val));
}

function JSTrueFalse($val) {
    return($val?'true':'false');
}

function header_vars($param) {
global $db, $skinfile, $dvd, $lang, $dbname, $table_prefix, $DVD_PROPERTIES_TABLE, $locks, $alang_translation;
global $VersionNum, $dbhost, $img_physpath, $img_webpath, $img_webpathf, $img_webpathb, $imagecachedir, $PHP_SELF;
global $aformat_name, $newachan_name, $headcast, $headcrew, $castsubs, $crewsubs, $thumbnails;
$lang_en_WISHNAME[0] = '';
$lang_en_WISHNAME[1] = 'Vague interest';
$lang_en_WISHNAME[2] = 'Like to have it';
$lang_en_WISHNAME[3] = 'Want it';
$lang_en_WISHNAME[4] = 'Really want it';
$lang_en_WISHNAME[5] = 'Need it';
$lang_en_WISHNAME[6] = 'Not Set';

    $includecrew = ProcessAParameter($param, 'INCLUDECREW', 'bool', false);
    $includecast = ProcessAParameter($param, 'INCLUDECAST', 'bool', false);
    $comments = ProcessAParameter($param, 'COMMENTS', 'bool', false);
    $language = ProcessAParameter($param, 'LANGUAGE', 'string', '');
    print_param($param, array('NAME','INCLUDECREW','INCLUDECAST','COMMENTS','LANGUAGE'));

    $base = substr($PHP_SELF, 0, strrpos($PHP_SELF, '/')+1);
    $skinname =  preg_replace('/\.htm[l]$/i', '', $skinfile);
    $ov = str_replace(array("\n",'"'), array('\r\n','\\"'), $dvd['overview']);
    $locale = substr(strstr($dvd['id'], '.'), 1, 2);
    if (!$locale) $locale = '0';
    $result = $db->sql_query("SELECT value FROM $DVD_PROPERTIES_TABLE WHERE property='Rating~$locale~$dvd[ratingsystem]~$dvd[rating]'") or die($db->sql_error());
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    $RatingDescription = $row['value'];
    unset($row);
    $locale = locality(array());
    list($vmaj, $vmin, $vpatch, $vbuild) = explode('.', $VersionNum);

    $retval = '';
    if ($comments && ($includecrew || $includecast)) $retval .= "\r\n";     // These two comments should always be emitted, but aren't
    if ($comments && ($includecrew || $includecast)) $retval .= "// Object Types\r\n";
    if ($includecast) {
        $retval .="\tfunction DP_CastEntry(id, firstName, middleName, lastName, role, voice, uncredited, creditedAs, hasImage, birthYear)\r\n";
        $retval .="\t{\r\n";
        $retval .="\t\tthis.id = id;\r\n";
        $retval .="\t\tthis.firstName = firstName;\r\n";
        $retval .="\t\tthis.middleName = middleName;\r\n";
        $retval .="\t\tthis.lastName = lastName;\r\n";
        $retval .="\t\tthis.role = role;\r\n";
        $retval .="\t\tthis.voice = voice;\r\n";
        $retval .="\t\tthis.uncredited = uncredited;\r\n";
        $retval .="\t\tthis.creditedAs = creditedAs;\r\n";
        $retval .="\t\tthis.hasImage = hasImage;\r\n";
        $retval .="\t\tthis.birthYear = birthYear;\r\n";
        $retval .="\t}\r\n";
    }
    if ($includecrew) {
        $retval .="\tfunction DP_CrewEntry(id, firstName, middleName, lastName, creditType, creditSubtype, creditedAs, creditType_Translated, creditSubtype_Translated, customRole, hasImage, birthYear)\r\n";
        $retval .="\t{\r\n";
        $retval .="\t\tthis.id = id;\r\n";
        $retval .="\t\tthis.firstName = firstName;\r\n";
        $retval .="\t\tthis.middleName = middleName;\r\n";
        $retval .="\t\tthis.lastName = lastName;\r\n";
        $retval .="\t\tthis.creditType = creditType;\r\n";
        $retval .="\t\tthis.creditSubtype = creditSubtype;\r\n";
        $retval .="\t\tthis.creditedAs = creditedAs;\r\n";
        $retval .="\t\tthis.creditType_Translated = creditType_Translated;\r\n";
        $retval .="\t\tthis.creditSubtype_Translated = creditSubtype_Translated;\r\n";
        $retval .="\t\tthis.customRole = customRole;\r\n";
        $retval .="\t\tthis.hasImage = hasImage;\r\n";
        $retval .="\t\tthis.birthYear = birthYear;\r\n";
        $retval .="\t}\r\n";
    }
    $retval .= "\tfunction DP_AudioTrack(audioContents, audioFormat, audioChannels, audioContents_Translated, audioFormat_Translated, audioChannels_Translated)\r\n";
    $retval .= "\t{\r\n";
    $retval .= "\t\tthis.audioContents = audioContents;\r\n";
    $retval .= "\t\tthis.audioFormat = audioFormat;\r\n";
    $retval .= "\t\tthis.audioChannels = audioChannels;\r\n";
    $retval .= "\t\tthis.audioContents_Translated = audioContents_Translated;\r\n";
    $retval .= "\t\tthis.audioFormat_Translated = audioFormat_Translated;\r\n";
    $retval .= "\t\tthis.audioChannels_Translated = audioChannels_Translated;\r\n";
    $retval .= "\t}\r\n";
    $retval .= "\tfunction DP_Disc(descriptionSideA, descriptionSideB, discIDSideA, discIDSideB, labelSideA, labelSideB, dualLayeredSideA, dualLayeredSideB, location, slot)\r\n";
    $retval .= "\t{\r\n";
    $retval .= "\t\tthis.descriptionSideA = descriptionSideA;\r\n";
    $retval .= "\t\tthis.descriptionSideB = descriptionSideB;\r\n";
    $retval .= "\t\tthis.discIDSideA = discIDSideA;\r\n";
    $retval .= "\t\tthis.discIDSideB = discIDSideB;\r\n";
    $retval .= "\t\tthis.labelSideA = labelSideA;\r\n";
    $retval .= "\t\tthis.labelSideB = labelSideB;\r\n";
    $retval .= "\t\tthis.dualLayeredSideA = dualLayeredSideA;\r\n";
    $retval .= "\t\tthis.dualLayeredSideB = dualLayeredSideB;\r\n";
    $retval .= "\t\tthis.location = location;\r\n";
    $retval .= "\t\tthis.slot = slot;\r\n";
    $retval .= "\t}\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// System Information\r\n";
    $retval .= "\tvar DP_ProgramVersionMajor = $vmaj;\r\n";
    $retval .= "\tvar DP_ProgramVersionMinor = $vmin;\r\n";
    $retval .= "\tvar DP_ProgramVersionRelease = $vpatch;\r\n";
    $retval .= "\tvar DP_ProgramVersionBuild = $vbuild;\r\n";
    $retval .= "\tvar DP_ProgramVersionIsBeta = " . JSTrueFalse(false) . ";\r\n";
    $retval .= "\tvar DP_ProgramLayoutName = \"$skinname\";\r\n";
    $retval .= "\tvar DP_ProgramDatabaseName = \"$dbname.$table_prefix\";\r\n";
    $retval .= "\tvar DP_ProgramPathDatabase = \"$dbhost\";\r\n";
    $retval .= "\tvar DP_ProgramPathImages = \"$base$img_webpath\";\r\n";
    $retval .= "\tvar DP_ProgramPathThumbnails = \"$base{$img_webpath}$thumbnails/\";\r\n";
    $retval .= "\tvar DP_ProgramPathLayouts = \"{$base}skins/\";\r\n";
    $retval .= "\tvar DP_ProgramPathReports = \"$base\";\r\n";
    $retval .= "\tvar DP_ProgramPathTemp = \"$base$imagecachedir\";\r\n";
    $retval .= "\tvar DP_ProgramPathProgram = \"$base\";\r\n";
    $retval .= "\tvar DP_IsComparing = " . JSTrueFalse(false) . ";\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// General Information\r\n";
    $retval .= "\tvar DP_UPC = \"$dvd[id]\";\r\n";
    $retval .= "\tvar DP_Locality = \"$locale\";\r\n";
    if ($dvd['builtinmediatype'] == MEDIA_TYPE_DVD || $dvd['builtinmediatype'] == MEDIA_TYPE_HDDVD_DVD) {
        $retval .= "\tvar DP_RegionAll = " . JSTrueFalse(strpos($dvd['region'], '0') !== false) .";\r\n";
        $retval .= "\tvar DP_Region1 = " . JSTrueFalse(strpos($dvd['region'], '1') !== false) .";\r\n";
        $retval .= "\tvar DP_Region2 = " . JSTrueFalse(strpos($dvd['region'], '2') !== false) .";\r\n";
        $retval .= "\tvar DP_Region3 = " . JSTrueFalse(strpos($dvd['region'], '3') !== false) .";\r\n";
        $retval .= "\tvar DP_Region4 = " . JSTrueFalse(strpos($dvd['region'], '4') !== false) .";\r\n";
        $retval .= "\tvar DP_Region5 = " . JSTrueFalse(strpos($dvd['region'], '5') !== false) .";\r\n";
        $retval .= "\tvar DP_Region6 = " . JSTrueFalse(strpos($dvd['region'], '6') !== false) .";\r\n";
    }
    if ($dvd['builtinmediatype'] == MEDIA_TYPE_BLURAY || $dvd['builtinmediatype'] == MEDIA_TYPE_BLURAY_DVD) {
        $retval .= "\tvar DP_BDRegionAll = " . JSTrueFalse(strpos($dvd['region'], '@') !== false) .";\r\n";
        $retval .= "\tvar DP_BDRegionA = " . JSTrueFalse(strpos($dvd['region'], 'A') !== false) .";\r\n";
        $retval .= "\tvar DP_BDRegionB = " . JSTrueFalse(strpos($dvd['region'], 'B') !== false) .";\r\n";
        $retval .= "\tvar DP_BDRegionC = " . JSTrueFalse(strpos($dvd['region'], 'C') !== false) .";\r\n";
    }
    if (file_exists("$img_physpath$dvd[id]f.jpg"))
        $retval .= "\tvar DP_ImageFileFront = \"$base$img_webpathf$dvd[id]f.jpg\";\r\n";
    if (file_exists("$img_physpath$dvd[id]b.jpg"))
        $retval .= "\tvar DP_ImageFileBack = \"$base$img_webpathb$dvd[id]b.jpg\";\r\n";
    $retval .= "\tvar DP_Title = \"$dvd[title]\";\r\n";
    $retval .= "\tvar DP_RatingSystem = \"$dvd[ratingsystem]\";\r\n";
    $retval .= "\tvar DP_Rating = \"$dvd[rating]\";\r\n";
    $retval .= "\tvar DP_RatingAge = $dvd[ratingage];\r\n";
    $retval .= "\tvar DP_RatingDescription = \"$RatingDescription\";\r\n";
    $retval .= "\tvar DP_RatingDetails = \"$dvd[ratingdetails]\";\r\n";
    $retval .= "\tvar DP_OriginalTitle = \"$dvd[originaltitle]\";\r\n";
    $retval .= "\tvar DP_Edition = \"$dvd[description]\";\r\n";
    $d = ($dvd['builtinmediatype'] == MEDIA_TYPE_DVD || $dvd['builtinmediatype'] == MEDIA_TYPE_HDDVD_DVD || $dvd['builtinmediatype'] == MEDIA_TYPE_BLURAY_DVD);
    $h = ($dvd['builtinmediatype'] == MEDIA_TYPE_HDDVD_DVD || $dvd['builtinmediatype'] == MEDIA_TYPE_HDDVD);
    $b = ($dvd['builtinmediatype'] == MEDIA_TYPE_BLURAY_DVD || $dvd['builtinmediatype'] == MEDIA_TYPE_BLURAY);
    $retval .= "\tvar DP_MediaTypeDVD = " . JSTrueFalse($d) . ";\r\n";
    $retval .= "\tvar DP_MediaTypeHDDVD = " . JSTrueFalse($h) . ";\r\n";
    $retval .= "\tvar DP_MediaTypeBluRay = " . JSTrueFalse($b) . ";\r\n";
    if ($dvd['custommediatype'] != '')$retval .= "\tvar DP_CustomMediaType = \"$dvd[custommediatype]\";\r\n";
    $retval .= "\tvar DP_ProductionYear = $dvd[productionyear];\r\n";
    $retval .= "\tvar DP_CountryOfOrigin = \"$dvd[countryoforigin]\";\r\n";
    $retval .= "\tvar DP_CountryOfOrigin2 = \"$dvd[countryoforigin2]\";\r\n";
    $retval .= "\tvar DP_CountryOfOrigin3 = \"$dvd[countryoforigin3]\";\r\n";
    $retval .= "\tvar DP_RunTime = $dvd[runningtime];\r\n";
    $retval .= "\tvar DP_CaseType = \"$dvd[casetype]\";\r\n";
    $retval .= "\tvar DP_CaseSlipCover = " . JSTrueFalse($dvd['caseslipcover']) . ";\r\n";
    $retval .= "\tvar DP_CaseType_Translated = \"" . $lang[strtoupper(str_replace(' ', '', $dvd['casetype']))] . "\";\r\n";
    $retval .= "\tvar DP_Front_Banner_Setting = \"" . (($dvd['mediabannerfront'] != 0)?"on": "off") . "\";\r\n";
    $retval .= "\tvar DP_Back_Banner_Setting = \"" . (($dvd['mediabannerback'] != 0)?"on": "off") . "\";\r\n";
    $retval .= "\tvar DP_ReleaseDate = new Date(" . JSDate($dvd['released'], '1899,11,30') . ");\r\n";
    $retval .= "\tvar DP_SRP = \"$dvd[srp]\";\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// Video Formats\r\n";
    $retval .= "\tvar DP_VideoStandardNTSC = " . JSTrueFalse($dvd['formatvideostandard']=='NTSC') . ";\r\n";
    $retval .= "\tvar DP_VideoStandardPAL = " . JSTrueFalse($dvd['formatvideostandard']=='PAL') . ";\r\n";
    $retval .= "\tvar DP_VideoFormatWidescreen = " . JSTrueFalse($dvd['formatletterbox']) . ";\r\n";
    $retval .= "\tvar DP_VideoFormatPanScan = " . JSTrueFalse($dvd['formatpanandscan']) . ";\r\n";
    $retval .= "\tvar DP_VideoFormatFullFrame = " . JSTrueFalse($dvd['formatfullframe']) . ";\r\n";
    $retval .= "\tvar DP_VideoFormatAnamorphic = " . JSTrueFalse($dvd['format16x9']) . ";\r\n";
    $retval .= "\tvar DP_AspectRatio = $dvd[formataspectratio];\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// Features\r\n";
    $retval .= "\tvar DP_FeatureSceneAccess = " . JSTrueFalse($dvd['featuresceneaccess']) . ";\r\n";
    $retval .= "\tvar DP_FeaturePlayAll = " . JSTrueFalse($dvd['featureplayall']) . ";\r\n";
    $retval .= "\tvar DP_FeatureTrailers = " . JSTrueFalse($dvd['featuretrailer']) . ";\r\n";
    $retval .= "\tvar DP_FeatureBonusTrailers = " . JSTrueFalse($dvd['featurebonustrailers']) . ";\r\n";
    $retval .= "\tvar DP_FeatureFeaturette = " . JSTrueFalse($dvd['featuremakingof']) . ";\r\n";
    $retval .= "\tvar DP_FeatureCommentary = " . JSTrueFalse($dvd['featurecommentary']) . ";\r\n";
    $retval .= "\tvar DP_FeatureDeletedScenes = " . JSTrueFalse($dvd['featuredeletedscenes']) . ";\r\n";
    $retval .= "\tvar DP_FeatureInterviews = " . JSTrueFalse($dvd['featureinterviews']) . ";\r\n";
    $retval .= "\tvar DP_FeatureBloopers = " . JSTrueFalse($dvd['featureouttakes']) . ";\r\n";
    $retval .= "\tvar DP_FeatureStoryboardComparisons = " . JSTrueFalse($dvd['featurestoryboardcomparisons']) . ";\r\n";
    $retval .= "\tvar DP_FeatureGallery = " . JSTrueFalse($dvd['featurephotogallery']) . ";\r\n";
    $retval .= "\tvar DP_FeatureProductionNotes = " . JSTrueFalse($dvd['featureproductionnotes']) . ";\r\n";
    $retval .= "\tvar DP_FeatureDVDROMContent = " . JSTrueFalse($dvd['featuredvdromcontent']) . ";\r\n";
    $retval .= "\tvar DP_FeatureInteractiveGame = " . JSTrueFalse($dvd['featuregame']) . ";\r\n";
    $retval .= "\tvar DP_FeatureMultiAngle = " . JSTrueFalse($dvd['featuremultiangle']) . ";\r\n";
    $retval .= "\tvar DP_FeatureMusicVideos = " . JSTrueFalse($dvd['featuremusicvideos']) . ";\r\n";
    $retval .= "\tvar DP_FeatureTHXCertified = " . JSTrueFalse($dvd['featurethxcertified']) . ";\r\n";
    $retval .= "\tvar DP_FeatureClosedCaptioned = " . JSTrueFalse($dvd['featureclosedcaptioned']) . ";\r\n";
    $retval .= "\tvar DP_FeatureDigitalCopy = " . JSTrueFalse($dvd['featuredigitalcopy']) . ";\r\n";
    $retval .= "\tvar DP_FeaturePIP = " . JSTrueFalse($dvd['featurepip']) . ";\r\n";
    $retval .= "\tvar DP_FeatureBDLive = " . JSTrueFalse($dvd['featurebdlive']) . ";\r\n";
    $retval .= "\tvar DP_FeatureDBOX = " . JSTrueFalse($dvd['featuredbox']) . ";\r\n";
    $retval .= "\tvar DP_FeatureCineChat = " . JSTrueFalse($dvd['featurecinechat']) . ";\r\n";
    $retval .= "\tvar DP_FeatureMovieIQ = " . JSTrueFalse($dvd['featuremovieiq']) . ";\r\n";
    $retval .= "\tvar DP_FeatureOther = \"" . str_replace(array("\n",'"'), array('<BR>','\\"'), $dvd['featureother']). "\";\r\n"; // Line break fix.
    $retval .= "\tvar DP_Overview = \"" . str_replace(array("\n",'"'), array('\r\n','\\"'), $dvd['overview']) . "\";\r\n";
    $retval .= "\tvar DP_EasterEggs = \"" . str_replace(array("\n",'"'), array('\r\n','\\"'), $dvd['eastereggs']) . "\";\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// Personal Information\r\n";
    $retval .= "\tvar DP_CollectionTypeOwned = " . JSTrueFalse($dvd['collectiontype']=='owned') . ";\r\n";
    $retval .= "\tvar DP_CollectionTypeOrdered = " . JSTrueFalse($dvd['collectiontype']=='ordered') . ";\r\n";
    $retval .= "\tvar DP_CollectionTypeWishList = " . JSTrueFalse($dvd['collectiontype']=='wishlist') . ";\r\n";
    $rct = strtolower($dvd['realcollectiontype']);
    $iscust = ($rct != 'owned' && $rct != 'ordered' && $rct != 'wish list');
    $retval .= "\tvar DP_CollectionTypeCustom = " . JSTrueFalse($iscust) . ";\r\n";
    if ($iscust) $retval .= "\tvar DP_CustomCollectionTypeName = \"" . addslashes($dvd['realcollectiontype']) . "\";\r\n";
    $retval .= "\tvar DP_CollectionNumber = \"$dvd[collectionnumber]\";\r\n";
    $retval .= "\tvar DP_CountAs = $dvd[countas];\r\n";
    $retval .= "\tvar DP_SortTitle = \"$dvd[sorttitle]\";\r\n";
    $retval .= "\tvar DP_WishPriority = \"" . $lang_en_WISHNAME[$dvd['wishpriority']] . "\";\r\n";
    $retval .= "\tvar DP_WishPriority_Translated = \"" . $lang["WISHNAME$dvd[wishpriority]"] . "\";\r\n";
    $retval .= "\tvar DP_PurchaseDate = new Date(" . JSDate($dvd['purchasedate'], '1899,11,30') . ");\r\n";
    $retval .= "\tvar DP_PurchasePlace = \"$dvd[purchaseplace]\";\r\n";
    $retval .= "\tvar DP_PurchasePrice = \"$dvd[purchaseprice]\";\r\n";
    $retval .= "\tvar DP_LastEdited = new Date(" . JSDate($dvd['lastedited'], '1899,11,30,1,0,0', true) . ");\r\n";
    $retval .= "\tvar DP_LastWatched = new Date(" . JSDate($dvd['lastwatched'], '1899,11,30') . ");\r\n";
    $retval .= "\tvar DP_LoanedTo = \"$dvd[loaninfo]\";\r\n";
    $retval .= "\tvar DP_LoanDue = new Date(" . JSDate($dvd['loandue'], '1899,11,30') . ");\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// Review\r\n";
    $retval .= "\tvar DP_ReviewFilm = $dvd[reviewfilm];\r\n";
    $retval .= "\tvar DP_ReviewVideo = $dvd[reviewvideo];\r\n";
    $retval .= "\tvar DP_ReviewAudio = $dvd[reviewaudio];\r\n";
    $retval .= "\tvar DP_ReviewExtras = $dvd[reviewextras];\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// Locks\r\n";
    $retval .= "\tvar DP_LockEntire = " . JSTrueFalse($locks['entire']) . ";\r\n";
    $retval .= "\tvar DP_LockAudioTracks = " . JSTrueFalse($locks['audio']) . ";\r\n";
    $retval .= "\tvar DP_LockBoxSetContents = " . JSTrueFalse(false) . ";\r\n"; // This isn't user visible in the UI, haven't determined what would set it true ...
    $retval .= "\tvar DP_LockCaseType = " . JSTrueFalse($locks['casetype']) . ";\r\n";
    $retval .= "\tvar DP_LockCast = " . JSTrueFalse($locks['cast']) . ";\r\n";
    $retval .= "\tvar DP_LockCrew = " . JSTrueFalse($locks['crew']) . ";\r\n";
    $retval .= "\tvar DP_LockDiscInformation = " . JSTrueFalse($locks['discinfo']) . ";\r\n";
    $retval .= "\tvar DP_LockEasterEggs = " . JSTrueFalse($locks['eastereggs']) . ";\r\n";
    $retval .= "\tvar DP_LockFeatures = " . JSTrueFalse($locks['features']) . ";\r\n";
    $retval .= "\tvar DP_LockGenres = " . JSTrueFalse($locks['genres']) . ";\r\n";
    $retval .= "\tvar DP_LockOverview = " . JSTrueFalse($locks['overview']) . ";\r\n";
    $retval .= "\tvar DP_LockProductionYear = " . JSTrueFalse($locks['productionyear']) . ";\r\n";
    $retval .= "\tvar DP_LockRating = " . JSTrueFalse($locks['rating']) . ";\r\n";
    $retval .= "\tvar DP_LockRegions = " . JSTrueFalse($locks['regions']) . ";\r\n";
    $retval .= "\tvar DP_LockReleaseDate = " . JSTrueFalse($locks['releasedate']) . ";\r\n";
    $retval .= "\tvar DP_LockRunTime = " . JSTrueFalse($locks['runningtime']) . ";\r\n";
    $retval .= "\tvar DP_LockScans = " . JSTrueFalse($locks['covers']) . ";\r\n";
    $retval .= "\tvar DP_LockSRP = " . JSTrueFalse($locks['srp']) . ";\r\n";
    $retval .= "\tvar DP_LockSubtitles = " . JSTrueFalse($locks['subtitles']) . ";\r\n";
    $retval .= "\tvar DP_LockStudios = " . JSTrueFalse($locks['studios']) . ";\r\n";
    $retval .= "\tvar DP_LockTitle = " . JSTrueFalse($locks['title']) . ";\r\n";
    $retval .= "\tvar DP_LockVideoFormats = " . JSTrueFalse($locks['videoformats']) . ";\r\n";
    $retval .= "\tvar DP_LockMediaType = " . JSTrueFalse($locks['mediatype']) . ";\r\n";
    $retval .= "\tvar DP_Notes = \"" . str_replace(array("\n",'"'), array('\r\n','\\"'), $dvd['o_notes']) . "\";\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// Temporary Data\r\n";
    $retval .= "\tvar DP_IsFlagged = false;\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// Array Data\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// Genres\r\n";
    $retval .= "\tvar DP_Genres = new Array(5);\r\n";
    $retval .= "\tDP_Genres[0] = \"" . (isset($dvd['genres'][0])?$dvd['genres'][0]:'') . "\";\r\n";
    $retval .= "\tDP_Genres[1] = \"" . (isset($dvd['genres'][1])?$dvd['genres'][1]:'') . "\";\r\n";
    $retval .= "\tDP_Genres[2] = \"" . (isset($dvd['genres'][2])?$dvd['genres'][2]:'') . "\";\r\n";
    $retval .= "\tDP_Genres[3] = \"" . (isset($dvd['genres'][3])?$dvd['genres'][3]:'') . "\";\r\n";
    $retval .= "\tDP_Genres[4] = \"" . (isset($dvd['genres'][4])?$dvd['genres'][4]:'') . "\";\r\n";
    $retval .= "\tvar DP_Genres_Translated = new Array(5);\r\n";
    $retval .= "\tDP_Genres_Translated[0] = \"" . (isset($dvd['genres'][0])?GenreTranslation($dvd['genres'][0]):'') . "\";\r\n";
    $retval .= "\tDP_Genres_Translated[1] = \"" . (isset($dvd['genres'][1])?GenreTranslation($dvd['genres'][1]):'') . "\";\r\n";
    $retval .= "\tDP_Genres_Translated[2] = \"" . (isset($dvd['genres'][2])?GenreTranslation($dvd['genres'][2]):'') . "\";\r\n";
    $retval .= "\tDP_Genres_Translated[3] = \"" . (isset($dvd['genres'][3])?GenreTranslation($dvd['genres'][3]):'') . "\";\r\n";
    $retval .= "\tDP_Genres_Translated[4] = \"" . (isset($dvd['genres'][4])?GenreTranslation($dvd['genres'][4]):'') . "\";\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// Studios\r\n";
    $retval .= "\tvar DP_Studios = new Array(3);\r\n";
    $retval .= "\tDP_Studios[0] = \"" . (isset($dvd['studios'][0])?$dvd['studios'][0][1]:'') . "\";\r\n";
    $retval .= "\tDP_Studios[1] = \"" . (isset($dvd['studios'][1])?$dvd['studios'][1][1]:'') . "\";\r\n";
    $retval .= "\tDP_Studios[2] = \"" . (isset($dvd['studios'][2])?$dvd['studios'][2][1]:'') . "\";\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// MediaCompanies\r\n";
    $retval .= "\tvar DP_MediaCompanies = new Array(3);\r\n";
    $retval .= "\tDP_MediaCompanies[0] = \"" . (isset($dvd['mediacompanies'][0])?$dvd['mediacompanies'][0][1]:'') . "\";\r\n";
    $retval .= "\tDP_MediaCompanies[1] = \"" . (isset($dvd['mediacompanies'][1])?$dvd['mediacompanies'][1][1]:'') . "\";\r\n";
    $retval .= "\tDP_MediaCompanies[2] = \"" . (isset($dvd['mediacompanies'][2])?$dvd['mediacompanies'][2][1]:'') . "\";\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// Subtitles\r\n";
    $tmp = count($dvd['subtitles']);
    $retval .= "\tvar DP_Subtitles = new Array($tmp);\r\n";
    for ($i=0; $i<$tmp; $i++)
        $retval .= "\tDP_Subtitles[$i] = \"" . $dvd['subtitles'][$i] . "\";\r\n";
    $retval .= "\tvar DP_Subtitles_Translated = new Array($tmp);\r\n";
    for ($i=0; $i<$tmp; $i++)
        $retval .= "\tDP_Subtitles_Translated[$i] = \"" . substr($alang_translation[$dvd['subtitles'][$i]], strpos($alang_translation[$dvd['subtitles'][$i]], '/>')+3) . "\";\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// Audio Tracks\r\n";
    $tmp = count($dvd['audio']);
    $retval .= "\tvar DP_AudioTracks = new Array($tmp);\r\n";
    for ($i=0; $i<$tmp; $i++)
        $retval .= "\tDP_AudioTracks[$i] = new DP_AudioTrack(\""
            . $dvd['audio'][$i]['audiocontent'] . "\", \""
            . $dvd['audio'][$i]['audioformat'] . "\", \""
            . $dvd['audio'][$i]['audiochannels'] . "\", \""
            . substr($alang_translation[$dvd['audio'][$i]['audiocontent']], strpos($alang_translation[$dvd['audio'][$i]['audiocontent']], '/>')+3) . "\", \""
            . $aformat_name[$dvd['audio'][$i]['audioformat']] . "\", \""
            . $newachan_name[$dvd['audio'][$i]['audiochannels']] . "\");\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// Discs\r\n";
    $tmp = count($dvd['discs']);
    $retval .= "\tvar DP_Discs = new Array($tmp);\r\n";
    for ($i=0; $i<$tmp; $i++)
        $retval .= "\tDP_Discs[$i] = new DP_Disc(\"" . $dvd['discs'][$i]['discdescsidea'] . '", "' . $dvd['discs'][$i]['discdescsideb']
                        . '", "' . $dvd['discs'][$i]['discidsidea'] . '", "' . $dvd['discs'][$i]['discidsideb']
                        . '", "' . $dvd['discs'][$i]['labelsidea'] . '", "' . $dvd['discs'][$i]['labelsideb']
                        . '", ' . JSTrueFalse($dvd['discs'][$i]['duallayeredsidea']) . ', ' . JSTrueFalse($dvd['discs'][$i]['duallayeredsideb'])
                        . ', "' . $dvd['discs'][$i]['location'] . '", "' . $dvd['discs'][$i]['slot'] . "\");\r\n";
    if ($comments) $retval .= "\r\n";
    if ($comments) $retval .= "// Tags\r\n";
    $tmp = count($dvd['tags']);
    $retval .= "\tvar DP_Tags = new Array($tmp);\r\n";
    for ($i=0; $i<$tmp; $i++)
        $retval .= "\tDP_Tags[$i] = \"" . $dvd['tags'][$i]['name'] . "\";\r\n";
    if ($includecast) {
        if ($comments) $retval .= "\r\n";
        if ($comments) $retval .= "// Cast - NOTE: Dividers are cast entries with no name; creditedAs is the caption\r\n";
        $tmp = count($dvd['actors']);
        $retval .= "\tvar DP_CastEntries = new Array($tmp);\r\n";
        for ($i=0; $i<$tmp; $i++)
            $retval .= "\tDP_CastEntries[$i] = new DP_CastEntry(" . $dvd['actors'][$i]['caid'] . ', "' . $dvd['actors'][$i]['firstname']
                                . '", "' . $dvd['actors'][$i]['middlename'] . '", "' . $dvd['actors'][$i]['lastname']
                                . '", "' . $dvd['actors'][$i]['role'] . '", ' . JSTrueFalse($dvd['actors'][$i]['voice'])
                                . ', ' . JSTrueFalse($dvd['actors'][$i]['uncredited']) . ', "' . $dvd['actors'][$i]['creditedas']
                                . '", ' . JSTrueFalse(HeadImage($dvd['actors'][$i], $headcast, $castsubs, $ignore, $dvd['id'])!='') . ', ' . $dvd['actors'][$i]['birthyear'] . ");\r\n";
    }
    if ($includecrew) {
        if ($comments) $retval .= "\r\n";
        if ($comments) $retval .= "// Crew - NOTE: Dividers are crew entries with no name; creditedAs is the caption\r\n";
        $tmp = count($dvd['credits']);
        $retval .= "\tvar DP_CrewEntries = new Array($tmp);\r\n";
        for ($i=0; $i<$tmp; $i++)
            $retval .= "\tDP_CrewEntries[$i] = new DP_CrewEntry(" . $dvd['credits'][$i]['caid'] . ', "' . $dvd['credits'][$i]['firstname']
                                . '", "' . $dvd['credits'][$i]['middlename'] . '", "' . $dvd['credits'][$i]['lastname']
                                . '", "' . $dvd['credits'][$i]['credittype'] . '", "' . $dvd['credits'][$i]['creditsubtype']
                                . '", "' . $dvd['credits'][$i]['creditedas'] . '", "' . $lang[strtoupper(str_replace(' ', '', $dvd['credits'][$i]['credittype']))]
                                . '", "' . $lang[strtoupper(str_replace(' ', '', $dvd['credits'][$i]['creditsubtype']))] . '", "' . $dvd['credits'][$i]['customrole']
                                . '", ' . JSTrueFalse(HeadImage($dvd['credits'][$i], $headcrew, $crewsubs, $ignore, $dvd['id'])!='') . ', ' . $dvd['credits'][$i]['birthyear'] . ");\r\n";
    }

    return($retval);
}
function title($param) {
global $dvd, $titledesc;
    $showdesc = ProcessAParameter($param, 'SHOWDESC', 'bool', true);
    $showtitle = ProcessAParameter($param, 'SHOWTITLE', 'bool', true);
    print_param($param, array('NAME','SHOWDESC','SHOWTITLE'));

    $ret = '';
    if ($showtitle && $showdesc) {
        $ret = $dvd['title'];
        if ($dvd['description'] != '') {
            switch ($titledesc) {
            case 1:
                $ret .= " ($dvd[description])";
                break;
            case 2:
                $ret .= ": $dvd[description]";
                break;
            case 3:
                $ret .= " - $dvd[description]";
                break;
            }
        }
    }
    else if ($showtitle) {
        $ret = $dvd['title'];
    }
    else if ($showdesc) {
        $ret = $dvd['description'];
    }
    return($ret);
}
function original_title($param) {
global $dvd, $titledesc;
    $usetitleifblank = ProcessAParameter($param, 'USETITLEIFBLANK', 'bool', true);
    $showdesc = ProcessAParameter($param, 'SHOWDESC', 'bool', true);
    print_param($param,array('NAME','USETITLEIFBLANK','SHOWDESC'));

    $ret = $dvd['originaltitle'];
    if ($ret == '' && $usetitleifblank)
        $ret = $dvd['title'];
    if ($ret != '' && $showdesc) {
        if ($dvd['description'] != '') {
            switch ($titledesc) {
            case 1:
                $ret .= " ($dvd[description])";
                break;
            case 2:
                $ret .= ": $dvd[description]";
                break;
            case 3:
                $ret .= " - $dvd[description]";
                break;
            }
        }
    }
    return($ret);
}
function ownership($param) {
global $dvd, $lang;
    $ifowned = ProcessAParameter($param, 'IFOWNED', 'string', $lang['OWNED']);
    $ifordered = ProcessAParameter($param, 'IFORDERED', 'string', $lang['ORDERED']);
    $ifwishlist = ProcessAParameter($param, 'IFWISHLIST', 'string', $lang['WISHLIST']);
    print_param($param, array('NAME','IFOWNED','IFORDERED','IFWISHLIST'));

    $ret = '';
    switch ($dvd['collectiontype']) {
    case 'owned':
        $ret = $ifowned;
        break;
    case 'ordered':
        $ret = $ifordered;
        break;
    case 'wishlist':
        $ret = $ifwishlist;
        break;
    }
    return($ret);
}
function loandue($param) {
// DVDPro BUG - ifoverdue seems broken ... it displays if _not_ overdue
global $dvd, $lang;
    $ifoverdue = ProcessAParameter($param, 'IFOVERDUE', 'string', '');
    print_param($param, array('NAME','IFOVERDUE'));

    if ($dvd['loandue'] == 0)
        return('');

    $late = (($dvd['loandue'] - time()) < 0);
    if ($ifoverdue == '') {
        $ret = fix88595(ucwords(strftimeReplacement($lang['SKINDATEFORMAT'], $dvd['loandue'])));
    }
    else {
        if ($late)
            $ret = '';
        else
            $ret = $ifoverdue;
    }
    return($ret);
}
function loanedto($param) {
global $dvd;
    $ifloaned = ProcessAParameter($param, 'IFLOANED', 'string', '');
    $ifnotloaned = ProcessAParameter($param, 'IFNOTLOANED', 'string', '');
    print_param($param, array('NAME','IFLOANED','IFNOTLOANED'));

    if ($dvd['loaninfo'] == '') {
        $ret = $ifnotloaned;
    }
    else {
        $ret = $dvd['loaninfo'];
        if ($ifloaned != '')
            $ret = $ifloaned;
    }
    return($ret);
}
function sorttitle($param) {
global $dvd;
    print_param($param, array('NAME'));

    $ret = $dvd['sorttitle'];
    return($ret);
}
function review($param) {
global $dvd, $reviewgraph;
    $width = ProcessAParameter($param, 'WIDTH', 'int', 100);
    $height = ProcessAParameter($param, 'HEIGHT', 'int', 20);
    $bgcolor = ProcessAParameter($param, 'BGCOLOR', 'string', '');
    print_param($param, array('NAME','WIDTH','HEIGHT','BGCOLOR'));
    return(DrawAReviewGraph($dvd, $reviewgraph, $width, $height, $bgcolor));
}
function last_watched_on($param) {
global $dvd, $DVD_EVENTS_TABLE, $watched, $db, $lang;
    print_param($param, array('NAME'));

    $sql = "SELECT UNIX_TIMESTAMP(timestamp) AS ts FROM $DVD_EVENTS_TABLE WHERE id='$dvd[id]' AND eventtype='$watched' ORDER BY timestamp DESC LIMIT 1";
    $res = $db->sql_query($sql) or die($db->sql_error());
    $row = $db->sql_fetchrow($res);
    $t = $row['ts'];
    $db->sql_freeresult($res);
    $ret = '';
    if ($t != '')
        $ret = fix88595(ucwords(strftimeReplacement($lang['SKINDATEFORMAT'], $t)));
    return($ret);
}
function last_watched_by($param) {
global $dvd, $DVD_EVENTS_TABLE, $DVD_USERS_TABLE, $IsPrivate, $watched, $db, $lang;
    print_param($param, array('NAME'));

    $sql = "SELECT u.firstname,u.lastname,timestamp FROM $DVD_EVENTS_TABLE e, $DVD_USERS_TABLE u WHERE id='$dvd[id]' AND eventtype='$watched' AND e.uid=u.uid ORDER BY timestamp DESC";
    $res = $db->sql_query($sql) or die($db->sql_error());
    $ts = '';
    $ret = '';
    $divider = ", ";
    while ($row = $db->sql_fetchrow($res)) {
        if ($ts == '') $ts = $row['timestamp'];
        if ($row['timestamp'] != $ts)
            break;
        if ($ret != '') $ret .= $divider;
        $row['lastname'] = HideName($row['lastname']);
        $ret .= preg_replace('/\s\s+/', ' ', trim("$row[firstname] $row[lastname]"));
    }
    $db->sql_freeresult($res);
    return($ret);
}
function otherfeatures($param) {
global $dvd;
    print_param($param, array('NAME'));

    $ret = $dvd['featureother'];
    return($ret);
}
function program_location($param) {
global $dvd;
    print_param($param, array('NAME'));

    $ret = '.';
    return($ret);
}
function program_language($param) {
global $lang;
    print_param($param, array('NAME'));

    $ret = 'English';
    return($ret);
}
function program_build($param) {
global $dvd;
    print_param($param, array('NAME'));

    $ret = '915';
    return($ret);
}
function program_version($param) {
global $dvd;
    print_param($param, array('NAME'));

    $ret = "2.5.0";
    return($ret);
}
function discs($param) {
// DVDPro BUG - can't represent Not-DS + Flipper (Windows program should dis-allow)
global $dvd, $lang;
    $divider = ProcessAParameter($param, 'DIVIDER', 'string', ', ');
    if ($divider == 'BREAK') $divider = '<BR>';
    $subdivider = ProcessAParameter($param, 'SUBDIVIDER', 'string', ' - ');
    if ($subdivider == 'BREAK') $subdivider = '<BR>';
    $showdescriptions = ProcessAParameter($param, 'SHOWDESCRIPTIONS', 'bool', false);
    $showlocations = ProcessAParameter($param, 'SHOWLOCATIONS', 'bool', false);
    $showslots = ProcessAParameter($param, 'SHOWSLOTS', 'bool', false);
    $showlabels = ProcessAParameter($param, 'SHOWLABELS', 'bool', false);
    $showsides = ProcessAParameter($param, 'SHOWSIDES', 'bool', false);
    $showlayers = ProcessAParameter($param, 'SHOWLAYERS', 'bool', false);
    $showdiscids = ProcessAParameter($param, 'SHOWDISCIDS', 'bool', false);
    print_param($param, array('NAME','DIVIDER','SUBDIVIDER','SHOWDESCRIPTIONS','SHOWLOCATIONS','SHOWSLOTS','SHOWLABELS','SHOWSIDES','SHOWLAYERS','SHOWDISCIDS'));

    $ret = '';
    foreach ($dvd['discs'] as $k => $d) {
        $line = '';
        if ($showdescriptions) {
            if ($line != '') $line .= $subdivider;
            $line .= $d['discdescsidea'].$d['discdescsideb'];
        }
        if ($showlocations) {
            if ($line != '') $line .= $subdivider;
            $line .= $d['location'];
        }
        if ($showslots) {
            if ($line != '') $line .= $subdivider;
            $line .= $d['slot'];
        }
        if ($showdiscids) {
            if ($line != '') $line .= $subdivider;
            $line .= $d['discidsidea'];
            if ($d['discidsideb'] != '')
                $line .= ", $d[discidsideb]";
        }
        if ($showlabels) {
            if ($line != '') $line .= $subdivider;
            $line .= $d['labelsidea'];
            if ($d['labelsideb'] != '')
                $line .= ", $d[labelsideb]";
        }
        if ($showsides) {
            if ($line != '') $line .= $subdivider;
            $line .= (($d['dualsided']==1)?$lang['DUALSIDED']:$lang['SINGLESIDED']);
        }
        if ($showlayers) {
            if ($line != '') $line .= $subdivider;
            $line .= (($d['duallayeredsidea']==1)?$lang['DUALLAYERED']:$lang['SINGLELAYERED']);
        }
        if ($ret != '') $ret .= $divider;
        $ret .= $line;
    }
    return($ret);
}
function eastereggs($param) {
global $dvd;
    print_param($param, array('NAME'));

    $ret = $dvd['eastereggs'];
    return($ret);
}
function notes($param) {
global $dvd;
    print_param($param, array('NAME'));

    $ret = $dvd['notes'];
    return($ret);
}
function subtitles($param) {
global $dvd;
    $divider = ProcessAParameter($param, 'DIVIDER', 'string', ', ');
    if ($divider == 'BREAK') $divider = '<BR>';
    print_param($param, array('NAME','DIVIDER'));

    $ret = '';
    foreach ($dvd['subtitles'] as $k => $s)
        $ret .= $s.$divider;
    $ret = substr($ret, 0, -1*strlen($divider));
    return($ret);
}
function audiotracks($param) {
global $dvd, $achan_rev_translation;
    $divider = ProcessAParameter($param, 'DIVIDER', 'string', ', ');
    if ($divider == 'BREAK') $divider = '<BR>';
    print_param($param, array('NAME','DIVIDER'));

    $ret = '';
    foreach ($dvd['audio'] as $k => $a) {
        $l = $a['audiocontent'];
        $n = $a['audioformat'];
        $ret .= "$l: $n$divider";
    }
    $ret = substr($ret, 0, -1*strlen($divider));
    return($ret);
}
function casetype($param) {
global $dvd;
    print_param($param, array('NAME'));

    $ret = $dvd['casetype'];
    return($ret);
}
function vid16x9($param) {
global $dvd;
    $text = ProcessAParameter($param, 'TEXT', 'string', 'Anamorphic');
    print_param($param, array('NAME','TEXT'));

    $ret = '';
    if ($dvd['format16x9'] == 1)
        $ret = $text;
    return($ret);
}
function vidstandard($param) {
global $dvd;
    print_param($param, array('NAME'));

    $ret = $dvd['formatvideostandard'];
    return($ret);
}
function vidformats($param) {
global $dvd, $lang;
    $maxlist = ProcessAParameter($param, 'MAXLIST', 'int', 0);
    $ratios = ProcessAParameter($param, 'RATIOS', 'bool', false);
    $divider = ProcessAParameter($param, 'DIVIDER', 'string', ', ');
    if ($divider == 'BREAK') $divider = '<BR>';
    print_param($param, array('NAME','DIVIDER','RATIOS','MAXLIST'));

    $ret = '';
    $count = 0;
    if ($dvd['formatletterbox'] == 1) {
        $ret = '<NOBR>';
        if ($dvd['format16x9'] == 1)
            $ret .= $lang['16X9'].' ';
        $ret .= $lang['WIDESCREEN'];
        if ($ratios)
            $ret .= " $dvd[formataspectratio]:1";
        $ret .= '</NOBR>';
        $count++;
    }
    if ($count == 1 && $maxlist >= 1)
        return($ret);
    if ($ret == '') $divider = '';
    if ($dvd['formatpanandscan'] == 1)
        $ret .= "$divider<NOBR>$lang[PANANDSCAN] 1.33:1</NOBR>";
    if ($dvd['formatfullframe'] == 1)
        $ret .= "$divider<NOBR>$lang[FULLFRAME] 1.33:1</NOBR>";
    return($ret);
}
function regions($param) {
global $dvd;
// PREFIX is not affixed if region == 0
    $prefix = ProcessAParameter($param, 'PREFIX', 'string', '');
    $noregion = ProcessAParameter($param, 'NOREGION', 'string', 'Region 0');
    print_param($param, array('NAME','PREFIX','NOREGION'));

    if ($dvd['region'] == '@' || $dvd['region'] == '0')
        return($noregion);
    $ret = $prefix;
    for ($i=0; $i<strlen($dvd['region']); $i++) {
        if ($ret != '') $ret .= ', ';
        $ret .= $dvd['region'][$i];
    }
    return($ret);
}
function region($param) {
global $dvd;
    $text = ProcessAParameter($param, 'TEXT', 'string', '');
    $noregion = ProcessAParameter($param, 'NOREGION', 'string', 'Region 0');
    print_param($param, array('NAME','NOREGION','TEXT'));

    if ($dvd['region'] == '@' || $dvd['region'] == '0')
        return($noregion);
    return($text.substr($dvd['region'], 0, 1));
}
function layers($param) {
global $dvd, $lang;
    $single = ProcessAParameter($param, 'SINGLE', 'string', $lang['SINGLELAYERED']);
    $dual = ProcessAParameter($param, 'DUAL', 'string', $lang['DUALLAYERED']);
    print_param($param, array('NAME','SINGLE','DUAL'));

    if ($dvd['formatduallayered'] == 1)
        $ret = $dual;
    else
        $ret = $single;
    return($ret);
}
function sides($param) {
global $dvd, $lang;
    $single = ProcessAParameter($param, 'SINGLE', 'string', $lang['SINGLESIDED']);
    $dual = ProcessAParameter($param, 'DUAL', 'string', $lang['DUALSIDED']);
//  $flipper = ProcessAParameter($param, 'FLIPPER', 'string', $lang['FLIPPER']);
    print_param($param, array('NAME','SINGLE','DUAL','FLIPPER'));

//  if ($dvd['formatflipper'] == 1)
//      $ret = $flipper;
//  else
    if ($dvd['formatdualsided'] == 1)
        $ret = $dual;
    else
        $ret = $single;
    return($ret);
}
function srp($param) {
global $dvd;
    print_param($param, array('NAME'));

    $ret = "$dvd[srp] $dvd[srpcurrencyid]";
    return($ret);
}
function studios($param) {
global $dvd;
// This function ignores media companies, as does the windows program
    $divider = ProcessAParameter($param, 'DIVIDER', 'string', ', ');
    if ($divider == 'BREAK') $divider = '<BR>';
    print_param($param, array('NAME','DIVIDER'));

    $ret = '';
    foreach ($dvd['studios'] as $k =>$s) {
        if ($ret != '') $ret .= $divider;
        $ret .= "<NOBR><NOBR>$s[1]</NOBR></NOBR>";
    }
    return($ret);
}
function runtime($param) {
global $dvd;
    $hoursonly = ProcessAParameter($param, 'HOURSONLY', 'bool', false);
    $minsonly = ProcessAParameter($param, 'MINSONLY', 'bool', false);
    $totalinmins = ProcessAParameter($param, 'TOTALINMINS', 'bool', false);
    print_param($param, array('NAME','HOURSONLY','MINSONLY','TOTALINMINS'));

    $mins = $dvd['runningtime']%60;
    $hours = floor($dvd['runningtime']/60);
    if ($hoursonly) {
        $ret = $hours;
    }
    else if ($minsonly) {
        $ret = $mins;
    }
    else if ($totalinmins) {
        $ret = $dvd['runningtime'];
    }
    else {
        if ($mins < 10) $mins = '0'.$mins;
        $ret = "$hours:$mins";
    }
    return($ret);
}
function purchdate($param) {
global $dvd, $lang;
    $prefix = ProcessAParameter($param, 'PREFIX', 'string', '');
    print_param($param, array('NAME','PREFIX'));

    $ret = $prefix.fix88595(ucwords(strftimeReplacement($lang['SKINDATEFORMAT'], $dvd['purchasedate'])));
    return($ret);
}
function purchplace($param) {
global $dvd, $supplier;
    $prefix = ProcessAParameter($param, 'PREFIX', 'string', '');
    print_param($param, array('NAME','PREFIX'));

// incupdate.php sets missing value into mysql as 'Unknown'
    if ($supplier === false)
        return('');
    $ret = "$prefix$supplier[suppliername]";
    return($ret);
}
function purchprice($param) {
global $dvd;
    $prefix = ProcessAParameter($param, 'PREFIX', 'string', '');
    print_param($param, array('NAME','PREFIX'));

    $ret = $prefix.$dvd['purchaseprice'];
    return($ret);
}
function overview($param) {
global $dvd;
    print_param($param, array('NAME'));
// This behaviour is different from the 2.x version, where \r\n was encoded as &#13;&#10;
// The text in the XML file has bare \r\n on the end of lines, which get converted to \n by the import.
// All we can do is change \n into \r\n when we emit ...
    $ret = str_replace("\n", "<BR>\r\n", $dvd['overview']);
    return($ret);
}
function image($param) {
global $dvd, $maxthumbwidth, $thumbwidth;
    $width = ProcessAParameter($param, 'WIDTH', 'int', $thumbwidth);
    $doback = (strcasecmp(ProcessAParameter($param, 'FACE', 'string', 'front'), 'back') == 0);
    print_param($param, array('NAME','WIDTH','FACE'));

    $postfix = '';
    if ($doback) {
        if ($dvd['backimageanchor'] != '')
            $postfix = '</a>';
        $xxx = $dvd['backimage'];
        if ($width <= $maxthumbwidth || $xxx == '')
            $xxx = $dvd['backthumb'];
        $ret = "$dvd[backimageanchor]<IMG SRC=\"$xxx\" WIDTH=$width>$postfix";
    }
    else {
        if ($dvd['frontimageanchor'] != '')
            $postfix = '</a>';
        $xxx = $dvd['frontimage'];
        if ($width <= $maxthumbwidth || $xxx == '')
            $xxx = $dvd['frontthumb'];
        $ret = "$dvd[frontimageanchor]<IMG SRC=\"$xxx\" WIDTH=$width>$postfix";
    }
    return($ret);
}
function rating($param) {
global $dvd;
    print_param($param, array('NAME'));

    $ret = $dvd['rating'];
    return($ret);
}
function genres($param) {
global $dvd;
    $divider = ProcessAParameter($param, 'DIVIDER', 'string', ', ');
    if ($divider == 'BREAK') $divider = '<BR>';
    print_param($param, array('NAME','DIVIDER'));

    $tmp = explode('<br>', $dvd['p_genres']);
    $ret = '';
    foreach ($tmp as $k =>$v) {
        if ($ret != '') $ret .= $divider;
        $ret .= "<NOBR><NOBR>$v</NOBR></NOBR>";
    }
    return($ret);
}
function directors($param) {
global $dvd, $colornames, $lang;
    $maxlist = ProcessAParameter($param, 'MAXLIST', 'int', 0);
    $divider = ProcessAParameter($param, 'DIVIDER', 'string', ', ');
    if ($divider == 'BREAK') $divider = '<BR>';
    $prefix = ProcessAParameter($param, 'PREFIX', 'string', '');
    $suffix = ProcessAParameter($param, 'SUFFIX', 'string', '');
    $showroles = ProcessAParameter($param, 'SHOWROLES', 'bool', false);
    $roledivider = ProcessAParameter($param, 'ROLEDIVIDER', 'string', '...');
    $cn = ProcessAParameter($param, 'COLORNAMES', 'bool', $colornames);
    $rolesfirst = ProcessAParameter($param, 'ROLESFIRST', 'bool', false);
    print_param($param, array('NAME','MAXLIST','DIVIDER','PREFIX','SUFFIX','SHOWROLES','ROLEDIVIDER','COLORNAMES','ROLESFIRST'));

    $ret = '';
    $count = 0;
    foreach ($dvd['directors'] as $k => $d) {
        $count++;
        if ($maxlist != 0 && $count>$maxlist)
            break;
        $d['creditsubtype'] = $lang[strtoupper(str_replace(' ','',$d['creditsubtype']))];
        $dirname = "<A href=\"javascript:;\" onClick=\"$d[oc]\">".ColorName($d, $cn, true).'</A>';
        if ($k != 0)
            $ret .= $divider;
        if ($showroles) {
            if ($rolesfirst)
                $ret .= $d['creditsubtype'].$roledivider.$dirname;
            else
                $ret .= $dirname.$roledivider.$d['creditsubtype'];
        }
        else
            $ret .= $dirname;
    }
    if ($ret != '')
        $ret = ($prefix.$ret.$suffix);
    return($ret);
}
function director($param) {
    return(directors($param));
}
function prodyear($param) {
global $dvd;
    $blank = ProcessAParameter($param, 'BLANK', 'string', '');
    print_param($param, array('NAME','BLANK'));

    $ret = $dvd['productionyear'];
    if ($ret == '') $ret = $blank;
    return($ret);
}
function lock($param) {
global $dvd, $locks;
    $ifset = ProcessAParameter($param, 'IFSET', 'string', 'Locked');
    $ifnotset = ProcessAParameter($param, 'IFNOTSET', 'string', '');
    print_param($param, array('NAME','IFSET','IFNOTSET'));

    $d = strtolower(substr($param['NAME'], 5));
    if ($d == 'audiotracks') $d = 'audio';
    if ($d == 'coverimages') $d = 'covers';
    $ret = $ifset;
    if ($locks[$d] == '')
        $ret = $ifnotset;
    return($ret);
}
function features($param) {
global $dvd, $bullet;
    $divider = ProcessAParameter($param, 'DIVIDER', 'string', ', ');
    if ($divider == 'BREAK') $divider = '<BR>';
    print_param($param, array('NAME','DIVIDER'));

    $ret = '<NOBR>';
    $sep = "</NOBR>$divider<NOBR>";
    foreach ($dvd['extras'] as $val) {
        $ret .= $val.$sep;
    }
    $ret = substr($ret, 0, -1*(strlen($sep)-7));
    return($ret);
}
function boxsetcontents($param) {
global $dvd, $DVD_TABLE, $db;
    $showupcs = ProcessAParameter($param, 'SHOWUPCS', 'bool', false);
    $divider = ProcessAParameter($param, 'DIVIDER', 'string', ', ');
    if ($divider == 'BREAK') $divider = '<BR>';
    print_param($param, array('NAME','SHOWUPCS','DIVIDER'));

    $ret = '';
    if ($dvd['boxchild'] == 0)
        return($ret);

    $sql = "SELECT title,upc from $DVD_TABLE WHERE boxparent='$dvd[id]' ORDER BY id";
    $res = $db->sql_query($sql) or die($db->sql_error());
    while ($row = $db->sql_fetchrow($res)) {
        $t = $row['title'];
        if ($showupcs)
            $t .= " ($row[upc])";
        if ($ret != '') $ret .= $divider;
        $ret .= "<NOBR>$t</NOBR>";
    }
    $db->sql_freeresult($res);
    return($ret);
}
function upc($param) {
global $dvd;
    $formatted = ProcessAParameter($param, 'FORMATTED', 'bool', true);
    print_param($param, array('NAME','FORMATTED'));

    if ($formatted)
        $ret = $dvd['upc'];
    else
        $ret = $dvd['id'];
    return($ret);
}
function reldate($param) {
global $dvd, $lang;
    $blank = ProcessAParameter($param, 'BLANK', 'string', '');
    $yearonly = ProcessAParameter($param, 'YEARONLY', 'bool', false);
    $yeardigits = ProcessAParameter($param, 'YEARDIGITS', 'string', '2');
    print_param($param, array('NAME','BLANK','YEARONLY','YEARDIGITS'));

    if ($dvd['released'] == '')
        return($blank);
    if ($yearonly) {
        $ret = fix88595(ucwords(strftimeReplacement(($yeardigits=='4'?"%Y":"%y"), $dvd['released'])));
    }
    else {
        $ret = fix88595(ucwords(strftimeReplacement($lang['SKINDATEFORMAT'], $dvd['released'])));
    }
    return($ret);
}
function locality($param) {
global $dvd, $lang;
    print_param($param, array('NAME'));

    $locale = substr(strstr($dvd['id'], '.'), 1, 2);
    if (!$locale) $locale = '0';
    $ret = $lang['LOCALE'.$locale];
    return($ret);
}
// SETTINGS is an extension
function settings($param) {
global $dvd, $lang, $VersionNum, $IsPrivate, $SeparateReviews;
global $reviewgraph, $reviewsort;

    print_param($param, array('NAME'));

    $ret = '';
    $ret .= "VersionNum=$VersionNum\n";
    $ret .= 'IsPrivate='.($IsPrivate?'true':'false')."\n";
    $ret .= 'SeparateReviews='.($SeparateReviews?'true':'false')."\n";
    $ret .= "reviewgraph=$reviewgraph\n";
    $ret .= "reviewsort=$reviewsort\n";
    $ret = substr($ret, 0, strlen($ret)-1);
    return($ret);
}
// TAGS is an extension
function tags($param) {
global $dvd;
    $divider = ProcessAParameter($param, 'DIVIDER', 'string', ', ');
    if ($divider == 'BREAK') $divider = '<BR>';
    $prefix = ProcessAParameter($param, 'PREFIX', 'string', '');
    $suffix = ProcessAParameter($param, 'SUFFIX', 'string', '');
    $separator = ProcessAParameter($param, 'SEPARATOR', 'string', '...');
    $showid = ProcessAParameter($param, 'SHOWID', 'bool', false);
    $showname = ProcessAParameter($param, 'SHOWNAME', 'bool', false);
    $showfullyqualifiedname = ProcessAParameter($param, 'SHOWFULLYQUALIFIEDNAME', 'bool', true);
    print_param($param, array('NAME','DIVIDER','PREFIX','SUFFIX','SEPARATOR','SHOWID','SHOWNAME','SHOWFULLYQUALIFIEDNAME'));

    $ret = '';
    foreach ($dvd['tags'] as $k => $t) {
        if ($ret != '') $ret .= $divider;
        $line = '';
        if ($showid) {
            if ($line != '') $line .= $separator;
            $line .= $t['id'];
        }
        if ($showname) {
            if ($line != '') $line .= $separator;
            $line .= $t['name'];
        }
        if ($showfullyqualifiedname) {
            if ($line != '') $line .= $separator;
            $line .= $t['fullyqualifiedname'];
        }
        $ret .= $line;
    }
    if ($ret != '')
        $ret = $prefix.$ret.$suffix;
    return($ret);
}
// EVENTS is an extension
function events($param) {
global $dvd, $lang, $IsPrivate;
    $divider = ProcessAParameter($param, 'DIVIDER', 'string', ', ');
    if ($divider == 'BREAK') $divider = '<BR>';
    $prefix = ProcessAParameter($param, 'PREFIX', 'string', '');
    $suffix = ProcessAParameter($param, 'SUFFIX', 'string', '');
    $separator = ProcessAParameter($param, 'SEPARATOR', 'string', '...');
    $showfullname = ProcessAParameter($param, 'SHOWFULLNAME', 'bool', false);
    $showfirstname = ProcessAParameter($param, 'SHOWFIRSTNAME', 'bool', true);
    $showlastname = ProcessAParameter($param, 'SHOWLASTNAME', 'bool', true);
    $showphonenumber = ProcessAParameter($param, 'SHOWPHONENUMBER', 'bool', true);
    $showemailaddress = ProcessAParameter($param, 'SHOWEMAILADDRESS', 'bool', true);
    $showeventtype = ProcessAParameter($param, 'SHOWEVENTTYPE', 'bool', true);
    $showtimestamp = ProcessAParameter($param, 'SHOWTIMESTAMP', 'bool', true);
    print_param($param, array('NAME','DIVIDER','PREFIX','SUFFIX','SEPARATOR','SHOWFULLNAME','SHOWFIRSTNAME','SHOWLASTNAME','SHOWPHONENUMBER','SHOWEMAILADDRESS','SHOWEVENTTYPE','SHOWTIMESTAMP'));

    $ret = '';
    foreach ($dvd['events'] as $k => $e) {
        $e['lastname'] = HideName($e['lastname']);
        if ($ret != '') $ret .= $divider;
        $line = '';
        if ($showfullname) {
            if ($line != '') $line .= $separator;
            $line .= "$e[firstname] $e[lastname]";
        }
        if ($showfirstname) {
            if ($line != '') $line .= $separator;
            $line .= $e['firstname'];
        }
        if ($showlastname) {
            if ($line != '') $line .= $separator;
            $line .= $e['lastname'];
        }
        if ($showphonenumber) {
            if ($line != '') $line .= $separator;
            $line .= $e['phonenumber'];
        }
        if ($showemailaddress) {
            if ($line != '') $line .= $separator;
            $line .= $e['emailaddress'];
        }
        if ($showeventtype) {
            if ($line != '') $line .= $separator;
            $line .= $e['eventtype'];
        }
        if ($showtimestamp) {
            if ($line != '') $line .= $separator;
            $line .= $e['timestamp'];
        }
        $ret .= $line;
    }
    if ($ret != '')
        $ret = $prefix.$ret.$suffix;
    return($ret);
}
function collnum($param) {
global $dvd, $lang;
// SHOWWISHLISTPRIORITY is an extension
    $showwishlistpriority = ProcessAParameter($param, 'SHOWWISHLISTPRIORITY', 'bool', false);
    print_param($param, array('NAME','SHOWWISHLISTPRIORITY'));

    $ret = $dvd['collectionnumber'];
    if ($showwishlistpriority && $dvd['collectiontype'] == 'wishlist')
        $ret = $lang['WISHNAME'.$dvd['wishpriority']];
    return($ret);
}
function crew($param) {
global $dvd, $colornames, $lang;
    $gottafind = '';
    switch ($param['NAME']) {
    case 'CREW_DIRECTION':
    case 'CREDITS_DIRECTION':
        return(directors($param));
    case 'CREW_ART':
    case 'CREDITS_ART':
        $gottafind = 'Art';
        break;
    case 'CREW_CINEMATOGRAPHY':
    case 'CREDITS_CINEMATOGRAPHY':
        $gottafind = 'Cinematography';
        break;
    case 'CREW_FILMEDITING':
    case 'CREDITS_FILMEDITING':
        $gottafind = 'Film Editing';
        break;
    case 'CREW_MUSIC':
    case 'CREDITS_MUSIC':
        $gottafind = 'Music';
        break;
    case 'CREW_PRODUCTION':
    case 'CREDITS_PRODUCTION':
        $gottafind = 'Production';
        break;
    case 'CREW_SOUND':
    case 'CREDITS_SOUND':
        $gottafind = 'Sound';
        break;
    case 'CREW_WRITING':
    case 'CREDITS_WRITING':
        $gottafind = 'Writing';
        break;
    }
    $maxlist = ProcessAParameter($param, 'MAXLIST', 'int', 0);
    $divider = ProcessAParameter($param, 'DIVIDER', 'string', ', ');
    if ($divider == 'BREAK') $divider = '<BR>';
    $prefix = ProcessAParameter($param, 'PREFIX', 'string', '');
    $suffix = ProcessAParameter($param, 'SUFFIX', 'string', '');
    $showroles = ProcessAParameter($param, 'SHOWROLES', 'bool', false);
    $roledivider = ProcessAParameter($param, 'ROLEDIVIDER', 'string', '...');
    $cn = ProcessAParameter($param, 'COLORNAMES', 'bool', $colornames);
    $rolesfirst = ProcessAParameter($param, 'ROLESFIRST', 'bool', false);
    print_param($param, array('NAME','MAXLIST','DIVIDER','PREFIX','SUFFIX','SHOWROLES','ROLEDIVIDER','COLORNAMES','ROLESFIRST'));

    $ret = '';
    $count = 0;
    foreach ($dvd['credits'] as $k => $c) {
        if ($c['credittype'] != $gottafind)
            continue;
        $count++;
        if ($maxlist != 0 && $count>$maxlist)
            break;
        $c['creditsubtype'] = $lang[strtoupper(str_replace(' ','',$c['creditsubtype']))];
        if (isset($c['oc']))
            $dirname = "<A href=\"javascript:;\" onClick=\"$c[oc]\">".ColorName($c, $cn, true).'</A>';
        else
            $dirname = ColorName($c, $cn, true);
        if ($ret != '') $ret .= $divider;
        if ($showroles) {
            if ($rolesfirst)
                $ret .= $c['creditsubtype'].$roledivider.$dirname;
            else
                $ret .= $dirname.$roledivider.$c['creditsubtype'];
        }
        else
            $ret .= $dirname;
    }
    if ($ret != '')
        $ret = ($prefix.$ret.$suffix);
    return($ret);
}
function actors($param) {
global $dvd, $colornames;
    $maxlist = ProcessAParameter($param, 'MAXLIST', 'int', 0);
    $divider = ProcessAParameter($param, 'DIVIDER', 'string', ', ');
    if ($divider == 'BREAK') $divider = '<BR>';
    $showroles = ProcessAParameter($param, 'SHOWROLES', 'bool', false);
    $roledivider = ProcessAParameter($param, 'ROLEDIVIDER', 'string', ' as ');
    $cn = ProcessAParameter($param, 'COLORNAMES', 'bool', $colornames);
    $rolesfirst = ProcessAParameter($param, 'ROLESFIRST', 'bool', false);
    $roledivifblank = ProcessAParameter($param, 'ROLEDIVIFBLANK', 'bool', false);
    print_param($param, array('NAME','MAXLIST','DIVIDER','SHOWROLES','ROLEDIVIDER','COLORNAMES','ROLESFIRST','ROLEDIVIFBLANK'));

    $ret = '';
    $count = 0;
    foreach ($dvd['actors'] as $k => $a) {
        $count++;
        if ($maxlist != 0 && $count>$maxlist)
            break;
        if ($k != 0)
            $ret .= '</NOBR>'.$divider.'<NOBR>';
        if (isset($a['oc']))
            $dirname = "<A href=\"javascript:;\" onClick=\"$a[oc]\">".ColorName($a, $cn, true).'</A>';
        else
            $dirname = ColorName($a, $cn, true);
        if ($showroles) {
            $rd = $roledivider;
            if ($a['role'] == '' && !$roledivifblank) $rd = '';
            if ($rolesfirst)
                $ret .= $a['role'].$rd.$dirname;
            else
                $ret .= $dirname.$rd.$a['role'];
        }
        else
            $ret .= $dirname;
    }
    if ($ret != '') $ret .= '</NOBR>';
    return($ret);
}
function cast($param) {
    return(actors($param));
}

function ProcessDP($matches) {
global $debugskin;

//  if (stristr($matches[0], 'ACTORS') !== false) {echo "<pre>matches = ";print_r($matches);echo "</pre><br>";}
    $tmp = explode('"', $matches[3]);
//  if (stristr($matches[0], 'ACTORS') !== false) {echo "<pre>tmp = ";print_r($tmp);echo "</pre><br>";}
    for ($i=0; $i<count($tmp)-1; ) {
/////       $arr[] = trim($tmp[$i]).'"'.trim($tmp[$i+1]).'"'; // This messes up " as ". Not doing it may break something else ...
        $arr[] = trim($tmp[$i]).'"'.$tmp[$i+1].'"';
        $i += 2;
    }
//  if (stristr($matches[0], 'RUNTIME') !== false) {echo "<pre>arr = ";print_r($arr);echo "</pre><br>";}
    foreach ($arr as $v) {
        $split = $splite = strpos($v, '=');
        $splitq = strpos($v, '"');
        if ($splite === false || $splitq < $splite)
            $split = $splitq;
        $param[strtoupper(substr($v, 0, $split))] = trim(substr($v, $split+1), '"');
    }
    unset($arr);
//  if (stristr($matches[0], 'RUNTIME') !== false) {echo "<pre>param = ";print_r($param);echo "</pre><br>";}
    $t = $param['NAME'];
    if ($t == '16X9')
        $t = 'VID16X9';
    if (strncmp($t, 'LOCK_', 5) == 0) $t = 'LOCK';
    if (strncmp($t, 'CREW_', 5) == 0) $t = 'CREW';
    if (strncmp($t, 'CREDITS_', 8) == 0) $t = 'CREW';
    if (function_exists($t))
        $result = $t($param);
    else {
        $result = '';
        if ($debugskin)
            echo "Unrecognised call: &lt;DP NAME=\"$t\"&gt;<br>";
    }
    return($matches[1].$result);
}

function Replace2Lang($matches) {
global $lang;
    $matches[1] = str_replace("'", '', $matches[1]);
    $matches[1] = str_replace('"', '', $matches[1]);
    $matches[2] = str_replace("'", '', $matches[2]);
    $matches[2] = str_replace('"', '', $matches[2]);
    return($lang[$matches[1]][$matches[2]]);
}
function ReplaceLang($matches) {
global $lang;
    $matches[1] = str_replace("'", '', $matches[1]);
    $matches[1] = str_replace('"', '', $matches[1]);
    return($lang[$matches[1]]);
}

if (is_readable("$skinloc/$skinfile")) {
    $skin = file_get_contents("$skinloc/$skinfile");
    $j = preg_replace_callback('/(.*)(<DP )([^>"]*(("[^"]*")[^>"]*)*)(>)(.*)/Ui', "ProcessDP", $skin);
    unset($skin);

    $j = preg_replace_callback('/\\$lang\\[(.*)\\]\\[(.*)\\]/U', "Replace2Lang", $j);
    $j = preg_replace_callback('/\\$lang\\[(.*)\\]/U', "ReplaceLang", $j);

    $j = preg_replace('|\\.\\.[\\\\/]\\.\\.[\\\\/]skins[\\\\/]temp[\\\\/]|i', $skinloc.'/', $j);
    $j = str_replace('$DPIMAGES.', $skinloc.'/', $j);
    $j = preg_replace('|</body>|i', $endbody, $j);
    $j = str_replace('</ body>', '</body>', $j);
    echo $j;
    DebugSQL($db, "skin($skinloc/$skinfile)");
    exit;       // don't fall through: falling through does the internal skin
}
