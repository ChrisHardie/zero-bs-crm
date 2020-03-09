/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.2
 *
 * Copyright 2017 ZeroBSCRM.com
 *
 * Date: 11/08/2017
 */
var zbscrmjs_sidebarState = 1; var zbscrmjs_sidebarChangeBlocker = false;

// catch nulls passed here
if (typeof window.zbsListViewCount == "undefined" || window.zbsListViewCount === null) window.zbsListViewCount = 0;

 jQuery(document).ready(function(){

    // init if settings there (not on non-listview pages)
    if (typeof window.zbsListViewSettings != "undefined") zeroBSCRMJS_initListView();

});

// Initiliase the list view.
function zeroBSCRMJS_initListView(){
    
            // custom screen options (in column manager ui)
            jQuery('#zbs-screenoptions-records-per-page').unbind('change').change(function(){

                // save screen opts
                zeroBSCRMJS_saveScreenOptions(function(){

                    // update list view

                        // mark data needs refresh:
                        window.zbsListViewParams.retrieved = false;

                        // redraw table
                        zeroBSCRMJS_drawListView();

                });

            });

            // save + close button at bottom of colmanager/screenopts
            jQuery('#zbs-columnmanager-bottomsave').unbind('click').click(function(){

                // just clcking basically means opts saVED (AS SAVED ON CHANGE)

                    // close this    
                    // (lazy sim click ;) )
                    jQuery('#zbs-open-column-manager').click(); 

            });

            // open/shut column manager
            jQuery('#zbs-open-column-manager').unbind('click').click(function(){

                //jQuery('#zbs-list-col-editor').toggle();
                if (jQuery('#zbs-list-col-editor').is(":visible")){

                    // hide
                    jQuery(this).addClass('blue').removeClass('teal');
                    jQuery('#zbs-list-col-editor').hide();

                } else {

                    // show
                    jQuery(this).removeClass('blue').addClass('teal');
                    jQuery('#zbs-list-col-editor').show();

                }

                    
            });
            // open/shut sidebar
            jQuery('#zbs-toggle-sidebar').click(function(){

                if (!window.zbscrmjs_sidebarChangeBlocker){

                    window.zbscrmjs_sidebarChangeBlocker = true;

                    // get state
                    if (window.zbscrmjs_sidebarState == 1){

                        // close

                            // hide sidebar
                            jQuery('#zbs-list-sidebar-wrap').hide();

                            // shrink left col -> sixteen wide column
                            jQuery('#zbs-list-table-wrap').removeClass().addClass('sixteen wide column');
                        
                            // ico
                            jQuery(this).html('<i class="toggle on icon"></i>');

                            // flip switch
                            window.zbscrmjs_sidebarState = -1;


                    } else {

                        // open

                            // show sidebar
                            jQuery('#zbs-list-sidebar-wrap').show();

                            // shrink left col -> twelve wide column
                            jQuery('#zbs-list-table-wrap').removeClass().addClass('twelve wide column');
                        
                            // ico
                            jQuery(this).html('<i class="toggle off icon"></i>');

                            // flip switch
                            window.zbscrmjs_sidebarState = 1;

                    }

                    setTimeout(function(){ window.zbscrmjs_sidebarChangeBlocker = false; },0);

                }

            });

            // Using Filters?
            if (typeof window.zbsListViewSettings != "undefined" && typeof window.zbsListViewSettings.filters != "undefined" && window.zbsListViewSettings.filters){

                // open/shut filter button manager
                jQuery('#zbs-list-view-edit-filters').click(function(){

                    jQuery('#zbs-list-view-edit-filters-wrap, #zbs-list-filters, #zbs-list-filters-edit-title').toggle();
                    setTimeout(function(){
                        if (jQuery('#zbs-list-view-edit-filters').hasClass('active'))
                            jQuery('#zbs-list-view-edit-filters').removeClass('active');
                        else
                            jQuery('#zbs-list-view-edit-filters').addClass('active')
                    },0);

                });

                // draw quickfilter Buttons
                zeroBSCRMJS_drawFilterButtons();

            }

             

            // drag drop columns
            jQuery( "#zbs-column-manager-available-cols .zbs-column-manager-connected, #zbs-column-manager-current-cols" ).sortable({
              connectWith: ".zbs-column-manager-connected",
              items: '.zbs-column-manager-col',
              placeholder: "ui compact tiny button zbs-column-manager-droptarget",
              // this stops dropping on other 'col type' group lists
              /* actually, just let it happen, no mega loss  
              https://stackoverflow.com/questions/11186355/jquery-ui-sortable-exclude-items-from-being-dropped
              receive: function(event, ui) {

                    if ($(ui.item).hasClass("foohulk")) {
                       $(ui.sender).sortable('cancel');

                        return false;

                    }

                }, */
              stop: function(event,ui){

                // save changes to local var
                zeroBSCRMJS_updateListViewColumnsVar(function(d){

                        // show loading
                        jQuery('#zbs-col-manager-loading').show();

                        // hide: could not save cols
                        jQuery('#zbsCantSaveCols').hide();

                        // columns changed, save via ajax then redraw data
                        zeroBSCRMJS_updateListViewColumns(function(d2){

                            // successfully saved

                                // hide loading
                                jQuery('#zbs-col-manager-loading').hide();

                                // mark data needs refresh:
                                window.zbsListViewParams.retrieved = false;

                                // redraw table
                                zeroBSCRMJS_drawListView();


                        },function(d2){

                            // hide loading
                            jQuery('#zbs-col-manager-loading').hide();

                            // could not save cols
                            jQuery('#zbsCantSaveCols').show();

                        });

                },function(d){

                    // no change, do nothing

                });


              }
            }).disableSelection();

            // draw table
            zeroBSCRMJS_drawListView();

            // bind (Sidebar)
            zeroBSCRMJS_bindSideBar();


            // Using Filters?
            if (window.zbsListViewSettings.filters){

                    // filter button sortables
                    jQuery( "#zbs-list-view-filter-options-current, #zbs-list-view-filter-options-available" ).sortable({
                      connectWith: ".zbs-filter-manager-connected",
                      stop: function(event,ui){

                        
                            // save changes to local var
                            zeroBSCRMJS_updateFilterButtonsVar(function(d){

                                    // show loading
                                    jQuery('#zbs-filter-button-manager-loading').show();

                                    // hide: could not save cols
                                    jQuery('#zbsCantSaveCols').hide();

                                    // columns changed, save via ajax then redraw 
                                    zeroBSCRMJS_updateListViewFilterButtons(function(d2){

                                        // successfully saved

                                            // hide loading
                                            jQuery('#zbs-filter-button-manager-loading').hide();

                                            // redraw buttons list
                                            zeroBSCRMJS_drawFilterButtons();


                                    },function(d2){

                                        // hide loading
                                        jQuery('#zbs-filter-button-manager-loading').hide();

                                        // could not save buttons
                                        jQuery('#zbsCantSaveButtons').show();

                                    });

                            },function(d){

                                // no change, do nothing

                            });

                      }
                    }).disableSelection();

            } // / if using filters

}


        function zeroBSCRMJS_bindSideBar(){

            // search click
            jQuery('#zbs-listview-runsearch').unbind('click').click(function(){

                zeroBSCRMJS_fireSearch();

            });
            jQuery('#zbs-listview-search').keypress(function (e) {
             var key = e.which;
             if(key == 13)  // the enter key code
              {
                zeroBSCRMJS_fireSearch();
                return false;  
              }
            });   


        }

        function zeroBSCRMJS_fireSearch(){
            var searchTerm = jQuery('#zbs-listview-search').val();

                if (searchTerm != ''){

                    // has search term, apply + redraw :)
                    zeroBSCRMJS_updateFilterOptionSearch(searchTerm);

                } else {

                    // empty search term, could do with op

                }
        }



        // takes current filters from local var and generates the url that'd load those...
        function zeroBSCRMJS_listview_generateCurrentFilterURL(withoutSort,withoutQuickFilters){

            var url = window.zbsListViewLink, tagStr = '', quickFilterStr = '', searchTerm = '';


            // Using Tags?
            if (window.zbsListViewSettings.tags){

                // got tags?
                var tagStr = ''; if (typeof window.zbsListViewParams.filters.tags != "undefined" && window.zbsListViewParams.filters.tags.length > 0){

                    // build a csv
                    jQuery.each(window.zbsListViewParams.filters.tags,function(ind,ele){

                        if (tagStr != '') tagStr += ',';
                        
                        // db1
                        if (typeof ele.term_id != "undefined") tagStr += ele.term_id;
                        // db2
                        else if (typeof ele.id != "undefined") tagStr += ele.id;

                    });

                }

            }


            // Using Filters?
            if (window.zbsListViewSettings.filters){

                // got quickfilters?
                var quickFilterStr = ''; if (typeof window.zbsListViewParams.filters.quickfilters != "undefined" && window.zbsListViewParams.filters.quickfilters.length > 0){

                    // build a csv
                    jQuery.each(window.zbsListViewParams.filters.quickfilters,function(ind,ele){

                        if (quickFilterStr != '') quickFilterStr += ',';
                        quickFilterStr += ele;

                    });

                }

            }


            // Using Search?
            if (window.zbsListViewSettings.search){

                // got search?
                var searchTerm = ''; if (typeof window.zbsListViewParams.filters.s != "undefined" && window.zbsListViewParams.filters.s != '') searchTerm = window.zbsListViewParams.filters.s;

            }


            // build url
            if (tagStr != '' && tagStr != 'undefined') url += window.zbsListViewTagFilterAffix + tagStr; //&zbs_tag=1,2,3
            if (searchTerm != '' && searchTerm != 'undefined') url += window.zbsListViewSearchFilterAffix + encodeURIComponent(searchTerm) //&s=ahahah


            if (typeof withoutQuickFilters == "undefined" || !withoutQuickFilters){

                if (quickFilterStr != '') url += window.zbsListViewQuickFilterAffix + quickFilterStr; //&quickfilter=1,2,3

            }

            if (typeof withoutSort =="undefined" || !withoutSort){

                if (typeof window.zbsListViewParams.sort != "undefined"){

                    url += '&sort=' + window.zbsListViewParams.sort;
                    
                    if (typeof window.zbsListViewParams.sortorder != 'undefined' && window.zbsListViewParams.sortorder == 'asc') {
                        url += '&sortdirection=asc';
                    }

                } // if is sort

            } // / if withSort


            return url;

        }

        // takes a new search term, updates local param + re-searches
        function zeroBSCRMJS_updateFilterOptionSearch(s){

            var prev = -1;
            if (typeof window.zbsListViewParams.filters.s != "undefined") prev = window.zbsListViewParams.filters.s;


            // is set?
            // meh, actually... if (typeof window.zbsListViewParams.filters.s != "undefined")
                window.zbsListViewParams.filters.s = s;
                //console.log("set search to " + s,s);

            // catch a bug here - somehow php seems to like to json_encode an array with just search as ARRAY, but with search + tags as OBJECT
            // so force it here..
            if (zeroBSCRMJS_isArray(window.zbsListViewParams.filters)){

                var newObj = {'s':s};
                // any tags?
                if (typeof window.zbsListViewParams.filters.tags != "undefined") newObj.tags = window.zbsListViewParams.filters.tags;

                window.zbsListViewParams.filters = newObj;

            }


            // change?
            if (prev == -1 || prev != s){

                // then redraw :) 

                    // mark data needs refresh:
                    window.zbsListViewParams.retrieved = false;

                    // redraw table
                    zeroBSCRMJS_drawListView();

            }

        }




        // writes any filter sentence out e.g. "tagged xyz"
        function zeroBSCRMJS_writeFilterSentence(){

            //console.log(window.zbsListViewParams.filters);

            var newSentence = '';

            if (typeof window.zbsListViewParams.filters.s != "undefined" && window.zbsListViewParams.filters.s != ''){            

                newSentence += zeroBSCRMJS_listViewLang('containing','Containing') +' "<span>' + window.zbsListViewParams.filters.s + '</span>"';
 
            }

            if (typeof window.zbsListViewParams.filters.tags != "undefined" && window.zbsListViewParams.filters.tags.length > 0){

                if (newSentence != '') newSentence += ' and ';
                newSentence += 'Tagged '; var taggedSentence = '';
                jQuery.each(window.zbsListViewParams.filters.tags,function(ind,ele){

                    //console.log("ele",ele);

                    if (taggedSentence != '') taggedSentence += ', ';
                    taggedSentence += '"<span>' + ele.name + '</span>"';

                });
                newSentence += taggedSentence;

            }


            // got quickfilters?
            if (typeof window.zbsListViewParams.filters.quickfilters != "undefined" && window.zbsListViewParams.filters.quickfilters.length > 0){

                if (newSentence != '') newSentence += ' and ';
                // newSentence += 'Filtered by: '; 
                newSentence += zeroBSCRMJS_listViewLang('filteredby','Filtered by') + ': ';
                var quickFilterStr = '';

                // add to sentence
                jQuery.each(window.zbsListViewParams.filters.quickfilters,function(ind,ele){

                    if (quickFilterStr != '') quickFilterStr += ',';

                    if (ele.substr(0,14) == 'notcontactedin'){

                        // catch these.
                        var dStr = ele.substr(14);
                        //quickFilterStr += 'Not Contacted in ' + dStr + ' days';
                        quickFilterStr += zeroBSCRMJS_listViewLang('notcontactedin','Not Contacted in') + ' ' + dStr + ' ' + zeroBSCRMJS_listViewLang('days','days');

                    } else {

                        // default
                        //quickFilterStr += zeroBSCRMJS_ucwords(ele); // hacky use of ucwords for now
                        // 2.17 added statuses as default, adding some more processing:
                        var eleS = ele.replace(/_/g,' ');
                        quickFilterStr += zeroBSCRMJS_ucwords(eleS); // hacky use of ucwords for now

                    }

                });
                newSentence += quickFilterStr;

            }

            if (newSentence != '') newSentence = '<h5 class="ui header blue"><i class="filter icon"></i>' + newSentence + '</h5>';

            jQuery('#zbs-listview-biline').html(newSentence);
            if (newSentence != ''){
                // show it
                jQuery('#zbs-listview-biline').show();
                // show clear filters button // and filters sentence
                jQuery('#zbs-listview-clearfilters, #zbs-listview-biline').removeClass('zbs-hide').removeClass('hidden');
                
            } else {
                jQuery('#zbs-listview-biline').hide();
                jQuery('#zbs-listview-clearfilters, #zbs-listview-biline').addClass('zbs-hide').addClass('hidden');
            }
            


        }
                

        








        // update data obj to match UI (takes UI and updates obj)
        function zeroBSCRMJS_updateListViewColumnsVar(changecb,nochangecb){

            // blocked?
            if (!window.zbsDrawListViewColUpdateBlocker){

                // set blocker
                window.zbsDrawListViewColUpdateBlocker = true;

                // get columns
                var cols = []; 
                jQuery('#zbs-column-manager-current-cols .zbs-column-manager-col').each(function(ind,ele){

                    // add data-key from each present
                    cols.push({fieldstr:jQuery(ele).attr('data-key'),namestr:jQuery(ele).html()});

                });

                // update obj

                    // compare via json string comparison, see if has changed
                    var changed = false; var lastCols = JSON.stringify(window.zbsListViewParams.columns); var newCols = JSON.stringify(cols);
                    if (lastCols !== newCols) changed = true;
                

                    if (changed){

                        window.zbsListViewParams.columns = cols;

                        // callbacks (this'll reload data or do whatever it needs to)
                        if (typeof changecb == 'function') changecb(cols);

                    } else {

                        // callback (no change)
                        if (typeof nochangecb == 'function') nochangecb(cols);

                    }


                // unset blocker
                window.zbsDrawListViewColUpdateBlocker = false;
            }

        }

        // update column sort from data obj
        function zeroBSCRMJS_updateListViewColumns(successcb,errcb){

            if (!window.zbsDrawListViewColUpdateAJAXBlocker){

                // set blocker
                window.zbsDrawListViewColUpdateAJAXBlocker = true;

                    // postbag!
                    var data = {
                        'action': 'updateListViewColumns',
                        'sec': window.zbscrmjs_secToken,
                        'listtype': window.zbsListViewParams.listtype,
                        'v': window.zbsListViewParams.columns
                    };

                    // Send 
                    jQuery.ajax({
                          type: "POST",
                          url: ajaxurl, // admin side is just ajaxurl not wptbpAJAX.ajaxurl,
                          "data": data,
                          dataType: 'json',
                          timeout: 20000,
                          success: function(response) {

                                // temp debug
                                // debug console.log("Column Data update: ",response);

                                // store updated in object
                                window.zbsListViewParams.columns = response;

                                // any success callback?
                                if (typeof successcb == 'function') successcb(response);

                                // unset blocker
                                window.zbsDrawListViewColUpdateAJAXBlocker = false;

                          },
                          error: function(response){ 

                                // temp debug
                                console.error("Column Data update Error: ",response);

                                // any error callback?
                                if (typeof errcb == 'function') errcb(response);

                                // unset blocker
                                window.zbsDrawListViewColUpdateAJAXBlocker = false;

                          }

                    });


            } // / not blocked


        }




















        // retrieves actual data
        function zeroBSCRMJS_retrieveListViewData(successcb,errcb){

            if (!window.zbsDrawListViewAJAXBlocker){

                // set blocker
                window.zbsDrawListViewAJAXBlocker = true;

                //console.log("posting ", window.zbsListViewParams);

                    // postbag!
                    var data = {
                        'action': 'retrieveListViewData',
                        'sec': window.zbscrmjs_secToken,
                        'v': window.zbsListViewParams
                    };

                    // Send 
                    jQuery.ajax({
                          type: "POST",
                          url: ajaxurl, // admin side is just ajaxurl not wptbpAJAX.ajaxurl,
                          "data": data,
                          dataType: 'json',
                          timeout: 20000,
                          success: function(response) {

                                // Debug console.log(response);

                                // store in object
                                if (typeof response != "undefined" && response !== null && typeof response.objects != "undefined"){
                                    
                                    // store table data
                                    window.zbsListViewData = response.objects;

                                    if (typeof response.objectcount != "undefined"){

                                        // store object count if present
                                        window.zbsListViewCount = response.objectcount;

                                    } else window.zbsListViewCount = 0;

                                } else {
                                    
                                    // error!
                                    console.error('Failed to retrieve object data!',response);
                                 
                                    // any error callback?
                                    if (typeof errcb == 'function') errcb(response);

                                }

                                // any success callback?
                                if (typeof successcb == 'function') successcb(response);

                                // unset blocker
                                window.zbsDrawListViewAJAXBlocker = false;

                          },
                          error: function(response){ 

                                // temp debug
                                console.error("List View Data Retrieve Error: ",response);

                                // any error callback?
                                if (typeof errcb == 'function') errcb(response);

                                // unset blocker
                                window.zbsDrawListViewAJAXBlocker = false;

                          }

                    });


            } // / not blocked


        }

        // passes language from window.zbsListViewLangLabels (js set in listview php)
        function zeroBSCRMJS_listViewLang(key,fallback){

            if (typeof fallback == 'undefined') var fallback = '';

            if (typeof window.zbsListViewLangLabels[key] != "undefined") return window.zbsListViewLangLabels[key];

            return fallback;
        }

        // returns fa icon (used for bulk actions atm.)
        function zeroBSCRMJS_listViewIco(key,fallback){

            if (typeof fallback == 'undefined') var fallback = '';

            if (typeof window.zbsListViewIcos[key] != "undefined") return window.zbsListViewIcos[key];

            return fallback;
        }
        

        // https://semantic-ui.com/collections/table.html
        function zeroBSCRMJS_drawListView(){

            //console.log('drawing with',window.zbsListViewParams);

            // if no blocker
            if (!window.zbsDrawListViewBlocker){

                // move this, if it's present (if was 0 results it'll be in the table, otherwise, wont be anyhow :)
                jQuery('#zbsNoResults').addClass('hidden').appendTo('#zbs-list-warnings-wrap');

                // debug
                // debug console.log("Drawing table with params",window.zbsListViewParams);

                // put blocker up
                window.zbsDrawListViewBlocker = true;

                // empty table, show loading
                jQuery('#zbs-list-table-wrap').html(window.zbsDrawListLoadingBoxHTML);

                // check data + retrieve if empty
                if (!window.zbsListViewParams.retrieved){

                    // debug console.log("Retrieving table data");

                    // retrieve data
                    zeroBSCRMJS_retrieveListViewData(function(d){

                        // holds event flags to fire post-draw
                        var postHTML = {};

                        // success callback
                        // debug console.log("Drawing with data",window.zbsListViewData);

                            // build html
                            var listViewHTML = ''; 

                            // header
                            listViewHTML += zeroBSCRMJS_listViewHeader();

                                // per line
                                if (window.zbsListViewData.length > 0) {
                                    jQuery.each(window.zbsListViewData,function(ind,ele){

                                        // add to html
                                        listViewHTML += zeroBSCRMJS_listViewLine(ele);

                                    });
                                } else {

                                    // no lines, move this ui msg into a blank row col
                                    if (jQuery('#zbsNoResults').length) {

                                        listViewHTML += '<tr><td colspan="' + window.zbsListViewParams.columns.length + '" id="zbs-no-results-wrap">';

                                        // to be fired after setTimeout jQuery('#zbsNoResults').appendTo('#zbs-no-results-wrap');
                                        postHTML.nores = true;

                                        listViewHTML += '</td></tr>';

                                    }
                                }
                            
                            // footer
                            listViewHTML += zeroBSCRMJS_listViewFooter();


                            // set 
                            jQuery('#zbs-list-table-wrap').html(listViewHTML);

                            // catch any post-html events
                            setTimeout(function(){

                                var lPostHTML = postHTML;

                                // empty result set.
                                if (typeof lPostHTML.nores) jQuery('#zbsNoResults').appendTo('#zbs-no-results-wrap').removeClass('hidden');

                                // bind any post-render (e.g. bulk action)
                                zeroBSCRMJS_listViewBinds();

                            },0);

                            // update semantic filter setence:
                            zeroBSCRMJS_writeFilterSentence();

                            // fini, remove blocker
                            window.zbsDrawListViewBlocker = false;

                    },function(errd){

                        // err callback? show msg (prefilled by php)
                        jQuery('#zbsCantLoadData').show();
                        jQuery('#zbs-list-table-wrap, #zbs-list-sidebar-wrap').hide();

                        // fini, remove blocker
                        window.zbsDrawListViewBlocker = false;

                    });

                }
            }

        }
        function zeroBSCRMJS_listViewHeader(){

            var listViewHeaderHTML = ''

            if (window.zbsListViewParams.columns.length > 0){
                var listViewHeaderHTML = '<table class="ui celled table unstackable">';
                    listViewHeaderHTML += '      <thead>';
                    listViewHeaderHTML += '        <tr>';

                    // bulk checkbox
                    // only if there's any bulk actions to use!
                    if (typeof window.zbsBulkActions != "undefined" && window.zbsBulkActions.length > 0) {
                        listViewHeaderHTML += '<th class="zbs-listview-bulk-hd"><div class="ui fitted checkbox"><input type="checkbox" id="zbsbulkmaster" /><label for="zbsbulkmaster"></label></div></th>';
                    }

                    jQuery.each(window.zbsListViewParams.columns,function(lvhInd,lvhEle){

                        var tdStr = lvhEle.namestr;
                        // debug console.log("sortable? " + window.zbsUnsortables.indexOf(lvhEle.fieldstr),[lvhEle.fieldstr,window.zbsUnsortables]);

                        // sortable?
                        // This says "are not in unsortable"
                        //if (typeof window.zbsListViewParams.sort != "undefined" && window.zbsUnsortables.indexOf(lvhEle.fieldstr) < 0){
                        // but for v2.2 we'll do "in zbsSortables"(because need new db  format to properly do sort)
                        if (typeof window.zbsSortables != "undefined" && window.zbsSortables.indexOf(lvhEle.fieldstr) > -1){



                            if (typeof window.zbsListViewParams.sort != "undefined" && window.zbsListViewParams.sort == lvhEle.fieldstr){

                                var sortDirection = 'down'; var sortDirectionUrlParam = 'asc';
                                if (typeof window.zbsListViewParams.sortorder != 'undefined' && window.zbsListViewParams.sortorder == 'asc') {
                                    sortDirection = 'up';
                                    sortDirectionUrlParam = 'desc';
                                }

                                tdStr += ' <a href="' + zeroBSCRMJS_listview_generateCurrentFilterURL(true) + '&sort=' + lvhEle.fieldstr + '&sortdirection=' + sortDirectionUrlParam + '" title="Click to Sort"><i class="angle ' + sortDirection + ' icon"></i></a>';

                            } else {

                                // use name as sort link, always defaults to desc
                                tdStr = ' <a href="' + zeroBSCRMJS_listview_generateCurrentFilterURL(true) + '&sort=' + lvhEle.fieldstr + '" title="Click to Sort">' + tdStr + '</a>';

                            }

                        } // / is sortable

                        
                    
                        listViewHeaderHTML += '        <th>' + tdStr + '</th>';

                    });                    
                    listViewHeaderHTML += '      </tr></thead>';
                    listViewHeaderHTML += '      <tbody>';

            } 

            return listViewHeaderHTML;
        }
        function zeroBSCRMJS_listViewLine(data){

      
            var lineHTML = '';

            if (window.zbsListViewParams.columns.length > 0){ //&& window.zbsListViewParams.columns.length == data.length){
                
                // if id passed, add to attr
                var trAttr = ''; if (typeof data.id != "undefined") trAttr += ' data-id="' + data.id + '"';

                var lineHTML = '<tr' + trAttr + '>';

                    // bulk actions checkbox (note the data['name'] is used for bulk actions etc.                                 
                    // only if there's any bulk actions to use!
                    if (typeof window.zbsBulkActions != "undefined" && window.zbsBulkActions.length > 0) {
                        lineHTML += '<td class="zbs-listview-bulk"><div class="ui fitted checkbox"><input type="checkbox" id="zbsbulk' + data['id'] + '" data-entityid="' + data['id'] + '" data-entityname="' + data['name'] + '" class="zbsbulkcb" /><label for="zbsbulk' + data['id'] + '"></label></div></td>';
                    }

                    

                    jQuery.each(window.zbsListViewParams.columns,function(lvhInd,lvhEle){
                    
                        // if override func exists, use that, else use default out:
                        var fieldFuncName = 'zeroBSCRMJS_listView_' + window.zbsListViewSettings.objdbname +'_'+lvhEle.fieldstr;
                        
                        if (typeof window[fieldFuncName] == 'function'){

                            // use it                            
                            lineHTML += window[fieldFuncName](data);

                        } else {

                            // see if generic exists
                            // e.g.  zeroBSCRMJS_listView_generic_nameavatar
                            var fieldFuncName = 'zeroBSCRMJS_listView_generic_'+lvhEle.fieldstr;
                            if (typeof window[fieldFuncName] == 'function'){

                                // use it
                                lineHTML += window[fieldFuncName](data);

                            } else {
                                
                                // final fallback
                                // all custom fields will likely end up here. 

                                // ... a short workaround for #149, here we check for presence of _cfdate 
                                // ... which will be set for any customfield date type fields:
                                if (typeof data[lvhEle.fieldstr + '_cfdate'] != "undefined" && data[lvhEle.fieldstr + '_cfdate'] != null && data[lvhEle.fieldstr + '_cfdate'] != 'null'){

                                    lineHTML += '<td>' + data[lvhEle.fieldstr + '_cfdate'] + '</td>';

                                } else {

                                    // Normal field output without any _td builder:
                                    if (typeof data[lvhEle.fieldstr] != "undefined" && data[lvhEle.fieldstr] != null && data[lvhEle.fieldstr] != 'null')
                                        lineHTML += '<td>' + data[lvhEle.fieldstr] + '</td>';
                                    else
                                        lineHTML += '<td></td>'; // empty

                                }

                            }

                        }

                    });                    
                    lineHTML += '</tr>';

            } 

            return lineHTML;

        }

        function zeroBSCRMJS_listViewFooter(){

            var listViewFooterHTML = '';

            /* could use...

            <tfoot>
                        <tr><th colspan="3">
                          <div class="ui right floated pagination menu">
                            <a class="icon item">
                              <i class="left chevron icon"></i>
                            </a>
                            <a class="item">1</a>
                            <a class="item">2</a>
                            <a class="item">3</a>
                            <a class="item">4</a>
                            <a class="icon item">
                              <i class="right chevron icon"></i>
                            </a>
                          </div>
                        </th>
                      </tr></tfoot>

            */

            // if pagination!
            if (typeof window.zbsListViewParams.pagination != "undefined"){

                // show count
                if (typeof window.zbsListViewCount != "undefined"){
                
                    // + 1 here is bulk actions cb col
                    listViewFooterHTML += '<tfoot><tr><th colspan="' + (window.zbsListViewParams.columns.length+1) + '">';

                    /* pagination */


                    var currentPage = 1; if (typeof window.zbsListViewParams.paged != "undefined") currentPage = window.zbsListViewParams.paged;
                    var perPageCount = window.zbsListViewParams.count; //20;
                    var totalPages = 1; 
                    var currentSearchURL = zeroBSCRMJS_listview_generateCurrentFilterURL(); // this takes filters and makes an url that'll prefix our pagination
                    var prevPageURL = ''; var nextPageURL = '';

                    // calc total pages
                    if (window.zbsListViewCount > 0 && perPageCount > 0){

                        totalPages = Math.ceil(window.zbsListViewCount/perPageCount);

                    }

                    if (totalPages > 1){

                        // this'll hold page links
                        var pageLinksToAdd = [];

                        // and this'll show <> if avail
                        if (totalPages > 1 && currentPage > 1) prevPageURL = currentSearchURL+'&paged='+ (currentPage-1);
                        if (totalPages > 1 && totalPages >= (currentPage+1)) nextPageURL = currentSearchURL+'&paged='+ (currentPage+1);


                        // draw pagination bar
                        if (totalPages < 9){

                            // just draw them out, otherwise draw first few last few
                            for (p = 1; p <= totalPages; p++){

                                // build as obj for later drawing
                                var anyActive = ''; if (p == currentPage) anyActive = ' active';
                                var pageObj = {page:p,url:currentSearchURL+'&paged='+p,classStr:'item'+anyActive};

                                pageLinksToAdd.push(pageObj);

                            }

                        } else {

                            // draw first few last few

                                // first 3
                                for (p = 1; p <= 3; p++){

                                    // build as obj for later drawing
                                    var anyActive = ''; if (p == currentPage) anyActive = ' active';
                                    var pageObj = {page:p,url:currentSearchURL+'&paged='+p,classStr:'item'+anyActive};

                                    pageLinksToAdd.push(pageObj);

                                }

                                // then the active number if not in last 3 or first 3
                                if (currentPage > 3 && currentPage < (totalPages-2)){

                                    // a few dots in middle :)
                                    var pageObj = {page:false,url:'#',classStr:'item'};
                                    pageLinksToAdd.push(pageObj);

                                    // then this no
                                    var pageObj = {page:currentPage,url:currentSearchURL+'&paged='+currentPage,classStr:'item active'};
                                    pageLinksToAdd.push(pageObj);


                                }

                                // with a few dots in middle :)
                                var pageObj = {page:false,url:'#',classStr:'item'};
                                pageLinksToAdd.push(pageObj);

                                // last 3
                                for (p = (totalPages-2); p <= totalPages; p++){

                                    // build as obj for later drawing
                                    var anyActive = ''; if (p == currentPage) anyActive = ' active';
                                    var pageObj = {page:p,url:currentSearchURL+'&paged='+p,classStr:'item'+anyActive};

                                    pageLinksToAdd.push(pageObj);

                                }


                        }

                        // then build it :)
                        if (pageLinksToAdd.length > 0){

                              listViewFooterHTML += '<div class="ui right floated pagination menu">';

                            // prev avail?
                            if (prevPageURL != ''){
                                listViewFooterHTML += '<a class="icon item" href="' + prevPageURL + '">';
                                  listViewFooterHTML += '<i class="left chevron icon"></i>';
                                listViewFooterHTML += '</a>';
                            } else {
                                listViewFooterHTML += '<a class="icon item disabled">';
                                  listViewFooterHTML += '<i class="left chevron icon"></i>';
                                listViewFooterHTML += '</a>';
                            }

                                jQuery.each(pageLinksToAdd,function(ind,ele){
                                    
                                    if (ele.page !== false)
                                        listViewFooterHTML += '<a class="' + ele.classStr + '" href="' + ele.url + '">' + ele.page + '</a>';
                                    else 
                                        // ...
                                        listViewFooterHTML += '<a class="item disabled">...</a>';

                                });

                            // next avail?
                            if (nextPageURL != ''){
                                listViewFooterHTML += '<a class="icon item" href="' + nextPageURL + '">';
                                  listViewFooterHTML += '<i class="right chevron icon"></i>';
                                listViewFooterHTML += '</a>';
                            } else {
                                listViewFooterHTML += '<a class="icon item disabled">';
                                  listViewFooterHTML += '<i class="right chevron icon"></i>';
                                listViewFooterHTML += '</a>';

                            }


                            listViewFooterHTML += '</div>';

                          }

                      }


                    // draw count
                    var objStrName = window.zbsListViewObjName;
                    //var incS = ''; if (window.zbsListViewCount > 1 || window.zbsListViewCount == 0) incS = 's';
                    if (window.zbsListViewCount > 1 || window.zbsListViewCount == 0) objStrName = window.zbsListViewObjNamePlural;
                    listViewFooterHTML +=  '<div id="zbs-listview-footer-count">' + zbscrmjs_prettifyLongInts(window.zbsListViewCount) + ' ' + objStrName + '</div>'; // <div class="ui left floated pagination menu"> + '</div>'; // '<div class="ui small  disabled button">' +

                    // bulk action
                    listViewFooterHTML += '<div id="zbsbulkactions" style="display:none"></div>';


                    /// close
                        listViewFooterHTML += '</th>';
                      listViewFooterHTML += '</tr></tfoot>';

                }


            }
                

            listViewFooterHTML += '</tbody></table>';

            return listViewFooterHTML;
        }

        function zeroBSCRMJS_listViewBinds(){

            // bulk select all
            /* this annoyingly wasn't working.
            jQuery('#zbsbulkmaster').checkbox({
                onChecked: function(){

                    console.log('checked');
                    // select all
                    //jQuery('.zbsbulkcb').checkbox('check');
                    jQuery('.zbs-listview-bulk input:checkbox').attr('checked', true);
                    
                },
                onUnchecked: function(){

                    // deselect all
                    //jQuery('.zbsbulkcb').checkbox('uncheck');
                    jQuery('.zbs-listview-bulk input:checkbox').attr('checked', false);
                    
                }
            }); */
            jQuery('#zbsbulkmaster').unbind('click').click(function(){
                
                if (jQuery('#zbsbulkmaster').attr('checked') == 'checked'){

                    // select all
                    //jQuery('.zbsbulkcb').checkbox('check');
                    jQuery('.zbs-listview-bulk input:checkbox').attr('checked', true);
                    
                } else {

                    // deselect all
                    //jQuery('.zbsbulkcb').checkbox('uncheck');
                    jQuery('.zbs-listview-bulk input:checkbox').attr('checked', false);
                    
                }

                // and update any bulk actions strs etc.
                setTimeout(function(){
                    zeroBSCRMJS_listView_bulkActionsUpdate();
                },0);

            });

            // any individual checkboxes:
            jQuery('.zbs-listview-bulk input:checkbox').click(function(){


                // and update any bulk actions strs etc.
                setTimeout(function(){
                    zeroBSCRMJS_listView_bulkActionsUpdate();
                },0);

            });

            // inline editing
            if (typeof window.zbsListViewSettings.editinline != "undefined" && window.zbsListViewSettings.editinline) zeroBSCRMJS_bindInlineEditing();


            // HOOK for list views
            // use func name like zeroBSCRMJS_listView_postRender_mailcampaign to fire stuff here
            var listViewPostRenderHookName = ''; 
            if (typeof window.zbsListViewParams != "undefined" && typeof window.zbsListViewParams.listtype != "undefined") listViewPostRenderHookName = 'zeroBSCRMJS_listView_postRender_' + window.zbsListViewParams.listtype;
            if (listViewPostRenderHookName != '' && typeof window[listViewPostRenderHookName] == 'function'){
                // fire
                window[listViewPostRenderHookName]();
            }
        }

        function zeroBSCRMJS_listView_bulkActionsUpdate(){

            // only if there's any bulk actions to use!
            if (typeof window.zbsBulkActions != "undefined" && window.zbsBulkActions.length > 0) {

                var bulkActionsHTML = '';
                var bulkActionsSelected = zeroBSCRMJS_listView_bulkActionsGetChecked();

                // any selected?
                if (bulkActionsSelected.length > 0){

                    // str
                    bulkActionsHTML += '(' + bulkActionsSelected.length + ' selected) ';

                    // avail 
                    if (typeof window.zbsBulkActions != "undefined" && window.zbsBulkActions.length > 0) {

                        // actions
                        bulkActionsHTML += '<select id="zbsbulkactionmaster">';

                            jQuery.each(window.zbsBulkActions,function(ind,ele){

                                var inc = true;

                                // only include "merge" if 2 selected
                                if (ele == 'merge') {
                                    if (bulkActionsSelected.length != 2) inc = false;
                                };

                                if (inc){
                                    // opt for each
                                    var optFuncName = 'zeroBSCRMJS_listView_' + window.zbsListViewSettings.objdbname + '_bulkActionTitle_' + ele;
                                    //console.log('calling',optFuncName);
                                    var optnamehtml = window[optFuncName]();
                                    //console.log('called '  + optFuncName,optnamehtml);                                
                                    bulkActionsHTML += '<option value="' + ele + '">' + optnamehtml + '</option>';
                                }


                            });


                        bulkActionsHTML += '</select>';

                    }

                    // action button
                    bulkActionsHTML += '<button id="zbsbulkactionmastergo" class="ui tiny button primary" style="margin-left:0.4em">Go</button>';

                }

                jQuery('#zbsbulkactions').html(bulkActionsHTML).show();

                // bind
                setTimeout(function(){

                    jQuery('#zbsbulkactionmastergo').unbind('click').click(function(){

                        // fire a gatherer func (allows for SWAL between click + fire (e.g. leave orphans, are you sure, choose tag))
                        // e.g. zeroBSCRMJS_listView_customer_bulkActionFire_delete
                        var optFuncName = 'zeroBSCRMJS_listView_' + window.zbsListViewSettings.objdbname + '_bulkActionFire_' + jQuery('#zbsbulkactionmaster').val();
                        // Debug console.log(optFuncName);
                        window[optFuncName]();

                    });

                },0);

            } // end if bulk actions

        }

        // update column sort from data obj
        function zeroBSCRMJS_enactBulkAction(actionstr,idList,extraParams,successcb,errcb){

            if (!window.zbsDrawListViewAJAXBlocker){

                // set blocker
                window.zbsDrawListViewAJAXBlocker = true;

                    if (typeof extraParams == "undefined") extraParams = {};

                    // postbag!
                    var data = {
                        'action': 'enactListViewBulkAction',
                        'sec': window.zbscrmjs_secToken,
                        'objtype': window.zbsListViewSettings.objdbname,
                        'actionstr': actionstr,
                        'ids': idList
                    };

                    // merge in any extra params
                    data = zeroBSCRMJS_extend(data,extraParams);

                    // Send 
                    jQuery.ajax({
                          type: "POST",
                          url: ajaxurl, // admin side is just ajaxurl not wptbpAJAX.ajaxurl,
                          "data": data,
                          dataType: 'json',
                          timeout: 20000,
                          success: function(response) {

                                // any success callback?
                                if (typeof successcb == 'function') successcb(response);

                                // unset blocker
                                window.zbsDrawListViewAJAXBlocker = false;

                                // refire draw

                                    // mark data needs refresh:
                                    window.zbsListViewParams.retrieved = false;

                                    // redraw table
                                    zeroBSCRMJS_drawListView();

                          },
                          error: function(response){ 

                                // any error callback?
                                if (typeof errcb == 'function') errcb(response);

                                // unset blocker
                                window.zbsDrawListViewAJAXBlocker = false;

                                // no refiring of draw :)

                          }

                    });


            } // / not blocked


        }

        function zeroBSCRMJS_listView_bulkActionsGetChecked(){

            // quick - cycles through checkboxes + returns array of id's

            var selected = [];
            jQuery('.zbs-listview-bulk input:checkbox').each(function(ind,ele){

                if (jQuery(ele).attr('checked') == 'checked') selected.push(jQuery(ele).attr('data-entityid'));

            });


            return selected;

        }

        function zeroBSCRMJS_listView_bulkActionsGetCheckedIncNames(){

            // quick - cycles through checkboxes + returns array of id's + names

            var selected = [];
            jQuery('.zbs-listview-bulk input:checkbox').each(function(ind,ele){

                if (jQuery(ele).attr('checked') == 'checked') selected.push({id:jQuery(ele).attr('data-entityid'),name:jQuery(ele).attr('data-entityname')});

            });


            return selected;

        }


        function zeroBSCRMJS_listView_editURL(id){

        /* refactored
            if (typeof id != "undefined" && id > 0){
                if (window.zbsListViewParams.listtype == 'customer') || window.zbsListViewParams.listtype == 'transaction' || window.zbsListViewParams.listtype == 'form' || window.zbsListViewParams.listtype == 'quote' || window.zbsListViewParams.listtype == 'company' || window.zbsListViewParams.listtype == 'invoice') 
                    return window.zbsObjectEditLinkPrefix + '?post=' + id + '&action=edit';
                else
                    return window.zbsObjectEditLinkPrefix + id;
            } else 
                return '#notfound';
        */


            // PRE DAL3 we do diff
            if (zbscrm_JS_DAL() > 2){

                // DAL 3 
                if (typeof id != "undefined" && id > 0){
                    switch (window.zbsListViewParams.listtype){

                        case 'customer':
                            return window.zbsObjectEditLinkPrefixCustomer + id;
                            break;

                        case 'company':
                            return window.zbsObjectEditLinkPrefixCompany + id;
                            break;

                        case 'quote':
                            return window.zbsObjectEditLinkPrefixQuote + id;
                            break;

                        case 'invoice':
                            return window.zbsObjectEditLinkPrefixInvoice + id;
                            break;

                        case 'transaction':
                            return window.zbsObjectEditLinkPrefixTransaction + id;
                            break;

                        case 'segment':
                            return window.zbsObjectEditLinkPrefixSegment + id;
                            break;

                        case 'form':
                            return window.zbsObjectEditLinkPrefixForm + id;
                            break;

                        case 'quotetemplate':
                            return window.zbsObjectEditLinkPrefixQuoteTemplate + id;
                            break;

                        // nothing?
                        default:
                            return window.zbsObjectEditLinkPrefix + id;//'?post=' + id + '&action=edit';//window.zbsObjectEditLinkPrefix + id;
                            break;

                    }

                }
                

            } else {

                // <DAL3

                if (typeof id != "undefined" && id > 0){
                    switch (window.zbsListViewParams.listtype){

                        case 'customer':
                            return window.zbsObjectEditLinkPrefixCustomer + id;
                            break;

                        case 'segment':
                            return window.zbsObjectEditLinkPrefixCustomer + id + '&zbstype=segment';
                            break;


                        // all non-contacts atm
                        default:
                            return window.zbsObjectEditLinkPrefix + id;//'?post=' + id + '&action=edit';//window.zbsObjectEditLinkPrefix + id;
                            break;

                    }
                }

            } // / <DAL3


            return '#notfound';
        }

        function zeroBSCRMJS_listView_viewURL(id){

        /* refactored
            if (typeof id != "undefined" && id > 0){

                // co doesn't seem to work?  window.zbsListViewParams.listtype == 'company'
                // quo doesn't either window.zbsListViewParams.listtype == 'quote' || 
                // } || window.zbsListViewParams.listtype == 'transaction' || window.zbsListViewParams.listtype == 'form' || window.zbsListViewParams.listtype == 'invoice')   
                if (window.zbsListViewParams.listtype == 'customer')
                    return zeroBSCRMJS_listView_viewURL_customer(id); //window.zbsObjectViewLinkPrefix + id;
                else
                    return window.zbsObjectEditLinkPrefix + '&post=' + id ;
            } else 
                return '#notfound';
        */

            // PRE DAL3 we do diff
            if (zbscrm_JS_DAL() > 2){

                // DAL 3 
                if (typeof id != "undefined" && id > 0){
                    switch (window.zbsListViewParams.listtype){

                        case 'customer':
                            return window.zbsObjectViewLinkPrefixCustomer + id;
                            break;

                        case 'company':
                            return window.zbsObjectViewLinkPrefixCompany + id;
                            break;

                        case 'quote':
                            return window.zbsObjectViewLinkPrefixQuote + id;
                            break;

                        case 'invoice':
                            return window.zbsObjectViewLinkPrefixInvoice + id;
                            break;

                        case 'transaction':
                            return window.zbsObjectViewLinkPrefixTransaction + id;
                            break;

                        case 'segment':
                            return window.zbsObjectViewLinkPrefixSegment + id;
                            break;

                        case 'form':
                            return window.zbsObjectViewLinkPrefixForm + id;
                            break;


                        // nothing?
                        default:
                            return window.zbsObjectEditLinkPrefix + id;//'?post=' + id; //return window.zbsObjectEditLinkPrefix + '&post=' + id ;
                            break;


                    }
                }


            } else {

                // <DAL3

                if (typeof id != "undefined" && id > 0){
                    switch (window.zbsListViewParams.listtype){

                        case 'customer':
                            return window.zbsObjectViewLinkPrefixCustomer + id;
                            break;

                        case 'company':
                            return window.zbsObjectViewLinkPrefixCompany + id;
                            break;

                        // all non-contacts atm
                        default:
                            return window.zbsObjectEditLinkPrefix + id;//'?post=' + id; //return window.zbsObjectEditLinkPrefix + '&post=' + id ;
                            break;


                    }
                }


            } // / <DAL3

            return '#notfound';

        }

        // specific to customer
        // used when page is non-customer e.g. trans list view lists customers
        function zeroBSCRMJS_listView_viewURL_customer(id){
       
            if (typeof id != "undefined" && id > 0)
                return window.zbsObjectViewLinkPrefixCustomer + id;
            else 
                return '#notfound';
        }

        // specific to company
        function zeroBSCRMJS_listView_viewURL_company(id){
       
            if (typeof id != "undefined" && id > 0)
                return window.zbsObjectViewLinkPrefixCompany + id;
            else 
                return '#notfound';
        }

        // specific to contact (Currently)
        function zeroBSCRMJS_listView_emailURL_contact(id){
       
            if (typeof id != "undefined" && id > 0)
                return window.zbsObjectEmailLinkPrefix + id;
            else 
                return '#notfound';
        }


        // filter button stuff
        var zbsDrawFilterButtonUpdateBlocker = false;
        var zbsDrawFilterButtonUpdateAJAXBlocker = false;

        // update data obj to match UI (takes UI and updates obj)
        function zeroBSCRMJS_updateFilterButtonsVar(changecb,nochangecb){

            // blocked?
            if (!window.zbsDrawFilterButtonUpdateBlocker){

                // set blocker
                window.zbsDrawFilterButtonUpdateBlocker = true;

                // get buttons
                var buttons = []; 
                jQuery('#zbs-list-view-filter-options-current .zbs-filter-button-manager-button').each(function(ind,ele){

                    // add data-key from each present
                    buttons.push({fieldstr:jQuery(ele).attr('data-key'),namestr:jQuery(ele).html()});

                });

                // update obj

                    // compare via json string comparison, see if has changed
                    var changed = false; var lastButtons = JSON.stringify(window.zbsFilterButtons); var newButtons = JSON.stringify(buttons);
                    if (lastButtons !== newButtons) changed = true;
                

                    if (changed){

                        window.zbsFilterButtons = buttons;

                        // callbacks (this'll reload data or do whatever it needs to)
                        if (typeof changecb == 'function') changecb(buttons);

                    } else {

                        // callback (no change)
                        if (typeof nochangecb == 'function') nochangecb(buttons);

                    }


                // unset blocker
                window.zbsDrawFilterButtonUpdateBlocker = false;
            }

        }

        // update column sort from data obj
        function zeroBSCRMJS_updateListViewFilterButtons(successcb,errcb){

            if (!window.zbsDrawFilterButtonUpdateAJAXBlocker){

                // set blocker
                window.zbsDrawFilterButtonUpdateAJAXBlocker = true;

                    // postbag!
                    var data = {
                        'action': 'updateListViewFilterButtons',
                        'sec': window.zbscrmjs_secToken,
                        'listtype': window.zbsListViewParams.listtype,
                        'v': window.zbsFilterButtons
                    };

                    // Send 
                    jQuery.ajax({
                          type: "POST",
                          url: ajaxurl, // admin side is just ajaxurl not wptbpAJAX.ajaxurl,
                          "data": data,
                          dataType: 'json',
                          timeout: 20000,
                          success: function(response) {

                                // temp debug
                                // debug console.log("Column Data update: ",response);

                                // store updated in object
                                window.zbsFilterButtons = response;

                                // any success callback?
                                if (typeof successcb == 'function') successcb(response);

                                // unset blocker
                                window.zbsDrawFilterButtonUpdateAJAXBlocker = false;

                          },
                          error: function(response){ 

                                // temp debug
                                console.error("Column Data update Error: ",response);

                                // any error callback?
                                if (typeof errcb == 'function') errcb(response);

                                // unset blocker
                                window.zbsDrawFilterButtonUpdateAJAXBlocker = false;

                          }

                    });


            } // / not blocked


        }

        function zeroBSCRMJS_drawFilterButtons(){

            var newHTML = '';

            jQuery.each(window.zbsFilterButtons,function(ind,ele){

                // if not selected
                var colorClasses = 'olive'; var withoutQuickFilterURLParam = false; var addQuickFilterURLParam = '&quickfilters=' + ele.fieldstr;
                // if selected..
                if (typeof window.zbsListViewParams.filters.quickfilters != "undefined" && window.zbsListViewParams.filters.quickfilters.indexOf(ele.fieldstr) > -1) {

                    colorClasses = 'green';
                    withoutQuickFilterURLParam = true; // allows click to reset
                     addQuickFilterURLParam = '';
                
                }



                newHTML += '<a href="' + zeroBSCRMJS_listview_generateCurrentFilterURL(true,withoutQuickFilterURLParam) + addQuickFilterURLParam + '" class="ui ' + colorClasses + ' button tiny">' + ele.namestr + '</a>';

            });

            jQuery('#zbs-list-filters').html(newHTML);

        }

