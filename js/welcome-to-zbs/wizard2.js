/*
            var t = {
                action: "zbs_wizard_fin",
                zbs_crm_name: zbsOptions.zbs_crm_name,
                zbs_crm_type: zbsOptions.zbs_crm_type,
                zbs_crm_other: zbsOptions.zbs_crm_other,
                zbs_crm_override: zbsOptions.zbs_crm_override,
                zbs_crm_subscribed: zbsOptions.zbs_crm_subscribed,
                zbs_crm_first_name: zbsOptions.zbs_crm_first_name,
                zbs_crm_last_name: zbsOptions.zbs_crm_last_name,
                zbs_crm_email: zbsOptions.zbs_crm_email,
                zbs_crm_share_ess: zbsOptions.zbs_crm_share_essentials,
                security: jQuery( '#zbswf-ajax-nonce' ).val()
            };*/
    var zbsOptions = {
        zbs_crm_name:'Zero BS CRM',
        zbs_crm_type: '', 
        zbs_crm_other: '',
        
        zbs_crm_subscribed: 0,
        zbs_crm_subblogname: '', 
        zbs_crm_first_name: '',
        zbs_crm_last_name: '',
        zbs_crm_email: '',
        zbs_crm_share_essentials: 0, 
        
        zbs_crm_curr: '',
        zbs_crm_menu_style: 2, 
        zbs_b2b: 0,
        zbs_quotes: 1,
        zbs_invoicing: 1,
        zbs_forms: 1
    };
