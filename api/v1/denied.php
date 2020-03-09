<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.0
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 06/04/17
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */
   #} NOPE
   zeroBSCRM_API_AccessDenied(); 
   exit();

?>