/* ====================================================================================
============== Bulk actions - Pre-checks - Customers ==================================
==================================================================================== */

        function zeroBSCRMJS_listView_customer_bulkActionFire_delete(){

            // SWAL sanity check + leave orphans?
            var extraParams = { leaveorphans: true };

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('areyousure'),
              html: '<div>' + zeroBSCRMJS_listViewLang('areyousurethese') + '<br /><label>' + zeroBSCRMJS_listViewLang('andthese') + '</label></div><select id="zbsbulkactiondeleteleaveorphans"><option value="true" selected="selected">' + zeroBSCRMJS_listViewLang('noleave') + '</option><option value="false">' + zeroBSCRMJS_listViewLang('yesthose') + '</option></select></div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Yes, delete!',
              //allowOutsideClick: false
            }).then(function (result) {

                // this check required from swal2 6.0+
                if (result.value){

                    // get setting
                    extraParams.leaveorphans = jQuery('#zbsbulkactiondeleteleaveorphans').val();

                    // fire delete + will automatically refresh list view
                    zeroBSCRMJS_enactBulkAction('delete',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                        // success ? SWAL?
                          swal(
                            zeroBSCRMJS_listViewLang('deleted'),
                            zeroBSCRMJS_listViewLang('contactsdeleted'),
                            'success'
                          );

                    },function(r){

                        // fail ? SWAL?
                        swal(
                            zeroBSCRMJS_listViewLang('notdeleted'),
                            zeroBSCRMJS_listViewLang('notcontactsdeleted'),
                            'warning'
                        );

                    }); 

                }

            });



        }
        function zeroBSCRMJS_listView_customer_bulkActionFire_addtag(){

           
            // SWAL which tag(s)?
            var extraParams = { tags: [] };

            // avail tags will be here: zbsTagsForBulkActions

            // build typeahead html
            /* actually, a straight list makes more sense, until too many
            var tagTypeaheadHTML = '<div id="zbs-tag-typeahead-wrap" class="zbstypeaheadwrap zbsbtypeaheadfullwidth">';
                tagTypeaheadHTML += '<input class="typeahead" type="text" placeholder="Tag...">';
                tagTypeaheadHTML += '</div>';
            */

            // build tag list (toggle'able)
            var tagSelectList = '<div id="zbs-select-tags" class="ui segment">';
                if (typeof window.zbsTagsForBulkActions != "undefined" && window.zbsTagsForBulkActions.length > 0){

                    jQuery.each(window.zbsTagsForBulkActions,function(ind,tag){
                        tagSelectList += '<div class="zbs-select-tag ui label"><div class="ui checkbox"><input type="checkbox" data-tagid="' + tag.id + '" id="zbs-tag-' + tag.id + '" /><label for="zbs-tag-' + tag.id + '">' + tag.name + '</label></div></div>';
                    });

                } else {

                    tagSelectList += '<div class="ui message"><p>' + zeroBSCRMJS_listViewLang('notags') + '</p></div>'   
                
                }
                tagSelectList += '</div>';

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('whichtags'),
              html: '<div>' + zeroBSCRMJS_listViewLang('whichtagsadd') + '<br />' + tagSelectList + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('addthesetags'),
              //allowOutsideClick: false,
              onOpen: function(){

                    // bind checkboxes (this just adds nice colour effect, not that important)
                    jQuery('.zbs-select-tag input:checkbox').unbind('click').click(function(){
                        
                        jQuery('.zbs-select-tag input:checkbox').each(function(ind,ele){

                            if (jQuery(ele).attr('checked') == 'checked')
                                jQuery(ele).closest('.ui.label').addClass('blue');
                            else
                                jQuery(ele).closest('.ui.label').removeClass('blue');

                        });
                        

                    });


              }
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // get settings
                    extraParams.tags = [];

                        // cycle through each tag input and if checked, add id
                        jQuery('.zbs-select-tag input:checkbox').each(function(ind,ele){

                                if (jQuery(ele).attr('checked') == 'checked') extraParams.tags.push(jQuery(ele).attr('data-tagid'));

                        });

                    // any tags?
                    if (extraParams.tags.length > 0){


                        // fire + will automatically refresh list view
                        zeroBSCRMJS_enactBulkAction('addtag',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                            // success ? SWAL?
                              swal(
                                zeroBSCRMJS_listViewLang('tagsadded'),
                                zeroBSCRMJS_listViewLang('tagsaddeddesc'),
                                'success'
                              );

                        },function(r){

                            // fail ? SWAL?
                            swal(
                                zeroBSCRMJS_listViewLang('tagsnotadded'),
                                zeroBSCRMJS_listViewLang('tagsnotaddeddesc'),
                                'warning'
                            );

                        }); 

                    } else {

                        // didn't select tags

                        swal(
                            zeroBSCRMJS_listViewLang('tagsnotselected'),
                            zeroBSCRMJS_listViewLang('tagsnotselecteddesc'),
                            'warning'
                        );

                    }

                }

            });


                    


        }
        function zeroBSCRMJS_listView_customer_bulkActionFire_removetag(){

           
            // SWAL which tag(s)?
            var extraParams = { tags: [] };

            // avail tags will be here: zbsTagsForBulkActions

            // build typeahead html
            /* actually, a straight list makes more sense, until too many
            var tagTypeaheadHTML = '<div id="zbs-tag-typeahead-wrap" class="zbstypeaheadwrap zbsbtypeaheadfullwidth">';
                tagTypeaheadHTML += '<input class="typeahead" type="text" placeholder="Tag...">';
                tagTypeaheadHTML += '</div>';
            */

            // build tag list (toggle'able)
            var tagSelectList = '<div id="zbs-select-tags" class="ui segment">';
                if (typeof window.zbsTagsForBulkActions != "undefined" && window.zbsTagsForBulkActions.length > 0){

                    jQuery.each(window.zbsTagsForBulkActions,function(ind,tag){
                        tagSelectList += '<div class="zbs-select-tag ui label"><div class="ui checkbox"><input type="checkbox" data-tagid="' + tag.id + '" id="zbs-tag-' + tag.id + '" /><label for="zbs-tag-' + tag.id + '">' + tag.name + '</label></div></div>';
                    });

                } else {

                    tagSelectList += '<div class="ui message"><p>' + zeroBSCRMJS_listViewLang('notags') + '</p></div>'   
                
                }
                tagSelectList += '</div>';

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('whichtags'),
              html: '<div>' + zeroBSCRMJS_listViewLang('whichtagsremove') + '<br />' + tagSelectList + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('removethesetags'),
              //allowOutsideClick: false,
              onOpen: function(){

                    // bind checkboxes (this just adds nice colour effect, not that important)
                    jQuery('.zbs-select-tag input:checkbox').unbind('click').click(function(){
                        
                        jQuery('.zbs-select-tag input:checkbox').each(function(ind,ele){

                            if (jQuery(ele).attr('checked') == 'checked')
                                jQuery(ele).closest('.ui.label').addClass('blue');
                            else
                                jQuery(ele).closest('.ui.label').removeClass('blue');

                        });
                        

                    });


              }
            }).then(function (result) {

                // this check required from swal2 6.0+
                if (result.value){

                    // get settings
                    extraParams.tags = [];

                        // cycle through each tag input and if checked, add id
                        jQuery('.zbs-select-tag input:checkbox').each(function(ind,ele){

                                if (jQuery(ele).attr('checked') == 'checked') extraParams.tags.push(jQuery(ele).attr('data-tagid'));

                        });

                    // any tags?
                    if (extraParams.tags.length > 0){


                        // fire + will automatically refresh list view
                        zeroBSCRMJS_enactBulkAction('removetag',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                            // success ? SWAL?
                              swal(
                                zeroBSCRMJS_listViewLang('tagsremoved'),
                                zeroBSCRMJS_listViewLang('tagsremoveddesc'),
                                'success'
                              );

                        },function(r){

                            // fail ? SWAL?
                            swal(
                                zeroBSCRMJS_listViewLang('tagsnotremoved'),
                                zeroBSCRMJS_listViewLang('tagsnotremoveddesc'),
                                'warning'
                            );

                        }); 

                    } else {

                        // didn't select tags

                        swal(
                            zeroBSCRMJS_listViewLang('tagsnotselected'),
                            zeroBSCRMJS_listViewLang('tagsnotselecteddesc'),
                            'warning'
                        );

                    }

                }

            });

        }

        // bulk action - Merge
        function zeroBSCRMJS_listView_customer_bulkActionFire_merge(){

            // SWAL sanity check + which is dominant (main)?
            var extraParams = { dominant: -1 };

            // select (which cust)
            var selectedCusts = zeroBSCRMJS_listView_bulkActionsGetCheckedIncNames();
            var selectHTML = '<select id="zbsbulkactionmergemaster">';
                jQuery.each(selectedCusts,function(ind,ele){
                    selectHTML += '<option value="' + ele.id + '">' + ele.name + '</option>';
                });
                selectHTML += '</select>';

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('areyousure'),
              html: '<div>' + zeroBSCRMJS_listViewLang('areyousurethesemerge') + '<br /><label>' + zeroBSCRMJS_listViewLang('whichdominant') + '</label></div>' + selectHTML + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('yesmerge'),
              //allowOutsideClick: false,
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // get setting
                    extraParams.dominant = jQuery('#zbsbulkactionmergemaster').val();

                    // fire delete + will automatically refresh list view
                    zeroBSCRMJS_enactBulkAction('merge',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                        // success ? SWAL?
                          swal(
                            zeroBSCRMJS_listViewLang('merged'),
                            zeroBSCRMJS_listViewLang('contactsmerged'),
                            'success'
                          );

                    },function(r){

                        // fail ? SWAL?
                        swal(
                            zeroBSCRMJS_listViewLang('notmerged'),
                            zeroBSCRMJS_listViewLang('contactsnotmerged'),
                            'warning'
                        );

                    }); 

                }

            });



        }

        function zeroBSCRMJS_listView_customer_bulkActionFire_export(){ zeroBSCRMJS_listView_genericBulkAction_export('contact'); }
        function zeroBSCRMJS_listView_company_bulkActionFire_export(){ zeroBSCRMJS_listView_genericBulkAction_export('company'); }
        function zeroBSCRMJS_listView_quote_bulkActionFire_export(){ zeroBSCRMJS_listView_genericBulkAction_export('quote'); }
        function zeroBSCRMJS_listView_invoice_bulkActionFire_export(){ zeroBSCRMJS_listView_genericBulkAction_export('invoice'); }
        function zeroBSCRMJS_listView_transaction_bulkActionFire_export(){ zeroBSCRMJS_listView_genericBulkAction_export('transaction'); }


        // just a wrapper, this func is the same for all objects fired, so this shortens zeroBSCRMJS_listView_customer_bulkActionFire_export etc.
        function zeroBSCRMJS_listView_genericBulkAction_export(typestr){

            // directly post to export page
            var params = { 
                'sec': window.zbscrmjs_secToken,
                'objtype': window.zbsListViewSettings.objdbname,
                'ids':zeroBSCRMJS_listView_bulkActionsGetChecked() 
            };

            var typeparam = '';
            if (typeof typestr != "undefined" && typestr !== '') typeparam = '&zbstype=' + typestr;

            zeroBSCRMJS_genericPostData(window.zbsExportPostURL+typeparam,'post',params);

        }
