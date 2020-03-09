<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V3.0
 *
 * Copyright 2019, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 20/02/2019
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

   /* This is done in Metaboxes3.Contacts etc. now 
   function zeroBSCRM_ExternalSourcesMetaboxSetup(){

        // initialise for contact:
        $zeroBS__Metabox_ExtSource = new zeroBS__Metabox_ExtSource( __FILE__, 'contact','zbs-add-edit-contact-edit');

        // if b2bmode, for co's too:
        if (zeroBSCRM_getSetting('companylevelcustomers') == "1") $zeroBS__Metabox_ExtSourceCo = new zeroBS__Metabox_ExtSource( __FILE__, 'company','zbs-add-edit-company-edit');

   }

   add_action( 'admin_init','zeroBSCRM_ExternalSourcesMetaboxSetup');

   */

/* ======================================================
   / Init Func
   ====================================================== */


/* ======================================================
   Create External Source Metabox
   ====================================================== */
    class zeroBS__Metabox_ExtSource extends zeroBS__Metabox {

        private $acceptableTypes = array('contact','company','transaction','invoice');

        public function __construct( $plugin_file, $objType='contact',$metaboxScreen='zbs-add-edit-contact-edit' ) {

            global $zbs;

            // set these
            // DAL3 switched for objType $this->postType = 'zerobs_customer';
            $this->objType = 'contact';
            $this->objTypeID = ZBS_TYPE_CONTACT;
            $this->metaboxID = 'zerobs-externalsource';
            $this->metaboxTitle = __('External Source',"zero-bs-crm");
            $this->metaboxScreen = 'zbs-add-edit-contact-edit'; // DEFAULT (overriden by metaboxScreens below)
            // Better than this, is initiating multiple of this class with diff screens/objtypes
            // ... because that presevers obj type unlike this solution: $this->metaboxScreens = array('zbs-add-edit-contact-edit','zbs-add-edit-company-edit'); // (since v3.0 we can add multiple, overrides metaboxScreen)
            $this->metaboxArea = 'side';
            $this->metaboxLocation = 'low';
            $this->saveOrder = 1;
            $this->capabilities = array(

                'can_hide'          => false, // can be hidden
                'areas'             => array('side'), // areas can be dragged to - normal side = only areas currently
                'can_accept_tabs'   => true,  // can/can't accept tabs onto it
                'can_become_tab'    => false, // can be added as tab
                'can_minimise'      => true, // can be minimised
                'can_move'          => true // can be moved

            );

            // Catch any passed params as overrides
            // (this allows us to have multiple initialised (e.g. one for contacts, one co's))
            if (isset($objType)) $this->objType = $objType;
            if (isset($objType)) $this->objTypeID = $zbs->DAL->objTypeID($this->objType);
            if (isset($metaboxScreen)) $this->metaboxScreen = $metaboxScreen;

            // set typeint based on type
            $this->typeInt = $zbs->DAL->objTypeID($this->objType); // contact -> ZBS_TYPE_CONTACT = 1

            #} Only load if is legit.
            
                // also hide if "new" (not edit) - as defies point of extsource
                $isEdit = false;
                if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['zbsid']) && !empty($_GET['zbsid'])) $isEdit = true;
            
            if (in_array($this->objType,$this->acceptableTypes) && $isEdit){
                // call this 
                $this->initMetabox();
            }

        }

        public function html( $obj, $metabox ) {

                #} Only load if is legit.
                if (in_array($this->objType,$this->acceptableTypes)){

                    // localise ID
                    $objID = -1; if (is_array($obj) && isset($obj['id'])) $objID = (int)$obj['id'];

                    // get sources, if any
                    $zeroBSCRMsources = zeroBS_getExternalSources($objID,$this->objTypeID);
                    
                    // got any?
                    if (isset($zeroBSCRMsources) && is_array($zeroBSCRMsources) && count($zeroBSCRMsources) > 0){
                        
                        if (count($zeroBSCRMsources) > 0) foreach ($zeroBSCRMsources as $extSource){

                            #} Display a "source"
                            echo '<div class="zbsExternalSource">';

                                $uid = $extSource['uid'];

                                // company + CSV means uid will be a useless hash, so replace that with name if we have
                                if ($this->objType == 'company'){

                                  if (isset($this->obj['name'])) 
                                    $uid = $this->obj['name'];
                                  else
                                    $uid = __('Imported based on name','zero-bs-crm');

                                }

                                // moved into func
                                $extSourceTitle = zeroBS_getExternalSourceTitle($extSource['source'],$uid);

                                // filter any given title - can be wired in to give links (e.g. wooc orders)
                                $extSourceTitle = apply_filters( 'zbs_external_source_infobox_line', $extSourceTitle, 
                                  array('objtype'=>$this->objType,
                                    'objid'=>$objID,
                                    'source' => $extSource['source'],
                                    'unique_id' => $uid));

                                // output
                                echo $extSourceTitle;

                            echo '</div>';

                        } 

                    } else {

                        // No External source ? hide?
                        echo '<p><i class="address book icon"></i> '.ucwords($this->objType).' '.__('added manually.','zero-bs-crm').'</p>';

                    }


                } // / only load if post type

        }

        
}
    
/* ======================================================
  / Create External Source Metabox
   ====================================================== */