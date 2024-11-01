<?php

namespace AuRise\Plugin\Socialized;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Class Utilities
 *
 * Utility functions used by this plugin.
 *
 * @package AuRise\Plugin\Socialized
 *
 * - Debugging
 * -- server_timing()
 *
 * - Caching
 * -- refresh_cache()
 * -- cache_prefix()
 * -- set_cache()
 * -- get_cache()
 *
 * - Metadata
 * -- set_meta()
 * -- get_meta()
 * -- sanitize_meta_id()
 * -- validate_meta_type()
 * -- sanitize_meta_key()
 *
 * - Array Manipulation
 * -- array_has_key()
 *
 * - String Manipulation
 * -- format_atts()
 * -- discover_shortcodes()
 *
 * - Resource Management
 * -- optionally_load_resource()
 *
 * - Post Management
 * -- get_posts()
 */
class Utilities
{

    /**
     * Send Time to Server-Timing API
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param DateTime $start a DateTime object of when the timing begain
     * @param string $name The name of what you're timing
     * @param string $key Optional. The key of what you're timing. Default is this plugin's slug.
     *
     * @return void
     */
    public static function server_timing($start, $name, $key = '')
    {
        $end = new \DateTime('now');
        @header(sprintf(
            'Server-Timing: %s;desc="%s";dur=%s',
            sanitize_key(empty($key) ? 'Plugin: ' . Settings::$vars['slug'] : $key),
            esc_attr($name),
            intval($end->format('Uv')) - intval($start->format('Uv')) //Measured in milliseconds
        ), false);
    }

    //**** Caching ****/

    /**
     * Check if Cache Should Be Cleared
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param bool $logged_in Refresh cache if user is logged in, otherwise respond to queries only.
     *
     * @return bool True if cache should be refreshed. False otherwise.
     */
    public static function refresh_cache($logged_in = true)
    {
        global $wp_customize;
        $soft_check = isset($_GET['nocache']) || isset($_GET['preview']) || (isset($_GET['avoid-minify']) && $_GET['avoid-minify'] == 'true') || isset($wp_customize);
        if (!$soft_check && $logged_in) {
            return is_user_logged_in();
        }
        return $soft_check;
    }

    /**
     * Get Caching Prefix
     *
     * Prefixes are unique to language (for caching language-based content),
     * group (optional), and post ID (optional).
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param string $group Optional. A name to group similar items of content together.
     * @param int $id Optional. A unique identifier to use in the prefix.
     *
     * @return string A sanitized string with no trailing underscore.
     */
    private static function cache_prefix($group = '', $id = '')
    {
        // Get locale based on user, pass 0 so it looks them up
        return sanitize_key('locale-' . get_user_locale(0) . ($group ? '_' . $group : '') . ($id ? '_post-' . $id : ''));
    }

    /**
     * Cache Data
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param string $key The key to use. This function will sanitize it for the database. Expected to not be SQL-escaped. Must be 172 characters or fewer in length.
     * @param mixed $value The data to be cached
     * @param int $expire Optional. The number of hours to cache before expiration. Default is 4
     * @param string $type Optional. The type of caching method. Can be `transient`, `cache`, or `both`. Default is `both`
     * @param string $group Optional. The grouping for this cache. Default is an empty string. The value is always automatically prepended with language data.
     * @param int|false $post_id Optional. If this data is associted with a post, pass the post ID or false to save it to the post's meta data
     * @param bool $generated_at Optional. If you want to display a timestamp for when this item was cached, set to true. Default is false. $value must be a string.
     *
     * @return mixed $value
     */
    public static function set_cache($key, $value, $expire = 4, $type = 'both', $group = '', $post_id = '', $generated_at = false)
    {
        if ($key) {
            $prefix = self::cache_prefix($group, $post_id);
            $key = sanitize_key($key);
            $transient_key = $prefix . '_' . $key;
            if ($generated_at && is_string($value)) {
                $value = sprintf(
                    '<!-- Cache generated for [%s] on %s to expire in %s hour(s) -->',
                    $transient_key,
                    date('n/j/Y H:i:s:u T'),
                    $expire
                ) . $value;
            }
            if ($type !== 'transient') {
                //Documentation: https://developer.wordpress.org/reference/functions/wp_cache_set/
                wp_cache_set($key, $value, $prefix, $expire * HOUR_IN_SECONDS);
            }
            if ($type !== 'cache') {
                //Documentation: https://developer.wordpress.org/reference/functions/set_transient/
                set_transient($transient_key, $value, $expire * HOUR_IN_SECONDS);
            }
        }
        return $value;
    }

