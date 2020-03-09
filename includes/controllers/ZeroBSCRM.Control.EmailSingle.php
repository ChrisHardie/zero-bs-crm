<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.95+
 *
 * Copyright 2018, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 03/09/2018
 */


/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */


function zeroBSCRm_get_unread_inbox_count(){

    global $wpdb, $ZBSCRM_t;
    $sql = "SELECT count(ID) FROM  " . $ZBSCRM_t['system_mail_hist'] . " WHERE zbsmail_opened = 0 AND zbsmail_status = 'inbox'";
    $number_unread = $wpdb->get_var($sql);
    return $number_unread;
}


add_action('wp_ajax_zbs_email_star_thread', 'zeroBSCRM_star_email_thread');
function zeroBSCRM_star_email_thread(){

    //stars the email thread for easier finding in the "Starred" box
    check_ajax_referer('zbscrmjs-glob-ajax-nonce','sec');

    if (!zeroBSCRM_permsSendEmailContacts()) exit('{processed:-1}');

	global $wpdb, $ZBSCRM_t;
    $the_thread = (int)sanitize_text_field($_POST['emid']);
    $sql = $wpdb->prepare("UPDATE " . $ZBSCRM_t['system_mail_hist'] . " SET zbsmail_starred = 1 WHERE zbsmail_sender_thread = %d", $the_thread);
    $wpdb->query($sql);
    $m['message'] = 'success';
    echo json_encode($m);
    die();
}

add_action('wp_ajax_zbs_email_unstar_thread', 'zeroBSCRM_unstar_email_thread');
function zeroBSCRM_unstar_email_thread(){

    //stars the email thread for easier finding in the "Starred" box
    check_ajax_referer('zbscrmjs-glob-ajax-nonce','sec');

    if (!zeroBSCRM_permsSendEmailContacts()) exit('{processed:-1}');

	global $wpdb, $ZBSCRM_t;
    $the_thread = (int)sanitize_text_field($_POST['emid']);
    $sql = $wpdb->prepare("UPDATE " . $ZBSCRM_t['system_mail_hist'] . " SET zbsmail_starred = 0 WHERE zbsmail_sender_thread = %d", $the_thread);
    $wpdb->query($sql);
    $m['message'] = 'success';
    echo json_encode($m);
    die();
}

add_action('wp_ajax_zbs_delete_email_thread', 'zeroBSCRM_delete_email_thread');
function zeroBSCRM_delete_email_thread(){

    check_ajax_referer('zbscrmjs-glob-ajax-nonce','sec');

    if (!zeroBSCRM_permsSendEmailContacts()) exit('{processed:-1}');

    global $wpdb, $ZBSCRM_t;
    $the_thread = (int)sanitize_text_field($_POST['emid']);
    $sql = $wpdb->prepare("DELETE FROM " . $ZBSCRM_t['system_mail_hist'] . " WHERE zbsmail_sender_thread = %d", $the_thread);
    $wpdb->query($sql);
    $m['message'] = 'success';
    echo json_encode($m);
    die();  
}

