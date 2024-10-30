<?php
if (! defined ( 'ABSPATH' )) {
    exit (); // Exit if accessed directly
}

$path = sprintf("%s/chiefed-constants.php", dirname(dirname(__FILE__)));
require_once ($path);

$path = sprintf("%s/meta_boxes.php", dirname(__FILE__));
require_once ($path);

if (! class_exists ( 'WP_List_Table' )) {
    require_once (ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class ChiefEditor_Table extends WP_List_Table {
    public $post_type = '';
    public $data = array ();
    public $allClinicalSituations = array ();
    public $allCloseClinicalSituations = array ();
    public $totalItems = 0;
    public $okItems = 0;
    public $koItems = 0;
    public $incompatibilityItems = 0;
    public $closeItems = 0;
    public $full_okItems = 0;
    public $full_koItems = 0;
    public $full_incompatibilityItems = 0;

    function column_post($item) {
        $result = '<div>'; 
        $actions = array();
        /*$result .= CHIEFED_SALES_ROLE . ' | ';
        $result .= current_user_can( CHIEFED_SALES_ROLE ). ' | ';*/
        if ( current_user_can( 'delete_others_posts' ) ) {
			$actions = array( 
				'edit' => sprintf( '<a href="%s" target="_blank">Edit</a>', $item['post_edit_permalink'] ), 				
			);
		} /*else {
			$actions = array();
		}*/
        $result .= sprintf ( '%1$s %2$s', sprintf ( '<a href="%s" target="_blank">' . $item ['post'] . '</a>', $item ['post_permalink'] ), $this->row_actions ( $actions ) );
        $result .= '</div>';

        return $result;
    }
    function column_status($item) {
        $size = '75px';
        $sizeStyle = "width:'.$size.';height:'.$size.';";
        // -webkit-text-stroke:1px black;
        $result = '<div class="chiefed_listtable_status" style="background-color:' . $item ['color'] . '">';
        $result .= Chief_Editor_Meta_Boxes::get_label_for_status($item ['status'],'web');
        $result .= '</div>';

        return $result;
    }
    function column_featured($item) {
        return sprintf ( '<a href="%s" target="_blank">' . $item ['featured'] . '</a>', $item ['post_permalink'] );
    }
    function column_blog($item) {
        return sprintf ( '<a href="%s" target="_blank">' . $item ['blog'] . '</a>', $item ['blog_permalink'] );
    }
    function column_author($item) {

        if (!empty($item['coauthors'])){
            $coauthors = $item ['coauthors'];
            $coauthors_array = explode(';',$coauthors);
            //$coauthors = get_coauthors( $post->ID );
            $result = '';
            foreach ( $coauthors_array as $author_id ) {
                // The comment was left by the co-author
                $current_author = get_user_by( 'ID', $author_id );             
                
                if ( $author_id == get_current_user_id() ) {
                    $fun = '<span class="chiefed_highlight">'.__('YOU').'</span>'.' ';
                    
                } else {
                    $fun = '';
                }
                $result .= $fun.$current_author->display_name .' (<a href="mailto:'.$current_author->user_email.'" >'.$current_author->user_email.'</a>)';
            }
        } else {
            $result = $item ['author'];
            //log_me($result);
            if (current_user_can ( 'delete_others_pages' )) {
                $post_id = $item ['post_id'];
                $blog_id = $item ['blog_id'];
                $result .= '<div class="wrap"><form id="' . $post_id . '_chief-editor-bat-form" class="chief-editor-bat-form" action="" method="POST">';
                $result .= '<div><input type="submit" id="' . $post_id . '_chief-editor-bat-submit" name="chief-editor-bat-submit" class="chief-editor-bat-submit" value="' . __ ( 'Send BAT to author', 'chief-editor' ) . '"/>';
                $result .= '<input type="hidden" id="postID" name="postID" value="' . $post_id . '">';
                $result .= '<input type="hidden" id="blogID" name="blogID" value="' . $blog_id . '">';
                $result .= '<input type="hidden" id="authorID" name="authorID" value="' . $item ['author_id'] . '">';
                $result .= '</div></form><div id="ce_dialog_email" class="ce_dialog_email" title="Dialog Title" style="display:none">Some text</div></div>';

                //log_me($result);

            } else {
                // $result = $item['author'];
            }
        }

        return $result;
    }
    function column_schedule($item) {
        $date = $item ['schedule'];
        if ($this->post_type == CHIEFED_PRE_DESKTOP_PUBLISHING_CPT){            
            //$result = $date;
            $result = '<h3 style="color:#002EB8">' . date_i18n( get_option ( 'date_format' ),  $date  ) . '</h3>';
            $result .= '<h4 style="color:#B8008A">' . date_i18n( 'G:i', $date  ) . '</h4>';
        } else {
            if ($item ['status'] == 'future') {
                $result = '<h3 style="color:#002EB8">' . date_i18n ( get_option ( 'date_format' ), strtotime ( $date ) ) . '</h3>';
                $result .= '<h4 style="color:#B8008A">' . date_i18n ( 'G:i', strtotime ( $date ) ) . '</h4>';
            } else {
                $result = __ ( 'not scheduled', 'chief-editor' );
            }
        }

        return $result;
    }
    function get_columns() {

        if ($this->post_type == CHIEFED_PRE_DESKTOP_PUBLISHING_CPT){
            $columns = array (
                'idx' => '#',
                'blog' => __ ( 'Periodical','chief-editor' ),
                'shot' => __('Shot','chief-editor'),
                //'featured' => __ ( 'Featured image', 'chief-editor' ),
                'post' => __ ( 'Post', 'chief-editor' ),
                /*'post_permalink' => __ ( 'Post link' ),
				'post_edit_permalink' => __ ( 'Post edit link' ),
				'submission_date' => __ ( "Submission date", 'chief-editor' ),
                */
                'status' => __ ( 'Status', 'chief-editor' ),
                'author' => __ ( "Author", 'chief-editor' ),
                'schedule' => __ ( "Schedule", 'chief-editor' ) 
            );
        } else {
            $columns = array (
                'idx' => '#',
                'blog' => __ ( 'Blog' ),
                'featured' => __ ( 'Featured image', 'chief-editor' ),
                'post' => __ ( 'Post', 'chief-editor' ),
                /*'post_permalink' => __ ( 'Post link' ),
				'post_edit_permalink' => __ ( 'Post edit link' ),
				'submission_date' => __ ( "Submission date", 'chief-editor' ),
                */
                'status' => __ ( 'Status', 'chief-editor' ),
                'author' => __ ( "Author", 'chief-editor' ),
                'schedule' => __ ( "Schedule", 'chief-editor' ) 
            );
        }


        //log_me($columns);

        return $columns;
    }
    public function get_hidden_columns() {
        return array (
            //'status',
            'post_permalink',
            'post_edit_permalink',
            'submission_date' 
        );
    }
    function prepare_items() {
        $columns = $this->get_columns ();
        $hidden = $this->get_hidden_columns ();
        $sortable = $this->get_sortable_columns ();
        $this->_column_headers = array (
            $columns,
            $hidden,
            $sortable 
        );
        if (null !== $this->data){
        usort ( $this->data, array (
            $this,
            'usort_reorder' 
        ) );
        }
        $this->items = $this->data;
        $this->totalItems = count ( $this->items );
    }
    function get_sortable_columns() {
        //$sortable_columns = array ();

        $sortable_columns = array (
            'idx' => array (
                'idx',
                false 
            ),
            'blog' => array (
                'blog',
                false 
            ),
            'post' => array (
                'post',
                false 
            ),
            'submission_date' => array (
                'submission_date',
                false 
            ),
            'status' => array (
                'status',
                false 
            ),
            'author' => array (
                'author',
                false 
            ),
            'schedule' => array (
                'schedule',
                false 
            ) 
        );
        return $sortable_columns;
    }
    function usort_reorder($a, $b) {
        // If no sort, default to title
        $orderby = (! empty ( $_GET ['orderby'] )) ? $_GET ['orderby'] : 'idx';
        // If no order, default to asc
        $order = (! empty ( $_GET ['order'] )) ? $_GET ['order'] : 'asc';

        if ($orderby == 'idx') {
            $result = intval ( $a [$orderby] ) > intval ( $b [$orderby] );
        } else {
            // Determine sort order
            $result = strcmp ( $a [$orderby], $b [$orderby] );
        }
        // Send final sort direction to usort
        return ($order === 'asc') ? $result : - $result;
    }
    function getTextBetweenTags($string, $tagname) {
        $pattern = "/<$tagname ?.*>(.*)<\/$tagname>/";
        preg_match ( $pattern, $string, $matches );
        return $matches [1];
    }
    function column_default($item, $column_name) {
        switch ($column_name) {

            case 'idx' :
                return '#' . $item [$column_name];
            case 'blog' :
            case 'shot' :
            case 'featured' :
            case 'submission_date' :
            case 'status' :
            case 'author' :
            case 'schedule' :
            case 'post' :
                return $item [$column_name];

            default :
                return print_r ( $item, true ); // Show the whole array for troubleshooting purposes
        }
    }
}