<?php

defined('IN_SCRIPT') || define('IN_SCRIPT', 1);

include_once('version.php');
include_once('functions.php');
include_once('globalinits.php');
if (is_readable('multisite.php'))
    include_once('multisite.php');

include_once('siteconfig.php');
if (is_readable($localsiteconfig))
    include_once($localsiteconfig);

include_once('locale.php');

$Deb = "//\t";
if ($UpdateDebug)
    $Deb = "\t";
@header('Content-Type: text/javascript; charset="windows-1252";');
echo <<<EOT
var StatusLine=null, OutputWindow=null;
var StatusFrequency=$UpdateStatusFrequency, MillisecondPauseBetweenPosts=$UpdateMillisecondPauseBetweenPosts;
var MillisecondSettlingTime=$UpdateMillisecondSettlingTime, BadIFrameReferences=10, IFE=0;
var URL='', db_fast_update=true, StartTime=0, CurrentlyUpdating=false, Initialized=false, AbortingSocket=false;
var xmlstatus, ConnectionIdFromPOST = -1, POSTStatus=200, CurrentStatus='', StatusBeforeCurrentRun='', TimeUntilWaitMessage=5000;
var HTTPTimer = -1, SocketTimeout=3*StatusFrequency*1000, ResetTimeout=5*SocketTimeout, S0, NumStats=0, RTT=0, SRTT=0, RTTVAR=0;
var DisplayState=0, NewUpdate=true, Display2Length=0, Display3Length=0, OutputLength = -1, DisplayLoop=100, D0, NumErrorReturns=0;
var Spinner = new Array('&ndash;', '\\\\', '|', '/'), SpinnerIndex=0, WatchingOther=false, StopTheMadness=false;
var PseudoOutput = new Array(), StatusQueryNumber=0, CalledFrom='', LastStatusWasReallyReset=false, HaveContentDocument=false;
var NoticePattern = /<div.*id="?phpdvd_notice"?[^>]*>([^<]*)<\/div>([^<]*)</i;
function DebugOutput(addtime, str) {
var tmp='';
    if (addtime)
        tmp += (new Date().getTime()-StartTime) + ' ';
    PseudoOutput.push(tmp + str);
}

// DisplayState has a state in the lower 3 bits (0-7) and the Stop state in the 4th bit (8)
// DisplayState = 0 - Stopped. Waiting to start
// DisplayState & 7 = 1 - Started. Waiting for any content
// DisplayState & 7 = 2 - Have seen "our" content. Waiting for more content
// DisplayState & 7 = 3 - Have seen "not-our" content. Waiting for more content
// DisplayState & 8 = 8 - Stop.

function millisecondstotime(msecs) {
var t = new Date(1970, 0, 1);
var millis = msecs % 1000;
    if (millis == 0)
        millis = '';
    else
        millis = (millis/1000).toFixed(3).substr(1);
    t.setSeconds(msecs/1000);
    var s = t.toTimeString().substr(0, 8);
    if (msecs > 86399000)
        s = Math.floor((t - Date.parse("1/1/70")) / 3600000) + s.substr(2);
    return(s+millis);
}

function GetIframeDocumentObject() {
var idocument;

    try {
        if (HaveContentDocument)
            idocument = document.getElementById("writehere").contentDocument;
        else
            idocument = document.getElementById("writehere").contentWindow.document;
    }
    catch (err) {
        DebugOutput(true, 'GetIframeDocumentObject (via '+CalledFrom+'): Javascript Error (HaveContentDocument='+HaveContentDocument+'): '+err.name+':'+err.message);
        UpdateWindowAndScroll('<h3><b style="color:red">Javascript Error: </b>GetIframeDocumentObject (via ' + CalledFrom + '): ' + err.name + ':' + err.message);
    }

// Some sort of error accessing the iframe. If we have a handle to the document, continue
    if (typeof(idocument) == 'undefined' || idocument == null) {
        DebugOutput(true, 'GetIframeDocumentObject: Javascript Error: iFrame document='+idocument);
        var icd, icw, icwd, theiframe = document.getElementById("writehere");
        try {
            icd = theiframe.contentDocument;
            icw = theiframe.contentWindow;
            icwd = icw.document;
        }
        catch (err) {
            DebugOutput(true, 'GetIframeDocumentObject: icwd-Javascript Error: ' + err.name + ':' + err.message);
        }
        DebugOutput(true, 'icd='+icd+' icw='+icw+' icwd='+icwd);
        idocument = null;
    }
    return(idocument);
}

