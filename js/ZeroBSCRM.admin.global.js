/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.0
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 17/06/2016
 */
jQuery(document).ready(function(){

	// THIS IS FOR POTENTIALLY GLOBAL STUFF ONLY! NO SPECIFICS (E>G> INVOICING)


		// set locale for all date stuff
		zbscrm_JS_momentInit();
 
		// Infobox:
		zbscrm_JS_infoBoxInit();

		// any typeaheads:
		zbscrm_JS_Bind_Typeaheads();

		// all date ranges:
		zbscrm_JS_bindDateRangePicker();

		// field validation
		zbscrm_JS_bindFieldValidators();

		// close logs
		zbscrm_JS_bindCloseLogs();

		// check dirty/clean
		zbscrm_JS_watchInputsAndDirty();
		zbscrm_JS_dirtyCatch();

		// menu stuff
		zbscrm_JS_adminMenuDropdown();

		// compat classes
		zbscrm_JS_compatClasses();

		// screenopts
		zeroBSCRMJS_bindScreenOptions();

		// contact global funcs
		zeroBSCRMJS_bindGlobalObjFuncs();

		// dissmiss msgs etc.
		zeroBSCRMJS_bindGlobalDismiss();

});

// returns a DAL or presumed DAL int
function zbscrm_JS_DAL(){
	
	var DAL = 2; if (typeof zbs_root.dal != "undefined") var DAL = parseInt(zbs_root.dal);

	return DAL;

}

// Detects if there are any compatability classes to add against body
// allows us to apply specific styles etc. e.g. Material Admin compat
function zbscrm_JS_compatClasses(){

	/* Now done in php with zeroBSCRM_bodyClassMods
	... but Material admin for some reason ignores that, so leaving in */
	// Material Admin check - adds class if using
	var customAdmin = window.zbscrmjs_custom_admin;
	if(customAdmin == 'material'){
		jQuery('body').addClass('zbs-compat-material-admin');
	} 

}

// this is for any moment init stuff (pre date picker etc.)
function zbscrm_JS_momentInit(){

	// language
	// set locale, if non standard 
	// https://stackoverflow.com/questions/17493309/how-do-i-change-the-language-of-moment-js
	if (typeof window.zbs_root.locale_short != "undefined" && window.zbs_root.locale_short != "en") moment.locale(window.zbs_root.locale_short);
	// debug console.log('locale:',window.zbs_root.locale_short);

	// timezone offset?
    // Here we get the UTS offset in minutes from zbs_root
    var offsetMins = 0; if (typeof window.zbs_root.timezone_offset_mins != "undefined") offsetMins = parseInt(window.zbs_root.timezone_offset_mins);
    

	// any .zbs-datemoment-since
	jQuery('.zbs-datemoment-since').each(function(ind,ele){

		if (jQuery(this).attr('data-zbs-created-uts')){

			var thisUTS = parseInt(jQuery(this).attr('data-zbs-created-uts'));
			if (thisUTS > 0){

                // Here we create a moment instance in correct timezone(offset) using original created unix timestamp in UTC
                var createdMoment = moment.unix(thisUTS).utcOffset(offsetMins);
                                
				// dump moment readable into html
				jQuery(this).html(createdMoment.fromNow());
				
			}
		}

	});

}

// Mikes fancy new top drop-down-menu :)
var zbscrmjs_adminMenuBlocker = false;
function zbscrm_JS_adminMenuDropdown(){

	// popup menu
	zbscrm_JS_initMenuPopups();

	jQuery(document).ready(function(){

		// hopscotch?
		// if defined & virgin & has menu
		if (window.zbscrmjs_hopscotch_virgin === 1 && typeof hopscotch != "undefined" && jQuery('#zbs-admin-top-bar').length) {

			// UNSET tour (unless somethign went wrong)
			//console.log('state:',hopscotch.getState());
			try {
						if (hopscotch.getState() != null) hopscotch.endTour();
			}
			catch(err) {
			}
			
			if (typeof window.zbscrmjs_hopscotch_squash == "undefined") hopscotch.startTour(window.zbsTour,0);

		}

	});


	// if calypso...
	if (zbscrm_JS_isCalypso()) {

		// if calypso, we save the #wpwrap top value so we can toggle when we fullscreen
		window.zbscrm_JS_wpwraptop = jQuery('#wpwrap').css('top');

		// if calypso, loading on an embed page, already full screen, need to run this to re-adjust/hide:
    	setTimeout(function(){

    		if (!jQuery('#zbs-main-logo-wrap').hasClass('menu-open')){ 
    			zbscrm_JS_fullscreenModeOn(jQuery('#zbs-main-logo-wrap')); 
    		}

	    },0);

    }


	// bind the toggle
    jQuery('#zbs-main-logo-wrap').unbind('click').click(function(e){

    	if(!window.zbscrmjs_adminMenuBlocker){

    		window.zbscrmjs_adminMenuBlocker = true;

        	if (jQuery(this).hasClass('menu-open')){

        		// go fullscreen
        		zbscrm_JS_fullscreenModeOn(this);

        	} else {

        		// close fullscreen mode
        		zbscrm_JS_fullscreenModeOff(this);

        	}
        }
    });

}

// Enable 'full screen mode'
function zbscrm_JS_fullscreenModeOn(wrapperElement){

	// adjust classes & hide menu bar etc.
	// any work here, take account of calypsoify results
	jQuery('body').addClass('zbs-fullscreen');
	jQuery(wrapperElement).removeClass('menu-open'); 
	jQuery("#wpadminbar, #adminmenuback, #adminmenuwrap, #calypso-sidebar-header").hide();

	// if we're in calypso, also adjust this:
	if (zbscrm_JS_isCalypso() && typeof window.zbscrm_JS_wpwraptop != "undefined") jQuery('#wpwrap').css('top',0);

	// redraw the overlay
	if (typeof hopscotch != "undefined") hopscotch.refreshBubblePosition();

	// & save state
	var data = {
		'action': 'zbs_admin_top_menu_save',
		'sec': window.zbscrmjs_topMenuSecToken,
		'hide': 1
	};
	jQuery.ajax({
	      type: "POST",
	      url: ajaxurl,
	      "data": data,
	      dataType: 'json',
	      timeout: 20000,
	      success: function(response) {
	      	// blocker
	      	window.zbscrmjs_adminMenuBlocker = false;
	      },
	      error: function(response){ 
	      	// blocker
	      	window.zbscrmjs_adminMenuBlocker = false;
	      }
	}); 

}

