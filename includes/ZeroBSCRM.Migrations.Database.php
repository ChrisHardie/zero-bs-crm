<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V3.0
 *
 * Copyright 2019 Zero BS Software Ltd.
 *
 * Date: 14/06/2019
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */


/* ======================================================
	DB MIGRATION HELPER FUNCS
	- This file stores MAJOR db migration helper funcs
	... including DAL1 -> DAL2 (253/270)
	... and DAL2 -> DAL3 (300)
   ====================================================== */

// this backs up a wp post pre-migrating
// used in v2.0 and v3.0 migrations
function zeroBSCRM_migration_backupPost($postObj=false,$zbsID=-1){

	// ZBSID can stay as -1 for now, because we have linking meta at the end of each record process
	// ... better that we back these up before we do any work :) safer... so forgo the id storage here..

	if ($postObj !== false && isset($postObj->ID)){

		global $wpdb,$ZBSCRM_t;

			// backup post
			$bk = $wpdb->insert( 
						$ZBSCRM_t['dbmigrationbkposts'], 
						array( 

							// fields
							  //'ID'
  
							  'wpID' => $postObj->ID,
							  'zbsID' => $zbsID,

							  'post_author' => $postObj->post_author,
							  'post_date' => $postObj->post_date,
							  'post_date_gmt' => $postObj->post_date_gmt,
							  'post_content' => $postObj->post_content,
							  'post_title' => $postObj->post_title,
							  'post_excerpt' => $postObj->post_excerpt,
							  'post_status' => $postObj->post_status,
							  'comment_status' => $postObj->comment_status,
							  'ping_status' => $postObj->ping_status,
							  'post_password' => $postObj->post_password,
							  'post_name' => $postObj->post_name,
							  'to_ping' => $postObj->to_ping,
							  'pinged' => $postObj->pinged,
							  'post_modified' => $postObj->post_modified,
							  'post_modified_gmt' => $postObj->post_modified_gmt,
							  'post_content_filtered' => $postObj->post_content_filtered,
							  'post_parent' => $postObj->post_parent,
							  'guid' => $postObj->guid,
							  'menu_order' => $postObj->menu_order,
							  'post_type' => $postObj->post_type,
							  'post_mime_type' => $postObj->post_mime_type,
							  'comment_count' => $postObj->comment_count,


						), 
						array( // field data types

							'%d',  
							'%d',  

							'%d',
							'%s',  
							'%s',  
							'%s', 
							'%s',
							'%s',  
							'%s',  
							'%s', 
							'%s',
							'%s',  
							'%s',  
							'%s', 
							'%s',
							'%s',  
							'%s',  
							'%s', 
							'%d',
							'%s',  
							'%d',  
							'%s', 
							'%s',
							'%d'  
						) );


			// backup all it's meta

			#} Prep & run query
			$queryObj = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}postmeta where {$wpdb->prefix}postmeta.post_id = %d",array($postObj->ID));
			$potentialMeta = $wpdb->get_results($queryObj, OBJECT);
			
			if (isset($potentialMeta) && is_array($potentialMeta) && count($potentialMeta) > 0) {

				foreach ($potentialMeta as $meta){

					// bk
					$bkMeta = $wpdb->insert( 
								$ZBSCRM_t['dbmigrationbkmeta'], 
								array( 

									// fields
									//'meta_id'

									'wpID' => $meta->meta_id,
									'zbsID' => $zbsID, // not used

									'post_id' => $meta->post_id,
									'meta_key' => $meta->meta_key,
									'meta_value' => $meta->meta_value


								), 
								array( // field data types

									'%d',  
									'%d',  

									'%d',
									'%s',  
									'%s'

								) );

				}

			} // else no meta

	} 


	return false;

}

function zeroBSCRM_db253migrateContacts($count=5){

		// DISABLE INTERNAL AUTOMATOR WHILE RUNNING (stop create logs etc)
		global $zbs; $zbs->internalAutomatorBlock = true;

    	// should have rights, proceed
		global $wpdb,$ZBSCRM_t,$zbsCustomerFields;
		$processedKey = 'zbsmig253';

    	// GET X Contacts to process
    	$count = (int)$count; if ($count <= 0) $count = 5;
    	$objType = 1; $perRun = $count; $dbug = false;

    	// Check backup tables exist/create (backup as we go)
    	if (count($wpdb->get_results("SHOW TABLES LIKE '".$ZBSCRM_t['dbmigrationbkposts']."'")) < 1) zeroBSCRM_createTables();

		// map of fields:
		$fieldsToMap = array('email' => 'email',
							'status' => 'status',
							'prefix' => 'prefix',
							'fname' => 'fname',
							'lname' => 'lname',
							'addr1' => 'addr1',
							'addr2' => 'addr2',
							'city' => 'city',
							'county' => 'county',
							'country' => 'country',
							'postcode' => 'postcode',
							'secaddr_addr1' => 'secaddr1',
							'secaddr_addr2' => 'secaddr2',
							'secaddr_city' => 'seccity',
							'secaddr_county' => 'seccounty',
							'secaddr_country' => 'seccountry',
							'secaddr_postcode' => 'secpostcode',
							'hometel' => 'hometel',
							'worktel' => 'worktel',
							'mobtel' => 'mobtel');


		// custom fields - calculate by working out what's not mapped
		// ... and add to new system if needed :)
		$customFields = array(); $db2CustomFields = $zbs->DAL->getActiveCustomFields(array('objtypeid'=>$objType));
		$cfTranslatedKeys = array(); // cf1 => source
		
		if (!is_array($db2CustomFields)) $db2CustomFields = array();

		if (count($zbsCustomerFields) > 0) {
			foreach ($zbsCustomerFields as $fieldKey => $fieldDeets){

				if ($dbug) echo 'checking customfield match ('.$fieldKey.'): '.(!array_key_exists($fieldKey, $fieldsToMap)).'<br />';
				if (!array_key_exists($fieldKey, $fieldsToMap)){

					// Make a CF key from label
					$cfKey = $zbs->DAL->makeSlug($fieldDeets[1]);
					// store translation
					$cfTranslatedKeys[$fieldKey] = $cfKey;

					// must be CF! (notes will show up here)
					//$customFields[$fieldKey] = $fieldDeets;
					$customFields[] = $fieldKey;

					// check exists in DB2
					if (!isset($db2CustomFields[$cfKey])){

						// add it + update
						$db2CustomFields[$cfKey] = $fieldDeets;
						$zbs->DAL->updateActiveCustomFields(array('objtypeid'=>$objType,'fields'=>$db2CustomFields));
						if ($dbug) echo 'adding customfield: '.$cfKey.'<br />';
				
					}

				}

			}
		}


    		// Build query (NOT USING any DAL funcs, as migrating, simpler)
			$args = array (
				'post_type'              => 'zerobs_customer',
				'post_status'            => 'publish',
				'posts_per_page'         => $perRun,
				'order'                  => 'ASC',
				'orderby'                => 'post_date',

					// this is our 'processed' flag
					'meta_query' => array(
						array(
								'key'	=> $processedKey,
								'value' => '',
								'compare' => 'NOT EXISTS'
								)
						)

			); 

			// retrieve
			$toMigrate = get_posts( $args );

			if ($dbug) echo 'Got '.count($toMigrate).' contacts to migrate<br />';

			// BEGIN
			foreach ($toMigrate as $customerHeader){

				// Backup original post + all it's meta
				zeroBSCRM_db253migrationBackupPost($customerHeader);

				// Check + block autodrafts/bug outs + SKIP PROCESSING!
				if ($customerHeader->post_title != 'Auto Draft'){

					// Copy main record
	 				$fullContact = zeroBS_getCustomer($customerHeader->ID,true,true,true);
					
					if ($dbug){ echo '<div style="clear:both;margin-top:30px;margin-left:20px;padding-left:20px;border-left:3px solid green">Starting Contact AKA ('.$fullContact['id'].' '.$fullContact['name'].')</div>'; }

	 				//print_r($fullContact); exit();
	 				/* Went through all typical/observable meta keys for a contact + migrated everything here
						- following were ignored:
							- zbs_obj_ext_api + zbs_customer_ext_api (concerned with external source - api, but migrated through 2.41)

	 				*/

	 					// assigned to? (passed below, outside $data array)
						$ownerID = get_post_meta($fullContact['id'], 'zbs_owner', true);
						$newContactOwner = -1; if (!empty($ownerID)) $newContactOwner = $ownerID;

	 					// build 
	 					$newContact = array(

											'email' => '', // Unique Field ! 
											'status' => '',
											'prefix' => '',
											'fname' => '',
											'lname' => '',
											'addr1' => '',
											'addr2' => '',
											'city' => '',
											'county' => '',
											'country' => '',
											'postcode' => '',
											'secaddr1' => '',
											'secaddr2' => '',
											'seccity' => '',
											'seccounty' => '',
											'seccountry' => '',
											'secpostcode' => '',
											'hometel' => '',
											'worktel' => '',
											'mobtel' => '',

											'tw'	=> '',
											'fb'	=> '',
											'li'	=> '',

											'wpid' 	=> -1,
											'avatar' => '',

											// Note Custom fields may be passed here, but will not have defaults so check isset()

											'tags' => -1, // if this is an array of ID's, they'll REPLACE existing tags against contact

											'externalSources' => -1, // if this is an array(array('source'=>src,'uid'=>uid),multiple()) it'll add :)

											'companies' => -1, // array of co id's :)


											// wh added for later use.
											'lastcontacted' => -1,
											// allow this to be set for MS sync etc.
											'created' => -1,

									);

							// just manually move over for now
							if (isset($fullContact['meta']) && is_array($fullContact['meta'])){

								// shorthand
								$fcm = $fullContact['meta'];

								foreach ($fieldsToMap as $db1Field => $db2Field){

									//if (isset($fcm['email'])) $newContact['email'] = $fcm['email'];
									if (isset($fcm[$db1Field])) $newContact[$db2Field] = $fcm[$db1Field];

								}


							}

							// custom fields - migration of actual field settings is done above (head of this func)
							if (count($customFields) > 0){

								// shorthand
								$fcm = $fullContact['meta'];

								foreach ($customFields as $fieldKey){

									// simply dump into new data(arr) - db2 deals with it :)
									// this function will also add NOTES as a custom field above, for those moving over from db1

									// note, this now uses slugified keys, so get correct key:
									$cfKey = $cfTranslatedKeys[$fieldKey];
									if (isset($fcm[$fieldKey])) $newContact[$cfKey] = $fcm[$fieldKey];

								}
							}

							// created
							$newContact['created'] = strtotime($fullContact['created']);

							// company assignment
							$companyAssigned = get_post_meta($fullContact['id'],'zbs_company',true);
							if (isset($companyAssigned) && $companyAssigned > 0) $newContact['companies'] = array($companyAssigned);


							// WP User 
							// note: found that we're using two diff vars here throughout:
							// zbs_wp_user_id and zbs_portal_wpid
							// renamed all to zbs_wp_user_id
							// .. but here not sure of state, so if can't find in one, look in other
							// ... after DB2 migration, will only ever use DB stored ver, so no issue there
							$wpID = get_post_meta($fullContact['id'],'zbs_wp_user_id',true);
							if (isset($wpID) && $wpID > 0) $newContact['wpid'] = $wpID;
							// try other?
							if ($newContact['wpid'] == -1){
								$wpID = get_post_meta($fullContact['id'],'zbs_portal_wpid',true);
								if (isset($wpID) && $wpID > 0) $newContact['wpid'] = $wpID;
							}



							// Avatar
							if (has_post_thumbnail( $fullContact['id'] )){

								$thumb_urlArr = wp_get_attachment_image_src( get_post_thumbnail_id($fullContact['id'], 'single-post-thumbnail'));
								$thumb_url = ''; if (is_array($thumb_urlArr)) $thumb_url = $thumb_urlArr[0];

								if (!empty($thumb_url)) $newContact['avatar'] = $thumb_url;
							}


							// Social Profiles
			 				$socialProfiles = get_post_meta($fullContact['id'],'zbs_customer_socials',true);
			 				if ($dbug){ echo 'Social Profiles: <pre>'; print_r($socialProfiles); echo '</pre>';}
			 				if (isset($socialProfiles['tw']) && !empty($socialProfiles['tw'])) $newContact['tw'] = $socialProfiles['tw'];
			 				if (isset($socialProfiles['fb']) && !empty($socialProfiles['fb'])) $newContact['fb'] = $socialProfiles['fb'];
			 				if (isset($socialProfiles['li']) && !empty($socialProfiles['li'])) $newContact['li'] = $socialProfiles['li'];

							// Tags
							$tags = wp_get_object_terms($fullContact['id'],'zerobscrm_customertag',array('order' => 'ASC','orderby' => 'name'));
							// cycle through + add to db if not present
							$tagIDs = array(); if (count($tags) > 0) {
								foreach ($tags as $t){

									$potentialID = (int)$zbs->DAL->getTag(-1,array(
										'objtype'	=> $objType, // contact
										'name' 		=> $t->name,
										'onlyID'	=> true,
										'ignoreowner' => true
										));

									if (empty($potentialID)){

										// add it
										$newTagID = $zbs->DAL->addUpdateTag(array(
											'data' => array(
												'objtype' 		=> $objType,
												'name' 			=> $t->name,
												'slug' 			=> $t->slug,
												'owner'			=> -1 // for now, tags = no owner :)//$newContactOwner
											)));

										if (!in_array($newTagID, $tagIDs)){

										 	$tagIDs[] = $newTagID;

										}


									} else if (!in_array($potentialID, $tagIDs)){

									 	$tagIDs[] = $potentialID;

									}

								}
							}
							$newContact['tags'] = $tagIDs;

							// External sources
							$extSource = get_post_meta($fullContact['id'],'zbs_external_sources',true);
							if (is_array($extSource)){

								$externalSources = array();
								if (isset($extSource['source']) && !empty($extSource['source'])
									&& isset($extSource['uid']) && !empty($extSource['uid'])){
									$externalSources[] = array('source'=>$extSource['source'],'uid'=>$extSource['uid']);
								} 

								// catch any secondaries :)
								// MEH, let em die :)

								if (is_array($externalSources) && count($externalSources) > 0) $newContact['externalSources'] = $externalSources;

							}

							// LAST CONTACTED
							$lastcontacted = -1; $latestContactLog = zeroBSCRM_getMostRecentLog($fullContact['id'],true,array('Call','Email'));
							if (isset($latestContactLog) && is_array($latestContactLog) && isset($latestContactLog['created'])){
								$lastcontacted = strtotime($latestContactLog['created']);
							}
							$newContact['lastcontacted'] = $lastcontacted;

						
	 					// NEW DB Obj add:
	 					$newCID = $zbs->DAL->contacts->addUpdateContact(array('owner'=>$newContactOwner,'data' => $newContact,'silentInsert'=>true));


						if ($dbug){ echo 'Added: '.$newCID.' (owned:'.$newContactOwner.'):<pre>'; print_r($newContact); echo '</pre><br />'; }


	 				// PORTAL DISABLEd/ENABLED (meta translation, basically)
	 				$currentPortalSetting = get_post_meta($fullContact['id'],'zbs_portal_disabled',true);
	 				if (!empty($currentPortalSetting) && $currentPortalSetting !== false) $zbs->DAL->updateMeta($objType,$newCID,'portal_disabled',true);
	 				// remove prev option? 

					// Copy Quotes (re-link to new db no)
					if (is_array($fullContact['quotes']) && count($fullContact['quotes']) > 0){

						foreach ($fullContact['quotes'] as $quote){

							// ID + ZBS ID -> link to new db obj
							update_post_meta($quote['id'],'zbs_customer_quote_customer',$newCID);
							// also store prev link (backup!)
							update_post_meta($quote['id'],'zbs_cust_quo_predb2',$fullContact['id']);

							// this covers where, in (woosync?) mike was using 2 vars, not sure why
							$oldWayMeta = get_post_meta( $quote['id'], 'customer', true );
							if (!empty($oldWayMeta)){
								update_post_meta($quote['id'],'customer',$newCID);
							}

							if ($dbug){ echo 'Relinked quote id '.$quote['id'].' from '.$fullContact['id'].' to '.$newCID.'<br />'; }
		

						}
					}

					// Copy Invoices (re-link to new db no)
					if (is_array($fullContact['invoices']) && count($fullContact['invoices']) > 0){

						foreach ($fullContact['invoices'] as $inv){

							// ID + ZBS ID -> link to new db obj
							update_post_meta($inv['id'],'zbs_customer_invoice_customer',$newCID);

								// this covers where, in (woosync?) mike was using 2 vars, not sure why
								$oldWayMeta = get_post_meta( $inv['id'], 'customer', true );
								if (!empty($oldWayMeta)){
									update_post_meta($inv['id'],'customer',$newCID);
								}

							// also store prev link (backup!)
							update_post_meta($inv['id'],'zbs_cust_inv_predb2',$fullContact['id']);	

							if ($dbug){ echo 'Relinked inv id '.$inv['id'].' from '.$fullContact['id'].' to '.$newCID.'<br />'; }
		
						}
					}

					// Copy Trans (re-link to new db no)
					if (is_array($fullContact['transactions']) && count($fullContact['transactions']) > 0){

						foreach ($fullContact['transactions'] as $tran){

							// ID + ZBS ID -> link to new db obj
							update_post_meta($tran['id'],'zbs_parent_cust',$newCID);
							// also store prev link (backup!)
							update_post_meta($tran['id'],'zbs_cust_tran_predb2',$fullContact['id']);

							// AND catch Mike's occasional sub-meta var:
							$transMeta = get_post_meta( $tran['id'], 'zbs_transaction_meta', true );
							if (!empty($transMeta) && is_array($transMeta)){
								if (isset($transMeta['customer'])){
									// set it to new id
									$transMeta['customer'] = $newCID;
									// save it
									update_post_meta($tran['id'], 'zbs_transaction_meta', $transMeta);
								}
							}

							// this covers where, in (woosync?) mike was using 2 vars, not sure why
							$oldWayMeta = get_post_meta( $tran['id'], 'customer', true );
							if (!empty($oldWayMeta)){
								update_post_meta($tran['id'],'customer',$newCID);
							}

							if ($dbug){ echo 'Relinked trans id '.$tran['id'].' from '.$fullContact['id'].' to '.$newCID.'<br />'; }
		
						}
					}

					// Copy events - here we get header lines, then get meta ourselves + op (more perf.)
					$existingEvents = zeroBS_getEventsByCustomerID($fullContact['id'],false,100000,0);
					if (is_array($existingEvents) && count($existingEvents) > 0){

						foreach ($existingEvents as $event){

							$eventMeta = get_post_meta($event['id'], 'zbs_event_meta', true);
							if (is_array($eventMeta)){

								// update
								$updatedEventMeta = $eventMeta; $updatedEventMeta['customer'] = $newCID;

								// ID + ZBS ID -> link to new db obj
								update_post_meta($event['id'],'zbs_event_meta',$updatedEventMeta);
								// also store prev (backup!)
								update_post_meta($event['id'],'zbs_cust_event_predb2',$eventMeta);	

								if ($dbug){ echo 'Relinked event id '.$event['id'].' from '.$eventMeta['customer'].' to '.$updatedEventMeta['customer'].'<br />'; }
		
							}

						}


					}

					// Copy Files
					$cFiles = get_post_meta($fullContact['id'], 'zbs_customer_files', true);
					if (is_array($cFiles)){

						// straight forward moving of meta :)
						$zbs->DAL->updateMeta($objType,$newCID,'files',$cFiles);

						if ($dbug){ echo 'Updated files: '.json_encode($cFiles).'<br />'; }
		

					}

					// Copy Logs
					# TO UPDATE POST logs -> DB2
					$logs = zeroBSCRM_getLogs($fullContact['id'],true,10000,0);
					if (is_array($logs) && count($logs) > 0){

						foreach ($logs as $log){

							/* This just copies, no good, got new DB2 tab now:

							// ID + ZBS ID -> link to new db obj - NOTE, this is confusing because 'owner' is contact here
							update_post_meta($log['id'],'zbs_logowner',$newCID);
							// also store prev link (backup!)
							update_post_meta($log['id'],'zbs_cust_log_predb2',$fullContact['id']);	*/

							// fill out
							$logType = ''; $logShortDesc = ''; $logLongDesc = ''; $logMeta = -1; $logCreated = -1;
							
							if (isset($log) && is_array($log)){

								if (isset($log['type'])) $logType = zeroBSCRM_permifyLogType($log['type']);
								if (isset($log['shortdesc'])) $logShortDesc = $log['shortdesc'];
								if (isset($log['longdesc'])) $logLongDesc = $log['longdesc'];
								if (isset($log)) $logMeta = $log;							
								$logCreated = strtotime($log['created']);

								// add meta to reflect copied from old, this'll override non-array meta's, but think there should be none :)
								if (!is_array($logMeta)) $logMeta = array();
								$logMeta['prevlogid'] = $log['id'];

								$logOwner = -1; if (isset($log['authorid']) && !empty($log['authorid']) && $log['authorid'] > 0) $logOwner = (int)$log['authorid'];
							
								// copy log into db
								$newLogID = $zbs->DAL->logs->addUpdateLog(array(

															// WH thought LOGS had no 'assigned' 
															// but apparently they did :) Troy diff file helped here :)
															// DAL changes makes possible :)
															'owner'			=> $logOwner,

															// fields (directly)
															'data'			=> array(

																'objtype' 	=> $objType,
																'objid' 	=> $newCID,
																'type' 		=> $logType,
																'shortdesc' => $logShortDesc,
																'longdesc' 	=> $logLongDesc,
																'meta' 		=> $logMeta,
																'created'	=> $logCreated
																
															)));


								if ($dbug){ echo 'Added log ('.$newLogID.') to contact ('.$newCID.'): '.$logShortDesc.'<br />'; }

								// add to existing log :)
								update_post_meta($log['id'],'zbs_cust_log_db2id',$newLogID);	

							}

						}
					}

					// Aliases?
					$aliases = zeroBS_getCustomerAliases($fullContact['id']);
					if (is_array($aliases) && count($aliases) > 0){
						foreach ($aliases as $alias){
							// brutal switch id?
							$wpdb->update( 
									$ZBSCRM_t['aka'], 
									array( 
										'aka_id' => $newCID
									), 
									array( // where
										'ID' => $alias['ID']
										),
									array(
										'%d'
									),
									array( // where data types
										'%d'
										));

							if ($dbug){ echo 'Switched AKA ('.$alias['ID'].')<br />'; }

						}

					}

					// Extra migration steps (extension support)

						// Hypothesis (re-assign answers to correct ID's)
						global $ZBSCRM_hypo_t; if (isset($ZBSCRM_hypo_t) && is_array($ZBSCRM_hypo_t)){

						    // via SQL - brutal
						    // set prev to new, no limits!
						    $sql = "UPDATE ". $ZBSCRM_hypo_t['hypothesis_answers'] ." SET hypothesis_obj_id = %d WHERE hypothesis_obj_id = %d;";
						    $wpdb->query($wpdb->prepare($sql,array($newCID,$fullContact['id'])));

						}

						// Checklists (re-assign answers to correct ID's)
						global $ZBSCRM_checklists_t; if (isset($ZBSCRM_checklists_t) && is_array($ZBSCRM_checklists_t)){

						    // via SQL - brutal
						    // set prev to new, no limits!
						    $sql = "UPDATE ". $ZBSCRM_checklists_t['checklist_answers'] ." SET checklist_obj_id = %d WHERE checklist_obj_id = %d;";
						    $wpdb->query($wpdb->prepare($sql,array($newCID,$fullContact['id'])));

						}

						// File Slots (re-assign answers to correct ID's)	
				        $cfboxSettings = $zbs->settings->get('customfields');

				        if (isset($cfboxSettings['customersfiles']) && is_array($cfboxSettings['customersfiles']) && count($cfboxSettings['customersfiles']) > 0) foreach ($cfboxSettings['customersfiles'] as $cfb){

				            $cfbName = ''; if (isset($cfb[0])) $cfbName = $cfb[0];
				            $filePerma = ''; if (isset($cfbName) && !empty($cfbName)) $filePerma = strtolower(str_replace(' ','_',str_replace('.','_',substr($cfbName,0,20))));


							$oldWayMeta = get_post_meta( $fullContact['id'], 'cfile_'.$filePerma, true );
							if (!empty($oldWayMeta)){

								// * MIGHT NEED RETHINKING
								
								// will just add a new meta?
								$zbs->DAL->updateMeta($objType,$newCID,'cfile_'.$filePerma,$oldWayMeta);

								// and this won't do anything as newCID is not a post...
								//update_post_meta($newCID,'cfile_'.$filePerma,$oldWayMeta);
							}
				        }

	    			


					// ADD pointer record postid <-> new crm id
					
						// DB1 -> 2
						update_post_meta($fullContact['id'], 'zbs_db2_id', $newCID);
						// DB2 -> 1
						$zbs->DAL->updateMeta($objType,$newCID,'zbs_db1_id',$fullContact['id']);

				} // end of if not auto-draft

				// Update original post status to trash
				$wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_status = 'trash' WHERE ID = %d LIMIT 1",array($customerHeader->ID)));

				// mark old cpt as processed
				update_post_meta($customerHeader->ID, $processedKey, time());

				if ($dbug){ echo 'Finished Contact AKA ('.$customerHeader->ID.')</div>'; }

			}


		// ENABLE INTERNAL AUTOMATOR WHILE RUNNING (stop create logs etc)
		global $zbs; $zbs->internalAutomatorBlock = false;

}


