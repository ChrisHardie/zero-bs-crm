<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.1.10
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 19/07/16
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */




/* ======================================================
   Init Func
   ====================================================== */

   function zeroBSCRM_CompaniesMetaboxSetup(){

        $zeroBS__Metabox_Companies = new zeroBS__Metabox_Companies( __FILE__ );
        $zeroBS__MetaboxCompanyAssociated = new zeroBS__MetaboxCompanyAssociated( __FILE__ );
        $zeroBS__MetaboxCompanyAttachments = new zeroBS__MetaboxCompanyAttachments( __FILE__ );
        

        #} Activity box on view page
        if(zeroBSCRM_is_company_view_page()){
            $zeroBS__Metabox_Company_Activity = new zeroBS__Metabox_Company_Activity( __FILE__ );
        }
   }

   add_action( 'admin_init','zeroBSCRM_CompaniesMetaboxSetup');

/* ======================================================
   / Init Func
   ====================================================== */



/* ======================================================
  Declare Globals
   ====================================================== */

    #} Used throughout
    // Don't know who added this, but GLOBALS are out of scope here
    // .. won't pass into the class? global $zbsCompanyFields,$zbsCompanyQuoteFields,$zbsCompanyInvoiceFields;

/* ======================================================
  / Declare Globals
   ====================================================== */




