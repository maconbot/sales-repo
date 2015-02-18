function sendOfferingForm(form_id, url)
{
	var form = jQuery( '#form_'+form_id );
	var inputs = jQuery( ':input', form );
	var terms = jQuery( '#terms_'+form_id );
	var data = {
		form_id: form_id,
		source: window.location.href
	};
	
	for( var i = 0; i < inputs.length; i++ )
	{
		var input = inputs[i];
		data[input.id] = input.value;
	}
	
	if( terms.length > 0 )
	{
		if( terms[0].checked == false )
		{
			alert( 'You must accept the terms and conditions.' );
			return false;
		}
	}
	
	showProgressButton( form_id );
	jQuery.post( url, data, offeringFormCB, 'json' );
}

function offeringFormCB(response)
{
	if(response.status == 'error')
	{
		showSubmitButton(response.form_id);
		alert(response.msg);
		return false;
	}
	
	usDoRedirect = false; // in case there is an redirect widget
	window.location.href = response.msg;
}

function showProgressButton( form_id )
{
	var progress = jQuery('#progress-bar'+form_id);
	var btn = jQuery('#submit-button'+form_id);
	var height = btn.height();

	var diff = (height-16)/2;
	
	if( diff < 0 )
	{
		diff = -1*diff;
		progress.css({height: diff+'px', margin: '0px auto'});
	}
	else
	{
		progress.css('margin', diff+'px auto');
	}
	
	btn.hide();
	progress.show();
}

function showSubmitButton(form_id)
{
	jQuery('#progress-bar'+form_id).hide();
	jQuery('#submit-button'+form_id).show();
}

function getStateInput( form_id, url, us_state_selected )
{
	var data = {
		form_id: form_id,
		us_state_selected: us_state_selected,
		country_abbr: jQuery('#country_'+form_id).val()
	};
	
	jQuery.post( url, data, getStateInputCB, 'json' );
}

function getStateInputCB( response )
{
	jQuery('#state_'+response.form_id).parent().html(response.html_input);
}