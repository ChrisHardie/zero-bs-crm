<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.14+
 *
 * Copyright 2017+ ZeroBSCRM.com
 *
 * Date: 26/09/2017
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */


/* LEGACY! WH removed from actual include 10/12/18 - if not used hereafter, remove */


/* ======================================================
	Admin Notices (Dismissible)
   ====================================================== */


// class found here: https://www.alexgeorgiou.gr/persistently-dismissible-notices-wordpress/ edited slightly due to issues
   // 1. The code was just doing zerobscrm_dismiss_1 (rather than zerobscrm_dismiss_name) which meant you could only fire it once
   // 2. Added transients for temporary dismissible notice (hard coded for just one variable (for now) can expand if WH agrees)

if ( ! class_exists( 'zeroBSCRM_Admin_Notices' ) ) {

    class zeroBSCRM_Admin_Notices {

        private static $_instance;
        private $admin_notices;
        const TYPES = 'error,warning,info,success';

        private function __construct() {
            $this->admin_notices = new stdClass();
            foreach ( explode( ',', self::TYPES ) as $type ) {
                $this->admin_notices->{$type} = array();
            }
            add_action( 'admin_init', array( &$this, 'action_admin_init' ) );
            add_action( 'admin_notices', array( &$this, 'action_admin_notices' ) );
            add_action( 'admin_enqueue_scripts', array( &$this, 'action_admin_enqueue_scripts' ) );
        }

        public static function get_instance() {
            if ( ! ( self::$_instance instanceof self ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public function action_admin_init() {
            $dismiss_name = filter_input( INPUT_GET, 'zerobscrm_dismiss', FILTER_SANITIZE_STRING );
            if ( is_string( $dismiss_name ) ) {
                if($dismiss_name == 'one-or-more'){
                    //then we are in our plugin update check. Let them dismiss it. But also want to return it every month
                    set_transient( "zerobscrm_dismissed_$dismiss_name", true, 30 * 24 * HOUR_IN_SECONDS);
                }else{
                    update_option( "zerobscrm_dismissed_$dismiss_name", true );
                }

                wp_die();
            }
        }

        public function action_admin_enqueue_scripts() {
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script(
                'zerobscrm-notify',
                //plugins_url( 'js/ZeroBSCRM.admin.notify.js', __FILE__ ),
                ZEROBSCRM_URL.'js/ZeroBSCRM.admin.notify.min.js',
                array( 'jquery' )
            );
        }

        public function action_admin_notices() {
            foreach ( explode( ',', self::TYPES ) as $type ) {
                foreach ( $this->admin_notices->{$type} as $admin_notice ) {

                	$zbs_option = sanitize_title($admin_notice->dismiss_name);   //using the name passed but turned from Sales Dashboard to sales-dashboard

                    $dismiss_url = add_query_arg( array(
                        'zerobscrm_dismiss' => sanitize_title($admin_notice->dismiss_name)
                    ), admin_url() );
                    if($zbs_option == 'one-or-more' && ! get_transient("zerobscrm_dismissed_{$zbs_option}") ) {
                        ?><div
                            class="ui message <?php echo $type; ?> notice zerobscrm-notice notice-<?php echo $type;

                            if ( $admin_notice->dismiss_option ) {
                                echo ' is-dismissible" data-dismiss-url="' . esc_url( $dismiss_url );
                            } ?>">
                            <p><?php echo $admin_notice->message; ?></p>

                        </div><?php
                    }else if ( ! get_option( "zerobscrm_dismissed_{$zbs_option}" ) && $zbs_option != 'one-or-more' ){
                        ?><div
                            class="ui message <?php echo $type; ?> notice zerobscrm-notice notice-<?php echo $type;

                            if ( $admin_notice->dismiss_option ) {
                                echo ' is-dismissible" data-dismiss-url="' . esc_url( $dismiss_url );
                            } ?>">
                            <p><?php echo $admin_notice->message; ?></p>

                        </div><?php
                    }
                }
            }
        }

        public function error( $message, $dismiss_option = false, $dismiss_name) {
            $this->notice( 'error', $message, $dismiss_option, $dismiss_name );
        }

        public function warning( $message, $dismiss_option = false, $dismiss_name) {
            $this->notice( 'warning', $message, $dismiss_option, $dismiss_name );
        }

        public function success( $message, $dismiss_option = false, $dismiss_name) {
            $this->notice( 'success', $message, $dismiss_option, $dismiss_name );
        }

        public function info( $message, $dismiss_option = false, $dismiss_name) {
            $this->notice( 'info', $message, $dismiss_option, $dismiss_name );
        }

        private function notice( $type, $message, $dismiss_option, $dismiss_name ) {
            $notice = new stdClass();
            $notice->message = $message;
            $notice->dismiss_option = $dismiss_option;
            $notice->dismiss_name = $dismiss_name;

            $this->admin_notices->{$type}[] = $notice;
        }

	public static function error_handler( $errno, $errstr, $errfile, $errline, $errcontext ) {
		if ( ! ( error_reporting() & $errno ) ) {
			// This error code is not included in error_reporting
			return;
		}

		$message = "errstr: $errstr, errfile: $errfile, errline: $errline, PHP: " . PHP_VERSION . " OS: " . PHP_OS;

		$self = self::get_instance();

		switch ($errno) {
			case E_USER_ERROR:
				$self->error( $message );
				break;

			case E_USER_WARNING:
				$self->warning( $message );
				break;

			case E_USER_NOTICE:
			default:
				$self->notice( $message );
				break;
		}

		// write to wp-content/debug.log if logging enabled
		error_log( $message );

		// Don't execute PHP internal error handler
		return true;
	}
    }
}