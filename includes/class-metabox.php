<?php

namespace AuRise\Plugin\Socialized;

defined('ABSPATH') || exit; // Exit if accessed directly

use AuRise\Plugin\Socialized\Utilities;
use AuRise\Plugin\Socialized\Settings;
use AuRise\Plugin\Socialized\Main;

/**
 * Metabox Class
 *
 * Utility functions used by this plugin.
 *
 * @package AuRise\Plugin\Socialized
 */
class Metabox
{
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_metabox')); //Add metabox to posts dashboard pages
        add_action('save_post', array($this, 'save_post')); //Save metabox settings on post save
        add_action('wp_ajax_socialized_regenerate_urls', array($this, 'regenerate_urls')); //Add AJAX call for logged in users to regenerate missing vanity URLs from admin page
        add_action('wp_ajax_socialized_update_url', array($this, 'update_url')); //Add AJAX call for logged in users to update vanity URL from post edit page
        add_action('wp_ajax_socialized_view', array($this, 'get_backend_view')); //Add AJAX call for logged in users to get a paginated list of posts with links
    }

    /**
     * Add Metabox
     *
     * Adds this plugin's metabox to the edit pages of the posts of the appropriate post type.
     *
     * @since 1.0.0
     */
    public function add_metabox()
    {
        $post_types = Settings::get_post_types();
        foreach ($post_types as $post_type) {
            add_meta_box(
                Settings::$vars['slug'], // Unique ID
                Settings::$vars['name'], // Box title
                array($this, 'display_metabox'), // Content callback, must be of type callable
                $post_type // Post type
            );
        }
    }

    /**
     * Display Post Metabox
     *
     * HTML markup for the metabox used to manage this plugin at the post level
     *
     * @since 1.0.0
     *
     * @param WP_Post $post Post Object.
     *
     * @return void
     */
    public function display_metabox($post)
    {
        //Prevent unauthorized users from viewing the page
        if (!current_user_can(Settings::$vars['capability_post'], $post->ID)) {
            return;
        }

        global $socialized; // Access main class
        $args = array(
            'post' => array(
                'ID' => $post->ID, '
                type' => $post->post_type,
                'url' => get_the_permalink($post->ID)
            ),
            'plugin_settings' => Settings::$vars,
            'post_options' => array(),
            'slug' => '',
            'term' => '',
            'default_term' => Main::get_primary_keyword($post->ID),
            'platforms' => array(),
            'yoast-seo' => Utilities::is_plugin_active('wordpress-seo/wp-seo.php')
        );
        foreach (Settings::$vars['post_options'] as $key => $field) {
            $args[$key] = sanitize_text_field(Utilities::get_meta($post->ID, $key, $field['default']));
            $description = sanitize_text_field(Utilities::array_has_key('description', $field));
            $args['post_options'][$key] = array(
                'id' => $key,
                'label' => $field['label'],
                'atts' => array_merge($field['atts'], array(
                    'id' => Settings::$vars['prefix'] . $key,
                    'name' => Settings::$vars['prefix'] . $key,
                    'value' => $args[$key]
                )),
                'note' => $description ? sprintf('<span class="note">%s</span>', $description) : ''
            );
            switch ($key) {
                case 'term':
                    // Set default term
                    $args['post_options'][$key]['atts']['placeholder'] = $args['default_term'];
                    break;
                default:
                    // Set placeholder value to be that of the current value if it's empty
                    if (!Utilities::array_has_key('placeholder', $args['post_options'][$key]['atts'])) {
                        $args['post_options'][$key]['atts']['placeholder'] = $args['post_options'][$key]['atts']['value'];
                    }
                    break;
            }
        }
        // Get platform data
        if (!empty($args['slug'])) {
            foreach (Settings::get_platforms() as $platform => $p) {
                $query = $socialized->get_permalink_with_query($platform, $post->ID, true);
                $args['platforms'][$platform] = array(
                    'slug' => $args['slug'] . $p['suffix'],
                    'title' => $p['title'],
                    'query' => $query,
                    'query_str' => http_build_query($query, '', '&', PHP_QUERY_RFC3986),
                    'hits' => Utilities::get_meta($post->ID, 'hits_' . $query['utm_source'])
                );
            }
        }

        //Load the template file
        load_template(Settings::$vars['path'] . 'templates/edit-metabox.php', true, $args);
    }

    /**
     * Save Post
     *
     * When saving a new post, save the custom slug too.
     *
     * @since 1.0.0
     *
     * @param integer $post_id Post ID
     *
     * @return void
     */
    public function save_post($post_id)
    {
        // Update all post options
        foreach (Settings::$vars['post_options'] as $key => $field) {
            Utilities::set_meta(
                $post_id,
                $key,
                sanitize_text_field(strval(Utilities::array_has_key(Settings::$vars['prefix'] . $key, $_POST)))
            );
        }

        // Maybe generate slug for just this post if it doesn't already exist
        global $socialized; // Access main class
        $socialized->generate_slug($post_id, Utilities::get_meta($post_id, 'slug'));
    }

    /**
     * Regenerate URLs via AJAX.
     *
     * Called from assets/scripts/admin-dashboard.js via AJAX, it runs the generate_urls() function on button click in the admin page.
     *
     * @since 1.0.0
     *
     * @return array Custom return object for debugging.
     */
    public function regenerate_urls()
    {
        $return = array(
            'success' => 0,
            'error' => 0,
            'messages' => array(),
            'output' => null
        );
        $return['messages'][] = 'Regenerating URLs via AJAX call';
        global $socialized; // Access main class
        $return = $socialized->generate_urls($return);
        if ($return['success'] && !$return['error']) {
            $return['output'] = 'Generation completed successfully.';
        } else {
            $return['output'] = 'Errors occurred. Please check the browser\'s console log for details.';
        }
        wp_die(json_encode($return));
    }

    /**
     * AJAX: Update Vanity URL for Single Post.
     *
     * Called from assets/scripts/admin-metabox.js via AJAX, it runs the generate_urls() function on button click in the admin page.
     *
     * @since 1.0.0
     *
     * @return array Custom return object for debugging.
     */
    public function update_url()
    {
        $return = array(
            'success' => 0,
            'error' => 0,
            'messages' => array(),
            'output' => null,
            'fields' => json_decode(str_replace('%27', "'", urldecode($_POST['fields']))),
            'links' => array()
        );
        $return['fields'] = is_null($return['fields']) ? null : (array) $return['fields'];
        $return['messages'][] = 'Updating a single URL via AJAX call';
        if (is_null($return['fields'])) {
            $return['error']++;
            $return['messages'][] = 'Fields post data is null!!!';
        } elseif ($return['fields']['post_id']) {
            $return['success']++;
            global $socialized; // Access main class
            //Validate that the slug doesn't already exist
            $page = $socialized->get_page_by_slug($return['fields']['slug']);
            if ($page !== false && $page['id'] != $return['fields']['post_id']) {
                $return['error']++;
                $return['output'] = 'This vanity slug is already used by &ldquo;' . get_the_title($page['id']) . '&rdquo;. Please try a different one.';
            } elseif ($page === false || $page['id'] == $return['fields']['post_id']) {
                //This is good to update
                $return['success']++;
                $value_before = get_post_meta($return['fields']['post_id'], Settings::$vars['prefix'] . 'slug', true);
                $return['messages'][] = 'No existing posts with this slug was found. Updating ' . $value_before . ' to ' . $return['fields']['slug'] . '...';
                $value_after = $socialized->add_slug($return['fields']['slug'], $return['fields']['post_id'], array(), $value_before);
                if ($value_after) {
                    $return['success']++;
                    $return['messages'][] = 'Post ID ' . $return['fields']['post_id'] . ' was updated with slug ' . $return['fields']['slug'];
                    $return['output'] = 'Success!';
                    foreach (Settings::get_platforms() as $key => $platform) {
                        $p_slug = $return['fields']['slug'] . $platform['suffix'];
                        $query_data = $socialized->get_permalink_with_query($key, $return['fields']['post_id'], true);
                        $return['links'][$key] = array(
                            'vanity_url_link' => home_url($p_slug),
                            'vanity_url_label' => $p_slug,
                            'campaign_term' => $query_data['utm_term']
                        );
                    }
                } else {
                    $return['error']++;
                    $return['messages'][] = 'Failed to update post ID ' . $return['fields']['post_id'] . ' with slug ' . $return['fields']['slug'];
                    $return['output'] = 'Errors occurred. Please check the browser\'s console log for details.';
                }
            }
        }
        $return['all_slugs'] = get_option(Settings::$vars['prefix'] . 'all_slugs', array());
        wp_die(json_encode($return, JSON_PRETTY_PRINT));
    }
}
