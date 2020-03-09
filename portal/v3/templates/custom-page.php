<?php
/**
 * Custom Page Template
 *
 * The child-page template
 *
 * @author 		ZeroBSCRM
 * @package 	Templates/Portal/Custom
 * @see			https://zerobscrm.com/kb/
 * @version     3.0
 * 
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Don't allow direct access
do_action( 'zbs_enqueue_scrips_and_styles' );

	// setup some variables
	global $post;
	$post_slug = $post->post_name;
	$fullWidth = false;
	$showNav = true;
	$canView = true;

?><div id="zbs-main" class="zbs-site-main">
	<div class="zbs-client-portal-wrap main site-main zbs-post zbs-hentry">
			<?php
			if ($showNav){
				zeroBS_portalnav($post_slug);
			}
			if (!$canView){
				echo '<div class="zbs-alert-danger">' . __("<b>Error:</b> You are not allowed to view this Page","zero-bs-crm") . '</div>';
			} else { 

				echo "<div class='zbs-portal-wrapper'>";
						$the_title 		= apply_filters('the_title', $post->post_title);
						$the_content 	= apply_filters('the_content', $post->post_content);
						echo '<h1>' . $the_title . '</h1>';
						echo "<div class='content' style='position:relative;'>";
							echo $the_content;
						echo "</div>";
					echo "</div>";

			}  ?><div style="clear:both"></div>
			<?php zeroBSCRM_portalFooter(); ?>
	</div>
</div>