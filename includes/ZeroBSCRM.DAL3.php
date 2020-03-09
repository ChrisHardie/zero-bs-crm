<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.20
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 01/11/16
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */


   /* DAL3.0 Notes:

        THIS FILE replaces previous DAL2.php, with the following changes:
        
        - Originally this was DAL2 1 x class containting methods like:
            - DAL->getContacts
        - ... in DAL3 these have moved to: 
            - DAL->contacts->getContacts()
        - ... for better organisation. 
        - This file, then, basically shuffles original DAL2 out into 4 classes:
            - DAL, contacts, segments, logs
            - As DAL, DAL->contacts, DAL->segments, DAL->logs
        - ... it also adds splat/catcher funcs for OLD references e.g. getContacts,
        - ... so that they will still function (but log need for replacement)

        ... so it's a kind of chimera DAL2 to bridge the gap between DAL2 + DAL3.
        ... should be fine to cut the chimera splats after a while though (or even pre v3.0 proper)


   */

/* ======================================================
   DB GENERIC/OBJ Helpers (not DAL GET etc.)
   ====================================================== */

    // ===============================================================================
    // ===========  PERMISSIONS HELPERS  =============================================

    // in time this'll allow multiple 'sites' per install (E.g. site 1 = epic.zbs.com site 2 = zbs.zbs.com)
    // for now, this is hard-coded to 1
    // replaces "zeroBSCRM_installSite" from old DAL
    function zeroBSCRM_site(){

        return 1;

    }
    // in time this'll allow multiple 'team' per site (E.g. branch1,branch2 etc.)
    // for now, this is hard-coded to 1
    // replaces "zeroBSCRM_installTeam" from old DAL
    function zeroBSCRM_team(){

        return 1;

    }
    // active user id - helper func
    // replaces "zeroBSCRM_currentUserID" from old DAL
    // can alternatively user $zbs->user()
    function zeroBSCRM_user(){

        return get_current_user_id();

    }

    // =========== /  PERMISSIONS HELPERS  ===========================================
    // ===============================================================================

    // ===============================================================================
    // ===========  TYPES ============================================================

        define('ZBS_TYPE_CONTACT',      1);
        define('ZBS_TYPE_COMPANY',      2);
        define('ZBS_TYPE_QUOTE',        3);
        define('ZBS_TYPE_INVOICE',      4);
        define('ZBS_TYPE_TRANSACTION',  5);
        define('ZBS_TYPE_EVENT',        6);
        define('ZBS_TYPE_FORM',         7);
        define('ZBS_TYPE_LOG',          8);
        define('ZBS_TYPE_SEGMENT',      9);
        define('ZBS_TYPE_LINEITEM',     10);
        define('ZBS_TYPE_EVENTREMINDER', 11);
        define('ZBS_TYPE_QUOTETEMPLATE', 12);
        define('ZBS_TYPE_ADDRESS',      13); // this is a precursor to v4 where we likely need to split out addresses from current in-object model (included here as custom fields now managed as if obj)

    // =========== /  TYPES  =========================================================
    // ===============================================================================


/* ======================================================
   / DB GENERIC/OBJ Helpers (not DAL GET etc.)
   ====================================================== */




/**
* zbsDAL is the Data Access Layer for ZBS v2.5+
*
* zbsDAL provides expanded CRUD actions to the 
* ZBS CRM generally, and will be initiated globally
* like the WordPress $wpdb. 
*
* @author   Woody Hayday <hello@zerobscrm.com>
* @version  2.0
* @access   public
* @see      https://zerobscrm.com/kb
*/
class zbsDAL {

    public $version = 3.0;


    // ===============================================================================
    // ===========  SUB DAL LAYERS  ==================================================
    // These hold sub-objects, e.g. contact
    public $contacts = false;
    public $segments = false;
    public $companies = false;
    public $quotes = false;
    public $invoices = false;
    public $transactions = false;
    public $forms = false;
    public $events = false;
    public $logs = false;
    public $lineitems = false;
    public $quotetemplates = false;


    // ===========  / SUB DAL LAYERS  ================================================
    // ===============================================================================


    // ===============================================================================
    // ===========  OBJECT TYPE DEFINITIONS    =======================================
    // These are here to validate types, use CONSTANTS in code (e.g. ZBS_TYPE_CONTACT)

    private $typesByID = array(

            ZBS_TYPE_CONTACT => 'contact',
            ZBS_TYPE_COMPANY => 'company',
            ZBS_TYPE_QUOTE => 'quote',
            ZBS_TYPE_INVOICE => 'invoice',
            ZBS_TYPE_TRANSACTION => 'transaction',
            ZBS_TYPE_EVENT => 'event',
            ZBS_TYPE_FORM => 'form',
            ZBS_TYPE_SEGMENT => 'segment',
            ZBS_TYPE_LOG => 'log',
            ZBS_TYPE_LINEITEM => 'lineitem',
            ZBS_TYPE_EVENTREMINDER => 'eventreminder',
            ZBS_TYPE_QUOTETEMPLATE => 'quotetemplate',
            ZBS_TYPE_ADDRESS => 'address'

    );

    // retrieve via DAL->oldCPT(1)
    private $typeCPT = array(

            ZBS_TYPE_CONTACT => 'zerobs_customer',
            ZBS_TYPE_COMPANY => 'zerobs_company',
            ZBS_TYPE_QUOTE => 'zerobs_quote',
            ZBS_TYPE_INVOICE => 'zerobs_invoice',
            ZBS_TYPE_TRANSACTION => 'zerobs_transaction',
            ZBS_TYPE_EVENT => 'zerobs_event',
            ZBS_TYPE_FORM => 'zerobs_form',
            // these never existed:
            //ZBS_TYPE_SEGMENT => 'zerobs_segment',
            //ZBS_TYPE_LOG => 'zerobs_log',
            //ZBS_TYPE_LINEITEM => 'lineitem'
            //ZBS_TYPE_EVENTREMINDER => 'eventreminder'
            ZBS_TYPE_QUOTETEMPLATE => 'zerobs_quo_template',
    );

    // retrieve via DAL->typeStr(1)
    private $typeNames = array(

            ZBS_TYPE_CONTACT =>         array('Contact','Contacts'),
            ZBS_TYPE_COMPANY =>         array('Company','Companies'),
            ZBS_TYPE_QUOTE =>           array('Quote','Quotes'),
            ZBS_TYPE_INVOICE =>         array('Invoice','Invoices'),
            ZBS_TYPE_TRANSACTION =>     array('Transaction','Transactions'),
            ZBS_TYPE_EVENT =>           array('Event','Events'),
            ZBS_TYPE_FORM =>            array('Form','Forms'),
            ZBS_TYPE_SEGMENT =>         array('Segment','Segments'),
            ZBS_TYPE_LOG =>             array('Log','Logs'),
            ZBS_TYPE_LINEITEM =>        array('Line Item','Line Items'),
            ZBS_TYPE_EVENTREMINDER =>   array('Event Reminder','Event Reminders'),
            ZBS_TYPE_QUOTETEMPLATE =>   array('Quote Template','Quote Templates'),
            ZBS_TYPE_ADDRESS =>         array('Address','Addresses')
    );

    // List View refs
    private $listViewRefs = array(

            // each of these is a slug for $zbs->slugs e.g. $zbs->slugs['managecontacts']
            ZBS_TYPE_CONTACT =>         'managecontacts',
            ZBS_TYPE_COMPANY =>         'managecompanies',
            ZBS_TYPE_QUOTE =>           'managequotes',
            ZBS_TYPE_INVOICE =>         'manageinvoices',
            ZBS_TYPE_TRANSACTION =>     'managetransactions',
            ZBS_TYPE_EVENT =>           'manage-events',
            ZBS_TYPE_FORM =>            'manageformscrm',
            ZBS_TYPE_SEGMENT =>         'segments',
            //no list page ZBS_TYPE_LOG =>             'managecontacts',
            //no list page ZBS_TYPE_LINEITEM =>        'managecontacts',
            //no list page ZBS_TYPE_EVENTREMINDER =>   'managecontacts',
            ZBS_TYPE_QUOTETEMPLATE =>   'quote-templates'
    );



    // field obj models
    // these match the $globals in fields.php - bit of a legacy chunk tbh, but used throughout
    private $fieldModelsByID = array(

            ZBS_TYPE_CONTACT =>         'zbsCustomerFields',
            ZBS_TYPE_COMPANY =>         'zbsCompanyFields',
            ZBS_TYPE_QUOTE =>           'zbsCustomerQuoteFields',
            ZBS_TYPE_INVOICE =>         'zbsCustomerInvoiceFields',
            ZBS_TYPE_TRANSACTION =>     'zbsTransactionFields',
            //ZBS_TYPE_EVENT =>           'zbsFormFields',
            ZBS_TYPE_FORM =>            'zbsFormFields',

    );

    // legacy support.
    private $oldTaxonomies = array(

            'zerobscrm_customertag' => ZBS_TYPE_CONTACT,
            'zerobscrm_companytag' => ZBS_TYPE_COMPANY,
            'zerobscrm_transactiontag' => ZBS_TYPE_TRANSACTION,
            'zerobscrm_logtag' => ZBS_TYPE_LOG,

        );


    // this is a shorthand for grabbing all addr fields 
    private $field_list_address = array(

            'zbsc_addr1','zbsc_addr2','zbsc_city','zbsc_postcode','zbsc_county','zbsc_country'

    );
    private $field_list_address2 = array(

            'zbsc_secaddr1','zbsc_secaddr2','zbsc_seccity','zbsc_secpostcode','zbsc_seccounty','zbsc_seccountry'

    );
    private $field_list_address_full = array(

            'zbsc_addr1','zbsc_addr2','zbsc_city','zbsc_postcode','zbsc_county','zbsc_country',
            'zbsc_secaddr1','zbsc_secaddr2','zbsc_seccity','zbsc_secpostcode','zbsc_seccounty','zbsc_seccountry'

    );

    // this stores any insert errors
    private $errorStack = array();

    // =========== /  OBJECT TYPE DEFINITIONS   ======================================
    // ===============================================================================




    // ===============================================================================
    // ===========  INIT =============================================================
    function __construct($args=array()) {

        // init sub-layers:
        $this->contacts = new zbsDAL_contacts;
        $this->segments = new zbsDAL_segments;

        global $zbs;
        if ($zbs->isDAL3()){
            $this->companies = new zbsDAL_companies;
            $this->quotes = new zbsDAL_quotes;
            $this->invoices = new zbsDAL_invoices;
            $this->transactions = new zbsDAL_transactions;
            $this->forms = new zbsDAL_forms;
            $this->events = new zbsDAL_events;
            $this->eventreminders = new zbsDAL_eventreminders;
            $this->logs = new zbsDAL_logs;
            $this->lineitems = new zbsDAL_lineitems;
            $this->quotetemplates = new zbsDAL_quotetemplates;
        }

    }
    // ===========  / INIT ===========================================================
    // ===============================================================================




    // ===============================================================================
    // ===========  HELPER/GET FUNCS =================================================

    public function field_list_address(){ return $this->field_list_address; }
    public function field_list_address2(){ return $this->field_list_address2; }
    public function field_list_address_full(){ return $this->field_list_address_full; }

    public function getObjectTypesByKey(){

        return array_flip($this->typesByID);

    }
    // returns old obj types by key
    public function getCPTObjectTypesByKey(){

        return array_flip($this->typeCPT);

    }

    // returns $zbs->DAL->contacts by '1'
    // for now brutal switch, avoid using anywhere but internally, use proper refs elsewhere
    // ... avoid use where not essential as does not produce highly readable code.
    public function getObjectLayerByType($objTypeID=-1){

        switch ($objTypeID){
            case ZBS_TYPE_CONTACT:
                return $this->contacts; break;
            case ZBS_TYPE_COMPANY:
                return $this->companies; break;
            case ZBS_TYPE_QUOTE:
                return $this->quotes; break;
            case ZBS_TYPE_INVOICE:
                return $this->invoices; break;
            case ZBS_TYPE_TRANSACTION:
                return $this->transactions; break;
            case ZBS_TYPE_FORM:
                return $this->forms; break;
            case ZBS_TYPE_EVENT:
                return $this->events; break;
            case ZBS_TYPE_EVENTREMINDER:
                return $this->eventreminders; break;
            case ZBS_TYPE_LOG:
                return $this->logs; break;
            case ZBS_TYPE_LINEITEM:
                return $this->lineitems; break;
            case ZBS_TYPE_QUOTETEMPLATE:
                return $this->quotetemplates; break;
        }

        return false;

    }

    public function isValidObjTypeID($objTypeIn=false){

        // if it's not an int type, cast it...?
        if (!is_int($objTypeIn)) $objTypeIn = (int)$objTypeIn;

        // if bigger than 0 and in our arr as a key, basic check
        if ($objTypeIn > 0 && isset($this->typesByID[$objTypeIn])) return true;

        return false;

    }

    // takes in an obj type str (e.g. 'contact') and returns DEFINED KEY ID = 1
    public function objTypeID($objTypeStr=''){

        // catch some legacy translations
        if ($objTypeStr == 'customer') $objTypeStr = 'contact';

        $byStr = $this->getObjectTypesByKey();
        if (isset($byStr[$objTypeStr])) return $byStr[$objTypeStr];

        // if not, fall back to old obj cpt types
        $byStr = $this->getCPTObjectTypesByKey();
        if (isset($byStr[$objTypeStr])) return $byStr[$objTypeStr];

        return -1;

    }

    // takes in an obj type int (e.g. 1) and returns key (e.g. 'contact')
    public function objTypeKey($objTypeID=-1){

        if (isset($this->typesByID[$objTypeID])) return $this->typesByID[$objTypeID];

        return -1;

    }

    // takes in an obj type int (e.g. 1) and returns field global var name (e.g. 'zbsCustomerFields')
    public function objFieldVarName($objTypeID=-1){

        if (isset($this->fieldModelsByID[$objTypeID])) return $this->fieldModelsByID[$objTypeID];

        return -1;

    }

    // takes in an obj type (e.g. 1) and returns obj model for that type (as per sub layer)
    // uses getObjectLayerByType to keep generic. Use only in fairly high level generic funcs.
    public function objModel($objTypeID=-1){

        if ($objTypeID > 0){

            $objLayer = $this->getObjectLayerByType($objTypeID);

            // if set, $objLayer will effectively be $zbs->DAL->contacts obj
            if (method_exists($objLayer,'objModel')){

                // all good
                return $objLayer->objModel();
            }

        }

        return false;
         
    }

    // takes in an old taxonomy str (e.g. zerobscrm_customertag) and returns new obj key (e.g. 1)
    public function cptTaxonomyToObjID($taxonomyStr=-1){

        if (isset($this->oldTaxonomies[$taxonomyStr])) return $this->oldTaxonomies[$taxonomyStr];

        return -1;

    }

    // takes in an obj ID and gives back the list view slug
    public function listViewSlugFromObjID($objTypeID=-1){

        global $zbs;

        if (isset($this->listViewRefs[$objTypeID]) && isset($zbs->slugs[$this->listViewRefs[$objTypeID]])) return $zbs->slugs[$this->listViewRefs[$objTypeID]];

        return '';

    }

    // retrieves single/plural 'str' for obj type id (e.g. 1 = Contact/Contacts)
    public function typeStr($typeInt=-1,$plural=false){

        $typeInt = (int)$typeInt;
        if ($typeInt > 0){

            if (isset($this->typeNames[$typeInt])){

                // plural
                if ($plural) return __($this->typeNames[$typeInt][1],'zero-bs-crm');
                // single
                return __($this->typeNames[$typeInt][0],'zero-bs-crm');

            }

        }
        return '';
    }

    // retrieves old CPT for that type
    public function typeCPT($typeInt=-1){

        $typeInt = (int)$typeInt;
        if ($typeInt > 0){

            if (isset($this->typeCPT[$typeInt])) return $this->typeCPT[$typeInt];
        }
        return false;
    }

    // =========== / HELPER/GET FUNCS ================================================
    // ===============================================================================




    // ===============================================================================
    // ===========  OWNERSHIP HELPERS  ===============================================

    // This func is a side-switch alternative to zeroBS_checkOwner
    public function checkObjectOwner($args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            'objID'         => -1,
            'objTypeID'       => -1,

            // id to compare to
            'potentialOwnerID'       => -1,

            // if not owned, return true?
            'allowNoOwnerAccess' => -1

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============)

        if ($objID !== -1 && $objTypeID > 0){

            $ownerID = $this->getObjectOwner(array(
                                    'objID'         => $objID,
                                    'objTypeID'       => $objTypeID
                                ));

            if (isset($ownerID) && $ownerID == $potentialOwnerID) 
                return true;
            // no owner owns this!
            else if ($allowNoOwnerAccess && (!isset($potentialOwner) || $potentialOwner == -1))
                return true;

        }

        return false;

    }

    // This func is a side-switch alternative to zeroBS_getOwner
    public function getObjectOwner($args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            'objID'         => -1,
            'objTypeID'       => -1

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============)

        if ($objID !== -1 && $objTypeID > 0){

            return $this->getFieldByID(array(
                'id' => $objID,
                'objtype' => $objTypeID,
                'colname' => 'zbs_owner',
                'ignoreowner'=>true));

        }

        return false;

    }

    // This func is a side-switch alternative to zeroBS_setOwner
    public function setObjectOwner($args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            'objID'         => -1,
            'objTypeID'       => -1,

            'ownerID'       => -1

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============)

        if ($objID !== -1 && $objTypeID > 0){

            return $this->getFieldByID(array(
                'id' => $objID,
                'objtype' => $objTypeID,
                'colname' => 'zbs_owner',
                'ignoreowner'=>true));

        }

        return false;

    }

    // this is used to get specific user's settings via userSetting
    private $userSettingPrefix = 'usrset_'; // completes via usrset_*ID*_key

    /* old way of doing it: (we now use zbs_owner :))
    private function getUserSettingPrefix($userID=-1){

        // completes usrset_*ID*_key
        return $this->userSettingPrefix.$userID.'_';
    } */

    // Note: Following MUST be used together.

        // this makes query vars (as appropriate) team + site (and owner if  $ignoreOwner not false)
        public function ownershipQueryVars($ignoreOwner=false){

            $queryVars = array();

            // add site
            // FOR V3.0 SITE + TEAM NOT YET USED, (BUT THIS'll WORK)
            //$queryVars[] = zeroBSCRM_site();

            // add team
            // FOR V3.0 SITE + TEAM NOT YET USED, (BUT THIS'll WORK)
            //$queryVars[] = zeroBSCRM_team();

            // add owner
            if (!$ignoreOwner) $queryVars[] = zeroBSCRM_user();

            return $queryVars;
            
        }
        // this makes query str (as appropriate) team + site (and owner if  $ignoreOwner not false)
        // $table ONLY needed when is a LEFT JOIN or similar.
        public function ownershipSQL($ignoreOwner=false,$table=''){

            // build
            $q = ''; $tableStr = ''; if (!empty($table)) $tableStr = $table.'.';

            // add site
            // FOR V3.0 SITE + TEAM NOT YET USED, (BUT THIS'll WORK)
            //$q = $this->spaceAnd($q).$tableStr.'zbs_site = %d';

            // add team
            // FOR V3.0 SITE + TEAM NOT YET USED, (BUT THIS'll WORK)
            //$q = $this->spaceAnd($q).$tableStr.'zbs_team = %d';

            // add owner
            if (!$ignoreOwner) $q = $this->spaceAnd($q).$tableStr.'zbs_owner = %d';

            return $q;
            
        }



    // ===========  / OWNERSHIP HELPERS  =============================================
    // ===============================================================================



    // ===============================================================================
    // ===========  ERROR HELPER FUNCS ===============================================
    /* These are shared between DAL2 + DAL3, though are only included from v3.0 +   */

        // retrieve errors from dal error stack
        public function getErrors($objTypeID=-1){

            // all:
            if ($objTypeID < 0) return $this->errorStack;

            // specific
            if (is_array($this->errorStack) && isset($this->errorStack[$objTypeID])) return $this->errorStack[$objTypeID];


            // ??
            return array();
        }

        // add error to dal error stack
        public function addError($errorCode=-1,$objTypeID=-1,$errStr='',$extraParam=false){

            if ($objTypeID > 0 && !empty($errStr)){

                // init
                if (!isset($this->errorStack) || !is_array($this->errorStack)) $this->errorStack = array();
                if (!isset($this->errorStack[$objTypeID]) || !is_array($this->errorStack[$objTypeID])) $this->errorStack[$objTypeID] = array();

                // if $errorCode, add to string
                if ($errorCode > 0) $errStr .= ' ('.__('Error #','zero-bs-crm').$errorCode.')';

                // add
                $this->errorStack[$objTypeID][] = array('code'=>$errorCode,'str'=>$errStr,'param'=>$extraParam);
            }
        }

    // =========== / ERROR HELPER FUNCS ==============================================
    // ===============================================================================





