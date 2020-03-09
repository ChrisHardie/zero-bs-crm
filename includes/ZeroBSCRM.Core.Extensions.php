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
	EXTENSION Globals
   ====================================================== */

  // this is filtered in core, so if you want to remove/add to this
  // hook into zbs_exclude_from_settings filter
  global $zbsExtensionsExcludeFromSettings; $zbsExtensionsExcludeFromSettings = array('pdfinv','csvimporterlite','portal', 'cf7','givewp','cal','batchtagger','salesdash','envato','bulktag','clientportalpro','mailcampaigns','apiconnector','contactform7','awesomesupport','membermouse','bulktagger','systememailspro','woo','wpa','advancedsegments','contactform','pay');

/* ======================================================
	EXTENSION Globals
   ====================================================== */



/* ======================================================
	EXTENSION FUNCS
   ====================================================== */

#} function to detect what extensions are installed
#} ONLY WORKS after plugins_loaded
#} see https://codex.wordpress.org/Plugin_API/Action_Reference
function zeroBSCRM_extensionsInstalled(){

	#} This list var will be populated via do_action at top of every extension
	global $zeroBSCRM_extensionsInstalledList;
	return $zeroBSCRM_extensionsInstalledList;

}

#} function to return all extensions inc what extensions are installed
#} ONLY WORKS after plugins_loaded
#} see https://codex.wordpress.org/Plugin_API/Action_Reference
function zeroBSCRM_extensionsList(){
	global $zbs;

	#} This list is all extensions + which are free
	global $zeroBSCRM_extensionsCompleteList;

	// get free
	$freeExts = zeroBSCRM_extensions_free(true);

	#} Process full list inc what's on/off
	$ret = array(); foreach ($zeroBSCRM_extensionsCompleteList as $extKey => $extObj){

		#} Get name
		$extName = $extKey; if (function_exists('zeroBSCRM_extension_name_'.$extKey) == 'function') $extName = call_user_func('zeroBSCRM_extension_name_'.$extKey);
		#} if not, usefallback
		if ($extName == $extKey && isset($extObj['fallbackname'])) $extName = $extObj['fallbackname'];

		$ret[$extKey] = array(
			'name' => $extName,
			'installed' => zeroBSCRM_isExtensionInstalled($extKey),
			'free' => in_array($extKey,$freeExts),
			'meta' => $extObj
		);

	}
	
	return $ret;

}

#} Returns a list split into FREE / PAID
function zeroBSCRM_extensionsListSegmented(){

	$exts = zeroBSCRM_extensionsList(); 

	#} Sort em
	$ret = array('free'=>array(),'paid'=>array());
	foreach ($exts as $extKey => $ext){

		if ($ext['free'])
			$ret['free'][$extKey] = $ext;
		else
			$ret['paid'][$extKey] = $ext;

	}

	return $ret;

}

#} Returns a list of possible PAID exts
function zeroBSCRM_extensionsListPaid($onlyInstalledAndActive=false){

	$exts = zeroBSCRM_extensionsList(); 

	#} Sort em
	$ret = array();
	foreach ($exts as $extKey => $ext){

		if (!$ext['free']) {

			if ($onlyInstalledAndActive){

				if (isset($ext['installed']) && $ext['installed']) $ret[$extKey] = $ext;

			} else
				$ret[$extKey] = $ext;

		}

	}

	return $ret;

}

#} Returns a list of installed PAID exts
function zeroBSCRM_activeInstalledProExt(){

	return zeroBSCRM_extensionsListPaid(true);

}

// This is a meshing of Mikes method + our existing ext system
// it catches:
// Installed, branded extensions
// Installed, rebranded extensions
// Installed, unactive, branded extensions
// DOES NOT FIND:
// Installed, unactive, rebranded extensions
// ... does this by checking actives for NAMES
// ... and checking our installed ext func
// REturns array of arrays(name=>,key=>,slug=>) (where possible)
/* e.g. 
(
    [Automations] => Array
        (
            [name] => Automations
            [key] => automations
            [slug] => 
            [active] => 1
        )

    [Mail Campaigns] => Array
        (
            [name] => Zero BS CRM Extension: Mail Campaigns
            [key] => mailcampaigns
            [slug] => zero-bs-extension-mailcamps/ZeroBSCRM_MailCampaigns.php
            [active] => -1
        )

)

// note keepAllVars allows stripping of unnecessary vars (pre send to update.)
*/
function zeroBSCRM_installedProExt($ignoreIfCantFindSlug=false,$keepAllVars=false,$ignoreIfCantFindKey=true){

	$ret = array();

	// first go through our 'installedExt'
	$zbsExtInstalled = zeroBSCRM_activeInstalledProExt();
	if (is_array($zbsExtInstalled)) foreach ($zbsExtInstalled as $k => $deets){

		// will have all but only slug where ext's have this:
		$slug = ''; $file = ''; if (function_exists('zeroBSCRM_extension_file_'.$k)) {
			$file = call_user_func('zeroBSCRM_extension_file_'.$k);
			$slug = plugin_basename($file);
		}

		// if here, MUST be active :)
		$ret[$deets['name']] = array('name'=>$deets['name'],'key'=>$k,'slug'=>$slug,'active'=>1,'ver'=>'','file'=>$file);

	}

	// from: https://codex.wordpress.org/Function_Reference/get_plugins
	// Check if get_plugins() function exists. This is required on the front end of the
	// site, since it is in a file that is normally only loaded in the admin.
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// then go through installed plugins and try and grab any deactivated via name
	$zbs_all = get_plugins();
	if (is_array($zbs_all)) foreach($zbs_all as $slug => $v){

		if($v['Name'] != 'Zero BS CRM' && stripos('#'.$v['Name'], 'Zero BS') > 0){

			// is a branded one of ours (probably)

			// cleaned name
			$cleanName = str_replace('Zero BS CRM Extension: ','',$v['Name']);

			// attempt to find deets
			$key = '';
			$potentialItem = zeroBSCRM_returnExtensionDetailsFromName($cleanName);
			if (is_array($potentialItem)) $key = $potentialItem['key'];

			$active = -1; if (is_plugin_active($slug)) $active = 1;

			$ret[$cleanName] = array('name'=>$v['Name'],'key'=>$key,'slug'=>$slug,'active'=>$active,'ver'=>$v['Version'],'file'=>'');

		} else {

			// either CORE crm, or otehr plugin, or REBRANDED ver of ext
			// try to catch deactivated rebranded vers?
			#LICENSING3.0 TODO

		}
	} // / foreach plugin

	// go through RET + get versions where needed :) + weed out $ignoreIfCantFindSlug
	$finalReturn = array();
	foreach ($ret as $extName => $ext){

		$extA = $ext;
		if (empty($ext['ver']) && !empty($ext['file'])) {
			$pluginFullDeets = get_plugin_data( $ext['file'] );
			if (is_array($pluginFullDeets) && isset($pluginFullDeets['Version'])) $extA['ver'] = $pluginFullDeets['Version'];
			// might as well copy over name if here too :)
			if (is_array($pluginFullDeets) && isset($pluginFullDeets['Name'])) $extA['name'] = $pluginFullDeets['Name'];
		}

		// strip these (unnecessary vars)
		if (!$keepAllVars){
			unset($extA['file']);
		}

		// finally, clean any slugs which are coming through as zero-bs-extension-csv-importer/ZeroBSCRM_CSVImporter.php instaed of ZeroBSCRM_CSVImporter.php
		// (but keep full in 'path')
		if (strpos($extA['slug'], '/') > 0){

			$extA['path'] = $extA['slug'];
			$extA['slug'] = substr($extA['slug'], strrpos($extA['slug'], '/') + 1);

		}

		if (
			(!$ignoreIfCantFindSlug || ($ignoreIfCantFindSlug && isset($extA['slug']) && !empty($extA['slug'])))
			&&
			(!$ignoreIfCantFindKey || ($ignoreIfCantFindKey &&  isset($extA['key']) && !empty($extA['key'])))
			) {
			
			$finalReturn[$extName] = $extA;

		}
	}
	

	return $finalReturn;
}

