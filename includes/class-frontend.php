<?php

namespace AuRise\Plugin\Socialized;

defined('ABSPATH') || exit; // Exit if accessed directly

use AuRise\Plugin\Socialized\Utilities;
use AuRise\Plugin\Socialized\Settings;


/**
 * Plugin Frontend File
 *
 * @package AuRise\Plugin\Socialized
 *
 * @since 4.0.0
 */
class Frontend
{
    public $block = null;

    /**
     * Constructor
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function __construct()
    {
        add_action('init', array($this, 'init')); // Add init hook to initialise shortcode
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 20); // Enqueue styles for frontend
        add_action('template_redirect', array($this, 'redirect'), 20); // Perform redirect

        // When set to 9, is either empty or has the actual excerpt
        // When set to 11, I see the content always with content, or excerpt?
        add_filter('get_the_excerpt', array($this, 'remove_from_excerpt'), 11);
        if (Settings::get('buttons_location', true, 'display') != 'hide') {
            add_filter('the_content', array($this, 'display_buttons'), 20); // Display buttons in content
        }

        // Initialise block class
        // include_once('class-block.php');
        // $this->block = new Block();
    }

    /**
     * Initialise Shortcode
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function init()
    {
        // Create shortcode to use anywhere shortcodes are processed
        add_shortcode(Settings::$vars['slug'], array($this, 'shortcode'));
    }

    /**
     * Register Shortcode.
     *
     * Creates the shortcode to use in post content that displays the social media sharing
     * buttons on the frontend for a single post.
     *
     * @since 1.0.0
     *
     * @param array $atts Optional. Shortcode attributes.
     * @param string $content Optional.
     * @param string $tag Optional.
     *
     * @return string Social media sharing button HTML.
     */
    public function shortcode($atts = array(), $content = '', $tag = '')
    {
        extract(shortcode_atts(array(
            'post_id' => '',
            'placement' => '', // top | end | stick-left | stick-right | hide
            'preview' => '',
        ), array_change_key_case((array)$atts, CASE_LOWER), $tag));
        $post_id = $post_id && (int)$post_id > 0 ? intval($post_id) : false;
        $preview = !empty($preview);
        if ($preview || Settings::is_allowed($post_id)) {
            // override default attributes with user attributes & normalize attribute keys, lowercase
            if (is_string($content) && $content) {
                // If there is content, display buttons
                return $this->display_buttons(
                    $content,
                    in_array($placement, array('top', 'end', 'stick-left', 'stick-right', 'hide', '')) ? $placement : '',
                    $post_id,
                    $preview
                );
            }
            // Just get the buttons directly
            return $this->get_buttons($post_id, false, $preview ? 'top' : 'auto', $preview);
        }
        return '';
    }

    /**
     * Enqueue Frontend Styles and Scripts.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function enqueue_scripts()
    {
        $minified = defined('SCRIPTS_DEBUG') && constant('SCRIPTS_DEBUG') ? '.min' : '';

        //Enqueue the frontend stylesheet for buttons
        wp_enqueue_style(
            Settings::$vars['slug'], // Handle
            Settings::$vars['url'] . "assets/styles/socialized$minified.css", // Source URL
            array(), // Dependencies
            $minified ? SOCIALIZED_VERSION : @filemtime(Settings::$vars['path'] . 'assets/styles/socialized.css') // Version
        );

        // Optionally enqueue the FontAwesome based on settings
        $exclude_fontawesome = Settings::get('exclude_fontawesome', true, 'display');
        if (Settings::get('icon_type', true, 'display') == 'fontawesome' && ($exclude_fontawesome === '1' || $exclude_fontawesome == 'false')) {
            wp_enqueue_script(
                Settings::$vars['prefix'] . '-fontawesome', // Handle
                Settings::$vars['url'] . 'assets/fontawesome/js/all.min.js', // Source URL
                array(), // Dependencies
                '6.4.2', // Version
                array(
                    'in_footer' => true, // Load in Footer
                    'strategy' => 'async' // Load asynchronously
                )
            );
        }

        // Enqueue frontend scripts for buttons
        wp_register_script(
            Settings::$vars['slug'] . '-stickybits', // Handle
            Settings::$vars['url'] . 'assets/scripts/vendor/stickybits.min.js', // Source URL
            array(), // Dependencies
            '3.7.9', // Stickybits version
            array(
                'in_footer' => true, // Load in Footer
                'strategy' => 'defer' // Defer the load
            )
        );
        wp_enqueue_script(
            Settings::$vars['slug'], // Handle
            Settings::$vars['url'] . "assets/scripts/socialized$minified.js", // Source URL
            array(Settings::$vars['slug'] . '-stickybits'), // Dependencies
            $minified ? SOCIALIZED_VERSION : @filemtime(Settings::$vars['path'] . 'assets/scripts/socialized.js'), // Version
            array(
                'in_footer' => true, // Load in Footer
                'strategy' => 'defer' // Defer the load
            )
        );
    }

    /**
     * Removes Socialized Text from Excerpt
     *
     * @since 4.0.0
     *
     * @param string $excerpt the post excerpt to filter
     *
     * @return string the filtered post excerpt
     */
    public function remove_from_excerpt($excerpt)
    {
        if (!empty($excerpt)) {
            $text_to_remove = __('Share this on:', 'socialized'); // This text always gets added for accessibility
            if (Settings::get('icon_type', true, 'display') == 'text') {
                // When the icon type is set to use text links, their labels get added here too, so remove them
                $text_to_remove .= implode('', array_column(Settings::get_platforms(), 'title'));
            }
            if (in_array('vanity-url', array_keys(Settings::get_platforms()))) {
                // When the vanity URL link is enabled, it's "Copied!" text gets added at the end, so remove it
                $text_to_remove .= __('Copied!', 'socialized');
            }
            if (strpos($excerpt, $text_to_remove) !== false) {
                $excerpt = str_replace($text_to_remove, '', $excerpt);
            }
        }
        return $excerpt;
    }

