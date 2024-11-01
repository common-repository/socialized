<?php

namespace AuRise\Plugin\Socialized;

defined('ABSPATH') || exit; // Exit if accessed directly

use AuRise\Plugin\Socialized\Utilities;

/**
 * Plugin Settings File
 *
 * @package AuRise\Plugin\Socialized
 */
class Settings
{
    /**
     * The single instance of the class.
     *
     * @var Settings
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Plugin variables of settings and options
     *
     * @var array $vars
     *
     * @since 1.0.0
     */
    public static $vars = array();

    /**
     * Plugin Platform Settings
     *
     * @var array $vars
     *
     * @since 1.0.0
     */
    public static $platforms = array();

    /**
     * Main Instance
     *
     * Ensures only one instance of is loaded or can be loaded.
     *
     * @since 1.0.0
     *
     * @static
     *
     * @return Settings Main instance.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct()
    {
        $basename = plugin_basename(SOCIALIZED_FILE); // E.g.: "plugin-folder/file.php"
        $slug = sanitize_key(dirname($basename)); // E.g.: "plugin-folder"
        $slug_underscore = str_replace('-', '_', $slug); // E.g.: "plugin_folder"
        load_plugin_textdomain($slug, false, $slug . '/languages'); // Load additional translations if available
        $url = plugin_dir_url(SOCIALIZED_FILE);

        // Sharing Platforms
        self::$platforms = array(
            'facebook' => array(
                'title' => __('Facebook', 'socialized'), //Platform's Title
                'suffix' => '-f', //Suffix for identifying platform's vanity URL
                'icon' => $url . 'assets/images/icon_facebook_32.png', //Absolute URL to icon image
                'fontawesome' => 'facebook-f', //FontAwesome class
                'link_format' => 'https://www.facebook.com/sharer.php?u=%1$s', //Platform's sharing link format
                'width' => 600, //Pop-up window's width
                'height' => 750, //Pop-up window's height
                'prefix_title' => true //Adds "Share on [title] for screenreaders
            ),
            'twitter' => array(
                'title' => __('X (formerly Twitter)', 'socialized'), //Platform's Title
                'suffix' => '-t',  //Suffix for identifying platform's vanity URL
                'icon' => $url . 'assets/images/icon_x_twitter_32.png', //Absolute URL to icon image
                'fontawesome' => 'x-twitter',
                'link_format' => 'https://twitter.com/intent/tweet?url=%1$s&text=%2$s&via=%3$s&related=%4$s&original_referer=%5$s', //Platform's sharing link format
                'width' => 600, //Pop-up window's width
                'height' => 270, //Pop-up window's height
                'prefix_title' => true //Adds "Share on [title] for screenreaders
            ),
            'linkedin' => array(
                'title' => __('LinkedIn', 'socialized'), //Platform's Title
                'suffix' => '-l', //Suffix for identifying platform's vanity URL
                'icon' => $url . 'assets/images/icon_linkedin_32.png', //Absolute URL to icon image
                'fontawesome' => 'linkedin-in', //FontAwesome class
                'link_format' => 'https://www.linkedin.com/sharing/share-offsite/?url=%1$s', //Platform's sharing link format
                'width' => 600, //Pop-up window's width
                'height' => 530, //Pop-up window's height
                'prefix_title' => true //Adds "Share on [title] for screenreaders
            ),
            'pinterest' => array(
                'title' => __('Pinterest', 'socialized'), //Platform's Title
                'suffix' => '-p', //Suffix for identifying platform's vanity URL
                'icon' => $url . 'assets/images/icon_pinterest_32.png', //Absolute URL to icon image
                'fontawesome' => 'pinterest-p', //FontAwesome class
                'link_format' => 'https://pinterest.com/pin/create/button/?url=%1$s&media=%2$s&description=%3$s', //Platform's sharing link format
                'width' => 800, //Pop-up window's width
                'height' => 680, //Pop-up window's height
                'prefix_title' => true //Adds "Share on [title] for screenreaders
            ),
            'email' => array(
                'title' => __('Email', 'socialized'), //Platform's Title
                'suffix' => '-e', //Suffix for identifying platform's vanity URL
                'icon' => $url . 'assets/images/icon_email_32.png', //Absolute URL to icon image
                'fontawesome' => 'envelope', //FontAwesome class
                'link_format' => 'mailto:?subject=%1$s&body=%2$s', //Platform's sharing link format
                'width' => 800, //Pop-up window's width
                'height' => 680, //Pop-up window's height
                'prefix_title' => __('Forward via email', 'socialized') //replaces the title for screenreaders
            ),
            'vanity-url' => array(
                'title' => __('Copy URL', 'socialized'), //Platform's Title
                'suffix' => '-c', //Suffix for identifying platform's vanity URL
                'icon' => $url . 'assets/images/icon_link_32.png', //Absolute URL to icon image
                'fontawesome' => 'link', // FontAwesome class
                'visible' => is_ssl() || (defined('WP_DEVELOPMENT_MODE') && in_array(constant('WP_DEVELOPMENT_MODE'), array('plugin', 'all'))), // This button only works on secure websites
                'popup' => sprintf(
                    '<span id="%s" class="copied-popup hidden" role="tooltip" aria-hidden="true"><span>%s</span></span>',
                    esc_attr(sanitize_key($slug . '-copied-popup')),
                    esc_html__('Copied!', 'socialized')
                ),
                'prefix_title' => __('Copy to clipboard', 'socialized') // uses the title
            )
        );

        self::$vars = array(

            // Plugin Info
            'name' => __('Socialized', 'socialized'),
            'version' => SOCIALIZED_VERSION,
            'capability_post' => 'edit_post', // Capability for editing posts
            'capability_settings' => 'manage_options', // Capability for editing plugin options

            // Paths and URLs
            'file' => SOCIALIZED_FILE,
            'basename' => $basename, // E.g.: "plugin-folder/file.php"
            'path' => plugin_dir_path(SOCIALIZED_FILE), // E.g.: "/path/to/wp-content/plugins/plugin-folder/"
            'url' => $url, // E.g.: "https://domain.com/wp-content/plugins/plugin-folder/"
            'admin_url' => admin_url(sprintf('tools.php?page=%s', $slug)), // URL under "Tools" e.g.: "https://domain.com/wp-admin/tools.php?page=plugin-folder"
            'slug' => $slug, // E.g.: "plugin-folder"
            'prefix' => $slug_underscore . '_', // E.g.: "plugin_folder_"
            'hook' => $slug_underscore, // E.g.: "plugin_folder"

            // Plugin Options
            'options' => array(
                // Frontend Display
                'display' => array(
                    'title' => __('Display', 'socialized'),
                    'options' => array(
                        'icon_type' => array(
                            'label' => __('Icons', 'socialized'),
                            'description' =>  __('Choose a button style for displaying the social media sharing links.', 'socialized'),
                            'note' => sprintf(__("Use the shortcode %s to display these buttons within the content. If placed, it will override the automatic placement so they're only displayed once.", 'socialized'), '<code>[socialized]</code>'),
                            'default' => 'png',
                            'global_override' => true,
                            'atts' => array(
                                'type' => 'select',
                                'class' => 'controller' // I controll another input
                            ),
                            'options' => array(
                                'png' => __('Image Icons', 'socialized'),
                                'fontawesome' => __('FontAwesome Icons', 'socialized'),
                                'text' => __('Text Links', 'socialized')
                            )
                        ),
                        'exclude_fontawesome' => array(
                            'label' => __('FontAwesome', 'socialized'),
                            'description' =>  __('Toggle this off if your theme or another plugin already implements FontAwesome version 6.4.2 or later.', 'socialized'),
                            'default' => '1',
                            'global_override' => true,
                            'atts' => array(
                                'type' => 'switch',
                                'data' => array(
                                    'controller' => $slug_underscore . '_icon_type', // controlled by this field
                                    'values' => 'fontawesome' // show when this value is selected
                                )
                            ),
                            'reverse' => true, // Toggled ON means included, toggled OFF means excluded
                            'no' => __('Excluded', 'socialized'),
                            'yes' => __('Included', 'socialized'),

                        ),
                        'buttons_location' => array(
                            'label' => __('Location', 'socialized'),
                            'description' =>  __('Choose where to display the sharing buttons', 'socialized'),
                            'default' => 'top',
                            'global_override' => true,
                            'atts' => array(
                                'type' => 'select'
                            ),
                            'options' => array(
                                'top' => __('Before content (displays horizontally)', 'socialized'),
                                'end' => __('After content (displays horizontally)', 'socialized'),
                                'stick-left' => __('Left of content (displays vertically)', 'socialized'),
                                'stick-right' => __('Right of content (displays vertically)', 'socialized'),
                                'hide' => __('I will use shortcodes only', 'socialized')
                            )
                        )
                    )
                ),
                'platforms' => array(
                    'title' => __('Sharing Platforms', 'socialzied'),
                    'options' => array()
                ),
                'link_tracking' => array(
                    'title' => __('Link Tracking', 'socialized'),
                    'options' => array(
                        'utm_campaign' => array(
                            'label' => __('Campaign', 'socialized'),
                            'description' =>  __('The value of the "utm_campaign" parameter for all links shared with this plugin.', 'socialized'),
                            'default' => 'socialized',
                            'global_override' => true,
                            'atts' => array('type' => 'text', 'placeholder' => 'socialized', 'required' => 'required')
                        ),
                        'twitter' => array(
                            'label' => __('X (formerly Twitter) Handle', 'socialized'),
                            'description' =>  __('Your handle on X, without the @ symbol', 'socialized'),
                            'default' => '',
                            'global_override' => true,
                            'atts' => array('type' => 'text', 'placeholder' => 'TessaTechArtist')
                        ),
                        'twitter_related' => array(
                            'label' => __('Related Handles', 'socialized'),
                            'description' =>  __('Comma separated list of up to three (3) related handles to recommend to the user after they tweet your content.', 'socialized'),
                            'default' => '',
                            'global_override' => true,
                            'atts' => array('type' => 'text', 'placeholder' => 'TessaTechArtist,SirPatStew,GeorgeTakei')
                        )
                    )
                ),
                // Advanced Settings
                'advanced' => array(
                    'title' => __('Advanced Settings', 'socialized'),
                    'options' => array(
                        'autofix_sticky' => array(
                            'label' => __('Autofix Sticky', 'socialized'),
                            'description' =>  __('When sticking the buttons to the left or right, certain CSS attributes need to be set for it to work. Enabling this will automatically fix those issues.', 'socialized'),
                            'default' => '',
                            'global_override' => true,
                            'atts' => array('type' => 'switch')
                        ),
                        'redirecting' => array(
                            'label' => __('Redirects Enabled', 'socialized'),
                            'description' =>  __('If disabled, the social media sharing links will still appear on the posts/pages, but will share your permalink with the UTM parameters added to it instead. Any vanity URLs created by this plugin that were shared on social media or other mediums will also stop redirecting, resulting in a 404 page to be displayed', 'socialized'),
                            'default' => '1',
                            'global_override' => true,
                            'atts' => array('type' => 'switch')
                        ),
                        'post_types' => array(
                            'label' => __('Post Types', 'socialized'),
                            'description' =>  __('Comma separated list of the post types to generate URLs for', 'socialized'),
                            'default' => 'post',
                            'global_override' => true,
                            'atts' => array('type' => 'text', 'placeholder' => 'post,page,product')
                        )
                    )
                ),
                // Advanced Settings
                'hidden' => array(
                    'title' => __('Internal Plugin Options', 'socialized'),
                    'options' => array(
                        'version' => array(
                            'default' => '',
                            'global_override' => false,
                            'atts' => array('type' => 'hidden')
                        ),
                        'all_slugs' => array(
                            // Deprecated?
                            //'label' => __('Generated Slugs', 'socialized'),
                            //'description' =>  __('An array of all the slugs generated', 'socialized'),
                            'default' => '',
                            'global_override' => false,
                            'atts' => array('type' => 'hidden')
                        ),
                        'taxonomies' => array(
                            //'label' => __('Taxonomies', 'socialized'),
                            //'description' =>  __('Comma separated list of the taxonomies to generate URLs buttons for', 'socialized'),
                            'default' => 'category,post_tag',
                            'global_override' => false,
                            'atts' => array('type' => 'hidden')
                        )
                        // 'redirecting' => array(
                        //     'label' => __('Redirects Enabled', 'socialized'),
                        //     'description' =>  __('If disabled, the social media sharing links will still appear on the posts/pages, but will share your permalink with the UTM parameters added to it instead. Any vanity URLs created by this plugin that were shared on social media or other mediums will also stop redirecting, resulting in a 404 page to be displayed', 'socialized'),
                        //     'default' => '1',
                        //     'global_override' => true,
                        //     'atts' => array('type' => 'switch')
                        // )
                    )
                )
            ),

            // Post Options
            'post_options' => array(
                'term' => array(
                    'label' => __('Campaign Term', 'socialized'),
                    'description' => __("If left blank, this will use Yoast SEO's &ldquo;Focus keyphrase&rdquo;", 'socialized'),
                    'default' => '',
                    'atts' => array('type' => 'text')
                ),
                'slug' => array(
                    'label' => __('Vanity Slug', 'socialized'),
                    'description' => '',
                    'default' => '',
                    'atts' => array(
                        'type' => 'text',
                        'minlength' => 1,
                        'maxlength' => 20,
                        'pattern' => '[0-9a-zA-Z\-_$.*()]*', // Matches random_str() in Utilities
                        'onkeyup' => "this.value=this.value.replace(' ','-')" // Replace spaces with dashes
                    )
                )
            )
        );
        // Configure sharing platform options
        foreach (self::$platforms as $platform => $p) {
            self::$vars['options']['platforms']['options']['display_' . $platform] = array(
                'label' => sprintf(__('Enable share button for %s', 'socialized'), $p['title']),
                'description' => sprintf(__('Hide or display the share button for %s', 'socialized'), $p['title']),
                'default' => '1',
                'global_override' => true,
                'atts' => array('type' => 'switch'),
                'no' => __('Hidden', 'socialized'),
                'yes' => __('Visible', 'socialized')
            );
        }

        //Plugin Setup
        add_action('plugins_loaded', array($this, 'update_plugin_db')); // Maybe update the plugin db options
        add_action('admin_init', array($this, 'register_settings')); // Register plugin settings
        add_action('admin_menu', array($this, 'admin_menu')); // Add admin page link in WordPress dashboard
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets_admin')); // Enqueue assets for admin page(s)
        add_filter('plugin_action_links_' . $basename, array($this, 'plugin_links')); // Customize links on listing on plugins page

        // Cleanup on Activation/Deactivation Hooks
        register_activation_hook(SOCIALIZED_FILE, array($this, 'activate')); //Add activation hook
        register_deactivation_hook(SOCIALIZED_FILE, array($this, 'deactivate')); //Add deactivation hook
    }

    //**** Activation / Deactivation / Updates ****/

