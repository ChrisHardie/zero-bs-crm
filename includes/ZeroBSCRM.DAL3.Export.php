<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V3.0
 *
 * Copyright 2019, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 24/05/2019
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

   // temp/mvp solution. Some fields need different labels for export!
   function zeroBSCRM_export_fieldReplacements(){
		
		return array(

				   				// Contacts/co
				   				'wpid' => __('WordPress ID','zero-bs-crm'),
				   				'tw' => __('Twitter Handle','zero-bs-crm'),
				   				'fb' => __('Facebook Page','zero-bs-crm'),
				   				'li' => __('LinkedIn','zero-bs-crm'),
				   				'avatar' => __('Avatar','zero-bs-crm'),
				   				'created' => __('Created Date','zero-bs-crm'),
				   				'lastupdated' => __('Last Updated Date','zero-bs-crm'),
				   				'lastcontacted' => __('Last Contacted Date','zero-bs-crm'),

				   				// Quotes
				   				'id_override' => __('Reference','zero-bs-crm'),
				   				'currency' => __('Currency','zero-bs-crm'),
				   				'hash' => __('Hash','zero-bs-crm'),
				   				'lastviewed' => __('Last Viewed','zero-bs-crm'),
				   				'viewed_count' => __('Viewed Count','zero-bs-crm'),
				   				'accepted' => __('Accepted Date','zero-bs-crm'),
				   				'acceptedsigned' => __('Signed Date','zero-bs-crm'),
				   				'acceptedip' => __('Signed via IP','zero-bs-crm'),

				   				// inv
				   				'status' => __('Status','zero-bs-crm'),

				   				// trans
				   				'origin' => __('Origin','zero-bs-crm'),
				   				'customer_ip' => __('Customer IP','zero-bs-crm'),

				   			);
   }

   function zeroBSCRM_export_blockedFields(){

   		return array(

   			// global
   			'zbs_site','zbs_team',

   			// quotes
   			'template','content','notes','send_attachments',

   			// inv
   			'pay_via','logo_url','pdf_template','portal_template','email_template','invoice_frequency','address_to_objtype','allow_partial','allow_tip','hours_or_quantity',

   			// trans
   			'parent', 'taxes', 'shipping_taxes'

   			);

   }


