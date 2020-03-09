<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.4+
 *
 * Copyright 2018, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 05/02/2017
 */


/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

   /* 


			!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

   			THIS FILE IS FOR WordPress Menu related changes - later to be unified into one .Menu file 

			!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!


   */


/* ======================================================
   v3 ZBS Menu Arr -> WP Admin Menu Associated Funcs
   ====================================================== */

// This builds out our ZBS menu array() 
// ... which ultimately forms the "default" zbs menu 
// ... which the core then uses to inject what it needs to into wp admin menus :)
function zeroBSCRM_menu_buildMenu(){

	global $zbs;

    #} Get the admin layout option 1 = Full, 2 = Slimline, 3 = CRM Only
    $zbsMenuMode = zeroBSCRM_getSetting('menulayout');
    if (!isset($zbsMenuMode) || !in_array($zbsMenuMode,array(1,2,3))) $zbsMenuMode = 2; #} Defaults to slimline

	#} Get other settings

		// b2b mode
		$b2bMode = zeroBSCRM_getSetting('companylevelcustomers');
	
		// other feats
		$useNeedsQuote = zeroBSCRM_getSetting('showneedsquote');
		$useQuotes = zeroBSCRM_getSetting('feat_quotes');
		$useInvoices = zeroBSCRM_getSetting('feat_invs');
		$useTransactions = zeroBSCRM_getSetting('feat_transactions');
		$useForms = zeroBSCRM_isExtensionInstalled('forms'); //zeroBSCRM_getSetting('feat_forms');
		$useCalendar = zeroBSCRM_getSetting('feat_calendar');

	#} Menu Builder, in a POST CPT world

	/* 

		array(
				'zbscrm' => array(
									'ico' => 'icon',
									'title' => 'title',
									'url' => 'url',
									'perms' => 'admin_zerobs_customers', (user capability)
									'order' => 99, // this is internal ordering (e.g. in zbs menus)
									'wpposition' => 99, // this is passed to wp
									'subitems' => array,
									'callback' => zeroBSCRM_pages_home
									'stylefuncs' => array
				),

				'hidden' => array(
									'subitems' => array() // THIS is all pages which need adding to WP but not adding to menus
				)

		)

	*/

	// this is the "first build" function, so begin with this :)
	$menu = array('hidden'=>array('perms' => 'zbs_dash','subitems'=>array()));

	// ===================================================
	// ======= ZBS Slimline Main Menu
	// ===================================================	
	if ($zbsMenuMode == ZBS_MENU_SLIM){

		// ZBS Slimline Main Menu (Top Level)
		$menu['zbs'] = array(
									'ico' => 'dashicons-groups',
									'title' => __('Zero BS CRM','zero-bs-crm'),
									'url' => $zbs->slugs['dash'],
									'perms' => 'zbs_dash',
									'order' => 1,
									'wpposition' => 2,
									'subitems' => array(),
									'callback' => 'zeroBSCRM_pages_dash',
									'stylefuncs' => array(
														'zeroBSCRM_global_admin_styles',
														'zeroBSCRM_admin_styles_chartjs',
														'zeroBSCRM_admin_styles_homedash'
													)
							);

		// Contacts (sub)
		$menu['zbs']['subitems']['contacts'] = array(
									'title' => __('Contacts','zero-bs-crm'),
									'url' => $zbs->slugs['managecontacts'],
									'perms' => 'admin_zerobs_view_customers',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_render_customerslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Companies (sub)
		if ($b2bMode > 0) $menu['zbs']['subitems']['companies'] = array(
									'title' => __(zeroBSCRM_getCompanyOrOrgPlural(),'zero-bs-crm'),
									'url' => $zbs->slugs['managecompanies'],
									'perms' => 'admin_zerobs_view_customers',
									'order' => 2,
									'wpposition' => 2,
									'callback' => 'zeroBSCRM_render_companyslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Quotes (sub)
		if ($useQuotes > 0) $menu['zbs']['subitems']['quotes'] = array(
									'title' => __('Quotes','zero-bs-crm'),
									'url' => $zbs->slugs['managequotes'],
									'perms' => 'admin_zerobs_view_quotes',
									'order' => 3,
									'wpposition' => 3,
									'callback' => 'zeroBSCRM_render_quoteslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Templates (sub)
		if ($useQuotes > 0) $menu['zbs']['subitems']['quotetemplates'] = array(
									'title' => __('Quote Templates','zero-bs-crm'),
									'url' => $zbs->slugs['quote-templates'],
									'perms' => 'admin_zerobs_quotes',
									'order' => 4, 'wpposition' => 4,
									'callback' => 'zeroBSCRM_render_quotetemplateslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Invoices (sub)
		if ($useInvoices > 0) $menu['zbs']['subitems']['invoices'] = array(
									'title' => __('Invoices','zero-bs-crm'),
									'url' => $zbs->slugs['manageinvoices'],
									'perms' => 'admin_zerobs_view_invoices',
									'order' => 3,
									'wpposition' => 3,
									'callback' => 'zeroBSCRM_render_invoiceslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview') //zeroBSCRM_scriptStyles_admin_invoiceBuilder
							);

		// Transactions (sub)
		if ($useTransactions > 0) $menu['zbs']['subitems']['transactions'] = array(
									'title' => __('Transactions','zero-bs-crm'),
									'url' => $zbs->slugs['managetransactions'],
									'perms' => 'admin_zerobs_view_transactions',
									'order' => 4,
									'wpposition' => 4,
									'callback' => 'zeroBSCRM_render_transactionslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Forms (sub)
		if ($useForms > 0) $menu['zbs']['subitems']['forms'] = array(
									'title' => __('Forms','zero-bs-crm'),
									'url' => $zbs->slugs['manageformscrm'],
									'perms' => 'admin_zerobs_forms',
									'order' => 5, 'wpposition' => 5,
									'callback' => 'zeroBSCRM_render_formslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Calendar (sub)
		if ($useCalendar > 0) $menu['zbs']['subitems']['calendar'] = array(
									'title' => __('Task Scheduler','zero-bs-crm'),
									'url' => $zbs->slugs['manage-events'],
									'perms' => 'admin_zerobs_view_events',
									'order' => 5, 'wpposition' => 5,
									'callback' => 'zeroBSCRM_render_eventslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_calendar_admin_styles')
							);

		// Advanced Search (sub) - removed for 3.0+, lets just improve normal search
		if (!$zbs->isDAL3()) $menu['zbs']['subitems']['advancedsearch'] = array(
									'title' => __('Advanced Search','zero-bs-crm'),
									'url' => $zbs->slugs['advanced-search'],
									'perms' => 'admin_zerobs_customers',
									'order' => 5, 'wpposition' => 5,
									'callback' => 'zeroBSCRM_advancedSearch',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Segments (sub)
		$menu['zbs']['subitems']['segments'] = array(
									'title' => __('Segments','zero-bs-crm'),
									'url' => $zbs->slugs['segments'],
									'perms' => 'admin_zerobs_customers',
									'order' => 5, 'wpposition' => 5,
									'callback' => 'zeroBSCRM_render_segmentslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Data Tools (sub)
		$menu['zbs']['subitems']['datatools'] = array(
									'title' => __('Data Tools','zero-bs-crm'),
									'url' => $zbs->slugs['datatools'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 90, 'wpposition' => 90,
									'callback' => 'zeroBSCRM_pages_datatools',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Settings (sub)
		$menu['zbs']['subitems']['settings'] = array(
									'title' => __('Settings','zero-bs-crm'),
									'url' => $zbs->slugs['settings'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 95, 'wpposition' => 95,
									'callback' => 'zeroBSCRM_pages_settings',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_settingspage_admin_styles')						
							);

		##WLREMOVE	// Install Extensions (sub)
		if (current_user_can('manage_options')) $menu['zbs']['subitems']['installext'] = array(
									'title' => '<span style="color: #FCB214 !important;">'.__('Install Extensions','zero-bs-crm').'</span>',
									'url' => $zbs->slugs['zerobscrm-install-helper'],
									'perms' => 'admin_zerobs_customers',
									'order' => 99, 'wpposition' => 99,
									'callback' => 'zeroBSCRM_pages_installextensionshelper',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);
		##/WLREMOVE

	}

	// ===================================================
	// ======= / ZBS Slimline Main Menu
	// ===================================================	


	// ===================================================
	// ======= User Dash Menu (non-slimline)
	// ===================================================
	if ($zbsMenuMode != ZBS_MENU_SLIM){

		// User Dash (Top Level)
		$menu['zbs'] = array(
									'ico' => 'dashicons-groups',
									'title' => __('ZBS Dashboard','zero-bs-crm'),
									'url' => $zbs->slugs['dash'],
									'perms' => 'zbs_dash',
									'order' => 1,
									'wpposition' => 2,
									'subitems' => array(),
									'callback' => 'zeroBSCRM_pages_dash',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_chartjs','zeroBSCRM_admin_styles_homedash')
							);

		// Advanced Search (sub)
		$menu['zbs']['subitems']['advancedsearch'] = array(
									'title' => __('Advanced Search','zero-bs-crm'),
									'url' => $zbs->slugs['advanced-search'],
									'perms' => 'admin_zerobs_customers',
									'order' => 5, 'wpposition' => 5,
									'callback' => 'zeroBSCRM_advancedSearch',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

	}
	// ===================================================
	// ======= / User Dash Menu (non-slimline)
	// ===================================================	



	// ===================================================
	// ======= Contact Menu (non-slimline)
	// ===================================================
	if ($zbsMenuMode != ZBS_MENU_SLIM){

		// Contacts (Top Level)
		$menu['contacts'] = array(
									'ico' => 'dashicons-admin-users',
									'title' => __('Contacts','zero-bs-crm'),
									'url' => $zbs->slugs['managecontacts'],
									'perms' => 'admin_zerobs_view_customers',
									'order' => 10, 'wpposition' => 25,
									'subitems' => array(),
									'callback' => 'zeroBSCRM_render_customerslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Needs Quote (sub)
		if ($useNeedsQuote > 0) $menu['contacts']['subitems']['needsquotes'] = array(
									'title' => __('Needs a Quote','zero-bs-crm'),
									'url' => $zbs->slugs['manage-customers-noqj'],
									'perms' => 'admin_zerobs_customers',
									'order' => 1, 'wpposition' => 1,
									'callback' => 'zeroBSCRM_render_customersNoQJlist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Tags (sub)
		$menu['contacts']['subitems']['tags'] = array(
									'title' => __('Contact','zero-bs-crm').' '.__('Tags','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['tagmanager'].'&tagtype=contact',
									'perms' => 'admin_zerobs_customers',
									'order' => 2, 'wpposition' => 2,
									'callback' => '',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Segments (sub)
		$menu['contacts']['subitems']['segments'] = array(
									'title' => __('Segments','zero-bs-crm'),
									'url' => $zbs->slugs['segments'],
									'perms' => 'admin_zerobs_customers',
									'order' => 3, 'wpposition' => 3,
									'callback' => 'zeroBSCRM_render_segmentslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Export Tools (sub)
		$menu['contacts']['subitems']['export'] = array(
									'title' => __('Export','zero-bs-crm'),
									'url' => $zbs->slugs['zbs-export-tools'], //prev: customer-search'],
									'perms' => 'admin_zerobs_customers',
									'order' => 4, 'wpposition' => 4,
									'callback' => 'zeroBSCRM_page_exportRecords', // prev.'zeroBSCRM_customersearch',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_exportTools')
							);

		// Add New (sub)
		$menu['contacts']['subitems']['addnew'] = array(
									'title' => __('Add New','zero-bs-crm').' '.__('Contact','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['addedit'].'&action=edit&zbstype=contact',
									'perms' => 'admin_zerobs_customers',
									'order' => 99, 'wpposition' => 99,
									'callback' => '', // not used? this is just a 'link'?
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);


	}
	// ===================================================
	// ======= / Contact Menu (non-slimline)
	// ===================================================	



	// ===================================================
	// ======= Company Menu (non-slimline)
	// ===================================================
	if ($b2bMode && $zbsMenuMode != ZBS_MENU_SLIM){

		// Companies (Top Level)
		$menu['companies'] = array(
									'ico' => 'dashicons-store',
									'title' => __(zeroBSCRM_getCompanyOrOrgPlural(),'zero-bs-crm'),
									'url' => $zbs->slugs['managecompanies'],
									'perms' => 'admin_zerobs_view_customers',
									'order' => 11, 'wpposition' => 25,
									'subitems' => array(),
									'callback' => 'zeroBSCRM_render_companyslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Tags (sub)
		$menu['companies']['subitems']['tags'] = array(
									'title' => __(zeroBSCRM_getCompanyOrOrg(),'zero-bs-crm').' '.__('Tags','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['tagmanager'].'&tagtype=company',
									'perms' => 'admin_zerobs_customers',
									'order' => 2, 'wpposition' => 2,
									'callback' => '',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Add New (sub)
		$menu['companies']['subitems']['addnew'] = array(
									'title' => __('Add New','zero-bs-crm').' '.__(zeroBSCRM_getCompanyOrOrg(),'zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['addedit'].'&action=edit&zbstype=company',
									'perms' => 'admin_zerobs_customers',
									'order' => 99, 'wpposition' => 99,
									'callback' => '', // not used? this is just a 'link'?
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

	}
	// ===================================================
	// ======= / Company Menu (non-slimline)
	// ===================================================

	// ===================================================
	// ======= Quote Menu (non-slimline)
	// ===================================================
	if ($useQuotes && $zbsMenuMode != ZBS_MENU_SLIM){

		// Quotes (Top Level)
		$menu['quotes'] = array(
									'ico' => 'dashicons-clipboard',
									'title' => __('Quotes','zero-bs-crm'),
									'url' => $zbs->slugs['managequotes'],
									'perms' => 'admin_zerobs_view_quotes',
									'order' => 10, 'wpposition' => 30,
									'subitems' => array(),
									'callback' => 'zeroBSCRM_render_quoteslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Tags (sub)
		$menu['quotes']['subitems']['tags'] = array(
									'title' => __('Quote','zero-bs-crm').' '.__('Tags','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['tagmanager'].'&tagtype=quote',
									'perms' => 'admin_zerobs_quotes',
									'order' => 2, 'wpposition' => 2,
									'callback' => '',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Tags (sub)
		$menu['quotes']['subitems']['tags'] = array(
									'title' => __('Quote','zero-bs-crm').' '.__('Tags','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['tagmanager'].'&tagtype=quote',
									'perms' => 'admin_zerobs_customers',
									'order' => 3, 'wpposition' => 3,
									'callback' => '',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Templates (sub)
		$menu['quotes']['subitems']['templates'] = array(
									'title' => __('Quote Templates','zero-bs-crm'),
									'url' => $zbs->slugs['quote-templates'],
									'perms' => 'admin_zerobs_quotes',
									'order' => 4, 'wpposition' => 4,
									'callback' => 'zeroBSCRM_render_quotetemplateslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Add New (sub)
		$menu['quotes']['subitems']['addnew'] = array(
									'title' => __('Add New','zero-bs-crm').' '.__('Quote','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['addedit'].'&action=edit&zbstype=quote',
									'perms' => 'admin_zerobs_quotes',
									'order' => 99, 'wpposition' => 99,
									'callback' => '', // not used? this is just a 'link'?
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

	}
	// ===================================================
	// ======= / Quote Menu (non-slimline)
	// ===================================================

	// ===================================================
	// ======= Invoice Menu (non-slimline)
	// ===================================================
	if ($useInvoices && $zbsMenuMode != ZBS_MENU_SLIM){

		// Invoices (Top Level)
		$menu['invoices'] = array(
									'ico' => 'dashicons-media-text',
									'title' => __('Invoices','zero-bs-crm'),
									'url' => $zbs->slugs['manageinvoices'],
									'perms' => 'admin_zerobs_view_invoices',
									'order' => 11, 'wpposition' => 30,
									'subitems' => array(),
									'callback' => 'zeroBSCRM_render_invoiceslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')//zeroBSCRM_scriptStyles_admin_invoiceBuilder
							);

		// Tags (sub)
		$menu['invoices']['subitems']['tags'] = array(
									'title' => __('Invoice','zero-bs-crm').' '.__('Tags','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['tagmanager'].'&tagtype=invoice',
									'perms' => 'admin_zerobs_view_invoices',
									'order' => 2, 'wpposition' => 2,
									'callback' => '',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Tags (sub)
		$menu['invoices']['subitems']['tags'] = array(
									'title' => __('Invoice','zero-bs-crm').' '.__('Tags','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['tagmanager'].'&tagtype=invoice',
									'perms' => 'admin_zerobs_customers',
									'order' => 3, 'wpposition' => 3,
									'callback' => '',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Add New (sub)
		$menu['invoices']['subitems']['addnew'] = array(
									'title' => __('Add New','zero-bs-crm').' '.__('Invoice','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['addedit'].'&action=edit&zbstype=invoice',
									'perms' => 'admin_zerobs_view_invoices',
									'order' => 99, 'wpposition' => 99,
									'callback' => '', // not used? this is just a 'link'?
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_scriptStyles_admin_invoiceBuilder')
							);

	}
	// ===================================================
	// ======= / Invoice Menu (non-slimline)
	// ===================================================

	// ===================================================
	// ======= Transaction Menu (non-slimline)
	// ===================================================
	if ($useTransactions && $zbsMenuMode != ZBS_MENU_SLIM){

		// Transactions (Top Level)
		$menu['transactions'] = array(
									'ico' => 'dashicons-cart',
									'title' => __('Transactions','zero-bs-crm'),
									'url' => $zbs->slugs['managetransactions'],
									'perms' => 'admin_zerobs_view_transactions',
									'order' => 12, 'wpposition' => 30,
									'subitems' => array(),
									'callback' => 'zeroBSCRM_render_transactionslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Tags (sub)
		$menu['transactions']['subitems']['tags'] = array(
									'title' => __('Transaction','zero-bs-crm').' '.__('Tags','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['tagmanager'].'&tagtype=transaction',
									'perms' => 'admin_zerobs_view_transactions',
									'order' => 2, 'wpposition' => 2,
									'callback' => '',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Add New (sub)
		$menu['transactions']['subitems']['addnew'] = array(
									'title' => __('Add New','zero-bs-crm').' '.__('Transaction','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['addedit'].'&action=edit&zbstype=transaction',
									'perms' => 'admin_zerobs_view_transactions',
									'order' => 99, 'wpposition' => 99,
									'callback' => '', // not used? this is just a 'link'?
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

	}
	// ===================================================
	// ======= / Transaction Menu (non-slimline)
	// ===================================================

	// ===================================================
	// ======= Forms Menu (non-slimline)
	// ===================================================
	if ($useForms && $zbsMenuMode != ZBS_MENU_SLIM){

		// Forms (Top Level)
		$menu['forms'] = array(
									'ico' => 'dashicons-welcome-widgets-menus',
									'title' => __('Forms','zero-bs-crm'),
									'url' => $zbs->slugs['manageformscrm'],
									'perms' => 'admin_zerobs_forms',
									'order' => 12, 'wpposition' => 35,
									'subitems' => array(),
									'callback' => 'zeroBSCRM_render_formslist_page',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_listview')
							);

		// Add New (sub)
		$menu['forms']['subitems']['addnew'] = array(
									'title' => __('Add New','zero-bs-crm').' '.__('Form','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['addedit'].'&action=edit&zbstype=form',
									'perms' => 'admin_zerobs_forms',
									'order' => 99, 'wpposition' => 99,
									'callback' => '', // not used? this is just a 'link'?
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_scriptStyles_admin_formBuilder')
							);

	}
	// ===================================================
	// ======= / Forms Menu (non-slimline)
	// ===================================================

	// ===================================================
	// ======= Task Scheduler Menu (non-slimline)
	// ===================================================
	if ($useCalendar && $zbsMenuMode != ZBS_MENU_SLIM){

		// Task Scheduler (Top Level)
		$menu['calendar'] = array(
									'ico' => 'dashicons-calendar-alt',
									'title' => __('Task Scheduler','zero-bs-crm'),
									'url' => $zbs->slugs['manage-events'],
									'perms' => 'admin_zerobs_events',
									'order' => 13, 'wpposition' => 35,
									'subitems' => array(),
									'callback' => 'zeroBSCRM_render_eventslist_page',
									'stylefuncs' => array(
										'zeroBSCRM_global_admin_styles',
										'zeroBSCRM_calendar_admin_styles'
									)
							);

		// Tags (sub)
		$menu['calendar']['subitems']['tags'] = array(
									'title' => __('Task','zero-bs-crm').' '.__('Tags','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['tagmanager'].'&tagtype=task',
									'perms' => 'admin_zerobs_customers',
									'order' => 3, 'wpposition' => 3,
									'callback' => '',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Add New (sub)
		$menu['calendar']['subitems']['addnew'] = array(
									'title' => __('Add New','zero-bs-crm').' '.__('Task','zero-bs-crm'),
									'url' => 'admin.php?page='.$zbs->slugs['addedit'].'&action=edit&zbstype=event',
									'perms' => 'admin_zerobs_events',
									'order' => 99, 'wpposition' => 99,
									'callback' => '', // not used? this is just a 'link'?
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_calendar_admin_styles')
							);

	}
	// ===================================================
	// ======= / Forms Menu (non-slimline)
	// ===================================================






	// ===================================================
	// ======= Data Tools (non-slimline)
	// ===================================================	
	if ($zbsMenuMode != ZBS_MENU_SLIM){

		// in non-slimline, data tools is added as a main menu item

		// Data Tools (Top Level)
		$menu['datatools'] = array(
									'ico' => 'dashicons-admin-tools',
									'title' => __('Data Tools','zero-bs-crm'),
									'url' => $zbs->slugs['datatools'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 90, 'wpposition' => 90,
									'subitems' => array(),
									'callback' => 'zeroBSCRM_pages_datatools',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

	}
	// ===================================================
	// ======= / Data Tools (non-slimline)
	// ===================================================	



	// ===================================================
	// ======= Settings
	// ===================================================	

		// Settings (Top Level)
		$adminMenuTitle = 'Zero BS CRM'; if ($zbsMenuMode == ZBS_MENU_SLIM) $adminMenuTitle = 'ZBS ' . __('Settings',"zero-bs-crm");
		$menu['settings'] = array(
									'ico' => 'dashicons-admin-settings',
									'title' => $adminMenuTitle,
									'url' => $zbs->slugs['home'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 100, 'wpposition' => 100,
									'subitems' => array(),
									'callback' => 'zeroBSCRM_pages_home',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_intro_admin_styles')
							);

		// Welcome to ZBS (sub)
		$menu['settings']['subitems']['welcome'] = array(
									'title' => __('Welcome to ZBS','zero-bs-crm'),
									'url' => $zbs->slugs['home'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_home',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_intro_admin_styles')
							);

		// System Status (sub)
		$menu['settings']['subitems']['systemstatus'] = array(
									'title' => __('System Status','zero-bs-crm'),
									'url' => $zbs->slugs['systemstatus'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 2,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_systemstatus',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Feedback (sub)
		$menu['settings']['subitems']['feedback'] = array(
									'title' => __('Feedback','zero-bs-crm'),
									'url' => $zbs->slugs['feedback'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 3,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_feedback',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Settings (sub)
		$menu['settings']['subitems']['settings'] = array(
									'title' => __('Settings','zero-bs-crm'),
									'url' => $zbs->slugs['settings'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 4,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_settings',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_settingspage_admin_styles')
							);


		// Extensions (sub)
		$menu['settings']['subitems']['extensions'] = array(
									'title' => '<span style="color: #FCB214 !important;">'.__('Extensions','zero-bs-crm').'</span>',
									'url' => $zbs->slugs['extensions'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 10,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_extensions',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_extension_admin_styles','zeroBSCRM_settingspage_admin_styles')
							);



	// ===================================================
	// ======= / Settings
	// ===================================================	


	// ===================================================
	// ======= Hidden
	// ===================================================	

		// Add/Edit (hidden)
		$menu['hidden']['subitems']['addedit'] = array(
									'title' => __('Add New','zero-bs-crm'),
									'url' => $zbs->slugs['addedit'],
									'perms' => 'admin_zerobs_customers',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_admin_addedit',
									'stylefuncs' => array(
															'zeroBSCRM_global_admin_styles',
															'zeroBSCRM_admin_styles_ui2_editview', // this func also subdivides by type (e.g. loads invoice builder styles/scripts)
															'zeroBSCRM_load_libs_js_momentdatepicker',

													)
							);	

		// File Edit (hidden)
		$menu['hidden']['subitems']['fileedit'] = array(
									'title' => __('Edit File','zero-bs-crm'),
									'url' => $zbs->slugs['editfile'],
									'perms' => 'admin_zerobs_customers',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_edit_file',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Tag Manager (hidden)
		$menu['hidden']['subitems']['tagmanager'] = array(
									'title' => __('Tags','zero-bs-crm'),
									'url' => $zbs->slugs['tagmanager'],
									'perms' => 'admin_zerobs_customers',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_admin_tags',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_ui2_editview')
							);

		// Export Tools (hidden)
		$menu['hidden']['subitems']['exporttools'] = array(
									'title' => __('Export Tools','zero-bs-crm'),
									'url' => $zbs->slugs['zbs-export-tools'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_page_exportRecords', // prev. zeroBSCRM_pages_export_tools
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_admin_styles_exportTools')
							);
		$menu['hidden']['subitems']['legacyexporttools'] = array(
									'title' => __('Legacy Export Tools','zero-bs-crm'),
									'url' => $zbs->slugs['legacy-zbs-export-tools'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_export_tools',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Notifications (hidden)
		$menu['hidden']['subitems']['notifications'] = array(
									'title' => __('Notifications','zero-bs-crm'),
									'url' => $zbs->slugs['notifications'],
									'perms' => 'admin_zerobs_notifications',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_admin_notifications',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Team (hidden)
		$menu['hidden']['subitems']['team'] = array(
									'title' => __('Team','zero-bs-crm'),
									'url' => $zbs->slugs['team'],
									'perms' => 'manage_options',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_admin_team',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Send Email (hidden)
		$menu['hidden']['subitems']['sendemail'] = array(
									'title' => __('Send Email','zero-bs-crm'),
									'url' => $zbs->slugs['sendmail'],
									'perms' => 'admin_zerobs_customers',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_admin_sendmail',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Emails (hidden)
		$menu['hidden']['subitems']['emails'] = array(
									'title' => __('Emails','zero-bs-crm'),
									'url' => $zbs->slugs['emails'],
									'perms' => 'admin_zerobs_customers',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_emails_UI',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_email_styles')
							);

		// Email Templates (hidden)
		$menu['hidden']['subitems']['emailtemplates'] = array(
									'title' => __('Email Templates','zero-bs-crm'),
									'url' => $zbs->slugs['email-templates'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_admin_system_emails',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Deactivation Error (hidden)
		$menu['hidden']['subitems']['deactivationerr'] = array(
									'title' => __('Deactivation error','zero-bs-crm'),
									'url' => $zbs->slugs['extensions-active'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_admin_deactivate_error',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Your Profile (hidden)
		$menu['hidden']['subitems']['yourprofile'] = array(
									'title' => __('Your Profile','zero-bs-crm'),
									'url' => $zbs->slugs['your-profile'],
									'perms' => 'admin_zerobs_customers',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_admin_your_profile',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Reminders (hidden)
		$menu['hidden']['subitems']['reminders'] = array(
									'title' => __('Reminders','zero-bs-crm'),
									'url' => $zbs->slugs['reminders'],
									'perms' => 'admin_zerobs_customers',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_admin_reminders',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Trashed (hidden)
		$menu['hidden']['subitems']['trashed'] = array(
									'title' => __('Trashed','zero-bs-crm'),
									'url' => $zbs->slugs['zbs-deletion'],
									'perms' => 'admin_zerobs_customers',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_postdelete',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// No Permissions (hidden)
		$menu['hidden']['subitems']['norights'] = array(
									'title' => __('No Permission','zero-bs-crm'),
									'url' => $zbs->slugs['zbs-noaccess'],
									'perms' => 'admin_zerobs_customers',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_pages_norights',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Migration DB2 (hidden)
		$menu['hidden']['subitems']['migratedb2'] = array(
									'title' => __('Migration','zero-bs-crm'),
									'url' => $zbs->slugs['migratedb2contacts'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_page_contactMigrationDB2',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles')
							);

		// Migration DB3 (hidden)
		$menu['hidden']['subitems']['migratedb3'] = array(
									'title' => __('Migration','zero-bs-crm'),
									'url' => $zbs->slugs['migratedal3'],
									'perms' => 'admin_zerobs_manage_options',
									'order' => 1,
									'wpposition' => 1,
									'callback' => 'zeroBSCRM_page_migrationDB3',
									'stylefuncs' => array('zeroBSCRM_global_admin_styles','zeroBSCRM_betaFeedback_styles') // beta feedback styles for RC
							);


	// ===================================================
	// ======= / Hidden
	// ===================================================	


	return $menu;

}

// takes ZBS formatted menu items + adds via wp menu system.
function zeroBSCRM_menu_applyWPMenu($menu=array()){

	// takeover mode, if active
	$menu = zeroBSCRM_menu_applyTakeover($menu);

	// cycle through menu + submenus + add to wp
	if (is_array($menu)) foreach ($menu as $menuItemKey => $menuItem){

		// pump em through
		zeroBSCRM_menu_add_toplevel($menuItemKey,$menuItem);

	}

}

// Takeover mode
// v3.0 + 
// identify if takeover mode on, if so, murder the wp menus other than zbs (this setting needs a WARNING!)
function zeroBSCRM_menu_applyTakeover($menu=false){

	global $zbs;
	
    #} Get the admin layout option 1 = Full, 2 = Slimline, 3 = CRM Only
    $zbsMenuMode = zeroBSCRM_getSetting('menulayout');
    if (!isset($zbsMenuMode) || !in_array($zbsMenuMode,array(1,2,3))) $zbsMenuMode = 2; #} Defaults to slimline

	#} Only for zbs custom user role users or all if flagged
	$takeoverModeAll = $zbs->settings->get('wptakeovermodeforall');
	$takeoverModeZBS = $zbs->settings->get('wptakeovermode');  
	$takeoverMode = false; if ($takeoverModeAll || (zeroBSCRM_permsIsZBSUser() && $takeoverModeZBS)) $takeoverMode = true;

	#} Menu mode specific overrides
    if ($zbsMenuMode == ZBS_MENU_CRMONLY){
    	$takeoverModeAll = true;
    	$takeoverModeZBS = true;
    	$takeoverMode  = true;
    }


	if ($takeoverMode){

		#if (isset($settings['wptakeovermode']) && $settings['wptakeovermode'] == 1 && zeroBSCRM_permsIsZBSUser()) {
			#https://codex.wordpress.org/Function_Reference/remove_menu_page
			remove_menu_page( 'index.php' );                  //Dashboard
			remove_menu_page( 'edit-tags.php?taxonomy=category' );                   //They appear to have for posts..  
			/*
				#} They wont have perms for all these anyhow :)
			remove_menu_page( 'edit.php' );                   //Posts
			remove_menu_page( 'upload.php' );                 //Media
			remove_menu_page( 'edit.php?post_type=page' );    //Pages
			remove_menu_page( 'edit-comments.php' );          //Comments
			remove_menu_page( 'themes.php' );                 //Appearance
			remove_menu_page( 'plugins.php' );                //Plugins
			remove_menu_page( 'users.php' );                  //Users
			remove_menu_page( 'tools.php' );                  //Tools
			remove_menu_page( 'options-general.php' );        //Settings
			*/

			if ($takeoverModeAll){

			    remove_menu_page( 'edit-tags.php?taxonomy=category' ); //They appear to have for posts weirdly
			    remove_menu_page( 'index.php' );                  //Dashboard
			    remove_menu_page( 'edit.php' );                   //Posts
			    remove_menu_page( 'upload.php' );                 //Media
			    remove_menu_page( 'edit.php?post_type=page' );    //Pages
			    remove_menu_page( 'edit-comments.php' );          //Comments
			    remove_menu_page( 'themes.php' );                 //Appearance
			    remove_menu_page( 'plugins.php' );                //Plugins
			    remove_menu_page( 'users.php' );                  //Users
			    remove_menu_page( 'tools.php' );                  //Tools
			    remove_menu_page( 'options-general.php' );        //Settings

			}

			#} Remove profile :) http://stackoverflow.com/questions/4524612/remove-profile-admin-menu-from-administrative-panel
			remove_menu_page('profile.php');

			#} Logout :)
			//$adminMenuLogout = add_menu_page( __('Log Out',"zero-bs-crm"), __('Log Out',"zero-bs-crm"), 'read', $zbs->slugs['logout'], 'zeroBSCRM_pages_logout', 'dashicons-unlock',100);
			//add_action( "admin_print_styles-{$adminMenuLogout}", 'zeroBSCRM_global_admin_styles' ); 

			// Add logout (Top Level)
			$menu['logout'] = array(
										'ico' => 'dashicons-unlock',
										'title' => __('Log Out','zero-bs-crm'),
										'url' => $zbs->slugs['logout'],
										'perms' => 'read',
										'order' => 999,
										'wpposition' => 999,
										'subitems' => array(),
										'callback' => 'zeroBSCRM_pages_logout',
										'stylefuncs' => array('zeroBSCRM_global_admin_styles')
								);


		}

	return $menu;

}


// Works through each menu item + subitem and validates current user has perms to see it
function zeroBSCRM_menu_securityGuard($menu=array()){

	// WORTH NOTING:
	// 'hidden' array checks for zbs_dash permissions. So all hidden wp pages are not going to work
	// ... for users who can't 'zbs_dash'
	
	$nMenu = array(); $userCaps = zeroBSCRM_getCurrentUserCaps();

	if (is_array($menu)) foreach ($menu as $topMenuKey => $topMenu){

		// got perms?
		if (isset($topMenu['perms']) && !empty($topMenu['perms'])){

			// user has perm for this top level menu?
			if (in_array($topMenu['perms'],$userCaps)){

				// user has permissions, lets add, but check each sub item too
				$toAdd = $topMenu; $toAdd['subitems'] = array();

				// check sub items
				if (is_array($topMenu['subitems'])) foreach ($topMenu['subitems'] as $subMenuKey => $subMenu){

					// got perms?
					if (isset($subMenu['perms']) && !empty($subMenu['perms'])){

						// user has perm for this sub level menu?
						if (in_array($subMenu['perms'],$userCaps)){

							// user has permissions, add to top menu subitems arr
							$toAdd['subitems'][$subMenuKey] = $subMenu;

						}

					}


				} // / check sub items

				// add
				$nMenu[$topMenuKey] = $toAdd;

			} // / user has cap for top menu
			
		} // / top menu has perms attr

	} // / top level menu item

	return $nMenu;

}


// Order menu items + subitems based on 'order'
function zeroBSCRM_menu_order($menu=array()){

	$nMenu = array();

	// first sort subitems
	if (is_array($menu)) foreach ($menu as $topMenuKey => $topMenu){

		// user has permissions, lets add, but check each sub item too
		$toAdd = $topMenu;

		// got subitems?
		if (isset($topMenu['subitems']) && !empty($topMenu['subitems'])){

			// sort subitems
			uasort($toAdd['subitems'],"zeroBSCRM_menu_order_sort");
			
		} // / top menu has subitems attr

		// add
		$nMenu[$topMenuKey] = $toAdd;

	} // / top level menu item (sort subitems)

	// Now sort toplevel:
	uasort($nMenu,"zeroBSCRM_menu_order_sort");

	// return ordered menu
	return $nMenu;

}

// Sort Func for: Order menu items + subitems based on 'order'
function zeroBSCRM_menu_order_sort($a, $b)
{
	// catch
	if (!is_array($a) || !is_array($b)) return false;
	if (!isset($a['order']) || !is_array($b['order'])) return false;

    if ($a['order'] == $b['order']) {
        return 0;
    }
    return ($a['order'] < $b['order']) ? -1 : 1;
}


// adds a toplevel menu item, and its subitems to wp menus:
function zeroBSCRM_menu_add_toplevel($menuItemKey='',$menuItem=-1)
{
	if (is_array($menuItem)){

		//echo 'adding '.$menuItemKey.' ('.count($menuItem['subitems']).')<br>';

		// here's a catch, this catches all "hidden" (null) submenu items, 
		// a hack which lets us add wp pages which are not menu-listed.
		if ($menuItemKey == 'hidden'){

			// Hidden subitems only in this one.

			// Any (hidden) subpages to add?
			if (isset($menuItem['subitems']) && is_array($menuItem['subitems'])){
				foreach ($menuItem['subitems'] as $subMenuKey => $subMenuItem){
					
					// Add the item
					// ...passing false for toplevel item, which sets these to hidden
					zeroBSCRM_menu_add_sublevel(false,$subMenuKey,$subMenuItem);

				} // / foreach subitem

			} // / if subitems

		} else {

			// NORMAL menu item + subitems

			// WP Menu add (traditional way)
			// ... this 'doubles' up on perms + ordering
			// https://developer.wordpress.org/reference/functions/add_menu_page/
			$adminPage = add_menu_page( 
												$menuItem['title'], //'Zero BS CRM ' . __('Plugin',"zero-bs-crm"), 
												$menuItem['title'], //$adminMenuTitle, 
												$menuItem['perms'], //'admin_zerobs_manage_options', 
												$menuItem['url'], //$zbs->slugs['home'], 
												$menuItem['callback'], //'zeroBSCRM_pages_home'
												((isset($menuItem['ico'])) ? $menuItem['ico'] : ''),
												((isset($menuItem['wpposition'])) ? $menuItem['wpposition'] : null)
										);
			
			// any style callbacks to enqueue?
			if (isset($menuItem['stylefuncs']) && is_array($menuItem['stylefuncs'])) 
				foreach ($menuItem['stylefuncs'] as $styleFunc){
					add_action( "admin_print_styles-{$adminPage}", $styleFunc );
				}

			// Any subpages to add?
			if (isset($menuItem['subitems']) && is_array($menuItem['subitems'])){
				foreach ($menuItem['subitems'] as $subMenuKey => $subMenuItem){
					
					// Add the item
					// this is split into subfunc as we also use it for null menus :)
					zeroBSCRM_menu_add_sublevel($menuItem,$subMenuKey,$subMenuItem);

				} // / foreach subitem

			} // / if subitems


		} // / NORMAL MENU

	} // if menuitem is array

}


// adds a sublevel menu item:
// to add a "HIDDEN" secret menu, pass $menuItem = false, and rest correct
function zeroBSCRM_menu_add_sublevel($menuItem=-1,$subMenuKey=-1,$subMenuItem=-1)
{
	if (is_array($subMenuItem)){

		// https://developer.wordpress.org/reference/functions/add_submenu_page/
		$adminSubPage = add_submenu_page( 
											(is_array($menuItem) && isset($menuItem['url'])) ? $menuItem['url'] : null, // parent slug
											$subMenuItem['title'], //__('Tags',"zero-bs-crm"), 
											$subMenuItem['title'], //__('Tags',"zero-bs-crm"), 
											$subMenuItem['perms'], //'admin_zerobs_customers', 
											$subMenuItem['url'], //$zbs->slugs['tagmanager'], 
											$subMenuItem['callback'] //'zeroBSCRM_pages_admin_tags' 
										);

		// any style callbacks to enqueue?
		if (isset($subMenuItem['stylefuncs']) && is_array($subMenuItem['stylefuncs'])) 
			foreach ($subMenuItem['stylefuncs'] as $subStyleFunc){
				add_action( "admin_print_styles-{$adminSubPage}", $subStyleFunc );
			}


	}
}
/* ======================================================
   / v3 ZBS Menu Arr -> WP Admin Menu Associated Funcs
   ====================================================== */





/* ======================================================
   PRE v3 WP Admin Menu Associated Funcs
   ====================================================== */

#} Add le admin menu
function zeroBSCRM_admin_menu() {

	global $zbs;

	#} Retrieve settings
	// $settings = $zbs->settings->getAll();

	#} If in b2b mode
	$b2bMode = zeroBSCRM_getSetting('companylevelcustomers');

	#} Check enabled (used multi-times)
	$ZBSuseCalendar = zeroBSCRM_getSetting('feat_calendar');

	#} Check transactions enabled
	$ZBSuseTrans = zeroBSCRM_getSetting('feat_transactions');

    #} Get the admin layout option 1 = Full, 2 = Slimline, 3 = CRM Only
    $zbsMenuMode = zeroBSCRM_getSetting('menulayout');
    if (!isset($zbsMenuMode) || !in_array($zbsMenuMode,array(1,2,3))) $zbsMenuMode = 2; #} Defaults to slimline


    #} Left in place, probably harmless. Mike integration code: 
    // NOTE: should be named more common sense, this doesn't explain at all where it fires in the queue..
	do_action('zerobs_importers');  //expose this area to add_action('zerobs_importers','zerobs_add_submenu')

	#} GO - ZBS ADMIN MENU:
	$adminMenuTitle = 'Zero BS CRM';  #} Default, if full menus
	if ($zbsMenuMode == ZBS_MENU_SLIM) $adminMenuTitle = 'ZBS ' . __('Settings',"zero-bs-crm");


	$adminPageHome = add_menu_page( 'Zero BS CRM ' . __('Plugin',"zero-bs-crm"), $adminMenuTitle, 'admin_zerobs_manage_options', $zbs->slugs['home'], 'zeroBSCRM_pages_home'); #_screen - was zeroBSCRM_pages_welcome_screen
	add_action( "admin_print_styles-{$adminPageHome}", 'zeroBSCRM_global_admin_styles' );
	add_action( "admin_print_styles-{$adminPageHome}", 'zeroBSCRM_intro_admin_styles' );
	
	#} 2.0.2 modified + combined 2 welcome pages..
	# This has to have the same callback + slug :)
	# https://wordpress.stackexchange.com/questions/200144/how-to-remove-duplicate-sub-menu-name-for-top-level-menu-items-in-a-plugin
    $adminMenuWelcome = add_submenu_page( $zbs->slugs['home'], __('Welcome to ZBS',"zero-bs-crm"), __('Welcome to ZBS',"zero-bs-crm"), 'admin_zerobs_manage_options', $zbs->slugs['home'], 'zeroBSCRM_pages_home' );
    add_action( "admin_print_styles-{$adminMenuWelcome}", 'zeroBSCRM_global_admin_styles' );
    add_action( "admin_print_styles-{$adminMenuWelcome}", 'zeroBSCRM_intro_admin_styles' );


    #} For my Export Improved UI I want a specific page here but NOT to be added anywhere to the menu
    $adminMenuWelcome = add_submenu_page( null, __('Export Tools',"zero-bs-crm"), __('Export Tools',"zero-bs-crm"), 'admin_zerobs_manage_options', $zbs->slugs['zbs-export-tools'], 'zeroBSCRM_pages_export_tools' );
    add_action( "admin_print_styles-{$adminMenuWelcome}", 'zeroBSCRM_global_admin_styles' );


	$adminManageT = add_submenu_page( null, __('Add New',"zero-bs-crm"), __('Add Edit',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['addedit'], 'zeroBSCRM_pages_admin_addedit' );
	add_action( "admin_print_styles-{$adminManageT}", 'zeroBSCRM_global_admin_styles' );
	add_action( "admin_print_styles-{$adminManageT}", "zeroBSCRM_admin_styles_ui2_editview");
	// WH - do we need this:?
	//add_action( "admin_print_styles-{$adminManageT}", "zeroBSCRM_admin_styles_chartjs");

	// Tag Manager
	$adminManageT = add_submenu_page( null, __('Tags',"zero-bs-crm"), __('Tags',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['tagmanager'], 'zeroBSCRM_pages_admin_tags' );
	add_action( "admin_print_styles-{$adminManageT}", 'zeroBSCRM_global_admin_styles' );
	add_action( "admin_print_styles-{$adminManageT}", "zeroBSCRM_admin_styles_ui2_editview");


	$adminManageT = add_submenu_page( null, __('Notifications',"zero-bs-crm"), __('Notifications',"zero-bs-crm"), 'admin_zerobs_notifications', $zbs->slugs['notifications'], 'zeroBSCRM_pages_admin_notifications' );

	add_action( "admin_print_styles-{$adminManageT}", 'zeroBSCRM_global_admin_styles' );


	$adminManageT = add_submenu_page( null, __('Team',"zero-bs-crm"), __('Team',"zero-bs-crm"), 'manage_options', $zbs->slugs['team'], 'zeroBSCRM_pages_admin_team' );

	add_action( "admin_print_styles-{$adminManageT}", 'zeroBSCRM_global_admin_styles' );


	//single file edit page..
	$adminManageF = add_submenu_page( null, __('Edit File','zerobscrm'), __('Edit File','zerobscrm'), 'admin_zerobs_customers', $zbs->slugs['editfile'], 'zeroBSCRM_pages_edit_file' );

	add_action( "admin_print_styles-{$adminManageF}", 'zeroBSCRM_global_admin_styles' );





	$adminManageT = add_submenu_page( null, __('Send Email',"zero-bs-crm"), __('Send Email',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['sendmail'], 'zeroBSCRM_pages_admin_sendmail' );
	add_action( "admin_print_styles-{$adminManageT}", 'zeroBSCRM_global_admin_styles' );

	#} new EMAILS page
	$adminEmails = add_submenu_page( null, __('Emails',"zero-bs-crm"), __('Emails',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['emails'], 'zeroBSCRM_emails_UI' );
	add_action( "admin_print_styles-{$adminEmails}", 'zeroBSCRM_global_admin_styles' );
	add_action( "admin_print_styles-{$adminEmails}", 'zeroBSCRM_email_styles' );



	$systemE = add_submenu_page( null, __('Email Templates','zero-bs-crm'), __('Email Templates','zero-bs-crm'), 'admin_zerobs_manage_options', $zbs->slugs['email-templates'], 'zeroBSCRM_pages_admin_system_emails' );
	add_action( "admin_print_styles-{$systemE}", 'zeroBSCRM_global_admin_styles' );


	#} Deactivation Error Page
	$errorDeactivate = add_submenu_page( null, __('Deactivation error', 'zero-bs-crm'), __('Deactivation error', 'zero-bs-crm'), 'admin_zerobs_manage_options', $zbs->slugs['extensions-active'], 'zeroBSCRM_pages_admin_deactivate_error' );
	add_action( "admin_print_styles-{$errorDeactivate}", 'zeroBSCRM_global_admin_styles' );


	#} Your Profile
	$yourProfile = add_submenu_page( null, __('Your Profile','zero-bs-crm'), __('Your Profile','zero-bs-crm'), 'admin_zerobs_customers', $zbs->slugs['your-profile'], 'zeroBSCRM_pages_admin_your_profile' );
	add_action( "admin_print_styles-{$yourProfile}", 'zeroBSCRM_global_admin_styles' );

	#} Reminders (simpler than tasks) can expose via API so we can do things like in slack
	#} /zbs_remind @woody to not be an angry bear
	$yourReminders = add_submenu_page( null, __('Reminders','zero-bs-crm'), __('Reminders','zero-bs-crm'), 'admin_zerobs_customers', $zbs->slugs['reminders'], 'zeroBSCRM_pages_admin_reminders' );
	add_action( "admin_print_styles-{$yourReminders}", 'zeroBSCRM_global_admin_styles' );


    #} Add welcome page as subpage
    #$adminMenuWelcome = add_submenu_page( $zbs->slugs['home'], 'Welcome to ZBS', 'Welcome to ZBS', 'manage_options', $zbs->slugs['welcome'], 'zeroBSCRM_pages_welcome_screen' );
    #add_action( "admin_print_styles-{$adminMenuWelcome}", 'zeroBSCRM_global_admin_styles' );
  
    #} System Status
    $adminMenuStatus = add_submenu_page( $zbs->slugs['home'], __('System Status',"zero-bs-crm"), __('System Status',"zero-bs-crm"), 'admin_zerobs_manage_options', $zbs->slugs['systemstatus'], 'zeroBSCRM_pages_systemstatus' );
	add_action( "admin_print_styles-{$adminMenuStatus}", 'zeroBSCRM_global_admin_styles' );   
    
    #} Feedback
    $adminMenuFeedback = add_submenu_page( $zbs->slugs['home'], __('Feedback',"zero-bs-crm"), __('Feedback',"zero-bs-crm"), 'admin_zerobs_manage_options', $zbs->slugs['feedback'], 'zeroBSCRM_pages_feedback' );
	add_action( "admin_print_styles-{$adminMenuFeedback}", 'zeroBSCRM_global_admin_styles' ); 
 

    #} Settings
    $adminMenuSettings = add_submenu_page( $zbs->slugs['home'], __('Settings',"zero-bs-crm"), __('Settings',"zero-bs-crm"), 'admin_zerobs_manage_options', $zbs->slugs['settings'], 'zeroBSCRM_pages_settings' );
	add_action( "admin_print_styles-{$adminMenuSettings}", 'zeroBSCRM_global_admin_styles' );
	add_action( "admin_print_styles-{$adminMenuSettings}", 'zeroBSCRM_settingspage_admin_styles' ); 

	##WLREMOVE
	#LICENSING3.0
	//    $adminMenuSettings = add_submenu_page( $zbs->slugs['home'], __('License',"zero-bs-crm"), __('License',"zero-bs-crm"), 'manage_options', $zbs->slugs['connect'], 'zeroBSCRM_pages_connectZBS' );
	//		add_action( "admin_print_styles-{$adminMenuSettings}", 'zeroBSCRM_global_admin_styles' ); 
	##/WLREMOVE


    ##add_action( 'admin.php', 'zeroBSCRM_load_libs_js_momentdatepicker' ); #} Datepicker for settings
	do_action('zbs-pre-extension-menu');


    #} Extensions
    $adminMenuExtend = add_submenu_page( $zbs->slugs['home'], __('Extensions',"zero-bs-crm"), '<span style="color: #FCB214 !important;">'. __('Extensions',"zero-bs-crm") .'</span>', 'admin_zerobs_manage_options', $zbs->slugs['extensions'], 'zeroBSCRM_pages_extensions' );
	add_action( "admin_print_styles-{$adminMenuExtend}", 'zeroBSCRM_global_admin_styles' );
	add_action( "admin_print_styles-{$adminMenuExtend}", 'zeroBSCRM_extension_admin_styles' ); 


	    #} Add hidden "trashed" page
    	#} This is a submenu of a submenu on purpose, as level 3 is never shown... but it's needed to allow wp to easily know where deletion url is
	    # http://wordpress.stackexchange.com/questions/73622/add-an-admin-page-but-dont-show-it-on-the-admin-menu
		$adminMenuTrash = add_submenu_page( null , __('Trash Msg',"zero-bs-crm"), __('Trash Msg',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['zbs-deletion'], 'zeroBSCRM_pages_postdelete', 1);
		add_action( "admin_print_styles-{$adminMenuTrash}", 'zeroBSCRM_global_admin_styles' ); 
		#} LIKEWISE, this is used as a "No rights" when using ownershup :)
		$adminMenuNoAccess = add_submenu_page( null , __('No Rights Msg',"zero-bs-crm"), __('No Rights Msg',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['zbs-noaccess'], 'zeroBSCRM_pages_norights', 1);
		add_action( "admin_print_styles-{$adminMenuNoAccess}", 'zeroBSCRM_global_admin_styles' ); 


    #} Data Tools (added 1.1.17 for inclusion of bulk delete admin tools) (removed from csv importer)
    #} Will only show for WP admins
	$adminMenuData = add_menu_page( __('Data Tools',"zero-bs-crm"), __('Data Tools',"zero-bs-crm"), 'admin_zerobs_manage_options', $zbs->slugs['datatools'], 'zeroBSCRM_pages_datatools', 'dashicons-admin-tools',90); #plugins_url('i/icon.png',__FILE__));
	add_action( "admin_print_styles-{$adminMenuData}", 'zeroBSCRM_global_admin_styles' ); 

	#} Migration DB2 page:
	$adminManageMigration = add_submenu_page( null, __('DB2.0 Update - Contacts',"zero-bs-crm"), __('DB2.0 Update - Contacts',"zero-bs-crm"), 'admin_zerobs_manage_options', $zbs->slugs['migratedb2contacts'], 'zeroBSCRM_page_contactMigrationDB2' );
	add_action( "admin_print_styles-{$adminManageMigration}", 'zeroBSCRM_global_admin_styles' );



	#} Migration DB3 page:
	$adminManageMigration = add_submenu_page( null, __('DB3.0 Update',"zero-bs-crm"), __('DB3.0 Update',"zero-bs-crm"), 'admin_zerobs_manage_options', $zbs->slugs['migratedal3'], 'zeroBSCRM_page_migrationDB3' );
	add_action( "admin_print_styles-{$adminManageMigration}", 'zeroBSCRM_global_admin_styles' );
	add_action( "admin_print_styles-{$adminManageMigration}", 'zeroBSCRM_betaFeedback_styles' );




	#} Import
 	//   add_submenu_page( $zbs->slugs['home'], 'Import', 'Import', 'manage_options', $zbs->slugs['import'], 'zeroBSCRM_pages_datatools' );

	#} Export
  	//  add_submenu_page( $zbs->slugs['datatools'], 'Export', 'Export', 'manage_options', $zbs->slugs['export'], 'zeroBSCRM_pages_export' );


    #} Remove trans for MVP (for mike) can remove this once prettied...  #transactions
	// remove_menu_page( 'edit.php?post_type=zerobs_transaction' );

    #} Added to tabs add_submenu_page( $zbs->slugs['home'], 'Custom Fields', 'Custom Fields', 'manage_options', $zbs->slugs['customfields'], 'zeroBSCRM_pages_customfields' );
    do_action('zerobs_extrasubmenu');   // add extra submenu
    #} WH Libs for Styles + lang
    #add_submenu_page( $zbs->slugs['home'], 'Styles', 'Styles', 'manage_options', $zbs->slugs['whstyles'], 'zeroBSCRM_pages_stylesettings' );
    #add_submenu_page( $zbs->slugs['home'], 'Language', 'Language', 'manage_options', $zbs->slugs['whlang'], 'zeroBSCRM_pages_langsettings' );
    #} Improved way:
    # not needed, goes under settings:
    #$adminMenuLanguage = add_submenu_page( $zbs->slugs['home'], 'Language', 'Language', 'manage_options', $zbs->slugs['whlang'], 'zeroBSCRM_pages_langsettings' );
	#add_action( "admin_print_styles-{$adminMenuLanguage}", 'zeroBSCRM_global_admin_styles' ); 


    #} If in override mode, remove dashboard option

		#} Only for zbs custom user role users or all if flagged
		$takeoverModeAll = $zbs->settings->get('wptakeovermodeforall');
		$takeoverModeZBS = $zbs->settings->get('wptakeovermode');  
		$takeoverMode = false; if ($takeoverModeAll || (zeroBSCRM_permsIsZBSUser() && $takeoverModeZBS)) $takeoverMode = true;

	#} Menu mode specific overrides
    if ($zbsMenuMode == ZBS_MENU_CRMONLY){
    	$takeoverModeAll = true;
    	$takeoverModeZBS = true;
    	$takeoverMode  = true;
    }


	if ($takeoverMode){
	#if (isset($settings['wptakeovermode']) && $settings['wptakeovermode'] == 1 && zeroBSCRM_permsIsZBSUser()) {
		#https://codex.wordpress.org/Function_Reference/remove_menu_page
		remove_menu_page( 'index.php' );                  //Dashboard
		remove_menu_page( 'edit-tags.php?taxonomy=category' );                   //They appear to have for posts..  
		/*
			#} They wont have perms for all these anyhow :)
		remove_menu_page( 'edit.php' );                   //Posts
		remove_menu_page( 'upload.php' );                 //Media
		remove_menu_page( 'edit.php?post_type=page' );    //Pages
		remove_menu_page( 'edit-comments.php' );          //Comments
		remove_menu_page( 'themes.php' );                 //Appearance
		remove_menu_page( 'plugins.php' );                //Plugins
		remove_menu_page( 'users.php' );                  //Users
		remove_menu_page( 'tools.php' );                  //Tools
		remove_menu_page( 'options-general.php' );        //Settings
		*/

		if ($takeoverModeAll){

		    remove_menu_page( 'edit-tags.php?taxonomy=category' ); //They appear to have for posts weirdly
		    remove_menu_page( 'index.php' );                  //Dashboard
		    remove_menu_page( 'edit.php' );                   //Posts
		    remove_menu_page( 'upload.php' );                 //Media
		    remove_menu_page( 'edit.php?post_type=page' );    //Pages
		    remove_menu_page( 'edit-comments.php' );          //Comments
		    remove_menu_page( 'themes.php' );                 //Appearance
		    remove_menu_page( 'plugins.php' );                //Plugins
		    remove_menu_page( 'users.php' );                  //Users
		    remove_menu_page( 'tools.php' );                  //Tools
		    remove_menu_page( 'options-general.php' );        //Settings

		}

		#} REmove profile :) http://stackoverflow.com/questions/4524612/remove-profile-admin-menu-from-administrative-panel
		//remove_submenu_page('users.php', 'profile.php');
		remove_menu_page('profile.php');

		#} Logout :)
	    #global $submenu;
	    #$submenu['index.php'] = array( 'Log Out', 'admin_zerobs_usr' , wp_logout_url() ); 
	    #print_r($submenu);
		$adminMenuLogout = add_menu_page( __('Log Out',"zero-bs-crm"), __('Log Out',"zero-bs-crm"), 'read', $zbs->slugs['logout'], 'zeroBSCRM_pages_logout', 'dashicons-unlock',100);
		add_action( "admin_print_styles-{$adminMenuLogout}", 'zeroBSCRM_global_admin_styles' ); 



	}
   

   // because we're using manage_categories, for some users post category editor will come up, this squashes that
   if (zeroBSCRM_permsIsZBSUser()) remove_menu_page( 'edit-tags.php?taxonomy=category' );
		

//	add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );

/* DAL 2 will not have "all customers" as CPT has been removed :)  

FOR SOME REASON THIS breaks manage contacts page, leaving for later ver...

if ($zbs->isDAL2() && 1 == 2){

	#} is double bubble with capability in add_submenu_page
	if (zeroBSCRM_permsCustomers()){

	    #} ZBS Manually adde customer menu, for those using full/crm menu styles
		$adminMenuContacts = add_menu_page( __('Contacts',"zero-bs-crm"), __('Manage Contacts',"zero-bs-crm"), 'admin_zerobs_view_customers', $zbs->slugs['managecontacts'], 'zeroBSCRM_render_customerslist_page', 'dashicons-admin-users',5);
		add_action( "admin_print_styles-{$adminMenuContacts}", 'zeroBSCRM_global_admin_styles' ); 

	    #} Add NOQJ customers page to custom post type
	    #} if setting!
	    if (isset($settings['showneedsquote']) && $settings['showneedsquote'] == 1){
	    	$adminNeedsAQ = add_submenu_page( $zbs->slugs['managecontacts'], __('Needs a Quote',"zero-bs-crm"), __('Needs a Quote',"zero-bs-crm"), 'admin_zerobs_view_customers', 'manage-customers-noqj', 'zeroBSCRM_render_customersNoQJlist_page' );
			add_action( "admin_print_styles-{$adminNeedsAQ}", 'zeroBSCRM_global_admin_styles' ); 

	    }
	    
		#} Add manage companies page to custom post type
	    if ($b2bMode){
	    	$adminB2B = add_submenu_page( 'edit.php?post_type=zerobs_company', __('Manage '.zeroBSCRM_getCompanyOrOrgPlural(),"zero-bs-crm"), __('Manage '.zeroBSCRM_getCompanyOrOrgPlural(),"zero-bs-crm"), 'admin_zerobs_customers', 'manage-companies', 'zeroBSCRM_render_companyslist_page', 1);
	    	add_action( "admin_print_styles-{$adminB2B}", 'zeroBSCRM_global_admin_styles' ); 
	    	add_action( "admin_print_styles-{$adminB2B}", 'zeroBSCRM_admin_styles_ui2_listview' );
	    }

		#} Mikes Search: 'tools.php' (now Export?)
		// WH: is this required in main menu now?
		$zbscsearch = add_submenu_page($zbs->slugs['managecontacts'], __('Export',"zero-bs-crm"), __('Export',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['customer-search'], 'zeroBSCRM_customersearch', 2 );
		add_action( 'admin_print_styles-' . $zbscsearch, 'zbscrm_customer_search_custom_css' );
		add_action( "admin_print_styles-{$zbscsearch}", 'zeroBSCRM_global_admin_styles' );

		#} Add Contact ? leave for now, is on header menu
	}


} else { */

	// DAL1: 
	#} Remove "All Customers"
	#} 20th November - the add_submenu_page below is throwing up errors since post type isn't a menu so cannot add a submenu to a removed menu
	#} defining as null. WP menus re-written in v3.0 anyway. This will fix the debug notices until v3.0.
	$allCustomersPage = remove_submenu_page( 'edit.php?post_type=zerobs_customer', 'edit.php?post_type=zerobs_customer' );

	#} is double bubble with capability in add_submenu_page
	if (zeroBSCRM_permsCustomers()){

	    #} Add manage customers page to custom post type
	    $adminMenuCompany = add_submenu_page( null, __('Manage '.zeroBSCRM_getContactOrCustomer().'s',"zero-bs-crm"), __('Manage '.zeroBSCRM_getContactOrCustomer().'s',"zero-bs-crm"), 'admin_zerobs_view_customers', $zbs->slugs['managecontacts'], 'zeroBSCRM_render_customerslist_page', 1);
		add_action( "admin_print_styles-{$adminMenuCompany}", 'zeroBSCRM_global_admin_styles' ); 
		// ui 2: 
		add_action( "admin_print_styles-{$adminMenuCompany}", 'zeroBSCRM_admin_styles_ui2_listview' ); 
	
		if ($zbs->isDAL2()){
		    #} Add Segments
		    $adminMenuSegments = add_submenu_page(null, __('Segments',"zero-bs-crm"), __('Segments',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['segments'], 'zeroBSCRM_render_segmentslist_page', 1);
			add_action( "admin_print_styles-{$adminMenuSegments}", 'zeroBSCRM_global_admin_styles' ); 
			// ui 2: 
			add_action( "admin_print_styles-{$adminMenuSegments}", 'zeroBSCRM_admin_styles_ui2_listview' ); 
		}

			
	   
	    #} Add NOQJ customers page to custom post type
	    #} if setting!
	    if (isset($settings['showneedsquote']) && $settings['showneedsquote'] == 1){
	    	$adminNeedsAQ = add_submenu_page( null, __('Needs a Quote',"zero-bs-crm"), __('Needs a Quote',"zero-bs-crm"), 'admin_zerobs_view_customers', $zbs->slugs['manage-customers-noqj'], 'zeroBSCRM_render_customersNoQJlist_page' );
			add_action( "admin_print_styles-{$adminNeedsAQ}", 'zeroBSCRM_global_admin_styles' ); 

	    }
	    
		#} Add manage companies page to custom post type
	    if ($b2bMode){
	    	$adminB2B = add_submenu_page( 'edit.php?post_type=zerobs_company', __('Manage '.zeroBSCRM_getCompanyOrOrgPlural(),"zero-bs-crm"), __('Manage '.zeroBSCRM_getCompanyOrOrgPlural(),"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['managecompanies'], 'zeroBSCRM_render_companyslist_page', 1);
	    	add_action( "admin_print_styles-{$adminB2B}", 'zeroBSCRM_global_admin_styles' ); 
	    	add_action( "admin_print_styles-{$adminB2B}", 'zeroBSCRM_admin_styles_ui2_listview' );
	    }

		#} Mikes Search: 'tools.php' (now Export?)
		// WH: is this required in main menu now?
		$zbscsearch = add_submenu_page( null, __('Export',"zero-bs-crm"), __('Export',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['customer-search'], 'zeroBSCRM_customersearch', 2 );
		add_action( "admin_print_styles-{$zbscsearch}", 'zbscrm_customer_search_custom_css' );
		add_action( "admin_print_styles-{$zbscsearch}", 'zeroBSCRM_global_admin_styles' );



	    #} Re-arrange submenus :)
	    #} http://wordpress.stackexchange.com/questions/73006/re-ordering-admin-submenu-sections
	    
	    	#} Global
	    	global $submenu;

	    	#} Use this to debug:    print_r($submenu); exit();
	    	if (isset($submenu['edit.php?post_type=zerobs_customer'])){

	    		#} Find em
	    		$menuItems = array();
	    		foreach ($submenu['edit.php?post_type=zerobs_customer'] as $ind => $menuItem){

	    			if ($menuItem[2] == 'post-new.php?post_type=zerobs_customer') $menuItems['addnew'] = $menuItem;
	    			if ($menuItem[2] == 'edit-tags.php?taxonomy=zerobscrm_customertag&amp;post_type=zerobs_customer') $menuItems['tags'] = $menuItem;
	    			if ($menuItem[2] == $zbs->slugs['segments']) $menuItems['segments'] = $menuItem;
	    			if ($menuItem[2] == 'manage-customers') $menuItems['manage'] = $menuItem;
	    			if ($menuItem[2] == 'manage-customers-noqj') $menuItems['noqj'] = $menuItem;
	    			if ($menuItem[2] == 'customer-searching') $menuItems['custsearch'] = $menuItem;


	    		}

	    		#} order
	    		$finalArr = array();
	    		if (isset($menuItems['manage'])) $finalArr[] = $menuItems['manage'];
	    		if (isset($menuItems['noqj'])) $finalArr[] = $menuItems['noqj'];
	    		if (isset($menuItems['segments'])) $finalArr[] = $menuItems['segments'];
	    		if (zeroBSCRM_permsCustomersTags()){ if (isset($menuItems['tags'])) $finalArr[] = $menuItems['tags']; }
	    		if (isset($menuItems['custsearch'])) $finalArr[] = $menuItems['custsearch'];	    		
	    		if (isset($menuItems['addnew'])) $finalArr[] = $menuItems['addnew'];


	    		/*
		    	#} Invert these two
			    $arr = array();
			    if (isset($submenu['edit.php?post_type=zerobs_customer'][17])) $arr[] = $submenu['edit.php?post_type=zerobs_customer'][17]; //Manage customers
			    if (isset($submenu['edit.php?post_type=zerobs_customer'][18])) if (isset($settings['showneedsquote']) && $settings['showneedsquote'] == 1) $arr[] = $submenu['edit.php?post_type=zerobs_customer'][18]; //Needs Quote

			    if (zeroBSCRM_permsCustomersTags()){

				    if (isset($submenu['edit.php?post_type=zerobs_customer'][15])) $arr[] = $submenu['edit.php?post_type=zerobs_customer'][15]; //Work Tags
				    if (isset($submenu['edit.php?post_type=zerobs_customer'][16])) $arr[] = $submenu['edit.php?post_type=zerobs_customer'][16]; //Customer Tags

				}

			    if (isset($submenu['edit.php?post_type=zerobs_customer'][10])) $arr[] = $submenu['edit.php?post_type=zerobs_customer'][10]; //Add new
			    // Ignore 15 + 16 (which are tags management)

			    */
			    $submenu['edit.php?post_type=zerobs_customer'] = $finalArr;

			}

	    	#} Use this to debug:    print_r($submenu); exit();
	    	if ($b2bMode) if (isset($submenu['edit.php?post_type=zerobs_company'])){

	    		#} Find em
	    		$menuItems = array();
	    		foreach ($submenu['edit.php?post_type=zerobs_company'] as $ind => $menuItem){

	    			if ($menuItem[2] == 'post-new.php?post_type=zerobs_company') $menuItems['addnew'] = $menuItem;
	    			if ($menuItem[2] == 'edit-tags.php?taxonomy=zerobscrm_companytag&amp;post_type=zerobs_company') $menuItems['tags'] = $menuItem;
	    			if ($menuItem[2] == 'manage-companies') $menuItems['manage'] = $menuItem;


	    		}

	    		#} order
	    		$finalArr = array();
	    		if (isset($menuItems['manage'])) $finalArr[] = $menuItems['manage'];
	    		if (zeroBSCRM_permsCustomersTags()){ if (isset($menuItems['tags'])) $finalArr[] = $menuItems['tags']; }
	    		if (isset($menuItems['addnew'])) $finalArr[] = $menuItems['addnew'];

			    $submenu['edit.php?post_type=zerobs_company'] = $finalArr;

			}

	}

//if dal 2}



    #} Remove "All QUOTES"
	$allQuotesPage = remove_submenu_page( 'edit.php?post_type=zerobs_quote', 'edit.php?post_type=zerobs_quote' );

	#} is double bubble with capability in add_submenu_page
	if (zeroBSCRM_permsQuotes()){

	    #} Add manage bookings page to custom post type
	    $adminManageQ = add_submenu_page( 'edit.php?post_type=zerobs_quote', __('Manage Quotes',"zero-bs-crm"), __('Manage Quotes',"zero-bs-crm"), 'admin_zerobs_view_quotes', $zbs->slugs['managequotes'], 'zeroBSCRM_render_quoteslist_page' );
	    
	    add_action( "admin_print_styles-{$adminManageQ}", 'zeroBSCRM_global_admin_styles' ); 
	    add_action( "admin_print_styles-{$adminManageQ}", 'zeroBSCRM_admin_styles_ui2_listview' );

	    #} Re-arrange submenus :)
	    #} http://wordpress.stackexchange.com/questions/73006/re-ordering-admin-submenu-sections
	    
	    	#} Global
	    	global $submenu;

	    	#} Use this to debug:   print_r($submenu); exit();


	    	if (isset($submenu['edit.php?post_type=zerobs_quote'])){
		    	
		    	#} Invert these two
			    $arr = array();
			    if (isset($submenu['edit.php?post_type=zerobs_quote'][11])) $arr[] = $submenu['edit.php?post_type=zerobs_quote'][11]; //Manage quotes
			    if (isset($submenu['edit.php?post_type=zerobs_quote'][10])) $arr[] = $submenu['edit.php?post_type=zerobs_quote'][10]; //Add new
			    // Ignore 15 + 16 (which are tags management)
			    $submenu['edit.php?post_type=zerobs_quote'] = $arr;		


				#} If using quote builder, display templates:
				#} NOTE: if "quotes" disabled totally, this won't have a menu to add too anyhow, due to the isset above this
				$useQuoteBuilder = zeroBSCRM_getSetting('usequotebuilder');

				if ($useQuoteBuilder == "1"){

				    #} Add manage customers page to custom post type
				    $adminMenuQuoteTemplates = add_submenu_page( 'edit.php?post_type=zerobs_quote', __('Quote Templates',"zero-bs-crm"), __('Quote Templates',"zero-bs-crm"), 'admin_zerobs_quotes', $zbs->slugs['quote-templates'], 'zeroBSCRM_render_quotetemplateslist_page', 1);


					add_action( "admin_print_styles-{$adminMenuQuoteTemplates}", 'zeroBSCRM_global_admin_styles' );

					#} Add secret sub page for edit :)
					//add_submenu_page ( 'manage-quote-templates', 'Test Menu', 'Child2', 'read', 'child2', '');
					//https://zbsphp5.dev/wp-admin/post-new.php?post_type=zerobs_quo_template


				    #} Remove "Quote template menu" #REMOVEQUOTEMPLATE - we have to add this so we can remove it, so that add new works!?!?
					$removedQuoteTemplatesPage = remove_menu_page( 'edit.php?post_type=zerobs_quo_template' );

				}

			} #  / isset at all

	}


    #} Remove "All INVOICES"
	$allInvoicesPage = remove_submenu_page( 'edit.php?post_type=zerobs_invoice', 'edit.php?post_type=zerobs_invoice' );


	$allEventsPage = remove_submenu_page( 'edit.php?post_type=zerobs_event', 'edit.php?post_type=zerobs_event' );

	if ($ZBSuseCalendar == "1" && zeroBSCRM_permsEvents()){
	
		#} Add manage events page to custom post type
	    $adminManageE = add_submenu_page( 'edit.php?post_type=zerobs_event', __('Task Scheduler',"zero-bs-crm"), __('Task Scheduler',"zero-bs-crm"), 'admin_zerobs_events', $zbs->slugs['manage-events'], 'zeroBSCRM_render_eventslist_page' );
		add_action( "admin_print_styles-{$adminManageE}", 'zeroBSCRM_global_admin_styles' );
		add_action( "admin_print_styles-{$adminManageE}", 'zeroBSCRM_calendar_admin_styles' );

		/*
	    $adminManageE = add_submenu_page( 'edit.php?post_type=zerobs_event', __('Completed Tasks',"zero-bs-crm"), __('Completed Tasks',"zero-bs-crm"), 'admin_zerobs_events', $zbs->slugs['manage-events-completed'], 'zeroBSCRM_render_eventslistcomplete_page' );
		add_action( "admin_print_styles-{$adminManageE}", 'zeroBSCRM_global_admin_styles' );
		add_action( "admin_print_styles-{$adminManageE}", 'zeroBSCRM_calendar_admin_styles' );
		*/
	
	    	#} Global
			
	    	global $submenu;

	    	#} Use this to debug:  	print_r($submenu); exit();
	    	if (isset($submenu['edit.php?post_type=zerobs_event'])){

	 
	    	#} Invert these two
			    $arr = array();


			    if (isset($submenu['edit.php?post_type=zerobs_event'][11])) $arr[] = $submenu['edit.php?post_type=zerobs_event'][11]; //Manage quotes

			    if (isset($submenu['edit.php?post_type=zerobs_event'][12])) $arr[] = $submenu['edit.php?post_type=zerobs_event'][12]; //Manage quotes
			    
			    if (isset($submenu['edit.php?post_type=zerobs_event'][10])) $arr[] = $submenu['edit.php?post_type=zerobs_event'][10]; //Add new
			    // Ignore 15 + 16 (which are tags management)
			    $submenu['edit.php?post_type=zerobs_event'] = $arr;

			}

			
	}



	#} is double bubble with capability in add_submenu_page
	if (zeroBSCRM_permsInvoices()){

	    #} Add manage bookings page to custom post type
	    $adminManageI = add_submenu_page( 'edit.php?post_type=zerobs_invoice', __('Manage Invoices',"zero-bs-crm"), __('Manage Invoices',"zero-bs-crm"), 'admin_zerobs_view_invoices', $zbs->slugs['manageinvoices'], 'zeroBSCRM_render_invoiceslist_page' );
		add_action( "admin_print_styles-{$adminManageI}", 'zeroBSCRM_global_admin_styles' );
		add_action( "admin_print_styles-{$adminManageI}", 'zeroBSCRM_admin_styles_ui2_listview' );

	    
	    #} Re-arrange submenus :)
	    #} http://wordpress.stackexchange.com/questions/73006/re-ordering-admin-submenu-sections
	    
	    	#} Global
	    	global $submenu;

	    	#} Use this to debug:  	print_r($submenu); exit();
	    	if (isset($submenu['edit.php?post_type=zerobs_invoice'])){

		    	#} Invert these two
			    $arr = array();
			    if (isset($submenu['edit.php?post_type=zerobs_invoice'][11])) $arr[] = $submenu['edit.php?post_type=zerobs_invoice'][11]; //Manage quotes
			    if (isset($submenu['edit.php?post_type=zerobs_invoice'][10])) $arr[] = $submenu['edit.php?post_type=zerobs_invoice'][10]; //Add new
			    // Ignore 15 + 16 (which are tags management)
			    $submenu['edit.php?post_type=zerobs_invoice'] = $arr;

			}

	}


    #} Remove "All INVOICES"
	$allTransactionsPage = remove_submenu_page( 'edit.php?post_type=zerobs_transaction', 'edit.php?post_type=zerobs_transaction' );

	#} is double bubble with capability in add_submenu_page
	if (zeroBSCRM_permsTransactions() && $ZBSuseTrans == 1){

	    #} Add manage bookings page to custom post type
	    $adminManageT = add_submenu_page( 'edit.php?post_type=zerobs_transaction', __('Manage Transactions',"zero-bs-crm"), __('Manage Transactions',"zero-bs-crm"), 'admin_zerobs_view_transactions', $zbs->slugs['managetransactions'], 'zeroBSCRM_render_transactionslist_page' );


	    add_action( "admin_print_styles-{$adminManageT}", 'zeroBSCRM_global_admin_styles' );
	    add_action( "admin_print_styles-{$adminManageT}", 'zeroBSCRM_admin_styles_ui2_listview' ); 

	    #} Re-arrange submenus :)
	    #} http://wordpress.stackexchange.com/questions/73006/re-ordering-admin-submenu-sections
	    
	    	#} Global
	    	global $submenu;

	    	#} Use this to debug:  	
	    	//print_r($submenu); exit();
	    	if (isset($submenu['edit.php?post_type=zerobs_transaction'])){

	
		    	#} Invert these
			    $arr = array();
			    if (isset($submenu['edit.php?post_type=zerobs_transaction'][16])) $arr[] = $submenu['edit.php?post_type=zerobs_transaction'][16]; //Manage 
			    if (isset($submenu['edit.php?post_type=zerobs_transaction'][10])) $arr[] = $submenu['edit.php?post_type=zerobs_transaction'][10]; //Add new
				
				/*
				This was breaking things in translated files since $menu[0] != 'Manage Transactions' think
				safer to remove it in general and go as per the above..
				$zbsFirstMenuItem = false;$zbsSecondMenuItem = false;$zbsThirdMenuItem = false;
			    foreach ($submenu['edit.php?post_type=zerobs_transaction'] as $key => $menu){

			    	switch ($menu[0]){

			    		case __('Manage Transactions','zero-bs-crm'):
			    			$zbsFirstMenuItem = $menu;
			    			break;
			    		case 'Transaction Tags':
			    			$zbsSecondMenuItem = $menu;
			    			break;
			    		case 'Add New':
			    			$zbsThirdMenuItem = $menu;
			    			break;

			    	}
			    }
			    $arr = array($zbsFirstMenuItem,$zbsSecondMenuItem,$zbsThirdMenuItem);
				*/
			    // Ignore 15 + 16 (which are tags management)
				$submenu['edit.php?post_type=zerobs_transaction'] = $arr;
				


			}

	} 



    #} Remove "All Forms" + replaces with manage forms
    $ZBSUseForms = zeroBSCRM_getSetting('feat_forms');
	if($ZBSUseForms == "1"){
	
		$allFormsPage = remove_submenu_page( 'edit.php?post_type=zerobs_form', 'edit.php?post_type=zerobs_form' );

		#} is double bubble with capability in add_submenu_page
		if (zeroBSCRM_isZBSAdminOrAdmin()){

		    #} Add manage page to custom post type
		    $adminUserD = add_submenu_page( 'edit.php?post_type=zerobs_form', __('Forms',"zero-bs-crm"), __('Forms',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['manageformscrm'], 'zeroBSCRM_render_formslist_page' );
		    
		    add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_global_admin_styles' ); 
		    add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_admin_styles_ui2_listview' );

		    #} Re-arrange submenus :)
		    #} http://wordpress.stackexchange.com/questions/73006/re-ordering-admin-submenu-sections
		    
		    	#} Global
		    	global $submenu;

		    	#} Use this to debug:   print_r($submenu); exit();

		    	if (isset($submenu['edit.php?post_type=zerobs_form'])){
			    	
			    	#} Invert these two
				    $arr = array();
				    if (isset($submenu['edit.php?post_type=zerobs_form'][11])) $arr[] = $submenu['edit.php?post_type=zerobs_form'][11]; //Manage
				    if (isset($submenu['edit.php?post_type=zerobs_form'][10])) $arr[] = $submenu['edit.php?post_type=zerobs_form'][10]; //Add new
				    // Ignore 15 + 16 (which are tags management)
				    $submenu['edit.php?post_type=zerobs_form'] = $arr;	

				} #  / isset at all

				#} Use this to debug:   print_r($submenu); exit();

		}

	} // if forms
		

    if ($zbsMenuMode == ZBS_MENU_SLIM){
    	//slimline removes all the CRM added custom post type menus...
    	remove_menu_page('edit.php?post_type=zerobs_customer');    //customers DAL1
    	remove_menu_page('admin.php?page='.$zbs->slugs['managecontacts']);    //customers DAL2
    	remove_menu_page('edit.php?post_type=zerobs_transaction'); 	//transactions
    	remove_menu_page('edit.php?post_type=zerobs_quote'); //quotes
    	remove_menu_page('edit.php?post_type=zerobs_invoice'); //invoices
    	$removedFormsMenu = remove_menu_page('edit.php?post_type=zerobs_form'); //forms
    	remove_menu_page('edit.php?post_type=zerobs_company'); //companies
    	remove_menu_page('edit.php?post_type=zerobs_event'); //events

    	//sync
    	#remove_menu_page('zerobscrm-sync'); //sync tools
    	//$removedDataTools = remove_menu_page('zerobscrm-datatools');  //data tools - we re-add this  to "ZBS CRM Admin"



    	/*
    	print_r($removedDataTools); exit();
		Array
		(
		    [0] => Data Tools
		    [1] => manage_options
		    [2] => zerobscrm-datatools
		    [3] => Data Tools
		    [4] => menu-top toplevel_page_zerobscrm-datatools
		    [5] => toplevel_page_zerobscrm-datatools
		    [6] => dashicons-admin-tools
		) 

	
		into:

		[zerobscrm-plugin] => Array
        (
            [0] => Array
                (
                    [0] => ZBS CRM Admin
                    [1] => manage_options
                    [2] => zerobscrm-plugin
                    [3] => Zero BS CRM Plugin
                )

            [1] => Array
                (
                    [0] => Welcome to ZBS
                    [1] => manage_options
                    [2] => zerobscrm-welcome
                    [3] => Welcome to ZBS
                )

            [2] => Array
                (
                    [0] => Extensions
                    [1] => manage_options
                    [2] => zerobscrm-extensions
                    [3] => Extensions
                )

            [3] => Array
                (
                    [0] => Settings
                    [1] => manage_options
                    [2] => zerobscrm-plugin-settings
                    [3] => Settings
                )

            [4] => Array
                (
                    [0] => System Status
                    [1] => manage_options
                    [2] => zerobscrm-systemstatus
                    [3] => System Status
                )

            [5] => Array
                (
                    [0] => Feedback
                    [1] => manage_options
                    [2] => zerobscrm-feedback
                    [3] => Feedback
                )

        )


		*/


    		#} Re-add data tools
    		global $submenu;
    		if (isset($removedDataTools) && isset($submenu['zerobscrm-plugin'])){

    			#} Hacky fix for prev being a top level arr (see debug above) - should rewrite this when we totally overhaul these menus
    			unset($removedDataTools[4],$removedDataTools[5],$removedDataTools[6]);


    			#} clone for a min
    			$menuArr = $submenu['zerobscrm-plugin'];

    			#} make a new one, injecting data-tools wherever we need to
    			$replacementMenu = array();
    			foreach ($menuArr as $menuItem){

    				#} inject before "System Status"
    				if ($menuItem[2] == 'zerobscrm-systemstatus') $replacementMenu[] = $removedDataTools;

    				#} add other
    				$replacementMenu[] = $menuItem;
    			}

    			#} replace
    			$submenu['zerobscrm-plugin'] = $replacementMenu;

    		}
	    	

    	//extensions - can do manually (only mail campaigns and sales dashboard exist after merge in of PayPal + Woo Sync + CSV importer)
    	//remove_menu_page('edit.php?post_type=zerobs_mailcampaign'); // #WHLOOKHERE - can we have a general remove admin menu pages for extensions func?
    	remove_menu_page('sales-dash'); //sales dashboard

		//$current_user = wp_get_current_user();
		//print_r($current_user); exit();

    	#} but adds easy access menu: WH addition, to discuss viability with MS
		$adminUserD = add_menu_page( __('Zero BS CRM User Dash',"zero-bs-crm"), __('Zero BS CRM',"zero-bs-crm"), 'zbs_dash', $zbs->slugs['dash'], 'zeroBSCRM_pages_dash','dashicons-groups',2);
		add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_global_admin_styles' );
		add_action( "admin_print_styles-{$adminUserD}", "zeroBSCRM_admin_styles_chartjs");
		add_action( "admin_print_styles-{$adminUserD}", "zeroBSCRM_admin_styles_homedash");


	    #} Add welcome page as subpage
	    if ($b2bMode){
	    	$adminUserD = add_submenu_page( $zbs->slugs['dash'], __(zeroBSCRM_getCompanyOrOrgPlural(),"zero-bs-crm"), __(zeroBSCRM_getCompanyOrOrgPlural(),"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['managecompanies'], 'zeroBSCRM_render_companyslist_page');
	    	add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_global_admin_styles' );
	    	add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_admin_styles_ui2_listview' );

	    }
	    $adminUserD = add_submenu_page( $zbs->slugs['dash'], __(zeroBSCRM_getContactOrCustomer().'s',"zero-bs-crm"), __(zeroBSCRM_getContactOrCustomer().'s',"zero-bs-crm"), 'admin_zerobs_view_customers', $zbs->slugs['managecontacts'], 'zeroBSCRM_render_customerslist_page', 1);
	    add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_global_admin_styles' );
		// ui 2: 
		add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_admin_styles_ui2_listview' ); 


		$ZBSuseQuotes = zeroBSCRM_getSetting('feat_quotes');
		if($ZBSuseQuotes == "1"){
		    $adminUserD = add_submenu_page( $zbs->slugs['dash'], __('Quotes',"zero-bs-crm"), __('Quotes',"zero-bs-crm"), 'admin_zerobs_view_quotes', $zbs->slugs['managequotes'], 'zeroBSCRM_render_quoteslist_page' );
		    add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_global_admin_styles' );
		    add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_admin_styles_ui2_listview' );
		}

		$ZBSuseInvoices = zeroBSCRM_getSetting('feat_invs');
		if($ZBSuseInvoices == "1"){
		    $adminUserD = add_submenu_page( $zbs->slugs['dash'], __('Invoices',"zero-bs-crm"), __('Invoices',"zero-bs-crm"), 'admin_zerobs_view_invoices', $zbs->slugs['manageinvoices'], 'zeroBSCRM_render_invoiceslist_page' );
			add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_global_admin_styles' );
			add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_admin_styles_ui2_listview' );
		}

		$ZBSUseForms = zeroBSCRM_getSetting('feat_forms');
		if($ZBSUseForms == "1"){
		    $adminUserD = add_submenu_page( $zbs->slugs['dash'], __('Forms',"zero-bs-crm"), __('Forms',"zero-bs-crm"), 'admin_zerobs_forms', $zbs->slugs['manageformscrm'], 'zeroBSCRM_render_formslist_page' );
			add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_global_admin_styles' );
			add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_admin_styles_ui2_listview' );
		}


		#} SHOULD ALSO BE OPTIONAL! post MVP
		if($ZBSuseTrans == "1"){
			$adminUserD = add_submenu_page( $zbs->slugs['dash'], __('Transactions',"zero-bs-crm"), __('Transactions',"zero-bs-crm"), 'admin_zerobs_view_transactions', $zbs->slugs['managetransactions'], 'zeroBSCRM_render_transactionslist_page' );
			add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_global_admin_styles' );
	   		add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_admin_styles_ui2_listview' ); 
		}

		// Declared above :) $ZBSuseCalendar = zeroBSCRM_getSetting('feat_calendar');
		if($ZBSuseCalendar == "1"){
		    $adminManageE = add_submenu_page( $zbs->slugs['dash'], __('Task Scheduler',"zero-bs-crm"), __('Task Scheduler',"zero-bs-crm"), 'admin_zerobs_view_events', $zbs->slugs['manage-events'], 'zeroBSCRM_render_eventslist_page' );
			add_action( "admin_print_styles-{$adminManageE}", 'zeroBSCRM_global_admin_styles' );
			add_action( "admin_print_styles-{$adminManageE}", 'zeroBSCRM_calendar_admin_styles' );
		}

	    #} Activity Search
	    /*

		add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '' )

	    */
	    $adminAdvancedSearch = add_submenu_page( $zbs->slugs['dash'], __('Advanced Search',"zero-bs-crm"), __('Advanced Search',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['advanced-search'], 'zeroBSCRM_advancedSearch' );
	    add_action( "admin_print_styles-{$adminAdvancedSearch}", 'zeroBSCRM_global_admin_styles' );


	    #} Add Segments
		if ($zbs->isDAL2()){
		    $adminMenuSegments = add_submenu_page( $zbs->slugs['dash'], __('Segments',"zero-bs-crm"), __('Segments',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['segments'], 'zeroBSCRM_render_segmentslist_page', 1);
			add_action( "admin_print_styles-{$adminMenuSegments}", 'zeroBSCRM_global_admin_styles' ); 
			// ui 2: 
			add_action( "admin_print_styles-{$adminMenuSegments}", 'zeroBSCRM_admin_styles_ui2_listview' ); 
		}
			
 
		##WLREMOVE
	    // install extensions helper
	    if(current_user_can('manage_options')){
	    $adminInstallExtensions = add_submenu_page( $zbs->slugs['dash'], __('Install Extensions',"zero-bs-crm"), __('Install Extensions',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['zerobscrm-install-helper'], 'zeroBSCRM_pages_installextensionshelper' );
	    add_action( "admin_print_styles-{$adminInstallExtensions}", 'zeroBSCRM_global_admin_styles' );
		} 
		##/WLREMOVE

		

	    #} Added forms 1.2.6

	    	#} Global
	    	global $submenu;


	    	#} Use this to debug:  	print_r($submenu); exit();
	    	if (zeroBSCRM_isExtensionInstalled('forms') && isset($submenu[$zbs->slugs['dash']])){

	    		#} if forms
	    		#$submenu[$zbs->slugs['dash']][] = $removedFormsMenu;
	    		#} Hacky, but works:
	    	//	$submenu[$zbs->slugs['dash']][] = array(__('Forms',"zero-bs-crm"),'edit_pages','edit.php?post_type=zerobs_form',__('Forms',"zero-bs-crm"));

	    	}

    
    	/* EXTRA ITEMS FOR CAPABILITIES TO SLIM MENU (e.g. PAYPAL SYNC ETC) */
    	    $ZBSuseQuotes = zeroBSCRM_getSetting('feat_quotes');
		    $ZBSuseInvoices = zeroBSCRM_getSetting('feat_invs');
		    $ZBSuseCal = zeroBSCRM_getSetting('feat_calendar');

		    #} Gather the URLS which navigated around the dashboard and use them to create sub-menus

		    if (zeroBSCRM_permsCustomers()){ 
		    $cust_tags    = get_admin_url('','admin.php?page='.$zbs->slugs['tagmanager'].'&tagtype=contact');
		    $cust_sear    = get_admin_url('','admin.php?page=customer-searching');
		    $cust_fields  = get_admin_url('','admin.php?page=zerobscrm-plugin-settings&tab=customfields'); 



		    } 

		    if ($ZBSuseQuotes == "1" && zeroBSCRM_permsQuotes()){ 
		    $man_quo    = get_admin_url('','admin.php?page=manage-quotes');
		    $quo_tem    =  get_admin_url('','edit.php?post_type=zerobs_quote&page=manage-quote-templates');
		    }

		    if ($ZBSuseInvoices == "1" && zeroBSCRM_permsInvoices()){ 
		    $man_inv = get_admin_url('','edit.php?post_type=zerobs_invoice&page=manage-invoices');
		    }

		    if($ZBSuseTrans == "1" && zeroBSCRM_permsTransactions()){
		    $man_tran = get_admin_url('','admin.php?page=manage-transactions');
		    }

		    if (zeroBSCRM_isExtensionInstalled('forms')){
		    $man_for = get_admin_url('','edit.php?post_type=zerobs_form');
		    }



		    $crm_users = get_admin_url('','user-new.php');

		    if (current_user_can( 'manage_options' )){

	

			      if (zeroBSCRM_isExtensionInstalled('pay')){ 
		    		$submenu[$zbs->slugs['dash']][] = array(__('PayPal Sync',"zero-bs-crm"),'edit_pages','admin.php?page='.$zbs->slugs['paypalsync'],__('PayPal Sync',"zero-bs-crm"));
			      } 
			      if (zeroBSCRM_isExtensionInstalled('woo')){ 
			      	$submenu[$zbs->slugs['dash']][] = array(__('WooSync',"zero-bs-crm"),'edit_pages','admin.php?page='.$zbs->slugs['woosync'],__('WooSync',"zero-bs-crm"));
			      }

			      if (zeroBSCRM_isExtensionInstalled('stripesync')){ 
			      	$submenu[$zbs->slugs['dash']][] = array(__('Stripe Sync',"zero-bs-crm"),'edit_pages','admin.php?page='.$zbs->slugs['stripesync'],__('Stripe Sync',"zero-bs-crm"));
			      }

			     /* Dealt with in-ext now (post v1.8) */
			     if (zeroBSCRM_isExtensionInstalled('mailcampaigns')){

			     	// if not v2+
			     	if (!function_exists('zeroBSCRM_mailCampaigns_v2')){

			      		$submenu[$zbs->slugs['dash']][] = array(__('Mail Campaigns',"zero-bs-crm"),'edit_pages','edit.php?post_type=zerobs_mailcampaign&page=manage-campaigns',__('Mail Campaigns',"zero-bs-crm"));

			      	}

			     } 

			   
			   if (zeroBSCRM_isExtensionInstalled('salesdash')){
			      	$submenu[$zbs->slugs['dash']][] = array(__('Sales Dashboard',"zero-bs-crm"),'edit_pages','admin.php?page='.$zbs->slugs['salesdash'],__('Sales Dashboard',"zero-bs-crm"));
			    }

	    		$submenu[$zbs->slugs['dash']][] = array(__('Data Tools',"zero-bs-crm"),'edit_pages','admin.php?page='.$zbs->slugs['datatools'],__('Data Tools',"zero-bs-crm"));

	    		if(zeroBSCRM_isExtensionInstalled('batchtagger')){
    				$submenu[$zbs->slugs['dash']][] = array(__('Bulk Tagger',"zero-bs-crm"),'edit_pages','admin.php?page='.$zbs->slugs['bulktagger'],__('Bulk Tagger',"zero-bs-crm"));
    			}



		      	$submenu[$zbs->slugs['dash']][] = array(__('Settings',"zero-bs-crm"),'edit_pages','admin.php?page='.$zbs->slugs['settings'],__('Settings',"zero-bs-crm"));

		      	// use our helper page
		      	//$submenu[$zbs->slugs['dash']][] = array(__('<span style="color: #FCB214 !important;">Install Extensions</span>',"zero-bs-crm"),'edit_pages','plugins.php',__('<span style="color: #FCB214 !important;">Install Extensions</span>',"zero-bs-crm"));
		      	##WLREMOVE
		      	$submenu[$zbs->slugs['dash']][] = array('<span style="color: #FCB214 !important;">'.__('Install Extensions',"zero-bs-crm").'</span>','edit_pages','zerobscrm-install-helper','<span style="color: #FCB214 !important;">'.__('Install Extensions',"zero-bs-crm").'</span>');		   
				##/WLREMOVE
		      	// remove old install extensions (just cycle through and then remove Install Extensions, because we re-add at the end)
		      	$newMenuArr = array(); $firstMenuItem = false; foreach ($submenu[$zbs->slugs['dash']] as $menuItem){

		      		if (isset($menuItem[0]) && $menuItem[0] != 'Install Extensions') 
		      			$newMenuArr[] = $menuItem;
		      		else {
		      			if ($firstMenuItem) 
		      				$newMenuArr[] = $menuItem;
		      			else 
		      				$firstMenuItem = true;
		      		}
		      		/* removes:
		      		Array
					(
					    [0] => Install Extensions
					    [1] => admin_zerobs_customers
					    [2] => zerobscrm-install-helper
					    [3] => Install Extensions
					)
					... leaves:

					Array
					(
					    [0] => <span style="color: #FCB214 !important;">Install Extensions</span>
					    [1] => edit_pages
					    [2] => zerobscrm-install-helper
					    [3] => <span style="color: #FCB214 !important;">Install Extensions</span>
					)
					*/

		      	}

		      	// allow ext etc. to filter menu items :)
				$newMenuArr = apply_filters( 'zbs_menu_mainwpmenu', $newMenuArr );

		      	// reset menu arr to our modified
		      	$submenu[$zbs->slugs['dash']] = $newMenuArr;


		      	// THIS was a conflict WH switched for above chunk on 7/9/17 (deploy/merge of 2.13) 
				//$submenu[$zbs->slugs['dash']][] = array(__('<span style="color: #FCB214 !important;">Install Extensions</span>',"zero-bs-crm"),'edit_pages','admin.php?page=zerobscrm-extensions',__('<span style="color: #FCB214 !important;">Install Extensions</span>',"zero-bs-crm"));


		  }

    }

    if ($zbsMenuMode == ZBS_MENU_FULL){
    	
    	#} add easy access menu to all : WH addition, to discuss viability with MS
		$adminUserD = add_menu_page( __('Zero BS CRM User Dash',"zero-bs-crm"), __('ZBS Dashboard',"zero-bs-crm"), 'zbs_dash', $zbs->slugs['dash'], 'zeroBSCRM_pages_dash','dashicons-groups',2);
	    add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_global_admin_styles' );
		add_action( "admin_print_styles-{$adminUserD}", "zeroBSCRM_admin_styles_chartjs");
		add_action( "admin_print_styles-{$adminUserD}", "zeroBSCRM_admin_styles_homedash");

		if($zbs->isDal2()){
			$zbsContacts = add_menu_page( __('Contacts',"zero-bs-crm"), __('Contacts',"zero-bs-crm"), 'admin_zerobs_view_customers', $zbs->slugs['managecontacts'], 'zeroBSCRM_render_customerslist_page','dashicons-admin-users',25); 
			add_action( "admin_print_styles-{$zbsContacts}", 'zeroBSCRM_global_admin_styles' );
			add_action( "admin_print_styles-{$zbsContacts}", 'zeroBSCRM_admin_styles_ui2_listview' );
		}

	    $adminAdvancedSearch = add_submenu_page( $zbs->slugs['dash'], __('Advanced Search',"zero-bs-crm"), __('Advanced Search',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['advanced-search'], 'zeroBSCRM_advancedSearch' );
	    add_action( "admin_print_styles-{$adminAdvancedSearch}", 'zeroBSCRM_global_admin_styles' );

    }

    if ($zbsMenuMode == ZBS_MENU_CRMONLY){
    	
    	#} add easy access menu to all : WH addition, to discuss viability with MS
		$adminUserD = add_menu_page( __('Zero BS CRM User Dash',"zero-bs-crm"), __('ZBS Dashboard',"zero-bs-crm"), 'zbs_dash', $zbs->slugs['dash'], 'zeroBSCRM_pages_dash','dashicons-groups',2);
    	add_action( "admin_print_styles-{$adminUserD}", 'zeroBSCRM_global_admin_styles' );
		add_action( "admin_print_styles-{$adminUserD}", "zeroBSCRM_admin_styles_chartjs");
		add_action( "admin_print_styles-{$adminUserD}", "zeroBSCRM_admin_styles_homedash");

		if($zbs->isDal2()){
			$zbsContacts = add_menu_page( __('Contacts',"zero-bs-crm"), __('Contacts',"zero-bs-crm"), 'admin_zerobs_view_customers', $zbs->slugs['managecontacts'], 'zeroBSCRM_render_customerslist_page','dashicons-admin-users',25); 
			add_action( "admin_print_styles-{$zbsContacts}", 'zeroBSCRM_global_admin_styles' );
			add_action( "admin_print_styles-{$zbsContacts}", 'zeroBSCRM_admin_styles_ui2_listview' );
		}

	    $adminAdvancedSearch = add_submenu_page( $zbs->slugs['dash'], __('Advanced Search',"zero-bs-crm"), __('Advanced Search',"zero-bs-crm"), 'admin_zerobs_customers', $zbs->slugs['advanced-search'], 'zeroBSCRM_advancedSearch' );
	    add_action( "admin_print_styles-{$adminAdvancedSearch}", 'zeroBSCRM_global_admin_styles' );
	    
    }

	

	#} Add daterangepicker to "add quote" and "add invoice" pages:
    add_action( 'load-post.php', 'zeroBSCRM_load_libs_js_momentdatepicker' );
    #why was this here twice? add_action( 'load-post.php', 'zeroBSCRM_load_libs_js_momentdatepicker' );
    add_action( 'load-post-new.php', 'zeroBSCRM_load_libs_js_momentdatepicker' );
    #why was this here twice? add_action( 'load-post-new.php', 'zeroBSCRM_load_libs_js_momentdatepicker' );
    add_action( 'load-post-new.php', 'zeroBSCRM_load_libs_js_momentdatepicker' );

	#] sort menu so ZBS at top :)
	#} Leave until menu's rewrite zeroBSCRM_sortCPTMenu();

	#] add any seperator to the menu
	#} Leave until menu's rewrite zeroBSCRM_addSeperatorMenu();



}

// rather than using remove_Submenu_page
// https://codex.wordpress.org/Function_Reference/remove_submenu_page
// this just kills it out of the $submenu global (so page will still load if accessed directly)
// zeroBSCRM_menus_removeWPSubMenu($zbs->slugs['datatools'],'zerobscrm-csvimporterlite-app');
function zeroBSCRM_menus_removeWPSubMenu($slug='',$subpage=''){

    	#} Global
    	global $submenu;

    	#} Use this to debug:  	print_r($submenu); exit();
    	if (isset($submenu[$slug]) && is_array($submenu[$slug])){

    		$newArr = array();
    		foreach ($submenu[$slug] as $ind => $page){
    			if ($page[2] != $subpage) $newArr[] = $page;
    		}
    		$submenu[$slug] = $newArr;

    	}
}

/* ======================================================
   PRE v3 WP Admin Menu Associated Funcs
   ====================================================== */
