/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.2.5
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 19/01/2017
 */
var zbsCRM_JS_proposalBlocker = false;
jQuery(document).ready(function(){

	jQuery('.zerobs-proposal-accept').click(function(){

		// do smt?
		if (!window.zbsCRM_JS_proposalBlocker){

			// block double clicks etc.
			window.zbsCRM_JS_proposalBlocker = true;

			// retrieve id
			var quoID = parseInt(jQuery(this).attr('data-zbsquoid'));
			var quoHash = jQuery(this).attr('data-zbsquohash');
			var signer = jQuery('#zerobs-proposal-signer-' + quoID).val();

			// validate email
			if (!zbscrm_JS_validateEmail(signer)){

				// highlight field
				jQuery('#zerobs-proposal-signer-' + quoID).addClass('zerobs-proposal-highlight');

				// show msg
				jQuery('#zerobs-proposal-signererr-' + quoID).show();

			} 

			// we good?
			if (zbscrm_JS_validateEmail(signer) && typeof quoID != "undefined" && quoID > 0){

				zbsCRM_JS_acceptProp(quoHash,quoID,signer,function(r){

					console.log("r",r);

					if (typeof r.success != "undefined"){

						// localise
						var quoteID = quoID;

						// good - fade out actions + say 'accepted, thanks'
						jQuery('#zerobs-proposal-actions-'+quoteID).slideUp();
						jQuery('#zerobs-proposal-fini-'+quoteID).slideDown();

						// unblock
						window.zbsCRM_JS_proposalBlocker = false;

					} else {

						// err actually, tho this should be passed via header 500 so it uses the below
						// for now, just do same here :/

						// localise
						var quoteID = quoID;

						// fail
						jQuery('#zerobs-proposal-err-'+quoteID).slideDown();

						// unblock
						window.zbsCRM_JS_proposalBlocker = false;

					}


				},function(r){

					// localise
					var quoteID = quoID;

					// fail
					jQuery('#zerobs-proposal-err-'+quoteID).slideDown();

					// unblock
					window.zbsCRM_JS_proposalBlocker = false;


				})


			}


		} // / not blocked

	});

	// correct
	jQuery('.zerobs-proposal-signer').keyup(function(event) {
		
		var signer = jQuery(this).val();

		// validate email
		if (zbscrm_JS_validateEmail(signer)){

			// unhighlight field
			jQuery(this).removeClass('zerobs-proposal-highlight');
			// show msg
			jQuery('.zerobs-proposal-signererr',jQuery(this).parent()).hide();

		} 

	});

});

function zbsCRM_JS_acceptProp(quoHash,quoID,signer,cb,errcb){


		// postbag!
		var data = {
			'action': 'zbs_quotes_accept_quote',
			'sec': window.zbsCRM_JS_proposalNonce,
			// data
			'qhash': quoHash,
			'qid': quoID,
			'signer': signer
		};


		// Send 
		jQuery.ajax({
			type: "POST",
			url: window.zbsCRM_JS_AJAXURL, 
			"data": data,
			dataType: 'json',
			timeout: 20000,
			success: function(response) {

				if (typeof cb == "function") cb(response);


			},
			error: function(response){ 

				// debug 
				console.error("RESPONSE",response);

				if (typeof errcb == "function") errcb(response);


			}

		});
}