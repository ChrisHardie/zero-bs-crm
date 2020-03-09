<?php 
/*!
 * Zero BS CRM - Emails
 * 
 * The start of the UI for the email systems within Zero BS CRM
 * Single emails, sent from the CRM etc. 
 * We will also need to log which "mail delivery" was used
 * to "SEND" the email too (i.e. mailboxes)
 * 
 * https://zerobscrm.com
 * V1.0
 *
 * Copyright 2018, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 18th August 2018
 *
 */


/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */


function zeroBSCRM_emails_UI(){
    #} make sure the DB can support the layout :-)
    //zeroBSCRM_createTables();
    
    //effectively our migration routine..
    //zeroBSCRM_update_mail_history_table();
    
    zeroBSCRM_emails_learn_menu();

      $sending_mail = false;
      if(isset($_GET['zbsprefill']) && !empty($_GET['zbsprefill'])){
            $sending_mail = true;
            ?>
                <style>
                    .zbs-email-list, .zbs-email-content{
                        display:none;
                    }
                </style>
            <?php
      }

      #} "cal" is the task scheduler. hide the task info on the sidebar, if installed
      if(!zeroBSCRM_isExtensionInstalled('cal')){
      ?>
        <style>
            .task-cell, .panel-h4, .the-tasks{
                display:none;
            }
        </style>
      <?php
      }


        //more scripts and styles into an enqueue :-)
    ?>



    <div class="ui inbox-wrap" style="margin-left: -20px;margin-right: 0px;">

        <div class="ui vertical menu inverted inbox-nav">

            <a class="item zbs-inbox-link">
                <div class="nav-men">
                    <i class="ui icon inbox"></i> <?php _e('Inbox','zero-bs-crm'); ?>
                    <?php $count = zeroBSCRm_get_unread_inbox_count();
                    if($count > 0){ ?>
                    <div class="ui blue label"><?php echo $count; ?></div>
                    <?php } ?>
                </div>
            </a>

            <a class="item zbs-starred-link">
                <div class="nav-men">
                    <i class="ui icon star"></i> <?php _e('Starred','zero-bs-crm'); ?>
                </div>
            </a>


            <a class="item zbs-hide">
                <div class="nav-men">
                    <i class="ui icon exclamation triangle"></i><?php _e('Important','zero-bs-crm');?>
                </div>
            </a>

            <?php if(!$sending_mail){ ?>
                <a class="active blue item zbs-sent-link">
            <?php }else{ ?>
                <a class="item zbs-sent-link">
            <?php } ?>
                <div class="nav-men">
                    <i class="ui icon paper plane"></i> <?php _e('Sent','zero-bs-crm');?>
                </div>
            </a>

            <?php do_action('zbs_emails_scheduled_nav'); ?>

            <!--
            <?php // if($sending_mail){ ?>
                <a class="active blue item zbs-drafts-link">
            <?php //}else{ ?>
                <a class="item zbs-drafts-link">
            <?php // } ?>

                <div class="nav-men">
                    <i class="ui icon file"></i> <?php // _e('Drafts','zero-bs-crm'); ?>
                </div>
            </a>
            -->

            <div class='push-down'>

                <?php do_action('zbs_before_email_templates_nav'); ?>

                <a class="item" href="<?php echo admin_url('admin.php?page=zbs-email-templates&zbs_template_id=1'); ?>">
                    <div class="nav-men">
                        <i class="ui icon file alternate outline"></i> <?php _e('Templates','zero-bs-crm'); ?>
                    </div>
                </a>

                <a class="item" href="<?php echo admin_url('admin.php?page=zerobscrm-plugin-settings&tab=mail'); ?>">
                    <div class="nav-men">
                        <i class="ui icon cog"></i> <?php _e('Settings','zero-bs-crm'); ?>
                    </div>
                </a>
            </div>

            
        </div>

        <?php if($sending_mail){ ?>
            <div id='zbs-send-single-email-ui' style='display:block;'>
        <?php }else{ ?>
            <div id='zbs-send-single-email-ui' style='display:none;'>
        <?php } ?>
            <?php zeroBSCRM_pages_admin_sendmail(); ?>
        </div>


        <div class='zbs-email-list inbox-email-list app-content'>      
            <?php
             $email_hist = zeroBSCRM_get_email_history(0,50, -1, 'inbox',-1,true);
            // zbs_prettyprint($email_hist);
             echo '<div class="ui celled list" style="background:white;">';

             if(count($email_hist) == 0){
                echo "<div class='no-emails'><i class='ui icon exclamation'></i><br/>" . __('No emails of this type','zero-bs-crm'); 
                ##WLREMOVE
                echo "<br><a href='https://zerobscrm.com/feature/emails/#inbox' target='_blank'>" . __('Learn More','zero-bs-crm') ."</a>";
                ##/WLREMOVE
                echo "</div>";
            }

             $i = 0;
            foreach($email_hist as $email){
                    $contact_meta = zeroBS_getCustomerMeta($email->zbsmail_target_objid);
                    echo '<div class="item zbs-email-list-item zbs-email-list-'.$email->zbsmail_sender_thread.' zbs-unread-'.$email->zbsmail_opened.'" data-cid="'.$email->zbsmail_target_objid.'" data-emid="'.$email->zbsmail_sender_thread.'" data-fav="'.$email->zbsmail_starred.'">';
                        echo  "<div class='zbs-contact'>";
                        //    echo "<input type='checkbox' />";
                            echo zeroBS_customerAvatarHTML($email->zbsmail_target_objid);
                            echo "<div class='zbs-who'>" . $contact_meta['fname'] . " " . $contact_meta['lname'] . "</div>";
                        echo "</div>";
                //    echo '<img class="ui avatar image" src="/images/avatar/small/helen.jpg">';
                    echo '<div class="content">';
                    echo '<div class="header">' .zeroBSCRM_textExpose($email->zbsmail_subject) .'</div>';
                        echo '<div class="the_content">' . zeroBSCRM_io_WPEditor_DBToHTMLExcerpt($email->zbsmail_content,200) . "</div>";

                        if($email->zbsmail_starred == 1){
                            echo "<i class='ui icon star yellow zbs-list-fav zbs-list-fav-".$email->zbsmail_sender_thread."'></i>";
                        }else{
                            echo "<i class='ui icon star yellow zbs-list-fav zbs-list-fav-".$email->zbsmail_sender_thread."' style='display:none;'></i>";
                        }

                    echo '</div>';
                echo '</div>';
                $i++;
            }

            echo '</div>';

            ?>
      
        </div>

        <div class='zbs-email-list starred-email-list app-content'>      
            <?php
             $email_hist = zeroBSCRM_get_email_history(0, 50,-1,'', -1, true,-1, true);
            // zbs_prettyprint($email_hist);
             echo '<div class="ui celled list" style="background:white;">';
             $i = 0;
             if(count($email_hist) == 0){
                echo "<div class='no-emails'><i class='ui icon exclamation'></i><br/>" . __('No emails of this type','zero-bs-crm') . "</div>";
            }
            foreach($email_hist as $email){
                    $contact_meta = zeroBS_getCustomerMeta($email->zbsmail_target_objid);
                    echo '<div class="item zbs-email-list-item zbs-email-list-'.$email->zbsmail_sender_thread.'" data-cid="'.$email->zbsmail_target_objid.'" data-emid="'.$email->zbsmail_sender_thread.'" data-fav="'.$email->zbsmail_starred.'">';
                        echo  "<div class='zbs-contact'>";
                        //    echo "<input type='checkbox' />";
                            echo zeroBS_customerAvatarHTML($email->zbsmail_target_objid);
                            echo "<div class='zbs-who'>" . $contact_meta['fname'] . " " . $contact_meta['lname'] . "</div>";
                        echo "</div>";
                //    echo '<img class="ui avatar image" src="/images/avatar/small/helen.jpg">';
                    echo '<div class="content">';
                    echo '<div class="header">' .zeroBSCRM_textExpose($email->zbsmail_subject) .'</div>';
                        echo '<div class="the_content">' . zeroBSCRM_io_WPEditor_DBToHTMLExcerpt($email->zbsmail_content,200) . "</div>";

                        if($email->zbsmail_starred == 1){
                            echo "<i class='ui icon star yellow zbs-list-fav zbs-list-fav-".$email->zbsmail_sender_thread."'></i>";
                        }else{
                            echo "<i class='ui icon star yellow zbs-list-fav zbs-list-fav-".$email->zbsmail_sender_thread."' style='display:none;'></i>";
                        }

                    echo '</div>';
                echo '</div>';
                $i++;
            }

            echo '</div>';

            ?>
      
        </div>

        <div class='zbs-email-list sent-email-list app-content RARRR'>
            <?php
             $email_hist = zeroBSCRM_get_email_history(0,50, -1, 'sent',-1,true);
            // zbs_prettyprint($email_hist);
             echo '<div class="ui celled list" style="background:white;">';
             $i = 0;

             if(count($email_hist) == 0){
                echo "<div class='no-emails'><i class='ui icon exclamation'></i><br/>" . __('No emails of this type','zero-bs-crm') . "</div>";
            }

            foreach($email_hist as $email){
                    $contact_meta = zeroBS_getCustomerMeta($email->zbsmail_target_objid);
                    echo '<div class="item zbs-email-list-item zbs-email-list-'.$email->zbsmail_sender_thread.'" data-cid="'.$email->zbsmail_target_objid.'" data-emid="'.$email->zbsmail_sender_thread.'" data-fav="'.$email->zbsmail_starred.'">';
                        echo  "<div class='zbs-contact'>";
                        //    echo "<input type='checkbox' />";
                            echo zeroBS_customerAvatarHTML($email->zbsmail_target_objid);
                            echo "<div class='zbs-who'>" . $contact_meta['fname'] . " " . $contact_meta['lname'] . "</div>";
                        echo "</div>";
                //    echo '<img class="ui avatar image" src="/images/avatar/small/helen.jpg">';
                    echo '<div class="content">';
                    echo '<div class="header">' .zeroBSCRM_textExpose($email->zbsmail_subject).'</div>';
                    echo '<div class="the_content">' . zeroBSCRM_io_WPEditor_DBToHTMLExcerpt($email->zbsmail_content,200) . "</div>";

                    if($email->zbsmail_starred == 1){
                        echo "<i class='ui icon star yellow zbs-list-fav zbs-list-fav-".$email->zbsmail_sender_thread."'></i>";
                    }else{
                        echo "<i class='ui icon star yellow zbs-list-fav zbs-list-fav-".$email->zbsmail_sender_thread."' style='display:none;'></i>";
                    }

                    echo '</div>';
                echo '</div>';
                $i++;
            }

            echo '</div>';

            ?>

        </div>

        <div class='zbs-email-content inverted dimmer app-content'>
            <div class="zbs-ajax-loading">
                <div class='click-email-to-load'>
                    <i class="ui icon envelope outline zbs-click-email-icon" style="font-size:30px;font-weight:100"></i>
                    <h4 class="click-email"><?php _e("Click an email to load details","zero-bs-crm"); ?></h4>
                </div>
                <img class='spinner-gif' src="<?php echo admin_url('images/spinner.gif'); ?>" />
            </div>

            <div id="zbs-email-body">
                <div class='zbs-email-actions'>
                <i class="ui icon star outline" id="zbs-star-this"></i>
                    <i class="trash alternate outline icon"></i>
                </div>

                <div class='zbs-email-thread'>

                </div>
            </div>

            <div id="zbs-email-send-message-thread">
            <?php
                do_action('zbs_email_canned_reply');
            ?>
            <?php
                $content = "";
                $editorSettings = array(
                        'media_buttons' => false,
                        'quicktags' => false,
                        'tinymce'=> array(
                            'toolbar1'=> 'bold,italic,underline,bullist,numlist,link,unlink,forecolor,undo,redo'
                        ),
                        'editor_class' => 'ui textarea zbs-email-thread'
                );
                wp_editor( htmlspecialchars_decode($content), 'zbs_send_email_thread',  $editorSettings); 
            ?>  
            <?php
                do_action('zbs_email_schedule_send_time');
            ?>
            <div class='zbs-send-email-thread-button ui button blue'><?php _e('Send','zero-bs-crm'); ?></div>
            </div>


      
        </div>

        <div class='zbs-email-contact-info app-content'>
              <?php 
                //the customer information pane - get using AJAX

              //  $customer_panel = zeroBSCRM_emails_customer_panel();

                //defaults
                $customer_panel['avatar'] = '';
                $customer_panel['customer']['fname'] = 'John';
                $customer_panel['customer']['lname'] = 'Doe';
                $customer_panel['customer']['status'] = __('Lead','zero-bs-crm');
                $customer_panel['tasks'] = array();
                $customer_panel['trans_value'] = 0;
                $customer_panel['quote_value'] = 0;

                echo "<div class='customer-panel-header'>";
                    echo "<div class='panel-edit-contact'>";
                        echo "<a class='edit-contact-link' href='" . admin_url('admin.php?page=zbs-add-edit&action=edit&zbsid=') ."'>" . __('Edit Contact','zero-bs-crm') . "</a>";
                    echo "</div>";
                    echo "<div id='panel-customer-avatar'>" . $customer_panel['avatar'] . "</div>";
                    echo "<div id='panel-name'>" . $customer_panel['customer']['fname'] . " " . $customer_panel['customer']['lname'] . "</div>";

                    echo "<div id='panel-status' class='ui label ".$customer_panel['customer']['status']."'>" . $customer_panel['customer']['status'] . "</div>";
                    
                    echo "<div class='simple-actions zbs-hide'>";
                        echo "<a class='ui label circular'><i class='ui icon phone'></i></a>";
                        echo "<a class='ui label circular'><i class='ui icon envelope'></i></a>";
                    echo "</div>";
                echo "</div>";

                $tasks = 25;
                $progress = 10;
                $completed = $tasks - $progress;

                echo "<div class='customer-panel-task-summary'>";

                    echo "<div class='task-cell'>";
                        echo "<div class='the-number total-tasks-panel'>" . $tasks . "</div>";
                        echo "<div class='the-type'>" . __('Tasks','zero-bs-crm') . "</div>";
                    echo "</div>";


                    echo "<div class='task-cell'>";
                        echo "<div class='the-number completed-tasks-panel'>" . $completed . "</div>";
                        echo "<div class='the-type'>" . __('Completed','zero-bs-crm') . "</div>";
                    echo "</div>";                 

                    echo "<div class='task-cell'>";
                        echo "<div class='the-number inprogress-tasks-panel'>" . $progress . "</div>";
                        echo "<div class='the-type'>" . __('In Progress','zero-bs-crm') . "</div>";
                    echo "</div>";


                echo "<div class='clear'></div>";

                echo "<div class='ui divider'></div>";

                echo "<div class='total-paid-wrap'>";
                        echo "<div class='total-paid cell'><div class='heading'>".__('Total Paid', 'zero-bs-crm')."</div><span class='the_value'>" . $customer_panel['trans_value'] . "</span></div>";
                        echo "<div class='total-due cell'><div class='heading'>".__('Total Due', 'zero-bs-crm')."</div><span class='the_value'>" . $customer_panel['quote_value'] . "</span></div>";
                echo "</div>";

                echo "<div class='clear'></div>";

                echo "<div class='ui divider'></div>";

                echo "<div class='panel-left-info'>";
                    echo "<i class='ui icon envelope outline'></i> <span class='panel-customer-email'></span>";
                    echo "<br/>";
                    echo "<i class='ui icon phone'></i> <span class='panel-customer-phone'></span>";

                echo "<h4 class='panel-h4'>" . __("Tasks", "zero-bs-crm") . "</h4>";

                echo "<ul class='the-tasks'>";
                foreach($customer_panel['tasks'] as $task){
                    if($task['actions']['complete'] == 1){
                        echo "<li class='complete'><i class='ui icon check green circle'></i> " . $task['title'] . "</li>";
                    }else{
                        echo "<li class='incomplete'>" . $task['title'] . "</li>";
                    }
                }
                echo "</ul>";

                echo "<div class='clear'></div>";

                echo "</div>";


                echo "<div class='clear'></div>";


              ?>
        </div>
        <div class='clear'></div>
    </div>

    <script type="text/javascript">

        // WH: 
        // ALTHOUGH THIS WORKS 
        // (Loads a sent msg)
        // It's not currently used, because send message func doesn't return ID, so just loading sent for now
        var zbsMailBoxShowSentID = <?php 

            $sentID = -1;

            if (isset($_GET['sentID'])){

                $sentID = (int)sanitize_text_field( $_GET['sentID'] );

            }

            if ($sentID > 0)
                echo $sentID;
            else
                echo -1;

        ?>;

        // WH put here to catch reload of page with 'sent' id
        // ... not sure where rest of your JS sits can't find
        jQuery(document).ready(function(){

            if (typeof window.zbsMailBoxShowSentID != "undefined" && window.zbsMailBoxShowSentID > 0 && jQuery('.zbs-email-list-' + window.zbsMailBoxShowSentID).length > 0){

                // jump to This (by fake clicking!)
                jQuery('.zbs-email-list-' + window.zbsMailBoxShowSentID).click();
            }

        });


    </script><?php

                //for the modal for selecting a canned reply
                do_action('zbs_end_emails_ui');

}



