/* Vision Slidemenü 1.0                        */
/* Copyright (C) 2002 Matthias Mohr            */
/* E-mail: webmaster@mamo-net.de               */
/* Homepage: http://www.mamo-net.de            */
/* ------------------------------------------- */
/* Sie dürfen dieses Script frei benutzen wenn */ 
/* dieser Corpright Hinweis bestehen bleibt.   */

NS6 = (document.getElementById&&!document.all);
IE = (document.all);
NS = (navigator.appName=="Netscape" && navigator.appVersion.charAt(0)=="4");

tempBar='';barBuilt=0;momItems=new Array();
moving=setTimeout('null',1);
function moveOut() {
if ((NS6||NS)&&parseInt(mom.left)<0 || IE && mom.pixelLeft<0) {
clearTimeout(moving);moving = setTimeout('moveOut()', slideSpeed);slideMenu(10);}
else {clearTimeout(moving);moving=setTimeout('null',1);}}
function moveBack() {clearTimeout(moving);moving = setTimeout('moveBack1()', waitTime);}
function moveBack1() {
if ((NS6||NS) && parseInt(mom.left)>(-menuWidth) || IE && mom.pixelLeft>(-menuWidth)) {
clearTimeout(moving);moving = setTimeout('moveBack1()', slideSpeed);slideMenu(-10);}
else {clearTimeout(moving);moving=setTimeout('null',1);}}
function slideMenu(num){
if (IE) {mom.pixelLeft += num;}
if (NS||NS6) {mom.left = parseInt(mom.left)+num;}
if (NS) {bmom.clip.right+=num;bmom2.clip.right+=num;}}

function MOMms() {
if (NS||NS6) {winY = window.pageYOffset;}
if (IE) {winY = document.body.scrollTop;}
if (NS6||IE||NS) {
if (winY!=lastY&&winY>YOffset-staticYOffset) {
smooth = .2 * (winY - lastY - YOffset + staticYOffset);}
else if (YOffset-staticYOffset+lastY>YOffset-staticYOffset) {
smooth = .2 * (winY - lastY - (YOffset-(YOffset-winY)));}
else {smooth=0;}
if(smooth > 0) smooth = Math.ceil(smooth);
else smooth = Math.floor(smooth);
if (IE) bmom.pixelTop+=smooth;
if (NS6||NS) bmom.top=parseInt(bmom.top)+smooth;
lastY = lastY+smooth;
setTimeout('MOMms()', 1);}}

function MOMbb() {
if(barText.indexOf('<IMG')>-1) {tempBar=barText;}
else{for (b=0;b<barText.length;b++) {tempBar+=barText.charAt(b)+"<BR>";}}
document.write('<td align="center" rowspan="100" width="'+barWidth+'" bgcolor="'+barBGColor+'" valign="'+barVAlign+'"><p align="center"><font face="'+barFontFamily+'" Size="'+barFontSize+'" COLOR="'+barFontColor+'"><B>'+tempBar+'</B></font></p></TD>');}

function MOMis() {
if (NS6){mom=document.getElementById("themom").style;bmom=document.getElementById("basemom").style;
bmom.clip="rect(0 "+document.getElementById("themom").offsetWidth+" "+document.getElementById("themom").offsetHeight+" 0)";mom.visibility="visible";}
else if (IE) {mom=document.all("themom").style;bmom=document.all("basemom").style;
bmom.clip="rect(0 "+themom.offsetWidth+" "+themom.offsetHeight+" 0)";bmom.visibility = "visible";}
else if (NS) {bmom=document.layers["basemom1"];
bmom2=bmom.document.layers["basemom2"];mom=bmom2.document.layers["themom"];
bmom2.clip.left=0;mom.visibility = "show";}
if (menuIsStatic=="yes") MOMms();}

function MOMbilden() {
if (IE||NS6) {document.write('<DIV ID="basemom" style="Position:Absolute;Left:'+XOffset+';Top:'+YOffset+';Z-Index:20;width:'+(menuWidth+barWidth+10)+'"><DIV ID="themom" style="Position:Absolute;Left:'+(-menuWidth)+';Top:0;Z-Index:20;" onmouseover="moveOut()" onmouseout="moveBack()">');}
if (NS) {document.write('<LAYER name="basemom1" top="'+YOffset+'" LEFT='+XOffset+' visibility="show"><ILAYER name="basemom2"><LAYER visibility="hide" name="themom" bgcolor="'+menuBGColor+'" left="'+(-menuWidth)+'" onmouseover="moveOut()" onmouseout="moveBack()">');}
if (NS6){document.write('<table border="0" cellpadding="0" cellspacing="0" width="'+(menuWidth+barWidth+2)+'" bgcolor="'+menuBGColor+'"><TR><TD>');}
document.write('<table border="0" cellpadding="0" cellspacing="1" width="'+(menuWidth+barWidth+2)+'" bgcolor="'+menuBGColor+'">');
for(i=0;i<momItems.length;i++) {
if(!momItems[i][3]){momItems[i][3]=menuCols;momItems[i][5]=menuWidth-1;}
else if(momItems[i][3]!=menuCols)momItems[i][5]=Math.round(menuWidth*(momItems[i][3]/menuCols)-1);
if(momItems[i-1]&&momItems[i-1][4]!="no"){document.write('<TR>');}
if(!momItems[i][1]){
document.write('<tr><td bgcolor="'+hdrBGColor+'" HEIGHT="'+hdrHeight+'" ALIGN="'+hdrAlign+'" VALIGN="'+hdrVAlign+'" WIDTH="'+momItems[i][5]+'" COLSPAN="'+momItems[i][3]+'">&nbsp;<font face="'+hdrFontFamily+'" Size="'+hdrFontSize+'" COLOR="'+hdrFontColor+'"><b>'+momItems[i][0]+'</b></font></td>');}
else {if(!momItems[i][2])momItems[i][2]=linkTarget;
document.write('<TD BGCOLOR="'+linkBGColor+'" onmouseover="bgColor=\''+linkOverBGColor+'\'" onmouseout="bgColor=\''+linkBGColor+'\'" WIDTH="'+momItems[i][5]+'" COLSPAN="'+momItems[i][3]+'"><ILAYER><LAYER onmouseover="bgColor=\''+linkOverBGColor+'\'" onmouseout="bgColor=\''+linkBGColor+'\'" WIDTH="100%" ALIGN="'+linkAlign+'"><DIV  ALIGN="'+linkAlign+'"><FONT face="'+linkFontFamily+'" Size="'+linkFontSize+'">&nbsp;<A HREF="'+momItems[i][1]+'" target="'+momItems[i][2]+'" CLASS="momItems">'+momItems[i][0]+'</a></font></DIV></LAYER></ILAYER></TD>');}
if(momItems[i][4]!="no"&&barBuilt==0){MOMbb();barBuilt=1;}
if(momItems[i][4]!="no"){document.write('</TR>');}}
document.write('</table>');
if (NS6){document.write('</TD></TR></TABLE>');}
if (IE||NS6) {document.write('</DIV></DIV>');}
if (NS) {document.write('</LAYER></ILAYER></LAYER>');}
theleft=-menuWidth;lastY=0;setTimeout('MOMis();', 1);}
