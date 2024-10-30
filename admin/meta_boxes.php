<?php
if (! defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

$path = sprintf("%s/chiefed_utils.php", dirname(dirname(__FILE__)));
include_once ($path);

//use ChiefEditor\CHIEFED_UTILS;

class Chief_Editor_Meta_Boxes
{
    private $screens = null;
    private $meta_box_group_title = 'informations-complmentaires';
    private $allowedFileExtensions = array(
        '.pptx',
        '.pdf',
        '.doc',
        '.docx',
        '.zip'
    );

    public static $webStatusesToAdd = null;
    public static $printStatusesToAdd = null;
	public static $categories = null;
    public static $webDefaultStatus = null;
    public static $printDefaultStatus = null;
    public static $timeFilters = null;
    public static $ordered_statuses_array = array(
        'future | Scheduled | #0f89db',
        'pending | Pending | #19a50d',
        'in-progress | In progress | #e0d900',
        'built | Built | #d80000',
        'assign | Assigned | #733f55',
        'draft | Draft | #cecece',
        'pitch | Pitch | #565656'
        
    );

    public static $fields = array(
        array(
            'id' => 'inside-shot',
            'label' => 'Dans le numéro',
            'type' => 'select'
        ),
        array(
            'id' => 'article-status',
            'label' => 'Status de l article',
            'type' => 'select'
        ),
        array(
            'id' => 'images-captions',
            'label' => 'Légendes images',
            'type' => 'textarea'
        ),
        array(
            'id' => 'bibliographie',
            'label' => 'Bibliographie',
            'type' => 'textarea'
        ),
        array(
            'id' => 'lien-d-intrt',
            'label' => 'Lien d intérêt',
            'type' => 'textarea'
        ),
        array(
            'id' => 'keypoints',
            'label' => 'Points essentiels',
            'type' => 'textarea'
        ),
        array(
            'id' => 'quiz',
            'label' => 'Quiz',
            'type' => 'textarea'
        ),
        array(
            'id' => 'thanks',
            'label' => 'Remerciements',
            'type' => 'textarea'
        ),
        array(
            'id' => 'images_number',
            'label' => 'Nombre d illustrations',
            'type' => 'number'
        ),
        array(
            'id' => 'deskpub_informations',
            'label' => 'Informations pour la maquette',
            'type' => 'textarea'
        ),
        array(
            'id' => 'max_nb_of_pages',
            'label' => 'Nombre maximal de page',
            'type' => 'number'
        ),
        array(
            'id' => 'observations',
            'label' => 'Observations de l editeur (ne sera pas transmis)',
            'type' => 'textarea'
        )
    
    );

    /**
     * Class construct method.
     * Adds actions to their respective WordPress hooks.
     */
    function __construct() {
    
        $this->screens = array(
            CHIEFED_PRE_DESKTOP_PUBLISHING_CPT,
           // get_option('chiefed_shots_cpt_name')
        );
        
        add_action('add_meta_boxes', array(
            $this,
            'add_meta_boxes'
        ));
        add_action('save_post', array(
            $this,
            'save_post'
        ));
        add_action('save_post', array(
            $this,
            'save_custom_meta_data'
        ));
        add_action('post_edit_form_tag', array(
            $this,
            'update_edit_form'
        ));
        
        if (self::$webStatusesToAdd == null) {
            $allLines = null;
            if (is_multisite()) {
                //CHIEFED_UTILS::getLogger()->debug("status is_multisite");
                $blog_id = get_current_blog_id();
                CHIEFED_UTILS::getLogger()->trace(get_site_option('chiefed_wwf_statuses_and_colors'));
                CHIEFED_UTILS::getLogger()->trace(get_option('chiefed_wwf_statuses_and_colors'));
                $webStatusesTextArea = get_option('chiefed_wwf_statuses_and_colors', get_site_option('chiefed_wwf_statuses_and_colors'));
                $printStatusesTextArea = get_option('chiefed_pwf_statuses_and_colors', get_site_option('chiefed_pwf_statuses_and_colors'));
            } else {
                //CHIEFED_UTILS::getLogger()->debug("status single site");
                $webStatusesTextArea = get_option('chiefed_wwf_statuses_and_colors');
                $printStatusesTextArea = get_option('chiefed_pwf_statuses_and_colors');
            }
            
            CHIEFED_UTILS::getLogger()->trace($webStatusesTextArea);
            CHIEFED_UTILS::getLogger()->trace($printStatusesTextArea);
            if (empty($webStatusesTextArea)) {
                $allLines = self::$ordered_statuses_array;
            }
            self::$webStatusesToAdd = self::statusesTextAreaToArray($webStatusesTextArea, $allLines);
            self::$webDefaultStatus = self::$webStatusesToAdd[0]['name'];
            
            if (empty($printStatusesTextArea)) {
                $allLines = self::$ordered_statuses_array;
            }
            self::$printStatusesToAdd = self::statusesTextAreaToArray($printStatusesTextArea, $allLines);
            self::$printDefaultStatus = self::$printStatusesToAdd[0]['name'];           
			
            
            $categoriesTextArea = get_option('chiefed_categories_and_colors');
            $timefiltersTextArea = get_option('chiefed_time_filters');
			self::$categories = self::statusesTextAreaToArray($categoriesTextArea, null);
			self::$timeFilters = self::statusesTextAreaToArray($timefiltersTextArea, null);
        }
        //CHIEFED_UTILS::getLogger()->debug('Meta built');
        CHIEFED_UTILS::getLogger()->debug('WEB statuses ' . count(self::$webStatusesToAdd));
        //CHIEFED_UTILS::getLogger()->debug(self::$webStatusesToAdd);
        CHIEFED_UTILS::getLogger()->debug('PRINT statuses ' . count(self::$printStatusesToAdd));
        //CHIEFED_UTILS::getLogger()->debug(self::$printStatusesToAdd);
        
    }

    static function statusesTextAreaToArray($statusesTextArea, $allLines)
    {
        $allLines = ! empty($allLines) ? $allLines : explode("\r\n", $statusesTextArea);
        $resultArray = array();
        if (empty($statusesTextArea) && empty($allLines)) {
            CHIEFED_UTILS::getLogger()->debug("ERROR::Empty statuses");
            
        } else {            
            foreach ($allLines as $line) {
                $lineArray = explode(" | ", $line);
                //CHIEFED_UTILS::getLogger()->debug(implode(' | ', $lineArray));
                $keys = array(
                    'name',
                    'label',
                    'color'
                );
                $keysSize = count($keys);
                while (count($lineArray) < $keysSize) {
                    $lineArray[] = '';
                }
                // $statusArray = array_fill_keys($keys, $lineArray);
                $statusArray = array_combine($keys, array_values($lineArray));
                $resultArray[] = $statusArray;
            }
        }
        return $resultArray;
    }

    function update_edit_form()
    {
        echo ' enctype="multipart/form-data"';
    }

    // end update_edit_form
    
    /**
     * Hooks into WordPress' add_meta_boxes function.
     * Goes through screens (post types) and adds the meta box.
     */
    public function add_meta_boxes()
    {
        CHIEFED_UTILS::getLogger()->debug("add_meta_boxes");
        CHIEFED_UTILS::getLogger()->debug("meta boxes on screens " . count($this->screens));
        CHIEFED_UTILS::getLogger()->debug($this->screens);
        foreach ($this->screens as $screen) {
            CHIEFED_UTILS::getLogger()->debug("meta boxes on screen " . $screen);
            add_meta_box('informations-complmentaires', __('Extra PRINT Editorial Informations', 'chief-editor'), array(
                $this,
                'add_meta_box_callback'
            ), $screen, 'advanced', 'default');
            
            add_meta_box('wp_custom_attachment', 'Images source Attachment', array(
                $this,
                'wp_custom_attachment'
            ), $screen, 'side');
        }
    }

    function wp_custom_attachment()
    {
        wp_nonce_field(plugin_basename(__FILE__), 'wp_custom_attachment_nonce');
        global $post;
        $id = $post->ID;
        $post_title = get_the_title($id);
        // CHIEFED_UTILS::getLogger()->debug("adding meta to post id : " . $id);
        // CHIEFED_UTILS::getLogger()->debug("adding meta to post id : " . get_the_ID());
        $currentFileValueArray = get_post_meta(get_the_ID(), 'wp_custom_attachment', true);
        if ($currentFileValueArray) {
            CHIEFED_UTILS::getLogger()->debug($currentFileValueArray);
            $currentFileValue = basename($currentFileValueArray['file']);
        } else {
            $currentFileValue = '';
        }
        
        CHIEFED_UTILS::getLogger()->debug($currentFileValue);
        // CHIEFED_UTILS::getLogger()->debug($currentFileValue['file']);
        
        $html = '<p class="description">';
        $html .= 'Upload your file here.<br/>';
        $html .= 'Possible file extensions : <b>' . implode(' | ', $this->allowedFileExtensions) . '</b>';
        $html .= '<br/>Current file: <b>' . $currentFileValue . '</b>';
        $html .= '</p>';
        $html .= '<input type="file" id="wp_custom_attachment" name="wp_custom_attachment" value="" size="25" />';
        $buttonText = __('Extract towards gallery');
        $html .= '<button class="button-primary" post_id="'.$id.'" post_title="'.$post_title.'" images_source_document_name="'.$currentFileValue.'" images_source_document="'.$currentFileValueArray['file'].'" id="chiefed_custom_attachment_extract_as_gallery" name="chiefed_custom_attachment_extract_as_gallery" value="" size="25" />'.$buttonText.'</button>';
        
        
        echo $html;
    }

    // end wp_custom_attachment
    
    /**
     * Generates the HTML for the meta box
     *
     * @param object $post
     *            WordPress post object
     */
    public function add_meta_box_callback($post)
    {
        CHIEFED_UTILS::getLogger()->debug("func::add_meta_box_callback");
        wp_nonce_field('informations_complmentaires_data', 'informations_complmentaires_nonce');
        $this->generate_fields($post);
    }

    function chiefed_meta_field_select($title, $name, $list, $description, $default = '', $select_only, $output)
    {
        $option_value = get_option($name, $default);
        if ($name == 'dbem_events_page' && ! is_object(get_page($option_value))) {
            $option_value = 0; // Special value
        }
        
        $select_input = '<select name="' . esc_attr($name) . '">';
        foreach ($list as $key => $value) {
            $select_input .= "<option value='" . esc_attr($key) . "'";
            $select_input .= ($key == $option_value) ? "selected='selected' >" : '>';
            $select_input .= esc_html($value);
            $select_input .= "</option>";
        }
        $select_input .= "</select>";
        
        if ($select_only) {
            $result = $select_input;
        } else {
            
            $result = "<tr valign=\"top\" id='" . esc_attr($name) . "_row'><th scope=\"row\">" . esc_html($title) . "</th><td><select name=\"" . esc_attr($name) . "\">";
            $result .= $select_input;
            $result .= "<br /> <em>" . $description . "</em></td></tr>";
        }
        
        if ($output) {
            echo $result;
        } else {
            return $result;
        }
    }

    public static function create_statuses_color_scale($type = 'print')
    {
        $array = $type == 'print' ? self::$printStatusesToAdd : self::$webStatusesToAdd;
        $result = '<div class="chiefed_scale_container">';
        foreach ($array as $status) {
            $result .= '<div class="chiefed_scale_item" style="background-color:' . $status['color'] . '">' . $status['label'] . '</div>';
            /*
             * if ($currentStatus == $status['name']){
             * //CHIEFED_UTILS::getLogger()->debug($currentStatus." == ".$status['name']);
             * $status_color = $status['color'];
             * }
             */
        }
        
        $result .= '</div>';
        
        return $result;
    }

    public static function get_statuses_array($type = 'print'){
        $array = $type == 'print' ? self::$printStatusesToAdd : self::$webStatusesToAdd;
        $result = array();
        foreach ($array as $status) {
             $result[] = $status['name'];
            
        }
        return $result;
    }
    
	
    public static function get_color_for($currentStatus, $array)
    {
        $status_color = '#cecece';
        if ($array) {
            foreach ($array as $status) {
                if ($currentStatus == $status['name']) {
                    //CHIEFED_UTILS::getLogger()->debug($currentStatus . " == " . $status['name']);
                    $status_color = $status['color'];
                }
            }
        }
        return $status_color;
    }
	
	public static function get_color_for_category($currentStatus)
	{
	    return self::get_color_for($currentStatus, self::$categories);
	}
    
	public static function get_color_for_status($currentStatus, $type = 'print')
    {
        $array = $type == 'print' ? self::$printStatusesToAdd : self::$webStatusesToAdd;
        return self::get_color_for($currentStatus, $array);
    }
	
    public static function get_label_for($currentStatus, $array)
    {
        $status_label = $currentStatus;
        if ($array) {
            foreach ($array as $status) {
                if ($currentStatus == $status['name']) {
                    //CHIEFED_UTILS::getLogger()->debug($currentStatus . " == " . $status['name']);
                    $status_label = $status['label'];
                }
            }
        } else {
            $settings_url = get_site_url() . '/wp-admin/options-general.php?page=chief_editor_single_options&tab=chiefed_gf_options';
            $status_label = $currentStatus . ' (<a href=' . $settings_url . '>Please configure plugin</a>)';
        }
        return $status_label;
    }
	

	public static function get_label_for_category($currentStatus)
	{
	    return self::get_label_for($currentStatus, self::$categories);
	}
	
	public static function get_label_for_status($currentStatus,$type = 'print')
    {
        $array = $type == 'print' ? self::$printStatusesToAdd : self::$webStatusesToAdd;
        return self::get_label_for($currentStatus, $array);
    }

    
    public static function getMetaFields(){
    	return self::$fields;
    }
    /**
     * Generates the field's HTML for the meta box.
     */
    public function generate_fields($post)
    {
        CHIEFED_UTILS::getLogger()->debug("generate_fields " . count(self::$fields));
        $output = '';
        foreach (self::$fields as $field) {
            $label = '<label for="' . $field['id'] . '">' . $field['label'] . '</label>';
            $db_value = get_post_meta($post->ID, 'informations_complmentaires_' . $field['id'], true);
            CHIEFED_UTILS::getLogger()->debug("Creating meta box for field " . $field['type'] . ' => ' . $field['label']);
            switch ($field['type']) {
                case 'textarea':
                    $input = sprintf('<textarea class="large-text" id="%s" name="%s" rows="5">%s</textarea>', $field['id'], $field['id'], $db_value);
                    break;
                case 'select':
                    
                    if ($field['id'] == 'article-status') {
                        $keyValueList = array();
                        foreach (self::$printStatusesToAdd as $status) {
                            $keyValueList[$status['name']] = $status['label'];
                        }
                        
                        $valueToSet = (empty($db_value) || false === $db_value) ? self::$printDefaultStatus : $db_value;
                        $currentStatusColor = self::get_color_for_status($valueToSet);
                        $currentStatusLabel = self::get_label_for_status($valueToSet);
                        $description = '<div style="color:white;width:100%;background-color:' . $currentStatusColor . ';">' . $currentStatusLabel . '</div>';
                        $input = $this->chiefed_meta_field_select($field['label'], $field['id'], $keyValueList, $description, $valueToSet, true, false);
                        $label = '<label for="' . $field['id'] . '">' . $field['label'] . $description . '</label>';
                        // $input .= $description;
                    } else if ($field['id'] == 'inside-shot') {
                        
                        $dropdown_args = array(
                            'post_type' => get_option('chiefed_shots_cpt_name'),
                            /*'exclude_tree'     => $post->ID,*/
						    'selected' => $db_value,
                            'id' => $field['id'] . '_id',
                            'name' => $field['id'],
                            'show_option_none' => __('not set yet','chief-editor'),
                            /* 'sort_column'      => 'menu_order, post_title',*/
                            'echo' => 0,
                            'post_status' => array(
                                'future',
                                'publish'
                            )
                        );
                        CHIEFED_UTILS::getLogger()->debug($dropdown_args);
                        $input = wp_dropdown_pages($dropdown_args);
                        CHIEFED_UTILS::getLogger()->debug($input);
                        $newUrl = admin_url('post-new.php?post_type=' . get_option('chiefed_shots_cpt_name'));
                        $input .= '<a target="_blank" class="button button-primary" href="' . $newUrl . '" >Ajouter un numéro</a>';
                    }
                    
                    break;
                default:
                    $input = sprintf('<input %s id="%s" name="%s" type="%s" value="%s">', $field['type'] !== 'color' ? 'class="regular-text"' : '', $field['id'], $field['id'], $field['type'], $db_value);
            }
            $output .= $this->row_format($label, $input);
        }
        echo '<table class="form-table"><tbody>' . $output . '</tbody></table>';
    }

    /**
     * Generates the HTML for table rows.
     */
    public function row_format($label, $input)
    {
        return sprintf('<tr><th scope="row">%s</th><td>%s</td></tr>', $label, $input);
    }

    /**
     * Hooks into WordPress' save_post function
     */
    public function save_post($post_id)
    {
        if (! isset($_POST['informations_complmentaires_nonce']))
            return $post_id;
        
        $nonce = $_POST['informations_complmentaires_nonce'];
        if (! wp_verify_nonce($nonce, 'informations_complmentaires_data'))
            return $post_id;
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $post_id;
        
        foreach (self::$fields as $field) {
            if (isset($_POST[$field['id']])) {
                CHIEFED_UTILS::getLogger()->debug("save field with type " . $field['type'] . ' : ' . $field['id']);
                switch ($field['type']) {
                    
                    case 'email':
                        $_POST[$field['id']] = sanitize_email($_POST[$field['id']]);
                        break;
                    case 'text':
                        $_POST[$field['id']] = sanitize_text_field($_POST[$field['id']]);
                        break;
                }
                
                update_post_meta($post_id, 'informations_complmentaires_' . $field['id'], $_POST[$field['id']]);
            } else if ($field['type'] === 'checkbox') {
                update_post_meta($post_id, 'informations_complmentaires_' . $field['id'], '0');
            }
        }
    }

    private function codeToMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;
            
            default:
                $message = "Unknown upload error";
                break;
        }
        return $message;
    }

    function save_custom_meta_data($id)
    {
        CHIEFED_UTILS::getLogger()->debug('wp_custom_attachment inside function ' . $id);
        /* --- security verification --- */
        
        if (isset($_POST['wp_custom_attachment_nonce']) && ! wp_verify_nonce($_POST['wp_custom_attachment_nonce'], plugin_basename(__FILE__))) {
            return $id;
        } /*
           * else {
           * wp_die("Cheating uh?...");
           * }
           */
        
        CHIEFED_UTILS::getLogger()->debug('wp_custom_attachment nonce checked');
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $id;
        } // end if
        CHIEFED_UTILS::getLogger()->debug('wp_custom_attachment not autosave');
        if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
            if (! current_user_can('edit_page', $id)) {
                return $id;
            } // end if
        } else {
            if (! current_user_can('edit_page', $id)) {
                return $id;
            } // end if
        } // end if
        /* - end security verification - */
        CHIEFED_UTILS::getLogger()->debug('save_custom_meta_data User can edit page');
        CHIEFED_UTILS::getLogger()->debug($_FILES);
        // CHIEFED_UTILS::getLogger()->debug($_POST['wp_custom_attachment']);
        // Make sure the file array isn't empty
        if (! empty($_FILES['wp_custom_attachment']['name'])) {
            CHIEFED_UTILS::getLogger()->debug('save_custom_meta_data some file OK');
            // Setup the array of supported file types. In this case, it's just PDF.
            $supported_types = array(
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.oasis.opendocument.text', // odt
                'application/zip'
                                                              // application/vnd.apple.keynote
                                                              // pages not supported : convert to other type
            );
            
            CHIEFED_UTILS::getLogger()->debug("check file type:");
            CHIEFED_UTILS::getLogger()->debug($supported_types);
            
            // Get the file type of the upload
            // if (isset($_FILES['wp_custom_attachment']['name'])){
            $arr_file_type = wp_check_filetype(basename($_FILES['wp_custom_attachment']['name']));
            CHIEFED_UTILS::getLogger()->debug($arr_file_type);
            $uploaded_type = $arr_file_type['type'];
            /*
             * } else {
             * CHIEFED_UTILS::getLogger()->debug("Problem uploading file with post");
             * CHIEFED_UTILS::getLogger()->debug($arr_file_type);
             * return;
             * }
             */
            
            // Check if the type is supported. If not, throw an error.
            if (in_array($uploaded_type, $supported_types)) {
                CHIEFED_UTILS::getLogger()->debug('save_custom_meta_data correct type OK');
                // Use the WordPress API to upload the file
                $upload = wp_upload_bits($_FILES['wp_custom_attachment']['name'], null, file_get_contents($_FILES['wp_custom_attachment']['tmp_name']));
                
                if (isset($upload['error']) && $upload['error'] != 0) {
                    wp_die('There was an error uploading your file. The error is: ' . $upload['error']);
                } else {
                    CHIEFED_UTILS::getLogger()->debug('save_custom_meta_data adding file as attachment to post...');
                    add_post_meta($id, 'wp_custom_attachment', $upload);
                    update_post_meta($id, 'wp_custom_attachment', $upload);
                    CHIEFED_UTILS::getLogger()->debug('save_custom_meta_data ...Done');
                } // end if/else
            } else {
                $msg = __('The file type you\'ve uploaded is not supported : ','chief-editor'). $uploaded_type;
                CHIEFED_UTILS::getLogger()->debug($msg);
                wp_die($msg);
            } // end if/else
        } else {
            // error uploading file
            $msg = __('Cannot upload file because','chief-editor');
            $errorCode = $_FILES['wp_custom_attachment']['error'];
            
            if ($errorCode != 4 && ! empty($errorCode)) {
                $msg = __('Cannot upload file because','chief-editor') . ' ' . $this->codeToMessage($_FILES['wp_custom_attachment']['error']);
                CHIEFED_UTILS::getLogger()->debug($msg);
                wp_die($msg);
            } /*else {
                wp_die($msg);
            }*/
        }
    } // end save_custom_meta_data


}
new Chief_Editor_Meta_Boxes;