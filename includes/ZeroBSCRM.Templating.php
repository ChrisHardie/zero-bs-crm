<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.2.5
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 09/01/2017
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */



/* ======================================================
	ZBS Templating - Load Initial HTML
   ====================================================== */
function zeroBSCRM_retrievePDFTemplate($template='default'){

	$templatedHTML = ''; 

	if (function_exists('file_get_contents')){

		#} templates
		// default = inv
		// statement = statemenet
		$acceptableTemplates = array('default','statement');

		if (in_array($template, $acceptableTemplates)){

		            try {

		            	#} Build from default template - see the useful - http://www.leemunroe.com/responsive-html-email-template/
		                $templatedHTML = file_get_contents(ZEROBSCRM_PATH.'html/invoices/pdf-'.$template.'.html');


		            } catch (Exception $e){

		                #} Nada 

		            }

		}

	}

	return $templatedHTML;

}





/* ======================================================
	ZBS Templating - Load Initial QUOTE HTML
   ====================================================== */
function zeroBSCRM_retrieveQuoteTemplate($template='default'){

	$templatedHTML = ''; 

	if (function_exists('file_get_contents')){

		#} templates
		$acceptableTemplates = array('default');

		if (in_array($template, $acceptableTemplates)){

		            try {

		            	#} Build from default template - see the useful - http://www.leemunroe.com/responsive-html-email-template/
		                $templatedHTML = file_get_contents(ZEROBSCRM_PATH.'html/quotes/quote-'.$template.'.html');


		            } catch (Exception $e){

		                #} Nada 

		            }

		}

	}

	return $templatedHTML;

}



function zeroBSCRM_mailTemplate_processEmailHTML($content){
	global $zbs;
	//acceptable html here is a bit flawed as it needs to be specifically done otherwise it will strip a <b>
	//inside a <p> if not defined carefully, better to just do wp_kses_post()
	//https://codex.wordpress.org/Function_Reference/wp_kses_post also sanitizes.
	$content = wp_kses_post($content);
	return $content;
}

function zeroBSCRM_mailTemplate_emailPreview($templateID=-1){

	global $zbs;

	if($templateID > 0){
		$html 		= zeroBSCRM_mail_retrieveWrapTemplate('default');

		$bodyHTML = "";
		$message_content = zeroBSCRM_mailTemplate_get($templateID);

		//catch any higher pages
		/* wh removed, as we add them, they'll go up 
		if($templateID > 5){
			$bodyHTML = "";
		}else{
			$bodyHTML = $message_content->zbsmail_body;	
		} */
		if (isset($message_content->zbsmail_body)) $bodyHTML = $message_content->zbsmail_body;	
		//our preview sublne
		$subLine = "This is a <b>Zero BS email template preview</b><br/><em>This footer text is not shown in live emails</em>.";
		$html = str_replace('###MSGCONTENT###',$bodyHTML,$html);  
		$html = str_replace('##UNSUB-LINE##',$subLine,$html);  
        $html = str_replace('###FOOTERUNSUBDEETS###',$subLine,$html); // legacy version of UNSUB-DETAILS
		$html = str_replace('###POWEREDBYDEETS###',zeroBSCRM_mailTemplate_poweredByHTML(),$html); // legacy version of POWERED-BY
		$html = str_replace('##POWERED-BY##',zeroBSCRM_mailTemplate_poweredByHTML(),$html);   

		// Replace some common ones with generic examples too:
		$html = str_replace('###EMAIL###','your.user@email.com',$html);

			// stopped sending cleartext passwords 
			// 12-gh-zero-bs-crm
			// $pwd = 'R4ND0MP455W0RD';
			$pwd = '<a href="' . wp_lostpassword_url() . '" title="Lost Password">'. __('Set Your Password', 'zero-bs-crm').'</a>';

		$html = str_replace('###PASSWORD###',$pwd,$html);
		$html = str_replace('###FOOTERBIZDEETS###','',$html);
		$html = str_replace('###FROMNAME###',zeroBSCRM_mailDelivery_defaultFromname(),$html);


		//process the template specific ### to actual viewable stuff...
		if($templateID == 1){
			//client portal

			$html = str_replace('###LOGINURL###',site_url('clients/login'),$html);

		}

		if($templateID == 2){
			//quote accepted
				
			$html = str_replace('###QUOTETITLE###','Example Quotation #101',$html);
			$html = str_replace('###QUOTEURL###',site_url('clients/login'),$html);
			$html = str_replace('###QUOTEEDITURL###',admin_url(),$html);

		}

		if($templateID == 3){
			//invoice template
			$i=0;

			$logoURL = '';
			##WLREMOVE
			$logoURL = $zbs->urls['cdn-logo'];
			##/WLREMOVE


			$tableHeaders = '';

				$zbsInvoiceHorQ = 'quantity';

				if($zbsInvoiceHorQ == 'quantity'){ 
				
					$tableHeaders = '<th class="left">'.__("Description",'zero-bs-crm').'</th><th>'.__("Quantity",'zero-bs-crm').'</th><th>'.__("Price",'zero-bs-crm').'</th><th>'.__("Total",'zero-bs-crm').'</th>';

				}else{ 

					$tableHeaders = '<th class="left">'.__("Description",'zero-bs-crm').'</th><th>'.__("Hours",'zero-bs-crm').'</th><th>'.__("Rate",'zero-bs-crm').'</th><th>'.__("Total",'zero-bs-crm').'</th>';

				}

			$lineItems = "";
			$lineItems .= 
			'<tbody class="zbs-item-block" data-tableid="'.$i.'" id="tblock'.$i.'">
					<tr class="top-row">
						<td style="width:70%">'.__('Your Invoice Item','zero-bs-crm').'</td>
						<td style="width:7.5%;text-align:center;" rowspan="3" class="cen">10</td>
						<td style="width:7.5%;text-align:center;" rowspan="3"class="cen">$20</td>
						<td style="width:7.5%;text-align:right;" rowspan="3" class="row-amount">$200</td>
					</tr>
					<tr class="bottom-row">
						<td colspan="4" class="tapad">'.__('Your invoice item description goes here','zero-bs-crm').'</td>     
					</tr>
					<tr class="add-row"></tr>
			</tbody>';  

			$html = str_replace('###INVOICETITLE###',__('Invoice','zero-bs-crm'),$html);  
			$html = str_replace('###LOGOURL###',$logoURL, $html);  
			
			$html = str_replace('###TITLE###',"Invoice Template", $html);  
			##WLREMOVE
			   //code goes here
				$html = str_replace('###TITLE###',"Zero BS CRM Invoice Template", $html);  
			##/WLREMOVE

			$invNoStr = "101";
			$invDateStr = "01/01/3001";
			$ref = "ABC";
			$dueDateStr = "01/01/3001";

			$totalsTable = "";

			$bizInfoTable = "";
			##WLREMOVE
				$bizInfoTable = "<div style='text-align:right'><b>John Doe</b><br/>This is replaced<br>with the customers details<br>from their profile.</div>";			
			##/WLREMOVE

			$html = str_replace('###INVNOSTR###',$invNoStr,$html);  
			$html = str_replace('###INVDATESTR###',$invDateStr,$html);  
			$html = str_replace('###REF###',$ref,$html);  
			$html = str_replace('###DUEDATE###',$dueDateStr,$html);  

			$html = str_replace('###BIZINFOTABLE###',$bizInfoTable,$html);  
			$html = str_replace('###TABLEHEADERS###',$tableHeaders,$html);  
			$html = str_replace('###LINEITEMS###',$lineItems,$html);  
			$html = str_replace('###TOTALSTABLE###',$totalsTable,$html);    

            $viewInPortal = '';
			$invoiceID = '';

            // got portal?
            //if (isset($invsettings['feat_portal']) && !empty($invsettings['feat_portal'])){
	        if (zeroBSCRM_isExtensionInstalled('portal')){

	        	// view on portal (hashed?)
	        	$viewInPortalURL = zeroBSCRM_portal_linkObj($invoiceID,ZBS_TYPE_INVOICE); //zeroBS_portal_link('invoices',$invoiceID);

	            // if viewing in portal?
	            $viewInPortal = '<div style="text-align:center;margin:1em;margin-top:2em">'.zeroBSCRM_mailTemplate_emailSafeButton($viewInPortalURL,__('View Invoice','zero-bs-crm')).'</div>';

	        }

			// view in portal?
            $html = str_replace('###VIEWINPORTAL###', $viewInPortal, $html);


		}

		// new proposal
		if($templateID == 4){

			$html = str_replace('###QUOTETITLE###','Example Quotation #101',$html); 
			$html = str_replace('###QUOTEURL###',site_url('clients/login'),$html);

		}


		// event
		if($templateID == 5){

			$html = str_replace('###EVENTTITLE###','Example Event #101',$html); 

            // centered event link button
    		$eventLinkButton = '<div style="text-align:center;margin:1em;margin-top:2em">'.zeroBSCRM_mailTemplate_emailSafeButton(admin_url(),__('View Event','zero-bs-crm')).'</div>';
            $bodyHTML = str_replace('###EVENTLINKBUTTON###',$eventLinkButton,$bodyHTML);

		}


		// generic replaces (e.g. loginlink, loginbutton)
		$html = zeroBSCRM_mailTemplate_genericReplaces($html);

	}else{
		$html = "";
	}
	return $html;
}


