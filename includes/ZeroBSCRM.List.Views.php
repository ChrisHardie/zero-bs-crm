<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.0
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 26/05/16
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

/* ================================================================================
=============================== CONTACTS ======================================= */

function zeroBSCRM_render_customerslist_page(){

    global $zbs;

    #} has no sync ext? Sell them
    $upsellBoxHTML = '';
    
    #} Extrajs:
    $extraJS = ''; 
        
    #} Messages:
    #} All messages need params to match this func: 
    #} ... zeroBSCRM_UI2_messageHTML($msgClass='',$msgHeader='',$msg='',$iconClass='',$id='')
    $messages = array(); 

        // this adds a message if not yet migrated dal
        if (!$zbs->isDAL2()){

            $link = '';

            if (current_user_can( 'manage_options' )) $link = '<br /><br /><a href="'.admin_url('admin.php?page='.$zbs->slugs['migratedb2contacts']).'" class="ui button large blue">'.__('Go to Update',"zero-bs-crm").'</a>';
            $messages[] = array(

                'large info',
                __('Contact Database Update Needed',"zero-bs-crm"),
                __('Your contact information needs an update to work with new database improvements, you will not be able to edit contact information until your contact database has been migrated.',"zero-bs-crm").$link,
                'disabled warning sign',
                'zbsNope'

            );

        }

    $list = new zeroBSCRM_list(array(

            'objType'       => 'customer',
            'singular'      => __('Contact',"zero-bs-crm"),
            'plural'        => __('Contacts',"zero-bs-crm"),
            'tag'           => 'zerobscrm_customertag',
            'postType'      => 'zerobs_customer',
            'postPage'      => 'manage-customers',
            'langLabels'    => array(

                
                // bulk action labels
                'deletecontacts' => __('Delete Contact(s)',"zero-bs-crm"),
                'merge' => __('Merge Contacts',"zero-bs-crm"),
                'export' => __('Export Contact(s)',"zero-bs-crm"),


                // bulk actions - contact deleting
                'andthese' => __('Shall I also delete the associated Invoices, Quotes, Transactions and Events?',"zero-bs-crm"),
                'contactsdeleted' => __('Your contact(s) have been deleted.',"zero-bs-crm"),
                'notcontactsdeleted' => __('Your contact(s) could not be deleted.',"zero-bs-crm"),

                // bulk actions - add/remove tags
                'notags' => __('You do not have any tags, do you want to',"zero-bs-crm").' <a target="_blank" href="'.zbsLink('tags',-1,'zerobs_customer',false,'contact').'">'.__('Add a tag',"zero-bs-crm").'</a>',                
                
                // bulk actions - merge 2 records
                'areyousurethesemerge' => __('Are you sure you want to merge these two contacts into one record, there is no "undo" here.',"zero-bs-crm").'<br />',
                'whichdominant' => __('Which is the "master" record (main record)?',"zero-bs-crm"),

                'contactsmerged' => __('Contacts Merged',"zero-bs-crm"),
                'contactsnotmerged' => __('Contacts could not be successfully merged',"zero-bs-crm"),

                // tel
                'telhome' => __('Home',"zero-bs-crm"),
                'telwork' => __('Work',"zero-bs-crm"),
                'telmob' => __('Mobile',"zero-bs-crm")

            ),
            'bulkActions'   => array('delete','addtag','removetag','merge','export'),
            //'sortables'     => array('id'),
            'unsortables'   => array('tagged','latestlog','editlink','phonelink','totalvalue','hasquote','hasinvoice','transactioncount','invoicecount','quotecount','invoicestotal','quotestotal','transactionstotal'),
            'extraBoxes' => $upsellBoxHTML,
            'extraJS'   => $extraJS,
            'messages'  => $messages
    ));

    $list->drawListView();

}

/* ============================== / CONTACTS ====================================== 
================================================================================ */




/* ================================================================================
=============================== COMPANIES ====================================== */

