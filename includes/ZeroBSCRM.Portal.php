<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.20
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 01/11/16
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */


// returns the directory name for Portal version to use. 
// v3.0+ uses v3, rest v1.0 (was previously 'endpoints')
function zeroBSCRM_portal_verDir(){
	
	global $zbs;
	if ($zbs->isDAL3())
		return 'v3';
	
	return 'v1';
}

// Sorts out the stylesheet includes
function zeroBS_portal_enqueue_stuff() {
    
    wp_enqueue_style('zbs-portal', ZEROBSCRM_URL . 'portal/'.zeroBSCRM_portal_verDir().'/css/ZeroBSCRM.portal.min.css' );
    wp_enqueue_style('zbs-fa', ZEROBSCRM_URL . 'css/font-awesome.min.css' );
    do_action('zbs_enqueue_portal', 'zeroBS_portal_enqueue_stuff');

}
add_action('zbs_enqueue_scrips_and_styles', 'zeroBS_portal_enqueue_stuff');

function zeroBSCRM_portal_themeSupport($classes=array()){

	$theme = wp_get_theme();

	switch($theme->Name){

		case 'Twenty Seventeen':

			$classes[] ='zbs-theme-support-2017';
			break;

		case 'Twenty Nineteen':

			$classes[] = 'zbs-theme-support-2019';
			break;

		case 'Twenty Twenty':

			$classes[] = 'zbs-theme-support-2020';
			break;

		case 'Twenty Twenty One':

			$classes[] = 'zbs-theme-support-2021';
			break;

	}

	return $classes;

}
// Basic theme support (here for now, probs needs option)
add_filter( 'body_class', 'zeroBSCRM_portal_themeSupport' );

#} We can do this below in the templater or templates? add_action( 'wp_enqueue_scripts', 'zeroBS_portal_enqueue_stuff' );
#} ... in the end we can just dump the above line into the templates before get_header() - hacky but works

// Adds the Rewrite Endpoint for the 'clients' area of the CRM. 
#} WH - this is dumped here now, because this whole thing is fired just AFTER init (to allow switch/on/off in main ZeroBSCRM.php)
/*
function zeroBS_portal_rewrite_endpoint(){
	add_rewrite_endpoint( 'clients', EP_ROOT );
}
add_action('init','zeroBS_portal_rewrite_endpoint');
*/

#function zeroBS_add_endpoints() {
#	add_rewrite_endpoint( 'clients', EP_ROOT );
#}
#add_action( 'init', 'zeroBS_add_endpoints');

/* Login link will therefore be
   <site_root>/clients/
   and <site_root>/clients/login 

Will be the equivalent of doing <site_root>?clients=login

Other URLS

What about <site_root>/clients/invoices/invoice_id
*/


// now to locate the templates...
// http://jeroensormani.com/how-to-add-template-files-in-your-plugin/

/**
 * Locate template.
 *
 * Locate the called template.
 * Search Order:
 * 1. /themes/theme/zerobscrm-plugin-templates/$template_name
 * 2. /themes/theme/$template_name
 * 3. /plugins/portal/v3/templates/$template_name.
 *
 * @since 1.2.7
 *
 * @param 	string 	$template_name			Template to load.
 * @param 	string 	$string $template_path	Path to templates.
 * @param 	string	$default_path			Default path to template files.
 * @return 	string 							Path to the template file.
 */
function zeroBS_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	// Set variable to search in zerobscrm-plugin-templates folder of theme.
	if ( ! $template_path ) :
		$template_path = 'zerobscrm-plugin-templates/';
	endif;
	// Set default plugin templates path.
	if ( ! $default_path ) :
		$default_path = ZEROBSCRM_PATH . 'portal/'.zeroBSCRM_portal_verDir().'/templates/'; // Path to the template folder
	endif;
	// Search template file in theme folder.
	$template = locate_template( array(
		$template_path . $template_name,
		$template_name
	) );
	// Get plugins template file.
	if ( ! $template ) :
		$template = $default_path . $template_name;
	endif;
	return apply_filters( 'zeroBS_locate_template', $template, $template_name, $template_path, $default_path );
}

/**
 * Get template.
 *
 * Search for the template and include the file.
 *
 * @since 1.2.7
 *
 * @see zeroBS_get_template()
 *
 * @param string 	$template_name			Template to load.
 * @param array 	$args					Args passed for the template file.
 * @param string 	$string $template_path	Path to templates.
 * @param string	$default_path			Default path to template files.
 */
function zeroBS_get_template( $template_name, $args = array(), $tempate_path = '', $default_path = '' ) {

	if ( is_array( $args ) && isset( $args ) ) :
		extract( $args );
	endif;	
	$template_file = zeroBS_locate_template( $template_name, $tempate_path, $default_path );
	if ( ! file_exists( $template_file ) ) :
		_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file ), '1.0.0' );
		return;
	endif;
	include $template_file;
}




#} MS - can you make this work with templates, couldn't so dumped (dumbly) here for now:
function zeroBSCRM_portalFooter(){
	// won't return ours :/
	//return zeroBS_get_template('footer.php');
	##WLREMOVE
	$showPoweredBy = zeroBSCRM_getSetting('showportalpoweredby');
    if ($showPoweredBy == "1"){ 
    	global $zbs; ?><div class="zerobs-portal-poweredby" style="font-size:11px;position:absolute;bottom:25px;right:50px;font-size:12px;"><?php _e('Powered by',"zero-bs-crm"); ?> <a href="<?php echo $zbs->urls['home']; ?>" target="_blank">ZBS CRM</a></div><?php 
    } 
	##/WLREMOVE
}