function zeroBSCRM_db253migrateClose(){

    	// should have rights, proceed
		global $zbs,$wpdb,$ZBSCRM_t,$zbsCustomerFields;
		
			// Closing of migration
			//  - Check migration
			// 	- Copy settings obj over to new db model
			// 	- Set 'DB2 flag'

			$errors = array();

			// Check Migration

				// count contacts in 2 db methods?
				//$db1Contacts = 0; $wpContacts = wp_count_posts('zerobs_customer'); if (isset($wpContacts) && isset($wpContacts->publish)) $db1Contacts =  (int)$wpContacts->publish;
				// this is pre wp_count_posts declaration, so use custom sql:
				// .. further, they'll be marked as trash now :) 
				// this get's published: $db1Contacts = zeroBS_customerCountSQL();
				//$db1Contacts = zeroBS_customerCountSQL('trash');
				//$db2Contacts = $zbs->DAL->contacts->getContactCount();
				// Don't show this error? What if have trashed count?
				//if ($db1Contacts != $db2Contacts) $errors[] = 'Contact Database could not be successfully synced ('.$db1Contacts.','.$db2Contacts.')!';

			// Copy settings
			$existingSettings = $zbs->settings->getAll();
			if (is_array($existingSettings)){

				foreach ($existingSettings as $settingKey => $settingVal){

					// DEBUG echo 'Adding setting: '.$settingKey.': <pre>'; print_r($settingVal); echo '</pre><br>';

					// add to new db
					$zbs->DAL->updateSetting($settingKey,$settingVal);

					// check matches
					$recheck = $zbs->DAL->setting($settingKey);

					// here we have to allow for: {"errors":["Unable to migrate setting: smtpaccs ('[]',array (\n))!","Unable to migrate setting: whlang ('[]',array (\n))!"]}
					// ... so skip them
					if (var_export($recheck,true) != "'[]'" && $recheck != $settingVal){

						$errors[] = 'Unable to migrate setting: '.$settingKey.' ('.var_export($recheck,true).','.var_export($settingVal,true).')!';

					}

				}

				// CUSTOM FIELDS gets updated diff way :)

				// DMZ Settings migration
				$dmzSettingsArr = $zbs->settings->dmzGetMigrationSet();

				if (is_array($dmzSettingsArr)){

					// get em
					$dmzKey = $dmzSettingsArr[0];
					$dmzRegister = $dmzSettingsArr[1];
					$dmzSettings = $dmzSettingsArr[2];

					// migrate register
					$zbs->DAL->updateSetting($dmzKey,$dmzRegister);
					if (isset($dmzRegister) && is_array($dmzRegister)) foreach ($dmzRegister as $dmzSubKey){ # => $dmzVal

						$zbs->DAL->updateSetting($dmzKey.'_'.$dmzSubKey, $dmzSettings[$dmzSubKey]);	

					}

				} // if dmz settings


			} else {
				
				// ERR
				// No need $errors[] = 'Unable to load settings!';

			}

		return $errors;
}

function zeroBSCRM_db253migrationBackupPost($postObj=false,$zbsID=-1){

	return zeroBSCRM_migration_backupPost($postObj,$zbsID);

}




// ======= DAL 3 (v3.0 DB Migration) - Closing
function zeroBSCRM_db300migrateClose(){

    	// should have rights, proceed
		global $zbs,$wpdb,$ZBSCRM_t,$zbsCustomerFields;
		
			// Closing of migration
			//  - Check migration
			//  ... this then returns any errors & the ajax func deals with those errs + closes if legit

			// retrieve & return any migration errors
			return get_option('zbs_db_migration_300_errstack', array());

}


/* ======================================================
	/ DB MIGRATION HELPER FUNCS
   ====================================================== */



