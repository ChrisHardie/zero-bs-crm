<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.52+
 *
 * Copyright 2018, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 27/02/18
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */


/* ======================================================
  Create Tags Box
   ====================================================== */
class zeroBS__Metabox_Tags extends zeroBS__Metabox {

    public $typeInt = false; // child fills out e.g. ZBS_TYPE_CONTACT
    public $showSuggestions = false; // show/hide tag suggestions
    public $saveAutomatically = false; // if this is true, this'll automatically update the object via addUpdateTags - this is off by default as most objects save tags themselves as part of addUpdateWhatever so that IA hooks fire correctly

    public function __construct( $plugin_file ) {

        // call this 
        $this->initMetabox();

    }

    public function html( $obj, $metabox ) {

            global $zbs; 

            $objid = -1; if (is_array($obj) && isset($obj['id'])) $objid = $obj['id'];

            // get tags ahead of time + only show if not empty :)
            $tags = array();
            // TODO DB2: $terms = $zbs->DAL->getTagsForObjID(array('objtype'=>$this->typeInt,'objid'=>$objid));
            $tags = zeroBSCRM_getCustomerTagsByID($objid); 
            // Debug echo 'Tags<pre>'; print_r($tags); echo '</pre>';
            // simplify :)
            $tagsArr = array(); if (is_array($tags) && count($tags) > 0) foreach ($tags as $t){
                /* even simpler...
                $tag = array(
                    'id' => $t->term_id,
                    'name' => $t->name
                );
                $tagsArr[] = $tag;*/
                
                //$tagsArr[] = $t->name;
                $tagsArr[] = $t['name'];
            }
            $tags = $tagsArr;

            ?><div id="zbs-edit-tags"><?php 

                if (zeroBSCRM_permsCustomers()){

                    // edit
                    ?><div id="zbs-add-tags">
                        <div class="ui action left icon fluid input">
                          <i class="tags icon"></i>
                          <input id="zbs-add-tag-value" type="text" placeholder="Enter tags">
                          <button id="zbs-add-tag-action" type="button" class="ui mini blue button">
                            <?php _e('Add',"zero-bs-crm"); ?>
                          </button>
                        </div>
                    </div>
                    <input name="zbs-tag-list" id="zbs-tag-list" type="hidden" /><?php 
                    // final tags are passed via hidden inpt zbs-tag-list
                    // look for zeroBSCRMJS_buildTagsInput in js :) JSONs it
                }

                    echo '<div id="zbs-tags-wrap">';
                        
                        $tagIndex = array();
                        
                        /* OLD VIEW - new view all JS
                            // zbs_prettyprint($terms);
                            $i = 1;
                            foreach($tags as $term){
                                $zbsurl = get_admin_url('','edit.php?post_type='.$this->postType.'&page='.$this->postPage) ."&zbs_tag=".$term->term_id;
                                $zbstermc = zeroBSCRM_prettifyLongInts($term->count);
                                if($i==1){
                                    echo "<div class='first-ten-tags'>";
                                }
                                // check
                                if (in_array($term->term_id, $tagIndex))
                                    $tagColor = 'blue';
                                else
                                    $tagColor = 'teal';

                                // handle super long tag names

                                echo '<a href="'.$zbsurl.'" class="ui button tiny '.$tagColor.'">'. $term->name . " (<span class='sub-count'>" .$zbstermc. "</span>)</a>";
                            

                                if($i == 6){
                                    echo "</div>"; //end first 10 tags
                                        #} tags UI for showing all
                                        echo "<div class='show-more-tags ui button olive tiny'>";
                                            _e("Show all tags","zero-bs-crm");
                                        echo "</div>";
                                    echo "<div class='more-tags'>";
                                }

                                $i++;

                            }
                            if($i >= 6){
                                echo "</div>"; //close the more tags
                            }

                        */

                        ?>
                        </div><!-- /.zbs-tags-wrap -->
                        <?php
                        if ($this->showSuggestions){ 

                            // Get top 20 tags, show top 5 + expand
                            $tagSuggestions = $zbs->DAL->getTagsForObjType(array(

                                'objtypeid'=>ZBS_TYPE_CONTACT,
                                'excludeEmpty'=>false,
                                'withCount'=>true,
                                'ignoreowner' => true,
                                // sort
                                'sortByField'   => 'tagcount',
                                'sortOrder'   => 'DESC',
                                // amount
                                'page' => 0,
                                'perPage' => 25

                                ));

                            /*  (
                                    [id] =&gt; 4
                                    [objtype] =&gt; 1
                                    [name] =&gt; John
                                    [slug] =&gt; john
                                    [created] =&gt; 1527771665
                                    [lastupdated] =&gt; 1527771999
                                    [count] =&gt; 3
                                )
                                */
                            if (is_array($tagSuggestions) && count($tagSuggestions) > 0){ ?>
                        <div id="zbs-tags-suggestions-wrap">
                            <div class="ui horizontal divider zbs-tags-suggestions-title"><?php _e('Suggested Tags','zero-bs-crm').':'; ?></div>
                            <div id="zbs-tags-suggestions">
                                <?php 

                                    $suggestionIndx = 0;
                                    foreach ($tagSuggestions as $tagSuggest){

                                        if ($suggestionIndx == 5 && count($tagSuggestions) > 5){

                                            ?><div class="ui horizontal divider" id="zbs-tag-suggestions-show-more"><i class="search plus icon"></i> <?php _e('Show More','zero-bs-crm'); ?></div>
                                            <div id="zbs-tag-suggestions-more-wrap"><?php
                                        }

                                        // brutal out
                                        ?><div class="ui small basic blue teal label zbsTagSuggestion" title="<?php _e('Add Tag','zero-bs-crm'); ?>"><?php echo $tagSuggest['name']; ?></div><?php


                                        $suggestionIndx++;
                                    }

                                    if (count($tagSuggestions) > 5){

                                        ?><div class="ui horizontal divider" id="zbs-tag-suggestions-show-less"><i class="search minus icon"></i> <?php _e('Show Less','zero-bs-crm'); ?></div></div><?php // close 'more';
                                    }

                                ?>
                            </div>
                        </div>
                        <?php }} ?>
                    </div>
                    <script type="text/javascript">

                        var zbsCRMJS_currentTags = <?php echo json_encode($tags); ?>;

                    </script>
            <?php 

    }

