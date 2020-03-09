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




/* ======================================================
   Edit Post - multiform data override (for metaboxes)
   ====================================================== */

  #} Updated 1.2 so that this only fires on OUR post edit pages
  #} https://www.rfmeier.net/allow-file-uploads-to-a-post-with-wordpress-post_edit_form_tag-action/
  function zeroBSCRM_update_edit_form() {

      global $post;
      
      //  if invalid $post object, return
      if(!$post)
          return;
      
      //  get the current post type
      $post_type = get_post_type($post->ID);
      
      //  if post type is not 'post', return
      #if('post' != $post_type)
      if (!in_array($post_type,array('zerobs_customer','zerobs_quote','zerobs_invoice','zerobs_transaction','zerobs_company')))
          return;

      #echo ' enctype="multipart/form-data"';
      printf(' enctype="multipart/form-data" encoding="multipart/form-data" ');

  }
  add_action('post_edit_form_tag', 'zeroBSCRM_update_edit_form');

/* ======================================================
   / Edit Post - multiform data override (for metaboxes)
   ====================================================== */



/* ======================================================
   / Edit Post Messages (i.e. "Post Updated => Event Updated")
   / See: http://ryanwelcher.com/2014/10/change-wordpress-post-updated-messages/
   ====================================================== */

add_filter( 'post_updated_messages', 'zeroBSCRM_post_updated_messages' );
function zeroBSCRM_post_updated_messages( $messages ) {

  $post             = get_post();
  $post_type        = get_post_type( $post );
  $post_type_object = get_post_type_object( $post_type );
  
  $messages['zerobs_event'] = array(
    0  => '', // Unused. Messages start at index 1.
    1  => __( 'Task updated.' ),
    2  => __( 'Custom field updated.' ),
    3  => __( 'Custom field deleted.'),
    4  => __( 'Task updated.' ),
    /* translators: %s: date and time of the revision */
    5  => isset( $_GET['revision'] ) ? sprintf( __( 'Event restored to revision from %s' ), wp_post_revision_title( (int) sanitize_text_field($_GET['revision']), false ) ) : false,
    6  => __( 'Task saved.' ),
    7  => __( 'Task saved.' ),
    8  => __( 'Task submitted.' ),
    9  => sprintf(
      __( 'Task scheduled for: <strong>%1$s</strong>.' ),
      // translators: Publish box date format, see http://php.net/date
      date_i18n(  'M j, Y @ G:i', strtotime( $post->post_date ) )
    ),
    10 => __( 'Task updated.' )
  );

        //you can also access items this way
        // $messages['post'][1] = "I just totally changed the Updated messages for standards posts";

        //return the new messaging 
  return $messages;
}


#} Deactivation error page - show if someone tried to deactivate the core with extensions still installed
function zeroBSCRM_pages_admin_deactivate_error(){
?>
    <div class='ui segment' style='text-align:center;'>
        <div style='font-size:60px;padding:30px;'>⚠️</div>
        <h3><?php _e("Error", "zero-bs-crm"); ?></h3>
        <p style='font-size:18px;'>
          <?php _e("You have tried to deactivate the Core while extensions are still active. Please de-activate extensions first.", "zero-bs-crm"); ?>
        </p>
        <p><a class='ui button blue' href="<?php echo admin_url('plugins.php'); ?>">Back to Plugins</a></p>
    </div>
<?php
}




#} Team UI page - i.e. to guide vs the wp-users.php
#} Added this to be able to make it easier for people to add team members to the CRM
#} Also to control permissions.
#} WHLOOK - is there a way of us finding out from telemetry how many people are actually using 
#} roles that are like the "customer" only role - as discussed I think our CRM has evolved past this
#} and we should have users as "CRMTEAM" members, and then "manage permissions" for them (vs the actual specific "role") 
function zeroBSCRM_pages_admin_team(){

    global $ZBSCRM_t,$wpdb;
    
    #} we can do this via AJAX eventually - but for now lets do it via normal $_POST stuff...
    $searching_users = false;
    
    #} User Search...
    if(isset($_POST['zbs-search-wp-users'])){

      $search = sanitize_text_field($_POST['zbs-search-wp-users']);
      $users = new WP_User_Query( array(
          'search'         => '*'.esc_attr( $search ).'*',
          'search_columns' => array(
              'user_nicename',
              'user_email',
          ),
      ) );
      $wp_users = $users->get_results();
        
      $zbsRoleIDs = array();
      foreach ( $wp_users as $user ) {
            $zbsRoleIDs[] = $user->ID;
      }

      $searching_users = true;

//      zbs_prettyprint($users_found);

    }else{
      // Zero BS CRM team roles.. 

        $role = array('zerobs_customermgr','zerobs_admin','administrator','zerobs_quotemgr','zerobs_invoicemgr','zerobs_transactionmgr','zerobs_mailmgr'); 




        $crm_users = get_users(array('role__in' => $role, 'orderby' => 'ID'));
        foreach ( $crm_users as $user ) {
            $zbsRoleIDs[] = $user->ID;
        }





    }



?>
    <script type="text/javascript">

        jQuery(document).ready(function($){

          jQuery('#zbs-search-wp-users').on("click"){
              jQuery("#zbs-users-search").submit();
          }


        });
    </script>

        



    <div class="ui segment zbs-inner-segment">
    <div id="zbs-team-mechanics">

      <form id="zbs-users-search" action="#" method="POST">
      <div class="ui search left" style="background:white;width:300px;float:left">
        <div class="ui icon input" style="width:100%;">
          <input class="prompt" name="zbs-search-wp-users"  type="text" placeholder="Search WordPress Users...">
          <i class="search icon" id="zbs-search-wp-users"></i>
        </div>
        <div class="results"></div>
      </div>
    </form>


        <a style="margin-left:10px;" class="ui button right" href="<?php echo admin_url('user-new.php?zbsslug=zbs-add-user'); ?>">
        <i class="add icon"></i> 
          <?php _e("Add New Team Member","zero-bs-crm");?>
        </a>

    </div>

    <div class='clear'></div>

    <div class="ui divider"></div>

    <table class="ui fixed single line celled table" id="zbs-team-user-table">
      <tbody>
        <th style="width:40px;"><?php _e("ID", "zero-bs-crm"); ?></th>
        <th><?php _e("Team member", "zero-bs-crm"); ?></th>
        <th><?php _e("Role", "zero-bs-crm"); ?></th>
        <th><?php _e("Last login", "zero-bs-crm"); ?></th>
        <th><?php _e("Manage permissions", "zero-bs-crm"); ?></th>
        <?php
        foreach($zbsRoleIDs as $ID){
            $user = get_user_by('ID', $ID);
            
            // zbs_prettyprint($user);

            $edit_url = admin_url('user-edit.php?user_id=' . $ID . '&zbsslug=zbs-edit-user');

            $caps_output = "";
            foreach($user->caps as $k => $v){
              $caps_output .= " " . zeroBSCRM_caps_to_nicename($k);
            }

            echo "<tr><td>".$ID."</td><td>" . get_avatar( $ID, 30 ) . "<div class='dn'>" . $user->display_name . "</div></td><td>" . $caps_output . "</td>";

            echo "<td>" . zeroBSCRM_wpb_lastlogin($ID) . " " . __("ago","zero-bs-crm") . "</td>";

            echo "<td><a href='".$edit_url."'' data-uid='".$ID."' class='zbs-perm-edit ui button mini blue'>";

            _e("Manage permissions", "zero-bs-crm"); 

            echo "</a></td>";

            echo "</tr>";

          //  zbs_prettyprint($user);
        }



        ?>

      </tbody>
    </table>


      </div>

<?php
}

#} this function turns our caps into a nicename for outputting
function zeroBSCRM_caps_to_nicename($caps = ''){

  $nicename = '';

  switch($caps){
    case 'administrator':
    $nicename = __("Full ZBS CRM Permissions (WP Admin)", "zero-bs-crm");
    break;
    case 'zerobs_admin':
    $nicename = __("Full ZBS CRM Permissions", "zero-bs-crm");
    break;

    case 'zerobs_customermgr':
    $nicename = __("Manage Customers Only", "zero-bs-crm");
    break;

    case 'zerobs_invoicemgr':
    $nicename = __("Manage Invoices Only", "zero-bs-crm");
    break;


    default: 
    $nicename = ucfirst($caps);
    break;

  }

  return $nicename;

}


#} This is NOTIFICATIONS UI on the back on FEEDBACK from customers and Google Forms we were having people
#} saying things like "This is GREAT, just wished it integrated with WooCommerce (i.e. unaware it does)"
#} My thoughts here is it a page which detects certain classes etc (e.g. WooCommerce) and displays a notification
#} about it, and the benefits of them getting WooSync :-) 
function zeroBSCRM_pages_admin_notifications(){

    global $zeroBSCRM_notifications;

    #} have a whole plugin here, which does browser notifications etc for Plugin Hunt Theme
    #} have brought it into its own INCLUDE does things like new.comment have replaced it with our
    #} IA actions (new.customer, customer.status.change) 

    ?>



    <?php
    $zeroBSCRM_notifications = get_option('zbs-crm-notifications');
    if($zeroBSCRM_notifications == ''){
      $zeroBSCRM_notifications = 0;
    }
    #} WooCommerce for starters - 

    zeroBSCRM_notifyme_activity();

    #} Store in a notification here, e.g.
    $recipient = get_current_user_id();
    $sender = -999; //in this case...  we can call ZBS our -999 user
    $post_id = 0; //i.e. not a post related activity
    $type = 'woosync.suggestion';   //this is a extension suggestion type
   // notifyme_insert_notification($recipient,$sender,$post_id,$type);



}

#} Tag Manager Page
function zeroBSCRM_pages_admin_tags(){

  #} run some defaults here.. 
  $type      = 'contact';
  

  if(isset($_GET['tagtype']) && !empty($_GET['tagtype'])) $type    = sanitize_text_field($_GET['tagtype']);

  switch ($type){

    case 'contact':

        $upsellBoxHTML = '';

            #} has ext? Give feedback
            if (!zeroBSCRM_hasPaidExtensionActivated()){ 


                // first build upsell box html
                $upsellBoxHTML = '<!-- Bulk Tagger --><div style="padding-right:1em">';
                $upsellBoxHTML .= '<h4>Tagging Tools:</h4>';

                    $upTitle = __('Bulk Tagger PRO',"zero-bs-crm");
                    $upDesc = __('Did you know that we\'ve made an extension for bulk tagging contacts based on transactions?',"zero-bs-crm");
                    $upButton = __('View Bulk Tagger',"zero-bs-crm");
                    $upTarget = 'https://zerobscrm.com/product/bulk-tagger/';

                    $upsellBoxHTML .= zeroBSCRM_UI2_squareFeedbackUpsell($upTitle,$upDesc,$upButton,$upTarget); 

                $upsellBoxHTML .= '</div><!-- / Import Tools box -->';

            } else { 

              // later this can point to??? https://zerobscrm.com/kb/knowledge-base/how-to-get-customers-into-zero-bs-crm/ 

            } 

        $tagView = new zeroBSCRM_TagManager(array(

                'objTypeID'     => ZBS_TYPE_CONTACT, // v3.0 +
                'objType'       => 'contact',
                'singular'      => __('Contact',"zero-bs-crm"),
                'plural'        => __('Contacts',"zero-bs-crm"),
                'postType'      => 'zerobs_customer',
                'postPage'      => 'manage-contact-tags', // <-- pre v3.0, post, it needs objTypeID only :)
                'langLabels'    => array(

                    // labels
                    //'what' => __('WHAT',"zero-bs-crm"),

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;

    case 'company': // v3+

        $upsellBoxHTML = '';

            #} has ext? Give feedback
            if (!zeroBSCRM_hasPaidExtensionActivated()){ 


                // first build upsell box html
                $upsellBoxHTML = '<!-- Bulk Tagger --><div style="padding-right:1em">';
                $upsellBoxHTML .= '<h4>Tagging Tools:</h4>';

                    $upTitle = __('Bulk Tagger PRO',"zero-bs-crm");
                    $upDesc = __('Did you know that we\'ve made an extension for bulk tagging contacts based on transactions?',"zero-bs-crm");
                    $upButton = __('View Bulk Tagger',"zero-bs-crm");
                    $upTarget = 'https://zerobscrm.com/product/bulk-tagger/';

                    $upsellBoxHTML .= zeroBSCRM_UI2_squareFeedbackUpsell($upTitle,$upDesc,$upButton,$upTarget); 

                $upsellBoxHTML .= '</div><!-- / Import Tools box -->';

            } else { 

              // later this can point to??? https://zerobscrm.com/kb/knowledge-base/how-to-get-customers-into-zero-bs-crm/ 

            } 

        $tagView = new zeroBSCRM_TagManager(array(

                'objTypeID'     => ZBS_TYPE_COMPANY, // v3.0 +
                'objType'       => 'company',
                'singular'      => __('Company',"zero-bs-crm"),
                'plural'        => __('Companies',"zero-bs-crm"),
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postType'      => 'zerobs_company',
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postPage'      => 'manage-contact-tags', // <-- pre v3.0, post, it needs objTypeID only :)
                'langLabels'    => array(

                    // labels
                    //'what' => __('WHAT',"zero-bs-crm"),

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;

    case 'quote': // v3+

        $upsellBoxHTML = '';

        $tagView = new zeroBSCRM_TagManager(array(

                'objTypeID'     => ZBS_TYPE_QUOTE, // v3.0 +
                'objType'       => 'quote',
                'singular'      => __('Quote',"zero-bs-crm"),
                'plural'        => __('Quotes',"zero-bs-crm"),
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postType'      => 'zerobs_company',
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postPage'      => 'manage-contact-tags', // <-- pre v3.0, post, it needs objTypeID only :)
                'langLabels'    => array(

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;

    case 'invoice': // v3+

        $upsellBoxHTML = '';

        $tagView = new zeroBSCRM_TagManager(array(

                'objTypeID'     => ZBS_TYPE_INVOICE, // v3.0 +
                'objType'       => 'invoice',
                'singular'      => __('Invoice',"zero-bs-crm"),
                'plural'        => __('Invoices',"zero-bs-crm"),
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postType'      => 'zerobs_company',
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postPage'      => 'manage-contact-tags', // <-- pre v3.0, post, it needs objTypeID only :)
                'langLabels'    => array(

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;

    case 'transaction':

        $upsellBoxHTML = '';

            #} has ext? Give feedback
            if (!zeroBSCRM_hasPaidExtensionActivated()){ 


                // first build upsell box html
                $upsellBoxHTML = '<!-- Bulk Tagger --><div style="padding-right:1em">';
                $upsellBoxHTML .= '<h4>Tagging Tools:</h4>';

                    $upTitle = __('Bulk Tagger PRO',"zero-bs-crm");
                    $upDesc = __('Did you know that we\'ve made an extension for bulk tagging contacts based on transactions?',"zero-bs-crm");
                    $upButton = __('View Bulk Tagger',"zero-bs-crm");
                    $upTarget = 'https://zerobscrm.com/product/bulk-tagger/';

                    $upsellBoxHTML .= zeroBSCRM_UI2_squareFeedbackUpsell($upTitle,$upDesc,$upButton,$upTarget); 

                $upsellBoxHTML .= '</div><!-- / Import Tools box -->';

            } else { 

              // later this can point to??? https://zerobscrm.com/kb/knowledge-base/how-to-get-customers-into-zero-bs-crm/ 

            } 

        $tagView = new zeroBSCRM_TagManager(array(

                'objType'       => 'transaction',
                'singular'      => __('Transaction',"zero-bs-crm"),
                'plural'        => __('Transactions',"zero-bs-crm"),
                'postType'      => 'zerobs_transaction',
                'listViewSlug'      => 'manage-transaction-tags',
                'langLabels'    => array(

                    // labels
                    //'what' => __('WHAT',"zero-bs-crm"),

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;

    case 'form': // v3+

        $upsellBoxHTML = '';

        $tagView = new zeroBSCRM_TagManager(array(

                'objTypeID'     => ZBS_TYPE_FORM, // v3.0 +
                'objType'       => 'form',
                'singular'      => __('Form',"zero-bs-crm"),
                'plural'        => __('Forms',"zero-bs-crm"),
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postType'      => 'zerobs_company',
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postPage'      => 'manage-contact-tags', // <-- pre v3.0, post, it needs objTypeID only :)
                'langLabels'    => array(

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;

    case 'event': // v3+

        $upsellBoxHTML = '';

        $tagView = new zeroBSCRM_TagManager(array(

                'objTypeID'     => ZBS_TYPE_EVENT, // v3.0 +
                'objType'       => 'event',
                'singular'      => __('Event',"zero-bs-crm"),
                'plural'        => __('Events',"zero-bs-crm"),
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postType'      => 'zerobs_company',
                // pre v3.0 companies didn't have tags, so no need for legacy: 'postPage'      => 'manage-contact-tags', // <-- pre v3.0, post, it needs objTypeID only :)
                'langLabels'    => array(

                ),
                'extraBoxes' => $upsellBoxHTML
        ));

        $tagView->drawTagView();

        break;



  }

}

/* =======
Edit File UI
========== */

function zeroBSCRM_pages_edit_file(){

  global $zbs;

    $customer = -1; if (isset($_GET['customer'])) $customer = (int)sanitize_text_field($_GET['customer']);
    // or company...
    $company = -1; if (isset($_GET['company'])) $company = (int)sanitize_text_field($_GET['company']);

    $fileid = (int)sanitize_text_field($_GET['fileid']);   //file ID is the ID of the file we want .. 

    if ($customer > 0 || $company > 0){

    //customer and file passed as variables. Allow us to edit the file title, description, show on portal, etc.
    
    //IF fileid is blank and newfile = true .. show the drag + drop uploader... if using fileslots, allow this to be chosen in the edit UI too..

    if ($customer > 0)
        $zbsFiles = zeroBSCRM_getCustomerFiles($customer);
    else if ($company > 0)
        $zbsFiles = zeroBSCRM_files_getFiles('company',$company);

    //file to edit is
    $ourFile = $zbsFiles[$fileid];
    $originalSlot = -1; if ($customer > 0) $originalSlot = zeroBSCRM_fileslots_fileSlot($ourFile['file'],$customer,ZBS_TYPE_CONTACT);

    if(isset($_POST['save']) && $_POST['save'] == -1){
      //we are saving down the file details... will add nonce too etc.
      echo "<div class='ui message blue' style='margin-right:20pz'><i class='icon info'></i> ".__("Details saved","zerobscrm") ."</div>";

      $title = sanitize_text_field($_POST['title']);
      $desc = wp_kses_post($_POST['desc']); // WH: should this be sanitized?
      $portal = ""; if(isset($_POST['fileportal']) && !empty($_POST['fileportal'])) $portal = (int)sanitize_text_field($_POST['fileportal']);
      $slot = ""; if(isset($_POST['fileslot']) && !empty($_POST['fileslot'])) $slot = sanitize_text_field($_POST['fileslot']);


      $ourFile['title'] = $title;
      $ourFile['desc'] = $desc;
      $ourFile['portal'] = $portal; // only customer
      // this is logged here, but can basically be ignored (is only logged via meta truthfully)
      //$ourFile['slot'] = $slot; 

      // 2.95 (support for CPP)
      $ourFile = apply_filters('zbs_cpp_fileedit_save',$ourFile,$_POST);

      $zbsFiles[$fileid] = $ourFile;

      if ($customer > 0)
        zeroBSCRM_updateCustomerFiles($customer, $zbsFiles);
      else if ($company > 0)
        zeroBSCRM_files_updateFiles('company',$company,$zbsFiles);

      // if slot, update manually (custs only)
      if (!isset($_POST['noslot']) && $customer > 0){

        // this'll empty the slot, if it previously had one and moved to new, or emptied
        // means 1 slot : 1 file
        if (!empty($originalSlot) && $slot != $originalSlot) zeroBSCRM_fileslots_clearFileSlot($originalSlot,$customer,ZBS_TYPE_CONTACT);

        // some slot
        // this will OVERRITE whatevers in that slot
        if (!empty($slot) && $originalSlot != $slot && $slot !== -1) zeroBSCRM_fileslots_addToSlot($slot,$ourFile['file'],$customer,ZBS_TYPE_CONTACT,true);        

        // reget
        $originalSlot = zeroBSCRM_fileslots_fileSlot($ourFile['file'],$customer,ZBS_TYPE_CONTACT);

      } 


    }

    // zeroBSCRM_updateCustomerFiles($cID, $zbsFiles);

    // get name
    $file = zeroBSCRM_files_baseName($ourFile['file'],isset($ourFile['priv']));


    /* debug 
    echo '<pre>';
    print_r(zeroBSCRM_fileSlots_getFileSlots(ZBS_TYPE_CONTACT));
    print_r(zeroBSCRM_fileslots_allSlots($customer,1));
    echo '</pre>'; */
    /* debug 
    echo '<pre>';
    print_r($ourFile);
    echo '</pre>'; */
    ?>

    <div class = "ui segment zbs-cp-file-edit-page">
      <?php

        // CPP thumb support. If file exists, display here
        if (function_exists('zeroBSCRM_cpp_getThumb')){

            $thumb = zeroBSCRM_cpp_getThumb($ourFile);
            if (!empty($thumb)){

                  // hacky solution to avoid shadow on 'filetype' default imgs
                  $probablyFileType = false; if (strpos($thumb, 'i/filetypes/') > 0) $probablyFileType = true;

                echo '<img src="'.$thumb.'" alt="'.__('File Thumbnail','zero-bs-crm').'" class="zbs-file-thumb';
                if ($probablyFileType) echo ' zbs-cp-file-img-default';
                echo '" />';
            }
        }

      ?>
      <h4><?php _e("Edit File Details", "zerobscrm"); ?></h4>
      <p>
        <?php _e("You are editing details for the following file", "zerobscrm"); ?>
        <br/>
        <em><?php echo $file; ?>
        (<a href="<?php echo $ourFile['url']; ?>" target="_blank"><?php _e("View file","zerobscrm"); ?></a>)</em>
      </p>
      <form class="ui form" method="POST" action="#">

          <label for="title"><?php _e("Title","zerobscrm");?></label>
          <input class="ui field input" id="title" name="title" value="<?php if(isset($ourFile['title'])) echo $ourFile['title'];?>" />

          <label for="desc"><?php _e("Description","zerobscrm"); ?></label>
          <textarea class="ui field textarea" id="desc" name="desc"><?php if(isset($ourFile['desc'])) echo $ourFile['desc'];?></textarea>

          <?php if(defined('ZBS_CLIENTPRO_TEMPLATES') && $customer > 0){ ?>
          <label for="fileportal"><?php _e("Show on Client Portal", "zerobscrm"); ?></label>
          <select class="ui field select" id="fileportal" name="fileportal">
              <option value="0" <?php if(isset($ourFile['portal']) && $ourFile['portal'] == 0 ) echo "selected"; ?>><?php _e("No","zerobscrm"); ?></option>
              <option value="1" <?php if(isset($ourFile['portal']) && $ourFile['portal'] == 1 ) echo "selected"; ?>><?php _e("Yes", "zerobscrm"); ?></option>
          </select>
          <?php } else { 
            
            // no client portal pro, so UPSELL :) 


            ##WLREMOVE 
            // only get admins!
            if (current_user_can('admin_zerobs_manage_options') && $customer > 0){ ?>
              <label><?php _e("Show on Client Portal", "zerobscrm"); ?></label>
              <div style="margin-bottom:1em;line-height: 1.8em"><input type="checkbox" name="fileportal" disabled="disabled" />&nbsp;&nbsp;<a href="<?php echo $zbs->urls['upgrade']; ?>?utm_content=inplugin-fileedit" target="_blank"><?php _e('Upgrade to a Bundle','zero-bs-crm'); ?></a> <?php _e('(and get Client Portal Pro) to enable this','zero-bs-crm'); ?>.</div><?php 
            }
            ##/WLREMOVE 

          } ?>

          <?php
            if ($customer > 0){
              // File slots 

              // Custom file attachment boxes
              //$settings = zeroBSCRM_getSetting('customfields'); $cfbInd = 1;
              //if (isset($settings['customersfiles']) && is_array($settings['customersfiles']) && count($settings['customersfiles']) > 0) {
              $fileSlots = zeroBSCRM_fileSlots_getFileSlots();
              // get all slots (to show 'overrite' warning)
              $allFilesInSlots = zeroBSCRM_fileslots_allSlots($customer,ZBS_TYPE_CONTACT);

              if (count($fileSlots) > 0){

                ?><label for="fileslot"><?php _e("Assign to Custom File Upload Box", "zerobscrm"); ?></label>
                <select class="ui field select" id="fileslot" name="fileslot">
                  <option value="-1"><?php _e("None", "zerobscrm");?></option><?php

                  foreach ($fileSlots as $cfb){

                      $nExtra = '';
                      if ($originalSlot != $cfb['key'] && isset($allFilesInSlots[$cfb['key']]) && !empty($allFilesInSlots[$cfb['key']])) 
                          $nExtra = ' ('.__('Current file','zero-bs-crm').': '.zeroBSCRM_files_baseName($allFilesInSlots[$cfb['key']],true).')';


                      echo '<option value="'.$cfb['key'].'"';
                      if (isset($originalSlot) && $originalSlot == $cfb['key']) echo ' selected="selected"';
                      echo '>'.$cfb['name'].$nExtra.'</option>';

                  }

                ?></select><?php

              } else echo '<input type="hidden" name="noslot" value="noslot" />';

            } ?>

            <?php 
                // Client portal pro integration
                do_action('zbs_cpp_fileedit',$ourFile);
            ?>

          <input type="hidden" value="-1" id="save" name="save"/>

          <input type="submit" class="ui button blue" value="<?php _e("Save details", "zerobscrm"); ?>"/>

      </form>

    </div>



    <?php

  } // if cid
}



/* ======================================================
   Admin Page Funcs (used for all adm pages)
   ====================================================== */

    #} Admin Page header
    function zeroBSCRM_pages_header($subpage=''){

      //global $wpdb, $zbs; #} Req
      // legacy.
   
    }


    #} Admin Page footer
    function zeroBSCRM_pages_footer(){
        
      // no longer needed now we don't wrap within zeroBSCRM_pages_header()
      // echo '</div>';
      // legacy.
      
    }


    #} Gross redir page
    function zeroBSCRM_pages_logout() {

      ?><script type="text/javascript">window.location='<?php echo wp_logout_url(); ?>';</script><h1 style="text-align:center">Logging you out!</h1><?php

    }

/* ======================================================
   / Admin Page Funcs (used for all adm pages)
   ====================================================== */



/* ======================================================
  Pagination functions
  ===================================================== */

  function zeroBSCRM_pagelink($page){
    if($page > 0){
      $pagin = $page;
    }else{
      $pagin = 0;
    }
    $zbsurl = get_admin_url('','admin.php?page=customer-searching') ."&zbs_page=".$pagin;
    return $zbsurl;
  }

  function zeroBSCRM_pagination( $args = array() ) {
      $defaults = array(
          'range'           => 4,
          'previous_string' => __( 'Previous', 'zero-bs-crm' ),
          'next_string'     => __( 'Next', 'zero-bs-crm' ),
          'before_output'   => '<ul class="pagination">',
          'after_output'    => '</ul>',
          'count'       => 0,
          'page'        => 0
      );
      
      $args = wp_parse_args( 
          $args, 
          apply_filters( 'wp_bootstrap_pagination_defaults', $defaults )
      );
      
      $args['range'] = (int) $args['range'] - 1;
      $count = (int)$args['count'];
      $page  = (int)$args['page'];
      $ceil  = ceil( $args['range'] / 2 );
      
      if ( $count <= 1 )
          return FALSE;
      
      if ( !$page )
          $page = 1;
      
      if ( $count > $args['range'] ) {
          if ( $page <= $args['range'] ) {
              $min = 1;
              $max = $args['range'] + 1;
          } elseif ( $page >= ($count - $ceil) ) {
              $min = $count - $args['range'];
              $max = $count;
          } elseif ( $page >= $args['range'] && $page < ($count - $ceil) ) {
              $min = $page - $ceil;
              $max = $page + $ceil;
          }
      } else {
          $min = 1;
          $max = $count;
      }
      
      $echo = '';
      $previous = intval($page) - 1;
      $previous = esc_attr( zeroBSCRM_pagelink($previous) );
      
      $firstpage = esc_attr( zeroBSCRM_pagelink(0) );

      if ( $firstpage && (1 != $page) )
          $echo .= '<li class="previous"><a href="' . $firstpage . '">' . __( 'First', 'text-domain' ) . '</a></li>';
      if ( $previous && (1 != $page) )
          $echo .= '<li><a href="' . $previous . '" title="' . __( 'previous', 'text-domain') . '">' . $args['previous_string'] . '</a></li>';
      
      if ( !empty($min) && !empty($max) ) {
          for( $i = $min; $i <= $max; $i++ ) {
              if ($page == $i) {
                  $echo .= '<li class="active"><span class="active">' . str_pad( (int)$i, 2, '0', STR_PAD_LEFT ) . '</span></li>';
              } else {
                  $echo .= sprintf( '<li><a href="%s">%002d</a></li>', esc_attr( zeroBSCRM_pagelink($i) ), $i );
              }
          }
      }
      
      $next = intval($page) + 1;
      $next = esc_attr( zeroBSCRM_pagelink($next) );
      if ($next && ($count != $page) )
          $echo .= '<li><a href="' . $next . '" title="' . __( 'next', 'text-domain') . '">' . $args['next_string'] . '</a></li>';
      
      $lastpage = esc_attr( zeroBSCRM_pagelink($count) );
      if ( $lastpage ) {
          $echo .= '<li class="next"><a href="' . $lastpage . '">' . __( 'Last', 'text-domain' ) . '</a></li>';
      }
      if ( isset($echo) )
          echo $args['before_output'] . $echo . $args['after_output'];
  }


/* ======================================================
   Admin Pages
   ====================================================== */

#} Add admin notice if account is not connected or subscription is not active


// For dismissing. Do it via get_option('prefix_{notice}_{name}'). e.g.
// zerobscrm_

// put into the data- vars of the dismiss message

// have jQuery (admin wide) to $.get

// in fact, use a class found here: https://www.alexgeorgiou.gr/persistently-dismissible-notices-wordpress/

/* WH REMOVED 10/12/18 - we're now hiding wp notices ZBS-wide
function zeroBSCRM_adminNotices_notConnected(){
  $class = 'notice notice-error alert ui message error is-dismissible';
  global $plugin_page, $zbs_update_avail, $zeroBSCRMadminNotice, $zbs;

  $connect_url = esc_url(admin_url('admin.php?page='.$zbs->slugs['connect']));
  $items = get_option('zbs_account_items');

  $screen = get_current_screen();

 if($screen->parent_file == 'edit.php?post_type=zerobs_customer'){

    $zbs_settings = esc_url(admin_url('admin.php?page='.$zbs->slugs['settings']));   
    $zbs_custom = esc_url(admin_url('admin.php?page='.$zbs->slugs['settings']).'&tab=customfields');
    $zbs_sort = esc_url(admin_url('admin.php?page='.$zbs->slugs['settings']).'&tab=fieldsorts');

    $message = __("Do you want to add more fields or hide some fields? Head to ","zero-bs-crm") . "<a href='". $zbs_settings ."' target='_blank'>" . __("General Settings","zero-bs-crm") . "</a>" . __(" to hide Main Address (checkbox), then ","zero-bs-crm") . "<a href='". $zbs_custom  ."'>" . __("Custom Fields","zero-bs-crm") . "</a>" .  __(" to add new fields, and ","zero-bs-crm") . "<a href='". $zbs_sort ."'>" . __("Field Sorts","zero-bs-crm") . "</a>" . __(" to control their order","zero-bs-crm");


    $zeroBSCRMadminNotice->info($message, true, 'add-new-ui-message');
  
  }

  if(is_array($items)){
    foreach($items as $item){
        if($item['status'] != 'Active'){
           $message = __('Your subscription for ',"zero-bs-crm") . ' <b>'.stripslashes($item['name']) . ' </b>' . __('is not active please re-activate',"zero-bs-crm");
          $zeroBSCRMadminNotice->error($message, true, $item['name']);
        }
    }
  }
 
 $class = 'notice notice-error alert ui message error';
  if($plugin_page != 'zerobscrm-connect' && !get_option('zbs_account_connected') && zeroBSCRM_hasPaidExtensionActivated()){
    $message = __('You have not yet connected your Zero BS CRM account! Please ',"zero-bs-crm") .'<a href="'.$connect_url.'">' .    __('click here to connect',"zero-bs-crm") . '</a>' .  __(' and keep up to date',"zero-bs-crm");
   //  $zeroBSCRMadminNotice->error($message, true, 'zbs-connected-account');
  }  
  if(get_option('zbs_update_avail') && $plugin_page != 'zerobscrm-connect' && zeroBSCRM_hasPaidExtensionActivated()){
    
    /* Not required ! / WH Removed - shows up in funky places? If we do get this to work, have to make it unanimously NOT show up on OUR pages?

     $message = __('One or more of your ZBS CRM extensions',"zero-bs-crm") . ' <a href="'.$connect_url.'">' . __('needs updating',"zero-bs-crm") . '</a>.';
     $zeroBSCRMadminNotice->warning($message, true, 'one-or-more');    
    * /


     //pop in a Notify Me Notification here instead....?
     $cid = get_current_user_id();

     //use transients...
     $zbsuppy = get_transient('zbs-one-or-more');
     if(!$zbsuppy){
       zeroBSCRM_notifyme_insert_notification($cid, -999, -1, 'extension.new.update.avail','');
       set_transient( 'zbs-one-or-more', 20, 24 * 7 * HOUR_IN_SECONDS );
    }

  }

  #} TO DO: ADD NOTICE FOR "TOO MANY SITES"
  if(get_option('zbs_too_many_sites')){
    $message = __('You have too many sites active. Please upgrade your ZBS CRM license by',"zero-bs-crm") . '<b><a href="https://zerobscrm.com/kb/submit-a-ticket/">' . __('contacting us',"zero-bs-crm") . '</a></b>';
    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );     
  }  
}


add_action( 'admin_notices', 'zeroBSCRM_adminNotices_notConnected' );
*/


#} Connect to Zero BS CRM 
function zeroBSCRM_pages_connectZBS(){
    ?>
      <div class="ui segment" style="margin-right:15px;text-align:center;">
        <h3 style="font-weight:900;"><?php _e("Zero BS CRM Licensing Core: Coming Soon to v3.0", "zero-bs-crm"); ?></h3>
      </div>
    <?php
}

#} turn this OFF (due re-write)
function zeroBSCRM_pages_connectZBS2(){
    
    global $zbs;

    // WH removed, not used $url = "https://zerobscrm.com/";

    // catch "unlink"
    if (isset($_GET['unlink']) && $_GET['unlink'] == 'unlinkme'){

        // brutal, should check rights, maybe remove extensions?

        update_option('zbs_account_connected',false);
        delete_option('zerobscrm_dismissed_zbs-connected-account');

        delete_option('zbs_account_access');
        delete_option('zbs_account_items');
        delete_transient('zbs_extension_check');

        $disconnectedAcc = true;

    }

    // WH Moved here to build a "has paid extensions"
      
            global $zbs_update_avail;
            $zbsAccountConnected = false;
            $zbs_all = get_plugins();
            $response = wp_safe_remote_get( $zbs->urls['api'] );
            $official_ver = json_decode($response['body'],true);
            $zbs_plugins = array();
            $zbs_update_avail = false;

            // brutal quick check if have any installed, so can upsell if not - hacked from MS's code
            $installedExts = 0;
            foreach($zbs_all as $zbs_plugin){

              if($zbs_plugin['Name'] != 'Zero BS CRM'){

                  if(stripos($zbs_plugin['Name'], 'Zero Bs') !== false) $installedExts++;

              } // / is not zb

            } // / foreach plugin



    ?>
    <script type="text/javascript"> 

    var zeroBSCRMJS_thisPage = '<?php echo admin_url('admin.php?page='.$zbs->slugs['connect']); ?>';
    jQuery(document).ready(function(){
        jQuery('.connect-account-button button').on("click",function(e){
            jQuery('.connect-account-form').toggle();

            // wh - also hide these, makes more focus
            jQuery('.connect-account-button').toggle();
            jQuery('.installed-zbs-plugins').toggle();
        });

        jQuery('.connect-zbs-account').on("click",function(e){
          e.preventDefault();
          var zbs_connect_url = 'https://zerobscrm.com/my-account/';
          if(jQuery(this).hasClass('disabled')){
            return false;
          }else{
            jQuery(this).addClass('disabled');
          }
          var post_data = { zbsaccountemail: jQuery('#accountemail').val(), zbsaccountkey: jQuery('#accountkey').val(), zbsaccountupdates: jQuery('#accountupdates').is(":checked"), zbshomeurl: '<?php echo home_url(); ?>' };

          jQuery('.connect-account-form, .connect-account-button').hide();
          jQuery('.loadingAlert').show();
          jQuery('.loadingAlert').css("visibility", "visible");
          var zbs_html = ' <div class="zbs-extensions"><h4 style="text-align:center">ðŸŽ‰ðŸŽ‰ðŸŽ‰Thank you for connecting your accountðŸŽ‰ðŸŽ‰ðŸŽ‰</h4>';
          jQuery.ajax({
            url: zbs_connect_url,
            data: post_data,
            dataType: 'jsonp',
            crossDomain: true,
            jsonpCallback: 'zbscallback',
            type: 'GET',
            success: function( response ) {
                  if(response.message == 'account_verified'){
                    jQuery.each(response.items, function(i, item) {
                      zbs_html = zbs_html + '<div>' + item.name + '<span class="'+item.status+'">' + item.status + '</span></div>';
                    });
                    zbs_store_trans(response.items);  //posts item array to transient..
                    zbs_html = zbs_html + '<hr /><a href="' + window.zeroBSCRMJS_thisPage + '" class="ui button primary large">View Extension Versions</a></div>';
                    jQuery('.result').html(zbs_html);
                    jQuery('.loadingAlert').hide();
                  }else{
                      jQuery('.result').html("<div class='ui message error marb'>Error. Your Account Details are incorrect or the server cannot handle your request. If this message continues please <b>contact support</b>.</div><br/>");
                      jQuery('.loadingAlert').hide();
                      return false;
                  }
                }
            });
          });
    });

    function zbs_store_trans(items){
        var zbs_tran = jQuery('.zbs-check-tran').data('zbs-checktran');      
        if(zbs_tran == 'yes'){
            var t = {
                action: "zbs_extension_transient",
                items: items,
                email: jQuery('#accountemail').val(),
                key:  jQuery('#accountkey').val(),
                security: jQuery( '#zbs-ext-trans-ajax-nonce' ).val(),
            }  
            i = jQuery.ajax({
                url: ajaxurl,
                type: "POST",
                data: t,
                dataType: "json"
            });
            i.done(function(e) {
            }),i.fail(function(e) {
            });
        }
    }
    function zbscallback(){}
    </script>
    <style>
    .spinner {
      background: url('<?php echo home_url();?>/wp-admin/images/wpspin_light.gif') no-repeat;
    }
    .marb{
      margin-bottom:20px;
    }
    #learn{
      display:none;
    }
    </style>
    <div class='container zbs-account-connector ui segment' style="padding-top:20px;">
      <h2 style="text-align:center;font-size:25px;font-weight:900;"><?php _e("Welcome to the","zero-bs-crm");?> Zero BS CRM <?php _e("Extension Manager","zero-bs-crm");?></h2>
      <p style="font-size: 16px;margin-left: 1em;max-width: 90%;text-align:center;">
      <?php _e("Here you can check on the status of your installed premium extensions and see whether any updates are required","zero-bs-crm"); ?>
      </p>

<div class='ui divider'></div>
<?php 
// if is existing exts, or connected
//if (zeroBSCRM_hasPaidExtensionActivated() || get_option('zbs_account_connected')){ 
if ($installedExts > 0 || get_option('zbs_account_connected')){ ?>
<div class="doubling stackable three column ui grid container" style="margin-top:5px;">

    <div class="ui column" style="text-align:center;">
      <div class="ui card">
      <div class="content">
        <div class="header"><?php _e("Delete Existing Version","zero-bs-crm");?></div>
      </div>
      <div class="content">
        <h4 class="ui sub header"><i class="icon trash red" style="font-size: 42px;margin-top: 20px;margin-bottom: -16px;"></i></h4>
        <div class="ui feed">
          <p>
             <?php _e("Remove existing extension from your plugins list ","zero-bs-crm");?>
          </p>
        </div>
      </div>
      <div class="extra content">
        <a class="ui button orange" href="<?php echo esc_url(admin_url('plugins.php')); ?>"><?php _e("Delete Extensions","zero-bs-crm");?></a>
      </div>
    </div>
    </div>

    <div class="ui column" style="text-align:center;">
            <div class="ui card">
      <div class="content">
        <div class="header"><?php _e("Download New Verson","zero-bs-crm");?></div>
      </div>
      <div class="content">
        <h4 class="ui sub header"><i class="icon download blue" style="font-size: 42px;margin-top: 20px;margin-bottom: -16px;"></i></h4>
        <div class="ui feed">
          <p>
             <?php _e("Download the latest version from your account ","zero-bs-crm");?>
          </p>
        </div>
      </div>
      <div class="extra content">
        <a class="ui button blue" href="https://app.zerobscrm.com" target="_blank"><?php _e("Go To Your Account","zero-bs-crm");?></a>
      </div>
    </div>

    <?php ##WLREMOVE ?>
    <a target="_blank" href="https://zerobscrm.com/kb/knowledge-base/how-do-i-update-an-extension/"><i class="icon info"></i> <?php _e("Read our guide on how to update your extensions", "zero-bs-crm"); ?></a>
    <?php ##/WLREMOVE ?>

    </div>

    <div class="ui column" style="text-align:center;">
            <div class="ui card">
      <div class="content">
        <div class="header"><?php _e("Upload New Version","zero-bs-crm");?></div>
      </div>
      <div class="content">
        <h4 class="ui sub header"><i class="icon upload green" style="font-size: 42px;margin-top: 20px;margin-bottom: -16px;"></i></h4>
        <div class="ui feed">
          <p>
             <?php _e("Add the extension as a new plugin from your 'Add New' page","zero-bs-crm");?>
          </p>
        </div>
      </div>
      <div class="extra content">
        <a class="ui button green" href="<?php echo esc_url(admin_url('plugin-install.php?tab=upload')); ?>"><?php _e("Add New Plugin","zero-bs-crm");?></a>
      </div>
    </div>
    </div>


  </div><?php

} // / if connected/has exts

      if (isset($disconnectedAcc)) {

        // just disconnected
        echo zeroBSCRM_UI2_messageHTML('','Account Disconnected','Your ZeroBSCRM.com account has been disconnected. Please reconnect soon! <br /><br /><a href="'.admin_url('admin.php?page='.$zbs->slugs['connect']).'" class="ui button">Back</a>');

        $items = '';

      } else {

        $items = get_option('zbs_account_items');
        if(is_array($items) && count($items) > 0){ ?>
        <h4 style="text-align:center">ðŸŽ‰ðŸŽ‰ðŸŽ‰<?php _e("Thank you for connecting your account","zero-bs-crm");?>ðŸŽ‰ðŸŽ‰ðŸŽ‰</h4>
        <div class='zbs-extensions'>
            <?php
              foreach($items as $item){
                if (isset($item['name']) && isset($item['status'])) 
                  echo '<div>' . stripslashes($item['name']) . '<span class="'.$item['status'].'">' . $item['status'] . '</span></div>';
                else
                  echo '<div>Unknown Item</div>';
              }
            ?>
        </div>
        <div id="zbs-extensions-manager-unlink-wrap"><a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['connect'].'&unlink=unlinkme'); ?>"><?php _e("Disconnect from your account","zero-bs-crm");?></a></div>
        <?php } else { 

          /* actually, just show it always if ($installedExts == 0) { ?>
              <div class='connect-account-button'>
                  <div class="ui warning message">
                    <div class="header">
                      You must register before you can do that!
                    </div>
                    You cannot connect to your account until you've <a href="https://zerobscrm.com/extensions/" target="_blank">purchased a subscription</a>.
                  </div>
              </div>
          <?php } else { ?>
              <div class='connect-account-button'>
                  <button class='ui button primary large'><i class="plug icon"></i> Connect ZeroBSCRM.com Account</button>
              </div>
          <?php }  */

          /* WH: No need to show this, as below catches + upsells :) 
                  ... actually, switch it from if none, to if some... */
          
          ?><div class='connect-account-button'>
                  <?php if ($installedExts > 0) { ?> or&nbsp;&nbsp;<a href="https://zerobscrm.com/extensions/?utm_content=inplugin-licence-purchasebutt" target="_blank" class="ui positive button large"><i class="shop icon"></i> <?php _e("Purchase Extension/Bundle","zero-bs-crm");?></a><?php } ?>
              </div>
          <?php

          

        } ?>

        <div class='result'></div>
        <div class='transient'>
            <?php 
                $ext_status = get_transient('zbs_extension_check');
                //to control how often the JS should check..
                if(!$ext_status){
                  echo '<div class="zbs-check-tran" data-zbs-checktran="yes"></div>';
                }else{
                  echo '<div class="zbs-check-tran" data-zbs-checktran="no"></div>';
                }

              echo '<input type="hidden" name="zbs-ext-trans-ajax-nonce" id="zbs-ext-trans-ajax-nonce" value="' . wp_create_nonce( 'zbs-ext-trans-ajax-nonce' ) . '" />';
            ?>
        </div>

        <div class="alert alert-info loadingAlert" style="text-align:center"><?php _e('Connecting',"zero-bs-crm"); ?>...<div class="spinner"></div></div>

        <?php } // / not just disconnected ?>

      <?php if (!is_array($items) && $items == ''){ ?>
      <div class='connect-account connect-account-form'>
          <h4 class="ui header" style="text-align:center"><i class="plug icon"></i> <?php _e("Connect","zero-bs-crm");?> ZeroBSCRM.com <?php _e("Account","zero-bs-crm");?></h4>
          <form action="#" method="POST">
          <label><?php _e("Account Email","zero-bs-crm");?></label><br/>
          <input id="accountemail" type="email" value="" placeholder="<?php _e("Enter your email","zero-bs-crm");?>" class='form-control'/>
          <label><?php _e("Account Key","zero-bs-crm");?></label>
          <input id="accountkey" type="api_key" valye="" placeholder="<?php _e("Enter your account key","zero-bs-crm");?>" class='form-control'/>
          <br/>
          <p class='sign-me-up' style="text-align:center">
              <span class='italic'><?php _e("Let me know about product updates","zero-bs-crm");?></span>
              <input id="accountupdates" type="checkbox" checked/>
          </p>
          <br/>
          <button class='connect-zbs-account form-control ui button positive' type ="button"><?php _e("Connect to","zero-bs-crm");?> Zero BS CRM</button>
          </form>
          <p class='no-account'>
            <?php _e("Don't have an account yet? Get your account with an","zero-bs-crm");?> <a href="https://zerobscrm.com/extensions/?utm_content=inplugin-licence-getyouracc" target="_blank"><?php _e("extension or bundle","zero-bs-crm");?></a>.
          </p>
          <p class='no-account'>
            <?php _e("Already purchased","zero-bs-crm");?>? <a href="https://zerobscrm.com/my-account/information/" target="_blank"><?php _e("Retrieve your account key here","zero-bs-crm");?></a> (<?php _e("Account Key tab","zero-bs-crm");?>).
          </p>
      </div>
      <?php } ?>


      <div class='installed-zbs-plugins container'>
        <?php 

          // if has any paid extensions
          if ($installedExts > 0){

            ?>
        <h2 class="ui header" style="text-align:center"><?php _e("Installed Extensions","zero-bs-crm");?></h2>
          <?php
            ?>
            <table class="wp-list-table widefat fixed striped dashboard_page_woothemes-helper">
              <thead>
              <tr>
                <th><?php _e("Extension","zero-bs-crm");?></th>
                <th><?php _e("Installed Version","zero-bs-crm");?></th>
              </tr>
              </thead>
              <tbody>
            <?php
            foreach($zbs_all as $zbs_plugin){

              if($zbs_plugin['Name'] != 'Zero BS CRM'){

                  $stripos = stripos($zbs_plugin['Name'], 'Zero Bs');
                  if($stripos !== false){
                   $zbs_plugins[$zbs_plugin['Name']] = $zbs_plugin['Version'];

                   echo "<tr><td>". $zbs_plugin['Name'] ."</td>";

                   if (isset($official_ver[$zbs_plugin['Name']])){                       
                       if(version_compare($official_ver[$zbs_plugin['Name']], $zbs_plugin['Version']) < 1){
                          echo "<td class='green'>" . $zbs_plugin['Version'] . "</td>";
                       }else{
                        echo "<td class='black'>" . $zbs_plugin['Version'] . "<span class='red'> - version ".$official_ver[$zbs_plugin['Name']]." available</span></td>";
                          $zbs_update_avail = true;

                          //then in here should be the notification adding system....
                          $parameters = __("Please update ", "zero-bs-crm") . $zbs_plugin['Name'] . __(" from version ", "zero-bs-crm") . $zbs_plugin['Version'] . __(" to version ", "zero-bs-crm") .  $official_ver[$zbs_plugin['Name']];

                          $ref = $zbs_plugin['Version'] . $official_ver[$zbs_plugin['Name']];
                          $reference = str_replace(".", "",  $ref);
                          $cid = get_current_user_id();
                          zeroBSCRM_notifyme_insert_notification($cid, -999, -1, 'custom.extension.update.needed', $parameters, $reference);

                       }

                   } else {

                      // new extensions not yet released / added to json
                    echo '<td class="black">' . __("Not Yet Released","zero-bs-crm") . '</td>';

                   }
                 }

              } // / is not zb

            } // / foreach plugin
          ?>
          </tbody>
          </table>

          <?php } else {

              // no (paid) extensions installed, upsell!
              ?>
              <h2 class="ui header" style="text-align:center"><?php _e("Installed Extensions","zero-bs-crm");?></h2>
              <div class="ui stacked segment inverted zbs-no-premium">
                <h4 class="ui header"><?php _e("No Premium Extensions Installed","zero-bs-crm");?>!</h4>
                <p><?php _e("You don't have any premium Extensions installed, let's fix that","zero-bs-crm");?>.</p>
                <p><a href="https://zerobscrm.com/extensions/?utm_content=inplugin-licence-gopro" class="ui button primary large"><?php _e("Buy Now","zero-bs-crm");?> <i class="star icon"></i></a></p>
              </div>
              <?php

          } ?>
      </div>

    </div>

    <?php
    if($zbs_update_avail){
      //for the message that an extension update is available...
      update_option('zbs_update_avail', $zbs_update_avail);
    }else{
      update_option('zbs_update_avail', $zbs_update_avail);  //still needs to set false..
    }
} 


#} New Home page
function zeroBSCRM_pages_dash(){
  
  global  $zbs,$zeroBSCRM_paypal_slugs;  //paypal extension slugs... ?>


<div class='zbs-dash-header'>
  <?php ##WLREMOVE ?>
  <div class="ui message compact" style="
    max-width: 400px;
    float: right;
    margin-top: -25px;
    margin-right: 30px;text-align:center;display:none;">
    <div class="header">
    </div>
  </div>
  <?php ##/WLREMOVE ?>


</div>

<?php
    // Customisable panels..  (on/off list of dashboard settings)
    // STORE in user meta things like this (so it can be different per user ?!?)
?>
<style>
.dashboard-customiser{
    width: 40px;
    height: 40px;
    position: absolute;
    top: -31px;
    right: 23px;
    background: white;
    border-radius: 4px;
    text-align: center;
    padding: 8px;
    font-size: 20px;
    cursor: pointer;
    z-index:2;
}
.dashboard-custom-choices{

    background-color: #fff;
    border-radius: 6px;
    position: relative;
    -webkit-box-shadow: 0 2px 5px 0 rgba(51,51,79,.07);
    box-shadow: 0 2px 5px 0 rgba(51,51,79,.07);
    color: black;
    position: absolute;
    top: 18px;
    right: 20px;
    border: 1px solid #e6e6ff;
    z-index: 10;
    max-height:600px;
    overflow-x: scroll;
    min-width:250px;
    display:none;
}
.dashboard-custom-choices ul{
  padding-top:10px;
}
.dashboard-custom-choices ul li{
      padding: 10px;
}
.dashboard-custom-choices label{
    display: inline-block;
    margin-bottom: 5px;
    font-weight: 700;
    color: #3f4347;
}
.dashboard-custom-choices ul li:hover{
  background-color: #f1f1f1;
}
.dashboard-custom-choices input:focus{
  outline:none;
}
.dashboard-custom-choices input{
  margin-right:15px;
}
body ::-webkit-scrollbar-track{
  background:white;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function(){

    jQuery('.dashboard-customiser').on("click",function(e){
        jQuery('.dashboard-custom-choices').toggle();
    });

    jQuery('.dashboard-custom-choices input').on("click", function(e){
        var zbs_dash_setting_id = jQuery(this).attr('id');
        jQuery('#' + zbs_dash_setting_id + '_display').toggle();

        var is_checked = -1; if (jQuery('#' + zbs_dash_setting_id).is(":checked")) is_checked = 1;
        var the_setting = zbs_dash_setting_id;
        var security = jQuery('#zbs_dash_setting_security').val();

         var data = {
                'action': 'zbs_dash_setting',
                'is_checked': is_checked,
                'the_setting': the_setting,
                'security': security
              };

              //console.log(data);

              // Send it Pat :D
              jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                "data": data,
                dataType: 'json',
                timeout: 20000,
                success: function(response) {

                  //console.log('settings updated');

                },
                error: function(response){

                  //console.log('settings failed to save');

                }

              });
       


        


    });

});
</script>

<?php
      $cid = get_current_user_id();
      $settings_dashboard_total_contacts      = get_user_meta($cid, 'settings_dashboard_total_contacts' ,true);
      $settings_dashboard_total_leads         = get_user_meta($cid, 'settings_dashboard_total_leads' ,true);
      $settings_dashboard_total_customers     = get_user_meta($cid, 'settings_dashboard_total_customers' ,true);
      $settings_dashboard_total_transactions  = get_user_meta($cid, 'settings_dashboard_total_transactions' ,true);
      $settings_dashboard_sales_funnel        = get_user_meta($cid, 'settings_dashboard_sales_funnel' ,true);
      $settings_dashboard_revenue_chart       = get_user_meta($cid, 'settings_dashboard_revenue_chart' ,true);
      $settings_dashboard_recent_activity     = get_user_meta($cid, 'settings_dashboard_recent_activity' ,true);
      $settings_dashboard_latest_contacts     = get_user_meta($cid, 'settings_dashboard_latest_contacts' ,true);

      if($settings_dashboard_total_contacts == ''){
          $settings_dashboard_total_contacts = 'true';
      }
      if($settings_dashboard_total_leads == ''){
          $settings_dashboard_total_leads = 'true';
      }
      if($settings_dashboard_total_customers == ''){
          $settings_dashboard_total_customers = 'true';
      }
      if($settings_dashboard_total_transactions == ''){
          $settings_dashboard_total_transactions = 'true';
      }
      if($settings_dashboard_sales_funnel == ''){
          $settings_dashboard_sales_funnel = 'true';
      }
      if($settings_dashboard_revenue_chart == ''){
          $settings_dashboard_revenue_chart = 'true';
      }
      if($settings_dashboard_recent_activity== ''){
          $settings_dashboard_recent_activity = 'true';
      }
      if($settings_dashboard_latest_contacts == ''){
          $settings_dashboard_latest_contacts = 'true';
      }

?>



<style>


</style>

<?php wp_nonce_field( 'zbs_dash_setting', 'zbs_dash_setting_security' ); ?>

<div class='dashboard-customiser'>
    <i class="icon sliders horizontal"></i>
</div>
<div class='dashboard-custom-choices'>
  <ul class="ui form">
  <li class="inline field">
    <label>
      <input class="ui checkbox" type="checkbox" name="settings_dashboard_total_contacts" id="settings_dashboard_total_contacts" <?php if($settings_dashboard_total_contacts == 'true'){ echo 'checked'; } ?>>
        <?php _e("Total Contacts","zero-bs-crm"); ?>
    </label>
  </li>

  <li class="inline field"><label>
      <input class="ui checkbox" type="checkbox" name="settings_dashboard_total_leads" id="settings_dashboard_total_leads" <?php if($settings_dashboard_total_leads == 'true'){ echo 'checked'; } ?>>
        <?php _e("Total Leads","zero-bs-crm"); ?>
  </label></li>

  <li class="item"><label>
      <input type="checkbox" name="settings_dashboard_total_customers" id="settings_dashboard_total_customers" <?php if($settings_dashboard_total_customers == 'true'){ echo 'checked'; } ?>>
        <?php _e("Total Customers","zero-bs-crm"); ?>
  </label></li>

  <li class="item"><label>
      <input type="checkbox" name="settings_dashboard_total_transactions" id="settings_dashboard_total_transactions" <?php if($settings_dashboard_total_transactions == 'true'){ echo 'checked'; } ?>>
        <?php _e("Total Transactions","zero-bs-crm"); ?>
  </label></li>

  <?php 
    //this is to put a control AFTER row 1. i.e. the TOTALS
    do_action('zbs_dashboard_customiser_after_row_1'); 
  ?>


  <li class="item" id="settings_dashboard_sales_funnel_list">
    <label>
      <input type="checkbox" name="settings_dashboard_sales_funnel" id="settings_dashboard_sales_funnel" <?php if($settings_dashboard_sales_funnel == 'true'){ echo 'checked'; } ?>>
        <?php _e("Sales Funnel","zero-bs-crm"); ?>
  </label></li>

  <li class="item"><label>
      <input type="checkbox" name="settings_dashboard_revenue_chart" id="settings_dashboard_revenue_chart" <?php if($settings_dashboard_revenue_chart == 'true'){ echo 'checked'; } ?>>
        <?php _e("Revenue Chart","zero-bs-crm"); ?>
  </label></li>


  <li class="item"><label>
      <input type="checkbox" name="settings_dashboard_recent_activity" id="settings_dashboard_recent_activity" <?php if($settings_dashboard_recent_activity == 'true'){ echo 'checked'; } ?>>
        <?php _e("Recent Activity","zero-bs-crm"); ?>
  </label></li>

  <li class="item"><label>
      <input type="checkbox" name="settings_dashboard_latest_contacts" id="settings_dashboard_latest_contacts" <?php if($settings_dashboard_latest_contacts == 'true'){ echo 'checked'; } ?>>
        <?php _e("Latest Contacts","zero-bs-crm"); ?>
  </label></li>

  <?php do_action('zerobscrm_dashboard_setting'); ?>

</ul>

</div>



<div class='row' style="margin-top:-60px;margin-left: -10px;margin-right: 10px;">
        <?php
          $all_count = zeroBS_customerCount();
          if($all_count > 0){
        ?>

  <div class="col-sm-6 col-lg-3" id="settings_dashboard_total_contacts_display" <?php if($settings_dashboard_total_contacts == 'true'){ echo "style='display:block;'";}else{ echo "style='display:none;'";} ?>  >
    <div class="panel text-center">
      <div class="panel-heading">
        <h4 class="panel-title text-muted font-light"><?php _e("Total Contacts","zero-bs-crm");?></h4>
      </div>
      <div class="panel-body p-t-10">
        <h2 class="zbs-h2"><i class="mdi mdi-arrow-down-bold-circle-outline text-danger m-r-10"></i><b><?php echo $all_count;?></b></h2>
      </div>
    </div>
  </div>

    <?php
  }
        $status1 = zeroBS_customerCountByStatus('lead'); 
        if($status1 > 0){
    ?>

  <div class="col-sm-6 col-lg-3" id="settings_dashboard_total_leads_display" <?php if($settings_dashboard_total_leads == 'true'){ echo "style='display:block;'";}else{ echo "style='display:none;'";} ?>  >
    <div class="panel text-center">
      <div class="panel-heading">
        <h4 class="panel-title text-muted font-light"><?php _e("Total Leads","zero-bs-crm");?></h4>
      </div>
      <div class="panel-body p-t-10">
        <h2 class="zbs-h2"><i class="mdi mdi-arrow-down-bold-circle-outline text-danger m-r-10"></i><b><?php echo $status1; ?></b></h2>
      </div>
    </div>
  </div>

  <?php
  }

      $status2 = zeroBS_customerCountByStatus('customer') + zeroBS_customerCountByStatus('upsell') + zeroBS_customerCountByStatus('postsale');
    if($status2 > 0){   
  ?>
  <div class="col-sm-6 col-lg-3" id="settings_dashboard_total_customers_display" <?php if($settings_dashboard_total_customers == 'true'){ echo "style='display:block;'";}else{ echo "style='display:none;'";} ?>  >
    <div class="panel text-center">
      <div class="panel-heading">
        <h4 class="panel-title text-muted font-light"><?php _e("Total Customers","zero-bs-crm");?></h4>
      </div>
      <div class="panel-body p-t-10">
        <h2 class="zbs-h2"><i class="mdi mdi-arrow-down-bold-circle-outline text-danger m-r-10"></i><b><?php echo $status2;?></b></h2>
      </div>
    </div>
  </div>

  <?php
  }


    $trans_count = zeroBS_tranCount();
    if($trans_count > 0){ 
  ?>
  <div class="col-sm-6 col-lg-3" id="settings_dashboard_total_transactions_display" <?php if($settings_dashboard_total_transactions == 'true'){ echo "style='display:block;'";}else{ echo "style='display:none;'";} ?>  >
    <div class="panel text-center">
      <div class="panel-heading">
        <h4 class="panel-title text-muted font-light"><?php _e("Total Transactions","zero-bs-crm");?></h4>
      </div>
      <div class="panel-body p-t-10">
        <h2 class="zbs-h2"><i class="mdi mdi-arrow-down-bold-circle-outline text-danger m-r-10"></i><b><?php echo $trans_count; ?></b></h2>
      </div>
    </div>
  </div>
  <?php } ?>

</div>

<div style="clear:both"></div>

<?php

//mike function to correct crm.zbscrm.com data...
// using WPDB for now


//get the funnel 


$zbsFunnelStr = zeroBSCRM_getSetting('zbsfunnel');

#} Defaults:
$zbsFunnelArr = array(); $zbsFunnelArrN = array();

#} Unpack.. if present
if (!empty($zbsFunnelStr)){

  if (strpos($zbsFunnelStr, ',') > -1) {

    // csv 
    $zbsFunnelArrN = explode(',',$zbsFunnelStr);
    $zbsFunnelArr = array_reverse($zbsFunnelArrN);

  } else {

    // single str 
    $zbsFunnelArr = array($zbsFunnelStr);
    $zbsFunnelArrN = array($zbsFunnelStr);

  }
}





$i = 0;
$tot = 0;
$n = count($zbsFunnelArr); 
// wh added these to stop php notices? 
$func = array(); $func = array();
foreach($zbsFunnelArr as $Funnel){
    //hack for demo site
    $fun[$i] = zeroBS_customerCountByStatus($Funnel);
    $func[$i] = $fun[$i] + $tot;
    $tot = $func[$i];
    $i++;
}

$values = array_reverse($func);

// WH note: added second set of SAME colours here - as was PHP NOTICE for users with more than 6 in setting below
$colors = array("#00a0d2", "#0073aa", "#035d88", "#333", "#222", "#000","#00a0d2", "#0073aa", "#035d88", "#333", "#222", "#000");
$colorsR = array_reverse($colors);

$i=0;
$data = '';
$n = count($zbsFunnelArr) -1;

// WH added - to stop 0 0 funnels
$someDataInData = false;

for($j = $n; $j >= 0;  $j--){

  $val = (int)$func[$j];

  if ($val > 0) $someDataInData = true;

  $data .= '{';
      $data .= "value: ".$val .",";
      $data .= "color: '".$colors[$j] ."',";
      $data .= "labelstr: '". $func[$j] . "'";
  $data .= '},';   
}

?>

<?php

/* Transactions - Revenue Chart data gen */

  #} Default
  $labels = array();

  $labels[0] = "'". date('F Y') . "'";
  $labelsa[0] = date('F Y');


for ($i = 0; $i < 5; $i++) {
  $date = date("F Y", mktime(0, 0, 0, date("m")-$i, 1, date("Y")));
  $labels[$i] = "'" . $date . "'";
  $labelsa[$i] = $date;
}

$labels = implode(",",array_reverse($labels));

$recentTransactions = zeroBS_getTransactionsRange('5', 'month');

$i=0;
$date_group = date('F Y');
$group_tot = 0;
$newdata = array();

	$tot = 0; $includeAll = false;
  $status_to_include = zeroBSCRM_getSetting('transinclude_status');	

	if (!is_array($status_to_include) && $status_to_include === 'all'){
		
		$includeAll = true;
		$search_array = array();

	} else {

		if (!is_array($status_to_include)) $status_to_include = array();
		$search_array 		= array_map('strtolower', $status_to_include);
	}

  if (isset($recentTransactions) && is_array($recentTransactions) && count($recentTransactions) > 0){ 
    foreach($recentTransactions as $transaction){

      // <3.0 
      if (!is_array($transaction) && isset($transaction->ID)){

          $trans_meta = get_post_meta($transaction->ID,'zbs_transaction_meta', true);

          // WH added check 6/2/19 - somehow some were being passed without meta?
          $this_trans_status = ''; if (is_array($trans_meta) && isset($trans_meta['status'])) $this_trans_status = strtolower(str_replace(' ','_',str_replace(':','_',$trans_meta['status'])));
      
          if ($includeAll || in_array($this_trans_status, $search_array)){
            $date_group_temp = date('F Y', strtotime($transaction->post_date));
            if($date_group != $date_group_temp){
              $i++;
              $date_group = $date_group_temp;
              $group_tot = 0;
            }
            if (is_array($trans_meta) && isset($trans_meta['total'])){
              $group_tot = $group_tot + (float)$trans_meta['total'];
            }
            $newdata[$date_group] = $group_tot;
          }

      } // / if set

      // v3.0 
      if (is_array($transaction) && isset($transaction['id'])){

          $this_trans_status = ''; if (isset($transaction['status'])) $this_trans_status = strtolower(str_replace(' ','_',str_replace(':','_',$transaction['status'])));
      
          if ($includeAll || in_array($this_trans_status, $search_array)){
            $date_group_temp = date('F Y', $transaction['date']);
            if ($date_group != $date_group_temp){
              $i++;
              $date_group = $date_group_temp;
              $group_tot = 0;
            }
            if (isset($transaction['total'])){
              $group_tot = $group_tot + (float)$transaction['total'];
            }
            $newdata[$date_group] = $group_tot;
          }

      }

    }
  } else {

    // wh added - what if no trans? ms - it degrades peacefully :-)

  }

    $chartdata = array();
    foreach($labelsa as $label){

        if(array_key_exists($label, $newdata)){

          $chartdata[$label] = $newdata[$label];

        }else{

          $chartdata[$label] = 0;

        }

    }

    $chartdataStr = implode(",",array_reverse($chartdata));

?>


<script type="text/javascript">
jQuery(document).ready(function(){


  jQuery('.learn')
    .popup({
      inline: false,
      on:'click',
      lastResort: 'bottom right',
  });

  <?php if(strlen($data) > 0){ ?>
  var funnelData = [<?php echo $data; ?>];
  <?php }else{  ?>
  var funnelData = '';
  <?php } ?>

  if (funnelData != '') jQuery('#funnel-container').drawFunnel(funnelData, {

    // Container height, 
    // i.e. height of #funnel-container
    width: jQuery('.zbs-funnel').width() - 50, 

    // Container width, 
    // i.e. width of #funnel-container
    height: 300,  

    // Padding between segments, in pixels
    padding: 1, 

    // Render only a half funnel
    half: false,  

    // Width of a segment can't be smaller than this, in pixels
    minSegmentSize: 30,  

    // label: function () { return "Label!"; } 


    label: function (obj) {
        return obj;
    }
  });



// WH added: don't draw if not there :)
if (jQuery('#bar-chart').length){

  new Chart(document.getElementById("bar-chart"), {
      type: 'bar',
      data: {
        labels: [<?php echo $labels; ?>],
        datasets: [
          {
            label: "",
            backgroundColor: ["#00a0d2", "#0073aa","#333","#222","#000"],
            data: [<?php echo $chartdataStr; ?>]
          }
        ]
      },
      options: {
        legend: { display: false },
        title: {
          display: false,
          text: ''
        },

        scales: {
          yAxes: [{
              display: true,
              ticks: {
                  beginAtZero: true   // minimum value will be 0.
              }
          }]
      }


      }
  });

}


});
</script>


<?php 

  // action hook for the first part of the dashboard (i.e. after the totals, but before the funnel chart)

  do_action('zbs_dashboard_pre_dashbox_post_totals');  //allow the code to hook into the dashboard

?>


<div style="clear:both"></div>
<div class='row' style="margin:1em;margin-left: 0.5em;">

  <div class="col-6 zbs-funnel"  id="settings_dashboard_sales_funnel_display" <?php if($settings_dashboard_sales_funnel == 'true'){ echo "style='display:block;'";}else{ echo "style='display:none;'";} ?>  >
    <div class='panel'>

      <div class="panel-heading" style="text-align:center">
        <h4 class="panel-title text-muted font-light"><?php _e("Sales Funnel","zero-bs-crm");?></h4>
      </div>
      <?php //echo '<h2>'.gettype($data).'</h2><pre>'; print_r($data); echo '</pre>';
      if ( // WH NOTE: MS is $data a string or array, think got lost in mix here, I'm adding this check to fix warning for Dennis: https://epicplugins.groovehq.com/groove_client/mailboxes/49227/filters/188769/tickets/78269408
          (is_array($data) && count($data) == 0)
          ||
          (is_string($data) && strlen($data) == 0)
          ||
          !$someDataInData
          ){ ?>
        <div class='ui message blue' style="text-align:center;margin-bottom:50px;">
            <?php _e("You do not have any contacts. Make sure you have contacts in each stage of your funnel.","zero-bs-crm");?> 
            <?php ##WLREMOVE ?><br/><br/>
            <a class="button ui blue" href="https://zerobscrm.com/kb/knowledge-base/zero-bs-crm-dashboard/"><?php _e("Read Guide","zero-bs-crm");?></a>
            <?php ##/WLREMOVE ?>
        </div>
      <?php } else { ?>
      <div id="funnel-container"></div>
      <?php } ?>

      <div class='funnel-legend' style="margin-bottom:30px;margin-top:20px;">
          <?php
            $i = 0;
            $zbsFunnelArrR = array_reverse($zbsFunnelArr);
            $j = count($zbsFunnelArrR);
            foreach($zbsFunnelArrR as $Funnel){
                echo '<div class="zbs-legend" style="background:'.$colors[$j - $i -1].'"></div><div class="zbs-label">  ' . $Funnel . '</span></div>';
                $i++;
            }
          ?>
      </div>

    </div>
  </div>

  <div class="col-6 zbs-funnel" id="settings_dashboard_revenue_chart_display" <?php if($settings_dashboard_revenue_chart == 'true'){ echo "style='display:block;'";}else{ echo "style='display:none;'";} ?>  >
    <div class='panel'>

      <div class="panel-heading" style="text-align:center">
      <?php  $currencyChar = zeroBSCRM_getCurrencyChr(); ?>
        <h4 class="panel-title text-muted font-light"><?php _e("Revenue Chart","zero-bs-crm");?> (<?php echo $currencyChar; ?>)</h4>
        <?php ##WLREMOVE ?><span class='upsell'><a href="https://zerobscrm.com/product/sales-dashboard/" target="_blank"><?php _e("Want More?","zero-bs-crm");?></a></span><?php ##/WLREMOVE ?>
      </div>


      <?php
      if(array_sum($newdata) == 0){ ?>
        <div class='ui message blue' style="text-align:center;margin-bottom:80px;margin-top:50px;">
            <?php _e("You do not have any transactions that match your chosen settings. You need transactions for your revenue chart to show. If you have transactions check your settings and then transaction statuses to include.","zero-bs-crm");?> 
            <?php ##WLREMOVE ?><br/><br/>
            <a class="button ui blue" href="https://zerobscrm.com/kb/knowledge-base/revenue-overview-chart/"><?php _e("Read Guide","zero-bs-crm");?></a>
            <?php ##/WLREMOVE ?>
        </div>
      <?php }else{ ?>
        <canvas id="bar-chart" width="800" height="490"></canvas>
      <?php } ?>
      
    </div>
  </div>

  <div style="clear:both"></div>


</div>


<?php
//changed this from false to 0, so we get all the logs and the functions actually get triggered..
// WH: changed for proper generic func $latestLogs = zeroBSCRM_getContactLogs(0,true,10);
$latestLogs = zeroBSCRM_getAllContactLogs(true,10);

?>

<style>
.activity-feed {
  padding: 15px;
}
.activity-feed .feed-item {
  position: relative;
  padding-bottom: 20px;
  padding-left: 30px;
  border-left: 2px solid #e4e8eb;
}
.activity-feed .feed-item:last-child {
  border-color: transparent;
}
.activity-feed .feed-item:after {
  content: "";
  display: block;
  position: absolute;
  top: 0;
  left: -6px;
  width: 10px;
  height: 10px;
  border-radius: 6px;
  background: #fff;
  border: 1px solid #f37167;
}
.activity-feed .feed-item .date {
  position: relative;
  top: -5px;
  color: #8c96a3;
  text-transform: uppercase;
  font-size: 13px;
}
.activity-feed .feed-item .text {
  position: relative;
  top: -3px;
}
</style>

<div class='row' style="margin:1em">
<div class="col-md-4-db" id="settings_dashboard_recent_activity_display" <?php if($settings_dashboard_recent_activity == 'true'){ echo "style='display:block;'";}else{ echo "style='display:none;'";} ?>>
  <div class="panel">
      <div class="panel-heading" style="text-align:center">
        <h4 class="panel-title text-muted font-light"><?php _e("Recent Activity","zero-bs-crm");?></h4>
      </div>

      <div class="ui list activity-feed" style="padding-left:20px;margin-bottom:20px;">

      <?php  
      
      if(count($latestLogs) == 0){ ?>

        <div class='ui message blue' style="text-align:center;margin-bottom:80px;margin-top:50px;margin-right:20px;">
            <i class="icon info"></i>
            <?php _e("No recent activity.","zero-bs-crm");?> 
        </div>


      <?php } ?>

      <?php if (count($latestLogs) > 0) foreach($latestLogs as $log){

          $em     = zeroBS_customerEmail($log['owner']);
          $avatar = zeroBSCRM_getGravatarURLfromEmail($em,28);
          $unixts =  date('U', strtotime($log['created']));
          $diff   = human_time_diff($unixts, current_time('timestamp'));

        if(isset($log['type'])){ 
          $logmetatype = $log['type']; 
        }else{ 
          $logmetatype = ''; 
        }

        // WH added from contact view:
          
          global $zeroBSCRM_logTypes, $zbs;
          // DAL 2 saves type as permalinked
          if ($zbs->isDAL2()){
            if (isset($zeroBSCRM_logTypes['zerobs_customer'][$logmetatype])) $logmetatype = __($zeroBSCRM_logTypes['zerobs_customer'][$logmetatype]['label'],"zero-bs-crm");
          }


        if(isset($log['shortdesc'])){ 
          $logmetashot = $log['shortdesc'];
        }else{ 
          $logmetashot = ''; 
        }


        $logauthor = ''; 
        if (isset($log['author'])) $logauthor = ' &mdash; ' . $log['author']; 
        

        /*

  <div class="feed-item">
    <div class="date">Sep 25</div>
    <div class="text">Responded to need <a href="single-need.php">â€œVolunteer opportunityâ€</a></div>
  </div>

        */


          echo "<div class='feed-item'>";
            echo "<div class='date'><img class='ui avatar img img-rounded' src='" . $avatar . "'/></div>";
            echo "<div class='content text'>";
              echo "<span class='header'>" . $logmetatype . "<span class='when'> (" . $diff . __(" ago","zero-bs-crm") . ")</span><span class='who'>".$logauthor."</span></span>";
              echo "<div class='description'>";
                echo $logmetashot;
              echo "<br/>";
              echo "</div>";

          echo "</div>";

          echo "</div>";
      } else {

          echo "<div class='feed-item'>";
            echo "<div class='content text'>";
              echo "<span class='header'>" . __('Contact Log Feed',"zero-bs-crm") . "<span class='when'> (" . __("Just now","zero-bs-crm") . ")</span></span>";
              echo "<div class='description'>";
                _e('This is where recent Contact actions will show up',"zero-bs-crm");
                echo "<br/>";
              echo "</div>";
            echo "</div>";
          echo "</div>";
      }
      ?>
      </div>
  </div>
</div>


<div class="col-md-8" id="settings_dashboard_latest_contacts_display" <?php if($settings_dashboard_latest_contacts == 'true'){ echo "style='display:block;margin: 0;padding-left: 0;'";}else{ echo "style='display:none;'";} ?>  >
  <div class="panel">
      <div class="panel-heading" style="text-align:center;position:relative">
        <h4 class="panel-title text-muted font-light"><?php _e("Latest Contacts","zero-bs-crm");?></h4>
        <span class='upsell'><a href="<?php echo zbsLink($zbs->slugs['managecontacts']); ?>"><?php _e("View All","zero-bs-crm");?></a></span>
      </div>


    <?php
      $latest_cust = zeroBS_getCustomers(true,10,0);
    ?>

      <?php  if(count($latest_cust) == 0){ ?>

        <div class='ui message blue' style="text-align:center;margin-bottom:80px;margin-top:50px;margin-right:20px;margin-left:20px;">
            <i class="icon info"></i>
            <?php _e("No contacts.","zero-bs-crm");?> 
        </div>


      <?php }else{ ?>


    <div class="panel-body">
      <div class="row">
        <div class="col-xs-12">
          <div class="table-responsive">
            <table class="table table-hover m-b-0">
              <thead>
                <tr>
                  <th><?php _e("ID","zero-bs-crm");?></th>
                  <th><?php _e("Avatar","zero-bs-crm");?></th>
                  <th><?php _e("First Name","zero-bs-crm");?></th>
                  <th><?php _e("Last Name","zero-bs-crm");?></th>
                  <th><?php _e("Status","zero-bs-crm");?></th>
                  <th><?php _e("View","zero-bs-crm");?></th>
                  <th style="text-align:right;"><?php _e("Added","zero-bs-crm");?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <?php
                    foreach($latest_cust as $cust){
                      $avatar = ''; if (isset($cust) && isset($cust['email'])) $avatar = zeroBSCRM_getGravatarURLfromEmail($cust['email'],25);
                      $fname = ''; if (isset($cust) && isset($cust['fname'])) $fname = $cust['fname'];
                      $lname = ''; if (isset($cust) && isset($cust['lname'])) $lname = $cust['lname'];
                      $status = ''; if (isset($cust) && isset($cust['status'])) $status = $cust['status'];
                      if (empty($status)) $status = __('None',"zero-bs-crm");
                      echo "<tr>";
                        echo "<td>" . $cust['id'] . "</td>";
                        echo "<td><img class='img-rounded' src='" . $avatar . "'/></td>";
                        echo "<td><div class='mar'>" . $fname . "</div></td>";
                        echo "<td><div class='mar'>" . $lname . "</div></td>";
                        echo "<td class='zbs-s zbs-".strtolower($status) ."'><div>" . $status . "</div></td>";
                      
                        echo "<td><div class='mar'><a href='" . zbsLink('view',$cust['id'],'zerobs_customer') . "'>";
                        _e('View',"zero-bs-crm");
                      echo "</a></div></td>";

                      echo "<td style='text-align:right;' class='zbs-datemoment-since' data-zbs-created-uts='" . $cust['createduts'] . "'>" . $cust['created'] . "</td>";

                      echo "</tr>";
                    }
                  ?>
                </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <?php } ?>


    </div>
</div>




</div>





  <?php

}


function zerobscrm_show_love($url='', $text='Zero BS - The WordPress CRM'){
  #} Quick function to 'show some love'.. called from PayPal Sync and other locale.
  ?>
  <style>
   ul.share-buttons{
    list-style: none;
    padding: 0;
    text-align: center;
  }
  ul.share-buttons li{
    display: inline-block;
    margin-left:4px;
  }
  .logo-wrapper{
    padding:20px;
  }
  .logo-wrapper img{
    width:200px;
  }
  </style>

  <?php $text = htmlentities($text); ?>

  <p style="font-size:16px;text-align:center"><?php _e('Zero BS CRM is the ultimate CRM tool for WordPress.<br/ >Help us get the word out and show some love... You know what to do...',"zero-bs-crm"); ?></p>
  <ul class="share-buttons">
  <li><a href="https://www.facebook.com/sharer/sharer.php?u=http%3A%2F%2Fzerobscrm.com&t=<?php echo $text;?>" target="_blank"
  ><img src="<?php echo ZEROBSCRM_URL.'i/Facebook.png'; ?>"></a></li>
  <li><a href="https://twitter.com/intent/tweet?source=http%3A%2F%2Fzerobscrm.com&text=<?php echo $text;?>%20http%3A%2F%2Fzerobscrm.com&via=zerobscrm" target="_blank" title="Tweet"><img src="<?php echo ZEROBSCRM_URL.'i/Twitter.png'; ?>"></a></li>
  <li><a href="https://plus.google.com/share?url=http%3A%2F%2Fzerobscrm.com" target="_blank" title="Share on Google+" onclick="window.open('https://plus.google.com/share?url=' + encodeURIComponent(<?php echo $url; ?>)); return false;"><img src="<?php echo ZEROBSCRM_URL.'i/Google+.png'; ?>"></a></li>
  <li><a href="http://www.tumblr.com/share?v=3&u=http%3A%2F%2Fzerobscrm.com&t=<?php echo $text;?>&s=" target="_blank" title="Post to Tumblr"><img src="<?php echo ZEROBSCRM_URL.'i/Tumblr.png'; ?>"></a></li>
  <li><a href="http://pinterest.com/pin/create/button/?url=http%3A%2F%2Fzerobscrm.com&description=<?php echo $text;?>" target="_blank" title="Pin it"><img src="<?php echo ZEROBSCRM_URL.'i/Pinterest.png'; ?>"></a></li>
  <li><a href="https://getpocket.com/save?url=http%3A%2F%2Fzerobscrm.com&title=<?php echo $text;?>" target="_blank" title="Add to Pocket"><img src="<?php echo ZEROBSCRM_URL.'i/Pocket.png'; ?>"></a></li>
  <li><a href="http://www.reddit.com/submit?url=http%3A%2F%2Fzerobscrm.com&title=<?php echo $text;?>" target="_blank" title="Submit to Reddit"><img src="<?php echo ZEROBSCRM_URL.'i/Reddit.png'; ?>"></a></li>
  <li><a href="http://www.linkedin.com/shareArticle?mini=true&url=http%3A%2F%2Fzerobscrm.com&title=<?php echo $text;?>&summary=&source=http%3A%2F%2Fzerobscrm.com" target="_blank" title="Share on LinkedIn"><img src="<?php echo ZEROBSCRM_URL.'i/LinkedIn.png'; ?>"></a></li>
  <li><a href="http://wordpress.com/press-this.php?u=http%3A%2F%2Fzerobscrm.com&t=<?php echo $text;?>&s=" target="_blank" title="Publish on WordPress"><img src="<?php echo ZEROBSCRM_URL.'i/Wordpress.png'; ?>"></a></li>
  <li><a href="https://pinboard.in/popup_login/?url=http%3A%2F%2Fzerobscrm.com&title=<?php echo $text;?>&description=" target="_blank" title="Save to Pinboard" <img src="<?php echo ZEROBSCRM_URL.'i/Pinboard.png'; ?>"></a></li>
  <li><a href="mailto:?subject=&body=<?php echo $text;?>:%20http%3A%2F%2Fzerobscrm.com" target="_blank" title="Email"><img src="<?php echo ZEROBSCRM_URL.'i/Email.png'; ?>"></a></li>
</ul>

  <?php
}


#} Main Config page
function zeroBSCRM_pages_home() {
  
  global $wpdb, $zbs; #} Req
  
  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Homepage 
  if (!zeroBSCRM_isWL()){ 
    // Everyday homepage
    zeroBSCRM_html_home2();
  } else {
    // WL Home
    zeroBSCRM_html_wlHome();
  }

  #} Footer
  zeroBSCRM_pages_footer();

?>
</div>
<?php 
}
 

#} Feedback page
function zeroBSCRM_pages_feedback() {
  
  global $wpdb, $zbs; #} Req
  
  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
    zeroBSCRM_pages_header('Send Us Feedback');

  #} page 
  zeroBSCRM_html_feedback(); 

  #} Footer
  zeroBSCRM_pages_footer();

?>
</div>
<?php 
}

#} Extensions page
function zeroBSCRM_pages_extensions() {
  
  global $wpdb, $zbs; #} Req
  
  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
    zeroBSCRM_pages_header('Extensions');

  #} page 
  zeroBSCRM_html_extensions(); 

  #} Footer
  zeroBSCRM_pages_footer();

?>
</div>
<?php 
}




#} Tabs func for settings page
function zeroBSCRM_html_settings_menu( $current = 'homepage' ) { 

  global $zbs;
  
  #} Default
  $tabs = array( 'settings' => 'General');
  $tabsNew = array(); // slugs included in here will get a new flag 

  #} Get Settings
  $settings = $zbs->settings->getAll();

  #} Add hard-typed:
  $tabs['bizinfo'] = __("Business Info","zero-bs-crm");
  $tabs['customfields'] = __("Custom Fields","zero-bs-crm");
  $tabs['fieldsorts'] = __("Field Sorts","zero-bs-crm");
  $tabs['fieldoptions'] = __("Field Options","zero-bs-crm");
  $tabs['listview'] = __("List View","zero-bs-crm");
  $tabs['tax'] = __("Tax","zero-bs-crm");
  $tabs['license'] = __('CRM License','zero-bs-crm');
    
    #} Load them from proper list :)
    global $zeroBSCRM_extensionsInstalledList;


    #} This will cycle through "installed" extensions and display them as tabs, using their custom funcs to get names, and falling back to a capitalised version of their perma
    if (isset($zeroBSCRM_extensionsInstalledList) && is_array($zeroBSCRM_extensionsInstalledList)) foreach ($zeroBSCRM_extensionsInstalledList as $installedExt){

      #} Ignore pages for a min
      global $zbsExtensionsExcludeFromSettings; #} IN ZeroBsCRM.Core.Extensions.php


      if (!in_array($installedExt, $zbsExtensionsExcludeFromSettings)){

        #} Got name func?
        if (function_exists('zeroBSCRM_extension_name_'.$installedExt)){

        #} Fire it to generate name :)
        $extNameFunc = 'zeroBSCRM_extension_name_'.$installedExt;

          // additional check, that there's actually a settings func to run :)
          if (function_exists('zeroBSCRM_extensionhtml_settings_'.$installedExt)){

              $tabs[$installedExt] = call_user_func($extNameFunc);

          }

        } else {

          #} Fallback to capitalised ver of perm
          // Don't even show, as of 10/1/19
          // if func doesn't exist, screw it
          // ... came ultimately to check for the page setting:
          if (function_exists('zeroBSCRM_extensionhtml_settings_'.$installedExt)){
            
            $tabs[$installedExt] = ucwords($installedExt);

          }

        }

      }

    }

  
    #} Optional:
    if ($settings['feat_transactions'] == 1) $tabs['transactions'] = __("Transactions","zero-bs-crm");
    if ($settings['feat_forms'] == 1) $tabs['forms'] = __("Forms","zero-bs-crm");
    if ($settings['feat_portal'] == 1) $tabs['clients'] = __("Client Portal","zero-bs-crm");
    if ($settings['feat_api'] == 1) $tabs['api'] = "API";

    #} Base hard-typed:
    $tabs['mail'] = __('Mail','zero-bs-crm');
    $tabs['maildelivery'] = __('Mail Delivery',"zero-bs-crm");
    $tabs['mailtemplates'] = __('Mail Templates',"zero-bs-crm");
      
    // make these filterable for the extensions..
    $tabs = apply_filters('zbs_settings_tabs', $tabs); ?>

     <div class="ui grid zbs-page-wrap" style="margin-top:0em"><?php

  // ========= V3 Migration Interaction ========================
     
  $v3InProgress = get_option('zbs_db_migration_300_inprog',false);
  if ($v3InProgress){

      // Block settings page changes.
      ?><div id="zbs-migration-blocker"></div><div id="zbs-migration-settings-notice">
        <?php 
        $msg = __('There is currently a CRM Migration in progress, until that migration has finished you will not be able to change any settings, as these may intefere with a safe migration. These will be back up shortly.','zero-bs-crm');
        echo zeroBSCRM_UI2_messageHTML('warning',__('Migration in Progress','zero-bs-crm'),$msg,'hourglass half','zbs-migration-settings-msg'); 
        ?>
      </div><?php
    
  }

  // ========= / V3 Migration Interaction ========================

    $links = array();

    #} passing "branding" for the logo top :-) 
    $branding = '';
    ##WLREMOVE
    $branding = 'zero-bs-crm';
    ##/WLREMOVE
?>

<div class="four wide column">

<div class="ui vertical fluid menu" id="zbs-settings-menu">
  <!-- Would be nice to add a cpanel style js search 
  <div class="item">
    <div class="ui input"><input type="text" placeholder="Search..."></div>
  </div> -->
  <div class="branding item" id="zbs-settings-head-tour">
    <?php /* if($branding == 'zero-bs-crm'){ ?>
          <img src="<?php echo ZEROBSCRM_URL ?>i/zero-bs-crm-admin-logo-clear.png">
        <?php } else { ?>
          <i class='fa fa-bars menu-open' style="height:30px;width:30px;font-size:30px;"></i>
        <?php } */
        ?><img src="<?php echo zeroBSCRM_getLogoURL(false); ?>" alt="" /><?php
        

        _e('Settings',"zero-bs-crm"); ?>
  </div>
  <?php 

  // QUICK hacky rewrite to add submenu under general/mail, needs genericifying so can use submenus throughout

  $sortedTabs = array(); 
  $underGeneral = array('customfields','fieldsorts','fieldoptions','listview','bizinfo','tax','license');
  $underMail = array('maildelivery','mailtemplates','mailcampaigns');

  #}WH this shows $tabs has ones which we excluded above. Possibly due to load order timings?
  //zbs_write_log($tabs);
  global $zbsExtensionsExcludeFromSettings;

  $tabs = apply_filters('zbs_settings_tabs', $tabs);

  foreach ($tabs as $tab => $name){

    //double check as the above zbs_write_log of $tabs outputs 
    /*
    [23-Aug-2018 23:43:05 UTC] Array
    (
        [settings] => General
        [bizinfo] => Business Info
        [customfields] => Custom Fields
        [transactions] => Transactions
        [fieldsorts] => Field Sorts
        [listview] => List View
        [forms] => Front-end Forms
        [clients] => Client Portal
        [api] => API
        [quotebuilder] => Quote Builder
        [invbuilder] => Invoice Builder
        [systememailspro] => System Emails Pro
        [mail] => Mail
        [maildelivery] => Mail Delivery
        [mailtemplates] => Mail Templates
        [bulktag] => Bulk Tagger
    )
    */

    if(in_array($tab,$zbsExtensionsExcludeFromSettings)){
       #}WH check here. Not sure how $tab has ones which have been told to be excluded?  
      //zbs_write_log("OI. I AM IN THE EXCLUDE ME. SO HOW HAVE I MADE IT HERE?");
        continue;
    }

    if (!isset($sortedTabs[$tab])) $sortedTabs[$tab] = array();
    $sortedTabs[$tab]['name'] = $name;
    $sortedTabs[$tab]['ico'] = ''; // nothing yet

    if (in_array($tab,$underGeneral)) {
      if (!isset($sortedTabs['settings'])) $sortedTabs['settings'] = array();
      if (!isset($sortedTabs['settings']['submenu'])) $sortedTabs['settings']['submenu'] = array();
      $sortedTabs['settings']['submenu'][$tab] = array(
        'name' => $name,
        'ico' => ''
        );

      // unset this - hacky
      unset($sortedTabs[$tab]);
    }

    if (in_array($tab,$underMail)) {
      if (!isset($sortedTabs['mail'])) $sortedTabs['mail'] = array();
      if (!isset($sortedTabs['mail']['submenu'])) $sortedTabs['mail']['submenu'] = array();
      $sortedTabs['mail']['submenu'][$tab] = array(
        'name' => $name,
        'ico' => ''
        );

      // unset this - hacky
      unset($sortedTabs[$tab]);
    }


  }

  foreach( $sortedTabs as $tab => $tabArr ){

        // could/should expand this to have icons + submenus 
        // as per example under "sub menu" here: https://semantic-ui.com/collections/menu.html
        $ico = $tabArr['ico'];
        $name = $tabArr['name'];

        if (isset($tabArr['submenu'])){

          // has submenus
          echo '<div class="item">';

          $class = ( $tab == $current ) ? ' active' : '';
          echo "<a class='item zbs-settings-head $class' href='?page=".$zbs->slugs['settings']."&tab=$tab'>".$ico."$name</a>";

          echo '        <div class="menu">';

                  foreach ($tabArr['submenu'] as $tab2 => $tabArr2){

                      $ico2 = $tabArr2['ico'];
                      $name2 = $tabArr2['name'];
                      $new = ''; if (in_array($tab2, $tabsNew)) $new = '<span class="ui label green tiny">New</span>';
                      $url = admin_url('admin.php?page='.$zbs->slugs['settings'].'&tab='.$tab2);

                      // temporary hard typed exception
                      if ($tab2 == 'mailtemplates') $url = admin_url('admin.php?page='.$zbs->slugs['email-templates']);

                      $class = ( $tab2 == $current ) ? ' active' : '';
                      echo '<a class="item $class" href="'.$url.'">'.$new.$ico2."$name2</a>";

                  }

                  // This is a good idea, but we should generically rebuild this whole thing
                  // it's not URGENT THO.. replaced this with the above method for now. do_action('zbs-general-sub-menu-additional-links');


            echo '</div>
                </div>';


        } else {

          // simple

          $class = ( $tab == $current ) ? ' active' : '';
          echo "<a class='item $class' href='?page=".$zbs->slugs['settings']."&tab=$tab'>".$ico."$name</a>";

        }

        
    } 

    // ONE more Upsell op
    echo '<a class="item" href="'.zbsLink($zbs->slugs['extensions']).'"><i class="ui orange puzzle piece icon"></i> '.__('Extensions','zero-bs-crm').'</a>';

    ?>
</div></div><div class="twelve wide stretched column" style="padding-left:0;">
    <div class="ui segment"><?php

/*

https://semantic-ui.com/collections/menu.html

<div class="ui vertical menu">
  <div class="item">
    <div class="ui input"><input type="text" placeholder="Search..."></div>
  </div>
  <div class="item">
    Home
    <div class="menu">
      <a class="active item">Search</a>
      <a class="item">Add</a>
      <a class="item">Remove</a>
    </div>
  </div>
  <a class="item">
    <i class="grid layout icon"></i> Browse
  </a>
  <a class="item">
    Messages
  </a>
  <div class="ui dropdown item">
    More
    <i class="dropdown icon"></i>
    <div class="menu">
      <a class="item"><i class="edit icon"></i> Edit Profile</a>
      <a class="item"><i class="globe icon"></i> Choose Language</a>
      <a class="item"><i class="settings icon"></i> Account Settings</a>
    </div>
  </div>
</div>


*/


/* old method 
   echo '<div class="ui left vertical menu sidebar">';
    foreach( $tabs as $tab => $name ){
        $class = ( $tab == $current ) ? ' active' : '';
        echo "<a class='item $class' href='?page=".$zbs->slugs['settings']."&tab=$tab'>$name</a>";
        
    }
    echo '</div>';
*/

}



#} Main Config page

//need this to be extendible really if we want to complete with something like WooCommerce or EDD.

/* UI with extensions menus down the side is one option. Suggest replacting tabs with a do_action and passing
* the extensions the global menu so it adds exta tabs for each setting
* tabs is good for a single plugin but hard to pass cross to extensions - leaving tab mode in in case want to utilise in future (WHREADME i.e. move custom fields to a tab??)
* Suggest also re-naming the 'Settings' menu of the 'Core' to be 'Main settings' and then each extension has it's own
*/


// This is a good idea, but we should generically rebuild this whole thing
// it's not URGENT THO.. replaced this with the above method for now. 
/*add_action('zbs-general-sub-menu-additional-links','zeroBSCRM_general_email_templates');
function zeroBSCRM_general_email_templates(){
  global $zbs;
    
    echo "<a class='item' href='?page=".$zbs->slugs['email-templates']."'><span class='ui label green tiny'>New</span>".__("System Emails",'zero-bs-crm')."</a>";

} */

// general mail settings
function zeroBSCRM_html_settings_mail(){


  global $wpdb, $zbs;  #} Req

  $confirmAct = false;
  $settings = $zbs->settings->getAll();   

  #} Act on any edits!
  if (isset($_POST['editzbsmail']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-mail' );

    #} 2.80+
    $updatedSettings['emailtracking'] = 0; if (isset($_POST['wpzbscrm_emailtracking']) && !empty($_POST['wpzbscrm_emailtracking'])) $updatedSettings['emailtracking'] = 1;
    $updatedSettings['directmsgfrom'] = 1; if (isset($_POST['wpzbscrm_directmsgfrom']) && !empty($_POST['wpzbscrm_directmsgfrom'])) $updatedSettings['directmsgfrom'] = (int)sanitize_text_field( $_POST['wpzbscrm_directmsgfrom'] );

    #} 2.90+ 

      #} Unsub msg + page + msg
      $updatedSettings['unsub'] = ''; if (isset($_POST['wpzbs_unsub'])) $updatedSettings['unsub'] = zeroBSCRM_textProcess( $_POST['wpzbs_unsub']);
      $updatedSettings['unsubpage'] = -1; if (isset($_POST['wpzbscrm_unsubpage']) && !empty($_POST['wpzbscrm_unsubpage'])) $updatedSettings['unsubpage'] = (int)sanitize_text_field($_POST['wpzbscrm_unsubpage']);
      $updatedSettings['unsubmsg'] = ''; if (isset($_POST['wpzbs_unsubmsg'])) $updatedSettings['unsubmsg'] = zeroBSCRM_textProcess( $_POST['wpzbs_unsubmsg']);

  
    #} 2.93+ 
    $updatedSettings['emailpoweredby'] = 0; if (isset($_POST['wpzbscrm_emailpoweredby']) && !empty($_POST['wpzbscrm_emailpoweredby'])) $updatedSettings['emailpoweredby'] = 1;
   
    #} 2.95.4+ 
    $updatedSettings['mailignoresslmismatch'] = 0; if (isset($_POST['wpzbscrm_mailignoresslmismatch']) && !empty($_POST['wpzbscrm_mailignoresslmismatch'])) $updatedSettings['mailignoresslmismatch'] = 1;
   

    #} Brutal update
    foreach ($updatedSettings as $k => $v) $zbs->settings->update($k,$v);

    #} $msg out!
    $sbupdated = true;

    #} Reload
    $settings = $zbs->settings->getAll();
      
  }

  #} catch resets.
  if (isset($_GET['resetsettings']) && zeroBSCRM_isZBSAdminOrAdmin()) if ($_GET['resetsettings']==1){

    $nonceVerified = wp_verify_nonce( $_GET['_wpnonce'], 'resetclearzerobscrm' );

    if (!isset($_GET['imsure']) || !$nonceVerified){

        #} Needs to confirm!  
        $confirmAct = true;
        $actionStr        = 'resetsettings';
        $actionButtonStr    = __('Reset Settings to Defaults?',"zero-bs-crm");
        $confirmActStr      = __('Reset All Zero BS CRM Settings?',"zero-bs-crm");
        $confirmActStrShort   = __('Are you sure you want to reset these settings to the defaults?',"zero-bs-crm");
        $confirmActStrLong    = __('Once you reset these settings you cannot retrieve your previous settings.',"zero-bs-crm");

      } else {


        if ($nonceVerified){

            #} Reset
            $zbs->settings->resetToDefaults();

            #} Reload
            $settings = $zbs->settings->getAll();

            #} Msg out!
            $sbreset = true;

        }

      }

  } 


  if (!$confirmAct){

  ?>
    
        <p id="sbDesc"><?php _e('Set up your global mail settings. This, plus Mail Delivery settings, and Mail Templates, make up the backbone of the Zero BS CRM system.',"zero-bs-crm"); ?></p>
        <p id="sbDesc" style="text-align:center;margin:1em">
          <?php echo '<a href="'.zbsLink($zbs->slugs['settings']).'&tab=maildelivery'.'" class="ui button green">'.__('Mail Delivery','zero-bs-crm').'</a>'; ?>&nbsp;
          <?php echo '<a href="'.zbsLink($zbs->slugs['email-templates']).'" class="ui button green">'.__('Mail Templates','zero-bs-crm').'</a>'; ?>
        </p>

        <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
        <?php if (isset($sbreset)) if ($sbreset) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Reset',"zero-bs-crm")); echo '</div>'; } ?>
        
        <div id="sbA">
          <pre><?php // print_r($settings); ?></pre>

            <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=mail">
              <input type="hidden" name="editzbsmail" id="editzbsmail" value="1" />
              <?php 
                // add nonce
                wp_nonce_field( 'zbs-update-settings-mail');
              ?>

              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Global Mail Settings',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_emailtracking"><?php _e('Track Open Statistics',"zero-bs-crm"); ?>:</label><br /><?php _e("Include tracking pixels in all outbound system emails<br/>(e.g. Welcome to the Client Portal)","zero-bs-crm"); ?>.</td>
                          <td style="width:200px"><input type="checkbox" class="winput form-control" name="wpzbscrm_emailtracking" id="wpzbscrm_emailtracking" value="1"<?php if (isset($settings['emailtracking']) && $settings['emailtracking'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_emailpoweredby"><?php _e('Show Powered By',"zero-bs-crm"); ?>:</label><br /><?php _e("Show 'Powered by ZBS CRM' on your email footer","zero-bs-crm"); ?>.</td>
                          <td style="width:200px"><input type="checkbox" class="winput form-control" name="wpzbscrm_emailpoweredby" id="wpzbscrm_emailpoweredby" value="1"<?php if (isset($settings['emailpoweredby']) && $settings['emailpoweredby'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_mailignoresslmismatch"><?php _e('Disable SSL Verification',"zero-bs-crm"); ?>:</label><br /><?php _e("Most good servers force ssl matches for outbound mail. This is sensible, but can cause issues when using custom SMTP delivery methods. If you're having issues verifying a delivery method test enabling this setting. If your mail delivery works without this on, it's better to leave this off.","zero-bs-crm"); ?></td>
                          <td style="width:200px"><input type="checkbox" class="winput form-control" name="wpzbscrm_mailignoresslmismatch" id="wpzbscrm_mailignoresslmismatch" value="1"<?php if (isset($settings['mailignoresslmismatch']) && $settings['mailignoresslmismatch'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>
      
                      </tbody>

                  </table>
              
              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Direct Mail Settings',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_directmsgfrom"><?php _e('Format of Sender Name',"zero-bs-crm"); ?>:</label><br /><?php _e("Which format of name should be used when sending direct emails (e.g. Email Contact)","zero-bs-crm"); ?>.</td>
                          <td style="width:200px">
                            <?php $coname = zeroBSCRM_mailDelivery_defaultFromname(); ?>
                          <select class="winput" name="wpzbscrm_directmsgfrom" id="wpzbscrm_directmsgfrom">
                            <option value="1" <?php if (isset($settings['directmsgfrom']) && $settings['directmsgfrom'] == '1') echo ' selected="selected"'; ?>><?php _e('First Name Last Name @ CRM Name (e.g. John Doe @','zero-bs-crm'); echo ' '.$coname.')';?></option>
                            <option value="2" <?php if (isset($settings['directmsgfrom']) && $settings['directmsgfrom'] == '2') echo ' selected="selected"'; ?>><?php _e('CRM Name (e.g.',"zero-bs-crm"); echo ' '.$coname.')'; ?></option>
                            <option value="3" <?php if (isset($settings['directmsgfrom']) && $settings['directmsgfrom'] == '3') echo ' selected="selected"'; ?>><?php _e('Mail Delivery Method Sender Name',"zero-bs-crm");?></option>
                          </select>
                        </tr>
      
                      </tbody>

                  </table>


                    <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Unsubscribe Settings',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>


                        <tr>
                            <td class="wfieldname"><label for="wpzbs_unsub"><?php _e('Email Unsubscribe Line',"zero-bs-crm"); ?>:</label><br /><?php _e('This line will be shown in your email templates with the placeholder ##UNSUB-LINE##, we recommend you complete this where it is legal to offer contacts the ability to stop communication. We cannot be held responsible for your emails meeting your local laws. Any text here will append this to your default email templates (Mail Campaigns).',"zero-bs-crm"); ?></td>
                            <td style="width:540px"><input type="text" class="winput form-control" name="wpzbs_unsub" id="wpzbs_unsub" value="<?php if (isset($settings['businessyourname']) && !empty($settings['unsub'])) echo $settings['unsub']; ?>" placeholder="e.g. You're seeing this because you're registered as a contact of Michael Scott Paper Company, if you'd like to unsubscribe from any future communications please click ###UNSUB-LINK###." /></td>
                        </tr>
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_gcaptchasitesecret"><?php _e('Unsubscribe Page',"zero-bs-crm"); ?>:</label><br /><?php _e("Select the WordPress page with your unsubscribe shortcode (Required for Mail Campaigns).","zero-bs-crm");?>                            
                          </td>
                          <td>
                            <?php

                                // reget
                                $pageID = (int)zeroBSCRM_getSetting('unsubpage',true);

                                // catch unsub recreate
                                if (isset($_GET['recreateunsubpage']) && isset($_GET['unsubPageNonce']) && wp_verify_nonce($_GET['unsubPageNonce'], 'recreate-unsub-page')) {

                                    // recreate 
                                    $pageID = zeroBSCRM_unsub_checkCreatePage();

                                    if (!empty($pageID) && $pageID > 0){

                                      // success
                                      $newPageURL = admin_url('post.php?post='.$pageID.'&action=edit');
                                      echo zeroBSCRM_UI2_messageHTML('info',__('Unsubscribe Page Created','zero-bs-crm'),__('Zero BS CRM successfully created a new page for unsubscriptions.','zero-bs-crm').'<br /><br /><a href="'.$newPageURL.'" class="ui button primary" target="_blank">'.__('View Page','zero-bs-crm').'</a>','info','new-unsub-page');

                                    } else {

                                      // failed
                                      echo zeroBSCRM_UI2_messageHTML('warning',__('Page Was Not Created','zero-bs-crm'),__('Zero BS CRM could not create a new page for unsubscriptions. If this persists, please contact support.','zero-bs-crm'),'info','new-unsub-page');
                                    
                                    }


                                }


                                $args = array('name' => 'wpzbscrm_unsubpage', 'id' => 'wpzbscrm_unsubpage','show_option_none' => __('No Page Found!','zero-bs-crm'));
                                if($pageID != -1){
                                  $args['selected'] = (int)$pageID;
                                }else{
                                  $args['selected'] = 0;
                                }
                                wp_dropdown_pages($args); 

                                // recreate link
                                $recreatePageURL = wp_nonce_url(admin_url('admin.php?page='.$zbs->slugs['settings'].'&tab=mail&recreateunsubpage=1'), 'recreate-unsub-page', 'unsubPageNonce');

                                // detect missing page (e.g. it hasn't autocreated for some reason, or they deleted), and offer a 'make page' button
                                if (zeroBSCRM_mail_getUnsubscribePage() == -1){

                                  echo zeroBSCRM_UI2_messageHTML('warning',__('No Unsubscription Page Found!','zero-bs-crm'),__('Zero BS CRM could not find a published WordPress page associated with Unsubscriptions. Please recreate this page to continue using the mail functionality of Zero BS CRM.','zero-bs-crm').'<br /><br /><a href="'.$recreatePageURL.'" class="ui button primary">'.__('Recreate Unsubscription Page','zero-bs-crm').'</a>','info','no-unsub-page');

                                } else {

                                  // no need really?

                                }
                            ?>
                          </td>
                        </tr>

                        <tr>
                            <td class="wfieldname"><label for="wpzbs_unsubmsg"><?php _e('Email Unsubscribe Line',"zero-bs-crm"); ?>:</label><br /><?php _e('This message will be shown to contacts after they have unsubscribed.',"zero-bs-crm"); ?></td>
                            <td style="width:540px"><input type="text" class="winput form-control" name="wpzbs_unsubmsg" id="wpzbs_unsubmsg" value="<?php if (isset($settings['unsubmsg']) && !empty($settings['unsubmsg'])) echo $settings['unsubmsg']; ?>" placeholder="e.g. You've been successfully unsubscribed." /></td>
                        </tr>

                      </tbody>

                  </table>
          

                  <table class="table table-bordered table-striped wtab">
                    <tbody>

                        <tr>
                          <td colspan="2" class="wmid"><button type="submit" class="ui button primary"><?php _e('Save Settings',"zero-bs-crm"); ?></button></td>
                        </tr>

                      </tbody>
                  </table>
                  
                  <div style="text-align:center;margin-top:3.5em">
                            <a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['settings'].'&tab=maildelivery'); ?>" class="ui button positive"><?php _e('Setup Mail Delivery','zero-bs-crm'); ?></a>&nbsp;
                            <a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['email-templates']); ?>" class="ui button positive"><?php _e('Edit Email Templates','zero-bs-crm'); ?></a>
                  </div>

              </form>


              <script type="text/javascript">

                jQuery(document).ready(function(){


                });


              </script>
              
      </div><?php 
      
      }else {

          ?><div id="clpSubPage" class="whclpActionMsg six">
            <p><strong><?php echo $confirmActStr; ?></strong></p>
              <h3><?php echo $confirmActStrShort; ?></h3>
              <?php echo $confirmActStrLong; ?><br /><br />
              <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo wp_nonce_url('?page='.$zbs->slugs['settings'].'&'.$actionStr.'=1&imsure=1','resetclearzerobscrm'); ?>';"><?php echo $actionButtonStr; ?></button>
              <button type="button" class="button button-large" onclick="javascript:window.location='?page=<?php echo $zbs->slugs['settings']; ?>';"><?php _e("Cancel","zero-bs-crm"); ?></button>
              <br />
        </div><?php 
      } 


}

function zeroBSCRM_pages_admin_system_emails(){

  global $zbs;

  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
   
  /*
  zeroBS_genericLearnMenu($title='',$addNew='',$filterStr='',$showLearn=true,$h3='',$learnContent='',$learnMoreURL='',$learnImgURL='',$learnVidURL=false,$hopscothCustomJS=false,$popupExtraCSS='')
  */


    //handle saving of the email template via normal $_POST[]...
    /* POST ID AND NAME FIELDS
    zbssubject -> subject
    zbsfromname
    zbsfromaddress

    */

    // discern subpage
    $page = 'recent-activity'; $pageName = __('Recent Email Activity','zero-bs-crm');
    if (isset($_GET['zbs_template_editor']) && !empty($_GET['zbs_template_editor'])) {
      $page = 'template-editor';
      $pageName = __('Template Settings','zero-bs-crm');
    }
    if (isset($_GET['zbs_template_id']) && !empty($_GET['zbs_template_id'])){
      $page = 'email-templates';
      $pageName = __('System Email Templates','zero-bs-crm');
    } 

    zeroBS_genericLearnMenu($pageName,'','',false,'','','','',false,false,'');


    // for now put this here, should probs be stored against template:
    // template id in here = can turn on/off
    $sysEmailsActiveInactive = array(1,2,6);

    // using tracking?
    $trackingEnabled = $zbs->settings->get('emailtracking');

 ?>
  <style>
    .email-stats{
      display: block;
      font-size: .75rem;
      text-transform: uppercase;
      color: #b8b8d9;
      font-weight: 600;
    }
    .email-template-box{
      cursor:pointer;
    }
    .the-templates a{
      color: black;
      font-weight:900;
    }
    time{
      white-space: nowrap;
      text-transform: uppercase;
      font-size: .5625rem;
      margin-left: 5px;
    }
    .hist-label{
      margin-right: 6px !important;
    }
    .email-sending-record{
      padding:10px;
    }
    .template-man-h4{
        font-weight:900;
        margin-bottom:0px;
        padding-top:10px;
    }
    .email-stats-top{
      font-size:13px;
      margin-top:5px;
      margin-bottom:5px;
    }
    .email-template-form label{
      text-transform: uppercase !important;
    }
    .the-templates .active{
    border: 1px solid #3f4347;
    border-left: 3px solid #3f4347;   
    }

    #tinymce{
      margin-left: 12px !important;
    }
    .lead{
      margin-top:5px;
      margin-bottom:5px;
    }
    .email-html-editor-free pre{
      text-align: center;
      padding: 50px;
      background: #f5f5f5;
      border: 2px dotted #ddd;
    }
    .update-nag{
      display:none;
    }
  </style>


  <script type="text/javascript">

      jQuery(document).ready(function(){

          jQuery('#zbs-sys-email-template-editor i.info.popup').popup({
            //boundary: '.boundary.example .segment'
          });

          jQuery('.zbs-turn-inactive').on("click",function(e){
              if(jQuery(this).hasClass('negative')){
                  return false;
              }
              jQuery('#zbs-saving-email-active').addClass('active');
              var theid = jQuery(this).data('emid');
              jQuery('#the-positive-button-' + theid).removeClass('positive');
              jQuery(this).addClass('negative');
              jQuery('.active-to-inactive-' + theid).addClass('negative');

              var t = {
                  action: "zbs_save_email_status",
                  id:  theid,
                  status: 'i',
                  security: jQuery( '#zbs-save-email_active' ).val(),
              }  
              i = jQuery.ajax({
                  url: ajaxurl,
                  type: "POST",
                  data: t,
                  dataType: "json"
              });
              i.done(function(e) {
                console.log(e);
                jQuery('#zbs-saving-email-active').removeClass('active');
                jQuery('#zbs-list-status-' + theid).removeClass('green').addClass('red');
                jQuery('#zbs-list-status-' + theid).html("<?php _e('Inactive','zero-bs-crm'); ?>");
              }),i.fail(function(e) {
              });
          });


          jQuery('#force-email-create').on("click", function(e){
              jQuery('#zbs-saving-email-create').addClass('active');
        
              var t = {
                  action: "zbs_create_email_templates",
                  security: jQuery( '#zbs_create_email_nonce' ).val(),
              }  
              
              i = jQuery.ajax({
                  url: ajaxurl,
                  type: "POST",
                  data: t,
                  dataType: "json"
              });
              i.done(function(e) {
                console.log(e);
                jQuery('#zbs-saving-email-create').removeClass('active');
                jQuery('#zbs-emails-result').html("");
                jQuery('.template-generate-results').show();

                // wh: just force reload it here?
                window.location.reload(false); 
               
              }),i.fail(function(e) {
              });


          });


          jQuery('.zbs-turn-active').on("click",function(e){
              
              jQuery('#zbs-saving-email-active').addClass('active');

              var theid = jQuery(this).data('emid');
              jQuery('#active-to-inactive-' + theid).removeClass('negative');
              jQuery(this).addClass('positive');
              jQuery('.inactive-to-active-' + theid).addClass('positive');

              //we want to AJAX save it using this action
              // zbs_save_email_status
              // with this nonce. 
              var t = {
                  action: "zbs_save_email_status",
                  id:  theid,
                  status: 'a',
                  security: jQuery( '#zbs-save-email_active' ).val(),
              }  
              
              i = jQuery.ajax({
                  url: ajaxurl,
                  type: "POST",
                  data: t,
                  dataType: "json"
              });
              i.done(function(e) {
                console.log(e);
                jQuery('#zbs-saving-email-active').removeClass('active');
                jQuery('#zbs-list-status-' + theid).removeClass('red').addClass('green');
                jQuery('#zbs-list-status-' + theid).html("<?php _e('Active','zero-bs-crm'); ?>");
              }),i.fail(function(e) {
              });


          });

      });

  </script>

  <?php

    $em_templates = '';
    $rec_ac = 'active';
    $template_id = -1;
    $tem_set = '';
    
    if (isset($_GET['zbs_template_id']) && !empty($_GET['zbs_template_id'])){
        $em_templates = 'active';
        $rec_ac = '';
        $template_id = (int)sanitize_text_field($_GET['zbs_template_id']);
        $tem_set = '';
    } else if (isset($_GET['zbs_template_editor']) && !empty($_GET['zbs_template_editor'])){
      $em_templates = '';
      $rec_ac = '';
      $template_id = -1;
      $tem_set = 'active';
    }

    $rec_acc_link = esc_url(admin_url('admin.php?page=zbs-email-templates'));


  ?>

  <div class="ui grid" style="margin-right:20px;">
    <div class="eight wide column"></div>
    <div class="eight wide column">
      <div id="email-template-submenu-admin" class="ui secondary menu pointing" style="float:right;">
          <a class="ui item <?php echo $rec_ac; ?>" href="<?php echo $rec_acc_link;?>"><?php _e("Recent Activity",'zero-bs-crm');?></a>
          <a class="ui item <?php echo $em_templates; ?>" href="<?php echo $rec_acc_link;?>&zbs_template_id=1"><?php _e("Email Templates",'zero-bs-crm');?></a>
          <a class="ui item <?php echo $tem_set; ?>" href="<?php echo $rec_acc_link;?>&zbs_template_editor=1"><?php _e("Template Settings",'zero-bs-crm');?></a>
      </div>
    </div>
  </div>


  <?php //if(isset($_GET['zbs_template_editor']) && !empty($_GET['zbs_template_editor'])){
    if ($page == 'template-editor'){ ?>

      <div class="ui segment" style="margin-right:20px;">
        <h4 class="template-man-h4"><?php _e("HTML Template", 'zero-bs-crm'); ?></h4>
        <p class='lead'><?php _e('This template is used for all outgoing ZBS emails. The <code>###MSGCONTENT###</code> placeholder represents the per-template content and must not be removed.','zero-bs-crm');?></p>
      
        <?php  ##WLREMOVE ?>
        <p class='lead'>
          <?php _e("You can edit this template by modifying ", 'zero-bs-crm'); ?>
          <code>/zero-bs-crm/html/templates/_responsivewrap.html</code> <?php _e(" but it is recommended to leave this template in tact for maximum device support."); ?>
        </p>
        <?php ##/WLREMOVE ?>
      
        <div class="ui divider"></div>
        <textarea cols="70" rows="25" name="zbstemplatehtml" id="zbstemplatehtml"><?php
            echo zeroBSCRM_mail_retrieveDefaultBodyTemplate('maintemplate');
          ?>
        </textarea>
        <div class="ui grid" style="margin-right:-15px;margin-top:20px;">
          <div class="eight wide column">
            <?php
              echo '<a href="' .$rec_acc_link .'" style="text-decoration:underline;font-size:11px;">' . __('Back to Activity','zero-bs-crm') . '</a>';
            ?>
          </div>
          <div class="eight wide column">
            <?php
            echo "<div style='float:right;'>";
              echo '<a href="'.site_url('?zbsmail-template-preview=1') .'"class="ui button inverted blue small" target="_blank">'.__('Preview','zero-bs-crm') .'</a>';
            echo '</div>';
            ?>
          </div>
        </div>
      </div>
    <?php
    } else { 
    ?>
    <div class="ui grid" id="zbs-sys-email-template-editor">

        <div class="five wide column the-templates">
            <?php
              //the template list...
              $zbs_system_emails = zeroBSCRM_mailTemplate_getAll();
              if(count($zbs_system_emails) == 0){

                //something went wrong with the creation of the emails...
                echo "<div class='ui segment' style='text-align:center'>";
                
                echo "<div id ='zbs-emails-result'>";
                    echo "<div class='ui inverted dimmer' id='zbs-saving-email-create'><div class='ui text loader'>".__("Creating templates....", 'zero-bs-crm') . "</div></div>";

                  echo '<h4 class="template-man-h4">' . __('No Email Templates', 'zero-bs-crm') . "</h4>";
                  echo "<p class='lead' style='padding:10px;'>" . __('Something went wrong with the email template creation.<br/>','zero-bs-crm') . "</p>";
                  echo "<div class='button ui large blue' id='force-email-create'>" . __('Create Now', 'zero-bs-crm') . "</div>";

                  echo "</div>";

                echo "<div class='template-generate-results' style='display:none;'>";
                  echo "<h4>" . __("Template Creation Succeeded",'zero-bs-crm') . "</h4>";
                  echo "<a href='".$rec_acc_link."' class='button ui green'>" . __('Reload Page','zero-bs-crm') . "</a>";
                echo "</div>";



                echo "</div>";



                echo '<input type="hidden" name="zbs_create_email_nonce" id="zbs_create_email_nonce" value="' . wp_create_nonce( 'zbs_create_email_nonce' ) . '" />';

              }
              foreach($zbs_system_emails as $sys_email){
                if($sys_email->zbsmail_id > 0){

                  if($template_id == $sys_email->zbsmail_id){
                    $class = 'active';
                  }else{
                    $class = '';
                  }

                  $link = esc_url(admin_url('admin.php?page=zbs-email-templates&zbs_template_id=' . $sys_email->zbsmail_id));

                  echo "<a href='$link'><div class='ui segment email-template-box $class' style='margin-bottom:10px;'>";
                    echo zeroBSCRM_mailTemplate_getSubject($sys_email->zbsmail_id);
  
                    // can be enabled/disabled
                    if (in_array($sys_email->zbsmail_id, $sysEmailsActiveInactive)){

                      if($sys_email->zbsmail_active == 1){
                        echo "<div class='ui label green tiny' id='zbs-list-status-". $sys_email->zbsmail_id."' style='float:right;margin-top:10px;'>" . __("Active",'zero-bs-crm') . "</div>";
                      }else{
                        echo "<div class='ui label red tiny' id='zbs-list-status-". $sys_email->zbsmail_id."' style='float:right;margin-top:10px;'>" . __("Inactive",'zero-bs-crm') . "</div>";     
                      }

                    }
  
                    // if tracking
                    if ($trackingEnabled == "1"){
                      echo "<div class='email-stats'>";
                        zeroBSCRM_mailDelivery_getTemplateStats($sys_email->zbsmail_id);
                      echo "</div>";
                    } else {
                      echo '<div class="email-stats">&nbsp;</div>';
                    }

                  echo "</div></a>";

                  }

              }
            ?>
            <div style="text-align:center;margin-top:1em">
              <a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['settings'].'&tab=mail'); ?>" class="ui basic button"><?php _e('Back to Mail Settings','zero-bs-crm'); ?></a>
            </div>
        </div>

        <div class="eleven wide column">
            <div class="segment ui" id="email-segment">
                <?php 
        
                  if(isset($_POST['zbssubject']) && !empty($_POST['zbssubject'])){

                    /* WH switched for mail delivery opts
                    $zbsfromname    = sanitize_text_field($_POST['zbsfromname']);
                    
                    //using sanitize email, can 
                    $zbsfromaddress = sanitize_email($_POST['zbsfromaddress']);
                    $zbsreplyto     = sanitize_email($_POST['zbsreplyto']);
                    $zbsccto        = sanitize_email($_POST['zbsccto']);
                    $zbsbccto       = sanitize_email($_POST['zbsbccto']);
                    */

                    // Mail Delivery
                    $zbsMailDeliveryMethod = sanitize_text_field($_POST['zbs-mail-delivery-acc']);
                    $zbsbccto       = sanitize_email($_POST['zbsbccto']);
                    

                    //this sanitizes the post content..
                    $zbscontent     = wp_kses_post($_POST['zbscontent']);
                    $zbssubject     = sanitize_text_field($_POST['zbssubject']);

                    if(isset($_GET['zbs_template_id'])){

                      $updateID = (int)sanitize_text_field($_GET['zbs_template_id']);

                      // wh simplified for del methods
                      //zeroBSCRM_updateEmailTemplate($updateID,$zbsfromname,$zbsfromaddress,$zbsreplyto, $zbsccto, $zbsbccto,$zbssubject, $zbscontent );
                      zeroBSCRM_updateEmailTemplate($updateID, $zbsMailDeliveryMethod, $zbsbccto,$zbssubject, $zbscontent );

                      echo "<div class='ui message green' style='margin-top:45px;margin-right:15px;'>" . __('Template updated','zerobsscrm') . '</div>';



                    }


                  }



                  if(isset($_GET['zbs_template_id']) && !empty($_GET['zbs_template_id'])){

                    //the tab number matches the template ID.
                    $emailtab = (int)sanitize_text_field($_GET['zbs_template_id']);

                    $form = '';

                    //single template data.
                    $data = zeroBSCRM_mailTemplate_get($emailtab);
                    if (gettype($data) == 'object') $form = $data;

                    if(!empty($form)){ 

                        //will need to nonce this up ... (?)
                        if(isset($_GET['sendtest']) && !empty($_GET['sendtest'])){

                            //we are sending a test...
                            $current_user   = wp_get_current_user();
                            $test_email    = $current_user->user_email;
                            
                            $html = zeroBSCRM_mailTemplate_emailPreview($emailtab);

                            //send it 
                            $subject = $form->zbsmail_subject;
                            $headers = zeroBSCRM_mailTemplate_getHeaders($emailtab);

                          /* old way


                            wp_mail( $test_email, $subject, $html, $headers );

                          */
                          
                          // discern del method
                          $mailDeliveryMethod = zeroBSCRM_mailTemplate_getMailDelMethod($emailtab);
                          if (!isset($mailDeliveryMethod) || empty($mailDeliveryMethod)) $mailDeliveryMethod = -1;

                            // build send array
                            $mailArray = array(
                              'toEmail' => $test_email,
                              'toName' => '',
                              'subject' => $subject,
                              'headers' => $headers,
                              'body' => $html,
                              'textbody' => '',
                              'options' => array(
                                'html' => 1
                              )
                            );

                            // Sends email
                            $sent = zeroBSCRM_mailDelivery_sendMessage($mailDeliveryMethod,$mailArray);

                            echo "<div class='ui message green' style='margin-top:45px;margin-right:15px;'>" . __('Test Email Sent to ','zerobsscrm') . '<b>'. $test_email .'</b></div>';
                        }


                          echo "<h4 class='template-man-h4'>". zeroBSCRM_mailTemplate_getSubject($emailtab) . "</h4>";

                          echo "<div class='email-stats email-stats-top'>";
                            zeroBSCRM_mailDelivery_getTemplateStats($emailtab);
                          echo "</div>";

                          echo "<div class='ui inverted dimmer' id='zbs-saving-email-active'><div class='ui text loader'>".__("Saving....", 'zero-bs-crm') . "</div></div>";

                          wp_nonce_field( "zbs-save-email_active" );

                          echo '<input type="hidden" name="zbs-save-email_active" id="zbs-save-email_active" value="' . wp_create_nonce( 'zbs-save-email_active' ) . '" />';


                          // can be enabled/disabled
                          if (in_array($form->zbsmail_id, $sysEmailsActiveInactive)){

                              if($form->zbsmail_active){
                                // 1 = active, 0 = inactive..
                                echo '<div class="ui buttons tiny" style="float: right;
                                        position: absolute;
                                        top: 19px;
                                        right: 20px;">
                                        <button class="ui positive button zbs-turn-active" id="the-positive-button-'.$emailtab.'" data-emid="'.$emailtab.'">Active</button>
                                        <div class="or"></div>
                                        <button class="ui button zbs-turn-inactive" id="active-to-inactive-'.$emailtab.'" data-emid="'.$emailtab.'">Inactive</button>
                                      </div>';
                              }else{  
                                echo '<div class="ui buttons tiny" style="float: right;
                                        position: absolute;
                                        top: 19px;
                                        right: 20px;">
                                        <button class="ui button zbs-turn-active" id="the-positive-button-'.$emailtab.'" data-emid="'.$emailtab.'">Active</button>
                                        <div class="or"></div>
                                        <button class="ui button zbs-turn-inactive negative" id="active-to-inactive-'.$emailtab.'" data-emid="'.$emailtab.'">Inactive</button>
                                      </div>';
                              }

                          }


                          echo "<div class='ui divider'></div>";

                          $formlink = esc_url(admin_url('admin.php?page=zbs-email-templates&zbs_template_id=' . $emailtab));

                          echo "<form class='ui form email-template-form' action='".$formlink."' METHOD='POST'>";

                          echo '<div class="field">';
                            echo '<label>' . __('Subject','zero-bs-crm') .'</label>';
                            echo '<input id="zbssubject" name="zbssubject" type="text" value="'.$form->zbsmail_subject.'">';
                          echo '</div>';

                          // 11/05/18 - delivery methods replace hard-typed opts here
                          echo '<div class="field">';
                            
                            echo '<div class="ui grid" style="margin-bottom:-0.4em"><div class="four wide column">';
                            echo '<label>' . __('Delivery Method','zero-bs-crm') .'</label>';
                            echo '</div><div class="twelve wide column">';
                            /* this looks bad ?><i class="circle info icon popup link" data-html="<?php _e("You can set up different delivery methods in your ","zero-bs-crm");?> <a href=''><?php _e('Delivery Methods Settings','zero-bs-crm'); ?></a>" data-position="bottom center"></i><?php */
                            ?><div class="ui teal label right floated"><i class="circle info icon link"></i> <?php _e("You can set up different delivery methods in your ","zero-bs-crm"); ?> <a href="<?php echo zbsLink($zbs->slugs['settings']).'&tab=maildelivery'; ?>"><?php _e('Delivery Methods Settings','zero-bs-crm'); ?></a></div><?php
                            echo '</div></div>';

                            zeroBSCRM_mailDelivery_accountDDL($form->zbsmail_deliverymethod);
                            
                          echo '</div>';

                          /* 
                          echo '<div class="field">';
                            echo '<label>' . __('From Name','zero-bs-crm') .'</label>';
                            echo '<input id="zbsfromname" name="zbsfromname" type="text" value="'.$form->zbsmail_fromname.'">';
                          echo '</div>';

                          echo '<div class="field">';
                            echo '<label>' . __('From Email','zero-bs-crm') .'</label>';
                            echo '<input id="zbsfromaddess" name="zbsfromaddress" type="text" value="'.$form->zbsmail_fromaddress.'">';
                          echo '</div>';

                          echo '<div class="field">';
                            echo '<label>' . __('Reply To','zero-bs-crm') .'</label>';
                            echo '<input id="zbsreplyto" name="zbsreplyto" type="text" value="'.$form->zbsmail_replyto.'">';
                          echo '</div>';

                          echo '<div class="field zbs-hide">';
                            echo '<label>' . __('Cc To','zero-bs-crm') .'</label>';
                            echo '<input id="zbsccto" name="zbsccto" type="text" value="'.$form->zbsmail_ccto.'">';
                          echo '</div>';
                          */

                          echo '<div class="field">';
                            echo '<label>' . __('Bcc To','zero-bs-crm') .'</label>';
                            echo '<input id="zbsbccto" name="zbsbccto" type="text" value="'.$form->zbsmail_bccto.'">';
                          echo '</div>';

                          echo '<div class="field">';
                            echo '<label>' . __('Content','zero-bs-crm') .'</label>';
                          $content = esc_html($form->zbsmail_body);
                          $edirotsettings = array(
                                  'media_buttons' => false,
                                  'editor_height' => 350,
                                  'quicktags' => false,
                                  'tinymce'=> false,
                          );
                          wp_editor( htmlspecialchars_decode($content), 'zbscontent',  $edirotsettings); 

                          echo '</div>';
                          ?>

                          <div class="ui grid" style="margin-right:-15px;">
                            <div class="eight wide column">
                              <?php
                                echo '<a href="' .$rec_acc_link. '&zbs_template_editor=1" style="text-decoration:underline;font-size:11px;">' . __('Edit HTML Template','zero-bs-crm') . '</a>';
                              ?>
                            </div>
                            <div class="eight wide column">
                              <?php

                              $sendtestlink = esc_url(admin_url('admin.php?page=zbs-email-templates&zbs_template_id=' . $emailtab . '&sendtest=1'));

                              echo "<div style='float:right;'>";
                                echo '<a href="'.site_url("?zbsmail-template-preview=1&template_id=". $emailtab).'" target="_blank" class="ui button inverted blue small">'.__('Preview','zero-bs-crm') .'</a>';
                                echo '<a href="'.$sendtestlink.'" class="ui button blue small">'.__('Send Test','zero-bs-crm') .'</a>';
                                echo '<input class="ui button green small" type="submit" value="'.__('Save','zero-bs-crm').'">';
                              echo '</div>';
                              ?>
                            </div>



                          </form>

                          <?php


                      }else{
                          echo "<div class='ui message blue'>";
                              echo "<i class='icon info'></i>" . __("No templates. Please generate", 'zero-bs-crm');
                          echo "</div>";
                      }


                //    zbs_prettyprint($data);



                    echo "</div>";


                  }else{
                    ?>

                      <h4 class="template-man-h4"><?php _e("Sent Emails", 'zero-bs-crm'); ?></h4>
                      <p class='lead'><?php _e("Your latest 50 emails are shown here so you can keep track of activity.", 'zero-bs-crm'); ?></p>
                      <div class="ui divider"></div>

                    <?php

                      zeroBSCRM_outputEmailHistory();

                  }

                ?>
            </div>
        </div>
    </div>

  <?php } //end of code for if template setting is being shown... ?>

  <?php
}



function zeroBSCRM_pages_settings() {

  global $wpdb, $zbs; #} Req
  
  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
   
  $settings_class = '';

  #} Modified this to work with $zeroBSCRM_extensionsInstalledList setup
  global $zeroBSCRM_extensionsInstalledList;

 // $zbs->write_log($zeroBSCRM_extensionsInstalledList);
 //  $zbs->write_log($zbs->extensions);

  #} $zbs allows filtering of the extensionsInstalled list so extensions add to here.
  $zeroBSCRM_extensionsInstalledList = $zbs->extensions;


    #} Retrieve any legit tab
  $currentTab = 'settings'; $getTab = ''; if (isset($_GET['tab'])) $getTab = sanitize_text_field($_GET['tab']);
  if ( !empty ( $getTab  )  && in_array($getTab,$zeroBSCRM_extensionsInstalledList)) $currentTab = $getTab;
   if ($getTab == 'customfields') $currentTab = 'customfields';
  if ($getTab == 'customers') $currentTab = 'customers';
  if ($getTab == 'quotes') $currentTab = 'quotes';
  if ($getTab == 'invoices') $currentTab = 'invoices';
  if ($getTab == 'forms') $currentTab = 'forms';

  if ($getTab == 'clients') $currentTab = 'clients';

  #} transaction settings
  if ($getTab == 'transactions') $currentTab = 'transactions';


  #} Added 1.1.19 - field sorts
  if ($getTab == 'fieldsorts') $currentTab = 'fieldsorts';

  #} Added  1.2.7 - language/labels
  if ($getTab == 'whlang') $currentTab = 'whlang';

  #} Added 2.0 - API
  if($getTab == 'api') $currentTab = 'api';

  #} Added 2.2+ - Mail Delivery
  // Temporarily removed until MC2 catches up + finishes Mail Delivery: 
  if ($getTab == 'mail') $currentTab = 'mail';
  if ($getTab == 'maildelivery') $currentTab = 'maildelivery';
  if ($getTab == 'mailcampaigns') $currentTab = 'mailcampaigns';

  #} Added 2.4+ - List view 
  if ($getTab == 'listview') $currentTab = 'listview';

  #} Added 2.9+ - License Key
  if ($getTab == 'license') $currentTab = 'license';
  
  #} Added 2.90 - bizinfo
  if ($getTab == 'bizinfo') $currentTab = 'bizinfo';

  #} Added 3.0 - field options
  if ($getTab == 'fieldoptions') $currentTab = 'fieldoptions'; 

  #} Added 3.0+ - Tax
  if ($getTab == 'tax') $currentTab = 'tax';


  #} Settings updated msg
  if ( isset($_GET['updated']) && 'true' == esc_attr( $_GET['updated'] ) ) echo '<div class="updated" ><p>Settings updated.</p></div>';

  #} Get tabs
  zeroBSCRM_html_settings_menu($currentTab);

    ?><div id="poststuff" class="pusher zbs-settings-page">
    
        <?php
        wp_nonce_field( "ilc-settings-page" ); 

        global $pagenow;
        
        if ( $pagenow == 'admin.php' && $_GET['page'] == $zbs->slugs['settings'] ){ 
        
          #} WH Removed - something to do with breaking the html below here makes WP mess with my custom fields settings page
          #} echo '<table class="form-table">';

          if ($currentTab == 'settings'){


            #} rough header, should be integrated... later
            ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('General Settings',"zero-bs-crm"); ?></h1><?php

            #} Def settings page
            zeroBSCRM_html_settings();

          } else if ($currentTab == 'customfields') {

            #} rough header, should be integrated... later
            ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('Custom Fields',"zero-bs-crm"); ?></h1><?php

            #} Custom fields page
            zeroBSCRM_html_customfields();

          # wh moved this to it's extension name (invoice builder) - to make it homogenous with quote builder settings
          # is now caught by extension code below, and fired by func zeroBSCRM_extensionhtml_settings_invbuilder
          #} else if($currentTab == 'invoices'){ 
            #zeroBSCRM_html_settings_invoices();
          } else if ($currentTab == 'fieldoptions') {

            #} rough header, should be integrated... later
            ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('Field Options',"zero-bs-crm"); ?></h1><?php

            #} Field options page (v3.0+)
            zeroBSCRM_html_settings_fieldOptions();

          } else if ($currentTab == 'listview') {

            #} rough header, should be integrated... later
            ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('List View',"zero-bs-crm"); ?></h1><?php

            #} list view page
            zeroBSCRM_html_listview_settings();

        } else if ($currentTab == 'license') {

          #} rough header, should be integrated... later
          ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('License Key',"zero-bs-crm"); ?></h1><?php

          #} list view page
          zeroBSCRM_html_license_settings();

        }else if($currentTab == 'clients'){ 

            #} rough header, should be integrated... later
            ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('Client Portal',"zero-bs-crm"); ?></h1><?php

            zeroBSCRM_html_settings_clients();

          } 

          else if($currentTab == 'transactions'){ 

            #} rough header, should be integrated... later
            ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('Transactions',"zero-bs-crm"); ?></h1><?php

            zeroBSCRM_html_settings_transactions();

          } 


           else if($currentTab == 'forms'){ 

            #} rough header, should be integrated... later
            ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('Forms',"zero-bs-crm"); ?></h1><?php

            zeroBSCRM_html_settings_forms();

          } else if ($currentTab == 'fieldsorts') {

            #} rough header, should be integrated... later
            ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('Field Sorts',"zero-bs-crm"); ?></h1><?php


            #} Field Sorts page
            zeroBSCRM_html_fieldsorts();

          } else if ($currentTab == 'api') {

            #} rough header, should be integrated... later
            ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('API Settings',"zero-bs-crm"); ?></h1><?php


            #} API Key page
            zeroBSCRM_html_api_page();

          } else if ($currentTab == 'mail') {

            #} rough header, should be integrated... later
            ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('Mail Settings',"zero-bs-crm"); ?></h1><?php

            #} Mail Delivery
            zeroBSCRM_html_settings_mail();

          }  else if ($currentTab == 'maildelivery') {

            #} Mail Delivery
            zeroBSCRM_html_settings_mail_delivery();

          }  else if ($currentTab == 'bizinfo') {

            #} rough header, should be integrated... later
            ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('Your Business Info',"zero-bs-crm"); ?></h1><?php

            #} bizinfo
            zeroBSCRM_html_settings_bizinfo();

          } else if ($currentTab == 'tax') {

            #} rough header, should be integrated... later
            ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('Tax Settings',"zero-bs-crm"); ?></h1><?php

            #} tax
            zeroBSCRM_html_settings_tax();

          }

          /*else if ($currentTab == 'whlang') {

            #} rough header, should be integrated... later
            ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e('Language Settings',"zero-bs-crm"); ?></h1><?php

            #} Lang page
            global $zeroBSCRM_helpEmail;
            $langPageHTML = zeroBSCRM_whLangLibLangEditPage('zerobscrm','zeroBSCRM_Settings','Zero BS CRM',$zeroBSCRM_helpEmail);
            echo $langPageHTML;


          } */
          else {


            #} Modified this to work with $zeroBSCRM_extensionsInstalledList setup
            if (function_exists('zeroBSCRM_extensionhtml_settings_'.$currentTab)){

                #} Hacky settings page titles... lol. v2.12
                if (function_exists('zeroBSCRM_extension_name_'.$currentTab)){
                  $settingPageName = call_user_func('zeroBSCRM_extension_name_'.$currentTab);
                  #} rough header, should be integrated... later
                  if (!empty($settingPageName)) { ?><h1 class="ui header blue" style="margin-top: 0;"><?php _e($settingPageName,"zero-bs-crm"); ?></h1><?php }
                }
        
              #} Fire it to generate page :)
              $settingsPageFunc = 'zeroBSCRM_extensionhtml_settings_'.$currentTab;
              call_user_func($settingsPageFunc);

            #} code here for the class version
            }else {

        

               zeroBSCRM_html_msg(-1,'There was an error loading this settings page ' . $settings_class);
              // }
            }

          }

          #} WH Removed echo '</table>';
        }
        ?>
  
      <?php //socials code ?>

    </div>

  </div> <?php // end of the ui grid col ?>
    </div> <?php // end of the ui grid ?>


<?php
  
  #} Footer
  zeroBSCRM_pages_footer();

?>

<?php 
}



#} API key management page
function zeroBSCRM_html_api_page(){

  global $zbs;

    $confirmAct = false;

    #} catch regen.
    if (isset($_GET['regeneratekeys']) && zeroBSCRM_isZBSAdminOrAdmin()) if ($_GET['regeneratekeys']==1){

      $nonceVerified = wp_verify_nonce( $_GET['_wpnonce'], 'regeneratekeys' );

      if (!isset($_GET['imsure']) || !$nonceVerified){

          #} Needs to confirm!  
          $confirmAct = true;
          $actionStr        = 'regeneratekeys';
          $actionButtonStr    = __('Regenerate API Key & Secret?',"zero-bs-crm");
          $confirmActStr      = __('Regenerate API Credentials',"zero-bs-crm");
          $confirmActStrShort   = __('Are you sure you want to regenerate your API Credentials',"zero-bs-crm");
          $confirmActStrLong    = __('Regenerating your API Credentials will mean that your existing details will no longer work.',"zero-bs-crm");

        } else {


          if ($nonceVerified){

              $newKey = zeroBSCRM_regenerateAPIKey();
              $newSecret = zeroBSCRM_regenerateAPISecret();
              $generatedNewKey = 1;

          }

        }

    } 

    #} generate?    
    if (isset($_POST['generate-key']) && $_POST['generate-key'] == 1 && zeroBSCRM_isZBSAdminOrAdmin()) {

      $newKey = zeroBSCRM_regenerateAPIKey();
      $newSecret = zeroBSCRM_regenerateAPISecret();
      $generatedNewKey = 1;

    }
    
    //$api_keys = $wpdb->get_results("SELECT * FROM $api_table");
    #$api_keys = zeroBSCRM_getAPIKeys();
    $api_key = zeroBSCRM_getAPIKey();
    $api_secret = zeroBSCRM_getAPISecret();

    $endpoint_url = zeroBSCRM_getAPIEndpoint(); 
    
    #} Warning if permalinks not pretty
    if(!zeroBSCRM_checkPrettyPermalinks()){
      $permalinks_url = admin_url('options-permalink.php');
      echo "<div class='ui error message danger' style='display:block;'><i class='exclamation circle icon white'></i>" . __('Permalinks need to be pretty for the API to be available. Update your', 'zero-bs-crm') . " <a href='".  esc_url($permalinks_url)  ."'>". __('Permalink settings', 'zero-bs-crm')  .".</a></div>";
    }


    if ($api_key == ''){



       ?>
       <style>
          .zbs-api-key-generate{
            padding:20px;
            text-align:center;
            font-size:30px;
            background:white;
          }
          .zbs-api-key-generate .button-primary{
              font-size:20px;
          }
       </style>

       
      <div class='zbs-api-key-generate'>
        <form action="#" method="POST">
            <p>
            <?php _e("You do not have an API key. Generate one?"); ?>
            </p>
            <input type='submit' class='generate-api ui primary button' value='<?php _e("Generate API key"); ?>'/>
            <input type='hidden' name='generate-key' id='generate-key' value='1'/>
        </form>
      </div>
       <?php
    } else {

        #} notify
        if (isset($generatedNewKey)) zeroBSCRM_html_msg(0,__('Successfully generated API Credentials',"zero-bs-crm"));

        $perms = array('revoked','read and write');


        if (!$confirmAct){
              echo '<table class="table table-bordered table-striped wtab">';
              echo '<thead>';
                echo '<th colspan=2>' . __('API Settings') . '</th>';
              echo '</thead>';
              echo '<tbody>';
                  echo '<tr><td>'.__('API Endpoint', 'zero-bs-crm') . '</td><td class="bold">' . $endpoint_url . '</td></tr>';
                  echo '<tr><td>'. __('API Key', 'zero-bs-crm').'</td><td class="bold">' . $api_key . '</td></tr>';
                  echo '<tr><td>'.__('API Secret', 'zero-bs-crm').'</td><td class="bold">' . $api_secret . '</td></tr>';

                  ##WLREMOVE
                  ?>
                  <tr><td colspan=2><a href="<?php echo $zbs->urls['apidocs']; ?>" target="_blank" class="ui right floated tiny button"><?php _e('API Docs','zero-bs-crm'); ?></a></td></tr>
                  <?php
                  
                  ##/WLREMOVE

              echo '</tbody>';
              echo '</table>';
               ?>
               <style>
                  .zbs-api-key-generate{
                    padding:20px;
                    text-align:center;
                    font-size:30px;
                    background:white;
                  }
                  .zbs-api-key-generate .button-primary{
                      font-size:20px;
                  }
               </style>
              <div class='zbs-api-key-generate'>
                <form action="" method="POST">
                    <p style="    padding: 14px;background: #FFF;"><button type="button" class="ui primary button button-large" onclick="javascript:window.location='?page=<?php echo $zbs->slugs['settings']; ?>&tab=api&regeneratekeys=1';"><?php _e('Regenerate API Credentials',"zero-bs-crm"); ?></button> </p>
                    <input type='hidden' name='generate-key' id='generate-key' value='1'/>
                </form>
              </div>
               <?php


        } else {

            ?><div id="clpSubPage" class="whclpActionMsg six">
              <p><strong><?php echo $confirmActStr; ?></strong></p>
                <h3><?php echo $confirmActStrShort; ?></h3>
                <?php echo $confirmActStrLong; ?><br /><br />
                <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo wp_nonce_url('?page='.$zbs->slugs['settings'].'&tab=api&'.$actionStr.'=1&imsure=1','regeneratekeys'); ?>';"><?php echo $actionButtonStr; ?></button>
                <button type="button" class="button button-large" onclick="javascript:window.location='?page=<?php echo $zbs->slugs['settings']; ?>&tab=api';"><?php _e("Cancel","zero-bs-crm"); ?></button>
                <br />
          </div><?php 
        } 


    }

    

}


#} Data Tools Page
function zeroBSCRM_pages_datatools() {
  
  global $wpdb, $zbs; #} Req
  
  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
    zeroBSCRM_pages_header('Import Tools');
  
  #} Settings
  zeroBSCRM_html_datatools();
  
  #} Footer
  zeroBSCRM_pages_footer();

?>
</div>
<?php 
} 


#} Install Extensions helper page
function zeroBSCRM_pages_installextensionshelper() {
  
  global $wpdb, $zbs;  #} Req
  
  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
    zeroBSCRM_pages_header(__('Installing Extensions',"zero-bs-crm"));
  
  #} Settings
  zeroBSCRM_html_installextensionshelper();
  
  #} Footer
  zeroBSCRM_pages_footer();

?>
</div>
<?php 
} 

#} Post(after) deletion Page
function zeroBSCRM_pages_postdelete() {
  
  global $wpdb, $zbs; #} Req
  
  if (
    !zeroBSCRM_permsCustomers()
    && !zeroBSCRM_permsQuotes()
    && !zeroBSCRM_permsInvoices()
    && !zeroBSCRM_permsTransactions()
    )  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
    #zeroBSCRM_pages_header('Deleted');

  #} Post Deletion page
  zeroBSCRM_html_deletion();
  
  #} Footer
  #zeroBSCRM_pages_footer();

?>
</div>
<?php 
} 



#} No rights to this (customer/company)
function zeroBSCRM_pages_norights() {
  
  global $wpdb, $zbs;  #} Req
  
  if (
    !zeroBSCRM_permsCustomers()
    && !zeroBSCRM_permsQuotes()
    && !zeroBSCRM_permsInvoices()
    && !zeroBSCRM_permsTransactions()
    )  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
    #zeroBSCRM_pages_header('Deleted');

  #} Post Deletion page
  zeroBSCRM_html_norights();
  
  #} Footer
  #zeroBSCRM_pages_footer();

?>
</div>
<?php 
} 


#} System Status Page
function zeroBSCRM_pages_systemstatus() {
  
  global $wpdb, $zbs;  #} Req
  
  if (!current_user_can('admin_zerobs_manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.',"zero-bs-crm") ); }
    
  #} Header
  zeroBSCRM_pages_header('System Status');

  #} page
  zeroBSCRM_html_systemstatus();
  
  #} Footer
  zeroBSCRM_pages_footer();

?>
</div>
<?php 
} 

// Whitelabel homepage.
function zeroBSCRM_html_wlHome(){

  global $zbs;

    ?>
    <div class="wrap">
    <h1 style="font-size: 34px;margin-left: 50px;color: #e06d17;margin-top: 1em;"><?php _e("Welcome to","zero-bs-crm");?> <b>Zero BS CRM</b></h1>
    <p style="font-size: 16px;margin-left: 50px;padding: 12px 20px 10px 20px;"><?php _e("This CRM Plugin is managed by ","zero-bs-crm");?> <b>Zero BS CRM</b>. <?php _e("If you have any questions, please","zero-bs-crm");?> <a href="<?php echo $zbs->urls['support']; ?>"><?php _e('email us',"zero-bs-crm"); ?></a>.</p>
    <?php

    // let wl users add content
    do_action( 'zerobscrm_wl_homepage');

}


#} MS - 3rd Dec 2018 - new function for the home page - function name the same, old function below
function zeroBSCRM_html_home2(){

  global $zbs;

  /*
    to highlight the benefits of ZBS CRM and going pro. Link into the new fature page
    show "Go Pro" offer and some testimonials :)
    need to remove top menu from this page ... do with ze CSS :-) 
  */

  //$add_new_customer_link = admin_url('admin.php?page=zbs-add-edit&action=edit&zbstype=contact');
  $add_new_customer_link = zbsLink('create',-1,'zerobs_customer');

  //change this to true when ELITE is out
  $isv3 = false;

  // WH added: Is now polite to License-key based settings like 'entrepreneur' doesn't try and upsell
  // this might be a bit easy to "hack out" hmmmm
  $bundle = false; if ($zbs->hasEntrepreneurBundleMin()) $bundle = true;


  // this stops hopscotch ever loading on this page :)
  ?><script type="text/javascript">var zbscrmjs_hopscotch_squash = true;</script>
  
  <div class='top-bar-welcome'></div>

  <div id="zbs-welcome">
    <div class="container">

      <div class="intro">

          <div class="bullie">
            <?php $bullie = zeroBSCRM_getBullie(); ?>
						<img src="<?php echo $bullie; ?>" alt="Bullie the ZBS mascot">
					</div>

          <div class="block">
						<h1><?php _e("Welcome to Zero BS CRM","zero-bs-crm");?></h1>
						<h6><?php _e("Thank you for choosing Zero BS CRM - The Ultimate Entrepreneurs' CRM (for WordPress)","zero-bs-crm");?></h6>
					</div>

      </div>

      <div id="first-customer">
        <a href="https://www.youtube.com/watch?v=WepIml_wWEM" target="_blank"><img src="<?php echo plugins_url('/i/first-customer-welcome-image.png', ZBS_ROOTFILE); ?>" alt="Adding your first customer"></a>
      </div>

      <div id="action-buttons" class='block'>
        <h6><?php _e("Zero BS CRM makes it easy for you to manage your customers using WordPress. To get started, ","zero-bs-crm"); echo '<a href="https://www.youtube.com/watch?v=WepIml_wWEM" target="_blank">'; _e("watch the video tutorial","zero-bs-crm"); echo '</a> '; _e("or read our guide on how create your first customer","zero-bs-crm"); ?>:</h6>
        <div class='zbs-button-wrap'>
          <div class="left">
          <a href="<?php echo esc_url($add_new_customer_link); ?>" class='add-first-customer btn btn-cta'><?php _e("Add Your First Customer","zero-bs-crm");?></a>
          </div>
          <div class="right">
            <a href="https://zerobscrm.com/kb/knowledge-base/adding-your-first-customer/" target="_blank" class='read-full-guide btn btn-hta'><?php _e("Read The Full Guide","zero-bs-crm");?></a>
          </div>
          <div class="clear"></div>
        </div>
      </div>


    </div><!-- / .container -->

    <div class="container margin-top30">
      <div class="intro zbs-features">

      <div class="block">
  			<h1><?php _e("Zero BS CRM Features and Extensions","zero-bs-crm");?></h1>
  			<h6><?php _e("Made for you, from the ground up. Zero BS CRM is both easy-to-use, and extremely flexible. Whatever your business, ZBS is the no-nonsense way of keeping a customer database","zero-bs-crm"); ?></h6>
  		</div>


      <div class="feature-list block">

  				<div class="feature-block first">
  					<img src="<?php echo plugins_url('/i/crm-dash.png', ZBS_ROOTFILE); ?>">
  					<h5>CRM Dashboard</h5>
  					<p>See at a glance the key areas of your CRM: e.g. Contact Activity, Contact Funnel, and Revenue snapshot.</p>
  				</div>

  				<div class="feature-block last">
  					<img src="<?php echo plugins_url('/i/customers.png', ZBS_ROOTFILE); ?>">
  					<h5>Limitless Contacts</h5>
  					<p>Add as many contacts as you like. No limits to the number of contacts you can add to your CRM.</p>
  				</div>

  				<div class="feature-block first">
  					<img src="<?php echo plugins_url('/i/quotes.png', ZBS_ROOTFILE); ?>">
  					<h5>Quote Builder</h5>
  					<p>Do you find yourself writing similar quotes/proposals over and over? Quote Builder makes it easy for your team.</p>
  				</div>

  				<div class="feature-block last">
  					<img src="<?php echo plugins_url('/i/invoices.png', ZBS_ROOTFILE); ?>">
  					<h5>Invoicing</h5>
  					<p>Got clients or people to bill? Easily create invoices, and get paid online (pro). Clients can see all Invoices in one place on the Client Portal.</p>
  				</div>

  				<div class="feature-block first">
  					<img src="<?php echo plugins_url('/i/transactions.png', ZBS_ROOTFILE); ?>">
  					<h5>Transactions</h5>
  					<p>Log transactions against contacts or companies, and reconcile to invoices. Track payments, ecommerce data, and LTV (lifetime value).</p>
  				</div>

  				<div class="feature-block last">
  					<img src="<?php echo plugins_url('/i/b2b.png', ZBS_ROOTFILE); ?>">
  					<h5>B2B Mode</h5>
  					<p>Manage leads working at Companies? B2B mode lets you group contacts under a Company and keep track of sales easier.</p>
  				</div>

  				<div class="feature-block first">
  					<img src="<?php echo plugins_url('/i/auto.png', ZBS_ROOTFILE); ?>">
  					<h5>Automations<span class='pro'>Entrepreneur</span></h5>
  					<p>Set up rule-based triggers and actions to automate your CRM work. Automatically Email new contacts, Distribute Leads, plus much more.</p>
  				</div>

  				<div class="feature-block last">
  					<img src="<?php echo plugins_url('/i/sms.png', ZBS_ROOTFILE); ?>">
  					<h5>Send SMS<span class='pro'>Entrepreneur</span></h5>
  					<p>Want to get in front of your customers, wherever they are? Send SMS messages to your contacts from their CRM record.</p>
  				</div>

  				<div class="feature-block first">
  					<img src="<?php echo plugins_url('/i/cpp.png', ZBS_ROOTFILE); ?>">
  					<h5>Client Portal Pro<span class='pro'>Entrepreneur</span></h5>
  					<p>Create a powerful 'client portal' in one click! Easily share files with clients via their contact record. Tweak the portal to fit your branding, and more!</p>
  				</div>

  				<div class="feature-block last">
  					<img src="<?php echo plugins_url('/i/mail.png', ZBS_ROOTFILE); ?>">
            <?php if($isv3){  ?>
  					  <h5>Mail Campaigns<span class='pro-elite'>Elite</span></h5>
            <?php }else{ ?>
  					  <h5>Mail Campaigns<span class='pro'>Entrepreneur</span></h5>
            <?php } ?>
  					<p>Send Email Broadcasts and Sequences to your CRM contacts using our <strong>powerful</strong> Mail Campaigns v2.0. which is linked directly into your CRM data!</p>
  				</div>

  		</div>

      <div class="clear"></div>

      <div class='zbs-button-wrap'>

          <a href="https://zerobscrm.com/features/" target="_blank" class='add-first-customer btn btn-hta'><?php _e("See All Features","zero-bs-crm");?></a>

      </div>


      <?php if (!$bundle){ ?>
      <div class="upgrade-cta upgrade">

					<div class="block">

						<div class="left">
							<h2>Upgrade to ENTREPRENEUR</h2>
							<ul>
								<li><span class="dashicons dashicons-yes"></span> PayPal Sync</li>
                <li><span class="dashicons dashicons-yes"></span> Invoicing Pro</li>
								<li><span class="dashicons dashicons-yes"></span> Stripe Sync</li>
                <li><span class="dashicons dashicons-yes"></span> Woo Sync</li>
								<li><span class="dashicons dashicons-yes"></span> User Registration</li>
								<li><span class="dashicons dashicons-yes"></span> Lead Capture</li>
								<li><span class="dashicons dashicons-yes"></span> Client Portal Pro</li>
								<li><span class="dashicons dashicons-yes"></span> Sales Dashboard</li>
								<li><span class="dashicons dashicons-yes"></span> Zapier</li>
                <li><span class="dashicons dashicons-yes"></span> Automations</li>
                <li style="width:100%"><span class="dashicons dashicons-yes"></span> Access to 25+ Extensions</li>
							</ul>
						</div>

						<div class="right">
							<h2><span>ENTREPRENEUR</span></h2>
							<div class="price">
								<span class="amount"><span class="dollar">$</span>199</span><br>
								<span class="term">per year</span>
							</div>
              <div class="zbs-button-wrap">
							<a href="http://bit.ly/2JuKSrY" rel="noopener noreferrer" target="_blank" class="btn btn-cta">
								Upgrade Now</a>
                <?php if($isv3){  ?>
                <div class='elite'>
                  <div class='go-elite'>or get Mail Campaigns too with our Elite package.. </div>
                  <a  class="elite-package" target="_blank" href="http://bit.ly/2JuKSrY">Elite Package</a>
                </div>
                <?php } ?>

              </div>
						</div>
            <div class="clear"></div>

					</div><!-- / .block -->

				</div><!-- / .upgrade-cta -->

        <div class="block" style="padding-bottom:0;">

						<h1>Testimonials</h1>

						<div class="testimonial-block">
							<img src="<?php echo plugins_url('/i/mb.jpg', ZBS_ROOTFILE); ?>">
							<p>My mind is blown away by how much attention has been placed on all the essential details built into ZeroBS CRM. It's a polished, professional product that I love being able to bake into my Website as a Service (WaaS), multisite network. It adds true value for my customers and completes my product offering. I've not been able to find any tool quite like it (and trust me, I've looked!) If you're looking to offer true value to your customers, this is worth its weight in gold...and that's zero BS! </p>
              <p class='who'><strong>Michal Short</strong>
            </div>

						<div class="testimonial-block">
							<img src="<?php echo plugins_url('/i/scribner.png', ZBS_ROOTFILE); ?>">
							<p>We can sit back and relax safe in the knowledge that Zero BS CRM is working tirelessly behind the scenes distributing leads automatically to our clients.</p>
              <p class='who'><strong>Dave Scribner</strong> 
          	</div>

				</div><!-- / .block -->

      </div><!-- / .intro.zbs-features -->

    </div><!-- / .container -->

    <div class="container final-block">
      <div class="block">
        <div class='zbs-button-wrap'>

            <a href="<?php echo $zbs->urls['upgrade']; ?>" target="_blank" class='upgrade-today btn btn-bta'><?php _e("Upgrade your CRM today","zero-bs-crm");?></a>
          
          <div class="clear"></div>
        </div>  
      </div>
    </div>

    <?php } else {

      // bundle owners:
      
      ?>
      </div><!-- / .intro.zbs-features -->

    </div><!-- / .container -->

    <div class="container final-block">
        <div class="block">
          <div class='zbs-button-wrap' style="padding-bottom:2em">

              <h4><?php _e('Your Account:','zero-bs-crm'); ?></h4>

              <a href="<?php echo zbsLink($zbs->slugs['extensions']); ?>" class='btn btn-bta'><?php _e("Manage Extensions","zero-bs-crm");?></a>
              <a href="<?php echo $zbs->urls['account']; ?>" target="_blank" class='btn btn-cta'><?php _e("Download Extensions","zero-bs-crm");?></a>
            
            <div class="clear"></div>
          </div>  
        </div>
      </div><?php

    } ?>

  </div><!-- / zbs-welcome -->

  <?php
}

#} DataTools HTML
#} Only exposed when a data tools plugin is installed:
#} - CSV Importer
function zeroBSCRM_html_datatools(){
  
  global $wpdb, $zbs;  #} Req 
  
  $deleting_data = false;

  if(current_user_can('manage_options')){

      // DELETE ALL DATA (Not Settings)
      if ( isset($_POST['zbs-delete-data']) && $_POST['zbs-delete-data'] == 'DO IT'){  
            $link = admin_url('admin.php?page=' . $zbs->slugs['datatools']);
            $str =  __("REMOVE ALL DATA", "zero-bs-crm");
            echo "<div class='ui segment' style='margin-right:20px;text-align:center;'>";

            echo "<h3>" . __('Delete all CRM data', 'zero-bs-crm') . "</h3>";

            echo "<div style='font-size:60px;margin:0.5em;'>⚠️</div>";
            echo "<p class='lead' style='font-size:16px;color:#999;padding-top:15px;'>";

              _e("This Administrator level utility will remove all data in your CRM. This cannot be undone. Proceed with caution.", "zero-bs-crm"); 

            echo "</p>";

            // 
            $del_link   =  $link . '&zbs-delete-data=1';
            $action = 'zbs_delete_data';
            $name   = 'zbs_delete_nonce';

            $nonce_del_link = wp_nonce_url( $del_link, $action, $name );
            echo "<a class='ui button red' href='" . $nonce_del_link ."'>" . $str . "</a>";

            echo "<a class='ui button green inverted' href='" . esc_url($link) . "'>" . __('CANCEL', 'zero-bs-crm') . "</a>";
            echo "</div>";
            $deleting_data = true;

      } else if ( isset( $_GET['zbs-delete-data'] ) && $_GET['zbs-delete-data'] == 1){

              //additional nonce check
              if (!isset($_GET['zbs_delete_nonce']) || !wp_verify_nonce($_GET['zbs_delete_nonce'], 'zbs_delete_data')) {

                   echo "<div class='ui segment' style='margin-right:20px;text-align:center;'>";
                  echo "<div class='ui message red' style='margin-right:20px;font-size:20px;'><i class='ui icon'></i>" . __("Data not deleted. Invalid permissions", "zero-bs-crm") . "</div>";
                echo "</div>";             


              }else{
                 echo "<div class='ui segment' style='margin-right:20px;text-align:center;'>";
                  echo "<div class='ui message green' style='margin-right:20px;font-size:20px;'><i class='ui icon check circle'></i>" . __("All CRM data deleted.", "zero-bs-crm") . "</div>";
                echo "</div>";                             

                //run the delete code
                zeroBSCRM_database_reset();


              }

       
      } 

      // DELETE ALL DATA (INCLUDING Settings)
      if ( isset($_POST['zbs-delete-all-data']) && $_POST['zbs-delete-all-data'] == 'FACTORY RESET'){  

            $link = admin_url('admin.php?page=' . $zbs->slugs['datatools']);
            $str =  __("REMOVE ALL DATA", "zero-bs-crm");
            echo "<div class='ui segment' style='margin-right:20px;text-align:center;'>";

            echo "<h3>" . __('Factory Reset CRM', 'zero-bs-crm') . "</h3>";

            echo "<div style='font-size:60px;margin:0.5em'>⚠️</div>";
            echo "<p class='lead' style='font-size:16px;color:#999;padding-top:15px;'>";

              _e("This Administrator level utility will remove all data in your CRM, including your CRM settings. This cannot be undone. Proceed with caution.", "zero-bs-crm"); 

            echo "</p>";

            // 
            $del_link   =  $link . '&zbs-delete-all-data=1';
            $action = 'zbs_delete_data';
            $name   = 'zbs_delete_nonce';

            $nonce_del_link = wp_nonce_url( $del_link, $action, $name );
            echo "<a class='ui button red' href='" . $nonce_del_link ."'>" . $str . "</a>";

            echo "<a class='ui button green inverted' href='" . esc_url($link) . "'>" . __('CANCEL', 'zero-bs-crm') . "</a>";
            echo "</div>";
            $deleting_data = true;

      } else if ( isset( $_GET['zbs-delete-all-data'] ) && $_GET['zbs-delete-all-data'] == 1){

              // additional nonce check
              if (!isset($_GET['zbs_delete_nonce']) || !wp_verify_nonce($_GET['zbs_delete_nonce'], 'zbs_delete_data')) {

                   echo "<div class='ui segment' style='margin-right:20px;text-align:center;'>";
                  echo "<div class='ui message red' style='margin-right:20px;font-size:20px;'><i class='ui icon'></i>" . __("Data not deleted. Invalid permissions", "zero-bs-crm") . "</div>";
                echo "</div>";             


              } else {
                 echo "<div class='ui segment' style='margin-right:20px;text-align:center;'>";
                  echo "<div class='ui message green' style='margin-right:20px;font-size:20px;'><i class='ui icon check circle'></i>" . __("CRM Factory Reset", "zero-bs-crm") . "</div>";
                echo "</div>";                             

                // run the delete code
                /*
                       ___________________    . , ; .
                      (___________________|~~~~~X.;' .
                                            ' `" ' `
                                  TNT

                */
                zeroBSCRM_database_nuke();

              }

       
      } 

  }

  if ( !$deleting_data ){ ?>
            
        <div id="zero-bs-tools" class="ui segment" style="margin-right:20px;">
          <h2 class="sbhomep"><?php _e("Welcome to","zero-bs-crm");?> Zero BS CRM <?php _e("Tools","zero-bs-crm");?></h2>
          <p class="sbhomep"><?php _e("This is the home for all of the different admin tools for ZBS which import data (Excluding the","zero-bs-crm");?> <strong><?php _e("Sync","zero-bs-crm");?></strong> <?php _e("Extensions","zero-bs-crm");?>).</p>
          <p class="sbhomep">
            <strong><?php _e("Free Data Tools","zero-bs-crm");?>:</strong><br />
        <?php if(!zeroBSCRM_isExtensionInstalled('csvpro')){ ?>
           <a class="ui button green primary" href="<?php echo admin_url('admin.php?page='.$zbs->slugs['csvlite']);?>"><?php _e("Import from CSV","zero-bs-crm");?></a>
        <?php } ;?>
      </p>
          <p class="sbhomep">
            <strong><?php _e("Data Tool Extensions Installed","zero-bs-crm");?>:</strong><br /><br />
              <?php 

                #} MVP
                $zbsDataToolsInstalled = 0; global $zeroBSCRM_CSVImporterslugs;
                if (zeroBSCRM_isExtensionInstalled('csvpro') && isset($zeroBSCRM_CSVImporterslugs)){

                  ?><button type="button" class="ui button primary" onclick="javascript:window.location='?page=<?php echo $zeroBSCRM_CSVImporterslugs['app']; ?>';" class="ui button primary" style="padding: 7px 16px;font-size: 16px;height: 46px;margin-bottom:8px;"><?php _e('CSV Importer',"zero-bs-crm"); ?></button><br /><?php
                  // tagger post v1.1 
                  if (isset($zeroBSCRM_CSVImporterslugs['tagger'])) { 
                    ?><button type="button" class="ui button primary" onclick="javascript:window.location='?page=<?php echo $zeroBSCRM_CSVImporterslugs['tagger']; ?>';" class="ui button primary" style="padding: 7px 16px;font-size: 16px;height: 46px;margin-bottom:8px;"><?php _e('CSV Tagger',"zero-bs-crm"); ?></button><br /><?php
                  }
                  $zbsDataToolsInstalled++;

                }

                if ($zbsDataToolsInstalled == 0){
                  ##WLREMOVE
                  ?><?php _e("You do not have any Pro ZBS Data Tools installed as of yet","zero-bs-crm");?>! <a href="<?php echo $zbs->urls['productsdatatools']; ?>" target="_blank"><?php _e("Get some now","zero-bs-crm");?></a><?php
                  ##/WLREMOVE
                }

              ?>              
            </p><p class="sbhomep">
              <!-- #datatoolsales -->
            <strong><?php _e("Import Tools","zero-bs-crm");?>:</strong><br /><br />             
              <a href="<?php echo $zbs->urls['productsdatatools']; ?>" target="_blank" class="ui button primary"><?php _e('View Available ZBS Import Tools',"zero-bs-crm"); ?></a>              
            </p>
            <div class="sbhomep">
              <strong><?php _e("Export Tools","zero-bs-crm");?>:</strong><br/>
              <p><?php _e('Want to use the refined object exporter? ',"zero-bs-crm"); ?></p>
              <p><a class="ui button" href="<?php echo admin_url('admin.php?page='.$zbs->slugs['zbs-export-tools'].'&zbswhat=contacts'); ?>">Export Tools</a></p>
            </div>
    </div>
    <div class="ui grid">
    <div class="eight wide column">
      
        <div class="ui segment" style="margin-right:20px;">
          <div class='mass-delete' style="text-align:center;">
              <h4 style="font-weight:900;"><?php _e("Delete CRM Data", "zero-bs-crm"); ?></h4>
              <p>
                <?php $str = __("To remove all CRM data (e.g. contacts, transactions etc.), type", "zero-bs-crm") . " 'DO IT' " . __(" in the box below and click 'Delete All Data'.", "zero-bs-crm"); ?>
                <?php echo $str; ?>
              </p>
              <div class="zbs-delete-box" style="max-width:70%;margin:auto;">
                <p class='ui message warning'>
                    <i class='ui icon exclamation'></i><b> <?php _e("Warning: This can not be undone", "zero-bs-crm"); ?></b>
                </p>
                <form id="reset-data" class="ui form" action="#" method="POST">
                  <input class="form-control" id="zbs-delete-data" name="zbs-delete-data" type="text" value="" placeholder="DO IT" style="text-align:center;font-size:25px;"/>
                  <input type="submit" class="ui button red" value="<?php _e("DELETE ALL DATA","zero-bs-crm") ;?>" style="margin-top:10px;"/>
                </form>
              </div>            
          </div>
        </div>

    </div>
    <div class="eight wide column">
      
        <div class="ui segment" style="margin-right:20px;">
          <div class='mass-delete' style="text-align:center;">
              <h4 style="font-weight:900;"><?php _e("Factory Reset CRM", "zero-bs-crm"); ?></h4>
              <p>
                <?php $str = __("To remove all CRM data (e.g. contacts, transactions etc.) AND all settings, type", "zero-bs-crm") . " 'FACTORY RESET' " . __(" in the box below and click 'Reset CRM'.", "zero-bs-crm"); ?>
                <?php echo $str; ?>
              </p>
              <div class="zbs-delete-box" style="max-width:70%;margin:auto;">
                <p class='ui message warning'>
                    <i class='ui icon exclamation'></i><b> <?php _e("Warning: This can not be undone", "zero-bs-crm"); ?></b>
                </p>
                <form id="reset-data" class="ui form" action="#" method="POST">
                  <input class="form-control" id="zbs-delete-all-data" name="zbs-delete-all-data" type="text" value="" placeholder="FACTORY RESET" style="text-align:center;font-size:25px;"/>
                  <input type="submit" class="ui button red" value="<?php _e("Reset CRM","zero-bs-crm") ;?>" style="margin-top:10px;"/>
                </form>
              </div>          
          </div>
        </div>

    </div>
  </div>
    
    
    
    <?php 

              }

}


#} Install Extensions helper page
function zeroBSCRM_html_installextensionshelper(){
  
  global $wpdb, $zbs;  #} Req 
  
  #} 27th Feb 2019 - MS pimp this page a little - but WL remove the salesy bit. bring into semantic UI properly too
  ?>
          <style>
            .intro{
              font-size:18px !important;;
              font-weight:200;
              line-height:20px;
              margin-bottom:10px;
              margin-top:20px;
            }
            .zbs-admin-segment-center{
              text-align:center;
            }
            h2{
              font-weight:900;
              padding-bottom:30px;
            }
            .intro-buttons{
              padding:20px;
            }
          </style>
          <div class="ui segment zbs-admin-segment-center" style="margin-right:15px;">
  <?php
          ##WLREMOVE
            zeroBSCRM_extension_installer_promo();
          ##/WLREMOVE    
  ?>
          <h2><?php _e("Installing Extensions for","zero-bs-crm");?> Zero BS CRM</h2>
          <p class="intro"><?php _e("To control which modules and extensions are active, please go the the ","zero-bs-crm");?> <a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['extensions']); ?>"><?php _e("Extension Manager","zero-bs-crm");?></a>.</p>
          <p class="intro"><?php _e("To install premium extensions, purchased in a bundle or individually please go to","zero-bs-crm");?> <a href="<?php echo admin_url('plugins.php'); ?>"><?php _e("Plugins","zero-bs-crm");?></a> <?php _e("and add your new extensions there.","zero-bs-crm");?></p>
          <p class="intro-buttons">
            <a href="<?php echo admin_url('plugins.php'); ?>" class="ui button primary"><i class="fa fa-plug" aria-hidden="true"></i> <?php _e("Upload Purchased Extensions","zero-bs-crm");?></a>    
            <a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['extensions']); ?>" class="ui button green"><i class="fa fa-search" aria-hidden="true"></i> <?php _e("Browse Extensions and Modules","zero-bs-crm");?></a>    
            </p>
    </div>
    
  <?php 

}

function zeroBSCRM_extension_installer_promo(){
  //extra function here to output additional bullie type stuff.
  ?>
  <div class="bullie">
    <?php $bullie = zeroBSCRM_getBullie(); ?>
    <img src="<?php echo $bullie; ?>" alt="Bullie the ZBS mascot">
  </div>
  <?php
}

#} Feedback page
function zeroBSCRM_html_feedback(){
  
  global $wpdb, $zbs;  #} Req

    if (zeroBSCRM_isWL()){  #WL - leave old ?>
        <div id="sbSubPage" style="width:600px"><h2 class="sbhomep"><?php _e("Feedback Makes","zero-bs-crm");?> Zero BS CRM <?php _e("better","zero-bs-crm");?></h2>
          <p class="sbhomep"><?php _e("We love to hear what you think of","zero-bs-crm");?> Zero BS CRM, <?php _e("it helps us make the CRM even better, even if you're hitting a wall with something, (it's useful so long as it's constructive critisism!)","zero-bs-crm");?></p>
          <p class="sbhomep"><?php _e("If you have a feature you'd like to see, a bug you may have found, or you'd like to suggest an idea for an extension, let us know below:","zero-bs-crm");?></p>
          <p class="sbhomep">
            <a href="<?php echo $zbs->urls['feedback']; ?>" class="ui button primary"><i class="fa fa-envelope-o" aria-hidden="true"></i> <?php _e("Send Feedback","zero-bs-crm");?></a>    
          </p>
    <?php } else { #ZBS ?>
        <div id="sbSubPage" style="width:800px"><h2 class="sbhomep"><?php _e("Feedback Makes","zero-bs-crm");?> Zero BS CRM <?php _e("better","zero-bs-crm");?></h2>
          <p class="sbhomep"><?php _e("We love to hear what you think of","zero-bs-crm");?> Zero BS CRM, <?php _e("it helps us make the CRM even better, even if you're hitting a wall with something, (it's useful if it's constructive critisism!)","zero-bs-crm");?></p>
          <p class="sbhomep"><?php _e("Please use the","zero-bs-crm");?> <a href="<?php echo $zbs->urls['feedbackform']; ?>" target="_blank"><?php _e('following form',"zero-bs-crm"); ?></a> <?php _e("to give feedback:","zero-bs-crm");?></p>
          <div style="text-align:center"><a href="<?php echo $zbs->urls['feedbackform']; ?>" target="_blank"><img src="<?php echo ZEROBSCRM_URL.'i/give-feedback.png'; ?>" alt="Give Feedback" /></a></div>
    <?php } ?>
          <?php ##WLREMOVE ?>
              <p class="sbhomep"><?php _e("What not to send through here:","zero-bs-crm");?>
              <ul class="sbhomep">
                <li><?php _e("Documentation requests","zero-bs-crm");?> (<a href="<?php echo $zbs->urls['docs']; ?>"><?php _e("Click here","zero-bs-crm");?></a> <?php _e("for that","zero-bs-crm");?>)</li>
                <li><?php _e("Support requests","zero-bs-crm");?> (<a href="<?php echo $zbs->urls['feedback']; ?>"><?php _e("Click here","zero-bs-crm");?></a> <?php _e("for that","zero-bs-crm");?>)</li>
              </ul>
              </p>
          <?php ##/WLREMOVE ?>
    </div><?php 

}

function zeroBSCRM_html_extensions_forWelcomeWizard(){

  global $wpdb, $zbs;  #} Req ?>



            
        <div id="sbSubPage" style="width:100%;max-width:1000px"><h2 class="sbhomep"><?php _e("Power Up your CRM","zero-bs-crm");?></h2>
          <p class="sbhomep"><?php _e("We hope that you love using ZBS and that you agree with our mentality of stripping out useless features and keeping things simple. Cool.","zero-bs-crm");?></p>
          <p class="sbhomep"><?php _e("We offer a few extensions which supercharge your ZBS. As is our principle, though, you wont find any bloated products here. These are simple, effective power ups for your ZBS. And compared to pay-monthly costs, they're affordable! Win!","zero-bs-crm");?></p>
          <div style="width:100%"><a href="<?php echo $zbs->urls['products']; ?>" target="_blank"><img style="width:100%;max-width:100%;margin-left:auto;margin-right:auto;" src="<?php echo $zbs->urls['extimgrepo'].'extensions.png'; ?>" alt="" /></a></div>
            <p class="sbhomep">
            <a href="<?php echo $zbs->urls['products']; ?>" class="ui button primary" style="padding: 7px 16px;font-size: 22px;height: 46px;" target="_blank"><?php _e("View More","zero-bs-crm");?></a>    
          </p>
    </div><?php 
  

}

#} helper for extension page (installs/uninstalls at init)
function zeroBSCRM_extensions_init_install(){

    #} Anything to install/uninstall?
    if (isset($_GET['zbsinstall']) && !empty($_GET['zbsinstall'])){

      #} this is passed to extensions page
      global $zeroBSExtensionMsgs;


      #} Validate
      global $zeroBSCRM_extensionsCompleteList;
      if (
        #} Nonce
        wp_verify_nonce( $_GET['_wpnonce'], 'zbscrminstallnonce' )
        &&
        #} Ext exists
        array_key_exists($_GET['zbsinstall'], $zeroBSCRM_extensionsCompleteList)){

        #} Act on $_GET['zbsinstall']
        $toActOn = sanitize_text_field($_GET['zbsinstall']);
        $installingExt = zeroBSCRM_returnExtensionDetails($toActOn);
        $installName = 'Unknown'; if (isset($installingExt['name'])) $installName = $installingExt['name'];

        #} Action
        $act = 'install'; if (zeroBSCRM_isExtensionInstalled($toActOn)) $act = 'uninstall';
        $successfullyInstalled = false;

        #} Try it
        try {

          if ($act == 'install'){

            #} INSTALL

            #} If install func exists
            if (function_exists('zeroBSCRM_extension_install_'.$toActOn)){

              #} try it (returns bool)
              $successfullyInstalled = call_user_func('zeroBSCRM_extension_install_'.$toActOn);

            }

          } else {

            #} UNINSTALL

            #} If install func exists
            if (function_exists('zeroBSCRM_extension_uninstall_'.$toActOn)){

              #} try it (returns bool)
              $successfullyInstalled = call_user_func('zeroBSCRM_extension_uninstall_'.$toActOn);

            }

          }

        } catch (Exception $ex){

          # meh

        }

        #} pass it on
        $zeroBSExtensionMsgs = array($successfullyInstalled,$installName,$act);


      }
    }
}




function zeroBSCRM_html_extensions(){
  
  //globals 
  global $zbs, $zeroBSExtensionMsgs, $zeroBSCRM_extensionsInstalledList;

  // new design - for the fact we are adding new extensions all the time and now won't need to
  // keep on remembering to update this array and it will keep up to date. Also with things 
  // like livestorm "connect" needed an on the flyfix.

  // WH added: Is now polite to License-key based settings like 'entrepreneur' doesn't try and upsell
  // this might be a bit easy to "hack out" hmmmm
  $bundle = false; if ($zbs->hasEntrepreneurBundleMin()) $bundle = true;

  echo "<div class='zbs-extensions-manager' style='margin-top:1em'>";

  #} Install msg
  if (isset($zeroBSExtensionMsgs)){

    echo '<div class="zbs-page-wrap install-message-list">';

    if ($zeroBSExtensionMsgs[0]){

      $msgHTML = '<i class="fa fa-check" aria-hidden="true"></i> Successfully '.$zeroBSExtensionMsgs[2].'ed extension: '.$zeroBSExtensionMsgs[1];

      // if API, catch and give further info (e.g. no key)
      if ($zeroBSExtensionMsgs[2] == 'install' && $zeroBSExtensionMsgs[1] == 'API'){

          // installed API
            // get if set
            $api_key = zeroBSCRM_getAPIKey();
            $api_secret = zeroBSCRM_getAPISecret();
            //$endpoint_url = zeroBSCRM_getAPIEndpoint(); 
            if (empty($api_key)){

              // assume no keys yet, tell em
              $msgHTML .= '<hr />'.__('You can now generate API Keys and send data into your CRM via API:','zero-bs-crm').'<p style="padding:1em"><a href="'.zbsLink($zbs->slugs['settings']).'&tab=api" class="ui button green">'.__('Generate API Keys','zero-bs-crm').'</a></p>';

            }


      }

      #} Show a help url if present
      if ($zeroBSExtensionMsgs[2] == 'install' && isset($installingExt) && isset($installingExt['meta']) && isset($installingExt['meta']['helpurl']) && !empty($installingExt['meta']['helpurl'])){

        $msgHTML .= '<br /><i class="fa fa-info-circle" aria-hidden="true"></i> <a href="'.$installingExt['meta']['helpurl'].'" target="_blank">View '.$zeroBSExtensionMsgs[1].' Help Documentation</a>';

      }
        
      echo zeroBSCRM_html_msg(0,$msgHTML);

    } else {

      global $zbsExtensionInstallError, $zbs;

      $errmsg = 'Unable to install extension: '.$zeroBSExtensionMsgs[1].', please contact <a href="'.$zbs->urls['support'].'" target="_blank">Support</a> if this persists.';

      if (isset($zbsExtensionInstallError)) $errmsg .= '<br />Installer Error: '.$zbsExtensionInstallError;


      echo zeroBSCRM_html_msg(-1,$errmsg);

    }

    echo '</div>';

  }

  //get the products, from our sites JSON custom REST endpoint - that way only need to manage there and not remember to update all the time
  //each product has our extkey so can do the same as the built in array here ;) #progress #woop-da-woop
  if(isset($_GET['extension_id']) && !empty($_GET['extension_id'])){
    ##WLREMOVE
          echo '<div class="zbs-page-wrap thinner" id="error-stuff">';
            $id = (int)sanitize_text_field($_GET['extension_id']);
            $request = wp_safe_remote_get( 'https://zerobscrm.com/wp-json/zbsextensions/v1/extensions/' . $id );

            if ( is_wp_error( $request ) ) {

            echo '<div class="zbs-page-wrap">';
                echo '<div class="ui message alert warning" style="display:block;margin-bottom: -25px;"><i class="wifi icon"></i> ';
                  _e("You must be connected to the internet to view our live extensions page.", "zero-bs-crm");
              echo '</div>';
            echo '</div>';

              return false;
            } 

            $body = wp_remote_retrieve_body( $request );
            $extension = json_decode( $body );
            $info = $extension->product;

            if($info == 'error'){
                echo '<div class="zbs-page-wrap">';
                  echo '<div class="ui message alert error" style="display:block;margin-bottom: -25px;"><i class="exclamation icon"></i> ';
                    _e("Product does not exist.", "zero-bs-crm");
                    echo ' <a href="'   .  esc_url(admin_url('admin.php?page=' . $zbs->slugs['extensions']))   .  '">' . __('Go Back', 'zero-bs-crm') . '</a>';
                echo '</div>';
              echo '</div>';
              return false;
            }
          echo '</div>';  
          //end of #error-stuff

          echo '<div class="zbs-page-wrap thinner single-info-start">';
          
            echo '<div class="ui segment main-header-img">';
              echo '<div class="back">';
                echo '<a href="'   .  esc_url(admin_url('admin.php?page=' . $zbs->slugs['extensions']))   .  '"><i class="chevron left icon"></i> ' . __('Back', 'zero-bs-crm') . '</a>';
              echo '</div>';

              echo '<div class="main-image full-size-image">';
                echo '<img src="' . $info->image .  '"/>';
              echo '</div>';

              echo '<div class="below-main-image about-author-block">';
                  //start the about block
                  echo '<div class="about-img"><img src="' . $info->by . '"/></a>';
                    echo '<div class="top-info-block">';
                    echo '<h4 class="extension-name">' . $info->name . '</h4>';
                    echo '<div class="who">'. __('by ', 'zero-bs-crm') .'<a class="by-url" href="' . $zbs->urls['home']   .  '" target="_blank">' . __('Zero BS CRM', 'zero-bs-crm') . '</a></div>';            
                    echo '</div>';
                  echo '</div>';
                  //end the about block

                  //action block (installed / not)
                  $extkey = $info->extkey;
                  $installed = zeroBSCRM_isExtensionInstalled($extkey);
                  $docs = $info->docs;
                  echo '<div class="actions-block"><div class="install-ext">';
                    if($installed){
                        echo '<span class="ui label green large"><i class="check circle icon"></i> ' . __('Installed', 'zero-bs-crm') . '</span>';
                    }else{
                        echo '<a href="'.$zbs->urls['checkoutprefix'].$extkey.'" class = "ui blue button" target="_blank"><i class="cart icon"></i> ' . __('Buy', 'zero-bs-crm') . '</a>';
                        echo '<a href="'.$zbs->urls['howtoinstall'].'" class = "ui button" target="_blank"><i class="download icon"></i> ' . __('Install', 'zero-bs-crm') . '</a>';
                    }
                    if($docs != ''){
                      echo '<a class="docs-url" href="'. esc_url($docs) .'" target="_blank">' . __('View Docs', 'zero-bs-crm') . '</a>';
                    }
                    echo '</div>';
                  echo '</div>'; 
                  //end action block
              echo '</div>';  
              //end the about-author-block
              
              echo '<div class="clear"></div>'; // clear stuff

            echo '</div>';  //end the whole header image block



          echo '</div>';  
          //end the start of the info block (top block)

          echo '<div class="zbs-page-wrap thinner single-bundle-wrap">';
            if(!$bundle){
              echo '<div class="bullie-wrap">';
                echo '<div class="bullie">';
                  $bullie = zeroBSCRM_getBullie(); 
                  echo '<img src="'. $bullie . '" alt="Bullie the ZBS mascot">';
                  echo '<div class="upgrade">' . __('Upgrade to our bundle to get access to all CRM extensions', 'zero-bs-crm') . '</div>';
                  echo '<a class = "ui button green mini upgrade-bullie-box" href="https://zerobscrm.com/extension-bundles/" target = "_blank"><i class="cart plus icon"></i> ' . __('Upgrade Now', 'zero-bs-crm') . '</a>';
                echo '</div>';
              echo '</div>';
              echo '<div class="clear"></div>';
            }  
          echo '</div>';

          echo '<div class="zbs-page-wrap thinner" id="single-ext-desc">';
            echo '<div class="ui segment main-talk">';
              echo '<div class="extension-description">';

                  // semantic ui switch html from bootstrap ones (grids basically)
                  $desc = str_replace('class="row"', 'class="ui grid"', $info->description);
                  $desc = str_replace(' row"', ' ui grid"', $desc);
                  $desc = str_replace('col-md-6', 'eight wide column', $desc);
                  $desc = str_replace('col-sm-8', 'ten wide column', $desc);
                  $desc = str_replace('col-lg-1', '', $desc);
                  $desc = str_replace('col-lg-2', 'four wide column', $desc);

                  echo $desc;
              echo '</div>';
              // buy
              if(!$installed) echo '<hr /><div style="margin:2em;text-align:center"><a href="'.$zbs->urls['checkoutprefix'].$extkey.'" class = "ui large blue button" target="_blank"><i class="cart icon"></i> ' . __('Buy', 'zero-bs-crm') . ' ' . __('Extension', 'zero-bs-crm') . '</a></div>';                    
            echo '</div>';
          echo "</div>"; 
          //id="single-ext-desc"

    ##/WLREMOVE
  }else{

        ##WLREMOVE
          $showLinkButton = true;

          //get the JSON response from woocommerce REST endpoint.
          $request = wp_safe_remote_get( $zbs->urls['checkoutapi'] );
          if ( is_wp_error( $request ) ) {
              //if there's an error, server the JSON in the function 
            $extensions = json_decode(zeroBSCRM_serve_cached_extension_block());
            echo '<div class="zbs-page-wrap">';
                echo '<div class="ui message alert warning" style="display:block;margin-bottom: -25px;"><i class="wifi icon"></i> ';
                  _e("You must be connected to the internet to view our live extensions page. You are being shown an offline version.", "zero-bs-crm");
              echo '</div>';
            echo '</div>';
            $showLinkButton = false;
          }else{
            $body = wp_remote_retrieve_body( $request );
            $extensions = json_decode( $body );
          }
          echo '<div class="zbs-page-wrap">';
            if(!$bundle){
            echo '<div class="bullie-wrap">';
              echo '<div class="bullie">';
                $bullie = zeroBSCRM_getBullie(); 
                echo '<img src="'. $bullie . '" alt="Bullie the ZBS mascot">';
                echo '<div class="upgrade">' . __('Upgrade to our Entrepreneur Bundle or higher to get access to all CRM extensions and save.', 'zero-bs-crm') . '</div>';
                echo '<a class = "ui button green mini upgrade-bullie-box" href="https://zerobscrm.com/extension-bundles/" target = "_blank"><i class="cart plus icon"></i> ' . __('Upgrade Now', 'zero-bs-crm') . '</a>';
              echo '</div>';
            echo '</div>';
            echo '<div class="clear"></div>';
            }  
            echo '<div class="ui top attached header premium-box"><h3 class="box-title">' . __('Premium Extensions', 'zero-bs-crm').'</h3>   <a class="guides ui button blue mini" href="'.  $zbs->urls['docs']  .'" target="_blank"><i class="book icon"></i> '. __('Knowledge-base', 'zero-bs-crm')  .'</a> <a class="guides ui button blue basic mini" href="#core-modules"><i class="puzzle piece icon"></i> '. __('Core Extensions', 'zero-bs-crm')  .'</a>   </div>';
            echo '<div class="clear"></div>';
            echo '<div class="ui segment attached">';
              echo '<div class="ui internally celled grid">';

              $e = 0; $count = 0; $idsToHide = array(17121,17119);
              foreach($extensions->paid as $extension){

                // hide bundles
                if (!in_array($extension->id, $idsToHide)){

                    $more_url = admin_url('admin.php?page=' . $zbs->slugs['extensions'] . '&extension_id=' . $extension->id);

                    $extkey = $extension->extkey;
                    $installed = zeroBSCRM_isExtensionInstalled($extkey);
                    if($e == 0){
                      echo '<div class="row">';
                    }

                    echo "<div class='two wide column'>";
                      echo "<img src='" . $extension->image  ."'/>";
                    echo "</div>";

                    echo "<div class='six wide column ext-desc'>";
                      if($installed){
                        echo '<div class="ui green right corner label"><i class="check icon"></i></div>';
                      }
                      echo "<div class='title'>" . $extension->name . '</div>';
                      echo "<div class='content'>" . $extension->short_desc  . '</div>';

                      if($showLinkButton){
                        echo '<div class="hover"></div><div class="hover-link">';

                        // api connector skips these
                        if ($extkey == 'apiconnector'){

                            // api connector
                            
                              // view
                              echo "<a href='". esc_url($zbs->urls['apiconnectorsales'])  ."' target='_blank'><button class='ui button orange mini'>" . __('View', 'zero-bs-crm') . "</button></a>";                      
                              
                              // download or buy
                              if ($bundle)
                                echo "<a href='". esc_url($zbs->urls['account'])  ."' target='_blank'><button class='ui button green mini'>" . __('Download', 'zero-bs-crm') . "</button></a>";
                              else
                                echo "<a href='". esc_url($zbs->urls['checkoutprefix'].$extkey)  ."' target='_blank'><button class='ui button green mini'>" . __('Buy', 'zero-bs-crm') . "</button></a>";

                          } else {

                            // non api connector
                            echo "<a href='". esc_url($more_url)  ."'><button class='ui button orange mini'>" . __('View', 'zero-bs-crm') . "</button></a>";
                            
                            if (!$installed){
                              
                              if ($bundle)
                                echo "<a href='". esc_url($zbs->urls['account'])  ."' target='_blank'><button class='ui button green mini'>" . __('Download', 'zero-bs-crm') . "</button></a>";
                              else
                                echo "<a href='". esc_url($zbs->urls['checkoutprefix'].$extkey)  ."' target='_blank'><button class='ui button green mini'>" . __('Buy', 'zero-bs-crm') . "</button></a>";

                            } else
                              if (isset($extension->docs) && !empty($extension->docs)) echo "<a href='". esc_url($extension->docs)  ."' target='_blank'><button class='ui button blue mini'>" . __('Docs', 'zero-bs-crm') . "</button></a>";
                          }
                        echo '</div>';
                      }

                    echo "</div>";

              
                    $e++;
                    $count++;
                    if($e > 1){
                      echo '</div>';
                      $e = 0;
                    }


                  } // / if not hidde

              }

                //add on the coming soon block     
                if ($e == 1){

                    // End of row

                      echo "<div class='two wide column'>";
                        echo "<img src='" . plugins_url('i/soon.png', ZBS_ROOTFILE)  ."'/>";
                      echo "</div>";

                      echo "<div class='six wide column ext-desc'>";
                        echo "<div class='title'>" . __('Coming soon', 'zero-bs-crm'). '</div>';
                        echo "<div class='content'>" . __('See and vote for what extensions we release next')  . '</div>';
                  
                        echo '<div class="hover"></div>';
                        echo "<a class='hover-link' href='". esc_url($zbs->urls['soon'])  ."' target='_blank'><button class='ui button orange mini'>" . __('View', 'zero-bs-crm') . "</button></a>";
                      echo "</div>";

                } else {

                  // Row to itself

                    echo '<div class="row">';

                    echo "<div class='two wide column'>";
                      echo "<img src='" . plugins_url('i/soon.png' , ZBS_ROOTFILE)  ."'/>";
                    echo "</div>";

                    echo "<div class='six wide column ext-desc'>";;
                      echo "<div class='title'>" . __('Coming soon', 'zero-bs-crm'). '</div>';
                      echo "<div class='content'>" . __('See and vote for what extensions we release next')  . '</div>';
              
                      echo '<div class="hover"></div>';
                      echo "<a class='hover-link' href='". esc_url($zbs->urls['soon'])  ."' target='_blank'><button class='ui button orange mini'>" . __('View', 'zero-bs-crm') . "</button></a>";
                    echo "</div>";

                }

                // coming soon end row
                echo '</div>'; //end the row (as it will be adding on)

              echo '</div>';
            echo '</div>';
          echo '</div>';  //end page wrap.

        ##/WLREMOVE

        //this block should be in here for rebranded people who want to turn on or off features.
        echo '<div class="zbs-page-wrap free-block-wrap">';
          echo '<h3 class="ui top attached header free-box" id="core-modules">' . __('Core Modules', 'zero-bs-crm').'</h3>';
          echo '<div class="ui segment attached free-ext-area">';
            echo '<div class="ui internally celled grid">';
              
              //output the free stuff :-) with turn on / off.
              $e = 0;
              foreach(zeroBSCRM_extensions_free() as $k => $v){

                if (is_array($v)){

                    $modify_url = wp_nonce_url('admin.php?page='.$zbs->slugs['extensions'].'&zbsinstall='.$k,'zbscrminstallnonce');

                    $installed = zeroBSCRM_isExtensionInstalled($k);
                    if($e == 0){
                      echo '<div class="row">';
                    }

                    echo "<div class='two wide column free-ext-img'>";
                      echo "<img src='" .  plugins_url('i/' . $v['i'], ZBS_ROOTFILE)  ."'/>";
                    echo "</div>";

                    echo "<div class='six wide column ext-desc'>";
                      $amend = __('Activate', 'zero-bs-crm');
                      $amend_color = 'green';
                      if($installed){
                        echo '<div class="ui green right corner label"><i class="check icon"></i></div>';
                        $amend = __('Deactivate', 'zero-bs-crm');
                        $amend_color = 'red';
                      }else{
                        echo '<div class="ui red right corner label"><i class="times icon"></i></div>';                  
                      }
                      echo "<div class='title'>" . $v['name'] . '</div>';
                      echo "<div class='content'>" . $v['short_desc']  . '</div>';

                      echo '<div class="hover"></div>';
                      echo "<a class='hover-link' href='". esc_url($modify_url)  ."'><button class='ui button ". $amend_color  ." mini'>" . $amend . "</button></a>";



                    echo "</div>";

                    $e++;
                    if($e > 1){
                      echo '</div>';
                      $e = 0;
                    }


                  } // / if is array (csvimporterlite = false so won't show here)


              } // /foreach 
          
            echo '</div>';
          echo '</div>';
        echo '</div>'; 



  }  


  echo "</div>";

}


/*

#} extensions page - v2.98 the "OLD" version before MS changes. Can remove it once above is in core.
function zeroBSCRM_html_extensions_old(){

  global $wpdb, $zbs;  #} Req

    global $zeroBSExtensionMsgs;

    #} Check pdf dom install
    zeroBSCRM_extension_checkinstall_pdfinv();

    #} hackaround
    zeroBSCRM_freeExtensionsInit();

    #} What's installed? (after any updates above)
    $extensionList = zeroBSCRM_extensionsListSegmented();

  ?>
            
        <div id="zbsExtensionsPage" style="margin-top:1em">

          <h2 class="sbhomep" style="font-size:28px;padding: 18px 0;"><i class="fa fa-plus-circle" aria-hidden="true"></i> <?php _e("Power Up your CRM","zero-bs-crm");?></h2>
          <div class="sbhomep">
            <p style="font-size:15px;"><?php _e("Welcome to the","zero-bs-crm");?> <strong>Zero BS CRM <?php _e("Extension Hub","zero-bs-crm");?></strong>. <?php _e("From this page you can quickly and easily install extra extensions that will supercharge your CRM","zero-bs-crm");?>.</p>
            <p style="text-align:Center;margin-top:2em;margin-bottom:2em">
              <a href="<?php echo $zbs->urls['products']; ?>?utm_content=inplugin_exthub_top" class="button button-large" style="font-size: 26px;padding: 12px;line-height: 1em;height: auto;background:orange;color:#000"><i class="fa fa-shopping-cart" aria-hidden="true" style="color:#FFF"></i> <?php _e("Shop Extensions","zero-bs-crm");?></a>
            </p>
            <p>
              <?php _e("Once purchased, Zero BS CRM extensions are installed like any other WordPress plugin. Head over to","zero-bs-crm");?> <a href="<?php echo admin_url("plugins.php");?>"><?php _e("here","zero-bs-crm");?></a> <?php _e("to install your premium extensions. Choose 'Add New' and install your extensions","zero-bs-crm");?>.
            </p>
            <p><b><?php _e("Extension updates","zero-bs-crm");?></b> <?php _e("when we update an extension you will be notified about any which require an update","zero-bs-crm");?></p>
          </div>


          <?php

          #} Install msg
          if (isset($zeroBSExtensionMsgs)){

            if ($zeroBSExtensionMsgs[0]){

              $msgHTML = '<i class="fa fa-check" aria-hidden="true"></i> Successfully '.$zeroBSExtensionMsgs[2].'ed extension: '.$zeroBSExtensionMsgs[1];

              #} Show a help url if present
              if ($zeroBSExtensionMsgs[2] == 'install' && isset($installingExt) && isset($installingExt['meta']) && isset($installingExt['meta']['helpurl']) && !empty($installingExt['meta']['helpurl'])){

                $msgHTML .= '<br /><i class="fa fa-info-circle" aria-hidden="true"></i> <a href="'.$installingExt['meta']['helpurl'].'" target="_blank">View '.$zeroBSExtensionMsgs[1].' Help Documentation</a>';

              }
                
              echo zeroBSCRM_html_msg(0,$msgHTML);

            } else {

              global $zbsExtensionInstallError, $zbs;

              $errmsg = 'Unable to install extension: '.$zeroBSExtensionMsgs[1].', please contact <a href="'.$zbs->urls['support'].'" target="_blank">Support</a> if this persists.';

              if (isset($zbsExtensionInstallError)) $errmsg .= '<br />Installer Error: '.$zbsExtensionInstallError;


              echo zeroBSCRM_html_msg(-1,$errmsg);

            }

          }

          ?>

          <div class="zbsExtensionGroup" id="free-extensions-tour-meister">
            <h2 id="free-extensions-tour"><?php _e("Free extensions","zero-bs-crm"); ?></h2>
            <p><?php _e("We've made a few extensions that are totally free, and can be installed with one click below. These are created as extras to the main core because not everyone wants the specific functionality","zero-bs-crm");?>.</p>
            <div class="zbsExtensionList">
              <?php if (count($extensionList['free']) > 0) foreach ($extensionList['free'] as $extKey => $ext){ 

                #} if need a short name... use that
                $shortName = $ext['name']; if (isset($ext['meta']) && isset($ext['meta']['shortname'])) $shortName = $ext['meta']['shortname'];

                #} Pro ver installed?
                $proVerInstalled = false; if (isset($ext['meta']) && isset($ext['meta']['prover']) && zeroBSCRM_isExtensionInstalled($ext['meta']['prover'])) $proVerInstalled = true;

              ?>
              <div class="zbsExtension zbsFreeExtension">
                <div class="zbsExtensionImg"<?php if (isset($ext['meta']['colour'])) echo ' style="background:'.$ext['meta']['colour'].' !important;"'; ?>>
                  <?php echo $ext['meta']['imgstr']; ?>
                </div>
                <h3><?php echo $ext['name']; ?></h3>
                <p><?php _e($ext['meta']['desc'],"zero-bs-crm"); ?></p>
                <div class="zbsExtensionButton"><?php
                if ($proVerInstalled){
                  ?><label style="color:green"><?php _e('PRO version installed.',"zero-bs-crm"); ?></label><?php 
                } else { ?>
                <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo wp_nonce_url('admin.php?page='.$zbs->slugs['extensions'].'&zbsinstall='.$extKey,'zbscrminstallnonce'); ?>';"><?php if ($ext['installed'])  _e('Deactivate',"zero-bs-crm"); else _e('Install',"zero-bs-crm"); ?> <?php echo $shortName; ?></button></div>
                <?php } ?>
              </div>
              <?php } ?>
            </div>
            <div style="clear:both"></div>
          </div>



          <div class="zbsExtensionGroup">
            <h2 id="paid-extensions-tour"><?php _e("Paid Extensions","zero-bs-crm");?></h2>
            <?php if (!zeroBSCRM_isWL()){ ##WLREMOVE ?>
              <p><?php _e("Want to get the absolute most out of your","zero-bs-crm");?> Zero BS CRM? <?php _e("These paid extensions are","zero-bs-crm");?> <em><?php _e("Next Level","zero-bs-crm");?></em>:</p>
              <div class="zbsExtensionList">
                <?php if (count($extensionList['paid']) > 0) foreach ($extensionList['paid'] as $extKey => $ext){ 

                  #} if need a short name... use that
                  $shortName = $ext['name']; if (isset($ext['meta']) && isset($ext['meta']['shortname'])) $shortName = $ext['meta']['shortname'];

                  ?>



                <div class="zbsExtension zbsPaidExtension">
                  <div class="zbsExtensionImg">
                    <img src="<?php echo $ext['meta']['imgstr']; ?>"/>
                  </div>
                  <h3><?php echo $ext['name']; ?></h3>
                  <p><?php _e($ext['meta']['desc'],"zero-bs-crm"); ?></p>
                  <div class="zbsExtensionButton"><?php
                  #} We should check if installed!
                  if ($ext['installed']){ if (isset($ext['meta']['helpurl']) && !empty($ext['meta']['helpurl'])) { ?>
                    <strong><?php _e("Installed","zero-bs-crm");?></strong> <a target="_blank" href="<?php echo $ext['meta']['helpurl']; ?>" class="ui button primary"><?php _e("View Docs","zero-bs-crm");?></a>
                  <?php } } else { ?>
                    <a target="_blank" href="<?php echo $ext['meta']['url']; ?>?utm_content=inplugin_exthub_spectificext" class="ui button primary"><?php _e("Purchase","zero-bs-crm");?> <?php echo $shortName; ?></a>
                  <?php } ?>
                  </div>
                </div>
                <?php } ?>
              </div>
            <?php ##/WLREMOVE 
              } else { #} White label ver: ?>
              <p><?php _e("To see which extensions we have available, please visit ","zero-bs-crm");?> <a href="<?php echo $zbs->urls['products']; ?>">Zero BS CRM homepage</a></p>
            <?php } ?>
            <div style="clear:both"></div>
          </div>
          
          
    </div>

  <?php 


}
*/


#} post-deletion page
function zeroBSCRM_html_deletion(){

  global $wpdb, $zbs;  #} Req

  #} Discern type of deletion:
  $deltype = '?'; # Customer
  $delstr = '?'; # Mary Jones ID 123
  $delID = -1;
  $isRestore = false;
  $backToPage = 'edit.php?post_type=zerobs_customer&page=manage-customers';

  #} Perhaps this needs nonce?
  if (isset($_GET['restoreplz']) && $_GET['restoreplz'] == 'kthx') $isRestore = true;

    #} Discern type
    if (isset($_GET['cid']) && !empty($_GET['cid'])){

      $delID = (int)sanitize_text_field($_GET['cid']);
      $delIDVar = 'cid';
      $backToPage = 'edit.php?post_type=zerobs_customer&page=manage-customers';

      #} Fill out
      $delType = zeroBSCRM_getContactOrCustomer(); #Contact or Customer
      $delStr = zeroBS_getCustomerName($delID);

    } else if (isset($_GET['qid']) && !empty($_GET['qid'])){

      #} Quote
      $delID = (int)sanitize_text_field($_GET['qid']);
      $delIDVar = 'qid';
      $backToPage = 'edit.php?post_type=zerobs_quote&page=manage-quotes';

      #} Fill out
      $delType = 'Quote';
      $delStr = 'Quote ID: '.$delID; # TODO - these probably need offset

    } else if (isset($_GET['qtid']) && !empty($_GET['qtid'])){

      #} Quote
      $delID = (int)sanitize_text_field($_GET['qtid']);
      $delIDVar = 'qtid';
      $backToPage = 'edit.php?post_type=zerobs_quote&page=manage-quote-templates';

      #} Fill out
      $delType = 'Quote Template';
      $delStr = 'Quote Template ID: '.$delID; # TODO - these probably need offset

    } else if (isset($_GET['iid']) && !empty($_GET['iid'])){

      #} Invoice
      $delID = (int)sanitize_text_field($_GET['iid']);
      $delIDVar = 'iid';
      $backToPage = 'admin.php?page=manage-invoices';

      #} Fill out
      $delType = 'Invoice';
      $delStr = 'Invoice ID: '.$delID; # TODO - these probably need offset

    } else if (isset($_GET['tid']) && !empty($_GET['tid'])){

      #} Transaction
      $delID = (int)sanitize_text_field($_GET['tid']);
      $delIDVar = 'tid';
      $backToPage = 'edit.php?post_type=zerobs_transaction&page=manage-transactions';

      #} Fill out
      $delType = 'Transaction';
      $delStr = 'Transaction ID: '.$delID;

    } else if (isset($_GET['eid']) && !empty($_GET['eid'])){

      #} Transaction
      $delID = (int)sanitize_text_field($_GET['eid']);
      $delIDVar = 'eid';
      $backToPage = 'edit.php?post_type=zerobs_event&page=manage-events';

      #} Fill out
      $delType = __('Task',"zero-bs-crm");
      $delStr = 'Event ID: '.$delID;

    }

    $perm = 0;
    if (isset($_GET['perm']) && !empty($_GET['perm'])){

      // wh added - mediocre last min check :/
      if (zeroBSCRM_permsEvents()){

          //only for events for now
         if (isset($_GET['eid']) && !empty($_GET['eid'])){
          
            $perm = (int)sanitize_text_field($_GET['perm']);
              if($perm == 1){
                  wp_delete_post($delID);
              }
            }

          }

    }

    #} Actual restore
    if ($isRestore && !empty($delID)){

      wp_untrash_post($delID);

    }

  if($perm == 1){ ?>

    <div id="zbsDeletionPage">
      <div id="zbsDeletionMsgWrap">
        <div id="zbsDeletionIco"><i class="fa fa-trash" aria-hidden="true"></i></div>
        <div class="zbsDeletionMsg">
            <?php echo $delStr . __(' Successfully deleted', 'zero-bs-crm'); ?>
        </div>
        <div class="zbsDeletionAction">
          <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo esc_url( $backToPage); ?>'">Back to <?php echo $delType; ?>s</button>
        </div>
      </div>
    </div> 


  <?php

  }else{


  ?>
    <div id="zbsDeletionPage">
      <div id="zbsDeletionMsgWrap">
        <div id="zbsDeletionIco"><i class="fa <?php if ($isRestore){ ?>fa-undo<?php } else { ?>fa-trash<?php } ?>" aria-hidden="true"></i></div>
        <div class="zbsDeletionMsg"><?php echo $delStr; ?> <?php _e("successfully","zero-bs-crm");?> 
          <?php if ($isRestore){ ?>
          <?php _e("retrieved from Trash","zero-bs-crm");?>
          <?php } else { #trashed ?>
          <?php _e("moved to Trash","zero-bs-crm");?>
          <?php } ?>
        </div>
        <div class="zbsDeletionAction">
          <?php if ($isRestore){ ?>
          <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo esc_url( $backToPage); ?>'">Back to <?php echo $delType; ?>s</button>
          <?php } else { #trashed ?>
          <button type="button" class="ui button green" onclick="javascript:window.location='admin.php?page=zbs-deletion&<?php echo $delIDVar; ?>=<?php echo $delID; ?>&restoreplz=kthx'">Undo (Restore <?php echo $delType; ?>)</button>
          &nbsp;&nbsp;
          <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo esc_url( $backToPage ); ?>'">Back to <?php echo $delType; ?>s</button>
          <?php } ?>


          
          <?php 
          if (isset($_GET['eid']) && !empty($_GET['eid'])){
            //right now, we only ever "trash" things without the ability to fully delete...
            //WHLOOK - won't work with new DB2.0 data objects will need our own process for
            // 1.) Trash
            // 2.) Permanently Delete
            // Might already be there, but MS not familiar. Events currently in old DB1.0 layout.
            // to discuss.
            $delID = (int)sanitize_text_field($_GET['eid']);
            $delete_link = admin_url('admin.php?page=zbs-deletion&eid=' . $delID . '&perm=1');
            ?>
            <br/>
            <?php _e("or", "zero-bs-crm"); ?>
            <br/>
            <a href='<?php echo $delete_link; ?>'><?php _e("Delete Permanently", "zero-bs-crm"); ?></a>
            <?php
            //this allows me to hook in and say "deleting permanently also deletes the outlook event permanently"
            do_action('zbs-delete-event-permanently');
          }
          ?>

        </div>
      </div>
    </div>        
    <?php 

  }

}


#} post-deletion page
function zeroBSCRM_html_norights(){

  global $wpdb, $zbs;  #} Req

  #} Discern type of norights:
  $noaccessType = '?'; # Customer
  $noaccessstr = '?'; # Mary Jones ID 123
  $noaccessID = -1;
  $isRestore = false;
  $backToPage = 'edit.php?post_type=zerobs_customer&page=manage-customers';

  #} Discern type + set back to page
  $noAccessType = '';

    // DAL3 switch
    if ($zbs->isDAL3()){

      // DAL 3
      $objID = $zbs->zbsvar('zbsid'); // -1 or 123 ID
      $objTypeStr = $zbs->zbsvar('zbstype'); // -1 or 'contact'

      // if objtypestr is -1, assume contact (default)
      if ($objTypeStr == -1)
        $objType = ZBS_TYPE_CONTACT;
      else
        $objType = $this->DAL->objTypeID($objTypeStr);

      // if got type, link to list view
      // else give dash link
      $slugToSend = ''; $noAccessTypeStr = '';

        // back to page
        if ($objType > 0) $slugToSend = $zbs->DAL->listViewSlugFromObjID($objType);
        if (empty($slugToSend)) $slugToSend = $zbs->slugs['dash'];
        $backToPage = 'admin.php?page='.$slugToSend;

        // obj type str
        if ($objType > 0) $noAccessTypeStr = $zbs->DAL->typeStr($objType);
        if (empty($noAccessTypeStr)) $noAccessTypeStr = __('Object','zero-bs-crm');


    } else {

      // PRE DAL3:

          if (isset($_GET['post_type']) && !empty($_GET['post_type']))
            $noAccessType = $_GET['post_type'];
          else {
            if (isset( $_GET['id'] )) $noAccessType = get_post_type( $_GET['id'] );
          }

          switch ($noAccessType){

              case 'zerobs_customer':
                
                  $backToPage = 'edit.php?post_type=zerobs_customer&page=manage-customers';
                  $noAccessTypeStr = __('Contact',"zero-bs-crm");

                  break;

              case 'zerobs_company':
                
                  $backToPage = 'edit.php?post_type=zerobs_company&page=manage-companies';
                  $noAccessTypeStr = __('Company',"zero-bs-crm");

                  break;

              default:
                
                  // Dash
                  $backToPage = 'admin.php?page='.$zbs->slugs['dash'];
                  $noAccessTypeStr = __('Resource',"zero-bs-crm");

                  break;

          }

      }

  ?>
    <div id="zbsNoAccessPage">
      <div id="zbsNoAccessMsgWrap">
        <div id="zbsNoAccessIco"><i class="fa fa-archive" aria-hidden="true"></i></div>
        <div class="zbsNoAccessMsg">
          <h2><?php echo _e('Access Restricted'); ?></h2>
          <p><?php echo _e('You do not have access to this '.$noAccessTypeStr.'.'); ?></p>
        </div>
        <div class="zbsNoAccessAction">
          <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo esc_url( $backToPage ); ?>'"><?php _e("Back","zero-bs-crm");?></button>

        </div>
      </div>
    </div>        
    <?php 
 

}


#} SETTINGS
function zeroBSCRM_html_settings(){

  global $wpdb, $zbs;  #} Req

  $confirmAct = false;

  #} Retrieve all settings
  $settings = $zbs->settings->getAll();

  #} #WH OR - need these lists?
  #} Autologgers: 
  $autoLoggers = array(
                    array('fieldname' => 'autolog_customer_new', 'title'=> 'Contact Creation'),
                    array('fieldname' => 'autolog_customer_statuschange', 'title'=> 'Contact Status Change'),
                    array('fieldname' => 'autolog_company_new', 'title'=> 'Company Creation'),
                    array('fieldname' => 'autolog_quote_new', 'title'=> 'Quote Creation'),
                    array('fieldname' => 'autolog_invoice_new', 'title'=> 'Invoice Creation'),
                    array('fieldname' => 'autolog_transaction_new', 'title'=> 'Transaction Creation'),
                    array('fieldname' => 'autolog_event_new', 'title'=> 'Event Creation'),
                    array('fieldname' => 'autolog_clientportal_new', 'title'=> 'Client Portal User Creation')
                  );

  // extensions add to list :D
  $autoLoggers = apply_filters( 'zbs_autologger_list', $autoLoggers );
    

    #} load currency list                           
    global $whwpCurrencyList;
    if(!isset($whwpCurrencyList)) require_once(ZEROBSCRM_PATH . 'includes/wh.currency.lib.php');
    /*
    #} load country list                            
    global $whwpCountryList;
    if(!isset($whwpCountryList)) require_once(ZEROBSCRM_PATH . 'includes/wh.countrycode.lib.php');

    */

  #} Act on any edits!
  if (isset($_POST['editwplf']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-general' );

    #} Retrieve
    $updatedSettings = array();
    $updatedSettings['wptakeovermode'] = 0; if (isset($_POST['wpzbscrm_wptakeovermode']) && !empty($_POST['wpzbscrm_wptakeovermode'])) $updatedSettings['wptakeovermode'] = 1;
    $updatedSettings['wptakeovermodeforall'] = 0; if (isset($_POST['wpzbscrm_wptakeovermodeforall']) && !empty($_POST['wpzbscrm_wptakeovermodeforall'])) $updatedSettings['wptakeovermodeforall'] = 1;
    $updatedSettings['customheadertext'] = ''; if (isset($_POST['wpzbscrm_customheadertext']) && !empty($_POST['wpzbscrm_customheadertext'])) $updatedSettings['customheadertext'] = sanitize_text_field($_POST['wpzbscrm_customheadertext']);
    $updatedSettings['loginlogourl'] = ''; if (isset($_POST['wpzbscrm_loginlogourl']) && !empty($_POST['wpzbscrm_loginlogourl'])) $updatedSettings['loginlogourl'] = sanitize_text_field($_POST['wpzbscrm_loginlogourl']);
    $updatedSettings['showneedsquote'] = 0; if (isset($_POST['wpzbscrm_showneedsquote']) && !empty($_POST['wpzbscrm_showneedsquote'])) $updatedSettings['showneedsquote'] = 1;
    $updatedSettings['killfrontend'] = 0; if (isset($_POST['wpzbscrm_killfrontend']) && !empty($_POST['wpzbscrm_killfrontend'])) $updatedSettings['killfrontend'] = 1;

    #} 1.1.7
    $updatedSettings['quoteoffset'] = 0; if (isset($_POST['wpzbscrm_quoteoffset']) && !empty($_POST['wpzbscrm_quoteoffset'])) $updatedSettings['quoteoffset'] = (int)sanitize_text_field($_POST['wpzbscrm_quoteoffset']);
    $updatedSettings['invoffset'] = 0; if (isset($_POST['wpzbscrm_invoffset']) && !empty($_POST['wpzbscrm_invoffset'])) $updatedSettings['invoffset'] = (int)sanitize_text_field($_POST['wpzbscrm_invoffset']);
    $updatedSettings['invallowoverride'] = 0; if (isset($_POST['wpzbscrm_invallowoverride']) && !empty($_POST['wpzbscrm_invallowoverride'])) $updatedSettings['invallowoverride'] = 1;
    $updatedSettings['showaddress'] = 0; if (isset($_POST['wpzbscrm_showaddress']) && !empty($_POST['wpzbscrm_showaddress'])) $updatedSettings['showaddress'] = 1;
    $updatedSettings['secondaddress'] = 0; if (isset($_POST['wpzbscrm_secondaddress']) && !empty($_POST['wpzbscrm_secondaddress'])) $updatedSettings['secondaddress'] = 1;
    
    #}2.93.1
    $updatedSettings['secondaddresslabel'] = __("Second Address","zero-bs-crm"); if (isset($_POST['wpzbscrm_secondaddresslabel']) && !empty($_POST['wpzbscrm_secondaddresslabel'])) $updatedSettings['secondaddresslabel'] = sanitize_text_field($_POST['wpzbscrm_secondaddresslabel']);


    #} 1.1.10
    $updatedSettings['companylevelcustomers'] = 0; if (isset($_POST['wpzbscrm_companylevelcustomers']) && !empty($_POST['wpzbscrm_companylevelcustomers'])) $updatedSettings['companylevelcustomers'] = 1;
    $updatedSettings['coororg'] = 'co'; 
      // modified to allow domain (for janos 5/1/18)
      if (isset($_POST['wpzbscrm_coororg']) && !empty($_POST['wpzbscrm_coororg']) && $_POST['wpzbscrm_coororg'] == 'org') $updatedSettings['coororg'] = 'org';
      if (isset($_POST['wpzbscrm_coororg']) && !empty($_POST['wpzbscrm_coororg']) && $_POST['wpzbscrm_coororg'] == 'domain') $updatedSettings['coororg'] = 'domain';

    #} 1.1.12
    $updatedSettings['showthanksfooter'] = 0; if (isset($_POST['wpzbscrm_showthanksfooter']) && !empty($_POST['wpzbscrm_showthanksfooter'])) $updatedSettings['showthanksfooter'] = 1;

    #} 1.1.15 - autologgers
    foreach ($autoLoggers as $autoLog) {
      $updatedSettings[$autoLog['fieldname']] = 0; if (isset($_POST['wpzbscrm_'.$autoLog['fieldname']]) && !empty($_POST['wpzbscrm_'.$autoLog['fieldname']])) $updatedSettings[$autoLog['fieldname']] = 1;
    }

    #} 1.1.17 - gcaptcha (should be moved into a "Forms" tab later)
    $updatedSettings['usegcaptcha'] = 0; if (isset($_POST['wpzbscrm_usegcaptcha']) && !empty($_POST['wpzbscrm_usegcaptcha'])) $updatedSettings['usegcaptcha'] = 1;
    $updatedSettings['gcaptchasitekey'] = 0; if (isset($_POST['wpzbscrm_gcaptchasitekey']) && !empty($_POST['wpzbscrm_gcaptchasitekey'])) $updatedSettings['gcaptchasitekey'] = sanitize_text_field($_POST['wpzbscrm_gcaptchasitekey']);
    $updatedSettings['gcaptchasitesecret'] = 0; if (isset($_POST['wpzbscrm_gcaptchasitesecret']) && !empty($_POST['wpzbscrm_gcaptchasitesecret'])) $updatedSettings['gcaptchasitesecret'] = sanitize_text_field($_POST['wpzbscrm_gcaptchasitesecret']);

    #} 1.1.18 - scope
    #WH Moved to Extensions Hub - $updatedSettings['feat_custom_fields'] = 0; if (isset($_POST['wpzbscrm_feat_custom_fields']) && !empty($_POST['wpzbscrm_feat_custom_fields'])) $updatedSettings['feat_custom_fields'] = 1;
    #WH Moved to Extensions Hub - $updatedSettings['feat_forms'] = 0; if (isset($_POST['wpzbscrm_feat_forms']) && !empty($_POST['wpzbscrm_feat_forms'])) $updatedSettings['feat_forms'] = 1;    

    #} 1.1.19 - Show countries in addresses?
    $updatedSettings['countries'] = 0; if (isset($_POST['wpzbscrm_countries']) && !empty($_POST['wpzbscrm_countries'])) $updatedSettings['countries'] = 1;

    #} 1.2.0 - Menu Layout Option
    $updatedSettings['menulayout'] = 1; if(isset($_POST['wpzbscrm_menulayout']) && !empty($_POST['wpzbscrm_menulayout'])) $updatedSettings['menulayout'] = (int)sanitize_text_field($_POST['wpzbscrm_menulayout']);

    $fileTypesUpload = $settings['filetypesupload'];
        
      foreach ($zbs->acceptable_mime_types as $filetype => $mimedeet){
        $fileTypesUpload[$filetype] = 0; if (isset($_POST['wpzbscrm_ft_'.$filetype]) && !empty($_POST['wpzbscrm_ft_'.$filetype])) $fileTypesUpload[$filetype] = 1;
      }

      $fileTypesUpload['all'] = 0; if (isset($_POST['wpzbscrm_ft_all']) && !empty($_POST['wpzbscrm_ft_all'])) $fileTypesUpload['all'] = 1;

    $updatedSettings['filetypesupload'] = $fileTypesUpload;
    
    #} 2.12
    $updatedSettings['perusercustomers'] = 0; if (isset($_POST['wpzbscrm_perusercustomers']) && !empty($_POST['wpzbscrm_perusercustomers'])) $updatedSettings['perusercustomers'] = 1;
    $updatedSettings['usercangiveownership'] = 0; if (isset($_POST['wpzbscrm_usercangiveownership']) && !empty($_POST['wpzbscrm_usercangiveownership'])) $updatedSettings['usercangiveownership'] = 1;
    $updatedSettings['clicktocall'] = 0; if (isset($_POST['wpzbscrm_clicktocall']) && !empty($_POST['wpzbscrm_clicktocall'])) $updatedSettings['clicktocall'] = 1;

    #} 2.12.1
    $updatedSettings['clicktocalltype'] = 1; if (isset($_POST['wpzbscrm_clicktocalltype']) && !empty($_POST['wpzbscrm_clicktocalltype'])) $updatedSettings['clicktocalltype'] = (int)sanitize_text_field($_POST['wpzbscrm_clicktocalltype'] );


    #} 2.++ telemetrics
    $updatedSettings['shareessentials'] = 0; if (isset($_POST['wpzbscrm_shareessentials']) && !empty($_POST['wpzbscrm_shareessentials'])) $updatedSettings['shareessentials'] = 1;

    #} 2.2
    $updatedSettings['usesocial'] = 0; if (isset($_POST['wpzbscrm_usesocial']) && !empty($_POST['wpzbscrm_usesocial'])) $updatedSettings['usesocial'] = 1;
    $updatedSettings['useaka'] = 0; if (isset($_POST['wpzbscrm_useaka']) && !empty($_POST['wpzbscrm_useaka'])) $updatedSettings['useaka'] = 1;
    $updatedSettings['taskownership'] = 0; if (isset($_POST['wpzbscrm_taskownership']) && !empty($_POST['wpzbscrm_taskownership'])) $updatedSettings['taskownership'] = 1;


    #} 2.52+ gravitar setting
    $updatedSettings['wpzbscrm_avatarmode'] = 1; if (isset($_POST['wpzbscrm_avatarmode']) && !empty($_POST['wpzbscrm_avatarmode'])) $updatedSettings['avatarmode'] = (int)sanitize_text_field($_POST['wpzbscrm_avatarmode']);

    #} 2.63
    $updatedSettings['showthankslogin'] = 0; if (isset($_POST['wpzbscrm_showthankslogin']) && !empty($_POST['wpzbscrm_showthankslogin'])) $updatedSettings['showthankslogin'] = 1;

    #} 2.75
    $updatedSettings['objnav'] = 0; if (isset($_POST['wpzbscrm_objnav']) && !empty($_POST['wpzbscrm_objnav'])) $updatedSettings['objnav'] = 1;


    #} Currency (Grim but will work for now)
    $updatedSettings['currency'] = array('chr'  => '$','strval' => 'USD'); 
    if (isset($_POST['wpzbscrm_currency'])) {
      foreach ($whwpCurrencyList as $currencyObj) {
        if ($currencyObj[1] == $_POST['wpzbscrm_currency']) {
          $updatedSettings['currency']['chr'] = $currencyObj[0];
          $updatedSettings['currency']['strval'] = $currencyObj[1];
          break;
        }
      }
    }


    #} 2.84 Currency Formatting
    $updatedSettings['currency_position'] = 0; if (isset($_POST['wpzbscrm_currency_position']) && !empty($_POST['wpzbscrm_currency_position'])) $updatedSettings['currency_position'] = (int)sanitize_text_field($_POST['wpzbscrm_currency_position']);
    $updatedSettings['currency_format_thousand_separator'] = ','; if (isset($_POST['wpzbscrm_currency_format_thousand_separator']) && !empty($_POST['wpzbscrm_currency_format_thousand_separator'])) $updatedSettings['currency_format_thousand_separator'] = sanitize_text_field($_POST['wpzbscrm_currency_format_thousand_separator']);
    $updatedSettings['currency_format_decimal_separator'] = '.'; if (isset($_POST['wpzbscrm_currency_format_decimal_separator']) && !empty($_POST['wpzbscrm_currency_format_decimal_separator'])) $updatedSettings['currency_format_decimal_separator'] = sanitize_text_field($_POST['wpzbscrm_currency_format_decimal_separator']);
    $updatedSettings['currency_format_number_of_decimals'] = 2; 
    if (isset($_POST['wpzbscrm_currency_format_number_of_decimals'])) {

      $decimalCount = (int)sanitize_text_field( $_POST['wpzbscrm_currency_format_number_of_decimals'] );
      if ($decimalCount < 0) $decimalCount = 0;
      if ($decimalCount > 10) $decimalCount = 10;
      $updatedSettings['currency_format_number_of_decimals'] = $decimalCount;

    }


    //print_r($updatedSettings); exit();


    #} Gmaps src inc (if needed)
    #$updatedSettings['incgoogmapjs'] = -1; if (isset($_POST['wpzbscrm_incgoogmapjs'])) $updatedSettings['incgoogmapjs'] = 1;

    #} CSS Override
    $updatedSettings['css_override'] = ''; if (isset($_POST['wpzbscrm_css_override'])) $updatedSettings['css_override'] = zeroBSCRM_textProcess($_POST['wpzbscrm_css_override']);

    #} Brutal update
    foreach ($updatedSettings as $k => $v) $zbs->settings->update($k,$v);

    #} $msg out!
    $sbupdated = true;

    #} Reload
    $settings = $zbs->settings->getAll();

  }

  #} catch resets.
  if (zeroBSCRM_isZBSAdminOrAdmin() && isset($_GET['resetsettings'])) if ($_GET['resetsettings']==1){

    $nonceVerified = wp_verify_nonce( $_GET['_wpnonce'], 'resetclearzerobscrm' );

    if (!isset($_GET['imsure']) || !$nonceVerified){

        #} Needs to confirm!  
        $confirmAct = true;
        $actionStr        = 'resetsettings';
        $actionButtonStr    = __('Reset Settings to Defaults?',"zero-bs-crm");
        $confirmActStr      = __('Reset All Zero BS CRM Settings?',"zero-bs-crm");
        $confirmActStrShort   = __('Are you sure you want to reset these settings to the defaults?',"zero-bs-crm");
        $confirmActStrLong    = __('Once you reset these settings you cannot retrieve your previous settings.',"zero-bs-crm");

      } else {


        if ($nonceVerified){

            #} Reset
            $zbs->settings->resetToDefaults();
            $settings = $zbs->settings->getAll();

            #} Msg out!
            $sbreset = true;

        }

      }

  }

  if (!$confirmAct){

  ?>
    
        <p id="sbDescOLD"><?php _e('From this page you can choose global settings for Zero BS CRM, and using the tabs above you can setup different',"zero-bs-crm"); ?> <a href="<?php echo $zbs->urls['products']; ?>" target="_blank"><?php _e("Extensions","zero-bs-crm");?></a></p>

        <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div>'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
        <?php if (isset($sbreset)) if ($sbreset) { echo '<div>'; zeroBSCRM_html_msg(0,__('Settings Reset',"zero-bs-crm")); echo '</div>'; } ?>
        
        <div id="sbA">
          <pre><?php // print_r($settings); ?></pre>

            <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>">
              <input type="hidden" name="editwplf" id="editwplf" value="1" />
              <?php 
                // add nonce
                wp_nonce_field( 'zbs-update-settings-general');
              ?>

               <table class="table table-bordered table-striped wtab">
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('WordPress Menu Layout',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>
                      
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_menulayout"><?php _e('Menu Layout',"zero-bs-crm"); ?>:</label><br /><?php _e('How do you want your WordPress Admin Menu to Display?',"zero-bs-crm"); ?></td>
                          <td style="width:540px">
                          <select class="winput" name="wpzbscrm_menulayout" id="wpzbscrm_menylayout">
                            <!-- common currencies first -->
                            <option value="1" <?php if (isset($settings['menulayout']) && $settings['menulayout'] == '1') echo ' selected="selected"'; ?>><?php _e('Full',"zero-bs-crm");?></option>
                            <option value="2" <?php if (isset($settings['menulayout']) && $settings['menulayout'] == '2') echo ' selected="selected"'; ?>><?php _e('Slimline',"zero-bs-crm");?></option>
                            <option value="3" <?php if (isset($settings['menulayout']) && $settings['menulayout'] == '3') echo ' selected="selected"'; ?>><?php _e('CRM Only',"zero-bs-crm");?></option>
                          </select>
                          <br />
                          <div>
                            <?php _e("Are you looking for your other WordPress menu items? (e.g.","zero-bs-crm");?> <a href="<?php echo admin_url('plugins.php'); ?>"><?php _e("Plugins","zero-bs-crm");?></a>, <?php _e("or","zero-bs-crm");?> <a href="<?php echo admin_url('users.php'); ?>"><?php _e("Users","zero-bs-crm");?></a>)?<br />
                            <?php _e("If you can't see these, (and you want to), select 'Slimline' or 'Full' from the above menu, then make sure 'Override WordPress (For All WP Users):' is disabled below","zero-bs-crm");?> (<a href="#override-allusers"><?php _e("here","zero-bs-crm");?></a>).<br />
                            <?php ##WLREMOVE ?>
                              <a href="https://zerobscrm.com/kb/knowledge-base/how-to-get-wordpress-menu-items-back/" target="_blank"><?php _e("View Guide","zero-bs-crm");?></a>
                            <?php ##/WLREMOVE ?>
                          </div>
                          </td>
                        </tr>

                      </tbody>
               </table>

              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('General Settings',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <?php #WH10 


                        #} #WH OR - currency ? country?
                          #} Curr
                      $tbpCurrency = $settings['currency'];
                      $tbpCurrencyChar = '&pound;'; $tbpCurrencyStr = 'GBP';
                      if (isset($tbpCurrency) && isset($tbpCurrency['chr'])) {
                          
                          $tbpCurrencyChar = $tbpCurrency['chr']; 
                          $tbpCurrencyStr = $tbpCurrency['strval'];

                      }

                    ?>
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_currency"><?php _e('Currency Symbol',"zero-bs-crm"); ?>:</label></td>
                          <td><select class="winput short" name="wpzbscrm_currency" id="wpzbscrm_currency">
                            <!-- common currencies first -->
                            <option value="USD">$ (USD)</option>
                            <option value="GBP">&pound; (GBP)</option>
                            <option disabled="disabled">----</option>
                            <?php foreach ($whwpCurrencyList as $currencyObj){
                              ?><option value="<?php echo $currencyObj[1]; ?>"<?php if (isset($settings['currency']) && isset($settings['currency']['strval']) && $settings['currency']['strval'] == $currencyObj[1]) echo ' selected="selected"'; ?>><?php echo $currencyObj[0].' ('.$currencyObj[1].')'; ?></option>
                            <?php } ?>
                          </select></td>
                        </tr>
                      
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_currency_format"><?php _e('Currency Format',"zero-bs-crm"); ?>:</label><br /><?php _e('Choose how you want your currency format to display',"zero-bs-crm"); ?></td>
                          <td style="width:540px">
                              <label for="wpzbscrm_currency_format_position">
                                <?php _e('Symbol position: ','zero-bs-crm'); ?>
                              </label>

                                <select class='form-control' name="wpzbscrm_currency_position" id="wpzbscrm_currency_position">
                                  <option value='0' <?php if($settings['currency_position'] == 0) echo 'selected'; ?>><?php _e('Left','zero-bs-crm');?></option>
                                  <option value='1' <?php if($settings['currency_position'] == 1) echo 'selected'; ?>><?php _e('Right','zero-bs-crm');?></option>
                                  <option value='2' <?php if($settings['currency_position'] == 2) echo 'selected'; ?>><?php _e('Left with space','zero-bs-crm');?></option>
                                  <option value='3' <?php if($settings['currency_position'] == 3) echo 'selected'; ?>><?php _e('Right with space','zero-bs-crm');?></option>
                                </select>

                              <br/>
                              <label for="wpzbscrm_currency_format_thousand_separator">
                                <?php _e('Thousand separator: ','zero-bs-crm'); ?>
                              </label>
                                <input type="text" class="winput form-control" name="wpzbscrm_currency_format_thousand_separator" id="wpzbscrm_currency_format_thousand_separator" value="<?php echo $settings['currency_format_thousand_separator']; ?>" />

                              <br/>
                              <label for="wpzbscrm_currency_format_decimal_separator">
                              <?php _e('Decimal separator: ','zero-bs-crm'); ?>
                              </label>
                              <input type="text" class="winput form-control" name="wpzbscrm_currency_format_decimal_separator" id="wpzbscrm_currency_format_decimal_separator" value="<?php echo $settings['currency_format_decimal_separator']; ?>" />

                              <br/>
                              <label for="wpzbscrm_currency_format_number_of_decimals">
                              <?php _e('Number of decimals: ','zero-bs-crm'); ?>
                              </label>
                              <input type="number" class="winput form-control" name="wpzbscrm_currency_format_number_of_decimals" id="wpzbscrm_currency_format_number_of_decimals" value="<?php echo $settings['currency_format_number_of_decimals']; ?>" />


                          </td>
                        </tr>



                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_showaddress"><?php _e('Show Customer Address Fields',"zero-bs-crm"); ?>:</label><br /><?php _e('Untick to hide the address fields (useful for online business)',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_showaddress" id="wpzbscrm_showaddress" value="1"<?php if (isset($settings['showaddress']) && $settings['showaddress'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_secondaddress"><?php _e('Second Address Fields',"zero-bs-crm"); ?>:</label><br /><?php _e('Allow editing of a "second address" against a customer',"zero-bs-crm"); ?></td>
                          <td style="width:540px">
                            <input type="checkbox" class="winput form-control" name="wpzbscrm_secondaddress" id="wpzbscrm_secondaddress" value="1"<?php if (isset($settings['secondaddress']) && $settings['secondaddress'] == "1") echo ' checked="checked"'; ?> />
                        </tr>
                        <tr>
                        <td class="wfieldname"><label for="wpzbscrm_secondaddress"><?php _e('Second Address Label',"zero-bs-crm"); ?>:</label><br /><?php _e('Edit what text is displayed (defaults to Second Address)',"zero-bs-crm"); ?></td>
                          <td style="width:540px">
                            <input type="text" class="wpinput form-control" name="wpzbscrm_secondaddresslabel" id="pzbscrm_secondaddresslabel" value="<?php if (isset($settings['secondaddresslabel'])){ echo $settings['secondaddresslabel'];  } ?>" placeholder="<?php _e("Second Address (if left blank)","zero-bs-crm"); ?>" />
                          </td>
                        </tr>
                      
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_countries"><?php _e('Use "Countries" in Address Fields',"zero-bs-crm"); ?>:</label><br /><?php _e('Untick to hide country from address fields (useful for local business)',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_countries" id="wpzbscrm_countries" value="1"<?php if (isset($settings['countries']) && $settings['countries'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_companylevelcustomers" id="b2b-tour"><?php _e('B2B Mode',"zero-bs-crm"); ?>:</label><br /><?php _e('Adds a "company or organisation" level to customers (allowing you to store contacts at a company)',"zero-bs-crm"); ?></td>
                          <td><input type="checkbox" class="winput form-control" name="wpzbscrm_companylevelcustomers" id="wpzbscrm_companylevelcustomers" value="1"<?php if (isset($settings['companylevelcustomers']) && $settings['companylevelcustomers'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>



                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_coororg"><?php _e('Company Label',"zero-bs-crm"); ?>:</label><br /><?php _e('Should ZBS use the label "Company" or "Organisation" for your B2B setup?',"zero-bs-crm"); ?></td>
                          <td><select class="winput short" name="wpzbscrm_coororg" id="wpzbscrm_coororg">
                            <option value="co"<?php if (isset($settings['coororg']) && $settings['coororg'] == 'co') echo ' selected="selected"'; ?>>Company</option>
                            <option value="org"<?php if (isset($settings['coororg']) && $settings['coororg'] == 'org') echo ' selected="selected"'; ?>>Organisation</option>
                            <?php # WH Note 5/1/17 - added this for Janos, but in reality no1 else will want,
                                  //  so will confuse people really.. rethink how we do these (very specific) installs
                                  // can we add a "key" somewhere which exposes some options to specific users?
                            ?>
                            <option value="domain"<?php if (isset($settings['coororg']) && $settings['coororg'] == 'domain') echo ' selected="selected"'; ?>>Domain</option>
                          </select>

                          </td>
                        </tr>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_perusercustomers"><?php _e('Contact Assignment',"zero-bs-crm"); ?>:</label><br /><?php _e('If ticked, each contact can be assigned to a CRM user.',"zero-bs-crm"); ?></td>
                          <td><input type="checkbox" class="winput form-control" name="wpzbscrm_perusercustomers" id="wpzbscrm_perusercustomers" value="1"<?php if (isset($settings['perusercustomers']) && $settings['perusercustomers'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_usercangiveownership"><?php _e('Assign Ownership',"zero-bs-crm"); ?>:</label><br /><?php _e('Allow users to assign contacts to another CRM user',"zero-bs-crm"); ?></td>
                          <td><input type="checkbox" class="winput form-control" name="wpzbscrm_usercangiveownership" id="wpzbscrm_usercangiveownership" value="1"<?php if (isset($settings['usercangiveownership']) && $settings['usercangiveownership'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>
                        
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_taskownership"><?php _e('Task Scheduler Ownership',"zero-bs-crm"); ?>:</label><br /><?php _e('Show only scheduled tasks owned by a user (Admin sees all).',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_taskownership" id="wpzbscrm_taskownership" value="1"<?php if (isset($settings['taskownership']) && $settings['taskownership'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>
                      
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_showneedsquote"><?php _e('Show \'Needs a Quote\' section',"zero-bs-crm"); ?>:</label><br /><?php _e('Adds a page to Customers menu to show customers added which do not have quotes attached, (and are not marked \'Refused\' or \'Blacklisted\')',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_showneedsquote" id="wpzbscrm_showneedsquote" value="1"<?php if (isset($settings['showneedsquote']) && $settings['showneedsquote'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>
                        <?php

                        // <3.0 (Did away with number offsets in 3.0) - suggest using custom field autonumber if needed.

                        if (!$zbs->isDAL3()){ ?>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_quoteoffset"><?php _e('Quote Number Offset',"zero-bs-crm"); ?>:</label><br /><?php _e('Setting this value will offset your quote numbers. E.g. adding 1000 here will make a quote number 1000+it\'s actual number',"zero-bs-crm"); ?></td>
                          <td><input type="text" class="winput form-control" name="wpzbscrm_quoteoffset" id="wpzbscrm_quoteoffset" value="<?php if (isset($settings['quoteoffset']) && !empty($settings['quoteoffset'])) echo (int)$settings['quoteoffset']; ?>" placeholder="e.g. 1000" /><br />(<?php _e("Currently, Next Quote Number to be issued","zero-bs-crm");?>: <?php echo zeroBSCRM_getNextQuoteID(); ?>)</td>
                        </tr>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_invoffset"><?php _e('Invoice Number Offset',"zero-bs-crm"); ?>:</label><br /><?php _e('Setting this value will offset your invoice numbers. E.g. adding 1000 here will make a invoice number 1000+it\'s actual number',"zero-bs-crm"); ?></td>
                          <td><input type="text" class="winput form-control" name="wpzbscrm_invoffset" id="wpzbscrm_invoffset" value="<?php if (isset($settings['invoffset']) && !empty($settings['invoffset'])) echo (int)$settings['invoffset']; ?>" placeholder="e.g. 1000" /><br />(<?php _e("Currently, Next Invoice Number to be issued","zero-bs-crm");?>: <?php echo zeroBSCRM_getNextInvoiceID(); ?>)</td>
                        </tr>
                      
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_invallowoverride"><?php _e('Allow Override of Invoice Numbers',"zero-bs-crm"); ?>:</label><br /><?php _e('Allows the editing of Invoice Numbers',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_invallowoverride" id="wpzbscrm_invallowoverride" value="1"<?php if (isset($settings['invallowoverride']) && $settings['invallowoverride'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>

                        <?php }

                        // ===== / <3.0 ?>
                      
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_clicktocall"><?php _e('Show Click 2 Call links',"zero-bs-crm"); ?>:</label><br /><?php _e('Show a clickable telephone link next to any available telephone number',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_clicktocall" id="wpzbscrm_clicktocall" value="1"<?php if (isset($settings['clicktocall']) && $settings['clicktocall'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>

                      
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_clicktocalltype"><?php _e('Click 2 Call link type',"zero-bs-crm"); ?>:</label><br /><?php _e('Use Skype or Standard Click to Call?',"zero-bs-crm"); ?></td>
                          <td style="width:540px">
                            <select class="winput form-control" name="wpzbscrm_clicktocalltype" id="wpzbscrm_clicktocalltype">
                                <option value="1"<?php if (isset($settings['clicktocalltype']) && $settings['clicktocalltype'] == "1") echo ' selected="selected"'; ?>>Click to Call (tel:)</option>
                                <option value="2"<?php if (isset($settings['clicktocalltype']) && $settings['clicktocalltype'] == "2") echo ' selected="selected"'; ?>>Skype Call (callto:)</option>
                             </select>
                           </td>
                        </tr>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_objnav"><?php _e('Use Navigation Mode',"zero-bs-crm"); ?>:</label><br /><?php _e('Shows Previous & Next buttons on each contact allowing quick navigation through your list.',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_objnav" id="wpzbscrm_objnav" value="1"<?php if (isset($settings['objnav']) && $settings['objnav'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>
                      
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_usesocial"><?php _e('Show Social Accounts',"zero-bs-crm"); ?>:</label><br /><?php _e('Show fields for social media accounts for each contact.',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_usesocial" id="wpzbscrm_usesocial" value="1"<?php if (isset($settings['usesocial']) && $settings['usesocial'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>


                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_useaka"><?php _e('Use AKA Mode',"zero-bs-crm"); ?>:</label><br /><?php _e('Allow each contact to have several email addresses as aliases.',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_useaka" id="wpzbscrm_useaka" value="1"<?php if (isset($settings['useaka']) && $settings['useaka'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_avatarmode"><?php _e('Contact Image Mode',"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px">
                            <select class="winput form-control" name="wpzbscrm_avatarmode" id="wpzbscrm_avatarmode">
                              <?php /* // 1 = gravitar only, 2 = custom imgs, 3 = none */ ?>
                                <option value="1"<?php if (isset($settings['avatarmode']) && $settings['avatarmode'] == "1") echo ' selected="selected"'; ?>>Gravatars</option>
                                <option value="2"<?php if (isset($settings['avatarmode']) && $settings['avatarmode'] == "2") echo ' selected="selected"'; ?>>Custom Images</option>
                                <option value="3"<?php if (isset($settings['avatarmode']) && $settings['avatarmode'] == "3") echo ' selected="selected"'; ?>>None</option>
                             </select>
                           </td>
                        </tr>



                      </tbody>

                  </table>

               <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid" id="override-allusers"><?php _e('WordPress Override Mode',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_wptakeovermode"><?php _e('Override WordPress',"zero-bs-crm"); ?>:</label><br /><?php _e('Enabling this setting hides the WordPress header, menu items, and Dashboard for users assigned CRM roles',"zero-bs-crm"); ?></td>
                          <td><input type="checkbox" class="winput form-control" name="wpzbscrm_wptakeovermode" id="wpzbscrm_wptakeovermode" value="1"<?php if (isset($settings['wptakeovermode']) && $settings['wptakeovermode'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>
                      
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_wptakeovermodeforall"><?php _e('Override WordPress (For All WP Users)',"zero-bs-crm"); ?>:</label></td>
                          <td>
                            <input type="checkbox" class="winput form-control" name="wpzbscrm_wptakeovermodeforall" id="wpzbscrm_wptakeovermodeforall" value="1"<?php if (isset($settings['wptakeovermodeforall']) && $settings['wptakeovermodeforall'] == "1") echo ' checked="checked"'; ?> />
                            <br /><small><?php _e('Enabling this setting hides the WordPress header, menu items, and Dashboard for all WordPress Users',"zero-bs-crm"); ?></small>
                            <br /><small><?php _e('It does not affect access to your Client Portal, API, or Proposals.',"zero-bs-crm"); ?></small>
                          </td>
                        </tr>
                      
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_loginlogourl"><?php _e('Login Logo Override',"zero-bs-crm"); ?>:</label><br /><?php _e('Enter an URL here, or upload a logo to override the WordPress login logo!',"zero-bs-crm"); ?></td>
                          <td style="width:540px">
                            <input style="width:90%;padding:10px;" name="wpzbscrm_loginlogourl" id="wpzbscrm_loginlogourl" class="form-control link" type="text" value="<?php if (isset($settings['loginlogourl']) && !empty($settings['loginlogourl'])) echo $settings['loginlogourl']; ?>" />
                            <button id="wpzbscrm_loginlogourlAdd" class="button" type="button"><?php _e("Upload Image","zero-bs-crm");?></button>
                          </td>
                        </tr>
                      
     
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_customheadertext"><?php _e('Custom CRM Header',"zero-bs-crm"); ?>:</label><br /><?php _e('Naming your CRM with the above \'Override WordPress\' option selected will show a custom header with that name',"zero-bs-crm"); ?></td>
                          <td><input type="text" class="winput form-control" name="wpzbscrm_customheadertext" id="wpzbscrm_customheadertext" value="<?php if (isset($settings['customheadertext']) && !empty($settings['customheadertext'])) echo $settings['customheadertext']; ?>" placeholder="e.g. <?php _e("Your CRM","zero-bs-crm");?>" /></td>
                        </tr>
                      
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_killfrontend"><?php _e('Disable Front-End',"zero-bs-crm"); ?>:</label></td>
                          <td>
                            <input type="checkbox" class="winput form-control" name="wpzbscrm_killfrontend" id="wpzbscrm_killfrontend" value="1"<?php if (isset($settings['killfrontend']) && $settings['killfrontend'] == "1") echo ' checked="checked"'; ?> />
                            <br /><small><?php _e('Enabling this setting will disable the front-end of this WordPress install, (redirecting it to your login url!)',"zero-bs-crm"); ?></small>
                            <br /><small><?php _e('This will effectively disable your Client Portal (if installed), but will not affect your API.',"zero-bs-crm"); ?></small>
                          </td>
                        </tr>
                      
                        <?php ##WLREMOVE ?>
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_shareessentials"><?php _e('Share Essentials',"zero-bs-crm"); ?>:</label><br /><?php _e('Share basic anonymised usage data with us, so we can improve this for you.',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_shareessentials" id="wpzbscrm_shareessentials" value="1"<?php if (isset($settings['shareessentials']) && $settings['shareessentials'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>
                  
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_showthanksfooter"><?php _e('Show Footer Message',"zero-bs-crm"); ?>:</label><br /><?php _e('Show or Hide "Thanks for using Zero BS CRM"',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_showthanksfooter" id="wpzbscrm_showthanksfooter" value="1"<?php if (isset($settings['showthanksfooter']) && $settings['showthanksfooter'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_showthankslogin"><?php _e('Show Login Powered By',"zero-bs-crm"); ?>:</label><br /><?php _e('Show or Hide "Powered by ZBS CRM" on your login page (when in override mode)',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_showthankslogin" id="wpzbscrm_showthankslogin" value="1"<?php if (isset($settings['showthankslogin']) && $settings['showthankslogin'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>
                        <?php ##/WLREMOVE ?>
      
                      </tbody>

                  </table>

              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('File Attachment Settings',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                      
                        <tr>
                          <td class="wfieldname"><label><?php _e('Accepted Upload File Types',"zero-bs-crm"); ?>:</label><br /><?php _e('This setting specifies which file types are acceptable for uploading against customers, quotes, or invoices.',"zero-bs-crm"); ?></td>
                          <td style="width:540px">
                            <?php foreach ($zbs->acceptable_mime_types as $filetype => $mimedeet){ ?>
                            <input type="checkbox" class="winput form-control" name="<?php echo 'wpzbscrm_ft_'.$filetype; ?>" id="<?php echo 'wpzbscrm_ft_'.$filetype; ?>" value="1"<?php if (isset($settings['filetypesupload']) && isset($settings['filetypesupload'][$filetype]) && $settings['filetypesupload'][$filetype] == "1") echo ' checked="checked"'; ?> /> <?php echo '.'.$filetype; ?><br />
                            <?php } ?>
                            <input type="checkbox" class="winput form-control" name="<?php echo 'wpzbscrm_ft_all'; ?>" id="<?php echo 'wpzbscrm_ft_all'; ?>" value="1"<?php if (isset($settings['filetypesupload']) && isset($settings['filetypesupload']['all']) && $settings['filetypesupload']['all'] == "1") echo ' checked="checked"'; ?> /> * (All)<br />
                          </td>
                        </tr>
      
                      </tbody>

                  </table>

              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Auto-logging Settings',"zero-bs-crm"); ?>:<br />(<?php _e("Automatically create log on action","zero-bs-crm");?>)</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <?php 
                          foreach ($autoLoggers as $autoLog){ ?>
                      
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_<?php echo $autoLog['fieldname']; ?>"><?php _e('Auto-log: '.$autoLog['title'],"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_<?php echo $autoLog['fieldname']; ?>" id="wpzbscrm_<?php echo $autoLog['fieldname']; ?>" value="1"<?php if (isset($settings[$autoLog['fieldname']]) && $settings[$autoLog['fieldname']] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>

                        <?php } ?>
      
                      </tbody>

                  </table>
          

                  <table class="table table-bordered table-striped wtab">
                    <tbody>

                        <tr>
                          <td colspan="2" class="wmid"><button type="submit" class="ui primary button"><?php _e('Save Settings',"zero-bs-crm"); ?></button></td>
                        </tr>

                      </tbody>
                  </table>

              </form>


              <table class="table table-bordered table-striped wtab" style="margin-top:40px;">
                 
                     <thead>
                          <tr>
                              <th class="wmid">Zero BS CRM <?php _e('Plugin: Extra Tools',"zero-bs-crm"); ?></th>
                          </tr>
                      </thead>
                      
                      <tbody>
                        <tr>
                          <td>
                            <p style="padding: 10px;text-align:center;">
                              <button type="button" class="ui primary button" onclick="javascript:window.location='?page=<?php echo $zbs->slugs['settings']; ?>&resetsettings=1';"><?php _e('Restore default settings',"zero-bs-crm"); ?></button>                           
                            </p>
                          </td>
                        </tr>
                      </tbody>
              </table>

              <script type="text/javascript">

                jQuery(document).ready(function(){


                  // Uploader
                  // http://stackoverflow.com/questions/17668899/how-to-add-the-media-uploader-in-wordpress-plugin (3rd answer)                    
                  jQuery('#wpzbscrm_loginlogourlAdd').click(function(e) {
                      e.preventDefault();
                      var image = wp.media({ 
                          title: '<?php _e("Upload Image","zero-bs-crm");?>',
                          // mutiple: true if you want to upload multiple files at once
                          multiple: false
                      }).open()
                      .on('select', function(e){
                          
                          // This will return the selected image from the Media Uploader, the result is an object
                          var uploaded_image = image.state().get('selection').first();
                          // We convert uploaded_image to a JSON object to make accessing it easier
                          // Output to the console uploaded_image
                          //console.log(uploaded_image);
                          var image_url = uploaded_image.toJSON().url;
                          // Let's assign the url value to the input field
                          jQuery('#wpzbscrm_loginlogourl').val(image_url);

                      });
                  });




                });


              </script>
              
      </div><?php 
      
      } else {

          ?><div id="clpSubPage" class="whclpActionMsg six">
            <p><strong><?php echo $confirmActStr; ?></strong></p>
              <h3><?php echo $confirmActStrShort; ?></h3>
              <?php echo $confirmActStrLong; ?><br /><br />
              <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo wp_nonce_url('?page='.$zbs->slugs['settings'].'&'.$actionStr.'=1&imsure=1','resetclearzerobscrm'); ?>';"><?php echo $actionButtonStr; ?></button>
              <button type="button" class="button button-large" onclick="javascript:window.location='?page=<?php echo $zbs->slugs['settings']; ?>';"><?php _e("Cancel","zero-bs-crm"); ?></button>
              <br />
        </div><?php 
      } 
}


function zeroBSCRM_html_settings_clients(){

  global $wpdb, $zbs; #} Req 

  $confirmAct = false;
  $settings = $zbs->settings->getAll();    

  #} Act on any edits!
  if (isset($_POST['editwplf']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-clients' );

    $updatedSettings['showportalpoweredby'] = 0; if (isset($_POST['wpzbscrm_showportalpoweredby']) && !empty($_POST['wpzbscrm_showportalpoweredby'])) $updatedSettings['showportalpoweredby'] = 1;    

    $updatedSettings['portalusers'] = 0; if (isset($_POST['wpzbscrm_portalusers']) && !empty($_POST['wpzbscrm_portalusers'])) $updatedSettings['portalusers'] = 1;    

    $updatedSettings['portalpage'] = 0; if (isset($_POST['wpzbscrm_portalpage']) && !empty($_POST['wpzbscrm_portalpage'])) $updatedSettings['portalpage'] = (int)sanitize_text_field($_POST['wpzbscrm_portalpage']);

    // any extra roles to assign?
    $updatedSettings['portalusers_extrarole'] = ''; if (isset($_POST['wpzbscrm_portalusers_extrarole']) && !empty($_POST['wpzbscrm_portalusers_extrarole'])) $updatedSettings['portalusers_extrarole'] = sanitize_text_field( $_POST['wpzbscrm_portalusers_extrarole'] );


    // status based auto-gen

    /* WH - should this be here? */
          #} retrieve value as simple CSV for now - simplistic at best.
          $zbsStatusStr = ''; 
          #} stored here: $settings['customisedfields']
          if (isset($settings['customisedfields']['customers']['status']) && is_array($settings['customisedfields']['customers']['status'])) $zbsStatusStr = $settings['customisedfields']['customers']['status'][1];                                        
          if (empty($zbsStatusStr)) {
            #} Defaults:
            global $zbsCustomerFields; if (is_array($zbsCustomerFields)) $zbsStatusStr = implode(',',$zbsCustomerFields['status'][3]);
          } 

          // cycle through + check post
          $zbsStatusSetting = 'all'; $zbsStatusSettingPotential = array();
          $zbsStatuses = explode(',', $zbsStatusStr); 
          if (is_array($zbsStatuses)) foreach ($zbsStatuses as $statusStr){

              // permify
              $statusKey = strtolower(str_replace(' ','_',str_replace(':','_',$statusStr)));

              // check post
              if (isset($_POST['wpzbscrm_portaluser_group_'.$statusKey])) $zbsStatusSettingPotential[] = $statusKey;

          }

          if (count($zbsStatusSettingPotential) > 0) {

            // set that
            $zbsStatusSetting = $zbsStatusSettingPotential;

          }

          // update
          $updatedSettings['portalusers_status'] = $zbsStatusSetting;



    $updatedSettings['zbs_portal_email_content'] = ''; if (isset($_POST['zbs_portal_email_content']) && !empty($_POST['zbs_portal_email_content'])) $updatedSettings['zbs_portal_email_content'] = wp_kses_post(nl2br($_POST['zbs_portal_email_content']));

    // 2.84 wh
    $updatedSettings['portal_hidefields'] = ''; if (isset($_POST['wpzbscrm_portal_hidefields']) && !empty($_POST['wpzbscrm_portal_hidefields'])) $updatedSettings['portal_hidefields'] = sanitize_text_field( $_POST['wpzbscrm_portal_hidefields']);

    #} 2.86 ms
    $updatedSettings['portalpage'] = 0; if(isset($_POST['wpzbscrm_portalpage']) && !empty($_POST['wpzbscrm_portalpage'])) $updatedSettings['portalpage'] = (int)sanitize_text_field($_POST['wpzbscrm_portalpage']);

    #} 3.0 - Easy Access Links (hash urls)
    $updatedSettings['easyaccesslinks'] = 0; if (isset($_POST['wpzbscrm_easyaccesslinks']) && !empty($_POST['wpzbscrm_easyaccesslinks'])) $updatedSettings['easyaccesslinks'] = 1;

    #} Brutal update
    foreach ($updatedSettings as $k => $v) $zbs->settings->update($k,$v);

    #} $msg out!
    $sbupdated = true;

    #} Allow portal pro to hook into the save routine
    do_action('zbs_portal_settings_save');

    #} Reload
    $settings = $zbs->settings->getAll();
      
  }

  #} catch resets.
  if (isset($_GET['resetsettings']) && zeroBSCRM_isZBSAdminOrAdmin()) if ($_GET['resetsettings']==1){

    $nonceVerified = wp_verify_nonce( $_GET['_wpnonce'], 'resetclearzerobscrm' );

    if (!isset($_GET['imsure']) || !$nonceVerified){

        #} Needs to confirm!  
        $confirmAct = true;
        $actionStr        = 'resetsettings';
        $actionButtonStr    = __('Reset Settings to Defaults?',"zero-bs-crm");
        $confirmActStr      = __('Reset All Zero BS CRM Settings?',"zero-bs-crm");
        $confirmActStrShort   = __('Are you sure you want to reset these settings to the defaults?',"zero-bs-crm");
        $confirmActStrLong    = __('Once you reset these settings you cannot retrieve your previous settings.',"zero-bs-crm");

      } else {

        if ($nonceVerified){

            #} Reset
            $zbs->settings->resetToDefaults();

            #} Reload
            $settings = $zbs->settings->getAll();

            #} Msg out!
            $sbreset = true;

        }

      }

  } 


  if (!$confirmAct && !isset($rebuildCustomerNames)){

    ##WLREMOVE 
    if (current_user_can( 'admin_zerobs_manage_options' ) && !zeroBSCRM_isExtensionInstalled('clientportalpro')){

      // upsell button
      ?><a href="<?php echo $zbs->urls['extcpp']; ?>" target="_blank" class="ui button orange right floated"><?php _e('Get Portal PRO','zero-bs-crm'); ?></a><?php

    } 
    ##/WLREMOVE 

  ?>
    
        <p id="sbDesc"><?php _e('Setup your Client Portal here. You can do things like edit the email which is sent and choose your portal template.',"zero-bs-crm"); ?></p>
        <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
        <?php if (isset($sbreset)) if ($sbreset) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Reset',"zero-bs-crm")); echo '</div>'; } ?>
        
        <div id="sbA">

          <?php

             // check user has permalinks proper
             if (function_exists('zeroBSCRM_portal_plainPermaCheck')) zeroBSCRM_portal_plainPermaCheck();

          ?>
            <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=clients">
              <input type="hidden" name="editwplf" id="editwplf" value="1" />
              <?php 
                // add nonce
                wp_nonce_field( 'zbs-update-settings-clients');
              ?>

              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Client Portal Settings',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_showportalpoweredby"><?php _e('Show powered by ZBS',"zero-bs-crm"); ?>:</label><br /><?php _e('Help show us some love by displaying the powered by on your portal',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_showportalpoweredby" id="wpzbscrm_showportalpoweredby" value="1"<?php if (isset($settings['showportalpoweredby']) && $settings['showportalpoweredby'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_portalpage"><?php _e('Client Portal Page',"zero-bs-crm"); ?>:</label><br /><?php _e("Select the page with your client portal shortcode","zero-bs-crm");?><br/>
                            <?php ##WLREMOVE ?>
                            <a href="https://zerobscrm.com/kb/knowledge-base/how-does-the-client-portal-work/#client-portal-shortcode" target="_blank"><?php _e("Learn More", "zero-bs-crm"); ?></a>
                            <?php ##/WLREMOVE ?>
                          </td>
                          <td>
                            <?php

                                // reget
                                $portalPage = (int)zeroBSCRM_getSetting('portalpage',true);

                                // catch portal recreate
                                if (isset($_GET['recreateportalpage']) && isset($_GET['portalPageNonce']) && wp_verify_nonce($_GET['portalPageNonce'], 'recreate-portal-page')) {

                                    // recreate 
                                    $portalPage = zeroBSCRM_portal_checkCreatePage();

                                    if (!empty($portalPage) && $portalPage > 0){

                                      // success
                                      $newCPPageURL = admin_url('post.php?post='.$portalPage.'&action=edit');
                                      echo zeroBSCRM_UI2_messageHTML('info',__('Portal Page Created','zero-bs-crm'),__('Zero BS CRM successfully created a new page for the Client Portal.','zero-bs-crm').'<br /><br /><a href="'.$newCPPageURL.'" class="ui button primary">'.__('View Portal Page','zero-bs-crm').'</a>','info','new-portal-page');

                                    } else {

                                      // failed
                                      echo zeroBSCRM_UI2_messageHTML('warning',__('Portal Page Was Not Created','zero-bs-crm'),__('Zero BS CRM could not create a new page for the Client Portal. If this persists, please contact support.','zero-bs-crm'),'info','new-portal-page');
                                    
                                    }


                                }


                                $args = array('name' => 'wpzbscrm_portalpage', 'id' => 'wpzbscrm_portalpage','show_option_none' => __('No Portal Page Found!','zero-bs-crm'));
                                if($portalPage != -1){
                                  $args['selected'] = (int)$portalPage;
                                }else{
                                  $args['selected'] = 0;
                                }
                                wp_dropdown_pages($args); 

                                // recreate link
                                $recreatePortalPageURL = wp_nonce_url(admin_url('admin.php?page='.$zbs->slugs['settings'].'&tab=clients&recreateportalpage=1'), 'recreate-portal-page', 'portalPageNonce');

                                // detect missing page (e.g. it hasn't autocreated for some reason, or they deleted), and offer a 'make page' button
                                if (zeroBSCRM_portal_getPortalPage() == -1){

                                  echo zeroBSCRM_UI2_messageHTML('warning',__('No Portal Page Found!','zero-bs-crm'),__('Zero BS CRM could not find a published WordPress page associated with the Client Portal. Please recreate this page to continue using the Client Portal.','zero-bs-crm').'<br /><br /><a href="'.$recreatePortalPageURL.'" class="ui button primary">'.__('Recreate Portal Page','zero-bs-crm').'</a>','info','no-portal-page');

                                } else {

                                  // no need really?

                                }
                            ?>
                          </td>
                        </tr>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_easyaccesslinks"><?php _e('Allow Easy-Access Links',"zero-bs-crm"); ?>:</label><br /><?php _e('Tick if want logged-out users to be able to view quotes and invoices, and pay for invoices (via a secure hash URL) on the portal',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_easyaccesslinks" id="wpzbscrm_easyaccesslinks" value="1"<?php if (isset($settings['easyaccesslinks']) && $settings['easyaccesslinks'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr> 

                  </tbody>
                </table>

                <table class="table table-bordered table-striped wtab">
                   
                       <thead>
                        
                            <tr>
                                <th colspan="2" class="wmid"><?php _e('Client Portal User Accounts',"zero-bs-crm"); ?>:</th>
                            </tr>

                        </thead>

                    <tbody>

                        <tr>
                            <td colspan="2" class="wmid">
                              <?php _e('WordPress Users are required for each contact to access to your Client Portal.<br />You can generate these from any contact record, or automatically by selecting "Generate Users for all new contacts" below.',"zero-bs-crm"); ?>
                              <hr />
                              <?php _e('The following options all concern the automatic creation of client portal user accounts.',"zero-bs-crm"); ?>
                              <div class="zbs-explainer-ico"><i class="fa fa-id-card" aria-hidden="true"></i></div>
                            </td>
                        </tr>


                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_portalusers"><?php _e('Generate WordPress Users for new contacts',"zero-bs-crm"); ?>:</label><br /><?php _e('Note: This will automatically email the new contact a welcome email as soon as they\'re added.',"zero-bs-crm"); ?>.</td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_portalusers" id="wpzbscrm_portalusers" value="1"<?php if (isset($settings['portalusers']) && $settings['portalusers'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_portalusers"><?php _e('Only Generate Users for Statuses',"zero-bs-crm"); ?>:</label><br />
                          <br /><?php 
                          // reword suggested by omar - https://zerobscrmcommunity.slack.com/archives/C64JJ5B5W/p1544775973033900
                          //_e('If automatically generating users, you can restrict which users automatically get accounts here, (based on contact status).',"zero-bs-crm");
                          //_e('This will automatically disable/enable client portal accounts based on status changes.',"zero-bs-crm"); 
                          _e('Only users with the following status will have a portal account generated for them. If the status is not checked a user will not be generated. If the contact already has a portal account and they are moved to an unchecked status, their portal account will be disabled until they are moved to another checked status.','zero-bs-crm'); ?>
                          <br /><br /><strong><?php _e('Note: This only applies when Automatic Generation is ticked above.',"zero-bs-crm"); ?></strong></td>
                          <td style="width:540px" id="zbs-portal-users-statuses">
                            <?php
                              
                              #} retrieve value as simple CSV for now - simplistic at best.
                              $zbsStatusStr = ''; 
                              #} stored here: $settings['customisedfields']
                              if (isset($settings['customisedfields']['customers']['status']) && is_array($settings['customisedfields']['customers']['status'])) $zbsStatusStr = $settings['customisedfields']['customers']['status'][1];                                        
                              if (empty($zbsStatusStr)) {
                                #} Defaults:
                                global $zbsCustomerFields; if (is_array($zbsCustomerFields)) $zbsStatusStr = implode(',',$zbsCustomerFields['status'][3]);
                              } 

                              // setting - if set this'll be:
                              // "all"
                              // or array of status perms :)
                              $selectedStatuses = 'all'; 
                              if (isset($settings['portalusers_status'])) $selectedStatuses = $settings['portalusers_status'];

                              $zbsStatuses = explode(',', $zbsStatusStr);
                              if (is_array($zbsStatuses)) {

                                  // each status
                                  foreach ($zbsStatuses as $statusStr){

                                      // permify
                                      $statusKey = strtolower(str_replace(' ','_',str_replace(':','_',$statusStr)));

                                      // checked?
                                      $checked = false; 
                                      if (
                                            (!is_array($selectedStatuses) && $selectedStatuses == 'all')
                                            ||
                                            (is_array($selectedStatuses) && in_array($statusKey,$selectedStatuses))
                                          ) $checked = true;

                                    ?><div class="zbs-status">
                                        <input type="checkbox" value="1" name="wpzbscrm_portaluser_group_<?php echo $statusKey; ?>" id="wpzbscrm_portaluser_group_<?php echo $statusKey; ?>"<?php if ($checked) echo ' checked="checked"'; ?> />
                                        <label for="wpzbscrm_portaluser_group_<?php echo $statusKey; ?>"><?php echo $statusStr; ?></label>
                                      </div><?php

                                  }

                              } else _e('No Statuses Found',"zero-bs-crm");


                            ?>
                          </td>
                        </tr>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_portalusers_extrarole"><?php _e('Assign extra role when generating users',"zero-bs-crm"); ?>:</label><br /><?php _e('If you\'d like to add a secondary role to users which Zero BS CRM creates automatically, you can do so here. (This is useful for integration into other plugins relating to access.)',"zero-bs-crm"); ?>.</td>
                          <td style="width:540px">
                            <?php

                              $roles = zeroBSCRM_getWordPressRoles();

                              if (is_array($roles) && count($roles) > 0){

                                ?><select type="checkbox" name="wpzbscrm_portalusers_extrarole" id="wpzbscrm_portalusers_extrarole">
                                    <option value=""><?php _e('None',"zero-bs-crm"); ?></option>
                                    <option disabled="disabled" value="">====</option>
                                <?php

                                  foreach ($roles as $roleKey => $roleArr){

                                    // for their protection, gonna NOT include admin roles here..
                                    $blockedArr = array('zerobs_admin','administrator');
                                    // in fact no other zbs role... either...
                                    if (substr($roleKey,0,7) != 'zerobs_' && !in_array($roleKey, $blockedArr)){

                                      ?><option value="<?php echo $roleKey; ?>"<?php 
                                      if (isset($settings['portalusers_extrarole']) && $settings['portalusers_extrarole'] == $roleKey)  echo ' selected="selected"';
                                      ?>><?php 
                                      if (is_array($roleArr) && isset($roleArr['name']))
                                        echo $roleArr['name'];
                                      else
                                        echo $roleKey;
                                      ?></option><?php

                                    }


                                  }

                                ?></select><?php

                              } else echo '-';


                            ?>
                          </td>
                        </tr>

                        <tr>
                          <td width="94">                                      
                            <label for="zbs-status"><?php _e('Fields to hide on Portal',"zero-bs-crm"); ?></label><br /><?php _e('These fields will not be shown to the client on the client portal under "Your Details" (and so will not be editable).',"zero-bs-crm"); ?>.</td>
                          </td>
                          <td>
                            <?php 

                              #} retrieve value as simple CSV for now - simplistic at best.
                              $portalHiddenFields = 'status,email'; 
                              if (isset($settings['portal_hidefields'])) $portalHiddenFields = $settings['portal_hidefields'];                                        

                            ?>
                            <input type="text" name="wpzbscrm_portal_hidefields" id="wpzbscrm_portal_hidefields" value="<?php echo $portalHiddenFields; ?>" class="form-control" />
                            <small style="margin-top:4px"><?php _e("Default is","zero-bs-crm");?>:<br /><span style="background:#ceeaea;padding:0 4px">status,email</span></small>
                          </td>
                        </tr>

      
                      </tbody>

                  </table>

                  <?php 
                  #} Hook in for client portal settings additions
                  do_action('zbs_portal_after_settings'); 
                  ?>

                  <table class="table table-bordered table-striped wtab">
                    <tbody>

                      <?php

                        $portalLink = zeroBS_portal_link();

                      ?>

                        <tr>
                          <td colspan="2" class="wmid"><button type="submit" class="ui button primary"><?php _e('Save Settings',"zero-bs-crm"); ?></button><a target="_blank" href="<?php echo $portalLink;?>" class="ui button green"><?php _e('Preview Portal',"zero-bs-crm");?></a></td>
                        </tr>

                      </tbody>
                  </table>

              </form>


              <script type="text/javascript">

                jQuery(document).ready(function(){


                  // Uploader
                  // http://stackoverflow.com/questions/17668899/how-to-add-the-media-uploader-in-wordpress-plugin (3rd answer)                    
                  jQuery('#wpzbscrm_loginlogourlAdd').click(function(e) {
                      e.preventDefault();
                      var image = wp.media({ 
                          title: 'Upload Image',
                          // mutiple: true if you want to upload multiple files at once
                          multiple: false
                      }).open()
                      .on('select', function(e){
                          
                          // This will return the selected image from the Media Uploader, the result is an object
                          var uploaded_image = image.state().get('selection').first();
                          // We convert uploaded_image to a JSON object to make accessing it easier
                          // Output to the console uploaded_image
                          //console.log(uploaded_image);
                          var image_url = uploaded_image.toJSON().url;
                          // Let's assign the url value to the input field
                          jQuery('#wpzbscrm_loginlogourl').val(image_url);

                      });
                  });




                });


              </script>
              
      </div><?php 
      
      }else {

          ?><div id="clpSubPage" class="whclpActionMsg six">
            <p><strong><?php echo $confirmActStr; ?></strong></p>
              <h3><?php echo $confirmActStrShort; ?></h3>
              <?php echo $confirmActStrLong; ?><br /><br />
              <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo wp_nonce_url('?page='.$zbs->slugs['settings'].'&'.$actionStr.'=1&imsure=1','resetclearzerobscrm'); ?>';"><?php echo $actionButtonStr; ?></button>
              <button type="button" class="button button-large" onclick="javascript:window.location='?page=<?php echo $zbs->slugs['settings']; ?>';"><?php _e("Cancel","zero-bs-crm"); ?></button>
              <br />
        </div><?php 
      } 

}



function zeroBSCRM_html_settings_forms(){

  global $wpdb, $zbs;  #} Req

  $confirmAct = false;
  $settings = $zbs->settings->getAll();   

  #} Act on any edits!
  if (isset($_POST['editwplf']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-forms' );

    #} 1.1.17 - gcaptcha (should be moved into a "Forms" tab later)
    $updatedSettings['showformspoweredby'] = 0; if (isset($_POST['wpzbscrm_showformspoweredby']) && !empty($_POST['wpzbscrm_showformspoweredby'])) $updatedSettings['showformspoweredby'] = 1;    
    $updatedSettings['usegcaptcha'] = 0; if (isset($_POST['wpzbscrm_usegcaptcha']) && !empty($_POST['wpzbscrm_usegcaptcha'])) $updatedSettings['usegcaptcha'] = 1;
    $updatedSettings['gcaptchasitekey'] = 0; if (isset($_POST['wpzbscrm_gcaptchasitekey']) && !empty($_POST['wpzbscrm_gcaptchasitekey'])) $updatedSettings['gcaptchasitekey'] = sanitize_text_field($_POST['wpzbscrm_gcaptchasitekey']);
    $updatedSettings['gcaptchasitesecret'] = 0; if (isset($_POST['wpzbscrm_gcaptchasitesecret']) && !empty($_POST['wpzbscrm_gcaptchasitesecret'])) $updatedSettings['gcaptchasitesecret'] = sanitize_text_field($_POST['wpzbscrm_gcaptchasitesecret']);

    #} Brutal update
    foreach ($updatedSettings as $k => $v) $zbs->settings->update($k,$v);

    #} $msg out!
    $sbupdated = true;

    #} Reload
    $settings = $zbs->settings->getAll();
      
  }

  #} catch resets.
  if (isset($_GET['resetsettings']) && zeroBSCRM_isZBSAdminOrAdmin()) if ($_GET['resetsettings']==1){

    $nonceVerified = wp_verify_nonce( $_GET['_wpnonce'], 'resetclearzerobscrm' );

    if (!isset($_GET['imsure']) || !$nonceVerified){

        #} Needs to confirm!  
        $confirmAct = true;
        $actionStr        = 'resetsettings';
        $actionButtonStr    = __('Reset Settings to Defaults?',"zero-bs-crm");
        $confirmActStr      = __('Reset All Zero BS CRM Settings?',"zero-bs-crm");
        $confirmActStrShort   = __('Are you sure you want to reset these settings to the defaults?',"zero-bs-crm");
        $confirmActStrLong    = __('Once you reset these settings you cannot retrieve your previous settings.',"zero-bs-crm");

      } else {


        if ($nonceVerified){

            #} Reset
            $zbs->settings->resetToDefaults();

            #} Reload
            $settings = $zbs->settings->getAll();

            #} Msg out!
            $sbreset = true;

        }

      }

  } 


  if (!$confirmAct && !isset($rebuildCustomerNames)){

  ?>
    
        <p id="sbDesc"><?php _e('From this page you can modify the settings for ZBS CRM Front-end Forms. Want to use other forms like Contact Form 7? Check out our ',"zero-bs-crm"); ?> <a href="<?php echo $zbs->urls['products']; ?>" target="_blank"><?php _e("Form Extensions","zero-bs-crm");?></a></p>

        <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
        <?php if (isset($sbreset)) if ($sbreset) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Reset',"zero-bs-crm")); echo '</div>'; } ?>
        
        <div id="sbA">
          <pre><?php // print_r($settings); ?></pre>

            <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=forms">
              <input type="hidden" name="editwplf" id="editwplf" value="1" />
              <?php 
                // add nonce
                wp_nonce_field( 'zbs-update-settings-forms');
              ?>

              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Forms Settings',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_showformspoweredby"><?php _e('Show powered by ZBS',"zero-bs-crm"); ?>:</label><br /><?php _e('Help show us some love by displaying the powered by on your forms',"zero-bs-crm"); ?>.</td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_showformspoweredby" id="wpzbscrm_showformspoweredby" value="1"<?php if (isset($settings['showformspoweredby']) && $settings['showformspoweredby'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_usegcaptcha"><?php _e('Enable reCaptcha',"zero-bs-crm"); ?>:</label><br /><?php _e('This setting enabled reCaptcha for your front end forms. If you\'d like to use this to avoid spam, please sign up for a site key and secret',"zero-bs-crm"); ?> <a href="https://www.google.com/recaptcha/admin#list" target="_blank">here</a>.</td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_usegcaptcha" id="wpzbscrm_usegcaptcha" value="1"<?php if (isset($settings['usegcaptcha']) && $settings['usegcaptcha'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_gcaptchasitekey"><?php _e('reCaptcha Site Key',"zero-bs-crm"); ?>:</label><br /></td>
                          <td><input type="text" class="winput form-control" name="wpzbscrm_gcaptchasitekey" id="wpzbscrm_gcaptchasitekey" value="<?php if (isset($settings['gcaptchasitekey']) && !empty($settings['gcaptchasitekey'])) echo $settings['gcaptchasitekey']; ?>" placeholder="e.g. 6LekCyoTAPPPALWpHONFsRO5RQPOqoHfehdb4iqG" /></td>
                        </tr>
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_gcaptchasitesecret"><?php _e('reCaptcha Site Secret',"zero-bs-crm"); ?>:</label><br /></td>
                          <td><input type="text" class="winput form-control" name="wpzbscrm_gcaptchasitesecret" id="wpzbscrm_gcaptchasitesecret" value="<?php if (isset($settings['gcaptchasitesecret']) && !empty($settings['gcaptchasitesecret'])) echo $settings['gcaptchasitesecret']; ?>" placeholder="e.g. 6LekCyoTAAPPAJbQ1rq81117nMoo9y45fB3OLJVx" /></td>
                        </tr>
      
                      </tbody>

                  </table>

          

                  <table class="table table-bordered table-striped wtab">
                    <tbody>

                        <tr>
                          <td colspan="2" class="wmid"><button type="submit" class="ui button primary"><?php _e('Save Settings',"zero-bs-crm"); ?></button></td>
                        </tr>

                      </tbody>
                  </table>

              </form>


              <script type="text/javascript">

                jQuery(document).ready(function(){


                  // Uploader
                  // http://stackoverflow.com/questions/17668899/how-to-add-the-media-uploader-in-wordpress-plugin (3rd answer)                    
                  jQuery('#wpzbscrm_loginlogourlAdd').click(function(e) {
                      e.preventDefault();
                      var image = wp.media({ 
                          title: 'Upload Image',
                          // mutiple: true if you want to upload multiple files at once
                          multiple: false
                      }).open()
                      .on('select', function(e){
                          
                          // This will return the selected image from the Media Uploader, the result is an object
                          var uploaded_image = image.state().get('selection').first();
                          // We convert uploaded_image to a JSON object to make accessing it easier
                          // Output to the console uploaded_image
                          //console.log(uploaded_image);
                          var image_url = uploaded_image.toJSON().url;
                          // Let's assign the url value to the input field
                          jQuery('#wpzbscrm_loginlogourl').val(image_url);

                      });
                  });




                });


              </script>
              
      </div><?php 
      
      }else {

          ?><div id="clpSubPage" class="whclpActionMsg six">
            <p><strong><?php echo $confirmActStr; ?></strong></p>
              <h3><?php echo $confirmActStrShort; ?></h3>
              <?php echo $confirmActStrLong; ?><br /><br />
              <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo wp_nonce_url('?page='.$zbs->slugs['settings'].'&'.$actionStr.'=1&imsure=1','resetclearzerobscrm'); ?>';"><?php echo $actionButtonStr; ?></button>
              <button type="button" class="button button-large" onclick="javascript:window.location='?page=<?php echo $zbs->slugs['settings']; ?>';"><?php _e("Cancel","zero-bs-crm"); ?></button>
              <br />
        </div><?php 
      } 

}


#} INVOICES
# moved to extension named func - function zeroBSCRM_html_settings_invoices(){
function zeroBSCRM_extensionhtml_settings_invbuilder(){

  global $wpdb, $zbs;  #} Req

  $confirmAct = false;
  $settings = $zbs->settings->getAll();   

  #} #WH OR - need these lists?

    #} load currency list                           
    global $whwpCurrencyList;
    if(!isset($whwpCurrencyList)) require_once(ZEROBSCRM_PATH . 'includes/wh.currency.lib.php');
    /*
    #} load country list                            
    global $whwpCountryList;
    if(!isset($whwpCountryList)) require_once(ZEROBSCRM_PATH . 'includes/wh.countrycode.lib.php');

    */

  #} Act on any edits!
  if (isset($_POST['editwplf']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-invbuilder' );

    /* Moved to bizinfo settings page 16/7/18
    #} Invoice Logo
        $updatedSettings['invoicelogourl'] = ''; if (isset($_POST['wpzbscrm_invoicelogourl']) && !empty($_POST['wpzbscrm_invoicelogourl'])) $updatedSettings['invoicelogourl'] = sanitize_text_field($_POST['wpzbscrm_invoicelogourl']);

    #} Invoice Chunks
    $updatedSettings['businessname'] = ''; if (isset($_POST['businessname'])) $updatedSettings['businessname'] = zeroBSCRM_textProcess($_POST['businessname']);
    $updatedSettings['businessyourname'] = ''; if (isset($_POST['businessyourname'])) $updatedSettings['businessyourname'] = zeroBSCRM_textProcess($_POST['businessyourname']);
    $updatedSettings['businessyouremail'] = ''; if (isset($_POST['businessyouremail'])) $updatedSettings['businessyouremail'] = zeroBSCRM_textProcess($_POST['businessyouremail']);
    $updatedSettings['businessyoururl'] = ''; if (isset($_POST['businessyoururl'])) $updatedSettings['businessyoururl'] = zeroBSCRM_textProcess($_POST['businessyoururl']);
    */ 


    
    $updatedSettings['defaultref'] = ''; if (isset($_POST['defaultref'])) $updatedSettings['defaultref'] = zeroBSCRM_textProcess($_POST['defaultref']);  
    $updatedSettings['businessextra'] = ''; if (isset($_POST['businessextra'])) $updatedSettings['businessextra'] = zeroBSCRM_textProcess($_POST['businessextra']);
    $updatedSettings['paymentinfo'] = ''; if (isset($_POST['paymentinfo'])) $updatedSettings['paymentinfo'] = zeroBSCRM_textProcess($_POST['paymentinfo']);
    $updatedSettings['paythanks'] = ''; if (isset($_POST['paythanks'])) $updatedSettings['paythanks'] = zeroBSCRM_textProcess($_POST['paythanks']);

    #} Invoice sending settings
    $updatedSettings['invfromemail'] = ''; if (isset($_POST['invfromemail'])) $updatedSettings['invfromemail'] = zeroBSCRM_textProcess($_POST['invfromemail']);
    $updatedSettings['invfromname'] = ''; if (isset($_POST['invfromname'])) $updatedSettings['invfromname'] = zeroBSCRM_textProcess($_POST['invfromname']);

    #} Hide Invoice ID
    $updatedSettings['invid'] = 0; if (isset($_POST['wpzbscrm_invid']) && !empty($_POST['wpzbscrm_invid'])) $updatedSettings['invid'] = 1;
  
    #} Allow Invoice Hash (view and pay without being logged into the portal)
    // moved to client portal settings 3.0 - $updatedSettings['invhash'] = 0; if (isset($_POST['wpzbscrm_invhash']) && !empty($_POST['wpzbscrm_invhash'])) $updatedSettings['invhash'] = 1;
  
    #} Tax etc
    $updatedSettings['invtax'] = 0; if (isset($_POST['wpzbscrm_invtax']) && !empty($_POST['wpzbscrm_invtax'])) $updatedSettings['invtax'] = 1;
    $updatedSettings['invdis'] = 0; if (isset($_POST['wpzbscrm_invdis']) && !empty($_POST['wpzbscrm_invdis'])) $updatedSettings['invdis'] = 1;
    $updatedSettings['invpandp'] = 0; if (isset($_POST['wpzbscrm_invpandp']) && !empty($_POST['wpzbscrm_invpandp'])) $updatedSettings['invpandp'] = 1;

    #} Statements
    $updatedSettings['statementextra'] = ''; if (isset($_POST['zbsi_statementextra'])) $updatedSettings['statementextra'] = zeroBSCRM_textProcess($_POST['zbsi_statementextra']);

    #} Brutal update
    foreach ($updatedSettings as $k => $v) $zbs->settings->update($k,$v);

    #} $msg out!
    $sbupdated = true;

    #} Reload
    $settings = $zbs->settings->getAll();
      
  }

  #} catch resets.
  if (isset($_GET['resetsettings'])) if ($_GET['resetsettings']==1){

    $nonceVerified = wp_verify_nonce( $_GET['_wpnonce'], 'resetclearzerobscrm' );

    if (!isset($_GET['imsure']) || !$nonceVerified){

        #} Needs to confirm!  
        $confirmAct = true;
        $actionStr        = 'resetsettings';
        $actionButtonStr    = __('Reset Settings to Defaults?',"zero-bs-crm");
        $confirmActStr      = __('Reset All Zero BS CRM Settings?',"zero-bs-crm");
        $confirmActStrShort   = __('Are you sure you want to reset these settings to the defaults?',"zero-bs-crm");
        $confirmActStrLong    = __('Once you reset these settings you cannot retrieve your previous settings.',"zero-bs-crm");

      } else {


        if ($nonceVerified){

            #} Reset
            $zbs->settings->resetToDefaults();

            #} Reload
            $settings = $zbs->settings->getAll();

            #} Msg out!
            $sbreset = true;

        }

      }

  } 


  if (!$confirmAct && !isset($rebuildCustomerNames)){

  ?>
    
        <p id="sbDesc"><?php _e('Setup and control how the invoicing functionality works in your ZBS CRM. If you have any feedback on our invoicing functionality please do let us know.',"zero-bs-crm"); ?></p>

        <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
        <?php if (isset($sbreset)) if ($sbreset) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Reset',"zero-bs-crm")); echo '</div>'; } ?>
        
        <div id="sbA">
          <pre><?php // print_r($settings); ?></pre>

            <form method="post">
              <input type="hidden" name="editwplf" id="editwplf" value="1" />
              <?php 
                // add nonce
                wp_nonce_field( 'zbs-update-settings-invbuilder');
              ?>

               <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('General',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      <tbody>

                       <tr>
                          <td class="wfieldname"><label for="defaultref"><?php _e('Default Reference',"zero-bs-crm"); ?>:</label><br /><?php _e('The default reference on invoices',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><textarea class="winput form-control" name="defaultref" id="defaultref"  placeholder="e.g. inv-" ><?php if (isset($settings['defaultref']) && !empty($settings['defaultref'])) echo $settings['defaultref']; ?></textarea></td>
                        </tr>


                        <tr>
                            <td class="wfieldname"><label for="businessextra"><?php _e('Extra Invoice Info',"zero-bs-crm"); ?>:</label><br /><?php _e('This information is (optionally) added to your invoice',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><textarea class="winput form-control" name="businessextra" id="businessextra"  placeholder="<?php _e('e.g. Your Address','zero-bs-crm'); ?>" ><?php if (isset($settings['businessextra']) && !empty($settings['businessextra'])) echo $settings['businessextra']; ?></textarea></td>
                        </tr>


                        <tr>
                            <td class="wfieldname"><label for="paymentinfo"><?php _e('Payment Info',"zero-bs-crm"); ?>:</label><br /><?php _e('This information is (optionally) added to your invoice',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><textarea class="winput form-control" name="paymentinfo" id="paymentinfo"  placeholder="<?php _e('e.g. BACS details','zero-bs-crm'); ?>" ><?php if (isset($settings['paymentinfo']) && !empty($settings['paymentinfo'])) echo $settings['paymentinfo']; ?></textarea></td>
                        </tr>


                        <tr>
                            <td class="wfieldname"><label for="paythanks"><?php _e('Thank You',"zero-bs-crm"); ?>:</label><br /><?php _e('This information is (optionally) added to your invoice',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><textarea class="winput form-control" name="paythanks" id="paythanks"  placeholder="<?php _e('e.g. Thank you for your custom. If you have any questions let us know','zero-bs-crm'); ?>" ><?php if (isset($settings['paythanks']) && !empty($settings['paythanks'])) echo $settings['paythanks']; ?></textarea></td>
                        </tr>

                        <tr>
                          <td colspan="2">
                            <p style="text-align:center"><?php _e('Looking for easy-pay/easy-access invoice links? You can now turn easy-access links on via the client portal settings page','zero-bs-crm'); ?></p>
                            <p style="text-align:center">
                              <a href="<?php echo zbsLink($zbs->slugs['settings']); echo '&tab=clients'; ?>" class="ui mini button blue"><?php _e('View Client Portal Settings','zero-bs-crm'); ?></a>
                              <?php ##WLREMOVE ?>
                              <a href="<?php echo $zbs->urls['easyaccessguide']; ?>" target="_blank" class="ui mini button green"><?php _e('View Easy-Access Links Guide','zero-bs-crm'); ?></a>
                              <?php ##/WLREMOVE ?>
                            </p>
                          </td>
                        </tr>

                      </tbody>

                </table>
   

               <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Statements',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      <tbody>

                        <tr>
                            <td class="wfieldname"><label for="zbsi_statementextra"><?php _e('Extra Statement Info',"zero-bs-crm"); ?>:</label><br /><?php _e('This information is (optionally) added to your statements (e.g. How to pay)',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><textarea class="winput form-control" name="zbsi_statementextra" id="zbsi_statementextra"  placeholder="<?php _e('e.g. BACS details','zero-bs-crm'); ?>" ><?php if (isset($settings['statementextra']) && !empty($settings['statementextra'])) echo $settings['statementextra']; ?></textarea></td>
                        </tr>


                      </tbody>

                </table>
         
               <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Additional settings',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_invid"><?php _e('Hide Invoice ID',"zero-bs-crm"); ?>:</label><br /><?php _e('Tick if want to hide the invoice ID (invoice ID increments automatically)',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_invid" id="wpzbscrm_invid" value="1"<?php if (isset($settings['invid']) && $settings['invid'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr> 
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_tax"><?php _e('Show tax on invoices',"zero-bs-crm"); ?>:</label><br /><?php _e('Tick if you need to charge tax',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_invtax" id="wpzbscrm_invtax" value="1"<?php if (isset($settings['invtax']) && $settings['invtax'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>                     
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_discount"><?php _e('Show discount on invoices',"zero-bs-crm"); ?>:</label><br /><?php _e('Tick if you want to add discounts',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_invdis" id="wpzbscrm_invdis" value="1"<?php if (isset($settings['invdis']) && $settings['invdis'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr> 
                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_pandp"><?php _e('Show P&P on invoices',"zero-bs-crm"); ?> (<?php _e('Shipping',"zero-bs-crm"); ?>):</label><br /><?php _e('Tick if you want to add postage and packaging',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="checkbox" class="winput form-control" name="wpzbscrm_invpandp" id="wpzbscrm_invpandp" value="1"<?php if (isset($settings['invpandp']) && $settings['invpandp'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr> 
                      </tbody>

                  </table>

          

                  <table class="table table-bordered table-striped wtab">
                    <tbody>

                        <tr>
                          <td colspan="2" class="wmid"><button type="submit" class="ui button primary"><?php _e('Save Settings',"zero-bs-crm"); ?></button></td>
                        </tr>

                      </tbody>
                  </table>

              </form>


              <script type="text/javascript">

                jQuery(document).ready(function(){

                  jQuery('#wpzbscrm_invpro_pay').change(function(){

                      if (jQuery(this).val() == "1"){
                        jQuery('.zbscrmInvProPayPalReq').hide();
                        jQuery('.zbscrmInvProStripeReq').show();
                      } else {
                        jQuery('.zbscrmInvProPayPalReq').show();
                        jQuery('.zbscrmInvProStripeReq').hide();
                      }


                  });

                });


              </script>
              
      </div><?php 
      
      } else {

          ?><div id="clpSubPage" class="whclpActionMsg six">
            <p><strong><?php echo $confirmActStr; ?></strong></p>
              <h3><?php echo $confirmActStrShort; ?></h3>
              <?php echo $confirmActStrLong; ?><br /><br />
              <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo wp_nonce_url('?page='.$zbs->slugs['settings'].'&'.$actionStr.'=1&imsure=1','resetclearzerobscrm'); ?>';"><?php echo $actionButtonStr; ?></button>
              <button type="button" class="button button-large" onclick="javascript:window.location='?page=<?php echo $zbs->slugs['settings']; ?>';"><?php _e("Cancel","zero-bs-crm"); ?></button>
              <br />
        </div><?php 
      } 
}


function zeroBSCRM_extensionhtml_settings_quotebuilder(){

  global $wpdb, $zbs;  #} Req

  $confirmAct = false;
  $settings = $zbs->settings->getAll();    
  
  #} Act on any edits!
  if (isset($_POST['editwplf']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-quotebuilder' );

    #} 1.1.17 - gcaptcha (should be moved into a "Forms" tab later)


    // taken out 2.96.7 //$updatedSettings['showpoweredbyquotes'] = 0; if (isset($_POST['wpzbscrm_showpoweredbyquotes']) && !empty($_POST['wpzbscrm_showpoweredbyquotes'])) $updatedSettings['showpoweredbyquotes'] = 1;    
    $updatedSettings['usequotebuilder'] = 0; if (isset($_POST['wpzbscrm_usequotebuilder']) && !empty($_POST['wpzbscrm_usequotebuilder'])) $updatedSettings['usequotebuilder'] = 1;

    #} Brutal update
    foreach ($updatedSettings as $k => $v) $zbs->settings->update($k,$v);

    #} $msg out!
    $sbupdated = true;

    #} Reload
    $settings = $zbs->settings->getAll();
      
  }

  #} catch resets.
  if (isset($_GET['resetsettings']) && zeroBSCRM_isZBSAdminOrAdmin()) if ($_GET['resetsettings'] ==1){

    $nonceVerified = wp_verify_nonce( $_GET['_wpnonce'], 'resetclearzerobscrm' );

    if (!isset($_GET['imsure']) || !$nonceVerified){

        #} Needs to confirm!  
        $confirmAct = true;
        $actionStr        = 'resetsettings';
        $actionButtonStr    = __('Reset Settings to Defaults?',"zero-bs-crm");
        $confirmActStr      = __('Reset All Zero BS CRM Settings?',"zero-bs-crm");
        $confirmActStrShort   = __('Are you sure you want to reset these settings to the defaults?',"zero-bs-crm");
        $confirmActStrLong    = __('Once you reset these settings you cannot retrieve your previous settings.',"zero-bs-crm");

      } else {


        if ($nonceVerified){

            #} Reset
            $zbs->settings->resetToDefaults();

            #} Reload
            $settings = $zbs->settings->getAll();

            #} Msg out!
            $sbreset = true;

        }

      }

  } 


  if (!$confirmAct && !isset($rebuildCustomerNames)){

  ?>
        <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
        <?php if (isset($sbreset)) if ($sbreset) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Reset',"zero-bs-crm")); echo '</div>'; } ?>
        
        <div id="sbA">

            <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=quotebuilder">
              <input type="hidden" name="editwplf" id="editwplf" value="1" />
              <?php 
                // add nonce
                wp_nonce_field( 'zbs-update-settings-quotebuilder');
              ?>
              <style>td{width:50%;}</style>
              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Quotes Settings',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_usequotebuilder"><?php _e('Enable Quote Builder',"zero-bs-crm"); ?>:</label><br /><?php _e('Disabling this will remove the quote-writing element of Quotes. This is useful if you\'re only using ZBS to log quotes, not write them.',"zero-bs-crm"); ?>.</td>
                          <td style=""><input type="checkbox" class="winput form-control" name="wpzbscrm_usequotebuilder" id="wpzbscrm_usequotebuilder" value="1"<?php if (isset($settings['usequotebuilder']) && $settings['usequotebuilder'] == "1") echo ' checked="checked"'; ?> /></td>
                        </tr>

                        <tr>
                          <td colspan="2">
                            <p style="text-align:center"><?php _e('Looking for easy-access quote links? You can now turn easy-access links on via the client portal settings page','zero-bs-crm'); ?></p>
                            <p style="text-align:center">
                              <a href="<?php echo zbsLink($zbs->slugs['settings']); echo '&tab=clients'; ?>" class="ui mini button blue"><?php _e('View Client Portal Settings','zero-bs-crm'); ?></a>
                              <?php ##WLREMOVE ?>
                              <a href="<?php echo $zbs->urls['easyaccessguide']; ?>" target="_blank" class="ui mini button green"><?php _e('View Easy-Access Links Guide','zero-bs-crm'); ?></a>
                              <?php ##/WLREMOVE ?>
                            </p>
                          </td>
                        </tr>
                      </tbody>
                  </table>
                  <table class="table table-bordered table-striped wtab">
                    <tbody>
                        <tr>
                          <td colspan="2" class="wmid"><button type="submit" class="ui button primary"><?php _e('Save Settings',"zero-bs-crm"); ?></button></td>
                        </tr>
                      </tbody>
                  </table>
              </form>


              
      </div><?php 
      
      }else {

          ?><div id="clpSubPage" class="whclpActionMsg six">
            <p><strong><?php echo $confirmActStr; ?></strong></p>
              <h3><?php echo $confirmActStrShort; ?></h3>
              <?php echo $confirmActStrLong; ?><br /><br />
              <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo wp_nonce_url('?page='.$zbs->slugs['settings'].'&'.$actionStr.'=1&imsure=1','resetclearzerobscrm'); ?>';"><?php echo $actionButtonStr; ?></button>
              <button type="button" class="button button-large" onclick="javascript:window.location='?page=<?php echo $zbs->slugs['settings']; ?>';"><?php _e("Cancel","zero-bs-crm"); ?></button>
              <br />
        </div><?php 
      } 

}


function zeroBSCRM_html_systemstatus(){

  global $wpdb, $zbs;  #} Req

  $normalSystemStatusPage = true;

  // catch v3 migration notes
  if (isset($_GET['v3migrationlog'])){

    // kill normal page
    $normalSystemStatusPage = false;

    if ($zbs->isDAL3()){

        // check for any migration 'errors' + also expose here.
        $errors = get_option('zbs_db_migration_300_errstack', array());
          
        $bodyStr = '<h2>'.__('Migration Completion Report','zero-bs-crm').'</h2>';

        if (is_array($errors) && count($errors) > 0){
            
            // this is a clone of what gets sent to them by email, but reusing the html gen here

            // build report
            $bodyStr = '<h2>'.__('Migration Completion Report','zero-bs-crm').'</h2>';
            $bodyStr .= '<p style="font-size:1.3em">'.__('Unfortunately there were some migration errors, which are shown below. The error messages should explain any conflicts found when merging, (this has also been emailed to you for your records).','zero-bs-crm').' '.__('Please visit the migration support page','zero-bs-crm').' <a href="'.$zbs->urls['db3migrate'].'" target="_blank">'.__('here','zero-bs-crm').'</a> '.__('if you require any further information.','zero-bs-crm').'</p>';            
            $bodyStr .= '<div style="position: relative;background: #FFFFFF;box-shadow: 0px 1px 2px 0 rgba(34,36,38,0.15);margin: 1rem 0em;padding: 1em 1em;border-radius: 0.28571429rem;border: 1px solid rgba(34,36,38,0.15);margin-right:1em !important"><h3>'.__('Non-critical Errors:','zero-bs-crm').'</h3>';

            // expose Timeouts
            $timeoutIssues = zeroBSCRM_getSetting('migration300_timeout_issues'); 
            if (isset($timeoutIssues) && $timeoutIssues == 1) echo zeroBSCRM_UI2_messageHTML('warning',__('Timeout','zero-bs-crm'),__('While this migration ran it hit one or more timeouts. This indicates that your server may be unperformant at scale with ZBS CRM','zero-bs-crm'));

              // list errors
              foreach ($errors as $error){

                $bodyStr .= '<div class="ui vertical segment">';
                  $bodyStr .= '<div class="ui grid">';
                    $bodyStr .= '<div class="two wide column right aligned"><span class="ui orange horizontal label">['.$error[0].']</span></div>';
                    $bodyStr .= '<div class="fourteen wide column"><p style="font-size: 1.1em;">'.$error[1].'</p></div>';
                  $bodyStr .= '</div>';
                $bodyStr .= '</div>';

                

              }

            $bodyStr .= '</div>';


        } else {

          $bodyStr .= zeroBSCRM_UI2_messageHTML('info',__('V3.0 Migration Completed Successfully','zero-bs-crm'),__('There were no errors when migrating your CRM install to v3.0','zero-bs-crm'),'','zbs-succcessfulyv3');

        }

        echo $bodyStr;

        ?><p style="text-align:center;margin:2em"><a href="<?php echo zeroBSCRM_getAdminURL($zbs->slugs['systemstatus']); ?>" class="ui button blue"><?php _e('Back to System Status',"zero-bs-crm"); ?></a></p><?php

    } else {

        // Not migrated yet? What?
        echo '<p>'.__('You have not yet migrated to v3.0','zero-bs-crm').'</p>';

    }

  }

  if ($normalSystemStatusPage){


  $settings = $zbs->settings->getAll();   

    /* debug: see rewrite rules
    $rules = get_option( 'rewrite_rules' );
    echo '<pre>'; print_r($rules); echo '</pre>';
    */

    // catch tools:
    if (current_user_can('admin_zerobs_manage_options') && isset($_GET['resetuserroles']) && wp_verify_nonce( $_GET['_wpnonce'], 'resetuserroleszerobscrm' ) ){

          // roles
        zeroBSCRM_clearUserRoles();

        // roles + 
        zeroBSCRM_addUserRoles();

        // flag
        $userRolesRebuilt = true;
    }

    // check for, and prep any general sys status errs:
    $generalErrors = array();

      // migration blocker (failed migrations looping)
      $migBlocks = get_option( 'zbsmigrationblockerrors', false);
      if ($migBlocks !== false && !empty($migBlocks)) {
        $generalErrors['migrationblock'] = __('A migration has been blocked from completing. Please contact support.','zero-bs-crm').' (#'.$migBlocks.')';

        // add ability to 'reset migration block' (note this is fired pre-migrations in ZeroBSCRM.Migrations.php)
        $generalErrors['migrationblock'] .= '<br /><a href="'.wp_nonce_url('?page='.$zbs->slugs['systemstatus'].'&resetmigrationblock=1','resetmigrationblock').'">'.__('Retry the Migration','zero-bs-crm').'</a>';

      }

      // hard-check database tables & report

        global $ZBSCRM_t,$wpdb;
        $missingTables = array();
        $tablesExist = $wpdb->get_results("SHOW TABLES LIKE '".$ZBSCRM_t['keys']."'");
        if (count($tablesExist) < 1) $missingTables[] = $ZBSCRM_t['keys'];

        // then we cycle through our tables :) - means all keys NEED to be kept up to date :) 
        foreach ($ZBSCRM_t as $tableKey => $tableName){
            $tablesExist = $wpdb->get_results("SHOW TABLES LIKE '".$tableName."'");
            if (count($tablesExist) < 1) {
              $missingTables[] = $tableName;
            }

        }

        // missing tables?
        if (count($missingTables) > 0){

            $generalErrors['missingtables'] = __('ZBS CRM has failed creating the tables it needs to operate. Please contact support.','zero-bs-crm').' (#306)';
            $generalErrors['missingtables'] .= '<br />'.__('The following tables could not be created:','zero-bs-crm').' ('.implode(', ',$missingTables).')';

        }

  ?>
    
        <p id="sbDesc"><?php _e('This page allows easy access for the various system status variables related to your WordPress install and ZBS CRM.',"zero-bs-crm"); ?></p>

        <?php if (isset($userRolesRebuilt) && $userRolesRebuilt) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('User Roles Rebuilt',"zero-bs-crm")); echo '</div>'; } ?>
        
        <?php if (count($generalErrors) > 0){

          foreach ($generalErrors as $err) echo zeroBSCRM_UI2_messageHTML('warning','',$err,'','');

        } ?>

        <div id="sbA" style="margin-right:1em">


                  <?php 

                  #CLEARS OUT MIGRATION HISTORY :o $zbs->settings->update('migrations',array());

                  #================================================================
                  #== ZBS relative
                  #================================================================

                      $zbsEnvList = array(

                          'corever' => 'CRM Core Version',
                          'dbver' => 'Database Version',
                          'dalver' => 'DAL Version',
                          'mysql' => 'MySQL Version',
                          'sqlrights' => 'SQL Permissions',
                          # clear auto-draft
                          'autodraftgarbagecollect' => 'Auto-draft Garbage Collection',
                          'locale' => 'Locale',
                          'assetdir' => 'Asset Upload Directory',
                          'local' => 'Server Connectivity',
                          'localtime' => 'DateTime Setting',
                          'devmode' => 'Dev/Production Mode',
                          'permalinks'  => 'Pretty Permalinks'
                        ); 

                      if (count($zbsEnvList)){ 
                  ?>
              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('ZBS Environment',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <?php foreach ($zbsEnvList as $envCheckKey => $envCheckName){ 

                          #} Retrieve
                          $result = zeroBSCRM_checkSystemFeat($envCheckKey,true);

                          ?>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_env_<?php echo $envCheckKey; ?>"><?php _e($envCheckName,"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><?php 
                          if (!$result[0] && $envCheckKey != 'devmode') echo '<div class="ui yellow label">'.__('Warning','zero-bs-crm').'</div>';
                          ?><?php echo $result[1]; ?></td>
                        </tr>

                        <?php } ?>
      
                      </tbody>

                  </table>


                  <?php } ?>


                  <?php 

                  #================================================================
                  #== Server relative
                  #================================================================

                      $servEnvList = array(
                        'serverdefaulttime' => 'Server Default Timezone',
                        'curl'    => 'CURL',
                        'zlib'    =>'zlib (Zip Library)',
                        'dompdf'  =>'PDF Engine',
                        'pdffonts'  =>'PDF Font Set',
                        'phpver'  => 'PHP Version',
                        'memorylimit'  => 'Memory Limit',
                        'executiontime'  => 'Max Execution Time',
                        'postmaxsize'  => 'Max File POST',
                        'uploadmaxfilesize' => 'Max File Upload Size',
                        'wpuploadmaxfilesize' => 'WordPress Max File Upload Size'
                        );

                      if (count($servEnvList)){ 
                  ?>
              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Server Environment',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <?php foreach ($servEnvList as $envCheckKey => $envCheckName){ 

                          #} Retrieve
                          $result = zeroBSCRM_checkSystemFeat($envCheckKey,true);

                          ?>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_env_<?php echo $envCheckKey; ?>"><?php _e($envCheckName,"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><?php echo $result[1]; ?></td>
                        </tr>

                        <?php } ?>

                        <?php do_action('zbs_server_checks'); ?>
      
                      </tbody>

                  </table>

                  <?php } ?>


                  <?php 

                  #================================================================
                  #== WordPress relative
                  #================================================================

                      $wpEnvList = array(); #none yet :)

                      if (count($wpEnvList)){ 
                  ?>
              <table class="table table-bordered table-striped wtab" >
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('WordPress Environment',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <?php foreach ($wpEnvList as $envCheckKey => $envCheckName){ 

                          #} Retrieve
                          $result = zeroBSCRM_checkSystemFeat($envCheckKey,true);

                          ?>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_env_<?php echo $envCheckKey; ?>"><?php _e($envCheckName,"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><?php echo $result[1]; ?></td>
                        </tr>

                        <?php } ?>
      
                      </tbody>

                  </table>

                  <?php } ?>


                  <?php 

                  #================================================================
                  #== ZBS relative: Migrations
                  #================================================================ 

                      // 2.88 moved this to show all migrations, completed or failed.
                  
                      global $zeroBSCRM_migrations;
                      $migratedAlreadyArr = zeroBSCRM_migrations_getCompleted(); // from 2.88 $zbs->settings->get('migrations');
                      
                      # temp
                      // n/a, fixed $migrationVers = array('123'=>'1.2.3','1119' => '1.1.19','127'=>'1.2.7','2531'=>'2.53.1','2943'=>'2.94.3','2952' => '2.95.2');
                      $migrationVers = array();

                      if (is_array($zeroBSCRM_migrations) && count($zeroBSCRM_migrations) > 0){ 
                  ?>
              <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('ZBS Migrations Completed',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <?php foreach ($zeroBSCRM_migrations as $migrationkey){ 

                          //$migrationDetail = get_option('zbsmigration'.$migrationkey);
                          $migrationDetails = zeroBSCRM_migrations_geMigration($migrationkey);
                          $migrationDetail = $migrationDetails[1];
                          #array('completed'=>time(),'meta'=>array('updated'=>'['.$quotesUpdated.','.$invsUpdated.']')));

                          $migrationName = $migrationkey; if (isset($migrationVers[$migrationkey])) $migrationName = $migrationVers[$migrationkey];

                          // 29999 => 2.99.99
                          $migrationName = zeroBSCRM_format_migrationVersion($migrationName);

                        ?>

                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_mig_<?php echo $migrationkey; ?>"><?php _e('Migration: '.$migrationName,"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><?php  

                              if (isset($migrationDetail['completed'])) {
                                
                                echo __('Completed','zero-bs-crm').' '.date('F j, Y, g:i a',$migrationDetail['completed']); 
                                if (isset($migrationDetail['meta']) && isset($migrationDetail['meta']['updated'])) {
                                  
                                  // pretty up
                                  $md = $migrationDetail['meta']['updated'];
                                  if ($migrationDetail['meta']['updated'] == 1) $md = __('Success','zero-bs-crm');
                                  if ($migrationDetail['meta']['updated'] == -1) $md = __('Fail/NA','zero-bs-crm');
                                  if ($migrationDetail['meta']['updated'] == 0) $md = __('Success','zero-bs-crm'); // basically
                                  
                                  echo ' ('.$md.')';
                                  
                                }

                              } else echo __('Not Yet Ran','zero-bs-crm');

                              ?></td>
                        </tr>

                        <?php } ?>

                        <?php

                          // expose migration Timeouts
                          $timeoutIssues = zeroBSCRM_getSetting('migration300_timeout_issues'); 
                          if (isset($timeoutIssues) && $timeoutIssues == 1) echo '<tr><td colspan="2" style="text-align:center"><strong>'.__('Timeouts','zero-bs-crm').'</strong>: '.__('One or more migrations experienced timeouts while running. This may indicate that your server is not performing very well.','zero-bs-crm').'</td></tr>';

                        ?>
      
                      </tbody>

                  </table>


                  <?php } ?>

              <?php 
              /* LOADS OF WH DEBUG FROM LICENSING PUSH, leaving until that's validated fini

                // detected ext 
                //
                echo 'Ext:<br><pre>'; print_r(zeroBSCRM_installedProExt()); echo '</pre>';
                global $zbs;
                $settings = $zbs->settings->get('license_key');
                echo 'Settings:<br><pre>'; print_r($settings); echo '</pre>';

                // set_site_transient('update_plugins', null);

                // this should force an update check (and update keys)
                $pluginUpdater = new zeroBSCRM_Plugin_Updater($zbs->urls['api'], $zbs->api_ver, 'zero-bs-crm');
                $zbs_transient = '';
                $x = $pluginUpdater->check_update($zbs_transient);
                echo 'updater:<br><pre>'; print_r($x); echo '</pre>';


                  // Check plugins https://stackoverflow.com/questions/22137814/wordpress-shows-i-have-1-plugin-update-when-all-plugins-are-already-updated
                $output = '';
                  $plugin_updates = get_site_transient( 'update_plugins' );
                  if ( $plugin_updates && ! empty( $plugin_updates->response ) ) {
                      foreach ( $plugin_updates->response as $plugin => $details ) {
                          echo "<p><strong>Plugin</strong> <u>$plugin</u> is reporting an available update.</p>";
                          print_r($details);
                      }
                  }

                echo 'updater:<br><pre>'; print_r(get_plugin_updates()); echo '</pre>'; */
                
               ?>
              <table class="table table-bordered table-striped wtab">
                 
                   <thead>
                    
                        <tr>
                            <th colspan="2" class="wmid"><?php _e('Extensions',"zero-bs-crm"); ?>:</th>
                        </tr>

                    </thead>
                    
                    <tbody>

                      <?php $exts = zeroBSCRM_installedProExt(); 
                      if (is_array($exts) && count($exts) > 0){

                        // simple list em (not complex like connect page)
                        foreach ($exts as $shortName => $e){

                          ?><tr><td><?php echo $e['name']; ?></td><td><?php echo $e['ver']; ?></td></tr><?php

                        }


                      } else {

                        ?><tr><td colspan="2"><div style=""><?php
                        
                        $message = __('No Extensions Detected','zero-bs-crm');
                        // upsell/connect if not wl
                        ##WLREMOVE 
                        $message .= '<br /><a href="'.$zbs->urls['products'].'">'.__('Purchase Extensions','zero-bs-crm').'</a> or <a href="'.$zbs->slugs['settings'].'&tab=license">'.__('Add License Key','zero-bs-crm').'</a>';
                        ##/WLREMOVE

                        ?></div></td></tr><?php

                      } ?>



                    </tbody>

              </table>
              <div id="zbs-licensing-debug" style="display:none;border:1px solid #ccc;margin:1em;padding:1em;background:#FFF">
                <?php if (zeroBSCRM_isZBSAdminOrAdmin()){
                      $l = $zbs->DAL->setting('licensingcount',0);
                      $err = $zbs->DAL->setting('licensingerror',false);
                      $key = $zbs->settings->get('license_key');

                      echo 'Attempts:'.$l.'</br>Err:<pre>'.print_r($err,1).'</pre></br>key:<pre>'.print_r($key,1).'</pre>';

                    } ?>
              </div>

               <?php 
              /* Debug for external sources

                  echo 'src:<pre>'.print_r($zbs->external_sources,1).'</pre>'; 
              */
                
               ?>
              <table class="table table-bordered table-striped wtab">
                 
                   <thead>
                    
                        <tr>
                            <th colspan="2" class="wmid"><?php _e('External Source Register',"zero-bs-crm"); ?>:</th>
                        </tr>

                    </thead>
                    
                    <tbody>

                      <?php
                      if (is_array($zbs->external_sources) && count($zbs->external_sources) > 0){

                        // simple list em
                        foreach ($zbs->external_sources as $key => $extsource){

                          ?><tr><td><?php echo $extsource[0].' ('.$key.')'; ?></td><td><?php if (isset($extsource['ico']) && !empty($extsource['ico'])) echo '<i class="fa '.$extsource['ico'].'"></i>'; else echo '???'; ?></td></tr><?php

                        }


                      } else {

                        ?><tr><td colspan="2"><div style=""><?php
                        
                        $message = __('No External Sources Registered. Please contact support!','zero-bs-crm');
                        
                        ?></div></td></tr><?php

                      } ?>



                    </tbody>

              </table>

               <?php 

                // if admin + has perf logs to show
                if (zeroBSCRM_isWPAdmin()){
                  $zbsPerfTestOpt = get_option( 'zbs-global-perf-test', array());

                  if (is_array($zbsPerfTestOpt) && count($zbsPerfTestOpt) > 0){

                     ?>
                    <table class="table table-bordered table-striped wtab">
                       
                         <thead>
                          
                              <tr>
                                  <th colspan="3" class="wmid"><?php _e('Performance Tests',"zero-bs-crm"); ?>:</th>
                              </tr>
                          
                              <tr>
                                  <th class=""><?php _e('Started',"zero-bs-crm"); ?>:</th>
                                  <th class="wmid"><?php _e('Get',"zero-bs-crm"); ?>:</th>
                                  <th class=""><?php _e('Results',"zero-bs-crm"); ?>:</th>
                              </tr>

                          </thead>
                          
                          <tbody>

                            <?php

                              // simple list em
                              foreach ($zbsPerfTestOpt as $perfTest){

                                ?><tr>

                                <td><?php 

                                  if (isset($perfTest['init'])) echo date('F j, Y, g:i a',$perfTest['init']);

                                ?></td>

                                <td><?php 

                                  if (isset($perfTest['get']) && is_array($perfTest['get'])) echo '<pre>'.print_r($perfTest['get'],1).'</pre>';

                                ?></td>

                                <td><?php 

                                  if (isset($perfTest['results']) && is_array($perfTest['results'])) echo '<pre>'.print_r($perfTest['results'],1).'</pre>';

                                ?></td>

                                </tr><?php 

                              }

                            ?>

                          </tbody>

                    </table>
                    <?php } // / has perf tests

                  } // / admin ?>

              <div class="ui segment">
                <h3><?php _e('Administrator Tools',"zero-bs-crm"); ?></h3>
                <a href="<?php echo wp_nonce_url('?page='.$zbs->slugs['systemstatus'].'&resetuserroles=1','resetuserroleszerobscrm'); ?>" class="ui button blue"><?php _e('Re-build User Roles',"zero-bs-crm"); ?></a>
                <?php if ($zbs->isDAL3()){ ?><a href="<?php echo zeroBSCRM_getAdminURL($zbs->slugs['systemstatus']).'&v3migrationlog=1'; ?>" class="ui button blue"><?php _e('v3 Migration Logs',"zero-bs-crm"); ?></a><?php } ?>
              </div>


              <script type="text/javascript">

                jQuery(document).ready(function(){



                });


              </script>
              
      </div><?php 


    } // / if normal page load

}

function zeroBSCRM_html_settings_bizinfo(){


  global $wpdb, $zbs;  #} Req

  $confirmAct = false;
  $settings = $zbs->settings->getAll();   

  #} Act on any edits!
  if (isset($_POST['editwplf']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-bizinfo' );

    // moved from invoice builder settings -> biz info 16/7/18

    #} Invoice Chunks
    $updatedSettings['businessname'] = ''; if (isset($_POST['businessname'])) $updatedSettings['businessname'] = zeroBSCRM_textProcess($_POST['businessname']);
    $updatedSettings['businessyourname'] = ''; if (isset($_POST['businessyourname'])) $updatedSettings['businessyourname'] = zeroBSCRM_textProcess($_POST['businessyourname']);
    $updatedSettings['businessyouremail'] = ''; if (isset($_POST['businessyouremail'])) $updatedSettings['businessyouremail'] = zeroBSCRM_textProcess($_POST['businessyouremail']);
    $updatedSettings['businessyoururl'] = ''; if (isset($_POST['businessyoururl'])) $updatedSettings['businessyoururl'] = zeroBSCRM_textProcess($_POST['businessyoururl']);
    $updatedSettings['businesstel'] = ''; if (isset($_POST['businesstel'])) $updatedSettings['businesstel'] = zeroBSCRM_textProcess($_POST['businesstel']);

    #} Invoice Logo
    $updatedSettings['invoicelogourl'] = ''; if (isset($_POST['wpzbscrm_invoicelogourl']) && !empty($_POST['wpzbscrm_invoicelogourl'])) $updatedSettings['invoicelogourl'] = sanitize_text_field($_POST['wpzbscrm_invoicelogourl']);

    #} Social
    $updatedSettings['twitter'] = ''; if (isset($_POST['wpzbs_twitter'])) {
      $updatedSettings['twitter'] = sanitize_text_field( $_POST['wpzbs_twitter']);
      if (substr($updatedSettings['twitter'],0,1) == '@') $updatedSettings['twitter'] = substr($updatedSettings['twitter'],1);
    }
    $updatedSettings['facebook'] = ''; if (isset($_POST['wpzbs_facebook'])) $updatedSettings['facebook'] = sanitize_text_field( $_POST['wpzbs_facebook']);
    $updatedSettings['linkedin'] = ''; if (isset($_POST['wpzbs_linkedin'])) $updatedSettings['linkedin'] = sanitize_text_field( $_POST['wpzbs_linkedin']);

    #} Brutal update
    foreach ($updatedSettings as $k => $v) $zbs->settings->update($k,$v);

    #} $msg out!
    $sbupdated = true;

    #} Reload
    $settings = $zbs->settings->getAll();
      
  }

  #} catch resets.
  if (isset($_GET['resetsettings']) && zeroBSCRM_isZBSAdminOrAdmin()) if ($_GET['resetsettings']==1){

    $nonceVerified = wp_verify_nonce( $_GET['_wpnonce'], 'resetclearzerobscrm' );

    if (!isset($_GET['imsure']) || !$nonceVerified){

        #} Needs to confirm!  
        $confirmAct = true;
        $actionStr        = 'resetsettings';
        $actionButtonStr    = __('Reset Settings to Defaults?',"zero-bs-crm");
        $confirmActStr      = __('Reset All Zero BS CRM Settings?',"zero-bs-crm");
        $confirmActStrShort   = __('Are you sure you want to reset these settings to the defaults?',"zero-bs-crm");
        $confirmActStrLong    = __('Once you reset these settings you cannot retrieve your previous settings.',"zero-bs-crm");

      } else {


        if ($nonceVerified){

            #} Reset
            $zbs->settings->resetToDefaults();

            #} Reload
            $settings = $zbs->settings->getAll();

            #} Msg out!
            $sbreset = true;

        }

      }

  } 






  if (!$confirmAct){

  ?>
    
        <p id="sbDesc"><?php _e('Set up your general business information. This is used across Zero BS CRM, in features such as invoicing, mail campaigns, and email notifications.',"zero-bs-crm"); ?></p>

        <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
        <?php if (isset($sbreset)) if ($sbreset) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Reset',"zero-bs-crm")); echo '</div>'; } ?>
        
        <div id="sbA">
          <pre><?php // print_r($settings); ?></pre>

            <form method="post">
              <input type="hidden" name="editwplf" id="editwplf" value="1" />
              <?php 
                // add nonce
                wp_nonce_field( 'zbs-update-settings-bizinfo');
              ?>
                    <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Your Business Vitals',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody>

                        <tr>
                            <td class="wfieldname"><label for="businessname"><?php _e('Your Business Name',"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><input type="text" class="winput form-control" name="businessname" id="businessname" value="<?php if (isset($settings['businessname']) && !empty($settings['businessname'])) echo $settings['businessname']; ?>" placeholder="e.g. Widget Co Ltd." /></td>
                        </tr>


                        <tr>
                          <td class="wfieldname"><label for="wpzbscrm_invoicelogourl"><?php _e('Your Business Logo',"zero-bs-crm"); ?>:</label><br /><?php _e('Enter an URL here, or upload a default logo to use on your invoices etc.',"zero-bs-crm"); ?></td>
                          <td style="width:540px">
                            <input style="width:90%;padding:10px;" name="wpzbscrm_invoicelogourl" id="wpzbscrm_invoicelogourl" class="form-control link" type="text" value="<?php if (isset($settings['invoicelogourl']) && !empty($settings['invoicelogourl'])) echo $settings['invoicelogourl']; ?>" />
                            <button id="wpzbscrm_invoicelogourlAdd" class="button" type="button"><?php _e("Upload Image","zero-bs-crm");?></button>
                          </td>
                        </tr>

                      </tbody>
                    </table>

                    <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Your Full Business Information',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>


                        <tr>
                            <td class="wfieldname"><label for="businessyourname"><?php _e('Your Name',"zero-bs-crm"); ?>:</label><br /><?php _e('The business proprietor (Useful for freelancers), e.g. "John Doe (optionally - added to your invoice)" ',"zero-bs-crm"); ?></td>
                          <td style="width:540px"><input type="text" class="winput form-control" name="businessyourname" id="businessyourname" value="<?php if (isset($settings['businessyourname']) && !empty($settings['businessyourname'])) echo $settings['businessyourname']; ?>" placeholder="e.g. John Doe" /></td>
                        </tr>

                        <tr>
                            <td class="wfieldname"><label for="businessyouremail"><?php _e('Business Contact Email',"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><input type="text" class="winput form-control" name="businessyouremail" id="businessyouremail" value="<?php if (isset($settings['businessyouremail']) && !empty($settings['businessyouremail'])) echo $settings['businessyouremail']; ?>" placeholder="e.g. email@domain.com" /></td>
                        </tr>

                        <tr>
                            <td class="wfieldname"><label for="businessyoururl"><?php _e('Business Website URL',"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><input type="text" class="winput form-control" name="businessyoururl" id="businessyoururl" value="<?php if (isset($settings['businessyoururl']) && !empty($settings['businessyoururl'])) echo $settings['businessyoururl']; ?>" placeholder="e.g. https://example.com" /></td>
                        </tr>

                        <tr>
                            <td class="wfieldname"><label for="businesstel"><?php _e('Business Telephone Number',"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><input type="text" class="winput form-control" name="businesstel" id="businesstel" value="<?php if (isset($settings['businesstel']) && !empty($settings['businesstel'])) echo $settings['businesstel']; ?>" placeholder="" /></td>
                        </tr>


                      </tbody>

                  </table>

                    <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" style="text-align:center">
                                <strong><?php _e('Your Business Social Info',"zero-bs-crm"); ?>:</strong><br />
                                <?php _e('Add your social accounts to (optionally) show them on your mail campaigns etc.',"zero-bs-crm"); ?>
                              </th>
                          </tr>

                      </thead>


                        <tr>
                            <td class="wfieldname"><label for="wpzbs_twitter"><?php _e('Twitter Handle',"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><input type="text" class="winput form-control" name="wpzbs_twitter" id="wpzbs_twitter" value="<?php if (isset($settings['twitter']) && !empty($settings['twitter'])) echo $settings['twitter']; ?>" placeholder="e.g. twitter (no @)" /></td>
                        </tr>

                        <tr>
                            <td class="wfieldname"><label for="wpzbs_facebook"><?php _e('Facebook Page',"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><input type="text" class="winput form-control" name="wpzbs_facebook" id="wpzbs_facebook" value="<?php if (isset($settings['facebook']) && !empty($settings['facebook'])) echo $settings['facebook']; ?>" placeholder="e.g. facebookpagename" /></td>
                        </tr>

                        <tr>
                            <td class="wfieldname"><label for="wpzbs_linkedin"><?php _e('Linked In ID',"zero-bs-crm"); ?>:</label></td>
                          <td style="width:540px"><input type="text" class="winput form-control" name="wpzbs_linkedin" id="wpzbs_linkedin" value="<?php if (isset($settings['linkedin']) && !empty($settings['linkedin'])) echo $settings['linkedin']; ?>" placeholder="e.g. linkedinco" /></td>
                        </tr>
                        <?php ##WLREMOVE ?>
                          <tr>
                              <th colspan="2" style="text-align:center;padding:1em">
                                <strong><?php _e('... and don\'t forget to follow Zero BS CRM (we\'re active on Twitter!)',"zero-bs-crm"); ?> <i class="twitter icon"></i>:</strong><br />
                                <a href="<?php echo $zbs->urls['twitter']; ?>" class="ui green button" target="_blank">@ZeroBSCRM</a><br /><br />
                                <strong><?php _e('Founders',"zero-bs-crm"); ?>:</strong><br />
                                <a href="<?php echo $zbs->urls['twitterwh']; ?>" target="_blank">@woodyhayday</a> and 
                                <a href="<?php echo $zbs->urls['twitterms']; ?>" target="_blank">@mikemayhem3030</a>
                              </th>
                          </tr>
                        <?php ##/WLREMOVE ?>

                      </tbody>

                  </table>

                  <table class="table table-bordered table-striped wtab">
                    <tbody>

                        <tr>
                          <td colspan="2" class="wmid"><button type="submit" class="ui button primary"><?php _e('Save Settings',"zero-bs-crm"); ?></button></td>
                        </tr>

                      </tbody>
                  </table>

              </form>


              <script type="text/javascript">

                jQuery(document).ready(function(){


                  // Uploader
                  // http://stackoverflow.com/questions/17668899/how-to-add-the-media-uploader-in-wordpress-plugin (3rd answer)                    
                  jQuery('#wpzbscrm_invoicelogourlAdd').click(function(e) {
                      e.preventDefault();
                      var image = wp.media({ 
                          title: '<?php _e("Upload Image","zero-bs-crm");?>',
                          // mutiple: true if you want to upload multiple files at once
                          multiple: false
                      }).open()
                      .on('select', function(e){
                          
                          // This will return the selected image from the Media Uploader, the result is an object
                          var uploaded_image = image.state().get('selection').first();
                          // We convert uploaded_image to a JSON object to make accessing it easier
                          // Output to the console uploaded_image
                          //console.log(uploaded_image);
                          var image_url = uploaded_image.toJSON().url;
                          // Let's assign the url value to the input field
                          jQuery('#wpzbscrm_invoicelogourl').val(image_url);

                      });
                  });




                });


              </script>
              
      </div><?php 
      
      } else {

          ?><div id="clpSubPage" class="whclpActionMsg six">
            <p><strong><?php echo $confirmActStr; ?></strong></p>
              <h3><?php echo $confirmActStrShort; ?></h3>
              <?php echo $confirmActStrLong; ?><br /><br />
              <button type="button" class="ui button primary" onclick="javascript:window.location='<?php echo wp_nonce_url('?page='.$zbs->slugs['settings'].'&'.$actionStr.'=1&imsure=1','resetclearzerobscrm'); ?>';"><?php echo $actionButtonStr; ?></button>
              <button type="button" class="button button-large" onclick="javascript:window.location='?page=<?php echo $zbs->slugs['settings']; ?>';"><?php _e("Cancel","zero-bs-crm"); ?></button>
              <br />
        </div><?php 
      } 


}


#} Custom Fields
function zeroBSCRM_html_customfields(){

  global $wpdb, $zbs;  #} Req

  $settings = $zbs->settings->getAll();

  $acceptableCFTypes = zeroBSCRM_customfields_acceptableCFTypes();//array('text','textarea','date','select','tel','price','numberfloat','numberint','email');

  //global $zbsCustomerFields;
  //echo '<pre>'; print_r($zbsCustomerFields); echo '</pre>'; exit();

  // this is used DAL3+
  $keyDrivenCustomFields = array(

    'customers'=>ZBS_TYPE_CONTACT,
    'companies'=>ZBS_TYPE_COMPANY,
    'quotes'=>ZBS_TYPE_QUOTE,
    'transactions'=>ZBS_TYPE_TRANSACTION,
    'invoices'=>ZBS_TYPE_INVOICE,
    'addresses'=>ZBS_TYPE_ADDRESS

  );

  #} Act on any edits!
  if (zeroBSCRM_isZBSAdminOrAdmin() && isset($_POST['editwplf'])){

    // check nonce
    check_admin_referer( 'zbs-update-settings-customfields' );

    #} Retrieve
    $customFields = array(

      'customers'=>array(),
      'customersfiles' => array(), // joc ++
      'companies'=>array(),
      'quotes'=>array(),
      'transactions'=>array(), // borge 2.91+
      'invoices'=>array(),
      'addresses'=>array()

    );

    // standard custom fields processing (not files/any that need special treatment)
    // genericified 20/07/19 2.91
    $customFieldsToProcess = array(
      'customers'=>'zbsCustomerFields',
      'companies'=>'zbsCompanyFields',
      'quotes'=>'zbsCustomerQuoteFields',
      'invoices'=>'zbsCustomerInvoiceFields',
      'transactions'=>'zbsTransactionFields',
      'addresses'=>'zbsAddressFields'
      );

    // this is used to stop dupes
    $customFieldSlugsUsed = array();

    #} Grab the first.... 128 ?
    for ($i = 1; $i <= 128; $i++){

        // WH generic'ified 20/07/18
        foreach ($customFieldsToProcess as $k => $globalVarName){

          // dupe check
          if (!isset($customFieldSlugsUsed[$k])) $customFieldSlugsUsed[$k] = array();
  
          # _t = type, _n = name, _p = placeholder
          if (isset($_POST['wpzbscrm_cf_'.$k.$i.'_t']) && !empty($_POST['wpzbscrm_cf_'.$k.$i.'_t'])){

              $possType = sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_t']);
              $possName = zeroBSCRM_textProcess(sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_n']));
              $possPlaceholder = sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_p']); #} Placeholder text or csv options

              // 2.98.5 added autonumber, encrypted, radio, checkbox, so save these extras:
              // because it always outputs the inputs, its safe to not isset check etc. they'll just be blank for non-types
                
                // radio, checkbox have no different/special additions

                // encrypted
                // Removed encrypted (for now), see https://zbsltd.atlassian.net/browse/ZBS-738
                // $encryptedPlaceholder = sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_enp']);
                // $encryptedPassword = sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_enpass']);
                
                // autonumber
                // because we store them dumbly in db, we don't allow special characters :)
                // allows alphanumeric + - + _
                $autonumberPrefix = trim(zeroBSCRM_strings_stripNonAlphaNumeric_dash(sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_anprefix'])));
                $autonumberNextNumber = (int)zeroBSCRM_strings_stripNonNumeric(trim(sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_annextnumber'])));
                $autonumberSuffix = trim(zeroBSCRM_strings_stripNonAlphaNumeric_dash(sanitize_text_field($_POST['wpzbscrm_cf_'.$k.$i.'_ansuffix'])));
                // roll them into one for storage :)
                // in fact we store them in placeholder for now! not super clean, custom fields needs a fresh rewrite (when can)
                // this overrides anything passed in _p above, also, so isn't messy messy :)
                if ($possType == 'autonumber') {
                  if ($autonumberNextNumber < 1) $autonumberNextNumber = 1;
                  $possPlaceholder = $autonumberPrefix.'#'.$autonumberNextNumber.'#'.$autonumberSuffix;
                }


              #} catch empty names
              if (empty($possName)) $possName = __('Custom Field ','zero-bs-crm'). (count($customFields[$k]) + 1);

              #} if using select, radio, or checkbox, trim ", " peeps
              if ($possType == 'select' || $possType == 'radio' || $possType == 'checkbox') $possPlaceholder = trim(str_replace(' ,',',',str_replace(', ',',',$possPlaceholder)));

              // 2.77+ added slug as a 4th arr item
              $possSlug = $zbs->DAL->makeSlug($possName);

              //echo 'field: '.$k.'<br>';
              //echo 'isset:'.(isset(${$globalVarName}[$possSlug])).', iscustom: '.(isset(${$globalVarName}[$possSlug]['custom-field'])).', taken: '.(isset($customFieldSlugsUsed[$k][$possSlug])).'<br>';

              // 2.96.7+ CHECK against existing fields + add -1 -2 etc. if already in there
              global ${$globalVarName};
              if (

                    (

                      isset(${$globalVarName}[$possSlug]) && 

                      (
                        // this means is a core field already with this name
                        (!isset(${$globalVarName}[$possSlug]['custom-field']))

                        ||

                        // this means it's a custom field, which has been pre-loaded, and this is the SECOND with that key
                        (isset(${$globalVarName}[$possSlug]['custom-field']) && isset($customFieldSlugsUsed[$k][$possSlug]))
                      )

                    )
                  ){

                    // is already set, try this
                    $c = 1;
                    while ($c <= 10){

                      // try append
                      if (!isset(${$globalVarName}[$possSlug.'-'.$c])){

                          // got one that's okay, set + break
                          $possName .= ' '.$c;
                          $possSlug = $possSlug.'-'.$c;
                          $c=11;

                      }

                      $c++;

                    }

              }

               //'id' here stops ever using that
              if ($possSlug == 'id'){

                    // is already set, try this
                    $c = 1;
                    while ($c <= 10){

                      // try append
                      if (!isset(${$globalVarName}[$possSlug.'-'.$c])){

                          // got one that's okay, set + break
                          $possName .= ' '.$c;
                          $possSlug = $possSlug.'-'.$c;
                          $c=11;

                      }

                      $c++;

                    }

              }

              if (in_array($possType,$acceptableCFTypes)){

                #} Add it
                $customFields[$k][] = array($possType,$possName,$possPlaceholder,$possSlug);
                // dupe check
                $customFieldSlugsUsed[$k][$possSlug] = 1;

              }

          }



        }

        #} CUSTOMERS FILES ( if using )
        # _t = type, _n = name, _p = placeholder
        if (isset($_POST['wpzbscrm_cf_customersfiles'.$i.'_n']) && !empty($_POST['wpzbscrm_cf_customersfiles'.$i.'_n'])){

          $possName = zeroBSCRM_textProcess(sanitize_text_field($_POST['wpzbscrm_cf_customersfiles'.$i.'_n']));

            #} Add
            if (!empty($possName)) $customFields['customersfiles'][] = array($possName);

        }


    } // end for loop 30 fields    

    // update DAL 2 custom fields :) (DAL3 dealt with below)
    if ($zbs->isDAL2() && !$zbs->isDAL3()){

        if (isset($customFields['customers']) && is_array($customFields['customers'])){

            // slight array reconfig
            $db2CustomFields = array();
            foreach ($customFields['customers'] as $cfArr){
              $db2CustomFields[$zbs->DAL->makeSlug($cfArr[1])] = $cfArr;
            }

            // simple maintain DAL2 (needs to also)
            $zbs->DAL->updateActiveCustomFields(array('objtypeid'=>1,'fields'=>$db2CustomFields));

        }

    }
    // DAL3 they all get this :) 
    if ($zbs->isDAL3()){

      foreach ($keyDrivenCustomFields as $key => $objTypeID){

          if (isset($customFields[$key]) && is_array($customFields[$key])){

              // slight array reconfig
              $db2CustomFields = array();
              foreach ($customFields[$key] as $cfArr){
                $db2CustomFields[$zbs->DAL->makeSlug($cfArr[1])] = $cfArr;
              }

              // simple maintain DAL2 (needs to also)
              $zbs->DAL->updateActiveCustomFields(array('objtypeid'=>$objTypeID,'fields'=>$db2CustomFields));

          }

      }

    }

    #} Brutal update (note this is on top of updateActiveCustomFields DAL2+ work above)
    $zbs->settings->update('customfields',$customFields);

          #} TODO HERE:
          #} - check that  1) customized fields are already working, 2) the above saves over that properly
          #} add prefix matching save code here.


    #} $msg out!
    $sbupdated = true;

    #} Reload
    $settings = $zbs->settings->getAll(true);
      
  }

  // load 
  $fieldOverride = $settings['fieldoverride'];

    // Following overloading code is also replicated in Fields.php, search #FIELDOVERLOADINGDAL2+

      // This ALWAYS needs to get overwritten by DAL2 for now :) 
      if (zeroBSCRM_isZBSAdminOrAdmin() && $zbs->isDAL2() && !$zbs->isDAL3() && isset($settings['customfields']) && isset($settings['customfields']['customers'])){

          $settings['customfields']['customers'] = $zbs->DAL->setting('customfields_contact',array());

      }
      // DAL3 ver (all objs in $keyDrivenCustomFields above)
      if ($zbs->isDAL3()){

          foreach ($keyDrivenCustomFields as $key => $objTypeID){

            if (isset($settings['customfields']) && isset($settings['customfields'][$key])){

                // turn ZBS_TYPE_CONTACT (1) into "contact"
                $typeStr = $zbs->DAL->objTypeKey($objTypeID);                
                if (!empty($typeStr)) $settings['customfields'][$key] = $zbs->DAL->setting('customfields_'.$typeStr,array());                

            }

          }

      }

    // / field Overloading

    ?>
    
        <p id="sbDesc"><?php _e('Using this page you can add or edit custom fields for your CRM',"zero-bs-crm"); ?></p>

        <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Custom Fields Updated',"zero-bs-crm")); echo '</div>'; } ?>
        
        <div id="sbA" class="zbs-settings-custom-fields">

            <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=customfields">
              <input type="hidden" name="editwplf" id="editwplf" value="1" />
              <?php 

                  // loading here is shown until custom fields drawn, then this loader hidden and all .zbs-generic-loaded shown
                  echo zeroBSCRM_UI2_loadingSegmentHTML('300px','zbs-generic-loading'); 

                // add nonce
                wp_nonce_field( 'zbs-update-settings-customfields');

              ?>
               <table class="table table-bordered table-striped wtab zbs-generic-loaded">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Contact Custom Fields',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-customers-custom-fields">

                        <tr>
                          <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-customer" class="ui small blue button">+ <?php _e("Add Custom Field","zero-bs-crm");?></button></td>
                        </tr>
      
                      </tbody>

                  </table>
               <table class="table table-bordered table-striped wtab zbs-generic-loaded">
                 
                     <thead>
                      
                          <tr>
                              <th class="wmid"><?php _e('Contact Custom File Upload Boxes',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-customersfiles-custom-fields">

                        <tr>
                          <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-customerfiles" class="ui small blue button">+ <?php _e("Add Custom File Box","zero-bs-crm");?></button></td>
                        </tr>
      
                      </tbody>

                  </table>
               <table class="table table-bordered table-striped wtab zbs-generic-loaded">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e(zeroBSCRM_getCompanyOrOrg().' Custom Fields',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-companies-custom-fields">

                        <tr>
                          <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-company" class="ui small blue button">+ <?php _e("Add Custom Field","zero-bs-crm");?></button></td>
                        </tr>
      
                      </tbody>

                  </table>
               <table class="table table-bordered table-striped wtab zbs-generic-loaded">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Quote Custom Fields',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-quotes-custom-fields">

                        <tr>
                          <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-quotes" class="ui small blue button">+ <?php _e("Add Custom Field","zero-bs-crm");?></button></td>
                        </tr>
      
                      </tbody>

                  </table>
               <table class="table table-bordered table-striped wtab zbs-generic-loaded">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Invoice Custom Fields',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-invoices-custom-fields">

                        <tr>
                          <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-invoices" class="ui small blue button">+ <?php _e("Add Custom Field","zero-bs-crm");?></button></td>
                        </tr>
      
                      </tbody>

                  </table>   
               <table class="table table-bordered table-striped wtab zbs-generic-loaded">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Transaction Custom Fields',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-transactions-custom-fields">

                        <tr>
                          <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-transactions" class="ui small blue button">+ <?php _e("Add Custom Field","zero-bs-crm");?></button></td>
                        </tr>
      
                      </tbody>
                      
                  </table>  
               <table class="table table-bordered table-striped wtab zbs-generic-loaded">

                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Address Custom Fields',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-addresses-custom-fields">

                        <tr>
                          <td colspan="2" style="text-align:right"><button type="button" id="zbscrm-addcustomfield-address" class="ui small blue button">+ <?php _e("Add Custom Field","zero-bs-crm");?></button></td>
                        </tr>
      
                      </tbody>

                  </table>


                  <table class="table table-bordered table-striped wtab zbs-generic-loaded">
                    <tbody>

                        <tr>
                          <td colspan="2" class="wmid"><button type="submit" class="ui button primary"><?php _e('Save Custom Fields',"zero-bs-crm"); ?></button></td>
                        </tr>

                      </tbody>
                  </table>

                  <p style="text-align:center" class="zbs-generic-loaded">
                    <i class="info icon"></i> <?php _e('Looking for default fields & statuses?','zero-bs-crm'); ?> <a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['settings'].'&tab=fieldoptions'); ?>"><?php _e('Click here for Field Options','zero-bs-crm'); ?></a>
                  </p>

              </form>

              <script type="text/javascript">

                // all custom js moved to admin.settings.js 12/3/19 :)

                var wpzbscrmCustomFields = <?php echo json_encode($settings['customfields']); ?>;
                var wpzbscrmAcceptableTypes = <?php echo json_encode($acceptableCFTypes); ?>;
                var wpzbscrm_settings_page = 'customfields'; // this fires init js in admin.settings.min.js
                var wpzbscrm_settings_lang = {

                        customfield:'<?php zeroBSCRM_slashOut(__('Custom Field','zero-bs-crm')); ?>',
                        remove:     '<?php zeroBSCRM_slashOut(__('Remove','zero-bs-crm')); ?>',
                        tel:        '<?php zeroBSCRM_slashOut(__('Telephone','zero-bs-crm')); ?>',
                        numbdec:    '<?php zeroBSCRM_slashOut(__('Numeric (Decimals)','zero-bs-crm')); ?>',
                        numb:       '<?php zeroBSCRM_slashOut(__('Numeric','zero-bs-crm')); ?>',
                        placeholder:'<?php zeroBSCRM_slashOut(__('Placeholder','zero-bs-crm')); ?>',
                        csvopt:     '<?php zeroBSCRM_slashOut(__("CSV of Options (e.g. 'a,b,c')",'zero-bs-crm')); ?>',
                        fieldname:  '<?php zeroBSCRM_slashOut(__('Field Name','zero-bs-crm')); ?>',
                        fieldplacehold:'<?php zeroBSCRM_slashOut(__('Field Placeholder Text','zero-bs-crm')); ?>',
                        fileboxname: '<?php zeroBSCRM_slashOut(__('File Box Name','zero-bs-crm')); ?>',
                        password:   '<?php zeroBSCRM_slashOut(__('Password','zero-bs-crm')); ?>',
                        encryptedtext: '<?php zeroBSCRM_slashOut(__('Encrypted Text','zero-bs-crm')); ?>',
                        radiobuttons: '<?php zeroBSCRM_slashOut(__('Radio Buttons','zero-bs-crm')); ?>',
                        prefix:     '<?php zeroBSCRM_slashOut(__('Prefix','zero-bs-crm')); ?>',
                        nextnumber: '<?php zeroBSCRM_slashOut(__('Next Number','zero-bs-crm')); ?>',
                        suffix:     '<?php zeroBSCRM_slashOut(__('Suffix','zero-bs-crm')); ?>',
                        prefixe:     '<?php zeroBSCRM_slashOut(__('(e.g. ABC-)','zero-bs-crm')); ?>',
                        nextnumbere: '<?php zeroBSCRM_slashOut(__('(e.g. 1)','zero-bs-crm')); ?>',
                        suffixe:     '<?php zeroBSCRM_slashOut(__('(e.g. -FINI)','zero-bs-crm')); ?>',
                        fieldtype:   '<?php zeroBSCRM_slashOut(__('Field Type:','zero-bs-crm')); ?>',
                        autonumberformat:   '<?php zeroBSCRM_slashOut(__('Autonumber Format','zero-bs-crm')); ?>',
                        autonumberguide:   '<?php zeroBSCRM_slashOut(__('Autonumber Guide','zero-bs-crm')); ?>',

                };
                var wpzbscrm_settings_urls = {

                    autonumberhelp: '<?php echo $zbs->urls['autonumberhelp']; ?>'

                };

              </script>
              
      </div><?php 
      
}




#} Field Options (statuses, defaults, field settings)
function zeroBSCRM_html_settings_fieldOptions(){

  global $wpdb, $zbs;  #} Req

  $settings = $zbs->settings->getAll();


  #} Act on any edits!
  if (isset($_POST['editwplf'])){
      #} Status + prefix

        #} Prev - this default was taken from config 16/2/17 - to be updated as and when
        // UPDATED 21/3/19 (v3+) search #UPDATECUSTOMFIELDDEFAULTS
        $customisedFields = array(

                        'customers' => array(
                          #} Allow people to order base fields + also modified some... via this
                          #} Can remove this and will revert to default
                          #} Currently: showhide, value (for now)
                          #} Remember, this'll effect other areas of the CRM
                          'status'=> array(
                            1,'Lead,Customer,Refused,Blacklisted,Cancelled by Customer,Cancelled by Us (Pre-Quote),Cancelled by Us (Post-Quote)'
                          ),
                          'prefix'=> array(
                            1,'Mr,Mrs,Ms,Miss,Dr,Prof,Mr & Mrs'
                          )
                        ),

                        #} transaction statuses..
                        'transactions' => array(
                          #} Allow people to order base fields + also modified some... via this
                          #} Can remove this and will revert to default
                          #} Currently: showhide, value (for now)
                          #} Remember, this'll effect other areas of the CRM
                          'status'=> array(
                            1,'Succeeded,Completed,Failed,Refunded,Processing,Pending,Hold,Cancelled'
                          )
                        ),



                        'companies' => array(
                          #} Allow people to order base fields + also modified some... via this
                          #} Can remove this and will revert to default
                          #} Currently: showhide, value (for now)
                          #} Remember, this'll effect other areas of the CRM
                          'status'=> array(
                            1,'Lead,Customer,Refused,Blacklisted'
                          )
                        ),
                        'quotes' => array(),
                        'invoices' => array()

                      );

        #} Retrieve
        $zbsStatusStr = ''; if (isset($_POST['zbs-status']) && !empty($_POST['zbs-status'])) $zbsStatusStr = sanitize_text_field($_POST['zbs-status']);
        $zbsCoStatusStr = ''; if (isset($_POST['zbs-status-companies']) && !empty($_POST['zbs-status-companies'])) $zbsCoStatusStr = sanitize_text_field($_POST['zbs-status-companies']);

        $zbsTranStatusStr = ''; if (isset($_POST['zbs-status-transactions']) && !empty($_POST['zbs-status-transactions'])) $zbsTranStatusStr = sanitize_text_field($_POST['zbs-status-transactions']);

        

        $zbsFunnelStr = ''; if (isset($_POST['zbs-funnel']) && !empty($_POST['zbs-funnel'])) {
          $zbsFunnelStr  = sanitize_text_field($_POST['zbs-funnel']);
          // wh added to trim , x
          $zbsFunnelStr = trim(str_replace(' ,',',',str_replace(', ',',',$zbsFunnelStr)));
        }

        $zbs->settings->update('zbsfunnel', $zbsFunnelStr);

        $zbsDefaultStatusStr = ''; if (isset($_POST['zbs-default-status']) && !empty($_POST['zbs-default-status'])) $zbsDefaultStatusStr = sanitize_text_field($_POST['zbs-default-status']);
        $zbsPrefixStr = ''; if (isset($_POST['zbs-prefix']) && !empty($_POST['zbs-prefix'])) $zbsPrefixStr = sanitize_text_field($_POST['zbs-prefix']);

        #} 2.10.3
        $zbsShowID = -1; if (isset($_POST['zbs-show-id']) && !empty($_POST['zbs-show-id'])) $zbsShowID = 1;

        #} Update

          #} any here? or 1?
          if (strpos($zbsStatusStr, ',') > -1) {

            #} Trim them...
            $zbsStatusArr = array(); $zbsStatusUncleanArr = explode(',',$zbsStatusStr);
            foreach ($zbsStatusUncleanArr as $x) {
              $z = trim($x);
              if (!empty($z)) $zbsStatusArr[] = $z;
            }

            $customisedFields['customers']['status'][1] = implode(',',$zbsStatusArr); #$zbsStatusArr;

          } else {

              #} only 1? or empty? 
              if (!empty($zbsStatusStr)) $customisedFields['customers']['status'][1] = $zbsStatusStr;

          }


          if (strpos($zbsTranStatusStr, ',') > -1) {

            #} Trim them...
            $zbsStatusArr = array(); $zbsStatusUncleanArr = explode(',',$zbsTranStatusStr);
            foreach ($zbsStatusUncleanArr as $x) {
              $z = trim($x);
              if (!empty($z)) $zbsStatusArr[] = $z;
            }

            $customisedFields['transactions']['status'][1] = implode(',',$zbsStatusArr); #$zbsStatusArr;

          } else {

              #} only 1? or empty? 
              if (!empty($zbsStatusStr)) $customisedFields['transactions']['status'][1] = $zbsStatusStr;

          }

          #} any here? or 1?
          if (strpos($zbsCoStatusStr, ',') > -1) {

            #} Trim them...
            $zbsStatusArr = array(); $zbsStatusUncleanArr = explode(',',$zbsCoStatusStr);
            foreach ($zbsStatusUncleanArr as $x) {
              $z = trim($x);
              if (!empty($z)) $zbsStatusArr[] = $z;
            }

            $customisedFields['companies']['status'][1] = implode(',',$zbsStatusArr); #$zbsStatusArr;

          } else {

              #} only 1? or empty? 
              if (!empty($zbsCoStatusStr)) $customisedFields['companies']['status'][1] = $zbsCoStatusStr;

          }

          #} any here? or 1?
          if (strpos($zbsPrefixStr, ',') > -1) {

            #} Trim them...
            $zbsPrefixArr = array(); $zbsPrefixUncleanArr = explode(',',$zbsPrefixStr);
            foreach ($zbsPrefixUncleanArr as $x) {
              $z = trim($x);
              if (!empty($z)) $zbsPrefixArr[] = $z;
            }

            $customisedFields['customers']['prefix'][1] =  implode(',',$zbsPrefixArr); #$zbsPrefixArr;

          } else {

              #} only 1? or empty? 
              if (!empty($zbsPrefixStr)) $customisedFields['customers']['prefix'][1] = $zbsPrefixStr;

          }


        #} 2.17
        $filtersFromStatus = -1; if (isset($_POST['wpzbscrm_filtersfromstatus']) && !empty($_POST['wpzbscrm_filtersfromstatus']) && $_POST['wpzbscrm_filtersfromstatus'] == "1") $filtersFromStatus = 1;


        #} 2.81
        $fieldOverride = -1; if (isset($_POST['wpzbscrm_fieldoverride']) && !empty($_POST['wpzbscrm_fieldoverride']) && $_POST['wpzbscrm_fieldoverride'] == "1") $fieldOverride = 1;

        #} 2.87
        $filtersFromSegments = -1; if (isset($_POST['wpzbscrm_filtersfromsegments']) && !empty($_POST['wpzbscrm_filtersfromsegments']) && $_POST['wpzbscrm_filtersfromsegments'] == "1") $filtersFromSegments = 1;
        
        #} 2.99.9.11
        $customFieldSearch = -1; if (isset($_POST['wpzbscrm_customfieldsearch']) && !empty($_POST['wpzbscrm_customfieldsearch']) && $_POST['wpzbscrm_customfieldsearch'] == "1") $customFieldSearch = 1;

        #} 3.0
        $shippingForTransactions = -1; if (isset($_POST['wpzbscrm_shippingfortransactions']) && !empty($_POST['wpzbscrm_shippingfortransactions']) && $_POST['wpzbscrm_shippingfortransactions'] == "1") $shippingForTransactions = 1;
        $paidDatesTransactions = -1; if (isset($_POST['wpzbscrm_paiddatestransaction']) && !empty($_POST['wpzbscrm_paiddatestransaction']) && $_POST['wpzbscrm_paiddatestransaction'] == "1") $paidDatesTransactions = 1;

          #} Brutal update
          $zbs->settings->update('customisedfields',$customisedFields);
          $zbs->settings->update('defaultstatus',$zbsDefaultStatusStr);
          $zbs->settings->update('showid',$zbsShowID);
          $zbs->settings->update('filtersfromstatus',$filtersFromStatus);
          $zbs->settings->update('fieldoverride',$fieldOverride);
          $zbs->settings->update('filtersfromsegments',$filtersFromSegments);
          $zbs->settings->update('shippingfortransactions',$shippingForTransactions);
          $zbs->settings->update('paiddatestransaction',$paidDatesTransactions);
          $zbs->settings->update('customfieldsearch',$customFieldSearch);

    #} $msg out!
    $sbupdated = true;

    #} Reload
    $settings = $zbs->settings->getAll(true);
      
  }

  // load 
  $fieldOverride = $settings['fieldoverride'];


    ?>
    
        <p id="sbDesc"><?php _e('Using this page you can manage the default fields, statuses and other field options used throughout your CRM',"zero-bs-crm"); ?></p>

        <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Custom Fields Updated',"zero-bs-crm")); echo '</div>'; } ?>
        
        <div id="sbA" class="zbs-settings-custom-fields">

            <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=fieldoptions">
              <input type="hidden" name="editwplf" id="editwplf" value="1" />
               
                 <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('General Field Options',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-statusprefix-custom-fields">

                        <tr>
                          <td colspan="2" style="padding:2%;">

                              <table class="table table-bordered table-striped wtab">
                                <tbody id="zbscrm-statusprefix-custom-fields">

                                  <tr>
                                    <td width="94">                                      
                                      <label for="zbs-show-id"><?php _e('Show IDs',"zero-bs-crm"); ?></label>
                                    </td>
                                    <td>
                                      <input type="checkbox" name="zbs-show-id" id="zbs-show-id" value="1" <?php if (isset($settings['showid']) && $settings['showid'] == "1") echo ' checked="checked"'; ?> class="form-control" />
                                      <p style="margin-top:4px"><?php _e("Choose whether to show or hide Contact/Company ID on customer record and manage pages","zero-bs-crm");?></p>
                                    </td>
                                  </tr>


                                  <tr>
                                    <td>                                      
                                      <label for="wpzbscrm_fieldoverride"><?php _e('Overwrite Option',"zero-bs-crm"); ?></label>
                                    </td>
                                    <td>
                                      <input type="checkbox" name="wpzbscrm_fieldoverride" id="wpzbscrm_fieldoverride" value="1"<?php if ($fieldOverride == "1") echo ' checked="checked"'; ?> class="form-control" />
                                      <br />
                                      <p style="margin-top:4px"><?php _e("When a field is overriden by the API, a form, or other non-manual means, only overwrite the fields that are sent and do not clear non-sent fields?","zero-bs-crm");?></p>
                                    </td>
                                  </tr>
                
                                </tbody>
                              </table>


                          </td>
                        </tr>
      
                      </tbody>

                  </table>
               
                 <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Contact Field Options',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-statusprefix-custom-fields">

                        <tr>
                          <td colspan="2" style="padding:2%;">

                              <table class="table table-bordered table-striped wtab">
                                <tbody id="zbscrm-statusprefix-custom-fields">

                                  <tr>
                                    <td width="94">                                      
                                      <label for="zbs-status"><?php _e('Contact Status',"zero-bs-crm"); ?></label>
                                    </td>
                                    <td>
                                      <?php 

                                        #} retrieve value as simple CSV for now - simplistic at best.
                                        $zbsStatusStr = ''; 
                                        #} stored here: $settings['customisedfields']
                                        if (isset($settings['customisedfields']['customers']['status']) && is_array($settings['customisedfields']['customers']['status'])) $zbsStatusStr = $settings['customisedfields']['customers']['status'][1];                                        
                                        if (empty($zbsStatusStr)) {
                                          #} Defaults:
                                          global $zbsCustomerFields; if (is_array($zbsCustomerFields)) $zbsStatusStr = implode(',',$zbsCustomerFields['status'][3]);
                                        }                                        

                                      ?>
                                      <input type="text" name="zbs-status" id="zbs-status" value="<?php echo $zbsStatusStr; ?>" class="form-control" />
                                      <p style="margin-top:4px"><?php _e("Default is","zero-bs-crm");?>:<br /><span style="background:#ceeaea;padding:0 4px">Lead,Customer,Refused,Blacklisted</span></p>
                                    </td>
                                  </tr>

                                  <tr>
                                    <td>                                      
                                      <label for="zbs-prefix"><?php _e('Prefix Options',"zero-bs-crm"); ?></label>
                                    </td>
                                    <td>
                                      <?php 

                                        #} retrieve value as simple CSV for now - simplistic at best.
                                        #} stored here: $settings['customisedfields']
                                        $zbsPrefixStr = ''; 
                                        if (isset($settings['customisedfields']['customers']['prefix']) && is_array($settings['customisedfields']['customers']['prefix'])) $zbsPrefixStr = $settings['customisedfields']['customers']['prefix'][1];                                        
                                        if (empty($zbsPrefixStr)) {
                                          #} Defaults:
                                          global $zbsCustomerFields; if (is_array($zbsCustomerFields)) $zbsPrefixStr = implode(',',$zbsCustomerFields['prefix'][3]);
                                        }       
                                        

                                      ?>
                                      <input type="text" name="zbs-prefix" id="zbs-prefix" value="<?php echo $zbsPrefixStr; ?>" class="form-control" />
                                      <p style="margin-top:4px"><?php _e("Default is","zero-bs-crm");?>: <span style="background:#ceeaea;padding:0 4px">Mr,Mrs,Ms,Miss,Dr,Prof,Mr &amp; Mrs</span></p>
                                    </td>
                                  </tr>

                                  <tr>
                                    <td><label for="wpzbscrm_filtersfromstatus">Status Quick-filters:</label></td>
                                    <td>
                                      <select class="winput form-control" name="wpzbscrm_filtersfromstatus" id="wpzbscrm_filtersfromstatus">
                                          <option value="1"<?php if (isset($settings['filtersfromstatus']) && $settings['filtersfromstatus'] == "1") echo ' selected="selected"'; ?>><?php _e('Automatic Status Quick Filters',"zero-bs-crm"); ?></option>
                                          <option value="-1"<?php if (isset($settings['filtersfromstatus']) && $settings['filtersfromstatus'] != "1") echo ' selected="selected"'; ?>><?php _e('No Status Quick Filters',"zero-bs-crm"); ?></option>
                                       </select>
                                       <p style="margin-top:4px"><?php _e('Automatically add Quick-filters for each status',"zero-bs-crm"); ?></p>
                                     </td>
                                  </tr>
                                  <tr>
                                    <td><label for="wpzbscrm_filtersfromsegments">Segment Quick-filters:</label></td>
                                    <td>
                                      <select class="winput form-control" name="wpzbscrm_filtersfromsegments" id="wpzbscrm_filtersfromsegments">
                                          <option value="1"<?php if (isset($settings['filtersfromsegments']) && $settings['filtersfromsegments'] == "1") echo ' selected="selected"'; ?>><?php _e('Automatic Segment Quick Filters',"zero-bs-crm"); ?></option>
                                          <option value="-1"<?php if (isset($settings['filtersfromsegments']) && $settings['filtersfromsegments'] != "1") echo ' selected="selected"'; ?>><?php _e('No Segment Quick Filters',"zero-bs-crm"); ?></option>
                                       </select>
                                       <p style="margin-top:4px"><?php _e('Automatically add Quick-filters for each Segment',"zero-bs-crm"); ?></p>
                                     </td>
                                  </tr>

                                  <tr>
                                    <td width="94">                                      
                                      <label for="zbs-default-status"><?php _e('Status: Default',"zero-bs-crm"); ?></label>
                                    </td>
                                    <td>
                                      <?php 

                                        #} stored here: $settings['defaultstatus']
                                        if (isset($settings['defaultstatus'])) $defaultStatusStr = $settings['defaultstatus'];
                                        if (!empty($zbsStatusStr)) {

                                          ?><select name="zbs-default-status" id="zbs-default-status" class="form-control"> 
                                          <?php

                                            $zbsStatuses = explode(',', $zbsStatusStr);
                                            if (is_array($zbsStatuses)) { foreach ($zbsStatuses as $statusStr){

                                              ?><option value="<?php echo $statusStr; ?>"<?php
                                              if ($defaultStatusStr == $statusStr) echo ' selected="selected"';
                                              ?>><?php echo $statusStr; ?></option><?php

                                            }}else{

                                                ?><option value=""><?php _e("None (Set values above and save to enable this)","zero-bs-crm");?></option><?php 

                                            }

                                            ?></select><?php

                                        }                                        

                                      ?>
                                      <p style="margin-top:4px"><?php _e("This setting determines which status will automatically be assigned to new customer records where a status is not specified (e.g. via web form","zero-bs-crm");?></p>
                                    </td>
                                  </tr>


                                    <tr>
                                      <td colspan="2" style="padding:2%;">

                                          <table class="table table-bordered table-striped wtab">
                                            <tbody>
                                              <tr>
                                                <td width="94">                                      
                                                  <label for="wpzbscrm_customfieldsearch"><?php _e('Include Custom Fields in Search',"zero-bs-crm"); ?></label>
                                                </td>
                                                <td>
                                                  <input type="checkbox" name="wpzbscrm_customfieldsearch" id="wpzbscrm_customfieldsearch" value="1" <?php if (isset($settings['customfieldsearch']) && $settings['customfieldsearch'] == "1") echo ' checked="checked"'; ?> class="form-control" />
                                                  <small style="margin-top:4px"><?php _e("","zero-bs-crm");?></small>
                                                </td>
                                              </tr>
                                            </tbody>
                                          </table>
                                      </td>
                                    </tr>
                
                                </tbody>
                              </table>


                          </td>
                        </tr>
      
                      </tbody>

                  </table>
               
               
                <?php // only show if using b2b 
                $b2bMode = zeroBSCRM_getSetting('companylevelcustomers');
                if($b2bMode){
                ?>
                 <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Company Field Options',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-statusprefix-custom-fields">

                        <tr>
                          <td colspan="2" style="padding:2%;">

                              <table class="table table-bordered table-striped wtab">
                                <tbody id="zbscrm-statusprefix-custom-fields">



                                  <tr>
                                    <td width="94">                                      
                                      <label for="zbs-status-companies"><?php _e('Company Status',"zero-bs-crm"); ?></label>
                                    </td>
                                    <td>
                                      <?php 

                                        #} retrieve value as simple CSV for now - simplistic at best.
                                        $zbsStatusStr = ''; 
                                        #} stored here: $settings['customisedfields']
                                        if (isset($settings['customisedfields']['companies']['status']) && is_array($settings['customisedfields']['companies']['status'])) $zbsStatusStr = $settings['customisedfields']['companies']['status'][1];                                        
                                        if (empty($zbsStatusStr)) {
                                          #} Defaults:
                                          global $zbsCompanyFields; if (is_array($zbsCompanyFields)) $zbsStatusStr = implode(',',$zbsCompanyFields['status'][3]);
                                        }                                        

                                      ?>
                                      <input type="text" name="zbs-status-companies" id="zbs-status-companies" value="<?php echo $zbsStatusStr; ?>" class="form-control" />
                                      <p style="margin-top:4px"><?php _e("Default is","zero-bs-crm");?>:<br /><span style="background:#ceeaea;padding:0 4px">Lead,Customer,Refused,Blacklisted</span></p>
                                    </td>
                                  </tr>
                
                                </tbody>
                              </table>


                          </td>
                        </tr>
      
                      </tbody>

                  </table>

                <?php } ?>
               
                 <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Transactions Field Options',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-statusprefix-custom-fields">

                        <tr>
                          <td colspan="2" style="padding:2%;">

                              <table class="table table-bordered table-striped wtab">
                                <tbody id="zbscrm-statusprefix-custom-fields">

                                  <tr>
                                    <td width="94">                                      
                                      <label for="zbs-status-transactions"><?php _e('Transaction Status',"zero-bs-crm"); ?></label>
                                    </td>
                                    <td>
                                      <?php 

                                        #} retrieve value as simple CSV for now - simplistic at best.
                                        $zbsTranStatusStr = ''; 
                                        #} stored here: $settings['customisedfields']
                                        if (isset($settings['customisedfields']['transactions']['status']) && is_array($settings['customisedfields']['transactions']['status'])) $zbsTranStatusStr = $settings['customisedfields']['transactions']['status'][1];                                        
                                        if (empty($zbsTranStatusStr)) {
                                          #} Defaults:
                                          global $zbsTransactionFields; if (is_array($zbsTransactionFields)) $zbsTranStatusStr = implode(',',$zbsTransactionFields['status'][3]);
                                        }                                        

                                      ?>
                                      <input type="text" name="zbs-status-transactions" id="zbs-status-transactions" value="<?php echo $zbsTranStatusStr; ?>" class="form-control" />
                                      <p style="margin-top:4px"><?php _e("Default is","zero-bs-crm");?>:<br /><span style="background:#ceeaea;padding:0 4px">Succeeded,Completed,Failed,Refunded,Processing,Pending,Hold,Cancelled</span></p>
                                    </td>
                                  </tr>


                                  <tr>
                                    <td width="94">                                      
                                      <label for="zbs-status"><?php _e('Use Shipping',"zero-bs-crm"); ?></label>
                                    </td>
                                    <td>
                                      <input type="checkbox" name="wpzbscrm_shippingfortransactions" id="wpzbscrm_shippingfortransactions" value="1" <?php if (isset($settings['shippingfortransactions']) && $settings['shippingfortransactions'] == "1") echo ' checked="checked"'; ?> class="form-control" />
                                      <p style="margin-top:4px"><?php _e("Choose whether to use shipping against your transactions","zero-bs-crm");?></p>
                                    </td>
                                  </tr>

                                  <tr>
                                    <td width="94">                                      
                                      <label for="zbs-status"><?php _e('Use Paid/Completed Dates',"zero-bs-crm"); ?></label>
                                    </td>
                                    <td>
                                      <input type="checkbox" name="wpzbscrm_paiddatestransaction" id="wpzbscrm_paiddatestransaction" value="1" <?php if (isset($settings['paiddatestransaction']) && $settings['paiddatestransaction'] == "1") echo ' checked="checked"'; ?> class="form-control" />
                                      <p style="margin-top:4px"><?php _e("Choose whether to use store date paid, and date completed against your transactions","zero-bs-crm");?></p>
                                    </td>
                                  </tr>

                
                                </tbody>
                              </table>


                          </td>
                        </tr>
      
                      </tbody>

                  </table>


                  <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Funnels',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-statusprefix-custom-fields">

                        <tr>
                          <td colspan="2" style="padding:2%;">

                              <table class="table table-bordered table-striped wtab" id="funnel">
                                <tbody id="zbscrm-statusprefix-custom-fields">

                                  <tr>
                                    <td>
                                      <label for="zbs-funnel"><?php _e("Funnel Statuses","zero-bs-crm");?></label>
                                    </td>
                                    <td>

                                     <?php 

                                        #} retrieve value as simple CSV for now - simplistic at best.
                                        $zbsFunnelStr = ''; 
                                        #} stored here: $settings['customisedfields']
                                        if (isset($settings['zbsfunnel']) && !empty($settings['zbsfunnel'])) $zbsFunnelStr = $settings['zbsfunnel'];                                        
                                       

                                        if (empty($zbsFunnelStr)) {
                                          #} Defaults:
                                          $zbsFunnelStr = 'Lead,Contacted,Customer,Upsell';
                                        }                                        

                                      ?>
                                      <input type="text" name="zbs-funnel" id="zbs-funnel" value="<?php echo $zbsFunnelStr; ?>" class="form-control" />
                                      <p style="margin-top:4px"><?php _e("Enter which statuses you want to display in the funnel. Starting at the top of the funnel","zero-bs-crm");?>. e.g. Lead,Contacted,Customer,Upsell as a CSV value</p>
                                    </td>
                                  </tr>

                                  </tbody>
                                  </table>

                  </tbody>
                  </table>                                


                  <table class="table table-bordered table-striped wtab">
                    <tbody>

                        <tr>
                          <td colspan="2" class="wmid"><button type="submit" class="ui button primary"><?php _e('Save Field Options',"zero-bs-crm"); ?></button></td>
                        </tr>

                      </tbody>
                  </table>

              </form>

              <script type="text/javascript">

                // all custom js moved to admin.settings.js 12/3/19 :)

                var wpzbscrm_settings_page = 'fieldoptions'; // this fires init js in admin.settings.min.js
                var wpzbscrm_settings_lang = {

                        // e.g. customfield:'<?php zeroBSCRM_slashOut(__('Custom Field','zero-bs-crm')); ?>',

                };
                var wpzbscrm_settings_urls = {

                    // e.g. autonumberhelp: '<?php echo $zbs->urls['autonumberhelp']; ?>'

                };

              </script>
              
      </div><?php 
      
}


function zeroBSCRM_html_settings_tax(){

  global $wpdb, $zbs;  #} Req

  $confirmAct = false;
  $taxTables = zeroBSCRM_getTaxTableArr();

  #} Act on any edits!
  if (isset($_POST['editzbstax'])){

    // cycle through realistic potentials:
    $taxTableIDs = array(); // this stores a quick index, every ID not present will get culled after this.
    for ($i = 1; $i <= 64; $i++){

      // got deets?
      $thisLine = array(
                          'id' => -1,
                          'name' => '',
                          'rate' => 0.0
      );
      
      // ID
      if (isset($_POST['zbs-taxtable-line-' .$i. '-id'])){
        $potentialID = (int)sanitize_text_field( $_POST['zbs-taxtable-line-' .$i. '-id'] );
        if ($potentialID > 0) {
          $thisLine['id'] = $potentialID;
          $taxTableIDs[] = $potentialID;
        }
      }

      // name + rate
      if (isset($_POST['zbs-taxtable-line-' .$i. '-name'])) $thisLine['name'] = sanitize_text_field( $_POST['zbs-taxtable-line-' .$i. '-name'] );
      if (isset($_POST['zbs-taxtable-line-' .$i. '-rate'])) $thisLine['rate'] = (float)sanitize_text_field( $_POST['zbs-taxtable-line-' .$i. '-rate'] );

      if ($thisLine['rate'] > 0){

        // Debug echo 'adding: <pre>'.print_r($thisLine,1).'</pre>';

        // add/update
        $addedID = zeroBSCRM_taxRates_addUpdateTaxRate(array(

              'id' => $thisLine['id'],
              'data'          => array(

                  'name'   => $thisLine['name'],
                  'rate'     => $thisLine['rate']
                  
              )
        ));
        // add any newly added to id index (though actually, because taxTables got above, shouldn't occur)
        if ($thisLine['id'] == -1 && $addedID > 0) $taxTableIDs[] = $addedID;

      }

    }
    // cull all those ID's not found in post
    foreach ($taxTables as $rate){
      if (!in_array($rate['id'], $taxTableIDs)) zeroBSCRM_taxRates_deleteTaxRate(array('id'=>$rate['id']));
    }

    #} Reload
    $taxTables = zeroBSCRM_getTaxTableArr();
      
  }

  // Debug echo '<pre>'.print_r($taxTables,1).'</pre>';

  ?><p id="sbDesc"><?php _e('On this page you can set up different tax rates to use throughout your CRM (e.g. in Transactions).',"zero-bs-crm"); ?></p>

      <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
      <?php if (isset($sbreset)) if ($sbreset) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Reset',"zero-bs-crm")); echo '</div>'; } ?>
      
      <div id="sbA">
        <pre><?php // print_r($settings); ?></pre>

          <form method="post">
            <input type="hidden" name="editzbstax" id="editzbstax" value="1" />

                  <table class="table table-bordered table-striped wtab" id="zbs-taxtable-table">
               
                   <thead>
                    
                        <tr>
                            <th colspan="3" class="wmid"><button type="button" class="ui icon button zbs-taxtable-add-rate right floated" title="<?php _e('Add Rate',"zero-bs-crm"); ?>"><i class="plus icon"></i></button><?php _e('Tax Rates',"zero-bs-crm"); ?>:</th>                            
                        </tr>
                    
                        <tr>
                            <th><?php _e('Name',"zero-bs-crm"); ?>:</th>
                            <th><?php _e('Rate',"zero-bs-crm"); ?>:</th>
                            <th></th>                          
                        </tr>

                    </thead>
                    
                    <tbody>

                      <tr id="zbs-taxtable-loader">
                        <td colspan="3" class="wmid"><div class="ui padded segment loading borderless" id="zbs-taxtables-loader">&nbsp;</div></td>
                      </tr>
              
            
                      
                    </tbody>
                  </table>                
                <table class="table" id="zbsNoTaxRateResults"<?php if (!is_array($taxTables) || count($taxTables) == 0) echo '';  else echo ' style="display:none"'; ?>>
                  <tbody>
                      <tr>
                        <td colspan="2" class="wmid">
                          <div class="ui info icon message">
                            <div class="content">
                              <div class="header"><?php _e('No Tax Rates',"zero-bs-crm"); ?></div>
                              <p><?php _e('There are no tax rates defined yet, do you want to',"zero-bs-crm"); echo ' <a href="#" id="zbs-new-add-tax-rate">'.__('Create one',"zero-bs-crm").'</a>?'; ?></p>
                            </div>
                          </div>                          
                        </td>
                      </tr>

                    </tbody>
                <table class="table table-bordered table-striped wtab">
                  <tbody>

                      <tr>
                        <td colspan="2" class="wmid"><button type="submit" class="ui button primary"><?php _e('Save Settings',"zero-bs-crm"); ?></button></td>
                      </tr>

                    </tbody>
                </table>

            </form>


            <script type="text/javascript">

              var zeroBSCRMJS_taxTable = <?php echo json_encode($taxTables); ?>;
              var zeroBSCRMJS_taxTableLang = {

                  defaulTaxName: '<?php echo zeroBSCRM_slashOut(__('Tax Rate Name','zero-bs-crm')); ?>',
                  defaulTaxPerc: '<?php echo zeroBSCRM_slashOut(__('Tax Rate %','zero-bs-crm')); ?>',
                  percSymbol: '<?php echo zeroBSCRM_slashOut(__('%','zero-bs-crm')); ?>',

                };

              jQuery(document).ready(function(){

                  // anything to build?
                  if (window.zeroBSCRMJS_taxTable.length > 0)
                    jQuery.each(window.zeroBSCRMJS_taxTable,function(ind,ele){

                        zeroBSCRMJS_taxTables_addLine(ele);

                    });

                  // remove loader
                  jQuery('#zbs-taxtable-loader').remove();

                  // bind what's here
                  zeroBSCRMJS_bind_taxTables();

              });

              function zeroBSCRMJS_bind_taxTables(){

                  jQuery('#zbs-new-add-tax-rate').unbind('click').click(function(){

                      // add a line
                      zeroBSCRMJS_taxTables_addLine();

                      // hide msg
                      jQuery('#zbsNoTaxRateResults').hide();

                  });
                  

                  jQuery('.zbs-taxtable-add-rate').unbind('click').click(function(){

                      // add a new line
                      zeroBSCRMJS_taxTables_addLine();

                  });

                  jQuery('.zbs-taxtable-remove-rate').unbind('click').click(function(){

                      var that = this;

                        swal({
                            title: '<?php _e('Are you sure?','zero-bs-crm'); ?>',
                            text: '<?php _e('Are you sure you want to delete this tax rate? This will remove it from your database and existing transactions with this tax rate will not show properly. You cannot undo this','zero-bs-crm'); ?>',
                            type: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: '<?php _e('Yes remove the tax rate','zero-bs-crm'); ?>',
                          })//.then((result) => {
                            .then(function (result) {
                            if (typeof result.value != "undefined" && result.value) {

                              var thisThat = that;

                              // brutal.
                              jQuery(thisThat).closest('.zbs-taxtable-line').remove();

                            }
                          });

                  });

                  // numbersOnly etc. 
                  zbscrm_JS_bindFieldValidators();
              }

              function zeroBSCRMJS_taxTables_addLine(line){

                  // gen the html
                  var html = zeroBSCRMJS_taxTables_genLine(line);

                  // append to table
                  jQuery('#zbs-taxtable-table tbody').append(html);

                  // rebind
                  zeroBSCRMJS_bind_taxTables();

              }

              function zeroBSCRMJS_taxTables_genLine(line){

                  var i = jQuery('.zbs-taxtable-line').length + 1;
                  var namestr = '', rateval = '', thisID = -1;
                  if (typeof line != "undefined" && typeof line.id != "undefined") thisID = line.id;
                  if (typeof line != "undefined" && typeof line.name != "undefined") namestr = line.name;
                  if (typeof line != "undefined" && typeof line.rate != "undefined") rateval = line.rate;

                  var html = '';

                    html += '<tr class="zbs-taxtable-line">';
                      html += '<td>';
                        html += '<input type="hidden" name="zbs-taxtable-line-' + i + '-id" value="' + thisID + '" />';
                        html += '<div class="ui fluid input"><input type="text" class="winput form-control" name="zbs-taxtable-line-' + i + '-name" id="zbs-taxtable-line-' + i + '-name" value="' + namestr + '" placeholder="' + window.zeroBSCRMJS_taxTableLang.defaulTaxName + '" /></div>';
                      html += '</td>';
                      html += '<td>';
                        html += '<div class="ui right labeled input">';
                          html += '<input type="text" class="winput form-control numbersOnly zbs-dc" name="zbs-taxtable-line-' + i + '-rate" id="zbs-taxtable-line-' + i + '-rate" value="' + rateval + '" placeholder="' + window.zeroBSCRMJS_taxTableLang.defaulTaxPerc + '"  />';
                        html += '<div class="ui basic label">' + window.zeroBSCRMJS_taxTableLang.percSymbol + '</div></div>';
                      html += '</td>'; 
                      html += '<td class="wmid">';
                        html += '<button type="button" class="ui icon button zbs-taxtable-remove-rate"><i class="close icon"></i></button>';
                      html += '</td>';                          
                    html += '</tr>';

                  return html;
              }

            </script>
            
    </div><?php 

}

#} Transactions settings (which type to include in total etc....)
function zeroBSCRM_html_settings_transactions(){
  // settings page for transactions. Expanded WooSync and Stripe Sync (and Sales Dash)
  // this will add settings for which type to include in
  /*
      1. which status to count in MRR (i.e. 'subscription')
      2. which to count in churn (i.e. 'refunded' and 'cancelled')
      3. for now, just allow the checkbox for which to include in 'transaction total'

  */

  global $wpdb, $zbs;  #} Req

  $confirmAct = false;
  $settings = $zbs->settings->getAll();  


    #} Act on any edits!
  if (isset($_POST['editwplf']) && zeroBSCRM_isZBSAdminOrAdmin()){

      // check nonce
      check_admin_referer( 'zbs-update-settings-transactions' );

      $zbsStatusSetting = 'all'; $zbsStatusSettingPotential = array();


      $zbsStatusStr = zeroBSCRM_getTransactionsStatuses();


      $zbsStatuses = explode(',', $zbsStatusStr); 
      if (is_array($zbsStatuses)) foreach ($zbsStatuses as $statusStr){

 
          // permify
          $statusKey = strtolower(str_replace(' ','_',str_replace(':','_',$statusStr)));


          // check post
          if (isset($_POST['wpzbscrm_transstatus_group_'.$statusKey])) $zbsStatusSettingPotential[] = $statusKey;

      }

      if (count($zbsStatusSettingPotential) > 0) {

        // set that
        $zbsStatusSetting = $zbsStatusSettingPotential;

      }

      // update
      $updatedSettings['transinclude_status'] = $zbsStatusSetting;

    #} Brutal update
    foreach ($updatedSettings as $k => $v) $zbs->settings->update($k,$v);

      #} $msg out!
      $sbupdated = true;

      #} Reload
      $settings = $zbs->settings->getAll();

    }

    // reget trans statuses
    $zbsStatusStr = zeroBSCRM_getTransactionsStatuses();

  ?>

        <p id="sbDesc"><?php _e('Here are some general transaction settings to help you control how transactions impact your overall CRM',"zero-bs-crm"); ?></p>

        <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
        
        <div id="sbA">
          <!--<pre><?php #print_r($settings); ?></pre>-->

            <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=transactions">
              <input type="hidden" name="editwplf" id="editwplf" value="1" />
              <?php 
                // add nonce
                wp_nonce_field( 'zbs-update-settings-transactions');
              ?>

               <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Transaction Settings',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-companies-custom-fields">

                  <tr>
                    <td class="wfieldname"><label for="wpzbscrm_transinclude"><?php _e('Include these statuses in total value',"zero-bs-crm"); ?>:</label><br /><?php _e('Tick which statuses to include in the total transaction value and total value of your contacts.',"zero-bs-crm"); ?>
                    <br /><?php _e('These are shown in the manage contact screen.',"zero-bs-crm"); ?>
                    <br /><br /></td>
                    <td style="width:540px" id="zbs-transaction-include-status">
                      <?php

                        $selectedStatuses = 'all'; 
                        if (isset($settings['transinclude_status'])) $selectedStatuses = $settings['transinclude_status'];

                        $zbsStatuses = explode(',', $zbsStatusStr);
                        if (is_array($zbsStatuses)) {

                            // each status
                            foreach ($zbsStatuses as $statusStr){

                                // permify
                                $statusKey = strtolower(str_replace(' ','_',str_replace(':','_',$statusStr)));

                                // checked?
                                $checked = false; 
                                if (
                                      (!is_array($selectedStatuses) && $selectedStatuses == 'all')
                                      ||
                                      (is_array($selectedStatuses) && in_array($statusKey,$selectedStatuses))
                                    ) $checked = true;

                              ?><div class="zbs-status">
                                  <input type="checkbox" value="1" name="wpzbscrm_transstatus_group_<?php echo $statusKey; ?>" id="wpzbscrm_transstatus_group_<?php echo $statusKey; ?>"<?php if ($checked) echo ' checked="checked"'; ?> />
                                  <label for="wpzbscrm_transstatus_group_<?php echo $statusKey; ?>"><?php echo $statusStr; ?></label>
                                </div><?php

                            }

                        } else _e('No Statuses Found',"zero-bs-crm");


                      ?>
                    </td>
                  </tr>

                </tbody>

              </table>


                  <table class="table table-bordered table-striped wtab">
                    <tbody>

                        <tr>
                          <td colspan="2" class="wmid"><button type="submit" class="ui button primary"><?php _e('Save Settings',"zero-bs-crm"); ?></button></td>
                        </tr>

                      </tbody>
                  </table>


                </form>

<?php

}


#} Let's an admin specify an alternative SMTP setup for mails to send from
#} Used by Mail Campaigns and internally (e.g. client portal welcome emails)
function zeroBSCRM_html_settings_mail_delivery(){

  global $zbs;  #} Req

  // check if running locally (Then smtp may not work, e.g. firewalls + pain)
  $runningLocally = zeroBSCRM_isLocal(true);

  ?><div id="zbs-mail-delivery-wrap"><?php

  #} SMTP Configured?
  $zbsSMTPAccs = zeroBSCRM_getMailDeliveryAccs(); 
  $defaultMailOptionIndex = zeroBSCRM_getMailDeliveryDefault();
      

  // TEMP DELETES ACCS: global $zbs->settings; $zbs->settings->update('smtpaccs',array());

  
  #} Defaults
  $defaultFromDeets = zeroBSCRM_wp_retrieveSendFrom();

  // Temp print_r($defaultFromDeets);

  if (count($zbsSMTPAccs) <= 0){

      #} ====================================
      #} No settings yet :)
          ?><h1 class="ui header blue zbs-non-wizard" style="margin-top: 0;"><?php _e('Mail Delivery',"zero-bs-crm"); ?></h1>

            <div class="ui icon big message zbs-non-wizard">
              <i class="wordpress icon"></i>
              <div class="content">
                <div class="header">
                  <?php _e('ZBS CRM is using the default WordPress email delivery',"zero-bs-crm"); ?>
                </div>
                <hr />
                <p><?php _e('By default Zero BS CRM is configured to use wp_mail to send out all emails. This means your emails will go out from the basic wordpress@yourdomain.com style sender. This isn\'t great for deliverability, or your branding.',"zero-bs-crm"); ?></p>
                <div><?php _e('Currently mail is sent from',"zero-bs-crm"); echo ' <div class="ui large teal horizontal label">'.$defaultFromDeets['name'].' ('.$defaultFromDeets['email'].')</div><br />'.__('Do you want to set up a different Mail Delivery option?',"zero-bs-crm"); ?></div>
                <div style="padding:2em 0 1em 2em">
                    <button type="button" id="zbs-mail-delivery-start-wizard" class="ui huge primary button"><?php _e('Start Wizard','zero-bs-crm'); ?></button>
                </div>
              </div>
            </div>


          <?php
      #} ====================================



  } else {

      #} ====================================
      #} Has settings, dump them out :)
      // debug 
      //echo '<pre>'; print_r($zbsSMTPAccs); echo '</pre>';
      //$key = zeroBSCRM_mailDelivery_makeKey($zbsSMTPAccs[0]); echo 'key:'.$key.'!';

      ?><div id="zbs-mail-delivery-account-list-wrap">
        <h1 class="ui header blue zbs-non-wizard" style="margin-top: 0;"><?php _e('Mail Delivery',"zero-bs-crm"); ?></h1>
          <table class="ui celled table zbs-non-wizard">
          <thead>
            <tr>
              <th><?php _e('Outbound Account',"zero-bs-crm"); ?></th>
              <th style="text-align:center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $detailMode = false; $accIndx = 0; 
            if (count($zbsSMTPAccs) > 1) $detailMode = true;
            foreach ($zbsSMTPAccs as $accKey => $acc){ 
              
                // reset
                $isDefault = false; 

            ?>
            <tr id="zbs-mail-delivery-<?php echo $accKey; ?>">
              <td class="zbs-mail-delivery-item-details"><?php 

                if (count($zbsSMTPAccs) == 1 && $accIndx == 0) {
                  $isDefault = true;
                } else {

                  if ($accKey == $defaultMailOptionIndex) $isDefault = true;

                }

                if ($isDefault) { ?><div class="ui ribbon label zbs-default"><?php _e('Default',"zero-bs-crm"); ?></div><?php }

                #} account name etc.
                $accStr = '';
                if (isset($acc['fromname'])) $accStr = $acc['fromname'];
                if (isset($acc['fromemail'])) {
                  if (!empty($accStr)) 
                    $accStr .= ' &lt;'.$acc['fromemail'].'&gt;';
                  else
                    $accStr .= $acc['fromemail'];
                }
                echo $accStr;

                #} Mode label
                ?>&nbsp;&nbsp;<div class="ui purple horizontal label"><?php
                $modeStr = 'wp_mail';
                if (isset($acc['mode']) && $acc['mode'] == 'smtp') $modeStr = 'SMTP';
                echo $modeStr;
                ?></div><?php

                #} Detail
                $detailStr = '';
                if (isset($acc['host']) && !empty($acc['host'])) $detailStr = $acc['host'];
                if ($detailMode) echo '<div class="zbs-mail-delivery-detail">'.$detailStr.'</div>';

                ?></td>
              <td style="text-align:center">
                <button type="button" class="ui tiny green button zbs-test-mail-delivery" data-from="<?php echo $acc['fromemail']; ?>" data-indx="<?php echo $accKey; ?>"><i class="icon mail"></i> Send Test</button>&nbsp;
                <button type="button" class="ui tiny orange button zbs-remove-mail-delivery" data-indx="<?php echo $accKey; ?>"><i class="remove circle icon"></i> Remove</button>&nbsp;
                <button type="button" class="ui tiny teal button zbs-default-mail-delivery<?php if ($isDefault) echo ' disabled'; ?>" data-indx="<?php echo $accKey; ?>"><i class="check circle outline icon"></i> Set as Default</button>
              </td>
            </tr>
            <?php $accIndx++; } ?>
          </tbody>
          <tfoot>
            <tr><th colspan="2">
              <div class="ui right floated">
                <button type="button" id="zbs-mail-delivery-start-wizard" class="ui primary button right floated"><i class="add circle icon"></i> <?php _e('Add Another','zero-bs-crm'); ?></button>
              </div>
            </th>
          </tr></tfoot>
        </table>
      </div><?php

      #} ====================================

  }



  /* Wizard? */

  #} Default
  $smtpAcc = array(
    'sendfromname' => '',
    'sendfromemail' => ''
  );

  #} Never run, so autofill with wp defaults?
  if (count($zbsSMTPAccs) == 0){

      // should these be the active user, really?
      $smtpAcc['sendfromname'] = $defaultFromDeets['name'];
      $smtpAcc['sendfromemail'] = $defaultFromDeets['email'];

  }


  ?><div id="zbs-mail-delivery-wizard-wrap" class="hidden">
        

        <!--<h1 class="ui header blue" style="margin-top: 0;"><?php _e('Mail Delivery Setup',"zero-bs-crm"); ?></h1>-->


        <div class="ui three top attached steps">
          <div class="active step zbs-top-step-1">
            <i class="address card outline icon"></i>
            <div class="content">
              <div class="title"><?php _e('Sender Details',"zero-bs-crm"); ?></div>
              <div class="description"><?php _e('Who are you?',"zero-bs-crm"); ?></div>
            </div>
          </div>
          <div class="disabled step zbs-top-step-2">
            <i class="server icon"></i>
            <div class="content">
              <div class="title"><?php _e('Mail Server',"zero-bs-crm"); ?></div>
              <div class="description"><?php _e('Your SMTP Settings',"zero-bs-crm"); ?></div>
            </div>
          </div>
          <div class="disabled step zbs-top-step-3">
            <i class="open envelope outline icon"></i>
            <div class="content">
              <div class="title"><?php _e('Confirmation',"zero-bs-crm"); ?></div>
              <div class="description"><?php _e('Test &amp; Verify',"zero-bs-crm"); ?></div>
            </div>
          </div>
        </div>
        <div class="ui attached segment borderless" id="zbs-mail-delivery-wizard-steps-wrap">
          

          <!-- Step 1: -->
          <div id="zbs-mail-delivery-wizard-step-1-wrap" class="zbs-step">

            <h1 class="ui header"><?php _e('Sender Details',"zero-bs-crm"); ?> <i class="address card outline icon"></i></h1>

            <div class="ui very padded segment borderless"><p><?php _e('Enter your "Send From" details below. For best brand impact we recommend using a same-domain email address, although any you have SMTP details for will work.',"zero-bs-crm"); ?> <?php ##WLREMOVE ?><a href="https://zerobscrm.com/kb/knowledge-base/mail-delivery-method-setup-smtp/"><?php _e('See the Guide',"zero-bs-crm"); ?></a><?php ##/WLREMOVE ?></p></div>

            <div class="ui inverted zbsdarkgradient segment">
              <div class="ui inverted form">
                <div class="field">
                  <label><?php _e('Send From Name',"zero-bs-crm"); ?>:</label>
                  <input id="zbs-mail-delivery-wizard-sendfromname" placeholder="<?php _e('e.g. Mike Mikeson',"zero-bs-crm"); ?>" type="text" value="<?php echo $smtpAcc['sendfromname']; ?>">
                  <div class="ui pointing label hidden" id="zbs-mail-delivery-wizard-sendfromname-error"></div>
                </div>
                <div class="field">
                  <label><?php _e('Send From Email',"zero-bs-crm"); ?>:</label>
                  <input id="zbs-mail-delivery-wizard-sendfromemail" placeholder="<?php _e('e.g. your@domain.com',"zero-bs-crm"); ?>" type="text" value="<?php echo $smtpAcc['sendfromemail']; ?>">
                  <div class="ui pointing label hidden" id="zbs-mail-delivery-wizard-sendfromemail-error"></div>
                </div>
                <div class="ui zbsclear">
                  <button type="button" class="ui button positive right floated" id="zbs-mail-delivery-wizard-step-1-submit"><?php _e('Next',"zero-bs-crm"); ?></button>
                </div>

              </div>
            </div>

          </div>
          <!-- / Step 1 -->

          <!-- Step 2: -->
          <div id="zbs-mail-delivery-wizard-step-2-wrap" class="hidden zbs-step">

            <h1 class="ui header"><?php _e('Mail Server',"zero-bs-crm"); ?> <i class="server icon"></i></h1>

            <div class="ui very padded segment borderless"><p><?php _e('ZBS can send out emails via your default server settings, or via an SMTP server (Mail server). If you\'d like to reliably send emails from a custom domain, we recommend entering SMTP details here.',"zero-bs-crm"); ?> <a href="https://zerobscrm.com/kb/knowledge-base/mail-delivery-method-setup-smtp/"><?php _e('See the Guide',"zero-bs-crm"); ?></a></p></div>

            <div class="ui inverted zbsdarkgradient segment">
              <div class="ui inverted form">

                <div class="field">
                  <div class="ui radio checkbox" id="zbs-mail-delivery-wizard-step-2-servertype-wpmail">
                    <input type="radio" name="servertype" checked="checked" tabindex="0" class="hidden">
                    <label><?php _e('Default WordPress Mail (wp_mail)',"zero-bs-crm"); ?></label>
                  </div>
                </div>



                  <div class="ui grid">
                    <div class="eight wide column">
                
                        <div class="field">
                          <div class="ui radio checkbox" id="zbs-mail-delivery-wizard-step-2-servertype-smtp">
                            <input type="radio" name="servertype" tabindex="0" class="hidden">
                            <label><?php _e('Custom Mail Server (SMTP)',"zero-bs-crm"); ?></label>
                          </div>
                        </div>

                    </div>
                    <div class="eight wide column hidden">

                      <div class="hidden" id="zbs-mail-delivery-wizard-step-2-prefill-smtp">
                        <div class="field">
                          <label for="smtpCommonProviders"><?php _e('Quick-fill SMTP Details',"zero-bs-crm"); ?>:</label>
                          <select>
                            <option value="-1" selected="selected" disabled="disabled"><?php _e('Select a Common Provider',"zero-bs-crm"); ?>:</option>
                            <option value="-1" disabled="disabled">=================</option>
                            <?php # Hard typed: <option value="ses1" data-host="email-smtp.us-east-1.amazonaws.com" data-auth="tls" data-port="587" data-example="AKGAIR8K9UBGAZY5UMLA">AWS SES US East (N. Virginia)</option><option value="ses3" data-host="email-smtp.us-west-2.amazonaws.com" data-auth="tls" data-port="587" data-example="AKGAIR8K9UBGAZY5UMLA">AWS SES US West (Oregon)</option><option value="ses2" data-host="email-smtp.eu-west-1.amazonaws.com" data-auth="tls" data-port="587" data-example="AKGAIR8K9UBGAZY5UMLA">AWS SES EU (Ireland)</option><option value="sendgrid" data-host="smtp.sendgrid.net" data-auth="tls" data-port="587" data-example="you@yourdomain.com">SendGrid</option><option value="gmail" data-host="smtp.gmail.com" data-auth="ssl" data-port="465" data-example="you@gmail.com">GMail</option><option value="outlook" data-host="smtp.live.com" data-auth="tls" data-port="587" data-example="you@outlook.com">Outlook.com</option><option value="office365" data-host="smtp.office365.com" data-auth="tls" data-port="587" data-example="you@office365.com">Office365.com</option><option value="yahoo" data-host="smtp.mail.yahoo.com" data-auth="ssl" data-port="465" data-example="you@yahoo.com">Yahoo Mail</option><option value="yahooplus" data-host="plus.smtp.mail.yahoo.com" data-auth="ssl" data-port="465" data-example="you@yahoo.com">Yahoo Mail Plus</option><option value="yahoouk" data-host="smtp.mail.yahoo.co.uk" data-auth="ssl" data-port="465" data-example="you@yahoo.co.uk">Yahoo Mail UK</option><option value="aol" data-host="smtp.aol.com" data-auth="tls" data-port="587" data-example="you@aol.com">AOL.com</option><option value="att" data-host="smtp.att.yahoo.com" data-auth="ssl" data-port="465" data-example="you@att.com">AT&amp;T</option><option value="hotmail" data-host="smtp.live.com" data-auth="tls" data-port="587" data-example="you@hotmail.com">Hotmail</option><option value="oneandone" data-host="smtp.1and1.com" data-auth="tls" data-port="587" data-example="you@yourdomain.com">1 and 1</option><option value="zoho" data-host="smtp.zoho.com" data-auth="ssl" data-port="465" data-example="you@zoho.com">Zoho</option><option value="mailgun" data-host="smtp.mailgun.org" data-auth="ssl" data-port="465" data-example="postmaster@YOUR_DOMAIN_NAME">MailGun</option><option value="oneandonecom" data-host="smtp.1and1.com" data-auth="tls" data-port="587" data-example="you@yourdomain.com">OneAndOne.com</option><option value="oneandonecouk" data-host="auth.smtp.1and1.co.uk" data-auth="tls" data-port="587" data-example="you@yourdomain.co.uk">OneAndOne.co.uk</option>

                            #} This allows easy update though :)
                            $commonSMTPSettings = zeroBSCRM_mailDelivery_commonSMTPSettings();
                            foreach ($commonSMTPSettings as $settingPerm => $settingArr){

                              echo '<option value="'.$settingPerm.'" data-host="'.$settingArr['host'].'" data-auth="'.$settingArr['auth'].'" data-port="'.$settingArr['port'].'" data-example="'.$settingArr['userexample'].'">'.$settingArr['name'].'</option>';

                            }

                            ?>
                          </select>
                        </div> <!-- .field -->
                      </div>

                    </div>
                  </div>

                <div class="ui inverted zbstrans segment hidden" id="zbs-mail-delivery-wizard-step-2-smtp-wrap">

                  <!-- SMTP DEETS -->
                  <div class="ui grid">
                    <div class="eight wide column">

                        <div class="required field">
                          <label for="zbs-mail-delivery-wizard-step-2-smtp-host"><?php _e('SMTP Address',"zero-bs-crm"); ?></label>
                          <input type="text" placeholder="e.g. pro.turbo-smtp.com" id="zbs-mail-delivery-wizard-step-2-smtp-host" class="mailInp" value="" />
                          <div class="ui pointing label hidden" id="zbs-mail-delivery-wizard-smtphost-error"></div>
                        </div> <!-- .field -->
                        <div class="required field">
                          <label for="zbs-mail-delivery-wizard-step-2-smtp-port"><?php _e('SMTP Port',"zero-bs-crm"); ?></label>
                          <div class="seven wide field">
                            <input type="text" placeholder="e.g. 587 or 465" id="zbs-mail-delivery-wizard-step-2-smtp-port" class="mailInp" value="587" />
                          </div>
                          <div class="ui pointing label hidden" id="zbs-mail-delivery-wizard-smtpport-error"></div>
                        </div> <!-- .field -->
                        
                        <!--
                        <div class="ui toggle checkbox">
                          <input type="checkbox" name="public">
                          <label>Use SSL Authentication</label>
                        </div>
                        -->
                    </div>
                    <div class="eight wide column">

                        
                          <div class="required field">
                            <label for="zbs-mail-delivery-wizard-step-2-smtp-user"><?php _e('Username',"zero-bs-crm"); ?></label>
                            <input type="text" placeholder="e.g. mike or mike@yourdomain.com" id="zbs-mail-delivery-wizard-step-2-smtp-user" class="mailInp" value="" autocomplete="new-smtpuser-<?php echo time(); ?>" />
                            <div class="ui pointing label hidden" id="zbs-mail-delivery-wizard-smtpuser-error"></div>
                          </div> <!-- .field -->
                          
                          <div class="required field">
                            <label for="zbs-mail-delivery-wizard-step-2-smtp-pass"><?php _e('Password',"zero-bs-crm"); ?></label>
                            <input type="text" placeholder="" id="zbs-mail-delivery-wizard-step-2-smtp-pass" class="mailInp" value="" autocomplete="new-password-<?php echo time(); ?>" />
                            <div class="ui pointing label hidden" id="zbs-mail-delivery-wizard-smtppass-error"></div>
                          </div> <!-- .field -->

                    </div>
                  </div>
                  <!-- / SMTP DEETS -->
                  

                </div>
                <div class="ui zbsclear">
                  <button type="button" class="ui button" id="zbs-mail-delivery-wizard-step-2-back"><?php _e('Back',"zero-bs-crm"); ?></button>
                  <button type="button" class="ui button positive right floated" id="zbs-mail-delivery-wizard-step-2-submit"><?php _e('Validate Settings',"zero-bs-crm"); ?></button>
                </div>
              </div> <!-- / inverted segment -->

              <div class="ui very padded segment borderless">
                <p><?php _e('After this step ZBS CRM will probe your server and attempt to send a test email in order to validate your settings.',"zero-bs-crm"); ?></p>
              </div>

            </div>

          </div>
          <!-- / Step 2 -->



          <!-- Step 3: -->
          <div id="zbs-mail-delivery-wizard-step-3-wrap" class="hidden zbs-step">

            <h1 class="ui header"><?php _e('Confirmation',"zero-bs-crm"); ?> <i class="open envelope outline icon"></i></h1>

            
              <div class="ui inverted zbsdarkgradient segment" id="zbs-mail-delivery-wizard-validate-console-wrap">

                <div class="ui padded zbsbigico segment loading borderless" id="zbs-mail-delivery-wizard-validate-console-ico">&nbsp;</div>
              
                <div class="ui padded segment borderless" id="zbs-mail-delivery-wizard-validate-console"><?php _e('Attempting to connect to mail server...',"zero-bs-crm"); ?></div>

                <div class="ui padded segment borderless hidden" id="zbs-mail-delivery-wizard-admdebug"></div>

                <div class="ui zbsclear">
                  <button type="button" class="ui hidden button" id="zbs-mail-delivery-wizard-step-3-back"><?php _e('Back',"zero-bs-crm"); ?></button>
                  <button type="button" class="ui button positive right floated disabled" id="zbs-mail-delivery-wizard-step-3-submit"><?php _e('Finish',"zero-bs-crm"); ?></button>
                </div>
                
              </div>
            </div>

          </div>
          <!-- / Step 3 -->
        </div>



    </div>

          <?php if ($runningLocally){

            ?><div class="ui message"><div class="header"><div class="ui yellow label"><?php _e('Local Machine?','zero-bs-crm'); ?></div></div><p><?php _e('It appears you are running Zero BS CRM locally, this may cause SMTP delivery methods to behave unexpectedly.<br />(e.g. your computer may block outgoing SMTP traffic via firewall or anti-virus software).<br />Zero BS CRM may require external web hosting to properly send via SMTP.','zero-bs-crm'); ?></p></div><?php

            
          } ?>


  <style type="text/css">

    /*
        see scss
    */

  </style>

  <script type="text/javascript">

  var zeroBSCRM_sToken = '<?php echo wp_create_nonce( "wpzbs-ajax-nonce" ); ?>';
  var zeroBSCRM_currentURL = '<?php echo admin_url( "admin.php?page=zerobscrm-plugin-settings&tab=maildelivery" ); ?>';

  var zeroBSCRMJS_lang = {

    // generic
    pleaseEnter : '<?php zeroBSCRM_slashOut(__('Please enter a value',"zero-bs-crm")); ?>',
    pleaseEnterEmail : '<?php zeroBSCRM_slashOut(__('Please enter a valid email address',"zero-bs-crm")); ?>',
    thanks : '<?php zeroBSCRM_slashOut(__('Thank you',"zero-bs-crm")); ?>',
    defaultText: '<?php zeroBSCRM_slashOut(__('Default',"zero-bs-crm")); ?>',

    // email delivery setup
    settingsValidatedWPMail: '<?php zeroBSCRM_slashOut(__('Your Email Delivery option has been validated. A test email has been sent via wp_mail, the default WordPress mail provider, to: ',"zero-bs-crm")); ?>',
    settingsValidatedWPMailError: '<?php zeroBSCRM_slashOut(__('There was an error sending a mail via wp_mail. Please go back and check your email address, if this persists please',"zero-bs-crm")); ?> <a href="<?php echo $zbs->urls['support']; ?>" target="_blank"><?php zeroBSCRM_slashOut(__('contact support',"zero-bs-crm")); ?></a>',
    settingsValidateSMTPProbing : '<?php zeroBSCRM_slashOut(__('Probing your mail server (this may take a few seconds)...',"zero-bs-crm")); ?>',
    settingsValidateSMTPPortCheck : '<?php zeroBSCRM_slashOut(__('Checking Ports are Open (this may take a few seconds)...',"zero-bs-crm")); ?>',
    settingsValidateSMTPAttempt : '<?php zeroBSCRM_slashOut(__('Attempting to send test email...',"zero-bs-crm")); ?>',
    settingsValidateSMTPSuccess : '<?php zeroBSCRM_slashOut(__('Test email sent...',"zero-bs-crm")); ?>',
    settingsValidateGMAIL : '<?php 
    ##WLREMOVE 
    zeroBSCRM_slashOut(__('GMail sometimes requires further steps to complete, please',"zero-bs-crm")); ?> <a href="https://zerobscrm.com/kb/knowledge-base/using-gmail-with-zbs-crm-mail-delivery-system/" target="_blank"><?php zeroBSCRM_slashOut(__('Read the Gmail Setup guide','zero-bs-crm'));
    ?></a><?php
    ##/WLREMOVE  

    // nout
    ?>',

    settingsValidatedSMTP: '<?php zeroBSCRM_slashOut(__('Your Email Delivery option has been validated. A test email has been sent via SMTP to the address below. Please check you recieved this email to ensure a complete test.',"zero-bs-crm")); ?> <a href="#debug" id="zbs-mail-delivery-showdebug"><?php zeroBSCRM_slashOut(__('debug output','zero-bs-crm')); ?></a> (<?php zeroBSCRM_slashOut(__('click to view','zero-bs-crm')); ?>).',
    settingsValidatedSMTPProbeError: '<?php zeroBSCRM_slashOut(__('ZBS CRM has tested your settings, and also tried probing your mail server, but unfortunately it was not possible to confirm a test email was sent. Pleaase go back and check your settings, and if this persists please',"zero-bs-crm")); ?> <a href="<?php echo $zbs->urls['support']; ?>" target="_blank"><?php zeroBSCRM_slashOut(__('contact support',"zero-bs-crm")); ?></a>, optionally sending us the <a href="#debug" id="zbs-mail-delivery-showdebug"><?php zeroBSCRM_slashOut(__('debug output','zero-bs-crm')); ?></a> (<?php zeroBSCRM_slashOut(__('click to view','zero-bs-crm')); ?>).',
    settingsValidatedSMTPGeneralError: '<?php zeroBSCRM_slashOut(__('There was an error sending a mail via SMTP. Please go back and check your settings, and if this persists please',"zero-bs-crm")); ?> <a href="<?php echo $zbs->urls['support']; ?>" target="_blank"><?php zeroBSCRM_slashOut(__('contact support',"zero-bs-crm")); ?></a><?php zeroBSCRM_slashOut(__(', optionally sending us the','zero-bs-crm')); ?> <a href="#debug" id="zbs-mail-delivery-showdebug"><?php zeroBSCRM_slashOut(__('debug output','zero-bs-crm')); ?></a> (<?php zeroBSCRM_slashOut(__('click to view','zero-bs-crm')); ?>).',

    // send test from list view
    sendTestMail: '<?php zeroBSCRM_slashOut(__('Send a test email from',"zero-bs-crm"));?>',
    sendTestButton: '<?php zeroBSCRM_slashOut(__('Send test',"zero-bs-crm"));?>',
    sendTestWhere: '<?php zeroBSCRM_slashOut(__('Which email address should we send the test email to?',"zero-bs-crm"));?>',
    sendTestFail: '<?php zeroBSCRM_slashOut(__('There was an error sending this test',"zero-bs-crm"));?>',
    sendTestSent: '<?php zeroBSCRM_slashOut(__('Test Sent Successfully',"zero-bs-crm"));?>',
    sendTestSentSuccess: '<?php zeroBSCRM_slashOut(__('Test email was successfully sent to',"zero-bs-crm"));?>',
    sendTestSentFailed: '<?php zeroBSCRM_slashOut(__('Test email could not be sent (problem with this mail delivery method?)',"zero-bs-crm"));?>',

    // delete mail delivery method via list view
    deleteMailDeliverySureTitle: '<?php zeroBSCRM_slashOut(__('Are you sure?',"zero-bs-crm"));?>',
    deleteMailDeliverySureText: '<?php zeroBSCRM_slashOut(__('This will totally remove this mail delivery method from your Zero BS CRM.',"zero-bs-crm"));?>',
    deleteMailDeliverySureConfirm: '<?php zeroBSCRM_slashOut(__('Yes, remove it!',"zero-bs-crm"));?>',
    deleteMailDeliverySureDeletedTitle: '<?php zeroBSCRM_slashOut(__('Delivery Method Removed',"zero-bs-crm"));?>',
    deleteMailDeliverySureDeletedText: '<?php zeroBSCRM_slashOut(__('Your mail delivery method has been successfully removed.',"zero-bs-crm"));?>',
    deleteMailDeliverySureDeleteErrTitle: '<?php zeroBSCRM_slashOut(__('Delivery Method Not Removed',"zero-bs-crm"));?>',
    deleteMailDeliverySureDeleteErrText: '<?php zeroBSCRM_slashOut(__('There was a general error removing this mail delivery method.',"zero-bs-crm"));?>',

    // set mail delivery method  as default via list view
    defaultMailDeliverySureTitle: '<?php zeroBSCRM_slashOut(__('Are you sure?',"zero-bs-crm"));?>',
    defaultMailDeliverySureText: '<?php zeroBSCRM_slashOut(__('Do you want to default to this mail delivery method?',"zero-bs-crm"));?>',
    defaultMailDeliverySureConfirm: '<?php zeroBSCRM_slashOut(__('Set as Default',"zero-bs-crm"));?>',
    defaultMailDeliverySureDeletedTitle: '<?php zeroBSCRM_slashOut(__('Default Saved',"zero-bs-crm"));?>',
    defaultMailDeliverySureDeletedText: '<?php zeroBSCRM_slashOut(__('Your mail delivery method default has been successfully saved.',"zero-bs-crm"));?>',
    defaultMailDeliverySureDeleteErrTitle: '<?php zeroBSCRM_slashOut(__('Default Not Updated',"zero-bs-crm"));?>',
    defaultMailDeliverySureDeleteErrText: '<?php zeroBSCRM_slashOut(__('There was a general error when setting this mail delivery method default.',"zero-bs-crm"));?>',

    likelytimeout: '<?php zeroBSCRM_slashOut(__('The Wizard timed out when trying to connect to your Mail Server. This probably means your server is blocking the SMTP port you have specified, please check with them that they have these ports open. If they will not open the ports, you may have to use wp_mail mode.',"zero-bs-crm"));?>',

  };

  var zeroBSCRMJS_SMTPWiz = {

    sendFromName: '',
    sendFromEmail: '',
    serverType: 'wp_mail',
    smtpHost: '',
    smtpPort: '',
    smtpUser: '',
    smtpPass: ''

  };

  // generic func - can we standardise this (wh)?
  function zeroBSCRMJS_refreshPage(){
      
    window.location = window.zeroBSCRM_currentURL;

  }

  jQuery(document).ready(function(){

    // bind
    zeroBSCRMJS_mail_delivery_bindWizard();
    zeroBSCRMJS_mail_delivery_bindList();


  });

  // defaults for test delivery pass through for SWAL
  var zbsTestDelivery = false, zbsTestDeliveryMsg = '';

  // bind list view stuff
  function zeroBSCRMJS_mail_delivery_bindList(){

      jQuery('.zbs-test-mail-delivery').unbind('click').click(function(){

        // get deets
        var emailFrom = '', emailIndx = -1;

          emailIndx = jQuery(this).attr('data-indx');
          emailFrom = jQuery(this).attr('data-from');

          swal({
            title: window.zeroBSCRMJS_lang.sendTestMail + ' "' + emailFrom + '"',
            //text: window.zeroBSCRMJS_lang.sendTestWhere,
            input: 'email',
            inputValue: emailFrom, // prefill with itself
            showCancelButton: true,
            confirmButtonText: window.zeroBSCRMJS_lang.sendTestButton,
            showLoaderOnConfirm: true,
            preConfirm: function (email) {
              return new Promise(function (resolve, reject) {

                // localise indx
                var lIndx = emailIndx;

                // timeout for loading
                setTimeout(function() {
                  if (!zbscrm_JS_validateEmail(email)) {
                    reject(window.zeroBSCRMJS_lang.pleaseEnterEmail)
                  } else {

                     var data = {
                            'action': 'zbs_maildelivery_test',
                            'indx': lIndx,
                            'em': email,
                            'sec': window.zeroBSCRM_sToken
                          };

                          // Send it Pat :D
                          jQuery.ajax({
                            type: "POST",
                            url: ajaxurl,
                            "data": data,
                            dataType: 'json',
                            timeout: 20000,
                            success: function(response) {

                              // localise
                              var lEmail = email;

                              console.log(response);

                              window.zbsTestDelivery = 'success';
                              window.zbsTestDeliveryMsg = window.zeroBSCRMJS_lang.sendTestSentSuccess + ' ' + lEmail;

                              resolve();

                            },
                            error: function(response){

                              console.log(response);

                              window.zbsTestDelivery = 'fail';
                              window.zbsTestDeliveryMsg = window.zeroBSCRMJS_lang.sendTestSentFailed;
                              
                              resolve();

                            }

                          });


                  }
                }, 2000)
              })
            },
            allowOutsideClick: false
          }).then(function (email) {

            if (window.zbsTestDelivery == 'success'){

                swal({
                  type: 'success',
                  title: window.zeroBSCRMJS_lang.sendTestSent,
                  html: window.zbsTestDeliveryMsg
                });

            } else {

                swal({
                  type: 'warning',
                  title: window.zeroBSCRMJS_lang.sendTestFail,
                  html: window.zbsTestDeliveryMsg
                });

            }
          }).catch(swal.noop);

      });

      // REMOVE one :)
      jQuery('.zbs-remove-mail-delivery').unbind('click').click(function(){

        // get deets
        var emailIndx = -1;

          emailIndx = jQuery(this).attr('data-indx');


          swal({
            title: window.zeroBSCRMJS_lang.deleteMailDeliverySureTitle,
            text: window.zeroBSCRMJS_lang.deleteMailDeliverySureText,
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: window.zeroBSCRMJS_lang.deleteMailDeliverySureConfirm
          }).then(function (result) {
            if (result.value) {

              // localise indx
              var lIndx = emailIndx;

               var data = {
                      'action': 'zbs_maildelivery_remove',
                      'indx': lIndx,
                      'sec': window.zeroBSCRM_sToken
                    };

                    // Send it Pat :D
                    jQuery.ajax({
                      type: "POST",
                      url: ajaxurl,
                      "data": data,
                      dataType: 'json',
                      timeout: 20000,
                      success: function(response) {

                        console.log('del',response);

                        swal({
                          title: window.zeroBSCRMJS_lang.deleteMailDeliverySureDeletedTitle,
                          text: window.zeroBSCRMJS_lang.deleteMailDeliverySureDeletedText,
                          type: 'success',
                          // refresh onClose: zeroBSCRMJS_refreshPage
                          onClose: function(){

                            // remove line
                            llIndx = lIndx;
                            jQuery('#zbs-mail-delivery-' + llIndx).hide();

                          }
                        });

                      },
                      error: function(response){

                        console.error('del',response);

                        swal(
                          window.zeroBSCRMJS_lang.deleteMailDeliverySureDeleteErrTitle,
                          window.zeroBSCRMJS_lang.deleteMailDeliverySureDeleteErrText,
                          'warning'
                        );

                      }

                    });

              }

            
          });

      });



      // Set as default
      jQuery('.zbs-default-mail-delivery').unbind('click').click(function(){

        // get deets
        var emailIndx = -1;

          emailIndx = jQuery(this).attr('data-indx');


          swal({
            title: window.zeroBSCRMJS_lang.defaultMailDeliverySureTitle,
            text: window.zeroBSCRMJS_lang.defaultMailDeliverySureText,
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: window.zeroBSCRMJS_lang.defaultMailDeliverySureConfirm
          }).then(function () {

              // localise indx
              var lIndx = emailIndx;

               var data = {
                      'action': 'zbs_maildelivery_setdefault',
                      'indx': lIndx,
                      'sec': window.zeroBSCRM_sToken
                    };

                    // Send it Pat :D
                    jQuery.ajax({
                      type: "POST",
                      url: ajaxurl,
                      "data": data,
                      dataType: 'json',
                      timeout: 20000,
                      success: function(response) {

                        console.log('def',response);

                        swal({
                          title: window.zeroBSCRMJS_lang.defaultMailDeliverySureDeletedTitle,
                          text: window.zeroBSCRMJS_lang.defaultMailDeliverySureDeletedText,
                          type: 'success',
                          // refresh onClose: zeroBSCRMJS_refreshPage
                          onClose: function(){

                            // remove other default labels + inject
                            jQuery('#zbs-mail-delivery-account-list-wrap td.zbs-mail-delivery-item-details .zbs-default').remove();
                            // undisable as well
                            jQuery('#zbs-mail-delivery-account-list-wrap .zbs-default-mail-delivery.disabled').removeClass('disabled');

                            llIndx = lIndx;
                            jQuery('#zbs-mail-delivery-' + llIndx + ' td.zbs-mail-delivery-item-details').prepend('<div class="ui ribbon label zbs-default">' + window.zeroBSCRMJS_lang.defaultText + '</div>');
                            jQuery('#zbs-mail-delivery-' + llIndx + ' .ui.button.zbs-default-mail-delivery').addClass('disabled');

                          }
                        });

                      },
                      error: function(response){

                        console.error('def',response);

                        swal(
                          window.zeroBSCRMJS_lang.defaultMailDeliverySureDeleteErrTitle,
                          window.zeroBSCRMJS_lang.defaultMailDeliverySureDeleteErrText,
                          'warning'
                        );

                      }

                    });



            
          });

      });

  }


  // bind wizard funcs
  function zeroBSCRMJS_mail_delivery_bindWizard(){

    // any of these?
    jQuery('.ui.radio.checkbox').checkbox();
    
    // start wiz
    jQuery('#zbs-mail-delivery-start-wizard').unbind('click').click(function(){

      // hide bits classed .zbs-non-wizard
      jQuery('.zbs-non-wizard',jQuery('#zbs-mail-delivery-wrap')).hide();

      // show wiz
      jQuery('#zbs-mail-delivery-wizard-wrap').show();


    });

    // step 1

      // submit
      jQuery('#zbs-mail-delivery-wizard-step-1-submit').unbind('click').click(function(){

          // test inputs & move on to step 2
          var okayToProceed = true;
          var sendFromName = jQuery('#zbs-mail-delivery-wizard-sendfromname').val();
          var sendFromEmail = jQuery('#zbs-mail-delivery-wizard-sendfromemail').val();

          // send from name
          if (sendFromName.length > 0){
            
            // set it
            window.zeroBSCRMJS_SMTPWiz.sendFromName = sendFromName;

            // hide any msg
            jQuery('#zbs-mail-delivery-wizard-sendfromname-error').html(window.zeroBSCRMJS_lang.thanks).addClass('hidden');

          } else {

            // not okay
            okayToProceed = false;

            // msg
            jQuery('#zbs-mail-delivery-wizard-sendfromname-error').html(window.zeroBSCRMJS_lang.pleaseEnter).removeClass('hidden');

          }

          // send from email
          if (sendFromEmail.length > 0 && zbscrm_JS_validateEmail(sendFromEmail)){
            
            // set it
            window.zeroBSCRMJS_SMTPWiz.sendFromEmail = sendFromEmail;

            // hide any msg
            jQuery('#zbs-mail-delivery-wizard-sendfromemail-error').html(window.zeroBSCRMJS_lang.thanks).addClass('hidden');

          } else {

            // not okay
            okayToProceed = false;

            // msg
            jQuery('#zbs-mail-delivery-wizard-sendfromemail-error').html(window.zeroBSCRMJS_lang.pleaseEnterEmail).removeClass('hidden');

          }

          // okay?
          if (okayToProceed){

            jQuery('#zbs-mail-delivery-wizard-step-1-wrap').hide();
            jQuery('#zbs-mail-delivery-wizard-step-2-wrap').show();

            jQuery('.zbs-top-step-1').removeClass('active');
            jQuery('.zbs-top-step-2').removeClass('disabled').addClass('active');

            // Pre-fill user on next step SMTP ...
            if (jQuery('#zbs-mail-delivery-wizard-step-2-smtp-user').val() == '') jQuery('#zbs-mail-delivery-wizard-step-2-smtp-user').val(sendFromEmail);

          }

      });



      // Step 2
      jQuery('#zbs-mail-delivery-wizard-step-2-wrap .ui.radio.checkbox').click(function(){

          // check mode
          var serverType = 'wp_mail'; 
          if (jQuery('#zbs-mail-delivery-wizard-step-2-servertype-smtp').checkbox('is checked')) serverType = 'smtp';

          // show hide
          if (serverType == 'smtp'){
            jQuery('#zbs-mail-delivery-wizard-step-2-smtp-wrap').show();
            jQuery('#zbs-mail-delivery-wizard-step-2-prefill-smtp').show();
          } else {
            jQuery('#zbs-mail-delivery-wizard-step-2-smtp-wrap').hide();
            jQuery('#zbs-mail-delivery-wizard-step-2-prefill-smtp').hide();
          }


      });

      // back button
      jQuery('#zbs-mail-delivery-wizard-step-2-back').unbind('click').click(function(){

            jQuery('#zbs-mail-delivery-wizard-step-1-wrap').show();
            jQuery('#zbs-mail-delivery-wizard-step-2-wrap').hide();

            jQuery('.zbs-top-step-1').removeClass('disabled').addClass('active');
            jQuery('.zbs-top-step-2').removeClass('active').addClass('disabled');

      });

      // quickfill smtp
      jQuery('#zbs-mail-delivery-wizard-step-2-prefill-smtp select').unbind('change').change(function(){

          // debug console.log(jQuery('#zbs-mail-delivery-wizard-step-2-prefill-smtp select').val());
          var v = jQuery('#zbs-mail-delivery-wizard-step-2-prefill-smtp select').val();

          // find deets
          jQuery('#zbs-mail-delivery-wizard-step-2-prefill-smtp select option').each(function(ind,ele){

              if (jQuery(ele).val() == v){

                // fill out + break
                jQuery('#zbs-mail-delivery-wizard-step-2-smtp-host').val(jQuery(ele).attr('data-host'));
                jQuery('#zbs-mail-delivery-wizard-step-2-smtp-port').val(jQuery(ele).attr('data-port'));
                jQuery('#zbs-mail-delivery-wizard-step-2-smtp-user').attr('placeholder',jQuery(ele).attr('data-example'));
                //data-host="email-smtp.us-east-1.amazonaws.com" data-auth="tls" data-port="587" data-example="AKGAIR8K9UBGAZY5UMLA"

                return true;

              }


          });


      });

      // check over deets
      jQuery('#zbs-mail-delivery-wizard-step-2-submit').unbind('click').click(function(){

          // test inputs & move on to step 2
          var okayToProceed = true;

          // wpmail or smtp?
          var serverType = 'wp_mail'; 
          if (jQuery('#zbs-mail-delivery-wizard-step-2-servertype-smtp').checkbox('is checked')) serverType = 'smtp';

          // smtp?
          if (serverType == "smtp"){

            var smtpHost = jQuery('#zbs-mail-delivery-wizard-step-2-smtp-host').val();
            var smtpPort = jQuery('#zbs-mail-delivery-wizard-step-2-smtp-port').val();
            var smtpUser = jQuery('#zbs-mail-delivery-wizard-step-2-smtp-user').val();
            var smtpPass = jQuery('#zbs-mail-delivery-wizard-step-2-smtp-pass').val();

            // first check lengths of them all

              if (smtpHost.length > 0){
                // set it
                window.zeroBSCRMJS_SMTPWiz.smtpHost = smtpHost;
                // hide any msg
                jQuery('#zbs-mail-delivery-wizard-smtphost-error').html(window.zeroBSCRMJS_lang.thanks).addClass('hidden');
              } else {
                // not okay
                okayToProceed = false;
                // msg
                jQuery('#zbs-mail-delivery-wizard-smtphost-error').html(window.zeroBSCRMJS_lang.pleaseEnter).removeClass('hidden');
              }

              if (smtpPort.length > 0){
                // set it
                window.zeroBSCRMJS_SMTPWiz.smtpPort = smtpPort;
                // hide any msg
                jQuery('#zbs-mail-delivery-wizard-smtpport-error').html(window.zeroBSCRMJS_lang.thanks).addClass('hidden');
              } else {
                // not okay
                okayToProceed = false;
                // msg
                jQuery('#zbs-mail-delivery-wizard-smtpport-error').html(window.zeroBSCRMJS_lang.pleaseEnter).removeClass('hidden');
              }

              if (smtpUser.length > 0){
                // set it
                window.zeroBSCRMJS_SMTPWiz.smtpUser = smtpUser;
                // hide any msg
                jQuery('#zbs-mail-delivery-wizard-smtpuser-error').html(window.zeroBSCRMJS_lang.thanks).addClass('hidden');
              } else {
                // not okay
                okayToProceed = false;
                // msg
                jQuery('#zbs-mail-delivery-wizard-smtpuser-error').html(window.zeroBSCRMJS_lang.pleaseEnter).removeClass('hidden');
              }

              if (smtpPass.length > 0){
                // set it
                window.zeroBSCRMJS_SMTPWiz.smtpPass = smtpPass;
                // hide any msg
                jQuery('#zbs-mail-delivery-wizard-smtppass-error').html(window.zeroBSCRMJS_lang.thanks).addClass('hidden');
              } else {
                // not okay
                okayToProceed = false;
                // msg
                jQuery('#zbs-mail-delivery-wizard-smtppass-error').html(window.zeroBSCRMJS_lang.pleaseEnter).removeClass('hidden');
              }





          } // end if smtp

          // wpmail
          if (serverType == 'wp_mail'){

            // no validation req.

          } // end if wpmail



            // okay?
            if (okayToProceed){

              jQuery('#zbs-mail-delivery-wizard-step-2-wrap').hide();
              jQuery('#zbs-mail-delivery-wizard-step-3-wrap').show();

              jQuery('.zbs-top-step-2').removeClass('active');
              jQuery('.zbs-top-step-3').removeClass('disabled').addClass('active');

              // start validator
              zeroBSCRMJS_validateSettings();

            }

      });

      // back button
      jQuery('#zbs-mail-delivery-wizard-step-3-back').unbind('click').click(function(){

            jQuery('#zbs-mail-delivery-wizard-step-2-wrap').show();
            jQuery('#zbs-mail-delivery-wizard-step-3-wrap').hide();

            jQuery('.zbs-top-step-2').removeClass('disabled').addClass('active');
            jQuery('.zbs-top-step-3').removeClass('active').addClass('disabled');

      });

      // fini button
      jQuery('#zbs-mail-delivery-wizard-step-3-submit').unbind('click').click(function(){

            window.location = window.zeroBSCRM_currentURL;

      });



  }

  // takes settings in window.zeroBSCRMJS_SMTPWiz and attempts to validate
  // (assumes present values)
  function zeroBSCRMJS_validateSettings(){

      /* window.zeroBSCRMJS_SMTPWiz    
        sendFromName: '',
        sendFromEmail: '',
        serverType: 'wp_mail',
        smtpHost: '',
        smtpPort: '',
        smtpUser: '',
        smtpPass: ''
      */


      var serverType = 'wp_mail'; 
      if (jQuery('#zbs-mail-delivery-wizard-step-2-servertype-smtp').checkbox('is checked')) serverType = 'smtp';

      // step through:
        //<i class="terminal icon"></i>
        //<i class="handshake icon"></i>
        //<i class="mail outline icon"></i>
        //<i class="open envelope outline icon"></i>

      // clear prev debug
      jQuery('#zbs-mail-delivery-wizard-admdebug').html('').hide();

      switch (serverType){

        case 'wp_mail':

            // easy - fire of a test via ajax, but will "work" in as far as validation

            // loading
            jQuery('#zbs-mail-delivery-wizard-validate-console-ico').addClass('loading');

            // postbag! - NOTE: This also adds a new Mail Delivery line to the options (or updates an old one with same email)
            var data = {
              'action': 'zbs_maildelivery_validation_wp_mail',
              'sendFromName': window.zeroBSCRMJS_SMTPWiz.sendFromName,
              'sendFromEmail': window.zeroBSCRMJS_SMTPWiz.sendFromEmail,
              'sec': window.zeroBSCRM_sToken
            };

            // Send it Pat :D
            jQuery.ajax({
              type: "POST",
              url: ajaxurl,
              "data": data,
              dataType: 'json',
              timeout: 20000,
              success: function(response) {

                console.log(response);

                  // remove loading
                  jQuery('#zbs-mail-delivery-wizard-validate-console-ico').removeClass('loading').html('<i class="open envelope outline icon"></i>');
                  jQuery('#zbs-mail-delivery-wizard-validate-console').html('');

                  // success?
                  if (typeof response.success != "undefined"){

                      // show result
                      var resHTML = window.zeroBSCRMJS_lang.settingsValidatedWPMail + '<div class="zbs-validated">' + window.zeroBSCRMJS_SMTPWiz.sendFromEmail + '</div>';
                      if (window.zeroBSCRMJS_SMTPWiz.smtpHost == 'smtp.gmail.com') resHTML += '<p>' + window.zeroBSCRMJS_lang.settingsValidateGMAIL + '</p>';
                      jQuery('#zbs-mail-delivery-wizard-validate-console').html(resHTML);

                      // enable finish button, remove back button
                      jQuery('#zbs-mail-delivery-wizard-step-3-back').hide();
                      jQuery('#zbs-mail-delivery-wizard-step-3-submit').show().removeClass('disabled');

                  } else {

                      // some kind of error, suggest retry
                      var resHTML = window.zeroBSCRMJS_lang.settingsValidatedWPMailError;
                      if (window.zeroBSCRMJS_SMTPWiz.smtpHost == 'smtp.gmail.com') resHTML += '<p>' + window.zeroBSCRMJS_lang.settingsValidateGMAIL + '</p>';
                      jQuery('#zbs-mail-delivery-wizard-validate-console').html(resHTML);
                      jQuery('#zbs-mail-delivery-wizard-validate-console-ico').html('<i class="warning sign icon"></i>');

                      // enable back button, disable finish button
                      jQuery('#zbs-mail-delivery-wizard-step-3-back').show();
                      jQuery('#zbs-mail-delivery-wizard-step-3-submit').addClass('disabled');

                  }

              },
              error: function(response){

                  // remove loading
                  jQuery('#zbs-mail-delivery-wizard-validate-console-ico').removeClass('loading');
                  jQuery('#zbs-mail-delivery-wizard-validate-console-ico').html('<i class="warning sign icon"></i>');

                  // some kind of error, suggest retry
                  var resHTML = window.zeroBSCRMJS_lang.settingsValidatedWPMailError;
                  if (window.zeroBSCRMJS_SMTPWiz.smtpHost == 'smtp.gmail.com') resHTML += '<p>' + window.zeroBSCRMJS_lang.settingsValidateGMAIL + '</p>';
                  jQuery('#zbs-mail-delivery-wizard-validate-console').html(resHTML);

                  // enable back button, disable finish button
                  jQuery('#zbs-mail-delivery-wizard-step-3-back').show();
                  jQuery('#zbs-mail-delivery-wizard-step-3-submit').addClass('disabled');

              }

            });


            break;

        case 'smtp':

            // less easy - fire of a test via ajax, return varied responses :)

            // loading
            jQuery('#zbs-mail-delivery-wizard-validate-console-ico').addClass('loading');
            jQuery('#zbs-mail-delivery-wizard-validate-console').html(window.zeroBSCRMJS_lang.settingsValidateSMTPPortCheck);


            // FIRST check ports open (step 1)
            var data = {
              'action': 'zbs_maildelivery_validation_smtp_ports',
              'smtpHost': window.zeroBSCRMJS_SMTPWiz.smtpHost,
              'smtpPort': window.zeroBSCRMJS_SMTPWiz.smtpPort,
              'sec': window.zeroBSCRM_sToken
            };

            // Send it Pat :D
            jQuery.ajax({
              type: "POST",
              url: ajaxurl,
              "data": data,
              dataType: 'json',
              timeout: 60000,
              success: function(response) {

                if (typeof response.open != "undefined" && response.open){

                  // NORMAL - validate smtp via send:
                  jQuery('#zbs-mail-delivery-wizard-validate-console').html(window.zeroBSCRMJS_lang.settingsValidateSMTPProbing);


                            // postbag! - NOTE: This also adds a new Mail Delivery line to the options (or updates an old one with same email)
                            var data = {
                              'action': 'zbs_maildelivery_validation_smtp',
                              'sendFromName': window.zeroBSCRMJS_SMTPWiz.sendFromName,
                              'sendFromEmail': window.zeroBSCRMJS_SMTPWiz.sendFromEmail,
                              'smtpHost': window.zeroBSCRMJS_SMTPWiz.smtpHost,
                              'smtpPort': window.zeroBSCRMJS_SMTPWiz.smtpPort,
                              'smtpUser': window.zeroBSCRMJS_SMTPWiz.smtpUser,
                              'smtpPass': window.zeroBSCRMJS_SMTPWiz.smtpPass,
                              'sec': window.zeroBSCRM_sToken
                            };

                            // Send it Pat :D
                            jQuery.ajax({
                              type: "POST",
                              url: ajaxurl,
                              "data": data,
                              dataType: 'json',
                              timeout: 60000,
                              success: function(response) {

                                // console.log('SMTP',response);

                                  // 2.94.2 we also added hidden output of all debugs (click to show)
                                  if (typeof response.debugs != "undefined"){

                                    var debugStr = '';
                                      if (response.debugs.length > 0) jQuery.each(response.debugs,function(ind,ele){

                                          debugStr += '<hr />' + ele;

                                      });
                                      jQuery('#zbs-mail-delivery-wizard-admdebug').html('<strong>Debug Log</strong>:<br />' + debugStr);
                                  }

                                  // remove loading + play routine for now (no seperate ajax tests here)
                                  jQuery('#zbs-mail-delivery-wizard-validate-console').html(window.zeroBSCRMJS_lang.settingsValidateSMTPProbing);
                                  jQuery('#zbs-mail-delivery-wizard-validate-console-ico').removeClass('loading').html('<i class="terminal icon"></i>');

                                  setTimeout(function(){

                                    // attempting to send msg
                                    jQuery('#zbs-mail-delivery-wizard-validate-console').html(window.zeroBSCRMJS_lang.settingsValidateSMTPAttempt);
                                    jQuery('#zbs-mail-delivery-wizard-validate-console-ico').html('<i class="terminal icon"></i>');


                                    // fly or die:


                                        // success?
                                        if (typeof response.success != "undefined" && response.success){

                                            // sent     
                                            var resHTML = window.zeroBSCRMJS_lang.settingsValidateSMTPSuccess;                 
                                            //if (window.zeroBSCRMJS_SMTPWiz.smtpHost == 'smtp.gmail.com') resHTML += window.zeroBSCRMJS_lang.settingsValidateGMAIL;
                                            jQuery('#zbs-mail-delivery-wizard-validate-console').html(resHTML);
                                            jQuery('#zbs-mail-delivery-wizard-validate-console-ico').html('<i class="mail outline icon"></i>');

                                            setTimeout(function(){

                                              //console.log('x',window.zeroBSCRMJS_SMTPWiz.smtpHost);
                                                // show result
                                                var resHTML = window.zeroBSCRMJS_lang.settingsValidatedSMTP + '<div class="zbs-validated">' + window.zeroBSCRMJS_SMTPWiz.sendFromEmail + '</div>';
                                                if (window.zeroBSCRMJS_SMTPWiz.smtpHost == 'smtp.gmail.com') resHTML += window.zeroBSCRMJS_lang.settingsValidateGMAIL;
                                                jQuery('#zbs-mail-delivery-wizard-validate-console').html(resHTML);
                                                jQuery('#zbs-mail-delivery-wizard-validate-console-ico').html('<i class="open envelope outline icon"></i>');

                                                // enable finish button, remove back button
                                                jQuery('#zbs-mail-delivery-wizard-step-3-back').hide();
                                                jQuery('#zbs-mail-delivery-wizard-step-3-submit').show().removeClass('disabled');

                                                setTimeout(function(){
                                                  // bind show debug
                                                  jQuery('#zbs-mail-delivery-showdebug').unbind('click').click(function(e){
                                                      jQuery('#zbs-mail-delivery-wizard-admdebug').toggle();
                                                      e.preventDefault();
                                                    });
                                                },0);

                                            },1000);


                                        } else {

                                            // some kind of error, suggest retry
                                            var resHTML = window.zeroBSCRMJS_lang.settingsValidatedSMTPProbeError;
                                            if (window.zeroBSCRMJS_SMTPWiz.smtpHost == 'smtp.gmail.com') resHTML += window.zeroBSCRMJS_lang.settingsValidateGMAIL;
                                            jQuery('#zbs-mail-delivery-wizard-validate-console').html(resHTML);
                                            jQuery('#zbs-mail-delivery-wizard-validate-console-ico').html('<i class="warning sign icon"></i>');

                                            // enable back button, disable finish button
                                            jQuery('#zbs-mail-delivery-wizard-step-3-back').show();
                                            jQuery('#zbs-mail-delivery-wizard-step-3-submit').addClass('disabled');

                                            // bind show debug
                                            jQuery('#zbs-mail-delivery-showdebug').unbind('click').click(function(){
                                              jQuery('#zbs-mail-delivery-wizard-admdebug').toggle();
                                            });


                                            setTimeout(function(){
                                              // bind show debug
                                              jQuery('#zbs-mail-delivery-showdebug').unbind('click').click(function(e){
                                                  jQuery('#zbs-mail-delivery-wizard-admdebug').toggle();
                                                  e.preventDefault();
                                                });
                                            },0);

                                        }


                                  },1000);


                                  setTimeout(function(){
                                    // bind show debug
                                    jQuery('#zbs-mail-delivery-showdebug').unbind('click').click(function(e){
                                        jQuery('#zbs-mail-delivery-wizard-admdebug').toggle();
                                        e.preventDefault();
                                      });
                                  },0);




                              },
                              error: function(response){

                                  // debug (likely timed out)
                                  jQuery('#zbs-mail-delivery-wizard-admdebug').html('<strong>Debug Log</strong>:<br />' + window.zeroBSCRMJS_lang.likelytimeout);

                                  // remove loading
                                  jQuery('#zbs-mail-delivery-wizard-validate-console-ico').removeClass('loading');
                                  jQuery('#zbs-mail-delivery-wizard-validate-console-ico').html('<i class="warning sign icon"></i>');

                                  // some kind of error, suggest retry
                                  var resHTML = window.zeroBSCRMJS_lang.settingsValidatedSMTPGeneralError;
                                  if (window.zeroBSCRMJS_SMTPWiz.smtpHost == 'smtp.gmail.com') resHTML += window.zeroBSCRMJS_lang.settingsValidateGMAIL;
                                  jQuery('#zbs-mail-delivery-wizard-validate-console').html(resHTML);

                                  // enable back button, disable finish button
                                  jQuery('#zbs-mail-delivery-wizard-step-3-back').show();
                                  jQuery('#zbs-mail-delivery-wizard-step-3-submit').addClass('disabled');

                                  setTimeout(function(){
                                    // bind show debug
                                    jQuery('#zbs-mail-delivery-showdebug').unbind('click').click(function(e){
                                        jQuery('#zbs-mail-delivery-wizard-admdebug').toggle();
                                        e.preventDefault();
                                      });
                                  },0);
                                  

                              }

                            });


                } // had open ports
                else {

                  // ports blocked

                  // 2.94.2 we also added hidden output of all debugs (click to show)
                  if (typeof response.debugs != "undefined"){

                        var debugStr = '';
                        if (response.debugs.length > 0) jQuery.each(response.debugs,function(ind,ele){

                            debugStr += '<hr />' + ele;

                        });
                        jQuery('#zbs-mail-delivery-wizard-admdebug').html('<strong>Debug Log (Ports Blocked)</strong>:<br />' + debugStr);


                        // remove loading
                        jQuery('#zbs-mail-delivery-wizard-validate-console-ico').removeClass('loading');
                        jQuery('#zbs-mail-delivery-wizard-validate-console-ico').html('<i class="warning sign icon"></i>');
                        
                        // some kind of error, suggest retry
                        var resHTML = window.zeroBSCRMJS_lang.likelytimeout;
                        if (window.zeroBSCRMJS_SMTPWiz.smtpHost == 'smtp.gmail.com') resHTML += window.zeroBSCRMJS_lang.settingsValidateGMAIL;
                        jQuery('#zbs-mail-delivery-wizard-validate-console').html(resHTML);

                        // enable back button, disable finish button
                        jQuery('#zbs-mail-delivery-wizard-step-3-back').show();
                        jQuery('#zbs-mail-delivery-wizard-step-3-submit').addClass('disabled');

                        setTimeout(function(){
                          // bind show debug
                          jQuery('#zbs-mail-delivery-showdebug').unbind('click').click(function(e){
                              jQuery('#zbs-mail-delivery-wizard-admdebug').toggle();
                              e.preventDefault();
                            });
                        },0);
                  }


                }



              },
              error: function(response){

                    // debug (likely timed out)
                    jQuery('#zbs-mail-delivery-wizard-admdebug').html('<strong>Debug Log (Ports Blocked)</strong>:<br />' + window.zeroBSCRMJS_lang.likelytimeout);

                    // remove loading
                    jQuery('#zbs-mail-delivery-wizard-validate-console-ico').removeClass('loading');
                    jQuery('#zbs-mail-delivery-wizard-validate-console-ico').html('<i class="warning sign icon"></i>');

                    // some kind of error, suggest retry
                    var resHTML = window.zeroBSCRMJS_lang.likelytimeout;
                    if (window.zeroBSCRMJS_SMTPWiz.smtpHost == 'smtp.gmail.com') resHTML += window.zeroBSCRMJS_lang.settingsValidateGMAIL;
                    jQuery('#zbs-mail-delivery-wizard-validate-console').html(resHTML);

                    // enable back button, disable finish button
                    jQuery('#zbs-mail-delivery-wizard-step-3-back').show();
                    jQuery('#zbs-mail-delivery-wizard-step-3-submit').addClass('disabled');

                    setTimeout(function(){
                      // bind show debug
                      jQuery('#zbs-mail-delivery-showdebug').unbind('click').click(function(e){
                          jQuery('#zbs-mail-delivery-wizard-admdebug').toggle();
                          e.preventDefault();
                        });
                    },0);
                    
                  

              }

            });


            

            break;

      } // / switch


  }


  </script><?php


  ?></div><?php # / wrap

}

function zeroBSCRM_html_license_settings(){

  global $wpdb, $zbs;  #} Req

  #} Act on any edits!
  if (isset($_POST['editwplflicense']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-license' );

    $licenseKeyArr = zeroBSCRM_getSetting('license_key');

    if (isset($_POST['wpzbscrm_license_key'])){

        $licenseKeyArr['key'] = sanitize_text_field( $_POST['wpzbscrm_license_key'] );

    }


    #} This brutally overrides existing!
    $zbs->settings->update('license_key',$licenseKeyArr);
    $sbupdated = true;

    #} Also, should also recheck the validity of the key and show message if not valid
    zeroBSCRM_license_check();

  }

  // reget
  $licenseKeyArr = zeroBSCRM_getSetting('license_key');

  // Debug echo 'islocal:'.zeroBSCRM_isLocal(true).'<br>';
  // Debug echo 'setting'.($licenseKeyArr['validity']).':<pre>'; print_r($licenseKeyArr); echo '</pre>';
  if (!zeroBSCRM_isLocal(true)){

      // check
      if (!is_array($licenseKeyArr) || !isset($licenseKeyArr['key']) || empty($licenseKeyArr['key'])){

          echo "<div class='ui message'><i class='ui icon info'></i>";
          $msg = __('Enter your License Key for updates and support.','zero-bs-crm');
          ##WLREMOVE
          $msg = __('Enter your License Key for updates and support. Please visit','zero-bs-crm')." <a href='". $zbs->urls['account'] ."' target='_blank'>".__('Your Account','zero-bs-crm').'</a> '.__(" for your key and CRM license management.","zero-bs-crm");
          ##/WLREMOVE
          echo $msg;
          echo "</div>";  

      } else {

          // simplify following:
          $licenseValid = false; if (isset($licenseKeyArr['validity'])) $licenseValid = ($licenseKeyArr['validity'] === 'true');

          if (!$licenseValid){
              echo "<div class='ui message red'><i class='ui icon info'></i>";
              $msg = __('Your License key is either invalid, expired, or not assigned to this site. Please contact support.','zero-bs-crm');

              ##WLREMOVE
              $msg = __('Your License key is either invalid, expired, or not assigned to this site. Please visit','zero-bs-crm')." <a href='". $zbs->urls['account'] ."' target='_blank'>".__('Your Account','zero-bs-crm').'</a> '.__('for your key and CRM license management.','zero-bs-crm');
              
              // add debug (from 2.98.1, to help us determine issues)
              $lastErrorMsg = ''; $err = $zbs->DAL->setting('licensingerror',false); if (is_array($err) && isset($err['err'])) $lastErrorMsg = $err['err'];
              if (!empty($lastErrorMsg)){
                $serverIP = zeroBSCRM_getServerIP();
                $msg .= '<br />'.__('If you believe you are seeing this in error, please ','zero-bs-crm')." <a href='". $zbs->urls['support'] ."' target='_blank'>".__('contact support','zero-bs-crm').'</a> '.__('and share the following debug output:','zero-bs-crm');
                $msg .= '<div style="margin:1em;padding:1em;">Server IP:<br />&nbsp;&nbsp;'.$serverIP;
                $msg .= '<br />Last Error:<br />&nbsp;&nbsp;'.$lastErrorMsg;
                $msg .= '</div>';
              }
              ##/WLREMOVE
              echo $msg;

              // got any errs? 
              // https://wordpress.stackexchange.com/questions/167898/is-it-safe-to-use-sslverify-true-for-with-wp-remote-get-wp-remote-post
              $hasHitError = $zbs->DAL->setting('licensingerror',false);

              if (is_array($hasHitError)){

                  $errorMsg = '<div style="font-size: 12px;padding: 1em;>['.date('F j, Y, g:i a',$hasHitError['time']).'] Reported Error: '.$hasHitError['err'].'</div>';

              }

              echo "</div>";
          } else {


              echo '<div class="ui grid">';
                echo '<div class="twelve wide column">';

                    echo "<div class='ui message green'><i class='ui icon check'></i>";
                    _e("Your License Key is valid for this site. Thank you.","zero-bs-crm");

                    // got updates?
                    if (isset($licenseKeyArr['extensions_updated']) && $licenseKeyArr['extensions_updated'] === 'false'){

                      echo ' '.__('You have extensions which need updating:','zero-bs-crm');
                      echo ' <a href="'.admin_url('update-core.php').'">'.__('Update now','zero-bs-crm').'</a>';

                    } 

                    echo "</div>"; 
                echo '</div>';

                // view license
                echo '<div class="four wide column" style="text-align:right;padding-top:1.5em;padding-right:2em"><span class="zbs-license-show-deets ui mini blue button" class="ui link"><i class="id card icon"></i> '.__('View License','zero-bs-crm').'</span></div>';
              echo '</div>'; // / grid


              // extra deets (hidden until "view License" clicked)
              echo '<div class="zbs-license-full-info ui segment grid" style="display:none">';
                echo '<div class="three wide column" style="text-align:center"><i class="id card icon" style="font-size: 3em;margin-top: 0.5em;"></i></div>';
                echo '<div class="thirteen wide column">';

                  // key
                  echo '<strong>'.__('License Key','zero-bs-crm').':</strong> ';
                  if (isset($licenseKeyArr['key']))
                    echo $licenseKeyArr['key'];
                  else
                    echo '-';
                  echo '<br />';

                  // sub deets
                  echo '<strong>'.__('Your Subscription','zero-bs-crm').':</strong> ';
                  if (isset($licenseKeyArr['access']))
                    echo $zbs->getSubscriptionLabel($licenseKeyArr['access']);
                  else
                    echo '-';
                  echo '<br />';

                  ##WLREMOVE

                    // next renewal
                    echo '<strong>'.__('Next Renewal','zero-bs-crm').':</strong> ';
                    if (isset($licenseKeyArr['expires']) && $licenseKeyArr['expires'] > 0)
                      echo zeroBSCRM_locale_utsToDate($licenseKeyArr['expires']);
                    else
                      echo '-';
                    echo '<br />';

                    // links
                    echo '<a href="'.$zbs->urls['licensinginfo'].'" target="_blank">'.__('Read about Yearly Subscriptions & Refunds','zero-bs-crm').'</a>';
                 
                  ##/WLREMOVE

                echo '</div>'; // / col


                ?><script type="text/javascript">

                  jQuery(document).ready(function(){

                      jQuery('.zbs-license-show-deets').click(function(){

                        jQuery('.zbs-license-full-info').show();
                        jQuery('.zbs-license-show-deets').hide();

                      });

                  });


                </script><?php

             echo '</div>'; // / grid

             echo '<div style="clear:both" class="ui divider"></div>';
          }

      }

    } // if not local

  ?>
    
        <?php if (isset($sbupdated)) if ($sbupdated) { 

            //echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>';
            echo zeroBSCRM_UI2_messageHTML('info','',__('Settings Updated',"zero-bs-crm"));

         } ?>
        <?php

        ##WLREMOVE
          // claimed api key? 
          global $zbsLicenseClaimed;
          if (isset($zbsLicenseClaimed)){

            echo zeroBSCRM_UI2_messageHTML('info',__('API Key Notice','zero-bs-crm'),__('Thank you for entering your API key. This key has been successfully associated with this install, if you would like to change which domain uses this API key, please visit ','zero-bs-crm').'<a href="'.$zbs->urls['account'].'" target="_blank">'.$zbs->urls['account'].'</a>');
          }
        ##/WLREMOVE


        // if on Local server, don't allow entry of license keys, because we will end up with a license key db full
        // + it's hard to license properly on local servers as peeps could have many the same
        // ... so for v1.0 at least, 'devmode' in effect
        if (zeroBSCRM_isLocal(true)){

            $guide = '';
            ##WLREMOVE
            $guide = '<br /><br /><a href="'.$zbs->urls['kbdevmode'].'" class="ui button primary" target="_blank">'.__('Read More','zero-bs-crm').'</a>';
            ##/WLREMOVE

            echo zeroBSCRM_UI2_messageHTML('info',__('Developer Mode','zero-bs-crm'),__('This install appears to be running on a local machine. For this reason your CRM is in Developer Mode. You cannot add a license key to developer mode, nor retrieve automatic-updates.','zero-bs-crm').$guide);

        } else {

          // normal page
              
          ?>
          <div id="sbA">
              <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=license" id="zbslicenseform">
                <input type="hidden" name="editwplflicense" id="editwplflicense" value="1" />
                <?php 
                // add nonce
                wp_nonce_field( 'zbs-update-settings-license');
                ?>


                    <table class="table table-bordered table-striped wtab">
                   
                       <thead>
                        
                            <tr>
                                <th colspan="2" class="wmid"><?php _e('License',"zero-bs-crm"); ?>:</th>
                            </tr>

                        </thead>
                        
                        <tbody id="zbscrm-addresses-license-key">

                        <tr>
                            <td class="wfieldname"><label for="wpzbscrm_license_key"><?php _e("License Key","zero-bs-crm"); ?>:</label><br /><?php _e('Enter your License Key.',"zero-bs-crm"); ?></td>
                            <td style="width:540px">
                              <input class='form-control' style="padding:10px;" name="wpzbscrm_license_key" id="wpzbscrm_license_key" class="form-control" type="text" value="<?php if (isset($licenseKeyArr['key']) && !empty($licenseKeyArr['key'])) echo $licenseKeyArr['key']; ?>" />
                            </td>
                          </tr>
        
                        </tbody>

                    </table>

                      <table class="table table-bordered table-striped wtab">
                        <tbody>

                            <tr>
                              <td colspan="2" class="wmid">
                                <button type="submit" class="ui button primary" id=""><?php _e('Save Settings',"zero-bs-crm"); ?></button>
                              </td>
                            </tr>

                          </tbody>
                      </table>

                </form>

                <script type="text/javascript">

                </script>
                
        </div><?php   

      } // normal page
}

#} List view settings
function zeroBSCRM_html_listview_settings(){

  global $wpdb, $zbs;  #} Req


  #} Act on any edits!
  if (zeroBSCRM_isZBSAdminOrAdmin() && isset($_POST['editwplflistview'])){

    // check nonce
    check_admin_referer( 'zbs-update-settings-listview' );

    #debug echo 'UPDATING: <PRE>'; print_r($_POST); echo '</PRE>';
    $existingSettings = $zbs->settings->get('quickfiltersettings');

    if (isset($_POST['wpzbscrm_notcontactedinx'])){

        $potentialNotContactedInX = (int)sanitize_text_field( $_POST['wpzbscrm_notcontactedinx'] );
        if ($potentialNotContactedInX > 0) $existingSettings['notcontactedinx'] = $potentialNotContactedInX;
    }

    if (isset($_POST['wpzbscrm_olderthanx'])){

        $potentialOlderThanX = (int)sanitize_text_field( $_POST['wpzbscrm_olderthanx'] );
        if ($potentialOlderThanX > 0) $existingSettings['olderthanx'] = $potentialOlderThanX;
    }

    #} This brutally overrides existing!
    $zbs->settings->update('quickfiltersettings',$existingSettings);

    // and this
    $allowinlineedits = -1; if (isset($_POST['wpzbscrm_allowinlineedits'])) $allowinlineedits = 1;
    $zbs->settings->update('allowinlineedits',$allowinlineedits);

    $sbupdated = true;

  }

  // reget
  $settings = $zbs->settings->get('quickfiltersettings');
  $allowinlineedits = $zbs->settings->get('allowinlineedits');

  ?>
    
        <p id="sbDesc"><?php _e('This page lets you set some generic List View settings. These affect pages like the',"zero-bs-crm"); ?> <a href="<?php echo zbsLink('create',-1,ZBS_TYPE_CONTACT); ?>"><?php _e('Contact List',"zero-bs-crm"); ?></a> view.</p>

        <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Settings Updated',"zero-bs-crm")); echo '</div>'; } ?>
        
        <div id="sbA">
            <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=listview" id="zbslistviewform">
              <input type="hidden" name="editwplflistview" id="editwplflistview" value="1" />
              <?php 
                // add nonce
                wp_nonce_field( 'zbs-update-settings-listview');
              ?>

                  <table class="table table-bordered table-striped wtab">
                 
                     <thead>
                      
                          <tr>
                              <th colspan="2" class="wmid"><?php _e('Quick Filters',"zero-bs-crm"); ?>:</th>
                          </tr>

                      </thead>
                      
                      <tbody id="zbscrm-addresses-custom-fields">

                      <tr>
                          <td class="wfieldname"><label for="wpzbscrm_notcontactedinx"><?php _e('"Not Contacted in X Days"',"zero-bs-crm"); ?>:</label><br /><?php _e('Enter the number of days to use in this filter.',"zero-bs-crm"); ?><br /><?php _e('E.g. Not contacted in 10 days',"zero-bs-crm"); ?></td>
                          <td style="width:540px">
                            <input style="width:100px;padding:10px;" name="wpzbscrm_notcontactedinx" id="wpzbscrm_notcontactedinx" class="form-control" type="text" value="<?php if (isset($settings['notcontactedinx']) && !empty($settings['notcontactedinx'])) echo $settings['notcontactedinx']; ?>" />
                          </td>
                        </tr>
                      <tr>
                          <td class="wfieldname"><label for="wpzbscrm_allowinlineedits"><?php _e('"Allow Inline Edits"',"zero-bs-crm"); ?>:</label><br /><?php _e('Allow Inline editing of list view fields',"zero-bs-crm"); ?></td>
                          <td style="width:540px">
                            <input type="checkbox" name="wpzbscrm_allowinlineedits" id="wpzbscrm_allowinlineedits" class="form-control" value="1"<?php if (isset($allowinlineedits) && $allowinlineedits == "1") echo ' checked="checked"'; ?> />
                          </td>
                        </tr>
                      <?php if ($zbs->DBVER >= 2){ ?>
                      <tr>
                          <td class="wfieldname"><label for="wpzbscrm_olderthanx"><?php _e('"Added more than X days ago"',"zero-bs-crm"); ?>:</label><br /><?php _e('Enter the number of days to use in this filter.',"zero-bs-crm"); ?><br /><?php _e('E.g. Contact older than 30 days',"zero-bs-crm"); ?></td>
                          <td style="width:540px">
                            <input style="width:100px;padding:10px;" name="wpzbscrm_olderthanx" id="wpzbscrm_olderthanx" class="form-control" type="text" value="<?php if (isset($settings['olderthanx']) && !empty($settings['olderthanx'])) echo $settings['olderthanx']; ?>" />
                          </td>
                        </tr>
                      <?php } ?>


      
                      </tbody>

                  </table>

                    <table class="table table-bordered table-striped wtab">
                      <tbody>

                          <tr>
                            <td colspan="2" class="wmid">
                              <button type="submit" class="ui button primary" id=""><?php _e('Save Settings',"zero-bs-crm"); ?></button>
                            </td>
                          </tr>

                        </tbody>
                    </table>

              </form>

              <script type="text/javascript">

              </script>
              
      </div><?php 
}

#} Field Sorts
function zeroBSCRM_html_fieldsorts(){

  global $wpdb, $zbs;  #} Req

  #$settings = $zbs->settings->getAll();

        $fieldTypes = array(

          'address' => array('name'=>'Address Fields','obj'=>'zbsAddressFields'),
          'customer' => array('name'=>'Contact Fields','obj'=>'zbsCustomerFields'),
          'company' => array('name'=>zeroBSCRM_getCompanyOrOrg().' Fields','obj'=>'zbsCompanyFields'),
          // following make no sense as we have custom editors for them :) v3.0 removed QUOTES, other 2 were'nt even in there yet
          //'quote' => array('name'=>'Quote Fields','obj'=>'zbsCustomerQuoteFields'),
          //'invoice' => array('name'=>'Invoice Fields','obj'=>'zbsInvoiceFields'),
          //'transaction' => array('name'=>'Transaction Fields','obj'=>'zbsTransactionFields'),

        );

  #} Act on any edits!
  if (isset($_POST['editwplfsort']) && zeroBSCRM_isZBSAdminOrAdmin()){

    // check nonce
    check_admin_referer( 'zbs-update-settings-fieldsorts' );

    #} localise
    global $zbsFieldSorts;

    #} Retrieve existing
    $newFieldOrderList = array();#$zbsFieldSorts;
    $newFieldHideList = array();

    #} Cycle through + Save custom field order
    foreach ($fieldTypes as $key => $fieldType){ 

      #} Retrieve from post
      $potentialCSV = ''; if (isset($_POST['zbscrm-'.$key.'-sortorder']) && !empty($_POST['zbscrm-'.$key.'-sortorder'])) $potentialCSV = sanitize_text_field($_POST['zbscrm-'.$key.'-sortorder']);

      #} TODO Compare with defaults (don't overridewise?)
      # use $zbsFieldSorts

      #} If not empty, break into array
      if (!empty($potentialCSV)){

        #$newArr = array();
        # brutal, lol
        $newArr = explode(',', $potentialCSV);

        #} add if any
        #} This adds to rolling arr
        #if (count($newArr) > 0) $newFieldOrderList[$key]['overrides'] = $newArr;
        #} ... but better to just add to save obj :)
        $newFieldOrderList[$key] = $newArr;

      }


      // for each fieldtype, also check for hidden fields (hacky temp workaround)

            #} Retrieve from post
            $potentialCSV = ''; if (isset($_POST['zbscrm-'.$key.'-hidelist']) && !empty($_POST['zbscrm-'.$key.'-hidelist'])) $potentialCSV = sanitize_text_field($_POST['zbscrm-'.$key.'-hidelist']);

            #} TODO Compare with defaults (don't overridewise?)
            # use $zbsFieldSorts

            #} If not empty, break into array
            if (!empty($potentialCSV)){

              #$newArr = array();
              # brutal, lol
              $newArr = explode(',', $potentialCSV);

              #} add if any
              #} This adds to rolling arr
              #if (count($newArr) > 0) $newFieldOrderList[$key]['overrides'] = $newArr;
              #} ... but better to just add to save obj :)
              $newFieldHideList[$key] = $newArr;

            }

            // / hidden fields


    }

    #debug echo 'UPDATING: <PRE>'; print_r($_POST); echo '</PRE>';

    #} This brutally overrides existing!
    $zbs->settings->update('fieldsorts',$newFieldOrderList);
    $zbs->settings->update('fieldhides',$newFieldHideList);
    $sbupdated = true;

    #$x = $zbs->settings->get('fieldsorts');
    #debug echo 'UPDATED: <PRE>'; print_r($x); echo '</PRE>';

    #} Then needs to "reget" fields :)
    zeroBSCRM_applyFieldSorts();

  }

  // Get field Hides...
  $fieldHideOverrides = $zbs->settings->get('fieldhides');

  ?>
    
        <p id="sbDesc"><?php _e('Using this page you can modify the order of the fields associated with Customers, Companies, Quotes',"zero-bs-crm"); ?></p>

        <?php if (isset($sbupdated)) if ($sbupdated) { echo '<div style="width:500px; margin-left:20px;" class="wmsgfullwidth">'; zeroBSCRM_html_msg(0,__('Field Orders Updated',"zero-bs-crm")); echo '</div>'; } ?>
        
        <div id="sbA">
            <form method="post" action="?page=<?php echo $zbs->slugs['settings']; ?>&tab=fieldsorts" id="zbsfieldsortform">
              <input type="hidden" name="editwplfsort" id="editwplfsort" value="1" />
                <?php 
                  // add nonce
                  wp_nonce_field( 'zbs-update-settings-fieldsorts');
                ?>
              <?php foreach ($fieldTypes as $key => $fieldType){ ?>



                   <table class="table table-bordered table-striped wtab">
                     
                         <thead>
                          
                              <tr>
                                  <th colspan="2" class="wmid"><?php _e($fieldType['name'],"zero-bs-crm"); ?>:</th>
                              </tr>

                          </thead>
                          
                          <tbody id="zbscrm-<?php echo $key; ?>-fieldsorts">

                            <tr>
                              <td colspan="2" style="text-align:right">

                                <div class="zbsSortableFieldList">

                                  <ul id="zbscrm-<?php echo $key; ?>-sort">

                                    <?php #} output fields 

                                      #} Weird this doesn't work for mike:
                                      #global $$fieldType['obj'];
                                      $fieldTypeObjVarName = $fieldType['obj'];
                                      global ${$fieldTypeObjVarName};# $$fieldTypeObjVarName; #} compat php7
                                      $fieldTypesArray = ${$fieldTypeObjVarName};

                                      #} This holds running list of migrated fields so only shows once
                                      $migratedFieldsOut = array();

                                      #} This holds a csv sort order output in input below
                                      $csvSortOrder = '';
                                      $csvHideList = '';

                                      if (count($fieldTypesArray) > 0) foreach ($fieldTypesArray as $subkey => $field){

                                        // remove address custom fields echo '<br>'.$subkey; print_r($field);
                                        if ($key != 'address' && (substr($subkey, 0,7) == 'addr_cf' || substr($subkey,0,10) == 'secaddr_cf')){

                                          // to ignore :)

                                        } else {

                                          // normal

                                          #} Those with a "migrate" attribute need to be switched for what they represent here
                                          #} (Addresses currently @ 1.1.19)

                                          if (isset($field['migrate']) && !empty($field['migrate'])){

                                            if (!in_array($field['migrate'],$migratedFieldsOut)){

                                              switch ($field['migrate']){

                                                #} Address Fields which were seperate fields under an obj are now managed as groups 
                                                case "addresses":

                                                  #} Grouped "Address" field out
                                                  ?><li data-key="addresses">Addresses</li><?php                                                

                                                  break;

                                              }


                                              #} add to csv
                                              if (!empty($csvSortOrder)) $csvSortOrder .= ',';
                                              $csvSortOrder .= $field['migrate'];

                                              #} And mark output
                                              $migratedFieldsOut[] = $field['migrate'];

                                            } // else just skip

                                          } else {

                                            #} Normal field out
                                            ?><li data-key="<?php echo $subkey; ?>"><?php echo $field[1]; 

                                            if (substr($subkey,0,2) == "cf") echo ' ('.__('Custom Field',"zero-bs-crm").')';


                                            // only bother with this if in these types:
                                            if (in_array($key,array('customer','company'))){

                                                  #} Show hide?
                                                  if (isset($field['essential']) && !empty($field['essential'])){

                                                      // these fields are always shown


                                                  } else {
                                                    
                                                    // can be hidden

                                                      // is hidden? 
                                                      $hidden = false; 
                                                      if (isset($fieldHideOverrides[$key]) && is_array($fieldHideOverrides[$key])){
                                                        if (in_array($subkey, $fieldHideOverrides[$key])){
                                                          $hidden = true;

                                                          #} add to csv
                                                          if (!empty($csvHideList)) $csvHideList .= ',';
                                                          $csvHideList .= $subkey;
                                                        }
                                                      }
                                                    ?><div class="zbs-showhide-field"><label for="zbsshowhide<?php echo $key.'-'.$subkey; ?>"><?php _e('Hide',"zero-bs-crm"); ?>:</label><input id="zbsshowhide<?php echo $key.'-'.$subkey; ?>" type="checkbox" value="1"<?php if ($hidden) echo ' checked="checked"'; ?> /><?php



                                                  }

                                            } // if hide/show option

                                          }

                                          ?></li><?php

                                          #} add to csv
                                          if (!empty($csvSortOrder)) $csvSortOrder .= ',';
                                          $csvSortOrder .= $subkey;

                                        } // if not addr custom field

                                      }

                                    ?>

                                  </ul>

                                </div>

                              </td>
                            </tr>
          
                          </tbody>

                      </table>
                      <input type="hidden" name="zbscrm-<?php echo $key; ?>-sortorder" id="zbscrm-<?php echo $key; ?>-sortorder" value="<?php echo $csvSortOrder; ?>" />
                      <input type="hidden" name="zbscrm-<?php echo $key; ?>-hidelist" id="zbscrm-<?php echo $key; ?>-hidelist" value="<?php echo $csvHideList; ?>" />

                  <?php } ?>

                    <table class="table table-bordered table-striped wtab">
                      <tbody>

                          <tr>
                            <td colspan="2" class="wmid">
                              <button type="button" class="ui button primary" id="zbsSaveFieldSorts"><?php _e('Save Field Sorts',"zero-bs-crm"); ?></button>
                            </td>
                          </tr>

                        </tbody>
                    </table>

              </form>

              <script type="text/javascript">
                var zbsSortableFieldTypes = [<?php $x = 1; foreach ($fieldTypes as $key => $fieldType){ if ($x > 1){ echo ","; } echo "'".$key."'"; $x++; } ?>];

                jQuery(document).ready(function(){


                jQuery( ".zbsSortableFieldList ul" ).sortable();
                jQuery( ".zbsSortableFieldList ul" ).disableSelection();

                // bind go button
                jQuery('#zbsSaveFieldSorts').click(function(){

                  // compile csv's
                  jQuery.each(window.zbsSortableFieldTypes,function(ind,ele){

                    var csvList = '';
                    var csvHideList = '';

                    // list into csv
                    jQuery('#zbscrm-' + ele + '-sort li').each(function(ind,ele){

                      if (csvList.length > 0) csvList += ',';

                      csvList += jQuery(ele).attr('data-key');

                      //DEBUG  console.log(ind + " " + jQuery(ele).attr('data-key'));


                      // show hides:

                        // if is present
                        if (jQuery('.zbs-showhide-field input[type=checkbox]',jQuery(ele))){

                            // if is checked
                            if (jQuery('.zbs-showhide-field input[type=checkbox]',jQuery(ele)).prop('checked')){

                                  // log hide
                                  if (csvHideList.length > 0) csvHideList += ',';

                                  csvHideList += jQuery(ele).attr('data-key');

                            }
                        }

                    });

                    // add to hidden input
                    jQuery('#zbscrm-' + ele + '-sortorder').val(csvList);
                    jQuery('#zbscrm-' + ele + '-hidelist').val(csvHideList);
                    //DEBUG  console.log("set " + '#zbscrm-' + ele + '-sortorder',csvList);


                  });


                  setTimeout(function(){
                    
                    // submit form
                    jQuery('#zbsfieldsortform').submit();

                  },0);

                })

                });

              </script>
              
      </div><?php 
}

/* ======================================================
   / Admin Pages
   ====================================================== */


/* ======================================================
  HTML Output Msg (alerts)
   ====================================================== */

   #} wrapper here for lib
  function whStyles_html_msg($flag,$msg,$includeExclaim=false){

    zeroBSCRM_html_msg($flag,$msg,$includeExclaim);

  }

  #} Outputs HTML message - 27th Feb 2019 - modified for Semantic UI (still had sgExclaim!)
  function zeroBSCRM_html_msg($flag,$msg,$includeExclaim=false){
    
      if ($includeExclaim){ $msg = '<div id="sgExclaim">!</div>'.$msg.''; }
      if ($flag == -1){
        echo '<div class="ui message alert danger">'.$msg.'</div>';
      } 
      if ($flag == 0){
        echo '<div class="ui message alert success">'.$msg.'</div>';  
      }
      if ($flag == 1){
        echo '<div class="ui message alert warning">'.$msg.'</div>'; 
      }
        if ($flag == 2){
            echo '<div class="ui message alert info">'.$msg.'</div>';
        }

      
  }

/* ======================================================
  / HTML Output Msg (alerts)
   ====================================================== */
