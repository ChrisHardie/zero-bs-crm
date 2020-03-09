<?php
/**
 * Your Details Page
 *
 * This displays the users details for editing
 *
 * @author 		ZeroBSCRM
 * @package 	Templates/Portal/Details
 * @see			https://zerobscrm.com/kb/
 * @version     3.0
 * 
 */



if ( ! defined( 'ABSPATH' ) ) exit; // Don't allow direct access

// zeroBS_portal_enqueue_stuff();

do_action( 'zbs_enqueue_scrips_and_styles' );

global $zbs, $wpdb, $zbsCustomerFields;


//handle the saving of the details..
if(array_key_exists('save', $_POST)){

    if($_POST['save'] == 1){


        $uid = get_current_user_id();
        $uinfo = get_userdata( $uid );
        $cID = zeroBS_getCustomerIDWithEmail($uinfo->user_email);


        // added !empty check - because if logged in as admin, saved deets, it made a new contact for them
        if((int)$_POST['customer_id'] == $cID && !empty($cID)){ 

                #} handle the password fields, if set.
                if(isset($_POST['password']) && !empty($_POST['password']) && isset($_POST['password2']) && !empty($_POST['password2']) ){

                    if($_POST['password'] != $_POST['password2']){
                        echo "<div class='zbs_alert danger'>" . __("Passwords do not match","zero-bs-crm") . "</div>";
                    } else {

                        // normal stuff + pw

                            // password
                            wp_set_password( sanitize_text_field($_POST['password']), $uid);
          
                            // update
                            zeroBSCRM_portal_UpdateDetailsFromPost($cID);

                            // log updated - longdesc here, later could be comparison changelog, e.g. 'contact changed number from x to y', for now left blank for speed of cp delivery
                            $longDesc = __('Includes password change','zero-bs-crm');
                            zeroBSCRM_portal_addClientUpdatedDetailsLog($cID,$longDesc);
                        
                            // msg                        
                            echo "<div class='zbs_alert'>" . __("Password and Details updated","zero-bs-crm") . "</div>";

        
          
                    }
                
                
                } else {

                    //just the normal stuff

                        // update
                        zeroBSCRM_portal_UpdateDetailsFromPost($cID);

                        // log updated - longdesc here, later could be comparison changelog, e.g. 'contact changed number from x to y', for now left blank for speed of cp delivery
                        $longDesc = '';
                        zeroBSCRM_portal_addClientUpdatedDetailsLog($cID,$longDesc);
                    
                        // msg
                        echo "<div class='zbs_alert'>" . __("Details Updated","zero-bs-crm") . "</div>";

                }

            }
        }
    }

    function zeroBSCRM_portal_UpdateDetailsFromPost($cID=-1){

        global $zbs;

        $contact = zeroBS_buildCustomerMeta($_POST);

        return $zbs->DAL->contacts->addUpdateContact(array(
            'id'    =>  $cID,
            'data'  => $contact,
            'do_not_update_blanks' => true
            ));
    }

    function zeroBSCRM_portal_addClientUpdatedDetailsLog($cID=-1,$longDesc=''){

        if ($cID > 0){

            $shortDesc = __('Contact changed some of their details via the Client Portal',"zero-bs-crm");

            #} Only raw checked... but proceed. (ADD or Update?) (if $zbsNoteIDtoUpdate = -1 it'll add, else it'll overwrite)
            $newOrUpdatedLogID = zeroBS_addUpdateLog($cID,-1,-1,array(
                'type' => 'Contact Changed Details via Portal',
                'shortdesc' => $shortDesc,
                'longdesc' => $longDesc,
            ),'zerobs_customer');

        }
    }



    $uid = get_current_user_id();
    $uinfo = get_userdata( $uid );
    $cID = zeroBS_getCustomerIDWithEmail($uinfo->user_email);