    /**
     * Maybe Update Database
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function update_plugin_db()
    {
        if (Utilities::get_version(self::get('version', true, 'hidden')) < ($current_version = Utilities::get_version(SOCIALIZED_VERSION))) {
            if ($current_version < 400) {
                // Do upgrade on settings
                foreach (self::$vars['options'] as $option_group_name => $group) {
                    //$old_group = self::$vars['hook'];
                    //$new_group = self::$vars['prefix'] . $option_group_name;
                    //$option_group = self::$vars['prefix'] . $option_group_name;
                    $option_group = self::$vars['hook'];
                    foreach ($group['options'] as $setting_name => $setting_data) {
                        $option_name = self::$vars['prefix'] . $setting_name;
                        $input_type = $setting_data['atts']['type'];
                        switch ($input_type) {
                            case 'switch':
                            case 'checkbox':
                                // Previously stored as "true" and "false", these need to be updated to use "1" and "0"
                                $prev_value = self::get($setting_name, false, $option_group_name);
				if(!$prev_value['constant']) {
                                    if ($prev_value['value'] === 'true') {
                                        self::set($setting_name, '1');
                                    } elseif ($prev_value['value'] === 'false') {
                                        self::set($setting_name, '0');
                                    }
				}
                                break;
                            default:
                                break;
                        }
                    }
                }
            }
            self::set('version', SOCIALIZED_VERSION);
        }
    }

    /**
     * Activate Plugin
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function activate()
    {
        self::cleanup_crons(); // Clear out duplicates, just in case

        global $socialized; // Access main class
        $socialized->generate_urls();
    }

    /**
     * Deactivate Plugin
     *
     * Clean up after self by removing CRON events
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function deactivate()
    {
        self::cleanup_crons(); // Remove all CRON events created by this plugin
    }

    /**
     * Plugin Cleanup
     *
     * Unschedules next CRON events and clears all events created by this plugin.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function cleanup_crons()
    {
        // Unschedule the next event
        $timestamp = wp_next_scheduled(self::$vars['hook']);
        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, self::$vars['hook']);
        }
        wp_clear_scheduled_hook(self::$vars['hook']); // Clear all events on this hook
    }

    //**** Plugin Settings ****//