/* ======================================================
  Export Tools
   ====================================================== */

   // based on what's posted, it'll show 'export routine'
   function zeroBSCRM_page_exportRecords(){

   		global $zbs;

   		$drawnUI = false;

   		if ($zbs->isDAL3() && zeroBSCRM_permsExport()){

	   		// == Retrieve objs to export =========

	   			// v3.0 is basic mvp, just exports based on ID's
	   			// 3.0+ should take list view params + regen
	   			$objTypeToExport = 1; // default to contact (or anything passed in get, below)
	   			$objToExportIDs = array();

	   			// got objtype (get)?
	   			if (isset($_GET) && isset($_GET['zbstype'])) {

	   				$potentialObjTypeStr = sanitize_text_field( $_GET['zbstype'] );
	   				if ($potentialObjTypeStr == 'customer') $potentialObjTypeStr = 'contact'; // legacy fix
	   				$potentialObjTypeToExport = $zbs->DAL->objTypeID($potentialObjTypeStr);
	   				if ($zbs->DAL->isValidObjTypeID($potentialObjTypeToExport)) $objTypeToExport = $potentialObjTypeToExport;

	   			}

	   			// got objtype (post)?
	   			if (isset($_POST) && isset($_POST['objtype'])) {

	   				$potentialObjTypeStr = sanitize_text_field( $_POST['objtype'] );
	   				if ($potentialObjTypeStr == 'customer') $potentialObjTypeStr = 'contact'; // legacy fix
	   				$potentialObjTypeToExport = $zbs->DAL->objTypeID($potentialObjTypeStr);
	   				if ($zbs->DAL->isValidObjTypeID($potentialObjTypeToExport)) $objTypeToExport = $potentialObjTypeToExport;

	   			}

	   			// got ID's
	   			if (isset($_POST) && isset($_POST['ids'])) {

	   				$potentialIDCSV = sanitize_text_field( $_POST['ids'] );
	   				$potentialIDs = explode(',', $potentialIDCSV);

	   				foreach ($potentialIDs as $potentialID){

	   					$i = (int)$potentialID;

	   					if ($i > 0 && !in_array($i, $objToExportIDs)) $objToExportIDs[] = $i;

	   				}

	   			}

	   			// export as str - allows later override (e.g. 'all')
	   			if (is_array($objToExportIDs) && count($objToExportIDs) > 0)
	   				$objToExportIDsStr = implode(',',$objToExportIDs);
	   			else 
	   				$objToExportIDsStr = 'all'; // :o HUGE?


	   		// == / Retrieve objs to export =======


	   		if ($objTypeToExport > 0 && (count($objToExportIDs) > 0 || $objToExportIDsStr == 'all') && zeroBSCRM_permsObjType($objTypeToExport)){ 

		   		// == Prep language + vars ============

	   				// what fields do we have to export?
		   			$fieldsAvailable = zeroBSCRM_export_produceAvailableFields($objTypeToExport,true);

					// obj layer
				   	$objDALLayer = $zbs->DAL->getObjectLayerByType($objTypeToExport);

			   		// count objs
			   		if ($objToExportIDsStr == 'all') 
			   			$objCount = $objDALLayer->getFullCount();
			   		else
			   			$objCount = count($objToExportIDs);

		   			// language
		   			$objTypeSingular = $zbs->DAL->typeStr($objTypeToExport,false);
		   			$objTypePlural = $zbs->DAL->typeStr($objTypeToExport,true);

		   			// basic label 'contact/contacts'
		   			$exportTypeLabel = $objTypeSingular;
		   			if ($objCount > 1) $exportTypeLabel = $objTypePlural;

		   		// == / Prep language + vars ===========


		   		// == UI ==============================

		   			// no need for legacy err dialog, this stops
		   			$drawnUI = true;

		   			// good to draw ?>
		   			<div id="zbs-export-wrap"><form method="post">

		   				<?php /* here we output the pre-requisites for the export (which is caught on init) */ ?>
		   				<input type="hidden" name="zbs-export-request" value="<?php echo time(); ?>" />
   						<?php wp_nonce_field( 'zbs_export_request', 'zbs-export-request-nonce' ); ?>
		   				<input type="hidden" name="zbs-export-request-objtype" value="<?php echo $objTypeToExport; ?>" />
		   				<input type="hidden" name="zbs-export-request-objids" value="<?php echo $objToExportIDsStr; ?>" />

			   			<h2><?php echo __('Export','zero-bs-crm').' '.zeroBSCRM_prettifyLongInts($objCount).' '.$exportTypeLabel; ?></h2>

			   			<div class="ui segment" id="zbs-export-filetype-wrap">
			   				<i class="file alternate outline icon"></i> <?php _e('Export as .CSV file','zero-bs-crm'); // later offer choice (excel variant? Outlook addressbook?) ?>
			   			</div>	


			   			<div class="ui segments" id="zbs-export-fields-wrap">
			   				<div class="ui segment header">
			   					<?php

			   					// header
			   					_e('Export Fields:','zero-bs-crm'); 

		   						// select all ?>
								<button type="button" class="ui blue mini button right floated all" id="zbs-export-select-all" ><span class="all"><i class="object ungroup icon"></i> <?php _e('Deselect','zero-bs-crm'); ?></span><span class="none" style="display:none"><i class="object group icon"></i> <?php _e('Select All','zero-bs-crm'); ?></span></button>

			   				</div>
				   			<div class="ui segment" id="zbs-export-fields">
				   				<?php

				   					if (count($fieldsAvailable) > 0){

				   						$lastArea = ''; $openArea = false;

							   			// if got type, retrieve fields to (potentially) export.
					   					foreach ($fieldsAvailable as $fK => $field){

					   						/* Semantic checkboxes bugging, working on MVP so simplified for now.
					   						?>
					   						<div>
						   						<div class="ui checkbox">
												  <input type="checkbox" id="zbs-export-field-<?php echo $fK; ?>"  name="zbs-export-field-<?php echo $fK; ?>">
												  <label><?php echo $fLabel; ?></label>
												</div>
											</div>
											<?php */

											// area grouping
											if (isset($field['area']) && $field['area'] !== $lastArea){

												// close any open areas
												if ($openArea) {
													echo '</div>';
													$openArea = false;
												}

												if ($field['area'] !== ''){

													// open 'area'
													$openArea = true; $lastArea = $field['area'];
													?><div class="ui segment"><div class="ui small header"><?php echo $field['area']; ?></div><?php

												}


											}

					   						?><input type="checkbox" id="zbs-export-field-<?php echo $fK; ?>" name="zbs-export-field-<?php echo $fK; ?>" checked="checked" value="<?php echo $fK; ?>" /><label for="zbs-export-field-<?php echo $fK; ?>"><?php echo $field['label']; ?></label><br /><?php

					   					}

										// close any open areas
										if ($openArea) echo '</div>';

					   				} else {

					   					// nope.
					   					echo zeroBSCRM_UI2_messageHTML('warning',__('No fields to export','zero-bs-crm'),__('There were no fields found that we can export.','zero-bs-crm').'</a>','','zbs-legacy-export-tools-dialog');

					   				}

				   				?>
				   			</div>
				   		</div>

				   		<div class="ui divider"></div>

			   			<button class="ui green button" type="submit"><i class="download icon"></i> <?php _e('Export','zero-bs-crm'); ?></button>

			   			<script type="text/javascript">

			   				jQuery(document).ready(function(){

			   					jQuery('#zbs-export-select-all').click(function(){

			   						if (jQuery(this).hasClass('none')){

			   							// check all
        								jQuery('input:checkbox',jQuery('#zbs-export-fields')).attr('checked','checked');

			   							jQuery(this).removeClass('none');
			   							jQuery('span.none').hide();
			   							jQuery('span.all').show();
			   							jQuery(this).addClass('all');

			   						} else {

			   							// deselect all
        								jQuery('input:checkbox',jQuery('#zbs-export-fields')).removeAttr('checked');

			   							jQuery(this).removeClass('all');
			   							jQuery('span.all').hide();
			   							jQuery('span.none').show();
			   							jQuery(this).addClass('none');

			   						}


			   					});

			   				});

			   			</script>

		   			</form></div><?php

		   		// == / UI ============================

		   	}

	   	} // / is DAL 3 + has perms


   		// LEGACY! (or error input)
   		if (!$drawnUI){

			// nothing passed to it, for now, point to legacy export tool ?>
		   	<div id="zbs-export-wrap">

	   			<h2><?php _e('Export Tools','zero-bs-crm'); ?></h2>

	   			<?php echo zeroBSCRM_UI2_messageHTML('warning',__('Nothing found to export','zero-bs-crm'),__('Nothing found to export','zero-bs-crm').'<br />'.__('Do you want to use the legacy export tool?','zero-bs-crm').'<br /><a href="'.admin_url('admin.php?page='.$zbs->slugs['legacy-zbs-export-tools']).'" class="ui button">'.__('Legacy Export Tools','zero-bs-crm').'</a>','','zbs-legacy-export-tools-dialog'); ?>

	   		</div>

   		<?php

   		}

   }