add_action('wp_ajax_zbs_email_send_thread_ui','zeroBSCRM_send_email_thread_ajax');
function zeroBSCRM_send_email_thread_ajax(){

    check_ajax_referer('zbscrmjs-glob-ajax-nonce','sec');

    if (!zeroBSCRM_permsSendEmailContacts()) exit('{processed:-1}');
    
    global $wpdb, $ZBSCRM_t;

    $the_thread = (int)sanitize_text_field($_POST['emid']);
    $the_customer_id = (int)sanitize_text_field($_POST['cid']);

   
    $sql = $wpdb->prepare("SELECT zbsmail_receiver_email FROM " . $ZBSCRM_t['system_mail_hist'] . " WHERE zbsmail_sender_thread = %d ORDER BY ID ASC LIMIT 0,1", $the_thread);
    $sending_to_email = $wpdb->get_var($sql);

    if($sending_to_email == ''){
        $sending_to_email = zeroBS_customerEmail($the_customer_id);
    }

    // get delivery method
    $sql = $wpdb->prepare("SELECT zbsmail_sender_maildelivery_key FROM " . $ZBSCRM_t['system_mail_hist'] . " WHERE zbsmail_sender_thread = %d ORDER BY ID ASC LIMIT 0,1", $the_thread);
    $deliveryMethod = $wpdb->get_var($sql);
      // validate still legit, else set to -1 if (empty($deliveryMethod))
      // actually, the sendmail func does this well, fallback to that


   /* this can probably be generalised, taken from AdminPages.php Send Email */

    if(zeroBSCRM_validateEmail($sending_to_email)){
        //email is valid and belongs to customer..

     //   if(isset($_GET['zbsprefill']) && !empty($_GET['zbsprefill'])){
            //pre-filled for customer...

            $subject = ''; if (isset($_POST['zbs-send-email-title'])) $subject = sanitize_text_field( $_POST['zbs-send-email-title'] );                
            $content = ''; if (isset($_POST['zbs_send_email_content'])) $content = wp_kses_post($_POST['zbs_send_email_content']);
            $toEmail = $sending_to_email;
            $customerID = (int)zeroBS_getCustomerIDWithEmail( $sending_to_email );

            $emailHTML = zeroBSCRM_mailTemplates_directMsg(true, $content, $subject);  
            $headers = array('Content-Type: text/html; charset=UTF-8'); 

            $uid = get_current_user_id();

            // get which del method naming convention:
            $namingConvention = zeroBSCRM_getSetting('directmsgfrom');
            switch ($namingConvention){

                case 1: // Agent Name @ CRM Name

                  $user_info = get_userdata($uid);

                  $agentName = $user_info->first_name . " " . $user_info->last_name;
                  if($agentName == " "){
                    $agentName = $user_info->display_name;
                  }

                  $agentName = ucwords($agentName);
                  $emailFromName = $agentName;

                  $crmName = zeroBSCRM_mailDelivery_defaultFromname();
                  if (!empty($crmName)) $emailFromName .= " @ " . $crmName; 

                  break;
                case 2: // CRM Name

                  $emailFromName = zeroBSCRM_mailDelivery_defaultFromname();

                  break;
                case 3: // Mail Delivery Name

                  // just pass empty and it'll default
                  $emailFromName = '';

                  break;
            }

            /* Old method:

            $emailFrom = zeroBSCRM_mailDelivery_defaultEmail();

            $headers[]  = 'From: '. $emailFromName .' <'.sanitize_email($emailFrom).'>';

            //we can add the tracking and log the send here, with ID = 999 (for UI emails)?
            $emailHTML = zeroBSCRM_mailTracking_addPixel($emailHTML, $uid, $customerID, $toEmail, -999, $subject);

            wp_mail(  $toEmail, $subject, $emailHTML, $headers );
            

            zeroBSCRM_mailTracking_logEmail(-999, $customerID, $uid, $toEmail, -999, $subject);
            */

            // build send array
            $mailArray = array(
              'toEmail' => $toEmail,
              'toName' => '',
              'subject' => $subject,
              'headers' => $headers,
              'body' => $emailHTML,
              'textbody' => '',
              'thread'  => $the_thread,
              'content' => $content, //not the full HTML just the content
              'options' => array(
                'html' => 1
              ),
              'tracking' => array( 
                // tracking :D (auto-inserted pixel + saved in history db)
                'emailTypeID' => -999, // mike's used -999 to mean direct email
                'targetObjID' => $customerID,
                'senderWPID' => $uid,
                'associatedObjID' => -999 // mike's used -999 to mean direct email (yes twice?)
              )
            );

            // if any, add
            if (!empty($emailFromName)) $mailArray['overrideSendName'] = $emailFromName;

            // DEBUG echo 'Sending:<pre>'; print_r($mailArray); echo '</pre>Result:';

            // Sends email, including tracking, via DEFAULT route out (-1)
            // and logs trcking :)
            $sent = zeroBSCRM_mailDelivery_sendMessage($deliveryMethod,$mailArray);

            // DEBUG print_r($sent); echo '<hr/>';



            $logDesc = __('Email Sent with the subject: ') . $subject; 

          #} Add log - temp, needs to also remember which camp + link to
            $newLogID = zeroBS_addUpdateContactLog($customerID,-1,-1,array(
              'type' => 'Email',
              'shortdesc' => __('Email Sent',"zero-bs-crm"),
              'longdesc' => $logDesc,
              // meta keyval for later linking
              'meta_assoc_src' => 'singlemail'
            ));




        /*
        }else{
            //do we want to allow "cowboy" sends from this page (or force through zbsprefill)
            _e("The email does not match the contact", "zero-bs-crm");
        }
        */

    }else{
        $m['message'] = "That is not a valid email. Please enter a valid email";
    }



   
    $m['message'] = 'success';
    echo json_encode($m);
    die();      
}