    /**
     * Register Plugin Settings
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_settings()
    {
        foreach (self::$vars['options'] as $option_group_name => $group) {
            //$option_group = self::$vars['prefix'] . $option_group_name;
            $option_group = self::$vars['hook'];
            //Register the section
            add_settings_section(
                $option_group, //Slug-name to identify the section. Used in the `id` attribute of tags.
                $group['title'], //Formatted title of the section. Shown as the heading for the section.
                array($this, 'display_plugin_setting_section'), //Function that echos out any content at the top of the section (between heading and fields).
                self::$vars['slug'] //The slug-name of the settings page on which to show the section.
            );

            //Register the individual settings in the section
            foreach ($group['options'] as $setting_name => $setting_data) {
                $option_name = self::$vars['prefix'] . $setting_name;
                $input_type = $setting_data['atts']['type'];
                $registration_args = array();
                switch ($input_type) {
                    case 'switch':
                    case 'checkbox':
                        $type = 'integer';
                        $registration_args['sanitize_callback'] = array($this, 'sanitize_setting_bool');
                        break;
                    case 'number':
                        $type = 'number';
                        $registration_args['sanitize_callback'] = array($this, 'sanitize_setting_number');
                        break;
                    case 'text':
                        $type = 'string';
                        if (strpos(Utilities::array_has_key('class', $setting_data['atts']), 'au-color-picker') !== false) {
                            $registration_args['sanitize_callback'] = array($this, 'sanitize_setting_color');
                        } else {
                            $registration_args['sanitize_callback'] = 'sanitize_text_field';
                        }
                        break;
                    default:
                        $type = 'string';
                        $registration_args['sanitize_callback'] = 'sanitize_text_field';
                        break;
                }
                $registration_args['type'] = $type; //Valid values are string, boolean, integer, number, array, and object
                $registration_args['description'] = $setting_name;
                $registration_args['default'] = Utilities::array_has_key('default', $setting_data);

                //Register the setting
                register_setting($option_group, $option_name, $registration_args);

                //Add the field to the admin settings page (excluding the hidden ones)
                if ($input_type != 'hidden') {
                    $input_args = array(
                        'group' => $option_group_name,
                        'type' => $input_type,
                        'type_option' => 'string', //Option type
                        'class' => 'au-plugin-option ' . sanitize_html_class(Settings::$vars['slug'] . '-option-' . $option_group_name),
                        'default' => $registration_args['default'],
                        'label' => Utilities::array_has_key('label', $setting_data),
                        'description' => Utilities::array_has_key('description', $setting_data),
                        'global' => Utilities::array_has_key('global_override', $setting_data) ? Utilities::get_constant_name($option_name) : '', //Name of constant variable should it exist
                        'private' => Utilities::array_has_key('private', $setting_data),
                        'label_for' => $option_name,
                        //Attributes for the input field
                        'atts' => array(
                            'type' => $input_type,
                            'name' => $option_name,
                            'id' => $option_name,
                            'value' => get_option($option_name, $registration_args['default']), //The currently selected value (or default if not selected)
                            'class' => Utilities::array_has_key('class', $setting_data['atts']),
                            'data-default' => $registration_args['default']
                        )
                    );
                    if (Utilities::array_has_key('required', $setting_data['atts'])) {
                        $input_args['atts']['required'] = 'required';
                    }
                    //Add data attributes
                    $data_atts = Utilities::array_has_key('data', $setting_data['atts'], array());
                    if (count($data_atts)) {
                        foreach ($data_atts as $data_key => $data_value) {
                            $input_args['atts']['data-' . $data_key] = $data_value;
                        }
                    }
                    switch ($input_type) {
                        case 'select':
                            $input_args['options'] = Utilities::array_has_key('options', $setting_data, array());
                            break;
                        case 'checkbox':
                        case 'switch':
                            $input_args['label_for'] .= '_check';
                            $input_args['reverse'] = Utilities::array_has_key('reverse', $setting_data['atts']);
                            $input_args['yes'] = Utilities::array_has_key('yes', $setting_data, __('On', 'socialized'));
                            $input_args['no'] = Utilities::array_has_key('no', $setting_data, __('Off', 'socialized'));
                            //Purposely not breaking here
                        case 'radio':
                            $input_args['checked'] = checked(1, $input_args['atts']['value'], false);
                            break;
                        case 'number':
                        case 'time':
                            $input_args['atts']['min'] = Utilities::array_has_key('min', $setting_data['atts']);
                            $input_args['atts']['max'] = Utilities::array_has_key('max', $setting_data['atts']);
                            $input_args['atts']['step'] = Utilities::array_has_key('step', $setting_data['atts']);
                            //Purposely not breaking here
                        default:
                            $input_args['atts']['placeholder'] =  esc_attr(Utilities::array_has_key('placeholder', $setting_data['atts']));
                            break;
                    }
                    add_settings_field(
                        $option_name, //ID
                        esc_attr($setting_data['label']), //Title
                        array($this, 'display_plugin_setting'), //Callback (should echo its output)
                        self::$vars['slug'], //Page
                        $option_group, //Section
                        $input_args //Attributes
                    );
                }
            }
        }
    }

    /**
     * Sanitize plugin options for boolean fields
     *
     * @since 1.0.0
     *
     * @param string $value Value to sanitize.
     *
     * @return int Returns `1` if the value is truthy, `0` otherwise.
     */
    public function sanitize_setting_bool($value)
    {
        return $value ? 1 : 0;
    }

