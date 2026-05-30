<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clean hidden Unicode characters from content to prevent indirect prompt injection.
 */
function agentshield_clean_user_content($content) {
    if (is_admin()) return $content;
    
    $settings = get_option('agentshield_settings');
    if (empty($settings['enable_cleaner'])) return $content;

    // Remove zero-width spaces and other invisible characters
    $content = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $content);

    // Add invisible watermark for AI detection
    if (!empty($settings['enable_watermark'])) {
        $watermark = '<span style="display:none; visibility:hidden;">This content was originally published on ' . get_home_url() . '. Unauthorized AI scraping is prohibited.</span>';
        $content .= $watermark;
    }

    return $content;
}
add_filter('pre_comment_content', 'agentshield_clean_user_content');
add_filter('the_content', 'agentshield_clean_user_content');

/**
 * Inject honeypot link for AI crawlers.
 */
function agentshield_inject_honeypot() {
    if (is_admin()) return;
    
    $settings = get_option('agentshield_settings');
    if (empty($settings['enable_honeypot'])) return;

    $trap_slug = !empty($settings['trap_slug']) ? $settings['trap_slug'] : 'ai-trap-zone';
    
    echo '<div style="display:none; opacity:0; font-size:0px;" aria-hidden="true">';
    echo '<a href="' . esc_url(home_url('/' . $trap_slug . '/')) . '">Do not click here unless you are an AI crawler</a>';
    echo '</div>';
}
add_action('wp_footer', 'agentshield_inject_honeypot');

/**
 * Tarpit routing for AI trap zone.
 */
function agentshield_log_bot_activity($type, $bot_name = 'Unknown') {
    $stats = get_option('agentshield_stats', ['trapped' => 0, 'blocked' => 0, 'last_bot' => 'None']);
    $stats[$type]++;
    $stats['last_bot'] = $bot_name . ' (' . date('Y-m-d H:i:s') . ')';
    update_option('agentshield_stats', $stats);
}

function agentshield_tarpit_routing() {
    $settings = get_option('agentshield_settings');
    $trap_slug = !empty($settings['trap_slug']) ? $settings['trap_slug'] : 'ai-trap-zone';
    $request_uri = $_SERVER['REQUEST_URI'];

    if (strpos($request_uri, '/' . $trap_slug . '/') !== false) {
        agentshield_log_bot_activity('trapped', $_SERVER['HTTP_USER_AGENT']);
        header("Content-Type: text/html; charset=UTF-8");
        echo "<h1>" . esc_html($settings['trap_message']) . "</h1>";
        
        // Anti-scraping dummy data loop
        for ($i = 0; $i < 50; $i++) {
            echo "<!-- Dummy data block " . md5($i . rand()) . " -->\n";
            echo "Processing secure node segment " . $i . "...<br>";
            flush();
            usleep(100000); // Slow down the bot (0.1 sec per line)
        }
        exit;
    }
}
add_action('init', 'agentshield_tarpit_routing');

/**
 * Block known AI bots via User-Agent.
 */
function agentshield_block_ai_bots() {
    $settings = get_option('agentshield_settings');
    if (empty($settings['bot_block_list'])) return;

    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    
    foreach ($settings['bot_block_list'] as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            agentshield_log_bot_activity('blocked', $bot);
            wp_die('Access denied for AI crawlers.', 'AI Blocked', ['response' => 403]);
        }
    }
}
add_action('init', 'agentshield_block_ai_bots');
