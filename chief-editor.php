<?php
/*
 * Plugin Name: Chief Editor
 * Plugin URI: http://www.termel.fr
 * Description: Manage all posts, comments and authors accross the network. The Chief Editor toolbox.
 * Version: 5.4.3
 * Author: Termel
 * Author URI: http://www.maxizone.fr
 * Text Domain: chief-editor
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly
}

require_once ( sprintf( "%s/chiefed-constants.php", dirname( __FILE__ ) ) );
require_once ( sprintf( "%s/admin/chief-editor-admin.php", dirname( __FILE__ ) ) );

require_once ( sprintf( "%s/admin/editor_table.php", dirname( __FILE__ ) ) );
require_once ( sprintf( "%s/chiefed_front_datatable.php", dirname( __FILE__ ) ) );
require_once ( sprintf( "%s/chiefed_utils.php", dirname( __FILE__ ) ) );
/*
 * register_activation_hook( __FILE__, array('ChiefEditor','chiefed_register_activation_hook'));
 * register_deactivation_hook( __FILE__, array('ChiefEditor','chiefed_register_deactivation_hook'));
 */
register_activation_hook( __FILE__, array( 'PreDesktopPublishing', 'chiefed_install' ) );
register_uninstall_hook( __FILE__, array( 'PreDesktopPublishing', 'chiefed_uninstall' ) );

// add_action( 'plugins_loaded', array( 'ChiefEditor', 'init' ) );

