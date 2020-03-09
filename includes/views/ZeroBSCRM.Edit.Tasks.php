<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.72+
 *
 * Copyright 2018, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 24/08/2018
 */


/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */
/**
 *  Function file for th task UI called in the modal, and via AJAX (if editing too)
 *  Keeps a central place for functions like this
 *  zeroBSCRM_add_edit_task(){ ..  } wh changed to zeroBSCRM_task_addEdit
 * 
 *  it breaks down the form into sub elements, each controlled by a function, so can edit tweak just certain parts
 *  easier than a 200 line form in metaboxes.
 * 
 * 
 *  NOTE this file is not directly called in v3 as it runs from the MetaBoxes3.Events.php file
 * 
 * 
 */

function zeroBSCRM_task_addEdit($taskID = -1){

    $task_meta = zeroBSCRM_task_getMeta($taskID);
   // zbs_prettyprint($task_meta);

    $uid = get_current_user_id();
    $zbsThisOwner = zeroBS_getOwner($taskID,true,'zerobs_event');

  /*  if($uid != $zbsThisOwner['ID']){

        $html = "<div class='ui segment'>";
        $html .= __("You cannot edit this task. It is not your task. Ask the task owner to modify");

    }else{ */


    if($taskID > 0){
        $html = "<div id='task-".$taskID."'>";
    }else{
        $html = "<div id='task-0'>";
    }

    $html .= zeroBSCRM_task_ui_clear();

    $html .= "<input id='zbs-task-title' name='event_post_title' type='text' value='".$task_meta['title']."' placeholder='".$task_meta['placeholder']."' />";    

    $html .= zeroBSCRM_task_ui_mark_complete($task_meta, $taskID);

    $html .= zeroBSCRM_task_ui_clear();

    $html .= zeroBSCRM_task_ui_assignment($task_meta, $taskID);

    $html .= zeroBSCRM_task_ui_date($task_meta);

    $html .= zeroBSCRM_task_ui_clear();

    $html .= zeroBSCRM_task_ui_description($task_meta);

    $html .= zeroBSCRM_task_ui_clear();

    $html .= zeroBSCRM_task_ui_reminders($task_meta, $taskID);

    $html .= zeroBSCRM_task_ui_clear();

    $html .= zeroBSCRM_task_ui_showOnCalendar($task_meta, $taskID);

    $html .= zeroBSCRM_task_ui_clear();

    if(class_exists('ZeroBSCRM_ClientPortalPro')){
        $html .= zeroBSCRM_task_ui_showOnPortal($task_meta);
    }

    $html .= zeroBSCRM_task_ui_clear();

    $html .= zeroBSCRM_task_ui_for($task_meta, $taskID);

    $html .= zeroBSCRM_task_ui_clear();

    $html .= zeroBSCRM_task_ui_for_co($task_meta);


   // $html .= zeroBSCRM_task_ui_save($task_meta);



    $html .= "</div>";

    


    return $html;

}

function zeroBSCRM_task_ui_clear(){
    return '<div class="clear zbs-task-clear"></div>';
}

#} the assign to CRM user UI
function zeroBSCRM_task_ui_assignment($task_meta = array(), $taskID = -1){
    $zbsEventsUsers = zeroBS_getPossibleEventOwners();

    if(array_key_exists('owner', $task_meta)){
        $currentEventUserID = $task_meta['owner'];
    }else{
        $currentEventUserID = -1;
    }


    

    $html = "";
    if($currentEventUserID == "" || $currentEventUserID == -1){
        $html .= "<div class='no-owner'><i class='ui icon user circle zbs-unassigned'></i>";
    }else{
        $owner_info = get_userdata( $currentEventUserID );
        $display_name = $owner_info->data->display_name;
        $ava_args = array(
            'class' => 'rounded-circle'
        );
        $avatar = get_avatar( $currentEventUserID, 30, '', $display_name, $ava_args );
        $html .= "<div class='no-owner'>" . $avatar . "<div class='dn'></div>";
    }

    $uid = get_current_user_id();
    $zbsThisOwner = zeroBS_getOwner($taskID,true,'zerobs_event');


    $linked_cal = get_post_meta($taskID,'zbs_outlook_id',true);

    if($uid != $zbsThisOwner['ID']){
        //then it is LOCKED and cannot be changed to another owner?
    }


    $html .= '<div class="owner-select" style="margin-left:30px;"><select class="form-controlx" id="zerobscrm-owner" name="zerobscrm-owner" style="width:80%display:block;height:36px;">';
    $html .= '<option value="-1">'. __('None',"zero-bs-crm") .'</option>';
    
    if (count($zbsEventsUsers) > 0) foreach ($zbsEventsUsers as $possOwner){
        $html .= '<option value="' . $possOwner->ID .'"'; 
        if ($possOwner->ID == $zbsThisOwner['ID']) $html .= ' selected="selected"';
        $html .= '>' . esc_html( $possOwner->display_name ) . '</option>';
    } 
    $html .= '</select></div></div>';

    



    return $html;

}

