<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.98+
 *
 * Copyright 2019+ ZeroBSCRM.com
 *
 * Date: 17/04/2019
 */

// This file provides ample warning for users who may be updating to v3.0 
// ... should have been introduced way earlier than 2.98+, but better late than never
// notes: https://wisdomplugin.com/add-inline-plugin-update-message/

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

// add check
//add_action('admin_init', 'zeroBSCRM_updates_checkForV3');
// this makes it only fire when loading plugins.php :)
add_action('pre_current_active_plugins', 'zeroBSCRM_updates_checkForV3');

// Checks to see if we're upgrading from <3.0 to 3.0 + warns 
function zeroBSCRM_updates_checkForV3(){

    // only for users who can ;)
    if ( current_user_can( 'activate_plugins' ) && !get_transient( 'zbs-v3-pre-notice' ) ) {

        global $zbs;

        $update_data = zeroBSCRM_updates_pluginHasUpdate( 'Zero BS CRM', 'zero-bs-crm' );

        /* debug
        echo 'Update:<pre>'.print_r( $update_data , 1).'</pre>'; 
        echo 'comparing '.$zbs->version.' to 3.0 = '.version_compare($zbs->version,  "3.0").'!'; 
        exit(); */

        if( is_object($update_data) && isset($update_data->update) && is_object($update_data->update) && isset($update_data->update->new_version)) { // has update avail

            // is it v3, and current is pre v3?
            if (
                version_compare($update_data->update->new_version, "3.0") > -1
                && // current is pre 3.0:
                version_compare($zbs->version,  "3.0") < 0){

                // add admin notice - this doesn't work now as fired post-init, (pre_current_active_plugins)
                // ... so add a transient to show it :)
                //add_action( 'admin_notices', 'zeroBSCRM_updates_v3AvailAdminNotice');
                // show for 1 hour :) 
                set_transient( 'zbs-v3-pre-notice', true, (60 * 60 * 1));

                // show on plugin updates
                add_action('in_plugin_update_message-'.ZBS_ROOTPLUGIN, 'zeroBSCRM_updates_v3AvailUpdateNotice', 10, 2 );

            } // / is not v3+ from pre v3.0

        } // has update

    } // users who can

}

// show/hide admin notice :)
add_action( 'admin_notices', 'zeroBSCRM_updates_v3AvailAdminNoticeCatch' );


// Admin Notice on Activation.
function zeroBSCRM_updates_v3AvailAdminNoticeCatch(){

    /* Check transient, if available display notice */
    if ( current_user_can( 'activate_plugins' ) && get_transient( 'zbs-v3-pre-notice' ) ){
        
        global $zbs;

        // delete if is v3 already.
        if (version_compare($zbs->version,  "3.0") >= 0){

            // stop showing this.
            delete_transient( 'zbs-v3-pre-notice' );

        } else {
            
            // show
            zeroBSCRM_updates_v3AvailAdminNotice();
        
        }

    }

}

// admin notice
function zeroBSCRM_updates_v3AvailAdminNotice(){

    global $zbs;

    ?>
        <div id="updated" class="error notice is-dismissible">
            <p><?php 
            echo '<p><strong>'.__('Zero BS CRM','zero-bs-crm').' '.__('is ready to update to v3.0.','zero-bs-crm').'</strong></p><div style="margin:1em">'.__('This update requires a database upgrade which may take 5-10 minutes, please do not update until you are ready to migrate!','zero-bs-crm');
            echo '<br /><br /><a href="'.$zbs->urls['db3migrate'].'" target="_blank" class="button">'.__('Read about the v3.0 Migration','zero-bs-crm').'</a></div>'; 
            ?></p>
        </div>

    <?php

}

// Plugin updates page inline msg:
function zeroBSCRM_updates_v3AvailUpdateNotice( $data, $response ) {

    global $zbs;

    ?><hr /><div class="update-message notice inline notice-error"><?php 
            echo '<p><strong>'.__('Zero BS CRM','zero-bs-crm').' '.__('is ready to update to v3.0.','zero-bs-crm').'</strong></p><div style="margin:1em">'.__('This update requires a database upgrade which may take 5-10 minutes, please do not update until you are ready to migrate!','zero-bs-crm');
            echo '<br /><br /><a href="'.$zbs->urls['db3migrate'].'" target="_blank" class="button">'.__('Read about the v3.0 Migration','zero-bs-crm').'</a></div>';
    ?></div><hr /><?php
}

