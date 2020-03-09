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

		// WH to MS: Have laid down basics of v3.0 integration here, easy to now add params properly.
		// v3.0 needs these objects refined, including textify for html like here in quotes, which breaks the json, for now we can simply not dump content:
		global $zbs;

			// make ARGS
			$args = array(				

				// Search/Filtering (leave as false to ignore)
				'searchPhrase' 	=> '',
				//'inArr'			=> $inArray,
				//'quickFilters'  => $quickFilters,
				//'isTagged'  	=> $hasTagIDs,

				//'withAssigned'	=> $withCustomerDeets,
				
				'suppressContent'	=> true, // NO HTML!

				'sortByField' 	=> 'ID',
				'sortOrder' 	=> 'DESC',
				'page'			=> 0,
				'perPage'		=> 20,

				'ignoreowner'		=> zeroBSCRM_DAL2_ignoreOwnership(ZBS_TYPE_QUOTE)


			);

		$quotes =  $zbs->DAL->quotes->getQuotes($args);
	
		echo json_encode($quotes);
		exit();

	}

	exit();

?>