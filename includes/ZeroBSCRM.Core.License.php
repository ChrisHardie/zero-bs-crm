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

#} System Nag messages for license key and upgrades


add_action("wp_after_admin_bar_render","zeroBSCRM_admin_nag_footer", 12);
#} This will nag if there's anytihng amiss with the settings
function zeroBSCRM_admin_nag_footer(){
	global $zbs;
	
	if($zbs->extensionCount() > 0){ 

		// dev mode, show (dev mode) once a day
		if (zeroBSCRM_isLocal(true)){

			if (isset($_GET['zbs-nag-modal-window-now']) && $_GET['zbs-nag-modal-window-now'] == 1){
				set_transient('zbs-nag-modal-window-now', 'nag', 60*60*24);
			}

			// show devmode ver:
			if(zeroBSCRM_isAdminPage() && ((isset($_GET['page']) && $_GET['page'] != 'zerobscrm-plugin-settings') || !isset($_GET['page']))){

				$message = '<h3>'.__('Developer Mode Active', 'zero-bs-crm').'</h3>';

				$guide_link = $zbs->urls['kbdevmode'];
				$guide = '';

				##WLREMOVE
				$guide = '<br />'.__('For more information see','zero-bs-crm').' <a href="'.$guide_link.'" target="_blank">'.__('this guide','zero-bs-crm').'</a><br />'.__('For licensing','zero-bs-crm').' <a href="'.$zbs->urls['support'].'" target="_blank">'.__('contact support','zero-bs-crm').'</a>';
				##/WLREMOVE

				$message .= '<p>'.__('You are running Zero BS CRM in Developer mode. Automatic-updates are only available in production.','zero-bs-crm').$guide.'</p>';
				
				// remove nag for developer mode as is giving false positives.
				// zeroBSCRM_show_admin_nag_modal($message);

			}

		} else {

			// normal mode, show every 30m?
			if (isset($_GET['zbs-nag-modal-window-now']) && $_GET['zbs-nag-modal-window-now'] == 1){
				// this was running out after 1 min? :o set_transient('zbs-nag-modal-window-now', 'nag', 60);
				// don't show it for 30min?
				set_transient('zbs-nag-modal-window-now', 'nag', 60*30);
			}


			if(zeroBSCRM_isAdminPage() && ((isset($_GET['page']) && $_GET['page'] != 'zerobscrm-plugin-settings') || (!isset($_GET['page'])))){
				
	    		$license = zeroBSCRM_getSetting('license_key');

			//	zbs_write_log($license);

				//so if our license information doesn't play out..
				if(isset($license) && !empty($license)){

					//first up.. the license key is not valid..
					if($license['validity'] == false && $license['extensions_updated'] != false){ 

						$message = __('Your License Key is Incorrect. Please update your license key for this site.', 'zero-bs-crm');
						zeroBSCRM_show_admin_bottom_nag($message);

						$message = '<h3>'.__('License Key Incorrect', 'zero-bs-crm').'</h3>';
						$message = '<p>'.__('You have entered an incorrect license key. You can get your license key from your account and enter it in settings.','zero-bs-crm').'</p>';
						zeroBSCRM_show_admin_nag_modal($message);
					}

					if((isset($license['key']) && $license['key'] == '') || !isset($license['key'])){ 
						$link = esc_url(admin_url('admin.php?page=zerobscrm-plugin-settings&tab=license'));
						$message = '<h3>'.__('License Key Needed', 'zero-bs-crm').'</h3>';
						$account_link = $zbs->urls['account'];
						$message .= '<p>'.__('You can get your license key from <a href="'.$account_link.'" target="_blank">your account</a> and enter it in <a href="'.$link.'">settings</a>.','zero-bs-crm').'</p>';
						zeroBSCRM_show_admin_nag_modal($message);
					}

					//if we have extensions which need updating
					if(isset($license['extensions_updated']) && $license['extensions_updated'] == false){ 

						$message = __('You are running extension versions which are not supported. Please update immediately to avoid any issues.', 'zero-bs-crm');
						zeroBSCRM_show_admin_bottom_nag($message);

						$link = esc_url(admin_url('admin.php?page=zerobscrm-plugin-settings&tab=license'));
						$update_link = esc_url(admin_url('plugins.php'));

						$message = __('<h3>Extension Update Required</h3>', 'zero-bs-crm');
						if($license['validity'] == 'empty'){ 
							$message .= __('<p>You are running extension versions which are not supported. Please <a href="'.$link.'">enter your license key</a> for automatic updates.</p>', 'zero-bs-crm');
						}else if($license['validity'] == false){ 
							$message .= __('<p>You are running extension versions which are not supported. Please <a href="'.$link.'">check your license key</a> and update immediately.</p>', 'zero-bs-crm');
						}else{
							$message .= __('<p>You are running extension versions which are not supported. Please <a href="'.$update_link.'">update immediately</a> to avoid any issues.</p>', 'zero-bs-crm');
						}

						zeroBSCRM_show_admin_nag_modal($message);
						
						
					}
				}
			}

		} // / is not local/devmode (normal)

	}
}

