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

        THIS FILE IS FOR Learn Buttons - later to be unified into one .Menu file or RETHOUGHT

        !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!


*/

// populate these, and allow it to be filterable by extension (or rebrandr could offer this functionality)
// forces us to have them all complete too

require_once(ZEROBSCRM_PATH .'includes/ZeroBSCRM.Core.Menus.Learn.Links.php');






#} Adds our learn modal under the other menu :-) functionised..
// this big "switch" function is bit simple, in end we probs want to queue these from individual views
// so each func can be next to the view it tops, for now, so be it. (tidied 5/2/18 wh)
function zeroBSCRM_admin_subtop_menu(){

	global $zbs;

	#} GET the page slug..
	$slug = ''; if (isset($_GET['page'])) $slug = sanitize_text_field($_GET['page']);

	#} CUSTOM slugs to affect behavior of standard WP pages
	$zbsSlug = ''; if (isset($_GET['zbsslug'])) $zbsSlug = sanitize_text_field($_GET['zbsslug']);

	#} Menu out? (flag)
	$menuOut = false;

	// HERE we set pageKey to be slug (lazy global)
	// this is used for screenoptions, so will require you to set it wherever you want to use them (see userScreenOptions in core.php)
	// must be exposed via zeroBS_outputScreenOptions :)
	// note: for some 'sub pages' e.g. add-edit TYPE - this'll be appended to by functions below/above this level.
	// ... so if this is just 'root' we can override it, otherwise, don't (default)
	if ($zbs->pageKey == 'root') $zbs->pageKey = $slug;
	

	switch ($slug){

		case $zbs->slugs['home']:
			zeroBSCRM_home_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['dash']:
			zeroBSCRM_dashboard_learn_menu(); $menuOut = true;
			break;
		//case $zbs->slugs['app']:
			//zeroBSCRM_app_learn_menu(); $menuOut = true;
			//break;
		case $zbs->slugs['settings']:
			zeroBSCRM_settings_learn_menu(); $menuOut = true;
			break;
			break;
		//case $zbs->slugs['customfields']:
			//zeroBSCRM_customfields_learn_menu(); $menuOut = true;
			//break;
		case $zbs->slugs['logout']:
			//zeroBSCRM_logout_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['sync']:
			zeroBSCRM_sync_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['datatools']:
			zeroBSCRM_datatools_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['welcome']:
			zeroBSCRM_welcome_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['feedback']:
			zeroBSCRM_feedback_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['extensions']:
			zeroBSCRM_powerup_learn_menu(); $menuOut = true;
			break;
		//case $zbs->slugs['import']:
			//zeroBSCRM_import_learn_menu(); $menuOut = true;
			//break;
		case $zbs->slugs['export']:
			zeroBSCRM_export_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['systemstatus']:
			zeroBSCRM_systemstatus_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['connect']:
			zeroBSCRM_connect_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['segments']:
			zeroBSCRM_segmentlist_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['bulktagger']:
			zeroBSCRM_bulktagger_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['salesdash']:
			zeroBSCRM_salesdash_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['stripesync']:
			zeroBS_menuOutIfFunc('zeroBSCRM_stripesync_learn_menu'); $menuOut = true;
			break;
		case $zbs->slugs['woosync']:
			zeroBS_menuOutIfFunc('zeroBSCRM_woosync_learn_menu'); $menuOut = true;
			break;
		case $zbs->slugs['paypalsync']:
			zeroBS_menuOutIfFunc('zeroBSCRM_paypalsync_learn_menu'); $menuOut = true;
			break;
		case $zbs->slugs['managecontacts']:
		case $zbs->slugs['managecontactsprev']:
			zeroBSCRM_contactlist_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['managequotes']:
		case $zbs->slugs['managequotesprev']:
			zeroBSCRM_quotelist_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['managetransactions']:
		case $zbs->slugs['managetransactionsprev']:
			zeroBSCRM_translist_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['manageinvoices']:
		case $zbs->slugs['manageinvoicesprev']:
			zeroBSCRM_invoicelist_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['managecompanies']:
		case $zbs->slugs['managecompaniesprev']:
			zeroBSCRM_companylist_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['manageformscrm']:
		case $zbs->slugs['manageformscrmprev']:
			zeroBSCRM_formlist_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['addedit']:

			// if we have action, switch :)
			if (isset($_GET['action'])){

				//LATER we'll need to add a var &zbstype, which I've started adding via zbsLink func in dal legacy (for now, should go to general funcs)
				/* now dealt with by proper funcs below 
				if ($_GET['action'] == 'edit'){
					zeroBSCRM_contactedit_learn_menu2(); $menuOut = true;
				} else {
					zeroBSCRM_viewcontact_learn_menu(); $menuOut = true;
				} */
				if ($_GET['action'] != 'edit' && $_GET['action'] != 'delete'){

					// view (by type)
					//if (isset($_GET['type'])){
						//switch ($_GET['type']){
					if (isset($_GET['zbstype'])){

						$action = sanitize_text_field($_GET['zbstype']);

						switch ($action){

							case 'contact':
								zeroBSCRM_viewcontact_learn_menu(); $menuOut = true;
								break;

							case 'company':
								zeroBSCRM_viewcompany_learn_menu(); $menuOut = true;
								break;

							case 'segment':
								zeroBSCRM_segmentedit_learn_menu(); $menuOut = true;
								break;


							default:
								// no type
								// can default to contact. if no 'type' it'll be contact :)
								zeroBSCRM_viewcontact_learn_menu(); $menuOut = true;
								break;
						}
					} else {
						// can default to contact. if no 'type' it'll be contact :)
						zeroBSCRM_viewcontact_learn_menu(); $menuOut = true;
					}
				} else {

					// edit dealt with below? wtf?.
				}

			} else {

				zeroBSCRM_viewcontact_learn_menu(); $menuOut = true;

			} 
			break;
		case $zbs->slugs['sendmail']:
			zeroBSCRM_sendemail_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['notifications']:
			zeroBSCRM_notifications_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['team']:
			zeroBSCRM_team_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['customer-search']:
			zeroBSCRM_exportcontact_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['quote-templates']:
			zeroBSCRM_quotetemplate_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['zbs-export-tools']:
			zeroBSCRM_exporttools_learn_menu(); $menuOut = true;
			break;
		case $zbs->slugs['manage-events']:
			zeroBSCRM_tasklist_learn_menu(); $menuOut = true;
			break;

		case $zbs->slugs['migratedb2contacts']:
			zeroBSCRM_migratedb2contacts_learn_menu(); $menuOut = true;


	}



	#} Custom slugs :-)
	switch ($zbsSlug){

		case $zbs->slugs['zbs-new-user']:
			zeroBSCRM_team_add_learn_menu(); $menuOut = true;
			break;

		case $zbs->slugs['zbs-edit-user']:
			zeroBSCRM_team_edit_learn_menu(); $menuOut = true;
			break;

	}

	#} Specific page checks (which are just slightly more complex checks against slugs... )
	// DEF this needs rethinking (as per WH notes)
	

	// both use same func now :) it determines
	if(zeroBSCRM_is_customer_new_page() || zeroBSCRM_is_customer_edit_page()){
		zeroBSCRM_contactedit_learn_menu2(); $menuOut = true;
	} 

	if(zeroBSCRM_is_segment_new_page() || zeroBSCRM_is_segment_edit_page()){
		zeroBSCRM_segmentedit_learn_menu(); $menuOut = true;
	} 


	if(zeroBSCRM_is_customertags_page()){
		zeroBSCRM_contacttags_learn_menu(); $menuOut = true;
	}

	if(zeroBSCRM_is_companytags_page()){
		zeroBSCRM_companytags_learn_menu(); $menuOut = true;
	}

	if(zeroBSCRM_is_company_new_page() || zeroBSCRM_is_company_edit_page()){
		//zeroBSCRM_companynew_learn_menu(); 
		zeroBSCRM_companyedit_learn_menu2();
		$menuOut = true;
	}

	/*if(zeroBSCRM_is_company_edit_page()){
		zeroBSCRM_companyedit_learn_menu(); $menuOut = true;
	} */


	
	if(zeroBSCRM_is_invoice_new_page()){
		zeroBSCRM_invoicenew_learn_menu(); $menuOut = true;	
	} elseif (zeroBSCRM_is_invoice_edit_page()){
		zeroBSCRM_invoiceedit_learn_menu();	 $menuOut = true;
	}


	if (zeroBSCRM_is_transaction_new_page()){
		zeroBSCRM_transnew_learn_menu(); $menuOut = true;
	} elseif (zeroBSCRM_is_transaction_edit_page()){
		zeroBSCRM_transedit_learn_menu(); $menuOut = true;
	}
	if(zeroBSCRM_is_transactiontags_page()){
		zeroBSCRM_transactiontags_learn_menu(); $menuOut = true;
	}




	if(zeroBSCRM_is_task_edit_page()){
		zeroBSCRM_taskedit_learn_menu(); $menuOut = true;
	} elseif (zeroBSCRM_is_task_new_page()){
		zeroBSCRM_tasknew_learn_menu(); $menuOut = true;
	}



	if(zeroBSCRM_is_form_edit_page()){
		zeroBSCRM_formedit_learn_menu(); $menuOut = true;
	} elseif (zeroBSCRM_is_form_new_page()){
		zeroBSCRM_formnew_learn_menu(); $menuOut = true;
	}
	



	if(zeroBSCRM_is_quotem_new_page()){
		zeroBSCRM_quotetemplatenew_learn_menu(); $menuOut = true;
	} elseif (zeroBSCRM_is_quotem_edit_page()){
		zeroBSCRM_quotetemplateedit_learn_menu(); $menuOut = true;
	}


	if(zeroBSCRM_is_quo_new_page()){
		zeroBSCRM_quotenew_learn_menu(); $menuOut = true;
	} elseif (zeroBSCRM_is_quo_edit_page()){
		zeroBSCRM_quoteedit_learn_menu(); $menuOut = true;
	}

	#} profile
	if (zeroBSCRM_is_profile_page()){
		zeroBSCRM_profile_learn_menu(); $menuOut = true;
	}


	// GENERIC DELETE PAGE!
	if (zeroBSCRM_is_delete_page()){
		zeroBSCRM_delete_learn_menu(); $menuOut = true;
	}


	// for any exts to hook into :)
	do_action('zerobscrm-subtop-menu');
	
	
}


