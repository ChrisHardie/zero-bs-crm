<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.2.3
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 15/12/16
 */

/*

	To add to cron:

		1) Add to list (#1)
		2) Add func x 2 (#2)


	To see what cron is enabled:
	http://wordpress.stackexchange.com/questions/98032/is-there-a-quick-way-to-view-the-wp-cron-schedule
  <?php 

    $cron_jobs = get_option( 'cron' );
    print_r($cron_jobs);

  ?>
	

*/

/* ======================================================
	Wrapper Arr (lists of cron to add)
   ====================================================== */

	 global 	$zbscrm_CRONList; 
	 
   			$zbscrm_CRONList = array(

   				##WLREMOVE
   				'tele' => 'daily',
   				'ext' => 'daily',
   				##/WLREMOVE

   				# use alpha, will be lower-cased for hook
   				// v3.0+ we do away with this: 'clearAutoDrafts' => 'hourly',
   				'notifyEvents' => 'hourly',
   				//'clearTempHashes' => 'hourly'
   				'clearSecLogs' => 'daily',

   			);



/* ======================================================
	/Wrapper Arr (lists of cron to add)
   ====================================================== */


/* ======================================================
	Add ZBS Custom schedule (5m)
	// https://wordpress.stackexchange.com/questions/208135/how-to-run-a-function-every-5-minutes
   ====================================================== */
	function zeroBSCRM_cronSchedules($schedules){
	    if(!isset($schedules["5min"])){
	        $schedules["5min"] = array(
	            'interval' => 5*60,
	            'display' => __('Once every 5 minutes'));
	    }
	    return $schedules;
	}
	add_filter('cron_schedules','zeroBSCRM_cronSchedules');
/* ======================================================
	/Add ZBS Custom schedule (5m)
   ====================================================== */


/* ======================================================
	Scheduler Funcs
   ====================================================== */
function zeroBSCRM_activateCrons(){


	global $zbscrm_CRONList; 
	foreach ($zbscrm_CRONList as $cronName => $timingStr)	{
		
		$hook = 'zbs'.strtolower($cronName);
		$funcName = 'zeroBSCRM_cron_'.$cronName;
		
	    if (! wp_next_scheduled ( $hook )) {
				wp_schedule_event(time(), $timingStr, $hook);
	    }

	}

}
register_activation_hook(ZBS_ROOTFILE, 'zeroBSCRM_activateCrons');
function zeroBSCRM_deactivateCrons(){

	global $zbscrm_CRONList; 
	foreach ($zbscrm_CRONList as $cronName)	{
		
		$hook = 'zbs'.strtolower($cronName);
		$funcName = 'zeroBSCRM_cron_'.$cronName;

		wp_clear_scheduled_hook($hook);

	}

}
register_deactivation_hook(ZBS_ROOTFILE, 'zeroBSCRM_deactivateCrons');
/* ======================================================
	/ Scheduler Funcs
   ====================================================== */





/* ======================================================
	Actual Action Funcs #2
   ====================================================== */

   # ======= Clear Auto-drafts
	function zeroBSCRM_cron_clearAutoDrafts() {

		#} Simple
		zeroBSCRM_clearCPTAutoDrafts();

	}

	add_action('zbsclearautodrafts', 'zeroBSCRM_cron_clearAutoDrafts');


	function zeroBSCRM_cron_notifyEvents() {

		#} Simple
		zeroBSCRM_notifyEvents();

	}

	add_action('zbsnotifyevents', 'zeroBSCRM_cron_notifyEvents');


   # ======= Clear temporary hashes
	/* function zeroBSCRM_cron_clearTempHashes() {

		#} Simple
		zeroBSCRM_clearTemporaryHashes();

	}

	add_action('zbscleartemphashes', 'zeroBSCRM_cron_clearTempHashes'); */

   # ======= Clear security logs (from easy-pay hash requests) *after 72h
	function zeroBSCRM_cron_clearSecLogs() {

		#} Simple
		zeroBSCRM_clearSecurityLogs();

	}

	add_action('zbsclearseclogs', 'zeroBSCRM_cron_clearSecLogs'); 

	##WLREMOVE
	#} checks if should send home telemetrics (will do weekly)
	function zeroBSCRM_cron_tele() {

		#} Simple
		zeroBSCRM_teleCron();

	}

	add_action('zbstele', 'zeroBSCRM_cron_tele');


	function zeroBSCRM_cron_ext() {

		#} Simple
		zeroBSCRM_extCron();

	}

	add_action('zbsext', 'zeroBSCRM_cron_ext');	
	##/WLREMOVE




