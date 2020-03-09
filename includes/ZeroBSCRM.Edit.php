<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.52+
 *
 * Copyright 2018, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 26/02/18
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

class zeroBSCRM_Edit{

    private $objID = false;
    private $obj = false; 
    private $objTypeID = false; // ZBS_TYPE_CONTACT - v3.0+

    // following now FILLED OUT by objTypeID above, v3.0+
    private $objType = false; // 'contact'
    private $singular = false; 
    private $plural = false;
    // renamed listViewSlug v3.0+ private $postPage = false;
    private $listViewSlug = false;
    // Discontinued from v3.0 private $tag = false; // this is now retrieved from DAL centralised vars by objTypeID above, v3.0+
    // Discontinued from v3.0 private $postType = false; // this is now retrieved from DAL centralised vars by objTypeID above, v3.0+

    private $langLabels = false;
    private $bulkActions = false;
    private $sortables = false;
    private $unsortables = false;
    private $extraBoxes = '';
    private $isGhostRecord = false;
    private $isNewRecord = false;

    function __construct($args=array()) {


        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            'objID' => false,
            'objTypeID'   => false,   //5

             // these are now retrieved from DAL centralised vars by objTypeID above, v3.0+
             // ... unless hard typed here.
            'objType'   => false,   //transaction
            'singular'   => false,  //Transaction
            'plural' => false,      //Transactions
            'tag' => false,         // Discontinued v3.0 + zerobs_transactiontag
            'postType' => false,    // Discontinued v3.0 + //zerobs_transaction
            'listViewSlug' => false,    //manage-transactions

            'langLabels' => array(
                    
            ),
            'extraBoxes' => '' // html for extra boxes e.g. upsells :)

        ); foreach ($defaultArgs as $argK => $argV){ $this->$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $this->$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$this->$argK = $newData;} else { $this->$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        // NOTE: here these vars are passed like:
        // $this->objID
        // .. NOT
        // $objID


        global $zbs;

        // we load from DAL defaults, if objTypeID passed (overriding anything passed, if empty/false)
        if (isset($this->objTypeID)){ //$zbs->isDAL3() && 

            $objTypeID = (int)$this->objTypeID;
            if ($objTypeID > 0){

                // obj type (contact)
                $objTypeStr = $zbs->DAL->objTypeKey($objTypeID);
                if ((!isset($this->objType) || $this->objType == false) && !empty($objTypeStr)) $this->objType = $objTypeStr;

                // singular
                $objSingular = $zbs->DAL->typeStr($objTypeID);
                if ((!isset($this->singular) || $this->singular == false) && !empty($objSingular)) $this->singular = $objSingular;

                // plural
                $objPlural = $zbs->DAL->typeStr($objTypeID,true);
                if ((!isset($this->plural) || $this->plural == false) && !empty($objPlural)) $this->plural = $objPlural;

                // listViewSlug
                $objSlug = $zbs->DAL->listViewSlugFromObjID($objTypeID);
                if ((!isset($this->listViewSlug) || $this->listViewSlug == false) && !empty($objSlug)) $this->listViewSlug = $objSlug;

            }

            //echo 'loading from '.$this->objTypeID.':<pre>'.print_r(array($objTypeStr,$objSingular,$objPlural,$objSlug),1).'</pre>'; exit();

        } else $this->isNewRecord = true;

        // if objid - load $post
        $this->loadObject();

        // Ghost?
        if ($this->objID !== -1 && !$this->isNewRecord && isset($this->objTypeID) && !is_array($this->obj)) $this->isGhostRecord = true;

        // anything to save?
        $this->catchPost();

    }

    // automatically, generically, loads the single obj
    public function loadObject(){

        // if objid - load $post
        if (isset($this->objID) && !empty($this->objID) && $this->objID > 0) {

            global $zbs;

            // DAL3 we can use generic getSingle
            if ($zbs->isDAL3() && $this->objTypeID > 0){

                // this gets $zbs->DAL->contacts->getSingle()
                $this->obj = $zbs->DAL->getObjectLayerByType($this->objTypeID)->getSingle($this->objID);

            } else {

                // DAL2
                // customer currently only
                $this->obj = zeroBS_getCustomer($this->objID);
            }

        }
    }

    public function catchPost(){

        // If post, fire do_action
            // DAL3 this gets postType switched to objType
        if (isset($_POST['zbs-edit-form-master']) && $_POST['zbs-edit-form-master'] == $this->objType){

            // fire it
            //do_action('zerobs_save_'.$this->postType, $this->objID, $this->obj);
            // DAL3 this gets postType switched to objType
            // debug:  echo 'zerobs_save_'.$this->objType.':'.$this->objID.':<pre>'.print_r($this->obj,1).'</pre>'; exit();
            do_action('zerobs_save_'.$this->objType, $this->objID, $this->obj);

            // after catching post, we need to reload data :) (as may be changed)
            $this->loadObject();

        }
    }

