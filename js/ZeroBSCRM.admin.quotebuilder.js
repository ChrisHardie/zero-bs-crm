/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.2+
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 22/12/2016
 */

// declare
var quoteTemplateBlocker = false; 

// init
jQuery(document).ready(function(){

	// "use quote builder"
	jQuery('#zbsQuoteBuilderStep2').click(function(){

		// SHOULD show some LOADING here...

		// get content + inject (via ajax)
		zbscrm_getTemplatedQuote(function(){

			// callback, after inject

			// show editor + step 3
			jQuery('#wpzbscsub_quotecontent').show(); // <DAL3
			jQuery('#zerobs-quote-content-edit').show(); // DAL3
			jQuery('#wpzbscsub_quoteactions').show(); // <DAL3
			jQuery('#zerobs-quote-nextstep').show(); // DAL3

			// hide button etc.
			jQuery('#zbs-quote-builder-step-1').slideUp();

			// fix height of content box, after the fact
			setTimeout(function(){

				jQuery('#zbs_quote_content_ifr').css('height','580px');

				// and scroll down to it - fancy!

				// <DAL3
				if (jQuery("#wpzbscsub_quotecontent").length > 0){
				    jQuery('html, body').animate({
				        scrollTop: jQuery("#wpzbscsub_quotecontent").offset().top
				    }, 2000);
				}

				// DAL3
				if (jQuery("#zerobs-quote-content-edit").length > 0){
				    jQuery('html, body').animate({
				        scrollTop: jQuery("#zerobs-quote-content-edit").offset().top
				    }, 2000);
				}


			},0);


		});


	});


	// save quote button - proxy
	jQuery('#zbsQuoteBuilderStep3').click(function(){

		// click save
		jQuery('#publish').click();

	});

	// on change of this, say good bad
	jQuery('#zbsQuoteBuilderEmailTo').keyup(function(){


		var email = jQuery('#zbsQuoteBuilderEmailTo').val();
		if (typeof email == "undefined" || email == '' || !zbscrm_JS_validateEmail(email)){

			// email issue
			jQuery('#zbsQuoteBuilderEmailTo').css('border','2px solid orange');
			jQuery('#zbsQuoteBuilderEmailToErr').show();


		} else {

			// return to normal 
			jQuery('#zbsQuoteBuilderEmailTo').css('border','1px solid #ddd');
			jQuery('#zbsQuoteBuilderEmailToErr').hide();

		}


	});


	// send quote (if possible)
	jQuery('#zbsQuoteBuilderSendNotification').click(function(){

		var quotenotificationproceed = true;
		var email = jQuery('#zbsQuoteBuilderEmailTo').val();
		var qid = jQuery('#zbsQuoteBuilderEmailTo').attr('data-quoteid');
		var cid = jQuery('#zbscq_customer').val();
		if (typeof email == "undefined" || email == '' || !zbscrm_JS_validateEmail(email)){

			quotenotificationproceed = false;

		} 

		// ASSUME qid :o

		if (quotenotificationproceed){

			zerobscrm_js_sendQuoteNotification(email,qid,cid,function(r){

				// sent
		        swal({title: "Success!",text: "Quote has been emailed!",type: "success",confirmButtonText: "OK"});


			},function(r){

				// error
		        swal({title: "Error!",text: "Failed Sending email to customer! If this error persists please contact ZBS CRM support.",type: "error",confirmButtonText: "OK"});

			});


		}


	});

	// if this is set, show the templated dialogs
	if (typeof window.zbscrm_templated != "undefined"){

		// and hide this
		jQuery('#zbs-quote-builder-step-1').hide();

		// show editor + step 3
		jQuery('#wpzbscsub_quotecontent').show(); // <DAL3
		jQuery('#zerobs-quote-content-edit').show(); // DAL3
		jQuery('#wpzbscsub_quoteactions').show(); // <DAL3
		jQuery('#zerobs-quote-nextstep').show(); // DAL3


	}

	// step 3 - copy url
	if (jQuery('#zbsQuoteBuilderURL').length) document.getElementById("zbsQuoteBuilderURL").onclick = function() {
	    this.select();
	    document.execCommand('copy');
	}




});



function zbscrm_appendTextToEditor(text) {
    if (typeof window.parent.send_to_editor == "function") window.parent.send_to_editor(text);
}


