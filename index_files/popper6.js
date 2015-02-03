//---------------------------------------------------------------------------------------------------------------------------------------

function observe(element,eventName,handler) {
	if (element.addEventListener){
		element.addEventListener(eventName, handler, false);
	}
	else{
		element.attachEvent("on" + eventName, handler);
	}
}

var BrowserInfo=(function(){
	var ua = navigator.userAgent;
	var isOpera = Object.prototype.toString.call(window.opera) == '[object Opera]';
	var BrowserVersionInfo=navigator.userAgent.match(/(opera|chrome|safari|firefox|msie)[\/\:\s]*(\.?\d+(?:\.\d+)*)/i)||["?",0];
   
	
	return {
		IE:			!!window.attachEvent && !isOpera,
		Opera:		isOpera,
		WebKit:		ua.indexOf('AppleWebKit/') > -1,
		Gecko:		ua.indexOf('Gecko') > -1 && ua.indexOf('KHTML') === -1,
		Firefox:		ua.indexOf('Firefox')>-1,
		MobileSafari:	/Apple.*Mobile/.test(ua),
		Version:		parseInt(navigator.appVersion),
		BrowserName:	BrowserVersionInfo[1],
		BrowserVersion:parseFloat(BrowserVersionInfo[2])
	}
})();

function escapeHTML(s) {
	return s.split('&').join('&amp;').split('<').join('&lt;').split('"').join('&quot;');
}
function qualifyURL(url) {
	var el= document.createElement('div');
	el.innerHTML= '<a href="'+escapeHTML(url)+'">x</a>';
	return el.firstChild.href;
}

//---------------------------------------------------------------------------------------------------------------------------------------

NonPopLinkClick=0;
NonPopLinkBuffer=2000;//milliseconds
PopperLoaded=0;

//FF			Leave Page			Stay on Page
//ChromeReload	Reload this page	Don't Reload
//ChromeLeave	Leave this Page		Stay on this Page
//Safari		Leave Page			Stay on Page
//IE			Leave this Page		Stay on this Page

//Specify default initial alert message if it isn't specified directly in the page
window.StayMessage=window.StayMessage||"Are You Sure!";
window.StayLink=window.StayLink||"";
window.DoublePop=window.DoublePop||1;
CtrlDown=0;
window.PrepopRedirect=window.PrepopRedirect||1;
window.RedirDelay=window.RedirDelay||(BrowserInfo.WebKit?1000:1);
window.LocalDomains=window.LocalDomains||document.location.hostname;

window.AlertBoxAlign=window.AlertBoxAlign||"left";

window.AlertBoxRegEx=window.AlertBoxRegEx||[{Expression:/\n\n/g,Replacement:"\n"}];

window.AlertBoxStyleDefault="";
window.AlertBoxStyleDefault+="-moz-box-sizing:border-box;";
window.AlertBoxStyleDefault+="-webkit-box-sizing:border-box;";
window.AlertBoxStyleDefault+="box-sizing:border-box;";
window.AlertBoxStyleDefault+="position:fixed;";
window.AlertBoxStyleDefault+="top:20px;";
window.AlertBoxStyleDefault+="left:50%;";
window.AlertBoxStyleDefault+="z-index:9999999;";
window.AlertBoxStyleDefault+="padding:1.5em 2em 1.5em;";
//window.AlertBoxStyleDefault+="width:700px;";
window.AlertBoxStyleDefault+="max-width:90%;";
window.AlertBoxStyleDefault+="border-radius:5px 5px 0 0;";
//window.AlertBoxStyleDefault+="font-weight:700;";
window.AlertBoxStyleDefault+="border:1px solid #000;";
window.AlertBoxStyleDefault+="border-top:5px solid red;";
window.AlertBoxStyleDefault+="background-color:#FFF;";
window.AlertBoxStyleDefault+="box-shadow:0 0 10px rgba(0,0,0,.4);";
window.AlertBoxStyleDefault+="white-space:pre;";
window.AlertBoxStyleDefault+="font-size: 16px;";
window.AlertBoxStyleDefault+="line-height: 24px;";
window.AlertBoxStyleDefault+="font-family:Arial,Verdana,sans-serif;";
window.AlertBoxStyleDefault+="text-align:"+window.AlertBoxAlign+";";

window.AlertBoxStyle=window.AlertBoxStyle||window.AlertBoxStyleDefault;



window.AlertBoxOverlayBackground=window.AlertBoxOverlayBackground||"rgba(0,0,0,0);";

window.AlertBoxOverlayStyleDefault="";
window.AlertBoxOverlayStyleDefault+="position:fixed;";
window.AlertBoxOverlayStyleDefault+="top:0;";
window.AlertBoxOverlayStyleDefault+="left:0;";
window.AlertBoxOverlayStyleDefault+="z-index:9999999;";
window.AlertBoxOverlayStyleDefault+="width:100%;";
window.AlertBoxOverlayStyleDefault+="height:100%;";
window.AlertBoxOverlayStyleDefault+="background:"+window.AlertBoxOverlayBackground+";";

window.AlertBoxOverlayStyle=window.AlertBoxOverlayStyle||window.AlertBoxOverlayStyleDefault;