// checks if a user has "enabled" or "disabled" access
function zeroBSCRM_portalIsUserEnabled(){

	// cached?
	if (defined('ZBS_CURRENT_USER_DISABLED')) return false;

	global $wpdb;
	$uid = get_current_user_id();
	$cID = zeroBS_getCustomerIDFromWPID($uid);

	// these ones definitely work
    $uinfo = get_userdata( $uid );
    $potentialEmail = ''; if (isset($uinfo->user_email)) $potentialEmail = $uinfo->user_email;
    $cID = zeroBS_getCustomerIDWithEmail($potentialEmail);

	$disabled = zeroBSCRM_isCustomerPortalDisabled($cID);

	if (!$disabled) return true;

	// cache to avoid multi-check
	define('ZBS_CURRENT_USER_DISABLED',true);
	return false;

}


function zeroBS_portalnav($menu = 'dashboard'){
	
	global $wp_query;

	// define
	$zbsWarn = ''; $dash_link = zeroBS_portal_link('dash');
	
	$the_vars = $wp_query->query_vars;

	$nav_items = array(
		'dashboard' 	=> array('name' => 'Dashboard', 'icon' => 'fa-dashboard','slug'=>''),
		'details' 		=> array('name' => 'Your Details', 'icon' => 'fa-user', 'slug' => 'details'),
	);

	if(zeroBSCRM_getSetting('feat_invs') > 0){
		$nav_items['invoices'] = array('name' => 'Invoices', 'icon' => 'fa-file-text-o', 'slug'=>'invoices');
	}

	if(zeroBSCRM_getSetting('feat_quotes') > 0){
		$nav_items['quotes'] 	= array('name' => 'Quotes', 'icon' => 'fa-clipboard', 'slug' => 'quotes');
	}

	if(zeroBSCRM_getSetting('feat_transactions') > 0){
		$nav_items['transactions'] 	= array('name' => 'Transactions', 'icon' => 'fa-shopping-cart', 'slug' => 'transactions');
	}

	$nav_items = apply_filters('zbs_portal_nav_menu_items', $nav_items);

	$nav_items = apply_filters('zbs_portal_nav_menu_items_no_endpoint', $nav_items);

	$allowed_keys = array('dashboard', 'thanks', 'cancel', 'logout', 'pn');

	echo '	<ul id="zbs-nav-tabs">';
		
	$portalPageID = zeroBSCRM_getSetting('portalpage');
	$slug = get_post_field( 'post_name', $portalPageID );
	if (empty($slug)) $slug = 'clients';

	foreach($nav_items as $k => $v){

		//make sure our standard keys stick - only in CPP
		if(class_exists( 'ZeroBSCRM_ClientPortalPro' )){
			if(!in_array($k, $allowed_keys)){
				if(!array_key_exists('show', $v)){
					continue;
				}
			}
		}

	    if(function_exists('zeroBSCRM_clientPortalgetEndpoint') && $k != 'dashboard' && $k != 'logout'){
            $k = zeroBSCRM_clientPortalgetEndpoint($k);
		}

		if($k == 'dashboard'){
			$link = esc_url(home_url() .'/'. $slug);
		}else{
			if(array_key_exists('slug', $v)){
				$link = esc_url(home_url() .'/'. $slug . '/' . $v['slug']);
			}
		}

		if($k == $menu){
			$class='active';
		}else{
			$class='na';
		}
		//produce the menu from the array of menu items (easier to extend :-) ).
		// WH: this assumes icon, otehrwise it'll break! :o
		echo "<li class='".$class."'><a href='" . $link ."'><i class='fa ".$v['icon']."'></i>". __($v['name'],'zero-bs-crm') . "</a></li>";

	}

	$zbs_logout_text = __('Log out',"zero-bs-crm");
	$zbs_logout_text = apply_filters('zbs_portal_logout_text', $zbs_logout_text);

	$zbs_logout_icon = 'fa-sign-out';
	$zbs_logout_icon = apply_filters('zbs_portal_logout_icon', $zbs_logout_icon);

	echo "<li class='na'><a href='". wp_logout_url( $dash_link ) . "'><i class='fa ".$zbs_logout_icon."' aria-hidden='true'></i>" . $zbs_logout_text . "</a></li>";
	echo '</ul>';

}


// first avail action which makes sense is to use template_redirect
// this tries its best to efficiently catch custom endpoints without affecting perf
add_action('template_redirect','zeroBSCRM_clientPortal_catchCustomPageEndpoints');
function zeroBSCRM_clientPortal_catchCustomPageEndpoints(){

	global $post;

	// only fire in most meaningful situations

	// if front-end
	// if $post
	// if is page
	// does have (eventual) parent of client portal page?
	if (!is_admin() &&
		isset($post) && is_object($post) &&
		is_page() && 
		zeroBSCRM_clientPortal_isChildOfPortalPage()){

		// is probably a custom page
		
			// this would work if we were rewrite ruling the page to /clients to fire 'zeroBSCRM_clientPortal_shortcode'
			// ... but we're not
			//add_action('zbs_portal_' . $post->post_name . '_endpoint', 'zeroBSCRM_clientPortal_customPage', 999, 1);

			// so achieve the same, buy overriding template_include
			add_filter('template_include','zeroBSCRM_clientPortal_customPage');

	}

}


