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

/**
 * Wrapper for zerobscrm_doing_it_wrong.
 *
 * @since  3.0.0
 * @param  string $function
 * @param  string $version
 * @param  string $replacement
 */
function zerobscrm_doing_it_wrong( $function, $message, $version ) {
	$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

	if ( is_ajax() ) {
		do_action( 'doing_it_wrong_run', $function, $message, $version );
		error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );
	} else {
		_doing_it_wrong( $function, $message, $version );
	}
}


/* ======================================================
	Error Log :) 
	===================================================== */
    function zbs_write_log ( $log )  {
   
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
      
    }



/* ======================================================
  Globally useful generic Funcs
  NOTE, this file will eventually dissolve into PROPER LIBS :) 
   ====================================================== */
   

	#} https://wordpress.stackexchange.com/questions/221202/does-something-like-is-rest-exist
	function zeroBSCRM_is_rest() {
		$prefix = rest_get_url_prefix( );
		if (defined('REST_REQUEST') && REST_REQUEST // (#1)
			|| isset($_GET['rest_route']) // (#2)
				&& strpos( trim( $_GET['rest_route'], '\\/' ), $prefix , 0 ) === 0)
			return true;

		// (#3)
		$rest_url = wp_parse_url( site_url( $prefix ) );
		$current_url = wp_parse_url( add_query_arg( array( ) ) );
		return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
	}


   // adapted from here https://wordpress.stackexchange.com/questions/15376/how-to-set-default-screen-options
   function zeroBSCRM_unhideMetaBox($postType='',$unhideKey='',$userID=''){

	    // So this can be used without hooking into user_register
	    if ( ! $userID) $userID = get_current_user_id(); 

	    // remove from setting
        $new = array(); $existing = get_user_meta( $userID, 'metaboxhidden_'.$postType, true);
        if (is_array($existing)) {
        	foreach ($existing as $x) if ($x != $unhideKey) $new[] = $x;
        	update_user_meta( $userID, 'metaboxhidden_'.$postType, $new );
        }

   }


   #} Return the admin URL of a slug
   function zeroBSCRM_getAdminURL($slug){
   		$url = admin_url('admin.php?page=' . $slug);
   		return $url;
   }


	function zeroBSCRM_slashOut($str='',$return=false){

		$x = addslashes($str);
		if ($return) return $x;
		echo $x;

	}

	// will strip slashes from a string or recurrsively for all strings in array :) 
	// BE CAREFUL with this one.
	function zeroBSCRM_stripSlashes($obj='',$return=true){

		switch (gettype($obj)){

			case 'string':

				// simple
				$x = stripslashes($obj);
				if ($return) return $x;
				echo $x;

				break;

			case 'array':

				// recursively strip
				$x = zeroBSCRM_stripSlashesFromArr($obj);

				if ($return) return $x;
				// this'll never work? echo $x;

				break;

			default:

				// NON str/arr... should not be using this for them>!
				return $obj;

				break;


		}

	}


	function zeroBSCRM_stripSlashesFromArr($value){
	    $value = is_array($value) ?
	                array_map('zeroBSCRM_stripSlashesFromArr', $value) :
	                stripslashes($value);

	    return $value;
	}


   # from http://wordpress.stackexchange.com/questions/91900/how-to-force-a-404-on-wordpress
	function zeroBSCRM_force_404() {
        status_header( 404 );
        nocache_headers();
        include( get_query_template( '404' ) );
        die();
	}

	// WH not sure why we need this, shuttled off into zeroBSCRM_generateHash which is cleaner.
   	function zeroBSCRM_GenerateHashForPost($postID=-1,$length=20){

   		#} Brutal hash generator, for now
   		if (!empty($postID)){

   			return zeroBSCRM_generateHash($length);

   		}

   		return '';

	}

	// WH centralised, we had zeroBSCRM_GenerateHashForPost - but as moving away from CPT's not sure why
	function zeroBSCRM_generateHash($length=20){

		$genLen = 20; if ($genLen < $length) $genLen = $length;
		$newMD5 = wp_generate_password($genLen, false);

		return substr($newMD5,0,$length-1);

	}

	function zeroBSCRM_loadCountryList(){
	    #} load country list                                   
	    global $zeroBSCRM_countries;
	    if(!isset($zeroBSCRM_countries)) require_once(ZEROBSCRM_PATH . 'includes/wh.countries.lib.php');

	    return $zeroBSCRM_countries;
	}

	function zeroBSCRM_uniqueID(){
		
		/*$ret = '';
		$ret = uniqid('ZBS_');
		return $ret;*/

		#} When you're wrapping a func in another, and you're guaranteed it'll return a val, can just do this:
		
		$prefix = 'ab33id_';
		##WLREMOVE
		$prefix = 'ZBS_';
		##/WLREMOVE
		
		return uniqid($prefix);

	}

	function zeroBSCRM_ifV($v){
		if (isset($v)) echo $v; 
	}

	// if is array and has value v, else
	function zbs_ifAV($a=array(),$v='',$else=false){
		if (is_array($a) && isset($a[$v])) return $a[$v];
		return $else;
	}

	function zbs_prettyprint($array){
		echo '<pre>';
	    var_dump($array);
	    echo '</pre>';
	}


	function zeroBS_delimiterIf($delimiter,$ifStr=''){

		if (!empty($ifStr)) return $delimiter;

		return '';
	}


