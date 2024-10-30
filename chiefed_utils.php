<?php
// namespace ChiefEditor;
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}

require_once __DIR__ . '/libs/vendor/autoload.php';

if (! class_exists ( 'CHIEFED_UTILS' )) {
	class CHIEFED_UTILS {
		public static $chiefed_logger = null;
		static function getAllPostsOfAllBlogsOfType($post_type = 'post', $startDate = null, $endDate = null, $excludeStatuses = array('publish','inherit','auto-draft','trash'), $args = null) {
			// global $ordered_statuses_array;
			$network_sites = get_sites ();
			$resultTable = array ();
			$result = array ();
			CHIEFED_UTILS::getLogger()->debug  ( 'Network has ' . count ( $network_sites ) . ' blog(s)' );
			$workflow = $post_type == CHIEFED_PRE_DESKTOP_PUBLISHING_CPT ? 'print' : 'web';
			$statuses = array_diff ( Chief_Editor_Meta_Boxes::get_statuses_array ( $workflow ), $excludeStatuses );
			if (empty ( $statuses )) {
				$statuses = array (
						'future',
						'draft' 
				);
			}
			CHIEFED_UTILS::getLogger()->debug  ( "Grab statuses:" );
			CHIEFED_UTILS::getLogger()->debug  ( $statuses );
			
			foreach ( $network_sites as $network_site ) {
				
				$blog_id = $network_site->blog_id;
				
				switch_to_blog ( $blog_id );
				
				CHIEFED_UTILS::getLogger()->debug  ( '#### get_posts of type ' . $post_type . ' on blog ' . $blog_id . ' with ' . count ( $statuses ) . ' statuses :' );
				// CHIEFED_UTILS::getLogger()->debug ($statuses);
				
				// FIXME misses inside shot meta query !!!
				
				// get_all_posts_for
				$allPostsOfCurrentBlog = self::get_all_posts_for ( $post_type, $excludeStatuses, $args );
				/*
				 * $allPostsOfCurrentBlog = get_posts(array(
				 * 'numberposts' => - 1,
				 * 'post_type' => $post_type,
				 * // $statusQueryLine = "AND ($wpdb->posts.post_status != '" . implode("' AND $wpdb->posts.post_status != '", $excludeStatuses) . "')";
				 * 'post_status' => $statuses,
				 * 'posts_per_page' => - 1
				 * ));
				 */
				
				CHIEFED_UTILS::getLogger()->debug  ( 'posts found : ' . count ( $allPostsOfCurrentBlog ) );
				
				foreach ( $allPostsOfCurrentBlog as $post ) {
					// CHIEFED_UTILS::getLogger()->debug ($post->ID . ' : ' . $post->post_title);
					$resultTable [$blog_id] [] = $post->ID;
				}
				
				// CHIEFED_UTILS::getLogger()->debug ($post_type . ' Before merge ' . count($result));
				$result = array_merge ( $result, $allPostsOfCurrentBlog );
				CHIEFED_UTILS::getLogger()->debug  ( 'Total is now ' . count ( $result ) );
				
				// Switch back to the main blog
				restore_current_blog ();
			}
			
			// $this->sort_posts($result,'post_status','ASC',false);
			CHIEFED_UTILS::getLogger()->debug  ( 'sorting posts found : ' . count ( $result ) );
			usort ( $result, array (
					'CHIEFED_UTILS',
					'status_cmp' 
			) );
			
			$result = array_reverse ( $result );
			/*
			 * foreach ($result as $resPost){
			 * CHIEFED_UTILS::getLogger()->debug ($resPost->ID .' : '.$resPost->post_status.' : '.$resPost->post_title);
			 * }
			 */
			return array (
					'all_posts' => $result,
					'ids_per_blog' => $resultTable 
			);
		}
		
		// custom function for comparing the data we want to sort by
		static function status_cmp($a, $b) {
			/*
			 * if ($a == $b) {
			 * return 0;
			 * }
			 * return ($a < $b) ? -1 : 1;
			 */
			$type = $a->post_type == CHIEFED_PRE_DESKTOP_PUBLISHING_CPT ? 'print' : 'web';
			$statusesToUse = $type == 'print' ? Chief_Editor_Meta_Boxes::$printStatusesToAdd : Chief_Editor_Meta_Boxes::$webStatusesToAdd;
			$result = 0;
			if ($a->post_status == $b->post_status) {
				$a_schedule_date = strtotime ( $a->post_date );
				$b_schedule_date = strtotime ( $b->post_date );
				
				if ($a_schedule_date == $b_schedule_date) {
					$result = 0;
				} else if ($a_schedule_date < $b_schedule_date) {
					$result = 1;
				} else {
					$result = - 1;
				}
				
				// return $result;
			} else {
				
				// $example = array('An example','Another example','One Example','Last example');
				$searchword = $a->post_status;
				$matches = array ();
				if (! empty ( $statusesToUse )) {
					foreach ( $statusesToUse as $k => $v ) {
						
						if ($searchword == $v ['name']) {
							// CHIEFED_UTILS::getLogger()->debug ($searchword . ' === ' . $v['name']);
							$a_key = $k;
						}
						/*
						 * if (preg_match("/\b$searchword\b/i", $v)) {
						 * $matches[$k] = $v;
						 * $a_key = $k;
						 * }
						 */
					}
					$searchword = $b->post_status;
					$matches = array ();
					foreach ( $statusesToUse as $k => $v ) {
						if ($searchword == $v ['name']) {
							// CHIEFED_UTILS::getLogger()->debug ($searchword . ' === ' . $v['name']);
							$b_key = $k;
						}
						/*
						 * if (preg_match("/\b$searchword\b/i", $v)) {
						 * $matches[$k] = $v;
						 * $b_key = $k;
						 * }
						 */
					}
				} else {
					CHIEFED_UTILS::getLogger()->error  ( 'ERROR sorting posts' );
				}
				
				if (intval ( $a_key ) == intval ( $b_key )) {
					$result = 0;
				} else if (intval ( $a_key ) < intval ( $b_key )) {
					$result = 1;
				} else {
					$result = - 1;
				}
				// $result = ($a_key > $b_key) ? 1 : - 1;
				// CHIEFED_UTILS::getLogger()->debug ($a->post_title.' : ' . $a_key . ' - '.$b->post_title.' : ' . $b_key . ' => ' . $result);
			}
			/*
			 * $a_key = array_search($a->post_status, Chief_Editor_Meta_Boxes::$ordered_statuses_array);
			 * $b_key = array_search($b->post_status, Chief_Editor_Meta_Boxes::$ordered_statuses_array);
			 */
			return $result;
		}
		static function get_post_color_from_status($post_state, $post_type) {
			// CHIEFED_UTILS::getLogger()->debug ("Get color for post status : " . $post_state);
			if (class_exists ( 'Chief_Editor_Meta_Boxes' )) {
				$type = $post_type == CHIEFED_PRE_DESKTOP_PUBLISHING_CPT ? 'print' : 'web';
				// CHIEFED_UTILS::getLogger()->debug ('get color for post '.$post_type.' : '.$type);
				$result = Chief_Editor_Meta_Boxes::get_color_for_status ( $post_state, $type );
				// CHIEFED_UTILS::getLogger()->debug ("Get color from Chief_Editor_Meta_Boxes : " . $result);
			} else {
				$futureColor = CE_SCHEDULED_COLOR;
				$draftColor = CE_DRAFT_COLOR;
				$pendingColor = CE_INPRESS_COLOR;
				$pitchColor = CE_NEW_COLOR;
				$assignedColor = CE_ASSIGNED_COLOR; // '#FFADFB';
				$inProgressColor = CE_INPRESS_SENT_COLOR; // '#f3f5b1';
				$BATColor = CE_INPRESS_COLOR; // '#69D947';
				$result = $draftColor;
				if ($post_state == 'future') {
					$result = $futureColor;
				} else if ($post_state == 'pending') {
					$result = $pendingColor;
				} else if ($post_state == 'pitch') {
					$result = $pitchColor;
				} else if ($post_state == 'assigned') {
					$result = $assignedColor;
				} else if ($post_state == 'in-progress') {
					$result = $inProgressColor;
				} else if ($post_state == 'bat') {
					$result = $BATColor;
				}
				
				CHIEFED_UTILS::getLogger()->debug  ( "Get color from legacy : " . $result );
			}
			
			return $result;
		}
		static private function findBlogIdFromPostId($postId, $postTable) {
			foreach ( $postTable as $key => $value ) {
				if (in_array ( $postId, $value )) {
					return $key;
				}
			}
		}
		static function get_the_post_thumbnail_by_blog($blog_id = NULL, $post_id = NULL, $size = 'thumbnail', $attrs = NULL) {
			global $current_blog;
			$sameblog = false;
			
			if (empty ( $blog_id ) || $blog_id == $current_blog->blog_id) {
				$blog_id = $current_blog->blog_id;
				$sameblog = true;
			}
			if (empty ( $post_id )) {
				global $post;
				$post_id = $post->ID;
			}
			if ($sameblog)
				return get_the_post_thumbnail ( $post_id, $size, $attrs );
			
			if (! self::has_post_thumbnail_by_blog ( $blog_id, $post_id ))
				return false;
			
			global $wpdb;
			// $oldblog = $wpdb->set_blog_id( $blog_id );
			switch_to_blog ( $blog_id );
			
			$blogdetails = get_blog_details ( $blog_id );
			// str_replace ( mixed $search , mixed $replace , mixed $subject [, int &$count ] )
			// echo 'Replace '.$current_blog->domain . $current_blog->path.' by '.$blogdetails->domain . $blogdetails->path.' in '.get_the_post_thumbnail( $post_id, $size, $attrs );
			$thumbcode = str_replace ( $current_blog->domain . $current_blog->path, $blogdetails->domain . $blogdetails->path, get_the_post_thumbnail ( $post_id, $size, $attrs ) );
			
			// $wpdb->set_blog_id( $oldblog );
			restore_current_blog ();
			return $thumbcode;
		}
		static function has_post_thumbnail_by_blog($blog_id = NULL, $post_id = NULL) {
			if (empty ( $blog_id )) {
				global $current_blog;
				$blog_id = $current_blog->blog_id;
			}
			if (empty ( $post_id )) {
				global $post;
				$post_id = $post->ID;
			}
			
			global $wpdb;
			$oldblog = $wpdb->set_blog_id ( $blog_id );
			
			$thumbid = has_post_thumbnail ( $post_id );
			$wpdb->set_blog_id ( $oldblog );
			return ($thumbid !== false) ? true : false;
		}
		static function get_multisite_post_edit_link($blogID, $postID) {
			switch_to_blog ( $blogID );
			
			$out = get_edit_post_link ( $postID );
			// echo 'WOW'.$edit_post_link;
			
			restore_current_blog ();
			
			return $out;
		}
		static function get_userdata_for_blog($author, $blog_id) {
			switch_to_blog ( $blog_id );
			
			$result = get_userdata ( $author );
			
			restore_current_blog ();
			return $result;
		}
		static function get_post_status_meaning_from_status($post_state) {
			$result = $post_state;
			
			return $result;
		}
		static function stringPresentInAuthorOrCoauthor($post_id, $search) {
			$post = get_post ( $post_id );
			$author = get_user_by ( 'ID', $post->post_author );
			$author_name = $author->display_name;
			
			$searchInMainAuthor = stripos ( $author_name, $search ) !== false;
			
			if ($searchInMainAuthor) {
				// $q3[] = $postID;
				return true;
			}
			
			if (function_exists ( 'coauthors' )) {
				$coauthors = get_coauthors ( $post_id );
				// $co_authors = array();
				foreach ( $coauthors as $author ) {
					$co_author_id = $author->ID;
					$current_author = get_user_by ( 'ID', $co_author_id );
					$author_name = $current_author->display_name;
					$searchInCoAuthor = stripos ( $author_name, $search ) !== false;
					if ($searchInCoAuthor) {
						
						return true;
					}
				}
			}
			
			return false;
		}
		static function get_all_posts_for($post_type, $excludeStatuses, $attr = array()) {
		    
		    $result = array ();
		    if ($attr == null){
		        CHIEFED_UTILS::getLogger()->error  ( "get_all_posts_for : no attributes");
		        $attr = array();
		        //return $result;      
		    }
		    
			extract ( $attr );
			
			
			CHIEFED_UTILS::getLogger()->debug  ( '#### get_posts of type ' . $post_type . ' on blog ' );
			CHIEFED_UTILS::getLogger()->debug  ( $attr );
			$workflow = $post_type == CHIEFED_PRE_DESKTOP_PUBLISHING_CPT ? 'print' : 'web';
			$statuses = array_diff ( Chief_Editor_Meta_Boxes::get_statuses_array ( $workflow ), $excludeStatuses );
			CHIEFED_UTILS::getLogger()->debug  ( $statuses );
			$args = null;
			if (! empty ( $inside_shot_id )) {
				
				$metaKey = 'informations_complmentaires_' . 'inside-shot';
				CHIEFED_UTILS::getLogger()->debug  ( "Finding shot articles where ". $metaKey .' is '.$inside_shot_id);
				$args = array (
						'numberposts' => - 1,
						'post_type' => $post_type,
				        'post_status' => $statuses,
						'posts_per_page' => - 1,
						'meta_query' => array (
								array (
										'key' => strval($metaKey),
										'value' => strval($inside_shot_id) 
								) 
								/*
								array (
										'key' => $metaKey,
										'value' => $inside_shot_id,
										'compare' => '=' 
								) */
						) 
				);
			} else if (! empty ( $timeframe ) || ! empty ( $search )) {
				CHIEFED_UTILS::getLogger()->debug  ( "Finding articles with timeframe and search" );
				CHIEFED_UTILS::getLogger()->debug  ( $timeframe );
				
				$args0 = array (
						'numberposts' => - 1,
						'post_type' => $post_type,
						// 'post_status' => $statuses,
				    'post_status' => $statuses,
						'posts_per_page' => - 1 
				);
				
				$args1 = array_merge ( $args0, array (
						'numberposts' => - 1,
						'fields' => 'ids',
						'date_query' => array (
								'column' => 'post_date',
								'after' => $timeframe 
						) 
				) );
				
				$q1 = get_posts ( $args1 );
				
				if ($search) {
					$searchArgs = array (
							'numberposts' => - 1,
							's' => $search,
							'fields' => 'ids' 
					);
					
					$args2 = array_merge ( $args0, $searchArgs );
					
					$q2 = get_posts ( $args2 );
					
					// search on author names as well
					$q3 = array ();
					// filter on author displayed names
					foreach ( $q1 as $key => $postID ) {
						// CHIEFED_UTILS::getLogger()->debug ($post);
						
						$stringHere = self::stringPresentInAuthorOrCoauthor ( $postID, $search );
						
						if ($stringHere) {
							$q3 [] = $postID;
						}
					}
					CHIEFED_UTILS::getLogger()->debug  ( "### TIME ###" );
					CHIEFED_UTILS::getLogger()->debug  ( $args1 );
					CHIEFED_UTILS::getLogger()->debug  ( $q1 );
					CHIEFED_UTILS::getLogger()->debug  ( "### Search s ###" );
					CHIEFED_UTILS::getLogger()->debug  ( $args2 );
					CHIEFED_UTILS::getLogger()->debug  ( $q2 );
					CHIEFED_UTILS::getLogger()->debug  ( "### Search on author name from q1 query ###" );
					CHIEFED_UTILS::getLogger()->debug  ( $q3 );
					
					$unique = array_unique ( array_merge ( $q2, $q3 ) );
					CHIEFED_UTILS::getLogger()->debug  ( "### UNIQUE ###" );
					CHIEFED_UTILS::getLogger()->debug  ( $unique );
					$intersect = array_intersect ( $q1, $unique );
				} else {
					$intersect = $q1;
				}
				
				CHIEFED_UTILS::getLogger()->debug  ( "### INTERSECT ###" );
				CHIEFED_UTILS::getLogger()->debug  ( $intersect );
				if (! empty ( $intersect )) {
					$args = array (
							'numberposts' => - 1,
							'post_type' => $post_type,
							'post__in' => $intersect,
							// 'post_status' => $statuses,
							'posts_per_page' => - 1 
					);
				} else {
					$args = null;
				}
			} else {
				CHIEFED_UTILS::getLogger()->debug  ( "Finding articles without timeframe nor search nor shot" );
				$args = array (
						'numberposts' => - 1,
						'post_type' => $post_type,
				    'post_status' => $statuses,
						// 'post_status' => $statuses,
						'posts_per_page' => - 1 
				
				);
			}
			CHIEFED_UTILS::getLogger()->debug  ( $args );
			if (null !== $args) {
				$allPostsOfCurrentBlog = get_posts ( $args );
			} else {
				$allPostsOfCurrentBlog = array ();
			}
			
			CHIEFED_UTILS::getLogger()->debug  ( "posts : " . count ( $allPostsOfCurrentBlog ) );
			
			return $allPostsOfCurrentBlog;
		}
		static function get_all_editor_items($post_type = 'post', $excludeStatuses = array('publish','inherit','auto-draft','trash'), $args = null) {
			CHIEFED_UTILS::getLogger()->debug  ( 'FETCH ALL POSTS FOR LIST: ' . $post_type );
			CHIEFED_UTILS::getLogger()->debug  ( $excludeStatuses );
			if (! is_multisite ()) {
				$rows = self::get_all_posts_for ( $post_type, $excludeStatuses, $args );
				CHIEFED_UTILS::getLogger()->debug  ( '!is_multisite :: count($rows) ' . count ( $rows ) );
			} else {
				
				$resultsArray = self::getAllPostsOfAllBlogsOfType ( $post_type, null, null, $excludeStatuses, $args );
				
				$rows = $resultsArray ['all_posts'];
				$resultTable = $resultsArray ['ids_per_blog'];
				
				CHIEFED_UTILS::getLogger()->debug  ( 'MULTISITE :: count($rows) ' . count ( $rows ) );
			}
			
			// now we need to get each of our posts into an array and return them
			if (! empty ( $rows )) {
				$nb_of_scheduled = 0;
				$nb_of_drafts = 0;
				$nb_of_pending = 0;
				
				$tableHeaderColor = "#6B747A";
				
				$manualTable = '<br/>';
				$chief_editor_table_header = '<table class="sortable" style="border:solid #6B6B6B 1px;width:100%;">';
				$chief_editor_table_header .= '<tr style="color:#FFAF30;background-color:' . $tableHeaderColor . ';">';
				$chief_editor_table_header .= '<td>#</td><td>' . __ ( 'Blog Title', 'chief-editor' ) . '</td><td>' . __ ( 'Featured image', 'chief-editor' ) . '</td>';
				$chief_editor_table_header .= '<td>Post</td><td>' . __ ( 'Submission date', 'chief-editor' ) . '</td><td>' . __ ( 'Status', 'chief-editor' ) . '</td>';
				// $chief_editor_table_header .= '<td>'.__('Excerpt','chief-editor').'</td>';
				$chief_editor_table_header .= '<td>' . __ ( 'Author (login)', 'chief-editor' ) . '</td>';
				$chief_editor_table_header .= '<td style="min-width: 100px;">' . __ ( 'Scheduled for date', 'chief-editor' ) . '</td></tr>';
				$manualTable .= $chief_editor_table_header;
				$posts = array ();
				$countIdx = 0;
				$tableData = array ();
				foreach ( $rows as $row ) {
					
					$countIdx ++;
					$data = $row->ID;
					$entry = array (
							'idx' => $countIdx 
					);
					
					if (is_multisite ()) {
						$blog_id = self::findBlogIdFromPostId ( $data, $resultTable );
						$current_blog_details = get_blog_details ( $blog_id );
						$blog_path = $current_blog_details->path;
						$blog_name = $current_blog_details->blogname;
						$permalink = get_blog_permalink ( $blog_id, $data );
						// CHIEFED_UTILS::getLogger()->debug ('Find post ' . $data . ' on blog ' . $blog_id);
						$new_post = $row; // get_blog_post($blog_id, $data);
						
						$entry ['blog'] = $blog_name;
					} else {
						$blog_id = '0';
						// $bloginfo = get_bloginfo();
						$blog_path = get_bloginfo ( 'url' );
						$blog_name = get_bloginfo ( 'name' );
						$new_post = $row; // get_post($data);
						$permalink = get_permalink ( $data );
						$entry ['blog'] = $blog_name;
					}
					
					$post_id = $new_post->ID;
					$title = $new_post->post_title;
					$entry ['id'] = $post_id;
					$entry ['post'] = $title;
					$post_thumbnail = '<a class="ce_post_thumbnail" target="_blank" href="' . $permalink . '" title="' . esc_attr ( $title ) . '">';
					$entry ['blog_permalink'] = $permalink;
					if (is_multisite ()) {
						$post_thumbnail .= self::get_the_post_thumbnail_by_blog ( $blog_id, $post_id, array (
								100,
								100 
						) );
					} else {
						$post_thumbnail .= get_the_post_thumbnail ( $post_id, array (
								100,
								100 
						) );
					}
					$post_thumbnail .= '</a>';
					
					$abstract = $new_post->post_excerpt;
					$author_id = $new_post->post_author;
					$co_authors = '';
					if (function_exists ( 'coauthors' )) {
						$coauthors = get_coauthors ( $post_id );
						$co_authors = array ();
						foreach ( $coauthors as $author ) {
							$co_authors [] = $author->ID;
						}
						
						$entry ['coauthors'] = implode ( ';', $co_authors );
					}
					if (is_multisite ()) {
						$user_info = self::get_userdata_for_blog ( $author_id, $blog_id );
					} else {
						$user_info = get_userdata ( $author_id );
					}
					
					$userlogin = $user_info->user_login;
					$userdisplayname = $user_info->display_name;
					$entry ['author'] = $userdisplayname;
					$entry ['author_id'] = $author_id;
					$date_format = 'l, jS F Y';
					$creation_date = get_the_time ( $date_format, $new_post );
					$date = $new_post->post_date;
					$entry ['submission_date'] = $date;
					// CHIEFED_UTILS::getLogger()->debug ($post_id . " working on post type " . get_post_type($post_id));
					$entry ['post_type'] = get_post_type ( $post_id );
					if ($entry ['post_type'] == CHIEFED_PRE_DESKTOP_PUBLISHING_CPT) {
						// PRINT WF
						$post_state = get_post_meta ( $post_id, 'informations_complmentaires_' . 'article-status', true );
						$entry ['schedule'] = '';
						$entry ['blog'] = PreDesktopPublishing::getPeriodicalName ( $post_id );
						$entry ['shot'] = get_post_meta ( $post_id, 'informations_complmentaires_' . 'inside-shot', true ); // PreDesktopPublishing::getPeriodicalShot($post_id);
						$entry ['nb_of_pages'] = get_post_meta ( $post_id, 'informations_complmentaires_' . 'max_nb_of_pages', true );
						$parentShotId = get_post_meta ( $post_id, 'informations_complmentaires_' . 'inside-shot', true );
						// CHIEFED_UTILS::getLogger()->debug ($parentShotId . " parent shot has date ?");
						if (is_numeric ( $parentShotId ) && $parentShotId > 0) {
							$shotDate = get_the_time ( 'U', $parentShotId );
							// CHIEFED_UTILS::getLogger()->debug ($parentShotId . " parent shot has date " . $shotDate);
							
							$entry ['schedule'] = $shotDate;
						} else {
							CHIEFED_UTILS::getLogger()->debug  ( $parentShotId . " no parent" );
						}
					} else {
						
						$post_state = $new_post->post_status;
						$entry ['schedule'] = $date;
					}
					$futureColor = self::get_post_color_from_status ( 'future', $entry ['post_type'] ); // '#A4F2FF';
					$draftColor = self::get_post_color_from_status ( 'draft', $entry ['post_type'] ); // '#EDEDED';
					$pendingColor = self::get_post_color_from_status ( 'pending', $entry ['post_type'] ); // '#9CFFA1';
					$entry ['status'] = $post_state;
					$line_color = self::get_post_color_from_status ( $post_state, $entry ['post_type'] );
					// $post_state == 'future' ? $futureColor : ( $post_state == 'pending' ? $pendingColor : $draftColor);
					// CHIEFED_UTILS::getLogger()->debug ("Author is : " . $author_id);
					if ($post_state == 'future') {
						$nb_of_scheduled ++;
					} elseif ($post_state == 'draft') {
						$nb_of_drafts ++;
					} elseif ($post_state == 'pending') {
						$nb_of_pending ++;
					}
					// CHIEFED_UTILS::getLogger()->debug ("Author is : " . $author_id);
					$complete_new_table_line = '<tr style="background-color:' . $line_color . ';">';
					$entry ['color'] = $line_color;
					$complete_new_table_line .= '<td>' . $countIdx . '</td>';
					$complete_new_table_line .= '<td><a href="' . $blog_path . '" target="_blank"><h4>' . $blog_name . '</h4></a></td>';
					$complete_new_table_line .= '<td>' . $post_thumbnail . '</td>';
					$entry ['featured'] = $post_thumbnail;
					$entry ['blog_permalink'] = $blog_path;
					$edit_post_link = '';
					if (is_multisite ()) {
						$edit_post_link .= self::get_multisite_post_edit_link ( $blog_id, $post_id );
					} else {
						$edit_post_link .= get_edit_post_link ( $post_id );
					}
					
					$entry ['post_permalink'] = $permalink;
					$entry ['post_edit_permalink'] = $edit_post_link;
					// current_user_can('delete_others_pages')
					
					$complete_new_table_line .= '<td><span style="font-size:16px;"><a href="' . $permalink . '" target="blank_" title="' . $title . '">' . $title . '</a></span>';
					if (current_user_can ( 'delete_others_pages' )) {
						$complete_new_table_line .= ' (<a href="' . $edit_post_link . '" target="_blank">' . __ ( 'Edit' ) . '</a>)';
					}
					$complete_new_table_line .= '</td>';
					$complete_new_table_line .= '<td>' . $creation_date . '</td>';
					$status_image = CHIEF_EDITOR_PLUGIN_URL . '/images/' . $post_state . '.png';
					$status_meaning = self::get_post_status_meaning_from_status ( $post_state );
					$complete_new_table_line .= '<td>' . $status_meaning . '<br/><img src="' . $status_image . '"/></td>';
					// $complete_new_table_line .= '<td>'.$abstract.'</td>';
					// CHIEFED_UTILS::getLogger()->debug ("Author is : " . $author_id);
					$complete_new_table_line .= '<td>' . $userdisplayname . ' (' . $userlogin . ')';
					if (current_user_can ( 'delete_others_pages' )) {
						$bat_button = '<div class="wrap"><form id="' . $post_id . '_chief-editor-bat-form" class="chief-editor-bat-form" action="" method="POST">';
						$bat_button .= '<div><input type="submit" id="' . $post_id . '_chief-editor-bat-submit" name="chief-editor-bat-submit" class="chief-editor-bat-submit button-primary" value="' . __ ( 'Send In-Press to author', 'chief-editor' ) . '"/>';
						$bat_button .= '<input type="hidden" id="postID" name="postID" value="' . $post_id . '">';
						$bat_button .= '<input type="hidden" id="blogID" name="blogID" value="' . $blog_id . '">';
						$bat_button .= '<input type="hidden" id="authorID" name="authorID" value="' . $author_id . '">';
						$bat_button .= '</div></form><div id="ce_dialog_email" class="ce_dialog_email" title="Dialog Title" style="display:none">Some text</div></div>';
						// CHIEFED_UTILS::getLogger()->debug ($bat_button);
						$complete_new_table_line .= $bat_button;
					}
					$complete_new_table_line .= '</td>';
					
					$entry ['post_id'] = $post_id;
					$entry ['blog_id'] = $blog_id;
					
					if ($post_state == 'future') {
						$complete_new_table_line .= '<td><h3 style="color:#002EB8">' . date_i18n ( get_option ( 'date_format' ), strtotime ( $date ) ) . '</h3>';
						$complete_new_table_line .= '<h4 style="color:#B8008A">' . date_i18n ( 'G:i', strtotime ( $date ) ) . '</h4></td>';
					} else {
						$complete_new_table_line .= '<td>' . __ ( 'not scheduled', 'chief-editor' ) . '</td>';
					}
					
					$complete_new_table_line .= '</tr>';
					
					$manualTable .= $complete_new_table_line;
					
					$posts [] = $new_post;
					$tableData [] = $entry;
				}
				
				$manualTable .= '</table>';
				$manualTable .= '<hr>';
				$manualTable .= '<table class="sortable" style="border:solid black 1px;width:50%;">';
				$manualTable .= '<tr style="background-color:' . $futureColor . ';"><td>' . __ ( 'Scheduled posts : ', 'chief-editor' ) . '</td><td>' . $nb_of_scheduled . '</td></tr>';
				$manualTable .= '<tr style="background-color:' . $pendingColor . ';"><td>' . __ ( 'Pending posts : ', 'chief-editor' ) . '</td><td>' . $nb_of_pending . '</td></tr>';
				$manualTable .= '<tr style="background-color:' . $draftColor . ';"><td>' . __ ( 'Draft posts : ', 'chief-editor' ) . '</td><td>' . $nb_of_drafts . '</td></tr>';
				$manualTable .= '<tr style="background-color:#ffffff;color:#000000;"><td>' . __ ( 'Total unpublished posts : ', 'chief-editor' ) . '</td><td>' . count ( $rows ) . '</td></tr>';
				$manualTable .= '</table>';
				$manualTable .= '<hr>';
				
				// echo "<pre>"; print_r($posts); echo "</pre>"; exit; # debugging code
				return $tableData;
			}
		}
		public static function getLogger($logger_category = "CHIEFED") {
			if (self::$chiefed_logger == NULL) {
				self::setupLog4PHPLogger ();
			}
			
			if (null == $logger_category || empty ( $logger_category )) {
				return self::$chiefed_logger;
			} else {
				return Logger::getLogger ( $logger_category );
			}
		}
		static function setupLog4PHPLogger() {
			$fileConfig = false;
			if (! $fileConfig) {
				$dir = plugin_dir_path ( __FILE__ );
				$logFilesPath = $dir . 'logs/chiefed-%s.log';
				//  ( $logFilesPath );
				Logger::configure ( array (
						'rootLogger' => array (
								'appenders' => array (
										'maxicharts' 
								),
								'level' => CHIEFED_DEBUG_LEVEL 
						),
						'appenders' => array (
								'maxicharts' => array (
										'class' => 'LoggerAppenderDailyFile',
										'layout' => array (
												'class' => 'LoggerLayoutPattern',
												'params' => array (
														'conversionPattern' => "%date{Y-m-d H:i:s,u} %logger %-5level %F{10}:%L %msg%n %ex" 
												) 
										),
										
										'params' => array (
												'file' => strval ( $logFilesPath ),
												'append' => true,
												'datePattern' => "Y-m-d" 
										) 
								) 
						) 
				) );
				
				self::$chiefed_logger = Logger::getLogger ( "Chief Editor" );
				// chiefed_log ( "Logger initialized" );
				Logger::getLogger ( __CLASS__ )->trace ( "Logger up!..." );
			}
		}
	}
}

new CHIEFED_UTILS ();