/* ====================================================================================
============== / Bulk actions - Pre-checks - Customers ================================
==================================================================================== */



/* ====================================================================================
============== Field Drawing JS - GENERIC List View ===================================
    These are fallbacks for when there is no zeroBSCRMJS_listView_CUSTOMER_id e.g.
==================================================================================== */


        // Draw <td> for id
        function zeroBSCRMJS_listView_generic_id(dataLine){

            var id = '#' + dataLine['id'];
            if (typeof dataLine['zbsid'] != "undefined") id = '<a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '">#' + dataLine['zbsid'] + '</a>';


            return '<td' + zeroBSCRMJS_listView_tdAttr('id',dataLine,dataLine['id']) + '>' + id + '</td>';
        }

        // Draw <td> for status
        function zeroBSCRMJS_listView_generic_status(dataLine){

            var statusStr = '';
            if (typeof dataLine['status'] != "undefined") statusStr = dataLine['status'];

            return '<td' + zeroBSCRMJS_listView_tdAttr('status',dataLine,dataLine['status']) + '>' + statusStr + '</td>';
        }


        // Draw <td> for added
        function zeroBSCRMJS_listView_generic_added(dataLine){
            var date = ''; 

            // DAL3
            if (date == '' && typeof dataLine['created_date'] != "undefined") date = dataLine['created_date'];

            // DAL2
            if (date == '' && typeof dataLine['created'] != "undefined") date = dataLine['created'];

            // DAL1
            if (date == '' && typeof dataLine['added'] != "undefined") date = dataLine['added'];

            return '<td>' + date + '</td>';
        }
        // Draw <td> for name 
        function zeroBSCRMJS_listView_generic_name(dataLine){

            //this is the other "view" UI: zeroBSCRMJS_listView_viewURL
            var v = ''; if (typeof dataLine['name'] != "undefined") v = dataLine['name'];
            if (v == '' && typeof dataLine['title'] != "undefined") v = dataLine['title'];
            var td = '<td><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '">' + v + '</a></td>';

            return td;
        }
        // Draw <td> for name and avatar
        // https://semantic-ui.com/collections/table.html
        function zeroBSCRMJS_listView_generic_nameavatar(dataLine){


            // var editURL = zeroBSCRMJS_listView_editURL(dataLine['id']);

            var editURL = zeroBSCRMJS_listView_viewURL(dataLine['id']);
            var emailURL = zeroBSCRMJS_listView_emailURL_contact(dataLine['id']);


            var emailStr = ''; if (typeof dataLine['email'] != "undefined" && dataLine['email'] != '') emailStr = '<a href="' + emailURL + '">' + dataLine['email'] + '</a>';
            var imgStr = ''; if (typeof dataLine['avatar'] != "undefined" && dataLine['avatar'] != '') imgStr = '<img src="' + dataLine['avatar'] + '" class="ui mini rounded image">';//imgStr = '<a href="' + editURL + '"><img src="' + dataLine['avatar'] + '" class="ui mini rounded image"></a>';
            var nameStr = ''; if (typeof dataLine['name'] != "undefined" && dataLine['name'] != '') nameStr = dataLine['name'];
            if (nameStr == '' && typeof dataLine['email'] != "undefined" && dataLine['email'] != '') nameStr = dataLine['email'];


            var td = '<td class="name-and-avatar-list"><h4 class="ui image header">';
            td += imgStr;
            td += '<div class="content"><a href="' + editURL + '">' + nameStr + '</a><div class="sub header">' + emailStr + '</div>';
            td += '</div></h4></td>';

            return td;
        }

        // Draw <td> for company 
        function zeroBSCRMJS_listView_generic_company(dataLine){

            var td = '<td></td>';

            if (typeof dataLine['company'] != "undefined" && typeof dataLine['company']['id'] != "undefined"){

                //this is the other "view" UI: zeroBSCRMJS_listView_viewURL
                var td = '<td><a href="' + zeroBSCRMJS_listView_viewURL_company(dataLine['company']['id']) + '">' + dataLine['company']['name'] + '</a></td>';

            }

            return td;
        }


        // Generic simplified customer line
        // as of v2.92 also allows [company] (e.g. transaction can have either or)
        function zeroBSCRMJS_listView_generic_customer(dataLine){

            //console.log(dataLine);

            if (typeof dataLine['customer'] != "undefined" && dataLine['customer'] != null && dataLine['customer'] != false && typeof dataLine['customer']['id'] != "undefined"){

                var custLine = dataLine['customer'];

                var editURL = zeroBSCRMJS_listView_viewURL_customer(dataLine['customer']['id']);
                var emailURL = zeroBSCRMJS_listView_emailURL_contact(dataLine['customer']['id']);

                var emailStr = ''; //if (typeof custLine['email'] != "undefined" && custLine['email'] != '') emailStr = '<a href="mailto:' + custLine['email'] + '" target="_blank">' + custLine['email'] + '</a>';
                var imgStr = ''; if (typeof custLine['avatar'] != "undefined" && custLine['avatar'] != '') imgStr = '<img src="' + custLine['avatar'] + '" class="ui mini rounded image">';//imgStr = '<a href="' + editURL + '"><img src="' + dataLine['avatar'] + '" class="ui mini rounded image"></a>';
                var nameStr = ''; if (typeof custLine['fullname'] != "undefined" && custLine['fullname'] != '') nameStr = custLine['fullname'];
                if (nameStr == '' && typeof custLine['email'] != "undefined" && custLine['email'] != '') nameStr = custLine['email'];


                var td = '<td class="name-and-avatar-list"><h4 class="ui image header">';
                td += imgStr;
                td += '<div class="content"><a href="' + editURL + '">' + nameStr + '</a><div class="sub header">' + emailStr + '</div>';
                td += '</div></h4></td>';


            } else if (typeof dataLine['company'] != "undefined" && dataLine['company'] != null && typeof dataLine['company']['id'] != "undefined"){

                var coLine = dataLine['company'];

                var editURL = zeroBSCRMJS_listView_viewURL_company(dataLine['company']['id']);

                var nameStr = ''; if (typeof coLine['fullname'] != "undefined" && coLine['fullname'] != '') nameStr = coLine['fullname'];


                var td = '<td class="name-and-avatar-list"><h4 class="ui header">';
                td += '<i class="building icon"></i>';
                td += '<div class="content"><a href="' + editURL + '">' + nameStr + '</a></div></h4></td>';


            } else {

                td = '<td>' + zeroBSCRMJS_listViewLang('nocustomer') + '</td>';
            }

            return td;
        }

        // Generic simplified customer email
        function zeroBSCRMJS_listView_generic_customeremail(dataLine){

            if (typeof dataLine['customer'] != "undefined" && typeof dataLine['customer']['id'] != "undefined"){

                var custLine = dataLine['customer'];

                var editURL = zeroBSCRMJS_listView_viewURL_customer(dataLine['customer']['id']);
                var emailURL = zeroBSCRMJS_listView_emailURL_contact(dataLine['customer']['id']);

                var emailStr = ''; if (typeof custLine['email'] != "undefined" && custLine['email'] != '') emailStr = '<a href="' + emailURL + '">' + custLine['email'] + '</a>';
               

                var td = '<td>' + emailStr + '</td>';


            } else {

                td = '<td>' + zeroBSCRMJS_listViewLang('nocustomer') + '</td>';
            }

            return td;
        }

        // Draw <td> for assigned to
        function zeroBSCRMJS_listView_generic_assigned(dataLine){

            var assignedToStr = ''; 

            // v2
            if  (
                    typeof dataLine['owner'] != "undefined" &&
                    typeof dataLine['owner']['OBJ'] != "undefined" && 
                    typeof dataLine['owner']['OBJ']['data'] != "undefined" && 
                    typeof dataLine['owner']['OBJ']['data']['display_name'] != "undefined") assignedToStr += dataLine['owner']['OBJ']['data']['display_name'];
            
            // v3
            if  (
                    typeof dataLine['owner'] != "undefined" &&
                    typeof dataLine['owner']['OBJ'] != "undefined" && 
                    typeof dataLine['owner']['OBJ']['display_name'] != "undefined") assignedToStr += dataLine['owner']['OBJ']['display_name'];

            return '<td>' + assignedToStr + '</td>';
        }


        // specifies 'assigned to' of customer/company owner of this obj
        // e.g. inv/trans against contact 123, this'll show 'owner' to 123
        function zeroBSCRMJS_listView_generic_assignedobj(dataLine){

            var assignedToStr = ''; 

            if (typeof dataLine['customer'] != "undefined" && dataLine['customer'] != null && dataLine['customer'] != false && typeof dataLine['customer']['owner'] != "undefined"){

                if  (
                    typeof dataLine['customer']['owner'] != "undefined" &&
                    typeof dataLine['customer']['owner']['OBJ'] != "undefined" && 
                    typeof dataLine['customer']['owner']['OBJ']['data'] != "undefined" && 
                    typeof dataLine['customer']['owner']['OBJ']['data']['display_name'] != "undefined"
                    ) assignedToStr += dataLine['customer']['owner']['OBJ']['data']['display_name'];
            

            } else if (typeof dataLine['company'] != "undefined" && dataLine['company'] != null && typeof dataLine['company']['owner'] != "undefined"){

                if  (
                    typeof dataLine['company']['owner'] != "undefined" &&
                    typeof dataLine['company']['owner']['OBJ'] != "undefined" && 
                    typeof dataLine['company']['owner']['OBJ']['data'] != "undefined" && 
                    typeof dataLine['company']['owner']['OBJ']['data']['display_name'] != "undefined"
                    ) assignedToStr += dataLine['company']['owner']['OBJ']['data']['display_name'];
            
            }

            return '<td>' + assignedToStr + '</td>';
        }

        // Draw <td> for latestlog
        function zeroBSCRMJS_listView_generic_latestlog(dataLine){

            var lastLogStr = ''; 
            if  (
                    typeof dataLine['lastlog'] != "undefined" &&
                    typeof dataLine['lastlog'] != "undefined" && 
                    typeof dataLine['lastlog']['type'] != "undefined" && 
                    typeof dataLine['lastlog']['shortdesc'] != "undefined") lastLogStr += zeroBSCRMJS_logTypeStr(dataLine['lastlog']['type']) + ': ' + dataLine['lastlog']['shortdesc'];
            
   
            return '<td>' + lastLogStr + '</td>';
        }
        // Draw <td> for lastcontafctec
        function zeroBSCRMJS_listView_generic_lastcontacted(dataLine){

            var lastLogStr = ''; 
            var lastUTS = -1;

            
            if  (typeof dataLine['lastcontactlog'] != "undefined" &&
                 typeof dataLine['lastcontactlog']['created'] != "undefined") {
                
                // DAL 1
                lastUTS = dataLine['lastcontactlog']['created'];

            } else if ( typeof dataLine['lastcontacted'] != "undefined" ) {

                // DAL2 
                lastUTS = dataLine['lastcontacted'];

            }

            //  format
            if (lastUTS !== -1){

                    var start = moment(lastUTS);
                    var end = moment();
                    var daysAgo = end.diff(start, "days");
                    if (daysAgo == 0){
                        lastLogStr = zeroBSCRMJS_listViewLang('today');
                    } else if (daysAgo > 0) {

                        if (daysAgo == 1) 
                            lastLogStr = zeroBSCRMJS_listViewLang('yesterday');
                        else
                            lastLogStr = daysAgo + ' ' + zeroBSCRMJS_listViewLang('daysago');
                         
                    }

            }
            
            if (lastLogStr == '') lastLogStr = zeroBSCRMJS_listViewLang('notcontacted');
   
            return '<td>' + lastLogStr + '</td>';
        }
        // Draw <td> for tagged
        function zeroBSCRMJS_listView_generic_tagged(dataLine){

            var tagStr = '';
            if  (typeof dataLine['tags'] != "undefined" && dataLine['tags'].length > 0) jQuery.each(dataLine['tags'],function(ind,ele){

                //if (tagStr != '') tagStr += ', ';

                //https://codex.wordpress.org/Function_Reference/wp_get_post_tags
                // ui choices: https://semantic-ui.com/elements/label.html
                // ui tag
                // ui basic
                // ui horizontal
                if (typeof ele['term_id'] != "undefined")
                    tagStr += '<a href="' + window.zbsTagSkipLinkPrefix + ele['term_id'] + '" title="View all with this tag" class="ui small basic label teal">' + ele['name'] + '</a>';
                else if (typeof ele['id'] != "undefined")
                    // DAL2
                    tagStr += '<a href="' + window.zbsTagSkipLinkPrefix + ele['id'] + '" title="View all with this tag" class="ui small basic label teal">' + ele['name'] + '</a>';


            });

            return '<td>' + tagStr + '</td>';
        }
        // Draw <td> for  edit link
        function zeroBSCRMJS_listView_generic_editlink(dataLine){
            // return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon edit"></i>' + window.zbs_lang.zbs_edit + '</a></td>';

            return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon pencil"></i>' + zeroBSCRMJS_listViewLang('zbs_edit') + '</a></td>';

        }
        // Draw <td> for  edit link
        function zeroBSCRMJS_listView_generic_editdirectlink(dataLine){
            // return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon edit"></i>' + window.zbs_lang.zbs_edit + '</a></td>';

            return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon pencil"></i>' + zeroBSCRMJS_listViewLang('zbs_edit') + '</a></td>';

        }
        // Draw <td> for  edit link
        function zeroBSCRMJS_listView_generic_editdirectlink(dataLine){
            // return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon edit"></i>' + window.zbs_lang.zbs_edit + '</a></td>';

            return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon eye"></i>' + window.zbs_lang.zbs_view + '</a></td>';

        }
        // Draw <td> for  edit link
        function zeroBSCRMJS_listView_generic_viewlink(dataLine){
            // return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon edit"></i>' + window.zbs_lang.zbs_edit + '</a></td>';

            return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '" class="ui basic button"><i class="icon eye"></i>' + window.zbs_lang.zbs_view + '</a></td>';

        }
        // Draw <td> for telephone <ahref
        function zeroBSCRMJS_listView_generic_phonelink(dataLine){


            // worktel hometel mobtel

            var phoneLinkStr = '';
            if (typeof dataLine['hometel'] != 'undefined' && dataLine['hometel'] != ''){

                phoneLinkStr += '<a href="' + zeroBSCRMJS_telURLFromNo(dataLine['hometel']) + '" class="ui tiny basic button"><i class="icon call"></i> ' + dataLine['hometel'] + ' (' + zeroBSCRMJS_listViewLang('telhome') + ')</a>';

            }
            if (typeof dataLine['worktel'] != 'undefined' && dataLine['worktel'] != ''){

                phoneLinkStr += '<a href="' + zeroBSCRMJS_telURLFromNo(dataLine['worktel']) + '" class="ui tiny basic button"><i class="icon call"></i> ' + dataLine['worktel'] + ' (' + zeroBSCRMJS_listViewLang('telwork') + ')</a>';

            }
            if (typeof dataLine['mobtel'] != 'undefined' && dataLine['mobtel'] != ''){

                phoneLinkStr += '<a href="' + zeroBSCRMJS_telURLFromNo(dataLine['mobtel']) + '" class="ui tiny basic button"><i class="icon call"></i> ' + dataLine['mobtel'] + ' (' + zeroBSCRMJS_listViewLang('telmob') + ')</a>';

            }

            return '<td class="center aligned">' + phoneLinkStr + '</td>';
        }

        


