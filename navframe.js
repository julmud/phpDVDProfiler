var navform;
var navheader=null, navheadercols;
var HeaderTop=0;

function GetTop(elem) {
var obj, curtop=0;

	obj = elem;
	if (obj.offsetParent) {
		while (obj.offsetParent) {
			curtop += obj.offsetTop;
			obj = obj.offsetParent;
		}
	}
	else {
		if (obj.y)
			curtop += obj.y;
	}
	return(curtop);
}

function GetLeft(elem) {
var obj, curleft=0;

	obj = elem;
	if (obj.offsetParent) {
		while (obj.offsetParent) {
			curleft += obj.offsetLeft;
			obj = obj.offsetParent;
		}
	}
	else {
		if (obj.x)
			curleft += obj.x;
	}
	return(curleft);
}

function SwitchField(whichone) {
var obj, found=0;

	obj = document.getElementsByName("Combobox");
	for (var i=0; i<obj.length; i++) {
		if (obj[i].id == whichone) {
			obj[i].style.visibility = "visible";
			navform.searchtext.value = obj[i].value;
			found = 1;
		}
		else {
			obj[i].style.visibility = "hidden";
		}
	}
	obj = document.getElementById("Textbox");
	if (found === 0) {
		obj.style.visibility = "visible";
		navform.searchtext.value = obj.value;
	}
	else {
		obj.style.visibility= "hidden";
	}
}

function PopulateMenuFrame(whichcol, whichway) {
var theimg, ar, selectedText;
var img = /(.*)(<img )(.*)(gfx\/)(.*)(\.gif)(.*)(&nbsp;)(.*)/i;
var ord = /(<a .*)(onclick=)([^\ ]*)(.*)(order=)([^&]*)(&)([^>]*)(>)(.*)(<\/a>)/i;
var thetext = /(.*)(title=)([^>]*)(.*)(<\/a>)(.*)/i;
var way=new Array();

	way["asc"] = "desc";
	way["desc"] = "asc";
	ar = ord.exec(navheadercols[whichcol].innerHTML);
	ar[0] = '';
	ar[3] = "\"PopulateMenuFrame(" + whichcol + ",\'" + way[whichway] + "\')\"";
	ar[6] = way[whichway];
	theimg = img.exec(ar[10]);
	if (theimg === null) {
		ar[10] = '<img src="gfx/' + whichway + '.gif" width=13 height=13 border=0 alt="">&nbsp;' + ar[10];
	}
	else {
		theimg[0] = '';
		theimg[5] = whichway;
		ar[10] = theimg.join('');
	}
	navheadercols[whichcol].innerHTML = ar.join('');

	selectedText = ar[10];
	for (i=1; i<navheadercols.length; i++) {
		if (i != whichcol) {
			ar = thetext.exec(navheadercols[i].innerHTML);
			if (selectedText.substring(selectedText.indexOf('&')) != ar[4].substring(ar[4].indexOf('&'))) {
				theimg = img.exec(navheadercols[i].innerHTML);
				if (theimg !== null)
					navheadercols[i].innerHTML = theimg[1] + theimg[9];
			}
			else
				navheadercols[i].innerHTML = navheadercols[whichcol].innerHTML;
		}
	}
	return(false);
}

function RemoveLoading() {
	document.getElementById("loading").style.display = "none";
	parent.document.getElementById("therows").rows = HeaderTop + navheader.offsetHeight + ",*";
// this ensures that the top frame won't scroll. the -4 is there to remove padding, i think, or margins
	document.body.style.height = HeaderTop - 4 + navheader.offsetHeight + "px";
}

function ResizeHeader() {
var i, menutablerows, menutablecols;
// This function is not called unless both nav and menu have been initialised, so no need to check for that
	RemoveLoading();

	menutablerows = parent.menu.document.getElementById("menutable").getElementsByTagName("tr");
	if (menutablerows.length > 2) {
		for (i=0; i<menutablerows.length; i++) {
			if (menutablerows[i].className == "o")
				break;
		}
		menutablecols = menutablerows[i].getElementsByTagName("td");
		for (i=menutablecols.length-1; i>=0; i--) {
			navheadercols[i].style.visibility = "visible";
			navheadercols[i].style.width = menutablecols[i].offsetWidth;
			navheadercols[i].style.top = HeaderTop;
			navheadercols[i].style.left = GetLeft(menutablecols[i]);
		}
	}
	else {
		for (i=0; i<navheadercols.length; i++) {
			if (i != 1)
				navheadercols[i].style.visibility = "hidden";
		}
	}
}

function NavInit(searchby) {
var curleft=0, curtop=0, obj;

	navform = document.getElementById("navform");
	obj = document.getElementById("Textbox");
	curtop = GetTop(obj);
	curleft = GetLeft(obj);
	obj = document.getElementsByName("Combobox");
	for (var i=0; i<obj.length; i++) {
		obj[i].style.left = curleft;
		obj[i].style.top = curtop;
	}
	SwitchField(searchby);
	navheader = document.getElementById("navheader");
	navheadercols = navheader.getElementsByTagName("div");
	HeaderTop = GetTop(navheader);
	for (var i=0; i<navheadercols.length; i++) {
		navheadercols[i].style.top = HeaderTop;
	}
	top.NavLoaded = true;
	return(true);
}

function SetVal(val) {
	navform.searchtext.value = val;
	return(true);
}

function ClearSearch(searchtext) {
	navform.Textbox.value='';
	navform.searchtext.value='';
	navform.searchby.selectedIndex = 0;
	SwitchField('');
	if ('searchtext' !== '')
		navform.submit();
}

function OnRes() {
	top.setcookie('widthgt800', GetWidth(), 10*365);
	return true;
}

function GetWidth() {
var frameWidth=0;
	if (self.innerWidth)
		frameWidth = self.innerWidth;
	else if (document.documentElement && document.documentElement.clientWidth)
		frameWidth = document.documentElement.clientWidth;
	else if (document.body)
		frameWidth = document.body.clientWidth;
	return(frameWidth);
}

function DebugFn(origval) {
//debugger
	ret = prompt("Left Pane Width="+GetWidth()+"px. Enter new TitlesPerPage value ('default' or a number):", origval);
	if (ret === null)
		return;
	if (ret == 'default') {
		top.setcookie('titlesperpage', top.getcookie('titlesperpage'), -1);
		top.location.reload();
		return;
	}
	if (ret == 'debugskin' || ret == 'IsPrivate') {
		if (top.getcookie(ret) === "")
			top.setcookie(ret, 'true', 10*365);
		else
			top.setcookie(ret, top.getcookie(ret), -1);
		top.location.reload();
		return;
	}
	ttp = parseInt(ret, 10);
	if (isNaN(ttp) || ttp < 0)
		ttp = origval;
	if (ttp != origval) {
		top.setcookie('titlesperpage', ttp, 10*365);
		top.location.reload();
	}
}