/* ======================================================
	/ Actual Action Funcs
   ====================================================== */





/* ======================================================
	CRONNABLE FUNCTION (should house these somewhere)
   ====================================================== */

#} WHLOOK - this should fire every day no? 
function zeroBSCRM_extCron(){

	// check for updates needed to extensions (daily)
	$zbs_update_avail = zeroBSCRM_extensions_checkForUpdates();
	update_option('zbs_update_avail', $zbs_update_avail);
	// need to handle situation if account not connected..
	$connect = get_option('zbs_account_items');
	if($connect == ''){
		//account has not been connected.. ABORT
	}else{
	  $check = get_transient('zbs_extension_check');  //this will decay every 24 hours..
	  $items = get_option('zbs_account_items');  //this has the items array..
	  $zbs_get_url = "https://zerobscrm.com/my-account/";
	  $access = get_option('zbs_account_access');
	  $args = array();
	  $args = array(
	              'body' => array(
	                      'zbsaccountemail' => $access['email'],
	                      'zbsaccountkey' => $access['key'],
	                      'zbshomeurl' => home_url()
	                      ),
	              'method' => 'GET'
	            );
	  $wp_get = wp_remote_get($zbs_get_url, $args);
	  if (isset($wp_get['body'])) {
	  	  $zbs_acc_met = zeroBSCRM_jsonp_decode($wp_get['body']);
		  $zbs_items = $zbs_acc_met->items;
		  $i=0;
		  foreach($zbs_items as $item){
		    $zitems[$i]['name'] = $item->name;
		    $zitems[$i]['status'] = $item->status;
		    $i++;
		  }
		  // not really req. delete_option('zbs_account_items');
		  update_option('zbs_account_items', $zitems);
		  $allowed_sites = $zbs_acc_met->allowed;
		  $actual_sites =  count($zbs_acc_met->sites);
		  if ($actual_sites > $allowed_sites){
		     update_option('zbs_too_many_sites',true);
		  } else {
		    update_option('zbs_too_many_sites',false);
		  }

		} else { // / if body

			// couldn't connect to server, ABORT

		} 
	}
}




// daily checks
function zeroBSCRM_teleCron(){

	global $zbs;

	$share = $zbs->settings->get('shareessentials');
	if ($share == "1"){  //only share if permission given

		// check last send
	    $activePeriod = get_option('zbs_teleactive');
	    // not implmented yet: $teleIssues = get_option('zbs_telewalls');
	    if (is_array($activePeriod) && isset($activePeriod['tstart'])){ // not implmented yet: && !$teleIssues){

	    	// within time?
	    	if ($activePeriod['tstart'] < (time()-604800)){

	    		// fire.
	    		zeroBSCRM_teleEndPeriod();

	    	}

	    } else {

	    	// smt's gone wrong with option obj, restart it
			$activePeriod = array('tstart'=>time());
			update_option('zbs_teleactive',$activePeriod);
			// not implmented yet: delete_option('zbs_telewalls');

	    }

	} // / if share essentials

}