/* ====================================================================================
============== / Field Drawing JS - GENERIC List View ================================
==================================================================================== */




/* ====================================================================================
============== Field Drawing JS - Customer List View ==================================
==================================================================================== */

        // ICONS playing up on semantic Select, so cut out for init.

        // bulk action titles
        function zeroBSCRMJS_listView_customer_bulkActionTitle_delete(){

            //return zeroBSCRMJS_listViewIco('deletecontacts') + ' ' + zeroBSCRMJS_listViewLang('deletecontacts');
            return zeroBSCRMJS_listViewLang('deletecontacts');

        }
        function zeroBSCRMJS_listView_customer_bulkActionTitle_addtag(){

            //return zeroBSCRMJS_listViewIco('addtags') + ' ' + zeroBSCRMJS_listViewLang('addtags');
            return zeroBSCRMJS_listViewLang('addtags');

        }
        function zeroBSCRMJS_listView_customer_bulkActionTitle_removetag(){

            //return zeroBSCRMJS_listViewIco('removetags') + ' ' + zeroBSCRMJS_listViewLang('removetags');
            return zeroBSCRMJS_listViewLang('removetags');

        }
        function zeroBSCRMJS_listView_customer_bulkActionTitle_merge(){

            //return zeroBSCRMJS_listViewIco('merge') + ' ' + zeroBSCRMJS_listViewLang('merge');
            return zeroBSCRMJS_listViewLang('merge');

        }
        function zeroBSCRMJS_listView_customer_bulkActionTitle_export(){

            //return zeroBSCRMJS_listViewIco('merge') + ' ' + zeroBSCRMJS_listViewLang('merge');
            return zeroBSCRMJS_listViewLang('export');

        }


        // Draw <td> for id
        function zeroBSCRMJS_listView_customer_id(dataLine){

            return '<td>#' + dataLine['id'] + '</td>';
        }


        // Second Address Fields
        function zeroBSCRMJS_listView_customer_secaddr1(dataLine){

            // catch various version endpoints
            var v = ''; 
            if (typeof dataLine['secaddr_addr1'] != "undefined") v = dataLine['secaddr_addr1'];
            if (v == '' && typeof dataLine['secaddr1'] != "undefined") v = dataLine['secaddr1'];

            return '<td>' + v + '</td>';
        }

        function zeroBSCRMJS_listView_customer_secaddr2(dataLine){

            // catch various version endpoints
            var v = ''; 
            if (typeof dataLine['secaddr_addr2'] != "undefined") v = dataLine['secaddr_addr2'];
            if (v == '' && typeof dataLine['secaddr2'] != "undefined") v = dataLine['secaddr2'];

            return '<td>' + v + '</td>';
        }

        function zeroBSCRMJS_listView_customer_seccity(dataLine){

            // catch various version endpoints
            var v = ''; 
            if (typeof dataLine['secaddr_city'] != "undefined") v = dataLine['secaddr_city'];
            if (v == '' && typeof dataLine['seccity'] != "undefined") v = dataLine['seccity'];

            return '<td>' + v + '</td>';
        }

        function zeroBSCRMJS_listView_customer_seccounty(dataLine){

            // catch various version endpoints
            var v = ''; 
            if (typeof dataLine['secaddr_county'] != "undefined") v = dataLine['secaddr_county'];
            if (v == '' && typeof dataLine['seccounty'] != "undefined") v = dataLine['seccounty'];

            return '<td>' + v + '</td>';
        }

        function zeroBSCRMJS_listView_customer_secpostcode(dataLine){

            // catch various version endpoints
            var v = ''; 
            if (typeof dataLine['secaddr_postcode'] != "undefined") v = dataLine['secaddr_postcode'];
            if (v == '' && typeof dataLine['secpostcode'] != "undefined") v = dataLine['secpostcode'];

            return '<td>' + v + '</td>';
        }

        function zeroBSCRMJS_listView_customer_seccountry(dataLine){

            // catch various version endpoints
            var v = ''; 
            if (typeof dataLine['secaddr_country'] != "undefined") v = dataLine['secaddr_country'];
            if (v == '' && typeof dataLine['seccountry'] != "undefined") v = dataLine['seccountry'];

            return '<td>' + v + '</td>';
        }


        // Draw <td> for quotes
        function zeroBSCRMJS_listView_customer_quotecount(dataLine){


            // temp, show count
            var quoteStr = '';
            if (typeof dataLine['quotes'] != "undefined") quoteStr = dataLine['quotes'].length;

            return '<td>' + quoteStr + '</td>';

        }
        
        // Draw <td> for invoices
        function zeroBSCRMJS_listView_customer_invoicecount(dataLine){

            // temp, show count
            var invStr = '';
            if (typeof dataLine['invoices'] != "undefined") invStr = dataLine['invoices'].length;

            return '<td>' + invStr + '</td>';
        }

        // Draw <td> for transactions
        function zeroBSCRMJS_listView_customer_transactioncount(dataLine){

            // temp, show count
            var transStr = '';
            if (typeof dataLine['transactions'] != "undefined") transStr = dataLine['transactions'].length;

            return '<td>' + transStr + '</td>';
        }

        // Draw <td> for quotes
        function zeroBSCRMJS_listView_customer_quotetotal(dataLine){


            // temp, show count
            var quoteStr = '';
            // not req. as php formats if (typeof dataLine['quotestotal'] != "undefined") quoteStr = zeroBSCRMJS_formatCurrency(dataLine['quotestotal']); //window.zbJS_curr + dataLine['quotestotal'];
            if (typeof dataLine['quotestotal'] != "undefined") quoteStr = dataLine['quotestotal']; //window.zbJS_curr + dataLine['quotestotal'];

            return '<td>' + quoteStr + '</td>';

        }
        
        // Draw <td> for invoices
        function zeroBSCRMJS_listView_customer_invoicetotal(dataLine){

            // temp, show count
            var invStr = '';
            // not req. as php formats if (typeof dataLine['invoicestotal'] != "undefined") invStr = zeroBSCRMJS_formatCurrency(dataLine['invoicestotal']); // window.zbJS_curr + dataLine['invoicestotal']
            if (typeof dataLine['invoicestotal'] != "undefined") invStr = dataLine['invoicestotal'];

            return '<td>' + invStr + '</td>';
        }

        // Draw <td> for transactions
        function zeroBSCRMJS_listView_customer_transactiontotal(dataLine){

            // temp, show count
            var transStr = '';

            // not req. as php formats if (typeof dataLine['transactionstotal'] != "undefined") transStr = zeroBSCRMJS_formatCurrency(dataLine['transactionstotal']); //  window.zbJS_curr + dataLine['transtotal']
            if (typeof dataLine['transactionstotal'] != "undefined") transStr = dataLine['transactionstotal'];

            // v3.0
            if (typeof dataLine['transactions_total'] != "undefined") transStr = dataLine['transactions_total'];

            return '<td>' + transStr + '</td>';
        }

        // Draw <td> for added
        function zeroBSCRMJS_listView_customer_added(dataLine){

            var date = '';

            // DAL3
            if (date == '' && typeof dataLine['created_date'] != "undefined") date = dataLine['created_date'];

            // DAL2
            if (date == '' && typeof dataLine['created'] != "undefined") date = dataLine['created'];

            // DAL1
            if (date == '' && typeof dataLine['added'] != "undefined") date = dataLine['added'];

            return '<td data-zbs-created-uts="' + dataLine['createduts'] + '">' + date + '</td>';
        }
        // Draw <td> for total value ... just format these in PHP and draw normally...
        function zeroBSCRMJS_listView_customer_totalvalue(dataLine){
            //return '<td>' + window.zbJS_curr + dataLine['totalvalue'] + '</td>';
            // not req as php formats return '<td>' + zeroBSCRMJS_formatCurrency(dataLine['totalvalue']) + '</td>';
            var v = ''; if (typeof dataLine['totalvalue'] != "undefined") v = dataLine['totalvalue'];
            return '<td>' + v + '</td>';
        }
        // Draw <td> for name 
        function zeroBSCRMJS_listView_customer_name(dataLine){

            //this is the other "view" UI: zeroBSCRMJS_listView_viewURL
            var v = ''; if (typeof dataLine['name'] != "undefined") v = dataLine['name'];
            var td = '<td><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '">' + v + '</a></td>';

            return td;
        }
        // Draw <td> for name 
        function zeroBSCRMJS_listView_customer_fname(dataLine){

            //console.log(dataLine);
            var td = '<td>' + dataLine['fname'] + '</td>';

            return td;
        }
        // Draw <td> for name 
        function zeroBSCRMJS_listView_customer_lname(dataLine){

            var td = '<td>' + dataLine['lname'] + '</td>';

            return td;
        }
        // Draw <td> for name and avatar
        // https://semantic-ui.com/collections/table.html
        function zeroBSCRMJS_listView_customer_nameavatar(dataLine){


            // var editURL = zeroBSCRMJS_listView_editURL(dataLine['id']);

            var editURL = zeroBSCRMJS_listView_viewURL(dataLine['id']);
            var emailURL = zeroBSCRMJS_listView_emailURL_contact(dataLine['id']);

            var emailStr = ''; if (typeof dataLine['email'] != "undefined" && dataLine['email'] != '') emailStr = '<a href="' + emailURL  + '">' + dataLine['email'] + '</a>';
            var imgStr = ''; if (typeof dataLine['avatar'] != "undefined" && dataLine['avatar'] != '') imgStr = '<img src="' + dataLine['avatar'] + '" class="ui mini rounded image">';//imgStr = '<a href="' + editURL + '"><img src="' + dataLine['avatar'] + '" class="ui mini rounded image"></a>';
            var nameStr = ''; if (typeof dataLine['name'] != "undefined" && dataLine['name'] != '') nameStr = dataLine['name'];
            if (nameStr == '' && typeof dataLine['email'] != "undefined" && dataLine['email'] != '') nameStr = dataLine['email'];


            var td = '<td class="name-and-avatar-list"><h4 class="ui image header">';
            td += imgStr;
            td += '<div class="content"><a href="' + editURL + '">' + nameStr + '</a><div class="sub header">' + emailStr + '</div>';
            td += '</div></h4></td>';

            return td;
        }

        // Draw <td> for assigned to
        function zeroBSCRMJS_listView_customer_assigned(dataLine){

            var assignedToStr = ''; var val = -1;
            if (typeof dataLine['owner'] != "undefined" && typeof dataLine['owner']['ID'] != "undefined") val = dataLine['owner']['ID'];

            // v2
            if  (
                    typeof dataLine['owner'] != "undefined" &&
                    typeof dataLine['owner']['OBJ'] != "undefined" && 
                    typeof dataLine['owner']['OBJ']['data'] != "undefined" && 
                    typeof dataLine['owner']['OBJ']['data']['display_name'] != "undefined") assignedToStr += dataLine['owner']['OBJ']['data']['display_name'];
                        
            // v3
            if  (
                    typeof dataLine['owner'] != "undefined" &&
                    typeof dataLine['owner']['OBJ'] != "undefined" && 
                    typeof dataLine['owner']['OBJ']['display_name'] != "undefined") assignedToStr += dataLine['owner']['OBJ']['display_name'];            

            return '<td' + zeroBSCRMJS_listView_tdAttr('assigned',dataLine,val) + '>' + assignedToStr + '</td>';
        }
        // Draw <td> for latestlog
        function zeroBSCRMJS_listView_customer_latestlog(dataLine){

            var lastLogStr = ''; 

            if  (
                    typeof dataLine['lastlog'] != "undefined" &&
                    typeof dataLine['lastlog']['type'] != "undefined" && 
                    typeof dataLine['lastlog']['shortdesc'] != "undefined") lastLogStr += zeroBSCRMJS_logTypeStr(dataLine['lastlog']['type']) + ': ' + dataLine['lastlog']['shortdesc'];
            
   
            return '<td>' + lastLogStr + '</td>';
        }
        // Draw <td> for tagged
        function zeroBSCRMJS_listView_customer_tagged(dataLine){

            var tagStr = '';
            if  (typeof dataLine['tags'] != "undefined" && dataLine['tags'].length > 0) jQuery.each(dataLine['tags'],function(ind,ele){

                //if (tagStr != '') tagStr += ', ';

                // DAL1
                //https://codex.wordpress.org/Function_Reference/wp_get_post_tags
                // ui choices: https://semantic-ui.com/elements/label.html
                // ui tag
                // ui basic
                // ui horizontal
                if (typeof ele['term_id'] != "undefined")
                    tagStr += '<a href="' + window.zbsTagSkipLinkPrefix + ele['term_id'] + '" title="View all with this tag" class="ui small basic label teal">' + ele['name'] + '</a>';
                else if (typeof ele['id'] != "undefined")
                    // DAL2
                    tagStr += '<a href="' + window.zbsTagSkipLinkPrefix + ele['id'] + '" title="View all with this tag" class="ui small basic label teal">' + ele['name'] + '</a>';

            });

            return '<td>' + tagStr + '</td>';
        }
        // Draw <td> for  edit link (For some reason VIEW is called editlink #techdebt)
        function zeroBSCRMJS_listView_customer_editlink(dataLine){
            // return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon edit"></i>' + window.zbs_lang.zbs_edit + '</a></td>';

            return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '" class="ui basic button"><i class="icon eye"></i>' + window.zbs_lang.zbs_view + '</a></td>';

        }
        // Draw <td> for  edit link
        function zeroBSCRMJS_listView_customer_editdirectlink(dataLine){
            // return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon edit"></i>' + window.zbs_lang.zbs_edit + '</a></td>';

            return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon eye"></i>' + window.zbs_lang.zbs_edit + '</a></td>';

        }

        // Draw <td> for has quote
        function zeroBSCRMJS_listView_customer_hasquote(dataLine){

            var hasQuote = '';
            if (typeof dataLine['quotes'] != 'undefined' && dataLine['quotes'] != 0 && dataLine['quotes'] != '0'){

                hasQuote = '<i class="large green checkmark icon"></i>';

            }

            return '<td class="center aligned">' + hasQuote + '</td>';
        }
        // Draw <td> for has inv
        function zeroBSCRMJS_listView_customer_hasinvoice(dataLine){

            var hasInvoice = '';
            if (typeof dataLine['invoices'] != 'undefined' && dataLine['invoices'] != 0 && dataLine['invoices'] != '0'){

                hasInvoice = '<i class="large green checkmark icon"></i>';

            }

            return '<td class="center aligned">' + hasInvoice + '</td>';
        }
        // Draw <td> for telephone <ahref
        function zeroBSCRMJS_listView_customer_phonelink(dataLine){


            // worktel hometel mobtel

            var phoneLinkStr = '';
            if (typeof dataLine['hometel'] != 'undefined' && dataLine['hometel'] != ''){

                phoneLinkStr += '<a href="' + zeroBSCRMJS_telURLFromNo(dataLine['hometel']) + '" class="ui tiny basic button"><i class="icon call"></i> ' + dataLine['hometel'] + ' (' + zeroBSCRMJS_listViewLang('telhome') + ')</a>';

            }
            if (typeof dataLine['worktel'] != 'undefined' && dataLine['worktel'] != ''){

                phoneLinkStr += '<a href="' + zeroBSCRMJS_telURLFromNo(dataLine['worktel']) + '" class="ui tiny basic button"><i class="icon call"></i> ' + dataLine['worktel'] + ' (' + zeroBSCRMJS_listViewLang('telwork') + ')</a>';

            }
            if (typeof dataLine['mobtel'] != 'undefined' && dataLine['mobtel'] != ''){

                phoneLinkStr += '<a href="' + zeroBSCRMJS_telURLFromNo(dataLine['mobtel']) + '" class="ui tiny basic button"><i class="icon call"></i> ' + dataLine['mobtel'] + ' (' + zeroBSCRMJS_listViewLang('telmob') + ')</a>';

            }

            return '<td class="center aligned">' + phoneLinkStr + '</td>';
        }

        


