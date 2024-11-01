<?php

use AuRise\Plugin\Socialized\Utilities;
?>
<div class="au-metabox-<?php esc_attr_e($args['plugin_settings']['slug']); ?>">

    <fieldset>
        <?php
        foreach ($args['post_options'] as $setting_id => $field) {
            printf(
                '<p class="input-field"><label for="%1$s">%2$s%4$s</label><input %3$s /></p>',
                esc_attr($args['plugin_settings']['prefix'] . $setting_id),
                esc_html($field['label']),
                Utilities::format_atts($field['atts']),
                $field['note']
            );
        }
        ?>
        <button type="submit" name="<?php esc_attr_e($args['plugin_settings']['prefix']); ?>submit" class="button button-primary"><?php esc_html_e('Update Links', 'socialized'); ?></button>
        <span class="loading-spinner hide"><i class="fa-solid fa-spinner fa-spin"></i></span>
        <p id="update-links-output" class="notice notice-info is-dismissible hide"></p>
    </fieldset>
    <?php if (count($args['platforms'])) : ?>
        <ol>
            <?php foreach ($args['platforms'] as $key => $platform) : ?>
                <li id="<?php esc_attr_e($args['plugin_settings']['prefix'] . $key); ?>">
                    <p><strong><?php esc_html_e($platform['title']); ?></strong></p>
                    <ul>
                        <li class="vanity-url"><strong><?php esc_html_e('Vanity URL:', 'socialized'); ?></strong>&nbsp;<?php printf('<a href="%1$s" target="_blank" class="value">%2$s</a>', esc_url(home_url($platform['slug'])), esc_html($platform['slug'])); ?></li>
                        <li class="hits"><strong><?php esc_html_e('Hits:', 'socialized'); ?></strong>&nbsp;<span class="value"><?php esc_html_e($platform['hits'] ? $platform['hits'] : 0); ?></span></li>
                        <li class="campaign-source"><strong><?php esc_html_e('Source:', 'socialized'); ?></strong>&nbsp;<span class="value"><?php esc_html_e($platform['query']['utm_source']); ?></span></li>
                        <li class="campaign-medium"><strong><?php esc_html_e('Medium:', 'socialized'); ?></strong>&nbsp;<span class="value"><?php esc_html_e($platform['query']['utm_medium']); ?></span></li>
                        <li class="campaign-name"><strong><?php esc_html_e('Campaign Name:', 'socialized'); ?></strong>&nbsp;<span class="value"><?php esc_html_e($platform['query']['utm_campaign']); ?></span></li>
                        <li class="campaign-source-platform"><strong><?php esc_html_e('Source Platform:', 'socialized'); ?></strong>&nbsp;<span class="value"><?php esc_html_e($platform['query']['utm_source_platform']); ?></span></li>
                        <li class="campaign-content"><strong><?php esc_html_e('Content:', 'socialized'); ?></strong>&nbsp;<span class="value"><?php esc_html_e($platform['query']['utm_content']); ?></span></li>
                        <?php if (!empty($platform['query']['utm_term'])) : ?>
                            <li class="campaign-term"><strong><?php esc_html_e('Term:', 'socialized'); ?></strong>&nbsp;<span class="value"><?php esc_html_e($platform['query']['utm_term']); ?></span></li>
                        <?php endif; ?>
                        <li class="campaign-creative-format"><strong><?php esc_html_e('Creative Format:', 'socialized'); ?></strong>&nbsp;<span class="value"><?php esc_html_e($platform['query']['utm_creative_format']); ?></span></li>
                        <li class="campaign-marketing-tactic"><strong><?php esc_html_e('Marketing Tactic:', 'socialized'); ?></strong>&nbsp;<span class="value"><?php esc_html_e($platform['query']['utm_marketing_tactic']); ?></span></li>
                        <li class="full-url"><strong><?php esc_html_e('Redirected Query:', 'socialized'); ?></strong>&nbsp;<?php
                                                                                                                            printf(
                                                                                                                                '<a href="%s" target="_blank" class="value"><code>%s</code></a>',
                                                                                                                                esc_url($args['post']['url'] . (strpos($args['post']['url'], '?') === false ? '?' : '&') . $platform['query_str']),
                                                                                                                                esc_html('?' . $platform['query_str'])
                                                                                                                            ); ?></li>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php else : ?>
        <p><?php esc_html_e('Edit the vanity slug above and save or', 'socialized'); ?> <a href="<?php echo (esc_url($args['plugin_settings']['admin_url'])); ?>"><?php esc_html_e('automatically generate the missing ones', 'socialized'); ?></a>.</p>
    <?php endif;
    if (!get_option($args['plugin_settings']['prefix'] . 'redirecting')) : ?>
        <p><strong><em><?php esc_html_e('Redirects are disabled!', 'socialized'); ?></em></strong> <a href="<?php echo (esc_url($args['plugin_settings']['admin_url'])); ?>"><?php esc_html_e('Enable vanity URLs', 'socialized'); ?></a></p>
    <?php endif; ?>
    <p><a href="<?php echo (esc_url($args['plugin_settings']['admin_url'])); ?>"><?php esc_html_e('Edit Global Settings', 'socialized'); ?></a></p>
</div>