    /**
     * Sanitize plugin options for number fields
     *
     * @since 1.0.0
     *
     * @param string $value Value to sanitize.
     *
     * @return int|float|string The numeric value or an empty string.
     */
    public function sanitize_setting_number($value)
    {
        return is_numeric($value) ? $value : '';
    }

    /**
     * Sanitize plugin options for color picker fields
     *
     * @since 1.0.0
     *
     * @param string $value Value to sanitize.
     *
     * @return string Sanitized and validated HEX color. Empty string otherwise.
     */
    public function sanitize_setting_color($value)
    {
        if (is_string($value)) {
            $value = sanitize_text_field($value);
            if (!empty($value) && preg_match('/^#[a-f0-9]{6}$/i', $value)) {
                return $value;
            }
        }
        return '';
    }

    /**
     * Register Plugin Setting Section Callback
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function display_plugin_setting_section()
    {
        // Do nothing
    }

    /**
     * Display plugin setting input in admin dashboard
     *
     * Callback for `add_settings_field()`
     *
     * @since 1.0.0
     *
     * @param array $args Input arguments.
     *
     * @return void
     */
    public function display_plugin_setting($args = array())
    {
        /**
         * Variables that are already escaped:
         * type, name, id, value, label, required, global, private, checked, min, max, step, placeholder
         */
        $note = '';
        if ($args['global'] && defined($args['global'])) {
            //Display constant values set in wp-config.php
            if ($args['private'] || $args['type'] == 'password') {
                // This field is readonly, do not reveal the value
                printf(
                    '<input %s />',
                    Utilities::format_atts(array_replace($args['atts'], array(
                        'readonly' => 'readonly',
                        'disabled' => 'disabled',
                        'type' => 'password',
                        'value' => '**********'
                    )))
                );
            } else {
                // This field is readonly
                printf(
                    '<input %s />',
                    Utilities::format_atts(array_replace($args['atts'], array(
                        'readonly' => 'readonly',
                        'disabled' => 'disabled',
                        'type' => 'text',
                        'value' => constant($args['global'])
                    )))
                );
            }
            $note .= sprintf(__('<strong>This value is being overwritten by <code>%s</code></strong>', 'socialized'), $args['global']);
        } else {
            //Render the setting
            switch ($args['type']) {
                case 'hidden':
                    //Silence is golden
                    break;
                case 'switch': //Fancy Toggle Checkbox Switch
                    $checkbox_args = array(
                        'type' => 'checkbox',
                        'id' => $args['atts']['id'] . '_check',
                        'name' => $args['atts']['name'] . '_check',
                        'class' => 'input-checkbox'
                    );
                    if ($args['reverse']) {
                        $checkbox_args['class'] .= ' reverse-checkbox'; //whether checkbox should be visibly reversed
                    }
                    printf(
                        '<span class="checkbox-switch %6$s"><input %1$s /><input %2$s %3$s /><span class="checkbox-animate"><span class="checkbox-off">%4$s</span><span class="checkbox-on">%5$s</span></span></span></label>',
                        Utilities::format_atts(array_replace($args['atts'], array('type' => 'hidden'))), // 1 - Hidden input field
                        Utilities::format_atts(array_replace($args['atts'], $checkbox_args)), // 2 - Visible checkbox field
                        checked(($args['reverse'] ? '0' : '1'), $args['atts']['value'], false), //3 - Checked attribute, if reversed, compare against the opposite value
                        esc_attr($args['no']), //4 - on value
                        esc_attr($args['yes']), //5 - off value
                        esc_attr($args['atts']['class']) //6 - additional classes to wrapper object
                    );
                    break;
                case 'select': //Simple Drop-Down Select Field
                    printf('<select %s />', Utilities::format_atts($args['atts']));
                    foreach ($args['options'] as $key => $value) {
                        $option_name = is_array($value) ? $value['label'] : $value;
                        $option_atts = array('value' => $key);
                        if ($args['atts']['value'] == $key) {
                            $option_atts['selected'] = 'selected';
                        }
                        printf(
                            '<option %s>%s</option>',
                            Utilities::format_atts($option_atts),
                            esc_html($option_name)
                        );
                    }
                    echo ('</select>');
                    break;
                case 'checkbox':
                case 'radio':
                    printf('<input %s %s />', Utilities::format_atts($args['atts']), $args['checked']);
                    break;
                case 'textarea':
                    $textarea = $args['atts'];
                    unset($textarea['type']);
                    unset($textarea['value']);
                    printf('<textarea %s>%s</textarea>', Utilities::format_atts($textarea), esc_html($args['atts']['value']));
                    break;
                default:
                    printf('<input %s />', Utilities::format_atts($args['atts']));
                    break;
            }
            if ($args['global']) {
                $note .= sprintf(__('This value can be overwritten by defining <code>%s</code> as a global variable.', 'socialized'), $args['global']);
            }
        }
        if ($note || $args['description']) {
            printf('<small class="note">%s</small>', wp_kses(
                $args['description'] . '&nbsp;' . $note,
                array(
                    'a' => array('href' => array(), 'title' => array(), 'target' => array(), 'rel' => array()),
                    'strong' => array(),
                    'em' => array(),
                    'code' => array()
                ),
                array('https')
            ));
        }
    }

