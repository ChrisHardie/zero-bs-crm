/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.2
 *
 * Copyright 2017 ZeroBSCRM.com
 *
 * Date: 11/08/2017
 */
 jQuery(document).ready(function(){

	//any code in here specific for edit company page
	console.log("======== COMPANY EDIT SCRIPTS =============");

	jQuery('.send-sms-none').bind("click",function(e){

		console.log("SMS button clicked");

				swal(
				    'Twilio Extension Needed!',
				    'To SMS your contacts you need the <a target="_blank" style="font-weight:900;text-decoration:underline;" href="https://zerobscrm.com/extension-bundles/">Twilio extension</a> (included in our Entrepreneurs Bundle)',
				    'info'
				);
				


	});

	// automatic "linkify" check + add
	// note - not certain if this may interfere with some, if so, exclude via class (as they'll be added e.g. email)
	jQuery('.zbs-text-input input').keyup(function(){

		zeroBSCRMJS_initLinkify(this);

	});
	// fire linkify for all inputs on load
	jQuery('.zbs-text-input input').each(function(ind,ele){

		zeroBSCRMJS_initLinkify(ele);

	});


});



 function zeroBSCRMJS_initLinkify(ele){
 	// find any links?
		var v = jQuery(ele).val(); var bound = false;
		if (v.length > 5) {

			var possMatch = zeroBSCRMJS_retrieveURLS(v);

			if (typeof possMatch == "object" && typeof possMatch !== "null"){

				if (possMatch != null && possMatch[0] != "undefined"){

					// remove any prev
					jQuery('.zbs-linkify',jQuery(ele).parent()).remove();

					// linkify
					jQuery(ele).parent().addClass('ui action input fluid').append('<button class="ui icon button zbs-linkify" type="button" data-url="' + possMatch[0] + '" title="Go To ' + possMatch[0] + '"><i class="linkify icon"></i></button>');

					// rebind
					zeroBSCRMJS_bindLinkify();

					bound = true;

				}

			} else {

				/* not inc in rollout - wait for MS email func + tie in

				// emails
				var possMatch = zeroBSCRMJS_retrieveEmails(v);

				if (possMatch != null && possMatch[0] != "undefined"){

					// remove any prev
					jQuery('.zbs-linkify',jQuery(ele).parent()).remove();

					// linkify
					jQuery(ele).parent().addClass('ui action input').append('<button class="ui icon button zbs-linkify" type="button" data-url="mailto:' + possMatch[0] + '" title="Email "' + possMatch[0] + '""><i class="mail outline icon"></i></button>');

					// rebind
					zeroBSCRMJS_bindLinkify();

					bound = true;

				}

				*/


			}
		}

		// unlinkify if not
		if (!bound) {
			jQuery('.zbs-linkify',jQuery(ele).parent()).remove();
			jQuery(ele).parent().removeClass('ui action input fluid');
		}
 }

 function zeroBSCRMJS_bindLinkify(){

	jQuery('.zbs-linkify').unbind('click').click(function(){

		var u = jQuery(this).attr('data-url');
		if (typeof u != "undefined" && u != '') window.open(u, '_blank');

	});
 }