#} Version 2.86+ run the client portal from a shortcode (translatable and more flexible)
function zeroBSCRM_portal_endpoint() {
	global $zbs;

	$nav_items = array(
		'dashboard' 	=> array('name' => 'Dashboard', 'icon' => 'fa-dashboard','slug'=>''),
		'details' 		=> array('name' => 'Your Details', 'icon' => 'fa-user', 'slug' => 'details'),
		'thanks' 		=> array('name' => 'Thank you', 'icon' => 'fa-user', 'slug' => 'thanks'),
		'cancel' 		=> array('name' => 'Payment Cancelled', 'icon' => 'fa-user', 'slug' => 'cancel'),
	);
	

	if(zeroBSCRM_getSetting('feat_invs') > 0){
		$nav_items['invoices'] = array('name' => 'Invoices', 'icon' => 'fa-file-text-o', 'slug'=>'invoices');
	}

	if(zeroBSCRM_getSetting('feat_quotes') > 0){
		$nav_items['quotes'] 	= array('name' => 'Quotes', 'icon' => 'fa-clipboard', 'slug' => 'quotes');
	}

	if(zeroBSCRM_getSetting('feat_transactions') > 0){
		$nav_items['transactions'] 	= array('name' => 'Transactions', 'icon' => 'fa-shopping-cart', 'slug' => 'transactions');
	}

	$nav_items = apply_filters('zbs_portal_nav_menu_items', $nav_items);

	foreach ($nav_items as $k => $v){
		if (array_key_exists('slug', $v)){
			add_rewrite_endpoint( $v['slug'], EP_ROOT | EP_PAGES );	
		}	
	}

	$nav_items = apply_filters('zbs_portal_nav_menu_items_no_endpoint', $nav_items);

	//catches the payments.. not modifiable
	add_rewrite_endpoint( 'pn', EP_ROOT | EP_PAGES );

	
}
add_action( 'init', 'zeroBSCRM_portal_endpoint' );

function zeroBSCRM_portal_query_vars( $vars ) {

	$nav_items = array(
		'dashboard' 	=> array('name' => 'Dashboard', 'icon' => 'fa-dashboard','slug'=>''),
		'details' 		=> array('name' => 'Your Details', 'icon' => 'fa-user', 'slug' => 'details'),
		'thanks' 		=> array('name' => 'Thank you', 'icon' => 'fa-user', 'slug' => 'thanks'),
		'cancel' 		=> array('name' => 'Payment Cancelled', 'icon' => 'fa-user', 'slug' => 'cancel'),
	);


	if(zeroBSCRM_getSetting('feat_invs') > 0){
		$nav_items['invoices'] = array('name' => 'Invoices', 'icon' => 'fa-file-text-o', 'slug'=>'invoices');
	}

	if(zeroBSCRM_getSetting('feat_quotes') > 0){
		$nav_items['quotes'] 	= array('name' => 'Quotes', 'icon' => 'fa-clipboard', 'slug' => 'quotes');
	}

	if(zeroBSCRM_getSetting('feat_transactions') > 0){
		$nav_items['transactions'] 	= array('name' => 'Transactions', 'icon' => 'fa-shopping-cart', 'slug' => 'transactions');
	}

	
	$nav_items = apply_filters('zbs_portal_nav_menu_items', $nav_items);

	//query vars here.
	foreach ($nav_items as $k => $v){
		if (array_key_exists('slug', $v)){
			$vars[] = $v['slug'];	
		}	
	}

	$nav_items = apply_filters('zbs_portal_nav_menu_items_no_endpoint', $nav_items);

	//payment pages
	$vars[] = 'pn';   //this is the endpoint for payment notifications.
	
    return $vars;
}
add_filter( 'query_vars', 'zeroBSCRM_portal_query_vars', 0 );



