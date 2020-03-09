<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V3.0
 *
 * Copyright 2019, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 04/06/2019
 */

	// V3.0 version of API

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

	if (!zeroBSCRM_API_is_zbs_api_authorised()){

		   #} NOPE
		   zeroBSCRM_API_AccessDenied(); 
		   exit();

	} else {

		#} Checks out, retrieve + return customers
		#} MIKE TODO - add paging/params for get count (max 50 at a time I think)
		// v3.0 needs these objects refined, including textify for html
		$transactions = zeroBS_getTransactions(true, 20);
		echo json_encode($transactions);
		exit();

	}

	exit();

?>