function zeroBSCRM_render_companyslist_page(){

    global $zbs;

    $list = new zeroBSCRM_list(array(

            'objType'       => 'company',
            'singular'      => __('Company',"zero-bs-crm"),
            'plural'        => __('Companies',"zero-bs-crm"),
            'tag'           => 'zerobscrm_companytag',
            'postType'      => 'zerobs_company',
            'postPage'      => 'manage-companies',
            'langLabels'    => array(

                // bulk action labels
                'deletecompanys' => __('Delete Company(s)',"zero-bs-crm"),
                'addtags' => __('Add tag(s)',"zero-bs-crm"),
                'removetags' => __('Remove tag(s)',"zero-bs-crm"),
                'export' => __('Export',"zero-bs-crm"),

                // bulk actions - company deleting
                'andthese' => __('Shall I also delete the associated Contacts, Invoices, Quotes, Transactions and Events? (This cannot be undone!)',"zero-bs-crm"),                
                'companysdeleted' => __('Your company(s) have been deleted.',"zero-bs-crm"),
                'notcompanysdeleted' => __('Your company(s) could not be deleted.',"zero-bs-crm"),

                // bulk actions - add/remove tags
                'notags' => __('You do not have any tags, do you want to',"zero-bs-crm").' <a target="_blank" href="'.zbsLink('tags',-1,'zerobs_company',false,'company').'">'.__('Add a tag',"zero-bs-crm").'</a>',

            ),
            'bulkActions'   => array('delete','addtag','removetag','export'),
            //default 'sortables'     => array('id'),
            //default 'unsortables'   => array('tagged','latestlog','editlink','phonelink')
    ));

    $list->drawListView();

}

/* ============================== / COMPANIES ===================================== 
================================================================================ */




/* ================================================================================
=============================== QUOTES ========================================= */

function zeroBSCRM_render_quoteslist_page(){

    $list = new zeroBSCRM_list(array(

            'objType'       => 'quote',
            'singular'      => __('Quote',"zero-bs-crm"),
            'plural'        => __('Quotes',"zero-bs-crm"),
            'tag'           => '',
            'postType'      => 'zerobs_quote',
            'postPage'      => 'manage-quotes',
            'langLabels'    => array(

                // bulk action labels
                'markaccepted' => __('Mark Accepted',"zero-bs-crm"),
                'markunaccepted' => __('Unmark Accepted',"zero-bs-crm"),
                'delete' => __('Delete Quote(s)',"zero-bs-crm"),
                'export' => __('Export Quote(s)',"zero-bs-crm"),


                // bulk actions - quote deleting
                'andthese' => __('Shall I also delete the associated Invoices, Quotes, Transactions and Events?',"zero-bs-crm"),
                'quotesdeleted' => __('Your quote(s) have been deleted.',"zero-bs-crm"),
                'notquotesdeleted' => __('Your quote(s) could not be deleted.',"zero-bs-crm"),

                // bulk actions - quote accepting
                'acceptareyousurequotes' => __('Are you sure you want to mark these quotes as accepted?',"zero-bs-crm"),
                'acceptdeleted' => __('Quote(s) Accepted',"zero-bs-crm"),
                'acceptquotesdeleted' => __('Your quote(s) have been marked accepted.',"zero-bs-crm"),
                'acceptnotdeleted' => __('Could not mark accepted!',"zero-bs-crm"),
                'acceptnotquotesdeleted' => __('Your quote(s) could not be marked accepted.',"zero-bs-crm"),

                // bulk actions - quote un accepting
                'unacceptareyousurethese' => __('Are you sure you want to mark these quotes as unaccepted?',"zero-bs-crm"),
                'unacceptdeleted' => __('Quote(s) Unaccepted',"zero-bs-crm"),
                'unacceptquotesdeleted' => __('Your quote(s) have been marked unaccepted.',"zero-bs-crm"),
                'unacceptnotdeleted' => __('Could not mark unaccepted!',"zero-bs-crm"),
                'unacceptnotquotesdeleted' => __('Your quote(s) could not be marked unaccepted.',"zero-bs-crm"),

                // bulk actions - add/remove tags
                'notags' => __('You do not have any tags, do you want to',"zero-bs-crm").' <a target="_blank" href="'.zbsLink('tags',-1,'zerobs_quote',false,'quote').'">'.__('Add a tag',"zero-bs-crm").'</a>',

            ),
            'bulkActions'   => array('markaccepted','markunaccepted','addtag','removetag','delete','export'),
            //default 'sortables'     => array('id'),
            //default 'unsortables'   => array('tagged','latestlog','editlink','phonelink')
    ));

    $list->drawListView();

}

/* =============================== / QUOTES ======================================= 
================================================================================ */




/* ================================================================================
=============================== INVOICES ======================================= */

