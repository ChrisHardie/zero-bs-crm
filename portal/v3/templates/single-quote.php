<?php
/**
 * Single Quote Template
 *
 * The Single Quote Portal Page 
 *
 * @author 		ZeroBSCRM
 * @package 	Templates/Portal/Quote
 * @see			https://zerobscrm.com/kb/
 * @version     3.0
 * 
 */



if ( ! defined( 'ABSPATH' ) ) exit; // Don't allow direct access
do_action( 'zbs_enqueue_scrips_and_styles' );
//zeroBS_portal_enqueue_stuff();

	//not used anymore? .. leave in just in case. remove later.
	$queryStr = get_query_var( 'clients' );
	#} Break it up if / present
	if (strpos($queryStr,'/'))
		$zbsPortalRequest = explode('/',$queryStr);
	else
		#} no / in it, so must just be a 1 worder like "invoices", here just jam in array so it matches prev exploded req.
		$zbsPortalRequest = array($queryStr);

	// ! end not used

	//moved into func
    if(function_exists('zeroBSCRM_clientPortalgetEndpoint')){
        $quote_endpoint = zeroBSCRM_clientPortalgetEndpoint('quotes');
    }else{
        $quote_endpoint = 'quotes';
    }


	// v3.0 Hashes (or ID)
	$quoteIDOrHash = sanitize_text_field( get_query_var( $quote_endpoint ) );
	$quoteHash = ''; $quoteID = -1;
	// discern if hash or id
	if (substr($quoteIDOrHash,0,3) == 'zh-'){
		
		// definitely hash
		$quoteHash = substr($quoteIDOrHash,3);

	} else {

		// probably ID
		$quoteID = (int)$quoteIDOrHash;

	}

	// settings
	$useHash = zeroBSCRM_getSetting('easyaccesslinks');

	// ==========================================
	// ==== Process hashed url
	// ==========================================

	$canView = false;
	$fullWidth = false;
	$showNav = false;
	$showViewingAsAdmin = false;
	$zbsWarn = '';

	// Using Easy-pay/hashed urls?
	if ($useHash == "1"){

		// ============== Brute force blocking

			// is this request from a blocked source?
			// (if tries to get *5?* hashes which are incorrect, it'll block that IP for 48h)
			if (zeroBSCRM_security_blockRequest('quoeasy')){			

				// BLOCKED (this is a nefarious user.)
				$canView = false;
				$showNav = false;


			} else {

				// NOT BLOCKED

		// ============== / Brute force blocking

				// log request (start)
				$requestID = zeroBSCRM_security_logRequest('quoeasy',$quoteHash,$quoteID);

				// hash okay? blocked?
				$hashOK = zeroBSCRM_quotes_getFromHash($quoteHash,-1);

				if ($hashOK['success'] == 1){

					// all checks out 
					// ... this has been accessed via clients/invoices/bF6wj0pGO74eXQpYIQZ

						// log request (fini) (passed)
						zeroBSCRM_security_finiRequest($requestID);

						// prep data
						$quoteID = $hashOK['data']['ID'];
						$canView = true;
						$cID = -1;
						$fullWidth = true;

						// this shows to admins viewing via portal a "this is on the portal as admin" msg
						$contactID = zeroBSCRM_quote_getContactAssigned((int)$quoteID);
						if ($contactID != $cID && zeroBSCRM_permsQuotes()){
							$showViewingAsAdmin = true;
						}


				} else {

					// Hash failed.

					// fall back to showing on the page
					// (if has quoteID + logged in)
					if (is_user_logged_in() && $quoteID > 0){

						$uid = get_current_user_id();
						$uinfo = get_userdata( $uid );
						$cID = zeroBS_getCustomerIDWithEmail($uinfo->user_email);
						$aID = zeroBSCRM_quote_getContactAssigned((int)$quoteID);

						if($aID == $cID || zeroBSCRM_permsQuotes()){

							// turned out okay, user can view this inv, irrelevant of being in easypay mode,
							// ... this has been accessed via clients/invoices/123

							// log request (fini) (passed)
							zeroBSCRM_security_finiRequest($requestID);
							$canView = true;
							$showNav = true;

						} else {

							echo 'x'; exit();
							// nope, this user shouldn't be seeing
							$canView = false;

						}

					}
				}

			} // / IF NOT BLOCKED (brute force attempts)

	} else {

		// Normal mode
		// ... this should have been been accessed via clients/invoices/123

		// got inv id at least?		
		if ($quoteID > 0){
		
			$uid = get_current_user_id();
			$uinfo = get_userdata( $uid );
			$cID = zeroBS_getCustomerIDWithEmail($uinfo->user_email);
			$contactID = zeroBSCRM_quote_getContactAssigned((int)$quoteID);

			if ($contactID == $cID || zeroBSCRM_permsQuotes()){
				//we are admin with manage invoice perms can also view
				$canView = true;
			}

			// this shows to admins viewing via portal a "this is on the portal as admin" msg
			if ($contactID != $cID && zeroBSCRM_permsQuotes()){
				$showViewingAsAdmin = true;
			}

			$portalPage = zeroBSCRM_getSetting('portalpage');
			$portalLink = get_page_link($portalPage);	
			$showNav = true;

		}

	}

	// ==========================================
	// ==== / Process hashed url
	// ==========================================


	// mikes perma check
	if(isset($_GET['zbsid'])){
			$zbsClientID 	= (int)$_GET['zbsid'];
			$zbsWarn = __("You are using PLAIN permalinks. Please switch to %postname% for the proper Client Portal experience. Some features may not work in plain permalink mode","zero-bs-crm"); 
	}