    // check owenship, access etc. 
    public function preChecks(){

        global $zbs;

        // only do this stuff v3.0+
        if ($zbs->isDAL3()){


            // if $this->obj is not an array, somehow it's not been loaded properly (probably perms)
            if (!is_array($this->obj)){            

                // get owner int
                $objOwner = $zbs->DAL->getObjectOwner(array(

                                'objID'         => $this->objID,
                                'objTypeID'       => $this->objTypeID

                        ));

                // current user
                $currentUserID = get_current_user_id();                

                if ($objOwner > 0 && $objOwner != $currentUserID){

                    // not current user
                    
                    // can even change owner?
                    $canGiveOwnership = $zbs->settings->get('usercangiveownership');
                    $canChangeOwner = ($canGiveOwnership == "1" || current_user_can('administrator'));

                    if (!$canChangeOwner){

                        // owners can't be changed, and this isn't theirs, show No msg
                        $this->preCheckFail(__('You do not have permission to load this',"zero-bs-crm").' '.$zbs->DAL->typeStr($this->objTypeID));

                        return false;

                    }

                    // ... otherwise it can be changed, so this isn't a perms loading issue

                    // show general issue
                    $this->preCheckFail(__('There was an error loading this',"zero-bs-crm").' '.$zbs->DAL->typeStr($this->objTypeID));

                    return false;

                }

            }

        }


        #} Only load if is legit.
        return true;
    }

    public function preCheckFail($msg=''){

            echo '<div id="zbs-obj-edit-precheck-fail" class="ui grid"><div class="row"><div class="two wide column"></div><div class="twelve wide column">';
            echo zeroBSCRM_UI2_messageHTML('warning',$msg,'','disabled warning sign','failRetrieving');
            echo '</div></div>';

            // grim quick hack to hide save button
            echo '<style>#zbs-edit-save{display:none}</style>';
    }

    public function drawEditView(){

        // run pre-checks which verify ownership etc.
        $okayToDraw = $this->preChecks();

        // draw if okay :)
        if ($okayToDraw) $this->drawEditViewHTML();

    }

    public function drawEditViewHTML(){

        if (empty($this->objType) || empty($this->listViewSlug) || empty($this->singular) || empty($this->plural)){


            echo zeroBSCRM_UI2_messageHTML('warning','Error Retrieving '.$this->singular,'There has been a problem retrieving your '.$this->singular.', if this issue persists, please contact support.','disabled warning sign','zbsCantLoadData');  
            return false;

        }

        // catch id's passed where no contact exists for them.
        if ($this->isGhostRecord){

            // brutal hide, then msg #ghostrecord
            ?><style type="text/css">#zbs-edit-save, #zbs-nav-view, #zbs-nav-prev, #zbs-nav-next { display:none; }</style>
            <div id="zbs-edit-warnings-wrap"><?php
            echo zeroBSCRM_UI2_messageHTML('warning','Error Retrieving '.$this->singular,'There does not appear to be a '.$this->singular.' with this ID.','disabled warning sign','zbsCantLoadData');  
            ?></div><?php  
            return false;

        }

        // catch if is new record + hide zbs-nav-view
        if ($this->isNewRecord){

            // just hide button via css. Should just stop this via learn in time
            ?><style type="text/css">#zbs-nav-view { display:none; }</style><?php  

        }

        global $zbs;

        // run pre-checks which verify ownership etc.
        $this->preChecks();


        ?><div id="zbs-edit-master-wrap"><form method="post" id="zbs-edit-form" enctype="multipart/form-data"><input type="hidden" name="zbs-edit-form-master" value="<?php echo $this->objType; ?>" />
        <style>

        </style>
        <?php
                // put screen options out
                zeroBSCRM_screenOptionsPanel();
        ?>

            <div id="zbs-edit-warnings-wrap">
                <?php #} Pre-loaded msgs, because I wrote the helpers in php first... should move helpers to js and fly these 

                echo zeroBSCRM_UI2_messageHTML('warning hidden','Error Retrieving '.$this->plural,'There has been a problem retrieving your '.$this->singular.', if this issue persists, please ask your administrator to reach out to ZBSCRM.','disabled warning sign','zbsCantLoadData');
                echo zeroBSCRM_UI2_messageHTML('warning hidden','Error Retrieving '.$this->singular,'There has been a problem retrieving your '.$this->singular.', if this issue persists, please ask your administrator to reach out to ZBSCRM.','disabled warning sign','zbsCantLoadDataSingle');
              
                ?>
            </div>
            <!-- main view: list + sidebar -->
            <div id="zbs-edit-wrap" class="ui divided grid <?php echo 'zbs-edit-wrap-'.$this->objType; ?>">

                <?php

                    if (count($zbs->pageMessages) > 0){
                
                        #} Updated Msgs
                        // was doing like this, but need control over styling
                        // do_action( 'zerobs_updatemsg_contact');
                        // so for now just using global :)
                        echo '<div class="row" style="padding-bottom: 0 !important;" id="zbs-edit-notification-row"><div class="sixteen wide column" id="zbs-edit-notification-wrap">';

                            foreach ($zbs->pageMessages as $msg){

                                // for now these can be any html :)
                                echo $msg;

                            }

                        echo '</div></div>';

                    }



                ?>

                <div class="row">


                    <!-- record list -->
                    <div class="twelve wide column" id="zbs-edit-table-wrap">

                        <?php 
                            #} Main Metaboxes
                            zeroBSCRM_do_meta_boxes( 'zbs-add-edit-'.$this->objType.'-edit', 'normal', $this->obj );
                        ?>

                    </div>
                    <!-- side bar -->
                    <div class="four wide column" id="zbs-edit-sidebar-wrap">
                        <?php 

                            #} Sidebar metaboxes
                            zeroBSCRM_do_meta_boxes( 'zbs-add-edit-'.$this->objType.'-edit', 'side', $this->obj );

                        ?>

                        <div class="ui divider"></div>
                        <?php ##WLREMOVE ?>
                        <?php echo $this->extraBoxes; ?>
                        <?php ##/WLREMOVE ?>
                    </div>
                </div>

                <!-- could use this for mobile variant?) 
                <div class="two column mobile only row" style="display:none"></div>
                -->
            </div> <!-- / mainlistview wrap -->
        </form></div>

