<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.0
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 26/05/16
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */





if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class zeroBSCRM_Customer_ListNoQJ extends WP_List_Table {

    #} WH v1.1
    private $customViewArr = false;

    /** Class constructor */
    public function __construct() {

        parent::__construct( array(
            'singular' => __( 'Customer', 'zero-bs-crm' ), //singular name of the listed records
            'plural'   => __( 'Customers', 'zero-bs-crm' ), //plural name of the listed records
            'ajax'     => false //does this table support ajax?
        ) );

    }


    /**
     * Retrieve customer data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_customers( $per_page = 10, $page_number = 1, $possibleSearchTerm = '' ) {

        #} ;} - this wires up to the retrieve func
        #} 26/05/16 added "include transactions"
        return zeroBS_getCustomersNoQJ(true,$per_page,$page_number,true,true,$possibleSearchTerm,true);

    }


    /**
     * Retrieve customer data from the database
     * WH Modified ver to retrieve FILTERED customers
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_customers_filtered( $per_page = 10, $page_number = 1 ) {

        #} lol brutal
        return zbs_customerFiltersRetrieveCustomers($per_page,$page_number,false);

    }


    /**
     * Delete a booking
     *
     * @param int $id booking ID
     */
    public static function delete_customer( $id ) {

        #} Brutal!
        #wp_delete_post($id);

    }


    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count() {
      
        #} NOTE: TODO: this doesn't work in regard to SEARCHES <-- 
        #} Just uses wp_count_posts
        return zeroBS_getCustomerCount();

    }

    /**
     * Returns the count of records in the database.
     * FILTERED
     *
     * @return null|string
     */
    public static function record_count_filtered() {
      
        #} :*()
        return zeroBS__customerFiltersRetrieveCustomerCount();

    }


    /** Text displayed when no booking data is available */
    public function no_items() {
        _e( 'No Customers avaliable.', 'zero-bs-crm' );
    }


    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default( $item, $column_name ) {

        /* default bits
        switch ( $column_name ) {
            #case 'address':
            #case 'city':
            #    return $item[ $column_name ];
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
        */

       

        #} WH 1.1 custom catcher
        if (is_array($this->customViewArr)){ 

            #} This is all good - is a custom passed func :)
            if (isset($this->customViewArr[$column_name])){

                #} find func for it
                $colFuncName = ''; 
                if (isset($this->customViewArr[$column_name][1])) $colFuncName = $this->customViewArr[$column_name][1];

                #} Here logic splits... if calling zbsDefault's then they're in this class, so they get $this-> if calling custom, they're not.. brutally discerned... lol
                if (substr($colFuncName,0,18) == 'zbsDefault_column_'){

                    #} Call it
                    return $this->$colFuncName($item);




                } else {

                    #} Totally custom func

                    #} Following will catch custom funcs and call them...
                    if (!empty($colFuncName) && function_exists($colFuncName))
                        return $colFuncName($item); #} This custom func will need to RETURN a string
                    else
                        #} no available function passed for this column name?
                        return 'Custom Column Function Not Found!';

                }


            } else {

                #} A column name fired that's not in view?
                return '?';

            }


        } else {

            #} Otherwise it's passing a column seperate to custom view wtf? perhaps option wiped out or smt?

            #} Hmmmm not sure it should even run this ever?

            switch ($column_name){

                case 'customername':
                    return $this->zbsDefault_column_customername($item);
                    break;
                case 'customeremail':
                    return $this->zbsDefault_column_customeremail($item);
                    break;
                case 'status':
                    return $this->zbsDefault_column_status($item);
                    break;
                case 'quotecount':
                    return $this->zbsDefault_column_quotecount($item);
                    break;
                case 'invcount':
                    return $this->zbsDefault_column_invcount($item);
                    break;
                case 'transcount':
                    return $this->zbsDefault_column_transcount($item);
                    break;
                case 'totalval':
                    return $this->zbsDefault_column_totalval($item);
                    break;
                case 'added':
                    return $this->zbsDefault_column_added($item);
                    break;
                default:
                    return print_r($item,true); #} DEBUG?
                    break;


            }

            #} ?
            return '?';

        }

    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    function zbsDefault_column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
        );
    }

    /**
     * Render the booking name column
     *
     * @param array $item
     *
     * @return string
     */
    function zbsDefault_column_customername( $item ) {
        
        $colStr = '';
        if (isset($item)){
            $colStr = '<strong><a href="post.php?post='.$item['id'].'&action=edit">'.zeroBS_customerName($item['id'],$item,false,false).'</a></strong><br />';
            if (isset($item['addr1']) && isset($item['city']))
                #$colStr .= '<div>'.$item['addr1'].', '.$item['city'].'</div>';
                $colStr .= '<div>'.zeroBS_customerAddr($item['id'],$item,'short',', ').'</div>';
        }
        #$colStr .= '(<span>ID:'.$item['id'].'</span>)';

        return $colStr;

    }

    /**
     * Render the booking name column
     *
     * @param array $item
     *
     * @return string
     */
    function zbsDefault_column_customeremail( $item ) {

        #} email
        $email = ''; if (isset($item) && isset($item['email']) && !empty($item['email'])) $email = $item['email'];
        
        $colStr = '-';
        if (!empty($email)){
            $colStr = '<strong><a href="post.php?post='.$item['id'].'&action=edit">'.$email.'</a></strong><br />';
            $colStr .= '<a href="mailto:'.$email.'" target="_blank">Send Email</a>';
        }
        #$colStr .= '(<span>ID:'.$item['id'].'</span>)';

        return $colStr;

    }

    /**
     * Render the booking trip column
     *
     * @param array $item
     *
     * @return string
     */
    function zbsDefault_column_status( $item ) {
        
        $status = '?'; $styles = '';

        if (isset($item) && isset($item['status'])){

            switch($item['status']){

                case 'Lead':
                    $status = $item['status'];
                    $styles = 'color:green';

                    break;
                case 'Customer':
                    $status = $item['status'];
                    $styles = 'color:#7F7FE4';

                    break;
                default:
                    $status = $item['status'];
                    break;


            }

        }


        return '<strong style="'.$styles.'">'.$status.'</strong>';
    }

    /**
     * Render the pickuptime column
     *
     * @param array $item
     *
     * @return string
     */
    function zbsDefault_column_quotecount( $item ) {
        
        $qc = 0;

        if (isset($item['quotes'])) $qc = count($item['quotes']);

        return zeroBSCRM_prettifyLongInts($qc);

    }
    function zbsDefault_column_invoicecount( $item ) {
        
        $iC = 0;

        if (isset($item['invoices'])) $iC = count($item['invoices']);

        return zeroBSCRM_prettifyLongInts($iC);

    }
    function zbsDefault_column_transactioncount( $item ) {
        
        $tC = 0;

        if (isset($item['transactions'])) $tC = count($item['transactions']);

        return zeroBSCRM_prettifyLongInts($tC);

    }
    function zbsDefault_column_totalvalue( $item ) {
        
        $totalVal = 0;

        /*#} Brutal add up for now
        if (isset($item['invoices']) && is_array($item['invoices'])) foreach ($item['invoices'] as $inv){

            if (isset($inv['meta']) && isset($inv['meta']['val'])) $totalVal += floatval($inv['meta']['val']);

        }*/

        #} Moved to main php func so we can add in values from anywhere :)
        $totalVal = zeroBS_customerTotalValue($item['id'],$item['invoices'],$item['transactions']);

        return zeroBSCRM_getCurrencyChr().zeroBSCRM_prettifyLongInts($totalVal).'<!--CUSTDEET:'.json_encode($item).'-->';

    }
    function zbsDefault_column_added( $item ) {

        return date(zeroBSCRM_getDateFormat(),strtotime($item['created']));

    }



    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    function zbsDefault_column_name( $item ) {

        $delete_nonce = wp_create_nonce( 'tbp_delete_customer' );

        $title = '<strong>' . $item['name'] . '</strong>';

        $actions = array(
            'delete' => sprintf( '<a href="?page=%s&action=%s&booking=%s&_wpnonce=%s">Delete</a>', esc_attr( sanitize_text_field( $_REQUEST['page'] ) ), 'delete', absint( $item['id'] ), $delete_nonce )
        );

        return $title . $this->row_actions( $actions );
    }


    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns() {


        #} Defaults (never used post v1.1)

        $columns = array(
            #'cb'      => '<input type="checkbox" />',
            'customername'    => __( 'Name', 'zero-bs-crm' ),
            'status' => __( 'Status', 'zero-bs-crm' ),
            'quotecount'    => __( 'Quotes', 'zero-bs-crm' ),
            'invcount' => __( 'Invoices', 'zero-bs-crm' ),
            'transcount' => __( 'Transactions', 'zero-bs-crm' ),
            'totalval' => __( 'Total Value', 'zero-bs-crm' ),
            'added' => __( 'Added', 'zero-bs-crm' )
        );

        #} Use customs if avail

            #} Retrieve settings
            global $zbs;
            $settings = $zbs->settings->getAll();

            #} Check if custom view avail
            if (isset($settings['customviews']) && isset($settings['customviews']['customer'])){

                #} Store in this prop for later use in this class... 
                $this->customViewArr = $settings['customviews']['customer'];

                $columns = array();

                #} cycle through each and add here :)
                if (count($settings['customviews']['customer']) > 0) foreach ($settings['customviews']['customer'] as $colname => $coldeets){

                    #} Will look like 'name' => array('Name','zbsDefaultFieldOut_customername'),
                    $columns[$colname] = __($coldeets[0],"zero-bs-crm");

                }                                                



            }


        return $columns;
    }


    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'customername' => array( 'customername', true ),
            'totalval' => array( 'totalval', true ),
            'added' => array( 'added', false )
        );

        return $sortable_columns;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = array(
            #'bulk-delete' => 'Delete'
        );

        return $actions;
    }


    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {

        #} had to switch this:
        #$this->_column_headers = $this->get_column_info();

        #} For this... 
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        #} (Because get_column_info is doing something to do with 'screen' that I cba to explore)

        /** Process bulk action */
        $this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'customers_per_page', 10 );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args( array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ));

        #} WH v1.1 - catches search submit
        $possibleSearch = ''; if (isset($_POST['s'])) $possibleSearch = sanitize_text_field($_POST['s']);

        $this->items = self::get_customers( $per_page, $current_page, $possibleSearch);

    }

    /**
     * Handles data query and filter, sorting, and pagination.
     * WH Modified ver using zbs_customerFilters* funcs
     */
    public function prepare_items_filtered() {

        #} had to switch this:
        #$this->_column_headers = $this->get_column_info();

        #} For this... 
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        #} (Because get_column_info is doing something to do with 'screen' that I cba to explore)

        /** Process bulk action */
        $this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'customers_per_page', 10 );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count_filtered();

        $this->set_pagination_args( array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ));

        #} WH v1.1 - catches search submit
        # Not in filtered func: $possibleSearch = ''; if (isset($_POST['s'])) $possibleSearch = sanitize_text_field($_POST['s']);

        #} This'll grab from the cached array which would be fired by record_count_filtered above
        $this->items = self::get_customers_filtered( $per_page, $current_page);

    }

    public function process_bulk_action() {

        /*
        //Detect when a bulk action is being triggered...
        if ( 'delete' === $this->current_action() ) {

            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'tbp_delete_customer' ) ) {
                die( '!' ); #} Go get a life script kiddies
            }
            else {
                self::delete_customer( absint( $_GET['booking'] ) );

                wp_redirect( esc_url( add_query_arg() ) );
                exit;
            }

        }

        // If the delete bulk action is triggered
        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
             || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
        ) {

            $delete_ids = esc_sql( $_POST['bulk-delete'] );

            // loop over the array of record IDs and delete them
            foreach ( $delete_ids as $id ) {
                self::delete_customer( $id );

            }

            wp_redirect( esc_url( add_query_arg() ) );
            exit;
        }

        */
    }

}