// Disable 'full screen mode'
function zbscrm_JS_fullscreenModeOff(wrapperElement){

	// adjust classes & show menu bar etc.
	// any work here, take account of calypsoify results
	jQuery('body').removeClass('zbs-fullscreen');
	jQuery(wrapperElement).addClass('menu-open');
	jQuery("#wpadminbar, #adminmenuback, #adminmenuwrap, #calypso-sidebar-header").show();

	// if we're in calypso, also adjust this:
	if (zbscrm_JS_isCalypso() && typeof window.zbscrm_JS_wpwraptop != "undefined") jQuery('#wpwrap').css('top',window.zbscrm_JS_wpwraptop);

	// redraw the overlay
	if (typeof hopscotch != "undefined") hopscotch.refreshBubblePosition();

	// & save state
	var data = {
		'action': 'zbs_admin_top_menu_save',
		'sec': window.zbscrmjs_topMenuSecToken,
		'hide': 0
	};
	jQuery.ajax({
	      type: "POST",
	      url: ajaxurl,
	      "data": data,
	      dataType: 'json',
	      timeout: 20000,
	      success: function(response) {
	      	// blocker
	      	window.zbscrmjs_adminMenuBlocker = false;
	      },
	      error: function(response){ 
	      	// blocker
	      	window.zbscrmjs_adminMenuBlocker = false;
	      }
	});  

}

// used by hopscotch to intefere, as well as on init
function zbscrm_JS_initMenuPopups(){
	if (typeof jQuery('#zbs-user-menu-item').popup != "undefined"){
		jQuery('#zbs-user-menu-item')
		  .popup({
		    popup : jQuery('#zbs-user-menu'),
			hoverable  : true,
		    on    : 'hover'
		  });
	}
}

// watches any input with class zbs-watch-input and if they're changed from post dom, it'll flag an input with thier id_dirtyflag
// lets you see in post if a field has changed :)
// NOTE this is separate from zbscrm_JS_dirtyCatch(); below
var zbscrmjsDirtyLog = {};
function zbscrm_JS_watchInputsAndDirty(){

	jQuery('.zbs-watch-input').each(function(ind,ele){

		var dirtyID = jQuery(ele).attr('name') + '_dirtyflag';

		if (jQuery('#'+dirtyID).length > 0){

			// log orig
			window.zbscrmjsDirtyLog[dirtyID] = jQuery(this).val();
		}

	});

	jQuery('.zbs-watch-input').bind('change',function(){

		var dirtyID = jQuery(this).attr('name') + '_dirtyflag';

		if (jQuery('#'+dirtyID).length > 0){

			// compare to orig
			if (jQuery(this).val() != window.zbscrmjsDirtyLog[dirtyID]) {

				// dirty
				jQuery('#'+dirtyID).val('1');

			} else {
				
				// clean
				jQuery('#'+dirtyID).val('0');

			}
		}

	});

}

// manages whether or not things have changed on a page that might need you to prompt before leaving
// e.g. contact deets changed, but not saved
var zbscrmjsPageChanges = {};var zbscrmjsPageData = {};
function zbscrm_JS_dirtyCatch(){

	jQuery('.zbs-dc').each(function(ind,ele){

		// log orig
		window.zbscrmjsPageData[jQuery(ele).attr('name')] = jQuery(this).val();
	

	});

	jQuery('.zbs-dc').bind('change',function(){

		// compare to orig
		if (jQuery(this).val() != window.zbscrmjsPageData[jQuery(this).attr('name')]) {

			// dirty
			//window.zbscrmjsPageChanges[jQuery(this).attr('name')] = 1;
			zbscrm_JS_addDirty(jQuery(this).attr('name'));

		} else {
			
			// clean
			//delete window.zbscrmjsPageChanges[jQuery(this).attr('name')];
			zbscrm_JS_delDirty(jQuery(this).attr('name'));

		}

		// console.log('change',window.zbscrmjsPageChanges);

	});

}
// these are used by other js (not just above dirtyCatch)
function zbscrm_JS_addDirty(key){

	window.zbscrmjsPageChanges[key] = 1;
	
}
function zbscrm_JS_delDirty(key){

	delete window.zbscrmjsPageChanges[key];
	
}


function zbscrm_JS_bindDateRangePicker(){

	/* 
	.zbs-date = date
	.zbs-date.zbs-empty-start = date + empty to start with
	.zbs-date-range = date range
	.zbs-date-time = date time
	.zbs-date-time-future = date time only in future 
	*/


    /*// All datepickers
    jQuery('.zbs-date').datetimepicker({
      timepicker:false,
      format:'d.m.Y'
    });

     Moved to daterangepicker 03/06/16 */

     // if daterangepicker
     if (typeof jQuery('.zbs-date').daterangepicker == "function"){

		// default hard typed
     	var localeOpt = {
	            format: "DD.MM.YYYY",
	            cancelLabel: 'Clear'
	    }; 

     	// this lets you override - see zeroBSCRM_date_localeForDaterangePicker + core zbs_root
     	if (typeof window.zbs_root.localeOptions != "undefined") localeOpt = zbscrm_JS_clone(window.zbs_root.localeOptions);

     	var dateRangePickerOpts = {
	        singleDatePicker: true,
	        showDropdowns: true,
	        locale: localeOpt,
	        timePicker: false // by default
		};
		

	    // where they have the class .zbs-empty-start we do not auto - inject todays date
	    jQuery('.zbs-date').not('.zbs-empty-start').daterangepicker(dateRangePickerOpts, 
	    function(start, end, label) {
	        //var years = moment().diff(start, 'years');
	        //alert("You are " + years + " years old.");
	    });

	    //... for those with that class we do this:
	    // based on https://github.com/dangrossman/daterangepicker/issues/815 'dangrossman commented on Sep 25, 2015'
	    dateRangePickerOpts.autoUpdateInput = false;
	    jQuery('.zbs-date.zbs-empty-start').daterangepicker(dateRangePickerOpts, 
	    function(chosen_date) {
		  this.element.val(chosen_date.format(localeOpt.format));
		});



		// Date Range Picker
		jQuery('.zbs-date-range').daterangepicker({
			autoUpdateInput: false,
			locale: localeOpt,//{format: 'DD.MM.YYYY',cancelLabel: 'Clear'},
			ranges: {'Today': [moment(), moment()],'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],'Last 7 Days': [moment().subtract(6, 'days'), moment()],'Last 30 Days': [moment().subtract(29, 'days'), moment()],'This Month': [moment().startOf('month'), moment().endOf('month')],'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]},
			callback: function (start,end,period){

		        //? jQuery('#zbs-crm-customerfilter-addedrange-reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
		        //? jQuery('#zbs-crm-customerfilter-addedrange').val(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));

			}
		});
		//jQuery(dateRangeSelector).on('cancel.daterangepicker', function(ev, picker) { jQuery(this).val(''); zbsJSFilterDateRangeClear(); });
		/* 
function zbscrm_JS_DateRangeCallback(start, end) {
    }
function zbscrm_JS_DateRangeClear() {
        jQuery('#zbs-crm-customerfilter-addedrange-reportrange span').html('');
        jQuery('#zbs-crm-customerfilter-addedrange').val('');
    }*/


		// Datetime Picker single (this is used in MC2, be careful changing)
		// because this uses times, we have to FORCE append time:
		// NOTE this doesn't fire if date-time-future, which is next jq call
		// NOTE: rather than corrupting useful .format here, we just set .formatIncTime if not set
		//if (localeOpt.format.indexOf('hh:mm') == -1) localeOpt.format += ' hh:mm';
		if (typeof localeOptTime == "undefined"){
			var localeOptTime = zbscrm_JS_clone(localeOpt);
			if (localeOptTime.format.indexOf('hh:mm') == -1) localeOptTime.format += ' hh:mm A';
		}
		jQuery('.zbs-date-time').not('.zbs-date-time-future').daterangepicker({
    		timePicker: true,
    		singleDatePicker: true,
			locale: localeOptTime,//{format: 'DD.MM.YYYY',cancelLabel: 'Clear'},
			callback: function (start,end,period){

		        //? jQuery('#zbs-crm-customerfilter-addedrange-reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
		        //? jQuery('#zbs-crm-customerfilter-addedrange').val(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));

			}
		});
		jQuery('.zbs-date-time-future').not('.zbs-date-time').daterangepicker({
    		timePicker: true,
    		minDate : new Date(),
    		singleDatePicker: true,
			locale: localeOptTime,//{format: 'DD.MM.YYYY',cancelLabel: 'Clear'},
			callback: function (start,end,period){

		        //? jQuery('#zbs-crm-customerfilter-addedrange-reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
		        //? jQuery('#zbs-crm-customerfilter-addedrange').val(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));

			}
		});

	}

}