/* ======================================================
  Company Metabox
   ====================================================== */
    class zeroBS__Metabox_Companies {

        static $instance;
        #private $packPerm;
        #private $pack;
        private $postType;
        private $coOrgLabel;

        public function __construct( $plugin_file ) {
           # if ( $this->instance instanceof wProject_Metabox ) {
            #    wp_die( sprintf( __( 'Cannot instantiate singleton class: %1$s. Use %1$s::$instance instead.', 'plugin-namespace' ), __CLASS__ ) );
            #} else {
                self::$instance = $this;
            #}


            # (language switch)
            $companyOrOrg = zeroBSCRM_getSetting('coororg');
            $this->coOrgLabel = __(zeroBSCRM_getCompanyOrOrg(),"zero-bs-crm");

            $this->postType = 'zerobs_company';
            #if (???) wp_die( sprintf( __( 'Cannot instantiate class: %1$s without pack', 'wptbp' ), __CLASS__ ) );

            add_action( 'add_meta_boxes', array( $this, 'create_meta_box' ) );
            add_filter( 'save_post', array( $this, 'save_meta_box' ), 10, 2 );
        }

        public function create_meta_box() {

            #'wptbp'.$this->postType

            add_meta_box(
                'wpzbsc_companydetails',
                __(zeroBSCRM_getCompanyOrOrg().' Details',"zero-bs-crm"),
                array( $this, 'print_meta_box' ),
                $this->postType,
                'normal',
                'high'
            );
        }

        public function print_meta_box( $post, $metabox ) {


                global $zbs;

                #} retrieve
                $zbsCompany = get_post_meta($post->ID, 'zbs_company_meta', true);


                // Get field Hides...
                $fieldHideOverrides = $zbs->settings->get('fieldhides');
                $zbsShowID = $zbs->settings->get('showid');


                global $zbsCompanyName; $zbsCompanyName = ''; if (isset($zbsCompany['name'])) $zbsCompanyName = $zbsCompany['name'];

                global $zbsCompanyFields;
                $fields = $zbsCompanyFields;

                #} Address settings
                $showAddresses = zeroBSCRM_getSetting('showaddress');
                $showSecondAddress = zeroBSCRM_getSetting('secondaddress');
                $showCountryFields = zeroBSCRM_getSetting('countries');

            
            ?>
                <style>
                #post-body-content{display:none;}
                </style>
                <input type="hidden" name="meta_box_ids[]" value="<?php echo $metabox['id']; ?>" />
                <?php wp_nonce_field( 'save_' . $metabox['id'], $metabox['id'] . '_nonce' ); ?>
                <?php wp_nonce_field( 'save_zbscompany', 'save_zbscompany_nce' ); #thisone is for the custom save_post func in main file ?>
                
                <?php #} Pass this if it's a new customer (for internal automator) 

                    if (gettype($zbsCompany) != "array") echo '<input type="hidden" name="zbscrm_newcompany" value="1" />';

                ?>

                <table class="form-table wh-metatab wptbp" id="wptbpMetaBoxMainItem">

                    <?php #} WH Hacky quick addition for MVP 
                    # ... further hacked

                    if ($zbsShowID == "1") { ?>
                    <tr class="wh-large"><th><label><?php _e(zeroBSCRM_getCompanyOrOrg(),"zero-bs-crm").' '; _e("ID","zero-bs-crm");?>:</label></th>
                    <td style="font-size: 20px;color: green;vertical-align: top;">
                        #<?php echo $post->ID; ?>
                    </td></tr>
                    <?php } ?>

    <?php if (has_post_thumbnail( $post->ID ) ): ?>
      <?php $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' ); ?>
      <tr class="wh-large"><th><label><?php echo $this->coOrgLabel; ?> Image:</label></th>
                    <td>
                        <a href="<?php echo $image[0]; ?>" target="_blank"><img src="<?php echo $image[0]; ?>" alt="<?php echo $this->coOrgLabel; ?> Image" style="max-width:300px;border:0" /></a>
                    </td></tr>
    <?php endif; ?>
                    

                    <?php 

            

                    #} This global holds "enabled/disabled" for specific fields... ignore unless you're WH or ask
                    global $zbsFieldsEnabled; if ($showSecondAddress == '1') $zbsFieldsEnabled['secondaddress'] = true;
                    
                    #} This is the grouping :)
                    $zbsFieldGroup = ''; $zbsOpenGroup = false;

                    foreach ($fields as $fieldK => $fieldV){

                        $showField = true;

                        #} Check if not hard-hidden by opt override (on off for second address, mostly)
                        if (isset($fieldV['opt']) && (!isset($zbsFieldsEnabled[$fieldV['opt']]) || !$zbsFieldsEnabled[$fieldV['opt']])) $showField = false;


                        // or is hidden by checkbox? 
                        if (isset($fieldHideOverrides['company']) && is_array($fieldHideOverrides['company'])){
                            if (in_array($fieldK, $fieldHideOverrides['company'])){
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
                                    echo '<div class="zbs-field-group zbs-fieldgroup-'.$fieldGroupLabel.' '. $zbsGroupClass .'"><label class="zbs-field-group-label">'.__($fieldV['area'],"zero-bs-crm").'</label>';
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


                            switch ($fieldV[0]){

                                case 'text':

                                // added zbs-text-input class 5/1/18 - this allows "linkify" automatic linking
                                // ... via js <div class="zbs-text-input">

                                    ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                                    <td><div class="zbs-text-input <?php echo $fieldK; ?>">
                                        <input type="text" name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control widetext" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" value="<?php if (isset($zbsCompany[$fieldK])) echo $zbsCompany[$fieldK]; ?>" autocomplete="zbsco-<?php echo time(); ?>-<?php echo $fieldK; ?>" />
                                    </div></td></tr><?php

                                    break;

                                case 'price':

                                    ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                                    <td>
                                        <?php echo zeroBSCRM_getCurrencyChr(); ?> <input style="width: 130px;display: inline-block;;" type="text" name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control  numbersOnly" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" value="<?php if (isset($zbsCompany[$fieldK])) echo $zbsCompany[$fieldK]; ?>" autocomplete="zbsco-<?php echo time(); ?>-<?php echo $fieldK; ?>" />
                                    </td></tr><?php

                                    break;
                                case 'numberfloat':

                                    ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                                    <td>
                                        <input style="width: 130px;display: inline-block;;" type="text" name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control  numbersOnly" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" value="<?php if (isset($zbsCompany[$fieldK])) echo $zbsCompany[$fieldK]; ?>" autocomplete="zbsco-<?php echo time(); ?>-<?php echo $fieldK; ?>" />
                                    </td></tr><?php

                                    break;

                                case 'numberint':

                                    ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                                    <td>
                                        <input style="width: 130px;display: inline-block;;" type="text" name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control  intOnly" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" value="<?php if (isset($zbsCompany[$fieldK])) echo $zbsCompany[$fieldK]; ?>" autocomplete="zbsco-<?php echo time(); ?>-<?php echo $fieldK; ?>" />
                                    </td></tr><?php

                                    break;


                                case 'date':

                                    ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                                    <td>
                                        <input type="text" name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control zbs-date" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" value="<?php if (isset($zbsCompany[$fieldK])) echo $zbsCompany[$fieldK]; ?>" autocomplete="zbsco-<?php echo time(); ?>-<?php echo $fieldK; ?>" />
                                    </td></tr><?php

                                    break;

                                case 'select':

                                    ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                                    <td>
                                        <select name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control" autocomplete="zbsco-<?php echo time(); ?>-<?php echo $fieldK; ?>">
                                            <?php

                                                if (isset($fieldV[3]) && count($fieldV[3]) > 0){

                                                    //catcher
                                                    echo '<option value="" disabled="disabled"';
                                                    if (!isset($zbsCompany[$fieldK]) || (isset($zbsCompany[$fieldK])) && empty($zbsCompany[$fieldK])) echo ' selected="selected"';
                                                    echo '>'.__('Select',"zero-bs-crm").'</option>';

                                                    foreach ($fieldV[3] as $opt){

                                                        echo '<option value="'.$opt.'"';
                                                        if (isset($zbsCompany[$fieldK]) && strtolower($zbsCompany[$fieldK]) == strtolower($opt)) echo ' selected="selected"'; 
                                                        echo '>'.$opt.'</option>';

                                                    }

                                                } else echo '<option value="">'.__('No Options',"zero-bs-crm").'!</option>';

                                            ?>
                                        </select>
                                    </td></tr><?php

                                    break;

                                case 'tel':

                                    ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                                    <td>
                                        <input type="text" name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control zbs-tel" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" value="<?php if (isset($zbsCompany[$fieldK])) echo $zbsCompany[$fieldK]; ?>" autocomplete="zbsco-<?php echo time(); ?>-<?php echo $fieldK; ?>" />
                                    </td></tr><?php

                                    break;

                                case 'email':

                                // added zbs-text-input class 5/1/18 - this allows "linkify" automatic linking
                                // ... via js <div class="zbs-text-input">
                                // removed from email for now zbs-text-input

                                    ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                                    <td><div class=" <?php echo $fieldK; ?>">
                                        <input type="text" name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control zbs-email" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" value="<?php if (isset($zbsCompany[$fieldK])) echo $zbsCompany[$fieldK]; ?>" autocomplete="off" />
                                    </div></td></tr><?php

                                    break;

                                case 'textarea':

                                    ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                                    <td>
                                        <textarea name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control" placeholder="<?php if (isset($fieldV[2])) echo $fieldV[2]; ?>" autocomplete="zbsco-<?php echo time(); ?>-<?php echo $fieldK; ?>"><?php if (isset($zbsCompany[$fieldK])) echo zeroBSCRM_textExpose($zbsCompany[$fieldK]); ?></textarea>
                                    </td></tr><?php

                                    break;

                                #} Added 1.1.19 
                                case 'selectcountry':

                                    $countries = zeroBSCRM_loadCountryList();

                                    if ($showCountryFields == "1"){

                                        ?><tr class="wh-large"><th><label for="<?php echo $fieldK; ?>"><?php _e($fieldV[1],"zero-bs-crm"); ?>:</label></th>
                                        <td>
                                            <select name="zbsc_<?php echo $fieldK; ?>" id="<?php echo $fieldK; ?>" class="form-control" autocomplete="zbsco-<?php echo time(); ?>-<?php echo $fieldK; ?>">
                                                <?php

                                                    #if (isset($fieldV[3]) && count($fieldV[3]) > 0){
                                                    if (isset($countries) && count($countries) > 0){

                                                        //catcher
                                                        echo '<option value="" disabled="disabled"';
                                                        if (!isset($zbsCompany[$fieldK]) || (isset($zbsCompany[$fieldK])) && empty($zbsCompany[$fieldK])) echo ' selected="selected"';
                                                        echo '>'.__('Select',"zero-bs-crm").'</option>';

                                                        foreach ($countries as $countryKey => $country){

                                                            // temporary fix for people storing "United States" but also "US"
                                                            // needs a migration to iso country code, for now, catch the latter (only 1 user via api)

                                                            echo '<option value="'.$country.'"';
                                                            if (isset($zbsCompany[$fieldK]) && (
                                                                    strtolower($zbsCompany[$fieldK]) == strtolower($country)
                                                                    ||
                                                                    strtolower($zbsCompany[$fieldK]) == strtolower($countryKey)
                                                                )) echo ' selected="selected"'; 
                                                            echo '>'.$country.'</option>';

                                                        }

                                                    } else echo '<option value="">No Countries Loaded!</option>';

                                                ?>
                                            </select>
                                        </td></tr><?php

                                    }

                                    break;


                            } #} / switch



                        } #} / if show


                        // ==================================================================================
                        // Following grouping code needed moving out of ifShown loop:

                            #} Closing field?
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

                    }


                    /* Debug <tr><td colspan="2"><pre><?php print_r($zbsCompany) ?></pre></td></tr> */

                    ?>
                    
            </table>


            <style type="text/css">
                #submitdiv {
                    display:none;
                }
            </style>
            <script type="text/javascript">

                jQuery(document).ready(function(){

                    // bastard override of wp terminology:
                    jQuery('#submitdiv h2 span').html('Save');
                    if (jQuery('#submitdiv #publish').val() == 'Publish')
                        jQuery('#submitdiv #publish').val('Save');
                    jQuery('#submitdiv').show();                    

                    // turn off auto-complete on records via form attr... should be global for all ZBS record pages
                    jQuery('#post').attr('autocomplete','off');

                    /*// All datepickers
                    jQuery('.zbs-date').datetimepicker({
                      timepicker:false,
                      format:'d.m.Y'
                    });

                     Moved to daterangepicker 03/06/16 */
                    jQuery('.zbs-date').daterangepicker({
                        singleDatePicker: true,
                        showDropdowns: true,
                        locale: {
                            format: "DD.MM.YYYY"
                        }
                    }, 
                    function(start, end, label) {
                        //var years = moment().diff(start, 'years');
                        //alert("You are " + years + " years old.");
                    });


                    zbscrm_JS_bindFieldValidators();
                    /* this should be done in global?!? ^^

                    jQuery('.numbersOnly').keyup(function () {
                        var rep = this.value.replace(/[^0-9\.]/g, '');
                        if (this.value != rep) {
                           this.value = rep;
                        }
                    }); */



                });


            </script>
             
            <input type="hidden" name="<?php echo $metabox['id']; ?>_fields[]" value="subtitle_text" />


            <?php
        }

        public function save_meta_box( $post_id, $post ) {
            if( empty( $_POST['meta_box_ids'] ) ){ return; }
            foreach( $_POST['meta_box_ids'] as $metabox_id ){
                if(!isset($_POST[ $metabox_id . '_nonce' ]) ||  ! wp_verify_nonce( $_POST[ $metabox_id . '_nonce' ], 'save_' . $metabox_id ) ){ continue; }
                #if( count( $_POST[ $metabox_id . '_fields' ] ) == 0 ){ continue; }
                if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){ continue; }

                if( $metabox_id == 'wpzbsc_companydetails'  && $post->post_type == $this->postType){


                    /* This was moved to centralised func 23/01/2015 v1.1

                    $zbsCompanyMeta = array();


                    global $zbsCompanyFields;

                    foreach ($zbsCompanyFields as $fK => $fV){

                        $zbsCompanyMeta[$fK] = '';

                        if (isset($_POST['zbsc_'.$fK])) {

                            switch ($fV[0]){

                                case 'tel':

                                    // validate tel?
                                    $zbsCompanyMeta[$fK] = sanitize_text_field($_POST['zbsc_'.$fK]);
                                    preg_replace("/[^0-9 ]/", '', $zbsCompanyMeta[$fK]);
                                    break;

                                case 'price':

                                    // validate tel?
                                    $zbsCompanyMeta[$fK] = sanitize_text_field($_POST['zbsc_'.$fK]);
                                    $zbsCompanyMeta[$fK] = preg_replace('@[^0-9\.]+@i', '-', $zbsCompanyMeta[$fK]);
                                    $zbsCompanyMeta[$fK] = floatval($zbsCompanyMeta[$fK]);
                                    break;


                                case 'textarea':

                                    $zbsCompanyMeta[$fK] = zeroBSCRM_textProcess($_POST['zbsc_'.$fK]);

                                    break;


                                default:

                                    $zbsCompanyMeta[$fK] = sanitize_text_field($_POST['zbsc_'.$fK]);

                                    break;


                            }


                        }


                    } */

                    $zbsCompanyMeta = zeroBS_buildCompanyMeta($_POST);

                    #} UPDATE!
                    update_post_meta($post_id, 'zbs_company_meta', $zbsCompanyMeta);

                    #} Also store in a global for zbs main save_post to catch (saves db trips)
                    global $zbsCurrentCompanyPostMeta; $zbsCurrentCompanyPostMeta = $zbsCompanyMeta;

                }
            }

            return $post;
        }
    }

    // Moved into zeroBSCRM_CompaniesMetaboxSetup at top
    //$zeroBS__Metabox_Companies = new zeroBS__Metabox_Companies( __FILE__ );

