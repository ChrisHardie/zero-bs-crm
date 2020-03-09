<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.5
 *
 * Copyright 2018, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 09/01/18
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

function zeroBSCRM_pages_addEditSegment($potentialID=-1){

    zeroBSCRM_html_addEditSegment($potentialID);

}


function zeroBSCRM_html_addEditSegment($potentialID=-1){

    global $zbs;

    #} New or edit
    $newSegment = true;

    // potential
    $segmentID = (int)$potentialID;

    // attempt retrieve (including has rights)
    $segment = $zbs->DAL->segments->getSegment($segmentID,true);

    if (isset($segment) && isset($segment['id'])) {
    
        // checks out
        $newSegment = false;

    } else {

        // no perms/doesn't checkout
        $segment = false;
    }

    // retrieve conditions/helpers
    $availableConditions = zeroBSCRM_segments_availableConditions();
    $availableConditionOperators = zeroBSCRM_segments_availableConditionOperators();
    $availableTags = $zbs->DAL->getTagsForObjType(array('objtypeid'=>ZBS_TYPE_CONTACT));
    $availableStatuses = zeroBSCRM_getCustomerStatuses(true);

    // debug
    //echo 'id 4 = '.$zbs->DAL->segments->isContactInSegment(4,2).'!';
    //echo 'compileSegmentsAffectedByContact:'.$zbs->DAL->segments->compileSegmentsAffectedByContact(4).'!';

    #} Refresh 2
    ?><div class="zbs-semantic wrap" id="zbs-segment-editor">

            <!-- load blocker not used.
            <div class="ui segment hidden" id="zbs-segment-editor-blocker">
              <div class="ui active inverted dimmer">
                <div class="ui text loader"><?php _e('Saving','zero-bs-crm'); ?></div>
              </div>
              <p></p>
            </div> -->


            <!-- edit segment -->
            <div class="ui huge form centralsimple">

                <div class="field required">
                  <label><?php _e('Name this Segment',"zero-bs-crm"); ?></label>
                  <p style="font-size:0.8em"><?php _e('Enter a descriptive title. This is shown on internal pages and reports.',"zero-bs-crm"); ?></p>
                  <input placeholder="<?php _e('e.g. VIP Customers',"zero-bs-crm"); ?>" type="text" id="zbs-segment-edit-var-title" name="zbs-segment-edit-var-title" class="max500" value="<?php if (isset($segment['name'])) echo $segment['name']; ?>">
                  <?php echo zeroBSCRM_UI2_messageHTML('mini error hidden','',__('This field is required',"zero-bs-crm"),'','zbs-segment-edit-var-title-err'); ?>
                </div>

            </div>

            <!-- edit segment -->
            <div class="ui large form centralsimple segment">

                <div class="field" style="padding-top:0;padding-bottom: 0">

                    <button class="ui icon small button primary right floated" type="button" id="zbs-segment-edit-act-add-condition">
                        <?php _e('Add Condition',"zero-bs-crm"); ?>  <i class="plus icon"></i>
                    </button>

                    <label><?php _e('Conditions',"zero-bs-crm"); ?></label>
                    <p><?php _e('Select conditions which will define this segment.',"zero-bs-crm"); ?></p>

                </div>

                <div id="zbs-segment-edit-conditions" class="ui segments">
                    <!-- built via js -->
                </div>
                <div class="field" style="padding-top:0">
                    <?php echo zeroBSCRM_UI2_messageHTML('mini hidden','',__('Segments require at least one condition',"zero-bs-crm"),'','zbs-segment-edit-conditions-err'); ?>
                </div>

                <div class="field" style="padding-top:1em">
                  <label><?php _e('Match Type',"zero-bs-crm"); ?></label>
                  <p><?php _e('Should contacts in this segment should match any or all the above conditions?:',"zero-bs-crm"); ?></p>                  
                   <select class="ui dropdown" id="zbs-segment-edit-var-matchtype">
                        <option value="all"><?php _e('Match all Conditions',"zero-bs-crm"); ?></option>
                        <option value="one"><?php _e('Match any one Condition',"zero-bs-crm"); ?></option>
                    </select>
                </div>
                
                <h4 class="ui horizontal header divider"><?php _e('Continue',"zero-bs-crm"); ?></h4>

                <div class="jog-on">
                    <button class="ui submit teal large icon button" id="zbs-segment-edit-act-p2preview"><?php _e('Preview Segment',"zero-bs-crm"); ?> <i class="unhide icon"></i></button>
                </div>
            </div>

            <!-- preview segment -->
            <div class="ui large form centralsimple segment hidden" id="zbs-segment-edit-preview">

                <div id="zbs-segment-edit-preview-output">

                </div>
                <?php echo zeroBSCRM_UI2_messageHTML('hidden','',__('Your conditions did not produce any matching Contacts. You can still save this segment, but currently there is no one in it!',"zero-bs-crm"),'','zbs-segment-edit-emptypreview-err'); ?>

                <div class="jog-on">
                    <button class="ui submit positive large icon button" id="zbs-segment-edit-act-p2submit"><?php _e('Save Segment',"zero-bs-crm"); ?> <i class="pie chart icon"></i></button>
                </div>
            </div>

            <?php // ajax + lang bits ?><script type="text/javascript">
            var zbsSegment = <?php echo json_encode($segment); ?>;
            var zbsAvailableConditions = <?php echo json_encode($availableConditions); ?>;
            var zbsAvailableConditionOperators = <?php echo json_encode($availableConditionOperators); ?>;
            var zbsAvailableTags = <?php echo json_encode($availableTags); ?>;
            var zbsAvailableStatuses = <?php echo json_encode($availableStatuses); ?>;
            var zbsSegmentStemURL = '<?php echo zbsLink('edit',-1,'segment',true); ?>';
            var zbsSegmentListURL = '<?php echo zbsLink($zbs->slugs['segments']); ?>';
            var zbsSegmentSEC = '<?php echo wp_create_nonce( "zbs-ajax-nonce" ); ?>';
            var zbsSegmentLang = {

                generalerrortitle: '<?php _e('General Error',"zero-bs-crm"); ?>',
                generalerror: '<?php _e('There was a general error.',"zero-bs-crm"); ?>',

                currentlyInSegment: '<?php _e('Contacts currently match these conditions.',"zero-bs-crm"); ?>',
                previewTitle: '<?php _e('Contacts Preview',"zero-bs-crm"); ?>',

                noName: '<?php _e('Unnamed Contact',"zero-bs-crm"); ?>',
                noEmail: '<?php _e('No Email',"zero-bs-crm"); ?>',

                notags: '<?php _e('No Tags Found',"zero-bs-crm"); ?>',
                nostatuses: '<?php _e('No Statuses Found',"zero-bs-crm"); ?>',

                to: '<?php _e('to',"zero-bs-crm"); ?>',
                eg: '<?php _e('e.g.',"zero-bs-crm"); ?>',

                saveSegment: '<?php echo zeroBSCRM_slashOut('Save Segment',true).' <i class="save icon">'; ?>',
                savedSegment: '<?php echo zeroBSCRM_slashOut('Segment Saved',true).' <i class="check circle outline icon">'; ?>',

                contactfields: '=== <?php _e('Contact Fields',"zero-bs-crm"); ?> ===',

            };</script>

    </div><?php

}


