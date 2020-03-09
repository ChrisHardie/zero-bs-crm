<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V2.99.12+
 *
 * Copyright 2019+ ZeroBSCRM.com
 *
 * Date: 29/10/2019
 */

/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */

	global $zbs;	

	#} Assets we need specifically here
	
		// js
		wp_enqueue_script("jquery");
		// not really needed. wp_enqueue_script('zbsbeforeyougojs', plugins_url('/js/before-you-go/jquery.blockUI.min.js',ZBS_ROOTFILE), array( 'jquery' ), $zbs->version);

		// css
		wp_enqueue_style('zbsbeforeyougocssloadstyles', 	plugins_url('/css/before-you-go/loadstyles.min.css',ZBS_ROOTFILE) );
		wp_enqueue_style('zbsbeforeyougocssopensans', 		plugins_url('/css/before-you-go/opensans.css',ZBS_ROOTFILE) );
		wp_enqueue_style('zbsbeforeyougocssadminmin', 		plugins_url('/css/before-you-go/admin.min.css',ZBS_ROOTFILE) );
		wp_enqueue_style('zbsbeforeyougocssexitform', 		plugins_url('/css/before-you-go/zbs-exitform.min.css',ZBS_ROOTFILE) );

		// dequeue anything?
		wp_dequeue_style('admin-bar-css');

	#} Image URLS
   	$assetsURLI = ZEROBSCRM_URL.'i/';
   
?><!DOCTYPE html>
<html lang="en-US">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width">
	<title>Before you Go...</title>
	<?php wp_print_styles(); ?>
	<style type="text/css">img.wp-smiley,img.emoji{display:inline !important;border:none !important;box-shadow:none !important;height:1em !important;width:1em !important;margin:0 .07em !important;vertical-align:-0.1em !important;background:none !important;padding:0 !important}#wc-logo img{max-width:20% !important}#feedbackPage{display:none}.wc-setup .wc-setup-actions .button-primary{background-color:#408bc9 !important;border-color:#408bc9 !important;-webkit-box-shadow:inset 0 1px 0 rgba(255,255,255,.25),0 1px 0 #408bc9 !important;box-shadow:inset 0 1px 0 rgba(255,255,255,.25),0 1px 0 #408bc9 !important;text-shadow:0 -1px 1px #408bc9,1px 0 1px #408bc9,0 1px 1px #408bc9,-1px 0 1px #408bc9 !important;float:right;margin:0;opacity:1}</style>	
	<style type="text/css">#wpadminbar { display:none !important; }</style>	
</head>
<body class="wc-setup wp-core-ui">
			<h1 id="wc-logo"><a href="https://zerobscrm.com" target="_blank"><img src="<?php echo $assetsURLI; ?>logo-400.png" alt="Zero BS CRM"></a></h1>
		<div class="wc-setup-content" id="firstPage">
			<h1>Before you go...</h1>
			<p>Thank you for trying ZBS CRM, before you go, we'd really love your feedback on our Free CRM plugin. It'd make our day if you could guide us to improving ZBS :)</p>
			<p>Just temporarily deactivating, or don't fancy giving feedback? No worries.<br />We're improving ZBS every week, so come back sometime and check us out @ <a href="https://zerobscrm.com">ZeroBSCRM.com</a></p>
			<p>All the best</p>
			<p>Mike &amp; Woody</p>
			<p class="wc-setup-actions step">
				<a href="http://goo.gl/forms/29I7Zgl0Eu2ZZybm2" target="_blank" class="button-primary button button-large button-next" id="giveFeedback">Let's Go! (Give Feedback)</a>
				<a href="<?php echo admin_url('plugins.php'); ?>" class="button button-large" id="notNow">Not right now</a>
			</p>
		</div>			
		<p style="text-align:center">(Giving feedback won't close this tab, and it shouldn't take more than 3 or 4 minutes)</p>
<?php // actually don't need scripts here :)
/* wp_print_scripts(); //wp_footer(); ?>
<script type="text/javascript">var feedbackCycle=false;jQuery(document).ready(function(){jQuery("#giveFeedback").click(function(){logFeedback(true,function(){setTimeout(function(){window.location=jQuery("#notNow").attr("href")},1000)})});jQuery("#notNow").click(function(a){a.preventDefault();logFeedback(false,function(){window.location=jQuery("#notNow").attr("href")});return false})});function logFeedback(b,a){if(!window.feedbackCycle){var c={action:"markFeedback",feedbackgiven:b};jQuery.ajax({type:"POST",url:"<?php echo admin_url('admin-ajax.php'); ?>",data:c,dataType:"json",timeout:20000,success:function(d){window.feedbackCycle=true;if(typeof a=="function"){a()}},error:function(d){window.feedbackCycle=true;if(typeof a=="function"){a()}}})}};</script>
*/ ?>
</body></html>