/* ====================================================================================
============== / Field Drawing JS - Customer List View ================================
==================================================================================== */


function zbsIdentify(){    
    return '#####zbshash#####';
}


/* ====================================================================================
============================ Bulk actions - Segments ==================================
==================================================================================== */


        // bulk action titles
        function zeroBSCRMJS_listView_segment_bulkActionTitle_delete(){

            //return zeroBSCRMJS_listViewIco('deletecontacts') + ' ' + zeroBSCRMJS_listViewLang('deletecontacts');
            return zeroBSCRMJS_listViewLang('deletesegments');

        }

        function zeroBSCRMJS_listView_segment_bulkActionFire_delete(){

            // SWAL sanity check + leave orphans?
            var extraParams = {};

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('areyousure'),
              html: '<div>' + zeroBSCRMJS_listViewLang('areyousurethese') + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Yes, delete!',
              //allowOutsideClick: false,
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // fire delete + will automatically refresh list view
                    zeroBSCRMJS_enactBulkAction('delete',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                        // success ? SWAL?
                          swal(
                            zeroBSCRMJS_listViewLang('deleted'),
                            zeroBSCRMJS_listViewLang('segmentsdeleted'),
                            'success'
                          );

                    },function(r){

                        // fail ? SWAL?
                        swal(
                            zeroBSCRMJS_listViewLang('notdeleted'),
                            zeroBSCRMJS_listViewLang('notsegmentsdeleted'),
                            'warning'
                        );

                    }); 

                }

            });



        }

/*  ===================================================================================
========================== / Bulk actions - Segments ==================================
==================================================================================== */

/* ====================================================================================
=============== Field Drawing JS - Segment List View ==================================
==================================================================================== */


        // Draw <td> for id
        function zeroBSCRMJS_listView_segment_id(dataLine){

            return '<td>#' + dataLine['id'] + '</td>';
        }
        // Draw <td> for added
        function zeroBSCRMJS_listView_segment_added(dataLine){

            var date = '';

            // DAL3
            if (date == '' && typeof dataLine['created_date'] != "undefined") date = dataLine['created_date'];

            // DAL2
            if (date == '' && typeof dataLine['createddate'] != "undefined") date = dataLine['createddate'];

            return '<td>' + date + '</td>';
        }
        // Draw <td> for name 
        function zeroBSCRMJS_listView_segment_name(dataLine){

            var td = '<td><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '">' + dataLine['name'] + '</a></td>';

            return td;
        }
        // Draw <td> for audience count 
        function zeroBSCRMJS_listView_segment_audiencecount(dataLine){

            var compStr = window.zbsListViewLangLabels.notCompiled;
            if (typeof dataLine['compilecount'] != "undefined"){

                compStr = '<span class="ui label teal" title="' + window.zbsListViewLangLabels.lastCompiled + ' ' + dataLine['lastcompileddate'] + '">' + dataLine['compilecount'] + '</span>';

                // if using segment quickfilters, can view them!
                if (typeof window.zbsSegmentViewStemURL != "undefined" && typeof dataLine['slug'] != "undefined"){
                    compStr = '<div class="ui left labeled button" title="' + window.zbsListViewLangLabels.lastCompiled + ' ' + dataLine['lastcompileddate'] + '"><a class="ui basic right pointing label">' + dataLine['compilecount'] + '</a><a href="' + window.zbsSegmentViewStemURL + dataLine['slug'] + '" class="ui button"><i class="list icon"></i> ' + window.zbsListViewLangLabels.view + '</a></div>';
                }
            }

            var td = '<td class="center aligned">' + compStr + '</td>';

            return td;
        }
        // Draw <td> for  edit link etc.
        function zeroBSCRMJS_listView_segment_action(dataLine){

            var buttons = '<a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon edit"></i> ' + zeroBSCRMJS_listViewLang('edit','Edit') + '</a>';

            // for now show to everyone
            //nvm use bulk buttons += '<button data-segment-id="' + dataLine['id'] + '" class="ui basic button zbs-delete-segment"><i class="icon trash"></i> ' + window.zbsListViewLangLabels.deletestr + '</a>';

            return '<td class="center aligned">' + buttons +'</td>';
        }