// Helper func to catch those pages without menus :/
function zeroBS_menuOutIfFunc($funcName=''){

	if (function_exists($funcName)) call_user_func($funcName);

}

/* =============================================================================
======== #} The LEARN MENUS for the $zbs->slugs - NEED TO FIGURE EXTENSIONS OUT TOO. 
============================================================================= */

/* 

	takes generic bits of these learn menus and smallifies the stuff so only needs changing once if changed later

	// takes in:
		- title
		- showLearn (if false, all learn stuff hidden)
		- h3 (if showLearn, is learn title)
		- learnContent = String of left hand side learn box content
		- learnImgVid = String of right hand side learn box content
		- hopscotchCustomJS = custom JS to override default hopscotch js 

	:)

*/
function zeroBS_genericLearnMenu($title='',$addNew='',$filterStr='',$showLearn=true,$h3='',$learnContent='',$learnMoreURL='',$learnImgURL='',$learnVidURL=false,$hopscothCustomJS=false,$popupExtraCSS=''){
 
	##WLREMOVE

	?><script type="text/javascript">

        jQuery(document).ready(function($){

	        <?php if ($showLearn){ ?>
	        jQuery('.learn')
	          .popup({
	            inline: false,
	            on:'click',
	            lastResort: 'bottom right',
	        });
	        <?php } ?>

          <?php /* this should never really be in generic
          if (empty($hopscothCustomJS)){ ?>
          if (typeof hopscotch != "undefined") { //  && hopscotch.getState() === "zbs-welcome-tour:4"
            hopscotch.startTour(window.zbsTour);
          } 
          <?php } else */
          echo $hopscothCustomJS; ?>


        });
    </script>

    <?php 
    ##/WLREMOVE

    // WL removes learn buttons - I don't think it does, but it did get us some freelance (and forced me to tidy it up / centralise links etc.)
    if (zeroBSCRM_isWL()) $showLearn = false;
	?>
    <style>
    	.wp-heading-inline, .page-title-action, #screen-meta, #screen-options-link-wrap{
    		display:none !important;
		}
		.wp-editor-wrap, .wp-switch-editor {
			position: relative;
			z-index: 1;
		}
	</style>
    <div id="zbs-admin-top-bar" style="margin-bottom:-20px;">
      <div id="zbs-list-top-bar">
          <h2 class="zbs-white"><span class="add-new-button"><?php echo $title; ?></span><?php if (!empty($addNew)) echo ' '.$addNew; ?>
          	<?php if ($showLearn){ ?>
            <div class="ui button grey tiny learn" id="learn"><i class="fa fa-graduation-cap" aria-hidden="true"></i> <?php _e('Learn',"zero-bs-crm"); ?></div>
            <div class="ui special popup top left transition hidden" id="learn-pop" style="<?php echo $popupExtraCSS; ?>">
              <h3 class="learn-h3"><?php echo $h3; ?></h3>
              <div class="content-left">
               	<?php echo $learnContent; 

               	if (!empty($learnMoreURL)){
               		echo '<br/><a href="'.$learnMoreURL.'" target="_blank" class="ui button orange">'.__("Learn More","zero-bs-crm").'</a>';
               	}
               	?>
              </div>
              <div class="video">
                  <?php 
                
                  if ($learnVidURL){
              		
              		?><iframe src="<?php echo $learnVidURL; ?>" width="385" height="207" frameborder="0" gesture="media" allow="encrypted-media" allowfullscreen style="margin-top:-15px;"></iframe><?php

              	} elseif (!empty($learnImgURL)){

              		echo '<p><img src="'.$learnImgURL.'" alt="'.$h3.'" /></p>';

              	} ?>
              </div>
            </div>
            <?php } ?>
            <?php if (!empty($filterStr)) echo $filterStr; ?>

            <?php // do_action('zbs_before_end_learn_bar'); ?>
          </h2>
        </div>
    </div><?php

}