    /**
     * Get Cached Data
     *
     * WP Cache Documentation: https://developer.wordpress.org/reference/functions/wp_cache_get/
     * Transient Documentation: https://developer.wordpress.org/reference/functions/get_transient/
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param string $key The key to use. This function will sanitize it for the database
     * @param string $type Optional. The type of caching method. Can be `transient`, `cache`, or `both`. Default is `both`
     * @param string $group Optional. The grouping for this cache. Default is an empty string. The value is always automatically prepended with language data.
     * @param int $id Optional. Optionally pass a post ID or other identifier to associate the data.
     *
     * @return mixed|false Value of data, false on failure to retrieve contents.
     */
    public static function get_cache($key, $type = 'both', $group = '', $id = '')
    {
        $value = false;
        if ($key) {
            $prefix = self::cache_prefix($group, $id);
            $key = sanitize_key($key);
            //If type is `cache` or `both, attempt to get the cache first
            if ($type !== 'transient' && $value === false) {
                $value = wp_cache_get($key, $prefix);
            }
            // If type is not `cache` and value is still false, get transient
            if ($type !== 'cache' && $value === false) {
                $value = get_transient($prefix . '_' . $key);
            }
        }
        return $value;
    }

    //**** Metadata ****/

    /**
     * Set Metadata
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param int $id The ID of the object to set metadata for.
     * @param string $key The meta key to set.
     * @param mixed $value The meta value to set.
     * @param bool $hidden Optional. If true, the meta key will be prefixed with an underscore to indicate it is a hidden field.
     * @param bool $prefixed Optional. If true, the meta key will be prefixed with the plugin slug to indicate it is part of this plugin. Default is true.
     * @param string $type Optional. Type of object metadata is for. Accepts `post`, `comment`, `term`, or `user`. Default is `post`
     *
     * @return mixed The meta key value that was set
     */
    public static function set_meta($id, $key, $value, $hidden = false, $prefixed = true, $type = 'post')
    {
        if (($id = self::sanitize_meta_id($id)) && ($key = self::sanitize_meta_key($key)) && self::validate_meta_type($type)) {
            if ($key) {
                if ($prefixed) {
                    $key = Settings::$vars['name'] . '_' . $key;
                }
                if ($hidden) {
                    $key = '_' . $key;
                }
            }
            update_metadata($type, $id, sanitize_key($key), $value);
        }
        return $value;
    }

    /**
     * Get Meta Data
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param int $id The ID of the object to get metadata from
     * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
     * @param mixed $default Optional. The default value to return if not set. Default is an empty string.
     * @param bool $hidden Optional. If true, the meta key will be prefixed with an underscore to indicate it is a hidden field.
     * @param bool $prefixed Optional. If true, the meta key will be prefixed with the plugin slug to indicate it is part of this plugin. Default is true.
     * @param string $type Optional. Type of object metadata is from. Accepts `post`, `comment`, `term`, or `user`. Default is `post`
     *
     * @return mixed The value of the meta field if it is a boolean, number, or truthy value. The value of $default otherwise.
     */
    public static function get_meta($id, $key = '', $default = '', $hidden = false, $prefixed = true, $type = 'post')
    {
        if (($id = self::sanitize_meta_id($id)) && self::validate_meta_type($type)) {
            $key = self::sanitize_meta_key($key);
            if ($key) {
                if ($prefixed) {
                    $key = Settings::$vars['name'] . '_' . $key;
                }
                if ($hidden) {
                    $key = '_' . $key;
                }
            }
            $value = get_metadata($type, $id, sanitize_key($key), true);
            if (is_bool($value) || is_numeric($value) || $value) {
                return $value;
            }
        }
        return $default;
    }

    /**
     * Sanitize Object ID
     *
     * Validates and returns a numeric ID (positive integer).
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param mixed $id The object ID
     *
     * @return int The object ID. Returns 0 otherwise.
     */
    private static function sanitize_meta_id($id)
    {
        return is_numeric($id) && (int)$id > 0 ? intval($id) : 0;
    }

    /**
     * Validate Meta Type
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param string $type The type to check against.
     *
     * @return bool Returns true if $type is `post`, `user`, or `term`.
     */
    private static function validate_meta_type($type)
    {
        return !empty($type) && in_array($type, array('post', 'user', 'term'));
    }

    /**
     * Sanitize Meta Key
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param string $key The meta key to sanitize.
     *
     * @return string The sanitized meta key. Empty string otherwise.
     */
    private static function sanitize_meta_key($key)
    {
        return is_string($key) && !empty(sanitize_key($key)) ? sanitize_key($key) : '';
    }

