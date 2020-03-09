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

		$json_params 		= file_get_contents("php://input");
		$company_params 	= json_decode($json_params,true);

		$perPage = 10; 			if (isset($company_params['perpage'])) $perPage 			= sanitize_text_field($company_params['perpage']);
		$page = 0; 				if (isset($company_params['page'])) $page 					= sanitize_text_field($company_params['page']);
		$withInvoices = -1; 	if (isset($company_params['invoices'])) $withInvoices 		= sanitize_text_field($company_params['invoices']);
		$withQuotes = -1; 		if (isset($company_params['quotes'])) $withQuotes			= sanitize_text_field($company_params['quotes']);
		$searchPhrase = ''; 	if (isset($company_params['search'])) $searchPhrase		= sanitize_text_field($company_params['search']);
		$withTransactions = -1; if (isset($company_params['transactions'])) $withTransactions	= sanitize_text_field($company_params['transactions']);
		$isOwned = -1; 			if (isset($company_params['owned'])) $isOwned 				= (int)$company_params['owned'];
	

		// #FORMIKENOTES - 
		// These should be Bools - see https://stackoverflow.com/questions/7336861/how-to-convert-string-to-boolean-php
		// ... this forces them from string of "true" or "false" into a bool
		$withInvoices = $withInvoices === 'true'? true: false;
		$withQuotes = $withQuotes === 'true'? true: false;
		$withTransactions = $withTransactions === 'true'? true: false;
		$isAssigned = false; // ??
	
		#} need to test this part, running out of timeeee
		$companies = zeroBS_getCompanies(true,$perPage,$page,$withInvoices,$withQuotes,$searchPhrase,$withTransactions,false,false, '',  '',false,false,false,'ID','DESC',false, $isAssigned);


		#} MIKE TODO - add paging/params for get count (max 50 at a time I think) - DONE ABOVE
		#  WOODY TODO - above needs moving to the $args version you mentioned (as added isAssigned) to DAL
		echo json_encode($companies);
		exit();

	}

	exit();

?>