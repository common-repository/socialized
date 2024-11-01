<?php

namespace AuRise\Plugin\Socialized;

defined('ABSPATH') || exit; // Exit if accessed directly

use AuRise\Plugin\Socialized\Utilities;
use AuRise\Plugin\Socialized\Settings;

/**
 * Class Main
 *
 * The main features unique to this plugin.
 *
 * @package AuRise\Plugin\Socialized
 */
class Main
{
    /**
     * The single instance of the class
     *
     * @var Main
     *
     * @since 1.0.0
     */
    protected static $_instance = null;

    public $frontend = null;
    public $metabox = null;

    /**
     * Main Instance
     *
     * Ensures only one instance of is loaded or can be loaded.
     *
     * @since 1.0.0
     *
     * @static
     *
     * @return Main instance.
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
        Settings::instance(); // Initialize settings
        $this->metabox = new Metabox(); // Initialize metabox code
        $this->frontend = new Frontend(); //Initialise frontend code
    }

    /**
     * Generate URLs.
     *
     * Queries the posts, looking for those that should have a slug but doesn't and then looping through them, creating slugs for posts that need them.
     *
     * @since 1.0.0
     * @param array $return Optional. Custom return object for debugging.
     * @return array Custom return object for debugging.
     */
    public function generate_urls($return = array('success' => 0, 'error' => 0, 'messages' => array()))
    {
        $return['messages'][] = 'Creating query...';

        $all_slugs = $this->get_all_slugs();
        $return['messages'][] = 'All slugs (' . count($all_slugs) . ') at the beginning';
        $return['messages'][] = $all_slugs;

        // Generate for all missing posts
        $post_types = Settings::get_post_types();
        if (count($post_types)) {
            $return['messages'][] = 'Getting posts of type: ' . implode(', ', $post_types);
            $posts_args = array(
                //'fields' => 'ids', // Returns an array of IDs
                //'posts_per_page' => -1, // Return all posts
                'post_type' => $post_types, // Retrieve only specified post types
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        // If the slug field doesn't exist
                        'key' => Settings::$vars['prefix'] . 'slug',
                        'value' => 'bug #23268', // Included for compatibility for WordPress version below 3.9
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        // If the slug field is empty
                        'key' => Settings::$vars['prefix'] . 'slug',
                        'value' => array(''),
                        'compare' => 'IN'
                    )
                )
            );
            $return['messages'][] = $posts_args;