// as above (zeroBSCRM_installedProExt) but returns a single arr for WL Core, if installed
function zeroBSCRM_installedWLCore($ignoreIfCantFindSlug=false,$keepAllVars=false,$ignoreIfCantFindKey=true){

	if (!zeroBSCRM_isWL()) return false;

	$ret = false;

	// then go through installed plugins and try and grab any deactivated via name
	$zbs_all = get_plugins(); 
	if (is_array($zbs_all)) foreach($zbs_all as $slug => $v){

		//echo plugin_basename(ZBS_ROOTFILE);//basename( dirname( ZBS_ROOTFILE ) ).'/'.ZBS_ROOTFILE;

		// if is core :) + WL
		if ($slug == plugin_basename(ZBS_ROOTFILE)){

			// is rebranded core :)

			// cleaned name
			$cleanName = $v['Name'];

			// attempt to find deets
			$key = 'core';
			$active = -1; if (is_plugin_active($slug)) $active = 1;

			$ret = array('name'=>$v['Name'],'key'=>$key,'slug'=>$slug,'active'=>$active,'ver'=>$v['Version'],'file'=>'');

		}
	} // / foreach plugin

	// go through RET + get versions where needed :) + weed out $ignoreIfCantFindSlug
	$finalReturn = array();

	$extA = $ret;
	if (empty($ret['ver']) && !empty($ret['file'])) {
		$pluginFullDeets = get_plugin_data( $ret['file'] );
		if (is_array($pluginFullDeets) && isset($pluginFullDeets['Version'])) $extA['ver'] = $pluginFullDeets['Version'];
		// might as well copy over name if here too :)
		if (is_array($pluginFullDeets) && isset($pluginFullDeets['Name'])) $extA['name'] = $pluginFullDeets['Name'];
	}

	// strip these (unnecessary vars)
	if (!$keepAllVars){
		unset($extA['file']);
	}

	// finally, clean any slugs which are coming through as zero-bs-extension-csv-importer/ZeroBSCRM_CSVImporter.php instaed of ZeroBSCRM_CSVImporter.php
	// (but keep full in 'path')
	if (strpos($extA['slug'], '/') > 0){

		$extA['path'] = $extA['slug'];
		$extA['slug'] = substr($extA['slug'], strrpos($extA['slug'], '/') + 1);

	}

	if (
		(!$ignoreIfCantFindSlug || ($ignoreIfCantFindSlug && isset($extA['slug']) && !empty($extA['slug'])))
		&&
		(!$ignoreIfCantFindKey || ($ignoreIfCantFindKey &&  isset($extA['key']) && !empty($extA['key'])))
		) {
		
		$finalReturn = $extA;

	}

	
	return $finalReturn;
}


function zeroBSCRM_extensionsInstalledCount($activatedOnly=false){

	// grabs all extensions (rebrander only grabs active, rest grabs active + deactive)
	$exts = zeroBSCRM_installedProExt();

	if (!$activatedOnly) return count($exts);

	$c = 0;
	//typo - should have been $exts not $ext. 
	foreach ($exts as $e){

		if ($e['active'] == "1") $c++;

	}
	return $c;
}

#} MS - 27th Feb 2019. This is bugged. 
#} function to detect if a specific extension is installed
#} ONLY WORKS after plugins_loaded
#} see https://codex.wordpress.org/Plugin_API/Action_Reference
function zeroBSCRM_isExtensionInstalled($extKey=''){

	#} This list var will be populated via do_action at top of every extension
	global $zeroBSCRM_extensionsInstalledList, $zbs;

	#} WH look. Something odd is going on here with this and the load order. 
	#} When doing $zeroBSCRM_extensionsInstalledList[] = 'extension-name' if an extension
	#} Loaded after this test it isn't in the array. 
	#} Certainly happened in Automations when trying to use it 

	#} Could we not have it check through the active plugins array instead? somehow. e.g
	#} I ended up doing something like this in Automations (to map the $extKey to the slug)

	/*

	#} Mike sticky test for automations which have link ins with other extensions..(i.e. can add to the array of actions)
	
	function zeroBSCRM_automations_isExtensionInstalled($slug){

			$slugArr = array(
					'convertkit' => 'ZeroBSCRM_ConvertKitConnector.php',
					'twilio'     => 'zerobscrm-ext-Twilio.php'

			);

			//slightly different way of doing things (checks active plugins, for the slugs above)
			if(array_key_exists($slug, $slugArr)){
				$check = $slugArr[$slug];
				$plugins = get_option('active_plugins');
				foreach($plugins as $plugin){
					if(stripos($plugin, $check) !== false){
						return true;
					} 
				}
			}
			return false;
	}


	*/

	//the class version should be set.
	#}WH look - this seemed to be causing a lot of pain somehow 
	/*
	if(is_array($zbs->extensions)){
		$zeroBSCRM_extensionsInstalledList = array_merge($zeroBSCRM_extensionsInstalledList, $zbs->extensions);
	}
	*/


	/* isn't keyed if (!empty($extKey) && isset($zeroBSCRM_extensionsInstalledList[$extKey])){

		return true;

	}*/
	if (count($zeroBSCRM_extensionsInstalledList) > 0) foreach ($zeroBSCRM_extensionsInstalledList as $ext){

//		$zbs->write_log("extension is " . $ext . " and extKey is " . $extKey);

		if (!empty($ext) && $ext == $extKey) return true;
	}

	#} Otherwise it's not!
	return false;

}

#} function to return a specific extension details
#} ONLY WORKS after plugins_loaded
#} see https://codex.wordpress.org/Plugin_API/Action_Reference
function zeroBSCRM_returnExtensionDetails($extKey=''){

	#} list
	global $zeroBSCRM_extensionsCompleteList;

	// get free
	$freeExts = zeroBSCRM_extensions_free(true);

	if (array_key_exists($extKey, $zeroBSCRM_extensionsCompleteList)){

		$extObj = $zeroBSCRM_extensionsCompleteList[$extKey];

		#} Get name
		$extName = $extKey; if (function_exists('zeroBSCRM_extension_name_'.$extKey) == 'function') $extName = call_user_func('zeroBSCRM_extension_name_'.$extKey);
		#} if not, usefallback
		if ($extName == $extKey && isset($extObj['fallbackname'])) $extName = $extObj['fallbackname'];

		return array(
			'key' => $extKey,
			'name' => $extName,
			'installed' => zeroBSCRM_isExtensionInstalled($extKey),
			'free' => in_array($extKey,$freeExts),
			'meta' => $extObj
		);

	}

	#} Otherwise it's not!
	return false;

}

// brutal check through list for 'Automations' etc.
function zeroBSCRM_returnExtensionDetailsFromName($extName=''){

	#} list
	global $zeroBSCRM_extensionsCompleteList;

	// get free
	$freeExts = zeroBSCRM_extensions_free(true);

	if (is_array($zeroBSCRM_extensionsCompleteList)) foreach ($zeroBSCRM_extensionsCompleteList as $key => $deets){

		// check against names
		$thisIsIt = false;

		if ($deets['fallbackname'] == $extName) $thisIsIt = true;
		if (isset($deets['name']) && $deets['name'] == $extName) $thisIsIt = true;

		if ($thisIsIt){

			return array(
				'key' => $key,
				'name' => $extName,
				'installed' => zeroBSCRM_isExtensionInstalled($key),
				'free' => in_array($key,$freeExts)
			);
		}


	}

	#} Otherwise it's not!
	return false;

}


