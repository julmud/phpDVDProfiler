window.onerror = function(msg, url, linenumber) {
var TheMessage = msg +' at line number ' + linenumber + ' of file ' + url + '. Please post this info on dvdaholic.me.uk/forums/';
var win = document.getElementById("outputhere");
	if (win == null)
		alert('JavaScript Error: ' + TheMessage);
	else
		win.innerHTML += '<h3><b style="color:red">JavaScript Error: </b>' + TheMessage + '</h3>';
	if (typeof(DebugOutput) == 'function')
		DebugOutput(true, 'JavaScript Error: ' + TheMessage);
	return(false);
}