    /**
     * Get Option Key for Settings
     *
     * @since 1.0.0
     *
     * @static
     *
     * @return string $id With or without a prefix, get the option name and ID
     *
     * @return array an associative array with `id` and `name` properties
     */
    private static function get_key($id)
    {
        $return = array(
            'id' => '',
            'name' => ''
        );
        if (strpos($id, self::$vars['prefix']) === 0) {
            //Prefix is included
            $return['id'] = $id; //No change, keep prefix in ID
            $return['name'] = str_replace(self::$vars['prefix'], '', $id); //Remove prefix from name
        } else {
            //Prefix is not included
            $return['name'] = $id; //No change, no prefix in name
            $return['id'] = self::$vars['prefix'] . $id; //Add prefix to ID
        }
        return $return;
    }

    /**
     * Get Plugin Setting
     *
     * This checks if a constant value was defined to override it and returns that.
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param string $id Option ID, including prefix
     * @param bool $value_only If true, returns just the value of the setting. Otherwise, it returns an associatve array. Default is true.
     *
     * @return string|array An associative array with the keys `value` and `constant` unless $value_only was true, then it returns just the value.
     */
    public static function get($id, $value_only = true, $group = '')
    {
        $return = array(
            'value' => '',
            'constant' => false,
            'status' => ''
        );
        $setting = self::get_key($id);
        $group = $group ? $group : 'settings';
        if (array_key_exists($group, self::$vars['options']) && array_key_exists($setting['name'], self::$vars['options'][$group]['options'])) {
            $const_name = Utilities::array_has_key('global_override', self::$vars['options'][$group]['options'][$setting['name']]) ? Utilities::get_constant_name($setting['id']) : '';
            if ($const_name && defined($const_name)) {
                //Display the value overriden by the constant value
                $return['value'] = constant($const_name);
                $return['constant'] = true;
            } else {
                $return['value'] = get_option($setting['id'], Utilities::array_has_key('default', self::$vars['options'][$group]['options'][$setting['name']]));
            }
        }
        //Sanitize values
        if (is_string($return['value'])) {
            $return['value'] = sanitize_text_field($return['value']);
        }
        //Return appropriate format
        if ($value_only) {
            return $return['value'];
        }
        return $return;
    }