function zeroBSCRM_task_ui_mark_complete($task_meta = array(), $taskID = -1){
    $html = "<div class='mark-complete-task'>";

        if(!array_key_exists('complete', $task_meta)){
            $task_meta['complete'] = 0;
        }
    
        if($task_meta['complete'] == 1){
            $html .= "<div id='task-mark-incomplete' class='task-comp incomplete'><button class='ui button green' data-taskid='".$taskID."'><i class='ui icon check white'></i>".__('Completed','zero-bs-crm')."</button></div>";
            $complete = "<input type='hidden' id='zbs-task-complete' value = '1' name = 'zbs-task-complete'/>";
        }else{
            $html .= "<div id='task-mark-complete' class='task-comp complete'><button class='ui button' data-taskid='".$taskID."'><i class='ui icon check'></i>".__('Mark Complete','zero-bs-crm')."</button></div>";
            $complete = "<input type='hidden' id='zbs-task-complete' value = '-1' name = 'zbs-task-complete'/>";
        }
    $html .= "</div>";
    $html .= $complete;
    return $html;
}

#} CRM company / contact assignment

#} the assign to CRM user UI
function zeroBSCRM_task_ui_for($task_meta = array()){

    $html = "";

    $html .= "<div class='no-contact zbs-task-for-who'><div class='zbs-task-for-help'><i class='ui icon users'></i> " . __('Contact','zero-bs-crm') . "</div>";

    //need UI for selecting who the task is for (company, then contaxt)
    $custName = ''; $custID = '';

    if(array_key_exists('customer', $task_meta)){
        $custID = $task_meta['customer'];
    }


    if (!empty($custID)){
        $contact = zeroBS_getCustomer($custID);
        if (isset($contact) && isset($contact['name'])) $custName = $contact['name'];
    }
    
    #} Output
    $html .= '<div class="zbs-task-for">' . zeroBSCRM_CustomerTypeList('zbscrmjs_customer_setCustomer',$custName,true) . "</div>";
    $html .= '<input type="hidden" name="zbsci_customer" id="zbsci_customer" value="' .$custID .'" />';
    
    $html .= "<div class='clear'></div></div>";

    return $html;

}

function zeroBSCRM_task_ui_for_co($task_meta = array()){


    $html = "";
    if(zeroBSCRM_getSetting('companylevelcustomers')){
        $html .= "<div class='no-contact zbs-task-for-who'><div class='zbs-task-for-help'><i class='ui icon building outline'></i> " . __('Company','zero-bs-crm') . "</div>";

        //need UI for selecting who the task is for (company, then contaxt)
        $coName = ''; $coID = '';

        if(array_key_exists('company', $task_meta)){
            $coID = $task_meta['company'];
        }
        if (!empty($coID)){
            $co = zeroBS_getCompany($coID);
            if (isset($co) && isset($co['meta']) && isset($co['meta']['coname'])) $coName = $co['meta']['coname'];
        }
        
        #} Output
        $html .= '<div class="zbs-task-for">' . zeroBSCRM_CompanyTypeList('zbscrmjs_customer_setCompany',$coName,true) . "</div>";
        $html .= '<input type="hidden" name="zbsci_company" id="zbsci_company" value="' .$coID .'" />';
        
        $html .= "<div class='clear'></div></div>";

    }

    return $html;

}