#} This is the page for "ZBS SEND EMAIL" UI
function zeroBSCRM_pages_admin_sendmail(){

    // declaring default
    $customerID = -1;

    // check perms
    if (zeroBSCRM_permsSendEmailContacts()){

      #} get the prefill..  send UI only from prefill ID
      global $zbs;

      $customerMeta = array();


      if(isset($_GET['zbsprefill']) && !empty($_GET['zbsprefill'])){
          //WH modernised for you: 
          if (!$zbs->isDAL2())
            $customerMeta = zeroBS_getCustomerMeta((int)sanitize_text_field($_GET['zbsprefill']));
          else
            $customerMeta = $zbs->DAL->contacts->getContact((int)sanitize_text_field($_GET['zbsprefill']),array('ignoreowner'    => zeroBSCRM_DAL2_ignoreOwnership(ZBS_TYPE_CONTACT)));

          //  zbs_prettyprint($customerMeta);

          $customerID = (int)sanitize_text_field($_GET['zbsprefill']);
          $toEmail = $customerMeta['email'];

      }

      if(isset($_POST['zbs-send-email-to']) && !empty($_POST['zbs-send-email-to'])){

        //sanitize text field might be f**king this up. sanitize_email
        // wh: use sanitize_email then!
          $sending_to_email = sanitize_email($_POST['zbs-send-email-to']);

          if(zeroBSCRM_validateEmail($sending_to_email)){
              //email is valid and belongs to customer..

           //   if(isset($_GET['zbsprefill']) && !empty($_GET['zbsprefill'])){
                  //pre-filled for customer...

                    $subject = ''; if (isset($_POST['zbs-send-email-title'])) $subject = zeroBSCRM_textProcess( $_POST['zbs-send-email-title'] );                
                    $content = ''; if (isset($_POST['zbs_send_email_content'])) $content = wp_kses_post($_POST['zbs_send_email_content']); // NO sanitation here because our mail func does it :)
                    $toEmail = $sending_to_email;
                    $customerID = (int)zeroBS_getCustomerIDWithEmail( $sending_to_email );

                    $cleanContent = zeroBSCRM_io_WPEditor_DBToHTML(zeroBSCRM_io_WPEditor_WPEditorToDB($content));
                    $emailHTML = zeroBSCRM_mailTemplates_directMsg(true, $cleanContent, $subject);  
                    $headers = array('Content-Type: text/html; charset=UTF-8'); 

                    $uid = get_current_user_id();

                    // mail delivery method (slug, e.g. 'zbs-whatever'):
                    $mailDeliveryMethod = -1; if (isset($_POST['zbs-mail-delivery-acc'])) $mailDeliveryMethod = sanitize_text_field( $_POST['zbs-mail-delivery-acc'] );
                    if (empty($mailDeliveryMethod)) $mailDeliveryMethod = -1;

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
                    'subject' => zeroBSCRM_textExpose($subject),
                    'headers' => $headers,
                    'body' => $emailHTML,
                    'textbody' => '',
                    'content' => $content, //cleaned by mail func
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
                    $sent = zeroBSCRM_mailDelivery_sendMessage($mailDeliveryMethod,$mailArray);

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
              _e("That is not a valid email. Please enter a valid email", "zero-bs-crm"); 
          }

      }             

  ?>
  
  <div class="ui grid">

      <div class="sixteen wide column">

        <?php 
                  // check for unsub flag + make aware
                  if (isset($customerID) && $customerID > 0 && $zbs->DAL->contacts->getContactDoNotMail($customerID)){
                  
                    $label = zeroBSCRM_UI2_label('red','',__('Email Unsubscribed','zero-bs-crm'),__('(Do Not Email Flag)','zero-bs-crm'),'do-not-email');
                    echo zeroBSCRM_UI2_messageHTML('warning',__('This contact has a flag against them:','zero-bs-crm'),$label.'<br/>'.__('(This means they\'ve asked you not to email them (Unsubscribed). You can still email them here, if you so choose.','zero-bs-crm'));
                  
                  }
           ?>
          <form autocomplete="off" id="zbs-send-single-email" class="ui form" action="<?php echo zeroBSCRM_getAdminURL($zbs->slugs['emails']); ?>" method="POST">

            <?php
            if (is_array($customerMeta) && array_key_exists('fname', $customerMeta)){
              $custName = $customerMeta['fname'] . " " . $customerMeta['lname'];
              $toEmail = $customerMeta['email'];
            }else{
              $custName = '';
              $toEmail = '';
            }


            //this needs to change after selecting a contact if they DO have an email... meh.
            if($toEmail == '' && isset($customerID)){
                echo zeroBSCRM_UI2_messageHTML('red','',__("No email exists for this contact. No message will be sent. Please edit the contact and add an email address.","zero-bs-crm"),'ui danger');
                /*
                echo "<div class='ui message red'><i class='ui icon danger'></i>";
                    _e("No email exists for this contact. No message will be sent. Please edit the contact and add an email address.","zero-bs-cr");
                echo "</div>";
                */
            }

            ?>
            

            <?php echo zeroBSCRM_CustomerTypeList('zbscrmjs_customer_setCustomerEmail',$custName,true); ?>
  
            <input type="hidden" id="zbs-send-email-to" name="zbs-send-email-to" value="<?php echo $toEmail; ?>"/>
            <br/>
            <?php zeroBSCRM_mailDelivery_accountDDL(1); ?>
            <br/>
            <br/>
            <input type="text" id="zbs-send-email-title" name="zbs-send-email-title" placeholder="<?php _e('Your email subject', "zero-bs-crm"); ?>"/>
            <br/><br/>
            <label><?php _e("Message", "zero-bs-crm"); ?></label>
            <?php
                do_action('zbs_email_canned_reply_single');
            ?>
            <?php
                $content = "";
                $editorSettings = array(
                        'media_buttons' => false,
                        'editor_height' => 220,
                        'quicktags' => false,
                        'tinymce'=> array(
                            'toolbar1'=> 'bold,italic,underline,bullist,numlist,link,unlink,forecolor,undo,redo'
                        ),
                        'editor_class' => 'ui textarea'
                );
                wp_editor( htmlspecialchars_decode($content), 'zbs_send_email_content',  $editorSettings); 
            ?>
          <br/>
          <input type="submit" class="ui button blue large right zbs-single-send-email-button" value="<?php _e("Send Email","zero-bs-crm");?>" />

          <?php do_action('zbs_single_email_schedule', $customerID) ?>

           <!--
          <div class='ui button large left save-draft-email' style="float:right"><?php //_e('Save Draft','zero-bs-crm'); ?></div>
              -->
          </form>
      </div>

      <style>
      .email-sending-record {
          padding: 10px;
      }
      time {
          white-space: nowrap;
          text-transform: uppercase;
          font-size: .5625rem;
          margin-left: 5px;
      }
      .hist-label {
          margin-right: 6px !important;
      }

      </style>



  </div>

  <script type="text/javascript">

    var zbsEmailSingleLang = {

        couldnotload: '<?php echo zeroBSCRM_slashOut(__('Could not load email thread, please try again','zero-bs-crm'),true); ?>'
    }
    var zbsContactEditPrefix = '<?php echo zbsLink('edit',-1,'zerobs_customer',true); ?>';

  </script>

  <?php

  } else {

    // no rights
    _e('You do not have permissions to access this page','zero-bs-crm');

  }

}


define('ZBSCRM_INC_EMAILUI', true);