jQuery(document).ready(function () {
    zbs_crm_js_updatePage2();
    
    jQuery('.zbs-menu-opt').click(function(){
        var sel = jQuery(this).attr('data-select');
        jQuery('#' + sel).prop( "checked", true );
    });
    
    jQuery('#zbs_crm_name').keyup(function(event) {
        zbs_crm_name_change();
    });
    var navListItems = jQuery('div.setup-panel div a'),
            allWells = jQuery('.setup-content'),
            allNextBtn = jQuery('.nextBtn'),
            alBackBtn = jQuery('.backBtn'),
            alListBtn = jQuery('.stepwizard-step');
    allWells.hide();
    navListItems.click(function (e) {
        e.preventDefault();
        var jQuerytarget = jQuery(jQuery(this).attr('href')),
                jQueryitem = jQuery(this);
        if (!jQueryitem.hasClass('disabled')) {
            navListItems.removeClass('btn-primary').addClass('btn-default');
            jQueryitem.addClass('btn-primary');
            allWells.hide();
            jQuerytarget.show();
            jQuerytarget.find('input:eq(0)').focus();
        }
    });
    alListBtn.click(function(){
        
        zbsJS_welcomeWizard_update_deets();
    });
    allNextBtn.click(function(){
        var curStep = jQuery(this).closest(".setup-content"),
            curStepBtn = curStep.attr("id"),
            nextStepWizard = jQuery('div.setup-panel div a[href="#' + curStepBtn + '"]').parent().next().children("a"),
            curInputs = curStep.find("input[type='text'],input[type='url']"),
            isValid = true;
        jQuery(".form-group").removeClass("has-error");
        for(var i=0; i<curInputs.length; i++){
            if (!curInputs[i].validity.valid){
                isValid = false;
                jQuery(curInputs[i]).closest(".form-group").addClass("has-error");
            }
        }
        if (isValid)
            nextStepWizard.removeAttr('disabled').trigger('click');
         //console.log(window.zbsOptions);  
    });
    jQuery('div.setup-panel div a.btn-primary').trigger('click');
    jQuery('.zbs-gogogo').unbind('click').bind('click',function(e){
        
        zbsJS_welcomeWizard_update_deets();
    
        
        //console.log("finito with ",zbsOptions);
        if(jQuery(this).hasClass('disabled')){
            return false;
        }
        jQuery(this).addClass('disabled');
            
            var t = window.zbsOptions;
            t.action = 'zbs_wizard_fin';
            t.security = jQuery( '#zbswf-ajax-nonce' ).val();
                
                    jQuery('.laststage').hide();
                    jQuery('.finishingupblock').show();
                    jQuery('.finishblock').hide();
                
                i = jQuery.ajax({
                    url: window.ajaxurl,
                    type: "POST",
                    data: t,
                    dataType: "json"
                });
                i.done(function(msg) {
                    
                    jQuery('.laststage').hide();
                    jQuery('.finishingupblock').hide();
                    jQuery('.finishblock').show();
                });
                i.fail(function(msg) {
                    
                    jQuery('.laststage').hide();
                    jQuery('.finishingupblock').hide();
                    jQuery('.finishblock').show();
                });
    });
});
function zbs_biz_select(){
    if (jQuery('#zbs_crm_type').val() == 'Other'){
        jQuery('#zbs_other_details').show();
        jQuery('#zbs_other_label').removeClass('hide');
        zbsOptions.zbs_crm_type = jQuery("#zbs_crm_type").val();
    }else{
        jQuery('#zbs_other_details').hide();
        jQuery('#zbs_other_label').addClass('hide');
        zbsOptions.zbs_crm_type = jQuery("#zbs_crm_type").val();
    }
    
    setTimeout(function(){
        zbs_crm_js_updatePage2();
    },0);
}
function zbs_crm_name_change(){
    crm_name = jQuery("#zbs_crm_name").val();
    
    if (crm_name != "") 
        jQuery("#crm-name").html(crm_name);
    else
        jQuery("#crm-name").html('Zero BS CRM');
}
function zbs_crm_js_updatePage2(){
    
    var b2bMode = jQuery("#zbs_b2b").is(':checked');
    var userType = jQuery("#zbs_crm_type").val();
    var userArea = 'smallbiz';
    
    switch (userType){
        case 'Freelance':
        case 'FreelanceDev':
        case 'FreelanceDes':
            userArea = 'freelance';
            break;
        case 'ecommerceWoo':
        case 'ecommerceShopify':
        case 'ecommerceOther':
            userArea = 'ecommerce';
            break;
    }
    
    if (b2bMode){
        jQuery('.zbs-nob2b-show').hide();
        jQuery('.zbs-b2b-show').show();
    } else {
        jQuery('.zbs-b2b-show').hide();
        jQuery('.zbs-nob2b-show').show();
    }
    switch (userArea){
        case 'freelance':
            jQuery('.zbs-freelancer-lead').show();
            jQuery('.zbs-smallbiz-lead').hide();
            jQuery('.zbs-ecomm-lead').hide();
            jQuery('.zbs-show-paysync').show();
            jQuery('.zbs-show-woosync').hide();
            jQuery('.zbs-show-starterbundle').show();
            break;
        case 'smallbiz':
            jQuery('.zbs-freelancer-lead').hide();
            jQuery('.zbs-smallbiz-lead').show();
            jQuery('.zbs-ecomm-lead').hide();
            jQuery('.zbs-show-paysync').show();
            
            jQuery('.zbs-show-starterbundle').show();
            break;
        case 'ecommerce':
            jQuery('.zbs-freelancer-lead').hide();
            jQuery('.zbs-smallbiz-lead').hide();
            jQuery('.zbs-ecomm-lead').show();
            jQuery('.zbs-show-paysync').show();
            
            jQuery('.zbs-show-starterbundle').show();
            break;
    }
}
function zbsJS_welcomeWizard_update_deets(){
        
        window.zbsOptions.zbs_crm_name = jQuery("#zbs_crm_name").val();
        window.zbsOptions.zbs_crm_other = jQuery("#zbs_other_details").val();
        window.zbsOptions.zbs_crm_curr = jQuery("#zbs_crm_curr").val();
        if(jQuery("#zbs-menu-opt-choice-override").is(':checked'))
            window.zbsOptions.zbs_crm_menu_style = 3;
        if(jQuery("#zbs-menu-opt-choice-slimline").is(':checked'))
            window.zbsOptions.zbs_crm_menu_style = 2;
        if(jQuery("#zbs-menu-opt-choice-full").is(':checked'))
            window.zbsOptions.zbs_crm_menu_style = 1;
        if(jQuery("#zbs_b2b").is(':checked'))
            window.zbsOptions.zbs_b2b = 1;
        else
            window.zbsOptions.zbs_b2b = 0;
        if(jQuery("#zbs_ess").is(':checked'))
            window.zbsOptions.zbs_crm_share_essentials = 1;
        else
            window.zbsOptions.zbs_crm_share_essentials = 0;
        
        if(jQuery("#zbs_quotes").is(':checked'))
            window.zbsOptions.zbs_quotes = 1;
        if(jQuery("#zbs_invoicing").is(':checked'))
            window.zbsOptions.zbs_invoicing = 1;
        if(jQuery("#zbs_forms").is(':checked'))
            window.zbsOptions.zbs_forms = 1;
        
        window.zbsOptions.zbs_crm_subblogname = jQuery("#zbs_crm_subblogname").val(); 
        window.zbsOptions.zbs_crm_first_name = jQuery("#zbs_crm_first_name").val();
        window.zbsOptions.zbs_crm_last_name = jQuery("#zbs_crm_last_name").val();
        window.zbsOptions.zbs_crm_email = jQuery("#zbs_crm_email").val();
        if(jQuery("#zbs_sub").is(':checked')) 
            window.zbsOptions.zbs_crm_subscribed = 1;
        else
            window.zbsOptions.zbs_crm_subscribed = 0;
}