// Implement the TCP packet timeout algorithm
function UpdateSocketTimeout() {
    NumStats++;
    RTT = new Date().getTime() - S0;
    if (NumStats == 1) {
        SRTT = RTT;
        RTTVAR = RTT/2;
    }
    else {
        RTTVAR = 0.75 * RTTVAR + 0.25 * Math.abs(SRTT - RTT);
        SRTT = 0.875 * SRTT + 0.125 * RTT;
    }
    SocketTimeout = SRTT + 4 * RTTVAR;
    if (SocketTimeout < 2000)   // TCP uses 1 second, but the conditions are different
        SocketTimeout = 2000;
    return;
}

function AskForUpdateStatus(Reset) {
var DO, ST, STO;
    LastStatusWasReallyReset = Reset;
    if (Reset) {
        var tmplogin = document.getElementById("auth_login").value;
        var tmppass = document.getElementById("auth_pass").value;
        ST = 'HealthCheck&auth_login=' + tmplogin + '&auth_pass=' + tmppass;
        DO = '(Reset) ';
        STO = Math.round(ResetTimeout);
        Initialized = false;
    }
    else {
        ST = 'UpdateStatus';
        DO = '';
        STO = Math.round(SocketTimeout);
    }
    xmlstatus.open('GET', URL + '?action=' + ST + '&QueryNumber=' + StatusQueryNumber, true);
    xmlstatus.onreadystatechange = ProcessStatusResponse;
    xmlstatus.setRequestHeader("Connection", "close");
    if (HTTPTimer != -1) {
DebugOutput(true, "AskForStatus canceling timer #" + HTTPTimer);
        clearTimeout(HTTPTimer);
        HTTPTimer = -1;
    }
    S0 = new Date().getTime();
    HTTPTimer = setTimeout('StatusTimeout(' + StatusQueryNumber + ')', STO);
DebugOutput(true, "AskForStatus " + DO + "sending request #" + StatusQueryNumber + ' with timer #' + HTTPTimer);
    StatusQueryNumber++;
    if (Reset) {
        StatusLine.innerHTML = "$lang[UPDATEWAITINGFORRESET]";
    }
    xmlstatus.send();
}

function StatusTimeout(SQN) {
DebugOutput(true, "Timeout fired for query #" + SQN + ". Aborting current request and forgetting timer #" + HTTPTimer);
    AbortingSocket = true;
    xmlstatus.abort();
    AbortingSocket = false;
    HTTPTimer = -1;
    if (LastStatusWasReallyReset) {
        StatusLine.innerHTML = "$lang[UPDATESTATUSTIMEOUT1] " + Math.round(ResetTimeout/10)/100 + " $lang[UPDATESTATUSTIMEOUT2]"; // 2 decimals accuracy
        ResetTimeout *= 2;  // Double the timeout
    }
    else {
        StatusLine.innerHTML = "$lang[UPDATESTATUSTIMEOUT1] " + Math.round(SocketTimeout/10)/100 + " $lang[UPDATESTATUSTIMEOUT2]"; // 2 decimals accuracy
        SocketTimeout *= 2; // Double the timeout
    }
DebugOutput(true, "StatusTimeout asks for another "+((LastStatusWasReallyReset)?'reset':'status')+" in 50 milliseconds");
    setTimeout('AskForUpdateStatus('+LastStatusWasReallyReset+')', 50); // last one timed out so only wait minimally before checking again
}