function zeroBSCRM_render_invoiceslist_page(){

    global $zbs;
    

    #} has no sync ext? Sell them
    $upsellBoxHTML = ''; 

            // WH added: Is now polite to License-key based settings like 'entrepreneur' doesn't try and upsell
            // this might be a bit easy to "hack out" hmmmm
            $bundle = false; if ($zbs->hasEntrepreneurBundleMin()) $bundle = true;
    
            #} has sync ext? Give feedback
            if (!zeroBSCRM_isExtensionInstalled('invpro')){ 

                if (!$bundle){

                    // first build upsell box html
                    $upsellBoxHTML = '<!-- Inv PRO box --><div class="">';
                    $upsellBoxHTML .= '<h4>Invoicing Pro:</h4>';

                        $upTitle = __('Supercharged Invoicing',"zero-bs-crm");
                        $upDesc = __('Get more out of invoicing, like accepting online payments!:',"zero-bs-crm");
                        $upButton = __('Get Invoicing PRO',"zero-bs-crm");
                        $upTarget = $zbs->urls['invpro'];

                        $upsellBoxHTML .= zeroBSCRM_UI2_squareFeedbackUpsell($upTitle,$upDesc,$upButton,$upTarget); 

                    $upsellBoxHTML .= '</div><!-- / Inv PRO box -->';

                } else {

                    // prompt to install
                    $upsellBoxHTML = '<!-- Inv PRO box --><div class="">';
                    $upsellBoxHTML .= '<h4>Invoicing Pro:</h4>';

                        $upTitle = __('Supercharged Invoicing',"zero-bs-crm");
                        $upDesc = __('You have the PRO version of CSV importer available because you are using a bundle. Please download and install:',"zero-bs-crm");
                        $upButton = __('Your Account',"zero-bs-crm");
                        $upTarget = $zbs->urls['account'];

                        $upsellBoxHTML .= zeroBSCRM_UI2_squareFeedbackUpsell($upTitle,$upDesc,$upButton,$upTarget); 

                    $upsellBoxHTML .= '</div><!-- / Inv PRO box -->';
                    
                }

            } else { 

             // later this can point to https://zerobscrm.com/kb/knowledge-base/how-to-get-customers-into-zero-bs-crm/ 
 
                

            } 

    $list = new zeroBSCRM_list(array(

            'objType'       => 'invoice',
            'singular'      => __('Invoice',"zero-bs-crm"),
            'plural'        => __('Invoices',"zero-bs-crm"),
            'tag'           => '',
            'postType'      => 'zerobs_invoice',
            'postPage'      => 'manage-invoices',
            'langLabels'    => array(

                
                // bulk action labels
                'delete' => __('Delete Invoice(s)',"zero-bs-crm"),
                'export' => __('Export Invoice(s)',"zero-bs-crm"),

                // bulk actions - invoice deleting
                'invoicesdeleted' => __('Your invoice(s) have been deleted.',"zero-bs-crm"),
                'notinvoicesdeleted' => __('Your invoice(s) could not be deleted.',"zero-bs-crm"),

                // bulk actions - invoice status update
                'statusareyousurethese' => __('Are you sure you want to change the status on marked invoice(s)?',"zero-bs-crm"),
                'statusupdated' => __('Invoice(s) Updated',"zero-bs-crm"),
                'statusinvoicesupdated' => __('Your invoice(s) have been updated.',"zero-bs-crm"),
                'statusnotupdated' => __('Could not update invoice!',"zero-bs-crm"),
                'statusnotinvoicesupdated' => __('Your invoice(s) could not be updated',"zero-bs-crm"),
                'statusdraft' => __('Draft',"zero-bs-crm"),
                'statusunpaid' => __('Unpaid',"zero-bs-crm"),
                'statuspaid' => __('Paid',"zero-bs-crm"),
                'statusoverdue' => __('Overdue',"zero-bs-crm"),

                // bulk actions - add/remove tags
                'notags' => __('You do not have any tags, do you want to',"zero-bs-crm").' <a target="_blank" href="'.zbsLink('tags',-1,'zerobs_invoice',false,'invoice').'">'.__('Add a tag',"zero-bs-crm").'</a>',


            ),
            'bulkActions'   => array('changestatus','addtag','removetag','delete','export'),
            //default 'sortables'     => array('id'),
            //default 'unsortables'   => array('tagged','latestlog','editlink','phonelink'),
            'extraBoxes' => $upsellBoxHTML
    ));

    $list->drawListView();

}

/* ============================== / INVOICES ===================================== 
================================================================================ */




/* ================================================================================
========================= TRANSACTIONS ========================================= */

