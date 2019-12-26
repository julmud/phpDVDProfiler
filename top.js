var NavLoaded=false, MenuLoaded=false, HowLongToWait=8000;
// HowLongToWait is the maximum number of milliseconds to wait for the two frames to have loaded

function ExecuteAfterAllHaveLoaded(code) {
	if (NavLoaded === false || MenuLoaded === false) {
		if (HowLongToWait > 0) {
			HowLongToWait -= 100;
			setTimeout('ExecuteAfterAllHaveLoaded("'+code+'")', 100);
			return(false);
		}
//		else
//			alert('Not waiting any longer!!!');
	}
	if (code != '')
		eval(code);
	return(true);
}

function MenuInit() {
	MenuLoaded = true;
// a resize can arrive after the flags have been set but before the initial resize
// but it doesn't matter because that can happen with quick resize-events anyway.
	ExecuteAfterAllHaveLoaded('nav.ResizeHeader();');
}

function DoResizeHeader() {
	if (NavLoaded === true && MenuLoaded === true)
		nav.ResizeHeader();
	return;
}

function getexpirydate(numdays) {
var Today = new Date();
	Today.setTime(Date.parse(Today) + numdays*24*60*60*1000);
	return(Today.toUTCString());
}

function getcookie(cookiename) {
var cookiestring = "" + document.cookie;
var index1 = cookiestring.indexOf(cookiename);
var index2;

	if (index1 == -1 || cookiename === "")
		return("");
	index2 = cookiestring.indexOf(';', index1);
	if (index2 == -1)
		index2 = cookiestring.length;
	return(unescape(cookiestring.substring(index1+cookiename.length+1, index2)));
}

function setcookie(name, value, durationindays) {
var cookiestring = name + "=" + escape(value) + ";EXPIRES=" + getexpirydate(durationindays);
	document.cookie = cookiestring;
	return(true);
}

function ChangeLang() {
// this fails for the galleries - needs the srch and sort stuff
var page=entry.location.href.match(/mediaid=([^&]*)/i);
	if (page == null || page[1] == '') {				// entry exists
		top.location.reload();	// and we're done
	}
	if (top.location.href.search(/lastmedia=/i) != -1) {	// url has a mediaid parameter
		top.location.assign(top.location.href.replace(/(lastmedia=)([^&]*)/i,"$1"+page[1]));
	}
	else if (top.location.href.indexOf('?') == -1) {	// no ? in url
		top.location.assign(top.location.href + '?lastmedia=' + page[1]);
	}
	else {
		top.location.assign(top.location.href + '&lastmedia=' + page[1]);
	}
}