// Check if attempting to preview email template
function zeroBSCRM_preview_email_template(){

	// if trying to preview
	if (isset($_GET['zbsmail-template-preview']) && $_GET['zbsmail-template-preview'] == 1){
  		
  		// if rights
  		if ( current_user_can( 'admin_zerobs_manage_options' ) ) {  

			$html = '';

			if(isset($_GET['template_id']) && !empty($_GET['template_id'])){

				$templateID = (int)sanitize_text_field( $_GET['template_id'] );
				$html = zeroBSCRM_mailTemplate_emailPreview($templateID);

			} else {

				$html = zeroBSCRM_mail_retrieveWrapTemplate('default');
				$bodyHTML = "";
				##WLREMOVE##
				$bodyHTML = "<h3 style='text-align:center;text-transform:uppercase'>Welcome to Zero BS CRM Email Templates</h3>";
				##/WLREMOVE##
				$bodyHTML .= "<div style='text-align:center'>" . __("This is example content for the email template preview. <p>This content will be replaced by what you have in your system email templates</p>", 'zero-bs-crm') . "</div>"; 
				$subLine = __("Thanks for using Zero BS CRM",'zero-bs-crm'); 
				// bit OTT like 5 references to zbs $bizInfoTable = "<b>Zero BS CRM is a Zero BS Software Limited Production</b>";
				$bizInfoTable = ''; $custInfoTable = '';
				$html = str_replace('###TITLE###',__('Template Preview','zero-bs-crm'),$html);
		        $html = str_replace('###MSGCONTENT###',$bodyHTML,$html);  
		        $html = str_replace('###FOOTERBIZDEETS###',$bizInfoTable,$html);  
		        $html = str_replace('###CUSTINFOTABLE###',$custInfoTable,$html);  
		        $html = str_replace('##UNSUB-LINE##',$subLine,$html);  
		        $html = str_replace('###FOOTERUNSUBDEETS###',$subLine,$html); // legacy version of UNSUB-DETAILS		        
				$html = str_replace('###POWEREDBYDEETS###',zeroBSCRM_mailTemplate_poweredByHTML(),$html); // legacy version of POWERED-BY
				$html = str_replace('##POWERED-BY##',zeroBSCRM_mailTemplate_poweredByHTML(),$html);   
				$html = str_replace('###FROMNAME###',zeroBSCRM_mailDelivery_defaultFromname(),$html);

	    	}


			echo $html;

			die();

		}
	}
} add_action('init','zeroBSCRM_preview_email_template');



/* ===========================================================
	ZBS Templating - Load Default Templates / Restore Default 
   ========================================================== */
function zeroBSCRM_mail_retrieveDefaultBodyTemplate($template='maintemplate'){

	$templatedHTML = ''; 

	if (function_exists('file_get_contents')){

		#} templates
		$acceptableTemplates = array('maintemplate','clientportal','invoicesent','quoteaccepted','quotesent','eventnotification','clientportalpwreset','invoicestatementsent');

		if (in_array($template, $acceptableTemplates)){

				// 2.98.6+ translated. maintemplate was a misnomer
				if ($template == 'maintemplate') $template = '_responsivewrap';

		            try {

		            	#} Build from default template - see the useful - http://www.leemunroe.com/responsive-html-email-template/
		                $templatedHTML = file_get_contents(ZEROBSCRM_PATH.'html/templates/'.$template.'.html');


		            } catch (Exception $e){

		                #} Nada 

		            }

		}

	}

	return $templatedHTML;
}

