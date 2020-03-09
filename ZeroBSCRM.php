<?php
/*
Plugin Name: Zero BS CRM
Plugin URI: https://zerobscrm.com
Description: Zero BS CRM is the simplest CRM for WordPress. Self host your own Customer Relationship Manager using WP.
Version: 3.0.11
Author: Zero BS CRM

Author URI: https://zerobscrm.com
Text Domain: zero-bs-crm
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly. 
}

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ABSPATH' ) ) exit;

#} PHP Version check #WHFeedbackFix
if (version_compare(phpversion(), '5.6', '<')) {
   
   // php version isn't high enough
   echo '<div style="font-family: \'Open Sans\',sans-serif;">'.__('Zero BS CRM Requires PHP Version 5.6 or above, please ask web hosting provider to update your PHP! (You are on version ','zero-bs-crm').' '.PHP_VERSION.')</div>';
   exit();    

    #} See http://wordpress.stackexchange.com/questions/76007/best-way-to-abort-plugin-in-case-of-insufficient-php-version for alternative options :slightly_smiling_face:
    #} I like this simple way
}
/* ======================================================
  / Breaking Checks
   ====================================================== */


// ====================================================================
// ==================== General Perf Testing ==========================

// Enabling THIS will start LOGGING PERFORMANCE TESTS
// NOTE: This will do for EVERY page load, so just add temporarily else adds rolling DRAIN on sys
// define('ZBSPERFTEST',1);

// full perf test mode
if (defined('ZBSPERFTEST')) {

    // store a global arr for this "test"
    global $zbsPerfTest;
    $zbsPerfTest = array('init'=>time(),'get'=>$_GET,'results'=>array());

    // include if not already
    if (!function_exists('zeroBSCRM_performanceTest_finishTimer')) {
        include_once dirname(__FILE__) . '/includes/ZeroBSCRM.PerformanceTesting.php';
    }

    // start timer
    zeroBSCRM_performanceTest_startTimer('plugin-load');
}

// =================== / General Perf Testing =========================
// ====================================================================


// ====================================================================
// =========================== Definitions ============================

// Define WC_PLUGIN_FILE.
if ( ! defined( 'ZBS_ROOTFILE' ) ) {

	define( 'ZBS_ROOTFILE', __FILE__ );
	define( 'ZBS_ROOTDIR', basename(dirname(__FILE__)) ); // zero-bs-crm
	define(	'ZBS_ROOTPLUGIN',ZBS_ROOTDIR.'/'.basename(ZBS_ROOTFILE)); // zero-bs-crm/ZeroBSCRM.php
	define('ZBS_LANG_DIR',basename( dirname( __FILE__ ) ) . '/languages');

}

// ========================= / Definitions ============================
// ====================================================================
	

// ====================================================================
// =================  Legacy (pre v2.53) Support ======================

	// LEGACY SUPPORT - all ext settings 
	global $zbsLegacySupport; $zbsLegacySupport = array('extsettingspostinit' => array());

	// support for old - to be removed in time.
	global $zeroBSCRM_Settings;

	// this gets run post init :)
	function zeroBSCRM_legacySupport(){

		// map old global, for NOW... remove once all ext's removed
		// only needed in old wh.config.lib's, which we can replace now :)
		global $zeroBSCRM_Settings, $zbs, $zbsLegacySupport;
		$zeroBSCRM_Settings = $zbs->settings;

		if (count($zbsLegacySupport) > 0) foreach ($zbsLegacySupport as $key => $defaultConfig){

			// init with a throwaway var (just get)

			// this checks is the setting is accessible, and if not (fresh installs) then uses the caught defaultConfig from wh.config.lib legacy support
			$existingSettings = $zbs->settings->dmzGetConfig($key);
			#} Create if not existing
			if (!is_array($existingSettings)){

				#} init
				$zbs->settings->dmzUpdateConfig($key,$defaultConfig);

			}

		} // / foreach loaded with legacy support

	}

	// legacy support for removal of _we() - to be fixed in ext
	if (!function_exists('_we')){
		function _we($str,$domain="zero-bs-crm"){
			_e($str,$domain);
		}
		function __w($str,$domain="zero-bs-crm"){
			return __($str,$domain);
		}
	}

// ================ / Legacy (pre v2.53) Support ======================
// ====================================================================



// ====================================================================
// =================  Main Include ====================================

// Include the main ZeroBS CRM class.
if (! class_exists('ZeroBSCRM')) {
    include_once dirname(__FILE__) . '/includes/ZeroBSCRM.Core.php';
}

#} Initiate ZBS Main Core
global $zbs; $zbs = ZeroBSCRM::instance();

// ================ / Main Include ====================================
// ====================================================================


// ====================================================================
// ==================== General Perf Testing ==========================

// close timer (at this point we'll have perf library)
if (defined('ZBSPERFTEST')) {

    // retrieve our global (may have had any number of test res added)
    global $zbsPerfTest;

    // close it
    zeroBSCRM_performanceTest_finishTimer('plugin-load');

    // store in perf-reports
    $zbsPerfTest['results']['plugin-load'] = zeroBSCRM_performanceTest_results('plugin-load');

    // here we basically wait for init so we can check user is wp admin
    // ... only saving perf logs if defined + wp admin
    add_action('shutdown', 'zeroBSCRM_init_perfTest');
}

function zeroBSCRM_init_perfTest()
{
    if (defined('ZBSPERFTEST') && zeroBSCRM_isWPAdmin()) {

        // retrieve our global (may have had any number of test res added)
        global $zbsPerfTest;

        // If admin, clear any prev perf test ifset
        if (isset($_GET['delperftest'])) {
            delete_option('zbs-global-perf-test');
        }
        
        // retrieve opt + add to it (up to 50 tests)
        $zbsPerfTestOpt = get_option('zbs-global-perf-test', array());
        if (is_array($zbsPerfTestOpt) && count($zbsPerfTestOpt) < 50) {

            // add
            $zbsPerfTestOpt[] = $zbsPerfTest;

            // save
            update_option('zbs-global-perf-test', $zbsPerfTestOpt);
        }
    }
}

// =================== / General Perf Testing =========================
// ====================================================================
