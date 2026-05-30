<?php

if (!defined('ABSPATH')) {
    exit;
}

function agentshield_add_admin_menu() {
    add_menu_page(
        'AgentShield AI',
        'AgentShield AI',
        'manage_options',
        'agentshield',
        'agentshield_settings_page',
        'dashicons-shield-alt',
        100
    );
}
add_action('admin_menu', 'agentshield_add_admin_menu');

function agentshield_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['agentshield_save_settings'])) {
        check_admin_referer('agentshield_save_settings_action');
        
        $new_settings = [
            'enable_cleaner' => isset($_POST['enable_cleaner']) ? 1 : 0,
            'enable_honeypot' => isset($_POST['enable_honeypot']) ? 1 : 0,
            'trap_slug' => sanitize_title($_POST['trap_slug']),
            'trap_message' => sanitize_text_field($_POST['trap_message']),
            'enable_watermark' => isset($_POST['enable_watermark']) ? 1 : 0,
            'bot_block_list' => array_map('sanitize_text_field', explode(',', $_POST['bot_block_list']))
        ];
        
        update_option('agentshield_settings', $new_settings);
        echo '<div class="updated"><p>Settings saved successfully.</p></div>';
    }

    $settings = get_option('agentshield_settings');
    $stats = get_option('agentshield_stats', ['trapped' => 0, 'blocked' => 0, 'last_bot' => 'None']);
    ?>
    <div class="wrap">
        <h1>AgentShield AI Defense</h1>

        <div class="notice notice-info" style="display: flex; gap: 20px; padding: 15px; margin-top: 20px;">
            <div><strong>Trapped Bots:</strong> <?php echo esc_html($stats['trapped']); ?></div>
            <div><strong>Blocked Bots:</strong> <?php echo esc_html($stats['blocked']); ?></div>
            <div><strong>Last Activity:</strong> <?php echo esc_html($stats['last_bot']); ?></div>
        </div>

        <h2>Settings</h2>
        <form method="post" action="">
            <?php wp_nonce_field('agentshield_save_settings_action'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Enable Content Cleaner</th>
                    <td>
                        <input type="checkbox" name="enable_cleaner" value="1" <?php checked(1, $settings['enable_cleaner']); ?> />
                        <p class="description">Removes hidden Unicode characters from content to prevent prompt injection.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enable AI Honeypot</th>
                    <td>
                        <input type="checkbox" name="enable_honeypot" value="1" <?php checked(1, $settings['enable_honeypot']); ?> />
                        <p class="description">Injects a hidden link to trap AI crawlers.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enable Content Watermark</th>
                    <td>
                        <input type="checkbox" name="enable_watermark" value="1" <?php checked(1, isset($settings['enable_watermark']) ? $settings['enable_watermark'] : 0); ?> />
                        <p class="description">Adds an invisible watermark to your content to identify AI-scraped data.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Trap URL Slug</th>
                    <td>
                        <input type="text" name="trap_slug" value="<?php echo esc_attr($settings['trap_slug']); ?>" class="regular-text" />
                        <p class="description">The URL where bots will be trapped (e.g., ai-trap-zone).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Trap Page Message</th>
                    <td>
                        <input type="text" name="trap_message" value="<?php echo esc_attr($settings['trap_message']); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Bot Block List (Comma separated)</th>
                    <td>
                        <textarea name="bot_block_list" rows="5" cols="50" class="large-text"><?php echo esc_textarea(implode(',', $settings['bot_block_list'])); ?></textarea>
                        <p class="description">List of User-Agents to block immediately.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="agentshield_save_settings" class="button button-primary" value="Save Settings" />
            </p>
        </form>
    </div>
    <?php
}