function zeroBSCRM_hasSyncExtensionActivated(){

	// tries several plugins to see if installed
	$hasExtension = false;
	$syncExtensions = array('pay','woo','stripesync','worldpay','groovesync','googlesync','envato');
	foreach ($syncExtensions as $ext){

		if (zeroBSCRM_isExtensionInstalled($ext)){

			$hasExtension = true;
			break;

		}
	}

	return $hasExtension;

}

function zeroBSCRM_hasPaidExtensionActivated(){

	$list = zeroBSCRM_extensionsListSegmented();
	if (is_array($list['paid']) && count($list['paid']) > 0) foreach ($list['paid'] as $extKey => $extDeet){

		// test
		if (zeroBSCRM_isExtensionInstalled($extKey)) return true;

	}

	return false;

}



#} Check update - DEFUNCT - moved to transient checks 
function zeroBSCRM_extensions_checkForUpdates(){
  
  //checks extensions and flags if an update is needed (added to CRON function)
	/*
    global $zbs;

    $zbs_all = get_plugins();
    $response = wp_safe_remote_get( $zbs->urls['updates'] );
    if (isset($response['body'])) {
      $official_ver = json_decode($response['body'],true);
      $zbs_plugins = array();
      $zbs_update_avail = false;
      foreach($zbs_all as $zbs_plugin){
        if($zbs_plugin['Name'] != 'Zero BS CRM'){   //let Zero BS CRM Core be handled by WP.org..
            $stripos = stripos($zbs_plugin['Name'], 'Zero Bs');
            if($stripos !== false){
             if($official_ver[$zbs_plugin['Name']] != $zbs_plugin['Version']){
                $zbs_update_avail = true;
             }
           }
        }
      }
    } // if body
    return $zbs_update_avail;
	*/
}


/* ======================================================
	/ EXTENSION FUNCS
   ====================================================== */



/* ======================================================
	EXTENSION DETAILS / FREE/included EXTENSIONS
   ====================================================== */