function ProcessStatusResponse() {
    if (xmlstatus.readyState == 4) {
    var tmp, ConnectionIdFromStatus, InPreStage, UpdateComplete, ConnectionIdFromStatusStillExists;
        if (AbortingSocket)
            return;
        if (HTTPTimer != -1) {
//DebugOutput(true, "ProcessStatus canceling timer #" + HTTPTimer);
            clearTimeout(HTTPTimer);
            HTTPTimer = -1;
        }
        if (xmlstatus.status != 200) {
            if (xmlstatus.status == 401 && LastStatusWasReallyReset) {
                tmp = "<h3>$lang[UPDATERESETERROR]: " + xmlstatus.responseText.match(NoticePattern)[2] + "</h3>";
DebugOutput(true, "Bad Username/Password");
                UpdateWindowAndScroll(tmp);
                setTimeout('AskForUpdateStatus(false)', 50);    // sort out the status and the laststatus flags
                return;     // Don't want to do another
            }
            tmp = "$lang[UPDATENETWORKERROR1]: " + xmlstatus.status;
            if (xmlstatus.statusText != '')
                tmp += " (" + xmlstatus.statusText + ")";
            tmp += " $lang[UPDATENETWORKERROR2]$lang[UPDATELASTGOOD]: " + CurrentStatus;
            if (xmlstatus.responseText != null)
                tmp += ' (' + xmlstatus.responseText + ')';
            StatusLine.innerHTML = tmp;
DebugOutput(true, "Bad status; asks for another status in "+StatusFrequency+" seconds: "+tmp);
            setTimeout('AskForUpdateStatus(false)', StatusFrequency*1000);
            return;
        }
        UpdateSocketTimeout();

        if (xmlstatus.responseText == null) {
            RemoveGodot();
            UpdateWindowAndScroll("$lang[UPDATENULLSTATUS1]" + xmlstatus.status + " $lang[UPDATENULLSTATUS2]");
DebugOutput(true, "No response ("+xmlstatus.status+"); asks for another status in "+StatusFrequency+" seconds");
            setTimeout('AskForUpdateStatus(false)', StatusFrequency*1000);
            return;
        }
        ConnectionIdFromStatusStillExists = xmlstatus.responseText.match('true')? true: false;
        CurrentStatus = xmlstatus.responseText.match(/UpdateStatus:Status=([^:]*):/);
        if (CurrentStatus == null) {
            RemoveGodot();
            UpdateWindowAndScroll("$lang[UPDATEBADUPDATESTATUS] ==&gt;<pre>" + xmlstatus.responseText + "</pre>&lt;==");
DebugOutput(true, "No UpdateStatus; asks for another status in "+StatusFrequency+" seconds: +"+xmlstatus.responseText+'+');
            setTimeout('AskForUpdateStatus(false)', StatusFrequency*1000);
            return;
        }
        CurrentStatus = CurrentStatus[1];

        var details = CurrentStatus.split('*');
        ConnectionIdFromStatus = details[details.length-2];
        var ResponseNumber=xmlstatus.responseText.match(/^([^-]*)-/);
        if (ResponseNumber == null) {
            ResponseNumber = "ResponseNumber missing";
        }
        else {
            ResponseNumber = ResponseNumber[1];
        }
DebugOutput(true, "Received status response #" + ResponseNumber+' Status= '+CurrentStatus+ConnectionIdFromStatusStillExists);
        InPreStage = (ConnectionIdFromStatus < 0);
        if (ConnectionIdFromStatus < 0) {
            ConnectionIdFromStatus *= -1;
        }
        UpdateComplete = (details[1]==0)? true: false;
        if (!Initialized) {
            tmp = "$lang[UPDATEREADY]";
            if (!UpdateComplete) {
                if (ConnectionIdFromStatusStillExists)
                    tmp = "$lang[UPDATERUNNING]";
                else
                    tmp = "$lang[UPDATEINCOMPLETE]";
                tmp += " ($lang[UPDATECONNECTIONID]: " + ConnectionIdFromStatus + ")";
                if (InPreStage) {
                    tmp += ": $lang[UPDATEPREPARING]: " + details[3];
                }
                else if (details[1] > 0) {
                    tmp += ": $lang[UPDATEPROCESSING]: " + details[3];
                }
                else if (details[1] < 0) {
                var fixzero = details[1].charAt(details[1].length-1) == '0';
                    tmp += ": $lang[UPDATECLEANUP]" + (-1 * details[1]);
                    if (fixzero) tmp += '0';
                }
                if (!ConnectionIdFromStatusStillExists) {
                    tmp += "&nbsp;<button type=\"button\" onClick='AskForUpdateStatus(true)'>$lang[UPDATERESET]</button>";
                }
            }
            if (LastStatusWasReallyReset) {
                UpdateWindowAndScroll("<h3>$lang[UPDATERESETCOMPLETE]</h3>");
            }
            StatusLine.innerHTML = tmp;
            StatusBeforeCurrentRun = CurrentStatus; // Solves some potential timing issues
            Initialized = true;

// If there is a connection still running, follow its progress
//          if (!ConnectionIdFromStatusStillExists)
                return;
            WatchingOther = true;
        }

        if (POSTStatus == 401) {
// Bad username/password ... there aren't any changes to status on a failed request, so don't muck with things - DisplayContent() handled output
DebugOutput(true, "Shutdown because POST status=401");
            setTimeout('ShutdownDisplayLoopAndPostAnother(false, false)', 50);  // shutdown display loop ASAP; no repost
            return;
        }

// If we're just starting up, we need to ensure that the POST has hit the server...
        if (CurrentStatus == StatusBeforeCurrentRun) {
            StatusLine.innerHTML = "$lang[UPDATEWAITINGFORUPDATE]";
            setTimeout('AskForUpdateStatus(false)', 500);   // wait a titch, and try again
            return;
        }

// Now we're seeing the results of our POST
        if (UpdateComplete && !InPreStage & !ConnectionIdFromStatusStillExists) {
// Update has finished
            if (POSTStatus == 200) {
                StatusLine.innerHTML = "$lang[UPDATESTRAGGLERS]";
                if (DisplayState == 3) {
DebugOutput(true, "Successful shutdown called from updatecomplete with state=3");
                    setTimeout('ShutdownDisplayLoopAndPostAnother(false, true)', 50);
                    return;
                }
CalledFrom='StatusResponseUpdateComplete';
                var tmp = GetIframeDocumentObject();
                if (tmp != null)
                    tmp = tmp.body.innerHTML;
                if (DisplayState == 2 && tmp != null && tmp.match(/<div.*id="?theend"?[^>]*>/i) != null) {
DebugOutput(true, "Successful shutdown called from updatecomplete with state=2 and end tag found");
                    setTimeout('ShutdownDisplayLoopAndPostAnother(false, true)', 50);
                    return;
                }
DebugOutput(true, "Successful shutdown called from updatecomplete with state="+DisplayState);
                setTimeout('ShutdownDisplayLoopAndPostAnother(false, true)', 2*MillisecondSettlingTime);
                return;
            }
DebugOutput(true, "Error shutdown called from updatecomplete");
            setTimeout('ShutdownDisplayLoopAndPostAnother(false, false)', 50);  // shutdown DisplayContent() loop ASAP
            return;
        }

        if (ConnectionIdFromStatus != ConnectionIdFromPOST && ConnectionIdFromPOST != -1)
            DebugOutput(true, "Connection Id Updating: " + ConnectionIdFromStatus + " Connection Id Started: " + ConnectionIdFromPOST);

        if (ConnectionIdFromStatusStillExists) {
// Update has not finished, and is still running
            tmp = '<span style="display:inline-block;width:16px;text-align:center">' + Spinner[SpinnerIndex] + '</span>';
            SpinnerIndex = (SpinnerIndex + 1) % Spinner.length;
$Deb        tmp += RTT + "==>" + Math.round(SRTT) + " &plusmn; " + Math.round(RTTVAR) + " (RTO=" + Math.round(SocketTimeout) + ")<== ";
            if (WatchingOther)
                tmp += "$lang[UPDATERUNNING]: ";
            tmp += "$lang[UPDATECONNECTIONID] " + ConnectionIdFromStatus;
            if (InPreStage) {
                tmp += ": $lang[UPDATEPREPARING]: " + details[3];
            }
            else if (details[1] > 0) {
                tmp += ": $lang[UPDATEPROCESSING]: " + details[3];
            }
            else if (details[1] < 0) {
            var fixzero = details[1].charAt(details[1].length-1) == '0';
                tmp += ": $lang[UPDATECLEANUP]" + (-1 * details[1]);
                if (fixzero) tmp += '0';
            }
            else if (UpdateComplete) {
// incupdate has finished, but the process still exists, so all output is not yet available
                tmp += ": $lang[UPDATEPROCESSCLEANUP]";
            }
            StatusLine.innerHTML = tmp;
DebugOutput(true, "Response #" + ResponseNumber + " wants another status in " + StatusFrequency + " seconds at " + (new Date().getTime()-StartTime+StatusFrequency*1000));
            setTimeout('AskForUpdateStatus(false)', StatusFrequency*1000);
            return;
        }

// The update has not finished and appears not to be running ... perhaps we should spark off another
DebugOutput(true, "Shutdown start another");
        setTimeout('ShutdownDisplayLoopAndPostAnother(true, false)', MillisecondSettlingTime);  // Wait SettlingTime milliseconds for display to finish
    }
    return;
}

