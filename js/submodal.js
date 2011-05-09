/**
 * This derivative version of subModal can be downloaded from http://gabrito.com/files/subModal/
 * Original By Seth Banks (webmaster at subimage dot com)  http://www.subimage.com/
 * Contributions by Eric Angel (tab index code), Scott (hiding/showing selects for IE users), Todd Huss (submodal class on hrefs, moving div containers into javascript, phark method for putting close.gif into CSS), Thomas Risberg (safari fixes for scroll amount), Dave Campbell (improved parsing of submodal-width-height class)
 */

var gPopupMask=null;var gPopupContainer=null;var gPopFrame=null;var gReturnFunc;var gPopupIsShown=false;var gHideSelects=false;var gLoading="loading.html";var gTabIndexes=new Array();var gTabbableTags=new Array("A","BUTTON","TEXTAREA","INPUT","IFRAME");if(!document.all){document.onkeypress=keyDownHandler;}function setPopUpLoadingPage(loading){gLoading=loading;}function initPopUp(){var body=document.getElementsByTagName('body')[0];var popmask=document.createElement('div');popmask.id='popupMask';var popcont=document.createElement('div');popcont.id='popupContainer';popcont.innerHTML=''+'<div id="popupInner">'+'<div id="popupTitleBar">'+'<div id="popupTitle"></div>'+'<div id="popupControls">'+'<a onclick="hidePopWin(false);"><span>Close</span></a>'+'</div>'+'</div>'+'<iframe src="'+gLoading+'" style="width:100%;height:100%;background-color:transparent;" scrolling="no" frameborder="0" allowtransparency="true" id="popupFrame" name="popupFrame" width="100%" height="100%"></iframe>'+'</div>';body.appendChild(popmask);body.appendChild(popcont);gPopupMask=document.getElementById("popupMask");gPopupContainer=document.getElementById("popupContainer");gPopFrame=document.getElementById("popupFrame");var brsVersion=parseInt(window.navigator.appVersion.charAt(0),10);if(brsVersion<=6&&window.navigator.userAgent.indexOf("MSIE")>-1){gHideSelects=true;}var elms=document.getElementsByTagName('a');for(i=0;i<elms.length;i++){if(elms[i].className.indexOf("submodal")>=0){elms[i].onclick=function(){var width=400;var height=200;var startIndex=this.className.indexOf("submodal");var endIndex=this.className.indexOf(" ",startIndex);if(endIndex<0){endIndex=this.className.length;}var clazz=this.className.substring(startIndex,endIndex);params=clazz.split('-');if(params.length==3){width=parseInt(params[1]);height=parseInt(params[2]);}showPopWin(this.href,width,height,null);return false;}}}}addEvent(window,"load",initPopUp);function showPopWin(url,width,height,returnFunc){gPopupIsShown=true;disableTabIndexes();gPopupMask.style.display="block";gPopupContainer.style.display="block";centerPopWin(width,height);var titleBarHeight=parseInt(document.getElementById("popupTitleBar").offsetHeight,10);gPopupContainer.style.width=width+"px";gPopupContainer.style.height=(height+titleBarHeight)+"px";gPopFrame.style.width=parseInt(document.getElementById("popupTitleBar").offsetWidth,10)+"px";gPopFrame.style.height=(height)+"px";gPopFrame.src=url;gReturnFunc=returnFunc;if(gHideSelects==true){hideSelectBoxes();}window.setTimeout("setPopTitleAndRewriteTargets();",100);}var gi=0;function centerPopWin(width,height){if(gPopupIsShown==true){if(width==null||isNaN(width)){width=gPopupContainer.offsetWidth;}if(height==null){height=gPopupContainer.offsetHeight;}var fullHeight=getViewportHeight();var fullWidth=getViewportWidth();var scLeft,scTop;if(self.pageYOffset){scLeft=self.pageXOffset;scTop=self.pageYOffset;}else if(document.documentElement&&document.documentElement.scrollTop){scLeft=document.documentElement.scrollLeft;scTop=document.documentElement.scrollTop;}else if(document.body){scLeft=document.body.scrollLeft;scTop=document.body.scrollTop;}gPopupMask.style.height=fullHeight+"px";gPopupMask.style.width=fullWidth+"px";gPopupMask.style.top=scTop+"px";gPopupMask.style.left=scLeft+"px";window.status=gPopupMask.style.top+" "+gPopupMask.style.left+" "+gi++;var titleBarHeight=parseInt(document.getElementById("popupTitleBar").offsetHeight,10);var topMargin=scTop+((fullHeight-(height+titleBarHeight))/2);if(topMargin<0){topMargin=0;}gPopupContainer.style.top=topMargin+"px";gPopupContainer.style.left=(scLeft+((fullWidth-width)/2))+"px";}}addEvent(window,"resize",centerPopWin);window.onscroll=centerPopWin;function hidePopWin(callReturnFunc){gPopupIsShown=false;restoreTabIndexes();if(gPopupMask==null){return;}gPopupMask.style.display="none";gPopupContainer.style.display="none";if(callReturnFunc==true&&gReturnFunc!=null){gReturnFunc(window.frames["popupFrame"].returnVal);}if(gHideSelects==true){displaySelectBoxes();}}function setPopTitleAndRewriteTargets(){if(window.frames["popupFrame"].document.title==null){window.setTimeout("setPopTitleAndRewriteTargets();",10);}else{var popupDocument=window.frames["popupFrame"].document;document.getElementById("popupTitle").innerHTML=popupDocument.title;if(popupDocument.getElementsByTagName('base').length<1){var aList=window.frames["popupFrame"].document.getElementsByTagName('a');for(var i=0;i<aList.length;i++){if(aList.target==null)aList[i].target='_parent';}var fList=window.frames["popupFrame"].document.getElementsByTagName('form');for(i=0;i<fList.length;i++){if(fList.target==null)fList[i].target='_parent';}}}}function keyDownHandler(e){if(gPopupIsShown&&e.keyCode==9)return false;}function disableTabIndexes(){if(document.all){var i=0;for(var j=0;j<gTabbableTags.length;j++){var tagElements=document.getElementsByTagName(gTabbableTags[j]);for(var k=0;k<tagElements.length;k++){gTabIndexes[i]=tagElements[k].tabIndex;tagElements[k].tabIndex="-1";i++;}}}}function restoreTabIndexes(){if(document.all){var i=0;for(var j=0;j<gTabbableTags.length;j++){var tagElements=document.getElementsByTagName(gTabbableTags[j]);for(var k=0;k<tagElements.length;k++){tagElements[k].tabIndex=gTabIndexes[i];tagElements[k].tabEnabled=true;i++;}}}}function hideSelectBoxes(){for(var i=0;i<document.forms.length;i++){for(var e=0;e<document.forms[i].length;e++){if(document.forms[i].elements[e].tagName=="SELECT"){document.forms[i].elements[e].style.visibility="hidden";}}}}function displaySelectBoxes(){for(var i=0;i<document.forms.length;i++){for(var e=0;e<document.forms[i].length;e++){if(document.forms[i].elements[e].tagName=="SELECT"){document.forms[i].elements[e].style.visibility="visible";}}}}function addEvent(obj,evType,fn){if(obj.addEventListener){obj.addEventListener(evType,fn,false);return true;}else if(obj.attachEvent){var r=obj.attachEvent("on"+evType,fn);return r;}else{return false;}}function getViewportHeight(){if(window.innerHeight!=window.undefined)return window.innerHeight;if(document.compatMode=='CSS1Compat')return document.documentElement.clientHeight;if(document.body)return document.body.clientHeight;return window.undefined;}function getViewportWidth(){if(window.innerWidth!=window.undefined)return window.innerWidth;if(document.compatMode=='CSS1Compat')return document.documentElement.clientWidth;if(document.body)return document.body.clientWidth;return window.undefined;}