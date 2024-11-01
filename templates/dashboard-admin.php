<?php
if (isset($_GET['settings-updated'])) {
    add_settings_error(
        $args['plugin_settings']['prefix'] . 'messages',
        $args['plugin_settings']['prefix'] . 'message',
        __('Settings Saved!', 'socialized'),
        'success'
    );
}
settings_errors($args['plugin_settings']['prefix'] . 'messages'); ?>
<div class="au-plugin">
    <h1><?php printf(
            '<img width="293" height="60" src="%3$s" title="%1$s %2$s" alt="%1$s %2$s" />',
            esc_attr($args['plugin_settings']['name']),
            esc_attr__('by AuRise Creative', 'socialized'),
            esc_url($args['plugin_settings']['url'] . 'assets/images/admin-logo.png')
        ); ?></h1>
    <div class="au-plugin-admin-ui">
        <div class="loading-spinner"><img src="<?php echo (esc_url($args['plugin_settings']['url'])); ?>assets/images/progress.gif" alt="" width="32" height="32" /></div>
        <div class="admin-ui hide">
            <nav class="nav-tab-wrapper">
                <a class="nav-tab" id="open-settings" href="#settings"><?php esc_html_e('Settings', 'socialized') ?></a>
                <a class="nav-tab" id="open-view" href="#view"><?php esc_html_e('View All', 'socialized') ?></a>
                <a class="nav-tab" id="open-about" href="#about"><?php esc_html_e('About', 'socialized') ?></a>
                <a class="nav-tab" id="open-regenerate" href="#regenerate"><?php esc_html_e('Generate Missing Vanity URLs', 'socialized') ?></a>
            </nav>
            <div id="tab-content" class="container">
                <section id="settings" class="tab">
                    <form method="post" action="options.php">
                        <?php $option_group = $args['plugin_settings']['hook'];
                        settings_fields($option_group);
                        global $wp_settings_fields;
                        if (isset($wp_settings_fields[$args['plugin_settings']['slug']][$option_group])) {
                            foreach ($args['plugin_settings']['options'] as $option_group_name => $group) {
                                if ($option_group_name !== 'hidden') {
                                    printf('<fieldset class="%s"><h2>%s</h2>', esc_attr($option_group_name), esc_html($group['title']));
                                    echo ('<table class="form-table" role="presentation">');
                                    foreach ((array)$wp_settings_fields[$args['plugin_settings']['slug']][$option_group] as $field) {
                                        if ($field['args']['group'] == $option_group_name) {
                                            // Open row
                                            if (!empty($field['args']['class'])) {
                                                echo '<tr class="' . esc_attr($field['args']['class']) . '">';
                                            } else {
                                                echo '<tr>';
                                            }

                                            // Label column
                                            if (!empty($field['args']['label_for'])) {
                                                echo ('<th scope="row"><label for="' . esc_attr($field['args']['label_for']) . '">' . esc_html($field['title']) . '</label></th>');
                                            } else {
                                                echo ('<th scope="row">' . esc_html($field['title']) . '</th>');
                                            }

                                            // Field column
                                            echo '<td>';
                                            call_user_func($field['callback'], $field['args']);
                                            echo '</td>';

                                            // Close row
                                            echo '</tr>';
                                        }
                                    }
                                    echo ('</table></fieldset>');
                                    submit_button(__('Save All Settings', 'socialized'));
                                }
                            }
                        } ?>
                    </form>
                </section>
                <section id="about" class="tab">
                    <p><?php echo (wp_kses_post(__("By adding campaign parameters to the destination URLs you use in your ad campaigns, you can collect information about the overall efficacy of those campaigns, and also understand where the campaigns are more effective. For example, your <em>Summer Sale</em> campaign might be generating lots of revenue, but if you're running the campaign in several different social apps, you want to know which of them is sending you the customers who generate the most revenue. Or if you're running different versions of the campaign via email, video ads, and in-app ads, you can compare the results to see where your marketing is most effective.", 'socialized'))); ?></p>
                    <p><?php esc_html_e('When a user clicks a referral link, the parameters you add are sent to Google Analytics, and the related data is available in the Campaigns reports.', 'socialized'); ?></p>
                    <p><a href="https://support.google.com/analytics/answer/10917952" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn more about Custom Campaigns in Google Analytics 4 (GA4).', 'socialized'); ?></a></p>
                    <p><?php esc_html_e('This plugin accomplishes two (2) things:', 'socialized'); ?></p>
                    <ol>
                        <li>
                            <?php esc_html_e('Automatically generates a vanity URL for each social media sharing button for each post that redirects to the post with the following UTM parameters and their appropriate respective values:', 'socialized'); ?>
                            <ul>
                                <li><strong>utm_id</strong>. <?php esc_html_e('Value:', 'socialized'); ?> <code>socialized</code></li>
                                <li><strong>utm_source</strong>. <?php esc_html_e('Possible value(s):', 'socialized'); ?> <?php echo (wp_kses('<code>' . implode('</code> | <code>', array_keys($args['platforms'])) . '</code>', array('code' => array()))); ?></li>
                                <li><strong>utm_source_platform</strong>. <?php esc_html_e('Value:', 'socialized'); ?> <code><?php esc_html_e(get_bloginfo('name') . ' ' . __('WordPress Website', 'socialized')); ?></code></li>
                                <li><strong>utm_medium</strong>. <?php esc_html_e('Possible value(s):', 'socialized'); ?> <code>social</code> | <code>email</code></li>
                                <li><strong>utm_content</strong>. <?php esc_html_e('Possible value(s):', 'socialized'); ?> <code><?php esc_html_e($args['plugin_settings']['slug']); ?>-share-link</code></li>
                                <li><strong>utm_campaign</strong>. <?php esc_html_e('Possible value(s):', 'socialized'); ?> <code><?php esc_html_e($args['plugin_settings']['slug']); ?></code> | <?php _e('or define in <em>Settings</em>', 'socialized'); ?></li>
                                <li>
                                    <strong>utm_term</strong>:&nbsp;
                                    <?php esc_html_e('Possible value(s):', 'socialized'); ?>&nbsp;
                                    <?php esc_html_e('Defined by typing in the text box on the post or page', 'socialized'); ?>
                                    <?php if ($args['yoast-seo']) {
                                        printf(
                                            __(' | or the “<a href="%1$s" target="%3$s">Focus keyphrase</a>” by <a href="%2$s" target="%3$s" rel="noopener noreferrer">Yoast SEO</a>', 'socialized'),
                                            'https://yoast.com/focus-keyword/#utm_source=yoast-seo&utm_medium=referral&utm_term=focus-keyphrase-qm&utm_content=socialized-plugin&utm_campaign=wordpress-general&platform=wordpress',
                                            'https://wordpress.org/plugins/wordpress-seo/',
                                            '_blank'
                                        );
                                    } ?>
                                </li>
                                <li><strong>utm_creative_format</strong>. <?php esc_html_e('Value:', 'socialized'); ?> <code>user-share-link</code></li>
                                <li><strong>utm_marketing_tactic</strong>. <?php esc_html_e('Value:', 'socialized'); ?> <code>prospecting</code></li>
                            </ul>
                        </li>
                        <li><?php esc_html_e('Displays social media sharing links in the content of each post that uses these vanity URLs.', 'socialized'); ?></li>
                    </ol>
                    <p><?php esc_html_e('Your permalink struture will not be affected.', 'socialized'); ?></p>
                </section>
                <section id="regenerate" class="tab">
                    <p><?php echo (wp_kses(sprintf(
                            __('Click the button below to generate vanity URLs for the following post type(s) that do not already have them: <code>%s</code>. It was already run once when this plugin was activated, and each newly created one generates their own when it is first saved, even as a draft. If you created a custom post type and wish to generate links for all of those as well, be sure to save the custom post type\'s slug in <em>Settings</em> before clicking this button.', 'socialized'),
                            implode(', ', $args['post_types'])
                        ), array('code' => array()))); ?></p>
                    <p class="buttons">
                        <button id="generate-urls" class="button button-primary"><?php esc_html_e('Generate Missing Vanity URLs', 'socialized'); ?></button>
                        <span class="progress-spinner hide"><img src="<?php echo (esc_url($args['plugin_settings']['url'])); ?>assets/images/progress.gif" alt="" width="32" height="32" /></span>
                    </p>
                    <p id="generate-status" class="status-text notice notice-info hide"></p>
                </section>
                <section id="view" class="tab">
                    <?php echo ($args['view_table']); ?>
                </section>
            </div>
        </div>
    </div>
    <?php load_template($args['plugin_settings']['path'] . 'templates/dashboard-support.php', true, $args['plugin_settings']); ?>
</div>