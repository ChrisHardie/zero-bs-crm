<?php
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.1.18
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 30/08/16
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */





/* ======================================================
	MIGRATION FUNCS
   ====================================================== */

global $zeroBSCRM_migrations; $zeroBSCRM_migrations = array(
	'1119','123','127',
	'216','22','240','241','242','250','2531',
	'270', // DAL 2.0
	'275',
	'280','281',
	'287','288',
	'2943', // 2.94.2 rebuild roles (added logs perms) + notice for mail delivery peeps (not using wp-mail)
	'295', // 2.94 - mikes alter of sys emails table + reset user roles (Added emails)
	'2952', // 2.95.2 - adds cron manager table silently (mc2 prep)
	'2962', // 2.96.2 - although set to 2953 as less so will run in v2.96.1 also
	'2963', // 2.96.3 - adds extra template for 'password reset email for cp'
	'2964', // 2.96.4 - FIX for missing 2.96.3 extra template for 'password reset email for cp'
	'2966',	// 2.96.6 - adds extra template for 'pdf statement'
	'2972', // 2.97.2 - adds db performance improvements for contacts retrieved via tag (including adding indexes)
	'2974', // 2.97.4 - fixes duplicated email templates (found on 2 installs so far)
	'2975', // 2.97.5 - (actually included in 2.97.4) corrects borked external sources setup.
	'2977', // 2.97.7 - Fixes an index to allow non-uniques (for user screen options)
	'2984', // 2.98.4 - Fixes segment conditions bug
	'2981',	// 2.98.1 - add in the invoice tax table
	'2999', // 2.99.0 - install tables for DAL3.0	
	'29999', // pre v3.0 - set to Flush permalinks 
	'3000', // 3.0 - Migrate all the THINGS
	'305', // 3.0.5 - catch instances where really old installs saved customer statuses as trans statuses gh-179
	'308' // 3.0.8 - Anyone with pdf module installed already, install pdf fonts for them
	);

global $zeroBSCRM_migrations_requirements; $zeroBSCRM_migrations_requirements = array(
		'270' => array('preload'),
		'288' => array('isDAL2','postsettings'),
		'3000' => array('preload','isDAL2')
	);


// mark's a migration complete
function zeroBSCRM_migrations_markComplete($migrationKey=-1,$logObj=false){

	global $zeroBSCRM_migrations;

	if (!empty($migrationKey) && in_array($migrationKey, $zeroBSCRM_migrations)) {

		$completedMigrations = zeroBSCRM_migrations_getCompleted();
		$completedMigrations[] = $migrationKey;

		// we're using wp options because they're reliable OUTSIDE of the scope of our settings model
		// ... which has changed through versions 
		// the separation here is key, at 2.88 WH discovered much re-running + pain due to this.
		// stick to a separate migration system (away from zbssettings)
	    update_option('zbsmigrations',$completedMigrations);

		// log opt?
	    update_option('zbsmigration'.$migrationKey,array('completed'=>time(),'meta'=>$logObj));

	}
}

// gets the list of completed migrations
function zeroBSCRM_migrations_getCompleted(){

	// we're using wp options because they're reliable OUTSIDE of the scope of our settings model
	// ... which has changed through versions 
	// the separation here is key, at 2.88 WH discovered much re-running + pain due to this.
	// stick to a separate migration system (away from zbssettings)

	// BUT WAIT! hilariously, for those who already have finished migrations, this'll re-run them
	// ... so here we 'MIGRATE' the migrations :o ffs
	global $zbs; $migrations = $zbs->settings->get('migrations'); if (isset($migrations) && is_array($migrations) && count($migrations) > 0) {
	
		$existingMigrationsMigration = get_option( 'zbsmigrationsdal', -1);

		if ($existingMigrationsMigration == -1){
			// copy over +
			// to stop this ever rerunning + confusing things, we set an option to say migrated the migrations, LOL
			update_option('zbsmigrations',$migrations);
			update_option('zbsmigrationsdal',2);
		}
	}

	// normal return
	return get_option( 'zbsmigrations', array() );

}

// gets details on a migration
function zeroBSCRM_migrations_geMigration($migrationKey=''){

	// we're using wp options because they're reliable OUTSIDE of the scope of our settings model
	// ... which has changed through versions 
	// the separation here is key, at 2.88 WH discovered much re-running + pain due to this.
	// stick to a separate migration system (away from zbssettings)
	$finished = false; $migrations = zeroBSCRM_migrations_getCompleted(); if (in_array($migrationKey,$migrations)) $finished = true;

	return array($finished,get_option('zbsmigration'.$migrationKey,false));

}

function zeroBSCRM_migrations_run($settingsArr=false){

	global $zeroBSCRM_migrations,$zeroBSCRM_migrations_requirements;

	    // catch migration block removal (can be run from system status):
	    if (current_user_can('admin_zerobs_manage_options') && isset($_GET['resetmigrationblock']) && wp_verify_nonce( $_GET['_wpnonce'], 'resetmigrationblock' ) ){

	        // unblock migration blocks
	        delete_option('zbsmigrationpreloadcatch');
	        delete_option('zbsmigrationblockerrors');

	        // flag
	        $migrationBlocksRemoved = true;
	    }

	#} Check if we've been stumped by blocking errs, and STOP migrating if so
	$blockingErrs = get_option( 'zbsmigrationblockerrors', false);
    if ($blockingErrs !== false && !empty($blockingErrs)) return false;

	#} load migrated list if not loaded
	$migratedAlreadyArr = zeroBSCRM_migrations_getCompleted();

	#} Run count
	$migrationRunCount = 0;

	#} cycle through any migrations + fire if not fired.
	if (count($zeroBSCRM_migrations) > 0) foreach ($zeroBSCRM_migrations as $migration){

		if (!in_array($migration,$migratedAlreadyArr) && function_exists('zeroBSCRM_migration_'.$migration)) {

			$run = true;

			// check reached state
			if (isset($zeroBSCRM_migrations_requirements[$migration])){


				// 'preload' requirement means this migration needs to run AFTER a reload AFTER the previous migration
				// ... so if preload here, we kill this loop, if prev migrations have run
				if (in_array('preload', $zeroBSCRM_migrations_requirements[$migration]) && $migrationRunCount > 0){

					// ... as a catch to stop infinite reloads, we check whether more than 3 of these have run in a row, and we stop that.
					$previousAttempts = get_option( 'zbsmigrationpreloadcatch', array());
					if (!is_array($previousAttempts)) $previousAttempts = array();
					if (!isset($previousAttempts[$migration])) $previousAttempts[$migration] = 1;
					if ($previousAttempts[$migration] < 5){

						// update count
						$previousAttempts[$migration]++;
						update_option('zbsmigrationpreloadcatch', $previousAttempts);

						// stop running migrations, reload the page
						header("Refresh:0");
						exit();

					} else {

						// set a global which'll show up on systemstatus if this state occurs.
						update_option('zbsmigrationblockerrors', $migration);					

						// expose an error that the world's about to rupture
					    add_action('after-zerobscrm-admin-init','zeroBSCRM_adminNotices_majorMigrationError');
			    		add_action( 'admin_notices', 'zeroBSCRM_adminNotices_majorMigrationError' );

					}

				}				

				// assume func
				foreach ($zeroBSCRM_migrations_requirements[$migration] as $check){

					// skip 'preload', dealt with above
					if ($check !== 'preload'){

						$checkFuncName = 'zeroBSCRM_migrations_checks_'.$check;
						if (!call_user_func($checkFuncName)) $run = false;

					}
				}
				

			}

			// go
			if ($run) {

				// run migration
				call_user_func('zeroBSCRM_migration_'.$migration);
				
				// update count
				$migrationRunCount++;

			}
		}

	}

}


function zeroBSCRM_migrations_checks_isDAL2(){

	global $zbs; return $zbs->isDAL2();

}

function zeroBSCRM_migrations_checks_postsettings(){

	global $zbs;
	/* didn't work:
	if (isset($zbs->settings) && method_exists($zbs->settings,'get')){
		$possiblyInstalled = $zbs->settings->get('settingsinstalled',true);
		if (isset($possiblyInstalled) && $possiblyInstalled > 0) return true;
	} */
	// HARD DB settings check
	try {
		$potentialDBSetting = $zbs->DAL->getSetting(array('key' => 'settingsinstalled','fullDetails' => false));	

		if (isset($potentialDBSetting) && $potentialDBSetting > 0) {

			return true;

		}

	} catch (Exception $e){

	}

	return false;
}

// general migration mechanism error
function zeroBSCRM_adminNotices_majorMigrationError(){

     //pop in a Notify Me Notification here instead....?
	 if (get_current_user_id() > 0){

	     // already sent?
	     $msgSent = get_transient('zbs-migration-general-errors');
	     if (!$msgSent){

	       zeroBSCRM_notifyme_insert_notification(get_current_user_id(), -999, -1, 'migration.blocked.errors','migration.blocked.errors');
	       set_transient( 'zbs-migration-general-errors', 20, 24 * 7 * HOUR_IN_SECONDS );

	    }

	}

}

/* ======================================================
	/ MIGRATION FUNCS
   ====================================================== */



