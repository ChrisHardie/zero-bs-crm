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



function zbs_quote_pdf(){

    
	#} download flag
	if ( isset($_POST['zbs_quote_download_pdf'])  ) {

        #} THIS REALLLY needs nonces! For now (1.1.19) added this for you...
        if (!zeroBSCRM_permsQuotes()) exit();

        #} Check nonce
        if (!wp_verify_nonce( $_POST['zbs_quote_pdf_gen'], 'zbs-quote-pdf-gen' )) exit();

        global $plugin_page;

        #} PDF Install check: 
        zeroBSCRM_extension_checkinstall_pdfinv();

		#} require DOMPDF
		#} ONLY once it's needed
		require_once(ZEROBSCRM_PATH . 'includes/lib/dompdf-0-8-2/autoload.inc.php');


		#use Dompdf\Options;
		#use Dompdf\Dompdf;

		# rather than use "use" which has to be used outside a func, you can do this:
		#$dompdf = new Dompdf\Dompdf($options);


		#mikeaddnonce

		#} Check ID
		$quotePostID = -1;
		if (isset($_POST['zbs_quote_id']) && !empty($_POST['zbs_quote_id'])) $quotePostID = (int)sanitize_text_field($_POST['zbs_quote_id']);

		#} If user has no perms, or id not present, die
		if (!zeroBSCRM_permsQuotes() || empty($quotePostID)){
			die();
		}

		/* this'll kill anyone but admins
		if(!current_user_can('manage_options')){  //if the user isn't admin, die a slow death
			die(); 
		}
		*/

		$html = zeroBSCRM_retrieveQuoteTemplate('default');
        $content = zeroBS_getQuoteBuilderContent($quotePostID);

    
        /* switch the content */
        $html = str_replace('###QUOTECONTENT###',$content['content'],$html);


		$options = new Dompdf\Options();
		$options->set('isRemoteEnabled', TRUE);
        $options->set('defaultFont', 'SourceSansPro');

		$dompdf = new Dompdf\Dompdf($options);
		$contxt = stream_context_create([ 
		    'ssl' => [ 
		        'verify_peer' => FALSE, 
		        'verify_peer_name' => FALSE,
		        'allow_self_signed'=> TRUE
		    ] 
		]);
		$dompdf->setHttpContext($contxt);

        $dompdf->loadHtml($html,'UTF-8');
        $dompdf->set_paper('A4', 'portrait');

		$dompdf->render();

		$upload_dir = wp_upload_dir();
		#$user_dirname = $upload_dir['basedir'].'/'.$current_user->user_login .'/invoices/';
		
		#} WH changed this, might as well dump them in a central repo, but do delete them as you go... a username is not enough to hide them
		$zbsQuoteDir = $upload_dir['basedir'].'\/quotes\/';

		if ( ! file_exists( $zbsQuoteDir ) ) {
		    wp_mkdir_p( $zbsQuoteDir );
		}

        // get actual inv id, not post id :)
		//$file_to_save = $zbsInvoiceDir.$invoicePostID.'.pdf';

        if (empty($quoteID)) $quoteID = $quotePostID;
        
        $file_to_save = $zbsQuoteDir.$quoteID.'.pdf';
        
		//save the pdf file on the server
		file_put_contents($file_to_save, $dompdf->output());   //not sure if it needs to physically save first? 
		//print the pdf file to the screen for saving
		header('Content-type: application/pdf');
		header('Content-Disposition: attachment; filename="quote-'.$quoteID.'.pdf"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . filesize($file_to_save));
		header('Accept-Ranges: bytes');
		readfile($file_to_save);

		//delete the PDF file once it's been read (i.e. downloaded)
		unlink($file_to_save); 

		#mikeinvdelete
		die();
	}
}
#} WH: Eventually this into the admin_init func, we need to manage them centrally, not add loads of diff calls
add_action('admin_init','zbs_quote_pdf');