<?php
if (! defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

$path_to_class = sprintf("%s/meta_boxes.php", dirname(__FILE__));
require_once ($path_to_class);
$path = sprintf("%s/chiefed_utils.php", dirname(dirname(__FILE__)));
require_once ($path);

if (! class_exists('CHIEFED_ADMIN')) {

    class CHIEFED_ADMIN
    {

        static function responsive_html_table($table_caption = "", $header = array(), $data = array())
        {
            $headerRow = '<thead>';
            $headerRow .= '<tr><th scope="col">' . implode('</th><th scope="col">', $header) . '</th></tr>';
            $headerRow .= '<thead>';
            CHIEFED_UTILS::getLogger()->debug($headerRow);
            $rows = array();
            
            foreach ($data as $row) {
                $cells = array();
                $colIdx = 0;
                foreach ($row as $cell) {
                    $addToFirst = ($colIdx == 0) ? 'scope="row"' : '';
                    $cells[] = "<td " . $addToFirst . " data-label=" . $header[$colIdx] . ">" . $cell . "</td>";
                    $colIdx ++;
                }
                
                $rows[] = "<tr>" . implode('', $cells) . "</tr>";
            }
            
            $result = '<table class="chiefed">';
            $result .= "<caption>" . $table_caption . "</caption>";
            $result .= $headerRow;
            $result .= "<tbody>" . implode('', $rows) . "</tbody></table>";
            
            CHIEFED_UTILS::getLogger()->debug($result);
            
            return $result;
        }

        static function html_table($data = array())
        {
            $rows = array();
            foreach ($data as $row) {
                $cells = array();
                foreach ($row as $cell) {
                    $cells[] = "<td>{$cell}</td>";
                }
                $rows[] = "<tr>" . implode('', $cells) . "</tr>";
            }
            return "<table class='hci-table'>" . implode('', $rows) . "</table>";
        }

        static function testIfLinuxPackageInstalled($request)
        {
            $outputArray = array();
            // dpkg -l | grep poppler-utils
            $cmd = "dpkg -l | grep " . $request;
            CHIEFED_UTILS::getLogger()->debug($cmd);
            $cmdOut = exec($cmd, $outputArray);
            CHIEFED_UTILS::getLogger()->debug($cmdOut);
            CHIEFED_UTILS::getLogger()->debug($outputArray);
            
            $found = ! empty($outputArray[0]);
            
            if (! $found) {
                $cmd = "opkg list-installed | grep " . $request;
                CHIEFED_UTILS::getLogger()->debug($cmd);
                $cmdOut = exec($cmd, $outputArray);
                CHIEFED_UTILS::getLogger()->debug($cmdOut);
                CHIEFED_UTILS::getLogger()->debug($outputArray);
                $found = ! empty($outputArray[0]);
            }
            
            return $found ? '<span style="color:green;">Installed: ' . $outputArray[0] . '</span>' : '<span style="color:red;">Not installed! ' . $cmdOut . '</span>';
        }

        static function testIfPythonPackageInstalled($request)
        {
            
            // $cmd = "python -c 'import pip;import pkg_resources;if pkg_resources.get_distribution('pip').version > 10: from pip._internal.utils.misc import get_installed_distributions;installed_packages = pip.get_installed_distributions();print(installed_packages);'";
            $scriptFile = "python " . plugin_dir_path(__DIR__) . 'py/getInstalledPythonPackages.py';
            CHIEFED_UTILS::getLogger()->debug($scriptFile);
            $pyCmd = $scriptFile; // . ' ' . $imagesSourceFile . ' ' . $tempImageFolder;
            CHIEFED_UTILS::getLogger()->debug($pyCmd);
            
            // $retValue = shell_exec ( $pyCmd );
            
            $outputArray = array();
            $pythonVersion = exec($pyCmd, $outputArray);
            CHIEFED_UTILS::getLogger()->debug($outputArray[0]);
            $trimmed = trim($outputArray[0], "[");
            $trimmed = trim($trimmed, "]");
            $allPackages = explode(', ', $trimmed);
            CHIEFED_UTILS::getLogger()->debug($allPackages);
            $found = false;
            $completeName = '';
            foreach ($allPackages as $package) {
                $foundPos = strpos($package, $request);
                if ($foundPos !== false) {
                    $found = true;
                    $completeName = $package;
                    CHIEFED_UTILS::getLogger()->debug('found at ' . $foundPos);
                }
            }
            
            return $found ? '<span style="color:green;">Installed: ' . $completeName . '</span>' : '<span style="color:red;">Not installed!</span>';
        }

        public static function system_requirements()
        {
            ?>

<p><?php _e( "System requirements", 'chief-editor' ); ?></p>


<?php
            $cmd = "python -c 'import sys; print sys.version'";
            // echo "Using command: " . $cmd;
            $outputArray = array();
            $pythonVersion = exec($cmd, $outputArray);
            CHIEFED_UTILS::getLogger()->debug($outputArray);
            if (count($outputArray) > 0) {
                $pythonText = '<span style="color:green;">Installed: ' . $outputArray[0] . '</span>';
            } else {
                $pythonText = '<span style="red;">Not installed!</span>';
            }
            $data = array();
            $data[] = array(
                '1' => '<b>Required</b>',
                '2' => '<b>Status</b>'
            );
            $data[] = array(
                '1' => 'Python',
                '2' => $pythonText
            );
            $packsToCheck = array(
                'python-docx',
                'python-pptx',
                'pdfminer',
                'Pillow',
                'docxpy'
            );
            foreach ($packsToCheck as $packToCheck) {
                $data[] = array(
                    '1' => $packToCheck,
                    '2' => self::testIfPythonPackageInstalled($packToCheck)
                );
            }
            
            echo '<fieldset class="chiefed_setting_fieldset"><legend>' . __('Python packages required', 'chief-editor') . '</legend>';
            
            echo self::html_table($data);
            echo '</fieldset>';
            echo '<fieldset class="chiefed_setting_fieldset"><legend>' . __('Linux packages required', 'chief-editor') . '</legend>';
            
            $dataLinux = array();
            $dataLinux[] = array(
                '1' => '<b>Linux Package Required</b>',
                '2' => '<b>Status</b>'
            );
            
            $linuxPacksToCheck = array(
                'poppler-utils'
            );
            foreach ($linuxPacksToCheck as $packToCheck) {
                $dataLinux[] = array(
                    '1' => $packToCheck,
                    '2' => self::testIfLinuxPackageInstalled($packToCheck)
                );
            }
            
            echo self::html_table($dataLinux);
            echo '</fieldset>';
        }

        public static function chiefed_list_all_options()
        {
            $all_options = wp_load_alloptions();
            $my_options = array();
            $toDisplay = '<table>';
            foreach ($all_options as $name => $value) {
                // if ( stristr( $name, '_transient' ) ) {
                $my_options[$name] = $value;
                $toDisplay .= '<tr>';
                $toDisplay .= '<td>' . $name . '</td><td>' . $value . '</td>';
                $toDisplay .= '</tr>';
                // }
            }
            
            $toDisplay .= '</table>';
            echo $toDisplay;
        }

        public static function chief_editor_calendar_settings()
        {
            if (! empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'chiefed_options_save')) {
                foreach ($_REQUEST as $option_name => $option_value) {
                    $prefix = 'chiefed_';
                    $prefixSize = strlen($prefix);
                    if (substr($option_name, 0, $prefixSize) == $prefix) {
                        if ($option_name == 'chiefed_scripts_limit') {
                            $option_value = str_replace(' ', '', $option_value);
                        } // clean up comma seperated emails, no spaces needed
                        update_option($option_name, $option_value);
                    }
                }
                if (empty($_REQUEST['chiefed_post_taxonomies'])) {
                    update_option('chiefed_post_taxonomies', '');
                }
                echo '<div class="updated notice"><p>' . __('Settings saved.', 'chief-editor') . '</p></div>';
            } else {
                echo 'Cannot verify nonce';
            }
            
            ?>
<form method="post" action="">
    <?php wp_nonce_field('chief-editor') ?>
<?php
            echo '<fieldset class="chiefed_setting_fieldset"><legend>' . __('Default post type shown in calendar - WPFullCalendar must be installed', 'chief-editor') . '</legend>';
            echo 'Use shortcode <pre>[fullcalendar type="post_type"]</pre> to display posts of any type';
            echo __('In Web workflow (post type is <pre>post</pre>), date used to display posts on calendar is post_date');
            echo sprintf('in Print workflow (post type is <pre>%s</pre>), reference date is Shot date, then post date', CHIEFED_PRE_DESKTOP_PUBLISHING_CPT);
            echo "<ul>";
            foreach (get_post_types(apply_filters('chiefed_get_post_types_args', array(
                'public' => true
            )), 'names') as $post_type) {
                $checked = get_option('chiefed_default_type', CHIEFED_PRE_DESKTOP_PUBLISHING_CPT) == $post_type ? 'checked' : '';
                $post_data = get_post_type_object($post_type);
                echo "<li><label><input type='radio' class='chiefed-post-type' name='chiefed_default_type' value='$post_type' $checked />&nbsp;&nbsp;{$post_data->labels->name} (<em>$post_type</em>)</label>";
                
                echo "</li>";
            }
            echo "</ul>";
            echo '</fieldset>';
            echo '<fieldset class="chiefed_setting_fieldset"><legend>' . __('Time filters', 'chief-editor') . '</legend>';
            $title = __('Time filters available to filter display', 'chief-editor');
            $name = 'chiefed_time_filters' . $suffix;
            $description = __('enter all needed time filters (one by line) using : <b>php_time | Label | default</b>.<br/> For example : -10 days | < 10 Days | 1', 'chief-editor');
            $defaultTimeFiltersArray = array(
                "-10 days | < 10 days | 1",
                "-1 month | < 1 month",
                "-2 month | < 2 month",
                "-6 month | < 6 month",
                "-1 year | < 1 year",
                "-2 years | < 2 years"
            );
            $defaultTimeFilters = implode('\n', $defaultTimeFiltersArray);
            chiefed_options_textarea($title, $name, $description, $defaultTimeFilters);
            
            ?>
            </fieldset>
	<input type="hidden" name="_wpnonce"
		value="<?php echo wp_create_nonce('chiefed_options_save'); ?>" />
        <?php submit_button(__( 'Save options', 'chief-editor' ) ); ?>        
</form>
<?php
        }

        public static function chief_editor_web_settings($suffix = '')
        {
            if (! empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'chiefed_options_save')) {
                CHIEFED_UTILS::getLogger()->debug($_REQUEST);
                foreach ($_REQUEST as $option_name => $option_value) {
                    $prefix = 'chiefed_';
                    $prefixSize = strlen($prefix);
                    $wpeditorPrefix = $prefix.'wpeditor_';
                    $wpeditorPrefixSize = strlen($wpeditorPrefix);
                    if (substr($option_name, 0, $prefixSize) == $prefix) {
                        CHIEFED_UTILS::getLogger()->debug("chiefed_  type field: ".$option_name);
                        if ($option_name == 'chiefed_scripts_limit') {
                            $option_value = str_replace(' ', '', $option_value);
                        } else if (substr($option_name,0, $wpeditorPrefixSize) == $wpeditorPrefix) {
                            CHIEFED_UTILS::getLogger()->debug("wp editor type field: ".$option_name);
                            // is wpeditor field
                            $option_value = htmlentities(stripslashes($option_value));
                            CHIEFED_UTILS::getLogger()->debug($option_value);
                            update_option($option_name, $option_value);
                        }
                        
                        update_option($option_name, $option_value);
                    } 
                }
                if (empty($_REQUEST['chiefed_post_taxonomies'])) {
                    update_option('chiefed_post_taxonomies', '');
                }
                echo '<div class="updated notice"><p>' . __('Settings saved.', 'chief-editor') . '</p></div>';
            } else {
                echo 'Cannot verify nonce';
            }
            
            ?>
<form method="post" action="">
    <?php wp_nonce_field('chief-editor') ?>
<?php
            
            echo '<fieldset class="chiefed_setting_fieldset"><legend>' . __('Statuses, Categories and Filters', 'chief-editor') . '</legend>';
            // $fieldSetStyle = 'border:solid #C4C4C4 1px;padding:1em;margin:5px 0;';
            
            $title = __('Statuses and colors for WEB workflow', 'chief-editor');
            $name = 'chiefed_wwf_statuses_and_colors' . $suffix;
            $description = __('enter all needed statuses (one by line) using : <b>status_name | status_label | status_color</b>.<br/> For example : status1 | My Status | #cecece', 'chief-editor');
            $description .= __('this should be WP post statuses', 'chief-editor');
            $defaultStatusesArray = array(
                "future | Planifie | #0f89db",
                "pending | BAT | #19a50d",
                "in-progress | En cours | #e0d900",
                "built | Built | #d80000",
                "assign | Received | #733f55",
                "draft | Brouillon | #cecece",
                "pitch | Pitch | #565656"
            );
            $defaultStatuses = implode("\n", $defaultStatusesArray);
            chiefed_options_textarea($title, $name, $description, $defaultStatuses);
            ?>
            </fieldset>
            
            <?php
            echo '<fieldset class="chiefed_setting_fieldset"><legend>' . __('Emails', 'chief-editor') . '</legend>';
            $title = __('Sender email address', 'chief-editor');
            $description = __('Email address used for sendings', 'chief-editor');
            $name = 'chiefed_sender_email';
            /*
             * $settings[] = array(
             * 'id' => 'sender_email',
             * 'name' => __('Sender email address','chief-editor'),
             * 'desc' => __('Email address used for sendings','chief-editor'),
             * 'type' => 'text',
             * 'size' => 'regular'
             * );
             */
            chiefed_options_input_text($title, $name, $description, '');
            echo '<br/><br/>';
            $title = __('Sender name', 'chief-editor');
            $description = __('Name, as it will be seen by recipients', 'chief-editor');
            $name = 'chiefed_sender_name';
            chiefed_options_input_text($title, $name, $description, '');
            echo '<br/><br/>';
            $title = __('Recipients emails', 'chief-editor');
            $description = __('Addresses to which all email will be sent to (use , as separator)', 'chief-editor');
            $name = 'chiefed_email_recipients';
            chiefed_options_input_text($title, $name, $description, '');
            echo '<br/><br/>';
            /*
             * $settings[] = array(
             * 'tag' => 'textarea',
             * 'rows' => '20',
             * 'cols' => '110',
             * 'id' => 'email_content-textarea',
             * 'name' => __('Email content','chief-editor'),
             * 'desc' => __('This is the standard email sent for to authors in order to validate the post','chief-editor') . '<br/>' . __('You can use the following tags inside:','chief-editor') . '<br/>' . '<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%username%</span>' . '<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%userlogin%</span>' . '<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%useremail%</span>' . '<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%postlink%</span>' . '<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%posttitle%</span>' . '<span style="padding:2px 5px;margin:2px 5px;background-color:#5C5C5C;color:#CCCCCC;border-radius:4px;">%blogurl%</span>',
             * 'std' => '50',
             * 'type' => 'text'
             * );
             */
            
            $defaultEmailContent = 'Cher %username%,<br />
Voici la previsualisation de votre article pour obtention d\'un Bon A Tirer : <br />
		        
<h2><a href="%postlink%" target="_blank">%posttitle%</a></h2><br />
		        
Vous devez etre authentifie avec vos identifiants personnels <a href="%blogurl%">sur le site</a> pour visualiser cet article en ligne:
<ul><li>Utiliser votre login : <strong>%userlogin%</strong></li>
<li>et votre mot de passe (si vous l\'avez oublie, demandez-en un nouveau en cliquant ici : <a href="%lostpassword_url%">Service de recuperation de mot de passe</a>)
</ul>
Si le message suivant apparait:<br />
<em>Desole, mais la page demande ne peut etre trouvee.</em>
c\'est que vous n\'etes pas connecte au site.
<h2>En cas de probleme</h2>Merci de suivre la procedure suivante pour visualiser votre post en ligne:<br />
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
		        
<br />Cordialement, L\'equipe';
            
            $title = __('Email content', 'chief-editor');
            $name = 'chiefed_wpeditor_' . '_email_content';
            $keywordsArray = array(
                '%username%',
                '%userlogin%',
                '%useremail%',
                '%postlink%',
                '%posttitle%',
                '%xmlfile%',
                '%imgdir%',
                '%lostpassword_url%',
            );
            
            
                
            $description = __('This is the standard email sent for to authors in order to validate the post', 'chief-editor') . '<br/>';
            $description .= __('You can use the following tags inside:', 'chief-editor') . '<br/>';
            $description .= '<span class="chiefed_possible_keywords">' .
                implode( '</span><span class="chiefed_possible_keywords">', $keywordsArray ) . '</span>';
            
            chiefed_options_wpeditor($title, $name, $description, $defaultEmailContent);
            echo '<br/><br/>';
            ?>
            </fieldset>
            
            <?php
            echo '<fieldset class="chiefed_setting_fieldset"><legend>' . __('Chief editors by category', 'chief-editor') . '</legend>';
            // Iterate through your list of blogs
            
            $categories = get_categories(array(
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            $title = __('Main category');
            $option_name = CHIEFED_MANAGER_OPTION_PREFIX . 'main_notification_category';
            $description = __('Selected chief editors will be notified of posts published both in this category and the other in the list below', 'chief-editor');
            $categoriesList = array();
            foreach ($categories as $category) {
                $categoriesList[$category->term_id] = $category->name;
            }
            chiefed_options_select($title, $option_name, $categoriesList, $description, $default = '');
            $main_chiefed_category = get_option($option_name);
            /*
             * CHIEFED_UTILS::getLogger()->debug("Remove ".$main_chiefed_category);
             * CHIEFED_UTILS::getLogger()->debug("from ");
             * CHIEFED_UTILS::getLogger()->debug($categories);
             */
            $keyCategoryToRemove = array_search($main_chiefed_category, array_column($categories, 'term_id'));
            unset($categories[$keyCategoryToRemove]);
            
            foreach ($categories as $category) {
                
                
                CHIEFED_UTILS::getLogger()->debug($category->name);
                echo '<hr/>';
                echo '<p>';
                $category_link = sprintf('<a href="%1$s" alt="%2$s">%3$s</a>', esc_url(get_category_link($category->term_id)), esc_attr(sprintf(__('View all posts in %s', 'chief-editor'), $category->name)), esc_html($category->name));
                
                echo '<span class="chiefed_category_title">' . sprintf(esc_html__('%s', 'chief-editor'), $category_link) . '</span> ';
                echo '<br/><span>' . sprintf(esc_html__('Description: %s', 'chief-editor'), $category->description) . '</span>';
                echo '<br/><span>' . sprintf(esc_html__('Post Count: %s', 'chief-editor'), $category->count) . '</span>';
                echo '</p>';
                $chief_editors_roles = array(
                    'author',
                    'contributor',
                    'editor',
                    'administrator'
                );
                
                $user_args = array(
                    'blog_id' => $GLOBALS['blog_id'],
                    'role__in' => $chief_editors_roles,
                    'orderby' => 'user_nicename',
                    'order' => 'ASC'
                );
                // echo $user_args;
                $blogusers = get_users($user_args);
                CHIEFED_UTILS::getLogger()->debug(count($blogusers) . " blog users");
                
                $blog_id = 0; // like blog number 0 because not multisite
                $fieldID = 'chiefed_editors_selector_' . $category->term_id;
                $baseOptionChiefEds = get_option('chiefed_chiefeditor_option');
                $fieldName = 'chiefed_chiefeditor_option[' . $category->term_id . ']';
                $fieldNameArray = $baseOptionChiefEds[$category->term_id];
          
                $allCheckedOptions = get_option($fieldName);
 
                $chief_editors_options_list = get_option('chief_editor_option');

                $select_start = sprintf('<select multiple="multiple" name="%s[]" id="%s" class="widefat" size="5" style="margin-bottom:10px">', $fieldName, $fieldID);

                echo $select_start;
                
                // wp_json_encode(
                // Each individual option
                $chiefEdsForCat = array();
                foreach ($blogusers as $user) {
                    $selectedState = '';
                    $id = $user->ID;
                    $userEmail = $user->user_email;
                    $userLogin = $user->user_login;
                    $userNicename = $user->display_name;
                    
                    // CHIEFED_UTILS::getLogger()->debug ("$id : $userEmail = ");
                    $checkedOptions = $fieldNameArray; // $chief_editors_options_list[$category->term_id];
                                                       // CHIEFED_UTILS::getLogger()->debug ($checkedOptions);
                    if ($checkedOptions) {
//                        CHIEFED_UTILS::getLogger()->debug($checkedOptions);
                        $needSelection = in_array($id, $checkedOptions);
                        $selectedState = $needSelection ? 'selected="selected"' : '';
                        if ($needSelection) {
                            $chiefEdsForCat[] = $userNicename;
                        }
                    }
                    $new_option = sprintf('<option value="%s" %s style="margin-bottom:3px;">%s</option>', $id, $selectedState, $id . ' - ' . $userLogin . ' - ' . $userNicename . ' (' . $userEmail . ')');
                    echo $new_option;
                }
                echo '</select>';
                
                echo '<p class="chiefed_category_title">Current Chief Editors: ';
                echo '<span class="chiefed_chiefed_nicename">' . sprintf(esc_html__('%s', 'chief-editor'), implode('</span> + <span class="chiefed_chiefed_nicename">', $chiefEdsForCat)) . '</span>';
                echo '</p>';
            }
            ?>            
            
	<input type="hidden" name="_wpnonce"
		value="<?php echo wp_create_nonce('chiefed_options_save'); ?>" />
        <?php submit_button(__( 'Save options', 'chief-editor' ) ); ?>        
</form>
<?php
        }

        public static function chief_editor_print_settings($suffix = '')
        {
            if (! empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'chiefed_options_save')) {
                foreach ($_REQUEST as $option_name => $option_value) {
                    $prefix = 'chiefed_';
                    $prefixSize = strlen($prefix);
                    if (substr($option_name, 0, $prefixSize) == $prefix) {
                        if ($option_name == 'chiefed_scripts_limit') {
                            $option_value = str_replace(' ', '', $option_value);
                        } // clean up comma seperated emails, no spaces needed
                        update_option($option_name, $option_value);
                    }
                }
                if (empty($_REQUEST['chiefed_post_taxonomies'])) {
                    update_option('chiefed_post_taxonomies', '');
                }
                echo '<div class="updated notice"><p>' . __('Settings saved.', 'chief-editor' ) . '</p></div>';
			} else {
				echo 'Cannot verify nonce';
			}
			
			?>
<form method="post" action="">
    <?php wp_nonce_field('chief-editor') ?>
    <?php
			/*
			 * echo '<fieldset class="chiefed_setting_fieldset"><legend>' . __('Posts and CPT options', 'chief-editor')
			 * . '</legend>';
			 *
			 * $title = __('Custom Post Type name for Articles', 'chief-editor');
			 * $name = 'chiefed_articles_cpt_name';
			 * $description = __('');
			 * chiefed_options_input_text($title, $name, $description, 'ce_pre_desktop_pub');
			 *
			 * $title = __('Custom Post Type name for Shots', 'chief-editor');
			 * $name = 'chiefed_shots_cpt_name';
			 * $description = __('');
			 * chiefed_options_input_text($title, $name, $description, 'ce_periodical_cpt');
			 *
			 * echo '</fieldset>';
			 */
			echo '<fieldset class="chiefed_setting_fieldset"><legend>' .
				 __( 'Statuses, Categories and Filters', 'chief-editor' ) . '</legend>';
			
			$title = __( 'Statuses and colors for PRINT workflow', 'chief-editor' );
			$name = 'chiefed_pwf_statuses_and_colors' . $suffix;
			$description = __( 
				'enter all needed statuses (one by line) using : <b>status_name | status_label | status_color</b>.<br/> For example : status1 | My Status | #cecece',
				'chief-editor' );
			$description .= __( 
				'you can define your own statuses (will appear in Status post meta box)',
				'chief-editor' );
			/*
			 * waiting_for_reception | à recevoir | #480000
			 * received | reçu | #733f55
			 * draft | montage (transmis à la PAO) | #143850
			 * built | monté | #5271be
			 * pending_review | en relecture | #f38334
			 * pending_author_validation | en BAT (envoyé à l auteur) | #043300
			 * pending | BAT | #0fad03
			 * publish | Publié | #0B7D03
			 */
			$defaultStatusesArray = array( 
				"waiting_for_reception | à recevoir | #480000",
				"received | reçu | #733f55",
				"draft | montage (transmis à la PAO) | #143850",
				"built | monté | #5271be",
				"pending_review | en relecture | #f38334",
				"pending_author_validation | en BAT (envoyé à l auteur) | #043300",
				"pending | BAT | #0fad03",
				"publish | Publié | #0B7D03" );
			$defaultStatuses = implode( "\n", $defaultStatusesArray );
			chiefed_options_textarea( $title, $name, $description, $defaultStatuses );
			echo '<hr/>';
			$title = __( 'Categories and colors', 'chief-editor' );
			$name = 'chiefed_categories_and_colors' . $suffix;
			$description = __( 
				'enter all needed categories (one by line) using : <b>category_slug | category_label | category_color</b>.<br/> For example : cat1 | My Cat | #cecece',
				'chief-editor' );
			$defaultStatuses = "";
			chiefed_options_textarea( $title, $name, $description, $defaultStatuses );
			
			?>
            </fieldset>


	<fieldset class="chiefed_setting_fieldset">
		<legend>
            <?php echo __('InDesign Connector','chief-editor'); ?>
            </legend>
                <?php
			
			chiefed_options_radio_binary( 
				__( 'Enable InDesign XML exports on save post?', 'chief-editor' ),
				'chiefed_xml_exports_enabled',
				'' );
			
			$title = __( 'Output XML directory' );
			$name = 'chiefed_xml_exports_dir';
			$description = __( 
				'Where all XML files will be written, each time a publish / update button is pressed on CPT, starting from wp-content/uploads dir (can include symbolic links, but necessitates to tweak web serveur php <em>open_basedir</em> parameter)' );
			chiefed_options_input_text( $title, $name, $description, 'chiefed_xml_exports' );
			
			$title = __( 'If necessary, string to search for replacement in created path for images' );
			$name = 'chiefed_xml_exports_path_search';
			$description = __( 'Wordpress view on storage may differ from InDesign users view on storage' );
			chiefed_options_input_text( $title, $name, $description );
			
			$title = __( 'If necessary, replacement string for previous search parameter' );
			$name = 'chiefed_xml_exports_path_replace';
			$description = __( 'Wordpress view on storage may differ from InDesign users view on storage' );
			chiefed_options_input_text( $title, $name, $description );
			
			?>
            </fieldset>
	<fieldset class="chiefed_setting_fieldset">
		<legend>
                <?php echo __('Managers notifications','chief-editor'); ?>
                </legend>
            <?php
			
			chiefed_options_radio_binary( 
				__( 'Enable email notifications for managers ?', 'chief-editor' ),
				'chiefed_enable_notifications',
				'' );
			
			$periodicalTaxonomy = PreDesktopPublishing::$taxoName;
			// echo '<h3>'.$periodicalTaxonomy.'</h3>';
			$terms = get_terms( 
				array( 'taxonomy' => $periodicalTaxonomy, 'hide_empty' => false ) );
			
			if ( count( $terms ) > 0 ) {
				$blogusers = get_users( [ 'role__in' => [ 'administrator', 'editor', 'author', 'contributor' ] ] );
				
				echo '<b>' . count( $blogusers ) . ' potential manager users found:</b><br/>';
				
				// Array of WP_User objects.
				// echo '<h4>Users items : '.count($blogusers).'</h4>';
				foreach ( $blogusers as $user ) {
					$key = $user->ID;
					$label = $user->ID . ') ' . $user->display_name . ' ('.$user->user_email.')';
					$userList[$key] = $label;
					//echo $user->ID . ' : ' . $user->display_name . '('.$user->user_email.')'.'<br/>';
				}
				
				// echo '<h4>Users list items : '.count($userList).'</h4>';
				foreach ( $terms as $termKey => $termDatas ) {
					// CHIEFED_UTILS::getLogger()->debug($termDatas);
					// echo implode(' | ',$termDatas);
					$title = __( 'Manager for' ) . ' ' . $termDatas->name;
					// echo $title;
					$slug = $termDatas->slug;
					$option_name = CHIEFED_MANAGER_OPTION_PREFIX . $slug;
					// echo $option_name;
					$description = $termDatas->description;
					// echo $title . ' | '.$slug . ' | '.$option_name.' | '.$description;
					chiefed_options_select( $title, $option_name, $userList, $description, $default = '' );
				}
			} else {
				echo '<span class="chiefed_missing_setting">Periodicals : ' . count( $terms ) . '</span><br/>';
				echo '<span class="chiefed_missing_setting">' .
					 __( 'You need to create periodicals in order to affect it to a manager' ) . '</span><br/>';
				
				// http://www.idweblogs.eu/test/wp-admin/post-new.php?post_type=ce_periodical_cpt
				// echo admin_url( 'edit-tags.php?taxonomy=category', 'https' );
				$createPeriodicalUrl = admin_url( 'post-new.php?post_type=' . get_option( 'chiefed_shots_cpt_name' ) );
				echo '<a target="_blank" href="' . $createPeriodicalUrl . '">Create first periodical now</a>';
			}
			
			echo '<br/><br/>';
			
			$title = __( 'Email template for managers', 'chief-editor' );
			$name = 'chiefed_manager_email_template' . $suffix;
			$description = __( 
				'This email will be sent to appropriate manager when a post of specified type is published (or updated)',
				'chief-editor' );
			$description .= '<br/>';
			$keywordsArray = array( 
				'%username%',
				'%userlogin%',
				'%useremail%',
				'%postlink%',
				'%posttitle%',
				'%xmlfile%',
				'%imgdir%',
			    '%lostpassword_url%',
			);
			
			$description .= '<span class="chiefed_possible_keywords">' .
				 implode( '</span><span class="chiefed_possible_keywords">', $keywordsArray ) . '</span>';
			
			$defaultTemplate = __( 
				'Hi %user_firstname%,
            Please find below a new article to prepare : %post_title%' );
			chiefed_options_textarea( $title, $name, $description, $defaultTemplate );
			
			?>
            </fieldset>
	<input type="hidden" name="_wpnonce"
		value="<?php echo wp_create_nonce('chiefed_options_save'); ?>" />
        <?php submit_button(__( 'Save options', 'chief-editor' ) ); ?>        
</form>
<?php
		}
	}
}

/*
 * Admin UI Helpers
 */
function chiefed_options_input_text( $title, $name, $description, $default = '' ) {
	?>
<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
	<th scope="row"><?php echo esc_html($title); ?></th>
	<td><input name="<?php echo esc_attr($name) ?>" type="text"
		id="<?php echo esc_attr($title) ?>" style="width: 95%"
		value="<?php echo esc_attr(get_option($name, $default), ENT_QUOTES); ?>"
		size="45" /><br /> <em><?php echo $description; ?></em></td>
</tr>
<?php
}

function chiefed_options_input_password( $title, $name, $description ) {
	?>
<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
	<th scope="row"><?php echo esc_html($title); ?></th>
	<td><input name="<?php echo esc_attr($name) ?>" type="password"
		id="<?php echo esc_attr($title) ?>" style="width: 95%"
		value="<?php echo esc_attr(get_option($name)); ?>" size="45" /><br />
		<em><?php echo $description; ?></em></td>
</tr>
<?php
}


function chiefed_options_wpeditor($title, $name, $description, $default){
    ?>
<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
	<th scope="row"><?php echo esc_html($title); ?></th>
	<td>
	
    <?php $option_content =  html_entity_decode(get_option($name,htmlentities($default)));
    wp_editor($option_content, $name, $settings ); ?><br />
		<em><?php echo $description; ?></em></td>
</tr>
<?php
}

function chiefed_options_textarea( $title, $name, $description, $default = '', $row = 15, $cols = 60 ) {
	?>
<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
	<th scope="row"><?php echo esc_html($title); ?></th>
	<td><textarea name="<?php echo esc_attr($name) ?>"
			id="<?php echo esc_attr($name) ?>" rows="<?php echo $row; ?>" cols="
<?php echo $cols; ?>"><?php echo wp_kses_post(get_option($name,$default));?></textarea><br />
		<em><?php echo $description; ?></em></td>
</tr>
<?php
}

function chiefed_options_radio( $name, $options, $title = '' ) {
	$option = get_option( $name );
	?>
<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
    <?php if( !empty($title) ): ?>
    <th scope="row"><?php  echo esc_html($title); ?></th>
	<td>
        <?php else: ?>
    
	
	<td colspan="2">
        <?php endif; ?>
        <table>
            <?php foreach($options as $value => $text): ?>
            <tr>
				<td><input
					id="<?php echo esc_attr($name) ?>_<?php echo esc_attr($value); ?>"
					name="<?php echo esc_attr($name) ?>" type="radio"
					value="<?php echo esc_attr($value); ?>"
					<?php if($option == $value) echo "checked='checked'"; ?> /></td>
				<td><?php echo $text ?></td>
			</tr>
            <?php endforeach; ?>
        </table>
	</td>
</tr>
<?php
}

function chiefed_options_radio_binary( $title, $name, $description, $option_names = '' ) {
	if ( empty( $option_names ) )
		$option_names = array( 0 => __( 'No', 'chief-editor' ), 1 => __( 'Yes', 'chief-editor' ) );
	if ( substr( $name, 0, 7 ) == 'dbem_ms' ) {
		$list_events_page = get_site_option( $name );
	} else {
		$list_events_page = get_option( $name );
	}
	?>
<tr valign="top" id='<?php echo $name;?>_row'>
	<th scope="row"><?php echo esc_html($title); ?></th>
	<td>
        <?php echo $option_names[1]; ?> <input
		id="<?php echo esc_attr($name) ?>_yes"
		name="<?php echo esc_attr($name) ?>" type="radio" value="1"
		<?php if($list_events_page) echo "checked='checked'"; ?> />&nbsp;&nbsp;&nbsp;
        <?php echo $option_names[0]; ?> <input
		id="<?php echo esc_attr($name) ?>_no"
		name="<?php echo esc_attr($name) ?>" type="radio" value="0"
		<?php if(!$list_events_page) echo "checked='checked'"; ?> /> <br />
	<em><?php echo $description; ?></em>
	</td>
</tr>
<?php
}

function chiefed_options_select( $title, $name, $list, $description, $default = '' ) {
	$option_value = get_option( $name, $default );
	if ( $name == 'dbem_events_page' && ! is_object( get_page( $option_value ) ) ) {
		$option_value = 0; // Special value
	}
	?>
<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
	<th scope="row"><?php echo esc_html($title); ?></th>
	<td><select name="<?php echo esc_attr($name); ?>">
            <?php foreach($list as $key => $value) : ?>
            <option value='<?php echo esc_attr($key) ?>'
				<?php echo ("$key" == $option_value) ? "selected='selected' " : ''; ?>>
                <?php echo esc_html($value); ?>
            </option>
            <?php endforeach; ?>
        </select> <br /> <em><?php echo $description; ?></em></td>
</tr>
<?php
}