function ShutdownDisplayLoopAndPostAnother(KeepGoing, DisplayComplete) {
var zxc="ShutdownDisplay: Before="+DisplayState;
    if (DisplayState == 0) {
DebugOutput(true, zxc);
        StartAnother(KeepGoing, DisplayComplete);   // No need to shut down the DisplayContent() loop
        return;
    }
    DisplayState |= 8;  // Set Shutdown flag -- it will finish the next time it displays
zxc+=" After="+DisplayState+" KeepGoing="+KeepGoing;
DebugOutput(true, zxc);
    setTimeout('StartAnother(' + KeepGoing + ', ' + DisplayComplete + ')', DisplayLoop + 50);   // Ensure loop will have run (still a WOV)
    return;
}

function StartAnother(KeepGoing, DisplayComplete) {
//DebugOutput(false, "In StartAnother DisplayState should be 0; DisplayState="+DisplayState+" KeepGoing="+KeepGoing+" DisplayComplete="+DisplayComplete);
// It would appear that Chrome, at least, can cause timer events to be delayed in some cases (for example if the page is
// no longer the actively displayed tab). So we'll keep looking to see that final DisplayContent() loop has run
DebugOutput(true, 'StartAnother: KeepGoing='+KeepGoing+' DisplayComplete='+DisplayComplete);
    if (DisplayState != 0) {
        setTimeout('StartAnother(' + KeepGoing + ', ' + DisplayComplete + ')', DisplayLoop + 50);   // Ensure loop will have run (still a WOV)
        return;
    }
    if (StopTheMadness)
        KeepGoing = false;
    if (!KeepGoing) {
        RemoveGodot();  // If there was nothing to come in, ensure that the waiting message is gone
        if (DisplayComplete) {
        var HowLong = new Date().getTime() - StartTime;
            StatusLine.innerHTML = "$lang[UPDATECOMPLETE] &mdash; " + HowLong/1000 + " $lang[UPDATESECONDS] (" + millisecondstotime(HowLong) + ")";
        }
        CurrentlyUpdating = false;  // *now* it's safe to let another run start
        return;
    }
DebugOutput(true, "Current="+CurrentStatus+"  AND Last="+StatusBeforeCurrentRun);
DebugOutput(false, "     Current.match="+ CurrentStatus.match(/^(.*)\*[^\*]*\*$/)[1]);
DebugOutput(false, "        Last.match="+    StatusBeforeCurrentRun.match(/^(.*)\*[^\*]*\*$/)[1]);
    if (CurrentStatus.match(/^(.*)\*[^\*]*\*$/)[1] == StatusBeforeCurrentRun.match(/^(.*)\*[^\*]*\*$/)[1]) {
CalledFrom='StartAnotherStatusMatch';
        var tmp = GetIframeDocumentObject();
        if (tmp != null)
            tmp = tmp.body.innerHTML;
        if (tmp != null && tmp.match(/<div.*id="?phpdvd_ErrorExit"?[^>]*>/i) != null) {
            StatusLine.innerHTML = "$lang[UPDATEERRORSEEN]";
            CurrentlyUpdating = false;  // *now* it's safe to let another run start
DebugOutput(true, "Error seen: not sending another POST");
            return;
        }
        if (!db_fast_update) {
            StatusLine.innerHTML = "$lang[UPDATENOCHANGE]";
            CurrentlyUpdating = false;  // *now* it's safe to let another run start
DebugOutput(true, "No change seen: not sending another POST");
            return;
        }
DebugOutput(true, "Turning off db_fast_update");
        RemoveGodot();
        UpdateWindowAndScroll("<h4>$lang[UPDATEFASTOFF]</h4>");
        db_fast_update = false;
    }
    document.getElementById("complete").checked = false;    // Turn off CompleteUpdate for further runs
    StatusLine.innerHTML = "$lang[UPDATERESTART]";
    setTimeout('PostAnUpdateRequest()', MillisecondPauseBetweenPosts);
    return;
}