#} JSON short description only
// REFRESH this is ever update woo/products: From: https://zerobscrm.com/wp-json/zbsextensions/v1/extensions/0
function zeroBSCRM_serve_cached_extension_block(){
  // a localhosted version of the extensions array. Loading images from local.
  $plugin_url = plugins_url('', ZBS_ROOTFILE) . '/';

  $imgs = array(
	'ph'				=> $plugin_url . 'i/1px.png',
	'rm' 				=> $plugin_url .'i/rm-connect-300x142.png' ,
	'live'				=> $plugin_url . 'i/livestorm-logo-300x142.jpg' , 
	'exit'				=> $plugin_url . 'i/exit-bee-product-image-300x142.png' , 
	'wp'				=> $plugin_url . 'i/wp-utils-2-300x142.png' , 
	'as'				=> $plugin_url . 'i/advanced-segments-for-crm-300x142.png' , 
	'aweb'				=> $plugin_url . 'i/aweber-extension-tile-1-300x142.png' , 
	'mm'				=> $plugin_url . 'i/zbs-crm-membermouse-product-300x142.png' , 
	'auto'				=> $plugin_url . 'i/zbs-crm-automations-300x142.png' , 
	'api'				=> $plugin_url . 'i/zbs-extension-crm-api-connector-300x142.png' , 
	'cpp'				=> $plugin_url . 'i/crm-extension-client-portal-pro-300x142.png' , 
	'passw'				=> $plugin_url . 'i/crm-password-manager-300x142.png' , 
	'twilio'			=> $plugin_url . 'i/twilio-extension-300x142.png' , 
	'mailchimp'			=> $plugin_url . 'i/mail-chimp-feat-300x142.png' , 
	'awesomesupport'	=> $plugin_url . 'i/awesome-support-cover-300x142.png' , 
	'envato'			=> $plugin_url . 'i/envato-ext-tile-300x142.png' , 
	'convertkit'		=> $plugin_url . 'i/convert-kit-banner-zbs-300x142.png' , 
	'batchtag'			=> $plugin_url . 'i/bulk-tagger-ext-2-300x142.png' , 
	'googlecontact'		=> $plugin_url . 'i/google-contacts-sync-300x142.png' , 
	'groove'			=> $plugin_url . 'i/groove-sync-300x142.png' , 
	'contactform'		=> $plugin_url . 'i/contact-form-7-300x142.png' , 
	'stripe'			=> $plugin_url . 'i/stripe-ext-300x142.png' , 
	'worldpay'			=> $plugin_url . 'i/worldpay-ext-300x142.png' , 
	'invpro'			=> $plugin_url . 'i/invoicing-pro-ext-300x142.png' , 
	'gravity'			=> $plugin_url . 'i/grav-forms-ext-zbs-300x142.png' , 
	'csvpro'			=> $plugin_url . 'i/csv-importer-pro-300x142.png' , 
	'mailcamp'			=> $plugin_url . 'i/mail-campaigns-ext-300x142.png' , 
	'paypal'			=> $plugin_url . 'i/paypal-ext-300x142.png' , 
	'woosync'			=> $plugin_url . 'i/woo-ext-300x142.png' , 
	'salesdash'			=> $plugin_url . 'i/sales-dashboard-ext-300x142.png' , 


   );
   
  $json = '{"data":{},"count":29,"paid":[{"id":26172,"name":"Registration Magic Connect","short_desc":"Capture your Registration Magic sign ups into Zero BS CRM. Including First Name and Last Name.","date":{"date":"2019-01-08 12:57:32.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"59","regular_price":"59","image":"'.$imgs['rm'].'","extkey":"registrationmagic"},{"id":25432,"name":"Livestorm","short_desc":"The Zero BS CRM livestorm connector automatically adds your Livestorm webinar sign ups into Zero BS CRM.","date":{"date":"2018-12-02 22:03:07.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"79","regular_price":"79","image":"'.$imgs['live'].'","extkey":"livestorm"},{"id":25431,"name":"ExitBee Connect","short_desc":"Exit Bee Connect automatically adds your Exit Bee form completions into Zero BS CRM.","date":{"date":"2018-12-02 21:59:18.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"79","regular_price":"79","image":"'.$imgs['exit'].'","extkey":"exitbee"},{"id":25336,"name":"WordPress Utilities","short_desc":"The Zero BS CRM WordPress utilities extension adds your website registrations to your Zero BS CRM.","date":{"date":"2018-11-19 22:50:37.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"59","regular_price":"59","image":"'.$imgs['wp'].'","extkey":"wordpressutilities"},{"id":25174,"name":"Advanced Segments","short_desc":"Easily divide your contacts into dynamic subgroups and manage your contacts effectively","date":{"date":"2018-09-21 10:44:24.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"49","regular_price":"49","image":"'.$imgs['as'].'","extkey":"advancedsegments"},{"id":24955,"name":"AWeber Connect","short_desc":"Connect your aWeber to your Zero BS CRM and add new Zero BS CRM contacts to your aWeber list.","date":{"date":"2018-07-26 05:02:28.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"39","regular_price":"39","image":"'.$imgs['aweb'].'","extkey":"aweber"},{"id":24763,"name":"Membermouse Connect","short_desc":"Enhance your MemberMouse subscription website by integrating your data with Zero BS CRM","date":{"date":"2018-07-24 17:29:28.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"79","regular_price":"79","image":"'.$imgs['mm'].'","extkey":"membermouse"},{"id":24696,"name":"Automations","short_desc":"Let Automations handle the mundane tasks within your CRM and save yourself time.","date":{"date":"2018-07-22 15:24:14.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"79","regular_price":"79","image":"'.$imgs['auto'].'","extkey":"automations"},{"id":24692,"name":"API Connector","short_desc":"Connects your website to Zero BS CRM via the API. Supports Forms, Website Registrations. Use on as many external sites as you like.","date":{"date":"2018-07-09 00:46:59.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"59","regular_price":"59","image":"'.$imgs['api'].'","extkey":"apiconnector"},{"id":24676,"name":"Client Portal Pro","short_desc":"Customise your Client Portal, Allow File Downloads, Display Tasks and Tickets plus much more","date":{"date":"2018-07-06 17:40:11.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"79","regular_price":"79","image":"'.$imgs['cpp'].'","extkey":"clientportalpro"},{"id":24569,"name":"Client Password Manager","short_desc":"Securely manage usernames and passwords for your clients websites, servers, and other logins.","date":{"date":"2018-05-28 14:09:12.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"39","regular_price":"39","image":"'.$imgs['passw'].'","extkey":"passwordmanager"},{"id":20570,"name":"Twilio Connect","short_desc":"Send SMS messages to your contacts, leads, and customers","date":{"date":"2017-11-24 11:53:18.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"49","regular_price":"49","image":"'.$imgs['twilio'].'","extkey":"twilio"},{"id":18274,"name":"MailChimp","short_desc":"Subscribe your ZBS CRM contacts to your MailChimp email marketing list automatically.","date":{"date":"2017-07-27 09:35:08.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"39","regular_price":"39","image":"'.$imgs['mailchimp'].'","extkey":"mailchimp"},{"id":18221,"name":"Awesome Support","short_desc":"Integrate Zero BS CRM with Awesome Support Plugin and see your Customers support ticket information within your CRM.","date":{"date":"2017-07-24 19:49:23.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"29","regular_price":"29","image":"'.$imgs['awesomesupport'].'","extkey":"awesomesupport"},{"id":17965,"name":"Envato Connect","short_desc":"Imports your item sales history from Envato into your ZBS CRM as transactions.","date":{"date":"2017-07-11 10:25:19.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"29","regular_price":"29","image":"'.$imgs['envato'].'","extkey":"envato"},{"id":17921,"name":"ConvertKit","short_desc":"Subscribe your contacts to your ConvertKit list automatically. Subscribe to a form, add a tag or subscribe to a sequence","date":{"date":"2017-07-10 10:25:41.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"39","regular_price":"39","image":"'.$imgs['convertkit'].'","extkey":"convertkit"},{"id":17692,"name":"Bulk Tagger","short_desc":"Bulk tag your customers based on transaction keywords. Target customers based on their transaction tags.","date":{"date":"2017-07-02 10:09:47.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"29.00","regular_price":"29.00","image":"'.$imgs['batchtag'].'","extkey":"batchtag"},{"id":17425,"name":"Google Contacts Sync","short_desc":"Retrieve all customer data from Google Contacts. Keep all Leads in your CRM and start managing your contacts effectively.","date":{"date":"2017-06-05 12:32:02.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"39.00","regular_price":"39.00","image":"'.$imgs['googlecontact'].'","extkey":"googlecontact"},{"id":17413,"name":"Groove Sync","short_desc":"Retrieve all customer data from Groove\u00a0automatically. Keep all Leads in your CRM.","date":{"date":"2017-06-02 16:25:18.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"39.00","regular_price":"39.00","image":"'.$imgs['groove'].'","extkey":"groove"},{"id":17409,"name":"Contact Form 7","short_desc":"Use Contact Form 7 to collect leads and customer info. Save time by automating your lead generation process.","date":{"date":"2017-06-01 13:05:12.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"49","regular_price":"49","image":"'.$imgs['contactform'].'","extkey":"contactform"},{"id":17378,"name":"Stripe Sync","short_desc":"Retrieve all customer data from Stripe automatically.","date":{"date":"2017-05-30 18:30:29.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"49.00","regular_price":"49.00","image":"'.$imgs['stripe'].'","extkey":"stripe"},{"id":17356,"name":"WorldPay Sync","short_desc":"Retrieve all customer data from WorldPay\u00a0automatically. Works great with Sales Dashboard.","date":{"date":"2017-05-24 12:25:12.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"49.00","regular_price":"49.00","image":"'.$imgs['worldpay'].'","extkey":"worldpay"},{"id":17067,"name":"Invoicing PRO","short_desc":"Invoicing PRO lets your\u00a0customers pay their invoices right from your Client Portal using either PayPal or Stripe.","date":{"date":"2017-02-21 11:06:47.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"49","regular_price":"49","image":"'.$imgs['invpro'].'","extkey":"invpro"},{"id":17030,"name":"Gravity Forms Connect","short_desc":"Use Gravity Forms to collect leads and customer info. Save time by automating your lead generation process.","date":{"date":"2017-01-25 03:31:56.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"49","regular_price":"49","image":"'.$imgs['gravity'].'","extkey":"gravity"},{"id":16690,"name":"CSV Importer PRO","short_desc":"Import your existing customer data into the Zero BS CRM system with our super simple CSV importer extension.","date":{"date":"2016-06-20 23:02:27.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"29","regular_price":"29","image":"'.$imgs['csvpro'].'","extkey":"csvpro"},{"id":16688,"name":"Mail Campaigns","short_desc":"Send emails to targeted segments of customers with this easy to use, powerful mail extension. Contact your customers easily.","date":{"date":"2016-06-20 22:53:09.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"79","regular_price":"79","image":"'.$imgs['mailcamp'].'","extkey":"mailcamp"},{"id":16685,"name":"PayPal Sync","short_desc":"Retrieve all customer data from PayPal automatically. Works great with Sales Dashboard.","date":{"date":"2016-06-16 23:00:34.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"49","regular_price":"49","image":"'.$imgs['paypal'].'","extkey":"paypal"},{"id":16621,"name":"Woo Sync","short_desc":"Retrieve all customer data from WooCommerce\u00a0automatically. Works great with Sales Dashboard.","date":{"date":"2016-06-11 11:56:55.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"49","regular_price":"49","image":"'.$imgs['woosync'].'","extkey":"woosync"},{"id":16609,"name":"Sales Dashboard","short_desc":"<p class=\"p1\"><span class=\"s1\">The ultimate sales dashboard. Track Gross Revenue, Net Revenue, Customer growth right from your CRM.<\/span><\/p>","date":{"date":"2016-06-05 14:04:16.000000","timezone_type":3,"timezone":"Europe\/London"},"price":"129","regular_price":"129","image":"'.$imgs['salesdash'].'","extkey":"salesdash"}]}';
  return $json;
}


#} Vars
global $zeroBSCRM_extensionsInstalledList, $zeroBSCRM_extensionsCompleteList;


