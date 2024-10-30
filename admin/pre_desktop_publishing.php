<?php
if (! defined ( 'ABSPATH' )) {
	exit ();
}
$path = sprintf ( "%s/meta_boxes.php", dirname ( __FILE__ ) );
require_once ($path);
$path = sprintf ( "%s/chiefed_image_processing.php", dirname ( __FILE__ ) );
require_once ($path);
$path = sprintf ( "%s/chiefed_utils.php", dirname ( dirname ( __FILE__ ) ) );
require_once ($path);

// use ChiefEditor\CHIEFED_UTILS;

if (! class_exists ( 'PreDesktopPublishing' )) {
	class PreDesktopPublishing {
		private $cptName = null;
		public static $taxoName = 'chiefed_period';
		private $shotTaxoName = 'chiefed_shot';
		private $preDP_directory_name = null;
		private $preDP_abs_path = null;
		function __construct() {
			// CHIEFED_UTILS::getLogger()->debug  ( "PreDesktopPublishing __construct" );
			$this->cptName = CHIEFED_PRE_DESKTOP_PUBLISHING_CPT;
			$actionName = 'publish_' . $this->cptName;
			$msg = __CLASS__ . ' ' . $actionName;
			CHIEFED_UTILS::getLogger ()->debug ( $msg );
			// CHIEFED_UTILS::getLogger()->debug  ( $msg );
			
			$custom_post_status = false;
			// CHIEFED_UTILS::getLogger()->debug  ( $msg );
			if ($custom_post_status) {
				add_action ( 'init', array (
						$this,
						'predp_custom_post_status' 
				) );
				add_action ( 'admin_footer-post.php', array (
						$this,
						'predp_append_post_status_list' 
				) );
			}
			
			add_filter ( 'wpfc_ajax_post', array (
					$this,
					'chiefed_set_item_color' 
			), 10, 2 );
			
			// Auto add shortcode to new shots CPT
			add_filter ( 'wp_insert_post_data', array (
					$this,
					'modify_shot_content' 
			), '99', 2 );
			
			$exportEnabled = get_option ( 'chiefed_xml_exports_enabled' );
			if (! $exportEnabled) {
				$msg = "->->-> Export to XML : DISABLED";
				CHIEFED_UTILS::getLogger ()->warn ( $msg );
				CHIEFED_UTILS::getLogger()->debug  ( $msg );
			} else {
				$msg = "->->-> Export to XML : ENABLED";
				CHIEFED_UTILS::getLogger ()->debug ( $msg );
				// CHIEFED_UTILS::getLogger()->debug  ( $msg );
				if ($this->preDP_abs_path == null) {
					$msg = $this->createPreDP_Directory ();
					CHIEFED_UTILS::getLogger ()->debug ( $msg );
					// CHIEFED_UTILS::getLogger()->debug  ( $msg );
				}
				add_action ( $actionName, array (
						$this,
						'chiefed_export_to_xml' 
				), 10, 2 );
				add_action ( $actionName, array (
						$this,
						'chiefed_export_to_xmll' 
				), 10, 1 );
				CHIEFED_UTILS::getLogger ()->debug ( $actionName . ' linked to chiefed_export_to_xml' );
			}
			
			CHIEFED_UTILS::getLogger ()->debug ( "add init callbacks" );
			add_action ( 'init', array (
					$this,
					'register_cpt_pre_desktop_publishing' 
			) );
			add_action ( 'init', array (
					$this,
					'chiefed_register_taxonomy_periodical' 
			) );
			add_action ( 'init', array (
					$this,
					'register_cpt_periodical_shot' 
			) );
			
			// extract images callback
			
			add_action('wp_ajax_chiefed_extract_images_to_gallery', array(
					$this,
					'chiefed_extract_all_images_from_source_to_gallery'
			));
			add_action('wp_ajax_nopriv_chiefed_extract_images_to_gallery', array(
					$this,
					'chiefed_extract_all_images_from_source_to_gallery'
			));
			
			
			
		}
		static function chiefed_install() {
			$msg = "+=+=+ chiefed_install";
			CHIEFED_UTILS::getLogger ()->info ( $msg );
			CHIEFED_UTILS::getLogger()->debug  ( $msg );
		}
		static function chiefed_uninstall() {
			$msg = "+=+=+ chiefed_UNinstall";
			CHIEFED_UTILS::getLogger ()->info ( $msg );
			CHIEFED_UTILS::getLogger()->debug  ( $msg );
			$delete_all_at_uninstall = false;
			if ($delete_all_at_uninstall) {
				$cpt_posts_data = array (
						array (
								'post' => get_posts ( array (
										'numberposts' => - 1,
										'post_type' => CHIEFED_PRE_DESKTOP_PUBLISHING_CPT,
										'post_status' => 'any' 
								) ) 
						),
						array (
								'post' => get_posts ( array (
										'numberposts' => - 1,
										'post_type' => CHIEFED_PERIODICAL_SHOT_CPT,
										'post_status' => 'any' 
								) ) 
						) 
				);
				
				/**
				 * Delete post.
				 */
				foreach ( $cpt_posts_data as $post_item ) {
					
					foreach ( $post_item ['post'] as $post ) {
						$msg = "--- deleted post " . $post->ID;
						CHIEFED_UTILS::getLogger ()->debug ( $msg );
						CHIEFED_UTILS::getLogger()->debug  ( $msg );
						wp_delete_post ( $post->ID, true );
					}
				}
				
				/**
				 * Delete Meta.
				 */
				/*
				 * require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-vcpm-meta.php';
				 * $meta_data = Vcpm_Meta::vcpm_meta_box();
				 */
				// foreach ($meta_data as $meta_item) {
				$meta_item = Chief_Editor_Meta_Boxes::getMetaFields ();
				foreach ( $meta_item as $field ) {
					$msg = "--- deleted post meta " . $field ['id'];
					CHIEFED_UTILS::getLogger ()->debug ( $msg );
					CHIEFED_UTILS::getLogger()->debug  ( $msg );
					delete_post_meta_by_key ( $field ['id'] );
				}
			} else {
				$msg = __ ( 'Leave all plugin posts and meta datas' );
				CHIEFED_UTILS::getLogger ()->info ( $msg );
			}
		}
		function convert_to_srgb($file) {
			// usefull if receiving CMJN image files
			
			// $file['name'] = 'wordpress-is-awesome-' . $file['name'];
			// CHIEFED_UTILS::getLogger()->debug($file);
			if (! extension_loaded ( 'imagick' )) {
				// CHIEFED_UTILS::getLogger()->debug("imagick not installed :(");
			} else {
				// CHIEFED_UTILS::getLogger()->debug("imagick is installed :)");
				/*
				 * if ($jpeg->getImageColorspace() == \Imagick::COLORSPACE_CMYK) {
				 * $jpeg->transformimagecolorspace(\Imagick::COLORSPACE_SRGB);
				 * }
				 */
			}
			
			return $file;
		}
		function chiefed_extract_all_images_from_source_to_gallery() {
			CHIEFED_UTILS::getLogger ()->debug ( $_POST );
			$post_id = $_POST ['postID'];
			
			$allFoldersArray = $this->chiefed_get_path_array($post_id);
			CHIEFED_UTILS::getLogger ()->debug ( $allFoldersArray );
			
			$imagesSourceFileArray = get_post_meta ( $post_id, 'wp_custom_attachment', true );
			CHIEFED_UTILS::getLogger ()->debug ( $imagesSourceFileArray );
			$imagesSourceFile = $imagesSourceFileArray ['file'];
			
			CHIEFED_UTILS::getLogger ()->info ( "==== EXTRACT ALL IMAGES ====" );
			
			$imagesArray = Chiefed_Image_Processor::extractImages ( $imagesSourceFile, $allFoldersArray ['temp'], $allFoldersArray ['images'] );
			CHIEFED_UTILS::getLogger ()->debug ( $imagesArray );
			$imagesExtractedSize = count($imagesArray) . ' '.__('images extracted');
			$imgPath = trailingslashit ( realpath ( $allFoldersArray ['images'] ) );
			
			CHIEFED_UTILS::getLogger ()->info ( "==== ATTACH ALL IMAGES AS GALLERY ====" );
			$imagesAttachedSize = Chiefed_Image_Processor::attachImagesToPostAndAppendAsGallery($post_id, $imgPath);
			CHIEFED_UTILS::getLogger ()->debug ( $imagesAttachedSize );
			
			echo $imagesExtractedSize;
			echo $imagesAttachedSize;
			wp_die();
		}
		/**
		 * Attaches the specified template to the page identified by the specified name.
		 *
		 * @params    $page_name        The name of the page to attach the template.
		 * @params    $template_path    The template's filename (assumes .php' is specified)
		 *
		 * @returns   -1 if the page does not exist; otherwise, the ID of the page.
		 */
		function attach_template_to_page($page_id, $template_file_name) {
			
			// Look for the page by the specified title. Set the ID to -1 if it doesn't exist.
			// Otherwise, set it to the page's ID.
			/*
			 * $page = get_page_by_title( $page_name, OBJECT, 'page' );
			 * $page_id = null == $page ? -1 : $page->ID;
			 */
			// Only attach the template if the page exists
			if (- 1 != $page_id) {
				update_post_meta ( $page_id, '_wp_page_template', $template_file_name );
			} // end if
			
			return $page_id;
		} // end attach_template_to_page
		function modify_shot_content($data, $postarr) {
			$postTypeName = empty ( get_option ( 'chiefed_shots_cpt_name' ) ) ? CHIEFED_PERIODICAL_SHOT_CPT : get_option ( 'chiefed_shots_cpt_name' );
			CHIEFED_UTILS::getLogger ()->debug ( "modify_shot_content :: " . $postTypeName );
			if ($data ['post_type'] != $postTypeName) {
				// return $data;
			} else {
				
				CHIEFED_UTILS::getLogger ()->debug ( $data );
				CHIEFED_UTILS::getLogger ()->debug ( $postarr );
				
				$current_post_id = $postarr ['ID'];
				$data ['post_content'] = '[chiefed_shot_single_page post_id="' . $current_post_id . '"]';
				
				// if( $data['post_type'] == 'your_custom_post_name' ) {
				$data ['comment_status'] = 1;
				// }
				$this->attach_template_to_page ( $current_post_id, 'page-templates/full-width.php' );
			}
			
			return $data; // Returns the modified data.
		}
		function register_cpt_periodical_shot($input) {
			$cptNameSingular = __ ( 'Shot', 'chief-editor' );
			$cptNamePlural = __ ( 'Shots', 'chief-editor' );
			CHIEFED_UTILS::getLogger ()->info ( "+++ register_cpt : " . $cptNamePlural );
			$labels = array (
					'name' => $cptNamePlural,
					'singular_name' => $cptNameSingular,
					'add_new' => __ ( 'Add New' ),
					'add_new_item' => __ ( 'Add' ) . ' ' . $cptNameSingular,
					'edit_item' => __ ( 'Edit' ) . ' ' . $cptNameSingular,
					'new_item' => __ ( 'New' ) . ' ' . $cptNameSingular,
					'view_item' => __ ( 'View' ) . ' ' . $cptNameSingular,
					'search_items' => __ ( 'Search' ) . ' ' . $cptNameSingular,
					'not_found' => $cptNameSingular . __ ( 'not found' ),
					'not_found_in_trash' => $cptNameSingular . __ ( 'Not found' ),
					'parent_item_colon' => __ ( 'Parent' ) . ' ' . $cptNameSingular,
					'menu_name' => $cptNamePlural 
			);
			
			$args = array (
					'labels' => $labels,
					'menu_icon' => 'dashicons-media-document',
					'hierarchical' => true,
					'description' => __ ( 'Represents a paper shot, and contains several printed articles', 'chief-editor' ),
					'supports' => array (
							'title',
							'editor',
							'excerpt',
							'author',
							'thumbnail',
							'custom-fields',
							'comments',
							'revisions',
							'page-attributes' 
					),
					'taxonomies' => array (
							'category',
							'post_tag' 
					),
					'public' => true,
					'show_ui' => true,
					'show_in_menu' => true,
					'menu_position' => 5,
					'show_in_nav_menus' => true,
					'publicly_queryable' => true,
					'exclude_from_search' => false,
					'has_archive' => false,
					'query_var' => true,
					'can_export' => true,
					'rewrite' => true,
					'capability_type' => 'post' 
			);
			
			// CHIEFED_UTILS::getLogger()->debug("+++ Adding custom post type : " . $this->cptName);
			$postTypeName = CHIEFED_PERIODICAL_SHOT_CPT;
			// CHIEFED_UTILS::getLogger()->debug($args);
			register_post_type ( $postTypeName, $args );
		}
		function chiefed_register_taxonomy_periodical($input) {
			$taxoName = self::$taxoName;
			$singularName = __ ( 'Periodical', 'chief-editor' );
			$pluralName = __ ( 'Periodicals', 'chief-editor' );
			CHIEFED_UTILS::getLogger ()->info ( "+++ register taxonomy : " . $pluralName );
			$labels = [ 
					'name' => $pluralName,
					'singular_name' => $singularName,
					'search_items' => __ ( 'Search' ) . ' ' . $pluralName,
					'all_items' => __ ( 'All' ) . ' ' . $pluralName,
					'parent_item' => __ ( 'Parent' ) . ' ' . $singularName,
					'parent_item_colon' => __ ( 'Parent' ) . ':' . $singularName,
					'edit_item' => __ ( 'Edit' ) . ' ' . $singularName,
					'update_item' => __ ( 'Update' ) . ' ' . $singularName,
					'add_new_item' => __ ( 'Add New' ) . ' ' . $singularName,
					'new_item_name' => __ ( 'New' ) . ' ' . $singularName . ' ' . __ ( 'Name' ),
					'menu_name' => $pluralName 
			];
			$args = [ 
					'hierarchical' => true, // make it hierarchical (like categories)
					'labels' => $labels,
					'show_ui' => true,
					'show_admin_column' => true,
					'query_var' => true,
					'rewrite' => [ 
							'slug' => $taxoName 
					] 
			];
			
			register_taxonomy ( $taxoName, [ 
					CHIEFED_PRE_DESKTOP_PUBLISHING_CPT,
					CHIEFED_PERIODICAL_SHOT_CPT 
			], $args );
			// CHIEFED_UTILS::getLogger()->debug($taxoName . ' taxo registered');
		}
		function chiefed_set_item_color($item, $post) {
			$postID = $post->ID;
			
			// set post color
			$currentStatus = get_post_meta ( $postID, 'informations_complmentaires_' . 'article-status', true );
			// CHIEFED_UTILS::getLogger()->debug($postID . " has status " . $currentStatus);
			$status_color = Chief_Editor_Meta_Boxes::get_color_for_status ( $currentStatus );
			// CHIEFED_UTILS::getLogger()->debug($currentStatus . " => " . $status_color);
			$item ["color"] = $status_color;
			
			// set post date : if no parent shot set, keep date of post, else get parent shot publication date
			$parentShotId = get_post_meta ( $postID, 'informations_complmentaires_' . 'inside-shot', true );
			if ($parentShotId > 0) {
				$shotDate = get_the_time ( 'U', $parentShotId );
				// CHIEFED_UTILS::getLogger()->debug($postID . " parent shot has date " . $shotDate);
				$date = new DateTime ();
				$date->setTimestamp ( $shotDate );
				// date_add($date, date_interval_create_from_date_string('10 days'));
				// $date = new DateTime('2000-01-01');
				$date->add ( new DateInterval ( 'PT1H' ) );
				// CHIEFED_UTILS::getLogger()->debug($postID . " parent shot has end date " . $shotDate);
				$item ["start"] = date ( 'Y-m-d\TH:i:s', $shotDate );
				$item ["end"] = date ( 'Y-m-d\TH:i:s', $date->getTimestamp () );
			}
			return $item;
		}
		function predp_custom_post_status() {
			CHIEFED_UTILS::getLogger ()->debug ( Chief_Editor_Meta_Boxes::$printStatusesToAdd );
			if (! Chief_Editor_Meta_Boxes::$printStatusesToAdd) {
				CHIEFED_UTILS::getLogger ()->debug ( "empty statuses" );
				
				return;
			}
			foreach ( Chief_Editor_Meta_Boxes::$printStatusesToAdd as $newStatus ) {
				
				register_post_status ( $newStatus ['name'], array (
						'label' => _x ( $newStatus ['label'], 'post status label', 'chief-editor' ),
						'public' => true,
						// 'post_type' => array($this->cptName ),
						'label_count' => _n_noop ( $newStatus ['label'] . ' <span class="count">(%s)</span>', $newStatus ['label'] . ' <span class="count">(%s)</span>' ),
						'show_in_admin_all_list' => true,
						'show_in_admin_status_list' => true 
				) );
			}
		}
		function insert_using_custom_status($data = array(), $postarr = array()) {
			CHIEFED_UTILS::getLogger ()->debug ( "-------- insert_using_custom_status ---------" );
			/*
			 * if ( empty( $postarr['publish'] ) ) {
			 * CHIEFED_UTILS::getLogger()->debug("-------- empty publish ---------");
			 * CHIEFED_UTILS::getLogger()->debug($postarr);
			 * return $data;
			 * }
			 */
			CHIEFED_UTILS::getLogger ()->debug ( "--------------------------------------------" );
			CHIEFED_UTILS::getLogger ()->debug ( $postarr );
			CHIEFED_UTILS::getLogger ()->debug ( "--------------------------------------------" );
			CHIEFED_UTILS::getLogger ()->debug ( $data );
			CHIEFED_UTILS::getLogger ()->debug ( "--------------------------------------------" );
			if ($this->cptName !== $data ['post_type']) {
				CHIEFED_UTILS::getLogger ()->debug ( "-------- not a " . $this->cptName . " but a " . $data ['post_type'] . " ---------" );
				return $data;
			}
			
			$statusNames = array ();
			foreach ( Chief_Editor_Meta_Boxes::$printStatusesToAdd as $status ) {
				$statusNames [] = $status ['name'];
			}
			CHIEFED_UTILS::getLogger ()->debug ( $statusNames );
			
			if (! empty ( $postarr ['post_status'] ) && in_array ( $postarr ['post_status'], $statusNames, true )) {
				$data ['post_status'] = sanitize_key ( $postarr ['post_status'] );
			} else {
				$data ['post_status'] = $this->defaultStatus;
			}
			return $data;
		}
		function predp_append_post_status_list() {
			global $post;
			$complete = '';
			$label = '';
			if ($post->post_type == CHIEFED_PRE_DESKTOP_PUBLISHING_CPT) {
				
				foreach ( Chief_Editor_Meta_Boxes::$printStatusesToAdd as $newStatus ) {
					
					if ($post->post_status == $newStatus ['name']) {
						$complete = ' selected="selected"';
						$label = '<span id="post-status-display"> ' . $newStatus ['label'] . '</span>';
					}
					echo '
          <script>
          jQuery(document).ready(function($){
               $("select#post_status").append(\'<option value="' . $newStatus ['name'] . '" ' . $complete . '>' . $newStatus ['label'] . '</option>\');
               $(".misc-pub-section label").append("' . $label . '");
          });
          </script>
          ';
				}
			}
		}
		function createPreDP_Directory() {
			$msg = '';
			$relative_path = get_option ( 'chiefed_xml_exports_dir' );
			if (! empty ( $relative_path )) {
				$this->preDP_directory_name = get_option ( 'chiefed_xml_exports_dir' ); // ) ?
				                                                                        // get_option('chiefed_xml_exports_dir')
				                                                                        // :
				                                                                        // basename(get_option('chiefed_xml_abs_exports_dir'));
				$upload = wp_upload_dir ();
				$upload_dir = $upload ['basedir'] . '/' . $this->preDP_directory_name;
			} else {
				
				CHIEFED_UTILS::getLogger ()->debug ( "Empty output dir!" . $relative_path );
				/*
				 * $this->preDP_directory_name = basename(get_option('chiefed_xml_abs_exports_dir'));
				 * $upload = wp_upload_dir();
				 * $upload_dir = get_option('chiefed_xml_abs_exports_dir');
				 */
			}
			
			// CHIEFED_UTILS::getLogger()->debug($upload_dir);
			// CHIEFED_UTILS::getLogger()->debug(realpath($upload_dir));
			$this->preDP_abs_path = trailingslashit ( $upload_dir );
			// CHIEFED_UTILS::getLogger ()->debug ( "preDP path: " . $this->preDP_abs_path );
			if (! is_dir ( $this->preDP_abs_path )) {
				if (mkdir ( $this->preDP_abs_path, 0755 )) {
					$msg = ':) +++ PreDP directory created : ' . $this->preDP_abs_path;
				} else {
					$msg = ':( ### Cannot create PreDP directory : ' . $this->preDP_abs_path;
				}
				
				// self::getLogger()->info('+++ PreDP directory created : ' . $this->images_abs_path);
			} else {
				// self::getLogger()->trace('+++ PreDP directory already exists : ' . $this->images_abs_path);
				$msg = ':) folder already exists : ' . $upload_dir;
			}
			// CHIEFED_UTILS::getLogger()->debug($msg);
			return $msg;
		}
		function register_cpt_pre_desktop_publishing($input) {
			// CHIEFED_UTILS::getLogger()->debug("register_cpt_pre_desktop_publishing");
			$preDPSingular = __ ( 'PRINT Post', 'chief-editor' );
			$preDPPlural = __ ( 'PRINT Posts', 'chief-editor' );
			
			CHIEFED_UTILS::getLogger ()->debug ( "+++ register CPT : " . $preDPPlural );
			$labels = array (
					'name' => $preDPPlural, // __ ( 'Pre DPs', 'chief-editor' ),
					'singular_name' => $preDPSingular, // __ ( 'Pre DP', 'chief-editor' ),
					'add_new' => __ ( 'Add New' ),
					'add_new_item' => __ ( 'Add New' ) . ' ' . $preDPSingular,
					'edit_item' => __ ( 'Edit' ) . ' ' . $preDPSingular,
					'new_item' => __ ( 'New' ) . ' ' . $preDPSingular,
					'view_item' => __ ( 'View' ) . ' ' . $preDPSingular,
					'search_items' => __ ( 'Search' ) . ' ' . $preDPSingular,
					'not_found' => __ ( 'not found' ) . ' ' . $preDPSingular,
					'not_found_in_trash' => __ ( 'Not found' ) . ' ' . $preDPSingular,
					'parent_item_colon' => __ ( 'Parent:' ) . ' ' . $preDPSingular,
					'menu_name' => $preDPPlural 
			);
			
			$args = array (
					'labels' => $labels,
					'menu_position' => 5,
					// 'menu-icon' => 'dashicons-paperclip',
					'menu_icon' => 'dashicons-portfolio',
					'hierarchical' => true,
					'description' => __ ( 'Just gather all author documents before generating necessary files for Desktop Publishing Unit', 'chief-editor' ),
					'supports' => array (
							'title',
							'editor',
							'excerpt',
							'author',
							'thumbnail',
							'custom-fields',
							'comments',
							'revisions',
							'page-attributes' 
					),
					'taxonomies' => array (
							'category',
							'post_tag' 
					),
					'public' => true,
					'show_ui' => true,
					'show_in_menu' => true,
					'show_in_nav_menus' => true,
					'publicly_queryable' => true,
					'exclude_from_search' => false,
					'has_archive' => false,
					'query_var' => true,
					'can_export' => true,
					'rewrite' => true,
					'capability_type' => 'post' 
			);
			
			$postTypeName = CHIEFED_PRE_DESKTOP_PUBLISHING_CPT;
			// CHIEFED_UTILS::getLogger()->debug($args);
			register_post_type ( $postTypeName, $args );
			// CHIEFED_UTILS::getLogger()->debug("+++ registered CPT : ".$postTypeName);
		}
		
		/*
		 * function shortcodes_to_xml_tags($content)
		 * {
		 * if( class_exists( 'WPSE_CollectShortcodeAttributes' ) )
		 * {
		 * $o = new WPSE_CollectShortcodeAttributes;
		 * $out = $o->init( $shortcode = 'gallery', $content )->get_attributes();
		 * print_r( $out );
		 * }
		 * }
		 */
		function shortcodes_to_xml_tags($content) {
			$result = array ();
			
			if (class_exists ( 'ChiefEditor_CollectShortcodeAttributes' )) {
				$o = new ChiefEditor_CollectShortcodeAttributes ();
				$out = $o->init ( $shortcode = 'gallery', $content )->get_attributes ();
				CHIEFED_UTILS::getLogger ()->debug ( $out );
				foreach ( $out as $image_gallery ) {
					CHIEFED_UTILS::getLogger ()->debug ( $image_gallery );
					$ids = $image_gallery ['ids'];
					CHIEFED_UTILS::getLogger ()->debug ( $ids );
					// *explode()
					$idsArray = explode ( ',', $ids );
					CHIEFED_UTILS::getLogger ()->debug ( $idsArray );
					if (is_array ( $idsArray )) {
						foreach ( $idsArray as $image_id ) {
							// wp_get_attachment_image ( int $attachment_id, string|array $size = 'thumbnail', bool
							// $icon = false, string|array $attr = '' )
							// wp_get_attachment_image( get_the_ID(), array('700', '600'), "", array( "class" =>
							// "img-responsive" ) );
							$image = get_post ( $image_id );
							$image_title = $image->post_title;
							$image_caption = $image->post_excerpt;
							$result [$image_id] ['filename'] = $image_title;
							$result [$image_id] ['caption'] = $image_caption;
							CHIEFED_UTILS::getLogger ()->debug ( $image_id . ' - ' . $image_title . ' - ' . $image_caption );
						}
					}
				}
			}
			return $result;
		}
		static function getTermsFor($postID, $taxonomy, $path_style = false) {
			$result = '';
			$terms = get_the_terms ( $postID, $taxonomy );
			
			if ($terms && ! is_wp_error ( $terms )) {
				$periodical_links = array ();
				foreach ( $terms as $term ) {
					
					$periodical_links [] = $path_style ? $term->slug : $term->name;
				}
				$result = join ( "_", $periodical_links );
			}
			return $result;
		}
		static function getPeriodicalName($postID, $path_style = false) {
			$result = '';
			
			$parentShotId = get_post_meta ( $postID, 'informations_complmentaires_' . 'inside-shot', true );
			// CHIEFED_UTILS::getLogger()->debug("getPeriodicalName::Inside shot : ".$parentShotId);
			if (is_numeric ( $parentShotId ) && $parentShotId > 0) {
				$parentPeriodicalName = self::getTermsFor ( $parentShotId, self::$taxoName, $path_style );
				$result = $parentPeriodicalName;
				// CHIEFED_UTILS::getLogger()->debug("Parent : ".$result);
			} else {
				$result = self::getTermsFor ( $postID, self::$taxoName, $path_style );
			}
			// CHIEFED_UTILS::getLogger()->debug('getPeriodicalName : '.$result);
			
			return $result;
		}
		static function getPeriodicalShot($postID, $path_style = false) {
			$result = '';
			
			$parentShotId = get_post_meta ( $postID, 'informations_complmentaires_' . 'inside-shot', true );
			// CHIEFED_UTILS::getLogger()->debug("getPeriodicalShot::Inside shot : ".$parentShotId);
			if (is_numeric ( $parentShotId ) && $parentShotId > 0) {
				if ($path_style) {
					$post = get_post ( $parentShotId );
					$result = $post->post_name;
				} else {
					$result = get_the_title ( $parentShotId );
				}
				/*
				 * $shotTitle = get_the_title($parentShotId);
				 * $result = $shotTitle;
				 */
				// CHIEFED_UTILS::getLogger()->debug("Parent : ".$result);
			} /*
			   * else {
			   * $result = self::getTermsFor($postID, self::$taxoName);
			   * }
			   */
			// CHIEFED_UTILS::getLogger()->debug('getPeriodicalShot : '.$result);
			
			return $result;
		}
		function getCategoryName($postID, $path_style = false) {
			$result = '';
			$catArray = get_the_category ( $postID );
			$category_links = array ();
			foreach ( $catArray as $cd ) {
				CHIEFED_UTILS::getLogger ()->debug ( $cd );
				$category_links [] = $path_style ? $cd->slug : $cd->name;
			}
			$result = join ( "_", $category_links );
			
			return $result;
		}
		function buildAbsoluteFilename($postID, $path, $filename, $ext) {
			// // start with :
			// [nom_du_periodique]/[categorie]/[annee]/[mois]/[n_d_f1_n_d_f2]/[titre_de_l_article]/titre.xml
			CHIEFED_UTILS::getLogger ()->debug ( "buildAbsoluteFilename::" . $postID );
			
			$directory = trailingslashit ( $path );
			
			$perioName = self::getPeriodicalName ( $postID, true );
			$perioShot = self::getPeriodicalShot ( $postID, true );
			
			$pathArray = array (
					"periodical" => $perioName,
					"shot" => $perioShot,
					'category' => $this->getCategoryName ( $postID, true ),
					"year" => date ( "Y" ),
					"month" => date ( "m" ) 
			);
			
			CHIEFED_UTILS::getLogger ()->debug ( $pathArray );
			$pathArray = array_filter ( $pathArray );
			$pathArray = array_unique ( $pathArray );
			CHIEFED_UTILS::getLogger ()->debug ( $pathArray );
			
			$directory .= implode ( "/", $pathArray );
			
			/*
			 * $directory .= '/' . self::getPeriodicalName($postID);
			 * $directory .= '/' . self::getPeriodicalShot($postID);
			 * $directory .= '/' . $this->getCategoryName($postID);
			 * $directory .= '/' . date("Y");
			 * $directory .= '/'.date("m");
			 */
			// $directory .= '/'.date("d");
			
			$directory = trailingslashit ( $directory );
			// If the directory doesn't already exists.
			if (! is_dir ( $directory )) {
				// Create our directory.
				mkdir ( $directory, 0755, true );
			}
			
			$result = $directory . $filename . $ext;
			CHIEFED_UTILS::getLogger ()->debug ( "->->-> Export XML : " . $result );
			
			$resultArray = array (
					"abs_filename" => $result,
					"sub_items" => $pathArray 
			);
			
			CHIEFED_UTILS::getLogger ()->debug ( $resultArray );
			
			return $resultArray;
		}
		function createArticleFolders($abs_file_path) {
			$basename = basename ( $abs_file_path, ".xml" );
			$sanitized = sanitize_file_name ( $basename );
			
			$dir = dirname ( $abs_file_path );
			
			$directory = $dir . '/' . $sanitized;
			
			if (! is_dir ( $directory )) {
				// Create our directory.
				mkdir ( $directory, 0755, true );
			}
			
			return $directory;
		}

		function sendNotificationToManager($userIDInCharge, $postID, $extra_search = array(), $extra_replace = array()) {
			
			/*
			 * $blog_url = get_site_url($blogID);
			 * // get post unique URL
			 * switch_to_blog($blogID);
			 */
			$current_post = get_post ( $postID );
			
			$post_title = $current_post->post_title;
			$permalink = get_permalink ( $postID );
			$post_author_id = $current_post->post_author;
			
			// get author email
			$user_info = get_userdata ( $userIDInCharge );
			if ($user_info) {
				$user_login = $user_info->user_login;
				$user_displayname = $user_info->display_name;
				$user_email = $user_info->user_email;
			} else {
				CHIEFED_UTILS::getLogger ()->error ( "cannot find user data for " . $userIDInCharge );
				return false;
			}
			$recipients_array = array ();
			// build mail content with std text
			$recipients_array [] = $user_email;
			
			$current_user = wp_get_current_user ();
			if ($current_user instanceof WP_User) {
				$recipients_array [] = $current_user->user_email;
				CHIEFED_UTILS::getLogger ()->debug ( 'Adding current user to email recipient : ' . $current_user->user_email );
			}
			
			$recipients_array = array_merge ( $recipients_array, explode ( ',', get_site_option ( 'email_recipients' ) ) );
			
			$multiple_to_recipients = $user_email . ',' . get_site_option ( 'email_recipients' );
			
			$recipients_array = array_unique ( $recipients_array );
			CHIEFED_UTILS::getLogger ()->debug ( $recipients_array );
			// CHIEFED_UTILS::getLogger()->debug("-------------");
			$recipients_array = array_values ( array_filter ( $recipients_array ) );
			// CHIEFED_UTILS::getLogger()->debug("-------------");
			CHIEFED_UTILS::getLogger ()->debug ( $recipients_array );
			// CHIEFED_UTILS::getLogger()->debug("-------------");
			$multiple_to_recipients = implode ( ',', $recipients_array );
			CHIEFED_UTILS::getLogger ()->debug ( 'All recipients of ready to build email notification : ' . $multiple_to_recipients );
			
			$msg_object = __ ( "To be build", 'chief-editor' ) . ' : ' . $post_title;
			
			// add other email recipients
			$sender_email = $current_user->user_email; // get_site_option('sender_email');
			$sender_name = $current_user->display_name; // get_site_option('sender_name');
			
			if (empty ( $sender_email ) || empty ( $sender_name )) {
				$message_to_user = __ ( "Please fill in sender name and email in network settings", 'chief-editor' );
				CHIEFED_UTILS::getLogger ()->debug ( $message_to_user );
				echo $message_to_user;
				return;
			}
			
			// send email to recipents
			$headers [] = "From: " . $sender_name . " <" . $sender_email . ">";
			$headers [] = "Content-type: text/html";
			
			$search = array (
					'/%username%/',
					'/%userlogin%/',
					'/%useremail%/',
					'/%postlink%/',
					'/%posttitle%/',
					'/%sendername%/' // '/%blogurl%/',
						                 // '/%n%/'
			);
			
			$search = array_merge ( $search, $extra_search );
			
			$replace = array (
					$user_displayname,
					$user_login,
					($user_email == "" ? "no email" : $user_email),
					$permalink,
					$post_title,
					$current_user->display_name // $blog_url,
						                            // "\n"
			);
			$replace = array_merge ( $replace, $extra_replace );
			$msg_content = preg_replace ( $search, $replace, get_option ( 'chiefed_manager_email_template' ) );
			$msg_content = stripslashes_deep ( $msg_content );
			$success = wp_mail ( $recipients_array, $msg_object, $msg_content, $headers );
			
			// send confirmation for ajax callback
			$message_to_user = $success ? __ ( 'Email sent successfully', 'chief-editor' ) . ' ' . __ ( 'to', 'chief-editor' ) . "\n" . $multiple_to_recipients : __ ( 'Problem sending email...', 'chief-editor' ) . "\n" . $multiple_to_recipients . "\n" . $msg_object . "\n" . $msg_content . "\n" . "From " . $sender_name . "<" . $sender_email . ">";
			
			CHIEFED_UTILS::getLogger ()->debug ( $message_to_user );
			return $success;
		}
		public function chiefed_export_to_xmll($id) {
			CHIEFED_UTILS::getLogger ()->info ( "****** chiefed_export_to_xmll : " . $id );
		}
		
		public function chiefed_get_path_array($post_id ){
			
			$post = get_post($post_id);
			$title = htmlspecialchars_decode ( $post->post_title );
			$filename = sanitize_file_name ( $title );
			$abs_file_path_array = $this->buildAbsoluteFilename ( $post_id , $this->preDP_abs_path, $filename, ".xml" );
			$abs_file_path = $abs_file_path_array ['abs_filename'];
			CHIEFED_UTILS::getLogger ()->debug ( $abs_file_path );
			$writeTo = $this->createArticleFolders ( $abs_file_path );
			$abs_file_path = $writeTo . '/' . $filename . ".xml";
			$imagesFolderArray = Chiefed_Image_Processor::createImagesSubFolder ( $writeTo );
			CHIEFED_UTILS::getLogger ()->debug ( $abs_file_path );
			return array_merge($imagesFolderArray,$abs_file_path_array);
		}
		
		public function chiefed_export_to_xml($ID, $post) {
			CHIEFED_UTILS::getLogger ()->info ( "chiefed_export_to_xml : " . $ID );
			
			// check complete path with PAO
			$exportEnabled = get_option ( 'chiefed_xml_exports_enabled' );
			CHIEFED_UTILS::getLogger ()->debug ( $exportEnabled );
			if (! $exportEnabled) {
				CHIEFED_UTILS::getLogger ()->debug ( "->->-> Export to XML : DISABLED" );
				return;
			}
			$title = htmlspecialchars_decode ( $post->post_title );
			CHIEFED_UTILS::getLogger ()->debug ( "->->-> Export to XML : " . $title );
			$allAuthors = array ();
			if (function_exists ( 'get_coauthors' )) {
				
				$authorUsers = get_coauthors ( $ID );
				// CHIEFED_UTILS::getLogger()->debug($authorUsers);
				foreach ( $authorUsers as $authorUser ) {
					$authorId = $authorUser->ID;
					$name = $authorUser->display_name; // get_the_author_meta( 'display_name', $authorId );
					$email = $authorUser->user_email; // get_the_author_meta( 'user_email', $authorId );
					$allAuthors [$authorId] = array (
							"name" => $name,
							"email" => $email 
					);
				}
			} else {
				CHIEFED_UTILS::getLogger ()->debug ( "Single author" );
				$authorId = $post->post_author; /* Post author ID. */
				$name = get_the_author_meta ( 'display_name', $authorId );
				$email = get_the_author_meta ( 'user_email', $authorId );
				$allAuthors [$authorId] = array (
						"name" => $name,
						"email" => $email 
				);
			}
			
			// coauthors_posts_links();
			
			$postContent = htmlspecialchars ( $post->post_content );
			if (empty($postContent)){
				CHIEFED_UTILS::getLogger ()->error ( "Empty post content" );
			}
			$excerpt = get_the_excerpt ( $ID );
			$content = strip_shortcodes ( $postContent );
			// informations_complmentaires_images-captions
			$imagesCaptions = get_post_meta ( $ID, 'informations_complmentaires_' . 'images-captions', true );
			if (empty($imagesCaptions)){
				CHIEFED_UTILS::getLogger ()->warn ( "Empty image captions" );
			} else {
				CHIEFED_UTILS::getLogger ()->debug ( $imagesCaptions );
			}
			$permalink = get_permalink ( $ID );
			$edit = get_edit_post_link ( $ID, '' );
			
			$shot = get_post_meta ( $ID, 'informations_complmentaires_' . 'inside-shot', true );
			$biblio = get_post_meta ( $ID, 'informations_complmentaires_' . 'bibliographie', true );
			$quiz = get_post_meta ( $ID, 'informations_complmentaires_' . 'quiz', true );
			$thanks = get_post_meta ( $ID, 'informations_complmentaires_' . 'thanks', true );
			$interet = get_post_meta ( $ID, 'informations_complmentaires_' . 'lien-d-intrt', true );
			$imagesNumber = get_post_meta ( $ID, 'informations_complmentaires_' . 'images_number', true );
			$infosForPAO = get_post_meta ( $ID, 'informations_complmentaires_' . 'deskpub_informations', true );
			$maxNbPages = get_post_meta ( $ID, 'informations_complmentaires_' . 'max_nb_of_pages', true );
			
			$xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?><article></article>
XML;
			$exportXML = new SimpleXMLElement ( $xmlstr );
			CHIEFED_UTILS::getLogger ()->debug ( $exportXML->asXML () );
			// $newArticles = $exportXML->addChild('articles');
			// $newArticle = $exportXML->addChild('article');
			$authors = $exportXML->addChild ( 'authors' );
			foreach ( $allAuthors as $authorId => $authorData ) {
				$authorNode = $authors->addChild ( 'author' );
				$authorNode->addChild ( 'id', $authorId );
				$authorNode->addChild ( 'display_name', $authorData ['name'] );
				$authorNode->addChild ( 'user_email', $authorData ['email'] );
			}
			
			$exportXML->addChild ( 'title', $title );
			$exportXML->addChild ( 'content', $content );
			$exportXML->addChild ( 'excerpt', $excerpt );
			$exportXML->addChild ( 'biblio', $biblio );
			$exportXML->addChild ( 'quiz', $quiz );
			$exportXML->addChild ( 'thanks', $thanks );
			
			$exportXML->addChild ( 'interet', $interet );
			$exportXML->addChild ( 'images_captions', $imagesCaptions );
			CHIEFED_UTILS::getLogger ()->debug ( "-------- CAPTIONS ----------" );
			CHIEFED_UTILS::getLogger ()->trace ( $imagesCaptions );
			$allCaptions = array ();
			$captionIdx = 1;
			foreach ( preg_split ( "/((\r?\n)|(\r\n?))/", $imagesCaptions ) as $line ) {
				// do stuff with $line
				CHIEFED_UTILS::getLogger()->trace($line);
				$captionPattern = '/^\d+\.|^\d+\s\.|^\d+\-|^\d+\s\-/';
				if (preg_match ( $captionPattern, $line, $matches ) === 1) {
					// line starts with number
					// int preg_match ( string $pattern , string $subject [, array &$matches [
					CHIEFED_UTILS::getLogger ()->debug ( "Starts with a number : " . $line );
					// CHIEFED_UTILS::getLogger()->debug($matches);
					$img_number = trim ( trim ( trim ( trim ( reset ( $matches ) ), '.' ), '-' ) );
					// $caption = str_replace();
					$caption = trim ( preg_replace ( $captionPattern, '', $line ) );
					if (empty($caption)){
						continue;
					}
					$allCaptions [$img_number] = $caption;
				} else {
					$caption = trim($line);
					if (empty($caption)){
						continue;
					}
					$allCaptions [$captionIdx] = $caption;
				}
				$captionIdx++;
			}
			CHIEFED_UTILS::getLogger ()->debug ( $allCaptions );
			
			CHIEFED_UTILS::getLogger ()->debug ( "#### Create Directories..." );
			$allFoldersArray = $this->chiefed_get_path_array($ID);
			
			
			// create dirs
			/*
			$filename = sanitize_file_name ( $title );
			$abs_file_path_array = $this->buildAbsoluteFilename ( $ID, $this->preDP_abs_path, $filename, ".xml" );
			$abs_file_path = $abs_file_path_array ['abs_filename'];			
			CHIEFED_UTILS::getLogger ()->debug ( $abs_file_path );
			$writeTo = $this->createArticleFolders ( $abs_file_path );
			$abs_file_path = $writeTo . '/' . $filename . ".xml";
			$imagesFolderArray = Chiefed_Image_Processor::createImagesSubFolder ( $writeTo );
			CHIEFED_UTILS::getLogger ()->debug ( $abs_file_path );
			*/
			// images processing
			
			// extract and add to XML
			
			$imagesTag = $exportXML->addChild ( 'images' );
			$export_images_from_attached_document = true;
			if ($export_images_from_attached_document) {
				$imagesSourceFileArray = get_post_meta ( $ID, 'wp_custom_attachment', true );
				CHIEFED_UTILS::getLogger ()->debug ( $imagesSourceFileArray );
				$imagesSourceFile = $imagesSourceFileArray ['file'];
				$imagesArray = Chiefed_Image_Processor::extractImages ( $imagesSourceFile, $allFoldersArray ['temp'], $allFoldersArray ['images'] );
				
				$imgPath = trailingslashit ( realpath ( $allFoldersArray ['images'] ) );
				
				//Chiefed_Image_Processor::attachImagesToPostAndAppendAsGallery($ID, $imgPath);
				
				Chiefed_Image_Processor::addImagesToXML($imagesTag,$imgPath);
				
			} else {
				$imagesList = $this->shortcodes_to_xml_tags ( $postContent );
				foreach ( $imagesList as $image ) {
					$imageTag = $imagesTag->addChild ( 'image', $imagesCaptions );
					$imageTag->addChild ( 'filename', $image ['filename'] );
					$imageTag->addChild ( 'caption', $image ['caption'] );
				}
			}
			
			// write output file
			CHIEFED_UTILS::getLogger ()->debug ( "##### write output file..." );
			
			// clean
			CHIEFED_UTILS::getLogger ()->debug ( "##### Cleaning..." );
			$dom = new DOMDocument ( "1.0", 'utf-8' );
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			$xmlFileContent = '';
			if ($exportXML instanceof SimpleXMLElement) {
				// format
				$xmlFileContent = $exportXML->asXML ();
			}
			//$xmlFileContent = htmlspecialchars ( $xmlFileContent );
			CHIEFED_UTILS::getLogger ()->trace ( $xmlFileContent );
			// $xmlFileContent = htmlspecialchars_decode( $xmlFileContent );
			CHIEFED_UTILS::getLogger ()->debug ( "##### Loading..." );
			$dom->loadXML ( $xmlFileContent );
			
			CHIEFED_UTILS::getLogger ()->trace ( $dom );
			CHIEFED_UTILS::getLogger ()->debug ( "##### Saving..." );
			$formattedXMLString = $dom->saveXML ();
			$formattedXMLString = htmlspecialchars_decode ( $formattedXMLString );
			$formattedXMLString = html_entity_decode($formattedXMLString);
			
			CHIEFED_UTILS::getLogger ()->trace ( $formattedXMLString );
			// write
			CHIEFED_UTILS::getLogger ()->debug ( "##### Writting..." );
			$writeOperation = file_put_contents ( $allFoldersArray['abs_filename'], $formattedXMLString );
			
			if ($writeOperation && chmod ( $allFoldersArray['abs_filename'], 0777 )) {
				CHIEFED_UTILS::getLogger ()->debug ( "####################################################" );
				CHIEFED_UTILS::getLogger ()->debug ( ":) Permissions OK : " . $allFoldersArray['abs_filename'] );
				CHIEFED_UTILS::getLogger ()->debug ( "####################################################" );
			} else {
				CHIEFED_UTILS::getLogger ()->debug ( "####################################################" );
				CHIEFED_UTILS::getLogger ()->debug ( ":( Permissions KO : " . $allFoldersArray['abs_filename'] );
				CHIEFED_UTILS::getLogger ()->debug ( "####################################################" );
			}
			
			$notificationEnabled = get_option ( 'chiefed_enable_notifications' );
			if ($notificationEnabled) {
				$pathItems = $allFoldersArray ['sub_items'];
				CHIEFED_UTILS::getLogger ()->debug ( "---> Send notifications for export" );
				CHIEFED_UTILS::getLogger ()->debug ( $pathItems );
				CHIEFED_UTILS::getLogger ()->debug ( $allFoldersArray );
				$optionName = CHIEFED_MANAGER_OPTION_PREFIX . $pathItems ['periodical'];
				$userIDInCharge = get_option ( $optionName );
				CHIEFED_UTILS::getLogger ()->debug ( $userIDInCharge );
				if (empty($userIDInCharge)){
					CHIEFED_UTILS::getLogger ()->warn ( "SKIPPING::Nobody is in charge for periodical ".$pathItems ['periodical'] );
					return;
				}
				$fileSysPrefix = 'file://';
				$authorsHtml = __ ( 'Author(s):' ) . '<ul>';
				foreach ( $allAuthors as $authorItem ) {
					$authorsHtml .= '<li><b>' . $authorItem ['name'] . '</b> ' . $authorItem ['email'] . '</li>';
				}
				$authorsHtml .= '</ul>';
				$numberOfPagesHtml = __ ( 'Number of pages: ', 'chief-editor' ) . '<b>' . $maxNbPages . '</b>';
				$shotHtml = __ ( 'Inside shot: ', 'chief-editor' ) . '<b>' . get_the_title ( $shot ) . '</b>';
				
				$search = array (
						'/%shot%/',
						'/%authors%/',
						'/%numberofpages%/',
						'/%xmlfile%/',
						'/%imgdir%/',
						'/%xmlfilehref%/',
						'/%imgdirhref%/' 
				);
				$replace = array (
						$shotHtml,
						$authorsHtml,
						$numberOfPagesHtml,
						$abs_file_path,
						$imagesFolderArray ['images'],
						$fileSysPrefix . $abs_file_path,
						$fileSysPrefix . $imagesFolderArray ['images'] 
				);
				
				$good = $this->sendNotificationToManager ( $userIDInCharge, $ID, $search, $replace );
				if ($good) {
					CHIEFED_UTILS::getLogger ()->info ( "InDesign connector : notification SENT :)" );
				} else {
					CHIEFED_UTILS::getLogger ()->error ( "InDesign connector : cannot send notification :(" );
				}
			} else {
				CHIEFED_UTILS::getLogger ()->warn ( "InDesign connector : notifications DISABLED" );
			}
			
		}
	}
}
class ChiefEditor_CollectShortcodeAttributes {
	private $text = '';
	private $shortcode = '';
	private $atts = array ();
	public function init($shortcode = '', $text = '') {
		$this->shortcode = esc_attr ( $shortcode );
		if (shortcode_exists ( $this->shortcode ) && has_shortcode ( $text, $this->shortcode )) {
			add_filter ( "shortcode_atts_{$this->shortcode}", array (
					$this,
					'collect' 
			), 10, 3 );
			$this->text = do_shortcode ( $text );
			remove_filter ( "shortcode_atts_{$this->shortcode}", array (
					$this,
					'collect' 
			), 10 );
		}
		return $this;
	}
	public function collect($out, $pair, $atts) {
		$this->atts [] = $atts;
		return $out;
	}
	public function get_attributes() {
		return $this->atts;
	}
}


new PreDesktopPublishing();