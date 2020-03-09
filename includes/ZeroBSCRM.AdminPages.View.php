<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.72+
 *
 * Copyright 2018, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 18/04/18
 */


/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

/*
  Exposes generic php-js for view pages (lang labels)
*/
function zeroBSCRM_pages_admin_view_page_js(){

  ?><script>
        var zbsViewLang = {

            'error': '<?php echo zeroBSCRM_slashOut(__('Error','zero-bs-crm')); ?>',
            'unabletodelete': '<?php echo zeroBSCRM_slashOut(__('Unable to delete this file.','zero-bs-crm')); ?>'
        };
    </script><?php
}

/*
  View Contact Page
*/
function zeroBSCRM_pages_admin_view_page_contact($id = -1){

  // generic php-js for view pages (lang labels)
  zeroBSCRM_pages_admin_view_page_js();

  if (!empty($id) && $id > 0){

      global $zbs;

      $useQuotes    = zeroBSCRM_getSetting('feat_quotes');
      $useInvoices  = zeroBSCRM_getSetting('feat_invs');
      $useTrans     = zeroBSCRM_getSetting('feat_transactions');
      $useTasks = false; if ($zbs->isDAL3()) $useTasks = zeroBSCRM_getSetting('feat_calendar'); // v3+


      #} get our single contact info
      $zbsCustomer = zeroBS_getCustomer($id, true,true,true);
      // debug echo 'contact<pre>'; print_r($zbsCustomer); echo '</pre>'; exit();
      $customerActions = zeroBS_contact_actions($id,$zbsCustomer);

      // if customer id provided, but no obj, don't load any further.
      // this matches the ghost-record treatment in the single edit.php class
      if (!is_array($zbsCustomer)){

            // brutal hide, then msg #ghostrecord
            ?><style type="text/css">#zbs-edit-save, #zbs-nav-view, #zbs-nav-prev, #zbs-nav-next { display:none; }</style>
            <div id="zbs-edit-warnings-wrap"><?php
            echo zeroBSCRM_UI2_messageHTML('warning','Error Retrieving Contact','There does not appear to be a Contact with this ID.','disabled warning sign','zbsCantLoadData');  
            ?></div><?php  
            return false;

      }

      #} moved socials to up here. So it's nicer placed in the profile
      // socials
      global $zbsSocialAccountTypes;
      $zbsSocials = zeroBS_getCustomerSocialAccounts($id);
      // empty empties.. hmmm
      $zbsSocialsProper = array(); if (is_array($zbsSocials) && count($zbsSocials) > 0) foreach ($zbsSocials as $zbsSocialKey => $zbsSocialAcc) if (!empty($zbsSocialAcc)) $zbsSocialsProper[$zbsSocialKey] = $zbsSocialAcc;
      $zbsSocials = $zbsSocialsProper; unset($zbsSocialsProper);
      

              #} get our single custmoer info
              //DAL3?
              if ($zbs->isDAL3()){
                $zbsCustomer = $zbs->DAL->contacts->getContact($id,array(
                    'withCustomFields'  => true,
                    'withQuotes'        => true,
                    'withInvoices'      => true,
                    'withTransactions'  => true,
                    'withTasks'         => true,
                    'withLogs'          => true,
                    'withLastLog'       => false,
                    'withTags'          => true,
                    'withCompanies'     => true,
                    'withOwner'         => true,
                    'withValues'        => true, 
                  ));
              } else {
                $zbsCustomer = zeroBS_getCustomer($id, true,true,true);
              }

              // debug echo '<pre>'; print_r($zbsCustomer); echo '</pre><hr>';

              $customerActions = zeroBS_contact_actions($id,$zbsCustomer);

              $contactEmail = ''; if (isset($zbsCustomer['email'])) $contactEmail = $zbsCustomer['email'];


              // avatar mode
              $avatarMode = zeroBSCRM_getSetting('avatarmode'); $avatar = '';
              if ($avatarMode !== "3") $avatar = zeroBS_customerAvatarHTML($id,$zbsCustomer,100,'ui small image centered'); 

              // Moved into Menus.learn.php for cleaner/more common sense output html 
              //echo '<div style="position: absolute;top: -40px;right: 7px;">';
              //echo zeroBSCRM_getObjNav($id,'view','CONTACT');
              //echo '</div>';

              // check flags (atm this is just 'do-not-email' 2.90+)
              $contactFlags = array(); if ($zbs->DAL->contacts->getContactDoNotMail($id)) $contactFlags[] = 'do-not-email';

              ?>
              <div class="ui divided grid" style="margin-top:-1em;">

                <div class="ten wide column" id="zbs-customer-panel">

                  <div class="ui segment grid">

                  <?php # based on avatar/no avatar, subtle diff design here:
                  if ($avatarMode == "3" || empty($avatar)){
                    
                      // 1 column, no avatar card
                      ?><div class="sixteen wide column zbs-view-card"><?php

                  } else {

                      // normal, 2 column 'contact card'
                      ?><div class="three wide column" style="text-align:center">
                          <?php echo $avatar; ?>
                            <a class="ui button green" style="margin-top:0.8em" href="<?php echo zbsLink('edit',$id,'zerobs_customer',false);?>">
                                <?php _e("Edit Contact", "zero-bs-crm"); ?>
                            </a>

                        </div>
                        <div class="thirteen wide column zbs-view-card"><?php


                  } ?>

                          <h3>
                            <?php echo zeroBS_customerName('',$zbsCustomer,false,false); ?>
                            <?php #} When no avatar, show edit button top right
                              if ($avatarMode == "3" || empty($avatar)){
                                ?><a class="ui button green right floated" style="margin-top:0.8em" href="<?php echo zbsLink('edit',$id,'zerobs_customer',false);?>">
                                      <?php _e("Edit Contact", "zero-bs-crm"); ?>
                                  </a><?php
                              }
                            ?>
                          </h3>
                          <p class="zbs-email">
                            <?php zeroBSCRM_html_sendemailto($id,$contactEmail,false); ?>
                          </p>

                          <div class="zbs-social-buttons">
                              <?php 
                                if (count($zbsSocialAccountTypes) > 0 && count($zbsSocials) > 0){ 
                                  foreach ($zbsSocialAccountTypes as $socialKey => $socialAccType){
                                     if (is_array($zbsSocials) && isset($zbsSocials[$socialKey]) && !empty($zbsSocials[$socialKey])){ 
                                            // got acc? link to it
                                            $socialLink = zeroBSCRM_getSocialLink($socialKey,$zbsSocials);
                                            //added so it outputs tw => twitter, fb => facebook
                                            $semanticSocial = zeroBSCRM_getSocialIcon($socialKey);
                                            ?>
                                            <a class="ui mini <?php echo $semanticSocial; ?> button" href="<?php echo $socialLink; ?>" target="_blank" title="<?php echo __('View',"zero-bs-crm").' '.$zbsSocials[$socialKey]; ?>"><i class="large middle aligned ui <?php echo zeroBSCRM_faSocialToSemantic($socialAccType['fa']); ?>" aria-hidden="true" style="padding-top:0"></i></a>
                                        <?php } 
                                  }
                                }
                              ?>
                          </div>
                          <div class='clear'></div>


                          <p class="zbs-sentence">
                            <?php echo zeroBSCRM_html_contactIntroSentence($zbsCustomer); ?>
                          </p>


                          <?php # https://codepen.io/kyleshockey/pen/bdeLrE 
                          if (count($customerActions) > 0) { ?>
                          <div class="action-wrap">
                            <div class="ui green basic dropdown action-button"><?php _e('Contact Actions',"zero-bs-crm"); ?><i class="dropdown icon"></i>
                               <div class="menu">
                                <?php foreach ($customerActions as $actKey => $action){ ?>
                                   <div class="item zbs-contact-action" id="zbs-contact-action-<?php echo $actKey; ?>"<?php
                                    // if url isset, pass that data-action, otherwise leave for js to attach to
                                    if (isset($action['url']) && !empty($action['url'])){ 
                                      ?> data-action="<?php if (isset($action['url'])) echo 'url'; ?>" data-url="<?php if (isset($action['url'])) echo $action['url']; ?>"<?php
                                    }

                                    // got extra attributes?
                                    if (isset($action['extraattr']) && is_array($action['extraattr'])){

                                          // dump extra attr into item
                                          foreach ($action['extraattr'] as $k => $v){
                                              echo ' data-'.$k.'="'.$v.'"';
                                          }

                                    } ?>>
                                     <?php 

                                        // got ico?
                                        if (isset($action['ico'])) echo '<i class="'.$action['ico'].'"></i>';

                                        // got text?
                                        if (isset($action['label'])) echo $action['label'];

                                    ?>
                                   </div>
                                <?php } ?>
                                </div>
                           </div>
                         </div>
                         <?php } ?>


                      </div>
                  </div>

                <?php #DEBUG echo '<pre>'; print_r($zbsCustomer); echo '</pre><hr>'; ?>

                  

                  <!-- customer vitals -->
                  <?php

                    // prep
                    $statusStr = ''; if (isset($zbsCustomer) && isset($zbsCustomer['status']) && !empty($zbsCustomer['status'])) $statusStr = $zbsCustomer['status'];
                  
                    // compiled addr str
                    $addrStr = ''; if (isset($zbsCustomer)) $addrStr = zeroBS_customerAddr($zbsCustomer['id'],$zbsCustomer,'full','<br />');
                    $addr2Str = ''; if (isset($zbsCustomer)) $addr2Str = zeroBS_customerSecondAddr($zbsCustomer['id'],$zbsCustomer,'full','<br />');

                    // tels?
                    $tels = array(); 
                    if (isset($zbsCustomer) && isset($zbsCustomer['hometel']) && !empty($zbsCustomer['hometel'])) $tels['hometel'] = $zbsCustomer['hometel'];
                    if (isset($zbsCustomer) && isset($zbsCustomer['worktel']) && !empty($zbsCustomer['worktel'])) $tels['worktel'] = $zbsCustomer['worktel'];
                    if (isset($zbsCustomer) && isset($zbsCustomer['mobtel']) && !empty($zbsCustomer['mobtel'])) $tels['mobtel'] = $zbsCustomer['mobtel'];

                    // values - DAL3 we get them passed all nicely :)
                    $contactTotalValue = 0; if (isset($zbsCustomer['total_value'])) $contactTotalValue = $zbsCustomer['total_value'];
                    $contactQuotesValue = 0; if (isset($zbsCustomer['quotes_total'])) $contactQuotesValue = $zbsCustomer['quotes_total'];
                    $contactInvoicesValue = 0; if (isset($zbsCustomer['invoices_total'])) $contactInvoicesValue = $zbsCustomer['invoices_total'];
                    $contactTransactionsValue = 0; if (isset($zbsCustomer['transactions_total'])) $contactTransactionsValue = $zbsCustomer['transactions_total'];

                    // pre dal 3 did this way
                    if (!$zbs->isDAL3()){

                      // calc'd each individually
                      $contactTotalValue = zeroBS_customerTotalValue($id, $zbsCustomer['invoices'], $zbsCustomer['transactions']);
                      $contactQuotesValue = zeroBS_customerQuotesValue($id, $zbsCustomer['quotes']);
                      $contactInvoicesValue = zeroBS_customerInvoicesValue($id, $zbsCustomer['invoices']);
                      $contactTransactionsValue = zeroBS_customerTransactionsValue($id, $zbsCustomer['transactions']);

                    }

                    // retrieve any additional tabs peeps have prepared
                    $zbsContactVitalTabs = apply_filters( 'zbs-contact-vital-tabs', array(), $id );

                  ?>

                  <div id="zbs-vital-menu">
                    <div class="ui top attached tabular menu">
                      <div data-tab="vitals" class="<?php if (!isset($activeVitalsTab)) { echo 'active '; $activeVitalsTab = 'vitals'; } ?>item"><?php 

                          // custom title e.g. Lead Vitals                                
                          if (!empty($statusStr)) 
                            echo $statusStr; 
                          else
                            _e('Contact',"zero-bs-crm");
                           
                           echo ' '.__("Vitals","zero-bs-crm"); 

                          ?></div>
                      <?php if (count($zbsSocialAccountTypes) > 0 && count($zbsSocials) > 0){ ?>
                        <div class="zbs-hide" data-tab="social" id="contact-tab-social" class="<?php if (!isset($activeVitalsTab)) { echo 'active '; $activeVitalsTab = 'social'; } ?>item"><?php _e('Social',"zero-bs-crm"); ?></div>                      
                      <?php } ?>
                      <?php #} Any integrated tabs - via filter zbs-contact-vital-tabs
                      if (is_array($zbsContactVitalTabs) && count($zbsContactVitalTabs) > 0){
                        $tabIndx = 1;
                        foreach ($zbsContactVitalTabs as $tab){

                          $tabName = __('Untitled Tab',"zero-bs-crm");
                          $tabID = 'zbs-contact-tab-'.$tabIndx;

                          if (is_array($tab) && isset($tab['name'])) $tabName = $tab['name'];
                          if (is_array($tab) && isset($tab['id'])) $tabID = $tab['id'];
                          
                          ?><div data-tab="<?php echo $tabID; ?>" class="item" id="contact-tab-<?php echo $tabID ?>"><?php echo $tabName; ?></div><?php

                          $tabIndx++;

                        }

                      } ?>
                      <?php if (!empty($statusStr)) { ?>
                      <div class="right menu item">
                        <?php _e("Status","zero-bs-crm");?>: 
                        <span class="ui green label"><?php echo $statusStr; ?></span>
                      </div>
                      <?php } ?>
                    </div>

                    <div class="ui bottom attached active tab segment" data-tab="vitals" id="zbs-contact-view-vitals">
                        <table class="ui fixed single line celled table">
                          <tbody>
                            <tr class="zbs-view-vital-totalvalue">
                              <td class="zbs-view-vital-label"><strong><?php _e("Total Value","zero-bs-crm");?><i class="circle info icon link" data-content="<?php _e("Total Value is all transaction types and any unpaid invoices","zero-bs-crm");?>" data-position="bottom center"></i></strong></td>
                              <td><strong><?php echo zeroBSCRM_formatCurrency($contactTotalValue); ?></strong></td>
                            </tr>
                            <?php if ($useQuotes == "1"){ ?>
                            <tr>
                              <td class="zbs-view-vital-label"><?php _e("Quotes","zero-bs-crm"); ?> <i class="circle info icon link" data-content="<?php _e("Quotes: This shows the total sum of your quotes & count.","zero-bs-crm");?>" data-position="bottom center"></i></td>
                              <td>
                                <?php if (count($zbsCustomer['quotes']) > 0)
                                        echo zeroBSCRM_formatCurrency($contactQuotesValue).' ('.count($zbsCustomer['quotes']).')';
                                      else
                                        _e('None',"zero-bs-crm"); ?>
                              </td>
                            </tr>
                            <?php } ?>
                            <?php if ($useInvoices == "1"){ ?>
                            <tr class="zbs-view-vital-invoices">
                              <td class="zbs-view-vital-label"><?php _e("Invoices","zero-bs-crm"); ?> <i class="circle info icon link" data-content="<?php _e("Invoices: This shows the total sum of your invoices & count.","zero-bs-crm");?>" data-position="bottom center"></i></td>
                              <td>
                                <?php if (count($zbsCustomer['invoices']) > 0)
                                        echo zeroBSCRM_formatCurrency($contactInvoicesValue).' ('.count($zbsCustomer['invoices']).')';
                                      else
                                        _e('None',"zero-bs-crm"); ?>
                              </td>
                            </tr>
                            <?php } ?>
                            <?php if ($useTrans == "1"){ ?>
                            <tr class="zbs-view-vital-transactions">
                              <td class="zbs-view-vital-label"><?php _e("Transactions","zero-bs-crm"); ?> <i class="circle info icon link" data-content="<?php _e("Transactions Total & count: This shows the sum of your succeeded transactions (set in settings)","zero-bs-crm");?>" data-position="bottom center"></i></td>
                              <td>
                                <?php if (count($zbsCustomer['transactions']) > 0)
                                        echo zeroBSCRM_formatCurrency($contactTransactionsValue).' ('.count($zbsCustomer['transactions']).')';
                                      else
                                        _e('None',"zero-bs-crm"); ?>
                              </td>
                            </tr>
                            <?php } ?>
                            <tr class="zbs-view-vital-source">
                              <td class="zbs-view-vital-label"><?php _e("Source","zero-bs-crm");?></td>
                              <td>
                                <?php
                                $zeroBSCRMsource = zeroBS_getExternalContactSource($id);
                                //MS? not sure what import meant to be here: if (isset($zeroBSCRMsource) && isset($zeroBSCRMsource['meta']) && isset($zeroBSCRMsource['meta']['import'])) echo $zeroBSCRMsource['meta']['import'];
                                if (isset($zeroBSCRMsource) && isset($zeroBSCRMsource['source']) && isset($zeroBSCRMsource['uid'])) 
                                    echo zeroBS_getExternalSourceTitle($zeroBSCRMsource['source'],$zeroBSCRMsource['uid']);
                                else
                                  _e('Manually Added',"zero-bs-crm");

                                 ?></td>
                            </tr>
                            <?php /* IF IN B2B MODE show co here too */

                              $b2bMode = zeroBSCRM_getSetting('companylevelcustomers');
                              if($b2bMode){ ?><tr class="zbs-view-vital-b2b">
                              <td class="zbs-view-vital-label"><?php _e("Company","zero-bs-crm");?></td>
                              <td>
                                <?php 

                                  // companies where this contact is linked to
                                  $contactStr = zeroBSCRM_html_linkedContactCompanies($id,(isset($zbsCustomer['companies']) ? $zbsCustomer['companies'] : false));

                                  if (!empty($contactStr))
                                    echo $contactStr;
                                  else
                                    __('No Company on File',"zero-bs-crm");

                                ?>
                              </td>
                            </tr>
                            <?php } ?>
                            <tr class="zbs-view-vital-address">
                              <td class="zbs-view-vital-label"><?php _e("Address Details","zero-bs-crm");?></td>
                              <td>
                                <?php 

                                      if (!empty($addrStr) && empty($addr2Str))
                                          echo $addrStr;
                                      else if (!empty($addrStr) && !empty($addr2Str)){
                                          ?><div class="ui grid">
                                            <div class="eight wide column">
                                                <h4 class="ui dividing header" style="margin-bottom: 0.6em;"><?php _e('Main address',"zero-bs-crm"); ?></h4>
                                                <?php echo $addrStr; ?>
                                            </div>
                                            <div class="eight wide column">
                                                <h4 class="ui dividing header" style="margin-bottom: 0.6em;"><?php _e('Secondary address',"zero-bs-crm"); ?></h4>
                                                <?php echo $addr2Str; ?>
                                            </div>
                                          </div><?php
                                      } else _e('No Address on File',"zero-bs-crm"); ?></td>
                            </tr>
                            <tr class="zbs-view-vital-telephone">
                              <td class="zbs-view-vital-label"><?php _e("Telephone Contacts","zero-bs-crm");?></td>
                              <td>
                                <?php 


                                      if (count($tels) > 0){

                                        // Click 2 call?
                                        $click2call = $zbs->settings->get('clicktocall');

                                        ?><div class="ui horizontal list"><?php

                                          foreach ($tels as $telKey => $telNo){ ?>
                                          <div class="item">
                                            <?php switch ($telKey){

                                              case 'hometel': 
                                                  echo '<i class="large phone icon"></i>';
                                                  break;
                                              case 'worktel':
                                                  echo '<i class="large phone square icon"></i>'; 
                                                  break;
                                              case 'mobtel':
                                                  echo '<i class="large mobile icon"></i>'; 
                                                  break;

                                            } ?>
                                            <div class="content">
                                              <?php if ($click2call == "1") { ?>
                                              <a class="ui small button" href="<?php echo zeroBSCRM_clickToCallPrefix().$telNo; ?>" title="<?php _e('Call',"zero-bs-crm").' '.$telNo; ?>"><?php echo $telNo; ?></a>
                                              <?php } else { ?>
                                              <div class="header"><?php echo $telNo; ?></div>
                                              <?php } ?>
                                            </div>
                                          </div>
                                          <?php } ?>

                                        </div><?php


                                      } else _e('No phone number on File',"zero-bs-crm"); ?></td>
                            </tr>
                            <?php

                              if (is_array($contactFlags) && count($contactFlags) > 0){
                                ?>
                            <tr class="zbs-view-vital-flags">
                              <td class="zbs-view-vital-label"><?php _e("Flags","zero-bs-crm");?></td>
                              <td>
                                <?php
                                
                                foreach ($contactFlags as $flag){

                                  switch ($flag){

                                    case 'do-not-email':
                                      echo zeroBSCRM_UI2_label('red','<i class="bell slash outline icon"></i>',__('Email Unsubscribed','zero-bs-crm'),__('(Do Not Email Flag)','zero-bs-crm'),'do-not-email');
                                      break;

                                  }

                                }

                                ?>
                              </td>
                            </tr><?php

                              }

                            ?>
                          </tbody>
                        </table>

                    </div>

                      <?php #} Any integrated tabs - via filter zbs-contact-vital-tabs
                      if (is_array($zbsContactVitalTabs) && count($zbsContactVitalTabs) > 0){
                        $tabIndx = 1;
                        foreach ($zbsContactVitalTabs as $tab){

                          $tabID = 'zbs-contact-tab-'.$tabIndx;
                          if (is_array($tab) && isset($tab['id'])) $tabID = $tab['id'];
                          
                          ?><div class="ui bottom attached tab segment" data-tab="<?php echo $tabID; ?>" id="zbs-contact-view-vitals-<?php echo $tabID; ?>">
                          <?php #} Content
                          if (is_array($tab) && isset($tab['contentaction'])){

                              // calls the users function name, if they opted for that instead of content
                              call_user_func($tab['contentaction'],$id);

                          } else if (is_array($tab) && isset($tab['content'])) echo $tab['content']; ?>
                          </div><?php

                          $tabIndx++;

                        }

                      } ?>                      
                    <!-- / customer vitals -->


                    <?php if (count($zbsSocialAccountTypes) > 0 && count($zbsSocials) > 0){ ?>
                    <div class="ui bottom attached tab segment" data-tab="social" id="zbs-contact-view-social">
                      <?php


                          if (count($zbsSocialAccountTypes) > 0){
                            
                            ?><div class="ui relaxed divided large list"><?php


                            foreach ($zbsSocialAccountTypes as $socialKey => $socialAccType){

                              ?><div class="item zbs-social-acc <?php echo $socialAccType['slug']; ?>" title="<?php echo $socialAccType['name']; ?>">
                                  <?php if (is_array($zbsSocials) && isset($zbsSocials[$socialKey]) && !empty($zbsSocials[$socialKey])){ 

                                      // got acc? link to it
                                      $socialLink = zeroBSCRM_getSocialLink($socialKey,$zbsSocials);

                                      ?>
                                      <i class="large middle aligned <?php echo zeroBSCRM_faSocialToSemantic($socialAccType['fa']); ?>" aria-hidden="true" style="padding-top:0"></i>
                                      <div class="content middle aligned">
                                        <a href="<?php echo $socialLink; ?>" target="_blank" title="<?php echo __('View',"zero-bs-crm").' '.$zbsSocials[$socialKey]; ?>" class="header"><?php echo $zbsSocials[$socialKey]; ?></a>
                                      </div>
                                  <?php } ?>
                              </div><?php

                            }

                            ?></div><?php

                          }

                      ?>
                    </div><!-- / customer socials -->
                    <?php } ?>
                  </div>
            

                  <h4 class="ui horizontal header divider">
                    <i class="archive icon"></i>
                    <?php _e('Documents',"zero-bs-crm"); ?>
                  </h4>

                  <div id="zbs-doc-menu">
                    <div class="ui top attached tabular menu">
                      <?php if ($useQuotes == "1"){ ?><div data-tab="quotes" class="<?php if (!isset($activeTab)) { echo 'active '; $activeTab = 'quotes'; } ?>item"><?php _e('Quotes',"zero-bs-crm"); ?></div><?php } ?>
                      <?php if ($useInvoices == "1"){ ?><div data-tab="invoices" class="<?php if (!isset($activeTab)) { echo 'active '; $activeTab = 'invoices'; } ?>item"><?php _e('Invoices',"zero-bs-crm"); ?></div><?php } ?>
                        <?php if ($useTrans == "1"){ ?><div data-tab="transactions" class="<?php if (!isset($activeTab)) { echo 'active '; $activeTab = 'transactions'; } ?>item"><?php _e('Transactions',"zero-bs-crm"); ?></div><?php } ?>
                      <div data-tab="files" class="<?php if (!isset($activeTab)) { echo 'active '; $activeTab = 'files'; } ?>item"><?php _e('Files','zero-bs-crm'); ?></div>
                      <?php if ($useTasks == "1"){ ?><div data-tab="tasks" class="<?php if (!isset($activeTab)) { echo 'active '; $activeTab = 'tasks'; } ?>item"><?php _e('Tasks',"zero-bs-crm"); ?></div><?php } ?>
                    </div>
                    <?php if ($useQuotes == "1"){ ?>
                    <div class="ui bottom attached <?php if ($activeTab == 'quotes') echo 'active '; ?>tab segment" data-tab="quotes">
                        <table class="ui celled table unstackable">
                              <thead>
                                  <th><?php _e("ID & Title","zero-bs-crm"); ?></th>
                                  <th><?php _e("Date","zero-bs-crm"); ?></th>
                                  <th><?php _e("Value","zero-bs-crm"); ?></th>
                                  <th><?php _e("Status","zero-bs-crm"); ?></th>
                              </thead>
                              <tbody>
                                <?php
                                if (count($zbsCustomer['quotes']) > 0){

                                  foreach($zbsCustomer['quotes'] as $quote){
                                    
                                    $quoteValue = '-'; 

                                    // DAL3 change of field name
                                    if ($zbs->isDAL3()){

                                        // 3.0
                                        $idRefStr = ''; 
                                        if (isset($quote['id'])) $idRefStr = '#'.$quote['id'];
                                        if (isset($quote['id_override'])) {
                                          if (!empty($idRefStr)) $idRefStr .= ' -';
                                          $idRefStr .= ' '.$quote['id_override'];
                                        }

                                        $quoteURL = zbsLink('edit',$quote['id'],ZBS_TYPE_QUOTE);

                                        $quoteValue = $quote['value'];

                                        $quoteStatus = $quote['status'];

                                    } else {

                                      $idRefStr = ''; 
                                      if (isset($quote['zbsid'])) $idRefStr = '#'.$quote['zbsid'];
                                      if (isset($quote['meta']) && isset($quote['meta']['ref'])) {
                                        if (!empty($idRefStr)) $idRefStr .= ' -';
                                        $idRefStr .= ' '.$quote['meta']['ref'];
                                      }

                                      $quoteURL = zbsLink('edit',$quote['id'],ZBS_TYPE_QUOTE);//admin_url('post.php?action=edit&post='.$quote['id']);

                                      if (isset($quote['meta']['val'])) $quoteValue = $quote['meta']['val'];

                                    }

                                    if ($quoteValue != '-' && !empty($quoteValue)) $quoteValue = zeroBSCRM_formatCurrency($quoteValue);


                                    echo "<tr>";
                                      echo '<td><a href="'.$quoteURL.'">' . $idRefStr . "</a></td>";
                                      echo "<td>" . zeroBSCRM_html_QuoteDate($quote) . "</td>";
                                      echo "<td>" . $quoteValue . "</td>";
                                      echo "<td><span class='".zeroBSCRM_html_quoteStatusLabel($quote)."'>" . zeroBS_getQuoteStatus($quote,false) . "</span></td>";
                                    echo "</tr>"; 
                                  }

                                } else {

                                  // empty, create?
                                  $newQuoteURL = zbsLink('create',-1,ZBS_TYPE_QUOTE).'&zbsprefillcust='.$zbsCustomer['id'];

                                  ?><tr>
                                      <td colspan="4">
                                          <div class="ui info icon message" id="zbsNoQuoteResults">
                                            <div class="content">
                                              <div class="header"><?php _e('No Quotes',"zero-bs-crm"); ?></div>
                                              <p><?php _e('This contact does not have any quotes yet, do you want to',"zero-bs-crm"); echo ' <a href="'.$newQuoteURL.'" class="">'.__('Create one',"zero-bs-crm").'</a>?'; ?></p>
                                            </div>
                                          </div>
                                      </td>
                                    </tr><?php

                                }

                                ?>

                              </tbody>
                            </table>
                    </div><?php } ?>

                    <?php if ($useInvoices == "1"){ ?>
                    <div class="ui bottom attached <?php if ($activeTab == 'invoices') echo 'active '; ?>tab segment" data-tab="invoices">
                        <table class="ui celled table unstackable">
                              <thead>
                                  <th><?php _e("Reference","zero-bs-crm"); ?></th>
                                  <th><?php _e("Date","zero-bs-crm"); ?></th>
                                  <th><?php _e("Amount","zero-bs-crm"); ?></th>
                                  <th><?php _e("Status","zero-bs-crm"); ?></th>
                              </thead>
                              <tbody>
                                <?php
                                if (count($zbsCustomer['invoices']) > 0){

                                  foreach($zbsCustomer['invoices'] as $invoice){

                                    // DAL3 change of field name
                                    if ($zbs->isDAL3()){

                                        // 3.0
                                        $idRefStr = ''; 
                                        if (isset($invoice['id'])) $idRefStr = '#'.$invoice['id'];
                                        if (isset($invoice['id_override'])) {
                                          if (!empty($idRefStr)) $idRefStr .= ' -';
                                          $idRefStr .= ' '.$invoice['id_override'];
                                        }

                                        $invoiceURL = zbsLink('edit',$invoice['id'],ZBS_TYPE_INVOICE);

                                        $invoiceVal = $invoice['total'];

                                        $invoiceStatus = $invoice['status'];

                                    } else {

                                        // <3.0
                                        $idRefStr = ''; 
                                        if (isset($invoice['zbsid'])) $idRefStr = '#'.$invoice['zbsid'];
                                        if (isset($invoice['meta']) && isset($invoice['meta']['ref'])) {
                                          if (!empty($idRefStr)) $idRefStr .= ' -';
                                          $idRefStr .= ' '.$invoice['meta']['ref'];
                                        }

                                        $invoiceURL = zbsLink('edit',$invoice['id'],ZBS_TYPE_INVOICE);

                                        $invoiceVal = $invoice['meta']['val'];

                                        $invoiceStatus = $invoice['meta']['status'];

                                    }
                                    
                                    echo "<tr>";
                                      echo '<td><a href="'.$invoiceURL.'">' . $idRefStr . "</a></td>";
                                      echo "<td>" . zeroBSCRM_html_InvoiceDate($invoice) . "</td>";
                                      echo "<td>" . zeroBSCRM_formatCurrency($invoiceVal) . "</td>";
                                      echo "<td><span class='".zeroBSCRM_html_invoiceStatusLabel($invoice)."'>" . ucfirst($invoiceStatus) . "</span></td>";
                                    echo "</tr>"; 
                                  }

                                } else {

                                  // empty, create?
                                  $newInvURL = zbsLink('create',-1,ZBS_TYPE_INVOICE).'&zbsprefillcust='.$zbsCustomer['id'];

                                  ?><tr>
                                      <td colspan="4">
                                          <div class="ui info icon message" id="zbsNoInvoiceResults">
                                            <div class="content">
                                              <div class="header"><?php _e('No Invoices',"zero-bs-crm"); ?></div>
                                              <p><?php _e('This contact does not have any invoices yet, do you want to',"zero-bs-crm"); echo ' <a href="'.$newInvURL.'" class="">'.__('Create one',"zero-bs-crm").'</a>?'; ?></p>
                                            </div>
                                          </div>
                                      </td>
                                    </tr><?php

                                }

                                ?>

                              </tbody>
                            </table>
                    </div><?php } ?>

                    <?php if ($useTrans == "1"){ ?>
                    <div class="ui bottom attached <?php if ($activeTab == 'transactions') echo 'active '; ?>tab segment" data-tab="transactions">
                        <?php

                        // get columns from screen options
                        $activeTransactionColumns = array('date','id','total','status'); // default
                        if (
                            isset($screenOpts) && is_array($screenOpts) 
                              && isset($screenOpts['tablecolumns']) && is_array($screenOpts['tablecolumns']) 
                                && isset($screenOpts['tablecolumns']['transactions']) 
                                && is_array($screenOpts['tablecolumns']['transactions']) 
                                && count($screenOpts['tablecolumns']['transactions']) > 0
                          ) $activeTransactionColumns = $screenOpts['tablecolumns']['transactions'];
                        ?>
                        <table class="ui celled table unstackable">
                              <thead>
                                <?php 

                                // for now, pick out id so always on left
                                if (in_array('id', $activeTransactionColumns)) echo '<th>'.zeroBS_objDraw_transactionColumnHeader('id').'</th>';

                                foreach ($activeTransactionColumns as $col){ 

                                  // id pulled out above
                                  if ($col != 'id') echo '<th>'.zeroBS_objDraw_transactionColumnHeader($col).'</th>'; 

                                } ?>
                              </thead>
                              <tbody>
                                <?php

                       

                                if (count($zbsCustomer['transactions']) > 0){

                                  foreach($zbsCustomer['transactions'] as $zbsTransaction){

                                    echo "<tr>";
                                
                                      // ultimately these should be drawn by JS so they can use the same 
                                      // 'generate obj html' funcs as list view
                                      // for now quickly generated in php for this freelance.

                                        // for now, pick out id so always on left
                                        if (in_array('id', $activeTransactionColumns)) echo '<td>'.zeroBS_objDraw_transactionColumnTD('id',$zbsTransaction).'</td>';

                                        foreach ($activeTransactionColumns as $col){ 

                                          // id pulled out above
                                          if ($col != 'id') echo '<td>'.zeroBS_objDraw_transactionColumnTD($col,$zbsTransaction).'</td>';

                                        }

                                    echo "</tr>";
                                  }

                                } else {

                                  // empty, create?
                                  ?><tr>
                                      <td colspan="<?php echo count($activeTransactionColumns); ?>">
                                          <div class="ui info icon message" id="zbsNoTransactionResults">
                                            <div class="content">
                                              <div class="header"><?php _e('No Transactions',"zero-bs-crm"); ?></div>
                                              <p><?php _e('This contact does not have any transactions yet, do you want to',"zero-bs-crm"); echo ' <a href="'.zbsLink('create',-1,ZBS_TYPE_TRANSACTION).'&prefill='.$zbsCustomer['id'].'" class="">'.__('Create one',"zero-bs-crm").'</a>?'; ?></p>
                                            </div>
                                          </div>
                                      </td>
                                    </tr><?php

                                }

                                ?>

                              </tbody>
                            </table>
                    </div>
                    <?php } ?>

                    <div class="ui bottom attached tab segment" data-tab="files">
                      <?php /* 
                       <a class="ui button tiny right" href="#">Add File</a>
                        <div class="clear"></div>
                        <div class="ui divider"></div>

                        <style>
                        .thetitle{
                          font-size: 16px;
                          font-weight: 900;
                        }
                        </style> */ ?>

                        <table class="ui celled table unstackable" id="zbsFilesTable" style="margin-bottom:0;">
                          <thead>
                            <th><?php _e("Info","zerobscrm");?></th>
                            <th class="center aligned"><?php _e("View File","zerobscrm");?></th>
                            <th class="center aligned"><?php _e("Shown on Portal","zerobscrm");?></th>
                            <th class="center aligned" style="min-width:230px"><?php _e("Actions","zerobscrm");?></th>
                          </thead>
                          <tbody>
                        <?php
                          $zbsFiles = zeroBSCRM_getCustomerFiles($id); $hasFiles = false;

                          #} Any files
                          if (is_array($zbsFiles) && count($zbsFiles) > 0){ 

                            $hasFiles = true;

                            $fileLineIndx = 0; foreach($zbsFiles as $zbsFile){                              

                              //$fileFullname = basename($zbsFile['file']);
                              //$file = substr($fileFullname,strpos($fileFullname, '-')+1);
                              $file = zeroBSCRM_files_baseName($zbsFile['file'],isset($zbsFile['priv']));
                              ?><tr>
                                <td>
                                  <h4><?php if (isset($zbsFile['title'])) echo $zbsFile['title']; else echo __('Untitled','zero-bs-crm'); ?></h4>
                                  <p>
                                    <?php if (isset($zbsFile['desc'])) echo $zbsFile['desc']; ?>
                                  </p>
                                  <em>(<?php echo $file; ?>)</em>
                                </td>

                                      <td class="center aligned">
                                          <?php 
                                              echo '<a class="" href="'.$zbsFile['url'].'" target="_blank" class="ui button basic">'.__("View","zero-bs-crm") .'</a>';
                                              
                                              ?>
                                      </td>


                                      <td class="center aligned">
                                        <?php 
                                        if(isset($zbsFile['portal']) && $zbsFile['portal']){
                                          echo "<i class='icon check cirlc green inverted'></i>";
                                        }else{
                                          echo "<i class='icon ban inverted red'></i'>";
                                        }
                                        ?>
                                      </td>

                                      <td class="center aligned">
                                        <?php
                                        $zbs_edit = esc_url(admin_url('admin.php?page='.$zbs->slugs['editfile']) . "&customer=".$id."&fileid=" . $fileLineIndx  );   
                                        ?>
                                        <a href="<?php echo $zbs_edit;?>" target="_blank" class="ui button basic"><i class="edit icon"></i><?php _e("Edit","zero-bs-crm"); ?></a>&nbsp;&nbsp;
                                        <button class="zbsDelFile ui button basic" data-delurl="<?php echo $zbsFile['url']; ?>"><i class="trash alternate icon"></i><?php _e("Delete","zero-bs-crm"); ?></button>
                                      </td>
                              </tr><?php
           
                              $fileLineIndx++;
                            } //end of the files loop.. 


                          } 

                          // put this out either way, so that if a user deletes all it can be reshown in ui

                              // empty, create?
                              ?><tr id="zbs-no-files-msg" style="display:<?php if (!$hasFiles) echo 'table-row'; else echo 'none'; ?>">
                                  <td colspan="4">
                                      <div class="ui info icon message" id="zbsNoFileResults">
                                        <div class="content">
                                          <div class="header"><?php _e('No Files',"zero-bs-crm"); ?></div>
                                          <p><?php _e('This contact does not have any files yet, do you want to',"zero-bs-crm"); echo ' <a href="'.zbsLink('edit',$id,'zerobs_customer',false).'#zerobs-customer-files-head" class="">'.__('Upload a file',"zero-bs-crm").'</a>?'; ?></p>
                                        </div>
                                      </div>
                                  </td>
                                </tr><?php

                        ?>
                        </tbody>
                        </table>
                        <div id="zbsFileActionOutput" style="display:none"></div>
                        <?php  

                        ##WLREMOVE 

                        // and upsell here if admin + not using client portal pro

                        if (current_user_can('admin_zerobs_manage_options') && !defined('ZBS_CLIENTPRO_TEMPLATES')){
                          
                          // no client portal pro, so UPSELL :) ?>
                          <div style="margin-bottom:0;line-height: 1.8em" class="ui inverted segment">Want to show clients files on their Client Portal? <a href="<?php echo $zbs->urls['upgrade']; ?>?utm_content=inplugin-contactview" target="_blank">Upgrade to a Bundle</a> (and get Client Portal Pro) to enable this.</div><?php 

                        }

                        ##/WLREMOVE 
                        ?>
                      </div>


                    <?php if ($useTasks == "1"){ ?>
                    <div class="ui bottom attached <?php if ($activeTab == 'tasks') echo 'active '; ?>tab segment" data-tab="tasks">
                        <table class="ui celled table unstackable">
                              <thead>
                                  <th><?php _e("Date","zero-bs-crm"); ?></th>
                                  <th><?php _e("Task","zero-bs-crm"); ?></th>
                                  <th><?php _e("Status","zero-bs-crm"); ?></th>
                                  <th><?php _e("View","zero-bs-crm"); ?></th>
                              </thead>
                              <tbody>
                                <?php
                                if (isset($zbsCustomer['tasks']) && is_array($zbsCustomer['tasks']) && count($zbsCustomer['tasks']) > 0){

                                  $lastTaskStart = -1; $upcomingOutput = false;

                                  foreach ($zbsCustomer['tasks'] as $task){

                                    // if the first task is upcoming, add a header
                                    if (!$upcomingOutput && $task['start'] > time()){

                                      // tried to use horizontal divider here, but there's a semantic bug
                                      // ... when using these in tables. https://semantic-ui.com/elements/divider.html
                                      // ... adding display:block to the td fixes, but then colspan doesn't work. Skipping for now
                                      echo '<tr><td colspan="4"><div class="ui horizontal divider">'.__('Upcoming Tasks','zero-bs-crm').'</div></td></tr>';

                                      // shown
                                      $upcomingOutput = true;

                                    }

                                    // if there are tasks in future, and past, draw a line between
                                    if ($lastTaskStart > 0 && $lastTaskStart > time() && $task['end'] < time()){


                                      // tried to use horizontal divider here, but there's a semantic bug
                                      // ... when using these in tables. https://semantic-ui.com/elements/divider.html
                                      // ... adding display:block to the td fixes, but then colspan doesn't work. Skipping for now
                                      echo '<tr><td colspan="4"><div class="ui horizontal divider">'.__('Past Tasks','zero-bs-crm').'</div></td></tr>';

                                    }

                                    $taskURL = zbsLink('edit',$task['id'],ZBS_TYPE_EVENT);
                                    $statusStr = __('Incomplete','zero-bs-crm'); if (isset($task['complete']) && $task['complete']) $statusStr = __('Completed','zero-bs-crm');
                                    $status = "<span class='".zeroBSCRM_html_taskStatusLabel($invoice)."'>" . $statusStr . "</span>";
                                    
                                    echo "<tr>";
                                      echo "<td>" . zeroBSCRM_html_taskDate($task) . "</td>";
                                      echo "<td>" . $task['title'] . "</td>";
                                      echo "<td>".$status."</td>";
                                      echo '<td style="text-align:center"><a href="'.$taskURL.'">' . __('View','zero-bs-crm') . "</a></td>";
                                    echo "</tr>"; 

                                    $lastTaskStart = $task['start'];

                                  }

                                } else {

                                  // empty, create?
                                  $newURL = zbsLink('create',-1,ZBS_TYPE_EVENT).'&zbsprefillcust='.$zbsCustomer['id'];

                                  ?><tr>
                                      <td colspan="4">
                                          <div class="ui info icon message" id="zbsNoInvoiceResults">
                                            <div class="content">
                                              <div class="header"><?php _e('No Tasks',"zero-bs-crm"); ?></div>
                                              <p><?php _e('This contact does not have any tasks yet, do you want to',"zero-bs-crm"); echo ' <a href="'.$newURL.'" class="">'.__('Create one',"zero-bs-crm").'</a>?'; ?></p>
                                            </div>
                                          </div>
                                      </td>
                                    </tr><?php

                                }

                                ?>

                              </tbody>
                            </table>
                    </div><?php } ?>


                    </div><!-- / tabs -->


                  <?php 

                      $customerTags = zeroBSCRM_getCustomerTagsByID($zbsCustomer['id']);

                      // debug echo '<pre>'; print_r($customerTags); echo '</pre><hr>';

                      if (count($customerTags) > 0){

                          ?><!-- TAGGED --><div class="zbs-view-tags">
                          <h4 class="ui horizontal header divider">
                              <i class="tag icon"></i>
                              <?php _e('Tagged',"zero-bs-crm"); ?>
                          </h4>
                          <?php

                            // output as links
                            zeroBSCRM_html_linkedContactTags($zbsCustomer['id'],$customerTags,'ui medium olive button');

                          ?>
                          </div><!-- / TAGGED --><?php
                      } ?>

                </div>

                <div class="six wide column" id="zbs-custom-quicklinks" style="padding-right: 30px;">

                  <?php 
                      #} Metaboxes
                      zeroBSCRM_do_meta_boxes( 'zbs-view-contact', 'side', $zbsCustomer ); // should be an obj! 'zerobs_customer'
                  ?>

                </div>


              </div>
              <script type="text/javascript">
                
                // Nonce
                var zbscrmjs_secToken = '<?php echo wp_create_nonce( "zbscrmjs-ajax-nonce" ); ?>';

                // moved to singleview.js
                var zbsViewSettings = {

                    objid: <?php echo $id; ?>,
                    objdbname: 'contact' <?php //echo $this->objType; ?>

                };

                </script><?php

                // PRIVATE hook (do not share with dev/docs PLEASE leave off.)
                do_action('zerobscrm_contactview_postscripts');

    } // if ID

}