function PostAnUpdateRequest() {
var patt = /<form[\s\S]*?<\/form>/i;
var rt = '<div id="iframeform" style="display:none">' + document.body.innerHTML.match(patt)[0] + "</div>";

CalledFrom='PostAnUpdateTop';
    var idocument = GetIframeDocumentObject();
// Some sort of error accessing the iframe. If we have a handle to the document, continue
    if (idocument == null) {
        BadIFrameReferences--;
        RemoveGodot();
        if (BadIFrameReferences > 0) {
            UpdateWindowAndScroll("<h3>$lang[UPDATENOIFRAMETRY]" + BadIFrameReferences + "</h3>");
            setTimeout('PostAnUpdateRequest()', MillisecondPauseBetweenPosts);
            return;
        }
        DebugOutput(true, 'Giving up');
        UpdateWindowAndScroll("<h3>$lang[UPDATENOIFRAMEFAIL]</h3>");
        setTimeout('ShutdownDisplayLoopAndPostAnother(false, false)', 50);
        return;
    }

// Force the form into the iFrame
    idocument.open();
    idocument.write(rt.replace("phpaction", "action")); // Turns out that "action" wasn't a great choice for a keyword ...
    idocument.close();

// Give the iFrame-form elements the same values as the user gave the visible form elements
    var elem = document.getElementById("LoginForm").elements;
    for (var i=0; i<elem.length; i++) {
        if (elem[i].type == 'hidden' ||
            elem[i].type == 'checkbox' ||
            elem[i].type == 'text' ||
            elem[i].type == 'password') {
            if (elem[i].name == 'phpaction') elem[i].name = 'action';
                var hiddenField = idocument.getElementsByName(elem[i].name)[0];
                hiddenField.setAttribute('value', elem[i].value);
            if (elem[i].type == 'checkbox') {
                    hiddenField.checked = elem[i].checked;
                    hiddenField.disabled = elem[i].disabled;
            }
        }
    }
// A better plan is to always start off with fast_update, and then if it bombs, change it to not-fast
    idocument.getElementById("db_fast_update_fromui").checked = db_fast_update;

// Reset relevant globals for a new run
    POSTStatus = 200;
    ConnectionIdFromPOST = -1;
    NewUpdate = true;
    StatusBeforeCurrentRun = CurrentStatus;
    Display3Length = Display2Length = 0;
// Submit the form from the iFrame
    RemoveGodot();
    UpdateWindowAndScroll("<h3>$lang[UPDATEPOST]</h3>");
DebugOutput(true, "Submit: DisplayState="+DisplayState+" CompleteChecked="+document.getElementById("complete").checked);
    idocument.getElementById("LoginForm").submit();
    setTimeout('DisplayContent()', DisplayLoop);    // After .1 seconds start looking for content to transfer to the screen
DebugOutput(true, "Post asks for another status in 500 milliseconds");
    setTimeout('AskForUpdateStatus(false)', 500);   // After .5 seconds start updating the status line
}