function zbscrm_JS_bindFieldValidators(){


    jQuery('.numbersOnly').keyup(function () {
        var rep = this.value.replace(/[^0-9\.]/g, '');
        if (this.value != rep) {
           this.value = rep;
        }
    });

    jQuery('.intOnly').keyup(function () {
        var rep = this.value.replace(/[^0-9]/g, '');
        if (this.value != rep) {
           this.value = rep;
        }
    });


}


function zbscrm_JS_infoBoxInit(){

	jQuery('.zbs-infobox').each(function(ind,ele){

		// build sub html
		var infoHTML = jQuery(ele).html();

		// inject new
		jQuery(ele).html('<i class="fa fa-info-circle zbs-help-ico"></i><div class="zbs-infobox-detail"></div>');

		// post render, inject
		setTimeout(function(){

			// localise
			var lEle = ele;

			// inject orig html
			jQuery('.zbs-infobox-detail',jQuery(lEle)).html(infoHTML);

			// add live class (show it)
			jQuery(lEle).addClass('zbs-live');

			// Bind
			setTimeout(function(){
					// mouse over
					jQuery('.zbs-infobox').hover(function(){

						// up opacity
						jQuery('i.zbs-help-ico',jQuery(this)).css('opacity','1');

						// show sub div (detail)
						jQuery('.zbs-infobox-detail',jQuery(this)).show();

					},function(){

						// up opacity
						jQuery('i.zbs-help-ico',jQuery(this)).css('opacity','0.6');

						// hide sub div (detail)
						jQuery('.zbs-infobox-detail',jQuery(this)).hide();

					});
			},0);

		},0);

	});

}