/* ======================================================
   DAL CRUD
   ====================================================== */


    // ===============================================================================
    // ===========   OBJ LINKS   =======================================================

    /**
     * returns objects against an obj (e.g. company's against contact id 101)
     * This is like getObjsLinksLinkedToObj, only it returns actual objs :)
     *
     * @param array $args   Associative array of arguments
     *                      obj array
     *
     * @return array result
     */
    public function getObjsLinkedToObj($args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            'objtypefrom'       => -1,
            'objtypeto'         => -1,

            // either or here, to specify direction of relationship
            'objfromid'         => -1,
            'objtoid'           => -1,

            // this will be passed to the getCompanies(array()) func, if given
            'objRetrievePassthrough' => array(), 

            'count' => false, // only return count

            // permissions
            //'ignoreowner'     => false // this'll let you not-check the owner of obj
            // NOTE 'owner' will ALWAYS be ignored by this, but allows for team/site
            // settings don't need owners yet :)

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        // hard ignored for now :)
        $ignoreowner = true;

        if (!isset($objtypefrom) || empty($objtypefrom)) return false;
        if ($this->objTypeKey($objtypefrom) === -1) return false;
        if (!isset($objtypeto) || empty($objtypeto)) return false;
        if ($this->objTypeKey($objtypeto) === -1) return false;

        #} Check ID
        $direction = 'from'; 
        $objfromid = (int)$objfromid; 
        $objtoid = (int)$objtoid; if ($objtoid > 0) $direction = 'to';

        if (
                (!empty($objfromid) && $objfromid > 0)
                ||
                (!empty($objtoid) && $objtoid > 0)

            ){

            $res = array();

            // get links - this could all be one query... optimise once other db objects moved over
            $objLinks = $this->getObjsLinksLinkedToObj(array(
                        'objtypefrom'   =>  $objtypefrom, // contact
                        'objtypeto'     =>  $objtypeto, // company
                        'objfromid'     =>  $objfromid, //-1 or id
                        'objtoid'       =>  $objtoid));

            if ($count) {
                if (is_array($objLinks))
                    return count($objLinks);
                else
                    return 0;
            }

            if (is_array($objLinks) && count($objLinks) > 0){ 

                // make an id array (useful)
                $idArray = array(); foreach ($objLinks as $l) {
                    
                    // switched direction
                    $xid = $l['objidto']; if ($direction == 'to') $xid = $l['objidfrom'];

                    if (!in_array($xid, $idArray)) $idArray[] = $xid;
                }

                // load them all (type dependent)
                switch ($objtypeto){


                    // not yet used, but will work :)
                    case ZBS_TYPE_CONTACT:
                        return $this->contacts->getContacts(array('inArr'=>$idArray));
                        break;

                    case ZBS_TYPE_COMPANY:
                        return $this->companies->getCompanies(array('inArr'=>$idArray));
                        break;

                    case ZBS_TYPE_QUOTE:
                        return $this->quotes->getQuotes(array('inArr'=>$idArray));
                        break;

                    case ZBS_TYPE_INVOICE:
                        return $this->invoices->getInvoices(array('inArr'=>$idArray));
                        break;

                    case ZBS_TYPE_TRANSACTION:
                        return $this->transactions->getTransactions(array('inArr'=>$idArray));
                        break;

                    case ZBS_TYPE_EVENT:
                        return $this->events->getEvents(array('inArr'=>$idArray));
                        break;

                    case ZBS_TYPE_QUOTETEMPLATE:
                        return $this->quotetemplates->getQuotetemplate(array('inArr'=>$idArray));
                        break;

                    /* not used
                    case ZBS_TYPE_LOG:
                        return $this->logs->getLogs(array('inArr'=>$idArray));
                        break;

                    case ZBS_TYPE_LINEITEM:
                        return $this->events->getEvents(array('inArr'=>$idArray));
                        break;

                    case ZBS_TYPE_EVENTREMINDER:
                        return $this->events->getEvents(array('inArr'=>$idArray));
                        break;
                    */
                        
                }


            }



            return $res;

        } // / if ID

        return false;

    }

    /**
     * returns object link lines against an obj (e.g. link id, company id's against contact id 101)
     *
     * @param array $args   Associative array of arguments
     *                      objtypeid, objid
     *
     * @return array result
     */
    public function getObjsLinksLinkedToObj($args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            'objtypefrom'       => -1,
            'objtypeto'         => -1,

            // either or here, to specify direction of relationship
            // if 'direction' = 'both', it'll check both
            'objfromid'         => -1,
            'objtoid'           => -1,

            'direction'          => 'from', // from, to, both (both checks for both id's and is used to validate if links exist)

            'count' => false, // only return count

            // permissions
            //'ignoreowner'     => false // this'll let you not-check the owner of obj
            // NOTE 'owner' will ALWAYS be ignored by this, but allows for team/site
            // settings don't need owners yet :)

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        // hard ignored for now
        $ignoreowner = true;
        
        if (!isset($objtypefrom) || empty($objtypefrom)) return false;
        if ($this->objTypeKey($objtypefrom) === -1) return false;
        if (!isset($objtypeto) || empty($objtypeto)) return false;
        if ($this->objTypeKey($objtypeto) === -1) return false;

        #} Check ID 
        $objfromid = (int)$objfromid; 
        $objtoid = (int)$objtoid; if ($objtoid > 0 && $direction != "both") $direction = 'to';

        if (
                (!empty($objfromid) && $objfromid > 0)
                ||
                (!empty($objtoid) && $objtoid > 0)

            ){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query
            $query = "SELECT * FROM ".$ZBSCRM_t['objlinks'];

            #} ============= WHERE ================

                #} Add 
                $wheres['zbsol_objtype_from'] = array('zbsol_objtype_from','=','%s',$objtypefrom);
                $wheres['zbsol_objtype_to'] = array('zbsol_objtype_to','=','%s',$objtypeto);

                // which direction?
                if ($direction == 'from' || $direction == 'both') $wheres['zbsol_objid_from'] = array('zbsol_objid_from','=','%s',$objfromid);
                if ($direction == 'to' || $direction == 'both') $wheres['zbsol_objid_to'] = array('zbsol_objid_to','=','%s',$objtoid);

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','DESC') . $this->buildPaging(0,10000);

            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $potentialRes = $wpdb->get_results($queryObj, OBJECT);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

            #} Interpret results (Result Set - multi-row)
            if (isset($potentialRes) && is_array($potentialRes) && count($potentialRes) > 0) {

                // if count?
                if ($count) return count($potentialRes);

                #} Has results, tidy + return 
                foreach ($potentialRes as $resDataLine) {
                            
                        // tidy
                        $resArr = $this->tidy_objlink($resDataLine);

                        $res[] = $resArr;

                }
            }

            // if count?
            if ($count) return 0;

            return $res;

        } // / if ID

        return false;

    }

    /**
     * returns ID of first obj of link type (to obj)
     * e.g. ID first invoice linked to transaction ID (X) 
     * (useful where transactions:invoices are only ever linked 1:1)
     *
     * @param array $args   Associative array of arguments
     *                      objtypeid, objid
     *
     * @return array result
     */
    public function getFirstIDLinkedToObj($args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            'objtypefrom'       => -1,
            'objtypeto'         => -1,
            'objfromid'         => -1,

            // permissions
            //'ignoreowner'     => false // this'll let you not-check the owner of obj
            // NOTE 'owner' will ALWAYS be ignored by this, but allows for team/site
            // settings don't need owners yet :)

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        // hard ignored for now
        $ignoreowner = true;
        
        if (!isset($objtypefrom) || empty($objtypefrom)) return false;
        if ($this->objTypeKey($objtypefrom) === -1) return false;
        if (!isset($objtypeto) || empty($objtypeto)) return false;
        if ($this->objTypeKey($objtypeto) === -1) return false;

        #} Check ID 
        $objfromid = (int)$objfromid; 

        if (!empty($objfromid) && $objfromid > 0){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query
            $query = "SELECT zbsol_objid_to FROM ".$ZBSCRM_t['objlinks'];

            #} ============= WHERE ================

                #} Add 
                $wheres['zbsol_objtype_from'] = array('zbsol_objtype_from','=','%s',$objtypefrom);
                $wheres['zbsol_objtype_to'] = array('zbsol_objtype_to','=','%s',$objtypeto);
                $wheres['zbsol_objid_from'] = array('zbsol_objid_from','=','%s',$objfromid);
                

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','DESC') . $this->buildPaging(0,1);

            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $potentialRes = $wpdb->get_var($queryObj);
                if ($potentialRes > -1) return (int)$potentialRes;

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

        } // / if ID

        return false;

    }


                            


     /**
     * adds or updates a link object
     * E.G. Contact -> Company
     * this says "match obj X with obj Y" (effectively 'tagging' it)
     * Using this generic format, but as of v2.5+ there's only contact->company links in here
     *
     * @param array $args Associative array of arguments
     *              id (if update - probably never used here), data(objtype,objid,tagid)
     *
     * @return int line ID
     */
    public function addUpdateObjLink($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1,

            // OWNERS will all be set to -1 for objlinks for now :) 
            //'owner'           => -1

            // fields (directly)
            'data'          => array(

                'objtypefrom'       => -1,
                'objtypeto'         => -1,
                'objfromid'         => -1,
                'objtoid'           => -1

            )

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} ========== CHECK FIELDS ============

            // check obtype is completed + legit
            if (!isset($data['objtypefrom']) || empty($data['objtypefrom'])) return false;
            if ($this->objTypeKey($data['objtypefrom']) === -1) return false;
            if (!isset($data['objtypeto']) || empty($data['objtypeto'])) return false;
            if ($this->objTypeKey($data['objtypeto']) === -1) return false;

            // if owner = -1, add current
            // for now, all -1 - not needed yet (makes tags dupe e.g.) if (!isset($owner) || $owner === -1) $owner = zeroBSCRM_user();
            $owner = -1;

            #} check obj ids
            if (empty($data['objfromid']) || $data['objfromid'] < 1 || empty($data['objtoid']) || $data['objtoid'] < 1) return false;

        #} ========= / CHECK FIELDS ===========

        #} Check if ID present
        $id = (int)$id;
        if (!empty($id) && $id > 0){

                #} Check if obj exists (here) - for now just brutal update (will error when doesn't exist)

                #} Attempt update
                if ($wpdb->update( 
                        $ZBSCRM_t['objlinks'], 
                        array( 

                            // ownership
                            // no need to update these (as of yet) - can't move teams etc.
                            //'zbs_site' => zeroBSCRM_installSite(),
                            //'zbs_team' => zeroBSCRM_installTeam(),
                            'zbs_owner' => $owner,

                            // fields
                            'zbsol_objtype_from' => $data['objtypefrom'],
                            'zbsol_objtype_to' => $data['objtypeto'],
                            'zbsol_objid_from' => $data['objfromid'],
                            'zbsol_objid_to' => $data['objtoid']
                        ), 
                        array( // where
                            'ID' => $id
                            ),
                        array( // field data types
                            '%d',
                            '%d', 
                            '%d', 
                            '%d', 
                            '%d'
                        ),
                        array( // where data types
                            '%d'
                            )) !== false){

                            // Successfully updated - Return id
                            return $id;

                        } else {

                            // FAILED update
                            return false;

                        }

        } else {
            
            #} No ID - must be an INSERT
            if ($wpdb->insert( 
                        $ZBSCRM_t['objlinks'], 
                        array( 

                            // ownership
                            'zbs_site' => zeroBSCRM_site(),
                            'zbs_team' => zeroBSCRM_team(),
                            'zbs_owner' => $owner,

                            // fields
                            'zbsol_objtype_from' => $data['objtypefrom'],
                            'zbsol_objtype_to' => $data['objtypeto'],
                            'zbsol_objid_from' => $data['objfromid'],
                            'zbsol_objid_to' => $data['objtoid']
                        ), 
                        array( // field data types
                            '%d',  // site
                            '%d',  // team
                            '%d',  // owner

                            '%d',  
                            '%d', 
                            '%d',  
                            '%d'  
                        ) ) > 0){

                    #} Successfully inserted, lets return new ID
                    $newID = $wpdb->insert_id;
                    return $newID;

                } else {

                    #} Failed to Insert
                    return false;

                }

        }

        return false;

    }

     /**
     * adds or updates object - object link  against an obj
     * this says "match company X,Y,Z with contact Y"
     *
     * @param array $args Associative array of arguments
     *              objtype,objid,tags (array of tagids)
     *
     * @return array $tags
     */
    public function addUpdateObjLinks($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'owner'         => -1,

            'objtypefrom'       => -1,
            'objtypeto'         => -1,
            'objfromid'         => -1,
            'objtoids'          => -1, // array of ID's 

            'mode'          => 'replace' // replace|append|remove

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============


        #} ========== CHECK FIELDS ============

            // check obtype is completed + legit
            if (!isset($objtypefrom) || empty($objtypefrom)) return false;
            if ($this->objTypeKey($objtypefrom) === -1) return false;
            if (!isset($objtypeto) || empty($objtypeto)) return false;
            if ($this->objTypeKey($objtypeto) === -1) return false;

            // if owner = -1, add current
            if (!isset($owner) || $owner === -1) $owner = zeroBSCRM_user();

            // tagging id
            $objfromid = (int)$objfromid; if (empty($objfromid) || $objfromid < 1) return false;

            // to obj list
            if (!is_array($objtoids)) return false;

            // mode
            if (gettype($mode) != 'string' || !in_array($mode, array('replace','append','remove'))) return false;

        #} ========= / CHECK FIELDS ===========

            switch ($mode){

                case 'replace':
        
                    // cull all previous
                    $deleted = $this->deleteObjLinks(array(
                                'objtypefrom'   =>  $objtypefrom, // contact
                                'objtypeto'     =>  $objtypeto, // company
                                'objfromid'     =>  $objfromid)); // where contact id = 

                    // cycle through & add
                    foreach ($objtoids as $objtoid){

                        $added = $this->addUpdateObjLink(array(
                            'data'=>array(
                                'objtypefrom'   =>  $objtypefrom,
                                'objtypeto'     =>  $objtypeto,
                                'objfromid'     =>  $objfromid,
                                'objtoid'       =>  $objtoid,
                                'owner'         =>  $owner
                                )));


                    }

                    break;

                case 'append':

                    // get existing
                    $objLinks = $this->getObjsLinksLinkedToObj(array(
                                'objtypefrom'   =>  $objtypefrom, // contact
                                'objtypeto'     =>  $objtypeto, // company
                                'objfromid'     =>  $objfromid));

                    // make just ids
                    $existingLinkIDs = array(); foreach ($objLinks as $l) $existingLinkIDs[] = $l['id'];

                    // cycle through& add
                    foreach ($objtoids as $objtoid){

                        if (!in_array($objtoid,$existingLinkIDs)){

                            // add a link
                            $this->addUpdateObjLink(array(
                            'data'=>array(
                                'objtypefrom'   =>  $objtypefrom,
                                'objtypeto'     =>  $objtypeto,
                                'objfromid'     =>  $objfromid,
                                'objtoid'       =>  $objtoid,
                                'owner'         =>  $owner
                                )));

                        }

                    }

                    break;

                case 'remove':

                    // cycle through & remove links
                    foreach ($objtoids as $objtoid){

                        // add a link
                        $this->deleteObjLinks(array(
                                'objtypefrom'   =>  $objtypefrom, // contact
                                'objtypeto'     =>  $objtypeto, // company
                                'objfromid'     =>  $objfromid,
                                'objtoid'       =>  $objtoid)); // where contact id = 


                    }

                    break;


            }


        return false;

    }

     /**
     * deletes all object links for a specific obj
     *
     * @param array $args Associative array of arguments
     *              id
     *
     * @return int success;
     */
    public function deleteObjLinks($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'objtypefrom'       => -1,
            'objtypeto'         => -1,
            'objfromid'         => -1,
            'objtoid'           => -1 // only toid/fromid to be set if want to delete all contact->company links

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} ========== CHECK FIELDS ============

            // check obtype is completed + legit
            if (!isset($objtypefrom) || empty($objtypefrom)) return false;
            if ($this->objTypeKey($objtypefrom) === -1) return false;
            if (!isset($objtypeto) || empty($objtypeto)) return false;
            if ($this->objTypeKey($objtypeto) === -1) return false;

            // obj id
            $objfromid = (int)$objfromid; $objtoid = (int)$objtoid; 
            if (
                (empty($objfromid) || $objfromid < 1)
                && 
                (empty($objtoid) || $objtoid < 1)
            ) return false;

            // CHECK PERMISSIONS?

        #} ========= / CHECK FIELDS ===========

            // basics
            $where = array( // where
                        'zbsol_objtype_from' => $objtypefrom,
                        'zbsol_objtype_to' => $objtypeto
                        );

            $whereFormat = array( // where
                        '%d',
                        '%d'
                        );

            // any to add?
            if (!empty($objfromid) && $objfromid > 0){
                $where['zbsol_objid_from'] = $objfromid;
                $whereFormat[] = '%d';
            }
            if (!empty($objtoid) && $objtoid > 0){
                $where['zbsol_objid_to'] = $objtoid;
                $whereFormat[] = '%d';
            }

        // brutal
        return $wpdb->delete( 
                    $ZBSCRM_t['objlinks'], 
                    $where,
                    $whereFormat);

    }
    /**
     * tidy's the object from wp db into clean array
     *
     * @param array $obj (DB obj)
     *
     * @return array (clean obj)
     */
    private function tidy_objlink($obj=false){

            $res = false;

            if (isset($obj->ID)){
            $res = array();
            $res['id'] = $obj->ID;
            /* 
              `zbs_site` INT NULL DEFAULT NULL,
              `zbs_team` INT NULL DEFAULT NULL,
              `zbs_owner` INT NOT NULL,
            */

            $res['objtypefrom'] = $obj->zbsol_objtype_from;
            $res['objtypeto'] = $obj->zbsol_objtype_to;
            $res['objidfrom'] = $obj->zbsol_objid_from;
            $res['objidto'] = $obj->zbsol_objid_to;

        } 

        return $res;


    }

    // ===========   OBJ LINKS   =====================================================
    // ===============================================================================




    // ===============================================================================
    // ===========   SETTINGS   ======================================================

     /**
     * Wrapper, use $this->setting($key) for easy retrieval of singular
     * Simplifies $this->getSetting
     *
     * @param string key
     *
     * @return bool result
     */
    public function setting($key='',$default=false){

        if (!empty($key)){

            return $this->getSetting(array(

                'key' => $key,
                'fullDetails' => false,
                'default' => $default

            ));

        }

        return $default;
    }

     /**
     * Wrapper, use $this->userSetting($key) for easy retrieval of singular setting FOR USER ID
     * Simplifies $this->getSetting
     * Specific for USER settings, this prefixes setting keys with usrset_ID_
     *
     * @param string key
     *
     * @return bool result
     */
    public function userSetting($userID=-1,$key='',$default=false){

        if (!empty($key) && $userID > 0){

            return $this->getSetting(array(

                // old way of doing it'key' => $this->getUserSettingPrefix($userID).$key,
                'key' => $this->userSettingPrefix.$key,
                'fullDetails' => false,
                'default' => $default,

                // this makes it 'per user'
                'ownedBy' => $userID

            ));

        }

        return $default;
    }

    /**
     * returns full setting line +- details
     *
     * @param array $args   Associative array of arguments
     *                      key, fullDetails, default
     *
     * @return array result
     */
    public function getSetting($args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            'key'   => false,
            'default' => false,
            'fullDetails' => false, // set this to 1 and get ID|key|val, rather than just the val

            // permissions - these are currently only used by screenoptions
            'ignoreowner'   => true, // this'll let you not-check the owner of obj
            'ownedBy'   => -1, 

            // returns scalar ID of line
            'onlyID'        => false

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        #} Check key
        if (!empty($key)){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query
            $query = "SELECT * FROM ".$ZBSCRM_t['settings'];

            #} ============= WHERE ================

                #} Add ID
                $wheres['zbsset_key'] = array('zbsset_key','=','%s',$key);

                #} Owned by
                if (!empty($ownedBy) && $ownedBy > 0){
                    
                    // would never hard-type this in (would make generic as in buildWPMetaQueryWhere)
                    // but this is only here until MIGRATED to db2 globally
                    //$wheres['incompany'] = array('ID','IN','(SELECT DISTINCT post_id FROM '.$wpdb->prefix."postmeta WHERE meta_key = 'zbs_company' AND meta_value = %d)",$inCompany);
                    // Use obj links now 
                    $wheres['ownedBy'] = array('zbs_owner','=','%s',$ownedBy);

                }


            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','DESC') . $this->buildPaging(0,1);


            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $potentialRes = $wpdb->get_row($queryObj, OBJECT);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

            #} Interpret Results (ROW)
            if (isset($potentialRes) && isset($potentialRes->ID)) {

                #} Has results, tidy + return 
                
                    #} Only ID? return it directly
                    if ($onlyID === true) return $potentialRes->ID;

                    #} full line or scalar setting val
                    if ($fullDetails)
                        return $this->tidy_setting($potentialRes);
                    else 
                        return $this->tidy_settingSingular($potentialRes);

            }

        } // / if ID

        return $default;

    }


    /**
     * returns all settings as settings arr (later add autoload)
     *
     * @param array $args Associative array of arguments
     *
     * @return array of settings lines
     */
    public function getSettings($args=array()){

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'autoloadOnly' => true,
            'fullDetails' => false, // if true returns inc id etc.

            // permissions
            //'ignoreowner'     => false // this'll let you not-check the owner of obj
            // NOTE 'owner' will ALWAYS be ignored by this, but allows for team/site
            // settings don't need owners yet :)

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        #} ========== CHECK FIELDS ============

            // check obtype is legit
            // autoload?

            $fields = 'ID,zbsset_key,zbsset_val';
            if ($fullDetails) $fields = '*';

            // always ignore owner for now (settings global)
            $ignoreowner = true;
        
        #} ========= / CHECK FIELDS ===========

        global $ZBSCRM_t,$wpdb; 
        $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

        #} Build query
        $query = "SELECT $fields FROM ".$ZBSCRM_t['settings'];

        #} ============= WHERE ================

            #} autoload?

        #} ============ / WHERE ===============

        #} Build out any WHERE clauses
        $wheresArr= $this->buildWheres($wheres,$whereStr,$params);
        $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
        #} / Build WHERE

        #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
        $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
        $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
        #} / Ownership

        #} Append to sql (this also automatically deals with sortby and paging)
        $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','ASC') . $this->buildPaging(0,10000);

        try {

            #} Prep & run query
            $queryObj = $this->prepare($query,$params);
            $potentialRes = $wpdb->get_results($queryObj, OBJECT);

        } catch (Exception $e){

            #} General SQL Err
            $this->catchSQLError($e);

        }

        #} Interpret results (Result Set - multi-row)
        if (isset($potentialRes) && is_array($potentialRes) && count($potentialRes) > 0) {

            #} Has results, tidy + return 
            foreach ($potentialRes as $resDataLine) {

                    // DEBUG echo $resDataLine->zbsset_key.' = ';

                    if ($fullDetails){
                        // tidy
                        $resArr = $this->tidy_setting($resDataLine);
                        $res[$resArr['key']] = $resArr;
                    } else
                        $res[$resDataLine->zbsset_key] = $this->tidy_settingSingular($resDataLine);

            }
        }

        return $res;
    } 

     /**
     * Wrapper, use $this->updateSetting($key,$val) for easy update of setting
     * Uses $this->addUpdateSetting
     *
     * @param string key
     * @param string value
     *
     * @return bool result
     */
    public function updateSetting($key='',$val=''){

        if (!empty($key)){

            return $this->addUpdateSetting(array(

                'data' => array(

                    'key' => $key,
                    'val' => $val
                )

            ));

        }

        return false;
    }

     /**
     * Wrapper, use $this->updateSetting($key,$val) for easy update of setting
     * Uses $this->addUpdateSetting
     *
     * @param string key
     * @param string value
     *
     * @return bool result
     */
    public function updateUserSetting($userID=-1,$key='',$val=''){

        // if -1 passed use current user?

        if (!empty($key) && $userID > 0){

            // because the following addUpdateSetting is dumb to owners (e.g. can't update 'per owner')
            // we must set perOwnerSetting to force 1 setting per key per user (owner)

            return $this->addUpdateSetting(array(

                'owner' => $userID,
                'data' => array(

                    // old way of doing it'key' => $this->getUserSettingPrefix($userID).$key,
                    'key' => $this->userSettingPrefix.$key,
                    'val' => $val,
                ),

                'perOwnerSetting' => true

            ));

        }

        return false;
    }

     /**
     * adds or updates a setting object
     * ... for a quicker wrapper, use $this->updateSetting($key,$val)
     *
     * @param array $args Associative array of arguments
     *              id (not req.), owner (not req.) data -> key/val
     *
     * @return int line ID
     */
    public function addUpdateSetting($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1,
            // NOTE 'owner' will ALWAYS be ignored by this, but allows for team/site
            // meta don't need owners yet :)
            // not anymore! use this for screenoptions, will be ignored unless specifically set
            'owner'         => -1,

            // fields (directly)
            'data'          => array(

                'key'       => '',
                'val'       => '',
                
            ),

            'perOwnerSetting' => false // if set to true this'll make sure only 1 key per 'owner' (potentially multi-key if set incorrectly, so beware)

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============


        #} ========== CHECK FIELDS ============

            $id = (int)$id;

            // if owner = -1, add current
            // Hard -1 for now - settings don't need - if (!isset($owner) || $owner === -1) $owner = zeroBSCRM_user();
            // ... they do now, (screen options) $owner = -1;
            if (isset($owner) && $owner !== -1) $owner = (int)$owner;

            // check key present + legit
            if (!isset($data['key']) || empty($data['key'])) return false;

            // setting ID finder - if obj key provided, check setting not already present (if so overwrite) 
            // keeps unique...  
            if ((empty($id) || $id <= 0)
                && 
                (isset($data['key']) && !empty($data['key']))) {

                // if perOwnerSetting it's 1 key-ed ret per owner, so query bit diff here:
                if (!$perOwnerSetting){

                    // check existence + return ID
                    $potentialID = (int)$this->getSetting(array(
                                    'key'       => $data['key'],
                                    'onlyID'    => true
                                    ));

                } else {

                    // perownedBy

                    // if no owner, return false, cannot be (shouldn't be cos of above)
                    if ($owner <= 0) return false;

                    // check existence + return ID
                    $potentialID = (int)$this->getSetting(array(
                                    'key'       => $data['key'],
                                    'onlyID'    => true,
                                    'ownedBy' => $owner
                                    ));
                }

                // override empty ID 
                if (!empty($potentialID) && $potentialID > 0) $id = $potentialID;

            }


        #} ========= / CHECK FIELDS ===========

        #} Var up any val (json_encode)
        if (in_array(gettype($data['val']),array("object","array"))){

            // WH note: it was necessary to add JSON_UNESCAPED_SLASHES to properly save down without issue
            // combined with a more complex zeroBSCRM_stripSlashes recurrsive
            // https://stackoverflow.com/questions/7282755/how-to-remove-backslash-on-json-encode-function
            $data['val'] = json_encode($data['val'],JSON_UNESCAPED_SLASHES);

        }


        if (isset($id) && !empty($id) && $id > 0){

            //echo 'updating setting id '.$id.'!';

                #} Check if obj exists (here) - for now just brutal update (will error when doesn't exist)

                #} Attempt update
                if ($wpdb->update( 
                        $ZBSCRM_t['settings'], 
                        array( 

                            // ownership
                            // no need to update these (as of yet) - can't move teams etc.
                            //'zbs_site' => zeroBSCRM_installSite(),
                            //'zbs_team' => zeroBSCRM_installTeam(),
                            'zbs_owner' => $owner,

                            // fields
                            'zbsset_key' => $data['key'],
                            'zbsset_val' => $data['val'],
                            'zbsset_lastupdated' => time()
                        ), 
                        array( // where
                            'ID' => $id
                            ),
                        array( // field data types
                            '%d',
                            '%s', 
                            '%s', 
                            '%d'
                        ),
                        array( // where data types
                            '%d'
                            )) !== false){

                            // Successfully updated - Return id
                            return $id;

                        } else {

                            // FAILED update
                            return false;

                        }

        } else {
            
            #} No ID - must be an INSERT
            if ($wpdb->insert( 
                        $ZBSCRM_t['settings'], 
                        array( 

                            // ownership
                            'zbs_site' => zeroBSCRM_site(),
                            'zbs_team' => zeroBSCRM_team(),
                            'zbs_owner' => $owner,

                            // fields
                            'zbsset_key' => $data['key'],
                            'zbsset_val' => $data['val'],
                            'zbsset_created' => time(),
                            'zbsset_lastupdated' => time()
                        ), 
                        array( // field data types
                            '%d',  // site
                            '%d',  // team
                            '%d',  // owner

                            '%s',  
                            '%s',   
                            '%d',  
                            '%d'  
                        ) ) > 0){

                    #} Successfully inserted, lets return new ID
                    $newID = $wpdb->insert_id;
                    return $newID;

                } else {

                    #} Failed to Insert
                    return false;

                }

        }

        return false;

    }

     /**
     * deletes a setting object
     *
     * @param array $args Associative array of arguments
     *              id
     *
     * @return int success;
     */
    public function deleteSetting($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} Check ID & Delete :)
        $id = (int)$id;
        if (!empty($id) && $id > 0) return zeroBSCRM_db2_deleteGeneric($id,'settings');

        return false;

    }

    /**
     * tidy's the object from wp db into clean array
     *
     * @param array $obj (DB obj)
     *
     * @return array (clean obj)
     */
    private function tidy_setting($obj=false){

            $res = false;

            if (isset($obj->ID)){
            $res = array();
            $res['id'] = $obj->ID;
            $res['key'] = $obj->zbsset_key;
            $res['val'] = $this->stripSlashes($obj->zbsset_val);
            $res['created'] = $obj->zbsset_created;
            $res['updated'] = $obj->zbsset_lastupdated;

        } 

        return $res;


    }

    /**
     * tidy's the object from wp db into clean array
     *
     * @param array $obj (DB obj)
     *
     * @return string
     */
    private function tidy_settingSingular($obj=false){

        $res = false;

        if (isset($obj->ID)){

            $val = $this->stripSlashes($this->decodeIfJSON($obj->zbsset_val));

            // catch this oddly non-decoded case
            if ($val == '[]') $val = array();

            return $val;
        }

        return $res;


    }

    // =========== / SETTINGS  =======================================================
    // ===============================================================================







    // ===============================================================================
    // ===========   META ============================================================


     /**
     * Wrapper, use $this->meta($objtype,$objid,$key) for easy retrieval of singular
     * Simplifies $this->getMeta
     *
     * @param int objtype
     * @param int objid
     * @param string key
     *
     * @return bool result
     */
    public function meta($objtype=-1,$objid=-1,$key='',$default=false){

        if (!empty($key)){

            return $this->getMeta(array(

                'objtype' => $objtype,
                'objid' => $objid,
                'key' => $key,
                'fullDetails' => false,
                'default' => $default

            ));

        }

        return $default;
    }
    
    /**
     * returns full meta line +- details
     *
     * @param array $args   Associative array of arguments
     *                      key, fullDetails, default
     *
     * @return array result
     */
    public function getMeta($args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(


            'objid'         => -1, // OBJECT ID - REQ
            'objtype'       => -1, // REQ
            'key'   => false,

            'default' => false,

            'fullDetails' => false, // set this to 1 and get ID|key|val, rather than just the val

            // permissions
            //'ignoreowner'     => false // this'll let you not-check the owner of obj
            // NOTE 'owner' will ALWAYS be ignored by this, but allows for team/site
            // meta don't need owners yet :)

            // returns scalar ID of line
            'onlyID'        => false

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        #} =========== CHECK FIELDS =============

            // check obtype is completed + legit
            if (!isset($objtype) || empty($objtype)) return false;
            if ($this->objTypeKey($objtype) === -1) return false;
            
            // obj id
            $objid = (int)$objid; if (empty($objid) || $objid < 1) return false;

            // for now, meta hard ignores owners
            $ignoreowner = true;

        #} =========== / CHECK FIELDS =============
        
        #} Check key
        if (!empty($key)){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query
            $query = "SELECT * FROM ".$ZBSCRM_t['meta'];

            #} ============= WHERE ================

                #} Add ID
                $wheres['zbsm_objid'] = array('zbsm_objid','=','%d',$objid);
                #} Add OBJTYPE
                $wheres['zbsm_objtype'] = array('zbsm_objtype','=','%d',$objtype);
                #} Add KEY
                $wheres['zbsm_key'] = array('zbsm_key','=','%s',$key);

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','DESC') . $this->buildPaging(0,1);


            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $potentialRes = $wpdb->get_row($queryObj, OBJECT);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

            #} Interpret Results (ROW)
            if (isset($potentialRes) && isset($potentialRes->ID)) {

                #} Has results, tidy + return 
                
                    #} Only ID? return it directly
                    if ($onlyID === true) return $potentialRes->ID;

                    #} full line or scalar setting val
                    if ($fullDetails)
                        return $this->tidy_meta($potentialRes);
                    else 
                        return $this->tidy_metaSingular($potentialRes);

            }

        } // / if ID

        return $default;

    }
    
    /**
     * returns FIRST ID which has matching meta keval pair
     *
     * @param array $args   Associative array of arguments
     *                      key, fullDetails, default
     *
     * @return array result
     */
    public function getIDWithMeta($args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(


            'objtype'       => -1, // REQ
            'key'   => false, // REQ
            'val'   => false, // REQ

            // permissions
            //'ignoreowner'     => false // this'll let you not-check the owner of obj
            // NOTE 'owner' will ALWAYS be ignored by this, but allows for team/site
            // meta don't need owners yet :)

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        #} =========== CHECK FIELDS =============

            // check obtype is completed + legit
            if (!isset($objtype) || empty($objtype)) return false;
            if ($this->objTypeKey($objtype) === -1) return false;
            
            // meta key
            if (empty($key) || empty($val) || $key == false || $val == false) return false;

            // for now, meta hard ignores owners
            $ignoreowner = true;

        #} =========== / CHECK FIELDS =============
        
        #} Check key
        if (!empty($key)){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query
            $query = "SELECT zbsm_objid FROM ".$ZBSCRM_t['meta'];

            #} ============= WHERE ================

                #} Add OBJTYPE
                $wheres['zbsm_objtype'] = array('zbsm_objtype','=','%d',$objtype);
                #} Add KEY
                $wheres['zbsm_key'] = array('zbsm_key','=','%s',$key);
                #} Add VAL
                $wheres['zbsm_val'] = array('zbsm_val','=','%s',$val);

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','DESC') . $this->buildPaging(0,1);


            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $v = (int)$wpdb->get_var($queryObj);
                if ($v > -1) return $v;

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

        } // / if ID

        return -1;

    }

     /**
     * Wrapper, use $this->updateMeta($objtype,$objid,$key,$val) for easy update of setting
     * Uses $this->addUpdateMeta
     * ... USE sub-layer rather than this direct, gives a degree of abstraction
     *
     * @param string key
     * @param string value
     *
     * @return bool result
     */
    public function updateMeta($objtype=-1,$objid=-1,$key='',$val=''){

        if (!empty($key)){ // && !empty($val)

            return $this->addUpdateMeta(array(

                'data' => array(

                    'objid'     => $objid,
                    'objtype'   => $objtype,
                    'key'       => $key,
                    'val'       => $val
                )

            ));

        }

        return false;
    }

     /**
     * adds or updates a setting object
     * ... for a quicker wrapper, use $this->updateMeta($key,$val)
     *
     * @param array $args Associative array of arguments
     *              id (not req.), owner (not req.) data -> key/val
     *
     * @return int line ID
     */
    public function addUpdateMeta($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1,
            // owner HARD disabled for this for now - not req. for each meta
            //'owner'           => -1,

            // fields (directly)
            'data'          => array(

                'objid'         => -1,
                'objtype'       => -1,
                'key'       => '',
                'val'       => '',
                
            )

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} ========== CHECK FIELDS ============

            $id = (int)$id;

            // if owner = -1, add current
            //if (!isset($owner) || $owner === -1) $owner = zeroBSCRM_user();
            // owner HARD disabled for this for now - not req. for each meta
            $owner = -1;

            // check key present + legit
            if (!isset($data['key']) || empty($data['key'])) return false;

            // check obtype is completed + legit
            if (!isset($data['objtype']) || empty($data['objtype'])) return false;
            if ($this->objTypeKey($data['objtype']) === -1) return false;
            
            // obj id
            $objid = (int)$data['objid']; if (empty($objid) || $objid < 1) return false;

            // meta ID finder - if obj key provided, check meta not already present (if so overwrite)   
            // keeps unique...  
            if ((empty($id) || $id <= 0)
                && 
                (isset($data['key']) && !empty($data['key']))
                // no need to check obj id + type here, as will return false above if not legit :)
                ) {

                // check existence + return ID
                $potentialID = (int)$this->getMeta(array(
                                'objid'         => $objid,
                                'objtype'   => $data['objtype'],
                                'key'       => $data['key'],
                                'onlyID'    => true
                                ));


                // override empty ID 
                if (!empty($potentialID) && $potentialID > 0) $id = $potentialID;

            }

        #} ========= / CHECK FIELDS ===========

        #} Var up any val (json_encode)
        if (in_array(gettype($data['val']),array("object","array"))){

            $data['val'] = json_encode($data['val']);

        }


        if (isset($id) && !empty($id) && $id > 0){

                #} Check if obj exists (here) - for now just brutal update (will error when doesn't exist)

                #} Attempt update
                if ($wpdb->update( 
                        $ZBSCRM_t['meta'], 
                        array( 

                            // ownership
                            // no need to update these (as of yet) - can't move teams etc.
                            //'zbs_site' => zeroBSCRM_installSite(),
                            //'zbs_team' => zeroBSCRM_installTeam(),
                            'zbs_owner' => $owner,

                            // fields
                            'zbsm_objtype'  => $data['objtype'],
                            'zbsm_objid'    => $objid,
                            'zbsm_key'      => $data['key'],
                            'zbsm_val'      => $data['val'],
                            'zbsm_lastupdated' => time()
                        ), 
                        array( // where
                            'ID' => $id
                            ),
                        array( // field data types
                            '%d',
                            '%d',
                            '%d',
                            '%s', 
                            '%s', 
                            '%d'
                        ),
                        array( // where data types
                            '%d'
                            )) !== false){

                            // Successfully updated - Return id
                            return $id;

                        } else {

                            // FAILED update
                            return false;

                        }

        } else {
            
            #} No ID - must be an INSERT
            if ($wpdb->insert( 
                        $ZBSCRM_t['meta'], 
                        array( 

                            // ownership
                            'zbs_site' => zeroBSCRM_site(),
                            'zbs_team' => zeroBSCRM_team(),
                            'zbs_owner' => $owner,

                            // fields
                            'zbsm_objtype'  => $data['objtype'],
                            'zbsm_objid'    => $objid,
                            'zbsm_key'      => $data['key'],
                            'zbsm_val'      => $data['val'],
                            'zbsm_created' => time(),
                            'zbsm_lastupdated' => time()
                        ), 
                        array( // field data types
                            '%d',  // site
                            '%d',  // team
                            '%d',  // owner

                            '%d',  
                            '%d',  
                            '%s',  
                            '%s',   
                            '%d',  
                            '%d'  
                        ) ) > 0){

                    #} Successfully inserted, lets return new ID
                    $newID = $wpdb->insert_id;
                    return $newID;

                } else {

                    #} Failed to Insert
                    return false;

                }

        }

        return false;

    }

     /**
     * deletes a meta object based on objid + key
     *
     * @param array $args Associative array of arguments
     *              id
     *
     * @return int success;
     */
    public function deleteMeta($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'objtype'           => -1,
            'objid'             => -1,
            'key'               => ''

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} Check ID, find, & Delete :)
        $objtype = (int)$objtype; if (isset($objtype) && $objtype !== -1 && $this->objTypeKey($objtype) === -1) return false;
        $objid = (int)$objid; if (empty($objid) || $objid < 1) return false;
        if (empty($key)) return false;

        #} FIND?
        $potentialID = (int)$this->getMeta(array(
                        'objid'     => $objid,
                        'objtype'   => $objtype,
                        'key'       => $key,
                        'onlyID'    => true
                        ));

        // override empty ID 
        if (!empty($potentialID) && $potentialID > 0) {

            return $this->deleteMetaByMetaID(array('id'=>$potentialID));

        }

        return false;

    }

     /**
     * deletes a meta object from a meta id
     *
     * @param array $args Associative array of arguments
     *              id
     *
     * @return int success;
     */
    public function deleteMetaByMetaID($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} Check ID & Delete :)
        $id = (int)$id;
        if (!empty($id) && $id > 0) return zeroBSCRM_db2_deleteGeneric($id,'meta');

        return false;

    }

    /**
     * tidy's the object from wp db into clean array
     *
     * @param array $obj (DB obj)
     *
     * @return array (clean obj)
     */
    private function tidy_meta($obj=false){

            $res = false;

            if (isset($obj->ID)){
            $res = array();
            $res['id'] = $obj->ID;
            $res['objtype'] = $obj->zbsm_objtype;
            $res['objid'] = $obj->zbsm_objid;
            $res['key'] = $obj->zbsm_key;
            $res['val'] = $this->stripSlashes($obj->zbsm_val);
            $res['created'] = $obj->zbsm_created;
            $res['updated'] = $obj->zbsm_lastupdated;

        } 

        return $res;


    }

    /**
     * tidy's the object from wp db into clean array
     *
     * @param array $obj (DB obj)
     *
     * @return string
     */
    private function tidy_metaSingular($obj=false){

        $res = false;

        if (isset($obj->ID)) return $this->stripSlashes($this->decodeIfJSON($obj->zbsm_val));

        return $res;


    }

    // =========== / META  ===========================================================
    // ===============================================================================





    // ===============================================================================
    // ===========   TAGS  ===========================================================
    /**
     * returns full tag line +- details
     *
     * @param int id        tag id
     * @param array $args   Associative array of arguments
     *                      withStats
     *
     * @return array result
     */
    public function getTag($id=-1,$args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            // Alternative search criteria to ID :)
            // .. LEAVE blank if using ID
            // objtype + name or slug
            'objtype'       => -1,
            'name'          => '',
            'slug'          => '',

            'withStats'     => false,

            // permissions
            //'ignoreowner'     => false // this'll let you not-check the owner of obj
            // NOTE 'owner' will ALWAYS be ignored by this, but allows for team/site
            // Tags don't need owners yet :)


            // returns scalar ID of line
            'onlyID'        => false

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============
        
        #} ========== CHECK FIELDS ============

            $id = (int)$id;

            // got objtype / name/slug?

            // check obtype is legit (if completed)
            if (isset($objtype) && $objtype !== -1 && $this->objTypeKey($objtype) === -1) {
            
                // if using obj type - check name/slug
                if (empty($name) && empty($slug)) return false;

                // ... else should be good to search

            }
        
            // Tags don't need owners yet :)
            $ignoreowner = true;

        #} ========= / CHECK FIELDS ===========
        
        #} Check ID or name/type
        if (
            (!empty($id) && $id > 0)
            ||
            (!empty($objtype) && !empty($slug))
            ){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query
            $query = "SELECT * FROM ".$ZBSCRM_t['tags'];

            #} ============= WHERE ================

                #} Add ID
                if (!empty($id) && $id > 0) $wheres['ID'] = array('ID','=','%d',$id);

                #} objtype + name/type/slug
                $wheres['zbstag_objtype'] = array('zbstag_objtype','=','%d',$objtype);
                if (!empty($name)) $wheres['zbstag_name'] = array('zbstag_name','=','%s',$name);
                if (!empty($slug)) $wheres['zbstag_slug'] = array('zbstag_slug','=','%s',$slug);

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','DESC') . $this->buildPaging(0,1);

            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $potentialRes = $wpdb->get_row($queryObj, OBJECT);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

            #} Interpret Results (ROW)
            if (isset($potentialRes) && isset($potentialRes->ID)) {

                #} Has results, tidy + return 
                
                    #} Only ID? return it directly
                    if ($onlyID === true) return $potentialRes->ID;
                
                    // tidy
                    $res = $this->tidy_tag($potentialRes);

                    // with stats?
                    if (isset($withStats) && $withStats){

                        // add all stats lines
                        $res['stats'] = $this->getTagStats(array('tagid'=>$potentialRes->ID));
                    
                    }

                    return $res;

            }

        } // / if ID

        return false;

    }

    /**
     * returns tag detail lines
     *
     * @param array $args Associative array of arguments
     *              withStats, searchPhrase, sortByField, sortOrder, page, perPage
     *
     * @return array of tag lines
     */
    public function getAllTags($args=array()){

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'searchPhrase' => '',
            'withStats'     => false,

            'sortByField'   => 'ID',
            'sortOrder'     => 'ASC',
            'page'          => 0,
            'perPage'       => 100,

            // permissions
            //'ignoreowner'     => false // this'll let you not-check the owner of obj
            // NOTE 'owner' will ALWAYS be ignored by this, but allows for team/site
            // Tags don't need owners yet :)

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        // Tags don't need owners yet :)
        $ignoreowner = true;

        global $ZBSCRM_t,$wpdb; 
        $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

        #} Build query
        $query = "SELECT * FROM ".$ZBSCRM_t['tags'];

        #} ============= WHERE ================

            #} Add Search phrase
            if (!empty($searchPhrase)){

                $wheres['zbstag_name'] = array('zbstag_name','LIKE','%s','%'.$searchPhrase.'%');

            }

        #} ============ / WHERE ===============

        #} Build out any WHERE clauses
        $wheresArr= $this->buildWheres($wheres,$whereStr,$params);
        $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
        #} / Build WHERE

        #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
        $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
        $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
        #} / Ownership

        #} Append to sql (this also automatically deals with sortby and paging)
        $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort($sortByField,$sortOrder) . $this->buildPaging($page,$perPage);

        try {

            #} Prep & run query
            $queryObj = $this->prepare($query,$params);
            $potentialRes = $wpdb->get_results($queryObj, OBJECT);

        } catch (Exception $e){

            #} General SQL Err
            $this->catchSQLError($e);

        }

        #} Interpret results (Result Set - multi-row)
        if (isset($potentialRes) && is_array($potentialRes) && count($potentialRes) > 0) {

            #} Has results, tidy + return 
            foreach ($potentialRes as $resDataLine) {
                        
                    // tidy
                    $resArr = $this->tidy_tag($resDataLine);

                    // with stats?
                    if (isset($withStats) && $withStats){

                        // add all stats lines
                        $res['stats'] = $this->getTagStats(array('tagid'=>$resDataLine->ID));
                    
                    }

                    $res[] = $resArr;

            }
        }

        return $res;
    } 

     /**
     * adds or updates a tag object
     *
     * @param array $args Associative array of arguments
     *              id (if update), ???
     *
     * @return int line ID
     */
    public function addUpdateTag($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1,

            // fields (directly)
            'data'          => array(

                'objtype'       => -1,
                'name'          => '',
                'slug'          => '',
                // OWNERS will all be set to -1 for tags for now :) 
                //'owner'           => -1

            )

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} ========== CHECK FIELDS ============

            $id = (int)$id;

            // check obtype is completed + legit
            if (!isset($data['objtype']) || empty($data['objtype'])) return false;
            if ($this->objTypeKey($data['objtype']) === -1) return false;

            // if owner = -1, add current
            // tags don't really need this level of ownership
            // so leaving as -1 for now :) 
            //if (!isset($data['owner']) || $data['owner'] === -1) $data['owner'] = zeroBSCRM_user();
            $data['owner'] = -1;

            // check name present + legit
            if (!isset($data['name']) || empty($data['name'])) return false;
            if (!isset($data['slug']) || empty($data['slug'])) {

                // generate one
                $data['slug'] = $this->makeSlug($data['name']);
            }

            // tag ID finder - if obj name provided, check tag not already present (if so overwrite)    
            // keeps unique...  
            if ((empty($id) || $id <= 0)
                && 
                (
                    (isset($data['name']) && !empty($data['name'])) ||
                    (isset($data['slug']) && !empty($data['slug']))
                )) {

                // check by slug
                // check existence + return ID
                $potentialID = (int)$this->getTag(-1,array(
                                'objtype'   => $data['objtype'],
                                'slug'      => $data['slug'],
                                'onlyID'    => true
                                ));

                // override empty ID 
                if (!empty($potentialID) && $potentialID > 0) $id = $potentialID;

            }

        #} ========= / CHECK FIELDS ===========

        #} Check if ID present
        $id = (int)$id;
        if (!empty($id) && $id > 0){

                #} Check if obj exists (here) - for now just brutal update (will error when doesn't exist)

                #} Attempt update
                if ($wpdb->update( 
                        $ZBSCRM_t['tags'], 
                        array( 

                            // ownership
                            // no need to update these (as of yet) - can't move teams etc.
                            //'zbs_site' => zeroBSCRM_installSite(),
                            //'zbs_team' => zeroBSCRM_installTeam(),
                            'zbs_owner' => $data['owner'],

                            // fields
                            'zbstag_objtype' => $data['objtype'],
                            'zbstag_name' => $data['name'],
                            'zbstag_slug' => $data['slug'],
                            'zbstag_lastupdated' => time()
                        ), 
                        array( // where
                            'ID' => $id
                            ),
                        array( // field data types
                            '%d',
                            '%d',
                            '%s', 
                            '%s', 
                            '%d'
                        ),
                        array( // where data types
                            '%d'
                            )) !== false){

                            // Successfully updated - Return id
                            return $id;

                        } else {

                            // FAILED update
                            return false;

                        }

        } else {
            
            #} No ID - must be an INSERT
            if ($wpdb->insert( 
                        $ZBSCRM_t['tags'], 
                        array( 

                            // ownership
                            'zbs_site' => zeroBSCRM_site(),
                            'zbs_team' => zeroBSCRM_team(),
                            'zbs_owner' => $data['owner'],

                            // fields
                            'zbstag_objtype' => $data['objtype'],
                            'zbstag_name' => $data['name'],
                            'zbstag_slug' => $data['slug'],
                            'zbstag_created' => time(),
                            'zbstag_lastupdated' => time()
                        ), 
                        array( // field data types
                            '%d',  // site
                            '%d',  // team
                            '%d',  // owner

                            '%d',  
                            '%s',  
                            '%s',  
                            '%d',  
                            '%d'  
                        ) ) > 0){

                    #} Successfully inserted, lets return new ID
                    $newID = $wpdb->insert_id;
                    return $newID;

                } else {

                    #} Failed to Insert
                    return false;

                }

        }

        return false;

    }

     /**
     * adds or updates any object's tags
     * ... this is really just a wrapper for addUpdateTagObjLinks
     *
     * @param array $args Associative array of arguments
     *              id (if update), owner, data (array of field data)
     *
     * @return int line ID
     */
    public function addUpdateObjectTags($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'objid'         => -1, // REQ
            'objtype'       => -1, // REQ

            // EITHER of the following:
            'tagIDs'        => -1,
            'tags'          => -1,

            'mode'          => 'replace'

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} ========== CHECK FIELDS ============

            // check id
            $objid = (int)$objid; if (empty($objid) || $objid <= 0) return false;

            // check obtype is legit (if completed)
            if (!isset($objtype) || $objtype == -1 || $this->objTypeKey($objtype) == -1) return false;

        #} ========= / CHECK FIELDS ===========

            #} If using tags, convert these to id's :)
            if ($tags !== -1 && is_array($tags)){

                // overwrite
                $tagIDs = array();

                // cycle through + find
                foreach ($tags as $tag){

                    $tagID = $this->getTag(-1,array(
                        'objtype'       => $objtype,
                        'name'          => $tag,
                        'onlyID' => true
                        ));

                    //echo 'looking for tag "'.$tag.'" got id '.$tagID.'!<br >';

                    if (!empty($tagID)) 
                        $tagIDs[] = $tagID;
                    else {
                        
                        //create
                        $tagID = $this->addUpdateTag(array(
                                                            'data'=>array(
                                                                'objtype'       => $objtype,
                                                                'name'          => $tag))); 
                        //add
                        if (!empty($tagID)) $tagIDs[] = $tagID;

                    }
                }

            }

        return $this->addUpdateTagObjLinks(array(
                'objtype'   =>$objtype,
                'objid'     =>$objid,
                'tagIDs'    =>$tagIDs,
                'mode'      =>$mode));

    }

     /**
     * deletes a tag object
     *
     * @param array $args Associative array of arguments
     *              id
     *
     * @return int success;
     */
    public function deleteTag($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1,
            'deleteLinks'   => true

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} Check ID & Delete :)
        $id = (int)$id;
        if (!empty($id) && $id > 0) {

            $deleted = zeroBSCRM_db2_deleteGeneric($id,'tags');

            // if links, also delete them!
            if ($deleteLinks){

                $deletedLinks = $wpdb->delete( 
                    $ZBSCRM_t['taglinks'], 
                    array( // where
                        'zbstl_tagid' => $id
                        ),
                    array(
                        '%d'
                        )
                    );
            }

            return $deleted;

        }

        return false;

    }

     /**
     * retrieves stats for tag (how many contacts/obj's use this tag) (effectively counts tag links split per obj)
     *
     * @param array $args Associative array of arguments
     *              id
     *
     * @return array
     */
    public function getAllTagStats($args=array()){

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1,
            'owner'         => -1,

            // permissions
            //'ignoreowner'     => false // this'll let you not-check the owner of obj
            // NOTE 'owner' will ALWAYS be ignored by this, but allows for team/site
            // Tags don't need owners yet :)

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============
        
        $ignoreowner = true; 

        #} Check ID
        $id = (int)$id;
        if (!empty($id) && $id > 0){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query
            $query = "SELECT COUNT(zbstl_objid) c, zbstl_objtype FROM ".$ZBSCRM_t['taglinks'];

            #} ============= WHERE ================

                #} Add ID
                $wheres['zbstl_tagid'] = array('zbstl_tagid','=','%d',$id);


                #} If 'owner' is set then have to ignore owner, because can't do both
                if (isset($owner) && $owner > 0) {
                    
                    // stops ownership check
                    $ignoreowner = true;

                    // adds owner to query
                    $wheres['zbs_owner'] = array('zbs_owner','=','%d',$owner);

                }

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} ============ CUSTOM GROUP/ORDERBY ==============
            
                // this allows grouping :) 
                $orderByCustom = ' GROUP BY zbstl_objtype ORDER BY c ASC';

            #} ============ / CUSTOM GROUP/ORDERBY ============

            #} Append to sql (and use our custom order by etc.)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $orderByCustom;

            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $potentialRes = $wpdb->get_results($queryObj, OBJECT);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

            #} Interpret results (Result Set - multi-row)
            if (isset($potentialRes) && is_array($potentialRes) && count($potentialRes) > 0) {

                #} Has results, tidy + return 
                foreach ($potentialRes as $resDataLine) {
                            
                        // tidy
                        $res[] = $this->tidy_tagstat($resDataLine);

                }
            }

            return $res;

        } // / if ID

        return false;

    }

     /**
     * retrieves stats for tag (how many contacts/obj's use this tag) 
     * this version returns specific count of uses for an objtypeid
     *
     * @param array $args Associative array of arguments
     *              id
     *
     * @return array
     */
    public function getTagObjStats($args=array()){

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1,
            'objtypeid'     => -1,
            'owner'         => -1,

            // permissions
            //'ignoreowner'     => false // this'll let you not-check the owner of obj
            // NOTE 'owner' will ALWAYS be ignored by this, but allows for team/site
            // Tags don't need owners yet :)

            // returns scalar ID of line
            'onlyID'        => false

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============
        
        $ignoreowner = true; 

        #} Check ID
        $id = (int)$id;
        if (!empty($id) && $id > 0){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query
            $query = "SELECT COUNT(zbstl_objid) c, zbstl_objtype FROM ".$ZBSCRM_t['taglinks'];

            #} ============= WHERE ================

                #} Add ID
                $wheres['zbstl_tagid'] = array('zbstl_tagid','=','%d',$id);

                #} Adds a specific type id
                if (!empty($objtypeid)){

                    $wheres['zbstl_objtype'] = array('zbstl_objtype','=','%d',$objtypeid);

                }


                #} If 'owner' is set then have to ignore owner, because can't do both
                if (isset($owner) && $owner > 0) {
                    
                    // stops ownership check
                    $ignoreowner = true;

                    // adds owner to query
                    $wheres['zbs_owner'] = array('zbs_owner','=','%d',$owner);

                }

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} ============ CUSTOM GROUP/ORDERBY ==============
            
                // this allows grouping :) 
                $orderByCustom = ' GROUP BY zbstl_objtype ORDER BY c ASC LIMIT 0,1';

            #} ============ / CUSTOM GROUP/ORDERBY ============

            #} Append to sql (and use our custom order by etc.)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $orderByCustom;

            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $potentialRes = $wpdb->get_row($queryObj, OBJECT);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

            #} Interpret Results (ROW)
            if (isset($potentialRes) && isset($potentialRes->ID)) {
            
                #} Only ID? return it directly
                if ($onlyID === true) return $potentialRes->ID;

                #} Has results, tidy + return 
                return $this->tidy_tagstat($potentialRes);

            }

        } // / if ID

        return false;

    }

    /**
     * tidy's the object from wp db into clean array
     *
     * @param array $obj (DB obj)
     *
     * @return array (clean obj)
     */
    private function tidy_tag($obj=false){

            $res = false;

            if (isset($obj->ID)){
            $res = array();
            $res['id'] = $obj->ID;
            /* 
              `zbs_site` INT NULL DEFAULT NULL,
              `zbs_team` INT NULL DEFAULT NULL,
              `zbs_owner` INT NOT NULL,
            */

            $res['objtype'] = $obj->zbstag_objtype;
            $res['name'] = $this->stripSlashes($obj->zbstag_name);
            $res['slug'] = $obj->zbstag_slug;


            $res['created'] = $obj->zbstag_created;
            $res['lastupdated'] = $obj->zbstag_lastupdated;

        } 

        return $res;


    }

    /**
     * tidy's the object from wp db into clean array
     *
     * @param array $obj (DB obj)
     *
     * @return array (clean obj)
     */
    private function tidy_tagstat($obj=false){

            $res = false;

            if (isset($obj->ID)){
            $res = array();
            $res['count'] = $obj->c;
            $res['objtypeid'] = $obj->zbstl_objtype;
            $res['objtype'] = $this->objTypeKey($obj->zbstl_objtype);

        } 

        return $res;


    }

    // =========== / TAGS      =======================================================
    // ===============================================================================





    // ===============================================================================
    // ===========   TAG LINKS  =======================================================
    /**
     * returns tags against an obj type (e.g. contact tags)
     *
     * @param array $args   Associative array of arguments
     *                      objtypeid
     *
     * @return array result
     */
    public function getTagsForObjType($args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            'objtypeid'     => -1,

            // select
            'excludeEmpty'  => -1,
            'excludeIDs' => -1, // if is an array of tag id's will exclude these :)

            // with
            'withCount'     => -1,
            
            // sort
            'sortByField'   => 'zbstag_name',
            'sortOrder'     => 'ASC',

            'page'          => 0, // this is what page it is (gets * by for limit)
            'perPage'       => 10000

            // permissions
            //'ignoreowner'     => false // this'll let you not-check the owner of obj
            // NOTE 'owner' will ALWAYS be ignored by this, but allows for team/site
            // Tags don't need owners yet :)

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============
        
        $ignoreowner = true;

        #} Check ID
        $objtypeid = (int)$objtypeid;
        if (!empty($objtypeid) && $objtypeid > 0){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();


            #} ============ EXTRA SELECT ==============

                $extraSelect = '';

                if ($withCount !== -1 || $excludeEmpty !== -1) {

                    // could make this distinct zbstl_objid if need more precision
                    // NOTE! Ownership leak here - this'll count GLOBALLY! todo: add ownership into this subquery
                    $extraSelect = ',(SELECT COUNT(taglink.ID) FROM '.$ZBSCRM_t['taglinks'].' taglink WHERE zbstl_tagid = tags.ID AND zbstl_objtype = %d) tagcount';
                    $params[] = $objtypeid;

                }

            #} ============ / EXTRA SELECT ==============

            #} Build query
            $query = "SELECT tags.*".$extraSelect." FROM ".$ZBSCRM_t['tags'].' tags';

            #} ============= WHERE ================
                
                // type id
                $wheres['zbstag_objtype'] = array('zbstag_objtype','=','%d',$objtypeid);

                // if exclude empty
                if ($excludeEmpty){
                    $wheres['direct'][] = array('(SELECT COUNT(taglink.ID) FROM '.$ZBSCRM_t['taglinks'].' taglink WHERE zbstl_tagid = tags.ID AND zbstl_objtype = %d) > 0',array($objtypeid));

                }

                if (is_array($excludeIDs)){

                    $checkedExcludedIDs = array();
                    foreach ($excludeIDs as $potentialID){
                        $pID = (int)$potentialID;
                        if ($pID > 0 && !in_array($pID, $checkedExcludedIDs)) $checkedExcludedIDs[] = $pID;
                    }

                    if (count($checkedExcludedIDs) > 0){

                        // add exclude ids query part (okay to directly inject here, as validated ints above.)
                        $wheres['excludedids'] = array('ID','NOT IN','('.implode(',', $checkedExcludedIDs).')');

                    }
                }

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort($sortByField,$sortOrder) . $this->buildPaging($page,$perPage);

            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $potentialRes = $wpdb->get_results($queryObj, OBJECT);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

            #} Interpret results (Result Set - multi-row)
            if (isset($potentialRes) && is_array($potentialRes) && count($potentialRes) > 0) {

                #} Has results, tidy + return 
                foreach ($potentialRes as $resDataLine) {
                            
                        // tidy
                        $resArr = $this->tidy_tag($resDataLine);

                        if ($withCount !== -1){

                            if (isset($resDataLine->tagcount))
                            	$resArr['count'] = $resDataLine->tagcount;
                            else
                            	$resArr['count'] = -1;

                        }

                        $res[] = $resArr;

                }
            }

            return $res;

        } // / if ID

        return false;

    }
    /**
     * returns tags against an obj (e.g. contact id 101)
     *
     * @param array $args   Associative array of arguments
     *                      objtypeid, objid
     *
     * @return array result
     */
    public function getTagsForObjID($args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            'objtypeid'     => -1,
            'objid'         => -1,

            // with
            'withCount'     => -1,
            'onlyID'        => -1,

            // permissions
            //'ignoreowner'     => false // this'll let you not-check the owner of obj
            // NOTE 'owner' will ALWAYS be ignored by this, but allows for team/site
            // Tags don't need owners yet :)

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============
        
        $ignoreowner = true;
        
        #} Check ID
        $objtypeid = (int)$objtypeid; $objid = (int)$objid; 
        if (!empty($objtypeid) && $objtypeid > 0 && !empty($objid) && $objid > 0){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query
            $query = "SELECT * FROM ".$ZBSCRM_t['tags'];

            #} ============= WHERE ================

                #} Add ID
                // rather than using the $wheres, here we have to manually add, because sub queries don't work otherwise.
                $whereStr = ' WHERE ID in (SELECT zbstl_tagid FROM '.$ZBSCRM_t['taglinks'].' WHERE zbstl_objtype = %d AND zbstl_objid = %d)';
                $params[] = $objtypeid; $params[] = $objid;

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','DESC') . $this->buildPaging(0,10000);
            //echo $query; print_r($params);

            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $potentialRes = $wpdb->get_results($queryObj, OBJECT);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

            #} Interpret results (Result Set - multi-row)
            if (isset($potentialRes) && is_array($potentialRes) && count($potentialRes) > 0) {

                #} Has results, tidy + return 
                foreach ($potentialRes as $resDataLine) {
                            

                        #} Only ID? return it directly
                        if ($onlyID === true) 
                            $resObj = $resDataLine->ID;
                        else
                            // tidy
                            $resObj = $this->tidy_tag($resDataLine);

                        if ($withCount){


                        }

                        $res[] = $resObj;

                }
            }

            return $res;

        } // / if ID

        return false;

    }


     /**
     * adds or updates a tag link object
     * this says "match tag X with obj Y" (effectively 'tagging' it)
     * NOTE: DO NOT CALL DIRECTLY, ALWAYS use addUpdateTagObjLinks (or it's wrappers) - because those fire actions :)
     *
     * @param array $args Associative array of arguments
     *              id (if update - probably never used here), data(objtype,objid,tagid)
     *
     * @return int line ID
     */
    public function addUpdateTagObjLink($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1,
            'owner'         => -1,

            // fields (directly)
            'data'          => array(

                'objtype'       => -1,
                'objid'         => -1,
                'tagid'         => -1

            )

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} ========== CHECK FIELDS ============

            // check obtype is completed + legit
            if (!isset($data['objtype']) || empty($data['objtype'])) return false;
            if ($this->objTypeKey($data['objtype']) === -1) return false;

            // if owner = -1, add current
            if (!isset($owner) || $owner === -1) $owner = zeroBSCRM_user();

            $objid = (int)$data['objid']; $tagid = (int)$data['tagid'];
            if (empty($data['objid']) || $data['objid'] < 1 || empty($data['tagid']) || $data['tagid'] < 1) return false;

        #} ========= / CHECK FIELDS ===========

        #} Check if ID present
        $id = (int)$id;
        if (!empty($id) && $id > 0){

                #} Check if obj exists (here) - for now just brutal update (will error when doesn't exist)

                #} Attempt update
                if ($wpdb->update( 
                        $ZBSCRM_t['taglinks'], 
                        array( 

                            // ownership
                            // no need to update these (as of yet) - can't move teams etc.
                            //'zbs_site' => zeroBSCRM_installSite(),
                            //'zbs_team' => zeroBSCRM_installTeam(),
                            'zbs_owner' => $owner,

                            // fields
                            'zbstl_objtype' => $data['objtype'],
                            'zbstl_objid' => $data['objid'],
                            'zbstl_tagid' => $data['tagid']
                        ), 
                        array( // where
                            'ID' => $id
                            ),
                        array( // field data types
                            '%d',
                            '%d', 
                            '%d', 
                            '%d'
                        ),
                        array( // where data types
                            '%d'
                            )) !== false){

                            // Successfully updated - Return id
                            return $id;

                        } else {

                            // FAILED update
                            return false;

                        }

        } else {
            
            #} No ID - must be an INSERT
            if ($wpdb->insert( 
                        $ZBSCRM_t['taglinks'], 
                        array( 

                            // ownership
                            'zbs_site' => zeroBSCRM_site(),
                            'zbs_team' => zeroBSCRM_team(),
                            'zbs_owner' => $owner,

                            // fields
                            'zbstl_objtype' => $data['objtype'],
                            'zbstl_objid' => $data['objid'],
                            'zbstl_tagid' => $data['tagid']
                        ), 
                        array( // field data types
                            '%d',  // site
                            '%d',  // team
                            '%d',  // owner

                            '%d',  
                            '%d',  
                            '%d'  
                        ) ) > 0){

                    #} Successfully inserted, lets return new ID
                    $newID = $wpdb->insert_id;
                    return $newID;

                } else {

                    #} Failed to Insert
                    return false;

                }

        }

        return false;

    }


     /**
     * adds or updates tag link objects against an obj
     * this says "match tag X,Y,Z with obj Y" (effectively 'tagging' it)
     *
     * @param array $args Associative array of arguments
     *              objtype,objid,tags (array of tagids)
     *
     * @return array $tags
     */
    public function addUpdateTagObjLinks($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'owner'         => -1,

            'objtype'       => -1,
            'objid'         => -1,
            'tagIDs'        => -1, // array of tag ID's 

            'mode'          => 'replace' // replace|append|remove

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============


        #} ========== CHECK FIELDS ============

            // check obtype is completed + legit
            if (!isset($objtype) || empty($objtype)) return false;
            if ($this->objTypeKey($objtype) === -1) return false;

            // if owner = -1, add current
            if (!isset($owner) || $owner === -1) $owner = zeroBSCRM_user();

            // tagging id
            $objid = (int)$objid; if (empty($objid) || $objid < 1) return false;

            // tag list
            if (!is_array($tagIDs)) return false;

            // mode
            if (gettype($mode) != 'string' || !in_array($mode, array('replace','append','remove'))) return false;

        #} ========= / CHECK FIELDS ===========

            switch ($mode){

                case 'replace':

					// (for actions) log starting objs
					$existingTagIDs = $this->getTagsForObjID(array('objtypeid'=>$objtype,'objid'=>$objid,'onlyID'=>true));
					if (!is_array($existingTagIDs)) $existingTagIDs = array();
					$removedTagsByID = array(); $addedTagsByID = array();
		
					// cull all previous
					$deleted = $this->deleteTagObjLinks(array('objid'=>$objid,'objtype'=>$objtype));

					// cycle through & add
					foreach ($tagIDs as $tid){

						$added = $this->addUpdateTagObjLink(array(
							'data'=>array(
								'objid'		=>	$objid,
								'objtype'	=>	$objtype,
								'tagid' 	=> 	$tid)));

						if ($added !== false){
							
							if (!in_array($tid, $existingTagIDs)) 
								$addedTagsByID[] = $tid; // tag was added
							//else 
								// tag was already in there, just re-added
						}

					}

					// actions

						// check removed
						 foreach ($existingTagIDs as $tid){

						 	if (!in_array($tid, $tagIDs)) $removedTagsByID[] = $tid;

						 }

						// fire actions for each tag

						 	// added to
							if (count($addedTagsByID) > 0) foreach ($addedTagsByID as $tagID) do_action('zbs_tag_added_to_objid',$tagID, $objtype, $objid);

							// removed from
							if (count($removedTagsByID) > 0) foreach ($removedTagsByID as $tagID) do_action('zbs_tag_removed_from_objid',$tagID, $objtype, $objid);


					// return
					return true;

					break;

                case 'append':

					// get existing
					$existingTagIDs = $this->getTagsForObjID(array('objtypeid'=>$objtype,'objid'=>$objid,'onlyID'=>true));

					// make just ids
					// no need, added ,'onlyID'=>true above
					//$existingTagIDs = array(); foreach ($tags as $t) $existingTagIDs[] = $t['id'];

					// cycle through& add
					foreach ($tagIDs as $tid){

						if (!in_array($tid,$existingTagIDs)){

							// add a link
							$this->addUpdateTagObjLink(array(
							'data'=>array(
								'objid'		=>	$objid,
								'objtype'	=>	$objtype,
								'tagid' 	=> 	$tid)));

							// fire action
							do_action('zbs_tag_added_to_objid',$tid, $objtype, $objid);

						}

					}
					return true;

					break;

                case 'remove':

					// get existing
					$existingTagIDs = $this->getTagsForObjID(array('objtypeid'=>$objtype,'objid'=>$objid,'onlyID'=>true));

					// cycle through & remove links
					foreach ($tagIDs as $tid){

						if (in_array($tid, $existingTagIDs)){

							// delete link
							$this->deleteTagObjLink(array(
								'objid'		=>	$objid,
								'objtype'	=>	$objtype,
								'tagid' 	=> 	$tid));

							// action
							do_action('zbs_tag_removed_from_objid',$tid, $objtype, $objid);

						}


					}

					return true;

                    break;


            }


        return false;

    }

     /**
     * deletes a tag object link
     *
     * @param array $args Associative array of arguments
     *              id
     *
     * @return int success;
     */
    public function deleteTagObjLink($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1,

            // or...

            'objtype'       => -1,
            'objid'         => -1,
            'tagid'         => -1


        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} Check ID & Delete :) (IF ID PRESENT)
        $id = (int)$id;
        if (!empty($id) && $id > 0) return zeroBSCRM_db2_deleteGeneric($id,'taglinks');

        #} ... else delete by objtype etc.

        #} ========== CHECK FIELDS ============

            // check obtype is completed + legit
            if (!isset($objtype) || empty($objtype)) return false;
            if ($this->objTypeKey($objtype) === -1) return false;
            
            // obj id
            $objid = (int)$objid; if (empty($objid) || $objid < 1) return false;

            // tag id
            $tagid = (int)$tagid; if (empty($tagid) || $tagid < 1) return false;

            // CHECK PERMISSIONS?

        #} ========= / CHECK FIELDS ===========

            #} ... if here then is trying to delete specific tag linkid
            return $wpdb->delete( 
                        $ZBSCRM_t['taglinks'], 
                        array( // where
                            'zbstl_objtype' => $objtype,
                            'zbstl_objid' => $objid,
                            'zbstl_tagid' => $tagid
                            ),
                        array(
                            '%d',
                            '%d',
                            '%d'
                            )
                        );

    }

     /**
     * deletes all tag object links for a specific obj
     *
     * @param array $args Associative array of arguments
     *              id
     *
     * @return int success;
     */
    public function deleteTagObjLinks($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'objtype'       => -1,
            'objid'         => -1,

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} ========== CHECK FIELDS ============

            // check obtype is completed + legit
            if (!isset($objtype) || empty($objtype)) return false;
            if ($this->objTypeKey($objtype) === -1) return false;
            
            // obj id
            $objid = (int)$objid; if (empty($objid) || $objid < 1) return false;

            // CHECK PERMISSIONS?

        #} ========= / CHECK FIELDS ===========

        // brutal
        return $wpdb->delete( 
                    $ZBSCRM_t['taglinks'], 
                    array( // where
                        'zbstl_objtype' => $objtype,
                        'zbstl_objid' => $objid
                        ),
                    array(
                        '%d',
                        '%d'
                        )
                    );

    }


    /**
     * tidy's the object from wp db into clean array
     *
     * @param array $obj (DB obj)
     *
     * @return array (clean obj)
     */
    private function tidy_taglink($obj=false){

            $res = false;

            if (isset($obj->ID)){
            $res = array();
            $res['id'] = $obj->ID;
            /* 
              `zbs_site` INT NULL DEFAULT NULL,
              `zbs_team` INT NULL DEFAULT NULL,
              `zbs_owner` INT NOT NULL,
            */

            $res['objtype'] = $obj->zbstag_objtype;
            $res['name'] = $this->stripSlashes($obj->zbstag_name);
            $res['slug'] = $obj->zbstag_slug;


            $res['created'] = $obj->zbstag_created;
            $res['lastupdated'] = $obj->zbstag_lastupdated;

        } 

        return $res;


    }

    // =========== / TAG LINKS      ==================================================
    // ===============================================================================









    // ===============================================================================
    // ===========   CUSTOM FIELDS   =================================================

    /**
     * returns true if field key exists as custom field for CONTACT
     *
     * @param array $args Associative array of arguments
     *              objtypeid
     *
     * @return array of customfield field keys
     */
    public function isActiveCustomField_Contact($customFieldKey=''){

        #} These are simply stored in settings with a key of customfields_objtype e.g. customfields_contact
        if (!empty($objtypeid) && $objtypeid > 0 && !empty($customFieldKey)) {

            // get custom fields
            $customFields = $this->getActiveCustomFields(array('objtypeid'=>ZBS_TYPE_CONTACT));

            // validate there
            if (is_array($customFields)) foreach ($customFields as $cfK => $cfV){

                if ($cfK == $customFieldKey) return true;
            }

        }

        return false;
    } 

    /**
     * returns true if field key exists as custom field for obj
     *
     * @param array $args Associative array of arguments
     *              objtypeid
     *
     * @return array of customfield field keys
     */
    public function isActiveCustomField($args=array()){

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'objtypeid' => -1,
            'customFieldKey' => ''

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        #} These are simply stored in settings with a key of customfields_objtype e.g. customfields_contact
        if (!empty($objtypeid) && $objtypeid > 0 && !empty($customFieldKey)) {

            // get custom fields
            $customFields = $this->getActiveCustomFields(array('objtypeid'=>$objtypeid));

            // validate there
            if (is_array($customFields)) foreach ($customFields as $cfK => $cfV){

                if ($cfK == $customFieldKey) return true;
            }

        }

        return false;
    } 



    /**
     * returns active custom field keys for an obj type
     *
     * @param array $args Associative array of arguments
     *              objtypeid
     *
     * @return array of customfield field keys
     */
    public function getActiveCustomFields($args=array()){

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'objtypeid' => -1,

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        #} These are simply stored in settings with a key of customfields_objtype e.g. customfields_contact
        if (!empty($objtypeid) && $objtypeid > 0) {

            return $this->setting('customfields_'.$this->objTypeKey($objtypeid),array());

        }

        return array();
    } 

    /**
     * updates active custom field keys for an obj type
     * No checking whatsoever
     *
     * @param array $args Associative array of arguments
     *              objtypeid
     *
     * @return array of customfield field keys
     */
    public function updateActiveCustomFields($args=array()){

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'objtypeid' => -1,
            'fields' => array()

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        #} These are simply stored in settings with a key of customfields_objtype e.g. customfields_contact
        if (!empty($objtypeid) && $objtypeid > 0) {

            return $this->updateSetting('customfields_'.$this->objTypeKey($objtypeid),$fields);

        }

        return array();
    } 


    /**
     * returns scalar value of 1 custom field line (or it's ID)
     * ... real custom fields will be got as part of getCustomers more commonly (this is for 1 alone)
     *
     * @param array $args   Associative array of arguments
     *                      objtypeid,objid,objkey
     *
     * @return array result
     */
    public function getCustomFieldVal($args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            'objtypeid'     => -1, // e.g. 1 = contact
            'objid'         => -1, // e.g. contact #101
            'objkey'        => '', // e.g. notes

            // permissions
            'ignoreowner'   => false, // this'll let you not-check the owner of obj

            // returns scalar ID of line
            'onlyID'        => false

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============
        
        #} Check IDs
        $objtypeid = (int)$objtypeid; $objid = (int)$objid;
        if (!empty($objtypeid) && $objtypeid > 0 && !empty($objid) && $objid > 0 && !empty($objkey)){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query
            $query = "SELECT ID,zbscf_objval FROM ".$ZBSCRM_t['customfields'];

            #} ============= WHERE ================

                #} Add obj type
                $wheres['zbscf_objtype'] = array('zbscf_objtype','=','%d',$objtypeid);

                #} Add obj ID
                $wheres['zbscf_objid'] = array('zbscf_objid','=','%d',$objid);

                #} Add obj key
                $wheres['zbscf_objkey'] = array('zbscf_objkey','=','%s',$objkey);

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','DESC') . $this->buildPaging(0,1);

            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $potentialRes = $wpdb->get_row($queryObj, OBJECT);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

            #} Interpret Results (ROW)
            if (isset($potentialRes) && isset($potentialRes->ID)) {

                #} Has results, tidy + return 
                
                    #} Only ID? return it directly
                    if ($onlyID === true)  return $potentialRes->ID;

                    // tidy
                    $res = $this->tidy_customfieldvalSingular($potentialRes);

                    return $res;

            }

        } // / if ID

        return false;

    }

     /**
     * adds or updates a customfield object
     * NOTE: because these are specific to unique ID of obj, there's no need for site/team etc. here
     *
     * @param array $args Associative array of arguments
     *              id (if update), owner, data (array of field data)
     *
     * @return int line ID
     */
    public function addUpdateCustomField($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1, // Custom field line ID (not obj id!)
            'owner'         => -1,

            // fields (directly)
            'data'          => array(
                'objtype' => -1,
                'objid' => -1,
                'objkey' => '',
                'objval' => 'NULL'
            )

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============


        #} ========== CHECK FIELDS ============


            $id = (int)$id;

            if (isset($data['objid'])) $data['objid'] = (int)$data['objid'];
            if (isset($data['objtype'])) $data['objtype'] = (int)$data['objtype'];

            // check obtype is completed + legit
            if (!isset($data['objtype']) || empty($data['objtype'])) return false;
            if ($this->objTypeKey($data['objtype']) === -1) return false;

            // check key + ID present
            if (!isset($data['objkey']) || empty($data['objkey'])) return false;
            if (!isset($data['objid']) || $data['objid'] <= 0) return false;

            // if owner = -1, add current
            if (!isset($owner) || $owner === -1) $owner = zeroBSCRM_user();

            // ID finder - if obj id +  key + val + typeid provided, check CF not already present (if so overwrite)     
            if ((empty($id) || $id <= 0)
                && 
                (isset($data['objtype']) && !empty($data['objtype']))
                && 
                (isset($data['objid']) && !empty($data['objid']))
                &&
                (isset($data['objkey']) && !empty($data['objkey']))) {

                // check existence + return ID
                $potentialID = (int)$this->getCustomFieldVal(array(
                                'objtypeid'     => $data['objtype'],
                                'objid'         => $data['objid'],
                                'objkey'        => $data['objkey'],
                                'onlyID'        => true,
                                'ignoreowner'   => true
                                ));
                // override empty ID 
                if (!empty($potentialID) && $potentialID > 0) $id = $potentialID;

            }

        #} ========= / CHECK FIELDS ===========

        #} Check if ID present
        if (!empty($id) && $id > 0){

                #} Check if obj exists (here) - for now just brutal update (will error when doesn't exist)

                #} Attempt update
                if ($wpdb->update( 
                        $ZBSCRM_t['customfields'], 
                        array( 

                            // ownership
                            // no need to update these (as of yet) - can't move teams etc.
                            //'zbs_site' => zeroBSCRM_installSite(),
                            //'zbs_team' => zeroBSCRM_installTeam(),
                            'zbs_owner' => $owner,

                            // fields
                            'zbscf_objtype' => $data['objtype'],
                            'zbscf_objid' => $data['objid'],
                            'zbscf_objkey' => $data['objkey'],
                            'zbscf_objval' => $data['objval'],

                            //'zbscf_created' => time(),
                            'zbscf_lastupdated' => time()
                        ), 
                        array( // where
                            'ID' => $id
                            ),
                        array( // field data types
                            //'%d',  // site
                            //'%d',  // team
                            '%d',  // owner

                            '%d',  
                            '%d',  
                            '%s', 
                            '%s',
 
                            '%d' // last updated
                        ),
                        array( // where data types
                            '%d'
                            )) !== false){

                            // Successfully updated - Return id
                            return $id;

                        } else {

                            // FAILED update
                            return false;

                        }

        } else {
            
            #} No ID - must be an INSERT
            if ($wpdb->insert( 
                        $ZBSCRM_t['customfields'], 
                        array( 

                            // ownership
                            'zbs_site' => zeroBSCRM_site(),
                            'zbs_team' => zeroBSCRM_team(),
                            'zbs_owner' => $owner,

                            // fields
                            'zbscf_objtype' => $data['objtype'],
                            'zbscf_objid' => $data['objid'],
                            'zbscf_objkey' => $data['objkey'],
                            'zbscf_objval' => $data['objval'],

                            'zbscf_created' => time(),
                            'zbscf_lastupdated' => time()
                        ), 
                        array( // field data types
                            '%d',  // site
                            '%d',  // team
                            '%d',  // owner

                            '%d',  
                            '%d',  
                            '%s', 
                            '%s',

                            '%d',  
                            '%d'  
                        ) ) > 0){

                    #} Successfully inserted, lets return new ID
                    $newID = $wpdb->insert_id;
                    return $newID;

                } else {

                    #} Failed to Insert
                    return false;

                }

        }

        return false;

    }

     /**
     * deletes a customfield object
     *
     * @param array $args Associative array of arguments
     *              id
     *
     * @return int success;
     */
    public function deleteCustomField($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} Check ID & Delete :)
        $id = (int)$id;
        if (!empty($id) && $id > 0) return zeroBSCRM_db2_deleteGeneric($id,'customfields');

        return false;

    }

    /**
     * tidy's the object from wp db into clean array
     *
     * @param array $obj (DB obj)
     *
     * @return array (clean obj)
     */
    private function tidy_customfieldvalSingular($obj=false){

        $res = false;

        if (isset($obj->ID)){

            // just return the value here!
            $res = $this->stripSlashes($obj->zbscf_objval);

        }

        return $res;


    }


    // =========== / CUSTOM FIELDS     ===============================================
    // ===============================================================================


    // ===============================================================================
    // ===========   EXTERNAL SOURCES  ===============================================
    /**
     * returns first external source line +- details
     *
     * @param int id        tag id
     * @param array $args   Associative array of arguments
     *                      withStats
     *
     * @return array result
     */
    public function getExternalSource($id=-1,$args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            // Alternative search criteria to ID :)
            // .. LEAVE blank if using ID
            //'contactID'         => -1, // NOTE: This only returns the FIRST source, if using multiple sources, use getExternalSourcesForContact
            'objectID'         => -1, 
            'objectType'       => -1, 
            'source'            => -1, // OPTIONAL, if used with contact ID will return 1 line :D

            // permissions
            'ignoreowner'   => false, // this'll let you not-check the owner of obj

            // returns scalar ID of line
            'onlyID'        => false

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============
        
        #} ========== CHECK FIELDS ============

            $id = (int)$id;
            $objectID = (int)$objectID;
            $objectType = (int)$objectType;

        #} ========= / CHECK FIELDS ===========
        
        #} Check ID or name/type
        if (
            $objectType > 0 && 
                (
                    (!empty($id) && $id > 0)
                    ||
                    (!empty($objectID) && $objectID > 0)
                )
            ){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query
            $query = "SELECT * FROM ".$ZBSCRM_t['externalsources'];

            #} ============= WHERE ================

                #} Add ID
                if (!empty($id) && $id > 0) $wheres['ID'] = array('ID','=','%d',$id);

                #} zbss_objid
                if (!empty($objectID) && $objectID > 0) $wheres['zbss_objid'] = array('zbss_objid','=','%d',$objectID);

                #} zbss_objid
                if (!empty($objectType) && $objectType > 0) $wheres['zbss_objtype'] = array('zbss_objtype','=','%d',$objectType);

                #} Source
                if (!empty($source) && $source !== -1) $wheres['zbss_source'] = array('zbss_source','=','%s',$source);

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','DESC') . $this->buildPaging(0,1);

            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $potentialRes = $wpdb->get_row($queryObj, OBJECT);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

            #} Interpret Results (ROW)
            if (isset($potentialRes) && isset($potentialRes->ID)) {

                #} Has results, tidy + return 
                
                    #} Only ID? return it directly
                    if ($onlyID === true) return $potentialRes->ID;
                
                    // tidy
                    $res = $this->tidy_externalsource($potentialRes);

                    return $res;

            }

        } // / if ID

        return false;

    }



    /**
     * returns multiple external source line +- details
     *
     * @param int id        tag id
     * @param array $args   Associative array of arguments
     *                      withStats
     *
     * @return array results
     */
    public function getExternalSources($id=-1,$args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(
    
            'objectID'         => -1, 
            'objectType'       => -1, 

            // permissions
            'ignoreowner'   => false, // this'll let you not-check the owner of obj

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============
        
        #} ========== CHECK FIELDS ============

            $id = (int)$id;
            $objectID = (int)$objectID;
            $objectType = (int)$objectType;

        #} ========= / CHECK FIELDS ===========
        
        #} Check ID or name/type
        if (
            $objectType > 0 && 
                (
                    (!empty($id) && $id > 0)
                    ||
                    (!empty($objectID) && $objectID > 0)
                )
            ){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query
            $query = "SELECT * FROM ".$ZBSCRM_t['externalsources'];

            #} ============= WHERE ================

                #} Add ID
                if (!empty($id) && $id > 0) $wheres['ID'] = array('ID','=','%d',$id);

                #} zbss_objid
                if (!empty($objectID) && $objectID > 0) $wheres['zbss_objid'] = array('zbss_objid','=','%d',$objectID);

                #} zbss_objid
                if (!empty($objectType) && $objectType > 0) $wheres['zbss_objtype'] = array('zbss_objtype','=','%d',$objectType);

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','DESC') . $this->buildPaging(0,1000);

            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $potentialRes = $wpdb->get_results($queryObj, OBJECT);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

            $res = array();

            #} Interpret results (Result Set - multi-row)
            if (isset($potentialRes) && is_array($potentialRes) && count($potentialRes) > 0) {

                #} Has results, tidy + return 
                foreach ($potentialRes as $resDataLine) {
                            
                        // tidy
                        $res[] = $this->tidy_externalsource($resDataLine);

                }
            }

            return $res;

        } // / if ID

        return false;

    }

     /**
     * adds or updates an external source object
     *
     * @param array $args Associative array of arguments
     *
     * @return int line ID
     */
    public function addUpdateExternalSource($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1,

            // fields (directly)
            'data'          => array(

                'objectType'   => -1, // enforced 2.97.5+
                'objectID'     => -1,
                'source'        => '',
                'uid'           => '',
                'owner'         => -1

            )

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} ========== CHECK FIELDS ============


            $id = (int)$id;

            // objectType
            if (!isset($data['objectType']) || $data['objectType'] <= 0) return false;

            // objectID
            if (!isset($data['objectID']) || $data['objectID'] <= 0) return false;

            // if owner = -1, add current
            if (!isset($data['owner']) || $data['owner'] === -1) $data['owner'] = zeroBSCRM_user();

            // check name present + legit
            if (!isset($data['source']) || empty($data['source'])) return false;

            // extsource ID finder - if obj source + cid provided, check not already present (if so overwrite)  
            // keeps unique...  
            if ((empty($id) || $id <= 0)
                && 
                (
                    (isset($data['objectType']) && !empty($data['objectType'])) ||
                    (isset($data['objectID']) && !empty($data['objectID'])) ||
                    (isset($data['source']) && !empty($data['source']))
                )) {

                // check by source + cid
                // check existence + return ID
                $potentialID = (int)$this->getExternalSource(-1,array(
                                'objectType'     => $data['objectType'],
                                'objectID'     => $data['objectID'],
                                'source'        => $data['source'],
                                'onlyID'    => true
                                ));

                // override empty ID 
                if (!empty($potentialID) && $potentialID > 0) $id = $potentialID;

            }


        #} ========= / CHECK FIELDS ===========

        #} Check if ID present
        $id = (int)$id;
        if (!empty($id) && $id > 0){

                #} Check if obj exists (here) - for now just brutal update (will error when doesn't exist)

                #} Attempt update
                if ($wpdb->update( 
                        $ZBSCRM_t['externalsources'], 
                        array( 

                            // ownership
                            // no need to update these (as of yet) - can't move teams etc.
                            //'zbs_site' => zeroBSCRM_installSite(),
                            //'zbs_team' => zeroBSCRM_installTeam(),
                            'zbs_owner' => $data['owner'],

                            // fields
                            'zbss_objid' => $data['objectID'],
                            'zbss_objtype' => $data['objectType'],
                            'zbss_source' => $data['source'],
                            'zbss_uid' => $data['uid'],
                            'zbss_lastupdated' => time()
                        ), 
                        array( // where
                            'ID' => $id
                            ),
                        array( // field data types
                            '%d',
                            '%d',
                            '%d',
                            '%s', 
                            '%s', 
                            '%d'
                        ),
                        array( // where data types
                            '%d'
                            )) !== false){

                            // Successfully updated - Return id
                            return $id;

                        } else {
                            
                            // FAILED update
                            return false;

                        }

        } else {
            
            #} No ID - must be an INSERT
            if ($wpdb->insert( 
                        $ZBSCRM_t['externalsources'], 
                        array( 

                            // ownership
                            'zbs_site' => zeroBSCRM_site(),
                            'zbs_team' => zeroBSCRM_team(),
                            'zbs_owner' => $data['owner'],

                            // fields
                            'zbss_objid' => $data['objectID'],
                            'zbss_objtype' => $data['objectType'],
                            'zbss_source' => $data['source'],
                            'zbss_uid' => $data['uid'],
                            'zbss_created' => time(),
                            'zbss_lastupdated' => time()
                        ), 
                        array( // field data types
                            '%d',  // site
                            '%d',  // team
                            '%d',  // owner

                            '%d',  
                            '%d',  
                            '%s',  
                            '%s',  
                            '%d',  
                            '%d'  
                        ) ) > 0){

                    #} Successfully inserted, lets return new ID
                    $newID = $wpdb->insert_id;

                    return $newID;

                } else {

                    #} Failed to Insert
                    return false;

                }

        }

        return false;

    }

     /**
     * deletes an external source object
     *
     * @param array $args Associative array of arguments
     *              id
     *
     * @return int success;
     */
    public function deleteExternalSource($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} Check ID & Delete :)
        $id = (int)$id;
        if (!empty($id) && $id > 0) return zeroBSCRM_db2_deleteGeneric($id,'externalsources');

        return false;

    }

    /**
     * tidy's the object from wp db into clean array
     *
     * @param array $obj (DB obj)
     *
     * @return array (clean obj)
     */
    public function tidy_externalsource($obj=false){

            $res = false;

            if (isset($obj->ID)){
            $res = array();
            $res['id'] = $obj->ID;
            /* 
              `zbs_site` INT NULL DEFAULT NULL,
              `zbs_team` INT NULL DEFAULT NULL,
              `zbs_owner` INT NOT NULL,
            */

            $res['objid'] = $obj->zbss_objid;
            $res['objtype'] = $obj->zbss_objtype;
            $res['source'] = $obj->zbss_source;
            $res['uid'] = $this->stripSlashes($obj->zbss_uid);


            $res['created'] = $obj->zbss_created;
            $res['lastupdated'] = $obj->zbss_lastupdated;

        } 

        return $res;


    }


    // =========== / External Sources      ===========================================
    // ===============================================================================





    // ===============================================================================
    // ===========   Web Tracking (UTM etc.)  ========================================

    /**
     * returns full tracking line +- details
     *
     * @param int id        tag id
     * @param array $args   Associative array of arguments
     *                      withStats
     *
     * @return array result
     */
    public function getTracking($id=-1,$args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            // permissions
            'ignoreowner'   => false, // this'll let you not-check the owner of obj

            // returns scalar ID of line
            'onlyID'        => false

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============
        
        #} ========== CHECK FIELDS ============

            $id = (int)$id;
        #} ========= / CHECK FIELDS ===========
        
        #} Check ID or name/type
        if (!empty($id) && $id > 0){

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query
            $query = "SELECT * FROM ".$ZBSCRM_t['tracking'];

            #} ============= WHERE ================

                #} Add ID
                if (!empty($id) && $id > 0) $wheres['ID'] = array('ID','=','%d',$id);

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','DESC') . $this->buildPaging(0,1);

            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                $potentialRes = $wpdb->get_row($queryObj, OBJECT);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }

            #} Interpret Results (ROW)
            if (isset($potentialRes) && isset($potentialRes->ID)) {

                #} Has results, tidy + return 
                
                    #} Only ID? return it directly
                    if ($onlyID === true) return $potentialRes->ID;
                
                    // tidy
                    $res = $this->tidy_tracking($potentialRes);

                    return $res;

            }

        } // / if ID

        return false;

    }


     /**
     * adds or updates a tracking object
     *
     * @param array $args Associative array of arguments
     *
     * @return int line ID
     */
    public function addUpdateTracking($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1,

            // fields (directly)
            'data'          => array(

                'contactID'             => -1,
                'action'                => '',
                'action_detail'         => '',
                'referrer'              => '',
                'utm_source'            => '',
                'utm_medium'            => '',
                'utm_name'              => '',
                'utm_term'              => '',
                'utm_content'           => '',

                'owner'         => -1

            )

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} ========== CHECK FIELDS ============

            $id = (int)$id;

            // contactID
            if (!isset($data['contactID']) || $data['contactID'] <= 0) return false;

            // if owner = -1, add current
            if (!isset($data['owner']) || $data['owner'] === -1) $data['owner'] = zeroBSCRM_user();

            // check action present + legit
            if (!isset($data['action']) || empty($data['action'])) return false;

        #} ========= / CHECK FIELDS ===========

        #} Check if ID present
        $id = (int)$id;
        if (!empty($id) && $id > 0){

                #} Check if obj exists (here) - for now just brutal update (will error when doesn't exist)

                #} Attempt update
                if ($wpdb->update( 
                        $ZBSCRM_t['tracking'], 
                        array( 

                            // ownership
                            // no need to update these (as of yet) - can't move teams etc.
                            //'zbs_site' => zeroBSCRM_installSite(),
                            //'zbs_team' => zeroBSCRM_installTeam(),
                            'zbs_owner' => $data['owner'],

                            // fields
                            'zbst_contactid' => $data['contactID'],
                            'zbst_action' => $data['action'],
                            'zbst_action_detail' => $data['action_detail'],
                            'zbst_referrer' => $data['referrer'],
                            'zbst_utm_source' => $data['utm_source'],
                            'zbst_utm_medium' => $data['utm_medium'],
                            'zbst_utm_name' => $data['utm_name'],
                            'zbst_utm_term' => $data['utm_term'],
                            'zbst_utm_content' => $data['utm_content'],

                            'zbst_lastupdated' => time()
                        ), 
                        array( // where
                            'ID' => $id
                            ),
                        array( // field data types
                            '%d',

                            '%d',
                            '%s',
                            '%s', 
                            '%s', 
                            '%s', 
                            '%s',
                            '%s', 
                            '%s', 
                            '%s', 

                            '%d'
                        ),
                        array( // where data types
                            '%d'
                            )) !== false){

                            // Successfully updated - Return id
                            return $id;

                        } else {

                            // FAILED update
                            return false;

                        }

        } else {
            
            #} No ID - must be an INSERT
            if ($wpdb->insert( 
                        $ZBSCRM_t['tracking'], 
                        array( 

                            // ownership
                            'zbs_site' => zeroBSCRM_site(),
                            'zbs_team' => zeroBSCRM_team(),
                            'zbs_owner' => $data['owner'],

                            // fields
                            'zbst_contactid' => $data['contactID'],
                            'zbst_action' => $data['action'],
                            'zbst_action_detail' => $data['action_detail'],
                            'zbst_referrer' => $data['referrer'],
                            'zbst_utm_source' => $data['utm_source'],
                            'zbst_utm_medium' => $data['utm_medium'],
                            'zbst_utm_name' => $data['utm_name'],
                            'zbst_utm_term' => $data['utm_term'],
                            'zbst_utm_content' => $data['utm_content'],

                            'zbst_created' => time(),
                            'zbst_lastupdated' => time()
                        ), 
                        array( // field data types
                            '%d',  // site
                            '%d',  // team
                            '%d',  // owner

                            '%d',
                            '%s',
                            '%s', 
                            '%s', 
                            '%s', 
                            '%s',
                            '%s', 
                            '%s', 
                            '%s', 

                            '%d',  
                            '%d'  
                        ) ) > 0){

                    #} Successfully inserted, lets return new ID
                    $newID = $wpdb->insert_id;
                    return $newID;

                } else {

                    #} Failed to Insert
                    return false;

                }

        }

        return false;

    }

     /**
     * deletes a tracking object
     *
     * @param array $args Associative array of arguments
     *              id
     *
     * @return int success;
     */
    public function deleteTracking($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} Check ID & Delete :)
        $id = (int)$id;
        if (!empty($id) && $id > 0) return zeroBSCRM_db2_deleteGeneric($id,'tracking');

        return false;

    }

    /**
     * tidy's the object from wp db into clean array
     *
     * @param array $obj (DB obj)
     *
     * @return array (clean obj)
     */
    private function tidy_tracking($obj=false){

            $res = false;

            if (isset($obj->ID)){
            $res = array();
            $res['id'] = $obj->ID;
            /* 
              `zbs_site` INT NULL DEFAULT NULL,
              `zbs_team` INT NULL DEFAULT NULL,
              `zbs_owner` INT NOT NULL,
            */

            $res['contactid'] = $obj->zbss_contactid;
            $res['action'] = $obj->zbst_action;
            $res['action_detail'] = $obj->zbst_action_detail;
            $res['referrer'] = $obj->zbst_referrer;
            $res['utm_source'] = $obj->zbst_utm_source;
            $res['utm_medium'] = $obj->zbst_utm_medium;
            $res['utm_name'] = $obj->zbst_utm_name;
            $res['utm_term'] = $obj->zbst_utm_term;
            $res['utm_content'] = $obj->zbst_utm_content;


            $res['created'] = $obj->zbst_created;
            $res['lastupdated'] = $obj->zbst_lastupdated;

        } 

        return $res;


    }


    // =========== / Web Tracking (UTM etc.)      ====================================
    // ===============================================================================







    // ===============================================================================
    // ===========   LOGS   ==========================================================

    /**
     * returns cron log lines
     *
     * @param array $args Associative array of arguments
     *              searchPhrase, sortByField, sortOrder, page, perPage
     *
     * @return array of tag lines
     */
    public function getCronLogs($args=array()){

        #} ============ LOAD ARGS =============
        $defaultArgs = array(


            'job'  => '', 


            'sortByField'   => 'ID',
            'sortOrder'     => 'DESC',
            'page'          => 0,
            'perPage'       => 100,

            // permissions
            'ignoreowner'   => false // this'll let you not-check the owner of obj

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        #} ========== CHECK FIELDS ============

        
        #} ========= / CHECK FIELDS ===========

        global $ZBSCRM_t,$wpdb; 
        $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

        #} Build query
        $query = "SELECT * FROM ".$ZBSCRM_t['cronmanagerlogs'];

        #} ============= WHERE ================

            #} job
            if (!empty($job) && $job > 0) $wheres['job'] = array('job','=','%s',$job);

        #} ============ / WHERE ===============

        #} Build out any WHERE clauses
        $wheresArr= $this->buildWheres($wheres,$whereStr,$params);
        $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
        #} / Build WHERE

        #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
        $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
        $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
        #} / Ownership

        #} Append to sql (this also automatically deals with sortby and paging)
        $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort($sortByField,$sortOrder) . $this->buildPaging($page,$perPage);

        try {

            #} Prep & run query
            $queryObj = $this->prepare($query,$params);
            $potentialRes = $wpdb->get_results($queryObj, OBJECT);

        } catch (Exception $e){

            #} General SQL Err
            $this->catchSQLError($e);

        }

        #} Interpret results (Result Set - multi-row)
        if (isset($potentialRes) && is_array($potentialRes) && count($potentialRes) > 0) {

            #} Has results, tidy + return 
            foreach ($potentialRes as $resDataLine) {
                        
                    // tidy
                    $resArr = $this->tidy_cronlog($resDataLine);

                    $res[] = $resArr;

            }
        }

        return $res;
    } 



     /**
     * adds or updates a cron log object
     *
     * @param array $args Associative array of arguments
     *              id (not req.), owner (not req.) data -> key/val
     *
     * @return int line ID
     */
    public function addUpdateCronLog($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1,
            'owner'         => -1,

            // fields (directly)
            'data'          => array(

                'job'   => '',
                'jobstatus'     => -1,
                'jobstarted'      => -1,
                'jobfinished' => -1,
                'jobnotes'  => ''
                
            )

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============


        #} ========== CHECK FIELDS ============

            $id = (int)$id;

            // if owner = -1, add current
            if (!isset($owner) || $owner === -1) $owner = zeroBSCRM_user();

        #} ========= / CHECK FIELDS ===========

        $dataArr = array( 

                            // ownership
                            // no need to update these (as of yet) - can't move teams etc.
                            //'zbs_site' => zeroBSCRM_installSite(),
                            //'zbs_team' => zeroBSCRM_installTeam(),
                            'zbs_owner' => $owner,

                            // fields
                            'job' => $data['job'],
                            'jobstatus' => $data['jobstatus'],
                            'jobstarted' => $data['jobstarted'],
                            'jobfinished' => $data['jobfinished'],
                            'jobnotes' => $data['jobnotes']
                        );

        $dataTypes = array( // field data types
                            '%d',

                            '%s',
                            '%d',
                            '%d', 
                            '%d',
                            '%s'
                        );


        if (isset($id) && !empty($id) && $id > 0){

                #} Check if obj exists (here) - for now just brutal update (will error when doesn't exist)

                #} Attempt update
                if ($wpdb->update( 
                        $ZBSCRM_t['cronmanagerlogs'], 
                        $dataArr, 
                        array( // where
                            'ID' => $id
                            ),
                        $dataTypes,
                        array( // where data types
                            '%d'
                            )) !== false){

                            // Successfully updated - Return id
                            return $id;

                        } else {

                            // FAILED update
                            return false;

                        }

        } else {

            // add team etc
            $dataArr['zbs_site'] = zeroBSCRM_site(); $dataTypes[] = '%d';
            $dataArr['zbs_team'] = zeroBSCRM_team(); $dataTypes[] = '%d';
            
            #} No ID - must be an INSERT
            if ($wpdb->insert( 
                        $ZBSCRM_t['cronmanagerlogs'], 
                        $dataArr, 
                        $dataTypes ) > 0){

                    #} Successfully inserted, lets return new ID
                    $newID = $wpdb->insert_id;

                    return $newID;

                } else {

                    #} Failed to Insert
                    return false;

                }

        }

        return false;

    }

     /**
     * deletes a CRON Log object
     * NOTE! this doesn't yet delete any META!
     *
     * @param array $args Associative array of arguments
     *              id
     *
     * @return int success;
     */
    public function deleteCronLog($args=array()){

        global $ZBSCRM_t,$wpdb;

        #} ============ LOAD ARGS =============
        $defaultArgs = array(

            'id'            => -1

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS ============

        #} Check ID & Delete :)
        $id = (int)$id;
        if (!empty($id) && $id > 0) return zeroBSCRM_db2_deleteGeneric($id,'cronmanagerlogs');

        return false;

    }

    /**
     * tidy's the object from wp db into clean array
     *
     * @param array $obj (DB obj)
     *
     * @return array (clean obj)
     */
    private function tidy_cronlog($obj=false){

            $res = false;

            if (isset($obj->ID)){
            $res = array();
            $res['id'] = $obj->ID;
            $res['owner'] = $obj->zbs_owner;
            
            $res['job'] = $obj->job;
            $res['jobstatus'] = $obj->jobstatus;
            $res['jobstarted'] = $obj->jobstarted;
            $res['jobfinished'] = $obj->jobfinished;
            $res['jobnotes'] = $obj->jobnotes;            

        } 

        return $res;


    }

    // =========== / CRONLOGS  =======================================================
    // ===============================================================================

/* ======================================================
   / DAL CRUD
   ====================================================== */





/* ======================================================
   Formatters
   ====================================================== */

    /**
     * Returns a formatted full name (e.g. Mr. Dave Davids)
     *
     * @param array $obj (tidied db obj)
     *
     * @return string fullname
     */
   public function format_fullname($contactArr=array()){

        $str = '';
        if (isset($contactArr['prefix'])) $str .= $contactArr['prefix'];
        if (isset($contactArr['fname'])) {
            if (!empty($str)) $str .= ' ';
            $str .= $contactArr['fname'];
        }
        if (isset($contactArr['lname'])) {
            if (!empty($str)) $str .= ' ';
            $str .= $contactArr['lname'];
        }

        return $str;
   }

    /**
     * Returns a formatted full name +- id, address (e.g. Mr. Dave Davids 12 London Street #23)
     * Replaces zeroBS_customerName from DAL1 more realistically than format_fullname
     *
     * @param array $obj (tidied db obj)
     *
     * @return string fullname
     */
   public function format_name_etc($contactArr=array(),$args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            'incFirstLineAddr'  => false,
            'incID'             => false,
            'company'           => false, // if true, looks for 'name' not 'fname+lname'

        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        // full name first
        if (!$company)
            $str = $this->format_fullname($contactArr);
        else { // company
            $str = ''; if (isset($contactArr['name'])) $str = $contactArr['name'];
        }

        #} Legacy from DAL1: 

            #} First line of addr?
            if ($incFirstLineAddr) if (isset($contactArr['addr1']) && !empty($contactArr['addr1'])) $str .= ' ('.$contactArr['addr1'].')';

            #} ID?
            if ($incID) $str .= ' #'.$contactArr['id'];

        return $str;
   }

    /**
     * Returns a formatted address
     * via getContactFullName this replaces zeroBS_customerAddr in dal1
     * NOTE, post v3.0 applies to ANY form of addr.
     *
     * @param array $obj (tidied db obj)
     *
     * @return string address
     */
    public function format_address($contactArr=array(),$args=array()){

        #} =========== LOAD ARGS ==============
        $defaultArgs = array(

            'addrFormat'        => 'short',
            'delimiter'         => ', ', // could use <br>
            'secondaddr'        => false // if true, use second address (if present in contact_arr)



        ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
        #} =========== / LOAD ARGS =============

        $ret = ''; $fieldPrefix = ''; 
        if ($secondaddr) $fieldPrefix = 'sec';
        // v3.0 exception, contacts need this prefix for second :/
        // attempt to account for that:
        if ($secondaddr && !isset($contactArr[$fieldPrefix.'addr1']) && isset($contactArr['secaddr_'.'addr1'])) $fieldPrefix = 'secaddr_';


        #} Legacy from DAL1: 
        $addrCustomFields = $this->getActiveCustomFields(array('objtypeid'=>ZBS_TYPE_ADDRESS));

            if ($addrFormat == 'short'){

                if (isset($contactArr[$fieldPrefix.'addr1']) && !empty($contactArr[$fieldPrefix.'addr1'])) $ret = $contactArr[$fieldPrefix.'addr1'];
                if (isset($contactArr[$fieldPrefix.'city']) && !empty($contactArr[$fieldPrefix.'city'])) $ret .= $this->delimiterIf($delimiter,$ret).$contactArr[$fieldPrefix.'city'];

            } else if ($addrFormat == 'full'){

                if (isset($contactArr[$fieldPrefix.'addr1']) && !empty($contactArr[$fieldPrefix.'addr1'])) $ret = $contactArr[$fieldPrefix.'addr1'];
                if (isset($contactArr[$fieldPrefix.'addr2']) && !empty($contactArr[$fieldPrefix.'addr2'])) $ret .= $this->delimiterIf($delimiter,$ret).$contactArr[$fieldPrefix.'addr2'];
                if (isset($contactArr[$fieldPrefix.'city']) && !empty($contactArr[$fieldPrefix.'city'])) $ret .= $this->delimiterIf($delimiter,$ret).$contactArr[$fieldPrefix.'city'];
                if (isset($contactArr[$fieldPrefix.'county']) && !empty($contactArr[$fieldPrefix.'county'])) $ret .= $this->delimiterIf($delimiter,$ret).$contactArr[$fieldPrefix.'county'];
                if (isset($contactArr[$fieldPrefix.'postcode']) && !empty($contactArr[$fieldPrefix.'postcode'])) $ret .= $this->delimiterIf($delimiter,$ret).$contactArr[$fieldPrefix.'postcode'];

                // any custom fields here
                if (is_array($addrCustomFields) && count($addrCustomFields) > 0){

                    foreach ($addrCustomFields as $cK => $cF){

                        // v2:
                        //$cKN = (int)$cK+1;
                        //$cKey = $fieldPrefix.'addr_cf'.$cKN;  
                        // v3:                    
                        $cKey = $fieldPrefix.'addr_'.$cK;                 

                        if (isset($contactArr[$cKey]) && !empty($contactArr[$cKey])) {

                            // if someone is using date custom fields here, output date, not uts - super edge case gh-349
                            if (isset($contactArr[$cKey.'_cfdate']) && !empty($contactArr[$cKey.'_cfdate']))
                                $ret .= $this->delimiterIf($delimiter,$ret).$contactArr[$cKey.'_cfdate'];
                            else
                                $ret .= $this->delimiterIf($delimiter,$ret).$contactArr[$cKey];

                        }
                    }

                }


            }

        $trimRet = trim($ret);
        return $trimRet;
    }

    /**
     * Returns a slug, generated from a str :)
     * Uses this: https://gist.github.com/james2doyle/9158349
     * In future, perhaps switch to this: https://github.com/ausi/slug-generator
     *
     * @param array $obj (tidied db obj)
     *
     * @return string fullname
     */

    /* This was causing people without iconv to bug out ... so rewrote quickly, need to add in ausi-slug-gen later

    ... rethinking https://github.com/phalcon/incubator/blob/master/Library/Phalcon/Utils/Slug.php
   public function makeSlug($string, $replace = array(), $delimiter = '-') {
      // https://github.com/phalcon/incubator/blob/master/Library/Phalcon/Utils/Slug.php
      if (!extension_loaded('iconv')) {
        throw new Exception('iconv module not loaded');
      }
      // Save the old locale and set the new locale to UTF-8
      $oldLocale = setlocale(LC_ALL, '0');
      setlocale(LC_ALL, 'en_US.UTF-8');
      $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
      if (!empty($replace)) {
        $clean = str_replace((array) $replace, ' ', $clean);
      }
      $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
      $clean = strtolower($clean);
      $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
      $clean = trim($clean, $delimiter);
      // Revert back to the old locale
      setlocale(LC_ALL, $oldLocale);
      return $clean;
    }

    */
    public function makeSlug($string, $replace = array(), $delimiter = '-') {

      // https://github.com/phalcon/incubator/blob/master/Library/Phalcon/Utils/Slug.php
        // and
      // https://stackoverflow.com/questions/4910627/php-iconv-translit-for-removing-accents-not-working-as-excepted
      //if (!extension_loaded('iconv')) {
      //  throw new Exception('iconv module not loaded');
      //}
      // Save the old locale and set the new locale to UTF-8
      $oldLocale = setlocale(LC_ALL, '0');
      setlocale(LC_ALL, 'en_US.UTF-8');

      // replace non letter or digits by -
      $clean = preg_replace('#[^\\pL\d]+#u', '-', $string);

      // transliterate
      if (function_exists('iconv')) 
        $clean = @iconv('UTF-8', 'ASCII//TRANSLIT', $clean);
      // else? smt else?

      // replace
      if (!empty($replace)) {
        $clean = str_replace((array) $replace, ' ', $clean);
      }
      
      // clean
      $clean = $this->makeSlugCleanStr($clean,$delimiter);

      // Revert back to the old locale
      setlocale(LC_ALL, $oldLocale);
      return $clean;
    }

    private function makeSlugCleanStr($string='', $delimiter='-'){

        // fix for ascii passing (I think) of ' resulting in -039- in place of '
        $string = str_replace('-039-','',$string);

        // replace non letter or non digits by -
        $string = preg_replace('#[^\pL\d]+#u', '-', $string);
        // Trim trailing -
        $string = trim($string, '-');
        $clean = preg_replace('~[^-\w]+~', '', $string);
        $clean = strtolower($clean);
        $clean = preg_replace('#[\/_|+ -]+#', $delimiter, $clean);
        $clean = trim($clean, $delimiter);


        return $clean;
    }


/* ======================================================
   / Formatters
   ====================================================== */






/* ======================================================
    To be sorted helpers 
   ====================================================== */

        /**
         * helper - returns single field against db table WHERE X
         * Will only work for native fields (not Cutom fields)
         *
         * @param array WHERE clauses (not Req.)
         * @param string tablename
         * @param string colname
         *
         * @return string
         */
        public function getFieldByWHERE($args=array()){

            #} =========== LOAD ARGS ==============
            $defaultArgs = array(

                'where' => -1,
                'objtype' => -1,
                'colname' => '',

                // permissions
                'ignoreowner'   => false // this'll let you not-check the owner of obj

            ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
            #} =========== / LOAD ARGS =============
            
            #} ========== CHECK FIELDS ============

                // check obtype is legit
                $objtype = (int)$objtype;
                if (!isset($objtype) || $objtype == -1 || $this->objTypeKey($objtype) === -1) return false;
            
                // check field (or 'COUNT(x)')
                if (empty($colname)) return false;

            #} ========= / CHECK FIELDS ===========

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query - NOTE this is vulnerable to injection.
            $query = "SELECT $colname FROM ".$this->lazyTable($objtype);
            //$params[] = $colname;

            #} ============= WHERE ================

                #} Add any where's 
                $wheres = $where;

            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','DESC') . $this->buildPaging(0,1);

            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                return $wpdb->get_var($queryObj);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }


            return false;

        }


        /**
         * helper - returns single field against db table (where ID =)
         * Will only work for native fields (not Cutom fields)
         *
         * @param int objID     object id
         * @param int objTypeID objectType id
         * @param string colname
         *
         * @return string
         */
        public function getFieldByID($args=array()){

            #} =========== LOAD ARGS ==============
            $defaultArgs = array(

                'id' => -1,
                'objtype' => -1,
                'colname' => '',

                // permissions
                'ignoreowner'   => false // this'll let you not-check the owner of obj

            ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
            #} =========== / LOAD ARGS =============
            
            #} ========== CHECK FIELDS ============

                // check id
                $id = (int)$id;
                if (!isset($id) || $id < 1) return false;

                // check obtype is legit
                $objtype = (int)$objtype;
                if (!isset($objtype) || $objtype == -1 || $this->objTypeKey($objtype) === -1) return false;
            
                // check field
                if (empty($colname)) return false;

            #} ========= / CHECK FIELDS ===========

            global $ZBSCRM_t,$wpdb; 
            $wheres = array('direct'=>array()); $whereStr = ''; $additionalWhere = ''; $params = array(); $res = array();

            #} Build query - NOTE this is vulnerable to injection.
            $query = "SELECT $colname FROM ".$this->lazyTable($objtype);
            //$params[] = $colname;

            #} ============= WHERE ================

                #} Add ID
                if (!empty($id) && $id > 0) $wheres['ID'] = array('ID','=','%d',$id);


            #} ============ / WHERE ==============

            #} Build out any WHERE clauses
            $wheresArr = $this->buildWheres($wheres,$whereStr,$params);
            $whereStr = $wheresArr['where']; $params = $params + $wheresArr['params'];
            #} / Build WHERE

            #} Ownership v1.0 - the following adds SITE + TEAM checks, and (optionally), owner
            $params = array_merge($params,$this->ownershipQueryVars($ignoreowner)); // merges in any req.
            $ownQ = $this->ownershipSQL($ignoreowner); if (!empty($ownQ)) $additionalWhere = $this->spaceAnd($additionalWhere).$ownQ; // adds str to query
            #} / Ownership

            #} Append to sql (this also automatically deals with sortby and paging)
            $query .= $this->buildWhereStr($whereStr,$additionalWhere) . $this->buildSort('ID','DESC') . $this->buildPaging(0,1);

            try {

                #} Prep & run query
                $queryObj = $this->prepare($query,$params);
                return $wpdb->get_var($queryObj);

            } catch (Exception $e){

                #} General SQL Err
                $this->catchSQLError($e);

            }


            return false;

        }


        /**
         * helper - forces update of field for obj id + type, 
         * THIS IS HARD usage, not for beginners/non-directors.
         * ... can break things if using this, so only use strictly for globally generic columns
         * ... e.g. zbs_owner, which appears in all obj.
         * ... ALWAYS use the DAL->contacts->whatever before this, where possible
         * // NOTE NOTE - THIS does not update "lastupdated" for each obj... AVOID USE!
         *
         * @param int objID     object id
         * @param int objTypeID objectType id
         * @param string colname
         *
         * @return string
         */
        public function setFieldByID($args=array()){

            #} =========== LOAD ARGS ==============
            $defaultArgs = array(

                'objID' => -1,
                'objTypeID' => -1,

                'colname' => '',
                'coldatatype' => '%d', // %d/s
                'newValue' => -99,

            ); foreach ($defaultArgs as $argK => $argV){ $$argK = $argV; if (is_array($args) && isset($args[$argK])) {  if (is_array($args[$argK])){ $newData = $$argK; if (!is_array($newData)) $newData = array(); foreach ($args[$argK] as $subK => $subV){ $newData[$subK] = $subV; }$$argK = $newData;} else { $$argK = $args[$argK]; } } }
            #} =========== / LOAD ARGS =============

            // this'll only update columnames present in here:            
            $restrictedToColumns = array('zbs_owner');

            if (in_array($colname,$restrictedToColumns) && $objID > 0 && $objTypeID > 0 && !empty($colname) && !empty($coldatatype) && $newValue !== -99){

                global $wpdb;

                // got table?
                $tableName = $this->lazyTable($objtype);

                if (empty($tableName)) return false;

                #} Attempt update
                if ($wpdb->update( 
                        $tableName, 
                        array( 

                            $colname => $newValue

                        ), 
                        array( // where
                            'ID' => $objID
                            ),
                        array( // field data types
                            $coldatatype
                        ),
                        array( // where data types
                            '%d'
                            )) !== false){

                            // Successfully updated - Return id
                            return $objID;

                        } else {

                            // FAILED update
                            return false;

                        }


            }


            return false;

        }










        // brutal switch for lazy tablenames
        public function lazyTable($objType=-1){

            global $ZBSCRM_t;

            switch ($objType){

                case ZBS_TYPE_CONTACT:
                    return $ZBSCRM_t['contacts'];
                    break;
                // dal3:
                case ZBS_TYPE_COMPANY:
                    return $ZBSCRM_t['companies'];
                    break;
                case ZBS_TYPE_QUOTE:
                    return $ZBSCRM_t['quotes'];
                    break;
                case ZBS_TYPE_INVOICE:
                    return $ZBSCRM_t['invoices'];
                    break;
                case ZBS_TYPE_TRANSACTION:
                    return $ZBSCRM_t['transactions'];
                    break;
                case ZBS_TYPE_EVENT:
                    return $ZBSCRM_t['events'];
                    break;
                case ZBS_TYPE_FORM:
                    return $ZBSCRM_t['forms'];
                    break;
                case ZBS_TYPE_LOG:
                    return $ZBSCRM_t['logs'];
                    break;
                case ZBS_TYPE_SEGMENT:
                    return $ZBSCRM_t['segments'];
                    break;
                case ZBS_TYPE_LINEITEM:
                    return $ZBSCRM_t['lineitems'];
                    break;
                case ZBS_TYPE_EVENTREMINDER:
                    return $ZBSCRM_t['eventreminders'];
                    break;
                case ZBS_TYPE_QUOTETEMPLATE:
                    return $ZBSCRM_t['quotetemplates'];
                    break;

            }
            
            return false;

        }

        // brutal switch for lazy tidy func
        public function lazyTidy($objType=-1,$obj=false){

            switch ($objType){

                case ZBS_TYPE_CONTACT:
                    return $this->contacts->tidy_contact($obj);
                    break;
                // dal3:
                case ZBS_TYPE_COMPANY:
                    return $this->companies->tidy_company($obj);
                    break;
                case ZBS_TYPE_QUOTE:
                    return $this->quotes->tidy_quote($obj);
                    break;
                case ZBS_TYPE_INVOICE:
                    return $this->invoices->tidy_invoice($obj);
                    break;
                case ZBS_TYPE_TRANSACTION:
                    return $this->transactions->tidy_transaction($obj);
                    break;
                case ZBS_TYPE_EVENT:
                    return $this->events->tidy_event($obj);
                    break;
                case ZBS_TYPE_FORM:
                    return $this->forms->tidy_form($obj);
                    break;
                case ZBS_TYPE_LOG:
                    return $this->logs->tidy_log($obj);
                    break;
                case ZBS_TYPE_SEGMENT:
                    return $this->segments->tidy_segment($obj);
                    break;
                case ZBS_TYPE_LINEITEM:
                    return $this->lineitems->tidy_lineitem($obj);
                    break;
                case ZBS_TYPE_EVENTREMINDER:
                    return $this->eventreminders->tidy_eventreminder($obj);
                    break;
                case ZBS_TYPE_QUOTETEMPLATE:
                    return $this->quotetemplates->tidy_quotetemplate($obj);
                    break;


            }
            
            return false;

        }

        // guesses at a tidy... lazy, remove these if hit walls
        public function lazyTidyGeneric($obj=false){

            $res = false;

            foreach ($obj as $propKey => $prop){

                if (!is_array($res)) $res = array();

                if ($propKey != 'ID' && strpos($propKey, '_') > 0){

                    // zbs_owner -> owner
                    $newKey = substr($propKey,strpos($propKey, '_')+1);
                    
                    $res[$newKey] = $this->stripSlashes($prop);

                } else $res['id'] = $prop;


            }

            return $res;

        }

        // appends a space, if req. (lazy helper for amongst queries)
        public function space($str='',$pre=false){

            if (!empty($str))
                if ($pre)
                    return ' '.$str;
                else
                    return $str.' ';

            return $str;

        }

        // appends a space and 'AND', if req. (lazy helper for amongst queries)
        public function spaceAnd($str=''){

            if (!empty($str)) return $str.' AND ';

            return $str;

        }

        // appends a space and 'Where', if req. (lazy helper for amongst queries)
        public function spaceWhere($str=''){

            $trimmedStr = trim($str);
            if (!empty($trimmedStr)) return ' WHERE '.$trimmedStr;

            return $str;

        }

        // returns delimiter, if str != epty
        // used to be zeroBS_delimiterIf pre dal1
        public function delimiterIf($delimiter,$ifStr=''){

            if (!empty($ifStr)) return $delimiter;

            return '';
        }

        // internal middle man for zeroBSCRM_stripSlashes where ALWAYS returns
        public function stripSlashes($obj=false){

            return zeroBSCRM_stripSlashes($obj,true);

        }

        // if it thinks str is json, it'll decode + return obj, otherwise returns str
        // this only works with arr/obj
        public function decodeIfJSON($str=''){

            if (zeroBSCRM_isJson($str)) return json_decode($str,true); // true req. https://stackoverflow.com/questions/22878219/json-encode-turns-array-into-an-object

            return $str;
        }


        // takes wherestr + additionalwhere and outputs legit SQL
        // GENERIC helper for all queries :)
        public function buildWhereStr($whereStr='',$additionalWhere=''){

            //echo 'W:'.$whereStr.'<br >AW:'.$additionalWhere.'!!<br ><Br >';
            
            #} Build
            $where = trim($whereStr); 

            #} Any additional
            if (!empty($additionalWhere)){ 
                if (!empty($where)) 
                    $where = $this->spaceAnd($where);
                else
                    $where = 'WHERE ';
                $where .= $additionalWhere;
            }

            return $this->space($where,true);
        }

        // add where's to SQL
        // + 
        // feed in params
        // GENERIC helper for all queries :)
        public function buildWheres($wheres=array(),$whereStr='',$params=array(),$andOr='AND',$includeInitialWHERE=true){

            $ret = array('where'=>$whereStr,'params'=>$params); if ($andOr != 'AND' && $andOr != 'OR') $andOr = 'AND';

              // clear empty direct
              if (isset($wheres['direct']) && is_array($wheres['direct']) && count($wheres['direct']) == 0) unset($wheres['direct']);

            if (is_array($wheres) && count($wheres) > 0) foreach ($wheres as $key => $whereArr) {

                if (empty($ret['where']) && $includeInitialWHERE) $ret['where'].= ' WHERE ';

                // Where's are passed 2 ways, "direct":
                // array(SQL,array(params))
                if ($key == 'direct'){

                    // several under 1 direct
                    foreach ($whereArr as $directWhere){

                        if (isset($directWhere[0]) && isset($directWhere[1])){

                        	// multi-direct ANDor
			                if (!empty($ret['where']) && $ret['where'] != ' WHERE '){
			                    $ret['where'] .= ' '.$andOr.' ';
			                }
                            
                            // ++ query
                            $ret['where'] .= $directWhere[0];

                            // ++ params (any number, if set)
                            if (is_array($directWhere[1]))
                                foreach ($directWhere[1] as $x) $ret['params'][] = $x;
                            else
                                $ret['params'][] = $directWhere[1];
                        }

                    }

                } else {

                    if (!empty($ret['where']) && $ret['where'] != ' WHERE '){
                        $ret['where'] .= ' '.$andOr.' ';
                    }

                    // Other way:
                    // irrelevantKEY => array(fieldname,operator,comparisonval,array(params))
                    // e.g. array('ID','=','%d',array(123))
                    // e.g. array('ID','IN','(SUBSELECT)',array(123))

                    // build where (e.g. "X = Y" or "Z IN (1,2,3)")
                    $ret['where'] .= $whereArr[0]. ' '.$whereArr[1].' '.$whereArr[2];

                    // ++ params (any number, if set)
                    if (isset($whereArr[3])) {
                        if (is_array($whereArr[3]))
                            foreach ($whereArr[3] as $x) $ret['params'][] = $x;
                        else
                            $ret['params'][] = $whereArr[3];
                    }

                    /* legacy

                    // add in - NOTE: this is TRUSTING key + whereArr[0]
                    $ret['where'] .= $key.' '.$whereArr[0].' '.$whereArr[2];

                    // feed in params
                    $ret['params'][] = $whereArr[1];
                    */

                }

            }

            return $ret;
        }


        // takes sortby field + order and returns str if not empty :)
        // Note: Is trusting legitimacy of $sortByField as parametised in wp db doesn't seem to work
        // can also now pass array (multi-sort)
        // e.g. $sortByField = 'zbsc_fname' OR $sortByField = array('zbsc_fname'=>'ASC','zbsc_lname' => 'DESC');
        public function buildSort($sortByField='',$sortOrder='ASC'){

            #} Sort by
            if (!is_array($sortByField) && !empty($sortByField)){

                $sortOrder = strtoupper($sortOrder);                

                if (!in_array($sortOrder, array('DESC','ASC'))) $sortOrder = 'DESC';
                return ' ORDER BY '.$sortByField.' '.$sortOrder;

            } else if (is_array($sortByField)){

                $orderByStr = '';
                foreach ($sortByField as $field => $order){

                    if (!empty($orderByStr)) $orderByStr .= ', ';
                    $orderByStr .= $field.' '.strtoupper($order);

                }

                if (!empty($orderByStr)) return ' ORDER BY '.$orderByStr;

            }

            return '';
        }


        // takes $page and $perPage and adds limit str if req.
        public function buildPaging($page=-1,$perPage=-1){

            #} Pagination
            if ($page == -1 && $perPage == -1){

                // NO LIMITS :o

            } else {

                $perPage = (int)$perPage;

                // Because SQL USING zero indexed page numbers, we remove -1 here
                // ... DO NOT change this without seeing usage of the function (e.g. list view) - which'll break
                $page = (int)$page-1; 
                if ($page < 0) $page = 0;

                // page needs multiplying :) 
                if ($page > 0) $page = $page * $perPage;

                // check params realistic
                // todo, for now, brute pass
                return ' LIMIT '.(int)$page.','.(int)$perPage;

            }

            return '';
        }


        // builds WHERE query for meta key / val pairs.
        // e.g. Get customers in Company id 9:
        // ... contacts where their ID is in post_id WHERE meta_key = zbs_company and meta_value = 9
        // infill for half-migrated stuff
        public function buildWPMetaQueryWhere($metaKey=-1,$metaVal=-1){

            if (!empty($metaKey) && !empty($metaVal)){

                global $wpdb;
                return array(
                    
                    'sql' => 'ID IN (SELECT DISTINCT post_id FROM '.$wpdb->prefix.'postmeta WHERE meta_key = %s AND meta_value = %d)',
                    'params' => array($metaKey,$metaVal)
                    );

            }

            return false;

        }

        // this returns %s etc. for common field names, will default to %s unless somt obv a date
        public function getTypeStr($fieldKey=''){

            if ($fieldKey == 'zbs_site' || $fieldKey == 'zbs_team' || $fieldKey == 'zbs_owner') return '%d';

            if (strpos($fieldKey, '_created') > 0) return '%d';
            if (strpos($fieldKey, '_lastupdated') > 0) return '%d';
            if (strpos($fieldKey, '_lastcontacted') > 0) return '%d';
            if (strpos($fieldKey, '_id') > 0) return '%d';
            if (strpos($fieldKey, '_ID') > 0) return '%d';
            if ($fieldKey == 'id' || $fieldKey == 'ID') return '%d';

            return '%s';

        }

        public function prepare($sql='',$params=array()){

            global $wpdb;

            // empty arrays causes issues in wpdb prepare
            if (is_array($params) && count($params) <= 0) return $sql;

            // normal return
            return $wpdb->prepare($sql,$params);

        }

        // not yet used
        public function catchSQLError($errObj=-1){


            // log?

            return false;

        }



/* ======================================================
    / To be sorted helpers  
   ====================================================== */










/* ======================================================
    Middle Man funcs (until DAL3.1)
   ====================================================== */

    // TEMP LOGGING to BACKTRACE LEGACY CALLS:
   /* CREATE TABLE `templogs` (
  `ID` int(32) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `funcname` varchar(500) NOT NULL,
  `filename` varchar(500) NOT NULL,
  `notes` longtext NOT NULL,
  `time` int(14) NOT NULL
) ENGINE='InnoDB' COLLATE 'utf8_general_ci'; */


    // temporary function used in v3.0 prep to weed out bad/old reference use
    // logs to table 'templogs' if table exists
    // left in until 3.1 - see #gh-146
    private function v3templogBacktrace($funcName='',$caller=false,$backtrace=false){

      global $ZBSCRM_t,$wpdb;
      
      $tableExist = $wpdb->get_results("SHOW TABLES LIKE 'templogs'");
      if (count($tableExist) >= 1){
            
            if ($wpdb->insert( 
            'templogs', 
            array( 
                // fields
                'funcname' => $funcName,
                'filename' => $caller['file'].':'.$caller['line'],
                'notes' => print_r($backtrace,1),//,
                'time' => time()
            ), 
            array(
                '%s',  
                '%s', 
                '%s',  
                '%d'  
            ) ) <= 0) exit('ERROR: Failed to log backtrace error ('.$funcName.')!<pre>'.print_r(array($backtrace,$caller,$funcName),1).'</pre>');
        
        }

        return true;

    }


   // polite flag:
   private function ____MIDDLEMAN_FUNCS(){}




public function getContact(...$args){

    // hard-typed
    $funcName = 'getContact';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContacts(...$args){

    // hard-typed
    $funcName = 'getContacts';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function addUpdateContact(...$args){

    // hard-typed
    $funcName = 'addUpdateContact';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function addUpdateContactTags(...$args){

    // hard-typed
    $funcName = 'addUpdateContactTags';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function addUpdateContactCompanies(...$args){

    // hard-typed
    $funcName = 'addUpdateContactCompanies';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function addUpdateContactWPID(...$args){

    // hard-typed
    $funcName = 'addUpdateContactWPID';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function deleteContact(...$args){

    // hard-typed
    $funcName = 'deleteContact';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function tidy_contact(...$args){

    // hard-typed
    $funcName = 'tidy_contact';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function db_ready_contact(...$args){

    // hard-typed
    $funcName = 'db_ready_contact';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactOwner(...$args){

    // hard-typed
    $funcName = 'getContactOwner';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactStatus(...$args){

    // hard-typed
    $funcName = 'getContactStatus';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactEmail(...$args){

    // hard-typed
    $funcName = 'getContactEmail';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactMobile(...$args){

    // hard-typed
    $funcName = 'getContactMobile';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactFullName(...$args){

    // hard-typed
    $funcName = 'getContactFullName';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactFullNameEtc(...$args){

    // hard-typed
    $funcName = 'getContactFullNameEtc';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactAddress(...$args){

    // hard-typed
    $funcName = 'getContactAddress';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContact2ndAddress(...$args){

    // hard-typed
    $funcName = 'getContact2ndAddress';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactTags(...$args){

    // hard-typed
    $funcName = 'getContactTags';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactLastContactUTS(...$args){

    // hard-typed
    $funcName = 'getContactLastContactUTS';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function setContactLastContactUTS(...$args){

    // hard-typed
    $funcName = 'setContactLastContactUTS';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactSocials(...$args){

    // hard-typed
    $funcName = 'getContactSocials';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactWPID(...$args){

    // hard-typed
    $funcName = 'getContactWPID';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactDoNotMail(...$args){

    // hard-typed
    $funcName = 'getContactDoNotMail';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function setContactDoNotMail(...$args){

    // hard-typed
    $funcName = 'setContactDoNotMail';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactAvatarURL(...$args){

    // hard-typed
    $funcName = 'getContactAvatarURL';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactAvatar(...$args){

    // hard-typed
    $funcName = 'getContactAvatar';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactAvatarHTML(...$args){

    // hard-typed
    $funcName = 'getContactAvatarHTML';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactCount(...$args){

    // hard-typed
    $funcName = 'getContactCount';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactCompanies(...$args){

    // hard-typed
    $funcName = 'getContactCompanies';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getContactPrevNext(...$args){

    // hard-typed
    $funcName = 'getContactPrevNext';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->contacts, $funcName)) return call_user_func_array(array($this->contacts,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}












public function getSegments(...$args){

    // hard-typed
    $funcName = 'getSegments';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getSegmentCount(...$args){

    // hard-typed
    $funcName = 'getSegmentCount';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function deleteSegment(...$args){

    // hard-typed
    $funcName = 'deleteSegment';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function tidy_segment(...$args){

    // hard-typed
    $funcName = 'tidy_segment';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function tidy_segment_condition(...$args){

    // hard-typed
    $funcName = 'tidy_segment_condition';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getSegmentsCountIncParams(...$args){

    // hard-typed
    $funcName = 'getSegmentsCountIncParams';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function previewSegment(...$args){

    // hard-typed
    $funcName = 'previewSegment';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function segmentConditionsToArgs(...$args){

    // hard-typed
    $funcName = 'segmentConditionsToArgs';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getSegmentBySlug(...$args){

    // hard-typed
    $funcName = 'getSegmentBySlug';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getSegment(...$args){

    // hard-typed
    $funcName = 'getSegment';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getSegementAudience(...$args){

    // hard-typed
    $funcName = 'getSegementAudience';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getSegmentsContainingContact(...$args){

    // hard-typed
    $funcName = 'getSegmentsContainingContact';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function isContactInSegment(...$args){

    // hard-typed
    $funcName = 'isContactInSegment';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function compileSegmentsAffectedByContact(...$args){

    // hard-typed
    $funcName = 'compileSegmentsAffectedByContact';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getSegmentConditions(...$args){

    // hard-typed
    $funcName = 'getSegmentConditions';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function updateSegmentCompiled(...$args){

    // hard-typed
    $funcName = 'updateSegmentCompiled';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function addUpdateSegment(...$args){

    // hard-typed
    $funcName = 'addUpdateSegment';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function addUpdateSegmentConditions(...$args){

    // hard-typed
    $funcName = 'addUpdateSegmentConditions';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function addUpdateSegmentCondition(...$args){

    // hard-typed
    $funcName = 'addUpdateSegmentCondition';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function removeSegmentConditions(...$args){

    // hard-typed
    $funcName = 'removeSegmentConditions';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function segmentConditionArgs(...$args){

    // hard-typed
    $funcName = 'segmentConditionArgs';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function segmentBuildDirectOrClause(...$args){

    // hard-typed
    $funcName = 'segmentBuildDirectOrClause';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function compileSegment(...$args){

    // hard-typed
    $funcName = 'compileSegment';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->segments, $funcName)) return call_user_func_array(array($this->segments,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}













public function getLog(...$args){

    // hard-typed
    $funcName = 'getLog';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->logs, $funcName)) return call_user_func_array(array($this->logs,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getLogsForObj(...$args){

    // hard-typed
    $funcName = 'getLogsForObj';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->logs, $funcName)) return call_user_func_array(array($this->logs,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function getLogsForANYObj(...$args){

    // hard-typed
    $funcName = 'getLogsForANYObj';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->logs, $funcName)) return call_user_func_array(array($this->logs,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function addUpdateLog(...$args){

    // hard-typed
    $funcName = 'addUpdateLog';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->logs, $funcName)) return call_user_func_array(array($this->logs,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function deleteLog(...$args){

    // hard-typed
    $funcName = 'deleteLog';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->logs, $funcName)) return call_user_func_array(array($this->logs,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}

public function tidy_log(...$args){

    // hard-typed
    $funcName = 'tidy_log';

    // retrieve backtrace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
    $caller = array_shift($backtrace);

    // log to db, if logging
    $this->v3templogBacktrace($funcName,$caller,$backtrace);

    // return, if available
    if (method_exists($this->logs, $funcName)) return call_user_func_array(array($this->logs,$funcName),func_get_args()); 

    // ultimate fallback
    return false;

}



   // polite flag:
   private function ____MIDDLEMAN_FUNCS_END(){}

/* ======================================================
    / Middle Man funcs (until DAL3.0)
   ====================================================== */

} // / DAL class

