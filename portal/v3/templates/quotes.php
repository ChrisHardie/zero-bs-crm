<?php
/**
 * Quote List Page
 *
 * This list of Quotes for the Portal
 *
 * @author 		ZeroBSCRM
 * @package 	Templates/Portal/Quotes
 * @see			https://zerobscrm.com/kb/
 * @version     3.0
 * 
 */



if ( ! defined( 'ABSPATH' ) ) exit; // Don't allow direct access

$ZBSuseQuotes = zeroBSCRM_getSetting('feat_quotes');
//zeroBS_portal_enqueue_stuff();

do_action( 'zbs_enqueue_scrips_and_styles' );

$portalLink = zeroBS_portal_link();

if($ZBSuseQuotes < 0){
        status_header( 404 );
        nocache_headers();
        include( get_query_template( '404' ) );
        die();
}

add_action( 'wp_enqueue_scripts', 'zeroBS_portal_enqueue_stuff' );
?>
<div id="zbs-main" class="zbs-site-main">
	<div class="zbs-client-portal-wrap main site-main zbs-post zbs-hentry">
	<?php
	//moved into func
    if(function_exists('zeroBSCRM_clientPortalgetEndpoint')){
        $quote_endpoint = zeroBSCRM_clientPortalgetEndpoint('quotes');
    }else{
        $quote_endpoint = 'quotes';
    }
	zeroBS_portalnav($quote_endpoint);
	?>
<div class='zbs-portal-wrapper zbs-portal-invoices-list'>

<?php
	global $wpdb;
	$uid = get_current_user_id();
	$uinfo = get_userdata( $uid );
	$cID = zeroBS_getCustomerIDWithEmail($uinfo->user_email);
	$currencyChar = zeroBSCRM_getCurrencyChr();

	// preview msg		
	zeroBSCRM_portal_adminPreviewMsg($cID,'margin-bottom:1em');

	// admin msg (upsell cpp) (checks perms itself, safe to run)
	zeroBSCRM_portal_adminMsg();

	$customer_quotes = zeroBS_getQuotesForCustomer($cID,true,100,0,false);


	if(count($customer_quotes) > 0){

		// titled v3.0
		?><h2><?php _e('Quotes','zero-bs-crm'); ?></h2><?php


		echo '<table class="table">';

		echo '<th>' . __('#',"zero-bs-crm") . '</th>';
		echo '<th>' . __('Date',"zero-bs-crm") . '</th>';
		echo '<th>' . __('Title',"zero-bs-crm") . '</th>';
		echo '<th>' . __('Total',"zero-bs-crm") . '</th>';
		echo '<th>' . __('Status',"zero-bs-crm") . '</th>';
		// echo '<th>' . __('Download PDF',"zero-bs-crm") . '</th>';

		foreach($customer_quotes as $cquo){


			// Quote Date
			$quoteDate = __("No date", "zero-bs-crm"); if (isset($cquo['date_date']) && !empty($cquo['date_date'])) $quoteDate = $cquo['date_date'];

			// Quote Status
			$quote_stat = zeroBS_getQuoteStatus($cquo);

			// Quote Value
			$quoteValue = ''; if (isset($cquo['value']) && !empty($cquo['value'])) $quoteValue = zeroBSCRM_formatCurrency($cquo['value']);			
		
        	// view on portal (hashed?)
        	$quoteURL = zeroBSCRM_portal_linkObj($cquo['id'],ZBS_TYPE_QUOTE); //$quoteURL = '#notavailable'; if (isset($cquo['hash'])) $quoteURL = esc_url($portalLink . $quote_endpoint . '/zh-' . $cquo['hash']);

			echo '<tr>';
			echo '<td><a href="'. $quoteURL .'">#'. $cquo['id'] .' '. __('(view)','zero-bs-crm') . '</a></td>';
				echo '<td>' . $quoteDate . '</td>';
				echo '<td><span class="name">'.$cquo['title'].'</span></td>';
				echo '<td>' . $quoteValue . '</td>';
				echo '<td><span class="status">'.$quote_stat.'</span></td>';
			echo '</tr>';
		}
		echo '</table>';
	}else{
		echo _e('You do not have any quotes yet.',"zero-bs-crm"); 
	}
	?>

		<?php zeroBSCRM_portalFooter(); ?>
		<div style="clear:both"></div>
		</div>
	</div>
</div>