function zeroBSCRM_render_customersNoQJlist_page(){

    $option = 'per_page';
    $args   = array(
        'label'   => 'Customers',
        'default' => 10,
        'option'  => 'customers_per_page'
    );

    add_screen_option( $option, $args );

    $customerListTable = new zeroBSCRM_Customer_ListNoQJ();

        #} Normal Header.
        #taxiBookerPRO_pages_header(__('Manage Bookings','taxibookerpro'));
        
        #} Load Library?
        $normalLoad = true;
        
        
        
        if ($normalLoad){

            #} Updated this to work with 4.5.2 wp list setup :) 
            #} https://core.trac.wordpress.org/browser/tags/4.5.2/src/wp-admin/edit.php
            #} https://core.trac.wordpress.org/browser/tags/4.5.2/src//wp-admin/includes/class-wp-list-table.php#L0


            /*
            get_current_screen()->set_screen_reader_content( array(
                     'heading_views'      => $post_type_object->labels->filter_items_list,
                     'heading_pagination' => $post_type_object->labels->items_list_navigation,
                     'heading_list'       => $post_type_object->labels->items_list,
             ) );
            add_screen_option( 'per_page', array( 'default' => 20, 'option' => 'edit_' . $post_type . '_per_page' ) );
            */

            #} Prep items
            $customerListTable->prepare_items();

            ?><div class="wrap">
                <h1>Customers (Without Quote)<?php 
                    #} Add new?
                    if ( zeroBSCRM_permsCustomers() ) {
                        echo ' <a href="' . zbsLink('create',-1,ZBS_TYPE_CONTACT) . '" class="page-title-action">' . esc_html( 'Add New' ) . '</a>';
                    }
                ?></h1>
                <?php 

                    #} If searching, show:
                    if (isset($_POST['s']) && !empty($_POST['s'])) {

                        $searchTerm = sanitize_text_field($_POST['s']);

                        echo '<div id="zbsSearchTerm">Searching: "'.$searchTerm.'" <button type="button" class="button" id="clearSearch">Cancel Search</button></div>';

                    }

                $customerListTable->views(); ?>
                <?php /*
                #} clash in code here, will be addressed when properly re-write these list tables
                <form id="posts-filter" method="get">*/ ?>
                <form method="post">
                    <?php $customerListTable->search_box('Search Customers','customersearch'); ?>
                    <?php $customerListTable->display(); ?>
                </form>
                <!--<br class="clear">-->
            </div>

                <script type="text/javascript">
                    jQuery(document).ready(function(){

                        jQuery('#clearSearch').click(function(){

                            jQuery('#customersearch-search-input').val('');
                            jQuery('#search-submit').click();

                        });

                    });
                </script>
                
            <?php

        }

}



