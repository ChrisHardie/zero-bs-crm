/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.2
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 29/12/16
 */

(function() {

	// brutally hardtyped, later move from
	var templatePlaceholders =  [{text:'Contact Full Name',value:'##CONTACT-FULLNAME##'},{text:'Contact First Name',value:'##CONTACT-FNAME##'},{text:'Contact Last Name',value:'##CONTACT-LNAME##'},{text:'Contact Email',value:'##CONTACT-EMAIL##'},{text:'Business Name',value:'##BIZNAME##'},{text:'Business State/Region',value:'##BIZSTATE##'},{text:'Quote Title',value:'##QUOTETITLE##'},{text:'Quote Value',value:'##QUOTEVALUE##'},{text:'Quote Date',value:'##QUOTEDATE##'}];

    tinymce.PluginManager.add('zbsQuoteTemplates', function( editor, url ) {
        
        // This is simple button
        /*editor.addButton( 'zbsQuoteTemplates', {
            title: 'Scratch Card Engine',
            image: window.wpsceURL + "i/WYSIWYG_icon.png",
            onclick: function () {
                 tinymce.activeEditor.execCommand("mceInsertContent", false, '[zbsQuoteTemplates]')
            }
        });*/


	    editor.addButton( 'zbsQuoteTemplates', {
	            title: 'Quote Template Placeholders',
	            image: window.zbs_root.root + "i/WYSIWYG_icon.png",
	            //icon: 'icon dashicons-tickets',
	            onclick: function () {
		            // Open window
		            editor.windowManager.open({
		                title: 'Select a Placeholder to Insert:',
		                width:400,
		                height:120,
		                body: [
		                    {
		                    	type: 'listbox', 
							    name: 'zbscrmtemplateplaceholder', 
							    label: 'Placeholder', 
							    'values': templatePlaceholders
							    //'value': window.zbsSelectedFormStyle
		                    },
		                ],
		                onsubmit: function(e) {

		                	 tinymce.activeEditor.execCommand("mceInsertContent", false, e.data.zbscrmtemplateplaceholder)

		                }
		            });
	                
	            }
	        });
    });

})();