        <script type="text/javascript">

            jQuery(document).ready(function($){
                console.log("======= EDIT VIEW UI =========");

            /* WH causes error on load...
            jQuery('.learn')
              .popup({
                inline: false,
                on:'click',
                lastResort: 'bottom right',
            }); */

              jQuery('.show-more-tags').on("click",function(e){
                jQuery('.more-tags').show();
                jQuery(this).hide();
              });

            });

            // General options for edit page
            var zbsEditSettings = {

                objid: <?php echo $this->objID; ?>,
                objdbname: '<?php echo $this->objType; ?>'

            };
            var zbsDrawEditViewBlocker = false;
            var zbsDrawEditAJAXBlocker = false;
            var zbsDrawEditLoadingBoxHTML = '<?php echo zeroBSCRM_UI2_loadingSegmentIncTextHTML(); ?>';

            <?php // these are all legacy, move over to zeroBSCRMJS_obj_editLink in global js: ?>
            var zbsObjectViewLinkPrefixCustomer = '<?php echo zbsLink('view',-1,'zerobs_customer',true); ?>';
            var zbsObjectEditLinkPrefixCustomer = '<?php echo zbsLink('edit',-1,'zerobs_customer',true); ?>';
            var zbsObjectViewLinkPrefixCompany = '<?php echo zbsLink('view',-1,'zerobs_company',true); ?>';
            var zbsListViewLink = '<?php echo zbsLink($this->listViewSlug); ?>';

            
            var zbsClick2CallType = parseInt('<?php echo zeroBSCRM_getSetting('clicktocalltype'); ?>');
            var zbsEditViewLangLabels = {

                    'today': '<?php echo zeroBSCRM_slashOut(__('Today',"zero-bs-crm")); ?>',
                    'view': '<?php echo zeroBSCRM_slashOut(__('View',"zero-bs-crm")); ?>',
                    'contact': '<?php echo zeroBSCRM_slashOut(__('Contact',"zero-bs-crm")); ?>',
                    'company': '<?php echo zeroBSCRM_slashOut(__('Company',"zero-bs-crm")); ?>',

                    <?php $labelCount = 0; 
                    if (count($this->langLabels) > 0) foreach ($this->langLabels as $labelK => $labelV){

                        if ($labelCount > 0) echo ',';

                        echo $labelK.":'".zeroBSCRM_slashOut($labelV)."'";

                        $labelCount++;

                    } ?>

            };
            <?php   #} Nonce for AJAX
                    echo 'var zbscrmjs_secToken = \''.wp_create_nonce( "zbscrmjs-ajax-nonce" ).'\';'; ?></script><?php

    } // /draw func

} // class