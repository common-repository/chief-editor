<?php
if (! defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

$path = sprintf("%s/admin/meta_boxes.php", dirname(__FILE__));
require_once ($path);

if (! class_exists('EditorDashBoard')) {

    class EditorDashBoard
    {

        private $dateFormat = 'Y-m-d';

        public function __construct()
        {
            add_shortcode('chiefed_editor_dashboard', array(
                $this,
                'chiefedCreateEditorDashBoard'
            ));
            
            // [chiefed_shot_single_page post_id="61"]
            add_shortcode('chiefed_shot_single_page', array(
                $this,
                'chiefedCreateSingleShotDashBoard'
            ));
            
            // load datatables
            add_action('wp_enqueue_scripts', array(
                $this,
                'chiefed_load_datatables'
            ));
        }

        function chiefed_load_datatables()
        {
            $chiefed_dt_js = plugins_url('libs/DataTables/datatables.min.js', __FILE__);
            $chiefed_dt_css = plugins_url('libs/DataTables/datatables.min.css', __FILE__);
            CHIEFED_UTILS::getLogger()->debug ($chiefed_dt_js);
            wp_enqueue_style('chiefed-dt-css', $chiefed_dt_css);
            wp_enqueue_script('chiefed-dt-js', $chiefed_dt_js, array(
                'jquery'
            ), false, true);
            
            $chiefed_dashboard_dt_js = plugins_url('js/chiefed_datatables.js', __FILE__);
            wp_enqueue_script('chiefed-dashboard-dt-js', $chiefed_dashboard_dt_js, array(
                'jquery',
                'chiefed-dt-js',
               // 'chief-editor-swal-js'
            ), false, true);
            wp_localize_script('chiefed-dashboard-dt-js', 'chiefed_ajax_object', array(
                'ajax_url' => admin_url('admin-ajax.php')
            ));
        }

        public static function createTableFromForms($extracted_posts_datas)
        {
            $result = array();
            // # Revue Numéro Post Status Author Schedule
            $result['header'] = array(
                '#',
                'Revue',
                'Numéro',
                'Catégorie',
                'Sous-catégorie',
                'Article',
                'Auteur',
            	'row_status',
                'Status',
                'Date',
                '# de pages'
            
            );
            $result['footer'] = $result['header'];
            $result['body'] = array();
            $idx = 1;
            if ($extracted_posts_datas){
            foreach ($extracted_posts_datas as $post_item) {
                
                $oneLine = array(
                    '#' . $idx,
                    self::column_blog($post_item),
                    self::column_shot($post_item), // ['shot'],
                    self::column_category($post_item),
                    self::get_sub_category($post_item['id']),
                    self::column_post($post_item),
                    self::column_author($post_item),
                	self::column_raw_status($post_item),
                    self::column_status($post_item),
                    self::column_schedule($post_item),
                    isset($post_item['nb_of_pages']) ? $post_item['nb_of_pages'] : 0,
                );
                
                $idx ++;
                
                $newDataItem = '<td>' . implode('</td><td>', $oneLine) . '</td>';
                // DOFF_Utils::getLogger()->trace($newDataItem);
                $result['body'][] = $newDataItem;
                $result['data'][] = $oneLine;
            }
            }
            
            // DOFF_Utils::getLogger()->debug("body size : " . count($result['body']));
            
            return $result;
        }

        static function get_main_category($post_id)
        {
            
            /*
             * $taxonomy = 'category';
             * // ID Gets which assign post
             * $post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
             *
             * // Links seprator.
             * $separator_link = ', ';
             *
             * if ( ! empty( $post_terms ) && ! is_wp_error( $post_terms ) ) {
             *
             * $term_ids = implode( ',' , $post_terms );
             *
             * $terms = wp_list_categories( array(
             * 'title_li' => '',
             * 'style' => 'none',
             * 'echo' => false,
             * 'taxonomy' => $taxonomy,
             * 'include' => $term_ids
             * ) );
             *
             * $terms = rtrim( trim( str_replace( '<br />', $separator_link, $terms ) ), $separator_link );
             *
             * // show category post.
             * $result = $terms;
             * }
             *
             * return $result;
             */
            $result = '';
            $categoriesTerms = get_the_category($post_id);
            if (! empty($categoriesTerms)) {
                $parent_categories = get_category_parents($categoriesTerms[0]->cat_ID, false, '/', true);
                $categories = explode('/', $parent_categories);
                $category = $categories[0];
                
                $color = Chief_Editor_Meta_Boxes::get_color_for_category($category);
                $result = '<div class="chiefed_listtable_category" style="background-color:' . $color . '">';
                $result .= Chief_Editor_Meta_Boxes::get_label_for_category($category);
                $result .= '</div>';
            }
            /*
             * if ( ! empty( $categories ) ) {
             * foreach ($categories as $category){
             * $result .= esc_html( $category->name );
             * }
             *
             * }
             */
           
            
            return $result;
        }

        static function get_sub_category($post_id)
        {
            $result = '';            
            $categories = get_the_category($post_id);
            
            if (! empty($categories)) {                
                $category = esc_html($categories[0]->slug);              
                $result = $category;
            }            
            
            return $result;
        }

        static function column_category($post_item)
        {
            return self::get_main_category($post_item['id']);
        }

        static function column_shot($post_item)
        {
            $shot_id = isset($post_item['shot']) ? $post_item['shot'] : '';
            $shot_name = PreDesktopPublishing::getPeriodicalShot($post_item['id']);
            $permalink = get_permalink($shot_id);
            
            return sprintf('<a href="%s" target="_blank">' . $shot_name . '</a>', $permalink);
        }       

        static function column_post($item)
        {
            $result = '<div>';
            $editBut = sprintf('<a href="%s" target="_blank">Edit</a>', $item['post_edit_permalink']);            
            $result .= sprintf('%1$s<br/>%2$s', sprintf('<a href="%s" target="_blank">' . $item['post'] . '</a>', $item['post_permalink']), $editBut);
            $result .= '</div>';
            
            return $result;
        }

        static function column_raw_status($item) {
        	$result = '<div class="chiefed_listtable_raw_status">';
        	$result .= $item['status'];
        	$result .= '</div>';
        	
        	return $result;
        }
        
        static function column_status($item)
        {
        
            $result = '<div status="'.$item['status'].'" class="chiefed_listtable_status" style="background-color:' . $item['color'] . '">';
            $result .= Chief_Editor_Meta_Boxes::get_label_for_status($item['status']);
            $result .= '</div>';
            
            return $result;
        }

        static function column_featured($item)
        {
            return sprintf('<a href="%s" target="_blank">' . $item['featured'] . '</a>', $item['post_permalink']);
        }

        static function column_blog($item)
        {
            return sprintf('<a href="%s" target="_blank">' . $item['blog'] . '</a>', $item['blog_permalink']);
        }

        static function column_author($item)
        {
            if (! empty($item['coauthors'])) {
                $coauthors = $item['coauthors'];
                $coauthors_array = explode(';', $coauthors);
                // $coauthors = get_coauthors( $post->ID );
                $result = '';
                foreach ($coauthors_array as $author_id) {
                    // The comment was left by the co-author
                    $current_author = get_user_by('ID', $author_id);
                    
                    if ($author_id == get_current_user_id()) {
                        $fun = '<span class="chiefed_highlight">' . __('YOU') . '</span>' . ' ';
                    } else {
                        $fun = '';
                    }
                    $result .= $fun . $current_author->display_name . ' (<a href="mailto:' . $current_author->user_email . '" >' . $current_author->user_email . '</a>)';
                }
            } else {
                $result = $item['author'];
                // log_me($result);
                if (current_user_can('delete_others_pages')) {
                    $post_id = $item['post_id'];
                    $blog_id = $item['blog_id'];
                    $result .= '<div class="wrap"><form id="' . $post_id . '_chief-editor-bat-form" class="chief-editor-bat-form" action="" method="POST">';
                    $result .= '<div><input type="submit" id="' . $post_id . '_chief-editor-bat-submit" name="chief-editor-bat-submit" class="chief-editor-bat-submit" value="' . __('Send In-Press to author', 'chief-editor') . '"/>';
                    $result .= '<input type="hidden" id="postID" name="postID" value="' . $post_id . '">';
                    $result .= '<input type="hidden" id="blogID" name="blogID" value="' . $blog_id . '">';
                    $result .= '<input type="hidden" id="authorID" name="authorID" value="' . $item['author_id'] . '">';
                    $result .= '</div></form><div id="ce_dialog_email" class="ce_dialog_email" title="Dialog Title" style="display:none">Some text</div></div>';
                    
                    // log_me($result);
                } else {
                    // $result = $item['author'];
                }
            }
            
            return $result;
        }

        static function column_schedule($item)
        {
            $date = $item['schedule'];
            if ($item['post_type'] == CHIEFED_PRE_DESKTOP_PUBLISHING_CPT) {
                // $result = $date;
                $result = '<div style="color:#002EB8">' . date_i18n(get_option('date_format'), strtotime($date)) . '</div>';
                // $result .= '<div style="color:#B8008A">' . date_i18n( 'G:i', $date ) . '</div>';
            } else {
                if ($item['status'] == 'future') {
                    $result = '<div style="color:#002EB8">' . date_i18n(get_option('date_format'), strtotime($date)) . '</div>';
                    $result .= '<div style="color:#B8008A">' . date_i18n('G:i', strtotime($date)) . '</div>';
                } else {
                    $result = __('not scheduled', 'chief-editor');
                }
            }
            
            return $result;
        }

        function getProgressBar($allItems){
        	CHIEFED_UTILS::getLogger()->debug ("getProgressBar");
        	$statusesArray = Chief_Editor_Meta_Boxes::get_statuses_array();
        	$statesOfArticles = array_combine (  $statusesArray, array_fill ( 0 , count($statusesArray) , 0 ) );
        	CHIEFED_UTILS::getLogger()->debug ($statesOfArticles);
        	$totalPagesOfShot = 0;
        	foreach ($allItems as $article) {
        		CHIEFED_UTILS::getLogger()->debug ("--- ".$article['id']." ---");
        		$post_state = get_post_meta($article['id'], 'informations_complmentaires_' . 'article-status', true);
        		$post_nb_page = get_post_meta($article['id'], 'informations_complmentaires_' . 'max_nb_of_pages', true);
        		if (empty($post_state)){
        			continue;
        		}
        		//CHIEFED_UTILS::getLogger()->debug ($article);
        		CHIEFED_UTILS::getLogger()->debug ("article ".$post_state." / ".$post_nb_page);
        		$statesOfArticles[$post_state] += $post_nb_page;
        		$totalPagesOfShot += $post_nb_page;
        	}
        	/*
        	<div class="bar">
        	<section id="green" style="width: 11.701%">Green</section>
        	<section id="blue" style="width: 30.7279%">Blue</section>
        	<section id="yellow" style="width: 16.0294%">Yellow</section>
        	<section id="red">Red</section>
        	</div>*/
        	
        	$htmlResult = '<div class="chiefed_shot_progress_bar">';
        	
        	foreach ($statesOfArticles as $status => $pages) {
        		if ($totalPagesOfShot == 0 || $pages == 0){
        			continue;
        		}
        		$completionRatio = 100 * $pages / $totalPagesOfShot;        		
        		$stateLabel = Chief_Editor_Meta_Boxes::get_label_for_status($status);
        		$htmlResult .= '<section title="'.$stateLabel.'" style="background-color:'.Chief_Editor_Meta_Boxes::get_color_for_status($status).';width: '.number_format($completionRatio, 2).'%">'.$stateLabel.'</section>';
        	}
        	
        	$htmlResult .= '</div>';
        	
        	
        	return $htmlResult;
        }
        
        function chiefedCreateSingleShotDashBoard($atts)
        {
            $defaultsParameters = array(
                'post_id' => ''
            );
            extract(shortcode_atts($defaultsParameters, $atts));
            
            $shot_id = str_replace(' ', '', $post_id);
            CHIEFED_UTILS::getLogger()->debug ("Create SHOT dashboard " . $post_id);
            $tableId = 'editor_single_shot_dashboard';
            $result = "";
            // set as full width
            // _wp_page_template page-templates/full-width.php
            //
            $postTypeName = CHIEFED_PRE_DESKTOP_PUBLISHING_CPT;
            $shot = get_post($shot_id);
            // $result .= $shot;
            CHIEFED_UTILS::getLogger()->debug ($shot);
            if ($shot) {
                $date = $shot->post_date;
                $attr = array(
                		'inside_shot_id' => $shot_id,
                );
                $allItems = CHIEFED_UTILS::get_all_editor_items($postTypeName, array(), $attr);
                CHIEFED_UTILS::getLogger()->debug (count($allItems)." articles inside shot ".$shot_id." : ".$shot->post_title);
                // $result .= $date;
                // [post_date] => 2018-03-28 12:20:39
                $result .= '<div class="chiefed_singleshot_header_container">';
                $result .= '<div class="chiefed_singleshot_header_item"><span>' . __('Shot') . ' : </span><div style="font-size:1.5em;font-weight: bold;">' . $shot->post_title . '</div></div>';
                $completionState = $this->getProgressBar($allItems);//'<meter style="flex-grow: 3;" id="chiefed_shot_progress" min="0" optimum="100" value="50" max="100"></meter>';
               // $result .= '<div class="chiefed_singleshot_header_item" style="flex-grow: 3;"><span>';
                $result .= $completionState;
               // $result .= '</div>';
                
                $result .= '<div class="chiefed_singleshot_header_item"><span>' . __('Date') . ' : </span><div style="font-size:1.5em;font-weight: bold;">' . date_i18n(get_option('date_format'), strtotime($date)) . '</div></div>';
                $result .= '</div>';
                
                //$insideShotId = $shot_id;
                
                
                if (empty($allItems)) {
                    $result = __('No post of type') . ' ' . $type;
                } else {
                    $tableArray = $this->createTableFromForms($allItems);
                    
                    $header = '<thead><tr><th>' . implode('</th><th>', $tableArray['header']) . '</th></tr></thead>';
                    $footer = '<tfoot><tr><th>' . implode('</th><th>', $tableArray['footer']) . '</th></tr></tfoot>';
                    $body = '<tbody><tr>';
                    $body .= isset($tableArray['body']) && is_array($tableArray['body']) ? implode('</tr><tr>', $tableArray['body']) : '';
                    $body .= '</tr></tbody>';
                    
                    $filtersText = __('Filters:');
                    
                    $table = '<table id="' . $tableId . '" class="display dt-responsive" cellspacing="0" width="100%">' . $header . $footer . $body . '</table>';
                    
                    $color_scale = Chief_Editor_Meta_Boxes::create_statuses_color_scale();
                    
                    $result .= /*$headerFilter .*/ $table . $color_scale;
                }
            } else {
                $result .= __('No data for shot') . ' : ' . $post_id;
            }
            
            return $result;
        }

        function createTimeDatatableFilters($tableId)
        {
            $name = $tableId . '_time_filter_name';
            
            $content = '<select name="' . $name . '" id="' . $tableId . '_time_filter_id' . '">';
            // FIXME : replace with setting META ::$timeFilters
            $filtersArray = Chief_Editor_Meta_Boxes::$timeFilters;
            CHIEFED_UTILS::getLogger()->debug ($filtersArray);
            /*
             * [0] => Array
        (
            [name] => -10 days
            [label] => < 10 days
            [color] => 1
        )


             */
            
            foreach ($filtersArray as $id => $datas) {
                $defaultSelection = $datas['color'] == true ? ' selected="selected"' : '';
                $newOption = '<option value="' . $datas['name'] . '"'.$defaultSelection.'>' . htmlentities($datas['label']) . '</option>';
                CHIEFED_UTILS::getLogger()->debug ($newOption);
                $content .= $newOption;
            }
            /*
            $content .= '<option value="-10 days" selected="selected">' . __('< 10 days','chief-editor') . '</option>';
            
            $periods = array(                
                '-1 month' => __('< 1 month','chief-editor'),
                '-2 month' => __('< 2 month','chief-editor'),
                '-6 month' => __('< 6 month','chief-editor'),
                '-1 year' => __('< 1 year','chief-editor'),
                '-2 years' => __('< 2 years','chief-editor'),               
            );
            
            foreach ($periods as $id => $label) {                
                $content .= '<option value="' . $id . '">' . $label . '</option>';
            }*/
            
            $content .= '</select>';
            
            $result = '<div class="datatable_filter">';
            $result .= __('Timeframe:','chief-editor');
            $result .= $content;
            $result .= '</div>';
            
            // $field->choices = $options;
            return $result;
        }
        
        function chiefedCreateEditorDashBoard($atts)
        {
            $defaultsParameters = array(
                'type' => 'post'
            );
            extract(shortcode_atts($defaultsParameters, $atts));
            
            $type = str_replace(' ', '', $type);
            $max_days_in_past = 365;
            $isEditor = current_user_can('delete_others_pages');
            if (! is_user_logged_in() || !$isEditor){
                $result = __('You cannot access this page, you need editor rights');
            } else {
                
                $tableId = 'editor_dashboard';
        
                //$args = array('post_type' => $type);
                //$postList = get_posts($args);
				
	            $excludeStatuses = array(
	               /* 'inherit',
	                'auto-draft',
	                'trash',
	                'publish'*/
	            );
				
				
	            $allItems = CHIEFED_UTILS::get_all_editor_items($type, $excludeStatuses);
				
				if (empty($allItems)){
					$result = __('No post of type').' '.$type;
				} else {
	                $tableArray = $this->createTableFromForms($allItems);
                
	                $header = '<thead><tr><th>' . implode('</th><th>', $tableArray['header']) . '</th></tr></thead>';
	                $footer = '<tfoot><tr><th>' . implode('</th><th>', $tableArray['footer']) . '</th></tr></tfoot>';
	                $body = '<tbody><tr>';
	                $body .= isset($tableArray['body']) && is_array($tableArray['body']) ? implode('</tr><tr>', $tableArray['body']) : '';
	                $body .= '</tr></tbody>';
                
	                $filtersText = __('Filters:');
	                //$headerFilter = '<fieldset class="chiefed-grey-box">';
	               
	                $headerFilter = $this->createTimeDatatableFilters($tableId);
	           
	               // $headerFilter .= '</fieldset>';
                
	                $error_panel = '<div id="chiefed-error-panel" style="color:red;"></div>';
	                
	                $table = '<table id="' . $tableId . '" class="display dt-responsive" cellspacing="0" width="100%">' . $header . $footer . $body . '</table>';
                
	                // //DOFF_Utils::getLogger ()->debug ($headerFilter);
					$color_scale = Chief_Editor_Meta_Boxes::create_statuses_color_scale();
					
	                $result = $error_panel.$headerFilter .$table . $color_scale;
					
					 
				}
				
               
            }
            return $result;
        }
    }
}

new EditorDashBoard();