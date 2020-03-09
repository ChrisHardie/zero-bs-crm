<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.70+
 *
 * Copyright 2018, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 23/04/2018
 */


/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */



// Check for custom admin styles
// e.g. Material Admin... and add support :)
function zeroBSCM_custom_admin_detect(){
	$zbs_custom_admin = 'none';
	if(function_exists('mtrl_panel_settings')){
		$zbs_custom_admin = 'material';
	}
	return $zbs_custom_admin;
}