/* ======================================================
  / Company Metabox
   ====================================================== */




/* ======================================================
  "Contacts at Company" Metabox
   ====================================================== */
    class zeroBS__MetaboxCompanyAssociated {

        static $instance;
        #private $packPerm;
        #private $pack;
        private $postType;
        private $coname;

        public function __construct( $plugin_file ) {
           # if ( $this->instance instanceof wProject_Metabox ) {
            #    wp_die( sprintf( __( 'Cannot instantiate singleton class: %1$s. Use %1$s::$instance instead.', 'plugin-namespace' ), __CLASS__ ) );
            #} else {
                self::$instance = $this;
            #}

            $this->postType = 'zerobs_company';
            #if (???) wp_die( sprintf( __( 'Cannot instantiate class: %1$s without pack', 'wptbp' ), __CLASS__ ) );

            #} Hacky/lazy
            global $zbsCompanyName; 
            $this->coname = zeroBSCRM_getCompanyOrOrg(); if (isset($zbsCompanyName)) $this->coname = $zbsCompanyName;

            add_action( 'add_meta_boxes', array( $this, 'create_meta_box' ) );
            add_filter( 'save_post', array( $this, 'save_meta_box' ), 10, 2 );
        }

        public function create_meta_box() {

            #'wptbp'.$this->postType

            add_meta_box(
                'wpzbsc_itemdetails_attachment',
                __('Contacts at',"zero-bs-crm").' '.__(zeroBSCRM_getCompanyOrOrg(),"zero-bs-crm"),
                array( $this, 'print_meta_box' ),
                $this->postType,
                'normal',
                'high'
            );
        }

        public function print_meta_box( $post, $metabox ) {

                $contacts = zeroBS_getCustomers(true,1000,0,false,false,'',false,false,$post->ID);        

                #} JUST OUTPUT

            ?>

                <table class="form-table wh-metatab wptbp" id="wptbpMetaBoxMainItemContacts">

                    <tr class="wh-large"><th>
                        <?php

                            if (count($contacts) > 0){

                                echo '<div id="zbs-co-contacts">';

                                foreach ($contacts as $contact){

                                    echo '<div class="zbs-co-contact">';

                                    #} Img or ico 
                                    echo zeroBS_getCustomerIcoHTML($contact['id']);

                                    #} new view link
                                    $url = zbsLink('view',$contact['id'],'zerobs_customer');

                                    echo '<strong><a href="'.$url.'">'.zeroBS_customerName($contact['id'],$contact,false,false).'</a></strong><br />';

                                    echo '</div>';

                                }


                                echo '</div>';

                            } else {

                                echo '<div style="margin-left:auto;margin-right:auto;display:inline-block">';
                                _e('No contacts found at',"zero-bs-crm"); echo ' '; _e(zeroBSCRM_getCompanyOrOrg(),"zero-bs-crm"); 
                                echo '</div>';

                            }

                        ?>
                    </th></tr>
                    
                </table>

            <style type="text/css">
                #submitdiv {
                    display:none;
                }
            </style>
            <script type="text/javascript">

                jQuery(document).ready(function(){

                    // bastard override of wp terminology:
                    jQuery('#submitdiv h2 span').html('Save');
                    if (jQuery('#submitdiv #publish').val() == 'Publish')
                        jQuery('#submitdiv #publish').val('Save');
                    jQuery('#submitdiv').show();

                    
                    /*// All datepickers
                    jQuery('.zbs-date').datetimepicker({
                      timepicker:false,
                      format:'d.m.Y'
                    });

                     Moved to daterangepicker 03/06/16 */
                    jQuery('.zbs-date').daterangepicker({
                        singleDatePicker: true,
                        showDropdowns: true,
                        locale: {
                            format: "DD.MM.YYYY"
                        }
                    }, 
                    function(start, end, label) {
                        //var years = moment().diff(start, 'years');
                        //alert("You are " + years + " years old.");
                    });


                    jQuery('.numbersOnly').keyup(function () {
                        var rep = this.value.replace(/[^0-9\.]/g, '');
                        if (this.value != rep) {
                           this.value = rep;
                        }
                    });


                });


            </script>
             


            <?php
        }

        public function save_meta_box( $post_id, $post ) {
            if( empty( $_POST['meta_box_ids'] ) ){ return; }
            foreach( $_POST['meta_box_ids'] as $metabox_id ){
                if( !isset($_POST[ $metabox_id . '_nonce' ]) || ! wp_verify_nonce( $_POST[ $metabox_id . '_nonce' ], 'save_' . $metabox_id ) ){ continue; }
                #if( count( $_POST[ $metabox_id . '_fields' ] ) == 0 ){ continue; }
                if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){ continue; }

                if( $metabox_id == 'wpzbsc_itemdetails_attachment'  && $post->post_type == $this->postType){

                    $zbsCustomerMeta = array();


                    global $zbsCustomerFields;

                    foreach ($zbsCustomerFields as $fK => $fV){

                        $zbsCustomerMeta[$fK] = '';

                        if (isset($_POST['zbsc_'.$fK])) {

                            switch ($fV[0]){

                                case 'tel':

                                    // validate tel?
                                    $zbsCustomerMeta[$fK] = sanitize_text_field($_POST['zbsc_'.$fK]);
                                    preg_replace("/[^0-9 ]/", '', $zbsCustomerMeta[$fK]);
                                    break;

                                case 'price':
                                case 'numberfloat':

                                    // validate foat
                                    $zbsCustomerMeta[$fK] = sanitize_text_field($_POST['zbsc_'.$fK]);
                                    $zbsCustomerMeta[$fK] = preg_replace('@[^0-9\.]+@i', '-', $zbsCustomerMeta[$fK]);
                                    $zbsCustomerMeta[$fK] = floatval($zbsCustomerMeta[$fK]);
                                    break;
                                    
                                case 'numberint':

                                    // validate int
                                    $zbsCustomerMeta[$fK] = sanitize_text_field($_POST['zbsc_'.$fK]);
                                    $zbsCustomerMeta[$fK] = preg_replace('@[^0-9]+@i', '-', $zbsCustomerMeta[$fK]);
                                    $zbsCustomerMeta[$fK] = floatval($zbsCustomerMeta[$fK]);
                                    break;


                                case 'textarea':

                                    $zbsCustomerMeta[$fK] = zeroBSCRM_textProcess($_POST['zbsc_'.$fK]);

                                    break;


                                default:

                                    $zbsCustomerMeta[$fK] = sanitize_text_field($_POST['zbsc_'.$fK]);

                                    break;


                            }

                        }


                    }

                    #} UPDATE!
                    update_post_meta($post_id, 'zbs_customer_meta', $zbsCustomerMeta);

                }
            }

            return $post;
        }
    }

    // Moved into zeroBSCRM_CompaniesMetaboxSetup at top
    //$zeroBS__MetaboxCompanyAssociated = new zeroBS__MetaboxCompanyAssociated( __FILE__ );