#} this is the event notifier. It should send an email 24 hours before if not complete
function zeroBSCRM_notifyEvents(){

	//86,400 seconds in 24 hours...
	global $wpdb;
	$query = "SELECT ID FROM $wpdb->posts WHERE post_type = 'zerobs_event' AND post_status = 'publish'";
	$results = $wpdb->get_results($query);

	$event = array();
	$i=0;
	foreach($results as $result){

		$zbsEventActions['complete'] = 0;

		$zbsEventMeta 	= 	get_post_meta($result->ID, 'zbs_event_meta', true);
		$zbsEventActions = get_post_meta($result->ID, 'zbs_event_actions', true);

		$zbsEventActions = array_merge($zbsEventMeta, $zbsEventActions);

		#} this is the flag as to whether the event has been notified about
		$notified = get_post_meta($result->ID,'24hnotify', true);

		if($zbsEventActions != '' && empty($notified)){

			if(array_key_exists('complete', $zbsEventActions)){

				if($zbsEventActions['complete'] != 1 && isset($zbsEventActions['notify_crm']) && $zbsEventActions['notify_crm'] == 'on'){    //only mail about non-complete events.

					$date = $zbsEventMeta['from'];

					//so we are testing if the event is in the future (i.e. time() <= $date) AND
					//and the time of the event is less than 24 hours away from now...

					// WH added. Simplify in these situ's man
					$eventTime = strtotime($date);
					$eventTimeMinus24hr = $eventTime - 86400;

					//if ($eventTime <= (time() + 86400) && time() <= $eventTime) {    //as soon as we get within 24 hours.. it'll fire
					if (time() > $eventTimeMinus24hr && time() < $eventTime){


						//the event URL
						$url = admin_url('post.php?post='.$result->ID.'&action=edit');

						$contactID = zeroBS_getOwner($result->ID,true,'zerobs_event');
						$contactID = $contactID['ID'];

						if(array_key_exists('customer', $zbsEventMeta) && $contactID > 0){

							$user_info = get_userdata($contactID);

							/*
							$username = $user_info->user_login;
							$first_name = $user_info->first_name;
							$last_name = $user_info->last_name;
							*/
							$email = $user_info->user_email;

							//notified already?
							// moved this check above $notified = get_post_meta($result->ID,'24hnotify', true);
							//if($notified == ''){  

							/* old way 

							$active = zeroBSCRM_get_email_status(ZBSEMAIL_EVENTNOTIFICATION);
							if($active){
								
								$body = zeroBSCRM_Event_generateNotificationHTML($password='',true, $email, $url, $result->ID);

						    	#} function zeroBSCRM_mailTracking_addPixel($message='', $who = -1, $user=-1, $email='', $item=-1) 
						    	#} who = 1 means this is an email to a CRM team user...
						    	$body = zeroBSCRM_mailTracking_addPixel($body, -13, $customerID, $email, $result->ID, $result->post_title);


				                //single template data.
					            $form = zeroBSCRM_mailTemplate_get(ZBSEMAIL_EVENTNOTIFICATION);
								$subject = $form->zbsmail_subject;

								$headers = zeroBSCRM_mailTemplate_getHeaders(ZBSEMAIL_EVENTNOTIFICATION);

								wp_mail(  $email, $subject, $body, $headers );
								
								#} function zeroBSCRM_mailTracking_logEmail($ID=-1, $uID=-1, $who = 0, $email='', $item=-1)
								#}QUOTE NOTIFICATION IS EMAIL ID = 2
								zeroBSCRM_mailTracking_logEmail(ZBSEMAIL_EVENTNOTIFICATION, $customerID, -13, $email, $result->ID, $result->post_title);

								update_post_meta($result->ID, '24hnotify', "1");



								}		

							//}  
							*/


							#} check if the email is active..
							$active = zeroBSCRM_get_email_status(ZBSEMAIL_EVENTNOTIFICATION);
							if ($active){

								// send welcome email (tracking will now be dealt with by zeroBSCRM_mailDelivery_sendMessage)

								// ==========================================================================================
								// =================================== MAIL SENDING =========================================

								// generate html
								$emailHTML = zeroBSCRM_Event_generateNotificationHTML($password='',true, $email, $url, $result->ID);

				                  // build send array
				                  $mailArray = array(
				                    'toEmail' => $email,
				                    'toName' => '',
				                    'subject' => zeroBSCRM_mailTemplate_getSubject(ZBSEMAIL_EVENTNOTIFICATION),
				                    'headers' => zeroBSCRM_mailTemplate_getHeaders(ZBSEMAIL_EVENTNOTIFICATION),
				                    'body' => $emailHTML,
				                    'textbody' => '',
				                    'options' => array(
				                      'html' => 1
				                    ),
				                    'tracking' => array( 
				                      // tracking :D (auto-inserted pixel + saved in history db)
				                      'emailTypeID' => ZBSEMAIL_EVENTNOTIFICATION,
				                      'targetObjID' => $contactID,
				                      'senderWPID' => -13,
				                      'associatedObjID' => $result->ID
				                    )
				                  );

				                  // DEBUG echo 'Sending:<pre>'; print_r($mailArray); echo '</pre>Result:';

				                  // Sends email, including tracking, via setting stored route out, (or default if none)
				                  // and logs trcking :)

									// discern del method
									$mailDeliveryMethod = zeroBSCRM_mailTemplate_getMailDelMethod(ZBSEMAIL_EVENTNOTIFICATION);
									if (!isset($mailDeliveryMethod) || empty($mailDeliveryMethod)) $mailDeliveryMethod = -1;

									// send
									$sent = zeroBSCRM_mailDelivery_sendMessage($mailDeliveryMethod,$mailArray);

									// mark as sent
									update_post_meta($result->ID,'24hnotify', 's');


								// =================================== / MAIL SENDING =======================================
								// ==========================================================================================

							}


						} else {


						} 


					} else {

					} 

				} //  if not completed + 24 hour reminder

			} // if array key complete exists

		} // / if has actions


	} // / for each event

}