?>
<div id="zbs-main" class="zbs-site-main">
    <div class="zbs-client-portal-wrap main site-main zbs-post zbs-hentry">
    <?php

    // define
    $details_endpoint = 'details';

    //moved into func
    if(function_exists('zeroBSCRM_clientPortalgetEndpoint')){
        $detaiils_endpoint = zeroBSCRM_clientPortalgetEndpoint('details');
    }
    zeroBS_portalnav($details_endpoint);
    ?>

    <div class='zbs-portal-wrapper'>
    <?php 

    // if admin, explain
    if (current_user_can( 'admin_zerobs_manage_options' ) && empty($cID)){
        ?>
            <div class='alert alert-info' style="font-size: 0.8em;text-align: left;">
                You are viewing this details page in the Client Portal<br />Typically this will show a contacts details and allow them to be changed.<br />You can hide fields from this page in Settings -> Client Portal -> 'Fields to hide on Portal'<br />(this message is only shown to admins). 
                <?php ##WLREMOVE ?>
                <br />Learn more about the client portal <a style="color:orange;font-size:0.8em;" href="https://zerobscrm.com/kb/knowledge-base/how-does-the-client-portal-work/" target="_blank">here</a>
                <?php ##/WLREMOVE ?>
            </div><?php
    }

    // admin msg (upsell cpp) (checks perms itself, safe to run)
    // leave off here, enough with long msg above zeroBSCRM_portal_adminMsg();

    ?>
	<form action="#" name="zbs-update-deets" method="POST" style="padding-bottom:50px;" class="form-horizontal form-inline">



