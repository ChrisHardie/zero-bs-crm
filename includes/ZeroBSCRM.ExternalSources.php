<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.97.4+
 *
 * Copyright 2019, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 11/01/2019
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

   /* 
        
        External sources got a bit muddy in DAL1, This file is designed to centralise + simplify :)

   */


    // Init set of external sources
    /* 
        // Proper way to add to these is (in extension plugin class):
        // key is that filter has priority below 99
        // .. can then be used anywhere POST init:99

        public function register_external_source($external_sources = array()){
            $external_sources['str'] = array('Stripe', 'ico' => 'fa-stripe');
            return $external_sources;
        }

        #} adds this as an external source
        add_filter('zbs_approved_sources' , array($this, 'register_external_source'), 10);

    */
    global  $zbscrmApprovedExternalSources;
            $zbscrmApprovedExternalSources = zeroBS_baseExternalSources();

// 2.97.7 wrapped these in a func so they can be less affected than a global ? :/
// this is called directly now in core to load them, rather than using the global $zbscrmApprovedExternalSources;
// ... global $zbscrmApprovedExternalSources is still set though, for backward compat, (not sure if some older ext using?) 
function zeroBS_baseExternalSources(){

            return array(
                #} For now these can just be 'key' => array('Name (for exposing') - is precursor for when we do more complex
                #} 26/05 added associated font awesome class for when exposing:
                'woo'       =>    array('WooCommerce','ico'=>'fa-shopping-cart'), #fa-shopping-cart is default :) no woo yet
                'pay'       =>    array('PayPal','ico'=>'fa-paypal'),
                'env'       =>    array('Envato','ico'=>'fa-envira'), #fa-envira is a look-alike http://fontawesome.io/icon/envira/
                'csv'       =>    array('CSV Import','ico'=>'fa-file-text'),
                'form'      =>   array('Form Capture', 'ico' => 'fa-wpforms'),
                'jvz'       =>    array('JV Zoo','ico'=>'fa-paypal'),
                'gra'       =>    array('Gravity Forms', 'ico' => 'fa-wpforms'),
                'api'       =>    array('API', 'ico' => 'fa-random'),
                'wpa'       =>    array('WorldPay', 'ico' => 'fa-credit-card'),
                'str'       =>    array('Stripe', 'ico' => 'fa-credit-card'),
                'wordpress' =>    array('WordPress', 'ico' => 'fa-wpforms'),
                'cf7'       =>    array('Contact Form 7', 'ico' => 'fa-wpforms'),

            );
}


// adapted from MetaBoxes.ExternalSources.php
function zeroBS_getExternalSourceTitle($srcKey='',$srcUID=''){

    // some old hard typed:

        switch ($srcKey){

            case 'pay': #} paypal

                return '<i class="fa fa-paypal"></i> PayPal:<br /><span>'.$srcUID.'</span>';

                break;

            #case 'woo': #} Woo
            case 'env':

                return '<i class="fa fa-envira"></i> Envato:<br /><span>'.$srcUID.'</span>';

                break;

            case 'form':

                return '<i class="fa fa-wpforms"></i> Form Capture:<br /><span>'.$srcUID.'</span>';

                break;

            case 'csv':

                return '<i class="fa fa-file-text"></i> CSV Import:<br /><span>'.$srcUID.'</span>';

                break;

            case 'gra':

                return '<i class="fa fa-wpforms"></i> Gravity Forms:<br /><span>'.$srcUID.'</span>';

                break;
                
            case 'api':

                return '<i class="fa fa-random"></i> API:<br /><span>'.$srcUID.'</span>';

                break;

            default:

                // see if in $zbs->external_sources
                global $zbs;

                if (isset($zbs->external_sources[$srcKey])){

                    $ico = 'fa-users'; if (is_array($zbs->external_sources[$srcKey]) && isset($zbs->external_sources[$srcKey]['ico'])) $ico = $zbs->external_sources[$srcKey]['ico'];
                    $name = ucwords(str_replace('_',' ',$srcKey)); if (is_array($zbs->external_sources[$srcKey]) && isset($zbs->external_sources[$srcKey][0])) $name = $zbs->external_sources[$srcKey][0];

                    return '<i class="fa '.$ico.'"></i> '.$name.':<br /><span>'.$srcUID.'</span>';

                } else {

                    #} Generic for now
                    return '<i class="fa fa-users"></i> '.ucwords(str_replace('_',' ',$srcKey)).':<br /><span>'.$srcUID.'</span>';

                }

                break;



        }
}