/* ======================================================
  / "Contacts at Company" Metabox
   ====================================================== */



/* ======================================================
  Attach files to customer metabox
   ====================================================== */


/* ======================================================
  company Files Metabox
   ====================================================== */

    class zeroBS__MetaboxCompanyAttachments {

        static $instance;
        #private $packPerm;
        #private $pack;
        private $postType;
        private $coname;

        public function __construct( $plugin_file ) {
           # if ( $this->instance instanceof wProject_Metabox ) {
            #    wp_die( sprintf( __( 'Cannot instantiate singleton class: %1$s. Use %1$s::$instance instead.', 'plugin-namespace' ), __CLASS__ ) );
            #} else {
                self::$instance = $this;
            #}

            $this->postType = 'zerobs_company';

            add_action( 'add_meta_boxes', array( $this, 'create_meta_box' ) );
            add_filter( 'save_post', array( $this, 'save_meta_box' ), 10, 2 );
        }

        public function create_meta_box() {

            #'wptbp'.$this->postType

            add_meta_box(
                'wpzbsc_itemdetails_attachment',
                __(zeroBSCRM_getCompanyOrOrg(),"zero-bs-crm").' '.__('Files',"zero-bs-crm"),
                array( $this, 'print_meta_box' ),
                $this->postType,
                'normal',
                'high'
            );
        }

        public function print_meta_box( $post, $metabox ) {

                global $zbs;

                $html = '';

                // wmod

                        #} retrieve - shouldn't these vars be "other files"... confusing
                        //$zbsFiles = zeroBSCRM_getCustomerFiles($post->ID);
                        $zbsFiles = zeroBSCRM_files_getFiles('company',$post->ID);//zeroBSCRM_getCustomerFiles($companyID);

                ?><table class="form-table wh-metatab wptbp" id="wptbpMetaBoxMainItemFiles">

                    <?php

                    #} Whole file delete method could do with rewrite
                    #} Also sort JS into something usable - should be ajax all this

                    #} Any existing
                    if (is_array($zbsFiles) && count($zbsFiles) > 0){ 
                      ?><tr class="wh-large zbsFileDetails"><th class="zbsFilesTitle"><label><?php echo '<span>'.count($zbsFiles).'</span> '.__('File(s)','zero-bs-crm').':'; ?></label></th>
                                <td id="zbsFileWrapOther">
                                    <table class="ui celled table" id="zbsFilesTable">
                                      <thead>
                                        <tr>
                                            <th><?php _e("File","zerobscrm");?></th>
                                            <th class="collapsing center aligned"><?php _e("Actions","zerobscrm");?></th>
                                        </tr>
                                    </thead><tbody>
                                                <?php $fileLineIndx = 1; foreach($zbsFiles as $zbsFile){

                                                    /* $file = basename($zbsFile['file']);

                                                    // if in privatised system, ignore first hash in name
                                                    if (isset($zbsFile['priv'])){

                                                        $file = substr($file,strpos($file, '-')+1);
                                                    } */
                                                    $file = zeroBSCRM_files_baseName($zbsFile['file'],isset($zbsFile['priv']));

                                                    $fileEditUrl = admin_url('admin.php?page='.$zbs->slugs['editfile']) . "&company=".$post->ID."&fileid=" . ($fileLineIndx-1);

                                                    echo '<tr class="zbsFileLineTR" id="zbsFileLineTRCompany'.$fileLineIndx.'">';
                                                    echo '<td><div class="zbsFileLine" id="zbsFileLineCompany'.$fileLineIndx.'"><a href="'.$zbsFile['url'].'" target="_blank">'.$file.'</a></div>';
                                                    /*
                                                    // if using portal.. state shown/hidden
                                                    // this is also shown in each file slot :) if you change any of it change that too
                                                    if(defined('ZBS_CLIENTPRO_TEMPLATES')){
                                                        if(isset($zbsFile['portal']) && $zbsFile['portal']){
                                                          echo "<p><i class='icon check circle green inverted'></i> ".__('Shown on Portal','zero-bs-crm').'</p>';
                                                        }else{
                                                          echo "<p><i class='icon ban inverted red'></i> ".__('Not shown on Portal','zero-bs-crm').'</p>';
                                                        }
                                                    }*/

                                                    echo '</td>';
                                                    echo '<td class="collapsing center aligned"><span class="zbsDelFile ui button basic" data-delurl="'.$zbsFile['url'].'"><i class="trash alternate icon"></i> '.__('Delete','zero-bs-crm').'</span> <a href="'.$fileEditUrl.'" target="_blank" class="ui button basic"><i class="edit icon"></i> '.__('Edit','zero-bs-crm').'</a></td></tr>';
                                                    $fileLineIndx++;

                                                } ?>
                                    </tbody></table>
                                </td></tr><?php

                    } ?>

                    <?php #adapted from http://code.tutsplus.com/articles/attaching-files-to-your-posts-using-wordpress-custom-meta-boxes-part-1--wp-22291


                            wp_nonce_field(plugin_basename(__FILE__), 'zbsc_file_attachment_nonce');
                             
                            $html .= '<input type="file" id="zbsc_file_attachment" name="zbsc_file_attachment" value="" size="25" class="zbs-dc">';
                            
                            ?><tr class="wh-large"><th><label><?php _e('Add File',"zero-bs-crm");?>:</label><br />(<?php _e('Optional',"zero-bs-crm");?>)<br /><?php _e('Accepted File Types',"zero-bs-crm");?>:<br /><?php echo zeroBS_acceptableFileTypeListStr(); ?></th>
                                <td><?php
                            echo $html;
                    ?></td></tr>

                
                </table>
                <?php

                   // PerfTest: zeroBSCRM_performanceTest_finishTimer('custmetabox');
                   // PerfTest: zeroBSCRM_performanceTest_debugOut();

                   ?>
                <?php #} AJAX NONCE ?><script type="text/javascript">var zbscrmjs_secToken = '<?php echo wp_create_nonce( "zbscrmjs-ajax-nonce" ); ?>';</script><?php # END OF NONCE ?>
                <script type="text/javascript">

                    var zbsCompanyCurrentlyDeleting = false;

                    jQuery('document').ready(function(){

                        jQuery('.zbsDelFile').click(function(){

                            if (!window.zbsCompanyCurrentlyDeleting){

                                // blocking
                                window.zbsCompanyCurrentlyDeleting = true;

                                var delUrl = jQuery(this).attr('data-delurl');
                                //var lineIDtoRemove = jQuery(this).closest('.zbsFileLine').attr('id');
                                var lineToRemove = jQuery(this).closest('tr');

                                if (typeof delUrl != "undefined" && delUrl != ''){



                                      // postbag!
                                      var data = {
                                        'action': 'delFile',
                                        'zbsfType': 'company',
                                        'zbsDel':  delUrl, // could be csv, never used though
                                        'zbsCID': <?php if (!empty($post->ID) && $post->ID > 0) echo $post->ID; else echo -1; ?>,
                                        'sec': window.zbscrmjs_secToken
                                      };

                                      // Send it Pat :D
                                      jQuery.ajax({
                                              type: "POST",
                                              url: ajaxurl, // admin side is just ajaxurl not wptbpAJAX.ajaxurl,
                                              "data": data,
                                              dataType: 'json',
                                              timeout: 20000,
                                              success: function(response) {

                                                var localLineToRemove = lineToRemove, localDelURL = delUrl;

                                                // visually remove
                                                //jQuery(this).closest('.zbsFileLine').remove();
                                                //jQuery('#' + lineIDtoRemove).remove();
                                                jQuery(localLineToRemove).remove();

                                                // update number
                                                var newNumber = jQuery('#zbsFilesTable tr').length-1;
                                                if (newNumber > 0)
                                                    jQuery('#wptbpMetaBoxMainItemFiles .zbsFilesTitle span').html();
                                                else
                                                    jQuery('#wptbpMetaBoxMainItemFiles .zbsFileDetails').remove();


                                                // remove any filled slots (with this file)                                                 
                                                //jQuery('.zbsFileSlotWrap',jQuery("table").find('[data-sloturl="' + localDelURL + '"]')).html('');                                                
                                                //jQuery('.zbsFileSlotTitle',jQuery("table").find('[data-sloturl="' + localDelURL + '"]')).html(0);
                                                jQuery('.zbsFileSlotTable').each(function(ind,ele){

                                                    if (jQuery(ele).attr('data-sloturl') == localDelURL){

                                                        //jQuery('.zbsFileSlotWrap',jQuery(ele)).html('');                                                
                                                        //jQuery('.zbsFileSlotTitle',jQuery(ele)).html(0);
                                                        jQuery('.zbsFileSlotWrap',jQuery(ele)).remove();
                                                
                                                    }

                                                });

                                                // Callback
                                                //if (typeof cb == "function") cb(response);
                                                //callback(response);

                                              },
                                              error: function(response){

                                                jQuery('#zbsFileWrapOther').append('<div class="alert alert-error" style="margin-top:10px;"><strong>Error:</strong> Unable to delete this file.</div>');

                                                // Callback
                                                //if (typeof errorcb == "function") errorcb(response);
                                                //callback(response);


                                              }

                                            });

                                }

                                window.zbsCompanyCurrentlyDeleting = false;

                            } // / blocking

                        });

                    });


                </script><?php

               // PerfTest: zeroBSCRM_performanceTest_finishTimer('other');


        }
        
        //public function save_meta_box( $post_id, $post ) {
        public function save_meta_box( $companyID, $company ) {

            global $zbsc_justUploadedCompany;


            if(!empty($_FILES['zbsc_file_attachment']['name']) && 
                (!isset($zbsc_justUploadedCompany) ||
                    (isset($zbsc_justUploadedCompany) && $zbsc_justUploadedCompany != $_FILES['zbsc_file_attachment']['name'])
                )
                ) {


            /* --- security verification --- */
            if(!wp_verify_nonce($_POST['zbsc_file_attachment_nonce'], plugin_basename(__FILE__))) {
              return $id;
            } // end if


            if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
              return $id;
            } // end if
               
            /* Switched out for WH Perms model 19/02/16 
            if('page' == $_POST['post_type']) { 
              if(!current_user_can('edit_page', $id)) {
                return $id;
              } // end if
            } else { 
                if(!current_user_can('edit_page', $id)) { 
                    return $id;
                } // end if
            } // end if */
            if (!zeroBSCRM_permsCustomers()){
                return $companyID;
            }
            /* - end security verification - */

            #} Blocking repeat-upload bug
            $zbsc_justUploadedCompany = $_FILES['zbsc_file_attachment']['name'];



                $supported_types = zeroBS_acceptableFileTypeMIMEArr(); //$supported_types = array('application/pdf');
                $arr_file_type = wp_check_filetype(basename($_FILES['zbsc_file_attachment']['name']));
                $uploaded_type = $arr_file_type['type'];

                if(in_array($uploaded_type, $supported_types) || (isset($supported_types['all']) && $supported_types['all'] == 1)) {
                    $upload = wp_upload_bits($_FILES['zbsc_file_attachment']['name'], null, file_get_contents($_FILES['zbsc_file_attachment']['tmp_name']));

                    if(isset($upload['error']) && $upload['error'] != 0) {
                        wp_die('There was an error uploading your file. The error is: ' . $upload['error']);
                    } else {
                        //update_post_meta($id, 'zbsc_file_attachment', $upload);

                            // v2.13 - also privatise the file (move to our asset store)
                            // $upload will have 'file' and 'url'
                            $fileName = basename($upload['file']);
                            $fileDir = dirname($upload['file']);
                            $privateThatFile = zeroBSCRM_privatiseUploadedFile($fileDir,$fileName);
                            if (is_array($privateThatFile) && isset($privateThatFile['file'])){ 

                                // successfully moved to our store

                                    // modify URL + file attributes
                                    $upload['file'] = $privateThatFile['file'];
                                    $upload['url'] = $privateThatFile['url'];

                                    // add this extra identifier if in privatised sys
                                    $upload['priv'] = true;

                            } else {

                                // couldn't move to store, leave in uploaded for now :)

                            }


                            // w mod - adds to array :)
                            $zbsCompanyFiles = zeroBSCRM_files_getFiles('company',$companyID);//zeroBSCRM_getCustomerFiles($companyID);

                            if (is_array($zbsCompanyFiles)){

                                //add it
                                $zbsCompanyFiles[] = $upload;

                            } else {

                                // first
                                $zbsCompanyFiles = array($upload);

                            }

                            ///update_post_meta($id, 'zbs_customer_files', $zbsCustomerFiles);  
                            //zeroBSCRM_updateCustomerFiles($companyID,$zbsCompanyFiles);
                            zeroBSCRM_files_updateFiles('company',$companyID,$zbsCompanyFiles);
                    }
                }
                else {
                    wp_die("The file type that you've uploaded is not an accepted file format.");
                }
            }

            return $company;
        }
    }


