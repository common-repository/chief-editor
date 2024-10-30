<?php
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}

define ( "CHIEFED_DEBUG_LEVEL", 'debug' );

if (! function_exists ( 'chiefed_log' )) {
	function chiefed_log($message) {
		if (WP_DEBUG === true) {
			if (is_array ( $message ) || is_object ( $message )) {
				error_log ( print_r ( $message, true ) );
			} else {
				error_log ( $message );
			}
		}
	}
}

define('CHIEFED_PRE_DESKTOP_PUBLISHING_CPT', 'ce_pre_desktop_pub');
define('CHIEFED_PERIODICAL_SHOT_CPT', 'ce_periodical_cpt');
define("CE_SCHEDULED_COLOR", "#91FEFF");
define("CE_INPRESS_COLOR", "#CFF09E");
define("CE_DRAFT_COLOR", "#cccccc");
define("CE_NEW_COLOR", "#FDD87F");
define("CE_INPRESS_SENT_COLOR", "#f3f5b1");
define("CE_ASSIGNED_COLOR", "#FFADFB");
define("CE_PUBLISHED_COLOR", "#BAADFB");


define('CHIEFED_MANAGER_OPTION_PREFIX','chiefed_manager_');

define ( "CHIEFED_SALES_ROLE", 'chiefed_sales_role' );
define ( "CHIEFED_CHIEF_EDITOR_ROLE", 'chiefed_chief_editor_role' );
define ( "CHIEFED_POST_AUTHOR_ROLE", 'chiefed_post_author_role' );
define ( "CHIEFED_COPYEDITOR_ROLE", 'chiefed_copyeditor_role' );