/* ====================================================================================
=============== / Field Drawing JS - Segment List View ================================
==================================================================================== */


/* ====================================================================================
================  Field Drawing JS - Quotetemplate List View ==========================
==================================================================================== */
        
        // Draw <td> for id
        function zeroBSCRMJS_listView_quotetemplate_id(dataLine){

            var id = '#' + dataLine['id'];
            if (typeof dataLine['zbsid'] != "undefined") id = '<a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '">#' + dataLine['zbsid'] + '</a>';

            return '<td' + zeroBSCRMJS_listView_tdAttr('id',dataLine,dataLine['id']) + '>' + id + '</td>';

        }
        
        // Draw <td> for title
        function zeroBSCRMJS_listView_quotetemplate_title(dataLine){

            var defStr = '';
            if (typeof dataLine['default'] != "undefined"){
                var d = parseInt(dataLine['default']);
                if (d > 0) defStr = '<br />(<i>' + zeroBSCRMJS_listViewLang('defaulttemplate','Default Template') + '</i>)';
            }
            return '<td' + zeroBSCRMJS_listView_tdAttr('title',dataLine,dataLine['title']) + '><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '">' + dataLine['title'] + '</a>' + defStr + '</td>';

        }
        
        // Draw <td> for  edit link etc.
        function zeroBSCRMJS_listView_quotetemplate_action(dataLine){

            var buttons = '<a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon edit"></i> ' + zeroBSCRMJS_listViewLang('edit','Edit') + '</a>';

            return '<td class="center aligned">' + buttons +'</td>';

        }


/* ====================================================================================
=============== / Field Drawing JS - Quotetemplate List View ==========================
==================================================================================== */

/* ====================================================================================
=============== Field Drawing JS - Company List View ==================================
==================================================================================== */

        // Draw <td> for name 
        function zeroBSCRMJS_listView_company_coname(dataLine){ return zeroBSCRMJS_listView_company_name(dataLine); }
        function zeroBSCRMJS_listView_company_name(dataLine){

            //this is the other "view" UI: zeroBSCRMJS_listView_viewURL
            var v = ''; if (typeof dataLine['name'] != "undefined") v = dataLine['name'];
            var td = '<td><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '">' + v + '</a></td>';

            return td;
        }

        function zeroBSCRMJS_listView_company_nameavatar(dataLine){


            var editURL = zeroBSCRMJS_listView_editURL(dataLine['id']);
            var emailURL = zeroBSCRMJS_listView_emailURL_contact(dataLine['id']);

            var emailStr = ''; if (typeof dataLine['email'] != "undefined" && dataLine['email'] != '') emailStr = '<a href="' + emailURL + '">' + dataLine['email'] + '</a>';
            var imgStr = ''; if (typeof dataLine['avatar'] != "undefined" && dataLine['avatar'] != '') imgStr = '<img src="' + dataLine['avatar'] + '" class="ui mini rounded image">';//imgStr = '<a href="' + editURL + '"><img src="' + dataLine['avatar'] + '" class="ui mini rounded image"></a>';
            var nameStr = ''; if (typeof dataLine['coname'] != "undefined" && dataLine['coname'] != '') nameStr = dataLine['coname'];
            if (nameStr == '' && typeof dataLine['name'] != "undefined") nameStr = dataLine['name']; // DAL3
            if (nameStr == '' && typeof dataLine['email'] != "undefined" && dataLine['email'] != '') nameStr = dataLine['email'];


            var td = '<td class="name-and-avatar-list"><h4 class="ui image header">';
            td += imgStr;
            td += '<div class="content"><a href="' + editURL + '">' + nameStr + '</a><div class="sub header">' + emailStr + '</div>';
            td += '</div></h4></td>';

            return td;
        }


        // Second Address Fields
        function zeroBSCRMJS_listView_company_secaddr1(dataLine){

            // catch various version endpoints
            var v = ''; 
            if (typeof dataLine['secaddr_addr1'] != "undefined") v = dataLine['secaddr_addr1'];
            if (v == '' && typeof dataLine['secaddr1'] != "undefined") v = dataLine['secaddr1'];

            return '<td>' + v + '</td>';
        }

        function zeroBSCRMJS_listView_company_secaddr2(dataLine){

            // catch various version endpoints
            var v = ''; 
            if (typeof dataLine['secaddr_addr2'] != "undefined") v = dataLine['secaddr_addr2'];
            if (v == '' && typeof dataLine['secaddr2'] != "undefined") v = dataLine['secaddr2'];

            return '<td>' + v + '</td>';
        }

        function zeroBSCRMJS_listView_company_seccity(dataLine){

            // catch various version endpoints
            var v = ''; 
            if (typeof dataLine['secaddr_city'] != "undefined") v = dataLine['secaddr_city'];
            if (v == '' && typeof dataLine['seccity'] != "undefined") v = dataLine['seccity'];

            return '<td>' + v + '</td>';
        }

        function zeroBSCRMJS_listView_company_seccounty(dataLine){

            // catch various version endpoints
            var v = ''; 
            if (typeof dataLine['secaddr_county'] != "undefined") v = dataLine['secaddr_county'];
            if (v == '' && typeof dataLine['seccounty'] != "undefined") v = dataLine['seccounty'];

            return '<td>' + v + '</td>';
        }

        function zeroBSCRMJS_listView_company_secpostcode(dataLine){

            // catch various version endpoints
            var v = ''; 
            if (typeof dataLine['secaddr_postcode'] != "undefined") v = dataLine['secaddr_postcode'];
            if (v == '' && typeof dataLine['secpostcode'] != "undefined") v = dataLine['secpostcode'];

            return '<td>' + v + '</td>';
        }

        function zeroBSCRMJS_listView_company_seccountry(dataLine){

            // catch various version endpoints
            var v = ''; 
            if (typeof dataLine['secaddr_country'] != "undefined") v = dataLine['secaddr_country'];
            if (v == '' && typeof dataLine['seccountry'] != "undefined") v = dataLine['seccountry'];

            return '<td>' + v + '</td>';
        }

        // Draw <td> for transactions
        function zeroBSCRMJS_listView_company_transactioncount(dataLine){

            // temp, show count
            var transStr = '';
            if (typeof dataLine['transactions'] != "undefined") transStr = dataLine['transactions'].length;

            return '<td>' + transStr + '</td>';
        }
        // Draw <td> for transactions
        function zeroBSCRMJS_listView_company_transactiontotal(dataLine){

            // temp, show count
            var transStr = '';

            // not req. as php formats if (typeof dataLine['transactionstotal'] != "undefined") transStr = zeroBSCRMJS_formatCurrency(dataLine['transactionstotal']); //  window.zbJS_curr + dataLine['transtotal']
            if (typeof dataLine['transactionstotal'] != "undefined") transStr = dataLine['transactionstotal'];

            return '<td>' + transStr + '</td>';
        }


        // Draw <td> for telephone <ahref
        function zeroBSCRMJS_listView_company_phonelink(dataLine){


            // worktel hometel mobtel

            var phoneLinkStr = '';
            if (typeof dataLine['maintel'] != 'undefined' && dataLine['maintel'] != ''){

                phoneLinkStr += '<a href="' + zeroBSCRMJS_telURLFromNo(dataLine['maintel']) + '" class="ui tiny basic button"><i class="icon call"></i> ' + dataLine['maintel'] + '</a>';

            }
            if (typeof dataLine['sectel'] != 'undefined' && dataLine['sectel'] != ''){

                phoneLinkStr += '<a href="' + zeroBSCRMJS_telURLFromNo(dataLine['sectel']) + '" class="ui tiny basic button"><i class="icon call"></i> ' + dataLine['sectel'] + '</a>';

            }

            return '<td class="center aligned">' + phoneLinkStr + '</td>';
        }
/* ====================================================================================
=============== / Field Drawing JS - Company List View ================================
==================================================================================== */


/* ====================================================================================
==============   Bulk actions - Titles - Company ======================================
==================================================================================== */
        // ICONS playing up on semantic Select, so cut out for init.

        // bulk action titles
        function zeroBSCRMJS_listView_company_bulkActionTitle_delete(){

            return zeroBSCRMJS_listViewLang('deletecompanys');

        }
        function zeroBSCRMJS_listView_company_bulkActionTitle_addtag(){

            return zeroBSCRMJS_listViewLang('addtags');

        }
        function zeroBSCRMJS_listView_company_bulkActionTitle_removetag(){

            return zeroBSCRMJS_listViewLang('removetags');

        }
        function zeroBSCRMJS_listView_company_bulkActionTitle_export(){

            //return zeroBSCRMJS_listViewIco('merge') + ' ' + zeroBSCRMJS_listViewLang('merge');
            return zeroBSCRMJS_listViewLang('export');

        }
/* ====================================================================================
============== / Bulk actions - Titles - Company ======================================
==================================================================================== */


/* ====================================================================================
============== Bulk actions - Pre-checks - Company ====================================
==================================================================================== */

        function zeroBSCRMJS_listView_company_bulkActionFire_delete(){

            // SWAL sanity check + leave orphans?
            var extraParams = { leaveorphans: true };

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('areyousure'),
              html: '<div>' + zeroBSCRMJS_listViewLang('areyousurethese') + '<br /><label>' + zeroBSCRMJS_listViewLang('andthese') + '</label></div><select id="zbsbulkactiondeleteleaveorphans"><option value="true" selected="selected">' + zeroBSCRMJS_listViewLang('noleave') + '</option><option value="false">' + zeroBSCRMJS_listViewLang('yesthose') + '</option></select></div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('yesdelete'),
              //allowOutsideClick: false,
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // get setting
                    extraParams.leaveorphans = jQuery('#zbsbulkactiondeleteleaveorphans').val();

                    // fire delete + will automatically refresh list view
                    zeroBSCRMJS_enactBulkAction('delete',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                        // success ? SWAL?
                          swal(
                            zeroBSCRMJS_listViewLang('deleted'),
                            zeroBSCRMJS_listViewLang('companysdeleted'),
                            'success'
                          );

                    },function(r){

                        // fail ? SWAL?
                        swal(
                            zeroBSCRMJS_listViewLang('notdeleted'),
                            zeroBSCRMJS_listViewLang('notcompanysdeleted'),
                            'warning'
                        );

                    }); 

                }

            });



        }
        function zeroBSCRMJS_listView_company_bulkActionFire_addtag(){

           
            // SWAL which tag(s)?
            var extraParams = { tags: [] };

            // avail tags will be here: zbsTagsForBulkActions

            // build typeahead html
            /* actually, a straight list makes more sense, until too many
            var tagTypeaheadHTML = '<div id="zbs-tag-typeahead-wrap" class="zbstypeaheadwrap zbsbtypeaheadfullwidth">';
                tagTypeaheadHTML += '<input class="typeahead" type="text" placeholder="Tag...">';
                tagTypeaheadHTML += '</div>';
            */

            // build tag list (toggle'able)
            var tagSelectList = '<div id="zbs-select-tags" class="ui segment">';
                if (typeof window.zbsTagsForBulkActions != "undefined" && window.zbsTagsForBulkActions.length > 0){

                    jQuery.each(window.zbsTagsForBulkActions,function(ind,tag){
                        tagSelectList += '<div class="zbs-select-tag ui label"><div class="ui checkbox"><input type="checkbox" data-tagid="' + tag.id + '" id="zbs-tag-' + tag.id + '" /><label for="zbs-tag-' + tag.id + '">' + tag.name + '</label></div></div>';
                    });

                } else {

                    tagSelectList += '<div class="ui message"><p>' + zeroBSCRMJS_listViewLang('notags') + '</p></div>'   
                
                }
                tagSelectList += '</div>';

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('whichtags'),
              html: '<div>' + zeroBSCRMJS_listViewLang('whichtagsadd') + '<br />' + tagSelectList + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('addthesetags'),
              //allowOutsideClick: false,
              onOpen: function(){

                    // bind checkboxes (this just adds nice colour effect, not that important)
                    jQuery('.zbs-select-tag input:checkbox').unbind('click').click(function(){
                        
                        jQuery('.zbs-select-tag input:checkbox').each(function(ind,ele){

                            if (jQuery(ele).attr('checked') == 'checked')
                                jQuery(ele).closest('.ui.label').addClass('blue');
                            else
                                jQuery(ele).closest('.ui.label').removeClass('blue');

                        });
                        

                    });


              }
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // get settings
                    extraParams.tags = [];

                        // cycle through each tag input and if checked, add id
                        jQuery('.zbs-select-tag input:checkbox').each(function(ind,ele){

                                if (jQuery(ele).attr('checked') == 'checked') extraParams.tags.push(jQuery(ele).attr('data-tagid'));

                        });

                    // any tags?
                    if (extraParams.tags.length > 0){


                        // fire + will automatically refresh list view
                        zeroBSCRMJS_enactBulkAction('addtag',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                            // success ? SWAL?
                              swal(
                                zeroBSCRMJS_listViewLang('tagsadded'),
                                zeroBSCRMJS_listViewLang('tagsaddeddesc'),
                                'success'
                              );

                        },function(r){

                            // fail ? SWAL?
                            swal(
                                zeroBSCRMJS_listViewLang('tagsnotadded'),
                                zeroBSCRMJS_listViewLang('tagsnotaddeddesc'),
                                'warning'
                            );

                        }); 

                    } else {

                        // didn't select tags

                        swal(
                            zeroBSCRMJS_listViewLang('tagsnotselected'),
                            zeroBSCRMJS_listViewLang('tagsnotselecteddesc'),
                            'warning'
                        );

                    }

                }

            });


                    


        }
        function zeroBSCRMJS_listView_company_bulkActionFire_removetag(){

           
            // SWAL which tag(s)?
            var extraParams = { tags: [] };

            // avail tags will be here: zbsTagsForBulkActions

            // build typeahead html
            /* actually, a straight list makes more sense, until too many
            var tagTypeaheadHTML = '<div id="zbs-tag-typeahead-wrap" class="zbstypeaheadwrap zbsbtypeaheadfullwidth">';
                tagTypeaheadHTML += '<input class="typeahead" type="text" placeholder="Tag...">';
                tagTypeaheadHTML += '</div>';
            */

            // build tag list (toggle'able)
            var tagSelectList = '<div id="zbs-select-tags" class="ui segment">';
                if (typeof window.zbsTagsForBulkActions != "undefined" && window.zbsTagsForBulkActions.length > 0){

                    jQuery.each(window.zbsTagsForBulkActions,function(ind,tag){
                        tagSelectList += '<div class="zbs-select-tag ui label"><div class="ui checkbox"><input type="checkbox" data-tagid="' + tag.id + '" id="zbs-tag-' + tag.id + '" /><label for="zbs-tag-' + tag.id + '">' + tag.name + '</label></div></div>';
                    });

                } else {

                    tagSelectList += '<div class="ui message"><p>' + zeroBSCRMJS_listViewLang('notags') + '</p></div>'   
                
                }
                tagSelectList += '</div>';

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('whichtags'),
              html: '<div>' + zeroBSCRMJS_listViewLang('whichtagsremove') + '<br />' + tagSelectList + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('removethesetags'),
              //allowOutsideClick: false,
              onOpen: function(){

                    // bind checkboxes (this just adds nice colour effect, not that important)
                    jQuery('.zbs-select-tag input:checkbox').unbind('click').click(function(){
                        
                        jQuery('.zbs-select-tag input:checkbox').each(function(ind,ele){

                            if (jQuery(ele).attr('checked') == 'checked')
                                jQuery(ele).closest('.ui.label').addClass('blue');
                            else
                                jQuery(ele).closest('.ui.label').removeClass('blue');

                        });
                        

                    });


              }
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // get settings
                    extraParams.tags = [];

                        // cycle through each tag input and if checked, add id
                        jQuery('.zbs-select-tag input:checkbox').each(function(ind,ele){

                                if (jQuery(ele).attr('checked') == 'checked') extraParams.tags.push(jQuery(ele).attr('data-tagid'));

                        });

                    // any tags?
                    if (extraParams.tags.length > 0){


                        // fire + will automatically refresh list view
                        zeroBSCRMJS_enactBulkAction('removetag',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                            // success ? SWAL?
                              swal(
                                zeroBSCRMJS_listViewLang('tagsremoved'),
                                zeroBSCRMJS_listViewLang('tagsremoveddesc'),
                                'success'
                              );

                        },function(r){

                            // fail ? SWAL?
                            swal(
                                zeroBSCRMJS_listViewLang('tagsnotremoved'),
                                zeroBSCRMJS_listViewLang('tagsnotremoveddesc'),
                                'warning'
                            );

                        }); 

                    } else {

                        // didn't select tags

                        swal(
                            zeroBSCRMJS_listViewLang('tagsnotselected'),
                            zeroBSCRMJS_listViewLang('tagsnotselecteddesc'),
                            'warning'
                        );

                    }

                }

            });

        }
/* ====================================================================================
============== / Bulk actions - Pre-checks - Company ==================================
==================================================================================== */


/* ====================================================================================
=============== Field Drawing JS - Quote List View ==================================
==================================================================================== */
    
    /* Now covered by generic_customer

        function zeroBSCRMJS_listView_quote_customer(dataLine){

            console.log('line',dataLine);

            if (typeof dataLine['customer'] != "undefined" && typeof dataLine['customer']['meta'] != "undefined"){

                var custLine = dataLine['customer']['meta'];

                var editURL = zeroBSCRMJS_listView_viewURL_customer(dataLine['customer']['id']);

                var emailStr = ''; //if (typeof custLine['email'] != "undefined" && custLine['email'] != '') emailStr = '<a href="mailto:' + custLine['email'] + '" target="_blank">' + custLine['email'] + '</a>';
                var imgStr = ''; if (typeof custLine['avatar'] != "undefined" && custLine['avatar'] != '') imgStr = '<img src="' + custLine['avatar'] + '" class="ui mini rounded image">';//imgStr = '<a href="' + editURL + '"><img src="' + dataLine['avatar'] + '" class="ui mini rounded image"></a>';
                var nameStr = ''; if (typeof custLine['fullname'] != "undefined" && custLine['fullname'] != '') nameStr = custLine['fullname'];
                if (nameStr == '' && typeof custLine['email'] != "undefined" && custLine['email'] != '') nameStr = custLine['email'];


                var td = '<td class="name-and-avatar-list"><h4 class="ui image header">';
                td += imgStr;
                td += '<div class="content"><a href="' + editURL + '">' + nameStr + '</a><div class="sub header">' + emailStr + '</div>';
                td += '</div></h4></td>';

            } else {

                td = '';
            }

            return td;
        }

    */
        // Draw <td> for quote title
        function zeroBSCRMJS_listView_quote_title(dataLine){

            //this is the other "view" UI: zeroBSCRMJS_listView_viewURL
            var v = ''; if (typeof dataLine['name'] != "undefined") v = dataLine['name'];
            if (v == '' && typeof dataLine['title'] != "undefined") v = dataLine['title']; // DAL3
            if (v == '' &&  typeof dataLine['id_override'] != "undefined") v = '#' + dataLine['id_override']; // DAL3 fallback
            var td = '<td><strong><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '">' + v + '</a></strong></td>';

            return td;
        }
        // Draw <td> for value
        function zeroBSCRMJS_listView_quote_value(dataLine){
            //return '<td>' + window.zbJS_curr + dataLine['totalvalue'] + '</td>';
            // not req. as php formats return '<td>' + zeroBSCRMJS_formatCurrency(dataLine['val']) + '</td>';

            var value = ''; 

            // DAL3
            if (value == '' && typeof dataLine['value'] != "undefined") value = dataLine['value'];

            // <DAL3
            if (value == '' && typeof dataLine['val'] != "undefined") value = dataLine['val'];


            return '<td>' + value + '</td>';
        }
        // Draw <td> for status
        function zeroBSCRMJS_listView_quote_status(dataLine){
            //return '<td>' + window.zbJS_curr + dataLine['totalvalue'] + '</td>';
            var stat = ''; if (typeof dataLine['status'] != "undefined") stat = dataLine['status'];
            return '<td>' + stat + '</td>';
        }