    //**** Array Manipulation ****/

    /**
     * Array Key Exists and Has Value
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param string|int $key The key to search for in the array.
     * @param array $array The array to search.
     * @param mixed $default The default value to return if not found or is empty. Default is an empty string.
     *
     * @return mixed|null The value of the key found in the array if it exists or the value of `$default` if not found or is empty.
     */
    public static function array_has_key($key, $array = array(), $default = '')
    {
        //Check if this key exists in the array
        $valid_key = (is_string($key) && !empty(sanitize_text_field($key))) || is_numeric($key);
        $valid_array = is_array($array) && count($array);
        if ($valid_key && $valid_array && array_key_exists($key, $array)) {
            //Always return if it's a boolean or number, otherwise only return if it's truthy
            if (is_bool($array[$key]) || is_numeric($array[$key]) || $array[$key]) {
                return $array[$key];
            }
        }
        return $default;
    }

    //**** String Manipulation ****/

    /**
     * Get Name of Constant Variable
     *
     * @since 4.0.0
     *
     * @param string $str Variable Name
     *
     * @return string Variable name formatted as a constant
     */
    public static function get_constant_name($str)
    {
        return strtoupper(str_replace('-', '_', sanitize_key($str)));
    }

    /**
     * Generate a Random String.
     *
     * Used for generating random vanity URLs.
     *
     * @since 1.0.0
     *
     * @param integer $length Optional. Number of characters to put in string. 8.
     * @param string $keyspace Optional. Letters, numbers, and/or symbols to select from for string.
     *
     * @return string A randomly generated string.
     */
    public static function random_str($length = 8, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_$.!*()')
    {
        $str = '';
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, mb_strlen($keyspace, '8bit') - 1)];
        }
        return sanitize_key($str);
    }

    /**
     * Format Attributes Array to String
     *
     * Can be used for shortcode attributes and form HTML fields.
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param array $atts An associative array of key/value pairs to convert
     * @param string $key_prefix Optional. A string to prepend to every key
     *
     * @return string A string formatted as `%s="%s"` for every attribute separated by a space
     */
    public static function format_atts($atts = array(), $key_prefix = '')
    {
        if (is_array($atts) && count($atts)) {
            $output = array();
            foreach ($atts as $key => $value) {
                $type = gettype($value);
                $key = strtolower(trim($key_prefix . $key));
                switch ($type) {
                    case 'string':
                        if (stripos($value, 'http') === 0) {
                            $output[] = sprintf('%s="%s"', esc_attr($key), esc_url($value, array('https', 'http')));
                        } else {
                            $output[] = sprintf('%s="%s"', esc_attr($key), esc_attr($value));
                        }
                        break;
                    case 'integer':
                        $output[] = sprintf('%s="%s"', esc_attr($key), esc_attr($value));
                        break;
                    case 'array':
                    case 'object':
                        $output[] = sprintf('%s="%s"', esc_attr($key), esc_attr(http_build_query($value)));
                        break;
                    default:
                        break;
                }
            }
            return implode(' ', $output);
        }
        return '';
    }

    /**
     * Parse Content for Specified Shortcodes
     *
     * Parse a string of content for a specific shortcode to retrieve its attributes and content
     *
     * @since 1.0.0
     *
     * @static
     *
     * @param string $content The content to be parsed
     * @param string $tag The shortcode tag
     * @param bool $closing_tag If true, it will look for closing tags. If false, it assumes the shortcode is self-closing. Default is false.
     *
     * @return array An associative array with `tag` (string) and `shortcodes` (sequential array). If shortcodes were discovered, each one has keys for `atts` (associative array) and `content` (string)
     */
    public static function discover_shortcodes($content, $tag, $closing_tag = false)
    {
        $return = array(
            'tag' => $tag,
            'shortcodes' => array()
        );
        $start_tag = '[' . $tag . ' '; //Opens the start tag, assumes there are attributes
        $start_tag_end = ']'; //Closes the start tag
        if ($closing_tag) {
            //If this is NOT a self-closing tag, it will have a closing tag after content
            $closing_tag = '[/' . $tag . ']';
        }
        $original_content = $content;
        $start = strpos($content, $start_tag);
        while ($start !== false) {
            $shortcode = array(
                'atts' => array(),
                'content' => ''
            );
            //Parse for shortcode attributes
            $atts_str = trim(str_replace(
                array($start_tag, $start_tag_end),
                '',
                substr($content, $start, strpos($content, $start_tag_end, $start))
            ));
            if (strpos($atts_str, '"') !== false) {
                $atts = explode('" ', substr(
                    $atts_str,
                    0,
                    -1 //Clip off the last character, which is a double quote
                ));
                if (is_array($atts) && count($atts)) {
                    foreach ($atts as $att_str) {
                        $pair = explode('="', $att_str);
                        if (is_array($pair) && count($pair) > 1) {
                            //Validate & normalize the key
                            $key = is_string($pair[0]) ? trim($pair[0]) : '';
                            if (!empty($key)) {
                                $shortcode['atts'][$key] = is_string($pair[1]) ? html_entity_decode($pair[1]) : $pair[1];
                            }
                        }
                    }
                }
            }
            $content_end = strpos($content, $start_tag_end, $start) + strlen($start_tag_end); //End after the self-closing start tag
            if ($closing_tag) {
                $closing_tag_pos = strpos($content, $closing_tag, $content_end);
                if ($closing_tag_pos !== false) {
                    $shortcode['content'] = substr($content, $content_end, $closing_tag_pos); //Get the content between the opening and closing tag
                    $content_end = strpos($content, $start_tag_end, $closing_tag_pos) + strlen($closing_tag); //End after the closing tag
                }
            }
            //If anything was discovered in this shortcode, add it to the return object
            if (count($shortcode['atts']) || !empty($shortcode['content'])) {
                $return['shortcodes'][] = $shortcode;
            }
            $content = substr($content, $content_end); //Remove this shortcode from the content to continue parsing in the do-while
            $start = $content_end;
        }

        //Now do it again, but assuming there are no attributes, just totally self closing
        $start_tag = '[' . $tag . ']'; //A single open tag with no attributes
        $content = $original_content; //Reset the content back to it's original state
        $start = strpos($content, $start_tag);
        while ($start !== false) {
            $shortcode = array(
                'atts' => array(),
                'content' => ''
            );
            $content_end = strlen($start_tag); //End after the self-closing start tag
            if ($closing_tag) {
                $closing_tag_pos = strpos($content, $closing_tag, $content_end);
                if ($closing_tag_pos !== false) {
                    $shortcode['content'] = substr($content, $content_end, $closing_tag_pos); //Get the content between the opening and closing tag
                    $content_end = $content_end + strlen($shortcode['content']) + strlen($closing_tag); //End after the closing tag
                }
            }
            //If anything was discovered in this shortcode, add it to the return object
            if (!empty($shortcode['content'])) {
                $return['shortcodes'][] = $shortcode;
            }
            $content = substr($content, $content_end); //Remove this shortcode from the content to continue parsing in the while
            $start = $content_end;
        }

        return $return;
    }

    /**
     * Get Plugin Version as Integer
     *
     * Converts a plugin version formatted as X.X.X to a single integer to compare newer and older versions.
     *
     * @since 4.0.0
     *
     * @static
     *
     * @param string $version Plugin version formatted as X.X.X
     *
     * @return int The version as an integer.
     */
    public static function get_version($version)
    {
        if (empty($version)) {
            return 0;
        }
        $v = array_reverse(explode('.', $version));
        $m = 1; // Multiplier starts at 1
        $t = 0; // Total to return
        foreach ($v as $x) {
            $t += intval($x) * $m;
            $m = $m * 10; // Increase the multiplier by 10
        }
        return $t;
    }

    //**** Resource Management ****/

    /**
     * Optionally Load Resource
     *
     * Usually from within a shortcode, enqueue a script or stylesheet if it hasn't been already.
     *
     * @param string $handle The resource handle.
     * @param string $url Optional. An absolute URL to the resource to load. If excluded, can only enqueue previously registered resources.
     * @param array $dependencies Optional. An array of handles that the resource depends on.
     * @param string|bool $version Optional. The version, if any. Default is `false` to exclude a version.
     * @param bool|string $media_or_footer Optional. For stylesheets, this parameter is expecting a media type. Default is `all`. For scripts, this parameter is expecting a boolean value for whether to place script in the footer. Default is true.
     * @param array $localized_data Optional. Additional data to localize a script.
     * @return string The status (if debugging is enabled)
     */
    public static function optionally_load_resource($handle, $url = '', $type = 'style', $dependencies = array(), $version = false, $media_or_footer = '', $localized_data = array())
    {
        $result = '';
        if ($type == 'style') {
            if (wp_style_is($handle, 'registered') && !wp_style_is($handle, 'queue')) {
                wp_enqueue_style($handle);
                if (WP_DEBUG) {
                    $result = sprintf('Stylesheet [%s] is already registered, just enqueue it', $handle);
                }
            } elseif (!wp_style_is($handle, 'registered') && !wp_style_is($handle, 'queue')) {
                if ($url) {
                    wp_enqueue_style($handle, $url, $dependencies, $version, $media_or_footer ? $media_or_footer : 'all');
                    if (WP_DEBUG) {
                        $result = sprintf('Stylesheet [%s] is NOT enqueued, set it [%s]', $handle, $url);
                    }
                } elseif (WP_DEBUG) {
                    $result = sprintf('Stylesheet [%s] is NOT enqueued and a URL was not provided to set it', $handle);
                }
            } elseif (WP_DEBUG) {
                $result = sprintf('Stylesheet [%s] is already registered and enqueued, do nothing', $handle);
            }
        } elseif ($type == 'script') {
            if (wp_script_is($handle, 'registered') && !wp_script_is($handle, 'queue')) {
                wp_enqueue_script($handle);
                if (WP_DEBUG) {
                    $result = sprintf('Script [%s] is already registered, just enqueue it', $handle);
                }
            } elseif (!wp_script_is($handle, 'registered') && !wp_script_is($handle, 'queue')) {
                if ($url) {
                    if (!is_bool($media_or_footer) && !$media_or_footer) {
                        $media_or_footer = array('in_footer' => true, 'strategy' => 'defer'); // Defer loading in footer
                    }
                    wp_enqueue_script($handle, $url, $dependencies, $version, $media_or_footer); //Place in footer
                    if (WP_DEBUG) {
                        $result = sprintf('Script [%s] is NOT registored OR enqueued, set it [%s]', $handle, $url);
                    }
                } elseif (WP_DEBUG) {
                    $result = sprintf('Script [%s] is NOT enqueued and a URL was not provided to set it', $handle);
                }
            } elseif (WP_DEBUG) {
                $result = sprintf('Script [%s] is already registered and enqueued, do nothing', $handle);
            }
            if (count($localized_data)) {
                global $wp_scripts;
                $data = $wp_scripts->get_data($handle, 'data');
                if (!empty($data)) {
                    //Localize it
                    wp_localize_script($handle, 'aurise_video_obj', $localized_data);
                }
            }
        }
        return $result;
    }

    //**** Post Management ****/

    /**
     * Get Posts
     *
     * Query results are cached for 1 hour.
     *
     * @param array $args
     * @param string $cache_key Optional.
     * @param string $cache_group Optional.
     *
     * @return array An array of posts based on parameters.
     */
    public static function get_posts($args = array(), $cache_key = '', $cache_group = 'socialized-queries')
    {
        $force_new = self::refresh_cache(true);
        if ($cache_key) {
            $posts = self::get_cache($cache_key, 'both', $cache_group);
            if (is_array($posts) && !$force_new) {
                return $posts; // Returned the cached posts
            }
        }

        // Assemble the query args
        if (is_array($args)) {
            $user_args = &$args;
        } elseif (is_object($args)) {
            $user_args = get_object_vars($args);
        } elseif (is_string($args)) {
            wp_parse_str($args, $user_args);
        } else {
            return null; // Bail
        }
        $defaults = array(
            'fields' => 'ids', // Returns an array of IDs
            'post_type' => 'post',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => true,
            'ignore_sticky_posts' => true, // Treat sticky posts like regular posts
            'no_found_rows' => true
        );
        $query_args = array_merge($defaults, $user_args);
        if (empty($cache_key)) {
            $cache_key = 'posts-query_' . http_build_query($query_args, '', '_');

            // Try getting cached posts again now
            $posts = self::get_cache($cache_key, 'both', $cache_group);
            if (is_array($posts) && !$force_new) {
                return $posts; // Returned the cached posts
            }
        }

        // Run the query, cache, and return posts
        $get_posts = new \WP_Query();
        $posts = $get_posts->query($query_args);
        self::set_cache($cache_key, $posts, 1, 'both', $cache_group);
        return $posts;
    }

    //**** Compatibility ****/

    /**
     * Is Plugin Active
     *
     * Used for generating random vanity URLs.
     *
     * @since 1.3.7
     * @param string $plugin Name of plugin to check for
     * @return bool Returns true if it is active, false if it is not
     */
    public static function is_plugin_active($plugin)
    {
        //Provides access to the is_plugin_active() function if not available
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        return is_plugin_active($plugin);
    }
}