#}Generates the learn content for a passed key
function zeroBS_generateLearnContent($key=''){
    global $zbs_learn_content_array;
    $learnContent = '';
    if($key != ''){
        if(array_key_exists($key,$zbs_learn_content_array)){
            $learnContent = $zbs_learn_content_array[$key];
            $learnContent = apply_filters('zbs_learn_'.$key.'_content', $learnContent);
        }
    }
    return $learnContent;
}

#}Generates the links (learn, image and video) accepts the key/slug of the link
function zeroBS_generateLearnLinks($key=''){
    global $zbs_learn_links_array, $zbs_learn_img_array, $zbs_learn_video_link_array;
    
    #}our default links
    $links = array(
        'learn' => 'https://zerobscrm.com/kb',
        'img'   => ZEROBSCRM_URL.'i/learn/learn-extensions-list.png',
        'vid'   => false
	);
	
	$zbs_learn_links_array = apply_filters('zbs_learn_links_array', $zbs_learn_links_array);

    if($key != ''){
        if(array_key_exists($key,$zbs_learn_links_array)){
            $links['learn'] = $zbs_learn_links_array[$key];
        }
        if(array_key_exists($key,$zbs_learn_img_array)){
            $links['img'] = $zbs_learn_img_array[$key];
        }
        if(array_key_exists($key,$zbs_learn_video_link_array)){
            $links['vid'] = $zbs_learn_video_link_array[$key];
        }
    }

    return $links;
}

#} Refined function for the learn buttons. FilterStr and addNew will make this a little more complex for some of the buttons
function zeroBSCRM_dashboard_learn_menu(){
    $title        = __('Dashboard','zero-bs-crm');
    $content      = zeroBS_generateLearnContent('dashboard');
    $links        = zeroBS_generateLearnLinks('dashboard');
	zeroBS_genericLearnMenu($title,'','',true,$title,$content,$links['learn'],$links['img'],$links['vid'],' //none','z-index: 9999999;');
}