// BE INCREADIBLY CAREFUL WITH THIS FUNC, it'll recursively delete a directory
// ... safety mechanism put in - if not defined will die :)
function zeroBSCRM_del($dir) { 

   if (!defined('ZBS_OKAY_TO_PROCEED')) exit('CANNOT');
   if (file_exists($dir) && is_dir($dir)){
	   	$files = array_diff(scandir($dir), array('.','..')); 
	    if (is_array($files)) foreach ($files as $file) { 
	      (is_dir("$dir/$file")) ? zeroBSCRM_del("$dir/$file") : unlink("$dir/$file"); 
	    } 
	    return rmdir($dir); 
	}
}


function zeroBSCRM_user_last_login( $user_login, $user ) {
    update_user_meta( $user->ID, 'last_login', time() );
}
add_action( 'wp_login', 'zeroBSCRM_user_last_login', 10, 2 );


function zeroBSCRM_currentUser_email() {
    $current_user = wp_get_current_user();
    return $current_user->user_email;
}
function zeroBSCRM_currentUser_displayName() {
    $current_user = wp_get_current_user();
    return $current_user->display_name;
}

 
/**
 * Display last login time
 *
 */
  
function zeroBSCRM_wpb_lastlogin($uid ) { 
    $last_login = get_user_meta( $uid, 'last_login',true);
    if($last_login == ''){
    	$the_login_date = __("Never","zero-bs-crm");
    }else{
    	$the_login_date = human_time_diff($last_login);
	}
    return $the_login_date; 
} 



	#} Pretty up long numbers
	function zeroBSCRM_prettifyLongInts($i){
		
		if ((int)$i > 999){
			return number_format($i);	
		} else {
			if (zeroBSCRM_numberOfDecimals($i) > 2) return round($i,2); else return $i;	
		}
		
	}

	// Brutal. http://snipplr.com/view/39450/
	function zeroBSCRM_prettyAbbr($size) {
	    $size = preg_replace('/[^0-9]/','',$size);
	    $sizes = array("", "k", "m");
	    if ($size == 0) { return('n/a'); } else {
	    return (round($size/pow(1000, ($i = floor(log($size, 1000)))), 0) . $sizes[$i]); }
	}


	#} how many decimal points?
	function zeroBSCRM_numberOfDecimals($value)
	{
		if ((int)$value == $value)
		{
			return 0;
		}
		else if (! is_numeric($value))
		{
			return false;
		}

		return strlen($value) - strrpos($value, '.') - 1;
	}


	function zeroBSCRM_mtime_float(){
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}     

	#} Does it's best to find the real IP for user
	function zeroBSCRM_getRealIpAddr()
	{
		#} check ip from share internet
		if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']))
		{
			$ip=$_SERVER['HTTP_CLIENT_IP'];
		}
		#} To check ip is pass from proxy
		elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		elseif (isset($_SERVER['REMOTE_ADDR']))
		{
			$ip=$_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	// from https://stackoverflow.com/questions/5800927/how-to-identify-server-ip-address-in-php
	function zeroBSCRM_getServerIP(){

		$ip = false;

		// this method is spoofable/not safe on all hosts
		// non iis
		if (!$ip && isset($_SERVER['SERVER_ADDR']) && !empty($_SERVER['SERVER_ADDR'])) $ip = $_SERVER['SERVER_ADDR'];
		// iis
		if (!$ip && isset($_SERVER['LOCAL_ADDR']) && !empty($_SERVER['LOCAL_ADDR'])) $ip = $_SERVER['LOCAL_ADDR'];


		// this method uses dns
		if (!$ip){
			$host= gethostname();
			$ip = gethostbyname($host);
		}

		return $ip;
	}

	#} from https://stackoverflow.com/questions/12553160/getting-visitors-country-from-their-ip
	function zeroBSCRM_ip_country()
	{
	
		/*
		$client = ''; if (isset($_SERVER['HTTP_CLIENT_IP'])) 		$client  = @$_SERVER['HTTP_CLIENT_IP'];
		$forward = ''; if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
		$remote = ''; if (isset($_SERVER['REMOTE_ADDR'])) 			$remote  = $_SERVER['REMOTE_ADDR'];
		if(filter_var($client, FILTER_VALIDATE_IP))
		{
			$ip = $client;
		}
		elseif(filter_var($forward, FILTER_VALIDATE_IP))
		{
			$ip = $forward;
		}
		else
		{
			$ip = $remote;
		}
		*/
		$ip = zeroBSCRM_getRealIpAddr(); $ip_data = false;
		$country  = "Unknown";
	

		/* Switched away from CURL for proper wp functions 29/10/19
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://www.geoplugin.net/json.gp?ip=".$ip);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$ip_data_in = curl_exec($ch); // string
		curl_close($ch);
		*/

	    $ip_data_in = wp_remote_get( 'http://www.geoplugin.net/json.gp?ip='.$ip, array(
		    'timeout'     => 15
		    )
		);

		if ( is_wp_error( $ip_data_in ) ) {
		    
		    //$error_message = $response->get_error_message();
		    //echo "Something went wrong: $error_message";

		} else {

		    if (is_array($ip_data_in['body']) && isset($ip_data_in['body']) && is_string($ip_data_in['body'])){
				$ip_data = json_decode($ip_data_in['body'],true);
				$ip_data = str_replace('&quot;', '"', $ip_data); // for PHP 5.2 see stackoverflow.com/questions/3110487/
			}
		}
	
		if (is_array($ip_data) && $ip_data && $ip_data['geoplugin_countryName'] != null) {
			$country = $ip_data['geoplugin_countryName'];
		}
	
		return $country;
	}



	function zeroBSCRM_findAB($html,$first,$nextStr,$fallbackCloser='</'){

		$f1 = strpos($html,$first);
		$f1end = $f1 + strlen($first);
		if ($f1){
			$f2 = strpos(substr($html,$f1end),$nextStr);
			if (!$f2){
				#use fallback closer to try
				$f2 = strpos(substr($html,$f1end),$fallbackCloser);
			}
			if (!$f2) $f2 = strlen(substr($html,$f1end));
			return substr($html,$f1end,$f2);
		}

		#if nothing returned?
		return '';
	}

	#} Proper tried URL retrieval
	/* Switched away from CURL for proper wp functions 29/10/19
	function zeroBSCRM_retrieve($u,$withType=false,$return404s=false,$passPost=array()){
		
		try {

			if( function_exists('curl_init') ) { 

					$ch = curl_init($u);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); #} Follow 301's

					#UA? #v212
					#old $ua="Mozilla/5.0 (X11; U; Linux i686; en-US) AppleWebKit/534.7 (KHTML, like Gecko) Ubuntu/10.04 Chromium/7.0.514.0 Chrome/7.0.514.0 Safari/534.7";
					#wmbpro
					$ua='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36';
					 if ($ua!="") {
							curl_setopt($ch, CURLOPT_USERAGENT, $ua);
						} else {
							curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
						}

					#} Any post?
					if (is_array($passPost) && count($passPost) > 0){

						//url-ify the data for the POST
						foreach($passPost as $key=>$value) { $passPost_string .= $key.'='.$value.'&'; }
						rtrim($passPost_string, '&');

						curl_setopt($ch,CURLOPT_POST, count($passPost));
						curl_setopt($ch,CURLOPT_POSTFIELDS, $passPost_string);

					}

					$ret = curl_exec($ch);
					$headerhttpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					if ($withType) $ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
					curl_close($ch);

					if ($headerhttpcode == 404 || $headerhttpcode == 500) $ret = '';

					
			} else $ret = file_get_contents($u);
		} catch (Exception $e){
			$ret = false;	
		}

		if ($withType)
			return array($ret,$ctype);
		else
			return $ret;
		
	}
	*/

	#} Proper tried URL retrieval
	/* this works well, but isn't very wp, had to switch out 29/10 for below
	function zeroBSCRM_retrieveFile($u,$filepath){

	 	 $fp = fopen($filepath, 'w+');
	     $ch = curl_init($u);

	     curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
	     curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
	     //curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	     curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	     curl_setopt($ch, CURLOPT_FILE, $fp);
	     curl_exec($ch);

	     curl_close($ch);
	     fclose($fp);

	     return (filesize($filepath) > 0)? true : false;

	} */

	// as clean as zeroBSCRM_retrieveFile was above, we needed to wpify for .org.
	// here's an adaptation of https://wordpress.stackexchange.com/questions/50094/wp-remote-get-downloading-and-saving-files
	function zeroBSCRM_retrieveFile($url,$filepath){

		// Use wp_remote_get to fetch the data
		$response = wp_remote_get($url);

		// Save the body part to a variable
		if (is_array($response) && isset($response['body'])){

			// Now use the standard PHP file functions
			$fp = fopen($filepath, "w");
			fwrite($fp, $response['body']);
			fclose($fp);

	     	return (filesize($filepath) > 0)? true : false;

		}

		return false;

	}


	# http://stackoverflow.com/questions/8889025/unzip-a-file-with-php
	function zeroBSCRM_expandArchive($filepath,$expandTo){

		#} REQUIRES PHP 5.2+ - this should be enabled by default
		#} But because some hosts SUCK we have to check + workaround
		if (zeroBSCRM_checkSystemFeat('zlib')){

			#} All should be okay
			try {

				if (file_exists($filepath) && file_exists($expandTo)){

					$zip = new ZipArchive;
					$res = $zip->open($filepath);
					if ($res === TRUE) {
					  $zip->extractTo($expandTo);
					  $zip->close();
					  return true;
					}

				}

			} catch (exception $ex){


			}

		} else {

			// NO ZipArchive, fallback to our include:
			#} This can cause php7 compat warnings: 
			# if (!class_exists('PclZip')) require_once(ZEROBSCRM_PATH . 'includes/lib/pclzip-2-8-2/pclzip.lib.php');
			if (!class_exists('PclZip')) require_once(ZEROBSCRM_PATH . 'includes/lib/pclzip-2-8-2/pclzip.lib.php7.php');

			// proceed using pcl
			try {

				if (file_exists($filepath) && file_exists($expandTo)){

						$archive = new PclZip($filepath);

						if ($archive->extract(PCLZIP_OPT_PATH, $expandTo) == 0) {
						    
						    return false;

						} else {
						    
						    return true;

						}


				}

			} catch (exception $ex){


			}




		}

		return false;

	}


	function zeroBSCRM_getGravatarURLfromEmail($email='',$size=80){

		// https:
		$url = '//www.gravatar.com/avatar/' . md5( $email );
		$url = add_query_arg( array(
			's' => $size,
			'd' => 'mm',
		), $url );
		return esc_url_raw( $url );
	}


	function zeroBSCRM_prettyformatBytes($size, $precision = 2){
		    $base = log($size, 1024);
		    $suffixes = array('', 'K', 'M', 'G', 'T');   

		    return round(pow(1024, $base - floor($base)), $precision) .''. $suffixes[floor($base)];
		}


	// returns send-from email + name
	// code ripped from wp_mail func 12/9/18
	// https://developer.wordpress.org/reference/functions/wp_mail/
	function zeroBSCRM_wp_retrieveSendFrom(){

	    // From email and name
	    // If we don't have a name from the input headers
	    //if ( !isset( $from_name ) )
	        $from_name = 'WordPress';

	    //if ( !isset( $from_email ) ) {
	        // Get the site domain and get rid of www.
	        $sitename = strtolower( $_SERVER['SERVER_NAME'] );
	        if ( substr( $sitename, 0, 4 ) == 'www.' ) {
	            $sitename = substr( $sitename, 4 );
	        }
	 
	        $from_email = 'wordpress@' . $sitename;
	    //}
	    $from_email = apply_filters( 'wp_mail_from', $from_email );

	    return array('name'=>$from_name,'email'=>$from_email);
	}



	#} This'll be true if wl
	function zeroBSCRM_isWL(){

		##WLREMOVE
        return false;
		##/WLREMOVE
		return true;

	}


	// ============= TELEMETRY SECTION


	// https://wordpress.stackexchange.com/questions/52144/what-wordpress-api-function-lists-active-inactive-plugins
	function zeroBSCRM_allPluginListSimple() {
    	$plugins = get_plugins();
    	$p = array();
        if (count($plugins) > 0) {
        	foreach ( $plugins as $plugin ) {

        		$p[] = array('n' => $plugin['Name'],'v' => $plugin['Version']);

        	}
        } 

        return $p;
    }

    // this ver gets ONLY active
    function zeroBSCRM_activePluginListSimple(){
    	$pluginsActive = get_option('active_plugins');
    	$plugins = get_plugins();
    	$p = array();
        if (count($plugins) > 0) {
        	foreach ( $plugins as $pluginKey => $plugin ) {

        		if (in_array($pluginKey,$pluginsActive)) $p[] = array('n' => $plugin['Name'],'v' => $plugin['Version']);

        	}
        } 
        
        return $p;
    }

   	##WLREMOVE
    // logs an internal page hit in active period of telemetry
    // p = page slug
    /*function zeroBSCRM_teleLog($p=''){

    	// defunct 2.7+
	    return true;
    }*/

    // logs a deactivate/activate
    // activate = 2 /deactive = 3
    /*function zeroBSCRM_teleLogAct($a=false){

    	// defunct 2.7+
    	return true;
    }

    // logs a user hitting page (adds to unique user count)
    // $u = username
    function zeroBSCRM_teleLogUsr($u=false){

    	// defunct 2.7+
    	return true;
    }*/
   	##/WLREMOVE

	// ============= / TELEMETRY SECTION


	#} ZBS JSONP decode
	// https://stackoverflow.com/questions/5081557/extract-jsonp-resultset-in-php
	function zeroBSCRM_jsonp_decode($jsonp, $assoc = false) { // PHP 5.3 adds depth as third parameter to json_decode
	    if($jsonp[0] !== '[' && $jsonp[0] !== '{') { // we have JSONP
	       $jsonp = substr($jsonp, strpos($jsonp, '('));
	    }
	    return json_decode(trim($jsonp,'();'), $assoc);
	}

	// used by DAL2 settings 
	// https://stackoverflow.com/questions/6041741/fastest-way-to-check-if-a-string-is-json-in-php
	function zeroBSCRM_isJson($str) {
	    $json = json_decode($str);
	    return $json && $str != $json;
	}

	// return placeholder img :) DAL2 friendly
	function zeroBSCRM_getDefaultContactAvatar(){

		// hmm - how to pass an img here? when using <i class="child icon"></i> for html
		// for now made a quick png
		return plugins_url('/i/default-contact.png',ZBS_ROOTFILE);

	}

	// return bullie "MOOOOOVE OVER" 
	// return placeholder img :) DAL2 friendly
	function zeroBSCRM_getBullie(){

		// hmm - how to pass an img here? when using <i class="child icon"></i> for html
		// for now made a quick png
		return plugins_url('/i/bullie.png',ZBS_ROOTFILE);

	}

	// return logo
	function zeroBSCRM_getLogoURL($black=false){

		if (zeroBSCRM_isWL())
			return plugins_url('/i/icon-32.png',ZBS_ROOTFILE);
		else
			if ($black)
				return plugins_url('/i/zero-bs-crm-admin-logo-black.png',ZBS_ROOTFILE);
			else
				return plugins_url('/i/zero-bs-crm-admin-logo-clear.png',ZBS_ROOTFILE);

	}

	// return placeholder img :) DAL2 friendly
	function zeroBSCRM_getDefaultContactAvatarHTML(){

		return '<i class="child icon zbs-default-avatar"></i>';

	}

	// https://stackoverflow.com/questions/2524680/check-whether-the-string-is-a-unix-timestamp
	function zeroBSCRM_isValidTimeStamp($timestamp){
	    return ((string) (int) $timestamp === $timestamp) 
	        && ($timestamp <= PHP_INT_MAX)
	        && ($timestamp >= ~PHP_INT_MAX);
	}

	// for use with export as per:
	// because of SYLK bug in excel, we have to wrap these in "" - but fputcsv doesnt do it :/
	// https://www.alunr.com/excel-csv-import-returns-an-sylk-file-format-error/
	// https://stackoverflow.com/questions/2489553/forcing-fputcsv-to-use-enclosure-for-all-fields
	function zeroBSCRM_encloseArrItems($arr=array(),$encloseWith='"'){

		$endArr = $arr;

		if (is_array($arr)){

			$endArr = array();
			foreach ($arr as $k => $v){
				$endArr[$k] = $encloseWith.$v.$encloseWith;
			}

		}

		return $endArr;
	}


	// recursive utf8-ing 
	// https://stackoverflow.com/questions/19361282/why-would-json-encode-return-an-empty-string
	function zeroBSCRM_utf8ize($d) {
	    if (is_array($d)) {
	        foreach ($d as $k => $v) {
	            $d[$k] = zeroBSCRM_utf8ize($v);
	        }
	    } else if (is_string ($d)) {
	        return utf8_encode($d);
	    }
	    return $d;
	}


	// returns a filetype img if avail
	// returns 48px from  https://github.com/redbooth/free-file-icons
	// ... cpp has fullsize 512px variants, but NOT to be added to core, adds bloat
	function zeroBSCRM_fileTypeImg($fileExtension=''){

		$fileExtension = sanitize_text_field( $fileExtension );
		if (!empty($fileExtension) && file_exists(ZEROBSCRM_PATH.'i/filetypes/'.$fileExtension.'.png')) return ZEROBSCRM_URL.'i/filetypes/'.$fileExtension.'.png';

		return ZEROBSCRM_URL.'i/filetypes/_blank.png';

	}

	// https://stackoverflow.com/questions/3797239/insert-new-item-in-array-on-any-position-in-php
	/**
	 * @param array      $array
	 * @param int|string $position
	 * @param mixed      $insert
	 */
	function zeroBSCRM_array_insert(&$array, $position, $insert)
	{
	    if (is_int($position)) {
	        array_splice($array, $position, 0, $insert);
	    } else {
	        $pos   = array_search($position, array_keys($array));
	        $array = array_merge(
	            array_slice($array, 0, $pos),
	            $insert,
	            array_slice($array, $pos)
	        );
	    }
	}

	// WH ver of zeroBSCRM_array_insert, specifically used for messing with menu arrs (used mc2)
	function zeroBSCRM_array_insert_ifset(&$array, $position, $insert){

		// check for $position legitimacy
	    if (is_int($position)) {

	    	if (count($array) > $position) 
	    		return zeroBSCRM_array_insert($array,$position,$insert);
	    	else {
	    		// just add
	    		$array = array_merge($array,$insert);
	    		return $array;
	    	}


	    } else if (is_array($position)){

	    	// array - checks for subvalues to find position
	    	/* 

	    		e.g. in this:


						Array
						(
						    [0] => Array
						        (
						            [0] => Zero BS CRM
						            [1] => zbs_dash
						            [2] => zerobscrm-dash
						            [3] => Zero BS CRM User Dash
						        )

						    [1] => Array
						        (
						            [0] => Contacts
						            [1] => admin_zerobs_view_customers
						            [2] => manage-customers
						            [3] => Contacts
						        )

						    [2] => Array
						        (
						            [0] => Quotes
						            [1] => admin_zerobs_view_quotes
						            [2] => manage-quotes
						            [3] => Quotes
						        )

				... if you passed $position as:

					array('1'=>'admin_zerobs_view_customers')

				... it'd insert before [1]

			*/


	    	// brutal
	    	$endPos = -1; $i = 0;
	    	foreach ($array as $a){
	    		// match position?
	    		foreach ($position as $k => $v){
	    			if ($a[$k] == $v){

	    				// has an attr matching position
	    				$endPos = $i;
	    			}
	    		}

	    		$i++;
	    	}

	    	// should now have pos
	    	if ($endPos > -1){

		    	// probs str, fallback to 
		    	return zeroBSCRM_array_insert($array,$endPos,$insert);


	    	} else {

	    		// append
	    		$array = array_merge($array,$insert);
	    		return $array;

	    	}

	    } else {

	    	// probs str, fallback to 
	    	return zeroBSCRM_array_insert($array,$position,$insert);

	    }

	}

/* ======================================================
  / Globally useful generic Funcs
   ====================================================== */

/* ======================================================
  unsub creation stuff - can't go in other as that's optionally included,
  // migrations sometimes need to use pre-inclusion, so here for now #notidealbutokay
   ====================================================== */
// this is fired by a migration, and checked on deactivate ext
function zeroBSCRM_unsub_checkCreatePage(){

	global $zbs;

	//check if the page exists, if not create and call it clients
	$pageID = zeroBSCRM_mail_getUnsubscribePage();

	if (empty($pageID) || $pageID < 1){

		// wh added to stop weird multi-fires (moving to migration fixed, but this is double protection)
		if (!defined('ZBS_UNSUB_PAGE_MADE')){


			//then we do not have a page for the client portal, create one, with slug clients and set as page
			//this should handle any backwards compatibility and not lose the URLs created
			$args = array(
				'post_name' => 'unsubscribe',
				'post_status' => 'publish',
				'post_title' => __('Unsubscribed','zero-bs-crm'),
				'post_content' => '[zerobscrm_unsubscribe]',
				'post_type'	=> 'page'
			);

			$pageID = wp_insert_post($args);
			$zbs->settings->update('unsubpage', $pageID);
			define('ZBS_UNSUB_PAGE_MADE',1);

			return $pageID;

		}

	} else return $pageID;

	return -1;
}
// returns an active page id or -1
function zeroBSCRM_mail_getUnsubscribePage(){

		// what settings says it is
		$pageID = (int)zeroBSCRM_getSetting('unsubpage');

		// is page live?
		if (!empty($pageID) || $pageID > 0) {

			$pageStatus = get_post_status($pageID);
			// page is trashed or smt, recreate
			if ($pageStatus !== 'publish') $pageID = -1;

		} else $pageID = -1;

		return $pageID;
}
/* ======================================================
  / unsub creation stuff
   ====================================================== */


/* ======================================================
  Portal creation stuff - can't go in .Portal.php as that's optionally included,
  // migrations sometimes need to use pre-inclusion, so here for now #notidealbutokay
   ====================================================== */
// this is fired by a migration, and checked on deactivate ext
function zeroBSCRM_portal_checkCreatePage(){

	global $zbs;

	//check if the page exists, if not create and call it clients
	$portalPage = zeroBSCRM_portal_getPortalPage();

	if (empty($portalPage) || $portalPage < 1){

		// wh added to stop weird multi-fires (moving to migration fixed, but this is double protection)
		if (!defined('ZBS_PORTAL_PAGE_MADE')){


			//then we do not have a page for the client portal, create one, with slug clients and set as page
			//this should handle any backwards compatibility and not lose the URLs created
			$args = array(
				'post_name' => 'clients',
				'post_status' => 'publish',
				'post_title' => __('Client Portal','zero-bs-crm'),
				'post_content' => '[zerobscrm_clientportal]',
				'post_type'	=> 'page'
			);

			$portalID = wp_insert_post($args);
			$zbs->settings->update('portalpage', $portalID);
			define('ZBS_PORTAL_PAGE_MADE',1);

			return $portalID;

		}

	} else return $portalPage;

	return -1;
}

// returns an active page id or -1
function zeroBSCRM_portal_getPortalPage(){

		// what settings says it is
		$portalPage = (int)zeroBSCRM_getSetting('portalpage');

		// is page live?
		if (!empty($portalPage) || $portalPage > 0) {

			$pageStatus = get_post_status($portalPage);
			// page is trashed or smt, recreate
			if ($pageStatus !== 'publish') $portalPage = -1;

		} else $portalPage = -1;

		return $portalPage;
}
/* ======================================================
  / Portal creation stuff
   ====================================================== */



/* ======================================================
   Link Helpers
   ====================================================== */

// produces a portal based link to a potentially-hashed obj (inv/quo as of v3.0)
function zeroBSCRM_portal_linkObj($objID=-1,$typeInt=ZBS_TYPE_INVOICE){

    global $zbs;

	// portal base
    $portalLink         = zeroBS_portal_link();

	// hash or id link? (if inv/quote)
    $useHash = zeroBSCRM_getSetting('easyaccesslinks');
	                
	switch ($typeInt){

		case ZBS_TYPE_INVOICE:

			// get inv settings
			$settings = zeroBSCRM_get_invoice_settings();
            $invoices_endpoint   = zeroBSCRM_portal_get_invoice_endpoint();

			//if invoice hashes this will be a hash URL, otherwise the invoice ID
		    if ($useHash == "1"){
		    	$hash = $zbs->DAL->invoices->getInvoiceHash($objID);
		    	if (!empty($hash)) return esc_url($portalLink .  $invoices_endpoint .  '/zh-' . $hash);
		    }

		    // otherwise just id
			return esc_url($portalLink .  $invoices_endpoint .  '/' . $objID);

		break;

		case ZBS_TYPE_QUOTE:

			// get quotes stem	
            $quotes_endpoint   = zeroBSCRM_portal_get_quote_endpoint();

            // got hash?
            if ($useHash == "1"){
			    $hash = $zbs->DAL->quotes->getQuoteHash($objID);
		    	if (!empty($hash)) return esc_url($portalLink .  $quotes_endpoint .  '/zh-' . $hash);
		    }

		    // otherwise just id
			return esc_url($portalLink .  $quotes_endpoint .  '/' . $objID);


		break;

	}

}

function zeroBS_portal_link($type='dash',$objIDorHashStr=-1){
	
	$portalPage = zeroBSCRM_getSetting('portalpage');
	$portalLink = get_page_link($portalPage);

	// get slug
	$portalSlug = 'clients'; 
	if (isset($portalPage) && !empty($portalPage) && $portalPage !== '') $portalSlug = get_post_field('post_name',$portalPage);

	switch ($type){

		case 'dash':
		case 'dashboard':
		case '':

			if (!isset($portalPage) || empty($portalPage) || $portalPage == ''){
				$portalLink = home_url('/clients');
			}

			// proper
			return $portalLink;

			break;

		default:

			// catch generic e.g. quotes invoices 
			
			$stem = $type; //'quotes';

			// if cpp, use that stem
			if (function_exists('zeroBSCRM_clientPortalgetEndpoint')) $stem = zeroBSCRM_clientPortalgetEndpoint($stem);

			// if using a str (hash) then prefix with zh- if not already
			if (is_string($objIDorHashStr)){
				if (substr($objIDorHashStr, 0,3) != 'zh-') $objIDorHashStr = 'zh-'.$objIDorHashStr;
			}

			if (
				(!is_string($objIDorHashStr) && ($objIDorHashStr == -1 || $objIDorHashStr <= 0)) // is false ID
				||
				(is_string($objIDorHashStr) && empty($objIDorHashStr)) // is empty hash str
				)
				return home_url('/'.$portalSlug.'/'.$stem.'/');
			else
				return home_url('/'.$portalSlug.'/'.$stem.'/'.$objIDorHashStr);
			break;


	}

	if (!isset($portalPage) || empty($portalPage) || $portalPage == ''){
		// this is a guess, at best... probably would never work.
		$portalLink = home_url('/clients');
	}
	return home_url('/#notfound');
}

/* ======================================================
  / Link Helpers
   ====================================================== */