// v2.98.6 - change default from /html/notifications/email-default/ to /html/templates/_responsivewrap.html
function zeroBSCRM_mail_retrieveWrapTemplate($template='default'){

	$templatedHTML = ''; 

	if (function_exists('file_get_contents')){

		#} templates
		$acceptableTemplates = array('default');

		if (in_array($template, $acceptableTemplates)){

				// translation of names (v2.98.6+)
				if ($template == "default") $template = '_responsivewrap';

		            try {

		            	#} Build from default template - see the useful - http://www.leemunroe.com/responsive-html-email-template/
		                //$templatedHTML = file_get_contents(ZEROBSCRM_PATH.'html/notifications/email-'.$template.'.html');
		                // 2.98.6+ moved this to proper dir.
		                $templatedHTML = file_get_contents(ZEROBSCRM_PATH.'html/templates/'.$template.'.html');


		            } catch (Exception $e){

		                #} Nada 

		            }

		}

	}

	return $templatedHTML;
}


/* ======================================================
	/ ZBS Templating - Load Initial HTML
   ====================================================== */

/* ======================================================
	ZBS Quotes - Generate HTML (notification emails)
   ====================================================== */

function zeroBSCRM_quote_generateNotificationHTML($quoteID=-1,$return=true){
		global $zbs;
	    if (!empty($quoteID)){

	        $templatedHTML = ''; $html = ''; $pWrap = '<p style="font-family:sans-serif;font-size:14px;font-weight:normal;margin:0;Margin-bottom:15px;">';

	        #} Get templated notify email
	        $templatedHTML = zeroBSCRM_mail_retrieveWrapTemplate('default');

	        #} Act
	        if (!empty($templatedHTML)){

	        	#} Actual body:
	        	$bodyHTML = '';

	        		#} Retrieve quote (for title + URL)
	        		$quote = zeroBS_getQuote($quoteID,true);

	        		// 3 translation
	        		$hasQuote = false; $proposalTitle = '';
	        		if ($zbs->isDAL3()){

	        			// dal3
	        			if (isset($quote) && is_array($quote)) {
	        				$hasQuote = true;
	        				if (isset($quote) && isset($quote['title']) && !empty($quote['title'])) $proposalTitle = $quote['title'];
	        			}

	        		} else {

	        			// pre dal3
	        			if (isset($quote['meta']) && is_array($quote['meta'])) {
	        				$hasQuote = true;
	        				if (isset($quote) && isset($quote['meta']['name']) && !empty($quote['meta']['name'])) $proposalTitle = $quote['meta']['name'];
	        			}

	        		}

	        		if ($hasQuote){

	                    //the business info from the settings
	                    $zbs_biz_name =  zeroBSCRM_getSetting('businessname');
	                    $zbs_biz_yourname =  zeroBSCRM_getSetting('businessyourname');

	                    $zbs_biz_extra =  zeroBSCRM_getSetting('businessextra');

	                    $zbs_biz_youremail =  zeroBSCRM_getSetting('businessyouremail');
	                    $zbs_biz_yoururl =  zeroBSCRM_getSetting('businessyoururl');
	                    $zbs_settings_slug = admin_url("admin.php?page=" . $zbs->slugs['settings']) . "&tab=invoices";
	
						#} title + URL                        		
                		$proposalURL = zeroBSCRM_portal_linkObj($quoteID,ZBS_TYPE_QUOTE); //zeroBS_portal_link('quotes',$quoteID);

                        #} override with settings email...
    					$message_content = zeroBSCRM_mailTemplate_get(ZBSEMAIL_NEWQUOTE);
    					$bodyOverride = $message_content->zbsmail_body;

						$bodyOverride = str_replace('###QUOTEURL###',$proposalURL,$bodyOverride);  
						$bodyOverride = str_replace('###QUOTETITLE###',$proposalTitle,$bodyOverride);  
						$bodyOverride = str_replace('###BIZNAME###',$zbs_biz_name,$bodyOverride);  
		        		$bodyHTML = $bodyOverride;
				        	 
			        	#} For now, use this, ripped from invoices: 
			        	#} (We need to centralise)

			                $bizInfoTable = '';

			                    $bizInfoTable = '<table class="table zbs-table" style="width:100%;">';
			                        $bizInfoTable .= '<tbody>';
			                            $bizInfoTable .= '<tr><td style="font-family:sans-serif;font-size:14px;vertical-align:top;padding-bottom:5px;"><strong>'.$zbs_biz_name.'</strong></td></tr>';
			                            $bizInfoTable .= '<tr><td style="font-family:sans-serif;font-size:14px;vertical-align:top;padding-bottom:5px;">'.$zbs_biz_yourname.'</td></tr>';
			                            $bizInfoTable .= '<tr><td style="font-family:sans-serif;font-size:14px;vertical-align:top;padding-bottom:5px;">'.$zbs_biz_extra.'</td></tr>';
			                            #$bizInfoTable .= '<tr class="top-pad"><td>'.$zbs_biz_youremail.'</td></tr>';
			                           	#$bizInfoTable .= '<tr><td>'.$zbs_biz_yoururl.'</td></tr>';
			                        $bizInfoTable .= '</tbody>';
			                    $bizInfoTable .= '</table>';

			            # phony - needs unsub
			            $subLine = __('You have received this notification because a proposal has been sent to you','zero-bs-crm');
			            if (isset($zbs_biz_name) && !empty($zbs_biz_name)) $subLine .= ' by '.$zbs_biz_name;
			            $subLine .= __('. If you believe this was sent in error, please reply and let us know.','zero-bs-crm');


			            #} Set them
			            # Not using this, inlined :) $cssURL = ZEROBSCRM_URL . 'css/ZeroBSCRM.emaildefaults.min.css';

			            #} replace the vars
			            #$html = str_replace('###CSS###',$cssURL,$templatedHTML);
			            $proposalEmailTitle = __('Proposal Notification','zero-bs-crm');




			            $html = str_replace('###TITLE###', $proposalEmailTitle, $templatedHTML);
			            $html = str_replace('###MSGCONTENT###',$bodyHTML,$html);  
			            $html = str_replace('###FOOTERBIZDEETS###',$bizInfoTable,$html);  
			            $html = str_replace('##UNSUB-LINE##',$subLine,$html);  
			            $html = str_replace('###FOOTERUNSUBDEETS###',$subLine,$html); // legacy version of UNSUB-DETAILS			            
						$html = str_replace('###POWEREDBYDEETS###',zeroBSCRM_mailTemplate_poweredByHTML(),$html); // legacy version of POWERED-BY
						$html = str_replace('##POWERED-BY##',zeroBSCRM_mailTemplate_poweredByHTML(),$html);   
						$html = str_replace('###FROMNAME###',zeroBSCRM_mailDelivery_defaultFromname(),$html);


    					// generic replaces (e.g. loginlink, loginbutton)
    					$html = zeroBSCRM_mailTemplate_genericReplaces($html);

			       }

	            #} Go
	            if (!$return) { echo $html; exit(); }

	        }  

	        return $html;


       	}
	    # FAIL
	   	return;
} 