/* ======================================================
	MIGRATIONS
   ====================================================== */

	function zeroBSCRM_migration_1119(){

		#} Glob
		global $zbs; #req

		$cosUpdated = 0;

		#} This function migrates users from before ver 1.1.19
		#$versionDifference = version_compare($zbs->version, "1.1.19");
		# no point in this! for now, just run once if on 1.1.19

		#} Retrieve if run or not
		# Moved into config model, see zeroBSCRM_migrations_run
		#$existingRun = get_option('zbsmigration1119');

		#} Will be boolean pre run, array after :)
		#if (gettype($existingRun) == "boolean" && $zbs->version == "1.1.19"){
		if ($zbs->version == "1.1.19"){

			#} Run update for all titles of co's
			#} ... for use with new integration funcs

			#} roughly get all (we're early, doubt anyone has 10k)
			$allCompanies = zeroBS_getCompanies(true,10000); $cosUpdated = 0;
			if (count($allCompanies) > 0) foreach ($allCompanies as $co){

		        #} Add zbs_company_nameperm meta (req. for importer) zeroBS_getCompanyIDWithName etc.
		        #} This sets this meta to the exact name, e.g. "Dell"
		        $simpleCName = zeroBS_companyName('',$co['meta'],false,false);
		        update_post_meta($co['id'],'zbs_company_nameperm',$simpleCName);

		        #} count
		        $cosUpdated++;


		    }

		}

	    /* moved to dal #} Mark migrated - add to settings
	    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '1119';
	    $zbs->settings->update('migrations',$migrationsExisting);
	    #} Also add a log
	    update_option('zbsmigration1119',array('completed'=>time(),'meta'=>array('updated'=>$cosUpdated)));
	    */
	    zeroBSCRM_migrations_markComplete('1119',array('updated'=>$cosUpdated));

	    #} Add admin notice
	    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_1119' );
			



	}
	function zeroBSCRM_migration_notice_1119() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php 
	        //_e( 'Zero BS CRM has completed a necessary migration to 1.1.19, Great!', 'zero-bs-crm' ); 
	        $ver = '1.1.19'; printf( esc_html__( 'Zero BS CRM has completed a necessary migration to version %s. Thanks for using ZBS.', 'zero-bs-crm' ), $ver );
	        ?></p>
	    </div>
	    <?php
	} 


	function zeroBSCRM_migration_123(){

		#} Glob
		global $zbs; #req

		#} This function migrates users from before ver 1.2.3
		#$versionDifference = version_compare($zbs->version, "1.2.3");
		# no point in this! for now, just run once if on 1.2.3

		#} Retrieve if run or not
		# Moved into config model, see zeroBSCRM_migrations_run
		#$existingRun = get_option('zbsmigration1119');

		#} Will be boolean pre run, array after :)
		#if (gettype($existingRun) == "boolean" && $zbs->version == "1.1.19"){
		
		# in fact, run this whatever version... e.g. if jumps from 1.1.19 to 1.2.4, still needs...
		#if ($zbs->version == "1.2.3"){

			#} Copies all existing quote + inv numbers to new proper field

			#} Quotes
			$allQuotes = zeroBS_getQuotes(false,50000); 
			$quoteOffset = zeroBSCRM_getQuoteOffset();
			$quotesUpdated = 0; $maxQuoteNo = 0;
			if (count($allQuotes) > 0) foreach ($allQuotes as $quote){

				# get existing?
				# won't have if migration run only once? - do anyway #get_post_meta($quote['id'])
				if (!isset($quote['zbsid']) || empty($quote['zbsid']) || $quote['zbsid'] == 0){

					# get logged no (or generated)
					$newQuoteID = $quoteOffset+(int)$quote['id'];        			

					# set it
					update_post_meta($quote['id'],'zbsid',$newQuoteID);

					$quotesUpdated++;

					if ($newQuoteID > $maxQuoteNo) $maxQuoteNo = $newQuoteID;

				}

			}

			#} set new max!
			if ($maxQuoteNo > 0) zeroBSCRM_setMaxQuoteID($maxQuoteNo);

			#} Invoices
			$allInvoices = zeroBS_getInvoices(true,50000); 
			$invoiceOffset = zeroBSCRM_getInvoiceOffset();
			$invsUpdated = 0; $maxInvoiceNo = 0;
			if (count($allInvoices) > 0) foreach ($allInvoices as $invoice){

				# get existing?
				# won't have if migration run only once? - do anyway #get_post_meta($quote['id'])
				if (!isset($invoice['zbsid']) || empty($invoice['zbsid']) || $invoice['zbsid'] == 0){

					# get logged no (or generated)
					$newInvoiceID = $invoiceOffset+(int)$invoice['id'];

					#} if meta['no'] is set, that overrides
					if (isset($invoice['meta']) && isset($invoice['meta']['no']) && !empty($invoice['meta']['no'])) $newInvoiceID = (int)$invoice['meta']['no'];

					# set it
					update_post_meta($invoice['id'],'zbsid',$newInvoiceID);
					$invsUpdated++;

					if ($newInvoiceID > $maxInvoiceNo) $maxInvoiceNo = $newInvoiceID;

				}

			}

			#} set new max!
			if ($maxInvoiceNo > 0) zeroBSCRM_setMaxInvoiceID($maxInvoiceNo);


	    	/* moved to dal 
		    #} Mark migrated - add to settings
		    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '123';
		    $zbs->settings->update('migrations',$migrationsExisting);
		    #} Also add a log
		    update_option('zbsmigration123',array('completed'=>time(),'meta'=>array('updated'=>'['.$quotesUpdated.','.$invsUpdated.','.$maxQuoteNo.','.$maxInvoiceNo.']')));
			*/
	    	zeroBSCRM_migrations_markComplete('123',array('updated'=>'['.$quotesUpdated.','.$invsUpdated.','.$maxQuoteNo.','.$maxInvoiceNo.']'));


		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_123' );
			

		#}


	}
	function zeroBSCRM_migration_notice_123() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php 
	        //_e( 'Zero BS CRM has completed a necessary migration to 1.2.3, Great!', 'zero-bs-crm' ); 
	        $ver = '1.2.3'; printf( esc_html__( 'Zero BS CRM has completed a necessary migration to version %s. Thanks for using ZBS.', 'zero-bs-crm' ), $ver );
	        ?></p>
	    </div>
	    <?php
	} 


	/*

		Migration of language overrides for 1.2.7

	*/
	function zeroBSCRM_migration_127(){

		#} Glob
		global $zbs, $zeroBSCRM_Conf_Setup; #req

		#} This function migrates users from before ver 1.2.7

			#} Removes the initial (taxi booker, lol) language entries and copies in existing
			$freshLanguageArr = array(); if (isset($zeroBSCRM_Conf_Setup['conf_defaults']['whlang']) && is_array($zeroBSCRM_Conf_Setup['conf_defaults']['whlang'])) $freshLanguageArr = $zeroBSCRM_Conf_Setup['conf_defaults']['whlang'];
			$zbs->settings->update('whlang',$freshLanguageArr);

		    /* moved to dal #} Mark migrated - add to settings
		    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '127';
		    $zbs->settings->update('migrations',$migrationsExisting);
		    #} Also add a log
		    update_option('zbsmigration127',array('completed'=>time(),'meta'=>array('updated'=>'1')));
		    */
	    	zeroBSCRM_migrations_markComplete('127',array('updated'=>1));


		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_127' );
			

		#}


	}
	function zeroBSCRM_migration_notice_127() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php 
	        //_e( 'Zero BS CRM has completed a necessary migration to 1.2.7, Great!', 'zero-bs-crm' ); 
	        $ver = '1.2.7'; printf( esc_html__( 'Zero BS CRM has completed a necessary migration to version %s. Thanks for using ZBS.', 'zero-bs-crm' ), $ver );?></p>
	    </div>
	    <?php
	} 


	/*

		Migration to rebuild company tities where they're borked (from some recent bug)

	*/
	function zeroBSCRM_migration_216(){

		#} Glob
		global $zbs, $zeroBSCRM_Conf_Setup; #req


			// =========== ACTUAL MIGRATION CODE

			#} roughly get all (we're early, doubt anyone has 50k)
			$allCompanies = zeroBS_getCompanies(true,50000); $cosUpdated = 0;
			if (count($allCompanies) > 0) foreach ($allCompanies as $co){

				#} Brutal rename
		        zbsCustomer_updateCompanyNameInPostTitle($co['id'],false);

		        #} count
		        $cosUpdated++;


		    }

		    // =========== ACTUAL MIGRATION CODE

		    /* moved to dal
		    #} Mark migrated - add to settings
		    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '216';
		    $zbs->settings->update('migrations',$migrationsExisting);
		    #} Also add a log
		    update_option('zbsmigration216',array('completed'=>time(),'meta'=>array('updated'=>$cosUpdated)));
		    */
	    	zeroBSCRM_migrations_markComplete('216',array('updated'=>$cosUpdated));

		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_216' );
			

		#}


	}
	function zeroBSCRM_migration_notice_216() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php 
	        //_e( 'Zero BS CRM has completed a necessary migration to 2.16, Thanks for using ZBS!', 'zero-bs-crm' ); 
	        $ver = '2.16'; printf( esc_html__( 'Zero BS CRM has completed a necessary migration to version %s. Thanks for using ZBS.', 'zero-bs-crm' ), $ver );
	        ?></p>
	    </div>
	    <?php
	} 


	/*

		Migration of db elements 2.2:
		- mail campaigns: Segments etc - make tables :)

	*/
	function zeroBSCRM_migration_22(){

		#} Glob
		global $zbs, $zeroBSCRM_Conf_Setup; #req

		#} This function migrates users from before ver 2.5


		  #} Check + create
		  zeroBSCRM_checkTablesExist();
		 
		    /* moved to dal
		    #} Mark migrated - add to settings
		    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '22';
		    $zbs->settings->update('migrations',$migrationsExisting);
		    #} Also add a log
		    update_option('zbsmigration22',array('completed'=>time(),'meta'=>array('updated'=>'1')));
			*/
	    	zeroBSCRM_migrations_markComplete('22',array('updated'=>1));

		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_22' );
			


	}
	function zeroBSCRM_migration_notice_22() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php //_e( 'Zero BS CRM has completed a necessary migration to 2.20, Great!', 'zero-bs-crm' ); 
	        $ver = '2.20'; printf( esc_html__( 'Zero BS CRM has completed a necessary migration to version %s. Thanks for using ZBS.', 'zero-bs-crm' ), $ver );
	        ?></p>
	    </div>
	    <?php
	} 
	/*

		Migration to rebuild customer titles inc post_exceprt for sort :)

	*/
	function zeroBSCRM_migration_227(){

		#} Glob
		global $zeroBSCRM_version,$zeroBSCRM_Settings,$zeroBSCRM_Conf_Setup; #req


			// =========== ACTUAL MIGRATION CODE

			#} roughly get all (we're early, doubt anyone has 500k)
			$allCustomers = zeroBS_getCustomers(false,500000); $custsUpdated = 0;
			if (count($allCustomers) > 0) foreach ($allCustomers as $cust){

				#} Brutal rename
		        zbsCustomer_updateCustomerNameInPostTitle($cust['id'],false);

		        #} count
		        $custsUpdated++;


		    }

		    // =========== ACTUAL MIGRATION CODE

		    /* moved to dal
		    #} Mark migrated - add to settings
		    $migrationsExisting = $zeroBSCRM_Settings->get('migrations'); $migrationsExisting[] = '227';
		    $zeroBSCRM_Settings->update('migrations',$migrationsExisting);
		    #} Also add a log
		    update_option('zbsmigration227',array('completed'=>time(),'meta'=>array('updated'=>$custsUpdated)));
			*/
	    	zeroBSCRM_migrations_markComplete('227',array('updated'=>$custsUpdated));


		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_227' );
			

		#}


	}
	function zeroBSCRM_migration_notice_227() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php 
	        //_e( 'Zero BS CRM has completed a necessary migration to 2.27, Thanks for using ZBS!', 'zero-bs-crm' ); 
	        $ver = '2.27'; printf( esc_html__( 'Zero BS CRM has completed a necessary migration to version %s. Thanks for using ZBS.', 'zero-bs-crm' ), $ver );
	        ?></p>
	    </div>
	    <?php
	} 

	/*

		Migration 2.4 - Refresh user roles

	*/
	function zeroBSCRM_migration_240(){

		#} Glob
		global $zbs, $zeroBSCRM_Conf_Setup; #req

		#} This function migrates users from before ver 2.4

		  #} re-add/remove any roles :)

			    // roles
				zeroBSCRM_clearUserRoles();

				// roles + 
				zeroBSCRM_addUserRoles();

		    /* moved to dal
		    #} Mark migrated - add to settings
		    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '240';
		    $zbs->settings->update('migrations',$migrationsExisting);
		    #} Also add a log
		    update_option('zbsmigration240',array('completed'=>time(),'meta'=>array('updated'=>'1')));
			*/
	    	zeroBSCRM_migrations_markComplete('240',array('updated'=>1));

		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_240' );
			


	}
	function zeroBSCRM_migration_notice_240() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php _e( 'Zero BS CRM has completed a necessary migration', 'zero-bs-crm' ); ?>  2.40, <?php _e('Great!',"zero-bs-crm"); ?></p>
	    </div>
	    <?php
	} 

	/*

		Migration 2.4 - Migrate external sources

	*/
	function zeroBSCRM_migration_241(){

		#} Glob
		global $zbs, $zeroBSCRM_Conf_Setup; #req

		#} This function migrates users from before ver 2.4


		    #} Migrate external sources from old way to new way...

	              // load all external source records,
	              // cycle through them + migrate them to new style,
	              // FOR NOW leave the old ones in-tact
	              global $wpdb;

	              $contacts = array(); $metavals = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta  WHERE meta_key LIKE 'zbs_customer_ext_%'", ARRAY_A);
	              
	              foreach ($metavals as $meta){

	                  /* print_r($meta);
	                  Array
	                    (
	                        [meta_id] => 572
	                        [post_id] => 225
	                        [meta_key] => zbs_customer_ext_api
	                        [meta_value] => aaron.aaberg.1516367251@stormgate.co.uk
	                    ) */

	                  // take api from zbs_customer_ext_api
	                  $thisExtSourceKey = substr($meta['meta_key'],17);

	                  // check in DB that a new record hasn't been already created for this customer, if has, use that
	                  if (!isset($contacts[$meta['post_id']])) {
	                    $existing = zeroBS_getExternalSource($meta['post_id'],1);
	                    if (is_array($existing)) $contacts[$meta['post_id']] = $existing;
	                  }

	                  // if contacts first ext source, create arr
	                  if (!isset($contacts[$meta['post_id']])) 
	                      $contacts[$meta['post_id']] = array(

	                            'source' => $thisExtSourceKey,
	                            'uid' => $meta['meta_value'],
	                            /* 2.52+ no need for this 
	                            'secondarysources' => array(),
	                            'tracking' => array(
	                              // empty for now
	                            ),
	                            'meta' => array(
	                              
	                              // this'll let us check if is a migrated val later, if we ever need to
	                              'migrated' => time()
	                          
	                            ) */
	                      );
	                  else {

	                    // add to secondary external sources
	                    // 2.52, no secondary sources this way, see DAL :) if (isset($contacts[$meta['post_id']]['secondarysources']) && !array_key_exists($thisExtSourceKey, $contacts[$meta['post_id']]['secondarysources'])) $contacts[$meta['post_id']]['seecondarysources'][$thisExtSourceKey] = $meta['meta_value'];

	                  }



	              }


	              if (count($contacts) > 0) {

	                  $migrated = 0;

	                  foreach ($contacts as $cID => $c){

	                    // add to new sys - type = 1 = contact
	                    zeroBS_updateExternalSource(1,$cID,$c);

	                    // assume success for now... (not deleting old method so should be okay)

	                  }
	              }

		    /* moved to dal
		    #} Mark migrated - add to settings
		    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '241';
		    $zbs->settings->update('migrations',$migrationsExisting);
		    #} Also add a log
		    update_option('zbsmigration241',array('completed'=>time(),'meta'=>array('updated'=>'1')));
			*/
	    	zeroBSCRM_migrations_markComplete('241',array('updated'=>1));

		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_241' );
			


	}
	function zeroBSCRM_migration_notice_241() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php _e( 'Zero BS CRM has completed a necessary migration', 'zero-bs-crm' ); ?>  2.41, <?php _e('Great!',"zero-bs-crm"); ?></p>
	    </div>
	    <?php
	} 

	/*

		Migration 2.4 - PDFDOM forced update

	*/
	function zeroBSCRM_migration_242(){

		#} Glob
		global $zbs, $zeroBSCRM_Conf_Setup; #req

		#} This function migrates users from before ver 2.4

		/* 3.0.11 this is removed, as now by default we ship zbs with dompdf

			// hard kill old dompdf:
			zeroBSCRM_extension_remove_dl_repo('dompdf');

			// install new: 
			zeroBSCRM_extension_checkinstall_pdfinv();
		*/
			
	    	zeroBSCRM_migrations_markComplete('242',array('updated'=>1));

		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_242' );
			


	}
	function zeroBSCRM_migration_notice_242() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php _e( 'Zero BS CRM has completed a necessary migration', 'zero-bs-crm' ); ?>  2.42, <?php _e('Great!',"zero-bs-crm"); ?></p>
	    </div>
	    <?php
	} 

	/*

		Migration 2.5 - Refresh user roles (again)

	*/
	function zeroBSCRM_migration_250(){

		#} Glob
		global $zbs, $zeroBSCRM_Conf_Setup; #req

		#} This function migrates users from before ver 2.4

		  #} re-add/remove any roles :)

			    // roles
				zeroBSCRM_clearUserRoles();

				// roles + 
				zeroBSCRM_addUserRoles();

		    /* moved to dal
		    #} Mark migrated - add to settings
		    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '250';
		    $zbs->settings->update('migrations',$migrationsExisting);
		    #} Also add a log
		    update_option('zbsmigration250',array('completed'=>time(),'meta'=>array('updated'=>'1')));

			*/
	    	zeroBSCRM_migrations_markComplete('250',array('updated'=>1));

		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_250' );
			


	}
	function zeroBSCRM_migration_notice_250() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php _e( 'Zero BS CRM has completed a necessary migration', 'zero-bs-crm' ); ?>  2.50, <?php _e('Great!',"zero-bs-crm"); ?></p>

	    </div>
	    <?php
	} 
	/*

		Migration 2.53.1 - fix for faulty transaction status setting

	*/
	function zeroBSCRM_migration_2531(){

		#} Glob
		global $zbs; #req

		#} This function clears setting: $settings['customisedfields']['transactions']['status'] 
		#} ... this isn't ideal, but is quick fix 
      	$settings = $zbs->settings->getAll();

      	if (isset($settings['customisedfields']) && isset($settings['customisedfields']['transactions']) && isset($settings['customisedfields']['transactions']['status'])){

      		// check if seems like any customer statuses?
      		// ... na just empty for now, which'll force back to a useful default
      		unset($settings['customisedfields']['transactions']['status']);
      		$zbs->settings->update('customisedfields',$settings['customisedfields']);

      		// and delete this (set back to 'all')
      		$zbs->settings->update('transinclude_status','all');

      	}
		

		    /* moved to dal
		    #} Mark migrated - add to settings
		    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '2531';
		    $zbs->settings->update('migrations',$migrationsExisting);
		    #} Also add a log
		    update_option('zbsmigration2531',array('completed'=>time(),'meta'=>array('updated'=>'1')));
			*/
	    	zeroBSCRM_migrations_markComplete('2531',array('updated'=>1));

		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_2531' );
			


	}
	function zeroBSCRM_migration_notice_2531() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php _e( 'Zero BS CRM has completed a necessary migration', 'zero-bs-crm' ); ?>  2.53.1, <?php _e('Great!',"zero-bs-crm"); ?></p>
	    </div>
	    <?php
	} 




	

	function zeroBSCRM_migration_280(){

		#} Glob
		global $zbs; #req

		#} Check + create
		zeroBSCRM_checkTablesExist();

		#} Make the DB emails...
		zeroBSCRM_populateEmailTemplateList();

		// check presence
		$mcTemplateCount = zeroBSCRM_mailTemplate_count();

		if ($mcTemplateCount > 0){	

			// success

		    /* moved to dal
		    #} Mark migrated - add to settings
		    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '280';
		    $zbs->settings->update('migrations',$migrationsExisting);
		    #} Also add a log
		    update_option('zbsmigration280',array('completed'=>time(),'meta'=>array('updated'=>'1')));
			*/
	    	zeroBSCRM_migrations_markComplete('280',array('updated'=>1));

		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_280' );

		} else {

			// failed?


			    /* moved to dal
				#} Mark migrated anyway, as they can do manually - add to settings
			    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '280';
			    $zbs->settings->update('migrations',$migrationsExisting);
			    #} Also add a log
			    update_option('zbsmigration280',array('completed'=>time(),'meta'=>array('updated'=>'-1')));
				*/
		    	zeroBSCRM_migrations_markComplete('280',array('updated'=>-1));

			    #} Add admin notice
			    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_280_fail' );

		}
			


	}
	function zeroBSCRM_migration_notice_280() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php _e( 'Zero BS CRM has completed a necessary migration', 'zero-bs-crm' ); ?>  2.80, <?php _e('Great!','zero-bs-crm'); ?></p>
	    </div>
	    <?php
	} 
	function zeroBSCRM_migration_notice_280_fail() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php _e( 'Zero BS CRM was unable to complete a necessary migration', 'zero-bs-crm' ); ?>  2.80, <?php _e('you may need to manually create your email templates. If this does not work, please contact support.','zero-bs-crm'); ?></p>
	    </div>
	    <?php
	} 


	/*

		Migration 2.81 - 
			- Refresh user roles 
			- migrate any 'file slots' in use
			- fix \\\\\\\\\\\' settings in db

	*/
	function zeroBSCRM_migration_281(){

		#} Glob
		global $wpdb,$zbs,$ZBSCRM_t;


		#} This function migrates users from before ver 2.4

		  #} re-add/remove any roles :)

			    // roles
				zeroBSCRM_clearUserRoles();

				// roles + 
				zeroBSCRM_addUserRoles();


		 	#} File Slots

	            $fileSlots = zeroBSCRM_fileSlots_getFileSlots();
	            if (count($fileSlots) > 0) {

	            	foreach ($fileSlots as $fs){

	            		$key = 'cfile_'.$fs['key'];

		                // get meta if exists
					    $metas = $wpdb->get_results( $wpdb->prepare( "
					        SELECT * FROM {$wpdb->postmeta} pm
					        WHERE pm.meta_key = '%s'
					    ", $key ), ARRAY_A );

					    if (is_array($metas) && count($metas) > 0){

					    	// process em
					    	foreach ($metas as $m){

								// just jam into zbs
								$zbs->DAL->updateMeta(ZBS_TYPE_CONTACT,$m['post_id'],$key,$m['meta_value']);
								
								// delete
								delete_post_meta($m['post_id'],$key);

					    	}

					    }

		                
		            }
		        }


		    #} Fix \\\\\\\\\\\\' settings in db


				    // bk settings as they are
				    $zbs->settings->createBackup('pre-slashkiller-'.time());

				    // literally get them all
				    $settingLines = $wpdb->get_results("SELECT * FROM ".$ZBSCRM_t['settings']."", ARRAY_A);
				    
				    foreach ($settingLines as $settingLine){

				      // if has more than one \\\\' then operate
				      $settingStr = $settingLine['zbsset_val'];
				      $origStr = $settingStr;

				      if (gettype($settingStr) == 'string' && strpos($settingStr, "\\\\'") > -1){

				        // replace up to 20 backslashes
				        for ($i = 0; $i < 20; $i++){

				            // 20 first..
				            $repStr = ''; for ($x = 2; $x < (20-$i); $x++) $repStr .= "\\";

				            if (!empty($repStr) && $repStr != "\\" && $repStr != "\\\\"){

				              $repStr .= "'";
				              
				              // json requires 2, so only replace down to 2
				              $settingStr = str_replace($repStr, "\\\\'", $settingStr);

				            }


				        }

				        if ($settingStr != $origStr){

				            // update
				            $wpdb->update( 
				                            $ZBSCRM_t['settings'], 
				                            array( 
				                              'zbsset_val' => $settingStr
				                            ), 
				                            array( 'ID' => $settingLine['ID'] ), 
				                            array( 
				                              '%s'
				                            ), 
				                            array( '%d' ) 
				                          );
				        }


				      }


				    }


			// mark completed
		    zeroBSCRM_migrations_markComplete('281',array('updated'=>1));

		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_281' );
			


	}
	function zeroBSCRM_migration_notice_281() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php _e( 'Zero BS CRM has completed a necessary migration', 'zero-bs-crm' ); ?>  2.81, <?php _e('Great!',"zero-bs-crm"); ?></p>
	    </div>
	    <?php
	} 





	/*

		Migration 2.70 - Attempt silent update of contact data -> DB2, and add admin notice, if not :)

	*/
	function zeroBSCRM_migration_270(){

		#} Load database migration file if not loaded		
		// now req by core :) require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.Migrations.Database.php');

		#} Glob
		global $zbs, $zeroBSCRM_Conf_Setup; #req

				// catch no-contact fresh installs + mark migrated :)
				//$contactCount = (int)zeroBS_customerCountSQL(); 
				global $wpdb;
				$contactCount = $wpdb->get_var($wpdb->prepare("SELECT count(DISTINCT p.id) FROM $wpdb->posts p WHERE p.post_type = 'zerobs_customer' AND p.post_status = %s",array('publish')));				

				if ($contactCount ==  0){

					if (!get_option('zbs_db_migration_253')){

			    		// this tries to 'close' migration + returns errors as array if any
			    		$errors = zeroBSCRM_db253migrateClose();
							
						// if no errors, switch the switch!
						if (count($errors) == 0){

							// Final close :) (note this can also be run from 270 migration automatically (so change there if here))
							$started = get_option( 'zbs_db_migration_253_inprog', time());
							update_option( 'zbs_db_migration_253', array('completed'=>time(),'started'=>$started));

							// ALL GOOD
								
						    #} Add success admin notice
						    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_270' );
						    zeroBSCRM_adminNotices_db253migrationFini();

		   					zeroBSCRM_migrations_markComplete('270',array('updated'=>'1','errs'=>$errors));

						} else {

							// ERRORS 

								// store in global for now
								//global $zbsMigrationErrors; $zbsMigrationErrors = $errors;
								// na in option
								update_option( 'zbs_db_migration_253_errors', $errors);


						    #} Add admin notice saying 'update needed'
						    add_action('after-zerobscrm-admin-init','zeroBSCRM_adminNotices_db270migrationErrors');
				    		add_action( 'admin_notices', 'zeroBSCRM_adminNotices_db270migrationErrors' );

		   					zeroBSCRM_migrations_markComplete('270',array('updated'=>'1','errs'=>$errors));

						}
  

					} else {


						/* moved to dal
					    #} Mark migrated - add to settings
					    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '270';
					    $zbs->settings->update('migrations',$migrationsExisting);
					    #} Also add a log
					    update_option('zbsmigration270',array('completed'=>time(),'meta'=>array('updated'=>'1','errs'=>false)));
					   */
		   				zeroBSCRM_migrations_markComplete('270',array('updated'=>'1','errs'=>false));

					}

				} else {

					// Not a virgin install, needs update

				    #} Add admin notice saying 'update needed'
				    //zeroBSCRM_adminNotices_db270migrationTodo();

				    add_action('after-zerobscrm-admin-init','zeroBSCRM_adminNotices_db270migrationTodo');
		    		add_action( 'admin_notices', 'zeroBSCRM_adminNotices_db270migrationTodo' );
		    		

					/* moved to dal
				    #} Mark migrated - add to settings
				    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '270';
				    $zbs->settings->update('migrations',$migrationsExisting);
				    #} Also add a log
				    update_option('zbsmigration270',array('completed'=>time(),'meta'=>array('updated'=>'1')));
				   */
	   				zeroBSCRM_migrations_markComplete('270',array('updated'=>'1'));



				}

			//}

	}

	function zeroBSCRM_adminNotices_db270migrationTodo(){

	     //pop in a Notify Me Notification here instead....?
		 if (get_current_user_id() > 0){

		     //use transients...
		     $zbsuppy = get_transient('zbs-db2-73-update');
		     if(!$zbsuppy){
		       zeroBSCRM_notifyme_insert_notification(get_current_user_id(), -999, -1, 'db2.update.253','funkydbupdate');
		       set_transient( 'zbs-db2-73-update', 20, 24 * 7 * HOUR_IN_SECONDS );
		    }

		}

	}

	function zeroBSCRM_adminNotices_db253migrationFini(){

	     //pop in a Notify Me Notification here instead....?
	     //use transients...
	     $zbsuppy = get_transient('zbs-db2-53-update-success');
	     if(!$zbsuppy){
	       zeroBSCRM_notifyme_insert_notification(get_current_user_id(), -999, -1, 'db2.update.253.success','funkydbupdate2');
	       set_transient( 'zbs-db2-53-update-success', 20, 24 * 7 * HOUR_IN_SECONDS );

	    }

	}

	function zeroBSCRM_adminNotices_db270migrationErrors(){

	     //pop in a Notify Me Notification here instead....?
		 if (get_current_user_id() > 0){

		     //use transients...
		     $zbsuppy = get_transient('zbs-db2-73-errors');
		     if(!$zbsuppy){
		       zeroBSCRM_notifyme_insert_notification(get_current_user_id(), -999, -1, 'db2.update.253.errors','db2.update.253.errors');
		       set_transient( 'zbs-db2-73-errors', 20, 24 * 7 * HOUR_IN_SECONDS );
		    }

		}

	}

	function zeroBSCRM_migration_notice_270() {

		global $zbs;

	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php echo __("Your ZBS contact database was successfully migrated. Please update any","zero-bs-crm").' <a href="'.$zbs->urls['products'].'" target="_blank">'.__("PRO Extensions","zero-bs-crm").'</a> '.__('you may have installed.',"zero-bs-crm"); ?></p>
	    </div>
	    <?php
	}



	/*

		Migration 2.75 - This fixes any multi-dupe tags created by the bug I let through where ownership 
		meant each person owned the tag :o

	*/
	function zeroBSCRM_migration_275(){

		#} Glob
		global $zbs, $wpdb, $ZBSCRM_t;
		
		$countChanged = 0;

		/* SELECT DISTINCT(t1.zbstag_name),t1.zbstag_slug,(SELECT ID FROM `wp_zbs_tags` as t2 WHERE t2.zbstag_slug = t1.zbstag_slug ORDER BY ID ASC LIMIT 0,1) 
FROM  `wp_zbs_tags` as t1
ORDER BY t1.zbstag_name
LIMIT 50

*/

/*/ NEEDS
zbstag_objtype
zbs_team
zbs_site
*/

		$tagQ = "SELECT DISTINCT(t1.zbstag_name),t1.zbstag_slug,(SELECT ID FROM ".$ZBSCRM_t['tags']." as t2 WHERE t2.zbstag_slug = t1.zbstag_slug ORDER BY ID ASC LIMIT 0,1) as tid,(SELECT COUNT(ID) FROM ".$ZBSCRM_t['tags']." as t3 WHERE t3.zbstag_slug = t1.zbstag_slug) as tc FROM ".$ZBSCRM_t['tags']." as t1 ORDER BY t1.zbstag_name";

		$potentialRes = $wpdb->get_results($tagQ, OBJECT);
		if (isset($potentialRes) && is_array($potentialRes) && count($potentialRes) > 0) {

			$tags = array();
   

			#} each unique tag
			foreach ($potentialRes as $resDataLine) {

				$slug = $resDataLine->zbstag_slug;
				$tc = (int)$resDataLine->tc;

				// is there dupes?
				if ($tc <= 1){

				} else {

					// already been 'got' so need to do the work

						// first switch all tag links over to the other obj id
						$properTagID = $resDataLine->tid;

						// Hard update directly
						$q = $wpdb->prepare('UPDATE '.$ZBSCRM_t['taglinks'].' SET zbstl_tagid = '.$properTagID.' WHERE zbstl_tagid IN (SELECT ID FROM '.$ZBSCRM_t['tags'].' WHERE zbstag_slug = %s AND ID <> %d)',array($slug,$resDataLine->tid));
						$wpdb->query($q);

						// delete shell tag(s)
						$wpdb->query($wpdb->prepare('DELETE FROM '.$ZBSCRM_t['tags'].' WHERE zbstag_slug = %s AND ID <> '.$resDataLine->tid,array($slug)));						

					$countChanged++;

				} 


			}

			// nuke all owners -1
			$wpdb->query('UPDATE '.$ZBSCRM_t['tags'].' SET zbs_owner = -1');
			$wpdb->query('UPDATE '.$ZBSCRM_t['taglinks'].' SET zbs_owner = -1');

		}
	
		/* moved to dal
	    #} Mark migrated - add to settings
	    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '275';
	    $zbs->settings->update('migrations',$migrationsExisting);
	    #} Also add a log
	    update_option('zbsmigration275',array('completed'=>time(),'meta'=>array('c'=>$countChanged)));*/
	   	zeroBSCRM_migrations_markComplete('275',array('c'=>$countChanged));

	}

	/*

		Migration 2.87
			- drop + rebuild segment db table

	*/
	function zeroBSCRM_migration_287(){

		#} Glob
		global $wpdb,$zbs,$ZBSCRM_t;

			// drop (ignore data? :o - shouldn't be any pre 287
		    $table_name = $ZBSCRM_t['segments'];
		    $sql = "DROP TABLE IF EXISTS $table_name";
		    $wpdb->query($sql);

		    // rebuild
		    zeroBSCRM_createTables();

		    
			/* moved to dal
			#} Mark migrated - add to settings
		    $migrationsExisting = $zbs->settings->get('migrations'); $migrationsExisting[] = '287';
		    $zbs->settings->update('migrations',$migrationsExisting);
		    #} Also add a log
		    update_option('zbsmigration287',array('completed'=>time(),'meta'=>array('updated'=>'1')));*/
	   		zeroBSCRM_migrations_markComplete('287',array('updated'=>'1'));

		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_287' );
			


	}
	function zeroBSCRM_migration_notice_287() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php _e( 'Zero BS CRM has completed a necessary migration', 'zero-bs-crm' ); ?>  2.87, <?php _e('Great!',"zero-bs-crm"); ?></p>
	    </div>
	    <?php
	} 

	/*

		Migration 2.88
			- build client portal page (moved to shortcodes) if using

	*/
	function zeroBSCRM_migration_288(){

		global $zbs;

		// weirdly, this is firing 3 times
		// (ONLY THIS migration? Really strange. Couldn't replicate with any other migration, so gonna just do this check:)
		// REQUIRES this:
		//if (!get_option('zbsmigration288')){
	
			// Note: On fresh install, portal isn't installed before this migration runs.
			// ... so for now leave it to ALWAYS install page
			// ... if we switch to 'CP not on by default', we need to kill this migration
			// if (zeroBSCRM_isExtensionInstalled('portal')) {
				zeroBSCRM_portal_checkCreatePage();
			//}

	   		zeroBSCRM_migrations_markComplete('288',array('updated'=>'1'));

		//}

	}



	/*

		Migration 2.94.2 - Refresh user roles (again)

	*/
	function zeroBSCRM_migration_2943(){

		#} Glob
		global $zbs, $zeroBSCRM_Conf_Setup; #req

		  #} re-add/remove any roles :)

			    // roles
				zeroBSCRM_clearUserRoles();

				// roles + 
				zeroBSCRM_addUserRoles();

		  #} Check if using non-wp_mail del methods + give notice
  			$zbsSMTPAccs = zeroBSCRM_getMailDeliveryAccs(); 
  			if (is_array($zbsSMTPAccs)){
  				$smtp = false;
  				foreach ($zbsSMTPAccs as $acc){
  					if (is_array($acc) && isset($acc['mode']) && $acc['mode'] == 'smtp') $smtp = true;
  				}

  				if ($smtp){

					// is using a smtp method, show notice
					if (get_current_user_id() > 0){

					     //use transients...
					     $zbsuppy = get_transient('zbs-smtp2943-errors');
					     if(!$zbsuppy){
					       zeroBSCRM_notifyme_insert_notification(get_current_user_id(), -999, -1, 'smtp.2943.needtocheck','smtp.2943.needtocheck');
					       set_transient( 'zbs-smtp2943-errors', 20, 24 * 7 * HOUR_IN_SECONDS );
					    }

					}
  				}
  			}


	    	zeroBSCRM_migrations_markComplete('2943',array('updated'=>1));

		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_2943' );


	}
	function zeroBSCRM_migration_notice_2943() {
	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php _e( 'Zero BS CRM has completed a necessary migration', 'zero-bs-crm' ); ?>  2.94.2, <?php _e('Great!',"zero-bs-crm"); ?></p>

	    </div>
	    <?php
	} 



	/*

		Migration 2.95 - alter sys emails table + reset user roles (Added emails)

	*/
	function zeroBSCRM_migration_295(){

		#} Glob
		global $zbs, $zeroBSCRM_Conf_Setup; #req

		  	#} run the alter, if not already run :) (migration engine deals with that)

			    // the update
				zeroBSCRM_update_mail_history_table();

		  #} re-add/remove any roles :)

			    // roles
				zeroBSCRM_clearUserRoles();

				// roles + 
				zeroBSCRM_addUserRoles();


	    	zeroBSCRM_migrations_markComplete('295',array('updated'=>1));

		    #} Add admin notice
		    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_295' );


	}
	function zeroBSCRM_migration_notice_295() {

		$link = zeroBSCRM_changelogLink();

	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php _e( 'Zero BS CRM has completed a necessary migration', 'zero-bs-crm' ); ?>  2.94.2, <?php _e('Great!',"zero-bs-crm"); ?></p>
	        <?php if (!empty($link)) echo '<p>'.$link.'</p>'; ?>
	    </div>
	    <?php
	} 



	/*

		Migration 2.952 - adds cronmanager tables silently

	*/
	function zeroBSCRM_migration_2952(){

		#} Glob
		global $zbs, $zeroBSCRM_Conf_Setup; #req

		  	#} running this will detect lack of tables + create
			zeroBSCRM_checkTablesExist();

			#} Mark complete (Silent)
	    	zeroBSCRM_migrations_markComplete('2952',array('updated'=>1));

	}


	/*

		Migration 2.96.2 (less than current ver) - fixes the messed up migration from 2.95

	*/

	function zeroBSCRM_migration_2962(){

		global $ZBSCRM_t,$wpdb;

		if (!zeroBSCRM_migration_tableHasColumn($ZBSCRM_t['system_mail_hist'],'zbsmail_sender_maildelivery_key')){

			$sql = "ALTER TABLE " . $ZBSCRM_t['system_mail_hist'] . " ADD COLUMN zbsmail_sender_maildelivery_key varchar(200) DEFAULT NULL";
			$wpdb->query($sql);

		}

		zeroBSCRM_migrations_markComplete('2962',array('updated'=>1));


	}
	/*

		Migration 2.96.3 - adds new template for 'client portal pw reset'

	*/

	function zeroBSCRM_migration_2963(){

		global $ZBSCRM_t,$wpdb;

		#} default is admin email and CRM name	
		//now all done via zeroBSCRM_mailDelivery_defaultFromname
		$from_name = zeroBSCRM_mailDelivery_defaultFromname();

		/* This wasn't used in end, switched to default mail delivery opt 
		$from_address = zeroBSCRM_mailDelivery_defaultEmail();; //default WordPress admin email ?
		$reply_to = '';
		$cc = ''; */
		$deliveryMethod = zeroBSCRM_getMailDeliveryDefault(); 
		
		$ID = 6;
		$reply_to = '';
		$cc = '';
		$bcc = '';

		#} The email stuff...
		$subject = __("Your Client Portal Password", 'zero-bs-crm');
		$content = zeroBSCRM_mail_retrieveDefaultBodyTemplate('clientportalpwreset');
		$active = 1; //1 = true..
		if(zeroBSCRM_mailTemplate_exists($ID) == 0){
			$content = zeroBSCRM_mailTemplate_processEmailHTML($content);
			//zeroBSCRM_insertEmailTemplate($ID,$from_name,$from_address,$reply_to,$cc,$bcc,$subject,$content,$active);
			zeroBSCRM_insertEmailTemplate($ID,$deliveryMethod,$bcc,$subject,$content,$active);
		}

		zeroBSCRM_migrations_markComplete('2963',array('updated'=>1));


	}


	// last one hadn't got the html file, this ADDS file proper :)
	function zeroBSCRM_migration_2964(){

		global $ZBSCRM_t,$wpdb;


		#} default is admin email and CRM name	
		//now all done via zeroBSCRM_mailDelivery_defaultFromname
		$from_name = zeroBSCRM_mailDelivery_defaultFromname();

		/* This wasn't used in end, switched to default mail delivery opt 
		$from_address = zeroBSCRM_mailDelivery_defaultEmail();; //default WordPress admin email ?
		$reply_to = '';
		$cc = ''; */
		$deliveryMethod = zeroBSCRM_getMailDeliveryDefault(); 
		
		$ID = 6;
		$reply_to = '';
		$cc = '';
		$bcc = '';

		// BRUTAL DELETE old one
		$wpdb->delete( $ZBSCRM_t['system_mail_templates'], array( 'zbsmail_id' => $ID ) );

		#} The email stuff...
		$subject = __("Your Client Portal Password", 'zero-bs-crm');
		$content = zeroBSCRM_mail_retrieveDefaultBodyTemplate('clientportalpwreset');
		
		$active = 1; //1 = true..
		if(zeroBSCRM_mailTemplate_exists($ID) == 0){
			$content = zeroBSCRM_mailTemplate_processEmailHTML($content);
			//zeroBSCRM_insertEmailTemplate($ID,$from_name,$from_address,$reply_to,$cc,$bcc,$subject,$content,$active);
			zeroBSCRM_insertEmailTemplate($ID,$deliveryMethod,$bcc,$subject,$content,$active);
		}

		zeroBSCRM_migrations_markComplete('2964',array('updated'=>1));


	}

	// adds template for 'invoice summary statement sent'
	function zeroBSCRM_migration_2966(){

		global $ZBSCRM_t,$wpdb;


		#} default is admin email and CRM name	
		//now all done via zeroBSCRM_mailDelivery_defaultFromname
		$from_name = zeroBSCRM_mailDelivery_defaultFromname();

		/* This wasn't used in end, switched to default mail delivery opt 
		$from_address = zeroBSCRM_mailDelivery_defaultEmail();; //default WordPress admin email ?
		$reply_to = '';
		$cc = ''; */
		$deliveryMethod = zeroBSCRM_getMailDeliveryDefault(); 
		
		$ID = 7;
		$reply_to = '';
		$cc = '';
		$bcc = '';
		
		#} The email stuff...
		$subject = __("Your Statement", 'zero-bs-crm');
		$content = zeroBSCRM_mail_retrieveDefaultBodyTemplate('invoicestatementsent');

		// BRUTAL DELETE old one
		$wpdb->delete( $ZBSCRM_t['system_mail_templates'], array( 'zbsmail_id' => $ID ) );
		
		$active = 1; //1 = true..
		if(zeroBSCRM_mailTemplate_exists($ID) == 0){
			$content = zeroBSCRM_mailTemplate_processEmailHTML($content);
			//zeroBSCRM_insertEmailTemplate($ID,$from_name,$from_address,$reply_to,$cc,$bcc,$subject,$content,$active);
			zeroBSCRM_insertEmailTemplate($ID,$deliveryMethod,$bcc,$subject,$content,$active);
		}

		zeroBSCRM_migrations_markComplete('2966',array('updated'=>1));


	}


	// 2.97.2 - adds db performance improvements for contacts retrieved via tag (including adding indexes)
	function zeroBSCRM_migration_2972(){

		global $ZBSCRM_t,$wpdb;

		// add indexs to tag link table
		$sql = "ALTER TABLE " . $ZBSCRM_t['taglinks'] . " ADD INDEX `zbstl_objid` (`zbstl_objid`), ADD INDEX `zbstl_tagid` (`zbstl_tagid`);";
		$wpdb->query($sql);

		// sys mail history
		$sql = "ALTER TABLE " . $ZBSCRM_t['system_mail_hist'] . " ADD INDEX `zbsmail_sender_wpid` (`zbsmail_sender_wpid`), ADD INDEX `zbsmail_sender_mailbox_id` (`zbsmail_sender_mailbox_id`);";
		$wpdb->query($sql);

		// object links
		$sql = "ALTER TABLE " . $ZBSCRM_t['objlinks'] . " ADD INDEX `zbsol_objid_from` (`zbsol_objid_from`), ADD INDEX `zbsol_objid_to` (`zbsol_objid_to`);";
		$wpdb->query($sql);

		// settings
		$sql = "ALTER TABLE " . $ZBSCRM_t['settings'] . " ADD UNIQUE `zbsset_key` (`zbsset_key`);";
		$wpdb->query($sql);

		// logs
		$sql = "ALTER TABLE " . $ZBSCRM_t['logs'] . " ADD INDEX `zbsl_objid` (`zbsl_objid`);";
		$wpdb->query($sql);

		// ext source
		$sql = "ALTER TABLE " . $ZBSCRM_t['externalsources'] . " ADD INDEX `zbss_contactid` (`zbss_contactid`);";
		$wpdb->query($sql);

		// contacts
		$sql = "ALTER TABLE " . $ZBSCRM_t['contacts'] . " ADD INDEX `subfields` (`zbsc_email`, `zbsc_wpid`);";
		$wpdb->query($sql);

		// aka
		$sql = "ALTER TABLE " . $ZBSCRM_t['aka'] . " ADD INDEX `aka_id_aka_alias` (`aka_id`, `aka_alias`);";
		$wpdb->query($sql);

		// should run FAST now.

		zeroBSCRM_migrations_markComplete('2972',array('updated'=>1));


	}


	// 2.97.4 - fixes duplicated email templates (found on 2 installs so far)
	function zeroBSCRM_migration_2974(){

		global $ZBSCRM_t,$wpdb;

		// 7 template emails up to here :)
		for ($i = 0; $i <= 7; $i++){

			// count em
			$sql = $wpdb->prepare("SELECT ID FROM " . $ZBSCRM_t['system_mail_templates'] . " WHERE zbsmail_id = %d GROUP BY ID ORDER BY zbsmail_id DESC, zbsmail_lastupdated DESC", $i);
			$r = $wpdb->get_results($sql, ARRAY_A);

				// if too many, delete oldest (few?)
				if (is_array($r) && count($r) > 1){

					$count = 0;

					// first stays, as the above selects in order by last updated
					foreach ($r as $x){

						// if already got one, delete this (extra)
						if ($count > 0){

							// BRUTAL DELETE old one
							$wpdb->delete( $ZBSCRM_t['system_mail_templates'], array( 'ID' => $x['ID'] ) );

						}

						$count++;

					}

				}


		}

		zeroBSCRM_migrations_markComplete('2974',array('updated'=>1));

	}

	// 2.97.5 - corrects borked external sources setup.
	function zeroBSCRM_migration_2975(){

		global $ZBSCRM_t,$wpdb;

		if (!zeroBSCRM_migration_tableHasColumn($ZBSCRM_t['externalsources'],'zbss_objtype')){

			// fix table
			$sql = "ALTER TABLE " . $ZBSCRM_t['externalsources'] . " ADD `zbss_objtype` int(3) NOT NULL DEFAULT '-1' AFTER `zbs_owner`, CHANGE `zbss_contactid` `zbss_objid` int(32) NOT NULL AFTER `zbss_objtype`;";
			$wpdb->query($sql);

		}

		// cyce through all external source, recovering them where we can!
		// WH modified in 2.97.6+ so this does in pages
		// ... it wont mark migration completed until there're no results left to translate.
		// ... so it'll do 300 per page load // changed to 1000 2.97.7
		// ... this is a throttling to overcome potential: https://epicplugins.groovehq.com/groove_client/mailboxes/49227/filters/188769/tickets/105063569
		/*	

			How this bug has manifested:

				- All contacts added/updated since DAL2 will have a line in $ZBSCRM_t['externalsources'] + NO corresponding postmeta line (these have been via DAL and  not via zeroBS_updateExternalSource())
				- All companies & trans added/updated since DAL2 will have line in ^ AND corresponding meta (these HAVE been using zeroBS_updateExternalSource())


		*/
		$sql = "SELECT ID,zbss_objid,zbss_objid,zbss_source,zbss_uid FROM " . $ZBSCRM_t['externalsources']  . " WHERE zbss_objtype = -1 ORDER BY ID ASC LIMIT 0,1000";
		$r = $wpdb->get_results($sql, ARRAY_A);

		// got any?
		if (count($r) > 0){

			// cycle through em
			foreach ($r as $objExternalSource){

				if (is_array($objExternalSource) && isset($objExternalSource['zbss_source']) && !empty($objExternalSource['zbss_source'])
					&& isset($objExternalSource['zbss_uid']) && !empty($objExternalSource['zbss_uid'])){

					// e.g.
					// trans: 123|545|woo|18712 + postmeta to match
					// contact: 123|545|woo|dave@dave.com + no postmeta

					// see if has matching meta
					$possibleMeta = (int)$wpdb->get_var($wpdb->prepare("SELECT count(meta_id) FROM $wpdb->postmeta WHERE meta_key = 'zbs_obj_ext_".$objExternalSource['zbss_source']."' AND meta_value = %s AND post_id = %d",array($objExternalSource['zbss_uid'],$objExternalSource['zbss_objid'])));
		

					// is it email? or has no meta match?
					if ($possibleMeta < 1){ // zeroBSCRM_validateEmail($objExternalSource['zbss_uid']) && 

						// probably a contact
						// set it 
							$wpdb->update( 
									$ZBSCRM_t['externalsources'], 
									array( 
										'zbss_objtype' => 1 // contact
									), 
									array( // where
										'ID' => $objExternalSource['ID']
										),
									array(
										'%d'
									),
									array( // where data types
										'%d'
										));

					} else {

						// trans or CO - discern by checking post_id
						$type = $wpdb->get_var($wpdb->prepare("SELECT post_type FROM $wpdb->posts WHERE ID = %d",array($objExternalSource['zbss_objid'])));
		
						// switch
						if ($type == 'zerobs_transaction'){

							// set it 
							$wpdb->update( 
									$ZBSCRM_t['externalsources'], 
									array( 
										'zbss_objtype' => 5 // transaction
									), 
									array( // where
										'ID' => $objExternalSource['ID']
										),
									array(
										'%d'
									),
									array( // where data types
										'%d'
										));

						} else if ($type == 'zerobs_company'){

							// set it 
							$wpdb->update( 
									$ZBSCRM_t['externalsources'], 
									array( 
										'zbss_objtype' => 2 // company
									), 
									array( // where
										'ID' => $objExternalSource['ID']
										),
									array(
										'%d'
									),
									array( // where data types
										'%d'
										));

						} else {

							// fall through cracks in the floor
						}


					}

				}

			}

			// leave to mark done next migration run, if none left to do

		} else {

			// no res, done
			zeroBSCRM_migrations_markComplete('2975',array('updated'=>1));

		}

	}


	 // 2.97.7 - Fixes an index to allow non-uniques (for user screen options)
	function zeroBSCRM_migration_2977(){

		global $ZBSCRM_t,$wpdb;

		// https://stackoverflow.com/questions/127156/how-do-i-check-if-an-index-exists-on-a-table-field-in-mysql
		$row = $wpdb->get_results(  "SHOW INDEX FROM " . $ZBSCRM_t['settings'] . " WHERE Column_name = 'zbs_owner'; "  );

		if (empty($row)){

			// fix table
			$sql = "ALTER TABLE `" . $ZBSCRM_t['settings'] . "`
ADD INDEX `zbsset_key` (`zbsset_key`),
ADD INDEX `zbs_owner` (`zbs_owner`),
DROP INDEX `zbsset_key`;";
			$wpdb->query($sql);

		}

		zeroBSCRM_migrations_markComplete('2977',array('updated'=>1));

	}


	 // 2.98.4 - Fixes segment conditions bug
	function zeroBSCRM_migration_2984(){

		global $ZBSCRM_t,$wpdb;

		if (!zeroBSCRM_migration_tableHasColumn($ZBSCRM_t['segmentsconditions'],'zbscondition_op')){

			// fix table
			$sql = "ALTER TABLE `" . $ZBSCRM_t['segmentsconditions'] . "` ADD `zbscondition_op` VARCHAR(50) NULL AFTER `zbscondition_type`;";
			$wpdb->query($sql);

		}

		zeroBSCRM_migrations_markComplete('2984',array('updated'=>1));

	}

	// 2.98.1 - creates invoice tax table
	function zeroBSCRM_migration_2981(){

		//global $ZBSCRM_t, $wpdb;
		//require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
		// Create tax table, WH moved into createTables func, where sits better

			  #} Check + create
			  zeroBSCRM_checkTablesExist();

		//create a tax table from the invoices already in the system
		

		zeroBSCRM_migrations_markComplete('2981',array('updated'=>1));

	}


	// 2.99.0 - install tables for DAL3.0
	function zeroBSCRM_migration_2999(){

		// table install
		zeroBSCRM_checkTablesExist();

		// fini
		zeroBSCRM_migrations_markComplete('2999',array('updated'=>1));

	}

	// 2.99.99 - set permalinks to flush with v3.0 migration
	function zeroBSCRM_migration_29999(){

		// set permalinks to flush, this'll cause them to be refreshed on 3000 migration
		// ... as that has preload setting
		zeroBSCRM_rewrite_setToFlush();

		// fini
		zeroBSCRM_migrations_markComplete('29999',array('updated'=>1));

	}



	// 3.0 - Migrate all the THINGS
	// UNTESTED as at 28/2/19
	function zeroBSCRM_migration_3000(){

		#} Load database migration file if not loaded		
		// now req by core :) require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.Migrations.Database.php');

		// Initial database check - this'll create any missing tables.
		zeroBSCRM_checkTablesExist();

		// staged migration. Run automatially only for those without any data (virgin CRM installs)		
		global $wpdb;
		$objCount = (int)$wpdb->get_var("SELECT count(DISTINCT p.id) FROM $wpdb->posts p WHERE p.post_type IN ('zerobs_company','zerobs_invoice','zerobs_quote','zerobs_transaction','zerobs_form') AND p.post_status = 'publish'");

		if ($objCount < 1){

				// virgin  install

				if (!get_option('zbs_db_migration_300')){

					// 'open' the migration
					zeroBSCRM_migration_open_300();

					// nothing to migrate as per $objCount above

					// 'close' the migration
					zeroBSCRM_migration_close_300();

		    		// any errors?
					$errors = get_option('zbs_db_migration_300_errstack', array());
						
					// if no errors, switch the switch!
					if (count($errors) == 0){

						// ALL GOOD
							
					    #} Add success admin notice
					    add_action( 'admin_notices', 'zeroBSCRM_migration_notice_300' );
					    zeroBSCRM_adminNotices_db300migrationFini();

					} else {

						// ERRORS 

					    #} Add admin notice saying migrated with errors
					    add_action('after-zerobscrm-admin-init','zeroBSCRM_adminNotices_db300migrationErrors');
			    		add_action( 'admin_notices', 'zeroBSCRM_adminNotices_db300migrationErrors' );

					}

					// mark migration fini
					zeroBSCRM_migrations_markComplete('3000',array('updated'=>'1','errs'=>false));

				} else {

					// otherwise, migration is either 'stuck' or fini already? shouldn't ever occur?

					// mark migration fini
					zeroBSCRM_migrations_markComplete('3000',array('updated'=>'1','errs'=>false));

				}


		} else {


			// MIGRATION NEEDED			
			// Not a virgin install, needs update

			// notifications
		    add_action('after-zerobscrm-admin-init','zeroBSCRM_adminNotices_db300migrationTodo');
    		add_action( 'admin_notices', 'zeroBSCRM_adminNotices_db300migrationTodo' );

		   	// mark migration fini
		   	zeroBSCRM_migrations_markComplete('3000',array('updated'=>'1','errs'=>false));

		}


	}




	function zeroBSCRM_adminNotices_db300migrationTodo(){

	     //pop in a Notify Me Notification here instead....?
		 if (get_current_user_id() > 0){

		     //use transients...
		     $zbsuppy = get_transient('zbs-db3-300-update');
		     if(!$zbsuppy){
		       zeroBSCRM_notifyme_insert_notification(get_current_user_id(), -999, -1, 'db3.update.300','funkydbupdate');
		       set_transient( 'zbs-db3-300-update', 20, 24 * 7 * HOUR_IN_SECONDS );
		    }

		}

	}

	function zeroBSCRM_adminNotices_db300migrationFini(){

	     //pop in a Notify Me Notification here instead....?
	     //use transients...
	     $zbsuppy = get_transient('zbs-db3-300-update-success');
	     if(!$zbsuppy){
	       zeroBSCRM_notifyme_insert_notification(get_current_user_id(), -999, -1, 'db3.update.300.success','funkydbupdate2');
	       set_transient( 'zbs-db3-300-update-success', 20, 24 * 7 * HOUR_IN_SECONDS );

	    }

	}
	function zeroBSCRM_migration_notice_300() {

		global $zbs;

	    ?>
	    <div class="updated notice is-dismissable">
	        <p><?php echo __("Your ZBS database was successfully migrated. Please update any","zero-bs-crm").' <a href="'.$zbs->urls['products'].'" target="_blank">'.__("PRO Extensions","zero-bs-crm").'</a> '.__('you may have installed.',"zero-bs-crm"); ?></p>
	    </div>
	    <?php
	}

	function zeroBSCRM_adminNotices_db300migrationErrors(){

	     //pop in a Notify Me Notification here instead....?
		 if (get_current_user_id() > 0){

		     //use transients...
		     $zbsuppy = get_transient('zbs-db3-300-errors');
		     if(!$zbsuppy){
		       zeroBSCRM_notifyme_insert_notification(get_current_user_id(), -999, -1, 'db3.update.300.errors','db3.update.300.errors');
		       set_transient( 'zbs-db3-300-errors', 20, 24 * 7 * HOUR_IN_SECONDS );
		    }

		}

	}
	

	// 3.0.5 - catch instances where really old installs saved customer statuses as trans statuses gh-179
	function zeroBSCRM_migration_305(){

		global $zbs;

		// detect this issue:
    	$customisedFields = $zbs->settings->get('customisedfields');


            #} retrieve value as simple CSV for now - simplistic at best.
            $zbsTranStatusStr = ''; 
            if (isset($customisedFields['transactions']['status']) && is_array($customisedFields['transactions']['status'])) $zbsTranStatusStr = $customisedFields['transactions']['status'][1];                                        
            if (empty($zbsTranStatusStr)) {
              #} Defaults:
              global $zbsTransactionFields; if (is_array($zbsTransactionFields)) $zbsTranStatusStr = implode(',',$zbsTransactionFields['status'][3]);
            }

            // ... it shouldn't be
            if ($zbsTranStatusStr == 'Lead,Customer,Refused,Blacklisted,Cancelled by Customer,Cancelled by Us (Pre-Quote),Cancelled by Us (Post-Quote)'){

            	// this *should* catch these users with this still unfixed

	            	// back it up (in case we later hit any issues)
	            	update_option( 'zbsmigrationbk_305',$customisedFields);

	            	// fix it so that the defaults are used
	            	$customisedFields['transactions']['status'][1] = 'Succeeded,Completed,Failed,Refunded,Processing,Pending,Hold,Cancelled';
	          		$zbs->settings->update('customisedfields',$customisedFields);

	          		// fix also the default 'which statuses to include in totals'
	          		$zbs->settings->update('transinclude_status','["succeeded","completed"]');

            }

		// fini
		zeroBSCRM_migrations_markComplete('305',array('updated'=>1));

	}


	// 3.0.8 - Anyone with pdf module installed already, install pdf fonts for them
	function zeroBSCRM_migration_308(){

		global $zbs;

		$shouldBeInstalled = zeroBSCRM_getSetting('feat_pdfinv');
		if ($shouldBeInstalled == "1"){

	        #} install pdf fonts 
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.PDFBuilder.php');
			zeroBSCRM_PDFBuilder_retrieveFonts();

		}

		// fini
		zeroBSCRM_migrations_markComplete('308',array('updated'=>1));

	}