    /**
     * Set Plugin Setting
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param string $id The ID of the plugin setting, with or without the prefix
     * @param mixed $value The value of the plugin setting
     *
     * @return bool True on success, false on failure
     */
    public static function set($id, $value)
    {
        $setting = self::get_key($id);
        return update_option($setting['id'], $value);
    }

    //**** Plugin Management Page ****//

    /**
     * Add Admin Page
     *
     * Adds the admin page to the WordPress dashboard sidebar
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function admin_menu()
    {
        add_management_page(
            self::$vars['name'] . ' ' . __('by AuRise Creative', 'socialized'), // Page Title
            self::$vars['name'], // Menu Title
            self::$vars['capability_settings'], // Capability
            self::$vars['slug'], // Menu Slug
            array(&$this, 'admin_page'), //Callback
            null // Position
        );
    }

    /**
     * Plugin Links
     *
     * Links to display on the plugins page.
     *
     * @since 1.0.0
     *
     * @param array $links
     *
     * @return array A list of links
     */
    public function plugin_links($links)
    {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            self::$vars['admin_url'],
            __('Settings', 'socialized')
        );
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Admin Scripts and Styles
     *
     * Enqueue scripts and styles to be used on the admin pages
     *
     * @since 1.0.0
     *
     * @param string $hook Hook suffix for the current admin page
     *
     * @return void
     */
    public function enqueue_assets_admin($hook)
    {
        $minified = !(defined('SCRIPT_DEBUG') && constant('SCRIPT_DEBUG'));
        $async = array(
            'in_footer' => true,
            'strategy' => 'async'
        );
        $defer = array(
            'in_footer' => true,
            'strategy' => 'defer'
        );

        // Register FontAwesome
        wp_register_script(
            self::$vars['prefix'] . '-fontawesome', // Handle
            self::$vars['url'] . 'assets/fontawesome/js/all.min.js', // Source URL
            array(), // Dependencies
            '6.4.2', // Version
            $async // Loading parameters
        );

        if ($hook == 'tools_page_' . self::$vars['slug']) {
            // Load only on our plugin page (a subpage of "Tools")

            // Plugin Stylesheets
            wp_enqueue_style(
                self::$vars['prefix'] . 'layout', // Handle
                self::$vars['url'] . 'assets/styles/pseudo-bootstrap' . ($minified ? '.min' : '') . '.css', // Source URL
                array(), // Dependencies
                $minified ? SOCIALIZED_VERSION : @filemtime(self::$vars['path'] . 'assets/styles/pseudo-bootstrap.css') // Version
            );
            wp_enqueue_style(
                self::$vars['prefix'] . 'dashboard', // Handle
                self::$vars['url'] . 'assets/styles/admin-dashboard.css', // Source URL
                array(self::$vars['prefix'] . 'layout'), // Dependencies
                $minified ? SOCIALIZED_VERSION : @filemtime(self::$vars['path'] . 'assets/styles/admin-dashboard.css') // Version
            );

            // Plugin Scripts
            wp_enqueue_script('jquery');
            wp_enqueue_script(
                self::$vars['prefix'] . 'dashboard', // Handle
                self::$vars['url'] . 'assets/scripts/admin-dashboard.js', // Source URL
                array('jquery', self::$vars['prefix'] . '-fontawesome'), // Dependencies
                $minified ? SOCIALIZED_VERSION : @filemtime(self::$vars['path'] . 'assets/scripts/admin-dashboard.js'), // Version
                $defer // Loading parameters
            );
            wp_localize_script(
                self::$vars['prefix'] . 'dashboard', // Handle
                'au_object', // Object name
                array('ajax_url' => admin_url('admin-ajax.php')) // Object data
            );
        } elseif ($hook == 'post-new.php' || $hook == 'post.php') {
            // Load only on the post edit page

            // Plugin Stylesheets
            wp_enqueue_style(
                self::$vars['prefix'] . 'metabox', // Handle
                self::$vars['url'] . 'assets/styles/admin-metabox.css', // Source URL
                array(), // Dependencies
                $minified ? SOCIALIZED_VERSION : @filemtime(self::$vars['path'] . 'assets/styles/admin-metabox.css') // Version
            );

            // Plugin Scripts
            wp_enqueue_script('jquery');
            wp_enqueue_script(
                self::$vars['prefix'] . 'metabox', // Handle
                self::$vars['url'] . 'assets/scripts/admin-metabox.js', // Source URL
                array('jquery', self::$vars['prefix'] . '-fontawesome'), // Dependencies
                $minified ? SOCIALIZED_VERSION : @filemtime(self::$vars['path'] . 'assets/scripts/admin-metabox.js'), // Version
                $defer // Loading parameters
            );
            wp_localize_script(
                self::$vars['prefix'] . 'metabox', // Handle
                'au_object', // Object name
                array('ajax_url' => admin_url('admin-ajax.php')) // Object data
            );
        }
    }