function Requeue() {
    if ((DisplayState & 8) == 8) {  // Are we done?
var zxc = "Requeue: Before="+DisplayState;
        DisplayState = 0;
zxc+=" After="+DisplayState;
DebugOutput(true, zxc);
    }
    else {
        setTimeout('DisplayContent()', DisplayLoop);    // wait for DisplayLoop milliseconds and look again
    }
    return;
}

function RemoveGodot() {
// This removes any "still working" message and resets the "time-since-last-output-update" timer
// it MUST always be followed by output to the output div (unless we're at the end of the run)
    if (OutputLength != -1) {
        OutputWindow.innerHTML = OutputWindow.innerHTML.substring(0, OutputLength);
        OutputLength = -1;
    }
    D0 = new Date().getTime();
}

function DisplayWaitingMessage(IgnoreTime) {
// OutputLength == -1 means that there is no waiting message on the screen
// Only update the screen if the message isn't already there
    if (OutputLength == -1) {
    var TimeSinceLastOutput = new Date().getTime() - D0;
        if (IgnoreTime || TimeSinceLastOutput > TimeUntilWaitMessage) {     // If nothing has happened after 5 seconds
            OutputLength = OutputWindow.innerHTML.length;
            UpdateWindowAndScroll("<br><h2>$lang[UPDATEGODOT]</h2><br>");
        }
    }
}