function Pop(event){
	//console.log("Redir:"+(new Date()).getTime());
	CancelPop();
	if(window.StayLink!="")location.href=window.StayLink;
}

function AddFFMessage(){
	var Msg=window.StayMessage;
	if(window.AlertBoxRegEx&&window.AlertBoxRegEx.length){
		for(var ThisRegExNum=0;ThisRegExNum<window.AlertBoxRegEx.length;ThisRegExNum++){
			var ThisRegEx=window.AlertBoxRegEx[ThisRegExNum];
			if(ThisRegEx.Expression&&ThisRegEx.Replacement)Msg=Msg.replace(ThisRegEx.Expression,ThisRegEx.Replacement);
		}
	}	
	
	var AlertBoxOverlay=document.createElement('div');
	AlertBoxOverlay.id="Popper_AlertBoxOverlay";
	AlertBoxOverlay.style=window.window.AlertBoxOverlayStyle;
	document.body.appendChild(AlertBoxOverlay);

	var AlertBox=document.createElement('div');
	AlertBox.id="Popper_AlertBox";
	AlertBox.style=window.window.AlertBoxStyle;
	AlertBox.innerHTML=Msg;
	AlertBoxOverlay.appendChild(AlertBox);
	AlertBox.style.marginLeft="-"+(AlertBox.offsetWidth/2)+"px";



	setTimeout(
		function(){
			window.onbeforeunload=null;
			window.location=window.StayLink;
		},
		5000
	);

}

function RemoveFFMessage(){
	var AlertBoxOverlay=document.getElementById("Popper_AlertBoxOverlay");
	AlertBoxOverlay&&AlertBoxOverlay.parentElement&&AlertBoxOverlay.parentElement.removeChild(AlertBoxOverlay);
	window.onbeforeunload=LeavingPage;
}

//Called when the user tries to leave the page, either going elsewhere or closing page, allows us to prompt them to stay
function LeavingPage(event){
	event=event||window.event;
	if(NonPopLinkClick==0||NonPopLinkClick+NonPopLinkBuffer<(new Date()).getTime()){
		if(window.DoublePop||(BrowserInfo.Gecko&&BrowserInfo.Version>=4)){
			if(BrowserInfo.Firefox&&BrowserInfo.BrowserVersion>=27){
				// Deal with FF 27 breaking everything by using a message div
				AddFFMessage();
			}
			else alert(window.StayMessage);
		}
		if(window.PrepopRedirect&&!BrowserInfo.WebKit&&!(BrowserInfo.Firefox&&BrowserInfo.BrowserVersion>=27)){
			if(window.StayLink!="")location.href=window.StayLink;
		}
		else if(!(BrowserInfo.Firefox&&BrowserInfo.BrowserVersion>=27)){
			setTimeout(function(){Pop(event);},RedirDelay);
		}
		
		return event.returnValue=window.StayMessage;

	}
}

//Called onclick of local links and forms to prevent popup
function CancelPop(){
	NonPopLinkClick=(new Date()).getTime();
}

function CheckKeyDown(event){
	//if ctrl stop bubble
	if(event.keyCode==17)event.cancelBubble=true;
	if(event.keyCode==116||(event.ctrlKey&&event.keyCode==82)){
		CancelPop();
		event.cancelBubble=true;
	}
	//if(event.keyCode==116)CancelPop();
}


function LoadPopper(){
	if(PopperLoaded)return;
	//Create regular expression to determine if url's are local
	var LocalInternet = new RegExp("^(?:javascript.*|https?://"+window.LocalDomains+".*|[^:]+)$");
	var Links=document.body.getElementsByTagName("A");
	var Forms=document.body.getElementsByTagName("FORM");
	PopperLoaded=1;
	
	//Loop thru links
	for(var ThisLinkNum=0;ThisLinkNum<Links.length;ThisLinkNum++){
		ThisLink = Links[ThisLinkNum];
		//Cancel pop on local link
		if(LocalInternet.test(ThisLink.href)){
			observe(ThisLink,"click",CancelPop);
		}
	}
	
	//Loop thru forms
	for(var ThisFormNum=0;ThisFormNum<Forms.length;ThisFormNum++){
		ThisForm = Forms[ThisFormNum];
		//Cancel pop on local form
		if(LocalInternet.test(ThisForm.action)){
			observe(ThisForm,"submit",CancelPop);
		}
	}
	
	observe(document,"keydown",CheckKeyDown);
	observe(window,"keydown",CheckKeyDown);
	
	window.onbeforeunload=LeavingPage;

	//Event.observe(window,"beforeunload",LeavingPage);
	/*
	if(window.onbeforeunload){
		window.oldonbeforeunload=window.onbeforeunload;
		window.onbeforeunload=function(event){
			LeavingPage(event);
			window.oldonbeforeunload(event);
		}
	}
	else window.onbeforeunload=LeavingPage;
	*/
}

observe(document,"DOMContentLoaded",LoadPopper);
observe(window,"load",LoadPopper);
observe(window,"pageshow",RemoveFFMessage);



//   &&BrowserInfo.BrowserVersion>=27 [remove to work on all firefox versions - 3 spots]