// binds typeaheads/bloodhound
function zbscrm_JS_Bind_Typeaheads(){
	
	zbscrm_JS_Bind_Typeaheads_Customers();
	zbscrm_JS_Bind_Typeaheads_Companies();

}
function zbscrm_JS_Bind_Typeaheads_Customers(){

	// if any?
	if (jQuery('.zbstypeaheadwrap .zbstypeahead').length > 0){

		// one prefetch bloodhound for all instances		
		var customers = new Bloodhound({
			//datumTokenizer: Bloodhound.tokenizers.whitespace,
			datumTokenizer: function (datum) {
						        return Bloodhound.tokenizers.whitespace(datum.name);
						    },
			queryTokenizer: Bloodhound.tokenizers.whitespace,
    		identify: function(obj) { return obj.id; },
			// https://github.com/twitter/typeahead.js/blob/master/doc/bloodhound.md#prefetch
			prefetch: //'../data/countries.json'
			{	// prefatch options
				url: window.zbscrmBHURLCustomers,
				ttl: 300000, // 300000 = 5 mins, 86400000 = 1 day (default) - ms			
	            transform: function(response) {
	                return jQuery.map(response, function (obj) {
	                    return {
	                    	id: obj.id,
		                    name: obj.name,
		                    created: obj.created,
		                    //DAL2: //email: obj.meta.email
		                    email: obj.email
	                    }
	                });
	            }
			},
			remote: {
				// this checks when users type, via ajax search ... useful addition to (cached) prefetch
				url: window.zbscrmBHURLCustomers + '&s=%QUERY',
          		wildcard: '%QUERY',
	            transform: function(response) {
	                return jQuery.map(response, function (obj) {
	                    return {
	                    	id: obj.id,
		                    name: obj.name,
		                    created: obj.created,
		                    //DAL2: //email: obj.meta.email
		                    email: obj.email
	                    }
	                });
	            }

			}
			//http://stackoverflow.com/questions/24560108/typeahead-v0-10-2-bloodhound-working-with-nested-json-objects
			/*filter: function (response) {
	            return jQuery.map(response, function (obj) {
	                return {
	                    name: obj.name,
	                    created: obj.created,
	                };
	            });
	        }*/
		});

		// for each typeahead init
		jQuery('.zbstypeaheadwrap .zbstypeahead').each(function(){

			//debug console.log("enabling on ",jQuery(this));

			jQuery(this).typeahead({
				hint: true,
				highlight: true,
				minLength: 1
			}, {
				name: 'customers',
				source: customers,
				//displayKey: 'name'
        		display: 'name'
			});


			// BRUTALLY setup all for autocomplete to die :)
			setTimeout(function(){
				var utc = new Date().getTime();
				var k = jQuery(this).attr('data-autokey'); if (typeof k == "undefined") var k = '-typeahead';
				var ns = 'zbsco-' + utc + '-' + k;
				jQuery('.zbstypeahead').attr('autocomplete',ns).attr('name',ns);
			},0);
			jQuery(this).bind('typeahead:open', function(ev, suggestion) {
			  // force all typeaheads to be NOT AUTOCOMPLETE
			  //jQuery('.zbstypeaheadco').attr('autocomplete','zbscontact-1518172413-addr1').attr('name','3f3g3g');
				var utc = new Date().getTime();
				var k = jQuery(this).attr('data-autokey'); if (typeof k == "undefined") var k = '-typeahead';
				var ns = 'zbsco-' + utc + '-' + k;
				jQuery('.zbstypeahead').attr('autocomplete',ns).attr('name',ns);
			}); 

			// bind any callbacks
			var potentalOpenCallback = jQuery(this).attr('data-zbsopencallback');

			if (typeof potentalOpenCallback == "string" && potentalOpenCallback.length > 0){
				jQuery(this).bind('typeahead:select', function(ev, suggestion) {
				  
				  var localisedCallback = potentalOpenCallback;
				  //Debug console.log('Selection: ',suggestion);
				  if (typeof window[localisedCallback] == "function") window[localisedCallback](suggestion);

				}); 
			}

			// this is a "change" callback which can be used as well as / instead of previous "select" callback
			// e.g. this'll fire if emptied :)
			var potentalChangeCallback = jQuery(this).attr('data-zbschangecallback');
			if (typeof potentalChangeCallback == "string" && potentalChangeCallback.length > 0){
				jQuery(this).bind('typeahead:change', function(ev, val) {
				  
				  var localisedCallback = potentalChangeCallback;
				  //Debug console.log('Selection: ',suggestion);
				  if (typeof window[localisedCallback] == "function") window[localisedCallback](val);

				}); 
			}

			/* other events https://github.com/twitter/typeahead.js/blob/master/doc/jquery_typeahead.md
				jQuery(this).bind('typeahead:open', function(ev, suggestion) {
				  console.log('Open: ', ev);
				});
				jQuery('.zbstypeahead').bind('typeahead:select', function(ev, suggestion) {
				  console.log('Selection: ' + suggestion);
				}); 
			*/

		});

	}



}
function zbscrm_JS_Bind_Typeaheads_Companies(){

	// Typeaheads:

		// if any?
		if (jQuery('.zbstypeaheadwrap .zbstypeaheadco').length > 0){

			// one prefetch bloodhound for all instances		
			var companies = new Bloodhound({
				//datumTokenizer: Bloodhound.tokenizers.whitespace,
				datumTokenizer: function (datum) {
							        return Bloodhound.tokenizers.whitespace(datum.name);
							    },
				queryTokenizer: Bloodhound.tokenizers.whitespace,
	    		identify: function(obj) { return obj.id; },
				// https://github.com/twitter/typeahead.js/blob/master/doc/bloodhound.md#prefetch
				prefetch: //'../data/countries.json'
				{	// prefatch options
					url: window.zbscrmBHURLCompanies,
					ttl: 300000, // 300000 = 5 mins, 86400000 = 1 day (default) - ms	
	    			//cache: false,		
		            transform: function(response) {
		                return jQuery.map(response, function (obj) {
		                    var x = {
		                    	id: obj.id,
			                    name: obj.name,
			                    created: obj.created,
			                    //won't be present from 2.95 email: obj.meta.email
			                    email: ''
		                    }
		                    if (typeof obj.meta != "undefined" && typeof obj.meta.email != "undefined") x.email = obj.meta.email;

		                    return x;
		                });
		            }
				},
				remote: {
					// this checks when users type, via ajax search ... useful addition to (cached) prefetch
					url: window.zbscrmBHURLCompanies + '&s=%QUERY',
	          		wildcard: '%QUERY',	
		            transform: function(response) {
		                return jQuery.map(response, function (obj) {
		                    var x = {
		                    	id: obj.id,
			                    name: obj.name,
			                    created: obj.created,
			                    //won't be present from 2.95 email: obj.meta.email
			                    email: ''
		                    }
		                    if (typeof obj.meta != "undefined" && typeof obj.meta.email != "undefined") x.email = obj.meta.email;

		                    return x;
		                });
		            }

				}
				//http://stackoverflow.com/questions/24560108/typeahead-v0-10-2-bloodhound-working-with-nested-json-objects
				/*filter: function (response) {
		            return jQuery.map(response, function (obj) {
		                return {
		                    name: obj.name,
		                    created: obj.created,
		                };
		            });
		        }*/
			});

			// for each typeahead init
			jQuery('.zbstypeaheadwrap .zbstypeaheadco').each(function(){

				//debug console.log("enabling on ",jQuery(this));

				jQuery(this).typeahead({
					hint: true,
					highlight: true,
					minLength: 1
				}, {
					name: 'companies',
					source: companies,
					//displayKey: 'name'
	        		display: 'name'
				});
				 
				/* 

					#AUTOCOMPLETE + THIS works: https://stackoverflow.com/questions/34585783/disable-browsers-autofill-when-using-typeahead-js

				*/

				// BRUTALLY setup all for autocomplete to die :)
				setTimeout(function(){
					var utc = new Date().getTime();
					var k = jQuery(this).attr('data-autokey'); if (typeof k == "undefined") var k = '-typeahead';
					var ns = 'zbsco-' + utc + '-' + k;
					jQuery('.zbstypeaheadco').attr('autocomplete',ns).attr('name',ns);
				},0);
				jQuery(this).bind('typeahead:open', function(ev, suggestion) {
				  // force all typeaheads to be NOT AUTOCOMPLETE
				  //jQuery('.zbstypeaheadco').attr('autocomplete','zbscontact-1518172413-addr1').attr('name','3f3g3g');
					var utc = new Date().getTime();
					var k = jQuery(this).attr('data-autokey'); if (typeof k == "undefined") var k = '-typeahead';
					var ns = 'zbsco-' + utc + '-' + k;
					jQuery('.zbstypeaheadco').attr('autocomplete',ns).attr('name',ns);
				}); 

				// bind any callbacks

				// typeahead selected callback :)
				var potentalOpenCallback = jQuery(this).attr('data-zbsopencallback');

				if (typeof potentalOpenCallback == "string" && potentalOpenCallback.length > 0){
					jQuery(this).bind('typeahead:select', function(ev, suggestion) {
					  
					  var localisedCallback = potentalOpenCallback;
					  //Debug console.log('Selection: ',suggestion);
					  if (typeof window[localisedCallback] == "function") window[localisedCallback](suggestion);

					}); 
				}

				// this is a "change" callback which can be used as well as / instead of previous "select" callback
				// e.g. this'll fire if emptied :)
				var potentalChangeCallback = jQuery(this).attr('data-zbschangecallback');
				if (typeof potentalChangeCallback == "string" && potentalChangeCallback.length > 0){
					jQuery(this).bind('typeahead:change', function(ev, val) {
					  
					  var localisedCallback = potentalChangeCallback;
					  //Debug console.log('Selection: ',suggestion);
					  if (typeof window[localisedCallback] == "function") window[localisedCallback](val);

					}); 
				}

				/* other events https://github.com/twitter/typeahead.js/blob/master/doc/jquery_typeahead.md
					jQuery(this).bind('typeahead:open', function(ev, suggestion) {
					  console.log('Open: ', ev);
					});
					jQuery('.zbstypeahead').bind('typeahead:select', function(ev, suggestion) {
					  console.log('Selection: ' + suggestion);
					}); 
				*/

			});

		}


	// selects (for < 50)

	// for each typeahead init
	// NOT COMPLETED. - could not get binds to reliably fire.
	// #TODOCOLIST
	/* 
	jQuery('.zbs-company-select .zbs-company-select-input').each(function(){

		// typeahead selected callback :)
		var potentalOpenCallback = jQuery(this).attr('data-zbsopencallback');

		if (typeof potentalOpenCallback == "string" && potentalOpenCallback.length > 0){
			console.log('bindin select ' + potentalOpenCallback);
			jQuery(this).bind('select', function(ev) {
			  
			  var localisedCallback = potentalOpenCallback;
			  //Debug 
			  console.log('Selection: ',[ev]);
			  if (typeof window[localisedCallback] == "function") window[localisedCallback](suggestion);

			}); 
		}

		// this is a "change" callback which can be used as well as / instead of previous "select" callback
		// e.g. this'll fire if emptied :)
		var potentalChangeCallback = jQuery(this).attr('data-zbschangecallback');
		if (typeof potentalChangeCallback == "string" && potentalChangeCallback.length > 0){
			console.log('bindin change ' + potentalOpenCallback);
			jQuery(this).bind('change', function(ev) {
			  
			  var localisedCallback = potentalChangeCallback;
			  //Debug console.log('Selection: ',suggestion);
			  console.log('Selection: ',[ev]);
			  if (typeof window[localisedCallback] == "function") window[localisedCallback](val);

			}); 
		}

	}); */

}