/* ======================================================
  / Export Tools
   ====================================================== */


/* ======================================================
   Export Tools -> Actual Export File


		   				<input type="hidden" name="zbs-export-request" value="<?php echo time(); ?>" />
   						<?php wp_nonce_field( 'zbs_export_request', 'zbs-export-request-nonce' ); ?>
		   				<input type="hidden" name="zbs-export-request-objtype" value="<?php echo $objTypeToExport; ?>" />
		   				<input type="hidden" name="zbs-export-request-objids" value="<?php echo $objToExportIDsStr; ?>" />
id="zbs-export-field-<?php echo $fK; ?>"
   ====================================================== */

	function zeroBSCRM_export_processExport(){

		global $zbs;

		// Check if valid posted export request
		// ++ nonce verifies
		// ++ is admin side
		// ++ is our page
		// ++ is DAL3
		// ++ has perms
		if ( 
			isset($_POST) && isset($_POST['zbs-export-request']) 
			&& 
			isset( $_POST['zbs-export-request-nonce'] ) && wp_verify_nonce( $_POST['zbs-export-request-nonce'], 'zbs_export_request' ) 
			&& 
			is_admin()
			&& 
			isset($_GET['page']) && $_GET['page'] == $zbs->slugs['zbs-export-tools']
			&&
   			$zbs->isDAL3() 
   			&& 
   			zeroBSCRM_permsExport()
			){

				$objTypeID = -1; $objIDArr = array(); $fields = array(); $extraParams = array('all'=>false);

				// == Param Retrieve ================================================

				// check obj type
	   			if (isset($_POST['zbs-export-request-objtype'])) {

	   				$potentialObjTypeID = (int)sanitize_text_field( $_POST['zbs-export-request-objtype'] );
	   				if ($zbs->DAL->isValidObjTypeID($potentialObjTypeID)) $objTypeID = $potentialObjTypeID;

	   			}

				// check id's
	   			if (isset($_POST['zbs-export-request-objids'])) {

	   				$potentialIDStr = sanitize_text_field( $_POST['zbs-export-request-objids'] );
	   				$potentialIDs = explode(',', $potentialIDStr);

	   				foreach ($potentialIDs as $potentialID){

	   					$i = (int)$potentialID;

	   					if ($i > 0 && !in_array($i, $objIDArr)) $objIDArr[] = $i;

	   				}

	   			}

	   			// catch extra params
	   			if (isset($potentialIDStr) && $potentialIDStr == 'all') $extraParams['all'] = true;


				// retrieve fields
				if (is_array($_POST)) foreach ($_POST as $k => $v){

					// retrieve all posted pre-keys
					if (substr($k,0,strlen('zbs-export-field-')) == 'zbs-export-field-'){

						// is *probably* one of ours (doesn't guarantee)
						$fieldKey = sanitize_text_field( $v );

						// some generic replacements:
						if ($fieldKey == 'ID') $fieldKey = 'id';
						if ($fieldKey == 'owner') $fieldKey = 'zbs_owner';

						// add
						if (!in_array($fieldKey, $fields)) $fields[] = $fieldKey;

					}

				}

	   			// == / Param Retrieve =============================================


	   			// == FINAL CHECKS? ================================================
	   			// Got acceptable objtype
	   			// Got fields to export
	   			// Got ID's to export (or all)
	   			if (
	   				$objTypeID > 0 && 
	   				is_array($fields) && count($fields) > 0
	   				&& 
	   				((is_array($objIDArr) && count($objIDArr) > 0) || $extraParams['all'])){

	   				// == obj type loading =========================================

			   			// obj layer
					   	$objDALLayer = $zbs->DAL->getObjectLayerByType($objTypeID);

		   				// what fields do we have to export?
			   			$fieldsAvailable = zeroBSCRM_export_produceAvailableFields($objTypeID);

			   			// language
			   			$objTypeSingular = $zbs->DAL->typeStr($objTypeID,false);
			   			$objTypePlural = $zbs->DAL->typeStr($objTypeID,true);

			   			// basic label 'contact/contacts'
			   			$exportTypeLabel = $objTypeSingular;
			   			if (count($objIDArr) > 1) $exportTypeLabel = $objTypePlural;

			   			// == obj type loading =========================================

						// general
						$filename = 'exported-'.$objTypePlural.'-'.date("d-m-Y_g-i-a").'.csv'; 

					// == file start ===================================================

						// send header
						header('Content-Type: text/csv; charset=utf-8');		
						header('Content-Disposition: attachment; filename= ' . $filename);

						// open output
						$output = fopen('php://output', 'w');

					// == / file start =================================================

		   			// == file gen =====================================================
				

						// column headers
						$columnHeaders = array(); foreach ($fields as $fK) {
							$label = $fK;
							if (isset($fieldsAvailable[$fK])) $label = $fieldsAvailable[$fK];
							$columnHeaders[] = $label;
						}
						fputcsv($output, $columnHeaders);

						// actual export lines

							// retrieve objs
							if ($extraParams['all']){
								$availObjs = $objDALLayer->getAll($objIDArr);
							} else {
								$availObjs = $objDALLayer->getIDList($objIDArr);
							}
							
							if (is_array($availObjs)) foreach ($availObjs as $obj){

								// per obj
								$objRow = array();
								foreach ($fields as $fK) {

									$v = ''; // default (means always right col count)
									if (isset($obj[$fK])) $v = $obj[$fK];

									// date objs use _date (which are formatted)
									if (isset($obj[$fK.'_date'])) $v = $obj[$fK.'_date'];
									// custom field dates too:
									if (isset($obj[$fK.'_cfdate'])) $v = $obj[$fK.'_cfdate'];

									// catch legacy secaddr_addr1 issues. (only for contacts)
									// blurgh.
									// secaddr1 => secaddr_addr1
									if ($objTypeID == ZBS_TYPE_CONTACT && substr($fK,0,3) == 'sec'){
										if (isset($obj[str_replace('sec','secaddr_',$fK)])) $v = $obj[str_replace('sec','secaddr_',$fK)];
									}

									// if -1 kill
									if ($v == -1) $v = '';

									$objRow[] = $v;

								} // / foreach field in each obj row

								// output row
								fputcsv($output, $objRow);

							} // / foreach obj

					// == / file gen ===================================================

					// == file fini ====================================================

						// send end
						fclose($output);
						exit();

					// == / file fini ==================================================

			} // / final checks

		} // / Check if valid posted export request

	}
	add_action('zerobscrm_post_init','zeroBSCRM_export_processExport');