add_action( 'wp_ajax_zbs_email_customer_panel', 'zeroBSCRM_emails_customer_panel' );
function zeroBSCRM_emails_customer_panel(){

    check_ajax_referer('zbscrmjs-glob-ajax-nonce','sec');

    if (!zeroBSCRM_permsSendEmailContacts()) exit('{processed:-1}');

    $customerID = (int)sanitize_text_field($_POST['cid']);
    $threadID = (int)sanitize_text_field($_POST['emid']);

    $ret['customer'] =  zeroBS_getCustomer($customerID,true,true,true);

    $ret['avatar'] = zeroBS_customerAvatarHTML($customerID);

    $ret['trans_value'] = zeroBSCRM_formatCurrency(zeroBS_customerTransactionsValue($customerID, $ret['customer']['transactions']));
    $ret['inv_value'] = zeroBSCRM_formatCurrency(zeroBS_customerInvoicesValue($customerID, $ret['customer']['invoices']));
    $ret['quote_value'] = zeroBSCRM_formatCurrency(zeroBS_customerQuotesValue($customerID, $ret['customer']['quotes']));

    $ret['tasks'] = zeroBSCRM_getTaskList($customerID);

    $email = zeroBSCRM_get_email_history(0, 50,  $customerID, '',-1,false, $threadID);

    zeroBSCRM_mark_as_read($threadID);

    $e = 0;
    foreach($email as $em){
        $email_ret[$e]['the_id'] = $em->ID;
        $email_ret[$e]['date'] = zeroBSCRM_locale_utsToDate($em->zbsmail_created);
        $email_ret[$e]['zbsmail_subject'] = $em->zbsmail_subject;
        if($em->zbsmail_content == null){
            $email_ret[$e]['zbsmail_content'] = __('No content was stored for this message','zero-bs-crm');
        }else{
            $email_ret[$e]['zbsmail_content'] = zeroBSCRM_io_WPEditor_DBToHTML($em->zbsmail_content);
        }
        $email_ret[$e]['zbsmail_opened'] = $em->zbsmail_opened;
        $email_ret[$e]['zbsmail_lastopened'] = zeroBSCRM_locale_utsToDatetimeWP($em->zbsmail_firstopened);
        $email_ret[$e]['in_or_out'] = $em->zbsmail_status;
        if($em->zbsmail_status == 'inbox'){
            $email_ret[$e]['avatar'] = zeroBS_customerAvatarHTML($em->zbsmail_target_objid);
        }else{
            $email_ret[$e]['avatar'] = get_avatar($em->zbsmail_sender_wpid, 35);
        }
        $e++;
    }
   
    $ret['email'] = $email_ret;

    echo json_encode($ret,true);
    die();

}

function zeroBSCRM_mark_as_read($threadID = -1){
    global $wpdb, $ZBSCRM_t;
    if($threadID >= 0){
        $sql = $wpdb->prepare("UPDATE " . $ZBSCRM_t['system_mail_hist'] . " SET zbsmail_opened = 1, zbsmail_lastopened = %d, zbsmail_firstopened = %d WHERE zbsmail_sender_thread = %d AND zbsmail_status = 'inbox' AND zbsmail_opened = 0", time(), time(), $threadID); 
    }
    $wpdb->query($sql);
}


 #} Mark as included :)
define('ZBSCRM_INC_SINGEMAILCONT',true);