/*
	#==================================================
	# Global UI funcs
	#==================================================
*/
function zbscrm_js_uiSpinnerBlocker(spinnerHTML){

	// def
	var anyContent = '<i class="fa fa-circle-o-notch fa-spin" aria-hidden="true"></i>';
	if (typeof spinnerHTML != "undefined") anyContent = spinnerHTML;

	return '<div class="zbsSpinnerBlocker"><div class="zbsSpinnerBG"></div><div class="zbsSpinnerIco">' + anyContent + '</div></div>';

}







/*
	#==================================================
	# / Global UI funcs
	#==================================================
*/

/*
	#==================================================
	# Global AJAX FUNCS
	#==================================================
*/

var zbscrm_custcache_invoices = {};
function zbscrm_js_getCustInvs(cID,cb,errcb){


	if (typeof cID != "undefined" && cID > 0){

		// see if in cache (rough cache)
	
		if (typeof window.zbscrm_custcache_invoices[cID] != "undefined") {

			// call back with that!
	        if (typeof cb == "function") cb(window.zbscrm_custcache_invoices[cID]);

	        return window.zbscrm_custcache_invoices[cID];

		}
		

		// ... otherwise retrieve!

		

		// postbag!
		var data = {
			'action': 'getinvs',
			'sec': window.zbs_root.zbsnonce,
			'cid': cID
		};

		// Send 
		jQuery.ajax({
		      type: "POST",
		      url: ajaxurl, // admin side is just ajaxurl not wptbpAJAX.ajaxurl,
		      "data": data,
		      dataType: 'json',
		      timeout: 20000,
		      success: function(response) {

		      	// set cache
		      	window.zbscrm_custcache_invoices[cID] = response;

	        	// callback
	        	if (typeof cb == "function") cb(response);

	        	return response;

		      },
		      error: function(response){ 

		      	//console.error("RESPONSE",response);

	        	// callback
	        	if (typeof errcb == "function") errcb(response);


		      }

		});

	} else {

		if (typeof errcb == "function") errcb({fail:1});

	}

	return false;

}

/*
	#==================================================
	# / Global AJAX FUNCS
	#==================================================
*/














/*
	#==================================================
	# GENERIC USEFUL FUNCS
	# Note: These may be duped in ZeroBSCRM.public.global.js or ZeroBSCRM.admin.global.js
	#==================================================
*/

// hitting js clone issues, using this from https://stackoverflow.com/questions/29050004/modifying-a-copy-of-a-javascript-object-is-causing-the-original-object-to-change
function zbscrm_JS_clone(obj) {
    if (null == obj || "object" != typeof obj) return obj;
    var copy = obj.constructor();
    for (var attr in obj) {
        if (obj.hasOwnProperty(attr)) copy[attr] = obj[attr];
    }
    return copy;
}

/* mikes, taken from leadform.js 1.1.19, not accurate?
function zbscrm_JS_isEmail(email) {
  var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  return regex.test(email);
}
*/
//http://stackoverflow.com/questions/46155/validate-email-address-in-javascript
function zbscrm_JS_validateEmail(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

//http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
function zbscrmjs_prettifyLongInts(x) {

	// catch accidental null passes
	if (x == null) return 0;

    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}


// w script to change "Note: Whatever X" into "note__whatever_x"
function zbscrmjs_permify(str){

	var str2 = zbscrmjs_replaceAll(str,' ','_');
	str2 = zbscrmjs_replaceAll(str2,':','_');
	return str2.toLowerCase();

	//return str.replace(' ','_').replace(':','_').toLowerCase();

}

function zbscrmjs_replaceAll(str, find, replace) {
    return str.replace(new RegExp(find, 'g'), replace);
}

//http://stackoverflow.com/questions/7467840/nl2br-equivalent-in-javascript
function zbscrmjs_nl2br(str, is_xhtml) {
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}

// brutal replace of <br />
function zbscrmjs_reversenl2br(str,incLinebreaks){

	var repWith = '';
	if (typeof incLinebreaks != "undefined") repWith = "\r\n";

	//return str.replace(/<br />/g,repWith);
	return str.split("<br />").join(repWith);

}


function ucwords(str) {
	//  discuss at: http://phpjs.org/functions/ucwords/
	// original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
	// improved by: Waldo Malqui Silva
	// improved by: Robin
	// improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// bugfixed by: Onno Marsman
	//    input by: James (http://www.james-bell.co.uk/)
	//   example 1: ucwords('kevin van  zonneveld');
	//   returns 1: 'Kevin Van  Zonneveld'
	//   example 2: ucwords('HELLO WORLD');
	//   returns 2: 'HELLO WORLD'

	return (str + '')
	.replace(/^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function($1) {
	return $1.toUpperCase();
	});
}



//http://stackoverflow.com/questions/154059/how-do-you-check-for-an-empty-string-in-javascript
function empty(str) {
    return (!str || 0 === str.length);
}

// returns a fully formed click 2 call link
function zeroBSCRMJS_telLinkFromNo(telno,internalHTML,extraClasses){

	return '<a href="tel:' + telno + '" class="' + extraClasses + '">' + internalHTML + '</a>';

}

// returns a click 2 call url
function zeroBSCRMJS_telURLFromNo(telno){

	if (typeof window.zbsClick2CallType != "undefined"){

		if (window.zbsClick2CallType == 2) return 'callto:' + telno;

	}

	return 'tel:' + telno;

}

// for annoying workaround in listview 2.0
function zeroBSCRMJS_isArray(obj){
    return !!obj && obj.constructor === Array;
}

// http://phpjs.org/functions/ucwords:569
function zeroBSCRMJS_ucwords(str) {
    return (str + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
        return $1.toUpperCase();
    });
}