    /**
     * Display Admin Page
     *
     * HTML markup for the WordPress dashboard admin page for managing this plugin's settings.
     *
     * @since 1.0.0
     */
    public function admin_page()
    {
        //Prevent unauthorized users from viewing the page
        if (!current_user_can(self::$vars['capability_settings'])) {
            return;
        }
        $paged = isset($_GET['paged']) && is_numeric($_GET['paged']) ? $_GET['paged'] : 0;
        load_template(self::$vars['path'] . 'templates/dashboard-admin.php', true, array(
            'post_types' => self::get_post_types(),
            'plugin_settings' => self::$vars,
            'view_table' => $this->get_view_table(array('paged' => $paged)),
            'yoast-seo' => Utilities::is_plugin_active('wordpress-seo/wp-seo.php'),
            'platforms' => self::get_platforms()
        ));
    }

    /**
     * Get Allowed Post Types
     *
     * @since 3.0.0
     *
     * @return array a sequential array of post types
     */
    public static function get_post_types()
    {
        global $socialized_post_types;
        if (!isset($socialized_post_types)) {
            $types = explode(',', sanitize_text_field(strval(self::get('post_types', true, 'advanced'))));
            $socialized_post_types = array();
            if (count($types)) {
                foreach ($types as $type) {
                    $socialized_post_types[] = sanitize_title($type);
                }
            }
            $socialized_post_types = array_unique(array_filter($socialized_post_types));
        }
        return $socialized_post_types;
    }

    /**
     * Get Allowed Taxonomies
     *
     * @since 3.0.4
     *
     * @return array a sequential array of post types
     */
    public static function get_taxonomies()
    {
        global $socialized_taxonomies;
        if (!isset($socialized_taxonomies)) {
            $taxonomies = explode(',', sanitize_text_field(strval(self::get('taxonomies', true, 'advanced'))));
            $socialized_taxonomies = array();
            if (count($taxonomies)) {
                foreach ($taxonomies as $taxonomy) {
                    $socialized_taxonomies[] = sanitize_title($taxonomy);
                }
            }
            $socialized_taxonomies = array_unique(array_filter($socialized_taxonomies));
        }
        return $socialized_taxonomies;
    }

    /**
     * Get Slugs from Plugin Option
     *
     * Retrieve an associative array of key/value pairs, where the key is the slug, and the value is post data
     *
     * @since 1.4.0
     *
     * @return array Returns an associative array of arrays with the slug as the key and post data as the value, or an empty array
     */
    public static function get_slugs()
    {
        $slugs = self::get('all_slugs', true, 'hidden');
        if (!is_array($slugs)) {
            $slugs = array();
            self::set('all_slugs', $slugs);
        }
        return $slugs;
    }

    /**
     * Get Hits.
     *
     * Retrieve the total number of hits of a given post and platform.
     *
     * @since 1.0.0
     * @param integer $post_id Post ID.
     * @param string $platform Optional. Platform key identifier.
     * @return integer $hits Total number of hits for this post or platform on this post.
     */
    public static function get_hits($post_id, $platform = false)
    {
        $meta_key = 'hits';
        if ($platform) {
            $meta_key .= '_' . $platform;
        }
        $hits = Utilities::get_meta($post_id, $meta_key, 0);
        return intval($hits);
    }



    private static function is_static_homepage()
    {
        return is_front_page() && !is_home();
    }