function DisplayContent() {
var retvals, tmp, idoccontent;

CalledFrom='DisplayContentTop';
    var idocument = GetIframeDocumentObject();
// Some sort of error accessing the iframe content. If we have a handle to the document, continue
    if (idocument == null) {
        IFE++;
        Requeue();
        return;
    }

    DisplayWaitingMessage(false);
    if (DisplayState == 0) {
var zxc = "Display: Before="+DisplayState;
        DisplayState = 1;
zxc+=" After="+DisplayState;
DebugOutput(true, zxc);
    }
    if ((DisplayState & 7) == 1) {
        if (idocument == null || idocument.body == null) {  // This would mean the DOM isn't quite built yet ...
            Requeue();
            return;
        }
        idoccontent = idocument.body.innerHTML;
        if (idoccontent == '' || idoccontent.match(/<div.*id="?iframeform"?[^>]*>/i) != null) {
// Either still have the form so nothing has come back, or there is an empty page
            if ((DisplayState & 8) == 8) {
// We have gotten nothing back, but the update finished.
                RemoveGodot();
                UpdateWindowAndScroll("$lang[UPDATENOOUTPUT]");
            }
            Requeue();
            return;
        }
var zxcc = "DisplayState switching to 2: Before="+DisplayState;
        DisplayState = (DisplayState & 8) | 2;  // Assume that the content will be "ours" ...
zxcc+=" After="+DisplayState;
DebugOutput(true, zxcc);
DebugOutput(true, "DisplayState switching to 2: Length="+idoccontent.length+" Content==>"+idoccontent);
    }

    idoccontent = idocument.body.innerHTML; // let's look at this content
    retvals = idoccontent.match(NoticePattern);

    if (retvals != null) {
// "our" content found
        if ((DisplayState & 7) != 2) {
// transition from "not-our" to "our" content
// It may not be possible to get here; it would mean that "our" page (at least from the "phpdvd_notice" div on)
// has appeared after "not-our" content (which is generally a browser timeout page or a remote internal
// server error ... This code assumes it is somehow possible ...
var zxc = "DisplayState != 2: Before="+DisplayState;
            DisplayState = (DisplayState & 8) | 2;  // Mask in state 2
zxc+=" After="+DisplayState;
DebugOutput(true, zxc);
        }
//DebugOutput(false, idoccontent.length+"<==>"+Display2Length);
        if (idoccontent.length <= Display2Length) { // we've already processed that bit
            Requeue();
            return;
        }
DebugOutput(true, "New Content: "+idoccontent.length+"<==>"+Display2Length);
//DebugOutput(false, idoccontent.substr(Display2Length));
        tmp = retvals[1].match(/([0-9]*) - ([0-9]*)/);
        if (tmp == null) {  // Just an error code returned
        var z;
            ConnectionIdFromPOST = -1;
            POSTStatus = retvals[1].match(/([0-9]*)/);
            if (POSTStatus == null) {   // got a "phpdvd_notice" div without an error code? WTF
                POSTStatus = 999;
                z = "<h3>$lang[UPDATEENDED]: $lang[UPDATETIMEOUT]</h3><br>";
            }
            else {
                POSTStatus = POSTStatus[1];
                z = "<h3>$lang[UPDATEENDED]: " + retvals[2] + "</h3><br>";
            }

            StatusLine.innerHTML = "$lang[UPDATEERROR]: <b>" + POSTStatus  + "</b>";
            RemoveGodot();
            UpdateWindowAndScroll(z);
            Display2Length = idoccontent.length;    // Indicate that we have consumed that part of the error
            Requeue();  // The async status loop will try to kill the display in the fullness of time
            return;
        }
        if (NewUpdate) {
            NewUpdate = false;
            POSTStatus = tmp[1];
            ConnectionIdFromPOST = tmp[2];
            RemoveGodot();
            UpdateWindowAndScroll("<h3>$lang[UPDATERESPONSE] $lang[UPDATECONNECTIONID] " + ConnectionIdFromPOST + "</h3>");
        }
        RemoveGodot();
        UpdateWindowAndScroll(idoccontent.substr(Display2Length));
        Display2Length = idoccontent.length;
        Requeue();
        return;
    }
    else {
// "not-our" content found
        if ((DisplayState & 7) != 3) {
// transition from "our" to "not-our" content
var zxc = "DisplayState != 3: Before="+DisplayState;
            DisplayState = (DisplayState & 8) | 3;  // Mask in state 3
zxc+=" After="+DisplayState;
DebugOutput(true, zxc);
            NumErrorReturns++;
DebugOutput(true, "DisplayState change to 3: NumErrorReturns="+NumErrorReturns+" Length="+idoccontent.length+" Content==>"+idoccontent);
            var TheId = 'Error' + NumErrorReturns;
            RemoveGodot();
            UpdateWindowAndScroll('<h3><img src="gfx/plus.gif" onClick="HideUnhide(\'' + TheId + '\', this)">$lang[UPDATEUNEXPECTED]</h3>');
            UpdateWindowAndScroll('<div id="' + TheId + '" style="display:none; padding-left: 15px; border:1px solid black">' + idoccontent + '</div>');
            Display3Length = idoccontent.length;
            if (idoccontent.match(/<div.*id="?iframeform"?[^>]*>/i) != null)
                DebugOutput(true, "New: DisplayState="+DisplayState+" Content==>"+idoccontent);
        }
        else {
            if (Display3Length < idoccontent.length) {
DebugOutput(true, "DisplayState="+DisplayState+": NumErrorReturns="+NumErrorReturns+" Length="+idoccontent.length+" Content==>"+idoccontent);
                RemoveGodot();
                document.getElementById('Error'+NumErrorReturns).innerHTML += idoccontent.substr(Display3Length);
                UpdateWindowAndScroll('');
                Display3Length = idoccontent.length;
                if (idoccontent.match(/<div.*id="?iframeform"?[^>]*>/i) != null)
                    DebugOutput(true, "Old: DisplayState="+DisplayState+" Content==>"+idoccontent);
            }
        }
        Requeue();
        return;
    }
}

function HideUnhide(theitems, obj) {
var item=document.getElementById(theitems);
    if (item.style.display == 'none') {
        item.style.display = '';
        obj.src = 'gfx/minus.gif';
    }
    else {
        item.style.display = 'none';
        obj.src = 'gfx/plus.gif';
    }
}

function SubmitClicked() {
// need to prevent multiple button presses until the current update stage is complete
    if (CurrentlyUpdating) {
        alert("$lang[UPDATEDONTPRESS]");
        return;
    }
    CurrentlyUpdating = true;
    WatchingOther = false;
    db_fast_update = true;  // Start trying to go fast, and fall back
    OutputWindow.innerHTML = '';    // Clear the window
    StatusLine.innerHTML = '';  // and the status
    StartTime = new Date().getTime();
DebugOutput(true, "Submit clicked - NEW EPOCH");
    PostAnUpdateRequest();
}

function ForceHalt() {
    StopTheMadness = true;
DebugOutput(true, "ForceHalt pressed and set");
    return;
}