/*

  Custom Fields View Contact Tab (using built in filter) following the tech guide here:

  Adding in for people, as 90% of users won't be developer level and we should display this for people

*/
function zeroBSCRM_pages_admin_view_page_contact_custom_fields($arr, $id ) {

  // if NO custom fields:
  $showCustomFieldsTab = true;
    // if admin, show inc message "click to add some"
    // if not admin, don't show
    $custom_fields = zeroBS_getContactCustomFields($id);
    if (!is_array($custom_fields) || count($custom_fields) <= 0){

        $showCustomFieldsTab = false;

        if (zeroBSCRM_isZBSAdminOrAdmin()){

          $showCustomFieldsTab = true;

        }

    }
    
    if ($showCustomFieldsTab){

      // this is just a check :)
      if (!is_array($arr)) $arr = array();
      // Here we add the new tab
      // 	'id' = Represents HTML id attribute, must be unique & html-attribute format (e.g. a-b-c)
      //	'name' = Title string
      //  'content' = the HTML you want to display in your tab (you could use another function to produce this)
      $arr[] = array(
      	'id' => 'contact-custom-fields-tab',
      	'name' => __('Custom Fields', 'zero-bs-crm'),
      	'content' => zeroBSCRM_pages_admin_display_custom_fields_table($id)
      	);

    }

    return $arr;

}
add_filter( 'zbs-contact-vital-tabs', 'zeroBSCRM_pages_admin_view_page_contact_custom_fields', 10, 2);



