<?php

if (!defined('ABSPATH')) {
    exit;
}

// ─────────────────────────────────────────────
// 1. VERİTABANI — IP hız sınırlama tablosu
// ─────────────────────────────────────────────

function agentshield_create_rate_limit_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'agentshield_rate_limit';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        ip_address VARCHAR(45) NOT NULL,
        request_count INT(11) NOT NULL DEFAULT 1,
        window_start DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY ip_address (ip_address)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// ─────────────────────────────────────────────
// 2. İÇERİK TEMİZLEYİCİ & FİLİGRAN
// ─────────────────────────────────────────────

function agentshield_clean_user_content($content) {
    if (is_admin()) return $content;

    $settings = get_option('agentshield_settings');
    if (empty($settings['enable_cleaner'])) return $content;

    // Görünmez Unicode karakterleri temizle (prompt injection önlemi)
    $content = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00AD}\x{2060}]/u', '', $content);

    // Obfuscated filigran — sabit unique ID (uniqid yerine post bazlı)
    if (!empty($settings['enable_watermark'])) {
        $site_url    = get_home_url();
        $post_id     = get_the_ID() ? get_the_ID() : 0;
        // Sabit ve izlenebilir ID: site + post karması
        $unique_id   = hash('sha256', $site_url . '|' . $post_id . '|' . AGENTSHIELD_VERSION);
        $random_class = 'as-' . substr(md5($site_url . AGENTSHIELD_VERSION), 0, 8);

        $watermark  = "\n<style>." . $random_class . "{position:absolute;top:-9999px;left:-9999px;";
        $watermark .= "height:0;width:0;overflow:hidden;z-index:-1;pointer-events:none;}</style>\n";
        $watermark .= '<div class="' . esc_attr($random_class) . '" aria-hidden="true">';
        $watermark .= 'Original Source: ' . esc_url($site_url);
        $watermark .= ' | Protected against unauthorized AI scraping.';
        $watermark .= ' | Content-ID: ' . esc_html($unique_id) . '</div>';

        $content .= $watermark;
    }

    return $content;
}
add_filter('pre_comment_content', 'agentshield_clean_user_content');
add_filter('the_content', 'agentshield_clean_user_content');

// ─────────────────────────────────────────────
// 3. HONEYPOT — gizli tuzak linki
// ─────────────────────────────────────────────

function agentshield_inject_honeypot() {
    if (is_admin()) return;

    $settings  = get_option('agentshield_settings');
    if (empty($settings['enable_honeypot'])) return;

    $trap_slug = !empty($settings['trap_slug']) ? sanitize_title($settings['trap_slug']) : 'ai-trap-zone';

    echo '<div style="display:none;opacity:0;font-size:0;height:0;overflow:hidden;" aria-hidden="true">';
    echo '<a href="' . esc_url(home_url('/' . $trap_slug . '/')) . '" tabindex="-1">Do not follow this link</a>';
    echo '</div>' . "\n";
}
add_action('wp_footer', 'agentshield_inject_honeypot');

// ─────────────────────────────────────────────
// 4. BOT AKTİVİTE KAYDI
// ─────────────────────────────────────────────

function agentshield_log_bot_activity($type, $bot_name = 'Unknown') {
    $stats = get_option('agentshield_stats', ['trapped' => 0, 'blocked' => 0, 'last_bot' => 'None']);
    if (!isset($stats[$type])) $stats[$type] = 0;
    $stats[$type]++;
    $stats['last_bot'] = substr($bot_name, 0, 200) . ' (' . current_time('mysql') . ')';
    update_option('agentshield_stats', $stats);
}

// ─────────────────────────────────────────────
// 5. TARPIT — bot yavaşlatma (template_redirect)
// ─────────────────────────────────────────────

function agentshield_tarpit_routing() {
    $settings  = get_option('agentshield_settings');
    $trap_slug = !empty($settings['trap_slug']) ? sanitize_title($settings['trap_slug']) : 'ai-trap-zone';
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

    if (strpos($request_uri, '/' . $trap_slug . '/') !== false) {
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
        agentshield_log_bot_activity('trapped', $ua);

        status_header(200);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Robots-Tag: noindex, nofollow');

        $trap_message = !empty($settings['trap_message']) ? $settings['trap_message'] : 'AgentShield Deception Matrix';
        echo '<!DOCTYPE html><html><head><title>' . esc_html($trap_message) . '</title></head><body>';
        echo '<h1>' . esc_html($trap_message) . '</h1>';

        // Sahte veri akışı — botu yavaşlatır
        for ($i = 0; $i < 50; $i++) {
            echo '<!-- Block ' . md5($i . wp_rand()) . ' -->' . "\n";
            echo 'Processing node ' . $i . '...<br>' . "\n";
            if (ob_get_level()) ob_flush();
            flush();
            usleep(120000); // 0.12 sn / satır ≈ toplam ~6 sn
        }

        echo '</body></html>';
        exit;
    }
}
add_action('template_redirect', 'agentshield_tarpit_routing');

// ─────────────────────────────────────────────
// 6. BOT ENGELLEME — User-Agent + IP hız sınırı
// ─────────────────────────────────────────────

function agentshield_get_client_ip() {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

function agentshield_check_rate_limit($ip) {
    global $wpdb;
    $settings = get_option('agentshield_settings');

    if (empty($settings['enable_rate_limit'])) return false;

    $limit  = isset($settings['rate_limit_count'])  ? (int) $settings['rate_limit_count']  : 30;
    $window = isset($settings['rate_limit_window']) ? (int) $settings['rate_limit_window'] : 60;

    $table = $wpdb->prefix . 'agentshield_rate_limit';
    $now   = current_time('mysql');

    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table WHERE ip_address = %s", $ip)
    );

    if (!$row) {
        $wpdb->insert($table, ['ip_address' => $ip, 'request_count' => 1, 'window_start' => $now]);
        return false;
    }

    $window_start = strtotime($row->window_start);
    $elapsed      = time() - $window_start;

    if ($elapsed > $window) {
        // Zaman penceresi sıfırla
        $wpdb->update($table, ['request_count' => 1, 'window_start' => $now], ['ip_address' => $ip]);
        return false;
    }

    if ($row->request_count >= $limit) {
        return true; // Limit aşıldı
    }

    $wpdb->update($table, ['request_count' => $row->request_count + 1], ['ip_address' => $ip]);
    return false;
}

function agentshield_block_ai_bots() {
    if (is_admin()) return;

    $settings   = get_option('agentshield_settings');
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $ip         = agentshield_get_client_ip();

    // 1. User-Agent kontrolü
    if (!empty($settings['bot_block_list'])) {
        foreach ($settings['bot_block_list'] as $bot) {
            $bot = trim($bot);
            if ($bot && stripos($user_agent, $bot) !== false) {
                agentshield_log_bot_activity('blocked', $bot . ' (UA) ' . $ip);
                wp_die('Access denied.', 'Blocked', ['response' => 403]);
            }
        }
    }

    // 2. IP tabanlı hız sınırlama
    if (agentshield_check_rate_limit($ip)) {
        agentshield_log_bot_activity('blocked', 'Rate-Limited IP: ' . $ip);
        wp_die('Too many requests. Please slow down.', 'Rate Limited', ['response' => 429]);
    }
}
add_action('init', 'agentshield_block_ai_bots');