#} sent to quote creator
function zeroBSCRM_quote_generateAcceptNotifHTML($quoteID=-1,$quoteSignedBy='',$return=true){

		global $zbs;

	    if (!empty($quoteID)){

	        $templatedHTML = ''; $html = ''; $pWrap = '<p style="font-family:sans-serif;font-size:14px;font-weight:normal;margin:0;Margin-bottom:15px;">';

	        #} Get templated notify email
	        $templatedHTML = zeroBSCRM_mail_retrieveWrapTemplate('default');

	        #} Act
	        if (!empty($templatedHTML)){

	        	#} Actual body:
	        	$bodyHTML = '';

	        		#} Retrieve quote (for title + URL)
	        		$quote = zeroBS_getQuote($quoteID,true);

	        		// 3 translation
	        		$hasQuote = false; $proposalTitle = '';
	        		if ($zbs->isDAL3()){

	        			// dal3
	        			if (isset($quote) && is_array($quote)) {
	        				$hasQuote = true;
	        				if (isset($quote) && isset($quote['title']) && !empty($quote['title'])) $proposalTitle = $quote['title'];
	        			}

	        		} else {

	        			// pre dal3
	        			if (isset($quote['meta']) && is_array($quote['meta'])) {
	        				$hasQuote = true;
	        				if (isset($quote) && isset($quote['meta']['name']) && !empty($quote['meta']['name'])) $proposalTitle = $quote['meta']['name'];
	        			}

	        		}

	        		if ($hasQuote){
	
                		//?? WH just saw this, not sure? // this is wrong, directs to portal - and WRONG url
                		//$proposalURL = get_the_permalink($quoteID);
                		//$proposalEditURL = get_edit_post_link($quoteID);
                		//... put these which should fix?
                		$proposalURL = zbsLink('edit',$quoteID,'zerobs_quote');
                		$proposalEditURL = zbsLink('edit',$quoteID,'zerobs_quote'); // same for now, not sure if have "view" urls for quotes (at point of writing this DAL3 translation)

			            // WH: Took these from above function, to fill your missing gaps (vars defined which are not set e.g. $bizInfoTable)
			            // biz info needs centralising + these too

				        	#} For now, use this, ripped from invoices: 
				        	#} (We need to centralise)

		                    $zbs_biz_name =  zeroBSCRM_getSetting('businessname');
		                    $zbs_biz_yourname =  zeroBSCRM_getSetting('businessyourname');
		                    $zbs_biz_extra =  zeroBSCRM_getSetting('businessextra');

				                $bizInfoTable = '';

				                    $bizInfoTable = '<table class="table zbs-table" style="width:100%;">';
				                        $bizInfoTable .= '<tbody>';
				                            $bizInfoTable .= '<tr><td style="font-family:sans-serif;font-size:14px;vertical-align:top;padding-bottom:5px;"><strong>'.$zbs_biz_name.'</strong></td></tr>';
				                            $bizInfoTable .= '<tr><td style="font-family:sans-serif;font-size:14px;vertical-align:top;padding-bottom:5px;">'.$zbs_biz_yourname.'</td></tr>';
				                            $bizInfoTable .= '<tr><td style="font-family:sans-serif;font-size:14px;vertical-align:top;padding-bottom:5px;">'.$zbs_biz_extra.'</td></tr>';
				                            #$bizInfoTable .= '<tr class="top-pad"><td>'.$zbs_biz_youremail.'</td></tr>';
				                           	#$bizInfoTable .= '<tr><td>'.$zbs_biz_yoururl.'</td></tr>';
				                        $bizInfoTable .= '</tbody>';
				                    $bizInfoTable .= '</table>';

			        	#} Over-ride with our new template stuff :-)
    					$message_content = zeroBSCRM_mailTemplate_get(ZBSEMAIL_QUOTEACCEPTED);
    					$bodyOverride = $message_content->zbsmail_body;
						$bodyOverride = str_replace('###QUOTEURL###',$proposalURL,$bodyOverride);  
						$bodyOverride = str_replace('###QUOTEEDITURL###', $proposalEditURL, $bodyOverride);
						$bodyOverride = str_replace('###QUOTETITLE###',$proposalTitle,$bodyOverride);  
						$bodyOverride = str_replace('###BIZNAME###',$zbs_biz_name,$bodyOverride);  
		        		$bodyHTML = $bodyOverride;
				  
			            $subLine = __('You have received this notification because your proposal has been accepted in Zero BS CRM','zero-bs-crm');
			            $subLine .= __('. If you believe this was sent in error, please reply and let us know.','zero-bs-crm');

			            #} Set them
			            # Not using this, inlined :) $cssURL = ZEROBSCRM_URL . 'css/ZeroBSCRM.emaildefaults.min.css';

			            #} replace the vars
			            #$html = str_replace('###CSS###',$cssURL,$templatedHTML);
			            $proposalEmailTitle = __('Proposal Notification','zero-bs-crm');
			            $html = str_replace('###TITLE###',$proposalEmailTitle,$templatedHTML);
			            $html = str_replace('###MSGCONTENT###',$bodyHTML,$html);  
			            $html = str_replace('###FOOTERBIZDEETS###',$bizInfoTable,$html);  
			            $html = str_replace('##UNSUB-LINE##',$subLine,$html);  
			            $html = str_replace('###FOOTERUNSUBDEETS###',$subLine,$html); // legacy version of UNSUB-DETAILS			            
						$html = str_replace('###POWEREDBYDEETS###',zeroBSCRM_mailTemplate_poweredByHTML(),$html); // legacy version of POWERED-BY
						$html = str_replace('##POWERED-BY##',zeroBSCRM_mailTemplate_poweredByHTML(),$html);     
						$html = str_replace('###FROMNAME###',zeroBSCRM_mailDelivery_defaultFromname(),$html);

    					// generic replaces (e.g. loginlink, loginbutton)
    					$html = zeroBSCRM_mailTemplate_genericReplaces($html);

			       }

	            #} Go
	            if (!$return) { echo $html; exit(); }

	        }  

	        return $html;


       	}

	    # FAIL
	   	return;
} 

/* ======================================================
	/ ZBS Quotes - Generate HTML (notification email)
   ====================================================== */



/* ======================================================
	ZBS Invoices - Generate HTML (notification emails)
   ====================================================== */