function zeroBSCRM_segments_typeConversions($value='',$type='',$operator='',$direction='in'){

    if (!empty($value)){

        $availableConditions = zeroBSCRM_segments_availableConditions();

        // For dates, convert to UTS here. (EXCEPT FOR daterange!, dealing with that in zeroBSCRM_segments_filterConditions for now)
        if (isset($availableConditions[$type]['conversion']) && $operator != 'daterange'){

            // INBOUND (e.g. post -> db)
            if ($direction == 'in'){

                switch ($availableConditions[$type]['conversion']){

                    case 'date-to-uts':

                        // convert date to uts
                        $value = zeroBSCRM_locale_dateToUTS($value,true);
                        
                        // for those dates used in 'AFTER' this needs to effectively be midnight on the day (start of next day)
                        if  ($operator == 'after') $value += (60*60*24);

                        break;


                }

            } else if ($direction == 'out'){

                // OUTBOUND (e.g. exposing dates in segment editor)

                switch ($availableConditions[$type]['conversion']){

                    case 'date-to-uts':

                        // for those dates used in 'AFTER' 
                        // this needs to effectively be midnight on the day (start of next day)
                        // (in this case, we remove the same)
                        if  ($operator == 'after') $value -= (60*60*24);

                        // convert uts back to date
                        $value = zeroBSCRM_date_i18n(-1,$value);
                        

                        break;


                }




            }

            
        }

    }

    return $value;
}

