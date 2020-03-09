<?php
/**
 * Transaction List
 *
 * The list of transactions made by a user (all statuses)
 *
 * @author 		ZeroBSCRM
 * @package 	Templates/Portal/Transactions
 * @see			https://zerobscrm.com/kb/
 * @version     3.0
 * 
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Don't allow direct access

#} changed to this, so if people want to re-style then can remove_action
do_action( 'zbs_enqueue_scrips_and_styles' );
//zeroBS_portal_enqueue_stuff();
?>
<div id="zbs-main" class="zbs-site-main">
	<div class="zbs-client-portal-wrap main site-main zbs-post zbs-hentry">
	<?php
	//moved into func
    if(function_exists('zeroBSCRM_clientPortalgetEndpoint')){
        $tran_endpoint = zeroBSCRM_clientPortalgetEndpoint('transactions');
    }else{
        $tran_endpoint = 'transactions';
    }
	zeroBS_portalnav($tran_endpoint);
	?>
	<div class='zbs-portal-wrapper'>
		<?php

			global $zbs;
			$uid = get_current_user_id();
			$uinfo = get_userdata( $uid );
			$cID = zeroBS_getCustomerIDWithEmail($uinfo->user_email);
			
			$customer_transactions = zeroBS_getTransactionsForCustomer($cID,true,100,0,false);

			// preview msg		
			zeroBSCRM_portal_adminPreviewMsg($cID,'margin-bottom:1em');

			// admin msg (upsell cpp) (checks perms itself, safe to run)
			zeroBSCRM_portal_adminMsg();			

			if (is_array($customer_transactions) && count($customer_transactions) > 0){

				// titled v3.0
				?><h2><?php _e('Transactions','zero-bs-crm'); ?></h2><?php

				echo '<table class="table">';
				echo '<th>' . __('Transaction',"zero-bs-crm") . '</th>';
				echo '<th>' . __('Transaction Date',"zero-bs-crm") . '</th>';
				echo '<th>' . __('Title',"zero-bs-crm") . '</th>';
				echo '<th>' . __('Total',"zero-bs-crm") . '</th>';
				
				foreach($customer_transactions as $transaction){

					// Transaction Date
					$transactionDate = __("No date", "zero-bs-crm"); if (isset($transaction['date_date']) && !empty($transaction['date_date'])) $transactionDate = $transaction['date_date'];

					// Transaction Ref
					$transactionRef = ''; if (isset($transaction['ref']) && !empty($transaction['ref'])) $transactionRef = $transaction['ref'];

					// transactionTitle Title
					$transactionTitle = ''; if (isset($transaction['title']) && !empty($transaction['title'])) $transactionTitle = $transaction['title'];

					// Transaction Value
					$transactionValue = ''; if (isset($transaction['total']) && !empty($transaction['total'])) $transactionValue = zeroBSCRM_formatCurrency($transaction['total']);			

		
					echo '<tr>';
						echo '<td>' . $transactionRef . '</td>';
						echo '<td>' . $transactionDate . '</td>';
						echo '<td><span class="name">'.$transactionTitle.'</span></td>';
						echo '<td>' . $transactionValue . '</td>';
					//	echo '<td class="tools"><a href="account/invoices/274119/pdf" class="pdf_download" target="_blank"><i class="fa fa-file-pdf-o"></i></a></td>';
					echo '</tr>';
				}
				echo '</table>';
			}else{
				echo _e('You do not have any transactions yet.',"zero-bs-crm"); 
			}
			?>
		<div style="clear:both"></div>
		<?php zeroBSCRM_portalFooter(); ?>
		</div>
	</div>
</div>