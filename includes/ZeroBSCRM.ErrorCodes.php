<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V3.0
 *
 * Copyright 2019, Zero BS Software Ltd.
 *
 * Date: 12/07/2019
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

   // WIP return of all error codes as array
   // hard-typed for v3.0 - note Do not change these lightly, programattically relied upon (at minimum for v2-3 migration)
   /*
	
		Initial guidance on format:
		// follow the example array below, add to if use-case crops up
		// key ranges:
	
		~100 = not used
		100-300 = js
		301-500 = php DAL
		501-700 = php general
		701-800 = Migrations

   */
   function zeroBSCRM_errorCodes(){

   		return array(

   			/* Example:

				key => array(
					
						// available options:
						'area'
						'objtype' (use * if all)
						'defaultmsg'

				)
	   			301 => array(
	   				'area' => 'dal'
	   				'objtype' => ZBS_TYPE_COMPANY,
   					'description' => 'unique_check_fail'
	   			)

   			*/


            // ================ PHP DAL
         
            301 => array(
               'area' => 'dal',
               'objtype' => '*',
               'description' => 'unique_check_fail'
            ),
            302 => array(
               'area' => 'dal',
               'objtype' => '*',
               'description' => 'update_fail'
            ),
            303 => array(
               'area' => 'dal',
               'objtype' => '*',
               'description' => 'insert_fail'
            ),
            304 => array(
               'area' => 'dal',
               'objtype' => '*',
               'description' => 'empty_not_allowed'
            ),
            305 => array(
               'area' => 'dal',
               'objtype' => '*',
               'description' => 'field_abbreviated'
            ),
            306 => array(
               'area' => 'dal',
               'objtype' => '*',
               'description' => 'failed_creating_tables'
            ),


            // ================ Migrations
         
            701 => array(
               'area' => 'migrations',
               'objtype' => '*',
               'description' => 'unavoidable_merge'
            ),
         
            702 => array(
               'area' => 'migrations',
               'objtype' => '*',
               'description' => 'invoice_total_discrepancy'
            ),
         
            703 => array(
               'area' => 'migrations',
               'objtype' => '*',
               'description' => 'open_fail'
            ), 
         
            704 => array(
               'area' => 'migrations',
               'objtype' => '*',
               'description' => 'migration_ajax_fail'
            ),
         
            710 => array(
               'area' => 'migrations',
               'objtype' => '*',
               'description' => 'extension_activation'
            ),
         
            711 => array(
               'area' => 'migrations',
               'objtype' => '*',
               'description' => 'close_fail'
            ),

   		);

   }