/*

  Client Portal Contact Tab (using built in filter) 

*/
function zeroBSCRM_pages_admin_view_page_contact_clientportal($arr, $id ) {

  if (zeroBSCRM_isExtensionInstalled('portal')){


        // this is just a check :)
        if (!is_array($arr)) $arr = array();

        // Here we add the new tab
        //  'id' = Represents HTML id attribute, must be unique & html-attribute format (e.g. a-b-c)
        //  'name' = Title string
        //  'content' = the HTML you want to display in your tab (you could use another function to produce this)
        $arr[] = array(
          'id' => 'contact-clientportal-tab',
          'name' => __('Client Portal', 'zero-bs-crm'),
          'content' => zeroBSCRM_pages_admin_display_clientportal_tab($id)
          );

  }

  return $arr;

}
add_filter( 'zbs-contact-vital-tabs', 'zeroBSCRM_pages_admin_view_page_contact_clientportal', 10, 2);

function zeroBSCRM_pages_admin_display_clientportal_tab($id=-1){

  // adapted from class @ zeroBS__Metabox_ContactPortal
  // moved to return
  $html = '';
  global $zbs;

        $wp_user_id = '';
        #} Rather than reload all the time :)
        global $zbsContactEditing; 

        #} retrieve
        //$zbsCustomer = get_post_meta($id, 'zbs_customer_meta', true);
        if (!isset($zbsContactEditing)){
            $zbsCustomer = zeroBS_getCustomer($id,false,false,false);
            $zbsContactEditing = $zbsCustomer;
        } else {
            $zbsCustomer = $zbsContactEditing;
        }

        if (isset($zbsCustomer) && is_array($zbsCustomer) && isset($zbsCustomer['email'])){

            //check customer link to see if it exists - wh moved to dal
            $wp_user_id = zeroBSCRM_getClientPortalUserID($id);

            /* nope
            if($wp_user_id == ''){
                $wp_user_id = email_exists( $zbsCustomer['email'] );
            } */
        }

        $html .= '<div class="waiting-togen">';

        if ($wp_user_id != -1 && $wp_user_id > 0){

            //a user already exists with this email

            // get user obj
            $userObj = get_userdata($wp_user_id);
            $html .= '<div class="zbs-customerportal-activeuser">';

                $html .= __('WordPress User Linked',"zero-bs-crm");
                $html .= ' #<span class="zbs-user-id">'. $wp_user_id .'</span>:<br />';

                $html .= '<span class="ui label">'.$userObj->user_login.'</span>';

                // wp admins get link
                if (zeroBSCRM_isWPAdmin()){
                    
                    $url = admin_url('user-edit.php?user_id='.$wp_user_id);
                    $html .= '<br /><a style="font-size: 12px;" href="'.$url.'" target="_blank"><i class="wordpress simple icon"></i> '.__('View WordPress Profile','zero-bs-crm').'</a>';

                }


            $html .= '</div>';

            // user ID will now have access to this area..
            $html .= '<hr /><div class="zbs-customerportal-activeuser-actions">';

                $html .= __('Customer Portal Access:',"zero-bs-crm");


                $customerPortalActive = true; if (zeroBSCRM_isCustomerPortalDisabled($id)) $customerPortalActive = false;
            
                if ($customerPortalActive){

                    // revoke/disable access
                    $html .= ' <span class="ui green empty circular label"></span> <span class="zbs-portal-label">'.__('Enabled',"zero-bs-crm").'</span>';

                    $html .= '<div id="zbs-customerportal-access-actions">';

                        if ($zbs->isDAL2()) $html .= '<button type="button" id="zbs-customerportal-resetpw" class="ui mini button orange">'.__('Reset Password',"zero-bs-crm").'</button>';
                        $html .= '<button type="button" id="zbs-customerportal-toggle" data-zbsportalaction="disable" class="ui mini button negative">'.__('Disable Access',"zero-bs-crm").'</button>';

                    $html .= '</div>';

                } else {

                    // enable access
                    $html .= ' <span class="ui red empty circular label"></span> <span class="zbs-portal-label">'.__('Disabled',"zero-bs-crm").'</span>';

                    $html .= '<div id="zbs-customerportal-access-actions">';
                        $html .= '<button type="button" id="zbs-customerportal-toggle" data-zbsportalaction="enable" class="ui mini button positive">'.__('Enable Access',"zero-bs-crm").'</button>';
                    $html .= '</div>';


                }

                $html .= '<input type="hidden" id="zbsportalaction-ajax-nonce" value="' . wp_create_nonce( 'zbsportalaction-ajax-nonce' ) . '" />';
            

            $html .= '</div>';
            

        }else if ((!$wp_user_id || $wp_user_id == -1) && $zbsCustomer != '' && is_array($zbsCustomer) && isset($zbsCustomer['email']) && !empty($zbsCustomer['email'])){
            $html .= '<div class="no-gen" style="text-align:center">';
            $html .= __('No WordPress User exists with this email',"zero-bs-crm");
            $html .= '<br/><br/>';
            $html .= '<div class="ui primary button button-primary wp-user-generate">';
            $html .= __('Generate WordPress User',"zero-bs-crm");
            $html .= '</div>';
            $html .= '<input type="hidden" name="newwp-ajax-nonce" id="newwp-ajax-nonce" value="' . wp_create_nonce( 'newwp-ajax-nonce' ) . '" />';
            $html .= '<input type="hidden" name="email" id="email" value="'. $zbsCustomer['email'] .'" />';
            $html .= '</div>';
        }else{
            __('Save your contact, or add an email to enable Customer Portal functionality',"zero-bs-crm");
        }

        $html .= '</div>';

        // THIS CAN be output at time of call, doesn't matter that it's above it in the HTML DOM
        ?><script type="text/javascript">

            jQuery(document).ready(function(){

                // bind activate/deactivate
                jQuery('#zbs-customerportal-toggle').unbind("click").bind('click',function(e){

                    // action
                    var action = jQuery(this).attr('data-zbsportalaction');

                    // fire ajax
                    var t = {
                        action: "zbsPortalAction",
                        portalAction: action,
                        cid: <?php if (!empty($id) && $id > 0) echo $id; else echo -1; ?>,
                        security: jQuery( '#zbsportalaction-ajax-nonce' ).val()
                    }                    
                    i = jQuery.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: t,
                        dataType: "json"
                    });
                    i.done(function(e) {
                        //console.log(e);
                        if(typeof e.success != "undefined"){

                            // localise
                            var cAction = action;

                            if (action == 'enable'){

                                // switch label
                                jQuery('.ui.circular.label',jQuery('.zbs-customerportal-activeuser-actions')).removeClass('red').addClass('green');
                                jQuery('.zbs-portal-label',jQuery('.zbs-customerportal-activeuser-actions')).html('<?php _e('Enabled',"zero-bs-crm"); ?>');
                                jQuery('#zbs-customerportal-toggle').removeClass('positive').addClass('negative').html('<?php _e('Disable Access',"zero-bs-crm"); ?>').attr('data-zbsportalaction','disable');
                                

                            } else if (action == 'disable'){

                                // switch label
                                jQuery('.ui.circular.label',jQuery('.zbs-customerportal-activeuser-actions')).addClass('red').removeClass('green');
                                jQuery('.zbs-portal-label',jQuery('.zbs-customerportal-activeuser-actions')).html('<?php _e('Disabled',"zero-bs-crm"); ?>');
                                jQuery('#zbs-customerportal-toggle').removeClass('negative').addClass('positive').html('<?php _e('Enable Access',"zero-bs-crm"); ?>').attr('data-zbsportalaction','enable');
                                

                            }

                        }
                    }), i.fail(function(e) {
                        //error
                    });

                });

                // bind reset pw
                jQuery('#zbs-customerportal-resetpw').unbind("click").bind('click',function(e){

                    // fire ajax
                    var t = {
                        action: "zbsPortalAction",
                        portalAction: 'resetpw',
                        cid: <?php if (!empty($id) && $id > 0) echo $id; else echo -1; ?>,
                        security: jQuery( '#zbsportalaction-ajax-nonce' ).val()
                    }                    
                    i = jQuery.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: t,
                        dataType: "json"
                    });
                    i.done(function(e) {
                        //console.log(e);
                        if(typeof e.success != "undefined"){

                            var newPassword =  '<?php zeroBSCRM_slashOut(__('Unknown',"zero-bs-crm")); ?>';
                            if (typeof e.pw != "undefined") newPassword = e.pw;

                            // swal confirm
                            swal(
                                '<?php zeroBSCRM_slashOut(__('Client Portal Password Reset',"zero-bs-crm")); ?>',
                                '<?php zeroBSCRM_slashOut(__('Client Portal password has been reset for this contact, and they have been emailed with the new password. The new password is:',"zero-bs-crm")); ?><br /><span class="ui label">' + newPassword + '</span>',
                                'info'
                            );


                        }
                    }), i.fail(function(e) {
                        //error
                    });

                });


                // bind create
                jQuery('.wp-user-generate').unbind("click").bind('click',function(e){
                    email = jQuery('#email').val();
                    customerid = <?php if (!empty($id) && $id > 0) echo $id; else echo -1; ?>;
                    if(email == ''){
                        alert("<?php _e('The email field is blank. Please edit the contact, add in the email and save, first.','zero-bs-crm'); ?>");
                        return false;
                    }
                    var t = {
                        action: "zbs_new_user",
                        email: email,
                        cid: customerid,
                        security: jQuery( '#newwp-ajax-nonce' ).val(),
                    }                    
                    i = jQuery.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: t,
                        dataType: "json"
                    });
                    i.done(function(e) {
                        console.log(e);
                        if(e.success){
                            jQuery('.zbs-user-id').html(e.user_id);
                            jQuery('.no-gen').remove();
                            jQuery('.waiting-togen').html("<div class='alert alert-success'><?php _e('Success','zero-bs-crm'); ?></div><?php _e('User Generated','zero-bs-crm'); ?>");
                        }
                    }), i.fail(function(e) {
                        //error
                    });
                });

            });


        </script><?php

    return $html;

}