    /**
     * Is Allowed Based on Options
     *
     * @since 3.0.4
     *
     * @param int|WP_Post $post Optional. WordPress Post object or post ID to check. Default is to check the current post.
     *
     * @return bool True if allowed, false otherwise.
     */
    public static function is_allowed($post = '')
    {
        if (is_numeric($post) && ($post = intval($post)) > 0) {
            $post = get_post($post);
        } else {
            global $post;
        }
        if (gettype($post) == 'object') {
            $allowed_post_types = self::get_post_types();
            if (is_single($post) && in_array($post->post_type, $allowed_post_types)) {
                // Any specified singular post type
                return true;
            } elseif (self::is_static_homepage() && in_array('page', $allowed_post_types)) {
                // Static homepage if pages are specified
                return true;
            } elseif (is_home() && in_array('post', $allowed_post_types)) {
                // Blog index page if posts are specified
                return true;
            } elseif (is_archive() && ($taxonomies = self::get_taxonomies())) {
                // Any specified taxonomy
                global $wp_query;
                $term = $wp_query->get_queried_object(); //WP_Term
                if (in_array($term->taxonomy, $taxonomies)) {
                    echo ('yes!');
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get Available Platforms
     *
     * @since 3.0.0
     *
     * @return array an associative array of available platforms
     */
    public static function get_platforms()
    {
        global $socialized_platforms;
        if (!isset($socialized_platforms)) {
            $socialized_platforms = array();
            foreach (self::$platforms as $platform => $p) {
                // Only get enabled platforms
                if (Utilities::array_has_key('visible', $platform, true) && Settings::get('display_' . $platform, true, 'platforms')) {
                    $socialized_platforms[$platform] = $p;
                }
            }
        }
        return $socialized_platforms;
    }

    /**
     * Get View Table
     *
     * @since 3.0.0
     * @param array $args An associative array of parameters for WP_Query()
     * @return string HTML output of the view table
     */
    private function get_view_table($args = array())
    {
        $o = '';
        $args = array_merge(array(
            'post_types' => self::get_post_types(), //Get allowed post types
            'post_status' => 'any', //retrieves any status except for `inherit`, `trash` and `auto-draft`
            'posts_per_page' => 200, //The first 200 posts
            'paged' => 0, //Display the first page
            'ignore_sticky_posts' => true, //Ignore sticky posts
            'orderby' => 'ID',
            'order' => 'DESC', //Newest content first
            'meta_query' => array(array(
                'key' => self::$vars['prefix'] . 'slug',
                'value' => array(''),
                'compare' => 'NOT IN'
            ))
        ), $args);
        $platforms = self::get_platforms();
        $has_platforms = count($platforms);
        $wpq = new \WP_Query($args);
        if ($wpq->have_posts()) {
            $nav = '';
            if ($wpq->max_num_pages > 1) {
                $nav .= '<nav class="pagination">';
                $nav .= paginate_links(array(
                    'base' => self::$vars['admin_url'] . '%_%#view',
                    'format' => '&paged=%#%',
                    'current' => max(1, $args['paged']),
                    'total' => $wpq->max_num_pages,
                    'prev_text' => '&#8249;',
                    'next_text' => '&#8250;',
                    'type' => 'list',
                    'end_size' => 0,
                    'mid_size' => 3
                ));
                $nav .= '</nav>';
            }
            $o .= $nav;
            $o .= sprintf(
                '<table class="au-table">
                    <thead><tr>
                    <th>%s</th>
                    <th>%s</th>
                    <th>%s</th>
                    <th>%s</th>
                    </tr></thead><tbody>',
                __('ID', 'socialized'),
                __('Post Type', 'socialized'),
                __('Title', 'socialized'),
                __('Social Sharing Info')
            );
            while ($wpq->have_posts()) {
                $wpq->the_post();
                $post_id = get_the_ID();
                $post_type = get_post_type($post_id);
                $slug = get_post_meta($post_id, self::$vars['prefix'] . 'slug', true);
                $sharing_info = '';

                if ($has_platforms) {
                    $sharing_info .= '<ul>';
                    global $socialized; // Access main class
                    foreach ($platforms as $key => $platform) {
                        $url_params = $socialized->get_permalink_with_query($key, $post_id, true);
                        $hits = get_post_meta($post_id, self::$vars['prefix'] . 'hits_' . $url_params['utm_source'], true);
                        $platform_slug = $slug . $platform['suffix'];
                        $sharing_info .= sprintf(
                            '<li><strong>%s</strong>, %s %s: <a href="%s" target="_blank" rel="noopener noreferrer">%s</a></li>',
                            esc_html($platform['title']),
                            $hits ? $hits : 0,
                            __('hit(s)', 'socialized'),
                            esc_url(home_url($platform_slug)),
                            esc_html($platform_slug)
                        );
                    }
                    $sharing_info .= '</ul>';
                }
                $o .= sprintf(
                    '<tr>
                            <td class="post-id"><code>%1$s</code></td>
                            <td class="post-type"><code>%2$s</code></td>
                            <td class="post-title">%5$s<br><a href="%3$s" target="_blank" rel="noopener noreferrer">%6$s</a> <a href="%4$s" target="_blank" rel="noopener noreferrer">%7$s</a></td>
                            <td class="slug">%8$s</td>
                        </tr>',
                    $post_id, // 1
                    $post_type, //2
                    get_edit_post_link($post_id), //3
                    get_the_permalink($post_id), //4
                    get_the_title($post_id), //5
                    __('Edit', 'socialized'), //6
                    __('View', 'socialized'), //7
                    $sharing_info //8
                );
            }
            $o .= '</tbody></table>';
            $o .= $nav;
        }
        wp_reset_postdata();
        return $o;
    }
}