function zeroBSCRM_invoice_generateNotificationHTML($invoiceID=-1,$return=true){

		global $zbs;
	    if (!empty($invoiceID) && $invoiceID > 0){

	    	// WH: is this even right?
	    	$invoicePostID = $invoiceID;

	        $html = ''; $body = ''; $pWrap = '<p style="font-family:sans-serif;font-size:14px;font-weight:normal;margin:0;Margin-bottom:15px;">';

	        #} Get templated notify email

	        	// body template
				$mailTemplate = zeroBSCRM_mailTemplate_get(ZBSEMAIL_EMAILINVOICE);
				$bodyHTML = $mailTemplate->zbsmail_body;	

				// html template
		        $html = zeroBSCRM_mail_retrieveWrapTemplate('default');
	            $html = str_replace('###MSGCONTENT###',$bodyHTML,$html);

	        #} Act
	        if (!empty($html)){

		            // this was refactored as was duplicate code.
		            // now all wired through zeroBSCRM_invoicing_generateInvoiceHTML
		            $html = zeroBSCRM_invoicing_generateInvoiceHTML($invoiceID,'notification',$html);

		            // view in portal is added to this inv html :)
		            $viewInPortal = '';

		            // got portal?
		            //if (isset($invsettings['feat_portal']) && !empty($invsettings['feat_portal'])){
		            if (zeroBSCRM_isExtensionInstalled('portal')){

			            // adapted from your quotes url gen
			            //$viewInPortalURL = home_url('/clients/invoices/'. $invoiceID);  // new link..
			        	$viewInPortalURL = zeroBSCRM_portal_linkObj($invoiceID,ZBS_TYPE_INVOICE); //zeroBS_portal_link('invoices',$invoiceID);

			            // if viewing in portal?
			            $viewInPortal = '<div style="text-align:center;margin:1em;margin-top:2em">'.zeroBSCRM_mailTemplate_emailSafeButton($viewInPortalURL,__('View Invoice','zero-bs-crm')).'</div>';

			        }

					// view in portal?
		            $html = str_replace('###VIEWINPORTAL###', $viewInPortal, $html);

					// generic replaces (e.g. loginlink, loginbutton)
					$html = zeroBSCRM_mailTemplate_genericReplaces($html);

	            #} Go
	            if (!$return) { echo $html; exit(); }

	        }  

	        return $html;


       	}
	    # FAIL
	   	return;
} 


// generates statement email html based on template in sys mail
function zeroBSCRM_statement_generateNotificationHTML($contactID=-1,$return=true){

		global $zbs;

	    if (!empty($contactID)){

	        $html = ''; $pWrap = '<p style="font-family:sans-serif;font-size:14px;font-weight:normal;margin:0;Margin-bottom:15px;">';

	        #} Get templated notify email

	        	// body template
				$mailTemplate = zeroBSCRM_mailTemplate_get(ZBSEMAIL_STATEMENT);
				$bodyHTML = $mailTemplate->zbsmail_body;	

				// html template
		        $html = zeroBSCRM_mail_retrieveWrapTemplate('default');
	            $html = str_replace('###MSGCONTENT###',$bodyHTML,$html);

	        #} Act
	        if (!empty($html)){

                //the business info from the settings
                $zbs_biz_name =  zeroBSCRM_getSetting('businessname');
                $zbs_biz_yourname =  zeroBSCRM_getSetting('businessyourname');
                //notused?$zbs_biz_youremail =  zeroBSCRM_getSetting('businessyouremail');
                //notused?$zbs_biz_yoururl =  zeroBSCRM_getSetting('businessyoururl');
                $zbs_biz_extra =  zeroBSCRM_getSetting('businessextra');

				$html = str_replace('###BIZNAME###',$zbs_biz_name,$html);  
		        	 
	        	#} For now, use this, ripped from invoices: 
	        	#} (We need to centralise)

	                $bizInfoTable = '';

	                    $bizInfoTable = '<table class="table zbs-table" style="width:100%;">';
	                        $bizInfoTable .= '<tbody>';
	                            $bizInfoTable .= '<tr><td style="font-family:sans-serif;font-size:14px;vertical-align:top;padding-bottom:5px;"><strong>'.$zbs_biz_name.'</strong></td></tr>';
	                            $bizInfoTable .= '<tr><td style="font-family:sans-serif;font-size:14px;vertical-align:top;padding-bottom:5px;">'.$zbs_biz_yourname.'</td></tr>';
	                            $bizInfoTable .= '<tr><td style="font-family:sans-serif;font-size:14px;vertical-align:top;padding-bottom:5px;">'.$zbs_biz_extra.'</td></tr>';
	                            #$bizInfoTable .= '<tr class="top-pad"><td>'.$zbs_biz_youremail.'</td></tr>';
	                           	#$bizInfoTable .= '<tr><td>'.$zbs_biz_yoururl.'</td></tr>';
	                        $bizInfoTable .= '</tbody>';
	                    $bizInfoTable .= '</table>';

	            #} replace the vars
	            #$html = str_replace('###CSS###',$cssURL,$templatedHTML);
	            $emailTitle = __('Statement','zero-bs-crm');
	            $subLine = '';

	            $html = str_replace('###TITLE###', $emailTitle, $html);  
	            $html = str_replace('###FOOTERBIZDEETS###',$bizInfoTable,$html);  
	            $html = str_replace('##UNSUB-LINE##',$subLine,$html);  
	            $html = str_replace('###FOOTERUNSUBDEETS###',$subLine,$html); // legacy version of UNSUB-DETAILS			            
				$html = str_replace('###POWEREDBYDEETS###',zeroBSCRM_mailTemplate_poweredByHTML(),$html); // legacy version of POWERED-BY
				$html = str_replace('##POWERED-BY##',zeroBSCRM_mailTemplate_poweredByHTML(),$html);   
				$html = str_replace('###FROMNAME###',zeroBSCRM_mailDelivery_defaultFromname(),$html);


				// generic replaces (e.g. loginlink, loginbutton)
				$html = zeroBSCRM_mailTemplate_genericReplaces($html);

	            #} Go
	            if (!$return) { echo $html; exit(); }

	        }  

	        return $html;


       	}
	    # FAIL
	   	return;
} 
/* ======================================================
	/ ZBS Invoices - Generate HTML (notification email)
   ====================================================== */

/* ======================================================
	ZBS Portal - Generate HTML (notification emails)
   ====================================================== */