/* 
  View Company Page
*/
function zeroBSCRM_pages_admin_view_page_company($id = -1){

  if (!empty($id) && $id > 0){

      global $zbs;


          $useQuotes = false; //not yet $useQuotes = zeroBSCRM_getSetting('feat_quotes');
          $useInvoices = zeroBSCRM_getSetting('feat_invs');
          $useTrans = zeroBSCRM_getSetting('feat_transactions');

          #} Get screen options for user
          $screenOpts = $zbs->userScreenOptions();

          #} get our single company info
              //DAL3?
              if ($zbs->isDAL3())
                $zbsCompanyObj = $zbs->DAL->companies->getCompany($id,array(
                      'withCustomFields'  => true,
                      'withQuotes'        => true,
                      'withInvoices'      => true,
                      'withTransactions'  => true,
                      'withLogs'          => true,
                      //'withLastLog'       => false,
                      'withTags'          => true,
                      'withOwner'         => true,
                      'withValues'        => true,
                      'withContacts'      => true,
                  ));
              else
                $zbsCompanyObj = zeroBS_getCompany($id,true);

          // if customer id provided, but no obj, don't load any further.
          // this matches the ghost-record treatment in the single edit.php class
          if (!is_array($zbsCompanyObj)){

                // brutal hide, then msg #ghostrecord
                ?><style type="text/css">#zbs-edit-save, #zbs-nav-view, #zbs-nav-prev, #zbs-nav-next { display:none; }</style>
                <div id="zbs-edit-warnings-wrap"><?php
                echo zeroBSCRM_UI2_messageHTML('warning','Error Retrieving Company','There does not appear to be a Company with this ID.','disabled warning sign','zbsCantLoadData');  
                ?></div><?php  
                return false;

          }

          // until DAL2 catches up with co, we need these lines to move ['meta'] into obj
          if ($zbs->isDAL3())
              $zbsCompany = $zbsCompanyObj;
          else {
              $zbsCompany = $zbsCompanyObj['meta'];
              $zbsCompany['id'] = $zbsCompanyObj['id'];
              $zbsCompany['created'] = $zbsCompanyObj['created'];
              $zbsCompany['name'] = $zbsCompanyObj['name'];
              $zbsCompany['transactions'] = array(); if (isset($zbsCompanyObj['transactions']) && is_array($zbsCompanyObj['transactions'])) $zbsCompany['transactions'] = $zbsCompanyObj['transactions'];
              $zbsCompany['invoices'] = array(); if (isset($zbsCompanyObj['invoices']) && is_array($zbsCompanyObj['invoices'])) $zbsCompany['invoices'] = $zbsCompanyObj['invoices'];
          }

          
          #} Get actions
          $companyActions = zeroBS_company_actions($id);

          #} PREP
          $companyEmail = ''; if (isset($zbsCompany['email'])) $companyEmail = $zbsCompany['email'];

          // values - DAL3 we get them passed all nicely :)
          $companyTotalValue = 0; if (isset($zbsCompany['total_value'])) $companyTotalValue = $zbsCompany['total_value'];
          $companyQuotesValue = 0; if (isset($zbsCompany['quotes_total'])) $companyQuotesValue = $zbsCompany['quotes_total'];
          $companyInvoicesValue = 0; if (isset($zbsCompany['invoices_total'])) $companyInvoicesValue = $zbsCompany['invoices_total'];
          $companyTransactionsValue = 0; if (isset($zbsCompany['transactions_total'])) $companyTransactionsValue = $zbsCompany['transactions_total'];

          // pre dal 3 did this way
          if (!$zbs->isDAL3()){

            // calc'd each individually
            // never used (pre dal3) $companyTotalValue = zeroBS_companyTotalValue($id, $zbsCompany['invoices'],$zbsCompany['transactions'])
            // never used (pre dal3) $companyQuotesValue = zeroBS_companyQuotesValue($id, $zbsCompany['quotes']);
            $companyInvoicesValue = zeroBS_companyInvoicesValue($id, $zbsCompany['invoices']);
            $companyTransactionsValue = zeroBS_companyTransactionsValue($id, $zbsCompany['transactions']);

          }

          // put screen options out
          zeroBSCRM_screenOptionsPanel();

        ?>

          <div class="ui divided grid" style="margin-top:-1em;">

            <div class="ten wide column" id="zbs-company-panel">

              <div class="ui segment grid">

              <?php # based on avatar/no avatar, subtle diff design here:
              // No avatars for co's yet (2.72) if ($avatarMode == "3" || empty($avatar)){
                
                  // 1 column, no avatar card
                  ?><div class="sixteen wide column zbs-view-card"><?php
              /*
              } else {

                  // normal, 2 column 'contact card'
                  ?><div class="three wide column" style="text-align:center">
                      <?php echo $avatar; ?>
                        <a class="ui button blue mini" style="margin-top:0.8em" href="<?php echo zbsLink('edit',$id,'zerobs_customer',false);?>">
                            <?php _e("Edit Contact", "zero-bs-crm"); ?>
                        </a>

                    </div>
                    <div class="thirteen wide column zbs-view-card"><?php


              }*/ ?>

                      <h3>
                        <?php echo zeroBS_companyName('',$zbsCompany,false,false); ?>
                        <?php #} When no avatar, show edit button top right
                          // no avatars yet for co - if ($avatarMode == "3" || empty($avatar)){
                            ?><a class="ui button blue mini right floated" style="margin-top:0.8em" href="<?php echo zbsLink('edit',$id,'zerobs_company',false);?>">
                                  <?php _e("Edit Company", "zero-bs-crm"); ?>
                              </a><?php
                          // no avatars yet for co - } 
                        ?>
                      </h3>
                      <?php /*<p class="zbs-email">
                        <?php zeroBSCRM_html_sendemailto($id,$contactEmail,false); ?>
                      </p> */ ?>
                      <p class="zbs-sentence">
                        <?php echo zeroBSCRM_html_companyIntroSentence($zbsCompany,$zbsCompanyObj); ?>
                      </p>


                      <?php # https://codepen.io/kyleshockey/pen/bdeLrE 
                      /* nope, none here yet if (count($companyActions) > 0) { ?>
                      <div class="action-wrap">
                        <div class="ui green basic dropdown action-button"><?php _e('Company Actions',"zero-bs-crm"); ?><i class="dropdown icon"></i>
                           <div class="menu">
                            <?php foreach ($companyActions as $actKey => $action){ ?>
                               <div class="item zbs-company-action" id="zbs-company-action-<?php echo $actKey; ?>" data-action="<?php if (isset($action['url'])) echo 'url'; ?>" data-url="<?php if (isset($action['url'])) echo $action['url']; ?>">
                                 <?php 

                                    // got ico?
                                    if (isset($action['ico'])) echo '<i class="'.$action['ico'].'"></i>';

                                    // got text?
                                    if (isset($action['label'])) echo $action['label'];

                                ?>
                               </div>
                            <?php } ?>
                            </div>
                       </div>
                     </div>
                     <?php }  */?>


                  </div>
              </div>

            <?php #DEBUG echo '<pre>'; print_r($zbsCompany); echo '</pre><hr>'; ?>

              

              <!-- company vitals -->
              <?php

                // prep
                $statusStr = ''; if (isset($zbsCompany) && isset($zbsCompany['status']) && !empty($zbsCompany['status'])) $statusStr = $zbsCompany['status'];
              
                // compiled addr str
                $addrStr = ''; if (isset($zbsCompany)) $addrStr = zeroBS_companyAddr($zbsCompany['id'],$zbsCompany,'full','<br />');
                $addr2Str = ''; if (isset($zbsCompany)) $addr2Str = zeroBS_companySecondAddr($zbsCompany['id'],$zbsCompany,'full','<br />');

                // tels?
                $tels = array(); 
                if (isset($zbsCompany) && isset($zbsCompany['maintel']) && !empty($zbsCompany['maintel'])) $tels['maintel'] = $zbsCompany['maintel'];
                if (isset($zbsCompany) && isset($zbsCompany['sectel']) && !empty($zbsCompany['sectel'])) $tels['sectel'] = $zbsCompany['sectel'];

                /* 
                // socials
                global $zbsSocialAccountTypes;
                $zbsSocials = zeroBS_getCustomerSocialAccounts($id);
                  // empty empties.. hmmm
                  $zbsSocialsProper = array(); if (is_array($zbsSocials) && count($zbsSocials) > 0) foreach ($zbsSocials as $zbsSocialKey => $zbsSocialAcc) if (!empty($zbsSocialAcc)) $zbsSocialsProper[$zbsSocialKey] = $zbsSocialAcc;
                  $zbsSocials = $zbsSocialsProper; unset($zbsSocialsProper);

                */


                // retrieve any additional tabs peeps have prepared
                $zbsCompanyVitalTabs = apply_filters( 'zbs-company-vital-tabs', array(), $id );

              ?>

              <div id="zbs-vital-menu">
                <div class="ui top attached tabular menu">
                  <div data-tab="vitals" class="<?php if (!isset($activeVitalsTab)) { echo 'active '; $activeVitalsTab = 'vitals'; } ?>item"><?php 

                      // custom title e.g. Lead Vitals                                
                      //if (!empty($statusStr)) 
                      //  echo $statusStr; 
                      //else
                        _e('Company',"zero-bs-crm");
                       
                       echo ' '.__("Vitals","zero-bs-crm"); 

                      ?></div>
                  <?php /* if (count($zbsSocialAccountTypes) > 0 && count($zbsSocials) > 0){ ?>
                    <div data-tab="social" class="<?php if (!isset($activeVitalsTab)) { echo 'active '; $activeVitalsTab = 'social'; } ?>item"><?php _e('Social',"zero-bs-crm"); ?></div>                      
                  <?php } */ ?>
                  <?php #} Any integrated tabs - via filter zbs-contact-vital-tabs
                  if (is_array($zbsCompanyVitalTabs) && count($zbsCompanyVitalTabs) > 0){
                    $tabIndx = 1;
                    foreach ($zbsCompanyVitalTabs as $tab){

                      $tabName = __('Untitled Tab',"zero-bs-crm");
                      $tabID = 'zbs-company-tab-'.$tabIndx;

                      if (is_array($tab) && isset($tab['name'])) $tabName = $tab['name'];
                      if (is_array($tab) && isset($tab['id'])) $tabID = $tab['id'];
                      
                      ?><div data-tab="<?php echo $tabID; ?>" class="item"><?php echo $tabName; ?></div><?php

                      $tabIndx++;

                    }

                  } ?>
                  <?php if (!empty($statusStr)) { ?>
                  <div class="right menu item">
                    <?php _e("Status","zero-bs-crm");?>: 
                    <span class="ui green label"><?php echo $statusStr; ?></span>
                  </div>
                  <?php } ?>
                </div>

                <div class="ui bottom attached active tab segment" data-tab="vitals" id="zbs-company-view-vitals">
                    <table class="ui fixed single line celled table">
                      <tbody>
                        <tr>
                          <td class="zbs-view-vital-label"><?php _e("Transactions","zero-bs-crm"); ?> <i class="circle info icon link" data-content="<?php _e("Transactions Total & count: This shows the sum of your succeeded transactions (set in settings)","zero-bs-crm");?>" data-position="bottom center"></i></td>
                          <td>
                            <?php if (count($zbsCompany['transactions']) > 0)
                                    echo zeroBSCRM_formatCurrency($companyTransactionsValue).' ('.count($zbsCompany['transactions']).')';
                                  else
                                    _e('None',"zero-bs-crm"); ?>
                          </td>
                        </tr>
                        <?php if ($useInvoices == "1"){ ?>
                        <tr class="zbs-view-vital-invoices">
                          <td class="zbs-view-vital-label"><?php _e("Invoices","zero-bs-crm"); ?> <i class="circle info icon link" data-content="<?php _e("Invoices: This shows the total sum of your invoices & count.","zero-bs-crm");?>" data-position="bottom center"></i></td>
                          <td>
                            <?php if (count($zbsCompany['invoices']) > 0)
                                    echo zeroBSCRM_formatCurrency($companyInvoicesValue).' ('.count($zbsCompany['invoices']).')';
                                  else
                                    _e('None',"zero-bs-crm"); ?>
                          </td>
                        </tr>
                        <?php } ?>
                        <?php if ($zbs->isDAL3()){ ?> 
                        <tr>
                          <td class="zbs-view-vital-label"><strong><?php _e("Total Value","zero-bs-crm");?><i class="circle info icon link" data-content="<?php _e("Total Value is all transaction types and any unpaid invoices","zero-bs-crm");?>" data-position="bottom center"></i></strong></td>
                          <td><strong><?php echo zeroBSCRM_formatCurrency($companyTotalValue); ?></strong></td>
                        </tr>
                        <?php if ($useQuotes == "1"){ ?>
                        <tr>
                          <td class="zbs-view-vital-label"><?php _e("Quotes","zero-bs-crm"); ?> <i class="circle info icon link" data-content="<?php _e("Quotes: This shows the total sum of your quotes & count.","zero-bs-crm");?>" data-position="bottom center"></i></td>
                          <td>
                            <?php if (count($zbsCompany['quotes']) > 0)
                                    echo zeroBSCRM_formatCurrency($companyQuotesValue).' ('.count($zbsCompany['quotes']).')';
                                  else
                                    _e('None',"zero-bs-crm"); ?>
                          </td>
                        </tr>
                        <?php }
                        } // if dal3 ?>
                        <tr>
                          <td class="zbs-view-vital-label"><?php _e("Address Details","zero-bs-crm");?></td>
                          <td>
                            <?php 

                                  if (!empty($addrStr) && empty($addr2Str))
                                      echo $addrStr;
                                  else if (!empty($addrStr) && !empty($addr2Str)){
                                      ?><div class="ui grid">
                                        <div class="eight wide column">
                                            <h4 class="ui dividing header" style="margin-bottom: 0.6em;"><?php _e('Main address',"zero-bs-crm"); ?></h4>
                                            <?php echo $addrStr; ?>
                                        </div>
                                        <div class="eight wide column">
                                            <h4 class="ui dividing header" style="margin-bottom: 0.6em;"><?php _e('Secondary address',"zero-bs-crm"); ?></h4>
                                            <?php echo $addr2Str; ?>
                                        </div>
                                      </div><?php
                                  } else _e('No Address on File',"zero-bs-crm"); ?></td>
                        </tr>
                        <tr>
                          <td class="zbs-view-vital-label"><?php _e("Telephone Contacts","zero-bs-crm");?></td>
                          <td>
                            <?php 


                                  if (count($tels) > 0){

                                    // Click 2 call?
                                    $click2call = $zbs->settings->get('clicktocall');

                                    ?><div class="ui horizontal list"><?php

                                      foreach ($tels as $telKey => $telNo){ ?>
                                      <div class="item">
                                        <?php switch ($telKey){

                                          case 'sectel': 
                                              echo '<i class="large phone icon"></i>';
                                              break;
                                          case 'maintel':
                                              echo '<i class="large phone square icon"></i>'; 
                                              break;

                                        } ?>
                                        <div class="content">
                                          <?php if ($click2call == "1") { ?>
                                          <a class="ui small button" href="<?php echo zeroBSCRM_clickToCallPrefix().$telNo; ?>" title="<?php _e('Call',"zero-bs-crm").' '.$telNo; ?>"><?php echo $telNo; ?></a>
                                          <?php } else { ?>
                                          <div class="header"><?php echo $telNo; ?></div>
                                          <?php } ?>
                                        </div>
                                      </div>
                                      <?php } ?>

                                    </div><?php


                                  } else _e('No phone number on File',"zero-bs-crm"); ?></td>
                        </tr>
                        <tr>
                          <td class="zbs-view-vital-label"><?php _e("Contacts","zero-bs-crm"); ?> <i class="circle info icon link" data-content="<?php _e("Contacts at this Company","zero-bs-crm");?>" data-position="bottom center"></i></td>
                          <td id="zbs-company-view-vitals-contacts">
                            <?php 

                              // contacts at company
                              $contactStr = zeroBSCRM_html_linkedCompanyContacts($id,(isset($zbsCompanyObj['contacts']) ? $zbsCompanyObj['contacts'] : false));

                              if (!empty($contactStr))
                                echo $contactStr;
                              else
                                _e('None',"zero-bs-crm");

                            ?>
                          </td>
                        </tr>
                        <tr>
                          <td class="zbs-view-vital-label"><?php _e("Source","zero-bs-crm");?></td>
                          <td>
                            <?php
                            $zeroBSCRMsource = zeroBS_getExternalSource($id,ZBS_TYPE_COMPANY);
                            //MS? not sure what import meant to be here: if (isset($zeroBSCRMsource) && isset($zeroBSCRMsource['meta']) && isset($zeroBSCRMsource['meta']['import'])) echo $zeroBSCRMsource['meta']['import'];
                            if (isset($zeroBSCRMsource) && isset($zeroBSCRMsource['source']) && isset($zeroBSCRMsource['uid'])){


                                $uid = $zeroBSCRMsource['uid'];

                                // company + CSV means uid will be a useless hash, so replace that with name if we have
                                if (isset($zbsCompany['name'])) 
                                  $uid = $zbsCompany['name'];
                                else
                                  $uid = __('Imported based on name','zero-bs-crm');


                                echo zeroBS_getExternalSourceTitle($zeroBSCRMsource['source'],$uid);
                            } else
                              _e('Manually Added',"zero-bs-crm");

                             ?></td>
                        </tr>
                      </tbody>
                    </table>

                </div>

                  <?php #} Any integrated tabs - via filter zbs-contact-vital-tabs
                  if (is_array($zbsCompanyVitalTabs) && count($zbsCompanyVitalTabs) > 0){
                    $tabIndx = 1;
                    foreach ($zbsCompanyVitalTabs as $tab){

                      $tabID = 'zbs-company-tab-'.$tabIndx;
                      if (is_array($tab) && isset($tab['id'])) $tabID = $tab['id'];
                      
                      ?><div class="ui bottom attached tab segment" data-tab="<?php echo $tabID; ?>" id="zbs-contact-view-vitals-<?php echo $tabID; ?>">
                      <?php #} Content
                          if (is_array($tab) && isset($tab['contentaction'])){

                              // calls the users function name, if they opted for that instead of content
                              call_user_func($tab['contentaction'],$id);

                          } else if (is_array($tab) && isset($tab['content'])) echo $tab['content']; ?>
                      </div><?php

                      $tabIndx++;

                    }

                  } ?>                      
                <!-- / company vitals -->
              </div>
        







                  <h4 class="ui horizontal header divider">
                    <i class="archive icon"></i>
                    <?php _e('Documents',"zero-bs-crm"); ?>
                  </h4>

                  <div id="zbs-doc-menu">
                    <div class="ui top attached tabular menu">
                      <?php /* never, yet! if ($useQuotes == "1"){ ?><div data-tab="quotes" class="<?php if (!isset($activeTab)) { echo 'active '; $activeTab = 'quotes'; } ?>item"><?php _e('Quotes',"zero-bs-crm"); ?></div><?php } ?>*/ ?>
                      <?php if ($useInvoices == "1"){ ?><div data-tab="invoices" class="<?php if (!isset($activeTab)) { echo 'active '; $activeTab = 'invoices'; } ?>item"><?php _e('Invoices',"zero-bs-crm"); ?></div><?php } ?>                      
                      <?php if ($useTrans == "1"){ ?><div data-tab="transactions" class="<?php if (!isset($activeTab)) { echo 'active '; $activeTab = 'transactions'; } ?>item"><?php _e('Transactions',"zero-bs-crm"); ?></div><?php } ?>
                      <div data-tab="files" class="<?php if (!isset($activeTab)) { echo 'active '; $activeTab = 'files'; } ?>item"><?php _e('Files','zero-bs-crm'); ?></div>                    
                    </div>
                    <?php /*if ($useQuotes == "1"){ ?>
                    <div class="ui bottom attached <?php if ($activeTab == 'quotes') echo 'active '; ?>tab segment" data-tab="quotes">
                        <table class="ui celled table">
                              <thead>
                                  <th><?php _e("ID & Title","zero-bs-crm"); ?></th>
                                  <th><?php _e("Date","zero-bs-crm"); ?></th>
                                  <th><?php _e("Value","zero-bs-crm"); ?></th>
                                  <th><?php _e("Status","zero-bs-crm"); ?></th>
                              </thead>
                              <tbody>
                                <?php
                                if (count($zbsCustomer['quotes']) > 0){

                                  foreach($zbsCustomer['quotes'] as $quote){

                                    $idRefStr = ''; 
                                    if (isset($quote['zbsid'])) $idRefStr = '#'.$quote['zbsid'];
                                    if (isset($quote['meta']) && isset($quote['meta']['ref'])) {
                                      if (!empty($idRefStr)) $idRefStr .= ' -';
                                      $idRefStr .= ' '.$quote['meta']['ref'];
                                    }

                                    $qVal = '-'; 
                                    if (isset($quote['meta']['val'])) $qVal = $quote['meta']['val'];
                                    if ($qVal != '-' && !empty($qVal)) $qVal = zeroBSCRM_formatCurrency($qVal);

                                    echo "<tr>";
                                      echo '<td><a href="'.admin_url('post.php?action=edit&post='.$quote['id']).'">' . $idRefStr . "</a></td>";
                                      echo "<td>" . zeroBSCRM_html_QuoteDate($quote) . "</td>";
                                      echo "<td>" . $qVal . "</td>";
                                      echo "<td><span class='".zeroBSCRM_html_quoteStatusLabel($quote)."'>" . zeroBS_getQuoteStatus($quote,false) . "</span></td>";
                                    echo "</tr>"; 
                                  }

                                } else {

                                  // empty, create?
                                  ?><tr>
                                      <td colspan="4">
                                          <div class="ui info icon message" id="zbsNoQuoteResults">
                                            <div class="content">
                                              <div class="header"><?php _e('No Quotes',"zero-bs-crm"); ?></div>
                                              <p><?php _e('This contact does not have any quotes yet, do you want to',"zero-bs-crm"); echo ' <a href="'.admin_url('post-new.php?post_type=zerobs_quote&zbsprefillcust='.$zbsCustomer['id']).'" class="">'.__('Create one',"zero-bs-crm").'</a>?'; ?></p>
                                            </div>
                                          </div>
                                      </td>
                                    </tr><?php

                                }

                                ?>

                              </tbody>
                            </table>
                    </div><?php } ?> */ ?>

                    <?php if ($useInvoices == "1"){ ?>
                    <div class="ui bottom attached <?php if ($activeTab == 'invoices') echo 'active '; ?>tab segment" data-tab="invoices">
                        <table class="ui celled table unstackable">
                              <thead>
                                  <th><?php _e("Reference","zero-bs-crm"); ?></th>
                                  <th><?php _e("Date","zero-bs-crm"); ?></th>
                                  <th><?php _e("Amount","zero-bs-crm"); ?></th>
                                  <th><?php _e("Status","zero-bs-crm"); ?></th>
                              </thead>
                              <tbody>
                                <?php
                                if (count($zbsCompany['invoices']) > 0){

                                  foreach($zbsCompany['invoices'] as $invoice){
                                    // debugecho '<pre>'; print_r($invoice); echo '</pre><hr>';

                                    // DAL3 change of field name
                                    if ($zbs->isDAL3()){

                                        // 3.0
                                        $idRefStr = ''; 
                                        if (isset($invoice['id'])) $idRefStr = '#'.$invoice['id'];
                                        if (isset($invoice['id_override'])) {
                                          if (!empty($idRefStr)) $idRefStr .= ' -';
                                          $idRefStr .= ' '.$invoice['id_override'];
                                        }

                                        $invoiceURL = zbsLink('edit',$invoice['id'],ZBS_TYPE_INVOICE);

                                        $invoiceVal = $invoice['total'];

                                        $invoiceStatus = $invoice['status'];

                                    } else {

                                        // <3.0
                                        $idRefStr = ''; 
                                        if (isset($invoice['zbsid'])) $idRefStr = '#'.$invoice['zbsid'];
                                        if (isset($invoice['meta']) && isset($invoice['meta']['ref'])) {
                                          if (!empty($idRefStr)) $idRefStr .= ' -';
                                          $idRefStr .= ' '.$invoice['meta']['ref'];
                                        }

                                        $invoiceURL = zbsLink('edit',$invoice['id'],ZBS_TYPE_INVOICE);//admin_url('post.php?action=edit&post='.$invoice['id']);

                                        $invoiceVal = $invoice['meta']['val'];

                                        $invoiceStatus = $invoice['meta']['status'];

                                    }
                                    
                                    echo "<tr>";
                                      echo '<td><a href="'.$invoiceURL.'">' . $idRefStr . "</a></td>";
                                      echo "<td>" . zeroBSCRM_html_InvoiceDate($invoice) . "</td>";
                                      echo "<td>" . zeroBSCRM_formatCurrency($invoiceVal) . "</td>";
                                      echo "<td><span class='".zeroBSCRM_html_invoiceStatusLabel($invoice)."'>" . ucfirst($invoiceStatus) . "</span></td>";
                                    echo "</tr>"; 
                                  }

                                } else {

                                  // empty, create?
                                  ?><tr>
                                      <td colspan="4">
                                          <div class="ui info icon message" id="zbsNoInvoiceResults">
                                            <div class="content">
                                              <div class="header"><?php _e('No Invoices',"zero-bs-crm"); ?></div>
                                              <p><?php 
                                              // no prefill for company yet: 
                                              // &zbsprefillcust='.$zbsCustomer['id']
                                              _e('This company does not have any invoices yet, do you want to',"zero-bs-crm"); echo ' <a href="'.zbsLink('create',-1,ZBS_TYPE_INVOICE).'&prefillco='.$zbsCompany['id'].'" class="">'.__('Create one',"zero-bs-crm").'</a>?'; ?></p>
                                            </div>
                                          </div>
                                      </td>
                                    </tr><?php

                                }

                                ?>

                              </tbody>
                            </table>
                    </div><?php } ?>
                    <div class="ui bottom attached <?php if ($activeTab == 'transactions') echo 'active '; ?>tab segment" data-tab="transactions">
                        <?php

                        // get columns from screen options
                        $activeTransactionColumns = array('date','id','total','status'); // default
                        if (
                            isset($screenOpts) && is_array($screenOpts) 
                              && isset($screenOpts['tablecolumns']) && is_array($screenOpts['tablecolumns']) 
                                && isset($screenOpts['tablecolumns']['transactions']) 
                                && is_array($screenOpts['tablecolumns']['transactions']) 
                                && count($screenOpts['tablecolumns']['transactions']) > 0
                          ) $activeTransactionColumns = $screenOpts['tablecolumns']['transactions'];
                        ?>
                        <table class="ui celled table unstackable">
                              <thead>
                                <?php 

                                // for now, pick out id so always on left
                                if (in_array('id', $activeTransactionColumns)) echo '<th>'.zeroBS_objDraw_transactionColumnHeader('id').'</th>';

                                foreach ($activeTransactionColumns as $col){ 

                                  // id pulled out above
                                  if ($col != 'id') echo '<th>'.zeroBS_objDraw_transactionColumnHeader($col).'</th>'; 

                                } ?>
                              </thead>
                              <tbody>
                                <?php

                       

                                if (count($zbsCompany['transactions']) > 0){

                                  foreach($zbsCompany['transactions'] as $zbsTransaction){

                                    echo "<tr>";
                                
                                      // ultimately these should be drawn by JS so they can use the same 
                                      // 'generate obj html' funcs as list view
                                      // for now quickly generated in php for this freelance.

                                        // for now, pick out id so always on left
                                        if (in_array('id', $activeTransactionColumns)) echo '<td>'.zeroBS_objDraw_transactionColumnTD('id',$zbsTransaction).'</td>';

                                        foreach ($activeTransactionColumns as $col){ 

                                          // id pulled out above
                                          if ($col != 'id') echo '<td>'.zeroBS_objDraw_transactionColumnTD($col,$zbsTransaction).'</td>';

                                        }

                                    echo "</tr>";
                                  }

                                } else {

                                  // empty, create?
                                  ?><tr>
                                      <td colspan="<?php echo count($activeTransactionColumns); ?>">
                                          <div class="ui info icon message" id="zbsNoTransactionResults">
                                            <div class="content">
                                              <div class="header"><?php _e('No Transactions',"zero-bs-crm"); ?></div>
                                              <p><?php _e('This company does not have any transactions yet, do you want to',"zero-bs-crm"); echo ' <a href="'.zbsLink('create',-1,ZBS_TYPE_TRANSACTION).'&prefillco='.$zbsCompany['id'].'" class="">'.__('Create one',"zero-bs-crm").'</a>?'; ?></p>
                                            </div>
                                          </div>
                                      </td>
                                    </tr><?php

                                }

                                ?>

                              </tbody>
                            </table>
                    </div>

                    <div class="ui bottom attached tab segment" data-tab="files">
                        <table class="ui celled table unstackable" id="zbsFilesTable" style="margin-bottom:0;">
                          <thead>
                            <th><?php _e("Info","zerobscrm");?></th>
                            <th class="center aligned"><?php _e("View File","zerobscrm");?></th>
                            <th class="center aligned" style="min-width:230px"><?php _e("Actions","zerobscrm");?></th>
                          </thead>
                          <tbody>
                        <?php
                          //$zbsFiles = zeroBSCRM_getCustomerFiles($id); $hasFiles = false;
                          $zbsFiles = zeroBSCRM_files_getFiles('company',$id); $hasFiles = false;

                          #} Any files
                          if (is_array($zbsFiles) && count($zbsFiles) > 0){ 

                            $hasFiles = true;

                            $fileLineIndx = 0; foreach($zbsFiles as $zbsFile){                              

                              //$fileFullname = basename($zbsFile['file']);
                              //$file = substr($fileFullname,strpos($fileFullname, '-')+1);
                              $file = zeroBSCRM_files_baseName($zbsFile['file'],isset($zbsFile['priv']));
                              ?><tr>
                                <td>
                                  <h4><?php if (isset($zbsFile['title'])) echo $zbsFile['title']; else echo __('Untitled','zero-bs-crm'); ?></h4>
                                  <p>
                                    <?php if (isset($zbsFile['desc'])) echo $zbsFile['desc']; ?>
                                  </p>
                                  <em>(<?php echo $file; ?>)</em>
                                </td>

                                      <td class="center aligned">
                                          <?php 
                                              echo '<a class="" href="'.$zbsFile['url'].'" target="_blank" class="ui button basic">'.__("View","zero-bs-crm") .'</a>';
                                              
                                              ?>
                                      </td>

                                      <td class="center aligned">
                                        <?php
                                        $zbs_edit = esc_url(admin_url('admin.php?page='.$zbs->slugs['editfile']) . "&company=".$id."&fileid=" . $fileLineIndx  );   
                                        ?>
                                        <a href="<?php echo $zbs_edit;?>" target="_blank" class="ui button basic"><i class="edit icon"></i><?php _e("Edit","zero-bs-crm"); ?></a>&nbsp;&nbsp;
                                        <button class="zbsDelFile ui button basic" data-type="company" data-delurl="<?php echo $zbsFile['url']; ?>"><i class="trash alternate icon"></i><?php _e("Delete","zero-bs-crm"); ?></button>
                                      </td>
                              </tr><?php
           
                              $fileLineIndx++;
                            } //end of the files loop.. 


                          } 

                          // put this out either way, so that if a user deletes all it can be reshown in ui

                              // empty, create?
                              ?><tr id="zbs-no-files-msg" style="display:<?php if (!$hasFiles) echo 'table-row'; else echo 'none'; ?>">
                                  <td colspan="4">
                                      <div class="ui info icon message" id="zbsNoFileResults">
                                        <div class="content">
                                          <div class="header"><?php _e('No Files',"zero-bs-crm"); ?></div>
                                          <p><?php _e('This company does not have any files yet, do you want to',"zero-bs-crm"); echo ' <a href="'.zbsLink('edit',$id,'zerobs_company',false).'#wpzbsc_itemdetails_attachment" class="">'.__('Upload a file',"zero-bs-crm").'</a>?'; ?></p>
                                        </div>
                                      </div>
                                  </td>
                                </tr><?php

                        ?>
                        </tbody>
                        </table>
                        <div id="zbsFileActionOutput" style="display:none"></div>
                      </div>


                </div><!-- docs -->

              <?php 

                  $companyTags = zeroBSCRM_getCompanyTagsByID($zbsCompany['id']);

                  // debug echo '<pre>'; print_r($companyTags); echo '</pre><hr>';

                  if (count($companyTags) > 0){

                      ?><!-- TAGGED --><div class="zbs-view-tags">
                      <h4 class="ui horizontal header divider">
                          <i class="tag icon"></i>
                          <?php _e('Tagged',"zero-bs-crm"); ?>
                      </h4>
                      <?php

                        // output as links
                        zeroBSCRM_html_linkedCompanyTags($zbsCompany['id'],$companyTags,'ui medium olive button');

                      ?>
                      </div><!-- / TAGGED --><?php
                  } ?>

            </div>

            <div class="six wide column" id="zbs-custom-quicklinks" style="padding-right: 30px;">

              <?php 
                  #} Metaboxes
                  zeroBSCRM_do_meta_boxes( 'zerobs_view_company', 'side', $zbsCompany );

                  /* Switched to metabox 2.98.7

                  <h4 class="ui horizontal header divider" style="margin-top:1em">
                    <i class="heartbeat icon"></i>
                    <?php _e('Activity',"zero-bs-crm"); ?>
                  </h4>
                    
                    <div class="ui segment grid">
                        <div class="sixteen wide column">
                          <h4 style="font-weight:900;"><?php _e("Activity","zero-bs-crm");?></h4>
                          <?php

                              // Contact timeline
                              // dabbled with using semantic ui: https://semantic-ui.com/views/feed.html
                              // looked through: http://freefrontend.com/css-timelines/
                              // in end used https://codepen.io/brady_wright/pen/NNOvrW + adapted colours
                              //zbs_prettyprint($zbsCompanyActivity);

                              $zbsCompanyActivity = zeroBSCRM_getCompanyLogs($id,true,100,0,'',false);

                              zeroBSCRM_html_companyTimeline($id,$zbsCompanyActivity,$zbsCompany);

                          ?>
                        </div>
                    </div>

                  */ ?>
            </div>


          </div>
          
              <script type="text/javascript">
                
                // Nonce
                var zbscrmjs_secToken = '<?php echo wp_create_nonce( "zbscrmjs-ajax-nonce" ); ?>';

                // moved to singleview.js
                var zbsViewSettings = {

                    objid: <?php echo $id; ?>,
                    objdbname: 'company' <?php //echo $this->objType; ?>

                };

                </script><?php

                // PRIVATE hook (do not share with dev/docs PLEASE leave off.)
                do_action('zerobscrm_companyview_postscripts');

     } // if ID

}