    public function save_data( $objID, $obj ) {

        // Note: Most objects save tags as part of their own addUpdate routines.
        // so this now only fires where saveAutomatically = true
        if ($this->saveAutomatically){

            // Save tags against objid
            // NEEDS AN OBJTYPE SWITCH HERE :)
            $potentialTags = zeroBSCRM_tags_retrieveFromPostBag(true,ZBS_TYPE_CONTACT);
            
            if (is_array($potentialTags)){

                global $zbs;

                // new db :)
                /* Nope. this isn't objtype generic, so re-add later if used
                $zbs->DAL->addUpdateContactTags(array(
                        'id' => $objID,
                        'tags' => $tags,
                        'mode' => 'replace'
                ));   
                */                         

            } // / if has tags arr

        } // / if saveAutomatically

        return $obj;
    }
}

/* ======================================================
  / Create Tags Box
   ====================================================== */


// tag related function - retrieve tags from post
function zeroBSCRM_tags_retrieveFromPostBag($returnAsIDs = true,$objectTypeID=-1){

    // - NOTE THIS REQ: 
                // final tags are passed via hidden inpt zbs-tag-list
                // look for zeroBSCRMJS_buildTagsInput in js :) JSONs it
    
    if (isset($_POST['zbs-tag-list']) && !empty($_POST['zbs-tag-list']) && $objectTypeID > 0){

        global $zbs;

        // should be json 
        // doesn't need decoding (wp done?) 
        // all are sanitized below
        $potentialTags = json_decode(stripslashes($_POST['zbs-tag-list']));

        // not sanitized at this point..
        if (is_array($potentialTags)){

            $tags = array();

            foreach ($potentialTags as $tag){

                $cleanTag = trim(sanitize_text_field( $tag ));
                if (!empty($cleanTag) && !in_array($cleanTag, $tags)) $tags[] = $cleanTag;

            } // / foreach tag  

            if (!$returnAsIDs)  
                return $tags;   
                // returns as array(0=>'tag1') etc.
            else {

                $tagIDs = array();

                // cycle through + find
                foreach ($tags as $tag){

                    $tagID = $zbs->DAL->getTag(-1,array(
                        'objtype'       => $objectTypeID,
                        'name'          => $tag,
                        'onlyID' => true
                        ));

                    //echo 'looking for tag "'.$tag.'" got id '.$tagID.'!<br >';

                    if (!empty($tagID)) 
                        $tagIDs[] = $tagID;
                    else {
                        
                        //create
                        $tagID = $zbs->DAL->addUpdateTag(array(
                                                            'data'=>array(
                                                            'objtype'       => $objectTypeID,
                                                            'name'          => $tag))); 
                        //add
                        if (!empty($tagID)) $tagIDs[] = $tagID;

                    }
                }

                return $tagIDs;

            }         

        } // / if is array

    } // / post

    return array();

}