function zeroBSCRM_Portal_generateNotificationHTML($pwd=-1,$return=true, $email){

		global $zbs;

	    if (!empty($pwd)){

	        $templatedHTML = ''; $html = ''; $pWrap = '<p style="font-family:sans-serif;font-size:14px;font-weight:normal;margin:0;Margin-bottom:15px;">';

	        #} Get templated notify email
	        $templatedHTML = zeroBSCRM_mail_retrieveWrapTemplate('default');

	        #} Act
	        if (!empty($templatedHTML)){
	        	#} Retrieve inv here, then build body etc. as below, and then use the replaces to make email....
	        	$inv = array();
	        		if (is_array($inv)){
				        	#} Actual body:
				        	$bodyHTML = '';
				        	$bizInfoTable = '';
				        	$subLine = '';
		
        					$message_content = zeroBSCRM_mailTemplate_get(ZBSEMAIL_CLIENTPORTALWELCOME);	
        					$bodyOverride = $message_content->zbsmail_body;
 
							$bodyOverride = str_replace('###EMAIL###',$email,$bodyOverride);  

							#} change the password to be a link 
							$pwd = '<a href="' . wp_lostpassword_url() . '" title="Lost Password">'. __('Set Your Password', 'zero-bs-crm').'</a>';

							$bodyOverride = str_replace('###PASSWORD###',$pwd,$bodyOverride);  
			        		$bodyHTML = $bodyOverride;

				            #} replace the vars
				            $emailTitle = __('Welcome to your Client Portal','zero-bs-crm');

				            $html = str_replace('###TITLE###',$emailTitle,$templatedHTML);
				            $html = str_replace('###MSGCONTENT###',$bodyHTML,$html);  
				            $html = str_replace('###FOOTERBIZDEETS###',$bizInfoTable,$html);  
				            $html = str_replace('##UNSUB-LINE##',$subLine,$html); 
				            $html = str_replace('###FOOTERUNSUBDEETS###',$subLine,$html); // legacy version of UNSUB-DETAILS 				            
							$html = str_replace('###POWEREDBYDEETS###',zeroBSCRM_mailTemplate_poweredByHTML(),$html); // legacy version of POWERED-BY
							$html = str_replace('##POWERED-BY##',zeroBSCRM_mailTemplate_poweredByHTML(),$html);   
							$html = str_replace('###FROMNAME###',zeroBSCRM_mailDelivery_defaultFromname(),$html);


	    					// generic replaces (e.g. loginlink, loginbutton)
	    					$html = zeroBSCRM_mailTemplate_genericReplaces($html);

				      }

	            #} Go
	            if (!$return) { echo $html; exit(); }

	        }  

	        return $html;


       	}

	    # FAIL
	   	return;
} 

 #} adapted from above. pw reset email
function zeroBSCRM_Portal_generatePWresetNotificationHTML($pwd=-1,$return=true, $email=''){

		global $zbs;

	    if (!empty($pwd)){

	        $templatedHTML = ''; $html = ''; $pWrap = '<p style="font-family:sans-serif;font-size:14px;font-weight:normal;margin:0;Margin-bottom:15px;">';

	        #} Get templated notify email
	        $templatedHTML = zeroBSCRM_mail_retrieveWrapTemplate('default');

	        #} Act
	        if (!empty($templatedHTML)){
	        	#} Retrieve inv here, then build body etc. as below, and then use the replaces to make email....
	        	$inv = array();
	        		if (is_array($inv)){
				        	#} Actual body:
				        	$bodyHTML = '';
				        	$bizInfoTable = '';
				        	$subLine = '';
		
        					$message_content = zeroBSCRM_mailTemplate_get(ZBSEMAIL_CLIENTPORTALPWREST);	
        					$bodyOverride = $message_content->zbsmail_body;
 
							$bodyOverride = str_replace('###EMAIL###',$email,$bodyOverride);  
							$bodyOverride = str_replace('###PASSWORD###',$pwd,$bodyOverride);  
			        		$bodyHTML = $bodyOverride;

				            #} replace the vars
				            $emailTitle = __('Your Client Portal Password has been reset','zero-bs-crm');

				            $html = str_replace('###TITLE###',$emailTitle,$templatedHTML);
				            $html = str_replace('###MSGCONTENT###',$bodyHTML,$html);  
				            $html = str_replace('###FOOTERBIZDEETS###',$bizInfoTable,$html);  
				            $html = str_replace('##UNSUB-LINE##',$subLine,$html); 
				            $html = str_replace('###FOOTERUNSUBDEETS###',$subLine,$html); // legacy version of UNSUB-DETAILS 				            
							$html = str_replace('###POWEREDBYDEETS###',zeroBSCRM_mailTemplate_poweredByHTML(),$html); // legacy version of POWERED-BY
							$html = str_replace('##POWERED-BY##',zeroBSCRM_mailTemplate_poweredByHTML(),$html);   
							$html = str_replace('###FROMNAME###',zeroBSCRM_mailDelivery_defaultFromname(),$html);


	    					// generic replaces (e.g. loginlink, loginbutton)
	    					$html = zeroBSCRM_mailTemplate_genericReplaces($html);

				      }

	            #} Go
	            if (!$return) { echo $html; exit(); }

	        }  

	        return $html;


       	}

	    # FAIL
	   	return;
} 