// built to dupe the php ver zeroBSCRM_formatCurrency
function zeroBSCRMJS_formatCurrency(c){

	//why are we managing two functions. The below needs modifying for settings
	//when we can just format the number in PHP in the AJAX response and spit it out..
	
	//return c;

	// WH: Sometimes you need to manage things on the fly? E.g. a user types in a number and you want to show it somewhere?
	// adapted yours...
	// For now just made this hodge-podge use the locale set in wp + the zbs settings
	// ... we need to move locale to a zbs setting to be thorough here, and also add datetime settings
	var localeStr = 'en-US'; if (typeof window.zbs_root != "undefined" && typeof window.zbs_root.locale != "undefined") localeStr = window.zbs_root.locale;


	// got locale?
	if (localeStr != ''){

		// answer low down here: https://stackoverflow.com/questions/149055/how-can-i-format-numbers-as-dollars-currency-string-in-javascript
		// https://stackoverflow.com/questions/149055/how-can-i-format-numbers-as-dollars-currency-string-in-javascript/16233919#16233919

		// this is to get over WP en_US not en-US
		localeStr = localeStr.replace(/_/,'-');

		// Create our number formatter.
		var formatter = new Intl.NumberFormat(localeStr, {
		  style: 'currency',
		  currency: window.zbs_root.currencyOptions.currencyStr,
		  minimumFractionDigits: window.zbs_root.currencyOptions.noOfDecimals
		});

		// Debug console.log('(1) C:' + c + ' becomes ' + formatter.format(c));

		return formatter.format(c); /* $2,500.00 */

	} else {

		// SHOULD NEVER RUN
		
		// fallback to curr + zeroBSCRMJS_number_format_i18n
		return window.zbs_root.currencyOptions.symbol + zeroBSCRMJS_number_format_i18n(c);

	}


}

// duped from wp php
// https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/functions.php#L215
function zeroBSCRMJS_number_format_i18n( number, decimals) {

	if (typeof decimals == "undefined") var decimals = 0;

	if (typeof window.zbswplocale != "undefined"){

		return zeroBSCRMJS_number_format(number,decimals,window.zbswplocale.decimal_point,window.zbswplocale.thousands_sep);

	} else {

		return zeroBSCRMJS_number_format(number,decimals);
	}

}