function zeroBSCRM_segments_availableConditions(){

    return apply_filters('zbs_segment_conditions',array(

            'status' => array('name'=>__('Status',"zero-bs-crm"),'operators' => array('equal','notequal'),'fieldname'=>'status'),
            'fullname' => array('name'=>__('Full Name',"zero-bs-crm"),'operators' => array('equal','notequal','contains'),'fieldname'=>'fullname'),
            'email' => array('name'=>__('Email',"zero-bs-crm"),'operators' => array('equal','notequal','contains'),'fieldname'=>'email'),
            'dateadded' => array('name'=>__('Date Added',"zero-bs-crm"),'operators' => array('before','after','daterange'),'fieldname'=>'dateadded','conversion'=>'date-to-uts'),
            'datelastcontacted' => array('name'=>__('Date Last Contacted',"zero-bs-crm"),'operators' => array('before','after','daterange'),'fieldname'=>'datelastcontacted','conversion'=>'date-to-uts'),
            'tagged' => array('name'=>__('Has Tag',"zero-bs-crm"),'operators' => array('tag'),'fieldname'=>'tagged'),
            'nottagged' => array('name'=>__('Is Not Tagged',"zero-bs-crm"),'operators' => array('tag'),'fieldname'=>'nottagged'),

        ));  
}

// TBA (When DAL2 trans etc.)
//'totalval' => array('name'=>__('Total Value',"zero-bs-crm"),'operators' => array('equal','notequal','larger','less','floatrange'),'fieldname'=>'totalval'),
/* these are for adv segments users only!
'quotecount' => array('name'=>__('Quote Count',"zero-bs-crm"),'operators' => array('equal','notequal','larger','less','intrange'),'fieldname'=>'quotecount'),
'invcount' => array('name'=>__('Invoice Count',"zero-bs-crm"),'operators' => array('equal','notequal','larger','less','intrange'),'fieldname'=>'invcount'),
'trancount' => array('name'=>__('Transaction Count',"zero-bs-crm"),'operators' => array('equal','notequal','larger','less','intrange'),'fieldname'=>'trancount'),
'country' => array('name'=>__('Country',"zero-bs-crm"),'operators' => array('equal','notequal'),'fieldname'=>'country'),
'county' => array('name'=>__('County',"zero-bs-crm"),'operators' => array('equal','notequal'),'fieldname'=>'county'),
'postal' => array('name'=>__('Postal Code',"zero-bs-crm"),'operators' => array('equal','notequal'),'fieldname'=>'postal'),
*/

function zeroBSCRM_segments_availableConditionOperators(){

    return array(

                    'equal' => array('name'=>__('Equals (=)')),
                    'notequal' => array('name'=>__('Not equals (!=)')),
                    'contains' => array('name'=>__('Contains (*)')),
                    'larger' => array('name'=>__('Larger than (>)')),
                    'less' => array('name'=>__('Less than (<)')),
                    'before' => array('name'=>__('Before date')),
                    'after' => array('name'=>__('After date')),
                    'daterange' => array('name'=>__('In date range')),
                    'floatrange' => array('name'=>__('In range')),
                    'intrange' => array('name'=>__('In range')),

                    // added for hypo, but will generally be useful
                    'istrue' => array('name'=>__('Is True')),
                    'isfalse' => array('name'=>__('Is False'))

            );

}  

/*
 * This Parent class allows us to simplify what's needed of each condition into a class below
*/
class zeroBSCRM_segmentCondition {

    public $key = false;
    public $condition = false;

    // killswitch
    private $addFilters = true;


    /**
     * Zero BS CRM Segment Argument Constructor.
     */
    public function __construct($constructionArgs=array()) {

        // in children we play with the order here (preConstructor)
        // so it's separated into an init func
        $this->init($constructionArgs);

    }

    public function init($constructionArgs=array()){

        global $zbs;

        if ($this->addFilters && $this->key !== false && is_array($this->condition)){

            // __ name
            if (isset($this->condition['name'])) $this->condition['name'] = __($this->condition['name'],'zero-bs-crm');

            // add the condition
            add_filter( 'zbs_segment_conditions', array($this,'condition'));

            // add the query arg builder
            add_filter( $zbs->DAL->makeSlug($this->key).'_zbsSegmentArgumentBuild', array($this,'conditionArg'),10,2);

        }

    }

    public function condition($conditions=array()) {

        if ($this->key !== false && is_array($this->condition)) return array_merge($conditions,array($this->key => $this->condition));

        // else don't add
        return $conditions;
    }
    // note starting arg is ignored (should not have been called multiple times)
    public function conditionArg($startingArg=false,$condition=false,$conditionKeySuffix=false){

        global $zbs,$wpdb,$ZBSCRM_t;

        return $startingArg;
    }

}