<?php

    $fields = $zbsCustomerFields;


    // Get field Hides...
    $fieldHideOverrides = $zbs->settings->get('fieldhides');
    // WH removed, not used $zbsShowID = $zbs->settings->get('showid');

    $zbsCustomer = zeroBS_getCustomerMeta($cID);

    $zbsOpenGroup = false;
    $showAddr = true; //setting..?

    // WH Forced these to not be output, they're dangerous. Probably more should be here, perhaps it should be an options page
    $neverShowToPortalUsers = array('status','email');
    $potentialNotToShow = $zbs->settings->get('portal_hidefields');
    if (isset($potentialNotToShow)){
        $potentialNotToShow = explode(',',$potentialNotToShow);
    }
    if (is_array($potentialNotToShow)) $neverShowToPortalUsers = $potentialNotToShow;


    ?>
    <input type="hidden" name="customer_id" id="customer_id" value="<?php echo $cID; ?>" />
    <?php

    #} Address settings
    $showAddresses = zeroBSCRM_getSetting('showaddress');
    $showSecondAddress = zeroBSCRM_getSetting('secondaddress');
    $showCountryFields = zeroBSCRM_getSetting('countries');

    #} This global holds "enabled/disabled" for specific fields... ignore unless you're WH or ask
    global $zbsFieldsEnabled; if ($showSecondAddress == "1") $zbsFieldsEnabled['secondaddress'] = true;


    $click2call = 0;
    $second_address_area = zeroBSCRM_getSetting('secondaddresslabel');


    // used for new fields 2.98.5 + 
    $postPrefix = 'zbsc_';
    

    foreach ($fields as $fieldK => $fieldV){

        // WH hard-not-showing some fields
        if (!in_array($fieldK, $neverShowToPortalUsers)){

            $showField = true;

            #} Check if not hard-hidden by opt override (on off for second address, mostly)
            if (isset($fieldV['opt']) && (!isset($zbsFieldsEnabled[$fieldV['opt']]) || !$zbsFieldsEnabled[$fieldV['opt']])) $showField = false;

            // or is hidden by checkbox? 
            if (isset($fieldHideOverrides['customer']) && is_array($fieldHideOverrides['customer'])){
                if (in_array($fieldK, $fieldHideOverrides['customer'])){
                    $showField = false;
                }
            }

            // ==================================================================================
            // Following grouping code needed moving out of ifShown loop:

                #} Whatever prev fiedl group was, if this is diff, close (post group)
                if (
                    $zbsOpenGroup &&
                        #} diff group
                        ( 
                            (isset($fieldV['area']) && $fieldV['area'] != $zbsFieldGroup) ||
                            #} No group
                                !isset($fieldV['area']) && $zbsFieldGroup != ''
                        )
                    ){

                        #} Special cases... gross
                        $zbsCloseTable = true; if ($zbsFieldGroup == 'Main Address') $zbsCloseTable = false;

                        #} Close it
                        echo '</table></div>';
                        if ($zbsCloseTable) echo '</td></tr>';

                }

                #} Any groupings?
                if (isset($fieldV['area'])){

                    #} First in a grouping? (assumes in sequential grouped order)
                    if ($zbsFieldGroup != $fieldV['area']){

                        #} set it
                        $zbsFieldGroup = $fieldV['area'];


                        $fieldGroupLabel = str_replace(' ','_',$zbsFieldGroup); $fieldGroupLabel = strtolower($fieldGroupLabel);
                    
                        if ($showSecondAddress != "1") {
                            $fieldGroupLabel .= "_100w";
                        }
                        if ($showAddresses == "0") {
                            $fieldGroupLabel .= " zbs-hide";
                        }


                        #} Special cases... gross
                        $zbsOpenTable = true; if ($zbsFieldGroup == 'Second Address') $zbsOpenTable = false;



                        #} Make class for hiding address (this form output is weird) <-- classic mike saying my code is weird when it works fully. Ask if you don't know!
                        $zbsLineClass = ''; $zbsGroupClass = '';

                        // if addresses turned off, hide the lot
                        if ($showAddresses != "1") {

                            // addresses turned off
                            $zbsLineClass = 'zbs-hide';
                            $zbsGroupClass = 'zbs-hide';

                        } else { 

                            // addresses turned on
                            if ($zbsFieldGroup == 'Second Address'){

                                // if we're in second address grouping:

                                    // if second address turned off
                                    if ($showSecondAddress != "1"){

                                        $zbsLineClass = 'zbs-hide';
                                        $zbsGroupClass = 'zbs-hide';

                                    }

                            }

                        }

                        // / address  modifiers



                        #} add group div + label
                        if ($zbsOpenTable) echo '<tr class="wh-large zbs-field-group-tr '.$zbsLineClass.'"><td colspan="2">';

                        
                        if($second_address_area != '' && $fieldV['area'] == 'Second Address'){
                            echo '<div class="zbs-field-group zbs-fieldgroup-'.$fieldGroupLabel.' '. $zbsGroupClass .'"><label class="zbs-field-group-label">'. $second_address_area .'</label>';
                        }else{
                            echo '<div class="zbs-field-group zbs-fieldgroup-'.$fieldGroupLabel.' '. $zbsGroupClass .'"><label class="zbs-field-group-label">'. __($fieldV['area'],"zero-bs-crm").'</label>';
                        }



                        echo '<table class="form-table wh-metatab wptbp" id="wptbpMetaBoxGroup-'.$fieldGroupLabel.'">';
                        
                        #} Set this (need to close)
                        $zbsOpenGroup = true;

                    }


                } else {

                    #} No groupings!
                    $zbsFieldGroup = '';

                }

            // / grouping
            // ==================================================================================
            


            #} If show...
            if ($showField) {     

                // This whole output is LEGACY
                // v3.0 + this is resolved in core via zeroBSCRM_html_editFields() and zeroBSCRM_html_editField()
                // ... in FormatHelpers. 
                // ... this could do with re-writing to match that.

                // get a value (this allows field-irrelevant global tweaks, like the addr catch below...)
                unset($value); // make sure unset from last, if at all
                if (isset($zbsCustomer[$fieldK])) $value = $zbsCustomer[$fieldK];

                    // contacts got stuck in limbo as we upgraded db in 2 phases. 
                    // following catches old str and modernises to v3.0
                    // make addresses their own objs 3.0+ and do away with this.
                    // ... hard typed to avoid custom field collisions, hacky at best.
                    switch ($fieldK){

                        case 'secaddr1':
                             if (isset($zbsCustomer['secaddr_addr1'])) $value = $zbsCustomer['secaddr_addr1'];
                             break;

                        case 'secaddr2':
                             if (isset($zbsCustomer['secaddr_addr2'])) $value = $zbsCustomer['secaddr_addr2'];
                             break;

                        case 'seccity':
                             if (isset($zbsCustomer['secaddr_city'])) $value = $zbsCustomer['secaddr_city'];
                             break;

                        case 'seccounty':
                             if (isset($zbsCustomer['secaddr_county'])) $value = $zbsCustomer['secaddr_county'];
                             break;

                        case 'seccountry':
                             if (isset($zbsCustomer['secaddr_country'])) $value = $zbsCustomer['secaddr_country'];
                             break;

                        case 'secpostcode':
                             if (isset($zbsCustomer['secaddr_postcode'])) $value = $zbsCustomer['secaddr_postcode'];
                             break;
                    }                     

                // echo '<tr><td colspan="2">'.$fieldK.' "'.$zbsCustomer[$fieldK].'" HERE ('.gettype($fieldV).'): <pre>'; print_r($fieldV); echo '</pre>!</td></tr>';
                // echo '<tr><td colspan="2">'.$fieldK.' "'.$value.'" HERE ('.$fieldV[0].')!</td></tr>';


                if (isset($fieldV[0])) switch ($fieldV[0]){

                    case 'text':

                    // added zbs-text-input class 5/1/18 - this allows "linkify" automatic linking
                    // ... via js
                        //  mike-label
                        ?><tr class="wh-large"><th><label class='label' for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                        <td><div class="zbs-text-input <?php echo $fieldK; ?>">
                            <input type="text" name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control widetext" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" value="<?php if (isset($value)) echo $value; ?>" autocomplete="zbscontact-<?php echo time(); ?>-<?php echo $fieldK; ?>" />
                        </div></td></tr><?php

                        break;

                    case 'price':

                        ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                        <td>
                            <?php echo zeroBSCRM_getCurrencyChr(); ?> <input style="width: 130px;display: inline-block;;" type="text" name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control  numbersOnly" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" value="<?php if (isset($value)) echo $value; ?>" autocomplete="zbscontact-<?php echo time(); ?>-<?php echo $fieldK; ?>" />
                        </td></tr><?php

                        break;


                    case 'date':

                        /* skipping DATE custom fields for v3.0, lets see if they're asked for...
                        ... if so, then rewrite this whole linkage (as above to match zeroBSCRM_html_editFields() style)
                        ... because 'date' here is a UTS, and we'll need date picker etc.

                        ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                        <td>
                            <input type="text" name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control zbs-date" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" value="<?php if (isset($value)) echo $value; ?>" autocomplete="zbscontact-<?php echo time(); ?>-<?php echo $fieldK; ?>" />
                        </td></tr><?php

                        */

                        break;

                    case 'select':

                        ?><tr class="wh-large"><th><label class='label' for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                        <td>
                            <select name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control zbs-watch-input" autocomplete="zbscontact-<?php echo time(); ?>-<?php echo $fieldK; ?>">
                                <?php
                                    // pre DAL 2 = $fieldV[3], DAL2 = $fieldV[2]
                                    $options = array(); 
                                    if (isset($fieldV[3])) {
                                        $options = $fieldV[3];
                                    } else {
                                        // DAL2 these don't seem to be auto-decompiled?
                                        // doing here for quick fix, maybe fix up the chain later.
                                        if (isset($fieldV[2])) $options = explode(',', $fieldV[2]);
                                    }

                                    if (isset($options) && count($options) > 0){

                                        //catcher
                                        echo '<option value="" disabled="disabled"';
                                        if (!isset($value) || (isset($value)) && empty($value)) echo ' selected="selected"';
                                        echo '>'.__('Select',"zero-bs-crm").'</option>';

                                        foreach ($options as $opt){

                                            echo '<option value="'.$opt.'"';
                                            if (isset($value) && strtolower($value) == strtolower($opt)) echo ' selected="selected"'; 
                                            // __ here so that things like country lists can be translated
                                            echo '>'.__($opt,"zero-bs-crm").'</option>';

                                        }

                                    } else echo '<option value="">'.__('No Options',"zero-bs-crm").'!</option>';

                                ?>
                            </select>
                            <input type="hidden" name="zbsc_<?php echo $fieldK; ?>_dirtyflag" id="zbsc_<?php echo $fieldK; ?>_dirtyflag" value="0" />
                        </td></tr><?php

                        break;

                    case 'tel':

                        ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm");?>:</label></th>
                        <td class="zbs-tel-wrap">
                            <input type="text" name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control zbs-tel" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" value="<?php if (isset($value)) echo $value; ?>" autocomplete="zbscontact-<?php echo time(); ?>-<?php echo $fieldK; ?>" />
                            <?php if ($click2call == "1" && isset($zbsCustomer[$fieldK]) && !empty($zbsCustomer[$fieldK])) echo '<a href="'.zeroBSCRM_clickToCallPrefix().$zbsCustomer[$fieldK].'" class="button"><i class="fa fa-phone"></i> '.$zbsCustomer[$fieldK].'</a>'; ?>
                            <?php 
                                if ($fieldK == 'mobtel'){

                                    $sms_class = 'send-sms-none';
                                    $sms_class = apply_filters('zbs_twilio_sms', $sms_class); 
                                    do_action('zbs_twilio_nonce');

                                    $customerMob = ''; if (is_array($zbsCustomer) && isset($zbsCustomer[$fieldK]) && isset($contact['id'])) $customerMob = zeroBS_customerMobile($contact['id'],$zbsCustomer);
                                    
                                    if (!empty($customerMob)) echo '<a class="' . $sms_class . ' button" data-smsnum="' . $customerMob .'"><i class="mobile alternate icon"></i> '.__('SMS','zero-bs-crm').': ' . $customerMob . '</a>';

                                }

                                ?>
                        </td></tr><?php

                        break;

                    case 'email':

                    // added zbs-text-input class 5/1/18 - this allows "linkify" automatic linking
                    // ... via js <div class="zbs-text-input">
                    // removed from email for now zbs-text-input

                        ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                        <td><div class="<?php echo $fieldK; ?>">
                            <input type="text" name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control zbs-email" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" value="<?php if (isset($value)) echo $value; ?>" autocomplete="off" />
                        </div></td></tr><?php


                        break;

                    case 'textarea':

                        ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                        <td>
                            <textarea name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" autocomplete="zbscontact-<?php echo time(); ?>-<?php echo $fieldK; ?>"><?php if (isset($value)) echo zeroBSCRM_textExpose($value); ?></textarea>
                        </td></tr><?php

                        break;

                    #} Added 1.1.19 
                    case 'selectcountry':

                        $countries = zeroBSCRM_loadCountryList();

                        if ($showCountryFields == "1"){

                        ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                        <td>
                            <select name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control" autocomplete="zbscontact-<?php echo time(); ?>-<?php echo $fieldK; ?>">
                                <?php

                                    #if (isset($fieldV[3]) && count($fieldV[3]) > 0){
                                    if (isset($countries) && count($countries) > 0){

                                        //catcher
                                        echo '<option value="" disabled="disabled"';
                                        if (!isset($value) || (isset($value)) && empty($value)) echo ' selected="selected"';
                                        echo '>'.__('Select',"zero-bs-crm").'</option>';

                                        foreach ($countries as $countryKey => $country){

                                            // temporary fix for people storing "United States" but also "US"
                                            // needs a migration to iso country code, for now, catch the latter (only 1 user via api)

                                            echo '<option value="'.$country.'"';
                                            if (isset($value) && (
                                                    strtolower($value) == strtolower($country)
                                                    ||
                                                    strtolower($value) == strtolower($countryKey)
                                                )) echo ' selected="selected"'; 
                                            echo '>'.$country.'</option>';

                                        }

                                    } else echo '<option value="">'.__('No Countries Loaded',"zero-bs-crm").'!</option>';

                                ?>
                            </select>
                        </td></tr><?php

                        }

                        break;


                    // 2.98.5 added autonumber, checkbox, radio
                    case 'autonumber':

                        // NOT SHOWN on portal :) 

                        break;

                    // radio
                    case 'radio':

                        ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                        <td>
                            <div class="zbs-field-radio-wrap">
                                <?php

                                    // DAL2 = $fieldV[2]
                                    $options = false; if (isset($fieldV[2])) $options = explode(',', $fieldV[2]);                                   

                                    //if (isset($fieldV[3]) && count($fieldV[3]) > 0){
                                    if (isset($options) && is_array($options) && count($options) > 0 && $options[0] != ''){

                                        $optIndex = 0;

                                        foreach ($options as $opt){

                                            echo '<div class="zbs-radio"><input type="radio" name="'.$postPrefix.$fieldK.'" id="'.$fieldK.'-'.$optIndex.'" value="'.$opt.'"';
                                            if (isset($value) && $value == $opt) echo ' checked="checked"'; 
                                            echo ' /> <label for="'.$fieldK.'-'.$optIndex.'">'.$opt.'</label></div>';

                                            $optIndex++;

                                        }

                                    } else echo '-'; //<input type="radio" name="'.$postPrefix.$fieldK.'" id="'.$fieldK.'-0" value="" checked="checked" /> 

                                ?>
                            </div>                            
                        </td></tr><?php

                        break;

                    // checkbox
                    case 'checkbox':

                        ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                        <td>
                            <div class="zbs-field-checkbox-wrap">
                                <?php
                                    
                                    // DAL2 = $fieldV[2]
                                    $options = false; if (isset($fieldV[2])) $options = explode(',', $fieldV[2]);                                  

                                    // split fields (multi select)
                                    $dataOpts = array();
                                    if (isset($value) && !empty($value)){
                                        $dataOpts = explode(',', $value);
                                    }

                                    //if (isset($fieldV[3]) && count($fieldV[3]) > 0){
                                    if (isset($options) && is_array($options) && count($options) > 0 && $options[0] != ''){

                                        $optIndex = 0;

                                        foreach ($options as $opt){

                                            echo '<div class="zbs-cf-checkbox"><input type="checkbox" name="'.$postPrefix.$fieldK.'-'.$optIndex.'" id="'.$fieldK.'-'.$optIndex.'" value="'.$opt.'"';
                                            if (in_array($opt, $dataOpts)) echo ' checked="checked"'; 
                                            echo ' /><label for="'.$fieldK.'-'.$optIndex.'">'.$opt.'</label></div>';

                                            $optIndex++;

                                        }

                                    } else echo '-';

                                ?>
                            </div>
                        </td></tr><?php

                        break;


                } #} / switch





            } #} / if show



            // ==================================================================================
            // Following grouping code needed moving out of ifShown loop:

                #} Closing field? (REGARDLESS of any hide!)
                if (
                    $zbsOpenGroup &&
                        #} diff group
                        ( 
                            (isset($fieldV['area']) && $fieldV['area'] != $zbsFieldGroup) ||
                            #} No group
                                !isset($fieldV['area']) && $zbsFieldGroup != ''
                        )
                    ){

                        #} Special cases... gross
                        $zbsCloseTable = true; if ($zbsFieldGroup == 'Main Address') $zbsCloseTable = false;

                        #} Close it
                        echo '</table></div>';
                        if ($zbsCloseTable) echo '</td></tr>';

                }


            // / grouping
            // ==================================================================================
            


            // new home for Company Add


        } // / not in 'hard do not show' list

    } // foreach field


    #} Extra for password reset.



?>
        <label style="margin-top:2em;"><?php _e("Change your password (or leave blank to keep the same)", "zero-bs-crm"); ?></label>
        <input class="form-control" type="password" id="password" name="password" value=""/>
        <label><?php _e("Re-enter password", "zero-bs-crm"); ?></label>
        <input class="form-control" type="password" id="password2" name="password2" value=""/>
        <input type="hidden" id="save" name="save" value="1"/>  
        <br/>
        <input type="submit" id="submit" value="<?php _e('Submit',"zero-bs-crm");?>"/>
        </form>

        <div style="clear:both"></div>
        <?php zeroBSCRM_portalFooter(); ?>
        </div>
    </div>
</div>