/* ====================================================================================
=============== / Field Drawing JS - Quote List View ================================
==================================================================================== */

/* ====================================================================================
==============   Bulk actions - Titles - Quote ======================================
==================================================================================== */
        // ICONS playing up on semantic Select, so cut out for init.

        // bulk action titles
        function zeroBSCRMJS_listView_quote_bulkActionTitle_markaccepted(){

            return zeroBSCRMJS_listViewLang('markaccepted');

        }
        function zeroBSCRMJS_listView_quote_bulkActionTitle_markunaccepted(){

            return zeroBSCRMJS_listViewLang('markunaccepted');

        }
        function zeroBSCRMJS_listView_quote_bulkActionTitle_delete(){

            return zeroBSCRMJS_listViewLang('delete');

        }
        function zeroBSCRMJS_listView_quote_bulkActionTitle_export(){

            return zeroBSCRMJS_listViewLang('export');

        }
/* ====================================================================================
============== / Bulk actions - Titles - Quote ======================================
==================================================================================== */


/* ====================================================================================
============== Bulk actions - Pre-checks - Quote ====================================
==================================================================================== */

        function zeroBSCRMJS_listView_quote_bulkActionFire_markaccepted(){

            // SWAL sanity check
            var extraParams = {};

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('acceptareyousure'),
              html: '<div>' + zeroBSCRMJS_listViewLang('acceptareyousurethese') + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('acceptyesdoit'),
              //allowOutsideClick: false,
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // get setting
                    extraParams.leaveorphans = jQuery('#zbsbulkactiondeleteleaveorphans').val();

                    // fire delete + will automatically refresh list view
                    zeroBSCRMJS_enactBulkAction('markaccepted',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                        // success ? SWAL?
                          swal(
                            zeroBSCRMJS_listViewLang('acceptdeleted'),
                            zeroBSCRMJS_listViewLang('acceptcompanysdeleted'),
                            'success'
                          );

                    },function(r){

                        // fail ? SWAL?
                        swal(
                            zeroBSCRMJS_listViewLang('acceptnotdeleted'),
                            zeroBSCRMJS_listViewLang('acceptnotcompanysdeleted'),
                            'warning'
                        );

                    }); 

                }

            });



        }
        function zeroBSCRMJS_listView_quote_bulkActionFire_markunaccepted(){

            // SWAL sanity check
            var extraParams = {};

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('unacceptareyousure'),
              html: '<div>' + zeroBSCRMJS_listViewLang('unacceptareyousurethese') + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('unacceptyesdoit'),
              //allowOutsideClick: false,
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // get setting
                    extraParams.leaveorphans = jQuery('#zbsbulkactiondeleteleaveorphans').val();

                    // fire delete + will automatically refresh list view
                    zeroBSCRMJS_enactBulkAction('markunaccepted',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                        // success ? SWAL?
                          swal(
                            zeroBSCRMJS_listViewLang('unacceptdeleted'),
                            zeroBSCRMJS_listViewLang('unacceptcompanysdeleted'),
                            'success'
                          );

                    },function(r){

                        // fail ? SWAL?
                        swal(
                            zeroBSCRMJS_listViewLang('unacceptnotdeleted'),
                            zeroBSCRMJS_listViewLang('unacceptnotcompanysdeleted'),
                            'warning'
                        );

                    }); 

                }

            });



        }
        function zeroBSCRMJS_listView_quote_bulkActionFire_delete(){

            // SWAL sanity check
            var extraParams = {};

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('areyousure'),
              html: '<div>' + zeroBSCRMJS_listViewLang('areyousurethese') + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('yesdoit'),
              //allowOutsideClick: false,
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // fire delete + will automatically refresh list view
                    zeroBSCRMJS_enactBulkAction('delete',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                        // success ? SWAL?
                          swal(
                            zeroBSCRMJS_listViewLang('deleted'),
                            zeroBSCRMJS_listViewLang('quotesdeleted'),
                            'success'
                          );

                    },function(r){

                        // fail ? SWAL?
                        swal(
                            zeroBSCRMJS_listViewLang('notdeleted'),
                            zeroBSCRMJS_listViewLang('notquotesdeleted'),
                            'warning'
                        );

                    }); 

                }

            });



        }
/* ====================================================================================
============== / Bulk actions - Pre-checks - Quote ==================================
==================================================================================== */


/* ====================================================================================
==============   Bulk actions - Titles - Invoice ======================================
==================================================================================== */
        // ICONS playing up on semantic Select, so cut out for init.
        function zeroBSCRMJS_listView_invoice_bulkActionTitle_delete(){

            return zeroBSCRMJS_listViewLang('delete');

        }
        function zeroBSCRMJS_listView_invoice_bulkActionTitle_export(){

            return zeroBSCRMJS_listViewLang('export');

        }
/* ====================================================================================
============== / Bulk actions - Titles - Invoice ======================================
==================================================================================== */



/* ====================================================================================
=============== Field Drawing JS - Invoice List View ==================================
==================================================================================== */
            
        // Draw <td> for inv no
        function zeroBSCRMJS_listView_invoice_no(dataLine){

            var id = ''; if (typeof dataLine['zbsid'] != "undefined") id = dataLine['zbsid'];

            var td = '<td>' + id + '</td>';

            return td;
        }
        // Draw <td> for inv date
        function zeroBSCRMJS_listView_invoice_date(dataLine){

            var v = ''; if (typeof dataLine['meta'] != "undefined" && typeof dataLine['meta']['date'] != "undefined") v = dataLine['meta']['date'];
            if (v == '' && typeof dataLine['date_date'] != "undefined") v = dataLine['date_date']; // DAL3

            var td = '<td>' + v + '</td>';

            return td;
        }
        // Draw <td> for inv due (WH added to ajax 2.95+)
        function zeroBSCRMJS_listView_invoice_due(dataLine){

            var v = ''; if (typeof dataLine['duedate'] != "undefined") v = dataLine['duedate'];
            if (v == '' && typeof dataLine['due_date_date'] != "undefined") v = dataLine['due_date_date']; // DAL3

            var td = '<td>' + v + '</td>';

            return td;
        }

        // Draw <td> for ref
        function zeroBSCRMJS_listView_invoice_ref(dataLine){

            var v = ''; if (typeof dataLine['title'] != "undefined") v = dataLine['title'];
            if (v == '' && typeof dataLine['id_override'] != "undefined") v = dataLine['id_override']; // DAL3
            if (v == '' && typeof dataLine['id'] != "undefined") v = dataLine['id']; // DAL3 fallback

            var td = '<td><strong><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '">' + v + '</a></strong></td>';

            return td;
        }
        // Draw <td> for value
        function zeroBSCRMJS_listView_invoice_val(dataLine){
            // not req. as php formats return '<td>' + zeroBSCRMJS_formatCurrency(dataLine['value']) + '</td>';

            var value = ''; 

            // DAL3
            if (value == '' && typeof dataLine['total'] != "undefined") value = dataLine['total'];

            // DAL2
            if (value == '' && typeof dataLine['value'] != "undefined") value = dataLine['value'];


            return '<td>' + value + '</td>';
        }
        // Draw <td> for value
        function zeroBSCRMJS_listView_invoice_value(dataLine){
            // not req. as php formats return '<td>' + zeroBSCRMJS_formatCurrency(dataLine['value']) + '</td>';

            var value = ''; 

            // DAL3
            if (value == '' && typeof dataLine['total'] != "undefined") value = dataLine['total'];

            // DAL2
            if (value == '' && typeof dataLine['value'] != "undefined") value = dataLine['value'];


            return '<td>' + value + '</td>';
        }
        // Draw <td> for status
        function zeroBSCRMJS_listView_invoice_status(dataLine){
            var stat = ''; if (typeof dataLine['status'] != "undefined") stat = dataLine['status'];
            var color = '';
            switch(stat){
                case zeroBSCRMJS_listViewLang('statusdraft','Draft'):
                    color = 'grey';
                break;

                case zeroBSCRMJS_listViewLang('statusunpaid','Unpaid'):
                    color = 'orange';
                break;

                case zeroBSCRMJS_listViewLang('statuspaid','Paid'):
                    color = 'green';
                break;

                case zeroBSCRMJS_listViewLang('statusoverdue','Overdue'):
                    color = 'red';
                break;

            }
            stat = '<span class="ui label ' + color + '">' + stat + "</span>";
                        
            return '<td>' + stat + '</td>';
        }


/* ====================================================================================
=============== / Field Drawing JS - Invoice List View ================================
==================================================================================== */

/* ====================================================================================
==============   Bulk actions - Titles - Invoice ======================================
==================================================================================== */
        function zeroBSCRMJS_listView_invoice_bulkActionTitle_changestatus(){

            return zeroBSCRMJS_listViewLang('changestatus');

        }
        function zeroBSCRMJS_listView_invoice_bulkActionTitle_delete(){

            return zeroBSCRMJS_listViewLang('delete');

        }
/* ====================================================================================
============== / Bulk actions - Titles - Invoice ======================================
==================================================================================== */


/* ====================================================================================
============== Bulk actions - Pre-checks - Invoice ====================================
==================================================================================== */

        function zeroBSCRMJS_listView_invoice_bulkActionFire_changestatus(){

            // SWAL sanity check
            var extraParams = {};

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('statusareyousure'),
              html: '<div>' + zeroBSCRMJS_listViewLang('statusareyousurethese') + '</div><select id="zbsbulkactionnewstatus"><option value="Draft" selected="selected">' + zeroBSCRMJS_listViewLang('statusdraft') + '</option><option value="Unpaid">' + zeroBSCRMJS_listViewLang('statusunpaid') + '</option><option value="Paid">' + zeroBSCRMJS_listViewLang('statuspaid') + '</option><option value="Overdue">' + zeroBSCRMJS_listViewLang('statusoverdue') + '</option></select></div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('statusyesdoit'),
              //allowOutsideClick: false, 
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){
                
                    // get setting
                    extraParams.newstatus = jQuery('#zbsbulkactionnewstatus').val();

                    // fire delete + will automatically refresh list view
                    zeroBSCRMJS_enactBulkAction('changestatus',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                        // success ? SWAL?
                          swal(
                            zeroBSCRMJS_listViewLang('statusupdated'),
                            zeroBSCRMJS_listViewLang('statuscompanysupdated'),
                            'success'
                          );

                    },function(r){

                        // fail ? SWAL?
                        swal(
                            zeroBSCRMJS_listViewLang('statusnotupdated'),
                            zeroBSCRMJS_listViewLang('statusnotcompanysupdated'),
                            'warning'
                        );

                    }); 

                }

            });



        }
        function zeroBSCRMJS_listView_invoice_bulkActionFire_delete(){

            // SWAL sanity check
            var extraParams = {};

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('areyousure'),
              html: '<div>' + zeroBSCRMJS_listViewLang('areyousurethese') + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('yesdoit'),
              //allowOutsideClick: false,
            }).then(function (result) {


                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // fire delete + will automatically refresh list view
                    zeroBSCRMJS_enactBulkAction('delete',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                        // success ? SWAL?
                          swal(
                            zeroBSCRMJS_listViewLang('deleted'),
                            zeroBSCRMJS_listViewLang('invoicesdeleted'),
                            'success'
                          );

                    },function(r){

                        // fail ? SWAL?
                        swal(
                            zeroBSCRMJS_listViewLang('notdeleted'),
                            zeroBSCRMJS_listViewLang('notinvoicesdeleted'),
                            'warning'
                        );

                    }); 

                }

            });



        }
/* ====================================================================================
============== / Bulk actions - Pre-checks - Invoice ==================================
==================================================================================== */


/* ====================================================================================
=============== Field Drawing JS - Transacts List View ================================
==================================================================================== */

        function zeroBSCRMJS_listView_transaction_id(dataLine){

            var v = '';
            if (v == '' && typeof dataLine['id_override'] != "undefined") v = dataLine['id_override']; // DAL3
            if (v == '' && typeof dataLine['id'] != "undefined") v = dataLine['id']; // fallback
            if (typeof dataLine['id'] != "undefined") v = '<a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '">#' + v + '</a>';


            return '<td><strong>' + v + '</strong></td>';
        }

        function zeroBSCRMJS_listView_transaction_item(dataLine){

            return zeroBSCRMJS_listView_transaction_title(dataLine);
            
        }

        function zeroBSCRMJS_listView_transaction_title(dataLine){

            var v = '';
            if (v == '' && typeof dataLine['title'] != "undefined") v = dataLine['title']; // DAL3
            if (v == '' && typeof dataLine['item'] != "undefined") v = dataLine['item']; // <DAL3

            return '<td><strong>' + v + '</strong></td>';
        }

        function zeroBSCRMJS_listView_transaction_orderid(dataLine){

            var v = '';
            if (v == '' && typeof dataLine['ref'] != "undefined") v = dataLine['ref']; // DAL3
            if (v == '' && typeof dataLine['orderid'] != "undefined") v = dataLine['orderid']; // <DAL3
            return '<td><strong>' + v + '</strong></td>';
        }
        
        // Draw <td> for value
        function zeroBSCRMJS_listView_transaction_total(dataLine){
            // not req. as php formats return '<td>' + zeroBSCRMJS_formatCurrency(dataLine['total']) + '</td>';
            return '<td>' + dataLine['total'] + '</td>';
        }
        
        // Draw <td> for status
        function zeroBSCRMJS_listView_transaction_status(dataLine){
            var stat = ''; if (typeof dataLine['status'] != "undefined") stat = dataLine['status'];
            var color = '';
            switch(stat){
                case zeroBSCRMJS_listViewLang('trans_status_cancelled','Cancelled'):
                    color = 'pink';
                break;

                case zeroBSCRMJS_listViewLang('trans_status_hold','Hold'):
                    color = 'orange';
                break;

                case zeroBSCRMJS_listViewLang('trans_status_pending','Pending'):
                    color = 'teal';
                break;

                case zeroBSCRMJS_listViewLang('trans_status_processing','Processing'):
                    color = 'teal';
                break;

                case zeroBSCRMJS_listViewLang('trans_status_refunded','Refunded'):
                    color = 'orange';
                break;

                case zeroBSCRMJS_listViewLang('trans_status_failed','Failed'):
                    color = 'red';
                break;

                case zeroBSCRMJS_listViewLang('trans_status_completed','Completed'):
                    color = 'positive';
                break;

                case zeroBSCRMJS_listViewLang('trans_status_succeeded','Succeeded'):
                    color = 'positive';
                break;

            }
            stat = '<span class="ui label ' + color + '">' + stat + "</span>";
                        
            return '<td>' + stat + '</td>';
        }

        function zeroBSCRMJS_listView_transaction_date_completed(dataLine){

            var v = ''; if (v == '' && typeof dataLine['date_completed_date'] != "undefined" && dataLine['date_completed_date'] !== false) v = dataLine['date_completed_date']; // DAL3

            return '<td>' + v + '</td>';
            
        }

/* ====================================================================================
=============== / Field Drawing JS - Transacts List View ==============================
==================================================================================== */


/* ====================================================================================
==============   Bulk actions - Titles - Transactions =================================
==================================================================================== */
        // ICONS playing up on semantic Select, so cut out for init.

        // bulk action titles
        function zeroBSCRMJS_listView_transaction_bulkActionTitle_delete(){

            return zeroBSCRMJS_listViewLang('delete');

        }
        function zeroBSCRMJS_listView_transaction_bulkActionTitle_addtag(){

            return zeroBSCRMJS_listViewLang('addtags');

        }
        function zeroBSCRMJS_listView_transaction_bulkActionTitle_removetag(){

            return zeroBSCRMJS_listViewLang('removetags');

        }
        function zeroBSCRMJS_listView_transaction_bulkActionTitle_export(){

            return zeroBSCRMJS_listViewLang('export');

        }
/* ====================================================================================
============== / Bulk actions - Titles - Transactions =================================
==================================================================================== */