            //Query posts of the specified post type that doesn't have a slug created
            //$posts_query = get_posts($posts_args);
            $posts_query = Utilities::get_posts($posts_args, 'missing-posts');
            $count = count($posts_query);
            $return['messages'][] = 'Results: ' . $count;
            if ($count) {
                $return['messages'][] = 'Begin looping through posts...';
                //Loop through all the posts and generate URLs for each one
                foreach ($posts_query as $post_id) {
                    $return = $this->generate_slug($post_id, '', $return, $all_slugs);
                }
                $return['messages'][] = 'Posts loop completed.';
            }
        }

        // Generate for all taxonomies
        $taxonomies = Settings::get_taxonomies();
        if (is_array($taxonomies) && count($taxonomies)) {
            $return['messages'][] = 'Getting terms of taxonomies: ' . implode(', ', $taxonomies);
            $terms_args = array(
                'number' => -1,
                'taxonomy' => $taxonomies,
                'hide_empty' => false, // Get terms with nothing assigned to them
                'meta_query' => array(
                    'relation' => 'OR',
                    // if the slug field doesn't exist
                    array(
                        'key' => Settings::$vars['prefix'] . 'slug',
                        'value' => 'bug #23268', //Included for compatibility for WordPress version below 3.9
                        'compare' => 'NOT EXISTS'
                    ),
                    // if the slug field is empty
                    array(
                        'key' => Settings::$vars['prefix'] . 'slug',
                        'value' => array(''),
                        'compare' => 'IN'
                    )
                )
            );
            $return['messages'][] = $terms_args;
            $terms_query = get_terms($terms_args);
            $count = count($terms_query);
            $return['messages'][] = 'Results: ' . $count;
            if ($count) {
                $return['messages'][] = 'Begin looping through terms...';
                //Loop through all the posts and generate URLs for each one
                foreach ($terms_query as $term) {
                    $return = $this->generate_slug($term->term_id, '', $return, $all_slugs, 'term', $term->taxonomy);
                }
                $return['messages'][] = 'Terms loop completed.';
            }
        }

        // Maybe blog index page
        if (in_array('post', $post_types)) {
            // Tessa To-DO
        }

        $return['messages'][] = 'All slugs (' . count($all_slugs) . ') at the end';
        $return['messages'][] = $all_slugs;

        $return['success']++;
        return $return;
    }

    /**
     * Generate Slug
     *
     * Create the slug used for a single object, term, or user and save it.
     *
     * @since 1.0.0
     *
     * @param integer $post_id Object ID.
     * @param string $slug Optional. Custom slug. Generates randomly if left blank.
     * @param array $return Optional. Custom return object for debugging.
     * @param string $type Optional. The object type. Options are `post`, `term`, and `user`. Default is `post`.
     *
     * @return array Custom return object for debugging.
     */
    public function generate_slug($id, $slug = '', $return = array('success' => 0, 'error' => 0, 'messages' => array()), $all_slugs = array(), $type = 'post', $taxonomy = '')
    {
        $return['messages'][] = 'Generating slug for ' . $type . ' ID ' . $id . ' - "' . $slug . '"';
        $continue = true;
        if (!count($all_slugs)) {
            $all_slugs = $this->get_all_slugs(); //Get option of all slugs, should be an array of arrays
        }
        // If the slug is empty, generate a new one
        if (empty($slug)) {
            $return['messages'][] = 'Provided slug was empty. Generate slug for ' . $id;
            $attempts = 0;
            $max_attempts = 100;
            for ($i = 0; $i < 1; $i++) {
                $slug = Utilities::random_str(); //Get a random string using default values
                $attempts++;
                if ($attempts > $max_attempts) {
                    //Don't create an infinite loop, just give up if reached max attempts
                    $i += 2;
                    $return['error']++;
                    $return['messages'][] = 'MAXIMUM ATTEMPTS REACHED for ' . $type . ' ID ' . $id;
                    $continue = false;
                } elseif ($all_slugs && gettype($all_slugs) == 'array' && count($all_slugs) && array_key_exists($slug, $all_slugs)) {
                    //check if exists, if it does, decrement $i to redo the slug
                    $i--;
                }
            }
            $return['messages'][] = $attempts . ' attempts were made for ' . $type . ' ID ' . $id;
        }
        // Continue if the slug was generated or provided
        if ($continue && !empty($slug)) {
            $updated = $this->add_slug($slug, $id, $all_slugs, '', $type, $taxonomy);
            if ($updated) {
                $return['success']++;
                $return['messages'][] = ucfirst($type) . ' ID ' . $id . ' was updated with slug ' . $slug;
            } else {
                $return['error']++;
                $return['messages'][] = ucfirst($type) . ' ID ' . $id . ' failed to update with slug ' . $slug;
            }
        }
        return $return;
    }

    /**
     * Get Post by Slug
     *
     * Return the post ID of that matches for the slug.
     *
     * @since 1.4.0
     *
     * @param string $slug Socialized vanity slug to retrieve.
     * @param bool $force_query If false, it will attempt to look up a cached version for performance. The cached versions expire after 4 hours. Default is true.
     *
     * @return array|false An associative array with keys for `type` (string, options are `post`, `term`, and `user`), `id` (integer), and `url` (string) if found. False otherwise.
     */
    public function get_page_by_slug($slug = '', $force_query = true)
    {
        if (!empty($slug)) {
            $cache_key = 'slug_' . $slug;
            $object = Utilities::get_cache($cache_key);
            if (!$object || !is_array($object) || $force_query) {
                $object = $this->lookup_slug($slug);
                if ($object !== false) {
                    Utilities::set_cache($cache_key, $object); //Cache for 4 hours
                    return $object;
                }
            }
        }
        return false;
    }

    /**
     * Get All Slugs
     *
     * Retrieve an associative array of key/value pairs, where the key is the slug, and the value is the post ID
     *
     * @since 1.4.0
     *
     * @return array Returns an associative array of arrays with the slug as the key and the type_id as the value
     */
    public function get_all_slugs()
    {
        global $socialized_slugs;
        if (!isset($socialized_slugs)) {
            $socialized_slugs = array();
            $query_args = array(
                'fields' => 'ids',
                'post_type' => Settings::get_post_types(), // Get allowed post types
                'post_status' => array(
                    // Options include: publish, pending, draft, auto-draft, future, private, inherit, trash, any
                    'publish', // viewable by everyone
                    'future', // scheduled to be published in a future date
                    'draft', // incomplete post viewable by anyone with proper user role
                    'pending', // awaiting a user with the `publish_posts` capability to publish
                    'private' // viewable only to WP users as appropriate level
                ),
                'meta_query' => array(array(
                    'key' => Settings::$vars['prefix'] . 'slug',
                    'value' => array(''),
                    'compare' => 'NOT IN'
                ))
            );
            if (in_array('attachment', $query_args['post_type'])) {
                $query_args['post_status'][] = 'inherit';
                // Since we're including the `inherit`, we need to ensure we don't get any auto-drafts or trash
                $query_args['post__not_in'] = Utilities::get_posts(array(
                    'post_type' => $query_args['post_type'], // Same post type
                    'post_status' => array('auto-draft', 'trash'), // Specifically exclude these
                ), 'excluded');
            }
            $posts = Utilities::get_posts($query_args, 'posts-with-slugs');
            if (count($posts)) {
                foreach ($posts as $post_id) {
                    $slug = Utilities::get_meta($post_id, 'slug');
                    $socialized_slugs[$slug] = $this->get_slug_info_by_id($post_id);
                }
            }
        }
        return $socialized_slugs;
    }

    /**
     * Get Slug Info by Object ID
     *
     * @param int $id The object ID
     * @param string $type Optional. The object type. Options include post, user, comment, and term. Default is `post`
     * @param string $taxonomy Optional. If the object is a `term`, then the `taxonomy` should be passed here to look up it's URL.
     *
     * @return array An associative array with keys for type, id, url, and taxonomy. URL and taxonomy may be empty.
     */
    function get_slug_info_by_id($id, $type = 'post', $taxonomy = '')
    {
        $return = array(
            'type' => $type,
            'id' => $id,
            'url' => '',
            'taxonomy' => ''
        );
        switch ($type) {
            case 'term':
                $return['taxonomy'] = $taxonomy;
                $url = get_term_link($id, $taxonomy);
                if (is_string($url)) {
                    $return['url'] = $url;
                }
                break;
            case 'user':
                $return['url'] = get_author_posts_url($id);
                break;
            case 'post':
                $url = get_the_permalink($id);
                if ($url) {
                    $return['url'] = $url;
                }
                break;
        }
        return $return;
    }

    /**
     * Add Slug
     *
     * Retrieve an associative array of key/value pairs, where the key is the slug, and the value is the post ID
     *
     * @since 1.4.0
     *
     * @param string $slug Vanity slug to be added
     * @param string|int $post_id Post ID to be assigned this slug
     * @param array|false $all_slugs Optional. List of all slugs if already retrieved. Otherwise, it looks it up from the plugin options.
     * @param string $type Optional. The object type. Options are `post`, `term`, and `user`. Default is `post`.
     * @param string $taxonomy Optional. If the `type` is `term`, then optionally specify the taxonomy.
     *
     * @return int|bool The new meta field ID if the field didn't exist and was therefore added, true on successful update, false on failure.
     */
    function add_slug($slug, $id, $all_slugs = false, $prev_value = '', $type = 'post', $taxonomy = '')
    {
        if (in_array($type, array('post', 'term', 'user'))) {
            $id = intval($id); //Convert it to an integer if it's a string
            if ($all_slugs === false) {
                // Get all slugs if passed is intentionally false
                $all_slugs = $this->get_all_slugs();
            }

            //Look for all of the old keys that belong to this post (if more than one) and remove them all from the array
            // $old_slugs = array_keys($all_slugs, $id);
            // if (count($old_slugs)) {
            //     foreach ($old_slugs as $old_slug) {
            //         unset($all_slugs[$old_slug]);
            //     }
            // }

            // Set values for new key
            $all_slugs[$slug] = $this->get_slug_info_by_id($id, $type, $taxonomy);

            Settings::set('all_slugs', $all_slugs); // Update option with array of all slugs
            return Utilities::set_meta($id, 'slug', $slug, false, true, $type); // Update post with meta value
            //return update_metadata($type, $id, Settings::$vars['prefix'] . 'slug', $slug, $prev_value); // Update post meta with slug
        }
        return false;
    }

    /**
     * Get Slug Value
     *
     * @since 3.0.4
     *
     * @param string $slug The slug to lookup and retrieve the values for.
     *
     * @return array|false An associative array with keys for `type` (string, options are `post`, `term`, and `user`), `id` (integer), and `url` (string)
     */
    private function lookup_slug($slug)
    {
        return Utilities::array_has_key($slug, $this->get_all_slugs(), false);
    }

    /**
     * Get Campaign Term
     *
     * Retrieves the value for utm_term for the individual post, using Yoast SEO's "focus keyphrase" if available.
     *
     * @since 1.0.0
     *
     * @param integer $post_id Post ID.
     *
     * @return string Campaign term.
     */
    public static function get_primary_keyword($id, $type = 'post')
    {
        // Get the plugin's meta value first
        $keyword = sanitize_text_field(Utilities::get_meta($id, 'term', '', false, true, $type));
        //$keyword = get_metadata($type, $id, Settings::$vars['prefix'] . 'term', true);
        if (empty($keyword) && Utilities::is_plugin_active('wordpress-seo/wp-seo.php')) {
            // Set Yoast SEO's "focus keyphrase" as "utm_term" if empty
            $keyword = sanitize_text_field(Utilities::get_meta($id, '_yoast_wpseo_focuskw', '', false, false, $type));
            //$keyword = get_metadata($type, $id, '_yoast_wpseo_focuskw', true);
        }
        return $keyword;
    }

    /**
     * Get UTM Query
     *
     * @since 1.0.0
     *
     * @param string $platform Platform identifier.
     * @param integer $post_id Optional. Post ID.
     * @param boolean $return_array Optional. Return query as an array or as a query string.
     *
     * @return string|array The query as a URL query or an associative array of key/value pairs
     */
    public static function get_permalink_with_query($platform, $post_id = false, $return_array = false, $type = 'post')
    {
        if ($post_id === false) {
            global $post;
            if (isset($post)) {
                $post_id = $post->ID;
            }
        }
        switch ($platform) {
            case 'vanity-url':
            case 'email':
                $medium = $platform;
                break;
            default:
                $medium = 'social';
                break;
        }
        $utm = array(
            'utm_id' => Settings::$vars['slug'], // Campaign ID. Used to identify a specific campaign or promotion. This is a required key for GA4 data import. Use the same IDs that you use when uploading campaign cost data.
            'utm_source' => esc_attr($platform), // Referrer, for example: google, newsletter4, billboard
            'utm_medium' => esc_attr($medium), // Marketing medium, for example: cpc, banner, email
            'utm_campaign' => esc_attr(Settings::get('utm_campaign', true, 'link_tracking')), // Product, slogan, promo code, for example: spring_sale
            'utm_source_platform' => esc_attr(get_bloginfo('name') . ' ' . __('WordPress Website', 'socialized')), //The platform responsible for directing traffic to a given Analytics property (such as a buying platform that sets budgets and targeting criteria or a platform that manages organic traffic data). For example: Search Ads 360 or Display & Video 360.
            'utm_content' => 'socialized-share-link', // Use to differentiate creatives. For example, if you have two call-to-action links within the same email message, you can use utm_content and set different values for each so you can tell which version is more effective.
            'utm_term' => self::get_primary_keyword($post_id, $type), // Used for paid search. Use utm_term to note the keywords for this ad. Example: running+shoes
            'utm_creative_format' => 'user-share-link', // Type of creative, for example: skyscraper, carousel, interactive, video, and image, and display
            'utm_marketing_tactic' => 'prospecting' // Targeting criteria applied to a campaign, for example: remarketing, onboarding, prospecting
        );
        if ($return_array) {
            return $utm;
        }
        return http_build_query($utm, '', '&', PHP_QUERY_RFC3986);
    }
}