#} Brutal quick fix
function zeroBSCRM_render_filtered_customersNoQJlist_page(){

    $option = 'per_page';
    $args   = array(
        'label'   => 'Customers',
        'default' => 10,
        'option'  => 'customers_per_page'
    );

    add_screen_option( $option, $args );
    
    $customerListTable = new zeroBSCRM_Customer_ListNoQJ();
   
        #} Normal Header.
        #taxiBookerPRO_pages_header(__('Manage Bookings','taxibookerpro'));
        
        #} Load Library?
        $normalLoad = true;
        
        
        
        if ($normalLoad){

            #} Updated this to work with 4.5.2 wp list setup :) 
            #} https://core.trac.wordpress.org/browser/tags/4.5.2/src/wp-admin/edit.php
            #} https://core.trac.wordpress.org/browser/tags/4.5.2/src//wp-admin/includes/class-wp-list-table.php#L0


            /*
            get_current_screen()->set_screen_reader_content( array(
                     'heading_views'      => $post_type_object->labels->filter_items_list,
                     'heading_pagination' => $post_type_object->labels->items_list_navigation,
                     'heading_list'       => $post_type_object->labels->items_list,
             ) );
            add_screen_option( 'per_page', array( 'default' => 20, 'option' => 'edit_' . $post_type . '_per_page' ) );
            */

            #} Prep items
                
                #} If no filters in place, just use normal func (much quicker)
                global $zbsCustomerFiltersPosted;
                if (!isset($zbsCustomerFiltersPosted)) {
                    #} Normal items retrieve
                    $customerListTable->prepare_items();
                    $recordCount = $customerListTable->record_count();
                } else {
                    #} Filtered items
                    $customerListTable->prepare_items_filtered();
                    $recordCount = $customerListTable->record_count_filtered();
                }

            ?><div class="wrap">
                <h1><?php  
                    
                    #} show count
                    echo zeroBSCRM_prettifyLongInts($recordCount);
                    

                ?> Customers (Without Quote)<?php 
                    #} Add new?
                    #if ( zeroBSCRM_permsCustomers() ) {
                    #    echo ' <a href="' . zbsLink('create',-1,ZBS_TYPE_CONTACT) . '" class="page-title-action">' . esc_html( 'Add New' ) . '</a>';
                    #}
                ?></h1>
                <br class="clear">
                <form method="post">
                    <?php $customerListTable->display(); ?>
                </form>
                <br class="clear">
            </div>

                <script type="text/javascript">
                    jQuery(document).ready(function(){

                        

                    });
                </script>
                
            <?php
    
        }


}