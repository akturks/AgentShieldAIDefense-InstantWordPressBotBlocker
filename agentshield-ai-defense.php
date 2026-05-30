<?php
/**
 * Plugin Name: AgentShield AI Defense
 * Plugin URI: https://github.com/akturks
 * Description: Advanced AI scraper and indirect prompt injection defense mechanism for WordPress.
 * Version: 1.0.0
 * Author: AgentShield Team
 * License: GPL2
 * Text Domain: agentshield
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('AGENTSHIELD_VERSION', '1.0.0');
define('AGENTSHIELD_PATH', plugin_dir_path(__FILE__));
define('AGENTSHIELD_URL', plugin_dir_url(__FILE__));

// Load core functions
require_once AGENTSHIELD_PATH . 'includes/core-functions.php';

// Load admin settings
if (is_admin()) {
    require_once AGENTSHIELD_PATH . 'admin/settings-page.php';
}

// Activation/Deactivation hooks
register_activation_hook(__FILE__, 'agentshield_activate');
register_deactivation_hook(__FILE__, 'agentshield_deactivate');

function agentshield_activate() {
    // Set default options
    if (!get_option('agentshield_settings')) {
        update_option('agentshield_settings', [
            'enable_cleaner' => 1,
            'enable_honeypot' => 1,
            'trap_slug' => 'ai-trap-zone',
            'trap_message' => 'AgentShield AI Deception Matrix v1.0',
            'bot_block_list' => ['GPTBot', 'CCBot', 'ChatGPT-User', 'anthropic-ai', 'Claude-Web', 'Googlebot-Extended']
        ]);
    }
    flush_rewrite_rules();
}

function agentshield_deactivate() {
    flush_rewrite_rules();
}