/* ======================================================
	Admin AJAX: DB Migration assistant
====================================================== */

	/* ======================================================
		Admin AJAX DAL2
	====================================================== */
   #} Set Transients for checking of the subscription status..
   add_action('wp_ajax_zbs_dbmigration253', 'zeroBSCRM_AJAX_dbMigration253');
   function zeroBSCRM_AJAX_dbMigration253(){

   		// nonce + perms checks
   		check_ajax_referer( 'wpzbs-ajax-nonce', 'sec' ); 
    	if (!current_user_can( 'admin_zerobs_manage_options' )) zeroBSCRM_sendJSONError(array('nope'=>1));

		if (!get_option('zbs_db_migration_253')){

			global $wpdb;
			$processedKey = 'zbsmig253';

			// set opt
			if (!get_option('zbs_db_migration_253_inprog')){ 
				update_option( 'zbs_db_migration_253_inprog',time());
			}

    		// this migrates x contacts:
    		zeroBSCRM_db253migrateContacts(5);

			// get closing counts
			$query = $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status = 'trash' AND post_type = 'zerobs_customer' AND ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value > 0)", $processedKey);
			$completed = $wpdb->get_var($query);
			$query = $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'zerobs_customer' AND ID NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value > 0)", $processedKey);
			$todo = $wpdb->get_var($query);			
			$total = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status <> 'auto-draft' AND post_type = 'zerobs_customer'");


			zeroBSCRM_sendJSONSuccess(array(

				'complete' => $completed,
				'todo' 		=> $todo,
				'total'		=> $total

				//'debug' => $toMigrate

			));
			exit();

		} else {

				// Error - already completed!
				zeroBSCRM_sendJSONError(array(

					'errors' => array('completed')

				));
				exit();

		}


   }

   #} CLOSES a migration, if successfully run
   add_action('wp_ajax_zbs_dbmigration253close', 'zeroBSCRM_AJAX_dbMigration253close');
   function zeroBSCRM_AJAX_dbMigration253close(){

   		// nonce + perms checks
   		check_ajax_referer( 'wpzbs-ajax-nonce', 'sec' ); 
    	if (!current_user_can( 'admin_zerobs_manage_options' )) zeroBSCRM_sendJSONError(array('nope'=>1));


		if (!get_option('zbs_db_migration_253')){
			
			$processedKey = 'zbsmig253';

    		// this tries to 'close' migration + returns errors as array if any
    		$errors = zeroBSCRM_db253migrateClose();

    		// This checks if any edit-lock issues occured
    		/* leave this for post-migration screen
				// Check for edits mid-migration
					$args = array (
						'post_type'              => 'zerobs_customer',
						'post_status'            => 'publish',
						'posts_per_page'         => $perRun,
						'order'                  => 'ASC',
						'orderby'                => 'post_date',

							// this is our 'processed' flag
							'meta_query' => array(
								array(
										'key'	=> 'zbsmig253editlock',
										'value' => '',
										'compare' => 'EXISTS'
										)
								)

					); $lockErrors = get_posts( $args );
			*/

			// if no errors, switch the switch!
			if (count($errors) == 0){

				// Final close :) (note this can also be run from 270 migration automatically (so change there if here))
				$started = get_option( 'zbs_db_migration_253_inprog', time());
				update_option( 'zbs_db_migration_253', array('completed'=>time(),'started'=>$started));

				zeroBSCRM_sendJSONSuccess(array(

					'completed' => time()
					//'lockerrors'=>$lockErrors

				));
				exit();

			} else {

				// Errors!
				zeroBSCRM_sendJSONError(array(

					'errors' => $errors
					//'lockerrors'=>$lockErrors

				));
				exit();

			}



		} else {

				// Error - already completed!
				zeroBSCRM_sendJSONSuccess(array(

					'completed' => time()
					//'lockerrors'=>$lockErrors
				));
				exit();

		}


   }
	/* ======================================================
		/ Admin AJAX DAL2
	====================================================== */

	/* ======================================================
		Admin AJAX DAL3
	====================================================== */


	#} Opens the v3.0 Migration
	/* 

		2.1 - "Open" migration:
				- Log which ext active
				- Disable extensions
				- move custom field settings from settings -> DB
				- NO EXT to turn back on (hard block) until 3.0 migrated
				- each ext needs to be at least ver recorded in core (we make a list) before re-activate
	*/
	add_action('wp_ajax_zbs_dbmigration300open', 'zeroBSCRM_AJAX_dbMigration300open');
	function zeroBSCRM_AJAX_dbMigration300open(){

		$debug = false;

		// nonce + perms checks
		check_ajax_referer( 'wpzbs-ajax-nonce', 'sec' ); 
		if (!current_user_can( 'admin_zerobs_manage_options' )) zeroBSCRM_sendJSONError(array('nope'=>1));

		// only run if not already completed :)
		if (!get_option('zbs_db_migration_300')){


			// open migration
			zeroBSCRM_migration_open_300();

			// ... either way we say "go"

			// reply
			zeroBSCRM_sendJSONSuccess(array(

				'completed' => time()
				//'lockerrors'=>$lockErrors

			));
			exit();

		} else {

				// Error - already initiated!
				zeroBSCRM_sendJSONSuccess(array(

					'completed' => time()
					//'lockerrors'=>$lockErrors
				));
				exit();

		}


	}

	// this function is used by zeroBSCRM_AJAX_dbMigration300open (above) & ZeroBSCRM.Migrations.php to provide virgin install automigration
	function zeroBSCRM_migration_open_300(){


			// if this hasn't already been started, start:
			if (!get_option('zbs_db_migration_300_inprog')){

				$debug = false;

				global $zbs,$ZBSCRM_t,$wpdb;

				// Log which ext active
				$extensions = zeroBSCRM_installedProExt();
				update_option('zbs_db_migration_300_pre_exts',$extensions);
	                    
				// Disable extensions
				// NO EXT to turn back on (hard block) until 3.0 migrated
				// each ext needs to be at least ver recorded in core (we make a list) before re-activate
				$noExtsDeactivated = zeroBSCRM_extensions_deactivateAll();

				// debug
				if ($debug) echo 'Extensions Deactivated:<pre>'.print_r($extensions,1).'</pre>';

				// move custom field settings from settings -> DB

					// our list
				    $customFieldsToProcess = array(
				      'addresses'=>'zbsAddressFields',
				      // already processed in migration 2.53 'customers'=>'zbsCustomerFields',
				      'companies'=>'zbsCompanyFields',
				      'quotes'=>'zbsCustomerQuoteFields',
				      'invoices'=>'zbsCustomerInvoiceFields',
				      'transactions'=>'zbsTransactionFields'
				      );

				    // we need to load the classes temporarily so we can get the objmodels so we don't allow
				    // collisions between cf keys + new fields				    
					require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.ObjectLayer.php');
					require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.Companies.php');
					require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.Quotes.php');
					require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.Invoices.php');
					require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.Transactions.php');
					require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.Addresses.php');
            		$tempCompaniesClass = new zbsDAL_companies;
		            $tempQuotesClass = new zbsDAL_quotes;
		            $tempInvoicesClass = new zbsDAL_invoices;
		            $tempTransactionsClass = new zbsDAL_transactions;
            		$tempAddressClass = new zbsDAL_addresses; // this is, at this point, a temporary obj with only the model.
				    $v3tempClasses = array(
				      'addresses'=>'tempAddressClass',
				      'companies'=>'tempCompaniesClass',
				      'quotes'=>'tempQuotesClass',
				      'invoices'=>'tempInvoicesClass',
				      'transactions'=>'tempTransactionsClass'
				      );

				    // what'll finally get inserted into dal2
				    $customFieldsToInsert = array();

				    // this stores the "translation" for later processing
				    // (e.g. company custom field 'cf1' => 'customslug')
				    $customFieldTranslationMatrix = array();

				    // cycle through the field objects and pick out any 'cf1' etc.
					foreach ($customFieldsToProcess as $k => $v){
						
						// globalise (legacy!!!)
						global ${$v};
					    
					    // debug echo $k.'<pre>'.print_r(${$v},1).'</pre>';
						// debug
						if ($debug) echo 'Custom fields processing: '.$k.'<br />';

						// if not set, create
						if (!isset($customFieldTranslationMatrix[$k])) $customFieldTranslationMatrix[$k] = array();

					    // get the v3.0 object model for this objtype
					    // .. this allows us to head off future collisions 
					    // .. e.g. user has custom field named 'id' or 'name' etc.
						$v3objModel = false; $v3objTypeInt = false;
						if (isset($v3tempClasses[$k])){

							// get the obj array for this type
							$v3objModel = ${$v3tempClasses[$k]}->objModel();
							if (!is_array($v3objModel)) $v3objModel = false;

							// get the obj type int
							$v3objTypeInt = ${$v3tempClasses[$k]}->objType();

						}

						// Core <v3.0 fields that became custom fields after the hop:
						if ($v3objTypeInt == ZBS_TYPE_COMPANY){

							// direct addition
							// note 'force-key' ONLY to be used here (to stop it checking against model where it'll see existing 'notes')
							${$v}[] = array('textarea',__('Notes','zero-bs-crm'),'','notes','custom-field' => 1,'force-key'=>1);

						}

					    // cycle through 
					    if (is_array(${$v})) foreach (${$v} as $fieldKey => $fieldDetails){

					    	// detect cf's
					    	/* 

								legacy:

								    [cf1] => Array
								        (
								            [0] => text
								            [1] => bbbb
								            [2] => bbb
								            [3] => bbbb
								            [custom-field] => 1
								        )

								translated (but not yet in db2):

								    [dddd] => Array
								        (
								            [0] => text
								            [1] => dddd
								            [2] => dddd
								            [3] => dddd
								            [custom-field] => 1
								        )
							*/
							if (substr($fieldKey, 0,2) == 'cf' || (isset($fieldDetails['custom-field']) && $fieldDetails['custom-field'] == 1)){

								// is Custom field (old style prefix, or already translated into new form array, but not yet in db2)

									// old style => new

									// build key
									$cfCheckedKey = ''; $tries = 0;
									while ($cfCheckedKey == '' && $tries < 32){

										// Make a CF key from label
										$keyOkay = true; $cfKey = $zbs->DAL->makeSlug($fieldDetails[1]);

										// after 0 append $tries (creates 'name-1')
										if ($tries > 0) $cfKey .= '-'.$tries;

										// if this 'force-key' is set, we obediently just use (allows for transition between core->meta in above logic)
										if (!isset($fieldDetails['force-key'])){

											// see if that matches an existing field (stop cf1 'id' overriding, e.g.)
											// ... or if it matches any 'future' fields (in DAL3) (to stop later collisions)
											// ... or that one's already been added for insert with this key.
											if (isset(${$v}[$cfKey])) $keyOkay = false;
											if (is_array($v3objModel) && isset($v3objModel[$cfKey])) $keyOkay = false;
											if (isset($customFieldsToInsert[$k]) && isset($customFieldsToInsert[$k][$cfKey])) $keyOkay = false;

										} else unset($fieldDetails['force-key']);

										// okay?
										if ($keyOkay) $cfCheckedKey = $cfKey;

										// tries (cieling to stop infinite spinning)
										$tries++;

									}

									// debug
									if ($debug) echo 'Custom field '.$k.'->'.$fieldDetails[1].' = '.$cfCheckedKey.'<br />';

									// success?
									if (!empty($cfCheckedKey)){

										// refine for new sys
										$ckArr = $fieldDetails;

											// add slug as 4th attr
											$ckArr[] = $cfCheckedKey;

										// add to array (inserted later).
										if (!isset($customFieldsToInsert[$k])) $customFieldsToInsert[$k] = array();
										$customFieldsToInsert[$k][$cfCheckedKey] = $ckArr;

										// add to translation matrix so actual migration engine can parse from meta to new obj
											
											// some objs which had been part-translated had $fieldKey = 0,1,2 rather than cf1,cf2,cf3 - this deals with those (one off):
											$fieldKeyVal = $fieldKey;
											if (strpos('#'.$fieldKey,'cf') <= 0){
												$cfInt = (int)$fieldKey;
												$fieldKeyVal = 'cf'.($cfInt+1);
											}

											// actual migration translation
											$customFieldTranslationMatrix[$k][$fieldKeyVal] = $cfCheckedKey;

									} // / if cfCheckedKey

							}  // / if is custom field

					    } // / foreach field


						// debug
						if ($debug){
							if (isset($customFieldsToInsert[$k])) 
								echo '<h4>Migrating Custom Fields for Obj Type '.$v3objTypeInt.':</h4><pre>'.print_r($customFieldsToInsert[$k],1).'</pre>';
							else
								echo '<h4>Migrating Custom Fields for Obj Type '.$v3objTypeInt.':</h4>(none)<br>';
						}

					    // here we should have a clean $customFieldsToInsert[$k]
					    // ... if anything in there, add to new custom fields model (in zbs db)
					    if ($v3objTypeInt && isset($customFieldsToInsert[$k]) && is_array($customFieldsToInsert[$k])){

							// update
							$zbs->DAL->updateActiveCustomFields(array('objtypeid'=>$v3objTypeInt,'fields'=>$customFieldsToInsert[$k]));

						}
						

					  } // / foreach objtype

					  // Address objtype also needs to migrate all existing addr_* custom field VALUES for contacts
					  // ... so that the new keys work.
					  if (is_array($customFieldTranslationMatrix) && isset($customFieldTranslationMatrix['addresses']) && is_array($customFieldTranslationMatrix['addresses']) && count($customFieldTranslationMatrix['addresses']) > 0){

					  		if ($debug) echo '<h3>Rebuilding Custom fields for addresses for Contacts</h3>';

					  		// is addresses to translate
					  		// ... ignoring site + team
					  		foreach ($customFieldTranslationMatrix['addresses'] as $origCFKey => $newCFKey){

					  			$cftransQBase = "UPDATE ".$ZBSCRM_t['customfields']." SET zbscf_objkey = %s WHERE zbscf_objtype = 1 AND zbscf_objkey = %s";

					  			// addr_*
					  			$cftransQ = $wpdb->prepare($cftransQBase,array('addr_'.$newCFKey,'addr_'.$origCFKey));
					  			$wpdb->query($cftransQ);

					  			// secaddr_*
					  			$cftransQ = $wpdb->prepare($cftransQBase,array('secaddr_'.$newCFKey,'secaddr_'.$origCFKey));
					  			$wpdb->query($cftransQ);
					  			
					  		}


					  }

					  // store this for obj migration engine to use
					  update_option( 'zbs_db_migration_300_cftrans', $customFieldTranslationMatrix);

					  // open a potential 'error stack'
					  update_option( 'zbs_db_migration_300_errstack', array());

						// debug
						if ($debug) echo 'Custom fields Migration Translations:<pre>'.print_r($customFieldTranslationMatrix,1).'</pre>';
						if ($debug) echo 'Custom fields to Insert:<pre>'.print_r($customFieldTranslationMatrix,1).'</pre>';

					  // FILE BOXES??

					  // save the custom fields obj for later analysis if issues
					  update_option( 'zbs_db_migration_300_cf', $customFieldsToInsert);


					  // Temporary re-assigning of objtype for company extsource records:
					  // Note: because ext sources are already migrated for companies, 
					  // we set all 'company ext sources' (e.g. ext sources with objtype = 2) to a 'temporary holder' (99), 
					  // this lets us migrate these to their new DAL2 id's without colliding
					  $updateQuery = 'UPDATE '.$ZBSCRM_t['externalsources'].' set zbss_objtype = 99 WHERE zbss_objtype = 2';
					  $wpdb->query($updateQuery);


				// Start the timer
				update_option( 'zbs_db_migration_300_inprog', time());

			} 
	}



	#} If this is called via AJAX, we've hit a timeout trying to migrate
	#} ... so we add the flag to 'do less per page'
	add_action('wp_ajax_zbs_dbmigration300timeoutflag', 'zeroBSCRM_AJAX_dbMigration300timeoutFlag');
	function zeroBSCRM_AJAX_dbMigration300timeoutFlag(){					

   		// nonce + perms checks
   		check_ajax_referer( 'wpzbs-ajax-nonce', 'sec' ); 
    	if (!current_user_can( 'admin_zerobs_manage_options' )) zeroBSCRM_sendJSONError(array('nope'=>1));

    	// set flag
    	global $zbs;
    	$zbs->settings->update('migration300_timeout_issues',1);

		zeroBSCRM_sendJSONSuccess(array(

			'flagged' => 1

		));
		exit();


    }


	#} Processes 1 x page of migration
	/* 
		2.2 - "Process" migration:
				- Telemetry
				- Fields global array?
				- Custom Field values for objs as we go
				- Backup (as we go):
				//- Companies -> DAL
				//- Quotes -> DAL
				- Quote Templates -> DAL
				- Invoices -> DAL
				- Transactions -> DAL
				- Lineitems -> DAL
				- Forms -> DAL
				- Events -> DAL
				- Logs? -> DAL?
				- Tags? -> DAL?
				- MIGRATE settings to settings not wp options (in dal2.helpers.php)
					- return (int)get_option('quoteindx',$defaultStartingQuoteID)+1;
					- update_option('zbs_crm_api_key', $api_key);
					- inv + quote hashes move from meta-based (zeroBSCRM_GenerateHashForPost) to table-based 
	*/
	add_action('wp_ajax_zbs_dbmigration300', 'zeroBSCRM_AJAX_dbMigration300');
	function zeroBSCRM_AJAX_dbMigration300(){					

   		// nonce + perms checks
   		check_ajax_referer( 'wpzbs-ajax-nonce', 'sec' ); 
    	if (!current_user_can( 'admin_zerobs_manage_options' )) zeroBSCRM_sendJSONError(array('nope'=>1));

		// only run if not already completed :)
		// only run if 'opening' routine completed
		if (!get_option('zbs_db_migration_300') && get_option('zbs_db_migration_300_inprog') > 0){

			global $wpdb;
			$processedKey = 'zbsmig300';
		/*
				- remember to copy Custom Fields (+addr custom fields) for each obj type (from meta -> our table)
				- Backup (as we go):
				- Companies -> DAL
				- Quotes -> DAL
				- Quote Templates -> DAL
				- Invoices -> DAL
				- Transactions -> DAL
				- Lineitems -> DAL
				- Forms -> DAL
				- Events -> DAL
				- Logs? -> DAL?
				- Tags? -> DAL?
				- MIGRATE settings to settings not wp options (in dal2.helpers.php)
					- return (int)get_option('quoteindx',$defaultStartingQuoteID)+1;
					- update_option('zbs_crm_api_key', $api_key);
					- inv + quote hashes move from meta-based (zeroBSCRM_GenerateHashForPost) to table-based 

				- Telemetry
		*/

			// work through the objects until all zero back.
			$objectsToMigrate = array(

				// CPT => DAL v3 objType id
				'zerobs_company' 		=> 2,
				'zerobs_quote' 			=> 3,
				'zerobs_invoice' 		=> 4,
				'zerobs_transaction' 	=> 5,
				'zerobs_event' 			=> 6,
				'zerobs_form' 			=> 7,
				'zerobs_quo_template' 	=> 12

			); 

			$migrated = 0; $processTarget = 20;

			// some users were timing out trying to 20, fallback to 5 if this happens
			$timeoutIssues = zeroBSCRM_getSetting('migration300_timeout_issues'); if (isset($timeoutIssues) && $timeoutIssues == 1) $processTarget = 3;

			foreach ($objectsToMigrate as $postType => $dalObjTypeID){

				// get todo
				$query = $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = '".$postType."' AND ID NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value > 0)", $processedKey);
				$todo = (int)$wpdb->get_var($query);

				// has some todo
				if ($todo > 0){

					// process x objs
					$migrated += zeroBSCRM_db300migrateObjects($processTarget,$postType,$dalObjTypeID);

				} else continue;

				// if it's reached it's target, then dump out of foreach
				if ($migrated >= $processTarget) break;

			}


			// if finished migrating objects, then migrate obj links
			// e.g. invoice -> contact

		
			// get closing counts

				// for all our objs that need migrating
				$cptList = '';
				foreach ($objectsToMigrate as $cptKey => $cptInt){
					if (!empty($cptList)) $cptList .= ',';
					$cptList .= "'".$cptKey."'";
				}

			$query = $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status = 'trash' AND post_type in (".$cptList.") AND ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value > 0)", $processedKey);
			$completed = $wpdb->get_var($query);
			$query = $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type in (".$cptList.") AND ID NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value > 0)", $processedKey);
			$todo = $wpdb->get_var($query);			
			$total = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status <> 'auto-draft' AND post_type in (".$cptList.")");


			zeroBSCRM_sendJSONSuccess(array(

				'complete' => $completed,
				'todo' 		=> $todo,
				'total'		=> $total

			));
			exit();

		} else {

				// Error - already completed!
				zeroBSCRM_sendJSONError(array(

					'errors' => array('completed')

				));
				exit();

		}


   }


   	// migrates x number of objtype from V2.0 to v3.0 DAL
   	function zeroBSCRM_db300migrateObjects($count=10,$postType='',$dalObjTypeID=-1){

   		$debug = false;

		// this gets returned
		$processedCount = 0;

   		if ($count > 0 && !empty($postType) && $dalObjTypeID > 0){

			// DISABLE INTERNAL AUTOMATOR WHILE RUNNING (stop create logs etc)
			global $zbs; $zbs->internalAutomatorBlock = true;

			// req.
			global $wpdb,$ZBSCRM_t,$zbsCustomerFields;

			// key
			$processedKey = 'zbsmig300';

			// GET X Obj to process
			$count = (int)$count; if ($count <= 0) $count = 5;
			$objType = 1; $perRun = $count;

			// Check backup tables exist/create (backup as we go)
			if (count($wpdb->get_results("SHOW TABLES LIKE '".$ZBSCRM_t['dbmigrationbkposts']."'")) < 1) zeroBSCRM_createTables();

			// Build query
			$args = array (
				'post_type'              => $postType,
				'post_status'            => 'publish',
				'posts_per_page'         => $perRun,
				'order'                  => 'ASC',
				'orderby'                => 'post_date',

					// this is our 'processed' flag
					'meta_query' => array(
						array(
								'key'	=> $processedKey,
								'value' => '',
								'compare' => 'NOT EXISTS'
								)
						)

			); 

			// retrieve
			$objectsToMigrate = get_posts( $args );

			// debug
			if ($debug) echo 'Retrieved '.count($objectsToMigrate).' '.$postType.' to migrate<br />';

			// Begin processing
			foreach ($objectsToMigrate as $cptObject){

				// Backup original post + all it's meta
				zeroBSCRM_migration_backupPost($cptObject);

				// Check + block autodrafts/bug outs + SKIP PROCESSING!
				// hmmm v2 invoices seem to be saving with 'auto draft' title :( - so let them through ¯\_(ツ)_/¯
				if ($cptObject->post_title != 'Auto Draft' || $postType == 'zerobs_invoice'){

					// debug
					if ($debug) echo '<div style="clear:both;margin-top:30px;margin-left:20px;padding-left:20px;border-left:3px solid green">Migrating object '.$postType.' (#'.$cptObject->ID.')</div>'; 

					// process via subfunc
					call_user_func('zeroBSCRM_migration_migrate_'.$postType,$cptObject);

   				}	

				// Update original post status to trash
				$wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_status = 'trash' WHERE ID = %d LIMIT 1",array($cptObject->ID)));

				// mark old cpt as processed
				update_post_meta($cptObject->ID, $processedKey, time());

				// increment
				$processedCount++;

				// debug
				if ($debug) echo 'Finished '.$postType.' AKA ('.$cptObject->ID.')</div>';

			} // / foreach obj type


			// ENABLE INTERNAL AUTOMATOR WHILE RUNNING (stop create logs etc)
			global $zbs; $zbs->internalAutomatorBlock = false;

		}

		return $processedCount;

	}

   #} CLOSES a migration, if successfully run
   /*

		2.3 - "Close" migration:
				- Switch over to 3.0
				- Turn on all ext approved for DAL3 (needs a switch in them somehow)

	*/
   add_action('wp_ajax_zbs_dbmigration300close', 'zeroBSCRM_AJAX_dbMigration300close');
   function zeroBSCRM_AJAX_dbMigration300close(){

   		// nonce + perms checks
   		check_ajax_referer( 'wpzbs-ajax-nonce', 'sec' ); 
    	if (!current_user_can( 'admin_zerobs_manage_options' )) zeroBSCRM_sendJSONError(array('nope'=>1));

		if (!get_option('zbs_db_migration_300')){

	    	// There are no breaking errors really in dal3 migration, so allow, with warnings logged to option for ui retrieval:

				// close it
				zeroBSCRM_migration_close_300();

				// return
				zeroBSCRM_sendJSONSuccess(array(

					'completed' => time()
					//'lockerrors'=>$lockErrors

				));
				exit();

	    	/*
			// if no errors, switch the switch!
			if (count($errors) == 0){

				// Final close :) (note this can also be run from 270 migration automatically (so change there if here))
				$started = get_option( 'zbs_db_migration_300_inprog', time());
				update_option( 'zbs_db_migration_300', array('completed'=>time(),'started'=>$started));

				zeroBSCRM_sendJSONSuccess(array(

					'completed' => time()
					//'lockerrors'=>$lockErrors

				));
				exit();

			} else {

				// Errors!
				zeroBSCRM_sendJSONError(array(

					'errors' => $errors
					//'lockerrors'=>$lockErrors

				));
				exit();

			} */



		} else {

				// Error - already completed!
				zeroBSCRM_sendJSONSuccess(array(

					'completed' => time()
					//'lockerrors'=>$lockErrors
				));
				exit();

		}


   }   

   // this function is used by zeroBSCRM_AJAX_dbMigration300close (above) & ZeroBSCRM.Migrations.php to provide virgin install automigration
   function zeroBSCRM_migration_close_300(){

			global $zbs;
			
			$processedKey = 'zbsmig300';

    		// this tries to 'close' migration + returns errors as array if any

    		// - Copy API secret + key out of wp options into ours
    		$apiKey = zeroBSCRM_getAPIKey(); if (!empty($apiKey)) $zbs->DAL->updateSetting('api_key',$apiKey);
    		$apiSecret = zeroBSCRM_getAPISecret(); if (!empty($apiSecret)) $zbs->DAL->updateSetting('api_secret',$apiSecret);

			// - Turn on all ext approved for DAL3
    		$extensionsPreMigration = get_option( 'zbs_db_migration_300_pre_exts', array());

	    		$extensionsCouldntReactivate = array();
	    		if (is_array($extensionsPreMigration)) foreach ($extensionsPreMigration as $shortName => $ext){

	    			$activated = false;
	    			$wasActive = (int)$ext['active'];

	    			if (isset($ext['key']) && $ext['ver'] && $wasActive  > 0){

		    			// get minimum version okay with v3
		    			$minVer = 99.99; if (isset($zbs->compat_versions['v3extminimums'][$ext['key']])) $minVer = $zbs->compat_versions['v3extminimums'][$ext['key']];
		    			if ($minVer > 0){

		    				// compare
							if (version_compare($ext['ver'],  $minVer) >= 0){

				    			// silently re-activate
				    			if (isset($ext['path']) && !empty($ext['path'])) {

				    				$activatedExt = activate_plugin($ext['path'],'',false,true);

					    			// worked?
					    			if( !is_wp_error( $activatedExt ) ) $activated = true;

					    		}								

				    		}

			    		}

		    		}

		    		if (!$activated) $extensionsCouldntReactivate[] = $ext;

	    		}

	    		if (count($extensionsCouldntReactivate) > 0) foreach ($extensionsCouldntReactivate as $ext){

	    			// any it can't activate cos of version, add to error stack
	    			// add errors to stack
	    			zeroBSCRM_migration_addErrToStack(array(710,__('Extension could not be re-activated:','zero-bs-crm').' <strong>'.$ext['name'].'</strong>'),'zbs_db_migration_300_errstack');

	    		}

    		// - Switch over to 3.0
    		$errors = zeroBSCRM_db300migrateClose();

	    		// debug
	    		//echo 'errors from migration:<pre>'.print_r($errors,1).'</pre>';

	    		if (is_array($errors) && count($errors) > 0){

		    		// Act on errors
		    		// the UI will load the front-end for this, but email a report to user

		    			// email report to this user
		    			try {
	    					
	    					$current_user = wp_get_current_user();
	    					if (isset($current_user) && isset($current_user->user_email)){

	    						// build report
								$subject = __('ZBSCRM Migration Report [Errors]','zero-bs-crm');
								$bodyStr = '<h2>'.__('Migration Completion Report','zero-bs-crm').'</h2>';
								$bodyStr .= '<p>'.__('ZBS CRM has completed an important migration from v2 to v3.0 on your site','zero-bs-crm').' '.get_site_url().'</p>';
								$bodyStr .= '<p>'.__('Unfortunately there were some migration errors, which are shown below. The error messages should explain any conflicts found when merging, but if you do need any help with migration errors please do forward this email to support:','zero-bs-crm').' '.$zbs->urls['migrationhelpemail'].' '.__('or visit the migration support page','zero-bs-crm').' <a href="'.$zbs->urls['db3migrate'].'" target="_blank">'.__('here','zero-bs-crm').'</a></p>';
								$bodyStr .= '<div style="position: relative;background: #FFFFFF;box-shadow: 0px 1px 2px 0 rgba(34,36,38,0.15);margin: 1rem 0em;padding: 1em 1em;border-radius: 0.28571429rem;border: 1px solid rgba(34,36,38,0.15);"><h3>'.__('Non-critical Errors:','zero-bs-crm').'</h3>';

									// list errors
									foreach ($errors as $error){

										$bodyStr .= '<p>['.$error[0].'] '.$error[1].'</p>';

									}

								$bodyStr .= '</div>';

	    						$body = zeroBSCRM_mail_retrieveWrapTemplate();
	    						$body = str_replace('###TITLE###',$subject,$body);
	    						$body = str_replace('###MSGCONTENT###',$bodyStr,$body);
	    						$body = str_replace('###FOOTERBIZDEETS###','',$body);
	    						$body = str_replace('###UNSUB-LINE###','',$body);
	    						$body = str_replace('###POWEREDBYDEETS###',__('ZBS CRM Admin Notice','zero-bs-crm'),$body);

	    						// simple send.
								$headers = array('Content-Type: text/html; charset=UTF-8');							
								wp_mail( $current_user->user_email, $subject, $body, $headers );

							}

	    				} catch (Exception $e){

	    					// couldn't email the user?
	    					// ... not much to be done here

	    				}

	    		}

				// Final close :) (this can also be run from 3000 migration automatically)
				$started = get_option( 'zbs_db_migration_300_inprog', time());
				update_option( 'zbs_db_migration_300', array('completed'=>time(),'started'=>$started));
				delete_option('zbs_db_migration_300_inprog');
                
   }


   // Migrate zerobs_company
   // take a cpt object from < 3.0 (DAL2), and migrate it into DAL3 obj
   function zeroBSCRM_migration_migrate_zerobs_company($cptObject=false){

   		$debug = false;

   		if (is_object($cptObject)){

   			global $zbs;

   			// retreive
   			$fullObj = zeroBS_getCompany($cptObject->ID,true);

			if ($debug) echo 'Company CPT:<pre>'.print_r($fullObj,1).'</pre>';

			// convert obj to correct obj
			$id = -1; $owner = -1;
			$dataArr = array(

                'status' => ((isset($fullObj['meta']['status'])) ? $fullObj['meta']['status'] : ''),
                'name' => ((isset($fullObj['meta']['coname'])) ? $fullObj['meta']['coname'] : ''),
                'email' => ((isset($fullObj['meta']['email'])) ? $fullObj['meta']['email'] : ''),
                'addr1' => ((isset($fullObj['meta']['addr1'])) ? $fullObj['meta']['addr1'] : ''),
                'addr2' => ((isset($fullObj['meta']['addr2'])) ? $fullObj['meta']['addr2'] : ''),
                'city' => ((isset($fullObj['meta']['city'])) ? $fullObj['meta']['city'] : ''),
                'county' => ((isset($fullObj['meta']['county'])) ? $fullObj['meta']['county'] : ''),
                'country' => ((isset($fullObj['meta']['country'])) ? $fullObj['meta']['country'] : ''),
                'postcode' => ((isset($fullObj['meta']['addr2'])) ? $fullObj['meta']['addr2'] : ''),
                'secaddr1' => ((isset($fullObj['meta']['secaddr_addr1'])) ? $fullObj['meta']['secaddr_addr1'] : ''),
                'secaddr2' => ((isset($fullObj['meta']['secaddr_addr2'])) ? $fullObj['meta']['secaddr_addr2'] : ''),
                'seccity' => ((isset($fullObj['meta']['secaddr_city'])) ? $fullObj['meta']['secaddr_city'] : ''),
                'seccounty' => ((isset($fullObj['meta']['secaddr_county'])) ? $fullObj['meta']['secaddr_county'] : ''),
                'seccountry' => ((isset($fullObj['meta']['secaddr_postcode'])) ? $fullObj['meta']['secaddr_postcode'] : ''),
                'secpostcode' => ((isset($fullObj['meta']['secaddr_country'])) ? $fullObj['meta']['secaddr_country'] : ''),
                'maintel' => ((isset($fullObj['meta']['maintel'])) ? $fullObj['meta']['maintel'] : ''),
                'sectel' => ((isset($fullObj['meta']['sectel'])) ? $fullObj['meta']['sectel'] : ''),

                // these are pre-cursors, not exposed in < 3.0 so safe to leave blank...
                'wpid' => '',
                'avatar' => '',
                'tw' => '',
                'li' => '',
                'fb' => '',

                // these we convert + pass
                'created' => strtotime($fullObj['created']),
                'lastupdated' => strtotime($fullObj['created']),
                'lastcontacted' => '',

                // Custom Fields -> dealt with below.

                'tags' => -1, // if this is an array of ID's, they'll REPLACE existing tags against contact
                'externalSources' => -1, // if this is an array(array('source'=>src,'uid'=>uid),multiple()) it'll add :)

                // obj links:
                'contacts' => false, // array of id's

            );

			if ($debug) echo '<hr />Company Arr Stage 1:<pre>'.print_r($dataArr,1).'</pre>';

			// retrieve $owner
			$owner = (int)get_post_meta($fullObj['id'], 'zbs_owner', true); if ($owner < 1) $owner = -1;

			// notes becomes custom field
			if (isset($fullObj['meta']['notes'])) $dataArr['notes'] = $fullObj['meta']['notes'];

			// custom fields
			$customFieldDB2toDB3Matrix = get_option( 'zbs_db_migration_300_cftrans', array() );

				// obj custom fields
				if (isset($customFieldDB2toDB3Matrix['companies']) && is_array($customFieldDB2toDB3Matrix['companies'])){

					// cycle through + translate
					foreach ($customFieldDB2toDB3Matrix['companies'] as $db1Key => $db2key){

							if ($debug) echo 'Probing for cf: "'.$db1Key.'" which becomes "'.$db2key.'" => ';

							if (isset($fullObj['meta'][$db1Key])) {

								$dataArr[$db2key] = $fullObj['meta'][$db1Key];

								if ($debug) echo '"'.$fullObj['meta'][$db1Key].'"';

							}
							
							if ($debug) echo '<br />';

					}

				}

				// ADDRESS custom fields - addr_cf1
				if (isset($customFieldDB2toDB3Matrix['addresses']) && is_array($customFieldDB2toDB3Matrix['addresses'])){

					// cycle through + translate
					foreach ($customFieldDB2toDB3Matrix['addresses'] as $db1Key => $db2key){

							if (isset($fullObj['meta']['addr_'.$db1Key])) $dataArr['addr_'.$db2key] = $fullObj['meta']['addr_'.$db1Key];
							if (isset($fullObj['meta']['secaddr_'.$db1Key])) $dataArr['secaddr_'.$db2key] = $fullObj['meta']['secaddr_'.$db1Key];

					}

				}

			if ($debug) echo '<hr />Company Arr Stage 2:<pre>'.print_r($dataArr,1).'</pre>';

			// Company image
			if (has_post_thumbnail($cptObject->ID)){

				// retrieve
				$thumb_urlArr = wp_get_attachment_image_src( get_post_thumbnail_id($cptObject->ID, 'single-post-thumbnail'));
				$thumb_url = ''; if (is_array($thumb_urlArr)) $thumb_url = $thumb_urlArr[0];

				// add
				if (!empty($thumb_url)) $dataArr['avatar'] = $thumb_url;

			}

			// TAGS
			$tags = wp_get_object_terms($fullObj['id'],'zerobscrm_companytag',array('order' => 'ASC','orderby' => 'name'));
			// cycle through + add to db if not present
			$tagIDs = array(); if (count($tags) > 0) {
				foreach ($tags as $t){

					$potentialID = (int)$zbs->DAL->getTag(-1,array(
						'objtype'	=> ZBS_TYPE_COMPANY, 
						'name' 		=> $t->name,
						'onlyID'	=> true,
						'ignoreowner' => true
						));

					if (empty($potentialID)){

						// add it
						$newTagID = $zbs->DAL->addUpdateTag(array(
							'data' => array(
								'objtype' 		=> ZBS_TYPE_COMPANY,
								'name' 			=> $t->name,
								'slug' 			=> $t->slug,
								'owner'			=> -1 // for now, tags = no owner :)
							)));

						if (!in_array($newTagID, $tagIDs)){

						 	$tagIDs[] = $newTagID;

						}


					} else if (!in_array($potentialID, $tagIDs)){

					 	$tagIDs[] = $potentialID;

					}

				}
			}
			if (is_array($tagIDs) && count($tagIDs) > 0) $dataArr['tags'] = $tagIDs;

			// EXTERNAL SOURCES
			//$extSource = get_post_meta($fullObj['id'],'zbs_external_sources',true);
			// v2.53+ ext sources are ALL in DAL2
			// note here we use the override objtype (99) in place of ZBS_TYPE_COMPANY (2)
			// because we set all company extsources to 99 in open mechanism of migrate, to avoid collisions here.
			$extSource = zeroBS_getExternalSource($fullObj['id'],99);
			if (is_array($extSource)){

				$externalSources = array();
				if (isset($extSource['source']) && !empty($extSource['source'])
					&& isset($extSource['uid']) && !empty($extSource['uid'])){
					$externalSources[] = array('source'=>$extSource['source'],'uid'=>$extSource['uid']);
				} 

				if (is_array($externalSources) && count($externalSources) > 0) $dataArr['externalSources'] = $externalSources;

			}
			// need to remove previous ext source reference too?

			// LAST CONTACTED
			$lastcontacted = -1; $latestContactLog = zeroBSCRM_getMostRecentCPTLog($fullObj['id'],true,array('Call','Email'));
			if (isset($latestContactLog) && is_array($latestContactLog) && isset($latestContactLog['created'])){
				$lastcontacted = strtotime($latestContactLog['created']);
			}
			$dataArr['lastcontacted'] = $lastcontacted;

			// Contacts (at co)
			$contactsAtCompany = $zbs->DAL->getContacts(array(

	            'inCompany'     => $cptObject->ID,
	            'sortByField'   => 'ID',
	            'sortOrder'     => 'ASC',
	            'page'          => 0,
	            'perPage'       => 1000,
	            'ignoreowner'   => true,
	            'onlyIDs' 		=> true

			));
			

			if ($debug) echo 'Contacts at Company: <pre>'.print_r($contactsAtCompany,1).'</pre>';

			// strip old relations
			// (as DAL2 <3.0 contacts were in new db, but companies not, 
			// so the links would be between new contacts + old cpt id's)
			$zbs->DAL->deleteObjLinks(array(
		            'objtypefrom'       => ZBS_TYPE_CONTACT,
		            'objtypeto'         => ZBS_TYPE_COMPANY,
		            'objtoid'           => $fullObj['id']
            ));

			// add to new company obj so it automaps
			if (is_array($contactsAtCompany) && count($contactsAtCompany) > 0){

				$dataArr['contacts'] = $contactsAtCompany;

			}


			if ($debug) echo '<hr />Company Arr Stage 3:<pre>'.print_r($dataArr,1).'</pre>';


		    // we need to load the classes temporarily so we can get the objmodels so we don't allow
		    // collisions between cf keys + new fields				    
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.ObjectLayer.php');
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.Companies.php');
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.Logs.php');
    		global $tempCompaniesClass,$tempLogClass; // global because addUpdate below uses this global to check any unique fields/model
    		if (!isset($tempCompaniesClass)) $tempCompaniesClass = new zbsDAL_companies;
    		if (!isset($tempLogClass)) $tempLogClass = new zbsDAL_logs; // used to migrate cpt logs -> dal2 logs

    		// before we insert, we check if a co already exists with this email (because if so they'll be merged)
    		$potentialv3CompanyID = -1;
    		if (isset($dataArr['email']) && !empty($dataArr['email'])) $potentialv3CompanyID = $tempCompaniesClass->getCompany(-1,array('email' =>$dataArr['email'], 'onlyID' => true,'withCustomFields'=>false,'withContacts'=>false));
    		if ($potentialv3CompanyID > 0){

    			// this will merge the two company records (with same email.)
    			// retrieve mergee and log both:
    			$toBeMergedOver = $tempCompaniesClass->getCompany(-1,array('email' =>$dataArr['email'],'withCustomFields'=>false,'withContacts'=>false));
    			// add to error stack
    			$errMsg = '['.__('Company','zero-bs-crm').'] '.__('Two objects were found with matching emails. Merged...','zero-bs-crm');

					// either way strip contacts as it adds tons of unuseful data to output:
					if (isset($dataArr['contacts'])) unset($dataArr['contacts']);
					if (isset($toBeMergedOver['contacts'])) unset($toBeMergedOver['contacts']);

    			if ($debug){
	    			$errMsg .= '<br />'.__('Company','zero-bs-crm').' #'.$potentialv3CompanyID.':<pre>'.json_encode($toBeMergedOver).'</pre>';
	    			$errMsg .= '<br />'.__('Company','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
    			} else {
    				$errMsg .= '<br />'.__('Company','zero-bs-crm').' <span class="ui label teal">#'.$potentialv3CompanyID.'</span> '.__('With email','zero-bs-crm').' <span class="ui label teal">'.$toBeMergedOver['email'].'</span><div class="hide"><pre>'.json_encode($toBeMergedOver).'</pre></div>';
    				$errMsg .= '<br />'.__('Merged with','zero-bs-crm').':';
    				$errMsg .= '<br />'.__('Company','zero-bs-crm').' <span class="ui label teal">#'.$cptObject->ID.'</span> '.__('With email','zero-bs-crm').' <span class="ui label teal">'.$dataArr['email'].'</span><div class="hide"><pre>'.json_encode($dataArr).'</pre></div>';
    			}


    			zeroBSCRM_migration_addErrToStack(array(701,$errMsg),'zbs_db_migration_300_errstack');

    		}

			// insert via temp dal3 class
			$insertedID = $tempCompaniesClass->addUpdateCompany(array(

	            'id'            => $id,
	            'owner'         => $owner,

	            // fields (directly)
	            'data'          => $dataArr,

	            'limitedFields' => -1,
	            'silentInsert' => true

			));

			// ... if failed, was it because of uniqueness? if so, '-n' the field and log fact
			if ($insertedID > 0){

				if ($debug) echo 'Successful insert:'.$insertedID.'!<br />';

				// Copy Files
				zeroBSCRM_files_moveFilesToNewObjectv3('company',$fullObj['id'],$insertedID);

				// Copy Transactions
				// Add a 'catch' for Transactions which lets the following 'transaction' migration pull them over to the new co id
				if (isset($fullObj['transactions']) && is_array($fullObj['transactions'])) foreach ($fullObj['transactions'] as $transaction){

					// add a meta pointing to the new id
					update_post_meta( $transaction['id'], 'zbs_parent_co_dal2', $insertedID);

				}

				// Copy Invoices
				// Add a 'catch' for Transactions which lets the following 'transaction' migration pull them over to the new co id
				if (isset($fullObj['invoices']) && is_array($fullObj['invoices'])) foreach ($fullObj['invoices'] as $invoice){

					// add a meta pointing to the new id
					update_post_meta( $invoice['id'], 'zbs_parent_co_dal2', $insertedID);

				}

				// Copy Events (presumably nobody has a full 100k events for a company :o )
				$events = zeroBS_getEventsByCompanyID($fullObj['id'],true,100000,1);
				// Add a 'catch' for Transactions which lets the following 'transaction' migration pull them over to the new co id
				if (is_array($events)) foreach ($events as $event){

					// add a meta pointing to the new id
					update_post_meta( $event['id'], 'zbs_parent_co_dal2', $insertedID);

				}

				// Copy Logs (Moves them from old CPT to new DB Table)				
				$logs = zeroBSCRM_getCPTLogs($fullObj['id'],true,100000,0);
				if (is_array($logs) && count($logs) > 0){

					foreach ($logs as $log){

						// fill out
						$logType = ''; $logShortDesc = ''; $logLongDesc = ''; $logMeta = -1; $logCreated = -1;
						
						if (isset($log) && is_array($log)){

							if (isset($log['type'])) $logType = zeroBSCRM_permifyLogType($log['type']);
							if (isset($log['shortdesc'])) $logShortDesc = $log['shortdesc'];
							if (isset($log['longdesc'])) $logLongDesc = $log['longdesc'];
							if (isset($log)) $logMeta = $log;							
							$logCreated = strtotime($log['created']);

							// add meta to reflect copied from old, this'll override non-array meta's, but think there should be none :)
							if (!is_array($logMeta)) $logMeta = array();
							$logMeta['prevlogid'] = $log['id'];

							// owner
							$logOwner = -1; if (isset($log['authorid']) && !empty($log['authorid']) && $log['authorid'] > 0) $logOwner = (int)$log['authorid'];
						
							// copy log into db
							$newLogID = $tempLogClass->addUpdateLog(array(

														'owner'			=> $logOwner,

														// fields (directly)
														'data'			=> array(

															'objtype' 	=> ZBS_TYPE_COMPANY,
															'objid' 	=> $insertedID,
															'type' 		=> $logType,
															'shortdesc' => $logShortDesc,
															'longdesc' 	=> $logLongDesc,
															'meta' 		=> $logMeta,
															'created'	=> $logCreated
															
														)));


							if ($debug){ echo 'Added log ('.$newLogID.') to obj ('.$insertedID.'): '.$logShortDesc.'<br />'; }

							// add to existing log :)
							update_post_meta($log['id'],'zbs_co_log_db2id',$newLogID);
							update_post_meta($log['id'],'zbs_tocleanup',$newLogID);	

						}

					}
				}

				// passed, return
				return $insertedID;

			} else {

				// field uniqueness errors?
				// ...if so, '-n' the field and log fact

                // fails
                $failedOnErrors = $zbs->DAL->getErrors(ZBS_TYPE_COMPANY);

                // debug
                if ($debug) echo 'Failed, errors:<pre>'.print_r($failedOnErrors,1).'</pre>';

                // cycle through & find 301 (failed uniqueness)
                $otherErrors = false;
                if (is_array($failedOnErrors)) foreach ($failedOnErrors as $error){

                	// any errors logged?
                	if (is_array($error) && isset($error['code'])){

                		// 301?
                		if ($error['code'] == 301){

                			// there is a field uniqueness fail:

                			if ($debug) echo 'Field uniqueness collision:<pre>'.print_r($error,1).'</pre>';

                			// add to error stack
                			$errMsg = '['.__('Company','zero-bs-crm').'] '.__('There was an error migrating an object (non-unique field found)','zero-bs-crm');

                				// either way strip contacts as it adds tons of unuseful data to output:
                				if (isset($dataArr['contacts'])) unset($dataArr['contacts']);

                			if ($debug) 
                				$errMsg .= '<br />'.__('Company','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
                			else
                				$errMsg .= '<br />'.__('Company','zero-bs-crm').' #'.$dataArr['id'].' '.__('With email','zero-bs-crm').' '.$dataArr['email'].'<div class="hide"><pre>'.json_encode($dataArr).'</pre></div>';

                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');

                		} // / if 301
                		else {

                			//... could have failed on insert/update?
                			$otherErrors = true;

                			// add to error stack
                			$errMsg = '['.__('Company','zero-bs-crm').'] '.__('There was an error migrating an object (could not insert)','zero-bs-crm');

                			if ($debug) 
                				$errMsg .= '<br />'.__('Company','zero-bs-crm').':<pre>'.json_encode($dataArr,1).'</pre>';
                			else
                				$errMsg .= '<br />'.__('Company','zero-bs-crm').' #'.$dataArr['id'].' '.__('With email','zero-bs-crm').' '.$dataArr['email'].'<div class="hide"><pre>'.json_encode($dataArr).'</pre></div>';

                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');

                		}

                	}

                } // / foreach error

			} // / didn't insert successfully


   		} // is valid obj

   		// return fail, so ball keeps rolling - should be logged in err stack if errs
   		//exit(__('Error Migrating object'));
   		return false;

   }

   // Migrate zerobs_quote - 3
   // take a cpt object from < 3.0 (DAL2), and migrate it into DAL3 obj
   function zeroBSCRM_migration_migrate_zerobs_quote($cptObject=false){

   		$debug = false;

   		if (is_object($cptObject)){

   			global $zbs;

   			// retreive
   			$fullObj = zeroBS_getQuote($cptObject->ID,true);
            	
            	// extra meta retrieves
   				// strangely this is set on the meta of obj + with this meta? ¯\_(ツ)_/¯
            	$fullObj['templated'] = get_post_meta($cptObject->ID, 'templated', true);
            	// ... this is the actual id if templated :/ this is the one to use v3
            	$fullObj['templateid'] = get_post_meta($cptObject->ID, 'zbs_quote_template_id', true);


				// Send attachments?
				$zbsSendAttachments = get_post_meta($cptObject->ID, 'zbs_quote_sendattachments', true);
				// force format
				if ($zbsSendAttachments > 0)
					$zbsSendAttachments = 1;
				else
					$zbsSendAttachments = -1;

				// we also pass zbsid to a meta, so that if anyone quarms with the missing old-dal ID we can retrieve it
				// and we add to id_override
				$zbsID = get_post_meta($cptObject->ID, 'zbsid', true);

			if ($debug) echo 'Quote CPT:<pre>'.print_r($fullObj,1).'</pre>';

			// convert obj to correct obj
			$id = -1; $owner = -1; $extraMeta = array('prev3id' => $fullObj['id']);
			$dataArr = array(
				
				'id_override' => $zbsID,
				'title' => ((isset($fullObj['meta']['name'])) ? $fullObj['meta']['name'] : ''),
				'currency' => '', // not uses v2 or v3 vanilla
				'value' => ((isset($fullObj['meta']['val'])) ? $fullObj['meta']['val'] : ''),
				'date' => ((isset($fullObj['meta']['date'])) ? zeroBSCRM_locale_dateToUTS($fullObj['meta']['date']) : ''),
				'template' => '',
				'content' => ((isset($fullObj['quotebuilder']) && isset($fullObj['quotebuilder']['content'])) ? $fullObj['quotebuilder']['content'] : ''),
				'notes' => ((isset($fullObj['meta']['notes'])) ? $fullObj['meta']['notes'] : ''),
                'send_attachments' => $zbsSendAttachments,
				'hash' => '',
				'lastviewed' => '',
				'viewed_count' => '',
				'accepted' => '',
				'acceptedsigned' => '',
				'acceptedip' => '',
				'lastupdated' => '',

				// lineitems:
				'lineitems'     => false, 

				// obj links:
				'contacts' => false,
				'companies' => false,

				'tags' => -1,

				'externalSources' => -1,

				'created' => strtotime($cptObject->post_date_gmt)

            );

			if ($debug) echo '<hr />Quote Arr Stage 1:<pre>'.print_r($dataArr,1).'</pre>';

			// retrieve $owner
			$owner = (int)get_post_meta($fullObj['id'], 'zbs_owner', true); if ($owner < 1) $owner = -1;

            // accepted
            if (isset($fullObj['meta']['accepted']) && is_array($fullObj['meta']['accepted'])){

				// accepted uts            	
            	if (isset($fullObj['meta']['accepted'][0]) && $fullObj['meta']['accepted'][0] > 0) $dataArr['accepted'] = $fullObj['meta']['accepted'][0];

            	// accepted by (str - will be email of signer)    	
            	if (isset($fullObj['meta']['accepted'][1]) && !empty($fullObj['meta']['accepted'][1])) $dataArr['acceptedsigned'] = $fullObj['meta']['accepted'][1];

            	// accepted by (ip)            	
            	if (isset($fullObj['meta']['accepted'][2]) && !empty($fullObj['meta']['accepted'][2])) $dataArr['acceptedip'] = $fullObj['meta']['accepted'][2];

            }

            // template
            // this will be the id of the quote template, but these will also be migrated, later, 
            // ... so zeroBSCRM_migration_migrate_zerobs_quo_template() will have to also go back and update these.
            // NOTE use of templateid not templated
            if (isset($fullObj['templateid']) && !empty($fullObj['templateid'])){

            	$t = (int)$fullObj['templateid'];
            	if ($t > 0) $dataArr['template'] = $t;

            	// if this is set, we'll use extrameta to pass it down the chain to zeroBSCRM_migration_migrate_zerobs_quo_template()
            	if ($t > 0) $extraMeta['prev3template'] = $t;

            }

			// custom fields
			$customFieldDB2toDB3Matrix = get_option( 'zbs_db_migration_300_cftrans', array() );
			$customFieldsDB3 = $zbs->DAL->getActiveCustomFields(array('objtypeid'=>ZBS_TYPE_QUOTE));

				// obj custom fields
				if (isset($customFieldDB2toDB3Matrix['quotes']) && is_array($customFieldDB2toDB3Matrix['quotes'])){

					// cycle through + translate
					foreach ($customFieldDB2toDB3Matrix['quotes'] as $db1Key => $db2key){

							if ($debug) echo 'Probing for cf: "'.$db1Key.'" which becomes "'.$db2key.'" => ';

							if (isset($fullObj['meta'][$db1Key])) {

								$dataArr[$db2key] = $fullObj['meta'][$db1Key];

								if ($debug) echo '"'.$fullObj['meta'][$db1Key].'"';

							}

							// translate date fields to uts if date :)
							if (isset($dataArr[$db2key]) && !empty($dataArr[$db2key]) && isset($customFieldsDB3[$db2key]) && is_array($customFieldsDB3[$db2key]) && isset($customFieldsDB3[$db2key][0])){

								// if type = date
								if ($customFieldsDB3[$db2key][0] == 'date'){

									// translate
									$dataArr[$db2key] = (int)zeroBSCRM_locale_dateToUTS($dataArr[$db2key]);

								}
							}
							
							if ($debug) echo '<br />';

					}

				}

			if ($debug) echo '<hr />Quote Arr Stage 2:<pre>'.print_r($dataArr,1).'</pre>';

			// TAGS - Quotes v2 doesn't have

			// EXTERNAL SOURCES - Quotes v2 doesn't have

			// Contact against quote
			$quoteContact = array(); if (isset($fullObj['customerid']) && $fullObj['customerid'] > 0) $quoteContact = array($fullObj['customerid']);

			if ($debug) echo 'Contact against Quote: <pre>'.print_r($quoteContact,1).'</pre>';

			// add to new quote contact obj so it automaps
			if (is_array($quoteContact) && count($quoteContact) > 0){

				$dataArr['contacts'] = $quoteContact;

			}

			// hash gets automatically generated in addUpdateQuote

			if ($debug) echo '<hr />Quote Arr Stage 3:<pre>'.print_r($dataArr,1).'</pre>';


		    // we need to load the classes temporarily so we can get the objmodels so we don't allow
		    // collisions between cf keys + new fields				    
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.ObjectLayer.php');
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.Quotes.php');
    		global $tempQuotesClass; // global because addUpdate below uses this global to check any unique fields/model
            $tempQuotesClass = new zbsDAL_quotes;

			// insert via temp dal3 class
			$insertedID = $tempQuotesClass->addUpdateQuote(array(

	            'id'            => $id,
	            'owner'         => $owner,

	            // fields (directly)
	            'data'          => $dataArr,

	            'limitedFields' => -1,
	            'silentInsert' => true,

	            // retain the old 'Quote ID'
	            'extraMeta' => $extraMeta

			));

			// worked?
			if ($insertedID > 0){

				if ($debug) echo 'Successful insert:'.$insertedID.'!<br />';

				// Copy Files
				zeroBSCRM_files_moveFilesToNewObjectv3('quote',$fullObj['id'],$insertedID);

				// passed, return
				return $insertedID;

			} else {

				// field uniqueness errors?
				// ...if so, '-n' the field and log fact

                // fails
                $failedOnErrors = $zbs->DAL->getErrors(ZBS_TYPE_QUOTE);

                // debug
                if ($debug) echo 'Failed, errors:<pre>'.print_r($failedOnErrors,1).'</pre>';

                // cycle through & find 301 (failed uniqueness)
                $otherErrors = false;
                if (is_array($failedOnErrors)) foreach ($failedOnErrors as $error){

                	// any errors logged?
                	if (is_array($error) && isset($error['code'])){

                		// 301? - note there wasn't any force_uniques on quote fields as at v3.0
                		if ($error['code'] == 301){

                			// there is a field uniqueness fail:

                			if ($debug) echo 'Field uniqueness collision:<pre>'.print_r($error,1).'</pre>';

                			// add to error stack
                			$errMsg = '['.__('Quote','zero-bs-crm').'] '.__('There was an error migrating an object (non-unique field found)','zero-bs-crm');
                			$errMsg .= '<br />'.__('Quote','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');               			

                		} // / if 301
                		else {

                			//... could have failed on insert/update?
                			$otherErrors = true;

                			// add to error stack
                			$errMsg = '['.__('Quote','zero-bs-crm').'] '.__('There was an error migrating an object (could not insert)','zero-bs-crm');
                			$errMsg .= '<br />'.__('Quote','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');

                		}

                	}

                } // / foreach error

			} // / didn't insert successfully


   		} // is valid obj

   		// return fail, so ball keeps rolling - should be logged in err stack if errs
   		//exit(__('Error Migrating object'));
   		return false;
   		
   }

   // Migrate zerobs_invoice - 4
   // take a cpt object from < 3.0 (DAL2), and migrate it into DAL3 obj
   function zeroBSCRM_migration_migrate_zerobs_invoice($cptObject=false){

   		$debug = false;

   		if (is_object($cptObject)){

   			global $zbs,$zbsTaxRateTable;

   			// retreive
   			$fullObj = zeroBS_getInvoice($cptObject->ID,true);   	

			// tax prep
			// ... because we're doing a load of these per call, we can cache the taxrate table
			if (!isset($zbsTaxRateTable)) $zbsTaxRateTable = zeroBSCRM_getTaxTableArr();		

   			/* as well as the logic below (common usage in core crm invoicing)
   			... these post meta keys had values set by inv pro... and will need somehow moving over 
   			... + = dealt with below
			+ zbsid
			+ zbs_company_invoice_company
   			+ zbs_customer_invoice_meta (this is the main meta key - legacy old name!)
   			+ zbs_inv_sendattachments
   			+ zbsInvoiceHorQ
   			+ zbs_invoice_totals
   			zbs_stripe_charge_id
   			- (this was superflous fodder) zbs_invoice_id
   			// Transaction -> zbs_customer_invoice_customer (set in inv pro ext) - customer id
   			// Transaction -> zbs_invoice_partials (set in inv pro ext) - invoice id? ¯\_(ツ)_/¯
   			// Transaction -> zbs_parent_cust (set in inv pro ext) - customer id
   			*/

   			// <v3.0 we calculated discounts on total, not on net, 
   			// ... this means that v3.0 calculating on net will mean some invoice values will be changed
   			// ... because v3.0 autocalcs invoices on saving.
   			// We suspect the number of users using discounts to be an edge case,
   			// ... so for Release candidate we'll just LOG these differences to guage the net change
            // ... because we need a full invoice obj layer to recalc the invoice, we have to save as meta
            // ... and then do this comparison as part of the migration closure jobs (to catch discrepencies + notify)
   			//$originalTotal = false; $newTotal = false;

   				// meta
   				// this was a bit of a spagetti mess < v3 ¯\_(ツ)_/¯         	

				// Send attachments?
				$zbsSendAttachments = get_post_meta($cptObject->ID, 'zbs_inv_sendattachments', true);
				// force format
				if ($zbsSendAttachments > 0)
					$zbsSendAttachments = 1;
				else
					$zbsSendAttachments = -1;

				// retrieve address to objtype (add_com_con prev)
				$addressToObjType = ''; if (isset($fullObj['meta']['add_com_con'])){

					if ($fullObj['meta']['add_com_con'] == 'con') $addressToObjType = ZBS_TYPE_CONTACT;
					if ($fullObj['meta']['add_com_con'] == 'com') $addressToObjType = ZBS_TYPE_COMPANY;

				}

				// Hours or quantity?
				$hoursOrQuantity = get_post_meta($cptObject->ID, 'zbsInvoiceHorQ', true);
				// force format (0 hours, 1 quantity)
				if ($hoursOrQuantity == 'hours')
					$hoursOrQuantity = 0;
				else
					$hoursOrQuantity = 1;

				// dates
				$dateUTS = ((isset($fullObj['meta']['date'])) ? (int)zeroBSCRM_locale_dateToUTS($fullObj['meta']['date']) : '');
				$dueDateUTS = ''; if ($dateUTS !== '' && $dateUTS > 0 && isset($fullObj['meta']['due'])){

					$dueDateDays = (int)$fullObj['meta']['due'];
					if ($dueDateDays > -1) $dueDateUTS = $dateUTS+(86400*$dueDateDays);

				}

				// total set in cpt
				$total = 0.0; if (isset($fullObj['val'])) $total = $fullObj['val'];

				// totals array stuff?
				$discount = ''; $discountType = ''; $net = ''; $postageTotal = ''; $postageTaxTotal = ''; $taxTotal = ''; $postageTaxRate = 0; $postageTaxStr = '';
                $invoiceTotalsArray = get_post_meta($cptObject->ID,"zbs_invoice_totals",true);
                if (is_array($invoiceTotalsArray)){

                	// no casting or type checking. Relies on legitimacy of set arr (should be okay here)
                	
                		// this is always the 'discount value' ($ or %)
                		if (isset($invoiceTotalsArray['invoice_discount_total'])) $discount = $invoiceTotalsArray['invoice_discount_total'];

                		// invoice_discount_total_value would be the $ always, but we need ^^ 

                		// discount type
                		if (isset($invoiceTotalsArray['invoice_discount_type'])) $discountType = $invoiceTotalsArray['invoice_discount_type'];

                		// subtotal val = net?
                		if (isset($invoiceTotalsArray['zbs-subtotal-value'])) $net = $invoiceTotalsArray['zbs-subtotal-value'];

                		// Postage
                		if (isset($invoiceTotalsArray['invoice_postage_total'])) $postageTotal = $invoiceTotalsArray['invoice_postage_total'];
                		if (isset($invoiceTotalsArray['invoice_postage_tax'])) $postageTaxRate = (float)$invoiceTotalsArray['invoice_postage_tax'];
                		// $postageTaxStr rate
	                	// only bother if has actual tax
						if ($postageTaxRate > 0){

							$postageTaxRateName = __('General Invoice Tax','zero-bs-crm').' '.$postageTaxRate.'%';

							// cycle through and see if we have a matching tax rate
							if (is_array($zbsTaxRateTable)) foreach ($zbsTaxRateTable as $taxRate){

								// if it's a migration-generated taxrate + matches percentage, use
								$thisRate = (float)$taxRate['rate'];
								if ($thisRate == $postageTaxRate && $taxRate['name'] == $postageTaxRateName){

									// use this one
									$postageTaxStr = $taxRate['id'];
									break;

								}

							} // / foreach

							// if no taxrate found, make one
							if ($postageTaxStr == ''){

								// add/update
								$potentialTaxRateID = zeroBSCRM_taxRates_addUpdateTaxRate(array(

								      //'id' =>
								      'data'          => array(

								          'name'   => $postageTaxRateName,
								          'rate'   => $postageTaxRate
								          
								      )

								));

								// reload table
								$zbsTaxRateTable = zeroBSCRM_getTaxTableArr();

								if (!empty($potentialTaxRateID))

									$postageTaxStr = $potentialTaxRateID;

								else {

									// FAILED to make tax rate, these'll go in as no-tax and *change* total?
									// ? #iffailledtaxrate
									// deferring, absolute fringe cases

								}

							} // / if no tax rate

							// calc postage tax total
							$postageTaxTotal = 0;
							if ($postageTotal > 0) $postageTaxTotal = round((float)($postageTotal * ($postageTaxRate/100)),2);


						}


                		// tax
                		if (isset($invoiceTotalsArray['invoice_tax_total'])) $taxTotal = $invoiceTotalsArray['invoice_tax_total'];
                
                		// total
                		if (isset($invoiceTotalsArray['invoice_grandt_value']) && !empty($invoiceTotalsArray['invoice_grandt_value'])) $total = $invoiceTotalsArray['invoice_grandt_value'];
                

                }

            // reference / zbsid - get's compounded if zbsid + reference set
            $newRef = ((isset($fullObj['zbsid'])) ? $fullObj['zbsid'] : '');
            if (isset($fullObj['ref']) && !empty($fullObj['ref'])){

            	if (empty($newRef))
            		$newRef = $fullObj['ref'];
            	else
            		$newRef = $newRef.' - '.$fullObj['ref'];

            }

            // pay via
            $payVia = 0;
            /* -1 = bacs/can'tpay online
				0 = default/no setting
				1 = paypal
				2 = stripe
				3 = worldpay
			*/
            // v2 '0' gets translated into -1 here, based on MS input 5/11/19
            if (isset($fullObj['zbs_payvia']) && $fullObj['zbs_payvia'] == 0) $payVia = -1;

			if ($debug) echo 'Invoice CPT:<pre>'.print_r($fullObj,1).'</pre>';
			if ($debug) echo 'Invoice LineItems:<pre>'.print_r(get_post_meta($cptObject->ID,'zbs_invoice_lineitems',true),1).'</pre>';
			if ($debug) echo 'invoiceTotalsArray:<pre>'.print_r($invoiceTotalsArray,1).'</pre>';

			// convert obj to correct obj
			$id = -1; $owner = -1; $extraMeta = array('prev3id' => $fullObj['zbsid'],'prev3cptid' => $fullObj['id']);
			$dataArr = array(

                'id_override' => $newRef,
                'parent' => '', // not used in v2 or v3 mvp
                'status' => ((isset($fullObj['meta']['status'])) ? $fullObj['meta']['status'] : __('Draft','zero-bs-crm')),
                'hash' => '', // will be auto-genned on addUpdate
                'pdf_template' => '', // precursor for v3.0+ stuff
                'portal_template' => '', // precursor for v3.0+ stuff
                'email_template' => '', // precursor for v3.0+ stuff
                'invoice_frequency' => '', // precursor for v3.0+ stuff
                'currency' => '', // precursor for v3.0+ stuff
                'pay_via' => $payVia, // pay via (online/bacs)

                'logo_url' => ((isset($fullObj['meta']['logo'])) ? $fullObj['meta']['logo'] : ''),
                'address_to_objtype' => $addressToObjType,
                'addressed_from' => '', // precursor for v3.0+ stuff
                'addressed_to' => '', // precursor for v3.0+ stuff
                'allow_partial' => -1, // precursor for v3.0+ stuff
                'allow_tip' => -1, // precursor for v3.0+ stuff
                'send_attachments' => $zbsSendAttachments,
                'hours_or_quantity' => $hoursOrQuantity,
                'date' => $dateUTS,
                'due_date' => $dueDateUTS,
                'paid_date' => '', // see inv pro (MS Says) - but couldn't find - https://a8c.slack.com/archives/DKKLLN3D0/p1564149188001500

                'hash_viewed' => '',  // precursor for v3.0+ stuff
                'hash_viewed_count' => '', // precursor for v3.0+ stuff
                'portal_viewed' => '', // precursor for v3.0+ stuff
                'portal_viewed_count' => '', // precursor for v3.0+ stuff
                'net' => $net,
                'discount' => $discount, 
                'discount_type' => $discountType, // seems to be 'm' or '%'
                'shipping' => $postageTotal,
                'shipping_taxes' => $postageTaxStr, // precursor for v3.0+ stuff
                'shipping_tax' => $postageTaxTotal,
                'taxes' => '', // precursor for v3.0+ stuff
                'tax' => $taxTotal,
                'total' => $total,
                'created' => strtotime($cptObject->post_date_gmt),
                'lastupdated' => '',

                // fill below
                'lineitems'     => false, 
                'contacts' => false,
                'companies' => false,
                'tags' => -1,
                'externalSources' => -1,

            );

            // pass this down for total-comparison
            // ... because we need a full invoice obj layer to recalc the invoice, we have to save as meta
            // ... and then do this comparison as part of the migration closure jobs (to catch discrepencies + notify)
            $extraMeta['prev3_total'] = $total; //$originalTotal = $total;

			if ($debug) echo '<hr />Invoice Arr Stage 1:<pre>'.print_r($dataArr,1).'</pre>';

			// retrieve $owner
			$owner = (int)get_post_meta($fullObj['id'], 'zbs_owner', true); if ($owner < 1) $owner = -1;

			// custom fields
			$customFieldDB2toDB3Matrix = get_option( 'zbs_db_migration_300_cftrans', array() );

				// obj custom fields
				if (isset($customFieldDB2toDB3Matrix['invoices']) && is_array($customFieldDB2toDB3Matrix['invoices'])){

					// cycle through + translate
					foreach ($customFieldDB2toDB3Matrix['invoices'] as $db1Key => $db2key){

							if ($debug) echo 'Probing for cf: "'.$db1Key.'" which becomes "'.$db2key.'" => ';

							if (isset($fullObj['meta'][$db1Key])) {

								$dataArr[$db2key] = $fullObj['meta'][$db1Key];

								if ($debug) echo '"'.$fullObj['meta'][$db1Key].'"';

							}
							
							if ($debug) echo '<br />';

					}

				}

			if ($debug) echo '<hr />Invoice Arr Stage 2:<pre>'.print_r($dataArr,1).'</pre>';

			// LINE ITEMS
			// these need to be taken aside as the main inv obj layer expects dal3 complete
			// ... but we're in a half state during migration
            $invLineItems = get_post_meta($cptObject->ID,'zbs_invoice_lineitems',true);

			// cycle through + format
			if (is_array($invLineItems) && count($invLineItems) > 0) {

				// construct $lineItems
				$lineItems = array(); $indx = 0;

				foreach ($invLineItems as $invoiceLineItem){

					// vars
					$quantity = ((isset($invoiceLineItem['zbsli_quan'])) ? $invoiceLineItem['zbsli_quan'] : 0);
					$price = ((isset($invoiceLineItem['zbsli_price'])) ? $invoiceLineItem['zbsli_price'] : 0);

					// line-item tax
					// here we have to do a conversion on the fly, because <3.0 we just logged a % for tax per line-item
					// ... but now we're using a rates table
					// ... we make a 'spot tax' rate if there's not already a spottax rate.
					$taxPercentage = (int)((isset($invoiceLineItem['zbsli_tax'])) ? $invoiceLineItem['zbsli_tax'] : 0);
					$taxRatesStr = '';
					$taxRateName = __('General Tax','zero-bs-crm').' '.$taxPercentage.'%';

					// only bother if has actual tax
					if ($taxPercentage > 0){

						// cycle through and see if we have a matching tax rate
						if (is_array($zbsTaxRateTable)) foreach ($zbsTaxRateTable as $taxRate){

							// if it's a migration-generated taxrate + matches percentage, use
							$thisRate = (int)$taxRate['rate'];
							if ($thisRate == $taxPercentage && $taxRate['name'] == $taxRateName){

								// use this one
								$taxRatesStr = $taxRate['id'];
								break;

							}

						} // / foreach

						// if no taxrate found, make one
						if ($taxRatesStr == ''){

							// add/update
							$potentialTaxRateID = zeroBSCRM_taxRates_addUpdateTaxRate(array(

							      //'id' =>
							      'data'          => array(

							          'name'   => $taxRateName,
							          'rate'   => $taxPercentage
							          
							      )

							));

							// reload table
							$zbsTaxRateTable = zeroBSCRM_getTaxTableArr();

							if (!empty($potentialTaxRateID))

								$taxRatesStr = $potentialTaxRateID;

							else {

								// FAILED to make tax rate, these'll go in as no-tax and *change* total?
								// ? #iffailledtaxrate
								// deferring, absolute fringe cases

							}

						} // / if no tax rate

						// calc tax total
						$taxTotal = 0;
						$net = $quantity * $price;
						if ($net > 0) $taxTotal = round((float)($net * ($taxPercentage/100)),2);


					}

					// create line item:
					// note that a number of these are prep for v3.0+ functionality, so will be passed blank from v2
					$li = array(
				                'order' => $indx,
				                'title'  => ((isset($invoiceLineItem['zbsli_itemname'])) ? $invoiceLineItem['zbsli_itemname'] : ''),
				                'desc' => ((isset($invoiceLineItem['zbsli_des'])) ? $invoiceLineItem['zbsli_des'] : ''),
				                'quantity' => $quantity,
				                'price' => $price,
				                'currency' => '',
				                'net' => '',
				                'discount' => '',
				                'fee' => '',
				                'shipping' => '',
				                'shipping_taxes' => '',
				                'shipping_tax' => '',
				                'taxes' => $taxRatesStr,
				                'tax' => $taxTotal,
				                'total' => ((isset($invoiceLineItem['zbsli_rowt'])) ? $invoiceLineItem['zbsli_rowt'] : ''),
				                'created' => '',
				                'lastupdated' => ''				                
				            );


					$lineItems[] = $li; $indx++;

				}

				// add
				// passing via new inv obj doesn't work as the main inv obj layer expects dal3 complete
				// ... but we're in a half state during migration
				// ... so we do them manually after insertion below.
				// if (is_array($lineItems) && $lineItems > 0) $dataArr['lineitems'] = $lineItems;

			}


            // if zbs_stripe_charge_id, pass it along :)
            $stripeChargeMeta = get_post_meta($cptObject->ID,'zbs_stripe_charge_id',true);
            if (isset($stripeChargeMeta) && !empty($stripeChargeMeta)) $extraMeta['zbs_stripe_charge_id'] = $stripeChargeMeta;

			// EXTERNAL SOURCES - None in V2 Invoicing

			// TAGS - None in V2 Invoicing
	
			// Contact against invoice
			$invoiceContact = array(); if (isset($fullObj['customerid']) && $fullObj['customerid'] > 0) $invoiceContact = array($fullObj['customerid']);

			if ($debug) echo 'Contact against invoice: <pre>'.print_r($invoiceContact,1).'</pre>';

			// add to new company obj so it automaps
			if (is_array($invoiceContact) && count($invoiceContact) > 0) $dataArr['contacts'] = $invoiceContact;

			// Company against invoice

            	//$fullObj['companyid'] = get_post_meta($cptObject->ID, 'zbs_company_invoice_company', true);
            	// however... V2->V3 (this) migration routine moves companies into DAL2 objs, so the 'new' correct ID should be in 'zbs_parent_co_dal2'
            	// ... so disregard this old setting, which would now house the old CPT ID of the Co
            	$fullObj['companyid'] = get_post_meta($cptObject->ID, 'zbs_parent_co_dal2', true);   

				// Company against invoice (using zbs_parent_co_dal2 as co will have been migrated to new ID)
				$invoiceCompany = array(); if (isset($fullObj['companyid']) && $fullObj['companyid'] > 0) $invoiceCompany = array($fullObj['companyid']);

				if ($debug) echo 'Company against invoice: <pre>'.print_r($invoiceCompany,1).'</pre>';

				// add to new company obj so it automaps
				if (is_array($invoiceCompany) && count($invoiceCompany) > 0) $dataArr['companies'] = $invoiceCompany;

			// Do we need to distinguish here? (this code from inv metabox v2:)
			/*
            if(isset($zbsCustomer['add_com_con'])){
                $co_con = $zbsCustomer['add_com_con'];
            }else{
                $co_con = 'con';
            }
            */

			if ($debug) echo '<hr />Invoice Arr Stage 3:<pre>'.print_r(array('data'=>$dataArr,'extrameta'=>$extraMeta),1).'</pre>';

		    // we need to load the classes temporarily so we can get the objmodels so we don't allow
		    // collisions between cf keys + new fields				    
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.ObjectLayer.php');
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.Invoices.php');
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.LineItems.php');
    		global $tempInvoicesClass; // global because addUpdate below uses this global to check any unique fields/model
    		$tempInvoicesClass = new zbsDAL_invoices;
    		$tempLineItemsClass = new zbsDAL_lineitems; // lineitems included to add these separately below (not ideal but internally in addUpdateInvoice it expects v3.0 lineitems)

			// insert via temp dal3 class
			$insertedID = $tempInvoicesClass->addUpdateInvoice(array(

	            'id'            => $id,
	            'owner'         => $owner,

	            // fields (directly)
	            'data'          => $dataArr,

	            'limitedFields' => -1,
	            'silentInsert' => true,

	            // retain the old 'Quote ID'
	            'extraMeta' => $extraMeta

			));

			// ... if failed, was it because of uniqueness? if so, '-n' the field and log fact
			if ($insertedID > 0){

				if ($debug) echo 'Successful insert:'.$insertedID.'!<br />';

				// Copy Files
				zeroBSCRM_files_moveFilesToNewObjectv3('invoice',$fullObj['id'],$insertedID);

				// Line Items
                if (isset($lineItems) && is_array($lineItems)) foreach ($lineItems as $lineitem) {

                    // slight rejig of passed so works cleanly with data array style
                    $lineItemID = false; if (isset($lineitem['ID'])) $lineItemID = $lineitem['ID'];
                    $tempLineItemsClass->addUpdateLineitem(array(
                        'id'=>$lineItemID,
                        'linkedObjType' => ZBS_TYPE_INVOICE,
                        'linkedObjID' => $insertedID,
                        'data'=>$lineitem
                        ));

                }

				// passed, return
				return $insertedID;

			} else {

				// field uniqueness errors?
				// ...if so, '-n' the field and log fact

                // fails
                $failedOnErrors = $zbs->DAL->getErrors(ZBS_TYPE_INVOICE);

                // debug
                if ($debug) echo 'Failed, errors:<pre>'.print_r($failedOnErrors,1).'</pre>';

                // cycle through & find 301 (failed uniqueness)
                $otherErrors = false;
                if (is_array($failedOnErrors)) foreach ($failedOnErrors as $error){

                	// any errors logged?
                	if (is_array($error) && isset($error['code'])){

                		// 301?
                		if ($error['code'] == 301){

                			// there is a field uniqueness fail:

                			if ($debug) echo 'Field uniqueness collision:<pre>'.print_r($error,1).'</pre>';

                			// add to error stack
                			$errMsg = '['.__('Invoice','zero-bs-crm').'] '.__('There was an error migrating an object (non-unique field found)','zero-bs-crm');
                			$errMsg .= '<br />'.__('Invoice','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');             			

                		} // / if 301
                		else {

                			//... could have failed on insert/update?
                			$otherErrors = true;

                			// add to error stack
                			$errMsg = '['.__('Invoice','zero-bs-crm').'] '.__('There was an error migrating an object (could not insert)','zero-bs-crm');
                			$errMsg .= '<br />'.__('Invoice','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');

                		}

                	}

                } // / foreach error

			} // / didn't insert successfully


   		} // is valid obj

   		// return fail, so ball keeps rolling - should be logged in err stack if errs
   		//exit(__('Error Migrating object'));
   		return false;

   }

   // Migrate zerobs_transaction - 5
   // take a cpt object from < 3.0 (DAL2), and migrate it into DAL3 obj
   function zeroBSCRM_migration_migrate_zerobs_transaction($cptObject=false){

   		$debug = false;

   		if (is_object($cptObject)){

   			global $zbs,$zbsTaxRateTable;

   			// retreive
   			$fullObj = zeroBS_getTransaction($cptObject->ID);   	

			// tax prep
			// ... because we're doing a load of these per call, we can cache the taxrate table
			if (!isset($zbsTaxRateTable)) $zbsTaxRateTable = zeroBSCRM_getTaxTableArr();		

   				// meta
   				// this was a bit of a spagetti mess < v3 ¯\_(ツ)_/¯

				// vals 
				$total = ((isset($fullObj['meta']['total'])) ? $fullObj['meta']['total'] : 0.0);
				$tax = ((isset($fullObj['meta']['tax'])) ? $fullObj['meta']['tax'] : 0.0);

				// tax
				// here we have to do a conversion on the fly, because <3.0 we logged a % and a £, sometimes...¯\_(ツ)_/¯
				// ... but now we're using a rates table
				// ... we make a 'spot tax' rate if there's not already a spottax rate.
				$taxPercentage = (int)((isset($fullObj['meta']['tax_rate'])) ? $fullObj['meta']['tax_rate'] : 0.0);
				$taxRatesStr = '';
				$taxRateName = __('General Tax','zero-bs-crm').' '.$taxPercentage.'%';

				// only bother if has actual tax
				if ($taxPercentage > 0){

					// cycle through and see if we have a matching tax rate
					if (is_array($zbsTaxRateTable)) foreach ($zbsTaxRateTable as $taxRate){

						// if it's a migration-generated taxrate + matches percentage, use
						$thisRate = (int)$taxRate['rate'];
						if ($thisRate == $taxPercentage && $taxRate['name'] == $taxRateName){

							// use this one
							$taxRatesStr = $taxRate['id'];
							break;

						}

					} // / foreach

					// if no taxrate found, make one
					if ($taxRatesStr == ''){

						// add/update
						$potentialTaxRateID = zeroBSCRM_taxRates_addUpdateTaxRate(array(

						      //'id' =>
						      'data'          => array(

						          'name'   => $taxRateName,
						          'rate'   => $taxPercentage
						          
						      )

						));

						// reload table
						$zbsTaxRateTable = zeroBSCRM_getTaxTableArr();

						if (!empty($potentialTaxRateID))

							$taxRatesStr = $potentialTaxRateID;

						else {

							// FAILED to make tax rate, these'll go in as no-tax and *change* total?
							// ? #iffailledtaxrate
							// deferring, absolute fringe cases

						}

					} // / if no tax rate

					// calc tax total
					// here we only do this if 'tax' not set (which it may be, someplaces was being pre v3.0)
					if ($tax <= 0){
						if ($net > 0) $tax = round((float)($net * ($taxPercentage/100)),2);
					}


				}

				// type - if it's a - assume it's a refund/credit note?
				$type = __('Sale','zero-bs-crm'); // array('Sale','Refund','Credit Note')
				if ($total < 0) $type = __('Refund','zero-bs-crm');

				// date
				// this isn't properly saved <3.0, it's set to the same as 'date' :/ (even post->post_date_gmt)
				$date = ((isset($fullObj['meta']['trans_time'])) ? $fullObj['meta']['trans_time'] : time());

				// associated Invoice
				// this is set in meta['invoice_id'] - but that's the CPT of the associated invoice.
				// ... which'll now be a zbsid... so needs translation
				$associatedInvoiceID = false;
				if (isset($fullObj['meta']['invoice_id']) && $fullObj['meta']['invoice_id'] > 0){

					// has $fullObj['meta']['invoice_id']
					$potentialInvoiceCPTID = (int)$fullObj['meta']['invoice_id'];
					if ($potentialInvoiceCPTID > 0){

						// find the new zbsid and set
						global $ZBSCRM_t;

			            #} Build query
			            $query = "SELECT zbsm_objid FROM ".$ZBSCRM_t['meta']." WHERE zbsm_objtype = %d AND zbsm_key = %s AND zbsm_val = %d";

                		#} Prep & run query
			            global $wpdb;
		                $queryObj = $wpdb->prepare($query,array(ZBS_TYPE_INVOICE,'extra_prev3cptid',$potentialInvoiceCPTID));
		                $associatedInvoiceID = (int)$wpdb->get_var($queryObj);

		                // check
		                if ($associatedInvoiceID <= 0) $associatedInvoiceID = false;

					}
				} else {

					// if invoice_id not set, it can also be stored in this meta: zbs_invoice_partials (non-clear naming)
					// ... invoicing pro does this
					// this catch is here to account for those that didn't get invoice_id but did get this meta? ¯\_(ツ)_/¯
					$potentialAssociatedInvID = (int)get_post_meta($cptObject->ID,'zbs_invoice_partials',true);
					if ($potentialAssociatedInvID > 0) $associatedInvoiceID = $potentialAssociatedInvID;

				}

			if ($debug) echo 'Transaction CPT:<pre>'.print_r($fullObj,1).'</pre>';
			if ($debug) echo 'Transaction LineItems:<pre>'.print_r(get_post_meta($cptObject->ID,'zerobscrm_lineitems',true),1).'</pre>';

			// convert obj to correct obj
			$id = -1; $owner = -1; $extraMeta = array('prev3cptid' => $fullObj['id']); //'prev3id' => $fullObj['zbsid'] (not for trans)
			$dataArr = array(
            
                'status' => ((isset($fullObj['meta']['status'])) ? $fullObj['meta']['status'] : __('Succeeded','zero-bs-crm')),
                'type' => $type,
                'ref' => ((isset($fullObj['meta']['orderid'])) ? $fullObj['meta']['orderid'] : ''),
                'origin' => '', // precursor for v3.0+ stuff
                'parent' => '', // precursor for v3.0+ stuff
                'hash' => '', // precursor for v3.0+ stuff
                'title' => ((isset($fullObj['meta']['item'])) ? $fullObj['meta']['item'] : ''),
                'desc' => '', // precursor for v3.0+ stuff
                'date' => $date, 
                'customer_ip' => '', // precursor for v3.0+ stuff
                'currency' => ((isset($fullObj['meta']['currency'])) ? $fullObj['meta']['currency'] : ''), // ONLY saved in woosync pre v3.0
                'net' => ((isset($fullObj['meta']['net'])) ? $fullObj['meta']['net'] : ''),
                'discount' => ((isset($fullObj['meta']['discount'])) ? $fullObj['meta']['discount'] : ''),
                'shipping' => ((isset($fullObj['meta']['shipping'])) ? $fullObj['meta']['shipping'] : ''), // ONLY saved in woosync pre v3.0
                'shipping_taxes' => '', // precursor for v3.0+ stuff
                'shipping_tax' => '', // precursor for v3.0+ stuff
                'taxes' => '', // tax_rate
                'tax' => $tax, // tax_rate * total
                'total' => $total,
                'date_paid' => ((isset($fullObj['meta']['paid'])) ? strtotime($fullObj['meta']['paid']) : ''), // ONLY saved in woosync pre v3.0
                'date_completed' => ((isset($fullObj['meta']['completed'])) ? strtotime($fullObj['meta']['completed']) : ''), // ONLY saved in woosync pre v3.0
                'created' => $date, // this isn't properly saved <3.0, it's set to the same as 'date' :/ (even post->post_date_gmt)
                'lastupdated' => '',

                // lineitems:
                'lineitems'     => false, 

                // custom fields

                // obj links:
                'contacts' => false,
                'companies' => false,
                'invoice_id' => $associatedInvoiceID,

                'tags' => -1,

                'externalSources' => -1

			);

			if ($debug) echo '<hr />Transaction Arr Stage 1:<pre>'.print_r($dataArr,1).'</pre>';

			// retrieve $owner
			$owner = (int)get_post_meta($fullObj['id'], 'zbs_owner', true); if ($owner < 1) $owner = -1;         

			// custom fields
			$customFieldDB2toDB3Matrix = get_option( 'zbs_db_migration_300_cftrans', array() );
			$customFieldsDB3 = $zbs->DAL->getActiveCustomFields(array('objtypeid'=>ZBS_TYPE_TRANSACTION));

				// obj custom fields
				if (isset($customFieldDB2toDB3Matrix['transactions']) && is_array($customFieldDB2toDB3Matrix['transactions'])){

					// cycle through + translate
					foreach ($customFieldDB2toDB3Matrix['transactions'] as $db1Key => $db2key){

							if ($debug) echo 'Probing for cf: "'.$db1Key.'" which becomes "'.$db2key.'" => ';

							// this was already 'db2' key at this point in the obj
							if (isset($fullObj['meta'][$db2key])) {

								$dataArr[$db2key] = $fullObj['meta'][$db2key];

								if ($debug) echo '"'.$fullObj['meta'][$db2key].'"';

							}

							if (isset($fullObj['meta'][$db1Key])) {

								$dataArr[$db2key] = $fullObj['meta'][$db1Key];

								if ($debug) echo '"'.$fullObj['meta'][$db1Key].'"';

							}

							// translate date fields to uts if date :)
							if (isset($dataArr[$db2key]) && !empty($dataArr[$db2key]) && isset($customFieldsDB3[$db2key]) && is_array($customFieldsDB3[$db2key]) && isset($customFieldsDB3[$db2key][0])){

								// if type = date
								if ($customFieldsDB3[$db2key][0] == 'date'){

									// translate
									$dataArr[$db2key] = (int)zeroBSCRM_locale_dateToUTS($dataArr[$db2key]);

								}
							}
							
							if ($debug) echo '<br />';

					}

				}

			if ($debug) echo '<hr />Transaction Arr Stage 2:<pre>'.print_r($dataArr,1).'</pre>';

			// TAGS
			$tags = wp_get_object_terms($fullObj['id'],'zerobscrm_transactiontag',array('order' => 'ASC','orderby' => 'name'));
			// cycle through + add to db if not present
			$tagIDs = array(); if (count($tags) > 0) {
				foreach ($tags as $t){

					$potentialID = (int)$zbs->DAL->getTag(-1,array(
						'objtype'	=> ZBS_TYPE_TRANSACTION, 
						'name' 		=> $t->name,
						'onlyID'	=> true,
						'ignoreowner' => true
						));

					if (empty($potentialID)){

						// add it
						$newTagID = $zbs->DAL->addUpdateTag(array(
							'data' => array(
								'objtype' 		=> ZBS_TYPE_TRANSACTION,
								'name' 			=> $t->name,
								'slug' 			=> $t->slug,
								'owner'			=> -1 // for now, tags = no owner :)//$newContactOwner
							)));

						if (!in_array($newTagID, $tagIDs)){

						 	$tagIDs[] = $newTagID;

						}


					} else if (!in_array($potentialID, $tagIDs)){

					 	$tagIDs[] = $potentialID;

					}

				}
			}
			if (is_array($tagIDs) && count($tagIDs) > 0) $dataArr['tags'] = $tagIDs;

			// Line items
			// these were not commonly used throughout DAL2, though a little untyped usage snuck into PayPalSync etc.			
			// these need to be taken aside as the main trans obj layer expects dal3 complete
            $lineItems = get_post_meta($cptObject->ID,'zerobscrm_lineitems',true);

			// External Sources
			// These are used in v2 Transactions, so we just need to translate cptID -> new obj ID
			// ... here we do this by loading from sql, then removing existing line, passing again here to the 'add'
			$extSource = $zbs->DAL->getExternalSource(-1,array(
			
	            'objectID'         => $fullObj['id'], 
	            'objectType'       => ZBS_TYPE_TRANSACTION,
				'ignoreowner'	   => true,
				'source'		   => -1

			));

			$clearExtSourceLine = -1;
			if (is_array($extSource) && isset($extSource['source']) && isset($extSource['uid'])){

				// translate to new data obj one
				// <3.0 was only ever 1 extsource, v3+ can have multiple, hence extra arr here
				// array(array('source'=>src,'uid'=>uid),multiple())
				$dataArr['externalSources'] = array(
					array(
						'source' 	=> $extSource['source'],
						'uid' 		=> $extSource['uid']
					)
				);

				// add to this so as to delete old if successful (below binman)
				$clearExtSourceLine = $extSource['id'];

			}
	
			// Contact against transaction 

				$transactionContact = array(); 
				// These seem to have been set as different things in extensions. ¯\_(ツ)_/¯
				// e.g. zbs_customer_invoice_customer, zbs_parent_cust in invoicing pro
				// ... but customerid as loaded from fullObj everywhere else... try and catch variants?
				// ... cascade override though, because the most sure bet is the $fullObj['customerid'] as far as I can see
				$possibleContactID = (int)get_post_meta($fullObj['id'], 'zbs_customer_invoice_customer', true);
				if (isset($possibleContactID) && $possibleContactID > 0) $transactionContact = array($fullObj['customerid']);
				$possibleContactID = (int)get_post_meta($fullObj['id'], 'zbs_parent_cust', true);
				if (isset($possibleContactID) && $possibleContactID > 0) $transactionContact = array($fullObj['customerid']);
				if (isset($fullObj['customerid']) && $fullObj['customerid'] > 0) $transactionContact = array($fullObj['customerid']);

				if ($debug) echo 'Contact against transaction: <pre>'.print_r($transactionContact,1).'</pre>';

				// add to new contact obj so it automaps
				if (is_array($transactionContact) && count($transactionContact) > 0) $dataArr['contacts'] = $transactionContact;


			// Company against transaction

	        	// however... V2->V3 (this) migration routine moves companies into DAL2 objs, so the 'new' correct ID should be in 'zbs_parent_co_dal2'
	        	// ... so disregard this old setting, which would now house the old CPT ID of the Co
	        	$fullObj['companyid'] = get_post_meta($cptObject->ID, 'zbs_parent_co_dal2', true);   


				// Company against transaction (using zbs_parent_co_dal2 as co will have been migrated to new ID)
				$transactionCompany = array(); if (isset($fullObj['companyid']) && $fullObj['companyid'] > 0) $transactionCompany = array($fullObj['companyid']);

				if ($debug) echo 'Company against transaction: <pre>'.print_r($transactionCompany,1).'</pre>';

				// add to new company obj so it automaps
				if (is_array($transactionCompany) && count($transactionCompany) > 0) $dataArr['companies'] = $transactionCompany;

			// Give WP attaches 1 extra meta: zbs_givewp_uid
			$givewp_uid = (int)get_post_meta($cptObject->ID,'zbs_givewp_uid',true);
			if ($givewp_uid > 0) $extraMeta['zbs_givewp_uid'] = $givewp_uid;

			if ($debug) echo '<hr />Transaction Arr Stage 3:<pre>'.print_r($dataArr,1).'</pre>';


		    // we need to load the classes temporarily so we can get the objmodels so we don't allow
		    // collisions between cf keys + new fields				    
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.ObjectLayer.php');
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.Transactions.php');
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.LineItems.php');
    		global $tempTransactionsClass; // global because addUpdate below uses this global to check any unique fields/model
    		$tempTransactionsClass = new zbsDAL_transactions;
    		$tempLineItemsClass = new zbsDAL_lineitems; // lineitems included to add these separately below (not ideal but internally in addUpdateInvoice it expects v3.0 lineitems)

			// insert via temp dal3 class
			$insertedID = $tempTransactionsClass->addUpdateTransaction(array(

	            'id'            => $id,
	            'owner'         => $owner,

	            // fields (directly)
	            'data'          => $dataArr,

	            'limitedFields' => -1,
	            'silentInsert' => true,

	            // retain the old 'Quote ID'
	            'extraMeta' => $extraMeta

			));

			// ... if failed, was it because of uniqueness? if so, '-n' the field and log fact
			if ($insertedID > 0){

				if ($debug) echo 'Successful insert:'.$insertedID.'!<br />';

				// extsources: delete old if successful (binman)
				if ($clearExtSourceLine > 0) $zbs->DAL->deleteExternalSource(array('id'=>$clearExtSourceLine));

				// Copy Files
				zeroBSCRM_files_moveFilesToNewObjectv3('transaction',$fullObj['id'],$insertedID);

				// Line Items
				// these were not commonly used throughout DAL2, though a little untyped usage snuck into PayPalSync etc.			
                if (is_array($lineItems)) foreach ($lineItems as $lineItem) {

                	/*

                	PayPal Sync (pre v3):

					$line_items[] = array(
						'name' 				=> $item_name,
						'desc' 				=> $number,
						'quantity'			=> $qty,
						'tax'				=> $taxamt,
						'ship'				=> $shipamt,
						'handle'			=> $handlingAmt,
						'currency'			=> $currency,
						'amount'			=> $amt
					);	

					*/

                	// need to rejig it, because these are saved in an untyped format 
                	$formattedForDAL2 = array(
                
			                'order' => '',
			                'title' => ((isset($lineItem['name'])) ? $lineItem['name'] : ''),
			                'desc' => ((isset($lineItem['desc'])) ? $lineItem['desc'] : ''),
			                'quantity' => ((isset($lineItem['quantity'])) ? $lineItem['quantity'] : ''),
			                'price' => '',
			                'currency' => ((isset($lineItem['currency'])) ? $lineItem['currency'] : ''),
			                'net' => '',
			                'discount' => '',
			                'fee' => ((isset($lineItem['handle'])) ? $lineItem['handle'] : ''),
			                'shipping' => ((isset($lineItem['ship'])) ? $lineItem['ship'] : ''),
			                'shipping_taxes' => '',
			                'shipping_tax' => '',
			                'taxes' => '',
			                'tax' => ((isset($lineItem['tax'])) ? $lineItem['tax'] : ''),
			                'total' => ((isset($lineItem['amount'])) ? $lineItem['amount'] : ''),
			                'lastupdated' => '',
			                'created' => -1,

					);

					// ... then we have to apply woo, because this has used different fields  ¯\_(ツ)_/¯
					/* 

					Woo Sync (pre v3):

					$line_items[] = array(
						'orderid'			=> $order_id, 			/ not used
						'customer' 			=> $customerID,			/ not used
						'status' 			=> $order_status,		/ not used (transaction level)
						'gross'				=> $line_total,			/ gross + total + amount? ???
						'amount'			=> $line_total,
						'total'				=> $line_total,
						'name' 				=> $product_name,
						'desc' 				=> $item_id,
						'quantity'			=> $quantity,
						'tax'				=> $line_total_tax,
						'ship'				=> 0,
						'handle'			=> 0,
						'currency'			=> $order_currency,
					);

					*/

					if (isset($lineItem['gross']) && !empty($lineItem['gross']) && $formattedForDAL2['gross'] == '') $formattedForDAL2 = $lineItem['total'];
					if (isset($lineItem['total']) && !empty($lineItem['total']) && $formattedForDAL2['total'] == '') $formattedForDAL2 = $lineItem['total'];					

                    // slight rejig of passed so works cleanly with data array style
                    $lineItemID = false; if (isset($lineItem['ID'])) $lineItemID = $lineItem['ID'];
                    $tempLineItemsClass->addUpdateLineitem(array(
                        'id'=>$lineItemID,
                        'linkedObjType' => ZBS_TYPE_TRANSACTION,
                        'linkedObjID' => $insertedID,
                        'data'=>$formattedForDAL2
                        ));

                }

				// passed, return
				return $insertedID;

			} else {

				// field uniqueness errors?
				// ...if so, '-n' the field and log fact

                // fails
                $failedOnErrors = $zbs->DAL->getErrors(ZBS_TYPE_TRANSACTION);

                // debug
                if ($debug) echo 'Failed, errors:<pre>'.print_r($failedOnErrors,1).'</pre>';

                // cycle through & find 301 (failed uniqueness)
                $otherErrors = false;
                if (is_array($failedOnErrors)) foreach ($failedOnErrors as $error){

                	// any errors logged?
                	if (is_array($error) && isset($error['code'])){

                		// 301?
                		if ($error['code'] == 301){

                			// there is a field uniqueness fail:

                			if ($debug) echo 'Field uniqueness collision:<pre>'.print_r($error,1).'</pre>';

                			// add to error stack
                			$errMsg = '['.__('Transaction','zero-bs-crm').'] '.__('There was an error migrating an object (non-unique field found)','zero-bs-crm');
                			$errMsg .= '<br />'.__('Transaction','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');              			

                		} // / if 301
                		else {

                			//... could have failed on insert/update?
                			$otherErrors = true;

                			// add to error stack
                			$errMsg = '['.__('Transaction','zero-bs-crm').'] '.__('There was an error migrating an object (could not insert)','zero-bs-crm');
                			$errMsg .= '<br />'.__('Transaction','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');

                		}

                	}

                } // / foreach error

			} // / didn't insert successfully


   		} // is valid obj

   		// return fail, so ball keeps rolling - should be logged in err stack if errs
   		return false;

   }

   // Migrate zerobs_event - 6 
   // take a cpt object from < 3.0 (DAL2), and migrate it into DAL3 obj
   function zeroBSCRM_migration_migrate_zerobs_event($cptObject=false){

   		$debug = false;

   		if (is_object($cptObject)){

   			global $zbs;

   			// retreive
   			$fullObj = zeroBS_getEvent($cptObject->ID,true);

          

            // translate dates
            $start = -1; $end = -1; $dateFormatStr = 'm/d/Y H:i:s';
            if (isset($fullObj['from']) && !empty($fullObj['from'])) $start = (int)zeroBSCRM_locale_dateToUTS($fullObj['from'],false,$dateFormatStr);
            if (isset($fullObj['to']) && !empty($fullObj['to'])) $end = (int)zeroBSCRM_locale_dateToUTS($fullObj['to'],false,$dateFormatStr);
            
            // completed (I think that's all that's stored in here...)
            $complete = -1; $zbsEventActions = get_post_meta($cptObject->ID, 'zbs_event_actions', true);
			if (isset($zbsEventActions['complete']) && $zbsEventActions['complete'] == 1) $complete = 1;

			// show on calendar showoncal - showonportal
			$show_on_portal = -1; $show_on_cal = -1;
            if (isset($fullObj['meta']['showonportal']) && !empty($fullObj['meta']['showonportal'])) $show_on_portal = 1;
            if (isset($fullObj['meta']['showoncal']) && !empty($fullObj['meta']['showoncal'])) $show_on_cal = 1;

            // reminder
            $reminder = false; if (isset($fullObj['meta']['notify_crm']) && !empty($fullObj['meta']['notify_crm'])){

            	// notified already?
            	$notified = get_post_meta($cptObject->ID,'24hnotify', true);
            	$sent = -1; if (!empty($notified)) $sent = 1;

            	$reminder = array(
	                'event' => -1, // this gets filled out automatically with the new event id by addUpdateEvent
	                'remind_at' => -86400, // only one in sys for now
	                'sent' => $sent,
	                //'lastupdated' => '', // leave to be set

	                'created' => strtotime($cptObject->post_date_gmt), // mark as created when event was.
            	);
            }

            // contact / co - customer  company
			$contactArr = array(); $companyArr = array();
            if (isset($fullObj['meta']['customer'])){
            	$contactID = (int)$fullObj['meta']['customer'];
            	if ($contactID > 0) $contactArr = array($contactID);
            }
            if (isset($fullObj['meta']['company'])){

            	// this is the cpt company id
            	// ... but that'll have been migrated to dal2 now...
            	//$companyID = (int)$fullObj['meta']['company'];

	        	// however... V2->V3 (this) migration routine moves companies into DAL2 objs, so the 'new' correct ID should be in 'zbs_parent_co_dal2'
	        	// ... so disregard this old setting, which would now house the old CPT ID of the Co
	        	$companyID = (int)get_post_meta($cptObject->ID, 'zbs_parent_co_dal2', true);  

	        	if ($debug) echo 'company is '.$fullObj['meta']['company'].'(cpt) and '.$companyID.'(zbsid)';

            	if ($companyID > 0) $companyArr = array($companyID);
            }

			if ($debug) echo 'Event CPT:<pre>'.print_r($fullObj,1).'</pre>';


			// convert obj to correct obj
			$id = -1; $owner = -2; // hack here to make owner NOT auto-set to current user (if -1 passed) because we're using owner as assignment for tasks
			$dataArr = array(
                
                'title' => ((isset($fullObj['meta']['title'])) ? $fullObj['meta']['title'] : ''),
                'desc' => ((isset($fullObj['meta']['notes'])) ? $fullObj['meta']['notes'] : ''),
                'start' => $start,
                'end' => $end,
                'complete' => $complete,
                'show_on_portal' => $show_on_portal,
                'show_on_cal' => $show_on_cal,
                // leave unset 'lastupdated' => '',

                // obj links:
                'contacts' => $contactArr,
                'companies' => $companyArr,

                // reminders:
                'reminders'     => false, // array($reminder), - added AFTER insert to get around temporary DAL2 state

                'tags' => -1, // No event tags pre v3.0

                'externalSources' => -1, // No externalsource logging for events pre v3.0

                'created' => strtotime($cptObject->post_date_gmt)


            );

			if ($debug) echo '<hr />Event Arr Stage 1:<pre>'.print_r($dataArr,1).'</pre>';

			// custom fields - Events v2 doesn't have

			// owner - user assignment
			$userIDofOwner = (int)get_post_meta($cptObject->ID, 'zbs_owner', true);
			if ($userIDofOwner > 0) $owner = $userIDofOwner;

			if ($debug) echo '<hr />Event Arr Stage 2:<pre>'.print_r($dataArr,1).'</pre>';

			// TAGS - Events v2 doesn't have

			// EXTERNAL SOURCES - Events v2 doesn't have

			if ($debug) echo '<hr />Event Arr Stage 3:<pre>'.print_r($dataArr,1).'</pre>';


		    // we need to load the classes temporarily so we can get the objmodels so we don't allow
		    // collisions between cf keys + new fields				    
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.ObjectLayer.php');
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.Events.php');
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.EventReminders.php');
    		global $tempEventsClass; // global because addUpdate below uses this global to check any unique fields/model
            $tempEventsClass = new zbsDAL_events;
            $tempEventRemindersClass = new zbsDAL_eventreminders;

			// insert via temp dal3 class
			$insertedID = $tempEventsClass->addUpdateEvent(array(

	            'id'            => $id,
	            'owner'         => $owner,

	            // fields (directly)
	            'data'          => $dataArr,

	            'limitedFields' => -1,
	            'silentInsert' => true,

	            // retain the old 'Quote ID'
	            'extraMeta' => array('prev3id' => $fullObj['id'])

			));

			// worked?
			if ($insertedID > 0){

				if ($debug) echo 'Successful insert:'.$insertedID.'!<br />';

				// Reminders (have to be added after as need $tempEventRemindersClass)
				if (is_array($reminder)){

                    // addupdate 

						// set eventid
                        $reminder['event'] = $insertedID;

                        // insert
                        $tempEventRemindersClass->addUpdateEventreminder(array('id'=>false,'data'=>$reminder));                  
					
				}

				// passed, return
				return $insertedID;

			} else {

				// field uniqueness errors?
				// ...if so, '-n' the field and log fact

                // fails
                $failedOnErrors = $zbs->DAL->getErrors(ZBS_TYPE_EVENT);

                // debug
                if ($debug) echo 'Failed, errors:<pre>'.print_r($failedOnErrors,1).'</pre>';

                // cycle through & find 301 (failed uniqueness)
                $otherErrors = false;
                if (is_array($failedOnErrors)) foreach ($failedOnErrors as $error){

                	// any errors logged?
                	if (is_array($error) && isset($error['code'])){

                		// 301? - note there wasn't any force_uniques on quote fields as at v3.0
                		if ($error['code'] == 301){

                			// there is a field uniqueness fail:

                			if ($debug) echo 'Field uniqueness collision:<pre>'.print_r($error,1).'</pre>';

                			// add to error stack
                			$errMsg = '['.__('Event','zero-bs-crm').'] '.__('There was an error migrating an object (non-unique field found)','zero-bs-crm');
                			$errMsg .= '<br />'.__('Event','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');            			

                		} // / if 301
                		else {

                			//... could have failed on insert/update?
                			$otherErrors = true;

                			// add to error stack
                			$errMsg = '['.__('Event','zero-bs-crm').'] '.__('There was an error migrating an object (could not insert)','zero-bs-crm');
                			$errMsg .= '<br />'.__('Event','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');

                		}

                	}

                } // / foreach error

			} // / didn't insert successfully


   		} // is valid obj

   		// return fail, so ball keeps rolling - should be logged in err stack if errs
   		//exit(__('Error Migrating object'));
   		return false;


   }

   // Migrate zerobs_form - 7
   // take a cpt object from < 3.0 (DAL2), and migrate it into DAL3 obj
   function zeroBSCRM_migration_migrate_zerobs_form($cptObject=false){

   		$debug = false;

   		if (is_object($cptObject)){

   			global $zbs;

   			// retreive
   			$fullObj = zeroBS_getForm($cptObject->ID);    

   			// title
   			$title = get_the_title($cptObject->ID);     

			if ($debug) echo 'Form CPT:<pre>'.print_r($fullObj,1).'</pre>';

			// convert obj to correct obj
			$id = -1; $owner = -1;
			$dataArr = array(


                'title' => $title,
                'style' => ((isset($fullObj['style'])) ? $fullObj['style'] : 'cgrab'),
                'views' => ((isset($fullObj['views'])) ? $fullObj['views'] : 0),
                'conversions' => ((isset($fullObj['conversions'])) ? $fullObj['conversions'] : 0),
                'label_header' => ((isset($fullObj['meta']['header'])) ? $fullObj['meta']['header'] : ''),
                'label_subheader' => ((isset($fullObj['meta']['subheader'])) ? $fullObj['meta']['subheader'] : ''),
                'label_firstname' => ((isset($fullObj['meta']['fname'])) ? $fullObj['meta']['fname'] : ''),
                'label_lastname' => ((isset($fullObj['meta']['lname'])) ? $fullObj['meta']['lname'] : ''),
                'label_email' => ((isset($fullObj['meta']['email'])) ? $fullObj['meta']['email'] : ''),
                'label_message' => ((isset($fullObj['meta']['notes'])) ? $fullObj['meta']['notes'] : ''),
                'label_button' => ((isset($fullObj['meta']['submit'])) ? $fullObj['meta']['submit'] : ''),
                'label_successmsg' => ((isset($fullObj['meta']['success'])) ? $fullObj['meta']['success'] : ''),
                'label_spammsg' => ((isset($fullObj['meta']['spam'])) ? $fullObj['meta']['spam'] : ''),
                'include_terms_check' => '', // precursor for v3.0+ stuff
                'terms_url' => '', // precursor for v3.0+ stuff
                'redir_url' => '', // precursor for v3.0+ stuff
                'font' => '', // precursor for v3.0+ stuff
                'colour_bg' => '', // precursor for v3.0+ stuff
                'colour_font' => '', // precursor for v3.0+ stuff
                'colour_emphasis' => '', // precursor for v3.0+ stuff

                'tags' => -1, // precursor for v3.0+ stuff
                
                'created' => strtotime($cptObject->post_date_gmt),
                'lastupdated' => strtotime($cptObject->post_date_gmt)


            );

			if ($debug) echo '<hr />Form Arr Stage 1:<pre>'.print_r($dataArr,1).'</pre>';

			// custom fields - Forms v2 doesn't have

			// TAGS - Forms v2 doesn't have

		    // we need to load the classes temporarily so we can get the objmodels so we don't allow
		    // collisions between cf keys + new fields				    
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.ObjectLayer.php');
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.Forms.php');
    		global $tempFormsClass; // global because addUpdate below uses this global to check any unique fields/model
            $tempFormsClass = new zbsDAL_forms;

			// insert via temp dal3 class
			$insertedID = $tempFormsClass->addUpdateForm(array(

	            'id'            => $id,
	            'owner'         => $owner,

	            // fields (directly)
	            'data'          => $dataArr,

	            'limitedFields' => -1,
	            'silentInsert' => true,

	            // retain the old 'Quote ID'
	            'extraMeta' => array('prev3id' => $fullObj['id'])

			));

			// worked?
			if ($insertedID > 0){

				if ($debug) echo 'Successful insert:'.$insertedID.'!<br />';

				// passed, return
				return $insertedID;

			} else {

				// field uniqueness errors?
				// ...if so, '-n' the field and log fact

                // fails
                $failedOnErrors = $zbs->DAL->getErrors(ZBS_TYPE_FORM);

                // debug
                if ($debug) echo 'Failed, errors:<pre>'.print_r($failedOnErrors,1).'</pre>';

                // cycle through & find 301 (failed uniqueness)
                $otherErrors = false;
                if (is_array($failedOnErrors)) foreach ($failedOnErrors as $error){

                	// any errors logged?
                	if (is_array($error) && isset($error['code'])){

                		// 301? - note there wasn't any force_uniques on quote fields as at v3.0
                		if ($error['code'] == 301){

                			// there is a field uniqueness fail:

                			if ($debug) echo 'Field uniqueness collision:<pre>'.print_r($error,1).'</pre>';

                			// add to error stack
                			$errMsg = '['.__('Form','zero-bs-crm').'] '.__('There was an error migrating an object (non-unique field found)','zero-bs-crm');
                			$errMsg .= '<br />'.__('Form','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');               			

                		} // / if 301
                		else {

                			//... could have failed on insert/update?
                			$otherErrors = true;

                			// add to error stack
                			$errMsg = '['.__('Form','zero-bs-crm').'] '.__('There was an error migrating an object (could not insert)','zero-bs-crm');
                			$errMsg .= '<br />'.__('Form','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');

                		}

                	}

                } // / foreach error

			} // / didn't insert successfully


   		} // is valid obj

   		// return fail, so ball keeps rolling - should be logged in err stack if errs
   		//exit(__('Error Migrating object'));
   		return false;


   }

   // Migrate zerobs_quo_template - 12
   // take a cpt object from < 3.0 (DAL2), and migrate it into DAL3 obj
   function zeroBSCRM_migration_migrate_zerobs_quo_template($cptObject=false){

   		$debug = false;

   		if (is_object($cptObject)){

   			global $zbs;

   			// retreive
   			$fullObj = zeroBS_getQuoteTemplate($cptObject->ID);    

   			// title
   			$title = get_the_title($cptObject->ID); 

   			// $zbsdefault (is one of ours?) if so add this via meta for v3
   			$zbsDefault = false; if (isset($fullObj['zbsdefault']) && !empty($fullObj['zbsdefault'])) $zbsDefault = true;

			if ($debug) echo 'Quote Template CPT:<pre>'.print_r($fullObj,1).'</pre>';

			// convert obj to correct obj
			$id = -1; $owner = -1;
			$dataArr = array(

                'title' => $title,
                'value' => '', // precursor for v3.0+ stuff
                'date_str' => '', // precursor for v3.0+ stuff
                'date' => '', // precursor for v3.0+ stuff
                'content' => ((isset($fullObj['content'])) ? $fullObj['content'] : ''),
                'notes' => '', // precursor for v3.0+ stuff
                'currency' => '', // precursor for v3.0+ stuff
                
                'created' => strtotime($cptObject->post_date_gmt),
                'lastupdated' => strtotime($cptObject->post_date_gmt)

            );

			if ($debug) echo '<hr />Quote Template Arr Stage 1:<pre>'.print_r($dataArr,1).'</pre>';

		    // we need to load the classes temporarily so we can get the objmodels so we don't allow
		    // collisions between cf keys + new fields				    
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.ObjectLayer.php');
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.QuoteTemplates.php');
			require_once(ZEROBSCRM_PATH . 'includes/ZeroBSCRM.DAL3.Obj.Quotes.php');    		
    		global $tempQuoteTemplatesClass,$tempQuotesClass;
            $tempQuoteTemplatesClass = new zbsDAL_quotetemplates;
            $tempQuotesClass = new zbsDAL_quotes;

			// insert via temp dal3 class
			$insertedID = $tempQuoteTemplatesClass->addUpdateQuotetemplate(array(

	            'id'            => $id,
	            'owner'         => $owner,

	            // fields (directly)
	            'data'          => $dataArr,

	            'limitedFields' => -1,
	            'silentInsert' => true,

	            // retain the old 'Quote template ID'
	            'extraMeta' => array('prev3id' => $fullObj['id'])

			));

			// worked?
			if ($insertedID > 0){

				if ($debug) echo 'Successful insert:'.$insertedID.'!<br />';

				// log $zbsDefault in meta
				if ($zbsDefault) {

					$zbs->DAL->addUpdateMeta(array(
						            'data'          => array(

						                'objid'         => $insertedID,
						                'objtype'       => ZBS_TYPE_QUOTETEMPLATE,
						                'key'       => 'zbsdefault',
						                'val'       => 1,
						                
						            ))
								);

				}


				/* This would ultimately have worked, but turns out <v3.0 we were not properly saving - zbs_quote_template_id 
				... so ignoring the translation issues here, for now, redeemable later using prev3id of this and extra_prev3template of quote, if needed

				// any quotes migrated will have old CPT template id's against them, so translate those for this, if not done:

					// find the new zbsid and set
					global $ZBSCRM_t;

		            #} Build query
		            $query = "SELECT ID,zbsm_objid FROM ".$ZBSCRM_t['meta']." WHERE zbsm_objtype = %d AND zbsm_key = %s AND zbsm_val = %d";

            		#} Prep & run query
		            global $wpdb;
	                $queryObj = $wpdb->prepare($query,array(ZBS_TYPE_QUOTE,'extra_prev3template',$cptObject->ID));
	                $quotesWithTemplate = $wpdb->get_results($queryObj, OBJECT);
	                if (is_array($quotesWithTemplate)){

	                	foreach ($quotesWithTemplate as $qwt){

	                		$objID = $qwt->zbsm_objid;
	                		$metaID = $qwt->ID;

	                		if ($metaID > 0){

		                		// update it
		                		$tempQuotesClass->setQuoteTemplate($objID,$insertedID);

		                		// cull the meta 
		                		$zbs->DAL->deleteMetaByMetaID(array('id'=>$metaID));

		                	}

	                	}

	                }

	               */



				// passed, return
				return $insertedID;

			} else {

				// field uniqueness errors?
				// ...if so, '-n' the field and log fact

                // fails
                $failedOnErrors = $zbs->DAL->getErrors(ZBS_TYPE_QUOTETEMPLATE);

                // debug
                if ($debug) echo 'Failed, errors:<pre>'.print_r($failedOnErrors,1).'</pre>';

                // cycle through & find 301 (failed uniqueness)
                $otherErrors = false;
                if (is_array($failedOnErrors)) foreach ($failedOnErrors as $error){

                	// any errors logged?
                	if (is_array($error) && isset($error['code'])){

                		// 301? - note there wasn't any force_uniques on quote fields as at v3.0
                		if ($error['code'] == 301){

                			// there is a field uniqueness fail:

                			if ($debug) echo 'Field uniqueness collision:<pre>'.print_r($error,1).'</pre>';

                			// add to error stack
                			$errMsg = '['.__('Quote template','zero-bs-crm').'] '.__('There was an error migrating an object (non-unique field found)','zero-bs-crm');
                			$errMsg .= '<br />'.__('Quote template','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');               			

                		} // / if 301
                		else {

                			//... could have failed on insert/update?
                			$otherErrors = true;

                			// add to error stack
                			$errMsg = '['.__('Quote template','zero-bs-crm').'] '.__('There was an error migrating an object (could not insert)','zero-bs-crm');
                			$errMsg .= '<br />'.__('Quote template`','zero-bs-crm').':<pre>'.json_encode($dataArr).'</pre>';
                			zeroBSCRM_migration_addErrToStack(array($error['code'],$errMsg),'zbs_db_migration_300_errstack');

                		}

                	}

                } // / foreach error

			} // / didn't insert successfully


   		} // is valid obj

   		// return fail, so ball keeps rolling - should be logged in err stack if errs
   		//exit(__('Error Migrating object'));
   		return false;


   }

	/* ======================================================
		/ Admin AJAX DAL3
	====================================================== */



/* ======================================================
	/ Admin AJAX: DB Migration assistant
====================================================== */


/* ======================================================
   Global "are you ready?" message (v2.99.7+)
   ====================================================== */


   /* pre v3.0


	function zeroBSCRM_v3Prep_globalAreYouReadyMessage(){

		global $zbs;

        ##WLREMOVE 

		// not for wl
		// not for non-admins
		// repeat weekly for now.

		// is admin
		if (current_user_can( 'manage_options' )){

			//need to check whether is our admin page (as is showing everywhere)
			if(zeroBSCRM_isAdminPage()){
				
				// hasn't dismissed in past week
				#to clear: zeroBSCRM_clearCloseState('v3prep2997');
				// should just use a wp_transient here.
				$hasXd = zeroBSCRM_getCloseState('v3prep2997');
				if (!$hasXd || $hasXd < time()-604800){

					$bundle = false; if ($zbs->hasEntrepreneurBundleMin()) $bundle = true;

					if (!$bundle){

						// Migration / IMPERITIVE notifications
						?><div id="zbs-v3prep-top-menu-notification" style="margin: 1em;margin-right:2em;margin-top: 3em;"><?php

								$msgHeader = __('v3.0 is Coming Soon!',"zero-bs-crm");
								$msgHeader .= '<i class="zbs-dismiss close icon" data-dismiss-element-id="zbs-v3prep-top-menu-notification" data-dismiss-key="v3prep2997" title="'.__('Dismiss','zero-bs-crm').'"></i>';

								$msgHTML = __('In the near future we\'ll be releasing v3.0 of Zero BS CRM. This will make the CRM up to 60x faster throughout, more developer-friendly, and come with bucket-loads of other benefits.',"zero-bs-crm");
								$msgHTML .= '<br /><br />';
								$msgHTML .= __('There will be an important data-migration when v3.0 is released, and we will also be introducing our Elite Bundle.','zero-bs-crm');
								$msgHTML .= '<br />';
								$msgHTML .= __('Read more about this incoming update below, or join our growing army of happy customers by purchasing today',"zero-bs-crm");
								$msgHTML .= '<br /><br />';
								$msgHTML .= '<a href="'.$zbs->urls['roadtov3'].'" class="ui button small blue">'.__('Read about v3.0','zero-bs-crm').'</a>';
								$msgHTML .= '<a href="'.$zbs->urls['upgrade'].'" class="ui button small green">'.__('Buy Now','zero-bs-crm').'</a>';


								echo zeroBSCRM_UI2_messageHTML('small info',$msgHeader,$msgHTML,'flag','zbsV3Incoming'); 
								

						?><hr />
						</div><?php

					} // / has bundle

				} // / hasn't dismissed in past week

			} // / is ZBS admin page
        
		} // / is admin

        ##/WLREMOVE
        return false; 

	}
	add_action( 'zerobscrm-subtop-menu', 'zeroBSCRM_v3Prep_globalAreYouReadyMessage' );

	*/


/* ======================================================
   / Global "are you ready?" message (v2.99.7+)
   ====================================================== */