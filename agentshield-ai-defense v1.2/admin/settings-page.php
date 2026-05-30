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
            'enable_cleaner'    => isset($_POST['enable_cleaner'])    ? 1 : 0,
            'enable_honeypot'   => isset($_POST['enable_honeypot'])   ? 1 : 0,
            'enable_watermark'  => isset($_POST['enable_watermark'])  ? 1 : 0,
            'enable_rate_limit' => isset($_POST['enable_rate_limit']) ? 1 : 0,
            'trap_slug'         => sanitize_title($_POST['trap_slug']),
            'trap_message'      => sanitize_text_field($_POST['trap_message']),
            'rate_limit_count'  => max(1, (int) $_POST['rate_limit_count']),
            'rate_limit_window' => max(10, (int) $_POST['rate_limit_window']),
            'bot_block_list'    => array_filter(array_map('trim', array_map('sanitize_text_field', explode(',', $_POST['bot_block_list'])))),
        ];

        update_option('agentshield_settings', $new_settings);
        echo '<div class="updated notice is-dismissible"><p><strong>AgentShield:</strong> Ayarlar başarıyla kaydedildi.</p></div>';
    }

    // İstatistikleri sıfırla
    if (isset($_POST['agentshield_reset_stats'])) {
        check_admin_referer('agentshield_save_settings_action');
        update_option('agentshield_stats', ['trapped' => 0, 'blocked' => 0, 'last_bot' => 'None']);
        echo '<div class="updated notice is-dismissible"><p>İstatistikler sıfırlandı.</p></div>';
    }

    $settings = get_option('agentshield_settings', []);
    $stats    = get_option('agentshield_stats', ['trapped' => 0, 'blocked' => 0, 'last_bot' => 'None']);

    // Varsayılan değerler
    $defaults = [
        'enable_cleaner'    => 1,
        'enable_honeypot'   => 1,
        'enable_watermark'  => 0,
        'enable_rate_limit' => 1,
        'trap_slug'         => 'ai-trap-zone',
        'trap_message'      => 'AgentShield AI Deception Matrix v1.2',
        'rate_limit_count'  => 30,
        'rate_limit_window' => 60,
        'bot_block_list'    => ['GPTBot', 'CCBot', 'ChatGPT-User', 'anthropic-ai', 'Claude-Web', 'Googlebot-Extended'],
    ];
    $settings = wp_parse_args($settings, $defaults);
    ?>
    <div class="wrap">
        <h1>🛡️ AgentShield AI Defense <span style="font-size:13px;color:#666;font-weight:normal;">v<?php echo AGENTSHIELD_VERSION; ?></span></h1>

        <!-- İstatistik Kartları -->
        <div style="display:flex;gap:16px;margin:20px 0;flex-wrap:wrap;">
            <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;min-width:140px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.07);">
                <div style="font-size:32px;font-weight:700;color:#d63638;"><?php echo esc_html($stats['blocked']); ?></div>
                <div style="color:#555;margin-top:4px;">Engellenen Bot</div>
            </div>
            <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;min-width:140px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.07);">
                <div style="font-size:32px;font-weight:700;color:#dba617;"><?php echo esc_html($stats['trapped']); ?></div>
                <div style="color:#555;margin-top:4px;">Tuzağa Düşen Bot</div>
            </div>
            <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;min-width:260px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.07);">
                <div style="font-size:13px;color:#666;margin-bottom:4px;">Son Aktivite</div>
                <div style="font-size:13px;font-weight:600;word-break:break-all;"><?php echo esc_html($stats['last_bot']); ?></div>
            </div>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('agentshield_save_settings_action'); ?>

            <h2 class="title">Koruma Özellikleri</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">İçerik Temizleyici</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_cleaner" value="1" <?php checked(1, $settings['enable_cleaner']); ?> />
                            Aktif
                        </label>
                        <p class="description">İçeriklerdeki gizli Unicode karakterleri silerek dolaylı prompt injection saldırılarını önler.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">AI Honeypot (Tuzak)</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_honeypot" value="1" <?php checked(1, $settings['enable_honeypot']); ?> />
                            Aktif
                        </label>
                        <p class="description">Sayfaya gizli bir tuzak linki ekler; AI tarayıcılar bu linke girdiğinde yavaşlatılır.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">İçerik Filigranı</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_watermark" value="1" <?php checked(1, $settings['enable_watermark']); ?> />
                            Aktif
                        </label>
                        <p class="description">Tüm içeriklere gizli, izlenebilir bir filigran ekler. Her içeriğin benzersiz bir karma ID'si olur.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">IP Hız Sınırlama</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_rate_limit" value="1" <?php checked(1, $settings['enable_rate_limit']); ?> />
                            Aktif
                        </label>
                        <p class="description">Belirli sürede çok fazla istek gönderen IP adreslerini otomatik olarak engeller.</p>
                    </td>
                </tr>
            </table>

            <h2 class="title">Hız Sınırlama Ayarları</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Maksimum İstek Sayısı</th>
                    <td>
                        <input type="number" name="rate_limit_count" value="<?php echo esc_attr($settings['rate_limit_count']); ?>" class="small-text" min="1" />
                        <p class="description">Bir IP adresinin zaman penceresi içinde yapabileceği maksimum istek sayısı.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Zaman Penceresi (saniye)</th>
                    <td>
                        <input type="number" name="rate_limit_window" value="<?php echo esc_attr($settings['rate_limit_window']); ?>" class="small-text" min="10" />
                        <p class="description">Hız sınırının ölçüldüğü süre (örn. 60 = 1 dakika).</p>
                    </td>
                </tr>
            </table>

            <h2 class="title">Tuzak Ayarları</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Tuzak URL Slug'ı</th>
                    <td>
                        <input type="text" name="trap_slug" value="<?php echo esc_attr($settings['trap_slug']); ?>" class="regular-text" />
                        <p class="description">Botların yönlendirileceği URL (örn. <code>ai-trap-zone</code> → siteniz.com/ai-trap-zone/).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Tuzak Sayfası Mesajı</th>
                    <td>
                        <input type="text" name="trap_message" value="<?php echo esc_attr($settings['trap_message']); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>

            <h2 class="title">Bot Engelleme Listesi</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Engellenecek User-Agent'lar</th>
                    <td>
                        <textarea name="bot_block_list" rows="6" cols="50" class="large-text code"><?php echo esc_textarea(implode(', ', (array) $settings['bot_block_list'])); ?></textarea>
                        <p class="description">Virgülle ayırarak yazın. Büyük/küçük harf duyarsız eşleşme yapılır.</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="agentshield_save_settings" class="button button-primary" value="Ayarları Kaydet" />
                &nbsp;
                <input type="submit" name="agentshield_reset_stats" class="button button-secondary" value="İstatistikleri Sıfırla"
                    onclick="return confirm('İstatistikler sıfırlanacak. Emin misiniz?');" />
            </p>
        </form>
    </div>
    <?php
}