// retrieves a formatted obj field array of what's actually 'allowed' to be exported
// Tested a few ways of achieving this, given the $globalfield limbo that v3 is in (legacy)
// - Using the $globalField method was unreliable, so rolled fresh using db-driven custom fields
// ... this could be rolled back into how we do $globalFields in road to v4 
// gh-253
function zeroBSCRM_export_produceAvailableFields($objTypeToExport=false,$includeAreas=false){

	global $zbs;

	// def
	$fieldsAvailable = array();

	// obj layer
   	$objDALLayer = $zbs->DAL->getObjectLayerByType($objTypeToExport);

   	// fields avail to export
   	// just base fields: $objLayerFields = $objDALLayer->objModel();
   	// base fields + custom fields:
   	$objLayerFields = $objDALLayer->objModelIncCustomFields(); 

   		// process
   		if (is_array($objLayerFields)){

   			$blockedFields = zeroBSCRM_export_blockedFields(); //,'zbs_owner'
   			$relabel = zeroBSCRM_export_fieldReplacements();

   			foreach ($objLayerFields as $fieldKey => $field){

   				// cf's need to alter this, so we var
   				$fieldKeyOutput = $fieldKey;

   				if (!in_array($fieldKey, $blockedFields)){

   					// simplify for output
   					if (!array_key_exists($fieldKey,$fieldsAvailable)){

   						/* e.g. : 
   						    [email] => Array
						        (
						            [fieldname] => zbsc_email
						            [format] => str
						            [input_type] => email
						            [label] => Email
						            [placeholder] => e.g. john@gmail.com
						            [essential] => 1
						        )

						    Note: 3.0.7+ also includes custom fields:

						    [test] => Array
						        (
						            [0] => text
						            [1] => test
						            [2] => test
						            [3] => test
						            [custom-field] => 1
						        )
						*/

						$label = $fieldKey; $area = '';

						if (!isset($field['custom-field'])){

							// Non CF stuff:

							// label
							if (isset($field['label'])) $label = $field['label'];

							// relabel?
							if (isset($relabel[$fieldKey])) $label = $relabel[$fieldKey];

							// addresses/areas (append)
							if (isset($field['area'])) {
								if (!$includeAreas) $label .= ' ('.$field['area'].')';
								$area = $field['area'];
							}

							// one exception:
							if ($label == 'zbs_owner') $label = __('Owner','zero-bs-crm');

						} else {

							// prefix $fieldKeyOutput
							$fieldKeyOutput = 'cf-'.$fieldKeyOutput;

							// Custom field passing
							if (isset($field[1])) $label = $field[1];

							// addresses/areas (append)
							if (isset($field['area'])) {
								if (!$includeAreas) $label .= ' ('.$field['area'].')';
								$area = $field['area'];
							}

							if ($area == '') $area = __('Custom Fields','zero-bs-crm');

						}

						if ($includeAreas)
							// with area
   							$fieldsAvailable[$fieldKey] = array('label'=>$label,'area'=>$area);
   						else
   							// simpler
   							$fieldsAvailable[$fieldKey] = $label;
   					}

   				}

   			}

   		}


   	return $fieldsAvailable;

}

/* ======================================================
   / Export Tools -> Actual Export File
   ====================================================== */
