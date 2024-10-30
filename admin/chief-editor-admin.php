<?php
if (! defined('ABSPATH')) {
    exit();
}

$path = sprintf("%s/editor_table.php", dirname(__FILE__));
require_once ($path);

$path = sprintf("%s/pre_desktop_publishing.php", dirname(__FILE__));
require_once ($path);

$path = sprintf("%s/meta_boxes.php", dirname(__FILE__));
require_once ($path);

$path = sprintf("%s/admin_settings.php", dirname(__FILE__));
require_once ($path);

$path = sprintf("%s/../chiefed_utils.php", dirname(__FILE__));
require_once ($path);

$path = sprintf("%s/../chiefed_front_datatable.php", dirname(__FILE__));
require_once ($path);

define("CHIEFED_DEBUG_OPTIONS", false);

if (! defined('CHIEF_EDITOR_PLUGIN_NAME'))
    define('CHIEF_EDITOR_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));

if (! defined('CHIEF_EDITOR_PLUGIN_DIR'))
    define('CHIEF_EDITOR_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . CHIEF_EDITOR_PLUGIN_NAME);

if (! defined('CHIEF_EDITOR_PLUGIN_URL'))
    define('CHIEF_EDITOR_PLUGIN_URL', WP_PLUGIN_URL . '/' . CHIEF_EDITOR_PLUGIN_NAME);

function updatePostDate($blog_id, $post_id, $post_date)
{
    echo '<br/>date: ' . $post_date . ' strtotime: ' . strtotime($post_date) . ' strtotime(now): ' . strtotime('now') . ' ' . strtotime("+1 hour") . '<br/>';
    echo 'date("Y-m-d H:i:s"):' . date("Y-m-d H:i:s");
    echo 'time("Y-m-d H:i:s"):' . time("Y-m-d H:i:s");
    $now = gmdate('Y-m-d H:i:59');
    echo '<br/>now from gmdate' . $now;
    $time = time();
    echo '<br/>time:' . $time;
    
    $status = strtotime($post_date) > strtotime('+1 hour') ? 'future' : 'publish';
    
    switch_to_blog($blog_id);
    
    $operation = 'edit';
    $newpostdata = array();
    // strtotime("now"), "\n";
    if ($status == 'publish') {
        echo ' ' . strtotime($post_date) . '(' . $post_date . ') < ' . strtotime("now"), "\n";
        echo 'cannot publish artilces from here, only schedule, dates in future';
        return;
        
        // $status = 'publish';
        // $newpostdata['post_status'] = $status;
        // $newpostdata['post_date'] = date( 'Y-m-d H:i:s', $post_date );
        
        // Also pass 'post_date_gmt' so that WP plays nice with dates
        // $newpostdata['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $post_date );
    } elseif ($status == 'future') {
        
        echo '<br/>SCHEDULING: ' . strtotime($post_date) . '>' . strtotime("today") . '\r\n';
        // $status = 'future';
        $newpostdata['post_status'] = $status;
        $newpostdata['post_date'] = date('Y-m-d H:i:s', strtotime($post_date));
        $newpostdata->edit_date = true;
        // Also pass 'post_date_gmt' so that WP plays nice with dates
        $newpostdata['post_date_gmt'] = gmdate('Y-m-d H:i:s', strtotime($post_date));
        
        echo '<br/>SCHEDULING: ' . $newpostdata['post_date'] . ' / GMDate : ' . $newpostdata['post_date_gmt'];
    }
    
    if ('insert' == $operation) {
        $err = wp_insert_post($newpostdata, true);
    } elseif ('edit' == $operation) {
        
        // echo 'edit ==' .$operation."\r\n";
        $newpostdata['ID'] = $post_id;
        
        // $newpostdata['edit_date'] = true;
        
        // echo $newpostdata['ID'] . "_" . $newpostdata['edit_date']. "_" . $newpostdata['post_status']. "_" . $newpostdata['post_date'] . "_" . $newpostdata['post_date_gmt'] ."\r\n";
        // echo '<br/>'.$newpostdata;
        $err = wp_update_post($newpostdata);
        // echo "wp_update_post::Error return: ".$err ."\r\n";
    }
}

class Sort_Posts
{

    var $order, $orderby;

    function __construct($orderby, $order)
    {
        $this->orderby = $orderby;
        $this->order = ('desc' == strtolower($order)) ? 'DESC' : 'ASC';
    }

    function sort($a, $b)
    {
        if ($a->{
            $this->orderby} == $b->{
            $this->orderby}) {
            return 0;
        }
        
        if ($a->{
            $this->orderby} < $b->{
            $this->orderby}) {
            return ('ASC' == $this->order) ? - 1 : 1;
        } else {
            return ('ASC' == $this->order) ? 1 : - 1;
        }
    }
}

if (! class_exists('ChiefEditorSettings')) {

    class ChiefEditorSettings
    {

        /**
         * Holds the values to be used in the fields callbacks
         */
        private $options;

        private $lang_domain = 'chief-editor';

        private $general_settings_key = 'chief_editor_posts_tab';

        private $custom_post_type_keys = array();

        private $calendar_settings_key = 'chief_editor_calendar_tab';

        private $advanced_settings_key = 'chief_editor_comments_tab';

        private $stats_key = 'chief_editor_stats_tab';

        private $custom_stats_key = 'ched_custom_stats_tab';

        private $chief_editor_options_key = 'chief_editor_settings_tab';

        private $chief_editor_dashboard_page_name = 'chief-editor-dashboard';

        private $chief_editor_settings_tabs = array();

        private static $slug = 'chief_editor_single_options';

        private static $updated = false;

        public function __construct()
        {
            // CHIEFED_UTILS::getLogger()->debug("Built CHIEF EDITOR");
            add_action('admin_init', array(
                $this,
                'register_general_settings'
            ));
            
            add_action('admin_init', array(
                $this,
                'register_calendar_tab'
            ));
            add_action('admin_init', array(
                $this,
                'register_advanced_settings'
            ));
            add_action('admin_init', array(
                $this,
                'register_stats_tab'
            ));
            
            add_action('admin_menu', array(
                $this,
                'add_admin_menus'
            ));
            // add_action( 'admin_enqueue_scripts',array( $this,'chief_editor_load_scripts'));
            add_action('wp_ajax_ce_send_author_std_validation_email', array(
                $this,
                'ce_process_ajax'
            ));
            add_action('wp_ajax_ce_send_author_std_validation_email_confirmed', array(
                $this,
                'ce_process_ajax_bat_confirm'
            ));
            
            add_action('wp_ajax_chiefed_get_table_data', array(
                $this,
                'chiefed_get_datatables_data'
            ));
            add_action('wp_ajax_nopriv_chiefed_get_table_data', array(
                $this,
                'chiefed_get_datatables_data'
            ));
            
            add_action('network_admin_menu', array(
                $this,
                'chiefed_create_network_menus'
            ));
            add_action('network_admin_menu', array(
                $this,
                'update_chiefed_options'
            ));
            add_action('admin_menu', array(
                $this,
                'chiefed_create_menus'
            ));
            
            add_shortcode('chiefeditor_post_list', array(
                $this,
                'displayPostListWithStatuses_fn'
            ));
            
            // $this->init();
        }

        function chiefed_get_datatables_data()
        {
            $resultArray = array(
                'data' => array(
                    "Revue" => "Tiger Nixon",
                    "Auteur" => "System Architect"
                )
            );
            /*
             * search
             *
             * [search] => Array
             * (
             * [value] => mich
             * [regex] => false
             * )
             */
            
            CHIEFED_UTILS::getLogger()->debug("Refreshing data in datatable...");
            $search = $_POST['search']['value'];
            $timeframe = $_POST['timeframe'];
            $order = $_POST['order'];
            CHIEFED_UTILS::getLogger()->debug("Searching : " . $search . ' on timeframe: ' . $timeframe);
            // CHIEFED_UTILS::getLogger()->debug($_POST);
            
            $type = 'ce_pre_desktop_pub';
            $excludeStatuses = array();
            $args = array(
                'inside_shot_id' => '',
                'timeframe' => $timeframe,
                'search' => $search,
                'order' => $order
            );
            
            CHIEFED_UTILS::getLogger()->debug($args);
            $getPosts = 1;
            if ($getPosts) {
                $allItems = CHIEFED_UTILS::get_all_editor_items($type, $excludeStatuses, $args);
                $allItemsArray = EditorDashBoard::createTableFromForms($allItems);
                if (null != $allItemsArray && ! empty($allItemsArray['data'])) {
                    $resultArray['data'] = $allItemsArray['data'];
                } else {
                    $resultArray['data'] = array();
                }
            }
            
            // CHIEFED_UTILS::getLogger()->debug($resultArray['data']);
            
            if (! empty($order)) {
                
                $orderColumn = $order[0]['column'];
                $orderDir = $order[0]['dir'];
                $multisortDir = $orderDir == 'asc' ? SORT_ASC : SORT_DESC;
                // Obtient une liste de colonnes
                $toOrder = array();
                foreach ($resultArray['data'] as $key => $row) {
                    $toOrder[$key] = wp_strip_all_tags($row[$orderColumn]);
                }
                    
                    // Trie les données par volume décroissant, edition croissant
                    // Ajoute $data en tant que dernier paramètre, pour trier par la clé commune
                array_multisort($toOrder, $multisortDir, $resultArray['data']);
            }
            
            $result = json_encode($resultArray);
            // CHIEFED_UTILS::getLogger()->debug($result);
            echo $result;
            
            wp_die();
        }

        public function update_chiefed_options()
        {
            CHIEFED_UTILS::getLogger()->debug("update_chiefed_options");
            /*
             * pitch | Waiting for reception | #480000
             * assign | Received | #733f55
             * pending | Building (sent to DP) | #143850
             * future | Built | #5271be
             */
            CHIEFED_UTILS::getLogger()->debug($_REQUEST);
            if (! empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'chiefed_options_save')) {
                
                return $this->save_network_settings();
            } else {
                CHIEFED_UTILS::getLogger()->debug("Pb checking nonce");
            }
        }

        function ce_process_ajax_bat_confirm()
        {
            $pID = htmlspecialchars($_POST['postID']);
            $bID = htmlspecialchars($_POST['blogID']);
            
            if (is_multisite()) {
                $this->send_confirmation_email_to_author_of_post($bID, $pID, $this->options);
            } else {
                CHIEFED_UTILS::getLogger()->info("SEND BAT :: Single site install");
                $this->send_notification_email_to_author_of_post($bID, $pID, $this->options);
            }
            die();
        }

        function send_notification_email_to_author_of_post($blogID, $postID, $options)
        {
            CHIEFED_UTILS::getLogger()->info("$blogID, $postID, $options");
            $blog_url = get_site_url();
            
            // get post unique URL
            
            $current_post = get_post($postID);
            
            $post_title = $current_post->post_title;
            $permalink = get_permalink($postID);
            $post_author_id = $current_post->post_author;
            
            // get author email
            $user_info = get_userdata($post_author_id);
            $user_login = $user_info->user_login;
            $user_displayname = $user_info->display_name;
            $user_email = $user_info->user_email;
            
            /*
             * $fieldID = 'chiefed_editors_selector_' . $category->term_id;
             * $baseOptionChiefEds = get_option('chiefed_chiefeditor_option');
             * $fieldName = 'chiefed_chiefeditor_option[' . $category->term_id . ']';
             * $fieldNameArray = $baseOptionChiefEds[$category->term_id];
             */
            
            // check if main category present (need to send notification email to authors / editors)
            $mainCategory = CHIEFED_MANAGER_OPTION_PREFIX . 'main_notification_category';
            $mainCatName = get_cat_name($mainCategory);
            $postMayHasChiefEditor = false;
            $editors_in_chief_concerned = array();
            $postCategoryIds = array();
            if (has_category($mainCatName, $postID)) {
                $postMayHasChiefEditor = true;
                CHIEFED_UTILS::getLogger()->debug("Post in " . $mainCatName);
                
                // collect all other categories of post: 3, 5, 7
                $category_detail = get_the_category($postID); // $post->ID
                foreach ($category_detail as $cd) {
                    // echo $cd->cat_name;
                    $postCategoryIds[] = $cd->term_id;
                }
                CHIEFED_UTILS::getLogger()->debug($postCategoryIds);
                // collect all chief eds in options for these categories
                // 3 -> john doe
                // 5 -> john doe + davide brunelo
                // 7 -> bob michard + ken short
                CHIEFED_UTILS::getLogger()->debug("Option value");
                $baseOptionChiefEds = get_option('chiefed_chiefeditor_option');
                CHIEFED_UTILS::getLogger()->debug($baseOptionChiefEds);
                foreach ($postCategoryIds as $catId) {
                    CHIEFED_UTILS::getLogger()->debug($catId);
                    // $fieldName = 'chiefed_chiefeditor_option[' . $category->term_id . ']';
                    foreach ($baseOptionChiefEds as $categoryId => $chiefEditorIds) {
                        
                        if ($catId === $categoryId) {
                            CHIEFED_UTILS::getLogger()->debug($catId . ' === ' . $categoryId);
                            // CHIEFED_UTILS::getLogger()->debug($baseOptionChiefEds);
                            $editors_in_chief_concerned = array_merge($editors_in_chief_concerned, $chiefEditorIds);
                        }
                    }
                }
                CHIEFED_UTILS::getLogger()->debug($editors_in_chief_concerned);
                
                $editors_in_chief_concerned = array_unique($editors_in_chief_concerned);
                CHIEFED_UTILS::getLogger()->debug($editors_in_chief_concerned);
            }         
            
            $recipients_array = array();
            // build mail content with std text
            $recipients_array[] = $user_email;
            
            $current_user = wp_get_current_user();
            if ($current_user instanceof WP_User) {
                $recipients_array[] = $current_user->user_email;
                CHIEFED_UTILS::getLogger()->debug('Adding current user to email recipient : ' . $current_user->user_email);
            }
            
            $recipients_array = array_merge($recipients_array, explode(',', get_option('email_recipients')));
            
            $multiple_to_recipients = $user_email . ',' . get_option('email_recipients');
            foreach ($editors_in_chief_concerned as $new_user_id) {
                $user_info = get_userdata($new_user_id);
                $user_email = $user_info->user_email;
                $recipients_array[] = $user_email;
                CHIEFED_UTILS::getLogger()->debug('Adding chief editor : ' . $user_email);
            }
            
            $recipients_array = array_unique($recipients_array);
            CHIEFED_UTILS::getLogger()->debug($recipients_array);
            CHIEFED_UTILS::getLogger()->debug("-------------");
            $recipients_array = array_values(array_filter($recipients_array));
            CHIEFED_UTILS::getLogger()->debug("-------------");
            CHIEFED_UTILS::getLogger()->debug($recipients_array);
            CHIEFED_UTILS::getLogger()->debug("-------------");
            $multiple_to_recipients = implode(',', $recipients_array);
            CHIEFED_UTILS::getLogger()->debug('All recipients of ready for printing email : ' . $multiple_to_recipients);
            
            $msg_object = __("In Press", 'chief-editor') . ' : ' . $post_title;
            
            // add other email recipients
            $sender_email = get_option('chiefed_sender_email');
            $sender_name = get_option('chiefed_sender_name');
            
            if (empty($sender_email) || empty($sender_name)) {
                $message_to_user = __("Please fill in sender name and email in settings", 'chief-editor');
                CHIEFED_UTILS::getLogger()->debug($message_to_user);
                echo $message_to_user;
                return;
            }
            
            // send email to recipents
            $headers[] = "From: " . $sender_name . " <" . $sender_email . ">";
            $headers[] = "Content-type: text/html";
            
            $search = array(
                '/%username%/',
                '/%userlogin%/',
                '/%useremail%/',
                '/%postlink%/',
                '/%posttitle%/',
                '/%blogurl%/',                
                '%lostpassword_url%'
            );
            
            $replace = array(
                $user_displayname,
                $user_login,
                ($user_email == "" ? "no email" : $user_email),
                $permalink,
                $post_title,
                $blog_url,               
                wp_lostpassword_url( $permalink ),
            );
            
            CHIEFED_UTILS::getLogger()->debug($search);
            CHIEFED_UTILS::getLogger()->debug($replace);
            
            
            $option_name = 'chiefed_wpeditor_' . '_email_content';
            $msg_content = preg_replace($search, $replace, html_entity_decode(get_option($option_name)));
            CHIEFED_UTILS::getLogger()->error($msg_content);
            $msg_content = stripslashes_deep($msg_content);
            CHIEFED_UTILS::getLogger()->error($msg_content);
            if (empty($msg_content)){
                CHIEFED_UTILS::getLogger()->error("Empty message body!");
                $message_to_user = __('No message body set, please go to Settings and type an email body.', 'chief-editor');
            } else {
                $success = wp_mail($recipients_array, $msg_object, $msg_content, $headers);
            
                if ($success){
                    $message_to_user = __('Email sent successfully', 'chief-editor') . ' ' . __('to', 'chief-editor') . "\n" . $multiple_to_recipients;
                } else {
                    $message_to_user = __('Problem sending email...', 'chief-editor') . "\n" . $multiple_to_recipients . "\n" . $msg_object . "\n" . $msg_content . "\n" . "From " . $sender_name . "<" . $sender_email . ">";
                }
            }
            echo $message_to_user;
        }
        
        function send_confirmation_email_to_author_of_post($blogID, $postID, $options)
        {
            // echo "need to send BAT for blog ".$blogID." fo post ".$postID;
            // echo "send_confirmation_email_to_author_of_post";
            $blog_url = get_site_url($blogID);
            
            // get post unique URL
            
            switch_to_blog($blogID);
            
            $current_post = get_post($postID);
            
            $post_title = $current_post->post_title;
            $permalink = get_permalink($postID);
            $post_author_id = $current_post->post_author;
            
            // get author email
            $user_info = get_userdata($post_author_id);
            $user_login = $user_info->user_login;
            $user_displayname = $user_info->display_name;
            $user_email = $user_info->user_email;
            
            $chief_editor_option_name = 'select_blog_' . $blogID . '_chief_editor';
            $editors_in_chief_concerned = get_site_option($chief_editor_option_name);
            
            restore_current_blog();
            
            // CHIEFED_UTILS::getLogger()->debug('send_confirmation_email_to_author_of_post::');
            // CHIEFED_UTILS::getLogger()->debug($editors_in_chief_concerned);
            $recipients_array = array();
            // build mail content with std text
            $recipients_array[] = $user_email;
            
            $current_user = wp_get_current_user();
            if ($current_user instanceof WP_User) {
                $recipients_array[] = $current_user->user_email;
                CHIEFED_UTILS::getLogger()->debug('Adding current user to email recipient : ' . $current_user->user_email);
            }
            
            $recipients_array = array_merge($recipients_array, explode(',', get_site_option('email_recipients')));
            
            $multiple_to_recipients = $user_email . ',' . get_site_option('email_recipients');
            foreach ($editors_in_chief_concerned as $new_user_id) {
                $user_info = get_userdata($new_user_id);
                $user_email = $user_info->user_email;
                $recipients_array[] = $user_email;
                CHIEFED_UTILS::getLogger()->debug('Adding chief editor : ' . $user_email);
            }
            
            $recipients_array = array_unique($recipients_array);
            CHIEFED_UTILS::getLogger()->debug($recipients_array);
            CHIEFED_UTILS::getLogger()->debug("-------------");
            $recipients_array = array_values(array_filter($recipients_array));
            CHIEFED_UTILS::getLogger()->debug("-------------");
            CHIEFED_UTILS::getLogger()->debug($recipients_array);
            CHIEFED_UTILS::getLogger()->debug("-------------");
            $multiple_to_recipients = implode(',', $recipients_array);
            CHIEFED_UTILS::getLogger()->debug('All recipients of ready for printing email : ' . $multiple_to_recipients);
            
            $msg_object = __("In Press", 'chief-editor') . ' : ' . $post_title;
            
            // add other email recipients
            $sender_email = get_site_option('sender_email');
            $sender_name = get_site_option('sender_name');
            
            if (empty($sender_email) || empty($sender_name)) {
                $message_to_user = __("Please fill in sender name and email in network settings", 'chief-editor');
                CHIEFED_UTILS::getLogger()->debug($message_to_user);
                echo $message_to_user;
                return;
            }
            
            // send email to recipents
            $headers[] = "From: " . $sender_name . " <" . $sender_email . ">";
            $headers[] = "Content-type: text/html";
            
            $search = array(
                '/%username%/',
                '/%userlogin%/',
                '/%useremail%/',
                '/%postlink%/',
                '/%posttitle%/',
                '/%blogurl%/',
                '/%n%/'
            );
            
            $replace = array(
                $user_displayname,
                $user_login,
                ($user_email == "" ? "no email" : $user_email),
                $permalink,
                $post_title,
                $blog_url,
                "\n"
            );
            
            $msg_content = preg_replace($search, $replace, get_site_option('email_content-textarea'));
            $msg_content = stripslashes_deep($msg_content);
            $success = wp_mail($recipients_array, $msg_object, $msg_content, $headers);
            
            // send confirmation for ajax callback
            $message_to_user = $success ? __('Email sent successfully', 'chief-editor') . ' ' . __('to', 'chief-editor') . "\n" . $multiple_to_recipients : __('Problem sending email...', 'chief-editor') . "\n" . $multiple_to_recipients . "\n" . $msg_object . "\n" . $msg_content . "\n" . "From " . $sender_name . "<" . $sender_email . ">";
            // . $multiple_to_recipients .'\n' . $msg_object.'\n' . $headers'\n' . $msg_content;
            
            // CHIEFED_UTILS::getLogger()->debug($message_to_user);
            echo $message_to_user;
        }

        function ce_process_ajax()
        {
            // CHIEFED_UTILS::getLogger()->debug($_POST);
            $pID = htmlspecialchars($_POST['postID']);
            $aID = htmlspecialchars($_POST['authorID']);
            $bID = htmlspecialchars($_POST['blogID']);
            
            switch_to_blog($bID);
            
            $current_post = get_post($pID);
            
            $title = $current_post->post_title;
            $recipients_array = array();
            // get_userdata( $userid );
            $user_info = get_userdata($aID);
            if (false == $user_info) {
                CHIEFED_UTILS::getLogger()->error("ERROR::No user data for id " . $aID);
            } else {
                $userlogin = $user_info->user_login;
                $userdisplayname = $user_info->display_name;
                $user_email = $user_info->user_email;
                $recipients_array[] = $user_email;
                CHIEFED_UTILS::getLogger()->debug('+++ Adding author email : ' . $user_email);
            }
            restore_current_blog();
            
            // build mail content with std text
            // $recipients_array[] = $user_email;
            $cc_emails = get_site_option('email_recipients');
            if (! empty($cc_emails)) {
                CHIEFED_UTILS::getLogger()->debug('+++ Adding cc emails : ' . $cc_emails);
                $recipients_array = array_merge($recipients_array, explode(',', $cc_emails));
            }
            $chief_editor_option_name = 'select_blog_' . $bID . '_chief_editor';
            $editors_in_chief_concerned = get_site_option($chief_editor_option_name);
            
            // $multiple_to_recipients = $user_email.','.get_site_option('email_recipients');
            foreach ($editors_in_chief_concerned as $new_user_id) {
                $user_info = get_userdata($new_user_id);
                $user_email = $user_info->user_email;
                $recipients_array[] = $user_email;
                CHIEFED_UTILS::getLogger()->debug('+++ Adding chief editor : ' . $user_email);
            }
            
            // CHIEFED_UTILS::getLogger()->debug($recipients_array);
            $recipients_array = array_unique($recipients_array);
            // CHIEFED_UTILS::getLogger()->debug($recipients_array);
            $recipients_array = array_values(array_filter($recipients_array));
            
            CHIEFED_UTILS::getLogger()->debug($recipients_array);
            $prefix = $bID . '_' . $pID . '_';
            echo '<div id="' . $prefix . 'chief-editor-bat-form-send" class="chief-editor-bat-form-send" action="" method="POST">';
            echo __('Are you sure you want to sent "ready for printing" email?', 'chief-editor') . '<br/>';
            echo '<b>' . $title . '</b><br/>';
            
            $emailList = '<ul>';
            
            foreach ($recipients_array as $user_email) {
                $user = get_user_by('email', $user_email);
                $user_display = $user->display_name;
                $emailList .= '<li>' . $user_display . '<span style="font-size:0.7em;">( ' . $user_email . ' )</span>' . '</li>';
            }
            
            $emailList .= '</ul>';
            
            echo $emailList;
            echo '<input type="hidden" id="postID" name="postID" value="' . $pID . '">';
            echo '<input type="hidden" id="blogID" name="blogID" value="' . $bID . '">';
            
            echo '<input type="submit" id="' . $prefix . 'chiefed_bat_send_confirm" name="chiefed_bat_send_confirm_name" class="chiefed_bat_send_confirm button-primary" value="';
            echo __('Send', 'chief-editor') . '"/>';
            $loading_image = CHIEF_EDITOR_PLUGIN_URL . '/images/loading_ring_fullframe.gif';
            echo '<img id="' . $prefix . 'ce_loading_icon" class="ce_loading_icon" src="' . $loading_image . '" style="width:22px;height:auto;display:none;"></img>';
            echo '</div>';
            CHIEFED_UTILS::getLogger()->debug("-------------");
            die();
        }

        function load_settings()
        {
            $this->general_settings = (array) get_option($this->general_settings_key);
            $this->advanced_settings = (array) get_option($this->advanced_settings_key);
            
            // Merge with defaults
            $this->general_settings = array_merge(array(
                'general_option' => 'General value'
            ), $this->general_settings);
            
            $this->advanced_settings = array_merge(array(
                'advanced_option' => 'Advanced value'
            ), $this->advanced_settings);
        }

        function register_general_settings()
        {
            if (current_user_can('edit_others_posts')) {
                $this->chief_editor_settings_tabs[$this->general_settings_key] = __('Posts', 'chief-editor');
            }
            
            if (current_user_can('delete_others_pages')) {
                $post_types = self::getAllCustomPostTypes();
                
                foreach ($post_types as $post_type) {
                    
                    // echo '<p>' . $post_type . '</p>';
                    // CHIEFED_UTILS::getLogger()->debug($post_type );
                    $element_name = 'checkbox_' . $post_type;
                    $checked = (get_site_option($element_name) == 1);
                    // CHIEFED_UTILS::getLogger()->debug('"'.$this->options[$element_name].'"');
                    // CHIEFED_UTILS::getLogger()->debug($post_type .' => '.$element_name. ' : '.$this->options[$element_name] . ' ' .$checked);
                    if ($checked) {
                        $this->custom_post_type_keys[] = $post_type;
                        $this->chief_editor_settings_tabs[$post_type] = __($post_type, 'chief-editor');
                    }
                }
            }
        }

        function register_calendar_tab()
        {
            if (current_user_can('delete_others_pages')) {
                $this->chief_editor_settings_tabs[$this->calendar_settings_key] = __('Calendar', 'chief-editor');
            }
        }

        function section_general_desc()
        {
            echo 'General section description goes here.';
        }

        function field_general_option()
        {
            ?>
<input type="text"
	name="<?php echo $this->general_settings_key; ?>[general_option]"
	value="<?php echo esc_attr( $this->general_settings['general_option'] ); ?>" />
<?php
        }

        function register_advanced_settings()
        {
            if (current_user_can('delete_others_pages')) {
                $this->chief_editor_settings_tabs[$this->advanced_settings_key] = __('Comments', 'chief-editor');
            }
        }

        function register_stats_tab()
        {
            if (current_user_can('delete_others_pages')) {
                $this->chief_editor_settings_tabs[$this->stats_key] = __('Authors', 'chief-editor');
                $this->chief_editor_settings_tabs[$this->custom_stats_key] = __('Custom Stats', 'chief-editor');
            }
        }

        function register_options_tab()
        {
            if (current_user_can('edit_users')) {
                $this->chief_editor_settings_tabs[$this->chief_editor_options_key] = __('Settings', 'chief-editor');
            }
        }

        function section_advanced_desc()
        {
            echo 'Advanced section description goes here.';
        }

        function field_advanced_option()
        {
            ?>
<input type="text"
	name="<?php echo $this->advanced_settings_key; ?>[advanced_option]"
	value="<?php echo esc_attr( $this->advanced_settings['advanced_option'] ); ?>" />
<?php
        }

        function add_admin_menus()
        {
            global $chief_editor_settings;
            if (current_user_can('edit_others_posts')) {
                
                // $chief_editor_settings = add_options_page( 'Chief Editor Settings', 'Chief Editor', 'read', $this->chief_editor_dashboard_page_name, array( $this, 'chief_editor_options_page' ) );
                $chief_editor_settings = add_menu_page('Chief Editor Dashboard', 'Chief Editor', 'read', $this->chief_editor_dashboard_page_name, array(
                    $this,
                    'chief_editor_options_page'
                ), 'dashicons-book-alt');
            }
        }

        function render_post_table_front_end($data, $type)
        {
            static $chiefedFrontListTable;
            if (! isset($chiefedFrontListTable)) {
                require_once (ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
                require_once (ABSPATH . 'wp-admin/includes/screen.php');
                require_once (ABSPATH . 'wp-admin/includes/class-wp-screen.php');
                require_once (ABSPATH . 'wp-admin/includes/template.php');
                $chiefedFrontListTable = new ChiefEditor_Table();
            }
            
            $chiefedFrontListTable->post_type = $type;
            $chiefedFrontListTable->data = $data;
            $chiefedFrontListTable->prepare_items();
            
            // $chiefedFrontListTable->search_box( 'search', 'search_id' );
            echo Chief_Editor_Meta_Boxes::create_statuses_color_scale();
            
            echo '<div class="chiefed_frontend_postlist_container">';
            $chiefedFrontListTable->display();
            echo '</div>';
            // echo '</form></div>';
        }

        function render_post_table($data)
        {
            $postListTable = new ChiefEditor_Table();
            echo '<div class="wrap"><h2>' . __('All network posts', 'chief-editor') . '</h2>';
            $postListTable->data = $data;
            $postListTable->prepare_items();
            $stats = $postListTable->totalItems . ' posts<br/>';
            echo $stats;
            $postListTable->display();
            echo '</div>';
        }

        function displayPostListWithStatuses_fn($atts)
        {
            $defaultsParameters = array(
                'type' => 'post'
            );
            extract(shortcode_atts($defaultsParameters, $atts));
            
            $type = str_replace(' ', '', $type);
            $this->displayPostListWithStatuses($type);
        }

        function displayPostListWithStatuses($type = 'post')
        {
            CHIEFED_UTILS::getLogger()->debug('### ' . $type . ' ###');
            
            // c function get_all_editor_items($post_type = 'post', $excludeStatuses = array('publish','inherit','auto-draft','trash'), $args)
            $allPosts = CHIEFED_UTILS::get_all_editor_items($type);
            if (count($allPosts) == 0) {
                echo '<p>' . __('No custom posts of type ', 'chief-editor') . ' ' . $type . '</p>';
                echo '<p>' . __('or', 'chief-editor') . '</p>';
                echo '<p>' . __('all of them are published', 'chief-editor') . '</p>';
            }
            if (is_admin()) {
                $this->render_post_table($allPosts);
            } else {
                $this->render_post_table_front_end($allPosts, $type);
            }
        }

        function chief_editor_options_page()
        {
            $current_tab = isset($_GET['tab']) ? $_GET['tab'] : $this->general_settings_key;
            // screen_icon();
            echo '<div style="text-align:center;padding:5px;">';
            echo screen_icon() . '<h1>Chief Editor</h1>';
            if (current_user_can('delete_others_pages')) {
                echo '<a class="button-primary" href="http://wordpress.org/plugins/chief-editor/" target="_blank">' . __('Visit Plugin Site', 'chief-editor') . '</a>';
                echo '<a  class="button-primary" style="color:#FFF600;" href="http://wordpress.org/support/view/plugin-reviews/chief-editor" target="_blank">' . __('Rate!', 'chief-editor') . '</a>';
                // echo 'by <a href="http://www.maxiblog.fr" target="_blank">max</a>, a <a href="http://www.maxizone.fr" target="_blank">music lover</a>';
            }
            echo '</div> ';
            
            echo '<h2 class="nav-tab-wrapper">';
            foreach ($this->chief_editor_settings_tabs as $tab_key => $tab_caption) {
                $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
                echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->chief_editor_dashboard_page_name . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
            }
            echo '</h2>';
            
            if ($current_tab == 'chief_editor_posts_tab') {
                $this->displayPostListWithStatuses();
            } elseif (in_array($current_tab, $this->custom_post_type_keys)) {
                // echo $current_tab;
                $allPosts = CHIEFED_UTILS::get_all_editor_items($current_tab);
                if (count($allPosts) == 0) {
                    echo '<p>' . __('No custom posts of type ', 'chief-editor') . '<b>' . $current_tab . '</b></p>';
                    echo '<p>' . __('or', 'chief-editor') . '</p>';
                    echo '<p>' . __('all of them are published', 'chief-editor') . '</p>';
                }
                $this->render_post_table($allPosts);
            } elseif ($current_tab == 'chief_editor_calendar_tab') {
                
                $this->create_calendar_table();
                // }
            } elseif ($current_tab == 'chief_editor_comments_tab') {
                
                // $this->recent_multisite_comments();
                global $wpdb;
                $last_month = mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"));
                $start_date = date('Y-m-d H:i:s', $last_month);
                $end_date = date('Y-m-d H:i:s');
                $intro_text = '<h3>' . __('All comments accross the network since ', 'chief-editor') . $start_date . '</h3><br/>';
                
                if (is_multisite()) {
                    
                    echo '<table>';
                    echo '<tr>';
                    echo '<td>';
                    // $mostCommentedPosts = $this->getMostCommentedPosts(10);
                    echo '<h3>' . __('Most commented posts ever') . '</h3><br/>' . $this->getMostCommentedPosts(10);
                    echo '</td>';
                    echo '<td>';
                    // $lastMonthIdx = date('m', strtotime('-1 month'));
                    $last_month_most_commented = mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"));
                    $current_month = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
                    $startDate = date('Y-m-01 H:i:s', $last_month_most_commented);
                    $endDate = date('Y-m-01 H:i:s', $current_month);
                    $mostCommentedPosts = $this->getMostCommentedPosts(10, $startDate, $endDate);
                    $allCommentsEver = $this->countAllCommentsMultisite();
                    echo '<h4>' . __('Total approved comments ever') . ' : ' . $allCommentsEver . '</h4>';
                    echo '<h3>' . __('Most commented posts last month') . '</h3><br/>' . $startDate . ' -> ' . $endDate . '<br/>' . $mostCommentedPosts;
                    echo '</td>';
                    echo '</tr>';
                    echo '</table>';
                    $allCommentsThisMonth = $this->getAllCommentsMultisite('1000', $start_date, $end_date);
                } else {
                    $selects = "SELECT comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content FROM wp_comments
WHERE comment_date >= '{$start_date}'
AND comment_date < '{$end_date}'
ORDER BY comment_date_gmt DESC LIMIT 1000";
                    
                    // real number is (number * # of blogs)
                    $allCommentsThisMonth = $wpdb->get_results($selects);
                }
                
                echo $intro_text . ' ' . count($allCommentsThisMonth) . __(' item(s)', 'chief-editor');
                echo $this->formatCommentsFromArray($allCommentsThisMonth);
            } elseif ($current_tab == 'chief_editor_stats_tab') {
                
                $this->bm_author_stats("alltime");
            } elseif ($current_tab == 'ched_custom_stats_tab') {
                
                $start = esc_attr(get_site_option('custom_stats_start_date')); // "2015-05-01";
                $end = esc_attr(get_site_option('custom_stats_end_date')); // "2015-05-31";
                CHIEFED_UTILS::getLogger()->debug($start . ' => ' . $end);
                
                $startDateTime = DateTime::createFromFormat('dmY', $start);
                $endDateTime = DateTime::createFromFormat('dmY', $end);
                
                CHIEFED_UTILS::getLogger()->debug($startDateTime);
                CHIEFED_UTILS::getLogger()->debug($endDateTime);
                // $startDate = date('Y-m-d H:i:s', $start );
                // $endDate = date('Y-m-d H:i:s', $end);
                if ($startDateTime && $endDateTime) {
                    echo '<h2>' . __('Statistics', 'chief-editor') . ' ' . __('from', 'chief-editor') . ' ' . $startDateTime->format('Y-M-d') . ' ' . __('to', 'chief-editor') . ' ' . $endDateTime->format('Y-M-d') . '</h2>';
                } else {
                    echo '<h2>' . __('cannot format dates correctly, please use format d m Y in settings', 'chief-editor') . '</h2>';
                }
                
                $this->ched_custom_author_stats("alltime", $startDateTime, $endDateTime);
            } elseif ($current_tab == 'chief_editor_settings_tab') {}
            if (current_user_can('delete_others_pages')) {
                echo '<div style="text-align:right;">';
                echo 'by <a href="http://www.maxiblog.fr" target="_blank">max</a>, a <a href="http://www.maxizone.fr" target="_blank">music lover</a>';
                echo '</div> ';
            }
        }

        public function get_comments_number_for_blog($blogid, $postid)
        {
            switch_to_blog($blogid);
            $result = get_comments_number($postid);
            restore_current_blog();
            return $result;
        }

        function postBetweenDates($post, $startDate, $endDate)
        {
            $format = 'Y-m-d';
            $postDate = get_the_time($format, $post->ID);
            $post_date = new DateTime($postDate);
            $start_date = new DateTime($startDate);
            $end_date = new DateTime($endDate);
            
            if ($post_date >= $start_date && $post_date <= $end_date) {
                
                return true;
            } else {
                return false;
            }
        }

        function getAllPostsOfAllBlogs($startDate = NULL, $endDate = NULL)
        {
            $network_sites = get_sites();
            
            $result = array();
            foreach ($network_sites as $network_site) {
                
                $blog_id = $network_site->blog_id;
                
                switch_to_blog($blog_id);
                
                $allPostsOfCurrentBlog = get_posts(array(
                    'numberposts' => - 1,
                    'posts_per_page' => - 1,
                    'post_type' => 'post',
                    'post_status' => array(
                        'publish',
                        'future'
                    )
                ));
                
                if ($startDate != NULL && $endDate != NULL) {
                    
                    foreach ($allPostsOfCurrentBlog as $post) {
                        if ($this->postBetweenDates($post, $startDate, $endDate)) {
                            $result[$blog_id][] = $post;
                        }
                    }
                } else {
                    $result[$blog_id] = $allPostsOfCurrentBlog;
                }
                
                // Switch back to the main blog
                restore_current_blog();
            }
            
            return $result;
        }

        public function getMostCommentedPosts($maxResults, $startDate = NULL, $endDate = NULL)
        {
            $blog_posts_array = $this->getAllPostsOfAllBlogs($startDate, $endDate);
            $postCommentsArray = array();
            $postCommentsTitles = array();
            $postCommentsPermalinks = array();
            // echo 'count($blog_posts_array) '.count($blog_posts_array) ;
            foreach ($blog_posts_array as $blogid => $postsOfBlog) {
                
                foreach ($postsOfBlog as $post) {
                    // echo "<br/>$blogid, $post->ID";
                    $nbOfComments = $this->get_comments_number_for_blog($blogid, $post->ID);
                    $postCommentsArray[$blogid . '_' . $post->ID] = $nbOfComments;
                    $postCommentsTitles[$blogid . '_' . $post->ID] = $post->post_title;
                    $postCommentsPermalinks[$blogid . '_' . $post->ID] = get_blog_permalink($blogid, $post->ID);
                }
            }
            $result = '<h4>' . __('Total number of posts accross network: ', 'chief-editor') . count($postCommentsArray) . '</h4>';
            $sortResult = arsort($postCommentsArray);
            // echo '$sorted : '.count($postCommentsArray);
            if ($sortResult) {
                
                $postComments = '<ol>';
                $idx = 1;
                foreach ($postCommentsArray as $key => $value) {
                    
                    if ($value) {
                        
                        $postComments .= '<li><a target="_blank" href="' . $postCommentsPermalinks[$key] . '">' . $postCommentsTitles[$key] . '</a> | #comments : ' . $value . '</li>';
                        if ($idx == $maxResults) {
                            break;
                        }
                        $idx += 1;
                    }
                }
                $postComments .= '</ol>';
                $result .= $postComments;
            } else {
                $result .= 'problem sorting...';
            }
            
            return $result;
        }

        // sitewide settings
        
        // multisite wide settings
        /*
         * private function init()
         * {
         *
         * // Adds settings to Network Settings
         * add_filter('wpmu_options', array(
         * $this,
         * 'show_network_settings'
         * ));
         * add_action('update_wpmu_options', array(
         * $this,
         * 'save_network_settings'
         * ));
         * }
         */
        public static function save_network_settings()
        {
            
            // $posted_settings = array_map( 'sanitize_text_field', $_POST['chief-editor'] );
            CHIEFED_UTILS::getLogger()->debug('==> saving settings');
            CHIEFED_UTILS::getLogger()->debug($_POST['chief-editor']);
            foreach ($_POST['chief-editor'] as $settingKey => $settingValue) {
                // if ($settingItem)
                
                CHIEFED_UTILS::getLogger()->debug('==> saving ' . $settingKey . ' =>' . $settingValue);
                if (strpos($settingKey, 'textarea') !== false) {
                    
                    $posted_settings[$settingKey] = stripslashes(wp_filter_post_kses(addslashes($settingValue)));
                } else if (strpos($settingKey, 'select') !== false) {
                    CHIEFED_UTILS::getLogger()->debug('Saving SELECT : ' . $settingKey . ' =>' . $settingValue);
                    $posted_settings[$settingKey] = $settingValue;
                } else if (strpos($settingKey, 'checkbox') !== false) {
                    
                    CHIEFED_UTILS::getLogger()->debug('Saving : ' . $settingKey . ' =>' . $settingValue);
                    $posted_settings[$settingKey] = $settingValue; // == 'on' ? 1 : 0;
                } else {
                    $posted_settings[$settingKey] = sanitize_text_field($settingValue);
                }
            }
            
            $settings = self::get_network_settings();
            foreach ($settings as $setting) {
                $isCheckbox = boolval(strpos($setting['type'], 'checkbox') !== false);
                $inArray = boolval(array_key_exists($setting['id'], $posted_settings));
                CHIEFED_UTILS::getLogger()->debug($setting['id'] . ' : ' . $isCheckbox . ' ' . $inArray);
                if (! $inArray && $isCheckbox) {
                    
                    // set to false:
                    $posted_settings[$setting['id']] = 0; // $posted_settings[$settingKey] == 1 ? 1 : 0;
                }
            }
            
            // CHIEFED_UTILS::getLogger()->debug($posted_settings);
            
            foreach ($posted_settings as $name => $value) {
                // $valueToSave = esc_html($value);
                update_site_option($name, $value);
            }
            
            self::$updated = true;
        }

        function chiefed_create_network_menus()
        {
            CHIEFED_UTILS::getLogger()->debug("chiefed_create_network_menus");
            
            $pageTitle = __('Chief Editor Settings', 'chief-editor');
            $menuTitle = __('Chief Editor');
            $capability = 'manage_options';
            $slug = self::$slug;
            
            CHIEFED_UTILS::getLogger()->debug($pageTitle);
            
            add_submenu_page('settings.php', $pageTitle, $menuTitle, $capability, $slug, array(
                $this,
                'show_network_settings'
            ));
            return $this;
        }

        function chiefed_create_menus()
        {
            CHIEFED_UTILS::getLogger()->debug("chiefed_create_menus");
            
            $pageTitle = __('Chief Editor Settings', 'chief-editor');
            $menuTitle = __('Chief Editor');
            $capability = 'manage_options';
            $slug = self::$slug;
            
            CHIEFED_UTILS::getLogger()->debug($pageTitle);
            
            add_options_page($pageTitle, $menuTitle, $capability, $slug, array(
                $this,
                'show_single_site_settings'
            ));
        }

        public static function show_single_site_settings()
        {
            ?>

<div class="wrap">
	<div id="icon-themes" class="icon32"></div>
	<h2><?php _e('Chief Editor Settings - multisite', 'chief-editor'); ?></h2>
    <?php settings_errors(); ?>
    <?php if ( 0 ) : ?>
    <div class="updated notice is-dismissible">
		<p><?php _e('Settings updated successfully!', 'chief-editor'); ?></p>
	</div>
    <?php endif;
            
            $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'chiefed_print_options';
            $blog_id = get_current_blog_id();
            ?>


    <h2 class="nav-tab-wrapper">

		<a href="?page=<?php echo self::$slug;?>&tab=chiefed_sys_requirements"
			class="nav-tab <?php echo $active_tab == 'chiefed_sys_requirements' ? 'nav-tab-active' : ''; ?>"><?php echo __('System Requirements'); ?></a>
		<a href="?page=<?php echo self::$slug;?>&tab=chiefed_print_options"
			class="nav-tab <?php echo $active_tab == 'chiefed_print_options' ? 'nav-tab-active' : ''; ?>"><?php echo __('Print Workflow'); ?></a>
		<a href="?page=<?php echo self::$slug;?>&tab=chiefed_web_options"
			class="nav-tab <?php echo $active_tab == 'chiefed_web_options' ? 'nav-tab-active' : ''; ?>"><?php echo __('Web Workflow'); ?></a>
		<a href="?page=<?php echo self::$slug;?>&tab=chiefed_calendar_options"
			class="nav-tab <?php echo $active_tab == 'chiefed_calendar_options' ? 'nav-tab-active' : ''; ?>"><?php echo __('Calendar options'); ?></a>
		<?php if (CHIEFED_DEBUG_OPTIONS) { ?>
		<a href="?page=<?php echo self::$slug;?>&tab=chiefed_all_options"
			class="nav-tab <?php echo $active_tab == 'chiefed_all_options' ? 'nav-tab-active' : ''; ?>"><?php echo __('All Options'); ?></a>
        <?php }  ?>
    </h2>
    <?php
            if ($active_tab == 'chiefed_print_options') {
                ?>
   
    <?php CHIEFED_ADMIN::chief_editor_print_settings(); ?>
    <?php
            } else if ($active_tab == 'chiefed_web_options') {
                ?>
  
    <?php CHIEFED_ADMIN::chief_editor_web_settings(); ?>
    <?php
            } else if ($active_tab == 'chiefed_calendar_options') {
                ?>
                 <?php CHIEFED_ADMIN::chief_editor_calendar_settings(); ?>
    <?php
            } else if ($active_tab == 'chiefed_sys_requirements') {
                ?>
   
    <?php CHIEFED_ADMIN::system_requirements(); ?>
 <?php
            } else if ($active_tab == 'chiefed_all_options') {
                ?>
    <h2><?php _e('All options', 'chief-editor'); ?></h2>
    <?php CHIEFED_ADMIN::chiefed_list_all_options(); ?>
    <noscript>
		<p>
			<em><?php _e( 'You must enable Javascript in order to proceed!', 'drug-interactions' ) ?></em>
		</p>
	</noscript>

	</form>


<?php
            }
            ?>
</div>

<?php
        }

        public static function show_network_settings()
        {
            ?>

<div class="wrap">
	<div id="icon-themes" class="icon32"></div>
	<h2><?php _e('Chief Editor Settings - multisite', 'chief-editor'); ?></h2>
    <?php settings_errors(); ?>
    <?php if ( 0 ) : ?>
    <div class="updated notice is-dismissible">
		<p><?php _e('Settings updated successfully!', 'chief-editor'); ?></p>
	</div>
    <?php endif;
            
            $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'chiefed_net_options';
            
            ?>


    <h2 class="nav-tab-wrapper">

		<a
			href="?page=<?php echo self::$slug;?>&tab=chiefed_network_legacy_options"
			class="nav-tab <?php echo $active_tab == 'chiefed_network_legacy_options' ? 'nav-tab-active' : ''; ?>"><?php echo __('Network Editorial'); ?></a>
		<a href="?page=<?php echo self::$slug;?>&tab=chiefed_net_options"
			class="nav-tab <?php echo $active_tab == 'chiefed_net_options' ? 'nav-tab-active' : ''; ?>"><?php echo __('InDesign Connector'); ?></a>
		<?php if (CHIEFED_DEBUG_OPTIONS) { ?>
		<a href="?page=<?php echo self::$slug;?>&tab=chiefed_all_options"
			class="nav-tab <?php echo $active_tab == 'chiefed_all_options' ? 'nav-tab-active' : ''; ?>"><?php echo __('All Options'); ?></a>
        <?php }  ?>
    </h2>
    <?php
            if ($active_tab == 'chiefed_net_options') {
                ?>
    <h2><?php _e('Settings', 'chief-editor'); ?></h2>
    <?php CHIEFED_ADMIN::chief_editor_web_settings(); ?>
    <?php
            } else if ($active_tab == 'chiefed_network_legacy_options') {
                ?>
    <h2><?php _e('System requirements', 'chief-editor'); ?></h2>
    <?php self::show_network_tab_settings(); ?>
  <?php
            } else if ($active_tab == 'chiefed_all_options') {
                ?>
    <h2><?php _e('All options', 'chief-editor'); ?></h2>
    <?php CHIEFED_ADMIN::chiefed_list_all_options(); ?>
    <noscript>
		<p>
			<em><?php _e( 'You must enable Javascript in order to proceed!', 'drug-interactions' ) ?></em>
		</p>
	</noscript>

	</form>


<?php
            }
            ?>
</div>

<?php
        }

        public static function show_network_tab_settings()
        {
            echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
            echo '<h2>Chief editor settings</h2>';
            if (self::$updated) :
                ?>
<div class="updated notice is-dismissible">
	<p><?php _e('Settings updated successfully!', 'chiefed'); ?></p>
</div>
<?php endif;
            
            $settings = self::get_network_settings();
            ?>
<form method="post">
	<table id="menu" class="form-table">
    <?php
            foreach ($settings as $setting) :
                $tag = $setting['tag'] ? $setting['tag'] : 'input';
                $type = esc_attr($setting['type']);
                $date = $setting['date'] == 1 ? true : false;
                // $callback = esc_attr($setting['callback']);
                ?>

    <tr valign="top">
			<th scope="row"><?php echo $setting['name']; ?></th>
			<td>
            <?php
                
                if ($type === 'select') {
                    $blog_id = esc_attr($setting['blog_id']);
                    $setting_id = esc_attr($setting['id']);
                    
                    // call_user_func($callback,$args);
                    $chief_editors_roles = array(
                        'contributor',
                        'author',
                        'editor'
                    );
                    $blogusers = array();
                    
                    foreach ($chief_editors_roles as $role) {
                        
                        $other_blogusers = get_users('blog_id=' . $blog_id . '&orderby=nicename&role=' . $role);
                        if ($other_blogusers) {
                            $blogusers = array_merge($blogusers, $other_blogusers);
                        }
                    }
                    
                    CHIEFED_UTILS::getLogger()->debug('Showing multiple select for blog ' . $blog_id . ' : ' . count($blogusers) . ' ' . count($chief_editors_roles) . ' : ');
                    
                    $fieldID = 'chief_editors_selector_' . $blog_id;
                    $fieldName = 'chief-editor[' . $setting_id . ']';
                    
                    printf('<select multiple="multiple" name="%s[]" id="%s" class="widefat" size="5" style="margin-bottom:10px">', $fieldName, $fieldID);
                    
                    // Each individual option
                    foreach ($blogusers as $user) {
                        $id = $user->ID;
                        $userEmail = $user->user_email;
                        $userLogin = $user->user_login;
                        $userNicename = $user->display_name;
                        CHIEFED_UTILS::getLogger()->debug("Setting selected back $id : $userEmail = ");
                        $checkedOptions = get_site_option($setting['id']);
                        CHIEFED_UTILS::getLogger()->debug($checkedOptions);
                        
                        printf('<option value="%s" %s style="margin-bottom:3px;">%s</option>', $id, in_array($id, $checkedOptions) ? 'selected="selected"' : '', $id . ' - ' . $userLogin . ' - ' . $userNicename . ' (' . $userEmail . ')');
                    }
                    
                    echo '</select>';
                    // $callback($args);
                } else {
                    $item = '<' . $tag . ' type="' . $type . '"';
                    $item .= ($setting['size'] ? ' size="' . esc_attr($setting['size']) . '"' : '');
                    $item .= ($setting['cols'] ? ' cols="' . esc_attr($setting['cols']) . '"' : '');
                    $item .= ($setting['rows'] ? ' rows="' . esc_attr($setting['rows']) . '"' : '');
                    $item .= ' name="chief-editor[' . esc_attr($setting['id']) . ']"';
                    if ($tag === 'textarea') {
                        // esc_textarea()
                        $savedValue = esc_textarea(stripslashes_deep(get_site_option($setting['id'])));
                        
                        // $item .= ' value="'.$savedValue.'"';
                        $item .= '>' . $savedValue . '</' . $tag . '>';
                    } else if ($type === 'checkbox') {
                        
                        $optionVal = get_site_option($setting['id']);
                        // $currentState = boolval($optionVal);
                        $checkPart = checked(1, $optionVal, false);
                        $item .= ' value="1" ' . $checkPart;
                        CHIEFED_UTILS::getLogger()->debug('$currentState : ' . $optionVal . ' html:' . $checkPart);
                        $item .= '/>';
                    } else if ($date) {
                        
                        $item .= ' class="datepicker" name="datepicker"';
                        $item .= ' value="' . esc_attr(get_site_option($setting['id'])) . '"';
                        $item .= '/>';
                    } else {
                        $item .= ' value="' . esc_attr(get_site_option($setting['id'])) . '"';
                        $item .= '/>';
                    }
                    
                    echo $item;
                }
                ?>
            <br /><?php echo '<em>'.$setting['desc'].'</em>'; ?>
        </td>
		</tr>
    <?php
            endforeach
            ;
            
            // echo CHIEFED_ADMIN::chief_editor_settings();
            
            echo '</table>';
            ?>
            <input type="hidden" name="_wpnonce"
			value="<?php echo wp_create_nonce('chiefed_options_save'); ?>" />
    <?php
            echo submit_button();
            echo '</form>';
            
            echo '</div>';
        }

        public static function get_network_settings()
        {
            $settings[] = array(
                'id' => 'sender_email',
                'name' => __('Sender email address', 'chief-editor'),
                'desc' => __('Email address used for sendings', 'chief-editor'),
                'type' => 'text',
                'size' => 'regular'
            );
            
            $settings[] = array(
                'id' => 'sender_name',
                'name' => __('Sender name', 'chief-editor'),
                'desc' => __('Name, as it will be seen by recipients', 'chief-editor'),
                'std' => 'regular',
                'type' => 'text'
            );
            
            $settings[] = array(
                'id' => 'email_recipients',
                'name' => __('Recipients emails', 'chief-editor'),
                'desc' => __('Addresses to which all email will be sent to (use , as separator)', 'chief-editor'),
                'std' => 'regular',
                'size' => '50',
                'type' => 'text'
            );
            
            $settings[] = array(
                'id' => 'custom_stats_start_date',
                'name' => __('Custom stats start date', 'chief-editor'),
                'desc' => __('The custom statistics will start from this date, please use dmY format', 'chief-editor'),
                'std' => 'regular',
                'size' => '50',
                'type' => 'text',
                'date' => 1
            );
            $settings[] = array(
                'id' => 'custom_stats_end_date',
                'name' => __('Custom stats end date', 'chief-editor'),
                'desc' => __('The custom statistics will end with this date, please use dmY format', 'chief-editor'),
                'std' => 'regular',
                'size' => '50',
                'type' => 'text',
                'date' => 1
            );
            
            $settings[] = array(
                'tag' => 'textarea',
                'rows' => '20',
                'cols' => '110',
                'id' => 'email_content-textarea',
                'name' => __('Email content', 'chief-editor'),
                'desc' => __('This is the standard email sent for to authors in order to validate the post', 'chief-editor') . '<br/>' . __('You can use the following tags inside:', 'chief-editor') . '<br/>' . '<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%username%</span>' . '<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%userlogin%</span>' . '<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%useremail%</span>' . '<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%postlink%</span>' . '<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%posttitle%</span>' . '<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%blogurl%</span>',
                'std' => '50',
                'type' => 'text'
            );
            
            $post_types = self::getAllCustomPostTypes();
            
            foreach ($post_types as $post_type) {
                $args = array(
                    'post_type' => $post_type
                );
                $element_name = 'checkbox_' . $post_type;
                $settings[] = array(
                    'id' => $element_name,
                    'name' => __('Show posts of type ', 'chief-editor') . '<br/><em>' . $post_type . '</em>',
                    'desc' => __('give you ability to manage posts of this type in a specific tab', 'chief-editor'),
                    'std' => 'regular',
                    // 'size' => '50',
                    'type' => 'checkbox'
                );
            }
            
            // ******
            
            // Iterate through your list of blogs
            foreach (get_sites() as $blog) {
                // foreach ($blog_ids as $blog_id){
                $blog_id = $blog->blog_id;
                
                // Switch to the next blog in the loop.
                // This will start at $id == 1 because of your ORDER BY statement.
                switch_to_blog($blog_id);
                
                // Get the 5 latest posts for the blog and store them in the $globalquery variable.
                // $globalquery = get_posts('numberposts=5&post_type=any');
                $blog_details = get_blog_details($blog_id);
                $blog_name = $blog_details->blogname;
                $setting_id = "select_blog_" . $blog_id . '_chief_editor';
                $args = array(
                    'blog_id' => $blog_id,
                    'setting_id' => $setting_id
                );
                
                // CHIEFED_UTILS::getLogger()->debug('Adding setting for blog '.$blog_name.' id '.$blog_id.' and setting id '.$setting_id);
                $settings[] = array(
                    'id' => $setting_id,
                    'name' => $blog_name,
                    'desc' => __('Set chief editor(s) for this blog', 'chief-editor'),
                    'std' => 'regular',
                    // 'size' => '50',
                    'type' => 'select',
                    'callback' => 'ce_blog_chief_editor_callback',
                    'blog_id' => $blog_id
                );
                
                // Switch back to the main blog
                restore_current_blog();
            }
            
            return apply_filters('chiefed_plugin_settings', $settings);
        }

        static function getAllCustomPostTypes()
        {
            $post_types = array();
            
            $args = array(
                
                '_builtin' => false
            );
            
            $output = 'names';
            // names or objects, note names is the default
            $operator = 'and';
            // 'and' or 'or'
            
            $post_types = array_merge($post_types, get_post_types($args, $output, $operator));
            
            return array_unique($post_types);
        }

        /**
         * Sanitize each setting field as needed
         *
         * @param array $input
         *            Contains all settings fields as array keys
         */
        public function sanitize($input)
        {
            $new_input = array();
            if (isset($input['sender_email']))
                $new_input['sender_email'] = sanitize_text_field($input['sender_email']);
            
            if (isset($input['sender_name']))
                $new_input['sender_name'] = sanitize_text_field($input['sender_name']);
            
            if (isset($input['email_recipients']))
                $new_input['email_recipients'] = sanitize_text_field($input['email_recipients']);
            
            if (isset($input['email_content']))
                $new_input['email_content'] = $input['email_content'];
            
            $args = array(
                /*'public'   => false,*/
                '_builtin' => false
            );
            
            $output = 'names';
            // names or objects, note names is the default
            $operator = 'and';
            // 'and' or 'or'
            
            $post_types = get_post_types($args, $output, $operator);
            
            foreach ($post_types as $post_type) {
                $element_name = 'checkbox_' . $post_type;
                if (isset($input[$element_name]))
                    $new_input[$element_name] = $input[$element_name];
            }
            
            foreach (get_sites() as $blog) {
                
                $blog_id = $blog['blog_id'];
                // switch_to_blog($blog_id);
                $setting_id = "blog_" . $blog_id . '_chief_editor';
                if (isset($input[$setting_id])) {
                    $new_input[$setting_id] = $input[$setting_id];
                }
            }
            
            CHIEFED_UTILS::getLogger()->debug($new_input);
            
            return $new_input;
        }

        /**
         * Print the Section text
         */
        public function ce_print_section_info()
        {
            print __('The following settings are used to send pre-formatted email to post authors, in order for them to validate it online before publishing', 'chief-editor');
        }

        public function ce_print_section_custom_post()
        {
            print __('This section allow you to select which custom post types are going to presented in a separate tab for scheduling', 'chief-editor');
        }

        public function ce_print_section_editors_info()
        {
            print __('Attribute Chief editors to each blog in order for them to receive', 'chief-editor') . ' ' . __('all', 'chief-editor') . ' ' . __('ready for printing', 'chief-editor') . ' ' . __('notifications', 'chief-editor');
        }

        function checkbox_element_callback(array $args)
        {
            $post_type = $args['post_type'];
            $options = get_option('checkbox_element_callback');
            $element_name = 'checkbox_' . $post_type;
            // if (in_array($element_name,$this->options)) {
            $checked = checked(1, $this->options[$element_name], false);
            /*
             * } else {
             * $checked = '';
             * }
             */
            // CHIEFED_UTILS::getLogger()->debug('$checked '.$checked);
            $html = '<input type="checkbox" id="' . $element_name . '" name="chief_editor_option[' . $element_name . ']" value="1"' . $checked . '/>';
            $html .= '<label for="' . $element_name . '"></label>';
            
            print $html;
        }

        function ce_blog_chief_editor_callback(array $args)
        {
            $blog_id = $args['blog_id'];
            $setting_id = $args['setting_id'];
            CHIEFED_UTILS::getLogger()->debug($setting_id);
            $chief_editors_roles = array(
                'contributor',
                'author',
                'editor'
            );
            $blogusers = array();
            
            foreach ($chief_editors_roles as $role) {
                
                $other_blogusers = get_users('blog_id=' . $blog_id . '&orderby=nicename&role=' . $role);
                if ($other_blogusers) {
                    $blogusers = array_merge($blogusers, $other_blogusers);
                }
            }
            
            CHIEFED_UTILS::getLogger()->debug(count($blogusers) . ' ' . count($chief_editors_roles) . ' : ');
            /*
             * echo isset($this->options[$setting_id]) ? $this->options[$setting_id] : 'not set<br/>';
             * $chief_editor_array = $this->options[$setting_id];
             *
             * echo count($chief_editor_array) . '<ul>';
             * foreach ($chief_editor_array as $chief_editor) {
             * echo '<li>'.$chief_editor.'</li>';
             * }
             * echo '</ul>';
             */
            $fieldID = 'chief_editors_selector_' . $blog_id;
            $fieldName = 'chief_editor_option[' . $setting_id . ']';
            
            printf('<select multiple="multiple" name="%s[]" id="%s" class="widefat" size="5" style="margin-bottom:10px">', $fieldName, $fieldID);
            
            // Each individual option
            foreach ($blogusers as $user) {
                $id = $user->ID;
                $userEmail = $user->user_email;
                $userLogin = $user->user_login;
                $userNicename = $user->display_name;
                CHIEFED_UTILS::getLogger()->debug("$id : $userEmail = ");
                $checkedOptions = $this->options[$setting_id];
                CHIEFED_UTILS::getLogger()->debug($checkedOptions);
                
                printf('<option value="%s" %s style="margin-bottom:3px;">%s</option>', $id, in_array($id, $checkedOptions) ? 'selected="selected"' : '', $id . ' - ' . $userLogin . ' - ' . $userNicename . ' (' . $userEmail . ')');
            }
            
            echo '</select>';
        }

        public function ce_sender_email_address_callback()
        {
            printf('<input type="text" id="sender_email" name="chief_editor_option[sender_email]" value="%s" />', isset($this->options['sender_email']) ? esc_attr($this->options['sender_email']) : '');
        }

        public function ce_sender_name_callback()
        {
            printf('<input type="text" id="sender_name" name="chief_editor_option[sender_name]" value="%s" />', isset($this->options['sender_name']) ? esc_attr($this->options['sender_name']) : '');
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ce_email_addresses_callback()
        {
            printf('<input type="text" id="email_recipients" name="chief_editor_option[email_recipients]" value="%s" />', isset($this->options['email_recipients']) ? esc_attr($this->options['email_recipients']) : '');
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function ce_email_content_callback()
        {
            $ce_default_mail_content = 'Cher %username%,<br/>
Voici la previsualisation de votre article pour obtention d\'un Bon A Tirer : <br/>

<h2><a href="%postlink%" target="_blank">%posttitle%</a></h2><br/>

Vous devez etre authentifie avec vos identifiants personnels <a href="%blogurl%">sur le site</a> pour visualiser cet article en ligne:
<ul><li>Utiliser votre login : <strong>%userlogin%</strong></li>
<li>et votre mot de passe (si vous l\'avez oublie, demandez-en un nouveau en cliquant ici : <a href="http://www.termel.com/wp-login.php?action=lostpassword">Service de recuperation de mot de passe</a>)
</ul>
Si le message suivant apparait:<br/>
<em>Desole, mais la page demande ne peut etre trouvee.</em>
c\'est que vous n\'etes pas connecte au site.
<h2>En cas de probleme</h2>Merci de suivre la procedure suivante pour visualiser votre post en ligne:<br/>
<ol><li>Se connecter avec vos identifiants <a href="%blogurl%">sur le site idweblogs</a>.</li>
<li>Verifier que votre nom (ou pseudo) apparait bien en haut a droite de l\'ecran, ce qui confirme votre connexion au site.</li>
<li>Ouvrir un nouvel onglet dans le meme navigateur (Chrome, Firefox, Internet Explorer,etc...).</li>
<li>Copier/coller le lien ci dessus dans ce nouvel onglet et valider.</li>
<li>Votre post doit s\'afficher correctement, en cas de probleme, merci de nous contacter : <a href="mailto:aide@idweblogs.com">aide@idweblogs.com</a></li>
</ol> 
<h2>Merci de preciser</h2> dans votre mail de reponse, si ce n\'est deja fait, les elements suivants:
<ol><li>Vos liens d\'interet eventuels pour ce post</li>
<li>Les mots cles qui permettent d\'indexer au mieux votre post</li>
<li>L\'image de Une du post</li>
<li>La categorie (ou les categories) du blog dans laquelle doit etre publie votre article</li>
<li>Les liens web eventuels a rajouter vers des sites externes ou de la bibliographie</li>
<li>(optionnel) une photo de vous</li>
</ol>

<br/>Cordialement, L\'equipe';
            
            printf('<textarea type="text" id="email_content" rows="25" cols="110" name="chief_editor_option[email_content]" value="%s">%s</textarea>', isset($this->options['email_content']) ? esc_attr($this->options['email_content']) : $ce_default_mail_content, isset($this->options['email_content']) ? esc_attr($this->options['email_content']) : $ce_default_mail_content);
        }

        public function create_calendar_table()
        {
            // Set up global variables. Great
            // global $wpdb, $blog_id, $post;
            $sumsArray = array();
            
            // Get a list of blogs in your multisite network
            // $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
            $weekNumber = date("W");
            $weeksInPast = 4;
            $weeksInFuture = 4;
            $thisWeekColor = "#F5B800";
            $backgroundColor = "#6B747A"; // "#6B6B6B";
            $lightGreyColor = "#E0E0E0";
            $startingWeek = max($weekNumber - $weeksInPast, 1);
            $currentYear = date("Y");
            echo '<table class="sortable" id="calendar_table" style="border:solid #6B6B6B 1px;width:100%;">';
            // $color_bool = true;
            $chief_editor_table_header = '<tr style="color:#FFAF30;background-color:' . $backgroundColor . ';color:#FFFFFF">';
            $chief_editor_table_header .= '<td>#</td>';
            $chief_editor_table_header .= '<td>' . __('Blog', 'chief-editor') . '</td>';
            for ($week = $startingWeek; $week <= $weekNumber + $weeksInFuture; $week ++) {
                
                $sumsArray[$week] = 0;
                $weekArray = $this->getStartAndEndDate($week, $currentYear);
                $color = '';
                if ($week == $weekNumber) {
                    $color = $thisWeekColor;
                } else {
                    $color = $backgroundColor;
                }
                
                $chief_editor_table_header .= '<td style="background-color:' . $color . ';">' . $weekArray['week_start'] . ' => ' . $weekArray['week_end'] . '</td>';
            }
            $chief_editor_table_header .= '</tr>';
            
            echo $chief_editor_table_header;
            
            $idx = 0;
            
            if (is_multisite()) {
                // Iterate through your list of blogs
                foreach (get_sites() as $blog) {
                    $public = $blog->public;
                    if ($public == 0) {
                        
                        continue;
                    }
                    $blog_id = $blog->blog_id;
                    
                    $idx += 1;
                    // Switch to the next blog in the loop.
                    // This will start at $id == 1 because of your ORDER BY statement.
                    switch_to_blog($blog_id);
                    // $posts_of_current_blog = array();
                    $posts_of_current_blog = get_posts(array(
                        'numberposts' => - 1,
                        'posts_per_page' => - 1,
                        'post_type' => 'post',
                        'post_status' => array(
                            'publish',
                            'future'
                        )
                    ));
                    
                    // Get the 5 latest posts for the blog and store them in the $globalquery variable.
                    // $globalquery = get_posts('numberposts=5&post_type=any');
                    $blog_details = get_blog_details($blog_id);
                    /*
                     * if ($this->noPostPublishedBetweenDates($posts_of_current_blog,$startingWeek,$weekNumber + $weeksInFuture)) {
                     * continue;
                     * }
                     */
                    
                    $new_line = '<tr>';
                    $new_line .= '<td>' . $idx . '</td>';
                    $new_line .= '<td>' . $blog_details->blogname . '</td>';
                    
                    for ($week = $startingWeek; $week <= $weekNumber + $weeksInFuture; $week ++) {
                        
                        $weekArray = $this->getStartAndEndDate($week, $currentYear);
                        $startDate = $weekArray['week_start'];
                        $endDate = $weekArray['week_end'];
                        // echo 'New Week ' . $startDate . ' ' . $endDate;
                        $currentWeekPosts = array();
                        
                        // echo '<ul>';
                        foreach ($posts_of_current_blog as $new_post) {
                            $format = 'Y-m-d';
                            $postDate = get_the_time($format, $new_post->ID);
                            $post_date = new DateTime($postDate);
                            $start_date = new DateTime($startDate);
                            $end_date = new DateTime($endDate);
                            
                            if ($post_date >= $start_date && $post_date <= $end_date) {
                                
                                $currentWeekPosts[] = $new_post;
                            }
                        }
                        
                        $numberOfPosts = count($currentWeekPosts);
                        // echo $numberOfPosts;
                        if ($numberOfPosts) {
                            
                            if ($week < $weekNumber) {
                                // post published
                                $color = CE_PUBLISHED_COLOR;
                            } else {
                                $color = CE_SCHEDULED_COLOR;
                            }
                            
                            $sumsArray[$week] += $numberOfPosts;
                            $new_line .= '<td class="ce_calendar_post_cell" style="background-color:' . $color . ';">';
                            $new_line .= '<div class="ce_calendar_post_title">';
                            $new_line .= '<ol>';
                            foreach ($currentWeekPosts as $weekPost) {
                                
                                $permalink = get_blog_permalink($blog_id, $weekPost->ID);
                                $new_line .= '<li>';
                                $new_line .= '<a title="' . __('published on', 'chief-editor').' ' . $weekPost->post_date . '" href="' . $permalink . '" target="_blank">' . $weekPost->post_title . '</a>';
                                $new_line .= '</li>';
                            }
                            $new_line .= '</ol>';
                            $new_line .= '</div>';
                            
                            $new_line .= '</td>';
                        } else {
                            $new_line .= '<td class="empty-cell"></td>';
                        }
                    }
                    
                    $new_line .= '</tr>';
                    echo $new_line;
                }
                // Switch back to the main blog
                restore_current_blog();
            }
            
            $last_line = '<tr>';
            $last_line .= '<td></td>';
            $last_line .= '<td>Total:</td>';
            for ($week = $startingWeek; $week <= $weekNumber + $weeksInFuture; $week ++) {
                
                $last_line .= '<td class="table_footer">' . $sumsArray[$week] . '</td>';
            }
            $last_line .= '</tr>';
            
            echo $last_line;
            echo '</table>';
        }

        function noPostPublishedBetweenDates($posts, $startW, $endW)
        {
            // echo "count($posts) posts between $startW and $endW";
            $currentYear = date("Y");
            $weekArray1 = $this->getStartAndEndDate($startW, $currentYear);
            $startDate = $weekArray['week_start'];
            // $endDate = $weekArray['week_end'];
            
            $weekArray1 = $this->getStartAndEndDate($endW, $currentYear);
            // $startDate = $weekArray['week_start'];
            $endDate = $weekArray['week_end'];
            
            foreach ($posts as $post) {
                // if ($post->post_date)
                $format = 'Y-m-d';
                $postDate = get_the_time($format, $post->ID);
                $post_date = new DateTime($postDate);
                $start_date = new DateTime($startDate);
                $end_date = new DateTime($endDate);
                
                if ($post_date >= $start_date && $post_date <= $end_date) {
                    
                    return 1;
                }
            }
            
            return 0;
        }

        function getStartAndEndDate($week, $year)
        {
            $dto = new DateTime();
            $dto->setISODate($year, $week);
            $ret['week_start'] = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $ret['week_end'] = $dto->format('Y-m-d');
            return $ret;
        }

        public function get_all_writers_over_network()
        {
            // Set up global variables. Great
            // global $wpdb;//, $blog_id, $post;
            
            // Get a list of blogs in your multisite network
            // $blogs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM wp_blogs ORDER BY $s",'blog_id' ) );
            $network_sites = get_sites();
            CHIEFED_UTILS::getLogger()->debug('Network has ' . count($network_sites) . ' blog(s)');
            
            $globalcontainer = array();
            // foreach( $blogs as $blog ) {
            foreach ($network_sites as $blog) {
                $blog_id = $blog->blog_id;
                CHIEFED_UTILS::getLogger()->debug($blog_id . ' -> ' . $blog->path);
                switch_to_blog($blog_id);
                
                // switch_to_blog( $blog->blog_id );
                $globalquery = array_merge(get_users('role=contributor'), get_users('role=author'), get_users('role=editor')); // get_posts( 'numberposts=5&post_type=any' );
                $globalcontainer = array_merge($globalcontainer, $globalquery);
                
                restore_current_blog();
            }
            
            return $globalcontainer;
        }

        public function ched_custom_author_stats($period, $start_date, $end_date)
        {
            // global $wpdb,$blog_id, $post;
            $table_class = "border:solid #6B6B6B 1px;width:100%;";
            $border_class = "border:solid #6B6B6B 1px;";
            
            echo '<form>';
            echo '<INPUT type="button" value="' . __('Trace graph for sorted column', 'chief-editor') . '" name="traceGraphButton" onClick="traceGraph();">';
            echo '</FORM>';
            echo '<table class="sortable" id="authorTable" style="border:solid #6B6B6B 1px;width:100%;">';
            $color_bool = true;
            $chief_editor_table_header = '<tr style="background-color:#6B6B6B;color:#FFFFFF">';
            $chief_editor_table_header .= '<td>' . __('Lastname', 'chief-editor') . '</td>';
            $chief_editor_table_header .= '<td>' . __('Firstnamem', 'chief-editor') . '</td>';
            $chief_editor_table_header .= '<td>' . __('Blog', 'chief-editor') . '</td>';
            $chief_editor_table_header .= '<td>' . __('Posts', 'chief-editor') . '</td>';
            $chief_editor_table_header .= '<td>' . __('Comments', 'chief-editor') . '</td>';
            $chief_editor_table_header .= '</tr>';
            
            // Get a list of blogs in your multisite network
            // $blogs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM wp_blogs ORDER BY %d",$blog_id ) );
            if (is_multisite()) {
                $network_sites = get_sites();
                
                CHIEFED_UTILS::getLogger()->debug('Network has ' . count($network_sites) . ' blog(s)');
                
                echo $chief_editor_table_header;
                $globalcontainer = array();
                
                $totalNbOfPosts = 0;
                $totalNbOfComments = 0;
                
                foreach ($network_sites as $blog) {
                    
                    $blog_id = $blog->blog_id;
                    CHIEFED_UTILS::getLogger()->debug($blog_id . ' -> ' . $blog->path);
                    switch_to_blog($blog_id);
                    
                    $blog_name = get_bloginfo('name');
                    $blog_title = get_bloginfo('title');
                    $blog_wpurl = get_bloginfo('wpurl');
                    $users = array_merge(get_users('role=administrator'), get_users('role=contributor'), get_users('role=author'), get_users('role=editor')); // get_posts( 'numberposts=5&post_type=any' );
                                                                                                                                                              
                    // echo '<tr>';
                                                                                                                                                              // echo '<p>Users : '.count($users).'</p>';
                    CHIEFED_UTILS::getLogger()->debug(count($users) . ' users on blog ' . $blog_title);
                    foreach ($users as $author) {
                        
                        $user_role = $author->role;
                        if ($user_role == 'subscriber') {
                            continue;
                        }
                        
                        $author_stats = $this->bm_get_stats($period, $author->ID, $start_date, $end_date);
                        if ($author_stats['posts'] == 0) {
                            continue;
                        }
                        $line_color = ($color_bool ? '#FFFFFF' : '#EDEDED');
                        $newLine = '<tr style="border:solid #6B6B6B 1px;background-color:' . $line_color . '">';
                        
                        $user_info = get_userdata($author->ID);
                        $userlogin = $user_info->user_login;
                        $userLastname = $user_info->last_name;
                        $userFirstname = $user_info->first_name;
                        $userdisplayname = $user_info->display_name;
                        $words_per_post = 0;
                        $words_per_comment = 0;
                        
                        if ($author_stats['posts'] > 0) {
                            $words_per_post = round($author_stats['postwords'] / $author_stats['posts']);
                        }
                        if ($author_stats['commentwords'] > 0 && $author_stats['posts'] > 0) {
                            $words_per_comment = floor($author_stats['commentwords'] / $author_stats['posts']);
                        }
                        
                        $performance = $author_stats['avgposts'] * $author_stats['avgcomments'];
                        
                        $user_rss_feed = $blog_wpurl . '/author/' . $userlogin . '/feed/';
                        
                        $cat_posts = $author_stats['categories'];
                        
                        $newLine .= '<td>' . $userLastname . '</td>';
                        $newLine .= '<td>' . $userFirstname . '</td>';
                        $newLine .= '<td>' . $blog_name . '</td>';
                        $newLine .= '<td>';
                        $totalNbOfPosts += $author_stats['posts'];
                        $newLine .= '<ul><li><b>Total : ' . $author_stats['posts'] . '</b>';
                        foreach ($cat_posts as $key => $value) {
                            $newLine .= '<li>' . $key . ' : ' . $value . '</li>';
                        }
                        $newLine .= '</ul>';
                        $newLine .= '</li></ul>';
                        $newLine .= '</td>';
                        $newLine .= '<td>' . $author_stats['comments'] . '</td>';
                        $totalNbOfComments += $author_stats['comments'];
                        $newLine .= '</tr>';
                        
                        echo $newLine;
                        $color_bool = ! $color_bool;
                    }
                    
                    restore_current_blog();
                }
            }
            
            echo '<tr><td>Total:</td><td></td><td></td><td>' . $totalNbOfPosts . '</td><td>' . $totalNbOfComments . '</td></tr>';
            echo '</table>';
            echo '<form>';
            echo '<INPUT type="button" value="' . __('Trace graph for sorted column', 'chief-editor') . '" name="traceGraphButton" onClick="traceGraph();">';
            echo '</FORM>';
            echo '<hr>';
            echo '<div style="text-align:center;"><canvas id="graphCanvas" height="600" width="1000"></canvas><br><br><canvas id="pieGraphCanvas" height="600" width="1000"></div>';
        }

        public function getAuthorsInfos()
        {
            $result = '';
            $users = array_merge(get_users('role=contributor'), get_users('role=author'), get_users('role=editor')); // get_posts( 'numberposts=5&post_type=any' );
                                                                                                                     
            $authorsEmails = array();
            CHIEFED_UTILS::getLogger()->debug("Users on blog : " . count($users));
            foreach ($users as $author) {
                
                $user_role = $author->role;
                if ($user_role == 'subscriber') {
                    continue;
                }
                
                $author_stats = $this->bm_get_stats($period, $author->ID);
                if ($author_stats['posts'] == 0) {
                    continue;
                }
                $line_color = ($color_bool ? '#FFFFFF' : '#EDEDED');
                $result .= '<tr style="border:solid #6B6B6B 1px;background-color:' . $line_color . '">';
                
                $user_info = get_userdata($author->ID);
                $userlogin = $user_info->user_login;
                $userdisplayname = $user_info->display_name;
                $authorsEmails[] = $user_info->user_email;
                $words_per_post = 0;
                $words_per_comment = 0;
                
                if ($author_stats['posts'] > 0) {
                    $words_per_post = round($author_stats['postwords'] / $author_stats['posts']);
                }
                if ($author_stats['commentwords'] > 0 && $author_stats['posts'] > 0) {
                    $words_per_comment = floor($author_stats['commentwords'] / $author_stats['posts']);
                }
                
                $performance = $author_stats['avgposts'] * $author_stats['avgcomments'];
                
                $user_rss_feed = $blog_wpurl . '/author/' . $userlogin . '/feed/';
                $result .= '<td>' . $blog_name . '</td><td>' . $userdisplayname . '</td><td>' . $userlogin . ' - <a target="_blank" href="' . $user_rss_feed . '">' . $user_rss_feed . '</a></td><td>' . $author_stats['bloggingmonths'] . '</td><td>' . $author_stats['posts'] . '</td><td>' . $author_stats['avgposts'] . '</td><td>' . $words_per_post . '</td><td>' . $author_stats['comments'] . '</td><td>' . $author_stats['avgcomments'] . '</td><td>' . $words_per_comment . '</td><td>' . $performance . '</td>';
                // $i++;
                $result .= '</tr>';
                $color_bool = ! $color_bool;
            }
            return array("TABLE" => $result, "EMAILS" => $authorsEmails);
        }

        public function bm_author_stats($period)
        {
            $allEmails = array();
            $table_class = "border:solid #6B6B6B 1px;width:100%;";
            $border_class = "border:solid #6B6B6B 1px;";
            
            echo '<form>';
            echo '<INPUT type="button" value="' . __('Trace graph for sorted column', 'chief-editor') . '" name="traceGraphButton" onClick="traceGraph();">';
            echo '</FORM>';
            echo '<table class="sortable" id="authorTable" style="border:solid #6B6B6B 1px;width:100%;">';
            $color_bool = true;
            $chief_editor_table_header = '<tr style="background-color:#6B6B6B;color:#FFFFFF"><td>Blog</td>';
            $chief_editor_table_header .= '<td>' . __('Name', 'chief-editor') . '</td><td>' . __('login', 'chief-editor') . '</td><td>' . __('Month blogging', 'chief-editor') . '</td>';
            $chief_editor_table_header .= '<td>' . __('Posts', 'chief-editor') . '</td><td>' . __('Posts/month', 'chief-editor') . '</td>';
            $chief_editor_table_header .= '<td>' . __('Words/post', 'chief-editor') . '</td><td>' . __('Comments', 'chief-editor') . '</td>';
            $chief_editor_table_header .= '<td>' . __('Comments/post', 'chief-editor') . '</td><td>' . __('Words/comment', 'chief-editor') . '</td><td>' . __('Comments/month', 'chief-editor') . '</td></tr>';
            
            echo $chief_editor_table_header;
            
            if (is_multisite()) {
                $network_sites = get_sites();
                CHIEFED_UTILS::getLogger()->debug('Network has ' . count($network_sites) . ' blog(s)');
                
                $globalcontainer = array();
                foreach ($network_sites as $blog) {
                    $blog_id = $blog->blog_id;
                    switch_to_blog($blog_id);
                    
                    // switch_to_blog( $blog->blog_id );
                    $blog_name = get_bloginfo('name');
                    $blog_title = get_bloginfo('title');
                    $blog_wpurl = get_bloginfo('wpurl');
                    $authorsInfos = $this->getAuthorsInfos();
                    echo $authorsInfos["TABLE"];
                    $allEmails = array_merge($allEmails, $authorsInfos["EMAILS"]);
                    
                    restore_current_blog();
                }
            } else {
                //echo $this->getAuthorsInfos();
                $authorsInfos = $this->getAuthorsInfos();
                echo $authorsInfos["TABLE"];
            }
            echo '</table>';
            echo '<form>';
            echo '<INPUT type="button" value="' . __('Trace graph for sorted column', 'chief-editor') . '" name="traceGraphButton" onClick="traceGraph();">';
            echo '</FORM>';
            echo '<hr>';
            echo '<div style="text-align:center;"><canvas id="graphCanvas" height="600" width="1000"></canvas><br><br><canvas id="pieGraphCanvas" height="600" width="1000"></div>';
            
            $usersInfos .= '<br/><hr/>';
            //$emails .= implode(';',$authorsEmails);
            $unique_authors = array_unique($allEmails);
            $usersInfos .= '<h3>All emails ('.count($unique_authors).')</h3>';
            $usersInfos .= '<p>';
            $usersInfos .= implode(';',$unique_authors);
            $usersInfos .= '</p>';
            
            foreach ($unique_authors as $unique_email){
                $usersInfos .= $unique_email.'<br/>';
            }
            
            echo $usersInfos;
        }

        public function bm_print_stats($stats)
        {
            
            // $options = get_option('BlogMetricsOptions');
            $option['fullstats'] = 1;
            
            if ($stats['period'] == "alltime") {
                $per = "per";
            } else if ($stats['period'] == "month") {
                $per = "this";
            }
            echo '<td style="vertical-align:text-top;width:220px;">';
            if (! is_numeric($stats['authors'])) {
                echo '<h3>' . $stats['authors'] . '</h3>';
            }
            echo '<h4 style="margin-bottom:2px;">Raw Author Contribution</h4>';
            
            if ($stats['avgposts'] == 1) {
                echo $stats['avgposts'] . " post $per month<br/>\n";
            } else {
                echo $stats['avgposts'] . " posts $per month<br/>\n";
            }
            if ($stats['posts'] > 0) {
                echo 'Avg: ' . round($stats['postwords'] / $stats['posts']) . " words per post<br/>\n";
            }
            if ($stats['stddevpostwords']) {
                echo 'Std dev: ' . round($stats['stddevpostwords']) . ' words' . "<br/>\n";
            }
            echo '<h4 style="margin-bottom:2px;">Conversation Rate Per Post</h4>';
            echo '<table style="border-collapse:collapse;">';
            echo '<tr><td>Avg: &nbsp;</td><td>' . $stats['avgcomments'] . ' comments' . "</td></tr>\n";
            if ($stats['stddevcomments']) {
                echo '<tr><td>Std dev: &nbsp;</td><td>' . $stats['stddevcomments'] . ' comments' . "</td></tr>\n";
            }
            if ($stats['commentwords'] > 0 && $stats['posts'] > 0) {
                echo '<tr><td>Avg:</td><td>' . floor($stats['commentwords'] / $stats['posts']) . ' words in comments' . "</td></tr>\n";
            }
            echo '<tr><td>Avg:</td><td>' . $stats['avgtrackbacks'] . ' trackbacks' . "</td></tr>\n";
            if ($stats['stddevtrackbacks']) {
                echo '<tr><td>Std dev:</td><td>' . $stats['stddevtrackbacks'] . ' trackbacks' . "</td></tr>\n";
            }
            echo '</table>' . "\n\n";
            
            if ($options['fullstats']) {
                echo '<h4 style="margin-bottom:2px;">Full Stats</h4>';
                echo '<table style="border-collapse:collapse;">';
                if (is_numeric($stats['authors'])) {
                    echo '<tr><td>Author(s):</td><td>' . $stats['authors'] . "</td></tr>";
                }
                if ($stats['period'] == "alltime") {
                    echo '<tr><td>Posts:</td><td>' . $stats['posts'] . "</td></tr>";
                }
                echo '<tr><td>Words in posts:</td><td>' . $stats['postwords'] . "</td></tr>";
                echo '<tr><td>Comments:</td><td>' . $stats['comments'] . "</td></tr>";
                echo '<tr><td>Words in comments:</td><td>' . $stats['commentwords'] . "</td></tr>";
                echo '<tr><td>Trackbacks:</td><td>' . $stats['trackbacks'] . "</td></tr>";
                if ($stats['period'] == "alltime") {
                    echo '<tr><td>Months blogging: &nbsp;</td><td>' . $stats['bloggingmonths'] . "</td></tr>";
                }
                
                echo '</table>';
            }
            echo '</td>';
        }

        function get_per_category_stats($author_id, $startDate, $endDate)
        {
            $result = array();
            
            
            $args = array(
                'type' => 'post',
                'child_of' => 0,
                'parent' => '',
                'orderby' => 'name',
                'order' => 'ASC',
                'hide_empty' => 1,
                'hierarchical' => 1,
                'exclude' => '',
                'include' => '',
                'number' => '',
                'taxonomy' => 'category',
                'pad_counts' => false
            );
            
            $categories = get_categories($args);
            
            foreach ($categories as $category) {
                
                $cat_slug = $category->slug;
                if ($startDate != NULL && $endDate != NULL) {
                    
                    $argsForQuery = array(
                        'category_name' => $cat_slug,
                        'author' => $author_id,
                        'post_status' => 'publish',
                        'posts_per_page' => - 1,
                        'date_query' => array(
                            array(
                                'after' => array(
                                    'year' => $startDate->format('Y'),
                                    'month' => $startDate->format('m'),
                                    'day' => $startDate->format('d')
                                ),
                                'before' => array(
                                    'year' => $endDate->format('Y'),
                                    'month' => $endDate->format('m'),
                                    'day' => $endDate->format('d')
                                ),
                                'inclusive' => true
                            )
                        )
                    );
                } else {
                    $argsForQuery = array(
                        'category_name' => $cat_slug,
                        'post_status' => 'publish',
                        'posts_per_page' => - 1,
                        'author' => $author_id
                    );
                }
                // CHIEFED_UTILS::getLogger()->debug($argsForQuery);
                $queried_posts = get_posts($argsForQuery);
                
                $nb_of_posts = count($queried_posts);
                if ($nb_of_posts) {
                    
                    $result[$cat_slug] = $nb_of_posts;
                    
                    foreach ($queried_posts as $post) {
                        CHIEFED_UTILS::getLogger()->debug($cat_slug . ' => ' . $nb_of_posts . ' ' . $post->post_title);
                    }
                }
            }
            
            return $result;
        }

        function bm_get_stats($period = "alltime", $authorid = 0, $startDate = NULL, $endDate = NULL)
        {
            global $wpdb;
            $options = get_option('BlogMetricsOptions');
            
            $periodquery = "";
            $authorquery = "";
            
            if ($startDate != NULL) {
                
                $start_date = $startDate->format('Y-m-d');
                if ($endDate != NULL) {
                    $end_date = $endDate->format('Y-m-d');
                } else {
                    $end_date = date("Y-m-d");
                }
                
                if (! empty($start_date) && ! empty($end_date)) {
                    $periodquery = " AND p.post_date BETWEEN '$start_date' AND '$end_date'";
               
                }
            } else if ($period == "month") {
                $periodquery = " AND p.post_date > date_sub(now(),interval 1 month)";
            }
            
            if ($authorid != 0) {
                $authorquery = " AND p.post_author = $authorid";
            }
            
            $authorsquery = "SELECT COUNT(DISTINCT post_author) FROM $wpdb->posts AS p WHERE p.post_type = 'post'" . $periodquery;
            
            // Override query if an authorid is set, to return display name for author
            if ($authorid != 0) {
                $authorsquery = "SELECT u.display_name FROM $wpdb->users AS u WHERE u.ID = $authorid";
            }
            
            $postsquery = "SELECT COUNT(ID) FROM $wpdb->posts AS p WHERE p.post_type = 'post' AND p.post_status = 'publish'" . $periodquery . $authorquery;
            
            $args = array(
                'post_status' => 'publish',
                'author' => $authorid,
                'posts_per_page' => - 1
            );
            if ($startDate != NULL && $endDate != NULL) {
                $args['date_query'] = array(
                    array(
                        'after' => array(
                            'year' => $startDate->format('Y'),
                            'month' => $startDate->format('m'),
                            'day' => $startDate->format('d')
                        ),
                        'before' => array(
                            'year' => $endDate->format('Y'),
                            'month' => $endDate->format('m'),
                            'day' => $endDate->format('d')
                        ),
                        'inclusive' => true
                    )
                );
            }
            // echo $args;
            $all_posts_for_user_on_blog = get_posts($args);
        
            
            $firstpostquery = "SELECT p.post_date FROM $wpdb->posts AS p WHERE p.post_status = 'publish'$authorquery ORDER BY p.post_date LIMIT 1";
            
            $commentfromwhere = "FROM $wpdb->comments AS c, $wpdb->posts AS p, $wpdb->users AS u " . "WHERE c.comment_approved = '1'" . " AND c.comment_author_email != u.user_email" . " AND c.comment_post_ID = p.ID" . " AND c.comment_type = ''" . " AND p.post_type = 'post'" . " AND p.post_author = u.ID" . $periodquery . $authorquery;
            
            $commentsquery = "SELECT COUNT(c.comment_ID) " . $commentfromwhere;
            $commentwordsquery = $commentfromwhere;
            
            $trackbackquery = str_replace("c.comment_type = ''", "c.comment_type != ''", $commentsquery);
            
            $postwordsquery = "FROM $wpdb->posts p WHERE p.post_status = 'publish' AND p.post_type = 'post'" . $periodquery . $authorquery;
            
            $stats['authors'] = $wpdb->get_var($authorsquery);
            $stats['posts'] = count($all_posts_for_user_on_blog); // $wpdb->get_var($postsquery);
            if ($startDate != NULL) {
                $stats['categories'] = $this->get_per_category_stats($authorid, $startDate, $endDate);
            }
            $stats['comments'] = $wpdb->get_var($commentsquery);
            $stats['trackbacks'] = $wpdb->get_var($trackbackquery);
            $stats['postwords'] = $this->bm_wordcount($postwordsquery, "post_content", "ID");
            $stats['commentwords'] = $this->bm_wordcount($commentwordsquery, "comment_content", "comment_ID");
            if ($period == "alltime") {
                $stats['firstpost'] = $wpdb->get_var($firstpostquery);
                $stats['bloggingmonths'] = floor((time() - strtotime($stats['firstpost'])) / 2628000);
                if ($stats['bloggingmonths'] == 0) {
                    $stats['bloggingmonths'] = 1;
                }
            } else if ($period == "month") {
                $stats['bloggingmonths'] = 1;
            }
            if ($stats['posts'] > 0) {
                $stats['avgposts'] = round($stats['posts'] / $stats['bloggingmonths'], 1);
            }
            
            if ($stats['comments'] > 0 && $stats['posts'] > 0) {
                $stats['avgcomments'] = round(($stats['comments'] / $stats['posts']), 1);
            } else {
                $stats['avgcomments'] = 0;
            }
            if ($stats['avgcomments'] > 1 && $options['stddev']) {
                $commentstddevquery = "SELECT (COUNT(c.comment_ID)-" . $stats['avgcomments'] . ")*(COUNT(c.comment_ID)-" . $stats['avgcomments'] . ") AS commentdiff2 " . $commentfromwhere . " GROUP BY c.comment_post_ID";
                $results = $wpdb->get_results($commentstddevquery);
                $totaldev = 0;
                foreach ($results as $result) {
                    $totaldev += $result->commentdiff2;
                }
                $stats['stddevcomments'] = round(sqrt($totaldev / $stats['posts']), 1);
            }
            if ($stats['trackbacks'] > 0) {
                $stats['avgtrackbacks'] = round($stats['trackbacks'] / $stats['posts'], 1);
            } else {
                $stats['avgtrackbacks'] = 0;
            }
            if ($stats['avgtrackbacks'] > 1 && $options['stddev']) {
                $trackbacksstddevquery = str_replace("c.comment_type = ''", "c.comment_type != ''", $commentstddevquery);
                $results = $wpdb->get_results($trackbacksstddevquery);
                $totaldev = 0;
                if ($results) {
                    foreach ($results as $result) {
                        $totaldev += $result->commentdiff2;
                    }
                    $stats['stddevtrackbacks'] = round(sqrt($totaldev / $stats['posts']), 1);
                } else {
                    $stats['stddevtrackbacks'] = 0;
                }
            }
            if ($stats['postwords'] > 0 && $options['stddev'] && $stats['posts'] > 1) {
                $stats['stddevpostwords'] = $this->bm_wordcount($postwordsquery, "post_content", "ID", ($stats['postwords'] / $stats['posts']));
            }
            
            $stats['period'] = $period;
            return $stats;
        }

        function bm_wordcount($statement, $attribute, $countAttribute, $avg = 0)
        {
            global $wpdb;
            $result = 0;
            
            $countStatement = "SELECT COUNT(" . $countAttribute . ") " . $statement;
            $counter = $wpdb->get_var($countStatement);
            $startLimit = 0;
            
            $rows_at_Once = $counter;
            
            $incrementStatement = "SELECT " . $attribute . " " . $statement;
            
            $intermedcount = 0;
            
            while ($startLimit < $counter) {
                $query = $incrementStatement . " LIMIT " . $startLimit . ", " . $rows_at_Once;
                $results = $wpdb->get_col($query);
                // count the words for each statement
                $intermedcount += count($results);
                for ($i = 0; $i < count($results); $i ++) {
                    $sum = str_word_count($results[$i]);
                    if ($avg == 0) {
                        $result += $sum;
                    } else {
                        $intermed += ($sum * $sum);
                    }
                }
                $startLimit += $rows_at_Once;
            }
            if ($avg != 0) {
                $result = sqrt($intermed / $intermedcount);
            }
            return $result;
        }

      

       
        

        

        

        function sort_posts($posts, $orderby, $order = 'ASC', $unique = true)
        {
            if (! is_array($posts)) {
                return false;
            }
            
            usort($posts, array(
                new Sort_Posts($orderby, $order),
                'sort'
            ));
            
            // use post ids as the array keys
            if ($unique && count($posts)) {
                $posts = array_combine(wp_list_pluck($posts, 'ID'), $posts);
            }
            
            return $posts;
        }

        function get_all_pending_posts_multisite($post_type = 'post')
        {
            global $wpdb;
            global $table_prefix;
            $rows = $wpdb->get_results("SELECT blog_id from $wpdb->blogs WHERE
public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0';");
            
            if ($rows) {
                $blogPostTableNames = array();
                foreach ($rows as $row) {
                    $blogPostTableNames[$row->blog_id] = $wpdb->get_blog_prefix($row->blog_id) . 'posts';
                }
                // print_r($blogPostTableNames); # debugging code
                
                // now we need to do a query to get all the posts from all our blogs
                // with limits applied
                if (count($blogPostTableNames) > 0) {
                    $query = '';
                    $i = 0;
                    foreach ($blogPostTableNames as $blogId => $tableName) {
                        if ($i > 0) {
                            $query .= ' UNION ';
                        }
                        
                        $query .= " (SELECT ID, post_status, post_date, $blogId as `blog_id` FROM $tableName WHERE (post_status != 'publish' AND post_status != 'inherit' AND post_status != 'auto-draft' AND post_status != 'trash') AND post_type = 'post')";
                        $i ++;
                    }
                    
                    // $query.= " ORDER BY post_status DESC, blog_id DESC, post_date DESC";// LIMIT 0,$howMany;";
                    $query .= " ORDER BY post_status='pitch',post_status='assigned',post_status='draft',post_status='in-progress',post_status='pending',post_status='future', post_date DESC";
                    
                    // x_field='F', x_field='P'
                    // echo $query; # debugging code
                    $rows = $wpdb->get_results($query);
                }
                return $rows;
            }
        }

        function recent_multisite_comments()
        {
            // echo '<h2>Comments</h2>';
            $network_sites = get_sites();
            $number_of_items = '1000';
            $result = array();
            foreach ($network_sites as $network_site) {
                // echo '<hr>';
                $blog_path = $network_site->path;
                $blog_id = $network_site->blog_id;
                echo '<h2><b><u>Blog ' . $blog_id . ' : ' . $blog_path . '</u></b></h2<br/>';
                
                switch_to_blog($blog_id);
                
          
                echo '<h3>Pending</h3>';
                echo $this->formatCommentsFromArray($this->getAllComments('hold', $number_of_items));
                
                echo '<h3>Approved</h3>';
                echo $this->formatCommentsFromArray($this->getAllComments('approve', $number_of_items));
                
                echo '<h3>Spam</h3>';
                echo $this->formatCommentsFromArray($this->getAllComments('spam', $number_of_items));
                
                echo '<h3>Trash</h3>';
                echo $this->formatCommentsFromArray($this->getAllComments('trash', $number_of_items));
                
                echo '<hr>';
                
                // remove_filter( 'comments_clauses', 'mp_comments_last_week_filter' );
                restore_current_blog();
            }
            
            return $result;
        }

        function mp_comments_last_week_filter($clauses)
        {
            $last_week = gmdate('W') - 1;
            $query_args = array(
                'w' => $last_week
            );
            $date_query = new WP_Date_Query($query_args, 'comment_date');
            echo $date_query;
            $clauses['where'] .= $date_query->get_sql();
            return $clauses;
        }

        function countAllCommentsMultisite()
        {
            global $wpdb;
            $totalComments = 0;
            foreach (get_sites() as $blog) {
                
                $blog_prefix = $wpdb->get_blog_prefix($blog->blog_id);
                $count = $wpdb->get_results("SELECT comment_approved, COUNT(*) AS num_comments FROM {$blog_prefix}comments GROUP BY comment_approved", ARRAY_A);
                CHIEFED_UTILS::getLogger()->debug($count);
                foreach ($count as $countIdx => $countData) {
                    if ($countData['comment_approved'] == 1) {
                        $totalComments += $countData['num_comments'];
                    }
                }
            }
            
            return $totalComments;
        }

        function getAllCommentsMultisite($number, $start_date, $end_date)
        {
            global $wpdb;
            $selects = array();
            
            $table_name = "{$wpdb->base_prefix}comments";
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                // echo $table_name . 'EXISTS !';
                $selects[] = "(SELECT comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, 0 as blog_id FROM {$table_name}
WHERE comment_date >= '{$start_date}'
AND comment_date < '{$end_date}'
ORDER BY comment_date_gmt DESC LIMIT {$number})"; // real number is (number * # of blogs)
            } else {
                // echo $table_name . 'DOES NOT EXISTS !';
            }
            
            foreach (get_sites() as $blog) {
                
                if ($blog->blog_id == '1') {
                    $table_name = "{$wpdb->base_prefix}{$blog->blog_id}_comments";
                    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                        // echo $table_name . ' skipped !';
                        continue;
                    } else {
                        // echo $table_name . ' EXISTS !';
                    }
                }
          
                // select only the fields you need here!
                $selects[] = "(SELECT comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_date_gmt, comment_content, {$blog->blog_id} as blog_id FROM {$wpdb->base_prefix}{$blog->blog_id}_comments
WHERE comment_date >= '{$start_date}'
AND comment_date < '{$end_date}'
ORDER BY comment_date_gmt DESC LIMIT {$number})"; // real number is (number * # of blogs)
            }
            
            // echo $selects;
            $query = implode(" UNION ALL ", $selects) . " ORDER BY comment_date_gmt DESC";
            // echo '<br/>'.$query;
            
            $comments = $wpdb->get_results($query);
            // echo '<br/>count : '. count($comments);
            
            return $comments;
        }

        function getAllComments($status = '', $number = 100)
        {
            
            $last_month = mktime(0, 0, 0, date("m") - 100, date("d"), date("Y"));
            // echo $last_month;
            $start_date = date('Y-m-d H:i:s', $last_month);
            $end_date = date('Y-m-d H:i:s');
            echo $start_date . '<=>' . $end_date;
            // echo 'MYSQL format: '.current_time('mysql');
            if (false) {
                $comment_status = $status;
                global $wpdb;
                $sql = "SELECT comment_ID, comment_post_ID, comment_author, comment_author_email, comment_date, comment_content, comment_approved, comment_type, comment_parent
FROM wp_comments WHERE comment_date > '" . $start_date . "' AND comment_approved = " . $comment_status . " ORDER BY comment_date_gmt DESC";
                $comments = $wpdb->get_results($sql);
            } else {
                $args = array(
                    
                    'author_email' => '',
                    'ID' => '',
                    'karma' => '',
                    'number' => $number,
                    'offset' => '',
                    'orderby' => '',
                    'order' => 'DESC',
                    'parent' => '',
                    'post_ID' => '',
                    'post_id' => 0,
                    'post_author' => '',
                    'post_name' => '',
                    'post_parent' => '',
                    'post_status' => '',
                    'post_type' => '',
                    'status' => $status,
                    'type' => 'comment',
                    'user_id' => '',
                    'search' => '',
                    'count' => false,
                    'meta_key' => '',
                    'meta_value' => '',
                    'meta_query' => array(
                        array(
                            'key' => 'comment_date',
                            'value' => $start_date,
                            'compare' => '>',
                            'type' => 'CHAR'
                        )
                    )
                );
            
                // The Query
                $comments_query = new WP_Comment_Query();
                
                $comments = $comments_query->query($args);
            }
     
            return $comments;
        }

        function formatCommentsFromArray($comments)
        {
            if ($comments) {
                $line_color = '#DEDEDE';
                $border_color = '#6B6B6B';
                $out = '<table class="sortable" style="border:solid ' . $border_color . ' 1px;
width:100%;
border-collapse:collapse;">';
                $out .= '<tr><th>Author</th><th>Answer</th><th>Comment</th><th>Post</th><th>Blog</th></tr>';
                
                foreach ($comments as $comment) {
                    
                    $comment_id = $comment->comment_ID;
                    $post_id = $comment->comment_post_ID;
                    // echo $post_id;
                    if (is_multisite()) {
                        switch_to_blog($comment->blog_id);
                    }
                    $post_permalink = get_permalink($post_id); // use $blog_id
                    $post_title = get_the_title($post_id);
                    if (is_multisite()) {
                        $blogdetails = get_blog_details($comment->blog_id);
                        $blog_path = $blogdetails->path;
                        $blog_permalink = get_blog_permalink($comment->blog_id, $post_id);
                        restore_current_blog();
                    } else {
                        $blog_path = get_bloginfo('url');
                        $blog_permalink = get_bloginfo('url');
                    }
                    // echo $post_permalink;
                    $out .= '<tr style="background-color:' . $line_color . ';
border:solid ' . $border_color . ' 1px;">';
                    // $out .= '<tr><td>'.$comment->comment_post_ID .'</td>';
                    $out .= '<td style="border:solid ' . $border_color . ' 1px;">' . $comment->comment_author . '<br/><i>' . $comment->comment_author_email . '</i></td>';
                    $link_to_comment = '<a href="' . $post_permalink . '#comment-' . $comment->comment_ID . '" rel="external nofollow" title="' . $post_title . '" target="_blank">';
                    $out .= '<td style="border:solid ' . $border_color . ' 1px;
text-align:center;">';
                    $out .= $link_to_comment; // <a href="'.get_comment_link($comment).'" target="_blank">';
                    $out .= '<input style="text-align:center;background-color:#2AA2CC;color:#000000;" id="show-comment" class="button" type="submit" value="Answer" name="showComment"></input></a>';
                    $comment_status = 'spam';
                    // $out .= '<a href="'. wp_set_comment_status( $comment_id, $comment_status ).'" target="_blank"><input style="float:right;background-color:#CC0000;color:#000000;" id="spam-comment" class="button" type="submit" value="Spam" name="spamComment"></input></a>';
                    $out .= '</td>';
                    $out .= '<td style="border:solid ' . $border_color . ' 1px;">Written on ' . $comment->comment_date . '<br/>' . $comment->comment_content . '</td>';
                    $out .= '<td style="border:solid ' . $border_color . ' 1px;"><a href="' . $post_permalink . '" target="_blank">' . $post_title . '</a></td>';
                    $out .= '<td style="border:solid ' . $border_color . ' 1px;"><a href="' . $blog_path . '" target="_blank">' . $blog_path . '</a></td>';
                    $out .= '</tr>';
                }
                $out .= '</table>';
            } else {
                $out = 'No comments found.';
            }
            return $out;
        }

        
      

       
        function the_post_thumbnail_by_blog($blog_id = NULL, $post_id = NULL, $size = 'post-thumbnail', $attrs = NULL)
        {
            echo get_the_post_thumbnail_by_blog($blog_id, $post_id, $size, $attrs);
        }
    }
}


new ChiefEditorSettings();