function zeroBSCRM_render_transactionslist_page(){

    $list = new zeroBSCRM_list(array(

            'objType'       => 'transaction',
            'singular'      => __('Transaction',"zero-bs-crm"),
            'plural'        => __('Transactions',"zero-bs-crm"),
            'tag'           => 'zerobscrm_transactiontag',
            'postType'      => 'zerobs_transaction',
            'postPage'      => 'manage-transactions',
            'langLabels'    => array(

                // bulk action labels
                'delete' => __('Delete Transaction(s)',"zero-bs-crm"),
                'export' => __('Export Transaction(s)',"zero-bs-crm"),

                // bulk actions - add/remove tags
                'notags' => __('You do not have any tags, do you want to',"zero-bs-crm").' <a target="_blank" href="'.zbsLink('tags',-1,'zerobs_transaction',false,'transaction').'">'.__('Add a tag',"zero-bs-crm").'</a>',                
               
                // statuses
                'trans_status_cancelled' => __('Cancelled',"zero-bs-crm"),
                'trans_status_hold' => __('Hold',"zero-bs-crm"),
                'trans_status_pending' => __('Pending',"zero-bs-crm"),
                'trans_status_processing' => __('Processing',"zero-bs-crm"),
                'trans_status_refunded' => __('Refunded',"zero-bs-crm"),
                'trans_status_failed' => __('Failed',"zero-bs-crm"),
                'trans_status_completed' => __('Completed',"zero-bs-crm"),
                'trans_status_succeeded' => __('Succeeded',"zero-bs-crm"),

            ),
            'bulkActions'   => array('addtag','removetag','delete','export'),
            'sortables'     => array('id'),
            'unsortables'   => array('tagged','latestlog','editlink','phonelink')
    ));

    $list->drawListView();

}

/* ============================ / TRANSACTIONS ==================================== 
================================================================================ */




/* ================================================================================
================================ FORMS ========================================= */

function zeroBSCRM_render_formslist_page(){


    

    #} has no sync ext? Sell them
    $upsellBoxHTML = ''; 
    
            #} has sync ext? Give feedback
            if (!zeroBSCRM_hasPaidExtensionActivated()){ 

                ##WLREMOVE
                // first build upsell box html
                $upsellBoxHTML = '<!-- Forms PRO box --><div class="">';
                $upsellBoxHTML .= '<h4>Need More Complex Forms?</h4>';

                    $upTitle = __('Fully Flexible Forms',"zero-bs-crm");
                    $upDesc = __('Zero BS CRM forms cover simple use contact and subscription forms, but if you need more we suggest using a form plugin like Contact Form 7 or Gravity Forms:',"zero-bs-crm");
                    $upButton = __('See Full Form Options',"zero-bs-crm");
                    $upTarget = 'https://zerobscrm.com/feature/forms/#benefit';

                    $upsellBoxHTML .= zeroBSCRM_UI2_squareFeedbackUpsell($upTitle,$upDesc,$upButton,$upTarget); 

                $upsellBoxHTML .= '</div><!-- / Inv Forms box -->';
                ##/WLREMOVE
            }

    $list = new zeroBSCRM_list(array(

            'objType'       => 'form',
            'singular'      => __('Form',"zero-bs-crm"),
            'plural'        => __('Forms',"zero-bs-crm"),
            'tag'           => '',
            'postType'      => 'zerobs_form',
            'postPage'      => 'manage-forms',
            'langLabels'    => array(

                'naked' => __('Naked',"zero-bs-crm"),
                'cgrab' => __('Content Grab',"zero-bs-crm"),
                'simple' => __('Simple',"zero-bs-crm"),

                // bulk action labels
                'delete' => __('Delete Form(s)',"zero-bs-crm"),
                'export' => __('Export Form(s)',"zero-bs-crm"),


                // bulk actions - deleting
                'formsdeleted' => __('Your form(s) have been deleted.',"zero-bs-crm"),
                'notformsdeleted' => __('Your form(s) could not be deleted.',"zero-bs-crm"),

            ),
            'bulkActions'   => array('delete'),
            //default 'sortables'     => array('id'),
            //default 'unsortables'   => array('tagged','latestlog','editlink','phonelink')
            'extraBoxes' => $upsellBoxHTML
    ));

    $list->drawListView();

}

/* ============================= / FORMS ========================================== 
================================================================================ */



/* ================================================================================
=============================== SEGMENTS ======================================= */

