<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.20
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 01/11/16
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */



/* ======================================================
  Add Roles
   ====================================================== */

   // for changes to be enacted, need to remove them before adding them!
   function zeroBSCRM_clearUserRoles(){

   		remove_role('zerobs_admin');
   		remove_role('zerobs_customermgr');
   		remove_role('zerobs_quotemgr');
   		remove_role('zerobs_invoicemgr');
   		remove_role('zerobs_transactionmgr');
   		remove_role('zerobs_customer');
   		remove_role('zerobs_mailmgr');

   }

	#} Build User Roles
	function zeroBSCRM_addUserRoles(){

			#} ZBS Admin
			// Add a custom user role
			#https://managewp.com/create-custom-user-roles-wordpress
			$result = add_role( 'zerobs_admin', __(

				'ZeroBSCRM Admin (Full ZBS Permissions)', 'zero-bs-crm' ),

				array(

				'read' => true, // true allows this capability
				'edit_posts' => false, // Allows user to edit their own posts
				'edit_pages' => false, // Allows user to edit pages
				'edit_others_posts' => false, // Allows user to edit others posts not just their own
				'create_posts' => false, // Allows user to create new posts
				'manage_categories' => false, // Allows user to manage post categories
				'publish_posts' => false, // Allows the user to publish, otherwise posts stays in draft mode

				)

			);

		    // gets the author role
		    $role = get_role( 'zerobs_admin' );

		    // This only works, because it accesses the class instance.
		    // would allow the author to edit others' posts for current theme only

		    #} W Note... can't we add all these above in add_role?
		    $role->add_cap( 'read' );
		    $role->remove_cap( 'edit_posts' );
		   	$role->add_cap( 'upload_files' ); // added 21/5/18 to ensure can upload media
		    $role->add_cap( 'admin_zerobs_usr' ); #} For all zerobs users :)
		    $role->add_cap( 'admin_zerobs_customers' );
		    $role->add_cap( 'admin_zerobs_customers_tags' );
		    $role->add_cap( 'admin_zerobs_quotes' );
		    $role->add_cap( 'admin_zerobs_events' );
		    $role->add_cap( 'admin_zerobs_invoices' );
		    $role->add_cap( 'admin_zerobs_transactions' );
		    $role->add_cap( 'admin_zerobs_forms' );
		    // NOTE. Adding this adds a random "Post categories / not posts" to menu
		    // will have to remove programattically :(
		    	$role->add_cap( 'manage_categories' );
		    $role->add_cap( 'manage_sales_dash' ); #mike added
		    $role->add_cap( 'admin_zerobs_mailcampaigns' );
		    $role->add_cap( 'zbs_dash' ); # WH added 1.2 - has rights to view ZBS Dash

		    // added 2.4 - for settings
		    $role->add_cap( 'admin_zerobs_manage_options' );
		    // ... and view versions (cannot edit)
		    $role->add_cap( 'admin_zerobs_view_customers' );
		    $role->add_cap( 'admin_zerobs_view_quotes' );
		    $role->add_cap( 'admin_zerobs_view_invoices' );
		    $role->add_cap( 'admin_zerobs_view_events' );
		   	$role->add_cap( 'admin_zerobs_view_transactions' );

		   	// logs
		    $role->add_cap( 'admin_zerobs_logs_addedit' );
		    $role->add_cap( 'admin_zerobs_logs_delete' );

		    // emails
		    $role->add_cap( 'admin_zerobs_sendemails_contacts' );

		    unset($role);




		    #=====================================================
		    #=====================================================

		    #} ALL ADMINS TOO :)

		    // gets the author role
		    $role = get_role( 'administrator' );

		    // this is for users who've removed 'administrator' role type
		    // WH temp catch anyhow, for Nimitz.
		    if ($role !== null){

			    // Caps
			    $role->add_cap( 'admin_zerobs_customers' );
			    $role->add_cap( 'admin_zerobs_customers_tags' );
			    $role->add_cap( 'admin_zerobs_quotes' );
			    $role->add_cap( 'admin_zerobs_invoices' );
			    $role->add_cap( 'admin_zerobs_events' );
			   	$role->add_cap( 'admin_zerobs_transactions' );
			    $role->add_cap( 'manage_sales_dash' );
			    $role->add_cap( 'admin_zerobs_mailcampaigns' );
			    $role->add_cap( 'admin_zerobs_forms' );
			    $role->add_cap( 'zbs_dash' ); # WH added 1.2 - has rights to view ZBS Dash
			    // NOPE. this shouldn't be here, _usr is to group our users $role->add_cap( 'admin_zerobs_usr' );

			    // added 2.4 - for settings
			    $role->add_cap( 'admin_zerobs_manage_options' );
			    // ... and view versions (cannot edit)
			    $role->add_cap( 'admin_zerobs_view_customers' );
			    $role->add_cap( 'admin_zerobs_view_quotes' );
			    $role->add_cap( 'admin_zerobs_view_invoices' );
			    $role->add_cap( 'admin_zerobs_view_events' );
			   	$role->add_cap( 'admin_zerobs_view_transactions' );
			   	// needed for notifications
			   	$role->add_cap( 'admin_zerobs_notifications' );

			   	// logs
			    $role->add_cap( 'admin_zerobs_logs_addedit' );
			    $role->add_cap( 'admin_zerobs_logs_delete' );

				//all users
	            $role->add_cap('admin_zerobs_usr');

			    // emails
			    $role->add_cap( 'admin_zerobs_sendemails_contacts' );

			    unset($role);

			}



		    #=====================================================
		    #=====================================================

		    #} ZBS Customer Manager
			$result = add_role( 'zerobs_customermgr', __(

				'ZeroBSCRM Customer Manager', 'zero-bs-crm' ),

				array(

				'read' => true, // true allows this capability
				'edit_posts' => false, // Allows user to edit their own posts
				'edit_pages' => false, // Allows user to edit pages
				'edit_others_posts' => false, // Allows user to edit others posts not just their own
				'create_posts' => false, // Allows user to create new posts
				'manage_categories' => false, // Allows user to manage post categories
				'publish_posts' => false, // Allows the user to publish, otherwise posts stays in draft mode

				)

			);

		    // gets the author role
		    $role = get_role( 'zerobs_customermgr' );

		    // caps
		    $role->add_cap( 'read' );
		    $role->remove_cap( 'edit_posts' );
		   	$role->add_cap( 'upload_files' ); // added 21/5/18 to ensure can upload media
		    $role->add_cap( 'admin_zerobs_usr' ); #} For all zerobs users :)
		    $role->add_cap( 'admin_zerobs_customers' );
		    $role->add_cap( 'admin_zerobs_customers_tags' );
		    $role->add_cap( 'manage_categories' );
		    $role->add_cap( 'zbs_dash' ); # WH added 1.2 - has rights to view ZBS Dash
			$role->add_cap( 'admin_zerobs_events' );

		    // ... and view versions (cannot edit)
		    $role->add_cap( 'admin_zerobs_view_customers' );
		    $role->add_cap( 'admin_zerobs_view_quotes' );
		    $role->add_cap( 'admin_zerobs_view_invoices' );
		    $role->add_cap( 'admin_zerobs_view_events' );
		   	$role->add_cap( 'admin_zerobs_view_transactions' );
		   	
		   	// ADDING these until we have singular views for all
		    $role->add_cap( 'admin_zerobs_quotes' );
		    $role->add_cap( 'admin_zerobs_events' );
		    $role->add_cap( 'admin_zerobs_invoices' );
		    $role->add_cap( 'admin_zerobs_transactions' );
		   	// needed for notifications
		   	$role->add_cap( 'admin_zerobs_notifications' );

		   	// logs
		    $role->add_cap( 'admin_zerobs_logs_addedit' );
		    //$role->add_cap( 'admin_zerobs_logs_delete' );
            
		    // emails
		    $role->add_cap( 'admin_zerobs_sendemails_contacts' );

		    unset($role);




		    #=====================================================
		    #=====================================================



		    #} ZBS Quote Manager
			$result = add_role( 'zerobs_quotemgr', __(

				'ZeroBSCRM Quote Manager', 'zero-bs-crm' ),

				array(

				'read' => true, // true allows this capability
				'edit_posts' => false, // Allows user to edit their own posts
				'edit_pages' => false, // Allows user to edit pages
				'edit_others_posts' => false, // Allows user to edit others posts not just their own
				'create_posts' => false, // Allows user to create new posts
				'manage_categories' => false, // Allows user to manage post categories
				'publish_posts' => false, // Allows the user to publish, otherwise posts stays in draft mode

				)

			);

		    // gets the author role
		    $role = get_role( 'zerobs_quotemgr' );

		    // caps
		    $role->add_cap( 'read' );
		    $role->remove_cap( 'edit_posts' );
		   	$role->add_cap( 'upload_files' ); // added 21/5/18 to ensure can upload media
		    $role->add_cap( 'admin_zerobs_usr' ); #} For all zerobs users :)
		    $role->add_cap( 'admin_zerobs_quotes' );
		    $role->add_cap( 'manage_categories' );
		    $role->add_cap( 'zbs_dash' ); # WH added 1.2 - has rights to view ZBS Dash
		    // ... and view versions (cannot edit)
		    $role->add_cap( 'admin_zerobs_view_customers' );
		    $role->add_cap( 'admin_zerobs_view_quotes' );
		    $role->add_cap( 'admin_zerobs_view_events' );

		   	// ADDING these until we have singular views for all
		    $role->add_cap( 'admin_zerobs_customers' );
		    $role->add_cap( 'admin_zerobs_events' );
		   	// needed for notifications
		   	$role->add_cap( 'admin_zerobs_notifications' );

		   	// logs
		    $role->add_cap( 'admin_zerobs_logs_addedit' );
		    //$role->add_cap( 'admin_zerobs_logs_delete' );

		    unset($role);




		    #=====================================================
		    #=====================================================

		    #} ZBS Invoice Manager
			$result = add_role( 'zerobs_invoicemgr', __(

				'ZeroBSCRM Invoice Manager', 'zero-bs-crm' ),

				array(

				'read' => true, // true allows this capability
				'edit_posts' => false, // Allows user to edit their own posts
				'edit_pages' => false, // Allows user to edit pages
				'edit_others_posts' => false, // Allows user to edit others posts not just their own
				'create_posts' => false, // Allows user to create new posts
				'manage_categories' => false, // Allows user to manage post categories
				'publish_posts' => false, // Allows the user to publish, otherwise posts stays in draft mode

				)

			);

		    // gets the author role
		    $role = get_role( 'zerobs_invoicemgr' );

		    // caps
		    $role->add_cap( 'read' );
		    $role->remove_cap( 'edit_posts' );
		   	$role->add_cap( 'upload_files' ); // added 21/5/18 to ensure can upload media
		    $role->add_cap( 'admin_zerobs_usr' ); #} For all zerobs users :)
		    $role->add_cap( 'admin_zerobs_invoices' );
		    $role->add_cap( 'manage_categories' );
		    $role->add_cap( 'zbs_dash' ); # WH added 1.2 - has rights to view ZBS Dash
		    // ... and view versions (cannot edit)
		    $role->add_cap( 'admin_zerobs_view_customers' );
		    $role->add_cap( 'admin_zerobs_view_events' );
		    $role->add_cap( 'admin_zerobs_view_invoices' );

		   	// ADDING these until we have singular views for all
		    $role->add_cap( 'admin_zerobs_customers' );
		    $role->add_cap( 'admin_zerobs_events' );
		   	// needed for notifications
		   	$role->add_cap( 'admin_zerobs_notifications' );

		   	// logs
		    $role->add_cap( 'admin_zerobs_logs_addedit' );
		    //$role->add_cap( 'admin_zerobs_logs_delete' );

		    unset($role);




		    #=====================================================
		    #=====================================================

		    #} ZBS Transaction Manager
			$result = add_role( 'zerobs_transactionmgr', __(

				'ZeroBSCRM Transaction Manager', 'zero-bs-crm' ),

				array(

				'read' => false, // true allows this capability
				'edit_posts' => false, // Allows user to edit their own posts
				'edit_pages' => false, // Allows user to edit pages
				'edit_others_posts' => false, // Allows user to edit others posts not just their own
				'create_posts' => false, // Allows user to create new posts
				'manage_categories' => false, // Allows user to manage post categories
				'publish_posts' => false, // Allows the user to publish, otherwise posts stays in draft mode

				)

			);

		    // gets the author role
		    $role = get_role( 'zerobs_transactionmgr' );

		    // caps
		    $role->add_cap( 'read' );
		    $role->remove_cap( 'edit_posts' );
		   	$role->add_cap( 'upload_files' ); // added 21/5/18 to ensure can upload media
		    $role->add_cap( 'admin_zerobs_usr' ); #} For all zerobs users :)
		    $role->add_cap( 'admin_zerobs_transactions' );
		    $role->add_cap( 'manage_categories' );
		    $role->add_cap( 'zbs_dash' ); # WH added 1.2 - has rights to view ZBS Dash
		    // ... and view versions (cannot edit)
		    $role->add_cap( 'admin_zerobs_view_customers' );
		    $role->add_cap( 'admin_zerobs_view_events' );
		   	$role->add_cap( 'admin_zerobs_view_transactions' );

		   	// ADDING these until we have singular views for all
		    $role->add_cap( 'admin_zerobs_customers' );
		    $role->add_cap( 'admin_zerobs_events' );
		   	// needed for notifications
		   	$role->add_cap( 'admin_zerobs_notifications' );

		   	// logs
		    $role->add_cap( 'admin_zerobs_logs_addedit' );
		    //$role->add_cap( 'admin_zerobs_logs_delete' );

		    unset($role);




		    #=====================================================
		    #=====================================================


		    #} ZBS Customer
			$result = add_role( 'zerobs_customer', __(

				'ZeroBSCRM Customer', 'zero-bs-crm' ),

				array(

					'read' => true, // true allows this capability
					'edit_posts' => false, // Allows user to edit their own posts
					'edit_pages' => false, // Allows user to edit pages
					'edit_others_posts' => false, // Allows user to edit others posts not just their own
					'create_posts' => false, // Allows user to create new posts
					'manage_categories' => false, // Allows user to manage post categories
					'publish_posts' => false, // Allows the user to publish, otherwise posts stays in draft mode

				)

			);


		    #=====================================================
		    #=====================================================

		    #} ZBS Mail Manager - Manages campaigns, customers / companies
			$result = add_role( 'zerobs_mailmgr', __(

				'ZeroBSCRM Mail Manager', 'zero-bs-crm' ),

				array(

				'read' => false, // true allows this capability
				'edit_posts' => false, // Allows user to edit their own posts
				'edit_pages' => false, // Allows user to edit pages
				'edit_others_posts' => false, // Allows user to edit others posts not just their own
				'create_posts' => false, // Allows user to create new posts
				'manage_categories' => false, // Allows user to manage post categories
				'publish_posts' => false, // Allows the user to publish, otherwise posts stays in draft mode

				)

			);

		    // gets the author role
		    $role = get_role( 'zerobs_mailmgr' );

		    // caps
		    $role->add_cap( 'read' );
		    $role->remove_cap( 'edit_posts' );
		   	$role->add_cap( 'upload_files' ); // added 21/5/18 to ensure can upload media
		    $role->add_cap( 'admin_zerobs_usr' ); #} For all zerobs users :)
		    $role->add_cap( 'admin_zerobs_mailcampaigns' );
		    $role->add_cap( 'admin_zerobs_customers' );
		    $role->add_cap( 'admin_zerobs_customers_tags' );
		    $role->add_cap( 'manage_categories' );
		    $role->add_cap( 'zbs_dash' ); # WH added 1.2 - has rights to view ZBS Dash

		    // ... and view versions (cannot edit)
		    $role->add_cap( 'admin_zerobs_view_customers' );
		    $role->add_cap( 'admin_zerobs_view_quotes' );
		    $role->add_cap( 'admin_zerobs_view_invoices' );
		    $role->add_cap( 'admin_zerobs_view_events' );
		   	$role->add_cap( 'admin_zerobs_view_transactions' );

		   	// ADDING these until we have singular views for all
		    $role->add_cap( 'admin_zerobs_quotes' );
		    $role->add_cap( 'admin_zerobs_events' );
		    $role->add_cap( 'admin_zerobs_invoices' );
		    $role->add_cap( 'admin_zerobs_transactions' );
		   	// needed for notifications
		   	$role->add_cap( 'admin_zerobs_notifications' );
            
		    // emails
		    $role->add_cap( 'admin_zerobs_sendemails_contacts' );

		    unset($role);




		    #=====================================================
		    #=====================================================



	}

	#function zeroBSCRM_RemoveUserRoles(){

	/*
		    // gets the author role
		    $role = get_role( 'zerobs_user' );

		    // This only works, because it accesses the class instance.
		    // would allow the author to edit others' posts for current theme only
		    $role->remove_cap( 'admin_zerobs_customers' );
		    $role->remove_cap( 'admin_zerobs_quotes' );
		    $role->remove_cap( 'admin_zerobs_invoices' );

	*/