#} Fill out full list - NOTE: this is currently only used for extra info for extensions page
#} Sould still use the functions like "zeroBSCRM_extension_name_mailcampaigns" for names etc.
$zeroBSCRM_extensionsCompleteList = array(
	
	#} Paid
	'automations' => array(

		'fallbackname' => 'Automations', # This is if no name func is found... 
		'desc' => __('Let our Automations do the mundane tasks for you.',"zero-bs-crm"),
		'url' => 'http://zerobscrm.com/extensions/automations/',
		'colour' => '#009cde',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/automations/'

	),



	'paypal' => array(

		'fallbackname' => 'PayPal Sync', # This is if no name func is found... 
		'desc' => __('Retrieve all customer data from PayPal automatically.',"zero-bs-crm"),
		'url' => 'http://zerobscrm.com/extensions/paypal-sync/',
		'colour' => '#009cde',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/paypal-sync/'

	),

	'woosync' => array(

		'fallbackname' => 'Woo Sync', # This is if no name func is found... 
		'desc' => __('Retrieve all customer data from WooCommerce.',"zero-bs-crm"),
		'url' => 'http://zerobscrm.com/extensions/woo-sync/',
		'colour' => 'rgb(216, 187, 73)',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/woo-sync/'

	),
	'stripe' => array(

		'fallbackname' => 'Stripe Sync', # This is if no name func is found... 
		'desc' => __('Retrieve all customer data from Stripe automatically.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/stripe-sync/',
		'colour' => '#5533ff',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/stripe-sync/'

	),
	'worldpay' => array(

		'fallbackname' => 'WorldPay Sync', # This is if no name func is found... 
		'desc' => __('Create Customers from World Pay Sync.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/worldpay-sync/',
		'colour' => '#f01e14', 
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/worldpay-sync/',

	),


	//Added to Extension 3rd Sept
	'membermouse' => array(

		'fallbackname' => 'Member Mouse', # This is if no name func is found... 
		'desc' => __('Imports your Membermouse user data to your CRM.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/membermouse/',
		'colour' => '#f01e14', 
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/member-mouse/',

	),

	'invpro' => array(

		'fallbackname' => 'Invoicing Pro', # This is if no name func is found... 
		'desc' => __('Collect invoice payments directly from ZBS, with PayPal.',"zero-bs-crm"),
		'url' => 'http://zerobscrm.com/extensions/invoicing-pro/',
		'colour' => '#1e0435',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/invoicing-pro/'

	),
	'csvpro' => array(

		'fallbackname' => 'CSV Importer PRO', # This is if no name func is found... 
		'desc' => __('Import existing customer data from CSV (Pro Version)',"zero-bs-crm"),
		'url' => 'http://zerobscrm.com/extensions/simple-csv-importer/',
		'colour' => 'green',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/csv-importer-pro/',

		'shortname' => 'CSV Imp. PRO' #used where long name won't fit

	),
	'mailcamp' => array(

		'fallbackname' => 'Mail Campaigns', # This is if no name func is found... 
		'desc' => __('Send emails to targeted segments of customers.',"zero-bs-crm"),
		'url' => 'http://zerobscrm.com/extensions/mail-campaigns/',
		'colour' => 'rgb(173, 210, 152)',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/mail-campaigns/'

	),
	'salesdash' => array(

		'fallbackname' => 'Sales Dashboard', # This is if no name func is found... 
		'desc' => __('The ultimate sales dashboard. See sales trends and more',"zero-bs-crm"),
		'url' => 'http://zerobscrm.com/extensions/sales-dashboard/',
		'colour' => 'black',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/sales-dashboard/'

	),
	'gravity' => array(

		'fallbackname' => 'Gravity Forms', # This is if no name func is found... 
		'desc' => __('Create Customers from Gravity Forms (Integration).',"zero-bs-crm"),
		'url' => 'http://zerobscrm.com/extensions/gravity-forms/',
		'colour' => '#91a8ad', #grav forms :) #91a8ad
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/gravity-forms/'

	),

	//NEW CODE ADDED TO CONTACT FORM 7
	'contactform' => array(

		'fallbackname' => 'Contact Form 7 Connector', # This is if no name func is found... 
		'desc' => __('Use Contact Form 7 to collect leads and customer info.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/contact-form-7/',
		'colour' => '#e2ca00',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/contact-form-7/'

	),
	'googlecontact' => array(

		'fallbackname' => 'Google Contact Sync', # This is if no name func is found... 
		'desc' => __('Retrieve all customer data from Google Contacts.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/google-contacts-sync/',
		'colour' => '#91a8ad', 
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/google-contacts-sync/'

	),
	'groove' => array(

		'fallbackname' => 'Groove Sync', # This is if no name func is found... 
		'desc' => __('Retrieve all customer data from Groove automatically.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/groove-sync/',
		'colour' => '#11ABCC',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/groove-sync/'

	),

	'convertkit' => array(

		'fallbackname' => 'ConvertKit Connector', # This is if no name func is found... 
		'desc' => __('Add your ZBS Contacts to your ConvertKit list.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/convertkit/',
		'colour' => '#11ABCC',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/convertkit-seva/'

	),


	//added to Twilio Connect 3rd Sept
	'twilio' => array(

		'fallbackname' => 'Twilio Connect', # This is if no name func is found... 
		'desc' => __('Send SMS from your Twilio Account.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/twilio/',
		'colour' => '#11ABCC',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/twilio-connector/'



	),



	'envato' => array(

		'fallbackname' => 'Envato Order Importer', # This is if no name func is found... 
		'desc' => __('Import your transaction history from Envato.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/envato-connect/',
		'colour' => '#11ABCC',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/envato/'

	),


	'awesomesupport' => array(

		'fallbackname' => 'Awesome Support Connector', # This is if no name func is found... 
		'desc' => __('See your contacts support ticket overview.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/awesome-support/',
		'colour' => '#11ABCC',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/awesome-support/'

	),

	'mailchimp' => array(

		'fallbackname' => 'MailChimp Connector', # This is if no name func is found... 
		'desc' => __('Add your ZBS Contacts to your Mailchimp email list.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/mailchimp/',
		'colour' => '#11ABCC',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/mailchimp/'

	),

	'batchtag' => array(

		'fallbackname' => 'Bulk Tagger', # This is if no name func is found... 
		'desc' => __('Bulk Tag your customers based on their transaction strings',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/bulk-tagger/',
		'colour' => '#11ABCC',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/bulk-tagger/'

	),

	'clientportalpro' => array(

		'fallbackname' => 'Client Portal Pro', # This is if no name func is found... 
		'desc' => __('Customise your Client Portal, Allow File Downloads, Display Tasks and more',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/client-portal-pro/',
		'colour' => '#11ABCC',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/client-portal-pro/'

	),


	'apiconnector' => array(

		'fallbackname' => 'API Connector', # This is if no name func is found... 
		'desc' => __('Connects your website to Zero BS CRM via the API. Supports Forms & Registrations.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/api-connector/',
		'colour' => '#11ABCC',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/api-connector/'

	),


	'passwordmanager' => array(
		'fallbackname' => 'Client Password Manager', # This is if no name func is found... 
		'desc' => __('Securely manage usernames and passwords for your clients websites, servers, and other logins.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/client-password-manager/',
		'colour' => '#11ABCC',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/client-password-manager/'

	),

	'advancedsegments' => array(
		'fallbackname' => 'Advanced Segments', # This is if no name func is found... 
		'desc' => __('Easily divide your contacts into dynamic subgroups and manage your contacts effectively.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/advanced-segments/',
		'colour' => '#aa73ac',
		'helpurl' => 'https://zerobscrm.com/kb/'

	),

	'aweber' => array(
		'fallbackname' => 'AWeber Connect',
		'desc' => __('Send Zero BS CRM contacts to your AWeber list.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/aweber-connect/',
		'colour' => '#aa73ac',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/aweber-connect/'
	),

	'livestorm' => array(
		'fallbackname' => 'Live Storm Connect',
		'desc' => __('Capture webinar sign ups to your CRM.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/livestorm-connect/',
		'colour' => '#aa73ac',
		'helpurl' => 'https://zerobscrm.com/kb/'
	),

	'exitbee' => array(
		'fallbackname' => 'Exit Bee Connect',
		'desc' => __('Convert abandoning visitors into customers.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/exitbee-connect/',
		'colour' => '#aa73ac',
		'helpurl' => 'https://zerobscrm.com/kb/'
	),

	'wordpressutilities' => array(
		'fallbackname' => 'WordPress Utilities',
		'desc' => __('Capture website sign ups into Zero BS CRM.',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/wordpress-utilities/',
		'colour' => '#aa73ac',
		'helpurl' => 'https://zerobscrm.com/kb/article-categories/wordpress-utilities/'
	),

	'activityreporter' => array(
		'fallbackname' => 'Activity Reporter',
		'desc' => __('Generates reports based on Contact or Company Logs',"zero-bs-crm"),
		'url' => 'https://zerobscrm.com/product/activity-reporter/',
		'colour' => '#aa73ac',
		'helpurl' => 'https://zerobscrm.com/kb/'
	),



	#} ============= Free =====

	'portal' => array(

		'fallbackname' => 'Customer Portal', # This is if no name func is found... 
		'imgstr' => '<i class="fa fa-users" aria-hidden="true"></i>',
		'desc' => __('Add a client area to your website.',"zero-bs-crm"),
		'colour' => '#833a3a',
		'helpurl' => 'https://zerobscrm.com/kb/',

		'shortName' => 'Portal'

	),

	'api' => array(

		'fallbackname' => 'Zero BS API', # This is if no name func is found... 
		'imgstr' => '<i class="fa fa-random" aria-hidden="true"></i>',
		'desc' => __('Enable the API area of your CRM.',"zero-bs-crm"),
		'colour' => '#000000',
		'helpurl' => 'https://zerobscrm.com/kb/',

		'shortName' => 'API'

	),

	'cal' => array(

		'fallbackname' => __('Zero BS Task Scheduler',"zero-bs-crm"), # This is if no name func is found... 
		'imgstr' => '<i class="fa fa-calendar" aria-hidden="true"></i>',
		'desc' => __('Enable ZBS CRM Task Scheduler.',"zero-bs-crm"),
		'colour' => '#ad6d0d',
		'helpurl' => 'https://zerobscrm.com/kb/',

		'shortName' => 'Calendar'

	),


	'quotebuilder' => array(

		'fallbackname' => 'Quote Builder', # This is if no name func is found... 
		'imgstr' => '<i class="fa fa-file-text-o" aria-hidden="true"></i>',
		'desc' => __('Write and send professional proposals from ZBS.',"zero-bs-crm"),
		'colour' => '#1fa67a',
		'helpurl' => 'https://zerobscrm.com/kb/'

	),

	'invbuilder' => array(

		'fallbackname' => 'Invoice Builder', # This is if no name func is found... 
		'imgstr' => '<i class="fa fa-file-text-o" aria-hidden="true"></i>',
		'desc' => __('Write and send professional invoices from ZBS.',"zero-bs-crm"),
		'colour' => '#2a044a',
		'helpurl' => 'https://zerobscrm.com/kb/'

	),

	'pdfinv' => array(

		'fallbackname' => 'PDF Invoicing', # This is if no name func is found... 
		'imgstr' => '<i class="fa fa-file-pdf-o" aria-hidden="true"></i>',
		'desc' => __('Want PDF Invoices? Get this installed.',"zero-bs-crm"),
		'colour' => 'green',
		'helpurl' => 'https://zerobscrm.com/kb/'

	),

	'transactions' => array(

		'fallbackname' => 'Transactions', # This is if no name func is found... 
		'imgstr' => '<i class="fa fa-file-shopping-cart" aria-hidden="true"></i>',
		'desc' => __('Log transactions in your CRM.',"zero-bs-crm"),
		'colour' => 'green',
		'helpurl' => 'https://zerobscrm.com/kb/'

	),


	'forms' => array(

		'fallbackname' => 'Front-end Forms', # This is if no name func is found... 
		'imgstr' => '<i class="fa fa-keyboard-o" aria-hidden="true"></i>',
		'desc' => __('Useful front-end forms to capture leads.',"zero-bs-crm"),
		'colour' => 'rgb(126, 88, 232)',
		'helpurl' => 'https://zerobscrm.com/kb/',
		'shortname' => 'Forms' #used where long name won't fit

	),

	#} Free ver
	'csvimporterlite' => array(

		'fallbackname' => 'CSV Importer LITE', # This is if no name func is found... 
		'imgstr' => '<i class="fa fa-upload" aria-hidden="true"></i>',
		'desc' => __('Lite Version of CSV Customer Importer',"zero-bs-crm"),
		'url' => 'http://zerobscrm.com/extensions/simple-csv-importer/',
		'colour' => 'green',
		'helpurl' => 'https://zerobscrm.com/kb/',
		'shortname' => 'CSV Imp. LITE', #used where long name won't fit
		'prover' => 'csvpro' #} if this is set, and 'csvimporter' ext exists, it'll default to "PRO installed"

	)

); #} #coreintegration

#} This deactivates all active ZBS extensions (used by migration routine for v3.0 - be careful if alter as back-compat may be comprimised)
function zeroBSCRM_extensions_deactivateAll(){

	// count
	$c = 0;

	// retrieve extensions
	$extensions = zeroBSCRM_installedProExt();                

	// Disable extensions
	if (is_array($extensions)) foreach ($extensions as $shortName => $e){

    	if (isset($e['path'])) deactivate_plugins( plugin_basename( $e['path'] ) ); 
    	$c++;

	}

	return $c;
}

#} This activates a given extension
// No point, just use activate_plugin vanilla. function zeroBSCRM_extensions_activate($path=false){}


#} Free Array
//this is a simpler version, with better icons (from the welcome to ZBS page and a description)
//have also added "transactions" to here. CSVimporterLite I would not see as a "module" to disable. Think should be the areas
function zeroBSCRM_extensions_free($justKeys=false){

	$exts = array(

			'csvimporterlite' => false, // false = doesn't show on ext manager page

			'portal' => array(
						'name' => __('Client Portal', 'zero-bs-crm'),
						'i' => 'cpp.png',
						'short_desc' => __('Adds a client area to your CRM install so they can see  their documents.', 'zero-bs-crm')
			),
			'api' => array(
						'name' => __('API', 'zero-bs-crm'),
						'i' => 'api.png',
						'short_desc' => __('The CRM API lets you interact with ZBS CRM via the application program interface.', 'zero-bs-crm')
			),
			'cal' => array(
						'name' => __('Tasks', 'zero-bs-crm'),
						'i' => 'task-cal.png',
						'short_desc' => __('Manage tasks for your contacts and what you need to do for them.', 'zero-bs-crm')
			),
			'quotebuilder' => array(
						'name' => __('Quotes', 'zero-bs-crm'),
						'i' => 'quotes.png',
						'short_desc' => __('Offer Quotes for your contacts to help you win more business.', 'zero-bs-crm')
			),
			'invbuilder' => array(
						'name' => __('Invoices', 'zero-bs-crm'),
						'i' => 'invoices.png',
						'short_desc' => __('Send invoices to your clients and allow them to pay online.', 'zero-bs-crm')
			),
			'pdfinv' => array(
						'name' => __('PDF Engine', 'zero-bs-crm'),
						'i' => 'pdf.png',
						'short_desc' => __('Supports PDF invoicing and PDF quotes (plus more).', 'zero-bs-crm')
			),
			'forms' => array(
						'name' => __('Forms', 'zero-bs-crm'),
						'i' => 'form.png',
						'short_desc' => __('Capture contacts into your CRM using our simple form solutions.', 'zero-bs-crm')
			),
			'transactions' => array(
						'name' => __('Transactions', 'zero-bs-crm'),
						'i' => 'transactions.png',
						'short_desc' => __('Log transactions against contacts and see their total value in the CRM.', 'zero-bs-crm')
			)
	);

	if ($justKeys) return array_keys($exts);

	return $exts;

}


#} Free extensions name funcs
function zeroBSCRM_extension_name_pdfinv(){ return __('PDF Engine',"zero-bs-crm"); }
function zeroBSCRM_extension_name_forms(){ return __('Front-end Forms',"zero-bs-crm"); }
function zeroBSCRM_extension_name_quotebuilder(){ return __('Quotes',"zero-bs-crm"); }
function zeroBSCRM_extension_name_invbuilder(){ return __('Invoicing',"zero-bs-crm"); }
function zeroBSCRM_extension_name_csvimporterlite(){ return __('CSV Importer LITE',"zero-bs-crm"); }
function zeroBSCRM_extension_name_portal(){ return __('Client Portal',"zero-bs-crm"); }
function zeroBSCRM_extension_name_api(){ return __('API',"zero-bs-crm"); }
function zeroBSCRM_extension_name_cal(){ return __('Tasks',"zero-bs-crm"); }

function zeroBSCRM_extension_name_transactions(){ return __('Transactions',"zero-bs-crm"); }

#} Settings page for PDF Invoicing (needs writing)
#} function zeroBSCRM_html_settings_pdfinv


// hard deletes any extracted repo (e.g. dompdf)
function zeroBSCRM_extension_remove_dl_repo($repoName=''){

	if (in_array($repoName,array('dompdf'))){

		// this is here to stop us ever accidentally using zeroBSCRM_del
		define('ZBS_OKAY_TO_PROCEED',time());
		zeroBSCRM_del(ZEROBSCRM_PATH.'includes/'.$repoName);

	}

}


#} WH 2.0.6 - added to check installed pre-use, auto-installs if not present (or marks uninstalled + returns false)
#} $checkInstallFonts allows you to just check dompdf, not the piggy-backed pdf fonts installer too (as PDFBuilder needs this)
function zeroBSCRM_extension_checkinstall_pdfinv($checkInstallFonts=true){

	global $zbs; 
	
	// retrieve lib path
	$includeFile = $zbs->libInclude('dompdf');

	$shouldBeInstalled = zeroBSCRM_getSetting('feat_pdfinv');
	if ($shouldBeInstalled == "1" && !empty($includeFile) && !file_exists($includeFile)){

		#} Brutal really, just set the setting
		global $zbs;
		$zbs->settings->update('feat_pdfinv',0);

		#} Returns true/false
		zeroBSCRM_extension_install_pdfinv();

	}

	$fontsInstalled = zeroBSCRM_getSetting('pdf_fonts_installed');
	if ($checkInstallFonts && $shouldBeInstalled == "1" && $fontsInstalled !== 1){

		// check fonts
		require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.PDFBuilder.php');
		zeroBSCRM_PDFBuilder_retrieveFonts();

	}

}

#} Install funcs for free exts
function zeroBSCRM_extension_install_pdfinv(){

	global $zbs;
	
	// retrieve lib path
	$includeFilePath = $zbs->libPath('dompdf');
	$includeFile = $zbs->libInclude('dompdf');

	#} Check if already downloaded libs:
	if (!empty($includeFile) && !file_exists($includeFile)){

		global $zbs;

		#} Libs appear to need downloading..
			
			set_time_limit(0); #} Just incase
			
			#} dirs
			$workingDir = ZEROBSCRM_PATH.'temp'.time(); if (!file_exists($workingDir)) wp_mkdir_p($workingDir);
			$endingDir = $includeFilePath; if (!file_exists($endingDir)) wp_mkdir_p($endingDir);

			if (file_exists($endingDir) && file_exists($workingDir)){

				#} Retrieve zip
				$libs = zeroBSCRM_retrieveFile($zbs->urls['extdlrepo'].'pdfinv.zip',$workingDir.'/pdfinv.zip');

				#} Expand
				if (file_exists($workingDir.'/pdfinv.zip')){

					#} Should checksum?

					#} For now, expand zip
					$expanded = zeroBSCRM_expandArchive($workingDir.'/pdfinv.zip',$endingDir.'/');

					#} Check success?
					if (file_exists($includeFile)){

						#} All appears good, clean up
						if (file_exists($workingDir.'/pdfinv.zip')) unlink($workingDir.'/pdfinv.zip');
						if (file_exists($workingDir)) rmdir($workingDir);

						#} Brutal really, just set the setting
						global $zbs;
						$zbs->settings->update('feat_pdfinv',1);

						#} add to installed?
						global $zeroBSCRM_extensionsInstalledList;
						if (!is_array($zeroBSCRM_extensionsInstalledList)) $zeroBSCRM_extensionsInstalledList = array();
						$zeroBSCRM_extensionsInstalledList[] = 'pdfinv';

						#} Also install pdf fonts 
						require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.PDFBuilder.php');
						zeroBSCRM_PDFBuilder_retrieveFonts();

						return true;

					} else {

						#} Add error msg
						global $zbsExtensionInstallError;
						$zbsExtensionInstallError = __('ZBS CRM was not able to extract the libraries it needs to in order to install PDF Engine.',"zero-bs-crm");

					}


				} else {

					#} Add error msg
					global $zbsExtensionInstallError;
					$zbsExtensionInstallError = __('ZBS CRM was not able to download the libraries it needs to in order to install PDF Engine.',"zero-bs-crm");

				}


			} else {

				#} Add error msg
				global $zbsExtensionInstallError;
				$zbsExtensionInstallError = __('ZBS CRM was not able to create the directories it needs to in order to install PDF Engine.',"zero-bs-crm");

			}


	} else {

		#} Already exists...

		#} Brutal really, just set the setting
		global $zbs;
		$zbs->settings->update('feat_pdfinv',1);

		#} Make it show up in the array again
		global $zeroBSCRM_extensionsInstalledList;
		if (!is_array($zeroBSCRM_extensionsInstalledList)) $zeroBSCRM_extensionsInstalledList = array();
		$zeroBSCRM_extensionsInstalledList[] = 'pdfinv';

		#} Also install pdf fonts 
		require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.PDFBuilder.php');
		zeroBSCRM_PDFBuilder_retrieveFonts();

		return true;

	}

	#} Return fail
	return false;

}

#} Uninstall funcs for free exts
function zeroBSCRM_extension_uninstall_pdfinv(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_pdfinv',0);

	#} remove from this list
	global $zeroBSCRM_extensionsInstalledList;
	$ret = array(); foreach ($zeroBSCRM_extensionsInstalledList as $x) if ($x !== 'pdfinv') $ret[] = $x;
	$zeroBSCRM_extensionsInstalledList = $ret;

	return true;

}

#} Transactions: New 2.98.2
function zeroBSCRM_extension_install_transactions(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_transactions',1);

	#} add to installed?
	global $zeroBSCRM_extensionsInstalledList;
	if (!is_array($zeroBSCRM_extensionsInstalledList)) $zeroBSCRM_extensionsInstalledList = array();
	$zeroBSCRM_extensionsInstalledList[] = 'transactions';

	return true;

}
function zeroBSCRM_extension_uninstall_transactions(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_transactions',-1);

	#} remove from this list
	global $zeroBSCRM_extensionsInstalledList;
	$ret = array(); foreach ($zeroBSCRM_extensionsInstalledList as $x) if ($x !== 'transactions') $ret[] = $x;
	$zeroBSCRM_extensionsInstalledList = $ret;

	return true;

}

function zeroBSCRM_extension_install_forms(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_forms',1);

	#} add to installed?
	global $zeroBSCRM_extensionsInstalledList;
	if (!is_array($zeroBSCRM_extensionsInstalledList)) $zeroBSCRM_extensionsInstalledList = array();
	$zeroBSCRM_extensionsInstalledList[] = 'forms';

	return true;

}
function zeroBSCRM_extension_uninstall_forms(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_forms',-1);

	#} remove from this list
	global $zeroBSCRM_extensionsInstalledList;
	$ret = array(); foreach ($zeroBSCRM_extensionsInstalledList as $x) if ($x !== 'forms') $ret[] = $x;
	$zeroBSCRM_extensionsInstalledList = $ret;

	return true;

}

function zeroBSCRM_extension_install_cal(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_calendar',1);

	#} add to installed?
	global $zeroBSCRM_extensionsInstalledList;
	if (!is_array($zeroBSCRM_extensionsInstalledList)) $zeroBSCRM_extensionsInstalledList = array();
	$zeroBSCRM_extensionsInstalledList[] = 'cal';

	return true;

}
function zeroBSCRM_extension_uninstall_cal(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_calendar',-1);

	#} remove from this list
	global $zeroBSCRM_extensionsInstalledList;
	$ret = array(); foreach ($zeroBSCRM_extensionsInstalledList as $x) if ($x !== 'cal') $ret[] = $x;
	$zeroBSCRM_extensionsInstalledList = $ret;

	return true;

}



function zeroBSCRM_extension_install_quotebuilder(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_quotes',1);

	#} Set this so as wp flushes permalinks on next Load
	zeroBSCRM_rewrite_setToFlush();

	#} add to installed?
	global $zeroBSCRM_extensionsInstalledList;
	if (!is_array($zeroBSCRM_extensionsInstalledList)) $zeroBSCRM_extensionsInstalledList = array();
	$zeroBSCRM_extensionsInstalledList[] = 'quotebuilder';

	return true;

}
function zeroBSCRM_extension_uninstall_quotebuilder(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_quotes',-1);

	#} Set this so as wp flushes permalinks on next Load
	zeroBSCRM_rewrite_setToFlush();

	#} remove from this list
	global $zeroBSCRM_extensionsInstalledList;
	$ret = array(); foreach ($zeroBSCRM_extensionsInstalledList as $x) if ($x !== 'quotebuilder') $ret[] = $x;
	$zeroBSCRM_extensionsInstalledList = $ret;

	return true;

}

function zeroBSCRM_extension_install_invbuilder(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_invs',1);

	#} add to installed?
	global $zeroBSCRM_extensionsInstalledList;
	if (!is_array($zeroBSCRM_extensionsInstalledList)) $zeroBSCRM_extensionsInstalledList = array();
	$zeroBSCRM_extensionsInstalledList[] = 'invbuilder';

	return true;

}
function zeroBSCRM_extension_uninstall_invbuilder(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_invs',-1);

	#} remove from this list
	global $zeroBSCRM_extensionsInstalledList;
	$ret = array(); foreach ($zeroBSCRM_extensionsInstalledList as $x) if ($x !== 'invbuilder') $ret[] = $x;
	$zeroBSCRM_extensionsInstalledList = $ret;

	return true;

}

function zeroBSCRM_extension_install_csvimporterlite(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_csvimporterlite',1);

	#} add to installed?
	global $zeroBSCRM_extensionsInstalledList;
	if (!is_array($zeroBSCRM_extensionsInstalledList)) $zeroBSCRM_extensionsInstalledList = array();
	$zeroBSCRM_extensionsInstalledList[] = 'csvimporterlite';

	return true;

}
function zeroBSCRM_extension_uninstall_csvimporterlite(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_csvimporterlite',-1);

	#} remove from this list
	global $zeroBSCRM_extensionsInstalledList;
	$ret = array(); foreach ($zeroBSCRM_extensionsInstalledList as $x) if ($x !== 'csvimporterlite') $ret[] = $x;
	$zeroBSCRM_extensionsInstalledList = $ret;

	return true;

}

function zeroBSCRM_extension_install_portal(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_portal',1);

	// create the page if it's not there.
	zeroBSCRM_portal_checkCreatePage();

	#} add to installed?
	global $zeroBSCRM_extensionsInstalledList;
	if (!is_array($zeroBSCRM_extensionsInstalledList)) $zeroBSCRM_extensionsInstalledList = array();
	$zeroBSCRM_extensionsInstalledList[] = 'portal';

	return true;

}
function zeroBSCRM_extension_uninstall_portal(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_portal',-1);

	#} remove from this list
	global $zeroBSCRM_extensionsInstalledList;
	$ret = array(); foreach ($zeroBSCRM_extensionsInstalledList as $x) if ($x !== 'portal') $ret[] = $x;
	$zeroBSCRM_extensionsInstalledList = $ret;

	return true;

}

function zeroBSCRM_extension_install_api(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_api',1);

	#} add to installed?
	global $zeroBSCRM_extensionsInstalledList;
	if (!is_array($zeroBSCRM_extensionsInstalledList)) $zeroBSCRM_extensionsInstalledList = array();
	$zeroBSCRM_extensionsInstalledList[] = 'api';

	return true;

}
function zeroBSCRM_extension_uninstall_api(){

	#} Brutal really, just set the setting
	global $zbs;
	$zbs->settings->update('feat_api',-1);

	#} remove from this list
	global $zeroBSCRM_extensionsInstalledList;
	$ret = array(); foreach ($zeroBSCRM_extensionsInstalledList as $x) if ($x !== 'api') $ret[] = $x;
	$zeroBSCRM_extensionsInstalledList = $ret;

	return true;

}



#} Free extensions init
function zeroBSCRM_freeExtensionsInit(){

	#} free exts installed?
	global $zeroBSCRM_extensionsInstalledList;
	if (!is_array($zeroBSCRM_extensionsInstalledList)) $zeroBSCRM_extensionsInstalledList = array();
	$ZBSuseForms = zeroBSCRM_getSetting('feat_forms');
	if ($ZBSuseForms == 1) $zeroBSCRM_extensionsInstalledList[] = 'forms';
	$ZBSusePDFS = zeroBSCRM_getSetting('feat_pdfinv');
	if ($ZBSusePDFS == 1) $zeroBSCRM_extensionsInstalledList[] = 'pdfinv';
	$ZBSuse = zeroBSCRM_getSetting('feat_quotes');
	if ($ZBSuse == 1) $zeroBSCRM_extensionsInstalledList[] = 'quotebuilder';
	$ZBSuse = zeroBSCRM_getSetting('feat_invs');
	if ($ZBSuse == 1) $zeroBSCRM_extensionsInstalledList[] = 'invbuilder';
	$ZBSuse = zeroBSCRM_getSetting('feat_csvimporterlite');
	if ($ZBSuse == 1) $zeroBSCRM_extensionsInstalledList[] = 'csvimporterlite';
	$ZBSuse = zeroBSCRM_getSetting('feat_portal');
	if ($ZBSuse == 1) $zeroBSCRM_extensionsInstalledList[] = 'portal';
	$ZBSuse = zeroBSCRM_getSetting('feat_api');
	if ($ZBSuse == 1) $zeroBSCRM_extensionsInstalledList[] = 'api';
	$ZBSuse = zeroBSCRM_getSetting('feat_calendar');
	if ($ZBSuse == 1) $zeroBSCRM_extensionsInstalledList[] = 'cal';

	$ZBSuse = zeroBSCRM_getSetting('feat_transactions');
	if ($ZBSuse == 1) $zeroBSCRM_extensionsInstalledList[] = 'transactions';



}

/* ======================================================
	EXTENSION DETAILS / FREE/included EXTENSIONS
   ====================================================== */