function zeroBSCRM_Event_generateNotificationHTML($pwd=-1,$return=true, $email, $url, $eventID){
	        $templatedHTML = ''; $html = ''; $pWrap = '<p style="font-family:sans-serif;font-size:14px;font-weight:normal;margin:0;Margin-bottom:15px;">';

	        #} Get templated notify email
	        $templatedHTML = zeroBSCRM_mail_retrieveWrapTemplate('default');
	        #} Act
	        if (!empty($templatedHTML)){
	        	#} Retrieve inv here, then build body etc. as below, and then use the replaces to make email....
	        	$inv = array();
	        		if (is_array($inv)){
				        	#} Actual body:
				        	$bodyHTML = '';
				        	$bizInfoTable = '';
				        	$subLine = '';

				        	$eventURL = admin_url('post.php?post='.$eventID.'&action=edit');

				        	$event_detail = get_post( $eventID );

				        	#} ID = 5 is the Event Notification.
        					$message_content = zeroBSCRM_mailTemplate_get(ZBSEMAIL_EVENTNOTIFICATION);	
							$bodyHTML = $message_content->zbsmail_body;

        					// generic replaces (e.g. loginlink, loginbutton)
							$bodyHTML = zeroBSCRM_mailTemplate_genericReplaces($bodyHTML);
							
						
							$zbsEventMeta 	 = 	get_post_meta($eventID, 'zbs_event_meta', true);
							$zbsEventActions =  get_post_meta($eventID, 'zbs_event_actions', true);

							$zbsEventActions = array_merge($zbsEventMeta, $zbsEventActions);

							#} construct the event HTML here
							$eventHTML = "<h1>" . $zbsEventActions['title'] . "</h1>";
							$eventHTML .= "<p>" . $zbsEventActions['notes'] . "</p>";
							$eventHTML .= "<p>";
								$eventHTML .=  __('from: ', 'zero-bs-crm') . $zbsEventActions['from'] . "<br/>";
								$eventHTML .=  __('to: ', 'zero-bs-crm') . $zbsEventActions['to'];
							$eventHTML .= "</p>";

				            
				            #} fill in the template with the event data....
				            $bodyHTML = str_replace('###EVENTTITLE###',$eventHTML, $bodyHTML);

				            // centered event link button
		            		$eventLinkButton = '<div style="text-align:center;margin:1em;margin-top:2em">'.zeroBSCRM_mailTemplate_emailSafeButton($eventURL,__('View Event','zero-bs-crm')).'</div>';
				            $bodyHTML = str_replace('###EVENTLINKBUTTON###',$eventLinkButton,$bodyHTML);

				            #} replace the vars
				            $html = str_replace('###TITLE###','Your Event is starting soon',$templatedHTML);

				            $html = str_replace('###MSGCONTENT###',$bodyHTML,$html);  
				            $html = str_replace('###FOOTERBIZDEETS###',$bizInfoTable,$html);  
				            $html = str_replace('##UNSUB-LINE##',$subLine,$html);  
							$html = str_replace('###POWEREDBYDEETS###',zeroBSCRM_mailTemplate_poweredByHTML(),$html); // legacy version of POWERED-BY
							$html = str_replace('##POWERED-BY##',zeroBSCRM_mailTemplate_poweredByHTML(),$html);    
							$html = str_replace('###FROMNAME###',zeroBSCRM_mailDelivery_defaultFromname(),$html);


	    					// generic replaces (e.g. loginlink, loginbutton)
	    					$html = zeroBSCRM_mailTemplate_genericReplaces($html);

				      }
	            #} Go
	            if (!$return) { echo $html; exit(); }
	        }  
	        return $html;
} 


/* ======================================================
	/ ZBS Invoices - Generate HTML (notification emails)
   ====================================================== */


/* ======================================================
	ZBS Direct Emails - Generate HTML
   ====================================================== */
function zeroBSCRM_mailTemplates_directMsg($return=true, $content='', $title = ''){

        $templatedHTML = ''; $html = ''; $pWrap = '<p style="font-family:sans-serif;font-size:14px;font-weight:normal;margin:0;Margin-bottom:15px;">';

        #} Get templated notify email
        $templatedHTML = zeroBSCRM_mail_retrieveWrapTemplate('default');
        #} Act
        if (!empty($templatedHTML)){


	        	#} Actual body:
	        	$bodyHTML = nl2br($content);

	        	$footerDeets = '';
	        	##WLREMOVE
	        	//$footerDeets = '<p>'.__("Sent from Zero BS CRM.",'zero-bs-crm').'</p>';
				##/WLREMOVE
				
				$footerUnSub ='';

	            #} replace the vars
	            $html = str_replace('###TITLE###',$title,$templatedHTML);
	            $html = str_replace('###MSGCONTENT###',$bodyHTML,$html);  
	            $html = str_replace('###FOOTERBIZDEETS###',$footerDeets,$html);  
	            $html = str_replace('##UNSUB-LINE##',$footerUnSub,$html);  
	           	$html = str_replace('###FOOTERUNSUBDEETS###',$footerUnSub,$html); // legacy version of UNSUB-DETAILS
				$html = str_replace('###POWEREDBYDEETS###',zeroBSCRM_mailTemplate_poweredByHTML(),$html); // legacy version of POWERED-BY
				$html = str_replace('##POWERED-BY##',zeroBSCRM_mailTemplate_poweredByHTML(),$html);   

	      

            #} Go
            if (!$return) { echo $html; exit(); }

        }  
        return $html;


} 
/* ======================================================
	/ ZBS Direct Emails - Generate HTML
   ====================================================== */




/* ======================================================
	ZBS Mail Delivery Tests
   ====================================================== */


	function zeroBSCRM_mailDelivery_generateTestHTML($return=true){

		        $templatedHTML = ''; $html = ''; $pWrap = '<p style="font-family:sans-serif;font-size:14px;font-weight:normal;margin:0;Margin-bottom:15px;">';

		        #} Get templated notify email
		        $templatedHTML = zeroBSCRM_mail_retrieveWrapTemplate('default');


		        #} Act
		        if (!empty($templatedHTML)){

			        	#} Actual body:
			        	$bodyHTML = '';

			        	$bodyHTML = "<div style='text-align:center'><h1>".__('Testing Mail Delivery Option',"zero-bs-crm")."</h1>";
			        	$bodyHTML .= '<p>'.__("This is a test email, sent to you by ZBS CRM. If you're recieving this loud and clear, it means your mail delivery setup has been successful, congratulations!","zero-bs-crm").'</p>';
						
						##WLREMOVE
						$bodyHTML .= '<p>'.__("Why not follow us on twitter to celebrate?","zero-bs-crm").'</p>';
						$bodyHTML .= '<p><a href="https://twitter.com/zerobscrm">@zerobscrm</a></p>';
						##/WLREMOVE

						$bodyHTML .= "</div>";

			        	$footerDeets = '<p>'.__("Sent from your friendly neighbourhood CRM.","zero-bs-crm").'</p>';
			        	$footerUnSub = '<p>'.__("Have a great day.","zero-bs-crm").'</p>';

			            #} replace the vars
			            $html = str_replace('###TITLE###','Testing Mail Delivery Option',$templatedHTML);
			            $html = str_replace('###MSGCONTENT###',$bodyHTML,$html);  
			            $html = str_replace('###FOOTERBIZDEETS###',$footerDeets,$html);  
			            $html = str_replace('##UNSUB-LINE##',$footerUnSub,$html);  
	           			$html = str_replace('###FOOTERUNSUBDEETS###',$footerUnSub,$html); // legacy version of UNSUB-DETAILS
						$html = str_replace('###POWEREDBYDEETS###',zeroBSCRM_mailTemplate_poweredByHTML(),$html); // legacy version of POWERED-BY
						$html = str_replace('##POWERED-BY##',zeroBSCRM_mailTemplate_poweredByHTML(),$html);   
						$html = str_replace('###FROMNAME###',zeroBSCRM_mailDelivery_defaultFromname(),$html);

			      

		            #} Go
		            if (!$return) { echo $html; exit(); }

		        }  

		        return $html;


	} 


/* ======================================================
	/ ZBS Mail Delivery Tests
   ====================================================== */