/* ======================================================
	/ MIGRATIONS
   ====================================================== */






/* ======================================================
	MIGRATION HELPER FUNCS
   ====================================================== */

	// returns link to consistency page, if not wl :)
	function zeroBSCRM_changelogLink(){

			$link = '';
			##WLREMOVE
			$link = '  <a href="https://zerobscrm.com/consistency/#full-changelog-timeline" target="_blank">'.__('View the update','zero-bs-crm').'</a>';
			##/WLREMOVE

			return $link;

	}



/* Migration used, updates mail history table for those who already have it :) */
function zeroBSCRM_update_mail_history_table(){
  
  global $ZBSCRM_t,$wpdb;

  /* 

  ORIGINAL TABLE
        `ID` INT NOT NULL AUTO_INCREMENT,
        `zbs_site` INT NULL DEFAULT NULL,
        `zbs_team` INT NULL DEFAULT NULL,
        `zbs_owner` INT NOT NULL,
        `zbsmail_type` INT NOT NULL,
        `zbsmail_sender_email` VARCHAR(200) NOT NULL,
        `zbsmail_sender_wpid` INT NOT NULL,     
        `zbsmail_sent` INT NOT NULL,
        `zbsmail_target_objid` INT NOT NULL,
        `zbsmail_assoc_objid` INT NOT NULL,
        `zbsmail_subject` VARCHAR(200),
        `zbsmail_hash` VARCHAR(128),
        `zbsmail_opened` INT NOT NULL,
        `zbsmail_clicked` INT NOT NULL,
        `zbsmail_lastopened` INT(14) NOT NULL,
        `zbsmail_lastclicked` INT(14) NOT NULL,
        `zbsmail_created` INT(14) NOT NULL,

  NEW TABLE (with comments about the table)

        `ID` int(11) NOT NULL,
        `zbs_site` int(11) DEFAULT NULL,
        `zbs_team` int(11) DEFAULT NULL,
        `zbs_owner` int(11) NOT NULL,
        `zbsmail_type` int(11) NOT NULL,
        `zbsmail_sender_thread` int(11) NOT NULL,
        `zbsmail_sender_email` varchar(200) NOT NULL,
        `zbsmail_sender_wpid` int(11) NOT NULL,
        `zbsmail_sender_mailbox_id` int(11) NOT NULL,
        `zbsmail_sender_mailbox_name` varchar(200) DEFAULT NULL,
        `zbsmail_receiver_email` varchar(200) NOT NULL,
        `zbsmail_sent` int(11) NOT NULL,
        `zbsmail_target_objid` int(11) NOT NULL,
        `zbsmail_assoc_objid` int(11) NOT NULL,
        `zbsmail_subject` varchar(200) DEFAULT NULL,
        `zbsmail_content` longtext,
        `zbsmail_hash` varchar(128) DEFAULT NULL,
        *REMOVING* `zbsmail_scheduled` int(11) NOT NULL,   *REMOVING*
        `zbsmail_status` varchar(120) DEFAULT NULL,
        *REMOVING* `zbsmail_condition` int(11) NOT NULL, *REMOVING*
        `zbsmail_starred` int(11) DEFAULT NULL,
        *REMOVING* `zbsmail_time_sent` int(11) NOT NULL, *REMOVING*
        `zbsmail_opened` int(11) NOT NULL,
        `zbsmail_clicked` int(11) NOT NULL,
        `zbsmail_firstopened` int(14) NOT NULL,
        `zbsmail_lastopened` int(14) NOT NULL,
        `zbsmail_lastclicked` int(14) NOT NULL,
        `zbsmail_created` int(14) NOT NULL,

  WHATS NEW (and WHY)    

        #} WH mail delivery below - so we can use it in threaded sends? 

        `zbsmail_sender_mailbox_id` int(11) NOT NULL,
        `zbsmail_sender_mailbox_name` varchar(200) DEFAULT NULL,

        #} WH changed to :

	        `zbsmail_sender_maildelivery_key` varchar(200) DEFAULT NULL,
	        --(removed)`zbsmail_sender_mailbox_name` varchar(200) DEFAULT NULL,

        #} Other additions
     
        `zbsmail_receiver_email` varchar(200) NOT NULL,   //who have we sent to
        `zbsmail_content` longtext,                       //store the content of the emails now

        `zbsmail_sender_thread` int(11) NOT NULL,         //so we can thread replies
        `zbsmail_status` varchar(120) DEFAULT NULL,       //sent, inbox (can be expanded, i.e. draft)
        `zbsmail_starred` int(11) DEFAULT NULL,           //is it a favourite or not


        `zbsmail_firstopened` int(14) NOT NULL,           //so it maintains the "Read: [Date]" and not over-written


   


  THE BELOW SQL WILL "MIGRATE" OLD HISTORY TABLE TO NEW. BUT FOR THOSE WITH NO CONTENT. IT'LL SHOW "no content was stored for this email".

  SHOULD ONLY EVER FIRE ONCE :)

  */

  // 3.0 split these into individual calls to ensure we're never trying to alter already existing columns
  $alterSQL = "ALTER TABLE " . $ZBSCRM_t['system_mail_hist'] . " ";
  if (!zeroBSCRM_migration_tableHasColumn($ZBSCRM_t['system_mail_hist'],'zbsmail_sender_maildelivery_key')) $wpdb->query($alterSQL . "ADD COLUMN zbsmail_sender_maildelivery_key varchar(200) DEFAULT NULL");
  if (!zeroBSCRM_migration_tableHasColumn($ZBSCRM_t['system_mail_hist'],'zbsmail_receiver_email')) $wpdb->query($alterSQL . "ADD COLUMN zbsmail_receiver_email varchar(200) NOT NULL");
  if (!zeroBSCRM_migration_tableHasColumn($ZBSCRM_t['system_mail_hist'],'zbsmail_content')) $wpdb->query($alterSQL . "ADD COLUMN zbsmail_content longtext");
  if (!zeroBSCRM_migration_tableHasColumn($ZBSCRM_t['system_mail_hist'],'zbsmail_sender_thread')) $wpdb->query($alterSQL . "ADD COLUMN zbsmail_sender_thread int(11) NOT NULL");
  if (!zeroBSCRM_migration_tableHasColumn($ZBSCRM_t['system_mail_hist'],'zbsmail_status')) $wpdb->query($alterSQL . "ADD COLUMN zbsmail_status varchar(120) DEFAULT NULL");
  if (!zeroBSCRM_migration_tableHasColumn($ZBSCRM_t['system_mail_hist'],'zbsmail_starred')) $wpdb->query($alterSQL . "ADD COLUMN zbsmail_starred int(11) DEFAULT NULL");
  if (!zeroBSCRM_migration_tableHasColumn($ZBSCRM_t['system_mail_hist'],'zbsmail_firstopened')) $wpdb->query($alterSQL . "ADD COLUMN zbsmail_firstopened int(14) NOT NULL");

  //migration will need to set all existing emails to "sent". Need to consider how to handle the system ones too (next check)
  $sql = "UPDATE " . $ZBSCRM_t['system_mail_hist'] . " SET zbsmail_status = 'sent'";
  $wpdb->query($sql);

}

