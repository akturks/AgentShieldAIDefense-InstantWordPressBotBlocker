=== AgentShield AI Defense ===
Contributors: agentshield
Tags: ai, security, bot-protection, anti-scraping, prompt-injection, rate-limiting
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.2.0
License: GPLv2 or later

WordPress sitenizi agresif AI tarayıcılarından ve dolaylı prompt injection saldırılarından koruyun.

== Description ==

AgentShield AI Defense, dijital varlıklarınızı yapay zeka kaynaklı tehditlere karşı koruyan özel bir güvenlik eklentisidir.

**Özellikler:**

1. **İçerik Temizleyici** – Dolaylı prompt injection için kullanılan görünmez Unicode karakterleri temizler.
2. **AI Honeypot** – Botları "tuzak bölgeye" çekerek kaynakları tüketir ve yavaşlatır.
3. **Bot Engelleyici** – User-Agent tespiti ile bilinen AI tarayıcılarını anında engeller.
4. **Görünmez Filigran** – İçerikleri AI tarayıcılarına karşı izlenebilir şekilde işaretler; her içeriğin sabit, benzersiz bir karma ID'si vardır.
5. **IP Hız Sınırlama** – Kısa sürede çok fazla istek yapan IP adreslerini otomatik olarak engeller (429 Too Many Requests).

== Kurulum ==

1. Eklenti klasörünü `/wp-content/plugins/` dizinine yükleyin.
2. WordPress 'Eklentiler' menüsünden etkinleştirin.
3. 'AgentShield AI' menüsünden ayarları yapılandırın.

== Changelog ==

= 1.2.0 =
* YENİ: IP tabanlı hız sınırlama (rate limiting) eklendi — veritabanı destekli, ayarlanabilir limit ve pencere süresi.
* YENİ: Admin paneline istatistik sıfırlama butonu eklendi.
* YENİ: Admin paneli Türkçe arayüz ile yeniden düzenlendi.
* İYİLEŞTİRME: Filigran artık `uniqid()` yerine post ID + site URL tabanlı sabit bir SHA-256 karma ID kullanıyor — gerçek anlamda izlenebilir.
* İYİLEŞTİRME: Tuzak yönlendirmesi `init`'ten `template_redirect`'e taşındı.
* İYİLEŞTİRME: Bot engelleme artık admin panelinde çalışmıyor (`is_admin()` koruması).
* İYİLEŞTİRME: IP tespitinde Cloudflare ve proxy başlıkları (CF-Connecting-IP, X-Forwarded-For) destekleniyor.
* İYİLEŞTİRME: Daha fazla görünmez Unicode karakteri temizleniyor (U+00AD, U+2060 eklendi).
* DÜZELTME: Bot listesinde virgül sonrası boşluklar otomatik temizleniyor.