function zeroBSCRM_quote_generatePortalQuoteHTML($quoteID){

	global $post, $zbs, $zbsQuoteData;
	$zbsQuoteData = zeroBS_getQuote($quoteID,true);

	if(isset($_POST["zbs-quote-id"]) && !empty($_POST["zbs-quote-id"]) && $_POST['zbs-save'] == 1 && $_POST["zbs-quote-id"] == $quoteID){
		$quoID = (int)sanitize_text_field($_POST["zbs-quote-id"]);
		zeroBS_markQuoteAccepted($quoID);
		echo '<div class="alert alert-success" style="margin-bottom:5em;">' . __w("Quote accepted","zero-bs-crm"). "</div>";


		//this was missing from the non-AJAX was :( ... suggest review this (fixing security WP redir issue with new DB)	
		#} Send notification to creator/owner of quote
		if ($zbs->isDAL3())
			$quoteCreatorEmail = zeroBS_getObjOwnerWPEmail($quoteID,'zerobs_quote');
		else
			// old cpt way (DAL 1+2)
			$quoteCreatorEmail = zeroBS_getCreatorEmail($quoteID);

		// WH note: this is a funky way of getting the owner id obj:?? to review when we hit it (only saw as redoing dal3 above)
		$userIDobj = get_user_by( 'email', $quoteCreatorEmail );
		$uID = (isset($userIDobj->ID) ? $userIDobj->ID : false);

		#} check if the email is active..
		$active = zeroBSCRM_get_email_status(ZBSEMAIL_QUOTEACCEPTED);

		#} if legit
		if (!empty($quoteCreatorEmail) && $active){

			/* Moved to new method 
		    $body = zeroBSCRM_quote_generateAcceptNotifHTML($quoteID,'',true);

		    #} function zeroBSCRM_mailTracking_addPixel($message='', $who = -1, $user=-1, $email='', $item=-1) 
		    #} who = 1 means this is an email to a CRM team user...
		    $body = zeroBSCRM_mailTracking_addPixel($body, -11, $uID, $quoteCreatorEmail, $quoteID);

            //single template data.
            $form = zeroBSCRM_mailTemplate_get(ZBSEMAIL_QUOTEACCEPTED);
			$subject = $form->zbsmail_subject;

    		#} get the new headers from the template from the new UI for editing these..
    		$headers = zeroBSCRM_mailTemplate_getHeaders(ZBSEMAIL_QUOTEACCEPTED);

 
			wp_mail( $quoteCreatorEmail, $subject, $body, $headers );

			#} function zeroBSCRM_mailTracking_logEmail($ID=-1, $uID=-1, $who = 0, $email='', $item=-1)
			#}QUOTE NOTIFICATION IS EMAIL ID = 2 = ZBSEMAIL_QUOTEACCEPTED
			zeroBSCRM_mailTracking_logEmail(ZBSEMAIL_QUOTEACCEPTED, $uID, -11, $quoteCreatorEmail, $quoteID);
			*/

			// ==========================================================================================
			// =================================== MAIL SENDING =========================================

			// generate html
			$emailHTML = zeroBSCRM_quote_generateAcceptNotifHTML($quoteID,'',true);

              // build send array
              $mailArray = array(
                'toEmail' => $quoteCreatorEmail,
                'toName' => '',
                'subject' => zeroBSCRM_mailTemplate_getSubject(ZBSEMAIL_QUOTEACCEPTED),
                'headers' => zeroBSCRM_mailTemplate_getHeaders(ZBSEMAIL_QUOTEACCEPTED),
                'body' => $emailHTML,
                'textbody' => '',
                'options' => array(
                  'html' => 1
                ),
                'tracking' => array( 
                  // tracking :D (auto-inserted pixel + saved in history db)
                  'emailTypeID' => ZBSEMAIL_QUOTEACCEPTED,
                  'targetObjID' => $uID,
                  'senderWPID' => -11,
                  'associatedObjID' => $quoteID // none
                )
              );

              // Sends email, including tracking, via setting stored route out, (or default if none)
              // and logs trcking :)

				// discern del method
				$mailDeliveryMethod = zeroBSCRM_mailTemplate_getMailDelMethod(ZBSEMAIL_QUOTEACCEPTED);
				if (!isset($mailDeliveryMethod) || empty($mailDeliveryMethod)) $mailDeliveryMethod = -1;

				// send
				$sent = zeroBSCRM_mailDelivery_sendMessage($mailDeliveryMethod,$mailArray);


			// =================================== / MAIL SENDING =======================================
			// ==========================================================================================

			// RELOAD quote (as now accepted)
			$zbsQuoteData = zeroBS_getQuote($quoteID,true);

		}



	}

	$quoteContent = ''; $acceptable = false;
	if ($zbs->isDAL3()){

		// dal3

			// content
			if (isset($zbsQuoteData) && isset($zbsQuoteData['content'])) $quoteContent = $zbsQuoteData['content'];

			//  acceptable?
			if (isset($zbsQuoteData) && (!isset($zbsQuoteData['accepted']) || (isset($zbsQuoteData['accepted']) && empty($zbsQuoteData['accepted'])))) {
				$acceptable = true;
			} else {
				
				// setting this shows it at base of quote, when accepted
				if ($zbsQuoteData['accepted'] > 0) $acceptedDate = $zbsQuoteData['accepted_date'];
			}


	} else {

		// pre dal3

			// content
			if (isset($zbsQuoteData) && isset($zbsQuoteData['quotebuilder']) && isset($zbsQuoteData['quotebuilder']['content'])) $quoteContent = $zbsQuoteData['quotebuilder']['content'];

			//  acceptable?
			if (isset($zbsQuoteData) && isset($zbsQuoteData['meta']) && (!isset($zbsQuoteData['meta']['accepted']) || (isset($zbsQuoteData['meta']['accepted']) && empty($zbsQuoteData['meta']['accepted'])))) $acceptable = true;

	}
	?>
	<div id="zerobs-proposal-<?php echo $quoteID; ?> main" class="zerobs-proposal entry-content hentry" style="margin-bottom:50px;margin-top:0px;">
		
		<div class="zerobs-proposal-body"><?php echo zeroBSCRM_io_WPEditor_DBToHTML($quoteContent); ?></div>
	
		<?php if ($acceptable){ ?>
			<div class="zerobs-proposal-actions" id="zerobs-proposal-actions-<?php echo $quoteID; ?>">
				<h3><?php _e('Accept Quote?',"zero-bs-crm"); ?></h3>
				
				<form id="accept-proposal" method="POST">
					<input type="hidden" id="zbs-quote-id" name="zbs-quote-id" value="<?php echo $quoteID; ?>" >
					<input type="hidden" id="zbs-save" name="zbs-save" value="1" >
					<button class="button btn btn-large btn-success button-success zerobs-proposal-accept" 
				 type="submit"><?php _e('Accept',"zero-bs-crm"); ?></button>

				</form>


		<?php } ?>

		<?php if (isset($acceptedDate)){ ?>

			<div class="zerobs-proposal-actions" id="zerobs-proposal-actions-<?php echo $quoteID; ?>">
				<h3><?php _e('Accepted',"zero-bs-crm"); ?> <?php echo $acceptedDate; ?></h3>
			</div>

		<?php } ?>


		<?php ##WLREMOVE 
		$showPoweredBy = zeroBSCRM_getSetting('showportalpoweredby');
            if ($showPoweredBy == "1"){ global $zbs; ?><div class="zerobs-proposal-poweredby"><?php _e('Proposals Powered by',"zero-bs-crm"); ?> <a href="<?php echo $zbs->urls['home']; ?>" target="_blank">ZeroBS CRM</a></div>
        <?php } ##/WLREMOVE  ?>

	</div>
	<div style="clear:both"></div>
    <?php echo '<script type="text/javascript">var zbsCRM_JS_proposalNonce = \''.wp_create_nonce( "zbscrmquo-nonce" ).'\';var zbsCRM_JS_AJAXURL = \''.esc_url( admin_url('admin-ajax.php') ).'\';</script>'; ?>
	<?php ?>
	<script type="text/javascript" src="<?php echo ZEROBSCRM_URL.'js/ZeroBSCRM.public.proposals.min.js'; ?>"></script>
	<?php
}

// wh: lets us check early on in the action stack to see if page is ours
// ... wrote so could make styles conditional in cpp.
// THIS (zeroBSCRM_clientPortal_isPortalPage) only works after 'wp' in action order (needs wp_query->query_var)
// This is also used by zeroBSCRM_isClientPortalPage in Admin Checks (which affects force redirect to dash, so be careful)
function zeroBSCRM_clientPortal_isPortalPage(){

	if (!is_admin()){

		// front end

			// got endpoint?
			return zeroBSCRM_clientPortal_isOurEndpoint();

	}

	return false;

}


