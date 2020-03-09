<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.72+
 *
 * Copyright 2018, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 24/08/2018
 */


/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */


 function zeroBSCRM_pages_admin_reminders(){
     ##WLREMOVE
    do_action('zbs_reminders_promo');
    ##/WLREMOVE
    do_action('zbs_reminders_pro');
 }

add_action('zbs_reminders_promo','zeroBSCRM_reminders_promo');
function zeroBSCRM_reminders_promo(){

    global $zbs; 
    
    echo "<div class='ui segment' style='margin-right:15px;font-size:18px;text-align:center;'>";
    
        echo "<h3 style='font-weight:900;'>CRM Reminders</h3>";

        echo "<p>";

            _e("CRM Reminders are part of the Calendar Pro extension. Set yourself and your team reminders to go along with your CRM Tasks","zero-bs-crm");

        echo "</p>";

        echo "<p>";
            echo "<a class='ui button large green' href='".$zbs->urls['extcal']."'>";
                _e("Find Out More", "zero-bs-crm"); 
            echo "</a>";

        echo "</p>";

    echo "</div>";
} 

 #} Mark as included :)
define('ZBSCRM_INC_REMINDERUI',true);

?>