/* ======================================================
	/ MIGRATION HELPER FUNCS
   ====================================================== */


/* ======================================================
   MIGRATION Pages
   ====================================================== */

  function zeroBSCRM_page_contactMigrationDB2() {
    
    global $zbs;

    if (!current_user_can( 'admin_zerobs_manage_options' )) exit('Goodbye');

    // check status
    $stage = 0;
    if (isset($_GET['gomode'])) $stage = 1;
    if (isset($_GET['fini'])) $stage = 2;

    $finiFlag = get_option('zbs_db_migration_253');
    if ($finiFlag > 0){

      // should be fini
      $stage = 2;

    }


    ?><div id="zbs-dbmigration"><?php


    switch ($stage){

      // not yet started
      case 0:

        ?><div class="ui very padded segment">
          <h1 class="ui header"><i class="database icon"></i> <?php _e('Database Upgrade',"zero-bs-crm"); ?></h1>
          <p><?php _e('An essential database update is required before you can continue to edit Zero BS CRM contacts. This update will significantly improve the performance of ZBS CRM.',"zero-bs-crm"); ?></p>
          <div class="ui divider"></div>
          <p><?php _e('Your contact data will be migrated from the existing database into the new one:',"zero-bs-crm"); ?></p>
          <p><strong><i class="angle double right icon"></i> <?php _e('Before we begin, please back-up your database!',"zero-bs-crm"); ?></strong></p>          
          <p><strong><i class="angle double right icon"></i> <?php _e('Please allow 1 - 30 minutes, (likely less)',"zero-bs-crm"); ?></strong></p>
          <p><strong><i class="angle double right icon"></i> <?php _e('Please leave this open until completed',"zero-bs-crm"); ?></strong></p>
          <div class="ui divider"></div>
          <p><strong><?php _e('NOTE: If you use the ZBS API, or a sync extension, for adding contacts, it is important you run this routine in one sitting.',"zero-bs-crm"); ?></strong></p>
          <div class="ui divider"></div>
          <p style="text-align:center">
              <a href="https://zerobscrm.com/kb/knowledge-base/updating-contact-database-dbv2-migration/" target="_blank" class="ui large button basic"><?php _e('Read Guide',"zero-bs-crm"); ?></a>
              <a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['migratedb2contacts'].'&gomode=1' ); ?>" class="ui large button green"><?php _e('I have backed up my database, Begin',"zero-bs-crm"); ?></a>
            </p>
        </div><?php

      break;

      // started, to continue
      case 1:

        ?><div class="ui very padded segment">
          <div class="ui active inline loader right floated" id="zbs-processing-ico"></div>
          <h1 class="ui header" style="margin-top:0;"><i class="database icon"></i> <?php _e('Database Upgrade: Processing',"zero-bs-crm"); ?></h1>
          <div class="ui divider"></div>
          <div id="zbs-db-processing">
            <p><strong><i class="angle double right icon"></i> <?php _e('Beginning Database Upgrade...',"zero-bs-crm"); ?></strong></p>
          </div>
          <div class="ui divider"></div>
            <div id="zbs-db-processing-stats" class="ui segment"></div>
          <div class="ui divider"></div>
          <p style="text-align:center" id="zbs-db-process-actions">
              <a href="https://zerobscrm.com/kb/knowledge-base/updating-contact-database-dbv2-migration/" target="_blank" class="ui large button basic"><?php _e('Read Guide',"zero-bs-crm"); ?></a>
              <button class="ui large button orange" id="zbs-dbmigration-stop"><i class="pause circle icon"></i><?php _e('Pause',"zero-bs-crm"); ?></button>
              <button class="ui large button green hidden" id="zbs-dbmigration-resume"><i class="play circle icon"></i><?php _e('Resume',"zero-bs-crm"); ?></button>
          </p>
        </div><?php

      break;

      // fini
      case 2:

        ?><div class="ui very padded segment">
          <h1 class="ui header green"><i class="database icon"></i> <?php _e('Database Upgrade Complete',"zero-bs-crm"); ?></h1>
          <p><?php _e('Your database has been successfully upgraded, thank you for using Zero BS CRM!',"zero-bs-crm"); ?></p>
          <div class="ui divider"></div>
          <img src="<?php echo $zbs->urls['extimgrepo']; ?>__zbs-completed.png" alt="Completed Migration" align="right" />
          <p><strong><i class="angle double right icon"></i> <?php _e('Your Contacts are now stored in an optimised table',"zero-bs-crm"); ?></strong></p>
          <p><strong><i class="angle double right icon"></i> <?php _e('Your Logs, Settings, Tags, External Sources have also been optimised',"zero-bs-crm"); ?></strong></p>
          <?php
            // Check for edits mid-migration
              $args = array (
                'post_type'              => 'zerobs_customer',
                'post_status'            => 'publish',
                'posts_per_page'         => 50000,
                'order'                  => 'ASC',
                'orderby'                => 'post_date',

                  // this is our 'processed' flag
                  'meta_query' => array(
                    array(
                        'key' => 'zbsmig253editlock',
                        'value' => '',
                        'compare' => 'EXISTS'
                        )
                    )

              ); $lockErrors = get_posts( $args );

              if (count($lockErrors) > 0){

                ?><div class="ui divider"></div>
                <p><strong><i class="angle double right icon"></i> <?php echo __('All of your contacts were migrated, but API/other routines tried to update',"zero-bs-crm").' '.count($lockErrors).' '.__('of them while the migration was running. This applies to the following contacts:',"zero-bs-crm"); ?></strong></p><?php
                foreach ($lockErrors as $lockedContactEdited){

                  ?><p><strong><?php echo '#'.$lockedContactEdited->ID; ?></strong> - <?php echo $lockedContactEdited->post_title; ?></p><?php 

                }
                ?><div class="ui divider"></div><?php

              } ?>
          <div class="ui divider"></div>
          <p><strong><i class="angle double right icon"></i> <?php echo __('NOTE: It is essential that you update any',"zero-bs-crm").' <a href="'.$zbs->urls['products'].'" target="_blank">'.__("PRO Extensions","zero-bs-crm").'</a> '.__('you may have installed.',"zero-bs-crm"); ?></strong></p>
          <p><a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['dash'] ); ?>" class="ui button green"><?php _e('Finish',"zero-bs-crm"); ?></a></p>
        </div><?php

        // .... and if there is any PRO exts installed, point to the page
        if (zeroBSCRM_hasPaidExtensionActivated()){

             //pop in a Notify Me Notification here
             $cid = get_current_user_id();

             //use transients...
             $zbsuppy = get_transient('zbs-db2-53-extup');
             if(!$zbsuppy){
               zeroBSCRM_notifyme_insert_notification($cid, -999, -1, 'db2.extupdate.253','funkydbupdate3');
               set_transient( 'zbs-db2-53-extup', 20, 24 * 7 * HOUR_IN_SECONDS );

            }
        }

      break;

    }


    ?>
    <style>
      #zbs-dbmigration {
        margin:3em;margin-top:2em
      }
      #zbs-dbmigration p {
        font-size:1.3em;
      }
    </style>
    <script type="text/javascript">
    // get going!
    var zbsDBMigrationBlocker = false; var zbsDBMigrationState = 'running';
    var zeroBSCRM_sToken = '<?php echo wp_create_nonce( "wpzbs-ajax-nonce" ); ?>';
    var zbsDBMigrationStage = <?php echo $stage; ?>;
    var zbsDBMigrationURL = '<?php echo admin_url('admin.php?page='.$zbs->slugs['migratedb2contacts']); ?>';
    var zbsDBMigrationLang = {

        retrieve: '<?php _e('Retrieving & Migrating 5 contacts...',"zero-bs-crm"); ?>',
        processed: '<?php _e('Successfully migrated contacts...',"zero-bs-crm"); ?>',
        migrated: '<?php _e('Migrated',"zero-bs-crm"); ?>',
        remaining: '<?php _e('Remaining',"zero-bs-crm"); ?>',
        fini: '<?php _e('Contacts successfully migrated!',"zero-bs-crm"); ?>',
        finishing: '<?php _e('Finalising...',"zero-bs-crm"); ?>',
        migrateclose: '<?php _e('Migrating Zero BS CRM Settings and finalising',"zero-bs-crm"); ?>',
        migrateclosedone: '<?php _e('Migration Completed Successfully!',"zero-bs-crm"); ?>',


        // errors
        migratefail: '<?php _e('Migration Error #321',"zero-bs-crm"); ?>',
        migratefail2: '<?php _e('Migration Error #322',"zero-bs-crm"); ?>',
        migratefaildetail: '<?php _e('There was a general error migrating a contact',"zero-bs-crm"); ?>',
        migrateclosefail: '<?php _e('Migration Error #421',"zero-bs-crm"); ?>',
        migrateclosefaildetail: '<?php _e('There was an error closing this migration',"zero-bs-crm"); ?>',
        debugTitle: '<?php _e('Debug:',"zero-bs-crm"); ?>',
        failtryagain: '<?php _e('Please click here to try again',"zero-bs-crm"); ?>',
    };

    jQuery(document).ready(function(){

      if (window.zbsDBMigrationStage === 1){

        // get going :D
        zeroBSCRMJS_poleDBMigration();

        jQuery('#zbs-dbmigration-stop').unbind('click').click(function(){

            // flag paused
            window.zbsDBMigrationState = 'paused';

            // hide/show buttons
            jQuery('#zbs-dbmigration-stop').addClass('hidden');
            jQuery('#zbs-dbmigration-resume').removeClass('hidden');
            jQuery('#zbs-processing-ico').hide();


        });

        jQuery('#zbs-dbmigration-resume').unbind('click').click(function(){

            // flag live
            window.zbsDBMigrationState = 'running';

            // kick it off (if it doesn't run, it'll be because it's already running, but also set a timeout to catch weirdo states with peeps clicking randomly)
            zeroBSCRMJS_poleDBMigration();
            setTimeout(function(){
              zeroBSCRMJS_poleDBMigration();
            },400);

            // hide/show buttons
            jQuery('#zbs-dbmigration-stop').removeClass('hidden');
            jQuery('#zbs-dbmigration-resume').addClass('hidden');
            jQuery('#zbs-processing-ico').show();

        });

      }

    });


    function zeroBSCRMJS_poleDBMigration(){

      console.log('attempting..',[window.zbsDBMigrationBlocker,window.zbsDBMigrationState]);

      // if not blocked
      if (!window.zbsDBMigrationBlocker && window.zbsDBMigrationState == 'running'){

        console.log('poling...');
        // log
        zeroBSCRMJS_addLineToDBMigrationLog(window.zbsDBMigrationLang.retrieve);

          // block
          window.zbsDBMigrationBlocker = true;

          // ACT
          zeroBSCRMJS_fireMigrationLine(function(r){

            // Success

              // log
              zeroBSCRMJS_addLineToDBMigrationLog(window.zbsDBMigrationLang.processed);

              // update percentage bar + notices / just stats
              var completed = -1, todo = -1, total = -1;
              if (typeof r != "undefined" && r != null){
                if (typeof r.complete != "undefined") {
                  var c = parseInt(r.complete);
                  if (c > -1) completed = c;
                }
                if (typeof r.todo != "undefined") {
                  var c = parseInt(r.todo);
                  if (c > -1) todo = c;
                }
                if (typeof r.total != "undefined") {
                  var c = parseInt(r.total);
                  if (c > -1) total = c;
                }

              }
              
              if (completed > -1 && total > -1){

                  if (todo < 0) todo = 0;

                  if (todo == 0 || completed == total){

                    // FINI

                      // stats 
                      jQuery('#zbs-db-processing-stats').html(zbsJS_semanticPercBar(100,'green',completed + ' / ' + total + ' ' + window.zbsDBMigrationLang.migrated));

                      // log
                      zeroBSCRMJS_addLineToDBMigrationLog(window.zbsDBMigrationLang.fini);
                      
                      // unblock
                      window.zbsDBMigrationBlocker = false;

                      // run closing wiz
                      zeroBSCRMJS_closeDBMigration();

                  } else {

                      // carry on!

                      var statsHTML = '';
                      var perc = Math.floor((completed/total)*100);

                      statsHTML = completed + ' / ' + total + ' ' + window.zbsDBMigrationLang.migrated + '&nbsp;&nbsp;(' + todo + ' ' + window.zbsDBMigrationLang.remaining + ')';

                      barHTML = zbsJS_semanticPercBar(perc,'green',statsHTML);

                      // stats 
                      jQuery('#zbs-db-processing-stats').html(barHTML);


                      // PROCEED: 

                        // unblock
                        window.zbsDBMigrationBlocker = false;

                        // setTimeout to respawn
                        setTimeout(function(){

                          // rerun
                          zeroBSCRMJS_poleDBMigration();

                        },100);


                    }

              } else {

                // no numbers passed. What.

                /*
                jQuery('#zbs-db-processing-stats').html('-');

                  // PROCEED: 
                  
                    // unblock
                    window.zbsDBMigrationBlocker = false;

                    // setTimeout to respawn
                    setTimeout(function(){

                      // rerun
                      zeroBSCRMJS_poleDBMigration();

                    },100);

                */

                // attempt to get some debug info
                var debugStr = '';
                if(typeof JSON === 'object' && typeof JSON.stringify === 'function'){
                  debugStr = JSON.stringify(r);
                }


                // Failure
                swal(
                    window.zbsDBMigrationLang.migratefail2,
                    window.zbsDBMigrationLang.migratefaildetail + ',<div style="background: #CCC;padding: 0.5em;margin: 1em 0em 0.2em 0;border-radius: 0.2em;">' + window.zbsDBMigrationLang.debugTitle + ' ' + debugStr + '</div><br /><a href="' + window.zbsDBMigrationURL + '" class="ui button blue middle aligned">' + window.zbsDBMigrationLang.failtryagain + '</a>',
                    'warning'
                );

              }

          },function(r){


            // Failure


                // attempt to get some debug info
                var debugStr = '';
                if(typeof JSON === 'object' && typeof JSON.stringify === 'function'){
                  debugStr = JSON.stringify(r);
                }

                swal(
                    window.zbsDBMigrationLang.migratefail,
                    window.zbsDBMigrationLang.migratefaildetail + ',<div style="background: #CCC;padding: 0.5em;margin: 1em 0em 0.2em 0;border-radius: 0.2em;">' + window.zbsDBMigrationLang.debugTitle + ' ' + debugStr + '</div><br /><a href="' + window.zbsDBMigrationURL + '" class="ui button blue middle aligned">' + window.zbsDBMigrationLang.failtryagain + '</a>',
                    'warning'
                );


          });



      }

    }

    function zeroBSCRMJS_closeDBMigration(){

      //console.log('attempting close..',[window.zbsDBMigrationBlocker,window.zbsDBMigrationState]);

      // if not blocked
      if (!window.zbsDBMigrationBlocker && window.zbsDBMigrationState == 'running'){

        //console.log('closing...');

        // stats 
        jQuery('#zbs-db-processing-stats').html(zbsJS_semanticPercBar(0,'green',window.zbsDBMigrationLang.finishing));

        // log
        zeroBSCRMJS_addLineToDBMigrationLog(window.zbsDBMigrationLang.migrateclose);

          // block
          window.zbsDBMigrationBlocker = true;

          // ACT
          zeroBSCRMJS_fireMigrationClosing(function(r){

            // Success

              // stats 
              jQuery('#zbs-db-processing-stats').html(zbsJS_semanticPercBar(100,'green',window.zbsDBMigrationLang.migrateclosedone));

              // log
              zeroBSCRMJS_addLineToDBMigrationLog(window.zbsDBMigrationLang.migrateclosedone);

              // all good if here, note any lockerrors and show fini page
              // actually, show lock errors on next page
              window.location = window.zbsDBMigrationURL + '&fini=2';

              // in case that doesn't work, clear these + add button
              jQuery('#zbs-db-process-actions').html('<a href="' + window.zbsDBMigrationURL + '&fini=2" class="ui button large green">Finish</a>');


          },function(r){

            // Failure

            var errStr = JSON.stringify(r);

                swal(
                    window.zbsDBMigrationLang.migrateclosefail,
                    window.zbsDBMigrationLang.migrateclosefaildetail + ',<br /><a href="' + window.zbsDBMigrationURL + '" class="ui button blue middle aligned">' + window.zbsDBMigrationLang.failtryagain + '</a><br />Error:' + errStr,
                    'warning'
                );


          });



      }

    }

    function zeroBSCRMJS_fireMigrationLine(cb,errcb){

       var data = {
              'action': 'zbs_dbmigration253',
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

                if (typeof cb == "function") cb(response);

              },
              error: function(response){

                console.error(response);

                if (typeof errcb == "function") errcb(response);

              }

            });
    }

    function zeroBSCRMJS_fireMigrationClosing(cb,errcb){

       var data = {
              'action': 'zbs_dbmigration253close',
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

                if (typeof cb == "function") cb(response);

              },
              error: function(response){

                console.error(response);

                if (typeof errcb == "function") errcb(response);

              }

            });
    }
    function zeroBSCRMJS_addLineToDBMigrationLog(str){

      // any to take off start>
      if (jQuery("#zbs-db-processing p").length > 5) jQuery('#zbs-db-processing p').first().remove();

      jQuery('#zbs-db-processing').append('<p><strong><i class="angle double right icon"></i> ' + str + '</strong></p>');

    }

    // save this, not req.
    function zeroBSCRM_migrationGoFullScreen(){

        jQuery('#wpcontent').css({"margin-left": "0px","margin-top": "-32px"});
            jQuery(this).removeClass('menu-open'); //not req..addClass('menu-closed');
            jQuery("#wpadminbar, #adminmenuback, #adminmenuwrap").hide();

    }
    </script>
    </div><?php


  }




  // v3.0 Migration page
  function zeroBSCRM_page_migrationDB3() {
    
    global $zbs;

    if (!current_user_can( 'admin_zerobs_manage_options' )) exit('Goodbye');

    // check status
    $stage = 0;
    if (isset($_GET['gomode'])) $stage = 1;
    if (isset($_GET['opnow'])) $stage = 2;
    if (isset($_GET['fini'])) $stage = 3;

    $finiFlag = get_option('zbs_db_migration_300');
    if ($finiFlag > 0){

      // should be fini
      $stage = 3;

    }

    // ================================================================================
    // =========== Release Candidate has a 'Give Feedback' Button =====================
    // ================================================================================

		    // if not whitelabel, show #migrationfeedback
		    if (!zeroBSCRM_isWL()){

		            // only ok because not WL
		            $logo = '<img src="'.ZEROBSCRM_URL.'i/zero-bs-crm-admin-logo-clear.png" class="zbs-feedback-logo" alt="" />';

		            $obj = array(
		                            'area' => 'v3migration',
		                            'email' => zeroBSCRM_currentUser_email(),
		                            'logo' => $logo,

		                            'givefeedback' => zeroBSCRM_slashOut(__('Give Migratiaon Feedback','zero-bs-crm'),true),
		                            'title' => zeroBSCRM_slashOut(__('Give v3 Migration Feedback:','zero-bs-crm'),true),
		                            'desc' => zeroBSCRM_slashOut(__('Got feedback on this migration routine? Please leave your comment here with as much specific detail as possible.','zero-bs-crm'),true),
		                            'commentplaceholder' => zeroBSCRM_slashOut(__('Please type your comment giving as much detail as possible...','zero-bs-crm'),true),
		                            'emaillabel' => zeroBSCRM_slashOut(__('Email:','zero-bs-crm'),true),
		                            'emailplaceholder' => zeroBSCRM_slashOut(__('Your Email...','zero-bs-crm'),true),
		                            'sendbutton' => zeroBSCRM_slashOut(__('Send Feedback','zero-bs-crm'),true),
		                            'cancelbutton' => zeroBSCRM_slashOut(__('Cancel','zero-bs-crm'),true),
		                            'sent' => zeroBSCRM_slashOut(__('Thank you for giving feedback, it really helps us move Zero BS CRM forwards. We appreciate you as a member of our entrepreneurship community.','zero-bs-crm'),true),
		                            'fail' => zeroBSCRM_slashOut(__('Failed sending feedback! Please try again, or use a support ticket:','zero-bs-crm').' <a href="https://zerobscrm.com/kb/submit-a-ticket/" target="_blank">'.__('Contact support','zero-bs-crm').'</a>',true),
		                            'incdata' => zeroBSCRM_slashOut(__('Note: Sending feedback here also sends us your site url.','zero-bs-crm'),true),

		                        );

		            // out with you!
		            ?><script type="text/javascript">var zbsBetaFeedback = <?php echo json_encode($obj); ?>;</script><?php

		    }

    // ================================================================================
    // ============== / Release Candidate 'Give Feedback' Button ======================
    // ================================================================================


    ?><div id="zbs-dbmigration"><?php

    switch ($stage){

      // not yet started
      case 0:

        ?><div class="ui very padded segment">
          <h1 class="ui header"><i class="database icon"></i> <?php _e('Ready for v3.0?',"zero-bs-crm"); ?></h1>
          <p><?php _e('An essential database update is required before you can continue to use Zero BS CRM. This update will significantly improve the performance of ZBS CRM.',"zero-bs-crm"); ?></p>
          <div class="ui divider"></div>
          <p><?php _e('Your data will be migrated into our new v3.0 architecture:',"zero-bs-crm"); ?></p>
          <p><strong><i class="check icon"></i> <?php _e('Up to 5x faster, throughout',"zero-bs-crm"); ?></strong></p>        
          <p><strong><i class="check icon"></i> <?php _e('Seperated from your WordPress data',"zero-bs-crm"); ?></strong></p>
          <p><strong><i class="check icon"></i> <?php _e('Groundwork for API v3.0, Custom Objects, and more!',"zero-bs-crm"); ?></strong></p>
          <div class="ui divider"></div>
        <?php 

        	// catch:
        	// users who have EXTENSIONS installed (even if not activated), but NO VALID LICENSE
        	// ... and warn "may not be able to use out-of-date exts after this"
        	$hasExtensionsInstalled = false; $extensions = zeroBSCRM_installedProExt(); if (is_array($extensions) && count($extensions) > 0) $hasExtensionsInstalled = true;

        	$hasValidLicense = false; $licenseKeyArr = zeroBSCRM_getSetting('license_key');
        	 if (!zeroBSCRM_isLocal(true))
        	 	if (isset($licenseKeyArr['validity'])) $hasValidLicense = ($licenseKeyArr['validity'] === 'true');

        	 // if unlicensed exts
        	 if ($hasExtensionsInstalled && !$hasValidLicense){

        	 	// Warn the user
        	 	echo zeroBSCRM_UI2_messageHTML('warning',__('Extension Licensing Warning','zero-bs-crm'),__('You have unlicensed extensions installed. These may no longer work after this migration (without an update). Please add a valid license key to avoid any loss of functionality.','zero-bs-crm'),'warning triangle','zbs-unlicensed-ext-warning');


        	 } elseif ($hasExtensionsInstalled && $hasValidLicense){

        	 	// licensed exts still prompt
        	 	echo zeroBSCRM_UI2_messageHTML('info',__('Extension Notice','zero-bs-crm'),__('You have CRM extensions installed. These may require an update after this migration wizard.','zero-bs-crm').'<br /><a href="'.$zbs->urls['db3migrateexts'].'" target="_blank" class="ui mini button">'.__('Read More','zero-bs-crm').'</a>','certificate','zbs-licensed-ext-warning');
        	 	
        	 }



        ?>
          <p style="text-align:center">
              <a href="<?php echo $zbs->urls['db3migrate']; ?>" target="_blank" class="ui huge button basic"><?php _e('Read Guide',"zero-bs-crm"); ?></a>
              <a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['migratedal3'].'&gomode=1' ); ?>" class="ui huge button green"><?php _e('Get Started',"zero-bs-crm"); ?></a>
            </p>
        </div><?php

      break;

      // Check backed up etc.
      case 1:

        ?><div class="ui very padded segment" id="zbs-migration-checkbox-wrap">
          <h1 class="ui header"><i class="database icon"></i> <?php _e('Upgrading to v3.0',"zero-bs-crm"); ?></h1>
          <p><?php _e('Before we get started with your v3.0 migration, we need to check you\'re good to go.',"zero-bs-crm"); ?></p>
          <div class="ui divider"></div>
          <p><?php _e('Please back up your database and files now! Then, when you are ready:',"zero-bs-crm"); ?></p>
			<div class="ui checkbox" style="font-size:1.3em">
				<input type="checkbox" class="zbs-db3-migration-check" id="zbs-db3-migration-check-1" value="1" />
				<label for="zbs-db3-migration-check-1" style="font-weight:800"><?php _e('I have backed up my database and files',"zero-bs-crm"); ?></label>
			</div>
			<div style="clear:both"></div>
			<div class="ui checkbox" style="font-size:1.3em">
				<input type="checkbox" class="zbs-db3-migration-check" id="zbs-db3-migration-check-2" value="1" />
				<label for="zbs-db3-migration-check-2" style="font-weight:800"><?php _e('I am aware this upgrade will temporarily disable my crm extensions',"zero-bs-crm"); ?></label>
			</div>   
			<div style="clear:both"></div>
			<div class="ui checkbox" style="font-size:1.3em">
				<input type="checkbox" class="zbs-db3-migration-check" id="zbs-db3-migration-check-3" value="1" />
				<label for="zbs-db3-migration-check-3" style="font-weight:800"><?php _e('I\'ve got enough time to let this finish now (1-20 minutes)',"zero-bs-crm"); ?></label>
			</div>
          <div class="ui compact error message" id="zbs-db3-migration-check-err" style="display:none"><p><?php _e('You have to agree to these before you can start the migration!','zero-bs-crm'); ?></div>
          <div class="ui divider"></div>
          <p style="text-align:center">
              <a href="<?php echo $zbs->urls['db3migrate']; ?>" target="_blank" class="ui huge button basic"><?php _e('Read Guide',"zero-bs-crm"); ?></a>
              <button id="zbs-start-v3-migration" type="button" class="ui huge button green disabled"><?php _e('Start Upgrade',"zero-bs-crm"); ?></button>
            </p>
        </div><?php

      break;

      // started, to continue
      case 2:

        ?><div class="ui very padded segment">
          <div class="ui active inline loader right floated" id="zbs-processing-ico"></div>
          <h1 class="ui header" style="margin-top:0;"><i class="database icon"></i> v3.0 <?php _e('Database Upgrade: Processing',"zero-bs-crm"); ?></h1>
          <div class="ui divider"></div>
          <div id="zbs-db-processing">
            <p><strong><i class="angle double right icon"></i> <?php _e('Beginning Database Upgrade...',"zero-bs-crm"); ?></strong></p>
          </div>
          <div class="ui divider"></div>
            <div id="zbs-db-processing-stats" class="ui segment"></div>
          <div class="ui divider"></div>
          <p style="text-align:center" id="zbs-db-process-actions">
              <a href="<?php echo $zbs->urls['db3migrate']; ?>" target="_blank" class="ui large button basic"><?php _e('Read Guide',"zero-bs-crm"); ?></a>
              <button class="ui large button orange" id="zbs-dbmigration-stop"><i class="pause circle icon"></i><?php _e('Pause',"zero-bs-crm"); ?></button>
              <button class="ui large button green hidden" id="zbs-dbmigration-resume"><i class="play circle icon"></i><?php _e('Resume',"zero-bs-crm"); ?></button>
          </p>
        </div><?php

      break;

      // fini
      case 3:

        ?><div class="ui very padded segment">
          <h1 class="ui header green"><i class="database icon"></i> v3.0 <?php _e('Database Upgrade Complete',"zero-bs-crm"); ?></h1>
          <p><?php _e('Your database has been successfully upgraded, thank you for using Zero BS CRM!',"zero-bs-crm"); ?></p>
          <div class="ui divider"></div>
          <img src="<?php echo $zbs->urls['extimgrepo']; ?>__zbs-completed.png" alt="Completed Migration" align="right" />
          <p><strong><i class="angle double right icon"></i> <?php _e('Your data is now fully migrated to the new architecture',"zero-bs-crm"); ?></strong></p>
          <?php
          	
          	// check for any migration 'errors' + also expose here.
          	$errors = get_option('zbs_db_migration_300_errstack', array());

          	// FURTHER - invoice discount discrepency check:
	   			// <v3.0 we calculated discounts on total, not on net, 
	   			// ... this means that v3.0 calculating on net will mean some invoice values will be changed
	   			// ... because v3.0 autocalcs invoices on saving.
	   			// We suspect the number of users using discounts to be an edge case,
	   			// ... so for Release candidate we'll just LOG these differences to guage the net change
	            // ... because we need a full invoice obj layer to recalc the invoice, we have to save as meta
	            // ... and then do this comparison as part of the migration closure jobs (to catch discrepencies + notify)

          		// only fire once, though
          		$migrationInvoicesVerified = get_option('zbs_db_migration_300_invverify', false);

          		if (!$migrationInvoicesVerified){

	                // compare the new total to the <v3.0 total and log any discrepencies
	          		$invoiceDiscrepencies = zeroBSCRM_migration_3000_verifyInvoiceTotals();

	          		if (count($invoiceDiscrepencies) > 0){

	          			// add any discrepencies to err stack    
	          			foreach ($invoiceDiscrepencies as $invID => $discrepancy){

	          				$errMsg = __('Invoice','zero-bs-crm').' '.$discrepancy['id'].' (#'.$invID.') '.__('has been recalculated and resulted in a value discrepancy:','zero-bs-crm');
	          				$errMsg .= '<br />'.__('Original Invoice Value:','zero-bs-crm').' '.$discrepancy['original'];
	          				$errMsg .= '<br />'.__('Recalculated Invoice Value:','zero-bs-crm').' '.$discrepancy['new'];

	    					zeroBSCRM_migration_addErrToStack(array(702,$errMsg),'zbs_db_migration_300_errstack');

	    				}

	    				// reget
	          			$errors = get_option('zbs_db_migration_300_errstack', array());

	    			}

	    			update_option('zbs_db_migration_300_invverify',time());

	    		}

          	if (is_array($errors) && count($errors) > 0){

                ?><div class="ui divider"></div>
                <?php 

                // this is a clone of what gets sent to them by email, but reusing the html gen here

					// build report
					$bodyStr = '<h2>'.__('Migration Completion Report','zero-bs-crm').'</h2>';
					$bodyStr .= '<p>'.__('Unfortunately there were some migration errors, which are shown below. The error messages should explain any conflicts found when merging, (this has also been emailed to you for your records).','zero-bs-crm').' '.__('Please visit the migration support page','zero-bs-crm').' <a href="'.$zbs->urls['db3migrate'].'" target="_blank">'.__('here','zero-bs-crm').'</a> '.__('if you require any further information.','zero-bs-crm').'</p>';
					$bodyStr .= '<p>'.__('You can review these errors at a later time by visiting CRM Settings -> System Status -> Migration Errors (at bottom of page) or by bookmarking','zero-bs-crm').' <a href="'.zeroBSCRM_getAdminURL($zbs->slugs['systemstatus']).'&v3migrationlog=1" target="_blank">'.__('this link','zero-bs-crm').'</a>.</p>';
					$bodyStr .= '<div style="position: relative;background: #FFFFFF;box-shadow: 0px 1px 2px 0 rgba(34,36,38,0.15);margin: 1rem 0em;padding: 1em 1em;border-radius: 0.28571429rem;border: 1px solid rgba(34,36,38,0.15);"><h3>'.__('Non-critical Errors:','zero-bs-crm').'</h3>';

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

					echo $bodyStr;

          	} ?>
          <div class="ui divider"></div>
          <p><strong><i class="angle double right icon"></i> <?php echo __('NOTE: It is essential that you update any',"zero-bs-crm").' <a href="'.$zbs->urls['products'].'" target="_blank">'.__("PRO Extensions","zero-bs-crm").'</a> '.__('you may have installed.',"zero-bs-crm"); ?></strong></p>
          <p><a href="<?php echo admin_url('admin.php?page='.$zbs->slugs['dash'] ); ?>" class="ui button green"><?php _e('Finish',"zero-bs-crm"); ?></a></p>
        </div><?php

        // .... and if there is any PRO exts installed, point to the page
        if (zeroBSCRM_hasPaidExtensionActivated()){

             //pop in a Notify Me Notification here
             $cid = get_current_user_id();

             //use transients...
             $zbsuppy = get_transient('zbs-db3-300-extup');
             if(!$zbsuppy){
               zeroBSCRM_notifyme_insert_notification($cid, -999, -1, 'db3.extupdate.300','funkydbupdate30');
               set_transient( 'zbs-db3-300-extup', 20, 24 * 7 * HOUR_IN_SECONDS );

            }
        }

      break;

    }


    ?>
    <style>
      #zbs-dbmigration {
        margin:3em;margin-top:2em
      }
      #zbs-dbmigration p {
        font-size:1.3em;
      }
    </style>
    <script type="text/javascript">

    // init state + lang
    var zbsDBMigrationBlocker = false; var zbsDBMigrationState = 'running';
    var zeroBSCRM_sToken = '<?php echo wp_create_nonce( "wpzbs-ajax-nonce" ); ?>';
    var zbsDBMigrationStage = <?php echo $stage; ?>;
    var zbsDBMigrationURL = '<?php echo admin_url('admin.php?page='.$zbs->slugs['migratedal3']); ?>';
    var zbsDBMigrationState = 'new'; // new->(opened)->operating->(closed)->finished
    var zbsDBMigrationLang = {

        opening: '<?php _e('Computing current Zero BS CRM Setup...',"zero-bs-crm"); ?>',
        opening2: '<?php _e('Temporarily disabling extensions...',"zero-bs-crm"); ?>',
        openingfini: '<?php _e('Pre-migration checks & actions complete...',"zero-bs-crm"); ?>',
        retrieve: '<?php _e('Retrieving & Migrating objects...',"zero-bs-crm"); ?>',
        processed: '<?php _e('Successfully migrated objects...',"zero-bs-crm"); ?>',
        migrated: '<?php _e('Migrated',"zero-bs-crm"); ?>',
        remaining: '<?php _e('Remaining',"zero-bs-crm"); ?>',
        fini: '<?php _e('Data successfully migrated!',"zero-bs-crm"); ?>',
        finishing: '<?php _e('Finalising...',"zero-bs-crm"); ?>',
        migrateclose: '<?php _e('Finalising...',"zero-bs-crm"); ?>',
        migrateclosedone: '<?php _e('Migration Completed Successfully!',"zero-bs-crm"); ?>',


        // errors
        migrateopenfail: '<?php _e('Migration Error #703',"zero-bs-crm"); ?>',
        migrateopenfaildetail: '<?php _e('There was an error beginning this migration',"zero-bs-crm"); ?>',
        migratefail: '<?php _e('Migration Error #704',"zero-bs-crm"); ?>',
        migratefaildetail: '<?php _e('There was a general error migrating an object',"zero-bs-crm"); ?>',
        migrateclosefail: '<?php _e('Migration Error #711',"zero-bs-crm"); ?>',
        migrateclosefaildetail: '<?php _e('There was an error closing this migration',"zero-bs-crm"); ?>',
        debugTitle: '<?php _e('Debug:',"zero-bs-crm"); ?>',
        failtryagain: '<?php _e('Please click here to try again',"zero-bs-crm"); ?>',


        timeoutfail: '<?php _e('Your server continues to timeout despite lowering the bar. Please contact support',"zero-bs-crm"); ?>',
    };
    var zbsDBMigrationTimeoutCount = 0;

    jQuery(document).ready(function(){

    	// STAGE 1 Binds: Check checkboxes are checked
    	// ===================================================
    	if (window.zbsDBMigrationStage === 1){

    		// as check them, enable
    		jQuery('#zbs-migration-checkbox-wrap .checkbox').each(function(ind,ele){

    			jQuery(ele).checkbox({
				    onChecked: function() {

		    			zeroBSCRMJS_checkChecksChanged();

				    },
				    onUnchecked: function() {

		    			zeroBSCRMJS_checkChecksChanged();
				    }
				  });    		

    		});

    		// bind submit
    		jQuery('#zbs-start-v3-migration').unbind('click').click(function(){
		
    			// checked?
				var checked = zeroBSCRMJS_checkChecks();				

    			if (checked == 3){

    				window.location = window.zbsDBMigrationURL + '&opnow=1';

    			} else {

    				jQuery('#zbs-db3-migration-check-err').show();

    			}


    		});

    	}
    	// ===================================================
    	// / STAGE 1 Binds


    	// STAGE 2 Binds & Init
    	// ===================================================
		if (window.zbsDBMigrationStage === 2){

			// initialise migration:
			if (window.zbsDBMigrationState == 'new') zeroBSCRMJS_migratev3_openDBMigration();
			if (window.zbsDBMigrationState == 'opened') zeroBSCRMJS_migratev3_poleDBMigration();

			// bind stop
			jQuery('#zbs-dbmigration-stop').unbind('click').click(function(){

			    // flag paused
			    window.zbsDBMigrationState = 'paused';

			    // hide/show buttons
			    jQuery('#zbs-dbmigration-stop').addClass('hidden');
			    jQuery('#zbs-dbmigration-resume').removeClass('hidden');
			    jQuery('#zbs-processing-ico').hide();


			});

			// bind resume
			jQuery('#zbs-dbmigration-resume').unbind('click').click(function(){

			    // flag live
			    window.zbsDBMigrationState = 'running';

			    // kick it off (if it doesn't run, it'll be because it's already running, but also set a timeout to catch weirdo states with peeps clicking randomly)            
			    if (window.zbsDBMigrationState == 'new') zeroBSCRMJS_migratev3_openDBMigration();
			    if (window.zbsDBMigrationState == 'opened') zeroBSCRMJS_migratev3_poleDBMigration();

			    setTimeout(function(){
			      
			        if (window.zbsDBMigrationState == 'new') zeroBSCRMJS_migratev3_openDBMigration();
			        if (window.zbsDBMigrationState == 'opened') zeroBSCRMJS_migratev3_poleDBMigration();

			    },400);

			    // hide/show buttons
			    jQuery('#zbs-dbmigration-stop').removeClass('hidden');
			    jQuery('#zbs-dbmigration-resume').addClass('hidden');
			    jQuery('#zbs-processing-ico').show();

			});

		}
    	// ===================================================
    	// / STAGE 2

    });

	// STAGE 1 Helpers: Check checkboxes are checked
	// ===================================================
	function zeroBSCRMJS_checkChecksChanged(){

		// checked?
		var checked = zeroBSCRMJS_checkChecks();				

		if (checked == 3)
			jQuery('#zbs-start-v3-migration').removeClass('disabled');
		else
			jQuery('#zbs-start-v3-migration').addClass('disabled');

	}

	// count successfully checked
	function zeroBSCRMJS_checkChecks(){

		// checked?
		var checked = 0;
		jQuery('#zbs-db3-migration-check-err').hide();

		// cycle through em
		jQuery('.zbs-db3-migration-check').each(function(ind,ele){

			if (jQuery(ele).is(':checked')){

				checked++;

			}

		});

		return checked;
	}
	// ===================================================
	// / STAGE 1 Helpers


	// STAGE 2 Helpers: Migration
	// ===================================================
	/*
		2.1 - "Open" migration:
		AJAX:
				- Log which ext active
				- Disable extensions
				- NO EXT to turn back on (hard block) until 3.0 migrated
				- each ext needs to be at least ver recorded in core (we make a list) before re-activate

	*/
    function zeroBSCRMJS_migratev3_openDBMigration(){

      console.log('Attempting v3 Migration open..',[window.zbsDBMigrationBlocker,window.zbsDBMigrationState]);

      // if not blocked
      if (window.zbsDBMigrationState == 'new' && !window.zbsDBMigrationBlocker && window.zbsDBMigrationState !== 'running'){

        // stats 
        jQuery('#zbs-db-processing-stats').html(zbsJS_semanticPercBar(0,'green',window.zbsDBMigrationLang.opening));

        // log
        zeroBSCRMJS_addLineToDBMigrationLog(window.zbsDBMigrationLang.opening2);

          // block
          window.zbsDBMigrationBlocker = true;

          // ACT
          zeroBSCRMJS_migratev3_fireMigrationOpening(function(r){

            // Success

              // stats 
              jQuery('#zbs-db-processing-stats').html(zbsJS_semanticPercBar(10,'green',window.zbsDBMigrationLang.openingfini));

              // log
              zeroBSCRMJS_addLineToDBMigrationLog(window.zbsDBMigrationLang.openingfini);

              // log statechange + kick off polling
		      window.zbsDBMigrationState = 'opened'; 
          	  window.zbsDBMigrationBlocker = false;
		      zeroBSCRMJS_migratev3_poleDBMigration();


          },function(r){

            // Failure
            var errStr = JSON.stringify(result);

                swal(
                    window.zbsDBMigrationLang.migrateopenfail,
                    window.zbsDBMigrationLang.migrateopenfaildetail + ',<br /><a href="' + window.zbsDBMigrationURL + '" class="ui button blue middle aligned">' + window.zbsDBMigrationLang.failtryagain + '</a><br />Error:' + errStr,
                    'warning'
                );


          });

      }

      return false;

    }


	// 2) Migrate
    function zeroBSCRMJS_migratev3_poleDBMigration(){

      console.log('Attempting v3 Migration..',[window.zbsDBMigrationBlocker,window.zbsDBMigrationState]);

      // if not blocked
      if (window.zbsDBMigrationState == 'opened' && !window.zbsDBMigrationBlocker && window.zbsDBMigrationState !== 'running'){

        console.log('polling...');
        // log
        zeroBSCRMJS_addLineToDBMigrationLog(window.zbsDBMigrationLang.retrieve);

          // block
          window.zbsDBMigrationBlocker = true;

          // ACT
          zeroBSCRMJS_migratev3_fireMigrationLine(function(r){

            // Success

              // log
              zeroBSCRMJS_addLineToDBMigrationLog(window.zbsDBMigrationLang.processed);

              // update percentage bar + notices / just stats
              var completed = -1, todo = -1, total = -1;
              if (typeof r != "undefined" && r != null){
                if (typeof r.complete != "undefined") {
                  var c = parseInt(r.complete);
                  if (c > -1) completed = c;
                }
                if (typeof r.todo != "undefined") {
                  var c = parseInt(r.todo);
                  if (c > -1) todo = c;
                }
                if (typeof r.total != "undefined") {
                  var c = parseInt(r.total);
                  if (c > -1) total = c;
                }

              }
              
              if (completed > -1 && total > -1){

                  if (todo < 0) todo = 0;

                  if (todo == 0 || completed == total){

                    // FINI

                      // stats 
                      jQuery('#zbs-db-processing-stats').html(zbsJS_semanticPercBar(100,'green',completed + ' / ' + total + ' ' + window.zbsDBMigrationLang.migrated));

                      // log
                      zeroBSCRMJS_addLineToDBMigrationLog(window.zbsDBMigrationLang.fini);
                      
                      // unblock
				      window.zbsDBMigrationState = 'migrated'; 
		          	  window.zbsDBMigrationBlocker = false;

                      // run closing wiz
                      zeroBSCRMJS_closeDBMigration();

                  } else {

                      // carry on!

                      var statsHTML = '';
                      var perc = Math.floor((completed/total)*100);

                      statsHTML = completed + ' / ' + total + ' ' + window.zbsDBMigrationLang.migrated + '&nbsp;&nbsp;(' + todo + ' ' + window.zbsDBMigrationLang.remaining + ')';

                      barHTML = zbsJS_semanticPercBar(perc,'green',statsHTML);

                      // stats 
                      jQuery('#zbs-db-processing-stats').html(barHTML);


                      // PROCEED: 

                        // unblock
                        window.zbsDBMigrationBlocker = false;

                        // setTimeout to respawn
                        setTimeout(function(){

                          // rerun
                          zeroBSCRMJS_migratev3_poleDBMigration();

                        },100);


                    }

              } else {

                // no numbers passed. What.

                /*
                jQuery('#zbs-db-processing-stats').html('-');

                  // PROCEED: 
                  
                    // unblock
                    window.zbsDBMigrationBlocker = false;

                    // setTimeout to respawn
                    setTimeout(function(){

                      // rerun
                      zeroBSCRMJS_poleDBMigration();

                    },100);

                */

                // attempt to get some debug info
                var debugStr = '';
                if(typeof JSON === 'object' && typeof JSON.stringify === 'function'){
                  debugStr = JSON.stringify(r);
                }


                // Failure
                swal(
                    window.zbsDBMigrationLang.migratefail2,
                    window.zbsDBMigrationLang.migratefaildetail + ',<div style="background: #CCC;padding: 0.5em;margin: 1em 0em 0.2em 0;border-radius: 0.2em;">' + window.zbsDBMigrationLang.debugTitle + ' ' + debugStr + '</div><br /><a href="' + window.zbsDBMigrationURL + '" class="ui button blue middle aligned">' + window.zbsDBMigrationLang.failtryagain + '</a>',
                    'warning'
                );

              }

          },function(r){

            // Failure
            var sendDebug = true;

            	// is this from timeout?
            	if (typeof r.statusText !== "undefined" && r.statusText == "timeout"){

            		// ... looks like it, 
            		console.log('Migration caused timeout #' + window.zbsDBMigrationTimeoutCount);

			  		// .. unless times out 3 times, then notice
			  		if (window.zbsDBMigrationTimeoutCount < 3){

			  			// increment
			  			window.zbsDBMigrationTimeoutCount++;			  	

	            		// set a 'slow down' mode and restart the poling            		
						var data = {
						  'action': 'zbs_dbmigration300timeoutflag',
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

							  	// stop debug
							  	sendDebug = false;		

						  		// restart the pole, running 5/page instead of 20/page

			                        // unblock
			                        window.zbsDBMigrationBlocker = false;

			                        // setTimeout to respawn
			                        setTimeout(function(){

			                          // rerun
			                          zeroBSCRMJS_migratev3_poleDBMigration();

			                        },100);


						  },
						  error: function(response){

							  	// leave for debug
							  	sendDebug = true;

						  }

						});

					} else {

					  	// stop debug
					  	sendDebug = false;

						// has tried restart 3 times! 
						var debugStr = window.zbsDBMigrationLang.timeoutfail;

		                swal(
		                    window.zbsDBMigrationLang.migratefail,
		                    window.zbsDBMigrationLang.migratefaildetail + ',<div style="background: #CCC;padding: 0.5em;margin: 1em 0em 0.2em 0;border-radius: 0.2em;">' + window.zbsDBMigrationLang.debugTitle + ' ' + debugStr + '</div>',
		                    'warning'
		                );


					}

            	}            

            	if (sendDebug){

	                // attempt to get some debug info
	                var debugStr = '';
	                if(typeof JSON === 'object' && typeof JSON.stringify === 'function'){
	                  debugStr = JSON.stringify(r);
	                }

	                swal(
	                    window.zbsDBMigrationLang.migratefail,
	                    window.zbsDBMigrationLang.migratefaildetail + ',<div style="background: #CCC;padding: 0.5em;margin: 1em 0em 0.2em 0;border-radius: 0.2em;">' + window.zbsDBMigrationLang.debugTitle + ' ' + debugStr + '</div><br /><a href="' + window.zbsDBMigrationURL + '" class="ui button blue middle aligned">' + window.zbsDBMigrationLang.failtryagain + '</a>',
	                    'warning'
	                );

	            }


          });

      } // / if no blocker + running

    }

    // 3) Close
    function zeroBSCRMJS_closeDBMigration(){

      console.log('Attempting v3 Migration close..',[window.zbsDBMigrationBlocker,window.zbsDBMigrationState]);

      // if not blocked
      if (!window.zbsDBMigrationBlocker && window.zbsDBMigrationState !== 'running'){

        console.log('closing...');

        // log
        zeroBSCRMJS_addLineToDBMigrationLog(window.zbsDBMigrationLang.migrateclose);

          // block
          window.zbsDBMigrationBlocker = true;

          // ACT
          zeroBSCRMJS_migratev3_fireMigrationClosing(function(r){

            // Success

              // stats 
              jQuery('#zbs-db-processing-stats').html(zbsJS_semanticPercBar(100,'green',window.zbsDBMigrationLang.migrateclosedone));

              // log
              zeroBSCRMJS_addLineToDBMigrationLog(window.zbsDBMigrationLang.migrateclosedone);

              // all good if here, note any lockerrors and show fini page
              // actually, show lock errors on next page
              window.location = window.zbsDBMigrationURL + '&fini=1';

              // in case that doesn't work, clear these + add button
              jQuery('#zbs-db-process-actions').html('<a href="' + window.zbsDBMigrationURL + '&fini=1" class="ui button large green">Finish</a>');


          },function(r){

            // Failure

            var errStr = JSON.stringify(r);

                swal(
                    window.zbsDBMigrationLang.migrateclosefail,
                    window.zbsDBMigrationLang.migrateclosefaildetail + ',<br /><a href="' + window.zbsDBMigrationURL + '" class="ui button blue middle aligned">' + window.zbsDBMigrationLang.failtryagain + '</a><br />Error:' + errStr,
                    'warning'
                );


          });



      }

    }

    function zeroBSCRMJS_migratev3_fireMigrationOpening(cb,errcb){

       var data = {
              'action': 'zbs_dbmigration300open',
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

                if (typeof cb == "function") cb(response);

              },
              error: function(response){

                console.error(response);

                if (typeof errcb == "function") errcb(response);

              }

            });
    }

    function zeroBSCRMJS_migratev3_fireMigrationLine(cb,errcb){

       var data = {
              'action': 'zbs_dbmigration300',
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

                console.log('Migration Response..',response);

                if (typeof cb == "function") cb(response);

              },
              error: function(response){

                console.error('Migration Error..',response);

                if (typeof errcb == "function") errcb(response);

              }

            });
    }

    function zeroBSCRMJS_migratev3_fireMigrationClosing(cb,errcb){

       var data = {
              'action': 'zbs_dbmigration300close',
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

                if (typeof cb == "function") cb(response);

              },
              error: function(response){

                console.error(response);

                if (typeof errcb == "function") errcb(response);

              }

            });
    }
    function zeroBSCRMJS_addLineToDBMigrationLog(str){

      // any to take off start>
      if (jQuery("#zbs-db-processing p").length > 5) jQuery('#zbs-db-processing p').first().remove();

      jQuery('#zbs-db-processing').append('<p><strong><i class="angle double right icon"></i> ' + str + '</strong></p>');

    }

    // save this, not req.
    function zeroBSCRM_migrationGoFullScreen(){

        jQuery('#wpcontent').css({"margin-left": "0px","margin-top": "-32px"});
            jQuery(this).removeClass('menu-open'); //not req..addClass('menu-closed');
            jQuery("#wpadminbar, #adminmenuback, #adminmenuwrap").hide();

    }
	// ===================================================
	// / STAGE 2 Helpers
    </script>
    </div><?php



     /*

		Migration Workflow:
			
				1. UI leading up to here (notification/locks -> migration wizard -> confirmed has backed up + aok to go)
					//- Lock metaboxes while executing
				2. Page loads + auto starts process:
					2.1 - "Open" migration:
							- Log which ext active
							- move custom field settings from settings -> DB
							- Disable extensions
							- NO EXT to turn back on (hard block) until 3.0 migrated
							- each ext needs to be at least ver recorded in core (we make a list) before re-activate							
					2.2 - "Process" migration:
							- Telemetry
							- Fields global array?
							- Custom Field values for objs as we go
							- Backup (as we go):
							- Companies -> DAL
							- Quotes -> DAL
							- Quote Templates -> DAL
							- Invoices -> DAL
							- Transactions -> DAL
							- Lineitems -> DAL
							- Forms -> DAL
							- Events -> DAL
							- Logs? -> DAL?
							- Tags? -> DAL?
							- MIGRATE settings to settings not wp options (in dal2.helpers.php)
								- return (int)get_option('quoteindx',$defaultStartingQuoteID)+1;
								- update_option('zbs_crm_api_key', $api_key);
								- inv + quote hashes move from meta-based (zeroBSCRM_GenerateHashForPost) to table-based 
					2.3 - "Close" migration:
							- Switch over to 3.0
							- Turn on all ext approved for DAL3 (needs a switch in them somehow)
					2.4 - "Fini" dialog:
							- X extensions have been reactivated, Y needs updated versions, 
							- Offer "clear data backup produced by migration?" -> delete db backup tables
							- "Whats new in v3.0" page / kb article
							- Thanks for migrating, next actions (follow? coming soon? buy (if not bought?))


				X. If any point errors, make easy to copy + paste to us (make RC feedback easy, loop short)


		
    */ 


  }