// is a child, or a child of a child, of the client portal main page
function zeroBSCRM_clientPortal_isChildOfPortalPage(){ 
	
	global $post; 
	
	if (!is_admin() && function_exists('zeroBSCRM_getSetting') && zeroBSCRM_isExtensionInstalled('portal')){

		$portalPage = (int)zeroBSCRM_getSetting('portalpage');
		
		if ($portalPage > 0 && isset($post) && is_object($post)){

			if ( is_page() && ($post->post_parent == $portalPage) ) {
					return true;
			} else { 

				// check 1 level deeper
				if ($post->post_parent > 0){

					$parentsParentID = (int)wp_get_post_parent_id($post->post_parent);
					
					if ($parentsParentID > 0 && ($parentsParentID == $portalPage) ) return true;

				}
				return false; 
			}
		}
	}
	return false;

}
 

// returns true if current page loaded has an endpoint that matches ours
// THIS (zeroBSCRM_clientPortal_isPortalPage) only works after 'wp' in action order (needs wp_query->query_var)
function zeroBSCRM_clientPortal_isOurEndpoint(){

	global $wp_query;

	// we get the post id (which will be the page id) + compare to our setting
	$portalPage = zeroBSCRM_getSetting('portalpage');
	if (!empty($portalPage) && $portalPage > 0 && isset($wp_query->post) && gettype($wp_query->post) == 'object' && isset($wp_query->post->ID) && $wp_query->post->ID == $portalPage) return true;

	if (zeroBSCRM_clientPortal_isChildOfPortalPage()) return true;

	/* The following will work for all pages except the dash, and only late in the wp_query action run
	... so I've used the above instead 

	$allowed_endpoints = array(
		'quotes',
		'invoices',
		'transactions',
		'details',
		'thanks',
		'cancel',
		'pn',
		'denied',
	);
	$allowed_endpoints = apply_filters('zbs_portal_endpoints', $allowed_endpoints);
	$the_vars = $wp_query->query_vars;
	foreach($the_vars as $k => $v){
		//run through the query vars and find ours
		if(in_array($k, $allowed_endpoints)){
			//we have a match
			return true;
		}

	}
	*/

	return false;
}

function zeroBSCRM_clientPortal_shortcode(){

	// this checks that we're on the front-end
	// ... a necessary step, because the editor (wp) now runs the shortcode on loading (probs gutenberg)
	// ... and because this should RETURN, instead it ECHO's directly
	// ... it should not run on admin side, because that means is probs an edit page!
	if ( ! is_admin() ) {

		global $wp_query;

		

		$allowed_endpoints = array(
			'quotes',
			'invoices',
			'transactions',
			'details',
			'thanks',
			'cancel',
			'pn',
			'denied',
		);
		$allowed_endpoints = apply_filters('zbs_portal_endpoints', $allowed_endpoints);

		// Define:
		$endpoint_value = '';

		$the_vars = $wp_query->query_vars;


		$our_endpoint = false;
		foreach($the_vars as $k => $v){

	
			//run through the query vars and find ours
			if(in_array($k, $allowed_endpoints)){
				//we have a match
				$our_endpoint = true;
				$endpoint = $k;
				$endpoint_value = $v;
			}

		}

		if(!$our_endpoint){
			$endpoint = 'dashboard';
		}

		//used to control which action to call
		$endpoint = apply_filters('zbs_portal_endpoint_to_action', $endpoint);

		if($endpoint == 'pn'){
			//does not seem to redirect.
			//capture the payment side of things
			do_action('zerobscrm_portal_pn');
		}else{

			if($endpoint_value != ''){
				do_action('zbs_portal_' . $endpoint . '_single_endpoint');
			}else{
				do_action('zbs_portal_' . $endpoint . '_endpoint');
			}
		
		}

	}

	return '';
}

add_shortcode('zerobscrm_clientportal', 'zeroBSCRM_clientPortal_shortcode');


// this catches failed logins, checks if from our page, then redirs
// From mr pippin https://pippinsplugins.com/redirect-to-custom-login-page-on-failed-login/
add_action( 'wp_login_failed', 'zeroBSCRM_portal_login_fail' );  // hook failed login
function zeroBSCRM_portal_login_fail( $username ) {

	$referrer = '';
	if(array_key_exists('HTTP_REFERER', $_SERVER)){
		$referrer = $_SERVER['HTTP_REFERER'];  // where did the post submission come from?
	}

     // if there's a valid referrer, and it's not the default log-in screen + it's got our post
     if ( !empty($referrer) && !strstr($referrer,'wp-login') && !strstr($referrer,'wp-admin') && isset($_POST['fromzbslogin'])) {
          wp_redirect(zeroBS_portal_link('dash') . '?login=failed' );  // let's append some information (login=failed) to the URL for the theme to use
          exit;
	 }
	 
	

}


#} The below loads in based on the endpoint above.
add_action('zbs_portal_dashboard_endpoint', 'zeroBSCRM_clientPortal_dashboard');
function zeroBSCRM_clientPortal_dashboard(){

	//REST endpoint here skips the is_admin test too,
	if(!is_user_logged_in()){
	   return zeroBS_get_template( 'login.php' );
	}else if(!zeroBSCRM_is_rest() && !is_admin()){
		if (!zeroBSCRM_portalIsUserEnabled())

			echo zeroBS_get_template('disabled.php');
		else {

			//add actions for additional content
			do_action('zbs_pre_dashboard_content');
			
			echo zeroBS_get_template('dashboard.php');

			//add actions for additional content
			do_action('zbs_post_dashboard_content');

		}
	}
}