/* ======================================================
  / Add + Remove Roles
   ====================================================== */





/* ======================================================
  Role Helpers
   ====================================================== */

   // note this returns true if is any ZBS role, INCLUDING zbs customer 
   // if need just 'backend' user, use zeroBSCRM_permsIsZBSBackendUser
	function zeroBSCRM_permsIsZBSUser(){

		#} Set a global var for this load, (sometimes multi-called)
		global $zeroBSCRM_isZBSUser;

		if (isset($zeroBSCRM_isZBSUser)) return $zeroBSCRM_isZBSUser;

		#} ... else
		$zeroBSCRM_isZBSUser = false;
	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_usr')) $zeroBSCRM_isZBSUser = true;
	    if ($cu->has_cap('zerobs_customer')) $zeroBSCRM_isZBSUser = true;
	    
	    return $zeroBSCRM_isZBSUser;
	}
   	// note this returns true if is any wp-admin based zbs user
   	// if want zbs customer roles too, use zeroBSCRM_permsIsZBSUser
	function zeroBSCRM_permsIsZBSBackendUser(){

		#} Set a global var for this load, (sometimes multi-called)
		global $zeroBSCRM_isZBSBackendUser;

		if (isset($zeroBSCRM_isZBSBackendUser)) return $zeroBSCRM_isZBSBackendUser;

		#} ... else
		$zeroBSCRM_isZBSBackendUser = false;
	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_usr')) $zeroBSCRM_isZBSBackendUser = true;
	    
	    return $zeroBSCRM_isZBSBackendUser;
	}
	// is backend zbs user, or wp admin
	function zeroBSCRM_permsIsZBSUserOrAdmin(){

		#} Set a global var for this load, (sometimes multi-called)
		global $zeroBSCRM_isZBSUser;

		if (isset($zeroBSCRM_isZBSUser)) return $zeroBSCRM_isZBSUser;

		#} ... else
		$zeroBSCRM_isZBSUser = false;
	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_usr')) $zeroBSCRM_isZBSUser = true;

	    #} or adm
	    if ($cu->has_cap('manage_options')) return true; //$zeroBSCRM_isZBSUser = true;
	    
	    return $zeroBSCRM_isZBSUser;
	}

	function zeroBSCRM_isZBSAdmin(){

	    $cu = wp_get_current_user();
	    //https://wordpress.stackexchange.com/questions/5047/how-to-check-if-a-user-is-in-a-specific-role
	    if (in_array( 'zerobs_admin', (array) $cu->roles )) return true;

	    return false;
	}

	function zeroBSCRM_isWPAdmin(){

	    $cu = wp_get_current_user();
	    
	    #} adm
	    if ($cu->has_cap('manage_options')) return true;

	    return false;
	}

	function zeroBSCRM_isZBSAdminOrAdmin(){

	    $cu = wp_get_current_user();
	    //https://wordpress.stackexchange.com/questions/5047/how-to-check-if-a-user-is-in-a-specific-role
	    if (in_array( 'zerobs_admin', (array) $cu->roles )) return true;

	    #} or adm
	    if ($cu->has_cap('manage_options')) return true;

	    return false;
	}

	function zeroBSCRM_wooCommerceRemoveBlock(){
	    #} Add Filter for WooCommerce
	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_usr')){
	    	add_filter( 'woocommerce_prevent_admin_access', '__return_false' );
		}
	}

	function zeroBSCRM_getWordPressRoles(){

	    global $wp_roles;

	    $all_roles = $wp_roles->roles;

	    return $all_roles;
	    
	}

	// return current user capabilities
	function zeroBSCRM_getCurrentUserCaps(){

		$data = get_userdata( get_current_user_id() );

		if ( is_object( $data) ) {
			return array_keys($data->allcaps);
		}

		return array();
	}


	// takes an objtypeid e.g. 1 = ZBS_TYPE_CONTACT
	// ... then checks current user has access to that type/area
	function zeroBSCRM_permsObjType($objTypeID=-1){

		switch ($objTypeID){

			case ZBS_TYPE_CONTACT:
			case ZBS_TYPE_COMPANY:

				return zeroBSCRM_permsCustomers();
				break;

			case ZBS_TYPE_QUOTE:
			case ZBS_TYPE_QUOTETEMPLATE:

				return zeroBSCRM_permsQuotes();
				break;

			case ZBS_TYPE_INVOICE:

				return zeroBSCRM_permsInvoices();
				break;

			case ZBS_TYPE_TRANSACTION:

				return zeroBSCRM_permsTransactions();
				break;

			case ZBS_TYPE_FORM:

				return zeroBSCRM_permsForms();
				break;

			case ZBS_TYPE_EVENT:

				return zeroBSCRM_permsEvents();
				break;			

		}

		return false;
	}

	function zeroBSCRM_permsCustomers(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_customers')) return true;
	    return false;
	}

	function zeroBSCRM_permsSendEmailContacts(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_sendemails_contacts')) return true;
	    return false;
	}
            
	function zeroBSCRM_permsViewCustomers(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_view_customers')) return true;
	    return false;
	}
	function zeroBSCRM_permsCustomersTags(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_customers_tags')) return true;
	    return false;
	}
	function zeroBSCRM_permsQuotes(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_quotes')) return true;
	    return false;
	}
	function zeroBSCRM_permsViewQuotes(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_view_quotes')) return true;
	    return false;
	}
	function zeroBSCRM_permsInvoices(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_invoices')) return true;
	    return false;
	}
	function zeroBSCRM_permsViewInvoices(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_view_invoices')) return true;
	    return false;
	}
	function zeroBSCRM_permsTransactions(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_transactions')) return true;
	    return false;
	}
	function zeroBSCRM_permsViewTransactions(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_view_transactions')) return true;
	    return false;
	}
	function zeroBSCRM_permsMailCampaigns(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_mailcampaigns')) return true;
	    return false;
	}
	function zeroBSCRM_permsForms(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_forms')) return true;
	    return false;
	}

	function zeroBSCRM_permsEvents(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_events')) return true;
	    return false;
	}
	function zeroBSCRM_permsNotify(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_notifications')) return true;
	    return false;
	}
	// NEEDS it's own cap when we get granular.
	function zeroBSCRM_permsExport(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('admin_zerobs_customers')) return true;
	    return false;
	}


	// LOGS


		// can add/edit logs
		function zeroBSCRM_permsLogsAddEdit(){

		    $cu = wp_get_current_user();
		    if ($cu->has_cap('admin_zerobs_logs_addedit')) return true;
		    return false;
		}

		// can delete logs
		function zeroBSCRM_permsLogsDelete(){

		    $cu = wp_get_current_user();
		    if ($cu->has_cap('admin_zerobs_logs_delete')) return true;
		    return false;
		}



	function zeroBSCRM_permsClient(){   //using Client to not confuse with Customer and Customer Manager

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('zerobs_customer')) return true;
	    return false;
	}
	function zeroBSCRM_permsWPEditPosts(){

	    $cu = wp_get_current_user();
	    if ($cu->has_cap('edit_posts')) return true;
	    return false;
	}

	function zeroBS_getPossibleCustomerOwners(){ return zeroBS_getPossibleOwners(array('zerobs_admin','zerobs_customermgr')); }
	function zeroBS_getPossibleCompanyOwners(){ return zeroBS_getPossibleOwners(array('zerobs_admin','zerobs_customermgr')); }
	function zeroBS_getPossibleQuoteOwners(){ return zeroBS_getPossibleOwners(array('zerobs_admin','zerobs_customermgr')); }
	function zeroBS_getPossibleInvoiceOwners(){ return zeroBS_getPossibleOwners(array('zerobs_admin','zerobs_customermgr')); }
	function zeroBS_getPossibleTransactionOwners(){ return zeroBS_getPossibleOwners(array('zerobs_admin','zerobs_customermgr')); }
	function zeroBS_getPossibleEventOwners(){ return zeroBS_getPossibleOwners(array('zerobs_admin','admin_zerobs_events')); }


	// added this because Multi-site doesn't reliably 
	// return on current_user_can('zerobs_customer')
	// https://wordpress.stackexchange.com/questions/5047/how-to-check-if-a-user-is-in-a-specific-role
	function zeroBSCRM_isRole($role=''){

		$user = wp_get_current_user();
		if ( in_array( $role, (array) $user->roles ) ) {
		    return true;
		}

		return false;

	}


	function zeroBS_getPossibleOwners($permsReq='',$simplify=false){

		// https://codex.wordpress.org/Function_Reference/get_users 
		/* possible args..
		 $args = array(
			'blog_id'      => $GLOBALS['blog_id'],
			'role'         => '',
			'role__in'     => array('administrator','zerobs_admin','zerobs_customermgr','zerobs_quotemgr','zerobs_invoicemgr','zerobs_transactionmgr',''),
			'role__not_in' => array(),
			'meta_key'     => '',
			'meta_value'   => '',
			'meta_compare' => '',
			'meta_query'   => array(),
			'date_query'   => array(),        
			'include'      => array(),
			'exclude'      => array(),
			'orderby'      => 'login',
			'order'        => 'ASC',
			'offset'       => '',
			'search'       => '',
			'number'       => '',
			'count_total'  => false,
			'fields'       => 'all',
			'who'          => '',
		 ); 

		 */


		if (empty($permsReq) || !in_array($permsReq, array('zerobs_admin','zerobs_customermgr','zerobs_quotemgr','zerobs_invoicemgr','zerobs_transactionmgr'))){

			// all zbs users + admin
			$args = array('role__in'     => array('administrator','zerobs_admin','zerobs_customermgr','zerobs_quotemgr','zerobs_invoicemgr','zerobs_transactionmgr'));

		} else {

			// specific roles :) (+- admin?)
			$args = array('role__in'     => array('administrator',$permsReq));


		}

		$users = get_users( $args );


		// this is used by inline editing on list view, be careful if editing
		if ($simplify){

			if (is_array($users)){

				$ret = array();

				foreach ($users as $u){

					$ret[] = array(

						'id' => $u->ID,
						'name' => $u->data->display_name,
						'email' => $u->data->user_email

					);
				}

				$users = $ret;
			}

		}

		return $users;

	}
/* ======================================================
  / Role Helpers
   ====================================================== */