function zeroBSCRM_show_admin_bottom_nag($message=''){
	?>
	<div class='zbs_nf'>
		<i class='ui icon warning'></i><?php echo $message; ?>
	</div>
	<?php	
}

//functionise as we will be using the same thing above
function zeroBSCRM_show_admin_nag_modal($message = ''){
		global $zbs; 
		$page = $zbs->slugs['dash'];
		if (isset($_GET['page'])) $page = sanitize_text_field($_GET['page']);
		if(!get_transient('zbs-nag-modal-window-now')){
		$link = admin_url('admin.php?page=' . $page . '&zbs-nag-modal-window-now=1');
		?>
				<div class='zbs_overlay'>
					<div class='close_nag_modal'><a href='<?php echo esc_url($link); ?>'>x</a></div>
					<div class='zbs-message-body'>
						<img id="zbs-main-logo-mobby" src="<?php echo zeroBSCRM_getLogoURL(false); ?>" alt="" style="cursor:pointer;" />
						<div class='zbs-message'>
							<?php echo $message; ?>
						</div>
					</div>
				</div>
		<?php
		}
}



/* ======================================================
  License related funcs
   ====================================================== */


	function zeroBSCRM_license_check(){
	    global $zbs;
	    // this should force an update check (and update keys)
	    $pluginUpdater = new zeroBSCRM_Plugin_Updater($zbs->urls['api'], $zbs->api_ver, 'zero-bs-crm');
	    $zbs_transient = '';
	    $pluginUpdater->check_update($zbs_transient);
	}


	#} gets a list of multi site 
	function zeroBSCRM_multisite_getSiteList(){
		global $wpdb;
		$sites = array();
		$table = $wpdb->prefix . "blogs"; 
		if($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
			$sql = "SELECT * FROM $table"; 
			$sites = $wpdb->get_results($sql);
		}

		// clean up (reduce bandwidth of pass/avoid overburdening)
		if (is_array($sites) && count($sites) > 0){
			$ret = array();
			foreach ($sites as $site) $ret[] = zeroBSCRM_tidy_multisite_site($site);
			$sites = $ret;
		}

		return $sites;

		// debug print_r(zeroBSCRM_multisite_getSiteList()); exit();

		/*
			we don't need all this

		    [blog_id] => 1
		    [site_id] => 1
		    [domain] => multisitetest.local
		    [path] => /
		    [registered] => 2018-08-10 15:29:31
		    [last_updated] => 2018-08-10 15:30:43
		    [public] => 1
		    [archived] => 0
		    [mature] => 0
		    [spam] => 0
		    [deleted] => 0
		    [lang_id] => 0
		*/

	}

	function zeroBSCRM_tidy_multisite_site($siteRow=array()){

		if (isset($siteRow->blog_id)){

			// active if not archived, spam, deleted
			$isActive = 1;
			if ($siteRow->archived) $isActive = -1;
			if ($siteRow->spam) $isActive = -1;
			if ($siteRow->deleted) $isActive = -1;

			return array(

					// not req. always same??
					'site_id' => $siteRow->site_id,
					'blog_id' => $siteRow->blog_id,

					'domain' => $siteRow->domain,
					'path' => $siteRow->path,

					// active if not archived, spam, deleted
					'active' => $isActive,

					// log these (useful)
					'deleted' => $siteRow->deleted,
					'archived' => $siteRow->archived,
					'spam' => $siteRow->spam,
					'lang_id' => $siteRow->lang_id,

					// not req. / not useful
					//'mature' => $siteRow->mature,
					//'public' => $siteRow->public,
					//'registered' => $siteRow->registered,
					//'last_updated' => $siteRow->last_updated,

				);


		}

		return false;
	}

/* ======================================================
  / License related funcs
   ====================================================== */