add_action('zbs_portal_details_endpoint', 'zeroBSCRM_clientPortal_details');
function zeroBSCRM_clientPortal_details(){

	if(!is_user_logged_in()){
		return zeroBS_get_template('login.php');
	}else{
		
		if (!zeroBSCRM_portalIsUserEnabled())
			return zeroBS_get_template('disabled.php');
		else {

			//add actions for additional content
			do_action('zbs_pre_details_content');
			
			return zeroBS_get_template('details.php');

			//add actions for additional content
			// WH NOTE: This'll NEVER fire?
			do_action('zbs_post_details_content');

		}
	}
}

add_action('zbs_portal_invoices_endpoint', 'zeroBSCRM_clientPortal_invoices');
function zeroBSCRM_clientPortal_invoices(){

	if(!is_user_logged_in()){
		return zeroBS_get_template('login.php');
	}else{
		if (!zeroBSCRM_portalIsUserEnabled()){
			return zeroBS_get_template('disabled.php');
		} else {

			//add actions for additional content
			do_action('zbs_pre_invoices_content');

			return zeroBS_get_template('invoices.php');

			//add actions for additional content
			// WH NOTE: This'll NEVER fire?
			do_action('zbs_post_invoices_content');

		}
	}

}

add_action('zbs_portal_invoices_single_endpoint', 'zeroBSCRM_clientPortal_single_invoice');
function zeroBSCRM_clientPortal_single_invoice($inv){

	// Does settings allow hashes here?
	$useHash = zeroBSCRM_getSetting('easyaccesslinks');

	if (!is_user_logged_in() && $useHash == "0"){

		// is not logged in, & is not hash-enabled:
		return zeroBS_get_template('login.php');
	
	} else {
	
		// is either logged in, or is hash-enabled

		if (!zeroBSCRM_portalIsUserEnabled() && $useHash == "0"){
		
			return zeroBS_get_template('disabled.php');
		
		} else {

			//add actions for additional content
			do_action('zbs_pre_single_invoices_content');

			// checks out, load inv
			return zeroBS_get_template('single-invoice.php');
	
			//add actions for additional content
			// WH NOTE: This'll NEVER fire?
			do_action('zbs_post_single_invoices_content');

		}
	
	}

}

add_action('zbs_portal_quotes_endpoint', 'zeroBSCRM_clientPortal_quotes');
function zeroBSCRM_clientPortal_quotes(){

	if(!is_user_logged_in()){
		return zeroBS_get_template('login.php');
	}else{
		
		if (!zeroBSCRM_portalIsUserEnabled())
			return zeroBS_get_template('disabled.php');
		else {

			//add actions for additional content
			do_action('zbs_pre_quotes_content');

			return zeroBS_get_template('quotes.php');

			//add actions for additional content
			// WH NOTE: This'll NEVER fire?
			do_action('zbs_post_quotes_content');

		}
	}

}

add_action('zbs_portal_quotes_single_endpoint', 'zeroBSCRM_clientPortal_single_quote');
function zeroBSCRM_clientPortal_single_quote(){

	// Does settings allow hashes here?
	$useHash = zeroBSCRM_getSetting('easyaccesslinks');

	if (!is_user_logged_in() && $useHash == "0"){

		// is not logged in, & is not hash-enabled:
		return zeroBS_get_template('login.php');
	
	} else {
	
		// is either logged in, or is hash-enabled

		if (!zeroBSCRM_portalIsUserEnabled() && $useHash == "0"){
		
			return zeroBS_get_template('disabled.php');
		
		} else {

			//add actions for additional content
			do_action('zbs_pre_single_quotes_content');

			// checks out, load inv
			return zeroBS_get_template('single-quote.php');

			//add actions for additional content
			// WH NOTE: This'll NEVER fire?
			do_action('zbs_post_single_quotes_content');

		}
	
	}
}

add_action('zbs_portal_transactions_endpoint', 'zeroBSCRM_clientPortal_transactions');
function zeroBSCRM_clientPortal_transactions(){

	if(!is_user_logged_in()){
		return zeroBS_get_template('login.php');
	}else{
		
		if (!zeroBSCRM_portalIsUserEnabled())
			return zeroBS_get_template('disabled.php');
		else {

			//add actions for additional content
			do_action('zbs_pre_quotes_content');

			return zeroBS_get_template('transactions.php');

			//add actions for additional content
			// WH NOTE: This'll NEVER fire?
			do_action('zbs_post_quotes_content');

		}
	}
}

add_action('zbs_portal_thanks_endpoint', 'zeroBSCRM_clientPortal_thanks');
function zeroBSCRM_clientPortal_thanks(){
	// Does settings allow hashes here?
	$useHash = zeroBSCRM_getSetting('easyaccesslinks');

	if(!is_user_logged_in()){
	   echo zeroBS_get_template( 'login.php' );
	}else{
		if (!zeroBSCRM_portalIsUserEnabled() && $useHash == "0"){
			echo zeroBS_get_template('disabled.php');
		}else {

			//add actions for additional content
			do_action('zbs_pre_thanks_content');

			echo zeroBS_get_template('thank-you.php');

			//add actions for additional content
			do_action('zbs_post_thanks_content');

		}
	}

}

add_action('zbs_portal_cancel_endpoint', 'zeroBSCRM_clientPortal_cancel');
function zeroBSCRM_clientPortal_cancel(){

	if(!is_user_logged_in()){
	   echo zeroBS_get_template( 'login.php' );
	}else{
		if (!zeroBSCRM_portalIsUserEnabled() && $useHash == "0"){
			echo zeroBS_get_template('disabled.php');
		} else {

			//add actions for additional content
			do_action('zbs_pre_cancel_content');

			echo zeroBS_get_template('cancelled.php');

			//add actions for additional content
			do_action('zbs_post_cancel_content');

		}

	}

}

