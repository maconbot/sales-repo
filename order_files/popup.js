var areYouReallySure = false;
var internalLink = false;

function onbefore_Cancel(){
	internalLink = true;
	window.onbeforeunload = null;
	return true;
}

function unload() {
    if (!areYouReallySure && !internalLink) {
        var e = e || window.event;
        areYouReallySure = 1;
        removePopup(link);
 
        
        alert(str);
        //if ($.browser.mozilla)  alert(str);
        //else document.getElementById('browser_instructions').style.visibility="visible";
        
        var userAgent = navigator.userAgent.toString().toLowerCase();
        
        if (userAgent.indexOf('chrome') !== -1 ) {
            setTimeout('redirect("'+link+'")',300);
        } 
		//else if ($.browser.msie) redirect(link);
        else redirect(link);
        
        if (e) e.returnValue = str;
        
        return str;
    };
}

//function hideInstructions(){$("#browser_instructions").hide();}
function hideInstructions(){$("browser_instructions").hide();}

function removePopup(){setTimeout("hideInstructions()", 3000);}
function redirect (link) {location.href = link;}