function zeroBSCRM_contactnew_learn_menu(){
    $title        = __('Add Contact','zero-bs-crm');
    $content      = zeroBS_generateLearnContent('contactnew');
    $links        = zeroBS_generateLearnLinks('contactnew');	
	zeroBS_genericLearnMenu($title,'','',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');
}

function zeroBSCRM_contactedit_learn_menu(){
    $title        = __('Edit Contact','zero-bs-crm');
    $content      = zeroBS_generateLearnContent('contactedit');
    $links        = zeroBS_generateLearnLinks('contactedit');	
	zeroBS_genericLearnMenu($title,'','',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');
}

function zeroBSCRM_contacttags_learn_menu(){
    $title        = __('Contact Tags','zero-bs-crm');
    $content      = zeroBS_generateLearnContent('contacttags');
    $links        = zeroBS_generateLearnLinks('contacttags');	
	zeroBS_genericLearnMenu($title,'','',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');
}

function zeroBSCRM_companytags_learn_menu(){
    $title        = __('Company Tags','zero-bs-crm');
    $content      = zeroBS_generateLearnContent('companytags');
    $links        = zeroBS_generateLearnLinks('companytags');	
	zeroBS_genericLearnMenu($title,'','',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');
}

function zeroBSCRM_notifications_learn_menu(){

	#}hopscoth is not scotching past this step?	
    $title        = __('Notifications','zero-bs-crm');
    $content      = zeroBS_generateLearnContent('notifications');
    $links        = zeroBS_generateLearnLinks('notifications');	

    $hopscotchJS = 'if (typeof hopscotch != "undefined" && hopscotch.getState() === "zbs-welcome-tour:4") { hopscotch.startTour(window.zbsTour);}';

	zeroBS_genericLearnMenu($title,'','',true,$title,$content,$links['learn'],$links['img'],$links['vid'],$hopscotchJS);
}

function zeroBSCRM_sendemail_learn_menu(){
    $title        = __('Send Email','zero-bs-crm');
    $content      = zeroBS_generateLearnContent('sendemail');
    $links        = zeroBS_generateLearnLinks('sendemail');	
	zeroBS_genericLearnMenu($title,'','',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}



function zeroBSCRM_contactlist_learn_menu(){

	global $zbs;

    #} title
    $title = zeroBSCRM_getContactOrCustomer().'s';

    #} Also Co - what is this.
    $alsoCo = ''; if (isset($customerListTable->coname) && !empty($customerListTable->coname)) {
        echo ' at '.$customerListTable->coname;
        $alsoCo = '&co='.$customerListTable->coID;
    }

    #} Add new
    $addNew = ''; if ( zeroBSCRM_permsCustomers() ) {
        $addNew = ' <a href="' . esc_url(zbsLink('create',-1,'zerobs_customer',false).$alsoCo) . '" class="button ui blue tiny zbs-add-new">' . __('Add New',"zero-bs-crm") . '</a>';
    }

    $content      = zeroBS_generateLearnContent('contactlist');
    $links        = zeroBS_generateLearnLinks('contactlist');	


    // filter strings
    $filterStr = '<a href="' .zbsLink($zbs->slugs['managecontacts']). '" id="zbs-listview-clearfilters" class="ui button red tiny hidden zbs-hide"><i class="undo icon"></i>'.__(" Clear Filters","zero-bs-crm").'</a><div id="zbs-listview-biline" class="hidden"></div>';

    #} And allow peeps also to toggl side bar:
    $filterStr .= '<button class="ui icon button basic right floated" type="button" id="zbs-toggle-sidebar"><i class="toggle off icon"></i></button>';

    #} Admins can change columns! (globally - should each person have own views?
    // Now everyone can see this menu (2.95.3+) - but can only edit count per page
    //if (zeroBSCRM_isZBSAdminOrAdmin()){ 
        $filterStr .= '<button class="ui icon button blue right floated" type="button" id="zbs-open-column-manager"><i class="options icon"></i></button>';
    //} 

	zeroBS_genericLearnMenu($title,$addNew,$filterStr,true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');
}

function zeroBSCRM_viewcontact_learn_menu($name=''){

    $title        = __('Viewing Contact','zero-bs-crm');
    $alsoCo = '';
    $addNew = ''; if ( zeroBSCRM_permsCustomers() ) {
        $addNew = ' <a href="' . esc_url(zbsLink('create',-1,'zerobs_customer',false)) . '" id="zbs-contact-add-new" class="button ui blue tiny zbs-add-new">' . __('Add New',"zero-bs-crm") . '</a>';
    }
    $content      = zeroBS_generateLearnContent('viewcontact');
    $links        = zeroBS_generateLearnLinks('viewcontact');	

	#} Navigation
	$zbsid = -1;
    if (isset($_GET['zbsid']) && !empty($_GET['zbsid'])) $zbsid = (int)sanitize_text_field($_GET['zbsid']);
	$filterStr = '<div class="ui items right floated" style="margin:0">'.zeroBSCRM_getObjNav($zbsid,'view',ZBS_TYPE_CONTACT).'</div>';
	
	zeroBS_genericLearnMenu($title,$addNew,$filterStr,true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_viewcompany_learn_menu($name=''){
    global $zbs_learn_links_array, $zbs_learn_img_array, $zbs_learn_video_link_array;

	$learnContent = '<p>'.__("Company information page. See key information about the company's status and when they were added.","zero-bs-crm").'</p>
					<p><strong>'.__("At a glance","zero-bs-crm").'</strong> '.__("you can see everything about the company and perform quick actions.","zero-bs-crm").'</p>';
					//<p>'.__("You can add tasks, send emails and see your contacts activity here.", "zero-bs-crm").'</p>';
						
    $addNew = ''; 

    // admin can change view setting
    // use screenoptions model instead
    //if ( current_user_can('admin_zerobs_manage_options') ) {
    //	$addNew = '<button class="ui icon right floated button" type="button" id="zbs-current-page-view-settings"><i class="settings icon"></i></button>';
    //}

    $learnContent = apply_filters('zbs_learn_viewcompany_content', $learnContent);
    
    $learnMoreURL = $zbs_learn_links_array['viewcompany'];
    $learnImgURL = $zbs_learn_img_array['viewcompany'];
    $learnVidURL = $zbs_learn_video_link_array['viewcompany'];
	

	#} Navigation
	$zbsid = -1;
    if (isset($_GET['zbsid']) && !empty($_GET['zbsid'])) $zbsid = (int)$_GET['zbsid'];
	$filterStr = '<div class="ui items right floated" style="margin:0">'.zeroBSCRM_getObjNav($zbsid,'view',ZBS_TYPE_COMPANY).'</div>';
	
	
	// output
	zeroBS_genericLearnMenu(__('Viewing Company',"zero-bs-crm"),$addNew,$filterStr,true,__('Company View',"zero-bs-crm"),$learnContent,$learnMoreURL,$learnImgURL,$learnVidURL,' //none','z-index: 9999999;');

}

// for new + edit menu :)
function zeroBSCRM_contactedit_learn_menu2(){

    global $zbs_learn_links_array, $zbs_learn_img_array, $zbs_learn_video_link_array,$zbs_learn_content_array;

    $title = __("New Contact","zero-bs-crm");
    
	$learnContent = $zbs_learn_content_array['newedit'];
    $learnContent = apply_filters('zbs_learn_newedit_content', $learnContent);

    $learnMoreURL = $zbs_learn_links_array['newedit'];
    $learnImgURL = $zbs_learn_img_array['newedit'];
    $learnVidURL = $zbs_learn_video_link_array['newedit'];

    $title        = __('New Contact','zero-bs-crm');
    $addNew = '';
    $content      = zeroBS_generateLearnContent('newedit');
    $links        = zeroBS_generateLearnLinks('newedit');	

	$zbsid = -1;

    if (isset($_GET['zbsid']) && !empty($_GET['zbsid'])) {
    	/* $id = (int)sanitize_text_field($_GET['zbsid']);
    		$filterStr .= '<a class="ui icon button basic blue right floated" href="'.zbsLink('view',$id,'zerobs_customer').'"><i class="angle left icon"></i> '.__('Back',"zero-bs-crm").'</a>';   	*/
    	$title = __("Edit Contact","zero-bs-crm");   
        $zbsid = (int)sanitize_text_field($_GET['zbsid']);
        $content      = zeroBS_generateLearnContent('contactedit');
        $links        = zeroBS_generateLearnLinks('contactedit');	
    }

    $metaboxMgrStr = '';
  
	
	$filterStr = '<div class="ui items right floated" style="margin:0">'.zeroBSCRM_getObjNav($zbsid,'edit',ZBS_TYPE_CONTACT).$metaboxMgrStr.'</div>';

	// output
	zeroBS_genericLearnMenu($title,$addNew,$filterStr,true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');


}


// for new + edit menu :)
function zeroBSCRM_companyedit_learn_menu2(){

	$title = __("New Company","zero-bs-crm");
    $content      = zeroBS_generateLearnContent('newcompany');
    $links        = zeroBS_generateLearnLinks('newcompany');	

    $filterStr = '';	

    // pre v3
    if (isset($_GET['post']) && !empty($_GET['post'])) {
    	$title = __("Edit Company","zero-bs-crm");
    	$id = (int)sanitize_text_field($_GET['post']);
    	$filterStr .= '<a class="ui icon button basic blue right floated" href="'.zbsLink('view',$id,'zerobs_company').'"><i class="angle left icon"></i> '.__('Back',"zero-bs-crm").'</a>';
    }	

    // v3.0+
    if (isset($_GET['zbsid']) && !empty($_GET['zbsid'])) {
    	$title = __("Edit Company","zero-bs-crm");
    	$id = (int)sanitize_text_field($_GET['zbsid']);
    	//$filterStr .= '<a class="ui icon button basic blue right floated" href="'.zbsLink('view',$id,'zerobs_company').'"><i class="angle left icon"></i> '.__('Back',"zero-bs-crm").'</a>';
		$filterStr = '<div class="ui items right floated" style="margin:0">'.zeroBSCRM_getObjNav($id,'edit',ZBS_TYPE_COMPANY).'</div>';

    }

	// output
	zeroBS_genericLearnMenu($title,'',$filterStr,true,$title,$content, $links['learn'], $links['img'], $links['vid'],'');


}

function zeroBSCRM_exportcontact_learn_menu(){

    $title      = __("Export Contacts","zero-bs-crm");
    $addNew     = '<a href="' . admin_url( 'admin.php?page=zbs-export-tools&zbswhat=contacts' ) . '" class="button ui blue tiny zbs-add-new">' . __('Export Other Types',"zero-bs-crm") . '</a>';
    $content    = zeroBS_generateLearnContent('exportcontact');
    $links      = zeroBS_generateLearnLinks('exportcontact');

	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_exporttools_learn_menu(){

    $title      = __("Export Tools","zero-bs-crm");
	$addNew     = ''; 
    $content    = zeroBS_generateLearnContent('exporttools');
    $links      = zeroBS_generateLearnLinks('exporttools');
	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],' //none');

}

#} Forms - LIST, EDIT and NEW
function zeroBSCRM_formlist_learn_menu(){
    global $zbs;

    $title      = __("Forms","zero-bs-crm");
	$addNew = '';
	if ( zeroBSCRM_permsQuotes() ) {
            $addNew = ' <a href="' . zbsLink('create',-1,'zerobs_form',false) . '" class="button ui blue tiny zbs-add-new">' . __('Add New',"zero-bs-crm") . '</a>';
	} 
    $content    = zeroBS_generateLearnContent('forms');
    $links      = zeroBS_generateLearnLinks('forms');

	#} MSTODO - Learn hidden 
	$hideLearn = true;
	$alsoCo = '';
	
	#} ? Yup ?
    $hopscotchCustomJS = 'if (typeof hopscotch != "undefined" && (hopscotch.getState() === "zbs-welcome-tour:9" || hopscotch.getState() === "zbs-welcome-tour:10" || hopscotch.getState() === "zbs-welcome-tour:11")) {hopscotch.startTour(window.zbsTour);}';

    #} Filters
	$filterStr = '<a href="' .admin_url('admin.php?page='.$zbs->slugs['manageformscrm']). '" id="zbs-listview-clearfilters" class="ui button red tiny hidden zbs-hide"><i class="undo icon"></i>'.__(" Clear Filters","zero-bs-crm").'</a><div id="zbs-listview-biline" class="hidden"></div>';
        
    #} And allow peeps also to toggl side bar:
    $filterStr .= '<button class="ui icon button basic right floated" type="button" id="zbs-toggle-sidebar"><i class="toggle off icon"></i></button>';

    #} Admins can change columns! (globally - should each person have own views?
    if (zeroBSCRM_isZBSAdminOrAdmin()){ 
        $filterStr .= '<button class="ui icon button blue right floated" type="button" id="zbs-open-column-manager"><i class="options icon"></i></button>';
    } 
	// output
	zeroBS_genericLearnMenu($title,$addNew,$filterStr,$hideLearn,$title,$content,$links['learn'],$links['img'],$links['vid'],$hopscotchCustomJS);

}


#} CONTENT NEEDS WRITING TOO!
function zeroBSCRM_formedit_learn_menu(){
    $title        = __('Edit Form','zero-bs-crm');
    $addNew = ' <a href="' . zbsLink('create',-1,'zerobs_form',false) . '" class="button ui blue tiny zbs-add-new">' . __('Add New',"zero-bs-crm") . '</a>';
    $content      = zeroBS_generateLearnContent('formedit');
    $links        = zeroBS_generateLearnLinks('formedit');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');
}


function zeroBSCRM_formnew_learn_menu(){
    $title        = __('New Form','zero-bs-crm');
    $addNew = '';
    $content      = zeroBS_generateLearnContent('formnew');
    $links        = zeroBS_generateLearnLinks('formnew');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');
}

function zeroBSCRM_taskedit_learn_menu(){

	global $zbs;

    $title      = __('Edit Task','zero-bs-crm');
    $addNew 	= '<div id="zbs-event-learn-nav"></div>';
    $addNew     .= ' <a href="' . zbsLink('create',-1,'zerobs_event',false) . '" class="button ui blue tiny zbs-add-new">' . __('Add New',"zero-bs-crm") . '</a>';
	$addNew 	.= ' <a href="' . zbsLink($zbs->slugs['manage-events']) . '" class="button ui orange tiny zbs-add-new zbs-add-new-task">' . __('View All Tasks',"zero-bs-crm") . '</a>'; 
	$content    = zeroBS_generateLearnContent('taskedit');
    $links      = zeroBS_generateLearnLinks('taskedit');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');
}

function zeroBSCRM_tasknew_learn_menu(){

	global $zbs;

    $title      = __('New Task','zero-bs-crm');
	$addNew 	= ' <a href="' . zbsLink($zbs->slugs['manage-events']) . '" class="button ui orange tiny zbs-add-new zbs-add-new-task">' . __('View All Tasks',"zero-bs-crm") . '</a>'; 
    $content    = zeroBS_generateLearnContent('tasknew');
    $links      = zeroBS_generateLearnLinks('tasknew');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_profile_learn_menu(){
    $title      = __('Your Profile','zero-bs-crm');
	$addNew 	= ''; 
    $content    = zeroBS_generateLearnContent('profile');
    $links      = zeroBS_generateLearnLinks('profile');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}


function zeroBSCRM_quotelist_learn_menu(){
    global $zbs;
    $title      = __('Manage Quotes','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('quotelist');
    $links      = zeroBS_generateLearnLinks('quotelist');	

	$addNew = '';
    #} Add new?
    if ( zeroBSCRM_permsCustomers() ) {
        $addNew = ' <a href="' . zbsLink('create',-1,'zerobs_quote',false) . '" class="button ui blue tiny zbs-add-new">' . __('Add New',"zero-bs-crm") . '</a>';
    }  

	$hideLearn = true;
	$alsoCo = '';
	
	#} ? Yup ?
    $hopscotchCustomJS = 'if (typeof hopscotch != "undefined" && (hopscotch.getState() === "zbs-welcome-tour:9" || hopscotch.getState() === "zbs-welcome-tour:10" || hopscotch.getState() === "zbs-welcome-tour:11")) {hopscotch.startTour(window.zbsTour);}';

    #} Filters
	$filterStr = '<a href="' .admin_url('admin.php?page='.$zbs->slugs['managequotes']). '" id="zbs-listview-clearfilters" class="ui button red tiny hidden"><i class="undo icon"></i>'.__(" Clear Filters","zero-bs-crm").'</a><div id="zbs-listview-biline" class="hidden"></div>';

    #} And allow peeps also to toggl side bar:
    $filterStr .= '<button class="ui icon button basic right floated" type="button" id="zbs-toggle-sidebar"><i class="toggle off icon"></i></button>';

    #} Admins can change columns! (globally - should each person have own views?
    if (zeroBSCRM_isZBSAdminOrAdmin()){ 
        $filterStr .= '<button class="ui icon button blue right floated" type="button" id="zbs-open-column-manager"><i class="options icon"></i></button>';
    } 
    
	// output
	zeroBS_genericLearnMenu($title,$addNew,$filterStr,$hideLearn,$title,$content,$links['learn'],$links['img'],$links['vid'],$hopscotchCustomJS);

}

function zeroBSCRM_quotenew_learn_menu(){
    $title      = __('New Quote','zero-bs-crm');
    $addNew     = '<div id="zbs-quote-learn-nav"></div>'; // js adds/edits
    $content    = zeroBS_generateLearnContent('quotenew');
    $links      = zeroBS_generateLearnLinks('quotenew');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_quoteedit_learn_menu(){
    $title      = __('Edit Quote','zero-bs-crm');
    $addNew     = '<div id="zbs-quote-learn-nav"></div>'; // js adds/edits
    #} Add new
    $addNew = ' <a href="' . esc_url(zbsLink('create',-1,ZBS_TYPE_QUOTE,false)) . '" class="button ui blue tiny zbs-add-new">' . __('Add New',"zero-bs-crm") . '</a>';
    
    $content    = zeroBS_generateLearnContent('quoteedit');
    $links      = zeroBS_generateLearnLinks('quoteedit');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}


function zeroBSCRM_transactiontags_learn_menu(){
    $title      = __('Transaction Tags','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('transactiontags');
    $links      = zeroBS_generateLearnLinks('transactiontags');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');
}



function zeroBSCRM_translist_learn_menu(){

	global $zbs;

    $title      = __('Transaction List','zero-bs-crm');
    #} Add new?
    $addNew = ''; if ( zeroBSCRM_permsCustomers() ) {
        $addNew = ' <a href="' . esc_url(zbsLink('create',-1,'zerobs_transaction',false)) . '" class="button ui blue tiny zbs-add-new">' . __('Add New',"zero-bs-crm") . '</a>';
    }
    $content    = zeroBS_generateLearnContent('translist');
    $links      = zeroBS_generateLearnLinks('translist');	

    $filterStr = '<a href="' .zbsLink($zbs->slugs['managetransactions']). '" id="zbs-listview-clearfilters" class="ui button red tiny hidden"><i class="undo icon"></i>'.__(" Clear Filters","zero-bs-crm").'</a><div id="zbs-listview-biline" class="hidden"></div>';
    $filterStr .= '<button class="ui icon button basic right floated" type="button" id="zbs-toggle-sidebar"><i class="toggle off icon"></i></button>';

    #} Admins can change columns! (globally - should each person have own views?
    if (current_user_can('administrator')){ 
        $filterStr .= '<button class="ui icon button blue right floated" type="button" id="zbs-open-column-manager"><i class="options icon"></i></button>';
    } 
	
    zeroBS_genericLearnMenu($title,$addNew,$filterStr,true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}


function zeroBSCRM_transnew_learn_menu(){
    $title      = __('New Transaction','zero-bs-crm');
    $addNew     = '<div id="zbs-transaction-learn-nav"></div>'; // js adds/edits
    $content    = zeroBS_generateLearnContent('transnew');
    $links      = zeroBS_generateLearnLinks('transnew');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_transedit_learn_menu(){
    $title      = __('Edit Transaction','zero-bs-crm');
    #} Add new
    $addNew = '<div id="zbs-transaction-learn-nav"></div>'; if ( zeroBSCRM_permsTransactions() ) {
        $addNew = ' <a href="' . esc_url(zbsLink('create',-1,ZBS_TYPE_TRANSACTION,false)) . '" class="button ui blue tiny zbs-add-new">' . __('Add New',"zero-bs-crm") . '</a>';
    }
    $content    = zeroBS_generateLearnContent('transedit');
    $links      = zeroBS_generateLearnLinks('transedit');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_quotetemplate_learn_menu(){
    $title      = __('Quote Templates','zero-bs-crm');
    $addNew     = ' <a href="' . zbsLink('create',-1,'zerobs_quo_template',false)  . '#free-extensions-tour" class="button ui blue tiny zbs-add-new" id="add-template">' . __('Add Template',"zero-bs-crm") . '</a>';
    $content    = zeroBS_generateLearnContent('quotetemplate');
    $links      = zeroBS_generateLearnLinks('quotetemplate');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}


function zeroBSCRM_quotetemplatenew_learn_menu(){

    $title      = __('New Quote Template','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('quotetemplatenew');
    $links      = zeroBS_generateLearnLinks('quotetemplatenew');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_quotetemplateedit_learn_menu(){
    $title      = __('Edit Quote Template','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('quotetemplatenew');
    $links      = zeroBS_generateLearnLinks('quotetemplatenew');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');
}


function zeroBSCRM_team_learn_menu(){
    $title      = __('Your Team','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('team');
    $links      = zeroBS_generateLearnLinks('team');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}


#} Add New User stuff.
function zeroBSCRM_team_add_learn_menu(){
    $title      = __('Add New Team Member','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('teamadd');
    $links      = zeroBS_generateLearnLinks('teamadd');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

#} Edit User...
function zeroBSCRM_team_edit_learn_menu(){
    $title      = __('Edit Team Member','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('teamedit');
    $links      = zeroBS_generateLearnLinks('teamedit');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_invoicelist_learn_menu(){

	global $zbs; 
	
    $title      = __('Manage Invoices','zero-bs-crm');
    $addNew = '';
    if ( zeroBSCRM_permsInvoices() ) {
        $addNew =  '<a href="' . zbsLink('create',-1,'zerobs_invoice',false) . '" class="button ui blue tiny zbs-add-new">' . __('Add New',"zero-bs-crm") . '</a>';
    }
    $content    = zeroBS_generateLearnContent('invoicelist');
    $links      = zeroBS_generateLearnLinks('invoicelist');	

    #} Filters
	$filterStr = '<a href="' .admin_url('admin.php?page='.$zbs->slugs['manageinvoices']). '" id="zbs-listview-clearfilters" class="ui button red tiny hidden"><i class="undo icon"></i>'.__(" Clear Filters","zero-bs-crm").'</a><div id="zbs-listview-biline" class="hidden"></div>';

    
    #} And allow peeps also to toggl side bar:
    $filterStr .= '<button class="ui icon button basic right floated" type="button" id="zbs-toggle-sidebar"><i class="toggle off icon"></i></button>';

    #} Admins can change columns! (globally - should each person have own views?
    if (zeroBSCRM_isZBSAdminOrAdmin()){ 
        $filterStr .= '<button class="ui icon button blue right floated" type="button" id="zbs-open-column-manager"><i class="options icon"></i></button>';
    } 

	zeroBS_genericLearnMenu($title,$addNew,$filterStr,true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_invoicenew_learn_menu(){
    $title      = __('New Invoice','zero-bs-crm');
    $addNew     = '<div id="zbs-invoice-learn-nav"></div>'; // js adds/edits
    $content    = zeroBS_generateLearnContent('invoicenew');
    $links      = zeroBS_generateLearnLinks('invoicenew');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}


function zeroBSCRM_invoiceedit_learn_menu(){
	
	$filterStr = '';
    $title      = __('Edit Invoice','zero-bs-crm');
    
    $alsoInAddNew = '';
	// if admin, show settings links too
	// (these get appended to the zbs-invoice-learn-nav) so that they can be shared with the js-added nav
    if (zeroBSCRM_isZBSAdminOrAdmin()){ 

    	global $zbs;
        $alsoInAddNew .= '<a class="ui icon mini button" target="_blank" href="'.admin_url("admin.php?page=" . $zbs->slugs['settings']) . "&tab=invbuilder".'" title="'.__('Invoice Settings','zero-bs-crm').'"><i class="options icon"></i></a>';
        $alsoInAddNew .= '<a class="ui icon mini button" target="_blank" href="'.admin_url("admin.php?page=" . $zbs->slugs['settings']) . "&tab=bizinfo".'" title="'.__('Business Settings','zero-bs-crm').'"><i class="building icon"></i></a>';		        
    }

    $addNew     = '<div id="zbs-invoice-learn-nav">'.$alsoInAddNew.'</div>'; // js adds/edits
    if ( zeroBSCRM_permsInvoices() ) {
        $addNew .=  '<a href="' . zbsLink('create',-1,'zerobs_invoice',false) . '" class="button ui blue tiny zbs-add-new">' . __('Add New',"zero-bs-crm") . '</a>';
    }
    $content    = zeroBS_generateLearnContent('invoiceedit');
    $links      = zeroBS_generateLearnLinks('invoiceedit');	

	zeroBS_genericLearnMenu($title,$addNew,$filterStr,true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_powerup_learn_menu(){
    $title      = __('Extend your CRM','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('powerup');
    $links      = zeroBS_generateLearnLinks('powerup');	


    $hopscotchJS = 'if (typeof hopscotch != "undefined" && (hopscotch.getState() === "zbs-welcome-tour:8:5" || hopscotch.getState() === "zbs-welcome-tour:9:5")) { hopscotch.startTour(window.zbsTour);}';

	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],$hopscotchJS);

}

function zeroBSCRM_datatools_learn_menu(){
    $title      = __('Data Tools','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('datatools');
    $links      = zeroBS_generateLearnLinks('datatools');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_companylist_learn_menu(){

	global $zbs;
	
    $title      = __('Manage Companies','zero-bs-crm');
    $addNew = '';
    if ( zeroBSCRM_permsInvoices() ) {
        $addNew =  '<a href="' .zbsLink('create',-1,'zerobs_company',false) . '" class="button ui blue tiny zbs-add-new">' . __('Add New',"zero-bs-crm") . '</a>';
    }
    $filterStr = '';
    $content    = zeroBS_generateLearnContent('companylist');
    $links      = zeroBS_generateLearnLinks('companylist');	

    // filter strings
    $filterStr = '<a href="' .zbsLink($zbs->slugs['managecompanies']). '" id="zbs-listview-clearfilters" class="ui button red tiny hidden zbs-hide"><i class="undo icon"></i>'.__(" Clear Filters","zero-bs-crm").'</a><div id="zbs-listview-biline" class="hidden"></div>';

    #} And allow peeps also to toggl side bar:
    $filterStr .= '<button class="ui icon button basic right floated" type="button" id="zbs-toggle-sidebar"><i class="toggle off icon"></i></button>';

    #} Admins can change columns! (globally - should each person have own views?
    if (zeroBSCRM_isZBSAdminOrAdmin()){ 
        $filterStr .= '<button class="ui icon button blue right floated" type="button" id="zbs-open-column-manager"><i class="options icon"></i></button>';
    } 

    zeroBS_genericLearnMenu($title,$addNew,$filterStr,true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');
    
}

function zeroBSCRM_companynew_learn_menu(){
    $title      = __('New Company','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('companynew');
    $links      = zeroBS_generateLearnLinks('companynew');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_companyedit_learn_menu(){
    $title      = __('Edit Company','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('companyedit');
    $links      = zeroBS_generateLearnLinks('companyedit');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_tasklist_learn_menu(){
    $title      = __('Tasks','zero-bs-crm');
    $addNew 	= ' <a href="' . zbsLink('create',-1,'zerobs_event',false) . '" class="button ui blue tiny zbs-add-new zbs-add-new-task">' . __('Add New',"zero-bs-crm") . '</a>';
	$content    = zeroBS_generateLearnContent('tasklist');
    $links      = zeroBS_generateLearnLinks('tasklist');	

    // show "who's calendar" top right?
    // adapted from what was inline output in List.Events.php
    global $zbs;
    $showEventsUsers = false;
    $currentEventUserID = false; if (isset($_GET['zbsowner']) && !empty($_GET['zbsowner'])) $currentEventUserID = (int)sanitize_text_field($_GET['zbsowner']);
    $zbsEventsUsers = zeroBS_getPossibleCustomerOwners();
    if (count($zbsEventsUsers) > 0 && zeroBSCRM_isZBSAdminOrAdmin()) {
        $showEventsUsers = true;
    } else {
        $taskOwnershipOn = zeroBSCRM_getSetting('taskownership');
        if ($taskOwnershipOn == "1") {
            $currentEventUserID = get_current_user_id();
        }
    }
    if ($showEventsUsers){ 
    	$eventUsersHTML = '<div style="float:right;margin-right: 1em;">'; // "width: 200px;
            $eventUsersHTML .= '<select class="form-control" id="zerobscrm-owner" name="zerobscrm-owner">';
        	    $eventUsersHTML .= '<option value="-1">'.__('All Users',"zero-bs-crm").'</option>';
                    if (count($zbsEventsUsers) > 0) 
                    	foreach ($zbsEventsUsers as $eventsUser){

                                $eventUsersHTML .= '<option value="'.$eventsUser->ID.'"';
                                if ($eventsUser->ID == $currentEventUserID) $eventUsersHTML .= ' selected="selected"';
                                $eventUsersHTML .= '>'.esc_html( $eventsUser->display_name ).'</option>';

                   		}
            $eventUsersHTML .= '</select>';
        $eventUsersHTML .= '</div> ';

        $eventUsersHTML .= '<script type="text/javascript">';
            $eventUsersHTML .= 'var zbsExistingEventsUserID = '.((!empty($currentEventUserID)) ? $currentEventUserID : '-1').';';
            $eventUsersHTML .= "jQuery('#zerobscrm-owner').bind('change',function(){";
                $eventUsersHTML .= 'var v = jQuery(this).val();';
                $eventUsersHTML .= "if (v != '' && v != window.zbsExistingEventsUserID){";
					$eventUsersHTML .= "var newURL = '".zbsLink($zbs->slugs['manage-events'])."';";
                    $eventUsersHTML .= "if (v != -1) newURL += '&zbsowner=' + jQuery(this).val();";
					// $eventUsersHTML .= "// reload with get var";
						$eventUsersHTML .= "window.location = newURL;";
                    $eventUsersHTML .= "}";
                $eventUsersHTML .= "});";
		$eventUsersHTML .= "</script>";

    } 


	zeroBS_genericLearnMenu($title,$addNew,$eventUsersHTML,true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

//hidden for now
function zeroBSCRM_connect_learn_menu(){
    $title      = __('Connect','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('connect');
    $links      = zeroBS_generateLearnLinks('connect');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}
        

function zeroBSCRM_systemstatus_learn_menu(){
    $title      = __('System Status','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('systemstatus');
    $links      = zeroBS_generateLearnLinks('systemstatus');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}
		
#} MSTODO: complete
function zeroBSCRM_export_learn_menu(){
    $title      = __('Export','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('export');
    $links      = zeroBS_generateLearnLinks('export');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}
		
function zeroBSCRM_feedback_learn_menu(){
    $title      = __('Feedback to us','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('feedback');
    $links      = zeroBS_generateLearnLinks('feedback');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

// Migration of db2 contacts
function zeroBSCRM_migratedb2contacts_learn_menu(){
    $title      = __('Databse Upgrade','zero-bs-crm');
    $addNew     = '';
    $content    = zeroBS_generateLearnContent('migratedb2contacts');
    $links      = zeroBS_generateLearnLinks('migratedb2contacts');	
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}
		
					
function zeroBSCRM_segmentlist_learn_menu(){    
    global $zbs;
    $title      = __('Segment List','zero-bs-crm');
    $content    = zeroBS_generateLearnContent('segmentlist');
    $links      = zeroBS_generateLearnLinks('segmentlist');	
   
    $addNew = ''; if ( zeroBSCRM_permsCustomers() ) {
        $addNew = ' <a href="' . esc_url(zbsLink('create',-1,'segment',false)) . '" class="button ui blue tiny zbs-add-new">' . __('Add New',"zero-bs-crm") . '</a>';
    }
     // filter strings
     $filterStr = '<a href="' .zeroBSCRM_getAdminURL($zbs->slugs['managecontacts']). '" id="zbs-listview-clearfilters" class="ui button red tiny hidden"><i class="undo icon"></i>'.__(" Clear Filters","zero-bs-crm").'</a><div id="zbs-listview-biline" class="hidden"></div>';

     #} And allow peeps also to toggl side bar:
     $filterStr .= '<button class="ui icon button basic right floated" type="button" id="zbs-toggle-sidebar"><i class="toggle off icon"></i></button>';
 
     #} Admins can change columns! (globally - should each person have own views?
     if (current_user_can('administrator')){ 
         $filterStr .= '<button class="ui icon button blue right floated" type="button" id="zbs-open-column-manager"><i class="options icon"></i></button>';
     }            
	// output
	zeroBS_genericLearnMenu('<i class="pie chart icon"></i>'.$title,$addNew,$filterStr,true,$title,$content,$links['learn'],$links['img'],$links['vid'],'','z-index: 9999999;');

}
	
// for new + edit menu :)
function zeroBSCRM_segmentedit_learn_menu(){
	global $zbs;

	$title = __("Segments","zero-bs-crm");
    $newSegment = true;
    $content    = zeroBS_generateLearnContent('segmentedit');
    $links      = zeroBS_generateLearnLinks('segmentedit');	


    $zbsid = $zbs->zbsvar('zbsid');
    if (isset($zbsid) && !empty($zbsid) && $zbsid !== -1) {
    	$title = __("Edit Segment","zero-bs-crm");  
    	$newSegment = false; 
    }

    $filterStr = '<button class="ui icon small button positive right floated';
    	if ($newSegment) $filterStr .= ' hidden';
    $filterStr .= '" type="button" id="zbs-segment-edit-act-save">'.__('Save Segment',"zero-bs-crm").'  <i class="save icon"></i></button>';
    $filterStr .= '<button class="ui button small right floated inverted basic" type="button" id="zbs-segment-edit-act-back">'.__('Back to List',"zero-bs-crm").'</button>';

	// output
	zeroBS_genericLearnMenu('<i class="pie chart icon"></i>'.$title,'',$filterStr,true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');


}

function zeroBSCRM_settings_learn_menu(){
    global $zbs;
    global $zbs_learn_links_array, $zbs_learn_img_array, $zbs_learn_video_link_array;
    $zbs_learn_links_array = apply_filters('zbs_learn_links_array', $zbs_learn_links_array);

	// wh temp hack for mail delivery learn
    $title = __("Settings","zero-bs-crm");
    
    if ( current_user_can('admin_zerobs_manage_options') ) {
        $addNew =  ' <a href="' . zeroBSCRM_getAdminURL($zbs->slugs['extensions'])  . '#free-extensions-tour" class="button ui orange tiny zbs-add-new" id="manage-features">' . __('Manage Features',"zero-bs-crm") . '</a>';
    }

	$tab = '';
	if (isset($_GET['tab']) && $_GET['tab'] == 'maildelivery'){
		$title .= ': '.__("Mail Delivery","zero-bs-crm");
		$tab = 'maildelivery';
	}
	if (isset($_GET['tab']) && $_GET['tab'] == 'mail'){
		$title .= ': '.__("Mail","zero-bs-crm");
		$tab = 'mail';
	}

    #} If filtering this, be careful as it changes based on the tab use $_GET['tab'] in filter 
    switch ($tab){
        case 'mail':
            $content    = zeroBS_generateLearnContent('mail');
            $links      = zeroBS_generateLearnLinks('mail');	
        break;
        case 'maildelivery':
            $content    = zeroBS_generateLearnContent('maildelivery');
            $links      = zeroBS_generateLearnLinks('maildelivery');	
        break;
        default: 
            $content    = zeroBS_generateLearnContent('settings');
            $links      = zeroBS_generateLearnLinks('settings');	
        break;
    }

    $hopscotchJS = 'if (typeof hopscotch != "undefined" && (hopscotch.getState() === "zbs-welcome-tour:10:5")) { hopscotch.startTour(window.zbsTour);}';

	
	// output
	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],$hopscotchJS);

}


function zeroBSCRM_emails_learn_menu(){
    $title      = __('Emails','zero-bs-crm');
	$addNew     = '';
	$filterStr = '<a href="'.admin_url('admin.php?page=zerobscrm-send-email').'" class="ui button blue tiny zbs-inbox-compose-email"><i class="ui icon pencil"></i> ' . __("Compose Mail", "zero-bs-crm") . '</a>';
    $content    = zeroBS_generateLearnContent('emails');
    $links      = zeroBS_generateLearnLinks('emails');	
	zeroBS_genericLearnMenu($title,$addNew,$filterStr,true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');
}

// ============ BENEATH HERE is functions which do not output (yet)

#} MSTODO: complete
function zeroBSCRM_home_learn_menu(){
    $title      = __('Home','zero-bs-crm');
    $addNew = '';
    $content    = zeroBS_generateLearnContent('home');
    $links      = zeroBS_generateLearnLinks('home');	
//	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}
#} MSTODO: complete
function zeroBSCRM_salesdash_learn_menu(){
    $title      = __('Sales Dashboard','zero-bs-crm');
    $addNew = '';
    $content    = zeroBS_generateLearnContent('salesdash');
    $links      = zeroBS_generateLearnLinks('salesdash');	
//	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');


}
#} MSTODO: complete
function zeroBSCRM_bulktagger_learn_menu(){
    $title      = __('Bulk Tagger','zero-bs-crm');
    $addNew = '';
    $content    = zeroBS_generateLearnContent('bulktagger');
    $links      = zeroBS_generateLearnLinks('bulktagger');	
    //	zeroBS_genericLearnMenu($title,$addNew,'',true,$title,$content,$links['learn'],$links['img'],$links['vid'],'');

}

function zeroBSCRM_welcome_learn_menu(){
	// None for now
}
		
function zeroBSCRM_sync_learn_menu(){
	// None for now
}


// Generic Delete menu
function zeroBSCRM_delete_learn_menu(){

    global $zbs,$zbs_learn_links_array, $zbs_learn_img_array, $zbs_learn_video_link_array,$zbs_learn_content_array;
    
	$learnContent = (isset($zbs_learn_content_array['delete'])) ? $zbs_learn_content_array['delete'] : '';
    $learnContent = apply_filters('zbs_learn_delete_content', $learnContent);

    $learnMoreURL = (isset($zbs_learn_links_array['delete'])) ? $zbs_learn_links_array['delete'] : '';
    $learnImgURL = (isset($zbs_learn_img_array['delete'])) ? $zbs_learn_img_array['delete'] : '';
    $learnVidURL = (isset($zbs_learn_video_link_array['delete'])) ? $zbs_learn_video_link_array['delete'] : '';

    $title        = __('Delete','zero-bs-crm');
    $addNew = '';
    $content      = zeroBS_generateLearnContent('delete');
    $links        = zeroBS_generateLearnLinks('delete');	

	$zbstype = -1;
    if (isset($_GET['zbstype']) && !empty($_GET['zbstype'])) {

    	// type specific :)
    	$zbstype = $_GET['zbstype'];

    		// try a conversion
    		$objTypeID = $zbs->DAL->objTypeID($zbstype);

    		if ($objTypeID > 0){

    			// got a type :D
    			$singular = $zbs->DAL->typeStr($objTypeID);
		    	$title = __("Delete","zero-bs-crm").' '.$singular;
		        $content      = zeroBS_generateLearnContent($zbstype.'delete'); // e.g. contactdelete
		        $links        = zeroBS_generateLearnLinks($zbstype.'delete');

		    }
    }

    $metaboxMgrStr = '';
 
 	// for now...
 	$showLearn = false; 
	
	$filterStr = '';

	// output
	zeroBS_genericLearnMenu($title,$addNew,$filterStr,$showLearn,$title,$content,$links['learn'],$links['img'],$links['vid'],'');


}