/* ======================================================
  / Attach files to company metabox
   ====================================================== */


/* ======================================================
  Company Activity Metabox
   ====================================================== */
class zeroBS__Metabox_Company_Activity extends zeroBS__Metabox {

    public function __construct( $plugin_file ) {
    
        $this->postType = 'zerobs_company';
        $this->metaboxID = 'zbs-company-activity-metabox';
        $this->metaboxTitle = __('Activity', 'zerobscrm');
        $this->metaboxIcon = 'heartbeat';
        $this->metaboxScreen = 'zerobs_view_company'; // we can use anything here as is now using our func
        $this->metaboxArea = 'side';
        $this->metaboxLocation = 'high';

        // call this 
        $this->initMetabox();

    }

    public function html( $obj, $metabox ) {
            
            global $zbs; 
            
            $objid = -1; if (is_array($obj) && isset($obj['id'])) $objid = $obj['id'];
            
            // no need for this, $obj will already be same $zbsCustomer = zeroBS_getCustomer($objid, true,true,true);
            
            echo '<div class="zbs-activity">';
                echo '<div class="">';
                    $zbsCompanyActivity = zeroBSCRM_getCompanyLogs($objid,true,100,0,'',false);
                    zeroBSCRM_html_companyTimeline($id,$zbsCompanyActivity,$zbsCompany);
                echo '</div>';
             echo '</div>';

    }

    // nothing to save here.
    public function save_data( $objID, $obj ) {
        return $obj;
    }
}


/* ======================================================
  Company Activity Metabox
   ====================================================== */