    /**
     * Get Buttons
     *
     * Generates the HTML elements of the social media sharing buttons for a single post.
     *
     * @since 1.0.0
     *
     * @param integer|false $post_id Optional. Post ID.
     * @param boolean $echo Optional. Echo or return the button HTML.
     * @param string $placement Optional. Where to place the button HTML. Default is `auto`.
     * @param bool $preview Optional. If true, buttons will not function and are for display purposes only. Default is false.
     *
     * @return string Social media sharing buttons HTML.
     */
    private function get_buttons($post_id = false, $echo = true, $placement = 'auto', $preview = false)
    {
        $post_id = $preview ? 0 : ($post_id === false ? get_the_ID() : $post_id);
        $cache_group = 'share-attributes';
        $output = '';
        $redirecting = Settings::get('redirecting', true, 'advanced');
        $icon_type = Settings::get('icon_type', true, 'display');
        if ($preview && $icon_type == 'fontawesome') {
            $icon_type = 'png';
        }
        $share_atts = array(
            'url' => '',
            'permalink' => $preview ? '#' : get_the_permalink($post_id),
            'slug' => $preview ? '#' : get_post_meta($post_id, 'socialized_slug', true),
            'title' => $preview ? __('Preview', 'socialized') : get_the_title($post_id),
            'description' => $preview ? __('This is a preview of the socialized sharing buttons.', 'socialized') : get_post_meta($post_id, '_yoast_wpseo_metadesc', true), //Get Yoast meta description
            'twitter' => urlencode(Settings::get('twitter', true, 'link_tracking')),
            'twitter_related' => urlencode(Settings::get('twitter_related', true, 'link_tracking')),
            'site_url' => urlencode(get_bloginfo('url')),
            'image' => ''
        );

        //Search for images within the content to feature
        if (!$preview) {
            $featured_image = Utilities::get_cache('image', 'both', $cache_group, $post_id);
            if (!$featured_image || Utilities::refresh_cache()) {
                $featured_image = 'No image available';
                // Try the Open Graph image set in Yoast SEO
                $image_id = Utilities::get_meta($post_id, '_yoast_wpseo_opengraph-image-id', '', false, false);
                if (!$image_id) {
                    // Try the Twitter image set in Yoast SEO
                    $image_id = Utilities::get_meta($post_id, '_yoast_wpseo_twitter-image-id', '', false, false);
                    if (!$image_id) {
                        // Try the post's featured image
                        $image_id = get_post_thumbnail_id($post_id); // Returns 0 if not set, or false if post doesn't exist
                        if (!$image_id) {
                            // Try the first image found in the post content
                            $post_content = get_post_field('post_content', $post_id);
                            if (!empty($post_content)) {
                                //Count the number of image elements
                                $imgs_count = substr_count($post_content, '<img');
                                if ($imgs_count > 0) {
                                    $dom = new \DOMDocument; //Create a DOM parser object
                                    @$dom->loadHTML($post_content); // Parse the HTML, suppress errors with @ because content is not always going to be valid HTML
                                    // Iterate over the <img /> elements
                                    foreach ($dom->getElementsByTagName('img') as $img) {
                                        $image_url = $img->getAttribute('src'); //Retrieve the src attribute from the element and add it to the array
                                        if (!empty($image_url)) {
                                            if (strpos($image_url, '/') === 0 && strpos($image_url, '//') !== 0) {
                                                // If it's a relative URL, force it to be absolute
                                                $featured_image = home_url($image_url);
                                            } else {
                                                // If it's an absolute URL, sanitize it
                                                $featured_image = sanitize_url($image_url);
                                            }
                                            break; // Break out of the foreach loop now that it's been set
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if ($image_id && $featured_image === 'No image available') {
                    $image_url = wp_get_attachment_image_url($image_id, 'full');
                    if ($image_url) {
                        $featured_image = sanitize_url($image_url);
                    }
                }
                // Cache the featured image (or message saying not available) for 24 hours
                $featured_image = Utilities::set_cache('image', $featured_image, 24, 'both', $cache_group, $post_id);
            }
            $share_atts['image'] = sanitize_url($featured_image);
        }

        $buttons = array();
        global $socialized; // Access main class
        foreach (Settings::get_platforms() as $platform => $p) {
            if ($redirecting) {
                $share_atts['url'] = urlencode(home_url($share_atts['slug'] . $p['suffix']));
            } else {
                if (strpos($share_atts['permalink'], '?') === false) {
                    $share_atts['permalink'] .= '?';
                } else {
                    $share_atts['permalink'] .= '&';
                }
                $share_atts['url'] = urlencode($share_atts['permalink']) . $socialized->get_permalink_with_query($platform);
            }
            $popup = array_key_exists('popup', $p) ? $p['popup'] : '';
            $title_attr = Utilities::array_has_key('prefix_title', $p, true);
            $link_atts = array(
                'class' => array('socialized-link'),
                'id' => sanitize_key('share-link-' . $platform),
                'title' => sanitize_text_field($title_attr ? ($title_attr === true ? __('Share on', 'socialized') . ' ' . $p['title'] : $title_attr) : $p['title']),
                'data-platform' => $platform,
                'href' => '',
                'target' => '_blank',
                'rel' => 'noopener noreferrer nofollow',
                'tabindex' => '0', // Accessibility documentation: https://web.dev/tabindex/
                'onclick' => 'window.open(this.href,\'targetWindow\',\'toolbar=no,location=0,status=no,menubar=no,scrollbars=yes,resizable=yes,width=%1$s,height=%2$s\');return false'
            );
            if ($preview) {
                $link_atts['class'][] = 'previewing-button';
                $link_atts['disabled'] = 'disabled';
            }
            switch ($platform) {
                case 'facebook':
                case 'linkedin':
                    $fontawesome_class = 'fa-brands';
                    $link_atts['onclick'] = $preview ? 'return false' : sprintf($link_atts['onclick'], $p['width'], $p['height']);
                    $link_atts['href'] = esc_url(sprintf($p['link_format'], $share_atts['url']));
                    break;
                case 'twitter':
                    $fontawesome_class = 'fa-brands';
                    $link_atts['onclick'] = $preview ? 'return false' : sprintf($link_atts['onclick'], $p['width'], $p['height']);
                    $link_atts['href'] = esc_url(sprintf(
                        $p['link_format'],
                        $share_atts['url'],
                        urlencode($share_atts['title']),
                        $share_atts['twitter'],
                        $share_atts['twitter_related'],
                        $share_atts['site_url']
                    ));
                    break;
                case 'pinterest':
                    $fontawesome_class = 'fa-brands';
                    $link_atts['onclick'] = $preview ? 'return false' : sprintf($link_atts['onclick'], $p['width'], $p['height']);
                    $link_atts['href'] = esc_url(sprintf(
                        $p['link_format'],
                        $share_atts['url'],
                        $share_atts['image'],
                        urlencode($share_atts['title'])
                    ));
                    break;
                case 'email':
                    $fontawesome_class = 'fa-solid';
                    $link_atts['onclick'] = $preview ? 'return false' : sprintf($link_atts['onclick'], $p['width'], $p['height']);
                    $link_atts['href'] = sprintf(
                        $p['link_format'],
                        rawurlencode(esc_html__('I thought you might like this article:', 'socialized') . ' ') . str_replace(array('&', '&#038;', '&amp;'), '%26', esc_html($share_atts['title'])),
                        rawurlencode(esc_html($share_atts['description']) . ' ') . esc_url($share_atts['url'])
                    );
                    break;
                case 'vanity-url':
                    $fontawesome_class = 'fa-solid';
                    $link_atts['aria-describedby'] = Settings::$vars['slug'] . '-copied-popup';
                    $link_atts['target'] = '';
                    $link_atts['onclick'] = $preview ? 'return false' : 'socialized.copyToClipboard(event); return false;';
                    $link_atts['href'] = esc_url(urldecode($share_atts['url']));
                    break;
                default:
                    break;
            }
            if (!empty($link_atts['href'])) {
                switch ($icon_type) {
                    case 'fontawesome':
                        $icon = sprintf(
                            '<i class="%s %s" aria-hidden="true"></i>',
                            esc_attr(sanitize_html_class($fontawesome_class)),
                            esc_attr(sanitize_html_class('fa-' . $p['fontawesome']))
                        );
                        $link_atts['width'] = 32;
                        $link_atts['height'] = 32;
                        break;
                    case 'text':
                        $icon = sprintf(
                            '<span class="%s">%s</span>',
                            esc_attr(sanitize_html_class(Settings::$vars['slug'] . '-text')),
                            esc_attr($p['title'])
                        );
                        break;
                    default:
                        $icon = sprintf(
                            '<img class="%3$s lazyload" src="%1$s" alt="%2$s" width="32" height="32" />',
                            esc_attr(sanitize_url($p['icon'])),
                            esc_attr($p['title']),
                            esc_attr(sanitize_html_class(Settings::$vars['slug'] . '-icon'))
                        );
                        $link_atts['width'] = 32;
                        $link_atts['height'] = 32;
                        break;
                }
                $link_atts['class'] = implode(' ', $link_atts['class']); // Convert class array to class string
                $buttons[] = sprintf(
                    '<span class="socialized-link-wrapper %s"><a %s>%s</a>%s</span>',
                    esc_attr(sanitize_html_class($platform)),
                    Utilities::format_atts($link_atts),
                    $icon, // HTML will be escaped later
                    $popup // HTML will be escaped later
                );
            }
        }
        if (count($buttons) > 0) {
            $output = wp_kses(sprintf(
                '<p class="socialized-links %s %s %s">%s%s</p>',
                esc_attr(sanitize_html_class('socialized-sticky-' . (stripos($placement, 'stick') === false ? 'no' : 'yes'))),
                esc_attr(sanitize_html_class('placement-' . $placement)),
                esc_attr(sanitize_html_class('icon-type-' . $icon_type)),
                sprintf(
                    '<span class="share-text %s">%s</span>',
                    esc_attr($icon_type == 'text' ? '' : 'screen-reader-text'),
                    esc_html__('Share this on:', 'socialized')
                ),
                implode('', $buttons)
            ), array(
                'p' => array('class' => array()),
                'span' => array(
                    'id' => array(),
                    'class' => array(),
                    'role' => array(),
                    'aria-hidden' => array()
                ),
                'a' => array(
                    'id' => array(),
                    'class' => array(),
                    'title' => array(),
                    'data-platform' => array(),
                    'href' => array(),
                    'target' => array(),
                    'rel' => array(),
                    'onclick' => array(),
                    'width' => array(),
                    'height' => array(),
                    'tabindex' => array(),
                    'aria-describedby' => array(),
                    'disabled' => array()
                ),
                'img' => array(
                    'class' => array(),
                    'src' => array(),
                    'alt' => array(),
                    'width' => array(),
                    'height' => array()
                ),
                'i' => array(
                    'class' => array(),
                    'aria-hidden' => array()
                )
            ));
            if ($echo) {
                echo ($output); // Escaped right above
            }
        }
        return $output; // Escaped right above
    }

    /**
     * Display Buttons
     *
     * Displays social media sharing buttons on the frontend for a single post.
     *
     * @since 1.0.0
     *
     * @param string $content. Post content.
     *
     * @return string Modified post content.
     */
    public function display_buttons($content = '', $placement = '', $post_id = '', $preview = false)
    {
        $fullcontent = $content;
        if (is_numeric($post_id) && ($post_id = intval($post_id)) > 0) {
            $post = get_post($post_id);
        } else {
            global $post;
        }
        if (gettype($post) == 'object' && Settings::is_allowed($post)) {
            //Only place automatically if the settings aren't set to hidden and if there isn't a shortcode already in the content
            $placement = $placement ? $placement : Settings::get('buttons_location', true, 'display');
            /*
                This function has a priority of 20 (default is 10), so most shortcodes have already
                been processed before this is run, that's why we're checking for the compiled
                code instead of the shortcode.
            */
            $contains_shortcode = $content && stripos($content, '<p class="socialized-links') !== false;
            if ($placement != 'hide' && !$contains_shortcode) {
                $buttons = $this->get_buttons($post->ID, false, $placement, $preview); // Buttons have already been escaped
                switch ($placement) {
                    case 'end': // Display at the end by simply appending to content
                        $fullcontent = $content . $buttons;
                        break;
                    case 'stick-left':
                    case 'stick-right':
                        // If sticky, will need to wrap content in specialized div
                        if (Settings::get('icon_type', true, 'display') != 'text') {
                            // Text buttons can't be sticky
                            $fullcontent = sprintf(
                                '<div class="socialized-sticky-wrapper %s">%s%s</div>',
                                esc_attr(sanitize_html_class($placement)),
                                $buttons, // Already escaped
                                $content // For WordPress to deal with
                            );
                        } else {
                            // Display at the beginning by simply prepending to content
                            $fullcontent = $buttons . $content;
                        }
                        break;
                    default: // Display at the beginning by simply prepending to content
                        $fullcontent = $buttons . $content;
                        break;
                }
            }
        }
        return $fullcontent;
    }

    /**
     * Perform the Redirect
     *
     * When hitting a 404 page, read the URL and redirect to the appropriate article with the defined UTM parameters.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function redirect()
    {
        if (!is_404() || !Settings::get('redirecting', true, 'advanced')) {
            return; // Bail, this is not a 404 page or the plugin is set to not redirect
        }

        // Attempt getting cached version first
        $path = wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $redirect_data = Utilities::get_cache($path, 'transient', 'redirects');
        // If cached data isn't available, look it up
        if (!is_array($redirect_data) || !count($redirect_data)) {

            if (substr_count($path, '/') !== 1) {
                return; // Bail, anything else is unlikely to have come from this plugin
            }

            // Identify our slug
            $slug = basename($path);
            $slug_parts = explode('-', $slug); //In case it was added in the generation bit, get the last one
            $slug_parts_c = count($slug_parts); //Should be at least 2, maybe more if the dash was used in the generated text
            if ($slug_parts_c < 2) {
                return; // Bail, not enough parts to be a vanity URL from this plugin
            }

            // Identify our platform suffix
            $suffix = '-' . $slug_parts[$slug_parts_c - 1]; // Gets the last part, our platform suffix
            unset($slug_parts[$slug_parts_c - 1]); // Removes the suffix from the array
            $slug = implode('-', $slug_parts); // Reform the remaining slug back into a string
            $platforms = Settings::get_platforms();
            $platform_suffixes = array_column($platforms, 'suffix'); // Returns an array of platform suffixes
            $platform_index = array_search($suffix, $platform_suffixes);
            if ($platform_index === false) {
                return; // Bail, for this suffix is unsupported by this plugin
            }
            $platform_keys = array_keys($platforms);
            $platform = sanitize_key($platform_keys[$platform_index]);

            // Identify the page
            global $socialized; // Access main class
            $page = $socialized->get_page_by_slug($slug, false);
            if ($page === false) {
                return; // Bail, failed to identify the page by our slug
            }

            // Get the redirect data
            $redirect_data = array(
                'post_id' => $page['id'],
                'redirect_url' => sanitize_url($page['url'] . '?' . $socialized->get_permalink_with_query($platform, $page['id'], false, $page['type'])),
                'platform' => $platform
            );
            Utilities::set_cache($path, $redirect_data, 4, 'transient', 'redirects');
        }

        if (!is_array($redirect_data) || !count($redirect_data)) {
            return; // Bail, still couldn't find redirect data after lookup
        }

        // Track usage for users that view the post but can't edit it
        if (!current_user_can('edit_post', $redirect_data['post_id'])) {
            $this->register_hit($redirect_data['post_id']); // Track pageview for the page in general
            $this->register_hit($redirect_data['post_id'], $redirect_data['platform']); // Track pageview for the platform for this page
        }

        // Append any additional query, if it exists
        $query = wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        $redirect_to = $redirect_data['redirect_url'];
        if (!is_null($query)) {
            // Append it to the URL
            $redirect_to .= '&' . $query;
        }

        // 301 Moved Permanently, SEO transfers "page rank" to the new location
        wp_safe_redirect($redirect_to, 301, Settings::$vars['name']);
        exit;
    }

    /**
     * Register Redirect Hit
     *
     * Registers a hit from a user being redirected using this plugin.
     *
     * @since 1.0.0
     *
     * @param integer $post_id Post ID.
     * @param string $platform Optional. Platform key identifier.
     *
     * @return integer $hits Total number of hits for this post or platform on this post.
     */
    private function register_hit($post_id, $platform = false)
    {
        $key = 'hits';
        if ($platform) {
            $key .= '_' . $platform;
        }
        $hits = Settings::get_hits($post_id, $platform);
        $hits++;
        Utilities::set_meta($post_id, $key, $hits);
        return $hits;
    }
}
