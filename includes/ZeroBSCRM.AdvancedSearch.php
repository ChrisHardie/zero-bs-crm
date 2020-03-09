<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2+
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 12/07/2017
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */


function zeroBSCRM_advancedSearch(){

	global $wpdb;

	$querystring = ''; $s1 =''; $s2 = '';

	if(isset($_GET['adv-s']) && $_GET['zbs-which'] == 0){
		$querystring = sanitize_text_field($_GET['adv-s']);
		$results = zeroBS_searchCustomers($querystring);
		$s1 = 'selected';
		$s2 = '';
	}
	if(isset($_GET['adv-s']) && $_GET['zbs-which'] == 1){
		$querystring = sanitize_text_field($_GET['adv-s']);
		$results = zeroBS_searchLogs($querystring);
		$s1 = '';
		$s2 = 'selected';
	}

	echo '<h4>' . __('Advanced Search',"zero-bs-crm") . '</h4>';
	// admins feedback!
	##WLREMOVE 
	if (current_user_can('admin_zerobs_manage_options')) echo '<div class="notice is-dismissible"><p>Like this feature of ZBSCRM, please do <a href="'.admin_url('admin.php?page=zerobscrm-feedback').'" target="_blank">Give Feedback</a> if you have time!</p></div>';
	##/WLREMOVE 
	echo '<div id="zbs-search-form"><form method="GET"><input type="hidden" name="page" value="advancedy-search-crm" />';
	echo '<input type="text" class="form-control act-ser" id="adv-s" name="adv-s" value="'.$querystring.'" placeholder="Search.."/>';
	?>
		<select id="zbs-which" name="zbs-which" class="form-control">
			<option value="0" <?php echo $s1;?>><?php _e("Customers","zero-bs-crm"); ?></option>
			<option value="1" <?php echo $s2;?>><?php _e("Activity","zero-bs-crm"); ?></option>
		</select>
	<?php
	echo '<input type="submit" value="Search" class="button-primary las"/>';
	echo '</form></div>';

	?>

	<style>
		#zbs-search-form {
			margin:10px;
		}
		#adv-s{
			width:300px;
			float:left;
			margin-left:5px;
			margin-right:10px;
		}
		#zbs-which{
			width:200px;
			float:left;
			margin-right:10px;
		}
		.las{
			margin-top:20px;
		}
		.activity-log{
			margin:20px;
		}
		.activity-log .log{
			background:white;
			margin-bottom:20px;
			border: 1px solid #ddd;
			padding:20px;
		}
		.log .log-title{
			font-size:14px;
		}
		.log .log-content{
			font-size:12px;
		}
		.log-footer{
			border-top: 1px solid #999;
		}
		.log-footer .type{
			font-size: 11px;
			font-style: italic;
		}
		.img-rounded{
			border-radius:50%;
		}
		.avatar{
			width:50px;
			float:left;
		}
		.wrapper{
			margin-left:50px;
		}
		.log-status{
			padding:5px;
			font-size:13px;
			color:white;
			background:#999;
			display:inline-block;
		}
	</style>
	<div class="activity-log">
	<?php

	$default = '//1.gravatar.com/avatar/4f1e528f9735dd9d0cbc322ca321db52?s=32&d=mm&f=y&r=g;';
	$size = 40;
	$searchType = -1; if (isset($_GET['zbs-which'])) $searchType = (int)sanitize_text_field($_GET['zbs-which']);
	switch($searchType){

		#} LOGS 
		case 1:

			#} $result = log record
			foreach($results as $result){

				//pretty the output (whack it inline CSS for now - lazy but hey ho!)
				// Bad - these should ALWAYS be done through DAL! $c = get_post_meta($result['owner'],'zbs_customer_meta');
				$customer = zeroBS_getCustomer($result['owner']);

				// if meta is not array, it's a deleted customer..
				if (is_array($customer)){

					// WH: I did modify below to check for empty $c, but this is a better way:
					// ... note passes meta (which avoids the func having to REGET from MYSQL)
					$customerName = zeroBS_customerName($result['owner'],$customer,false,true);
					$customerEmail = zeroBS_customerEmail($result['owner'],$customer);
					//Not req here: $customerAddr = zerobs_customerAddr($result['owner'],array(),'full');

				} else {

					$customerName = ''; $customerEmail = ''; 
				}


				//$avatar_url = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $customerEmail ) ) ) . "?d=" . urlencode( $default ) . "&s=" . $size;
				$avatar_url = zeroBS_customerAvatar($customer['id']);

				// catches weird case empty logs
				if (is_array($result)){

					echo "<div class='log'>";
						echo "<div class='avatar'>";
							echo '<img class="img-rounded" src="' .$avatar_url. '" style="max-width:40px" alt="" />';
						echo "</div><div class='wrapper'>";
						echo "<div class='log-title'>";
							echo $result['shortdesc'];
						echo "</div>";
						echo "<div class='log-content'>";
							echo $result['longdesc'];
						echo "</div>";
						echo "<div class='log-footer'>";
							echo "<div class='type'>" . $result['type'] . " (" . $result['created'] . ")</div>";

							if (!empty($customerName)){
								echo "<div class='when'><span class='name'><a href=\"".zbsLink('view',$result['owner'],'zerobs_customer')."\">" . $customerName . '</a>';
								if (!empty($customerEmail)) echo ' ('.$customerEmail.')'; //$c[0]['fname'] . " " . $c[0]['lname'] . " (" . $c[0]['email'] . ")
								echo "</span></div>"; 
							} else {
								echo "<div class='when'><span class='name'>Customer Deleted</span></div>"; 

							}
						echo "</div></div>";
					echo '</div>';

				} else {

					// Safe to ignore these, think was WH old tests
					// echo 'weirdlog:<pre>'; print_r($result); echo '</pre>';
				}


			}

			break;


		#} Customers
		case 0: 

			#} $result = customer record
			foreach($results as $result){
				//pretty the output (whack it inline CSS for now - lazy but hey ho!)

				// WH: I did modify below to check for empty $c, but this is a better way:
				// ... note passes meta (which avoids the func having to REGET from MYSQL)
				$customerName = zeroBS_customerName($result['id'],$result,false,true);
				$customerEmail = zeroBS_customerEmail($result['id'],$result);
				$customerAddr = zerobs_customerAddr($result['id'],$result,'full');


				//$avatar_url = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $customerEmail ) ) ) . "?d=" . urlencode( $default ) . "&s=" . $size;
				$avatar_url = zeroBS_customerAvatar($result['id']);

				echo "<div class='log'>";
					echo "<div class='avatar'>";
						if (!empty($avatar_url)) 
							echo '<img class="img-rounded" src="' .$avatar_url. '" style="max-width:40px" alt="" />';
					echo "</div><div class='wrapper'>";
					echo "<div class='log-status'>";
						echo $result['status'];
					echo "</div>";
					echo "<div class='log-content'>";
						echo "<div class='name'>";

							echo "<h4>" . $customerName . "</h4>";
						echo "</div>";
						echo "<div class='address'>";
						echo $customerAddr;
						/* proper way above ^^ 
							echo $result['addr1'] . "<br/>";
							echo $result['addr2'] . "<br/>";
							echo $result['city'] . "<br/>";
							echo $result['county'] . "<br/>";
							echo $result['postcode'] . "<br/>";

							*/
						echo "</div>";
					echo "</div>";
					echo "<div class='log-footer'>";
						echo "<div class='when'><span class='name'><a href=\"".zbsLink('view',$result['id'],'zerobs_customer')."\">" . $customerName . '</a>';
						if (!empty($customerEmail)) echo ' ('.$customerEmail.')'; //$result['fname'] . " " . $result['lname'] . " (" . $result['email'] . ")
						echo "</span></div>";

					echo "</div></div>";
				echo '</div>';
			}

			break;

			default:

				// not 0, nor 1
				// nothing?

				break;

		}


	
	?>
	</div>


	<?php
}