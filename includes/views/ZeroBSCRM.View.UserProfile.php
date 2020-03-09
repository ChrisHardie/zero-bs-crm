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

 function zeroBSCRM_pages_admin_your_profile(){

    $cid = get_current_user_id();
    $user_info = wp_get_current_user();
    $fname = $user_info->user_firstname;
    $lname = $user_info->user_lastname;

    $ava_args = array(
        'class' => 'ui img rounded'
    );
    ?>
    <style>
        .top-header{
            text-align:center;
        }
        .top-header img{
            border-radius: 50%;
        }
    </style>
    <?php
    echo "<div class='ui segment zbscentral' style='margin-top:2em'>";
        echo "<div class='top-header'>";
        echo get_avatar( $cid, 120, '', '...', $ava_args );
        echo "<br/>";
        echo "<h3>" . $fname . " " . $lname . "</h3>";
        echo "</div>";

        echo "<div class='ui divider'></div>";

        do_action('zbs_your_profile_cal_pro_promo');
        do_action('zbs_your_profile');

    echo "</div>";


 }

 add_action('zbs_your_profile_cal_pro_promo', 'zeroBSCRM_pages_your_profile_promo');
 function zeroBSCRM_pages_your_profile_promo(){

    global $zbs;



 }


 #} Mark as included :)
define('ZBSCRM_INC_PROFILEUI',true);

?>