if ( ! class_exists( 'ChiefEditor' ) ) {

	class ChiefEditor {

		public static $chiefedRolesToAdd = array();

		protected static $instance;

		/*
		public static function init() {
			CHIEFED_UTILS::getLogger()->debug( 'init::' . __CLASS__ );
			//is_null( self::$instance ) and self::$instance = new self();
			if (!self::$instance) {
				self::$instance = new self();
			}
			return self::$instance;
		}*/

		function __construct() {
			CHIEFED_UTILS::getLogger()->debug( 'Construct::' . __CLASS__ );
			add_action( 'admin_enqueue_scripts', array( $this, 'chiefed_admin_scripts' ) );
			add_action( 'init', array( $this, 'chief_editor_load_lang' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'chiefed_adding_styles' ) );			
			
			register_activation_hook( __FILE__, array( $this, 'chiefed_register_activation_hook' ) );			
			register_deactivation_hook( __FILE__, array( $this, 'chiefed_register_deactivation_hook' ) );
		}

		// SCRIPTS
		function chiefed_admin_scripts() {
			CHIEFED_UTILS::getLogger()->debug( 'Construct::chiefed_admin_scripts' );
			$jqueryuiVersion = "1.12.1";
			wp_enqueue_script( 
				'jqueryui-js',
				plugins_url( '/libs/jqueryui/' . $jqueryuiVersion . '/jquery-ui.min.js', __FILE__ ) );

			wp_enqueue_style( 
				'jquery-ui-css',
				plugins_url( '/libs/jqueryui/' . $jqueryuiVersion . '/jquery-ui.min.css', __FILE__ ) );
			/*
			wp_enqueue_style(
			    'chief-editor-swal-css',
			    plugins_url( '/libs/sweetalert2-7.15.0/dist/sweetalert2.min.css', __FILE__ ) );
			wp_enqueue_script(
			    'chief-editor-swal-js',
			    plugins_url( '/libs/sweetalert2-7.15.0/dist/sweetalert2.min.js', __FILE__ ) );
			*/
			// sweetalert2/dist/sweetalert2.all.min.js
			wp_enqueue_script(
			    'chief-editor-swal-js',
			    plugins_url( '/libs/node_modules/sweetalert2/dist/sweetalert2.min.js', __FILE__ ) );
			
			wp_enqueue_style(
			    'chief-editor-swal-css',
			    plugins_url( '/libs/node_modules/sweetalert2/dist/sweetalert2.min.css', __FILE__ ) );
			
			wp_enqueue_script( 'chief-editor-js', plugins_url( '/js/chief-editor.js', __FILE__ ), array('chief-editor-swal-js') );

			wp_enqueue_style( 'chief-editor-css', plugins_url( '/css/chief-editor.css', __FILE__ ) );
			wp_enqueue_style( 'chief-editor-admin-css', plugins_url( '/css/chief-editor-admin.css', __FILE__ ) );			
			wp_enqueue_script( 'sorttable-js', plugins_url( '/js/sorttable.js', __FILE__ ) );
			wp_enqueue_script( 'ce-Chart-js', plugins_url( '/js/ChartNew.js', __FILE__ ) );			
			wp_enqueue_script( 'chief-editor-graph-js', plugins_url( '/js/chief-editor-graph.js', __FILE__ ) );
			
			
			wp_enqueue_script( 'chief-editor-moment-js', plugins_url( '/libs/moment/moment-with-locales.js', __FILE__ ) );
			
			if (is_admin()){
				wp_enqueue_script( 'chief-editor-print-editor-js', plugins_url( '/js/chiefed_print_editor.js', __FILE__ ) );				
			}
		}

		function chiefed_adding_styles() {
			CHIEFED_UTILS::getLogger()->debug( 'Construct::chiefed_adding_styles' );
			
			wp_enqueue_style( 'chief-editor-css', plugins_url( '/css/chief-editor.css', __FILE__ ) );
			
			wp_enqueue_style( 'chief-editor-shot-css', plugins_url( '/css/chiefed-shot.css', __FILE__ ) );
			
			
			
		}
		
		function chief_editor_load_lang() {
			$plugin_name = 'chief-editor';
			$relative_path = dirname( plugin_basename( __FILE__ ) ) . '/languages';
			// echo $relative_path . '<br/>';
			if ( load_plugin_textdomain( 'chief-editor', false, $relative_path ) ) {
				// CHIEFED_UTILS::getLogger()->debug( 'SUCCESS::loading lang file in :'.$relative_path);
			} else {
				CHIEFED_UTILS::getLogger()->debug( 'ERROR::loading lang file' );
			}
		}
		
		public function chiefed_register_activation_hook() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			
			$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
			check_admin_referer( "activate-plugin_{$plugin}" );
			
			CHIEFED_UTILS::getLogger()->info( 'Creating CE Roles...' );
			self::$chiefedRolesToAdd = array(
				CHIEFED_SALES_ROLE => __( 'CE:Sales', 'dental-office' ),
				CHIEFED_CHIEF_EDITOR_ROLE => __( 'CE:Chief Editor', 'dental-office' ),
				CHIEFED_POST_AUTHOR_ROLE => __( 'CE:Auteur', 'dental-office' ),
				CHIEFED_COPYEDITOR_ROLE => __( 'CE:CopyEditor', 'dental-office' ) );
			$needToSeePrePublishedPostOfOthers = 'edit_others_posts';
			
			$contributor = get_role( 'contributor' );
			$contributorCaps = $contributor->capabilities;
			
			if ( is_multisite() ) {
				if ( function_exists( 'get_sites' ) ) {
				$sites = get_sites();
				foreach ( $sites as $site ) {
					switch_to_blog( $site->blog_id );
					CHIEFED_UTILS::getLogger()->info( "Changing roles on site : ".$site->blog_id );
					// do something
					foreach ( self::$chiefedRolesToAdd as $new_role_key => $new_role_name ) {
						CHIEFED_UTILS::getLogger()->info( "Adding new role : " . $new_role_key . ' => ' . $new_role_name );
						remove_role( $new_role_key );
						$add_new_role = add_role( $new_role_key, $new_role_name, $contributorCaps );
						if ( null == $add_new_role ) {
							CHIEFED_UTILS::getLogger()->warn( "Already exists: " . $new_role_name );
						}
						if ( $add_new_role && $new_role_key != CHIEFED_POST_AUTHOR_ROLE ) {
							// post author does not need to see others posts, only chief editor of blog
							$add_new_role->add_cap( $needToSeePrePublishedPostOfOthers );
						}
					}
					restore_current_blog();
				}
				//return;
			}
			} else {
				foreach ( self::$chiefedRolesToAdd as $new_role_key => $new_role_name ) {
					CHIEFED_UTILS::getLogger()->info( "Adding new role : " . $new_role_key . ' => ' . $new_role_name );
					remove_role( $new_role_key );
					$add_new_role = add_role( $new_role_key, $new_role_name, $contributorCaps );
					if ( null == $add_new_role ) {
						CHIEFED_UTILS::getLogger()->warn( "Already exists: " . $new_role_name );
					}
					if ( $add_new_role && $new_role_key != CHIEFED_POST_AUTHOR_ROLE ) {
						// post author does not need to see others posts, only chief editor of blog
						$add_new_role->add_cap( $needToSeePrePublishedPostOfOthers );
					}
				}
			}
			CHIEFED_UTILS::getLogger()->info( "... Done!" );
			flush_rewrite_rules();
		}
		
		public function chiefed_register_deactivation_hook() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			
			$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
			check_admin_referer( "deactivate-plugin_{$plugin}" );
			
			// Uncomment the following line to see the function in action
			$msg = "Disabling plugin, removing roles...";
			/*
			 * CHIEFED_UTILS::getLogger()->debug ($msg);
			 * CHIEFED_UTILS::getLogger()->info($msg);
			 */
			CHIEFED_UTILS::getLogger()->info($msg);
			$rolesToRemove = array(
				CHIEFED_SALES_ROLE => __( 'CE:Sales', 'dental-office' ),
				CHIEFED_CHIEF_EDITOR_ROLE => __( 'CE:Chief Editor', 'dental-office' ),
				CHIEFED_POST_AUTHOR_ROLE => __( 'CE:Auteur', 'dental-office' ),
				CHIEFED_COPYEDITOR_ROLE => __( 'CE:CopyEditor', 'dental-office' ) );
			
			$removed = array();
			
			foreach ( $rolesToRemove as $new_role_key => $new_role_name ) {
				remove_role( $new_role_key );
				// remove_role(strtoupper($new_role_key));
				// CHIEFED_UTILS::getLogger()->debug ("Role removed: ".$new_role_name);
				$removed[] = $new_role_key;
			}
			
			//exit( var_dump( $removed ) );
			CHIEFED_UTILS::getLogger()->info($removed);
			flush_rewrite_rules();
		}
	}
}

new ChiefEditor();