// https://stackoverflow.com/questions/12820312/equivalent-to-php-function-number-format-in-jquery-javascript
function zeroBSCRMJS_number_format (number, decimals, dec_point, thousands_sep) {
    // Strip all characters but numerical ones.
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

// https://stackoverflow.com/questions/14346414/how-do-you-do-html-encode-using-javascript
function zeroBSCRMJS_htmlEncode(value){
  //create a in-memory div, set it's inner text(which jQuery automatically encodes)
  //then grab the encoded contents back out.  The div never exists on the page.
  return jQuery('<div/>').text(value).html();
}

function zeroBSCRMJS_htmlDecode(value){
  return jQuery('<div/>').html(value).text();
}
function zeroBSCRMJS_newWindow(url,windowName,height,width) {
	if (typeof height == "undefined") var height = 600;
	if (typeof width == "undefined") var width = 600;
   newwindow=window.open(url,windowName,'height=' + height + ',width=' + width);
   if (window.focus) {newwindow.focus()}
   return false;
}
//https://stackoverflow.com/questions/4068373/center-a-popup-window-on-screen
function zeroBSCRMJS_newWindowCenter(url, title, w, h) {
    // Fixes dual-screen position                         Most browsers      Firefox
    var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
    var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

    var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
    var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

    var left = ((width / 2) - (w / 2)) + dualScreenLeft;
    var top = ((height / 2) - (h / 2)) + dualScreenTop;
    var newWindow = window.open(url, title, 'scrollbars=yes, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

    // Puts focus on the newWindow
    if (window && window.focus) {
        newWindow.focus();
    }

}
//https://plainjs.com/javascript/utilities/merge-two-javascript-objects-19/
function zeroBSCRMJS_extend(obj, src) {
    for (var key in src) {
        if (src.hasOwnProperty(key)) obj[key] = src[key];
    }
    return obj;
}


// adapted from https://stackoverflow.com/questions/1500260/detect-urls-in-text-with-javascript
function zeroBSCRMJS_retrieveURLS(str){
	var urlRegex = /(((https?:\/\/)|(www\.))[^\s]+)/g;
	var match = urlRegex.exec(str);

	return match;

}
// https://stackoverflow.com/questions/14440444/extract-all-email-addresses-from-bulk-text-using-jquery
function zeroBSCRMJS_retrieveEmails(str){
	return str.match(/([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9._-]+)/gi);
}

// https://stackoverflow.com/questions/542938/how-do-i-get-the-number-of-days-between-two-dates-in-javascript
function parseDate(str) {
    var mdy = str.split('/');
    return new Date(mdy[2], mdy[0]-1, mdy[1]);
}

function daydiff(first, second){
    return Math.round((second-first)/(1000*60*60*24));
}

// Select func https://stackoverflow.com/questions/4990175/array-select-in-javascript
Array.prototype.zbsselect = function(closure){
    for(var n = 0; n < this.length; n++) {
        if(closure(this[n])){
            return this[n];
        }
    }

    return null;
};



// semantic html helper, returns percentage bar:
function zbsJS_semanticPercBar(perc,extraClasses,label){

	if (typeof extraClasses != '') 
		extraClasses = ' ' + extraClasses;
	else
		var extraClasses = '';

	var ret = '<div class="ui progress' + extraClasses + '"><div class="bar"';
	if (typeof perc != "undefined") ret += ' style="width:' + perc + '%"';
	ret += '><div class="progress">';
	if (typeof perc != "undefined") ret += perc + '%';
	ret += '</div></div>';
	if (typeof label != "undefined") ret += '<div class="label">' + label + '</div>';
	ret += '</div>';

	return ret;
}

// used for uts func (not sure where this went!? (WH 28/5/18))
if (!Date.now) {
    Date.now = function() { return new Date().getTime(); }
}
function zbsJS_uts(){
	return Math.floor(Date.now() / 1000);
}

// used in forms ages ago, ported here + cleaned name from zbs_htmlEncode
function zeroBSCRMJS_htmlEncode(value){
  //create a in-memory div, set it's inner text(which jQuery automatically encodes)
  //then grab the encoded contents back out.  The div never exists on the page.
  return jQuery('<div/>').text(value).html();
}
/* super simple switch
HIDES: .zbs-generic-loading
SHOWS: .zbs-generic-loaded
*/
function zeroBSCRMJS_genericLoaded(){

	jQuery('.zbs-generic-loading').hide();
	jQuery('.zbs-generic-loaded').show();

}

// lazypost https://stackoverflow.com/questions/1708540/jquery-post-possible-to-do-a-full-page-post-request
function zeroBSCRMJS_genericPostData(actionUrl, method, data) {
    var mapForm = jQuery('<form id="mapform" action="' + actionUrl + '" method="' + method.toLowerCase() + '"></form>');
    for (var key in data) {
        if (data.hasOwnProperty(key)) {
            mapForm.append('<input type="hidden" name="' + key + '" id="' + key + '" value="' + data[key] + '" />');
        }
    }
    jQuery('body').append(mapForm);
    mapForm.submit();
}

/* // ---------------------- 

	Screen Options 
	
 // ---------------------- */

 function zeroBSCRMJS_bindScreenOptions(){

 	jQuery('.zbs-screenoptions-tablecolumns .ui.checkbox').click(function(){
 		
 		// save
 		zeroBSCRMJS_saveScreenOptions();

 	});


    // show hide screen opts
    jQuery('#zbs-screen-options-handle').unbind('click').click(function(){

        if (jQuery('#zbs-screen-options').hasClass('zbs-closed')){

            // open
            jQuery('#zbs-screen-options').removeClass('zbs-closed');


        } else {

            // close
            jQuery('#zbs-screen-options').addClass('zbs-closed');

            // if 'arrange' mode still on, turn off
            if (typeof zeroBSCRMJS_metaboxManagerSwitchMode == 'function') zeroBSCRMJS_metaboxManagerSwitchMode('off');
            
	 		
	 		// save
	 		zeroBSCRMJS_saveScreenOptions();


        }

    });
 }

// takes global js vars + saves against user + page via ajax
var zbscrmjs_screenoptblock = false;
function zbsJS_updateScreenOptions(successcb,errcb){

	// blocker
	window.zbscrmjs_screenoptblock = true;

	var data = {
		'action': 'save_zbs_screen_options',
		'sec': window.zbscrmjs_secToken,
		'screenopts': window.zbsScreenOptions,
		'pagekey': window.zbsPageKey
	};

	jQuery.ajax({
	      type: "POST",
	      url: ajaxurl,
	      "data": data,
	      dataType: 'json',
	      timeout: 20000,
	      success: function(response) {
	      	// blocker
	      	window.zbscrmjs_screenoptblock = false;

	      	if (typeof successcb == 'function') successcb(response);

	      },
	      error: function(response){ 
	      	// blocker
	      	window.zbscrmjs_screenoptblock = false;

	      	if (typeof errcb == 'function') errcb(response);
	      }
	}); 

}



// This was adapted from zeroBSCRMJS_saveScreenOptionsMetaboxes in metabox manager
// generically saves any table column settings (from checkboxes -> user screen options)
// this is fired on checking a box in the screenopts div (see bindScreenOpts)
var zbsjsScreenOptsBlock = false;
function zeroBSCRMJS_saveScreenOptions(cb){

    if (!window.zbsjsScreenOptsBlock){

        // blocker
        window.zbsjsScreenOptsBlock = true;

        // just check - empty defaults
	    if (typeof window.zbsScreenOptions != "undefined" || window.zbsScreenOptions == false) window.zbsScreenOptions = { mb_normal: {}, mb_side: {}, mb_hidden: [], mb_mini:[], pageoptions:[], tablecolumns:{} }; 

        // update global screen options (safe to run even on non tablecolumns pages)
        zeroBSCRMJS_buildScreenOptionsTableColumns();

        // update any generics (where they have controls present on page)
        zeroBSCRMJS_buildScreenOptionsGenerics();

        // save
        zbsJS_updateScreenOptions(function(r){

            // No debug for now console.log('Saved!',r);

            // blocker
            window.zbsjsScreenOptsBlock = false;

            // callback
            if (typeof cb == "function") cb();

        },function(r){

            // No debug for now console.error('Failed to save!',r);

            // blocker
            window.zbsjsScreenOptsBlock = false;

            // callback
            if (typeof cb == "function") cb();

        });

    } 

}

// this builds tablecol screenoptions from actual screen state :)
function zeroBSCRMJS_buildScreenOptionsTableColumns(){
    
      // ====== Table columns:

            var tabIdx = 1;

            var tcAreas = ['transactions'];

            // for each area
            jQuery.each(tcAreas,function(tcAreasIndx,tcArea){

            	var obj = [];

                  // 'normal' metaboxes
                  jQuery('#zbs-tablecolumns-' + tcArea + ' .zbs-tablecolumn-checkbox').each(function(ind,ele){

                    // is tabbed? (ignore, tabbed dealt with below for simplicity)
                    if (jQuery(this).checkbox('is checked')){

                        // add to list
                        obj.push(jQuery(ele).attr('data-colkey'));


                    }

                  });

                  // override whatevers here
                  if (typeof window.zbsScreenOptions.tablecolumns == "undefined") window.zbsScreenOptions.tablecolumns = {};
                  window.zbsScreenOptions.tablecolumns[tcArea] = obj;

            });

    return window.zbsScreenOptions;

}
// this grabs generic screenOptions into the obj if they're set
function zeroBSCRMJS_buildScreenOptionsGenerics(){
    
      // ====== perpage
      if (jQuery('#zbs-screenoptions-records-per-page').length > 0){

      		var perPage = parseInt(jQuery('#zbs-screenoptions-records-per-page').val());
      		if (perPage < 1) perPage = 20;

      		// set it
      		if (perPage > 0) window.zbsScreenOptions.perpage = perPage;

      }
         

    return window.zbsScreenOptions;

}

/* // ---------------------- 

	/ Screen Options 
	
 // ---------------------- */


/* // ---------------------- 

		AJAX REST DAL
	
 // ---------------------- */
var zbsAJAXRestRetrieve = false;
function zeroBSCRMJS_rest_retrieveCompany(companyID,callback,cbfail){

	// only works if window.zbscrmBHURLCompanies. + ‘&id=’ + companyID; defined

	if (typeof companyID != "undefined" && typeof window.zbscrmBHURLCompanies != "undefined"){

		// block
		window.zbsAJAXRestRetrieve = true;

		// url
		var restURL = window.zbscrmBHURLCompanies + '&id=' + companyID;
		jQuery.ajax({
			type: "GET",
			url: restURL,
			timeout: 10000,
			success: function(response) {

				//console.log("response",response);

				// unblock
				window.zbsAJAXRestRetrieve = false;

				// any callback
				if (typeof callback == "function") callback(response);

					return true;

			},
			error: function(response){

				//console.log('err',response);

				// unblock
				window.zbsAJAXRestRetrieve = false;

				// any callback
				if (typeof cbfail == "function") cbfail(response);

					return false;

		 	}

		});
	}

}

/* // ---------------------- 

	/ AJAX REST DAL
	
 // ---------------------- */

/* ========================================================================================== 
    Global Object funcs (e.g. contact cards)
========================================================================================== */
// lang helper:
// passes language from window.x (js set in listview php)
function zeroBSCRMJS_globViewLang(key,fallback){

    if (typeof fallback == 'undefined') var fallback = '';

    if (typeof window.zbs_root.lang != "undefined" && typeof window.zbs_root.lang[key] != "undefined") return window.zbs_root.lang[key];

    return fallback;
}

function zeroBSCRMJS_bindGlobalObjFuncs(){

	// needs to fire post page init
	setTimeout(function(){

		// debug console.log('binding global obj funcs');

		// contacts
		zeroBSCRMJS_bindGlobalContactFuncs();

	},500);

}

function zeroBSCRMJS_bindGlobalContactFuncs(){
	
	// debug console.log('binding global contact obj funcs');

	// send statement modal (currently only used on view + edit contact pages)
	jQuery('#zbs-contact-action-sendstatement').unbind('click').click(function(){

		/* 
		1. Opens a modal window with space for email address “to send statement to”
		(defaults to contact email address), as well as cancel + send buttons
		2. Cancel = closes modal
		3. Send = sends pdf statement along with templated email to given email address

		*/

		var emailToSendTo = ''; var cID = '';
		if (typeof jQuery(this).attr('data-sendto') != "undefined") emailToSendTo = jQuery(this).attr('data-sendto');
		if (typeof jQuery(this).attr('data-cid') != "undefined") cID = parseInt(jQuery(this).attr('data-cid'));


            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: '<i class="envelope outline icon"></i> ' + zeroBSCRMJS_globViewLang('sendstatement'),
              html: '<div style="font-size: 1.2em;padding: 0.3em;">' + zeroBSCRMJS_globViewLang('sendstatementaddr') + '<br /><div class="ui input"><input type="text" name="zbs-send-pdf-statement-to-email" id="zbs-send-pdf-statement-to-email" value="' + emailToSendTo + '" placeholder="' + zeroBSCRMJS_globViewLang('enteremail')+ '" /></div></div>',
              //text: "Are you sure you want to delete these?",
              type: '',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_globViewLang('send'),
              cancelButtonText: zeroBSCRMJS_globViewLang('cancel'),
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

	            	// localise
	            	var lCID = cID;
	            	var lEmailToSendTo = emailToSendTo;

	      			// & save state
					var data = {
						'action': 'zbs_invoice_send_statement',
						'sec': window.zbs_root.zbsnonce,
						'cid': lCID,
						'em': lEmailToSendTo,
					};
					jQuery.ajax({
					      type: "POST",
					      url: ajaxurl,
					      "data": data,
					      dataType: 'json',
					      timeout: 20000,
					      success: function(response) {

					      	// blocker
					      	window.zbscrmjs_adminMenuBlocker = false;
		                    // success ? SWAL?
		                      swal(
		                        zeroBSCRMJS_globViewLang('sent'),
		                        zeroBSCRMJS_globViewLang('statementsent'),
		                        'success'
		                      );

					      },
					      error: function(response){ 

					      	// blocker
					      	window.zbscrmjs_adminMenuBlocker = false;
		                    // fail ? SWAL?
		                    swal(
		                        zeroBSCRMJS_globViewLang('notsent'),
		                        zeroBSCRMJS_globViewLang('statementnotsent'),
		                        'warning'
		                    );

					      }
					});  

				}	
            });


	});


}