function zeroBSCRM_clientPortal_customPage(){

	// because we're hijacking the running roder with template_include
	// .. we need get_header and get_footer here
	// but otherwise this mimics actions such as zeroBSCRM_clientPortal_single_invoice
	get_header();

	if (!is_user_logged_in()){
		echo zeroBS_get_template('login.php');
	} else{
		
		if (!zeroBSCRM_portalIsUserEnabled())
			echo zeroBS_get_template('disabled.php');
		else {

			// add actions for additional content
			do_action('zbs_pre_custompage_content');
			
			echo zeroBS_get_template('custom-page.php');

		}
	}

	get_footer();

}

// upsell shown to admins across whole portal as they view as admin
function zeroBSCRM_portal_adminMsg(){

	global $zbs;

	// temp fix
    if (current_user_can( 'admin_zerobs_manage_options' ) && !function_exists('zeroBSCRM_cpp_register_endpoints')){// !zeroBSCRM_isExtensionInstalled('clientportalpro')){

	 ##WLREMOVE ?>

	<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery('#zbs-close-cpp-note').click(function(){
				jQuery('.zbs-client-portal-pro-note').remove();
			});
		});
	</script>
	<?php ##/WLREMOVE

	}

	return '';

}

function zeroBSCRM_portal_adminPreviewMsg($cID=-1,$extraCSS=''){
	
	// permalinks warning
	if (function_exists('zeroBSCRM_portal_plainPermaCheck')) zeroBSCRM_portal_plainPermaCheck();

 	//removed. is garish on first install. Upsell in better ways.
}

function zeroBSCRM_portal_plainPermaCheck(){

	// permalinks warning
	if (current_user_can( 'admin_zerobs_manage_options' )){
	   //allow for anyone who may be testing with DEFAULT permalinks on (but they should really NOT use default in production)
	   $permalink_structure = get_option('permalink_structure');
	   if($permalink_structure == ''){
		   $zbsWarn = __("Please Note: You are using PLAIN permalinks. Please switch to %postname% for the proper Client Portal experience (WordPress Settings->Permalinks). Some features may not work in plain permalink mode. This Permalink mode is not recommended for production installations.","zero-bs-crm"); 
               ?>
			   <div style="margin:20px;padding:10px;background:red;color:white;text-align:center;">
				   <?php echo $zbsWarn; ?>
			   </div>
		   <?php
	   }
	}

}


//the invoice endpoint
function zeroBSCRM_portal_get_invoice_endpoint(){
    
    // default 
    $endpoint = 'invoices';
	
    // set in cpp?
	if (function_exists('zeroBSCRM_clientPortalgetEndpoint')) $endpoint = zeroBSCRM_clientPortalgetEndpoint('invoices');

	// catch any somehow empties
	if (empty($endpoint)) $endpoint = 'invoices';

	return $endpoint;
}

//the quote endpoint
function zeroBSCRM_portal_get_quote_endpoint(){
    
    // default 
    $endpoint = 'quotes';
	
    // set in cpp?
	if (function_exists('zeroBSCRM_clientPortalgetEndpoint')) $endpoint = zeroBSCRM_clientPortalgetEndpoint('quotes');

	// catch any somehow empties
	if (empty($endpoint)) $endpoint = 'quotes';

	return $endpoint;
}

#} New fucntions here. Used as NAMED in WooSync. Please do not rename and not tell me as need to update WooSync if so
#} 1. The invoice list function 
/**
 * 
 * @param $link and $endpoint as they will differ between Portal and WooCommerce My Account
 * 
 */
function zeroBSCRM_portal_list_invoices($link = '', $endpoint = ''){

	global $zbs;

	if ($zbs->isDAL3())
		return zeroBSCRM_portal_list_invoices_v3($link,$endpoint);
	else
		return zeroBSCRM_portal_list_invoices_prev3($link,$endpoint);

} 

#} 1. The invoice list function (v3)
/**
 * 
 * @param $link and $endpoint as they will differ between Portal and WooCommerce My Account
 * 
 */
function zeroBSCRM_portal_list_invoices_v3($link = '', $endpoint = ''){

	global $wpdb;
	$uid = get_current_user_id();
	$uinfo = get_userdata( $uid );
	$cID = zeroBS_getCustomerIDWithEmail($uinfo->user_email);

	$customer_invoices = zeroBS_getInvoicesForCustomer($cID,true,100,0,false);


	if(count($customer_invoices) > 0){

		// titled v3.0
		?><h2><?php _e('Invoices','zero-bs-crm'); ?></h2><?php

		echo '<table class="table zbs-invoice-list">';

		echo '<th>' . __('#','zerobscrm') . '</th>';
		echo '<th>' . __('Date','zerobscrm') . '</th>';
		echo '<th>' . __('Due Date','zerobscrm') . '</th>';
		echo '<th>' . __('Total','zerobscrm') . '</th>';
		echo '<th>' . __('Status','zerobscrm') . '</th>';
	
		do_action('zbs-extra-invoice-header-table');

		foreach($customer_invoices as $cinv){

			//invstatus check
			$inv_status = $cinv['status'];

			// id
			$idStr = '#'.$cinv['id'];
			if (isset($cinv['id_override']) && !empty($cinv['id_override'])) $idStr = $cinv['id_override'];

			// skip drafts
			if ($inv_status == __('Draft','zero-bs-crm')){
				continue;
			}

			if (!isset($cinv['due_date']) || empty($cinv['due_date']) || $cinv['due_date'] == -1)
				//no due date;
				$due_date_str = __("No due date", "zero-bs-crm");
			else
				$due_date_str = $cinv['due_date_date'];
			
        	// view on portal (hashed?)
        	$invoiceURL = zeroBSCRM_portal_linkObj($cinv['id'],ZBS_TYPE_INVOICE); //zeroBS_portal_link('invoices',$invoiceID);

			$idLinkStart = ''; $idLinkEnd = '';
			if (!empty($invoiceURL)){
				$idLinkStart = '<a href="'. $invoiceURL .'">'; $idLinkEnd = '</a>';
			}

			echo '<tr>';
				echo '<td>'. $idLinkStart.$idStr . ' '. __('(view)') . $idLinkEnd.'</td>';
				echo '<td>' . $cinv['date_date'] . '</td>';
				echo '<td>' . $due_date_str . '</td>';
				echo '<td>' . zeroBSCRM_formatCurrency($cinv['total']) . '</td>';
				echo '<td><span class="status '. $inv_status .'">'.$cinv['status'].'</span></td>';

				do_action('zbs-extra-invoice-body-table', $cinv['id']);

			//	echo '<td class="tools"><a href="account/invoices/274119/pdf" class="pdf_download" target="_blank"><i class="fa fa-file-pdf-o"></i></a></td>';
			echo '</tr>';
		}
		echo '</table>';
	}else{
		echo _e('You do not have any invoices yet.',"zero-bs-crm"); 
	}

}