/* ======================================================
	ZBS Mail Templating General
   ====================================================== */

function zeroBSCRM_mailTemplate_poweredByHTML($type='html'){

	// if enabled
    $poweredBy = (int)zeroBSCRM_getSetting('emailpoweredby',true);

    if ($poweredBy == 1){

    	if ($type == 'html'){

			$poweredBytext = "";
			##WLREMOVE
			return 'Powered by <a href="http://zerobscrm.com" style="color:#3498db;text-decoration:underline;color:#999999;font-size:12px;text-align:center;text-decoration:none;">ZeroBSCRM.com</a>.';
			$poweredBytext = __('Powered by ZBS CRM', 'zero-bs-crm');
			##/WLREMOVE

			return $poweredBytext;

		} elseif ($type == 'text'){

			$poweredBytext = "";
			##WLREMOVE
			return 'Powered by http://zerobscrm.com';
			$poweredBytext = __('Powered by ZBS CRM', 'zero-bs-crm');
			##/WLREMOVE

			return $poweredBytext;


		}

	}

	return '';
}


function zeroBSCRM_mailTemplate_getHeaders($templateID = -1){

	if($templateID > 0){

		$mailTemplate = zeroBSCRM_mailTemplate_get($templateID);

        //headers being set...
		$headers = array('Content-Type: text/html; charset=UTF-8'); 

		//extra header settings..
		// We don't use these now, as mail is sent out properly via Mail Delivery
		//$headers[]  = 'From: '. esc_html($mailTemplate->zbsmail_fromname).' <'.sanitize_email($mailTemplate->zbsmail_fromaddress).'>';
		//$headers[]  = 'Reply-To: ' . sanitize_email($mailTemplate->zbsmail_replyto);
		// but we use this :) 
		if (isset($mailTemplate->zbsmail_bccto) && !empty($mailTemplate->zbsmail_bccto)) $headers[]  = 'Bcc: ' . sanitize_email($mailTemplate->zbsmail_bccto); 


	}else{
		$headers = array('Content-Type: text/html; charset=UTF-8'); 
	}

	return $headers;
}

// adapted from https://buttons.cm/
function zeroBSCRM_mailTemplate_emailSafeButton($url='',$str=''){

	return '<div><!--[if mso]>
	<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="'.$url.'" style="height:53px;v-text-anchor:middle;width:200px;" arcsize="8%" stroke="f" fillcolor="#49a9ce">
		<w:anchorlock/>
		<center>
	<![endif]-->
		<a href="'.$url.'"
	style="background-color:#49a9ce;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:700;line-height:53px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;">'.$str.'</a>
	<!--[if mso]>
		</center>
	</v:roundrect>
	<![endif]--></div>';

}


// replaces generic attrs in one place, e.g. loginlink, loginurl
function zeroBSCRM_mailTemplate_genericReplaces($html=''){

	global $zbs;

	// LOGIN

		$login_url = admin_url('admin.php?page='.$zbs->slugs['dash'] ); 

		// link
	    $html = str_replace('###LOGINLINK###','<a href="'.$login_url.'">'.__('Go to CRM','zero-bs-crm').'</a>',$html);

	    // centered button
	    $button = '<div style="text-align:center;margin:1em;margin-top:2em">'.zeroBSCRM_mailTemplate_emailSafeButton($login_url,__('Go to CRM','zero-bs-crm')).'</div>';
	    $html = str_replace('###LOGINBUTTON###',$button,$html);

	    // url
	    $html = str_replace('###LOGINURL###',$login_url,$html);

	// PORTAL

		$portal_url = zeroBS_portal_link();

		// link
	    $html = str_replace('###PORTALLINK###','<a href="'.$portal_url.'">'.$portal_url.'</a>',$html);

	    // centered button
	    $button = '<div style="text-align:center;margin:1em;margin-top:2em">'.zeroBSCRM_mailTemplate_emailSafeButton($portal_url,__('View Portal','zero-bs-crm')).'</div>';
	    $html = str_replace('###PORTALBUTTON###',$button,$html);

	    // url
	    $html = str_replace('###PORTALURL###',$portal_url,$html);



    return $html;

}

/* WH Notes:

	There was all this note-age from old vers:
			Customer Meta Translation - 2.90 
			#}PUT THE EMAIL THROUGH THE FILTERS (FOR THE #FNAME# NEW CUSTOMER TAGS do prepare_email_{trigger}_template
			#} WH: Let's do this as filters :)
	
	... but I think these funcs needed a bit of a clean up
	... should be backward compatible, and safe to stick to using the filter: zeroBSCRM_replace_customer_placeholders
	... MC2.0 uses this indirectly through 'zerobscrm_mailcamp_merge' and 'zerobscrm_mailcamp_merge_text'
	... so be careful with it

	... v3.0 I've made them DAL3 safe, not fully refactored as approaching deadline
*/
function zeroBSCRM_replace_customer_placeholders($html = '', $cID = -1, $contactObj = false){

	if ($cID > 0 && $html != ''){

		global $zbs;

		if (is_array($contactObj) && isset($contactObj['id']))
			$contact = $contactObj;
		else {
			if ($zbs->isDAL3())
				// v3.0
				$contact = $zbs->DAL->contacts->getContact($cID,array(
		            'withCustomFields'  => true,
		            // need any of these?
		            'withQuotes'        => false,
		            'withInvoices'      => false,
		            'withTransactions'  => false,
		            'withLogs'          => false,
		            'withLastLog'       => false,
		            'withTags'          => false,
		            'withCompanies'     => false,
		            'withOwner'         => false,
		            'withValues'        => false,
            ));
			else
				// pre v3.0
				$contact = zeroBS_getCustomerMeta($cID);
		}

		// replace all placeholders :)
		$newHTML = $html;
		foreach ($contact as $k => $v){
			$newHTML = str_replace('##CONTACT-'.strtoupper($k) . '##' ,$v, $newHTML);  
		}
		$html = $newHTML;

	}

    return $html;

}

add_filter( 'zerobscrm_quote_html_generate','zeroBSCRM_replace_customer_placeholders', 20, 2);

// as above, but replaces with 'demo data'
function zeroBSCRM_replace_customer_placeholders_demo($html = ''){
	if($html != ''){
		$cust_meta = zeroBS_getDemoCustomer();
		$newHTML = $html;
		foreach($cust_meta as $k => $v){
			$newHTML = str_replace('##CONTACT-'.strtoupper($k) . '##' ,$v, $newHTML);  
		}
		$html = $newHTML;
	}
    return $html;
}

/* ======================================================
	/ ZBS Mail Templating General
   ====================================================== */