// uses zbs_root to build a view link for obj type (use globally)
function zeroBSCRMJS_obj_viewLink(objTypeStr,objID){

	if (typeof objTypeStr != "undefined" && objTypeStr != '' && typeof objID != "undefined" && objID != ''){
		if (typeof window.zbs_root.links != "undefined" && typeof window.zbs_root.links.generic_view != "undefined"){

			// replace with obj type
			return window.zbs_root.links.generic_view.replace('_TYPE_',objTypeStr) + objID;

		}

	} // / if not obj type + id

	return '#pagenotfound';

}

// uses zbs_root to build a edit link for obj type (use globally)
function zeroBSCRMJS_obj_editLink(objTypeStr,objID){

	if (typeof objTypeStr != "undefined" && objTypeStr != '' && typeof objID != "undefined" && objID != ''){
		if (typeof window.zbs_root.links != "undefined" && typeof window.zbs_root.links.generic_edit != "undefined"){

			// replace with obj type
			return window.zbs_root.links.generic_edit.replace('_TYPE_',objTypeStr) + objID;

		}

	} // / if not obj type + id

	return '#pagenotfound';

}

/* ========================================================================================== 
    / Global Object funcs (e.g. contact cards)
========================================================================================== */


/* ========================================================================================== 
    Global Dismiss funcs (e.g. notifications)
========================================================================================== */
function zeroBSCRMJS_bindGlobalDismiss(){

	jQuery('.zbs-dismiss').unbind('click').click(function(ind,ele){

		// retrieve attr
		var dismissKey = jQuery(this).attr('data-dismiss-key');
		var dismissElementID = jQuery(this).attr('data-dismiss-element-id');

		if (dismissKey !== ''){

			// ajax set transient - see also zbscrm_JS_bindCloseLogs()
			if (!window.zbscrmjs_closeLogBlocker){

					// blocker
					window.zbscrmjs_closeLogBlocker = true;

					// postbag!
					var data = {
						'action': 'logclose',
						'sec': window.zbs_root.zbsnonce,
						'closing': dismissKey
					};

					// Send 
					jQuery.ajax({
					      type: "POST",
					      url: ajaxurl, // admin side is just ajaxurl not wptbpAJAX.ajaxurl,
					      "data": data,
					      dataType: 'json',
					      timeout: 20000,
					      success: function(response) {

					      	// localise
					      	var thisEle = dismissElementID;

					      	// remove it!
					      	jQuery('#' + thisEle).slideUp();

					      	// blocker
					      	window.zbscrmjs_closeLogBlocker = false;

					      },
					      error: function(response){ 

					      	// localise
					      	var thisEle = dismissElementID;

					      	// remove it!
					      	jQuery('#' + thisEle).slideUp();

					      	// blocker
					      	window.zbscrmjs_closeLogBlocker = false;


					      }

					});

				}
		}

	});


}

// this was the original func, though moved to zbs-dismiss for better nomencleture
var zbscrmjs_closeLogBlocker = false;
function zbscrm_JS_bindCloseLogs(){

	jQuery('.zbsCloseThisAndLog').click(function(){

		// retrieve key
		var thisCloseLog = jQuery(this).attr('data-closelog');

		if (thisCloseLog !== '' && !window.zbscrmjs_closeLogBlocker){

			// localise
			var closeDialog = this;

				// blocker
				window.zbscrmjs_closeLogBlocker = true;

				// postbag!
				var data = {
					'action': 'logclose',
					'sec': window.zbs_root.zbsnonce,
					'closing': thisCloseLog
				};

				// Send 
				jQuery.ajax({
				      type: "POST",
				      url: ajaxurl, // admin side is just ajaxurl not wptbpAJAX.ajaxurl,
				      "data": data,
				      dataType: 'json',
				      timeout: 20000,
				      success: function(response) {

				      	// localise
				      	var thisEle = closeDialog;

				      	// remove it!
				      	jQuery(thisEle).parent().slideUp();

				      	// blocker
				      	window.zbscrmjs_closeLogBlocker = false;

				      },
				      error: function(response){ 

				      	// localise
				      	var thisEle = closeDialog;

				      	// remove it!
				      	jQuery(thisEle).parent().slideUp();

				      	// blocker
				      	window.zbscrmjs_closeLogBlocker = false;


				      }

				});

			}


	});

}
/* ========================================================================================== 
    / Global Dismiss funcs (e.g. notifications)
========================================================================================== */

/* ========================================================================================== 
    Calypso related functions
========================================================================================== */
function zbscrm_JS_isCalypso(){

	return jQuery('#calypso-sidebar-header').length;
}
/* ========================================================================================== 
    / Calypso related functions
========================================================================================== */