function zbscrm_getTemplatedQuote(cb,errcb){

	if (!window.quoteTemplateBlocker){

		// req:
		var custID =  ''; if (jQuery('#zbscq_customer').length > 0) custID = jQuery('#zbscq_customer').val();
		var quoteTemplateID = 0; if (jQuery('#zbs_quote_template_id').length > 0) quoteTemplateID = parseInt(jQuery('#zbs_quote_template_id').val());

		// retrieve deets - <DAL3
		var zbs_quote_title = ''; if (jQuery('#name').length > 0) zbs_quote_title = jQuery('#name').val();
		var zbs_quote_val = ''; if (jQuery('#val').length > 0) zbs_quote_val = jQuery('#val').val();
		var zbs_quote_dt = ''; if (jQuery('#date').length > 0) zbs_quote_dt = jQuery('#date').val();

		//console.log('here',[custID,quoteTemplateID]);

		// DAL3 + we do a more full pass of data
		var fields = {};
        if (zbscrm_JS_DAL() > 2){
			// this'll work excluding checkboxes - https://stackoverflow.com/questions/11338774/serialize-form-data-to-json			  		    
		    jQuery.map(jQuery('#zbs-edit-form').serializeArray(), function(n, i){
		        fields[n['name']] = n['value'];
		    });
		}

		if (!empty(custID)){ 

			// has quote template (not blank)
			if(!empty(quoteTemplateID)){

				// proceed.
			    quoteTemplateAJAX = jQuery.ajax({
			        url: ajaxurl,
			        type: "POST",
			        data: {
				        action: "zbs_get_quote_template",
				        quote_fields:fields, // DAL3 only cares about this

				        // DAL2:
				        cust_id: custID,
				        quote_type: quoteTemplateID,
				        quote_title: zbs_quote_title,
				        quote_val: zbs_quote_val,
				        quote_dt: zbs_quote_dt,

				        // Sec:
				        security: jQuery( '#quo-ajax-nonce' ).val()
				    },
			        dataType: "json"
			    });
			    quoteTemplateAJAX.done(function(e) {

			    	// msg out
			        swal({title: "Success!",text: "Quote Template Populated",type: "success",confirmButtonText: "OK"});

			        setTimeout(function(){
			        // inject
			        zbscrm_appendTextToEditor(e.html);
			    },500);

			        // disable blocker
			        window.quoteTemplateBlocker = false;

			        // callback?
			        if (typeof cb == "function") cb();


			    }), quoteTemplateAJAX.fail(function(e) {

			    	// err
			        swal({title: "Error!",text: "Failed retrieving template! If this error persists please contact ZBS CRM support.",type: "error",confirmButtonText: "OK"});

			        // disable blocker
			        window.quoteTemplateBlocker = false;

			        // callback?
			        if (typeof errcb == "function") errcb();

			    });

			} else {

				// blank template

			        // disable blocker
			        window.quoteTemplateBlocker = false;

			        // callback?
			        if (typeof cb == "function") cb();
				
			}

		} else {

			// err - no cust / quote template id
			if (empty(custID)) swal({title: "Error!",text: "Please Choose a Customer",type: "error",confirmButtonText: "OK"});
            if (empty(quoteTemplateID)) swal({title: "Error!",text: "Please Choose a Template",type: "error",confirmButtonText: "OK"});

	        // disable blocker
	        window.quoteTemplateBlocker = false;

			return false; 

		}

	} // blocker

}

var zerobscrm_js_sendingQuote = false;
function zerobscrm_js_sendQuoteNotification(emailAddress,qid,cid,cb,errcb){

	if (!window.zerobscrm_js_sendingQuote){

		// blocker
		window.zerobscrm_js_sendingQuote = true;

		// check
		if (typeof emailAddress != "undefined" && emailAddress != '' && zbscrm_JS_validateEmail(emailAddress) && typeof qid != "undefined" && qid > 0){

				// postbag!
				var data = {
					'action': 'zbs_quotes_send_quote',
					'sec': window.zbscrmjs_secToken,
					// data
					'em': emailAddress,
					'qid': qid,
					'cid': cid
				};


				// Send 
				jQuery.ajax({
					type: "POST",
					url: ajaxurl, // admin side is just ajaxurl not wptbpAJAX.ajaxurl,
					"data": data,
					dataType: 'json',
					timeout: 20000,
					success: function(response) {

						if (typeof cb == "function") cb(response);

						// unblock
						window.zerobscrm_js_sendingQuote = false;


					},
					error: function(response){ 

						// debug 
						console.error("RESPONSE",response);

						if (typeof errcb == "function") errcb(response);

						// unblock
						window.zerobscrm_js_sendingQuote = false;


					}

				});



		} else {

			// not valid - unblock
			window.zerobscrm_js_sendingQuote = false;

		}


	}

}