/* ======================================================
   / MIGRATION Pages
   ====================================================== */

/* ======================================================
   V3 MIGRATION Helpers
   ====================================================== */

   // compare the new total to the <v3.0 total and log any discrepencies
   // this is called just after close of v3.0 migration (via page, as needs post-closure dal3)
   function zeroBSCRM_migration_3000_verifyInvoiceTotals(){

   		global $zbs;

   		$discrepencies = array();

  			// get minimal-data invoices:
      		$invoices = $zbs->DAL->invoices->getInvoices(array(
				            
				            'withLineItems'     => true,
				            'withCustomFields'  => false,
				            'withTransactions'  => false,
				            'withTags'          => false,
				            'withOwner'         => false,
				            'withAssigned'      => false,
				            'withFiles'         => false,

				            'sortByField'   => 'ID',
				            'sortOrder'     => 'ASC',
				            'page'          => -1,
				            'perPage'       => -1,
				            'ignoreowner' 	=> true
				        ));

      		// brutal cycle through, no paging, as suspect few where this'll be an issue (Added test for RC)
      		if (is_array($invoices)) foreach ($invoices as $invoice){

      			$originalTotal = $zbs->DAL->meta(ZBS_TYPE_INVOICE,$invoice['id'],'extra_prev3_total',false);
      			if ($originalTotal !== false){

      				// identifier
      				$id = $invoice['id'];
      				if (!empty($invoice['id_override'])) $id = $invoice['id_override'];

      				// recalc inv
      				$invFinal = $zbs->DAL->invoices->recalculate($invoice);
      				$invTotal = (float)$invFinal['total'];

      				// cast
      				$originalTotal = (float)$originalTotal;

      				// compare
      				if ($invTotal !== $originalTotal){

      					// discrepency
      					$discrepencies[$invoice['id']] = array('id'=>$id,'original'=>$originalTotal,'new'=>$invTotal);

      				}

      			}

      		}


      	return $discrepencies;

   }

   // simplistic arr manager
   function zeroBSCRM_migration_addErrToStack($err=array(),$errKey=''){

   		if ($errKey !== ''){

   			$existing = get_option($errKey, array());

   			// catch err in err stack.
   			if (!is_array($existing)) $existing = array();

   			// add + update
   			$existing[] = $err;
			update_option( $errKey, $existing);

			return true;

   		}

   		return false;
   }

   // checks if a column already exists
   // note $tableName is used unchecked
   function zeroBSCRM_migration_tableHasColumn($tableName='',$columnName=''){

   		global $wpdb;

   		if (!empty($tableName) && !empty($columnName)){

   			$q = $wpdb->prepare("SHOW COLUMNS FROM ".$tableName." LIKE %s",array($columnName));
	 
	 		// old
	   		//$row = $wpdb->get_results( $wpdb->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = %s AND column_name = %s",array($tableName,$columnName))  );
	   		$row = $wpdb->get_results( $q );
			
			if (is_array($row) && count($row) == 0) return false;

		} 

		return true;


   }

/* ======================================================
   / V3 MIGRATION Helpers
   ====================================================== */