#} the date picker UI
function zeroBSCRM_task_ui_date($task_meta = array()){
    $html = "<div class='no-task-date'><i class='ui icon calendar outline'></i> ". __('Date','zero-bs-crm') ." </div>";


    if(!array_key_exists('from',$task_meta)){
        // starting date
        //$start_d = date('m/d/Y H') . ":00:00";
        //$end_d =  date('m/d/Y H') . ":00:00";
        // wh modified to now + 1hr
        $start_d = date('d F Y H:i:s',(time()+3600));
        $end_d =  date('d F Y H:i:s',(time()+3600+3600));
    }else{
        $d = new DateTime($task_meta['from']);
        $start_d = $d->format('d F Y H:i:s');
         $d = new DateTime($task_meta['to']);
         $end_d = $d->format('d F Y H:i:s');
    }

    // wh note: added autocomplete value here to STOP gchrome autosuggests :)
    $html = '<div class="no-task-date"><input type="text" id="daterange" class="form-control" name="daterange" value="' . $start_d . ' - ' . $end_d .'" autocomplete="zbs-'.time() . '-task-date" /></div>';

    $html .= '<input type="hidden" id="zbs_from" name="zbs_event_from" value="' . $start_d .'"/>';
    $html .= '<input type="hidden" id="zbs_to" name="zbs_event_to" value="' . $end_d . '"/>';
    
    return $html;
}

#} the save UI button
function zeroBSCRM_task_ui_save($task_meta = array()){
    $html = "<button class='ui button blue large zbs-save-event'>". __('Save','zero-bs-crm') ." </button>";
    return $html;
}


function zeroBSCRM_task_ui_reminders($task_meta = array(), $taskID = -1){
    $show = true;
    if(array_key_exists('notify_crm', $task_meta)){
        $show = $task_meta['notify_crm'];
    }
    $html = "<div class='remind_task'>";
        $html .= '<div><input name="zbs_remind_task_24" id="zbs_remind_task_24" type="checkbox"';
          if($show){ $html .= 'checked="checked"';};
        $html .= "/><label for='zbs_remind_task_24'>" .__('Remind CRM member around 24 hours before (if not complete)','zero-bs-crm') ."</label></div>";
//    $html .= "<a class='ui label blue' href='". admin_url('admin.php?page=zbs-reminders') ."' target='_blank' style='margin-top: 0.2em;margin-right: 0.3em;'>" .__('Add more reminders', 'zero-bs-crm') . "</a>";
    $html .= "</div>";

    #} Better reminders in Calendar Pro :-) 
    $html = apply_filters('zbs_task_reminders', $html);

    return $html;
}

function zeroBSCRM_task_ui_showOnCalendar($task_meta = array(), $taskID = -1){

    $show = true;
    if(array_key_exists('showoncal', $task_meta)){
        $show = $task_meta['showoncal'];
    }
    $linked_cal = get_post_meta($taskID,'zbs_outlook_id',true);
    $html = "<div class='show-on-calendar'>";
        if($linked_cal != ''){
            $html .= '<div class="zbs-hide"><input name="zbs_show_on_calendar" id="zbs_show_on_calendar" type="checkbox"';
            if($show){ $html .= 'checked="checked"';};
            $html .= "/></div><div class='outlook-event'>" . __("Linked to Online Calendar (will always show on CRM Calendar)","zero-bs-crm") . "</div>";
        }else{
            $html .= '<div><input name="zbs_show_on_calendar" id="zbs_show_on_calendar" type="checkbox"';
            if($show){ $html .= 'checked="checked"';};
            $html .= "/><label for='zbs_show_on_calendar'>" .__('Show on Calendar','zero-bs-crm') ."</label></div></div>";
        }

    #} anything else we may want to filter with.
    $html = apply_filters('zbs_calendar_add_to_calendar', $html);

    return $html;
    
}


function zeroBSCRM_task_ui_showOnPortal($task_meta = array()){
    $show = true;
    if(array_key_exists('showonportal', $task_meta)){
        $show = $task_meta['showonportal'];
    }
    $html = "<div class='show-on-calendar'>";
        $html .= '<div><input name="zbs_show_on_portal" id="zbs_show_on_portal" type="checkbox"';
          if($show){ $html .= 'checked="checked"';};
        $html .= "/><label for='zbs_show_on_portal'>" .__('Show on Client Portal (if assigned to contact)','zero-bs-crm') ."</label></div>";
    $html .= "</div>";
    return $html;
}



function zeroBSCRM_task_ui_comments($pID){
    $args = array();
    $html = comment_form($args, $pID);
    return $html;
}

function zeroBSCRM_task_ui_description($task_meta = array()){
    $html = "<div class='clear'></div><div class='zbs-task-desc'><textarea id='zbs_event_notes' name='zbs_event_notes' placeholder='".__('Task Description....','zero-bs-crm')."'>";
    if (isset($task_meta) && isset($task_meta['notes'])) $html .= $task_meta['notes']; 
    $html .= "</textarea></div>";
    return $html; 
}




#} Mark as included :)
define('ZBSCRM_INC_TASKUI',true);


?>