?>
<style>
.zerobs-proposal-body{
    font-size: 16px;
    background: #FFFFFF;
    box-shadow: 0px 1px 2px 0 rgba(34,36,38,0.15);
    margin: 1rem 0em;
    padding: 20px;
    border-radius: 0.28571429rem;
    border: 1px solid rgba(34,36,38,0.15);
    margin-top: -32px;
}
.zerobs-proposal-body li, .zerobs-proposal-body li span{
	padding:5px;
	line-height: 18px;
}
.zerobs-proposal-body table td, table tbody th {
    border: 1px solid #ddd;
    padding: 8px;
    font-size: 16px;
}
.zerobs-proposal-body ul{
	padding-left:20px;
}
</style>


<div id="zbs-main" class="zbs-site-main">
	<div class="zbs-client-portal-wrap main site-main zbs-post zbs-hentry">
		<?php
		// perms to see nav?
		if ($showNav){
			zeroBS_portalnav($quote_endpoint);
		}

		// perms to see quote?
		if (!$canView){
			echo '<div class="zbs-alert-danger">' . __("<b>Error:</b> You are not allowed to view this Quote","zero-bs-crm") . '</div>';
		} else { 

				?><div class="zbs-portal-wrapper zbs-portal-quote-single"><?php

				// if viewing as admin
			 	if($showViewingAsAdmin){  
			 		?><div class='wrapper' style="padding-left:20px;padding-right:20px;padding-bottom:20px;">
						<div class='alert alert-info'>
							<?php _e('You are viewing this quote in the Client Portal','zero-bs-crm'); ?>
							<br />
							[<?php _e('This message is only shown to admins','zero-bs-crm'); ?>]
							<?php ##WLREMOVE ?>
							<br /><a style="color:orange;font-size:18px;" href="https://zerobscrm.com/kb/knowledge-base/how-does-the-client-portal-work/" target="_blank"><?php _e('Learn more about the client portal','zero-bs-crm'); ?></a>
							<?php ##/WLREMOVE ?>
						</div>
						<?php zeroBSCRM_portal_adminMsg(); ?>
						<?php if (!empty($zbsWarn)) { ?>
						<div style="margin:20px;padding:10px;background:red;color:white;text-align:center;">
							<?php echo $zbsWarn; ?>
						</div>
						<?php } // if $zbsWarn ?>
					</div><?php 
				} ?>
				
					<?php echo zeroBSCRM_quote_generatePortalQuoteHTML($quoteID); ?>

				</div>

		<?php } ?>
		<div style="clear:both"></div>
		<?php zeroBSCRM_portalFooter(); ?>
	</div>
</div>