#} 1. The invoice list function (pre v3)
/**
 * 
 * @param $link and $endpoint as they will differ between Portal and WooCommerce My Account
 * 
 */
function zeroBSCRM_portal_list_invoices_prev3($link = '', $endpoint = ''){

	global $wpdb;
	$uid = get_current_user_id();
	$uinfo = get_userdata( $uid );
	$cID = zeroBS_getCustomerIDWithEmail($uinfo->user_email);
	$currencyChar = zeroBSCRM_getCurrencyChr();

	$customer_invoices = zeroBS_getInvoicesForCustomer($cID,true,100,0,false);


	if(count($customer_invoices) > 0){
		echo '<table class="table zbs-invoice-list">';

		echo '<th>' . __('#','zerobscrm') . '</th>';
		echo '<th>' . __('Date','zerobscrm') . '</th>';
		echo '<th>' . __('Due Date','zerobscrm') . '</th>';
		echo '<th>' . __('Total','zerobscrm') . '</th>';
		echo '<th>' . __('Status','zerobscrm') . '</th>';
	
		do_action('zbs-extra-invoice-header-table');

		foreach($customer_invoices as $cinv){

			//invstatus check
			$inv_status = strtolower($cinv['meta']['status']);

			if($inv_status == 'draft'){
				continue;
			}
			//defaults for meta values
			if(!array_key_exists('date', $cinv['meta'])){
				$cinv['meta']['date'] = date('m/d/Y h:i:s a', time());
			}

			if(!array_key_exists('due', $cinv['meta'])){
				$cinv['meta']['due'] = -1;
			}


			/*
			if(array_key_exists('date', $cinv['meta'])){
				$inv_date = date_create($cinv['meta']['date']);
			}

			
			if($cinv['meta']['due'] == -1){
				//no due date;
				$zbs_when_due = __("No due date", "zero-bs-crm");
			}else{
				$due_date = date_create($cinv['meta']['date']);
				$str = $cinv['meta']['due'] . ' days';
				if ($due_date instanceof DateTime){
					date_add($due_date, date_interval_create_from_date_string($str));
					$zbs_when_due = date_format($due_date, 'd M Y');
				}
				$zbs_when_due = '';
			}
			*/

			// Replaced this with code taken from fixed AJAX listViewRetrieve 2.99.9.10 Oct 19
			//$invoice_due_date = zeroBSCRM_locale_utsToDate(strtotime($cinv['meta']['date']));
			$invoiced_uts = zeroBSCRM_locale_dateToUTS($cinv['meta']['date']);
			$invoiceDate = zeroBSCRM_date_i18n(-1, $invoiced_uts);

			// due?
			if (isset($cinv['meta']['due'])) {

				$due = (int)$cinv['meta']['due'];

				if ($due <= 0){

					$dueDate = $invoiceDate;

				} else {

					// calc
					$dueDate = zeroBSCRM_date_i18n(-1, ($invoiced_uts+($due*86400)));

				}

			}

			echo '<tr>';
				echo '<td><a href="'. esc_url($link .  $endpoint .  '/' . $cinv['id']) .'">#'. $cinv['zbsid'] . __(' (view)') . '</a></td>';
				echo '<td>' . $invoiceDate . '</td>';
				echo '<td>' . $dueDate . '</td>';
				echo '<td>' . zeroBSCRM_formatCurrency($cinv['meta']['val']) . '</td>';
				echo '<td><span class="status '. $inv_status .'">'.$cinv['meta']['status'].'</span></td>';

				do_action('zbs-extra-invoice-body-table', $cinv['id']);

			//	echo '<td class="tools"><a href="account/invoices/274119/pdf" class="pdf_download" target="_blank"><i class="fa fa-file-pdf-o"></i></a></td>';
			echo '</tr>';
		}
		echo '</table>';
	}else{
		echo _e('You do not have any invoices yet.',"zero-bs-crm"); 
	}

}

#} 2. The single invoice display
function zeroBSCRM_portal_single_invoice($invID = -1,$invHash=''){

	// WH moved the security checks back out of this func, back into single-invoice.php,
	// ... because we had hash based sec checks on v3.0 branch, which superceded the ones here.
	// ... if this needs security checks (if used anywhere other than single-invoice.php) please bring to WH attention
	echo zeroBSCRM_invoice_generatePortalInvoiceHTML($invID);

}


/* replaced by above
// Retrieves a Portal URL
function zeroBSCRM_portalPermalink($type='dash',$objID=-1){

	$rootStem = 'clients';

	switch ($type){

		case 'dash':
				return home_url('/clients/');
			break;
		case 'quote':
		case 'quotes':

			if ($objID == -1 || $objID <= 0) 
				return home_url('/clients/quotes/');
			else
				return home_url('/clients/quotes/'.$objID);

			break;



	}

	return home_url('/#notfound');
}
*/