/* ====================================================================================
============== Bulk actions - Pre-checks - Transactions =============================
==================================================================================== */

        function zeroBSCRMJS_listView_transaction_bulkActionFire_delete(){

            // SWAL sanity check + leave orphans?
            var extraParams = { leaveorphans: true };

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('areyousure'),
              html: '<div>' + zeroBSCRMJS_listViewLang('areyousurethese') + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('yesdelete'),
              //allowOutsideClick: false,
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // fire delete + will automatically refresh list view
                    zeroBSCRMJS_enactBulkAction('delete',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                        // success ? SWAL?
                          swal(
                            zeroBSCRMJS_listViewLang('deleted'),
                            zeroBSCRMJS_listViewLang('transactionsdeleted'),
                            'success'
                          );

                    },function(r){

                        // fail ? SWAL?
                        swal(
                            zeroBSCRMJS_listViewLang('notdeleted'),
                            zeroBSCRMJS_listViewLang('nottransactionsdeleted'),
                            'warning'
                        );

                    }); 

                }

            });



        }
        function zeroBSCRMJS_listView_transaction_bulkActionFire_addtag(){

           
            // SWAL which tag(s)?
            var extraParams = { tags: [] };

            // avail tags will be here: zbsTagsForBulkActions

            // build typeahead html
            /* actually, a straight list makes more sense, until too many
            var tagTypeaheadHTML = '<div id="zbs-tag-typeahead-wrap" class="zbstypeaheadwrap zbsbtypeaheadfullwidth">';
                tagTypeaheadHTML += '<input class="typeahead" type="text" placeholder="Tag...">';
                tagTypeaheadHTML += '</div>';
            */

            // build tag list (toggle'able)
            var tagSelectList = '<div id="zbs-select-tags" class="ui segment">';
                if (typeof window.zbsTagsForBulkActions != "undefined" && window.zbsTagsForBulkActions.length > 0){

                    jQuery.each(window.zbsTagsForBulkActions,function(ind,tag){
                        tagSelectList += '<div class="zbs-select-tag ui label"><div class="ui checkbox"><input type="checkbox" data-tagid="' + tag.id + '" id="zbs-tag-' + tag.id + '" /><label for="zbs-tag-' + tag.id + '">' + tag.name + '</label></div></div>';
                    });

                } else {

                    tagSelectList += '<div class="ui message"><p>' + zeroBSCRMJS_listViewLang('notags') + '</p></div>'   
                
                }
                tagSelectList += '</div>';

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('whichtags'),
              html: '<div>' + zeroBSCRMJS_listViewLang('whichtagsadd') + '<br />' + tagSelectList + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('addthesetags'),
              //allowOutsideClick: false,
              onOpen: function(){

                    // bind checkboxes (this just adds nice colour effect, not that important)
                    jQuery('.zbs-select-tag input:checkbox').unbind('click').click(function(){
                        
                        jQuery('.zbs-select-tag input:checkbox').each(function(ind,ele){

                            if (jQuery(ele).attr('checked') == 'checked')
                                jQuery(ele).closest('.ui.label').addClass('blue');
                            else
                                jQuery(ele).closest('.ui.label').removeClass('blue');

                        });
                        

                    });


              }
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // get settings
                    extraParams.tags = [];

                        // cycle through each tag input and if checked, add id
                        jQuery('.zbs-select-tag input:checkbox').each(function(ind,ele){

                                if (jQuery(ele).attr('checked') == 'checked') extraParams.tags.push(jQuery(ele).attr('data-tagid'));

                        });

                    // any tags?
                    if (extraParams.tags.length > 0){


                        // fire + will automatically refresh list view
                        zeroBSCRMJS_enactBulkAction('addtag',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                            // success ? SWAL?
                              swal(
                                zeroBSCRMJS_listViewLang('tagsadded'),
                                zeroBSCRMJS_listViewLang('tagsaddeddesc'),
                                'success'
                              );

                        },function(r){

                            // fail ? SWAL?
                            swal(
                                zeroBSCRMJS_listViewLang('tagsnotadded'),
                                zeroBSCRMJS_listViewLang('tagsnotaddeddesc'),
                                'warning'
                            );

                        }); 

                    } else {

                        // didn't select tags

                        swal(
                            zeroBSCRMJS_listViewLang('tagsnotselected'),
                            zeroBSCRMJS_listViewLang('tagsnotselecteddesc'),
                            'warning'
                        );

                    }

                }

            });


                    


        }
        function zeroBSCRMJS_listView_transaction_bulkActionFire_removetag(){

           
            // SWAL which tag(s)?
            var extraParams = { tags: [] };

            // avail tags will be here: zbsTagsForBulkActions

            // build typeahead html
            /* actually, a straight list makes more sense, until too many
            var tagTypeaheadHTML = '<div id="zbs-tag-typeahead-wrap" class="zbstypeaheadwrap zbsbtypeaheadfullwidth">';
                tagTypeaheadHTML += '<input class="typeahead" type="text" placeholder="Tag...">';
                tagTypeaheadHTML += '</div>';
            */

            // build tag list (toggle'able)
            var tagSelectList = '<div id="zbs-select-tags" class="ui segment">';
                if (typeof window.zbsTagsForBulkActions != "undefined" && window.zbsTagsForBulkActions.length > 0){

                    jQuery.each(window.zbsTagsForBulkActions,function(ind,tag){
                        tagSelectList += '<div class="zbs-select-tag ui label"><div class="ui checkbox"><input type="checkbox" data-tagid="' + tag.id + '" id="zbs-tag-' + tag.id + '" /><label for="zbs-tag-' + tag.id + '">' + tag.name + '</label></div></div>';
                    });

                } else {

                    tagSelectList += '<div class="ui message"><p>' + zeroBSCRMJS_listViewLang('notags') + '</p></div>'   
                
                }
                tagSelectList += '</div>';

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('whichtags'),
              html: '<div>' + zeroBSCRMJS_listViewLang('whichtagsremove') + '<br />' + tagSelectList + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('removethesetags'),
              //allowOutsideClick: false,
              onOpen: function(){

                    // bind checkboxes (this just adds nice colour effect, not that important)
                    jQuery('.zbs-select-tag input:checkbox').unbind('click').click(function(){
                        
                        jQuery('.zbs-select-tag input:checkbox').each(function(ind,ele){

                            if (jQuery(ele).attr('checked') == 'checked')
                                jQuery(ele).closest('.ui.label').addClass('blue');
                            else
                                jQuery(ele).closest('.ui.label').removeClass('blue');

                        });
                        

                    });


              }
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // get settings
                    extraParams.tags = [];

                        // cycle through each tag input and if checked, add id
                        jQuery('.zbs-select-tag input:checkbox').each(function(ind,ele){

                                if (jQuery(ele).attr('checked') == 'checked') extraParams.tags.push(jQuery(ele).attr('data-tagid'));

                        });

                    // any tags?
                    if (extraParams.tags.length > 0){


                        // fire + will automatically refresh list view
                        zeroBSCRMJS_enactBulkAction('removetag',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                            // success ? SWAL?
                              swal(
                                zeroBSCRMJS_listViewLang('tagsremoved'),
                                zeroBSCRMJS_listViewLang('tagsremoveddesc'),
                                'success'
                              );

                        },function(r){

                            // fail ? SWAL?
                            swal(
                                zeroBSCRMJS_listViewLang('tagsnotremoved'),
                                zeroBSCRMJS_listViewLang('tagsnotremoveddesc'),
                                'warning'
                            );

                        }); 

                    } else {

                        // didn't select tags

                        swal(
                            zeroBSCRMJS_listViewLang('tagsnotselected'),
                            zeroBSCRMJS_listViewLang('tagsnotselecteddesc'),
                            'warning'
                        );

                    }

                }

            });

        }
/* ====================================================================================
============== / Bulk actions - Pre-checks - Transactions =============================
==================================================================================== */


/* ====================================================================================
=============== Field Drawing JS - Form List View =====================================
==================================================================================== */


        // Draw <td> for form id
        function zeroBSCRMJS_listView_form_id(dataLine){

            var td = '<td><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '">#' + dataLine['id'] + '</a></td>';

            return td;
        }


        // Draw <td> for title
        function zeroBSCRMJS_listView_form_title(dataLine){

            var td = '<td><strong><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '">' + dataLine['title'] + '</a></strong></td>';

            return td;
        }
        function zeroBSCRMJS_listView_form_style(dataLine){

            
            if (typeof dataLine['style'] != "undefined")
            switch (dataLine['style']){

                case 'naked':

                    return '<td class="center aligned"><span class="ui label grey">' + zeroBSCRMJS_listViewLang('naked') + '</span></td>';

                    break;

                case 'cgrab':

                    return '<td class="center aligned"><span class="ui label orange">' + zeroBSCRMJS_listViewLang('cgrab') + '</span></td>';

                    break;

                case 'simple':

                    return '<td class="center aligned"><span class="ui label teal">' + zeroBSCRMJS_listViewLang('simple') + '</span></td>';

                    break;


            }


            return '<td class="center aligned"></td>';

        }

/* ====================================================================================
=============== / Field Drawing JS - Form List View ===================================
==================================================================================== */

/* ====================================================================================
==============   Bulk actions - Titles - Form =========================================
==================================================================================== */
        function zeroBSCRMJS_listView_form_bulkActionTitle_delete(){

            return zeroBSCRMJS_listViewLang('delete');

        }
/* ====================================================================================
============== / Bulk actions - Titles - Form =========================================
==================================================================================== */


/* ====================================================================================
============== Bulk actions - Pre-checks - Form =======================================
==================================================================================== */

        function zeroBSCRMJS_listView_form_bulkActionFire_delete(){

            // SWAL sanity check
            var extraParams = {};

            // see ans 3 here https://stackoverflow.com/questions/31463649/sweetalert-prompt-with-two-input-fields
            swal({
              title: zeroBSCRMJS_listViewLang('areyousure'),
              html: '<div>' + zeroBSCRMJS_listViewLang('areyousurethese') + '</div>',
              //text: "Are you sure you want to delete these?",
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: zeroBSCRMJS_listViewLang('yesdoit'),
              //allowOutsideClick: false,
            }).then(function (result) {

                // this check required from swal2 6.0+ https://github.com/sweetalert2/sweetalert2/issues/724
                if (result.value){

                    // fire delete + will automatically refresh list view
                    zeroBSCRMJS_enactBulkAction('delete',zeroBSCRMJS_listView_bulkActionsGetChecked(),extraParams,function(r){

                        // success ? SWAL?
                          swal(
                            zeroBSCRMJS_listViewLang('deleted'),
                            zeroBSCRMJS_listViewLang('formsdeleted'),
                            'success'
                          );

                    },function(r){

                        // fail ? SWAL?
                        swal(
                            zeroBSCRMJS_listViewLang('notdeleted'),
                            zeroBSCRMJS_listViewLang('notformsdeleted'),
                            'warning'
                        );

                    }); 

                }

            });



        }
/* ====================================================================================
============== / Bulk actions - Pre-checks - Form =====================================
==================================================================================== */


/* Not used - use generic :)

        // Draw <td> for  edit link
        function zeroBSCRMJS_listView_customer_editlink(dataLine){
            // return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon edit"></i>' + window.zbs_lang.zbs_edit + '</a></td>';

            return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '" class="ui basic button"><i class="icon eye"></i>' + window.zbs_lang.zbs_view + '</a></td>';

        }
        // Draw <td> for  edit link
        function zeroBSCRMJS_listView_company_editlink(dataLine){
            // return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon edit"></i>' + window.zbs_lang.zbs_edit + '</a></td>';

            return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '" class="ui basic button"><i class="icon eye"></i>' + window.zbs_lang.zbs_view + '</a></td>';

        }
        // Draw <td> for  edit link
        function zeroBSCRMJS_listView_quote_editlink(dataLine){
            // return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_editURL(dataLine['id']) + '" class="ui basic button"><i class="icon edit"></i>' + window.zbs_lang.zbs_edit + '</a></td>';

            return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '" class="ui basic button"><i class="icon eye"></i>' + window.zbs_lang.zbs_view + '</a></td>';

        }
        function zeroBSCRMJS_listView_invoice_editlink(dataLine){
            return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '" class="ui basic button"><i class="icon eye"></i>' + window.zbs_lang.zbs_edit + '</a></td>';

        }
        // Draw <td> for  edit link
        function zeroBSCRMJS_listView_transaction_editlink(dataLine){

            return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '" class="ui basic button"><i class="icon eye"></i>' + window.zbs_lang.zbs_view + '</a></td>';

        }
        function zeroBSCRMJS_listView_form_editlink(dataLine){
            return '<td class="center aligned"><a href="' + zeroBSCRMJS_listView_viewURL(dataLine['id']) + '" class="ui basic button"><i class="icon eye"></i>' + window.zbs_lang.zbs_edit + '</a></td>';

        }*/

        function zeroBSCRMJS_logTypeStr(typeKey){

            if (typeof window.zbsLogTypes.zerobs_customer[typeKey] != "undefined"){
                return window.zbsLogTypes.zerobs_customer[typeKey].label;
            }
            if (typeof window.zbsLogTypes.zerobs_company[typeKey] != "undefined"){
                return window.zbsLogTypes.zerobs_company[typeKey].label;
            }
            return typeKey;
        }


/* ====================================================================================
===============  Inline Editing  ======================================================
==================================================================================== */

    // binds inline-editing for all fields available
    function zeroBSCRMJS_bindInlineEditing(){

        jQuery('#zbs-list-wrap td.zbs-inline-edit').unbind('click').click(function(){

            // clicked on an edit
            if (jQuery(this).hasClass('zbs-editing')){

                // already editing

            } else {

                // not editing, build editor

                    // get col type + val
                    var col = jQuery(this).attr('data-col');
                    var val = jQuery(this).attr('data-val');

                    console.log('col:',[col,val]);

                    if (typeof col != "undefined" && col != ""){

                        // build editor str
                        var editorStr = '';

                        // if override func exists, use that, else use default out:
                            // e.g.  zeroBSCRMJS_listView_customer_edit_nameavatar
                        var fieldFuncName = 'zeroBSCRMJS_listView_' + window.zbsListViewSettings.objdbname +'_edit_' + col;
                        if (typeof window[fieldFuncName] == 'function'){

                            // use it
                            editorStr = window[fieldFuncName](val);

                        } else {
                            // see if generic exists
                            // e.g.  zeroBSCRMJS_listView_generic_edit_nameavatar
                            var fieldFuncName = 'zeroBSCRMJS_listView_generic_edit_'+col;
                            if (typeof window[fieldFuncName] == 'function'){

                                // use it
                                editorStr = window[fieldFuncName](val);

                            }
                        }


                        // got editor str?
                        if (editorStr != ''){

                            var that = this;

                            // replace td contents
                            jQuery(this).html(editorStr);

                            // change class + unbind click for this td
                            jQuery(this).removeClass('zbs-inline-edit').addClass('zbs-inline-editing');
                            jQuery(this).unbind('click');

                            // bind 
                            zeroBSCRMJS_listView_bindInlineEditSave();

                            // bind + force focus (helps blur work later)
                            setTimeout(function(){

                                var that2 = that;

                                // force focus (helps blur work later)
                                jQuery('select',that2).focus();

                            },100);

                

                        }


                    }

            }


        });



    }

    // returns any classes needed for this td (col) - currently only classes req are inline editing
    function zeroBSCRMJS_listView_tdAttr(colKey,dataLine,val){

        var classStr = '', attrStr = '';

        // inline editing?
        if (
            typeof window.zbsListViewSettings.editinline != "undefined" && 
            window.zbsListViewSettings.editinline &&

            typeof window.zbsListViewParams != "undefined" && 
            typeof window.zbsListViewParams.editinline != "undefined" && 
            typeof window.zbsListViewParams.editinline[colKey] != "undefined" && 
            window.zbsListViewParams.editinline[colKey] == 1){

            classStr += 'zbs-inline-edit';
            attrStr += ' data-col="' + colKey + '"';
            attrStr += ' data-val="' + val + '"';
        }

        if (classStr != '') classStr = ' class="' + classStr + '"';
        return classStr + attrStr;

    }


    // binds the 'click out to save' func
    function zeroBSCRMJS_listView_bindInlineEditSave(){

        jQuery('.zbs-listview-inline-edit-field').unbind('blur').blur(function(){

            // retrieve deets
            var that = this;

            // get id of obj (from nearest tr)
            var id = parseInt(jQuery(this).closest('tr').attr('data-id'));

            // col
            var col = jQuery(this).closest('.zbs-inline-editing').attr('data-col');

            // val
            var value = jQuery(this).val();
            var thisLabel = jQuery(':selected',this).text();// for select's with value != label
            var prevVal = jQuery(this).closest('.zbs-inline-editing').attr('data-val');

            // any change? 
            if (value != prevVal){

                if (id > 0 && col != ''){

                    // probs legit, save
                    zeroBSCRMJS_listView_saveInlineEdit(id,col,value,function(){

                        var lThis = that, lValue = value, lLabel = thisLabel;

                        // worked, update td
                        // for now, just dump the str
                        // ... this'll need adjusting when we get to more complex cols
                        // ... probably using the "zeroBSCRMJS_listView_customer_id" and generic draw html model

                        // replace html  + do classes
                        jQuery(lThis).closest('.zbs-inline-editing').html(lLabel).removeClass('zbs-inline-editing').addClass('zbs-inline-edit');

                        // rebind
                        zeroBSCRMJS_bindInlineEditing();

                    },function(){

                        // err
                          swal(
                            zeroBSCRMJS_listViewLang('couldntupdate'),
                            zeroBSCRMJS_listViewLang('couldntupdatedeets'),
                            'warning'
                          );

                    });

                }

            } else {

                // no change but clicked out :)
                var lThis = that, lValue = value, lLabel = thisLabel;

                // worked, update td
                // for now, just dump the str
                // ... this'll need adjusting when we get to more complex cols
                // ... probably using the "zeroBSCRMJS_listView_customer_id" and generic draw html model

                // replace html  + do classes
                jQuery(lThis).closest('.zbs-inline-editing').html(lLabel).removeClass('zbs-inline-editing').addClass('zbs-inline-edit');

                // rebind
                zeroBSCRMJS_bindInlineEditing();
            }

        });


    }
                            

    // save (ALL)
    var zbListViewInlineEditorAJAXBlocker = false;
    function zeroBSCRMJS_listView_saveInlineEdit(id,col,val,successcb,errcb){

        if (!window.zbListViewInlineEditorAJAXBlocker){

            // set blocker
            window.zbListViewInlineEditorAJAXBlocker = true;

                // postbag!
                var data = {
                    'action': 'zbs_list_save_inline_edit',
                    'sec': window.zbscrmjs_secToken,
                    'listtype': window.zbsListViewParams.listtype,
                    'id': id,
                    'field': col,
                    'v': val
                };

                // Send 
                jQuery.ajax({
                      type: "POST",
                      url: ajaxurl, // admin side is just ajaxurl not wptbpAJAX.ajaxurl,
                      "data": data,
                      dataType: 'json',
                      timeout: 20000,
                      success: function(response) {

                            // temp debug
                            // debug   console.log("Column Data update: ",response);


                            // any success callback?
                            if (typeof successcb == 'function') successcb(response);

                            // unset blocker
                            window.zbListViewInlineEditorAJAXBlocker = false;

                      },
                      error: function(response){ 

                            // temp debug console.error("Column Data update Error: ",response);

                            // any error callback?
                            if (typeof errcb == 'function') errcb(response);

                            // unset blocker
                            window.zbListViewInlineEditorAJAXBlocker = false;

                      }

                });
        }

    }



/* ====================================================================================
============== / Inline Editing  ======================================================
==================================================================================== */


/* ====================================================================================
====================== Field Drawing - Inline Edits ===================================
==================================================================================== */
        
        /* e.g.
        function zeroBSCRMJS_listView_customer_edit_id(dataLine){
        */

        // contact status
        function zeroBSCRMJS_listView_customer_edit_status(existingVal){

            var editorHTML = '';

                // brutal assume set?
                if (window.zbsListViewInlineEdit.customer.statuses.length > 0){

                    // got some
                    editorHTML = '<select class="zbs-listview-inline-edit-field">';
                    jQuery.each(window.zbsListViewInlineEdit.customer.statuses,function(ind,ele){
                        editorHTML += '<option value="' + ele + '"';
                        if (ele == existingVal) editorHTML += ' selected="selected"';
                        editorHTML += '>' + ele + '</option>';
                    });
                    editorHTML += '</select>';
                    
                }

            return editorHTML;

        }

        // contact assigned
        function zeroBSCRMJS_listView_generic_edit_assigned(existingVal){

            var editorHTML = '';

                // brutal assume set?
                if (window.zbsListViewInlineEdit.owners.length > 0){

                    // got some
                    editorHTML = '<select class="zbs-listview-inline-edit-field">';
                    jQuery.each(window.zbsListViewInlineEdit.owners,function(ind,ele){
                        editorHTML += '<option value="' + ele.id + '"';
                        if (ele.id == existingVal) editorHTML += ' selected="selected"';
                        editorHTML += '>' + ele.name + '</option>';
                    });
                    editorHTML += '</select>';
                    
                }

            return editorHTML;

        }

/* ====================================================================================
=================== /  Field Drawing - Inline Edits ===================================
==================================================================================== */