function DebugEntry() {
    var w = window.open('about:blank','_blank','toolbar=no,width=700,height=500,resizable=yes,scrollbars=yes,status=yes');
    w.document.write('<h3>Debug Output ... Copy all of the text and mail it to fred</h3>');
    w.document.write('Browser: ' + Detect() + '<br>');
    w.document.write('Server: '+URL+'<br>');
    w.document.write('StatusFrequency = '+StatusFrequency+'<br>');
    w.document.write('MillisecondPauseBetweenPosts = '+MillisecondPauseBetweenPosts+'<br>');
    w.document.write('MillisecondSettlingTime = '+MillisecondSettlingTime+'<br>');
    w.document.write('RTT = '+RTT+'<br>SRTT = '+SRTT+'<br>RTTVAR = '+RTTVAR+'<br>SocketTimeout = '+SocketTimeout+'<br>');
    w.document.write('<pre>');
    for (var i in PseudoOutput)
        w.document.write(PseudoOutput[i].replace(/</g, '&lt;').replace(/>/g, '&gt;') + "\\n");
    w.document.write("</pre><br><br><br><h3>HTML in output window</h3><pre>");
    w.document.write(OutputWindow.innerHTML.replace(/</g, '&lt;').replace(/>/g, '&gt;') + "\\n");
    w.document.write("</pre>");
    return;
}

function UpdateWindowAndScroll(str) {
    if (str != '')
        OutputWindow.innerHTML += str;
    OutputWindow.scrollTop = 999999;
    return;
}

function UpdateLoaded() {
    document.LoginForm.auth_login.focus();
    HaveContentDocument = (typeof(document.getElementById("writehere").contentDocument) == 'object');
    URL = document.getElementById("LoginForm").action;

    if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlstatus = new XMLHttpRequest();
    }
    else {// code for IE6, IE5
        xmlstatus = new ActiveXObject("Microsoft.XMLHTTP");
    }
    StartTime = new Date().getTime();
DebugOutput(true, "UpdateLoaded asks for initial status");
    AskForUpdateStatus(false);
    StatusLine = document.getElementById("statushere");
    OutputWindow = document.getElementById("outputhere");
//DebugOutput(false, "loaded has "+DisplayState);
}

// This browser detection code is a modified version of code found at http://www.javascripter.net/faq/browsern.htm
function Detect() {
var nVer=navigator.appVersion, nAgt=navigator.userAgent, browserName=navigator.appName;
var fullVersion='' + parseFloat(navigator.appVersion);
var nameOffset, verOffset, ix;

// In Opera, the true version is after "Opera" or after "Version"
    if ((verOffset=nAgt.indexOf("Opera")) != -1) {
        browserName = "Opera";
        fullVersion = nAgt.substring(verOffset + 6);
        if ((verOffset=nAgt.indexOf("Version")) != -1)
            fullVersion = nAgt.substring(verOffset+8);
    }
// In MSIE, the true version is after "MSIE" in userAgent
    else if ((verOffset=nAgt.indexOf("MSIE")) != -1) {
        browserName = "Microsoft Internet Explorer";
        fullVersion = nAgt.substring(verOffset + 5);
    }
// In Chrome, the true version is after "Chrome"
    else if ((verOffset=nAgt.indexOf("Chrome")) != -1) {
        browserName = "Google Chrome";
        fullVersion = nAgt.substring(verOffset + 7);
    }
// In Safari, the true version is after "Safari" or after "Version"
    else if ((verOffset=nAgt.indexOf("Safari")) != -1) {
        browserName = "Safari";
        fullVersion = nAgt.substring(verOffset + 7);
        if ((verOffset=nAgt.indexOf("Version")) != -1)
            fullVersion = nAgt.substring(verOffset + 8);
    }
// In Firefox, the true version is after "Firefox"
    else if ((verOffset=nAgt.indexOf("Firefox")) != -1) {
        browserName = "Firefox";
        fullVersion = nAgt.substring(verOffset + 8);
    }
// In most other browsers, "name/version" is at the end of userAgent
    else if ((nameOffset=nAgt.lastIndexOf(' ')+1) < (verOffset=nAgt.lastIndexOf('/'))) {
        browserName = nAgt.substring(nameOffset, verOffset);
        fullVersion = nAgt.substring(verOffset + 1);
        if (browserName.toLowerCase() == browserName.toUpperCase())
            browserName = navigator.appName;
    }
// trim the fullVersion string at semicolon/space if present
    if ((ix=fullVersion.indexOf(";")) != -1)
        fullVersion = fullVersion.substring(0, ix);
    if ((ix=fullVersion.indexOf(" ")) != -1)
        fullVersion = fullVersion.substring(0, ix);

    return(browserName + ' ' + fullVersion);
}

EOT;