function zeroBSCRM_render_segmentslist_page(){

    global $zbs;

    #} has no sync ext? Sell them
    $upsellBoxHTML = '';
    
            #} has mail ext? Give feedback
            if (!zeroBSCRM_isExtensionInstalled('mailcampaigns')){ 


                // first build upsell box html
                $upsellBoxHTML = '<div class="">';
                $upsellBoxHTML .= '<h4>'.__('Using Segments','zero-bs-crm').':</h4>';

                        $upTitle = __('Segment like a PRO',"zero-bs-crm");
                        $upDesc = __('Did you know that we\'ve made segments more advanced?',"zero-bs-crm");
                        $upButton = __('See Advanced Segments',"zero-bs-crm");
                        $upTarget = $zbs->urls['advancedsegments'];

                        $upsellBoxHTML .= zeroBSCRM_UI2_squareFeedbackUpsell($upTitle,$upDesc,$upButton,$upTarget); 

                $upsellBoxHTML .= '</div>';

            } else { 

             // later this can point to https://zerobscrm.com/kb/knowledge-base/how-to-get-customers-into-zero-bs-crm/ 
 
                $upsellBoxHTML = '<div class="">';
                $upsellBoxHTML .= '<h4>'.__('Got Feedback?','zero-bs-crm').':</h4>';

                        $upTitle = __('Enjoying segments?',"zero-bs-crm");
                        $upDesc = __('This segment feature is new in v2.86, we\'re looking for feedback!',"zero-bs-crm");
                        $upButton = __('Send Feedback',"zero-bs-crm");
                        $upTarget = "mailto:hello@zerobscrm.com?subject='Segments%20Feedback'";

                        $upsellBoxHTML .= zeroBSCRM_UI2_squareFeedbackUpsell($upTitle,$upDesc,$upButton,$upTarget); 
                
                $upsellBoxHTML .= '</div>';

            }

    // pass this for filter links
    $extraJS = ''; if (zeroBSCRM_getSetting('filtersfromsegments') == "1"){ $extraJS = " var zbsSegmentViewStemURL = '".zbsLink($zbs->slugs['managecontacts']).'&quickfilters=segment_'."';"; }
        

    $list = new zeroBSCRM_list(array(

            'objType'       => 'segment',
            'singular'      => __('Segment',"zero-bs-crm"),
            'plural'        => __('Segments',"zero-bs-crm"),
            'tag'           => '',
            'postType'      => 'segment',
            'postPage'      => $zbs->slugs['segments'],
            'langLabels'    => array(

                // compiled language
                'lastCompiled' => __('Last Compiled',"zero-bs-crm"),
                'notCompiled' => __('Not Compiled',"zero-bs-crm"),
                
                // bulk action labels
                'deletesegments' => __('Delete Segment(s)',"zero-bs-crm"),
                'export' => __('Export Segment(s)',"zero-bs-crm"),

                // bulk actions - segment deleting
                'segmentsdeleted' => __('Your segment(s) have been deleted.',"zero-bs-crm"),
                'notsegmentsdeleted' => __('Your segment(s) could not be deleted.',"zero-bs-crm"),

            ),
            'bulkActions'   => array('delete'),
            //'sortables'     => array('id'),
            'unsortables'   => array('audiencecount','action','added'),
            'extraBoxes' => $upsellBoxHTML,
            'extraJS' => $extraJS
    ));

    $list->drawListView();

}

/* ============================== / SEGMENTS ====================================== 
================================================================================ */



/* ================================================================================
================================ QUOTETEMPLATES ================================ */

function zeroBSCRM_render_quotetemplateslist_page(){


    #} has no sync ext? Sell them
    $upsellBoxHTML = ''; 

    $list = new zeroBSCRM_list(array(

            'objType'       => 'quotetemplate',
            'singular'      => __('Quote Template',"zero-bs-crm"),
            'plural'        => __('Quote Templates',"zero-bs-crm"),
            'tag'           => '',
            'postType'      => 'zerobs_quo_template',
            'postPage'      => 'manage-quote-templates',
            'langLabels'    => array(

                // bulk action labels
                'delete' => __('Delete Quote Template(s)',"zero-bs-crm"),
                'export' => __('Export Quote Template(s)',"zero-bs-crm"),

                // for listview
                'defaulttemplate' => __('Default Template','zero-bs-crm'),
                'deletetemplate' => __('Delete Template','zero-bs-crm'),

                // bulk actions - quote template deleting
                'quotetemplatesdeleted' => __('Your Quote template(s) have been deleted.',"zero-bs-crm"),
                'notquotetemplatesdeleted' => __('Your Quote template(s) could not be deleted.',"zero-bs-crm"),

            ),
            'bulkActions'   => array('delete'),
            'extraBoxes' => $upsellBoxHTML
    ));

    $list->drawListView();

}

/* ============================= / QUOTETEMPLATES =================================
================================================================================ */

